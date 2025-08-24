<?php
defined('BASEPATH') or exit('No direct script access allowed');

class School_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function add($data)
{
    // Get default country ID or null
    $country_id = $this->get_default_country_id();
    
    $insert_data = [
        'entity_type' => 'school',
        'name' => $data['name'] ?? '', // Only required field
        'email' => !empty(trim($data['email'] ?? '')) ? trim($data['email']) : null, // Set to null if empty or blank
        'contact_no' => !empty(trim($data['phone'] ?? '')) ? trim($data['phone']) : null,
        'address' => !empty(trim($data['address'] ?? '')) ? trim($data['address']) : null,
        'city' => !empty(trim($data['city'] ?? '')) ? trim($data['city']) : null,
        'zip' => !empty(trim($data['postal_code'] ?? '')) ? trim($data['postal_code']) : null,
        'country_id' => $country_id,
        'school_internal_id' => $this->generate_internal_id(),
        'school_id' => !empty(trim($data['school_id'] ?? '')) ? trim($data['school_id']) : null,
        'school_type' => !empty(trim($data['school_type'] ?? '')) ? trim($data['school_type']) : null,
        'school_name_id' => $this->get_or_create_school_name_id($data['school_name'] ?? ''),
        'school_grade' => !empty(trim($data['grade'] ?? '')) ? trim($data['grade']) : null,
        'school_student_dob' => !empty(trim($data['dob'] ?? '')) ? trim($data['dob']) : null,
        'school_bank_account_no' => !empty(trim($data['bank_account_number'] ?? '')) ? trim($data['bank_account_number']) : null,
        'school_sponsorship_start_date' => !empty(trim($data['sponsorship_start'] ?? '')) ? trim($data['sponsorship_start']) : null,
        'school_sponsorship_end_date' => !empty(trim($data['sponsorship_end'] ?? '')) ? trim($data['sponsorship_end']) : null,
        'school_introducedby' => !empty(trim($data['introduced_by'] ?? '')) ? trim($data['introduced_by']) : null,
        'school_introducedph' => !empty(trim($data['introduced_phone'] ?? '')) ? trim($data['introduced_phone']) : null,
        'school_father_name' => !empty(trim($data['father_name'] ?? '')) ? trim($data['father_name']) : null,
        'school_mother_name' => !empty(trim($data['mother_name'] ?? '')) ? trim($data['mother_name']) : null,
        'school_father_income' => $data['father_income'] ?? 0,
        'school_mother_income' => $data['mother_income'] ?? 0,
        'school_guardian_name' => !empty(trim($data['guardian_name'] ?? '')) ? trim($data['guardian_name']) : null,
        'school_guardian_income' => $data['guardian_income'] ?? 0,
        'background_info' => !empty(trim($data['background_information'] ?? '')) ? trim($data['background_information']) : null,
        'internal_comment' => !empty(trim($data['internal_comment'] ?? '')) ? trim($data['internal_comment']) : null,
        'external_comment' => !empty(trim($data['external_comment'] ?? '')) ? trim($data['external_comment']) : null
    ];

    $this->db->insert(db_prefix() . 'school_students', $insert_data);
    $student_id = $this->db->insert_id();
    
    // Only create client if basic info is provided
    if (!empty(trim($data['name'] ?? ''))) {
        $this->load->model('clients_model');
        
        // Prepare client data - don't include email if it's empty
        $client_data = [
            'firstname' => trim($data['name']),
            'phonenumber' => !empty(trim($data['phone'] ?? '')) ? trim($data['phone']) : '',
        ];
        
        // Only add email if it's not empty
        if (!empty(trim($data['email'] ?? ''))) {
            $client_data['email'] = trim($data['email']);
        }
        
        $client_data['custom_fields'] = [
            'contact_type' => 'school_student'
        ];
        
        $this->clients_model->add($client_data);
    }

