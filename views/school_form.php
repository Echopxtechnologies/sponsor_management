<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-10 col-md-offset-1">
        <div class="panel_s">
          <div class="panel-body">
            <!-- Header -->
            <div class="row">
              <div class="col-md-8">
                <h4 class="customer-profile-group-heading">
                  <i class="fa fa-graduation-cap"></i>
                  <?php echo isset($student) ? 'Edit School Student' : 'Register School Student'; ?>
                </h4>
              </div>
              <div class="col-md-4 text-right">
                <a href="<?php echo admin_url('student_sponsor_portal/school_students'); ?>" class="btn btn-default">
                  <i class="fa fa-arrow-left"></i> Back to List
                </a>
              </div>
            </div>
            <hr class="hr-panel-heading">

            <?php
              echo form_open_multipart(
                admin_url('student_sponsor_portal/school_student_form'),
                ['id' => 'school-student-form', 'novalidate' => 'novalidate', 'autocomplete'=>'off']
              );
            ?>
            <?php if(isset($student)): ?>
              <input type="hidden" name="student_id" value="<?php echo (int)$student['id']; ?>">
            <?php endif; ?>

            <!-- hidden for "create new" items (created now via AJAX OR on save, depending on endpoint) -->
            <input type="hidden" name="new_country_name" id="new_country_name">
            <input type="hidden" name="new_country_phone_code" id="new_country_phone_code">
            <input type="hidden" name="new_school_name" id="new_school_name">
            <input type="hidden" name="new_bank_name" id="new_bank_name">

            <?php if(isset($student) && !empty($student)): ?>
              <?php
              $all_fields = ['name','email','contact_no','address','city','zip','school_name_id','school_grade','school_student_dob','school_father_name','school_mother_name','bank_id','school_bank_account_no'];
              $completed = 0;
              foreach($all_fields as $field) { if (!empty($student[$field])) $completed++; }
              $completion = round(($completed / count($all_fields)) * 100);
              ?>
              <div class="alert alert-info" id="profile-completion-wrap">
                <i class="fa fa-info-circle"></i>
                Profile Completion: <strong id="profile-completion-value"><?php echo $completion; ?>%</strong>
                <div class="progress" style="margin-top:5px;">
                  <div class="progress-bar" id="profile-completion-bar" style="width:<?php echo $completion; ?>%"></div>
                </div>
              </div>
            <?php endif; ?>

            <ul class="nav nav-tabs" role="tablist">
              <li role="presentation" class="active"><a href="#student-info" aria-controls="student-info" role="tab" data-toggle="tab"><i class="fa fa-user"></i> Student Info</a></li>
              <li role="presentation"><a href="#sponsorship" aria-controls="sponsorship" role="tab" data-toggle="tab"><i class="fa fa-heart"></i> Sponsorship</a></li>
              <li role="presentation"><a href="#bank-info" aria-controls="bank-info" role="tab" data-toggle="tab"><i class="fa fa-bank"></i> Bank Info</a></li>
              <li role="presentation"><a href="#additional-info" aria-controls="additional-info" role="tab" data-toggle="tab"><i class="fa fa-info-circle"></i> Additional Info</a></li>
              <li role="presentation"><a href="#report-cards" aria-controls="report-cards" role="tab" data-toggle="tab"><i class="fa fa-file-text"></i> Report Cards</a></li>
              <li><a href="#tab_staff" data-toggle="tab"><i class="fa fa-user-circle"></i> Staff Account</a></li>
            </ul>

            <div class="tab-content" style="margin-top:20px;">

              <!-- Student Info -->
              <div role="tabpanel" class="tab-pane active" id="student-info">
                <h5><i class="fa fa-user"></i> Basic Information</h5>
                <hr>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="name" class="control-label">Full Name *</label>
                      <input type="text" name="name" id="name" required class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['name'] ?? '') : ''; ?>" placeholder="Enter student's full name">
                    </div>

                    <div class="form-group">
                      <label for="email" class="control-label">Email Address</label>
                      <input type="email" name="email" id="email" class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['email'] ?? '') : ''; ?>" placeholder="Enter email address">
                    </div>

                    <div class="form-group">
                      <label for="phone" class="control-label">Phone Number</label>
                      <div class="input-group">
                        <div class="input-group-addon" id="phone-code-display">
                          <?php
                            $phone_code = '+1';
                            if(isset($student) && !empty($student['country_id']) && isset($countries)) {
                              foreach($countries as $country) {
                                if((int)$country['id'] === (int)$student['country_id']) { $phone_code = $country['phone_code']; break; }
                              }
                            }
                            echo html_escape($phone_code);
                          ?>
                        </div>
                        <input type="text" name="phone" id="phone" class="form-control"
                          value="<?php echo isset($student) ? html_escape($student['contact_no'] ?? '') : ''; ?>" placeholder="Enter phone number">
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="dob" class="control-label">Date of Birth</label>
                      <input type="date" name="dob" id="dob" class="form-control"
                        value="<?php echo isset($student) ? ($student['school_student_dob'] ?? '') : ''; ?>">
                    </div>

                    <div class="form-group">
                      <label class="control-label">Age</label>
                      <input type="text" id="calculated-age" class="form-control" readonly
                        value="<?php echo isset($student) ? ($student['school_age'] ?? '') : ''; ?>" placeholder="Will be calculated from DOB">
                    </div>

                    <div class="form-group">
                      <label for="profile_photo" class="control-label">Profile Photo</label>
                      <input type="file" name="profile_photo" id="profile_photo" class="form-control" accept="image/*">
                      <?php if(isset($student) && !empty($student['profile_photo'])): ?>
                        <div class="current-photo" style="margin-top: 10px;">
                          <img src="<?php echo admin_url('student_sponsor_portal/display_profile_photo/' . $student['id']); ?>" 
                               alt="Profile Photo" style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;">
                          <br><small class="text-muted">Current profile photo</small>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <!-- Country dropdown + add -->
                    <div class="form-group">
                      <label for="country_id" class="control-label">Country</label>
                      <div class="country-select-wrapper">
                        <select name="country_id" id="country_id" class="form-control selectpicker" data-live-search="true" data-none-selected-text="Select Country">
                          <option value="">Select Country</option>
                          <?php if(!empty($countries)): foreach($countries as $c): ?>
                            <option value="<?php echo (int)$c['id']; ?>"
                              data-phone-code="<?php echo html_escape($c['phone_code']); ?>"
                              <?php echo (isset($student) && (int)$student['country_id'] === (int)$c['id']) ? 'selected' : ''; ?>>
                              <?php echo html_escape($c['name']); ?> (<?php echo html_escape($c['phone_code']); ?>)
                            </option>
                          <?php endforeach; endif; ?>
                        </select>
                        <button type="button" class="btn btn-default btn-sm add-new-btn" data-toggle="modal" data-target="#addCountryModal" title="Add New Country">
                          <i class="fa fa-plus"></i>
                        </button>
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="address" class="control-label">Address</label>
                      <textarea name="address" id="address" class="form-control" rows="3" placeholder="Complete address"><?php echo isset($student) ? html_escape($student['address'] ?? '') : ''; ?></textarea>
                    </div>

                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group">
                          <label for="city" class="control-label">City / District</label>
                          <input type="text" name="city" id="city" class="form-control"
                            value="<?php echo isset($student) ? html_escape($student['city'] ?? '') : ''; ?>" placeholder="City / District">
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <label for="postal_code" class="control-label">Postal Code</label>
                          <input type="text" name="postal_code" id="postal_code" class="form-control"
                            value="<?php echo isset($student) ? html_escape($student['zip'] ?? '') : ''; ?>" placeholder="Postal code">
                        </div>
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="school_id" class="control-label">School Student ID</label>
                      <input type="text" name="school_id" id="school_id" class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['school_id'] ?? '') : ''; ?>" placeholder="Enter school student ID">
                    </div>
                  </div>
                </div>

                <h5><i class="fa fa-graduation-cap"></i> School Information</h5>
                <hr>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="grade" class="control-label">Grade / Class</label>
                      <select name="grade" id="grade" class="form-control">
                        <option value="">Select Grade</option>
                        <?php for($g=1;$g<=13;$g++): ?>
                          <option value="<?php echo $g; ?>" <?php echo (isset($student) && (string)($student['school_grade'] ?? '') === (string)$g) ? 'selected' : ''; ?>>Grade <?php echo $g; ?></option>
                        <?php endfor; ?>
                      </select>
                    </div>

                    <div class="form-group">
                      <label for="school_type" class="control-label">School Type</label>
                      <input type="text" name="school_type" id="school_type" class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['school_type'] ?? '') : ''; ?>" placeholder="e.g. Type 1AB">
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="school_name_id" class="control-label">School Name</label>
                      <div class="school-select-wrapper">
                        <select id="school_name_id" name="school_name_id" class="form-control selectpicker" data-live-search="true" data-none-selected-text="Select School">
                          <option value="">Select School</option>
                          <?php if(!empty($schools)): foreach($schools as $row): ?>
                            <?php
                              // support array rows or flat strings
                              $sid = is_array($row) ? (int)($row['id'] ?? 0) : 0;
                              $sname = is_array($row) ? ($row['name'] ?? '') : (string)$row;
                              // if your controller sent only names, store name in value with data-name; model handles name via school_name field (see hidden below)
                            ?>
                            <?php if($sid): ?>
                              <option value="<?php echo $sid; ?>" data-name="<?php echo html_escape($sname); ?>"
                                <?php echo (isset($student) && (int)($student['school_name_id'] ?? 0) === $sid) ? 'selected' : ''; ?>>
                                <?php echo html_escape($sname); ?>
                              </option>
                            <?php else: ?>
                              <option value="" data-name="<?php echo html_escape($sname); ?>"><?php echo html_escape($sname); ?></option>
                            <?php endif; ?>
                          <?php endforeach; endif; ?>
                        </select>
                        <button type="button" class="btn btn-default btn-sm add-new-btn" data-toggle="modal" data-target="#addSchoolModal" title="Add New School">
                          <i class="fa fa-plus"></i>
                        </button>
                      </div>
                      <!-- hidden name fallback (if you choose to send/accept name instead of id) -->
                      <input type="hidden" name="school_name" id="school_name" value="<?php echo isset($student) ? html_escape($student['school_name'] ?? '') : ''; ?>">
                    </div>
                  </div>
                </div>
              </div>

              <!-- Sponsorship -->
              <div role="tabpanel" class="tab-pane" id="sponsorship">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="sponsorship_start" class="control-label">Sponsorship Start Date</label>
                      <input type="date" name="sponsorship_start" id="sponsorship_start" class="form-control"
                        value="<?php echo isset($student) ? ($student['school_sponsorship_start_date'] ?? '') : ''; ?>">
                    </div>
                    <div class="form-group">
                      <label for="sponsorship_end" class="control-label">Sponsorship End Date</label>
                      <input type="date" name="sponsorship_end" id="sponsorship_end" class="form-control"
                        value="<?php echo isset($student) ? ($student['school_sponsorship_end_date'] ?? '') : ''; ?>">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="introduced_by" class="control-label">Introduced By</label>
                      <input type="text" name="introduced_by" id="introduced_by" class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['school_introducedby'] ?? '') : ''; ?>" placeholder="Person who introduced the student">
                    </div>
                    <div class="form-group">
                      <label for="introduced_phone" class="control-label">Introducer's Phone</label>
                      <input type="text" name="introduced_phone" id="introduced_phone" class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['school_introducedph'] ?? '') : ''; ?>" placeholder="Contact number">
                    </div>
                  </div>
                </div>
              </div>

              <!-- Bank Info -->
              <div role="tabpanel" class="tab-pane" id="bank-info">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="bank_id" class="control-label">Bank Name</label>
                      <div class="bank-select-wrapper">
                        <select name="bank_id" id="bank_id" class="form-control selectpicker" data-live-search="true" data-none-selected-text="Select Bank">
                          <option value="">Select Bank</option>
                          <?php if(!empty($banks)): foreach($banks as $b): ?>
                            <option value="<?php echo (int)$b['id']; ?>" <?php echo (isset($student) && (int)($student['bank_id'] ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>>
                              <?php echo html_escape($b['name']); ?>
                            </option>
                          <?php endforeach; endif; ?>
                        </select>
                        <button type="button" class="btn btn-default btn-sm add-new-btn" data-toggle="modal" data-target="#addBankModal" title="Add New Bank">
                          <i class="fa fa-plus"></i>
                        </button>
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="bank_account_number" class="control-label">Bank Account Number</label>
                      <input type="text" name="bank_account_number" id="bank_account_number" class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['school_bank_account_no'] ?? '') : ''; ?>" placeholder="Account number">
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="bank_branch_number" class="control-label">Bank Branch Number</label>
                      <input type="text" name="bank_branch_number" id="bank_branch_number" class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['school_bank_branch_number'] ?? '') : ''; ?>" placeholder="Branch code/number">
                    </div>
                    <div class="form-group">
                      <label for="bank_branch_info" class="control-label">Bank Branch Information</label>
                      <textarea name="bank_branch_info" id="bank_branch_info" class="form-control" rows="3" placeholder="Additional branch details"><?php echo isset($student) ? html_escape($student['school_bank_branch_info'] ?? '') : ''; ?></textarea>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Additional Info -->
              <div role="tabpanel" class="tab-pane" id="additional-info">
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="father_name" class="control-label">Father's Name</label>
                      <input type="text" name="father_name" id="father_name" class="form-control" value="<?php echo isset($student) ? html_escape($student['school_father_name'] ?? '') : ''; ?>">
                    </div>
                    <div class="form-group">
                      <label for="father_income" class="control-label">Father's Income</label>
                      <input type="number" step="0.01" name="father_income" id="father_income" class="form-control" value="<?php echo isset($student) ? html_escape($student['school_father_income'] ?? '') : ''; ?>" placeholder="Monthly income">
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="mother_name" class="control-label">Mother's Name</label>
                      <input type="text" name="mother_name" id="mother_name" class="form-control" value="<?php echo isset($student) ? html_escape($student['school_mother_name'] ?? '') : ''; ?>">
                    </div>
                    <div class="form-group">
                      <label for="mother_income" class="control-label">Mother's Income</label>
                      <input type="number" step="0.01" name="mother_income" id="mother_income" class="form-control" value="<?php echo isset($student) ? html_escape($student['school_mother_income'] ?? '') : ''; ?>" placeholder="Monthly income">
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="guardian_name" class="control-label">Guardian's Name</label>
                      <input type="text" name="guardian_name" id="guardian_name" class="form-control" value="<?php echo isset($student) ? html_escape($student['school_guardian_name'] ?? '') : ''; ?>">
                    </div>
                    <div class="form-group">
                      <label for="guardian_income" class="control-label">Guardian's Income</label>
                      <input type="number" step="0.01" name="guardian_income" id="guardian_income" class="form-control" value="<?php echo isset($student) ? html_escape($student['school_guardian_income'] ?? '') : ''; ?>" placeholder="Monthly income">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-12">
                    <div class="form-group">
                      <label for="background_information" class="control-label">Background Information</label>
                      <textarea name="background_information" id="background_information" class="form-control" rows="3" placeholder="Student background, family situation, etc."><?php echo isset($student) ? html_escape($student['background_info'] ?? '') : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                      <label for="internal_comment" class="control-label">Internal Comment</label>
                      <textarea name="internal_comment" id="internal_comment" class="form-control" rows="3" placeholder="Internal notes (not visible to sponsors)"><?php echo isset($student) ? html_escape($student['internal_comment'] ?? '') : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                      <label for="external_comment" class="control-label">External Comment</label>
                      <textarea name="external_comment" id="external_comment" class="form-control" rows="3" placeholder="Comments visible to sponsors"><?php echo isset($student) ? html_escape($student['external_comment'] ?? '') : ''; ?></textarea>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Report Cards -->
              <div role="tabpanel" class="tab-pane" id="report-cards">
                <?php if(isset($student) && !empty($student['id'])): ?>
                  <div class="row">
                    <div class="col-md-5">
                      <h5><i class="fa fa-upload"></i> Upload Report Card</h5>
                      <hr>
                      <form id="form-upload-report" enctype="multipart/form-data">
                        <input type="hidden" name="student_school_id" value="<?php echo (int)$student['id']; ?>">
                        <div class="form-group">
                          <label>Filename *</label>
                          <input type="text" name="filename" class="form-control" required placeholder="e.g. Term 1 - 2025">
                        </div>
                        <div class="form-group">
                          <label>Term *</label>
                          <select name="term" id="term" class="form-control" required>
                            <option value="">Select Term</option>
                            <option value="Term1">Term1</option>
                            <option value="Term2">Term2</option>
                            <option value="Term3">Term3</option>
                          </select>
                        </div>
                        <div class="form-group">
                          <label>Upload Date *</label>
                          <input type="date" name="upload_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                          <label>Report Card File (PDF/Image) *</label>
                          <input type="file" name="report_card_file" id="report_card_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <button class="btn btn-primary" type="button" id="btn-upload-card"><i class="fa fa-cloud-upload"></i> Upload</button>
                      </form>
                    </div>
                    <div class="col-md-7">
                      <h5><i class="fa fa-list"></i> Uploaded Report Cards</h5>
                      <hr>
                      <div class="table-responsive">
                        <table class="table table-striped" id="tbl-report-cards">
                          <thead>
                            <tr>
                              <th>#</th>
                              <th>Filename</th>
                              <th>Term</th>
                              <th>Date</th>
                              <th>Size</th>
                              <th>Actions</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr><td colspan="6" class="text-center">Loading...</td></tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                <?php else: ?>
                  <div class="alert alert-info"><i class="fa fa-info-circle"></i> Please save the student first before uploading report cards.</div>
                <?php endif; ?>
              </div>

              <!-- Staff Account Tab -->
              <div class="tab-pane" id="tab_staff">
                <div class="checkbox checkbox-primary">
                  <input type="checkbox" id="create_staff" name="create_staff" value="1" <?php echo !empty($student['staff_id']) ? 'checked' : ''; ?>>
                  <label for="create_staff">Create a Staff login</label>
                </div>
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="staff_email" class="control-label">Login Email</label>
                      <input type="email" name="staff_email" id="staff_email" class="form-control" value="<?php echo isset($student) ? html_escape($student['email'] ?? '') : ''; ?>">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="staff_firstname" class="control-label">First Name</label>
                      <input type="text" name="staff_firstname" id="staff_firstname" class="form-control" value="<?php echo isset($student) ? html_escape($student['name'] ?? '') : ''; ?>">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="staff_lastname" class="control-label">Last Name</label>
                      <input type="text" name="staff_lastname" id="staff_lastname" class="form-control">
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="staff_password" class="control-label">Password</label>
                      <input type="password" name="staff_password" id="staff_password" class="form-control">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="checkbox checkbox-primary" style="margin-top:28px;">
                      <input type="checkbox" id="staff_active" name="staff_active" value="1" <?php echo !isset($student) || ($student['staff_active'] ?? 1) ? 'checked' : ''; ?>>
                      <label for="staff_active">Active Login</label>
                    </div>
                  </div>
                </div>

                <?php if (!empty($student['id'])): ?>
                  <button type="submit" class="btn btn-success" formaction="<?php echo admin_url('student_sponsor_portal/grant_school_access'); ?>" formmethod="post">Grant Portal Access</button>
                  <button type="submit" class="btn btn-danger" formaction="<?php echo admin_url('student_sponsor_portal/revoke_school_access'); ?>" formmethod="post">Revoke Portal Access</button>
                <?php endif; ?>
              </div>

            </div>

            <button type="submit" id="btn-save-student" class="btn btn-primary btn-lg" form="school-student-form">
              <i class="fa fa-save"></i> <?php echo isset($student) ? 'Update Student' : 'Register Student'; ?>
            </button>

            <?php echo form_close(); ?>
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
          <small class="text-muted">Note: This may be created instantly or on Save (depending on your controller).</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="btnAddCountry" onclick="return addCountry();">Add Country</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Add School Modal -->
  <div class="modal fade" id="addSchoolModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Add New School</h4>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="modal_school_name">School Name *</label>
            <input type="text" id="modal_school_name" class="form-control" required placeholder="Enter school name">
          </div>
          <small class="text-muted">This will be created instantly.</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="btnAddSchool" onclick="return addSchool();">Add School</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Bank Modal -->
  <div class="modal fade" id="addBankModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Add New Bank</h4>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="modal_bank_name">Bank Name *</label>
            <input type="text" id="modal_bank_name" class="form-control" required placeholder="Enter bank name">
          </div>
          <small class="text-muted">This will be created instantly.</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="btnAddBank" onclick="return addBank();">Add Bank</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Age Warning Modal -->
  <div class="modal fade" id="ageWarningModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-exclamation-triangle text-warning"></i> Age Warning</h4>
        </div>
        <div class="modal-body">
          <p><strong>Warning:</strong> The student is under 18 years old (<span id="age-display"></span>).</p>
          <p>Please ensure proper guardian consent.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" data-dismiss="modal">Understood</button>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.nav-tabs>li>a{font-weight:500}
