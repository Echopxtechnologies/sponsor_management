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
              <div class="col-md-8" style="margin-top:44px;">
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
                  $form_action = admin_url('student_sponsor_portal/sponsor_form/' . (int)$sponsor['id']);
              } else {
                  $form_action = admin_url('student_sponsor_portal/sponsor_form');
              }

              echo form_open($form_action, ['id' => 'sponsor-form']);
            ?>
            <?php if (!empty($sponsor['id'])): ?>
              <input type="hidden" name="sponsor_id" value="<?= (int)$sponsor['id']; ?>">
            <?php endif; ?>

            <input type="hidden" name="active_tab" id="active_tab" value="<?php echo html_escape($this->input->post('active_tab') ?? ($active_tab ?? '#tab_basic')); ?>">

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
                  <li><a href="#tab_staff" data-toggle="tab"><i class="fa fa-user-circle"></i> Sponsor Access</a></li>
                <?php endif; ?>
              </ul>
            </div>

            <div class="tab-content sponsor-form-content">
              <!-- BASIC INFO TAB -->
              <div class="tab-pane active" id="tab_basic">
                
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
                        <select name="country_id" id="country_id" class="form-control selectpicker" data-live-search="true" data-none-selected-text="Select Country">
                          <option value="">Select Country</option>
                          <?php if(!empty($countries)): foreach($countries as $c): ?>
                            <?php
                              $cid   = (int)($c['id'] ?? $c['country_id'] ?? 0);
                              $cname = $c['name'] ?? $c['short_name'] ?? '';
                              $pcode = $c['calling_code'] ?? $c['phone_code'] ?? '';
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
                        <div class="country-select-wrapper">
                          <select name="country_id" id="country_id" class="form-control selectpicker" data-live-search="true" data-none-selected-text="Select Country">
                            <option value="">Select Country</option>
                            <?php if(!empty($countries)): foreach($countries as $c): ?>
                              <?php
                                $cid   = (int)($c['id'] ?? $c['country_id'] ?? 0);
                                $cname = $c['name'] ?? $c['short_name'] ?? '';
                                $pcode = $c['calling_code'] ?? $c['phone_code'] ?? '';
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
                             $phone_code = '+1';
                            if(isset($sponsor) && !empty($sponsor['country_id']) && !empty($countries)) {
                              foreach($countries as $country) {
                                $cid = (int)($country['id'] ?? $country['country_id'] ?? 0);
                                $pcode = $country['calling_code'] ?? $country['phone_code'] ?? '';
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
                      <label for="city" class="control-label">City</label>
                      <input type="text" name="city" id="city" class="form-control" 
                             placeholder="Enter City" 
                             value="<?php echo isset($sponsor['city']) ? html_escape($sponsor['city']) : ''; ?>"
                             maxlength="100" />
                    </div>
                  </div>
                </div>

                <?= render_textarea('address', 'Address', html_escape($sponsor['address'] ?? '')); ?>

                <div class="row">
                  <div class="col-md-6">
                    <?= render_input('zip', 'Postal Code', html_escape($sponsor['zip'] ?? '')); ?>
                  </div>
                </div>
              </div>

              <!-- BANK TAB -->
              <div class="tab-pane" id="tab_bank">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="bank_id">Bank</label>
                      <?php if(isset($is_sponsor) && $is_sponsor): ?>
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
                        <div class="bank-select-wrapper">
                          <select name="bank_id" id="bank_id" class="selectpicker form-control" data-live-search="true" data-width="100%">
                            <option value="">Select Bank</option>
                            <?php foreach ($banks as $b): ?>
                            <option value="<?= (int)$b['id']; ?>"
                                <?= !empty($sponsor['bank_id']) && (int)$sponsor['bank_id']===(int)$b['id'] ? 'selected' : '' ?>>
                                <?= html_escape($b['name']); ?>
                            </option>
                            <?php endforeach; ?>
                          </select>
<button type="button" class="btn btn-default btn-sm add-new-btn" data-toggle="modal" data-target="#addBankModal" title="Add Bank">
  <i class="fa fa-plus"></i>
</button>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <?= render_input('sponsor_bank_branch_info', 'Branch Name',
                        html_escape($sponsor['sponsor_bank_branch_info'] ?? '')); ?>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <?= render_input('sponsor_bank_branch_number', 'Branch/IFSC',
                        html_escape($sponsor['sponsor_bank_branch_number'] ?? '')); ?>
                  </div>
                  <div class="col-md-6">
                    <?= render_input('sponsor_bank_account_no', 'Account Number',
                        html_escape($sponsor['sponsor_bank_account_no'] ?? '')); ?>
                  </div>
                </div>
              </div>

              <!-- STUDENTS TAB -->
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
                $school_students_count = (int)($sponsor['school_students_count'] ?? 0);
                $university_students_count = (int)($sponsor['university_students_count'] ?? 0);
                $total_students_count = $school_students_count + $university_students_count;
                
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

                $total_commitment = (float)($sponsor['total_commitment'] ?? 0);
                $total_paid = (float)($sponsor['total_paid'] ?? 0);
                $total_balance = $total_commitment - $total_paid;
                $total_transactions = (int)($sponsor['total_transactions'] ?? 0);
                ?>

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
                      <h4 style="margin: 10px 0 5px 0;">Amt <?php echo number_format($total_commitment, 0); ?></h4>
                      <small>Total Commitment</small>
                    </div>
                  </div>
                </div>

                <?php if($total_students_count > 0): ?>
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
                                <h4 style="margin: 0 0 5px 0; color: #007bff;">Amt <?php echo number_format($total_commitment, 2); ?></h4>
                                <small class="text-muted">Total Commitment</small>
                              </div>
                            </div>
                            <div class="col-md-3">
                              <div class="metric-box" style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 4px; border-left: 4px solid #28a745;">
                                <h4 style="margin: 0 0 5px 0; color: #28a745;">Amt <?php echo number_format($total_paid, 2); ?></h4>
                                <small class="text-muted">Amount Paid</small>
                              </div>
                            </div>
                            <div class="col-md-3">
                              <div class="metric-box" style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 4px; border-left: 4px solid <?php echo $total_balance > 0 ? '#ffc107' : '#17a2b8'; ?>;">
                                <h4 style="margin: 0 0 5px 0; color: <?php echo $total_balance > 0 ? '#ffc107' : '#17a2b8'; ?>;">Amt <?php echo number_format(abs($total_balance), 2); ?></h4>
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

              <!-- SPONSORSHIP TAB (Admin Only) -->
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

              <!-- STAFF TAB (Admin Only) -->
              <div class="tab-pane" id="tab_staff">
                <div class="checkbox checkbox-primary">
                  <input type="checkbox" id="create_staff" name="create_staff" value="1" <?= !empty($sponsor['staff_id']) ? 'checked' : ''; ?>>
                  <label for="create_staff">Create a Sponsor login</label>
                </div>

                <div class="row">
                  <div class="col-md-4">
                    <?= render_input('staff_email', 'Login Email', html_escape($sponsor['email'] ?? ''), 'email'); ?>
                  </div>
                  <div class="col-md-4">
                    <?= render_input('staff_firstname', 'First Name', html_escape($sponsor['name'] ?? '')); ?>
                  </div>
                  <div class="col-md-4">
                    <?= render_input('staff_lastname', 'Last Name', ''); ?>
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
            </div>

            <!-- Action Buttons -->
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
        <button type="button" class="btn btn-primary" id="btnAddBank" onclick="addBank(); return false;">Add Bank</button>
      </div>
    </div>
  </div>
</div>

<?php endif; ?>

<!-- Student Details Modal -->
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
/* Enhanced Sponsor Form Styling */
.sponsor-form-wrapper {
  background: #fff;
  border-radius: 4px;
}

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

.sponsor-form-wrapper .sponsor-form-content {
  background: #fff;
  padding: 25px;
  border: 1px solid #ddd;
  border-top: none;
  border-radius: 0 0 4px 4px;
  min-height: 400px;
}

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

.sponsor-form-wrapper .sponsor-form-content hr {
  margin: 15px 0 20px;
  border-color: #e8e8e8;
}

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

.sponsor-form-wrapper .bootstrap-select > .dropdown-toggle {
  height: 36px !important;
  line-height: 1.4 !important;
  padding: 8px 12px !important;
}

.sponsor-form-wrapper .bootstrap-select .filter-option-inner-inner {
  line-height: 1.4;
}

.sponsor-form-wrapper .row {
  margin-left: -10px;
  margin-right: -10px;
}

.sponsor-form-wrapper .row > [class*="col-"] {
  padding-left: 10px;
  padding-right: 10px;
}

.sponsor-form-wrapper .has-error,
.sponsor-form-wrapper .field-error {
  border-color: #d9534f !important;
  box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 6px rgba(217,83,79,.6) !important;
}

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
</style>

<!-- ============================================ -->
<!-- FIXED JAVASCRIPT SECTION - ALL CHANGES HERE -->
<!-- ========================================== -->
<!-- ============================================ -->
<!-- FIXED JAVASCRIPT SECTION - ALL CHANGES HERE -->
<!-- ========================================== -->
<script>
// Ensure alert_float function exists
if (typeof alert_float !== 'function') { 
  window.alert_float = function(type, message){ alert(message); }; 
}

(function($){
  'use strict';
  
  var isSponsor = <?php echo json_encode(isset($is_sponsor) && $is_sponsor); ?>;
  var currentSponsorId = <?php echo json_encode(!empty($sponsor['id']) ? (int)$sponsor['id'] : 0); ?>;

  // ========== HELPER FUNCTIONS ==========
  
  /**
   * Refresh selectpicker dropdown
   */
  function refreshSelectpicker($el){
    if ($.fn.selectpicker) { 
      $el.selectpicker('refresh'); 
    }
  }

  /**
   * Update phone code display when country changes
   */
  function updatePhoneCodeDisplay() {
    var $country = $('#country_id');
    var selectedValue = $country.val();
    
    if (!selectedValue) {
      $('#phone-code-display').text('+1');
      console.log('🔍 No country selected, using default +1');
      return;
    }
    
    // More explicit option selection
    var $opt = $country.find('option[value="' + selectedValue + '"]');
    
    if (!$opt.length) {
      console.warn('⚠️ Could not find option for country_id:', selectedValue);
      $('#phone-code-display').text('+1');
      return;
    }
    
    // Try multiple methods to get phone code - ORDER MATTERS
    var code = $opt.attr('data-phone-code') || 
               $opt.data('phoneCode') ||
               $opt.data('phone-code') ||
               '+1';
    
    // Update the display
    $('#phone-code-display').text(code);
    
    // Enhanced debugging
    console.log('🔍 Phone Code Detection:', {
        country_id: selectedValue,
        option_text: $opt.text(),
        attr_data_phone_code: $opt.attr('data-phone-code'),
        data_phoneCode: $opt.data('phoneCode'),
        data_phone_code: $opt.data('phone-code'),
        final_code: code
    });
  }

  /**
   * Log all available countries with their attributes
   */
  function logAvailableCountries() {
    console.group('🌍 Available Countries with All Attributes');
    $('#country_id option').each(function(index) {
      var $opt = $(this);
      if ($opt.val()) { // Skip empty option
        // Get ALL attributes
        var attrs = {};
        $.each(this.attributes, function() {
          attrs[this.name] = this.value;
        });
        
        console.log(index + '. Country:', {
          id: $opt.val(),
          name: $opt.text(),
          'ALL ATTRIBUTES': attrs,
          'data-phone-code (attr)': $opt.attr('data-phone-code'),
          'data-phone-code (data)': $opt.data('phone-code'),
          'phoneCode (data camelCase)': $opt.data('phoneCode'),
          is_selected: $opt.is(':selected')
        });
      }
    });
    console.groupEnd();
  }

  // ========== ADMIN-ONLY FUNCTIONS ==========
  
  if (!isSponsor) {
    /**
     * Add new country (staged for form submission)
     */
    window.addCountry = function(){
      var name  = $.trim($('#modal_country_name').val());
      var phone = $.trim($('#modal_country_phone').val());
      
      console.log('Adding new country:', { name: name, phone: phone });
      
      if(!name || !phone){ 
        alert('Country name and phone code are required'); 
        return false; 
      }

      // Store values in hidden fields
      $('#new_country_name').val(name);
      $('#new_country_phone_code').val(phone);

      var $sel = $('#country_id');
      var tmpId = '__NEW_COUNTRY__';
      
      // Remove any existing temporary country
      $sel.find('option[value="'+tmpId+'"]').remove();
      
      // Create new option with proper data attribute
      var $newOption = $('<option/>', {
        value: tmpId,
        text: name + (phone ? ' (' + phone + ')' : ''),
        selected: true
      });
      $newOption.attr('data-phone-code', phone);
      
      $sel.append($newOption);
      
      console.log('✅ New country option added:', {
        value: tmpId,
        text: $newOption.text(),
        phone_code: phone,
        'data-phone-code': $newOption.attr('data-phone-code')
      });
      
      // Refresh selectpicker
      if ($.fn.selectpicker) {
        $sel.selectpicker('refresh');
        $sel.selectpicker('val', tmpId);
        $sel.selectpicker('render');
      }
      
      // Update phone code display
      updatePhoneCodeDisplay();

      // Close modal and clear fields
      $('#addCountryModal').modal('hide');
      $('#modal_country_name').val(''); 
      $('#modal_country_phone').val('');
      
      alert_float('success','Country will be added when you save the form');
      return false;
    };
  }

  // ========== DOM READY - INITIALIZATION ==========
  
  $(function(){
    console.group('🚀 Sponsor Form Initialization');
    console.log('Is Sponsor View:', isSponsor);
    console.log('Current Sponsor ID:', currentSponsorId);
    console.groupEnd();
    
    // ========== INITIALIZE SELECTPICKER ==========
    
    setTimeout(function(){ 
      if(!$.fn || !$.fn.selectpicker) {
        console.error('❌ Selectpicker plugin not available');
        return;
      }
      
      console.log('Initializing selectpicker...');
      
      $('.selectpicker').selectpicker({
        style: 'btn-default',
        liveSearch: true,
        dropupAuto: false
      });
      
      // Log available countries for debugging
      logAvailableCountries();
      
      // Set initial country value
      var currentCountry = $('#country_id').val();
      console.log('🔍 Initial country_id value:', currentCountry);
      
      if(currentCountry) {
        console.log('Setting selectpicker to country_id:', currentCountry);
        $('#country_id').selectpicker('val', currentCountry);
        $('#country_id').selectpicker('refresh');
      } else {
        console.warn('⚠️ No country_id selected on page load');
      }
      
      // ALWAYS update phone code display on init
      updatePhoneCodeDisplay();
      
      console.log('✅ Selectpicker initialization complete');
      
    }, 200);

    // ========== COUNTRY CHANGE HANDLERS ==========
    
    // Bootstrap-select change event (primary)
    $('#country_id').on('changed.bs.select', function(e, clickedIndex, isSelected, previousValue){
      var selectedValue = $(this).val();
      
      console.group('🔄 Country Changed (Bootstrap-Select)');
      console.log('Clicked Index:', clickedIndex);
      console.log('Previous Value:', previousValue);
      console.log('New Value:', selectedValue);
      console.groupEnd();
      
      updatePhoneCodeDisplay();
    });
    
    // Native select change event (fallback)
    $('#country_id').on('change', function(){
      var selectedValue = $(this).val();
      
      console.group('🔄 Country Changed (Native)');
      console.log('Selected Value:', selectedValue);
      console.log('Selected Text:', $(this).find('option:selected').text());
      console.groupEnd();
      
      updatePhoneCodeDisplay();
    });

    // ========== FORM VALIDATION & SUBMISSION ==========
    
    $('#sponsor-form').on('submit', function(e){
      console.log('📝 Form submitting...');
      
      // Save current active tab
      var currentTab = $('.nav-tabs li.active a').attr('href') || '#tab_basic';
      $('#active_tab').val(currentTab);
      
      // Log form data being submitted
      var formData = $(this).serializeArray();
      console.group('📤 Form Data Summary');
      formData.forEach(function(field) {
        if (field.name === 'country_id') {
          console.log('✨ country_id:', field.value);
        }
        if (field.name === 'contact_no') {
          console.log('📞 contact_no:', field.value);
        }
      });
      console.groupEnd();

      // Validate required fields
      var ok = true;
      $(this).find('input[required], select[required]').each(function(){
        var $field = $(this);
        var val = $field.val();
        
        if (val === '' || val === null) {
          ok = false;
          $field.addClass('has-error');
          console.error('❌ Required field missing:', $field.attr('name'));
          
          // Switch to tab containing the error
          var $pane = $field.closest('.tab-pane');
          if ($pane.length) {
            $('a[href="#'+$pane.attr('id')+'"]').tab('show');
          }
        } else {
          $field.removeClass('has-error');
        }
      });
      
      if (!ok) {
        e.preventDefault();
        alert_float('danger','Please fill in all required fields.');
        console.error('❌ Form validation failed');
        return false;
      }
      
      // Show loading state
      var saveText = isSponsor ? 'Updating Profile...' : 'Saving...';
      $('#save-sponsor-btn').prop('disabled', true)
                             .html('<i class="fa fa-spinner fa-spin"></i> ' + saveText);
      
      console.log('✅ Form validation passed, submitting...');
    });

    // Remove error class on field change
    $('input, select, textarea').on('change keyup', function(){ 
      $(this).removeClass('has-error'); 
    });

    // ========== ADMIN-ONLY: BANK MODAL HANDLERS ==========
    
    if (!isSponsor) {
      // Open add bank modal
      $('#btn-add-bank').on('click', function(){
        console.log('Opening bank modal...');
        $('#nb_name, #nb_branch, #nb_ifsc').val('');
        $('#modalAddBank').modal('show');
        setTimeout(function(){ $('#nb_name').focus(); }, 300);
      });

      // Save new bank
      $('#nb_save').on('click', function(){
        var name = $('#nb_name').val().trim();
        var branch = $('#nb_branch').val().trim();
        var ifsc = $('#nb_ifsc').val().trim();
        
        console.log('Saving bank:', { name: name, branch: branch, ifsc: ifsc });
        
        if (!name) { 
          alert_float('warning','Bank name is required'); 
          $('#nb_name').focus();
          return; 
        }

        var payload = { 
          name: name, 
          branch: branch, 
          ifsc: ifsc 
        };
        
        // Add CSRF token if available
        if (typeof csrfData !== 'undefined') {
          payload[csrfData.token_name] = csrfData.hash;
        }

        // Disable save button
        $('#nb_save').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        $.post('<?= admin_url('student_sponsor_portal/ajax_bank_create'); ?>', payload, function(resp){
          var r; 
          try { 
            r = JSON.parse(resp); 
          } catch(e) { 
            console.error('Failed to parse bank response:', resp);
            r = {success: false, message: 'Invalid server response'}; 
          }
          
          // Re-enable button
          $('#nb_save').prop('disabled', false).html('<i class="fa fa-check"></i> Save');
          
          if (!r.success) { 
            alert_float('warning', r.message || 'Failed to add bank'); 
            return; 
          }
          
          // Update CSRF token
          if (typeof csrfData !== 'undefined' && r[csrfData.token_name]) {
            csrfData.hash = r[csrfData.token_name];
          }

          console.log('✅ Bank added successfully:', r);
          
          // Add new bank to dropdown
          var $bank = $('#bank_id');
          $bank.append($('<option/>', {
            value: r.id, 
            text: r.text || r.name, 
            selected: true
          }));
          
          if ($.fn.selectpicker) {
            $bank.selectpicker('refresh');
          }

          $('#modalAddBank').modal('hide');
          alert_float('success','Bank added successfully');
          
        }).fail(function(xhr, status, error){
          console.error('AJAX error:', {xhr: xhr, status: status, error: error});
          $('#nb_save').prop('disabled', false).html('<i class="fa fa-check"></i> Save');
          alert_float('danger', 'Network error: Could not add bank');
        });
      });
    }

    // ========== RESTORE ACTIVE TAB ==========
    
    var initialTab = $('#active_tab').val();
    if (initialTab && $('a[href="'+initialTab+'"]').length) {
      console.log('Restoring active tab:', initialTab);
      $('a[href="'+initialTab+'"]').tab('show');
    }
    
    console.log('✅ Sponsor form initialization complete');
  });
  
})(jQuery);
</script>

<!-- TEMPORARY: Diagnostic Output (Remove after testing) -->
<?php if(!isset($is_sponsor) || !$is_sponsor): ?>
<script>
(function() {
  try {
    var countriesData = <?php echo json_encode($countries ?? [], JSON_UNESCAPED_UNICODE); ?>;
    
    console.group('📊 DIAGNOSTIC: Countries Data from PHP');
    console.log('Countries array:', countriesData);
    console.log('Total countries:', countriesData.length);
    
    if (countriesData.length > 0) {
      console.log('Sample country structure:', countriesData[0]);
      
      // Check critical fields
      var firstCountry = countriesData[0];
      console.log('Field check:', {
        'has id': 'id' in firstCountry,
        'has name': 'name' in firstCountry,
        'has calling_code': 'calling_code' in firstCountry,
        'has phone_code': 'phone_code' in firstCountry
      });
      
      if (firstCountry.calling_code !== undefined) {
        console.log('✅ calling_code field EXISTS');
        console.log('Sample calling_code value:', firstCountry.calling_code);
      } else {
        console.error('❌ calling_code field MISSING');
      }
      
      // Show first 5 countries
      console.table(countriesData.slice(0, 5));
    } else {
      console.warn('⚠️ No countries data available');
    }
    console.groupEnd();
  } catch(e) {
    console.error('❌ Error in diagnostic code:', e.message);
  }
})();



/**
 * Add Bank - Standalone function for modal
 * Place this OUTSIDE the main jQuery closure
 */
function addBank() {
  console.log('🏦 addBank() called');
  
  var $btn = $('#btnAddBank');
  var bankName = $('#modal_bank_name').val();
  
  // Validate
  if (!bankName || bankName.trim() === '') {
    alert('Please enter a bank name');
    $('#modal_bank_name').focus();
    return false;
  }
  
  bankName = bankName.trim();
  console.log('Adding bank:', bankName);
  
  // Disable button
  $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Adding...');
  
  // Prepare data
  var postData = {
    name: bankName
  };
  
  // Add CSRF token if available
  if (typeof csrfData !== 'undefined' && csrfData.token_name && csrfData.hash) {
    postData[csrfData.token_name] = csrfData.hash;
  }
  
  // AJAX request
  $.ajax({
    url: '<?php echo admin_url("student_sponsor_portal/add_bank"); ?>',
    type: 'POST',
    data: postData,
    dataType: 'json',
    success: function(response) {
      console.log('✅ Bank add response:', response);
      
      // Update CSRF token if provided
      if (typeof csrfData !== 'undefined' && response[csrfData.token_name]) {
        csrfData.hash = response[csrfData.token_name];
      }
      
      if (response && response.success) {
        // Get the new bank ID and name
        var newBankId = response.id || response.bank_id || '';
        var newBankName = response.name || response.bank_name || bankName;
        
        console.log('New bank created:', {id: newBankId, name: newBankName});
        
        // Add to dropdown
        var $bankSelect = $('#bank_id');
        var newOption = $('<option></option>')
          .attr('value', newBankId)
          .text(newBankName)
          .prop('selected', true);
        
        $bankSelect.append(newOption);
        
        // Refresh selectpicker if available
        if (typeof $.fn.selectpicker !== 'undefined') {
          $bankSelect.selectpicker('refresh');
          $bankSelect.selectpicker('val', newBankId);
        }
        
        // Close modal and reset
        $('#addBankModal').modal('hide');
        $('#modal_bank_name').val('');
        
        // Show success message
        if (typeof alert_float === 'function') {
          alert_float('success', response.message || 'Bank added successfully');
        } else {
          alert('Bank added successfully: ' + newBankName);
        }
        
      } else {
        // Handle failure
        var errorMsg = response.message || 'Failed to add bank';
        console.error('❌ Bank add failed:', errorMsg);
        
        if (typeof alert_float === 'function') {
          alert_float('danger', errorMsg);
        } else {
          alert('Error: ' + errorMsg);
        }
      }
    },
    error: function(xhr, status, error) {
      console.error('❌ AJAX error:', {xhr: xhr, status: status, error: error});
      
      var errorMsg = 'Network error. Please try again.';
      
      // Try to get error message from response
      try {
        var responseJson = JSON.parse(xhr.responseText);
        if (responseJson && responseJson.message) {
          errorMsg = responseJson.message;
        }
      } catch(e) {
        // Use default error message
      }
      
      if (typeof alert_float === 'function') {
        alert_float('danger', errorMsg);
      } else {
        alert('Error: ' + errorMsg);
      }
    },
    complete: function() {
      // Re-enable button
      $btn.prop('disabled', false).html('Add Bank');
      console.log('🏦 addBank() complete');
    }
  });
  
  return false; // Prevent any default action
}
</script>
<?php endif; ?>