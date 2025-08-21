<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Student_sponsor_portal extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        // $this->load->model('student_sponsor_portal/school_model');
        // $this->load->model('student_sponsor_portal/university_model');
        // $this->load->model('student_sponsor_portal/sponsor_model');
        $this->load->model('student_sponsor_portal/school_model', 'student_model');
        $this->load->model('student_sponsor_portal/sponsor_model');
        $this->load->model('student_sponsor_portal/university_model');
        $this->load->model('student_sponsor_portal/school_model'); // Already aliased above, but needed for other references
    }

    public function index()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        $data['title'] = 'Student Sponsor Portal Dashboard';
        $data['school_count'] = $this->school_model->count_all();
        $data['university_count'] = $this->university_model->count_all();
        $data['sponsor_count'] = $this->sponsor_model->count_all();


        $data['school_students'] = $this->school_model->get_all(); 
        $data['university_students'] = $this->university_model->get_all(); 
        $data['sponsors'] = $this->sponsor_model->get_all(); 

        $this->load->view('student_sponsor_portal/dashboard', $data);
        
    }

    // REPLACE your existing school_form() method with this:
    public function school_form()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        $data['title'] = 'School Students Management';
        $data['school_students'] = $this->school_model->get_all();
        
        $this->load->view('student_sponsor_portal/school_students_list', $data);
    }

   
