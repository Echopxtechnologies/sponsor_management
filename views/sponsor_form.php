<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body sponsor-form-wrapper">

            <!-- Header -->
            <div class="row">
              <div class="col-md-8">
                <h4 class="customer-profile-group-heading">
                  <i class="fa fa-user-plus"></i>
                  <?php if(isset($is_sponsor) && $is_sponsor): ?>
                    My Profile
                  <?php else: ?>
                    <?= !empty($sponsor) ? 'Edit Sponsor' : 'Add New Sponsor'; ?>
                  <?php endif; ?>
                </h4>
                <?php if(isset($is_sponsor) && $is_sponsor): ?>
                  <p class="text-muted"><i class="fa fa-info-circle"></i> Update your sponsor information and view your sponsored students</p>
                <?php endif; ?>
              </div>
              <div class="col-md-4 text-right">
                <?php if(!isset($is_sponsor) || !$is_sponsor): ?>
                  <a href="<?= admin_url('student_sponsor_portal/sponsors'); ?>" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Back to List
                  </a>
                <?php endif; ?>
              </div>
            </div>
            <hr class="hr-panel-heading">

            <?php
              if (isset($is_sponsor) && $is_sponsor && isset($sponsor) && !empty($sponsor['id'])) {
                  // For sponsors accessing their portal - include sponsor ID in URL
                  $form_action = admin_url('student_sponsor_portal/sponsor_form/' . (int)$sponsor['id']);
              } else {
                  // For admins using normal form - use base URL (existing behavior)
                  $form_action = admin_url('student_sponsor_portal/sponsor_form');
              }

              echo form_open($form_action, ['id' => 'sponsor-form']);
            ?>
            <?php if (!empty($sponsor['id'])): ?>
              <input type="hidden" name="sponsor_id" value="<?= (int)$sponsor['id']; ?>">
            <?php endif; ?>

            <!-- remember last active tab -->
            <input type="hidden" name="active_tab" id="active_tab" value="<?php echo html_escape($this->input->post('active_tab') ?? ($active_tab ?? '#tab_basic')); ?>">

            <!-- new-on-save helpers (admin only) -->
            <?php if(!isset($is_sponsor) || !$is_sponsor): ?>
              <input type="hidden" name="new_country_name" id="new_country_name">
              <input type="hidden" name="new_country_phone_code" id="new_country_phone_code">
            <?php endif; ?>

            <!-- Tabs -->
            <div class="horizontal-tabs">
              <ul class="nav nav-tabs nav-tabs-horizontal sponsor-form-tabs" role="tablist">
                <li class="active"><a href="#tab_basic" data-toggle="tab"><i class="fa fa-user"></i> 
                  <?php echo (isset($is_sponsor) && $is_sponsor) ? 'My Info' : 'Basic Info'; ?>
                </a></li>
                <li><a href="#tab_bank" data-toggle="tab"><i class="fa fa-bank"></i> Banking</a></li>
                <?php if(!empty($sponsor['id'])): ?>
                  <li><a href="#tab_students" data-toggle="tab"><i class="fa fa-graduation-cap"></i> 
                    <?php echo (isset($is_sponsor) && $is_sponsor) ? 'My Students' : 'Sponsored Students'; ?>
                  </a></li>
                <?php endif; ?>
                <?php if(!isset($is_sponsor) || !$is_sponsor): ?>
                  <li><a href="#tab_sponsorship" data-toggle="tab"><i class="fa fa-calendar"></i> Sponsorship</a></li>
                  <li><a href="#tab_staff" data-toggle="tab"><i class="fa fa-user-circle"></i> Staff Account</a></li>
                <?php endif; ?>
              </ul>
            </div>

            <div class="tab-content sponsor-form-content">
              <!-- CONSOLIDATED BASIC INFO -->
              <div class="tab-pane active" id="tab_basic">
                
                <!-- Basic Information Section -->
                <h5><i class="fa fa-user"></i> Basic Information</h5>
                <hr>
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
                
                <div class="row">
                  <div class="col-md-12">
                    <?= render_input('sponsor_occupation', 'Occupation', html_escape($sponsor['sponsor_occupation'] ?? '')); ?>
                  </div>
                </div>

                <!-- Contact Information Section -->
                <h5><i class="fa fa-phone"></i> Contact Information</h5>
                <hr>
                <div class="row">
                  <div class="col-md-6">
                    <?= render_input('email', 'Email Address', html_escape($sponsor['email'] ?? ''), 'email'); ?>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="country_id" class="control-label">Country</label>
                      <?php if(isset($is_sponsor) && $is_sponsor): ?>
                        <!-- Simple dropdown for sponsors -->
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
                      <?php endif; ?>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="contact_no" class="control-label">Phone Number</label>
                      <div class="input-group">
                        <div class="input-group-addon" id="phone-code-display">
                          <?php
                            $phone_code = '+1'; // Default to US
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
                        <input type="text" name="contact_no" id="contact_no" class="form-control"
                          value="<?php echo html_escape($sponsor['contact_no'] ?? ''); ?>" 
                          placeholder="Phone number">
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="state_id" class="control-label">State/Province</label>
                      <input 
            type="text" 
            name="state_name" 
            id="state_name" 
            class="form-control" 
            placeholder="Enter State/Province" 
            value="<?php echo isset($sponsor['state_name']) ? html_escape($sponsor['state_name']) : ''; ?>"
            maxlength="100"
        />
           <input type="hidden" name="state_id" id="state_id" value="<?php echo isset($sponsor['state_id']) ? (int)$sponsor['state_id'] : ''; ?>">
                        <?php if(!empty($states)): foreach($states as $s): ?>
                          <?php
                            $state_selected = (isset($sponsor) && (int)($sponsor['state_id'] ?? 0) === (int)$s['id']);
                          ?>
                         
                        <?php endforeach; endif; ?>
                      </select>
                    </div>
                  </div>
                </div>

                <?= render_textarea('address', 'Address', html_escape($sponsor['address'] ?? '')); ?>

                <div class="row">
                  <div class="col-md-6">
                    <?= render_input('city', 'City', html_escape($sponsor['city'] ?? '')); ?>
                  </div>
                  <div class="col-md-6">
                    <?= render_input('zip', 'Postal Code', html_escape($sponsor['zip'] ?? '')); ?>
                  </div>
                </div>
              </div>

              <!-- Bank Tab -->
              <div class="tab-pane" id="tab_bank">
                <div class="row">
                  <!-- Bank + Add button -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="bank_id">Bank</label>

                      <?php if(isset($is_sponsor) && $is_sponsor): ?>
                        <!-- Simple dropdown for sponsors -->
                        <select name="bank_id" id="bank_id" class="form-control selectpicker" data-live-search="true" data-none-selected-text="Select Bank">
                          <option value="">Select Bank</option>
                          <?php if(!empty($banks)): foreach($banks as $b): ?>
                            <?php
                              $bank_selected = (isset($sponsor) && (int)($sponsor['bank_id'] ?? 0) === (int)$b['id']);
                            ?>
                            <option value="<?php echo (int)$b['id']; ?>" <?php echo $bank_selected ? 'selected' : ''; ?>>
                              <?php echo html_escape($b['name']); ?>
                            </option>
                          <?php endforeach; endif; ?>
                        </select>
                      <?php else: ?>
                        <!-- Admin version with add button -->
                        <div class="bank-select-wrapper">
                          <select name="bank_id" id="bank_id"
                                  class="selectpicker form-control"
                                  data-live-search="true" data-width="100%">
                              <option value="">Select Bank</option>
                              <?php foreach ($banks as $b): ?>
                              <option value="<?= (int)$b['id']; ?>"
                                  <?= !empty($sponsor['bank_id']) && (int)$sponsor['bank_id']===(int)$b['id'] ? 'selected' : '' ?>>
                                  <?= html_escape($b['name']); ?>
                              </option>
                              <?php endforeach; ?>
                          </select>

                          <button type="button" class="btn btn-default btn-sm add-new-btn" id="btn-add-bank"
                                  title="Add Bank">
                              <i class="fa fa-plus"></i>
                          </button>
                        </div>
                      <?php endif; ?>
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

