<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Sponsor_model extends App_Model
{
    /** @var string */
    private $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'sponsor_records';
    }

    /* ============================== CREATE ============================== */

    /**
     * Insert sponsor row
     * @param array $data (raw POST is fine; we sanitize/whitelist here)
     * @return int|false insert id or false
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
        $row['country_id'] = $this->normalize_country_id($row['country_id'] ?? null);

        // Enforce NULL for unique columns when empty (avoid duplicate '' errors)
        if (isset($row['sponsor_bank_account_no']) && $row['sponsor_bank_account_no'] === '') {
            $row['sponsor_bank_account_no'] = null;
        }

        // Minimal required field
        if (empty($row['name'])) {
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
            log_message('error', 'Sponsor_model::add DB error: ' . ($this->db->error()['message'] ?? 'unknown'));
            $this->db->trans_complete();
            return false;
        }

        $sponsor_id = (int) $this->db->insert_id();

        // Optional: create a related client/contact with minimal info
        if (!empty($row['name'])) {
            $this->load->model('clients_model');
            try {
                $this->clients_model->add([
                    'firstname'     => $row['name'],
                    'email'         => $row['email'] ?? '',
                    'phonenumber'   => $row['contact_no'] ?? '',
                    'custom_fields' => ['contact_type' => 'sponsor'],
                ]);
            } catch (Throwable $e) {
                log_message('error', 'clients_model->add failed: ' . $e->getMessage());
                // don't fail the sponsor insert because of this
            }
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            return false;
        }

        return $sponsor_id;
    }




    /* =============================== READ =============================== */

    /**
     * Get all sponsors (joined with country/bank when available)
     * @return array<int,array>
     */
    public function get_all(): array
    {
        $sp = $this->table;

        $meta = $this->country_table_meta();
        if ($meta) {
            $this->db->select("sp.*, c.{$meta['name']} AS country_name");
            $this->db->from("$sp sp");
            $this->db->join("{$meta['table']} c", "c.{$meta['pk']} = sp.country_id", 'left');
        } else {
            $this->db->select('sp.*')->from("$sp sp");
        }

        if ($this->db->table_exists(db_prefix() . 'bank')) {
            $this->db->select('b.name AS bank_name');
            $this->db->join(db_prefix() . 'bank b', 'b.id = sp.bank_id', 'left');
        }

        $results = $this->db->get()->result_array();
        $out = [];
        foreach ($results as $r) {
            $out[] = $this->map_database_to_form_fields($r);
        }
        return $out;
    }

    /**
     * Get single sponsor by id
     * @param int $sponsor_id
     * @return array|null
     */
    public function get_by_id(int $sponsor_id): ?array
    {
        if ($sponsor_id <= 0) {
            return null;
        }

        $sp = $this->table;
        $meta = $this->country_table_meta();

        if ($meta) {
            $this->db->select("sp.*, c.{$meta['name']} AS country_name");
            $this->db->from("$sp sp");
            $this->db->join("{$meta['table']} c", "c.{$meta['pk']} = sp.country_id", 'left');
        } else {
            $this->db->select('sp.*')->from("$sp sp");
        }

        if ($this->db->table_exists(db_prefix() . 'bank')) {
            $this->db->select('b.name AS bank_name');
            $this->db->join(db_prefix() . 'bank b', 'b.id = sp.bank_id', 'left');
        }

        $this->db->where('sp.id', $sponsor_id);
        $row = $this->db->get()->row_array();

        if (!$row) {
            return null;
        }
        return $this->map_database_to_form_fields($row);
    }

    /**
     * Count all sponsors
     */
    public function count_all(): int
    {
        return (int) $this->db->count_all_results($this->table);
    }

    /* ============================== MISSING METHODS (ADDED) ============================== */

    /**
     * Get all universities for dropdown
     * @return array
     */
    public function get_universities(): array
    {
        if (!$this->db->table_exists(db_prefix() . 'university_name')) {
            return [];
        }
        
        return $this->db->select('id, name')
                       ->order_by('name', 'ASC')
                       ->get(db_prefix() . 'university_name')
                       ->result_array();
    }

    /**
     * Get all programs for dropdown
     * @return array
     */
    public function get_programs(): array
    {
        if (!$this->db->table_exists(db_prefix() . 'university_program')) {
            return [];
        }
        
        return $this->db->select('id, name')
                       ->order_by('name', 'ASC')
                       ->get(db_prefix() . 'university_program')
                       ->result_array();
    }

    /**
     * Get all sponsors for dropdown (useful for admin views)
     * @return array
     */
    public function get_sponsors(): array
    {
        return $this->db->select('id, name')
                       ->order_by('name', 'ASC')
                       ->get($this->table)
                       ->result_array();
    }

    /**
     * Alias for backward compatibility with controller
     */
    public function update_student($data, $id)
    {
        return $this->update($data, $id);
    }

    /* ============================== STUDENT SELECTION ============================== */

    /**
     * Get available students for sponsor selection (both school and university)
     * @param int $sponsor_id Current sponsor ID (to exclude already selected students)
     * @return array
     */
    public function get_available_students($sponsor_id = null)
    {
        $result = [
            'school_students' => [],
            'university_students' => []
        ];

        // Get currently selected students for this sponsor (if editing)
        $selected_school_ids = [];
        $selected_university_ids = [];
        
        if ($sponsor_id) {
            $sponsor = $this->get_by_id($sponsor_id);
            if ($sponsor) {
                $selected_school_ids = $this->decode_internal_ids($sponsor['school_internal_ids'] ?? '');
                $selected_university_ids = $this->decode_internal_ids($sponsor['university_internal_ids'] ?? '');
            }
        }

        // Get school students
        if ($this->db->table_exists(db_prefix() . 'school_students')) {
            $this->db->select('
                id,
                school_internal_id,
                name,
                school_grade,
                school_age,
                city,
                school_student_dob,
                email,
                contact_no,
                sponsor_id
            ');
            $this->db->from(db_prefix() . 'school_students');
            $this->db->where('school_internal_id IS NOT NULL');
            $this->db->where('school_internal_id !=', '');
            $this->db->order_by('name', 'ASC');
            
            $school_students = $this->db->get()->result_array();
            
            foreach ($school_students as $student) {
                $result['school_students'][] = [
                    'id' => $student['id'],
                    'internal_id' => $student['school_internal_id'],
                    'name' => $student['name'],
                    'type' => 'school',
                    'grade' => 'Grade ' . ($student['school_grade'] ?? 'N/A'),
                    'age' => $student['school_age'] ? $student['school_age'] . ' years' : 'N/A',
                    'location' => $student['city'] ?? 'N/A',
                    'dob' => $student['school_student_dob'] ?? '',
                    'email' => $student['email'] ?? '',
                    'phone' => $student['contact_no'] ?? '',
                    'current_sponsor_id' => $student['sponsor_id'],
                    'is_selected' => in_array($student['school_internal_id'], $selected_school_ids)
                ];
            }
        }

        // Get university students
        if ($this->db->table_exists(db_prefix() . 'university_students')) {
            $this->db->select('
                us.id,
                us.university_internal_id,
                us.name,
                us.university_year_of_study,
                us.university_age,
                us.city,
                us.university_student_dob,
                us.email,
                us.contact_no,
                us.sponsor_id,
                un.name as university_name,
                up.name as program_name
            ');
            $this->db->from(db_prefix() . 'university_students us');
            $this->db->join(db_prefix() . 'university_name un', 'un.id = us.university_name_id', 'left');
            $this->db->join(db_prefix() . 'university_program up', 'up.id = us.university_program_id', 'left');
            $this->db->where('us.university_internal_id IS NOT NULL');
            $this->db->where('us.university_internal_id !=', '');
            $this->db->order_by('us.name', 'ASC');
            
            $university_students = $this->db->get()->result_array();
            
            foreach ($university_students as $student) {
                $result['university_students'][] = [
                    'id' => $student['id'],
                    'internal_id' => $student['university_internal_id'],
                    'name' => $student['name'],
                    'type' => 'university',
                    'grade' => 'Year ' . ($student['university_year_of_study'] ?? 'N/A'),
                    'university' => $student['university_name'] ?? 'N/A',
                    'program' => $student['program_name'] ?? 'N/A',
                    'age' => $student['university_age'] ? $student['university_age'] . ' years' : 'N/A',
                    'location' => $student['city'] ?? 'N/A',
                    'dob' => $student['university_student_dob'] ?? '',
                    'email' => $student['email'] ?? '',
                    'phone' => $student['contact_no'] ?? '',
                    'current_sponsor_id' => $student['sponsor_id'],
                    'is_selected' => in_array($student['university_internal_id'], $selected_university_ids)
                ];
            }
        }

        return $result;
    }

    /**
     * IMPROVED VERSION - Update sponsor's selected students AND student relationships
     * This is called from the controller after saving the sponsor
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
            // Step 1: Update the JSON fields in sponsor table
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

            // Step 2: Now update the student relationships
            $relationship_result = $this->update_student_sponsor_relationships($sponsor_id, $clean_school_ids, $clean_university_ids);
            
            if (!$relationship_result) {
                throw new Exception('Failed to update student sponsor relationships');
            }

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

    /**
     * COMPLETELY REWRITTEN - Update student sponsor relationships in both directions
     * This method ensures the sponsor_id field in student tables is properly updated
     * @param int $sponsor_id
     * @param array $school_internal_ids
     * @param array $university_internal_ids
     * @return bool
     */
    public function update_student_sponsor_relationships($sponsor_id, $school_internal_ids = [], $university_internal_ids = [])
    {
        if ($sponsor_id <= 0) {
            return false;
        }

        try {
            log_message('info', "Starting update_student_sponsor_relationships for sponsor {$sponsor_id}");

            // Step 1: Clear ALL existing sponsor relationships for this sponsor
            // This prevents orphaned relationships when students are deselected
            
            if ($this->db->table_exists(db_prefix() . 'school_students')) {
                // Clear sponsor_id for all school students previously sponsored by this sponsor
                $this->db->where('sponsor_id', $sponsor_id);
                $this->db->update(db_prefix() . 'school_students', ['sponsor_id' => null]);
                $cleared_school = $this->db->affected_rows();
                log_message('info', "Cleared sponsor_id from {$cleared_school} school students for sponsor {$sponsor_id}");
            }
            
            if ($this->db->table_exists(db_prefix() . 'university_students')) {
                // Clear sponsor_id for all university students previously sponsored by this sponsor
                $this->db->where('sponsor_id', $sponsor_id);
                $this->db->update(db_prefix() . 'university_students', ['sponsor_id' => null]);
                $cleared_university = $this->db->affected_rows();
                log_message('info', "Cleared sponsor_id from {$cleared_university} university students for sponsor {$sponsor_id}");
            }

            // Step 2: Set new sponsor relationships for selected students
            
            // Update school students
            if (!empty($school_internal_ids) && $this->db->table_exists(db_prefix() . 'school_students')) {
                // Verify these students exist before updating
                $existing_students = $this->db->select('school_internal_id, id, name')
                                              ->where_in('school_internal_id', $school_internal_ids)
                                              ->get(db_prefix() . 'school_students')
                                              ->result_array();
                
                log_message('info', "Found " . count($existing_students) . " existing school students to update");
                
                if (!empty($existing_students)) {
                    $existing_ids = array_column($existing_students, 'school_internal_id');
                    
                    // Update sponsor_id for these students
                    $this->db->where_in('school_internal_id', $existing_ids);
                    $this->db->update(db_prefix() . 'school_students', ['sponsor_id' => $sponsor_id]);
                    $updated_school = $this->db->affected_rows();
                    
                    log_message('info', "Updated sponsor_id for {$updated_school} school students to sponsor {$sponsor_id}");
                    
                    // Verify the updates worked
                    $verification = $this->db->select('school_internal_id, name, sponsor_id')
                                            ->where_in('school_internal_id', $existing_ids)
                                            ->get(db_prefix() . 'school_students')
                                            ->result_array();
                    
                    foreach ($verification as $student) {
                        if ($student['sponsor_id'] == $sponsor_id) {
                            log_message('info', "VERIFIED: School student {$student['school_internal_id']} ({$student['name']}) now has sponsor_id = {$sponsor_id}");
                        } else {
                            log_message('error', "FAILED: School student {$student['school_internal_id']} ({$student['name']}) has sponsor_id = {$student['sponsor_id']}, expected {$sponsor_id}");
                        }
                    }
                } else {
                    log_message('warning', "No school students found with internal_ids: " . implode(', ', $school_internal_ids));
                }
            }

            // Update university students
            if (!empty($university_internal_ids) && $this->db->table_exists(db_prefix() . 'university_students')) {
                // Verify these students exist before updating
                $existing_students = $this->db->select('university_internal_id, id, name')
                                              ->where_in('university_internal_id', $university_internal_ids)
                                              ->get(db_prefix() . 'university_students')
                                              ->result_array();
                
                log_message('info', "Found " . count($existing_students) . " existing university students to update");
                
                if (!empty($existing_students)) {
                    $existing_ids = array_column($existing_students, 'university_internal_id');
                    
                    // Update sponsor_id for these students
                    $this->db->where_in('university_internal_id', $existing_ids);
                    $this->db->update(db_prefix() . 'university_students', ['sponsor_id' => $sponsor_id]);
                    $updated_university = $this->db->affected_rows();
                    
                    log_message('info', "Updated sponsor_id for {$updated_university} university students to sponsor {$sponsor_id}");
                    
                    // Verify the updates worked
                    $verification = $this->db->select('university_internal_id, name, sponsor_id')
                                            ->where_in('university_internal_id', $existing_ids)
                                            ->get(db_prefix() . 'university_students')
                                            ->result_array();
                    
                    foreach ($verification as $student) {
                        if ($student['sponsor_id'] == $sponsor_id) {
                            log_message('info', "VERIFIED: University student {$student['university_internal_id']} ({$student['name']}) now has sponsor_id = {$sponsor_id}");
                        } else {
                            log_message('error', "FAILED: University student {$student['university_internal_id']} ({$student['name']}) has sponsor_id = {$student['sponsor_id']}, expected {$sponsor_id}");
                        }
                    }
                } else {
                    log_message('warning', "No university students found with internal_ids: " . implode(', ', $university_internal_ids));
                }
            }

            // Step 3: Create reminder entries for tracking
            $this->create_sponsor_reminders($sponsor_id, $school_internal_ids, $university_internal_ids);

            log_message('info', "Successfully completed update_student_sponsor_relationships for sponsor {$sponsor_id}");
            return true;
            
        } catch (Exception $e) {
            log_message('error', 'Exception in update_student_sponsor_relationships: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get students who have selected a specific sponsor
     * @param int $sponsor_id
     * @return array
     */
    public function get_students_by_sponsor($sponsor_id)
    {
        $result = ['school_students' => [], 'university_students' => []];
        
        if ($sponsor_id <= 0) {
            return $result;
        }

        // Get school students sponsored by this sponsor
        if ($this->db->table_exists(db_prefix() . 'school_students')) {
            $this->db->select('ss.*, sn.name as school_name')
                     ->from(db_prefix() . 'school_students ss')
                     ->join(db_prefix() . 'school_name sn', 'sn.id = ss.school_name_id', 'left')
                     ->where('ss.sponsor_id', $sponsor_id);
            $result['school_students'] = $this->db->get()->result_array();
        }

        // Get university students sponsored by this sponsor  
        if ($this->db->table_exists(db_prefix() . 'university_students')) {
            $this->db->select('us.*, un.name as university_name, up.name as program_name')
                     ->from(db_prefix() . 'university_students us')
                     ->join(db_prefix() . 'university_name un', 'un.id = us.university_name_id', 'left')
                     ->join(db_prefix() . 'university_program up', 'up.id = us.university_program_id', 'left')
                     ->where('us.sponsor_id', $sponsor_id);
            $result['university_students'] = $this->db->get()->result_array();
        }

        return $result;
    }

    /**
     * Get students sponsored by a specific sponsor
     * @param int $sponsor_id
     * @return array
     */
    public function get_sponsored_students($sponsor_id)
    {
        $sponsor = $this->get_by_id($sponsor_id);
        if (!$sponsor) {
            return ['school_students' => [], 'university_students' => []];
        }

        $result = ['school_students' => [], 'university_students' => []];
        
        // Get school students
        $school_ids = $this->decode_internal_ids($sponsor['school_internal_ids'] ?? '');
        if (!empty($school_ids) && $this->db->table_exists(db_prefix() . 'school_students')) {
            $this->db->select('*');
            $this->db->from(db_prefix() . 'school_students');
            $this->db->where_in('school_internal_id', $school_ids);
            $result['school_students'] = $this->db->get()->result_array();
        }

        // Get university students
        $university_ids = $this->decode_internal_ids($sponsor['university_internal_ids'] ?? '');
        if (!empty($university_ids) && $this->db->table_exists(db_prefix() . 'university_students')) {
            $this->db->select('us.*, un.name as university_name, up.name as program_name');
            $this->db->from(db_prefix() . 'university_students us');
            $this->db->join(db_prefix() . 'university_name un', 'un.id = us.university_name_id', 'left');
            $this->db->join(db_prefix() . 'university_program up', 'up.id = us.university_program_id', 'left');
            $this->db->where_in('us.university_internal_id', $university_ids);
            $result['university_students'] = $this->db->get()->result_array();
        }

        return $result;
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
     * Create reminder entries for sponsored students
     */
    private function create_sponsor_reminders($sponsor_id, $school_internal_ids, $university_internal_ids)
    {
        if (!$this->db->table_exists(db_prefix() . 'sponsor_reminder')) {
            return; // Table doesn't exist, skip this step
        }

        try {
            // Delete existing reminders for this sponsor
            $this->db->where('sponsor_id', $sponsor_id)
                     ->delete(db_prefix() . 'sponsor_reminder');

            // Get school student IDs by internal_id
            if (!empty($school_internal_ids)) {
                $clean_school_ids = array_filter($school_internal_ids, function($id) {
                    return !empty(trim($id));
                });
                
                if (!empty($clean_school_ids)) {
                    $school_students = $this->db->select('id, school_sponsorship_start_date, school_sponsorship_end_date')
                                               ->where_in('school_internal_id', $clean_school_ids)
                                               ->get(db_prefix() . 'school_students')
                                               ->result_array();
                    
                    foreach ($school_students as $student) {
                        $reminder_data = [
                            'sponsor_id' => $sponsor_id,
                            'student_school_id' => $student['id'],
                            'sponsorship_start_date' => $student['school_sponsorship_start_date'],
                            'sponsorship_end_date' => $student['school_sponsorship_end_date'],
                            'reminder_status' => 'not_sent',
                            'renewal_reminder_status' => 'not_sent',
                            'created_on' => date('Y-m-d H:i:s')
                        ];
                        
                        $this->db->insert(db_prefix() . 'sponsor_reminder', $reminder_data);
                    }
                }
            }

            // Get university student IDs by internal_id
            if (!empty($university_internal_ids)) {
                $clean_university_ids = array_filter($university_internal_ids, function($id) {
                    return !empty(trim($id));
                });
                
                if (!empty($clean_university_ids)) {
                    $university_students = $this->db->select('id, university_sponsorship_start_date, university_sponsorship_end_date')
                                                   ->where_in('university_internal_id', $clean_university_ids)
                                                   ->get(db_prefix() . 'university_students')
                                                   ->result_array();
                    
                    foreach ($university_students as $student) {
                        $reminder_data = [
                            'sponsor_id' => $sponsor_id,
                            'student_university_id' => $student['id'],
                            'sponsorship_start_date' => $student['university_sponsorship_start_date'],
                            'sponsorship_end_date' => $student['university_sponsorship_end_date'],
                            'reminder_status' => 'not_sent',
                            'renewal_reminder_status' => 'not_sent',
                            'created_on' => date('Y-m-d H:i:s')
                        ];
                        
                        $this->db->insert(db_prefix() . 'sponsor_reminder', $reminder_data);
                    }
                }
            }
            
        } catch (Exception $e) {
            log_message('error', 'Error creating sponsor reminders: ' . $e->getMessage());
            // Don't fail the main operation for reminder creation failure
        }
    }

    /* =============================== UPDATE ============================== */

    /**
     * Update sponsor row
     * @param array $data
     * @param int $sponsor_id
     * @return bool
     */
    public function update(array $data, int $sponsor_id): bool
    {
        if ($sponsor_id <= 0) {
            return false;
        }

        // If a staff login email was provided, treat it as the authoritative contact email
        $staffEmail = isset($data['staff_email']) ? trim((string)$data['staff_email']) : '';
        if ($staffEmail !== '' && filter_var($staffEmail, FILTER_VALIDATE_EMAIL)) {
            $data['email'] = $staffEmail;
        }

        $row = $this->sanitize_payload($data, /*is_update=*/true);

        // Country FK normalization when present
        if (array_key_exists('country_id', $row)) {
            $row['country_id'] = $this->normalize_country_id($row['country_id']);
        }

        // Enforce NULL for unique columns when empty to avoid duplicate '' errors
        if (array_key_exists('sponsor_bank_account_no', $row) && $row['sponsor_bank_account_no'] === '') {
            $row['sponsor_bank_account_no'] = null;
        }

        // Normalize email if present
        if (array_key_exists('email', $row)) {
            $row['email'] = trim((string)$row['email']);
            if ($row['email'] === '' || !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                $row['email'] = null;
            }
        }

        // Handle student selection updates - REMOVED from here
        // This is now handled separately in update_sponsored_students method

        if (!$row) {
            // nothing to update
            return false;
        }

        $this->db->where('id', $sponsor_id);
        $ok = $this->db->update($this->table, $row);
        if (!$ok) {
            log_message('error', 'Sponsor_model::update DB error: ' . ($this->db->error()['message'] ?? 'unknown'));
        }
        return (bool) $ok;
    }

    /**
     * Backward-compatible alias
     */
    public function update_sponsor(array $data, int $sponsor_id): bool
    {
        return $this->update($data, $sponsor_id);
    }

    /* =============================== DELETE ============================== */

    public function delete_sponsor(int $sponsor_id): bool
    {
        if ($sponsor_id <= 0) {
            return false;
        }

        $this->db->trans_start();

        try {
            // Clear sponsor_id from students
            $this->db->where('sponsor_id', $sponsor_id)
                     ->update(db_prefix() . 'school_students', ['sponsor_id' => null]);
            
            $this->db->where('sponsor_id', $sponsor_id)
                     ->update(db_prefix() . 'university_students', ['sponsor_id' => null]);

            // Delete reminders
            if ($this->db->table_exists(db_prefix() . 'sponsor_reminder')) {
                $this->db->where('sponsor_id', $sponsor_id)
                         ->delete(db_prefix() . 'sponsor_reminder');
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

    public function delete(int $sponsor_id): bool
    {
        return $this->delete_sponsor($sponsor_id);
    }

    /* ============================== HELPERS ============================== */

    /**
     * Whitelist + sanitize input for insert/update
     * - Trims strings
     * - Converts empty strings to '' (insert) or NULL (update) appropriately
     */
    private function sanitize_payload(array $data, bool $is_update): array
    {
        // Only fields that exist in your table:
        $fields = [
            'entity_type', 'name', 'email', 'contact_no', 'address', 'city', 'zip',
            'country_id', 'state_id', 'state', 'bank_id',
            'sponsor_type', 'sponsor_occupation',
            'sponsor_bank_branch_info', 'sponsor_bank_branch_number', 'sponsor_bank_account_no',
            'membership_start_date', 'membership_end_date', 'sponsor_frequency',
            'school_internal_ids', 'university_internal_ids', // Add these new fields
            'staff_id', 'active', // present in your schema
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
                case 'state_id':
                case 'bank_id':
                case 'staff_id':
                    // Allow nulls
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
                    // enforce your intended default
                    $row[$f] = ($v !== '') ? $v : 'sponsor';
                    break;

                case 'school_internal_ids':
                case 'university_internal_ids':
                    // These are JSON fields, handle them as strings
                    $row[$f] = ($v === '' || $v === null) ? null : $v;
                    break;

                default:
                    // Strings -> for INSERT keep empty string as ''; for UPDATE convert '' to NULL
                    if ($is_update) {
                        $row[$f] = ($v === '') ? null : $v;
                    } else {
                        $row[$f] = ($v === null) ? '' : $v;
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
     * Normalize/validate country id (FK)
     * - If your FK points to tblcountry(id) we validate against it
     */
    private function normalize_country_id($candidate)
    {
        // Allow NULL
        if ($candidate === null || $candidate === '') {
            // You previously attempted a default; keep it optional/safe:
            $cid = $this->get_default_country_id();
            return $this->is_valid_country_id($cid) ? $cid : null;
        }

        $cid = (int) $candidate;
        if (!$this->is_valid_country_id($cid)) {
            return null;
        }
        return $cid;
    }

    /**
     * Return Y-m-d or NULL
     */
    private function normalize_date_or_null($v): ?string
    {
        if ($v === '' || $v === null) {
            return null;
        }
        // basic safe normalization; adjust if you need time
        $ts = strtotime($v);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    /**
     * Prefer Perfex default tblcountry; fallback to tblcountries for display only.
     */
    private function country_table_meta(): ?array
    {
        if ($this->db->table_exists(db_prefix() . 'country')) {
            return ['table' => db_prefix() . 'country', 'pk' => 'id', 'name' => 'name'];
        }
        if ($this->db->table_exists(db_prefix() . 'countries')) {
            // Used only for joins to show display names when tblcountry is not present
            return ['table' => db_prefix() . 'countries', 'pk' => 'country_id', 'name' => 'short_name'];
        }
        return null;
    }

    private function get_default_country_id(): ?int
    {
        $meta = $this->country_table_meta();
        // only guess when real FK table is tblcountry
        if (!$meta || $meta['table'] !== db_prefix() . 'country') {
            return null;
        }
        $this->db->select($meta['pk'])->limit(1);
        $row = $this->db->get($meta['table'])->row();
        return $row ? (int) $row->{$meta['pk']} : null;
    }

    private function is_valid_country_id($id): bool
    {
        if ($id === null) {
            return true; // nullable is allowed
        }
        $meta = $this->country_table_meta();
        if (!$meta || $meta['table'] !== db_prefix() . 'country') {
            // If you don't actually have tblcountry, treat any value as invalid to avoid FK errors
            return false;
        }
        $this->db->where($meta['pk'], (int) $id);
        return (bool) $this->db->get($meta['table'])->row();
    }

    /**
     * Map DB row to view form shape
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
            'state_id'                    => $r['state_id'] ?? '',
            'state'                       => $r['state'] ?? '',
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
            'school_internal_ids'         => $r['school_internal_ids'] ?? '',
            'university_internal_ids'     => $r['university_internal_ids'] ?? '',
            'entity_type'                 => $r['entity_type'] ?? 'sponsor',
            'staff_id'                    => $r['staff_id'] ?? '',
            'active'                      => $r['active'] ?? '', // exists in your schema
        ];
    }
}