// ADD these 3 new methods:


    public function save_school_student()
    {
        // Debug logging
        error_log("=== SAVE SCHOOL STUDENT DEBUG ===");
        error_log("POST data: " . print_r($_POST, true));
        error_log("RAW POST: " . file_get_contents('php://input'));
        
        if ($this->input->post()) {
            $student_id = $this->input->post('student_id');
            $post_data = $this->input->post();
            
            error_log("Student ID from POST: " . ($student_id ? $student_id : 'EMPTY'));
            error_log("Student ID type: " . gettype($student_id));
            error_log("Student ID length: " . strlen($student_id));
            error_log("Is student_id empty? " . (empty($student_id) ? 'YES' : 'NO'));
            error_log("Is student_id null? " . (is_null($student_id) ? 'YES' : 'NO'));
            error_log("Student ID === ''? " . ($student_id === '' ? 'YES' : 'NO'));
            
            // Remove student_id from post_data to avoid it being inserted as a field
            unset($post_data['student_id']);
            
            // Check for student_id more thoroughly
            if (empty($student_id) || $student_id === '' || $student_id === '0' || is_null($student_id)) {
                // Create new student
                if (!has_permission('student_sponsor_portal', '', 'create')) {
                    echo json_encode(['success' => false, 'message' => 'No permission to create']);
                    return;
                }
                
                error_log("CREATING new student");
                $result = $this->school_model->add($post_data);
                $message = 'School student created successfully';
            } else {
                // Update existing student
                if (!has_permission('student_sponsor_portal', '', 'edit')) {
                    echo json_encode(['success' => false, 'message' => 'No permission to edit']);
                    return;
                }
                
                error_log("UPDATING student with ID: " . $student_id);
                
                // Verify student exists before updating
                $existing_student = $this->school_model->get($student_id);
                if (!$existing_student) {
                    error_log("Student with ID $student_id not found");
                    echo json_encode(['success' => false, 'message' => 'Student not found']);
                    return;
                }
                
                $result = $this->school_model->update($post_data, $student_id);
                $message = 'School student updated successfully';
            }

            error_log("Operation result: " . ($result ? 'SUCCESS' : 'FAILED'));
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => $message]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error occurred']);
            }
        } else {
            error_log("No POST data received");
            echo json_encode(['success' => false, 'message' => 'No data received']);
        }
    }

    public function university_form()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        if ($this->input->post()) {
            if (!has_permission('student_sponsor_portal', '', 'create')) {
                access_denied('student_sponsor_portal');
            }
            
            $id = $this->university_model->add($this->input->post());
            if ($id) {
                set_alert('success', 'University student registered successfully');
                redirect(admin_url('student_sponsor_portal/university_form'));
            }
        }
        
        $data['title'] = 'Register University Student';
        $this->load->view('student_sponsor_portal/university_form', $data);
    }

    public function get_stats()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        $stats = [
            'school_students' => $this->school_model->count_all(),
            'university_students' => $this->university_model->count_all(),
            'sponsors' => $this->sponsor_model->count_all(),
        ];

        echo json_encode($stats);
    }


    public function save_sponsor()
    {
        if (!has_permission('student_sponsor_portal', '', 'create')) {
            access_denied('student_sponsor_portal');
        }

        if ($this->input->post()) {
            $sponsor_id = $this->input->post('sponsor_id');
            $post_data = $this->input->post();

            if (empty($sponsor_id)) {
                // Create new sponsor
                $result = $this->sponsor_model->add($post_data);
                $message = 'Sponsor created successfully';
            } else {
                // Update existing sponsor
                $result = $this->sponsor_model->update($post_data, $sponsor_id);
                $message = 'Sponsor updated successfully';
            }

            if ($result) {
                echo json_encode(['success' => true, 'message' => $message]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error saving sponsor']);
            }
        }
    }
    // Replace your existing sponsor_form() method with this updated version

 public function sponsors()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        $data['title'] = 'Sponsor Management';
        $data['sponsors'] = $this->sponsor_model->get_all();
        
        $this->load->view('student_sponsor_portal/sponsors_list', $data);
    }

     public function sponsor_form($sponsor_id = null)
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        if ($this->input->post()) {
            if (!has_permission('student_sponsor_portal', '', 'create')) {
                access_denied('student_sponsor_portal');
            }
            
            $post_data = $this->input->post();
            $sponsor_id = $this->input->post('sponsor_id');

            // Remove sponsor_id from data array to prevent it from being updated as a column
            unset($post_data['sponsor_id']);

            if (empty($sponsor_id)) {
                // Create new sponsor
                $id = $this->sponsor_model->add($post_data);
                $message = 'Sponsor registered successfully';
            } else {
                // Update existing sponsor
                $id = $this->sponsor_model->update_sponsor($post_data, $sponsor_id);
                $message = 'Sponsor updated successfully';
            }
            
            if ($id) {
                set_alert('success', $message);
                redirect(admin_url('student_sponsor_portal/sponsors'));
            }
        }
        
        $data['title'] = 'Register Sponsor';
        
        // If sponsor_id is provided, load sponsor data for editing
        if ($sponsor_id) {
            $sponsor_data = $this->sponsor_model->get_by_id($sponsor_id);
            if ($sponsor_data) {
                // Convert object to array for compatibility with the view
                $data['sponsor'] = (array) $sponsor_data;
                $data['title'] = 'Edit Sponsor';
            } else {
                set_alert('danger', 'Sponsor not found');
                redirect(admin_url('student_sponsor_portal/sponsors'));
            }
        }
        
        $this->load->view('student_sponsor_portal/sponsor_form', $data);
    }

    // Add these new methods for AJAX operations

    public function get_sponsor()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        if ($this->input->post()) {
            $sponsor_id = $this->input->post('sponsor_id');
            $sponsor_data = $this->sponsor_model->get_by_id($sponsor_id);
            
            if ($sponsor_data) {
                $sponsor = (array) $sponsor_data; // Convert object to array
                
                if ($this->input->post('action') == 'view') {
                    // Return formatted HTML for view modal
                    $html = '<div class="row">
                        <div class="col-md-6">
                            <h5><strong>Basic Information</strong></h5>
                            <p><strong>Name:</strong> ' . htmlspecialchars($sponsor['name']) . '</p>
                            <p><strong>Type:</strong> ' . htmlspecialchars($sponsor['sponsor_type']) . '</p>
                            <p><strong>Email:</strong> ' . htmlspecialchars($sponsor['email']) . '</p>
                            <p><strong>Phone:</strong> ' . htmlspecialchars($sponsor['phone']) . '</p>
                        </div>
                        <div class="col-md-6">
                            <h5><strong>Additional Information</strong></h5>
                            <p><strong>Occupation:</strong> ' . htmlspecialchars($sponsor['sponsor_occupation'] ?? '') . '</p>
                            <p><strong>Address:</strong> ' . htmlspecialchars($sponsor['address'] ?? '') . '</p>
                            <p><strong>City:</strong> ' . htmlspecialchars($sponsor['city'] ?? '') . '</p>
                        </div>
                    </div>';
                    echo json_encode(['success' => true, 'html' => $html]);
                } else {
                    // Return raw data for edit
                    echo json_encode(['success' => true, 'sponsor' => $sponsor]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Sponsor not found']);
            }
        }
    }

    public function delete_sponsor()
    {
        if (!has_permission('student_sponsor_portal', '', 'delete')) {
            access_denied('student_sponsor_portal');
        }

        if ($this->input->post()) {
            $sponsor_id = $this->input->post('sponsor_id');
            $result = $this->sponsor_model->delete_sponsor($sponsor_id);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Sponsor deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error deleting sponsor']);
            }
        }
    }

    public function school_students()
{
    if (!has_permission('student_sponsor_portal', '', 'view')) {
        access_denied('student_sponsor_portal');
    }

    $data['title'] = 'School Students Management';
    $data['school_students'] = $this->school_model->get_all();
    $data['districts'] = $this->school_model->get_districts();
    
    $this->load->view('student_sponsor_portal/school_students_list', $data);
}

/**
 * School student form (add/edit)
 */
