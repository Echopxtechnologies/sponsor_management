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
        $insert_data = [
            'name'                     => $data['name'] ?? '',
            'email'                    => $data['email'] ?? '',
            'phone'                    => $data['phone'] ?? '',
            'grade'                    => $data['grade'] ?? '',
            'dob'                      => $data['dob'] ?? null,
            'school_id'                => $data['school_id'] ?? '',
            'school_type'              => $data['school_type'] ?? '',
            'school_name'              => $data['school_name'] ?? '',
            'graduation_exam_date'     => $data['graduation_exam_date'] ?? null,
            'father_name'              => $data['father_name'] ?? '',
            'mother_name'              => $data['mother_name'] ?? '',
            'father_income'            => $data['father_income'] ?? '',
            'mother_income'            => $data['mother_income'] ?? '',
            'guardian_name'            => $data['guardian_name'] ?? '',
            'guardian_income'          => $data['guardian_income'] ?? '',
            'sponsorship_start'        => $data['sponsorship_start'] ?? null,
            'sponsorship_end'          => $data['sponsorship_end'] ?? null,
            'sponsors'                 => $data['sponsors'] ?? '',
            'introduced_by'            => $data['introduced_by'] ?? '',
            'introduced_phone'         => $data['introduced_phone'] ?? '',
            'bank_name'                => $data['bank_name'] ?? '',
            'bank_branch_number'       => $data['bank_branch_number'] ?? '',
            'bank_branch_info'         => $data['bank_branch_info'] ?? '',
            'bank_account_number'      => $data['bank_account_number'] ?? '',
            'address'                  => $data['address'] ?? '',
            'district'                 => $data['district'] ?? '',
            'postal_code'              => $data['postal_code'] ?? '',
            'country'                  => $data['country'] ?? 'Sri Lanka',
            'background_information'   => $data['background_information'] ?? '',
            'internal_comment'         => $data['internal_comment'] ?? '',
            'external_comment'         => $data['external_comment'] ?? '',
            // Report card fields
            'academic_year'            => $data['academic_year'] ?? '',
            'term'                     => $data['term'] ?? '',
            'overall_grade'            => $data['overall_grade'] ?? '',
            'percentage'               => $data['percentage'] ?? null,
            'class_rank'               => $data['class_rank'] ?? null,
            'attendance'               => $data['attendance'] ?? null,
            'teacher_comments'         => $data['teacher_comments'] ?? '',
            'subjects_performance'     => $data['subjects_performance'] ?? ''
        ];

        $this->db->insert(db_prefix() . 'school_students', $insert_data);
        $student_id = $this->db->insert_id();

        // Also create a client record
        $this->load->model('clients_model');
        $this->clients_model->add([
            'firstname' => $data['name'] ?? '',
            'email'     => $data['email'] ?? '',
            'phonenumber' => $data['phone'] ?? '',
            'custom_fields' => [
                'contact_type' => 'school_student'
            ]
        ]);

        return $student_id;
    }

    public function get_all()
    {
        return $this->db->get(db_prefix() . 'school_students')->result_array();
    }

    public function count_all()
    {
        return $this->db->count_all_results(db_prefix() . 'school_students');
    }

    /**
     * Get single student as array
     */
    public function get($id)
    {
        return $this->db->get_where(db_prefix() . 'school_students', ['id' => $id])->row_array();
    }

    /**
     * Get single student by ID (consistent naming)
     */
    public function get_by_id($student_id)
    {
        $this->db->where('id', $student_id);
        return $this->db->get(db_prefix() . 'school_students')->row_array();
    }

    /**
     * Update student method
     */
    public function update($data, $id)
    {
        $update_data = [
            'name'                     => $data['name'] ?? '',
            'email'                    => $data['email'] ?? '',
            'phone'                    => $data['phone'] ?? '',
            'grade'                    => $data['grade'] ?? '',
            'dob'                      => $data['dob'] ?? null,
            'school_id'                => $data['school_id'] ?? '',
            'school_type'              => $data['school_type'] ?? '',
            'school_name'              => $data['school_name'] ?? '',
            'graduation_exam_date'     => $data['graduation_exam_date'] ?? null,
            'father_name'              => $data['father_name'] ?? '',
            'mother_name'              => $data['mother_name'] ?? '',
            'father_income'            => $data['father_income'] ?? '',
            'mother_income'            => $data['mother_income'] ?? '',
            'guardian_name'            => $data['guardian_name'] ?? '',
            'guardian_income'          => $data['guardian_income'] ?? '',
            'sponsorship_start'        => $data['sponsorship_start'] ?? null,
            'sponsorship_end'          => $data['sponsorship_end'] ?? null,
            'sponsors'                 => $data['sponsors'] ?? '',
            'introduced_by'            => $data['introduced_by'] ?? '',
            'introduced_phone'         => $data['introduced_phone'] ?? '',
            'bank_name'                => $data['bank_name'] ?? '',
            'bank_branch_number'       => $data['bank_branch_number'] ?? '',
            'bank_branch_info'         => $data['bank_branch_info'] ?? '',
            'bank_account_number'      => $data['bank_account_number'] ?? '',
            'address'                  => $data['address'] ?? '',
            'district'                 => $data['district'] ?? '',
            'postal_code'              => $data['postal_code'] ?? '',
            'country'                  => $data['country'] ?? 'Sri Lanka',
            'background_information'   => $data['background_information'] ?? '',
            'internal_comment'         => $data['internal_comment'] ?? '',
            'external_comment'         => $data['external_comment'] ?? '',
            // Report card fields
            'academic_year'            => $data['academic_year'] ?? '',
            'term'                     => $data['term'] ?? '',
            'overall_grade'            => $data['overall_grade'] ?? '',
            'percentage'               => $data['percentage'] ?? null,
            'class_rank'               => $data['class_rank'] ?? null,
            'attendance'               => $data['attendance'] ?? null,
            'teacher_comments'         => $data['teacher_comments'] ?? '',
            'subjects_performance'     => $data['subjects_performance'] ?? ''
        ];

        $this->db->where('id', $id);
        return $this->db->update(db_prefix() . 'school_students', $update_data);
    }

    /**
     * Update student (alternative method name for consistency)
     */
    public function update_student($data, $student_id)
    {
        return $this->update($data, $student_id);
    }

    /**
     * Delete student method
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete(db_prefix() . 'school_students');
    }

    /**
     * Delete student (alternative method name for consistency)
     */

    /**
     * Get students with filters
     */
    public function get_students_filtered($filters = [])
    {
        $this->db->select('*');
        $this->db->from(db_prefix() . 'school_students');

        if (!empty($filters['grade'])) {
            $this->db->where('grade', $filters['grade']);
        }

        if (!empty($filters['district'])) {
            $this->db->like('district', $filters['district']);
        }

        if (!empty($filters['school_name'])) {
            $this->db->like('school_name', $filters['school_name']);
        }

        if (!empty($filters['search'])) {
            $this->db->group_start();
            $this->db->like('name', $filters['search']);
            $this->db->or_like('email', $filters['search']);
            $this->db->or_like('phone', $filters['search']);
            $this->db->group_end();
        }

        $this->db->order_by('id', 'DESC');
        return $this->db->get()->result_array();
    }

    /**
     * Get unique districts for filter dropdown
     */
    public function get_districts()
    {
        $this->db->select('district');
        $this->db->from(db_prefix() . 'school_students');
        $this->db->where('district !=', '');
        $this->db->where('district IS NOT NULL');
        $this->db->group_by('district');
        $this->db->order_by('district', 'ASC');
        $query = $this->db->get();
        
        $districts = [];
        foreach ($query->result_array() as $row) {
            $districts[] = $row['district'];
        }
        
        return $districts;
    }

    /**
     * Get unique schools for filter dropdown
     */
    public function get_schools()
    {
        $this->db->select('school_name');
        $this->db->from(db_prefix() . 'school_students');
        $this->db->where('school_name !=', '');
        $this->db->where('school_name IS NOT NULL');
        $this->db->group_by('school_name');
        $this->db->order_by('school_name', 'ASC');
        $query = $this->db->get();
        
        $schools = [];
        foreach ($query->result_array() as $row) {
            $schools[] = $row['school_name'];
        }
        
        return $schools;
    }
    public function students()
{
    if (!has_permission('student_sponsor_portal', '', 'view')) {
        access_denied('student_sponsor_portal');
    }

    $data['title'] = 'Student Management';
    $data['students'] = $this->student_model->get_all();
    
    $this->load->view('student_sponsor_portal/students_list', $data);
}

