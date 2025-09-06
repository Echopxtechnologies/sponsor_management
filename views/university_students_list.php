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
                <h4 class="customer-profile-group-heading" style="margin-top:50px;">
                  <i class="fa fa-university"></i> <?php echo isset($title) ? $title : 'University Students'; ?>
                </h4>
              </div>
              <div class="col-md-4 text-right">
                <a href="<?php echo admin_url('student_sponsor_portal/export_university_students'); ?>" class="btn btn-success">
                  <i class="fa fa-download"></i> Export Students
                </a>
                <a href="<?php echo admin_url('student_sponsor_portal/university_student_form'); ?>" class="btn btn-primary">
                  <i class="fa fa-plus"></i> New Student
                </a>
              </div>
            </div>

            <hr class="hr-panel-heading">

            <!-- Filters -->
            <div class="row">
              <div class="col-md-2">
                <div class="form-group">
                  <label for="filter_year">Year of Study</label>
                  <select id="filter_year" class="form-control selectpicker" data-none-selected-text="All Years">
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
              <div class="col-md-2">
                <div class="form-group">
                  <label for="filter_status">Status</label>
                  <select id="filter_status" class="form-control selectpicker" data-none-selected-text="All Status">
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
                  <input type="text" id="search_students" class="form-control" placeholder="Search students (name, email, phone)...">
                </div>
              </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
              <table class="table table-hover students-table" id="university-students-table">
                <thead>
                  <tr>
                    <th width="6%">ID</th>
                    <th width="22%">Student Name</th>
                    <th width="13%">University</th>
                    <th width="13%">Program</th>
                    <th width="12%">Year of Study</th>
                    <th width="10%">Status</th>
                    <th width="14%">Email</th>
                    <th width="10%">Phone</th>
                  </tr>
                </thead>
                <tbody>
                <?php if(!empty($students)): ?>
                  <?php foreach ($students as $s): ?>
                    <?php
                      $sid    = (int)($s['id'] ?? 0);
                      $name   = (string)($s['name'] ?? '');
                      $university = (string)($s['university_name'] ?? '');
                      $program = (string)($s['program_name'] ?? '');
                      $year_code = (string)($s['university_year_of_study'] ?? '');
                      $email  = (string)($s['email'] ?? '');
                      $phone  = (string)($s['contact_no'] ?? '');
                      $staff_id = $s['staff_id'] ?? null;
                      $active = (int)($s['active'] ?? 0);

                      // Determine status based on staff_id and active
                      $status = 'unverified';
                      $status_class = 'default';
                      $status_icon = 'fa-question-circle';
                      
                      if ($staff_id !== null) {
                        $status = 'verified';
                        $status_class = 'info';
                        $status_icon = 'fa-check-circle';
                        
                        if ($active == 1) {
                          $status = 'active';
                          $status_class = 'success';
                          $status_icon = 'fa-check-circle';
                        } else {
                          $status = 'inactive';
                          $status_class = 'warning';
                          $status_icon = 'fa-pause-circle';
                        }
                      }

                      // Year display mapping
                      $year_map = [
                        '1Y1S' => 'Year 1, Sem 1',
                        '1Y2S' => 'Year 1, Sem 2',
                        '2Y1S' => 'Year 2, Sem 1',
                        '2Y2S' => 'Year 2, Sem 2',
                        '3Y1S' => 'Year 3, Sem 1',
                        '3Y2S' => 'Year 3, Sem 2',
                        '4Y1S' => 'Year 4, Sem 1',
                        '4Y2S' => 'Year 4, Sem 2',
                        '5Y1S' => 'Year 5, Sem 1',
                        '5Y2S' => 'Year 5, Sem 2'
                      ];
                      $year_display = $year_code ? ($year_map[$year_code] ?? $year_code) : '';

                      // Make initials fallback
                      $initials = '';
                      foreach (preg_split('/\s+/', trim($name)) as $p) {
                        if ($p !== '' && strlen($initials) < 2) $initials .= strtoupper(substr($p, 0, 1));
                      }

                      $photoUrl = admin_url('student_sponsor_portal/display_profile_photo/' . $sid);
                    ?>
                    <tr class="student-row"
                        id="student-row-<?php echo $sid; ?>"
                        data-year="<?php echo html_escape($year_code); ?>"
                        data-program="<?php echo html_escape(mb_strtolower($program)); ?>"
                        data-university="<?php echo html_escape(mb_strtolower($university)); ?>"
                        data-status="<?php echo html_escape($status); ?>">
                      <td><strong><?php echo $sid; ?></strong></td>

                      <!-- Student Name with circular photo -->
                      <td>
                        <div class="media">
                          <div class="media-left">
                            <div class="avatar" id="avatar-<?php echo $sid; ?>">
                                <span class="avatar__initials" id="initials-<?php echo $sid; ?>">
                                    <?php echo $initials !== '' ? html_escape($initials) : '•'; ?>
                                </span>
                                <img src="<?php echo $photoUrl; ?>" 
                                     alt="<?php echo html_escape($name); ?>" 
                                     class="avatar__image"
                                     id="avatar-img-<?php echo $sid; ?>"
                                     onload="hideInitials(<?php echo $sid; ?>)"
                                     onerror="showInitials(<?php echo $sid; ?>)"
                                     style="display: none;">
                            </div>
                          </div>
                          <div class="media-body">
                            <strong><?php echo html_escape($name); ?></strong>
                            <div class="row-options" style="display:none;">
                              <a href="<?php echo admin_url('student_sponsor_portal/university_student_form/' . $sid); ?>">Edit</a> |
                              <a href="#" onclick="deleteStudent(<?php echo $sid; ?>); return false;" class="text-danger">Delete</a>
                            </div>
                          </div>
                        </div>
                      </td>

                      <td><?php echo $university ? html_escape($university) : '<span class="text-muted">Not specified</span>'; ?></td>
                      <td><?php echo $program ? html_escape($program) : '<span class="text-muted">Not specified</span>'; ?></td>
                      <td>
                        <?php if($year_display !== ''): ?>
                          <span class="label label-info"><?php echo html_escape($year_display); ?></span>
                        <?php else: ?>
                          <span class="text-muted">Not set</span>
                        <?php endif; ?>
                      </td>
                      
                      <!-- Status Column -->
                      <td>
                        <span class="label label-<?php echo $status_class; ?>" title="<?php echo ucfirst($status); ?>">
                          <i class="fa <?php echo $status_icon; ?>"></i> <?php echo ucfirst($status); ?>
                        </span>
                      </td>
                      
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
                    <td colspan="8" class="text-center">
                      <div style="padding:40px;">
                        <i class="fa fa-university fa-3x text-muted"></i>
                        <h4 class="text-muted">No students found</h4>
                        <p class="text-muted">Get started by adding your first university student.</p>
                        <a href="<?php echo admin_url('student_sponsor_portal/university_student_form'); ?>" class="btn btn-primary">
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
/* Main table styling */
.students-table th { 
  background: #f8f9fa; 
  font-weight: 600; 
  font-size: 12px; 
  border-bottom: 2px solid #dee2e6; 
}
.students-table td { 
  vertical-align: middle; 
  font-size: 13px; 
}
.label { 
  font-size: 10px; 
  padding: 3px 6px; 
}

