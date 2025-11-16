<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<!-- CRITICAL: Define Avatar Functions BEFORE HTML that uses them -->
<script>
// Avatar Management Functions - Defined Early
function hideUniversityInitials(studentId) {
  var avatar = document.getElementById('avatar-' + studentId);
  var initials = document.getElementById('initials-' + studentId);
  var image = document.getElementById('avatar-img-' + studentId);
  
  if (avatar && initials && image) {
    avatar.classList.add('avatar--has-image');
    initials.style.display = 'none';
    image.style.display = 'block';
  }
}

function showUniversityInitials(studentId) {
  var avatar = document.getElementById('avatar-' + studentId);
  var initials = document.getElementById('initials-' + studentId);
  var image = document.getElementById('avatar-img-' + studentId);
  
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
                  <i class="fa fa-university"></i> <?php echo isset($title) ? $title : 'University Students'; ?>
                </h4>
              </div>
              <div class="col-md-6">
                <div class="text-right" style="margin-top:15px;">
                  <!-- Import Button -->
                  <a href="<?php echo admin_url('student_sponsor_portal/bulk_import_university_students'); ?>" 
                     class="btn btn-warning btn-sm" 
                     style="margin-right: 5px;"
                     title="Import students from Excel/CSV file">
                    <i class="fa fa-upload"></i> Import
                  </a>
                  
                  <!-- Export Button (Direct CSV Download) -->
                  <a href="<?php echo admin_url('student_sponsor_portal/export_university_students'); ?>" 
                     class="btn btn-success btn-sm" 
                     style="margin-right: 5px;"
                     title="Export all students to CSV file">
                    <i class="fa fa-download"></i> Export CSV
                  </a>
                  
                  <!-- Download Template Button -->
                  <a href="<?php echo admin_url('student_sponsor_portal/download_university_students_template'); ?>" 
                     class="btn btn-info btn-sm" 
                     style="margin-right: 5px;"
                     title="Download import template">
                    <i class="fa fa-file-text-o"></i> Template
                  </a>
                  
                  <!-- Add Student Button -->
                  <a href="<?php echo admin_url('student_sponsor_portal/university_student_form'); ?>" 
                     class="btn btn-primary btn-sm">
                    <i class="fa fa-plus"></i> New Student
                  </a>
                </div>
              </div>
            </div>

            <hr class="hr-panel-heading">

            <!-- Statistics Summary -->
            <?php if(!empty($students) && count($students) > 0): ?>
            <div class="row" style="margin-bottom: 15px;">
              <div class="col-md-12">
                <div class="alert alert-info" style="margin-bottom: 10px; padding: 8px 15px;">
                  <i class="fa fa-info-circle"></i>
                  <strong>Total Students: <?php echo count($students); ?></strong>
                  <span class="pull-right">
                    <small>Click on student name to view/edit details</small>
                  </span>
                </div>
              </div>
            </div>
            <?php endif; ?>

            <!-- Students Table -->
            <div class="table-responsive">
              <table class="table table-hover students-table dt-table" id="university-students-table" width="100%">
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
                      // Student data
                      $sid    = (int)($s['id'] ?? 0);
                      $name   = (string)($s['name'] ?? '');
                      $university = (string)($s['university_name'] ?? '');
                      $program = (string)($s['program_name'] ?? '');
                      $year_code = (string)($s['university_year_of_study'] ?? '');
                      $email  = (string)($s['email'] ?? '');
                      $phone  = (string)($s['contact_no'] ?? '');
                      $staff_id = $s['staff_id'] ?? null;
                      $active = (int)($s['active'] ?? 0);
                      $university_internal_id = isset($s['university_internal_id']) && $s['university_internal_id'] !== '' && $s['university_internal_id'] !== 0 
                        ? $s['university_internal_id'] 
                        : 'Not Set';

                      // Sponsor information
                      $sponsor_name = (string)($s['sponsor_name'] ?? '');
                      $sponsor_type = (string)($s['sponsor_type'] ?? '');
                      $all_sponsor_names = (string)($s['all_sponsor_names'] ?? '');
                      $sponsor_count = (int)($s['sponsor_count'] ?? 0);

                      // Determine status
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

                      // Generate initials for avatar
                      $initials = '';
                      foreach (preg_split('/\s+/', trim($name)) as $p) {
                        if ($p !== '' && strlen($initials) < 2) {
                          $initials .= strtoupper(substr($p, 0, 1));
                        }
                      }

                      // Photo URL - using data-src for lazy loading
                      $photoUrl = admin_url('student_sponsor_portal/display_profile_photo/' . $sid);
                    ?>
                    <tr class="student-row"
                        id="student-row-<?php echo $sid; ?>"
                        data-student-id="<?php echo $sid; ?>"
                        data-year="<?php echo html_escape($year_code); ?>"
                        data-program="<?php echo html_escape(mb_strtolower($program)); ?>"
                        data-university="<?php echo html_escape(mb_strtolower($university)); ?>"
                        data-status="<?php echo html_escape($status); ?>"
                        data-sponsor="<?php echo html_escape(mb_strtolower($sponsor_name)); ?>">
                      
                      <!-- Serial Number -->
                      <td><strong><?php echo $serial; ?></strong></td>

                      <!-- Student Name with Avatar -->
                      <td>
                        <div class="media">
                          <div class="media-left">
                            <div class="avatar" id="avatar-<?php echo $sid; ?>">
                              <span class="avatar__initials" id="initials-<?php echo $sid; ?>">
                                <?php echo $initials !== '' ? html_escape($initials) : '•'; ?>
                              </span>
                              
                              <!-- LAZY LOADING: Use data-src instead of src -->
                              <img data-src="<?php echo $photoUrl; ?>"
                                   alt="<?php echo html_escape($name); ?>" 
                                   class="avatar__image"
                                   id="avatar-img-<?php echo $sid; ?>"
                                   onload="hideUniversityInitials(<?php echo $sid; ?>)"
                                   onerror="showUniversityInitials(<?php echo $sid; ?>)"
                                   style="display:none;">

                            </div>
                          </div>  
                          <div class="media-body">
                            <strong>
                              <a href="<?php echo admin_url('student_sponsor_portal/university_student_form/' . $sid); ?>">
                                <?php echo html_escape($name); ?>
                              </a>
                            </strong>
                            <br><small class="text-muted">ID: <?php echo $university_internal_id; ?></small>
                            <div class="row-options" style="display:none;">
                              <a href="<?php echo admin_url('student_sponsor_portal/university_student_form/' . $sid); ?>">Edit</a> |
                              <a href="#" onclick="deleteUniversityStudent(<?php echo $sid; ?>); return false;" class="text-danger">Delete</a>
                            </div>
                          </div>
                        </div>
                      </td>

                      <!-- University -->
                      <td>
                        <?php echo $university ? html_escape($university) : '<span class="text-muted">Not specified</span>'; ?>
                      </td>
                      
                      <!-- Program -->
                      <td>
                        <?php echo $program ? html_escape($program) : '<span class="text-muted">Not specified</span>'; ?>
                      </td>
                      
                      <!-- Year of Study -->
                      <td>
                        <?php if($year_display !== ''): ?>
                          <span class="label label-info"><?php echo html_escape($year_display); ?></span>
                        <?php else: ?>
                          <span class="text-muted">Not set</span>
                        <?php endif; ?>
                      </td>
                      
                      <!-- Status -->
                      <td>
                        <span class="label label-<?php echo $status_class; ?>" title="<?php echo ucfirst($status); ?>">
                          <i class="fa <?php echo $status_icon; ?>"></i> <?php echo ucfirst($status); ?>
                        </span>
                      </td>
                      
                      <!-- Sponsor -->
                      <td>
                        <?php if($all_sponsor_names): ?>
                          <div class="sponsor-info">
                            <strong class="text-success">
                              <?php echo html_escape($all_sponsor_names); ?>
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
                      
                      <!-- Contact -->
                      <td>
                        <div class="contact-info">
                          <?php if($email): ?>
                            <div>
                              <a href="mailto:<?php echo html_escape($email); ?>" class="text-primary">
                                <i class="fa fa-envelope-o"></i> <?php echo html_escape($email); ?>
                              </a>
                            </div>
                          <?php endif; ?>
                          <?php if($phone): ?>
                            <div>
                              <a href="tel:<?php echo html_escape($phone); ?>" class="text-success">
                                <i class="fa fa-phone"></i> <?php echo html_escape($phone); ?>
                              </a>
                            </div>
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
                  <!-- Empty State -->
                  <tr>
                    <td colspan="8" class="text-center">
                      <div style="padding:40px;">
                        <i class="fa fa-university fa-3x text-muted"></i>
                        <h4 class="text-muted">No students found</h4>
                        <p class="text-muted">Get started by adding your first university student or importing from Excel/CSV.</p>
                        <div class="btn-group">
                          <a href="<?php echo admin_url('student_sponsor_portal/university_student_form'); ?>" 
                             class="btn btn-primary">
                            <i class="fa fa-plus"></i> Add First Student
                          </a>
                          <a href="<?php echo admin_url('student_sponsor_portal/bulk_import_university_students'); ?>" 
                             class="btn btn-warning">
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

<!-- View Student Modal -->
<div class="modal fade" id="studentViewModal" tabindex="-1" role="dialog" aria-labelledby="studentViewModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title" id="studentViewModalLabel">
          <i class="fa fa-university"></i> Student Details
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
        <a id="editStudentBtn" class="btn btn-primary">
          <i class="fa fa-edit"></i> Edit Student
        </a>
      </div>
    </div>
  </div>
</div>

<style>
/* DataTables Custom Styling - FIXED VERSION */
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

/* Main Table Styling */
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

/* Label/Badge Styling */
.label { 
  font-size: 10px; 
  padding: 4px 8px;
  border-radius: 12px;
  font-weight: 500;
}

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

/* Sponsor Info Styling */
.sponsor-info {
  font-size: 12px;
}

.sponsor-info strong {
  display: block;
  margin-bottom: 2px;
  font-size: 12px;
}

.sponsor-info small {
  display: block;
  line-height: 1.2;
  font-size: 11px;
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

/* Row Hover Effects */
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

/* Avatar Styling */
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

/* Alert Styling */
.alert-info {
  background-color: #d1ecf1;
  border-color: #bee5eb;
  color: #0c5460;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
  .text-right {
    text-align: left !important;
  }
  
  .table-responsive {
    font-size: 12px;
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
</style>

<script>
(function waitForjQuery() {
  if (typeof window.jQuery === 'undefined') {
    return setTimeout(waitForjQuery, 50);
  }

  (function($) {

    // expose avatar helpers because HTML uses onload/onerror
    window.hideUniversityInitials = function(studentId) {
      var avatar = document.getElementById('avatar-' + studentId);
      var initials = document.getElementById('initials-' + studentId);
      var image = document.getElementById('avatar-img-' + studentId);
      if (avatar && initials && image) {
        avatar.classList.add('avatar--has-image');
        initials.style.display = 'none';
        image.style.display = 'block';
      }
    };

    window.showUniversityInitials = function(studentId) {
      var avatar = document.getElementById('avatar-' + studentId);
      var initials = document.getElementById('initials-' + studentId);
      var image = document.getElementById('avatar-img-' + studentId);
      if (avatar && initials && image) {
        avatar.classList.remove('avatar--has-image');
        initials.style.display = 'block';
        image.style.display = 'none';
      }
    };

    // view & delete functions
    window.viewUniversityStudent = function(id) {
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
    };

    window.deleteUniversityStudent = function(id) {
      if (!confirm('Are you sure you want to delete this university student? This action cannot be undone.')) return;

      var $row = $('#student-row-' + id).css('opacity', '0.5');

      $.post('<?php echo admin_url("student_sponsor_portal/delete_university_student"); ?>', {
        student_id: id
      }, function(response) {
        if (response && response.success) {
          // when DT is ready we'll remove via API; if not, just remove row
          var table = $.fn.DataTable && $.fn.DataTable.isDataTable('#university-students-table')
            ? $('#university-students-table').DataTable()
            : null;

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
          $row.css('opacity', '1');
          var msg = (response && response.message) || 'Error deleting student';
          if (typeof alert_float === 'function') {
            alert_float('danger', msg);
          } else {
            alert('Error: ' + msg);
          }
        }
      }, 'json').fail(function() {
        $row.css('opacity', '1');
        if (typeof alert_float === 'function') {
          alert_float('danger', 'Error deleting student');
        } else {
          alert('Error deleting student');
        }
      });
    };

    // -------------------------
    // LAZY LOADING IMPLEMENTATION
    // -------------------------
    var tries = 0;
    (function waitForPerfexDT() {
      var $tbl = $('#university-students-table');

      // table exists AND DataTables plugin is there AND Perfex already initialized it
      if ($tbl.length && $.fn.DataTable && $.fn.DataTable.isDataTable($tbl)) {
        var universityTable = $tbl.DataTable();
        console.log('✅ hooked into existing University Students DataTable');

        // Function to load visible avatars
        function loadVisibleAvatars(table) {
          table.rows({page: 'current'}).every(function () {
            var node = this.node();
            var img = $(node).find('.avatar__image')[0];
            if (img && !img.src) {
              var realSrc = img.getAttribute('data-src');
              if (realSrc) img.src = realSrc;
            }
          });
        }

        // Load avatars for initial page
        loadVisibleAvatars(universityTable);

        // Load avatars on every page change/draw
        universityTable.on('draw', function () {
          // Update serial numbers
          var api = universityTable;
          var startIndex = api.context[0]._iDisplayStart;
          api.column(0, { page: 'current' }).nodes().each(function(cell, i) {
            cell.innerHTML = '<strong>' + (startIndex + i + 1) + '</strong>';
          });

          // Load avatars for current page
          loadVisibleAvatars(universityTable);
        });

        // Row hover effects for action links
        $(document).on('mouseenter', '.student-row', function() {
          $(this).find('.row-options').show();
        }).on('mouseleave', '.student-row', function() {
          $(this).find('.row-options').hide();
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

      // not ready yet → try again
      if (tries < 120) {    // ~12 seconds total
        tries++;
        return setTimeout(waitForPerfexDT, 100);
      } else {
        console.warn('DataTable on #university-students-table was not initialized by Perfex.');
      }
    })();

  })(window.jQuery);

})();
</script>

<?php init_tail(); ?>