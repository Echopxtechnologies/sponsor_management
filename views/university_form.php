<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-10 col-md-offset-1">
        <div class="panel_s">
          <div class="panel-body university-student-form-wrapper">
            <!-- Header -->
            <div class="row">
              <div class="col-md-8">
                <h4 class="customer-profile-group-heading">
                  <i class="fa fa-university"></i>
                  <?php if(isset($is_university_student) && $is_university_student): ?>
                    My Profile
                  <?php else: ?>
                    <?php echo isset($student) ? 'Edit University Student' : 'Register University Student'; ?>
                  <?php endif; ?>
                </h4>
                <?php if(isset($is_university_student) && $is_university_student): ?>
                  <p class="text-muted"><i class="fa fa-info-circle"></i> Update your personal information below</p>
                <?php endif; ?>
              </div>
              <div class="col-md-4 text-right">
                <?php if(!isset($is_university_student) || !$is_university_student): ?>
                  <a href="<?php echo admin_url('student_sponsor_portal/university_students'); ?>" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Back to List
                  </a>
                <?php endif; ?>
              </div>
            </div>
            <hr class="hr-panel-heading">

            <?php
              if (isset($is_university_student) && $is_university_student && isset($student) && !empty($student['id'])) {
                  // For university students accessing their portal - include student ID in URL
                  $form_action = admin_url('student_sponsor_portal/university_student_form/' . (int)$student['id']);
              } else {
                  // For admins using normal form - use base URL (existing behavior)
                  $form_action = admin_url('student_sponsor_portal/university_student_form');
              }

              echo form_open_multipart(
                  $form_action,
                  ['id' => 'university-student-form', 'novalidate' => 'novalidate', 'autocomplete'=>'off']
              );
            ?>
            <?php if(isset($student)): ?>
              <input type="hidden" name="student_id" value="<?php echo (int)$student['id']; ?>">
            <?php endif; ?>

            <!-- remember last active tab -->
            <input type="hidden" name="active_tab" id="active_tab" value="<?php echo html_escape($this->input->post('active_tab') ?? ($active_tab ?? '#student-info')); ?>">

            <!-- hidden fields that carry "new" items to server on Save (admin only) -->
            <?php if(!isset($is_university_student) || !$is_university_student): ?>
              <input type="hidden" name="new_country_name" id="new_country_name">
              <input type="hidden" name="new_country_phone_code" id="new_country_phone_code">
              <input type="hidden" name="new_university_name" id="new_university_name">
              <input type="hidden" name="new_program_name" id="new_program_name">
              <input type="hidden" name="new_bank_name" id="new_bank_name">
            <?php endif; ?>
            <input type="hidden" name="calculated_age" id="calculated_age_hidden">

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

            <!-- Tab Navigation -->
            <ul class="nav nav-tabs student-form-tabs" role="tablist">
              <li role="presentation" class="active">
                <a href="#student-info" aria-controls="student-info" role="tab" data-toggle="tab">
                  <i class="fa fa-user"></i> 
                  <?php echo (isset($is_university_student) && $is_university_student) ? 'My Info' : 'Student Info'; ?>
                </a>
              </li>
              <?php if(!isset($is_university_student) || !$is_university_student): ?>
                <li role="presentation">
                  <a href="#sponsorship" aria-controls="sponsorship" role="tab" data-toggle="tab">
                    <i class="fa fa-heart"></i> Sponsorship
                  </a>
                </li>
              <?php endif; ?>
              <li role="presentation">
                <a href="#bank-info" aria-controls="bank-info" role="tab" data-toggle="tab">
                  <i class="fa fa-bank"></i> Bank Info
                </a>
              </li>
              <li role="presentation">
                <a href="#family-info" aria-controls="family-info" role="tab" data-toggle="tab">
                  <i class="fa fa-users"></i> Family Info
                </a>
              </li>
              <?php if(!isset($is_university_student) || !$is_university_student): ?>
                <li role="presentation">
                  <a href="#additional-info" aria-controls="additional-info" role="tab" data-toggle="tab">
                    <i class="fa fa-info-circle"></i> Additional Info
                  </a>
                </li>
              <?php endif; ?>
              <li role="presentation">
                <a href="#report-cards" aria-controls="report-cards" role="tab" data-toggle="tab">
                  <i class="fa fa-file-text"></i> Report Cards
                </a>
              </li>
              <?php if(!isset($is_university_student) || !$is_university_student): ?>
                <li role="presentation">
                  <a href="#tab_staff" data-toggle="tab">
                    <i class="fa fa-user-circle"></i> Staff Account
                  </a>
                </li>
              <?php endif; ?>
            </ul>

            <div class="tab-content student-form-content" style="margin-top:0;">

              <!-- Student Info Tab -->
              <div role="tabpanel" class="tab-pane active" id="student-info">
                <h5><i class="fa fa-user"></i> Basic Information</h5>
                <hr>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="name" class="control-label">Full Name *</label>
                      <input type="text" name="name" id="name" required class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['name'] ?? '') : (isset($old['name']) ? html_escape($old['name']) : ''); ?>" 
                        placeholder="Enter student's full name">
                    </div>

                    <div class="form-group">
                      <label for="email" class="control-label">Email Address</label>
                      <input type="email" name="email" id="email" class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['email'] ?? '') : (isset($old['email']) ? html_escape($old['email']) : ''); ?>" 
                        placeholder="Enter email address">
                    </div>

                    <div class="form-group">
                      <label for="phone" class="control-label">Phone Number</label>
                      <div class="input-group">
                        <div class="input-group-addon" id="phone-code-display">
                          <?php
                            $phone_code = '+1'; // Default to US
                            if(isset($student) && !empty($student['country_id']) && !empty($countries)) {
                              foreach($countries as $country) {
                                $cid = (int)($country['id'] ?? 0);
                                $pcode = $country['phone_code'] ?? $country['calling_code'] ?? '';
                                if((int)$student['country_id'] === $cid && $pcode!==''){ $phone_code = $pcode; break; }
                              }
                            }
                            echo html_escape($phone_code);
                          ?>
                        </div>
                        <input type="text" name="phone" id="phone" class="form-control"
                          value="<?php echo isset($student) ? html_escape($student['contact_no'] ?? '') : (isset($old['phone']) ? html_escape($old['phone']) : ''); ?>" 
                          placeholder="Enter phone number">
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="dob" class="control-label">Date of Birth</label>
                      <input type="date" name="dob" id="dob" class="form-control"
                        value="<?php echo isset($student) ? ($student['university_student_dob'] ?? '') : (isset($old['dob']) ? $old['dob'] : ''); ?>">
                    </div>

                    <div class="form-group">
                      <label class="control-label">Age</label>
                      <input type="text" id="calculated-age" class="form-control" readonly
                        value="<?php echo isset($student) && !empty($student['university_age']) ? $student['university_age'] . ' years' : ''; ?>" 
                        placeholder="Will be calculated from DOB">
                    </div>

                    <div class="form-group">
                      <label for="profile_photo" class="control-label">Profile Photo</label>
                      <input type="file" name="profile_photo" id="profile_photo" class="form-control" accept="image/*">
                      <?php if(isset($student) && !empty($student['id'])): ?>
                        <div class="current-photo" style="margin-top: 10px;">
                          <img src="<?php echo admin_url('student_sponsor_portal/display_profile_photo/' . (int)$student['id']); ?>"
                               alt="Profile Photo" style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;"
                               onerror="this.style.display='none'">
                          <br><small class="text-muted">Current profile photo</small>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <!-- Country dropdown + add (admin only can add new) -->
                    <div class="form-group">
                      <label for="country_id" class="control-label">Country</label>
                      <?php if(isset($is_university_student) && $is_university_student): ?>
                        <!-- Simple dropdown for university students -->
                        <select name="country_id" id="country_id" class="form-control selectpicker" data-live-search="true" data-none-selected-text="Select Country">
                          <option value="">Select Country</option>
                          <?php if(!empty($countries)): foreach($countries as $c): ?>
                            <?php
                              $cid   = (int)($c['id'] ?? 0);
                              $cname = $c['name'] ?? $c['short_name'] ?? '';
                              $pcode = $c['phone_code'] ?? $c['calling_code'] ?? '';
                              $selected = (isset($student) && (int)($student['country_id'] ?? 0) === $cid) ||
                                         (isset($old['country_id']) && (int)$old['country_id'] === $cid);
                            ?>
                            <option value="<?php echo $cid; ?>"
                              data-phone-code="<?php echo html_escape($pcode); ?>"
                              <?php echo $selected ? 'selected' : ''; ?>>
                              <?php echo html_escape($cname); ?><?php echo $pcode!=='' ? ' ('.html_escape($pcode).')' : ''; ?>
                            </option>
                          <?php endforeach; endif; ?>
                        </select>
                      <?php else: ?>
                        <!-- Admin version with add button -->
                        <div class="country-select-wrapper">
                          <select name="country_id" id="country_id" class="form-control selectpicker" data-live-search="true" data-none-selected-text="Select Country">
                            <option value="">Select Country</option>
                            <?php if(!empty($countries)): foreach($countries as $c): ?>
                              <?php
                                $cid   = (int)($c['id'] ?? 0);
                                $cname = $c['name'] ?? $c['short_name'] ?? '';
                                $pcode = $c['phone_code'] ?? $c['calling_code'] ?? '';
                                $selected = (isset($student) && (int)($student['country_id'] ?? 0) === $cid) ||
                                           (isset($old['country_id']) && (int)$old['country_id'] === $cid);
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
                      <?php endif; ?>
                    </div>

                    <div class="form-group">
                      <label for="address" class="control-label">Address</label>
                      <textarea name="address" id="address" class="form-control" rows="3" placeholder="Complete address"><?php echo isset($student) ? html_escape($student['address'] ?? '') : (isset($old['address']) ? html_escape($old['address']) : ''); ?></textarea>
                    </div>

                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group">
                          <label for="city" class="control-label">City / District</label>
                          <input type="text" name="city" id="city" class="form-control"
                            value="<?php echo isset($student) ? html_escape($student['city'] ?? '') : (isset($old['city']) ? html_escape($old['city']) : ''); ?>" 
                            placeholder="City / District">
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <label for="postal_code" class="control-label">Postal Code</label>
                          <input type="text" name="postal_code" id="postal_code" class="form-control"
                            value="<?php echo isset($student) ? html_escape($student['zip'] ?? '') : (isset($old['postal_code']) ? html_escape($old['postal_code']) : ''); ?>" 
                            placeholder="Postal code">
                        </div>
                      </div>
                    </div>

                    <!-- University ID - Editable for both admin and students -->
                    <div class="form-group">
                      <label for="university_id" class="control-label">University Student ID</label>
                      <input type="text" name="university_id" id="university_id" class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['university_id'] ?? '') : (isset($old['university_id']) ? html_escape($old['university_id']) : ''); ?>" 
                        placeholder="Enter university student ID">
                    </div>

                    <!-- Internal ID - Always read-only display -->
                    <?php if(isset($student) && !empty($student['university_internal_id'])): ?>
                      <div class="form-group">
                        <label class="control-label">Internal Student ID</label>
                        <p class="form-control-static">
                          <strong><?php echo html_escape($student['university_internal_id']); ?></strong>
                        </p>
                      </div>
                    <?php endif; ?>

                    <?php if(!isset($is_university_student) || !$is_university_student): ?>
                      <!-- Admin can still edit internal ID if needed -->
                      <div class="form-group">
                        <label for="university_internal_id" class="control-label">Edit Internal Student ID</label>
                        <input type="text" name="university_internal_id" id="university_internal_id" class="form-control"
                          value="<?php echo isset($student) ? html_escape($student['university_internal_id'] ?? '') : (isset($old['university_internal_id']) ? html_escape($old['university_internal_id']) : ''); ?>" 
                          placeholder="Internal tracking ID">
                        <small class="text-muted">Admin only: Modify internal tracking ID</small>
                      </div>
                    <?php endif; ?>
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
                          <?php if(!empty($universities)): foreach($universities as $university): ?>
                            <?php
                              $uid = (int)($university['id'] ?? 0);
                              $uname = (string)($university['name'] ?? '');
                              $university_selected = (isset($student) && (int)($student['university_name_id'] ?? 0) === $uid) ||
                                                   (isset($old['university_name_id']) && (int)$old['university_name_id'] === $uid);
                            ?>
                            <option value="<?php echo $uid; ?>" data-name="<?php echo html_escape($uname); ?>"
                              <?php echo $university_selected ? 'selected' : ''; ?>>
                              <?php echo html_escape($uname); ?>
                            </option>
                          <?php endforeach; endif; ?>
                        </select>
                        <?php if(!isset($is_university_student) || !$is_university_student): ?>
                          <button type="button" class="btn btn-default btn-sm add-new-btn" data-toggle="modal" data-target="#addUniversityModal" title="Add New University">
                            <i class="fa fa-plus"></i>
                          </button>
                        <?php endif; ?>
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="university_program_id" class="control-label">Program / Degree</label>
                      <div class="program-select-wrapper">
                        <select name="university_program_id" id="university_program_id" class="form-control selectpicker" data-live-search="true" data-none-selected-text="Select Program">
                          <option value="">Select Program</option>
                          <?php if(!empty($programs)): foreach($programs as $program): ?>
                            <?php
                              $pid = (int)($program['id'] ?? 0);
                              $pname = (string)($program['name'] ?? '');
                              $program_selected = (isset($student) && (int)($student['university_program_id'] ?? 0) === $pid) ||
                                                (isset($old['university_program_id']) && (int)$old['university_program_id'] === $pid);
                            ?>
                            <option value="<?php echo $pid; ?>" data-name="<?php echo html_escape($pname); ?>"
                              <?php echo $program_selected ? 'selected' : ''; ?>>
                              <?php echo html_escape($pname); ?>
                            </option>
                          <?php endforeach; endif; ?>
                        </select>
                        <?php if(!isset($is_university_student) || !$is_university_student): ?>
                          <button type="button" class="btn btn-default btn-sm add-new-btn" data-toggle="modal" data-target="#addProgramModal" title="Add New Program">
                            <i class="fa fa-plus"></i>
                          </button>
                        <?php endif; ?>
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
                        $current_year = isset($student) ? ($student['university_year_of_study'] ?? '') : (isset($old['year_of_study']) ? $old['year_of_study'] : '');
                        foreach($year_options as $value => $label): ?>
                          <option value="<?php echo $value; ?>" <?php echo ($current_year == $value) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Sponsorship Tab (Admin Only) -->
              <?php if(!isset($is_university_student) || !$is_university_student): ?>
              <div role="tabpanel" class="tab-pane" id="sponsorship">
                  <!-- READ-ONLY CURRENT SPONSORS DISPLAY -->
  <?php if(isset($student) && !empty($student['id'])): ?>
    <div class="row">
      <div class="col-md-12">
        <h5><i class="fa fa-heart"></i> Current Sponsors</h5>
        <hr>
        <div class="current-sponsors-display">
          <?php 
            // Get sponsor history (already loaded by model)
            $sponsor_history = $student['sponsor_history'] ?? [];
            if (!empty($sponsor_history)): 
          ?>
            <div class="sponsors-list">
              <?php foreach($sponsor_history as $index => $sponsor): ?>
                <div class="sponsor-card" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px; padding: 15px; margin-bottom: 10px;">
                  <div class="row">
                    <div class="col-md-8">
                      <h6 class="sponsor-name">
                        <i class="fa fa-heart text-danger"></i> 
                        <strong><?php echo html_escape($sponsor['sponsor_name']); ?></strong>
                        <?php if(!empty($sponsor['sponsor_type'])): ?>
                          <span class="label label-info"><?php echo html_escape($sponsor['sponsor_type']); ?></span>
                        <?php endif; ?>
                      </h6>
                      
                      <?php if(!empty($sponsor['sponsor_email'])): ?>
                        <p class="sponsor-contact">
                          <i class="fa fa-envelope text-primary"></i> 
                          <a href="mailto:<?php echo html_escape($sponsor['sponsor_email']); ?>">
                            <?php echo html_escape($sponsor['sponsor_email']); ?>
                          </a>
                        </p>
                      <?php endif; ?>
                      
                      <div class="sponsor-meta">
                        <small class="text-muted">
                          <i class="fa fa-info-circle"></i> 
                          <strong>Relationship:</strong> <?php echo ucfirst($sponsor['relationship_type'] ?? 'Direct'); ?> Sponsorship
                          
                          <?php if(!empty($sponsor['total_amount'])): ?>
                            | <strong>Amount:</strong> ₹<?php echo number_format($sponsor['total_amount'], 0); ?>
                          <?php endif; ?>
                          
                          <?php if(!empty($sponsor['sponsorship_start'])): ?>
                            | <strong>Start:</strong> <?php echo date('M d, Y', strtotime($sponsor['sponsorship_start'])); ?>
                          <?php endif; ?>
                          
                          <?php if(!empty($sponsor['sponsorship_end'])): ?>
                            | <strong>End:</strong> <?php echo date('M d, Y', strtotime($sponsor['sponsorship_end'])); ?>
                          <?php endif; ?>
                        </small>
                      </div>
                    </div>
                    
                    <div class="col-md-4 text-right">
                      <?php if($sponsor['relationship_type'] === 'transaction'): ?>
                        <span class="label label-success">Transaction-based</span>
                      <?php else: ?>
                        <span class="label label-primary">Direct Assignment</span>
                      <?php endif; ?>
                      
                      <?php if(!empty($sponsor['payment_type'])): ?>
                        <br><small class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $sponsor['payment_type'])); ?></small>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            
            <div class="sponsors-summary" style="background: #e8f5e8; border: 1px solid #d4edda; border-radius: 4px; padding: 10px; margin-top: 15px;">
              <strong><i class="fa fa-info-circle text-success"></i> Summary:</strong> 
              This student is sponsored by <strong><?php echo count($sponsor_history); ?></strong> sponsor<?php echo count($sponsor_history) > 1 ? 's' : ''; ?>
              <?php 
                $total_amount = array_sum(array_column($sponsor_history, 'total_amount'));
                if ($total_amount > 0): 
              ?>
                with a total commitment of <strong>₹<?php echo number_format($total_amount, 0); ?></strong>
              <?php endif; ?>
            </div>
            
          <?php else: ?>
            <div class="no-sponsors" style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; padding: 20px; text-align: center;">
              <i class="fa fa-heart-o fa-2x text-warning"></i>
              <h6 class="text-warning" style="margin-top: 10px;">No Sponsors Assigned</h6>
              <p class="text-muted">This student does not have any sponsors yet. Sponsors can be assigned through direct assignment or sponsorship transactions.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <br>
  <?php endif; ?>
                <div class="row">
                  <div class="col-md-6">
                    

                    <div class="form-group">
                      <label for="sponsorship_start" class="control-label">Sponsorship Start Date</label>
                      <input type="date" name="sponsorship_start" id="sponsorship_start" class="form-control"
                        value="<?php echo isset($student) ? ($student['university_sponsorship_start_date'] ?? '') : (isset($old['sponsorship_start']) ? $old['sponsorship_start'] : ''); ?>">
                    </div>
                    <div class="form-group">
                      <label for="sponsorship_end" class="control-label">Sponsorship End Date</label>
                      <input type="date" name="sponsorship_end" id="sponsorship_end" class="form-control"
                        value="<?php echo isset($student) ? ($student['university_sponsorship_end_date'] ?? '') : (isset($old['sponsorship_end']) ? $old['sponsorship_end'] : ''); ?>">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="introduced_by" class="control-label">Introduced By</label>
                      <input type="text" name="introduced_by" id="introduced_by" class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['university_introducedby'] ?? '') : (isset($old['introduced_by']) ? html_escape($old['introduced_by']) : ''); ?>" 
                        placeholder="Person who introduced the student">
                    </div>
                    <div class="form-group">
                      <label for="introduced_phone" class="control-label">Introducer's Phone</label>
                      <input type="text" name="introduced_phone" id="introduced_phone" class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['university_introducedph'] ?? '') : (isset($old['introduced_phone']) ? html_escape($old['introduced_phone']) : ''); ?>" 
                        placeholder="Contact number">
                    </div>
                  </div>
                </div>
              </div>
              <?php endif; ?>

              <!-- Bank Info Tab -->
              <div role="tabpanel" class="tab-pane" id="bank-info">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="bank_id" class="control-label">Bank Name</label>
                      <div class="bank-select-wrapper">
                        <select name="bank_id" id="bank_id" class="form-control selectpicker" data-live-search="true" data-none-selected-text="Select Bank">
                          <option value="">Select Bank</option>
                          <?php if(!empty($banks)): foreach($banks as $b): ?>
                            <?php
                              $bank_selected = (isset($student) && (int)($student['bank_id'] ?? 0) === (int)$b['id']) ||
                                             (isset($old['bank_id']) && (int)$old['bank_id'] === (int)$b['id']);
                            ?>
                            <option value="<?php echo (int)$b['id']; ?>" <?php echo $bank_selected ? 'selected' : ''; ?>>
                              <?php echo html_escape($b['name']); ?>
                            </option>
                          <?php endforeach; endif; ?>
                        </select>
                        <?php if(!isset($is_university_student) || !$is_university_student): ?>
                          <button type="button" class="btn btn-default btn-sm add-new-btn" data-toggle="modal" data-target="#addBankModal" title="Add New Bank">
                            <i class="fa fa-plus"></i>
                          </button>
                        <?php endif; ?>
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="bank_account_number" class="control-label">Bank Account Number</label>
                      <input type="text" name="bank_account_number" id="bank_account_number" class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['university_bank_account_no'] ?? '') : (isset($old['bank_account_number']) ? html_escape($old['bank_account_number']) : ''); ?>" 
                        placeholder="Account number">
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="bank_branch_number" class="control-label">Bank Branch Number</label>
                      <input type="text" name="bank_branch_number" id="bank_branch_number" class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['university_bank_branch_number'] ?? '') : (isset($old['bank_branch_number']) ? html_escape($old['bank_branch_number']) : ''); ?>" 
                        placeholder="Branch code/number">
                    </div>
                    <div class="form-group">
                      <label for="bank_branch_info" class="control-label">Bank Branch Information</label>
                      <textarea name="bank_branch_info" id="bank_branch_info" class="form-control" rows="3" placeholder="Additional branch details"><?php echo isset($student) ? html_escape($student['university_bank_branch_info'] ?? '') : (isset($old['bank_branch_info']) ? html_escape($old['bank_branch_info']) : ''); ?></textarea>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Family Info Tab -->
              <div role="tabpanel" class="tab-pane" id="family-info">
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="father_name" class="control-label">Father's Name</label>
                      <input type="text" name="father_name" id="father_name" class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['university_father_name'] ?? '') : (isset($old['father_name']) ? html_escape($old['father_name']) : ''); ?>">
                    </div>
                    <div class="form-group">
                      <label for="father_income" class="control-label">Father's Income</label>
                      <input type="number" step="0.01" name="father_income" id="father_income" class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['university_father_income'] ?? '') : (isset($old['father_income']) ? html_escape($old['father_income']) : ''); ?>" 
                        placeholder="Monthly income">
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="mother_name" class="control-label">Mother's Name</label>
                      <input type="text" name="mother_name" id="mother_name" class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['university_mother_name'] ?? '') : (isset($old['mother_name']) ? html_escape($old['mother_name']) : ''); ?>">
                    </div>
                    <div class="form-group">
                      <label for="mother_income" class="control-label">Mother's Income</label>
                      <input type="number" step="0.01" name="mother_income" id="mother_income" class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['university_mother_income'] ?? '') : (isset($old['mother_income']) ? html_escape($old['mother_income']) : ''); ?>" 
                        placeholder="Monthly income">
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="guardian_name" class="control-label">Guardian's Name</label>
                      <input type="text" name="guardian_name" id="guardian_name" class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['university_guardian_name'] ?? '') : (isset($old['guardian_name']) ? html_escape($old['guardian_name']) : ''); ?>">
                    </div>
                    <div class="form-group">
                      <label for="guardian_income" class="control-label">Guardian's Income</label>
                      <input type="number" step="0.01" name="guardian_income" id="guardian_income" class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['university_guardian_income'] ?? '') : (isset($old['guardian_income']) ? html_escape($old['guardian_income']) : ''); ?>" 
                        placeholder="Monthly income">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-12">
                    <div class="form-group">
                      <label for="background_information" class="control-label">Background Information</label>
                      <textarea name="background_information" id="background_information" class="form-control" rows="3" 
                        placeholder="Student background, family situation, etc."><?php echo isset($student) ? html_escape($student['background_info'] ?? '') : (isset($old['background_information']) ? html_escape($old['background_information']) : ''); ?></textarea>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Additional Info Tab (Admin Only) -->
              <?php if(!isset($is_university_student) || !$is_university_student): ?>
              <div role="tabpanel" class="tab-pane" id="additional-info">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="internal_comment" class="control-label">Internal Comment</label>
                      <textarea name="internal_comment" id="internal_comment" class="form-control" rows="3" 
                        placeholder="Internal notes (not visible to sponsors)"><?php echo isset($student) ? html_escape($student['internal_comment'] ?? '') : (isset($old['internal_comment']) ? html_escape($old['internal_comment']) : ''); ?></textarea>
                      <small class="text-muted">These comments are only visible to staff/administrators.</small>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="external_comment" class="control-label">External Comment</label>
                      <textarea name="external_comment" id="external_comment" class="form-control" rows="3" 
                        placeholder="Comments visible to sponsors"><?php echo isset($student) ? html_escape($student['external_comment'] ?? '') : (isset($old['external_comment']) ? html_escape($old['external_comment']) : ''); ?></textarea>
                      <small class="text-muted">These comments may be visible to sponsors and other stakeholders.</small>
                    </div>
                  </div>
                </div>
              </div>
              <?php endif; ?>

              <!-- Report Cards Tab -->
              <div role="tabpanel" class="tab-pane" id="report-cards">
                <?php if(isset($student) && !empty($student['id'])): ?>
                  <div class="row">
                    <div class="col-md-12">
                      <h5 id="repo"><i class="fa fa-upload"></i> Upload Report Card</h5>
                      <hr>
                      <input type="hidden" id="rc_student_university_id" value="<?php echo (int)$student['id']; ?>">
                      <div class="row">
                        <div class="col-md-4">
                          <div class="form-group">
                            <label for="filename">Filename</label>
                            <input type="text" id="filename" class="form-control" placeholder="e.g. Semester 1 Report - 2025">
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="form-group">
                            <label for="report_card_term">Term</label>
                            <select id="report_card_term" class="form-control">
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
                            <select id="semester_end_month" class="form-control">
                              <option value="">Select Month</option>
                              <?php for($m=1;$m<=12;$m++): ?>
                                <option value="<?php echo $m; ?>"><?php echo date('F', mktime(0,0,0,$m,10)); ?></option>
                              <?php endfor; ?>
                            </select>
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label for="semester_end_year">Semester End Year</label>
                            <input type="number" id="semester_end_year" class="form-control" min="2020" max="2035" value="<?php echo date('Y'); ?>">
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label for="report_card_file">Report Card File (PDF, Image)</label>
                            <input type="file" id="report_card_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.gif">
                          </div>
                        </div>
                      </div>

                      <div class="form-group">
                        <button type="button" class="btn btn-primary" id="uploadReportCardBtn" onclick="return uploadReportCard();">
                          <i class="fa fa-upload"></i> Upload Report Card
                        </button>
                      </div>
                    </div>
                  </div>

                  <div class="row" style="margin-top: 30px;">
                    <div class="col-md-12">
                      <h5><i class="fa fa-files-o"></i> <?php echo (isset($is_university_student) && $is_university_student) ? 'My Report Cards' : 'Report Cards'; ?></h5>
                      <hr>
                      <div class="table-responsive">
                        <table class="table table-striped" id="reportCardsTable">
                          <thead>
                            <tr>
                              <th>#</th>
                              <th>Filename</th>
                              <th>Term</th>
                              <th>End Month/Year</th>
                              <th>Upload Date</th>
                              <th>Size</th>
                              <th>Actions</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr><td colspan="7" class="text-center">Loading...</td></tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                <?php else: ?>
                  <div class="alert alert-info"><i class="fa fa-info-circle"></i> Please save the student first before uploading report cards.</div>
                <?php endif; ?>
              </div>

              <!-- Staff Account Tab (Admin Only) -->
              <?php if(!isset($is_university_student) || !$is_university_student): ?>
              <div class="tab-pane" id="tab_staff">
                <div class="checkbox checkbox-primary">
                  <input type="checkbox" id="create_staff" name="create_staff" value="1" <?php echo (!empty($student['staff_id']) || isset($old['create_staff'])) ? 'checked' : ''; ?>>
                  <label for="create_staff">Create a Staff login</label>
                </div>
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="staff_email" class="control-label">Login Email</label>
                      <input type="email" name="staff_email" id="staff_email" class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['email'] ?? '') : (isset($old['staff_email']) ? html_escape($old['staff_email']) : ''); ?>">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="staff_firstname" class="control-label">First Name</label>
                      <input type="text" name="staff_firstname" id="staff_firstname" class="form-control"
                        value="<?php echo isset($student) ? html_escape($student['name'] ?? '') : (isset($old['staff_firstname']) ? html_escape($old['staff_firstname']) : ''); ?>">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="staff_lastname" class="control-label">Last Name</label>
                      <input type="text" name="staff_lastname" id="staff_lastname" class="form-control"
                        value="<?php echo isset($old['staff_lastname']) ? html_escape($old['staff_lastname']) : ''; ?>">
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
                      <input type="checkbox" id="staff_active" name="staff_active" value="1" <?php echo (!isset($student) || ($student['active'] ?? 1) || (isset($old['staff_active']) && $old['staff_active'])) ? 'checked' : ''; ?>>
                      <label for="staff_active">Active Login</label>
                    </div>
                  </div>
                </div>

                <?php if (!empty($student['id'])): ?>
                  <input type="hidden" name="student_id" value="<?php echo (int)$student['id']; ?>">
                  <button type="submit" class="btn btn-success" formaction="<?php echo admin_url('student_sponsor_portal/grant_university_access'); ?>" formmethod="post">Grant Portal Access</button>
                  <button type="submit" class="btn btn-danger"  formaction="<?php echo admin_url('student_sponsor_portal/revoke_university_access'); ?>" formmethod="post">Revoke Portal Access</button>
                <?php endif; ?>
              </div>
              <?php endif; ?>

            </div>

            <!-- Action Buttons -->
            <div class="row" style="margin-top: 20px;">
              <div class="col-md-12">
                <button type="submit" id="btn-save-student" class="btn btn-primary btn-lg">
                  <i class="fa fa-save"></i> 
                  <?php if(isset($is_university_student) && $is_university_student): ?>
                    Update My Profile
                  <?php else: ?>
                    <?php echo isset($student) ? 'Update Student' : 'Register Student'; ?>
                  <?php endif; ?>
                </button>
                
                <?php if(!isset($is_university_student) || !$is_university_student): ?>
                  <a href="<?php echo admin_url('student_sponsor_portal/university_students'); ?>" class="btn btn-default btn-lg">
                    <i class="fa fa-times"></i> Cancel
                  </a>
                <?php endif; ?>
              </div>
            </div>

            <?php echo form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modals (Admin Only) -->
  <?php if(!isset($is_university_student) || !$is_university_student): ?>

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
            <input type="text" id="modal_country_name" class="form-control" required placeholder="e.g., United States">
          </div>
          <div class="form-group">
            <label for="modal_country_phone">Phone Code *</label>
            <input type="text" id="modal_country_phone" class="form-control" required placeholder="+1">
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
          <small class="text-muted">This will be created instantly.</small>
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
          <small class="text-muted">This will be created instantly.</small>
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
          <small class="text-muted">This will be created instantly.</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="btnAddBank" onclick="return addBank();">Add Bank</button>
        </div>
      </div>
    </div>
  </div>

  <?php endif; ?>

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
/* Enhanced University Form Styling - Compatible with both Portal and Admin views */

