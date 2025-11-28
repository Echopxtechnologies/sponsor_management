<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<!-- CRITICAL: Define Avatar Functions BEFORE HTML that uses them -->
<script>
// Avatar Management Functions - Defined Early
function hideSponsorInitials(sponsorId) {
  var avatar = document.getElementById('sponsor-avatar-' + sponsorId);
  var initials = document.getElementById('sponsor-initials-' + sponsorId);
  var image = document.getElementById('sponsor-avatar-img-' + sponsorId);
  
  if (avatar && initials && image) {
    avatar.classList.add('avatar--has-image');
    initials.style.display = 'none';
    image.style.display = 'block';
  }
}

function showSponsorInitials(sponsorId) {
  var avatar = document.getElementById('sponsor-avatar-' + sponsorId);
  var initials = document.getElementById('sponsor-initials-' + sponsorId);
  var image = document.getElementById('sponsor-avatar-img-' + sponsorId);
  
  if (avatar && initials && image) {
    avatar.classList.remove('avatar--has-image');
    initials.style.display = 'block';
    image.style.display = 'none';
  }
}
</script>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <!-- Header Section -->
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="customer-profile-group-heading" style="margin-top:60px; margin-left:13px;">
                                    <i class="fa fa-users"></i> Sponsor Management
                                </h4>
                            </div>
                            <div class="col-md-6">
                                <div class="text-right" style="margin-top:15px;">
                                    <!-- Import Button -->
                                    <a href="<?php echo admin_url('student_sponsor_portal/bulk_import_sponsors'); ?>" 
                                       class="btn btn-warning btn-sm"
                                       style="margin-right: 5px;"
                                       title="Import sponsors from Excel/CSV file">
                                        <i class="fa fa-upload"></i> Import
                                    </a>
                                    
                                    <!-- Export Button -->
                                    <a href="<?php echo admin_url('student_sponsor_portal/export_sponsors'); ?>" 
                                       class="btn btn-success btn-sm"
                                       style="margin-right: 5px;"
                                       title="Export all sponsors to CSV file">
                                        <i class="fa fa-download"></i> Export CSV
                                    </a>
                                    
                                    <!-- New Sponsor Button -->
                                    <a href="<?php echo admin_url('student_sponsor_portal/sponsor_form'); ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fa fa-plus"></i> New Sponsor
                                    </a>
                                </div>
                            </div>
                        </div>

                        <hr class="hr-panel-heading">

                        <!-- Statistics Summary -->
                        <?php if(!empty($sponsors) && count($sponsors) > 0): ?>
                        <div class="row" style="margin-bottom: 15px;">
                            <div class="col-md-12">
                                <div class="alert alert-info" style="margin-bottom: 10px; padding: 8px 15px;">
                                    <i class="fa fa-info-circle"></i>
                                    <strong>Total Sponsors: <?php echo count($sponsors); ?></strong>
                                    <span class="pull-right">
                                        <small>Click on sponsor name to view/edit details</small>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Sponsors Table -->
                        <div class="table-responsive">
                            <table class="table table-hover sponsors-table dt-table" id="sponsors-table" width="100%">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="18%">Sponsor Details</th>
                                        <th width="10%">Type</th>
                                        <th width="15%">Contact Info</th>
                                        <th width="25%">Sponsored Students</th>
                                        <th width="15%">Financial Summary</th>
                                        <th width="7%">Status</th>
                                        <th width="5%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($sponsors)): ?>
                                        <?php 
                                            $serial = 1;
                                            $active_sponsoring_count = 0;
                                            $active_no_students_count = 0;
                                            $inactive_count = 0;
                                            
                                            foreach($sponsors as $index => $sponsor): 
                                        ?>
                                        <?php
                                            $sponsor_id = (int)($sponsor['id'] ?? 0);
                                            $sponsor_name = (string)($sponsor['name'] ?? '');
                                            $sponsor_type = (string)($sponsor['sponsor_type'] ?? '');
                                            $sponsor_email = (string)($sponsor['email'] ?? '');
                                            $sponsor_phone = (string)($sponsor['contact_no'] ?? '');
                                            $sponsor_frequency = (string)($sponsor['sponsor_frequency'] ?? '');
                                            $staff_id = $sponsor['staff_id'] ?? null;
                                            $active_flag = (int)($sponsor['active'] ?? 0);

                                            // Get sponsored students counts and names
                                            $school_students_count = (int)($sponsor['school_students_count'] ?? 0);
                                            $university_students_count = (int)($sponsor['university_students_count'] ?? 0);
                                            $total_students_count = $school_students_count + $university_students_count;
                                            
                                            // Get student names data
                                            $student_names_data = $sponsor['sponsored_student_names'] ?? [
                                                'school_students' => [],
                                                'university_students' => [],
                                                'school_names_display' => '',
                                                'university_names_display' => '',
                                                'school_total_count' => 0,
                                                'university_total_count' => 0,
                                                'school_has_more' => false,
                                                'university_has_more' => false
                                            ];
                                            
                                            // Get financial info
                                            $total_commitment = (float)($sponsor['total_commitment'] ?? 0);
                                            $total_paid = (float)($sponsor['total_paid'] ?? 0);
                                            $total_balance = $total_commitment - $total_paid;
                                            $total_transactions = (int)($sponsor['total_transactions'] ?? 0);

                                            // Determine status
                                            $sponsor_status = 'inactive';
                                            $sponsor_status_class = 'danger';
                                            $sponsor_status_label = 'Inactive';
                                            
                                            if ($active_flag == 1) {
                                                if ($total_students_count > 0) {
                                                    $sponsor_status = 'active_sponsoring';
                                                    $sponsor_status_class = 'success';
                                                    $sponsor_status_label = 'Active & Sponsoring';
                                                    $active_sponsoring_count++;
                                                } else {
                                                    $sponsor_status = 'active_no_students';
                                                    $sponsor_status_class = 'warning';
                                                    $sponsor_status_label = 'Active (No Students)';
                                                    $active_no_students_count++;
                                                }
                                            } else {
                                                $inactive_count++;
                                            }

                                            // Create initials from sponsor name
                                            $initials = '';
                                            foreach (preg_split('/\s+/', trim($sponsor_name)) as $p) {
                                                if ($p !== '' && strlen($initials) < 2) $initials .= strtoupper(substr($p, 0, 1));
                                            }
                                            if ($initials === '') $initials = 'S';
                                        ?>
                                        <tr class="sponsor-row" 
                                            id="sponsor-row-<?php echo $sponsor_id; ?>"
                                            data-sponsor-id="<?php echo $sponsor_id; ?>"
                                            data-type="<?php echo html_escape(mb_strtolower($sponsor_type)); ?>"
                                            data-frequency="<?php echo html_escape(mb_strtolower($sponsor_frequency)); ?>"
                                            data-status="<?php echo html_escape($sponsor_status); ?>"
                                            data-students-count="<?php echo $total_students_count; ?>">
                                            
                                            <td><strong><?php echo $serial; ?></strong></td>
                                            
                                            <!-- Sponsor Details -->
                                            <td>
                                                <div class="media">
                                                    <div class="media-left">
                                                        <div class="avatar" id="sponsor-avatar-<?php echo $sponsor_id; ?>">
                                                            <span class="avatar__initials" id="sponsor-initials-<?php echo $sponsor_id; ?>">
                                                                <?php echo html_escape($initials); ?>
                                                            </span>
                                                            <!-- Future: Add sponsor photo support -->
                                                            <!-- <img data-src="<?php echo admin_url('student_sponsor_portal/display_sponsor_photo/' . $sponsor_id); ?>"
                                                                 alt="<?php echo html_escape($sponsor_name); ?>" 
                                                                 class="avatar__image"
                                                                 id="sponsor-avatar-img-<?php echo $sponsor_id; ?>"
                                                                 onload="hideSponsorInitials(<?php echo $sponsor_id; ?>)"
                                                                 onerror="showSponsorInitials(<?php echo $sponsor_id; ?>)"
                                                                 style="display:none;"> -->
                                                        </div>
                                                    </div>
                                                    <div class="media-body">
                                                        <strong>
                                                            <a href="<?php echo admin_url('student_sponsor_portal/sponsor_form/' . $sponsor_id); ?>">
                                                                <?php echo htmlspecialchars($sponsor_name); ?>
                                                            </a>
                                                        </strong>
                                                        <br><small class="text-muted">ID: <?php echo $sponsor_id; ?></small>
                                                        <?php if(!empty($sponsor['city'])): ?>
                                                            <br><small class="text-muted"><i class="fa fa-map-marker"></i> <?php echo htmlspecialchars($sponsor['city']); ?></small>
                                                        <?php endif; ?>
                                                        <div class="row-options" style="display:none;">
                                                            <?php if(has_permission('student_sponsor_portal', '', 'edit')): ?>
                                                            <a href="<?php echo admin_url('student_sponsor_portal/sponsor_form/' . $sponsor_id); ?>">Edit</a> |
                                                            <?php endif; ?>
                                                            <?php if(has_permission('student_sponsor_portal', '', 'delete')): ?>
                                                            <a href="#" onclick="deleteSponsor(<?php echo $sponsor_id; ?>); return false;" class="text-danger">Delete</a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Type -->
                                            <td>
                                                <?php if(!empty($sponsor_type)): ?>
                                                    <?php if($sponsor_type == 'individual'): ?>
                                                        <span class="label label-primary"><i class="fa fa-user"></i> Individual</span>
                                                    <?php elseif($sponsor_type == 'company'): ?>
                                                        <span class="label label-info"><i class="fa fa-building"></i> Company</span>
                                                    <?php else: ?>
                                                        <span class="label label-default"><?php echo ucfirst($sponsor_type); ?></span>
                                                    <?php endif; ?>
                                                    <?php if(!empty($sponsor_frequency)): ?>
                                                        <br><small class="text-muted"><?php echo ucfirst($sponsor_frequency); ?> donor</small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not set</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Contact Info -->
                                            <td>
                                                <div class="contact-info">
                                                    <?php if(!empty($sponsor_email)): ?>
                                                        <div style="margin-bottom: 3px;">
                                                            <a href="mailto:<?php echo html_escape($sponsor_email); ?>" class="text-primary">
                                                                <i class="fa fa-envelope" style="width: 12px;"></i> <?php echo htmlspecialchars($sponsor_email); ?>
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if(!empty($sponsor_phone)): ?>
                                                        <div>
                                                            <a href="tel:<?php echo html_escape($sponsor_phone); ?>" class="text-success">
                                                                <i class="fa fa-phone" style="width: 12px;"></i> <?php echo htmlspecialchars($sponsor_phone); ?>
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if(empty($sponsor_email) && empty($sponsor_phone)): ?>
                                                        <span class="text-muted">No contact info</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>

                                            <!-- Sponsored Students - Enhanced with Names -->
                                            <td>
                                                <?php if($total_students_count > 0): ?>
                                                    <div class="sponsored-students-names">
                                                        <!-- School Students -->
                                                        <?php if($student_names_data['school_total_count'] > 0): ?>
                                                            <div class="student-category-section" style="margin-bottom: 8px;">
                                                                <div class="category-header">
                                                                    <span class="label label-primary" style="font-size: 9px;">
                                                                     School (<?php echo $student_names_data['school_total_count']; ?>)
                                                                    </span>
                                                                </div>
                                                                <div class="student-names-list" style="margin-top: 3px; font-size: 11px; line-height: 1.3;">
                                                                    <?php if(!empty($student_names_data['school_names_display'])): ?>
                                                                        <span class="text-primary student-names-text" 
                                                                              title="<?php echo html_escape($student_names_data['school_names_display']); ?>">
                                                                            <?php echo htmlspecialchars($student_names_data['school_names_display']); ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <!-- University Students -->
                                                        <?php if($student_names_data['university_total_count'] > 0): ?>
                                                            <div class="student-category-section" style="margin-bottom: 8px;">
                                                                <div class="category-header">
                                                                    <span class="label label-success" style="font-size: 9px;">
                                                                      University (<?php echo $student_names_data['university_total_count']; ?>)
                                                                    </span>
                                                                </div>
                                                                <div class="student-names-list" style="margin-top: 3px; font-size: 11px; line-height: 1.3;">
                                                                    <?php if(!empty($student_names_data['university_names_display'])): ?>
                                                                        <span class="text-success student-names-text" 
                                                                              title="<?php echo html_escape($student_names_data['university_names_display']); ?>">
                                                                            <?php echo htmlspecialchars($student_names_data['university_names_display']); ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="no-students">
                                                        <span class="text-muted" style="font-size: 11px;">
                                                            <i class="fa fa-users text-muted"></i> No students sponsored
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Financial Summary -->
                                            <td>
                                                <?php if($total_commitment > 0 || $total_paid > 0): ?>
                                                    <div class="financial-summary" style="font-size: 11px;">
                                                        <?php if($total_commitment > 0): ?>
                                                        <div>
                                                            <strong>Committed:</strong> ₹<?php echo number_format($total_commitment, 2); ?>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if($total_paid > 0): ?>
                                                        <div class="text-success">
                                                            <strong>Paid:</strong> ₹<?php echo number_format($total_paid, 2); ?>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if($total_balance != 0): ?>
                                                        <div class="<?php echo $total_balance > 0 ? 'text-warning' : 'text-info'; ?>">
                                                            <strong><?php echo $total_balance > 0 ? 'Balance' : 'Overpaid'; ?>:</strong> 
                                                            ₹<?php echo number_format(abs($total_balance), 2); ?>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if($total_transactions > 0): ?>
                                                        <div class="text-muted">
                                                            <?php echo $total_transactions; ?> transaction<?php echo $total_transactions > 1 ? 's' : ''; ?>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted" style="font-size: 11px;">No financial data</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Status -->
                                            <td>
                                                <span class="label label-<?php echo $sponsor_status_class; ?>" 
                                                      title="<?php echo $sponsor_status_label; ?>" 
                                                      style="font-size: 10px;">
                                                    <?php if($sponsor_status == 'active_sponsoring'): ?>
                                                        <i class="fa fa-check-circle"></i>
                                                    <?php elseif($sponsor_status == 'active_no_students'): ?>
                                                        <i class="fa fa-exclamation-triangle"></i>
                                                    <?php else: ?>
                                                        <i class="fa fa-times-circle"></i>
                                                    <?php endif; ?>
                                                    <?php echo $sponsor_status_label; ?>
                                                </span>
                                            </td>

                                            <!-- Actions -->
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-default btn-xs dropdown-toggle" type="button" data-toggle="dropdown">
                                                        <i class="fa fa-cog"></i>
                                                        <span class="caret"></span>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-right">
                                                        <?php if(has_permission('student_sponsor_portal', '', 'edit')): ?>
                                                        <li><a href="<?php echo admin_url('student_sponsor_portal/sponsor_form/' . $sponsor_id); ?>"><i class="fa fa-edit"></i> Edit</a></li>
                                                        <?php endif; ?>
                                                    
                                                        <?php if(has_permission('student_sponsor_portal', '', 'delete')): ?>
                                                        <li class="divider"></li>
                                                        <li><a href="#" onclick="deleteSponsor(<?php echo $sponsor_id; ?>); return false;" class="text-danger"><i class="fa fa-trash"></i> Delete</a></li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php 
                                            $serial++;
                                        endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">
                                                <div style="padding: 40px;">
                                                    <i class="fa fa-users fa-3x text-muted"></i>
                                                    <h4 class="text-muted">No sponsors found</h4>
                                                    <p class="text-muted">Get started by adding your first sponsor.</p>
                                                    <a href="<?php echo admin_url('student_sponsor_portal/sponsor_form'); ?>" class="btn btn-primary">
                                                        <i class="fa fa-plus"></i> Add First Sponsor
                                                    </a>
                                                </div>
                                            </td>
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

