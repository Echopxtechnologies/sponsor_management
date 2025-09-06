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
                                <h4 class="customer-profile-group-heading" style="margin-top:50px;">
                                    <i class="fa fa-users"></i> Sponsor Management
                                </h4>
                            </div>
                            <div class="col-md-4 text-right">
                                <a href="<?php echo admin_url('student_sponsor_portal/export_sponsors'); ?>" class="btn btn-success">
                                    <i class="fa fa-download"></i> Export Sponsors
                                </a>
                                <a href="<?php echo admin_url('student_sponsor_portal/sponsor_form'); ?>" class="btn btn-primary">
                                    <i class="fa fa-plus"></i> New Sponsor
                                </a>
                            </div>
                        </div>

                        <hr class="hr-panel-heading">

                        <!-- Filters and Search -->
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="filter_status">Access Status</label>
                                    <select id="filter_status" class="form-control selectpicker">
                                        <option value="">All Status</option>
                                        <option value="verified">Verified</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="unverified">Unverified</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="filter_type">Sponsor Type</label>
                                    <select id="filter_type" class="form-control selectpicker">
                                        <option value="">All Types</option>
                                        <option value="individual">Individual</option>
                                        <option value="company">Company</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="filter_frequency">Frequency</label>
                                    <select id="filter_frequency" class="form-control selectpicker">
                                        <option value="">All Frequencies</option>
                                        <option value="one_time">One-time</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="quarterly">Quarterly</option>
                                        <option value="half_yearly">Half-yearly</option>
                                        <option value="yearly">Yearly</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="search_sponsors">Search</label>
                                    <input type="text" id="search_sponsors" class="form-control" placeholder="Search sponsors (name, email, phone)...">
                                </div>
                            </div>
                        </div>

                        <!-- Sponsors Table -->
                        <div class="table-responsive">
                            <table class="table table-hover sponsors-table" id="sponsors-table">
                                <thead>
                                    <tr>
                                        <th width="6%">ID</th>
                                        <th width="28%">Sponsor Name</th>
                                        <th width="10%">Type</th>
                                        <th width="16%">Email</th>
                                        <th width="12%">Phone</th>
                                        <th width="10%">Access Status</th>
                                        <th width="10%">Frequency</th>
                                        <th width="8%">Membership</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($sponsors)): ?>
                                        <?php foreach($sponsors as $index => $sponsor): ?>
                                        <?php
                                            $sponsor_id = (int)($sponsor['id'] ?? 0);
                                            $sponsor_name = (string)($sponsor['name'] ?? '');
                                            $sponsor_type = (string)($sponsor['sponsor_type'] ?? '');
                                            $sponsor_email = (string)($sponsor['email'] ?? '');
                                            $sponsor_phone = (string)($sponsor['contact_no'] ?? '');
                                            $sponsor_frequency = (string)($sponsor['sponsor_frequency'] ?? '');
                                            $staff_id = $sponsor['staff_id'] ?? null;
                                            $active_flag = (int)($sponsor['active'] ?? 0);

                                            // Determine access status based on staff_id and active
                                            $access_status = 'unverified';
                                            $access_status_class = 'default';
                                            $access_status_icon = 'fa-question-circle';
                                            
                                            if ($staff_id !== null) {
                                                $access_status = 'verified';
                                                $access_status_class = 'info';
                                                $access_status_icon = 'fa-check-circle';
                                                
                                                if ($active_flag == 1) {
                                                    $access_status = 'active';
                                                    $access_status_class = 'success';
                                                    $access_status_icon = 'fa-check-circle';
                                                } else {
                                                    $access_status = 'inactive';
                                                    $access_status_class = 'warning';
                                                    $access_status_icon = 'fa-pause-circle';
                                                }
                                            }

                                            // Calculate membership status based on sponsorship dates
                                            $membership_status = 'inactive';
                                            $membership_class = 'default';
                                            
                                            if ($active_flag == 1) {
                                                if(!empty($sponsor['membership_start_date'])) {
                                                    $start_date = strtotime($sponsor['membership_start_date']);
                                                    $current_date = time();
                                                    
                                                    if($start_date <= $current_date) {
                                                        if(empty($sponsor['membership_end_date'])) {
                                                            $membership_status = 'active';
                                                            $membership_class = 'success';
                                                        } else {
                                                            $end_date = strtotime($sponsor['membership_end_date']);
                                                            if($end_date >= $current_date) {
                                                                $membership_status = 'active';
                                                                $membership_class = 'success';
                                                            }
                                                        }
                                                    }
                                                } else {
                                                    // If active flag is 1 but no start date, consider as active
                                                    $membership_status = 'active';
                                                    $membership_class = 'success';
                                                }
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
                                            data-access-status="<?php echo html_escape($access_status); ?>">
                                            
                                            <td><strong><?php echo $sponsor_id; ?></strong></td>
                                            
                                            <!-- Sponsor Name with avatar placeholder -->
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
                                                        <?php if(!empty($sponsor_type)): ?>
                                                            <br><small class="text-muted"><?php echo ucfirst($sponsor_type); ?></small>
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
                                                        <span class="label label-primary">Individual</span>
                                                    <?php elseif($sponsor_type == 'company'): ?>
                                                        <span class="label label-info">Company</span>
                                                    <?php else: ?>
                                                        <span class="label label-default"><?php echo ucfirst($sponsor_type); ?></span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not set</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Email -->
                                            <td>
                                                <?php if(!empty($sponsor_email)): ?>
                                                    <a href="mailto:<?php echo html_escape($sponsor_email); ?>"><?php echo htmlspecialchars($sponsor_email); ?></a>
                                                <?php else: ?>
                                                    <span class="text-muted">Not provided</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Phone -->
                                            <td><?php echo !empty($sponsor_phone) ? htmlspecialchars($sponsor_phone) : '<span class="text-muted">Not provided</span>'; ?></td>

                                            <!-- Access Status -->
                                            <td>
                                                <span class="label label-<?php echo $access_status_class; ?>" title="<?php echo ucfirst($access_status); ?>">
                                                    <i class="fa <?php echo $access_status_icon; ?>"></i> <?php echo ucfirst($access_status); ?>
                                                </span>
                                            </td>

                                            <!-- Frequency -->
                                            <td>
                                                <?php 
                                                if(!empty($sponsor_frequency)) {
                                                    echo ucfirst(str_replace('_', ' ', $sponsor_frequency));
                                                } else {
                                                    echo '<span class="text-muted">Not set</span>';
                                                }
                                                ?>
                                            </td>

                                            <!-- Membership Status -->
                                            <td>
                                                <span class="label label-<?php echo $membership_class; ?>">
                                                    <?php echo ucfirst($membership_status); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
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

<!-- View Sponsor Modal -->
<div class="modal fade" id="viewSponsorModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-eye"></i> Sponsor Details
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
/* Enhanced Sponsors List Styling - Matching Students Lists */

.sponsors-table th { 
    background: #f8f9fa; 
    font-weight: 600; 
    font-size: 12px; 
    border-bottom: 2px solid #dee2e6; 
}

.sponsors-table td { 
    vertical-align: middle; 
    font-size: 13px; 
}

.label { 
    font-size: 10px; 
    padding: 3px 6px; 
}

/* Status-specific styling */
.label-success { background-color: #fbfcfbff; }
.label-warning { background-color: #fafaf9ff; }
.label-info { background-color: #f1f3f3ff; }
.label-default { background-color: #fef7f7ff; }
.label-primary { background-color: #eff4f8ff; }

.row-options { 
    font-size: 11px; 
    color: #777; 
    display: none !important; 
    margin-top: 2px; 
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
    color: #d9534f !important; 
}

.row-options a.text-danger:hover { 
    color: #c9302c !important; 
}

.sponsor-row:hover { 
    background: #f9f9f9; 
}

.sponsor-row:hover .row-options { 
    display: block !important; 
}

.modal-lg { 
    width: 900px; 
}

#sponsor-details-content h5 { 
    color: #337ab7; 
    border-bottom: 1px solid #ddd; 
    padding-bottom: 10px; 
    margin-bottom: 15px; 
}

#sponsor-details-content p { 
    margin-bottom: 8px; 
}

#sponsor-details-content .row { 
    margin-bottom: 15px; 
}

.avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid #ddd;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.avatar__initials {
    font-size: 12px;
    color: #666;
    line-height: 1;
    text-align: center;
    font-weight: 600;
}

.media-left { 
    padding-right: 10px; 
}

.btn-xs { 
    padding: 2px 5px; 
    font-size: 11px; 
}

.dropdown-menu { 
    min-width: 140px; 
}

.text-muted { 
    font-size: 12px; 
}

/* Ensure consistent table styling */
.sponsors-table tbody tr:hover {
    background-color: #f9f9f9 !important;
}

.sponsors-table .media {
    margin: 0;
}

.sponsors-table .media-body {
    vertical-align: middle;
}

/* Filter styling */
.form-group {
    margin-bottom: 15px;
}

.form-group label {
    font-weight: 500;
    color: #333;
    margin-bottom: 5px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .sponsors-table th,
    .sponsors-table td {
        padding: 8px 4px;
        font-size: 11px;
    }
    
    .avatar {
        width: 30px;
        height: 30px;
    }
    
    .avatar__initials {
        font-size: 10px;
    }
    
    .media-left {
        padding-right: 8px;
    }
}
</style>

<script>
// Global variable to store current sponsor being viewed
let currentSponsorId = null;

// Define functions globally first (matching school structure exactly)
function viewSponsor(id) {
    currentSponsorId = id;
    $('#viewSponsorModal').modal('show');
    
    $.ajax({
        url: '<?php echo admin_url("student_sponsor_portal/get_sponsor"); ?>',
        type: 'POST',
        data: { 
            sponsor_id: id,
            action: 'view',
            <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#sponsor-details-content').html(response.html);
                
                // Update CSRF token if provided
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

function editCurrentSponsor() {
    if (currentSponsorId) {
        window.location.href = '<?php echo admin_url("student_sponsor_portal/sponsor_form/"); ?>' + currentSponsorId;
    }
}

function deleteSponsor(id) {
    if (!confirm('Are you sure you want to delete this sponsor? This action cannot be undone.')) return;

    var row = $('#sponsor-row-' + id).css('opacity', '0.5');

    $.ajax({
        url: '<?php echo admin_url("student_sponsor_portal/delete_sponsor"); ?>',
        type: 'POST',
        data: { 
            sponsor_id: id,
            <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                // Remove the row from DataTable
                if ($.fn.DataTable && $.fn.DataTable.isDataTable('#sponsors-table')) {
                    var table = $('#sponsors-table').DataTable();
                    table.row('#sponsor-row-' + id).remove().draw();
                } else {
                    $('#sponsor-row-' + id).remove();
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

function debounce(fn, delay) {
    var t;
    return function() {
        clearTimeout(t);
        var args = arguments, ctx = this;
        t = setTimeout(function(){ fn.apply(ctx, args); }, delay || 300);
    };
}

$(document).ready(function() {
    // Initialize DataTable with proper configuration
    var table = $('#sponsors-table').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[0, "desc"]],
        columnDefs: [
            { orderable: false, targets: [1] },
            { className: "text-center", targets: [0, 5, 6, 7] }
        ],
        language: {
            emptyTable: "No sponsors found",
            zeroRecords: "No matching sponsors found",
            info: "Showing _START_ to _END_ of _TOTAL_ sponsors",
            infoEmpty: "Showing 0 to 0 of 0 sponsors",
            infoFiltered: "(filtered from _MAX_ total sponsors)"
        }
    });
    
    // Row hover effects
    $(document).on('mouseenter', '.sponsor-row', function(){ 
        $(this).find('.row-options').show(); 
    }).on('mouseleave', '.sponsor-row', function(){ 
        $(this).find('.row-options').hide(); 
    });
    
    // Custom search functionality
    $('#search_sponsors').on('keyup', debounce(function() {
        table.search(this.value).draw();
    }, 250));
    
    // Filter functionality using custom filter for access status
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable !== table.table().node()) return true;

        var selectedAccessStatus = $('#filter_status').val();
        if (!selectedAccessStatus) return true;

        var node = table.row(dataIndex).node();
        if (!node) return true;

        var rowAccessStatus = node.getAttribute('data-access-status');
        return rowAccessStatus === selectedAccessStatus;
    });

    // Access status filter
    $('#filter_status').on('change', function() {
        table.draw();
    });
    
    // Type filter
    $('#filter_type').on('change', function() {
        var typeFilter = $(this).val();
        if (typeFilter) {
            table.column(2).search(typeFilter).draw();
        } else {
            table.column(2).search('').draw();
        }
    });
    
    // Frequency filter
    $('#filter_frequency').on('change', function() {
        var frequencyFilter = $(this).val();
        if (frequencyFilter) {
            table.column(6).search(frequencyFilter).draw();
        } else {
            table.column(6).search('').draw();
        }
    });
    
    // Initialize selectpicker
    if ($.fn.selectpicker) {
        $('.selectpicker').selectpicker();
    }
});
</script>

<?php init_tail(); ?>