<!-- SPONSORED STUDENTS TAB - READ-ONLY DISPLAY -->
<?php if(!empty($sponsor['id'])): ?>
<div class="tab-pane" id="tab_students">
  <div class="row">
    <div class="col-md-12">
      <h5><i class="fa fa-graduation-cap"></i> 
        <?php echo (isset($is_sponsor) && $is_sponsor) ? 'My Sponsored Students' : 'Sponsored Students'; ?>
      </h5>
      <hr>
      
      <div class="alert alert-info">
        <i class="fa fa-info-circle"></i> 
        <strong>Note:</strong> Students appear here when transactions are created for them. 
        <?php if(!isset($is_sponsor) || !$is_sponsor): ?>
          To sponsor students, create transactions in the Transaction Management section.
        <?php else: ?>
          Contact administration to sponsor new students or modify sponsorships.
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php
  // Get the same data structure used in sponsors list
  $school_students_count = (int)($sponsor['school_students_count'] ?? 0);
  $university_students_count = (int)($sponsor['university_students_count'] ?? 0);
  $total_students_count = $school_students_count + $university_students_count;
  
  // Get student names data (same as list view)
  $student_names_data = $sponsor['sponsored_student_names'] ?? [
    'school_students' => [],
    'university_students' => [],
    'school_names_display' => '',
    'university_names_display' => '',
    'school_total_count' => 0,
    'university_total_count' => 0,
    'school_has_more' => false,
    'university_has_more' => false
  ];

  // Get financial data
  $total_commitment = (float)($sponsor['total_commitment'] ?? 0);
  $total_paid = (float)($sponsor['total_paid'] ?? 0);
  $total_balance = $total_commitment - $total_paid;
  $total_transactions = (int)($sponsor['total_transactions'] ?? 0);
  ?>

  <!-- Summary Statistics Row -->
  <div class="row" style="margin-bottom: 25px;">
    <div class="col-md-3">
      <div class="alert alert-primary text-center">
        <i class="fa fa-school fa-2x"></i>
        <h4 style="margin: 10px 0 5px 0;"><?php echo $school_students_count; ?></h4>
        <small>School Students</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="alert alert-info text-center">
        <i class="fa fa-university fa-2x"></i>
        <h4 style="margin: 10px 0 5px 0;"><?php echo $university_students_count; ?></h4>
        <small>University Students</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="alert alert-success text-center">
        <i class="fa fa-users fa-2x"></i>
        <h4 style="margin: 10px 0 5px 0;"><?php echo $total_students_count; ?></h4>
        <small>Total Students</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="alert alert-warning text-center">
        <i class="fa fa-money fa-2x"></i>
        <h4 style="margin: 10px 0 5px 0;">₹<?php echo number_format($total_commitment, 0); ?></h4>
        <small>Total Commitment</small>
      </div>
    </div>
  </div>

  <?php if($total_students_count > 0): ?>
    <!-- School Students Section -->
    <?php if($school_students_count > 0): ?>
    <div class="student-section">
      <div class="row">
        <div class="col-md-12">
          <div class="panel panel-primary">
            <div class="panel-heading">
              <h6 class="panel-title">
                <i class="fa fa-school"></i> School Students 
                <span class="badge" style="background: rgba(255,255,255,0.3);"><?php echo $school_students_count; ?></span>
              </h6>
            </div>
            <div class="panel-body">
              <?php if(!empty($student_names_data['school_names_display'])): ?>
                <div class="student-names-section">
                  <h6><i class="fa fa-users text-primary"></i> Sponsored School Students:</h6>
                  <div class="student-names-display">
                    <p class="lead" style="font-size: 14px; line-height: 1.6; margin-bottom: 15px; color: #2c5282;">
                      <?php echo htmlspecialchars($student_names_data['school_names_display']); ?>
                    </p>
                  </div>
                  
                  <?php if($student_names_data['school_has_more']): ?>
                  <div class="load-more-section">
                    <button type="button" class="btn btn-sm btn-primary load-detailed-students" 
                            data-sponsor-id="<?php echo (int)$sponsor['id']; ?>" 
                            data-type="school">
                      <i class="fa fa-list"></i> View All School Students (<?php echo $student_names_data['school_total_count']; ?> total)
                    </button>
                  </div>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <div class="alert alert-info">
                  <i class="fa fa-info-circle"></i> 
                  <?php echo $school_students_count; ?> school student<?php echo $school_students_count > 1 ? 's' : ''; ?> sponsored. 
                  <button type="button" class="btn btn-xs btn-primary load-detailed-students" 
                          data-sponsor-id="<?php echo (int)$sponsor['id']; ?>" 
                          data-type="school">
                    <i class="fa fa-refresh"></i> Load Student Details
                  </button>
                </div>
              <?php endif; ?>
              
              <!-- Detailed students container -->
              <div class="detailed-students-container" id="school-detailed-container" style="display: none;">
                <hr>
                <div class="detailed-students-content">
                  <div class="text-center">
                    <i class="fa fa-spinner fa-spin"></i> Loading detailed student information...
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- University Students Section -->
    <?php if($university_students_count > 0): ?>
    <div class="student-section">
      <div class="row">
        <div class="col-md-12">
          <div class="panel panel-info">
            <div class="panel-heading">
              <h6 class="panel-title">
                <i class="fa fa-university"></i> University Students 
                <span class="badge" style="background: rgba(255,255,255,0.3);"><?php echo $university_students_count; ?></span>
              </h6>
            </div>
            <div class="panel-body">
              <?php if(!empty($student_names_data['university_names_display'])): ?>
                <div class="student-names-section">
                  <h6><i class="fa fa-users text-info"></i> Sponsored University Students:</h6>
                  <div class="student-names-display">
                    <p class="lead" style="font-size: 14px; line-height: 1.6; margin-bottom: 15px; color: #1f5582;">
                      <?php echo htmlspecialchars($student_names_data['university_names_display']); ?>
                    </p>
                  </div>
                  
                  <?php if($student_names_data['university_has_more']): ?>
                  <div class="load-more-section">
                    <button type="button" class="btn btn-sm btn-info load-detailed-students" 
                            data-sponsor-id="<?php echo (int)$sponsor['id']; ?>" 
                            data-type="university">
                      <i class="fa fa-list"></i> View All University Students (<?php echo $student_names_data['university_total_count']; ?> total)
                    </button>
                  </div>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <div class="alert alert-info">
                  <i class="fa fa-info-circle"></i> 
                  <?php echo $university_students_count; ?> university student<?php echo $university_students_count > 1 ? 's' : ''; ?> sponsored. 
                  <button type="button" class="btn btn-xs btn-info load-detailed-students" 
                          data-sponsor-id="<?php echo (int)$sponsor['id']; ?>" 
                          data-type="university">
                    <i class="fa fa-refresh"></i> Load Student Details
                  </button>
                </div>
              <?php endif; ?>
              
              <!-- Detailed students container -->
              <div class="detailed-students-container" id="university-detailed-container" style="display: none;">
                <hr>
                <div class="detailed-students-content">
                  <div class="text-center">
                    <i class="fa fa-spinner fa-spin"></i> Loading detailed student information...
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Financial Summary Section -->
    <?php if($total_commitment > 0 || $total_paid > 0): ?>
    <div class="row" style="margin-top: 25px;">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h6 class="panel-title"><i class="fa fa-bar-chart"></i> Financial Summary</h6>
          </div>
          <div class="panel-body">
            <div class="row">
              <div class="col-md-3">
                <div class="metric-box" style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 4px; border-left: 4px solid #007bff;">
                  <h4 style="margin: 0 0 5px 0; color: #007bff;">₹<?php echo number_format($total_commitment, 2); ?></h4>
                  <small class="text-muted">Total Commitment</small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="metric-box" style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 4px; border-left: 4px solid #28a745;">
                  <h4 style="margin: 0 0 5px 0; color: #28a745;">₹<?php echo number_format($total_paid, 2); ?></h4>
                  <small class="text-muted">Amount Paid</small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="metric-box" style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 4px; border-left: 4px solid <?php echo $total_balance > 0 ? '#ffc107' : '#17a2b8'; ?>;">
                  <h4 style="margin: 0 0 5px 0; color: <?php echo $total_balance > 0 ? '#ffc107' : '#17a2b8'; ?>;">₹<?php echo number_format(abs($total_balance), 2); ?></h4>
                  <small class="text-muted"><?php echo $total_balance > 0 ? 'Balance Due' : 'Overpaid'; ?></small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="metric-box" style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 4px; border-left: 4px solid #6c757d;">
                  <h4 style="margin: 0 0 5px 0; color: #6c757d;"><?php echo $total_transactions; ?></h4>
                  <small class="text-muted">Total Transactions</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <?php if(!isset($is_sponsor) || !$is_sponsor): ?>
    <div class="row" style="margin-top: 20px;">
      <div class="col-md-12 text-center">
        <div class="btn-group" role="group">
          <a href="<?php echo admin_url('student_sponsor_portal/transactions?sponsor_id=' . (int)$sponsor['id']); ?>" 
             class="btn btn-primary">
            <i class="fa fa-money"></i> View All Transactions
          </a>
          <a href="<?php echo admin_url('student_sponsor_portal/transactions/create?sponsor_id=' . (int)$sponsor['id']); ?>" 
             class="btn btn-success">
            <i class="fa fa-plus"></i> Create New Transaction
          </a>
        </div>
      </div>
    </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- No Students Found -->
    <div class="row">
      <div class="col-md-12">
        <div class="alert alert-warning text-center" style="padding: 40px;">
          <i class="fa fa-graduation-cap fa-4x text-muted"></i>
          <h4 class="text-muted" style="margin-top: 20px;">No sponsored students found</h4>
          <p class="text-muted">This sponsor doesn't have any students linked through transactions yet.</p>
          <?php if(!isset($is_sponsor) || !$is_sponsor): ?>
          <div style="margin-top: 20px;">
            <a href="<?php echo admin_url('student_sponsor_portal/transactions/create?sponsor_id=' . (int)$sponsor['id']); ?>" 
               class="btn btn-primary btn-lg">
              <i class="fa fa-plus"></i> Create First Transaction
            </a>
          </div>
          <?php else: ?>
          <p class="text-info" style="margin-top: 15px;">
            <i class="fa fa-phone"></i> Contact administration to sponsor students.
          </p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>

              <!-- SPONSORSHIP (Admin Only) -->
              <?php if(!isset($is_sponsor) || !$is_sponsor): ?>
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
                      <small class="text-muted">Active status is managed automatically based on transactions and dates.</small>
                    </div>
                  </div>
                </div>
              </div>

              <!-- STAFF (Admin Only) -->
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
                    <?= render_input('staff_lastname', 'Last Name'); html_escape($sponsor['name'] ?? ''); ?>
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
              <?php endif; ?>
            </div><!-- /tab-content -->

            <!-- Actions -->
            <div class="form-group" style="margin-top:20px;border-top:1px solid #eee;padding-top:20px;">
              <button type="submit" class="btn btn-primary btn-lg" id="save-sponsor-btn">
                <i class="fa fa-save"></i> 
                <?php if(isset($is_sponsor) && $is_sponsor): ?>
                  Update My Profile
                <?php else: ?>
                  <?= !empty($sponsor) ? 'Update Sponsor' : 'Save Sponsor'; ?>
                <?php endif; ?>
              </button>
              
              <?php if(!isset($is_sponsor) || !$is_sponsor): ?>
                <a href="<?= admin_url('student_sponsor_portal/sponsors'); ?>" class="btn btn-default btn-lg">
                  <i class="fa fa-times"></i> Cancel
                </a>
              <?php endif; ?>
            </div>

            <?= form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modals (Admin Only) -->