public function student_form($student_id = null)
{
    if (!has_permission('student_sponsor_portal', '', 'view')) {
        access_denied('student_sponsor_portal');
    }

    if ($this->input->post()) {
        if (!has_permission('student_sponsor_portal', '', 'create')) {
            access_denied('student_sponsor_portal');
        }
        
        $post_data = $this->input->post();
        $student_id = $this->input->post('student_id');

        // Remove student_id from data array
        unset($post_data['student_id']);

        if (empty($student_id)) {
            // Create new student
            $id = $this->student_model->add($post_data);
            $message = 'Student added successfully';
        } else {
            // Update existing student
            $success = $this->student_model->update_student($post_data, $student_id);
            $message = 'Student updated successfully';
            $id = $success;
        }
        
        if ($id) {
            set_alert('success', $message);
            redirect(admin_url('student_sponsor_portal/students'));
        } else {
            set_alert('danger', 'Error saving student');
        }
    }
    
    $data['title'] = 'Add New Student';
    
    // If student_id is provided, load student data for editing
    if ($student_id) {
        $student_data = $this->student_model->get_by_id($student_id);
        if ($student_data) {
            // Data is already an array from get_by_id method
            $data['student'] = $student_data;
            $data['title'] = 'Edit Student';
        } else {
            set_alert('danger', 'Student not found');
            redirect(admin_url('student_sponsor_portal/students'));
        }
    }
    
    $this->load->view('student_sponsor_portal/student_form', $data);
}

