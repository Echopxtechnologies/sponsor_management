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
                                <a href="<?php echo admin_url('student_sponsor_portal/export_students'); ?>" class="btn btn-success" style="margin-right: 10px;">
                                    <i class="fa fa-download"></i> Export Students
                                </a>
                                <a href="<?php echo admin_url('student_sponsor_portal/school_student_form'); ?>" class="btn btn-primary">
                                    <i class="fa fa-plus"></i> New Student
                                </a>
                            </div>
                        </div>
                        <hr class="hr-panel-heading">

                        <!-- Filters and Search -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="filter_grade">Grade Filter</label>
                                    <select id="filter_grade" class="form-control selectpicker">
                                        <option value="">All Grades</option>
                                        <?php for($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?php echo $i; ?>">Grade <?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="filter_district">District</label>
                                    <select id="filter_district" class="form-control selectpicker">
                                        <option value="">All Districts</option>
                                        <?php if(isset($districts)): ?>
                                            <?php foreach($districts as $district): ?>
                                                <option value="<?php echo htmlspecialchars($district); ?>"><?php echo htmlspecialchars($district); ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="filter_school">School</label>
                                    <input type="text" id="filter_school" class="form-control" placeholder="Filter by school">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="search_students">Search</label>
                                    <input type="text" id="search_students" class="form-control" placeholder="Search students...">
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover students-table" id="school-students-table">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="25%">Student Name</th>
                                        <th width="10%">Grade</th>
                                        <th width="20%">School</th>
                                        <th width="15%">Email</th>
                                        <th width="12%">Phone</th>
                                        <th width="13%">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($school_students)): ?>
                                        <?php foreach ($school_students as $index => $student): ?>
                                        <tr class="student-row" id="student-row-<?php echo $student['id']; ?>">
                                            <td><?php echo $student['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($student['name']); ?></strong>
                                                <div class="row-options" style="display: none;">
                                                    <a href="<?php echo admin_url('student_sponsor_portal/school_student_form/' . $student['id']); ?>">View</a>
                                                    |
                                                    <a href="#" onclick="deleteStudent(<?php echo (int)$student['id']; ?>); return false;" class="text-danger">Delete</a>
                                                </div>
                                                <?php if(!empty($student['district'])): ?>
                                                    <br><small class="text-muted">District: <?php echo htmlspecialchars($student['district']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="label label-info">Grade <?php echo htmlspecialchars($student['grade']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($student['school_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['email'] ?? 'Not provided'); ?></td>
                                            <td><?php echo htmlspecialchars($student['phone'] ?? 'Not provided'); ?></td>
                                            <td>
                                                <?php 
                                                // Calculate status (you can customize this logic based on your needs)
                                                $status = 'active'; // Default status
                                                ?>
                                                <span class="label label-<?php echo $status == 'active' ? 'success' : 'default'; ?>">
                                                    <?php echo ucfirst($status); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">
                                                <div style="padding: 40px;">
                                                    <i class="fa fa-graduation-cap fa-3x text-muted"></i>
                                                    <h4 class="text-muted">No students found</h4>
                                                    <p class="text-muted">Get started by adding your first student.</p>
                                                    <a href="<?php echo admin_url('student_sponsor_portal/school_student_form'); ?>" class="btn btn-primary">
                                                        <i class="fa fa-plus"></i> Add First Student
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

<!-- Student View Modal -->
<div class="modal fade" id="studentViewModal" tabindex="-1" role="dialog" aria-labelledby="studentViewModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="studentViewModalLabel">
                    <i class="fa fa-graduation-cap"></i> Student Details
                </h4>
            </div>
            <div class="modal-body" id="studentViewContent">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p>Loading student details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editStudentBtn">
                    <i class="fa fa-edit"></i> Edit Student
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Global variable to store current student ID
var currentStudentId = null;

function viewStudent(studentId) {
    currentStudentId = studentId;
    $('#studentViewModal').modal('show');
    
    $.ajax({
        url: '<?php echo admin_url("student_sponsor_portal/get_school_student"); ?>',
        type: 'POST',
        data: { 
            student_id: studentId,
            action: 'view'
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#studentViewContent').html(response.html);
            } else {
                $('#studentViewContent').html('<div class="alert alert-danger">' + response.message + '</div>');
            }
        },
        error: function() {
            $('#studentViewContent').html('<div class="alert alert-danger">Error loading student details</div>');
        }
    });
}

function deleteStudent(studentId) {
    if (!studentId) {
        alert_float('danger', 'Invalid student ID');
        return;
    }

    if (!confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
        return;
    }

    // Show loading indicator
    var row = $('#student-row-' + studentId);
    row.css('opacity', '0.5');
    
    // Prepare data with CSRF token if available
    var postData = { student_id: studentId };
    
    // Add CSRF token if available in the page
    if (typeof csrfData !== 'undefined') {
        for (var key in csrfData) {
            if (csrfData.hasOwnProperty(key)) {
                postData[key] = csrfData[key];
            }
        }
    }
    
    console.log('Sending delete request for student ID:', studentId);
    console.log('Post data:', postData);
    
    $.ajax({
        url: '<?php echo admin_url("student_sponsor_portal/delete_school_student"); ?>',
        type: 'POST',
        data: postData,
        dataType: 'json',
        success: function(response) {
            console.log('Delete response:', response);
            
            if(response && response.success) {
                // Remove the row from the table
                if ($.fn.DataTable && $.fn.DataTable.isDataTable('#school-students-table')) {
                    var table = $('#school-students-table').DataTable();
                    table.row('#student-row-' + studentId).remove().draw();
                } else {
                    $('#student-row-' + studentId).remove();
                }
                
                alert_float('success', response.message || 'Student deleted successfully');
            } else {
                row.css('opacity', '1');
                alert_float('danger', response.message || 'Error deleting student');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            console.error('XHR response:', xhr.responseText);
            
            row.css('opacity', '1');
            
            // Try to parse the response for more details
            try {
                var response = JSON.parse(xhr.responseText);
                if (response && response.message) {
                    alert_float('danger', 'Error: ' + response.message);
                } else {
                    alert_float('danger', 'Error deleting student. Please check console for details.');
                }
            } catch (e) {
                alert_float('danger', 'Error deleting student. Status: ' + status);
            }
        }
    });
}

$(document).ready(function() {
    // Initialize DataTable
    $('#school-students-table').DataTable({
        "pageLength": 10,
        "searching": false, // We'll use custom search
        "ordering": true,
        "info": true,
        "responsive": true,
        "order": [[ 0, "desc" ]],
        "columnDefs": [
            { "orderable": false, "targets": 6 } // Disable sorting on Status column
        ]
    });
    
    // Handle row hover using event delegation (works with DataTables)
    $(document).on('mouseenter', '.student-row', function() {
        $(this).find('.row-options').show();
    }).on('mouseleave', '.student-row', function() {
        $(this).find('.row-options').hide();
    });
    
    // Custom search functionality
    $('#search_students').on('keyup', function() {
        $('#school-students-table').DataTable().search(this.value).draw();
    });
    
    // Filter functionality
    $('#filter_grade, #filter_district').on('change', function() {
        var table = $('#school-students-table').DataTable();
        
        // Get filter values
        var gradeFilter = $('#filter_grade').val();
        var districtFilter = $('#filter_district').val();
        
        // Apply filters
        table.columns().search('').draw();
        
        if (gradeFilter) {
            table.column(2).search('Grade ' + gradeFilter).draw();
        }
        if (districtFilter) {
            table.column(1).search(districtFilter).draw();
        }
    });
    
    // School filter
    $('#filter_school').on('keyup', function() {
        var table = $('#school-students-table').DataTable();
        table.column(3).search(this.value).draw();
    });
    
    // Initialize selectpicker
    if ($.fn.selectpicker) {
        $('.selectpicker').selectpicker();
    }
    
    // Edit button in modal
    $('#editStudentBtn').on('click', function() {
        if (currentStudentId) {
            window.location.href = '<?php echo admin_url("student_sponsor_portal/school_student_form/"); ?>' + currentStudentId;
        }
    });
    
    // Reset modal content when hidden
    $('#studentViewModal').on('hidden.bs.modal', function () {
        $('#studentViewContent').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Loading student details...</p></div>');
        currentStudentId = null;
    });
});
</script>

<style>
.students-table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.students-table td {
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

.student-row:hover {
    background-color: #f9f9f9;
}

.student-row:hover .row-options {
    display: block !important; /* Force show on hover */
}

.modal-lg {
    width: 900px;
}

#studentViewContent h5 {
    color: #337ab7;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

#studentViewContent p {
    margin-bottom: 8px;
}

#studentViewContent .row {
    margin-bottom: 15px;
}
</style>

<?php init_tail(); ?>