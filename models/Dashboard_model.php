<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Dashboard Model - Fixed with correct column names from database
 * @version 1.3 - PRODUCTION READY
 */
class Dashboard_model extends App_Model
{
    private $tbl_school_students;
    private $tbl_university_students;
    private $tbl_sponsors;
    private $tbl_transactions;
    private $tbl_payments;
    
    // CORRECT DATE COLUMNS FROM YOUR DATABASE STRUCTURE
    private $date_cols = [
        'school_students' => 'created_on',      // ✓ Verified from SHOW COLUMNS
        'university_students' => 'created_on',  // ✓ Verified from SHOW COLUMNS
        'sponsors' => 'created_on',             // ✓ Verified from SHOW COLUMNS
        'transactions' => 'created_at',         // ✓ Verified from SHOW COLUMNS
        'payments' => 'created_at'              // ✓ Verified from SHOW COLUMNS
    ];
    
    public function __construct()
    {
        parent::__construct();
        
        $this->tbl_school_students = db_prefix() . 'school_students';
        $this->tbl_university_students = db_prefix() . 'university_students';
        $this->tbl_sponsors = db_prefix() . 'sponsor_records';
        $this->tbl_transactions = db_prefix() . 'sponsor_transactions';
        $this->tbl_payments = db_prefix() . 'sponsor_payments';
    }

    public function get_dashboard_data($options = [])
    {
        $defaults = [
            'time_range' => 30,
            'recent_limit' => 10,
            'trend_months' => 6,
            'include_charts' => true,
            'include_pending' => true
        ];
        
        $options = array_merge($defaults, $options);
        
        return [
            'dashboard_stats' => $this->get_comprehensive_statistics($options['time_range']),
            'recent_students' => $this->get_recent_students($options['recent_limit']),
            'recent_sponsors' => $this->get_recent_sponsors($options['recent_limit']),
            'recent_activities' => $this->get_recent_activities($options['recent_limit'] * 2),
            'recent_transactions' => $this->get_recent_transactions($options['recent_limit']),
            'pending_items' => $options['include_pending'] ? $this->get_pending_items() : [],
            'chart_data' => $options['include_charts'] ? $this->get_chart_data($options['trend_months']) : [],
            'quick_stats' => $this->get_quick_stats(),
            'alerts' => $this->get_dashboard_alerts()
        ];
    }

    public function get_comprehensive_statistics($days = 30)
    {
        return [
            'school_students' => $this->get_school_student_stats($days),
            'university_students' => $this->get_university_student_stats($days),
            'sponsors' => $this->get_sponsor_stats($days),
            'financial' => $this->get_financial_stats($days)
        ];
    }

