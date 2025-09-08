<?php
defined('BASEPATH') or exit('No direct script access allowed');

class University_model extends App_Model
{
    private $tbl_students = 'tbluniversity_students';
    private $tbl_uname    = 'tbluniversity_name';
    private $tbl_program  = 'tbluniversity_program';
    private $tbl_bank     = 'tblbank';
    private $tbl_rcard    = 'tbluniversity_report_card';

    public function __construct()
    {
        parent::__construct();
        $this->ensure_required_tables();
    }

    /* ----------------- ENHANCED FILTERING & STATISTICS ----------------- */

    /**
     * Get comprehensive statistics for dashboard
     * 
     * @return array Statistics array with counts
     */
    public function get_statistics()
    {
        $stats = [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'verified' => 0,
            'unverified' => 0,
            'by_year' => [],
            'by_status' => [],
            'by_program' => [],
            'recent_additions' => 0
        ];

        // Base query for all students with status information
        $this->db->select('
            us.id,
            us.university_year_of_study,
            us.staff_id,
            s.active as staff_active,
            us.created_at,
            up.name as program_name
        ');
        $this->db->from(db_prefix() . 'university_students us');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = us.staff_id', 'left');
        $this->db->join(db_prefix() . 'university_program up', 'up.id = us.university_program_id', 'left');
        
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

            // Count by year
            $year = $student['university_year_of_study'] ?? 'Unknown';
            $stats['by_year'][$year] = ($stats['by_year'][$year] ?? 0) + 1;

            // Count by program
            $program = $student['program_name'] ?? 'Unknown';
            $stats['by_program'][$program] = ($stats['by_program'][$program] ?? 0) + 1;

            // Count recent additions
            if (!empty($student['created_at']) && $student['created_at'] >= $thirty_days_ago) {
                $stats['recent_additions']++;
            }
        }

        return $stats;
    }

