<?php defined('BASEPATH') or exit('No direct script access allowed');

class Student_sponsor_portal extends AdminController
{
    /* ===================== Common ===================== */

    public function __construct()
    {
        parent::__construct();

        // Models (perfex prefix-aware models that you already have)
        $this->load->model('student_sponsor_portal/school_model',     'school_model');
        $this->load->model('student_sponsor_portal/sponsor_model',    'sponsor_model');
        $this->load->model('student_sponsor_portal/university_model', 'university_model');
    }

    /* ---------- Helpers ---------- */

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

    /** Create/Update staff; returns staffid or null */
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
            $auto = bin2hex(random_bytes(4)); // 8 chars
            $payload['password'] = app_hash_password($auto);
        }

        $this->db->insert($staff_tbl, $payload);
        return (int)$this->db->insert_id();
    }

    /* ===================== Dashboard ===================== */

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

    /* ===================== Sponsors ===================== */

    public function sponsors()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) access_denied('student_sponsor_portal');

        $data['title']    = 'Sponsor Management';
        $data['sponsors'] = $this->sponsor_model->get_all();
        $this->load->view('student_sponsor_portal/sponsors_list', $data);
    }

    public function sponsor_form($sponsor_id = null)
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) access_denied('student_sponsor_portal');

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

        $data['title'] = 'Register Sponsor';
        if ($sponsor_id) {
            $row = $this->sponsor_model->get_by_id((int)$sponsor_id);
            if (!$row) {
                set_alert('danger', 'Sponsor not found');
                redirect(admin_url('student_sponsor_portal/sponsors'));
            }
            $data['sponsor'] = $row;
            $data['title']   = 'Edit Sponsor';
        }

        $this->load->view('student_sponsor_portal/sponsor_form', $data);
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

    /* ===================== Sponsor portal access ===================== */
     

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


/* ===================== University portal access ===================== */

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


/* ===================== School portal access ===================== */

public function grant_school_access($student_id = null)
{
    if (!has_permission('student_sponsor_portal', '', 'edit')) access_denied('student_sponsor_portal');

    // Accept from POST[name=student_id] or 4th URI segment
    $student_id = $this->resolve_id('student_id', 4);
    if ($student_id <= 0) { set_alert('danger','Missing student ID.'); redirect(admin_url('student_sponsor_portal/school_students')); }

    $s = $this->school_model->get_by_id($student_id);
    if (!$s) { set_alert('danger','Student not found.'); redirect(admin_url('student_sponsor_portal/school_students')); }

    // Pull staff fields from POST with fallbacks from student
    $data = [
        'staff_email'     => $this->in('staff_email', $s['email'] ?? ''),
        'staff_firstname' => $this->in('staff_firstname', $s['name'] ?? 'Student'),
        'staff_lastname'  => $this->in('staff_lastname', ''),
        'staff_password'  => $this->in('staff_password', ''),
        'active'          => $this->in_bool('staff_active'),
    ];
    if ($data['staff_email'] === '') {
        set_alert('danger','Login email is required.');
        redirect(admin_url('student_sponsor_portal/school_student_form/'.$student_id));
    }

    // Create / update staff and link it
    $this->ensure_three_min_roles();
    $staff_id = $this->upsert_staff($data, $s['staff_id'] ?? null, 'School Student');
    if (!$staff_id) {
        set_alert('danger','Failed to create/update staff account.');
        redirect(admin_url('student_sponsor_portal/school_student_form/'.$student_id));
    }

    $tbl = db_prefix().'school_students';
    $update = ['staff_id' => (int)$staff_id];
    if ($this->db->field_exists('active', $tbl))             $update['active'] = (int)$data['active'];
    elseif ($this->db->field_exists('staff_active', $tbl))   $update['staff_active'] = (int)$data['active'];

    $this->db->where('id', (int)$student_id)->update($tbl, $update);

    set_alert('success', 'Portal access granted'.($data['staff_password']!==''?'. Password set as provided.':'. Existing password kept or auto-generated.'));
    redirect(admin_url('student_sponsor_portal/school_student_form/'.$student_id));
}

