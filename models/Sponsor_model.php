<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Sponsor_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ------------------------- CREATE ------------------------- */
    public function add($data)
    {
        $country_id = isset($data['country_id']) && $data['country_id'] !== '' ? (int)$data['country_id'] : null;
        if ($country_id === null) {
            $country_id = $this->get_default_country_id(); // may still be null
        }
        if (!$this->is_valid_country_id($country_id)) {
            $country_id = null; // avoid FK violation
        }
        $insert_data = [
            'entity_type'                   => 'sponsor',
            'name'                          => $data['name'] ?? '',
            'email'                         => $data['email'] ?? null,
            'contact_no'                    => $data['contact_no'] ?? null,
            'address'                       => $data['address'] ?? null,
            'city'                          => $data['city'] ?? null,
            'zip'                           => $data['zip'] ?? null,
            'country_id'                    => $country_id,
            'state_id'                      => $data['state_id'] ?? null,
            'state'                         => $data['state'] ?? null, // text fallback if you use it
            'bank_id'                       => $data['bank_id'] ?? null,
            'sponsor_type'                  => $data['sponsor_type'] ?? null,
            'sponsor_occupation'            => $data['sponsor_occupation'] ?? null,
            'sponsor_bank_branch_info'      => $data['sponsor_bank_branch_info'] ?? null,
            'sponsor_bank_branch_number'    => $data['sponsor_bank_branch_number'] ?? null,
            'sponsor_bank_account_no'       => $data['sponsor_bank_account_no'] ?? null,
            'membership_start_date'=> $data['membership_start_date'] ?? null,
            'membership_end_date' => $data['membership_end_date'] ?? null,
            'sponsor_frequency'             => $data['sponsor_frequency'] ?? null,
        ];

        $this->db->insert(db_prefix() . 'sponsor_records', $insert_data);
        $sponsor_id = $this->db->insert_id();

        // Optional: create a client/contact just with basic info
        if (!empty($data['name'])) {
            $this->load->model('clients_model');
            $this->clients_model->add([
                'firstname' => $data['name'] ?? '',
                'email'     => $data['email'] ?? '',
                'phonenumber' => $data['contact_no'] ?? '',
                'custom_fields' => ['contact_type' => 'sponsor']
            ]);
        }

        return $sponsor_id;
    }

    /* ------------------------- READ ------------------------- */
   public function get_all()
{
    $sp = db_prefix().'sponsor_records';
    $this->db->from($sp . ' sp');

    $meta = $this->country_table_meta();
    if ($meta) {
        $this->db->select('sp.*, c.'.$meta['name'].' as country_name');
        $this->db->join($meta['table'].' c', 'c.'.$meta['pk'].' = sp.country_id', 'left');
    } else {
        $this->db->select('sp.*');
    }

    if ($this->db->table_exists(db_prefix().'bank')) {
        $this->db->select('b.name as bank_name');
        $this->db->join(db_prefix().'bank b', 'b.id = sp.bank_id', 'left');
    }

    $results = $this->db->get()->result_array();

    $mapped = [];
    foreach ($results as $r) {
        $mapped[] = $this->map_database_to_form_fields($r);
    }
    return $mapped;
}

    public function grant_access($sponsor_id)
{
    $this->load->model('sponsor_model');
    $sponsor = $this->sponsor_model->get($sponsor_id);

    if (!$sponsor) {
        set_alert('danger', 'Sponsor not found');
        // redirect(admin_url('sponsor'));
    }

    // Prepare staff data
    $staffData = [
        'firstname'   => $sponsor->name,
        'lastname'    => '', // can take from sponsor table if available
        'email'       => $sponsor->email,
        'password'    => app_generate_password(), // or input value
        'active'      => 1,
        'role'        => 3, // assign role id (example: sponsor role)
    ];

    // Insert into staff table
    $this->db->insert('tblstaff', $staffData);
    $staff_id = $this->db->insert_id();

    // Update sponsor record with staff_id
    $this->db->where('id', $sponsor_id);
    $this->db->update('tblsponsor', ['staff_id' => $staff_id]);

    set_alert('success', 'Portal access granted');
    // redirect(admin_url('sponsor/edit/' . $sponsor_id));
}

