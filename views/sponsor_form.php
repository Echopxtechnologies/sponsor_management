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
                  <i class="fa fa-user-plus"></i>
                  <?= !empty($sponsor) ? 'Edit Sponsor' : 'Add New Sponsor'; ?>
                </h4>
              </div>
              <div class="col-md-4 text-right">
                <a href="<?= admin_url('student_sponsor_portal/sponsors'); ?>" class="btn btn-default">
                  <i class="fa fa-arrow-left"></i> Back to List
                </a>
              </div>
            </div>
            <hr class="hr-panel-heading">

            <?= form_open(admin_url('student_sponsor_portal/sponsor_form'), ['id' => 'sponsor-form']); ?>
            <?php if (!empty($sponsor['id'])): ?>
              <input type="hidden" name="sponsor_id" value="<?= (int)$sponsor['id']; ?>">
            <?php endif; ?>

            <!-- Tabs -->
            <div class="horizontal-tabs">
              <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
                <li class="active"><a href="#tab_basic" data-toggle="tab"><i class="fa fa-user"></i> Basic Info</a></li>
                <li><a href="#tab_contact" data-toggle="tab"><i class="fa fa-phone"></i> Contact</a></li>
                <li><a href="#tab_bank" data-toggle="tab"><i class="fa fa-bank"></i> Banking</a></li>
                <li><a href="#tab_students" data-toggle="tab"><i class="fa fa-graduation-cap"></i> Students</a></li>
                <li><a href="#tab_sponsorship" data-toggle="tab"><i class="fa fa-calendar"></i> Sponsorship</a></li>
                <li><a href="#tab_staff" data-toggle="tab"><i class="fa fa-user-circle"></i> Staff Account</a></li>
              </ul>
            </div>

            <div class="tab-content">
              <!-- BASIC -->
              <div class="tab-pane active" id="tab_basic">
                <div class="row">
                  <div class="col-md-6">
                    <?= render_input('name', 'Sponsor Name *', html_escape($sponsor['name'] ?? ''), 'text', ['required' => true]); ?>
                  </div>
                  <div class="col-md-6">
                    <?= render_select(
                      'sponsor_type',
                      [
                        ['id'=>'individual','name'=>'Individual'],
                        ['id'=>'company','name'=>'Company']
                      ],
                      ['id','name'],
                      'Sponsor Type *',
                      $sponsor['sponsor_type'] ?? '',
                      ['required' => true]
                    ); ?>
                  </div>
                </div>
                <?= render_input('sponsor_occupation', 'Occupation', html_escape($sponsor['sponsor_occupation'] ?? '')); ?>
              </div>

              <!-- CONTACT -->
              <div class="tab-pane" id="tab_contact">
                <div class="row">
                  <div class="col-md-6">
                    <?= render_input('email', 'Email', html_escape($sponsor['email'] ?? ''), 'email'); ?>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="country_id" class="control-label">Country</label>
                      <div class="country-select-wrapper">
                        <select name="country_id" id="country_id" class="form-control selectpicker" data-live-search="true" data-none-selected-text="Select Country">
                          <option value="">Select Country</option>
                          <?php if(!empty($countries)): foreach($countries as $c): ?>
                            <?php
                              $cid   = (int)($c['id'] ?? 0);
                              $cname = $c['name'] ?? $c['short_name'] ?? '';
                              $pcode = $c['phone_code'] ?? $c['calling_code'] ?? '';
                              $selected = (isset($sponsor) && (int)($sponsor['country_id'] ?? 0) === $cid);
                            ?>
                            <option value="<?php echo $cid; ?>"
                              data-phone-code="<?php echo html_escape($pcode); ?>"
                              <?php echo $selected ? 'selected' : ''; ?>>
                              <?php echo html_escape($cname); ?><?php echo $pcode!=='' ? ' ('.html_escape($pcode).')' : ''; ?>
                            </option>
                          <?php endforeach; endif; ?>
                        </select>
                        <button type="button" class="btn btn-default btn-sm add-new-btn" data-toggle="modal" data-target="#addCountryModal" title="Add New Country">
                          <i class="fa fa-plus"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="phone_code" class="control-label">Country Code</label>
                      <div class="input-group">
                        <div class="input-group-addon" id="phone-code-display">
                          <?php
                            $phone_code = '+1';
                            if(isset($sponsor) && !empty($sponsor['country_id']) && !empty($countries)) {
                              foreach($countries as $country) {
                                $cid = (int)($country['id'] ?? 0);
                                $pcode = $country['phone_code'] ?? $country['calling_code'] ?? '';
                                if((int)$sponsor['country_id'] === $cid && $pcode!==''){ $phone_code = $pcode; break; }
                              }
                            }
                            echo html_escape($phone_code);
                          ?>
                        </div>
                        <input type="hidden" name="phone_code" id="phone_code" value="<?php echo html_escape($phone_code); ?>">
                      </div>
                    </div>
                  </div>
                  <div class="col-md-9">
                    <?= render_input('contact_no', 'Phone', html_escape($sponsor['contact_no'] ?? ''), 'tel'); ?>
                  </div>
                </div>

                <?= render_textarea('address', 'Address', html_escape($sponsor['address'] ?? '')); ?>

                <div class="row">
                  <div class="col-md-4">
                    <?= render_input('city', 'City', html_escape($sponsor['city'] ?? '')); ?>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="state_id" class="control-label">State/Province</label>
                      <select name="state_id" id="state_id" class="form-control selectpicker" data-live-search="true" data-none-selected-text="Select State">
                        <option value="">Select State</option>
                        <?php if(!empty($states)): foreach($states as $s): ?>
                          <?php
                            $state_selected = (isset($sponsor) && (int)($sponsor['state_id'] ?? 0) === (int)$s['id']);
                          ?>
                          <option value="<?php echo (int)$s['id']; ?>" <?php echo $state_selected ? 'selected' : ''; ?>>
                            <?php echo html_escape($s['name']); ?>
                          </option>
                        <?php endforeach; endif; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <?= render_input('zip', 'Postal Code', html_escape($sponsor['zip'] ?? '')); ?>
                  </div>
                </div>
              </div>

             <!-- Bank Tab -->
                <div class="tab-pane" id="tab_bank">
                <div class="row bank-row">
                    <!-- Bank + Add button (same row, perfectly aligned) -->
                    <div class="col-md-6">
                    <div class="form-group">
                        <label for="bank_id">Bank</label>

                        <div class="bank-inline">
                        <select name="bank_id" id="bank_id"
                                class="selectpicker form-control"
                                data-live-search="true" data-width="100%">
                            <option value="">Non selected</option>
                            <?php foreach ($banks as $b): ?>
                            <option value="<?= (int)$b['id']; ?>"
                                <?= !empty($sponsor['bank_id']) && (int)$sponsor['bank_id']===(int)$b['id'] ? 'selected' : '' ?>>
                                <?= html_escape($b['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="button" class="btn btn-default bank-add-btn" id="btn-add-bank"
                                title="Add Bank">
                            <i class="fa fa-plus"></i>
                        </button>
                        </div>
                    </div>
                    </div>

                    <!-- Branch Name -->
                    <div class="col-md-6">
                    <?= render_input('sponsor_bank_branch_info', 'Branch Name',
                        html_escape($sponsor['sponsor_bank_branch_info'] ?? '')); ?>
                    </div>
                </div>

                <div class="row">
                    <!-- Branch/IFSC -->
                    <div class="col-md-6">
                    <?= render_input('sponsor_bank_branch_number', 'Branch/IFSC',
                        html_escape($sponsor['sponsor_bank_branch_number'] ?? '')); ?>
                    </div>

                    <!-- Account Number -->
                    <div class="col-md-6">
                    <?= render_input('sponsor_bank_account_no', 'Account Number',
                        html_escape($sponsor['sponsor_bank_account_no'] ?? '')); ?>
                    </div>
                </div>
                </div>

              <!-- STUDENTS SELECTION TAB -->
              <div class="tab-pane" id="tab_students">
                <div class="row">
                  <div class="col-md-12">
                    <h5><i class="fa fa-graduation-cap"></i> Select Students to Sponsor</h5>
                    <p class="text-muted">Choose multiple students from both school and university levels that this sponsor will support.</p>
                    <hr>
                  </div>
                </div>

                <!-- Search and Filter Bar -->
                <div class="row" style="margin-bottom: 20px;">
                  <div class="col-md-4">
                    <div class="input-group">
                      <input type="text" id="student-search" class="form-control" placeholder="Search students by name...">
                      <span class="input-group-addon"><i class="fa fa-search"></i></span>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <select id="student-type-filter" class="form-control">
                      <option value="">All Types</option>
                      <option value="school">School Students</option>
                      <option value="university">University Students</option>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <input type="text" id="location-filter" class="form-control" placeholder="Filter by location...">
                  </div>
                  <div class="col-md-2">
                    <button type="button" id="clear-filters" class="btn btn-default btn-block">Clear Filters</button>
                  </div>
                </div>

                <!-- Selected Students Summary -->
                <div class="row" style="margin-bottom: 20px;">
                  <div class="col-md-12">
                    <div class="alert alert-info" id="selected-summary">
                      <i class="fa fa-info-circle"></i> 
                      <strong>Selected Students:</strong> 
                      <span id="selected-count">0 School, 0 University</span>
                      <button type="button" class="btn btn-xs btn-default pull-right" id="clear-all-selections">Clear All</button>
                    </div>
                  </div>
                </div>

                <!-- Students List Container -->
                <div class="row">
                  <div class="col-md-12">
                    <div id="students-loading" class="text-center" style="padding: 40px;">
                      <i class="fa fa-spinner fa-spin fa-2x"></i>
                      <p>Loading students...</p>
                    </div>
                    
                    <div id="students-container" style="display: none;">
                      <!-- School Students Section -->
                      <div class="student-section" id="school-students-section">
                        <h6 class="section-header">
                          <i class="fa fa-school"></i> School Students
                          <span class="badge" id="school-count-badge">0</span>
                        </h6>
                        <div class="students-grid" id="school-students-grid">
                          <!-- Dynamic content -->
                        </div>
                      </div>

                      <!-- University Students Section -->
                      <div class="student-section" id="university-students-section">
                        <h6 class="section-header">
                          <i class="fa fa-university"></i> University Students
                          <span class="badge" id="university-count-badge">0</span>
                        </h6>
                        <div class="students-grid" id="university-students-grid">
                          <!-- Dynamic content -->
                        </div>
                      </div>
                    </div>

                    <div id="no-students-found" class="text-center" style="display: none; padding: 40px;">
                      <i class="fa fa-search fa-2x text-muted"></i>
                      <p class="text-muted">No students found matching your criteria.</p>
                    </div>
                  </div>
                </div>

                <!-- Hidden inputs to store selections -->
                <input type="hidden" name="selected_school_students" id="selected_school_students" value="">
                <input type="hidden" name="selected_university_students" id="selected_university_students" value="">
              </div>

              <!-- SPONSORSHIP -->
              <div class="tab-pane" id="tab_sponsorship">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="membership_start_date" class="control-label">Sponsorship Start Date</label>
                      <input type="date" name="membership_start_date" id="membership_start_date" class="form-control"
                        value="<?php echo isset($sponsor) ? ($sponsor['membership_start_date'] ?? '') : ''; ?>">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="membership_end_date" class="control-label">Sponsorship End Date</label>
                      <input type="date" name="membership_end_date" id="membership_end_date" class="form-control"
                        value="<?php echo isset($sponsor) ? ($sponsor['membership_end_date'] ?? '') : ''; ?>">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <?= render_select(
                      'sponsor_frequency',
                      [
                        ['id'=>'one_time','name'=>'One-time'],
                        ['id'=>'monthly','name'=>'Monthly'],
                        ['id'=>'quarterly','name'=>'Quarterly'],
                        ['id'=>'half_yearly','name'=>'Half-yearly'],
                        ['id'=>'yearly','name'=>'Yearly']
                      ],
                      ['id','name'],
                      'Payment Frequency',
                      $sponsor['sponsor_frequency'] ?? ''
                    ); ?>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="active" class="control-label">Sponsorship Status</label>
                      <select name="active" id="active" class="form-control">
                        <option value="1" <?php echo (isset($sponsor) && ($sponsor['active'] ?? 0) == 1) ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo (isset($sponsor) && ($sponsor['active'] ?? 0) == 0) ? 'selected' : ''; ?>>Inactive</option>
                      </select>
                      <small class="text-muted">Automatically set to Active when students are selected and dates are valid.</small>
                    </div>
                  </div>
                </div>

                <!-- Sponsorship Summary -->
                <div class="row" style="margin-top: 20px;">
                  <div class="col-md-12">
                    <div class="alert alert-info" id="sponsorship-summary" style="display: none;">
                      <h6><i class="fa fa-info-circle"></i> Sponsorship Summary</h6>
                      <div id="sponsorship-details"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- STAFF -->
              <div class="tab-pane" id="tab_staff">
                <div class="checkbox checkbox-primary">
                  <input type="checkbox" id="create_staff" name="create_staff" value="1" <?= !empty($sponsor['staff_id']) ? 'checked' : ''; ?>>
                  <label for="create_staff">Create a Staff login</label>
                </div>

                <div class="row">
                  <div class="col-md-4">
                    <?= render_input('staff_email', 'Login Email', html_escape($sponsor['email'] ?? ''), 'email'); ?>
                  </div>
                  <div class="col-md-4">
                    <?= render_input('staff_firstname', 'First Name', html_escape($sponsor['name'] ?? '')); ?>
                  </div>
                  <div class="col-md-4">
                    <?= render_input('staff_lastname', 'Last Name'); ?>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-4">
                    <?= render_input('staff_password', 'Password', '', 'password'); ?>
                  </div>
                  <div class="col-md-4">
                    <div class="checkbox checkbox-primary" style="margin-top:28px;">
                      <input type="checkbox" id="staff_active" name="staff_active" value="1" checked>
                      <label for="staff_active">Active Login</label>
                    </div>
                  </div>
                </div>

                <?php if (!empty($sponsor['id'])): ?>
                  <button type="submit" class="btn btn-success"
                          formaction="<?= admin_url('student_sponsor_portal/grant_sponsor_access'); ?>"
                          formmethod="post">
                    Grant Portal Access
                  </button>
                  <button type="submit" class="btn btn-danger"
                          formaction="<?= admin_url('student_sponsor_portal/revoke_sponsor_access'); ?>"
                          formmethod="post">
                    Revoke Portal Access
                  </button>
                <?php endif; ?>
              </div>
            </div><!-- /tab-content -->

            <!-- Actions -->
            <div class="form-group" style="margin-top:20px;border-top:1px solid #eee;padding-top:20px;">
              <button type="submit" class="btn btn-primary" id="save-sponsor-btn">
                <i class="fa fa-save"></i> <?= !empty($sponsor) ? 'Update Sponsor' : 'Save Sponsor'; ?>
              </button>
              <a href="<?= admin_url('student_sponsor_portal/sponsors'); ?>" class="btn btn-default">Cancel</a>
            </div>

            <?= form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add Country Modal -->
<div class="modal fade" id="addCountryModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Add New Country</h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="modal_country_name">Country Name *</label>
          <input type="text" id="modal_country_name" class="form-control" required placeholder="e.g., Sri Lanka">
        </div>
        <div class="form-group">
          <label for="modal_country_phone">Phone Code *</label>
          <input type="text" id="modal_country_phone" class="form-control" required placeholder="+94">
        </div>
        <small class="text-muted">This will be created instantly.</small>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="btnAddCountry" onclick="return addCountry();">Add Country</button>
      </div>
    </div>
  </div>
</div>

<!-- Add Bank Modal -->
<div class="modal fade" id="modalAddBank" tabindex="-1" role="dialog" aria-labelledby="addBankLbl">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title" id="addBankLbl"><i class="fa fa-bank"></i> Add Bank</h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Bank Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="nb_name">
        </div>
        <div class="form-group">
          <label>Branch</label>
          <input type="text" class="form-control" id="nb_branch">
        </div>
        <div class="form-group">
          <label>IFSC / Code</label>
          <input type="text" class="form-control" id="nb_ifsc">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="nb_save"><i class="fa fa-check"></i> Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Student Details Modal -->
<div class="modal fade" id="studentDetailsModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title"><i class="fa fa-user"></i> Student Details</h4>
      </div>
      <div class="modal-body" id="student-details-content">
        <!-- Dynamic content -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php init_tail(); ?>

<script>
// Student data and selection management
let allStudents = {
  school_students: [],
  university_students: []
};
let selectedSchoolStudents = [];
let selectedUniversityStudents = [];

/* -------- Student Management Functions -------- */
function loadStudents() {
  $('#students-loading').show();
  $('#students-container').hide();
  
  const sponsorId = $('input[name="sponsor_id"]').val() || '';
  
  $.ajax({
    url: '<?= admin_url("student_sponsor_portal/get_available_students"); ?>',
    type: 'GET',
    data: { sponsor_id: sponsorId },
    dataType: 'json',
    success: function(response) {
      if (response.success) {
        allStudents = response.data;
        initializeSelectedStudents();
        renderStudents();
        updateSponsorshipSummary();
      } else {
        alert('Error loading students: ' + (response.message || 'Unknown error'));
      }
      $('#students-loading').hide();
    },
    error: function(xhr, status, error) {
      console.error('AJAX Error:', xhr.responseText);
      alert('Error loading students. Please try again. Error: ' + error);
      $('#students-loading').hide();
    }
  });
}

function initializeSelectedStudents() {
  // Initialize selections based on existing data when editing
  selectedSchoolStudents = allStudents.school_students
    .filter(s => s.is_selected)
    .map(s => s.internal_id);
    
  selectedUniversityStudents = allStudents.university_students
    .filter(s => s.is_selected)
    .map(s => s.internal_id);
    
  updateSelectedSummary();
  updateHiddenInputs();
}

function renderStudents() {
  const searchTerm = $('#student-search').val().toLowerCase();
  const typeFilter = $('#student-type-filter').val();
  const locationFilter = $('#location-filter').val().toLowerCase();

  // Filter students based on search criteria
  let filteredSchool = allStudents.school_students.filter(student => {
    const matchesSearch = student.name.toLowerCase().includes(searchTerm) ||
                         student.internal_id.toLowerCase().includes(searchTerm);
    const matchesLocation = !locationFilter || student.location.toLowerCase().includes(locationFilter);
    const matchesType = !typeFilter || typeFilter === 'school';
    
    return matchesSearch && matchesLocation && matchesType;
  });

  let filteredUniversity = allStudents.university_students.filter(student => {
    const matchesSearch = student.name.toLowerCase().includes(searchTerm) ||
                         student.internal_id.toLowerCase().includes(searchTerm);
    const matchesLocation = !locationFilter || student.location.toLowerCase().includes(locationFilter);
    const matchesType = !typeFilter || typeFilter === 'university';
    
    return matchesSearch && matchesLocation && matchesType;
  });

  // Render school students
  renderStudentSection('school', filteredSchool);
  
  // Render university students
  renderStudentSection('university', filteredUniversity);

  // Show/hide sections and no results message
  const hasResults = filteredSchool.length > 0 || filteredUniversity.length > 0;
  
  $('#students-container').toggle(hasResults);
  $('#no-students-found').toggle(!hasResults);
  
  $('#school-students-section').toggle(filteredSchool.length > 0);
  $('#university-students-section').toggle(filteredUniversity.length > 0);
  
  // Update badges
  $('#school-count-badge').text(filteredSchool.length);
  $('#university-count-badge').text(filteredUniversity.length);
}

function renderStudentSection(type, students) {
  const gridId = type + '-students-grid';
  const $grid = $('#' + gridId);
  
  $grid.empty();
  
  students.forEach(student => {
    const isSelected = type === 'school' 
      ? selectedSchoolStudents.includes(student.internal_id)
      : selectedUniversityStudents.includes(student.internal_id);
    
    const studentCard = createStudentCard(student, isSelected);
    $grid.append(studentCard);
  });
}

function createStudentCard(student, isSelected) {
  const cardClass = isSelected ? 'student-card selected' : 'student-card';
  const checkIcon = isSelected ? 'fa-check-square' : 'fa-square-o';
  
  const typeInfo = student.type === 'school' 
    ? `<span class="label label-primary">${student.grade}</span>`
    : `<span class="label label-info">${student.grade}</span><br><small class="text-muted">${student.university || 'N/A'}</small>`;

  return `
    <div class="${cardClass}" data-internal-id="${student.internal_id}" data-type="${student.type}">
      <div class="student-card-header">
        <div class="student-select-checkbox">
          <i class="fa ${checkIcon}" aria-hidden="true"></i>
        </div>
        <div class="student-info">
          <h6 class="student-name">${student.name}</h6>
          <small class="text-muted">${student.internal_id}</small>
        </div>
      </div>
      <div class="student-card-body">
        <div class="row">
          <div class="col-xs-6">
            ${typeInfo}
          </div>
          <div class="col-xs-6 text-right">
            <small class="text-muted">
              <i class="fa fa-map-marker"></i> ${student.location}<br>
              <i class="fa fa-birthday-cake"></i> ${student.age}
            </small>
          </div>
        </div>
        <div class="student-actions">
          <button type="button" class="btn btn-xs btn-default view-details" 
                  data-student='${JSON.stringify(student).replace(/'/g, "&apos;")}'>
            <i class="fa fa-eye"></i> Details
          </button>
        </div>
      </div>
    </div>
  `;
}

function toggleStudentSelection(internalId, type) {
  if (type === 'school') {
    const index = selectedSchoolStudents.indexOf(internalId);
    if (index > -1) {
      selectedSchoolStudents.splice(index, 1);
    } else {
      selectedSchoolStudents.push(internalId);
    }
  } else {
    const index = selectedUniversityStudents.indexOf(internalId);
    if (index > -1) {
      selectedUniversityStudents.splice(index, 1);
    } else {
      selectedUniversityStudents.push(internalId);
    }
  }
  
  updateSelectedSummary();
  updateHiddenInputs();
  updateSponsorshipSummary();
  renderStudents(); // Re-render to update selection states
}

function updateSelectedSummary() {
  const schoolCount = selectedSchoolStudents.length;
  const universityCount = selectedUniversityStudents.length;
  
  $('#selected-count').text(`${schoolCount} School, ${universityCount} University`);
}

function updateHiddenInputs() {
  $('#selected_school_students').val(JSON.stringify(selectedSchoolStudents));
  $('#selected_university_students').val(JSON.stringify(selectedUniversityStudents));
}

function updateSponsorshipSummary() {
  const startDate = $('#membership_start_date').val();
  const endDate = $('#membership_end_date').val();
  const frequency = $('#sponsor_frequency').val();
  const schoolCount = selectedSchoolStudents.length;
  const universityCount = selectedUniversityStudents.length;
  const totalStudents = schoolCount + universityCount;

  if (totalStudents > 0 || startDate || endDate) {
    let summary = '<div class="row">';
    
    if (totalStudents > 0) {
      summary += `<div class="col-md-4">
        <strong>Selected Students:</strong><br>
        <i class="fa fa-school"></i> ${schoolCount} School Students<br>
        <i class="fa fa-university"></i> ${universityCount} University Students
      </div>`;
    }
    
    if (startDate || endDate) {
      summary += `<div class="col-md-4">
        <strong>Sponsorship Period:</strong><br>
        Start: ${startDate || 'Not set'}<br>
        End: ${endDate || 'Ongoing'}
      </div>`;
    }
    
    if (frequency) {
      summary += `<div class="col-md-4">
        <strong>Payment Frequency:</strong><br>
        ${frequency.charAt(0).toUpperCase() + frequency.slice(1).replace('_', ' ')}
      </div>`;
    }
    
    summary += '</div>';
    
    // Auto-set active status if students selected and dates are valid
    const today = new Date().toISOString().split('T')[0];
    if (totalStudents > 0 && startDate && (!endDate || endDate >= today) && startDate <= today) {
      $('#active').val('1');
      summary += '<div class="alert alert-success" style="margin-top: 10px;"><i class="fa fa-check"></i> Status automatically set to <strong>Active</strong> (students selected and dates are valid)</div>';
    }
    
    $('#sponsorship-details').html(summary);
    $('#sponsorship-summary').show();
  } else {
    $('#sponsorship-summary').hide();
  }
}

function clearAllSelections() {
  selectedSchoolStudents = [];
  selectedUniversityStudents = [];
  updateSelectedSummary();
  updateHiddenInputs();
  updateSponsorshipSummary();
  renderStudents();
}

function showStudentDetails(student) {
  let detailsHtml = `
    <div class="row">
      <div class="col-md-6">
        <h5>Basic Information</h5>
        <p><strong>Name:</strong> ${student.name}</p>
        <p><strong>ID:</strong> ${student.internal_id}</p>
        <p><strong>Type:</strong> ${student.type === 'school' ? 'School Student' : 'University Student'}</p>
        <p><strong>Grade/Year:</strong> ${student.grade}</p>
        <p><strong>Age:</strong> ${student.age}</p>
        <p><strong>Location:</strong> ${student.location}</p>
      </div>
      <div class="col-md-6">
        <h5>Contact Information</h5>
        <p><strong>Email:</strong> ${student.email || 'Not provided'}</p>
        <p><strong>Phone:</strong> ${student.phone || 'Not provided'}</p>
        <p><strong>DOB:</strong> ${student.dob || 'Not provided'}</p>
  `;
  
  if (student.type === 'university') {
    detailsHtml += `
        <h5 style="margin-top: 20px;">University Information</h5>
        <p><strong>University:</strong> ${student.university || 'N/A'}</p>
        <p><strong>Program:</strong> ${student.program || 'N/A'}</p>
    `;
  }
  
  detailsHtml += `
      </div>
    </div>
  `;
  
  $('#student-details-content').html(detailsHtml);
  $('#studentDetailsModal').modal('show');
}

// Add Country function
window.addCountry = function(){
  var name  = $.trim($('#modal_country_name').val());
  var phone = $.trim($('#modal_country_phone').val());
  if(!name || !phone){ alert('Country name and phone code are required'); return false; }

  $.ajax({
    url: '<?= admin_url("student_sponsor_portal/add_country_ajax"); ?>',
    type: 'POST',
    data: { country_name: name, phone_code: phone },
    dataType: 'json',
    success: function(r) {
      if(r && r.success) {
        var $sel = $('#country_id');
        $sel.append($('<option/>',{
          value: r.country_id, 
          text: r.country_name + (r.phone_code ? ' (' + r.phone_code + ')' : ''), 
          selected: true,
          'data-phone-code': r.phone_code
        }));
        
        if ($.fn.selectpicker) $sel.selectpicker('refresh');
        $('#phone-code-display').text(r.phone_code);
        $('#phone_code').val(r.phone_code);

        $('#addCountryModal').modal('hide');
        $('#modal_country_name').val(''); 
        $('#modal_country_phone').val('');
        alert_float('success', r.message || 'Country added successfully');
      } else {
        alert(r && r.message ? r.message : 'Failed to add country');
      }
    },
    error: function() {
      alert('Error adding country');
    }
  });
  return false;
};

/* -------- Form validation and event handlers -------- */
$(function(){
  // Initialize selectpicker
  if ($.fn.selectpicker) $('.selectpicker').selectpicker();

  // Load students when the students tab is first shown
  $('a[href="#tab_students"]').on('shown.bs.tab', function() {
    if (allStudents.school_students.length === 0 && allStudents.university_students.length === 0) {
      loadStudents();
    }
  });

  // Student card selection
  $(document).on('click', '.student-card', function(e) {
    if ($(e.target).hasClass('view-details') || $(e.target).parent().hasClass('view-details')) {
      return; // Don't toggle selection when clicking details button
    }
    
    const internalId = $(this).data('internal-id');
    const type = $(this).data('type');
    
    toggleStudentSelection(internalId, type);
  });

  // View student details
  $(document).on('click', '.view-details', function(e) {
    e.stopPropagation();
    const student = $(this).data('student');
    showStudentDetails(student);
  });

  // Search and filter functionality
  $('#student-search, #location-filter').on('input', function() {
    renderStudents();
  });

  $('#student-type-filter').on('change', function() {
    renderStudents();
  });

  $('#clear-filters').on('click', function() {
    $('#student-search, #location-filter').val('');
    $('#student-type-filter').val('');
    renderStudents();
  });

  $('#clear-all-selections').on('click', function() {
    if (confirm('Are you sure you want to clear all selected students?')) {
      clearAllSelections();
    }
  });

  // Country selection and phone code update
  $('#country_id').on('changed.bs.select change', function(){
    var $opt = $(this).find('option:selected');
    var code = $opt.data('phone-code') || '+1';
    $('#phone-code-display').text(code);
    $('#phone_code').val(code);

    // Load states for selected country
    var cid = $(this).val();
    var $state = $('#state_id');
    $state.empty().append('<option value="">Select State</option>');
    if ($.fn.selectpicker) $state.selectpicker('refresh');
    
    if (!cid) return;

    $.get('<?= admin_url('student_sponsor_portal/ajax_states/'); ?>'+cid, function(resp){
      var r; 
      try { r = JSON.parse(resp); } catch(e){ r = {results:[]}; }
      if (typeof csrfData !== 'undefined' && r && r[csrfData.token_name]) csrfData.hash = r[csrfData.token_name];
      (r.results || []).forEach(function(s){
        $state.append($('<option/>',{value:s.id,text:s.name}));
      });
      if ($.fn.selectpicker) $state.selectpicker('refresh');
    });
  });

  // Sponsorship date and status monitoring
  $('#membership_start_date, #membership_end_date, #sponsor_frequency').on('change', function() {
    updateSponsorshipSummary();
  });

  // Form validation
  $('#sponsor-form').on('submit', function(e){
    var ok = true;
    $(this).find('input[required], select[required]').each(function(){
      if ($(this).val() === '' || $(this).val() === null) {
        ok = false;
        $(this).addClass('has-error');
        var pane = $(this).closest('.tab-pane');
        if (pane.length) $('a[href="#'+pane.attr('id')+'"]').tab('show');
      } else {
        $(this).removeClass('has-error');
      }
    });
    if (!ok) {
      e.preventDefault();
      alert_float('danger','Please fill in all required fields.');
      return false;
    }

    // Update hidden inputs before submission
    updateHiddenInputs();
    
    // Disable submit button to prevent double submission
    $('#save-sponsor-btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
  });

  $('input,select').on('change keyup', function(){ $(this).removeClass('has-error'); });

  // Add Bank modal handlers
  $('#btn-add-bank').on('click', function(){
    $('#nb_name,#nb_branch,#nb_ifsc').val('');
    $('#modalAddBank').modal('show');
    setTimeout(function(){ $('#nb_name').focus(); }, 250);
  });

  $('#nb_save').on('click', function(){
    var name = $('#nb_name').val().trim();
    var branch = $('#nb_branch').val().trim();
    var ifsc = $('#nb_ifsc').val().trim();
    if (!name) { alert_float('warning','Bank name is required'); return; }

    var payload = { name: name, branch: branch, ifsc: ifsc };
    if (typeof csrfData !== 'undefined') payload[csrfData.token_name] = csrfData.hash;

    $.post('<?= admin_url('student_sponsor_portal/ajax_bank_create'); ?>', payload, function(resp){
      var r; try { r = JSON.parse(resp); } catch(e) { r = {success:false}; }
      if (!r.success) { alert_float('warning', r.message || 'Failed to add bank'); return; }
      if (typeof csrfData !== 'undefined' && r[csrfData.token_name]) csrfData.hash = r[csrfData.token_name];

      var $bank = $('#bank_id');
      $bank.append($('<option/>',{value:r.id, text:r.text, selected:true}));
      if ($.fn.selectpicker) $bank.selectpicker('refresh');

      $('#modalAddBank').modal('hide');
      alert_float('success','Bank added');
    });
  });

  // Initialize on page load if editing existing sponsor
  if ($('input[name="sponsor_id"]').val()) {
    setTimeout(function() {
      updateSponsorshipSummary();
    }, 500);
  }
});
</script>

<style>
  .bank-inline{
    display:flex; gap:8px; align-items:stretch;
  }
  .bank-inline .bootstrap-select{ flex:1; }
  .bank-add-btn{
    height:38px;                 /* matches default input height */
    display:flex; align-items:center; justify-content:center;
    padding:6px 10px;
  }
  /* Fix selectpicker toggle height to match */
  .bootstrap-select .dropdown-toggle{ height:38px; }
  @media (max-width:767px){
    .bank-inline{ flex-direction:row; }
  }

.has-error { border-color:#e74c3c !important; }
.nav-tabs-horizontal { border-bottom:2px solid #ddd; }
.nav-tabs-horizontal > li > a { border-radius:4px 4px 0 0; margin-right:2px; }
.nav-tabs-horizontal > li.active > a,
.nav-tabs-horizontal > li.active > a:hover,
.nav-tabs-horizontal > li.active > a:focus {
  background:#fff; border-color:#ddd #ddd transparent; border-bottom-color:transparent;
}
.tab-content { padding:20px 0; min-height:400px; }
.text-danger { color:#e74c3c; }
.form-group label .fa { margin-right:5px; }

/* Student Selection Styles */
.student-section {
  margin-bottom: 30px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 15px;
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  margin-bottom: 15px;
  font-weight: 600;
  color: #495057;
}

.section-header .badge {
  background: #007bff;
}

.students-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 15px;
  padding: 0 5px;
}

.student-card {
  border: 2px solid #dee2e6;
  border-radius: 8px;
  padding: 15px;
  background: white;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
}

.student-card:hover {
  border-color: #007bff;
  box-shadow: 0 4px 12px rgba(0,123,255,0.15);
  transform: translateY(-2px);
}

.student-card.selected {
  border-color: #28a745;
  background: #f8fff9;
  box-shadow: 0 4px 12px rgba(40,167,69,0.15);
}

.student-card-header {
  display: flex;
  align-items: flex-start;
  margin-bottom: 12px;
}

.student-select-checkbox {
  margin-right: 12px;
  color: #6c757d;
  font-size: 18px;
  line-height: 1;
  margin-top: 2px;
}

.student-card.selected .student-select-checkbox {
  color: #28a745;
}

.student-info {
  flex: 1;
  min-width: 0;
}

.student-name {
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 4px 0;
  color: #212529;
  line-height: 1.2;
  word-wrap: break-word;
}

.student-card-body {
  margin-top: 8px;
}

.student-card-body .label {
  font-size: 11px;
  padding: 3px 6px;
}

.student-actions {
  margin-top: 10px;
  text-align: center;
}

.student-actions .btn {
  font-size: 11px;
  padding: 4px 8px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .students-grid {
    grid-template-columns: 1fr;
    gap: 10px;
  }
  
  .student-card {
    padding: 12px;
  }
}