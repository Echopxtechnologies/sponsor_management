<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="customer-profile-group-heading"><?php echo $title; ?></h4>
                            </div>
                            <div class="col-md-4 text-right">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                                        <i class="fa fa-download"></i> Export <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu" role="menu">
                                        <li><a href="#" onclick="exportData('pdf')"><i class="fa fa-file-pdf-o"></i> Export PDF</a></li>
                                        <li><a href="#" onclick="exportData('excel')"><i class="fa fa-file-excel-o"></i> Export Excel</a></li>
                                        <li><a href="#" onclick="exportData('csv')"><i class="fa fa-file-text-o"></i> Export CSV</a></li>
                                    </ul>
                                </div>
                                <button type="button" onclick="refreshStats()" class="btn btn-warning">
                                    <i class="fa fa-refresh"></i> Refresh
                                </button>
                            </div>
                        </div>
                        <hr class="hr-panel-heading">

                        <!-- Enhanced Summary Cards with Progress -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="panel panel-primary">
                                    <div class="panel-heading">
                                        <div class="row">
                                            <div class="col-xs-3">
                                                <i class="fa fa-child fa-3x"></i>
                                            </div>
                                            <div class="col-xs-9 text-right">
                                                <h2 class="mtop5"><?php echo $school_count; ?></h2>
                                                <div>School Students</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel-footer">
                                        <div class="row">
                                            <div class="col-xs-6">
                                                <span class="text-muted">Active: <?php echo isset($active_school_count) ? $active_school_count : 0; ?></span>
                                            </div>
                                            <div class="col-xs-6 text-right">
                                                <a href="<?php echo admin_url('student_sponsor_portal/school_form'); ?>" class="btn btn-xs btn-primary">
                                                    <i class="fa fa-plus"></i> Add New
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="panel panel-success">
                                    <div class="panel-heading">
                                        <div class="row">
                                            <div class="col-xs-3">
                                                <i class="fa fa-graduation-cap fa-3x"></i>
                                            </div>
                                            <div class="col-xs-9 text-right">
                                                <h2 class="mtop5"><?php echo $university_count; ?></h2>
                                                <div>University Students</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel-footer">
                                        <div class="row">
                                            <div class="col-xs-6">
                                                <span class="text-muted">Active: <?php echo isset($active_university_count) ? $active_university_count : 0; ?></span>
                                            </div>
                                            <div class="col-xs-6 text-right">
                                                <a href="<?php echo admin_url('student_sponsor_portal/university_form'); ?>" class="btn btn-xs btn-success">
                                                    <i class="fa fa-plus"></i> Add New
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="panel panel-info">
                                    <div class="panel-heading">
                                        <div class="row">
                                            <div class="col-xs-3">
                                                <i class="fa fa-handshake-o fa-3x"></i>
                                            </div>
                                            <div class="col-xs-9 text-right">
                                                <h2 class="mtop5"><?php echo $sponsor_count; ?></h2>
                                                <div>Sponsors</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel-footer">
                                        <div class="row">
                                            <div class="col-xs-6">
                                                <span class="text-muted">Active: <?php echo isset($active_sponsor_count) ? $active_sponsor_count : 0; ?></span>
                                            </div>
                                            <div class="col-xs-6 text-right">
                                                <a href="<?php echo admin_url('student_sponsor_portal/sponsor_form'); ?>" class="btn btn-xs btn-info">
                                                    <i class="fa fa-plus"></i> Add New
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="panel panel-warning">
                                    <div class="panel-heading">
                                        <div class="row">
                                            <div class="col-xs-3">
                                                <i class="fa fa-link fa-3x"></i>
                                            </div>
                                            <div class="col-xs-9 text-right">
                                                <h2 class="mtop5"><?php echo isset($sponsorship_count) ? $sponsorship_count : 0; ?></h2>
                                                <div>Sponsorships</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel-footer">
                                        <div class="row">
                                            <div class="col-xs-6">
                                                <span class="text-muted">Active: <?php echo isset($active_sponsorship_count) ? $active_sponsorship_count : 0; ?></span>
                                            </div>
                                            <div class="col-xs-6 text-right">
                                                <a href="<?php echo admin_url('student_sponsor_portal/sponsorship_form'); ?>" class="btn btn-xs btn-warning">
                                                    <i class="fa fa-plus"></i> Create
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Charts and Analytics Row -->
                        <div class="row mtop20">
                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><i class="fa fa-bar-chart"></i> Student Distribution</h3>
                                    </div>
                                    <div class="panel-body">
                                        <canvas id="studentChart" width="400" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><i class="fa fa-pie-chart"></i> Sponsorship Status</h3>
                                    </div>
                                    <div class="panel-body">
                                        <canvas id="sponsorshipChart" width="400" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Enhanced Filters -->
                        <div class="row mtop20">
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><i class="fa fa-filter"></i> Advanced Filters</h3>
                                    </div>
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label>Date Range</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control datepicker" id="date_from" placeholder="From Date">
                                                    <span class="input-group-addon">to</span>
                                                    <input type="text" class="form-control datepicker" id="date_to" placeholder="To Date">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <label>Status</label>
                                                <select class="form-control" id="status_filter">
                                                    <option value="">All Status</option>
                                                    <option value="active">Active</option>
                                                    <option value="inactive">Inactive</option>
                                                    <option value="pending">Pending</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label>Type</label>
                                                <select class="form-control" id="type_filter">
                                                    <option value="">All Types</option>
                                                    <option value="school">School</option>
                                                    <option value="university">University</option>
                                                    <option value="sponsor">Sponsor</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label>Search</label>
                                                <input type="text" class="form-control" id="global_search" placeholder="Search by name, email, phone...">
                                            </div>
                                            <div class="col-md-2">
                                                <label>&nbsp;</label>
                                                <div>
                                                    <button type="button" class="btn btn-info btn-block" onclick="applyFilters()">
                                                        <i class="fa fa-search"></i> Apply
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Enhanced Tabbed Data View -->
                        <ul class="nav nav-tabs mtop20" role="tablist">
                            <li class="active">
                                <a href="#school_tab" role="tab" data-toggle="tab">
                                    <i class="fa fa-child"></i> School Students 
                                    <span class="badge"><?php echo $school_count; ?></span>
                                </a>
                            </li>
                            <li>
                                <a href="#university_tab" role="tab" data-toggle="tab">
                                    <i class="fa fa-graduation-cap"></i> University Students 
                                    <span class="badge"><?php echo $university_count; ?></span>
                                </a>
                            </li>
                            <li>
                                <a href="#sponsor_tab" role="tab" data-toggle="tab">
                                    <i class="fa fa-handshake-o"></i> Sponsors 
                                    <span class="badge"><?php echo $sponsor_count; ?></span>
                                </a>
                            </li>
                            <li>
                                <a href="#sponsorship_tab" role="tab" data-toggle="tab">
                                    <i class="fa fa-link"></i> Sponsorships 
                                    <span class="badge"><?php echo isset($sponsorship_count) ? $sponsorship_count : 0; ?></span>
                                </a>
                            </li>
                            <li>
                                <a href="#reports_tab" role="tab" data-toggle="tab">
                                    <i class="fa fa-bar-chart"></i> Reports
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- School Students Tab -->
                            <div role="tabpanel" class="tab-pane active" id="school_tab">
                                <div class="row mtop10">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control search-input" placeholder="Search School Students..." data-target="#school_table">
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-control grade-filter" data-target="#school_table">
                                            <option value="">All Grades</option>
                                            <?php for($i = 1; $i <= 12; $i++): ?>
                                                <option value="Grade <?php echo $i; ?>">Grade <?php echo $i; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="pull-right">
                                            <span class="text-muted">Show: </span>
                                            <select class="form-control" id="school_per_page" style="width: auto; display: inline-block;">
                                                <option value="10">10</option>
                                                <option value="25">25</option>
                                                <option value="50">50</option>
                                                <option value="100">100</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive mtop10">
                                    <table class="table table-bordered table-hover" id="school_table">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <input type="checkbox" id="select_all_school" onclick="toggleSelectAll('school')">
                                                </th>
                                                <th>Name <i class="fa fa-sort sort-icon" data-column="name"></i></th>
                                                <th>Email <i class="fa fa-sort sort-icon" data-column="email"></i></th>
                                                <th>Phone <i class="fa fa-sort sort-icon" data-column="phone"></i></th>
                                                <th>Grade <i class="fa fa-sort sort-icon" data-column="grade"></i></th>
                                                <th>Status</th>
                                                <th>Sponsored</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($school_students as $student): ?>
                                            <tr>
                                                <td><input type="checkbox" name="selected_students[]" value="<?php echo $student['id']; ?>"></td>
                                                <td><?php echo $student['name']; ?></td>
                                                <td><a href="mailto:<?php echo $student['email']; ?>"><?php echo $student['email']; ?></a></td>
                                                <td><?php echo $student['phone']; ?></td>
                                                <td><span class="badge badge-info"><?php echo $student['grade']; ?></span></td>
                                                <td>
                                                    <span class="label label-<?php echo ($student['status'] == 'active') ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($student['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if(isset($student['sponsored']) && $student['sponsored']): ?>
                                                        <span class="label label-success"><i class="fa fa-check"></i> Yes</span>
                                                    <?php else: ?>
                                                        <span class="label label-default"><i class="fa fa-times"></i> No</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="<?php echo admin_url('student_sponsor_portal/view_student/'.$student['id']); ?>" class="btn btn-xs btn-info" title="View">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <a href="<?php echo admin_url('student_sponsor_portal/edit_student/'.$student['id']); ?>" class="btn btn-xs btn-primary" title="Edit">
                                                            <i class="fa fa-edit"></i>
                                                        </a>
                                                        <a href="#" onclick="deleteRecord('student', <?php echo $student['id']; ?>)" class="btn btn-xs btn-danger" title="Delete">
                                                            <i class="fa fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="bulk-actions" style="display: none;">
                                            <select class="form-control" id="bulk_action_school" style="width: auto; display: inline-block;">
                                                <option value="">Bulk Actions</option>
                                                <option value="activate">Activate</option>
                                                <option value="deactivate">Deactivate</option>
                                                <option value="delete">Delete</option>
                                                <option value="export">Export Selected</option>
                                            </select>
                                            <button type="button" class="btn btn-info" onclick="executeBulkAction('school')">Apply</button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div id="school_pagination" class="pull-right"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- University Students Tab -->
                            <div role="tabpanel" class="tab-pane" id="university_tab">
                                <div class="row mtop10">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control search-input" placeholder="Search University Students..." data-target="#university_table">
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-control program-filter" data-target="#university_table">
                                            <option value="">All Programs</option>
                                            <option value="Engineering">Engineering</option>
                                            <option value="Medicine">Medicine</option>
                                            <option value="Business">Business</option>
                                            <option value="Arts">Arts</option>
                                            <option value="Science">Science</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="pull-right">
                                            <span class="text-muted">Show: </span>
                                            <select class="form-control" id="university_per_page" style="width: auto; display: inline-block;">
                                                <option value="10">10</option>
                                                <option value="25">25</option>
                                                <option value="50">50</option>
                                                <option value="100">100</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive mtop10">
                                    <table class="table table-bordered table-hover" id="university_table">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <input type="checkbox" id="select_all_university" onclick="toggleSelectAll('university')">
                                                </th>
                                                <th>Name <i class="fa fa-sort sort-icon" data-column="name"></i></th>
                                                <th>Email <i class="fa fa-sort sort-icon" data-column="email"></i></th>
                                                <th>Phone <i class="fa fa-sort sort-icon" data-column="phone"></i></th>
                                                <th>Program <i class="fa fa-sort sort-icon" data-column="program"></i></th>
                                                <th>Year</th>
                                                <th>Status</th>
                                                <th>Sponsored</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($university_students as $student): ?>
                                            <tr>
                                                <td><input type="checkbox" name="selected_students[]" value="<?php echo $student['id']; ?>"></td>
                                                <td><?php echo $student['name']; ?></td>
                                                <td><a href="mailto:<?php echo $student['email']; ?>"><?php echo $student['email']; ?></a></td>
                                                <td><?php echo $student['phone']; ?></td>
                                                <td><span class="badge badge-primary"><?php echo $student['program']; ?></span></td>
                                                <td><?php echo isset($student['year']) ? $student['year'] : 'N/A'; ?></td>
                                                <td>
                                                    <span class="label label-<?php echo ($student['status'] == 'active') ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($student['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if(isset($student['sponsored']) && $student['sponsored']): ?>
                                                        <span class="label label-success"><i class="fa fa-check"></i> Yes</span>
                                                    <?php else: ?>
                                                        <span class="label label-default"><i class="fa fa-times"></i> No</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="<?php echo admin_url('student_sponsor_portal/view_student/'.$student['id']); ?>" class="btn btn-xs btn-info" title="View">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <a href="<?php echo admin_url('student_sponsor_portal/edit_student/'.$student['id']); ?>" class="btn btn-xs btn-primary" title="Edit">
                                                            <i class="fa fa-edit"></i>
                                                        </a>
                                                        <a href="#" onclick="deleteRecord('student', <?php echo $student['id']; ?>)" class="btn btn-xs btn-danger" title="Delete">
                                                            <i class="fa fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="bulk-actions" style="display: none;">
                                            <select class="form-control" id="bulk_action_university" style="width: auto; display: inline-block;">
                                                <option value="">Bulk Actions</option>
                                                <option value="activate">Activate</option>
                                                <option value="deactivate">Deactivate</option>
                                                <option value="delete">Delete</option>
                                                <option value="export">Export Selected</option>
                                            </select>
                                            <button type="button" class="btn btn-info" onclick="executeBulkAction('university')">Apply</button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div id="university_pagination" class="pull-right"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sponsors Tab -->
                            <div role="tabpanel" class="tab-pane" id="sponsor_tab">
                                <div class="row mtop10">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control search-input" placeholder="Search Sponsors..." data-target="#sponsor_table">
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-control type-filter" data-target="#sponsor_table">
                                            <option value="">All Types</option>
                                            <option value="Individual">Individual</option>
                                            <option value="Corporate">Corporate</option>
                                            <option value="Foundation">Foundation</option>
                                            <option value="NGO">NGO</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="pull-right">
                                            <span class="text-muted">Show: </span>
                                            <select class="form-control" id="sponsor_per_page" style="width: auto; display: inline-block;">
                                                <option value="10">10</option>
                                                <option value="25">25</option>
                                                <option value="50">50</option>
                                                <option value="100">100</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive mtop10">
                                    <table class="table table-bordered table-hover" id="sponsor_table">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <input type="checkbox" id="select_all_sponsor" onclick="toggleSelectAll('sponsor')">
                                                </th>
                                                <th>Name <i class="fa fa-sort sort-icon" data-column="name"></i></th>
                                                <th>Email <i class="fa fa-sort sort-icon" data-column="email"></i></th>
                                                <th>Phone <i class="fa fa-sort sort-icon" data-column="phone"></i></th>
                                                <th>Type <i class="fa fa-sort sort-icon" data-column="sponsor_type"></i></th>
                                                <th>Sponsorships</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($sponsors as $sponsor): ?>
                                            <tr>
                                                <td><input type="checkbox" name="selected_sponsors[]" value="<?php echo $sponsor['id']; ?>"></td>
                                                <td><?php echo $sponsor['name']; ?></td>
                                                <td><a href="mailto:<?php echo $sponsor['email']; ?>"><?php echo $sponsor['email']; ?></a></td>
                                                <td><?php echo $sponsor['phone']; ?></td>
                                                <td><span class="badge badge-info"><?php echo $sponsor['sponsor_type']; ?></span></td>
                                                <td>
                                                    <span class="badge badge-success">
                                                        <?php echo isset($sponsor['sponsorship_count']) ? $sponsor['sponsorship_count'] : 0; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="label label-<?php echo ($sponsor['status'] == 'active') ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($sponsor['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="<?php echo admin_url('student_sponsor_portal/view_sponsor/'.$sponsor['id']); ?>" class="btn btn-xs btn-info" title="View">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <a href="<?php echo admin_url('student_sponsor_portal/edit_sponsor/'.$sponsor['id']); ?>" class="btn btn-xs btn-primary" title="Edit">
                                                            <i class="fa fa-edit"></i>
                                                        </a>
                                                        <a href="<?php echo admin_url('student_sponsor_portal/create_sponsorship/'.$sponsor['id']); ?>" class="btn btn-xs btn-success" title="Create Sponsorship">
                                                            <i class="fa fa-link"></i>
                                                        </a>
                                                        <a href="#" onclick="deleteRecord('sponsor', <?php echo $sponsor['id']; ?>)" class="btn btn-xs btn-danger" title="Delete">
                                                            <i class="fa fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="bulk-actions" style="display: none;">
                                            <select class="form-control" id="bulk_action_sponsor" style="width: auto; display: inline-block;">
                                                <option value="">Bulk Actions</option>
                                                <option value="activate">Activate</option>
                                                <option value="deactivate">Deactivate</option>
                                                <option value="delete">Delete</option>
                                                <option value="export">Export Selected</option>
                                            </select>
                                            <button type="button" class="btn btn-info" onclick="executeBulkAction('sponsor')">Apply</button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div id="sponsor_pagination" class="pull-right"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sponsorships Tab -->
                            <div role="tabpanel" class="tab-pane" id="sponsorship_tab">
                                <div class="row mtop10">
                                    <div class="col-md-4">
                                        <input type="text" class="form-control search-input" placeholder="Search Sponsorships..." data-target="#sponsorship_table">
                                    </div>
                                    <div class="col-md-2">
                                        <select class="form-control status-filter" data-target="#sponsorship_table">
                                            <option value="">All Status</option>
                                            <option value="active">Active</option>
                                            <option value="pending">Pending</option>
                                            <option value="completed">Completed</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select class="form-control amount-filter" data-target="#sponsorship_table">
                                            <option value="">All Amounts</option>
                                            <option value="0-1000">$0 - $1,000</option>
                                            <option value="1000-5000">$1,000 - $5,000</option>
                                            <option value="5000-10000">$5,000 - $10,000</option>
                                            <option value="10000+">$10,000+</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <a href="<?php echo admin_url('student_sponsor_portal/sponsorship_form'); ?>" class="btn btn-success btn-block">
                                            <i class="fa fa-plus"></i> New Sponsorship
                                        </a>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="pull-right">
                                            <span class="text-muted">Show: </span>
                                            <select class="form-control" id="sponsorship_per_page" style="width: auto; display: inline-block;">
                                                <option value="10">10</option>
                                                <option value="25">25</option>
                                                <option value="50">50</option>
                                                <option value="100">100</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive mtop10">
                                    <table class="table table-bordered table-hover" id="sponsorship_table">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <input type="checkbox" id="select_all_sponsorship" onclick="toggleSelectAll('sponsorship')">
                                                </th>
                                                <th>ID</th>
                                                <th>Student</th>
                                                <th>Sponsor</th>
                                                <th>Amount</th>
                                                <th>Duration</th>
                                                <th>Start Date</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(isset($sponsorships)): ?>
                                                <?php foreach ($sponsorships as $sponsorship): ?>
                                                <tr>
                                                    <td><input type="checkbox" name="selected_sponsorships[]" value="<?php echo $sponsorship['id']; ?>"></td>
                                                    <td>#SP<?php echo str_pad($sponsorship['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                                    <td><?php echo $sponsorship['student_name']; ?></td>
                                                    <td><?php echo $sponsorship['sponsor_name']; ?></td>
                                                    <td><strong>$<?php echo number_format($sponsorship['amount'], 2); ?></strong></td>
                                                    <td><?php echo $sponsorship['duration']; ?> months</td>
                                                    <td><?php echo date('M d, Y', strtotime($sponsorship['start_date'])); ?></td>
                                                    <td>
                                                        <span class="label label-<?php 
                                                            switch($sponsorship['status']) {
                                                                case 'active': echo 'success'; break;
                                                                case 'pending': echo 'warning'; break;
                                                                case 'completed': echo 'info'; break;
                                                                case 'cancelled': echo 'danger'; break;
                                                                default: echo 'default';
                                                            }
                                                        ?>">
                                                            <?php echo ucfirst($sponsorship['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="<?php echo admin_url('student_sponsor_portal/view_sponsorship/'.$sponsorship['id']); ?>" class="btn btn-xs btn-info" title="View">
                                                                <i class="fa fa-eye"></i>
                                                            </a>
                                                            <a href="<?php echo admin_url('student_sponsor_portal/edit_sponsorship/'.$sponsorship['id']); ?>" class="btn btn-xs btn-primary" title="Edit">
                                                                <i class="fa fa-edit"></i>
                                                            </a>
                                                            <a href="<?php echo admin_url('student_sponsor_portal/sponsorship_invoice/'.$sponsorship['id']); ?>" class="btn btn-xs btn-warning" title="Invoice">
                                                                <i class="fa fa-file-text-o"></i>
                                                            </a>
                                                            <a href="#" onclick="deleteRecord('sponsorship', <?php echo $sponsorship['id']; ?>)" class="btn btn-xs btn-danger" title="Delete">
                                                                <i class="fa fa-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="bulk-actions" style="display: none;">
                                            <select class="form-control" id="bulk_action_sponsorship" style="width: auto; display: inline-block;">
                                                <option value="">Bulk Actions</option>
                                                <option value="activate">Activate</option>
                                                <option value="suspend">Suspend</option>
                                                <option value="complete">Mark Complete</option>
                                                <option value="export">Export Selected</option>
                                            </select>
                                            <button type="button" class="btn btn-info" onclick="executeBulkAction('sponsorship')">Apply</button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div id="sponsorship_pagination" class="pull-right"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Reports Tab -->
                            <div role="tabpanel" class="tab-pane" id="reports_tab">
                                <div class="row mtop20">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="panel panel-default">
                                                    <div class="panel-heading">
                                                        <h3 class="panel-title"><i class="fa fa-calendar"></i> Monthly Reports</h3>
                                                    </div>
                                                    <div class="panel-body">
                                                        <div class="form-group">
                                                            <label>Select Month/Year</label>
                                                            <input type="month" class="form-control" id="report_month" value="<?php echo date('Y-m'); ?>">
                                                        </div>
                                                        <button type="button" class="btn btn-info btn-block" onclick="generateMonthlyReport()">
                                                            <i class="fa fa-bar-chart"></i> Generate Report
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="panel panel-default">
                                                    <div class="panel-heading">
                                                        <h3 class="panel-title"><i class="fa fa-users"></i> Custom Reports</h3>
                                                    </div>
                                                    <div class="panel-body">
                                                        <div class="form-group">
                                                            <label>Report Type</label>
                                                            <select class="form-control" id="custom_report_type">
                                                                <option value="student_performance">Student Performance</option>
                                                                <option value="sponsor_analysis">Sponsor Analysis</option>
                                                                <option value="financial_summary">Financial Summary</option>
                                                                <option value="sponsorship_trends">Sponsorship Trends</option>
                                                            </select>
                                                        </div>
                                                        <button type="button" class="btn btn-success btn-block" onclick="generateCustomReport()">
                                                            <i class="fa fa-file-text-o"></i> Generate Report
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="panel panel-default">
                                                    <div class="panel-heading">
                                                        <h3 class="panel-title"><i class="fa fa-download"></i> Export Options</h3>
                                                    </div>
                                                    <div class="panel-body">
                                                        <div class="form-group">
                                                            <label>Export Format</label>
                                                            <select class="form-control" id="export_format">
                                                                <option value="pdf">PDF Report</option>
                                                                <option value="excel">Excel Spreadsheet</option>
                                                                <option value="csv">CSV Data</option>
                                                                <option value="json">JSON Data</option>
                                                            </select>
                                                        </div>
                                                        <button type="button" class="btn btn-warning btn-block" onclick="exportAllData()">
                                                            <i class="fa fa-download"></i> Export All Data
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Quick Stats Summary -->
                                        <div class="row mtop20">
                                            <div class="col-md-12">
                                                <div class="panel panel-default">
                                                    <div class="panel-heading">
                                                        <h3 class="panel-title"><i class="fa fa-dashboard"></i> Quick Statistics</h3>
                                                    </div>
                                                    <div class="panel-body">
                                                        <div class="row">
                                                            <div class="col-md-3">
                                                                <div class="stats-box">
                                                                    <h3><?php echo number_format(isset($total_sponsorship_amount) ? $total_sponsorship_amount : 0, 2); ?>$</h3>
                                                                    <p>Total Sponsorship Amount</p>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="stats-box">
                                                                    <h3><?php echo number_format(isset($avg_sponsorship_amount) ? $avg_sponsorship_amount : 0, 2); ?>$</h3>
                                                                    <p>Average Sponsorship</p>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="stats-box">
                                                                    <h3><?php echo isset($sponsored_students_percentage) ? $sponsored_students_percentage : 0; ?>%</h3>
                                                                    <p>Students Sponsored</p>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="stats-box">
                                                                    <h3><?php echo isset($active_sponsors_percentage) ? $active_sponsors_percentage : 0; ?>%</h3>
                                                                    <p>Active Sponsors</p>
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

                    </div> <!-- /.panel-body -->
                </div> <!-- /.panel_s -->
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js for charts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize date pickers
    $('.datepicker').datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true
    });

    // Initialize charts
    initializeCharts();
    
    // Initialize advanced filtering
    initializeAdvancedFiltering();
    
    // Initialize sorting
    initializeSorting();
    
    // Initialize pagination
    initializePagination();
});

