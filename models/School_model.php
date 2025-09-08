<?php
defined('BASEPATH') or exit('No direct script access allowed');

class School_model extends App_Model
{
    private $tbl_students = 'tblschool_students';
    private $tbl_sname    = 'tblschool_name';
    private $tbl_bank     = 'tblbank';
    private $tbl_rcard    = 'tblschool_report_card';

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
    ];

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
            'by_grade' => [],
            'by_status' => [],
            'recent_additions' => 0
        ];

        // Base query for all students with status information
        $this->db->select('
            ss.id,
            ss.school_grade,
            ss.staff_id,
            s.active as staff_active,
            ss.created_at
        ');
        $this->db->from($this->tbl_students . ' ss');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = ss.staff_id', 'left');
        
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

            // Count by grade
            $grade = $student['school_grade'] ?? 'Unknown';
            $stats['by_grade'][$grade] = ($stats['by_grade'][$grade] ?? 0) + 1;

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
            ss.*,
            sn.name AS school_name,
            b.name AS bank_name,
            c.short_name AS country_name,
            s.active as staff_active,
            s.staffid as staff_id
        ', false);

        $this->db->from($this->tbl_students . ' ss');
        $this->db->join($this->tbl_sname . ' sn', 'sn.id = ss.school_name_id', 'left');
        $this->db->join($this->tbl_bank . ' b', 'b.id = ss.bank_id', 'left');
        $this->db->join(db_prefix() . 'countries c', 'c.country_id = ss.country_id', 'left');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = ss.staff_id', 'left');

        // Apply filters
        $this->apply_filters($filters);

        // Handle DataTables search
        if (!empty($datatables_params['search']['value'])) {
            $search = $datatables_params['search']['value'];
            $this->db->group_start();
            $this->db->like('ss.name', $search);
            $this->db->or_like('ss.email', $search);
            $this->db->or_like('ss.contact_no', $search);
            $this->db->or_like('ss.school_internal_id', $search);
            $this->db->or_like('sn.name', $search);
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
                $columns = ['ss.id', 'ss.name', 'ss.school_grade', 'sn.name', 'status', 'ss.contact_no'];
                if (isset($columns[$column_index])) {
                    if ($columns[$column_index] !== 'status') {
                        $this->db->order_by($columns[$column_index], $direction);
                    }
                }
            }
        } else {
            $this->db->order_by('ss.id', 'DESC');
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

        // Sponsorship status
        if (!empty($filters['sponsorship_status'])) {
            if ($filters['sponsorship_status'] === 'sponsored') {
                $this->db->where('ss.sponsor_id IS NOT NULL');
            } elseif ($filters['sponsorship_status'] === 'unsponsored') {
                $this->db->where('ss.sponsor_id IS NULL');
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
            'grades' => $this->get_available_grades(),
            'schools' => $this->get_schools(),
            'cities' => $this->get_available_cities(),
            'countries' => $this->get_countries(),
            'banks' => $this->get_banks()
        ];
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

    /* ----------------- VALIDATION ----------------- */

    public function validate_age_grade($grade, $age, $grade_mismatch_reason = null)
    {
        if (empty($grade) || $age === null || $age === '') {
            return ['valid' => true];
        }

        $grade_str = (string)$grade;
        $age = (int)$age;

        // No validation for grades above 10 (O/L and A/L students)
        if (in_array($grade_str, ['O/L', 'A/L1', 'A/L2'], true)) {
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
            $grade = $data['grade'] ?? $data['school_grade'] ?? null;
            $age = $data['calculated_age'] ?? $data['school_age'] ?? null;
            $grade_mismatch_reason = $data['grade_mismatch_reason'] ?? null;
            
            if (!empty($grade) && !empty($age)) {
                $validation = $this->validate_age_grade($grade, $age, $grade_mismatch_reason);
                if (!$validation['valid']) {
                    return ['success' => false, 'message' => $validation['message']];
                }
            }

            $photo = null;
            try { 
                $photo = $this->handle_profile_photo_upload(); 
            } catch (Exception $e) { 
                log_message('error','School photo upload: '.$e->getMessage()); 
            }

            $country_id     = $this->toIntOrNull($data['country_id'] ?? null);
            $school_name_id = $this->get_or_create_school_name_id($data['school_name'] ?? ($data['school_name_id'] ?? ''));

            $insert = [
                'school_internal_id'            => $this->generate_internal_id(),
                'name'                          => $data['name'] ?? '',
                'profile_photo'                 => $photo,
                'contact_no'                    => $this->toNullIfEmpty($data['phone'] ?? ''),
                'email'                         => $this->toNullIfEmpty($data['email'] ?? ''),
                'address'                       => $this->toNullIfEmpty($data['address'] ?? ''),
                'city'                          => $this->toNullIfEmpty($data['city'] ?? ''),
                'zip'                           => $this->toNullIfEmpty($data['postal_code'] ?? ''),
                'country_id'                    => $country_id,
                'school_id'                     => $this->toNullIfEmpty($data['school_id'] ?? ''),
                'school_type'                   => $this->toNullIfEmpty($data['school_type'] ?? ''),
                'school_name_id'                => $school_name_id,
                'school_grade_year'             => $this->toIntOrNull($data['school_grade_year'] ?? ''),
                'school_grade'                  => $this->toNullIfEmpty($data['grade'] ?? ''),
                'grade_mismatch_reason'         => $this->toNullIfEmpty($grade_mismatch_reason),
                'school_student_dob'            => $this->toNullIfEmpty($data['dob'] ?? ''),
                'school_age'                    => $this->calc_age_int($data['dob'] ?? null),
                'bank_id'                       => $this->toIntOrNull($data['bank_id'] ?? ''),
                'school_bank_branch_number'     => $this->toNullIfEmpty($data['bank_branch_number'] ?? ''),
                'school_bank_branch_info'       => $this->toNullIfEmpty($data['bank_branch_info'] ?? ''),
                'school_bank_account_no'        => $this->toNullIfEmpty($data['bank_account_number'] ?? ''),
                'school_sponsorship_start_date' => $this->toNullIfEmpty($data['sponsorship_start'] ?? ''),
                'school_sponsorship_end_date'   => $this->toNullIfEmpty($data['sponsorship_end'] ?? ''),
                'school_introducedby'           => $this->toNullIfEmpty($data['introduced_by'] ?? ''),
                'school_introducedph'           => $this->toNullIfEmpty($data['introduced_phone'] ?? ''),
                'school_father_name'            => $this->toNullIfEmpty($data['father_name'] ?? ''),
                'school_mother_name'            => $this->toNullIfEmpty($data['mother_name'] ?? ''),
                'school_guardian_name'          => $this->toNullIfEmpty($data['guardian_name'] ?? ''),
                'school_father_income'          => $this->toFloatOrNull($data['father_income'] ?? null),
                'school_mother_income'          => $this->toFloatOrNull($data['mother_income'] ?? null),
                'school_guardian_income'        => $this->toFloatOrNull($data['guardian_income'] ?? null),
                'sponsor_id'                    => $this->toIntOrNull($data['sponsor_id'] ?? ''),
                'background_info'               => $this->toNullIfEmpty($data['background_information'] ?? ''),
                'internal_comment'              => $this->toNullIfEmpty($data['internal_comment'] ?? ''),
                'external_comment'              => $this->toNullIfEmpty($data['external_comment'] ?? ''),
                'created_at'                    => date('Y-m-d H:i:s')
            ];

            $insert = $this->filter_existing_columns($this->tbl_students, $insert);

            $this->db->insert($this->tbl_students, $insert);
            $id = (int)$this->db->insert_id();
            return $id ?: false;

        } catch (Exception $e) {
            log_message('error','Error adding school student: '.$e->getMessage());
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

            $update_data = [];
            try {
                $photo = $this->handle_profile_photo_upload();
                if ($photo !== null) {
                    $update_data['profile_photo'] = $photo;
                }
            } catch (Exception $e) {
                log_message('error', 'School_model::update - Photo upload error: ' . $e->getMessage());
            }

            $field_mappings = [
                'name' => 'name',
                'email' => 'email',
                'contact_no' => 'contact_no',
                'address' => 'address',
                'city' => 'city',
                'zip' => 'zip',
                'country_id' => 'country_id',
                'school_id' => 'school_id',
                'school_internal_id' => 'school_internal_id',
                'school_type' => 'school_type',
                'school_name_id' => 'school_name_id',
                'school_grade' => 'school_grade',
                'grade_mismatch_reason' => 'grade_mismatch_reason',
                'school_student_dob' => 'school_student_dob',
                'school_age' => 'school_age',
                'bank_id' => 'bank_id',
                'school_bank_account_no' => 'school_bank_account_no',
                'school_bank_branch_number' => 'school_bank_branch_number',
                'school_bank_branch_info' => 'school_bank_branch_info',
                'school_sponsorship_start_date' => 'school_sponsorship_start_date',
                'school_sponsorship_end_date' => 'school_sponsorship_end_date',
                'school_introducedby' => 'school_introducedby',
                'school_introducedph' => 'school_introducedph',
                'school_father_name' => 'school_father_name',
                'school_mother_name' => 'school_mother_name',
                'school_guardian_name' => 'school_guardian_name',
                'school_father_income' => 'school_father_income',
                'school_mother_income' => 'school_mother_income',
                'school_guardian_income' => 'school_guardian_income',
                'background_info' => 'background_info',
                'internal_comment' => 'internal_comment',
                'external_comment' => 'external_comment'
            ];

            foreach ($field_mappings as $db_field => $target_field) {
                if (array_key_exists($db_field, $data)) {
                    $value = $data[$db_field];
                    
                    if (in_array($db_field, ['country_id', 'bank_id', 'school_name_id'], true)) {
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
                    } elseif (in_array($db_field, ['school_father_income', 'school_mother_income', 'school_guardian_income', 'school_age'], true)) {
                        $update_data[$target_field] = ($value === '' || $value === null) ? null : (is_numeric($value) ? (float)$value : null);
                    } else {
                        $update_data[$target_field] = ($value === '' || $value === null) ? null : $value;
                    }
                }
            }

            if (!empty($update_data['school_student_dob']) && !isset($update_data['school_age'])) {
                try {
                    $dob = new DateTime($update_data['school_student_dob']);
                    $now = new DateTime();
                    $calculated_age = $dob->diff($now)->y;
                    $update_data['school_age'] = $calculated_age;
                } catch (Exception $e) {
                    log_message('error', 'School_model::update - Error calculating age: ' . $e->getMessage());
                }
            }

            $update_data = $this->filter_existing_columns($this->tbl_students, $update_data);

            if (empty($update_data)) {
                return true;
            }

            $this->db->where('id', $id);
            $result = $this->db->update($this->tbl_students, $update_data);

            if (!$result) {
                $error = $this->db->error();
                log_message('error', 'School_model::update - Database update failed: ' . json_encode($error));
                return false;
            }

            return true;

        } catch (Exception $e) {
            log_message('error', 'School_model::update - Exception: ' . $e->getMessage());
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

    /* ----------------- READ OPERATIONS ----------------- */

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
        return $this->db->get()->result_array();
    }

    public function get_by_id($id)
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
        $this->db->where('ss.id', (int)$id);
        return $this->db->get()->row_array();
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
                
            default:
                return true;
        }
    }

    private function country_schema()
    {
        if ($this->db->table_exists(db_prefix().'countries')) {
            return ['table'=>db_prefix().'countries','id'=>'country_id','name'=>'short_name'];
        }
        if ($this->db->table_exists(db_prefix().'country')) {
            return ['table'=>db_prefix().'country','id'=>'id','name'=>'name'];
        }
        return ['table'=>null,'id'=>null,'name'=>null];
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

    // Maintain compatibility with existing methods
    public function update_student($data, $id) { return $this->update($data, $id); }
    public function delete_student($id) { return $this->delete($id); }
}