public function revoke_access($sponsor_id)
{
    $sponsor = $this->sponsor_model->get($sponsor_id);

    if ($sponsor && $sponsor->staff_id) {
        // Disable staff account
        $this->db->where('staffid', $sponsor->staff_id);
        $this->db->update('tblstaff', ['active' => 0]);

        // Optionally clear sponsor staff_id
        $this->db->where('id', $sponsor_id);
        $this->db->update('tblsponsor', ['staff_id' => null]);

        set_alert('success', 'Portal access revoked');
    } else {
        set_alert('danger', 'No staff account linked');
    }

    // redirect(admin_url('sponsor/edit/' . $sponsor_id));
}


    public function get_by_id($sponsor_id)
{
    $sp = db_prefix().'sponsor_records';
    $this->db->from($sp . ' sp');

    $meta = $this->country_table_meta();
    if ($meta) {
        $this->db->select('sp.*, c.'.$meta['name'].' as country_name');
        $this->db->join($meta['table'].' c', 'c.'.$meta['pk'].' = sp.country_id', 'left');
    } else {
        $this->db->select('sp.*');
    }

    if ($this->db->table_exists(db_prefix().'bank')) {
        $this->db->select('b.name as bank_name');
        $this->db->join(db_prefix().'bank b', 'b.id = sp.bank_id', 'left');
    }

    $this->db->where('sp.id', $sponsor_id);
    $row = $this->db->get()->row_array();
    if (!$row) { return null; }

    return $this->map_database_to_form_fields($row);
}


    public function count_all()
    {
        return $this->db->count_all_results(db_prefix() . 'sponsor_records');
    }

    /* ------------------------- UPDATE ------------------------- */
    public function update($data, $sponsor_id)
{
    $update = [];

    // Basic text fields present in your table
    $text_fields = [
        'name','email','contact_no','address','city','zip',
        'sponsor_type','sponsor_occupation',
        'sponsor_bank_branch_info','sponsor_bank_branch_number',
        'sponsor_bank_account_no','sponsor_frequency','state'
    ];
    foreach ($text_fields as $f) {
        if (array_key_exists($f, $data)) {
            $update[$f] = $data[$f] !== '' ? $data[$f] : null;
        }
    }

    // ---- Country (FK) with validation ----
    if (array_key_exists('country_id', $data)) {
        $cid = ($data['country_id'] === '' ? null : (int)$data['country_id']);
        if (!$this->is_valid_country_id($cid)) { $cid = null; }
        $update['country_id'] = $cid;
    }

    // Other ID / FK fields (do NOT include country_id here)
    $id_fields = ['state_id','bank_id'];
    foreach ($id_fields as $f) {
        if (array_key_exists($f, $data)) {
            $update[$f] = ($data[$f] === '' ? null : (int)$data[$f]);
        }
    }

    // Dates (use your actual columns)
    $date_fields = ['membership_start_date','membership_end_date'];
    foreach ($date_fields as $f) {
        if (array_key_exists($f, $data)) {
            $update[$f] = $data[$f] !== '' ? $data[$f] : null;
        }
    }

    if (!$update) { return false; }

    $this->db->where('id', $sponsor_id);
    return $this->db->update(db_prefix().'sponsor_records', $update);
}

    public function update_sponsor($data, $sponsor_id)
    {
        return $this->update($data, $sponsor_id);
    }

    /* ------------------------- DELETE ------------------------- */
    public function delete_sponsor($sponsor_id)
    {
        $this->db->where('id', $sponsor_id);
        return $this->db->delete(db_prefix().'sponsor_records');
    }
    public function delete($sponsor_id) { return $this->delete_sponsor($sponsor_id); }

    /* ------------------------- HELPERS ------------------------- */

    // Prefer Perfex default tblcountries; fall back to tblcountry; else null.
    private function resolve_country_table()
    {
        if ($this->db->table_exists(db_prefix().'countries')) { return db_prefix().'countries'; }
        if ($this->db->table_exists(db_prefix().'country'))   { return db_prefix().'country'; }
        return null;
    }

    private function get_default_country_id()
    {
        $meta = $this->country_table_meta();
        if (!$meta || $meta['table'] !== db_prefix().'country') {
            return null; // don’t guess if tblcountry isn’t available
        }
        $this->db->select($meta['pk'])->limit(1);
        $row = $this->db->get($meta['table'])->row();
        return $row ? (int)$row->{$meta['pk']} : null;
    }

    private function is_valid_country_id($id)
    {
        if ($id === null) return true; // nullable FK is OK (if column allows NULL)
        $meta = $this->country_table_meta();
        if (!$meta || $meta['table'] !== db_prefix().'country') return false;

        $this->db->where($meta['pk'], (int)$id);
        return (bool) $this->db->get($meta['table'])->row();
    }
    private function map_database_to_form_fields($r)
    {
        return [
            'id'                             => $r['id'] ?? '',
            'name'                           => $r['name'] ?? '',
            'email'                          => $r['email'] ?? '',
            'contact_no'                     => $r['contact_no'] ?? '',
            'phone'                          => $r['contact_no'] ?? '', // display-only
            'address'                        => $r['address'] ?? '',
            'city'                           => $r['city'] ?? '',
            'zip'                            => $r['zip'] ?? '',
            'country_id'                     => $r['country_id'] ?? '',
            'country_name'                   => $r['country_name'] ?? '',
            'state_id'                       => $r['state_id'] ?? '',
            'state'                          => $r['state'] ?? '',      // text state if you use it
            'bank_id'                        => $r['bank_id'] ?? '',
            'bank_name'                      => $r['bank_name'] ?? '',
            'sponsor_type'                   => $r['sponsor_type'] ?? '',
            'sponsor_occupation'             => $r['sponsor_occupation'] ?? '',
            'sponsor_bank_branch_info'       => $r['sponsor_bank_branch_info'] ?? '',
            'sponsor_bank_branch_number'     => $r['sponsor_bank_branch_number'] ?? '',
            'sponsor_bank_account_no'        => $r['sponsor_bank_account_no'] ?? '',
            'membership_start_date'          => $r['membership_start_date'] ?? '',
            'membership_end_date'=> $r['membership_end_date'] ?? '',
            'sponsor_frequency'              => $r['sponsor_frequency'] ?? '',
            'entity_type'                    => $r['entity_type'] ?? 'sponsor',
            'staff_id'                       => $r['staff_id'] ?? '',
            'active'                         => $r['active'] ?? '', // only if your table has this column
        ];
    }
private function country_table_meta()
{
    // Your FK points to tblcountry(id)
    if ($this->db->table_exists(db_prefix().'country')) {
        return ['table' => db_prefix().'country', 'pk' => 'id', 'name' => 'name'];
    }
    // Fallback for reads if someone also has tblcountries
    if ($this->db->table_exists(db_prefix().'countries')) {
        return ['table' => db_prefix().'countries', 'pk' => 'country_id', 'name' => 'short_name'];
    }
    return null;
}


}
