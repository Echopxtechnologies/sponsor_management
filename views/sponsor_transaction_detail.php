<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="fa fa-exchange"></i> Transaction Details #<?= $transaction['id'] ?>
                        </h3>
                        <div class="pull-right">
                            <a href="<?= admin_url('student_sponsor_portal/sponsor_dashboard') ?>" class="btn btn-sm btn-default">
                                <i class="fa fa-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction Overview -->
        <div class="row">
            <div class="col-md-8">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-info-circle"></i> Transaction Information</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Transaction ID:</strong></td>
                                        <td><code><?= $transaction['id'] ?></code></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Student:</strong></td>
                                        <td>
                                            <?= htmlspecialchars($transaction['school_student_name'] ?: $transaction['university_student_name']) ?>
                                            <br>
                                            <small class="text-muted">
                                                ID: <?= htmlspecialchars($transaction['school_internal_id'] ?: $transaction['university_internal_id']) ?>
                                            </small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Created Date:</strong></td>
                                        <td><?= date('M d, Y', strtotime($transaction['created_date'])) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Payment Type:</strong></td>
                                        <td>
                                            <span class="label label-info">
                                                <?= ucfirst($transaction['payment_type'] ?? 'One-time') ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Total Amount:</strong></td>
                                        <td><h4 class="text-primary">Amt <?= number_format($transaction['total_amount'], 2) ?></h4></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Amount Paid:</strong></td>
                                        <td><h4 class="text-success">Amt <?= number_format($transaction['amount_paid'], 2) ?></h4></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Next Payment:</strong></td>
                                        <td><h4 class="text-danger">Amt <?= number_format($transaction['balance_amount'], 2) ?></h4></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Currency:</strong></td>
                                        <td><?= strtoupper($transaction['currency'] ?? 'INR') ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <?php if (!empty($transaction['note'])): ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <strong>Note:</strong> <?= nl2br(htmlspecialchars($transaction['note'])) ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Status and Progress -->
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-bar-chart"></i> Payment Progress</h3>
                    </div>
                    <div class="panel-body">
                        <?php 
                        $completion_percentage = $transaction['total_amount'] > 0 
                            ? ($transaction['amount_paid'] / $transaction['total_amount']) * 100 
                            : 0;
                        ?>
                        
                        <div class="progress progress-striped">
                            <div class="progress-bar progress-bar-<?= $completion_percentage == 100 ? 'success' : ($completion_percentage > 0 ? 'warning' : 'danger') ?>" 
                                 style="width: <?= $completion_percentage ?>%">
                                <?= number_format($completion_percentage, 1) ?>%
                            </div>
                        </div>

                        <div class="text-center">
                            <?php if ($completion_percentage == 100): ?>
                                <span class="label label-success label-lg">Completed</span>
                            <?php elseif ($completion_percentage > 0): ?>
                                <span class="label label-warning label-lg">In Progress</span>
                            <?php else: ?>
                                <span class="label label-danger label-lg">Pending</span>
                            <?php endif; ?>
                        </div>

                        <hr>

                    <?php echo render_date_input('next_payment_due', 'Next Payment Due', 
                        isset($txn->next_payment_due) && $txn->next_payment_due != '0000-00-00' 
                            ? _d($txn->next_payment_due) 
                            : '',
                        ['disabled' => true]); ?>

                    <?php echo render_date_input('last_payment_date', 'Last Payment Date', 
                        isset($txn->last_payment_date) && $txn->last_payment_date != '0000-00-00' 
                            ? _d($txn->last_payment_date) 
                            : '', 
                        ['disabled' => true]); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="fa fa-credit-card"></i> Payment History 
                            <span class="badge"><?= count($payments) ?></span>
                        </h3>
                    </div>
                    <div class="panel-body">
                        <?php if (empty($payments)): ?>
                            <div class="alert alert-info text-center">
                                <i class="fa fa-info-circle fa-2x"></i><br>
                                <strong>No Payments Yet</strong><br>
                                No payments have been recorded for this transaction.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="paymentsTable">
                                    <thead>
                                        <tr>
                                            <th>Payment Date</th>
                                            <th>Amount</th>
                                            <th>Currency</th>
                                            <th>Note</th>
                                            <th>Created By</th>
                                            <th>Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td>
                                                <strong><?= date('M d, Y', strtotime($payment['payment_date'])) ?></strong>
                                            </td>
                                            <td>
                                                <span class="text-success">
                                                    <strong>Amt <?= number_format($payment['amount'], 2) ?></strong>
                                                </span>
                                            </td>
                                            <td><?= strtoupper($payment['currency'] ?? 'INR') ?></td>
                                            <td>
                                                <?php if (!empty($payment['note'])): ?>
                                                    <?= htmlspecialchars($payment['note']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No note</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($payment['created_by'])): ?>
                                                    <span class="text-muted">Staff ID: <?= $payment['created_by'] ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">System</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= date('M d, Y H:i', strtotime($payment['created_at'])) ?>
                                                </small>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="info">
                                            <th>Total Payments:</th>
                                            <th class="text-success">Amt <?= number_format(array_sum(array_column($payments, 'amount')), 2) ?></th>
                                            <th colspan="4"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Schedule (if recurring) -->
        <?php if (!empty($transaction['payment_type']) && $transaction['payment_type'] !== 'one-time'): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="fa fa-calendar"></i> Payment Schedule
                        </h3>
                    </div>
                    <div class="panel-body">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            This is a <strong><?= ucfirst($transaction['payment_type']) ?></strong> payment plan.
                            
                            <?php if (!empty($transaction['next_payment_due'])): ?>
                                The next payment of <strong>Amt <?= number_format($transaction['balance_amount'], 0) ?></strong> 
                                is due on <strong><?= date('M d, Y', strtotime($transaction['next_payment_due'])) ?></strong>.
                            <?php else: ?>
                                No future payments are scheduled at this time.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.table-borderless > tbody > tr > td,
.table-borderless > tbody > tr > th,
.table-borderless > thead > tr > td,
.table-borderless > thead > tr > th {
    border: none;
}

.label-lg {
    font-size: 14px;
    padding: 6px 12px;
}

.progress {
    margin-bottom: 10px;
}
</style>

<script>
$(document).ready(function() {
    // Initialize DataTable for payments
    if ($.fn.DataTable) {
        $('#paymentsTable').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[0, 'desc']],
            footerCallback: function (row, data, start, end, display) {
                // This is handled in the HTML already, but keeping for consistency
            }
        });
    }

    // Add tooltips for better UX
    $('[data-toggle="tooltip"]').tooltip();
});
</script>

<?php init_tail(); ?>