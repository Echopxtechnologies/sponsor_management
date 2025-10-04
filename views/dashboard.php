<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <!-- Header Section -->
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="customer-profile-group-heading" style="margin-top:20px;">
                                    <i class="fa fa-dashboard"></i> <?php echo isset($title) ? $title : 'Student Sponsor Portal Dashboard'; ?>
                                </h4>
                                <p class="text-muted">Real-time overview of sponsorship activities and performance metrics</p>
                            </div>
                            <div class="col-md-4">
                                <div class="text-right" style="margin-top:15px;">
                                    <div class="btn-group" role="group" style="margin-right: 5px;">
                                        <button type="button" class="btn btn-success btn-sm dropdown-toggle" 
                                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fa fa-download"></i> Export <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-right">
                                            <li><a href="<?php echo admin_url('student_sponsor_portal/export_dashboard_pdf'); ?>"><i class="fa fa-file-pdf-o text-danger"></i> Dashboard PDF</a></li>
                                            <li><a href="<?php echo admin_url('student_sponsor_portal/export_dashboard_excel'); ?>"><i class="fa fa-file-excel-o text-success"></i> Summary Excel</a></li>
                                            <li><a href="<?php echo admin_url('student_sponsor_portal/export_all_data'); ?>"><i class="fa fa-file-text-o"></i> All Data CSV</a></li>
                                        </ul>
                                    </div>
                                    <button type="button" onclick="refreshDashboard()" class="btn btn-info btn-sm">
                                        <i class="fa fa-refresh"></i> Refresh
                                    </button>
                                </div>
                            </div>
                        </div>

                        <hr class="hr-panel-heading">

                        <!-- Summary Cards -->
                        <div class="row">
                            <!-- School Students Card -->
                            <div class="col-md-3">
                                <div class="dashboard-card school-students-card">
                                    <div class="card-header">
                                        <div class="card-icon">
                                            <i class="fa fa-child"></i>
                                        </div>
                                        <div class="card-title">
                                            <h3><?php echo number_format($dashboard_stats['school_students']['total'] ?? 0); ?></h3>
                                            <span>School Students</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="progress progress-sm">
                                            <?php 
                                                $school_total = $dashboard_stats['school_students']['total'] ?? 0;
                                                $school_active = $dashboard_stats['school_students']['active'] ?? 0;
                                                $school_active_percentage = ($school_total > 0) ? round(($school_active / $school_total) * 100, 1) : 0;
                                            ?>
                                            <div class="progress-bar progress-bar-primary" 
                                                 style="width: <?php echo $school_active_percentage; ?>%"></div>
                                        </div>
                                        <div class="card-stats">
                                            <span class="text-success">
                                                <i class="fa fa-check-circle"></i> 
                                                <?php echo $school_active; ?> Active
                                            </span>
                                            <span class="text-warning">
                                                <i class="fa fa-heart"></i> 
                                                <?php echo $dashboard_stats['school_students']['sponsored'] ?? 0; ?> Sponsored
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <div class="card-actions">
                                            <a href="<?php echo admin_url('student_sponsor_portal/school_students'); ?>" 
                                               class="btn btn-xs btn-primary">
                                                <i class="fa fa-list"></i> View All
                                            </a>
                                            <a href="<?php echo admin_url('student_sponsor_portal/school_student_form'); ?>" 
                                               class="btn btn-xs btn-success">
                                                <i class="fa fa-plus"></i> Add New
                                            </a>
                                        </div>
                                        <?php if(isset($dashboard_stats['school_students']['trend']) && $dashboard_stats['school_students']['trend'] != 0): ?>
                                            <div class="card-trend">
                                                <span class="trend <?php echo $dashboard_stats['school_students']['trend'] > 0 ? 'trend-up' : 'trend-down'; ?>">
                                                    <i class="fa fa-arrow-<?php echo $dashboard_stats['school_students']['trend'] > 0 ? 'up' : 'down'; ?>"></i>
                                                    <?php echo abs($dashboard_stats['school_students']['trend']); ?>%
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- University Students Card -->
                            <div class="col-md-3">
                                <div class="dashboard-card university-students-card">
                                    <div class="card-header">
                                        <div class="card-icon">
                                            <i class="fa fa-graduation-cap"></i>
                                        </div>
                                        <div class="card-title">
                                            <h3><?php echo number_format($dashboard_stats['university_students']['total'] ?? 0); ?></h3>
                                            <span>University Students</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="progress progress-sm">
                                            <?php 
                                                $university_total = $dashboard_stats['university_students']['total'] ?? 0;
                                                $university_active = $dashboard_stats['university_students']['active'] ?? 0;
                                                $university_active_percentage = ($university_total > 0) ? round(($university_active / $university_total) * 100, 1) : 0;
                                            ?>
                                            <div class="progress-bar progress-bar-success" 
                                                 style="width: <?php echo $university_active_percentage; ?>%"></div>
                                        </div>
                                        <div class="card-stats">
                                            <span class="text-success">
                                                <i class="fa fa-check-circle"></i> 
                                                <?php echo $university_active; ?> Active
                                            </span>
                                            <span class="text-warning">
                                                <i class="fa fa-heart"></i> 
                                                <?php echo $dashboard_stats['university_students']['sponsored'] ?? 0; ?> Sponsored
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <div class="card-actions">
                                            <a href="<?php echo admin_url('student_sponsor_portal/university_students'); ?>" 
                                               class="btn btn-xs btn-success">
                                                <i class="fa fa-list"></i> View All
                                            </a>
                                            <a href="<?php echo admin_url('student_sponsor_portal/university_student_form'); ?>" 
                                               class="btn btn-xs btn-primary">
                                                <i class="fa fa-plus"></i> Add New
                                            </a>
                                        </div>
                                        <?php if(isset($dashboard_stats['university_students']['trend']) && $dashboard_stats['university_students']['trend'] != 0): ?>
                                            <div class="card-trend">
                                                <span class="trend <?php echo $dashboard_stats['university_students']['trend'] > 0 ? 'trend-up' : 'trend-down'; ?>">
                                                    <i class="fa fa-arrow-<?php echo $dashboard_stats['university_students']['trend'] > 0 ? 'up' : 'down'; ?>"></i>
                                                    <?php echo abs($dashboard_stats['university_students']['trend']); ?>%
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Sponsors Card -->
                            <div class="col-md-3">
                                <div class="dashboard-card sponsors-card">
                                    <div class="card-header">
                                        <div class="card-icon">
                                            <i class="fa fa-handshake-o"></i>
                                        </div>
                                        <div class="card-title">
                                            <h3><?php echo number_format($dashboard_stats['sponsors']['total'] ?? 0); ?></h3>
                                            <span>Sponsors</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="progress progress-sm">
                                            <?php 
                                                $sponsor_total = $dashboard_stats['sponsors']['total'] ?? 0;
                                                $sponsor_active = $dashboard_stats['sponsors']['active'] ?? 0;
                                                $sponsor_active_percentage = ($sponsor_total > 0) ? round(($sponsor_active / $sponsor_total) * 100, 1) : 0;
                                            ?>
                                            <div class="progress-bar progress-bar-info" 
                                                 style="width: <?php echo $sponsor_active_percentage; ?>%"></div>
                                        </div>
                                        <div class="card-stats">
                                            <span class="text-success">
                                                <i class="fa fa-check-circle"></i> 
                                                <?php echo $sponsor_active; ?> Active
                                            </span>
                                            <span class="text-info">
                                                <i class="fa fa-users"></i> 
                                                <?php echo $dashboard_stats['sponsors']['actively_sponsoring'] ?? 0; ?> Sponsoring
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <div class="card-actions">
                                            <a href="<?php echo admin_url('student_sponsor_portal/sponsors'); ?>" 
                                               class="btn btn-xs btn-info">
                                                <i class="fa fa-list"></i> View All
                                            </a>
                                            <a href="<?php echo admin_url('student_sponsor_portal/sponsor_form'); ?>" 
                                               class="btn btn-xs btn-success">
                                                <i class="fa fa-plus"></i> Add New
                                            </a>
                                        </div>
                                        <?php if(isset($dashboard_stats['sponsors']['trend']) && $dashboard_stats['sponsors']['trend'] != 0): ?>
                                            <div class="card-trend">
                                                <span class="trend <?php echo $dashboard_stats['sponsors']['trend'] > 0 ? 'trend-up' : 'trend-down'; ?>">
                                                    <i class="fa fa-arrow-<?php echo $dashboard_stats['sponsors']['trend'] > 0 ? 'up' : 'down'; ?>"></i>
                                                    <?php echo abs($dashboard_stats['sponsors']['trend']); ?>%
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Card -->
                            <div class="col-md-3">
                                <div class="dashboard-card financial-card">
                                    <div class="card-header">
                                        <div class="card-icon">
                                            <i class="fa fa-money"></i>
                                        </div>
                                        <div class="card-title">
                                            <h3>₹<?php echo number_format($dashboard_stats['financial']['total_committed'] ?? 0, 0); ?></h3>
                                            <span>Total Committed</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="progress progress-sm">
                                            <?php 
                                                $total_committed = $dashboard_stats['financial']['total_committed'] ?? 0;
                                                $total_paid = $dashboard_stats['financial']['total_paid'] ?? 0;
                                                $payment_percentage = ($total_committed > 0) ? round(($total_paid / $total_committed) * 100, 1) : 0;
                                            ?>
                                            <div class="progress-bar progress-bar-warning" 
                                                 style="width: <?php echo $payment_percentage; ?>%"></div>
                                        </div>
                                        <div class="card-stats">
                                            <span class="text-success">
                                                <i class="fa fa-check"></i> 
                                                ₹<?php echo number_format($total_paid, 0); ?> Paid
                                            </span>
                                            <span class="text-warning">
                                                <i class="fa fa-clock-o"></i> 
                                                ₹<?php echo number_format($total_committed - $total_paid, 0); ?> Pending
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <div class="card-actions">
                                            <a href="<?php echo admin_url('student_sponsor_portal/transactions'); ?>" 
                                               class="btn btn-xs btn-warning">
                                                <i class="fa fa-list"></i> Transactions
                                            </a>
                                            <a href="<?php echo admin_url('student_sponsor_portal/transaction'); ?>" 
                                               class="btn btn-xs btn-success">
                                                <i class="fa fa-plus"></i> Add Payment
                                            </a>
                                        </div>
                                        <?php if(isset($dashboard_stats['financial']['trend']) && $dashboard_stats['financial']['trend'] != 0): ?>
                                            <div class="card-trend">
                                                <span class="trend <?php echo $dashboard_stats['financial']['trend'] > 0 ? 'trend-up' : 'trend-down'; ?>">
                                                    <i class="fa fa-arrow-<?php echo $dashboard_stats['financial']['trend'] > 0 ? 'up' : 'down'; ?>"></i>
                                                    <?php echo abs($dashboard_stats['financial']['trend']); ?>%
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions & Recent Activity Row -->
                        <div class="row" style="margin-top: 25px;">
                            <!-- Quick Actions Panel -->
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h3 class="panel-title">
                                            <i class="fa fa-bolt text-warning"></i> Quick Actions
                                        </h3>
                                    </div>
                                    <div class="panel-body">
                                        <div class="quick-actions">
                                            <div class="action-group">
                                                <h6>Students</h6>
                                                <a href="<?php echo admin_url('student_sponsor_portal/school_student_form'); ?>" 
                                                   class="btn btn-sm btn-primary btn-block">
                                                    <i class="fa fa-plus"></i> Add School Student
                                                </a>
                                                <a href="<?php echo admin_url('student_sponsor_portal/university_student_form'); ?>" 
                                                   class="btn btn-sm btn-success btn-block">
                                                    <i class="fa fa-plus"></i> Add University Student
                                                </a>
                                            </div>
                                            
                                            <div class="action-group">
                                                <h6>Sponsors & Payments</h6>
                                                <a href="<?php echo admin_url('student_sponsor_portal/sponsor_form'); ?>" 
                                                   class="btn btn-sm btn-info btn-block">
                                                    <i class="fa fa-user-plus"></i> Add Sponsor
                                                </a>
                                                <a href="<?php echo admin_url('student_sponsor_portal/transaction'); ?>" 
                                                   class="btn btn-sm btn-warning btn-block">
                                                    <i class="fa fa-money"></i> Record Payment
                                                </a>
                                            </div>
                                            
                                            <div class="action-group">
                                                <h6>Data Management</h6>
                                                <a href="<?php echo admin_url('student_sponsor_portal/bulk_import_school_students'); ?>" 
                                                   class="btn btn-sm btn-default btn-block">
                                                    <i class="fa fa-upload"></i> Import Students
                                                </a>
                                                <a href="<?php echo admin_url('student_sponsor_portal/export_all_data'); ?>" 
                                                   class="btn btn-sm btn-default btn-block">
                                                    <i class="fa fa-download"></i> Export All Data
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Activity Panel -->
                            <div class="col-md-8">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h3 class="panel-title">
                                                    <i class="fa fa-clock-o text-info"></i> Recent Activity
                                                </h3>
                                            </div>
                                            <div class="col-md-4 text-right">
                                                <div class="btn-group btn-group-xs">
                                                    <button type="button" class="btn btn-default active" onclick="filterActivity('all')">All</button>
                                                    <button type="button" class="btn btn-default" onclick="filterActivity('students')">Students</button>
                                                    <button type="button" class="btn btn-default" onclick="filterActivity('sponsors')">Sponsors</button>
                                                    <button type="button" class="btn btn-default" onclick="filterActivity('payments')">Payments</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel-body">
                                        <div class="activity-timeline" id="activity-timeline">
                                            <?php if(!empty($recent_activities)): ?>
                                                <?php foreach($recent_activities as $activity): ?>
                                                    <div class="activity-item" data-type="<?php echo $activity['type']; ?>">
                                                        <div class="activity-icon" style="background-color: <?php echo $activity['color']; ?>">
                                                            <i class="fa <?php echo $activity['icon']; ?>"></i>
                                                        </div>
                                                        <div class="activity-content">
                                                            <div class="activity-title"><?php echo html_escape($activity['title']); ?></div>
                                                            <div class="activity-description"><?php echo html_escape($activity['description']); ?></div>
                                                        </div>
                                                        <div class="activity-time">
                                                            <?php echo time_ago($activity['created_at']); ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="empty-state">
                                                    <i class="fa fa-clock-o"></i>
                                                    <p>No recent activity found</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Records Tabs -->
                        <div class="row" style="margin-top: 25px;">
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h3 class="panel-title">
                                            <i class="fa fa-table"></i> Recent Records
                                        </h3>
                                    </div>
                                    <div class="panel-body">
                                        <ul class="nav nav-tabs dashboard-tabs" role="tablist">
                                            <li class="active">
                                                <a href="#recent-students" role="tab" data-toggle="tab">
                                                    <i class="fa fa-graduation-cap"></i> Recent Students 
                                                    <span class="badge"><?php echo count($recent_students ?? []); ?></span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#recent-sponsors" role="tab" data-toggle="tab">
                                                    <i class="fa fa-handshake-o"></i> Recent Sponsors 
                                                    <span class="badge"><?php echo count($recent_sponsors ?? []); ?></span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#recent-transactions" role="tab" data-toggle="tab">
                                                    <i class="fa fa-money text-success"></i> Recent Transactions 
                                                    <span class="badge"><?php echo count($recent_transactions ?? []); ?></span>
                                                </a>
                                            </li>
                                        </ul>

                                        <div class="tab-content dashboard-tab-content">
                                            <!-- Recent Students Tab -->
                                            <div role="tabpanel" class="tab-pane active" id="recent-students">
                                                <div class="table-responsive" style="margin-top: 15px;">
                                                    <table class="table table-hover recent-students-table">
                                                        <thead>
                                                            <tr>
                                                                <th width="5%">#</th>
                                                                <th width="30%">Student Name</th>
                                                                <th width="15%">Type</th>
                                                                <th width="15%">Grade/Year</th>
                                                                <th width="15%">Status</th>
                                                                <th width="20%">Sponsored</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if(!empty($recent_students)): ?>
                                                                <?php foreach($recent_students as $index => $student): ?>
                                                                    <?php 
                                                                        $form_url = ($student['student_type'] === 'university') ? 'university_student_form' : 'school_student_form';
                                                                        $student_url = admin_url('student_sponsor_portal/' . $form_url . '/' . $student['id']);
                                                                    ?>
                                                                    <tr class="clickable-row" data-href="<?php echo $student_url; ?>" style="cursor: pointer;">
                                                                        <td><?php echo $index + 1; ?></td>
                                                                        <td>
                                                                            <strong><?php echo html_escape($student['name']); ?></strong>
                                                                            <br><small class="text-muted">
                                                                                Added: <?php echo date('M d, Y', strtotime($student['created_at'])); ?>
                                                                            </small>
                                                                        </td>
                                                                        <td>
                                                                            <?php if($student['student_type'] === 'university'): ?>
                                                                                <span class="label label-success">
                                                                                    <i class="fa fa-graduation-cap"></i> University
                                                                                </span>
                                                                            <?php else: ?>
                                                                                <span class="label label-primary">
                                                                                    <i class="fa fa-child"></i> School
                                                                                </span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td>
                                                                            <span class="badge badge-<?php echo $student['student_type'] === 'university' ? 'success' : 'primary'; ?>">
                                                                                <?php echo $student['school_grade'] ?? $student['university_year_of_study'] ?? 'N/A'; ?>
                                                                            </span>
                                                                        </td>
                                                                        <td>
                                                                            <?php
                                                                                $status = $student['staff_active'] ?? 1;
                                                                                $status_class = $status ? 'success' : 'warning';
                                                                                $status_text = $status ? 'Active' : 'Inactive';
                                                                            ?>
                                                                            <span class="label label-<?php echo $status_class; ?>">
                                                                                <i class="fa fa-<?php echo $status ? 'check-circle' : 'pause-circle'; ?>"></i>
                                                                                <?php echo $status_text; ?>
                                                                            </span>
                                                                        </td>
                                                                        <td>
                                                                            <?php if(!empty($student['sponsor_count']) && $student['sponsor_count'] > 0): ?>
                                                                                <span class="label label-info">
                                                                                    <i class="fa fa-heart"></i> Yes
                                                                                </span>
                                                                            <?php else: ?>
                                                                                <span class="label label-default">
                                                                                    <i class="fa fa-heart-o"></i> No
                                                                                </span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <tr>
                                                                    <td colspan="6" class="text-center text-muted">No recent students found</td>
                                                                </tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <!-- Recent Sponsors Tab -->
                                            <div role="tabpanel" class="tab-pane" id="recent-sponsors">
                                                <div class="table-responsive" style="margin-top: 15px;">
                                                    <table class="table table-hover recent-sponsors-table">
                                                        <thead>
                                                            <tr>
                                                                <th width="5%">#</th>
                                                                <th width="30%">Sponsor Name</th>
                                                                <th width="15%">Type</th>
                                                                <th width="25%">Contact</th>
                                                                <th width="12%">Students</th>
                                                                <th width="13%">Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if(!empty($recent_sponsors)): ?>
                                                                <?php foreach($recent_sponsors as $index => $sponsor): ?>
                                                                    <tr class="clickable-row" data-href="<?php echo admin_url('student_sponsor_portal/sponsor_form/' . $sponsor['id']); ?>" style="cursor: pointer;">
                                                                        <td><?php echo $index + 1; ?></td>
                                                                        <td>
                                                                            <strong><?php echo html_escape($sponsor['name']); ?></strong>
                                                                            <br><small class="text-muted">
                                                                                Added: <?php echo date('M d, Y', strtotime($sponsor['created_at'])); ?>
                                                                            </small>
                                                                        </td>
                                                                        <td>
                                                                            <span class="label label-info">
                                                                                <i class="fa fa-<?php echo ($sponsor['sponsor_type'] === 'company') ? 'building' : 'user'; ?>"></i>
                                                                                <?php echo ucfirst($sponsor['sponsor_type'] ?? 'Individual'); ?>
                                                                            </span>
                                                                        </td>
                                                                        <td>
                                                                            <?php if(!empty($sponsor['email'])): ?>
                                                                                <small><i class="fa fa-envelope"></i> <?php echo html_escape($sponsor['email']); ?></small><br>
                                                                            <?php endif; ?>
                                                                            <?php if(!empty($sponsor['contact_no'])): ?>
                                                                                <small><i class="fa fa-phone"></i> <?php echo html_escape($sponsor['contact_no']); ?></small>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td>
                                                                            <span class="badge badge-primary">
                                                                                <?php echo (int)($sponsor['school_students_count'] ?? 0) + (int)($sponsor['university_students_count'] ?? 0); ?>
                                                                            </span>
                                                                        </td>
                                                                        <td>
                                                                            <?php
                                                                                $status = $sponsor['active'] ?? 1;
                                                                                $status_class = $status ? 'success' : 'warning';
                                                                                $status_text = $status ? 'Active' : 'Inactive';
                                                                            ?>
                                                                            <span class="label label-<?php echo $status_class; ?>">
                                                                                <i class="fa fa-<?php echo $status ? 'check-circle' : 'pause-circle'; ?>"></i>
                                                                                <?php echo $status_text; ?>
                                                                            </span>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <tr>
                                                                    <td colspan="6" class="text-center text-muted">No recent sponsors found</td>
                                                                </tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <!-- Recent Transactions Tab -->
                                            <div role="tabpanel" class="tab-pane" id="recent-transactions">
                                                <div class="table-responsive" style="margin-top: 15px;">
                                                    <table class="table table-hover recent-transactions-table">
                                                        <thead>
                                                            <tr>
                                                                <th width="5%">#</th>
                                                                <th width="22%">Sponsor</th>
                                                                <th width="22%">Student</th>
                                                                <th width="15%">Amount</th>
                                                                <th width="18%">Type</th>
                                                                <th width="18%">Date</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if(!empty($recent_transactions)): ?>
                                                                <?php foreach($recent_transactions as $index => $transaction): ?>
                                                                    <tr class="clickable-row" data-href="<?php echo admin_url('student_sponsor_portal/transaction/' . $transaction['id']); ?>" style="cursor: pointer;">
                                                                        <td><?php echo $index + 1; ?></td>
                                                                        <td>
                                                                            <strong><?php echo html_escape($transaction['sponsor_name']); ?></strong>
                                                                        </td>
                                                                        <td>
                                                                            <strong><?php echo html_escape($transaction['student_name']); ?></strong>
                                                                            <br><small class="text-muted">
                                                                                <?php echo ucfirst($transaction['student_type'] ?? ''); ?>
                                                                            </small>
                                                                        </td>
                                                                        <td>
                                                                            <strong class="text-success">₹<?php echo number_format($transaction['amount'], 2); ?></strong>
                                                                        </td>
                                                                        <td>
                                                                            <span class="label label-info">
                                                                                <?php echo ucfirst(str_replace('_', ' ', $transaction['payment_type'] ?? 'One-time')); ?>
                                                                            </span>
                                                                        </td>
                                                                        <td>
                                                                            <small>
                                                                                <?php echo date('M d, Y', strtotime($transaction['created_at'])); ?>
                                                                            </small>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <tr>
                                                                    <td colspan="6" class="text-center text-muted">No recent transactions found</td>
                                                                </tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    margin-bottom: 20px;
    overflow: hidden;
}