<!-- Sponsored Students Modal -->
<div class="modal fade" id="sponsoredStudentsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-graduation-cap"></i> Students Sponsored by <span id="sponsor-name-display"></span>
                </h4>
            </div>
            <div class="modal-body" id="sponsored-students-content">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p>Loading sponsored students...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="manage-students-btn" onclick="manageStudents()">
                    <i class="fa fa-edit"></i> Manage Students
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Sponsor Details Modal -->
<div class="modal fade" id="viewSponsorModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-user"></i> Sponsor Details
                </h4>
            </div>
            <div class="modal-body" id="sponsor-details-content">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p>Loading sponsor details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="edit-sponsor-btn" onclick="editCurrentSponsor()">
                    <i class="fa fa-edit"></i> Edit
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* DataTables Custom Styling */
.dataTables_wrapper {
  padding: 0;
  margin-top: 15px;
}

.dataTables_wrapper .dataTables_length {
  float: left;
  margin-bottom: 15px;
}

.dataTables_wrapper .dataTables_length select {
  padding: 5px;
  border: 1px solid #ddd;
  border-radius: 3px;
  margin: 0 5px;
}

.dataTables_wrapper .dataTables_filter {
  float: right;
  margin-bottom: 15px;
  text-align: right;
}

.dataTables_wrapper .dataTables_filter label {
  font-weight: normal;
  margin-bottom: 0;
}

