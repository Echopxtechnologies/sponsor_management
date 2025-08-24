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
                <a href="<?php echo admin_url('student_sponsor_portal/export_university_students'); ?>" class="btn btn-success" style="margin-right:10px;">
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
                  <?php if(!empty($university_students)): ?>
                    <?php foreach($university_students as $student): ?>
                      <tr class="student-row" id="uni-student-row-<?php echo (int)$student['id']; ?>">
                        <td>
                          <strong><?php echo htmlspecialchars($student['university_internal_id'] ?? 'UNI'.str_pad($student['id'], 3, '0', STR_PAD_LEFT)); ?></strong>
                        </td>
                        <td>
                          <div class="media">
                            <?php if(!empty($student['profile_photo'])): ?>
                              <div class="media-left">
                                <img src="<?php echo $student['profile_photo']; ?>" alt="Profile" class="media-object img-circle" style="width:40px;height:40px;">
                              </div>
                            <?php endif; ?>
                            <div class="media-body">
                              <strong><?php echo htmlspecialchars($student['name']); ?></strong>
                              <div class="row-options" style="display:none;">

                                <a href="<?php echo admin_url('student_sponsor_portal/university_student_form/'.$student['id']); ?>">Edit</a> |
                                <a href="#" onclick="deleteUniStudent(<?php echo (int)$student['id']; ?>); return false;" class="text-danger">Delete</a>
                              </div>
                              <?php if(!empty($student['university_student_dob'])): ?>
                                <?php
                                $age = null;
                                if($student['university_student_dob']){
                                  try {
                                    $dob = new DateTime($student['university_student_dob']);
                                    $now = new DateTime();
                                    $age = $dob->diff($now)->y;
                                  } catch(Exception $e) {}
                                }
                                ?>
                                <?php if($age): ?>
                                  <br><small class="text-muted">Age: <?php echo $age; ?></small>
                                <?php endif; ?>
                              <?php endif; ?>
                            </div>
                          </div>
                        </td>
                        <td><?php echo htmlspecialchars($student['university_name'] ?? 'Not specified'); ?></td>
                        <td><?php echo htmlspecialchars($student['program_name'] ?? 'Not specified'); ?></td>
                        <td>
                          <?php 
                          $year_display = '';
                          if(!empty($student['university_year_of_study'])){
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
                            $year_display = $year_map[$student['university_year_of_study']] ?? $student['university_year_of_study'];
                          }
                          ?>
                          <?php if($year_display): ?>
                            <span class="label label-info"><?php echo $year_display; ?></span>
                          <?php else: ?>
                            <span class="text-muted">Not set</span>
                          <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($student['email'] ?? 'Not provided'); ?></td>
                        <td><?php echo htmlspecialchars($student['contact_no'] ?? 'Not provided'); ?></td>
                        <td>
                          <div class="btn-group">
                            <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                              <i class="fa fa-cogs"></i> <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right">
                              <li><a href="javascript:void(0)" onclick="viewUniStudent(<?php echo (int)$student['id']; ?>)">
                                <i class="fa fa-eye"></i> View Details</a></li>
                              <li><a href="<?php echo admin_url('student_sponsor_portal/university_student_form/'.$student['id']); ?>">
                                <i class="fa fa-edit"></i> Edit</a></li>
                              <li class="divider"></li>
                              <li><a href="#" onclick="deleteUniStudent(<?php echo (int)$student['id']; ?>); return false;" class="text-danger">
                                <i class="fa fa-trash"></i> Delete</a></li>
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
<div class="modal fade" id="uniStudentViewModal" tabindex="-1" role="dialog" aria-labelledby="uniStudentViewModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title" id="uniStudentViewModalLabel"><i class="fa fa-university"></i> Student Details</h4>
      </div>
      <div class="modal-body" id="uniStudentViewContent">
        <div class="text-center">
          <i class="fa fa-spinner fa-spin fa-2x"></i>
          <p>Loading student details...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <a id="editUniStudentBtn" class="btn btn-primary"><i class="fa fa-edit"></i> Edit Student</a>
      </div>
    </div>
  </div>
</div>

<script>
var currentUniStudentId = null;

