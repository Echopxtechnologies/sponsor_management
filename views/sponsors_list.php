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
                                <h4 class="customer-profile-group-heading">
                                    <i class="fa fa-users"></i> Sponsor Management
                                </h4>
                            </div>
                            <div class="col-md-4 text-right">
                                <a href="<?php echo admin_url('student_sponsor_portal/sponsor_form'); ?>" class="btn btn-primary">
                                    <i class="fa fa-plus"></i> Add New Sponsor
                                </a>
                            </div>
                        </div>
                        <hr class="hr-panel-heading">

                        <!-- Filters and Search -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="filter_status">Status Filter</label>
                                    <select id="filter_status" class="form-control selectpicker">
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="filter_type">Sponsor Type</label>
                                    <select id="filter_type" class="form-control selectpicker">
                                        <option value="">All Types</option>
                                        <option value="individual">Individual</option>
                                        <option value="company">Company</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="filter_frequency">Frequency</label>
                                    <select id="filter_frequency" class="form-control selectpicker">
                                        <option value="">All Frequencies</option>
                                        <option value="one_time">One-time</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="half_yearly">Half-yearly</option>
                                        <option value="yearly">Yearly</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="search_sponsors">Search</label>
                                    <input type="text" id="search_sponsors" class="form-control" placeholder="Search sponsors...">
                                </div>
                            </div>
                        </div>

                        <!-- Sponsors Table -->
                        <div class="table-responsive">
                            <table class="table table-hover sponsors-table" id="sponsors-table">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="25%">Sponsor Name</th>
                                        <th width="12%">Type</th>
                                        <th width="18%">Email</th>
                                        <th width="15%">Phone</th>
                                        <th width="10%">Status</th>
                                        <th width="10%">Frequency</th>
                                        <th width="5%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($sponsors)): ?>
                                        <?php foreach($sponsors as $index => $sponsor): ?>
                                        <tr class="sponsor-row" data-sponsor-id="<?php echo $sponsor['id']; ?>">
                                            <td><?php echo $sponsor['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($sponsor['name'] ?? ''); ?></strong>
                                                <?php if(!empty($sponsor['sponsor_type'])): ?>
                                                    <br><small class="text-muted"><?php echo ucfirst($sponsor['sponsor_type']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if(!empty($sponsor['sponsor_type'])): ?>
                                                    <?php if($sponsor['sponsor_type'] == 'individual'): ?>
                                                        <span class="label label-primary">Individual</span>
                                                    <?php elseif($sponsor['sponsor_type'] == 'company'): ?>
                                                        <span class="label label-info">Company</span>
                                                    <?php else: ?>
                                                        <span class="label label-default"><?php echo ucfirst($sponsor['sponsor_type']); ?></span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="label label-default">Not Set</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($sponsor['email'] ?? 'Not provided'); ?></td>
                                            <td><?php echo htmlspecialchars($sponsor['phone'] ?? 'Not provided'); ?></td>
                                            <td>
                                                <?php 
                                                // Calculate status based on sponsorship dates and active flag
                                                $status = 'inactive';
                                                $active_flag = isset($sponsor['active']) ? (int)$sponsor['active'] : 0;
                                                
                                                if ($active_flag == 1) {
                                                    if(!empty($sponsor['membership_start_date'])) {
                                                        $start_date = strtotime($sponsor['membership_start_date']);
                                                        $current_date = time();
                                                        
                                                        if($start_date <= $current_date) {
                                                            if(empty($sponsor['membership_end_date'])) {
                                                                $status = 'active';
                                                            } else {
                                                                $end_date = strtotime($sponsor['membership_end_date']);
                                                                if($end_date >= $current_date) {
                                                                    $status = 'active';
                                                                }
                                                            }
                                                        }
                                                    } else {
                                                        // If active flag is 1 but no start date, consider as active
                                                        $status = 'active';
                                                    }
                                                }
                                                ?>
                                                <span class="label label-<?php echo $status == 'active' ? 'success' : 'default'; ?>">
                                                    <?php echo ucfirst($status); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                if(!empty($sponsor['sponsor_frequency'])) {
                                                    echo ucfirst(str_replace('_', ' ', $sponsor['sponsor_frequency']));
                                                } else {
                                                    echo 'Not set';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-default btn-icon dropdown-toggle" type="button" data-toggle="dropdown">
                                                        <i class="fa fa-ellipsis-h"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-right">
                                                        <li>
                                                            <a href="#" onclick="viewSponsor(<?php echo $sponsor['id']; ?>); return false;">
                                                                <i class="fa fa-eye text-info"></i> View Details
                                                            </a>
                                                        </li>
                                                        <?php if(has_permission('student_sponsor_portal', '', 'edit')): ?>
                                                        <li>
                                                            <a href="<?php echo admin_url('student_sponsor_portal/sponsor_form/' . $sponsor['id']); ?>">
                                                                <i class="fa fa-edit text-primary"></i> Edit Sponsor
                                                            </a>
                                                        </li>
                                                        <?php endif; ?>
                                                        <?php if(has_permission('student_sponsor_portal', '', 'delete')): ?>
                                                        <li class="divider"></li>
                                                        <li>
                                                            <a href="#" onclick="deleteSponsor(<?php echo $sponsor['id']; ?>); return false;" class="text-danger">
                                                                <i class="fa fa-trash text-danger"></i> Delete
                                                            </a>
                                                        </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
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
                <!-- Sponsor details will be loaded here via AJAX -->
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

<script>
// Global variable to store current sponsor being viewed
let currentSponsorId = null;

function viewSponsor(id) {
    currentSponsorId = id;
    
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
                $('#viewSponsorModal').modal('show');
                
                // Update CSRF token if provided
                if (typeof csrfData !== 'undefined' && response[csrfData.token_name]) {
                    csrfData.hash = response[csrfData.token_name];
                }
            } else {
                alert_float('danger', response.message || 'Error loading sponsor details');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', xhr.responseText);
            alert_float('danger', 'Error loading sponsor details');
        }
    });
}