    /**
     * Advanced filtering for students list with DataTables server-side processing
     * 
     * @param array $filters Filter parameters
     * @param array $datatables_params DataTables parameters (start, length, search, order)
     * @return array Filtered results with pagination info
     */
    public function get_students_filtered($filters = [], $datatables_params = [])
    {
        // Build base query with all necessary joins
        $this->db->select('
            us.*,
            un.name AS university_name,
            up.name AS program_name,
            b.name AS bank_name,
            c.short_name AS country_name,
            s.active as staff_active,
            s.staffid as staff_id
        ', false);

        $this->db->from(db_prefix() . 'university_students us');
        $this->db->join(db_prefix() . 'university_name un', 'un.id = us.university_name_id', 'left');
        $this->db->join(db_prefix() . 'university_program up', 'up.id = us.university_program_id', 'left');
        $this->db->join(db_prefix() . 'bank b', 'b.id = us.bank_id', 'left');
        $this->db->join(db_prefix() . 'countries c', 'c.country_id = us.country_id', 'left');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = us.staff_id', 'left');

        // Apply filters
        $this->apply_filters($filters);

        // Handle DataTables search
        if (!empty($datatables_params['search']['value'])) {
            $search = $datatables_params['search']['value'];
            $this->db->group_start();
            $this->db->like('us.name', $search);
            $this->db->or_like('us.email', $search);
            $this->db->or_like('us.contact_no', $search);
            $this->db->or_like('us.university_internal_id', $search);
            $this->db->or_like('un.name', $search);
            $this->db->or_like('up.name', $search);
            $this->db->group_end();
        }

        // Get total count before pagination
        $total_query = clone $this->db;
        $total_records = $total_query->count_all_results('', false);

        // Handle ordering
        if (!empty($datatables_params['order'])) {
            foreach ($datatables_params['order'] as $order) {
                $column_index = (int)$order['column'];
                $direction = $order['dir'] === 'desc' ? 'DESC' : 'ASC';
                
                // Map column indices to actual columns
                $columns = ['us.id', 'us.name', 'un.name', 'up.name', 'us.university_year_of_study', 'status', 'us.contact_no'];
                if (isset($columns[$column_index])) {
                    if ($columns[$column_index] !== 'status') {
                        $this->db->order_by($columns[$column_index], $direction);
                    }
                }
            }
        } else {
            $this->db->order_by('us.id', 'DESC');
        }

        // Handle pagination
        if (isset($datatables_params['length']) && $datatables_params['length'] != -1) {
            $this->db->limit($datatables_params['length'], $datatables_params['start'] ?? 0);
        }

        $students = $this->db->get()->result_array();

        // Add computed status to each student
        foreach ($students as &$student) {
            $student['computed_status'] = $this->determine_student_status($student);
        }

        return [
            'data' => $students,
            'recordsTotal' => $this->count_all(),
            'recordsFiltered' => $total_records
        ];
    }

    /**
     * Apply filters to the current query
     * 
     * @param array $filters Filter parameters
     */
    private function apply_filters($filters)
    {
        // Year of study filter
        if (!empty($filters['year'])) {
            $this->db->where('us.university_year_of_study', $filters['year']);
        }

        // Status filter
        if (!empty($filters['status'])) {
            $this->apply_status_filter($filters['status']);
        }

        // University filter
        if (!empty($filters['university'])) {
            $this->db->like('un.name', $filters['university']);
        }

        // Program filter
        if (!empty($filters['program'])) {
            $this->db->like('up.name', $filters['program']);
        }

        // City filter
        if (!empty($filters['city'])) {
            $this->db->like('us.city', $filters['city']);
        }

        // Age range filter
        if (!empty($filters['age_min'])) {
            $this->db->where('us.university_age >=', (int)$filters['age_min']);
        }
        if (!empty($filters['age_max'])) {
            $this->db->where('us.university_age <=', (int)$filters['age_max']);
        }

        // Date range filters
        if (!empty($filters['created_from'])) {
            $this->db->where('DATE(us.created_at) >=', $filters['created_from']);
        }
        if (!empty($filters['created_to'])) {
            $this->db->where('DATE(us.created_at) <=', $filters['created_to']);
        }

        // Sponsorship status
        if (!empty($filters['sponsorship_status'])) {
            if ($filters['sponsorship_status'] === 'sponsored') {
                $this->db->where('us.sponsor_id IS NOT NULL');
            } elseif ($filters['sponsorship_status'] === 'unsponsored') {
                $this->db->where('us.sponsor_id IS NULL');
            }
        }
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
                $this->db->where('us.staff_id IS NOT NULL');
                $this->db->where('s.active', 1);
                break;
            case 'inactive':
                $this->db->where('us.staff_id IS NOT NULL');
                $this->db->where('s.active', 0);
                break;
            case 'verified':
                $this->db->where('us.staff_id IS NOT NULL');
                break;
            case 'unverified':
                $this->db->where('us.staff_id IS NULL');
                break;
        }
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
     * Get dropdown data for filters
     * 
     * @return array Dropdown options
     */
    public function get_filter_options()
    {
        return [
            'years' => $this->get_available_years(),
            'universities' => $this->get_universities(),
            'programs' => $this->get_programs(),
            'cities' => $this->get_available_cities(),
            'countries' => $this->get_countries(),
            'banks' => $this->get_banks()
        ];
    }

    /**
     * Get available years from existing students
     * 
     * @return array List of years
     */
    public function get_available_years()
    {
        $this->db->select('university_year_of_study as year');
        $this->db->from(db_prefix() . 'university_students');
        $this->db->where('university_year_of_study IS NOT NULL');
        $this->db->where('university_year_of_study !=', '');
        $this->db->group_by('university_year_of_study');
        $this->db->order_by('university_year_of_study', 'ASC');

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
        $this->db->from(db_prefix() . 'university_students');
        $this->db->where('city IS NOT NULL');
        $this->db->where('city !=', '');
        $this->db->group_by('city');
        $this->db->order_by('city', 'ASC');

        return $this->db->get()->result_array();
    }

    /* ----------------- VALIDATION ----------------- */

    public function validate_student_data($data)
    {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Student name is required';
        }
        
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        return empty($errors) ? ['valid' => true] : ['valid' => false, 'errors' => $errors];
    }