    private function get_school_student_stats($days = 30)
    {
        $date_col = $this->date_cols['school_students'];
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $total = $this->db->count_all_results($this->tbl_school_students);
        
        // Active count
        $this->db->select('COUNT(*) as count');
        $this->db->from($this->tbl_school_students . ' ss');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = ss.staff_id', 'inner');
        $this->db->where('s.active', 1);
        $active = $this->db->get()->row()->count ?? 0;
        
        // Sponsored count
        $this->db->select('COUNT(DISTINCT ss.id) as count');
        $this->db->from($this->tbl_school_students . ' ss');
        $this->db->group_start();
        $this->db->where('ss.sponsor_id IS NOT NULL');
        $this->db->or_where("ss.id IN (SELECT DISTINCT school_student_id FROM {$this->tbl_transactions} WHERE school_student_id IS NOT NULL)", null, false);
        $this->db->group_end();
        $sponsored = $this->db->get()->row()->count ?? 0;
        
        // Recent additions - FIXED with correct column
        $recent = $this->db->where("{$date_col} >=", $cutoff_date)
                          ->count_all_results($this->tbl_school_students);
        
        // Calculate trend - FIXED
        $previous_cutoff = date('Y-m-d H:i:s', strtotime("-" . ($days * 2) . " days"));
        $previous = $this->db->where("{$date_col} >=", $previous_cutoff)
                            ->where("{$date_col} <", $cutoff_date)
                            ->count_all_results($this->tbl_school_students);
        
        $trend = 0;
        if ($previous > 0) {
            $trend = round((($recent - $previous) / $previous) * 100, 1);
        } elseif ($recent > 0) {
            $trend = 100;
        }
        
        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'sponsored' => $sponsored,
            'unsponsored' => $total - $sponsored,
            'recent' => $recent,
            'trend' => $trend
        ];
    }

    private function get_university_student_stats($days = 30)
    {
        $date_col = $this->date_cols['university_students'];
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $total = $this->db->count_all_results($this->tbl_university_students);
        
        $this->db->select('COUNT(*) as count');
        $this->db->from($this->tbl_university_students . ' us');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = us.staff_id', 'inner');
        $this->db->where('s.active', 1);
        $active = $this->db->get()->row()->count ?? 0;
        
        $this->db->select('COUNT(DISTINCT us.id) as count');
        $this->db->from($this->tbl_university_students . ' us');
        $this->db->group_start();
        $this->db->where('us.sponsor_id IS NOT NULL');
        $this->db->or_where("us.id IN (SELECT DISTINCT university_student_id FROM {$this->tbl_transactions} WHERE university_student_id IS NOT NULL)", null, false);
        $this->db->group_end();
        $sponsored = $this->db->get()->row()->count ?? 0;
        
        $recent = $this->db->where("{$date_col} >=", $cutoff_date)
                          ->count_all_results($this->tbl_university_students);
        
        $previous_cutoff = date('Y-m-d H:i:s', strtotime("-" . ($days * 2) . " days"));
        $previous = $this->db->where("{$date_col} >=", $previous_cutoff)
                            ->where("{$date_col} <", $cutoff_date)
                            ->count_all_results($this->tbl_university_students);
        
        $trend = 0;
        if ($previous > 0) {
            $trend = round((($recent - $previous) / $previous) * 100, 1);
        } elseif ($recent > 0) {
            $trend = 100;
        }
        
        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'sponsored' => $sponsored,
            'unsponsored' => $total - $sponsored,
            'recent' => $recent,
            'trend' => $trend
        ];
    }

    private function get_sponsor_stats($days = 30)
    {
        $date_col = $this->date_cols['sponsors'];
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $total = $this->db->count_all_results($this->tbl_sponsors);
        $active = $this->db->where('active', 1)->count_all_results($this->tbl_sponsors);
        
        $this->db->select('COUNT(DISTINCT sponsor_id) as count');
        $this->db->from($this->tbl_transactions);
        $actively_sponsoring = $this->db->get()->row()->count ?? 0;
        
        $recent = $this->db->where("{$date_col} >=", $cutoff_date)
                          ->count_all_results($this->tbl_sponsors);
        
        $previous_cutoff = date('Y-m-d H:i:s', strtotime("-" . ($days * 2) . " days"));
        $previous = $this->db->where("{$date_col} >=", $previous_cutoff)
                            ->where("{$date_col} <", $cutoff_date)
                            ->count_all_results($this->tbl_sponsors);
        
        $trend = 0;
        if ($previous > 0) {
            $trend = round((($recent - $previous) / $previous) * 100, 1);
        } elseif ($recent > 0) {
            $trend = 100;
        }
        
        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'actively_sponsoring' => $actively_sponsoring,
            'recent' => $recent,
            'trend' => $trend
        ];
    }

    private function get_financial_stats($days = 30)
    {
        $date_col = $this->date_cols['payments']; // created_at for payments
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $this->db->select('SUM(total_amount) as total_committed, SUM(amount_paid) as total_paid');
        $totals = $this->db->get($this->tbl_transactions)->row();
        
        $total_committed = (float)($totals->total_committed ?? 0);
        $total_paid = (float)($totals->total_paid ?? 0);
        
        $this->db->select('SUM(amount) as recent_payments');
        $this->db->where("{$date_col} >=", $cutoff_date);
        $recent_payments = $this->db->get($this->tbl_payments)->row()->recent_payments ?? 0;
        
        $previous_cutoff = date('Y-m-d H:i:s', strtotime("-" . ($days * 2) . " days"));
        $this->db->select('SUM(amount) as previous_payments');
        $this->db->where("{$date_col} >=", $previous_cutoff);
        $this->db->where("{$date_col} <", $cutoff_date);
        $previous_payments = $this->db->get($this->tbl_payments)->row()->previous_payments ?? 0;
        
        $trend = 0;
        if ($previous_payments > 0) {
            $trend = round((($recent_payments - $previous_payments) / $previous_payments) * 100, 1);
        } elseif ($recent_payments > 0) {
            $trend = 100;
        }
        
        return [
            'total_committed' => $total_committed,
            'total_paid' => $total_paid,
            'balance' => $total_committed - $total_paid,
            'recent_payments' => $recent_payments,
            'trend' => $trend,
            'payment_ratio' => $total_committed > 0 ? round(($total_paid / $total_committed) * 100, 1) : 0
        ];
    }

    public function get_recent_students($limit = 10)
    {
        $students = [];
        $school_date_col = $this->date_cols['school_students'];
        $uni_date_col = $this->date_cols['university_students'];
        
        // School students
        $this->db->select("
            'school' as student_type,
            ss.id,
            ss.name,
            ss.school_grade,
            ss.{$school_date_col} as created_at,
            ss.sponsor_id,
            ss.staff_active,
            COALESCE(
                (SELECT COUNT(DISTINCT st.sponsor_id) 
                 FROM {$this->tbl_transactions} st 
                 WHERE st.school_student_id = ss.id),
                CASE WHEN ss.sponsor_id IS NOT NULL THEN 1 ELSE 0 END
            ) as sponsor_count
        ", false);
        $this->db->from($this->tbl_school_students . ' ss');
        $this->db->order_by("ss.{$school_date_col}", 'DESC');
        $this->db->limit(ceil($limit / 2));
        $school_students = $this->db->get()->result_array();
        
        // University students
        $this->db->select("
            'university' as student_type,
            us.id,
            us.name,
            us.university_year_of_study,
            us.{$uni_date_col} as created_at,
            us.sponsor_id,
            us.active as staff_active,
            COALESCE(
                (SELECT COUNT(DISTINCT st.sponsor_id) 
                 FROM {$this->tbl_transactions} st 
                 WHERE st.university_student_id = us.id),
                CASE WHEN us.sponsor_id IS NOT NULL THEN 1 ELSE 0 END
            ) as sponsor_count
        ", false);
        $this->db->from($this->tbl_university_students . ' us');
        $this->db->order_by("us.{$uni_date_col}", 'DESC');
        $this->db->limit(ceil($limit / 2));
        $university_students = $this->db->get()->result_array();
        
        $students = array_merge($school_students, $university_students);
        usort($students, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return array_slice($students, 0, $limit);
    }

    public function get_recent_sponsors($limit = 10)
    {
        $date_col = $this->date_cols['sponsors'];
        
        $this->db->select("
            s.*,
            s.{$date_col} as created_at,
            (SELECT COUNT(DISTINCT school_student_id) 
             FROM {$this->tbl_transactions} 
             WHERE sponsor_id = s.id AND school_student_id IS NOT NULL) as school_students_count,
            (SELECT COUNT(DISTINCT university_student_id) 
             FROM {$this->tbl_transactions} 
             WHERE sponsor_id = s.id AND university_student_id IS NOT NULL) as university_students_count
        ", false);
        $this->db->from($this->tbl_sponsors . ' s');
        $this->db->order_by("s.{$date_col}", 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get()->result_array();
    }

    public function get_recent_transactions($limit = 10)
    {
        $date_col = $this->date_cols['payments'];
        
        $this->db->select("
            p.id,
            p.amount,
            p.payment_date,
            p.{$date_col} as created_at,
            t.payment_type,
            sp.name as sponsor_name,
            COALESCE(ss.name, us.name) as student_name,
            CASE 
                WHEN t.school_student_id IS NOT NULL THEN 'school'
                WHEN t.university_student_id IS NOT NULL THEN 'university'
                ELSE 'unknown'
            END as student_type
        ", false);
        $this->db->from($this->tbl_payments . ' p');
        $this->db->join($this->tbl_transactions . ' t', 't.id = p.transaction_id', 'left');
        $this->db->join($this->tbl_sponsors . ' sp', 'sp.id = p.sponsor_id', 'left');
        $this->db->join($this->tbl_school_students . ' ss', 'ss.id = t.school_student_id', 'left');
        $this->db->join($this->tbl_university_students . ' us', 'us.id = t.university_student_id', 'left');
        $this->db->order_by("p.{$date_col}", 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get()->result_array();
    }

    public function get_recent_activities($limit = 20)
    {
        $activities = [];
        
        $activities = array_merge($activities, $this->get_recent_student_activities(ceil($limit / 4)));
        $activities = array_merge($activities, $this->get_recent_sponsor_activities(ceil($limit / 4)));
        $activities = array_merge($activities, $this->get_recent_payment_activities(ceil($limit / 4)));
        $activities = array_merge($activities, $this->get_recent_sponsorship_activities(ceil($limit / 4)));
        
        usort($activities, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return array_slice($activities, 0, $limit);
    }

    private function get_recent_student_activities($limit)
    {
        $activities = [];
        $school_date_col = $this->date_cols['school_students'];
        $uni_date_col = $this->date_cols['university_students'];
        
        $this->db->select("id, name, {$school_date_col} as created_at, 'school' as type", false);
        $this->db->order_by($school_date_col, 'DESC');
        $this->db->limit($limit);
        $school = $this->db->get($this->tbl_school_students)->result_array();
        
        foreach ($school as $student) {
            $activities[] = [
                'type' => 'students',
                'icon' => 'fa-child',
                'color' => '#337ab7',
                'title' => 'New School Student Added',
                'description' => "Student: {$student['name']}",
                'created_at' => $student['created_at']
            ];
        }
        
        $this->db->select("id, name, {$uni_date_col} as created_at, 'university' as type", false);
        $this->db->order_by($uni_date_col, 'DESC');
        $this->db->limit($limit);
        $university = $this->db->get($this->tbl_university_students)->result_array();
        
        foreach ($university as $student) {
            $activities[] = [
                'type' => 'students',
                'icon' => 'fa-graduation-cap',
                'color' => '#5cb85c',
                'title' => 'New University Student Added',
                'description' => "Student: {$student['name']}",
                'created_at' => $student['created_at']
            ];
        }
        
        return $activities;
    }

    private function get_recent_sponsor_activities($limit)
    {
        $date_col = $this->date_cols['sponsors'];
        
        $this->db->select("id, name, {$date_col} as created_at", false);
        $this->db->order_by($date_col, 'DESC');
        $this->db->limit($limit);
        $sponsors = $this->db->get($this->tbl_sponsors)->result_array();
        
        $activities = [];
        foreach ($sponsors as $sponsor) {
            $activities[] = [
                'type' => 'sponsors',
                'icon' => 'fa-handshake-o',
                'color' => '#5bc0de',
                'title' => 'New Sponsor Registered',
                'description' => "Sponsor: {$sponsor['name']}",
                'created_at' => $sponsor['created_at']
            ];
        }
        
        return $activities;
    }

    private function get_recent_payment_activities($limit)
    {
        $date_col = $this->date_cols['payments'];
        
        $this->db->select("p.amount, p.{$date_col} as created_at, sp.name as sponsor_name", false);
        $this->db->from($this->tbl_payments . ' p');
        $this->db->join($this->tbl_sponsors . ' sp', 'sp.id = p.sponsor_id', 'left');
        $this->db->order_by("p.{$date_col}", 'DESC');
        $this->db->limit($limit);
        $payments = $this->db->get()->result_array();
        
        $activities = [];
        foreach ($payments as $payment) {
            $activities[] = [
                'type' => 'payments',
                'icon' => 'fa-money',
                'color' => '#f0ad4e',
                'title' => 'Payment Received',
                'description' => "Amt : " . number_format($payment['amount'], 2) . " from {$payment['sponsor_name']}",
                'created_at' => $payment['created_at']
            ];
        }
        
        return $activities;
    }

    private function get_recent_sponsorship_activities($limit)
    {
        $date_col = $this->date_cols['transactions'];
        
        $this->db->select("t.{$date_col} as created_at, sp.name as sponsor_name, COALESCE(ss.name, us.name) as student_name", false);
        $this->db->from($this->tbl_transactions . ' t');
        $this->db->join($this->tbl_sponsors . ' sp', 'sp.id = t.sponsor_id', 'left');
        $this->db->join($this->tbl_school_students . ' ss', 'ss.id = t.school_student_id', 'left');
        $this->db->join($this->tbl_university_students . ' us', 'us.id = t.university_student_id', 'left');
        $this->db->order_by("t.{$date_col}", 'DESC');
        $this->db->limit($limit);
        $connections = $this->db->get()->result_array();
        
        $activities = [];
        foreach ($connections as $conn) {
            $activities[] = [
                'type' => 'sponsors',
                'icon' => 'fa-link',
                'color' => '#d9534f',
                'title' => 'Sponsorship Created',
                'description' => "{$conn['sponsor_name']} sponsoring {$conn['student_name']}",
                'created_at' => $conn['created_at']
            ];
        }
        
        return $activities;
    }

    public function get_pending_items()
    {
        // Stub for now - implement later
        return [];
    }

    public function get_dashboard_alerts()
    {
        // Stub for now - implement later
        return [];
    }

    public function get_chart_data($months = 6)
    {
        return [
            'trend_months' => $this->get_trend_labels($months),
            'trend_students' => $this->get_monthly_student_trend($months),
            'trend_sponsors' => $this->get_monthly_sponsor_trend($months),
            'trend_payments' => $this->get_monthly_payment_trend($months),
        ];
    }

    private function get_trend_labels($months)
    {
        $labels = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $labels[] = date('M Y', strtotime("-{$i} months"));
        }
        return $labels;
    }

    private function get_monthly_student_trend($months)
    {
        $data = [];
        $school_date_col = $this->date_cols['school_students'];
        $uni_date_col = $this->date_cols['university_students'];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $start = date('Y-m-01 00:00:00', strtotime("-{$i} months"));
            $end = date('Y-m-t 23:59:59', strtotime("-{$i} months"));
            
            $school = $this->db->where("{$school_date_col} >=", $start)
                              ->where("{$school_date_col} <=", $end)
                              ->count_all_results($this->tbl_school_students);
            
            $university = $this->db->where("{$uni_date_col} >=", $start)
                                  ->where("{$uni_date_col} <=", $end)
                                  ->count_all_results($this->tbl_university_students);
            
            $data[] = $school + $university;
        }
        
        return $data;
    }

    private function get_monthly_sponsor_trend($months)
    {
        $data = [];
        $date_col = $this->date_cols['sponsors'];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $start = date('Y-m-01 00:00:00', strtotime("-{$i} months"));
            $end = date('Y-m-t 23:59:59', strtotime("-{$i} months"));
            
            $count = $this->db->where("{$date_col} >=", $start)
                             ->where("{$date_col} <=", $end)
                             ->count_all_results($this->tbl_sponsors);
            
            $data[] = $count;
        }
        
        return $data;
    }

    private function get_monthly_payment_trend($months)
    {
        $data = [];
        $date_col = $this->date_cols['payments'];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $start = date('Y-m-01 00:00:00', strtotime("-{$i} months"));
            $end = date('Y-m-t 23:59:59', strtotime("-{$i} months"));
            
            $this->db->select('SUM(amount) as total');
            $this->db->where("{$date_col} >=", $start);
            $this->db->where("{$date_col} <=", $end);
            $total = $this->db->get($this->tbl_payments)->row()->total ?? 0;
            
            $data[] = (float)$total;
        }
        
        return $data;
    }

    public function get_quick_stats()
    {
        return [
            'total_students' => $this->db->count_all_results($this->tbl_school_students) + 
                               $this->db->count_all_results($this->tbl_university_students),
            'total_sponsors' => $this->db->count_all_results($this->tbl_sponsors),
            'active_sponsorships' => $this->db->count_all_results($this->tbl_transactions),
            'this_month_payments' => $this->get_this_month_payments(),
            'pending_tasks' => 0
        ];
    }

    private function get_this_month_payments()
    {
        $date_col = $this->date_cols['payments'];
        $start = date('Y-m-01 00:00:00');
        $end = date('Y-m-t 23:59:59');
        
        $this->db->select('SUM(amount) as total');
        $this->db->where("{$date_col} >=", $start);
        $this->db->where("{$date_col} <=", $end);
        
        return (float)($this->db->get($this->tbl_payments)->row()->total ?? 0);
    }
}