public function get_student()
{
    if (!has_permission('student_sponsor_portal', '', 'view')) {
        access_denied('student_sponsor_portal');
    }

    if ($this->input->post()) {
        $student_id = $this->input->post('student_id');
        $student_data = $this->student_model->get_by_id($student_id);

        if ($student_data) {
            $student = $student_data;

            if ($this->input->post('action') == 'view') {
                $html = '<div class="row">
                    <div class="col-md-6">
                        <h5><strong>Student Information</strong></h5>
                        <p><strong>Name:</strong> ' . htmlspecialchars($student['name']) . '</p>
                        <p><strong>Grade:</strong> ' . htmlspecialchars($student['grade'] ?? 'Not set') . '</p>
                        <p><strong>School:</strong> ' . htmlspecialchars($student['school_name'] ?? 'Not provided') . '</p>
                        <p><strong>Email:</strong> ' . htmlspecialchars($student['email'] ?? 'Not provided') . '</p>
                        <p><strong>Phone:</strong> ' . htmlspecialchars($student['phone'] ?? 'Not provided') . '</p>
                        <p><strong>Date of Birth:</strong> ' . htmlspecialchars($student['dob'] ?? 'Not provided') . '</p>
                    </div>
                    <div class="col-md-6">
                        <h5><strong>Contact Information</strong></h5>
                        <p><strong>District:</strong> ' . htmlspecialchars($student['district'] ?? 'Not provided') . '</p>
                        <p><strong>Address:</strong> ' . htmlspecialchars($student['address'] ?? 'Not provided') . '</p>
                        <p><strong>Postal Code:</strong> ' . htmlspecialchars($student['postal_code'] ?? 'Not provided') . '</p>
                        <p><strong>Country:</strong> ' . htmlspecialchars($student['country'] ?? 'Not provided') . '</p>
                    </div>
                </div>
                <div class="row" style="margin-top: 20px;">
                    <div class="col-md-6">
                        <h5><strong>Family Information</strong></h5>
                        <p><strong>Father\'s Name:</strong> ' . htmlspecialchars($student['father_name'] ?? 'Not provided') . '</p>
                        <p><strong>Mother\'s Name:</strong> ' . htmlspecialchars($student['mother_name'] ?? 'Not provided') . '</p>
                        <p><strong>Guardian:</strong> ' . htmlspecialchars($student['guardian_name'] ?? 'Not provided') . '</p>
                    </div>
                    <div class="col-md-6">
                        <h5><strong>Sponsorship Information</strong></h5>
                        <p><strong>Sponsors:</strong> ' . htmlspecialchars($student['sponsors'] ?? 'Not assigned') . '</p>
                        <p><strong>Sponsorship Start:</strong> ' . htmlspecialchars($student['sponsorship_start'] ?? 'Not set') . '</p>
                        <p><strong>Sponsorship End:</strong> ' . htmlspecialchars($student['sponsorship_end'] ?? 'Not set') . '</p>
                        <p><strong>Introduced by:</strong> ' . htmlspecialchars($student['introduced_by'] ?? 'Not provided') . '</p>
                        <p><strong>Introducer\'s Phone:</strong> ' . htmlspecialchars($student['introduced_phone'] ?? 'Not provided') . '</p>
                    </div>
                </div>
                <div class="row" style="margin-top: 20px;">
                    <div class="col-md-12">
                        <h5><strong>Academic Information</strong></h5>
                        <p><strong>Academic Year:</strong> ' . htmlspecialchars($student['academic_year'] ?? 'Not set') . '</p>
                        <p><strong>Term:</strong> ' . htmlspecialchars($student['term'] ?? 'Not set') . '</p>
                        <p><strong>Overall Grade:</strong> ' . htmlspecialchars($student['overall_grade'] ?? 'Not set') . '</p>
                        <p><strong>Percentage:</strong> ' . htmlspecialchars($student['percentage'] ?? 'Not set') . '%</p>
                        <p><strong>Class Rank:</strong> ' . htmlspecialchars($student['class_rank'] ?? 'Not set') . '</p>
                        <p><strong>Attendance:</strong> ' . htmlspecialchars($student['attendance'] ?? 'Not set') . '%</p>
                        <p><strong>Teacher Comments:</strong> ' . htmlspecialchars($student['teacher_comments'] ?? 'Not set') . '</p>
                        <p><strong>Subjects Performance:</strong> ' . htmlspecialchars($student['subjects_performance'] ?? 'Not set') . '</p>
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

public function delete_student()
{
    if (!has_permission('student_sponsor_portal', '', 'delete')) {
        access_denied('student_sponsor_portal');
    }

    if ($this->input->post()) {
        $student_id = $this->input->post('student_id');
        $result = $this->student_model->delete_student($student_id);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting student']);
        }
    }
}

/**
 * Export students to Excel/CSV
 */
public function export_students()
{
    if (!has_permission('student_sponsor_portal', '', 'view')) {
        access_denied('student_sponsor_portal');
    }

    $students = $this->student_model->get_all();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="school_students_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // CSV headers
    fputcsv($output, [
        'ID', 'Name', 'Email', 'Phone', 'Grade', 'School Name', 'District',
        'Date of Birth', 'Father Name', 'Mother Name', 'Guardian Name',
        'Sponsors', 'Sponsorship Start', 'Sponsorship End', 'Address'
    ]);

    // CSV rows
    foreach ($students as $student) {
        fputcsv($output, [
            $student['id'],
            $student['name'],
            $student['email'],
            $student['phone'],
            $student['grade'],
            $student['school_name'],
            $student['district'],
            $student['dob'],
            $student['father_name'],
            $student['mother_name'],
            $student['guardian_name'],
            $student['sponsors'],
            $student['sponsorship_start'],
            $student['sponsorship_end'],
            $student['address']
        ]);
    }

    fclose($output);
}

}