    /* ----------------- INSTALL/CHECKS ----------------- */
    
    private function ensure_required_tables()
    {
        $this->ensure_university_name_table();
        $this->ensure_university_program_table();
        $this->ensure_university_report_card_table();
        $this->ensure_bank_table();
        $this->ensure_university_students_photo_column();
    }

    private function ensure_university_students_photo_column()
    {
        $table = db_prefix().'university_students';
        if (!$this->db->table_exists($table)) return;

        if (!$this->db->field_exists('profile_photo', $table)) {
            $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `profile_photo` LONGBLOB NULL AFTER `name`");
        } else {
            $this->db->query("ALTER TABLE `{$table}` MODIFY COLUMN `profile_photo` LONGBLOB NULL");
        }
    }

    private function ensure_university_name_table()
    {
        $table_name = db_prefix() . 'university_name';
        if (!$this->db->table_exists($table_name)) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `{$table_name}` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) NOT NULL,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `name` (`name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }

    private function ensure_university_program_table()
    {
        $table_name = db_prefix() . 'university_program';
        if (!$this->db->table_exists($table_name)) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `{$table_name}` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) NOT NULL,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `name` (`name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }

    private function ensure_university_report_card_table()
    {
        $table = db_prefix().'university_report_card';

        if (!$this->db->table_exists($table)) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `{$table}` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `student_university_id` int(11) NOT NULL,
                    `filename` varchar(255) COLLATE latin1_swedish_ci NOT NULL,
                    `report_card_file` varchar(255) COLLATE latin1_swedish_ci NULL,
                    `upload_date` date NOT NULL,
                    `report_card_term` enum('1Y1S','1Y2S','2Y1S','2Y2S','3Y1S','3Y2S','4Y1S','4Y2S','5Y1S','5Y2S') COLLATE latin1_swedish_ci NOT NULL,
                    `current_term` enum('1Y1S','1Y2S','2Y1S','2Y2S','3Y1S','3Y2S','4Y1S','4Y2S','5Y1S','5Y2S') COLLATE latin1_swedish_ci NOT NULL,
                    `semester_end_month` tinyint(3) unsigned NULL,
                    `semester_end_year` year(4) NULL,
                    `file_blob` mediumblob NULL,
                    `mime_type` varchar(100) COLLATE latin1_swedish_ci NULL,
                    `file_size` int(10) unsigned NULL,
                    `sha256` char(64) COLLATE latin1_swedish_ci NULL,
                    `created_on` datetime NULL,
                    PRIMARY KEY (`id`),
                    KEY `student_university_id` (`student_university_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
            ");
            return;
        }

        if (!$this->db->field_exists('file_blob', $table)) {
            $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `file_blob` MEDIUMBLOB NULL AFTER `upload_date`");
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
        $table_name = db_prefix() . 'bank';
        if (!$this->db->table_exists($table_name)) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `{$table_name}` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) NOT NULL,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `name` (`name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
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

        if ($size <= 0 || $size > 5*1024*1024) {
            throw new Exception('File size too large. Max 5MB.');
        }

        $mime = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            if ($f) { $m = @finfo_file($f, $tmp); if ($m) $mime = strtolower($m); finfo_close($f); }
        } elseif (function_exists('getimagesize')) {
            $gi = @getimagesize($tmp);
            if ($gi && !empty($gi['mime'])) $mime = strtolower($gi['mime']);
        }

        $allowed = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
        if (!in_array($mime, $allowed, true)) throw new Exception('Invalid file type. Only JPG/PNG/GIF/WebP allowed.');

        $bytes = @file_get_contents($tmp);
        if ($bytes === false) throw new Exception('Could not read uploaded file.');

        return $bytes;
    }
    