/* Status-specific styling */
.label-success { background-color: #ffffffff; }
.label-warning { background-color: #f1f0efff; }
.label-info { background-color: #ffffffff; }
.label-default { background-color: #777; }

/* Row options styling */
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

/* Row hover effects */
.student-row:hover { 
  background: #f9f9f9; 
}
.student-row:hover .row-options { 
  display: block !important; 
}

/* Avatar styling */
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

.avatar__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  position: absolute;
  top: 0;
  left: 0;
  z-index: 2;
}

.avatar__initials {
  position: absolute;
  font-size: 12px;
  color: #666;
  line-height: 1;
  text-align: center;
  z-index: 1;
  font-weight: 600;
}

.avatar--has-image .avatar__initials {
  display: none;
}

.media-left { 
  padding-right: 10px; 
}
</style>

<script>
// Avatar management functions
function hideInitials(studentId) {
  var avatar = document.getElementById('avatar-' + studentId);
  var initials = document.getElementById('initials-' + studentId);
  var image = document.getElementById('avatar-img-' + studentId);
  
  if (avatar && initials && image) {
    avatar.classList.add('avatar--has-image');
    initials.style.display = 'none';
    image.style.display = 'block';
  }
}

function showInitials(studentId) {
  var avatar = document.getElementById('avatar-' + studentId);
  var initials = document.getElementById('initials-' + studentId);
  var image = document.getElementById('avatar-img-' + studentId);
  
  if (avatar && initials && image) {
    avatar.classList.remove('avatar--has-image');
    initials.style.display = 'block';
    image.style.display = 'none';
  }
}

// Student management functions
function viewStudent(id) {
  $('#studentViewModal').modal('show');

  $.post('<?php echo admin_url("student_sponsor_portal/get_university_student"); ?>', {
    student_id: id,
    action: 'view'
  }, function(resp) {
    if (resp && resp.success) {
      $('#studentViewContent').html(resp.html);
      $('#editStudentBtn').attr('href', '<?php echo admin_url("student_sponsor_portal/university_student_form/"); ?>' + id);
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

  $.post('<?php echo admin_url("student_sponsor_portal/delete_university_student"); ?>', {
    student_id: id
  }, function(response) {
    if (response && response.success) {
      if ($.fn.DataTable && $.fn.DataTable.isDataTable('#university-students-table')) {
        var t = $('#university-students-table').DataTable();
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
  // Initialize avatar states
  $('.avatar__image').each(function() {
    var img = this;
    var studentId = img.id.replace('avatar-img-', '');
    
    if (img.complete && img.naturalHeight !== 0) {
      hideInitials(studentId);
    } else {
      showInitials(studentId);
    }
  });

  // DataTable initialization
  var table = $('#university-students-table').DataTable({
    responsive: true,
    pageLength: 10,
    order: [[0, "desc"]],
    columnDefs: [{ orderable: false, targets: [1] }],
    language: {
      emptyTable: "No university students found",
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

  // Search functionality
  $('#search_students').on('keyup', debounce(function(){
    table.search(this.value).draw();
  }, 250));

  // Custom filter for year/program/university/status using data attributes
  $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
    if (settings.nTable !== table.table().node()) return true;

    var node = table.row(dataIndex).node();
    if (!node) return true;

    var needYear = ($('#filter_year').val() || '').trim();
    var needProg = ($('#filter_program').val() || '').toLowerCase().trim();
    var needUni  = ($('#filter_university').val() || '').toLowerCase().trim();
    var needStatus = ($('#filter_status').val() || '').trim();

    var rowYear = (node.getAttribute('data-year') || '').trim();
    var rowProg = (node.getAttribute('data-program') || '').toLowerCase().trim();
    var rowUni  = (node.getAttribute('data-university') || '').toLowerCase().trim();
    var rowStatus = (node.getAttribute('data-status') || '').trim();

    if (needYear && rowYear !== needYear) return false;
    if (needProg && rowProg.indexOf(needProg) === -1) return false;
    if (needUni  && rowUni.indexOf(needUni)   === -1) return false;
    if (needStatus && rowStatus !== needStatus) return false;

    return true;
  });

  // Filter event handlers
  $('#filter_year').on('change', function(){
    table.draw();
  });

  $('#filter_status').on('change', function(){
    table.draw();
  });

  $('#filter_program').on('keyup', debounce(function(){
    table.draw();
  }, 250));

  $('#filter_university').on('keyup', debounce(function(){
    table.draw();
  }, 250));

  // Initialize selectpicker
  if($.fn.selectpicker){ 
    $('.selectpicker').selectpicker(); 
  }
});
</script>

<?php init_tail(); ?>