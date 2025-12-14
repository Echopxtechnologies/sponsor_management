<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Sponsor Model - Complete Implementation for Perfex CRM
 * 
 * Handles all sponsor-related database operations including:
 * - CRUD operations for sponsors
 * - Sponsored student relationships via transactions
 * - DataTables integration with server-side processing
 * - Country and bank foreign key relationships
 * - Financial transaction summaries
 * - Statistics and reporting
 * 
 * @version 1.1 - Fixed country schema foreign key issue
 */
class Sponsor_model extends App_Model
{
    /** @var string Main sponsors table */
    private $table;

    /** @var string School students table */
    private $tbl_school_students = 'tblschool_students';

    /** @var string University students table */
    private $tbl_university_students = 'tbluniversity_students';

    /** @var string Sponsor transactions table */
    private $tbl_sponsor_transactions = 'tblsponsor_transactions';

    /** @var string Bank table */
    private $tbl_bank = 'tblbank';

    /** @var string School name table */
    private $tbl_school_name = 'tblschool_name';

    /** @var string University name table */
    private $tbl_university_name = 'tbluniversity_name';

    /** @var string University program table */
    private $tbl_university_program = 'tbluniversity_program';

    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'sponsor_records';
        $this->ensure_required_tables();
    }

    /* ============================== ENHANCED LIST VIEW METHODS ============================== */

    /**
     * Get all sponsors with sponsored student information for list view
     * @param bool $include_sponsored_students Include detailed sponsored student info
     * @return array Complete sponsors list with student information
     */
    public function get_all($include_sponsored_students = true): array
    {
        $sp = $this->table;
        $c = $this->country_schema();

        // Build base query with country join
        $select_fields = ['sp.*'];
        
        if ($c['table']) {
            $select_fields[] = "c.{$c['name']} AS country_name";
            $this->db->from("$sp sp");
            $this->db->join("{$c['table']} c", "c.{$c['id']} = sp.country_id", 'left');
        } else {
            $select_fields[] = "NULL AS country_name";
            $this->db->from("$sp sp");
        }

        // Bank join
        if ($this->db->table_exists($this->tbl_bank)) {
            $select_fields[] = 'b.name AS bank_name';
            $this->db->join($this->tbl_bank . ' b', 'b.id = sp.bank_id', 'left');
        }

        // Add sponsored student counts and financial totals via subqueries
        if ($include_sponsored_students && $this->db->table_exists($this->tbl_sponsor_transactions)) {
            $select_fields[] = '
                (SELECT COUNT(DISTINCT school_student_id) 
                 FROM ' . $this->tbl_sponsor_transactions . ' st1 
                 WHERE st1.sponsor_id = sp.id AND st1.school_student_id IS NOT NULL) AS school_students_count';
            
            $select_fields[] = '
                (SELECT COUNT(DISTINCT university_student_id) 
                 FROM ' . $this->tbl_sponsor_transactions . ' st2 
                 WHERE st2.sponsor_id = sp.id AND st2.university_student_id IS NOT NULL) AS university_students_count';
            
            $select_fields[] = '
                (SELECT COALESCE(SUM(total_amount), 0) 
                 FROM ' . $this->tbl_sponsor_transactions . ' st3 
                 WHERE st3.sponsor_id = sp.id) AS total_commitment';
            
            $select_fields[] = '
                (SELECT COALESCE(SUM(p.amount), 0) 
                FROM ' . db_prefix() . 'sponsor_payments p
                INNER JOIN ' . $this->tbl_sponsor_transactions . ' st4 ON st4.id = p.transaction_id
                WHERE st4.sponsor_id = sp.id) AS total_paid';
            
            $select_fields[] = '
                (SELECT COUNT(*) 
                 FROM ' . $this->tbl_sponsor_transactions . ' st5 
                 WHERE st5.sponsor_id = sp.id) AS total_transactions';
        }

        $this->db->select(implode(', ', $select_fields), false);
        $this->db->order_by('sp.name', 'ASC');
        $results = $this->db->get()->result_array();
        
        $out = [];
        foreach ($results as $r) {
            $sponsor = $this->map_database_to_form_fields($r);
            
            if ($include_sponsored_students) {
                // Add computed sponsored student information
                $sponsor = $this->enhance_sponsor_with_student_info($sponsor, $r);
                
                // Add student names for list view - KEY ENHANCEMENT
                $sponsor['sponsored_student_names'] = $this->get_sponsored_student_names_for_list($sponsor['id']);
            }
            
            $out[] = $sponsor;
        }
        
        return $out;
    }

    /**
     * Get sponsor by ID with complete details including student names
     * @param int $sponsor_id Sponsor ID
     * @return array|null Complete sponsor information
     */
    public function get_by_id(int $sponsor_id): ?array
    {
        if ($sponsor_id <= 0) {
            return null;
        }

        $sp = $this->table;
        $c = $this->country_schema();

        // Build select fields
        $select_fields = ['sp.*'];
        
        if ($c['table']) {
            $select_fields[] = "c.{$c['name']} AS country_name";
            $this->db->from("$sp sp");
            $this->db->join("{$c['table']} c", "c.{$c['id']} = sp.country_id", 'left');
        } else {
            $select_fields[] = "NULL AS country_name";
            $this->db->from("$sp sp");
        }

        // Bank join
        if ($this->db->table_exists($this->tbl_bank)) {
            $select_fields[] = 'b.name AS bank_name';
            $this->db->join($this->tbl_bank . ' b', 'b.id = sp.bank_id', 'left');
        }

        // Add sponsored student counts via subqueries
        if ($this->db->table_exists($this->tbl_sponsor_transactions)) {
            $select_fields[] = '
                (SELECT COUNT(DISTINCT school_student_id) 
                 FROM ' . $this->tbl_sponsor_transactions . ' st1 
                 WHERE st1.sponsor_id = sp.id AND st1.school_student_id IS NOT NULL) AS school_students_count';
            
            $select_fields[] = '
                (SELECT COUNT(DISTINCT university_student_id) 
                 FROM ' . $this->tbl_sponsor_transactions . ' st2 
                 WHERE st2.sponsor_id = sp.id AND st2.university_student_id IS NOT NULL) AS university_students_count';
            
            $select_fields[] = '
                (SELECT COALESCE(SUM(total_amount), 0) 
                 FROM ' . $this->tbl_sponsor_transactions . ' st3 
                 WHERE st3.sponsor_id = sp.id) AS total_commitment';
            
            $select_fields[] = '
                (SELECT COALESCE(SUM(p.amount), 0) 
                FROM ' . db_prefix() . 'sponsor_payments p
                INNER JOIN ' . $this->tbl_sponsor_transactions . ' st4 ON st4.id = p.transaction_id
                WHERE st4.sponsor_id = sp.id) AS total_paid';
            
            $select_fields[] = '
                (SELECT COUNT(*) 
                 FROM ' . $this->tbl_sponsor_transactions . ' st5 
                 WHERE st5.sponsor_id = sp.id) AS total_transactions';
        }

        $this->db->select(implode(', ', $select_fields), false);
        $this->db->where('sp.id', $sponsor_id);
        $row = $this->db->get()->row_array();

        if (!$row) {
            return null;
        }
        
        $sponsor = $this->map_database_to_form_fields($row);
        
        // Add the same student enhancement as get_all() method
        $sponsor = $this->enhance_sponsor_with_student_info($sponsor, $row);
        
        // Add student names for form view
        $sponsor['sponsored_student_names'] = $this->get_sponsored_student_names_for_list($sponsor['id']);
        
        // Add sponsored student information for individual sponsor view
        $sponsored_students = $this->get_sponsored_students($sponsor_id);
        $sponsor['sponsored_students_detail'] = $sponsored_students;
        $sponsor['sponsor_statistics'] = $this->get_sponsor_statistics($sponsor_id);
        
        return $sponsor;
    }

    /**
     * Get sponsored student names optimized for list view display
     * @param int $sponsor_id Sponsor ID
     * @param int $limit Maximum number of names to return (default 4)
     * @return array Student names and counts for display
     */
    public function get_sponsored_student_names_for_list($sponsor_id, $limit = 4)
    {
        if ($sponsor_id <= 0) {
            return [
                'school_students' => [],
                'university_students' => [],
                'school_names_display' => '',
                'university_names_display' => '',
                'school_total_count' => 0,
                'university_total_count' => 0,
                'school_has_more' => false,
                'university_has_more' => false
            ];
        }

        $result = [
            'school_students' => [],
            'university_students' => [],
            'school_names_display' => '',
            'university_names_display' => '',
            'school_total_count' => 0,
            'university_total_count' => 0,
            'school_has_more' => false,
            'university_has_more' => false
        ];

        // Get school students names
        if ($this->db->table_exists($this->tbl_sponsor_transactions) && 
            $this->db->table_exists($this->tbl_school_students)) {
            
            // First get total count
            $this->db->select('COUNT(DISTINCT ss.id) as total_count');
            $this->db->from($this->tbl_sponsor_transactions . ' st');
            $this->db->join($this->tbl_school_students . ' ss', 'ss.id = st.school_student_id', 'inner');
            $this->db->where('st.sponsor_id', $sponsor_id);
            $this->db->where('st.school_student_id IS NOT NULL');
            $count_result = $this->db->get()->row_array();
            $result['school_total_count'] = (int)($count_result['total_count'] ?? 0);
            
            if ($result['school_total_count'] > 0) {
                // Get limited names for display
                $this->db->select('ss.name, ss.school_internal_id, ss.school_grade, sn.name as school_name');
                $this->db->from($this->tbl_sponsor_transactions . ' st');
                $this->db->join($this->tbl_school_students . ' ss', 'ss.id = st.school_student_id', 'inner');
                $this->db->join($this->tbl_school_name . ' sn', 'sn.id = ss.school_name_id', 'left');
                $this->db->where('st.sponsor_id', $sponsor_id);
                $this->db->where('st.school_student_id IS NOT NULL');
                $this->db->group_by('ss.id');
                $this->db->order_by('ss.name', 'ASC');
                $this->db->limit($limit);
                
                $school_students = $this->db->get()->result_array();
                $result['school_students'] = $school_students;
                $result['school_has_more'] = $result['school_total_count'] > $limit;
                
                // Build display string
                $names = array_column($school_students, 'name');
                $result['school_names_display'] = $this->build_names_display_string($names, $result['school_total_count'], $limit);
            }
        }

        // Get university students names
        if ($this->db->table_exists($this->tbl_sponsor_transactions) && 
            $this->db->table_exists($this->tbl_university_students)) {
            
            // First get total count
            $this->db->select('COUNT(DISTINCT us.id) as total_count');
            $this->db->from($this->tbl_sponsor_transactions . ' st');
            $this->db->join($this->tbl_university_students . ' us', 'us.id = st.university_student_id', 'inner');
            $this->db->where('st.sponsor_id', $sponsor_id);
            $this->db->where('st.university_student_id IS NOT NULL');
            $count_result = $this->db->get()->row_array();
            $result['university_total_count'] = (int)($count_result['total_count'] ?? 0);
            
            if ($result['university_total_count'] > 0) {
                // Get limited names for display
                $this->db->select('us.name, us.university_internal_id, us.university_year_of_study, un.name as university_name, up.name as program_name');
                $this->db->from($this->tbl_sponsor_transactions . ' st');
                $this->db->join($this->tbl_university_students . ' us', 'us.id = st.university_student_id', 'inner');
                $this->db->join($this->tbl_university_name . ' un', 'un.id = us.university_name_id', 'left');
                $this->db->join($this->tbl_university_program . ' up', 'up.id = us.university_program_id', 'left');
                $this->db->where('st.sponsor_id', $sponsor_id);
                $this->db->where('st.university_student_id IS NOT NULL');
                $this->db->group_by('us.id');
                $this->db->order_by('us.name', 'ASC');
                $this->db->limit($limit);
                
                $university_students = $this->db->get()->result_array();
                $result['university_students'] = $university_students;
                $result['university_has_more'] = $result['university_total_count'] > $limit;
                
                // Build display string
                $names = array_column($university_students, 'name');
                $result['university_names_display'] = $this->build_names_display_string($names, $result['university_total_count'], $limit);
            }
        }

        return $result;
    }

    /**
     * Build a display string for student names with "and X more" logic
     * @param array $names Array of student names
     * @param int $total_count Total number of students
     * @param int $limit Display limit
     * @return string Formatted display string
     */
    private function build_names_display_string($names, $total_count, $limit)
    {
        if (empty($names)) {
            return '';
        }

        $display_names = array_slice($names, 0, $limit);
        $remaining = $total_count - count($display_names);

        if ($remaining > 0) {
            return implode(', ', $display_names) . ' and ' . $remaining . ' more';
        } else {
            return implode(', ', $display_names);
        }
    }

    /* ============================== DATATABLES INTEGRATION ============================== */

    /**
     * Get sponsors with enhanced information for DataTables
     * @param array $request_data DataTables request parameters
     * @return array DataTables response format
     */
    public function get_sponsors_datatables($request_data = [])
    {
        try {
            // Extract DataTables parameters
            $start = (int)($request_data['start'] ?? 0);
            $length = (int)($request_data['length'] ?? 25);
            $search_value = $request_data['search']['value'] ?? '';
            $order = $request_data['order'] ?? [];

            // Build base query
            $this->build_sponsors_list_query();

            // Apply search
            if (!empty($search_value)) {
                $this->apply_sponsors_search($search_value);
            }

            // Get total filtered count before applying limit
            $filtered_query = clone $this->db;
            $filtered_count = $filtered_query->count_all_results('', false);

            // Apply ordering
            $this->apply_sponsors_ordering($order);

            // Apply pagination
            if ($length != -1) {
                $this->db->limit($length, $start);
            }

            $sponsors = $this->db->get()->result_array();

            // Format data for DataTables
            $formatted_data = [];
            foreach ($sponsors as $sponsor) {
                $formatted_data[] = $this->format_sponsor_for_datatables($sponsor);
            }

            return [
                'draw' => (int)($request_data['draw'] ?? 0),
                'recordsTotal' => $this->count_all(),
                'recordsFiltered' => $filtered_count,
                'data' => $formatted_data
            ];

        } catch (Exception $e) {
            log_message('error', 'Sponsor_model::get_sponsors_datatables - ' . $e->getMessage());
            
            return [
                'draw' => (int)($request_data['draw'] ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Build the base query for sponsors list with all necessary joins
     */
    private function build_sponsors_list_query()
    {
        $sp = $this->table;
        $c = $this->country_schema();

        $select_fields = ['sp.*'];

        // Country join
        if ($c['table']) {
            $select_fields[] = "c.{$c['name']} AS country_name";
            $this->db->from("$sp sp");
            $this->db->join("{$c['table']} c", "c.{$c['id']} = sp.country_id", 'left');
        } else {
            $select_fields[] = "NULL AS country_name";
            $this->db->from("$sp sp");
        }

        // Bank join
        if ($this->db->table_exists($this->tbl_bank)) {
            $select_fields[] = 'b.name AS bank_name';
            $this->db->join($this->tbl_bank . ' b', 'b.id = sp.bank_id', 'left');
        }

        // Sponsored student counts and transaction totals
        if ($this->db->table_exists($this->tbl_sponsor_transactions)) {
            $select_fields[] = '
                (SELECT COUNT(DISTINCT school_student_id) 
                 FROM ' . $this->tbl_sponsor_transactions . ' st1 
                 WHERE st1.sponsor_id = sp.id AND st1.school_student_id IS NOT NULL) AS school_students_count';
            
            $select_fields[] = '
                (SELECT COUNT(DISTINCT university_student_id) 
                 FROM ' . $this->tbl_sponsor_transactions . ' st2 
                 WHERE st2.sponsor_id = sp.id AND st2.university_student_id IS NOT NULL) AS university_students_count';
            
            $select_fields[] = '
                (SELECT COALESCE(SUM(total_amount), 0) 
                 FROM ' . $this->tbl_sponsor_transactions . ' st3 
                 WHERE st3.sponsor_id = sp.id) AS total_commitment';
            
            $select_fields[] = '
                (SELECT COALESCE(SUM(p.amount), 0) 
                FROM ' . db_prefix() . 'sponsor_payments p
                INNER JOIN ' . $this->tbl_sponsor_transactions . ' st4 ON st4.id = p.transaction_id
                WHERE st4.sponsor_id = sp.id) AS total_paid';
            
            $select_fields[] = '
                (SELECT COUNT(*) 
                 FROM ' . $this->tbl_sponsor_transactions . ' st5 
                 WHERE st5.sponsor_id = sp.id) AS total_transactions';
        }

        $this->db->select(implode(', ', $select_fields), false);
    }

    /**
     * Apply search filters for sponsors
     */
    private function apply_sponsors_search($search_value)
    {
        $search = $this->db->escape_like_str($search_value);
        
        $this->db->group_start();
        $this->db->like('sp.name', $search);
        $this->db->or_like('sp.email', $search);
        $this->db->or_like('sp.contact_no', $search);
        $this->db->or_like('sp.sponsor_type', $search);
        $this->db->or_like('sp.city', $search);
        $this->db->group_end();
    }

    /**
     * Apply ordering for sponsors list
     */
    private function apply_sponsors_ordering($order)
    {
        if (!empty($order)) {
            foreach ($order as $order_item) {
                $column_index = (int)$order_item['column'];
                $direction = strtoupper($order_item['dir']) === 'DESC' ? 'DESC' : 'ASC';
                
                // Map column indices to database columns
                $columns = [
                    0 => 'sp.id',
                    1 => 'sp.name',
                    2 => 'sp.sponsor_type',
                    3 => 'sp.email',
                    4 => 'sp.contact_no',
                    5 => null, // Sponsored students names (computed)
                    6 => null, // Financial summary
                    7 => null  // Actions column
                ];
                
                if (isset($columns[$column_index]) && $columns[$column_index] !== null) {
                    $this->db->order_by($columns[$column_index], $direction);
                }
            }
        } else {
            $this->db->order_by('sp.name', 'ASC');
        }
    }

    /**
     * Format sponsor data for DataTables display
     */
    private function format_sponsor_for_datatables($sponsor)
    {
        $sponsor_id = (int)($sponsor['id'] ?? 0);
        $name = (string)($sponsor['name'] ?? '');
        $email = (string)($sponsor['email'] ?? '');
        $phone = (string)($sponsor['contact_no'] ?? '');
        $type = (string)($sponsor['sponsor_type'] ?? '');
        
        // Calculate sponsored student information
        $school_count = (int)($sponsor['school_students_count'] ?? 0);
        $university_count = (int)($sponsor['university_students_count'] ?? 0);
        $total_students = $school_count + $university_count;
        
        // Financial information
        $total_commitment = (float)($sponsor['total_commitment'] ?? 0);
        $total_paid = (float)($sponsor['total_paid'] ?? 0);
        $balance = $total_commitment - $total_paid;
        
        // Generate initials for avatar
        $initials = $this->generate_initials($name);
        
        return [
            'DT_RowId' => 'sponsor-row-' . $sponsor_id,
            'DT_RowData' => [
                'sponsor-id' => $sponsor_id,
                'type' => mb_strtolower($type),
                'student-count' => $total_students
            ],
            'id' => $sponsor_id,
            'name' => [
                'display' => $name,
                'initials' => $initials,
                'edit_url' => admin_url('student_sponsor_portal/sponsor_form/' . $sponsor_id)
            ],
            'type' => $type,
            'contact' => [
                'email' => $email,
                'phone' => $phone
            ],
            'sponsored_students' => [
                'total' => $total_students,
                'school' => $school_count,
                'university' => $university_count,
                'display_text' => $this->format_student_count_display($school_count, $university_count)
            ],
            'financial' => [
                'total_commitment' => $total_commitment,
                'total_paid' => $total_paid,
                'balance' => $balance,
                'formatted_commitment' => number_format($total_commitment, 2),
                'formatted_paid' => number_format($total_paid, 2),
                'formatted_balance' => number_format($balance, 2)
            ],
            'status' => $this->determine_sponsor_status($sponsor)
        ];
    }

    /**
     * Format student count display text
     */
    private function format_student_count_display($school_count, $university_count)
    {
        $total = $school_count + $university_count;
        
        if ($total === 0) {
            return 'No students';
        }
        
        $parts = [];
        if ($school_count > 0) {
            $parts[] = $school_count . ' School';
        }
        if ($university_count > 0) {
            $parts[] = $university_count . ' University';
        }
        
        return implode(' + ', $parts) . ' (' . $total . ' total)';
    }

    /**
     * Determine sponsor status based on activity and student count
     */
    private function determine_sponsor_status($sponsor)
    {
        $is_active = !empty($sponsor['active']) && $sponsor['active'] == 1;
        $has_students = ((int)($sponsor['school_students_count'] ?? 0) + (int)($sponsor['university_students_count'] ?? 0)) > 0;
        
        if (!$is_active) {
            return [
                'status' => 'inactive',
                'class' => 'danger',
                'label' => 'Inactive'
            ];
        }
        
        if ($has_students) {
            return [
                'status' => 'active_sponsoring',
                'class' => 'success',
                'label' => 'Active & Sponsoring'
            ];
        }
        
        return [
            'status' => 'active_no_students',
            'class' => 'warning',
            'label' => 'Active (No Students)'
        ];
    }

    /**
     * Generate initials from sponsor name
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
     * Enhance sponsor data with student information
     */
    private function enhance_sponsor_with_student_info($sponsor, $raw_data)
    {
        // Add sponsored student counts
        $sponsor['school_students_count'] = (int)($raw_data['school_students_count'] ?? 0);
        $sponsor['university_students_count'] = (int)($raw_data['university_students_count'] ?? 0);
        $sponsor['total_students_count'] = $sponsor['school_students_count'] + $sponsor['university_students_count'];
        
        // Add financial information
        $sponsor['total_commitment'] = (float)($raw_data['total_commitment'] ?? 0);
        $sponsor['total_paid'] = (float)($raw_data['total_paid'] ?? 0);
        $sponsor['total_balance'] = $sponsor['total_commitment'] - $sponsor['total_paid'];
        $sponsor['total_transactions'] = (int)($raw_data['total_transactions'] ?? 0);
        
        // Add sponsorship status
        $sponsor['sponsorship_status'] = $this->get_sponsorship_status($sponsor);
        
        // Add formatted display values
        $sponsor['students_display'] = $this->format_student_count_display(
            $sponsor['school_students_count'],
            $sponsor['university_students_count']
        );
        
        return $sponsor;
    }

    /**
     * Get sponsorship status summary
     */
    private function get_sponsorship_status($sponsor)
    {
        $total_students = $sponsor['total_students_count'] ?? 0;
        $total_commitment = $sponsor['total_commitment'] ?? 0;
        $total_paid = $sponsor['total_paid'] ?? 0;
        
        return [
            'has_students' => $total_students > 0,
            'has_commitments' => $total_commitment > 0,
            'has_payments' => $total_paid > 0,
            'payment_ratio' => $total_commitment > 0 ? ($total_paid / $total_commitment) : 0
        ];
    }

    /* ============================== CORE CRUD METHODS ============================== */


    /**
 * Optional: Create or update related client record for sponsor
 * Only use if you need client integration
 * @param int $sponsor_id Sponsor ID
 * @param array $sponsor_data Sponsor data
 * @return int|bool Client ID or false on failure
 */
public function sync_sponsor_to_client($sponsor_id, $sponsor_data)
{
    if ($sponsor_id <= 0) {
        return false;
    }

    $this->load->model('clients_model');

    // Check if client already exists with sponsor reference
    $existing_client = $this->db->where('sponsor_id', $sponsor_id)
                                 ->get(db_prefix() . 'clients')
                                 ->row();

    $client_data = [
        'company'       => $sponsor_data['name'] ?? '',  // CORRECT FIELD
        'phonenumber'   => $sponsor_data['contact_no'] ?? '',  // CORRECT FIELD
        'country'       => $sponsor_data['country_id'] ?? 0,
        'city'          => $sponsor_data['city'] ?? '',
        'zip'           => $sponsor_data['zip'] ?? '',
        'address'       => $sponsor_data['address'] ?? '',
        'active'        => $sponsor_data['active'] ?? 1,
    ];

    // Add contact person with email
    $contact_data = [
        'firstname'     => $sponsor_data['name'] ?? '',
        'email'         => $sponsor_data['email'] ?? '',
        'is_primary'    => 1
    ];

    try {
        if ($existing_client) {
            // Update existing client
            $this->clients_model->update($client_data, $existing_client->userid);
            
            // Update contact
            $this->db->where('userid', $existing_client->userid)
                     ->where('is_primary', 1)
                     ->update(db_prefix() . 'contacts', $contact_data);
            
            return $existing_client->userid;
        } else {
            // Create new client
            $client_id = $this->clients_model->add($client_data, true);
            
            if ($client_id) {
                // Store sponsor reference in client custom field
                $this->db->where('userid', $client_id)
                         ->update(db_prefix() . 'clients', ['sponsor_id' => $sponsor_id]);
                
                // Update the primary contact
                $this->db->where('userid', $client_id)
                         ->where('is_primary', 1)
                         ->update(db_prefix() . 'contacts', ['email' => $contact_data['email']]);
            }
            
            return $client_id;
        }
    } catch (Exception $e) {
        log_message('error', 'sync_sponsor_to_client failed: ' . $e->getMessage());
        return false;
    }
}

    /**
 * Add a new sponsor - FIXED VERSION
 * @param array $data Sponsor data
 * @return int|false Sponsor ID on success, false on failure
 */
public function add(array $data)
{
    // If a staff login email was provided, treat it as the authoritative contact email
    $staffEmail = isset($data['staff_email']) ? trim((string)$data['staff_email']) : '';
    if ($staffEmail !== '' && filter_var($staffEmail, FILTER_VALIDATE_EMAIL)) {
        $data['email'] = $staffEmail;
    }

    $row = $this->sanitize_payload($data, /*is_update=*/false);

    // Country FK normalization
    $row['country_id'] = $this->validate_country_id($row['country_id'] ?? null);

    // Enforce NULL for unique columns when empty
    if (isset($row['sponsor_bank_account_no']) && $row['sponsor_bank_account_no'] === '') {
        $row['sponsor_bank_account_no'] = null;
    }

    // Minimal required field
    if (empty($row['name'])) {
        log_message('error', 'Sponsor_model::add - Name is required');
        return false;
    }

    // Ensure email is either a valid email or NULL
    if (isset($row['email'])) {
        $row['email'] = trim((string)$row['email']);
        if ($row['email'] === '' || !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $row['email'] = null;
        }
    }

    $this->db->trans_start();

    $ok = $this->db->insert($this->table, $row);
    if (!$ok) {
        $error = $this->db->error();
        log_message('error', 'Sponsor_model::add DB error: ' . ($error['message'] ?? 'unknown') . ' | Code: ' . ($error['code'] ?? 'N/A'));
        $this->db->trans_complete();
        return false;
    }

    $sponsor_id = (int) $this->db->insert_id();
    
    log_message('info', 'Sponsor_model::add - Successfully created sponsor ID: ' . $sponsor_id);

    // REMOVED: Client sync - sponsors are independent entities
    // If you need to create a related client, do it in the controller with proper mapping

    $this->db->trans_complete();
    if ($this->db->trans_status() === false) {
        return false;
    }

    return $sponsor_id;
}

/**
 * Update sponsor - FIXED: Don't override email unless explicitly requested
 * @param array $data Sponsor data
 * @param int $sponsor_id Sponsor ID
 * @return bool Success status
 */
public function update(array $data, int $sponsor_id): bool
{
    log_message('debug', '=== MODEL UPDATE START ===');
    log_message('debug', 'Sponsor ID: ' . $sponsor_id);
    log_message('debug', 'Input data received: ' . print_r($data, true));
    
    if ($sponsor_id <= 0) {
        log_message('error', 'Invalid sponsor_id: ' . $sponsor_id);
        return false;
    }

    // FIXED: Only use staff_email if main email is empty or if explicitly updating staff account
    $staffEmail = isset($data['staff_email']) ? trim((string)$data['staff_email']) : '';
    $mainEmail = isset($data['email']) ? trim((string)$data['email']) : '';
    
    // Only override if main email is empty AND staff email is provided
    if ($mainEmail === '' && $staffEmail !== '' && filter_var($staffEmail, FILTER_VALIDATE_EMAIL)) {
        $data['email'] = $staffEmail;
        log_message('debug', 'Using staff email as main email (main email was empty): ' . $staffEmail);
    } else {
        log_message('debug', 'Using main email field: ' . $mainEmail);
    }

    // Sanitize the payload
    log_message('debug', 'Before sanitize_payload: email = ' . ($data['email'] ?? 'NOT SET'));
    $row = $this->sanitize_payload($data, true);
    log_message('debug', 'After sanitize_payload: ' . print_r($row, true));

    // Country ID normalization
    $row['country_id'] = $this->validate_country_id($row['country_id'] ?? null);

    // Enforce NULL for unique columns when empty
    if (isset($row['sponsor_bank_account_no']) && $row['sponsor_bank_account_no'] === '') {
        $row['sponsor_bank_account_no'] = null;
    }

    // Minimal required field validation
    if (empty($row['name'])) {
        log_message('error', 'Sponsor_model::update - Name is required');
        return false;
    }

    // Validate and normalize email properly
    if (isset($row['email'])) {
        log_message('debug', 'Email field exists in row: ' . $row['email']);
        $row['email'] = trim((string)$row['email']);
        
        if ($row['email'] !== '' && !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            log_message('warning', 'Invalid email format: ' . $row['email']);
            $row['email'] = null;
        }
        log_message('debug', 'Final email value: ' . ($row['email'] ?? 'NULL'));
    } else {
        log_message('warning', 'Email field NOT in sanitized row!');
    }

    log_message('debug', '=== FINAL ROW TO UPDATE ===');
    log_message('debug', print_r($row, true));
    log_message('debug', '=== END FINAL ROW ===');

    $this->db->trans_start();

    // Perform the update
    $this->db->where('id', $sponsor_id);
    $this->db->db_debug = false;
    
    $ok = $this->db->update($this->table, $row);
    
    // Log the actual query executed
    $last_query = $this->db->last_query();
    log_message('debug', '=== SQL QUERY EXECUTED ===');
    log_message('debug', $last_query);
    log_message('debug', '=== END SQL QUERY ===');
    
    // Check affected rows
    $affected_rows = $this->db->affected_rows();
    log_message('debug', 'Affected rows: ' . $affected_rows);

    if (!$ok) {
        $error = $this->db->error();
        log_message('error', 'DB UPDATE FAILED!');
        log_message('error', 'Error message: ' . ($error['message'] ?? 'unknown'));
        log_message('error', 'Error code: ' . ($error['code'] ?? 'N/A'));
        $this->db->trans_complete();
        return false;
    }

    log_message('info', 'Sponsor_model::update - Successfully updated sponsor ID: ' . $sponsor_id);

    $this->db->trans_complete();
    
    if ($this->db->trans_status() === false) {
        log_message('error', 'Transaction failed for sponsor ID: ' . $sponsor_id);
        return false;
    }

    log_message('debug', '=== MODEL UPDATE END - SUCCESS ===');
    return true;
}




    /**
     * Delete sponsor and handle related data
     * @param int $sponsor_id Sponsor ID
     * @return bool Success status
     */
    public function delete_sponsor(int $sponsor_id): bool
    {
        if ($sponsor_id <= 0) {
            return false;
        }

        $this->db->trans_start();

        try {
            // Clear sponsor_id from students (they become unsponsored)
            if ($this->db->table_exists($this->tbl_school_students)) {
                $this->db->where('sponsor_id', $sponsor_id)
                         ->update($this->tbl_school_students, ['sponsor_id' => null]);
            }
            
            if ($this->db->table_exists($this->tbl_university_students)) {
                $this->db->where('sponsor_id', $sponsor_id)
                         ->update($this->tbl_university_students, ['sponsor_id' => null]);
            }

            // Delete related transactions if table exists
            if ($this->db->table_exists($this->tbl_sponsor_transactions)) {
                $this->db->where('sponsor_id', $sponsor_id)
                         ->delete($this->tbl_sponsor_transactions);
            }

            // Delete sponsor
            $this->db->where('id', $sponsor_id);
            $deleted = $this->db->delete($this->table);

            $this->db->trans_complete();
            return $this->db->trans_status() && $deleted;
            
        } catch (Exception $e) {
            log_message('error', 'Error deleting sponsor: ' . $e->getMessage());
            $this->db->trans_rollback();
            return false;
        }
    }

    /**
     * Count all sponsors
     * @return int Total sponsor count
     */
    public function count_all(): int
    {
        return (int) $this->db->count_all_results($this->table);
    }

    /* ============================== ALIAS METHODS FOR COMPATIBILITY ============================== */

    /**
     * Alias for update method
     */
    public function update_sponsor(array $data, int $sponsor_id): bool
    {
        return $this->update($data, $sponsor_id);
    }

    /**
     * Alias for delete_sponsor method
     */
    public function delete(int $sponsor_id): bool
    {
        return $this->delete_sponsor($sponsor_id);
    }

    /**
     * Alias for update method
     */
    public function update_student($data, $id)
    {
        return $this->update($data, $id);
    }

    /* ============================== DROPDOWN DATA METHODS ============================== */
/**
 * Get all countries for dropdown with phone codes
 * @return array Countries list with calling codes
 */
public function get_countries()
{
    if (!$this->db->table_exists(db_prefix() . 'countries')) {
        return [];
    }

    $this->db->select('country_id as id, short_name as name, calling_code, calling_code as phone_code');
    $this->db->from(db_prefix() . 'countries');
    $this->db->where('calling_code IS NOT NULL');
    $this->db->where('calling_code !=', '');
    $this->db->order_by('short_name', 'ASC');
    
    $result = $this->db->get()->result_array();
    
    // Debug logging
    if (empty($result)) {
        log_message('error', 'Sponsor_model::get_countries - No countries returned. Query: ' . $this->db->last_query());
    } else {
        log_message('debug', 'Sponsor_model::get_countries - Retrieved ' . count($result) . ' countries with calling codes');
    }
    
    return $result;
}

    /**
     * Get all banks for dropdown
     * @return array Banks list
     */
    public function get_banks(): array
    {
        if (!$this->db->table_exists($this->tbl_bank)) {
            return [];
        }
        
        return $this->db->select('id, name')
                       ->order_by('name', 'ASC')
                       ->get($this->tbl_bank)
                       ->result_array();
    }

    /**
     * Get all universities for dropdown
     * @return array Universities list
     */
    public function get_universities(): array
    {
        if (!$this->db->table_exists($this->tbl_university_name)) {
            return [];
        }
        
        return $this->db->select('id, name')
                       ->order_by('name', 'ASC')
                       ->get($this->tbl_university_name)
                       ->result_array();
    }

    /**
     * Get all programs for dropdown
     * @return array Programs list
     */
    public function get_programs(): array
    {
        if (!$this->db->table_exists($this->tbl_university_program)) {
            return [];
        }
        
        return $this->db->select('id, name')
                       ->order_by('name', 'ASC')
                       ->get($this->tbl_university_program)
                       ->result_array();
    }

    /**
     * Get all sponsors for dropdown
     * @return array Sponsors list
     */
    public function get_sponsors(): array
    {
        return $this->db->select('id, name')
                       ->order_by('name', 'ASC')
                       ->get($this->table)
                       ->result_array();
    }

    /* ============================== SPONSORED STUDENTS METHODS ============================== */

    /**
     * Get all students sponsored by a specific sponsor through transactions
     * @param int $sponsor_id Sponsor ID
     * @return array Sponsored students by type
     */
    public function get_sponsored_students($sponsor_id)
    {
        if ($sponsor_id <= 0) {
            return ['school_students' => [], 'university_students' => []];
        }

        $result = ['school_students' => [], 'university_students' => []];

        // Get school students through sponsor_transactions table
        if ($this->db->table_exists($this->tbl_sponsor_transactions) && 
            $this->db->table_exists($this->tbl_school_students)) {
            
            $this->db->select('
                ss.*,
                sn.name as school_name,
                c.short_name as country_name,
                b.name as bank_name,
                st.id as transaction_id,
                st.total_amount,
                st.amount_paid,
                st.next_payment_due,
                st.payment_type
            ');
            $this->db->from($this->tbl_sponsor_transactions . ' st');
            $this->db->join($this->tbl_school_students . ' ss', 'ss.id = st.school_student_id', 'inner');
            $this->db->join($this->tbl_school_name . ' sn', 'sn.id = ss.school_name_id', 'left');
            $this->db->join(db_prefix() . 'countries c', 'c.country_id = ss.country_id', 'left');
            $this->db->join($this->tbl_bank . ' b', 'b.id = ss.bank_id', 'left');
            $this->db->where('st.sponsor_id', $sponsor_id);
            $this->db->where('st.school_student_id IS NOT NULL');
            $this->db->group_by('ss.id'); // Group by student ID to avoid duplicates
            $this->db->order_by('ss.name', 'ASC');
            
            $result['school_students'] = $this->db->get()->result_array();
        }

        // Get university students through sponsor_transactions table
        if ($this->db->table_exists($this->tbl_sponsor_transactions) && 
            $this->db->table_exists($this->tbl_university_students)) {
            
            $this->db->select('
                us.*,
                un.name as university_name,
                up.name as program_name,
                c.short_name as country_name,
                b.name as bank_name,
                st.id as transaction_id,
                st.total_amount,
                st.amount_paid,
                st.next_payment_due,
                st.payment_type
            ');
            $this->db->from($this->tbl_sponsor_transactions . ' st');
            $this->db->join($this->tbl_university_students . ' us', 'us.id = st.university_student_id', 'inner');
            $this->db->join($this->tbl_university_name . ' un', 'un.id = us.university_name_id', 'left');
            $this->db->join($this->tbl_university_program . ' up', 'up.id = us.university_program_id', 'left');
            $this->db->join(db_prefix() . 'countries c', 'c.country_id = us.country_id', 'left');
            $this->db->join($this->tbl_bank . ' b', 'b.id = us.bank_id', 'left');
            $this->db->where('st.sponsor_id', $sponsor_id);
            $this->db->where('st.university_student_id IS NOT NULL');
            $this->db->group_by('us.id'); // Group by student ID to avoid duplicates
            $this->db->order_by('us.name', 'ASC');
            
            $result['university_students'] = $this->db->get()->result_array();
        }

        return $result;
    }

    /**
     * Get students with transaction summary for a sponsor
     * @param int $sponsor_id Sponsor ID
     * @return array Students with transaction summaries
     */
    public function get_sponsored_students_with_transactions($sponsor_id)
    {
        $students = $this->get_sponsored_students($sponsor_id);
        $result = [];

        // Process school students
        foreach ($students['school_students'] as $student) {
            $student['student_type'] = 'school';
            $student['school_internal_id'] = $student['school_internal_id'] ?? '';
            
            // Calculate transaction summary (aggregate all transactions for this student from this sponsor)
            $student['transaction_summary'] = $this->get_student_transaction_summary($student['id'], 'school', $sponsor_id);
            $result[] = $student;
        }

        // Process university students
        foreach ($students['university_students'] as $student) {
            $student['student_type'] = 'university';
            $student['university_internal_id'] = $student['university_internal_id'] ?? '';
            
            // Calculate transaction summary (aggregate all transactions for this student from this sponsor)
            $student['transaction_summary'] = $this->get_student_transaction_summary($student['id'], 'university', $sponsor_id);
            $result[] = $student;
        }

        return $result;
    }

    /**
     * Get transaction summary for a student from a specific sponsor
     * @param int $student_id Student ID
     * @param string $type Student type ('school' or 'university')
     * @param int $sponsor_id Sponsor ID
     * @return array Transaction summary
     */
    private function get_student_transaction_summary($student_id, $type, $sponsor_id)
    {
        $summary = [
            'total_amount' => 0,
            'amount_paid' => 0,
            'balance_amount' => 0,
            'transaction_count' => 0
        ];

        if (!$this->db->table_exists($this->tbl_sponsor_transactions)) {
            return $summary;
        }

        $field = $type === 'school' ? 'school_student_id' : 'university_student_id';
        
        $this->db->select('
            SUM(total_amount) as total_amount,
            SUM(amount_paid) as amount_paid,
            COUNT(*) as transaction_count
        ');
        $this->db->from($this->tbl_sponsor_transactions);
        $this->db->where($field, $student_id);
        $this->db->where('sponsor_id', $sponsor_id);
        
        $result = $this->db->get()->row_array();
        
        if ($result) {
            $summary['total_amount'] = (float)($result['total_amount'] ?? 0);
            $summary['amount_paid'] = (float)($result['amount_paid'] ?? 0);
            $summary['balance_amount'] = $summary['total_amount'] - $summary['amount_paid'];
            $summary['transaction_count'] = (int)($result['transaction_count'] ?? 0);
        }

        return $summary;
    }

    /**
     * Get available students for sponsor selection
     * @param int $sponsor_id Sponsor ID (0 for all available students)
     * @return array Available students by type
     */
    public function get_available_students($sponsor_id = 0)
    {
        $result = ['school_students' => [], 'university_students' => []];

        // Get unsponsored school students OR students not sponsored by this particular sponsor
        if ($this->db->table_exists($this->tbl_school_students)) {
            $this->db->select('
                ss.id,
                ss.name,
                ss.school_internal_id,
                ss.school_grade,
                ss.city,
                ss.email,
                sn.name as school_name,
                "school" as student_type
            ');
            $this->db->from($this->tbl_school_students . ' ss');
            $this->db->join($this->tbl_school_name . ' sn', 'sn.id = ss.school_name_id', 'left');
            
            if ($sponsor_id > 0) {
                // Exclude students already sponsored by this sponsor
                $this->db->where("ss.id NOT IN (
                    SELECT DISTINCT school_student_id 
                    FROM " . $this->tbl_sponsor_transactions . " 
                    WHERE sponsor_id = {$sponsor_id} AND school_student_id IS NOT NULL
                )");
            }
            
            $this->db->where('ss.school_internal_id IS NOT NULL');
            $this->db->where('ss.school_internal_id !=', '');
            $this->db->order_by('ss.name', 'ASC');
            
            $result['school_students'] = $this->db->get()->result_array();
        }

        // Get unsponsored university students OR students not sponsored by this particular sponsor
        if ($this->db->table_exists($this->tbl_university_students)) {
            $this->db->select('
                us.id,
                us.name,
                us.university_internal_id,
                us.university_year_of_study,
                us.city,
                us.email,
                un.name as university_name,
                up.name as program_name,
                "university" as student_type
            ');
            $this->db->from($this->tbl_university_students . ' us');
            $this->db->join($this->tbl_university_name . ' un', 'un.id = us.university_name_id', 'left');
            $this->db->join($this->tbl_university_program . ' up', 'up.id = us.university_program_id', 'left');
            
            if ($sponsor_id > 0) {
                // Exclude students already sponsored by this sponsor
                $this->db->where("us.id NOT IN (
                    SELECT DISTINCT university_student_id 
                    FROM " . $this->tbl_sponsor_transactions . " 
                    WHERE sponsor_id = {$sponsor_id} AND university_student_id IS NOT NULL
                )");
            }
            
            $this->db->where('us.university_internal_id IS NOT NULL');
            $this->db->where('us.university_internal_id !=', '');
            $this->db->order_by('us.name', 'ASC');
            
            $result['university_students'] = $this->db->get()->result_array();
        }

        return $result;
    }

   /**
 * FIXED VERSION - Update sponsored students WITHOUT deleting transactions
 * This method should only update the JSON fields in sponsor table
 * It does NOT touch the transactions table - transactions are managed separately
 * @param int $sponsor_id
 * @param array $school_internal_ids
 * @param array $university_internal_ids
 * @return bool
 */
public function update_sponsored_students($sponsor_id, $school_internal_ids = [], $university_internal_ids = [])
{
    if ($sponsor_id <= 0) {
        log_message('error', 'Invalid sponsor_id provided to update_sponsored_students: ' . $sponsor_id);
        return false;
    }

    // Ensure arrays
    $school_internal_ids = is_array($school_internal_ids) ? $school_internal_ids : [];
    $university_internal_ids = is_array($university_internal_ids) ? $university_internal_ids : [];

    // Clean the arrays - remove empty values
    $clean_school_ids = array_unique(array_filter($school_internal_ids, function($id) {
        return !empty(trim($id));
    }));
    
    $clean_university_ids = array_unique(array_filter($university_internal_ids, function($id) {
        return !empty(trim($id));
    }));

    log_message('info', "update_sponsored_students called for sponsor {$sponsor_id}");
    log_message('info', "School IDs: " . implode(', ', $clean_school_ids));
    log_message('info', "University IDs: " . implode(', ', $clean_university_ids));

    $this->db->trans_start();

    try {
        // ONLY update the JSON fields in sponsor table
        // Do NOT touch transactions - they should be managed separately via transactions module
        $update_data = [
            'school_internal_ids' => $this->encode_internal_ids($clean_school_ids),
            'university_internal_ids' => $this->encode_internal_ids($clean_university_ids)
        ];

        $this->db->where('id', $sponsor_id);
        $result = $this->db->update($this->table, $update_data);
        
        if (!$result) {
            throw new Exception('Failed to update sponsor JSON fields');
        }

        log_message('info', "Updated sponsor {$sponsor_id} JSON fields successfully");

        $this->db->trans_complete();
        
        if ($this->db->trans_status() === false) {
            throw new Exception('Transaction failed');
        }
        
        log_message('info', "Successfully completed update_sponsored_students for sponsor {$sponsor_id}");
        return true;

    } catch (Exception $e) {
        log_message('error', 'Error in update_sponsored_students: ' . $e->getMessage());
        $this->db->trans_rollback();
        return false;
    }
}

/* ============================== INTERNAL ID HELPERS ============================== */

/**
 * Encode array of internal IDs as JSON
 * @param array $ids
 * @return string|null
 */
private function encode_internal_ids($ids)
{
    if (empty($ids) || !is_array($ids)) {
        return null;
    }
    
    // Filter out empty values and ensure unique
    $clean_ids = array_unique(array_filter($ids, function($id) {
        return !empty(trim($id));
    }));
    
    return empty($clean_ids) ? null : json_encode(array_values($clean_ids));
}
/**
 * Decode JSON string to array of internal IDs
 * @param string $json
 * @return array
 */
private function decode_internal_ids($json)
{
    if (empty($json)) {
        return [];
    }
    
    $decoded = json_decode($json, true);
    
    if (!is_array($decoded)) {
        return [];
    }
    
    // Filter out empty values
    return array_filter($decoded, function($id) {
        return !empty(trim($id));
    });
}
    /**
     * Get statistics for a sponsor
     * @param int $sponsor_id Sponsor ID
     * @return array Sponsor statistics
     */
    public function get_sponsor_statistics($sponsor_id)
    {
        $students = $this->get_sponsored_students($sponsor_id);
        
        $stats = [
            'total_students' => 0,
            'school_students' => count($students['school_students']),
            'university_students' => count($students['university_students']),
            'total_commitment' => 0,
            'total_paid' => 0,
            'total_balance' => 0
        ];

        $stats['total_students'] = $stats['school_students'] + $stats['university_students'];

        // Calculate financial totals from transactions table
        if ($this->db->table_exists($this->tbl_sponsor_transactions)) {
            // $this->db->select('SUM(total_amount) as total_commitment, SUM(amount_paid) as total_paid');
            // $this->db->from($this->tbl_sponsor_transactions);
            // $this->db->where('sponsor_id', $sponsor_id);
            // $totals = $this->db->get()->row_array();
            // Get total commitment from transactions
            $this->db->select('SUM(total_amount) as total_commitment');
            $this->db->from($this->tbl_sponsor_transactions);
            $this->db->where('sponsor_id', $sponsor_id);
            $commitment_result = $this->db->get()->row_array();

            // Get total paid FRESH from payments table
            $paid_sql = "SELECT COALESCE(SUM(p.amount), 0) as total_paid
                        FROM " . db_prefix() . "sponsor_payments p
                        INNER JOIN " . $this->tbl_sponsor_transactions . " t ON t.id = p.transaction_id
                        WHERE t.sponsor_id = ?";
            $paid_result = $this->db->query($paid_sql, [$sponsor_id])->row_array();

            $totals = [
                'total_commitment' => $commitment_result['total_commitment'] ?? 0,
                'total_paid' => $paid_result['total_paid'] ?? 0
            ];
                        
            if ($totals) {
                $stats['total_commitment'] = (float)($totals['total_commitment'] ?? 0);
                $stats['total_paid'] = (float)($totals['total_paid'] ?? 0);
                $stats['total_balance'] = $stats['total_commitment'] - $stats['total_paid'];
            }
        }

        return $stats;
    }

    /* ============================== HELPER METHODS ============================== */

    /**
     * Country table schema - FIXED to detect actual database structure
     * @return array Country table configuration
     */
    private function country_schema()
    {
        return [
            'table' => db_prefix() . 'countries',
            'id' => 'country_id', 
            'name' => 'short_name'
        ];
    }

    /**
     * Validate country ID - FIXED version with proper error handling
     * @param mixed $country_id Country ID to validate
     * @return int|null Valid country ID or null
     */
    private function validate_country_id($country_id)
    {
        if (empty($country_id)) return null;
        
        $c = $this->country_schema();
        
        // If no country table exists, return null (no validation possible)
        if (!$c['table']) {
            log_message('debug', 'Sponsor_model: No country table exists, setting country_id to null');
            return null;
        }
        
        $country_id = (int)$country_id;
        
        // Verify country exists using correct schema
        $exists = $this->db->where($c['id'], $country_id)
                          ->count_all_results($c['table']) > 0;
        
        if (!$exists) {
            log_message('warning', 'Sponsor_model: Country ID ' . $country_id . ' not found in ' . $c['table']);
            return null;
        }
        
        return $country_id;
    }

    /**
     * Whitelist and sanitize input for insert/update
     * @param array $data Raw input data
     * @param bool $is_update Whether this is an update operation
     * @return array Sanitized data
     */
  /**
 * Whitelist and sanitize input for insert/update - FIXED VERSION
 * @param array $data Raw input data
 * @param bool $is_update Whether this is an update operation
 * @return array Sanitized data
 */
private function sanitize_payload(array $data, bool $is_update): array
{
    $fields = [
        'entity_type', 'name', 'email', 'contact_no', 'address', 'city', 'zip',
        'country_id', 'bank_id',
        'sponsor_type', 'sponsor_occupation',
        'sponsor_bank_branch_info', 'sponsor_bank_branch_number', 'sponsor_bank_account_no',
        'membership_start_date', 'membership_end_date', 'sponsor_frequency',
        'staff_id', 'active'
    ];

    $row = [];
    foreach ($fields as $f) {
        if (!array_key_exists($f, $data)) {
            continue;
        }
        $v = $data[$f];

        // Trim strings
        if (is_string($v)) {
            $v = trim($v);
        }

        switch ($f) {
            case 'country_id':
            case 'bank_id':
            case 'staff_id':
                // Allow nulls for foreign keys
                if ($v === '' || $v === null) {
                    $row[$f] = null;
                } else {
                    $row[$f] = (int) $v;
                }
                break;

            case 'active':
                $row[$f] = (int) !!$v;
                break;

            case 'membership_start_date':
            case 'membership_end_date':
                $row[$f] = $this->normalize_date_or_null($v);
                break;

            case 'entity_type':
                $row[$f] = ($v !== '') ? $v : 'sponsor';
                break;

            // CRITICAL FIX: Handle email and contact_no specially
            case 'email':
            case 'contact_no':
                // Allow empty string to clear the field
                // Let the validation in update() method handle email validation
                $row[$f] = ($v === null) ? null : (string)$v;
                break;

            default:
                // Other strings - convert empty to NULL only for non-critical fields
                if ($is_update) {
                    // For updates, preserve empty strings for text fields
                    // Only convert to NULL if explicitly null
                    $row[$f] = ($v === null) ? null : (string)$v;
                } else {
                    // For inserts, empty string is ok
                    $row[$f] = ($v === null) ? '' : (string)$v;
                }
                break;
        }
    }

    // Ensure default entity_type if not provided
    if (!isset($row['entity_type']) || $row['entity_type'] === '') {
        $row['entity_type'] = 'sponsor';
    }

    return $row;
}
    /**
     * Return Y-m-d or NULL
     * @param mixed $v Date value
     * @return string|null Normalized date
     */
    private function normalize_date_or_null($v): ?string
    {
        if ($v === '' || $v === null) {
            return null;
        }
        $ts = strtotime($v);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    /**
     * Map DB row to view form shape
     * /**
     * Map DB row to view form shape
     * @param array $r Database row
     * @return array Mapped form fields
     */
  private function map_database_to_form_fields(array $r): array
{
    return [
        'id'                          => $r['id'] ?? '',
        'name'                        => $r['name'] ?? '',
        'email'                       => $r['email'] ?? '',
        'contact_no'                  => $r['contact_no'] ?? '',
        'phone'                       => $r['contact_no'] ?? '',
        'address'                     => $r['address'] ?? '',
        'city'                        => $r['city'] ?? '',
        'zip'                         => $r['zip'] ?? '',
        'country_id'                  => $r['country_id'] ?? '',
        'country_name'                => $r['country_name'] ?? '',
        // REMOVED state_id and state fields
        'bank_id'                     => $r['bank_id'] ?? '',
        'bank_name'                   => $r['bank_name'] ?? '',
        'sponsor_type'                => $r['sponsor_type'] ?? '',
        'sponsor_occupation'          => $r['sponsor_occupation'] ?? '',
        'sponsor_bank_branch_info'    => $r['sponsor_bank_branch_info'] ?? '',
        'sponsor_bank_branch_number'  => $r['sponsor_bank_branch_number'] ?? '',
        'sponsor_bank_account_no'     => $r['sponsor_bank_account_no'] ?? '',
        'membership_start_date'       => $r['membership_start_date'] ?? '',
        'membership_end_date'         => $r['membership_end_date'] ?? '',
        'sponsor_frequency'           => $r['sponsor_frequency'] ?? '',
        'entity_type'                 => $r['entity_type'] ?? 'sponsor',
        'staff_id'                    => $r['staff_id'] ?? '',
        'active'                      => $r['active'] ?? '',
    ];
}

    /* ============================== TABLE MANAGEMENT ============================== */

    /**
     * Ensure all required tables exist
     */
    private function ensure_required_tables()
    {
        $this->ensure_sponsor_table();
        $this->ensure_sponsor_transactions_table();
    }

    /**
     * Ensure sponsor records table exists with CORRECT foreign key constraint
     */
  private function ensure_sponsor_table()
{
    if (!$this->db->table_exists($this->table)) {
        $c = $this->country_schema();
        $country_fk = '';
        
        if ($c['table']) {
            $country_fk = ",
                CONSTRAINT `{$this->table}_ibfk_1` 
                FOREIGN KEY (`country_id`) 
                REFERENCES `{$c['table']}` (`{$c['id']}`)
                ON DELETE SET NULL 
                ON UPDATE CASCADE";
        }
        
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$this->table}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `entity_type` varchar(50) NOT NULL DEFAULT 'sponsor',
                `name` varchar(255) NOT NULL,
                `email` varchar(255) DEFAULT NULL,
                `contact_no` varchar(50) DEFAULT NULL,
                `address` text DEFAULT NULL,
                `city` varchar(100) DEFAULT NULL,
                `zip` varchar(20) DEFAULT NULL,
                `country_id` int(11) DEFAULT NULL,
                `bank_id` int(11) DEFAULT NULL,
                `sponsor_type` varchar(100) DEFAULT NULL,
                `sponsor_occupation` varchar(255) DEFAULT NULL,
                `sponsor_bank_branch_info` varchar(255) DEFAULT NULL,
                `sponsor_bank_branch_number` varchar(50) DEFAULT NULL,
                `sponsor_bank_account_no` varchar(100) DEFAULT NULL,
                `membership_start_date` date DEFAULT NULL,
                `membership_end_date` date DEFAULT NULL,
                `sponsor_frequency` varchar(50) DEFAULT NULL,
                `staff_id` int(11) DEFAULT NULL,
                `active` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime DEFAULT current_timestamp(),
                `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_bank_account` (`sponsor_bank_account_no`),
                KEY `idx_country_id` (`country_id`),
                KEY `idx_bank_id` (`bank_id`),
                KEY `idx_staff_id` (`staff_id`),
                KEY `idx_active` (`active`),
                KEY `idx_sponsor_type` (`sponsor_type`)
                {$country_fk}
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        ");
        
        log_message('info', 'Sponsor_model: Created sponsor_records table with country FK to ' . ($c['table'] ?? 'none'));
    }
}

    /**
     * Ensure sponsor transactions table exists
     */
    private function ensure_sponsor_transactions_table()
    {
        if (!$this->db->table_exists($this->tbl_sponsor_transactions)) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `{$this->tbl_sponsor_transactions}` (
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
                    `next_payment_due` date DEFAULT NULL,
                    `status` varchar(20) DEFAULT 'active',
                    `created_at` datetime DEFAULT current_timestamp(),
                    `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    PRIMARY KEY (`id`),
                    KEY `idx_sponsor_id` (`sponsor_id`),
                    KEY `idx_school_student_id` (`school_student_id`),
                    KEY `idx_university_student_id` (`university_student_id`),
                    KEY `idx_status` (`status`),
                    CONSTRAINT `fk_sponsor_transactions_sponsor` 
                    FOREIGN KEY (`sponsor_id`) 
                    REFERENCES `{$this->table}` (`id`) 
                    ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
            ");
        }
    }

    /* ============================== DIAGNOSTIC/DEBUG METHODS ============================== */

    /**
     * Debug method to check country table structure
     * Use this to diagnose which country table exists in your database
     * 
     * @return array Debug information
     */
    public function debug_country_schema()
    {
        $debug_info = [
            'detected_schema' => $this->country_schema(),
            'tables_checked' => []
        ];

        // Check both possible table names
        $possible_tables = [
            'tblcountry' => db_prefix() . 'country',
            'tblcountries' => db_prefix() . 'countries'
        ];

        foreach ($possible_tables as $label => $table_name) {
            $exists = $this->db->table_exists($table_name);
            $debug_info['tables_checked'][$label] = [
                'table_name' => $table_name,
                'exists' => $exists
            ];

            if ($exists) {
                // Get table structure
                $structure = $this->db->query("DESCRIBE `{$table_name}`")->result_array();
                $debug_info['tables_checked'][$label]['structure'] = $structure;
                
                // Get sample data
                $sample = $this->db->limit(3)->get($table_name)->result_array();
                $debug_info['tables_checked'][$label]['sample_data'] = $sample;
            }
        }

        // Check sponsor_records foreign key constraints
        $fk_query = "
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '{$this->table}'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ";
        
        $debug_info['sponsor_foreign_keys'] = $this->db->query($fk_query)->result_array();

        return $debug_info;
    }

    

    /**
     * Fix country foreign key constraint if it's pointing to wrong table
     * WARNING: This will drop and recreate the foreign key
     * 
     * @return array Result of fix operation
     */
    public function fix_country_foreign_key()
    {
        $result = [
            'success' => false,
            'message' => '',
            'actions_taken' => []
        ];

        try {
            $c = $this->country_schema();
            
            if (!$c['table']) {
                $result['message'] = 'No country table detected in database';
                return $result;
            }

            // Get existing FK constraints on country_id
            $existing_fk = $this->db->query("
                SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '{$this->table}'
                AND COLUMN_NAME = 'country_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ")->result_array();

            foreach ($existing_fk as $fk) {
                // Drop incorrect FK
                $drop_sql = "ALTER TABLE `{$this->table}` DROP FOREIGN KEY `{$fk['CONSTRAINT_NAME']}`";
                $this->db->query($drop_sql);
                $result['actions_taken'][] = "Dropped FK: {$fk['CONSTRAINT_NAME']}";
            }

            // Create correct FK
            $fk_name = $this->table . '_country_fk';
            $create_fk_sql = "
                ALTER TABLE `{$this->table}`
                ADD CONSTRAINT `{$fk_name}`
                FOREIGN KEY (`country_id`) 
                REFERENCES `{$c['table']}` (`{$c['id']}`)
                ON DELETE SET NULL 
                ON UPDATE CASCADE
            ";
            
            $this->db->query($create_fk_sql);
            $result['actions_taken'][] = "Created FK: {$fk_name} -> {$c['table']}({$c['id']})";
            
            $result['success'] = true;
            $result['message'] = 'Foreign key constraint fixed successfully';
            
        } catch (Exception $e) {
            $result['success'] = false;
            $result['message'] = 'Error fixing foreign key: ' . $e->getMessage();
            log_message('error', 'Sponsor_model::fix_country_foreign_key - ' . $e->getMessage());
        }

        return $result;
    }

    
}