// Refresh Stats
function refreshStats() {
    $.ajax({
        url: '<?php echo admin_url('student_sponsor_portal/get_stats'); ?>',
        type: 'GET',
        dataType: 'json',
        beforeSend: function() {
            $('button[onclick="refreshStats()"]').html('<i class="fa fa-spinner fa-spin"></i> Refreshing...');
        },
        success: function(response) {
            if(response.success) {
                location.reload();
            } else {
                alert('Failed to refresh stats');
            }
        },
        error: function() {
            alert('Failed to refresh stats');
        },
        complete: function() {
            $('button[onclick="refreshStats()"]').html('<i class="fa fa-refresh"></i> Refresh');
        }
    });
}

// Enhanced Client-side Filtering
function initializeAdvancedFiltering() {
    $(document).on('keyup', '.search-input', function () {
        var searchTerm = $(this).val().toLowerCase();
        var targetTable = $($(this).data('target'));

        targetTable.find("tbody tr").each(function () {
            var found = false;
            $(this).find("td").each(function () {
                if ($(this).text().toLowerCase().includes(searchTerm)) {
                    found = true;
                }
            });
            $(this).toggle(found);
        });
        updatePagination(targetTable.attr('id'));
    });

    // Grade filter for school students
    $(document).on('change', '.grade-filter', function() {
        var filterValue = $(this).val().toLowerCase();
        var targetTable = $($(this).data('target'));
        
        targetTable.find("tbody tr").each(function () {
            if(filterValue === '') {
                $(this).show();
            } else {
                var gradeText = $(this).find("td:eq(4)").text().toLowerCase();
                $(this).toggle(gradeText.includes(filterValue));
            }
        });
        updatePagination(targetTable.attr('id'));
    });

    // Program filter for university students
    $(document).on('change', '.program-filter', function() {
        var filterValue = $(this).val().toLowerCase();
        var targetTable = $($(this).data('target'));
        
        targetTable.find("tbody tr").each(function () {
            if(filterValue === '') {
                $(this).show();
            } else {
                var programText = $(this).find("td:eq(4)").text().toLowerCase();
                $(this).toggle(programText.includes(filterValue));
            }
        });
        updatePagination(targetTable.attr('id'));
    });

    // Type filter for sponsors
    $(document).on('change', '.type-filter', function() {
        var filterValue = $(this).val().toLowerCase();
        var targetTable = $($(this).data('target'));
        
        targetTable.find("tbody tr").each(function () {
            if(filterValue === '') {
                $(this).show();
            } else {
                var typeText = $(this).find("td:eq(4)").text().toLowerCase();
                $(this).toggle(typeText.includes(filterValue));
            }
        });
        updatePagination(targetTable.attr('id'));
    });
}