    return $student_id;
}
        public function get_cities()
    {
        $this->db->select('city');
        $this->db->from(db_prefix() . 'school_students');
        $this->db->where('city !=', '');
        $this->db->where('city IS NOT NULL');
        $this->db->group_by('city');
        $this->db->order_by('city', 'ASC');
        $query = $this->db->get();
        
        $cities = [];
        foreach ($query->result_array() as $row) {
            $cities[] = $row['city'];
        }
        
        return $cities;
    }

    // Backward compatibility - since district column doesn't exist, return cities instead
    public function get_districts()
    {
        return $this->get_cities();
    }

    private function get_default_country_id()
    {
        // Try to get the first available country
        $this->db->select('id');
        $this->db->limit(1);
        $country = $this->db->get(db_prefix() . 'country')->row();
        
        return $country ? $country->id : null;
    }

    private function generate_internal_id()
    {
        $this->db->select('MAX(CAST(SUBSTRING(school_internal_id, 4) AS UNSIGNED)) as max_id');
        $this->db->like('school_internal_id', 'SCH', 'after');
        $result = $this->db->get(db_prefix() . 'school_students')->row();
        
        $next_id = 1;
        if ($result && $result->max_id) {
            $next_id = $result->max_id + 1;
        }
        
        return 'SCH' . str_pad($next_id, 3, '0', STR_PAD_LEFT);
    }

    private function get_or_create_school_name_id($school_name)
    {
        if (empty($school_name)) {
            return null;
        }
        
        // Check if school name exists
        $this->db->where('name', $school_name);
        $school = $this->db->get(db_prefix() . 'school_name')->row();
        
        if ($school) {
            return $school->id;
        }
        
        // Create new school name
        $this->db->insert(db_prefix() . 'school_name', ['name' => $school_name]);
        return $this->db->insert_id();
    }

    public function get_all()
{
    $this->db->select('ss.*, sn.name as school_name, c.name as country_name, b.name as bank_name');
    $this->db->from(db_prefix() . 'school_students ss');
    $this->db->join(db_prefix() . 'school_name sn', 'sn.id = ss.school_name_id', 'left');
    $this->db->join(db_prefix() . 'country c', 'c.id = ss.country_id', 'left');
    $this->db->join(db_prefix() . 'bank b', 'b.id = ss.bank_id', 'left');
    $results = $this->db->get()->result_array();
    
    // Map each result to expected field names
    $mapped_results = [];
    foreach ($results as $result) {
        $mapped_results[] = [
            'id' => $result['id'] ?? '',
            'name' => $result['name'] ?? '',
            'email' => $result['email'] ?? '',
            'phone' => $result['contact_no'] ?? '', // contact_no -> phone
            'grade' => $result['school_grade'] ?? '', // school_grade -> grade
            'school_name' => $result['school_name'] ?? '',
            'district' => $result['city'] ?? '', // Using city as district
            'dob' => $result['school_student_dob'] ?? '',
            'father_name' => $result['school_father_name'] ?? '',
            'mother_name' => $result['school_mother_name'] ?? '',
            'guardian_name' => $result['school_guardian_name'] ?? '',
            'sponsors' => '', // This field doesn't exist in DB
            'sponsorship_start' => $result['school_sponsorship_start_date'] ?? '',
            'sponsorship_end' => $result['school_sponsorship_end_date'] ?? '',
            'address' => $result['address'] ?? '',
            'city' => $result['city'] ?? '',
            
            // Keep original database fields for backward compatibility
            'contact_no' => $result['contact_no'] ?? '',
            'school_grade' => $result['school_grade'] ?? '',
            'school_student_dob' => $result['school_student_dob'] ?? '',
            'school_sponsorship_start_date' => $result['school_sponsorship_start_date'] ?? '',
            'school_sponsorship_end_date' => $result['school_sponsorship_end_date'] ?? '',
            'school_father_name' => $result['school_father_name'] ?? '',
            'school_mother_name' => $result['school_mother_name'] ?? '',
            'school_guardian_name' => $result['school_guardian_name'] ?? '',
        ];
    }
    
    return $mapped_results;
}

    public function count_all()
    {
        return $this->db->count_all_results(db_prefix() . 'school_students');
    }

    public function get($id)
    {
        return $this->db->get_where(db_prefix() . 'school_students', ['id' => $id])->row_array();
    }