<?php if(!isset($is_sponsor) || !$is_sponsor): ?>

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

<?php endif; ?>

<!-- Student Details Modal (Available to both admin and sponsors) -->
<div class="modal fade" id="studentDetailsModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
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

<style>
/* Enhanced Sponsor Form Styling - Aligned with School Form */

/* Main wrapper */
.sponsor-form-wrapper {
  background: #fff;
  border-radius: 4px;
}

/* Tab Navigation */
.sponsor-form-wrapper .sponsor-form-tabs {
  border-bottom: 2px solid #e8e8e8;
  background: linear-gradient(to bottom, #f8f9fa 0%, #f1f3f4 100%);
  margin: 0 0 0 0;
  padding: 0 15px;
  border-radius: 4px 4px 0 0;
  overflow-x: auto;
  white-space: nowrap;
}

.sponsor-form-wrapper .sponsor-form-tabs > li {
  margin-bottom: -2px;
  position: relative;
  display: inline-block;
  float: none;
}

.sponsor-form-wrapper .sponsor-form-tabs > li > a {
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

.sponsor-form-wrapper .sponsor-form-tabs > li > a:hover {
  background: rgba(255, 255, 255, 0.8);
  border-color: #ddd #ddd transparent;
  color: #337ab7;
  text-decoration: none;
}

.sponsor-form-wrapper .sponsor-form-tabs > li.active > a,
.sponsor-form-wrapper .sponsor-form-tabs > li.active > a:hover,
.sponsor-form-wrapper .sponsor-form-tabs > li.active > a:focus {
  color: #337ab7;
  background: #fff;
  border: 1px solid #ddd;
  border-bottom-color: #fff;
  cursor: default;
  font-weight: 600;
  box-shadow: 0 -2px 4px rgba(0,0,0,0.1);
  text-decoration: none;
}

.sponsor-form-wrapper .sponsor-form-tabs > li.active > a::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  right: 0;
  height: 2px;
  background: #337ab7;
}

/* Tab Content */
.sponsor-form-wrapper .sponsor-form-content {
  background: #fff;
  padding: 25px;
  border: 1px solid #ddd;
  border-top: none;
  border-radius: 0 0 4px 4px;
  min-height: 400px;
}

/* Section Headers in tabs */
.sponsor-form-wrapper .sponsor-form-content h5 {
  color: #337ab7;
  margin-top: 25px;
  margin-bottom: 12px;
  font-weight: 600;
  font-size: 16px;
  border-left: 4px solid #337ab7;
  padding-left: 12px;
}

.sponsor-form-wrapper .sponsor-form-content h5:first-child {
  margin-top: 0;
}

.sponsor-form-wrapper .sponsor-form-content h6 {
  color: #495057;
  margin-top: 20px;
  margin-bottom: 10px;
  font-weight: 600;
  font-size: 14px;
  border-left: 3px solid #6c757d;
  padding-left: 10px;
}

.sponsor-form-wrapper .sponsor-form-content hr {
  margin: 15px 0 20px;
  border-color: #e8e8e8;
}

/* Form Controls */
.sponsor-form-wrapper .form-group {
  margin-bottom: 20px;
}

.sponsor-form-wrapper .form-control {
  border: 1px solid #d0d0d0;
  border-radius: 4px;
  transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
  height: 36px;
  font-size: 14px;
}

.sponsor-form-wrapper textarea.form-control {
  height: auto;
  min-height: 90px;
}

.sponsor-form-wrapper .form-control:focus {
  border-color: #337ab7;
  box-shadow: 0 0 5px rgba(51, 122, 183, 0.3);
}

.sponsor-form-wrapper .control-label {
  font-weight: 500;
  color: #333;
  margin-bottom: 6px;
  font-size: 13px;
}

/* Select Wrappers - Aligned with school form */
.sponsor-form-wrapper .country-select-wrapper,
.sponsor-form-wrapper .bank-select-wrapper {
  position: relative;
  display: flex;
  align-items: stretch;
}

.sponsor-form-wrapper .country-select-wrapper .bootstrap-select,
.sponsor-form-wrapper .bank-select-wrapper .bootstrap-select {
  flex: 1;
  margin-right: 5px;
  width: auto !important;
}

.sponsor-form-wrapper .add-new-btn {
  border-left: 0;
  border-radius: 0 4px 4px 0 !important;
  padding: 8px 12px;
  margin-left: 0;
  z-index: 5;
  flex-shrink: 0;
  height: 36px;
  background: #f8f9fa;
  border-color: #d0d0d0;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 36px;
}

.sponsor-form-wrapper .add-new-btn:hover {
  background-color: #337ab7;
  color: #fff;
  border-color: #2e6da4;
}

.sponsor-form-wrapper .country-select-wrapper .btn-group.bootstrap-select,
.sponsor-form-wrapper .bank-select-wrapper .btn-group.bootstrap-select {
  flex: 1;
  width: auto !important;
  display: flex !important;
}

.sponsor-form-wrapper .country-select-wrapper .btn-group.bootstrap-select .btn,
.sponsor-form-wrapper .bank-select-wrapper .btn-group.bootstrap-select .btn {
  width: 100%;
  border-top-right-radius: 0 !important;
  border-bottom-right-radius: 0 !important;
  text-align: left;
  height: 36px;
  line-height: 1.4;
}

/* Input Group Styling for Phone Code - KEY ADDITION */
.sponsor-form-wrapper .input-group {
  display: flex;
  width: 100%;
}

.sponsor-form-wrapper .input-group-addon {
  background: #f8f9fa;
  border: 1px solid #d0d0d0;
  border-right: 0;
  padding: 8px 12px;
  border-radius: 4px 0 0 4px;
  font-weight: 500;
  color: #555;
  white-space: nowrap;
  display: flex;
  align-items: center;
  height: 36px;
  min-width: 50px;
  justify-content: center;
}

.sponsor-form-wrapper .input-group .form-control {
  border-left: 0;
  border-radius: 0 4px 4px 0;
  flex: 1;
}

/* Consistent Bootstrap Select Heights */
.sponsor-form-wrapper .bootstrap-select > .dropdown-toggle {
  height: 36px !important;
  line-height: 1.4 !important;
  padding: 8px 12px !important;
}

.sponsor-form-wrapper .bootstrap-select .filter-option-inner-inner {
  line-height: 1.4;
}

/* Row spacing improvements */
.sponsor-form-wrapper .row {
  margin-left: -10px;
  margin-right: -10px;
}

.sponsor-form-wrapper .row > [class*="col-"] {
  padding-left: 10px;
  padding-right: 10px;
}

/* Student Display Styles */
.students-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 15px;
  padding: 0 5px;
}

