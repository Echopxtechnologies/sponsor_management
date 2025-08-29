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
    /* ===================== Common ===================== */

    public function __construct()
    {
        parent::__construct();

        // Models (perfex prefix-aware models that you already have)
        $this->load->model('student_sponsor_portal/school_model',     'school_model');
        $this->load->model('student_sponsor_portal/sponsor_model',    'sponsor_model');
        $this->load->model('student_sponsor_portal/university_model', 'university_model');
        $this->load->model('student_sponsor_portal/Sponsor_transactions_model', 'txn_model');
    }
    
     public function add()
    {
        if ($this->input->post()) {
            $this->sponsor_student_model->add_relation($this->input->post());
            set_alert('success', 'Relation Added Successfully');
            redirect(admin_url('sponsorstudent'));
        }

        $this->load->model('sponsor_model');
        $this->load->model('student_model');

        $data['sponsors'] = $this->sponsor_model->get_all();
        $data['students'] = $this->student_model->get_all();

        $data['title'] = 'Assign Sponsor to Student';
        $this->load->view('sponsor_student/add', $data);
    }

// /modules/student_sponsor_portal/controllers/Student_sponsor_portal.php
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

    // include CSRF to refresh hash for later calls (Perfex friendly)
    $out = [
        'results' => $results,
        'pagination' => ['more'=>$more],
        $this->security->get_csrf_token_name() => $this->security->get_csrf_hash()
    ];
    echo json_encode($out); die();
}

