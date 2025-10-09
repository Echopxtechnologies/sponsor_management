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
                            <div class="col-md-6">
                                <h4 class="customer-profile-group-heading" style="margin-top:60px;">
                                    <i class="fa fa-users"></i> Sponsor Management
                                </h4>
                            </div>
                            <div class="col-md-6">
                                <div class="text-right" style="margin-top:15px;">
                                    <!-- Export Button -->
                                    <div class="btn-group" role="group" style="margin-right: 5px;">
                                        <a href="<?php echo admin_url('student_sponsor_portal/export_sponsors'); ?>" 
                                           class="btn btn-success btn-sm"
                                           title="Export all sponsors to CSV file">
                                            <i class="fa fa-download"></i> Export CSV
                                        </a>
                                    </div>
                                    <!-- New Sponsor Button -->
                                    <div class="btn-group" role="group">
                                        <a href="<?php echo admin_url('student_sponsor_portal/sponsor_form'); ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fa fa-plus"></i> New Sponsor
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="hr-panel-heading">

                        <!-- Sponsors Table -->
                        <div class="table-responsive">
                            <table class="table table-hover sponsors-table" id="sponsors-table">
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
                                            data-type="<?php echo html_escape(mb_strtolower($sponsor_type)); ?>"
                                            data-frequency="<?php echo html_escape(mb_strtolower($sponsor_frequency)); ?>"
                                            data-status="<?php echo html_escape($sponsor_status); ?>"
                                            data-students-count="<?php echo $total_students_count; ?>">
                                            
                                            <td><strong><?php echo $serial; ?></strong></td>
                                            
                                            <!-- Sponsor Details -->
                                            <td>
                                                <div class="media">
                                                    <div class="media-left">
                                                        <div class="avatar">
                                                            <span class="avatar__initials">
                                                                <?php echo html_escape($initials); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="media-body">
                                                        <strong><?php echo htmlspecialchars($sponsor_name); ?></strong>
                                                        <br><small class="text-muted">ID: <?php echo $sponsor_id; ?></small>
                                                        <?php if(!empty($sponsor['city'])): ?>
                                                            <br><small class="text-muted"><i class="fa fa-map-marker"></i> <?php echo htmlspecialchars($sponsor['city']); ?></small>
                                                        <?php endif; ?>
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
                                                <?php if(!empty($sponsor_email)): ?>
                                                    <div style="margin-bottom: 3px;">
                                                        <i class="fa fa-envelope text-muted" style="width: 12px;"></i>
                                                        <a href="mailto:<?php echo html_escape($sponsor_email); ?>" style="font-size: 11px;">
                                                            <?php echo htmlspecialchars($sponsor_email); ?>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if(!empty($sponsor_phone)): ?>
                                                    <div>
                                                        <i class="fa fa-phone text-muted" style="width: 12px;"></i>
                                                        <span style="font-size: 11px;"><?php echo htmlspecialchars($sponsor_phone); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if(empty($sponsor_email) && empty($sponsor_phone)): ?>
                                                    <span class="text-muted">No contact info</span>
                                                <?php endif; ?>
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

.sponsor-row {
    transition: all 0.2s ease;
}

.sponsor-row:hover { 
    background: #f8f9fa;
    transform: translateX(2px);
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
    transition: all 0.3s ease;
}

.avatar:hover {
    border-color: #007bff;
    transform: scale(1.05);
}

.avatar__initials {
    font-size: 13px;
    color: #6c757d;
    line-height: 1;
    font-weight: 600;
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

/* Responsive adjustments */
@media (max-width: 768px) {
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
// Global variables
let currentSponsorId = null;
let currentSponsorName = '';

$(document).ready(function() {
    // Initialize DataTable
    var table = $('#sponsors-table').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[1, "asc"]],
        columnDefs: [
            { orderable: false, targets: [7] }, // Actions column
            { searchable: false, targets: [0] }, // Serial number column
            { orderable: false, targets: [4] }   // Sponsored students column (names are dynamic)
        ],
        language: {
            emptyTable: "No sponsors found",
            zeroRecords: "No matching sponsors found",
            info: "Showing _START_ to _END_ of _TOTAL_ sponsors",
            infoEmpty: "Showing 0 to 0 of 0 sponsors",
            infoFiltered: "(filtered from _MAX_ total sponsors)"
        }
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
});

// View sponsored students
function viewSponsoredStudents(sponsorId, sponsorName) {
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
}

// View sponsor details
function viewSponsorDetails(sponsorId) {
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
}

// View sponsor transactions
function viewSponsorTransactions(sponsorId) {
    window.location.href = '<?php echo admin_url("student_sponsor_portal/sponsor_transactions/"); ?>' + sponsorId;
}

// Edit current sponsor
function editCurrentSponsor() {
    if (currentSponsorId) {
        window.location.href = '<?php echo admin_url("student_sponsor_portal/sponsor_form/"); ?>' + currentSponsorId;
    }
}

// Manage students
function manageStudents() {
    if (currentSponsorId) {
        window.location.href = '<?php echo admin_url("student_sponsor_portal/manage_sponsored_students/"); ?>' + currentSponsorId;
    }
}

// Assign students to sponsor
function assignStudentsToSponsor(sponsorId) {
    window.location.href = '<?php echo admin_url("student_sponsor_portal/assign_students/"); ?>' + sponsorId;
}

// Delete sponsor
function deleteSponsor(sponsorId) {
    if (!confirm('Are you sure you want to delete this sponsor? This will remove all sponsorship relationships but keep student records intact.')) {
        return;
    }

    var row = $('#sponsor-row-' + sponsorId).css('opacity', '0.5');

    $.ajax({
        url: '<?php echo admin_url("student_sponsor_portal/delete_sponsor"); ?>',
        type: 'POST',
        data: { 
            sponsor_id: sponsorId,
            <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                // Remove the row from DataTable
                if ($.fn.DataTable && $.fn.DataTable.isDataTable('#sponsors-table')) {
                    var table = $('#sponsors-table').DataTable();
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
                row.css('opacity', '1');
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
            row.css('opacity', '1');
            if (typeof alert_float === 'function') {
                alert_float('danger', 'Error deleting sponsor');
            } else {
                alert('Error deleting sponsor');
            }
        }
    });
}
</script>

<?php init_tail(); ?>