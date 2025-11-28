<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="fa fa-heart"></i> Welcome, <?= htmlspecialchars($sponsor->name) ?>
                        </h3>
                    </div>
                    <div class="panel-body">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            <strong>Sponsor Dashboard:</strong> View your sponsored students and track their progress. 
                            You have read-only access to student information and transaction history.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="panel panel-success">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-users fa-3x text-success"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge"><?= count($sponsored_students) ?></div>
                                <div>Students Sponsored</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="panel panel-primary">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-money fa-3x text-primary"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge"><?= $transaction_summary['total_transactions'] ?? 0 ?></div>
                                <div>Total Transactions</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="panel panel-warning">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-check-circle fa-3x text-warning"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge">Amount <?= number_format($transaction_summary['total_paid'] ?? 0, 0) ?></div>
                                <div>Total Paid</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="panel panel-danger">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-clock-o fa-3x text-danger"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge">Amount <?= number_format($transaction_summary['total_outstanding'] ?? 0, 0) ?></div>
                                <div>Outstanding</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sponsored Students -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="fa fa-graduation-cap"></i> My Sponsored Students
                        </h3>
                    </div>
                    <div class="panel-body">
                        <?php if (empty($sponsored_students)): ?>
                            <div class="alert alert-warning text-center">
                                <i class="fa fa-exclamation-triangle fa-2x"></i><br>
                                <strong>No Students Found</strong><br>
                                You don't have any students assigned to you yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="sponsoredStudentsTable">
                                    <thead>
                                        <tr>
                                            <th>Student Name</th>
                                            <th>Type</th>
                                            <th>ID</th>
                                            <th>Grade/Year</th>
                                            <th>Institution</th>
                                            <th>Location</th>
                                            <th>Contact</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sponsored_students as $student): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($student['name']) ?></strong>
                                            </td>
                                            <td>
                                                <span class="label label-<?= $student['student_type'] === 'school' ? 'primary' : 'info' ?>">
                                                    <?= ucfirst($student['student_type']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <code><?= htmlspecialchars($student['student_type'] === 'school' ? $student['school_internal_id'] : $student['university_internal_id']) ?></code>
                                            </td>
                                            <td>
                                                <?php if ($student['student_type'] === 'school'): ?>
                                                    Grade <?= htmlspecialchars($student['school_grade']) ?>
                                                <?php else: ?>
                                                    Year <?= htmlspecialchars($student['university_year_of_study']) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($student['student_type'] === 'school'): ?>
                                                    <?= htmlspecialchars($student['school_name'] ?? 'Not specified') ?>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($student['university_name'] ?? 'Not specified') ?>
                                                    <?php if (!empty($student['program_name'])): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars($student['program_name']) ?></small>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($student['city'] ?? 'Not provided') ?></td>
                                            <td>
                                                <?php if (!empty($student['email'])): ?>
                                                    <a href="mailto:<?= htmlspecialchars($student['email']) ?>" class="text-muted">
                                                        <i class="fa fa-envelope"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($student['contact_no'])): ?>
                                                    <a href="tel:<?= htmlspecialchars($student['contact_no']) ?>" class="text-muted">
                                                        <i class="fa fa-phone"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?= admin_url('student_sponsor_portal/view_sponsored_student/' . $student['student_type'] . '/' . $student['id']) ?>" 
                                                   class="btn btn-sm btn-info" title="View Details">
                                                    <i class="fa fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions and Payments -->
        <div class="row">
            <!-- Recent Transactions -->
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="fa fa-exchange"></i> Recent Transactions
                        </h3>
                    </div>
                    <div class="panel-body">
                        <?php if (empty($recent_transactions)): ?>
                            <p class="text-muted text-center">No transactions found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-condensed">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_transactions as $txn): ?>
                                        <tr>
                                            <td>
                                                <small>
                                                    <?= htmlspecialchars($txn['school_student_name'] ?: $txn['university_student_name']) ?>
                                                    <br><span class="text-muted"><?= htmlspecialchars($txn['school_internal_id'] ?: $txn['university_internal_id']) ?></span>
                                                </small>
                                            </td>
                                            <td>
                                                <strong>Amt :<?= number_format($txn['total_amount'], 0) ?></strong>
                                                <br><small class="text-success">Paid: Amt :<?= number_format($txn['amount_paid'], 0) ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                $balance = $txn['total_amount'] - $txn['amount_paid'];
                                                if ($balance <= 0): ?>
                                                    <span class="label label-success">Completed</span>
                                                <?php elseif ($txn['amount_paid'] > 0): ?>
                                                    <span class="label label-warning">Partial</span>
                                                <?php else: ?>
                                                    <span class="label label-danger">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?= admin_url('student_sponsor_portal/view_transaction_details/' . $txn['id']) ?>" 
                                                   class="btn btn-xs btn-default" title="View Transaction">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="fa fa-credit-card"></i> Recent Payments
                        </h3>
                    </div>
                    <div class="panel-body">
                        <?php if (empty($payment_history)): ?>
                            <p class="text-muted text-center">No payments recorded yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-condensed">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Student</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payment_history as $payment): ?>
                                        <tr>
                                            <td>
                                                <small><?= date('M d, Y', strtotime($payment['payment_date'])) ?></small>
                                            </td>
                                            <td>
                                                <small>
                                                    <?= htmlspecialchars($payment['school_student_name'] ?: $payment['university_student_name']) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <strong class="text-success">Amt: <?= number_format($payment['amount'], 0) ?></strong>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.huge {
    font-size: 2.2em;
    font-weight: bold;
}

.panel-body .row {
    margin: 0;
}

.fa-3x {
    font-size: 2.5em !important;
}

.table-condensed > thead > tr > th,
.table-condensed > tbody > tr > th,
.table-condensed > tfoot > tr > th,
.table-condensed > thead > tr > td,
.table-condensed > tbody > tr > td,
.table-condensed > tfoot > tr > td {
    padding: 3px;
}
</style>

<script>
$(document).ready(function() {
    // Initialize DataTable for sponsored students
    if ($.fn.DataTable) {
        $('#sponsoredStudentsTable').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[0, 'asc']],
            columnDefs: [
                { targets: [7], orderable: false } // Actions column
            ]
        });
    }
});
</script>

<?php init_tail(); ?>