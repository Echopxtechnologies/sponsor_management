<?php
defined('BASEPATH') or exit('No direct script access allowed');

class School_model extends App_Model
{
    private $tbl_students = 'tblschool_students';
    private $tbl_sname    = 'tblschool_name';
    private $tbl_bank     = 'tblbank';
    private $tbl_rcard    = 'tblschool_report_card';
    private $tbl_sponsor  = 'tblsponsor_records';
    private $tbl_sponsor_txn = 'tblsponsor_transactions';

    // Grade to age mapping for Sri Lankan education system
    private $grade_age_mapping = [
        1 => ['min' => 5, 'max' => 6],
        2 => ['min' => 6, 'max' => 7],
        3 => ['min' => 7, 'max' => 8],
        4 => ['min' => 8, 'max' => 9],
        5 => ['min' => 9, 'max' => 10],
        6 => ['min' => 10, 'max' => 11],
        7 => ['min' => 11, 'max' => 12],
        8 => ['min' => 12, 'max' => 13],
        9 => ['min' => 13, 'max' => 14],
        10 => ['min' => 14, 'max' => 15],
        11 => ['min' => 15, 'max' => 16], // O/L
        12 => ['min' => 16, 'max' => 17], // A/L1
        13 => ['min' => 17, 'max' => 18], // A/L2
        14 => ['min' => 18, 'max' => 19], // A/L Final
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ensure_required_tables();
    }

    /* ----------------- ENHANCED SPONSOR METHODS ----------------- */