public function search_school_students()
{
    if (!is_staff_logged_in()) show_404();
    $q = trim($this->input->post('q', true));
    $page = max(1, (int)$this->input->post('page')); $limit=20; $offset=($page-1)*$limit;

    $this->db->select('id, name');
    if ($q !== '') { $this->db->like('name', $q); }
    $this->db->order_by('name','asc')->limit($limit+1, $offset);
    $rows = $this->db->get(db_prefix().'school_students')->result();

    $more = count($rows) > $limit; if ($more) array_pop($rows);
    $results = array_map(function($r){ return ['id'=>$r->id,'name'=>$r->name,'text'=>$r->name]; }, $rows);

    echo json_encode([
        'results'=>$results,
        'pagination'=>['more'=>$more],
        $this->security->get_csrf_token_name() => $this->security->get_csrf_hash()
    ]); die();
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




    /* ===================== Payments List ===================== */

/** /admin/student_sponsor_portal/payments */
public function payments()
{
    if (!(is_admin() || has_permission('student_sponsor_portal', '', 'view'))) access_denied('student_sponsor_portal');

    // --- read filters from GET ---
    $filters = [
        'date_from'    => trim($this->input->get('date_from') ?? ''),
        'date_to'      => trim($this->input->get('date_to') ?? ''),
        'sponsor_id'   => (int)($this->input->get('sponsor_id') ?? 0),
        'student_type' => trim($this->input->get('student_type') ?? ''), // 'school' | 'university' | ''
        'currency'     => trim($this->input->get('currency') ?? ''),
        'min_amount'   => (float)($this->input->get('min_amount') ?? 0),
        'max_amount'   => (float)($this->input->get('max_amount') ?? 0),
        'has_note'     => $this->input->get('has_note') === '1' ? 1 : 0,
        'q'            => trim($this->input->get('q') ?? ''),
        'created_by'   => (int)($this->input->get('created_by') ?? 0),
    ];

    // --- paging ---
    $per_page = 25;
    $page     = max(1, (int)($this->input->get('page') ?? 1));
    $offset   = ($page - 1) * $per_page;

    // data
    $data['title']     = 'Sponsor Payments';
    $data['filters']   = $filters;
    $data['payments']  = $this->txn_model->list_payments($filters, $per_page, $offset);
    $data['total']     = $this->txn_model->count_payments($filters);
    $data['per_page']  = $per_page;
    $data['page']      = $page;

    // dropdown sources
    $data['sponsors']  = $this->db->select('id,name')->order_by('name')->get('tblsponsor_records')->result();
    $data['staff']     = $this->db->select('staffid,firstname,lastname')->order_by('firstname')->get(db_prefix().'staff')->result();

    $this->load->view('transactions/payments_index', $data);
}


    /* ===================== Transaction Management ===================== */

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

    /* --------- BEGIN: enforce XOR + true NULLs --------- */
    // Read raw values from POST
    $schoolRaw = isset($data['school_student_id']) ? trim((string)$data['school_student_id']) : '';
    $uniRaw    = isset($data['university_student_id']) ? trim((string)$data['university_student_id']) : '';

    // Cast to ints only if non-empty
    $schoolId  = ($schoolRaw !== '') ? (int)$schoolRaw : 0;
    $uniId     = ($uniRaw   !== '') ? (int)$uniRaw    : 0;

    // If university chosen, blank out school; if school chosen, blank out university
    if ($uniId > 0 && $schoolId > 0) {
        // user selected both -> block
        set_alert('danger', 'Pick exactly one: School student or University student.');
        $back = $id ? 'student_sponsor_portal/transaction/'.$id.'?tab=details'
                    : 'student_sponsor_portal/transaction?tab=details';
        redirect(admin_url($back));
    }

    if ($uniId > 0) {
        $data['university_student_id'] = $uniId;
        $data['school_student_id']     = null;   // IMPORTANT: true NULL (prevents 0)
    } elseif ($schoolId > 0) {
        $data['school_student_id']     = $schoolId;
        $data['university_student_id'] = null;   // IMPORTANT: true NULL
    } else {
        set_alert('danger', 'Please select a student (school or university).');
        $back = $id ? 'student_sponsor_portal/transaction/'.$id.'?tab=details'
                    : 'student_sponsor_portal/transaction?tab=details';
        redirect(admin_url($back));
    }

    // Ensure sponsor present
    $data['sponsor_id'] = isset($data['sponsor_id']) ? (int)$data['sponsor_id'] : 0;
    if ($data['sponsor_id'] <= 0) {
        set_alert('danger', 'Sponsor is required.');
        $back = $id ? 'student_sponsor_portal/transaction/'.$id.'?tab=details'
                    : 'student_sponsor_portal/transaction?tab=details';
        redirect(admin_url($back));
    }

    // Normalize numeric/text fields
    $data['total_amount'] = isset($data['total_amount']) ? (float)$data['total_amount'] : 0.0;
    $data['amount_paid']  = isset($data['amount_paid'])  ? (float)$data['amount_paid']  : 0.0;
    $data['currency']     = isset($data['currency']) && $data['currency'] !== '' ? trim($data['currency']) : 'INR';
    /* --------- END: enforce XOR + true NULLs --------- */

    if ($id) {
        if (!has_permission('student_sponsor_portal', '', 'edit')) access_denied('student_sponsor_portal');
        $ok = $this->txn_model->update($id, $data);
        if ($ok) { $this->txn_model->recompute_next_due_from_type($id); }   // <-- add this

        set_alert($ok ? 'success' : 'warning', $ok ? 'Updated' : 'Update failed');
        redirect(admin_url('student_sponsor_portal/transaction/'.$id));
    } else {
        if (!has_permission('student_sponsor_portal', '', 'create')) access_denied('student_sponsor_portal');
        $newId = $this->txn_model->create($data);
        if ($newId) { $this->txn_model->recompute_next_due_from_type($newId); } // <-- add this
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

    /** Helper method for creating staff from student data */
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

   public function add_payment($transaction_id)
{
    // Permissions
    if (!is_admin() && !has_permission('student_sponsor_portal', '', 'create')) {
        access_denied('student_sponsor_portal');
    }

    $transaction_id = (int) $transaction_id;
    if ($transaction_id <= 0) {
        show_error('Invalid transaction id', 400);
    }

    // Fetch the transaction (need payment_type, currency, etc.)
    $txn = $this->db->where('id', $transaction_id)
                    ->get(db_prefix().'sponsor_transactions')->row();
    if (!$txn) {
        show_error('Transaction not found', 404);
    }

    // Read POST
    $pdate    = trim($this->input->post('payment_date', true));
    $amount   = (float) $this->input->post('amount', true);
    $currency = trim($this->input->post('currency', true));
    $note     = trim($this->input->post('note', true));
    $sponsor  = (int) $this->input->post('sponsor_id', true);
    $student  = (int) $this->input->post('student_id', true);

    // Basic validation
    if (!$pdate || !$amount) {
        set_alert('warning', 'Payment date and amount are required.');
        redirect(admin_url('student_sponsor_portal/transaction/'.$transaction_id.'?tab=payments'));
    }

    // Insert payment
    $row = [
        'transaction_id' => $transaction_id,
        'sponsor_id'     => $sponsor ?: (int)$txn->sponsor_id,
        'student_id'     => $student ?: (int)($txn->school_student_id ?: $txn->university_student_id),
        'payment_date'   => $pdate,                       // Y-m-d expected
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

    // --- Recalculate totals and dates ---------------------------------------

    // 1) Recalc amount_paid (sum of all payments)
    $sum = $this->db->select_sum('amount', 's')
                    ->where('transaction_id', $transaction_id)
                    ->get(db_prefix().'sponsor_payments')->row();
    $amount_paid = (float) ($sum ? $sum->s : 0);

    // 2) Get latest payment date for last_payment_date
    $last = $this->db->select('payment_date')
                     ->where('transaction_id', $transaction_id)
                     ->order_by('payment_date', 'DESC')
                     ->limit(1)
                     ->get(db_prefix().'sponsor_payments')->row();
    $last_payment_date = $last ? $last->payment_date : null;

    // 3) Compute next_payment_due from payment_type
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
                // one_time or custom => leave NULL or handle custom rule here
                default:
                    $next_payment_due = null;
            }
        }
    }

    // 4) Update the transaction row
    $this->db->where('id', $transaction_id)->update(db_prefix().'sponsor_transactions', [
        'amount_paid'       => $amount_paid,
        'balance_amount'    => max(0, (float)$txn->total_amount - $amount_paid),
        'last_payment_date' => $last_payment_date,
        'next_payment_due'  => $next_payment_due,
    ]);

    set_alert('success', 'Payment added.');
    redirect(admin_url('student_sponsor_portal/transaction/'.$transaction_id.'?tab=payments'));
}


    /* ===== Payments: edit & delete ===== */