.student-card {
  border: 2px solid #28a745;
  border-radius: 8px;
  padding: 15px;
  background: #f8fff9;
  position: relative;
  transition: all 0.3s ease;
}

.student-card:hover {
  box-shadow: 0 4px 12px rgba(40,167,69,0.2);
  transform: translateY(-2px);
}

.student-card-header {
  display: flex;
  align-items: flex-start;
  margin-bottom: 12px;
}

.student-status-icon {
  margin-right: 12px;
  color: #28a745;
  font-size: 18px;
  line-height: 1;
  margin-top: 2px;
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
  margin-right: 5px;
}

.student-actions {
  margin-top: 10px;
  text-align: center;
}

.student-actions .btn {
  font-size: 11px;
  padding: 4px 8px;
  margin: 2px;
}

.transaction-summary {
  background: #f8f9fa;
  border-radius: 4px;
  padding: 8px 10px;
  margin-top: 8px;
  font-size: 11px;
}

.transaction-summary .summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 2px;
}

.transaction-summary .summary-row:last-child {
  margin-bottom: 0;
  font-weight: 600;
}

/* Error States */
.sponsor-form-wrapper .has-error,
.sponsor-form-wrapper .field-error {
  border-color: #d9534f !important;
  box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 6px rgba(217,83,79,.6) !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .sponsor-form-wrapper .sponsor-form-tabs {
    padding: 0 10px;
  }
  
  .sponsor-form-wrapper .sponsor-form-tabs > li > a {
    padding: 10px 12px;
    font-size: 12px;
  }
  
  .sponsor-form-wrapper .sponsor-form-content {
    padding: 15px;
  }
  
  .students-grid {
    grid-template-columns: 1fr;
    gap: 10px;
  }
  
  .student-card {
    padding: 12px;
  }
  
  .sponsor-form-wrapper .country-select-wrapper,
  .sponsor-form-wrapper .bank-select-wrapper {
    flex-direction: column;
  }
  
  .sponsor-form-wrapper .country-select-wrapper .bootstrap-select,
  .sponsor-form-wrapper .bank-select-wrapper .bootstrap-select {
    margin-right: 0;
    margin-bottom: 5px;
  }
  
  .sponsor-form-wrapper .add-new-btn {
    border-radius: 4px !important;
    width: 100%;
  }

  .sponsor-form-wrapper .row {
    margin-left: -5px;
    margin-right: -5px;
  }

  .sponsor-form-wrapper .row > [class*="col-"] {
    padding-left: 5px;
    padding-right: 5px;
  }
}