.dashboard-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.dashboard-card .card-header {
    padding: 20px;
    border-bottom: 1px solid #f1f3f4;
    display: flex;
    align-items: center;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

.dashboard-card .card-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    font-size: 20px;
    color: #fff;
}

.school-students-card .card-icon { background: linear-gradient(135deg, #337ab7, #2c5aa0); }
.university-students-card .card-icon { background: linear-gradient(135deg, #5cb85c, #449d44); }
.sponsors-card .card-icon { background: linear-gradient(135deg, #5bc0de, #31b0d5); }
.financial-card .card-icon { background: linear-gradient(135deg, #f0ad4e, #ec971f); }

.dashboard-card .card-title h3 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
    color: #2c3e50;
}

.dashboard-card .card-title span {
    font-size: 12px;
    color: #7f8c8d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.dashboard-card .card-body {
    padding: 15px 20px;
}

.dashboard-card .progress {
    height: 4px;
    border-radius: 2px;
    margin-bottom: 15px;
    background-color: #f8f9fa;
}

.dashboard-card .card-stats {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
}

.dashboard-card .card-footer {
    padding: 12px 20px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dashboard-card .card-actions {
    display: flex;
    gap: 5px;
}

.dashboard-card .card-trend {
    font-size: 11px;
    font-weight: 600;
}

.trend-up { color: #28a745; }
.trend-down { color: #dc3545; }

.quick-actions .action-group {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f1f3f4;
}

.quick-actions .action-group:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.quick-actions .action-group h6 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 10px;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.quick-actions .btn {
    margin-bottom: 5px;
    font-size: 12px;
    padding: 8px 12px;
}

.activity-timeline {
    max-height: 300px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    padding: 10px 0;
    border-bottom: 1px solid #f8f9fa;
    position: relative;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    font-size: 12px;
    color: #fff;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-content .activity-title {
    font-size: 13px;
    font-weight: 500;
    color: #2c3e50;
    margin-bottom: 3px;
}

.activity-content .activity-description {
    font-size: 11px;
    color: #7f8c8d;
    line-height: 1.4;
}

.activity-time {
    font-size: 10px;
    color: #95a5a6;
    white-space: nowrap;
    margin-left: 10px;
    flex-shrink: 0;
}

.dashboard-tabs {
    border-bottom: 2px solid #f1f3f4;
    margin-bottom: 0;
}

.dashboard-tabs > li > a {
    font-weight: 500;
    color: #666;
    border: none;
    border-radius: 4px 4px 0 0;
    padding: 12px 16px;
    font-size: 12px;
}

.dashboard-tabs > li > a:hover {
    background-color: #f8f9fa;
    border-color: transparent;
}

.dashboard-tabs > li.active > a,
.dashboard-tabs > li.active > a:hover,
.dashboard-tabs > li.active > a:focus {
    color: #337ab7;
    background-color: #fff;
    border: none;
    border-bottom: 2px solid #337ab7;
}

.dashboard-tab-content {
    background: #fff;
    border: 1px solid #e9ecef;
    border-top: none;
    border-radius: 0 0 4px 4px;
}

.table-hover tbody tr:hover {
    background-color: #f8f9fa;
}

/* Clickable rows */
.clickable-row:hover {
    background-color: #f0f7ff !important;
    transition: background-color 0.2s ease;
}

.table th {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #495057;
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.table td {
    font-size: 12px;
    vertical-align: middle;
    border-color: #f1f3f4;
}

.label {
    font-size: 9px;
    padding: 3px 6px;
    border-radius: 3px;
    font-weight: 500;
}

.badge {
    font-size: 9px;
    padding: 3px 6px;
    border-radius: 10px;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #95a5a6;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

.panel-default {
    border: 1px solid #e9ecef;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.panel-heading {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-bottom: 1px solid #e9ecef;
    color: #495057;
}

.panel-title {
    font-weight: 600;
    font-size: 14px;
}

.panel-title i {
    margin-right: 8px;
}

@media (max-width: 768px) {
    .dashboard-card .card-header {
        padding: 15px;
        flex-direction: column;
        text-align: center;
    }
    
    .dashboard-card .card-icon {
        margin-right: 0;
        margin-bottom: 10px;
    }
    
    .dashboard-card .card-footer {
        flex-direction: column;
        gap: 10px;
    }
    
    .dashboard-card .card-actions {
        justify-content: center;
    }
    
    .quick-actions .btn {
        font-size: 11px;
        padding: 6px 10px;
    }
    
    .activity-timeline {
        max-height: 200px;
    }
}
</style>

<script>
$(document).ready(function() {
    // Make table rows clickable
    $('.clickable-row').click(function() {
        window.location = $(this).data('href');
    });
    
    $('[data-toggle="tooltip"]').tooltip();
});

function filterActivity(type) {
    $('.btn-group button').removeClass('active');
    $(`button[onclick="filterActivity('${type}')"]`).addClass('active');
    
    if (type === 'all') {
        $('.activity-item').show();
    } else {
        $('.activity-item').hide();
        $(`.activity-item[data-type="${type}"]`).show();
    }
}

function refreshDashboard() {
    window.location.reload();
}
</script>

<?php init_tail(); ?>