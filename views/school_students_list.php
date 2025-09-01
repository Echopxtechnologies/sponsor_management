<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">

            <!-- Header -->
            <div class="row">
              <div class="col-md-8">
                <h4 class="customer-profile-group-heading">
                  <i class="fa fa-graduation-cap"></i> <?php echo isset($title) ? $title : 'School Students'; ?>
                </h4>
              </div>
              <div class="col-md-4 text-right">
                <a href="<?php echo admin_url('student_sponsor_portal/export_school_students'); ?>" class="btn btn-success">
                  <i class="fa fa-download"></i> Export Students
                </a>
                <a href="<?php echo admin_url('student_sponsor_portal/school_student_form'); ?>" class="btn btn-primary">
                  <i class="fa fa-plus"></i> New Student
                </a>
              </div>
            </div>

            <hr class="hr-panel-heading">

            <!-- Filters -->
            <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                  <label for="filter_grade">Grade</label>
                  <select id="filter_grade" class="form-control selectpicker" data-none-selected-text="All Grades">
                    <option value="">All Grades</option>
                    <?php for($i=1;$i<=13;$i++): ?>
                      <option value="<?php echo $i; ?>">Grade <?php echo $i; ?></option>
                    <?php endfor; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label for="filter_school">School</label>
                  <input type="text" id="filter_school" class="form-control" placeholder="Filter by school">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="search_students">Search</label>
                  <input type="text" id="search_students" class="form-control" placeholder="Search students (name, email, phone)...">
                </div>
              </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
              <table class="table table-hover students-table" id="school-students-table">
                <thead>
                  <tr>
                    <th width="8%">ID</th>
                    <th width="30%">Student Name</th>
                    <th width="12%">Grade</th>
                    <th width="25%">School</th>
                    <th width="15%">Email</th>
                    <th width="10%">Phone</th>
                  </tr>
                </thead>
                <tbody>
                <?php if(!empty($school_students)): ?>
                  <?php foreach ($school_students as $s): ?>
                    <?php
                      $sid    = (int)($s['id'] ?? 0);
                      $name   = (string)($s['name'] ?? '');
                      $grade  = (string)($s['school_grade'] ?? '');
                      $school = (string)($s['school_name'] ?? '');
                      $email  = (string)($s['email'] ?? '');
                      $phone  = (string)($s['contact_no'] ?? '');

                      // Make initials fallback
                      $initials = '';
                      foreach (preg_split('/\s+/', trim($name)) as $p) {
                        if ($p !== '' && strlen($initials) < 2) $initials .= strtoupper(substr($p, 0, 1));
                      }

                      // IMPORTANT: use the controller method that exists
                      $photoUrl = admin_url('student_sponsor_portal/display_school_photo/' . $sid);
                    ?>
                    <tr class="student-row"
                        id="student-row-<?php echo $sid; ?>"
                        data-grade="<?php echo html_escape($grade); ?>"
                        data-school="<?php echo html_escape(mb_strtolower($school)); ?>">
                      <td><strong><?php echo $sid; ?></strong></td>

                      <!-- Student Name with circular photo -->
                      <td>
                        <div class="media">
                          <div class="media-left">
                            <div class="avatar">
                                <!-- Initials fallback -->
                                <span class="avatar__initials">
                                    <?php echo $initials !== '' ? html_escape($initials) : '•'; ?>
                                </span>
                                <!-- Image tag -->
                                <img src="<?php echo $photoUrl; ?>" 
                                    alt="Profile" 
                                    onerror="this.style.display='none'">
                            </div>
                          </div>
                          <div class="media-body">
                            <strong><?php echo html_escape($name); ?></strong>
                            <div class="row-options" style="display:none;">
                              <a href="javascript:void(0)" onclick="viewStudent(<?php echo $sid; ?>)">View</a> |
                              <a href="<?php echo admin_url('student_sponsor_portal/school_student_form/' . $sid); ?>">Edit</a> |
                              <a href="#" onclick="deleteStudent(<?php echo $sid; ?>); return false;" class="text-danger">Delete</a>
                            </div>
                          </div>
                        </div>
                      </td>

                      <td>
                        <?php if($grade !== ''): ?>
                          <span class="label label-info">Grade <?php echo html_escape($grade); ?></span>
                        <?php else: ?>
                          <span class="text-muted">Not set</span>
                        <?php endif; ?>
                      </td>
                      <td><?php echo $school ? html_escape($school) : '<span class="text-muted">Not specified</span>'; ?></td>
                      <td>
                        <?php if($email): ?>
                          <a href="mailto:<?php echo html_escape($email); ?>"><?php echo html_escape($email); ?></a>
                        <?php else: ?>
                          <span class="text-muted">Not provided</span>
                        <?php endif; ?>
                      </td>
                      <td><?php echo $phone ? html_escape($phone) : '<span class="text-muted">Not provided</span>'; ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" class="text-center">
                      <div style="padding:40px;">
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

<!-- View Modal -->
<div class="modal fade" id="studentViewModal" tabindex="-1" role="dialog" aria-labelledby="studentViewModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title" id="studentViewModalLabel"><i class="fa fa-graduation-cap"></i> Student Details</h4>
      </div>
      <div class="modal-body" id="studentViewContent">
        <div class="text-center">
          <i class="fa fa-spinner fa-spin fa-2x"></i>
          <p>Loading student details...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <a id="editStudentBtn" class="btn btn-primary"><i class="fa fa-edit"></i> Edit Student</a>
      </div>
    </div>
  </div>
