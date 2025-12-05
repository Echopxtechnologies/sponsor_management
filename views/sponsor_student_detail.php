<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="fa fa-user"></i> <?= htmlspecialchars($student['name']) ?> 
                            <span class="label label-<?= $student_type === 'school' ? 'primary' : 'info' ?>">
                                <?= ucfirst($student_type) ?> Student
                            </span>
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

        <!-- Profile Photo and Basic Info -->
        <div class="row">
            <div class="col-md-3">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-camera"></i> Profile Photo</h3>
                    </div>
                    <div class="panel-body text-center">
                        <?php if ($student_type === 'school'): ?>
                            <img src="<?= admin_url('student_sponsor_portal/display_school_photo/' . $student['id']) ?>" 
                                 class="img-responsive img-rounded student-photo" 
                                 alt="Student Photo"
                                 onerror="this.src='<?= base_url('assets/images/user-placeholder.jpg') ?>'">
                        <?php else: ?>
                            <img src="<?= admin_url('student_sponsor_portal/display_profile_photo/' . $student['id']) ?>" 
                                 class="img-responsive img-rounded student-photo" 
                                 alt="Student Photo"
                                 onerror="this.src='<?= base_url('assets/images/user-placeholder.jpg') ?>'">
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-bar-chart"></i> Quick Stats</h3>
                    </div>
                    <div class="panel-body">
                        <p><strong>Active Transactions:</strong> <?= count($transactions) ?></p>
                        <p><strong>Total Sponsored:</strong> Amt <?= number_format(array_sum(array_column($transactions, 'total_amount')), 0) ?></p>
                        <p><strong>Total Paid:</strong> Amt <?= number_format(array_sum(array_column($transactions, 'amount_paid')), 0) ?></p>
                        <p><strong>Outstanding:</strong> Amt <?= number_format(array_sum(array_column($transactions, 'balance_amount')), 0) ?></p>
                    </div>
                </div>
            </div>

            <!-- Student Information -->
            <div class="col-md-9">
                <!-- Basic Info -->
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title"><i class="fa fa-info-circle"></i> Basic Information</h3>
                        </div>
                        <div class="panel-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Full Name:</strong></td>
                                    <td><?= htmlspecialchars($student['name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Student ID:</strong></td>
                                    <td>
                                        <code><?= htmlspecialchars($student_type === 'school' ? $student['school_internal_id'] : $student['university_internal_id']) ?></code>
                                    </td>
                                </tr>
                                <?php if ($student_type === 'school'): ?>
                                    <tr>
                                        <td><strong>Grade:</strong></td>
                                        <td><?= htmlspecialchars($student['school_grade']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>School:</strong></td>
                                        <td><?= htmlspecialchars($student['school_name'] ?? 'Not specified') ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Age:</strong></td>
                                        <td><?= htmlspecialchars($student['school_age'] ?? 'Not provided') ?></td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td><strong>Year of Study:</strong></td>
                                        <td>Year <?= htmlspecialchars($student['university_year_of_study']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>University:</strong></td>
                                        <td><?= htmlspecialchars($student['university_name'] ?? 'Not specified') ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Program:</strong></td>
                                        <td><?= htmlspecialchars($student['program_name'] ?? 'Not specified') ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Age:</strong></td>
                                        <td><?= htmlspecialchars($student['university_age'] ?? 'Not provided') ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <td><strong>Date of Birth:</strong></td>
                                    <td>
                                        <?= !empty($student[$student_type . '_student_dob']) 
                                            ? date('M d, Y', strtotime($student[$student_type . '_student_dob'])) 
                                            : 'Not provided' ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Contact & Location -->
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title"><i class="fa fa-map-marker"></i> Location</h3>
                        </div>
                        <div class="panel-body">
                            <table class="table table-borderless">
                          <?php /* COMMENTED OUT - Email not shown to sponsors
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td>
                                    <?php if (!empty($student['email'])): ?>
                                        <a href="mailto:<?= htmlspecialchars($student['email']) ?>">
                                            <?= htmlspecialchars($student['email']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Not provided</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            */ ?>
                            <?php /* COMMENTED OUT - Phone not shown to sponsors
                            <tr>
                                <td><strong>Phone:</strong></td>
                                <td>
                                    <?php if (!empty($student['contact_no'])): ?>
                                        <a href="tel:<?= htmlspecialchars($student['contact_no']) ?>">
                                            <?= htmlspecialchars($student['contact_no']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Not provided</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            */ ?>
                                <tr>
                                    <td><strong>City:</strong></td>
                                    <td><?= htmlspecialchars($student['city'] ?? 'Not provided') ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Address:</strong></td>
                                    <td><?= htmlspecialchars($student['address'] ?? 'Not provided') ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Postal Code:</strong></td>
                                    <td><?= htmlspecialchars($student['zip'] ?? 'Not provided') ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Country:</strong></td>
                                    <td><?= htmlspecialchars($student['country_name'] ?? 'Not provided') ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Family Information -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-users"></i> Family Information</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h5><strong>Father's Details</strong></h5>
                                <p><strong>Name:</strong> <?= htmlspecialchars($student[$student_type . '_father_name'] ?? 'Not provided') ?></p>
                                <p><strong>Income:</strong> 
                                    <?php if (!empty($student[$student_type . '_father_income'])): ?>
                                        Amt <?= number_format($student[$student_type . '_father_income'], 0) ?>
                                    <?php else: ?>
                                        Not provided
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <h5><strong>Mother's Details</strong></h5>
                                <p><strong>Name:</strong> <?= htmlspecialchars($student[$student_type . '_mother_name'] ?? 'Not provided') ?></p>
                                <p><strong>Income:</strong> 
                                    <?php if (!empty($student[$student_type . '_mother_income'])): ?>
                                        Amt <?= number_format($student[$student_type . '_mother_income'], 0) ?>
                                    <?php else: ?>
                                        Not provided
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <h5><strong>Guardian Details</strong></h5>
                                <p><strong>Name:</strong> <?= htmlspecialchars($student[$student_type . '_guardian_name'] ?? 'Not provided') ?></p>
                                <p><strong>Income:</strong> 
                                    <?php if (!empty($student[$student_type . '_guardian_income'])): ?>
                                        Amt <?= number_format($student[$student_type . '_guardian_income'], 0) ?>
                                    <?php else: ?>
                                        Not provided
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sponsorship Transactions -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-exchange"></i> Your Sponsorship Transactions</h3>
                    </div>
                    <div class="panel-body">
                        <?php if (empty($transactions)): ?>
                            <div class="alert alert-info text-center">
                                <i class="fa fa-info-circle"></i>
                                No transactions found for this student.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped" id="transactionsTable">
                                    <thead>
                                        <tr>
                                            <th>Transaction Date</th>
                                            <th>Total Amount</th>
                                            <th>Amount Paid</th>
                                            <th>Remaining Amount</th>
                                            <th>Payment Type</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactions as $txn): ?>
                                        <tr>
                                            <td><?= date('M d, Y', strtotime($txn['created_at'])) ?></td>
                                            <td><strong>Amt <?= number_format($txn['total_amount'], 0) ?></strong></td>
                                            <td class="text-success">Amt <?= number_format($txn['amount_paid'], 0) ?></td>
                                            <td class="text-danger">Amt <?= number_format($txn['balance_amount'], 0) ?></td>
                                            <td>
                                                <span class="label label-default">
                                                    <?= ucfirst($txn['payment_type'] ?? 'One-time') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                $balance = $txn['balance_amount'];
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
                                                   class="btn btn-xs btn-info" title="View Transaction Details">
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

        <!-- Report Cards -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-file-text"></i> Academic Reports</h3>
                    </div>
                    <div class="panel-body">
                        <?php if (empty($report_cards['report_cards'])): ?>
                            <div class="alert alert-info text-center">
                                <i class="fa fa-info-circle"></i>
                                No report cards available yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped" id="reportCardsTable">
                                    <thead>
                                        <tr>
                                            <th>Term</th>
                                            <th>Upload Date</th>
                                            <th>File Name</th>
                                            <?php if ($student_type === 'university'): ?>
                                                <th>Semester End</th>
                                            <?php endif; ?>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_cards['report_cards'] as $card): ?>
                                        <tr>
                                            <td>
                                                <span class="label label-primary">
                                                    <?= htmlspecialchars($card['term'] ?? $card['report_card_term']) ?>
                                                </span>
                                            </td>
                                            <td><?= $card['upload_date'] ?></td>
                                            <td><?= htmlspecialchars($card['display_name']) ?></td>
                                            <?php if ($student_type === 'university'): ?>
                                                <td>
                                                    <?php if (!empty($card['semester_end_month']) && !empty($card['semester_end_year'])): ?>
                                                        <?= date('M Y', mktime(0, 0, 0, $card['semester_end_month'], 1, $card['semester_end_year'])) ?>
                                                    <?php else: ?>
                                                        Not specified
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                            <td>
                                                <a href="<?= $card['file_url'] ?>" 
                                                   class="btn btn-xs btn-success" 
                                                   title="Download Report Card" 
                                                   target="_blank">
                                                    <i class="fa fa-download"></i> Download
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

        <!-- External Comments -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-comment"></i> External Comments</h3>
                    </div>
                    <div class="panel-body">
                        <?php if (!empty($student['external_comment'])): ?>
                            <div class="well well-sm" style="background-color: #f9f9f9; border: 1px solid #e3e3e3;">
                                <?= nl2br(htmlspecialchars($student['external_comment'])) ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="fa fa-info-circle"></i>
                                No external comments available.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.table-borderless > tbody > tr > td,
.table-borderless > tbody > tr > th,
.table-borderless > thead > tr > td,
.table-borderless > thead > tr > th {
    border: none;
}

.panel-title .label {
    margin-left: 10px;
    vertical-align: middle;
}

.student-photo {
    max-width: 200px;
    max-height: 200px;
    margin: 0 auto;
}
</style>

<script>
$(document).ready(function() {
    // Initialize DataTables
    if ($.fn.DataTable) {
        $('#transactionsTable').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[0, 'desc']],
            columnDefs: [
                { targets: [6], orderable: false } // Actions column
            ]
        });

        $('#reportCardsTable').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[1, 'desc']],
            columnDefs: [
                { targets: [<?= $student_type === 'university' ? '4' : '3' ?>], orderable: false } // Actions column
            ]
        });
    }
});
</script>

<?php init_tail(); ?>