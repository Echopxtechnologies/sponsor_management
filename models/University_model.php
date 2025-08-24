<?php
defined('BASEPATH') or exit('No direct script access allowed');

class University_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ensure_required_tables();
    }

    /* ----------------- TABLE CREATION HELPERS ----------------- */
    
    private function ensure_required_tables()
    {
        $this->ensure_university_name_table();
        $this->ensure_university_program_table(); 
        $this->ensure_university_report_card_table();
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
            
            // Insert some default data
            $default_universities = [
                'University of Colombo',
                'University of Peradeniya', 
                'University of Kelaniya',
                'University of Moratuwa',
                'University of Sri Jayewardenepura',
                'University of Ruhuna',
                'Sabaragamuwa University',
                'Wayamba University',
                'Rajarata University',
                'South Eastern University'
            ];
            
            foreach ($default_universities as $uni) {
                $this->db->replace($table_name, ['name' => $uni]);
            }
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
            
            // Insert some default programs
            $default_programs = [
                'Bachelor of Science (BSc)',
                'Bachelor of Arts (BA)',
                'Bachelor of Engineering (BEng)',
                'Bachelor of Medicine (MBBS)',
                'Bachelor of Law (LLB)',
                'Bachelor of Commerce (BCom)',
                'Bachelor of Information Technology (BIT)',
                'Master of Science (MSc)',
                'Master of Arts (MA)',
                'Master of Business Administration (MBA)',
                'Doctor of Philosophy (PhD)'
            ];
            
            foreach ($default_programs as $program) {
                $this->db->replace($table_name, ['name' => $program]);
            }
        }
    }

    private function ensure_university_report_card_table()
    {
        $table_name = db_prefix() . 'university_report_card';
        
        if (!$this->db->table_exists($table_name)) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `{$table_name}` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `student_university_id` int(11) NOT NULL,
                    `filename` varchar(255) NOT NULL,
                    `upload_date` datetime NOT NULL,
                    `report_card_term` varchar(100) DEFAULT NULL,
                    `current_term` varchar(100) DEFAULT NULL,
                    `semester_end_month` int(2) DEFAULT NULL,
                    `semester_end_year` int(4) DEFAULT NULL,
                    `report_card_file` varchar(500) NOT NULL,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `student_university_id` (`student_university_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }

    /* ----------------- CREATE ----------------- */
    public function add($data)
    {
        try {
            // Resolve foreign keys from names
            $university_name_id    = $this->get_or_create_university_name_id($data['university_name'] ?? ($data['university_name_id'] ?? ''));
            $university_program_id = $this->get_or_create_university_program_id($data['program'] ?? ($data['university_program_id'] ?? ''));
            $bank_id = $this->get_or_create_bank_id($data['bank_name'] ?? ($data['bank_id'] ?? ''));

            // Handle country - check both country_id and countries table variations
            $country_id = 1; // Default
            if (!empty($data['country_id'])) {
                $country_id = (int)$data['country_id'];
            }

            // Map fields to actual DB columns
            $insert = [
                // Core
                'entity_type'                         => 'university',
                'name'                                => $data['name'] ?? '',
                'profile_photo'                       => $data['profile_photo'] ?? null,
                'contact_no'                          => $data['phone'] ?? '',
                'email'                               => $this->toNullIfEmpty($data['email'] ?? ''),

                // Address
                'address'                             => $this->toNullIfEmpty($data['address'] ?? ''),
                'city'                                => $this->toNullIfEmpty($data['city'] ?? ''),
                'zip'                                 => $this->toNullIfEmpty($data['postal_code'] ?? ''),
                'country_id'                          => $country_id,

                // University specifics
                'university_internal_id'              => $this->generate_internal_id(),
                'university_id'                       => $this->toNullIfEmpty($data['university_id'] ?? ''),
                'university_name_id'                  => $university_name_id,
                'university_program_id'               => $university_program_id,
                'university_year_of_study'            => $this->sanitize_year($data['year_of_study'] ?? null),

                'university_student_dob'              => $this->toNullIfEmpty($data['dob'] ?? ''),
                'university_age'                      => $this->calc_age($data['dob'] ?? null),

                // Bank (these fields have UNIQUE constraints, so must be NULL if empty)
                'bank_id'                             => $bank_id,
                'university_bank_branch_number'       => $this->toNullIfEmpty($data['bank_branch_number'] ?? ''),
                'university_bank_branch_info'         => $this->toNullIfEmpty($data['bank_branch_info'] ?? ''),
                'university_bank_account_no'          => $this->toNullIfEmpty($data['bank_account_number'] ?? ''),

                // Sponsorship
                'university_sponsorship_start_date'   => $this->toNullIfEmpty($data['sponsorship_start'] ?? ''),
                'university_sponsorship_end_date'     => $this->toNullIfEmpty($data['sponsorship_end'] ?? ''),
                'university_introducedby'             => $this->toNullIfEmpty($data['introduced_by'] ?? ''),
                'university_introducedph'             => $this->toNullIfEmpty($data['introduced_phone'] ?? ''),

                // Family
                'university_father_name'              => $this->toNullIfEmpty($data['father_name'] ?? ''),
                'university_mother_name'              => $this->toNullIfEmpty($data['mother_name'] ?? ''),
                'university_father_income'            => $this->toFloatOrNull($data['father_income'] ?? null),
                'university_mother_income'            => $this->toFloatOrNull($data['mother_income'] ?? null),
                'university_guardian_name'            => $this->toNullIfEmpty($data['guardian_name'] ?? ''),
                'university_guardian_income'          => $this->toFloatOrNull($data['guardian_income'] ?? null),

                // Notes & sponsor
                'sponsor_id'                          => $this->toIntOrNull($data['sponsor_id'] ?? ''),
                'background_info'                     => $this->toNullIfEmpty($data['background_information'] ?? ''),
                'internal_comment'                    => $this->toNullIfEmpty($data['internal_comment'] ?? ''),
                'external_comment'                    => $this->toNullIfEmpty($data['external_comment'] ?? ''),
            ];

            $this->db->insert(db_prefix() . 'university_students', $insert);
            $student_id = $this->db->insert_id();

            if ($student_id) {
                // Create client record in Perfex CRM
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
            // Prepare update data
            $update_data = [];

            // Map form fields to database fields
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

            // Process each field
            foreach ($field_mapping as $form_field => $db_field) {
                if (array_key_exists($form_field, $data)) {
                    $value = $data[$form_field];
                    
                    // Handle special processing for certain fields
                    if (in_array($form_field, ['father_income', 'mother_income', 'guardian_income'])) {
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

            // Handle foreign key relationships
            if (isset($data['university_name_id']) && !empty($data['university_name_id'])) {
                $update_data['university_name_id'] = (int)$data['university_name_id'];
            }
            if (isset($data['university_program_id']) && !empty($data['university_program_id'])) {
                $update_data['university_program_id'] = (int)$data['university_program_id'];
            }
            if (isset($data['bank_id']) && !empty($data['bank_id'])) {
                $update_data['bank_id'] = (int)$data['bank_id'];
            }

            // Calculate age if DOB is updated
            if (isset($update_data['university_student_dob']) && !empty($update_data['university_student_dob'])) {
                $update_data['university_age'] = $this->calc_age($update_data['university_student_dob']);
            }

            // Perform update
            $this->db->where('id', (int)$id);
            $this->db->update(db_prefix() . 'university_students', $update_data);
            
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
                if (file_exists($card->report_card_file)) {
                    unlink($card->report_card_file);
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

    /* ----------------- HELPERS ----------------- */

    private function country_schema()
    {
        // Prefer your custom table first
        if ($this->db->table_exists(db_prefix() . 'country')) {
            return [
                'table'      => db_prefix() . 'country', // tblcountry
                'id'         => 'id',
                'name'       => 'name',
                'phone'      => 'phone_code',
                'is_custom'  => true,
            ];
        }

        // Fallback to Perfex core table
        if ($this->db->table_exists(db_prefix() . 'countries')) {
            return [
                'table'      => db_prefix() . 'countries', // tblcountries
                'id'         => 'country_id',
                'name'       => 'short_name',
                'phone'      => 'phonecode',
                'is_custom'  => false,
            ];
        }

        // None found
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

    private function get_or_create_university_name_id($name_or_id)
    {
        if (!$name_or_id) return null;
        
        // If it's already an ID, return it
        if (is_numeric($name_or_id)) {
            return (int)$name_or_id;
        }
        
        // Otherwise treat as name and find or create
        $this->db->where('name', $name_or_id);
        $row = $this->db->get(db_prefix() . 'university_name')->row();
        if ($row) return (int)$row->id;
        
        $this->db->insert(db_prefix() . 'university_name', ['name' => $name_or_id]);
        return (int)$this->db->insert_id();
    }

    private function get_or_create_university_program_id($name_or_id)
    {
        if (!$name_or_id) return null;
        
        if (is_numeric($name_or_id)) {
            return (int)$name_or_id;
        }
        
        $this->db->where('name', $name_or_id);
        $row = $this->db->get(db_prefix() . 'university_program')->row();
        if ($row) return (int)$row->id;
        
        $this->db->insert(db_prefix() . 'university_program', ['name' => $name_or_id]);
        return (int)$this->db->insert_id();
    }

    private function get_or_create_bank_id($name_or_id)
    {
        if (!$name_or_id) return null;
        
        if (is_numeric($name_or_id)) {
            return (int)$name_or_id;
        }
        
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

        // Map common formats to DB enum
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
        } catch (\Throwable $e) {
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
        if (!$c['table']) { return []; }

        if ($c['is_custom']) {
            // tblcountry already has the expected columns
            $this->db->select('id, name, phone_code');
            $this->db->from($c['table']);
            $this->db->order_by('name', 'ASC');
            return $this->db->get()->result_array();
        } else {
            // tblcountries -> alias to match your view
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
        // Check for existing bank tables in Perfex CRM
        if ($this->db->table_exists(db_prefix() . 'bank')) {
            return $this->db->order_by('name', 'ASC')->get(db_prefix() . 'bank')->result_array();
        }
        
        // Create a basic bank table if none exists
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . db_prefix() . "bank` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Insert some default banks
        $default_banks = [
            'Bank of Ceylon', 'People\'s Bank', 'Commercial Bank', 
            'Hatton National Bank', 'Sampath Bank', 'Seylan Bank',
            'Union Bank', 'DFCC Bank', 'Nations Trust Bank'
        ];
        
        foreach ($default_banks as $bank) {
            $this->db->replace(db_prefix() . 'bank', ['name' => $bank]);
        }
        
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