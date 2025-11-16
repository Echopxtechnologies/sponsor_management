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
            $this->_check_university_student_access(); 
            // Add access control check on every request
            $this->_check_school_student_access();
            
            log_message('debug', 'Student_sponsor_portal controller loaded successfully');
        }

        /* ===================== DASHBOARD ===================== */
    public function index()
    {
        $this->load->model('student_sponsor_portal/school_model', 'school_model');
        $this->load->model('student_sponsor_portal/sponsor_model', 'sponsor_model');
        $this->load->model('student_sponsor_portal/university_model', 'university_model');
        $this->load->model('student_sponsor_portal/Sponsor_transactions_model', 'txn_model');
        $this->load->model('student_sponsor_portal/dashboard_model');
        
        // Student checks
        $current_university_student = $this->is_university_student_user();
        if ($current_university_student) {
            redirect(admin_url('student_sponsor_portal/university_student_form/' . $current_university_student->id));
            return;
        }
        
        $current_student = $this->is_school_student_user();
        if ($current_student) {
            redirect(admin_url('student_sponsor_portal/school_student_form/' . $current_student->id));
            return;
        }
        
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        // Get dashboard data
        $dashboard_data = $this->dashboard_model->get_dashboard_data([
            'time_range' => 30,
            'recent_limit' => 10,
            'trend_months' => 6,
            'include_charts' => true,
            'include_pending' => true
        ]);
        
        // Merge all dashboard data into $data array for the view
        $data = array_merge([
            'title' => 'Student Sponsor Portal Dashboard'
        ], $dashboard_data);
        
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

    /* ===================== ACCESS CONTROL METHODS ===================== */

        /**
         * Check if current user is a school student with entity_type = 'school'
         */
        private function is_school_student_user()
        {
            if (!is_staff_logged_in()) {
                return false;
            }
            
            $staff_id = get_staff_user_id();
            
            // Check if this staff member is linked to a school student
            $student = $this->db->select('id, entity_type, school_internal_id, name')
                            ->where('staff_id', $staff_id)
                            ->where('entity_type', 'school')
                            ->where('staff_active', 1)
                            ->get(db_prefix() . 'school_students')
                            ->row();
            
            return $student ? $student : false;
        }

        /**
         * Get the school student record for the current logged-in staff member
         */
        private function get_current_student_record()
        {
            $staff_id = get_staff_user_id();
            
            return $this->db->where('staff_id', $staff_id)
                        ->where('entity_type', 'school')
                        ->get(db_prefix() . 'school_students')
                        ->row_array();
        }

        /**
         * Check if user can access a specific student record
         */
        private function can_access_student($student_id)
        {
            // Admin can access all
            if (is_admin()) {
                return true;
            }
            
            // Check if user is a school student trying to access their own record
            $current_student = $this->is_school_student_user();
            if ($current_student) {
                return (int)$current_student->id === (int)$student_id;
            }
            
            // Regular permission check for other staff
            return has_permission('student_sponsor_portal', '', 'view');
        }

    /**
         * REPLACE your _check_school_student_access() method with this fixed version
         */
        private function _check_school_student_access()
        {
            // Skip access control for AJAX requests and specific methods
            if ($this->input->is_ajax_request()) {
                return;
            }
            
            $current_student = $this->is_school_student_user();
            
            if ($current_student) {
                $method = $this->router->fetch_method();
                
                // Allowed methods for school students
                $allowed_methods = [
                    'index',  // Add this to prevent redirect loops
                    'school_student_form',
                    'get_school_student', 
                    'display_school_photo',
                    'upload_school_report_card',
                    'get_school_report_cards',
                    'download_school_report_card'
                ];
                
                // Only redirect if accessing truly forbidden methods
                if (!in_array($method, $allowed_methods)) {
                    // Use a more gentle redirect that preserves session
                    set_alert('info', 'You have been redirected to your profile.');
                    redirect(admin_url('student_sponsor_portal/school_student_form/' . $current_student->id));
                }
            }
        }

        /* =================================================================================== */
        /* =====================              SCHOOL STUDENTS              =================== */
        /* =================================================================================== */

        public function school_students()
        {
            // School students can't access the list - redirect to their own form
            $current_student = $this->is_school_student_user();
            if ($current_student) {
                redirect(admin_url('student_sponsor_portal/school_student_form/' . $current_student->id));
                return;
            }
            
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
        
    // Add this to your Student_sponsor_portal.php controller

        // In the school_student_form method, update the validation and saving logic:
    public function school_student_form($student_id = null)
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) access_denied('student_sponsor_portal');

        try {
            log_message('debug', 'Loading school student form for ID: ' . ($student_id ?: 'new'));

            // IMPORTANT: Define $current_student at the beginning to avoid undefined variable error
            $current_student = $this->is_school_student_user();
            $is_school_student = (bool)$current_student;

            if ($this->input->post()) {
                $isCreate = empty($this->input->post('student_id'));
                if ($isCreate && !has_permission('student_sponsor_portal', '', 'create')) access_denied('student_sponsor_portal');
                if (!$isCreate && !has_permission('student_sponsor_portal', '', 'edit')) access_denied('student_sponsor_portal');

                $post = $this->input->post();
                $sid = (int)($post['student_id'] ?? 0);
                unset($post['student_id']);

                log_message('debug', 'Raw POST data: ' . json_encode($post));

                // Clean and map form data to database fields
                $cleaned_data = $this->clean_school_post_data($post, $is_school_student);

                // Validate age-grade combination if both are present
                if (!empty($cleaned_data['school_grade']) && !empty($cleaned_data['school_age'])) {
                    $validation_result = $this->school_model->validate_age_grade(
                        $cleaned_data['school_grade'], 
                        $cleaned_data['school_age'], 
                        $cleaned_data['grade_mismatch_reason'] ?? null
                    );
                    
                    if (!$validation_result['valid']) {
                        $this->session->set_flashdata('old_input', $post);
                        set_alert('danger', $validation_result['message']);
                        if ($isCreate) {
                            redirect(admin_url('student_sponsor_portal/school_student_form'));
                        } else {
                            redirect(admin_url('student_sponsor_portal/school_student_form/' . $sid));
                        }
                        return;
                    }
                }

                $this->session->set_flashdata('old_input', $post);

                if ($isCreate) {
                    $res = $this->school_model->add($cleaned_data); 
                    
                    // Handle both old format (just ID) and new format (array with success flag)
                    if (is_array($res)) {
                        if (!$res['success']) {
                            $this->session->set_flashdata('old_input', $post);
                            set_alert('danger', $res['message'] ?? 'Error saving student');
                            redirect(admin_url('student_sponsor_portal/school_student_form'));
                            return;
                        }
                        $newId = (int)$res['id'];
                    } else {
                        $newId = $res ? (int)$res : 0;
                        if (!$newId) {
                            $this->session->set_flashdata('old_input', $post);
                            set_alert('danger', 'Error saving student');
                            redirect(admin_url('student_sponsor_portal/school_student_form'));
                            return;
                        }
                    }

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

                    log_message('debug', 'Attempting to update student ID: ' . $sid . ' with data: ' . json_encode($cleaned_data));
                    
                    // Get student data before update for comparison
                    $before_update = $this->school_model->get_by_id($sid);
                    log_message('debug', 'Student data BEFORE update: ' . json_encode($before_update));

                    $result = $this->school_model->update($cleaned_data, $sid);
                    
                    // Handle both boolean and array responses
                    if (is_array($result)) {
                        if (!$result['success']) {
                            set_alert('danger', $result['message'] ?? 'Error updating student');
                            redirect(admin_url('student_sponsor_portal/school_student_form/' . $sid));
                            return;
                        }
                        $ok = true;
                    } else {
                        $ok = $result;
                    }
                    
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
                        return;
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

            // If school student, force them to only access their own record
            if ($current_student) {
                $student_id = (int)$current_student->id;
            }

            $data['title'] = 'Register School Student';
            if ($student_id) {
                $student = $this->school_model->get_by_id((int)$student_id);
                if (!$student) { 
                    set_alert('danger', 'Student not found');
                    redirect(admin_url('student_sponsor_portal/school_students')); 
                }
                
                // Access control: school students can only view their own record
                if ($current_student && (int)$student['id'] !== (int)$current_student->id) {
                    access_denied('student_sponsor_portal');
                }
                
                $data['student'] = $student;
                $data['title'] = $is_school_student ? 'My Profile' : 'Edit School Student';
            }

            // Pass the school student flag to the view
            $data['is_school_student'] = $is_school_student;
            $data['can_edit_restricted_fields'] = !$is_school_student;

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
        // Updated clean_school_post_data method in Student_sponsor_portal.php
        private function clean_school_post_data($data, $is_school_student = false)
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
            'grade' => 'school_grade',
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
            'school_id' => 'school_id',
            'school_internal_id' => 'school_internal_id',
            'school_type' => 'school_type',
            'grade_mismatch_reason' => 'grade_mismatch_reason', 
            'school_name_id' => 'school_name_id',
            'school_grade_year' => 'school_grade_year',  // Added this mapping
        ];

        // Admin-only fields (students CANNOT edit these)
        $admin_only_fields = [
            'sponsorship_start' => 'school_sponsorship_start_date',
            'sponsorship_end' => 'school_sponsorship_end_date',
            'introduced_by' => 'school_introducedby',
            'introduced_phone' => 'school_introducedph',
            'internal_comment' => 'internal_comment',
            'external_comment' => 'external_comment'
        ];

        // Add admin-only fields to mappings only if user is admin
        if (!$is_school_student) {
            $field_mappings = array_merge($field_mappings, $admin_only_fields);
        }

        // Loop through all the field mappings and clean data
        foreach ($field_mappings as $form_field => $db_field) {
            if (isset($data[$form_field])) {
                $value = is_string($data[$form_field]) ? trim($data[$form_field]) : $data[$form_field];
                
                if ($form_field === 'grade' && $value !== '' && $value !== null) {
                    $normalized = $this->normalize_grade($value);
                    $cleaned[$db_field] = $normalized;
                    log_message('debug', "Grade normalization: '{$value}' → '{$normalized}'");
                    continue; // Skip the default processing below
                }
                
                // Handle different data types
                if ($value === '' || $value === null) {
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
        
        // IMPORTANT: Don't set entity_type for student updates to avoid conflicts
        if (!$is_school_student) {
            $cleaned['entity_type'] = 'school';
        }
        
        // Handle new country creation (admin only)
        if (!$is_school_student && !empty($data['new_country_name']) && !empty($data['new_country_phone_code'])) {
            $country_id = $this->create_new_country($data['new_country_name'], $data['new_country_phone_code']);
            if ($country_id) {
                $cleaned['country_id'] = $country_id;
            }
        }

        // Handle the 'created_on' field, set it to the current timestamp if not present
        if (!isset($cleaned['created_on'])) {
            $cleaned['created_on'] = date('Y-m-d H:i:s'); // Set current timestamp
        }

        log_message('debug', 'Cleaned school student data: ' . json_encode($cleaned));
        
        return $cleaned;
    }

    private function handle_profile_photo_upload($student_id)
    {
        if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
            log_message('debug', 'No profile photo uploaded or upload error for student ID: ' . $student_id);
            return false;
        }
        
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        $file = $_FILES['profile_photo'];
        
        // Size validation
        if ($file['size'] > $max_size) {
            log_message('error', 'Profile photo too large: ' . $file['size'] . ' bytes for student ID: ' . $student_id);
            set_alert('warning', 'Profile photo must be less than 2MB');
            return false;
        }
        
        // Extension validation (first line of defense)
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_extensions)) {
            log_message('error', 'Invalid file extension: ' . $file_extension . ' for student ID: ' . $student_id);
            set_alert('warning', 'Profile photo must be JPG, PNG, or GIF format');
            return false;
        }
        
        // MIME type detection WITHOUT fileinfo dependency
        $mime_type = $this->detect_mime_type($file['tmp_name'], $file['type']);
        
        if (!in_array($mime_type, $allowed_types)) {
            log_message('error', 'Invalid MIME type: ' . $mime_type . ' for student ID: ' . $student_id);
            set_alert('warning', 'Profile photo must be a valid image file (JPG, PNG, or GIF)');
            return false;
        }
        
        // Read file content
        $image_data = file_get_contents($file['tmp_name']);
        
        if ($image_data === false) {
            log_message('error', 'Could not read uploaded file for student ID: ' . $student_id);
            set_alert('warning', 'Could not process uploaded image');
            return false;
        }
        
        // Additional validation using getimagesize (doesn't need fileinfo!)
        if (function_exists('getimagesizefromstring')) {
            $image_info = getimagesizefromstring($image_data);
            if ($image_info === false) {
                log_message('error', 'Invalid image data for student ID: ' . $student_id);
                set_alert('warning', 'Uploaded file is not a valid image');
                return false;
            }
            
            // Validate image dimensions (optional - prevent extremely large images)
            if ($image_info[0] > 3000 || $image_info[1] > 3000) {
                log_message('warning', 'Image dimensions too large: ' . $image_info[0] . 'x' . $image_info[1] . ' for student ID: ' . $student_id);
                set_alert('warning', 'Image dimensions too large. Please use an image smaller than 3000x3000 pixels');
                return false;
            }
        }
        
        // Save to database
        try {
            $this->db->where('id', (int)$student_id);
            $result = $this->db->update(db_prefix() . 'school_students', ['profile_photo' => $image_data]);
            
            if ($result) {
                log_message('debug', 'Profile photo updated successfully for student ID: ' . $student_id . ', size: ' . strlen($image_data) . ' bytes');
                return true;
            } else {
                log_message('error', 'Database update failed for profile photo, student ID: ' . $student_id);
                set_alert('warning', 'Failed to save profile photo to database');
                return false;
            }
        } catch (Exception $e) {
            log_message('error', 'Exception saving profile photo for student ID: ' . $student_id . ': ' . $e->getMessage());
            set_alert('warning', 'Error saving profile photo: ' . $e->getMessage());
            return false;
        }
    }


    /**
     * Detect MIME type with multiple fallback methods
     * Handles cases where finfo_open() is not available
     */
    private function detect_mime_type_fallback($file_path, $uploaded_type = null)
    {
        // Method 1: Try getimagesize (best for images, no fileinfo needed)
        if (function_exists('getimagesize')) {
            try {
                $image_info = @getimagesize($file_path);
                if ($image_info !== false && isset($image_info['mime'])) {
                    log_message('debug', 'MIME detected via getimagesize: ' . $image_info['mime']);
                    return strtolower($image_info['mime']);
                }
            } catch (Exception $e) {
                log_message('warning', 'getimagesize failed: ' . $e->getMessage());
            }
        }
        
        // Method 2: Try finfo if available
        if (function_exists('finfo_open')) {
            try {
                $finfo = @finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo !== false) {
                    $detected = @finfo_file($finfo, $file_path);
                    @finfo_close($finfo);
                    if ($detected !== false) {
                        log_message('debug', 'MIME detected via finfo: ' . $detected);
                        return strtolower($detected);
                    }
                }
            } catch (Exception $e) {
                log_message('warning', 'finfo_open failed: ' . $e->getMessage());
            }
        }
        
        // Method 3: Check file signature (magic bytes)
        if (is_readable($file_path)) {
            $handle = @fopen($file_path, 'rb');
            if ($handle) {
                $bytes = fread($handle, 10);
                fclose($handle);
                
                if (substr($bytes, 0, 3) === "\xFF\xD8\xFF") {
                    log_message('debug', 'MIME detected via signature: image/jpeg');
                    return 'image/jpeg';
                }
                if (substr($bytes, 0, 4) === "\x89PNG") {
                    log_message('debug', 'MIME detected via signature: image/png');
                    return 'image/png';
                }
                if (substr($bytes, 0, 3) === "GIF") {
                    log_message('debug', 'MIME detected via signature: image/gif');
                    return 'image/gif';
                }
                if (substr($bytes, 8, 4) === "WEBP") {
                    log_message('debug', 'MIME detected via signature: image/webp');
                    return 'image/webp';
                }
            }
        }
        
        // Method 4: Use uploaded MIME type if valid
        if (!empty($uploaded_type)) {
            $valid = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array(strtolower($uploaded_type), $valid)) {
                log_message('debug', 'Using uploaded MIME type: ' . $uploaded_type);
                return strtolower($uploaded_type);
            }
        }
        
        // Method 5: Guess from extension
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $map = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];
        
        if (isset($map[$ext])) {
            log_message('debug', 'MIME guessed from extension: ' . $map[$ext]);
            return $map[$ext];
        }
        
        log_message('warning', 'Could not determine MIME type, using fallback');
        return 'application/octet-stream';
    }
    /**
     * Detect MIME type with comprehensive fallback (NO fileinfo dependency)
     */
    private function detect_mime_type($file_path, $uploaded_type = null)
    {
        $mime_type = 'application/octet-stream';
        
        // Method 1: Try getimagesize FIRST (works without fileinfo extension)
        if (function_exists('getimagesize')) {
            $image_info = @getimagesize($file_path);
            if ($image_info !== false && isset($image_info['mime'])) {
                log_message('debug', 'MIME detected via getimagesize: ' . $image_info['mime']);
                return strtolower($image_info['mime']);
            }
        }
        
        // Method 2: Try finfo ONLY if extension is available
        if (extension_loaded('fileinfo') && function_exists('finfo_open')) {
            try {
                $finfo = @finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo !== false) {
                    $detected_mime = @finfo_file($finfo, $file_path);
                    @finfo_close($finfo);
                    if ($detected_mime !== false && $detected_mime !== '') {
                        log_message('debug', 'MIME detected via finfo: ' . $detected_mime);
                        return strtolower($detected_mime);
                    }
                }
            } catch (Exception $e) {
                log_message('warning', 'finfo failed: ' . $e->getMessage());
            }
        }
        
        // Method 3: Try mime_content_type (if available)
        if (function_exists('mime_content_type')) {
            try {
                $detected_mime = @mime_content_type($file_path);
                if ($detected_mime !== false && $detected_mime !== '') {
                    log_message('debug', 'MIME detected via mime_content_type: ' . $detected_mime);
                    return strtolower($detected_mime);
                }
            } catch (Exception $e) {
                log_message('warning', 'mime_content_type failed: ' . $e->getMessage());
            }
        }
        
        // Method 4: Check file signature (magic bytes) - RELIABLE WITHOUT ANY EXTENSION
        if (is_readable($file_path)) {
            $handle = @fopen($file_path, 'rb');
            if ($handle) {
                $bytes = fread($handle, 12);
                fclose($handle);
                
                // Check for common image types
                if (substr($bytes, 0, 3) === "\xFF\xD8\xFF") {
                    log_message('debug', 'MIME detected by signature: image/jpeg');
                    return 'image/jpeg';
                }
                if (substr($bytes, 0, 8) === "\x89PNG\r\n\x1a\n") {
                    log_message('debug', 'MIME detected by signature: image/png');
                    return 'image/png';
                }
                if (substr($bytes, 0, 3) === "GIF") {
                    log_message('debug', 'MIME detected by signature: image/gif');
                    return 'image/gif';
                }
                if (substr($bytes, 8, 4) === "WEBP") {
                    log_message('debug', 'MIME detected by signature: image/webp');
                    return 'image/webp';
                }
            }
        }
        
        // Method 5: Use uploaded MIME type as last resort (with validation)
        if (!empty($uploaded_type)) {
            $uploaded_type = strtolower(trim($uploaded_type));
            $valid_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($uploaded_type, $valid_types)) {
                log_message('debug', 'Using uploaded MIME type: ' . $uploaded_type);
                return $uploaded_type;
            }
        }
        
        // Method 6: Guess from file extension as absolute fallback
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $extension_map = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'pdf'  => 'application/pdf',
        ];
        
        if (isset($extension_map[$extension])) {
            log_message('debug', 'MIME guessed from extension: ' . $extension_map[$extension]);
            return $extension_map[$extension];
        }
        
        log_message('warning', 'Could not determine MIME type for: ' . $file_path);
        return 'application/octet-stream';
    }

    /**
     * REMOVE the detect_mime_type_fallback method - we don't need it anymore
     */

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
            $current_student = $this->is_school_student_user();
            if (!has_permission('student_sponsor_portal', '', 'view')) access_denied('student_sponsor_portal');

            if ($this->input->post()) {
                $student_id  = $this->input->post('student_id');
                if ($current_student && (int)$current_student->id !== (int)$student_id) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            // ADD THIS ACCESS CHECK:
            if ($current_student && (int)$current_student->id !== (int)$student_id) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
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


    // bulk import     
    // Replace your existing bulk_import_school_students method with this complete solution

    public function bulk_import_school_students()
    {
        if (!has_permission('student_sponsor_portal', '', 'create')) {
            access_denied('student_sponsor_portal');
        }

        // Handle POST submission
        if ($this->input->method() === 'post') {
            log_message('debug', 'Import: POST request received for bulk import');
            
            if (empty($_FILES['import_file']['name'])) {
                log_message('error', 'Import: No file uploaded');
                set_alert('danger', 'Please select a file to import');
                redirect(admin_url('student_sponsor_portal/bulk_import_school_students'));
                return;
            }

            $file_data = $_FILES['import_file'];
            log_message('debug', 'Import: File uploaded - ' . $file_data['name'] . ' (' . $file_data['size'] . ' bytes)');

            // Validate file type - Accept both Excel and CSV
            $file_ext = strtolower(pathinfo($file_data['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['xlsx', 'xls', 'csv'];
            
            if (!in_array($file_ext, $allowed_extensions)) {
                log_message('error', 'Import: Invalid file type - ' . $file_ext);
                set_alert('danger', 'Only Excel (.xlsx, .xls) and CSV files are supported.');
                redirect(admin_url('student_sponsor_portal/bulk_import_school_students'));
                return;
            }

            // Check file size (max 20MB for Excel files)
            if ($file_data['size'] > 20 * 1024 * 1024) {
                log_message('error', 'Import: File too large - ' . $file_data['size'] . ' bytes');
                set_alert('danger', 'File size exceeds 20MB limit');
                redirect(admin_url('student_sponsor_portal/bulk_import_school_students'));
                return;
            }

            // Process the uploaded file
            $temp_file = $file_data['tmp_name'];
            
            try {
                log_message('debug', 'Import: Starting to process file');
                $result = $this->process_import_file($temp_file, $file_ext);
                
                if ($result['success']) {
                    $message = "Import completed! ";
                    $message .= "Added: {$result['added']}, ";
                    $message .= "Updated: {$result['updated']}, ";
                    $message .= "Errors: {$result['errors']}";
                    
                    log_message('info', 'Import: ' . $message);
                    
                    if (!empty($result['error_details'])) {
                        $this->session->set_flashdata('import_errors', $result['error_details']);
                    }
                    
                    set_alert('success', $message);
                } else {
                    log_message('error', 'Import failed: ' . $result['message']);
                    set_alert('danger', 'Import failed: ' . $result['message']);
                }
                
            } catch (Exception $e) {
                log_message('error', 'Import Exception: ' . $e->getMessage());
                set_alert('danger', 'Import failed: ' . $e->getMessage());
            }
            
            redirect(admin_url('student_sponsor_portal/bulk_import_school_students'));
            return;
        }
        
        // Show the form
        $data['title'] = 'Bulk Import School Students';
        $data['import_errors'] = $this->session->flashdata('import_errors');
        $this->load->view('student_sponsor_portal/bulk_import_school_students', $data);
    }

    /**
     * Create column mapping from headers
     */
    private function create_column_mapping($headers)
    {
        $mapping = [];
        
        // Direct field mappings - header name => database column
        $field_mappings = [
            // Basic Information
            'name' => 'name',
            'email' => 'email',
            'phone' => 'contact_no',
            'contact_no' => 'contact_no',
            'phone_number' => 'contact_no',
            
            // Personal Details
            'date_of_birth' => 'school_student_dob',
            'dob' => 'school_student_dob',
            'birth_date' => 'school_student_dob',
            'age' => 'school_age',
            
            // Address Information
            'address' => 'address',
            'city' => 'city',
            'postal_code' => 'zip',
            'zip_code' => 'zip',
            'zip' => 'zip',
            'country' => 'country_name', // Will be converted to country_id
            
            // School Information
            'school_id' => 'school_id',
            'student_id' => 'school_id',
            'internal_id' => 'school_internal_id',
            'school_internal_id' => 'school_internal_id',
            'grade' => 'school_grade',
            'school_grade' => 'school_grade',
            'class' => 'school_grade',
            'school_type' => 'school_type',
            'school' => 'school_name', // Will be converted to school_name_id
            'school_name' => 'school_name',
            'grade_mismatch_reason' => 'grade_mismatch_reason',
            
            // Bank Information
            'bank' => 'bank_name', // Will be converted to bank_id
            'bank_name' => 'bank_name',
            'account_number' => 'school_bank_account_no',
            'bank_account_number' => 'school_bank_account_no',
            'bank_account_no' => 'school_bank_account_no',
            'branch_number' => 'school_bank_branch_number',
            'branch_code' => 'school_bank_branch_number',
            'branch_info' => 'school_bank_branch_info',
            'bank_branch_info' => 'school_bank_branch_info',
            
            // Family Information
            'father_name' => 'school_father_name',
            'father' => 'school_father_name',
            'father_income' => 'school_father_income',
            'mother_name' => 'school_mother_name',
            'mother' => 'school_mother_name',
            'mother_income' => 'school_mother_income',
            'guardian_name' => 'school_guardian_name',
            'guardian' => 'school_guardian_name',
            'guardian_income' => 'school_guardian_income',
            
            // Sponsorship Information
            'sponsorship_start' => 'school_sponsorship_start_date',
            'sponsorship_start_date' => 'school_sponsorship_start_date',
            'sponsorship_end' => 'school_sponsorship_end_date',
            'sponsorship_end_date' => 'school_sponsorship_end_date',
            'introduced_by' => 'school_introducedby',
            'introducer' => 'school_introducedby',
            'introduced_phone' => 'school_introducedph',
            'introducer_phone' => 'school_introducedph',
            
            // Comments
            'background_information' => 'background_info',
            'background' => 'background_info',
            'background_info' => 'background_info',
            'internal_comment' => 'internal_comment',
            'admin_notes' => 'internal_comment',
            'external_comment' => 'external_comment',
            'public_notes' => 'external_comment',
        ];
        
        // Map headers to database columns
    foreach ($headers as $index => $header) {
        // CRITICAL FIX #1: Remove UTF-8 BOM (invisible character Excel adds)
        $header = str_replace("\xEF\xBB\xBF", '', $header);
        
        // CRITICAL FIX #2: Normalize to underscore-separated lowercase (matching field_mappings keys)
        $clean_header = strtolower(trim($header));
        $clean_header = preg_replace('/[\s\-]+/', '_', $clean_header); // Convert spaces/hyphens to underscores
        $clean_header = preg_replace('/[^\w]/', '', $clean_header);     // Remove non-word chars (except underscore)

        if (isset($field_mappings[$clean_header])) {
            $db_column = $field_mappings[$clean_header];
            $mapping[$db_column] = $index;
            log_message('debug', "School Import: ✓ Mapped '{$header}' (cleaned: '{$clean_header}') to '{$db_column}' at INDEX {$index}");
        } else {
            // Log unmapped headers for debugging
            log_message('debug', "School Import: ✗ UNMAPPED header '{$header}' (cleaned: '{$clean_header}') at INDEX {$index}");
        }

        }
        
        return $mapping;
    }

    /**
     * Map row data using column mapping
     */
    private function map_row_data($row, $mapping)
    {
        $data = [];
        
        foreach ($mapping as $db_column => $column_index) {
            $value = isset($row[$column_index]) ? trim($row[$column_index]) : '';
            
            if ($value === '') {
                continue; // Skip empty values
            }
            
            // Handle special data types and transformations
            switch ($db_column) {
                case 'school_student_dob':
                    $data[$db_column] = $this->parse_date($value);
                    break;
                    
                case 'school_age':
                    $data[$db_column] = is_numeric($value) ? (int)$value : null;
                    break;
                    
                case 'school_father_income':
                case 'school_mother_income':
                case 'school_guardian_income':
                    $data[$db_column] = is_numeric($value) ? (float)$value : null;
                    break;
                    
                case 'school_sponsorship_start_date':
                case 'school_sponsorship_end_date':
                    $data[$db_column] = $this->parse_date($value);
                    break;
                    
                // Handle foreign key fields - convert directly here instead of separate method
                case 'country_name':
                    $country_id = $this->get_or_create_country_id($value);
                    if ($country_id) {
                        $data['country_id'] = $country_id;
                    }
                    // Don't store country_name in final data
                    break;
                    
                case 'school_name':
                    $school_id = $this->school_model->get_or_create_school_name_id($value);
                    if ($school_id) {
                        $data['school_name_id'] = $school_id;
                    }
                    // Don't store school_name in final data
                    break;
                    
                case 'bank_name':
                    $bank_id = $this->get_or_create_bank_id($value);
                    if ($bank_id) {
                        $data['bank_id'] = $bank_id;
                    }
                    // Don't store bank_name in final data
                    break;
                    
                default:
                    // All other fields - store directly
                    $data[$db_column] = $value;
                    break;
            }
        }
        
        // Calculate age if DOB provided and age not set
        if (!empty($data['school_student_dob']) && empty($data['school_age'])) {
            $data['school_age'] = $this->calculate_age_from_dob($data['school_student_dob']);
        }
        
        return $data;
    }

    /**
     * Parse date from various formats - add this if you don't have it
     */
    private function parse_date($value)
    {
        if (empty($value)) {
            return null;
        }
        
        // Handle Excel date serial numbers
        if (is_numeric($value) && class_exists('\PhpOffice\PhpSpreadsheet\Shared\Date')) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                return $date->format('Y-m-d');
            } catch (Exception $e) {
                log_message('warning', 'Excel date conversion failed: ' . $e->getMessage());
            }
        }
        
        // Handle regular date strings
        try {
            $date = new DateTime($value);
            return $date->format('Y-m-d');
        } catch (Exception $e) {
            log_message('warning', "Date parsing failed for value: {$value}");
            return null;
        }
    }

    /**
     * Calculate age from date of birth - add this if you don't have it
     */
    private function calculate_age_from_dob($dob)
    {
        if (!$dob) {
            return null;
        }
        
        try {
            $birth_date = new DateTime($dob);
            $today = new DateTime();
            return $birth_date->diff($today)->y;
        } catch (Exception $e) {
            log_message('warning', 'Age calculation failed: ' . $e->getMessage());
            return null;
        }
    }


    /**
     * Download Excel template
     */
    public function download_school_students_template()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }
        
        // Check if PHPSpreadsheet is available
        $autoload_paths = [
            FCPATH . 'vendor/autoload.php',
            APPPATH . 'third_party/vendor/autoload.php',
            APPPATH . 'libraries/vendor/autoload.php'
        ];
        
        $phpspreadsheet_loaded = false;
        foreach ($autoload_paths as $path) {
            if (file_exists($path)) {
                require_once($path);
                $phpspreadsheet_loaded = true;
                break;
            }
        }
        
        if (!$phpspreadsheet_loaded || !class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // Fallback to CSV template
            $this->download_csv_template();
            return;
        }
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $headers = [
            'Name', 'Email', 'Phone', 'Date of Birth', 'Address', 'City', 'Postal Code', 'Country',
            'School ID', 'Internal ID', 'Grade', 'School Type', 'School Name',
            'Bank Name', 'Account Number', 'Branch Number', 'Branch Info',
            'Father Name', 'Father Income', 'Mother Name', 'Mother Income', 
            'Guardian Name', 'Guardian Income', 'Background Information',
            'Sponsorship Start', 'Sponsorship End', 'Introduced By', 'Introducer Phone',
            'Internal Comment', 'External Comment', 'Grade Mismatch Reason'
        ];
        
        $sheet->fromArray($headers, null, 'A1');
        
        // Add sample data
        $sampleData = [
            'John Doe', 'john.doe@example.com', '+94771234567', '2010-01-15', '123 Main Street',
            'Colombo', '00100', 'Sri Lanka', 'STU001', 'SCH001', '8', 'Type 1AB', 'Royal College',
            'Bank of Ceylon', '1234567890', '001', 'Colombo Branch',
            'Father Name', 50000, 'Mother Name', 30000, 'Guardian Name', 40000,
            'Student background information', '2024-01-01', '2025-12-31', 'Teacher Name', '+94771234568',
            'Internal notes', 'External notes', ''
        ];
        
        $sheet->fromArray($sampleData, null, 'A2');
        
        // Style the header
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => 'E8E8E8']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ];
        
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);
        
        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Add instructions sheet
        $instructionsSheet = $spreadsheet->createSheet();
        $instructionsSheet->setTitle('Instructions');
        
        $instructions = [
            ['School Students Import Template - Instructions'],
            [''],
            ['REQUIRED FIELDS:'],
            ['- Name: Student\'s full name (required)'],
            [''],
            ['OPTIONAL FIELDS:'],
            ['- Email: Student\'s email address'],
            ['- Phone: Contact number'],
            ['- Date of Birth: Format YYYY-MM-DD (e.g., 2010-01-15)'],
            ['- Grade: 1-10, O/L, A/L1, A/L2, A/L Final'],
            ['- All other fields are optional'],
            [''],
            ['NOTES:'],
            ['- Students with existing email/internal ID will be updated'],
            ['- Schools, banks, and countries will be created if they don\'t exist'],
            ['- Age validation applies for grades 1-10'],
            ['- Remove the sample data before importing'],
            ['- Maximum file size: 20MB']
        ];
        
        $instructionsSheet->fromArray($instructions, null, 'A1');
        $instructionsSheet->getColumnDimension('A')->setWidth(50);
        
        // Set active sheet back to template
        $spreadsheet->setActiveSheetIndex(0);
        
        $filename = 'school_students_template_' . date('Y-m-d') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Fallback CSV template download
     */
    private function download_csv_template()
    {
        $filename = 'school_students_template_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        $headers = [
            'Name', 'Email', 'Phone', 'Date of Birth', 'Address', 'City', 'Postal Code', 'Country',
            'School ID', 'Internal ID', 'Grade', 'School Type', 'School Name',
            'Bank Name', 'Account Number', 'Branch Number', 'Branch Info',
            'Father Name', 'Father Income', 'Mother Name', 'Mother Income', 
            'Guardian Name', 'Guardian Income', 'Background Information',
            'Sponsorship Start', 'Sponsorship End', 'Introduced By', 'Introducer Phone',
            'Internal Comment', 'External Comment', 'Grade Mismatch Reason'
        ];
        
        fputcsv($output, $headers);
        
        // Sample data
        $sample = [
            'John Doe', 'john.doe@example.com', '+94771234567', '2010-01-15', '123 Main Street',
            'Colombo', '00100', 'Sri Lanka', 'STU001', 'SCH001', '8', 'Type 1AB', 'Royal College',
            'Bank of Ceylon', '1234567890', '001', 'Colombo Branch',
            'Father Name', '50000', 'Mother Name', '30000', 'Guardian Name', '40000',
            'Student background information', '2024-01-01', '2025-12-31', 'Teacher Name', '+94771234568',
            'Internal notes', 'External notes', ''
        ];
        
        fputcsv($output, $sample);
        
        fclose($output);
        exit;
    }
    /**
     * Simplified column mapping that works with your database
     */
    private function create_simple_column_mapping($headers)
    {
        $mapping = [];
        
        // Simple, reliable field mappings
        $fields = [
            'name' => ['name', 'student_name', 'full_name'],
            'email' => ['email', 'email_address'],
            'phone' => ['phone', 'contact_no', 'phone_number', 'mobile'],
            'dob' => ['dob', 'date_of_birth', 'birth_date'],
            'address' => ['address', 'home_address'],
            'city' => ['city', 'district'],
            'postal_code' => ['postal_code', 'zip', 'zip_code'],
            'country_name' => ['country', 'country_name'],
            'school_id' => ['school_id', 'student_id'],
            'school_internal_id' => ['school_internal_id', 'internal_id'],
            'grade' => ['grade', 'class', 'school_grade'],
            'school_type' => ['school_type', 'type'],
            'school_name' => ['school_name', 'school'],
            'bank_name' => ['bank_name', 'bank'],
            'bank_account_number' => ['bank_account_number', 'account_number'],
            'father_name' => ['father_name', 'father'],
            'mother_name' => ['mother_name', 'mother'],
            'background_information' => ['background_information', 'background']
        ];
        
        foreach ($headers as $index => $header) {
            $clean_header = trim(strtolower($header));
            
            foreach ($fields as $field => $variations) {
                if (in_array($clean_header, $variations)) {
                    $mapping[$field] = $index;
                    break;
                }
            }
        }
        
        return $mapping;
    }

    /**
     * Simple row mapping
     */
    private function map_row_simple($row, $mapping)
    {
        $data = [];
        
        foreach ($mapping as $field => $index) {
            $value = isset($row[$index]) ? trim($row[$index]) : '';
            
            if ($value !== '') {
                switch ($field) {
                    case 'dob':
                        try {
                            $date = new DateTime($value);
                            $data[$field] = $date->format('Y-m-d');
                        } catch (Exception $e) {
                            $data[$field] = '';
                        }
                        break;
                        
                    default:
                        $data[$field] = $value;
                }
            }
        }
        
        // Handle foreign keys
        if (!empty($data['country_name'])) {
            $country_id = $this->get_or_create_country_id($data['country_name']);
            if ($country_id) {
                $data['country_id'] = $country_id;
            }
            unset($data['country_name']);
        }
        
        if (!empty($data['school_name'])) {
            $school_id = $this->school_model->get_or_create_school_name_id($data['school_name']);
            if ($school_id) {
                $data['school_name_id'] = $school_id;
            }
            unset($data['school_name']);
        }
        
        if (!empty($data['bank_name'])) {
            $bank_id = $this->get_or_create_bank_id($data['bank_name']);
            if ($bank_id) {
                $data['bank_id'] = $bank_id;
            }
            unset($data['bank_name']);
        }
        
        return $data;
    }
    private function process_csv_import_fixed($file_path)
    {
        log_message('debug', 'CSV Import: Starting processing of file: ' . $file_path);
        
        if (!file_exists($file_path)) {
            return ['success' => false, 'message' => 'Import file not found'];
        }
        
        $added = 0;
        $updated = 0;
        $errors = 0;
        $error_details = [];
        
        try {
            // Read CSV file with proper encoding handling
            $handle = fopen($file_path, 'r');
            if (!$handle) {
                return ['success' => false, 'message' => 'Cannot open CSV file'];
            }
            
            // Get headers from first row
            $headers = fgetcsv($handle);
            if (!$headers) {
                fclose($handle);
                return ['success' => false, 'message' => 'Invalid CSV file - no headers found'];
            }
            
            // Clean and normalize headers
            $headers = array_map(function($h) {
                return trim(strtolower(str_replace([' ', '_', '-'], '_', $h)));
            }, $headers);
            
            log_message('debug', 'CSV Import: Cleaned Headers: ' . json_encode($headers));
            
            // Map headers to database fields
            $column_mapping = $this->create_column_mapping($headers);
            if (empty($column_mapping)) {
                fclose($handle);
                return ['success' => false, 'message' => 'No valid columns found in CSV. Please check the column names match the template.'];
            }
            
            log_message('debug', 'CSV Import: Column mapping: ' . json_encode($column_mapping));
            
            $row_number = 1; // Start from 1 (header is row 0)
            
            // Process each data row
            while (($row = fgetcsv($handle)) !== false) {
                $row_number++;
                
                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        log_message('debug', "CSV Import: Row {$row_number}: Skipping empty row");
                        continue;
                    }
                    
                    log_message('debug', "CSV Import: Row {$row_number}: Processing: " . json_encode(array_slice($row, 0, 3)));
                    
                    // Map row data to our format
                    $student_data = $this->map_csv_row_to_student_data($row, $column_mapping, $headers);
                    
                    // Validate required fields
                    $validation = $this->validate_csv_row_data($student_data, $row_number);
                    if (!$validation['valid']) {
                        $errors++;
                        $error_details[] = "Row {$row_number}: " . $validation['message'];
                        log_message('warning', "CSV Import: Row {$row_number}: Validation failed: " . $validation['message']);
                        continue;
                    }
                    
                    // Check if student exists
                    $existing_student = $this->find_existing_student_for_import($student_data);
                    
                    if ($existing_student) {
                        // Update existing student
                        log_message('debug', "CSV Import: Row {$row_number}: Updating existing student ID: " . $existing_student['id']);
                        
                        $cleaned_data = $this->clean_school_post_data($student_data, false);
                        $result = $this->school_model->update($cleaned_data, $existing_student['id']);
                        
                        if ($result) {
                            $updated++;
                            log_message('debug', "CSV Import: Row {$row_number}: Successfully updated student");
                        } else {
                            $errors++;
                            $error_details[] = "Row {$row_number}: Failed to update existing student";
                            log_message('error', "CSV Import: Row {$row_number}: Failed to update student");
                        }
                    } else {
                        // Add new student
                        log_message('debug', "CSV Import: Row {$row_number}: Creating new student");
                        
                        $cleaned_data = $this->clean_school_post_data($student_data, false);
                        log_message('debug', "CSV Import: Row {$row_number}: Cleaned data: " . json_encode($cleaned_data));
                        
                        $result = $this->school_model->add($cleaned_data);
                        
                        if ($result && !is_array($result)) {
                            $added++;
                            log_message('debug', "CSV Import: Row {$row_number}: Successfully added student with ID: " . $result);
                        } else {
                            $errors++;
                            $message = is_array($result) ? ($result['message'] ?? 'Unknown error') : 'Failed to add student';
                            $error_details[] = "Row {$row_number}: {$message}";
                            log_message('error', "CSV Import: Row {$row_number}: Failed to add student: " . $message);
                        }
                    }
                    
                } catch (Exception $e) {
                    $errors++;
                    $error_details[] = "Row {$row_number}: " . $e->getMessage();
                    log_message('error', "CSV Import: Row {$row_number} exception: " . $e->getMessage());
                }
            }
            
            fclose($handle);
            
            log_message('info', "CSV Import completed. Added: {$added}, Updated: {$updated}, Errors: {$errors}");
            
            return [
                'success' => true,
                'added' => $added,
                'updated' => $updated,
                'errors' => $errors,
                'error_details' => $error_details
            ];
            
        } catch (Exception $e) {
            if (isset($handle)) fclose($handle);
            log_message('error', 'CSV Import processing error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to process CSV: ' . $e->getMessage()];
        }
    }

    private function map_csv_row_to_student_data($row, $mapping, $headers)
    {
        $data = [];
        
        foreach ($mapping as $field => $column_index) {
            $value = isset($row[$column_index]) ? trim($row[$column_index]) : '';
            
            // Handle special field transformations
            switch ($field) {
                case 'dob':
                    if ($value && $value !== '') {
                        try {
                            $date = new DateTime($value);
                            $data[$field] = $date->format('Y-m-d');
                        } catch (Exception $e) {
                            log_message('warning', "CSV Import: Invalid date format for field {$field}: {$value}");
                            $data[$field] = '';
                        }
                    }
                    break;
                    
                case 'father_income':
                case 'mother_income':
                case 'guardian_income':
                case 'age':
                    $data[$field] = is_numeric($value) ? (float)$value : null;
                    break;
                case 'grade':
                    // Normalize grade if provided
                    if ($value && $value !== '') {
                        $data[$field] = $this->normalize_grade($value);
                    } else {
                        $data[$field] = null;
                    }
                    break;
                case 'sponsorship_start':
                case 'sponsorship_end':
                    if ($value && $value !== '') {
                        try {
                            $date = new DateTime($value);
                            $data[$field] = $date->format('Y-m-d');
                        } catch (Exception $e) {
                            log_message('warning', "CSV Import: Invalid date format for field {$field}: {$value}");
                            $data[$field] = '';
                        }
                    }
                    break;
                    
                default:
                    $data[$field] = $value !== '' ? $value : null;
                    break;
            }
        }
        
        // Handle foreign key lookups
        if (!empty($data['country'])) {
            $country_id = $this->get_or_create_country_id($data['country']);
            if ($country_id) {
                $data['country_id'] = $country_id;
            }
            unset($data['country']);
        }
        
        if (!empty($data['school_name'])) {
            $school_id = $this->school_model->get_or_create_school_name_id($data['school_name']);
            if ($school_id) {
                $data['school_name_id'] = $school_id;
            }
            unset($data['school_name']);
        }
        
        if (!empty($data['bank_name'])) {
            $bank_id = $this->get_or_create_bank_id($data['bank_name']);
            if ($bank_id) {
                $data['bank_id'] = $bank_id;
            }
            unset($data['bank_name']);
        }
        
        // Calculate age if DOB provided and age not set
        if (!empty($data['dob']) && empty($data['age'])) {
            try {
                $dob = new DateTime($data['dob']);
                $now = new DateTime();
                $data['calculated_age'] = $dob->diff($now)->y;
            } catch (Exception $e) {
                log_message('warning', 'CSV Import: Error calculating age from DOB: ' . $e->getMessage());
            }
        }
        
        return $data;
    }


    /**
     * Validate CSV row data
     */
    private function validate_csv_row_data($data, $row_number) {
        if (empty($data['name'])) {
            return ['valid' => false, 'message' => 'Name is required'];
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Invalid email format: ' . $data['email']];
        }

        // Validate grade if provided (accepts NORMALIZED values now)
        if (!empty($data['grade'])) {
            $valid_grades = [
                '1', '2', '3', '4', '5', '6', '7', '8', '9', '10',
                'O/L', 'A/L1', 'A/L2', 'A/L Final'  // These are the NORMALIZED values
            ];
            
            if (!in_array($data['grade'], $valid_grades, true)) {
                return [
                    'valid' => false, 
                    'message' => 'Invalid grade: ' . $data['grade'] . ' (Row: ' . $row_number . ')'
                ];
            }
        }

        return ['valid' => true];
    }

    // private function normalize_grade($grade)
    // {
    //     $map = ['11' => 'O/L', '12' => 'A/L1', '13' => 'A/L2', '14' => 'A/L Final'];
    //     return $map[trim($grade)] ?? trim($grade);
    // }
    private function normalize_grade($grade) {
        // Remove any whitespace and convert to string
        $grade = trim((string)$grade);
        
        // Log the input value
        log_message('debug', "Normalize Grade - Input: '{$grade}' (Type: " . gettype($grade) . ")");
        
        // Mapping array
        $map = [
            '11' => 'O/L',
            '12' => 'A/L1',
            '13' => 'A/L2',
            '14' => 'A/L Final'
        ];
        
        // Return mapped value if exists, otherwise return original grade
        if (isset($map[$grade])) {
            $result = $map[$grade];
            log_message('debug', "Normalize Grade - Output: '{$result}' (Mapped)");
            return $result;
        }
        
        // For grades 1-10, return as-is
        log_message('debug', "Normalize Grade - Output: '{$grade}' (Unchanged)");
        return $grade;
    }
    /**
     * Find existing student by email or internal ID for import
     */
    private function find_existing_student_for_import($data)
    {
        if (!empty($data['email'])) {
            $student = $this->db->where('email', $data['email'])
                            ->get(db_prefix() . 'school_students')
                            ->row_array();
            if ($student) {
                log_message('debug', 'CSV Import: Found existing student by email: ' . $data['email']);
                return $student;
            }
        }
        
        if (!empty($data['school_internal_id'])) {
            $student = $this->db->where('school_internal_id', $data['school_internal_id'])
                            ->get(db_prefix() . 'school_students')
                            ->row_array();
            if ($student) {
                log_message('debug', 'CSV Import: Found existing student by internal ID: ' . $data['school_internal_id']);
                return $student;
            }
        }
        
        return null;
    }

    /**
     * Map CSV column headers to our field names
     */
    private function map_csv_columns($headers)
    {
        $mapping = [];
        
        // Field mappings (case-insensitive)
        $field_mappings = [
            'name' => ['name', 'student_name', 'full_name', 'student name', 'full name', 'name*'],
            'email' => ['email', 'email_address', 'email address'],
            'phone' => ['phone', 'contact_no', 'phone_number', 'contact_number', 'mobile', 'phone number'],
            'dob' => ['dob', 'date_of_birth', 'birth_date', 'date of birth'],
            'address' => ['address', 'home_address', 'home address'],
            'city' => ['city', 'district', 'location'],
            'postal_code' => ['postal_code', 'zip', 'zip_code', 'postal code'],
            'country_name' => ['country', 'country_name', 'country name'],
            'school_id' => ['school_id', 'student_id', 'school student id'],
            'school_internal_id' => ['internal_id', 'school_internal_id', 'internal id'],
            'grade' => ['grade', 'class', 'school_grade', 'student_grade'],
            'school_type' => ['school_type', 'school type'],
            'school_name' => ['school', 'school_name', 'school name'],
            'bank_name' => ['bank', 'bank_name', 'bank name'],
            'bank_account_number' => ['account_number', 'bank_account', 'account number'],
            'bank_branch_number' => ['branch_number', 'branch_code', 'branch number'],
            'bank_branch_info' => ['branch_info', 'branch_information', 'branch info'],
            'father_name' => ['father_name', 'father name', 'father'],
            'father_income' => ['father_income', 'father income'],
            'mother_name' => ['mother_name', 'mother name', 'mother'],
            'mother_income' => ['mother_income', 'mother income'],
            'guardian_name' => ['guardian_name', 'guardian name', 'guardian'],
            'guardian_income' => ['guardian_income', 'guardian income'],
            'background_information' => ['background', 'background_info', 'background_information'],
            'sponsorship_start' => ['sponsorship_start', 'sponsorship_start_date', 'sponsorship start'],
            'sponsorship_end' => ['sponsorship_end', 'sponsorship_end_date', 'sponsorship end'],
            'introduced_by' => ['introduced_by', 'introduced by', 'introducer'],
            'introduced_phone' => ['introduced_phone', 'introducer_phone', 'introduced phone'],
            'internal_comment' => ['internal_comment', 'internal comment', 'admin_notes'],
            'external_comment' => ['external_comment', 'external comment', 'public_notes']
        ];
        
        foreach ($headers as $index => $header) {
            $header = strtolower(trim($header));
            
            foreach ($field_mappings as $field => $possible_names) {
                if (in_array($header, $possible_names)) {
                    $mapping[$field] = $index;
                    log_message('debug', "Mapped column '{$headers[$index]}' to field '{$field}'");
                    break;
                }
            }
        }
        
        return $mapping;
    }

    /**
     * Map CSV row data using column mapping
     */
    private function map_csv_row_data($row, $mapping)
    {
        $data = [];
        
        foreach ($mapping as $field => $column_index) {
            $value = isset($row[$column_index]) ? trim($row[$column_index]) : '';
            
            // Handle special field transformations
            switch ($field) {
                case 'dob':
                    if ($value && $value !== '') {
                        try {
                            // Try to parse various date formats
                            $date = new DateTime($value);
                            $data[$field] = $date->format('Y-m-d');
                        } catch (Exception $e) {
                            $data[$field] = ''; // Invalid date
                            log_message('debug', "Invalid date format for field {$field}: {$value}");
                        }
                    }
                    break;
                    
                case 'father_income':
                case 'mother_income':
                case 'guardian_income':
                    $data[$field] = is_numeric($value) ? (float)$value : null;
                    break;
                    
                case 'sponsorship_start':
                case 'sponsorship_end':
                    if ($value && $value !== '') {
                        try {
                            $date = new DateTime($value);
                            $data[$field] = $date->format('Y-m-d');
                        } catch (Exception $e) {
                            $data[$field] = '';
                            log_message('debug', "Invalid date format for field {$field}: {$value}");
                        }
                    }
                    break;
                    
                default:
                    $data[$field] = $value;
                    break;
            }
        }
        
        // Handle lookups for foreign key fields
        if (!empty($data['country_name'])) {
            $country_id = $this->get_or_create_country_id($data['country_name']);
            if ($country_id) {
                $data['country_id'] = $country_id;
            } else {
                log_message('error', 'Failed to create country for ' . $data['country_name']);
                return null;  // Skip this row if the country can't be created
            }
            unset($data['country_name']);
        }
        
        if (!empty($data['school_name'])) {
        $school_id = $this->school_model->get_or_create_school_name_id($data['school_name']);
        if ($school_id) {
            $data['school_name_id'] = $school_id;
        } else {
            log_message('error', 'Failed to create school for ' . $data['school_name']);
            return null;  // Skip this row if the school can't be created
        }
        unset($data['school_name']);
    }
        
        // Handle bank_name
    if (!empty($data['bank_name'])) {
        $bank_id = $this->get_or_create_bank_id($data['bank_name']);
        if ($bank_id) {
            $data['bank_id'] = $bank_id;
        } else {
            log_message('error', 'Failed to create bank for ' . $data['bank_name']);
            return null;  // Skip this row if the bank can't be created
        }
        unset($data['bank_name']);
    }
        
        return $data;
    }

    /**
     * Find existing student by email or internal ID
     */
    private function find_existing_student_by_email_or_id($data)
    {
        if (!empty($data['email'])) {
            $student = $this->db->where('email', $data['email'])
                            ->get(db_prefix() . 'school_students')
                            ->row_array();
            if ($student) {
                log_message('debug', 'Found existing student by email: ' . $data['email']);
                return $student;
            }
        }
        
        if (!empty($data['school_internal_id'])) {
            $student = $this->db->where('school_internal_id', $data['school_internal_id'])
                            ->get(db_prefix() . 'school_students')
                            ->row_array();
            if ($student) {
                log_message('debug', 'Found existing student by internal ID: ' . $data['school_internal_id']);
                return $student;
            }
        }
        
        return null;
    }


    /**
     * Read CSV file with proper encoding handling
     */
    private function read_csv_file($file_path)
    {
        $rows = [];
        
        if (($handle = fopen($file_path, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rows[] = $data;
            }
            fclose($handle);
        }
        
        return $rows;
    }

    private function process_school_students_import($file_path)
    {
        require_once(APPPATH . 'third_party/PHPOffice/vendor/autoload.php');
        
        $added = 0;
        $updated = 0;
        $errors = 0;
        $error_details = [];
        
        try {
            // Load the spreadsheet
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            if (empty($rows)) {
                return ['success' => false, 'message' => 'No data found in file'];
            }
            
            // Get header row and map columns
            $headers = array_shift($rows);
            $column_mapping = $this->map_import_columns($headers);
            
            if (empty($column_mapping)) {
                return ['success' => false, 'message' => 'No valid columns found. Please check the template.'];
            }
            
            // Process each row
            foreach ($rows as $row_index => $row) {
                $actual_row = $row_index + 2; // +1 for 0-based index, +1 for header row
                
                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }
                    
                    // Map row data to our format
                    $student_data = $this->map_row_data($row, $column_mapping);
                        if ($student_data === null) {
                            $errors++;
                            $error_details[] = "Row {$row_number}: Missing required foreign keys (e.g., country, school, bank)";
                            continue;
                        }

                    
                    // Validate required fields
                    $validation_result = $this->validate_import_row($student_data, $actual_row);
                    if (!$validation_result['valid']) {
                        $errors++;
                        $error_details[] = "Row {$actual_row}: " . $validation_result['message'];
                        continue;
                    }
                    
                    // Check if student exists (by email or internal ID)
                    $existing_student = $this->find_existing_student($student_data);
                    
                    if ($existing_student) {
                        // Update existing student
                        $cleaned_data = $this->clean_school_post_data($student_data, false);
                        $result = $this->school_model->update($cleaned_data, $existing_student['id']);
                        
                        if ($result) {
                            $updated++;
                        } else {
                            $errors++;
                            $error_details[] = "Row {$actual_row}: Failed to update student";
                        }
                    } else {
                        // Add new student
                        $cleaned_data = $this->clean_school_post_data($student_data, false);
                        $result = $this->school_model->add($cleaned_data);
                        
                        if ($result && !is_array($result)) {
                            $added++;
                        } else {
                            $errors++;
                            $message = is_array($result) ? $result['message'] : 'Failed to add student';
                            $error_details[] = "Row {$actual_row}: {$message}";
                        }
                    }
                    
                } catch (Exception $e) {
                    $errors++;
                    $error_details[] = "Row {$actual_row}: " . $e->getMessage();
                    log_message('error', "Import row {$actual_row} error: " . $e->getMessage());
                }
            }
            
            return [
                'success' => true,
                'added' => $added,
                'updated' => $updated,
                'errors' => $errors,
                'error_details' => $error_details
            ];
            
        } catch (Exception $e) {
            log_message('error', 'Excel processing error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to process file: ' . $e->getMessage()];
        }
    }

    /**
     * Map Excel columns to our database fields
     */
    private function map_import_columns($headers)
    {
        $mapping = [];
        
        // Define column mappings (case-insensitive)
        $field_mappings = [
            'name' => ['name', 'student_name', 'full_name', 'student name', 'full name'],
            'email' => ['email', 'email_address', 'email address'],
            'phone' => ['phone', 'contact_no', 'phone_number', 'contact_number', 'mobile', 'phone number', 'contact number'],
            'dob' => ['dob', 'date_of_birth', 'birth_date', 'date of birth', 'birth date'],
            'address' => ['address', 'home_address', 'home address'],
            'city' => ['city', 'district', 'location'],
            'postal_code' => ['postal_code', 'zip', 'zip_code', 'postal code', 'zip code'],
            'country_name' => ['country', 'country_name', 'country name'],
            'school_id' => ['school_id', 'student_id', 'school student id', 'student id'],
            'school_internal_id' => ['internal_id', 'school_internal_id', 'internal id', 'school internal id'],
            'grade' => ['grade', 'class', 'school_grade', 'student_grade', 'school grade'],
            'school_type' => ['school_type', 'school type'],
            'school_name' => ['school', 'school_name', 'school name'],
            'bank_name' => ['bank', 'bank_name', 'bank name'],
            'bank_account_number' => ['account_number', 'bank_account', 'account number', 'bank account'],
            'bank_branch_number' => ['branch_number', 'branch_code', 'branch number', 'branch code'],
            'bank_branch_info' => ['branch_info', 'branch_information', 'branch info', 'branch information'],
            'father_name' => ['father_name', 'father name', 'father'],
            'father_income' => ['father_income', 'father income'],
            'mother_name' => ['mother_name', 'mother name', 'mother'],
            'mother_income' => ['mother_income', 'mother income'],
            'guardian_name' => ['guardian_name', 'guardian name', 'guardian'],
            'guardian_income' => ['guardian_income', 'guardian income'],
            'background_information' => ['background', 'background_info', 'background_information', 'background information'],
            'sponsorship_start' => ['sponsorship_start', 'sponsorship_start_date', 'sponsorship start', 'sponsorship start date'],
            'sponsorship_end' => ['sponsorship_end', 'sponsorship_end_date', 'sponsorship end', 'sponsorship end date'],
            'introduced_by' => ['introduced_by', 'introduced by', 'introducer'],
            'introduced_phone' => ['introduced_phone', 'introducer_phone', 'introduced phone', 'introducer phone'],
            'internal_comment' => ['internal_comment', 'internal comment', 'admin_notes', 'admin notes'],
            'external_comment' => ['external_comment', 'external comment', 'public_notes', 'public notes']
        ];
        
        foreach ($headers as $index => $header) {
            $header = strtolower(trim($header));
            
            foreach ($field_mappings as $field => $possible_names) {
                if (in_array($header, $possible_names)) {
                    $mapping[$field] = $index;
                    break;
                }
            }
        }
        
        return $mapping;
    }

    /**
     * Validate import row data
     */
    private function validate_import_row($data, $row_number)
    {
        // Name is required
        if (empty($data['name'])) {
            return ['valid' => false, 'message' => 'Name is required'];
        }
        
        // Email format validation
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Invalid email format'];
        }
        
        // Grade validation - ACCEPTS NORMALIZED VALUES (after normalize_grade() has run)
        if (!empty($data['grade'])) {
            $valid_grades = [
                '1', '2', '3', '4', '5', '6', '7', '8', '9', '10',
                'O/L', 'A/L1', 'A/L2', 'A/L Final'  // These are AFTER normalization
            ];
            
            if (!in_array($data['grade'], $valid_grades, true)) {
                return [
                    'valid' => false, 
                    'message' => 'Invalid grade: "' . $data['grade'] . '" is not a valid grade (Row: ' . $row_number . ')'
                ];
            }
        }
        
        return ['valid' => true];
    }

    /**
     * Find existing student by email or internal ID
     */


    private function generate_csv_template()
    {
        $filename = 'school_students_import_template_' . date('Y-m-d') . '.csv';
        
        // Clear any previous output
        if (ob_get_length()) {
            ob_end_clean();
        }
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        $headers = [
            'Name', 'Email', 'Phone', 'Date of Birth', 'Address', 'City', 'Postal Code', 'Country',
            'School ID', 'Internal ID', 'Grade', 'School Type', 'School Name',
            'Bank Name', 'Account Number', 'Branch Number', 'Branch Info',
            'Father Name', 'Father Income', 'Mother Name', 'Mother Income', 
            'Guardian Name', 'Guardian Income', 'Background Information',
            'Sponsorship Start', 'Sponsorship End', 'Introduced By', 'Introducer Phone',
            'Internal Comment', 'External Comment'
        ];
        
        fputcsv($output, $headers);
        
        // Sample data
        $sampleData = [
            'John Doe', 'john.doe@email.com', '+94771234567', '2010-01-15', '123 Main St, Colombo',
            'Colombo', '00100', 'Sri Lanka', 'STU001', 'SCH001', '8', 'Type 1AB', 'Royal College',
            'Bank of Ceylon', '1234567890', '001', 'Colombo Branch',
            'Father Name', '50000', 'Mother Name', '30000', 'Guardian Name', '40000',
            'Student background information', '2024-01-01', '2025-12-31', 'Teacher Name', '+94771234568',
            'Internal notes', 'External notes'
        ];
        
        fputcsv($output, $sampleData);
        
        // Add instructions as comments
        fputcsv($output, []);
        fputcsv($output, ['INSTRUCTIONS:']);
        fputcsv($output, ['1. Fill in your student data starting from row 3']);
        fputcsv($output, ['2. Name column is required, others are optional']);
        fputcsv($output, ['3. Dates should be in YYYY-MM-DD format']);
        fputcsv($output, ['4. Grades: 1-10, O/L, A/L1, A/L2, A/L Final']);
        fputcsv($output, ['5. Schools and banks will be created if they don\'t exist']);
        fputcsv($output, ['6. Students with existing email/internal ID will be updated']);
        
        fclose($output);
        exit;
    }
    /**
     * Export all school students to CSV with ALL sponsors (no sponsor type)
     */
    public function export_school_students()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        try {
            // Get ALL students
            $students = $this->school_model->get_all();
            
            if (empty($students)) {
                set_alert('warning', 'No students found to export');
                redirect(admin_url('student_sponsor_portal/school_students'));
                return;
            }

            // Generate filename
            $filename = 'school_students_export_' . date('Y-m-d_H-i-s') . '.csv';
            
            // Set headers for CSV download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');
            
            // Create output stream
            $output = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Define CSV headers (NO sponsor type)
            $csv_headers = [
                'ID',
                'Internal ID',
                'Entity Type',
                'Name',
                'Email',
                'Phone',
                'Date of Birth',
                'Age',
                'Grade',
                'Grade Mismatch Reason',
                'School ID',
                'School Name',
                'School Type',
                'Address',
                'City',
                'Postal Code',
                'Country',
                'Bank Name',
                'Account Number',
                'Branch Number',
                'Branch Info',
                'Father Name',
                'Father Income',
                'Mother Name',
                'Mother Income',
                'Guardian Name',
                'Guardian Income',
                'Sponsors',                   // All sponsor names
                'Number of Sponsors',         
                'Sponsorship Start',
                'Sponsorship End',
                'Introduced By',
                'Introducer Phone',
                'Background Info',
                'Internal Comment',
                'External Comment',
                'Created Date'
            ];
            
            // Write headers
            fputcsv($output, $csv_headers);
            
            // Write student data
            foreach ($students as $student) {
                $all_sponsor_names = $student['all_sponsor_names'] ?? '';
                $sponsor_count = $student['sponsor_count'] ?? 0;
                
                $row = [
                    $student['id'] ?? '',
                    $student['school_internal_id'] ?? '',
                    $student['entity_type'] ?? 'school',
                    $student['name'] ?? '',
                    $student['email'] ?? '',
                    $student['contact_no'] ?? '',
                    $student['school_student_dob'] ?? '',
                    $student['school_age'] ?? '',
                    $student['school_grade'] ?? '',
                    $student['grade_mismatch_reason'] ?? '',
                    $student['school_id'] ?? '',
                    $student['school_name'] ?? '',
                    $student['school_type'] ?? '',
                    $student['address'] ?? '',
                    $student['city'] ?? '',
                    $student['zip'] ?? '',
                    $student['country_name'] ?? '',
                    $student['bank_name'] ?? '',
                    $student['school_bank_account_no'] ?? '',
                    $student['school_bank_branch_number'] ?? '',
                    $student['school_bank_branch_info'] ?? '',
                    $student['school_father_name'] ?? '',
                    $student['school_father_income'] ?? '',
                    $student['school_mother_name'] ?? '',
                    $student['school_mother_income'] ?? '',
                    $student['school_guardian_name'] ?? '',
                    $student['school_guardian_income'] ?? '',
                    $all_sponsor_names,          // ALL sponsors (comma-separated)
                    $sponsor_count,              // Number of sponsors
                    $student['school_sponsorship_start_date'] ?? '',
                    $student['school_sponsorship_end_date'] ?? '',
                    $student['school_introducedby'] ?? '',
                    $student['school_introducedph'] ?? '',
                    $student['background_info'] ?? '',
                    $student['internal_comment'] ?? '',
                    $student['external_comment'] ?? '',
                    $student['created_on'] ?? ($student['created_at'] ?? '')
                ];
                
                fputcsv($output, $row);
            }
            
            fclose($output);
            exit;
            
        } catch (Exception $e) {
            log_message('error', 'CSV Export Error: ' . $e->getMessage());
            set_alert('danger', 'Error exporting students: ' . $e->getMessage());
            redirect(admin_url('student_sponsor_portal/school_students'));
        }
    }


    /**
     * Export all university students to CSV with ALL sponsors (no sponsor type)
     */
    public function export_university_students()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        try {
            // Get ALL university students
            $students = $this->university_model->get_all();
            
            if (empty($students)) {
                set_alert('warning', 'No students found to export');
                redirect(admin_url('student_sponsor_portal/university_students'));
                return;
            }

            // Generate filename
            $filename = 'university_students_export_' . date('Y-m-d_H-i-s') . '.csv';
            
            // Set headers for CSV download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');
            
            // Create output stream
            $output = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Define CSV headers (NO staff_id, NO sponsor type)
            $csv_headers = [
                'ID',
                'Internal ID',
                'Entity Type',
                'Name',
                'Email',
                'Phone',
                'Date of Birth',
                'Age',
                'Year of Study',
                'University ID',
                'University Name',
                'Program',
                'Address',
                'City',
                'Postal Code',
                'Country',
                'Bank Name',
                'Account Number',
                'Branch Number',
                'Branch Info',
                'Father Name',
                'Father Income',
                'Mother Name',
                'Mother Income',
                'Guardian Name',
                'Guardian Income',
                'Sponsors',                   // All sponsor names
                'Number of Sponsors',         
                'Sponsorship Start',
                'Sponsorship End',
                'Introduced By',
                'Introducer Phone',
                'Background Info',
                'Internal Comment',
                'External Comment',
                'Created Date'
            ];
            
            // Write headers
            fputcsv($output, $csv_headers);
            
            // Write student data
            foreach ($students as $student) {
                $all_sponsor_names = $student['all_sponsor_names'] ?? '';
                $sponsor_count = $student['sponsor_count'] ?? 0;
                
                $row = [
                    $student['id'] ?? '',
                    $student['university_internal_id'] ?? '',
                    $student['entity_type'] ?? 'university',
                    $student['name'] ?? '',
                    $student['email'] ?? '',
                    $student['contact_no'] ?? '',
                    $student['university_student_dob'] ?? '',
                    $student['university_age'] ?? '',
                    $student['university_year_of_study'] ?? '',
                    $student['university_id'] ?? '',
                    $student['university_name'] ?? '',
                    $student['program_name'] ?? '',
                    $student['address'] ?? '',
                    $student['city'] ?? '',
                    $student['zip'] ?? '',
                    $student['country_name'] ?? '',
                    $student['bank_name'] ?? '',
                    $student['university_bank_account_no'] ?? '',
                    $student['university_bank_branch_number'] ?? '',
                    $student['university_bank_branch_info'] ?? '',
                    $student['university_father_name'] ?? '',
                    $student['university_father_income'] ?? '',
                    $student['university_mother_name'] ?? '',
                    $student['university_mother_income'] ?? '',
                    $student['university_guardian_name'] ?? '',
                    $student['university_guardian_income'] ?? '',
                    $all_sponsor_names,          // ALL sponsors (comma-separated)
                    $sponsor_count,              // Number of sponsors
                    $student['university_sponsorship_start_date'] ?? '',
                    $student['university_sponsorship_end_date'] ?? '',
                    $student['university_introducedby'] ?? '',
                    $student['university_introducedph'] ?? '',
                    $student['background_info'] ?? '',
                    $student['internal_comment'] ?? '',
                    $student['external_comment'] ?? '',
                    $student['created_on'] ?? ($student['created_at'] ?? '')
                ];
                
                fputcsv($output, $row);
            }
            
            fclose($output);
            exit;
            
        } catch (Exception $e) {
            log_message('error', 'University CSV Export Error: ' . $e->getMessage());
            set_alert('danger', 'Error exporting students: ' . $e->getMessage());
            redirect(admin_url('student_sponsor_portal/university_students'));
        }
    }

    /**
     * Export all sponsors to CSV with sponsored students and financial data
     */
    public function export_sponsors()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        try {
            // Get ALL sponsors with their sponsored students and financial data
            $sponsors = $this->sponsor_model->get_all();
            
            if (empty($sponsors)) {
                set_alert('warning', 'No sponsors found to export');
                redirect(admin_url('student_sponsor_portal/sponsors'));
                return;
            }

            // Generate filename
            $filename = 'sponsors_export_' . date('Y-m-d_H-i-s') . '.csv';
            
            // Set headers for CSV download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');
            
            // Create output stream
            $output = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Define CSV headers
            $csv_headers = [
                'ID',
                'Name',
                'Type',
                'Frequency',
                'Email',
                'Phone',
                'Address',
                'City',
                'State',
                'Postal Code',
                'Country',
                'Occupation',
                'Company Name',
                'Company Address',
                'Company Phone',
                'Bank Name',
                'Account Number',
                'Branch Number',
                'Branch Info',
                'School Students Count',
                'School Students Names',
                'University Students Count',
                'University Students Names',
                'Total Students Sponsored',
                'Total Transactions',
                'Total Committed Amount',
                'Total Paid Amount',
                'Total Outstanding Balance',
                'Payment Completion %',
                'Portal Access',
                'Status',
                'Created Date',
                'Notes'
            ];
            
            // Write headers
            fputcsv($output, $csv_headers);
            
            // Write sponsor data
            foreach ($sponsors as $sponsor) {
                // Get sponsored students names
                $student_names_data = $sponsor['sponsored_student_names'] ?? [
                    'school_students' => [],
                    'university_students' => [],
                    'school_names_display' => '',
                    'university_names_display' => '',
                    'school_total_count' => 0,
                    'university_total_count' => 0
                ];
                
                // Get financial data
                $total_commitment = (float)($sponsor['total_commitment'] ?? 0);
                $total_paid = (float)($sponsor['total_paid'] ?? 0);
                $total_balance = $total_commitment - $total_paid;
                $payment_completion = $total_commitment > 0 
                    ? round(($total_paid / $total_commitment) * 100, 2) 
                    : 0;
                
                // Determine status
                $staff_id = $sponsor['staff_id'] ?? null;
                $active = (int)($sponsor['active'] ?? 0);
                $total_students = (int)($sponsor['school_students_count'] ?? 0) + 
                                (int)($sponsor['university_students_count'] ?? 0);
                
                $status_text = 'Inactive';
                if ($active == 1) {
                    $status_text = $total_students > 0 ? 'Active & Sponsoring' : 'Active (No Students)';
                }
                
                $portal_access = $staff_id !== null 
                    ? ($active == 1 ? 'Yes (Active)' : 'Yes (Inactive)') 
                    : 'No';
                
                $row = [
                    $sponsor['id'] ?? '',
                    $sponsor['name'] ?? '',
                    $sponsor['sponsor_type'] ?? '',
                    $sponsor['sponsor_frequency'] ?? '',
                    $sponsor['email'] ?? '',
                    $sponsor['contact_no'] ?? '',
                    $sponsor['address'] ?? '',
                    $sponsor['city'] ?? '',
                    $sponsor['state_name'] ?? '',
                    $sponsor['zip'] ?? '',
                    $sponsor['country_name'] ?? '',
                    $sponsor['sponsor_occupation'] ?? '',
                    $sponsor['sponsor_company_name'] ?? '',
                    $sponsor['sponsor_company_address'] ?? '',
                    $sponsor['sponsor_company_phone'] ?? '',
                    $sponsor['bank_name'] ?? '',
                    $sponsor['sponsor_bank_account_no'] ?? '',
                    $sponsor['sponsor_bank_branch_number'] ?? '',
                    $sponsor['sponsor_bank_branch_info'] ?? '',
                    $student_names_data['school_total_count'],
                    $student_names_data['school_names_display'],
                    $student_names_data['university_total_count'],
                    $student_names_data['university_names_display'],
                    $total_students,
                    $sponsor['total_transactions'] ?? 0,
                    $total_commitment,
                    $total_paid,
                    $total_balance,
                    $payment_completion . '%',
                    $portal_access,
                    $status_text,
                    $sponsor['created_on'] ?? ($sponsor['created_at'] ?? ''),
                    $sponsor['notes'] ?? ''
                ];
                
                fputcsv($output, $row);
            }
            
            fclose($output);
            exit;
            
        } catch (Exception $e) {
            log_message('error', 'Sponsors CSV Export Error: ' . $e->getMessage());
            set_alert('danger', 'Error exporting sponsors: ' . $e->getMessage());
            redirect(admin_url('student_sponsor_portal/sponsors'));
        }
    }


    /**
     * Get students by specific IDs with all joined data
     */
    private function get_students_by_ids($student_ids)
    {
        if (empty($student_ids)) {
            return [];
        }
        
        $c = $this->country_schema();

        $this->db->select('
            ss.*,
            sn.name AS school_name,
            b.name  AS bank_name' .
            ($c['table'] ? ', c.' . $c['name'] . ' AS country_name' : ', NULL AS country_name')
        , false);

        $this->db->from(db_prefix() . 'school_students ss');
        $this->db->join(db_prefix() . 'school_name sn', 'sn.id = ss.school_name_id', 'left');

        if ($c['table']) {
            $this->db->join($c['table'].' c', 'c.'.$c['id'].' = ss.country_id', 'left');
        }

        $this->db->join(db_prefix() . 'bank b', 'b.id = ss.bank_id', 'left');
        $this->db->where_in('ss.id', $student_ids);
        $this->db->order_by('ss.id', 'ASC');
        
        return $this->db->get()->result_array();
    }

    /**
     * Get country schema (helper method - add if not exists)
     */
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
            $current_student = $this->is_school_student_user();
            if (!has_permission('student_sponsor_portal', '', 'view')) {
                access_denied('student_sponsor_portal');
            }
            if ($current_student && (int)$current_student->id !== (int)$student_id) {
            header('HTTP/1.0 403 Forbidden');
            exit('Access denied');
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
        try {
            // Check permissions first
            if (!has_permission('student_sponsor_portal', '', 'edit')) {
                access_denied('student_sponsor_portal');
            }

            // Debug: Log the incoming request
            log_message('debug', 'grant_school_access called with student_id: ' . ($student_id ?? 'null'));
            log_message('debug', 'POST data: ' . print_r($this->input->post(), true));

            // Handle POST request (form submission)
            if ($this->input->method() === 'post') {
                $student_id = $this->input->post('student_id');
                
                if (!$student_id || !is_numeric($student_id)) {
                    set_alert('danger', 'Invalid student ID provided.');
                    redirect(admin_url('student_sponsor_portal/school_students'));
                    return;
                }
                
                $student_id = (int)$student_id;
                
                // Get student data
                $s = $this->school_model->get_by_id($student_id);
                if (!$s) { 
                    set_alert('danger', 'Student not found.'); 
                    redirect(admin_url('student_sponsor_portal/school_students')); 
                    return;
                }

                // Validate form data
                $staff_email = trim($this->input->post('staff_email') ?: '');
                $staff_firstname = trim($this->input->post('staff_firstname') ?: '');
                $staff_password = trim($this->input->post('staff_password') ?: '');
                $staff_active = $this->input->post('staff_active') ? 1 : 0;

                if (empty($staff_email)) {
                    set_alert('danger', 'Email is required for portal access.');
                    redirect(admin_url('student_sponsor_portal/school_student_form/' . $student_id));
                    return;
                }

                if (empty($staff_firstname)) {
                    $staff_firstname = $s['name'] ?: 'Student';
                }

                // Prepare staff data
                $staff_data = [
                    'staff_email'     => $staff_email,
                    'staff_firstname' => $staff_firstname,
                    'staff_lastname'  => '',
                    'staff_password'  => $staff_password,
                    'active'          => $staff_active,
                ];

                // Get existing staff ID if any
                $existing_staff_id = isset($s['staff_id']) && $s['staff_id'] !== '' && $s['staff_id'] !== null 
                                    ? (int)$s['staff_id'] 
                                    : null;

                // Create or update staff account
                $staff_id = $this->create_simple_staff($staff_data, $existing_staff_id);
                
                if (!$staff_id) {
                    set_alert('danger', 'Failed to create staff account. Please check the email address.');
                    redirect(admin_url('student_sponsor_portal/school_student_form/' . $student_id));
                    return;
                }

                // Grant portal permissions
                $this->grant_portal_permissions($staff_id);

                // Update student record with proper error handling
                try {
                    $tbl = db_prefix() . 'school_students';
                    $update_data = ['staff_id' => (int)$staff_id];
                    
                    // Only update active field if it exists
                    if ($this->db->field_exists('staff_active', $tbl)) {
                        $update_data['staff_active'] = (int)$staff_active;
                    } elseif ($this->db->field_exists('active', $tbl)) {
                        $update_data['active'] = (int)$staff_active;
                    }
                    
                    $this->db->where('id', (int)$student_id)->update($tbl, $update_data);
                    
                    // Check if update was successful
                    if ($this->db->affected_rows() >= 0) {
                        $msg = 'Portal access granted successfully.';
                        if (!empty($staff_password)) {
                            $msg .= ' Password has been set as provided.';
                        }
                        set_alert('success', $msg);
                    } else {
                        set_alert('warning', 'Staff account created but student record update may have failed.');
                    }
                    
                } catch (Exception $e) {
                    log_message('error', 'Error updating student record: ' . $e->getMessage());
                    set_alert('warning', 'Staff account created but there was an issue updating the student record.');
                }

                redirect(admin_url('student_sponsor_portal/school_student_form/' . $student_id));
                return;
            }

            // Handle GET request (show form) - this part might be missing
            if (!$student_id || !is_numeric($student_id)) {
                set_alert('danger', 'Student ID is required.');
                redirect(admin_url('student_sponsor_portal/school_students'));
                return;
            }

            $student_id = (int)$student_id;
            $s = $this->school_model->get_by_id($student_id);
            
            if (!$s) { 
                set_alert('danger', 'Student not found.'); 
                redirect(admin_url('student_sponsor_portal/school_students')); 
                return;
            }

            // Prepare data for the view
            $data = [
                'title' => 'Grant Portal Access',
                'student' => $s,
                'student_id' => $student_id
            ];

            // Load the form view (you may need to create this view)
            $this->load->view('student_sponsor_portal/grant_access_form', $data);

        } catch (Exception $e) {
            log_message('error', 'Exception in grant_school_access: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            set_alert('danger', 'An error occurred: ' . $e->getMessage());
            redirect(admin_url('student_sponsor_portal/school_students'));
        }
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


        /**
     * Check if current user is a sponsor with entity access
     */
    private function is_sponsor_user()
    {
        if (!is_staff_logged_in()) {
            return false;
        }
        
        $staff_id = get_staff_user_id();
        
        // Check if this staff member is linked to a sponsor record
        $sponsor = $this->db->select('id, name, staff_id')
                        ->where('staff_id', $staff_id)
                        ->where('active', 1) // Assuming you have an active field
                        ->get(db_prefix() . 'sponsor_records')
                        ->row();
        
        return $sponsor ? $sponsor : false;
    }

    /**
     * Check sponsor access control for restricted areas
     */
    private function _check_sponsor_access()
    {
        // Skip access control for AJAX requests and specific methods
        if ($this->input->is_ajax_request()) {
            return;
        }
        
        $current_sponsor = $this->is_sponsor_user();
        
        if ($current_sponsor) {
            $method = $this->router->fetch_method();
            
            // Allowed methods for sponsors (read-only access)
            $allowed_methods = [
                'index',  // Dashboard redirect
                'sponsor_profile', // Sponsor profile view
                'my_sponsored_students', // Students list
                'student_detail', // Student detail view
                'debug_sponsor_info', // Debug method for sponsors
                'view_sponsored_student', // Alternative student detail view
                'download_school_report_card', // Download student reports
                'download_university_report_card', // Download university reports
                'display_school_photo', // View student photos
                'display_profile_photo', // View university student photos
            ];
            
            // Only redirect if accessing forbidden methods
            if (!in_array($method, $allowed_methods)) {
                set_alert('info', 'You have been redirected to your sponsor dashboard.');
                redirect(admin_url('student_sponsor_portal/sponsor_profile'));
            }
        }
    }

    /**
     * Get current sponsor's sponsored students
     */
    private function get_sponsor_students($sponsor_id)
    {
        $students = [];
        
        // Get school students sponsored by this sponsor
        $school_students = $this->db->select('
            ss.id,
            ss.name,
            ss.school_grade,
            ss.school_internal_id,
            ss.email,
            ss.contact_no,
            ss.city,
            sn.name as school_name,
            "school" as student_type
        ')
        ->from(db_prefix() . 'sponsor_transactions st')
        ->join(db_prefix() . 'school_students ss', 'ss.id = st.school_student_id', 'inner')
        ->join(db_prefix() . 'school_name sn', 'sn.id = ss.school_name_id', 'left')
        ->where('st.sponsor_id', $sponsor_id)
        ->where('ss.school_internal_id IS NOT NULL')
        ->group_by('ss.id')
        ->get()
        ->result_array();
        
        // Get university students sponsored by this sponsor
        $university_students = $this->db->select('
            us.id,
            us.name,
            us.university_year_of_study,
            us.university_internal_id,
            us.email,
            us.contact_no,
            us.city,
            un.name as university_name,
            up.name as program_name,
            "university" as student_type
        ')
        ->from(db_prefix() . 'sponsor_transactions st')
        ->join(db_prefix() . 'university_students us', 'us.id = st.university_student_id', 'inner')
        ->join(db_prefix() . 'university_name un', 'un.id = us.university_name_id', 'left')
        ->join(db_prefix() . 'university_program up', 'up.id = us.university_program_id', 'left')
        ->where('st.sponsor_id', $sponsor_id)
        ->where('us.university_internal_id IS NOT NULL')
        ->group_by('us.id')
        ->get()
        ->result_array();
        
        return array_merge($school_students, $university_students);
    }

    /**
     * Get sponsor's transaction summary
     */
    private function get_sponsor_transaction_summary($sponsor_id)
    {
        $summary = $this->db->select('
            COUNT(*) as total_transactions,
            SUM(total_amount) as total_committed,
            SUM(amount_paid) as total_paid,
            SUM(balance_amount) as total_outstanding
        ')
        ->where('sponsor_id', $sponsor_id)
        ->get(db_prefix() . 'sponsor_transactions')
        ->row_array();
        
        return $summary;
    }

    /**
     * Sponsor Dashboard - Read-only view of sponsored students
     */
    public function sponsor_dashboard()
    {
        // Check if user is a sponsor
        $current_sponsor = $this->is_sponsor_user();
        if (!$current_sponsor) {
            // If not a sponsor, check if they have regular permissions
            if (!has_permission('student_sponsor_portal', '', 'view')) {
                access_denied('student_sponsor_portal');
            }
            // If admin/staff, redirect to main dashboard
            redirect(admin_url('student_sponsor_portal'));
            return;
        }

        try {
            $sponsor_id = (int)$current_sponsor->id;
            
            // Get sponsored students
            $sponsored_students = $this->get_sponsor_students($sponsor_id);
            
            // Get transaction summary
            $transaction_summary = $this->get_sponsor_transaction_summary($sponsor_id);
            
            // Get recent transactions
            $recent_transactions = $this->db->select('
                st.*,
                ss.name as school_student_name,
                ss.school_internal_id,
                us.name as university_student_name,
                us.university_internal_id
            ')
            ->from(db_prefix() . 'sponsor_transactions st')
            ->join(db_prefix() . 'school_students ss', 'ss.id = st.school_student_id', 'left')
            ->join(db_prefix() . 'university_students us', 'us.id = st.university_student_id', 'left')
            ->where('st.sponsor_id', $sponsor_id)
            ->order_by('st.created_date', 'DESC')
            ->limit(10)
            ->get()
            ->result_array();
            
            // Get payment history
            $payment_history = $this->db->select('
                sp.*,
                st.total_amount,
                ss.name as school_student_name,
                us.name as university_student_name
            ')
            ->from(db_prefix() . 'sponsor_payments sp')
            ->join(db_prefix() . 'sponsor_transactions st', 'st.id = sp.transaction_id', 'inner')
            ->join(db_prefix() . 'school_students ss', 'ss.id = st.school_student_id', 'left')
            ->join(db_prefix() . 'university_students us', 'us.id = st.university_student_id', 'left')
            ->where('sp.sponsor_id', $sponsor_id)
            ->order_by('sp.payment_date', 'DESC')
            ->limit(15)
            ->get()
            ->result_array();
            
            $data = [
                'title' => 'My Sponsored Students',
                'sponsor' => $current_sponsor,
                'sponsored_students' => $sponsored_students,
                'transaction_summary' => $transaction_summary,
                'recent_transactions' => $recent_transactions,
                'payment_history' => $payment_history,
                'is_sponsor_view' => true
            ];
            
            $this->load->view('student_sponsor_portal/sponsor_dashboard', $data);
            
        } catch (Exception $e) {
            log_message('error', 'Error in sponsor_dashboard: ' . $e->getMessage());
            show_error('An error occurred while loading your dashboard: ' . $e->getMessage(), 500);
        }
    }


    /**
     * View transaction details (read-only for sponsors)
     */
    public function view_transaction_details($transaction_id = null)
    {
        $current_sponsor = $this->is_sponsor_user();
        if (!$current_sponsor) {
            access_denied('student_sponsor_portal');
        }
        
        if (!$transaction_id) {
            set_alert('danger', 'Transaction ID required');
            redirect(admin_url('student_sponsor_portal/sponsor_dashboard'));
            return;
        }
        
        $sponsor_id = (int)$current_sponsor->id;
        $transaction_id = (int)$transaction_id;
        
        // Get transaction and verify sponsor access
        $transaction = $this->db->select('
            st.*,
            ss.name as school_student_name,
            ss.school_internal_id,
            us.name as university_student_name,
            us.university_internal_id
        ')
        ->from(db_prefix() . 'sponsor_transactions st')
        ->join(db_prefix() . 'school_students ss', 'ss.id = st.school_student_id', 'left')
        ->join(db_prefix() . 'university_students us', 'us.id = st.university_student_id', 'left')
        ->where('st.id', $transaction_id)
        ->where('st.sponsor_id', $sponsor_id)
        ->get()
        ->row_array();
        
        if (!$transaction) {
            set_alert('danger', 'Transaction not found or access denied');
            redirect(admin_url('student_sponsor_portal/sponsor_dashboard'));
            return;
        }
        
        // Get payments for this transaction
        $payments = $this->db->select('*')
            ->where('transaction_id', $transaction_id)
            ->order_by('payment_date', 'DESC')
            ->get(db_prefix() . 'sponsor_payments')
            ->result_array();
        
        $data = [
            'title' => 'Transaction Details',
            'transaction' => $transaction,
            'payments' => $payments,
            'sponsor' => $current_sponsor,
            'is_sponsor_view' => true
        ];
        
        $this->load->view('student_sponsor_portal/sponsor_transaction_detail', $data);
    }





    // Add these methods to your Student_sponsor_portal.php controller

    /**
     * Sponsor Profile - Read-only view for sponsors
     */
    public function sponsor_profile()
    {
        $current_sponsor = $this->is_sponsor_user();
        if (!$current_sponsor) {
            access_denied('student_sponsor_portal');
        }

        try {
            $sponsor_id = (int)$current_sponsor->id;
            $sponsor = $this->sponsor_model->get_by_id($sponsor_id);
            
            if (!$sponsor) {
                set_alert('danger', 'Sponsor profile not found');
                redirect(admin_url(''));
                return;
            }

            // Get basic statistics
            $stats = [
                'total_students' => count($this->get_sponsor_students($sponsor_id)),
                'school_students' => count($this->get_sponsor_students_by_type($sponsor_id, 'school')),
                'university_students' => count($this->get_sponsor_students_by_type($sponsor_id, 'university')),
                'total_transactions' => $this->db->where('sponsor_id', $sponsor_id)->count_all_results(db_prefix() . 'sponsor_transactions'),
                'total_committed' => $this->db->select_sum('total_amount')->where('sponsor_id', $sponsor_id)->get(db_prefix() . 'sponsor_transactions')->row()->total_amount ?? 0,
                'total_paid' => $this->db->select_sum('amount_paid')->where('sponsor_id', $sponsor_id)->get(db_prefix() . 'sponsor_transactions')->row()->amount_paid ?? 0,
            ];

            $data = [
                'title' => 'My Profile',
                'sponsor' => $sponsor,
                'stats' => $stats,
                'is_sponsor_view' => true
            ];

            $this->load->view('student_sponsor_portal/sponsor_profile', $data);

        } catch (Exception $e) {
            log_message('error', 'Error in sponsor_profile: ' . $e->getMessage());
            show_error('Error loading profile', 500);
        }
    }
    public function view_sponsored_student($student_type = null, $student_id = null)
    {
        $current_sponsor = $this->is_sponsor_user();
        if (!$current_sponsor) {
            access_denied('student_sponsor_portal');
        }
        
        if (!$student_type || !$student_id || !in_array($student_type, ['school', 'university'])) {
            set_alert('danger', 'Invalid student reference');
            redirect(admin_url('student_sponsor_portal/my_sponsored_students'));
            return;
        }
        
        $sponsor_id = (int)$current_sponsor->id;
        $student_id = (int)$student_id;
        
        // Verify this sponsor has access to this student
        $access_check = $this->db->select('id')
            ->where('sponsor_id', $sponsor_id)
            ->where($student_type . '_student_id', $student_id)
            ->get(db_prefix() . 'sponsor_transactions')
            ->row();
        
        if (!$access_check) {
            set_alert('danger', 'You do not have access to view this student');
            redirect(admin_url('student_sponsor_portal/my_sponsored_students'));
            return;
        }
        
        try {
            if ($student_type === 'school') {
                $student = $this->school_model->get_by_id($student_id);
                $report_cards = $this->school_model->get_report_cards($student_id);
            } else {
                $student = $this->university_model->get_by_id($student_id);
                $report_cards = $this->university_model->get_report_cards($student_id);
            }
            
            if (!$student) {
                set_alert('danger', 'Student not found');
                redirect(admin_url('student_sponsor_portal/my_sponsored_students'));
                return;
            }
            
            // Get transactions for this student from this sponsor
            $transactions = $this->db->select('*')
                ->where('sponsor_id', $sponsor_id)
                ->where($student_type . '_student_id', $student_id)
                ->order_by('created_date', 'DESC')
                ->get(db_prefix() . 'sponsor_transactions')
                ->result_array();
            
            $data = [
                'title' => 'Student Details - ' . $student['name'],
                'student' => $student,
                'student_type' => $student_type,
                'transactions' => $transactions,
                'report_cards' => $report_cards,
                'sponsor' => $current_sponsor,
                'is_sponsor_view' => true
            ];
            
            $this->load->view('student_sponsor_portal/sponsor_student_detail', $data);
            
        } catch (Exception $e) {
            log_message('error', 'Error viewing sponsored student: ' . $e->getMessage());
            set_alert('danger', 'Error loading student details');
            redirect(admin_url('student_sponsor_portal/my_sponsored_students'));
        }
    }

    /**
     * Get sponsor students by type (helper method)
     */
    private function get_sponsor_students_by_type($sponsor_id, $type)
    {
        if ($type === 'school') {
            return $this->db->select('
                ss.id,
                ss.name,
                ss.school_grade,
                ss.school_internal_id,
                ss.email,
                ss.contact_no,
                ss.city,
                sn.name as school_name,
                "school" as student_type
            ')
            ->from(db_prefix() . 'sponsor_transactions st')
            ->join(db_prefix() . 'school_students ss', 'ss.id = st.school_student_id', 'inner')
            ->join(db_prefix() . 'school_name sn', 'sn.id = ss.school_name_id', 'left')
            ->where('st.sponsor_id', $sponsor_id)
            ->where('ss.school_internal_id IS NOT NULL')
            ->group_by('ss.id')
            ->get()
            ->result_array();
        } else {
            return $this->db->select('
                us.id,
                us.name,
                us.university_year_of_study,
                us.university_internal_id,
                us.email,
                us.contact_no,
                us.city,
                un.name as university_name,
                up.name as program_name,
                "university" as student_type
            ')
            ->from(db_prefix() . 'sponsor_transactions st')
            ->join(db_prefix() . 'university_students us', 'us.id = st.university_student_id', 'inner')
            ->join(db_prefix() . 'university_name un', 'un.id = us.university_name_id', 'left')
            ->join(db_prefix() . 'university_program up', 'up.id = us.university_program_id', 'left')
            ->where('st.sponsor_id', $sponsor_id)
            ->where('us.university_internal_id IS NOT NULL')
            ->group_by('us.id')
            ->get()
            ->result_array();
        }
    }




        /* =================================================================================== */
        /* =====================            UNIVERSITY STUDENTS            =================== */
        /* =================================================================================== */
        private function is_university_student_user()
        {
            if (!is_staff_logged_in()) {
                return false;
            }
            
            $staff_id = get_staff_user_id();
            
            // Check if this staff member is linked to a university student
            $student = $this->db->select('id, entity_type, university_internal_id, name')
                            ->where('staff_id', $staff_id)
                            ->where('entity_type', 'university')
                            ->where('active', 1) // or 'staff_active' depending on your column name
                            ->get(db_prefix() . 'university_students')
                            ->row();
            
            return $student ? $student : false;
        }
        private function get_current_university_student_record()
        {
            $staff_id = get_staff_user_id();
            
            return $this->db->where('staff_id', $staff_id)
                        ->where('entity_type', 'university')
                        ->get(db_prefix() . 'university_students')
                        ->row_array();
        }
        private function can_access_university_student($student_id)
        {
            // Admin can access all
            if (is_admin()) {
                return true;
            }
            
            // Check if user is a university student trying to access their own record
            $current_student = $this->is_university_student_user();
            if ($current_student) {
                return (int)$current_student->id === (int)$student_id;
            }
            
            // Regular permission check for other staff
            return has_permission('student_sponsor_portal', '', 'view');
        }
        private function _check_university_student_access()
        {
            // Skip access control for AJAX requests and specific methods
            if ($this->input->is_ajax_request()) {
                return;
            }
            
            $current_student = $this->is_university_student_user();
            
            if ($current_student) {
                $method = $this->router->fetch_method();
                
                // Allowed methods for university students
                $allowed_methods = [
                    'index',  // Add this to prevent redirect loops
                    'university_student_form',
                    'get_university_student', 
                    'display_profile_photo',
                    'upload_university_report_card',
                    'get_university_report_cards',
                    'download_university_report_card',
                    // Add AJAX methods for form helpers
                    'add_university_ajax',
                    'add_program_ajax',
                    'add_country_ajax',
                    'add_bank_ajax'
                ];
                
                // Only redirect if accessing truly forbidden methods
                if (!in_array($method, $allowed_methods)) {
                    // Use a more gentle redirect that preserves session
                    set_alert('info', 'You have been redirected to your profile.');
                    redirect(admin_url('student_sponsor_portal/university_student_form/' . $current_student->id));
                }
            }
        }
        private function create_or_update_staff_from_university_student(array $student_data, ?int $existing_staff_id): ?int
        {
            $staff_data = [
                'staff_email'     => $student_data['email'] ?? '',
                'staff_firstname' => $student_data['name'] ?? '',
                'staff_lastname'  => '',
                'staff_password'  => $student_data['staff_password'] ?? '',
                'active'          => !empty($student_data['staff_active']) ? 1 : 0,
            ];

            return $this->upsert_staff($staff_data, $existing_staff_id, 'University Student');
    }
        public function university_students()
        {
        $current_student = $this->is_school_student_user();
        if ($current_student) {
            redirect(admin_url('student_sponsor_portal/school_student_form/' . $current_student->id));
            return;
        }
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
        
        public function sponsor()
        {
            // $result = $this->university_model->is_student_sponsored($id);
            $result = $this->university_model->get_all();
            if($result){
                echo "<pre>";
                print_r($result);
                echo "</pre>";
            }else{
                echo "No Sponsor ";
            }
        }
        public function university_student_form($student_id = null)
        {
            if (!has_permission('student_sponsor_portal', '', 'view')) access_denied('student_sponsor_portal');

            try {
                log_message('debug', 'Loading university student form for ID: ' . ($student_id ?: 'new'));

                // IMPORTANT: Define $current_student at the beginning to avoid undefined variable error
                $current_university_student = $this->is_university_student_user();
                $is_university_student = (bool)$current_university_student;

                if ($this->input->post()) {
                    $isCreate = empty($this->input->post('student_id'));
                    if ($isCreate && !has_permission('student_sponsor_portal', '', 'create')) access_denied('student_sponsor_portal');
                    if (!$isCreate && !has_permission('student_sponsor_portal', '', 'edit')) access_denied('student_sponsor_portal');

                    $post = $this->input->post();
                    $sid = (int)($post['student_id'] ?? 0);
                    unset($post['student_id']);

                    log_message('debug', 'Raw POST data: ' . json_encode($post));

                    // Clean and map form data to database fields
                    $cleaned_data = $this->clean_university_post_data($post, $is_university_student);

                    $this->session->set_flashdata('old_input', $post);

                    if ($isCreate) {
                        $res = $this->university_model->add($cleaned_data);
                        
                        // Handle both old format (just ID) and new format (array with success flag)
                        if (is_array($res)) {
                            if (!$res['success']) {
                                $this->session->set_flashdata('old_input', $post);
                                set_alert('danger', $res['message'] ?? 'Error saving student');
                                redirect(admin_url('student_sponsor_portal/university_student_form'));
                                return;
                            }
                            $newId = (int)$res['id'];
                        } else {
                            $newId = $res ? (int)$res : 0;
                            if (!$newId) {
                                $this->session->set_flashdata('old_input', $post);
                                set_alert('danger', 'Error saving student');
                                redirect(admin_url('student_sponsor_portal/university_student_form'));
                                return;
                            }
                        }

                        // Handle profile photo upload
                        $this->handle_university_profile_photo_upload($newId);

                        // Handle staff creation - FIXED FOR UNIVERSITY STUDENTS
                        if (!empty($this->input->post('create_staff'))) {
                            $existing = $this->university_model->get_by_id($newId);
                            $existing_staff_id = $existing['staff_id'] ?? null;

                            $this->ensure_three_min_roles();
                            $staff_id = $this->create_or_update_staff_from_university_student($this->input->post(), $existing_staff_id);

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

                        log_message('debug', 'Attempting to update university student ID: ' . $sid . ' with data: ' . json_encode($cleaned_data));
                        
                        // Get student data before update for comparison
                        $before_update = $this->university_model->get_by_id($sid);
                        log_message('debug', 'University student data BEFORE update: ' . json_encode($before_update));

                        $result = $this->university_model->update_student($cleaned_data, $sid);
                        
                        // Handle both boolean and array responses
                        if (is_array($result)) {
                            if (!$result['success']) {
                                set_alert('danger', $result['message'] ?? 'Error updating student');
                                redirect(admin_url('student_sponsor_portal/university_student_form/' . $sid));
                                return;
                            }
                            $ok = true;
                        } else {
                            $ok = $result;
                        }
                        
                        if ($ok) {
                            // Get student data after update for verification
                            $after_update = $this->university_model->get_by_id($sid);
                            log_message('debug', 'University student data AFTER update: ' . json_encode($after_update));
                            
                            set_alert('success', 'University student updated successfully');
                        } else {
                            $err = $this->db->error();
                            log_message('error', 'Database update failed for university student ID: ' . $sid . '. Error: ' . json_encode($err));
                            $msg = (!empty($err['code']) && (int)$err['code'] === 1062)
                                ? 'Duplicate value detected (e.g., email). Please use a different value.'
                                : 'Error saving student. Please check all fields and try again.';
                            set_alert('danger', $msg);
                            redirect(admin_url('student_sponsor_portal/university_student_form/' . $sid));
                            return;
                        }

                        // Handle staff creation for existing student - FIXED
                        if (!empty($this->input->post('create_staff'))) {
                            $existing = $this->university_model->get_by_id($sid);
                            $existing_staff_id = $existing['staff_id'] ?? null;

                            $this->ensure_three_min_roles();
                            $staff_id = $this->create_or_update_staff_from_university_student($this->input->post(), $existing_staff_id);

                            if ($staff_id) {
                                $this->db->where('id', $sid)->update(db_prefix() . 'university_students', [
                                    'staff_id' => $staff_id,
                                    'active' => !empty($this->input->post('staff_active')) ? 1 : 0,
                                ]);
                            }
                        }

                        // FIXED: Proper redirect for university students
                        if ($is_university_student) {
                            redirect(admin_url('student_sponsor_portal/university_student_form/' . $sid));
                        } else {
                            redirect(admin_url('student_sponsor_portal/university_students'));
                        }
                    }
                }

                // If university student, force them to only access their own record
                if ($current_university_student) {
                    $student_id = (int)$current_university_student->id;
                }

                $data['title'] = 'Register University Student';
                if ($student_id) {
                    $student = $this->university_model->get_by_id((int)$student_id);
                    if (!$student) { 
                        set_alert('danger', 'Student not found');
                        redirect(admin_url('student_sponsor_portal/university_students')); 
                    }
                    
                    // Access control: university students can only view their own record
                    if ($current_university_student && (int)$student['id'] !== (int)$current_university_student->id) {
                        access_denied('student_sponsor_portal');
                    }
                    
                    $data['student'] = $student;
                    $data['title'] = $is_university_student ? 'My Profile' : 'Edit University Student';
                }

                // Pass the university student flag to the view
                $data['is_university_student'] = $is_university_student;
                $data['can_edit_restricted_fields'] = !$is_university_student;

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
        
        // Size validation
        if ($file['size'] > $max_size) {
            set_alert('warning', 'Profile photo must be less than 2MB');
            return false;
        }
        
        // MIME type detection WITHOUT fileinfo
        $mime_type = $this->detect_mime_type_safe($file['tmp_name'], $file['type'] ?? '');
        
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
    /**
     * Detect MIME type WITHOUT fileinfo extension dependency
     * Uses multiple fallback methods
     */
    private function detect_mime_type_safe($file_path, $uploaded_type = '')
    {
        // Method 1: Use getimagesize (BEST - works without any extension!)
        if (function_exists('getimagesize')) {
            $image_info = @getimagesize($file_path);
            if ($image_info !== false && isset($image_info['mime'])) {
                log_message('debug', 'MIME detected via getimagesize: ' . $image_info['mime']);
                return strtolower($image_info['mime']);
            }
        }
        
        // Method 2: Try mime_content_type if available
        if (function_exists('mime_content_type')) {
            try {
                $detected = @mime_content_type($file_path);
                if ($detected !== false && $detected !== '') {
                    log_message('debug', 'MIME detected via mime_content_type: ' . $detected);
                    return strtolower($detected);
                }
            } catch (Exception $e) {
                log_message('warning', 'mime_content_type failed: ' . $e->getMessage());
            }
        }
        
        // Method 3: Check file signature (magic bytes) - RELIABLE
        if (is_readable($file_path)) {
            $handle = @fopen($file_path, 'rb');
            if ($handle) {
                $bytes = fread($handle, 12);
                fclose($handle);
                
                // Check for common image types
                if (substr($bytes, 0, 3) === "\xFF\xD8\xFF") {
                    log_message('debug', 'MIME detected by signature: image/jpeg');
                    return 'image/jpeg';
                }
                if (substr($bytes, 0, 8) === "\x89PNG\r\n\x1a\n") {
                    log_message('debug', 'MIME detected by signature: image/png');
                    return 'image/png';
                }
                if (substr($bytes, 0, 3) === "GIF") {
                    log_message('debug', 'MIME detected by signature: image/gif');
                    return 'image/gif';
                }
                if (substr($bytes, 8, 4) === "WEBP") {
                    log_message('debug', 'MIME detected by signature: image/webp');
                    return 'image/webp';
                }
            }
        }
        
        // Method 4: Use uploaded MIME type if valid
        if (!empty($uploaded_type)) {
            $uploaded_type = strtolower(trim($uploaded_type));
            $valid_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($uploaded_type, $valid_types)) {
                log_message('debug', 'Using uploaded MIME type: ' . $uploaded_type);
                return $uploaded_type;
            }
        }
        
        // Method 5: Guess from file extension as absolute fallback
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $extension_map = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
        ];
        
        if (isset($extension_map[$extension])) {
            log_message('debug', 'MIME guessed from extension: ' . $extension_map[$extension]);
            return $extension_map[$extension];
        }
        
        log_message('warning', 'Could not determine MIME type for: ' . $file_path);
        return 'application/octet-stream';
    }   
        public function upload_university_report_card()
        {
            header('Content-Type: application/json');

            // Check if user is a university student with access to their own records
            $current_university_student = $this->is_university_student_user();
            $is_university_student = (bool)$current_university_student;
            
            // Allow access if user is admin with permissions OR if they're a university student
            if (!$is_university_student && !has_permission('student_sponsor_portal', '', 'create')) {
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

            // If user is a university student, ensure they can only upload to their own record
            if ($is_university_student && (int)$current_university_student->id !== $student_id) {
                echo json_encode(['success' => false, 'message' => 'Access denied - can only upload to your own profile']);
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

            if ($semester_end_month === null || $semester_end_year === null) {
                echo json_encode(['success' => false, 'message' => 'Semester end month and year are required']);
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

            // Enhanced MIME detection
            $mime = $this->detect_mime_type($tmpName, $_FILES['report_card_file']['type'] ?? '');

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
        // Update your clean_university_post_data method in Student_sponsor_portal.php controller

        private function clean_university_post_data($data, $is_university_student = false)
    {
        $cleaned = [];
        
        // Form-style field mappings (what your form sends)
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
            'background_information' => 'background_info',
        ];

        // Extra: support importer-style keys (already DB names)
        // This is the exact problem you are facing.
        $importer_db_keys = [
            'contact_no'                  => 'contact_no',
            'zip'                         => 'zip',
            'university_year_of_study'    => 'university_year_of_study',
            'university_student_dob'      => 'university_student_dob',
            'university_age'              => 'university_age',
            'university_bank_account_no'  => 'university_bank_account_no',
            'university_bank_branch_info' => 'university_bank_branch_info',
            'university_bank_branch_number' => 'university_bank_branch_number',
            'university_father_name'      => 'university_father_name',
            'university_mother_name'      => 'university_mother_name',
            'university_father_income'    => 'university_father_income',
            'university_mother_income'    => 'university_mother_income',
            'university_guardian_name'    => 'university_guardian_name',
            'university_guardian_income'  => 'university_guardian_income',
            'background_info'             => 'background_info',
        ];

        // Admin-only fields
        $admin_only_fields = [
            'sponsorship_start' => 'university_sponsorship_start_date',
            'sponsorship_end' => 'university_sponsorship_end_date',
            'introduced_by' => 'university_introducedby',
            'introduced_phone' => 'university_introducedph',
            'sponsor_id' => 'sponsor_id',
            'internal_comment' => 'internal_comment',
            'external_comment' => 'external_comment'
        ];

        if (!$is_university_student) {
            $field_mappings = array_merge($field_mappings, $admin_only_fields);
            // importer may also send these already in DB format, so add them too
            $importer_db_keys = array_merge($importer_db_keys, [
                'university_sponsorship_start_date' => 'university_sponsorship_start_date',
                'university_sponsorship_end_date'   => 'university_sponsorship_end_date',
                'university_introducedby'           => 'university_introducedby',
                'university_introducedph'           => 'university_introducedph',
                'sponsor_id'                        => 'sponsor_id',
                'internal_comment'                  => 'internal_comment',
                'external_comment'                  => 'external_comment',
            ]);
        }
        
        // 1) handle form-style keys
        foreach ($field_mappings as $form_field => $db_field) {
            if (isset($data[$form_field])) {
                $value = is_string($data[$form_field]) ? trim($data[$form_field]) : $data[$form_field];

                if ($value === '' || $value === null) {
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

        // 2) handle importer-style keys (already DB names)
        foreach ($importer_db_keys as $db_key => $final_key) {
            if (isset($data[$db_key])) {
                $value = is_string($data[$db_key]) ? trim($data[$db_key]) : $data[$db_key];

                if ($value === '' || $value === null) {
                    $cleaned[$final_key] = ($value === '') ? '' : null;
                } elseif (in_array($db_key, ['university_father_income', 'university_mother_income', 'university_guardian_income'])) {
                    $cleaned[$final_key] = is_numeric($value) ? (float)$value : null;
                } elseif (in_array($db_key, ['country_id', 'bank_id', 'university_name_id', 'university_program_id', 'sponsor_id', 'university_age'])) {
                    $cleaned[$final_key] = is_numeric($value) ? (int)$value : null;
                } else {
                    $cleaned[$final_key] = $value;
                }
            }
        }
        
        // auto age
        if (!empty($cleaned['university_student_dob']) && empty($cleaned['university_age'])) {
            try {
                $dob = new DateTime($cleaned['university_student_dob']);
                $now = new DateTime();
                $cleaned['university_age'] = $dob->diff($now)->y;
            } catch (Exception $e) {
                log_message('error', 'Error calculating age: ' . $e->getMessage());
            }
        }
        
        if (!$is_university_student) {
            $cleaned['entity_type'] = 'university';
        }

        // your create_new_* logic stays the same
        if (!$is_university_student) {
            if (!empty($data['new_country_name']) && !empty($data['new_country_phone_code'])) {
                $country_id = $this->create_new_country($data['new_country_name'], $data['new_country_phone_code']);
                if ($country_id) {
                    $cleaned['country_id'] = $country_id;
                }
            }

            if (!empty($data['new_university_name'])) {
                $university_id = $this->create_new_university($data['new_university_name']);
                if ($university_id) {
                    $cleaned['university_name_id'] = $university_id;
                }
            }

            if (!empty($data['new_program_name'])) {
                $program_id = $this->create_new_program($data['new_program_name']);
                if ($program_id) {
                    $cleaned['university_program_id'] = $program_id;
                }
            }

            if (!empty($data['new_bank_name'])) {
                $bank_id = $this->create_new_bank($data['new_bank_name']);
                if ($bank_id) {
                    $cleaned['bank_id'] = $bank_id;
                }
            }
        }
        
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

    /**
     * Bulk import university students from Excel/CSV
     */
    public function bulk_import_university_students()
    {
        if (!has_permission('student_sponsor_portal', '', 'create')) {
            access_denied('student_sponsor_portal');
        }

        // Handle POST submission
        if ($this->input->method() === 'post') {
            log_message('debug', 'University Import: POST request received for bulk import');
            
            if (empty($_FILES['import_file']['name'])) {
                log_message('error', 'University Import: No file uploaded');
                set_alert('danger', 'Please select a file to import');
                redirect(admin_url('student_sponsor_portal/bulk_import_university_students'));
                return;
            }

            $file_data = $_FILES['import_file'];
            log_message('debug', 'University Import: File uploaded - ' . $file_data['name'] . ' (' . $file_data['size'] . ' bytes)');

            // Validate file type - Accept both Excel and CSV
            $file_ext = strtolower(pathinfo($file_data['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['xlsx', 'xls', 'csv'];
            
            if (!in_array($file_ext, $allowed_extensions)) {
                log_message('error', 'University Import: Invalid file type - ' . $file_ext);
                set_alert('danger', 'Only Excel (.xlsx, .xls) and CSV files are supported.');
                redirect(admin_url('student_sponsor_portal/bulk_import_university_students'));
                return;
            }

            // Check file size (max 20MB for Excel files)
            if ($file_data['size'] > 20 * 1024 * 1024) {
                log_message('error', 'University Import: File too large - ' . $file_data['size'] . ' bytes');
                set_alert('danger', 'File size exceeds 20MB limit');
                redirect(admin_url('student_sponsor_portal/bulk_import_university_students'));
                return;
            }

            // Process the uploaded file
            $temp_file = $file_data['tmp_name'];
            
            try {
                log_message('debug', 'University Import: Starting to process file');
                $result = $this->process_university_import_file($temp_file, $file_ext);
                
                if ($result['success']) {
                    $message = "University import completed! ";
                    $message .= "Added: {$result['added']}, ";
                    $message .= "Updated: {$result['updated']}, ";
                    $message .= "Errors: {$result['errors']}";
                    
                    log_message('info', 'University Import: ' . $message);
                    
                    if (!empty($result['error_details'])) {
                        $this->session->set_flashdata('import_errors', $result['error_details']);
                    }
                    
                    set_alert('success', $message);
                } else {
                    log_message('error', 'University import failed: ' . $result['message']);
                    set_alert('danger', 'Import failed: ' . $result['message']);
                }
                
            } catch (Exception $e) {
                log_message('error', 'University Import Exception: ' . $e->getMessage());
                set_alert('danger', 'Import failed: ' . $e->getMessage());
            }
            
            redirect(admin_url('student_sponsor_portal/bulk_import_university_students'));
            return;
        }
        
        // Show the form
        $data['title'] = 'Bulk Import University Students';
        $data['import_errors'] = $this->session->flashdata('import_errors');
        $this->load->view('student_sponsor_portal/bulk_import_university_students', $data);
    }

    /**
     * Process university import file (Excel or CSV)
     */
    private function process_university_import_file($file_path, $file_ext)
    {
        log_message('debug', 'University Import: Processing file: ' . $file_path . ' (.' . $file_ext . ')');
        
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return ['success' => false, 'message' => 'Cannot read upload file'];
        }
        
        $added = 0;
        $updated = 0;
        $errors = 0;
        $error_details = [];
        
        try {
            // Load PHPSpreadsheet for Excel files
            if (in_array($file_ext, ['xlsx', 'xls'])) {
                // Check if PHPSpreadsheet is available
                $autoload_paths = [
                    FCPATH . 'vendor/autoload.php',
                    APPPATH . 'third_party/vendor/autoload.php',
                    APPPATH . 'libraries/vendor/autoload.php'
                ];
                
                $phpspreadsheet_loaded = false;
                foreach ($autoload_paths as $path) {
                    if (file_exists($path)) {
                        require_once($path);
                        $phpspreadsheet_loaded = true;
                        log_message('debug', 'University Import: PHPSpreadsheet loaded from: ' . $path);
                        break;
                    }
                }
                
                if (!$phpspreadsheet_loaded || !class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
                    return ['success' => false, 'message' => 'PHPSpreadsheet not available. Please install via Composer or use CSV format.'];
                }
                
                try {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
                    $worksheet = $spreadsheet->getActiveSheet();
                    $rows = $worksheet->toArray(null, true, true, true);
                    log_message('debug', 'University Import: Excel file loaded successfully with ' . count($rows) . ' rows');
                } catch (Exception $e) {
                    return ['success' => false, 'message' => 'Error reading Excel file: ' . $e->getMessage()];
                }
            } else {
                // Process CSV file
                $rows = [];
                if (($handle = fopen($file_path, "r")) !== FALSE) {
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        $rows[] = $data;
                    }
                    fclose($handle);
                    log_message('debug', 'University Import: CSV file loaded successfully with ' . count($rows) . ' rows');
                } else {
                    return ['success' => false, 'message' => 'Cannot open CSV file'];
                }
            }
            
            if (empty($rows)) {
                return ['success' => false, 'message' => 'No data found in file'];
            }
            
            // Get header row and create column mapping
            $headers = array_shift($rows);
            if (empty($headers)) {
                return ['success' => false, 'message' => 'No headers found in file'];
            }
            
            log_message('debug', 'University Import: Headers found: ' . json_encode($headers));
            
            // Create column mapping
            $column_map = $this->create_university_column_mapping($headers);
            
            if (empty($column_map)) {
                return ['success' => false, 'message' => 'No valid columns found. Please check your headers match the template.'];
            }
            
            log_message('debug', 'University Import: Column mapping: ' . json_encode($column_map));
            
            $row_number = 1; // Start from 1 (header is row 0)
            
            // Process each data row
            foreach ($rows as $row) {
                $row_number++;
                
                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }
                    
                    log_message('debug', 'University Import: Processing row ' . $row_number);
                    
                    // Map the row data
                    $student_data = $this->map_university_row_data($row, $column_map);
                    
                    // Basic validation
                    if (empty($student_data['name'])) {
                        $errors++;
                        $error_details[] = "Row {$row_number}: Name is required";
                        continue;
                    }
                    
                    // Check for existing student
                    $existing = null;
                    if (!empty($student_data['email'])) {
                        $existing = $this->db->where('email', $student_data['email'])
                                        ->get(db_prefix().'university_students')
                                        ->row_array();
                    } elseif (!empty($student_data['university_internal_id'])) {
                        $existing = $this->db->where('university_internal_id', $student_data['university_internal_id'])
                                        ->get(db_prefix().'university_students')
                                        ->row_array();
                    }
                    
                    // Clean data using the same method as the form
                    $cleaned_data = $this->clean_university_post_data($student_data, false);
                    
                    if ($existing) {
                        // Update existing student
                        log_message('debug', 'University Import: Updating existing student: ' . $existing['id']);
                        
                        $result = $this->university_model->update($cleaned_data, $existing['id']);
                        
                        if ($result) {
                            $updated++;
                        } else {
                            $errors++;
                            $error_details[] = "Row {$row_number}: Failed to update student";
                        }
                    } else {
                        // Add new student
                        log_message('debug', 'University Import: Adding new student');
                        
                        $result = $this->university_model->add($cleaned_data);
                        
                        if ($result && !is_array($result)) {
                            $added++;
                            log_message('debug', 'University Import: Added student with ID: ' . $result);
                        } else {
                            $errors++;
                            $message = is_array($result) ? ($result['message'] ?? 'Unknown error') : 'Failed to add student';
                            $error_details[] = "Row {$row_number}: {$message}";
                        }
                    }
                    
                } catch (Exception $e) {
                    $errors++;
                    $error_details[] = "Row {$row_number}: " . $e->getMessage();
                    log_message('error', 'University Import: Row error: ' . $e->getMessage());
                }
            }
            
            log_message('info', "University import completed. Added: {$added}, Updated: {$updated}, Errors: {$errors}");
            
            return [
                'success' => true,
                'added' => $added,
                'updated' => $updated,
                'errors' => $errors,
                'error_details' => $error_details
            ];
            
        } catch (Exception $e) {
            log_message('error', 'University import processing error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Processing error: ' . $e->getMessage()];
        }
    }

    /**
     * Create column mapping from headers for university students
     */
    private function create_university_column_mapping($headers)
    {
        $mapping = [];

        log_message('debug', 'University Import: Processing headers - count: ' . count($headers));
        log_message('debug', 'University Import: Raw headers: ' . json_encode($headers));

        // **COMPREHENSIVE field mappings - covers ALL database columns**
        $field_mappings = [
            // Basic Information
            'name' => 'name',
            'student_name' => 'name',
            'full_name' => 'name',
            
            'email' => 'email',
            'email_address' => 'email',
            
            'phone' => 'contact_no',
            'contact_no' => 'contact_no',
            'phone_number' => 'contact_no',
            'contact_number' => 'contact_no',
            'mobile' => 'contact_no',

            // Personal Details
            'date_of_birth' => 'university_student_dob',
            'dob' => 'university_student_dob',
            'birth_date' => 'university_student_dob',
            'university_student_dob' => 'university_student_dob',
            
            'age' => 'university_age',
            'university_age' => 'university_age',
            'student_age' => 'university_age',

            // Address Information
            'address' => 'address',
            'street_address' => 'address',
            'home_address' => 'address',
            
            'city' => 'city',
            'town' => 'city',
            'district' => 'city',
            
            'postal_code' => 'zip',
            'zip_code' => 'zip',
            'zip' => 'zip',
            'postcode' => 'zip',
            
            'country' => 'country_name',
            'country_name' => 'country_name',
            'country_id' => 'country_id',

            // University Information
            'university_id' => 'university_id',
            'student_id' => 'university_id',
            'student_number' => 'university_id',
            
            'internal_id' => 'university_internal_id',
            'university_internal_id' => 'university_internal_id',
            'internal_student_id' => 'university_internal_id',
            
            'university' => 'university_name',
            'university_name' => 'university_name',
            'institution' => 'university_name',
            'university_name_id' => 'university_name_id',
            
            'program' => 'university_program',
            'course' => 'university_program',
            'degree' => 'university_program',
            'program_name' => 'university_program',
            'university_program' => 'university_program',
            'university_program_id' => 'university_program_id',
            
            'year_of_study' => 'university_year_of_study',
            'university_year_of_study' => 'university_year_of_study',
            'year' => 'university_year_of_study',
            'study_year' => 'university_year_of_study',
            'current_year' => 'university_year_of_study',

            // Bank Information
            'bank' => 'bank_name',
            'bank_name' => 'bank_name',
            'bank_id' => 'bank_id',
            
            'account_number' => 'university_bank_account_no',
            'bank_account_number' => 'university_bank_account_no',
            'bank_account_no' => 'university_bank_account_no',
            'university_bank_account_no' => 'university_bank_account_no',
            
            'branch_number' => 'university_bank_branch_number',
            'branch_code' => 'university_bank_branch_number',
            'bank_branch_number' => 'university_bank_branch_number',
            'university_bank_branch_number' => 'university_bank_branch_number',
            
            'branch_info' => 'university_bank_branch_info',
            'branch_information' => 'university_bank_branch_info',
            'bank_branch_info' => 'university_bank_branch_info',
            'university_bank_branch_info' => 'university_bank_branch_info',
            'branch' => 'university_bank_branch_info',

            // Family Information
            'father_name' => 'university_father_name',
            'father' => 'university_father_name',
            'university_father_name' => 'university_father_name',
            
            'father_income' => 'university_father_income',
            'university_father_income' => 'university_father_income',
            
            'mother_name' => 'university_mother_name',
            'mother' => 'university_mother_name',
            'university_mother_name' => 'university_mother_name',
            
            'mother_income' => 'university_mother_income',
            'university_mother_income' => 'university_mother_income',
            
            'guardian_name' => 'university_guardian_name',
            'guardian' => 'university_guardian_name',
            'university_guardian_name' => 'university_guardian_name',
            
            'guardian_income' => 'university_guardian_income',
            'university_guardian_income' => 'university_guardian_income',

            // Sponsorship Information
            'sponsorship_start' => 'university_sponsorship_start_date',
            'sponsorship_start_date' => 'university_sponsorship_start_date',
            'university_sponsorship_start_date' => 'university_sponsorship_start_date',
            'start_date' => 'university_sponsorship_start_date',
            
            'sponsorship_end' => 'university_sponsorship_end_date',
            'sponsorship_end_date' => 'university_sponsorship_end_date',
            'university_sponsorship_end_date' => 'university_sponsorship_end_date',
            'end_date' => 'university_sponsorship_end_date',
            
            'introduced_by' => 'university_introducedby',
            'introducer' => 'university_introducedby',
            'university_introducedby' => 'university_introducedby',
            'introduced_by_name' => 'university_introducedby',
            
            'introduced_phone' => 'university_introducedph',
            'introducer_phone' => 'university_introducedph',
            'university_introducedph' => 'university_introducedph',
            'introducer_contact' => 'university_introducedph',
            
            'sponsor_id' => 'sponsor_id',
            'sponsor' => 'sponsor_id',

            // Comments/Notes
            'background_information' => 'background_info',
            'background' => 'background_info',
            'background_info' => 'background_info',
            'notes' => 'background_info',
            
            'internal_comment' => 'internal_comment',
            'admin_notes' => 'internal_comment',
            'internal_notes' => 'internal_comment',
            
            'external_comment' => 'external_comment',
            'public_notes' => 'external_comment',
            'external_notes' => 'external_comment',
        ];

        // Map headers to database columns
        foreach ($headers as $index => $header) {
            // CRITICAL FIX: Remove UTF-8 BOM and normalize
            $header = str_replace("\xEF\xBB\xBF", '', $header);
            
            // Normalize: lowercase, spaces/hyphens to underscores, remove special chars
            $clean_header = strtolower(trim($header));
            $clean_header = preg_replace('/[\s\-]+/', '_', $clean_header);
            $clean_header = preg_replace('/[^\w]/', '', $clean_header);

            if (isset($field_mappings[$clean_header])) {
                $db_column = $field_mappings[$clean_header];
                $mapping[$db_column] = $index;
                log_message('debug', "University Import: ✓ Mapped '{$header}' (cleaned: '{$clean_header}') to '{$db_column}' at INDEX {$index}");
            } else {
                log_message('debug', "University Import: ✗ UNMAPPED header '{$header}' (cleaned: '{$clean_header}') at INDEX {$index}");
            }
        }

        log_message('debug', 'University Import: Final mapping count: ' . count($mapping));
        log_message('debug', 'University Import: Column mapping: ' . json_encode($mapping));
        
        return $mapping;
    }
    /**
     * Map row data for university students
     */
    private function map_university_row_data($row, $mapping)
    {
        $data = [];
        
        log_message('debug', 'University Import: Mapping row with ' . count($row) . ' columns');
        
        foreach ($mapping as $field => $column_index) {
            $value = isset($row[$column_index]) ? trim($row[$column_index]) : '';
            
            // **CRITICAL: Don't skip empty values - let database handle them**
            
            // Handle special field transformations
            switch ($field) {
                case 'university_student_dob':
                case 'university_sponsorship_start_date':
                case 'university_sponsorship_end_date':
                    if ($value && $value !== '') {
                        try {
                            // Handle Excel date serial numbers
                            if (is_numeric($value) && class_exists('\PhpOffice\PhpSpreadsheet\Shared\Date')) {
                                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                                $data[$field] = $date->format('Y-m-d');
                            } else {
                                $date = new DateTime($value);
                                $data[$field] = $date->format('Y-m-d');
                            }
                            log_message('debug', "University Import: Converted date '{$value}' to '{$data[$field]}' for field '{$field}'");
                        } catch (Exception $e) {
                            $data[$field] = null;
                            log_message('warning', "University Import: Invalid date format for field {$field}: {$value}");
                        }
                    } else {
                        $data[$field] = null;
                    }
                    break;
                    
                case 'university_father_income':
                case 'university_mother_income':
                case 'university_guardian_income':
                    $data[$field] = ($value !== '' && is_numeric($value)) ? (float)$value : null;
                    log_message('debug', "University Import: Income field '{$field}' = " . var_export($data[$field], true));
                    break;
                    
                case 'university_age':
                case 'sponsor_id':
                    $data[$field] = ($value !== '' && is_numeric($value)) ? (int)$value : null;
                    log_message('debug', "University Import: Integer field '{$field}' = " . var_export($data[$field], true));
                    break;
                    
                case 'university_year_of_study':
                    // Normalize year values
                    if ($value && $value !== '') {
                        $year_map = [
                            '1' => '1Y1S', 'year 1' => '1Y1S', '1st year' => '1Y1S', 'first year' => '1Y1S',
                            '2' => '2Y1S', 'year 2' => '2Y1S', '2nd year' => '2Y1S', 'second year' => '2Y1S',
                            '3' => '3Y1S', 'year 3' => '3Y1S', '3rd year' => '3Y1S', 'third year' => '3Y1S',
                            '4' => '4Y1S', 'year 4' => '4Y1S', '4th year' => '4Y1S', 'fourth year' => '4Y1S',
                            '5' => '5Y1S', 'year 5' => '5Y1S', '5th year' => '5Y1S', 'fifth year' => '5Y1S',
                        ];
                        $normalized = strtolower(trim($value));
                        $data[$field] = $year_map[$normalized] ?? $value;
                    } else {
                        $data[$field] = null;
                    }
                    log_message('debug', "University Import: Year field '{$field}' = '{$data[$field]}'");
                    break;
                    
                default:
                    // All other fields - preserve value (even empty strings)
                    $data[$field] = $value !== '' ? $value : null;
                    if ($value !== '') {
                        log_message('debug', "University Import: Field '{$field}' = '{$value}'");
                    }
                    break;
            }
        }
        
        // Handle foreign key lookups AFTER all fields are mapped
        
        // Country lookup
        if (!empty($data['country_name'])) {
            $country_id = $this->get_or_create_country_id($data['country_name']);
            if ($country_id) {
                $data['country_id'] = $country_id;
                log_message('debug', "University Import: Country '{$data['country_name']}' resolved to ID: {$country_id}");
            }
            unset($data['country_name']);
        }
        
        // University lookup
        if (!empty($data['university_name'])) {
            $university_id = $this->university_model->get_or_create_university_name_id($data['university_name']);
            if ($university_id) {
                $data['university_name_id'] = $university_id;
                log_message('debug', "University Import: University '{$data['university_name']}' resolved to ID: {$university_id}");
            }
            unset($data['university_name']);
        }
        
        // Program lookup
        if (!empty($data['university_program'])) {
            $program_id = $this->university_model->get_or_create_university_program_id($data['university_program']);
            if ($program_id) {
                $data['university_program_id'] = $program_id;
                log_message('debug', "University Import: Program '{$data['university_program']}' resolved to ID: {$program_id}");
            }
            unset($data['university_program']);
        }
        
        // Bank lookup
        if (!empty($data['bank_name'])) {
            $bank_id = $this->get_or_create_bank_id($data['bank_name']);
            if ($bank_id) {
                $data['bank_id'] = $bank_id;
                log_message('debug', "University Import: Bank '{$data['bank_name']}' resolved to ID: {$bank_id}");
            }
            unset($data['bank_name']);
        }
        
        // Set entity_type
        $data['entity_type'] = 'university';
        
        log_message('debug', 'University Import: Final mapped data: ' . json_encode($data));
        
        return $data;
    }

    /**
     * Download Excel template for university students
     */
    public function download_university_students_template()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }
        
        // Check if PHPSpreadsheet is available
        $autoload_paths = [
            FCPATH . 'vendor/autoload.php',
            APPPATH . 'third_party/vendor/autoload.php',
            APPPATH . 'libraries/vendor/autoload.php'
        ];
        
        $phpspreadsheet_loaded = false;
        foreach ($autoload_paths as $path) {
            if (file_exists($path)) {
                require_once($path);
                $phpspreadsheet_loaded = true;
                break;
            }
        }
        
        if (!$phpspreadsheet_loaded || !class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // Fallback to CSV template
            $this->download_university_csv_template();
            return;
        }
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $headers = [
            'Name',                          // name
            'Email',                         // email
            'Phone',                         // contact_no
            'Date of Birth',                 // university_student_dob (YYYY-MM-DD)
            'Age',                           // university_age
            'Address',                       // address
            'City',                          // city
            'Postal Code',                   // zip
            'Country',                       // country_name
            'University ID',                 // university_id
            'Internal ID',                   // university_internal_id
            'University',                    // university_name
            'Program',                       // university_program
            'Year of Study',                 // university_year_of_study
            'Bank Name',                     // bank_name
            'Account Number',                // university_bank_account_no
            'Branch Number',                 // university_bank_branch_number
            'Branch Info',                   // university_bank_branch_info
            'Father Name',                   // university_father_name
            'Father Income',                 // university_father_income
            'Mother Name',                   // university_mother_name
            'Mother Income',                 // university_mother_income
            'Guardian Name',                 // university_guardian_name
            'Guardian Income',               // university_guardian_income
            'Background Information',        // background_info
            'Sponsorship Start',             // university_sponsorship_start_date (YYYY-MM-DD)
            'Sponsorship End',               // university_sponsorship_end_date (YYYY-MM-DD)
            'Introduced By',                 // university_introducedby
            'Introducer Phone',              // university_introducedph
            'Sponsor ID',                    // sponsor_id
            'Internal Comment',              // internal_comment
            'External Comment'               // external_comment
        ];
        
        $sheet->fromArray($headers, null, 'A1');
        
        // Add sample data
        $sampleData = [
            'Jane Doe',                      // Name
            'jane.doe@university.edu',       // Email
            '+94771234567',                  // Phone
            '2000-01-15',                    // Date of Birth
            '24',                            // Age
            '123 University Avenue',         // Address
            'Colombo',                       // City
            '00100',                         // Postal Code
            'Sri Lanka',                     // Country
            'UNI001',                        // University ID
            'UNIV001',                       // Internal ID
            'University of Colombo',         // University
            'Computer Science',              // Program
            '2Y1S',                          // Year of Study
            'Bank of Ceylon',                // Bank Name
            '1234567890',                    // Account Number
            '001',                           // Branch Number
            'Colombo Branch',                // Branch Info
            'Father Name',                   // Father Name
            '50000',                         // Father Income
            'Mother Name',                   // Mother Name
            '30000',                         // Mother Income
            'Guardian Name',                 // Guardian Name
            '40000',                         // Guardian Income
            'Student background info',       // Background Information
            '2024-01-01',                    // Sponsorship Start
            '2026-12-31',                    // Sponsorship End
            'Professor Name',                // Introduced By
            '+94771234568',                  // Introducer Phone
            '1',                             // Sponsor ID
            'Internal notes',                // Internal Comment
            'External notes'                 // External Comment
        ];
        
        $sheet->fromArray($sampleData, null, 'A2');
        
        // Style the header
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => 'E8E8E8']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ];
        
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);
        
        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Add instructions sheet
        $instructionsSheet = $spreadsheet->createSheet();
        $instructionsSheet->setTitle('Instructions');
        
        $instructions = [
            ['University Students Import Template - Instructions'],
            [''],
            ['REQUIRED FIELDS:'],
            ['- Name: Student\'s full name (required)'],
            [''],
            ['OPTIONAL FIELDS:'],
            ['- Email: Student\'s email address'],
            ['- Phone: Contact number'],
            ['- Date of Birth: Format YYYY-MM-DD (e.g., 2000-01-15)'],
            ['- Year of Study: 1Y1S, 1Y2S, 2Y1S, 2Y2S, 3Y1S, 3Y2S, 4Y1S, 4Y2S, 5Y1S, 5Y2S'],
            ['- All other fields are optional'],
            [''],
            ['NOTES:'],
            ['- Students with existing email/internal ID will be updated'],
            ['- Universities, programs, and banks will be created if they don\'t exist'],
            ['- Remove the sample data before importing'],
            ['- Maximum file size: 20MB']
        ];
        
        $instructionsSheet->fromArray($instructions, null, 'A1');
        $instructionsSheet->getColumnDimension('A')->setWidth(50);
        
        // Set active sheet back to template
        $spreadsheet->setActiveSheetIndex(0);
        
        $filename = 'university_students_template_' . date('Y-m-d') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Fallback CSV template download for university students
     */
    private function download_university_csv_template()
    {
        $filename = 'university_students_template_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        $headers = [
            'Name', 'Email', 'Phone', 'Date of Birth', 'Address', 'City', 'Postal Code', 'Country',
            'University ID', 'Internal ID', 'University', 'Program', 'Year of Study',
            'Bank Name', 'Account Number', 'Branch Number', 'Branch Info',
            'Father Name', 'Father Income', 'Mother Name', 'Mother Income', 
            'Guardian Name', 'Guardian Income', 'Background Information',
            'Sponsorship Start', 'Sponsorship End', 'Introduced By', 'Introducer Phone',
            'Internal Comment', 'External Comment'
        ];
        
        fputcsv($output, $headers);
        
        // Sample data
        $sample = [
            'Jane Doe', 'jane.doe@university.edu', '+94771234567', '2000-01-15', '123 University Avenue',
            'Colombo', '00100', 'Sri Lanka', 'UNI001', 'UNIV001', 'University of Colombo', 'Computer Science',
            '2Y1S', 'Bank of Ceylon', '1234567890', '001', 'Colombo Branch',
            'Father Name', '50000', 'Mother Name', '30000', 'Guardian Name', '40000',
            'Student background information', '2024-01-01', '2026-12-31', 'Professor Name', '+94771234568',
            'Internal notes', 'External notes'
        ];
        
        fputcsv($output, $sample);
        
        fclose($output);
        exit;
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

    public function display_s_profile_photo($student_id)
    {
        $student_id = (int) $student_id;

        $photo_data = $this->school_model->get_profile_photo($student_id);
        
        if ($photo_data && strlen($photo_data) > 0) {
            // Detect MIME type
            $mime_type = 'image/jpeg'; // Default
            
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
                // Detect by signature
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
            
            // Output the photo
            header('Content-Type: ' . $mime_type);
            header('Content-Length: ' . strlen($photo_data));
            header('Cache-Control: max-age=3600');
            header('Pragma: public');
            
            echo $photo_data;
        }
        // NO PHOTO FOUND - Generate initial avatar with first letter
        // $this->generate_initial_avatar($student_id);
        exit;
    }




        /**
     * Display profile photo for university student
     * Returns transparent pixel if photo not found (no 404 error)
     */
    public function display_profile_photo($student_id)
    {
        $student_id = (int)$student_id;
        $photo_data = $this->university_model->get_profile_photo($student_id);
        
        if ($photo_data && strlen($photo_data) > 0) {
            // Detect MIME type
            $mime_type = 'image/jpeg'; // Default
            
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
                // Detect by signature
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
            
            // Output the photo
            header('Content-Type: ' . $mime_type);
            header('Content-Length: ' . strlen($photo_data));
            header('Cache-Control: max-age=3600');
            header('Pragma: public');
            
            echo $photo_data;
            exit;
        }
        
        // NO PHOTO FOUND - Generate initial avatar with first letter
        // $this->generate_initial_avatar($student_id);
        exit;
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
        if ($student_id <= 0) { 
            set_alert('danger', 'Missing student ID.'); 
            redirect(admin_url('student_sponsor_portal/university_students')); 
        }

        $s = $this->university_model->get_by_id($student_id);
        if (!$s) { 
            set_alert('danger', 'Student not found.'); 
            redirect(admin_url('student_sponsor_portal/university_students')); 
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
            redirect(admin_url('student_sponsor_portal/university_student_form/' . $student_id));
        }

        // FIX: Cast to int or null properly
        $existing_staff_id = isset($s['staff_id']) && $s['staff_id'] !== '' && $s['staff_id'] !== null 
                            ? (int)$s['staff_id'] 
                            : null;

        $staff_id = $this->create_simple_staff($data, $existing_staff_id);
        if (!$staff_id) {
            set_alert('danger', 'Failed to create/update staff account.');
            redirect(admin_url('student_sponsor_portal/university_student_form/' . $student_id));
        }

        // Grant portal permissions
        $this->grant_portal_permissions($staff_id);

        // Update student record - FIXED: Check which column exists and use appropriate field
        $tbl = db_prefix() . 'university_students';
        $update_data = ['staff_id' => (int)$staff_id];
        
        // Check which active column exists in your table
        if ($this->db->field_exists('active', $tbl)) {
            $update_data['active'] = (int)$data['active'];
        } elseif ($this->db->field_exists('staff_active', $tbl)) {
            $update_data['staff_active'] = (int)$data['active'];
        }
        // If neither exists, just update staff_id
        
        $this->db->where('id', (int)$student_id)->update($tbl, $update_data);

        set_alert('success', 'Portal access granted' . ($data['staff_password'] !== '' ? '. Password set as provided.' : '. Existing password kept or auto-generated.'));
        redirect(admin_url('student_sponsor_portal/university_student_form/' . $student_id));
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
            $current_student = $this->is_school_student_user();
            if ($current_student) {
                redirect(admin_url('student_sponsor_portal/school_student_form/' . $current_student->id));
                return;
            }
            if (!has_permission('student_sponsor_portal', '', 'view')) access_denied('student_sponsor_portal');

            $data['title']    = 'Sponsor Management';
            $data['sponsors'] = $this->sponsor_model->get_all();
            $this->load->view('student_sponsor_portal/sponsors_list', $data);
        }

    // Add these methods to your Student_sponsor_portal.php controller

        /**
         * Get available students for sponsor selection
         */
        public function get_available_students()
        {
            header('Content-Type: application/json');
            
            if (!has_permission('student_sponsor_portal', '', 'view')) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }

            $sponsor_id = (int)($this->input->get('sponsor_id') ?: $this->input->post('sponsor_id'));
            
            try {
                $students_data = $this->sponsor_model->get_available_students($sponsor_id);
                
                echo json_encode([
                    'success' => true,
                    'data' => $students_data
                ]);
                
            } catch (Exception $e) {
                log_message('error', 'Error getting available students: ' . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => 'Error loading students: ' . $e->getMessage()
                ]);
            }
        }

        /**
         * Updated sponsor_form method to handle student selection
         */
    public function sponsor_form($sponsor_id = null)
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        if ($this->input->method() === 'post') {
            // CRITICAL DEBUG: Log ALL incoming POST data
            $all_post = $this->input->post(null, true);
            log_message('debug', '=== SPONSOR FORM POST DATA START ===');
            log_message('debug', 'Raw POST data: ' . print_r($all_post, true));
            log_message('debug', '=== SPONSOR FORM POST DATA END ===');

            $id = $this->in_int('sponsor_id', 0);
            $post_data = $this->input->post(null, true);
            
            unset($post_data['sponsor_id']);

            // DEBUG: Log what we're about to send to model
            log_message('debug', '=== DATA BEING SENT TO MODEL ===');
            log_message('debug', 'Sponsor ID: ' . $id);
            log_message('debug', 'Email value: ' . ($post_data['email'] ?? 'NOT SET'));
            log_message('debug', 'Name value: ' . ($post_data['name'] ?? 'NOT SET'));
            log_message('debug', 'Contact_no value: ' . ($post_data['contact_no'] ?? 'NOT SET'));
            log_message('debug', 'Full post_data: ' . print_r($post_data, true));
            log_message('debug', '=== END DATA TO MODEL ===');

            // Handle student selections
            $school_students = [];
            $university_students = [];
            
            if (!empty($post_data['selected_school_students'])) {
                $selected_school = json_decode($post_data['selected_school_students'], true);
                if (is_array($selected_school)) {
                    $school_students = $selected_school;
                }
            }
            
            if (!empty($post_data['selected_university_students'])) {
                $selected_university = json_decode($post_data['selected_university_students'], true);
                if (is_array($selected_university)) {
                    $university_students = $selected_university;
                }
            }

            $post_data['selected_school_students'] = $school_students;
            $post_data['selected_university_students'] = $university_students;

            if ($id === 0) {
                if (!has_permission('student_sponsor_portal', '', 'create')) {
                    access_denied('student_sponsor_portal');
                }
                
                $newId = $this->sponsor_model->add($post_data);
                if ($newId) {
                    $this->sponsor_model->update_sponsored_students($newId, $school_students, $university_students);
                    set_alert('success', 'Sponsor registered successfully');
                    redirect(admin_url('student_sponsor_portal/sponsors'));
                }
                set_alert('danger', 'Error creating sponsor');
            } else {
                if (!has_permission('student_sponsor_portal', '', 'edit')) {
                    access_denied('student_sponsor_portal');
                }
                
                log_message('debug', '=== CALLING MODEL UPDATE ===');
                $ok = $this->sponsor_model->update($post_data, $id);
                log_message('debug', '=== UPDATE RESULT: ' . ($ok ? 'TRUE' : 'FALSE') . ' ===');
                
                if ($ok) {
                    $this->sponsor_model->update_sponsored_students($id, $school_students, $university_students);
                    
                    // DEBUG: Verify the update by fetching the record
                    $updated_sponsor = $this->sponsor_model->get_by_id($id);
                    log_message('debug', '=== VERIFICATION AFTER UPDATE ===');
                    log_message('debug', 'Email in DB after update: ' . ($updated_sponsor['email'] ?? 'NULL'));
                    log_message('debug', 'Name in DB after update: ' . ($updated_sponsor['name'] ?? 'NULL'));
                    log_message('debug', '=== END VERIFICATION ===');
                    
                    set_alert('success', 'Sponsor updated successfully');
                    redirect(admin_url('student_sponsor_portal/sponsors'));
                }
                
                log_message('error', 'Update returned FALSE for sponsor ID: ' . $id);
                set_alert('danger', 'Error updating sponsor. Please check the logs.');
            }
        }

        // Load form data...
        $data = [];
        $data['title'] = 'Register Sponsor';
        $data['sponsor'] = null;

        if ($sponsor_id) {
            $row = $this->sponsor_model->get_by_id((int)$sponsor_id);
            if (!$row) {
                set_alert('danger', 'Sponsor not found');
                redirect(admin_url('student_sponsor_portal/sponsors'));
            }
            $data['sponsor'] = $row;
            $data['title'] = 'Edit Sponsor';
        }

        $data['banks'] = $this->sponsor_model->get_banks();
        $data['countries'] = $this->sponsor_model->get_countries();
        
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

        /**
         * Search students (for AJAX autocomplete)
         */
        public function search_students_for_sponsor()
        {
            if (!has_permission('student_sponsor_portal', '', 'view')) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }

            $query = trim($this->input->get('q') ?? '');
            $type = trim($this->input->get('type') ?? ''); // 'school' or 'university'
            $limit = min(20, max(5, (int)($this->input->get('limit') ?? 10)));

            $results = [];

            try {
                // Search school students
                if (empty($type) || $type === 'school') {
                    if ($this->db->table_exists(db_prefix() . 'school_students')) {
                        $this->db->select('
                            school_internal_id as internal_id,
                            name,
                            school_grade as grade,
                            city,
                            \'school\' as type
                        ');
                        $this->db->from(db_prefix() . 'school_students');
                        $this->db->where('school_internal_id IS NOT NULL');
                        $this->db->where('school_internal_id !=', '');
                        
                        if ($query) {
                            $this->db->group_start();
                            $this->db->like('name', $query);
                            $this->db->or_like('school_internal_id', $query);
                            $this->db->group_end();
                        }
                        
                        $this->db->order_by('name', 'ASC');
                        $this->db->limit($limit);
                        
                        $school_results = $this->db->get()->result_array();
                        
                        foreach ($school_results as $student) {
                            $results[] = [
                                'internal_id' => $student['internal_id'],
                                'name' => $student['name'],
                                'display' => $student['name'] . ' (Grade ' . $student['grade'] . ') - ' . ($student['city'] ?? 'N/A'),
                                'type' => 'school',
                                'grade' => 'Grade ' . $student['grade'],
                                'location' => $student['city'] ?? 'N/A'
                            ];
                        }
                    }
                }

                // Search university students
                if (empty($type) || $type === 'university') {
                    if ($this->db->table_exists(db_prefix() . 'university_students')) {
                        $this->db->select('
                            us.university_internal_id as internal_id,
                            us.name,
                            us.university_year_of_study as year_of_study,
                            us.city,
                            un.name as university_name,
                            \'university\' as type
                        ');
                        $this->db->from(db_prefix() . 'university_students us');
                        $this->db->join(db_prefix() . 'university_name un', 'un.id = us.university_name_id', 'left');
                        $this->db->where('us.university_internal_id IS NOT NULL');
                        $this->db->where('us.university_internal_id !=', '');
                        
                        if ($query) {
                            $this->db->group_start();
                            $this->db->like('us.name', $query);
                            $this->db->or_like('us.university_internal_id', $query);
                            $this->db->group_end();
                        }
                        
                        $this->db->order_by('us.name', 'ASC');
                        $this->db->limit($limit);
                        
                        $university_results = $this->db->get()->result_array();
                        
                        foreach ($university_results as $student) {
                            $results[] = [
                                'internal_id' => $student['internal_id'],
                                'name' => $student['name'],
                                'display' => $student['name'] . ' (Year ' . $student['year_of_study'] . ') - ' . ($student['university_name'] ?? 'N/A'),
                                'type' => 'university',
                                'grade' => 'Year ' . $student['year_of_study'],
                                'university' => $student['university_name'] ?? 'N/A',
                                'location' => $student['city'] ?? 'N/A'
                            ];
                        }
                    }
                }

                echo json_encode([
                    'success' => true,
                    'results' => $results
                ]);
                
            } catch (Exception $e) {
                log_message('error', 'Error searching students: ' . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => 'Error searching students'
                ]);
            }
        }

        /**
         * Get student details by internal ID (for modals/details view)
         */
        public function get_student_details()
        {
            if (!has_permission('student_sponsor_portal', '', 'view')) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }

            $internal_id = trim($this->input->get('internal_id') ?? '');
            $type = trim($this->input->get('type') ?? '');

            if (empty($internal_id) || empty($type)) {
                echo json_encode(['success' => false, 'message' => 'Missing parameters']);
                return;
            }

            try {
                $student = null;

                if ($type === 'school' && $this->db->table_exists(db_prefix() . 'school_students')) {
                    $this->db->select('*');
                    $this->db->from(db_prefix() . 'school_students');
                    $this->db->where('school_internal_id', $internal_id);
                    $student = $this->db->get()->row_array();
                    
                    if ($student) {
                        $student['type'] = 'school';
                        $student['grade_display'] = 'Grade ' . ($student['school_grade'] ?? 'N/A');
                    }
                    
                } elseif ($type === 'university' && $this->db->table_exists(db_prefix() . 'university_students')) {
                    $this->db->select('us.*, un.name as university_name, up.name as program_name');
                    $this->db->from(db_prefix() . 'university_students us');
                    $this->db->join(db_prefix() . 'university_name un', 'un.id = us.university_name_id', 'left');
                    $this->db->join(db_prefix() . 'university_program up', 'up.id = us.university_program_id', 'left');
                    $this->db->where('us.university_internal_id', $internal_id);
                    $student = $this->db->get()->row_array();
                    
                    if ($student) {
                        $student['type'] = 'university';
                        $student['grade_display'] = 'Year ' . ($student['university_year_of_study'] ?? 'N/A');
                    }
                }

                if ($student) {
                    echo json_encode([
                        'success' => true,
                        'student' => $student
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Student not found'
                    ]);
                }
                
            } catch (Exception $e) {
                log_message('error', 'Error getting student details: ' . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => 'Error loading student details'
                ]);
            }
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

        /**
     * Simple method to grant portal access by inserting staff permissions directly
     */
    private function grant_portal_permissions($staff_id)
    {
        $permissions = [
            ['staff_id' => $staff_id, 'feature' => 'student_sponsor_portal', 'capability' => 'view'],
            ['staff_id' => $staff_id, 'feature' => 'student_sponsor_portal', 'capability' => 'create'],
            ['staff_id' => $staff_id, 'feature' => 'student_sponsor_portal', 'capability' => 'edit'],
        ];

        foreach ($permissions as $perm) {
            // Check if permission already exists
            $exists = $this->db->where($perm)->get(db_prefix().'staff_permissions')->row();
            if (!$exists) {
                $this->db->insert(db_prefix().'staff_permissions', $perm);
            }
        }
    }

    /**
     * Simplified sponsor access grant
     */
    private function create_simple_staff(array $data, ?int $existing_staff_id): ?int
    {
        $staff_tbl = db_prefix().'staff';

        $email     = trim((string)$data['staff_email']);
        $firstname = trim((string)$data['staff_firstname']);
        $lastname  = trim((string)$data['staff_lastname']);
        $password  = trim((string)$data['staff_password']);
        $active    = !empty($data['active']) ? 1 : 0;

        if ($email === '' || $firstname === '') return null;

        $payload = [
            'email'     => $email,
            'firstname' => $firstname,
            'lastname'  => $lastname,
            'admin'     => 0,
            'active'    => (int)$active,
        ];

        if ($password !== '') {
            $payload['password'] = app_hash_password($password);
        }

        // Update existing staff
        if ($existing_staff_id) {
            $this->db->where('staffid', (int)$existing_staff_id)->update($staff_tbl, $payload);
            return (int)$existing_staff_id;
        }

        // Check if staff with this email already exists
        $existing = $this->db->get_where($staff_tbl, ['email' => $email])->row();
        if ($existing) {
            $this->db->where('staffid', (int)$existing->staffid)->update($staff_tbl, $payload);
            return (int)$existing->staffid;
        }

        // Create new staff with auto-generated password if none provided
        if (empty($payload['password'])) {
            $auto = bin2hex(random_bytes(4));
            $payload['password'] = app_hash_password($auto);
        }

        $this->db->insert($staff_tbl, $payload);
        return (int)$this->db->insert_id();
    }

    public function grant_sponsor_access($sponsor_id = null)
    {
        if (!has_permission('student_sponsor_portal', '', 'edit')) access_denied('student_sponsor_portal');

        $sponsor_id = $this->resolve_id('sponsor_id', 4);
        if ($sponsor_id <= 0) { 
            set_alert('danger', 'Missing sponsor ID.'); 
            redirect(admin_url('student_sponsor_portal/sponsors')); 
        }

        $s = $this->sponsor_model->get_by_id($sponsor_id);
        if (!$s) { 
            set_alert('danger', 'Sponsor not found.'); 
            redirect(admin_url('student_sponsor_portal/sponsors')); 
        }

        $data = [
            'staff_email'     => $this->in('staff_email', $s['email'] ?? ''),
            'staff_firstname' => $this->in('staff_firstname', $s['name'] ?? 'Sponsor'),
            'staff_lastname'  => $this->in('staff_lastname', ''),
            'staff_password'  => $this->in('staff_password', ''),
            'active'          => $this->in_bool('staff_active'),
        ];

        if ($data['staff_email'] === '') {
            set_alert('danger', 'Login email is required.');
            redirect(admin_url('student_sponsor_portal/sponsor_form/' . $sponsor_id));
        }

        // FIX: Cast to int or null properly
        $existing_staff_id = isset($s['staff_id']) && $s['staff_id'] !== '' && $s['staff_id'] !== null 
                            ? (int)$s['staff_id'] 
                            : null;

        $staff_id = $this->create_simple_staff($data, $existing_staff_id);
        if (!$staff_id) {
            set_alert('danger', 'Failed to create/update staff account.');
            redirect(admin_url('student_sponsor_portal/sponsor_form/' . $sponsor_id));
        }

        // Grant portal permissions
        $this->grant_portal_permissions($staff_id);

        // Update sponsor record
        $this->db->where('id', (int)$sponsor_id)->update(db_prefix().'sponsor_records', [
            'staff_id' => (int)$staff_id,
            'active' => (int)$data['active']
        ]);

        set_alert('success', 'Portal access granted' . ($data['staff_password'] !== '' ? '. Password set as provided.' : '. Existing password kept or auto-generated.'));
        redirect(admin_url('student_sponsor_portal/sponsor_form/' . $sponsor_id));
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
            $current_student = $this->is_school_student_user();
    if ($current_student) {
        redirect(admin_url('student_sponsor_portal/school_student_form/' . $current_student->id));
        return;
    }
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
        $this->load->model('student_sponsor_portal/sponsor_transactions_model');
        
        if ($id && is_numeric($id)) {
            // Load transaction data
            $txn = $this->sponsor_transactions_model->get($id);
            
            if (!$txn) {
                show_404();
            }
            
            // Load payments data (your existing code)
            if (!isset($payments)) {
                $this->db->where('transaction_id', (int)$txn->id);
                $this->db->order_by('payment_date', 'DESC');
                $payments = $this->db->get(db_prefix().'sponsor_payments')->result();
            }
            
            // Load email template - ADD THIS LINE
            $email_template = $this->sponsor_transactions_model->get_due_email_template($id);
            
            // Debug: Add this temporarily to see if it's working
            // Remove this after testing
            if ($email_template) {
                log_message('debug', 'Email template loaded: ' . print_r($email_template, true));
            } else {
                log_message('debug', 'Email template is NULL for transaction ID: ' . $id);
            }
            
            // Check for editing payment (your existing code)
            $payment_to_edit = null;
            if (isset($_GET['edit_payment'])) {
                $edit_id = (int) $_GET['edit_payment'];
                $payment_to_edit = $this->db->where('id', $edit_id)->get(db_prefix().'sponsor_payments')->row();
            }
            
            // Pass data to view
            $data = [
                'txn' => $txn,
                'payments' => $payments,
                'email_template' => $email_template,  // MAKE SURE THIS IS HERE
                'payment_to_edit' => $payment_to_edit,
                'title' => 'Transaction Details'
            ];
            
        } else {
            // New transaction
            $data = [
                'txn' => null,
                'payments' => [],
                'email_template' => null,
                'payment_to_edit' => null,
                'title' => 'New Transaction'
            ];
        }
        
        $this->load->view('student_sponsor_portal/transactions/form', $data);
    }
    public function send_test_email($id)
    {
        $this->load->model('student_sponsor_portal/sponsor_transactions_model');
        
        // Debug: Check if transaction exists
        $txn = $this->sponsor_transactions_model->get($id);
        if (!$txn) {
            set_alert('danger', 'Transaction not found!');
            redirect(admin_url('student_sponsor_portal/transactions'));
            return;
        }
        
        // Debug: Check if sponsor has email
        $sponsor = $this->db->select('email, name')->where('id', $txn->sponsor_id)->get(db_prefix().'sponsor_records')->row();
        if (!$sponsor) {
            set_alert('danger', 'Sponsor record not found!');
            redirect(admin_url('student_sponsor_portal/transaction/'.$id.'?tab=payments'));
            return;
        }
        
        if (empty($sponsor->email)) {
            set_alert('danger', 'Sponsor "' . $sponsor->name . '" does not have an email address configured. Please update the sponsor record.');
            redirect(admin_url('student_sponsor_portal/transaction/'.$id.'?tab=payments'));
            return;
        }
        
        // Debug: Check if emails_model exists
        if (!class_exists('Emails_model')) {
            $this->load->model('emails_model');
        }
        
        // Attempt to send
        $result = $this->sponsor_transactions_model->send_due_email($id);
        
        if ($result) {
            set_alert('success', 'Email sent successfully to: ' . $sponsor->email);
            
            // Update the due_reminder_sent flag
            $this->db->where('id', $id);
            $this->db->update(db_prefix().'sponsor_transactions', [
                'due_reminder_sent' => 1,
                'scheduled_due_reminder_date' => null, // Clear the scheduled date
            ]);
        } else {
            set_alert('danger', 'Failed to send email to: ' . $sponsor->email . '. Check email configuration and logs.');
        }
        
        redirect(admin_url('student_sponsor_portal/transaction/'.$id.'?tab=payments'));
    }


    public function send_payment_email($payment_id)
    {
        $CI =& get_instance();
        $CI->load->model('emails_model');
        $this->load->model('student_sponsor_portal/sponsor_transactions_model');

        // Step 1: Fetch the payment record
        $pay_tbl = $this->sponsor_transactions_model->get_pay_tbl(); // Access table name via model method
        $payment = $this->db->select('*')->where('id', $payment_id)->get($pay_tbl)->row();
        
        if (!$payment) {
            log_message('error', 'Payment not found for ID: ' . $payment_id);
            return false;
        }

        // Step 2: Fetch the sponsor transaction record based on the transaction ID
        $txn = $this->sponsor_transactions_model->get($payment->transaction_id);
        if (!$txn) {
            log_message('error', 'Transaction not found for payment: ' . $payment_id);
            return false;
        }

        // Step 3: Get sponsor details (email and name)
        $sponsor = $this->db->select('email, name')->where('id', $txn->sponsor_id)->get(db_prefix().'sponsor_records')->row();
        if (!$sponsor) {
            log_message('error', 'Sponsor not found for transaction ID: ' . $txn->sponsor_id);
            return false;
        }

        // Step 4: Get student details (name)
        $student_name = '';
        if ($txn->school_student_id) {
            $student = $this->db->select('name')->where('id', $txn->school_student_id)->get(db_prefix().'school_students')->row();
            $student_name = $student ? $student->name : 'Student';
        } elseif ($txn->university_student_id) {
            $student = $this->db->select('name')->where('id', $txn->university_student_id)->get(db_prefix().'university_students')->row();
            $student_name = $student ? $student->name : 'Student';
        }

        // Step 5: Prepare the email body with detailed payment information
        $subject = "Payment Details for " . $sponsor->name;

        // Calculate remaining amount
        $remaining_amount = $txn->total_amount - $txn->amount_paid;

        $body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <p style="color: #4a90e2; font-size: 18px; margin-bottom: 20px;">Hello ' . $sponsor->name . ',</p>
            
            <p style="color: #333; line-height: 1.6; margin-bottom: 20px;">
                We have received a new payment for the sponsorship of <strong>' . $student_name . '</strong>. Below are the details:
            </p>
            
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0; background-color: #f9f9f9;">
                <thead>
                    <tr style="background-color: #e8e8e8;">
                        
                        <th style="border: 1px solid #ddd; padding: 12px; text-align: left; font-weight: bold;">Payment Amount</th>
                        <th style="border: 1px solid #ddd; padding: 12px; text-align: left; font-weight: bold;">Remaining Amount</th>
                        <th style="border: 1px solid #ddd; padding: 12px; text-align: left; font-weight: bold;">Payment Date</th>
                        <th style="border: 1px solid #ddd; padding: 12px; text-align: left; font-weight: bold;">Payment Method</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 12px;">' . $txn->transaction_id . '</td>
                        <td style="border: 1px solid #ddd; padding: 12px;">' . number_format($payment->amount, 2) . ' ' . $payment->currency . '</td>
                        <td style="border: 1px solid #ddd; padding: 12px;">' . number_format($remaining_amount, 2) . ' ' . $payment->currency . '</td>
                        <td style="border: 1px solid #ddd; padding: 12px;">' . date('m/d/Y', strtotime($payment->payment_date)) . '</td>
                        <td style="border: 1px solid #ddd; padding: 12px;">' . ($payment->note ?: 'N/A') . '</td>
                    </tr>
                </tbody>
            </table>
            
            <p style="color: #333; line-height: 1.6; margin-top: 20px;">
                Thank you for your continued support of <strong>' . get_option('companyname') . '</strong>.<br>
                If you have any questions, feel free to contact us.
            </p>
        </div>';

        // Step 6: Attempt to send the email
        log_message('debug', 'Attempting to send email to: ' . $sponsor->email);
        
        $result = $CI->emails_model->send_simple_email(
            $sponsor->email,
            $subject,
            $body
        );
        
        if ($result) {
            log_message('debug', 'Payment email sent successfully to: ' . $sponsor->email);
        } else {
            log_message('error', 'Failed to send payment email to: ' . $sponsor->email);
        }

        return $result;
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

        // Insert payment record
        $ok = $this->db->insert(db_prefix().'sponsor_payments', $row);

        if (!$ok) {
            set_alert('warning', 'Failed to add payment.');
            redirect(admin_url('student_sponsor_portal/transaction/'.$transaction_id.'?tab=payments'));
        }

        // Now, fetch the payment record using the transaction_id and get the payment_id
        $payment = $this->db->where('transaction_id', $transaction_id)
                            ->order_by('payment_date', 'DESC')  // Ensure we get the latest payment first
                            ->get(db_prefix().'sponsor_payments')->row();

        if ($payment) {
            // Pass the correct payment_id to send_payment_email
            $this->send_payment_email($payment->id);  // Send the email with the correct payment_id
        }

        // Update the transaction with the new payment details
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

        // Update transaction record with new payment status
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

        public function email_preview($id)
    {
        $this->load->model('student_sponsor_portal/sponsor_transactions_model');
        $template = $this->sponsor_transactions_model->get_due_email_template($id);

        $data['template'] = $template;
        $data['title'] = 'Email Preview';
        $this->load->view('student_sponsor_portal/transaction/'.$transaction_id, $data);
    }

        /* =================================================================================== */
        /* =====================                  HELPER METHODS             =================== */
        /* =================================================================================== */

    /**
     * Get countries with proper phone code field mapping
     */
    private function _get_countries()
    {
        if ($this->db->table_exists(db_prefix() . 'countries')) {
            return $this->db->select('
                country_id as id, 
                short_name as name, 
                calling_code as phone_code,
                calling_code
            ')
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


        public function cron_due_reminders()
    {
        // Require a secret token so random people can't run it
        $token = $this->input->get('token');
        $expected = get_option('cron_key'); // or set your own option, or hardcode e.g. 'MY_STRONG_SECRET'
        if (!$expected || $token !== $expected) {
            show_error('Unauthorized', 401);
        }

        $this->load->model('student_sponsor_portal/sponsor_transactions_model');
        $res = $this->sponsor_transactions_model->run_due_reminder_cron();

        header('Content-Type: text/plain');
        echo 'OK ' . date('Y-m-d H:i:s') . PHP_EOL;
        echo 'sent_before=' . $res['sent_before'] . PHP_EOL;
        echo 'sent_due_day=' . $res['sent_due_day'] . PHP_EOL;
    }
    /**
     * Simplified process import file - removes unnecessary complexity
     */
    private function process_import_file($file_path, $file_ext)
    {
        log_message('debug', 'School Import: Processing file: ' . $file_path . ' (.' . $file_ext . ')');
        
        if (!file_exists($file_path) || !is_readable($file_path)) {
            log_message('error', 'School Import: Cannot read upload file: ' . $file_path);
            return ['success' => false, 'message' => 'Cannot read upload file'];
        }
        
        $added = 0;
        $updated = 0;
        $errors = 0;
        $error_details = [];
        
        try {
            // Load file data
            if (in_array($file_ext, ['xlsx', 'xls'])) {
                $rows = $this->load_excel_file($file_path);
            } else {
                $rows = $this->load_csv_file($file_path);
            }
            
            if (empty($rows)) {
                log_message('error', 'School Import: No data found in file: ' . $file_path);
                return ['success' => false, 'message' => 'No data found in file'];
            }
            
            // Get headers and create mapping
            $headers = array_shift($rows);
            if (empty($headers)) {
                log_message('error', 'School Import: No headers found in file: ' . $file_path);
                return ['success' => false, 'message' => 'No headers found in file'];
            }
            
            log_message('debug', 'School Import: Headers found - ' . json_encode($headers));
            
            // Clean and normalize headers
            $headers = array_map(function($h) {
                return trim(strtolower(str_replace([' ', '_', '-'], '_', $h)));
            }, $headers);
            
            log_message('debug', 'School Import: Cleaned Headers: ' . json_encode($headers));
            
            // Map headers to database fields
            $column_mapping = $this->create_column_mapping($headers);
            if (empty($column_mapping)) {
                log_message('error', 'School Import: No valid columns found in file: ' . $file_path);
                return ['success' => false, 'message' => 'No valid columns found. Please check your headers.'];
            }
            
            log_message('debug', 'School Import: Column mapping: ' . json_encode($column_mapping));
            
            $row_number = 1; // Start from 1 (header is row 0)
            
            // Process each data row
            foreach ($rows as $row) {
                $row_number++;
                
                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        log_message('debug', "School Import: Row {$row_number}: Skipping empty row");
                        continue;
                    }
                    
                    log_message('debug', "School Import: Row {$row_number}: Processing: " . json_encode(array_slice($row, 0, 3)));
                    
                    // Map the row data
                    $student_data = $this->map_row_data($row, $column_mapping);
                    log_message('debug', "School Import: Row {$row_number}: Mapped data: " . json_encode($student_data));
                    
                    // Validate required fields
                    if (empty($student_data['name'])) {
                        $errors++;
                        $error_details[] = "Row {$row_number}: Name is required";
                        log_message('warning', "School Import: Row {$row_number}: Validation failed: Name is required");
                        continue;
                    }
                    
                    // Validate age-grade if both present
                    if (!empty($student_data['school_grade']) && !empty($student_data['school_age'])) {
                        $validation = $this->school_model->validate_age_grade(
                            $student_data['school_grade'], 
                            $student_data['school_age'], 
                            $student_data['grade_mismatch_reason'] ?? null
                        );
                        
                        if (!$validation['valid']) {
                            $errors++;
                            $error_details[] = "Row {$row_number}: " . $validation['message'];
                            log_message('warning', "School Import: Row {$row_number}: Validation failed: " . $validation['message']);
                            continue;
                        }
                    }
                    
                    // Check for existing student
                    $existing_student = $this->find_existing_student($student_data);
                    
                    if ($existing_student) {
                        // Update existing student
                        log_message('debug', "School Import: Row {$row_number}: Updating existing student ID: " . $existing_student['id']);
                        
                        $result = $this->school_model->update($student_data, $existing_student['id']);
                        
                        if ($result) {
                            $updated++;
                            log_message('debug', "School Import: Row {$row_number}: Successfully updated student");
                        } else {
                            $errors++;
                            $error_details[] = "Row {$row_number}: Failed to update student";
                            log_message('error', "School Import: Row {$row_number}: Failed to update student");
                        }
                    } else {
                        // Add new student
                        log_message('debug', "School Import: Row {$row_number}: Creating new student");
                        
                        $result = $this->school_model->add($student_data);
                        
                        if ($result && !is_array($result)) {
                            $added++;
                            log_message('debug', "School Import: Row {$row_number}: Successfully added student with ID: " . $result);
                        } else {
                            $errors++;
                            $message = is_array($result) ? ($result['message'] ?? 'Unknown error') : 'Failed to add student';
                            $error_details[] = "Row {$row_number}: {$message}";
                            log_message('error', "School Import: Row {$row_number}: Failed to add student: " . $message);
                        }
                    }
                    
                } catch (Exception $e) {
                    $errors++;
                    $error_details[] = "Row {$row_number}: " . $e->getMessage();
                    log_message('error', "School Import: Row {$row_number} exception: " . $e->getMessage());
                }
            }
            
            log_message('info', "School Import completed. Added: {$added}, Updated: {$updated}, Errors: {$errors}");
            
            return [
                'success' => true,
                'added' => $added,
                'updated' => $updated,
                'errors' => $errors,
                'error_details' => $error_details
            ];
            
        } catch (Exception $e) {
            log_message('error', 'School Import processing error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Processing error: ' . $e->getMessage()];
        }
    }

    /**
     * Load Excel file data - FIXED VERSION
     */
    private function load_excel_file($file_path)
    {
        // Check if PHPSpreadsheet is available
        $autoload_paths = [
            FCPATH . 'vendor/autoload.php',
            APPPATH . 'third_party/vendor/autoload.php',
            APPPATH . 'libraries/vendor/autoload.php'
        ];
        
        $phpspreadsheet_loaded = false;
        foreach ($autoload_paths as $path) {
            if (file_exists($path)) {
                require_once($path);
                $phpspreadsheet_loaded = true;
                break;
            }
        }
        
        if (!$phpspreadsheet_loaded || !class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            throw new Exception('PHPSpreadsheet not available. Please install via Composer or use CSV format.');
        }
        
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
        $worksheet = $spreadsheet->getActiveSheet();
        
        // CRITICAL FIX: Use numeric indices, not column letters
        // FALSE = don't use column letters as keys
        $rows = $worksheet->toArray(null, true, true, false);
        
        log_message('debug', 'Excel Import: Loaded ' . count($rows) . ' rows');
        log_message('debug', 'Excel Import: First row (headers): ' . json_encode($rows[0] ?? []));
        
        return $rows;
    }

    /**
     * Load CSV file data
     */
    private function load_csv_file($file_path)
    {
        $rows = [];
        if (($handle = fopen($file_path, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rows[] = $data;
            }
            fclose($handle);
        } else {
            throw new Exception('Cannot open CSV file');
        }
        return $rows;
    }

    /**
     * Find existing student by email or internal ID
     */
    private function find_existing_student($student_data)
    {
        // Check by email first
        if (!empty($student_data['email'])) {
            $student = $this->db->where('email', $student_data['email'])
                            ->get(db_prefix() . 'school_students')
                            ->row_array();
            if ($student) {
                return $student;
            }
        }
        
        // Then check by internal ID
        if (!empty($student_data['school_internal_id'])) {
            $student = $this->db->where('school_internal_id', $student_data['school_internal_id'])
                            ->get(db_prefix() . 'school_students')
                            ->row_array();
            if ($student) {
                return $student;
            }
        }
        
        return null;
    }

    /**
     * Get or create country ID - simplified version
     */
    private function get_or_create_country_id($country_name)
    {
        if (empty($country_name)) {
            return null;
        }
        
        // Try to find existing country by short name
        $country = $this->db->where('short_name', $country_name)
                        ->get(db_prefix() . 'countries')
                        ->row_array();
        
        // If country exists, return its ID
        if ($country) {
            return (int)$country['country_id'];
        }
        
        // If country does not exist, create a new country
        $data = [
            'short_name' => $country_name,
            'calling_code' => '+1' // Default phone code, you might want to adjust this
        ];
        
        // Insert new country into the database
        $this->db->insert(db_prefix() . 'countries', $data);
        return (int)$this->db->insert_id(); // Return the ID of the newly inserted country
    }


    /**
     * Get or create bank ID - simplified version
     */
    private function get_or_create_bank_id($bank_name)
    {
        if (empty($bank_name)) {
            return null;
        }
        
        // Try to find existing bank by name
        $bank = $this->db->where('name', $bank_name)
                        ->get(db_prefix() . 'bank')
                        ->row_array();
        
        // If bank exists, return its ID
        if ($bank) {
            return (int)$bank['id'];
        }
        
        // If bank does not exist, create a new bank
        $this->db->insert(db_prefix() . 'bank', ['name' => $bank_name]);
        return (int)$this->db->insert_id(); // Return the ID of the newly inserted bank
    }




    public function my_sponsored_students()
    {
        $current_sponsor = $this->is_sponsor_user();
        if (!$current_sponsor) {
            access_denied('student_sponsor_portal');
        }

        try {
            $sponsor_id = (int)$current_sponsor->id;
            
            // Get filter parameters
            $student_type = $this->input->get('type'); // 'school', 'university', or 'all'
            $search = trim($this->input->get('search') ?? '');
            
            // Get students based on filter
            if ($student_type === 'school') {
                $students = $this->get_sponsor_students_by_type($sponsor_id, 'school');
            } elseif ($student_type === 'university') {
                $students = $this->get_sponsor_students_by_type($sponsor_id, 'university');
            } else {
                $students = $this->get_sponsor_students($sponsor_id);
            }

            // Apply search filter if provided
            if (!empty($search)) {
                $students = array_filter($students, function($student) use ($search) {
                    return stripos($student['name'], $search) !== false ||
                        stripos($student['school_internal_id'] ?? $student['university_internal_id'], $search) !== false ||
                        stripos($student['city'] ?? '', $search) !== false;
                });
            }

            // Get transaction summaries for each student
            foreach ($students as &$student) {
                $student_id_field = $student['student_type'] . '_student_id';
                
                $txn_summary = $this->db->select('
                    COUNT(*) as transaction_count,
                    SUM(total_amount) as total_amount,
                    SUM(amount_paid) as amount_paid,
                    SUM(balance_amount) as balance_amount
                ')
                ->where('sponsor_id', $sponsor_id)
                ->where($student_id_field, $student['id'])
                ->get(db_prefix() . 'sponsor_transactions')
                ->row_array();
                
                $student['transaction_summary'] = $txn_summary;
            }

            $data = [
                'title' => 'My Sponsored Students',
                'sponsor' => $current_sponsor,
                'students' => $students,
                'current_filter' => $student_type ?: 'all',
                'current_search' => $search,
                'is_sponsor_view' => true
            ];

            $this->load->view('student_sponsor_portal/sponsor_students_list', $data);

        } catch (Exception $e) {
            log_message('error', 'Error in my_sponsored_students: ' . $e->getMessage());
            show_error('Error loading students', 500);
        }
    }
    /**
     * Apply filters to student list
     */
    private function apply_student_filters($students, $filter, $search)
    {
        $filtered = $students;

        // Apply type filter
        if ($filter !== 'all') {
            $filtered = array_filter($filtered, function($student) use ($filter) {
                return ($student['student_type'] ?? '') === $filter;
            });
        }

        // Apply search filter
        if (!empty($search)) {
            $search_lower = mb_strtolower($search);
            $filtered = array_filter($filtered, function($student) use ($search_lower) {
                $searchable_fields = [
                    $student['name'] ?? '',
                    $student['school_internal_id'] ?? '',
                    $student['university_internal_id'] ?? '',
                    $student['city'] ?? '',
                    $student['email'] ?? '',
                    $student['contact_no'] ?? '',
                    $student['school_name'] ?? '',
                    $student['university_name'] ?? '',
                    $student['program_name'] ?? ''
                ];
                
                $searchable_text = mb_strtolower(implode(' ', $searchable_fields));
                return mb_strpos($searchable_text, $search_lower) !== false;
            });
        }

        // Re-index array to maintain proper indexing
        return array_values($filtered);
    }

    /**
     * Get current sponsor ID - implement based on your authentication
     * This is just an example - adjust based on your system
     */
    private function get_current_sponsor_id()
    {
        // Method 1: If sponsor is logged in as staff
        if (is_staff_logged_in()) {
            $staff_id = get_staff_user_id();
            
            // Find sponsor record linked to this staff member
            $this->load->model('sponsor_model');
            $this->db->where('staff_id', $staff_id);
            $sponsor = $this->db->get(db_prefix() . 'sponsor_records')->row();
            
            return $sponsor ? $sponsor->id : null;
        }
        
        // Method 2: If using session-based sponsor ID
        if ($this->session->userdata('sponsor_id')) {
            return (int)$this->session->userdata('sponsor_id');
        }
        
        // Method 3: If passing sponsor ID via URL parameter
        if ($this->input->get('sponsor_id')) {
            return (int)$this->input->get('sponsor_id');
        }
        
        return null;
    }

    /**
     * View individual student details
     */
    public function student_detail()
    {
        $type = $this->input->get('type', true); // 'school' or 'university'
        $id = (int)$this->input->get('id', true);
        
        if (!$type || !$id || !in_array($type, ['school', 'university'])) {
            show_404();
            return;
        }

        // Load appropriate model
        if ($type === 'school') {
            $this->load->model('school_model');
            $student = $this->school_model->get_by_id($id);
            $data['report_cards'] = $this->school_model->get_report_cards($id);
        } else {
            $this->load->model('university_model');
            $student = $this->university_model->get_by_id($id);
            $data['report_cards'] = $this->university_model->get_report_cards($id);
        }

        if (!$student) {
            set_alert('danger', 'Student not found');
            redirect(admin_url('student_sponsor_portal/my_sponsored_students'));
            return;
        }

        // Verify sponsor has access to this student
        $current_sponsor_id = $this->get_current_sponsor_id();
        if (!$current_sponsor_id || $student['sponsor_id'] != $current_sponsor_id) {
            set_alert('danger', 'Access denied');
            redirect(admin_url('student_sponsor_portal/my_sponsored_students'));
            return;
        }

        $data['student'] = $student;
        $data['student_type'] = $type;
        $data['title'] = 'Student Details - ' . $student['name'];

        $this->load->view('admin/sponsor_portal/student_detail', $data);
    }


    /**
     * Alternative method name for backward compatibility
     */
    public function get_sponsored_students()
    {
        return $this->get_sponsored_students_with_transactions();
    }





    // SCHOOL WORKING EXPORT 
    /**
     * Export sponsored students to Excel (for sponsors viewing their students)
     */
    public function export_sponsored_students()
    {
        // Check if user is a sponsor
        $current_sponsor = $this->is_sponsor_user();
        if (!$current_sponsor) {
            if (!has_permission('student_sponsor_portal', '', 'view')) {
                access_denied('student_sponsor_portal');
            }
        }

        try {
            // Get sponsor ID
            $sponsor_id = $current_sponsor ? (int)$current_sponsor->id : null;
            
            if (!$sponsor_id) {
                set_alert('danger', 'Sponsor information not found.');
                redirect(admin_url('student_sponsor_portal/my_sponsored_students'));
                return;
            }

            // Get filter parameters from URL
            $filter = $this->input->get('type') ?? 'all';
            $search = trim($this->input->get('search') ?? '');

            // Check if PHPSpreadsheet is available
            $autoload_paths = [
                FCPATH . 'vendor/autoload.php',
                APPPATH . 'third_party/vendor/autoload.php',
                APPPATH . 'libraries/vendor/autoload.php'
            ];
            
            $phpspreadsheet_loaded = false;
            foreach ($autoload_paths as $path) {
                if (file_exists($path)) {
                    require_once($path);
                    $phpspreadsheet_loaded = true;
                    break;
                }
            }
            
            if (!$phpspreadsheet_loaded || !class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                set_alert('danger', 'PHPSpreadsheet not available. Please install via Composer.');
                redirect(admin_url('student_sponsor_portal/my_sponsored_students'));
                return;
            }

            // Get students based on filter
            if ($filter === 'school') {
                $students = $this->get_sponsor_students_by_type($sponsor_id, 'school');
            } elseif ($filter === 'university') {
                $students = $this->get_sponsor_students_by_type($sponsor_id, 'university');
            } else {
                $students = $this->get_sponsor_students($sponsor_id);
            }

            // Apply search filter if provided
            if (!empty($search)) {
                $students = array_filter($students, function($student) use ($search) {
                    return stripos($student['name'], $search) !== false ||
                        stripos($student['school_internal_id'] ?? $student['university_internal_id'], $search) !== false ||
                        stripos($student['city'] ?? '', $search) !== false;
                });
            }

            if (empty($students)) {
                set_alert('warning', 'No students found to export.');
                redirect(admin_url('student_sponsor_portal/my_sponsored_students'));
                return;
            }

            // Get transaction summaries for each student
            foreach ($students as &$student) {
                $student_id_field = $student['student_type'] . '_student_id';
                
                $txn_summary = $this->db->select('
                    COUNT(*) as transaction_count,
                    SUM(total_amount) as total_amount,
                    SUM(amount_paid) as amount_paid,
                    SUM(balance_amount) as balance_amount,
                    MIN(sponsorship_start) as sponsorship_start,
                    MAX(sponsorship_end) as sponsorship_end,
                    MAX(next_payment_due) as next_payment_due,
                    payment_type
                ')
                ->where('sponsor_id', $sponsor_id)
                ->where($student_id_field, $student['id'])
                ->get(db_prefix() . 'sponsor_transactions')
                ->row_array();
                
                $student['transaction_summary'] = $txn_summary;
            }

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('My Sponsored Students');
            
            // Comprehensive headers
            $headers = [
                'Student Name', 'Type', 'Student ID', 'Grade/Year', 'Institution',
                'Program/Course', 'Email', 'Phone', 'City', 'Country',
                'Total Sponsored Amount', 'Amount Paid', 'Balance Amount',
                'Sponsorship Start', 'Sponsorship End', 'Next Payment Due',
                'Payment Type', 'Transaction Count', 'Status'
            ];
            
            $sheet->fromArray($headers, null, 'A1');
            
            // Add data rows
            $row = 2;
            foreach ($students as $student) {
                $txn = $student['transaction_summary'];
                
                $data = [
                    $student['name'] ?? '',
                    ucfirst($student['student_type'] ?? ''),
                    $student['student_type'] === 'school' 
                        ? ($student['school_internal_id'] ?? '') 
                        : ($student['university_internal_id'] ?? ''),
                    $student['student_type'] === 'school'
                        ? 'Grade ' . ($student['school_grade'] ?? 'N/A')
                        : 'Year ' . ($student['university_year_of_study'] ?? 'N/A'),
                    $student['student_type'] === 'school'
                        ? ($student['school_name'] ?? 'Not specified')
                        : ($student['university_name'] ?? 'Not specified'),
                    $student['student_type'] === 'university'
                        ? ($student['program_name'] ?? '')
                        : '',
                    $student['email'] ?? '',
                    $student['contact_no'] ?? '',
                    $student['city'] ?? '',
                    $student['country_name'] ?? '',
                    $txn['total_amount'] ?? 0,
                    $txn['amount_paid'] ?? 0,
                    $txn['balance_amount'] ?? 0,
                    $txn['sponsorship_start'] ?? '',
                    $txn['sponsorship_end'] ?? '',
                    $txn['next_payment_due'] ?? '',
                    $txn['payment_type'] ?? '',
                    $txn['transaction_count'] ?? 0,
                    !empty($txn['balance_amount']) && $txn['balance_amount'] > 0 ? 'Active' : 'Completed'
                ];
                
                $sheet->fromArray($data, null, 'A' . $row);
                $row++;
            }
            
            // Add totals row
            $totals = [
                'TOTALS:', '', '', '', '', '', '', '', '',
                'Total:',
                array_sum(array_column(array_column($students, 'transaction_summary'), 'total_amount')),
                array_sum(array_column(array_column($students, 'transaction_summary'), 'amount_paid')),
                array_sum(array_column(array_column($students, 'transaction_summary'), 'balance_amount')),
                '', '', '', '', '', ''
            ];
            $sheet->fromArray($totals, null, 'A' . $row);
            
            // Style header row
            $headerStyle = [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => '4472C4']
                ],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ];
            $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);
            
            // Style totals row
            $totalsStyle = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => 'E7E6E6']
                ]
            ];
            $sheet->getStyle('A' . $row . ':' . $sheet->getHighestColumn() . $row)->applyFromArray($totalsStyle);
            
            // Auto-size columns
            foreach (range('A', $sheet->getHighestColumn()) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Format currency columns
            $currencyStyle = '#,##0';
            $sheet->getStyle('K2:M' . ($row - 1))->getNumberFormat()->setFormatCode($currencyStyle);
            
            // Add summary sheet
            $this->add_sponsor_export_summary_sheet($spreadsheet, $students, $sponsor_id);
            
            // Set active sheet back to main data
            $spreadsheet->setActiveSheetIndex(0);
            
            // Generate filename
            $filter_text = $filter !== 'all' ? '_' . $filter : '';
            $search_text = !empty($search) ? '_search' : '';
            $filename = 'my_sponsored_students' . $filter_text . $search_text . '_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            // Output the file
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
            
        } catch (Exception $e) {
            log_message('error', 'Error in export_sponsored_students: ' . $e->getMessage());
            set_alert('danger', 'Error exporting students: ' . $e->getMessage());
            redirect(admin_url('student_sponsor_portal/my_sponsored_students'));
        }
    }

    /**
     * Add summary sheet for sponsor export (REUSE THE EXISTING METHOD FROM MODEL)
     */
    private function add_sponsor_export_summary_sheet($spreadsheet, $students, $sponsor_id)
    {
        $summarySheet = $spreadsheet->createSheet();
        $summarySheet->setTitle('Export Summary');
        
        $total_students = count($students);
        $school_students = count(array_filter($students, function($s) { return $s['student_type'] === 'school'; }));
        $university_students = count(array_filter($students, function($s) { return $s['student_type'] === 'university'; }));
        
        $total_committed = array_sum(array_column(array_column($students, 'transaction_summary'), 'total_amount'));
        $total_paid = array_sum(array_column(array_column($students, 'transaction_summary'), 'amount_paid'));
        $total_balance = array_sum(array_column(array_column($students, 'transaction_summary'), 'balance_amount'));
        
        $summary = [
            ['My Sponsored Students - Export Summary'],
            [''],
            ['Export Details'],
            ['Export Date:', date('Y-m-d H:i:s')],
            ['Sponsor ID:', $sponsor_id],
            ['Total Students:', $total_students],
            ['School Students:', $school_students],
            ['University Students:', $university_students],
            [''],
            ['Financial Summary'],
            ['Total Committed Amount:', '₹' . number_format($total_committed, 2)],
            ['Total Paid Amount:', '₹' . number_format($total_paid, 2)],
            ['Total Outstanding Balance:', '₹' . number_format($total_balance, 2)],
            ['Payment Completion Rate:', round(($total_committed > 0 ? ($total_paid / $total_committed) * 100 : 0), 2) . '%'],
            [''],
            ['Student Type Distribution'],
            ['School Students:', $school_students . ' (' . round(($total_students > 0 ? ($school_students / $total_students) * 100 : 0), 1) . '%)'],
            ['University Students:', $university_students . ' (' . round(($total_students > 0 ? ($university_students / $total_students) * 100 : 0), 1) . '%)']
        ];
        
        $summarySheet->fromArray($summary, null, 'A1');
        
        // Style title
        $summarySheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '4472C4']]
        ]);
        
        // Auto-size columns
        $summarySheet->getColumnDimension('A')->setWidth(35);
        $summarySheet->getColumnDimension('B')->setWidth(25);
    }


    // sposnor import function 
    /**
     * Bulk import sponsors from Excel/CSV
     */
    public function bulk_import_sponsors()
    {
        if (!has_permission('student_sponsor_portal', '', 'create')) {
            access_denied('student_sponsor_portal');
        }

        // Handle POST submission
        if ($this->input->method() === 'post') {
            log_message('debug', 'Sponsor Import: POST request received for bulk import');
            
            if (empty($_FILES['import_file']['name'])) {
                log_message('error', 'Sponsor Import: No file uploaded');
                set_alert('danger', 'Please select a file to import');
                redirect(admin_url('student_sponsor_portal/bulk_import_sponsors'));
                return;
            }

            $file_data = $_FILES['import_file'];
            log_message('debug', 'Sponsor Import: File uploaded - ' . $file_data['name'] . ' (' . $file_data['size'] . ' bytes)');

            // Validate file type - Accept both Excel and CSV
            $file_ext = strtolower(pathinfo($file_data['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['xlsx', 'xls', 'csv'];
            
            if (!in_array($file_ext, $allowed_extensions)) {
                log_message('error', 'Sponsor Import: Invalid file type - ' . $file_ext);
                set_alert('danger', 'Only Excel (.xlsx, .xls) and CSV files are supported.');
                redirect(admin_url('student_sponsor_portal/bulk_import_sponsors'));
                return;
            }

            // Check file size (max 20MB)
            if ($file_data['size'] > 20 * 1024 * 1024) {
                log_message('error', 'Sponsor Import: File too large - ' . $file_data['size'] . ' bytes');
                set_alert('danger', 'File size exceeds 20MB limit');
                redirect(admin_url('student_sponsor_portal/bulk_import_sponsors'));
                return;
            }

            // Process the uploaded file
            $temp_file = $file_data['tmp_name'];
            
            try {
                log_message('debug', 'Sponsor Import: Starting to process file');
                $result = $this->process_sponsor_import_file($temp_file, $file_ext);
                
                if ($result['success']) {
                    $message = "Sponsor import completed! ";
                    $message .= "Added: {$result['added']}, ";
                    $message .= "Updated: {$result['updated']}, ";
                    $message .= "Errors: {$result['errors']}";
                    
                    log_message('info', 'Sponsor Import: ' . $message);
                    
                    if (!empty($result['error_details'])) {
                        $this->session->set_flashdata('import_errors', $result['error_details']);
                    }
                    
                    set_alert('success', $message);
                } else {
                    log_message('error', 'Sponsor import failed: ' . $result['message']);
                    set_alert('danger', 'Import failed: ' . $result['message']);
                }
                
            } catch (Exception $e) {
                log_message('error', 'Sponsor Import Exception: ' . $e->getMessage());
                set_alert('danger', 'Import failed: ' . $e->getMessage());
            }
            
            redirect(admin_url('student_sponsor_portal/bulk_import_sponsors'));
            return;
        }
        
        // Show the form
        $data['title'] = 'Bulk Import Sponsors';
        $data['import_errors'] = $this->session->flashdata('import_errors');
        $this->load->view('student_sponsor_portal/bulk_import_sponsors', $data);
    }

    /**
     * Process sponsor import file (Excel or CSV)
     */
    private function process_sponsor_import_file($file_path, $file_ext)
    {
        log_message('debug', 'Sponsor Import: Processing file: ' . $file_path . ' (.' . $file_ext . ')');
        
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return ['success' => false, 'message' => 'Cannot read upload file'];
        }
        
        $added = 0;
        $updated = 0;
        $errors = 0;
        $error_details = [];
        
        try {
            // Load file data
            if (in_array($file_ext, ['xlsx', 'xls'])) {
                $rows = $this->load_excel_file($file_path);
            } else {
                $rows = $this->load_csv_file($file_path);
            }
            
            if (empty($rows)) {
                return ['success' => false, 'message' => 'No data found in file'];
            }
            
            // Get headers and create mapping
            $headers = array_shift($rows);
            if (empty($headers)) {
                return ['success' => false, 'message' => 'No headers found in file'];
            }
            
            $column_mapping = $this->create_sponsor_column_mapping($headers);
            if (empty($column_mapping)) {
                return ['success' => false, 'message' => 'No valid columns found. Please check your headers match the template.'];
            }
            
            log_message('debug', 'Sponsor Import: Column mapping: ' . json_encode($column_mapping));
            
            $row_number = 1; // Start from 1 (header is row 0)
            
            // Process each data row
            foreach ($rows as $row) {
                $row_number++;
                
                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }
                    
                    // Map the row data
                    $sponsor_data = $this->map_sponsor_row_data($row, $column_mapping);
                    
                    // Validate required fields
                    if (empty($sponsor_data['name'])) {
                        $errors++;
                        $error_details[] = "Row {$row_number}: Name is required";
                        continue;
                    }
                    
                    // Check for existing sponsor by email
                    $existing_sponsor = $this->find_existing_sponsor($sponsor_data);
                    
                    if ($existing_sponsor) {
                        // Update existing sponsor
                        log_message('debug', "Sponsor Import: Row {$row_number}: Updating existing sponsor ID: " . $existing_sponsor['id']);
                        
                        $result = $this->sponsor_model->update($sponsor_data, $existing_sponsor['id']);
                        
                        if ($result) {
                            $updated++;
                        } else {
                            $errors++;
                            $error_details[] = "Row {$row_number}: Failed to update sponsor";
                        }
                    } else {
                        // Add new sponsor
                        log_message('debug', "Sponsor Import: Row {$row_number}: Creating new sponsor");
                        
                        $result = $this->sponsor_model->add($sponsor_data);
                        
                        if ($result && !is_array($result)) {
                            $added++;
                            log_message('debug', "Sponsor Import: Row {$row_number}: Successfully added sponsor with ID: " . $result);
                        } else {
                            $errors++;
                            $message = is_array($result) ? ($result['message'] ?? 'Unknown error') : 'Failed to add sponsor';
                            $error_details[] = "Row {$row_number}: {$message}";
                        }
                    }
                    
                } catch (Exception $e) {
                    $errors++;
                    $error_details[] = "Row {$row_number}: " . $e->getMessage();
                    log_message('error', "Sponsor Import: Row {$row_number} exception: " . $e->getMessage());
                }
            }
            
            log_message('info', "Sponsor import completed. Added: {$added}, Updated: {$updated}, Errors: {$errors}");
            
            return [
                'success' => true,
                'added' => $added,
                'updated' => $updated,
                'errors' => $errors,
                'error_details' => $error_details
            ];
            
        } catch (Exception $e) {
            log_message('error', 'Sponsor import processing error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Processing error: ' . $e->getMessage()];
        }
    }
    /**
     * Create column mapping for sponsors - HANDLES BOTH IDs AND NAMES
     */
    private function create_sponsor_column_mapping($headers)
    {
        $mapping = [];
        
        // Complete field mappings
        $field_mappings = [
            // Basic Information
            'name' => ['name', 'sponsor_name', 'full_name', 'sponsor name', 'full name', 'sponsor name'],
            'email' => ['email', 'email_address', 'email address', 'sponsor_email', 'sponsor email'],
            'contact_no' => ['phone', 'contact_no', 'contact no', 'phone_number', 'phone number', 'contact_number', 'contact number', 'mobile', 'contact', 'phone no'],
            'address' => ['address', 'street_address', 'street address', 'home_address', 'home address'],
            'city' => ['city', 'town', 'location'],
            'zip' => ['postal_code', 'postal code', 'zip', 'zip_code', 'zip code', 'postcode', 'zipcode'],
            
            // Sponsor-specific fields
            'sponsor_type' => ['sponsor_type', 'sponsor type', 'type', 'category', 'sponsor category'],
            'sponsor_occupation' => ['sponsor_occupation', 'sponsor occupation', 'occupation', 'job', 'profession', 'work', 'employment'],
            
            // Payment/Frequency
            'sponsor_frequency' => [
                'sponsor_frequency', 'sponsor frequency', 'frequency', 'payment_frequency', 'payment frequency', 
                'donation frequency', 'donation_frequency', 'sponsorship frequency', 'sponsorship_frequency',
                'payment_type', 'payment type'
            ],
            
            // Bank Information
            'sponsor_bank_branch_info' => ['branch_info', 'branch info', 'branch_information', 'branch information', 'branch details', 'branch', 'sponsor_bank_branch_info', 'bank branch info', 'bank branch'],
            'sponsor_bank_branch_number' => ['branch_number', 'branch number', 'branch_code', 'branch code', 'branch no', 'sponsor_bank_branch_number', 'bank branch number', 'bank branch no'],
            'sponsor_bank_account_no' => ['bank_account_number', 'bank account number', 'account_number', 'account number', 'bank_account', 'bank account', 'account no', 'account_no', 'sponsor_bank_account_no'],
            
            // Membership/Sponsorship Dates
            'membership_start_date' => [
                'membership_start_date', 'membership start date', 'membership_start', 'membership start', 'start_date', 'start date',
                'sponsorship_start_date', 'sponsorship start date', 'sponsorship_start', 'sponsorship start', 'startdate'
            ],
            'membership_end_date' => [
                'membership_end_date', 'membership end date', 'membership_end', 'membership end', 'end_date', 'end date',
                'sponsorship_end_date', 'sponsorship end date', 'sponsorship_end', 'sponsorship end', 'enddate'
            ],
            
            // Country - can be ID or Name
            'country' => ['country', 'country_name', 'country name', 'country_id', 'country id', 'countryid'],
            
            // Bank - can be ID or Name
            'bank' => ['bank', 'bank_name', 'bank name', 'bank_id', 'bank id', 'bankid'],
        ];
        
        foreach ($headers as $index => $header) {
            // Normalize header: lowercase, replace special chars with spaces
            $clean_header = strtolower(trim($header));
            $clean_header = str_replace(['-', '_'], ' ', $clean_header);
            $clean_header = preg_replace('/\s+/', ' ', $clean_header); // normalize multiple spaces
            
            foreach ($field_mappings as $field => $possible_names) {
                // Normalize possible names for comparison
                $normalized_names = array_map(function($name) {
                    $n = strtolower(trim($name));
                    $n = str_replace(['-', '_'], ' ', $n);
                    return preg_replace('/\s+/', ' ', $n);
                }, $possible_names);
                
                if (in_array($clean_header, $normalized_names)) {
                    $mapping[$field] = $index;
                    log_message('debug', "Sponsor Import: Mapped column '{$header}' (index {$index}) to field '{$field}'");
                    break;
                }
            }
        }
        
        log_message('debug', 'Sponsor Import: Final column mapping: ' . json_encode($mapping));
        
        return $mapping;
    }
    /**
     * Map row data for sponsors - HANDLES BOTH IDs AND NAMES
     */
    private function map_sponsor_row_data($row, $mapping)
    {
        $data = [];
        
        foreach ($mapping as $field => $column_index) {
            $value = isset($row[$column_index]) ? trim($row[$column_index]) : '';
            
            // Handle special field transformations
            switch ($field) {
                case 'membership_start_date':
                case 'membership_end_date':
                    if ($value && $value !== '') {
                        try {
                            // Handle Excel date serial numbers
                            if (is_numeric($value) && $value > 59 && class_exists('\PhpOffice\PhpSpreadsheet\Shared\Date')) {
                                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                                $data[$field] = $date->format('Y-m-d');
                            } else {
                                $date = new DateTime($value);
                                $data[$field] = $date->format('Y-m-d');
                            }
                        } catch (Exception $e) {
                            $data[$field] = null;
                            log_message('debug', "Sponsor Import: Invalid date format for field {$field}: {$value}");
                        }
                    } else {
                        $data[$field] = null;
                    }
                    break;
                    
                case 'sponsor_frequency':
                    // Normalize frequency values
                    if ($value && $value !== '') {
                        $normalized = strtolower(trim(str_replace(['-', '_', ' '], '', $value)));
                        
                        // Map variations to standard values
                        $frequency_map = [
                            'onetime' => 'one-time',
                            'once' => 'one-time',
                            'single' => 'one-time',
                            'monthly' => 'monthly',
                            'month' => 'monthly',
                            'quarterly' => 'quarterly',
                            'quarter' => 'quarterly',
                            '3months' => 'quarterly',
                            'halfyearly' => 'half-yearly',
                            'halfyear' => 'half-yearly',
                            'biannual' => 'half-yearly',
                            '6months' => 'half-yearly',
                            'yearly' => 'yearly',
                            'annual' => 'yearly',
                            'annually' => 'yearly',
                            'year' => 'yearly',
                        ];
                        
                        $data['sponsor_frequency'] = $frequency_map[$normalized] ?? $value;
                    } else {
                        $data['sponsor_frequency'] = null;
                    }
                    break;
                    
                case 'sponsor_type':
                    // Normalize sponsor type
                    if ($value && $value !== '') {
                        $normalized = strtolower(trim($value));
                        if (in_array($normalized, ['individual', 'company', 'organization', 'corporate'])) {
                            // Map variations
                            if ($normalized === 'organization' || $normalized === 'corporate') {
                                $data['sponsor_type'] = 'company';
                            } else {
                                $data['sponsor_type'] = $normalized;
                            }
                        } else {
                            $data['sponsor_type'] = 'individual'; // Default
                        }
                    } else {
                        $data['sponsor_type'] = 'individual'; // Default
                    }
                    break;
                    
                case 'country':
                    // Handle both ID and Name
                    if ($value && $value !== '') {
                        if (is_numeric($value)) {
                            // It's an ID - use directly
                            $data['country_id'] = (int)$value;
                            log_message('debug', "Sponsor Import: Using country ID: {$value}");
                        } else {
                            // It's a name - store for lookup
                            $data['country_name'] = $value;
                            log_message('debug', "Sponsor Import: Will lookup country: {$value}");
                        }
                    }
                    break;
                    
                case 'bank':
                    // Handle both ID and Name
                    if ($value && $value !== '') {
                        if (is_numeric($value)) {
                            // It's an ID - use directly
                            $data['bank_id'] = (int)$value;
                            log_message('debug', "Sponsor Import: Using bank ID: {$value}");
                        } else {
                            // It's a name - store for lookup
                            $data['bank_name'] = $value;
                            log_message('debug', "Sponsor Import: Will lookup bank: {$value}");
                        }
                    }
                    break;
                    
                // All other fields - direct mapping
                default:
                    $data[$field] = $value !== '' ? $value : null;
                    break;
            }
        }
        
        // Handle foreign key lookups ONLY if we have names (not IDs)
        if (!empty($data['country_name']) && empty($data['country_id'])) {
            $country_id = $this->get_or_create_country_id($data['country_name']);
            if ($country_id) {
                $data['country_id'] = $country_id;
                log_message('debug', "Sponsor Import: Looked up country '{$data['country_name']}' -> ID: {$country_id}");
            }
            unset($data['country_name']);
        } elseif (isset($data['country_name'])) {
            unset($data['country_name']);
        }
        
        if (!empty($data['bank_name']) && empty($data['bank_id'])) {
            $bank_id = $this->get_or_create_bank_id($data['bank_name']);
            if ($bank_id) {
                $data['bank_id'] = $bank_id;
                log_message('debug', "Sponsor Import: Looked up bank '{$data['bank_name']}' -> ID: {$bank_id}");
            }
            unset($data['bank_name']);
        } elseif (isset($data['bank_name'])) {
            unset($data['bank_name']);
        }
        
        // Set entity_type (required field)
        $data['entity_type'] = 'sponsor';
        
        log_message('debug', 'Sponsor Import: Final mapped row data: ' . json_encode($data));
        
        return $data;
    }
    /**
     * Find existing sponsor by email
     */
    private function find_existing_sponsor($sponsor_data)
    {
        // Check by email first
        if (!empty($sponsor_data['email'])) {
            $sponsor = $this->db->where('email', $sponsor_data['email'])
                            ->get(db_prefix() . 'sponsor_records')
                            ->row_array();
            if ($sponsor) {
                log_message('debug', 'Sponsor Import: Found existing sponsor by email: ' . $sponsor_data['email']);
                return $sponsor;
            }
        }
        
        // Then check by bank account number if provided (unique field)
        if (!empty($sponsor_data['sponsor_bank_account_no'])) {
            $sponsor = $this->db->where('sponsor_bank_account_no', $sponsor_data['sponsor_bank_account_no'])
                            ->get(db_prefix() . 'sponsor_records')
                            ->row_array();
            if ($sponsor) {
                log_message('debug', 'Sponsor Import: Found existing sponsor by bank account: ' . $sponsor_data['sponsor_bank_account_no']);
                return $sponsor;
            }
        }
        
        return null;
    }
    /**
     * Download Excel template for sponsors
     */
    public function download_sponsors_template()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }
        
        // Check if PHPSpreadsheet is available
        $autoload_paths = [
            FCPATH . 'vendor/autoload.php',
            APPPATH . 'third_party/vendor/autoload.php',
            APPPATH . 'libraries/vendor/autoload.php'
        ];
        
        $phpspreadsheet_loaded = false;
        foreach ($autoload_paths as $path) {
            if (file_exists($path)) {
                require_once($path);
                $phpspreadsheet_loaded = true;
                break;
            }
        }
        
        if (!$phpspreadsheet_loaded || !class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // Fallback to CSV template
            $this->download_sponsors_csv_template();
            return;
        }
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers - Aligned with database columns (removed product_id, school_internal_ids, university_internal_ids)
        $headers = [
        'Name',                     // name
        'Sponsor Type',             // sponsor_type (individual/company)
        'Occupation',               // sponsor_occupation
        'Phone Number',             // contact_no
        'Email',                    // email
        'Address',                  // address
        'City',                     // city
        'ZIP Code',                 // zip
        'Country',                  // country_name (will lookup country_id)
        'Bank',                     // bank_name (will lookup bank_id)
        'Bank Branch Info',         // sponsor_bank_branch_info
        'Bank Branch Number',       // sponsor_bank_branch_number
        'Bank Account Number',      // sponsor_bank_account_no
        'Payment Frequency',        // sponsor_frequency
        'Sponsorship Start Date',   // membership_start_date
        'Sponsorship End Date'    // membership_end_date

        ];
        
        $sheet->fromArray($headers, null, 'A1');
        
        // Add sample data - Professional Sri Lankan example
        $sampleData = [
            'Pradeep Fernando',                              // Name
            'individual',                                    // sponsor_type (individual or company)
            'Senior Software Engineer',                      // sponsor_occupation
            '+94771234567',                                  // contact_no
            'pradeep.fernando@example.lk',                  // email
            'No. 245/3, Galle Road, Colombo 03',           // address
            'Colombo',                                       // city
            '00300',                                         // zip
            '1',                                             // country_id (Sri Lanka)
            '1',                                             // bank_id (Bank of Ceylon, Commercial Bank, etc.)
            'Kollupitiya Branch',                           // sponsor_bank_branch_info
            '003',                                           // sponsor_bank_branch_number
            '8012345678',                                    // sponsor_bank_account_no
            'monthly',                                       // sponsor_frequency (monthly, yearly, quarterly, one-time)
            '2025-01-01',                                   // membership_start_date (YYYY-MM-DD)
            '2025-12-31'                                    // membership_end_date (YYYY-MM-DD)
        ];
        
        $sheet->fromArray($sampleData, null, 'A2');
        
        // Add second sample for company type
        $sampleData2 = [
            'ABC Holdings (Pvt) Ltd',                       // Name
            'company',                                       // sponsor_type
            'Corporate Social Responsibility',               // sponsor_occupation
            '+94112567890',                                  // contact_no
            'csr@abcholdings.lk',                           // email
            'No. 100, Bauddhaloka Mawatha, Colombo 04',   // address
            'Colombo',                                       // city
            '00400',                                         // zip
            '1',                                             // country_id
            '2',                                             // bank_id
            'Bambalapitiya Branch',                         // sponsor_bank_branch_info
            '004',                                           // sponsor_bank_branch_number
            '1234567890',                                    // sponsor_bank_account_no
            'yearly',                                        // sponsor_frequency
            '2025-01-01',                                   // membership_start_date
            '2026-12-31'                                    // membership_end_date
        ];
        
        $sheet->fromArray($sampleData2, null, 'A3');
        
        // Style the header
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => '4472C4']
            ],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ];
        
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);
        
        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Add instructions sheet
        $instructionsSheet = $spreadsheet->createSheet();
        $instructionsSheet->setTitle('Instructions');
        
        $instructions = [
            ['Sponsors Import Template - Instructions'],
            [''],
            ['REQUIRED FIELDS:'],
            ['- Name: Sponsor\'s full name or organization name (required)'],
            ['- Sponsor Type: Must be either "individual" or "company" (required)'],
            [''],
            ['OPTIONAL FIELDS:'],
            ['- Sponsor Occupation: Sponsor\'s occupation, profession, or department (e.g., Software Engineer, CSR Manager)'],
            ['- Contact Number: Phone/mobile number with country code (e.g., +94771234567)'],
            ['- Email: Sponsor\'s email address (recommended for communication)'],
            ['- Address: Complete physical address with street number and name'],
            ['- City: City/town name (e.g., Colombo, Kandy, Galle)'],
            ['- ZIP Code: Postal/ZIP code (e.g., 00300, 00400)'],
            ['- Country ID: Numeric ID of the country (check your countries table, Sri Lanka is usually 1)'],
            ['- Bank ID: Numeric ID of the bank (check your banks table)'],
            ['- Bank Branch Info: Bank branch name or location (e.g., Kollupitiya Branch, Kandy City Branch)'],
            ['- Bank Branch Number: Bank branch code/number (e.g., 003, 004)'],
            ['- Bank Account Number: Sponsor\'s bank account number'],
            ['- Sponsor Frequency: Donation frequency - use: monthly, yearly, quarterly, or one-time'],
            ['- Membership Start Date: Start date in YYYY-MM-DD format (e.g., 2025-01-15)'],
            ['- Membership End Date: End date in YYYY-MM-DD format (e.g., 2026-01-15)'],
            [''],
            ['FIELD VALIDATIONS:'],
            ['- Sponsor Type: Only "individual" or "company" are valid values (lowercase)'],
            ['- Country ID & Bank ID: Must match existing IDs in your database'],
            ['- Dates: Must be in YYYY-MM-DD format (year-month-day)'],
            ['- IDs: Must be numeric values only'],
            ['- Contact Number: Include country code (e.g., +94 for Sri Lanka)'],
            [''],
            ['COMMON SRI LANKAN BANKS:'],
            ['- Bank of Ceylon (BOC)'],
            ['- People\'s Bank'],
            ['- Commercial Bank of Ceylon'],
            ['- Sampath Bank'],
            ['- Hatton National Bank (HNB)'],
            ['- Nations Trust Bank (NTB)'],
            ['- DFCC Bank'],
            ['- Seylan Bank'],
            ['(Verify Bank IDs from your system admin panel)'],
            [''],
            ['IMPORT BEHAVIOR:'],
            ['- Sponsors with existing email will be updated (if email is unique in your system)'],
            ['- Sponsors with existing bank account number will be updated (if unique in your system)'],
            ['- Remove both sample data rows (rows 2 and 3) before importing your actual data'],
            [''],
            ['TIPS & BEST PRACTICES:'],
            ['- Maximum recommended file size: 20MB'],
            ['- Verify Country IDs and Bank IDs from your system before importing'],
            ['- Test with 1-2 records first before bulk import'],
            ['- Keep a backup of your existing sponsor data before importing'],
            ['- For company sponsors, put department/role in Sponsor Occupation field'],
            ['- Use consistent date format throughout (YYYY-MM-DD)'],
            ['- Ensure email addresses are unique to avoid conflicts'],
            ['- Bank account numbers should also be unique per sponsor']
        ];
        
        $instructionsSheet->fromArray($instructions, null, 'A1');
        $instructionsSheet->getColumnDimension('A')->setWidth(80);
        
        // Style instructions header
        $instructionsSheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1F4E78']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]
        ]);
        
        // Highlight section headers in instructions
        $sectionRows = [3, 6, 22, 30, 39, 45];
        foreach ($sectionRows as $row) {
            $instructionsSheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '2E5C8A']]
            ]);
        }
        
        // Set active sheet back to template
        $spreadsheet->setActiveSheetIndex(0);
        
        $filename = 'sponsors_import_template_' . date('Y-m-d') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Fallback CSV template download for sponsors
     */
    private function download_sponsors_csv_template()
    {
        $filename = 'sponsors_import_template_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers - Aligned with database columns (removed product_id, school_internal_ids, university_internal_ids)
        $headers = [
        'Name',                     // name
        'Sponsor Type',             // sponsor_type (individual/company)
        'Occupation',               // sponsor_occupation
        'Phone Number',             // contact_no
        'Email',                    // email
        'Address',                  // address
        'City',                     // city
        'ZIP Code',                 // zip
        'Country',                  // country_name (will lookup country_id)
        'Bank',                     // bank_name (will lookup bank_id)
        'Bank Branch Info',         // sponsor_bank_branch_info
        'Bank Branch Number',       // sponsor_bank_branch_number
        'Bank Account Number',      // sponsor_bank_account_no
        'Payment Frequency',        // sponsor_frequency
        'Sponsorship Start Date',   // membership_start_date
        'Sponsorship End Date'     // membership_end_date

        ];
        
        fputcsv($output, $headers);
        
        // Sample data 1 - Individual sponsor (Professional Sri Lankan example)
        $sample1 = [
            'Pradeep Fernando',                              // Name
            'individual',                                    // sponsor_type
            'Senior Software Engineer',                      // sponsor_occupation
            '+94771234567',                                  // contact_no
            'pradeep.fernando@example.lk',                  // email
            'No. 245/3, Galle Road, Colombo 03',           // address
            'Colombo',                                       // city
            '00300',                                         // zip
            '1',                                             // country_id
            '1',                                             // bank_id
            'Kollupitiya Branch',                           // sponsor_bank_branch_info
            '003',                                           // sponsor_bank_branch_number
            '8012345678',                                    // sponsor_bank_account_no
            'monthly',                                       // sponsor_frequency
            '2025-01-01',                                   // membership_start_date
            '2025-12-31'                                    // membership_end_date
        ];
        
        fputcsv($output, $sample1);
        
        // Sample data 2 - Company sponsor
        $sample2 = [
            'ABC Holdings (Pvt) Ltd',                       // Name
            'company',                                       // sponsor_type
            'Corporate Social Responsibility',               // sponsor_occupation
            '+94112567890',                                  // contact_no
            'csr@abcholdings.lk',                           // email
            'No. 100, Bauddhaloka Mawatha, Colombo 04',   // address
            'Colombo',                                       // city
            '00400',                                         // zip
            '1',                                             // country_id
            '2',                                             // bank_id
            'Bambalapitiya Branch',                         // sponsor_bank_branch_info
            '004',                                           // sponsor_bank_branch_number
            '1234567890',                                    // sponsor_bank_account_no
            'yearly',                                        // sponsor_frequency
            '2025-01-01',                                   // membership_start_date
            '2026-12-31'                                    // membership_end_date
        ];
        
        fputcsv($output, $sample2);
        
        fclose($output);
        exit;
    }
    }