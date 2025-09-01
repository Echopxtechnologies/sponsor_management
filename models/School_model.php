<?php
defined('BASEPATH') or exit('No direct script access allowed');

class School_model extends App_Model
{
    private $tbl_students = 'tblschool_students';
    private $tbl_sname    = 'tblschool_name';
    private $tbl_bank     = 'tblbank';
    private $tbl_rcard    = 'tblschool_report_card';

    public function __construct()
    {
        parent::__construct();
        $this->ensure_required_tables();
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
            // Make blob/path columns NULL-able so either storage strategy works.
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

        // Soft-align existing schema (non-destructive).
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

    /* ----------------- PHOTO ----------------- */

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

    /* ----------------- CRUD ----------------- */

    public function add($data)
    {
        try {
            $photo = null;
            try { $photo = $this->handle_profile_photo_upload(); } catch (Exception $e) { log_message('error','School photo upload: '.$e->getMessage()); }

            $country_id     = $this->toIntOrNull($data['country_id'] ?? null);
            $school_name_id = $this->get_or_create_school_name_id($data['school_name'] ?? ($data['school_name_id'] ?? ''));

            // Build insert, then filter to existing columns (prevents unknown-column crashes).
            $insert = [
                // identification
                'school_internal_id'            => $this->generate_internal_id(),
                'name'                          => $data['name'] ?? '',
                'profile_photo'                 => $photo,

                // contacts
                'contact_no'                    => $this->toNullIfEmpty($data['phone'] ?? ''),
                'email'                         => $this->toNullIfEmpty($data['email'] ?? ''),
                'address'                       => $this->toNullIfEmpty($data['address'] ?? ''),
                'city'                          => $this->toNullIfEmpty($data['city'] ?? ''),
                'zip'                           => $this->toNullIfEmpty($data['postal_code'] ?? ''),
                'country_id'                    => $country_id,

                // school info
                'school_id'                     => $this->toNullIfEmpty($data['school_id'] ?? ''),
                'school_type'                   => $this->toNullIfEmpty($data['school_type'] ?? ''),
                'school_name_id'                => $school_name_id,

                // academic
                'school_grade_year'             => $this->toIntOrNull($data['school_grade_year'] ?? ''),
                'school_grade'                  => $this->toNullIfEmpty($data['grade'] ?? ''),
                'grade_mismatch_reason'         => $this->toNullIfEmpty($data['grade_mismatch_reason'] ?? ''),

                // personal
                'school_student_dob'            => $this->toNullIfEmpty($data['dob'] ?? ''),
                'school_age'                    => $this->calc_age_int($data['dob'] ?? null),

                // bank
                'bank_id'                       => $this->toIntOrNull($data['bank_id'] ?? ''),
                'school_bank_branch_number'     => $this->toNullIfEmpty($data['bank_branch_number'] ?? ''),
                'school_bank_branch_info'       => $this->toNullIfEmpty($data['bank_branch_info'] ?? ''),
                'school_bank_account_no'        => $this->toNullIfEmpty($data['bank_account_number'] ?? ''),

                // sponsorship
                'school_sponsorship_start_date' => $this->toNullIfEmpty($data['sponsorship_start'] ?? ''),
                'school_sponsorship_end_date'   => $this->toNullIfEmpty($data['sponsorship_end'] ?? ''),

                // intro
                'school_introducedby'           => $this->toNullIfEmpty($data['introduced_by'] ?? ''),
                'school_introducedph'           => $this->toNullIfEmpty($data['introduced_phone'] ?? ''),

                // family/income
                'school_father_name'            => $this->toNullIfEmpty($data['father_name'] ?? ''),
                'school_mother_name'            => $this->toNullIfEmpty($data['mother_name'] ?? ''),
                'school_guardian_name'          => $this->toNullIfEmpty($data['guardian_name'] ?? ''),
                'school_father_income'          => $this->toFloatOrNull($data['father_income'] ?? null),
                'school_mother_income'          => $this->toFloatOrNull($data['mother_income'] ?? null),
                'school_guardian_income'        => $this->toFloatOrNull($data['guardian_income'] ?? null),

                // misc
                'sponsor_id'                    => $this->toIntOrNull($data['sponsor_id'] ?? ''),
                'background_info'               => $this->toNullIfEmpty($data['background_information'] ?? ''),
                'internal_comment'              => $this->toNullIfEmpty($data['internal_comment'] ?? ''),
                'external_comment'              => $this->toNullIfEmpty($data['external_comment'] ?? ''),
            ];

            $insert = $this->filter_existing_columns($this->tbl_students, $insert);

            $this->db->insert($this->tbl_students, $insert);
            $id = (int)$this->db->insert_id();
            return $id ?: false;

        } catch (Exception $e) {
            log_message('error','Error adding school student: '.$e->getMessage());
            return false;
        }
    }

    public function update($data, $id)
    {
        try {
            $id = (int)$id;
            if ($id <= 0) return false;

            // Start building the update array - similar to add() but for existing record
            $update_data = [];

            // Handle profile photo if uploaded
            try {
                $photo = $this->handle_profile_photo_upload();
                if ($photo !== null) {
                    $update_data['profile_photo'] = $photo;
                }
            } catch (Exception $e) {
                log_message('error','School photo upload (update): '.$e->getMessage());
            }

            // Map the same fields as in add(), using the cleaned data from controller
            $field_mappings = [
                // Basic info
                'name' => 'name',
                'email' => 'email',
                'contact_no' => 'contact_no',
                'address' => 'address',
                'city' => 'city',
                'zip' => 'zip',
                'country_id' => 'country_id',
                
                // School info
                'school_id' => 'school_id',
                'school_internal_id' => 'school_internal_id',
                'school_type' => 'school_type',
                'school_name_id' => 'school_name_id',
                'school_grade' => 'school_grade',
                'school_student_dob' => 'school_student_dob',
                'school_age' => 'school_age',
                
                // Bank info
                'bank_id' => 'bank_id',
                'school_bank_account_no' => 'school_bank_account_no',
                'school_bank_branch_number' => 'school_bank_branch_number',
                'school_bank_branch_info' => 'school_bank_branch_info',
                
                // Sponsorship
                'school_sponsorship_start_date' => 'school_sponsorship_start_date',
                'school_sponsorship_end_date' => 'school_sponsorship_end_date',
                'school_introducedby' => 'school_introducedby',
                'school_introducedph' => 'school_introducedph',
                
                // Family
                'school_father_name' => 'school_father_name',
                'school_mother_name' => 'school_mother_name',
                'school_guardian_name' => 'school_guardian_name',
                'school_father_income' => 'school_father_income',
                'school_mother_income' => 'school_mother_income',
                'school_guardian_income' => 'school_guardian_income',
                
                // Comments
                'background_info' => 'background_info',
                'internal_comment' => 'internal_comment',
                'external_comment' => 'external_comment'
            ];

            // Apply the cleaned data with proper NULL handling for foreign keys
            foreach ($field_mappings as $db_field => $target_field) {
                if (array_key_exists($db_field, $data)) {
                    $value = $data[$db_field];
                    
                    // Handle foreign key fields - convert empty strings to NULL and validate existence
                    if (in_array($db_field, ['country_id', 'bank_id', 'school_name_id'], true)) {
                        if ($value === '' || $value === null) {
                            $update_data[$target_field] = null;
                        } else {
                            $fk_id = (int)$value;
                            // Validate foreign key exists
                            if ($this->validateForeignKey($db_field, $fk_id)) {
                                $update_data[$target_field] = $fk_id;
                            } else {
                                log_message('warning', 'Invalid foreign key: '.$db_field.' = '.$fk_id.' does not exist');
                                $update_data[$target_field] = null; // Set to NULL if invalid
                            }
                        }
                    }
                    // Handle numeric fields
                    elseif (in_array($db_field, ['school_father_income', 'school_mother_income', 'school_guardian_income', 'school_age'], true)) {
                        $update_data[$target_field] = ($value === '' || $value === null) ? null : (is_numeric($value) ? (float)$value : null);
                    }
                    // Handle all other fields
                    else {
                        $update_data[$target_field] = ($value === '' || $value === null) ? null : $value;
                    }
                }
            }

            // Filter to only existing database columns
            $update_data = $this->filter_existing_columns($this->tbl_students, $update_data);

            // Log what we're updating
            log_message('debug', 'School_model::update for ID '.$id.' with data: ' . json_encode($update_data));

            if (empty($update_data)) {
                log_message('debug', 'School_model::update - no data to update for ID: ' . $id);
                return true; // Nothing to update
            }

            // Perform the update
            $this->db->where('id', $id);
            $result = $this->db->update($this->tbl_students, $update_data);

            if (!$result) {
                $error = $this->db->error();
                log_message('error', 'School_model::update failed for ID '.$id.': '.json_encode($error));
                return false;
            }

            log_message('debug', 'School_model::update successful for ID '.$id.' - affected rows: '.$this->db->affected_rows());
            return true;

        } catch (Exception $e) {
            log_message('error','Error updating school student: '.$e->getMessage());
            return false;
        }
    }

    public function update_student($data, $id) { return $this->update($data, $id); }

    public function delete($id)
    {
        try {
            // delete report cards (and files)
            $cards = $this->db->where('student_school_id', (int)$id)->get($this->tbl_rcard)->result();
            foreach ($cards as $c) {
                if (!empty($c->report_card_file)) {
                    $abs = rtrim(FCPATH, '/\\') . '/' . ltrim($c->report_card_file, '/');
                    if (is_file($abs)) @unlink($abs);
                }
            }
            $this->db->where('student_school_id', (int)$id)->delete($this->tbl_rcard);

            // delete student
            $this->db->where('id', (int)$id)->delete($this->tbl_students);
            return $this->db->affected_rows() > 0;

        } catch (Exception $e) {
            log_message('error','Error deleting school student: '.$e->getMessage());
            return false;
        }
    }

    public function delete_student($id) { return $this->delete($id); }

    /* ----------------- READ/LISTS ----------------- */

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

    public function get_students_filtered($filters = [])
    {
        $this->db->select('ss.*, sn.name as school_name');
        $this->db->from($this->tbl_students.' ss');
        $this->db->join($this->tbl_sname.' sn', 'sn.id = ss.school_name_id', 'left');

        if (!empty($filters['grade']))       $this->db->where('ss.school_grade', $filters['grade']);
        if (!empty($filters['city']))        $this->db->like('ss.city', $filters['city']);
        if (!empty($filters['school_name'])) $this->db->like('sn.name', $filters['school_name']);

        if (!empty($filters['search'])) {
            $this->db->group_start();
            $this->db->like('ss.name', $filters['search']);
            $this->db->or_like('ss.email', $filters['search']);
            $this->db->or_like('ss.contact_no', $filters['search']);
            $this->db->group_end();
        }

        $this->db->order_by('ss.id', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_schools()
    {
        return $this->db->order_by('name','ASC')->get($this->tbl_sname)->result_array();
    }

    /* ----------------- REPORT CARDS (school) - FIXED TO MATCH UNIVERSITY VERSION ----------------- */

    public function get_report_cards($student_id)
    {
        $tbl = db_prefix().'school_report_card';
        
        // Select all fields except BLOB data for listing
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
            // Format upload date consistently
            $c['upload_date'] = !empty($c['upload_date'])
                ? date('M d, Y', strtotime($c['upload_date']))
                : '';

            // Determine display name (same logic as university)
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

            // Add download URL
            $c['file_url'] = admin_url('student_sponsor_portal/download_school_report_card/'.$c['id']);
            $c['download_url'] = $c['file_url']; // Add both for compatibility
        }
        unset($c);

        return ['success' => true, 'report_cards' => $rows];
    }

    /* ----------------- HELPERS ----------------- */

    private function validateForeignKey($field, $id)
    {
        if (!$id || $id <= 0) return false;
        
        switch ($field) {
            case 'country_id':
                $c = $this->country_schema();
                if (!$c['table']) return true; // No country table, skip validation
                return $this->db->where($c['id'], $id)->count_all_results($c['table']) > 0;
                
            case 'bank_id':
                return $this->db->where('id', $id)->count_all_results($this->tbl_bank) > 0;
                
            case 'school_name_id':
                return $this->db->where('id', $id)->count_all_results($this->tbl_sname) > 0;
                
            default:
                return true; // Unknown field, assume valid
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
        // SCH001, SCH002, ...
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
}