.nav-tabs>li.active>a,.nav-tabs>li.active>a:hover,.nav-tabs>li.active>a:focus{background:#f8f9fa;border-bottom-color:transparent}
.tab-content{background:#f8f9fa;padding:20px;border:1px solid #ddd;border-top:none;border-radius:0 0 4px 4px}
.tab-content h5{color:#337ab7;margin-top:20px;margin-bottom:10px}
.tab-content h5:first-child{margin-top:0}
.tab-content hr{margin:10px 0 20px}
.has-error{border-color:#d9534f}
.school-select-wrapper,.bank-select-wrapper,.country-select-wrapper{position:relative;display:flex;align-items:stretch}
.school-select-wrapper .bootstrap-select,.bank-select-wrapper .bootstrap-select,.country-select-wrapper .bootstrap-select{flex:1;margin-right:5px;width:auto!important}
.school-select-wrapper select,.bank-select-wrapper select,.country-select-wrapper select{flex:1;border-top-right-radius:0;border-bottom-right-radius:0}
.add-new-btn{border-left:0;border-radius:0 4px 4px 0!important;padding:8px 12px;margin-left:0;z-index:5;border-top-left-radius:0!important;border-bottom-left-radius:0!important;flex-shrink:0;height:34px}
.add-new-btn:hover{background-color:#337ab7;color:#fff;border-color:#2e6da4}
.school-select-wrapper .btn-group.bootstrap-select,.bank-select-wrapper .btn-group.bootstrap-select,.country-select-wrapper .btn-group.bootstrap-select{flex:1;width:auto!important;display:flex!important}
.school-select-wrapper .btn-group.bootstrap-select .btn,.bank-select-wrapper .btn-group.bootstrap-select .btn,.country-select-wrapper .btn-group.bootstrap-select .btn{width:100%;border-top-right-radius:0!important;border-bottom-right-radius:0!important;text-align:left}
</style>

<?php init_tail(); ?>
<script>
if (typeof alert_float !== 'function') { window.alert_float = function(type, message){ alert(message); }; }

(function($){

  function recalcProfileCompletion(){
    var $wrap = $('#profile-completion-wrap');
    if(!$wrap.length) return;
    var checks = [
      !!$('#name').val(), !!$('#email').val(), !!$('#phone').val(), !!$('#address').val(),
      !!$('#city').val(), !!$('#postal_code').val(),
      !!$('#school_name_id').val() || !!$('#school_name').val(), !!$('#grade').val(),
      !!$('#dob').val(), !!$('#father_name').val(), !!$('#mother_name').val(),
      !!$('#bank_id').val(), !!$('#bank_account_number').val()
    ];
    var pct = Math.round((checks.filter(Boolean).length/checks.length)*100);
    $('#profile-completion-value').text(pct+'%');
    $('#profile-completion-bar').css('width', pct+'%');
  }

  function calculateAge(){
    var dob = $('#dob').val();
    if(!dob){ $('#calculated-age').val(''); return null; }
    var today = new Date(), birthDate = new Date(dob);
    var age = today.getFullYear() - birthDate.getFullYear();
    var m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
    $('#calculated-age').val(age + ' years');
    if (age < 18 && age > 0) {
      $('#age-display').text(age + ' years old');
      $('#ageWarningModal').modal('show');
    }
    return age;
  }

  function refreshSelectpicker($el){
    if ($.fn.selectpicker) { $el.selectpicker('refresh'); }
  }

  // School name: keep hidden text in sync (if you prefer to submit name)
  function syncSchoolNameHidden(){
    var $opt = $('#school_name_id option:selected');
    var name = $opt.data('name') || $opt.text() || '';
    $('#school_name').val($.trim(name));
  }
  $('#school_name_id').on('changed.bs.select change', syncSchoolNameHidden);

  // Add Country -> either mark for creation on save (hidden) or call AJAX endpoint if you have it
  window.addCountry = function(){
    var name  = $.trim($('#modal_country_name').val());
    var phone = $.trim($('#modal_country_phone').val());
    if(!name || !phone){ alert('Country name and phone code are required'); return false; }

    // If you have an AJAX endpoint like university flow, call it here.
    // Otherwise, set hidden for creation on save through controller/model.
    $('#new_country_name').val(name);
    $('#new_country_phone_code').val(phone);

    // inject temp option now
    var $sel = $('#country_id');
    var tmpId = '__NEW_COUNTRY__';
    $sel.find('option[value="'+tmpId+'"]').remove();
    $sel.append($('<option/>',{value:tmpId, text: name+' ('+phone+')', selected:true, 'data-phone-code': phone}));
    refreshSelectpicker($sel);
    $('#phone-code-display').text(phone);

    $('#addCountryModal').modal('hide');
    $('#modal_country_name').val(''); $('#modal_country_phone').val('');
    alert_float('success','Country will be added on Save');
    recalcProfileCompletion();
    return false;
  };

  // Add School (instant via AJAX)
  window.addSchool = function(){
    var $btn = $('#btnAddSchool').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Adding...');
    var name = $.trim($('#modal_school_name').val());
    if(!name){ alert('Enter school name'); $btn.prop('disabled', false).text('Add School'); return false; }

    $.ajax({
      url: '<?php echo admin_url("student_sponsor_portal/add_school_name"); ?>',
      type: 'POST', dataType: 'json',
      data: { name: name }
    }).done(function(r){
      if(r && r.success){
        var $sel = $('#school_name_id');
        var id   = r.id || r.school_name_id || '';
        var txt  = r.name || name;
        if(id){
          $sel.find('option[value="'+id+'"]').remove();
          $sel.append($('<option/>',{value:id, text:txt, selected:true, 'data-name': txt}));
        } else {
          // if endpoint returns only name, fallback to name via hidden input
          $('#school_name').val(txt);
        }
        refreshSelectpicker($sel);
        syncSchoolNameHidden();
        $('#addSchoolModal').modal('hide'); $('#modal_school_name').val('');
        alert_float('success', r.message || 'School added');
      } else {
        alert(r && r.message ? r.message : 'Failed to add school');
      }
    }).fail(function(){ alert('Error adding school.'); })
      .always(function(){ $btn.prop('disabled', false).text('Add School'); });

    return false;
  };

  // Add Bank (instant via AJAX)
  window.addBank = function(){
    var $btn = $('#btnAddBank').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Adding...');
    var name = $.trim($('#modal_bank_name').val());
    if(!name){ alert('Enter bank name'); $btn.prop('disabled', false).text('Add Bank'); return false; }

    $.ajax({
      url: '<?php echo admin_url("student_sponsor_portal/add_bank"); ?>',
      type: 'POST', dataType: 'json',
      data: { name: name }
    }).done(function(r){
      if(r && r.success){
        var $sel = $('#bank_id');
        var id   = r.id || r.bank_id || '';
        var txt  = r.name || r.bank_name || name;
        if(id){
          $sel.find('option[value="'+id+'"]').remove();
          $sel.append($('<option/>',{value:id, text:txt, selected:true}));
          refreshSelectpicker($sel);
        }
        $('#addBankModal').modal('hide'); $('#modal_bank_name').val('');
        alert_float('success', r.message || 'Bank added');
        recalcProfileCompletion();
      } else {
        alert(r && r.message ? r.message : 'Failed to add bank');
      }
    }).fail(function(){ alert('Error adding bank.'); })
      .always(function(){ $btn.prop('disabled', false).text('Add Bank'); });

    return false;
  };

  // Report cards
  function loadReportCards(){
    var sid = <?php echo !empty($student['id']) ? (int)$student['id'] : 0; ?>;
    if(!sid) return;
    $.get('<?php echo admin_url("student_sponsor_portal/get_school_report_cards"); ?>/'+sid, function(resp){
      try{ resp = (typeof resp==='string') ? JSON.parse(resp) : resp; }catch(e){ resp = {success:false}; }
      var $tb = $('#tbl-report-cards tbody').empty();
      if(resp && resp.success && resp.report_cards && resp.report_cards.length){
        var i=1;
        resp.report_cards.forEach(function(c){
          var size = c.file_size ? (c.file_size + ' bytes') : '';
          var dl   = c.download_url || '<?php echo admin_url("student_sponsor_portal/download_school_report_card/"); ?>'+c.id;
          var tr = $('<tr/>',{'data-id':c.id}).append(
            $('<td/>',{text:i++}),
            $('<td/>').append($('<a/>',{href:dl, target:'_blank', rel:'noopener', text:(c.filename||('report_card_'+c.id)) })),
            $('<td/>',{text:c.term}),
            $('<td/>',{text:c.upload_date||''}),
            $('<td/>',{text:size}),
            $('<td/>').append(
              $('<a/>',{href:dl, class:'btn btn-xs btn-default', html:'<i class="fa fa-download"></i>'}), ' ',
              $('<button/>',{type:'button', class:'btn btn-xs btn-danger btn-del-card', html:'<i class="fa fa-trash"></i>'})
            )
          );
          $tb.append(tr);
        });
      } else {
        $tb.append('<tr><td colspan="6" class="text-center">No report cards uploaded yet.</td></tr>');
      }
    }).fail(function(){
      $('#tbl-report-cards tbody').html('<tr><td colspan="6" class="text-center text-danger">Error loading report cards</td></tr>');
    });
  }

  $(function(){
    // init selectpicker like UNI
    setTimeout(function(){ if($.fn && $.fn.selectpicker){ $('.selectpicker').selectpicker('destroy').selectpicker(); } }, 100);

    // phone code update on country change
    $('#country_id').on('change', function(){
      var code = $(this).find('option:selected').data('phone-code') || '+1';
      $('#phone-code-display').text(code);
      recalcProfileCompletion();
    });

    // profile photo preview
    $('#profile_photo').on('change', function(){
      var f = this.files && this.files[0]; if(!f) return;
      var reader = new FileReader();
      reader.onload = function(e){ $('.current-photo img').attr('src', e.target.result); };
      reader.readAsDataURL(f);
    });

    // keep hidden school_name synced
    syncSchoolNameHidden();

    // age + completion
    $('#dob').on('change input', function(){ calculateAge(); recalcProfileCompletion(); });
    setTimeout(calculateAge, 100);
    $(document).on('input change', '#school-student-form input, #school-student-form select, #school-student-form textarea', function(){
      $(this).removeClass('has-error'); recalcProfileCompletion();
    });

    // report cards
    <?php if(isset($student) && !empty($student['id'])): ?> loadReportCards(); <?php endif; ?>
    $('#btn-upload-card').on('click', function(){
      var $btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Uploading...');
      var form = document.getElementById('form-upload-report');
      var fd = new FormData(form);
      // CSRF
      if (typeof csrfData !== 'undefined' && csrfData && csrfData.token_name && csrfData.hash) {
        fd.append(csrfData.token_name, csrfData.hash);
      }
      $.ajax({
        url: '<?php echo admin_url("student_sponsor_portal/upload_school_report_card"); ?>',
        type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json'
      }).done(function(r){
        if(r && r.success){
          alert_float('success', r.message || 'Uploaded');
          form.reset();
          loadReportCards();
        } else {
          alert('Upload failed: '+(r && r.message ? r.message : 'Unknown error'));
        }
      }).fail(function(){ alert('Error uploading'); })
        .always(function(){ $btn.prop('disabled', false).html('<i class="fa fa-cloud-upload"></i> Upload'); });
    });

    // delete card
    $(document).on('click', '.btn-del-card', function(){
      var $tr = $(this).closest('tr'); var id = $tr.data('id'); if(!id) return;
      if(!confirm('Delete this report card?')) return;
      $.post('<?php echo admin_url("student_sponsor_portal/delete_school_report_card"); ?>/'+id, {}, function(r){
        try{ r = (typeof r==='string')?JSON.parse(r):r; }catch(e){ r={success:false}; }
        if(r && r.success){ $tr.remove(); alert_float('success','Deleted'); }
        else { alert('Delete failed'); }
      });
    });

    // submit guard
    var submitting = false;
    $('#school-student-form').on('submit', function(e){
      if(submitting){ e.preventDefault(); return false; }
      var isValid = true;
      $(this).find('input[required], select[required], textarea[required]').each(function(){
        if(!$(this).val()){
          isValid = false; $(this).addClass('has-error');
          var $pane = $(this).closest('.tab-pane'); if($pane.length){ $('a[href="#'+$pane.attr('id')+'"]').tab('show'); }
        } else { $(this).removeClass('has-error'); }
      });
      submitting = true;
      $('#btn-save-student').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
    });
  });

})(jQuery);
</script>