    public function get_profile_photo($student_id)
    {
        $row = $this->db->select('profile_photo')->where('id', (int)$student_id)->get($this->tbl_students)->row();
        return $row ? $row->profile_photo : null;
    }

    /* ----------------- HANDLE NEW ITEMS ----------------- */
    
    private function process_new_items($data)
    {
        if (!empty($data['new_country_name']) && !empty($data['new_country_phone_code'])) {
            $country_id = $this->create_new_country($data['new_country_name'], $data['new_country_phone_code']);
            if ($country_id) {
                $data['country_id'] = $country_id;
            }
        }
        if (!empty($data['new_university_name'])) {
            $university_id = $this->create_new_university($data['new_university_name']);
            if ($university_id) $data['university_name_id'] = $university_id;
        }
        if (!empty($data['new_program_name'])) {
            $program_id = $this->create_new_program($data['new_program_name']);
            if ($program_id) $data['university_program_id'] = $program_id;
        }
        if (!empty($data['new_bank_name'])) {
            $bank_id = $this->create_new_bank($data['new_bank_name']);
            if ($bank_id) $data['bank_id'] = $bank_id;
        }
        return $data;
    }
    
    private function create_new_country($name, $phone_code)
    {
        try {
            $tbl = db_prefix().'countries';
            $payload = [
                'short_name'   => $name,
                'calling_code' => $phone_code,
            ];
            $this->db->insert($tbl, $payload);
            return (int)$this->db->insert_id();
        } catch (Exception $e) {
            log_message('error', 'Error creating country: ' . $e->getMessage());
            return null;
        }
    }
    
    private function create_new_university($name)
    {
        try {
            $this->db->insert(db_prefix() . 'university_name', ['name' => $name]);
            return (int)$this->db->insert_id();
        } catch (Exception $e) {
            log_message('error', 'Error creating university: ' . $e->getMessage());
            return null;
        }
    }
    
    private function create_new_program($name)
    {
        try {
            $this->db->insert(db_prefix() . 'university_program', ['name' => $name]);
            return (int)$this->db->insert_id();
        } catch (Exception $e) {
            log_message('error', 'Error creating program: ' . $e->getMessage());
            return null;
        }
    }
    
    private function create_new_bank($name)
    {
        try {
            $this->db->insert(db_prefix() . 'bank', ['name' => $name]);
            return (int)$this->db->insert_id();
        } catch (Exception $e) {
            log_message('error', 'Error creating bank: ' . $e->getMessage());
            return null;
        }
    }

    /* ----------------- CRUD OPERATIONS ----------------- */

    public function add($data)
    {
        try {
            $validation = $this->validate_student_data($data);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => implode(', ', $validation['errors'])];
            }

            $data = $this->process_new_items($data);

            $profile_photo_data = null;
            try { 
                $profile_photo_data = $this->handle_profile_photo_upload(); 
            } catch (Exception $e) { 
                log_message('error', 'Profile photo upload error: ' . $e->getMessage()); 
            }

            $university_name_id    = $this->get_or_create_university_name_id($data['university_name'] ?? ($data['university_name_id'] ?? ''));
            $university_program_id = $this->get_or_create_university_program_id($data['program'] ?? ($data['university_program_id'] ?? ''));
            $bank_id               = $this->get_or_create_bank_id($data['bank_name'] ?? ($data['bank_id'] ?? ''));

            $country_id = !empty($data['country_id']) ? (int)$data['country_id'] : null;