public function edit_payment($transaction_id, $payment_id)
{
    if (!has_permission('student_sponsor_portal', '', 'edit')) access_denied('student_sponsor_portal');

    $transaction_id = (int) $transaction_id;
    $payment_id     = (int) $payment_id;

    // if POST, update then redirect
    if ($this->input->post()) {
        $data = [
            'payment_date' => $this->input->post('payment_date'),
            'amount'       => $this->input->post('amount'),
            'currency'     => $this->input->post('currency'),
            'note'         => $this->input->post('note'),
        ];

        $ok = $this->txn_model->update_payment($payment_id, $data);
        if ($ok) {
            // ensure totals updated
            $this->txn_model->recompute_amount_paid($transaction_id);
            set_alert('success', 'Payment updated');
        } else {
            set_alert('warning', 'Update failed');
        }
        redirect(admin_url('student_sponsor_portal/transaction/'.$transaction_id.'?tab=payments'));
    }

    // GET: show edit form on the same page (handled by view via ?edit_payment=ID)
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
    if (!has_permission('student_sponsor_portal', '', 'view')) {
        access_denied('student_sponsor_portal');
    }

    // ---------- CREATE / UPDATE ----------
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

    // ---------- PAGE DATA ----------
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

    // ---------- REFERENCE LISTS (for selects) ----------
    // Banks (tblbank)
    $banks = [];
    if ($this->db->table_exists(db_prefix().'bank')) {
        $rs = $this->db->order_by('name','asc')->get(db_prefix().'bank')->result_array();
        foreach ($rs as $r) {
            $banks[] = ['id' => (int)$r['id'], 'name' => (string)$r['name']];
        }
    }
    $data['banks'] = $banks;

    // Countries (prefer tblcountry; fallback to tblcountries display if present)
    $countries = [];
    if ($this->db->table_exists(db_prefix().'country')) {
        $rs = $this->db->order_by('name','asc')->get(db_prefix().'country')->result_array();
        foreach ($rs as $r) {
            $countries[] = ['id' => (int)$r['id'], 'name' => (string)$r['name']];
        }
    } elseif ($this->db->table_exists(db_prefix().'countries')) {
        // display-only fallback (no FK validation), still useful for the UI
        $rs = $this->db->order_by('short_name','asc')->get(db_prefix().'countries')->result_array();
        foreach ($rs as $r) {
            $countries[] = ['id' => (int)$r['country_id'], 'name' => (string)$r['short_name']];
        }
    }
    $data['countries'] = $countries;

    // States (optional – only if you have a state table and a selected country)
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

    // ---------- VIEW ----------
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

    // minimal schema: tblbank(id, name, branch, code)
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

        $student_id = $this->resolve_id('student_id', 4);
        if ($student_id <= 0) { set_alert('danger','Missing student ID.'); redirect(admin_url('student_sponsor_portal/school_students')); }

        $s = $this->school_model->get_by_id($student_id);
        if (!$s) { set_alert('danger','Student not found.'); redirect(admin_url('student_sponsor_portal/school_students')); }

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

    // Handle normal form POST (non-AJAX)
    if ($this->input->post()) {
        $isCreate = empty($this->input->post('student_id'));
        if ($isCreate && !has_permission('student_sponsor_portal', '', 'create')) {
            access_denied('student_sponsor_portal');
        }
        if (!$isCreate && !has_permission('student_sponsor_portal', '', 'edit')) {
            access_denied('student_sponsor_portal');
        }

        $post = $this->input->post();
        $sid  = (int)($post['student_id'] ?? 0);
        unset($post['student_id']);

        // Keep user input to repopulate the form on error
        $this->session->set_flashdata('old_input', $post);

        if ($isCreate) {
            // CREATE
           $res = $this->school_model->add($post);
            if (!is_array($res)) { $res = $res ? ['success'=>true,'id'=>$res] : ['success'=>false,'message'=>'Unable to save.']; }

            if (!$res['success']) {
                $this->session->set_flashdata('old_input', $post);
                set_alert('danger', $res['message'] ?? 'Error saving student');
                redirect(admin_url('student_sponsor_portal/school_student_form')); // stays on form
            }


            $newId = (int)$res['id'];

            // Optional: create staff user
            if (!empty($this->input->post('create_staff'))) {
                $existing = $this->school_model->get($newId);
                $existing_staff_id = $existing['staff_id'] ?? null;

                $this->ensure_three_min_roles();
                $staff_id = $this->create_or_update_staff_from_student($this->input->post(), $existing_staff_id);

                if ($staff_id) {
                    $this->db->where('id', $newId)->update(db_prefix().'school_students', [
                        'staff_id' => $staff_id,
                        'staff_active' => !empty($this->input->post('staff_active')) ? 1 : 0,
                    ]);
                }
            } else {
                // default inactive if not creating staff
                $this->db->where('id', $newId)->update(db_prefix().'school_students', ['staff_active' => 0]);
            }
            
            $result = $res;

            set_alert('success', 'School student registered successfully');
            redirect(admin_url('student_sponsor_portal/school_students'));
        } else {
            // UPDATE
            $ok = $this->school_model->update($post, $sid);
            if (!$ok) {
                // Optional: read last DB error for nicer message (e.g., duplicate)
                $err = $this->db->error();
                $msg = (!empty($err['code']) && (int)$err['code'] === 1062)
                    ? 'Duplicate value detected (e.g., email). Please use a different value.'
                    : 'Error saving student.';
                set_alert('danger', $msg);
                redirect(admin_url('student_sponsor_portal/school_student_form/'.$sid));
            }

            set_alert('success', 'School student updated successfully');
            redirect(admin_url('student_sponsor_portal/school_students'));
        }
    }

    // ----- GET: render the form
    $data['title'] = 'Register School Student';

    if ($student_id) {
        $student_data = $this->school_model->get_by_id($student_id);
        if ($student_data) {
            $data['student'] = $student_data;
            $data['title']   = 'Edit School Student';
        } else {
            set_alert('danger', 'Student not found');
            redirect(admin_url('student_sponsor_portal/school_students'));
        }
    }

    // repopulate with old input after a failed POST
    $data['old'] = $this->session->flashdata('old_input') ?: [];

    $data['banks'] = $this->db->select('id,name')->order_by('name','ASC')->get(db_prefix().'bank')->result_array();

    $this->load->view('student_sponsor_portal/school_form', $data);
}