</div>

<style>
.students-table th { background:#f8f9fa; font-weight:600; font-size:12px; border-bottom:2px solid #dee2e6; }
.students-table td { vertical-align:middle; font-size:13px; }
.label { font-size:10px; padding:3px 6px; }
.row-options { font-size:11px; color:#777; display:none !important; margin-top:2px; }
.row-options a { color:#777; text-decoration:none; }
.row-options a:hover { color:#333; text-decoration:none; }
.row-options a.text-danger { color:#d9534f !important; }
.row-options a.text-danger:hover { color:#c9302c !important; }
.student-row:hover { background:#f9f9f9; }
.student-row:hover .row-options { display:block !important; }
.modal-lg { width:900px; }
#studentViewContent h5 { color:#337ab7; border-bottom:1px solid #ddd; padding-bottom:10px; margin-bottom:15px; }
#studentViewContent p { margin-bottom:8px; }
#studentViewContent .row { margin-bottom:15px; }
.avatar{
  width:40px;height:40px;
  border-radius:50%;
  overflow:hidden;
  border:2px solid #ddd;
  background:#f0f0f0;
  display:flex;align-items:center;justify-content:center;
  position:relative;
}
.avatar img{
  width:100%;height:100%;
  object-fit:cover;display:block;
}
.avatar__initials{
  position:absolute;
  font-size:12px;color:#666;line-height:1;text-align:center;
}
.media-left{ padding-right:10px; }

.btn-xs { padding:2px 5px; font-size:11px; }
.dropdown-menu { min-width:140px; }
.text-muted { font-size:12px; }
</style>

<script>
// Define functions globally first (matching university structure exactly)
function viewStudent(id) {
  $('#studentViewModal').modal('show');

  $.post('<?php echo admin_url("student_sponsor_portal/get_school_student"); ?>', {
    student_id: id,
    action: 'view'
  }, function(resp) {
    if (resp && resp.success) {
      $('#studentViewContent').html(resp.html);
      $('#editStudentBtn').attr('href', '<?php echo admin_url("student_sponsor_portal/school_student_form/"); ?>' + id);
    } else {
      $('#studentViewContent').html('<div class="alert alert-danger">' + (resp.message || 'Error loading student') + '</div>');
    }
  }, 'json').fail(function() {
    $('#studentViewContent').html('<div class="alert alert-danger">Error loading student details</div>');
  });
}

function deleteStudent(id) {
  if (!confirm('Are you sure you want to delete this student? This action cannot be undone.')) return;

  var row = $('#student-row-' + id).css('opacity', '0.5');

  $.post('<?php echo admin_url("student_sponsor_portal/delete_school_student"); ?>', {
    student_id: id
  }, function(response) {
    if (response && response.success) {
      if ($.fn.DataTable && $.fn.DataTable.isDataTable('#school-students-table')) {
        var t = $('#school-students-table').DataTable();
        t.row('#student-row-' + id).remove().draw();
      } else {
        $('#student-row-' + id).remove();
      }
      if (typeof alert_float === 'function') {
        alert_float('success', response.message || 'Student deleted successfully');
      } else {
        alert('Student deleted successfully');
      }
    } else {
      row.css('opacity', '1');
      var msg = (response && response.message) || 'Error deleting student';
      if (typeof alert_float === 'function') {
        alert_float('danger', msg);
      } else {
        alert('Error: ' + msg);
      }
    }
  }, 'json').fail(function() {
    row.css('opacity', '1');
    if (typeof alert_float === 'function') {
      alert_float('danger', 'Error deleting student');
    } else {
      alert('Error deleting student');
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
  // DataTable init
  var table = $('#school-students-table').DataTable({
    responsive: true,
    pageLength: 10,
    order: [[0, "desc"]],
    columnDefs: [{ orderable: false, targets: [1] }],
    language: {
      emptyTable: "No school students found",
      zeroRecords: "No matching students found",
      info: "Showing _START_ to _END_ of _TOTAL_ students",
      infoEmpty: "Showing 0 to 0 of 0 students",
      infoFiltered: "(filtered from _MAX_ total students)"
    }
  });

  // Row hover effects
  $(document).on('mouseenter', '.student-row', function(){ 
    $(this).find('.row-options').show(); 
  }).on('mouseleave', '.student-row', function(){ 
    $(this).find('.row-options').hide(); 
  });

  // Search and filter functionality
  $('#search_students').on('keyup', debounce(function(){
    table.search(this.value).draw();
  }, 250));

  $('#filter_grade').on('change', function(){
    var g = $(this).val();
    if(g){ 
      table.column(2).search('^\\s*Grade\\s*'+g+'\\b', true, false).draw(); 
    } else { 
      table.column(2).search('').draw(); 
    }
  });

  $('#filter_school').on('keyup', debounce(function(){
    table.column(3).search(this.value).draw();
  }, 250));

  // Initialize selectpicker if available
  if($.fn.selectpicker){ 
    $('.selectpicker').selectpicker(); 
  }
});
</script>

<?php init_tail(); ?>