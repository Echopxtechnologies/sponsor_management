<?php
defined('BASEPATH') or exit('No direct script access allowed');

class School_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ensure_required_tables();
    }

    /* ----------------- TABLE CHECK HELPERS (no seeding) ----------------- */

    private function ensure_required_tables()
    {
        $this->ensure_school_name_table();
        $this->ensure_school_report_card_table();
        $this->ensure_bank_table();
        $this->ensure_school_students_photo_column(); // make sure photo column is present/compatible
        // do NOT create/seed tblcountries (Perfex core or your custom tblcountry)
    }

    private function ensure_school_students_photo_column()
    {
        $table = db_prefix().'school_students';
        if (!$this->db->table_exists($table)) return;

        if (!$this->db->field_exists('profile_photo', $table)) {
            $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `profile_photo` LONGBLOB NULL AFTER `name`");
        } else {
            // align nullability/type (safe no-op if already correct)
            $this->db->query("ALTER TABLE `{$table}` MODIFY COLUMN `profile_photo` LONGBLOB NULL");
        }
    }

    private function ensure_school_name_table()
    {
        $table = db_prefix() . 'school_name';
        if (!$this->db->table_exists($table)) {
            // Match your dump: latin1_swedish_ci, DATETIME default current_timestamp(), no UNIQUE(name)
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `{$table}` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) COLLATE latin1_swedish_ci NOT NULL,
                    `created_on` datetime DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
            ");
        } else {
            // Align types/collation if table exists (safe no-ops if already correct)
            $this->db->query("ALTER TABLE `{$table}` MODIFY `name` varchar(255) COLLATE latin1_swedish_ci NOT NULL");
            $this->db->query("ALTER TABLE `{$table}` MODIFY `created_on` datetime DEFAULT current_timestamp()");
        }
    }

    private function ensure_school_report_card_table()
    {
        $table = db_prefix().'school_report_card';
        if (!$this->db->table_exists($table)) {
            // Match your dump exactly (latin1 cols, created_on datetime nullable, no default)
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `{$table}` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `student_school_id` int(11) NOT NULL,
                    `filename` varchar(255) COLLATE latin1_swedish_ci NOT NULL,
                    `term` enum('Term1','Term2','Term3') COLLATE latin1_swedish_ci NOT NULL,
                    `upload_date` date NOT NULL,
                    `report_card_file` varchar(255) COLLATE latin1_swedish_ci NOT NULL,
                    `file_blob` mediumblob NOT NULL,
                    `mime_type` varchar(100) COLLATE latin1_swedish_ci NOT NULL,
                    `file_size` int(10) UNSIGNED NOT NULL,
                    `sha256` char(64) COLLATE latin1_swedish_ci NOT NULL,
                    `created_on` datetime NULL,
                    PRIMARY KEY (`id`),
                    KEY `student_school_id` (`student_school_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
            ");
            return;
        }

        // If exists, ensure the important columns are present with proper nullability/collation (no destructive changes)
        if (!$this->db->field_exists('file_blob', $table)) {
            $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `file_blob` mediumblob NOT NULL AFTER `report_card_file`");
        }
        $ensure = [
            'mime_type' => "varchar(100) COLLATE latin1_swedish_ci NOT NULL",
            'file_size' => "int(10) UNSIGNED NOT NULL",
            'sha256'    => "char(64) COLLATE latin1_swedish_ci NOT NULL",
        ];
        foreach ($ensure as $col => $def) {
            if (!$this->db->field_exists($col, $table)) {
                $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$def}");
            } else {
                $this->db->query("ALTER TABLE `{$table}` MODIFY `{$col}` {$def}");
            }
        }

        // filename/report_card_file not null + latin1 collation
        $this->db->query("ALTER TABLE `{$table}` MODIFY `filename` varchar(255) COLLATE latin1_swedish_ci NOT NULL");
        $this->db->query("ALTER TABLE `{$table}` MODIFY `report_card_file` varchar(255) COLLATE latin1_swedish_ci NOT NULL");

        // created_on nullable, no default
        $this->db->query("ALTER TABLE `{$table}` MODIFY `created_on` datetime NULL");
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

    /* ----------------- PROFILE PHOTO HELPERS ----------------- */

    private function handle_profile_photo_upload()
    {
        if (!isset($_FILES['profile_photo'])) {
            return null; // nothing uploaded
        }

        $err = $_FILES['profile_photo']['error'];
        if ($err === UPLOAD_ERR_NO_FILE) return null;
        if ($err !== UPLOAD_ERR_OK) {
            throw new Exception('Upload failed (code '.$err.')');
        }

        $tmp  = $_FILES['profile_photo']['tmp_name'];
        $size = (int)$_FILES['profile_photo']['size'];

        // size cap (5MB)
        if ($size <= 0 || $size > 5 * 1024 * 1024) {
            throw new Exception('File size too large. Max 5MB.');
        }

        // detect MIME from content (don’t trust $_FILES[type])
        $mime = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $det = @finfo_file($finfo, $tmp);
                if ($det) $mime = strtolower($det);
                finfo_close($finfo);
            }
        } elseif (function_exists('getimagesize')) {
            $gi = @getimagesize($tmp);
            if ($gi && !empty($gi['mime'])) $mime = strtolower($gi['mime']);
        }

        $allowed = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
        if (!in_array($mime, $allowed, true)) {
            throw new Exception('Invalid file type. Only JPG/PNG/GIF/WebP allowed.');
        }

        $bytes = @file_get_contents($tmp);
        if ($bytes === false) {
            throw new Exception('Could not read uploaded file.');
        }

        return $bytes; // raw blob for LONGBLOB column
    }

    public function get_profile_photo($student_id)
    {
        $this->db->select('profile_photo');
        $this->db->where('id', (int)$student_id);
        $row = $this->db->get(db_prefix().'school_students')->row();
        return $row ? $row->profile_photo : null;
    }

    /* ----------------- CREATE ----------------- */

    public function add($data)
    {
        try {
            $photo = null;
            try { $photo = $this->handle_profile_photo_upload(); } catch (Exception $e) { log_message('error','School photo upload: '.$e->getMessage()); }

            $country_id = !empty($data['country_id']) ? (int)$data['country_id'] : null;
            $school_name_id = $this->get_or_create_school_name_id($data['school_name'] ?? ($data['school_name_id'] ?? ''));

            $insert = [
                'entity_type'                   => 'school',
                'name'                          => $data['name'] ?? '',
                'profile_photo'                 => $photo,
                'contact_no'                    => $this->toNullIfEmpty($data['phone'] ?? ''),
                'email'                         => $this->toNullIfEmpty($data['email'] ?? ''),
                'address'                       => $this->toNullIfEmpty($data['address'] ?? ''),
                'city'                          => $this->toNullIfEmpty($data['city'] ?? ''),
                'zip'                           => $this->toNullIfEmpty($data['postal_code'] ?? ''),
                'country_id'                    => $country_id,

                'school_internal_id'            => $this->generate_internal_id(),
                'school_id'                     => $this->toNullIfEmpty($data['school_id'] ?? ''),
                'school_type'                   => $this->toNullIfEmpty($data['school_type'] ?? ''),
                'school_name_id'                => $school_name_id,

                'school_grade_year'             => $this->toIntOrNull($data['school_grade_year'] ?? ''),
                'school_grade'                  => $this->toNullIfEmpty($data['grade'] ?? ''),
                'grade_mismatch_reason'         => $this->toNullIfEmpty($data['grade_mismatch_reason'] ?? ''),

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
                'school_father_income'          => $this->toFloatOrNull($data['father_income'] ?? null),
                'school_mother_income'          => $this->toFloatOrNull($data['mother_income'] ?? null),
                'school_guardian_name'          => $this->toNullIfEmpty($data['guardian_name'] ?? ''),
                'school_guardian_income'        => $this->toFloatOrNull($data['guardian_income'] ?? null),

                'sponsor_id'                    => $this->toIntOrNull($data['sponsor_id'] ?? ''),
                'background_info'               => $this->toNullIfEmpty($data['background_information'] ?? ''),
                'internal_comment'              => $this->toNullIfEmpty($data['internal_comment'] ?? ''),
                'external_comment'              => $this->toNullIfEmpty($data['external_comment'] ?? ''),
            ];

            $this->db->insert(db_prefix().'school_students', $insert);
            $id = (int)$this->db->insert_id();
            return $id ?: false;

        } catch (Exception $e) {
            log_message('error','Error adding school student: '.$e->getMessage());
            return false;
        }
    }

    /* ----------------- READ ----------------- */

    public function get_all()
    {
        $c = $this->country_schema();

        $this->db->select('
            ss.*,
            sn.name AS school_name,
            b.name  AS bank_name' .
            ($c['table'] ? ', c.' . $c['name'] . ' AS country_name' : ', NULL AS country_name')
        , false);

        $this->db->from(db_prefix().'school_students ss');
        $this->db->join(db_prefix().'school_name sn', 'sn.id = ss.school_name_id', 'left');

        if ($c['table']) {
            $this->db->join($c['table'].' c', 'c.'.$c['id'].' = ss.country_id', 'left');
        }

        $this->db->join(db_prefix().'bank b', 'b.id = ss.bank_id', 'left');
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

        $this->db->from(db_prefix().'school_students ss');
        $this->db->join(db_prefix().'school_name sn', 'sn.id = ss.school_name_id', 'left');

        if ($c['table']) {
            $this->db->join($c['table'].' c', 'c.'.$c['id'].' = ss.country_id', 'left');
        }

        $this->db->join(db_prefix().'bank b', 'b.id = ss.bank_id', 'left');
        $this->db->where('ss.id', (int)$id);
        return $this->db->get()->row_array();
    }

    public function count_all()
    {
        return $this->db->count_all_results(db_prefix().'school_students');
    }

    /* ----------------- UPDATE ----------------- */

    public function update($data, $id)
    {
        try {
            $update = [];

            $map = [
                // basics
                'name'                   => 'name',
                'email'                  => 'email',
                'phone'                  => 'contact_no',
                'address'                => 'address',
                'city'                   => 'city',
                'postal_code'            => 'zip',
                'country_id'             => 'country_id',

                // school identity
                'school_id'              => 'school_id',
                'school_type'            => 'school_type',
                'school_name_id'         => 'school_name_id', // direct id if provided
                'school_name'            => 'school_name',    // OR create by name

                // academics / personal
                'school_grade_year'      => 'school_grade_year',
                'grade'                  => 'school_grade',
                'grade_mismatch_reason'  => 'grade_mismatch_reason',
                'dob'                    => 'school_student_dob',

                // sponsorship
                'sponsorship_start'      => 'school_sponsorship_start_date',
                'sponsorship_end'        => 'school_sponsorship_end_date',

                // introduced by
                'introduced_by'          => 'school_introducedby',
                'introduced_phone'       => 'school_introducedph',

                // bank
                'bank_id'                => 'bank_id',
                'bank_branch_number'     => 'school_bank_branch_number',
                'bank_branch_info'       => 'school_bank_branch_info',
                'bank_account_number'    => 'school_bank_account_no',

                // family/income
                'father_name'            => 'school_father_name',
                'mother_name'            => 'school_mother_name',
                'guardian_name'          => 'school_guardian_name',
                'father_income'          => 'school_father_income',
                'mother_income'          => 'school_mother_income',
                'guardian_income'        => 'school_guardian_income',

                // misc
                'sponsor_id'             => 'sponsor_id',
                'background_information' => 'background_info',
                'internal_comment'       => 'internal_comment',
                'external_comment'       => 'external_comment',
            ];

            foreach ($map as $in => $col) {
                if (!array_key_exists($in, $data)) continue;

                $val = $data[$in];

                if (in_array($in, ['father_income','mother_income','guardian_income'], true)) {
                    $update[$col] = $this->toFloatOrNull($val);
                } elseif (in_array($in, ['sponsor_id','bank_id','country_id','school_grade_year'], true)) {
                    $update[$col] = $this->toIntOrNull($val);
                } elseif ($in === 'dob') {
                    $update[$col] = $this->toNullIfEmpty($val);
                    $update['school_age'] = $this->calc_age_int($val);
                } elseif ($in === 'school_name_id') {
                    $update['school_name_id'] = $this->toIntOrNull($val);
                } elseif ($in === 'school_name') {
                    $update['school_name_id'] = $this->get_or_create_school_name_id($val);
                } else {
                    $update[$col] = $this->toNullIfEmpty($val);
                }
            }

            // optional new profile photo
            try {
                $photo = $this->handle_profile_photo_upload();
                if ($photo !== null) $update['profile_photo'] = $photo;
            } catch (Exception $e) {
                // swallow but log
                log_message('error','School photo upload (update): '.$e->getMessage());
            }

            if (empty($update)) return false;

            $this->db->where('id', (int)$id);
            $this->db->update(db_prefix().'school_students', $update);
            return $this->db->affected_rows() >= 0;

        } catch (Exception $e) {
            log_message('error','Error updating school student: '.$e->getMessage());
            return false;
        }
    }

    public function update_student($data, $id)
    {
        return $this->update($data, $id);
    }

    /* ----------------- DELETE ----------------- */

    public function delete($id)
    {
        try {
            // delete related report cards (unlink path if present)
            $this->db->where('student_school_id', (int)$id);
            $cards = $this->db->get(db_prefix().'school_report_card')->result();
            foreach ($cards as $c) {
                if (!empty($c->report_card_file)) {
                    $abs = rtrim(FCPATH, '/\\') . '/' . ltrim($c->report_card_file, '/');
                    if (is_file($abs)) @unlink($abs);
                }
            }
            $this->db->where('student_school_id', (int)$id)->delete(db_prefix().'school_report_card');

            // delete student
            $this->db->where('id', (int)$id)->delete(db_prefix().'school_students');
            return $this->db->affected_rows() > 0;

        } catch (Exception $e) {
            log_message('error','Error deleting school student: '.$e->getMessage());
            return false;
        }
    }

    public function delete_student($id) { return $this->delete($id); }

    /* ----------------- FILTERS/LISTS (safe for missing cols) ----------------- */

    public function get_students_filtered($filters = [])
    {
        $this->db->select('ss.*, sn.name as school_name');
        $this->db->from(db_prefix().'school_students ss');
        $this->db->join(db_prefix().'school_name sn', 'sn.id = ss.school_name_id', 'left');

        if (!empty($filters['grade'])) $this->db->where('ss.school_grade', $filters['grade']);
        if (!empty($filters['city']))  $this->db->like('ss.city', $filters['city']);
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
        $this->ensure_school_name_table();
        return $this->db->order_by('name','ASC')->get(db_prefix().'school_name')->result_array();
    }

    /* ----------------- REPORT CARD HELPERS (school) ----------------- */

    public function get_report_cards($student_id)
    {
        $this->db->where('student_school_id', (int)$student_id);
        $this->db->order_by('upload_date', 'DESC');
        $cards = $this->db->get(db_prefix().'school_report_card')->result_array();

        foreach ($cards as &$c) {
            $c['download_url'] = admin_url('student_sponsor_portal/download_school_report_card/'.$c['id']);
        }
        return ['success'=>true, 'report_cards'=>$cards];
    }

    /* ----------------- HELPERS ----------------- */

    private function country_schema()
    {
        // Prefer core countries if present; else your custom tblcountry
        if ($this->db->table_exists(db_prefix().'countries')) {
            return ['table'=>db_prefix().'countries','id'=>'country_id','name'=>'short_name','phone'=>'calling_code','is_custom'=>false];
        }
        if ($this->db->table_exists(db_prefix().'country')) {
            // Matches your tblcountry structure: id, name, phone_code
            return ['table'=>db_prefix().'country','id'=>'id','name'=>'name','phone'=>'phone_code','is_custom'=>true];
        }
        return ['table'=>null,'id'=>null,'name'=>null,'phone'=>null,'is_custom'=>null];
    }

    private function generate_internal_id()
    {
        $this->db->select('MAX(CAST(SUBSTRING(school_internal_id, 4) AS UNSIGNED)) as max_id');
        $this->db->like('school_internal_id', 'SCH', 'after');
        $row = $this->db->get(db_prefix().'school_students')->row();
        $next = ($row && $row->max_id) ? ((int)$row->max_id + 1) : 1;
        return 'SCH' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    public function get_or_create_school_name_id($name_or_id)
    {
        if (!$name_or_id) return null;
        if (is_numeric($name_or_id)) return (int)$name_or_id;

        $this->db->where('name', $name_or_id);
        $row = $this->db->get(db_prefix().'school_name')->row();
        if ($row) return (int)$row->id;

        $this->db->insert(db_prefix().'school_name', ['name'=>$name_or_id]);
        return (int)$this->db->insert_id();
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