/* Main wrapper */
.university-student-form-wrapper {
  background: #fff;
  border-radius: 4px;
}

/* Tab Navigation */
.university-student-form-wrapper .student-form-tabs {
  border-bottom: 2px solid #e8e8e8;
  background: linear-gradient(to bottom, #f8f9fa 0%, #f1f3f4 100%);
  margin: 0 0 0 0;
  padding: 0 15px;
  border-radius: 4px 4px 0 0;
  overflow-x: auto;
  white-space: nowrap;
}

.university-student-form-wrapper .student-form-tabs > li {
  margin-bottom: -2px;
  position: relative;
  display: inline-block;
  float: none;
}

.university-student-form-wrapper .student-form-tabs > li > a {
  font-weight: 500;
  color: #555;
  border: 1px solid transparent;
  border-radius: 4px 4px 0 0;
  padding: 12px 16px;
  margin-right: 2px;
  transition: all 0.2s ease-in-out;
  background: transparent;
  text-decoration: none;
  position: relative;
}

.university-student-form-wrapper .student-form-tabs > li > a:hover {
  background: rgba(255, 255, 255, 0.8);
  border-color: #ddd #ddd transparent;
  color: #337ab7;
  text-decoration: none;
}

.university-student-form-wrapper .student-form-tabs > li.active > a,
.university-student-form-wrapper .student-form-tabs > li.active > a:hover,
.university-student-form-wrapper .student-form-tabs > li.active > a:focus {
  color: #337ab7;
  background: #fff;
  border: 1px solid #ddd;
  border-bottom-color: #fff;
  cursor: default;
  font-weight: 600;
  box-shadow: 0 -2px 4px rgba(0,0,0,0.1);
  text-decoration: none;
}

.university-student-form-wrapper .student-form-tabs > li.active > a::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  right: 0;
  height: 2px;
  background: #337ab7;
}