/**
 * AJAX endpoint (returns JSON). Use it from JS if the form submits via AJAX.
 */
public function save_school_student()
{
    // Always return JSON
    $this->output->set_content_type('application/json');

    if (!$this->input->post()) {
        $this->output->set_output(json_encode(['success'=>false,'message'=>'No data received']));
        return;
    }

    $sid  = (int)$this->input->post('student_id');
    $post = $this->input->post();
    unset($post['student_id']);

    // CREATE
    if ($sid <= 0) {
        if (!has_permission('student_sponsor_portal', '', 'create')) {
            $this->output->set_status_header(403)->set_output(json_encode(['success'=>false,'message'=>'No permission to create']));
            return;
        }

        $res = $this->school_model->add($post);
            if (!is_array($res)) { $res = $res ? ['success'=>true,'id'=>$res] : ['success'=>false,'message'=>'Unable to save.']; }

            if (!$res['success']) {
                $this->session->set_flashdata('old_input', $post);
                set_alert('danger', $res['message'] ?? 'Error saving student');
                redirect(admin_url('student_sponsor_portal/school_student_form')); // stays on form
            }


        if (!$res['success']) {
            $code = 400;
            if (($res['error'] ?? '') === 'duplicate_email' || ($res['error'] ?? '') === 'duplicate_key') {
                $code = 409; // Conflict
            }
            $this->output->set_status_header($code)->set_output(json_encode($res));
            return;
        }

        $newId = (int)$res['id'];

        if (!empty($this->input->post('create_staff'))) {
            $existing = $this->school_model->get($newId);
            $existing_staff_id = $existing['staff_id'] ?? null;

            $this->ensure_three_min_roles();
            $staff_id = $this->create_or_update_staff_from_student($this->input->post(), $existing_staff_id);

            if ($staff_id) {
                $this->db->where('id', $newId)->update(db_prefix().'school_students', [
                    'staff_id' => $staff_id,
                    'active'   => !empty($this->input->post('active')) ? 1 : 0,
                ]);
            }
        } else {
            $this->db->where('id', $newId)->update(db_prefix().'school_students', ['active'=>0]);
        }

        $this->output->set_output(json_encode(['success'=>true,'id'=>$newId,'message'=>'School student created successfully']));
        return;
    }

    // UPDATE
    if (!has_permission('student_sponsor_portal', '', 'edit')) {
        $this->output->set_status_header(403)->set_output(json_encode(['success'=>false,'message'=>'No permission to edit']));
        return;
    }

    // Ensure the student exists
    $existing_student = $this->school_model->get($sid);
    if (!$existing_student) {
        $this->output->set_status_header(404)->set_output(json_encode(['success'=>false,'message'=>'Student not found']));
        return;
    }

    $ok = $this->school_model->update($post, $sid);
    if (!$ok) {
        $err = $this->db->error();
        $msg = (!empty($err['code']) && (int)$err['code'] === 1062)
            ? 'Duplicate value detected (e.g., email). Please use a different value.'
            : 'Database error occurred';
        $this->output->set_status_header((!empty($err['code']) && (int)$err['code'] === 1062) ? 409 : 400)
             ->set_output(json_encode(['success'=>false,'message'=>$msg]));
        return;
    }

    $this->output->set_output(json_encode(['success'=>true,'message'=>'School student updated successfully']));
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
                $student = $student_data;
                
                if ($this->input->post('action') == 'view') {
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
                    echo json_encode(['success' => true, 'student' => $student]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Student not found']);
            }
        }
    }

    public function add_school_name() {
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
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="school_students_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        fputcsv($output, [
            'ID', 'Name', 'Email', 'Phone', 'Grade', 'School Name', 'District', 
            'Date of Birth', 'Father Name', 'Mother Name', 'Guardian Name',
            'Sponsors', 'Sponsorship Start', 'Sponsorship End', 'Address'
        ]);
        
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

    public function university_students()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }
        
        $data['students'] = $this->university_model->get_all();
        $data['title'] = 'University Students';
        
        // FIXED: Load the correct view
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