public function get_by_id($student_id)
{
    $this->db->select('ss.*, sn.name as school_name, c.name as country_name, b.name as bank_name');
    $this->db->from(db_prefix() . 'school_students ss');
    $this->db->join(db_prefix() . 'school_name sn', 'sn.id = ss.school_name_id', 'left');
    $this->db->join(db_prefix() . 'country c', 'c.id = ss.country_id', 'left');
    $this->db->join(db_prefix() . 'bank b', 'b.id = ss.bank_id', 'left');
    $this->db->where('ss.id', $student_id);
    $result = $this->db->get()->row_array();
    
    if (!$result) {
        return null;
    }
    
    // Map database fields to form field names to avoid undefined key errors
    $mapped_result = [
        'id' => $result['id'] ?? '',
        'name' => $result['name'] ?? '',
        'email' => $result['email'] ?? '',
        'phone' => $result['contact_no'] ?? '', // contact_no -> phone
        'grade' => $result['school_grade'] ?? '', // school_grade -> grade
        'school_name' => $result['school_name'] ?? '',
        'school_id' => $result['school_id'] ?? '',
        'school_type' => $result['school_type'] ?? '',
        'dob' => $result['school_student_dob'] ?? '', // school_student_dob -> dob
        'address' => $result['address'] ?? '',
        'city' => $result['city'] ?? '',
        'zip' => $result['zip'] ?? '',
        'postal_code' => $result['zip'] ?? '', // zip -> postal_code
        'country' => $result['country_name'] ?? '',
        'country_name' => $result['country_name'] ?? '',
        'district' => $result['city'] ?? '', // Using city as district since district column doesn't exist
        
        // Sponsorship fields
        'sponsorship_start' => $result['school_sponsorship_start_date'] ?? '',
        'sponsorship_end' => $result['school_sponsorship_end_date'] ?? '',
        'sponsors' => '', // This field doesn't exist in DB, set as empty
        
        // Introduction fields
        'introduced_by' => $result['school_introducedby'] ?? '',
        'introduced_phone' => $result['school_introducedph'] ?? '',
        
        // Bank fields
        'bank_account_number' => $result['school_bank_account_no'] ?? '',
        'bank_branch_number' => '', // This field doesn't exist in DB
        'bank_branch_info' => '', // This field doesn't exist in DB
        'bank_name' => $result['bank_name'] ?? '',
        
        // Family fields
        'father_name' => $result['school_father_name'] ?? '',
        'father_income' => $result['school_father_income'] ?? '',
        'mother_name' => $result['school_mother_name'] ?? '',
        'mother_income' => $result['school_mother_income'] ?? '',
        'guardian_name' => $result['school_guardian_name'] ?? '',
        'guardian_income' => $result['school_guardian_income'] ?? '',
        
        // Additional info
        'background_information' => $result['background_info'] ?? '',
        'internal_comment' => $result['internal_comment'] ?? '',
        'external_comment' => $result['external_comment'] ?? '',
        
        // Academic fields (these don't exist in current DB structure)
        'graduation_exam_date' => '',
        'academic_year' => '',
        'term' => '',
        'overall_grade' => '',
        'percentage' => '',
        'class_rank' => '',
        'attendance' => '',
        'teacher_comments' => '',
        'subjects_performance' => '',
        
        // Keep original database fields for backward compatibility
        'contact_no' => $result['contact_no'] ?? '',
        'school_grade' => $result['school_grade'] ?? '',
        'school_student_dob' => $result['school_student_dob'] ?? '',
        'school_sponsorship_start_date' => $result['school_sponsorship_start_date'] ?? '',
        'school_sponsorship_end_date' => $result['school_sponsorship_end_date'] ?? '',
        'school_introducedby' => $result['school_introducedby'] ?? '',
        'school_introducedph' => $result['school_introducedph'] ?? '',
        'school_bank_account_no' => $result['school_bank_account_no'] ?? '',
        'school_father_name' => $result['school_father_name'] ?? '',
        'school_father_income' => $result['school_father_income'] ?? '',
        'school_mother_name' => $result['school_mother_name'] ?? '',
        'school_mother_income' => $result['school_mother_income'] ?? '',
        'school_guardian_name' => $result['school_guardian_name'] ?? '',
        'school_guardian_income' => $result['school_guardian_income'] ?? '',
        'background_info' => $result['background_info'] ?? '',
    ];
    
    return $mapped_result;
}

