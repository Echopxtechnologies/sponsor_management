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
              <div class="col-md-6">
                <h4 class="customer-profile-group-heading" style="margin-top:60px; margin-left:13px;">
                  <i class="fa fa-university"></i> <?php echo isset($title) ? $title : 'University Students'; ?>
                </h4>
              </div>
              <div class="col-md-6">
                <div class="text-right" style="margin-top:15px;">
                  <a href="<?php echo admin_url('student_sponsor_portal/bulk_import_university_students'); ?>" 
                     class="btn btn-warning btn-sm" style="margin-right: 5px;">
                    <i class="fa fa-upload"></i> Import
                  </a>
                  <a href="<?php echo admin_url('student_sponsor_portal/export_university_students'); ?>" 
                     class="btn btn-success btn-sm" style="margin-right: 5px;">
                    <i class="fa fa-download"></i> Export CSV
                  </a>
                  <a href="<?php echo admin_url('student_sponsor_portal/download_university_students_template'); ?>" 
                     class="btn btn-info btn-sm" style="margin-right: 5px;">
                    <i class="fa fa-file-text-o"></i> Template
                  </a>
                  <a href="<?php echo admin_url('student_sponsor_portal/university_student_form'); ?>" 
                     class="btn btn-primary btn-sm">
                    <i class="fa fa-plus"></i> New Student
                  </a>
                </div>
              </div>
            </div>

            <hr class="hr-panel-heading">

            <!-- Students Table -->
            <div class="table-responsive">
              <table class="table table-hover students-table" id="university-students-table" width="100%">
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
                  <!-- Data loaded via AJAX -->
                </tbody>
              </table>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
    