public function get_stats()
{
    if (!has_permission('student_sponsor_portal', '', 'view')) {
        echo json_encode(['success' => false]); return;
    }
    $this->load->model('student_sponsor_portal/school_model');
    $this->load->model('student_sponsor_portal/university_model');
    $this->load->model('student_sponsor_portal/sponsor_model');

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

    
    public function university_student_form($student_id = null)
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        if ($this->input->post()) {
            $id   = $this->input->post('student_id');
            $post = $this->input->post(null, true);

            try {
                if (empty($id)) {
                    if (!has_permission('student_sponsor_portal', '', 'create')) {
                        access_denied('student_sponsor_portal');
                    }

                    $newId = $this->university_model->add($post);
                    
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
                        $this->db->where('id', $newId);
                        $this->db->update(db_prefix() . 'university_students', ['active' => 0]);
                    }

                    if ($newId) {
                        set_alert('success', 'University student created successfully');
                        redirect(admin_url('student_sponsor_portal/university_students'));
                    } else {
                        set_alert('danger', 'Error creating student');
                    }
                } else {
                    if (!has_permission('student_sponsor_portal', '', 'edit')) {
                        access_denied('student_sponsor_portal');
                    }

                    $ok  = (bool) $this->university_model->update_student($post, $id);
                    
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

                    if ($ok) {
                        set_alert('success', 'University student updated successfully');
                        redirect(admin_url('student_sponsor_portal/university_students'));
                    } else {
                        set_alert('danger', 'Error updating student');
                    }
                }
            } catch (Exception $e) {
                log_message('error', 'Error saving university student: ' . $e->getMessage());
                set_alert('danger', 'Database error occurred: ' . $e->getMessage());
            }
        }

        $data['title'] = 'Register University Student';

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

        $this->load->view('student_sponsor_portal/university_form', $data);
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
// school report card 
/* ===================== School Report Cards ===================== */

    public function upload_school_report_card()
    {
        header('Content-Type: application/json');

        if (!has_permission('student_sponsor_portal', '', 'create')) {
            echo json_encode(['success' => false, 'message' => 'No permission']); return;
        }

        // inputs (match schema/UX names)
        $student_id = (int)$this->input->post('student_school_id');
        $term       = trim((string)$this->input->post('term')); // 'Term1'|'Term2'|'Term3'
        $display    = trim((string)$this->input->post('display_filename')); // optional

        if ($student_id <= 0) { echo json_encode(['success'=>false,'message'=>'Student ID is required']); return; }
        if ($term === '' || !in_array($term, ['Term1','Term2','Term3'], true)) {
            echo json_encode(['success'=>false,'message'=>'Valid term is required']); return;
        }
        if (empty($_FILES['report_card_file']['name'])) {
            echo json_encode(['success'=>false,'message'=>'No file selected']); return;
        }

        // ensure student exists
        $s = $this->school_model->get_by_id($student_id);
        if (!$s) { echo json_encode(['success'=>false,'message'=>'Student not found']); return; }

        $origName = (string)$_FILES['report_card_file']['name'];
        $tmpName  = (string)$_FILES['report_card_file']['tmp_name'];
        $size     = (int)$_FILES['report_card_file']['size'];

        if (!is_uploaded_file($tmpName)) { echo json_encode(['success'=>false,'message'=>'Upload failed (temp not found)']); return; }

        // 10MB cap (adjust if you like)
        $maxBytes = 10 * 1024 * 1024;
        if ($size <= 0 || $size > $maxBytes) { echo json_encode(['success'=>false,'message'=>'File too large (max 10MB)']); return; }

        // MIME
        $mime = null;
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE); if ($f) { $mime = @finfo_file($f, $tmpName); finfo_close($f); }
        }
        if (!$mime && !empty($_FILES['report_card_file']['type'])) $mime = $_FILES['report_card_file']['type'];
        $mime = strtolower((string)$mime) ?: 'application/octet-stream';

        // visible filename (DB: filename)
        $display = $display !== '' ? $display : $origName;
        $display = preg_replace('/[^\p{L}\p{N}\-\._ ]/u', '_', $display);
        if ($display === '' || $display === '_') $display = 'report_card_'.date('Ymd_His');

        $sha256 = @hash_file('sha256', $tmpName) ?: null;

        $tbl = db_prefix().'school_report_card';

        $payload = [
            'student_school_id' => $student_id,
            'term'              => $term,
            'upload_date'       => date('Y-m-d'),     // schema is DATE (not datetime)
            'filename'          => $display,
            'mime_type'         => $mime,
            'file_size'         => $size,
            'sha256'            => $sha256,
            'created_on'        => date('Y-m-d H:i:s'), // nullable with default CURRENT_TIMESTAMP in your schema
        ];

        // storage mode: prefer blob if column exists
        $hasBlob = $this->db->field_exists('file_blob', $tbl);
        if ($hasBlob) {
            $bytes = @file_get_contents($tmpName);
            if ($bytes === false) { echo json_encode(['success'=>false,'message'=>'Cannot read uploaded file']); return; }
            $payload['file_blob'] = $bytes;
            if ($this->db->field_exists('report_card_file', $tbl)) $payload['report_card_file'] = null;

            $ok = $this->db->insert($tbl, $payload);
            if (!$ok) { echo json_encode(['success'=>false,'message'=>'Database error (blob insert)']); return; }
        } else {
            // disk mode
            $internalId = (string)($s['school_internal_id'] ?? '');
            $internalId = preg_replace('/[^\w\-]+/u', '_', $internalId);
            if ($internalId === '') $internalId = 'STU';

            $prefixed = $internalId.'_'.$display;
            if (strlen($prefixed) > 200) {
                $ext = ''; $dot = strrpos($prefixed, '.');
                if ($dot !== false && $dot >= 1) { $ext = substr($prefixed, $dot); }
                $prefixed = substr($prefixed, 0, 200 - strlen($ext)) . $ext;
            }

            $storeDir = rtrim(FCPATH, '/\\') . '/uploads/school_report_cards/';
            if (!is_dir($storeDir)) @mkdir($storeDir, 0755, true);

            $serverName = $prefixed; $i=2;
            while (file_exists($storeDir.$serverName) && $i < 100) {
                $dot = strrpos($prefixed, '.');
                if ($dot !== false && $dot >= 1) {
                    $base = substr($prefixed, 0, $dot); $ext = substr($prefixed, $dot);
                    $serverName = $base.'-'.$i.$ext;
                } else {
                    $serverName = $prefixed.'-'.$i;
                }
                $i++;
            }
            $dest = $storeDir.$serverName;

            if (!@move_uploaded_file($tmpName, $dest)) {
                if (!@copy($tmpName, $dest)) { echo json_encode(['success'=>false,'message'=>'Could not save file']); return; }
            }

            $payload['report_card_file'] = 'uploads/school_report_cards/'.$serverName;

            $ok = $this->db->insert($tbl, $payload);
            if (!$ok) { @unlink($dest); echo json_encode(['success'=>false,'message'=>'Database error (file path insert)']); return; }
        }

        echo json_encode(['success'=>true,'message'=>'Report card uploaded']);
    }



    public function get_school_report_cards()
    {
        header('Content-Type: application/json');
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            echo json_encode(['success'=>false,'message'=>'No permission']); return;
        }

        $student_id = (int)$this->input->post('student_id');
        if ($student_id <= 0) { echo json_encode(['success'=>false,'message'=>'Student ID is required']); return; }

        $tbl = db_prefix().'school_report_card';

        // don’t select blob for JSON
        $this->db->select('id, student_school_id, term, upload_date, filename, report_card_file, mime_type, file_size, sha256, created_on', false);
        $this->db->where('student_school_id', $student_id);
        $this->db->order_by('upload_date', 'DESC');

        $rows = $this->db->get($tbl)->result_array();

        foreach ($rows as &$r) {
            $r['upload_date'] = !empty($r['upload_date']) ? date('M d, Y', strtotime($r['upload_date'])) : '';
            $name = trim((string)($r['filename'] ?? ''));
            if ($name === '' && !empty($r['report_card_file'])) $name = basename($r['report_card_file']);
            if ($name === '') {
                $ext = ($r['mime_type'] === 'application/pdf') ? '.pdf' :
                    (in_array($r['mime_type'], ['image/jpeg','image/jpg'], true) ? '.jpg' :
                    ($r['mime_type'] === 'image/png' ? '.png' : ''));
                $name = 'report_card_'.$r['id'].$ext;
            }
            $r['display_name'] = $name;
            $r['file_url']     = admin_url('student_sponsor_portal/download_school_report_card/'.$r['id']);
        } unset($r);

        echo json_encode(['success'=>true,'report_cards'=>$rows]);
    }

    public function download_school_report_card($id)
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) access_denied('student_sponsor_portal');

        $id  = (int)$id;
        $tbl = db_prefix().'school_report_card';

        $row = $this->db->get_where($tbl, ['id'=>$id])->row_array();
        if (!$row) show_404();

        $filename = trim((string)($row['filename'] ?? 'report_card_'.$id));
        if ($filename === '') $filename = 'report_card_'.$id;
        $mime = trim((string)($row['mime_type'] ?? 'application/octet-stream'));

        header('X-Content-Type-Options: nosniff');
        header('Content-Type: '.$mime);
        header('Content-Disposition: attachment; filename="'.str_replace('"','',$filename).'"');

        if ($this->db->field_exists('file_blob', $tbl) && !empty($row['file_blob'])) {
            $data = $row['file_blob'];
            header('Content-Length: '.strlen($data));
            echo $data; return;
        }

        $rel = trim((string)($row['report_card_file'] ?? ''));
        if ($rel === '') show_404();
        $path = FCPATH . ltrim($rel, '/');
        if (!is_file($path)) show_404();

        header('Content-Length: '.filesize($path));
        readfile($path);
    }

    public function delete_school_report_card()
    {
        header('Content-Type: application/json');

        if (!has_permission('student_sponsor_portal', '', 'delete')) {
            echo json_encode(['success'=>false,'message'=>'No permission', $this->security->get_csrf_token_name()=>$this->security->get_csrf_hash()]); return;
        }

        $id = (int)$this->input->post('report_card_id');
        if ($id <= 0) {
            echo json_encode(['success'=>false,'message'=>'Report card ID is required', $this->security->get_csrf_token_name()=>$this->security->get_csrf_hash()]); return;
        }

        $tbl = db_prefix().'school_report_card';
        $row = $this->db->get_where($tbl, ['id'=>$id])->row();
        if (!$row) {
            echo json_encode(['success'=>false,'message'=>'Report card not found', $this->security->get_csrf_token_name()=>$this->security->get_csrf_hash()]); return;
        }

        $this->db->where('id', $id)->delete($tbl);
        if ($this->db->affected_rows() > 0) {
            // if disk file stored, remove
            if (!empty($row->report_card_file)) {
                $abs = rtrim(FCPATH, '/\\') . '/' . ltrim($row->report_card_file, '/');
                if (is_file($abs)) @unlink($abs);
            }

            echo json_encode(['success'=>true,'message'=>'Report card deleted', $this->security->get_csrf_token_name()=>$this->security->get_csrf_hash()]);
        } else {
            echo json_encode(['success'=>false,'message'=>'Failed to delete', $this->security->get_csrf_token_name()=>$this->security->get_csrf_hash()]);
        }
    }





    /* ===================== Report Cards ===================== */