public function school_student_form($student_id = null)
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
            $id = $this->school_model->add($post_data);
            $message = 'School student registered successfully';
        } else {
            // Update existing student
            $success = $this->school_model->update_student($post_data, $student_id);
            $message = 'School student updated successfully';
            $id = $success;
        }
        
        if ($id) {
            set_alert('success', $message);
            redirect(admin_url('student_sponsor_portal/school_students'));
        } else {
            set_alert('danger', 'Error saving student');
        }
    }
    
    $data['title'] = 'Register School Student';
    
    // If student_id is provided, load student data for editing
    if ($student_id) {
        $student_data = $this->school_model->get_by_id($student_id);
        if ($student_data) {
            // Data is already an array from get_by_id method
            $data['student'] = $student_data;
            $data['title'] = 'Edit School Student';
        } else {
            set_alert('danger', 'Student not found');
            redirect(admin_url('student_sponsor_portal/school_students'));
        }
    }
    
    $this->load->view('student_sponsor_portal/school_form', $data);
}

/**
 * Get school student data (AJAX)
 */
public function get_school_student()
{
    if (!has_permission('student_sponsor_portal', '', 'view')) {
        access_denied('student_sponsor_portal');
    }

    if ($this->input->post()) {
        $student_id = $this->input->post('student_id');
        $student_data = $this->school_model->get_by_id($student_id);
        
        if ($student_data) {
            // Data is already an array from get_by_id method
            $student = $student_data;
            
            if ($this->input->post('action') == 'view') {
                // Return formatted HTML for view modal
                $html = '<div class="row">
                    <div class="col-md-6">
                        <h5><strong>Student Information</strong></h5>
                        <p><strong>Name:</strong> ' . htmlspecialchars($student['name']) . '</p>
                        <p><strong>Grade:</strong> ' . htmlspecialchars($student['grade']) . '</p>
                        <p><strong>School:</strong> ' . htmlspecialchars($student['school_name']) . '</p>
                        <p><strong>Email:</strong> ' . htmlspecialchars($student['email']) . '</p>
                        <p><strong>Phone:</strong> ' . htmlspecialchars($student['phone']) . '</p>
                        <p><strong>Date of Birth:</strong> ' . htmlspecialchars($student['dob'] ?? 'Not provided') . '</p>
                    </div>
                    <div class="col-md-6">
                        <h5><strong>Additional Information</strong></h5>
                        <p><strong>District:</strong> ' . htmlspecialchars($student['district'] ?? 'Not provided') . '</p>
                        <p><strong>Address:</strong> ' . htmlspecialchars($student['address'] ?? 'Not provided') . '</p>
                        <p><strong>Father\'s Name:</strong> ' . htmlspecialchars($student['father_name'] ?? 'Not provided') . '</p>
                        <p><strong>Mother\'s Name:</strong> ' . htmlspecialchars($student['mother_name'] ?? 'Not provided') . '</p>
                        <p><strong>Guardian:</strong> ' . htmlspecialchars($student['guardian_name'] ?? 'Not provided') . '</p>
                    </div>
                </div>
                <div class="row" style="margin-top: 20px;">
                    <div class="col-md-12">
                        <h5><strong>Sponsorship Information</strong></h5>
                        <p><strong>Sponsors:</strong> ' . htmlspecialchars($student['sponsors'] ?? 'Not assigned') . '</p>
                        <p><strong>Sponsorship Period:</strong> ' . 
                        (($student['sponsorship_start'] ?? false) ? 
                            htmlspecialchars($student['sponsorship_start']) . ' to ' . htmlspecialchars($student['sponsorship_end'] ?? 'Ongoing') 
                            : 'Not set') . '</p>
                        <p><strong>Introduced by:</strong> ' . htmlspecialchars($student['introduced_by'] ?? 'Not provided') . '</p>
                    </div>
                </div>';
                echo json_encode(['success' => true, 'html' => $html]);
            } else {
                // Return raw data for edit
                echo json_encode(['success' => true, 'student' => $student]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Student not found']);
        }
    }
}

/**
 * Delete school student (AJAX)
 */
public function delete_school_student()
{
    if (!is_admin() && !has_permission('student_sponsor_portal', '', 'delete')) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }

    $student_id = $this->input->post('student_id');

    if (!$student_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
        return;
    }

    $this->load->model('student_sponsor_portal/school_model');

    $deleted = $this->school_model->delete($student_id);

    if ($deleted) {
        echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete student']);
    }
}

public function delete($id)
{
    $this->db->where('id', $id);
    return $this->db->delete(db_prefix() . 'school_students');
}


/**
 * Export students to Excel/CSV
 */
public function export_students()
{
    if (!has_permission('student_sponsor_portal', '', 'view')) {
        access_denied('student_sponsor_portal');
    }

    $students = $this->school_model->get_all();
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="school_students_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, [
        'ID', 'Name', 'Email', 'Phone', 'Grade', 'School Name', 'District', 
        'Date of Birth', 'Father Name', 'Mother Name', 'Guardian Name',
        'Sponsors', 'Sponsorship Start', 'Sponsorship End', 'Address'
    ]);
    
    // Add student data
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
?>