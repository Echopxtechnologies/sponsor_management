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
              <div class="col-md-6">
                <h4 class="customer-profile-group-heading" style="margin-top:20px;">
                  <i class="fa fa-university"></i> <?php echo isset($title) ? $title : 'University Students'; ?>
                </h4>
              </div>
              <div class="col-md-6">
                <div class="text-right" style="margin-top:15px;">
                  <div class="btn-group" role="group" style="margin-right: 5px;">
                    <a href="<?php echo admin_url('student_sponsor_portal/bulk_import_university_students'); ?>" 
                       class="btn btn-warning btn-sm" title="Bulk Import Students">
                      <i class="fa fa-upload"></i> Import
                    </a>
                  </div>
                  <div class="btn-group" role="group" style="margin-right: 5px;">
                    <button type="button" class="btn btn-success btn-sm dropdown-toggle" 
                            data-toggle="dropdown" title="Export Options">
                      <i class="fa fa-download"></i> Export <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right">
                      <li><a href="<?php echo admin_url('student_sponsor_portal/export_university_students'); ?>">
                        <i class="fa fa-file-excel-o"></i> Export All Students (Excel)</a></li>
                      <li><a href="#" onclick="exportFilteredStudents(); return false;">
                        <i class="fa fa-filter"></i> Export Filtered Results (Excel)</a></li>
                      <li class="divider"></li>
                      <li><a href="<?php echo admin_url('student_sponsor_portal/download_university_students_template'); ?>">
                        <i class="fa fa-download"></i> Download Excel Template</a></li>
                    </ul>
                  </div>
                  <div class="btn-group" role="group">
                    <a href="<?php echo admin_url('student_sponsor_portal/university_student_form'); ?>" 
                       class="btn btn-primary btn-sm">
                      <i class="fa fa-plus"></i> New Student
                    </a>
                  </div>
                </div>
              </div>
            </div>

            <hr class="hr-panel-heading">

            <!-- Table -->
            <div class="table-responsive">
              <table class="table table-hover students-table" id="university-students-table">
                <thead>
                  <tr>
                    <th width="5%">#</th>
                    <th width="20%">Student Name</th>
                    <th width="15%">University</th>
                    <th width="15%">Program</th>
                    <th width="12%">Year of Study</th>
                    <th width="10%">Status</th>
                    <th width="13%">Sponsor</th>
                    <th width="15%">Contact</th>
                  </tr>
                </thead>
                <tbody>
                <?php if(!empty($students)): ?>
                  <?php 
                    $serial = 1; 
                    foreach ($students as $s): 
                  ?>
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
                        $sponsor_name = (string)($s['sponsor_name'] ?? '');
  $sponsor_type = (string)($s['sponsor_type'] ?? '');
  $sponsor_relationship = $s['sponsor_relationship_type'] ?? 'direct';
  $all_sponsor_names = (string)($s['all_sponsor_names'] ?? '');
  $all_sponsor_types = (string)($s['all_sponsor_types'] ?? '');
  $sponsor_count = (int)($s['sponsor_count'] ?? 0);
                      
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
                        data-student-id="<?php echo $sid; ?>"
                        data-year="<?php echo html_escape($year_code); ?>"
                        data-program="<?php echo html_escape(mb_strtolower($program)); ?>"
                        data-university="<?php echo html_escape(mb_strtolower($university)); ?>"
                        data-status="<?php echo html_escape($status); ?>">
                      
                      <td><strong><?php echo $serial; ?></strong></td>

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
                            <br><small class="text-muted">ID: <?php echo $sid; ?></small>
                            <div class="row-options" style="display:none;">
                              <a href="<?php echo admin_url('student_sponsor_portal/university_student_form/' . $sid); ?>">Edit</a> |
                              <a href="#" onclick="viewStudent(<?php echo $sid; ?>); return false;">View</a> |
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
                      
                      <!-- Sponsor Column -->
<td>
  <?php if($all_sponsor_names): ?>
    <div class="sponsor-info">
      <strong class="text-success">
        <i class="fa fa-heart"></i> <?php echo html_escape($all_sponsor_names); ?>
      </strong>
     
      <?php if($sponsor_count > 1): ?>
        <br><small class="text-info">
          <i class="fa fa-users"></i> <?php echo $sponsor_count; ?> Sponsors
        </small>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <span class="text-muted">
      <i class="fa fa-heart-o"></i> No Sponsor
    </span>
  <?php endif; ?>