            $insert = [
                'entity_type'                       => 'university',
                'name'                              => $data['name'] ?? '',
                'profile_photo'                     => $profile_photo_data,
                'contact_no'                        => $data['phone'] ?? '',
                'email'                             => $this->toNullIfEmpty($data['email'] ?? ''),
                'address'                           => $this->toNullIfEmpty($data['address'] ?? ''),
                'city'                              => $this->toNullIfEmpty($data['city'] ?? ''),
                'zip'                               => $this->toNullIfEmpty($data['postal_code'] ?? ''),
                'country_id'                        => $country_id,
                'university_internal_id'            => $this->generate_internal_id(),
                'university_id'                     => $this->toNullIfEmpty($data['university_id'] ?? ''),
                'university_name_id'                => $university_name_id,
                'university_program_id'             => $university_program_id,
                'university_year_of_study'          => $this->sanitize_year($data['year_of_study'] ?? null),
                'university_student_dob'            => $this->toNullIfEmpty($data['dob'] ?? ''),
                'university_age'                    => $this->calc_age($data['dob'] ?? null),
                'bank_id'                           => $bank_id,
                'university_bank_branch_number'     => $this->toNullIfEmpty($data['bank_branch_number'] ?? ''),
                'university_bank_branch_info'       => $this->toNullIfEmpty($data['bank_branch_info'] ?? ''),
                'university_bank_account_no'        => $this->toNullIfEmpty($data['bank_account_number'] ?? ''),
                'university_sponsorship_start_date' => $this->toNullIfEmpty($data['sponsorship_start'] ?? ''),
                'university_sponsorship_end_date'   => $this->toNullIfEmpty($data['sponsorship_end'] ?? ''),
                'university_introducedby'           => $this->toNullIfEmpty($data['introduced_by'] ?? ''),
                'university_introducedph'           => $this->toNullIfEmpty($data['introduced_phone'] ?? ''),
                'university_father_name'            => $this->toNullIfEmpty($data['father_name'] ?? ''),
                'university_mother_name'            => $this->toNullIfEmpty($data['mother_name'] ?? ''),
                'university_father_income'          => $this->toFloatOrNull($data['father_income'] ?? null),
                'university_mother_income'          => $this->toFloatOrNull($data['mother_income'] ?? null),
                'university_guardian_name'          => $this->toNullIfEmpty($data['guardian_name'] ?? ''),
                'university_guardian_income'        => $this->toFloatOrNull($data['guardian_income'] ?? null),
                'sponsor_id'                        => $this->toIntOrNull($data['sponsor_id'] ?? ''),
                'background_info'                   => $this->toNullIfEmpty($data['background_information'] ?? ''),
                'internal_comment'                  => $this->toNullIfEmpty($data['internal_comment'] ?? ''),
                'external_comment'                  => $this->toNullIfEmpty($data['external_comment'] ?? ''),
                'created_at'                        => date('Y-m-d H:i:s')
            ];

            $insert = $this->filter_existing_columns(db_prefix() . 'university_students', $insert);

            $this->db->insert(db_prefix() . 'university_students', $insert);
            $student_id = (int)$this->db->insert_id();

            if ($student_id) {
                return $student_id;
            }