public function update($data, $id)
{
    // Prepare update data carefully
    $update_data = [];
    
    // Text fields - only update if provided or explicitly empty
    $text_fields = [
        'name', 'email', 'contact_no', 'address', 'city', 'zip',
        'school_id', 'school_type', 'school_grade',
        'school_introducedby', 'school_introducedph',
        'school_father_name', 'school_mother_name', 'school_guardian_name',
        'background_info', 'internal_comment', 'external_comment'
    ];
    
    foreach ($text_fields as $field) {
        if (isset($data[$field])) {
            $update_data[$field] = !empty($data[$field]) ? $data[$field] : null;
        }
    }
    
    // Special handling for school_name_id
    if (isset($data['school_name'])) {
        $update_data['school_name_id'] = $this->get_or_create_school_name_id($data['school_name']);
    }
    
    // Date fields
    $date_fields = [
        'school_student_dob' => 'dob',
        'school_sponsorship_start_date' => 'sponsorship_start',
        'school_sponsorship_end_date' => 'sponsorship_end'
    ];
    
    foreach ($date_fields as $db_field => $form_field) {
        if (isset($data[$form_field])) {
            $update_data[$db_field] = !empty($data[$form_field]) ? $data[$form_field] : null;
        }
    }
    
    // Numeric fields - ensure they're properly cast
    $numeric_fields = [
        'school_father_income' => 'father_income',
        'school_mother_income' => 'mother_income',
        'school_guardian_income' => 'guardian_income'
    ];
    
    foreach ($numeric_fields as $db_field => $form_field) {
        if (isset($data[$form_field])) {
            $update_data[$db_field] = !empty($data[$form_field]) ? (float)$data[$form_field] : 0;
        }
    }
    
    // Bank account field
    if (isset($data['bank_account_number'])) {
        $update_data['school_bank_account_no'] = !empty($data['bank_account_number']) ? $data['bank_account_number'] : null;
    }
    
    // Debug: Check what's being updated
    log_activity('School Student Update Data: ' . json_encode($update_data));
    
    if (!empty($update_data)) {
        $this->db->where('id', $id);
        $result = $this->db->update(db_prefix() . 'school_students', $update_data);
        
        // Check if update was successful
        if ($this->db->affected_rows() > 0) {
            log_activity('School student updated successfully. ID: ' . $id);
        } else {
            log_activity('No changes detected in school student update. ID: ' . $id);
        }
        
        return $result;
    }
    
    log_activity('No update data provided for school student. ID: ' . $id);
    return false;
}

    public function update_student($data, $student_id)
    {
        return $this->update($data, $student_id);
    }

    public function delete($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete(db_prefix() . 'school_students');
    }

    public function delete_student($id)
    {
        return $this->delete($id);
    }

    public function get_students_filtered($filters = [])
    {
        $this->db->select('ss.*, sn.name as school_name');
        $this->db->from(db_prefix() . 'school_students ss');
        $this->db->join(db_prefix() . 'school_name sn', 'sn.id = ss.school_name_id', 'left');

        if (!empty($filters['grade'])) {
            $this->db->where('ss.school_grade', $filters['grade']);
        }

        if (!empty($filters['city'])) {
            $this->db->like('ss.city', $filters['city']);
        }

        if (!empty($filters['school_name'])) {
            $this->db->like('sn.name', $filters['school_name']);
        }

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
        $this->db->select('sn.name as school_name');
        $this->db->from(db_prefix() . 'school_students ss');
        $this->db->join(db_prefix() . 'school_name sn', 'sn.id = ss.school_name_id', 'inner');
        $this->db->group_by('sn.name');
        $this->db->order_by('sn.name', 'ASC');
        $query = $this->db->get();
        
        $schools = [];
        foreach ($query->result_array() as $row) {
            $schools[] = $row['school_name'];
        }
        
        return $schools;
    }

    // These methods appear to be controller methods mixed in the model - they should be moved to the controller
    public function get_student()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        if ($this->input->post()) {
            $student_id = $this->input->post('student_id');
            $student_data = $this->get_by_id($student_id);

            if ($student_data) {
                $student = $student_data;

                if ($this->input->post('action') == 'view') {
                    $html = '<div class="row">
                        <div class="col-md-6">
                            <h5><strong>Student Information</strong></h5>
                            <p><strong>Name:</strong> ' . htmlspecialchars($student['name']) . '</p>
                            <p><strong>Grade:</strong> ' . htmlspecialchars($student['school_grade'] ?? 'Not set') . '</p>
                            <p><strong>School:</strong> ' . htmlspecialchars($student['school_name'] ?? 'Not provided') . '</p>
                            <p><strong>Email:</strong> ' . htmlspecialchars($student['email'] ?? 'Not provided') . '</p>
                            <p><strong>Phone:</strong> ' . htmlspecialchars($student['contact_no'] ?? 'Not provided') . '</p>
                            <p><strong>Date of Birth:</strong> ' . htmlspecialchars($student['school_student_dob'] ?? 'Not provided') . '</p>
                        </div>
                        <div class="col-md-6">
                            <h5><strong>Contact Information</strong></h5>
                            <p><strong>City:</strong> ' . htmlspecialchars($student['city'] ?? 'Not provided') . '</p>
                            <p><strong>Address:</strong> ' . htmlspecialchars($student['address'] ?? 'Not provided') . '</p>
                            <p><strong>Postal Code:</strong> ' . htmlspecialchars($student['zip'] ?? 'Not provided') . '</p>
                            <p><strong>Country:</strong> ' . htmlspecialchars($student['country_name'] ?? 'Not provided') . '</p>
                        </div>
                    </div>
                    <div class="row" style="margin-top: 20px;">
                        <div class="col-md-6">
                            <h5><strong>Family Information</strong></h5>
                            <p><strong>Father\'s Name:</strong> ' . htmlspecialchars($student['school_father_name'] ?? 'Not provided') . '</p>
                            <p><strong>Mother\'s Name:</strong> ' . htmlspecialchars($student['school_mother_name'] ?? 'Not provided') . '</p>
                            <p><strong>Guardian:</strong> ' . htmlspecialchars($student['school_guardian_name'] ?? 'Not provided') . '</p>
                        </div>
                        <div class="col-md-6">
                            <h5><strong>Sponsorship Information</strong></h5>
                            <p><strong>Sponsorship Start:</strong> ' . htmlspecialchars($student['school_sponsorship_start_date'] ?? 'Not set') . '</p>
                            <p><strong>Sponsorship End:</strong> ' . htmlspecialchars($student['school_sponsorship_end_date'] ?? 'Not set') . '</p>
                            <p><strong>Introduced by:</strong> ' . htmlspecialchars($student['school_introducedby'] ?? 'Not provided') . '</p>
                            <p><strong>Introducer\'s Phone:</strong> ' . htmlspecialchars($student['school_introducedph'] ?? 'Not provided') . '</p>
                        </div>
                    </div>';
                    
                    echo json_encode(['success' => true, 'html' => $html]);
                } else {
                    echo json_encode(['success' => true, 'student' => $student]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Student not found']);
            }
        }
    }

    public function export_students()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        $students = $this->get_all();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="school_students_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // CSV headers
        fputcsv($output, [
            'ID', 'Name', 'Email', 'Phone', 'Grade', 'School Name', 'City',
            'Date of Birth', 'Father Name', 'Mother Name', 'Guardian Name',
            'Sponsorship Start', 'Sponsorship End', 'Address'
        ]);

        // CSV rows
        foreach ($students as $student) {
            fputcsv($output, [
                $student['id'],
                $student['name'],
                $student['email'],
                $student['contact_no'],
                $student['school_grade'],
                $student['school_name'],
                $student['city'],
                $student['school_student_dob'],
                $student['school_father_name'],
                $student['school_mother_name'],
                $student['school_guardian_name'],
                $student['school_sponsorship_start_date'],
                $student['school_sponsorship_end_date'],
                $student['address']
            ]);
        }

        fclose($output);
    }
}