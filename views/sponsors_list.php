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
                                        <th width="15%">Frequency</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($sponsors)): ?>
                                        <?php foreach($sponsors as $index => $sponsor): ?>
                                        <tr class="sponsor-row">
                                            <td><?php echo $sponsor['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($sponsor['name'] ?? ''); ?></strong>
                                                <div class="row-options" style="display: none;">
                                                    <a href="<?php echo admin_url('student_sponsor_portal/sponsor_form/' . $sponsor['id']); ?>">View</a>
                                                    |
                                                    <a href="#" onclick="deleteSponsor(<?php echo $sponsor['id']; ?>); return false;" class="text-danger">Delete</a>
                                                </div>
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
                                                // Calculate status based on sponsorship dates
                                                $status = 'inactive';
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
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">
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
                <button type="button" class="btn btn-primary" onclick="editCurrentSponsor()">
                    <i class="fa fa-edit"></i> Edit
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function deleteSponsor(id) {
    if (confirm('Are you sure you want to delete this sponsor?')) {
        $.ajax({
            url: '<?php echo admin_url("student_sponsor_portal/delete_sponsor"); ?>',
            type: 'POST',
            data: { sponsor_id: id },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert_float('success', 'Sponsor deleted successfully');
                    location.reload();
                } else {
                    alert_float('danger', response.message || 'Error deleting sponsor');
                }
            },
            error: function() {
                alert_float('danger', 'Error deleting sponsor');
            }
        });
    }
}

$(document).ready(function() {
    // Initialize DataTable
    $('#sponsors-table').DataTable({
        "pageLength": 10,
        "searching": false, // We'll use custom search
        "ordering": true,
        "info": true,
        "responsive": true
    });
    
    // Handle row hover using event delegation (works with DataTables)
    $(document).on('mouseenter', '.sponsor-row', function() {
        $(this).find('.row-options').show();
    }).on('mouseleave', '.sponsor-row', function() {
        $(this).find('.row-options').hide();
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

.row-options {
    font-size: 12px;
    color: #777;
    display: none !important; /* Force hide initially */
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
    background-color: #f9f9f9;
}

.sponsor-row:hover .row-options {
    display: block !important; /* Force show on hover */
}
</style>

<?php init_tail(); ?>