public function upload_report_card()
{
    header('Content-Type: application/json');

    // Permissions
    if (!has_permission('student_sponsor_portal', '', 'create')) {
        echo json_encode(['success' => false, 'message' => 'No permission']);
        return;
    }

    // ---------- Inputs ----------
    $student_id         = (int)$this->input->post('student_university_id');
    $report_card_term   = trim((string)$this->input->post('report_card_term'));
    $semester_end_month = ($this->input->post('semester_end_month') !== '') ? (int)$this->input->post('semester_end_month') : null;
    $semester_end_year  = ($this->input->post('semester_end_year')  !== '') ? (int)$this->input->post('semester_end_year')  : null;
    $display_filename   = trim((string)$this->input->post('display_filename')); // Optional

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
    $tmpName  = (string)$_FILES['report_card_file']['tmp_name'];
    $size     = (int)$_FILES['report_card_file']['size'];

    if (!is_uploaded_file($tmpName)) {
        echo json_encode(['success'=>false,'message'=>'Upload failed (temp not found)']); return;
    }

    // Size cap (adjust if needed)
    $maxBytes = 16 * 1024 * 1024; // 16 MB
    if ($size <= 0 || $size > $maxBytes) {
        echo json_encode(['success'=>false,'message'=>'File too large (max 16MB)']); return;
    }

    // ---------- MIME detection ----------
    $mime = null;
    if (function_exists('finfo_open')) {
        $f = finfo_open(FILEINFO_MIME_TYPE);
        if ($f) { $mime = @finfo_file($f, $tmpName); finfo_close($f); }
    }
    if (!$mime && !empty($_FILES['report_card_file']['type'])) {
        $mime = $_FILES['report_card_file']['type'];
    }
    $mime = strtolower((string)$mime) ?: 'application/octet-stream';

    // ---------- Visible/display filename (stored in DB column `filename`) ----------
    // Prefer the user-typed name, else original upload name, then sanitize.
    $display = $display_filename !== '' ? $display_filename : $origName;
    $display = preg_replace('/[^\p{L}\p{N}\-\._ ]/u', '_', $display);
    if ($display === '' || $display === '_') {
        $display = 'report_card_'.date('Ymd_His');
    }

    $sha256 = @hash_file('sha256', $tmpName) ?: null;

    $tbl = db_prefix().'university_report_card';

    // Base payload used in both storage modes
    $payload = [
        'student_university_id' => $student_id,
        'report_card_term'      => $report_card_term ?: null,
        // If you don’t track current_term separately, mirror the same value to satisfy NOT NULL schemas
        'current_term'          => $report_card_term ?: null,
        'semester_end_month'    => $semester_end_month,
        'semester_end_year'     => $semester_end_year,
        'upload_date'           => date('Y-m-d H:i:s'),
        'created_on'            => date('Y-m-d H:i:s'),
        'filename'              => $display, // human/display name
        'mime_type'             => $mime,
        'file_size'             => $size,
        'sha256'                => $sha256,
    ];

    // ---------- Storage mode detection ----------
    $hasBlob = $this->db->field_exists('file_blob', $tbl) || $this->db->field_exists('report_card_blob', $tbl);
    $blobCol = $this->db->field_exists('file_blob', $tbl) ? 'file_blob'
            : ($this->db->field_exists('report_card_blob', $tbl) ? 'report_card_blob' : null);

    if ($hasBlob && $blobCol) {
        // ===== DB BLOB MODE =====
        $bytes = @file_get_contents($tmpName);
        if ($bytes === false) {
            echo json_encode(['success'=>false,'message'=>'Cannot read uploaded file']); return;
        }

        $payload[$blobCol] = $bytes;
        if ($this->db->field_exists('report_card_file', $tbl)) {
            $payload['report_card_file'] = null; // path not used in BLOB mode
        }

        $ok = $this->db->insert($tbl, $payload);
        if (!$ok) {
            echo json_encode(['success'=>false,'message'=>'Database error (blob insert)']); return;
        }

    } else {
        // ===== DISK FILE MODE =====
        // Build a server filename prefixed with the student's internal ID (for uniqueness/traceability).
        $internalId = (string)($student['university_internal_id'] ?? '');
        $internalId = preg_replace('/[^\w\-]+/u', '_', $internalId);
        if ($internalId === '') { $internalId = 'UNSPEC'; }

        $prefixed = $internalId . '_' . $display;
        // Keep extension and limit length
        if (strlen($prefixed) > 200) {
            $ext = '';
            $dot = strrpos($prefixed, '.');
            if ($dot !== false && $dot >= 1) { $ext = substr($prefixed, $dot); }
            $prefixed = substr($prefixed, 0, 200 - strlen($ext)) . $ext;
        }

        $storeDir = rtrim(FCPATH, '/\\') . '/uploads/student_report_cards/';
        if (!is_dir($storeDir)) @mkdir($storeDir, 0755, true);

        // Avoid overwrite by appending -2, -3, ...
        $serverName = $prefixed;
        $counter = 2;
        while (file_exists($storeDir . $serverName)) {
            $dot = strrpos($prefixed, '.');
            if ($dot !== false && $dot >= 1) {
                $base = substr($prefixed, 0, $dot);
                $ext  = substr($prefixed, $dot);
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

        // Save relative path for later download
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

        // do NOT select the blob column in JSON
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
            // pretty date (your column is DATE in schema)
            $card['upload_date'] = !empty($card['upload_date'])
                ? date('M d, Y', strtotime($card['upload_date']))
                : '';

            // display name
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

            // download/stream url
            $card['file_url'] = admin_url('student_sponsor_portal/download_report_card/'.$card['id']);
        }
        unset($card);

        echo json_encode(['success'=>true,'report_cards'=>$cards]);
    }

    public function download_report_card($id)
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) access_denied('student_sponsor_portal');

        $id  = (int)$id;
        $tbl = db_prefix().'university_report_card';

        $row = $this->db->get_where($tbl, ['id'=>$id])->row_array();
        if (!$row) show_404();

        $filename = trim((string)($row['filename'] ?? 'report_card_'.$id));
        $filename = $filename !== '' ? $filename : ('report_card_'.$id);
        $mime     = trim((string)($row['mime_type'] ?? 'application/octet-stream'));

        // Prefer blob when present
        $blobCol = $this->db->field_exists('file_blob', $tbl) ? 'file_blob'
                : ($this->db->field_exists('report_card_blob', $tbl) ? 'report_card_blob' : null);

        // Output
        header('X-Content-Type-Options: nosniff');
        header('Content-Type: '.$mime);
        header('Content-Disposition: attachment; filename="'.str_replace('"','',$filename).'"');

        if ($blobCol && !empty($row[$blobCol])) {
            $data = $row[$blobCol];
            header('Content-Length: '.strlen($data));
            // Optional integrity: if sha256 present you can verify/log here
            echo $data;
            return;
        }

        // Fallback to file path
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



    // university ajac hadel 
    /* ===== Inline add endpoints used by the view modals ===== */

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

        $tbl = db_prefix().'countries'; // assumes fields: id, name, phone_code
        // Avoid duplicates
        $this->db->group_start()->where('name',$name)->or_where('phone_code',$code)->group_end();
        $exists = $this->db->get($tbl)->row();
        if ($exists) {
            $this->output->set_header('X-CSRF-TOKEN: '.$this->security->get_csrf_hash());
            echo json_encode([
                'success'      => true,
                'message'      => 'Country already existed and was selected',
                'country_id'   => (int)$exists->id,
                'country_name' => (string)$exists->name,
                'phone_code'   => (string)$exists->phone_code,
            ]);
            return;
        }

        $this->db->insert($tbl, ['name'=>$name, 'phone_code'=>$code]);
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

        $tbl = db_prefix().'university_name'; // assumes fields: id, name
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

        $tbl = db_prefix().'university_program'; // assumes fields: id, name
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

        $tbl = db_prefix().'bank'; // assumes fields: id, name
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
}