/* Button styling */
.sponsor-form-wrapper .btn {
  border-radius: 4px;
  font-weight: 500;
  height: 36px;
  line-height: 1.4;
  padding: 8px 16px;
}

.sponsor-form-wrapper .btn-lg {
  height: auto;
  padding: 12px 24px;
  font-size: 16px;
}

/* Ensure consistent styling across different contexts */
.sponsor-form-wrapper * {
  box-sizing: border-box;
}

/* Override any conflicting Perfex styles */
.sponsor-form-wrapper .nav-tabs {
  border-bottom: 2px solid #e8e8e8 !important;
}

.sponsor-form-wrapper .nav-tabs > li.active > a {
  border-bottom-color: #fff !important;
}

.sponsor-form-wrapper .tab-content {
  border-top: none !important;
}
</style>

<script>
if (typeof alert_float !== 'function') { 
  window.alert_float = function(type, message){ alert(message); }; 
}

(function($){
  // Check if user is a sponsor - using passed data from controller
  var isSponsor = <?php echo json_encode(isset($is_sponsor) && $is_sponsor); ?>;
  var currentSponsorId = <?php echo json_encode(!empty($sponsor['id']) ? (int)$sponsor['id'] : 0); ?>;

  function refreshSelectpicker($el){
    if ($.fn.selectpicker) { $el.selectpicker('refresh'); }
  }


function updatePhoneCode() {
  var $countrySelect = $('#country_id');
  var selectedValue = $countrySelect.val();
  
  console.log('updatePhoneCode called, selected value:', selectedValue);
  
  if (!selectedValue) {
    // No country selected, use default
    var code = '+1';
    $('#phone-code-display').text(code);
    return;
  }
  
  // Get the phone code from the original select element (not the Bootstrap Select UI)
  var phoneCode = null;
  
  // Method 1: Direct lookup from the original select element
  $countrySelect.find('option').each(function() {
    if ($(this).val() == selectedValue) {
      phoneCode = $(this).attr('data-phone-code') || $(this).data('phone-code');
      console.log('Found phone code via method 1:', phoneCode, 'for country:', selectedValue);
      return false; // break the loop
    }
  });
  
  // Method 2: Try getting from currently selected option
  if (!phoneCode) {
    var $selectedOption = $countrySelect.find('option:selected');
    phoneCode = $selectedOption.attr('data-phone-code') || $selectedOption.data('phone-code');
    console.log('Found phone code via method 2:', phoneCode);
  }
  
  // Method 3: Try getting from option with matching value
  if (!phoneCode) {
    var $targetOption = $countrySelect.find('option[value="' + selectedValue + '"]');
    phoneCode = $targetOption.attr('data-phone-code') || $targetOption.data('phone-code');
    console.log('Found phone code via method 3:', phoneCode);
  }
  
  // Fallback - parse from option text if data attribute is missing
  if (!phoneCode) {
    $countrySelect.find('option').each(function() {
      if ($(this).val() == selectedValue) {
        var optionText = $(this).text();
        var match = optionText.match(/\(([+]?\d+)\)$/);
        if (match) {
          phoneCode = match[1];
          if (!phoneCode.startsWith('+')) {
            phoneCode = '+' + phoneCode;
          }
          console.log('Found phone code via text parsing:', phoneCode);
        }
        return false;
      }
    });
  }
  
  // Final fallback
  if (!phoneCode || phoneCode === '' || phoneCode === 'undefined') {
    phoneCode = '+1';
    console.log('Using fallback phone code:', phoneCode);
  }
  
  // Update the display
  $('#phone-code-display').text(phoneCode);
  console.log('Final phone code set to:', phoneCode, 'for country ID:', selectedValue);
}

// Event handlers with better Bootstrap Select compatibility
$(document).ready(function() {
  // Initialize selectpicker first
  if ($.fn.selectpicker) {
    $('.selectpicker').selectpicker();
  }
  
  // Bind events after selectpicker is initialized
  $('#country_id').on('change', function() {
    console.log('Country changed (change event):', $(this).val());
    setTimeout(updatePhoneCode, 50); // Small delay for Bootstrap Select
  });
  
  $('#country_id').on('changed.bs.select', function() {
    console.log('Country changed (bootstrap-select event):', $(this).val());
    setTimeout(updatePhoneCode, 50); // Small delay
  });
  
  // Load states for selected country (admin only)
  $('#country_id').on('change changed.bs.select', function(){
    if (!isSponsor) {
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
    }
  });
  
  // Initialize phone code after everything is loaded
  setTimeout(function() {
    updatePhoneCode();
  }, 500);
});
  /* -------- Transaction-based Student Display Functions -------- */
  function loadSponsoredStudents() {
    if (!currentSponsorId) {
      $('#sponsored-students-display').html(
        '<div class="alert alert-info">' +
        '<i class="fa fa-info-circle"></i> ' +
        'Save the sponsor first to view sponsored students.' +
        '</div>'
      );
      return;
    }

    $.ajax({
      url: '<?= admin_url("student_sponsor_portal/get_sponsored_students_with_transactions"); ?>',
      type: 'GET',
      data: { sponsor_id: currentSponsorId },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          displaySponsoredStudents(response.data);
          displaySponsorshipStatistics(response.statistics);
        } else {
          $('#sponsored-students-display').html(
            '<div class="alert alert-warning">' +
            '<i class="fa fa-exclamation-triangle"></i> ' +
            'Error loading sponsored students: ' + (response.message || 'Unknown error') +
            '</div>'
          );
        }
      },
      error: function(xhr, status, error) {
        console.error('AJAX Error:', xhr.responseText);
        $('#sponsored-students-display').html(
          '<div class="alert alert-danger">' +
          '<i class="fa fa-exclamation-circle"></i> ' +
          'Error loading sponsored students. Please try again.' +
          '</div>'
        );
      }
    });
  }

  function displaySponsoredStudents(students) {
    let html = '';
    
    if (!students || students.length === 0) {
      html = '<div class="alert alert-info">' +
             '<i class="fa fa-info-circle"></i> ' +
             '<strong>No sponsored students found.</strong><br>' +
             'Students will appear here when transactions are created for them.' +
             '</div>';
    } else {
      // Group students by type
      const schoolStudents = students.filter(s => s.student_type === 'school');
      const universityStudents = students.filter(s => s.student_type === 'university');
      
      if (schoolStudents.length > 0) {
        html += '<div class="student-section">';
        html += '<h6 class="section-header"><i class="fa fa-school"></i> School Students <span class="badge badge-primary">' + schoolStudents.length + '</span></h6>';
        html += '<div class="students-grid">';
        
        schoolStudents.forEach(function(student) {
          html += createSponsoredStudentCard(student);
        });
        
        html += '</div></div>';
      }
      
      if (universityStudents.length > 0) {
        html += '<div class="student-section">';
        html += '<h6 class="section-header"><i class="fa fa-university"></i> University Students <span class="badge badge-info">' + universityStudents.length + '</span></h6>';
        html += '<div class="students-grid">';
        
        universityStudents.forEach(function(student) {
          html += createSponsoredStudentCard(student);
        });
        
        html += '</div></div>';
      }
    }
    
    $('#sponsored-students-display').html(html);
  }

  function createSponsoredStudentCard(student) {
    const typeInfo = student.student_type === 'school' 
      ? '<span class="label label-primary">Grade ' + (student.school_grade || 'N/A') + '</span>'
      : '<span class="label label-info">Year ' + (student.university_year_of_study || 'N/A') + '</span>';

    const schoolInfo = student.student_type === 'school' 
      ? (student.school_name || 'N/A')
      : (student.university_name || 'N/A') + (student.program_name ? ' - ' + student.program_name : '');

    const transactionSummary = student.transaction_summary || {};
    const totalAmount = parseFloat(transactionSummary.total_amount || 0);
    const amountPaid = parseFloat(transactionSummary.amount_paid || 0);
    const balanceAmount = totalAmount - amountPaid;

    return `
      <div class="student-card">
        <div class="student-card-header">
          <div class="student-status-icon">
            <i class="fa fa-check-circle" aria-hidden="true" title="Actively Sponsored"></i>
          </div>
          <div class="student-info">
            <h6 class="student-name">${student.name}</h6>
            <small class="text-muted">${student.school_internal_id || student.university_internal_id || 'N/A'}</small>
          </div>
        </div>
        <div class="student-card-body">
          <div class="row">
            <div class="col-xs-6">
              ${typeInfo}
            </div>
            <div class="col-xs-6 text-right">
              <small class="text-muted">
                <i class="fa fa-map-marker"></i> ${student.city || 'N/A'}<br>
                <i class="fa fa-envelope"></i> ${student.email || 'N/A'}
              </small>
            </div>
          </div>
          
          <div class="row" style="margin-top: 8px;">
            <div class="col-xs-12">
              <small class="text-muted">
                <i class="fa fa-graduation-cap"></i> ${schoolInfo}
              </small>
            </div>
          </div>
          
          ${totalAmount > 0 ? `
          <div class="transaction-summary">
            <div class="summary-row">
              <span>Total Commitment:</span>
              <span>₹${totalAmount.toLocaleString('en-IN', {maximumFractionDigits: 2})}</span>
            </div>
            <div class="summary-row">
              <span>Amount Paid:</span>
              <span>₹${amountPaid.toLocaleString('en-IN', {maximumFractionDigits: 2})}</span>
            </div>
            <div class="summary-row">
              <span>Balance:</span>
              <span>₹${balanceAmount.toLocaleString('en-IN', {maximumFractionDigits: 2})}</span>
            </div>
            <div class="summary-row">
              <span>Transactions:</span>
              <span>${transactionSummary.transaction_count || 0}</span>
            </div>
          </div>
          ` : ''}
          
          <div class="student-actions">
            <button type="button" class="btn btn-xs btn-default view-details" 
                    data-student='${JSON.stringify(student).replace(/'/g, "&apos;")}'>
              <i class="fa fa-eye"></i> Details
            </button>
            ${!isSponsor ? `
            <button type="button" class="btn btn-xs btn-info view-transactions" 
                    data-student-id="${student.id}" data-student-type="${student.student_type}">
              <i class="fa fa-money"></i> Transactions
            </button>
            ` : ''}
          </div>
        </div>
      </div>
    `;
  }

  function displaySponsorshipStatistics(stats) {
    if (!stats) return;
    
    const totalCommitment = parseFloat(stats.total_commitment || 0);
    const totalPaid = parseFloat(stats.total_paid || 0);
    const totalBalance = parseFloat(stats.total_balance || 0);
    
    $('#total-students').text(stats.total_students || 0);
    $('#total-commitment').text('₹' + totalCommitment.toLocaleString('en-IN', {maximumFractionDigits: 2}));
    $('#total-paid').text('₹' + totalPaid.toLocaleString('en-IN', {maximumFractionDigits: 2}));
    $('#total-balance').text('₹' + totalBalance.toLocaleString('en-IN', {maximumFractionDigits: 2}));
    
    $('#sponsorship-statistics').show();
  }

  function showStudentDetails(student) {
    let detailsHtml = `
      <div class="row">
        <div class="col-md-6">
          <h5>Basic Information</h5>
          <p><strong>Name:</strong> ${student.name}</p>
          <p><strong>Internal ID:</strong> ${student.school_internal_id || student.university_internal_id || 'N/A'}</p>
          <p><strong>Type:</strong> ${student.student_type === 'school' ? 'School Student' : 'University Student'}</p>
          <p><strong>Grade/Year:</strong> ${student.school_grade || student.university_year_of_study || 'N/A'}</p>
          <p><strong>Email:</strong> ${student.email || 'Not provided'}</p>
          <p><strong>City:</strong> ${student.city || 'Not provided'}</p>
        </div>
        <div class="col-md-6">
          <h5>Institution Information</h5>
    `;
    
    if (student.student_type === 'school') {
      detailsHtml += `
          <p><strong>School:</strong> ${student.school_name || 'N/A'}</p>
          <p><strong>Country:</strong> ${student.country_name || 'N/A'}</p>
      `;
    } else {
      detailsHtml += `
          <p><strong>University:</strong> ${student.university_name || 'N/A'}</p>
          <p><strong>Program:</strong> ${student.program_name || 'N/A'}</p>
          <p><strong>Country:</strong> ${student.country_name || 'N/A'}</p>
      `;
    }
    
    if (student.transaction_summary && student.transaction_summary.total_amount > 0) {
      const ts = student.transaction_summary;
      detailsHtml += `
          <h5 style="margin-top: 20px;">Financial Summary</h5>
          <p><strong>Total Commitment:</strong> ₹${parseFloat(ts.total_amount).toLocaleString('en-IN')}</p>
          <p><strong>Amount Paid:</strong> ₹${parseFloat(ts.amount_paid).toLocaleString('en-IN')}</p>
          <p><strong>Balance Due:</strong> ₹${(ts.total_amount - ts.amount_paid).toLocaleString('en-IN')}</p>
          <p><strong>Number of Transactions:</strong> ${ts.transaction_count}</p>
      `;
    }
    
    detailsHtml += `
        </div>
      </div>
    `;
    
    $('#student-details-content').html(detailsHtml);
    $('#studentDetailsModal').modal('show');
  }

  /* -------- Form validation and event handlers -------- */
  $(function(){
    // Initialize selectpicker
    if ($.fn.selectpicker) $('.selectpicker').selectpicker();

    // Load sponsored students when the students tab is first shown (only if sponsor exists)
    $('a[href="#tab_students"]').on('shown.bs.tab', function() {
      loadSponsoredStudents();
    });

    // View student details (available to both admin and sponsors)
    $(document).on('click', '.view-details', function(e) {
      e.stopPropagation();
      const student = $(this).data('student');
      showStudentDetails(student);
    });

    // View transactions (admin only)
    if (!isSponsor) {
      $(document).on('click', '.view-transactions', function(e) {
        e.stopPropagation();
        const studentId = $(this).data('student-id');
        const studentType = $(this).data('student-type');
        
        // Redirect to transaction management with filters
        const baseUrl = '<?= admin_url("student_sponsor_portal/transactions"); ?>';
        const params = new URLSearchParams({
          sponsor_id: currentSponsorId,
          student_id: studentId,
          student_type: studentType
        });
        window.open(baseUrl + '?' + params.toString(), '_blank');
      });
    }

    // Country selection and phone code update - KEY FUNCTIONALITY FROM SCHOOL FORM
    $('#country_id').on('changed.bs.select change', function(){
      var $opt = $(this).find('option:selected');
      var code = $opt.data('phone-code') || '+1';
      $('#phone-code-display').text(code);

      // Load states for selected country (admin only)
      if (!isSponsor) {
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
      }
    });

    // Form validation
    $('#sponsor-form').on('submit', function(e){
      // remember current tab
      var currentTab = $('.nav-tabs li.active a').attr('href') || '#tab_basic';
      $('#active_tab').val(currentTab);

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
      
      // Disable submit button to prevent double submission
      var saveText = isSponsor ? 'Updating Profile...' : 'Saving...';
      $('#save-sponsor-btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + saveText);
    });

    $('input,select').on('change keyup', function(){ $(this).removeClass('has-error'); });

    // Add Bank modal handlers (admin only)
    if (!isSponsor) {
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
    }

    $(document).ready(function() {
      var currentSponsorId = <?php echo json_encode(!empty($sponsor['id']) ? (int)$sponsor['id'] : 0); ?>;
      var isSponsorsView = <?php echo json_encode(!isset($is_sponsor) || !$is_sponsor); ?>;

      // Load detailed students when requested
      $(document).on('click', '.load-detailed-students', function() {
        var $btn = $(this);
        var sponsorId = $btn.data('sponsor-id');
        var type = $btn.data('type');
        var $container = $('#' + type + '-detailed-container');
        var $content = $container.find('.detailed-students-content');
        
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Loading...');
        $container.show();
        
        $.ajax({
          url: '<?php echo admin_url("student_sponsor_portal/get_sponsored_students"); ?>',
          type: 'POST',
          data: { 
            sponsor_id: sponsorId,
            type: type,
            <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
          },
          dataType: 'json',
          success: function(response) {
            if (response.success && response.students) {
              var html = createStudentsGrid(response.students, type);
              $content.html(html);
              $btn.parent().hide(); // Hide the load more button
            } else {
              $content.html('<div class="alert alert-warning">Unable to load detailed student information.</div>');
              $btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Try Again');
            }
          },
          error: function() {
            $content.html('<div class="alert alert-danger">Error loading student details.</div>');
            $btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Try Again');
          }
        });
      });

      // Create students grid HTML
      function createStudentsGrid(students, type) {
        if (!students || students.length === 0) {
          return '<div class="alert alert-info">No students found.</div>';
        }

        var html = '<div class="students-grid">';
        
        students.forEach(function(student) {
          // Create initials for avatar
          var initials = '';
          if (student.name) {
            var nameParts = student.name.trim().split(' ');
            for (var i = 0; i < Math.min(2, nameParts.length); i++) {
              if (nameParts[i].length > 0) {
                initials += nameParts[i].charAt(0).toUpperCase();
              }
            }
          }
          if (!initials) initials = 'S';

          // Type-specific information
          var typeInfo = '';
          var institutionInfo = '';
          
          if (type === 'school') {
            typeInfo = '<span class="label label-primary">Grade ' + (student.school_grade || 'N/A') + '</span>';
            institutionInfo = student.school_name || 'N/A';
          } else {
            typeInfo = '<span class="label label-info">Year ' + (student.university_year_of_study || 'N/A') + '</span>';
            institutionInfo = (student.university_name || 'N/A') + (student.program_name ? ' - ' + student.program_name : '');
          }

          // Financial information
          var financialHtml = '';
          if (student.total_amount && parseFloat(student.total_amount) > 0) {
            var totalAmount = parseFloat(student.total_amount || 0);
            var amountPaid = parseFloat(student.amount_paid || 0);
            var balance = totalAmount - amountPaid;
            
            financialHtml = `
              <div class="transaction-summary">
                <div class="summary-row">
                  <span>Commitment:</span>
                  <span>₹${totalAmount.toLocaleString('en-IN', {maximumFractionDigits: 0})}</span>
                </div>
                <div class="summary-row">
                  <span>Paid:</span>
                  <span>₹${amountPaid.toLocaleString('en-IN', {maximumFractionDigits: 0})}</span>
                </div>
                <div class="summary-row">
                  <span>Balance:</span>
                  <span>₹${balance.toLocaleString('en-IN', {maximumFractionDigits: 0})}</span>
                </div>
              </div>
            `;
          }

          html += `
            <div class="student-card">
              <div class="student-card-header">
                <div class="student-avatar">${initials}</div>
                <div class="student-info">
                  <h6>${student.name || 'Unknown'}</h6>
                  <small class="text-muted">${student.school_internal_id || student.university_internal_id || 'N/A'}</small>
                </div>
              </div>
              <div class="student-card-body">
                <div style="margin-bottom: 8px;">
                  ${typeInfo}
                </div>
                <div style="margin-bottom: 8px;">
                  <small class="text-muted">
                    <i class="fa fa-graduation-cap"></i> ${institutionInfo}
                  </small>
                </div>
                <div style="margin-bottom: 8px;">
                  <small class="text-muted">
                    <i class="fa fa-map-marker"></i> ${student.city || 'N/A'} | 
                    <i class="fa fa-envelope"></i> ${student.email || 'N/A'}
                  </small>
                </div>
                ${financialHtml}
                <div class="student-actions">
                  <button type="button" class="btn btn-xs btn-default view-student-details" 
                          data-student-id="${student.id}" 
                          data-student-type="${type}"
                          data-student-name="${student.name || 'Unknown'}">
                    <i class="fa fa-eye"></i> Details
                  </button>
                  ${isSponsorsView ? `
                  <button type="button" class="btn btn-xs btn-info view-student-transactions" 
                          data-student-id="${student.id}" 
                          data-student-type="${type}">
                    <i class="fa fa-money"></i> Transactions
                  </button>
                  ` : ''}
                </div>
              </div>
            </div>
          `;
        });

        html += '</div>';
        return html;
      }

      // View student details
      $(document).on('click', '.view-student-details', function() {
        var studentId = $(this).data('student-id');
        var studentType = $(this).data('student-type');
        var studentName = $(this).data('student-name');
        
        $('#studentDetailsModal .modal-title').html('<i class="fa fa-user"></i> ' + studentName);
        $('#student-details-content').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</div>');
        
        $('#view-student-transactions').data('student-id', studentId).data('student-type', studentType);
        
        $('#studentDetailsModal').modal('show');
        
        // Load basic student info
        $('#student-details-content').html(`
          <div class="alert alert-info">
            <h5>Student Information</h5>
            <p><strong>Name:</strong> ${studentName}</p>
            <p><strong>ID:</strong> ${studentId}</p>
            <p><strong>Type:</strong> ${studentType.charAt(0).toUpperCase() + studentType.slice(1)} Student</p>
            <p><strong>Sponsor:</strong> <?php echo htmlspecialchars($sponsor['name'] ?? ''); ?></p>
            <hr>
            <p><small class="text-muted">Detailed information is available through the transaction management system.</small></p>
          </div>
        `);
        
        $('#view-student-transactions').show();
      });

      // View student transactions (admin only)
      <?php if(!isset($is_sponsor) || !$is_sponsor): ?>
      $(document).on('click', '.view-student-transactions, #view-student-transactions', function() {
        var studentId = $(this).data('student-id');
        var studentType = $(this).data('student-type');
        
        if (studentId && studentType) {
          var url = '<?php echo admin_url("student_sponsor_portal/transaction"); ?>'
          window.open(url, '_blank');
        }
      });
      <?php endif; ?>
    });

    // restore tab after reload (server echoes active_tab)
    var initialTab = $('#active_tab').val();
    if (initialTab && $('a[href="'+initialTab+'"]').length) {
      $('a[href="'+initialTab+'"]').tab('show');
    }

    // Auto-load students tab if editing existing sponsor
    if (currentSponsorId && initialTab === '#tab_students') {
      setTimeout(function() {
        loadSponsoredStudents();
      }, 500);
    }

    // Initialize phone code display on page load
    setTimeout(function() {
      updatePhoneCode();
    }, 100);
  });
})(jQuery);
</script>