    /**
     * Get sponsor information for a student
     * 
     * @param int $student_id Student ID
     * @return array|null Sponsor information
     */
    public function get_student_sponsor($student_id)
    {
        $student_id = (int)$student_id;
        if ($student_id <= 0) return null;

        // First try direct sponsor_id relationship
        $this->db->select('
            sr.id as sponsor_id,
            sr.name as sponsor_name,
            sr.email as sponsor_email,
            sr.sponsor_type,
            sr.sponsor_occupation,
            "direct" as relationship_type
        ');
        $this->db->from($this->tbl_students . ' ss');
        $this->db->join($this->tbl_sponsor . ' sr', 'sr.id = ss.sponsor_id', 'inner');
        $this->db->where('ss.id', $student_id);
        $this->db->where('ss.sponsor_id IS NOT NULL');
        
        $direct_sponsor = $this->db->get()->row_array();
        if ($direct_sponsor) {
            return $direct_sponsor;
        }

        // If no direct relationship, check sponsor_transactions table
        $this->db->select('
            sr.id as sponsor_id,
            sr.name as sponsor_name,
            sr.email as sponsor_email,
            sr.sponsor_type,
            sr.sponsor_occupation,
            st.total_amount,
            st.amount_paid,
            st.currency,
            st.payment_type,
            st.sponsorship_start,
            st.sponsorship_end,
            st.next_payment_due,
            "transaction" as relationship_type
        ');
        $this->db->from($this->tbl_sponsor_txn . ' st');
        $this->db->join($this->tbl_sponsor . ' sr', 'sr.id = st.sponsor_id', 'inner');
        $this->db->where('st.school_student_id', $student_id);
        $this->db->order_by('st.id', 'DESC');
        $this->db->limit(1);
        
        return $this->db->get()->row_array();
    }

    /**
     * Get all sponsors for a student (including transaction history)
     * 
     * @param int $student_id Student ID
     * @return array Array of sponsor relationships
     */
    public function get_student_sponsors_history($student_id)
    {
        $student_id = (int)$student_id;
        if ($student_id <= 0) return [];

        $sponsors = [];

        // Get direct sponsor relationship
        $direct = $this->get_student_sponsor($student_id);
        if ($direct && $direct['relationship_type'] === 'direct') {
            $sponsors[] = $direct;
        }

        // Get all sponsors from transactions
        $this->db->select('
            sr.id as sponsor_id,
            sr.name as sponsor_name,
            sr.email as sponsor_email,
            sr.sponsor_type,
            sr.sponsor_occupation,
            st.total_amount,
            st.amount_paid,
            st.currency,
            st.payment_type,
            st.sponsorship_start,
            st.sponsorship_end,
            st.next_payment_due,
            st.created_at as transaction_date,
            "transaction" as relationship_type
        ');
        $this->db->from($this->tbl_sponsor_txn . ' st');
        $this->db->join($this->tbl_sponsor . ' sr', 'sr.id = st.sponsor_id', 'inner');
        $this->db->where('st.school_student_id', $student_id);
        $this->db->order_by('st.created_at', 'DESC');
        
        $transaction_sponsors = $this->db->get()->result_array();
        
        // Merge and deduplicate
        foreach ($transaction_sponsors as $txn_sponsor) {
            $exists = false;
            foreach ($sponsors as $existing) {
                if ($existing['sponsor_id'] == $txn_sponsor['sponsor_id']) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $sponsors[] = $txn_sponsor;
            }
        }

        return $sponsors;
    }

    /**
     * Check if a student is currently sponsored
     * 
     * @param int $student_id Student ID
     * @return bool True if sponsored
     */
    public function is_student_sponsored($student_id)
    {
        $sponsor = $this->get_student_sponsor($student_id);
        return !empty($sponsor);
    }

    /**
     * Get sponsor summary for multiple students (for list views)
     * 
     * @param array $student_ids Array of student IDs
     * @return array Associative array [student_id => sponsor_info]
     */
    public function get_students_sponsor_summary($student_ids)
    {
        if (empty($student_ids)) return [];
        
        $student_ids = array_map('intval', $student_ids);
        $sponsor_map = [];

        // Get direct sponsor relationships
        $this->db->select('
            ss.id as student_id,
            sr.id as sponsor_id,
            sr.name as sponsor_name,
            sr.sponsor_type,
            "direct" as relationship_type
        ');
        $this->db->from($this->tbl_students . ' ss');
        $this->db->join($this->tbl_sponsor . ' sr', 'sr.id = ss.sponsor_id', 'inner');
        $this->db->where_in('ss.id', $student_ids);
        $this->db->where('ss.sponsor_id IS NOT NULL');
        
        $direct_sponsors = $this->db->get()->result_array();
        foreach ($direct_sponsors as $ds) {
            $sponsor_map[$ds['student_id']] = $ds;
        }

        // Get transaction-based sponsors for students without direct sponsors
        $remaining_students = array_diff($student_ids, array_keys($sponsor_map));
        if (!empty($remaining_students)) {
            $this->db->select('
                st.school_student_id as student_id,
                sr.id as sponsor_id,
                sr.name as sponsor_name,
                sr.sponsor_type,
                st.sponsorship_start,
                st.sponsorship_end,
                "transaction" as relationship_type,
                ROW_NUMBER() OVER (PARTITION BY st.school_student_id ORDER BY st.id DESC) as rn
            ', false);
            $this->db->from($this->tbl_sponsor_txn . ' st');
            $this->db->join($this->tbl_sponsor . ' sr', 'sr.id = st.sponsor_id', 'inner');
            $this->db->where_in('st.school_student_id', $remaining_students);
            
            // Get the latest transaction for each student
            $subquery = $this->db->get_compiled_select();
            $this->db->reset_query();
            
            $this->db->query("
                SELECT student_id, sponsor_id, sponsor_name, sponsor_type, sponsorship_start, sponsorship_end, relationship_type
                FROM ({$subquery}) ranked 
                WHERE rn = 1
            ");
            
            $txn_sponsors = $this->db->get()->result_array();
            foreach ($txn_sponsors as $ts) {
                if (!isset($sponsor_map[$ts['student_id']])) {
                    $sponsor_map[$ts['student_id']] = $ts;
                }
            }
        }

        return $sponsor_map;
    }

    /* ----------------- UPDATED CRUD OPERATIONS WITH SPONSOR INFO ----------------- */

    /**
     * Get all students with sponsor information
     * 
     * @return array Students with sponsor data
     */
   public function get_all()
{
    $c = $this->country_schema();

    $this->db->select('
        ss.*,
        sn.name AS school_name,
        b.name  AS bank_name' .
        ($c['table'] ? ', c.' . $c['name'] . ' AS country_name' : ', NULL AS country_name')
    , false);

    $this->db->from($this->tbl_students.' ss');
    $this->db->join($this->tbl_sname.' sn', 'sn.id = ss.school_name_id', 'left');

    if ($c['table']) {
        $this->db->join($c['table'].' c', 'c.'.$c['id'].' = ss.country_id', 'left');
    }

    $this->db->join($this->tbl_bank.' b', 'b.id = ss.bank_id', 'left');
    $this->db->order_by('ss.id', 'DESC');
    
    $students = $this->db->get()->result_array();
    
    // Enhance each student with complete sponsor information
    foreach ($students as &$student) {
        $sponsors = $this->get_student_sponsors_history($student['id']);
        
        if (!empty($sponsors)) {
            // Get the primary/latest sponsor
            $primary_sponsor = $sponsors[0];
            $student['sponsor_id'] = $primary_sponsor['sponsor_id'];
            $student['sponsor_name'] = $primary_sponsor['sponsor_name'];
            $student['sponsor_type'] = $primary_sponsor['sponsor_type'];
            $student['sponsor_email'] = $primary_sponsor['sponsor_email'];
            $student['sponsor_relationship_type'] = $primary_sponsor['relationship_type'];
            
            // Add all sponsors information for display
            $sponsor_names = [];
            $sponsor_types = [];
            foreach ($sponsors as $sponsor) {
                $sponsor_names[] = $sponsor['sponsor_name'];
                if (!empty($sponsor['sponsor_type'])) {
                    $sponsor_types[] = $sponsor['sponsor_type'];
                }
            }
            
            // For multiple sponsors, create combined display
            if (count($sponsors) > 1) {
                $student['all_sponsor_names'] = implode(', ', array_unique($sponsor_names));
                $student['all_sponsor_types'] = implode(', ', array_unique($sponsor_types));
                $student['sponsor_count'] = count($sponsors);
            } else {
                $student['all_sponsor_names'] = $student['sponsor_name'];
                $student['all_sponsor_types'] = $student['sponsor_type'];
                $student['sponsor_count'] = 1;
            }
        } else {
            // No sponsors
            $student['sponsor_id'] = null;
            $student['sponsor_name'] = '';
            $student['sponsor_type'] = '';
            $student['sponsor_email'] = '';
            $student['sponsor_relationship_type'] = '';
            $student['all_sponsor_names'] = '';
            $student['all_sponsor_types'] = '';
            $student['sponsor_count'] = 0;
        }
    }
    
    return $students;
}

    /**
     * Get student by ID with sponsor information
     * 
     * @param int $id Student ID
     * @return array|null Student data with sponsor info
     */
    public function get_by_id($id)
    {
        $c = $this->country_schema();

        $this->db->select('
        ss.*,
        sn.name AS school_name,
        b.name  AS bank_name,
        sr.id as sponsor_id,
        sr.name as sponsor_name,
        sr.sponsor_type,
        sr.email as sponsor_email,
        sr.sponsor_occupation,
        sr.address as sponsor_address,
        sr.city as sponsor_city' .
        ($c['table'] ? ', c.' . $c['name'] . ' AS country_name' : ', NULL AS country_name')
    , false);

        $this->db->from($this->tbl_students.' ss');
        $this->db->join($this->tbl_sname.' sn', 'sn.id = ss.school_name_id', 'left');
        $this->db->join($this->tbl_sponsor.' sr', 'sr.id = ss.sponsor_id', 'left');

        if ($c['table']) {
            $this->db->join($c['table'].' c', 'c.'.$c['id'].' = ss.country_id', 'left');
        }

        $this->db->join($this->tbl_bank.' b', 'b.id = ss.bank_id', 'left');
        $this->db->where('ss.id', (int)$id);
        
        $student = $this->db->get()->row_array();
        
        if ($student) {
            // If no direct sponsor, check for transaction-based sponsor
            if (empty($student['sponsor_id'])) {
                $txn_sponsor = $this->get_student_sponsor($student['id']);
                if ($txn_sponsor && $txn_sponsor['relationship_type'] === 'transaction') {
                    $student['sponsor_id'] = $txn_sponsor['sponsor_id'];
                    $student['sponsor_name'] = $txn_sponsor['sponsor_name'];
                    $student['sponsor_type'] = $txn_sponsor['sponsor_type'];
                    $student['sponsor_email'] = $txn_sponsor['sponsor_email'];
                    $student['sponsor_phone'] = $txn_sponsor['sponsor_phone'];
                    $student['sponsor_relationship_type'] = 'transaction';
                    
                    // Add transaction-specific fields
                    if (isset($txn_sponsor['total_amount'])) {
                        $student['sponsorship_amount'] = $txn_sponsor['total_amount'];
                        $student['sponsorship_currency'] = $txn_sponsor['currency'];
                        $student['sponsorship_start'] = $txn_sponsor['sponsorship_start'];
                        $student['sponsorship_end'] = $txn_sponsor['sponsorship_end'];
                        $student['payment_type'] = $txn_sponsor['payment_type'];
                    }
                }
            } else {
                $student['sponsor_relationship_type'] = 'direct';
                
                // Get transaction details even for direct sponsors
                $txn_details = $this->get_student_sponsor_transactions($student['id']);
                if (!empty($txn_details)) {
                    $latest_txn = $txn_details[0]; // Get the latest transaction
                    $student['sponsorship_amount'] = $latest_txn['total_amount'];
                    $student['sponsorship_currency'] = $latest_txn['currency'];
                    $student['sponsorship_start'] = $latest_txn['sponsorship_start'];
                    $student['sponsorship_end'] = $latest_txn['sponsorship_end'];
                    $student['payment_type'] = $latest_txn['payment_type'];
                }
            }
            
            // Get full sponsor history
            $student['sponsor_history'] = $this->get_student_sponsors_history($student['id']);
        }
        
        return $student;
    }

    /**
     * Get sponsor transactions for a student
     * 
     * @param int $student_id Student ID
     * @return array Transaction details
     */
    public function get_student_sponsor_transactions($student_id)
    {
        $student_id = (int)$student_id;
        if ($student_id <= 0) return [];

        $this->db->select('
            st.*,
            sr.name as sponsor_name,
            sr.sponsor_type
        ');
        $this->db->from($this->tbl_sponsor_txn . ' st');
        $this->db->join($this->tbl_sponsor . ' sr', 'sr.id = st.sponsor_id', 'left');
        $this->db->where('st.school_student_id', $student_id);
        $this->db->order_by('st.created_at', 'DESC');
        
        return $this->db->get()->result_array();
    }

    /**
     * Enhanced get_students_filtered method with sponsor information
     * 
     * @param array $filters Filter parameters
     * @param array $datatables_params DataTables parameters
     * @return array Filtered results with pagination info
     */
    public function get_students_filtered($filters = [], $datatables_params = [])
    {
        try {
            // Build base query with all necessary joins including sponsor
            $this->db->select('
                ss.*,
                sn.name AS school_name,
                b.name AS bank_name,
                c.short_name AS country_name,
                s.active as staff_active,
                s.staffid as staff_id,
                s.firstname as staff_firstname,
                s.lastname as staff_lastname,
                sr.id as sponsor_id,
                sr.name as sponsor_name,
                sr.sponsor_type,
                sr.email as sponsor_email
            ', false);

            $this->db->from($this->tbl_students . ' ss');
            $this->db->join($this->tbl_sname . ' sn', 'sn.id = ss.school_name_id', 'left');
            $this->db->join($this->tbl_bank . ' b', 'b.id = ss.bank_id', 'left');
            $this->db->join(db_prefix() . 'countries c', 'c.country_id = ss.country_id', 'left');
            $this->db->join(db_prefix() . 'staff s', 's.staffid = ss.staff_id', 'left');
            $this->db->join($this->tbl_sponsor . ' sr', 'sr.id = ss.sponsor_id', 'left');

            // Apply filters before getting count
            $this->apply_filters($filters);

            // Handle DataTables search
            if (!empty($datatables_params['search']['value'])) {
                $search = $this->db->escape_like_str($datatables_params['search']['value']);
                $this->db->group_start();
                $this->db->like('ss.name', $search);
                $this->db->or_like('ss.email', $search);
                $this->db->or_like('ss.contact_no', $search);
                $this->db->or_like('ss.school_internal_id', $search);
                $this->db->or_like('sn.name', $search);
                $this->db->or_like('sr.name', $search); // Add sponsor name to search
                $this->db->group_end();
            }

            // Clone query for counting filtered records
            $count_query = clone $this->db;
            $filtered_count = $count_query->count_all_results('', false);

            // Handle ordering
            if (!empty($datatables_params['order'])) {
                foreach ($datatables_params['order'] as $order) {
                    $column_index = (int)$order['column'];
                    $direction = strtoupper($order['dir']) === 'DESC' ? 'DESC' : 'ASC';
                    
                    // Map column indices to actual columns
                    $columns = [
                        0 => 'ss.id',
                        1 => 'ss.name', 
                        2 => 'ss.school_grade',
                        3 => 'sn.name',
                        4 => null, // Status column - handle separately
                        5 => 'ss.contact_no',
                        6 => 'sr.name' // Sponsor column
                    ];
                    
                    if (isset($columns[$column_index]) && $columns[$column_index] !== null) {
                        $this->db->order_by($columns[$column_index], $direction);
                    }
                }
            } else {
                $this->db->order_by('ss.id', 'DESC');
            }

            // Handle pagination
            if (isset($datatables_params['length']) && $datatables_params['length'] != -1) {
                $start = $datatables_params['start'] ?? 0;
                $length = $datatables_params['length'];
                $this->db->limit($length, $start);
            }

            $students = $this->db->get()->result_array();

            // Add computed status and additional sponsor info to each student
            // Add computed status and complete sponsor info to each student
foreach ($students as &$student) {
    $student['computed_status'] = $this->determine_student_status($student);
    
    // Get all sponsors for this student
    $sponsors = $this->get_student_sponsors_history($student['id']);
    
    if (!empty($sponsors)) {
        // Get the primary/latest sponsor
        $primary_sponsor = $sponsors[0];
        $student['sponsor_id'] = $primary_sponsor['sponsor_id'];
        $student['sponsor_name'] = $primary_sponsor['sponsor_name'];
        $student['sponsor_type'] = $primary_sponsor['sponsor_type'];
        $student['sponsor_email'] = $primary_sponsor['sponsor_email'] ?? '';
        $student['sponsor_relationship_type'] = $primary_sponsor['relationship_type'];
        
        // Add all sponsors information for display
        $sponsor_names = [];
        $sponsor_types = [];
        foreach ($sponsors as $sponsor) {
            $sponsor_names[] = $sponsor['sponsor_name'];
            if (!empty($sponsor['sponsor_type'])) {
                $sponsor_types[] = $sponsor['sponsor_type'];
            }
        }
        
        // For multiple sponsors, create combined display
        if (count($sponsors) > 1) {
            $student['all_sponsor_names'] = implode(', ', array_unique($sponsor_names));
            $student['all_sponsor_types'] = implode(', ', array_unique($sponsor_types));
            $student['sponsor_count'] = count($sponsors);
        } else {
            $student['all_sponsor_names'] = $student['sponsor_name'];
            $student['all_sponsor_types'] = $student['sponsor_type'];
            $student['sponsor_count'] = 1;
        }
    } else {
        // No sponsors
        $student['sponsor_id'] = null;
        $student['sponsor_name'] = '';
        $student['sponsor_type'] = '';
        $student['sponsor_email'] = '';
        $student['sponsor_relationship_type'] = '';
        $student['all_sponsor_names'] = '';
        $student['all_sponsor_types'] = '';
        $student['sponsor_count'] = 0;
    }
}
            return [
                'data' => $students,
                'recordsTotal' => $this->count_all(),
                'recordsFiltered' => $filtered_count
            ];

        } catch (Exception $e) {
            log_message('error', 'School_model::get_students_filtered - ' . $e->getMessage());
            
            return [
                'data' => [],
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Enhanced apply_filters method with sponsor filtering
     * 
     * @param array $filters Filter parameters
     */
    private function apply_filters($filters)
    {
        // Grade filter
        if (!empty($filters['grade'])) {
            $this->db->where('ss.school_grade', $filters['grade']);
        }

        // Status filter (requires complex logic)
        if (!empty($filters['status'])) {
            $this->apply_status_filter($filters['status']);
        }

        // School filter
        if (!empty($filters['school'])) {
            $this->db->like('sn.name', $filters['school']);
        }

        // Sponsor filter
        if (!empty($filters['sponsor'])) {
            $this->db->like('sr.name', $filters['sponsor']);
        }

        // Sponsorship status filter
        if (!empty($filters['sponsorship_status'])) {
            if ($filters['sponsorship_status'] === 'sponsored') {
                $this->db->group_start();
                $this->db->where('ss.sponsor_id IS NOT NULL');
                $this->db->or_where('ss.id IN (SELECT DISTINCT school_student_id FROM ' . $this->tbl_sponsor_txn . ' WHERE school_student_id IS NOT NULL)', null, false);
                $this->db->group_end();
            } elseif ($filters['sponsorship_status'] === 'unsponsored') {
                $this->db->where('ss.sponsor_id IS NULL');
                $this->db->where('ss.id NOT IN (SELECT DISTINCT school_student_id FROM ' . $this->tbl_sponsor_txn . ' WHERE school_student_id IS NOT NULL)', null, false);
            }
        }

        // City filter
        if (!empty($filters['city'])) {
            $this->db->like('ss.city', $filters['city']);
        }

        // Age range filter
        if (!empty($filters['age_min'])) {
            $this->db->where('ss.school_age >=', (int)$filters['age_min']);
        }
        if (!empty($filters['age_max'])) {
            $this->db->where('ss.school_age <=', (int)$filters['age_max']);
        }

        // Date range filters
        if (!empty($filters['created_from'])) {
            $this->db->where('DATE(ss.created_at) >=', $filters['created_from']);
        }
        if (!empty($filters['created_to'])) {
            $this->db->where('DATE(ss.created_at) <=', $filters['created_to']);
        }
    }

    /* ----------------- EXISTING METHODS (KEEP ALL UNCHANGED) ----------------- */

    public function validate_age_grade($grade, $age, $grade_mismatch_reason = null)
    {
        if (empty($grade) || $age === null || $age === '') {
            return ['valid' => true];
        }

        $grade_str = (string)$grade;
        $age = (int)$age;

        // No validation for grades above 10 (O/L and A/L students)
        if (in_array($grade_str, ['O/L', 'A/L1', 'A/L2', 'A/L Final'], true)) {
            return ['valid' => true];
        }

        $grade_int = (int)$grade;
        if (!isset($this->grade_age_mapping[$grade_int])) {
            return ['valid' => false, 'message' => 'Invalid grade specified'];
        }

        $expected = $this->grade_age_mapping[$grade_int];
        $min_age = $expected['min'];
        $max_age = $expected['max'];
        
        if ($age >= $min_age && $age <= $max_age) {
            return ['valid' => true];
        }

        if ($age < $min_age) {
            return [
                'valid' => false, 
                'message' => "Student is too young for Grade {$grade_int}. Age {$age} is below minimum required age of {$min_age} years.",
                'too_young' => true
            ];
        }

        if ($age === ($max_age + 1)) {
            if (empty($grade_mismatch_reason)) {
                return [
                    'valid' => false, 
                    'message' => "Age {$age} is one year older than typical for Grade {$grade_int} (expected {$min_age}-{$max_age} years). Please provide a grade mismatch reason.",
                    'requires_reason' => true
                ];
            }
            return ['valid' => true, 'has_mismatch' => true];
        }

        if ($age > ($max_age + 1)) {
            return [
                'valid' => false, 
                'message' => "Student is too old for Grade {$grade_int}. Age {$age} exceeds maximum allowed age of " . ($max_age + 1) . " years (expected {$min_age}-{$max_age}, max with reason: " . ($max_age + 1) . ").",
                'too_old' => true
            ];
        }

        return ['valid' => false, 'message' => 'Invalid age-grade combination'];
    }

    /* ----------------- INSTALL/CHECKS ----------------- */

    private function ensure_required_tables()
    {
        $this->ensure_school_name_table();
        $this->ensure_school_report_card_table();
        $this->ensure_bank_table();
        $this->ensure_school_students_photo_column();
        $this->ensure_sponsor_tables();
    }

    private function ensure_sponsor_tables()
    {
        // Ensure sponsor_records table exists
        if (!$this->db->table_exists($this->tbl_sponsor)) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `{$this->tbl_sponsor}` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) NOT NULL,
                    `email` varchar(255) DEFAULT NULL,
                    `phone` varchar(50) DEFAULT NULL,
                    `sponsor_type` varchar(100) DEFAULT NULL,
                    `sponsor_occupation` varchar(255) DEFAULT NULL,
                    `address` text DEFAULT NULL,
                    `city` varchar(100) DEFAULT NULL,
                    `created_at` datetime DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            ");
        }

        // Ensure sponsor_transactions table exists
        if (!$this->db->table_exists($this->tbl_sponsor_txn)) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `{$this->tbl_sponsor_txn}` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `sponsor_id` int(11) NOT NULL,
                    `school_student_id` int(11) DEFAULT NULL,
                    `university_student_id` int(11) DEFAULT NULL,
                    `total_amount` decimal(15,2) NOT NULL DEFAULT 0,
                    `amount_paid` decimal(15,2) NOT NULL DEFAULT 0,
                    `currency` varchar(10) NOT NULL DEFAULT 'INR',
                    `payment_type` varchar(30) NOT NULL DEFAULT 'one_time',
                    `sponsorship_start` date DEFAULT NULL,
                    `sponsorship_end` date DEFAULT NULL,
                    `created_at` datetime DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    KEY `sponsor_id` (`sponsor_id`),
                    KEY `school_student_id` (`school_student_id`),
                    KEY `university_student_id` (`university_student_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            ");
        }
    }

    private function ensure_school_students_photo_column()
    {
        $table = db_prefix().'school_students';
        if (!$this->db->table_exists($table)) return;

        if (!$this->db->field_exists('profile_photo', $table)) {
            $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `profile_photo` LONGBLOB NULL AFTER `name`");
        } else {
            $this->db->query("ALTER TABLE `{$table}` MODIFY COLUMN `profile_photo` LONGBLOB NULL");
        }
    }

    private function ensure_school_name_table()
    {
        $table = db_prefix().'school_name';
        if (!$this->db->table_exists($table)) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `{$table}` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) COLLATE latin1_swedish_ci NOT NULL,
                    `created_on` datetime DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
            ");
        } else {
            $this->db->query("ALTER TABLE `{$table}` MODIFY `name` varchar(255) COLLATE latin1_swedish_ci NOT NULL");
            $this->db->query("ALTER TABLE `{$table}` MODIFY `created_on` datetime DEFAULT current_timestamp()");
        }
    }

    private function ensure_school_report_card_table()
    {
        $table = db_prefix().'school_report_card';
        if (!$this->db->table_exists($table)) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `{$table}` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `student_school_id` int(11) NOT NULL,
                    `filename` varchar(255) COLLATE latin1_swedish_ci NOT NULL,
                    `term` enum('Term1','Term2','Term3') COLLATE latin1_swedish_ci NOT NULL,
                    `upload_date` date NOT NULL,
                    `report_card_file` varchar(255) COLLATE latin1_swedish_ci NULL,
                    `file_blob` mediumblob NULL,
                    `mime_type` varchar(100) COLLATE latin1_swedish_ci NULL,
                    `file_size` int(10) UNSIGNED NULL,
                    `sha256` char(64) COLLATE latin1_swedish_ci NULL,
                    `created_on` datetime NULL,
                    PRIMARY KEY (`id`),
                    KEY `student_school_id` (`student_school_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
            ");
            return;
        }

        if (!$this->db->field_exists('file_blob', $table)) {
            $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `file_blob` MEDIUMBLOB NULL AFTER `report_card_file`");
        } else {
            $this->db->query("ALTER TABLE `{$table}` MODIFY `file_blob` MEDIUMBLOB NULL");
        }

        foreach ([
            'report_card_file' => "varchar(255) COLLATE latin1_swedish_ci NULL",
            'mime_type'        => "varchar(100) COLLATE latin1_swedish_ci NULL",
            'file_size'        => "int(10) UNSIGNED NULL",
            'sha256'           => "char(64) COLLATE latin1_swedish_ci NULL",
            'filename'         => "varchar(255) COLLATE latin1_swedish_ci NOT NULL",
            'created_on'       => "datetime NULL",
        ] as $col => $def) {
            if ($this->db->field_exists($col, $table)) {
                $this->db->query("ALTER TABLE `{$table}` MODIFY `{$col}` {$def}");
            } else {
                $this->db->query("ALTER TABLE `{$table}` ADD `{$col}` {$def}");
            }
        }
    }

    private function ensure_bank_table()
    {
        $table = db_prefix().'bank';
        if (!$this->db->table_exists($table)) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `{$table}` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) COLLATE latin1_swedish_ci NOT NULL,
                    `created_on` datetime DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `name` (`name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
            ");
        }
    }

    /* ----------------- PHOTO HANDLING ----------------- */

    private function handle_profile_photo_upload()
    {
        if (!isset($_FILES['profile_photo'])) return null;
        if ($_FILES['profile_photo']['error'] === UPLOAD_ERR_NO_FILE) return null;
        if ($_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload failed (code '.$_FILES['profile_photo']['error'].')');
        }

        $tmp  = $_FILES['profile_photo']['tmp_name'];
        $size = (int)$_FILES['profile_photo']['size'];
        $name = $_FILES['profile_photo']['name'];

        if ($size <= 0 || $size > 5*1024*1024) {
            throw new Exception('File size too large. Max 5MB.');
        }

        $mime = $this->detect_mime_type($tmp, $_FILES['profile_photo']['type']);

        $allowed = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
        if (!in_array($mime, $allowed, true)) {
            throw new Exception('Invalid file type. Only JPG/PNG/GIF/WebP allowed. Detected: ' . $mime);
        }

        $file_extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception('Invalid file extension: ' . $file_extension);
        }

        $bytes = @file_get_contents($tmp);
        if ($bytes === false) throw new Exception('Could not read uploaded file.');

        if (function_exists('getimagesizefromstring')) {
            $image_info = getimagesizefromstring($bytes);
            if ($image_info === false) {
                throw new Exception('Invalid image data - not a valid image file');
            }
            
            if ($image_info[0] > 3000 || $image_info[1] > 3000) {
                throw new Exception('Image dimensions too large: ' . $image_info[0] . 'x' . $image_info[1] . '. Maximum: 3000x3000 pixels');
            }
        }

        return $bytes;
    }

    private function detect_mime_type($file_path, $uploaded_type = null)
    {
        $mime_type = 'application/octet-stream';
        
        if (function_exists('finfo_open')) {
            try {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo !== false) {
                    $detected_mime = finfo_file($finfo, $file_path);
                    finfo_close($finfo);
                    if ($detected_mime !== false) {
                        return strtolower($detected_mime);
                    }
                }
            } catch (Exception $e) {
                log_message('warning', 'finfo_open failed in School_model: ' . $e->getMessage());
            }
        }
        
        if (function_exists('getimagesize')) {
            try {
                $image_info = getimagesize($file_path);
                if ($image_info !== false && isset($image_info['mime'])) {
                    return strtolower($image_info['mime']);
                }
            } catch (Exception $e) {
                log_message('warning', 'getimagesize failed in School_model: ' . $e->getMessage());
            }
        }
        
        if (function_exists('mime_content_type')) {
            try {
                $detected_mime = mime_content_type($file_path);
                if ($detected_mime !== false) {
                    return strtolower($detected_mime);
                }
            } catch (Exception $e) {
                log_message('warning', 'mime_content_type failed in School_model: ' . $e->getMessage());
            }
        }
        
        if (!empty($uploaded_type)) {
            $uploaded_type = strtolower(trim($uploaded_type));
            $valid_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($uploaded_type, $valid_types)) {
                return $uploaded_type;
            }
        }
        
        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $extension_map = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
        ];
        
        if (isset($extension_map[$file_extension])) {
            return $extension_map[$file_extension];
        }
        
        if (is_readable($file_path)) {
            $file_content = file_get_contents($file_path, false, null, 0, 12);
            if ($file_content !== false) {
                if (substr($file_content, 0, 3) === "\xFF\xD8\xFF") {
                    return 'image/jpeg';
                }
                if (substr($file_content, 0, 4) === "\x89PNG") {
                    return 'image/png';
                }
                if (substr($file_content, 0, 3) === "GIF") {
                    return 'image/gif';
                }
                if (substr($file_content, 0, 4) === "RIFF" && substr($file_content, 8, 4) === "WEBP") {
                    return 'image/webp';
                }
            }
        }
        
        log_message('warning', 'Could not determine MIME type in School_model, using fallback: ' . $mime_type);
        return $mime_type;
    }

    public function get_profile_photo($student_id)
    {
        $row = $this->db->select('profile_photo')->where('id', (int)$student_id)->get($this->tbl_students)->row();
        return $row ? $row->profile_photo : null;
    }

    /* ----------------- CRUD OPERATIONS ----------------- */

   public function add($data)
{
    try {
        // Log incoming data for debugging
        log_message('debug', 'School_model::add - Incoming data: ' . json_encode(array_keys($data)));

        // Validate age-grade combination
        $grade = $data['grade'] ?? $data['school_grade'] ?? null;
        $age = $data['calculated_age'] ?? $data['school_age'] ?? null;
        $grade_mismatch_reason = $data['grade_mismatch_reason'] ?? null;
        
        if (!empty($grade) && !empty($age)) {
            $validation = $this->validate_age_grade($grade, $age, $grade_mismatch_reason);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }
        }

        // Handle profile photo upload FIRST
        $photo = null;
        try { 
            $photo = $this->handle_profile_photo_upload();
            if ($photo !== null) {
                log_message('debug', 'Profile photo uploaded successfully, size: ' . strlen($photo));
            }
        } catch (Exception $e) { 
            log_message('error', 'School photo upload: ' . $e->getMessage()); 
        }

        // Complete field mappings (handles multiple input formats)
        $field_mappings = [
            'name' => 'name',
            'phone' => 'contact_no',
            'contact_no' => 'contact_no',
            'email' => 'email',
            'address' => 'address',
            'city' => 'city',
            'postal_code' => 'zip',
            'zip' => 'zip',
            'country_id' => 'country_id',
            'school_id' => 'school_id',
            'school_type' => 'school_type',
            'school_name' => 'school_name_id',
            'school_name_id' => 'school_name_id',
            'grade' => 'school_grade',
            'school_grade' => 'school_grade',
            'grade_mismatch_reason' => 'grade_mismatch_reason',
            'school_grade_year' => 'school_grade_year',
            'dob' => 'school_student_dob',
            'school_student_dob' => 'school_student_dob',
            'bank_name' => 'bank_id',
            'bank_id' => 'bank_id',
            'bank_account_number' => 'school_bank_account_no',
            'school_bank_account_no' => 'school_bank_account_no',
            'bank_branch_number' => 'school_bank_branch_number',
            'school_bank_branch_number' => 'school_bank_branch_number',
            'bank_branch_info' => 'school_bank_branch_info',
            'school_bank_branch_info' => 'school_bank_branch_info',
            'sponsorship_start' => 'school_sponsorship_start_date',
            'school_sponsorship_start_date' => 'school_sponsorship_start_date',
            'sponsorship_end' => 'school_sponsorship_end_date',
            'school_sponsorship_end_date' => 'school_sponsorship_end_date',
            'introduced_by' => 'school_introducedby',
            'school_introducedby' => 'school_introducedby',
            'introduced_phone' => 'school_introducedph',
            'school_introducedph' => 'school_introducedph',
            'sponsor_id' => 'sponsor_id',
            'father_name' => 'school_father_name',
            'school_father_name' => 'school_father_name',
            'mother_name' => 'school_mother_name',
            'school_mother_name' => 'school_mother_name',
            'guardian_name' => 'school_guardian_name',
            'school_guardian_name' => 'school_guardian_name',
            'father_income' => 'school_father_income',
            'school_father_income' => 'school_father_income',
            'mother_income' => 'school_mother_income',
            'school_mother_income' => 'school_mother_income',
            'guardian_income' => 'school_guardian_income',
            'school_guardian_income' => 'school_guardian_income',
            'background_information' => 'background_info',
            'background_info' => 'background_info',
            'internal_comment' => 'internal_comment',
            'external_comment' => 'external_comment',
        ];

        // Process school name and bank FIRST
        $school_name_id = null;
        $bank_id = null;

        if (isset($data['school_name']) || isset($data['school_name_id'])) {
            $school_val = $data['school_name'] ?? ($data['school_name_id'] ?? '');
            if (!empty($school_val)) {
                $school_name_id = $this->get_or_create_school_name_id($school_val);
            }
        }

        if (isset($data['bank_name']) || isset($data['bank_id'])) {
            $bank_val = $data['bank_name'] ?? ($data['bank_id'] ?? '');
            if (!empty($bank_val)) {
                $bank_id = $this->get_or_create_bank_id($bank_val);
            }
        }

        // Build insert array with defaults
        $insert = [
            'school_internal_id' => $this->generate_internal_id(),
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Add profile photo if uploaded
        if ($photo !== null) {
            $insert['profile_photo'] = $photo;
        }

        // Add processed IDs
        $insert['school_name_id'] = $school_name_id;
        $insert['bank_id'] = $bank_id;

        // Process all other fields using field mappings
        foreach ($field_mappings as $input_field => $db_field) {
            if (array_key_exists($input_field, $data)) {
                $value = $data[$input_field];
                
                // Skip if already processed
                if (in_array($db_field, ['school_name_id', 'bank_id'], true)) {
                    continue;
                }
                
                // Handle special field types
                if ($db_field === 'school_grade') {
                    $insert[$db_field] = $this->toNullIfEmpty($value);
                } elseif ($db_field === 'school_grade_year') {
                    $insert[$db_field] = $this->toIntOrNull($value);
                } elseif ($db_field === 'school_student_dob') {
                    $insert[$db_field] = $this->toNullIfEmpty($value);
                    // Auto-calculate age from DOB
                    if (!empty($value)) {
                        $insert['school_age'] = $this->calc_age_int($value);
                    }
                } elseif (in_array($db_field, ['school_father_income', 'school_mother_income', 'school_guardian_income'], true)) {
                    $insert[$db_field] = $this->toFloatOrNull($value);
                } elseif ($db_field === 'sponsor_id') {
                    $insert[$db_field] = $this->toIntOrNull($value);
                } elseif ($db_field === 'country_id') {
                    $insert[$db_field] = $this->toIntOrNull($value);
                } else {
                    $insert[$db_field] = $this->toNullIfEmpty($value);
                }
            }
        }

        // Filter to only existing columns
        $insert = $this->filter_existing_columns($this->tbl_students, $insert);

        // Log what we're inserting (without binary data)
        $log_insert = $insert;
        if (isset($log_insert['profile_photo'])) {
            $log_insert['profile_photo'] = '[BINARY DATA ' . strlen($insert['profile_photo']) . ' bytes]';
        }
        log_message('debug', 'School_model::add - Insert data: ' . json_encode($log_insert));

        // Insert the record
        $this->db->insert($this->tbl_students, $insert);
        $id = (int)$this->db->insert_id();

        if ($id) {
            log_message('debug', 'School_model::add - Success! Student ID: ' . $id);
            return $id;
        }

        log_message('error', 'School_model::add - Failed to get insert ID');
        return false;

    } catch (Exception $e) {
        log_message('error', 'Error adding school student: ' . $e->getMessage());
        log_message('error', 'Stack trace: ' . $e->getTraceAsString());
        return ['success' => false, 'message' => 'Error adding student: ' . $e->getMessage()];
    }
}
 public function update($data, $id)
{
    try {
        $id = (int)$id;
        if ($id <= 0) {
            log_message('error', 'School_model::update - Invalid ID: ' . $id);
            return false;
        }

        // Log incoming data for debugging
        log_message('debug', '=== SCHOOL UPDATE START === Student ID: ' . $id);
        log_message('debug', 'POST data keys: ' . json_encode(array_keys($data)));
        log_message('debug', 'FILES data: ' . json_encode(array_keys($_FILES)));
        
        // Check if profile photo is being uploaded
        if (isset($_FILES['profile_photo'])) {
            log_message('debug', 'Profile photo in FILES - name: ' . $_FILES['profile_photo']['name'] . ', error: ' . $_FILES['profile_photo']['error'] . ', size: ' . $_FILES['profile_photo']['size']);
        } else {
            log_message('debug', 'No profile_photo in $_FILES');
        }

        // Validate age-grade combination
        $grade = $data['school_grade'] ?? null;
        $age = $data['school_age'] ?? null;
        $grade_mismatch_reason = $data['grade_mismatch_reason'] ?? null;
        
        if (!empty($grade) && !empty($age)) {
            $validation = $this->validate_age_grade($grade, $age, $grade_mismatch_reason);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }
        }

        // CRITICAL FIX: Remove empty profile_photo from POST data
        // This prevents the empty string from overriding the uploaded file
        if (array_key_exists('profile_photo', $data)) {
            if (empty($data['profile_photo']) || $data['profile_photo'] === '') {
                unset($data['profile_photo']);
                log_message('debug', 'Removed empty profile_photo from POST data');
            }
        }

        $update_data = [];

        // Handle profile photo upload - THIS MUST BE FIRST
        $photo_uploaded = false;
        try {
            log_message('debug', 'Attempting to handle profile photo upload...');
            $photo = $this->handle_profile_photo_upload();
            
            if ($photo !== null) {
                $update_data['profile_photo'] = $photo;
                $photo_uploaded = true;
                log_message('debug', '✓ Profile photo uploaded successfully! Size: ' . strlen($photo) . ' bytes');
            } else {
                log_message('debug', 'No profile photo uploaded (handle_profile_photo_upload returned null)');
            }
        } catch (Exception $e) {
            log_message('error', '✗ Profile photo upload FAILED: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            // Don't return false - continue with other updates
        }

        // Complete field mappings (same as add method)
        $field_mappings = [
            'name' => 'name',
            'phone' => 'contact_no',
            'contact_no' => 'contact_no',
            'email' => 'email',
            'address' => 'address',
            'city' => 'city',
            'postal_code' => 'zip',
            'zip' => 'zip',
            'country_id' => 'country_id',
            'school_id' => 'school_id',
            'school_internal_id' => 'school_internal_id',
            'school_type' => 'school_type',
            'school_name' => 'school_name_id',
            'school_name_id' => 'school_name_id',
            'grade' => 'school_grade',
            'school_grade' => 'school_grade',
            'grade_mismatch_reason' => 'grade_mismatch_reason',
            'school_grade_year' => 'school_grade_year',
            'dob' => 'school_student_dob',
            'school_student_dob' => 'school_student_dob',
            'bank_name' => 'bank_id',
            'bank_id' => 'bank_id',
            'bank_account_number' => 'school_bank_account_no',
            'school_bank_account_no' => 'school_bank_account_no',
            'bank_branch_number' => 'school_bank_branch_number',
            'school_bank_branch_number' => 'school_bank_branch_number',
            'bank_branch_info' => 'school_bank_branch_info',
            'school_bank_branch_info' => 'school_bank_branch_info',
            'sponsorship_start' => 'school_sponsorship_start_date',
            'school_sponsorship_start_date' => 'school_sponsorship_start_date',
            'sponsorship_end' => 'school_sponsorship_end_date',
            'school_sponsorship_end_date' => 'school_sponsorship_end_date',
            'introduced_by' => 'school_introducedby',
            'school_introducedby' => 'school_introducedby',
            'introduced_phone' => 'school_introducedph',
            'school_introducedph' => 'school_introducedph',
            'sponsor_id' => 'sponsor_id',
            'father_name' => 'school_father_name',
            'school_father_name' => 'school_father_name',
            'mother_name' => 'school_mother_name',
            'school_mother_name' => 'school_mother_name',
            'guardian_name' => 'school_guardian_name',
            'school_guardian_name' => 'school_guardian_name',
            'father_income' => 'school_father_income',
            'school_father_income' => 'school_father_income',
            'mother_income' => 'school_mother_income',
            'school_mother_income' => 'school_mother_income',
            'guardian_income' => 'school_guardian_income',
            'school_guardian_income' => 'school_guardian_income',
            'background_information' => 'background_info',
            'background_info' => 'background_info',
            'internal_comment' => 'internal_comment',
            'external_comment' => 'external_comment',
        ];

        // Process school name and bank
        if (array_key_exists('school_name', $data) || array_key_exists('school_name_id', $data)) {
            $school_val = $data['school_name'] ?? ($data['school_name_id'] ?? '');
            if (!empty($school_val)) {
                $update_data['school_name_id'] = $this->get_or_create_school_name_id($school_val);
            } else {
                $update_data['school_name_id'] = null;
            }
        }

        if (array_key_exists('bank_name', $data) || array_key_exists('bank_id', $data)) {
            $bank_val = $data['bank_name'] ?? ($data['bank_id'] ?? '');
            if (!empty($bank_val)) {
                $update_data['bank_id'] = $this->get_or_create_bank_id($bank_val);
            } else {
                $update_data['bank_id'] = null;
            }
        }

        // Map all other fields
        foreach ($field_mappings as $input_field => $db_field) {
            if (array_key_exists($input_field, $data)) {
                $value = $data[$input_field];
                
                // Skip if already processed
                if (in_array($db_field, ['school_name_id', 'bank_id'], true)) {
                    continue;
                }
                
                // Handle special field types
                if ($db_field === 'school_grade') {
                    $update_data[$db_field] = $this->toNullIfEmpty($value);
                } elseif ($db_field === 'school_grade_year') {
                    $update_data[$db_field] = $this->toIntOrNull($value);
                } elseif ($db_field === 'school_student_dob') {
                    $update_data[$db_field] = $this->toNullIfEmpty($value);
                    // Also update age if DOB is updated
                    if (!empty($value)) {
                        $update_data['school_age'] = $this->calc_age_int($value);
                    }
                } elseif (in_array($db_field, ['school_father_income', 'school_mother_income', 'school_guardian_income'], true)) {
                    $update_data[$db_field] = $this->toFloatOrNull($value);
                } elseif ($db_field === 'sponsor_id') {
                    $update_data[$db_field] = $this->toIntOrNull($value);
                } elseif ($db_field === 'country_id') {
                    $update_data[$db_field] = $this->toIntOrNull($value);
                } else {
                    $update_data[$db_field] = $this->toNullIfEmpty($value);
                }
            }
        }

        // Filter to only existing columns
        $update_data = $this->filter_existing_columns($this->tbl_students, $update_data);

        if (empty($update_data)) {
            log_message('debug', 'No data to update');
            return true;
        }

        // Log what we're updating (without binary data)
        $log_update = $update_data;
        if (isset($log_update['profile_photo'])) {
            $log_update['profile_photo'] = '[BINARY DATA ' . strlen($update_data['profile_photo']) . ' bytes]';
        }
        log_message('debug', 'Fields being updated: ' . json_encode(array_keys($log_update)));
        log_message('debug', 'Update data (sanitized): ' . json_encode($log_update));

        // Perform the update
        $this->db->where('id', $id);
        $result = $this->db->update($this->tbl_students, $update_data);

        if (!$result) {
            $error = $this->db->error();
            log_message('error', 'Database update failed: ' . json_encode($error));
            return false;
        }

        $affected = $this->db->affected_rows();
        log_message('debug', '✓ Update successful! Affected rows: ' . $affected);
        
        if ($photo_uploaded) {
            log_message('info', 'Profile photo updated successfully for school student ID: ' . $id);
        }

        return true;

    } catch (Exception $e) {
        log_message('error', 'Exception in School_model::update: ' . $e->getMessage());
        log_message('error', 'Stack trace: ' . $e->getTraceAsString());
        return false;
    }
}
    public function delete($id)
    {
        try {
            $cards = $this->db->where('student_school_id', (int)$id)->get($this->tbl_rcard)->result();
            foreach ($cards as $c) {
                if (!empty($c->report_card_file)) {
                    $abs = rtrim(FCPATH, '/\\') . '/' . ltrim($c->report_card_file, '/');
                    if (is_file($abs)) @unlink($abs);
                }
            }
            $this->db->where('student_school_id', (int)$id)->delete($this->tbl_rcard);

            $this->db->where('id', (int)$id)->delete($this->tbl_students);
            return $this->db->affected_rows() > 0;

        } catch (Exception $e) {
            log_message('error','Error deleting school student: '.$e->getMessage());
            return false;
        }
    }

    public function count_all()
    {
        return $this->db->count_all_results($this->tbl_students);
    }

    public function get_schools()
    {
        return $this->db->order_by('name','ASC')->get($this->tbl_sname)->result_array();
    }

    public function get_countries()
    {
        $c = $this->country_schema();
        if (!$c['table']) return [];

        $this->db->select($c['id'] . ' AS id, ' . $c['name'] . ' AS name', false);
        $this->db->from($c['table']);
        $this->db->order_by($c['name'], 'ASC');
        return $this->db->get()->result_array();
    }

    public function get_banks()
    {
        return $this->db->order_by('name', 'ASC')->get($this->tbl_bank)->result_array();
    }

    /* ----------------- REPORT CARDS ----------------- */

    public function get_report_cards($student_id)
    {
        $tbl = db_prefix().'school_report_card';
        
        $this->db->select('
            id,
            student_school_id,
            filename,
            term,
            upload_date,
            report_card_file,
            mime_type,
            file_size,
            sha256,
            created_on
        ', false);
        
        $this->db->where('student_school_id', (int)$student_id);
        $this->db->order_by('upload_date', 'DESC');
        $this->db->order_by('created_on', 'DESC');
        
        $rows = $this->db->get($tbl)->result_array();

        foreach ($rows as &$c) {
            $c['upload_date'] = !empty($c['upload_date'])
                ? date('M d, Y', strtotime($c['upload_date']))
                : '';

            $name = trim((string)($c['filename'] ?? ''));
            if ($name === '' && !empty($c['report_card_file'])) {
                $name = basename($c['report_card_file']);
            }
            if ($name === '') {
                $ext = '';
                if ($c['mime_type'] === 'application/pdf') $ext = '.pdf';
                elseif (in_array($c['mime_type'], ['image/jpeg','image/jpg'], true)) $ext = '.jpg';
                elseif ($c['mime_type'] === 'image/png') $ext = '.png';
                elseif ($c['mime_type'] === 'image/gif') $ext = '.gif';
                $name = 'report_card_'.$c['id'].$ext;
            }
            $c['display_name'] = $name;

            $c['file_url'] = admin_url('student_sponsor_portal/download_school_report_card/'.$c['id']);
            $c['download_url'] = $c['file_url'];
        }
        unset($c);

        return ['success' => true, 'report_cards' => $rows];
    }

    /* ----------------- HELPER METHODS ----------------- */

    private function validateForeignKey($field, $id)
    {
        if (!$id || $id <= 0) return false;
        
        switch ($field) {
            case 'country_id':
                $c = $this->country_schema();
                if (!$c['table']) return true;
                return $this->db->where($c['id'], $id)->count_all_results($c['table']) > 0;
                
            case 'bank_id':
                return $this->db->where('id', $id)->count_all_results($this->tbl_bank) > 0;
                
            case 'school_name_id':
                return $this->db->where('id', $id)->count_all_results($this->tbl_sname) > 0;
                
            case 'sponsor_id':
                return $this->db->where('id', $id)->count_all_results($this->tbl_sponsor) > 0;
                
            default:
                return true;
        }
    }

    private function country_schema()
    {
        return [
            'table' => db_prefix() . 'countries',
            'id' => 'country_id', 
            'name' => 'short_name'
        ];
    }

    private function get_or_create_country_id($country_name)
    {
        if (empty($country_name)) return null;
        
        // Find existing country
        $country = $this->db->where('short_name', $country_name)
                           ->get(db_prefix() . 'countries')
                           ->row();
        
        if ($country) {
            return (int)$country->country_id;
        }
        
        // Create new country
        $data = [
            'short_name' => $country_name,
            'long_name' => $country_name,
            'calling_code' => '+1'
        ];
        
        $this->db->insert(db_prefix() . 'countries', $data);
        return (int)$this->db->insert_id();
    }

    private function generate_internal_id()
    {
        $row = $this->db->select('MAX(CAST(SUBSTRING(school_internal_id, 4) AS UNSIGNED)) AS m', false)
                        ->like('school_internal_id', 'SCH', 'after')
                        ->get($this->tbl_students)->row();
        $next = ($row && $row->m) ? ((int)$row->m + 1) : 1;
        return 'SCH' . str_pad((string)$next, 3, '0', STR_PAD_LEFT);
    }

    public function get_or_create_school_name_id($name_or_id)
    {
        if (!$name_or_id) return null;
        if (is_numeric($name_or_id)) return (int)$name_or_id;

        $row = $this->db->get_where($this->tbl_sname, ['name'=>$name_or_id])->row();
        if ($row) return (int)$row->id;

        $this->db->insert($this->tbl_sname, ['name'=>$name_or_id]);
        return (int)$this->db->insert_id();
    }

    private function filter_existing_columns($table, array $payload)
    {
        $fields = $this->db->list_fields($table);
        if (!$fields) return $payload;
        $keep = [];
        foreach ($payload as $k=>$v) {
            if (in_array($k, $fields, true)) $keep[$k] = $v;
        }
        return $keep;
    }

    private function toFloatOrNull($v)
    {
        if ($v === '' || $v === null) return null;
        return is_numeric($v) ? (float)$v : null;
    }

    private function toIntOrNull($v)
    {
        if ($v === '' || $v === null) return null;
        return (int)$v;
    }

    private function toNullIfEmpty($v)
    {
        return ($v === '' || $v === null) ? null : $v;
    }

    private function calc_age_int($dob)
    {
        if (!$dob) return null;
        try {
            $d1 = new DateTime($dob);
            $d2 = new DateTime('now');
            return (int)$d1->diff($d2)->y;
        } catch (Exception $e) { return null; }
    }

    /**
     * Determine student status based on staff relationship
     * 
     * @param array $student Student data with staff info
     * @return string Status (active, inactive, verified, unverified)
     */
    private function determine_student_status($student)
    {
        if (empty($student['staff_id'])) {
            return 'unverified';
        }

        if (!empty($student['staff_active']) && $student['staff_active'] == 1) {
            return 'active';
        } elseif (isset($student['staff_active']) && $student['staff_active'] == 0) {
            return 'inactive';
        }

        return 'verified';
    }

    /**
     * Apply status-based filtering
     * 
     * @param string $status Status to filter by (active, inactive, verified, unverified)
     */
    private function apply_status_filter($status)
    {
        switch ($status) {
            case 'active':
                $this->db->where('ss.staff_id IS NOT NULL');
                $this->db->where('s.active', 1);
                break;
            case 'inactive':
                $this->db->where('ss.staff_id IS NOT NULL');
                $this->db->where('s.active', 0);
                break;
            case 'verified':
                $this->db->where('ss.staff_id IS NOT NULL');
                break;
            case 'unverified':
                $this->db->where('ss.staff_id IS NULL');
                break;
        }
    }

    /**
     * Server-side DataTables processing for students list
     * 
     * @param array $request_data Complete request data from DataTables and filters
     * @return array DataTables response format
     */
    public function get_students_datatables($request_data = [])
    {
        // Extract DataTables parameters
        $datatables_params = [
            'start' => (int)($request_data['start'] ?? 0),
            'length' => (int)($request_data['length'] ?? 25),
            'search' => [
                'value' => $request_data['search']['value'] ?? '',
                'regex' => false
            ],
            'order' => $request_data['order'] ?? []
        ];
        
        // Extract filter parameters
        $filters = [
            'grade' => $request_data['filter_grade'] ?? '',
            'status' => $request_data['filter_status'] ?? '',
            'school' => $request_data['filter_school'] ?? '',
            'sponsor' => $request_data['filter_sponsor'] ?? '', // Add sponsor filter
            'sponsorship_status' => $request_data['filter_sponsorship_status'] ?? '', // Add sponsorship status filter
            'city' => $request_data['filter_city'] ?? '',
            'age_min' => $request_data['filter_age_min'] ?? '',
            'age_max' => $request_data['filter_age_max'] ?? '',
            'created_from' => $request_data['filter_created_from'] ?? '',
            'created_to' => $request_data['filter_created_to'] ?? ''
        ];
        
        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== '' && $value !== null;
        });
        
        try {
            // Get filtered results
            $result = $this->get_students_filtered($filters, $datatables_params);
            
            // Format data for DataTables
            $formatted_data = [];
            foreach ($result['data'] as $student) {
                $formatted_data[] = $this->format_student_for_datatables($student);
            }
            
            return [
                'draw' => (int)($request_data['draw'] ?? 0),
                'recordsTotal' => $result['recordsTotal'],
                'recordsFiltered' => $result['recordsFiltered'],
                'data' => $formatted_data,
                'error' => null
            ];
            
        } catch (Exception $e) {
            log_message('error', 'School_model::get_students_datatables - ' . $e->getMessage());
            
            return [
                'draw' => (int)($request_data['draw'] ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Error loading students: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Format student data for DataTables display with sponsor information
     * 
     * @param array $student Raw student data
     * @return array Formatted data for DataTables
     */
    private function format_student_for_datatables($student)
    {
        $sid = (int)($student['id'] ?? 0);
        $name = (string)($student['name'] ?? '');
        $grade = (string)($student['school_grade'] ?? '');
        $school = (string)($student['school_name'] ?? '');
        $email = (string)($student['email'] ?? '');
        $phone = (string)($student['contact_no'] ?? '');
        
        // Sponsor information
        $sponsor_name = (string)($student['sponsor_name'] ?? '');
        $sponsor_type = (string)($student['sponsor_type'] ?? '');
        
        // Determine status
        $status_info = $this->get_student_status_info($student);
        
        // Generate avatar initials
        $initials = $this->generate_initials($name);
        
        return [
            'DT_RowId' => 'student-row-' . $sid,
            'DT_RowData' => [
                'student-id' => $sid,
                'grade' => $grade,
                'school' => mb_strtolower($school),
                'status' => $status_info['status'],
                'sponsor' => mb_strtolower($sponsor_name)
            ],
            'id' => $sid,
            'name' => [
                'display' => $name,
                'photo_url' => admin_url('student_sponsor_portal/display_school_photo/' . $sid),
                'initials' => $initials,
                'edit_url' => admin_url('student_sponsor_portal/school_student_form/' . $sid)
            ],
            'grade' => $grade,
            'school' => $school,
            'status' => $status_info,
            'contact' => [
                'email' => $email,
                'phone' => $phone
            ],
            'sponsor' => [
                'name' => $sponsor_name,
                'type' => $sponsor_type,
                'is_sponsored' => !empty($sponsor_name)
            ]
        ];
    }

    /**
     * Get comprehensive status information for a student
     * 
     * @param array $student Student data
     * @return array Status information with class, icon, and label
     */
    private function get_student_status_info($student)
    {
        $status = $this->determine_student_status($student);
        
        $status_map = [
            'active' => [
                'status' => 'active',
                'class' => 'success',
                'icon' => 'fa-check-circle',
                'label' => 'Active'
            ],
            'inactive' => [
                'status' => 'inactive',
                'class' => 'warning',
                'icon' => 'fa-pause-circle',
                'label' => 'Inactive'
            ],
            'verified' => [
                'status' => 'verified',
                'class' => 'info',
                'icon' => 'fa-check-circle',
                'label' => 'Verified'
            ],
            'unverified' => [
                'status' => 'unverified',
                'class' => 'default',
                'icon' => 'fa-question-circle',
                'label' => 'Unverified'
            ]
        ];
        
        return $status_map[$status] ?? $status_map['unverified'];
    }

    /**
     * Generate initials from student name
     * 
     * @param string $name Full name
     * @return string Initials (max 2 characters)
     */
    private function generate_initials($name)
    {
        $initials = '';
        foreach (preg_split('/\s+/', trim($name)) as $part) {
            if ($part !== '' && strlen($initials) < 2) {
                $initials .= strtoupper(substr($part, 0, 1));
            }
        }
        return $initials !== '' ? $initials : '•';
    }

    /**
     * Get statistics with filter support including sponsor information
     * 
     * @param array $filters Optional filters to apply
     * @return array Statistics array
     */
    public function get_statistics($filters = [])
    {
        $stats = [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'verified' => 0,
            'unverified' => 0,
            'sponsored' => 0,
            'unsponsored' => 0,
            'by_grade' => [],
            'by_status' => [],
            'by_sponsor_type' => [],
            'recent_additions' => 0
        ];

        try {
            // Build query with filters including sponsor information
            $this->db->select('
                ss.id,
                ss.school_grade,
                ss.staff_id,
                ss.sponsor_id,
                s.active as staff_active,
                ss.created_at,
                sr.sponsor_type
            ');
            $this->db->from($this->tbl_students . ' ss');
            $this->db->join(db_prefix() . 'staff s', 's.staffid = ss.staff_id', 'left');
            $this->db->join($this->tbl_sponsor . ' sr', 'sr.id = ss.sponsor_id', 'left');
            
            // Apply filters if provided
            if (!empty($filters)) {
                $this->apply_filters($filters);
            }
            
            $students = $this->db->get()->result_array();
            $stats['total'] = count($students);

            // Count recent additions (last 30 days)
            $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));

            foreach ($students as $student) {
                // Determine status
                $status = $this->determine_student_status($student);
                $stats['by_status'][$status] = ($stats['by_status'][$status] ?? 0) + 1;
                
                // Count by main categories
                if ($status === 'active') $stats['active']++;
                elseif ($status === 'inactive') $stats['inactive']++;
                elseif ($status === 'verified') $stats['verified']++;
                else $stats['unverified']++;

                // Count by sponsorship status
                $is_sponsored = !empty($student['sponsor_id']);
                if ($is_sponsored) {
                    $stats['sponsored']++;
                    
                    // Count by sponsor type
                    $sponsor_type = $student['sponsor_type'] ?? 'Unknown';
                    $stats['by_sponsor_type'][$sponsor_type] = ($stats['by_sponsor_type'][$sponsor_type] ?? 0) + 1;
                } else {
                    $stats['unsponsored']++;
                    
                    // Check for transaction-based sponsorship
                    $txn_sponsor = $this->get_student_sponsor($student['id']);
                    if ($txn_sponsor && $txn_sponsor['relationship_type'] === 'transaction') {
                        $stats['sponsored']++;
                        $stats['unsponsored']--;
                        
                        $sponsor_type = $txn_sponsor['sponsor_type'] ?? 'Unknown';
                        $stats['by_sponsor_type'][$sponsor_type] = ($stats['by_sponsor_type'][$sponsor_type] ?? 0) + 1;
                    }
                }

                // Count by grade
                $grade = $student['school_grade'] ?? 'Unknown';
                $stats['by_grade'][$grade] = ($stats['by_grade'][$grade] ?? 0) + 1;

                // Count recent additions
                if (!empty($student['created_at']) && $student['created_at'] >= $thirty_days_ago) {
                    $stats['recent_additions']++;
                }
            }

            return $stats;

        } catch (Exception $e) {
            log_message('error', 'School_model::get_statistics - ' . $e->getMessage());
            return $stats;
        }
    }

    // Maintain compatibility with existing methods
    public function update_student($data, $id) { return $this->update($data, $id); }
    public function delete_student($id) { return $this->delete($id); }

    /**
     * Get available filter options including sponsors
     * 
     * @return array Dropdown options
     */
    public function get_filter_options()
    {
        return [
            'grades' => $this->get_available_grades(),
            'schools' => $this->get_schools(),
            'sponsors' => $this->get_available_sponsors(),
            'cities' => $this->get_available_cities(),
            'countries' => $this->get_countries(),
            'banks' => $this->get_banks()
        ];
    }

    /**
     * Get available sponsors from existing relationships
     * 
     * @return array List of sponsors
     */
    public function get_available_sponsors()
    {
        // Get sponsors from direct relationships
        $this->db->select('DISTINCT sr.id, sr.name, sr.sponsor_type');
        $this->db->from($this->tbl_students . ' ss');
        $this->db->join($this->tbl_sponsor . ' sr', 'sr.id = ss.sponsor_id', 'inner');
        $this->db->where('ss.sponsor_id IS NOT NULL');
        $this->db->order_by('sr.name', 'ASC');
        
        $direct_sponsors = $this->db->get()->result_array();
        
        // Get sponsors from transactions
        $this->db->select('DISTINCT sr.id, sr.name, sr.sponsor_type');
        $this->db->from($this->tbl_sponsor_txn . ' st');
        $this->db->join($this->tbl_sponsor . ' sr', 'sr.id = st.sponsor_id', 'inner');
        $this->db->where('st.school_student_id IS NOT NULL');
        $this->db->order_by('sr.name', 'ASC');
        
        $txn_sponsors = $this->db->get()->result_array();
        
        // Merge and deduplicate
        $sponsors = [];
        $seen_ids = [];
        
        foreach (array_merge($direct_sponsors, $txn_sponsors) as $sponsor) {
            if (!in_array($sponsor['id'], $seen_ids)) {
                $sponsors[] = $sponsor;
                $seen_ids[] = $sponsor['id'];
            }
        }
        
        return $sponsors;
    }

    /**
     * Get available grades from existing students
     * 
     * @return array List of grades
     */
    public function get_available_grades()
    {
        $this->db->select('school_grade as grade');
        $this->db->from($this->tbl_students);
        $this->db->where('school_grade IS NOT NULL');
        $this->db->where('school_grade !=', '');
        $this->db->group_by('school_grade');
        $this->db->order_by('
            CASE 
                WHEN school_grade REGEXP "^[0-9]+$" THEN CAST(school_grade AS UNSIGNED)
                WHEN school_grade = "O/L" THEN 11
                WHEN school_grade = "A/L1" THEN 12  
                WHEN school_grade = "A/L2" THEN 13
                WHEN school_grade = "A/L Final" THEN 14
                ELSE 999
            END
        ');

        return $this->db->get()->result_array();
    }

    /**
     * Get available cities from existing students
     * 
     * @return array List of cities
     */
    public function get_available_cities()
    {
        $this->db->select('city');
        $this->db->from($this->tbl_students);
        $this->db->where('city IS NOT NULL');
        $this->db->where('city !=', '');
        $this->db->group_by('city');
        $this->db->order_by('city', 'ASC');

        return $this->db->get()->result_array();
    }



    // export helper 
 /**
 * Export sponsored students to Excel
 */
public function export_sponsored_students()
{
    // Check permissions
    if (!has_permission('student_sponsor_portal', '', 'view')) {
        access_denied('student_sponsor_portal');
    }

    // Get sponsor ID from session
    $sponsor_id = $this->get_current_sponsor_id();
    
    if (!$sponsor_id) {
        set_alert('danger', 'Sponsor information not found.');
        redirect(admin_url('student_sponsor_portal/sponsor_profile'));
        return;
    }

    // Get filter parameters from GET request
    $filter = $this->input->get('type') ?? 'all';
    $search = $this->input->get('search') ?? '';

    // Load the appropriate model
    $this->load->model('sponsor_model'); // Adjust to your actual model name

    // Call export method
    $result = $this->sponsor_model->export_sponsored_students($sponsor_id, $filter, $search);

    if (!$result['success']) {
        set_alert('danger', $result['message']);
        redirect(admin_url('student_sponsor_portal/my_sponsored_students'));
        return;
    }

    // Output the file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($result['spreadsheet']);
    $writer->save('php://output');
    exit;
}

/**
 * Get or create bank ID
 * 
 * @param string|int $name_or_id Bank name or ID
 * @return int|null Bank ID
 */
private function get_or_create_bank_id($name_or_id)
{
    if (!$name_or_id) return null;
    if (is_numeric($name_or_id)) return (int)$name_or_id;

    $this->db->where('name', $name_or_id);
    $row = $this->db->get($this->tbl_bank)->row();
    if ($row) return (int)$row->id;

    $this->db->insert($this->tbl_bank, ['name' => $name_or_id]);
    return (int)$this->db->insert_id();
}

/**
 * Helper method to get current sponsor ID from session
 */
private function get_current_sponsor_id()
{
    // Adjust this based on how you store sponsor information in session
    // Example implementations:
    
    // Option 1: If you store it directly in session
    return $this->session->userdata('sponsor_id');
    
    // Option 2: If you get it from staff relationship
    // $staff_id = get_staff_user_id();
    // return $this->sponsor_model->get_sponsor_by_staff_id($staff_id);
    
    // Option 3: If stored in a custom session variable
    // return $this->session->userdata('current_sponsor_id');
}}