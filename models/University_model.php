<?php
defined('BASEPATH') or exit('No direct script access allowed');

class University_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ensure_required_tables();
    }

    /* ----------------- TABLE CHECK HELPERS (no seeding) ----------------- */
    
    private function ensure_required_tables()
    {
        $this->ensure_university_name_table();
        $this->ensure_university_program_table();
        $this->ensure_university_report_card_table();
        // do NOT create/seed tblcountries (Perfex core)
        $this->ensure_bank_table();
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
                    `upload_date` date NOT NULL,
                    `report_card_term` enum('1Y1S','1Y2S','2Y1S','2Y2S','3Y1S','3Y2S','4Y1S','4Y2S','5Y1S','5Y2S') NOT NULL,
                    `current_term` enum('1Y1S','1Y2S','2Y1S','2Y2S','3Y1S','3Y2S','4Y1S','4Y2S','5Y1S','5Y2S') NOT NULL,
                    `semester_end_month` tinyint(3) unsigned NOT NULL,
                    `semester_end_year` year(4) NOT NULL,
                    `file_blob` mediumblob NOT NULL,
                    `mime_type` varchar(100) NOT NULL,
                    `file_size` int(10) unsigned NOT NULL,
                    `sha256` char(64) NOT NULL,
                    `filename` varchar(255) NULL,
                    `report_card_file` varchar(255) NULL,
                    `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `student_university_id` (`student_university_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            return;
        }

        // Add/adjust columns if the table already exists
        if (!$this->db->field_exists('file_blob', $table)) {
            $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `file_blob` mediumblob NOT NULL AFTER `semester_end_year`");
        }
        if (!$this->db->field_exists('mime_type', $table)) {
            $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `mime_type` varchar(100) NOT NULL AFTER `file_blob`");
        }
        if (!$this->db->field_exists('file_size', $table)) {
            $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `file_size` int(10) unsigned NOT NULL AFTER `mime_type`");
        }
        if (!$this->db->field_exists('sha256', $table)) {
            $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `sha256` char(64) NOT NULL AFTER `file_size`");
        }
        if ($this->db->field_exists('filename', $table)) {
            $this->db->query("ALTER TABLE `{$table}` MODIFY `filename` varchar(255) NULL");
        } else {
            $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `filename` varchar(255) NULL AFTER `sha256`");
        }
        if ($this->db->field_exists('report_card_file', $table)) {
            $this->db->query("ALTER TABLE `{$table}` MODIFY `report_card_file` varchar(255) NULL");
        } else {
            $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `report_card_file` varchar(255) NULL AFTER `filename`");
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

    /* ----------------- PROFILE PHOTO HELPERS ----------------- */
    
    private function handle_profile_photo_upload()
    {
        if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        $file = $_FILES['profile_photo'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Invalid file type. Only JPG, PNG, and GIF images are allowed.');
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum 5MB allowed.');
        }
        return file_get_contents($file['tmp_name']);
    }
    
    public function get_profile_photo($student_id)
    {
        $this->db->select('profile_photo');
        $this->db->where('id', (int)$student_id);
        $result = $this->db->get(db_prefix() . 'university_students')->row();
        return $result ? $result->profile_photo : null;
    }

    /* ----------------- HANDLE NEW ITEMS ----------------- */
    
    private function process_new_items($data)
    {
        // New Country -> insert into tblcountries (short_name, calling_code)
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
            // Perfex core table
            $tbl = db_prefix().'countries';
            $payload = [
                'short_name'   => $name,
                'calling_code' => $phone_code,
                // leave other columns NULL
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

    /* ----------------- AJAX HELPERS (model-level) ----------------- */

    public function add_country_ajax($country_name, $phone_code)
    {
        try {
            $country_name = trim($country_name);
            $phone_code   = trim($phone_code);
            if ($country_name === '' || $phone_code === '') {
                return ['success' => false, 'message' => 'Country name and phone code are required'];
            }

            $tbl = db_prefix().'countries';
            $this->db->where('short_name', $country_name);
            $existing = $this->db->get($tbl)->row();
            if ($existing) {
                return [
                    'success'      => true,
                    'message'      => 'Country already exists',
                    'country_id'   => (int)$existing->country_id,
                    'country_name' => (string)$existing->short_name,
                    'phone_code'   => (string)$existing->calling_code
                ];
            }

            $this->db->insert($tbl, [
                'short_name'   => $country_name,
                'calling_code' => $phone_code,
            ]);
            $id = (int)$this->db->insert_id();

            return $id ? [
                'success'      => true,
                'message'      => 'Country added successfully',
                'country_id'   => $id,
                'country_name' => $country_name,
                'phone_code'   => $phone_code
            ] : ['success'=>false,'message'=>'Failed to add country'];

        } catch (Exception $e) {
            log_message('error', 'Error adding country: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function add_university_ajax($university_name)
    {
        try {
            $university_name = trim($university_name);
            if ($university_name === '') return ['success'=>false,'message'=>'University name is required'];

            $tbl = db_prefix().'university_name';
            $this->db->where('name', $university_name);
            $existing = $this->db->get($tbl)->row();
            if ($existing) {
                return ['success'=>true,'message'=>'University already exists','university_id'=>(int)$existing->id,'university_name'=>$existing->name];
            }
            $this->db->insert($tbl, ['name'=>$university_name]);
            $id = (int)$this->db->insert_id();
            return $id ? ['success'=>true,'message'=>'University added successfully','university_id'=>$id,'university_name'=>$university_name]
                       : ['success'=>false,'message'=>'Failed to add university'];
        } catch (Exception $e) {
            log_message('error', 'Error adding university: ' . $e->getMessage());
            return ['success'=>false,'message'=>'Database error occurred'];
        }
    }

    public function add_program_ajax($program_name)
    {
        try {
            $program_name = trim($program_name);
            if ($program_name === '') return ['success'=>false,'message'=>'Program name is required'];

            $tbl = db_prefix().'university_program';
            $this->db->where('name', $program_name);
            $existing = $this->db->get($tbl)->row();
            if ($existing) {
                return ['success'=>true,'message'=>'Program already exists','program_id'=>(int)$existing->id,'program_name'=>$existing->name];
            }
            $this->db->insert($tbl, ['name'=>$program_name]);
            $id = (int)$this->db->insert_id();
            return $id ? ['success'=>true,'message'=>'Program added successfully','program_id'=>$id,'program_name'=>$program_name]
                       : ['success'=>false,'message'=>'Failed to add program'];
        } catch (Exception $e) {
            log_message('error', 'Error adding program: ' . $e->getMessage());
            return ['success'=>false,'message'=>'Database error occurred'];
        }
    }

    public function add_bank_ajax($bank_name)
    {
        try {
            $bank_name = trim($bank_name);
            if ($bank_name === '') return ['success'=>false,'message'=>'Bank name is required'];

            $tbl = db_prefix().'bank';
            $this->db->where('name', $bank_name);
            $existing = $this->db->get($tbl)->row();
            if ($existing) {
                return ['success'=>true,'message'=>'Bank already exists','bank_id'=>(int)$existing->id,'bank_name'=>$existing->name];
            }
            $this->db->insert($tbl, ['name'=>$bank_name]);
            $id = (int)$this->db->insert_id();
            return $id ? ['success'=>true,'message'=>'Bank added successfully','bank_id'=>$id,'bank_name'=>$bank_name]
                       : ['success'=>false,'message'=>'Failed to add bank'];
        } catch (Exception $e) {
            log_message('error', 'Error adding bank: ' . $e->getMessage());
            return ['success'=>false,'message'=>'Database error occurred'];
        }
    }

    /* ----------------- CREATE ----------------- */
    public function add($data)
    {
        try {
            $data = $this->process_new_items($data);

            $profile_photo_data = null;
            try { $profile_photo_data = $this->handle_profile_photo_upload(); }
            catch (Exception $e) { log_message('error', 'Profile photo upload error: ' . $e->getMessage()); }

            $university_name_id    = $this->get_or_create_university_name_id($data['university_name'] ?? ($data['university_name_id'] ?? ''));
            $university_program_id = $this->get_or_create_university_program_id($data['program'] ?? ($data['university_program_id'] ?? ''));
            $bank_id               = $this->get_or_create_bank_id($data['bank_name'] ?? ($data['bank_id'] ?? ''));

            // Country: prefer provided ID, else leave null/1
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
            ];

            $this->db->insert(db_prefix() . 'university_students', $insert);
            $student_id = (int)$this->db->insert_id();

            if ($student_id) {
                if (class_exists('Clients_model')) {
                    $this->load->model('clients_model');
                    $this->clients_model->add([
                        'company'       => ($data['name'] ?? '') . ' (University Student)',
                        'firstname'     => $data['name'] ?? '',
                        'email'         => $data['email'] ?? '',
                        'phonenumber'   => $data['phone'] ?? '',
                        'address'       => $data['address'] ?? '',
                        'city'          => $data['city'] ?? '',
                        'zip'           => $data['postal_code'] ?? '',
                        'country'       => $country_id,
                        'groups_in'     => [],
                        'custom_fields' => []
                    ]);
                }
                return $student_id;
            }

            return false;

        } catch (Exception $e) {
            log_message('error', 'Error adding university student: ' . $e->getMessage());
            return false;
        }
    }

    /* ----------------- READ ----------------- */
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

    /* ----------------- UPDATE ----------------- */
    public function update_student($data, $id)
    {
        try {
            $data = $this->process_new_items($data);

            $profile_photo_data = null;
            try { $profile_photo_data = $this->handle_profile_photo_upload(); }
            catch (Exception $e) { log_message('error', 'Profile photo upload error during update: ' . $e->getMessage()); }

            $update_data = [];

            $field_mapping = [
                'name' => 'name',
                'email' => 'email',
                'phone' => 'contact_no',
                'address' => 'address',
                'city' => 'city',
                'postal_code' => 'zip',
                'country_id' => 'country_id',
                'university_id' => 'university_id',
                'dob' => 'university_student_dob',
                'year_of_study' => 'university_year_of_study',
                'father_name' => 'university_father_name',
                'mother_name' => 'university_mother_name',
                'father_income' => 'university_father_income',
                'mother_income' => 'university_mother_income',
                'guardian_name' => 'university_guardian_name',
                'guardian_income' => 'university_guardian_income',
                'sponsorship_start' => 'university_sponsorship_start_date',
                'sponsorship_end' => 'university_sponsorship_end_date',
                'introduced_by' => 'university_introducedby',
                'introduced_phone' => 'university_introducedph',
                'bank_branch_number' => 'university_bank_branch_number',
                'bank_branch_info' => 'university_bank_branch_info',
                'bank_account_number' => 'university_bank_account_no',
                'sponsor_id' => 'sponsor_id',
                'background_information' => 'background_info',
                'internal_comment' => 'internal_comment',
                'external_comment' => 'external_comment'
            ];

            foreach ($field_mapping as $form_field => $db_field) {
                if (array_key_exists($form_field, $data)) {
                    $value = $data[$form_field];
                    if (in_array($form_field, ['father_income','mother_income','guardian_income'])) {
                        $update_data[$db_field] = $this->toFloatOrNull($value);
                    } elseif ($form_field === 'sponsor_id') {
                        $update_data[$db_field] = $this->toIntOrNull($value);
                    } elseif ($form_field === 'year_of_study') {
                        $update_data[$db_field] = $this->sanitize_year($value);
                    } else {
                        $update_data[$db_field] = $this->toNullIfEmpty($value);
                    }
                }
            }

            if (isset($data['university_name_id']) && $data['university_name_id'] !== '__NEW_UNIVERSITY__' && $data['university_name_id'] !== '') {
                $update_data['university_name_id'] = (int)$data['university_name_id'];
            }
            if (isset($data['university_program_id']) && $data['university_program_id'] !== '__NEW_PROGRAM__' && $data['university_program_id'] !== '') {
                $update_data['university_program_id'] = (int)$data['university_program_id'];
            }
            if (isset($data['bank_id']) && $data['bank_id'] !== '__NEW_BANK__' && $data['bank_id'] !== '') {
                $update_data['bank_id'] = (int)$data['bank_id'];
            }
            if (isset($data['country_id']) && $data['country_id'] !== '__NEW_COUNTRY__' && $data['country_id'] !== '') {
                $update_data['country_id'] = (int)$data['country_id'];
            }

            if ($profile_photo_data !== null) {
                $update_data['profile_photo'] = $profile_photo_data;
            }

            if (isset($update_data['university_student_dob']) && !empty($update_data['university_student_dob'])) {
                $update_data['university_age'] = $this->calc_age($update_data['university_student_dob']);
            }

            $this->db->where('id', (int)$id);
            $this->db->update(db_prefix().'university_students', $update_data);
            return $this->db->affected_rows() >= 0;

        } catch (Exception $e) {
            log_message('error', 'Error updating university student: ' . $e->getMessage());
            return false;
        }
    }

    /* ----------------- DELETE ----------------- */
    public function delete($id)
    {
        try {
            // Delete related report cards first
            $this->db->where('student_university_id', (int)$id);
            $report_cards = $this->db->get(db_prefix() . 'university_report_card')->result();

            foreach ($report_cards as $card) {
                if (!empty($card->report_card_file) && file_exists($card->report_card_file)) {
                    @unlink($card->report_card_file);
                }
            }

            $this->db->where('student_university_id', (int)$id);
            $this->db->delete(db_prefix() . 'university_report_card');

            // Delete student record
            $this->db->where('id', (int)$id);
            $this->db->delete(db_prefix() . 'university_students');

            return $this->db->affected_rows() > 0;
        } catch (Exception $e) {
            log_message('error', 'Error deleting university student: ' . $e->getMessage());
            return false;
        }
    }

    /* ----------------- REPORT CARD METHODS (match your table) ----------------- */
    
    public function upload_report_card($data)
    {
        try {
            if (!isset($_FILES['report_card_file']) || $_FILES['report_card_file']['error'] !== UPLOAD_ERR_OK) {
                return ['success' => false, 'message' => 'No file uploaded or upload error occurred'];
            }

            $file = $_FILES['report_card_file'];

            // Validate type & size
            $allowed_types = [
                'application/pdf',
                'image/jpeg', 'image/jpg',
                'image/png'
            ];
            // Prefer server-detected mime
            $finfo     = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime_type, $allowed_types, true)) {
                return ['success' => false, 'message' => 'Invalid file type. Only PDF/JPG/PNG allowed.'];
            }
            if ($file['size'] > 10 * 1024 * 1024) { // 10MB (MEDIUMBLOB can handle up to 16MB)
                return ['success' => false, 'message' => 'File too large (max 10MB).'];
            }

            // Read file into blob
            $blob    = file_get_contents($file['tmp_name']);
            $sha256  = hash('sha256', $blob);
            $nowDate = date('Y-m-d');
            $nowDT   = date('Y-m-d H:i:s');

            $insert_data = [
                'student_university_id' => (int)$data['student_university_id'],
                'upload_date'           => $nowDate,
                'report_card_term'      => $data['report_card_term'] ?? null,
                'current_term'          => $data['current_term'] ?? null,
                'semester_end_month'    => !empty($data['semester_end_month']) ? (int)$data['semester_end_month'] : null,
                'semester_end_year'     => !empty($data['semester_end_year']) ? (int)$data['semester_end_year'] : null,
                'file_blob'             => $blob,
                'mime_type'             => $mime_type,
                'file_size'             => (int)$file['size'],
                'sha256'                => $sha256,
                'created_on'            => $nowDT,
            ];

            $this->db->insert(db_prefix().'university_report_card', $insert_data);

            if ($this->db->affected_rows() > 0) {
                return ['success' => true, 'message' => 'Report card uploaded successfully'];
            }
            return ['success' => false, 'message' => 'Database error while saving report card'];

        } catch (Exception $e) {
            log_message('error', 'Report card upload error: '.$e->getMessage());
            return ['success' => false, 'message' => 'Upload failed: '.$e->getMessage()];
        }
    }

    
    public function get_report_cards($student_id)
    {
        try {
            $this->db->where('student_university_id', (int)$student_id);
            $this->db->order_by('upload_date', 'DESC');
            $cards = $this->db->get(db_prefix().'university_report_card')->result_array();

            foreach ($cards as &$c) {
                // You’ll implement this controller route to stream the blob by ID:
                $c['download_url'] = admin_url('student_sponsor_portal/download_report_card/'.$c['id']);
            }
            return ['success' => true, 'report_cards' => $cards];
        } catch (Exception $e) {
            log_message('error', 'Error getting report cards: '.$e->getMessage());
            return ['success' => false, 'message' => 'Error loading report cards'];
        }
    }

    public function get_report_card_file($report_card_id)
    {
        $this->db->where('id', (int)$report_card_id);
        $card = $this->db->get(db_prefix().'university_report_card')->row();
        if ($card && !empty($card->file_blob)) {
            // Build a friendly filename by term/month/year if available
            $basename = 'report_card_'.$card->id;
            if (!empty($card->report_card_term)) {
                $basename .= '_'.$card->report_card_term;
            }
            if (!empty($card->semester_end_month) && !empty($card->semester_end_year)) {
                $basename .= '_'.$card->semester_end_month.'-'.$card->semester_end_year;
            }

            // Extension hint (optional)
            $ext = '';
            if ($card->mime_type === 'application/pdf') $ext = '.pdf';
            elseif (in_array($card->mime_type, ['image/jpeg','image/jpg'], true)) $ext = '.jpg';
            elseif ($card->mime_type === 'image/png') $ext = '.png';

            return [
                'data'      => $card->file_blob,     // raw binary string
                'mime_type' => $card->mime_type,
                'size'      => (int)$card->file_size,
                'sha256'    => $card->sha256,
                'filename'  => $basename.$ext,
            ];
        }
        return null;
    }
    public function delete_report_card($report_card_id)
    {
        try {
            $this->db->where('id', (int)$report_card_id);
            $exists = $this->db->get(db_prefix().'university_report_card')->row();
            if (!$exists) {
                return ['success' => false, 'message' => 'Report card not found'];
            }
            $this->db->where('id', (int)$report_card_id);
            $this->db->delete(db_prefix().'university_report_card');
            return ['success' => true, 'message' => 'Report card deleted'];
        } catch (Exception $e) {
            log_message('error', 'Error deleting report card: '.$e->getMessage());
            return ['success' => false, 'message' => 'Error deleting report card'];
        }
    }
    public function download_report_card($id)
    {
        $this->load->model('university_model');
        $file = $this->university_model->get_report_card_file((int)$id);
        if (!$file) {
            show_404();
            return;
        }
        // Send headers and output the blob
        header('Content-Type: '.$file['mime_type']);
        header('Content-Length: '.$file['size']);
        header('Content-Disposition: inline; filename="'.str_replace('"','',$file['filename']).'"');
        // Optional: ETag
        if (!empty($file['sha256'])) {
            header('ETag: "'.$file['sha256'].'"');
        }
        echo $file['data'];
        exit;
    }


    /* ----------------- HELPERS ----------------- */

    private function country_schema()
    {
        if ($this->db->table_exists(db_prefix() . 'countries')) {
            // Perfex core table (your structure)
            return [
                'table'     => db_prefix() . 'countries',
                'id'        => 'country_id',
                'name'      => 'short_name',
                'phone'     => 'calling_code',
                'is_custom' => false,
            ];
        }
        // Optional custom fallback (not used if Perfex table exists)
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


    private function get_or_create_university_name_id($name_or_id)
    {
        if (!$name_or_id) return null;
        if (is_numeric($name_or_id)) return (int)$name_or_id;

        $this->db->where('name', $name_or_id);
        $row = $this->db->get(db_prefix() . 'university_name')->row();
        if ($row) return (int)$row->id;

        $this->db->insert(db_prefix() . 'university_name', ['name' => $name_or_id]);
        return (int)$this->db->insert_id();
    }

    private function get_or_create_university_program_id($name_or_id)
    {
        if (!$name_or_id) return null;
        if (is_numeric($name_or_id)) return (int)$name_or_id;

        $this->db->where('name', $name_or_id);
        $row = $this->db->get(db_prefix() . 'university_program')->row();
        if ($row) return (int)$row->id;

        $this->db->insert(db_prefix() . 'university_program', ['name' => $name_or_id]);
        return (int)$this->db->insert_id();
    }

    private function get_or_create_bank_id($name_or_id)
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
            // tblcountries -> alias to match your view expects id, name, phone_code
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
}