.dataTables_wrapper .dataTables_filter input {
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 6px 12px;
  margin-left: 8px;
  width: 250px;
  display: inline-block;
}

.dataTables_wrapper .dataTables_info {
  float: left;
  padding-top: 8px;
  font-size: 13px;
  color: #666;
}

.dataTables_wrapper .dataTables_paginate {
  float: right;
  text-align: right;
  padding-top: 0;
  margin: 0;
}

/* Pagination Buttons - FIXED STYLING */
.dataTables_wrapper .dataTables_paginate .paginate_button {
  box-sizing: border-box;
  display: inline-block;
  min-width: 32px;
  padding: 6px 12px;
  margin-left: 2px;
  margin-right: 2px;
  text-align: center;
  text-decoration: none !important;
  cursor: pointer;
  color: #333 !important;
  border: 1px solid #ddd;
  background-color: #fff;
  border-radius: 3px;
  transition: all 0.2s ease;
  font-size: 13px;
  line-height: 1.42857143;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
  background-color: #f5f5f5;
  border-color: #ccc;
  color: #333 !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
  background-color: #007bff;
  color: #fff !important;
  border-color: #007bff;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover,
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled:active {
  cursor: not-allowed;
  color: #999 !important;
  border-color: #ddd;
  background-color: #fff;
  opacity: 0.5;
}

.dataTables_wrapper .dataTables_paginate .ellipsis {
  padding: 0 10px;
  color: #999;
}

/* Clear floats after pagination */
.dataTables_wrapper:after {
  visibility: hidden;
  display: block;
  content: "";
  clear: both;
  height: 0;
}

/* Enhanced Sponsors List Styling */
.sponsors-table {
    border: 1px solid #e9ecef;
    border-radius: 6px;
    overflow: hidden;
}

.sponsors-table th { 
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    font-weight: 600; 
    font-size: 12px; 
    border-bottom: 2px solid #dee2e6;
    color: #495057;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 12px 8px;
}

.sponsors-table td { 
    vertical-align: middle; 
    font-size: 13px;
    padding: 12px 8px;
    border-bottom: 1px solid #f1f3f4;
}

.label { 
    font-size: 10px; 
    padding: 4px 8px;
    border-radius: 12px;
    font-weight: 500;
}

.label-success { background-color: #28a745; color: white; }
.label-warning { background-color: #ffc107; color: #212529; }
.label-info { background-color: #17a2b8; color: white; }
.label-default { background-color: #6c757d; color: white; }
.label-primary { background-color: #007bff; color: white; }
.label-danger { background-color: #dc3545; color: white; }

/* Contact Info Styling */
.contact-info div {
  margin-bottom: 2px;
  font-size: 12px;
}

.contact-info a {
  text-decoration: none;
}

.contact-info a:hover {
  text-decoration: underline;
}

/* Row Options (Action Links) */
.row-options { 
  font-size: 11px; 
  color: #777; 
  display: none !important; 
  margin-top: 3px; 
}
.row-options a { 
  color: #777; 
  text-decoration: none; 
}
.row-options a:hover { 
  color: #333; 
  text-decoration: none; 
}
.row-options a.text-danger { 
  color: #dc3545 !important; 
}
.row-options a.text-danger:hover { 
  color: #c82333 !important; 
}

.sponsor-row {
    transition: all 0.2s ease;
}

.sponsor-row:hover { 
    background: #f8f9fa;
    transform: translateX(2px);
}

.sponsor-row:hover .row-options { 
  display: block !important; 
}

.avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid #e9ecef;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: all 0.3s ease;
}

.avatar:hover {
    border-color: #007bff;
    transform: scale(1.05);
}

.avatar__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  position: absolute;
  top: 0;
  left: 0;
  z-index: 2;
  display: none;
}

.avatar__initials {
    position: absolute;
    font-size: 13px;
    color: #6c757d;
    line-height: 1;
    text-align: center;
    z-index: 1;
    font-weight: 600;
    display: block;
}

.avatar--has-image .avatar__initials {
  display: none !important;
}

.avatar--has-image .avatar__image {
  display: block !important;
}

.media-left { 
    padding-right: 12px; 
}

/* Enhanced Student Names Styling */
.sponsored-students-names {
    padding: 8px;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.student-category-section {
    margin-bottom: 6px;
}

.category-header {
    margin-bottom: 4px;
}

.student-names-list {
    background: white;
    padding: 6px 8px;
    border-radius: 4px;
    border: 1px solid #e3e6ea;
    font-weight: 500;
}

.student-names-text {
    display: block;
    word-wrap: break-word;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100%;
}

.students-summary {
    text-align: center;
}

.financial-summary {
    line-height: 1.4;
}

.no-students {
    text-align: center;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

/* Modal styling */
.modal-lg { 
    width: 900px; 
}

.sponsored-students-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    font-size: 11px;
    padding: 8px;
}

.sponsored-students-table td {
    font-size: 12px;
    padding: 8px;
    vertical-align: middle;
}

.student-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 600;
    color: #1976d2;
    margin-right: 8px;
}

/* Alert Styling */
.alert-info {
  background-color: #d1ecf1;
  border-color: #bee5eb;
  color: #0c5460;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .text-right {
        text-align: left !important;
    }
    
    .sponsors-table th,
    .sponsors-table td {
        padding: 6px 4px;
        font-size: 11px;
    }
    
    .avatar {
        width: 30px;
        height: 30px;
    }
    
    .avatar__initials {
        font-size: 10px;
    }
    
    .sponsored-students-names {
        padding: 4px;
    }
    
    .student-names-list {
        padding: 4px 6px;
        font-size: 10px;
    }
    
    .dataTables_wrapper .dataTables_filter input {
        width: 150px;
    }
    
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        float: none;
        text-align: left;
    }
    
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        float: none;
        text-align: center;
        margin-top: 10px;
    }
}

/* Hide specific columns on mobile */
@media (max-width: 992px) {
    .sponsors-table th:nth-child(6),
    .sponsors-table td:nth-child(6) {
        display: none;
    }
}

@media (max-width: 768px) {
    .sponsors-table th:nth-child(3),
    .sponsors-table td:nth-child(3),
    .sponsors-table th:nth-child(4),
    .sponsors-table td:nth-child(4) {
        display: none;
    }
}

/* Enhanced tooltips for long student names */
.student-names-text[title] {
    cursor: help;
}
</style>

<script>
// Wait for jQuery and then execute
(function waitForjQuery() {
  if (typeof window.jQuery === 'undefined') {
    return setTimeout(waitForjQuery, 50);
  }

  (function($) {

    // Global variables
    let currentSponsorId = null;
    let currentSponsorName = '';

    // Expose delete function globally
    window.deleteSponsor = function(sponsorId) {
        if (!confirm('Are you sure you want to delete this sponsor? This will remove all sponsorship relationships but keep student records intact.')) {
            return;
        }

        var $row = $('#sponsor-row-' + sponsorId).css('opacity', '0.5');

        $.ajax({
            url: '<?php echo admin_url("student_sponsor_portal/delete_sponsor"); ?>',
            type: 'POST',
            data: { 
                sponsor_id: sponsorId,
                <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            dataType: 'json',
            success: function(response) {
                if(response && response.success) {
                    // Remove the row from DataTable
                    var table = $.fn.DataTable && $.fn.DataTable.isDataTable('#sponsors-table')
                        ? $('#sponsors-table').DataTable()
                        : null;

                    if (table) {
                        table.row('#sponsor-row-' + sponsorId).remove().draw();
                    } else {
                        $('#sponsor-row-' + sponsorId).remove();
                    }
                    
                    if (typeof alert_float === 'function') {
                        alert_float('success', response.message || 'Sponsor deleted successfully');
                    } else {
                        alert('Sponsor deleted successfully');
                    }
                    
                    // Update CSRF token
                    if (typeof csrfData !== 'undefined' && response[csrfData.token_name]) {
                        csrfData.hash = response[csrfData.token_name];
                    }
                } else {
                    $row.css('opacity', '1');
                    var msg = (response && response.message) || 'Error deleting sponsor';
                    if (typeof alert_float === 'function') {
                        alert_float('danger', msg);
                    } else {
                        alert('Error: ' + msg);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Delete Error:', xhr.responseText);
                $row.css('opacity', '1');
                if (typeof alert_float === 'function') {
                    alert_float('danger', 'Error deleting sponsor');
                } else {
                    alert('Error deleting sponsor');
                }
            }
        });
    };

    // View sponsored students
    window.viewSponsoredStudents = function(sponsorId, sponsorName) {
        currentSponsorId = sponsorId;
        currentSponsorName = sponsorName;
        
        $('#sponsor-name-display').text(sponsorName);
        $('#sponsoredStudentsModal').modal('show');
        
        $.ajax({
            url: '<?php echo admin_url("student_sponsor_portal/get_sponsored_students"); ?>',
            type: 'POST',
            data: { 
                sponsor_id: sponsorId,
                <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#sponsored-students-content').html(response.html);
                    
                    // Update CSRF token
                    if (typeof csrfData !== 'undefined' && response[csrfData.token_name]) {
                        csrfData.hash = response[csrfData.token_name];
                    }
                } else {
                    $('#sponsored-students-content').html('<div class="alert alert-danger">' + (response.message || 'Error loading students') + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                $('#sponsored-students-content').html('<div class="alert alert-danger">Error loading sponsored students</div>');
            }
        });
    };

    // View sponsor details
    window.viewSponsorDetails = function(sponsorId) {
        currentSponsorId = sponsorId;
        $('#viewSponsorModal').modal('show');
        
        $.ajax({
            url: '<?php echo admin_url("student_sponsor_portal/get_sponsor"); ?>',
            type: 'POST',
            data: { 
                sponsor_id: sponsorId,
                action: 'view',
                <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#sponsor-details-content').html(response.html);
                    
                    // Update CSRF token
                    if (typeof csrfData !== 'undefined' && response[csrfData.token_name]) {
                        csrfData.hash = response[csrfData.token_name];
                    }
                } else {
                    $('#sponsor-details-content').html('<div class="alert alert-danger">' + (response.message || 'Error loading sponsor') + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                $('#sponsor-details-content').html('<div class="alert alert-danger">Error loading sponsor details</div>');
            }
        });
    };

    // Other global functions
    window.editCurrentSponsor = function() {
        if (currentSponsorId) {
            window.location.href = '<?php echo admin_url("student_sponsor_portal/sponsor_form/"); ?>' + currentSponsorId;
        }
    };

    window.manageStudents = function() {
        if (currentSponsorId) {
            window.location.href = '<?php echo admin_url("student_sponsor_portal/manage_sponsored_students/"); ?>' + currentSponsorId;
        }
    };

    window.assignStudentsToSponsor = function(sponsorId) {
        window.location.href = '<?php echo admin_url("student_sponsor_portal/assign_students/"); ?>' + sponsorId;
    };

    // -------------------------
    // WAIT FOR PERFEX DATATABLE INITIALIZATION
    // -------------------------
    var tries = 0;
    (function waitForPerfexDT() {
      var $tbl = $('#sponsors-table');

      // Check if table exists AND DataTables is initialized by Perfex
      if ($tbl.length && $.fn.DataTable && $.fn.DataTable.isDataTable($tbl)) {
        var sponsorsTable = $tbl.DataTable();
        console.log('✅ Hooked into existing Sponsors DataTable');

        // Update serial numbers on every draw
        sponsorsTable.on('draw', function () {
          var api = sponsorsTable;
          var startIndex = api.context[0]._iDisplayStart;
          api.column(0, { page: 'current' }).nodes().each(function(cell, i) {
            cell.innerHTML = '<strong>' + (startIndex + i + 1) + '</strong>';
          });
        });

        // Row hover effects for action links
        $(document).on('mouseenter', '.sponsor-row', function() {
          $(this).find('.row-options').show();
        }).on('mouseleave', '.sponsor-row', function() {
          $(this).find('.row-options').hide();
        });

        // View students button click
        $(document).on('click', '.view-students-btn', function() {
            var sponsorId = $(this).data('sponsor-id');
            var sponsorName = $(this).data('sponsor-name');
            viewSponsoredStudents(sponsorId, sponsorName);
        });
        
        // Assign students button click
        $(document).on('click', '.assign-students-btn', function() {
            var sponsorId = $(this).data('sponsor-id');
            assignStudentsToSponsor(sponsorId);
        });
        
        // Enhanced tooltip handling for student names
        $('.student-names-text[title]').on('mouseenter', function() {
            var $this = $(this);
            var title = $this.attr('title');
            if (title && title.length > 50) {
                $this.tooltip({
                    placement: 'top',
                    trigger: 'hover',
                    container: 'body',
                    html: false,
                    title: title
                }).tooltip('show');
            }
        });

        // Focus on search box with keyboard shortcut (Ctrl+F or Cmd+F)
        $(document).on('keydown', function(e) {
          if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            $('.dataTables_filter input').focus();
          }
        });

        return;
      }

      // Not ready yet → try again
      if (tries < 120) {    // ~12 seconds total
        tries++;
        return setTimeout(waitForPerfexDT, 100);
      } else {
        console.warn('DataTable on #sponsors-table was not initialized by Perfex.');
      }
    })();

  })(window.jQuery);

})();
</script>

<?php init_tail(); ?>