.students-table { border: 1px solid #e9ecef; border-radius: 6px; overflow: hidden; }
.students-table th { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); font-weight: 600; font-size: 12px; border-bottom: 2px solid #dee2e6; color: #495057; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 8px; }
.students-table td { vertical-align: middle; font-size: 13px; padding: 12px 8px; border-bottom: 1px solid #f1f3f4; }
.label { font-size: 10px; padding: 4px 8px; border-radius: 12px; font-weight: 500; }
.label-success { background-color: #28a745; color: white; }
.label-warning { background-color: #ffc107; color: #212529; }
.label-info { background-color: #17a2b8; color: white; }
.label-default { background-color: #6c757d; color: white; }
.avatar { width: 42px; height: 42px; border-radius: 50%; overflow: hidden; border: 2px solid #e9ecef; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); display: flex; align-items: center; justify-content: center; position: relative; }
.avatar__image { width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0; z-index: 2; display: none; }
.avatar__initials { font-size: 13px; color: #6c757d; font-weight: 600; }
.avatar--has-image .avatar__initials { display: none !important; }
.avatar--has-image .avatar__image { display: block !important; }
.media-left { padding-right: 12px; }
.row-options { font-size: 11px; margin-top: 3px; display: none; }
.row-options a { color: #777; text-decoration: none; }
.row-options a.text-danger { color: #dc3545 !important; }
tr:hover .row-options { display: block; }
.contact-info div { margin-bottom: 2px; font-size: 12px; }
.sponsor-info { font-size: 12px; }
</style>

<?php init_tail(); ?>

<script>


$('#university-students-table').on('draw.dt', function() {
    $(this).closest('.dataTables_wrapper').removeClass('table-loading');
});
(function waitForjQuery() {
  if (typeof window.jQuery === 'undefined') {
    return setTimeout(waitForjQuery, 50);
  }

  (function($) {
    var yearMap = {
      '1Y1S': 'Year 1, Sem 1', '1Y2S': 'Year 1, Sem 2',
      '2Y1S': 'Year 2, Sem 1', '2Y2S': 'Year 2, Sem 2',
      '3Y1S': 'Year 3, Sem 1', '3Y2S': 'Year 3, Sem 2',
      '4Y1S': 'Year 4, Sem 1', '4Y2S': 'Year 4, Sem 2',
      '5Y1S': 'Year 5, Sem 1', '5Y2S': 'Year 5, Sem 2'
    };

    var statusConfig = {
      'active':     { class: 'success', icon: 'fa-check-circle' },
      'inactive':   { class: 'warning', icon: 'fa-pause-circle' },
      'verified':   { class: 'info',    icon: 'fa-check-circle' },
      'unverified': { class: 'default', icon: 'fa-question-circle' }
    };

    // Wait for DataTables to be available
    var dtTries = 0;
    (function waitForDataTables() {
      if (typeof $.fn.DataTable === 'undefined') {
        if (dtTries < 100) {
          dtTries++;
          return setTimeout(waitForDataTables, 50);
        }
        console.error('DataTables not loaded');
        return;
      }

      var universityTable = $('#university-students-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: admin_url + 'student_sponsor_portal/get_university_students_ajax',
          type: 'POST'
        },
        order: [[1, 'asc']],
        pageLength: 25,
        columns: [
          { 
            data: null, orderable: false, searchable: false,
            render: function(data, type, row, meta) {
              return '<strong>' + (meta.row + meta.settings._iDisplayStart + 1) + '</strong>';
            }
          },
          { 
            data: 'name',
            render: function(data, type, row) {
              var photoUrl = admin_url + 'student_sponsor_portal/display_profile_photo/' + row.id;
              return '<div class="media">' +
                '<div class="media-left">' +
                  '<div class="avatar" id="avatar-' + row.id + '">' +
                    '<span class="avatar__initials" id="initials-' + row.id + '">' + row.initials + '</span>' +
                    '<img src="' + photoUrl + '" class="avatar__image" id="avatar-img-' + row.id + '" ' +
                         'onload="hideUniversityInitials(' + row.id + ')" ' +
                         'onerror="showUniversityInitials(' + row.id + ')" style="display:none;">' +
                  '</div>' +
                '</div>' +
                '<div class="media-body">' +
                  '<strong><a href="' + admin_url + 'student_sponsor_portal/university_student_form/' + row.id + '">' + data + '</a></strong>' +
                  '<br><small class="text-muted">ID: ' + (row.university_internal_id || 'Not Set') + '</small>' +
                  '<div class="row-options">' +
                    '<a href="' + admin_url + 'student_sponsor_portal/university_student_form/' + row.id + '">Edit</a> | ' +
                    '<a href="#" onclick="deleteUniversityStudent(' + row.id + '); return false;" class="text-danger">Delete</a>' +
                  '</div>' +
                '</div>' +
              '</div>';
            }
          },
          { data: 'university_name', defaultContent: '<span class="text-muted">Not specified</span>' },
          { data: 'program_name', defaultContent: '<span class="text-muted">Not specified</span>' },
          { 
            data: 'university_year_of_study',
            render: function(data) {
              if (!data) return '<span class="text-muted">Not set</span>';
              return '<span class="label label-info">' + (yearMap[data] || data) + '</span>';
            }
          },
          { 
            data: 'status',
            render: function(data) {
              var cfg = statusConfig[data] || statusConfig['unverified'];
              return '<span class="label label-' + cfg.class + '"><i class="fa ' + cfg.icon + '"></i> ' + 
                     (data ? data.charAt(0).toUpperCase() + data.slice(1) : 'Unverified') + '</span>';
            }
          },
          { 
            data: 'all_sponsor_names',
            render: function(data, type, row) {
              if (!data) return '<span class="text-muted"><i class="fa fa-heart-o"></i> No Sponsor</span>';
              var html = '<div class="sponsor-info"><strong class="text-success">' + data + '</strong>';
              if (row.sponsor_count > 1) {
                html += '<br><small class="text-info"><i class="fa fa-users"></i> ' + row.sponsor_count + ' Sponsors</small>';
              }
              return html + '</div>';
            }
          },
          { 
            data: 'email',
            render: function(data, type, row) {
              var html = '<div class="contact-info">';
              if (data) html += '<div><a href="mailto:' + data + '" class="text-primary"><i class="fa fa-envelope-o"></i> ' + data + '</a></div>';
              if (row.contact_no) html += '<div><a href="tel:' + row.contact_no + '" class="text-success"><i class="fa fa-phone"></i> ' + row.contact_no + '</a></div>';
              if (!data && !row.contact_no) html += '<span class="text-muted">No contact info</span>';
              return html + '</div>';
            }
          }
        ],
        language: {
          processing: '<i class="fa fa-spinner fa-spin fa-2x"></i> Loading...',
          emptyTable: '<div style="padding:40px;text-align:center;"><i class="fa fa-university fa-3x text-muted"></i><h4 class="text-muted">No students found</h4></div>'
        }
      });

      console.log('✅ University Students DataTable initialized');

    })();

    // Delete function
    window.deleteUniversityStudent = function(id) {
      if (!confirm('Are you sure you want to delete this student?')) return;
      $.post(admin_url + 'student_sponsor_portal/delete_university_student', { student_id: id }, function(response) {
        if (response.success) {
          $('#university-students-table').DataTable().ajax.reload();
          if (typeof alert_float === 'function') {
            alert_float('success', response.message || 'Student deleted');
          }
        } else {
          if (typeof alert_float === 'function') {
            alert_float('danger', response.message || 'Error deleting student');
          }
        }
      }, 'json');
    };

    // Avatar functions
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

  })(window.jQuery);
})();
</script>