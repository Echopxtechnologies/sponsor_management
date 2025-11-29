<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <!-- Welcome Header -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="fa fa-user-circle"></i> Welcome, <?= htmlspecialchars($sponsor['name']) ?>
                        </h3>
                    </div>
                    <div class="panel-body">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            <strong>Sponsor Portal:</strong> View your profile information and track your sponsored students.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="panel panel-success">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-users fa-3x text-success"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge"><?= $stats['total_students'] ?></div>
                                <div>Total Students</div>
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
                                <i class="fa fa-graduation-cap fa-3x text-primary"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge"><?= $stats['school_students'] ?></div>
                                <div>School Students</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="panel panel-info">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-university fa-3x text-info"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge"><?= $stats['university_students'] ?></div>
                                <div>University Students</div>
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
                                <i class="fa fa-money fa-3x text-warning"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge">Amt <?= number_format($stats['total_committed'], 0) ?></div>
                                <div>Total Committed</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Information -->
        <div class="row">
            <!-- Personal Information -->
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-user"></i> Personal Information</h3>
                    </div>
                    <div class="panel-body">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Full Name:</strong></td>
                                <td><?= htmlspecialchars($sponsor['name']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Sponsor Type:</strong></td>
                                <td>
                                    <span class="label label-info">
                                        <?= htmlspecialchars($sponsor['sponsor_type'] ?? 'Individual') ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Occupation:</strong></td>
                                <td><?= htmlspecialchars($sponsor['sponsor_occupation'] ?? 'Not specified') ?></td>
                            </tr>
                            <tr>
                                <td><strong>Date of Birth:</strong></td>
                                <td>
                                    <?= !empty($sponsor['sponsor_dob']) 
                                        ? date('M d, Y', strtotime($sponsor['sponsor_dob'])) 
                                        : 'Not provided' ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-phone"></i> Contact Information</h3>
                    </div>
                    <div class="panel-body">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td><?= htmlspecialchars($sponsor['email'] ?? 'Not provided') ?></td>
                            </tr>
                            <tr>
                                <td><strong>Phone:</strong></td>
                                <td><?= htmlspecialchars($sponsor['phone'] ?? 'Not provided') ?></td>
                            </tr>
                            <tr>
                                <td><strong>Address:</strong></td>
                                <td><?= htmlspecialchars($sponsor['address'] ?? 'Not provided') ?></td>
                            </tr>
                            <tr>
                                <td><strong>City:</strong></td>
                                <td><?= htmlspecialchars($sponsor['city'] ?? 'Not provided') ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-dashboard"></i> Quick Actions</h3>
                    </div>
                    <div class="panel-body text-center">
                        <a href="<?= admin_url('student_sponsor_portal/my_sponsored_students') ?>" 
                           class="btn btn-primary btn-lg">
                            <i class="fa fa-users"></i> View All My Students
                        </a>
                        
                        <a href="<?= admin_url('student_sponsor_portal/my_sponsored_students?type=school') ?>" 
                           class="btn btn-info btn-lg">
                            <i class="fa fa-graduation-cap"></i> School Students Only
                        </a>
                        
                        <a href="<?= admin_url('student_sponsor_portal/my_sponsored_students?type=university') ?>" 
                           class="btn btn-success btn-lg">
                            <i class="fa fa-university"></i> University Students Only
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-history"></i> Sponsorship Summary</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <h4 class="text-primary">Total Transactions</h4>
                                <h2><?= $stats['total_transactions'] ?></h2>
                            </div>
                            <div class="col-md-4 text-center">
                                <h4 class="text-success">Amount Paid</h4>
                                <h2>Amt <?= number_format($stats['total_paid'], 0) ?></h2>
                            </div>
                            <div class="col-md-4 text-center">
                                <h4 class="text-warning">Outstanding</h4>
                                <h2>Amt <?= number_format($stats['total_committed'] - $stats['total_paid'], 0) ?></h2>
                            </div>
                        </div>
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

.btn-lg {
    margin: 5px;
    padding: 12px 24px;
}
</style>

<?php init_tail(); ?>