/* Tab Content */
.university-student-form-wrapper .student-form-content {
  background: #fff;
  padding: 25px;
  border: 1px solid #ddd;
  border-top: none;
  border-radius: 0 0 4px 4px;
  min-height: 400px;
}

/* Section Headers in tabs */
.university-student-form-wrapper .student-form-content h5 {
  color: #337ab7;
  margin-top: 25px;
  margin-bottom: 12px;
  font-weight: 600;
  font-size: 16px;
  border-left: 4px solid #337ab7;
  padding-left: 12px;
}

.university-student-form-wrapper .student-form-content h5:first-child {
  margin-top: 0;
}

.university-student-form-wrapper .student-form-content hr {
  margin: 15px 0 20px;
  border-color: #e8e8e8;
}

/* Form Controls */
.university-student-form-wrapper .form-group {
  margin-bottom: 20px;
}

.university-student-form-wrapper .form-control {
  border: 1px solid #d0d0d0;
  border-radius: 4px;
  transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.university-student-form-wrapper .form-control:focus {
  border-color: #337ab7;
  box-shadow: 0 0 5px rgba(51, 122, 183, 0.3);
}

.university-student-form-wrapper .control-label {
  font-weight: 500;
  color: #333;
  margin-bottom: 6px;
}

/* Error States */
.university-student-form-wrapper .has-error,
.university-student-form-wrapper .field-error {
  border-color: #d9534f !important;
  box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 6px rgba(217,83,79,.6) !important;
}

/* Select Wrappers */
.university-student-form-wrapper .university-select-wrapper,
.university-student-form-wrapper .program-select-wrapper,
.university-student-form-wrapper .bank-select-wrapper,
.university-student-form-wrapper .country-select-wrapper {
  position: relative;
  display: flex;
  align-items: stretch;
}

.university-student-form-wrapper .university-select-wrapper .bootstrap-select,
.university-student-form-wrapper .program-select-wrapper .bootstrap-select,
.university-student-form-wrapper .bank-select-wrapper .bootstrap-select,
.university-student-form-wrapper .country-select-wrapper .bootstrap-select {
  flex: 1;
  margin-right: 5px;
  width: auto !important;
}

.university-student-form-wrapper .add-new-btn {
  border-left: 0;
  border-radius: 0 4px 4px 0 !important;
  padding: 8px 12px;
  margin-left: 0;
  z-index: 5;
  flex-shrink: 0;
  height: 34px;
  background: #f8f9fa;
  border-color: #ccc;
}

.university-student-form-wrapper .add-new-btn:hover {
  background-color: #337ab7;
  color: #fff;
  border-color: #2e6da4;
}

.university-student-form-wrapper .university-select-wrapper .btn-group.bootstrap-select,
.university-student-form-wrapper .program-select-wrapper .btn-group.bootstrap-select,
.university-student-form-wrapper .bank-select-wrapper .btn-group.bootstrap-select,
.university-student-form-wrapper .country-select-wrapper .btn-group.bootstrap-select {
  flex: 1;
  width: auto !important;
  display: flex !important;
}

.university-student-form-wrapper .university-select-wrapper .btn-group.bootstrap-select .btn,
.university-student-form-wrapper .program-select-wrapper .btn-group.bootstrap-select .btn,
.university-student-form-wrapper .bank-select-wrapper .btn-group.bootstrap-select .btn,
.university-student-form-wrapper .country-select-wrapper .btn-group.bootstrap-select .btn {
  width: 100%;
  border-top-right-radius: 0 !important;
  border-bottom-right-radius: 0 !important;
  text-align: left;
}

/* Form control static styling */
.university-student-form-wrapper .form-control-static {
  padding-top: 7px;
  padding-bottom: 7px;
  margin-bottom: 0;
  min-height: 34px;
  font-weight: 500;
  color: #555;
}

/* Alert styling */
.university-student-form-wrapper .alert {
  border-radius: 4px;
  border: 1px solid transparent;
}

/* Button styling */
.university-student-form-wrapper .btn {
  border-radius: 4px;
  font-weight: 500;
}

.university-student-form-wrapper .btn-primary {
  background-color: #337ab7;
  border-color: #2e6da4;
}

.university-student-form-wrapper .btn-primary:hover {
  background-color: #286090;
  border-color: #204d74;
}

/* Table styling in report cards */
.university-student-form-wrapper .table {
  margin-bottom: 0;
}

.university-student-form-wrapper .table > thead > tr > th {
  border-bottom: 2px solid #ddd;
  background-color: #f8f9fa;
  font-weight: 600;
}

.university-student-form-wrapper .table-striped > tbody > tr:nth-of-type(odd) {
  background-color: #f9f9f9;
}

/* Responsive tabs for mobile */
@media (max-width: 768px) {
  .university-student-form-wrapper .student-form-tabs {
    padding: 0 10px;
  }
  
  .university-student-form-wrapper .student-form-tabs > li > a {
    padding: 10px 12px;
    font-size: 12px;
  }
  
  .university-student-form-wrapper .student-form-content {
    padding: 15px;
  }
}

/* Ensure consistent styling across different contexts */
.university-student-form-wrapper * {
  box-sizing: border-box;
}

/* Override any conflicting Perfex styles */
.university-student-form-wrapper .nav-tabs {
  border-bottom: 2px solid #e8e8e8 !important;
}

.university-student-form-wrapper .nav-tabs > li.active > a {
  border-bottom-color: #fff !important;
}

.university-student-form-wrapper .tab-content {
  border-top: none !important;
}
</style>

<?php init_tail(); ?>
<script>
if (typeof alert_float !== 'function') { window.alert_float = function(type, message){ alert(message); }; }

(function($){
  // Check if user is a university student - using passed data from controller
  var isUniversityStudent = <?php echo json_encode(isset($is_university_student) && $is_university_student); ?>;
  var canEditRestricted = <?php echo json_encode(isset($can_edit_restricted_fields) && $can_edit_restricted_fields); ?>;

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

  function calculateAge(){
    var dob = $('#dob').val();
    if(!dob){ 
      $('#calculated-age').val(''); 
      $('#calculated_age_hidden').val('');
      return null; 
    }
    var today = new Date(), birthDate = new Date(dob);
    var age = today.getFullYear() - birthDate.getFullYear();
    var m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
    
    $('#calculated-age').val(age + ' years');
    $('#calculated_age_hidden').val(age); // For form submission
    
    if (age < 18 && age > 0) {
      $('#age-display').text(age + ' years old');
      $('#ageWarningModal').modal('show');
    }
    return age;
  }

  function refreshSelectpicker($el){
    if ($.fn.selectpicker) { $el.selectpicker('refresh'); }
  }

  // Admin-only functions
  if (!isUniversityStudent) {
    // Add Country
    window.addCountry = function(){
      var name  = $.trim($('#modal_country_name').val());
      var phone = $.trim($('#modal_country_phone').val());
      if(!name || !phone){ alert('Country name and phone code are required'); return false; }

      $('#new_country_name').val(name);
      $('#new_country_phone_code').val(phone);

      var $sel = $('#country_id');
      var tmpId = '__NEW_COUNTRY__';
      $sel.find('option[value="'+tmpId+'"]').remove();
      $sel.append($('<option/>',{value:tmpId, text: name+(phone?' ('+phone+')':''), selected:true, 'data-phone-code': phone}));
      refreshSelectpicker($sel);
      $('#phone-code-display').text(phone);

      $('#addCountryModal').modal('hide');
      $('#modal_country_name').val(''); $('#modal_country_phone').val('');
      alert_float('success','Country will be added on Save');
      recalcProfileCompletion();
      return false;
    };

    // Add University (instant via AJAX)
    window.addUniversity = function(){
      var $btn = $('#btnAddUniversity').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Adding...');
      var name = $.trim($('#modal_university_name').val());
      if(!name){ alert('Enter university name'); $btn.prop('disabled', false).text('Add University'); return false; }

      $.ajax({
        url: '<?php echo admin_url("student_sponsor_portal/add_university_name"); ?>',
        type: 'POST', dataType: 'json',
        data: { name: name }
      }).done(function(r){
        if(r && r.success){
          var $sel = $('#university_name_id');
          var id   = r.id || r.university_id || '';
          var txt  = r.name || name;
          if(id){
            $sel.append($('<option/>',{value:id, text:txt, selected:true, 'data-name': txt}));
            refreshSelectpicker($sel);
          }
          $('#addUniversityModal').modal('hide'); $('#modal_university_name').val('');
          alert_float('success', r.message || 'University added');
          recalcProfileCompletion();
        } else {
          alert(r && r.message ? r.message : 'Failed to add university');
        }
      }).fail(function(){ alert('Error adding university.'); })
        .always(function(){ $btn.prop('disabled', false).text('Add University'); });

      return false;
    };

    // Add Program (instant via AJAX)
    window.addProgram = function(){
      var $btn = $('#btnAddProgram').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Adding...');
      var name = $.trim($('#modal_program_name').val());
      if(!name){ alert('Enter program name'); $btn.prop('disabled', false).text('Add Program'); return false; }

      $.ajax({
        url: '<?php echo admin_url("student_sponsor_portal/add_university_program"); ?>',
        type: 'POST', dataType: 'json',
        data: { name: name }
      }).done(function(r){
        if(r && r.success){
          var $sel = $('#university_program_id');
          var id   = r.id || r.program_id || '';
          var txt  = r.name || name;
          if(id){
            $sel.append($('<option/>',{value:id, text:txt, selected:true, 'data-name': txt}));
            refreshSelectpicker($sel);
          }
          $('#addProgramModal').modal('hide'); $('#modal_program_name').val('');
          alert_float('success', r.message || 'Program added');
          recalcProfileCompletion();
        } else {
          alert(r && r.message ? r.message : 'Failed to add program');
        }
      }).fail(function(){ alert('Error adding program.'); })
        .always(function(){ $btn.prop('disabled', false).text('Add Program'); });

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
  } else {
    // For university students, disable admin-only functions
    window.addCountry = function(){ return false; };
    window.addUniversity = function(){ return false; };
    window.addProgram = function(){ return false; };
    window.addBank = function(){ return false; };
  }

  // Report cards - Load existing cards
  function loadReportCards(){
    var sid = <?php echo !empty($student['id']) ? (int)$student['id'] : 0; ?>;
    if(!sid) return;

    $.get('<?php echo admin_url("student_sponsor_portal/get_university_report_cards"); ?>/'+sid, function(resp){
      try{ resp = (typeof resp==='string') ? JSON.parse(resp) : resp; }catch(e){ resp = {success:false}; }
      var $tb = $('#reportCardsTable tbody').empty();
      if(resp && resp.success && resp.report_cards && resp.report_cards.length){
        var i=1;
        resp.report_cards.forEach(function(c){
          var size = c.file_size ? (c.file_size + ' bytes') : '';
          var dl = c.file_url || c.download_url || '<?php echo admin_url("student_sponsor_portal/download_university_report_card/"); ?>'+c.id;
          var uploadDate = c.upload_date || '';
          var monthYear = '';
          if(c.semester_end_month && c.semester_end_year){
            var months = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            monthYear = (months[parseInt(c.semester_end_month)] || c.semester_end_month) + ' ' + c.semester_end_year;
          }
          
          var actionsHtml = '<a href="'+dl+'" class="btn btn-xs btn-default" title="Download"><i class="fa fa-download"></i></a>';
          
          // Only show delete button for admins
          if (!isUniversityStudent) {
            actionsHtml += ' <button type="button" class="btn btn-xs btn-danger btn-del-card" title="Delete"><i class="fa fa-trash"></i></button>';
          }
          
          var tr = $('<tr/>',{'data-id':c.id}).append(
            $('<td/>',{text:i++}),
            $('<td/>').append($('<a/>',{href:dl, target:'_blank', rel:'noopener', text:(c.filename||c.display_name||('report_card_'+c.id)) })),
            $('<td/>',{text:c.report_card_term || c.current_term || 'N/A'}),
            $('<td/>',{text:monthYear}),
            $('<td/>',{text:uploadDate}),
            $('<td/>',{text:size}),
            $('<td/>').html(actionsHtml)
          );
          $tb.append(tr);
        });
      } else {
        $tb.append('<tr><td colspan="7" class="text-center">No report cards uploaded yet.</td></tr>');
      }
    }).fail(function(xhr, status, error){
      $('#reportCardsTable tbody').html('<tr><td colspan="7" class="text-center text-danger">Error loading report cards: ' + error + '</td></tr>');
    });
  }

  // Upload handler (AJAX)
  window.uploadReportCard = function(){
    var $btn = $('#uploadReportCardBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Uploading...');

    var fileInput = document.getElementById('report_card_file');
    if(!$('#filename').val()){ alert('Please enter a filename'); $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Upload Report Card'); return false; }
    if(!$('#report_card_term').val()){ alert('Please select a term'); $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Upload Report Card'); return false; }
    if(!$('#semester_end_month').val()){ alert('Please select semester end month'); $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Upload Report Card'); return false; }
    if(!$('#semester_end_year').val()){ alert('Please select semester end year'); $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Upload Report Card'); return false; }
    if(!fileInput || !fileInput.files || !fileInput.files[0]){ alert('Please select a file to upload'); $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Upload Report Card'); return false; }

    var formData = new FormData();
    formData.append('student_university_id', $('#rc_student_university_id').val());
    formData.append('filename', $('#filename').val());
    formData.append('report_card_term', $('#report_card_term').val());
    formData.append('semester_end_month', $('#semester_end_month').val());
    formData.append('semester_end_year', $('#semester_end_year').val());
    formData.append('report_card_file', fileInput.files[0]);

    // Add CSRF token if available
    if (typeof csrfData !== 'undefined' && csrfData && csrfData.token_name && csrfData.hash) {
      formData.append(csrfData.token_name, csrfData.hash);
    }

    $.ajax({
      url: '<?php echo admin_url("student_sponsor_portal/upload_university_report_card"); ?>',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      timeout: 30000
    }).done(function(r){
      if(r && r.success){
        alert_float('success', r.message || 'Report card uploaded successfully');
        $('#filename').val('');
        $('#report_card_term').val('');
        $('#semester_end_month').val('');
        $('#semester_end_year').val('<?php echo date('Y'); ?>');
        $('#report_card_file').val('');
        loadReportCards();
      } else {
        alert('Upload failed: '+(r && r.message ? r.message : 'Unknown error'));
      }
    }).fail(function(xhr){
      var errorMsg = 'Server error';
      try {
        var j = JSON.parse(xhr.responseText);
        if (j.message) errorMsg = j.message;
      } catch(e){}
      alert('Upload failed: ' + errorMsg);
    }).always(function(){
      $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Upload Report Card');
    });

    return false;
  };

  // Delete card handler (admin only)
  if (!isUniversityStudent) {
    window.deleteReportCard = function(id){
      if(!id) return false;
      if(!confirm('Are you sure you want to delete this report card?')) return false;

      $.post('<?php echo admin_url("student_sponsor_portal/delete_university_report_card"); ?>/'+id, {}, function(r){
        try{ r = (typeof r==='string')?JSON.parse(r):r; }catch(e){ r={success:false}; }
        if(r && r.success){
          alert_float('success','Report card deleted successfully');
          loadReportCards();
        }
        else {
          alert('Delete failed: ' + (r.message || 'Unknown error'));
        }
      }).fail(function(){
        alert('Error deleting report card');
      });

      return false;
    };

    // Delete card click
    $(document).on('click', '.btn-del-card', function(){
      var $tr = $(this).closest('tr');
      var id = $tr.data('id');
      if(!id) return;
      return deleteReportCard(id);
    });
  }

  $(function(){
    // init selectpicker
    setTimeout(function(){ if($.fn && $.fn.selectpicker){ $('.selectpicker').selectpicker('destroy').selectpicker(); } }, 100);

    // phone code update
    $('#country_id').on('change', function(){
      var code = $(this).find('option:selected').data('phone-code') || '+1';
      $('#phone-code-display').text(code);
      recalcProfileCompletion();
    });

    // profile photo preview
    $('#profile_photo').on('change', function(){
      var f = this.files && this.files[0]; if(!f) return;
      var reader = new FileReader();
      reader.onload = function(e){ $('.current-photo img').attr('src', e.target.result).show(); };
      reader.readAsDataURL(f);
    });

    // age + completion
    $('#dob').on('change input', function(){ calculateAge(); recalcProfileCompletion(); });
    setTimeout(calculateAge, 100);
    $(document).on('input change', '#university-student-form input, #university-student-form select, #university-student-form textarea', function(){
      $(this).removeClass('has-error field-error'); recalcProfileCompletion();
    });

    // report cards
    <?php if(isset($student) && !empty($student['id'])): ?>
      $('a[href="#report-cards"]').on('shown.bs.tab', function() { loadReportCards(); });
      setTimeout(function(){ if ($('#report-cards').hasClass('active')) { loadReportCards(); } }, 100);
    <?php endif; ?>

    // submit guard (remember active tab)
    var submitting = false;
    $('#university-student-form').on('submit', function(e){
      if(submitting){ e.preventDefault(); return false; }

      // remember current tab
      var currentTab = $('.nav-tabs li.active a').attr('href') || '#student-info';
      $('#active_tab').val(currentTab);

      // Validate ONLY the main form (exclude the report-cards pane entirely)
      var isValid = true;
      $('#university-student-form .tab-pane:not(#report-cards)').find('input[required], select[required], textarea[required]').each(function(){
        if(!$(this).val()){
          isValid = false; $(this).addClass('has-error field-error');
          var $pane = $(this).closest('.tab-pane');
          if($pane.length){ $('a[href="#'+$pane.attr('id')+'"]').tab('show'); }
          return false; // break each on first error
        } else { $(this).removeClass('has-error field-error'); }
      });

      if(!isValid){ e.preventDefault(); return false; }

      submitting = true;
      var saveText = isUniversityStudent ? 'Updating Profile...' : 'Saving...';
      $('#btn-save-student').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + saveText);
    });

    // restore tab after reload (server echoes active_tab)
    var initialTab = $('#active_tab').val();
    if (initialTab && $('a[href="'+initialTab+'"]').length) {
      $('a[href="'+initialTab+'"]').tab('show');
    }

    // Show completion calculation after page load
    setTimeout(recalcProfileCompletion, 200);
  });
})(jQuery);
</script>