// Apply Global Filters
function applyFilters() {
    var dateFrom = $('#date_from').val();
    var dateTo = $('#date_to').val();
    var status = $('#status_filter').val();
    var type = $('#type_filter').val();
    var search = $('#global_search').val();

    // Apply filters via AJAX
    $.ajax({
        url: '<?php echo admin_url('student_sponsor_portal/apply_filters'); ?>',
        type: 'POST',
        data: {
            date_from: dateFrom,
            date_to: dateTo,
            status: status,
            type: type,
            search: search
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                // Update tables with filtered data
                updateTablesWithFilteredData(response.data);
            }
        },
        error: function() {
            alert('Failed to apply filters');
        }
    });
}

// Initialize Charts
function initializeCharts() {
    // Student Distribution Chart
    var ctx1 = document.getElementById('studentChart').getContext('2d');
    var studentChart = new Chart(ctx1, {
        type: 'doughnut',
        data: {
            labels: ['School Students', 'University Students'],
            datasets: [{
                data: [<?php echo $school_count; ?>, <?php echo $university_count; ?>],
                backgroundColor: ['#337ab7', '#5cb85c'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Sponsorship Status Chart
    var ctx2 = document.getElementById('sponsorshipChart').getContext('2d');
    var sponsorshipChart = new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: ['Active', 'Pending', 'Completed', 'Cancelled'],
            datasets: [{
                label: 'Sponsorships',
                data: [
                    <?php echo isset($active_sponsorship_count) ? $active_sponsorship_count : 0; ?>,
                    <?php echo isset($pending_sponsorship_count) ? $pending_sponsorship_count : 0; ?>,
                    <?php echo isset($completed_sponsorship_count) ? $completed_sponsorship_count : 0; ?>,
                    <?php echo isset($cancelled_sponsorship_count) ? $cancelled_sponsorship_count : 0; ?>
                ],
                backgroundColor: ['#5cb85c', '#f0ad4e', '#5bc0de', '#d9534f'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Toggle Select All
function toggleSelectAll(type) {
    var isChecked = $('#select_all_' + type).is(':checked');
    $('input[name="selected_' + type + 's[]"]').prop('checked', isChecked);
    
    if(isChecked) {
        $('.bulk-actions').show();
    } else {
        $('.bulk-actions').hide();
    }
}

// Execute Bulk Actions
function executeBulkAction(type) {
    var action = $('#bulk_action_' + type).val();
    var selected = $('input[name="selected_' + type + 's[]"]:checked').map(function() {
        return this.value;
    }).get();

    if(action === '' || selected.length === 0) {
        alert('Please select an action and at least one item.');
        return;
    }

    if(confirm('Are you sure you want to ' + action + ' ' + selected.length + ' item(s)?')) {
        $.ajax({
            url: '<?php echo admin_url('student_sponsor_portal/bulk_action'); ?>',
            type: 'POST',
            data: {
                action: action,
                type: type,
                selected: selected
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    location.reload();
                } else {
                    alert('Action failed: ' + response.message);
                }
            },
            error: function() {
                alert('Failed to execute bulk action');
            }
        });
    }
}

// Delete Record
function deleteRecord(type, id) {
    if(confirm('Are you sure you want to delete this ' + type + '?')) {
        $.ajax({
            url: '<?php echo admin_url('student_sponsor_portal/delete'); ?>',
            type: 'POST',
            data: {
                type: type,
                id: id
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    location.reload();
                } else {
                    alert('Delete failed: ' + response.message);
                }
            },
            error: function() {
                alert('Failed to delete record');
            }
        });
    }
}

// Export Data
function exportData(format) {
    var activeTab = $('.nav-tabs li.active a').attr('href').replace('#', '').replace('_tab', '');
    
    window.open('<?php echo admin_url('student_sponsor_portal/export'); ?>?format=' + format + '&type=' + activeTab, '_blank');
}

// Initialize Sorting
function initializeSorting() {
    $('.sort-icon').click(function() {
        var column = $(this).data('column');
        var table = $(this).closest('table');
        var tbody = table.find('tbody');
        var rows = tbody.find('tr').toArray();
        var columnIndex = $(this).closest('th').index();
        
        // Toggle sort direction
        var isAsc = $(this).hasClass('fa-sort-asc');
        $('.sort-icon').removeClass('fa-sort-asc fa-sort-desc').addClass('fa-sort');
        
        if(isAsc) {
            $(this).removeClass('fa-sort fa-sort-asc').addClass('fa-sort-desc');
        } else {
            $(this).removeClass('fa-sort fa-sort-desc').addClass('fa-sort-asc');
        }
        
        // Sort rows
        rows.sort(function(a, b) {
            var aValue = $(a).find('td').eq(columnIndex).text().trim();
            var bValue = $(b).find('td').eq(columnIndex).text().trim();
            
            if(isAsc) {
                return bValue.localeCompare(aValue);
            } else {
                return aValue.localeCompare(bValue);
            }
        });
        
        // Rebuild tbody
        tbody.empty().append(rows);
    });
}

// Initialize Pagination
function initializePagination() {
    $('[id$="_per_page"]').change(function() {
        var tableId = $(this).attr('id').replace('_per_page', '_table');
        updatePagination(tableId);
    });
}

// Update Pagination
function updatePagination(tableId) {
    var table = $('#' + tableId);
    var perPage = parseInt($('#' + tableId.replace('_table', '_per_page')).val());
    var rows = table.find('tbody tr:visible');
    var totalRows = rows.length;
    var totalPages = Math.ceil(totalRows / perPage);
    
    // Hide all rows
    rows.hide();
    
    // Show first page rows
    rows.slice(0, perPage).show();
    
    // Update pagination controls
    var paginationId = tableId.replace('_table', '_pagination');
    var paginationHtml = '';
    
    if(totalPages > 1) {
        paginationHtml += '<ul class="pagination pagination-sm">';
        for(var i = 1; i <= totalPages; i++) {
            paginationHtml += '<li class="' + (i === 1 ? 'active' : '') + '">';
            paginationHtml += '<a href="#" onclick="showPage(\'' + tableId + '\', ' + i + ', ' + perPage + ')">' + i + '</a>';
            paginationHtml += '</li>';
        }
        paginationHtml += '</ul>';
    }
    
    $('#' + paginationId).html(paginationHtml);
}

// Show Page
function showPage(tableId, page, perPage) {
    var table = $('#' + tableId);
    var rows = table.find('tbody tr:visible');
    var start = (page - 1) * perPage;
    var end = start + perPage;
    
    rows.hide();
    rows.slice(start, end).show();
    
    // Update active pagination button
    var paginationId = tableId.replace('_table', '_pagination');
    $('#' + paginationId + ' li').removeClass('active');
    $('#' + paginationId + ' li:eq(' + (page - 1) + ')').addClass('active');
}

// Generate Reports
function generateMonthlyReport() {
    var month = $('#report_month').val();
    window.open('<?php echo admin_url('student_sponsor_portal/monthly_report'); ?>?month=' + month, '_blank');
}

function generateCustomReport() {
    var reportType = $('#custom_report_type').val();
    window.open('<?php echo admin_url('student_sponsor_portal/custom_report'); ?>?type=' + reportType, '_blank');
}

function exportAllData() {
    var format = $('#export_format').val();
    window.open('<?php echo admin_url('student_sponsor_portal/export_all'); ?>?format=' + format, '_blank');
}

// Show selected count when checkboxes are checked
$(document).on('change', 'input[type="checkbox"][name^="selected_"]', function() {
    var type = $(this).attr('name').replace('selected_', '').replace('[]', '');
    var checkedCount = $('input[name="selected_' + type + '[]"]:checked').length;
    
    if(checkedCount > 0) {
        $('.bulk-actions').show();
    } else {
        $('.bulk-actions').hide();
    }
});
</script>

<style>
.stats-box {
    text-align: center;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 5px;
    margin-bottom: 10px;
}

.stats-box h3 {
    margin: 0;
    color: #337ab7;
    font-size: 2em;
}

.stats-box p {
    margin: 5px 0 0 0;
    color: #666;
}

.panel-footer {
    background: #f5f5f5;
    border-top: 1px solid #ddd;
    padding: 10px 15px;
}

.table-hover tbody tr:hover {
    background-color: #f5f5f5;
}

.sort-icon {
    cursor: pointer;
    margin-left: 5px;
}

.sort-icon:hover {
    color: #337ab7;
}

.pagination {
    margin: 0;
}

.bulk-actions {
    padding: 10px 0;
}

.badge {
    font-size: 11px;
}

.btn-group .btn {
    margin-right: 2px;
}

.panel-heading i {
    margin-right: 5px;
}

@media (max-width: 768px) {
    .col-md-3, .col-md-4, .col-md-6 {
        margin-bottom: 15px;
    }
    
    .btn-group .btn {
        display: block;
        width: 100%;
        margin-bottom: 2px;
    }
}
</style>

<?php init_tail(); ?>