</td>
                      <!-- Contact Column (Combined Email/Phone) -->
                      <td>
                        <div class="contact-info">
                          <?php if($email): ?>
                            <div><a href="mailto:<?php echo html_escape($email); ?>" class="text-primary"><i class="fa fa-envelope-o"></i> <?php echo html_escape($email); ?></a></div>
                          <?php endif; ?>
                          <?php if($phone): ?>
                            <div><a href="tel:<?php echo html_escape($phone); ?>" class="text-success"><i class="fa fa-phone"></i> <?php echo html_escape($phone); ?></a></div>
                          <?php endif; ?>
                          <?php if(!$email && !$phone): ?>
                            <span class="text-muted">No contact info</span>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php 
                    $serial++; 
                  endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="8" class="text-center">
                      <div style="padding:40px;">
                        <i class="fa fa-university fa-3x text-muted"></i>
                        <h4 class="text-muted">No students found</h4>
                        <p class="text-muted">Get started by adding your first university student or importing from Excel.</p>
                        <div class="btn-group">
                          <a href="<?php echo admin_url('student_sponsor_portal/university_student_form'); ?>" class="btn btn-primary">
                            <i class="fa fa-plus"></i> Add First Student
                          </a>
                          <a href="<?php echo admin_url('student_sponsor_portal/bulk_import_university_students'); ?>" class="btn btn-warning">
                            <i class="fa fa-upload"></i> Import Students
                          </a>
                        </div>
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
/* Button toolbar alignment */
.btn-toolbar {
  display: flex;
  gap: 5px;
  align-items: center;
}

.btn-toolbar .btn-group {
  margin-right: 0;
}

/* Main table styling */
.students-table {
  border: 1px solid #e9ecef;
  border-radius: 6px;
  overflow: hidden;
}

.students-table th { 
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  font-weight: 600; 
  font-size: 12px; 
  border-bottom: 2px solid #dee2e6;
  color: #495057;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 12px 8px;
}

.students-table td { 
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

/* Status-specific styling */
.label-success { 
  background-color: #28a745;
  color: white;
}
.label-warning { 
  background-color: #ffc107;
  color: #212529;
}
.label-info { 
  background-color: #17a2b8;
  color: white;
}
.label-default { 
  background-color: #6c757d;
  color: white;
}

/* Contact info styling */
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

/* Row options styling */
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

/* Row hover effects */
.student-row {
  transition: all 0.2s ease;
}

.student-row:hover { 
  background: #f8f9fa;
  transform: translateX(2px);
}

.student-row:hover .row-options { 
  display: block !important; 
}

/* Avatar styling */
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
}

.avatar__initials {
  position: absolute;
  font-size: 13px;
  color: #6c757d;
  line-height: 1;
  text-align: center;
  z-index: 1;
  font-weight: 600;
}

.avatar--has-image .avatar__initials {
  display: none;
}

.media-left { 
  padding-right: 12px; 
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .btn-toolbar {
    flex-direction: column;
    gap: 10px;
  }
}
</style>

<script>
var table;

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

// Export functions
function exportFilteredStudents() {
  var visibleRows = table.rows({ search: 'applied' }).data();
  if (visibleRows.length === 0) {
    alert('No students found with current filters');
    return;
  }
  
  var studentIds = [];
  table.rows({ search: 'applied' }).every(function() {
    var row = this.node();
    var studentId = $(row).data('student-id');
    if (studentId) {
      studentIds.push(studentId);
    }
  });
  
  if (studentIds.length === 0) {
    alert('No students to export');
    return;
  }
  
  exportStudentsByIds(studentIds, 'filtered_university_students_export');
}

function exportStudentsByIds(studentIds, filename) {
  var form = $('<form>', {
    method: 'POST',
    action: '<?php echo admin_url("student_sponsor_portal/export_university_students_by_ids"); ?>'
  });
  
  form.append($('<input>', {
    type: 'hidden',
    name: 'student_ids',
    value: JSON.stringify(studentIds)
  }));
  
  form.append($('<input>', {
    type: 'hidden',
    name: 'filename',
    value: filename
  }));
  
  // Add CSRF token if available
  if (typeof csrfData !== 'undefined' && csrfData && csrfData.token_name && csrfData.hash) {
    form.append($('<input>', {
      type: 'hidden',
      name: csrfData.token_name,
      value: csrfData.hash
    }));
  }
  
  $('body').append(form);
  form.submit();
  form.remove();
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
      if (table) {
        table.row('#student-row-' + id).remove().draw();
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
  table = $('#university-students-table').DataTable({
    responsive: true,
    pageLength: 25,
    order: [[0, "desc"]],
    columnDefs: [
      { orderable: false, targets: [1] }, // Name column
      { searchable: false, targets: [0] }  // ID column
    ],
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
});
</script>

<?php init_tail(); ?>