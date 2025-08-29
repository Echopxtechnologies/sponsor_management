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
                  <i class="fa fa-university"></i>
                  <?php echo isset($student) ? 'Edit University Student' : 'Register University Student'; ?>
                </h4>
              </div>
              <div class="col-md-4 text-right">
                <a href="<?php echo admin_url('student_sponsor_portal/university_students'); ?>" class="btn btn-default">
                  <i class="fa fa-arrow-left"></i> Back to List
                </a>
              </div>
            </div>
            <hr class="hr-panel-heading">

            <?php
              echo form_open_multipart(
                admin_url('student_sponsor_portal/university_student_form'),
                ['id' => 'university-student-form', 'novalidate' => 'novalidate', 'autocomplete'=>'off']
              );
            ?>
            <?php if(isset($student)): ?>
              <input type="hidden" name="student_id" value="<?php echo (int)$student['id']; ?>">
            <?php endif; ?>

            <!-- hidden fields that carry "new" items to server on Save -->
            <input type="hidden" name="new_country_name" id="new_country_name">
            <input type="hidden" name="new_country_phone_code" id="new_country_phone_code">
            <input type="hidden" name="new_university_name" id="new_university_name">
            <input type="hidden" name="new_program_name" id="new_program_name">
            <input type="hidden" name="new_bank_name" id="new_bank_name">

            <?php if(isset($student) && !empty($student)): ?>
              <?php
              $all_fields = ['name','email','contact_no','address','city','zip','university_name_id','university_program_id','university_year_of_study','university_student_dob','university_father_name','university_mother_name','bank_id','university_bank_account_no'];
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
                        value="<?php echo isset($student) ? htmlspecialchars($student['name']) : ''; ?>" placeholder="Enter student's full name">
                    </div>

                    <div class="form-group">
                      <label for="email" class="control-label">Email Address</label>
                      <input type="email" name="email" id="email" class="form-control"
                        value="<?php echo isset($student) ? htmlspecialchars($student['email']) : ''; ?>" placeholder="Enter email address">
                    </div>

                    <div class="form-group">
                      <label for="phone" class="control-label">Phone Number</label>
                      <div class="input-group">
                        <div class="input-group-addon" id="phone-code-display">
                          <?php
                          $phone_code = '+1';
                          if(isset($student) && !empty($student['country_id']) && isset($countries)) {
                            foreach($countries as $country) {
                              if($country['id'] == $student['country_id']) { $phone_code = $country['phone_code']; break; }
                            }
                          }
                          echo htmlspecialchars($phone_code);
                          ?>
                        </div>
                        <input type="text" name="phone" id="phone" class="form-control"
                          value="<?php echo isset($student) ? htmlspecialchars($student['contact_no']) : ''; ?>" placeholder="Enter phone number">
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="dob" class="control-label">Date of Birth</label>
                      <input type="date" name="dob" id="dob" class="form-control"
                        value="<?php echo isset($student) ? ($student['university_student_dob'] ?? '') : ''; ?>">
                    </div>

                    <div class="form-group">
                      <label class="control-label">Age</label>
                      <input type="text" id="calculated-age" class="form-control" readonly
                        value="<?php echo isset($student) ? ($student['university_age'] ?? '') : ''; ?>" placeholder="Will be calculated from DOB">
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
                    <div class="form-group">
                      <label for="country_id" class="control-label">Country</label>
                      <div class="country-select-wrapper">
                        <select name="country_id" id="country_id" class="form-control selectpicker" data-live-search="true" data-none-selected-text="Select Country">
                          <option value="">Select Country</option>
                          <?php if(isset($countries)): foreach($countries as $country): ?>
                            <option value="<?php echo $country['id']; ?>"
                                    data-phone-code="<?php echo htmlspecialchars($country['phone_code']); ?>"
                                    <?php echo (isset($student) && $student['country_id'] == $country['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($country['name']); ?> (<?php echo htmlspecialchars($country['phone_code']); ?>)
                            </option>
                          <?php endforeach; endif; ?>
                        </select>
                        <!-- Add Country button -->
                        <button type="button" class="btn btn-default btn-sm add-new-btn" data-toggle="modal" data-target="#addCountryModal" title="Add New Country">
                          <i class="fa fa-plus"></i>
                        </button>
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="address" class="control-label">Address</label>
                      <textarea name="address" id="address" class="form-control" rows="3" placeholder="Complete address"><?php echo isset($student) ? htmlspecialchars($student['address'] ?? '') : ''; ?></textarea>
                    </div>

                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group">
                          <label for="city" class="control-label">City</label>
                          <input type="text" name="city" id="city" class="form-control" value="<?php echo isset($student) ? htmlspecialchars($student['city'] ?? '') : ''; ?>" placeholder="City">
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <label for="postal_code" class="control-label">Postal Code</label>
                          <input type="text" name="postal_code" id="postal_code" class="form-control" value="<?php echo isset($student) ? htmlspecialchars($student['zip'] ?? '') : ''; ?>" placeholder="Postal code">
                        </div>
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="university_id" class="control-label">University Student ID</label>
                      <input type="text" name="university_id" id="university_id" class="form-control"
                        value="<?php echo isset($student) ? htmlspecialchars($student['university_id'] ?? '') : ''; ?>" placeholder="Enter university student ID">
                    </div>
                  </div>
                </div>

                <h5><i class="fa fa-graduation-cap"></i> Academic Information</h5>
                <hr>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="university_name_id" class="control-label">University Name</label>
                      <div class="university-select-wrapper">
                        <select name="university_name_id" id="university_name_id" class="form-control selectpicker" data-live-search="true" data-none-selected-text="Select University">
                          <option value="">Select University</option>
                          <?php if(isset($universities)): foreach($universities as $university): ?>
                            <option value="<?php echo $university['id']; ?>" <?php echo (isset($student) && $student['university_name_id'] == $university['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($university['name']); ?>
                            </option>
                          <?php endforeach; endif; ?>
                        </select>
                        <button type="button" class="btn btn-default btn-sm add-new-btn" data-toggle="modal" data-target="#addUniversityModal" title="Add New University">
                          <i class="fa fa-plus"></i>
                        </button>
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="university_program_id" class="control-label">Program / Degree</label>
                      <div class="program-select-wrapper">
                        <select name="university_program_id" id="university_program_id" class="form-control selectpicker" data-live-search="true" data-none-selected-text="Select Program">
                          <option value="">Select Program</option>
                          <?php if(isset($programs)): foreach($programs as $program): ?>
                            <option value="<?php echo $program['id']; ?>" <?php echo (isset($student) && $student['university_program_id'] == $program['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($program['name']); ?>
                            </option>
                          <?php endforeach; endif; ?>
                        </select>
                        <button type="button" class="btn btn-default btn-sm add-new-btn" data-toggle="modal" data-target="#addProgramModal" title="Add New Program">
                          <i class="fa fa-plus"></i>
                        </button>
                      </div>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="year_of_study" class="control-label">Year of Study</label>
                      <select name="year_of_study" id="year_of_study" class="form-control">
                        <option value="">Select year and semester</option>
                        <?php
                        $year_options = [
                          '1Y1S'=>'1st Year, 1st Semester','1Y2S'=>'1st Year, 2nd Semester',
                          '2Y1S'=>'2nd Year, 1st Semester','2Y2S'=>'2nd Year, 2nd Semester',
                          '3Y1S'=>'3rd Year, 1st Semester','3Y2S'=>'3rd Year, 2nd Semester',
                          '4Y1S'=>'4th Year, 1st Semester','4Y2S'=>'4th Year, 2nd Semester',
                          '5Y1S'=>'5th Year, 1st Semester','5Y2S'=>'5th Year, 2nd Semester'
                        ];
                        $current_year = isset($student) ? ($student['university_year_of_study'] ?? '') : '';
                        foreach($year_options as $value => $label): ?>
                          <option value="<?php echo $value; ?>" <?php echo ($current_year == $value) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>

                <h5><i class="fa fa-users"></i> Family Information</h5>
                <hr>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="father_name" class="control-label">Father's Name</label>
                      <input type="text" name="father_name" id="father_name" class="form-control" value="<?php echo isset($student) ? htmlspecialchars($student['university_father_name'] ?? '') : ''; ?>" placeholder="Father's full name">
                    </div>
                    <div class="form-group">
                      <label for="father_income" class="control-label">Father's Income</label>
                      <input type="number" name="father_income" id="father_income" class="form-control" step="0.01" value="<?php echo isset($student) ? ($student['university_father_income'] ?? '') : ''; ?>" placeholder="Monthly income">
                    </div>

                    <div class="form-group">
                      <label for="mother_name" class="control-label">Mother's Name</label>
                      <input type="text" name="mother_name" id="mother_name" class="form-control" value="<?php echo isset($student) ? htmlspecialchars($student['university_mother_name'] ?? '') : ''; ?>" placeholder="Mother's full name">
                    </div>
                    <div class="form-group">
                      <label for="mother_income" class="control-label">Mother's Income</label>
                      <input type="number" name="mother_income" id="mother_income" class="form-control" step="0.01" value="<?php echo isset($student) ? ($student['university_mother_income'] ?? '') : ''; ?>" placeholder="Monthly income">
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="guardian_name" class="control-label">Guardian's Name</label>
                      <input type="text" name="guardian_name" id="guardian_name" class="form-control" value="<?php echo isset($student) ? htmlspecialchars($student['university_guardian_name'] ?? '') : ''; ?>" placeholder="Guardian's full name (if different from parents)">
                    </div>
                    <div class="form-group">
                      <label for="guardian_income" class="control-label">Guardian's Income</label>
                      <input type="number" name="guardian_income" id="guardian_income" class="form-control" step="0.01" value="<?php echo isset($student) ? ($student['university_guardian_income'] ?? '') : ''; ?>" placeholder="Monthly income">
                    </div>

                    <div class="alert alert-info" style="margin-top:30px;">
                      <i class="fa fa-info-circle"></i>
                      <strong>Note:</strong> Guardian information is only required if different from parents or if parents are unavailable.
                    </div>
                  </div>
                </div>
              </div>

              <!-- Sponsorship -->
              <div role="tabpanel" class="tab-pane" id="sponsorship">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="sponsor_id" class="control-label">Main Sponsor</label>
                      <select name="sponsor_id" id="sponsor_id" class="form-control">
                        <option value="">Select Sponsor</option>
                        <?php if(isset($sponsors)): foreach($sponsors as $sponsor): ?>
                          <option value="<?php echo $sponsor['id']; ?>" <?php echo (isset($student) && $student['sponsor_id'] == $sponsor['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sponsor['name']); ?>
                          </option>
                        <?php endforeach; endif; ?>
                      </select>
                    </div>

                    <div class="form-group">
                      <label for="sponsorship_start" class="control-label">Sponsorship Start Date</label>
                      <input type="date" name="sponsorship_start" id="sponsorship_start" class="form-control" value="<?php echo isset($student) ? ($student['university_sponsorship_start_date'] ?? '') : ''; ?>">
                    </div>
                    <div class="form-group">
                      <label for="sponsorship_end" class="control-label">Sponsorship End Date</label>
                      <input type="date" name="sponsorship_end" id="sponsorship_end" class="form-control" value="<?php echo isset($student) ? ($student['university_sponsorship_end_date'] ?? '') : ''; ?>">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="introduced_by" class="control-label">Introduced By</label>
                      <input type="text" name="introduced_by" id="introduced_by" class="form-control" value="<?php echo isset($student) ? htmlspecialchars($student['university_introducedby'] ?? '') : ''; ?>" placeholder="Person who introduced the student">
                    </div>
                    <div class="form-group">
                      <label for="introduced_phone" class="control-label">Introducer's Phone</label>
                      <input type="text" name="introduced_phone" id="introduced_phone" class="form-control" value="<?php echo isset($student) ? htmlspecialchars($student['university_introducedph'] ?? '') : ''; ?>" placeholder="Contact number">
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
                          <?php if(isset($banks)): foreach($banks as $bank): ?>
                            <option value="<?php echo $bank['id']; ?>" <?php echo (isset($student) && $student['bank_id'] == $bank['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($bank['name']); ?>
                            </option>
                          <?php endforeach; endif; ?>
                        </select>
                        <button type="button" class="btn btn-default btn-sm add-new-btn" data-toggle="modal" data-target="#addBankModal" title="Add New Bank">
                          <i class="fa fa-plus"></i>
                        </button>
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="bank_branch_number" class="control-label">Bank Branch Number</label>
                      <input type="text" name="bank_branch_number" id="bank_branch_number" class="form-control" value="<?php echo isset($student) ? htmlspecialchars($student['university_bank_branch_number'] ?? '') : ''; ?>" placeholder="Branch code/number">
                    </div>

                    <div class="form-group">
                      <label for="bank_account_number" class="control-label">Bank Account Number</label>
                      <input type="text" name="bank_account_number" id="bank_account_number" class="form-control" value="<?php echo isset($student) ? htmlspecialchars($student['university_bank_account_no'] ?? '') : ''; ?>" placeholder="Account number">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="bank_branch_info" class="control-label">Bank Branch Information</label>
                      <textarea name="bank_branch_info" id="bank_branch_info" class="form-control" rows="5" placeholder="Additional branch details, address, etc."><?php echo isset($student) ? htmlspecialchars($student['university_bank_branch_info'] ?? '') : ''; ?></textarea>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Additional Info -->
              <div role="tabpanel" class="tab-pane" id="additional-info">
                <div class="row">
                  <div class="col-md-12">
                    <div class="form-group">
                      <label for="background_information" class="control-label">Background Information</label>
                      <textarea name="background_information" id="background_information" class="form-control" rows="4" placeholder="Student background, family situation, etc."><?php echo isset($student) ? htmlspecialchars($student['background_info'] ?? '') : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                      <label for="internal_comment" class="control-label">Internal Comment</label>
                      <textarea name="internal_comment" id="internal_comment" class="form-control" rows="3" placeholder="Internal notes (not visible to sponsors)"><?php echo isset($student) ? htmlspecialchars($student['internal_comment'] ?? '') : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                      <label for="external_comment" class="control-label">External Comment</label>
                      <textarea name="external_comment" id="external_comment" class="form-control" rows="3" placeholder="Comments visible to sponsors"><?php echo isset($student) ? htmlspecialchars($student['external_comment'] ?? '') : ''; ?></textarea>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Report Cards -->
              <div role="tabpanel" class="tab-pane" id="report-cards">
                <?php if(isset($student)): ?>
                <div class="row">
                  <div class="col-md-12">
                    <h5><i class="fa fa-upload"></i> Upload Report Card</h5>
                    <hr>
                    <form id="reportCardUploadForm" enctype="multipart/form-data">
                      <input type="hidden" name="student_university_id" value="<?php echo $student['id']; ?>">
                      <div class="row">
                        <div class="col-md-4">
                          <div class="form-group">
                            <label for="report_card_term">Term</label>
                            <select name="report_card_term" id="report_card_term" class="form-control">
                              <option value="">Select Term</option>
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
                        <div class="col-md-4">
                          <div class="form-group">
                            <label for="semester_end_month">Semester End Month</label>
                            <select name="semester_end_month" id="semester_end_month" class="form-control">
                              <option value="">Select Month</option>
                              <?php for($m=1;$m<=12;$m++): ?>
                                <option value="<?php echo $m; ?>"><?php echo date('F', mktime(0,0,0,$m,10)); ?></option>
                              <?php endfor; ?>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="form-group">
                            <label for="semester_end_year">Semester End Year</label>
                            <input type="number" name="semester_end_year" id="semester_end_year" class="form-control" min="2020" max="2035" value="<?php echo date('Y'); ?>">
                          </div>
                        </div>
                      </div>

                      <!-- New: Optional display filename -->
                      <div class="row">
                        <div class="col-md-12">
                          <div class="form-group">
                            <label for="display_filename">File Name (optional)</label>
                            <input type="text" name="display_filename" id="display_filename" class="form-control" placeholder="e.g. 2025_Sem1_Report.pdf">
                            <small class="text-muted">If left blank, the original upload name will be used.</small>
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-12">
                          <div class="form-group">
                            <label for="report_card_file">Report Card File (PDF, Image)</label>
                            <input type="file" name="report_card_file" id="report_card_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                          </div>
                        </div>
                      </div>
                      <div class="form-group">
                        <button type="button" class="btn btn-primary" id="uploadReportCardBtn" onclick="return uploadReportCard();">
                          <i class="fa fa-upload"></i> Upload Report Card
                        </button>
                      </div>
                    </form>
                  </div>
                </div>

                <div class="row" style="margin-top: 30px;">
                  <div class="col-md-12">
                    <h5><i class="fa fa-files-o"></i> Existing Report Cards</h5>
                    <hr>
                    <div class="table-responsive">
                      <table class="table table-striped" id="reportCardsTable">
                        <thead>
                          <tr>
                            <th>Term</th>
                            <th>End Month/Year</th>
                            <th>File Name</th>
                            <th>Upload Date</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($student) && !empty($student['id'])): ?>
                              <?php
                                $CI =& get_instance();
                                $tbl = db_prefix().'university_report_card';
                                // don’t fetch BLOBs
                                $CI->db->select('id, upload_date, report_card_term, current_term, semester_end_month, semester_end_year, filename, report_card_file, mime_type');
                                $CI->db->where('student_university_id', (int)$student['id']);
                                $CI->db->order_by('upload_date', 'DESC');
                                $cards = $CI->db->get($tbl)->result_array();

                                if ($cards):
                                  foreach ($cards as $card):
                                    // display name
                                    $name = trim((string)$card['filename']);
                                    if ($name === '' && !empty($card['report_card_file'])) $name = basename($card['report_card_file']);
                                    if ($name === '') {
                                      $ext = ($card['mime_type']==='application/pdf') ? '.pdf' : (in_array($card['mime_type'],['image/jpeg','image/jpg']) ? '.jpg' : ($card['mime_type']==='image/png' ? '.png' : ''));
                                      $name = 'report_card_'.$card['id'].$ext;
                                    }
                                    $download = admin_url('student_sponsor_portal/download_report_card/'.$card['id']);
                                    $monthTxt = $card['semester_end_month'] ? date('M', mktime(0,0,0,(int)$card['semester_end_month'],10)) : '';
                                    $uploadTxt = !empty($card['upload_date']) ? date('M d, Y', strtotime($card['upload_date'])) : '';
                              ?>
                                <tr>
                                  <td><?= htmlspecialchars($card['report_card_term'] ?: 'N/A'); ?></td>
                                  <td><?= htmlspecialchars($monthTxt.' '.($card['semester_end_year'] ?: '')); ?></td>
                                  <td><a href="<?= $download; ?>" target="_blank" rel="noopener"><?= htmlspecialchars($name); ?></a></td>
                                  <td><?= htmlspecialchars($uploadTxt); ?></td>
                                  <td>
                                    <button type="button" class="btn btn-danger btn-xs" onclick="return deleteReportCard(<?= (int)$card['id']; ?>);">
                                      <i class="fa fa-trash"></i>
                                    </button>
                                  </td>
                                </tr>
                              <?php endforeach; else: ?>
                                <tr><td colspan="5" class="text-center">No report cards uploaded yet.</td></tr>
                              <?php endif; ?>
                            <?php else: ?>
                              <tr><td colspan="5" class="text-center">No report cards uploaded yet.</td></tr>
                            <?php endif; ?>
                        </tbody>

                      </table>
                    </div>
                  </div>
                </div>
                <?php else: ?>
                <div class="alert alert-info"><i class="fa fa-info-circle"></i> Please save the student information first before uploading report cards.</div>
                <?php endif; ?>
              </div>

              <!-- Staff Account Tab -->
              <div class="tab-pane" id="tab_staff">
                <div class="checkbox checkbox-primary">
                  <input type="checkbox" id="create_staff" name="create_staff" value="1" <?= !empty($student['staff_id']) ? 'checked' : ''; ?>>
                  <label for="create_staff">Create a Staff login</label>
                </div>
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="staff_email" class="control-label">Login Email</label>
                      <input type="email" name="staff_email" id="staff_email" class="form-control" value="<?= isset($student) ? htmlspecialchars($student['email'] ?? '') : ''; ?>">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="staff_firstname" class="control-label">First Name</label>
                      <input type="text" name="staff_firstname" id="staff_firstname" class="form-control" value="<?= isset($student) ? htmlspecialchars($student['name'] ?? '') : ''; ?>">
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
                      <input type="checkbox" id="staff_active" name="staff_active" value="1" checked>
                      <label for="staff_active">Active Login</label>
                    </div>
                  </div>
                </div>

                <?php if (!empty($student['id'])): ?>
                  <button type="submit" class="btn btn-success" formaction="<?= admin_url('student_sponsor_portal/grant_university_access'); ?>" formmethod="post">Grant Portal Access</button>
                  <button type="submit" class="btn btn-danger" formaction="<?= admin_url('student_sponsor_portal/revoke_university_access'); ?>" formmethod="post">Revoke Portal Access</button>
                <?php endif; ?>
              </div>

            </div>

            <button type="submit" id="btn-save-student" class="btn btn-primary btn-lg" form="university-student-form">
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
          <small class="text-muted">Note: This will be created when you save the form.</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="btnAddCountry" onclick="return addCountry();">Add Country</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Add University Modal -->
  <div class="modal fade" id="addUniversityModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Add New University</h4>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="modal_university_name">University Name *</label>
            <input type="text" id="modal_university_name" class="form-control" required placeholder="Enter university name">
          </div>
          <small class="text-muted">Note: This will be created instantly.</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="btnAddUniversity" onclick="return addUniversity();">Add University</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Program Modal -->
  <div class="modal fade" id="addProgramModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Add New Program</h4>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="modal_program_name">Program Name *</label>
            <input type="text" id="modal_program_name" class="form-control" required placeholder="Enter program name">
          </div>
          <small class="text-muted">Note: This will be created instantly.</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="btnAddProgram" onclick="return addProgram();">Add Program</button>
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
          <small class="text-muted">Note: This will be created instantly.</small>
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
          <p>Please ensure you have proper guardian consent and documentation for students under 18.</p>
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
.university-select-wrapper,.program-select-wrapper,.bank-select-wrapper,.country-select-wrapper{position:relative;display:flex;align-items:stretch}
.university-select-wrapper .bootstrap-select,.program-select-wrapper .bootstrap-select,.bank-select-wrapper .bootstrap-select,.country-select-wrapper .bootstrap-select{flex:1;margin-right:5px;width:auto!important}
.university-select-wrapper select,.program-select-wrapper select,.bank-select-wrapper select,.country-select-wrapper select{flex:1;border-top-right-radius:0;border-bottom-right-radius:0}
.add-new-btn{border-left:0;border-radius:0 4px 4px 0!important;padding:8px 12px;margin-left:0;z-index:5;border-top-left-radius:0!important;border-bottom-left-radius:0!important;flex-shrink:0;height:34px}
.add-new-btn:hover{background-color:#337ab7;color:#fff;border-color:#2e6da4}
.university-select-wrapper .btn-group.bootstrap-select,.program-select-wrapper .btn-group.bootstrap-select,.bank-select-wrapper .btn-group.bootstrap-select,.country-select-wrapper .btn-group.bootstrap-select{flex:1;width:auto!important;display:flex!important}
.university-select-wrapper .btn-group.bootstrap-select .btn,.program-select-wrapper .btn-group.bootstrap-select .btn,.bank-select-wrapper .btn-group.bootstrap-select .btn,.country-select-wrapper .btn-group.bootstrap-select .btn{width:100%;border-top-right-radius:0!important;border-bottom-right-radius:0!important;text-align:left}
.badge-unsaved{margin-left:6px;font-size:11px;background:#f0ad4e}
</style>

<?php init_tail(); ?>
<script>
/* Guard if Perfex alert_float not present */
if (typeof alert_float !== 'function') {
  window.alert_float = function(type, message){ alert(message); };
}

/* noConflict-safe wrapper but export functions on window */
(function($){

  // ---------- Helpers (internal) ----------
  function getMonthName(n){
    var months = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    n = parseInt(n,10);
    return months[n] || '';
  }

  function recalcProfileCompletion(){
    var $wrap = $('#profile-completion-wrap');
    if(!$wrap.length) return;
    var checks = [
      !!$('#name').val(), !!$('#email').val(), !!$('#phone').val(), !!$('#address').val(),
      !!$('#city').val(), !!$('#postal_code').val(),
      !!$('#university_name_id').val(), !!$('#university_program_id').val(), !!$('#year_of_study').val(),
      !!$('#dob').val(), !!$('#father_name').val(), !!$('#mother_name').val(),
      !!$('#bank_id').val(), !!$('#bank_account_number').val()
    ];
    var pct = Math.round((checks.filter(Boolean).length/checks.length)*100);
    $('#profile-completion-value').text(pct+'%');
    $('#profile-completion-bar').css('width', pct+'%');
  }

  function injectAndSelect($select, id, text, extraAttrs){
    var $opt = $('<option/>', {value: id, text: text});
    if(extraAttrs){ $.each(extraAttrs, function(k,v){ $opt.attr(k, v); }); }
    $select.find('option[value="'+id+'"]').remove();
    $select.append($opt);
    $select.val(id);
    if($select.hasClass('selectpicker') && typeof $select.selectpicker === 'function'){
      $select.selectpicker('refresh');
    }
    recalcProfileCompletion();
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

  // ---------- Public functions (called by inline onclick) ----------
  window.addCountry = function(){
    var name  = $.trim($('#modal_country_name').val());
    var phone = $.trim($('#modal_country_phone').val());
    if(!name || !phone){ alert('Country name and phone code are required'); return false; }

    // set hidden fields for model->process_new_items()
    $('#new_country_name').val(name);
    $('#new_country_phone_code').val(phone);

    // temporary option so UI selects it now
    injectAndSelect($('#country_id'), '__NEW_COUNTRY__', name+' ('+phone+')', {'data-phone-code': phone});
    $('#phone-code-display').text(phone);

    // close/clear
    $('#addCountryModal').modal('hide');
    $('#modal_country_name').val('');
    $('#modal_country_phone').val('');
    alert_float('success','Country will be created on Save');
    return false;
  };

  window.addUniversity = function(){
    var $btn = $('#btnAddUniversity').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Adding...');
    var name = $.trim($('#modal_university_name').val());
    if(!name){ alert('Enter university name'); $btn.prop('disabled', false).text('Add University'); return false; }

    $.ajax({
      url: '<?php echo admin_url("student_sponsor_portal/add_university_name"); ?>',
      type: 'POST',
      dataType: 'json',
      data: { name: name }
    }).done(function(r){
      if(r && r.success){
        injectAndSelect($('#university_name_id'), r.id, r.name);
        $('#new_university_name').val('');
        $('#addUniversityModal').modal('hide');
        $('#modal_university_name').val('');
        alert_float('success', r.message || 'University added');
      } else {
        alert(r && r.message ? r.message : 'Failed to add university');
      }
    }).fail(function(){
      alert('Error adding university.');
    }).always(function(){
      $btn.prop('disabled', false).text('Add University');
    });

    return false;
  };

  window.addProgram = function(){
    var $btn = $('#btnAddProgram').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Adding...');
    var name = $.trim($('#modal_program_name').val());
    if(!name){ alert('Enter program name'); $btn.prop('disabled', false).text('Add Program'); return false; }

    $.ajax({
      url: '<?php echo admin_url("student_sponsor_portal/add_university_program"); ?>',
      type: 'POST',
      dataType: 'json',
      data: { name: name }
    }).done(function(r){
      if(r && r.success){
        injectAndSelect($('#university_program_id'), r.id, r.name);
        $('#new_program_name').val('');
        $('#addProgramModal').modal('hide');
        $('#modal_program_name').val('');
        alert_float('success', r.message || 'Program added');
      } else {
        alert(r && r.message ? r.message : 'Failed to add program');
      }
    }).fail(function(){
      alert('Error adding program.');
    }).always(function(){
      $btn.prop('disabled', false).text('Add Program');
    });

    return false;
  };

  window.addBank = function(){
    var $btn = $('#btnAddBank').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Adding...');
    var name = $.trim($('#modal_bank_name').val());
    if(!name){ alert('Enter bank name'); $btn.prop('disabled', false).text('Add Bank'); return false; }

    $.ajax({
      url: '<?php echo admin_url("student_sponsor_portal/add_bank"); ?>',
      type: 'POST',
      dataType: 'json',
      data: { name: name }
    }).done(function(r){
      if(r && r.success){
        injectAndSelect($('#bank_id'), r.id, r.name);
        $('#new_bank_name').val('');
        $('#addBankModal').modal('hide');
        $('#modal_bank_name').val('');
        alert_float('success', r.message || 'Bank added');
      } else {
        alert(r && r.message ? r.message : 'Failed to add bank');
      }
    }).fail(function(){
      alert('Error adding bank.');
    }).always(function(){
      $btn.prop('disabled', false).text('Add Bank');
    });

    return false;
  };

  window.uploadReportCard = function(){
    var $btn = $('#uploadReportCardBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Uploading...');

    var fileInput = document.getElementById('report_card_file');
    if(!$('#report_card_term').val()){ alert('Please select a term'); $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Upload Report Card'); return false; }
    if(!$('#semester_end_month').val()){ alert('Please select semester end month'); $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Upload Report Card'); return false; }
    if(!$('#semester_end_year').val()){ alert('Please select semester end year'); $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Upload Report Card'); return false; }
    if(!fileInput || !fileInput.files || !fileInput.files[0]){ alert('Please select a file to upload'); $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Upload Report Card'); return false; }

    var formData = new FormData();
    formData.append('student_university_id', $('input[name="student_university_id"]').val());
    formData.append('report_card_term', $('#report_card_term').val());
    formData.append('semester_end_month', $('#semester_end_month').val());
    formData.append('semester_end_year', $('#semester_end_year').val());
    formData.append('display_filename', $('#display_filename').val()); // NEW
    formData.append('report_card_file', fileInput.files[0]);

    // Perfex CSRF (if available)
    if (typeof csrfData !== 'undefined' && csrfData && csrfData.token_name && csrfData.hash) {
      formData.append(csrfData.token_name, csrfData.hash);
    }

    $.ajax({
      url: '<?php echo admin_url("student_sponsor_portal/upload_report_card"); ?>',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json'
    }).done(function(response){
      if(response && response.success){
        alert_float('success','Report card uploaded successfully!');
        $('#report_card_term').val('');
        $('#semester_end_month').val('');
        $('#semester_end_year').val('<?php echo date('Y'); ?>');
        $('#display_filename').val(''); // NEW
        $('#report_card_file').val('');
        loadReportCards();
      } else {
        alert('Upload failed: ' + (response && response.message ? response.message : 'Unknown error'));
      }
    }).fail(function(){
      alert('Error uploading report card. Please try again.');
    }).always(function(){
      $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Upload Report Card');
    });

    return false;
  };

  window.deleteReportCard = function(id){
    if(!id) return false;
    if(!confirm('Are you sure you want to delete this report card?')) return false;

    $.post('<?php echo admin_url("student_sponsor_portal/delete_report_card"); ?>', {
      report_card_id: id
    }, function(response){
      if(response && response.success){
        alert_float('success','Report card deleted');
        loadReportCards();
      } else {
        alert('Delete failed: ' + (response && response.message ? response.message : 'Unknown error'));
      }
    }, 'json').fail(function(){
      alert('Error deleting report card');
    });

    return false;
  };

  // ---------- Page init / bindings ----------
  function loadReportCards(){
    <?php if(isset($student)): ?>
    $.post('<?php echo admin_url("student_sponsor_portal/get_report_cards"); ?>', {
      student_id: <?php echo (int)$student['id']; ?>
    }, function(data){
      var tbody = $('#reportCardsTable tbody');
      tbody.empty();
      if(data && data.success && data.report_cards && data.report_cards.length){
        $.each(data.report_cards, function(i, card){
          var fileUrl = card.file_url || '#';
          var ext = (card.mime_type === 'application/pdf') ? '.pdf'
                   : (card.mime_type === 'image/jpeg' || card.mime_type === 'image/jpg') ? '.jpg'
                   : (card.mime_type === 'image/png') ? '.png' : '';
          var niceName = (card.filename && String(card.filename).trim())
              ? card.filename
              : ('report_card_' + card.id + ext);

          tbody.append(
            '<tr>'
            +'<td>'+(card.report_card_term||'N/A')+'</td>'
            +'<td>'+getMonthName(card.semester_end_month)+' '+(card.semester_end_year||'')+'</td>'
            +'<td><a href="'+card.file_url+'" target="_blank" rel="noopener">'+(card.display_name||'file')+'</a></td>'
            +'<td>'+card.upload_date+'</td>'
            +'<td><button type="button" class="btn btn-danger btn-xs" onclick="return deleteReportCard('+card.id+');"><i class="fa fa-trash"></i></button></td>'
            +'</tr>'
          );
        });
      } else {
        tbody.append('<tr><td colspan="5" class="text-center">No report cards uploaded yet.</td></tr>');
      }
    }, 'json').fail(function(){
      $('#reportCardsTable tbody').html('<tr><td colspan="5" class="text-center text-danger">Error loading report cards</td></tr>');
    });
    <?php endif; ?>
  }

  $(function(){
    // selectpicker safe init
    setTimeout(function(){
      if($.fn && typeof $.fn.selectpicker === 'function'){
        $('.selectpicker').selectpicker('destroy').selectpicker();
      }
    }, 100);

    // prevent Enter inside modals from submitting main form
    $(document).on('keydown', '.modal input', function(e){
      if(e.key === 'Enter'){
        e.preventDefault();
        $(this).closest('.modal').find('.btn-primary').not('.close').trigger('click');
      }
    });

    // age calc & completion
    $('#dob').on('change input', function(){ calculateAge(); recalcProfileCompletion(); });
    setTimeout(calculateAge, 100);

    // phone code with country
    $('#country_id').on('change', function(){
      var code = $(this).find('option:selected').data('phone-code') || '+1';
      $('#phone-code-display').text(code);
      recalcProfileCompletion();
    });

    // live completion
    $(document).on('input change', '#university-student-form input, #university-student-form select, #university-student-form textarea', function(){
      $(this).removeClass('has-error');
      recalcProfileCompletion();
    });

    // load report cards (edit mode)
    <?php if(isset($student)): ?> setTimeout(loadReportCards, 400); <?php endif; ?>

    // basic submit guard/validation
    var submitting = false;
    $('#university-student-form').on('submit', function(e){
      if(submitting){ e.preventDefault(); return false; }
      var isValid = true;
      $(this).find('input[required], select[required], textarea[required]').each(function(){
        if(!$(this).val()){
          isValid = false;
          $(this).addClass('has-error');
          var $pane = $(this).closest('.tab-pane');
          if($pane.length){ $('a[href="#'+$pane.attr('id')+'"]').tab('show'); }
        } else {
          $(this).removeClass('has-error');
        }
      });
      // if(!isValid){
      //   e.preventDefault();
      //   alert('Please fill in all required fields.');
      //   return false;
      // }
      submitting = true;
      $('#btn-save-student').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
    });
  });

})(jQuery);
</script>