function editCurrentSponsor() {
    if (currentSponsorId) {
        window.location.href = '<?php echo admin_url("student_sponsor_portal/sponsor_form/"); ?>' + currentSponsorId;
    }
}

function deleteSponsor(id) {
    if (confirm('Are you sure you want to delete this sponsor? This action cannot be undone.')) {
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
                    alert_float('success', 'Sponsor deleted successfully');
                    
                    // Remove the row from DataTable
                    var table = $('#sponsors-table').DataTable();
                    var $row = $('[data-sponsor-id="' + id + '"]');
                    table.row($row).remove().draw();
                    
                    // Update CSRF token
                    if (typeof csrfData !== 'undefined' && response[csrfData.token_name]) {
                        csrfData.hash = response[csrfData.token_name];
                    }
                } else {
                    alert_float('danger', response.message || 'Error deleting sponsor');
                }
            },
            error: function(xhr, status, error) {
                console.error('Delete Error:', xhr.responseText);
                alert_float('danger', 'Error deleting sponsor');
            }
        });
    }
}

$(document).ready(function() {
    // Initialize DataTable with proper configuration
    $('#sponsors-table').DataTable({
        "pageLength": 10,
        "searching": false, // We'll use custom search
        "ordering": true,
        "info": true,
        "responsive": true,
        "columnDefs": [
            { "orderable": false, "targets": [7] }, // Disable sorting on Actions column
            { "className": "text-center", "targets": [0, 6, 7] }
        ]
    });
    
    // Custom search functionality
    $('#search_sponsors').on('keyup', function() {
        $('#sponsors-table').DataTable().search(this.value).draw();
    });
    
    // Filter functionality
    $('#filter_status, #filter_type, #filter_frequency').on('change', function() {
        var table = $('#sponsors-table').DataTable();
        
        // Get filter values
        var statusFilter = $('#filter_status').val();
        var typeFilter = $('#filter_type').val();
        var frequencyFilter = $('#filter_frequency').val();
        
        // Apply filters
        table.columns().search('').draw();
        
        if (statusFilter) {
            table.column(5).search(statusFilter).draw();
        }
        if (typeFilter) {
            table.column(2).search(typeFilter).draw();
        }
        if (frequencyFilter) {
            table.column(6).search(frequencyFilter).draw();
        }
    });
    
    // Initialize selectpicker
    if ($.fn.selectpicker) {
        $('.selectpicker').selectpicker();
    }
});
</script>

<style>
.sponsors-table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.sponsors-table td {
    vertical-align: middle;
}

.label {
    font-size: 11px;
    padding: 4px 8px;
}

.dropdown-menu {
    min-width: 160px;
}

.dropdown-menu > li > a {
    padding: 8px 16px;
}

.dropdown-menu > li > a i {
    margin-right: 8px;
    width: 14px;
}

.btn-icon {
    width: 30px;
    height: 30px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sponsor-row:hover {
    background-color: #f9f9f9;
}

/* Responsive table adjustments */
@media (max-width: 768px) {
    .table-responsive {
        border: none;
    }
    
    .sponsors-table td {
        padding: 8px 4px;
        font-size: 12px;
    }
    
    .sponsors-table th {
        padding: 8px 4px;
        font-size: 11px;
    }
}
</style>

<?php init_tail(); ?>