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
            // don’t fail the sponsor insert because of this
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
        $this->db->where('id', $sponsor_id);
        return (bool) $this->db->delete($this->table);
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
            // If you don’t actually have tblcountry, treat any value as invalid to avoid FK errors
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
            'entity_type'                 => $r['entity_type'] ?? 'sponsor',
            'staff_id'                    => $r['staff_id'] ?? '',
            'active'                      => $r['active'] ?? '', // exists in your schema
        ];
    }
}