public function revoke_school_access($student_id = null)
{
    if (!has_permission('student_sponsor_portal', '', 'edit')) access_denied('student_sponsor_portal');

    $student_id = $this->resolve_id('student_id', 4);
    if ($student_id <= 0) { set_alert('danger','Missing student ID.'); redirect(admin_url('student_sponsor_portal/school_students')); }

    $s = $this->school_model->get_by_id($student_id);
    if (!$s) { set_alert('danger','Student not found.'); redirect(admin_url('student_sponsor_portal/school_students')); }
    if (empty($s['staff_id'])) { set_alert('danger','No staff account linked.'); redirect(admin_url('student_sponsor_portal/school_student_form/'.$student_id)); }

    // Disable staff and unlink from student
    $this->db->where('staffid', (int)$s['staff_id'])->update(db_prefix().'staff', ['active'=>0]);

    $tbl = db_prefix().'school_students';
    $update = ['staff_id' => null];
    if ($this->db->field_exists('active', $tbl))             $update['active'] = 0;
    elseif ($this->db->field_exists('staff_active', $tbl))   $update['staff_active'] = 0;

    $this->db->where('id', (int)$student_id)->update($tbl, $update);

    set_alert('success','Portal access revoked.');
    redirect(admin_url('student_sponsor_portal/school_student_form/'.$student_id));
}



    /* ===================== Schools ===================== */

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

    public function school_form()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        $data['title'] = 'School Students Management';
        $data['school_students'] = $this->school_model->get_all();
        
        $this->load->view('student_sponsor_portal/school_students_list', $data);
    }

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
                // After $id is resolved (new or updated) …
                if (!empty($this->input->post('create_staff'))) {
                    // Get current student to check existing staff_id when updating
                    $existing = $id ? $this->school_model->get($id) : null;
                    $existing_staff_id = $existing['staff_id'] ?? null;

                    $this->ensure_three_min_roles();
                    $staff_id = $this->create_or_update_staff_from_student($this->input->post(), $existing_staff_id);

                    if ($staff_id) {
                        // Persist link + active flag on student
                        $this->db->where('id', $id);
                        $this->db->update(db_prefix().'school_students', [
                            'staff_id'     => $staff_id,
                            'active' => !empty($this->input->post('active')) ? 1 : 0,
                        ]);
                    }
                } else {
                    // If user unticked it, optionally disable login
                    if (!empty($id)) {
                        $this->db->where('id', $id);
                        $this->db->update(db_prefix().'school_students', ['active' => 0]);
                    }
                }

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
                // After $id is resolved (new or updated) …
                if (!empty($this->input->post('create_staff'))) {
                    // Get current student to check existing staff_id when updating
                    $existing = $id ? $this->school_model->get($id) : null;
                    $existing_staff_id = $existing['staff_id'] ?? null;

                    $this->ensure_three_min_roles();
                    $staff_id = $this->create_or_update_staff_from_student($this->input->post(), $existing_staff_id);

                    if ($staff_id) {
                        // Persist link + active flag on student
                        $this->db->where('id', $id);
                        $this->db->update(db_prefix().'school_students', [
                            'staff_id'     => $staff_id,
                            'active' => !empty($this->input->post('active')) ? 1 : 0,
                        ]);
                    }
                } else {
                    // If user unticked it, optionally disable login
                    if (!empty($id)) {
                        $this->db->where('id', $id);
                        $this->db->update(db_prefix().'school_students', ['active' => 0]);
                    }
                }

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

    /* ===================== Universities ===================== */

    /** List page */
    public function university_students()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) { access_denied('student_sponsor_portal'); }

        $data['title'] = 'University Students Management';
        $data['university_students'] = $this->university_model->get_all(); // expects array of arrays
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

            // Optional staff linking for a NEW university student
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
    
    /** Add/Edit form page */
    public function university_student_form($student_id = null)
    {
        // View permission required to access the page
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        // Handle POST (create/update)
        if ($this->input->post()) {
            $id   = $this->input->post('student_id');        // existing row id (if any)
            $post = $this->input->post(null, true);          // sanitized payload

            try {
                if (empty($id)) {
                    // CREATE → need create permission
                    if (!has_permission('student_sponsor_portal', '', 'create')) {
                        access_denied('student_sponsor_portal');
                    }

                    $newId = $this->university_model->add($post);
                    if ($newId) {
                        set_alert('success', 'University student created successfully');
                        redirect(admin_url('student_sponsor_portal/university_students'));
                    } else {
                        set_alert('danger', 'Error creating student');
                    }
                    $updated = $this->university_model->update($post, $post['student_id']);
                    if ($updated) {
                        set_alert('success', 'University student updated successfully');
                        redirect(admin_url('student_sponsor_portal/university_students'));
                    } else {
                        set_alert('danger', 'Error updating student');
                    }

                    // Optional staff linking on CREATE
                    if ($newId && !empty($this->input->post('create_staff'))) {
                        $existing          = $this->university_model->get_by_id($newId);
                        $existing_staff_id = isset($existing['staff_id']) ? $existing['staff_id'] : null;

                        $this->ensure_three_min_roles();
                        $staff_id = $this->create_or_update_staff_from_student($post, $existing_staff_id);

                        if ($staff_id) {
                            $this->db->where('id', $newId);
                            $this->db->update(db_prefix() . 'university_students', [
                                'staff_id'     => $staff_id,
                                'active' => !empty($this->input->post('active')) ? 1 : 0,
                            ]);
                        }
                    } elseif (!empty($newId)) {
                        // Explicitly ensure active = 0 when not creating staff
                        $this->db->where('id', $newId);
                        $this->db->update(db_prefix() . 'university_students', ['active' => 0]);
                    }

                    $ok  = (bool) $newId;
                    $msg = 'University student registered successfully';
                } else {
                    // UPDATE → need edit permission
                    if (!has_permission('student_sponsor_portal', '', 'edit')) {
                        access_denied('student_sponsor_portal');
                    }

                    $ok  = (bool) $this->university_model->update_student($post, $id);
                    $msg = 'University student updated successfully';

                    // Optional: keep staff mapping in sync on UPDATE
                    if ($ok && !empty($this->input->post('create_staff'))) {
                        $existing          = $this->university_model->get_by_id($id);
                        $existing_staff_id = isset($existing['staff_id']) ? $existing['staff_id'] : null;

                        $this->ensure_three_min_roles();
                        $staff_id = $this->create_or_update_staff_from_student($post, $existing_staff_id);

                        if ($staff_id) {
                            $this->db->where('id', $id);
                            $this->db->update(db_prefix() . 'university_students', [
                                'staff_id'     => $staff_id,
                                'active' => !empty($this->input->post('active')) ? 1 : 0,
                            ]);
                        }
                    } elseif (!empty($id)) {
                        $this->db->where('id', $id);
                        $this->db->update(db_prefix() . 'university_students', ['active' => 0]);
                    }
                }

                if (!empty($ok)) {
                    set_alert('success', $msg);
                    redirect(admin_url('student_sponsor_portal/university_students'));
                    return; // prevent executing the rest of the method after redirect
                } else {
                    set_alert('danger', 'Error saving student');
                }
            } catch (Exception $e) {
                log_message('error', 'Error saving university student: ' . $e->getMessage());
                set_alert('danger', 'Database error occurred: ' . $e->getMessage());
            }
        }

        // If we are here, either GET request or POST failed → load the form
        $data['title'] = 'Register University Student';

        // Load dropdowns (safe-guarded)
        try {
            $data['countries']    = $this->db->table_exists(db_prefix() . 'countries') ? $this->university_model->get_countries() : [];
            $data['universities'] = $this->university_model->get_universities();
            $data['programs']     = $this->university_model->get_programs();
            $data['banks']        = $this->university_model->get_banks();
            $data['sponsors']     = $this->university_model->get_sponsors();
        } catch (Exception $e) {
            log_message('error', 'Error loading dropdown data: ' . $e->getMessage());
            $data['countries'] = $data['universities'] = $data['programs'] = $data['banks'] = $data['sponsors'] = [];
            set_alert('warning', 'Some dropdown data could not be loaded. Please check system logs.');
        }

        // Edit mode
        if (!empty($student_id)) {
            $student = $this->university_model->get_by_id($student_id);
            if ($student) {
                $data['student'] = $student;
                $data['title']   = 'Edit University Student';
            } else {
                set_alert('danger', 'Student not found');
                redirect(admin_url('student_sponsor_portal/university_students'));
                return;
            }
        }

        // Render view
        $this->load->view('student_sponsor_portal/university_form', $data);
    }

    /** AJAX get / view */
    public function get_university_student()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) { access_denied('student_sponsor_portal'); }

        if ($this->input->post()) {
            $post = $this->input->post(null, true);
            $id = $this->input->post('student_id');
            $s  = $this->university_model->get_by_id($id);

            if ($s) {
                if ($this->input->post('action') == 'view') {
                    $html = '<div class="row">
                        <div class="col-md-6">
                            <h5><strong>Student Information</strong></h5>
                            <p><strong>Name:</strong> '.htmlspecialchars($s['name']).'</p>
                            <p><strong>University:</strong> '.htmlspecialchars($s['university_name'] ?? '').'</p>
                            <p><strong>Faculty:</strong> '.htmlspecialchars($s['faculty'] ?? '').'</p>
                            <p><strong>Program:</strong> '.htmlspecialchars($s['program'] ?? '').'</p>
                            <p><strong>Year:</strong> '.htmlspecialchars($s['year_of_study'] ?? '').'</p>
                            <p><strong>Email:</strong> '.htmlspecialchars($s['email'] ?? '').'</p>
                            <p><strong>Phone:</strong> '.htmlspecialchars($s['phone'] ?? '').'</p>
                            <p><strong>DOB:</strong> '.htmlspecialchars($s['dob'] ?? 'Not provided').'</p>
                        </div>
                        <div class="col-md-6">
                            <h5><strong>Additional Information</strong></h5>
                            <p><strong>Address:</strong> '.htmlspecialchars($s['address'] ?? 'Not provided').'</p>
                            <p><strong>District:</strong> '.htmlspecialchars($s['district'] ?? 'Not provided').'</p>
                            <p><strong>Advisor Comments:</strong> '.htmlspecialchars($s['advisor_comments'] ?? '').'</p>
                            <p><strong>GPA:</strong> '.htmlspecialchars($s['gpa'] ?? '').'</p>
                            <p><strong>Credits:</strong> '.htmlspecialchars($s['credits_earned'] ?? '').'</p>
                            <p><strong>Attendance:</strong> '.htmlspecialchars($s['attendance'] ?? '').'%</p>
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

    /** AJAX delete */
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

    /** CSV export */
    public function export_university_students()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) { access_denied('student_sponsor_portal'); }
        $students = $this->university_model->get_all();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="university_students_'.date('Y-m-d').'.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'ID','Name','Email','Phone','University','Faculty','Program','Year',
            'University ID','DOB','Admission Date','Expected Graduation',
            'Sponsors','Sponsorship Start','Sponsorship End',
            'GPA','Credits','Attendance','Address','District','Postal Code','Country'
        ]);

        foreach($students as $s){
            fputcsv($out, [
                $s['id'] ?? '', $s['name'] ?? '', $s['email'] ?? '', $s['phone'] ?? '',
                $s['university_name'] ?? '', $s['faculty'] ?? '', $s['program'] ?? '', $s['year_of_study'] ?? '',
                $s['university_id_no'] ?? '', $s['dob'] ?? '', $s['admission_date'] ?? '', $s['expected_graduation'] ?? '',
                $s['sponsors'] ?? '', $s['sponsorship_start'] ?? '', $s['sponsorship_end'] ?? '',
                $s['gpa'] ?? '', $s['credits_earned'] ?? '', $s['attendance'] ?? '',
                $s['address'] ?? '', $s['district'] ?? '', $s['postal_code'] ?? '', $s['country'] ?? ''
            ]);
        }
        fclose($out);
    }

    /** Quick-add helpers used by the form (JSON) */
    public function add_university_name() {
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
            // Check if already exists
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

    public function add_university_program() {
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

    public function add_bank() {
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

    /* ===================== Report Cards (unchanged) ===================== */

    public function upload_report_card()
    {
        header('Content-Type: application/json');
        
        if (!has_permission('student_sponsor_portal', '', 'create')) {
            echo json_encode(['success' => false, 'message' => 'No permission']);
            return;
        }

        $student_id        = $this->input->post('student_university_id');
        $report_card_term  = $this->input->post('report_card_term');
        $semester_end_month= $this->input->post('semester_end_month');
        $semester_end_year = $this->input->post('semester_end_year');

        if (empty($student_id)) {
            echo json_encode(['success' => false, 'message' => 'Student ID is required']);
            return;
        }

        // Verify student exists
        $student = $this->university_model->get_by_id($student_id);
        if (!$student) {
            echo json_encode(['success' => false, 'message' => 'Student not found']);
            return;
        }

        // Handle file upload
        if (!empty($_FILES['report_card_file']['name'])) {
            $config['upload_path']   = './uploads/student_report_cards/';
            $config['allowed_types'] = 'pdf|jpg|jpeg|png';
            $config['max_size']      = 5120; // 5MB
            $config['file_name']     = 'report_card_' . $student_id . '_' . time();
            
            // Create directory if it doesn't exist
            if (!is_dir($config['upload_path'])) {
                mkdir($config['upload_path'], 0755, true);
            }

            $this->load->library('upload', $config);

            if ($this->upload->do_upload('report_card_file')) {
                $upload_data = $this->upload->data();
                
                // Save to database
                $data = [
                    'student_university_id' => $student_id,
                    'filename'              => $upload_data['file_name'],
                    'report_card_term'      => $report_card_term,
                    'semester_end_month'    => $semester_end_month,
                    'semester_end_year'     => $semester_end_year,
                    'report_card_file'      => $upload_data['full_path'],
                    'upload_date'           => date('Y-m-d H:i:s'),
                    'created_on'            => date('Y-m-d H:i:s')
                ];

                $this->db->insert(db_prefix() . 'university_report_card', $data);
                
                if ($this->db->insert_id()) {
                    echo json_encode(['success' => true, 'message' => 'Report card uploaded successfully']);
                } else {
                    // Delete uploaded file if database insert failed
                    @unlink($upload_data['full_path']);
                    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => $this->upload->display_errors('', '')]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No file selected']);
        }
    }

    public function get_report_cards()
    {
        header('Content-Type: application/json');
        
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            echo json_encode(['success' => false, 'message' => 'No permission']);
            return;
        }

        $student_id = $this->input->post('student_id');
        
        if (empty($student_id)) {
            echo json_encode(['success' => false, 'message' => 'Student ID is required']);
            return;
        }

        $this->db->where('student_university_id', $student_id);
        $this->db->order_by('upload_date', 'DESC');
        $report_cards = $this->db->get(db_prefix() . 'university_report_card')->result_array();

        // Add file URLs
        foreach ($report_cards as &$card) {
            $card['file_url']   = base_url('uploads/student_report_cards/' . $card['filename']);
            $card['upload_date']= date('M d, Y', strtotime($card['upload_date']));
        }

        echo json_encode(['success' => true, 'report_cards' => $report_cards]);
    }

    public function delete_report_card()
    {
        header('Content-Type: application/json');
        
        if (!has_permission('student_sponsor_portal', '', 'delete')) {
            echo json_encode(['success' => false, 'message' => 'No permission']);
            return;
        }

        $report_card_id = $this->input->post('report_card_id');
        
        if (empty($report_card_id)) {
            echo json_encode(['success' => false, 'message' => 'Report card ID is required']);
            return;
        }

        // Get report card details before deletion
        $this->db->where('id', $report_card_id);
        $report_card = $this->db->get(db_prefix() . 'university_report_card')->row();

        if ($report_card) {
            // Delete from database
            $this->db->where('id', $report_card_id);
            $deleted = $this->db->delete(db_prefix() . 'university_report_card');

            if ($deleted) {
                // Delete physical file
                if (!empty($report_card->report_card_file) && file_exists($report_card->report_card_file)) {
                    @unlink($report_card->report_card_file);
                }
                
                echo json_encode(['success' => true, 'message' => 'Report card deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete report card']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Report card not found']);
        }
    }
}