function viewUniStudent(id){
  currentUniStudentId = id;
  $('#uniStudentViewModal').modal('show');

  $.post('<?php echo admin_url("student_sponsor_portal/get_university_student"); ?>',
    {student_id:id, action:'view'},
    function(resp){
      if(resp && resp.success){
        $('#uniStudentViewContent').html(resp.html);
      }else{
        $('#uniStudentViewContent').html('<div class="alert alert-danger">'+(resp.message||'Error loading student')+'</div>');
      }
    }, 'json'
  ).fail(function(){
    $('#uniStudentViewContent').html('<div class="alert alert-danger">Error loading student details</div>');
  });
}

function deleteUniStudent(id){
  if(!confirm('Are you sure you want to delete this student? This action cannot be undone.')) return;
  var row = $('#uni-student-row-'+id).css('opacity','0.5');

  var postData = {student_id:id};
  if(typeof csrfData !== 'undefined'){ for (var k in csrfData){ postData[k]=csrfData[k]; } }

  $.post('<?php echo admin_url("student_sponsor_portal/delete_university_student"); ?>', postData, function(resp){
    if(resp && resp.success){
      if($.fn.DataTable && $.fn.DataTable.isDataTable('#university-students-table')){
        var t = $('#university-students-table').DataTable();
        t.row('#uni-student-row-'+id).remove().draw();
      } else {
        $('#uni-student-row-'+id).remove();
      }
      alert_float('success', resp.message || 'Student deleted successfully');
    }else{
      row.css('opacity','1');
      alert_float('danger', (resp && resp.message) || 'Error deleting student');
    }
  },'json').fail(function(){
    row.css('opacity','1');
    alert_float('danger','Error deleting student');
  });
}

$(function(){
  // Datatable
  $('#university-students-table').DataTable({
    pageLength:10, 
    searching:false, 
    ordering:true, 
    info:true, 
    responsive:true,
    order:[[0,'desc']], 
    columnDefs:[{orderable:false, targets:[7]}],
    language: {
      emptyTable: "No university students found",
      zeroRecords: "No matching students found"
    }
  });

  // Hover row options
  $(document).on('mouseenter','.student-row',function(){ $(this).find('.row-options').show(); })
             .on('mouseleave','.student-row',function(){ $(this).find('.row-options').hide(); });

  // Search
  $('#search_students').on('keyup', function(){
    $('#university-students-table').DataTable().search(this.value).draw();
  });

  // Filters
  $('#filter_year').on('change', function(){
    var t=$('#university-students-table').DataTable();
    var year=$(this).val();
    if(year){ 
      t.column(4).search(year).draw(); 
    } else {
      t.column(4).search('').draw();
    }
  });

  $('#filter_program').on('keyup', function(){
    $('#university-students-table').DataTable().column(3).search($(this).val()).draw();
  });

  $('#filter_university').on('keyup', function(){
    $('#university-students-table').DataTable().column(2).search($(this).val()).draw();
  });

  if($.fn.selectpicker){ $('.selectpicker').selectpicker(); }

  $('#editUniStudentBtn').on('click', function(){
    if(currentUniStudentId){
      window.location.href = '<?php echo admin_url("student_sponsor_portal/university_student_form/"); ?>'+currentUniStudentId;
    }
  });

  $('#uniStudentViewModal').on('hidden.bs.modal', function(){
    $('#uniStudentViewContent').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Loading student details...</p></div>');
    currentUniStudentId = null;
  });
});
</script>

<style>
.students-table th{background:#f8f9fa;font-weight:600;font-size:12px;}
.students-table td{vertical-align:middle;font-size:13px;}
.label{font-size:10px;padding:3px 6px;}
.row-options{font-size:11px;color:#777;display:none!important;margin-top:2px}
.row-options a{color:#777;text-decoration:none}
.row-options a:hover{color:#333;text-decoration:none}
.row-options a.text-danger{color:#d9534f!important}
.row-options a.text-danger:hover{color:#c9302c!important}
.student-row:hover{background:#f9f9f9}
.student-row:hover .row-options{display:block!important}
.modal-lg{width:900px}
#uniStudentViewContent h5{color:#337ab7;border-bottom:1px solid #ddd;padding-bottom:10px;margin-bottom:15px}
#uniStudentViewContent p{margin-bottom:8px}
#uniStudentViewContent .row{margin-bottom:15px}
.media-object.img-circle{border:2px solid #ddd;}
.btn-xs{padding:2px 5px;font-size:11px;}
.dropdown-menu{min-width:120px;}
</style>
<?php init_tail(); ?>