            return false;

        } catch (Exception $e) {
            log_message('error', 'Error adding university student: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error adding student: ' . $e->getMessage()];
        }
    }

    public function update($data, $id)
    {
        try {
            $id = (int)$id;
            if ($id <= 0) return false;

            $validation = $this->validate_student_data($data);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => implode(', ', $validation['errors'])];
            }

            $data = $this->process_new_items($data);

            $update_data = [];
            try {
                $photo = $this->handle_profile_photo_upload();
                if ($photo !== null) {
                    $update_data['profile_photo'] = $photo;
                }
            } catch (Exception $e) {
                log_message('error','University photo upload (update): '.$e->getMessage());
            }

            $field_mappings = [
                'name' => 'name',
                'email' => 'email',
                'contact_no' => 'contact_no',
                'address' => 'address',
                'city' => 'city',
                'zip' => 'zip',
                'country_id' => 'country_id',
                'university_id' => 'university_id',
                'university_internal_id' => 'university_internal_id',
                'university_name_id' => 'university_name_id',
                'university_program_id' => 'university_program_id',
                'university_year_of_study' => 'university_year_of_study',
                'university_student_dob' => 'university_student_dob',
                'university_age' => 'university_age',
                'bank_id' => 'bank_id',
                'university_bank_account_no' => 'university_bank_account_no',
                'university_bank_branch_number' => 'university_bank_branch_number',
                'university_bank_branch_info' => 'university_bank_branch_info',
                'university_sponsorship_start_date' => 'university_sponsorship_start_date',
                'university_sponsorship_end_date' => 'university_sponsorship_end_date',
                'university_introducedby' => 'university_introducedby',
                'university_introducedph' => 'university_introducedph',
                'sponsor_id' => 'sponsor_id',
                'university_father_name' => 'university_father_name',
                'university_mother_name' => 'university_mother_name',
                'university_guardian_name' => 'university_guardian_name',
                'university_father_income' => 'university_father_income',
                'university_mother_income' => 'university_mother_income',
                'university_guardian_income' => 'university_guardian_income',
                'background_info' => 'background_info',
                'internal_comment' => 'internal_comment',
                'external_comment' => 'external_comment'
            ];

            foreach ($field_mappings as $db_field => $target_field) {
                if (array_key_exists($db_field, $data)) {
                    $value = $data[$db_field];
                    
                    if (in_array($db_field, ['country_id', 'bank_id', 'university_name_id', 'university_program_id', 'sponsor_id'], true)) {
                        if ($value === '' || $value === null) {
                            $update_data[$target_field] = null;
                        } else {
                            $fk_id = (int)$value;
                            if ($this->validateForeignKey($db_field, $fk_id)) {
                                $update_data[$target_field] = $fk_id;
                            } else {
                                $update_data[$target_field] = null;
                            }
                        }
                    } elseif (in_array($db_field, ['university_father_income', 'university_mother_income', 'university_guardian_income', 'university_age'], true)) {
                        $update_data[$target_field] = ($value === '' || $value === null) ? null : (is_numeric($value) ? (float)$value : null);
                    } else {
                        $update_data[$target_field] = ($value === '' || $value === null) ? null : $value;
                    }
                }
            }

            $update_data = $this->filter_existing_columns(db_prefix() . 'university_students', $update_data);

            if (empty($update_data)) {
                return true;
            }

            $this->db->where('id', $id);
            $result = $this->db->update(db_prefix() . 'university_students', $update_data);

            if (!$result) {
                $error = $this->db->error();
                log_message('error', 'University_model::update failed: '.json_encode($error));
                return false;
            }

            return true;

        } catch (Exception $e) {
            log_message('error','Error updating university student: '.$e->getMessage());
            return ['success' => false, 'message' => 'Error updating student: ' . $e->getMessage()];
        }
    }

    public function delete($id)
    {
        try {
            $tbl_rcard = db_prefix() . 'university_report_card';
            
            $cards = $this->db->where('student_university_id', (int)$id)->get($tbl_rcard)->result();
            foreach ($cards as $c) {
                if (!empty($c->report_card_file)) {
                    $abs = rtrim(FCPATH, '/\\') . '/' . ltrim($c->report_card_file, '/');
                    if (is_file($abs)) @unlink($abs);
                }
            }
            $this->db->where('student_university_id', (int)$id)->delete($tbl_rcard);

            $this->db->where('id', (int)$id)->delete(db_prefix() . 'university_students');

            return $this->db->affected_rows() > 0;
        } catch (Exception $e) {
            log_message('error', 'Error deleting university student: ' . $e->getMessage());
            return false;
        }
    }

    /* ----------------- READ OPERATIONS ----------------- */

    public function get_all()
    {
        $c = $this->country_schema();

        $this->db->select('
            us.*,
            un.name AS university_name,
            up.name AS program_name,
            b.name  AS bank_name' .
            ($c['table'] ? ', c.' . $c['name'] . ' AS country_name' : ', NULL AS country_name')
        , false);

        $this->db->from(db_prefix() . 'university_students us');
        $this->db->join(db_prefix() . 'university_name un', 'un.id = us.university_name_id', 'left');
        $this->db->join(db_prefix() . 'university_program up', 'up.id = us.university_program_id', 'left');

        if ($c['table']) {
            $this->db->join($c['table'] . ' c', 'c.' . $c['id'] . ' = us.country_id', 'left');
        }

        $this->db->join(db_prefix() . 'bank b', 'b.id = us.bank_id', 'left');
        $this->db->order_by('us.id', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_by_id($id)
    {
        $c = $this->country_schema();

        $this->db->select('
            us.*,
            un.name AS university_name,
            up.name AS program_name,
            b.name  AS bank_name' .
            ($c['table'] ? ', c.' . $c['name'] . ' AS country_name' : ', NULL AS country_name')
        , false);

        $this->db->from(db_prefix() . 'university_students us');
        $this->db->join(db_prefix() . 'university_name un', 'un.id = us.university_name_id', 'left');
        $this->db->join(db_prefix() . 'university_program up', 'up.id = us.university_program_id', 'left');

        if ($c['table']) {
            $this->db->join($c['table'] . ' c', 'c.' . $c['id'] . ' = us.country_id', 'left');
        }

        $this->db->join(db_prefix() . 'bank b', 'b.id = us.bank_id', 'left');
        $this->db->where('us.id', (int)$id);
        return $this->db->get()->row_array();
    }

    public function count_all()
    {
        return $this->db->count_all_results(db_prefix() . 'university_students');
    }

    /* ----------------- REPORT CARD METHODS ----------------- */
    
    public function get_report_cards($student_id)
    {
        $tbl = db_prefix().'university_report_card';
        
        $this->db->select('
            id,
            student_university_id,
            filename,
            report_card_term,
            current_term,
            semester_end_month,
            semester_end_year,
            upload_date,
            report_card_file,
            mime_type,
            file_size,
            sha256,
            created_on
        ', false);
        
        $this->db->where('student_university_id', (int)$student_id);
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

            $c['file_url'] = admin_url('student_sponsor_portal/download_university_report_card/'.$c['id']);
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
                return $this->db->where('id', $id)->count_all_results(db_prefix() . 'bank') > 0;
                
            case 'university_name_id':
                return $this->db->where('id', $id)->count_all_results(db_prefix() . 'university_name') > 0;
                
            case 'university_program_id':
                return $this->db->where('id', $id)->count_all_results(db_prefix() . 'university_program') > 0;
                
            case 'sponsor_id':
                return $this->db->where('id', $id)->count_all_results(db_prefix() . 'sponsor_records') > 0;
                
            default:
                return true;
        }
    }

    private function country_schema()
    {
        if ($this->db->table_exists(db_prefix() . 'countries')) {
            return [
                'table'     => db_prefix() . 'countries',
                'id'        => 'country_id',
                'name'      => 'short_name',
                'phone'     => 'calling_code',
                'is_custom' => false,
            ];
        }
        if ($this->db->table_exists(db_prefix() . 'country')) {
            return [
                'table'     => db_prefix() . 'country',
                'id'        => 'id',
                'name'      => 'name',
                'phone'     => 'phone_code',
                'is_custom' => true,
            ];
        }
        return ['table' => null, 'id' => null, 'name' => null, 'phone' => null, 'is_custom' => null];
    }

    private function generate_internal_id()
    {
        $this->db->select('MAX(CAST(SUBSTRING(university_internal_id, 4) AS UNSIGNED)) as max_id');
        $this->db->like('university_internal_id', 'UNI', 'after');
        $row = $this->db->get(db_prefix() . 'university_students')->row();
        $next = ($row && $row->max_id) ? ((int)$row->max_id + 1) : 1;
        return 'UNI' . str_pad($next, 3, '0', STR_PAD_LEFT);
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

    public function get_or_create_university_name_id($name_or_id)
    {
        if (!$name_or_id) return null;
        if (is_numeric($name_or_id)) return (int)$name_or_id;

        $this->db->where('name', $name_or_id);
        $row = $this->db->get(db_prefix() . 'university_name')->row();
        if ($row) return (int)$row->id;

        $this->db->insert(db_prefix() . 'university_name', ['name' => $name_or_id]);
        return (int)$this->db->insert_id();
    }

    public function get_or_create_university_program_id($name_or_id)
    {
        if (!$name_or_id) return null;
        if (is_numeric($name_or_id)) return (int)$name_or_id;

        $this->db->where('name', $name_or_id);
        $row = $this->db->get(db_prefix() . 'university_program')->row();
        if ($row) return (int)$row->id;

        $this->db->insert(db_prefix() . 'university_program', ['name' => $name_or_id]);
        return (int)$this->db->insert_id();
    }

    public function get_or_create_bank_id($name_or_id)
    {
        if (!$name_or_id) return null;
        if (is_numeric($name_or_id)) return (int)$name_or_id;

        $this->db->where('name', $name_or_id);
        $row = $this->db->get(db_prefix() . 'bank')->row();
        if ($row) return (int)$row->id;

        $this->db->insert(db_prefix() . 'bank', ['name' => $name_or_id]);
        return (int)$this->db->insert_id();
    }

    private function sanitize_year($val)
    {
        $allowed = ['1Y1S','1Y2S','2Y1S','2Y2S','3Y1S','3Y2S','4Y1S','4Y2S','5Y1S','5Y2S'];
        if (!$val) return null;
        if (in_array($val, $allowed, true)) return $val;

        $map = [
            '1' => '1Y1S', '2' => '2Y1S', '3' => '3Y1S', '4' => '4Y1S', '5' => '5Y1S',
            'Year 1' => '1Y1S', 'Year 2' => '2Y1S', 'Year 3' => '3Y1S', 'Year 4' => '4Y1S', 'Year 5' => '5Y1S'
        ];
        return $map[$val] ?? null;
    }

    private function calc_age($dob)
    {
        if (!$dob) return null;
        try {
            $d1 = new DateTime($dob);
            $d2 = new DateTime('now');
            return (int)$d1->diff($d2)->y;
        } catch (Exception $e) {
            return null;
        }
    }

    private function toFloatOrNull($v)
    {
        if ($v === '' || $v === null || $v === 0) return null;
        return (float)$v;
    }

    private function toIntOrNull($v)
    {
        if ($v === '' || $v === null || $v === 0) return null;
        return (int)$v;
    }

    private function toNullIfEmpty($v)
    {
        if ($v === '' || $v === null) return null;
        return $v;
    }

    /* ----------------- DROPDOWN DATA HELPERS ----------------- */
    
    public function get_countries()
    {
        $c = $this->country_schema();
        if (!$c['table']) return [];

        if ($c['is_custom']) {
            $this->db->select('id, name, phone_code');
            $this->db->from($c['table']);
            $this->db->order_by('name', 'ASC');
            return $this->db->get()->result_array();
        } else {
            $this->db->select($c['id'] . ' AS id, ' . $c['name'] . ' AS name, ' . $c['phone'] . ' AS phone_code', false);
            $this->db->from($c['table']);
            $this->db->order_by($c['name'], 'ASC');
            return $this->db->get()->result_array();
        }
    }

    public function get_universities()
    {
        $this->ensure_university_name_table();
        return $this->db->order_by('name', 'ASC')->get(db_prefix() . 'university_name')->result_array();
    }

    public function get_programs()
    {
        $this->ensure_university_program_table();
        return $this->db->order_by('name', 'ASC')->get(db_prefix() . 'university_program')->result_array();
    }

    public function get_banks()
    {
        $this->ensure_bank_table();
        return $this->db->order_by('name', 'ASC')->get(db_prefix() . 'bank')->result_array();
    }

    public function get_sponsors()
    {
        if ($this->db->table_exists(db_prefix() . 'sponsor_records')) {
            $this->db->where('entity_type', 'sponsor');
            return $this->db->get(db_prefix() . 'sponsor_records')->result_array();
        }
        return [];
    }

    // Maintain compatibility with existing methods
    public function update_student($data, $id) { return $this->update($data, $id); }
    public function delete_student($id) { return $this->delete($id); }
}