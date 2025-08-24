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
              // IMPORTANT: explicit action + novalidate to avoid silent HTML5 blocks
              echo form_open_multipart(
                admin_url('student_sponsor_portal/university_student_form'),
                ['id' => 'university-student-form', 'novalidate' => 'novalidate']
              );
            ?>
            <?php if(isset($student)): ?>
              <input type="hidden" name="student_id" value="<?php echo (int)$student['id']; ?>">
            <?php endif; ?>

            <?php if(isset($student) && !empty($student)): ?>
              <?php
              $all_fields = ['name', 'email', 'contact_no', 'address', 'city', 'zip', 'university_name_id', 'university_program_id', 'university_year_of_study', 'university_student_dob', 'university_father_name', 'university_mother_name', 'bank_id', 'university_bank_account_no'];
              $completed = 0;
              foreach($all_fields as $field) {
                if (!empty($student[$field])) $completed++;
              }
              $completion = round(($completed / count($all_fields)) * 100);
              ?>
              <div class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                Profile Completion: <strong><?php echo $completion; ?>%</strong>
                <div class="progress" style="margin-top:5px;">
                  <div class="progress-bar" style="width:<?php echo $completion; ?>%"></div>
                </div>
              </div>
            <?php endif; ?>

            <!-- Simplified Tabs -->
            <ul class="nav nav-tabs" role="tablist">
              <li role="presentation" class="active">
                <a href="#student-info" aria-controls="student-info" role="tab" data-toggle="tab">
                  <i class="fa fa-user"></i> Student Info
                </a>
              </li>
              <li role="presentation">
                <a href="#sponsorship" aria-controls="sponsorship" role="tab" data-toggle="tab">
                  <i class="fa fa-heart"></i> Sponsorship
                </a>
              </li>
              <li role="presentation">
                <a href="#bank-info" aria-controls="bank-info" role="tab" data-toggle="tab">
                  <i class="fa fa-bank"></i> Bank Info
                </a>
              </li>
              <li role="presentation">
                <a href="#additional-info" aria-controls="additional-info" role="tab" data-toggle="tab">
                  <i class="fa fa-info-circle"></i> Additional Info
                </a>
              </li>
              <li role="presentation">
                <a href="#report-cards" aria-controls="report-cards" role="tab" data-toggle="tab">
                  <i class="fa fa-file-text"></i> Report Cards
                </a>
              </li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content" style="margin-top:20px;">

              <!-- Student Info Tab -->
              <div role="tabpanel" class="tab-pane active" id="student-info">
                <!-- Basic Information -->
                <h5><i class="fa fa-user"></i> Basic Information</h5>
                <hr>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="name" class="control-label">Full Name *</label>
                      <input type="text" name="name" id="name" required class="form-control"
                        value="<?php echo isset($student) ? htmlspecialchars($student['name']) : ''; ?>"
                        placeholder="Enter student's full name">
                    </div>

                    <div class="form-group">
                      <label for="email" class="control-label">Email Address</label>
                      <input type="email" name="email" id="email" class="form-control"
                        value="<?php echo isset($student) ? htmlspecialchars($student['email']) : ''; ?>"
                        placeholder="Enter email address">
                    </div>

                    <div class="form-group">
                      <label for="phone" class="control-label">Phone Number</label>
                      <div class="input-group">
                        <div class="input-group-addon" id="phone-code-display">
                          <?php
                          $phone_code = '+1';
                          if(isset($student) && !empty($student['country_id']) && isset($countries)) {
                            foreach($countries as $country) {
                              if($country['id'] == $student['country_id']) {
                                $phone_code = $country['phone_code'];
                                break;
                              }
                            }
                          }
                          echo htmlspecialchars($phone_code);
                          ?>
                        </div>
                        <input type="text" name="phone" id="phone" class="form-control"
                          value="<?php echo isset($student) ? htmlspecialchars($student['contact_no']) : ''; ?>"
                          placeholder="Enter phone number">
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
                        value="<?php echo isset($student) ? ($student['university_age'] ?? '') : ''; ?>"
                        placeholder="Will be calculated from DOB">
                    </div>

                    <div class="form-group">
                      <label for="profile_photo" class="control-label">Profile Photo</label>
                      <input type="file" name="profile_photo" id="profile_photo" class="form-control" accept="image/*">
                      <?php if(isset($student) && !empty($student['profile_photo'])): ?>
                        <small class="text-muted">Current: <?php echo basename($student['profile_photo']); ?></small>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="country_id" class="control-label">Country</label>
                      <div class="country-select-wrapper">
                        <select name="country_id" id="country_id" class="form-control selectpicker" data-live-search="true" data-none-selected-text="Select Country">
                          <option value="">Select Country</option>
                          <?php if(isset($countries)): ?>
                            <?php foreach($countries as $country): ?>
                              <option value="<?php echo $country['id']; ?>"
                                data-phone-code="<?php echo htmlspecialchars($country['phone_code']); ?>"
                                <?php echo (isset($student) && $student['country_id'] == $country['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($country['name']); ?> (<?php echo htmlspecialchars($country['phone_code']); ?>)
                              </option>
                            <?php endforeach; ?>
                          <?php endif; ?>
                        </select>
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="address" class="control-label">Address</label>
                      <textarea name="address" id="address" class="form-control" rows="3"
                        placeholder="Complete address"><?php echo isset($student) ? htmlspecialchars($student['address'] ?? '') : ''; ?></textarea>
                    </div>

                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group">
                          <label for="city" class="control-label">City</label>
                          <input type="text" name="city" id="city" class="form-control"
                            value="<?php echo isset($student) ? htmlspecialchars($student['city'] ?? '') : ''; ?>"
                            placeholder="City">
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <label for="postal_code" class="control-label">Postal Code</label>
                          <input type="text" name="postal_code" id="postal_code" class="form-control"
                            value="<?php echo isset($student) ? htmlspecialchars($student['zip'] ?? '') : ''; ?>"
                            placeholder="Postal code">
                        </div>
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="university_id" class="control-label">University Student ID</label>
                      <input type="text" name="university_id" id="university_id" class="form-control"
                        value="<?php echo isset($student) ? htmlspecialchars($student['university_id'] ?? '') : ''; ?>"
                        placeholder="Enter university student ID">
                    </div>
                  </div>
                </div>

                <!-- Academic Information -->
                <h5><i class="fa fa-graduation-cap"></i> Academic Information</h5>
                <hr>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="university_name_id" class="control-label">University Name</label>
                      <div class="university-select-wrapper">
                          <select name="university_name_id" id="university_name_id" class="form-control selectpicker"
                                  data-live-search="true" data-none-selected-text="Select University">
                              <option value="">Select University</option>
                              <?php if(isset($universities)): ?>
                                  <?php foreach($universities as $university): ?>
                                      <option value="<?php echo $university['id']; ?>"
                                          <?php echo (isset($student) && $student['university_name_id'] == $university['id']) ? 'selected' : ''; ?>>
                                          <?php echo htmlspecialchars($university['name']); ?>
                                      </option>
                                  <?php endforeach; ?>
                              <?php endif; ?>
                          </select>
                          <button type="button" class="btn btn-default btn-sm add-new-btn"
                                  data-toggle="modal" data-target="#addUniversityModal"
                                  title="Add New University">
                              <i class="fa fa-plus"></i>
                          </button>
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="university_program_id" class="control-label">Program / Degree</label>
                      <div class="program-select-wrapper">
                          <select name="university_program_id" id="university_program_id" class="form-control selectpicker"
                                  data-live-search="true" data-none-selected-text="Select Program">
                              <option value="">Select Program</option>
                              <?php if(isset($programs)): ?>
                                  <?php foreach($programs as $program): ?>
                                      <option value="<?php echo $program['id']; ?>"
                                          <?php echo (isset($student) && $student['university_program_id'] == $program['id']) ? 'selected' : ''; ?>>
                                          <?php echo htmlspecialchars($program['name']); ?>
                                      </option>
                                  <?php endforeach; ?>
                              <?php endif; ?>
                          </select>
                          <button type="button" class="btn btn-default btn-sm add-new-btn"
                                  data-toggle="modal" data-target="#addProgramModal"
                                  title="Add New Program">
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
                          '1Y1S' => '1st Year, 1st Semester',
                          '1Y2S' => '1st Year, 2nd Semester',
                          '2Y1S' => '2nd Year, 1st Semester',
                          '2Y2S' => '2nd Year, 2nd Semester',
                          '3Y1S' => '3rd Year, 1st Semester',
                          '3Y2S' => '3rd Year, 2nd Semester',
                          '4Y1S' => '4th Year, 1st Semester',
                          '4Y2S' => '4th Year, 2nd Semester',
                          '5Y1S' => '5th Year, 1st Semester',
                          '5Y2S' => '5th Year, 2nd Semester'
                        ];
                        $current_year = isset($student) ? ($student['university_year_of_study'] ?? '') : '';
                        foreach($year_options as $value => $label): ?>
                          <option value="<?php echo $value; ?>" <?php echo ($current_year == $value) ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>

                <!-- Family Information -->
                <h5><i class="fa fa-users"></i> Family Information</h5>
                <hr>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="father_name" class="control-label">Father's Name</label>
                      <input type="text" name="father_name" id="father_name" class="form-control"
                        value="<?php echo isset($student) ? htmlspecialchars($student['university_father_name'] ?? '') : ''; ?>"
                        placeholder="Father's full name">
                    </div>
                    <div class="form-group">
                      <label for="father_income" class="control-label">Father's Income</label>
                      <input type="number" name="father_income" id="father_income" class="form-control" step="0.01"
                        value="<?php echo isset($student) ? ($student['university_father_income'] ?? '') : ''; ?>"
                        placeholder="Monthly income">
                    </div>

                    <div class="form-group">
                      <label for="mother_name" class="control-label">Mother's Name</label>
                      <input type="text" name="mother_name" id="mother_name" class="form-control"
                        value="<?php echo isset($student) ? htmlspecialchars($student['university_mother_name'] ?? '') : ''; ?>"
                        placeholder="Mother's full name">
                    </div>
                    <div class="form-group">
                      <label for="mother_income" class="control-label">Mother's Income</label>
                      <input type="number" name="mother_income" id="mother_income" class="form-control" step="0.01"
                        value="<?php echo isset($student) ? ($student['university_mother_income'] ?? '') : ''; ?>"
                        placeholder="Monthly income">
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="guardian_name" class="control-label">Guardian's Name</label>
                      <input type="text" name="guardian_name" id="guardian_name" class="form-control"
                        value="<?php echo isset($student) ? htmlspecialchars($student['university_guardian_name'] ?? '') : ''; ?>"
                        placeholder="Guardian's full name (if different from parents)">
                    </div>
                    <div class="form-group">
                      <label for="guardian_income" class="control-label">Guardian's Income</label>
                      <input type="number" name="guardian_income" id="guardian_income" class="form-control" step="0.01"
                        value="<?php echo isset($student) ? ($student['university_guardian_income'] ?? '') : ''; ?>"
                        placeholder="Monthly income">
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
                        <?php if(isset($sponsors)): ?>
                          <?php foreach($sponsors as $sponsor): ?>
                            <option value="<?php echo $sponsor['id']; ?>"
                              <?php echo (isset($student) && $student['sponsor_id'] == $sponsor['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($sponsor['name']); ?>
                            </option>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </select>
                    </div>

                    <div class="form-group">
                      <label for="sponsorship_start" class="control-label">Sponsorship Start Date</label>
                      <input type="date" name="sponsorship_start" id="sponsorship_start" class="form-control"
                        value="<?php echo isset($student) ? ($student['university_sponsorship_start_date'] ?? '') : ''; ?>">
                    </div>
                    <div class="form-group">
                      <label for="sponsorship_end" class="control-label">Sponsorship End Date</label>
                      <input type="date" name="sponsorship_end" id="sponsorship_end" class="form-control"
                        value="<?php echo isset($student) ? ($student['university_sponsorship_end_date'] ?? '') : ''; ?>">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="introduced_by" class="control-label">Introduced By</label>
                      <input type="text" name="introduced_by" id="introduced_by" class="form-control"
                        value="<?php echo isset($student) ? htmlspecialchars($student['university_introducedby'] ?? '') : ''; ?>"
                        placeholder="Person who introduced the student">
                    </div>
                    <div class="form-group">
                      <label for="introduced_phone" class="control-label">Introducer's Phone</label>
                      <input type="text" name="introduced_phone" id="introduced_phone" class="form-control"
                        value="<?php echo isset($student) ? htmlspecialchars($student['university_introducedph'] ?? '') : ''; ?>"
                        placeholder="Contact number">
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
                          <select name="bank_id" id="bank_id" class="form-control selectpicker"
                                  data-live-search="true" data-none-selected-text="Select Bank">
                              <option value="">Select Bank</option>
                              <?php if(isset($banks)): ?>
                                  <?php foreach($banks as $bank): ?>
                                      <option value="<?php echo $bank['id']; ?>"
                                          <?php echo (isset($student) && $student['bank_id'] == $bank['id']) ? 'selected' : ''; ?>>
                                          <?php echo htmlspecialchars($bank['name']); ?>
                                      </option>
                                  <?php endforeach; ?>
                              <?php endif; ?>
                          </select>
                          <button type="button" class="btn btn-default btn-sm add-new-btn"
                                  data-toggle="modal" data-target="#addBankModal"
                                  title="Add New Bank">
                              <i class="fa fa-plus"></i>
                          </button>
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="bank_branch_number" class="control-label">Bank Branch Number</label>
                      <input type="text" name="bank_branch_number" id="bank_branch_number" class="form-control"
                        value="<?php echo isset($student) ? htmlspecialchars($student['university_bank_branch_number'] ?? '') : ''; ?>"
                        placeholder="Branch code/number">
                    </div>

                    <div class="form-group">
                      <label for="bank_account_number" class="control-label">Bank Account Number</label>
                      <input type="text" name="bank_account_number" id="bank_account_number" class="form-control"
                        value="<?php echo isset($student) ? htmlspecialchars($student['university_bank_account_no'] ?? '') : ''; ?>"
                        placeholder="Account number">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="bank_branch_info" class="control-label">Bank Branch Information</label>
                      <textarea name="bank_branch_info" id="bank_branch_info" class="form-control" rows="5"
                        placeholder="Additional branch details, address, etc."><?php echo isset($student) ? htmlspecialchars($student['university_bank_branch_info'] ?? '') : ''; ?></textarea>
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
                      <textarea name="background_information" id="background_information" class="form-control" rows="4"
                        placeholder="Student background, family situation, etc."><?php echo isset($student) ? htmlspecialchars($student['background_info'] ?? '') : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                      <label for="internal_comment" class="control-label">Internal Comment</label>
                      <textarea name="internal_comment" id="internal_comment" class="form-control" rows="3"
                        placeholder="Internal notes (not visible to sponsors)"><?php echo isset($student) ? htmlspecialchars($student['internal_comment'] ?? '') : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                      <label for="external_comment" class="control-label">External Comment</label>
                      <textarea name="external_comment" id="external_comment" class="form-control" rows="3"
                        placeholder="Comments visible to sponsors"><?php echo isset($student) ? htmlspecialchars($student['external_comment'] ?? '') : ''; ?></textarea>
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
                    <!-- NOTE: this is a div, not a <form>; we bind to the button click -->
                    <div id="reportCardUploadForm" enctype="multipart/form-data">
                      <input type="hidden" name="student_university_id" value="<?php echo $student['id']; ?>">
                      <div class="row">
                        <div class="col-md-4">
                          <div class="form-group">
                            <label for="report_card_term">Term</label>
                            <input type="text" name="report_card_term" id="report_card_term" class="form-control" placeholder="e.g., 1st Term, 2nd Term">
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="form-group">
                            <label for="semester_end_month">Semester End Month</label>
                            <select name="semester_end_month" id="semester_end_month" class="form-control">
                              <option value="">Select Month</option>
                              <option value="1">January</option>
                              <option value="2">February</option>
                              <option value="3">March</option>
                              <option value="4">April</option>
                              <option value="5">May</option>
                              <option value="6">June</option>
                              <option value="7">July</option>
                              <option value="8">August</option>
                              <option value="9">September</option>
                              <option value="10">October</option>
                              <option value="11">November</option>
                              <option value="12">December</option>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="form-group">
                            <label for="semester_end_year">Semester End Year</label>
                            <input type="number" name="semester_end_year" id="semester_end_year" class="form-control"
                                   min="2020" max="2030" value="<?php echo date('Y'); ?>">
                          </div>
                        </div>
                      </div>
                      <div class="row">
                        <div class="col-md-12">
                          <div class="form-group">
                            <label for="report_card_file">Report Card File (PDF, Image)</label>
                            <input type="file" name="report_card_file" id="report_card_file" class="form-control"
                                   accept=".pdf,.jpg,.jpeg,.png" required>
                          </div>
                        </div>
                      </div>
                      <div class="form-group">
                        <button type="button" class="btn btn-primary" id="uploadReportCardBtn">
                          <i class="fa fa-upload"></i> Upload Report Card
                        </button>
                      </div>
                    </div>
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
                          <!-- Report cards will be loaded here via AJAX -->
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                  <i class="fa fa-info-circle"></i>
                  Please save the student information first before uploading report cards.
                </div>
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

  <!-- Add University Modal -->
  <div class="modal fade" id="addUniversityModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Add New University</h4>
        </div>
        <form id="addUniversityForm">
          <div class="modal-body">
            <div class="form-group">
              <label for="new_university_name">University Name *</label>
              <input type="text" name="name" id="new_university_name"
                     class="form-control" required placeholder="Enter university name">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Add University</button>
          </div>
        </form>
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
        <form id="addProgramForm">
          <div class="modal-body">
            <div class="form-group">
              <label for="new_program_name">Program Name *</label>
              <input type="text" name="name" id="new_program_name"
                     class="form-control" required placeholder="Enter program name">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Program</button>
          </div>
        </form>
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
        <form id="addBankForm">
          <div class="modal-body">
            <div class="form-group">
              <label for="new_bank_name">Bank Name *</label>
              <input type="text" name="name" id="new_bank_name"
                     class="form-control" required placeholder="Enter bank name">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Bank</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
.nav-tabs>li>a{font-weight:500}
.nav-tabs>li.active>a,
.nav-tabs>li.active>a:hover,
.nav-tabs>li.active>a:focus{background:#f8f9fa;border-bottom-color:transparent}
.tab-content{background:#f8f9fa;padding:20px;border:1px solid #ddd;border-top:none;border-radius:0 0 4px 4px}
.tab-content h5{color:#337ab7;margin-top:20px;margin-bottom:10px}
.tab-content h5:first-child{margin-top:0}
.tab-content hr{margin:10px 0 20px}
.has-error{border-color:#d9534f}

.university-select-wrapper,
.program-select-wrapper,
.bank-select-wrapper,
.country-select-wrapper {
    position: relative;
    display: flex;
    align-items: stretch;
}

.university-select-wrapper .bootstrap-select,
.program-select-wrapper .bootstrap-select,
.bank-select-wrapper .bootstrap-select,
.country-select-wrapper .bootstrap-select {
    flex: 1;
    margin-right: 5px;
    width: auto !important;
}

.university-select-wrapper select,
.program-select-wrapper select,
.bank-select-wrapper select,
.country-select-wrapper select {
    flex: 1;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.add-new-btn {
    border-left: 0;
    border-radius: 0 4px 4px 0 !important;
    padding: 8px 12px;
    margin-left: 0;
    z-index: 5;
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
    flex-shrink: 0;
    height: 34px;
}

.add-new-btn:hover {
    background-color: #337ab7;
    color: white;
    border-color: #2e6da4;
}

.university-select-wrapper .btn-group.bootstrap-select,
.program-select-wrapper .btn-group.bootstrap-select,
.bank-select-wrapper .btn-group.bootstrap-select,
.country-select-wrapper .btn-group.bootstrap-select {
    flex: 1;
    width: auto !important;
    display: flex !important;
}

.university-select-wrapper .btn-group.bootstrap-select .btn,
.program-select-wrapper .btn-group.bootstrap-select .btn,
.bank-select-wrapper .btn-group.bootstrap-select .btn,
.country-select-wrapper .btn-group.bootstrap-select .btn {
    width: 100%;
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
    text-align: left;
}
</style>

<script>
/** ========== Utilities ========== **/
function calculateAge() {
  const dob = $('#dob').val();
  if (dob) {
      const today = new Date();
      const birthDate = new Date(dob);
      let age = today.getFullYear() - birthDate.getFullYear();
      const monthDiff = today.getMonth() - birthDate.getMonth();
      if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) { age--; }
      $('#calculated-age').val(age + ' years');
  } else {
      $('#calculated-age').val('');
  }
}

function getMonthName(monthNumber) {
  const months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  return months[monthNumber] || '';
}

/** ========== Report Card ========== **/
function loadReportCards() {
  <?php if(isset($student)): ?>
  $.post('<?php echo admin_url("student_sponsor_portal/get_report_cards"); ?>', {
      student_id: <?php echo (int)$student['id']; ?>
  }, function(data) {
      if (data.success) {
          var tbody = $('#reportCardsTable tbody');
          tbody.empty();

          if (data.report_cards.length > 0) {
              $.each(data.report_cards, function(index, card) {
                  var row = '<tr>' +
                      '<td>' + (card.report_card_term || 'N/A') + '</td>' +
                      '<td>' + getMonthName(card.semester_end_month) + ' ' + (card.semester_end_year || '') + '</td>' +
                      '<td><a href="' + card.file_url + '" target="_blank">' + card.filename + '</a></td>' +
                      '<td>' + card.upload_date + '</td>' +
                      '<td>' +
                      '<button type="button" class="btn btn-danger btn-xs delete-report-card" data-id="' + card.id + '">' +
                      '<i class="fa fa-trash"></i>' +
                      '</button>' +
                      '</td>' +
                      '</tr>';
                  tbody.append(row);
              });
          } else {
              tbody.append('<tr><td colspan="5" class="text-center">No report cards uploaded yet.</td></tr>');
          }
      }
  });
  <?php endif; ?>
}

$(document).on('click', '#uploadReportCardBtn', function (e) {
  e.preventDefault();
  var fd = new FormData();
  fd.append('student_university_id', $('input[name="student_university_id"]').val());
  fd.append('report_card_term', $('#report_card_term').val());
  fd.append('semester_end_month', $('#semester_end_month').val());
  fd.append('semester_end_year', $('#semester_end_year').val());
  var fileInput = document.getElementById('report_card_file');
  if (fileInput && fileInput.files && fileInput.files[0]) {
    fd.append('report_card_file', fileInput.files[0]);
  }
  $.ajax({
    url: '<?php echo admin_url("student_sponsor_portal/upload_report_card"); ?>',
    type: 'POST',
    data: fd,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function (response) {
      if (response.success) {
        alert_float('success', response.message);
        $('#report_card_term').val('');
        $('#semester_end_month').val('');
        $('#semester_end_year').val('<?php echo date('Y'); ?>');
        $('#report_card_file').val('');
        loadReportCards();
      } else {
        alert_float('danger', response.message);
      }
    },
    error: function () {
      alert_float('danger', 'Error uploading report card');
    }
  });
});

$(document).on('click', '.delete-report-card', function() {
  if (confirm('Are you sure you want to delete this report card?')) {
      var reportCardId = $(this).data('id');
      $.post('<?php echo admin_url("student_sponsor_portal/delete_report_card"); ?>', {
          report_card_id: reportCardId
      }, function(response) {
          if (response.success) {
              alert_float('success', response.message);
              loadReportCards();
          } else {
              alert_float('danger', response.message);
          }
      });
  }
});

/** ========== Main Form Submit ========== **/
(function() {
  var submitting = false;

  // on page ready
  $(function(){
    // Calculate age when DOB changes
    $('#dob').on('change', calculateAge);
    calculateAge();

    // Initialize selectpicker if available
    if($.fn.selectpicker){ $('.selectpicker').selectpicker(); }

    // Country -> phone code display
    $('#country_id').on('change', function(){
      var phoneCode = $(this).find('option:selected').data('phone-code') || '+1';
      $('#phone-code-display').text(phoneCode);
    });

    // Load report cards if editing
    <?php if(isset($student)): ?> loadReportCards(); <?php endif; ?>
  });

  // Log click for debugging
  $(document).on('click', '#btn-save-student', function(){
    console.log('[University] Save button clicked');
  });

  // Handle submit (let browser post after light validation)
  $('#university-student-form').on('submit', function(e){
    console.log('[University] Form submit started');
    if (submitting) {
      console.log('[University] Prevent duplicate submit');
      e.preventDefault();
      return false;
    }

    // validate only required fields (currently just name is required)
    var isValid = true;
    $('#university-student-form').find('input[required], select[required], textarea[required]').each(function(){
      if(!$(this).val()){
        isValid = false;
        $(this).addClass('has-error');
        var tabPane = $(this).closest('.tab-pane');
        if(tabPane.length){ $('a[href="#'+tabPane.attr('id')+'"]').tab('show'); }
      } else {
        $(this).removeClass('has-error');
      }
    });

    if (!isValid) {
      console.log('[University] Validation failed');
      e.preventDefault();
      alert_float('danger','Please fill in all required fields.');
      return false;
    }

    // allow natural submit
    submitting = true;
    console.log('[University] Validation OK, submitting...');
  });

  // clear error on change
  $(document).on('input change', '#university-student-form input, #university-student-form select, #university-student-form textarea', function(){
    $(this).removeClass('has-error');
  });
})();
</script>
<?php init_tail(); ?>
