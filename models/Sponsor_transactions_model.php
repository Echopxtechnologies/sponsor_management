<?php defined('BASEPATH') or exit('No direct script access allowed');

class Sponsor_transactions_model extends App_Model
{
    /** @var string fully-qualified table names (with db_prefix) */
    protected $txn_tbl;
    protected $pay_tbl;


    public function __construct()
    {
        parent::__construct();
        $this->txn_tbl = db_prefix() . 'sponsor_transactions';
        $this->pay_tbl = db_prefix() . 'sponsor_payments';
        $this->ensure_tables();
    }

    /** Compute next due date from an anchor date and a frequency */
private function compute_next_due($type, $anchorYmd)
{
    if (!$anchorYmd) return null;
    switch ($type) {
        case 'monthly':   return date('Y-m-d', strtotime($anchorYmd . ' +1 month'));
        case 'quarterly': return date('Y-m-d', strtotime($anchorYmd . ' +3 month'));
        case 'yearly':    return date('Y-m-d', strtotime($anchorYmd . ' +1 year'));
        case 'one_time':  return null;
        // 'custom' (or unknown): do not auto-calc; let user set manually
        default:          return null;
    }
}

/** Recalc totals + last/next dates after any payment change */
public function recompute_after_payment($txn_id)
{
    $txn_id = (int)$txn_id;

    $txn = $this->db->where('id', $txn_id)->get($this->txn_tbl)->row();
    if (!$txn) return false;

    $agg = $this->db->select('SUM(amount) AS paid, MAX(payment_date) AS last_dt', false)
                    ->where('transaction_id', $txn_id)
                    ->get($this->pay_tbl)->row();
    $paid = $agg && $agg->paid ? (float)$agg->paid : 0.0;
    $last = $agg && $agg->last_dt ? $agg->last_dt : null;

    // Anchor for next due: prefer last payment, else sponsorship start
    $anchor = $last ?: ($txn->sponsorship_start ?: null);
    $next   = ($txn->payment_type === 'custom') ? $txn->next_payment_due
                                                : $this->compute_next_due($txn->payment_type, $anchor);

    $update = [
        'amount_paid'       => $paid,
        'last_payment_date' => $last,
        'next_payment_due'  => $next,
    ];

    // Optional: pre-schedule the due reminder
    if ($next && (int)$txn->due_reminder_active === 1 && (int)$txn->due_reminder_days_before > 0) {
        $update['scheduled_due_reminder_date'] =
            date('Y-m-d', strtotime($next . ' -' . (int)$txn->due_reminder_days_before . ' days'));
    } else {
        $update['scheduled_due_reminder_date'] = null;
    }

    return $this->db->where('id', $txn_id)->update($this->txn_tbl, $update);
}

/** Recalc only next_payment_due after changing payment_type/sponsorship dates */
public function recompute_next_due_from_type($txn_id)
{
    $txn_id = (int)$txn_id;
    $txn = $this->db->where('id', $txn_id)->get($this->txn_tbl)->row();
    if (!$txn) return false;

    // Keep old to detect real change
    $oldNext = $txn->next_payment_due ?: null;

    if ($txn->payment_type === 'custom') {
        // Respect manually-entered next_payment_due for custom
        // Still keep schedule up to date
        $this->update_scheduled_reminders($txn_id);
        return true;
    }

    $anchor = $txn->last_payment_date ?: ($txn->sponsorship_start ?: null);
    $next   = $this->compute_next_due($txn->payment_type, $anchor);

    $update = ['next_payment_due' => $next];

    if ($next && (int)$txn->due_reminder_active === 1 && (int)$txn->due_reminder_days_before > 0) {
        $update['scheduled_due_reminder_date'] =
            date('Y-m-d', strtotime($next . ' -' . (int)$txn->due_reminder_days_before . ' days'));
    } else {
        $update['scheduled_due_reminder_date'] = null;
    }

    $this->db->where('id', $txn_id)->update($this->txn_tbl, $update);

    // If next due actually changed => new cycle
    if ($next !== $oldNext) {
        $this->reset_due_cycle($txn_id);               // <-- pass the id
        $this->update_scheduled_reminders($txn_id);
    } else {
        // keep schedule fresh if only days/active changed
        $this->update_scheduled_reminders($txn_id);
    }

    return true;
}

/** Recalc totals, anchor, next due after any payment add/update/delete */
/** Recalc totals, anchor, next due after any payment add/update/delete */
public function recompute_after_payment_change($txn_id): bool
{
    $txn_id = (int)$txn_id;

    $txn = $this->db->where('id', $txn_id)->get($this->txn_tbl)->row();
    if (!$txn) return false;

    // Aggregate payments
    $agg = $this->db->select('SUM(amount) AS paid, MAX(payment_date) AS last_dt', false)
                    ->where('transaction_id', $txn_id)
                    ->get($this->pay_tbl)->row();
    $paid = $agg && $agg->paid ? (float)$agg->paid : 0.0;
    $last = $agg && $agg->last_dt ? $agg->last_dt : null;

    $oldNext = $txn->next_payment_due ?: null;

    // Compute next due (respect custom)
    if ($txn->payment_type === 'custom') {
        $next = $txn->next_payment_due;
    } else {
        $anchor = $last ?: ($txn->sponsorship_start ?: null);
        $next   = $this->compute_next_due($txn->payment_type, $anchor);
    }

    $update = [
        'amount_paid'       => $paid,
        'last_payment_date' => $last,
        'next_payment_due'  => $next,
    ];

    if ($next && (int)$txn->due_reminder_active === 1 && (int)$txn->due_reminder_days_before > 0) {
        $update['scheduled_due_reminder_date'] =
            date('Y-m-d', strtotime($next . ' -' . (int)$txn->due_reminder_days_before . ' days'));
    } else {
        $update['scheduled_due_reminder_date'] = null;
    }

    $this->db->where('id', $txn_id)->update($this->txn_tbl, $update);

    // If due date changed => start a fresh cycle; otherwise just keep schedule fresh
    if ($next !== $oldNext) {
        $this->reset_due_cycle($txn_id);
    }
    $this->update_scheduled_reminders($txn_id);

    return true;
}




