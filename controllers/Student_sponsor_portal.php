<?php defined('BASEPATH') or exit('No direct script access allowed');

/* (Optional) add permissions on install */
hooks()->add_action('after_module_activation', function($module){
    if ($module === 'student_sponsor_portal') {
        $cap = [
            'capabilities' => [
                'view'   => 1,
                'create' => 1,
                'edit'   => 1,
                'delete' => 1,
            ],
        ];
        register_staff_capabilities('student_sponsor_portal', $cap, _l('Student Sponsor Portal'));
    }
});

class Student_sponsor_portal extends AdminController
{
    /* ===================== INITIALIZATION ===================== */

    public function __construct()
    {
        parent::__construct();

        $this->load->model('student_sponsor_portal/school_model',     'school_model');
        $this->load->model('student_sponsor_portal/sponsor_model',    'sponsor_model');
        $this->load->model('student_sponsor_portal/university_model', 'university_model');
        $this->load->model('student_sponsor_portal/Sponsor_transactions_model', 'txn_model');
        
        log_message('debug', 'Student_sponsor_portal controller loaded successfully');
    }

    /* ===================== DASHBOARD ===================== */

    public function index()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) access_denied('student_sponsor_portal');

        $data['title']             = 'Student Sponsor Portal Dashboard';
        $data['school_count']      = $this->school_model->count_all();
        $data['university_count']  = $this->university_model->count_all();
        $data['sponsor_count']     = $this->sponsor_model->count_all();

        $data['school_students']    = $this->school_model->get_all();
        $data['university_students']= $this->university_model->get_all();
        $data['sponsors']           = $this->sponsor_model->get_all();

        $this->load->view('student_sponsor_portal/dashboard', $data);
    }

    public function get_stats()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            echo json_encode(['success' => false]); return;
        }

        $payload = [
            'school_count'      => (int) $this->school_model->count_all(),
            'university_count'  => (int) $this->university_model->count_all(),
            'sponsor_count'     => (int) $this->sponsor_model->count_all(),
            'active_school_count'     => (int) $this->db->where('staff_active', 1)->count_all_results(db_prefix().'school_students'),
            'active_university_count' => 0,
            'active_sponsor_count'    => 0,
            'sponsorship_count'       => 0,
            'active_sponsorship_count'   => 0,
            'pending_sponsorship_count'  => 0,
            'completed_sponsorship_count'=> 0,
            'cancelled_sponsorship_count'=> 0,
        ];
        echo json_encode(['success' => true, 'data' => $payload]);
    }




    /* =================================================================================== */
    /* =====================              SCHOOL STUDENTS              =================== */
    /* =================================================================================== */

    public function school_students()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        try {
            log_message('debug', 'Loading school students list');
            
            $data['title'] = 'School Students Management';
            $data['school_students'] = $this->school_model->get_all();
            
            $this->load->view('student_sponsor_portal/school_students_list', $data);
            
        } catch (Exception $e) {
            log_message('error', 'Error in school_students: ' . $e->getMessage());
            show_error('An error occurred while loading school students: ' . $e->getMessage(), 500);
        }
    }

    public function school_student_form($student_id = null)
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) access_denied('student_sponsor_portal');

        try {
            log_message('debug', 'Loading school student form for ID: ' . ($student_id ?: 'new'));

            if ($this->input->post()) {
                $isCreate = empty($this->input->post('student_id'));
                if ($isCreate && !has_permission('student_sponsor_portal', '', 'create')) access_denied('student_sponsor_portal');
                if (!$isCreate && !has_permission('student_sponsor_portal', '', 'edit')) access_denied('student_sponsor_portal');

                $post = $this->input->post();
                $sid = (int)($post['student_id'] ?? 0);
                unset($post['student_id']);

                // Log raw POST data for debugging
                log_message('debug', 'Raw POST data: ' . json_encode($post));

                // Clean and map form data to database fields
                $cleaned_data = $this->clean_school_post_data($post);

                $this->session->set_flashdata('old_input', $post);

                if ($isCreate) {
                    $res = $this->school_model->add($cleaned_data);
                    if (!is_array($res)) { 
                        $res = $res ? ['success' => true, 'id' => $res] : ['success' => false, 'message' => 'Unable to save.']; 
                    }
                    if (!$res['success']) {
                        $this->session->set_flashdata('old_input', $post);
                        set_alert('danger', $res['message'] ?? 'Error saving student');
                        redirect(admin_url('student_sponsor_portal/school_student_form'));
                    }
                    $newId = (int)$res['id'];

                    // Handle profile photo upload
                    $this->handle_profile_photo_upload($newId);

                    // Handle staff creation
                    if (!empty($this->input->post('create_staff'))) {
                        $existing = $this->school_model->get_by_id($newId);
                        $existing_staff_id = $existing['staff_id'] ?? null;

                        $this->ensure_three_min_roles();
                        $staff_id = $this->create_or_update_staff_from_student($this->input->post(), $existing_staff_id);

                        if ($staff_id) {
                            $this->db->where('id', $newId)->update(db_prefix() . 'school_students', [
                                'staff_id' => $staff_id,
                                'staff_active' => !empty($this->input->post('staff_active')) ? 1 : 0,
                            ]);
                        }
                    } else {
                        $this->db->where('id', $newId)->update(db_prefix() . 'school_students', ['staff_active' => 0]);
                    }

                    set_alert('success', 'School student registered successfully');
                    redirect(admin_url('student_sponsor_portal/school_students'));
                } else {
                    // Handle profile photo upload for existing student
                    $this->handle_profile_photo_upload($sid);

                    // Log before update
                    log_message('debug', 'Attempting to update student ID: ' . $sid . ' with data: ' . json_encode($cleaned_data));
                    
                    // Get student data before update for comparison
                    $before_update = $this->school_model->get_by_id($sid);
                    log_message('debug', 'Student data BEFORE update: ' . json_encode($before_update));

                    $ok = $this->school_model->update($cleaned_data, $sid);
                    
                    if ($ok) {
                        // Get student data after update for verification
                        $after_update = $this->school_model->get_by_id($sid);
                        log_message('debug', 'Student data AFTER update: ' . json_encode($after_update));
                        
                        // Check if anything actually changed
                        $changes_made = false;
                        foreach ($cleaned_data as $field => $new_value) {
                            if (isset($before_update[$field]) && $before_update[$field] != $new_value) {
                                $changes_made = true;
                                log_message('debug', "Field '{$field}' changed from '{$before_update[$field]}' to '{$new_value}'");
                            } elseif (!isset($before_update[$field]) && $new_value !== null && $new_value !== '') {
                                $changes_made = true;
                                log_message('debug', "New field '{$field}' set to '{$new_value}'");
                            }
                        }
                        
                        if (!$changes_made) {
                            log_message('warning', 'Update reported success but no changes detected in database for student ID: ' . $sid);
                            set_alert('warning', 'Update completed but no changes were detected. Please verify your data.');
                        } else {
                            set_alert('success', 'School student updated successfully');
                        }
                    } else {
                        $err = $this->db->error();
                        log_message('error', 'Database update failed for student ID: ' . $sid . '. Error: ' . json_encode($err));
                        $msg = (!empty($err['code']) && (int)$err['code'] === 1062)
                            ? 'Duplicate value detected (e.g., email). Please use a different value.'
                            : 'Error saving student. Please check all fields and try again.';
                        set_alert('danger', $msg);
                        redirect(admin_url('student_sponsor_portal/school_student_form/' . $sid));
                    }

                    // Handle staff creation for existing student
                    if (!empty($this->input->post('create_staff'))) {
                        $existing = $this->school_model->get_by_id($sid);
                        $existing_staff_id = $existing['staff_id'] ?? null;

                        $this->ensure_three_min_roles();
                        $staff_id = $this->create_or_update_staff_from_student($this->input->post(), $existing_staff_id);

                        if ($staff_id) {
                            $this->db->where('id', $sid)->update(db_prefix() . 'school_students', [
                                'staff_id' => $staff_id,
                                'staff_active' => !empty($this->input->post('staff_active')) ? 1 : 0,
                            ]);
                        }
                    }

                    redirect(admin_url('student_sponsor_portal/school_students'));
                }
            }

            $data['title'] = 'Register School Student';
            if ($student_id) {
                $student = $this->school_model->get_by_id((int)$student_id);
                if (!$student) { 
                    set_alert('danger', 'Student not found');
                    redirect(admin_url('student_sponsor_portal/school_students')); 
                }
                $data['student'] = $student;
                $data['title'] = 'Edit School Student';
            }
            $data['old'] = $this->session->flashdata('old_input') ?: [];
            $data['banks'] = $this->db->select('id,name')->order_by('name', 'ASC')->get(db_prefix() . 'bank')->result_array();
            $data['schools'] = $this->school_model->get_schools();
            $data['countries'] = $this->_get_countries();
            
            $this->load->view('student_sponsor_portal/school_form', $data);
            
        } catch (Exception $e) {
            log_message('error', 'Error in school_student_form: ' . $e->getMessage());
            show_error('An error occurred while loading the form: ' . $e->getMessage(), 500);
        }
    }
    // Add this new method to clean and map form data to database fields
    private function clean_school_post_data($data)
    {
        $cleaned = [];
        
        // Field mappings from form names to database column names (ONLY ACTUAL DB FIELDS)
        $field_mappings = [
            'name' => 'name',
            'email' => 'email', 
            'phone' => 'contact_no',
            'dob' => 'school_student_dob',
            'calculated_age' => 'school_age',
            'country_id' => 'country_id',
            'address' => 'address',
            'city' => 'city',
            'postal_code' => 'zip',
            'school_id' => 'school_id',
            'school_internal_id' => 'school_internal_id',
            'grade' => 'school_grade',
            'school_type' => 'school_type',
            'school_name_id' => 'school_name_id',
            'sponsorship_start' => 'school_sponsorship_start_date',
            'sponsorship_end' => 'school_sponsorship_end_date',
            'introduced_by' => 'school_introducedby',
            'introduced_phone' => 'school_introducedph',
            'bank_id' => 'bank_id',
            'bank_account_number' => 'school_bank_account_no',
            'bank_branch_number' => 'school_bank_branch_number',
            'bank_branch_info' => 'school_bank_branch_info',
            'father_name' => 'school_father_name',
            'father_income' => 'school_father_income',
            'mother_name' => 'school_mother_name', 
            'mother_income' => 'school_mother_income',
            'guardian_name' => 'school_guardian_name',
            'guardian_income' => 'school_guardian_income',
            'background_information' => 'background_info',
            'internal_comment' => 'internal_comment',
            'external_comment' => 'external_comment'
        ];
        
        foreach ($field_mappings as $form_field => $db_field) {
            if (isset($data[$form_field])) {
                $value = is_string($data[$form_field]) ? trim($data[$form_field]) : $data[$form_field];
                
                // Handle different data types
                if ($value === '' || $value === null) {
                    // For updates, we want to actually set empty values, not skip them
                    $cleaned[$db_field] = ($value === '') ? '' : null;
                } elseif (in_array($form_field, ['father_income', 'mother_income', 'guardian_income'])) {
                    $cleaned[$db_field] = is_numeric($value) ? (float)$value : null;
                } elseif (in_array($form_field, ['country_id', 'bank_id', 'school_name_id', 'calculated_age'])) {
                    $cleaned[$db_field] = is_numeric($value) ? (int)$value : null;
                } else {
                    $cleaned[$db_field] = $value;
                }
            }
        }
        
        // Calculate age if DOB is provided and age not already calculated
        if (!empty($cleaned['school_student_dob']) && empty($cleaned['school_age'])) {
            try {
                $dob = new DateTime($cleaned['school_student_dob']);
                $now = new DateTime();
                $cleaned['school_age'] = $dob->diff($now)->y;
            } catch (Exception $e) {
                log_message('error', 'Error calculating age: ' . $e->getMessage());
            }
        }
        
        // Set entity_type (required field with default value)
        $cleaned['entity_type'] = 'school';
        
        // Handle new country creation
        if (!empty($data['new_country_name']) && !empty($data['new_country_phone_code'])) {
            $country_id = $this->create_new_country($data['new_country_name'], $data['new_country_phone_code']);
            if ($country_id) {
                $cleaned['country_id'] = $country_id;
            }
        }
        
        // Log what we're about to save for debugging
        log_message('debug', 'Cleaned school student data: ' . json_encode($cleaned));
        
        return $cleaned;
    }


    // Add this method to handle profile photo uploads
    private function handle_profile_photo_upload($student_id)
    {
        if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        $file = $_FILES['profile_photo'];
        
        if ($file['size'] > $max_size) {
            set_alert('warning', 'Profile photo must be less than 2MB');
            return false;
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            set_alert('warning', 'Profile photo must be JPG, PNG, or GIF');
            return false;
        }
        
        $image_data = file_get_contents($file['tmp_name']);
        
        if ($image_data) {
            $this->db->where('id', (int)$student_id)
                    ->update(db_prefix() . 'school_students', ['profile_photo' => $image_data]);
            return true;
        }
        
        return false;
    }

    // Add this method to create new countries
    private function create_new_country($name, $phone_code)
    {
        if (empty($name) || empty($phone_code)) {
            return false;
        }
        
        // Check if country already exists
        $existing = $this->db->where('short_name', $name)
                            ->or_where('calling_code', $phone_code)
                            ->get(db_prefix() . 'countries')
                            ->row();
        
        if ($existing) {
            return (int)$existing->country_id;
        }
        
        // Create new country
        $country_data = [
            'short_name' => $name,
            'calling_code' => $phone_code
        ];
        
        $this->db->insert(db_prefix() . 'countries', $country_data);
        return $this->db->insert_id();
    }
    public function get_school_student()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) access_denied('student_sponsor_portal');

        if ($this->input->post()) {
            $student_id  = $this->input->post('student_id');
            $student     = $this->school_model->get_by_id($student_id);

            if (!$student) { echo json_encode(['success' => false, 'message' => 'Student not found']); return; }

            if ($this->input->post('action') == 'view') {
                ob_start(); ?>
                <div class="row">
                    <div class="col-md-6">
                        <h5><strong>Student Information</strong></h5>
                        <p><strong>Name:</strong> <?= htmlspecialchars($student['name']) ?></p>
                        <p><strong>Grade:</strong> <?= htmlspecialchars($student['school_grade'] ?? '') ?></p>
                        <p><strong>School:</strong> <?= htmlspecialchars($student['school_name'] ?? '') ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($student['email'] ?? '') ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($student['contact_no'] ?? '') ?></p>
                        <p><strong>Date of Birth:</strong> <?= htmlspecialchars($student['school_student_dob'] ?? 'Not provided') ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5><strong>Additional Information</strong></h5>
                        <p><strong>District:</strong> <?= htmlspecialchars($student['city'] ?? 'Not provided') ?></p>
                        <p><strong>Address:</strong> <?= htmlspecialchars($student['address'] ?? 'Not provided') ?></p>
                        <p><strong>Father's Name:</strong> <?= htmlspecialchars($student['school_father_name'] ?? 'Not provided') ?></p>
                        <p><strong>Mother's Name:</strong> <?= htmlspecialchars($student['school_mother_name'] ?? 'Not provided') ?></p>
                        <p><strong>Guardian:</strong> <?= htmlspecialchars($student['school_guardian_name'] ?? 'Not provided') ?></p>
                    </div>
                </div>
                <div class="row" style="margin-top: 20px;">
                    <div class="col-md-12">
                        <h5><strong>Sponsorship Information</strong></h5>
                        <p><strong>Sponsorship Period:</strong>
                            <?= ($student['school_sponsorship_start_date'] ?? false)
                                ? htmlspecialchars($student['school_sponsorship_start_date']) . ' to ' . htmlspecialchars($student['school_sponsorship_end_date'] ?? 'Ongoing')
                                : 'Not set' ?>
                        </p>
                        <p><strong>Introduced by:</strong> <?= htmlspecialchars($student['school_introducedby'] ?? 'Not provided') ?></p>
                    </div>
                </div>
                <?php
                echo json_encode(['success' => true, 'html' => ob_get_clean()]);
            } else {
                echo json_encode(['success' => true, 'student' => $student]);
            }
        }
    }

    public function delete_school_student()
    {
        if (!is_admin() && !has_permission('student_sponsor_portal', '', 'delete')) {
            echo json_encode(['success'=>false,'message'=>'Access denied']); 
            return;
        }
        
        $id = $this->input->post('student_id');
        if (!$id || !is_numeric($id)) {
            echo json_encode(['success'=>false,'message'=>'Invalid student ID']); 
            return;
        }

        $id = (int)$id;
        
        try {
            // Check if student exists first
            $student = $this->school_model->get_by_id($id);
            if (!$student) {
                echo json_encode(['success'=>false,'message'=>'Student not found']);
                return;
            }

            // Delete the student
            $deleted = $this->school_model->delete($id);
            
            if ($deleted) {
                log_message('info', 'School student deleted successfully. ID: ' . $id . ' by staff: ' . get_staff_user_id());
                echo json_encode(['success'=>true,'message'=>'Student deleted successfully']);
            } else {
                log_message('error', 'Failed to delete school student. ID: ' . $id);
                echo json_encode(['success'=>false,'message'=>'Failed to delete student - database error']);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Exception during school student deletion. ID: ' . $id . ' Error: ' . $e->getMessage());
            echo json_encode(['success'=>false,'message'=>'An error occurred while deleting the student']);
        }
    }
    public function export_school_students()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        $students = $this->school_model->get_all();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="school_students_'.date('Y-m-d').'.csv"');

        $output = fopen('php://output', 'w');
        
        fputcsv($output, [
            'ID', 'Name', 'Email', 'Phone', 'School', 'Grade', 'DOB', 'Address', 
            'City', 'Postal Code', 'Father Name', 'Mother Name', 'Guardian Name',
            'Bank', 'Account Number', 'Sponsorship Start', 'Sponsorship End'
        ]);

        foreach($students as $s) {
            fputcsv($output, [
                $s['id'] ?? '',
                $s['name'] ?? '',
                $s['email'] ?? '',
                $s['contact_no'] ?? '',
                $s['school_name'] ?? '',
                $s['school_grade'] ?? '',
                $s['school_student_dob'] ?? '',
                $s['address'] ?? '',
                $s['city'] ?? '',
                $s['zip'] ?? '',
                $s['school_father_name'] ?? '',
                $s['school_mother_name'] ?? '',
                $s['school_guardian_name'] ?? '',
                $s['bank_name'] ?? '',
                $s['school_bank_account_no'] ?? '',
                $s['school_sponsorship_start_date'] ?? '',
                $s['school_sponsorship_end_date'] ?? ''
            ]);
        }

        fclose($output);
    }

    public function add_school_name()
    {
        header('Content-Type: application/json');
        
        if (!has_permission('student_sponsor_portal', '', 'create')) {
            echo json_encode(['success' => false, 'message' => 'No permission']);
            return;
        }
        
        $name = trim($this->input->post('name'));
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'School name is required']);
            return;
        }
        
        try {
            $this->db->where('name', $name);
            $exists = $this->db->get(db_prefix() . 'school_name')->row();
            if ($exists) {
                echo json_encode(['success' => false, 'message' => 'School already exists']);
                return;
            }
            
            $this->db->insert(db_prefix() . 'school_name', ['name' => $name]);
            $id = $this->db->insert_id();
            
            if ($id) {
                echo json_encode([
                    'success' => true, 
                    'id' => $id,
                    'name' => $name,
                    'message' => 'School added successfully'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add school']);
            }
        } catch (Exception $e) {
            log_message('error', 'Error adding school: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        }
    }

   public function upload_school_report_card()
    {
        header('Content-Type: application/json');

        if (!has_permission('student_sponsor_portal', '', 'create')) {
            echo json_encode([
                'success' => false, 
                'message' => 'No permission'
            ]);
            return;
        }

        $student_id = (int)$this->input->post('student_school_id');
        $report_card_term = trim((string)$this->input->post('term'));
        $upload_date = trim((string)$this->input->post('upload_date'));
        $display_filename = trim((string)$this->input->post('filename'));

        if ($student_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Student ID is required']);
            return;
        }

        $student = $this->school_model->get_by_id($student_id);
        if (!$student) {
            echo json_encode(['success' => false, 'message' => 'Student not found']);
            return;
        }

        if (empty($_FILES['report_card_file']['name'])) {
            echo json_encode(['success' => false, 'message' => 'No file selected']);
            return;
        }

        if (empty($report_card_term) || !in_array($report_card_term, ['Term1', 'Term2', 'Term3'])) {
            echo json_encode(['success' => false, 'message' => 'Valid term is required (Term1, Term2, or Term3)']);
            return;
        }

        if (empty($upload_date)) {
            $upload_date = date('Y-m-d');
        }

        $origName = (string)$_FILES['report_card_file']['name'];
        $tmpName = (string)$_FILES['report_card_file']['tmp_name'];
        $size = (int)$_FILES['report_card_file']['size'];

        if (!is_uploaded_file($tmpName)) {
            echo json_encode(['success' => false, 'message' => 'Upload failed (temp not found)']);
            return;
        }

        $maxBytes = 16 * 1024 * 1024; // 16 MB
        if ($size <= 0 || $size > $maxBytes) {
            echo json_encode(['success' => false, 'message' => 'File too large (max 16MB)']);
            return;
        }

        $mime = null;
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            if ($f) { 
                $mime = @finfo_file($f, $tmpName); 
                finfo_close($f); 
            }
        }
        if (!$mime && !empty($_FILES['report_card_file']['type'])) {
            $mime = $_FILES['report_card_file']['type'];
        }
        $mime = strtolower((string)$mime) ?: 'application/octet-stream';

        // Validate file type
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($mime, $allowed_types) && !in_array($file_ext, $allowed_extensions)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF and image files allowed.']);
            return;
        }

        $display = $display_filename !== '' ? $display_filename : $origName;
        $display = preg_replace('/[^\p{L}\p{N}\-\._ ]/u', '_', $display);
        if ($display === '' || $display === '_') {
            $display = 'report_card_'.date('Ymd_His');
        }

        $sha256 = @hash_file('sha256', $tmpName) ?: null;

        $tbl = db_prefix().'school_report_card';

        $payload = [
            'student_school_id' => $student_id,
            'filename'          => $display,
            'term'              => $report_card_term,
            'upload_date'       => $upload_date,
            'mime_type'         => $mime,
            'file_size'         => $size,
            'sha256'            => $sha256,
            'created_on'        => date('Y-m-d H:i:s'),
        ];

        // Check if we should use BLOB storage
        $blobCol = $this->db->field_exists('file_blob', $tbl) ? 'file_blob' : null;

        if ($blobCol) {
            // Store as BLOB
            $bytes = @file_get_contents($tmpName);
            if ($bytes === false) {
                echo json_encode(['success' => false, 'message' => 'Cannot read uploaded file']);
                return;
            }

            $payload[$blobCol] = $bytes;
            $payload['report_card_file'] = null; // Clear file path when using BLOB

            // Check for existing report card with same term for this student
            $existing = $this->db->where('student_school_id', $student_id)
                                ->where('term', $report_card_term)
                                ->get($tbl)
                                ->row();
            
            if ($existing) {
                // Update existing record
                $ok = $this->db->where('id', $existing->id)->update($tbl, $payload);
                if (!$ok) {
                    echo json_encode(['success' => false, 'message' => 'Database update failed']);
                    return;
                }
            } else {
                // Insert new record
                $ok = $this->db->insert($tbl, $payload);
                if (!$ok) {
                    echo json_encode(['success' => false, 'message' => 'Database insert failed']);
                    return;
                }
            }
        } else {
            // Store as file path (fallback)
            $internalId = (string)($student['school_internal_id'] ?? '');
            $internalId = preg_replace('/[^\w\-]+/u', '_', $internalId);
            if ($internalId === '') { 
                $internalId = 'SCHOOL_' . $student_id; 
            }

            $prefixed = $internalId . '_' . $display;
            if (strlen($prefixed) > 200) {
                $ext = '';
                $dot = strrpos($prefixed, '.');
                if ($dot !== false && $dot >= 1) { 
                    $ext = substr($prefixed, $dot); 
                }
                $prefixed = substr($prefixed, 0, 200 - strlen($ext)) . $ext;
            }

            $storeDir = rtrim(FCPATH, '/\\') . '/uploads/school_report_cards/';
            if (!is_dir($storeDir)) @mkdir($storeDir, 0755, true);

            $serverName = $prefixed;
            $counter = 2;
            while (file_exists($storeDir . $serverName)) {
                $dot = strrpos($prefixed, '.');
                if ($dot !== false && $dot >= 1) {
                    $base = substr($prefixed, 0, $dot);
                    $ext = substr($prefixed, $dot);
                    $serverName = $base . '-' . $counter . $ext;
                } else {
                    $serverName = $prefixed . '-' . $counter;
                }
                $counter++;
                if ($counter > 99) break;
            }

            $dest = $storeDir . $serverName;
            if (!@move_uploaded_file($tmpName, $dest)) {
                if (!@copy($tmpName, $dest)) {
                    echo json_encode(['success' => false, 'message' => 'Could not save file']);
                    return;
                }
            }

            $payload['report_card_file'] = 'uploads/school_report_cards/' . $serverName;

            // Check for existing report card with same term for this student
            $existing = $this->db->where('student_school_id', $student_id)
                                ->where('term', $report_card_term)
                                ->get($tbl)
                                ->row();
            
            if ($existing) {
                // Delete old file if it exists
                if (!empty($existing->report_card_file)) {
                    $oldPath = FCPATH . ltrim($existing->report_card_file, '/');
                    if (is_file($oldPath)) @unlink($oldPath);
                }
                
                $ok = $this->db->where('id', $existing->id)->update($tbl, $payload);
                if (!$ok) {
                    @unlink($dest);
                    echo json_encode(['success' => false, 'message' => 'Database update failed']);
                    return;
                }
            } else {
                $ok = $this->db->insert($tbl, $payload);
                if (!$ok) {
                    @unlink($dest);
                    echo json_encode(['success' => false, 'message' => 'Database insert failed']);
                    return;
                }
            }
        }

        echo json_encode(['success' => true, 'message' => 'Report card uploaded successfully']);
    }

    public function download_school_report_card($id)
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        $card = $this->db->get_where(db_prefix() . 'school_report_card', ['id' => (int)$id])->row();
        
        if (!$card) {
            show_404();
        }

        // Download from BLOB
        if (!empty($card->file_blob)) {
            $filename = $card->filename ?: 'report_card_' . $id;
            
            // Add appropriate extension if missing
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if (!$ext) {
                if ($card->mime_type === 'application/pdf') {
                    $filename .= '.pdf';
                } elseif (strpos($card->mime_type, 'image/') === 0) {
                    $filename .= '.jpg';
                }
            }
            
            header('Content-Type: ' . $card->mime_type);
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($card->file_blob));
            header('Cache-Control: no-cache, must-revalidate');
            
            echo $card->file_blob;
            return;
        }

        // Fallback to file path (if report_card_file exists)
        if (!empty($card->report_card_file)) {
            $file_path = FCPATH . ltrim($card->report_card_file, '/');
            if (file_exists($file_path)) {
                $filename = $card->filename ?: 'report_card_' . $id;
                
                header('Content-Type: ' . $card->mime_type);
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($file_path));
                
                readfile($file_path);
                return;
            }
        }
        
        show_404();
    }

    public function delete_school_report_card($id)
    {
        header('Content-Type: application/json');

        if (!has_permission('student_sponsor_portal', '', 'delete')) {
            echo json_encode(['success' => false, 'message' => 'No permission']);
            return;
        }

        $card = $this->db->get_where(db_prefix() . 'school_report_card', ['id' => (int)$id])->row();
        
        if (!$card) {
            echo json_encode(['success' => false, 'message' => 'Report card not found']);
            return;
        }

        // Delete from database (BLOB data will be automatically removed)
        $this->db->where('id', (int)$id)->delete(db_prefix() . 'school_report_card');
        
        if ($this->db->affected_rows() > 0) {
            // Also clean up any physical file if it exists (legacy cleanup)
            if (!empty($card->report_card_file)) {
                $file_path = FCPATH . ltrim($card->report_card_file, '/');
                if (file_exists($file_path)) {
                    @unlink($file_path);
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Report card deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete report card']);
        }
    }
    public function get_school_report_cards($student_id)
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            echo json_encode(['success' => false, 'message' => 'No permission']);
            return;
        }

        $result = $this->school_model->get_report_cards((int)$student_id);
        echo json_encode($result);
    }

    public function display_school_photo($student_id)
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }
        
        $photo_data = $this->school_model->get_profile_photo((int)$student_id);
        
        if ($photo_data) {
            header('Content-Type: image/jpeg');
            header('Content-Length: ' . strlen($photo_data));
            header('Cache-Control: max-age=3600');
            
            echo $photo_data;
        } else {
            header('HTTP/1.0 404 Not Found');
            exit('Photo not found');
        }
    }

    public function search_school_students()
    {
        if (!is_staff_logged_in()) show_404();
        $q = trim($this->input->post('q', true));
        $page = max(1, (int)$this->input->post('page')); 
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $this->db->select('id, name');
        if ($q !== '') { 
            $this->db->like('name', $q); 
        }
        $this->db->order_by('name', 'asc')->limit($limit + 1, $offset);
        $rows = $this->db->get(db_prefix().'school_students')->result();

        $more = count($rows) > $limit; 
        if ($more) array_pop($rows);
        
        $results = array_map(function($r) { 
            return ['id' => $r->id, 'name' => $r->name, 'text' => $r->name]; 
        }, $rows);

        echo json_encode([
            'results' => $results,
            'pagination' => ['more' => $more],
            $this->security->get_csrf_token_name() => $this->security->get_csrf_hash()
        ]); 
        die();
    }

    public function grant_school_access($student_id = null)
    {
        if (!has_permission('student_sponsor_portal', '', 'edit')) access_denied('student_sponsor_portal');

        $student_id = $this->resolve_id('student_id', 4);
        if ($student_id <= 0) { 
            set_alert('danger', 'Missing student ID.'); 
            redirect(admin_url('student_sponsor_portal/school_students')); 
        }

        $s = $this->school_model->get_by_id($student_id);
        if (!$s) { 
            set_alert('danger', 'Student not found.'); 
            redirect(admin_url('student_sponsor_portal/school_students')); 
        }

        $data = [
            'staff_email'     => $this->in('staff_email', $s['email'] ?? ''),
            'staff_firstname' => $this->in('staff_firstname', $s['name'] ?? 'Student'),
            'staff_lastname'  => $this->in('staff_lastname', ''),
            'staff_password'  => $this->in('staff_password', ''),
            'active'          => $this->in_bool('staff_active'),
        ];

        if ($data['staff_email'] === '') {
            set_alert('danger', 'Login email is required.');
            redirect(admin_url('student_sponsor_portal/school_student_form/' . $student_id));
        }

        $this->ensure_three_min_roles();
        $staff_id = $this->upsert_staff($data, $s['staff_id'] ?? null, 'School Student');
        if (!$staff_id) {
            set_alert('danger', 'Failed to create/update staff account.');
            redirect(admin_url('student_sponsor_portal/school_student_form/' . $student_id));
        }

        $tbl = db_prefix() . 'school_students';
        $update = ['staff_id' => (int)$staff_id];
        if ($this->db->field_exists('active', $tbl))           $update['active'] = (int)$data['active'];
        elseif ($this->db->field_exists('staff_active', $tbl)) $update['staff_active'] = (int)$data['active'];

        $this->db->where('id', (int)$student_id)->update($tbl, $update);

        set_alert('success', 'Portal access granted' . ($data['staff_password'] !== '' ? '. Password set as provided.' : '. Existing password kept or auto-generated.'));
        redirect(admin_url('student_sponsor_portal/school_student_form/' . $student_id));
    }

    public function revoke_school_access($student_id = null)
    {
        if (!has_permission('student_sponsor_portal', '', 'edit')) access_denied('student_sponsor_portal');

        $student_id = $this->resolve_id('student_id', 4);
        if ($student_id <= 0) { 
            set_alert('danger', 'Missing student ID.'); 
            redirect(admin_url('student_sponsor_portal/school_students')); 
        }

        $s = $this->school_model->get_by_id($student_id);
        if (!$s) { 
            set_alert('danger', 'Student not found.'); 
            redirect(admin_url('student_sponsor_portal/school_students')); 
        }
        if (empty($s['staff_id'])) { 
            set_alert('danger', 'No staff account linked.'); 
            redirect(admin_url('student_sponsor_portal/school_student_form/' . $student_id)); 
        }

        $this->db->where('staffid', (int)$s['staff_id'])->update(db_prefix() . 'staff', ['active' => 0]);

        $tbl = db_prefix() . 'school_students';
        $update = ['staff_id' => null];
        if ($this->db->field_exists('active', $tbl))           $update['active'] = 0;
        elseif ($this->db->field_exists('staff_active', $tbl)) $update['staff_active'] = 0;

        $this->db->where('id', (int)$student_id)->update($tbl, $update);

        set_alert('success', 'Portal access revoked.');
        redirect(admin_url('student_sponsor_portal/school_student_form/' . $student_id));
    }




    /* =================================================================================== */
    /* =====================            UNIVERSITY STUDENTS            =================== */
    /* =================================================================================== */

    public function university_students()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }
        
        $data['students'] = $this->university_model->get_all();
        $data['title'] = 'University Students';
        
        $this->load->view('student_sponsor_portal/university_students_list', $data);
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

            if ($id && !empty($this->input->post('create_staff'))) {
                $existing          = $this->university_model->get_by_id($id);
                $existing_staff_id = $existing['staff_id'] ?? null;

                $this->ensure_three_min_roles();
                $staff_id = $this->create_or_update_staff_from_student($this->input->post(), $existing_staff_id);

                if ($staff_id) {
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix().'university_students', [
                        'staff_id'     => $staff_id,
                        'active' => !empty($this->input->post('active')) ? 1 : 0,
                    ]);
                }
            } elseif (!empty($id)) {
                $this->db->where('id', $id);
                $this->db->update(db_prefix().'university_students', ['active' => 0]);
            }

            if ($id) {
                set_alert('success', 'University student registered successfully');
                redirect(admin_url('student_sponsor_portal/university_students'));
            }
        }
        
        $data['title'] = 'Register University Student';
        $this->load->view('student_sponsor_portal/university_form', $data);
    }
    
    public function university_student_form($student_id = null)
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) access_denied('student_sponsor_portal');

        try {
            log_message('debug', 'Loading university student form for ID: ' . ($student_id ?: 'new'));

            if ($this->input->post()) {
                $isCreate = empty($this->input->post('student_id'));
                if ($isCreate && !has_permission('student_sponsor_portal', '', 'create')) access_denied('student_sponsor_portal');
                if (!$isCreate && !has_permission('student_sponsor_portal', '', 'edit')) access_denied('student_sponsor_portal');

                $post = $this->input->post();
                $sid = (int)($post['student_id'] ?? 0);
                unset($post['student_id']);

                // Log raw POST data for debugging
                log_message('debug', 'Raw POST data: ' . json_encode($post));

                // Clean and map form data to database fields
                $cleaned_data = $this->clean_university_post_data($post);

                $this->session->set_flashdata('old_input', $post);

                if ($isCreate) {
                    $res = $this->university_model->add($cleaned_data);
                    if (!is_array($res)) { 
                        $res = $res ? ['success' => true, 'id' => $res] : ['success' => false, 'message' => 'Unable to save.']; 
                    }
                    if (!$res['success']) {
                        $this->session->set_flashdata('old_input', $post);
                        set_alert('danger', $res['message'] ?? 'Error saving student');
                        redirect(admin_url('student_sponsor_portal/university_student_form'));
                    }
                    $newId = (int)$res['id'];

                    // Handle profile photo upload
                    $this->handle_university_profile_photo_upload($newId);

                    // Handle staff creation
                    if (!empty($this->input->post('create_staff'))) {
                        $existing = $this->university_model->get_by_id($newId);
                        $existing_staff_id = $existing['staff_id'] ?? null;

                        $this->ensure_three_min_roles();
                        $staff_id = $this->create_or_update_staff_from_student($this->input->post(), $existing_staff_id);

                        if ($staff_id) {
                            $this->db->where('id', $newId)->update(db_prefix() . 'university_students', [
                                'staff_id' => $staff_id,
                                'active' => !empty($this->input->post('staff_active')) ? 1 : 0,
                            ]);
                        }
                    } else {
                        $this->db->where('id', $newId)->update(db_prefix() . 'university_students', ['active' => 0]);
                    }

                    set_alert('success', 'University student registered successfully');
                    redirect(admin_url('student_sponsor_portal/university_students'));
                } else {
                    // Handle profile photo upload for existing student
                    $this->handle_university_profile_photo_upload($sid);

                    // Log before update
                    log_message('debug', 'Attempting to update university student ID: ' . $sid . ' with data: ' . json_encode($cleaned_data));
                    
                    // Get student data before update for comparison
                    $before_update = $this->university_model->get_by_id($sid);
                    log_message('debug', 'University student data BEFORE update: ' . json_encode($before_update));

                    $ok = $this->university_model->update_student($cleaned_data, $sid);
                    
                    if ($ok) {
                        // Get student data after update for verification
                        $after_update = $this->university_model->get_by_id($sid);
                        log_message('debug', 'University student data AFTER update: ' . json_encode($after_update));
                        
                        // Check if anything actually changed
                        $changes_made = false;
                        foreach ($cleaned_data as $field => $new_value) {
                            if (isset($before_update[$field]) && $before_update[$field] != $new_value) {
                                $changes_made = true;
                                log_message('debug', "Field '{$field}' changed from '{$before_update[$field]}' to '{$new_value}'");
                            } elseif (!isset($before_update[$field]) && $new_value !== null && $new_value !== '') {
                                $changes_made = true;
                                log_message('debug', "New field '{$field}' set to '{$new_value}'");
                            }
                        }
                        
                        if (!$changes_made) {
                            log_message('warning', 'Update reported success but no changes detected in database for university student ID: ' . $sid);
                            set_alert('warning', 'Update completed but no changes were detected. Please verify your data.');
                        } else {
                            set_alert('success', 'University student updated successfully');
                        }
                    } else {
                        $err = $this->db->error();
                        log_message('error', 'Database update failed for university student ID: ' . $sid . '. Error: ' . json_encode($err));
                        $msg = (!empty($err['code']) && (int)$err['code'] === 1062)
                            ? 'Duplicate value detected (e.g., email). Please use a different value.'
                            : 'Error saving student. Please check all fields and try again.';
                        set_alert('danger', $msg);
                        redirect(admin_url('student_sponsor_portal/university_student_form/' . $sid));
                    }

                    // Handle staff creation for existing student
                    if (!empty($this->input->post('create_staff'))) {
                        $existing = $this->university_model->get_by_id($sid);
                        $existing_staff_id = $existing['staff_id'] ?? null;

                        $this->ensure_three_min_roles();
                        $staff_id = $this->create_or_update_staff_from_student($this->input->post(), $existing_staff_id);

                        if ($staff_id) {
                            $this->db->where('id', $sid)->update(db_prefix() . 'university_students', [
                                'staff_id' => $staff_id,
                                'active' => !empty($this->input->post('staff_active')) ? 1 : 0,
                            ]);
                        }
                    }

                    redirect(admin_url('student_sponsor_portal/university_students'));
                }
            }

            $data['title'] = 'Register University Student';
            if ($student_id) {
                $student = $this->university_model->get_by_id((int)$student_id);
                if (!$student) { 
                    set_alert('danger', 'Student not found');
                    redirect(admin_url('student_sponsor_portal/university_students')); 
                }
                $data['student'] = $student;
                $data['title'] = 'Edit University Student';
            }
            $data['old'] = $this->session->flashdata('old_input') ?: [];
            $data['banks'] = $this->db->select('id,name')->order_by('name', 'ASC')->get(db_prefix() . 'bank')->result_array();
            $data['universities'] = $this->university_model->get_universities();
            $data['programs'] = $this->university_model->get_programs();
            $data['sponsors'] = $this->university_model->get_sponsors();
            $data['countries'] = $this->_get_countries();
            
            $this->load->view('student_sponsor_portal/university_form', $data);
            
        } catch (Exception $e) {
            log_message('error', 'Error in university_student_form: ' . $e->getMessage());
            show_error('An error occurred while loading the form: ' . $e->getMessage(), 500);
        }
    }
    private function handle_university_profile_photo_upload($student_id)
    {
        if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        $file = $_FILES['profile_photo'];
        
        if ($file['size'] > $max_size) {
            set_alert('warning', 'Profile photo must be less than 2MB');
            return false;
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            set_alert('warning', 'Profile photo must be JPG, PNG, or GIF');
            return false;
        }
        
        $image_data = file_get_contents($file['tmp_name']);
        
        if ($image_data) {
            $this->db->where('id', (int)$student_id)
                    ->update(db_prefix() . 'university_students', ['profile_photo' => $image_data]);
            return true;
        }
        
        return false;
    }
     public function upload_university_report_card()
    {
        header('Content-Type: application/json');

        if (!has_permission('student_sponsor_portal', '', 'create')) {
            echo json_encode(['success' => false, 'message' => 'No permission']);
            return;
        }

        $student_id = (int)$this->input->post('student_university_id');
        $report_card_term = trim((string)$this->input->post('report_card_term'));
        $semester_end_month = ($this->input->post('semester_end_month') !== '') ? (int)$this->input->post('semester_end_month') : null;
        $semester_end_year = ($this->input->post('semester_end_year') !== '') ? (int)$this->input->post('semester_end_year') : null;
        $display_filename = trim((string)$this->input->post('filename'));

        if ($student_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Student ID is required']);
            return;
        }

        $student = $this->university_model->get_by_id($student_id);
        if (!$student) {
            echo json_encode(['success' => false, 'message' => 'Student not found']);
            return;
        }

        if (empty($_FILES['report_card_file']['name'])) {
            echo json_encode(['success' => false, 'message' => 'No file selected']);
            return;
        }

        if (empty($report_card_term) || !in_array($report_card_term, ['1Y1S','1Y2S','2Y1S','2Y2S','3Y1S','3Y2S','4Y1S','4Y2S','5Y1S','5Y2S'])) {
            echo json_encode(['success' => false, 'message' => 'Valid term is required']);
            return;
        }

        $origName = (string)$_FILES['report_card_file']['name'];
        $tmpName = (string)$_FILES['report_card_file']['tmp_name'];
        $size = (int)$_FILES['report_card_file']['size'];

        if (!is_uploaded_file($tmpName)) {
            echo json_encode(['success' => false, 'message' => 'Upload failed (temp not found)']);
            return;
        }

        $maxBytes = 16 * 1024 * 1024; // 16 MB
        if ($size <= 0 || $size > $maxBytes) {
            echo json_encode(['success' => false, 'message' => 'File too large (max 16MB)']);
            return;
        }

        $mime = null;
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            if ($f) { 
                $mime = @finfo_file($f, $tmpName); 
                finfo_close($f); 
            }
        }
        if (!$mime && !empty($_FILES['report_card_file']['type'])) {
            $mime = $_FILES['report_card_file']['type'];
        }
        $mime = strtolower((string)$mime) ?: 'application/octet-stream';

        // Validate file type
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($mime, $allowed_types) && !in_array($file_ext, $allowed_extensions)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF and image files allowed.']);
            return;
        }

        $display = $display_filename !== '' ? $display_filename : $origName;
        $display = preg_replace('/[^\p{L}\p{N}\-\._ ]/u', '_', $display);
        if ($display === '' || $display === '_') {
            $display = 'report_card_'.date('Ymd_His');
        }

        $sha256 = @hash_file('sha256', $tmpName) ?: null;

        $tbl = db_prefix().'university_report_card';

        $payload = [
            'student_university_id' => $student_id,
            'filename'              => $display,
            'report_card_term'      => $report_card_term,
            'current_term'          => $report_card_term,
            'semester_end_month'    => $semester_end_month,
            'semester_end_year'     => $semester_end_year,
            'upload_date'           => date('Y-m-d'),
            'mime_type'             => $mime,
            'file_size'             => $size,
            'sha256'                => $sha256,
            'created_on'            => date('Y-m-d H:i:s'),
        ];

        // Check if we should use BLOB storage
        $blobCol = $this->db->field_exists('file_blob', $tbl) ? 'file_blob' : null;

        if ($blobCol) {
            // Store as BLOB
            $bytes = @file_get_contents($tmpName);
            if ($bytes === false) {
                echo json_encode(['success' => false, 'message' => 'Cannot read uploaded file']);
                return;
            }

            $payload[$blobCol] = $bytes;
            $payload['report_card_file'] = null; // Clear file path when using BLOB

            // Check for existing report card with same term for this student
            $existing = $this->db->where('student_university_id', $student_id)
                                ->where('report_card_term', $report_card_term)
                                ->get($tbl)
                                ->row();
            
            if ($existing) {
                // Update existing record
                $ok = $this->db->where('id', $existing->id)->update($tbl, $payload);
                if (!$ok) {
                    echo json_encode(['success' => false, 'message' => 'Database update failed']);
                    return;
                }
            } else {
                // Insert new record
                $ok = $this->db->insert($tbl, $payload);
                if (!$ok) {
                    echo json_encode(['success' => false, 'message' => 'Database insert failed']);
                    return;
                }
            }
        } else {
            // Store as file path (fallback)
            $internalId = (string)($student['university_internal_id'] ?? '');
            $internalId = preg_replace('/[^\w\-]+/u', '_', $internalId);
            if ($internalId === '') { 
                $internalId = 'UNI_' . $student_id; 
            }

            $prefixed = $internalId . '_' . $display;
            if (strlen($prefixed) > 200) {
                $ext = '';
                $dot = strrpos($prefixed, '.');
                if ($dot !== false && $dot >= 1) { 
                    $ext = substr($prefixed, $dot); 
                }
                $prefixed = substr($prefixed, 0, 200 - strlen($ext)) . $ext;
            }

            $storeDir = rtrim(FCPATH, '/\\') . '/uploads/university_report_cards/';
            if (!is_dir($storeDir)) @mkdir($storeDir, 0755, true);

            $serverName = $prefixed;
            $counter = 2;
            while (file_exists($storeDir . $serverName)) {
                $dot = strrpos($prefixed, '.');
                if ($dot !== false && $dot >= 1) {
                    $base = substr($prefixed, 0, $dot);
                    $ext = substr($prefixed, $dot);
                    $serverName = $base . '-' . $counter . $ext;
                } else {
                    $serverName = $prefixed . '-' . $counter;
                }
                $counter++;
                if ($counter > 99) break;
            }

            $dest = $storeDir . $serverName;
            if (!@move_uploaded_file($tmpName, $dest)) {
                if (!@copy($tmpName, $dest)) {
                    echo json_encode(['success' => false, 'message' => 'Could not save file']);
                    return;
                }
            }

            $payload['report_card_file'] = 'uploads/university_report_cards/' . $serverName;

            // Check for existing report card with same term for this student
            $existing = $this->db->where('student_university_id', $student_id)
                                ->where('report_card_term', $report_card_term)
                                ->get($tbl)
                                ->row();
            
            if ($existing) {
                // Delete old file if it exists
                if (!empty($existing->report_card_file)) {
                    $oldPath = FCPATH . ltrim($existing->report_card_file, '/');
                    if (is_file($oldPath)) @unlink($oldPath);
                }
                
                $ok = $this->db->where('id', $existing->id)->update($tbl, $payload);
                if (!$ok) {
                    @unlink($dest);
                    echo json_encode(['success' => false, 'message' => 'Database update failed']);
                    return;
                }
            } else {
                $ok = $this->db->insert($tbl, $payload);
                if (!$ok) {
                    @unlink($dest);
                    echo json_encode(['success' => false, 'message' => 'Database insert failed']);
                    return;
                }
            }
        }

        echo json_encode(['success' => true, 'message' => 'Report card uploaded successfully']);
    }

    public function get_university_report_cards($student_id)
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            echo json_encode(['success' => false, 'message' => 'No permission']);
            return;
        }

        $result = $this->university_model->get_report_cards((int)$student_id);
        echo json_encode($result);
    }

    public function download_university_report_card($id)
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        $card = $this->db->get_where(db_prefix() . 'university_report_card', ['id' => (int)$id])->row();
        
        if (!$card) {
            show_404();
        }

        // Download from BLOB
        if (!empty($card->file_blob)) {
            $filename = $card->filename ?: 'report_card_' . $id;
            
            // Add appropriate extension if missing
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if (!$ext) {
                if ($card->mime_type === 'application/pdf') {
                    $filename .= '.pdf';
                } elseif (strpos($card->mime_type, 'image/') === 0) {
                    $filename .= '.jpg';
                }
            }
            
            header('Content-Type: ' . $card->mime_type);
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($card->file_blob));
            header('Cache-Control: no-cache, must-revalidate');
            
            echo $card->file_blob;
            return;
        }

        // Fallback to file path (if report_card_file exists)
        if (!empty($card->report_card_file)) {
            $file_path = FCPATH . ltrim($card->report_card_file, '/');
            if (file_exists($file_path)) {
                $filename = $card->filename ?: 'report_card_' . $id;
                
                header('Content-Type: ' . $card->mime_type);
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($file_path));
                
                readfile($file_path);
                return;
            }
        }
        
        show_404();
    }

    public function delete_university_report_card($id)
    {
        header('Content-Type: application/json');

        if (!has_permission('student_sponsor_portal', '', 'delete')) {
            echo json_encode(['success' => false, 'message' => 'No permission']);
            return;
        }

        $card = $this->db->get_where(db_prefix() . 'university_report_card', ['id' => (int)$id])->row();
        
        if (!$card) {
            echo json_encode(['success' => false, 'message' => 'Report card not found']);
            return;
        }

        // Delete from database (BLOB data will be automatically removed)
        $this->db->where('id', (int)$id)->delete(db_prefix() . 'university_report_card');
        
        if ($this->db->affected_rows() > 0) {
            // Also clean up any physical file if it exists (legacy cleanup)
            if (!empty($card->report_card_file)) {
                $file_path = FCPATH . ltrim($card->report_card_file, '/');
                if (file_exists($file_path)) {
                    @unlink($file_path);
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Report card deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete report card']);
        }
    }
    // /update
    private function clean_university_post_data($data)
    {
        $cleaned = [];
        
        // Field mappings from form names to database column names (ONLY ACTUAL DB FIELDS)
        $field_mappings = [
            'name' => 'name',
            'email' => 'email', 
            'phone' => 'contact_no',
            'dob' => 'university_student_dob',
            'calculated_age' => 'university_age',
            'country_id' => 'country_id',
            'address' => 'address',
            'city' => 'city',
            'postal_code' => 'zip',
            'university_id' => 'university_id',
            'university_internal_id' => 'university_internal_id',
            'university_name_id' => 'university_name_id',
            'university_program_id' => 'university_program_id',
            'year_of_study' => 'university_year_of_study',
            'sponsorship_start' => 'university_sponsorship_start_date',
            'sponsorship_end' => 'university_sponsorship_end_date',
            'introduced_by' => 'university_introducedby',
            'introduced_phone' => 'university_introducedph',
            'bank_id' => 'bank_id',
            'bank_account_number' => 'university_bank_account_no',
            'bank_branch_number' => 'university_bank_branch_number',
            'bank_branch_info' => 'university_bank_branch_info',
            'father_name' => 'university_father_name',
            'father_income' => 'university_father_income',
            'mother_name' => 'university_mother_name', 
            'mother_income' => 'university_mother_income',
            'guardian_name' => 'university_guardian_name',
            'guardian_income' => 'university_guardian_income',
            'sponsor_id' => 'sponsor_id',
            'background_information' => 'background_info',
            'internal_comment' => 'internal_comment',
            'external_comment' => 'external_comment'
        ];
        
        foreach ($field_mappings as $form_field => $db_field) {
            if (isset($data[$form_field])) {
                $value = is_string($data[$form_field]) ? trim($data[$form_field]) : $data[$form_field];
                
                // Handle different data types
                if ($value === '' || $value === null) {
                    // For updates, we want to actually set empty values, not skip them
                    $cleaned[$db_field] = ($value === '') ? '' : null;
                } elseif (in_array($form_field, ['father_income', 'mother_income', 'guardian_income'])) {
                    $cleaned[$db_field] = is_numeric($value) ? (float)$value : null;
                } elseif (in_array($form_field, ['country_id', 'bank_id', 'university_name_id', 'university_program_id', 'sponsor_id', 'calculated_age'])) {
                    $cleaned[$db_field] = is_numeric($value) ? (int)$value : null;
                } else {
                    $cleaned[$db_field] = $value;
                }
            }
        }
        
        // Calculate age if DOB is provided and age not already calculated
        if (!empty($cleaned['university_student_dob']) && empty($cleaned['university_age'])) {
            try {
                $dob = new DateTime($cleaned['university_student_dob']);
                $now = new DateTime();
                $cleaned['university_age'] = $dob->diff($now)->y;
            } catch (Exception $e) {
                log_message('error', 'Error calculating age: ' . $e->getMessage());
            }
        }
        
        // Set entity_type (required field with default value)
        $cleaned['entity_type'] = 'university';
        
        // Handle new country creation
        if (!empty($data['new_country_name']) && !empty($data['new_country_phone_code'])) {
            $country_id = $this->create_new_country($data['new_country_name'], $data['new_country_phone_code']);
            if ($country_id) {
                $cleaned['country_id'] = $country_id;
            }
        }
        
        // Handle new university creation
        if (!empty($data['new_university_name'])) {
            $university_id = $this->create_new_university($data['new_university_name']);
            if ($university_id) {
                $cleaned['university_name_id'] = $university_id;
            }
        }
        
        // Handle new program creation
        if (!empty($data['new_program_name'])) {
            $program_id = $this->create_new_program($data['new_program_name']);
            if ($program_id) {
                $cleaned['university_program_id'] = $program_id;
            }
        }
        
        // Handle new bank creation
        if (!empty($data['new_bank_name'])) {
            $bank_id = $this->create_new_bank($data['new_bank_name']);
            if ($bank_id) {
                $cleaned['bank_id'] = $bank_id;
            }
        }
        
        // Log what we're about to save for debugging
        log_message('debug', 'Cleaned university student data: ' . json_encode($cleaned));
        
        return $cleaned;
    }

    private function create_new_university($name)
    {
        if (empty($name)) return false;
        
        // Check if university already exists
        $existing = $this->db->where('name', $name)->get(db_prefix() . 'university_name')->row();
        if ($existing) {
            return (int)$existing->id;
        }
        
        // Create new university
        $this->db->insert(db_prefix() . 'university_name', ['name' => $name]);
        return $this->db->insert_id();
    }
    
    private function create_new_program($name)
    {
        if (empty($name)) return false;
        
        // Check if program already exists
        $existing = $this->db->where('name', $name)->get(db_prefix() . 'university_program')->row();
        if ($existing) {
            return (int)$existing->id;
        }
        
        // Create new program
        $this->db->insert(db_prefix() . 'university_program', ['name' => $name]);
        return $this->db->insert_id();
    }
    public function get_university_student()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) { access_denied('student_sponsor_portal'); }

        if ($this->input->post()) {
            $id = $this->input->post('student_id');
            $s  = $this->university_model->get_by_id($id);

            if ($s) {
                if ($this->input->post('action') == 'view') {
                    $html = '<div class="row">
                        <div class="col-md-6">
                            <h5><strong>Student Information</strong></h5>
                            <p><strong>Name:</strong> '.htmlspecialchars($s['name']).'</p>
                            <p><strong>University:</strong> '.htmlspecialchars($s['university_name'] ?? '').'</p>
                            <p><strong>Program:</strong> '.htmlspecialchars($s['program_name'] ?? '').'</p>
                            <p><strong>Year:</strong> '.htmlspecialchars($s['university_year_of_study'] ?? '').'</p>
                            <p><strong>Email:</strong> '.htmlspecialchars($s['email'] ?? '').'</p>
                            <p><strong>Phone:</strong> '.htmlspecialchars($s['contact_no'] ?? '').'</p>
                            <p><strong>DOB:</strong> '.htmlspecialchars($s['university_student_dob'] ?? 'Not provided').'</p>
                        </div>
                        <div class="col-md-6">
                            <h5><strong>Additional Information</strong></h5>
                            <p><strong>Address:</strong> '.htmlspecialchars($s['address'] ?? 'Not provided').'</p>
                            <p><strong>City:</strong> '.htmlspecialchars($s['city'] ?? 'Not provided').'</p>
                            <p><strong>Father\'s Name:</strong> '.htmlspecialchars($s['university_father_name'] ?? '').'</p>
                            <p><strong>Mother\'s Name:</strong> '.htmlspecialchars($s['university_mother_name'] ?? '').'</p>
                        </div>
                    </div>';
                    echo json_encode(['success'=>true, 'html'=>$html]);
                } else {
                    echo json_encode(['success'=>true, 'student'=>$s]);
                }
            } else {
                echo json_encode(['success'=>false, 'message'=>'Student not found']);
            }
        }
    }

    public function delete_university_student()
    {
        if (!is_admin() && !has_permission('student_sponsor_portal', '', 'delete')) {
            echo json_encode(['success'=>false,'message'=>'Access denied']); return;
        }
        $id = $this->input->post('student_id');
        if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid student ID']); return; }

        $deleted = $this->university_model->delete($id);
        if ($deleted) {
            echo json_encode(['success'=>true,'message'=>'Student deleted successfully']);
        } else {
            echo json_encode(['success'=>false,'message'=>'Failed to delete student']);
        }
    }

    public function export_university_students()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) { access_denied('student_sponsor_portal'); }
        $students = $this->university_model->get_all();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="university_students_'.date('Y-m-d').'.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'ID','Name','Email','Phone','University','Program','Year',
            'University ID','DOB','Father Name','Mother Name',
            'Sponsorship Start','Sponsorship End','Address','City','Postal Code','Country'
        ]);

        foreach($students as $s){
            fputcsv($out, [
                $s['id'] ?? '', $s['name'] ?? '', $s['email'] ?? '', $s['contact_no'] ?? '',
                $s['university_name'] ?? '', $s['program_name'] ?? '', $s['university_year_of_study'] ?? '',
                $s['university_id'] ?? '', $s['university_student_dob'] ?? '', 
                $s['university_father_name'] ?? '', $s['university_mother_name'] ?? '',
                $s['university_sponsorship_start_date'] ?? '', $s['university_sponsorship_end_date'] ?? '',
                $s['address'] ?? '', $s['city'] ?? '', $s['zip'] ?? '', $s['country_name'] ?? ''
            ]);
        }
        fclose($out);
    }

    public function add_university_name() 
    {
        header('Content-Type: application/json');

        if (!has_permission('student_sponsor_portal', '', 'create')) {
            echo json_encode(['success' => false, 'message' => 'No permission']);
            return;
        }
        
        $name = trim($this->input->post('name'));
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Name is required']);
            return;
        }
        
        try {
            $this->db->where('name', $name);
            $exists = $this->db->get(db_prefix() . 'university_name')->row();
            if ($exists) {
                echo json_encode(['success' => false, 'message' => 'University already exists']);
                return;
            }
            
            $this->db->insert(db_prefix() . 'university_name', ['name' => $name]);
            $id = $this->db->insert_id();
            
            if ($id) {
                echo json_encode([
                    'success' => true, 
                    'id' => $id,
                    'name' => $name,
                    'message' => 'University added successfully'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add university']);
            }
        } catch (Exception $e) {
            log_message('error', 'Error adding university: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        }
    }

    public function add_university_program() 
    {
        header('Content-Type: application/json');
        
        if (!has_permission('student_sponsor_portal', '', 'create')) {
            echo json_encode(['success' => false, 'message' => 'No permission']);
            return;
        }
        
        $name = trim($this->input->post('name'));
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Program name is required']);
            return;
        }
        
        try {
            $this->db->where('name', $name);
            $exists = $this->db->get(db_prefix() . 'university_program')->row();
            if ($exists) {
                echo json_encode(['success' => false, 'message' => 'Program already exists']);
                return;
            }
            
            $this->db->insert(db_prefix() . 'university_program', ['name' => $name]);
            $id = $this->db->insert_id();
            
            if ($id) {
                echo json_encode([
                    'success' => true, 
                    'id' => $id,
                    'name' => $name,
                    'message' => 'Program added successfully'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add program']);
            }
        } catch (Exception $e) {
            log_message('error', 'Error adding program: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        }
    }

    public function add_bank() 
    {
        header('Content-Type: application/json');
        
        if (!has_permission('student_sponsor_portal', '', 'create')) {
            echo json_encode(['success' => false, 'message' => 'No permission']);
            return;
        }
        
        $name = trim($this->input->post('name'));
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Bank name is required']);
            return;
        }
        
        try {
            $this->db->where('name', $name);
            $exists = $this->db->get(db_prefix() . 'bank')->row();
            if ($exists) {
                echo json_encode(['success' => false, 'message' => 'Bank already exists']);
                return;
            }
            
            $this->db->insert(db_prefix() . 'bank', ['name' => $name]);
            $id = $this->db->insert_id();
            
            if ($id) {
                echo json_encode([
                    'success' => true, 
                    'id' => $id,
                    'name' => $name,
                    'message' => 'Bank added successfully'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add bank']);
            }
        } catch (Exception $e) {
            log_message('error', 'Error adding bank: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        }
    }

    public function upload_report_card()
    {
        header('Content-Type: application/json');

        if (!has_permission('student_sponsor_portal', '', 'create')) {
            echo json_encode(['success' => false, 'message' => 'No permission']);
            return;
        }

        $student_id = (int)$this->input->post('student_university_id');
        $report_card_term = trim((string)$this->input->post('report_card_term'));
        $semester_end_month = ($this->input->post('semester_end_month') !== '') ? (int)$this->input->post('semester_end_month') : null;
        $semester_end_year = ($this->input->post('semester_end_year') !== '') ? (int)$this->input->post('semester_end_year') : null;
        $display_filename = trim((string)$this->input->post('display_filename'));

        if ($student_id <= 0) {
            echo json_encode(['success'=>false,'message'=>'Student ID is required']); return;
        }

        $student = $this->university_model->get_by_id($student_id);
        if (!$student) {
            echo json_encode(['success'=>false,'message'=>'Student not found']); return;
        }

        if (empty($_FILES['report_card_file']['name'])) {
            echo json_encode(['success'=>false,'message'=>'No file selected']); return;
        }

        $origName = (string)$_FILES['report_card_file']['name'];
        $tmpName = (string)$_FILES['report_card_file']['tmp_name'];
        $size = (int)$_FILES['report_card_file']['size'];

        if (!is_uploaded_file($tmpName)) {
            echo json_encode(['success'=>false,'message'=>'Upload failed (temp not found)']); return;
        }

        $maxBytes = 16 * 1024 * 1024; // 16 MB
        if ($size <= 0 || $size > $maxBytes) {
            echo json_encode(['success'=>false,'message'=>'File too large (max 16MB)']); return;
        }

        $mime = null;
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            if ($f) { $mime = @finfo_file($f, $tmpName); finfo_close($f); }
        }
        if (!$mime && !empty($_FILES['report_card_file']['type'])) {
            $mime = $_FILES['report_card_file']['type'];
        }
        $mime = strtolower((string)$mime) ?: 'application/octet-stream';

        $display = $display_filename !== '' ? $display_filename : $origName;
        $display = preg_replace('/[^\p{L}\p{N}\-\._ ]/u', '_', $display);
        if ($display === '' || $display === '_') {
            $display = 'report_card_'.date('Ymd_His');
        }

        $sha256 = @hash_file('sha256', $tmpName) ?: null;

        $tbl = db_prefix().'university_report_card';

        $payload = [
            'student_university_id' => $student_id,
            'report_card_term'      => $report_card_term ?: null,
            'current_term'          => $report_card_term ?: null,
            'semester_end_month'    => $semester_end_month,
            'semester_end_year'     => $semester_end_year,
            'upload_date'           => date('Y-m-d H:i:s'),
            'created_on'            => date('Y-m-d H:i:s'),
            'filename'              => $display,
            'mime_type'             => $mime,
            'file_size'             => $size,
            'sha256'                => $sha256,
        ];

        $hasBlob = $this->db->field_exists('file_blob', $tbl) || $this->db->field_exists('report_card_blob', $tbl);
        $blobCol = $this->db->field_exists('file_blob', $tbl) ? 'file_blob'
                : ($this->db->field_exists('report_card_blob', $tbl) ? 'report_card_blob' : null);

        if ($hasBlob && $blobCol) {
            $bytes = @file_get_contents($tmpName);
            if ($bytes === false) {
                echo json_encode(['success'=>false,'message'=>'Cannot read uploaded file']); return;
            }

            $payload[$blobCol] = $bytes;
            if ($this->db->field_exists('report_card_file', $tbl)) {
                $payload['report_card_file'] = null;
            }

            $ok = $this->db->insert($tbl, $payload);
            if (!$ok) {
                echo json_encode(['success'=>false,'message'=>'Database error (blob insert)']); return;
            }

        } else {
            $internalId = (string)($student['university_internal_id'] ?? '');
            $internalId = preg_replace('/[^\w\-]+/u', '_', $internalId);
            if ($internalId === '') { $internalId = 'UNSPEC'; }

            $prefixed = $internalId . '_' . $display;
            if (strlen($prefixed) > 200) {
                $ext = '';
                $dot = strrpos($prefixed, '.');
                if ($dot !== false && $dot >= 1) { $ext = substr($prefixed, $dot); }
                $prefixed = substr($prefixed, 0, 200 - strlen($ext)) . $ext;
            }

            $storeDir = rtrim(FCPATH, '/\\') . '/uploads/student_report_cards/';
            if (!is_dir($storeDir)) @mkdir($storeDir, 0755, true);

            $serverName = $prefixed;
            $counter = 2;
            while (file_exists($storeDir . $serverName)) {
                $dot = strrpos($prefixed, '.');
                if ($dot !== false && $dot >= 1) {
                    $base = substr($prefixed, 0, $dot);
                    $ext = substr($prefixed, $dot);
                    $serverName = $base . '-' . $counter . $ext;
                } else {
                    $serverName = $prefixed . '-' . $counter;
                }
                $counter++;
                if ($counter > 99) break;
            }

            $dest = $storeDir . $serverName;
            if (!@move_uploaded_file($tmpName, $dest)) {
                if (!@copy($tmpName, $dest)) {
                    echo json_encode(['success'=>false,'message'=>'Could not save file']); return;
                }
            }

            $payload['report_card_file'] = 'uploads/student_report_cards/' . $serverName;

            $ok = $this->db->insert($tbl, $payload);
            if (!$ok) {
                @unlink($dest);
                echo json_encode(['success'=>false,'message'=>'Database error (file path insert)']); return;
            }
        }

        echo json_encode(['success'=>true,'message'=>'Report card uploaded successfully']);
    }

    public function get_report_cards()
    {
        header('Content-Type: application/json');
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            echo json_encode(['success' => false, 'message' => 'No permission']); return;
        }

        $student_id = (int)$this->input->post('student_id');
        if ($student_id <= 0) { echo json_encode(['success'=>false,'message'=>'Student ID is required']); return; }

        $tbl = db_prefix().'university_report_card';

        $this->db->select('
            id,
            student_university_id,
            upload_date,
            report_card_term,
            current_term,
            semester_end_month,
            semester_end_year,
            mime_type,
            file_size,
            sha256,
            filename,
            report_card_file
        ', false);
        $this->db->where('student_university_id', $student_id);
        $this->db->order_by('upload_date', 'DESC');

        $cards = $this->db->get($tbl)->result_array();

        foreach ($cards as &$card) {
            $card['upload_date'] = !empty($card['upload_date'])
                ? date('M d, Y', strtotime($card['upload_date']))
                : '';

            $name = trim((string)($card['filename'] ?? ''));
            if ($name === '' && !empty($card['report_card_file'])) {
                $name = basename($card['report_card_file']);
            }
            if ($name === '') {
                $ext = '';
                if ($card['mime_type'] === 'application/pdf') $ext = '.pdf';
                elseif (in_array($card['mime_type'], ['image/jpeg','image/jpg'], true)) $ext = '.jpg';
                elseif ($card['mime_type'] === 'image/png') $ext = '.png';
                $name = 'report_card_'.$card['id'].$ext;
            }
            $card['display_name'] = $name;

            $card['file_url'] = admin_url('student_sponsor_portal/download_report_card/'.$card['id']);
        }
        unset($card);

        echo json_encode(['success'=>true,'report_cards'=>$cards]);
    }

    public function download_report_card($id)
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) access_denied('student_sponsor_portal');

        $id = (int)$id;
        $tbl = db_prefix().'university_report_card';

        $row = $this->db->get_where($tbl, ['id'=>$id])->row_array();
        if (!$row) show_404();

        $filename = trim((string)($row['filename'] ?? 'report_card_'.$id));
        $filename = $filename !== '' ? $filename : ('report_card_'.$id);
        $mime = trim((string)($row['mime_type'] ?? 'application/octet-stream'));

        $blobCol = $this->db->field_exists('file_blob', $tbl) ? 'file_blob'
                : ($this->db->field_exists('report_card_blob', $tbl) ? 'report_card_blob' : null);

        header('X-Content-Type-Options: nosniff');
        header('Content-Type: '.$mime);
        header('Content-Disposition: attachment; filename="'.str_replace('"','',$filename).'"');

        if ($blobCol && !empty($row[$blobCol])) {
            $data = $row[$blobCol];
            header('Content-Length: '.strlen($data));
            echo $data;
            return;
        }

        $rel = trim((string)($row['report_card_file'] ?? ''));
        if ($rel === '') { show_404(); }
        $path = FCPATH . ltrim($rel, '/');
        if (!is_file($path)) { show_404(); }

        header('Content-Length: '.filesize($path));
        readfile($path);
    }

    public function delete_report_card()
    {
        header('Content-Type: application/json');

        if (!has_permission('student_sponsor_portal', '', 'delete')) {
            echo json_encode(['success' => false, 'message' => 'No permission', 'csrfHash' => $this->security->get_csrf_hash()]);
            return;
        }

        $report_card_id = (int)$this->input->post('report_card_id');
        if ($report_card_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Report card ID is required', 'csrfHash' => $this->security->get_csrf_hash()]);
            return;
        }

        $row = $this->db->get_where(db_prefix().'university_report_card', ['id' => $report_card_id])->row();
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Report card not found', 'csrfHash' => $this->security->get_csrf_hash()]);
            return;
        }

        $this->db->where('id', $report_card_id)->delete(db_prefix().'university_report_card');
        if ($this->db->affected_rows() > 0) {
            $rel = ltrim((string)$row->report_card_file, '/');
            $abs = rtrim(FCPATH, '/\\') . '/' . $rel;
            if (is_file($abs)) { @unlink($abs); }

            echo json_encode(['success' => true, 'message' => 'Report card deleted', 'csrfHash' => $this->security->get_csrf_hash()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete', 'csrfHash' => $this->security->get_csrf_hash()]);
        }
    }

    public function display_profile_photo($student_id)
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }
        
        $photo_data = $this->university_model->get_profile_photo((int)$student_id);
        
        if ($photo_data) {
            $mime_type = 'image/jpeg';
            
            if (class_exists('finfo')) {
                try {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $detected_mime = $finfo->buffer($photo_data);
                    if ($detected_mime) {
                        $mime_type = $detected_mime;
                    }
                } catch (Exception $e) {
                    // Use fallback
                }
            }
            elseif (function_exists('getimagesizefromstring')) {
                try {
                    $image_info = getimagesizefromstring($photo_data);
                    if ($image_info && isset($image_info['mime'])) {
                        $mime_type = $image_info['mime'];
                    }
                } catch (Exception $e) {
                    // Use fallback
                }
            }
            else {
                $signature = substr($photo_data, 0, 4);
                if (substr($signature, 0, 3) === "\xFF\xD8\xFF") {
                    $mime_type = 'image/jpeg';
                } elseif ($signature === "\x89PNG") {
                    $mime_type = 'image/png';
                } elseif (substr($signature, 0, 3) === "GIF") {
                    $mime_type = 'image/gif';
                } elseif (substr($signature, 0, 2) === "BM") {
                    $mime_type = 'image/bmp';
                }
            }
            
            header('Content-Type: ' . $mime_type);
            header('Content-Length: ' . strlen($photo_data));
            header('Cache-Control: max-age=3600');
            header('Pragma: public');
            
            echo $photo_data;
        } else {
            header('HTTP/1.0 404 Not Found');
            exit('Photo not found');
        }
    }

    public function add_country_ajax()
    {
        header('Content-Type: application/json');
        if (!has_permission('student_sponsor_portal', '', 'create')) {
            echo json_encode(['success'=>false,'message'=>'No permission']); return;
        }

        $name = trim((string)$this->input->post('country_name'));
        $code = trim((string)$this->input->post('phone_code'));
        if ($name === '' || $code === '') {
            echo json_encode(['success'=>false,'message'=>'Country name and phone code are required']); return;
        }

        $tbl = db_prefix().'countries';
        $this->db->group_start()->where('short_name',$name)->or_where('calling_code',$code)->group_end();
        $exists = $this->db->get($tbl)->row();
        if ($exists) {
            $this->output->set_header('X-CSRF-TOKEN: '.$this->security->get_csrf_hash());
            echo json_encode([
                'success'      => true,
                'message'      => 'Country already existed and was selected',
                'country_id'   => (int)$exists->country_id,
                'country_name' => (string)$exists->short_name,
                'phone_code'   => (string)$exists->calling_code,
            ]);
            return;
        }

        $this->db->insert($tbl, ['short_name'=>$name, 'calling_code'=>$code]);
        $id = (int)$this->db->insert_id();

        $this->output->set_header('X-CSRF-TOKEN: '.$this->security->get_csrf_hash());
        echo json_encode([
            'success'      => (bool)$id,
            'message'      => $id ? 'Country added successfully' : 'Failed to add country',
            'country_id'   => $id,
            'country_name' => $name,
            'phone_code'   => $code,
        ]);
    }

    public function add_university_ajax()
    {
        header('Content-Type: application/json');
        if (!has_permission('student_sponsor_portal', '', 'create')) {
            echo json_encode(['success'=>false,'message'=>'No permission']); return;
        }

        $name = trim((string)$this->input->post('university_name'));
        if ($name === '') { echo json_encode(['success'=>false,'message'=>'University name is required']); return; }

        $tbl = db_prefix().'university_name';
        $exists = $this->db->get_where($tbl, ['name'=>$name])->row();
        if ($exists) {
            $this->output->set_header('X-CSRF-TOKEN: '.$this->security->get_csrf_hash());
            echo json_encode([
                'success'=>true,'message'=>'University already existed and was selected',
                'university_id'=>(int)$exists->id,'university_name'=>(string)$exists->name
            ]); return;
        }

        $this->db->insert($tbl, ['name'=>$name]);
        $id = (int)$this->db->insert_id();

        $this->output->set_header('X-CSRF-TOKEN: '.$this->security->get_csrf_hash());
        echo json_encode([
            'success'=>(bool)$id, 'message'=>$id?'University added successfully':'Failed to add university',
            'university_id'=>$id, 'university_name'=>$name
        ]);
    }

    public function add_program_ajax()
    {
        header('Content-Type: application/json');
        if (!has_permission('student_sponsor_portal', '', 'create')) {
            echo json_encode(['success'=>false,'message'=>'No permission']); return;
        }

        $name = trim((string)$this->input->post('program_name'));
        if ($name === '') { echo json_encode(['success'=>false,'message'=>'Program name is required']); return; }

        $tbl = db_prefix().'university_program';
        $exists = $this->db->get_where($tbl, ['name'=>$name])->row();
        if ($exists) {
            $this->output->set_header('X-CSRF-TOKEN: '.$this->security->get_csrf_hash());
            echo json_encode([
                'success'=>true,'message'=>'Program already existed and was selected',
                'program_id'=>(int)$exists->id,'program_name'=>(string)$exists->name
            ]); return;
        }

        $this->db->insert($tbl, ['name'=>$name]);
        $id = (int)$this->db->insert_id();

        $this->output->set_header('X-CSRF-TOKEN: '.$this->security->get_csrf_hash());
        echo json_encode([
            'success'=>(bool)$id, 'message'=>$id?'Program added successfully':'Failed to add program',
            'program_id'=>$id, 'program_name'=>$name
        ]);
    }
    
    public function add_bank_ajax()
    {
        header('Content-Type: application/json');
        if (!has_permission('student_sponsor_portal', '', 'create')) {
            echo json_encode(['success'=>false,'message'=>'No permission']); return;
        }

        $name = trim((string)$this->input->post('bank_name'));
        if ($name === '') { echo json_encode(['success'=>false,'message'=>'Bank name is required']); return; }

        $tbl = db_prefix().'bank';
        $exists = $this->db->get_where($tbl, ['name'=>$name])->row();
        if ($exists) {
            $this->output->set_header('X-CSRF-TOKEN: '.$this->security->get_csrf_hash());
            echo json_encode([
                'success'=>true,'message'=>'Bank already existed and was selected',
                'bank_id'=>(int)$exists->id,'bank_name'=>(string)$exists->name
            ]); return;
        }

        $this->db->insert($tbl, ['name'=>$name]);
        $id = (int)$this->db->insert_id();

        $this->output->set_header('X-CSRF-TOKEN: '.$this->security->get_csrf_hash());
        echo json_encode([
            'success'=>(bool)$id, 'message'=>$id?'Bank added successfully':'Failed to add bank',
            'bank_id'=>$id, 'bank_name'=>$name
        ]);
    }

    public function search_university_students()
    {
        if (!is_staff_logged_in()) show_404();
        $q = trim($this->input->post('q', true));
        $page = max(1, (int)$this->input->post('page')); $limit=20; $offset=($page-1)*$limit;

        $this->db->select('id, name');
        if ($q !== '') { $this->db->like('name', $q); }
        $this->db->order_by('name','asc')->limit($limit+1, $offset);
        $rows = $this->db->get(db_prefix().'university_students')->result();

        $more = count($rows) > $limit; if ($more) array_pop($rows);
        $results = array_map(function($r){ return ['id'=>$r->id,'name'=>$r->name,'text'=>$r->name]; }, $rows);

        echo json_encode([
            'results'=>$results,
            'pagination'=>['more'=>$more],
            $this->security->get_csrf_token_name() => $this->security->get_csrf_hash()
        ]); die();
    }

    public function grant_university_access($student_id = null)
    {
        if (!has_permission('student_sponsor_portal', '', 'edit')) access_denied('student_sponsor_portal');

        $student_id = $this->resolve_id('student_id', 4);
        if ($student_id <= 0) { set_alert('danger','Missing student ID.'); redirect(admin_url('student_sponsor_portal/university_students')); }

        $s = $this->university_model->get_by_id($student_id);
        if (!$s) { set_alert('danger','Student not found.'); redirect(admin_url('student_sponsor_portal/university_students')); }

        $data = [
            'staff_email'     => $this->in('staff_email', $s['email'] ?? ''),
            'staff_firstname' => $this->in('staff_firstname', $s['name'] ?? 'Student'),
            'staff_lastname'  => $this->in('staff_lastname', ''),
            'staff_password'  => $this->in('staff_password', ''),
            'active'          => $this->in_bool('staff_active'),
        ];
        if ($data['staff_email'] === '') {
            set_alert('danger','Login email is required.');
            redirect(admin_url('student_sponsor_portal/university_student_form/'.$student_id));
        }

        $this->ensure_three_min_roles();
        $staff_id = $this->upsert_staff($data, $s['staff_id'] ?? null, 'University Student');
        if (!$staff_id) {
            set_alert('danger','Failed to create/update staff account.');
            redirect(admin_url('student_sponsor_portal/university_student_form/'.$student_id));
        }

        $tbl = db_prefix().'university_students';
        $update = ['staff_id' => (int)$staff_id];
        if ($this->db->field_exists('active', $tbl))       $update['active'] = (int)$data['active'];
        elseif ($this->db->field_exists('staff_active', $tbl)) $update['staff_active'] = (int)$data['active'];

        $this->db->where('id', (int)$student_id)->update($tbl, $update);

        set_alert('success', 'Portal access granted'.($data['staff_password']!==''?'. Password set as provided.':'. Existing password kept or auto-generated.'));
        redirect(admin_url('student_sponsor_portal/university_student_form/'.$student_id));
    }

    public function revoke_university_access($student_id = null)
    {
        if (!has_permission('student_sponsor_portal', '', 'edit')) access_denied('student_sponsor_portal');

        $student_id = $this->resolve_id('student_id', 4);
        if ($student_id <= 0) { set_alert('danger','Missing student ID.'); redirect(admin_url('student_sponsor_portal/university_students')); }

        $s = $this->university_model->get_by_id($student_id);
        if (!$s) { set_alert('danger','Student not found.'); redirect(admin_url('student_sponsor_portal/university_students')); }
        if (empty($s['staff_id'])) { set_alert('danger','No staff account linked.'); redirect(admin_url('student_sponsor_portal/university_student_form/'.$student_id)); }

        $this->db->where('staffid', (int)$s['staff_id'])->update(db_prefix().'staff', ['active'=>0]);

        $tbl = db_prefix().'university_students';
        $update = ['staff_id'=>null];
        if ($this->db->field_exists('active', $tbl))       $update['active']=0;
        elseif ($this->db->field_exists('staff_active', $tbl)) $update['staff_active']=0;
        $this->db->where('id', (int)$student_id)->update($tbl, $update);

        set_alert('success','Portal access revoked.');
        redirect(admin_url('student_sponsor_portal/university_student_form/'.$student_id));
    }




    /* =================================================================================== */
    /* =====================                 SPONSORS                  =================== */
    /* =================================================================================== */

    public function sponsors()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) access_denied('student_sponsor_portal');

        $data['title']    = 'Sponsor Management';
        $data['sponsors'] = $this->sponsor_model->get_all();
        $this->load->view('student_sponsor_portal/sponsors_list', $data);
    }

    public function sponsor_form($sponsor_id = null)
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        if ($this->input->method() === 'post') {
            $id        = $this->in_int('sponsor_id', 0);
            $post_data = $this->input->post(null, true);
            unset($post_data['sponsor_id']);

            if ($id === 0) {
                if (!has_permission('student_sponsor_portal', '', 'create')) access_denied('student_sponsor_portal');
                $newId = $this->sponsor_model->add($post_data);
                if ($newId) {
                    set_alert('success', 'Sponsor registered successfully');
                    redirect(admin_url('student_sponsor_portal/sponsors'));
                }
                set_alert('danger', 'Error creating sponsor');
            } else {
                if (!has_permission('student_sponsor_portal', '', 'edit')) access_denied('student_sponsor_portal');
                $ok = $this->sponsor_model->update($post_data, $id);
                if ($ok) {
                    set_alert('success', 'Sponsor updated successfully');
                    redirect(admin_url('student_sponsor_portal/sponsors'));
                }
                set_alert('danger', 'Error updating sponsor');
            }
        }

        $data              = [];
        $data['title']     = 'Register Sponsor';
        $data['sponsor']   = null;

        if ($sponsor_id) {
            $row = $this->sponsor_model->get_by_id((int)$sponsor_id);
            if (!$row) {
                set_alert('danger', 'Sponsor not found');
                redirect(admin_url('student_sponsor_portal/sponsors'));
            }
            $data['sponsor'] = $row;
            $data['title']   = 'Edit Sponsor';
        }

        $banks = [];
        if ($this->db->table_exists(db_prefix().'bank')) {
            $rs = $this->db->order_by('name','asc')->get(db_prefix().'bank')->result_array();
            foreach ($rs as $r) {
                $banks[] = ['id' => (int)$r['id'], 'name' => (string)$r['name']];
            }
        }
        $data['banks'] = $banks;

        $countries = [];
        if ($this->db->table_exists(db_prefix().'countries')) {
            $rs = $this->db->order_by('short_name','asc')->get(db_prefix().'countries')->result_array();
            foreach ($rs as $r) {
                $countries[] = ['id' => (int)$r['country_id'], 'name' => (string)$r['short_name']];
            }
        }
        $data['countries'] = $countries;

        $states = [];
        $selected_country_id = isset($data['sponsor']['country_id']) ? (int)$data['sponsor']['country_id'] : 0;
        if ($selected_country_id > 0 && $this->db->table_exists(db_prefix().'state')) {
            $rs = $this->db->where('country_id', $selected_country_id)
                           ->order_by('name','asc')
                           ->get(db_prefix().'state')->result_array();
            foreach ($rs as $r) {
                $states[] = ['id' => (int)$r['id'], 'name' => (string)$r['name']];
            }
        }
        $data['states'] = $states;

        $this->load->view('student_sponsor_portal/sponsor_form', $data);
    }


    public function ajax_bank_create()
    {
        if (!is_admin() && !has_permission('student_sponsor_portal','','create')) {
            show_error('Forbidden', 403);
        }
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $name   = trim((string)$this->input->post('name', true));
        $branch = trim((string)$this->input->post('branch', true));
        $ifsc   = trim((string)$this->input->post('ifsc', true));

        if ($name === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Bank name is required',
                $this->security->get_csrf_token_name() => $this->security->get_csrf_hash(),
            ]); return;
        }

        $row = ['name'=>$name];
        if ($this->db->field_exists('branch', db_prefix().'bank')) $row['branch'] = $branch;
        if ($this->db->field_exists('code',   db_prefix().'bank')) $row['code']   = $ifsc;

        $ok = $this->db->insert(db_prefix().'bank', $row);
        if (!$ok) {
            echo json_encode([
                'success'=>false,
                'message'=>'Insert failed',
                $this->security->get_csrf_token_name() => $this->security->get_csrf_hash(),
            ]); return;
        }

        $id = (int)$this->db->insert_id();
        echo json_encode([
            'success'=>true,
            'id'=>$id,
            'text'=>$name,
            $this->security->get_csrf_token_name() => $this->security->get_csrf_hash(),
        ]);
    }

    public function ajax_states($country_id)
    {
        if (!$this->input->is_ajax_request()) { show_404(); }
        $out = [];
        if ($this->db->table_exists(db_prefix().'state')) {
            $rows = $this->db->where('country_id', (int)$country_id)
                             ->order_by('name','asc')->get(db_prefix().'state')->result_array();
            foreach ($rows as $r) { $out[] = ['id'=>$r['id'], 'name'=>$r['name']]; }
        }
        echo json_encode([
            'results'=>$out,
            $this->security->get_csrf_token_name() => $this->security->get_csrf_hash(),
        ]);
    }

    public function get_sponsor()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) $this->respond_json(false, 'Access denied');

        $id = $this->in_int('sponsor_id', 0);
        if ($id <= 0) $this->respond_json(false, 'Invalid sponsor ID');

        $s = $this->sponsor_model->get_by_id($id);
        if (!$s) $this->respond_json(false, 'Sponsor not found');

        if ($this->in('action') === 'view') {
            ob_start(); ?>
            <div class="row">
              <div class="col-md-6">
                <h5><strong>Basic Information</strong></h5>
                <p><strong>Name:</strong> <?= htmlspecialchars($s['name'] ?? '') ?></p>
                <p><strong>Type:</strong> <?= htmlspecialchars($s['sponsor_type'] ?? '') ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($s['email'] ?? '') ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($s['phone'] ?? '') ?></p>
              </div>
              <div class="col-md-6">
                <h5><strong>Additional Information</strong></h5>
                <p><strong>Occupation:</strong> <?= htmlspecialchars($s['sponsor_occupation'] ?? '') ?></p>
                <p><strong>Address:</strong> <?= htmlspecialchars($s['address'] ?? '') ?></p>
                <p><strong>City:</strong> <?= htmlspecialchars($s['city'] ?? '') ?></p>
              </div>
            </div>
            <?php
            $this->respond_json(true, 'OK', ['html' => ob_get_clean()]);
        }

        $this->respond_json(true, 'OK', ['sponsor' => $s]);
    }

    public function delete_sponsor()
    {
        if (!has_permission('student_sponsor_portal', '', 'delete')) $this->respond_json(false, 'Access denied');

        $id = $this->in_int('sponsor_id', 0);
        if ($id <= 0) $this->respond_json(false, 'Invalid sponsor ID');

        $ok = $this->sponsor_model->delete_sponsor($id);
        $this->respond_json((bool)$ok, $ok ? 'Sponsor deleted successfully' : 'Error deleting sponsor');
    }

    public function grant_sponsor_access($sponsor_id = null)
    {
        if (!has_permission('student_sponsor_portal', '', 'edit')) access_denied('student_sponsor_portal');

        $sponsor_id = $this->resolve_id('sponsor_id', 4);
        if ($sponsor_id <= 0) { set_alert('danger','Missing sponsor ID.'); redirect(admin_url('student_sponsor_portal/sponsors')); }

        $s = $this->sponsor_model->get_by_id($sponsor_id);
        if (!$s) { set_alert('danger','Sponsor not found.'); redirect(admin_url('student_sponsor_portal/sponsors')); }

        $data = [
            'staff_email'     => $this->in('staff_email', $s['email'] ?? ''),
            'staff_firstname' => $this->in('staff_firstname', $s['name'] ?? 'Sponsor'),
            'staff_lastname'  => $this->in('staff_lastname', ''),
            'staff_password'  => $this->in('staff_password', ''),
            'active'          => $this->in_bool('staff_active'),
        ];
        if ($data['staff_email'] === '') {
            set_alert('danger','Login email is required.');
            redirect(admin_url('student_sponsor_portal/sponsor_form/'.$sponsor_id));
        }

        $this->ensure_three_min_roles();
        $staff_id = $this->upsert_staff($data, null, 'Sponsor');
        if (!$staff_id) {
            set_alert('danger','Failed to create/update staff account.');
            redirect(admin_url('student_sponsor_portal/sponsor_form/'.$sponsor_id));
        }

        $sp_tbl = db_prefix().'sponsor_records';
        $update = ['staff_id' => (int)$staff_id];
        if ($this->db->field_exists('active', $sp_tbl))       $update['active'] = (int)$data['active'];
        elseif ($this->db->field_exists('staff_active', $sp_tbl)) $update['staff_active'] = (int)$data['active'];
        $this->db->where('id', (int)$sponsor_id)->update($sp_tbl, $update);

        set_alert('success', 'Portal access granted'.($data['staff_password']!==''?'. Password set as provided.':'. Existing password kept or auto-generated.'));
        redirect(admin_url('student_sponsor_portal/sponsor_form/'.$sponsor_id));
    }

    public function revoke_sponsor_access($sponsor_id = null)
    {
        if (!has_permission('student_sponsor_portal', '', 'edit')) access_denied('student_sponsor_portal');

        $sponsor_id = $this->resolve_id('sponsor_id', 4);
        if ($sponsor_id <= 0) { set_alert('danger','Missing sponsor ID.'); redirect(admin_url('student_sponsor_portal/sponsors')); }

        $s = $this->sponsor_model->get_by_id($sponsor_id);
        if (!$s) { set_alert('danger','Sponsor not found.'); redirect(admin_url('student_sponsor_portal/sponsors')); }
        if (empty($s['staff_id'])) { set_alert('danger','No staff account linked.'); redirect(admin_url('student_sponsor_portal/sponsor_form/'.$sponsor_id)); }

        $this->db->where('staffid', (int)$s['staff_id'])->update(db_prefix().'staff', ['active'=>0]);

        $sp_tbl = db_prefix().'sponsor_records';
        $update = ['staff_id'=>null];
        if ($this->db->field_exists('active', $sp_tbl))       $update['active']=0;
        elseif ($this->db->field_exists('staff_active', $sp_tbl)) $update['staff_active']=0;
        $this->db->where('id', (int)$sponsor_id)->update($sp_tbl, $update);

        set_alert('success','Portal access revoked.');
        redirect(admin_url('student_sponsor_portal/sponsor_form/'.$sponsor_id));
    }

    public function search_sponsors()
    {
        if (!is_staff_logged_in()) show_404();
        $q = trim($this->input->post('q', true));
        $page = max(1, (int)$this->input->post('page'));
        $limit = 20; $offset = ($page-1)*$limit;

        $this->db->select('id, name');
        if ($q !== '') { $this->db->like('name', $q); }
        $this->db->order_by('name','asc')->limit($limit+1, $offset);
        $rows = $this->db->get(db_prefix().'sponsor_records')->result();

        $more = count($rows) > $limit;
        if ($more) array_pop($rows);

        $results = [];
        foreach ($rows as $r) {
            $results[] = ['id'=>$r->id, 'name'=>$r->name, 'text'=>$r->name];
        }

        $out = [
            'results' => $results,
            'pagination' => ['more'=>$more],
            $this->security->get_csrf_token_name() => $this->security->get_csrf_hash()
        ];
        echo json_encode($out); die();
    }




    /* =================================================================================== */
    /* =====================           TRANSACTIONS & PAYMENTS          =================== */
    /* =================================================================================== */

    public function payments()
    {
        if (!(is_admin() || has_permission('student_sponsor_portal', '', 'view'))) access_denied('student_sponsor_portal');

        $filters = [
            'date_from'    => trim($this->input->get('date_from') ?? ''),
            'date_to'      => trim($this->input->get('date_to') ?? ''),
            'sponsor_id'   => (int)($this->input->get('sponsor_id') ?? 0),
            'student_type' => trim($this->input->get('student_type') ?? ''),
            'currency'     => trim($this->input->get('currency') ?? ''),
            'min_amount'   => (float)($this->input->get('min_amount') ?? 0),
            'max_amount'   => (float)($this->input->get('max_amount') ?? 0),
            'has_note'     => $this->input->get('has_note') === '1' ? 1 : 0,
            'q'            => trim($this->input->get('q') ?? ''),
            'created_by'   => (int)($this->input->get('created_by') ?? 0),
        ];

        $per_page = 25;
        $page     = max(1, (int)($this->input->get('page') ?? 1));
        $offset   = ($page - 1) * $per_page;

        $data['title']     = 'Sponsor Payments';
        $data['filters']   = $filters;
        $data['payments']  = $this->txn_model->list_payments($filters, $per_page, $offset);
        $data['total']     = $this->txn_model->count_payments($filters);
        $data['per_page']  = $per_page;
        $data['page']      = $page;

        $data['sponsors']  = $this->db->select('id,name')->order_by('name')->get('tblsponsor_records')->result();
        $data['staff']     = $this->db->select('staffid,firstname,lastname')->order_by('firstname')->get(db_prefix().'staff')->result();

        $this->load->view('transactions/payments_index', $data);
    }

    public function transactions()
    {
        if (!(is_admin() || has_permission('student_sponsor_portal', '', 'view'))) access_denied('student_sponsor_portal');
        $data['title'] = 'Sponsor Transactions';
        $data['txns']  = $this->txn_model->get();
        $this->load->view('transactions/list', $data);
    }

    public function transaction($id = null)
    {
        if ($id) {
            if (!has_permission('student_sponsor_portal', '', 'edit')) access_denied('student_sponsor_portal');
            $data['txn'] = $this->txn_model->get($id);
            if (!$data['txn']) show_404();
            $data['title'] = 'Edit Transaction';
        } else {
            if (!has_permission('student_sponsor_portal', '', 'create')) access_denied('student_sponsor_portal');
            $data['txn'] = null;
            $data['title'] = 'Create Transaction';
        }

        $CI = &get_instance();
        $data['sponsors']     = $CI->db->select('id,name')->order_by('name')->get('tblsponsor_records')->result();
        $data['schools']      = $CI->db->select('id,name')->order_by('name')->get('tblschool_students')->result();
        $data['universities'] = $CI->db->select('id,name')->order_by('name')->get('tbluniversity_students')->result();

        $this->load->view('transactions/form', $data);
    }

    public function transaction_save()
    {
        if (!is_admin() && !has_permission('student_sponsor_portal','', 'create') && !has_permission('student_sponsor_portal','', 'edit')) {
            access_denied('student_sponsor_portal');
        }

        $id   = (int)$this->input->post('id');
        $data = $this->input->post();

        $schoolRaw = isset($data['school_student_id']) ? trim((string)$data['school_student_id']) : '';
        $uniRaw    = isset($data['university_student_id']) ? trim((string)$data['university_student_id']) : '';

        $schoolId  = ($schoolRaw !== '') ? (int)$schoolRaw : 0;
        $uniId     = ($uniRaw   !== '') ? (int)$uniRaw    : 0;

        if ($uniId > 0 && $schoolId > 0) {
            set_alert('danger', 'Pick exactly one: School student or University student.');
            $back = $id ? 'student_sponsor_portal/transaction/'.$id.'?tab=details'
                        : 'student_sponsor_portal/transaction?tab=details';
            redirect(admin_url($back));
        }

        if ($uniId > 0) {
            $data['university_student_id'] = $uniId;
            $data['school_student_id']     = null;
        } elseif ($schoolId > 0) {
            $data['school_student_id']     = $schoolId;
            $data['university_student_id'] = null;
        } else {
            set_alert('danger', 'Please select a student (school or university).');
            $back = $id ? 'student_sponsor_portal/transaction/'.$id.'?tab=details'
                        : 'student_sponsor_portal/transaction?tab=details';
            redirect(admin_url($back));
        }

        $data['sponsor_id'] = isset($data['sponsor_id']) ? (int)$data['sponsor_id'] : 0;
        if ($data['sponsor_id'] <= 0) {
            set_alert('danger', 'Sponsor is required.');
            $back = $id ? 'student_sponsor_portal/transaction/'.$id.'?tab=details'
                        : 'student_sponsor_portal/transaction?tab=details';
            redirect(admin_url($back));
        }

        $data['total_amount'] = isset($data['total_amount']) ? (float)$data['total_amount'] : 0.0;
        $data['amount_paid']  = isset($data['amount_paid'])  ? (float)$data['amount_paid']  : 0.0;
        $data['currency']     = isset($data['currency']) && $data['currency'] !== '' ? trim($data['currency']) : 'INR';

        if ($id) {
            if (!has_permission('student_sponsor_portal', '', 'edit')) access_denied('student_sponsor_portal');
            $ok = $this->txn_model->update($id, $data);
            if ($ok) { $this->txn_model->recompute_next_due_from_type($id); }

            set_alert($ok ? 'success' : 'warning', $ok ? 'Updated' : 'Update failed');
            redirect(admin_url('student_sponsor_portal/transaction/'.$id));
        } else {
            if (!has_permission('student_sponsor_portal', '', 'create')) access_denied('student_sponsor_portal');
            $newId = $this->txn_model->create($data);
            if ($newId) { $this->txn_model->recompute_next_due_from_type($newId); }
            set_alert($newId ? 'success' : 'warning', $newId ? 'Added' : 'Add failed');
            redirect(admin_url($newId
                ? 'student_sponsor_portal/transaction/'.$newId
                : 'student_sponsor_portal/transaction'));
        }
    }

    public function transaction_delete($id)
    {
        if (!has_permission('student_sponsor_portal', '', 'delete')) access_denied('student_sponsor_portal');
        $ok = $this->txn_model->delete($id);
        set_alert($ok ? 'success' : 'warning', $ok ? 'Deleted' : 'Delete failed');
        redirect(admin_url('student_sponsor_portal/transactions'));
    }

    public function add_payment($transaction_id)
    {
        if (!is_admin() && !has_permission('student_sponsor_portal', '', 'create')) {
            access_denied('student_sponsor_portal');
        }

        $transaction_id = (int) $transaction_id;
        if ($transaction_id <= 0) {
            show_error('Invalid transaction id', 400);
        }

        $txn = $this->db->where('id', $transaction_id)
                        ->get(db_prefix().'sponsor_transactions')->row();
        if (!$txn) {
            show_error('Transaction not found', 404);
        }

        $pdate    = trim($this->input->post('payment_date', true));
        $amount   = (float) $this->input->post('amount', true);
        $currency = trim($this->input->post('currency', true));
        $note     = trim($this->input->post('note', true));
        $sponsor  = (int) $this->input->post('sponsor_id', true);
        $student  = (int) $this->input->post('student_id', true);

        if (!$pdate || !$amount) {
            set_alert('warning', 'Payment date and amount are required.');
            redirect(admin_url('student_sponsor_portal/transaction/'.$transaction_id.'?tab=payments'));
        }

        $row = [
            'transaction_id' => $transaction_id,
            'sponsor_id'     => $sponsor ?: (int)$txn->sponsor_id,
            'student_id'     => $student ?: (int)($txn->school_student_id ?: $txn->university_student_id),
            'payment_date'   => $pdate,
            'amount'         => $amount,
            'currency'       => $currency ?: $txn->currency,
            'note'           => $note,
            'created_by'     => get_staff_user_id(),
            'created_at'     => date('Y-m-d H:i:s'),
        ];
        $ok = $this->db->insert(db_prefix().'sponsor_payments', $row);

        if (!$ok) {
            set_alert('warning', 'Failed to add payment.');
            redirect(admin_url('student_sponsor_portal/transaction/'.$transaction_id.'?tab=payments'));
        }

        $sum = $this->db->select_sum('amount', 's')
                        ->where('transaction_id', $transaction_id)
                        ->get(db_prefix().'sponsor_payments')->row();
        $amount_paid = (float) ($sum ? $sum->s : 0);

        $last = $this->db->select('payment_date')
                         ->where('transaction_id', $transaction_id)
                         ->order_by('payment_date', 'DESC')
                         ->limit(1)
                         ->get(db_prefix().'sponsor_payments')->row();
        $last_payment_date = $last ? $last->payment_date : null;

        $next_payment_due = null;
        if ($last_payment_date) {
            $dt = DateTime::createFromFormat('Y-m-d', $last_payment_date) ?: new DateTime($last_payment_date);
            if ($dt) {
                switch ($txn->payment_type) {
                    case 'monthly':
                        $dt->modify('+1 month');
                        $next_payment_due = $dt->format('Y-m-d');
                        break;
                    case 'quarterly':
                        $dt->modify('+3 months');
                        $next_payment_due = $dt->format('Y-m-d');
                        break;
                    case 'yearly':
                        $dt->modify('+1 year');
                        $next_payment_due = $dt->format('Y-m-d');
                        break;
                    default:
                        $next_payment_due = null;
                }
            }
        }

        $this->db->where('id', $transaction_id)->update(db_prefix().'sponsor_transactions', [
            'amount_paid'       => $amount_paid,
            'balance_amount'    => max(0, (float)$txn->total_amount - $amount_paid),
            'last_payment_date' => $last_payment_date,
            'next_payment_due'  => $next_payment_due,
        ]);

        set_alert('success', 'Payment added.');
        redirect(admin_url('student_sponsor_portal/transaction/'.$transaction_id.'?tab=payments'));
    }

    public function edit_payment($transaction_id, $payment_id)
    {
        if (!has_permission('student_sponsor_portal', '', 'edit')) access_denied('student_sponsor_portal');

        $transaction_id = (int) $transaction_id;
        $payment_id     = (int) $payment_id;

        if ($this->input->post()) {
            $data = [
                'payment_date' => $this->input->post('payment_date'),
                'amount'       => $this->input->post('amount'),
                'currency'     => $this->input->post('currency'),
                'note'         => $this->input->post('note'),
            ];

            $ok = $this->txn_model->update_payment($payment_id, $data);
            if ($ok) {
                $this->txn_model->recompute_amount_paid($transaction_id);
                set_alert('success', 'Payment updated');
            } else {
                set_alert('warning', 'Update failed');
            }
            redirect(admin_url('student_sponsor_portal/transaction/'.$transaction_id.'?tab=payments'));
        }

        redirect(admin_url('student_sponsor_portal/transaction/'.$transaction_id.'?tab=payments&edit_payment='.$payment_id));
    }

    public function delete_payment($transaction_id, $payment_id)
    {
        if (!has_permission('student_sponsor_portal', '', 'delete')) access_denied('student_sponsor_portal');

        $transaction_id = (int) $transaction_id;
        $payment_id     = (int) $payment_id;

        $ok = $this->txn_model->delete_payment($payment_id);
        if ($ok) {
            $this->txn_model->recompute_amount_paid($transaction_id);
            set_alert('success', 'Payment deleted');
        } else {
            set_alert('warning', 'Delete failed');
        }
        redirect(admin_url('student_sponsor_portal/transaction/'.$transaction_id.'?tab=payments'));
    }


    /* =================================================================================== */
    /* =====================                  HELPER METHODS             =================== */
    /* =================================================================================== */

    private function _get_countries()
    {
        if ($this->db->table_exists(db_prefix() . 'countries')) {
            return $this->db->select('country_id as id, short_name as name, calling_code as phone_code')
                           ->order_by('short_name', 'ASC')
                           ->get(db_prefix() . 'countries')
                           ->result_array();
        }
        return [];
    }

    private function in(string $key, string $default = '')
    {
        $val = $this->input->post($key, true);
        if ($val === null) return $default;
        return is_string($val) ? trim($val) : $default;
    }

    private function in_int(string $key, int $default = 0): int
    {
        $val = $this->input->post($key, true);
        if ($val === null || $val === '') return $default;
        return max(0, (int)$val);
    }

    private function in_bool(string $key): int
    {
        return $this->input->post($key) !== null ? 1 : 0;
    }

    private function respond_json(bool $ok, string $msg, array $extra = []): void
    {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
        exit;
    }

    private function resolve_id(string $post_key, int $segment = 4): int
    {
        $id = $this->in_int($post_key, 0);
        if ($id > 0) return $id;
        $from_uri = (int)$this->uri->segment($segment);
        return $from_uri > 0 ? $from_uri : 0;
    }

    private function ensure_minimal_role(string $role_name): int
    {
        $roles_tbl = db_prefix().'roles';
        $role = $this->db->get_where($roles_tbl, ['name' => $role_name])->row();
        if ($role) return (int)$role->roleid;

        $this->db->insert($roles_tbl, ['name' => $role_name]);
        return (int)$this->db->insert_id();
    }

    private function ensure_three_min_roles(): void
    {
        $this->ensure_minimal_role('Sponsor');
        $this->ensure_minimal_role('School Student');
        $this->ensure_minimal_role('University Student');
    }

    private function upsert_staff(array $data, ?int $existing_staff_id, string $role_name): ?int
    {
        $staff_tbl = db_prefix().'staff';

        $email     = isset($data['staff_email']) ? trim((string)$data['staff_email'])
                     : (isset($data['email']) ? trim((string)$data['email']) : '');
        $firstname = trim((string)($data['staff_firstname'] ?? $data['name'] ?? ''));
        $lastname  = trim((string)($data['staff_lastname'] ?? ''));
        $password  = trim((string)($data['staff_password'] ?? ''));
        $active    = !empty($data['active']) ? 1 : 0;

        if ($email === '' || $firstname === '') return null;

        $payload = [
            'email'     => $email,
            'firstname' => $firstname,
            'lastname'  => $lastname,
            'admin'     => 0,
            'active'    => (int)$active,
        ];

        if ($this->db->field_exists('role', $staff_tbl)) {
            $payload['role'] = $this->ensure_minimal_role($role_name);
        }

        if ($password !== '') {
            $payload['password'] = app_hash_password($password);
        }

        if ($existing_staff_id) {
            $this->db->where('staffid', (int)$existing_staff_id)->update($staff_tbl, $payload);
            return (int)$existing_staff_id;
        }

        $existing = $this->db->get_where($staff_tbl, ['email' => $email])->row();
        if ($existing) {
            $this->db->where('staffid', (int)$existing->staffid)->update($staff_tbl, $payload);
            return (int)$existing->staffid;
        }

        if (empty($payload['password'])) {
            $auto = bin2hex(random_bytes(4));
            $payload['password'] = app_hash_password($auto);
        }

        $this->db->insert($staff_tbl, $payload);
        return (int)$this->db->insert_id();
    }

    private function create_or_update_staff_from_student(array $student_data, ?int $existing_staff_id): ?int
    {
        $staff_data = [
            'staff_email'     => $student_data['email'] ?? '',
            'staff_firstname' => $student_data['name'] ?? '',
            'staff_lastname'  => '',
            'staff_password'  => $student_data['staff_password'] ?? '',
            'active'          => !empty($student_data['active']) ? 1 : 0,
        ];

        return $this->upsert_staff($staff_data, $existing_staff_id, 'Student');
    }
}