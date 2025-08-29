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
                <h4 class="customer-profile-group-heading">
                  <i class="fa fa-university"></i>
                  University Students
                </h4>
              </div>
              <div class="col-md-4 text-right">
                <a href="<?php echo admin_url('student_sponsor_portal/export_university_students'); ?>"
                   class="btn btn-success" style="margin-right:10px;">
                  <i class="fa fa-download"></i> Export Students
                </a>
                <a href="<?php echo admin_url('student_sponsor_portal/university_student_form'); ?>"
                   class="btn btn-primary">
                  <i class="fa fa-plus"></i> New Student
                </a>
              </div>
            </div>

            <hr class="hr-panel-heading">

            <!-- Filters -->
            <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                  <label for="filter_year">Year of Study</label>
                  <select id="filter_year" class="form-control selectpicker">
                    <option value="">All Years</option>
                    <option value="1Y1S">1st Year, 1st Semester</option>
                    <option value="1Y2S">1st Year, 2nd Semester</option>
                    <option value="2Y1S">2nd Year, 1st Semester</option>
                    <option value="2Y2S">2nd Year, 2nd Semester</option>
                    <option value="3Y1S">3rd Year, 1st Semester</option>
                    <option value="3Y2S">3rd Year, 2nd Semester</option>
                    <option value="4Y1S">4th Year, 1st Semester</option>
                    <option value="4Y2S">4th Year, 2nd Semester</option>
                    <option value="5Y1S">5th Year, 1st Semester</option>
                    <option value="5Y2S">5th Year, 2nd Semester</option>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label for="filter_program">Program</label>
                  <input type="text" id="filter_program" class="form-control" placeholder="Filter by program">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label for="filter_university">University</label>
                  <input type="text" id="filter_university" class="form-control" placeholder="Filter by university">
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
              <table class="table table-hover students-table" id="university-students-table">
                <thead>
                  <tr>
                    <th width="5%">ID</th>
                    <th width="20%">Student Name</th>
                    <th width="15%">University</th>
                    <th width="15%">Program</th>
                    <th width="12%">Year of Study</th>
                    <th width="15%">Email</th>
                    <th width="12%">Phone</th>
                    <th width="6%">Actions</th>
                  </tr>
                </thead>
                <tbody>
                <?php if (isset($students) && !empty($students)): ?>
                  <?php foreach ($students as $student): ?>
                    <?php
                      $u_id = (int)($student['id'] ?? 0);
                      $uin = $student['university_internal_id'] ?? ('UNI' . str_pad($u_id, 3, '0', STR_PAD_LEFT));
                      $u_name = $student['name'] ?? '';
                      $u_university = $student['university_name'] ?? 'Not specified';
                      $u_program = $student['program_name'] ?? 'Not specified';
                      $u_year_code = $student['university_year_of_study'] ?? '';
                      $year_map = [
                        '1Y1S' => '1st Yr, Sem 1',
                        '1Y2S' => '1st Yr, Sem 2',
                        '2Y1S' => '2nd Yr, Sem 1',
                        '2Y2S' => '2nd Yr, Sem 2',
                        '3Y1S' => '3rd Yr, Sem 1',
                        '3Y2S' => '3rd Yr, Sem 2',
                        '4Y1S' => '4th Yr, Sem 1',
                        '4Y2S' => '4th Yr, Sem 2',
                        '5Y1S' => '5th Yr, Sem 1',
                        '5Y2S' => '5th Yr, Sem 2'
                      ];
                      $u_year_disp = $u_year_code ? ($year_map[$u_year_code] ?? $u_year_code) : '';
                      $u_email = $student['email'] ?? '';
                      $u_phone = $student['contact_no'] ?? '';
                    ?>
                    <tr class="student-row"
                        id="uni-student-row-<?php echo $u_id; ?>"
                        data-year="<?php echo htmlspecialchars($u_year_code); ?>"
                        data-program="<?php echo htmlspecialchars(strtolower($u_program)); ?>"
                        data-university="<?php echo htmlspecialchars(strtolower($u_university)); ?>">
                      <td>
                        <strong><?php echo htmlspecialchars($uin); ?></strong>
                      </td>
                      <td>
                        <div class="media">
                          <?php if (!empty($student['profile_photo'])): ?>
                            <div class="media-left">
                              <img src="<?php echo admin_url('student_sponsor_portal/display_profile_photo/' . $u_id); ?>"
                                   alt="Profile" class="media-object img-circle"
                                   style="width:40px;height:40px;object-fit:cover;">
                            </div>
                          <?php else: ?>
                            <div class="media-left">
                              <div style="width:40px;height:40px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;border:2px solid #ddd;border-radius:50%;font-size:10px;color:#666;">
                                <i class="fa fa-user"></i>
                              </div>
                            </div>
                          <?php endif; ?>
                          <div class="media-body">
                            <strong><?php echo htmlspecialchars($u_name); ?></strong>
                            <div class="row-options" style="display:none;">
                              <a href="<?php echo admin_url('student_sponsor_portal/university_student_form/' . $u_id); ?>">Edit</a> |
                              <a href="#" onclick="deleteStudent(<?php echo $u_id; ?>); return false;" class="text-danger">Delete</a>
                            </div>
                            <?php if (!empty($student['university_internal_id'])): ?>
                              <br><small class="text-muted"><?php echo htmlspecialchars($student['university_internal_id']); ?></small>
                            <?php endif; ?>
                          </div>
                        </div>
                      </td>
                      <td><?php echo htmlspecialchars($u_university); ?></td>
                      <td><?php echo htmlspecialchars($u_program); ?></td>
                      <td>
                        <?php if (!empty($u_year_code)): ?>
                          <span class="label label-info"><?php echo htmlspecialchars($u_year_disp); ?></span>
                          <!-- hidden raw code as fallback for text search -->
                          <span class="dt-year-code" style="display:none;"><?php echo htmlspecialchars($u_year_code); ?></span>
                        <?php else: ?>
                          <span class="text-muted">Not set</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if (!empty($u_email)): ?>
                          <a href="mailto:<?php echo htmlspecialchars($u_email); ?>"><?php echo htmlspecialchars($u_email); ?></a>
                        <?php else: ?>
                          <span class="text-muted">Not provided</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if (!empty($u_phone)): ?>
                          <?php echo htmlspecialchars($u_phone); ?>
                        <?php else: ?>
                          <span class="text-muted">Not provided</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <div class="btn-group">
                          <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-cogs"></i> <span class="caret"></span>
                          </button>
                          <ul class="dropdown-menu dropdown-menu-right">
                            <li>
                              <a href="javascript:void(0)" onclick="viewStudent(<?php echo $u_id; ?>)">
                                <i class="fa fa-eye"></i> View Details
                              </a>
                            </li>
                            <li>
                              <a href="<?php echo admin_url('student_sponsor_portal/university_student_form/' . $u_id); ?>">
                                <i class="fa fa-edit"></i> Edit
                              </a>
                            </li>
                            <li class="divider"></li>
                            <li>
                              <a href="#" onclick="deleteStudent(<?php echo $u_id; ?>); return false;" class="text-danger">
                                <i class="fa fa-trash"></i> Delete
                              </a>
                            </li>
                          </ul>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="8" class="text-center">
                      <div style="padding:40px;">
                        <i class="fa fa-university fa-3x text-muted"></i>
                        <h4 class="text-muted">No students found</h4>
                        <p class="text-muted">Get started by adding your first university student.</p>
                        <a href="<?php echo admin_url('student_sponsor_portal/university_student_form'); ?>"
                           class="btn btn-primary">
                          <i class="fa fa-plus"></i> Add First Student
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div><!-- /.table-responsive -->

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
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title" id="studentViewModalLabel"><i class="fa fa-university"></i> Student Details</h4>
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
.media-object.img-circle { border:2px solid #ddd; }
.btn-xs { padding:2px 5px; font-size:11px; }
.dropdown-menu { min-width:120px; }
.text-muted { font-size:12px; }
</style>

<script>
var currentStudentId = null;

function viewStudent(id) {
  currentStudentId = id;
  $('#studentViewModal').modal('show');

  $.post('<?php echo admin_url("student_sponsor_portal/get_university_student"); ?>', {
    student_id: id,
    action: 'view'
  }, function(resp) {
    if (resp && resp.success) {
      $('#studentViewContent').html(resp.html);
    } else {
      $('#studentViewContent').html('<div class="alert alert-danger">' + (resp.message || 'Error loading student') + '</div>');
    }
  }, 'json').fail(function() {
    $('#studentViewContent').html('<div class="alert alert-danger">Error loading student details</div>');
  });
}

function deleteStudent(id) {
  if (!confirm('Are you sure you want to delete this student? This action cannot be undone.')) return;

  var row = $('#uni-student-row-' + id).css('opacity', '0.5');

  $.post('<?php echo admin_url("student_sponsor_portal/delete_university_student"); ?>', {
    student_id: id
  }, function(response) {
    if (response.success) {
      if ($.fn.DataTable && $.fn.DataTable.isDataTable('#university-students-table')) {
        var t = $('#university-students-table').DataTable();
        t.row('#uni-student-row-' + id).remove().draw();
      } else {
        $('#uni-student-row-' + id).remove();
      }
      alert_float('success', response.message || 'Student deleted successfully');
    } else {
      row.css('opacity', '1');
      alert_float('danger', (response && response.message) || 'Error deleting student');
    }
  }, 'json').fail(function() {
    row.css('opacity', '1');
    alert_float('danger', 'Error deleting student');
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
  // ---------------- DataTable init ----------------
  var table = $('#university-students-table').DataTable({
    responsive: true,
    pageLength: 10,
    order: [[0, "desc"]],
    columnDefs: [{ orderable: false, targets: [7] }],
    language: {
      emptyTable: "No university students found",
      zeroRecords: "No matching students found",
      info: "Showing _START_ to _END_ of _TOTAL_ students",
      infoEmpty: "Showing 0 to 0 of 0 students",
      infoFiltered: "(filtered from _MAX_ total students)"
    }
  });

  // row hover options
  $(document).on('mouseenter', '.student-row', function(){ $(this).find('.row-options').show(); })
             .on('mouseleave', '.student-row', function(){ $(this).find('.row-options').hide(); });

  // ---------------- Custom filter (uses data-*) ----------------
  $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
    if (settings.nTable !== table.table().node()) return true;

    var node = table.row(dataIndex).node();
    if (!node) return true;

    var needYear = ($('#filter_year').val() || '').trim();              // e.g., "3Y1S"
    var needProg = ($('#filter_program').val() || '').toLowerCase().trim();
    var needUni  = ($('#filter_university').val() || '').toLowerCase().trim();

    var rowYear = (node.getAttribute('data-year') || '').trim();
    var rowProg = (node.getAttribute('data-program') || '').toLowerCase().trim();
    var rowUni  = (node.getAttribute('data-university') || '').toLowerCase().trim();

    if (needYear && rowYear !== needYear) return false;
    if (needProg && rowProg.indexOf(needProg) === -1) return false;
    if (needUni  && rowUni.indexOf(needUni)   === -1) return false;

    return true;
  });

  // ---------------- Inputs -> draw() ----------------
  // Global search (built-in)
  $('#search_students').on('keyup', debounce(function(){
    table.search(this.value).draw();
  }, 250));

  // Year select (supports selectpicker + plain select)
  var fireYearFilter = function(){ table.draw(); };
  $('#filter_year').on('change', fireYearFilter);
  $('#filter_year').on('changed.bs.select', fireYearFilter);

  // Program / University (debounced)
  $('#filter_program').on('keyup', debounce(function(){ table.draw(); }, 250));
  $('#filter_university').on('keyup', debounce(function(){ table.draw(); }, 250));

  // Initial draw in case controls have preset values
  table.draw();
});
</script>

<?php init_tail(); ?>