    public function list_payments(array $f, int $limit = 25, int $offset = 0)
{
    $db = $this->db;

    $db->select("
        p.id,
        p.transaction_id,
        p.sponsor_id,
        p.payment_date,
        p.amount,
        p.currency,
        p.note,
        p.created_at,
        p.created_by,
        s.name AS sponsor_name,
        t.total_amount,
        t.currency AS txn_currency,
        t.school_student_id,
        t.university_student_id,
        COALESCE(ss.name, us.name) AS student_name,
        CASE WHEN t.school_student_id IS NOT NULL THEN 'school'
             WHEN t.university_student_id IS NOT NULL THEN 'university'
             ELSE '' END AS student_type
    ");
    $db->from("$this->pay_tbl p");
    $db->join("$this->txn_tbl t", "t.id = p.transaction_id", "left");
    $db->join("tblsponsor_records s", "s.id = p.sponsor_id", "left");
    $db->join(db_prefix().'school_students ss', "ss.id = t.school_student_id", "left");
    $db->join(db_prefix().'university_students us', "us.id = t.university_student_id", "left");

    // filters
    if (!empty($f['date_from'])) $db->where('p.payment_date >=', $f['date_from']);
    if (!empty($f['date_to']))   $db->where('p.payment_date <=', $f['date_to']);
    if (!empty($f['sponsor_id']))   $db->where('p.sponsor_id', (int)$f['sponsor_id']);
    if (!empty($f['currency']))     $db->where('p.currency', $f['currency']);
    if (!empty($f['min_amount']))   $db->where('p.amount >=', (float)$f['min_amount']);
    if (!empty($f['max_amount']))   $db->where('p.amount <=', (float)$f['max_amount']);
    if (!empty($f['has_note']))     $db->where("p.note <> ''");
    if (!empty($f['q']))            $db->group_start()
                                         ->like('p.note', $f['q'])
                                         ->or_like('s.name', $f['q'])
                                         ->or_like('ss.name', $f['q'])
                                         ->or_like('us.name', $f['q'])
                                       ->group_end();
    if (!empty($f['created_by']))   $db->where('p.created_by', (int)$f['created_by']);
    if ($f['student_type'] === 'school')     $db->where('t.school_student_id IS NOT NULL', null, false);
    if ($f['student_type'] === 'university') $db->where('t.university_student_id IS NOT NULL', null, false);

    $db->order_by('p.payment_date', 'DESC');
    $db->limit($limit, $offset);

    return $db->get()->result();
}

public function count_payments(array $f): int
{
    $db = $this->db;

    $db->from("$this->pay_tbl p");
    $db->join("$this->txn_tbl t", "t.id = p.transaction_id", "left");
    $db->join("tblsponsor_records s", "s.id = p.sponsor_id", "left");
    $db->join(db_prefix().'school_students ss', "ss.id = t.school_student_id", "left");
    $db->join(db_prefix().'university_students us', "us.id = t.university_student_id", "left");

    if (!empty($f['date_from'])) $db->where('p.payment_date >=', $f['date_from']);
    if (!empty($f['date_to']))   $db->where('p.payment_date <=', $f['date_to']);
    if (!empty($f['sponsor_id']))   $db->where('p.sponsor_id', (int)$f['sponsor_id']);
    if (!empty($f['currency']))     $db->where('p.currency', $f['currency']);
    if (!empty($f['min_amount']))   $db->where('p.amount >=', (float)$f['min_amount']);
    if (!empty($f['max_amount']))   $db->where('p.amount <=', (float)$f['max_amount']);
    if (!empty($f['has_note']))     $db->where("p.note <> ''");
    if (!empty($f['q']))            $db->group_start()
                                         ->like('p.note', $f['q'])
                                         ->or_like('s.name', $f['q'])
                                         ->or_like('ss.name', $f['q'])
                                         ->or_like('us.name', $f['q'])
                                       ->group_end();
    if (!empty($f['created_by']))   $db->where('p.created_by', (int)$f['created_by']);
    if ($f['student_type'] === 'school')     $db->where('t.school_student_id IS NOT NULL', null, false);
    if ($f['student_type'] === 'university') $db->where('t.university_student_id IS NOT NULL', null, false);

    return (int)$db->count_all_results();
}
/** Treat '' and null as NULL; otherwise cast to int. */
private function to_int_or_null($in, string $key)
{
    if (!array_key_exists($key, $in)) return null;
    $v = $in[$key];
    if ($v === '' || $v === null) return null;
    $v = (int)$v;
    return $v > 0 ? $v : null; // avoid 0 which breaks FKs
}

/** Treat '' and null as NULL for date-like fields. */
private function to_date_or_null($v)
{
    return ($v === '' || $v === null) ? null : $v;
}

/** Exactly one of school_student_id or university_student_id must be set. */
private function has_exactly_one_student(array $row): bool
{
    $s = isset($row['school_student_id']) ? (int)$row['school_student_id'] : 0;
    $u = isset($row['university_student_id']) ? (int)$row['university_student_id'] : 0;
    $s = $s > 0 ? 1 : 0;
    $u = $u > 0 ? 1 : 0;
    return ($s + $u) === 1;
}

private function clean($in)
{
    // fallback logic for the "days" fields
    $due_days = isset($in['due_reminder_days_before'])
        ? (int)$in['due_reminder_days_before']
        : (isset($in['days_before_end']) ? (int)$in['days_before_end'] : 15);

    $renewal_days = isset($in['renewal_reminder_days_before'])
        ? (int)$in['renewal_reminder_days_before']
        : (isset($in['days_before_end']) ? (int)$in['days_before_end'] : 15);

    $row = [
        'sponsor_id'               => (int)($in['sponsor_id'] ?? 0),
        'school_student_id'        => $this->to_int_or_null($in, 'school_student_id'),
        'university_student_id'    => $this->to_int_or_null($in, 'university_student_id'),
        'total_amount'             => (float)($in['total_amount'] ?? 0),
        'amount_paid'              => (float)($in['amount_paid'] ?? 0),
        'currency'                 => $in['currency'] ?? 'INR',

        // Only keep next_payment_due from input if it was actually submitted
        'next_payment_due'         => array_key_exists('next_payment_due', $in)
                                      ? $this->to_date_or_null($in['next_payment_due'])
                                      : null, // will be ignored on update if not present (see note below)

        'payment_type'             => $in['payment_type'] ?? 'one_time',
        'last_payment_date'        => $this->to_date_or_null($in['last_payment_date'] ?? null),

        'due_reminder_active'      => !empty($in['due_reminder_active']) ? 1 : 0,
        'due_reminder_days_before' => max(0, $due_days),
        'scheduled_due_reminder_date' => $this->to_date_or_null($in['scheduled_due_reminder_date'] ?? null),
        'due_reminder_sent'        => !empty($in['due_reminder_sent']) ? 1 : 0,

        'sponsorship_start'        => $this->to_date_or_null($in['sponsorship_start'] ?? null),
        'sponsorship_end'          => $this->to_date_or_null($in['sponsorship_end'] ?? null),

        'renewal_reminder_active'  => !empty($in['renewal_reminder_active']) ? 1 : 0,
        'renewal_reminder_days_before' => max(0, $renewal_days),
        'scheduled_renewal_reminder'   => $this->to_date_or_null($in['scheduled_renewal_reminder'] ?? null),
        'renewal_reminder_sent'    => !empty($in['renewal_reminder_sent']) ? 1 : 0,
    ];

    if (!$this->has_exactly_one_student($row)) {
        if (empty($row['school_student_id']))     $row['school_student_id'] = null;
        if (empty($row['university_student_id'])) $row['university_student_id'] = null;
    }

    return $row;
}




    /* ---------------------------- Public API ---------------------------- */

    /**
     * Get single transaction (with joins) or list (newest first).
     * Returns object (single) or array of objects (list) to match Perfex style.
     */
    public function get($id = null)
    {
        $this->db
            ->select("
                t.*,
                s.name            AS sponsor_name,
                ss.name           AS school_student_name,
                us.name           AS university_student_name
            ")
            ->from($this->txn_tbl . ' t')
            ->join(db_prefix() . 'sponsor_records   s',  's.id  = t.sponsor_id',           'left')
            ->join(db_prefix() . 'school_students   ss', 'ss.id = t.school_student_id',     'left')
            ->join(db_prefix() . 'university_students us','us.id = t.university_student_id','left');

        if ($id) {
            $this->db->where('t.id', (int)$id);
            return $this->db->get()->row();
        }

        $this->db->order_by('t.id', 'DESC');
        return $this->db->get()->result();
    }

    /** Insert and return new ID */
    // public function create(array $data)
    // {
    //     $clean = $this->clean($data);

    //     // require sponsor and exactly one student type
    //     if (!$clean['sponsor_id'] || !$this->has_exactly_one_student($clean)) {
    //         return false;
    //     }

    //     // timestamps if columns exist
    //     if ($this->db->field_exists('created_at', $this->txn_tbl)) {
    //         $clean['created_at'] = date('Y-m-d H:i:s');
    //     }
    //     if ($this->db->field_exists('updated_at', $this->txn_tbl)) {
    //         $clean['updated_at'] = date('Y-m-d H:i:s');
    //     }

    //     $this->db->insert($this->txn_tbl, $clean);
    //     return (int)$this->db->insert_id();
    // }

   public function create(array $data)
{
    $clean = $this->clean($data);
    if (!$clean['sponsor_id'] || !$this->has_exactly_one_student($clean)) return false;

    if ($this->db->field_exists('created_at', $this->txn_tbl)) $clean['created_at'] = date('Y-m-d H:i:s');
    if ($this->db->field_exists('updated_at', $this->txn_tbl)) $clean['updated_at'] = date('Y-m-d H:i:s');

    $this->db->insert($this->txn_tbl, $clean);
    $id = (int)$this->db->insert_id();
    if ($id) {
        $this->recompute_next_due_from_type($id);
        $this->update_scheduled_reminders($id);
    }
    return $id;
}


    /** Update by ID */
    // public function update($id, array $data)
    // {
    //     $clean = $this->clean($data);

    //     if (!$clean['sponsor_id'] || !$this->has_exactly_one_student($clean)) {
    //         return false;
    //     }

    //     if ($this->db->field_exists('updated_at', $this->txn_tbl)) {
    //         $clean['updated_at'] = date('Y-m-d H:i:s');
    //     }

    //     $this->db->where('id', (int)$id)->update($this->txn_tbl, $clean);
    //     return $this->db->affected_rows() > 0;
    // }
    
public function update($id, array $data)
{
    $clean = $this->clean($data);
    if (!$clean['sponsor_id'] || !$this->has_exactly_one_student($clean)) return false;

    // If next_payment_due not posted (readonly omitted), don't overwrite DB
    if (!array_key_exists('next_payment_due', $data)) unset($clean['next_payment_due']);

    if ($this->db->field_exists('updated_at', $this->txn_tbl)) {
        $clean['updated_at'] = date('Y-m-d H:i:s');
    }

    $this->db->where('id', (int)$id)->update($this->txn_tbl, $clean);

    // Always ensure computed fields are correct even if 0 rows affected
    $this->recompute_next_due_from_type($id);
    $this->update_scheduled_reminders($id);

    return true;
}



    /** Hard-delete a transaction and its payments */
    public function delete($id)
    {
        $id = (int)$id;
        // delete children first
        $this->db->where('transaction_id', $id)->delete($this->pay_tbl);
        $this->db->where('id', $id)->delete($this->txn_tbl);
        return $this->db->affected_rows() > 0;
    }
    

    /**
     * Add a payment and auto-recompute amount_paid on parent.
     * Returns inserted payment ID or false.
     */
    public function add_payment(array $in)
{
    $row = [
        'transaction_id' => (int)($in['transaction_id'] ?? 0),
        'sponsor_id'     => (int)($in['sponsor_id'] ?? 0),
        'student_id'     => !empty($in['student_id']) ? (int)$in['student_id'] : null,
        'payment_date'   => $this->to_date_or_null($in['payment_date'] ?? null),
        'amount'         => (float)($in['amount'] ?? 0),
        'currency'       => trim($in['currency'] ?? 'INR'),
        'note'           => trim($in['note'] ?? ''),
        'created_by'     => (int)($in['created_by'] ?? 0),
        'created_at'     => date('Y-m-d H:i:s'),
    ];

    if ($row['transaction_id'] <= 0 || $row['amount'] <= 0) {
        return false;
    }

    $this->db->insert($this->pay_tbl, $row);
    $pid = (int)$this->db->insert_id();

    if ($pid > 0) {
        // Recompute totals/anchor/next due and (re)schedule reminders
        $this->recompute_after_payment_change((int)$row['transaction_id']);
    }

    return $pid;
}

    /** Force recompute of amount_paid from payments table */
    public function recompute_amount_paid(int $transaction_id): void
    {
        $sum = $this->db->select_sum('amount')
                        ->from($this->pay_tbl)
                        ->where('transaction_id', $transaction_id)
                        ->get()->row();
        $paid = (float)($sum->amount ?? 0);

        $this->db->where('id', $transaction_id)
                 ->update($this->txn_tbl, [
                     'amount_paid' => $paid,
                     'last_payment_date' => $this->latest_payment_date($transaction_id),
                     'updated_at' => date('Y-m-d H:i:s'),
                 ]);
    }

    public function get_payment(int $payment_id)
{
    return $this->db->where('id', $payment_id)->get($this->pay_tbl)->row();
}

public function update_payment(int $payment_id, array $in): bool
{
    // Need parent txn id to recompute after update
    $existing = $this->db->where('id', $payment_id)->get($this->pay_tbl)->row();
    if (!$existing) return false;
    $txn_id = (int)$existing->transaction_id;

    $row = [
        'payment_date' => $this->to_date_or_null($in['payment_date'] ?? null),
        'amount'       => (float)($in['amount'] ?? 0),
        'currency'     => trim($in['currency'] ?? 'INR'),
        'note'         => trim($in['note'] ?? ''),
    ];

    $this->db->where('id', $payment_id)->update($this->pay_tbl, $row);
    $ok = $this->db->affected_rows() > 0;

    // Even if no rows affected (same data), safely recompute to keep schedule in sync
    $this->recompute_after_payment_change($txn_id);

    return $ok;
}

public function delete_payment(int $payment_id): bool
{
    // Find parent txn id first
    $p = $this->db->where('id', $payment_id)->get($this->pay_tbl)->row();
    $txn_id = $p ? (int)$p->transaction_id : 0;

    $this->db->where('id', $payment_id)->delete($this->pay_tbl);
    $ok = $this->db->affected_rows() > 0;

    if ($ok && $txn_id > 0) {
        $this->recompute_after_payment_change($txn_id);
    }

    return $ok;
}



    /* ---------------------------- Internals ---------------------------- */

    

    
    private function latest_payment_date(int $transaction_id)
    {
        $row = $this->db->select_max('payment_date')
                        ->from($this->pay_tbl)
                        ->where('transaction_id', $transaction_id)
                        ->get()->row();
        $d = $row && !empty($row->payment_date) ? $row->payment_date : null;
        return $d ? $d : null;
    }

    /* ---------------------- Install / Ensure Tables --------------------- */

    private function ensure_tables(): void
    {
        // transactions
        if (!$this->db->table_exists($this->txn_tbl)) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `{$this->txn_tbl}` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `sponsor_id` int(11) NOT NULL,
                    `school_student_id` int(11) DEFAULT NULL,
                    `university_student_id` int(11) DEFAULT NULL,
                    `total_amount` decimal(15,2) NOT NULL DEFAULT 0,
                    `amount_paid` decimal(15,2) NOT NULL DEFAULT 0,
                    `currency` varchar(10) NOT NULL DEFAULT 'INR',

                    `payment_type` varchar(30) NOT NULL DEFAULT 'one_time',
                    `last_payment_date` date DEFAULT NULL,
                    `next_payment_due` date DEFAULT NULL,

                    `due_reminder_active` tinyint(1) NOT NULL DEFAULT 0,
                    `due_reminder_days_before` int(11) NOT NULL DEFAULT 15,
                    `scheduled_due_reminder_date` date DEFAULT NULL,
                    `due_reminder_sent` tinyint(1) NOT NULL DEFAULT 0,

                    `sponsorship_start` date DEFAULT NULL,
                    `sponsorship_end` date DEFAULT NULL,

                    `renewal_reminder_active` tinyint(1) NOT NULL DEFAULT 0,
                    `renewal_reminder_days_before` int(11) NOT NULL DEFAULT 15,
                    `scheduled_renewal_reminder` date DEFAULT NULL,
                    `renewal_reminder_sent` tinyint(1) NOT NULL DEFAULT 0,

                    `created_at` datetime DEFAULT NULL,
                    `updated_at` datetime DEFAULT NULL,

                    PRIMARY KEY (`id`),
                    KEY `sponsor_id` (`sponsor_id`),
                    KEY `school_student_id` (`school_student_id`),
                    KEY `university_student_id` (`university_student_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            ");
        }

        // payments
        if (!$this->db->table_exists($this->pay_tbl)) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `{$this->pay_tbl}` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `transaction_id` int(11) NOT NULL,
                    `sponsor_id` int(11) NOT NULL,
                    `student_id` int(11) DEFAULT NULL,
                    `payment_date` date DEFAULT NULL,
                    `amount` decimal(15,2) NOT NULL DEFAULT 0,
                    `currency` varchar(10) NOT NULL DEFAULT 'INR',
                    `note` text,
                    `created_by` int(11) DEFAULT NULL,
                    `created_at` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `transaction_id` (`transaction_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            ");
        }
        // In Sponsor_transactions_model::ensure_tables(), after table creation blocks:
        if ($this->db->table_exists($this->txn_tbl)) {
            // due_reminder_sent_at (when the -15 email went)
            if (!$this->db->field_exists('due_reminder_sent_at', $this->txn_tbl)) {
                $this->db->query("ALTER TABLE `{$this->txn_tbl}` ADD `due_reminder_sent_at` datetime DEFAULT NULL");
            }
            // due_day_email_sent (email sent on the exact due date) + timestamp
            if (!$this->db->field_exists('due_day_email_sent', $this->txn_tbl)) {
                $this->db->query("ALTER TABLE `{$this->txn_tbl}` ADD `due_day_email_sent` tinyint(1) NOT NULL DEFAULT 0");
            }
            if (!$this->db->field_exists('due_day_email_sent_at', $this->txn_tbl)) {
                $this->db->query("ALTER TABLE `{$this->txn_tbl}` ADD `due_day_email_sent_at` datetime DEFAULT NULL");
            }
        }

    }

    // In your Sponsor_transactions_model.php

// Make sure your method in Sponsor_transactions_model.php looks exactly like this:

// Replace your get_due_email_template method in the model with this:

public function get_due_email_template($transaction_id)
{
    $txn = $this->get($transaction_id);
    if (!$txn) return false;

    // Get sponsor name
    $sponsor = $this->db->select('name')->where('id', $txn->sponsor_id)->get(db_prefix().'sponsor_records')->row();
    $sponsor_name = $sponsor ? $sponsor->name : 'Sponsor';

    // Get student name
    $student_name = '';
    if ($txn->school_student_id) {
        $student = $this->db->select('name')->where('id', $txn->school_student_id)->get(db_prefix().'school_students')->row();
        $student_name = $student ? $student->name : 'Student';
    } elseif ($txn->university_student_id) {
        $student = $this->db->select('name')->where('id', $txn->university_student_id)->get(db_prefix().'university_students')->row();
        $student_name = $student ? $student->name : 'Student';
    }

    $due_date = $txn->next_payment_due ? date('m/d/Y', strtotime($txn->next_payment_due)) : 'Not Set';
    $amount_due = $txn->total_amount - $txn->amount_paid;
    $currency = strtolower($txn->currency);

    $subject = "Reminder: Payment Due on " . $due_date;

    $body = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <p style="color: #4a90e2; font-size: 18px; margin-bottom: 20px;">Hello ' . $sponsor_name . ',</p>
        
        <p style="color: #333; line-height: 1.6; margin-bottom: 20px;">
            This is a reminder that your next payment for <strong>' . $student_name . '</strong> is due on <strong>' . $due_date . '</strong>.
        </p>
        
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0; background-color: #f9f9f9;">
            <thead>
                <tr style="background-color: #e8e8e8;">
                    <th style="border: 1px solid #ddd; padding: 12px; text-align: left; font-weight: bold;">Sponsor</th>
                    <th style="border: 1px solid #ddd; padding: 12px; text-align: left; font-weight: bold;">Student</th>
                    <th style="border: 1px solid #ddd; padding: 12px; text-align: left; font-weight: bold;">Total</th>
                    <th style="border: 1px solid #ddd; padding: 12px; text-align: left; font-weight: bold;">Paid</th>
                    <th style="border: 1px solid #ddd; padding: 12px; text-align: left; font-weight: bold;">Balance</th>
                    <th style="border: 1px solid #ddd; padding: 12px; text-align: left; font-weight: bold;">Due Date</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="border: 1px solid #ddd; padding: 12px;">' . $sponsor_name . '</td>
                    <td style="border: 1px solid #ddd; padding: 12px;">' . $student_name . '</td>
                    <td style="border: 1px solid #ddd; padding: 12px;">' . number_format($txn->total_amount, 2) . '</td>
                    <td style="border: 1px solid #ddd; padding: 12px;">' . number_format($txn->amount_paid, 2) . ' ' . $currency . '</td>
                    <td style="border: 1px solid #ddd; padding: 12px;">' . number_format($amount_due, 2) . '</td>
                    <td style="border: 1px solid #ddd; padding: 12px;">' . $due_date . '</td>
                </tr>
            </tbody>
        </table>
        
        <p style="color: #333; line-height: 1.6; margin-top: 20px;">
            Thank you for supporting 87 Initiative in helping underprivileged children in Sri Lanka.<br>
            <strong>' . get_option('companyname') . '</strong>
        </p>
    </div>';

    return [
        'subject' => $subject,
        'body'    => $body,
    ];
}

public function send_due_email($transaction_id)
{
    $CI =& get_instance();
    $CI->load->model('emails_model');

    // Debug: Log the transaction ID
    log_message('debug', 'Attempting to send email for transaction ID: ' . $transaction_id);

    $txn = $this->get($transaction_id);
    if (!$txn) {
        log_message('error', 'Transaction not found: ' . $transaction_id);
        return false;
    }

    // Debug: Log transaction data
    log_message('debug', 'Transaction found, sponsor_id: ' . $txn->sponsor_id);

    $template = $this->get_due_email_template($transaction_id);
    if (!$template) {
        log_message('error', 'Email template generation failed for transaction: ' . $transaction_id);
        return false;
    }

    // Debug: Log template generation success
    log_message('debug', 'Email template generated successfully');

    // Fetch sponsor email - IMPORTANT: Check if 'email' column exists
    $sponsor = $this->db->select('email, name')->where('id', $txn->sponsor_id)->get(db_prefix().'sponsor_records')->row();
    
    // Debug: Check sponsor data
    if (!$sponsor) {
        log_message('error', 'Sponsor not found in database for ID: ' . $txn->sponsor_id);
        return false;
    }
    
    log_message('debug', 'Sponsor found: ' . $sponsor->name . ', email: ' . ($sponsor->email ?? 'NULL'));
    
    if (empty($sponsor->email)) {
        log_message('error', 'Sponsor email is empty for sponsor: ' . $sponsor->name);
        return false;
    }

    // Debug: Attempt to send email
    log_message('debug', 'Attempting to send email to: ' . $sponsor->email);
    log_message('debug', 'Email subject: ' . $template['subject']);
    
    $result = $CI->emails_model->send_simple_email(
        $sponsor->email,
        $template['subject'],
        $template['body']
    );
    
    // Debug: Log result
    log_message('debug', 'Email send result: ' . ($result ? 'SUCCESS' : 'FAILED'));
    
    return $result;
}


/**
 * Calculate and set the scheduled due reminder date
 * Called after creating/updating transactions
 */
public function update_scheduled_reminders($transaction_id)
{
    $txn = $this->get($transaction_id);
    if (!$txn) return false;

    $today = new DateTime('today');
    $updates = [];

    // Only the X-days-before schedule
    if ((int)$txn->due_reminder_active === 1 && $txn->next_payment_due) {
        $due = new DateTime($txn->next_payment_due);
        $days = (int)$txn->due_reminder_days_before ?: 15;
        $rem = (clone $due)->modify('-'.$days.' days');

        // schedule only if not in the past and not already sent
        if ((int)$txn->due_reminder_sent === 0 && $rem >= $today) {
            $updates['scheduled_due_reminder_date'] = $rem->format('Y-m-d');
        } else {
            $updates['scheduled_due_reminder_date'] = null;
        }
    } else {
        $updates['scheduled_due_reminder_date'] = null;
    }

    $this->db->where('id', $transaction_id);
    return $this->db->update($this->txn_tbl, $updates);
}


/** Reset reminder state for a new next_payment_due cycle */
private function reset_due_cycle(int $txn_id): void
{
    $this->db->where('id', $txn_id)->update($this->txn_tbl, [
        'due_reminder_sent'            => 0,
        'due_day_email_sent'           => 0,
        'due_reminder_sent_at'         => null,   // or your renamed columns
        'due_day_email_sent_at'        => null,
        'scheduled_due_reminder_date'  => null,
    ]);
}


/**
 * Run daily: sends (a) scheduled X-days-before reminder, and (b) on-the-day email.
 * Returns array with counts.
 */

public function run_due_reminder_cron(): array
{
    $today = date('Y-m-d');
    $sent_before = 0;
    $sent_due_day = 0;

    // A) X-days-before reminders
    $before_list = $this->db->from($this->txn_tbl)
        ->where('due_reminder_active', 1)
        ->where('scheduled_due_reminder_date', $today)
        ->group_start()
            ->where('due_reminder_sent', 0)
            ->or_where('DATE(due_reminder_sent_at) <>', $today) // ✅ prevent re-send today
        ->group_end()
        ->get()->result();

    foreach ($before_list as $txn) {
        if ($this->send_due_email((int)$txn->id)) {
            $this->db->where('id', $txn->id)->update($this->txn_tbl, [
                'due_reminder_sent'        => 1,
                'due_reminder_sent_at'     => date('Y-m-d H:i:s'),
                'scheduled_due_reminder_date' => null,
            ]);
            $sent_before++;
        }
    }

    // B) On-the-day reminders
    $due_day_list = $this->db->from($this->txn_tbl)
        ->where('due_reminder_active', 1)
        ->where('next_payment_due', $today)
        ->group_start()
            ->where('due_day_email_sent', 0)
            ->or_where('DATE(due_day_email_sent_at) <>', $today) // ✅ prevent re-send today
        ->group_end()
        ->get()->result();

    foreach ($due_day_list as $txn) {
        if ($this->send_due_email((int)$txn->id)) {
            $this->db->where('id', $txn->id)->update($this->txn_tbl, [
                'due_day_email_sent'    => 1,
                'due_day_email_sent_at' => date('Y-m-d H:i:s'),
            ]);
            $sent_due_day++;
        }
    }

    return ['sent_before' => $sent_before, 'sent_due_day' => $sent_due_day];
}




}
