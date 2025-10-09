<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-10 col-md-offset-1">
        <div class="panel_s">
          <div class="panel-body">

            <!-- Tabs -->
            <ul class="nav nav-tabs" role="tablist" style="margin-bottom:15px;">
              <li role="presentation" class="<?php echo (!isset($_GET['tab']) || $_GET['tab']!=='payments') ? 'active' : ''; ?>">
                <a href="#tab_details" aria-controls="tab_details" role="tab" data-toggle="tab">Details</a>
              </li>
              <li role="presentation" class="<?php echo ($txn ? '' : 'disabled'); ?> <?php echo (isset($_GET['tab']) && $_GET['tab']==='payments') ? 'active' : ''; ?>">
                <a href="#tab_payments" aria-controls="tab_payments" role="tab" data-toggle="tab" <?php echo $txn ? '' : 'onclick="return false"'; ?>>Payments</a>
              </li>
            </ul>

            <div class="tab-content">
              <!-- ====================== DETAILS TAB ====================== -->
              <div role="tabpanel" class="tab-pane <?php echo (!isset($_GET['tab']) || $_GET['tab']!=='payments') ? 'active' : ''; ?>" id="tab_details">

                <form method="post" action="<?php echo admin_url('student_sponsor_portal/transaction_save'); ?>" novalidate>
                  <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                  <?php if ($txn): ?><input type="hidden" name="id" value="<?php echo (int)$txn->id; ?>"><?php endif; ?>

                  <!-- Sponsor -->
                  <div class="form-group">
                    <label>Sponsor <span class="text-danger">*</span></label>
                    <input type="hidden" name="sponsor_id" id="sponsor_id" value="<?php echo $txn ? (int)$txn->sponsor_id : ''; ?>">
                    <select id="sponsor_picker" style="width:100%"></select>
                  </div>

                  <!-- School Student -->
                  <div class="form-group">
                    <label>School Student</label>
                    <input type="hidden" name="school_student_id" id="school_student_id" value="<?php echo $txn ? (int)$txn->school_student_id : ''; ?>">
                    <select id="school_picker" style="width:100%"></select>
                  </div>

                  <!-- University Student -->
                  <div class="form-group">
                    <label>University Student</label>
                    <input type="hidden" name="university_student_id" id="university_student_id" value="<?php echo $txn ? (int)$txn->university_student_id : ''; ?>">
                    <select id="university_picker" style="width:100%"></select>
                    <span class="help-block">Pick <b>either</b> School student <b>or</b> University student (not both).</span>
                  </div>

                  <div class="row">
                    <div class="col-md-4">
                      <div class="form-group">
                        <label>Total Amount</label>
                        <input type="number" step="0.01" class="form-control" name="total_amount" value="<?php echo $txn ? html_escape($txn->total_amount) : '0.00'; ?>">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <label>Amount Paid</label>
                        <input type="number" step="0.01" class="form-control" name="amount_paid" value="<?php echo $txn ? html_escape($txn->amount_paid) : '0.00'; ?>" readonly>
                        <span class="help-block">Auto-updates from Payments.</span>
                      </div>
                    </div>
                  <div class="col-md-4">
  <div class="form-group">
    <label>Currency <span class="text-danger">*</span></label>
    <select name="currency" class="form-control selectpicker" required>
      <?php
        // Define available currencies
        $currencies = [
          'LKR' => 'Sri Lankan Rupees (LKR)',
          'USD' => 'US Dollars (USD)',
          'CAD' => 'Canadian Dollars (CAD)',
          'GBP' => 'UK Pounds (GBP)',
          'AUD' => 'Australian Dollars (AUD)'
        ];
        
        // Get current currency (default to LKR)
        $selected_currency = $txn ? strtoupper($txn->currency) : 'LKR';
        
        // Generate options
        foreach ($currencies as $code => $label):
      ?>
        <option value="<?php echo $code; ?>" <?php echo ($selected_currency == $code) ? 'selected' : ''; ?>>
          <?php echo $label; ?>
        </option>
      <?php endforeach; ?>
    </select>
    <small class="help-block">Currency will be stored as 3-letter code (e.g., LKR, USD)</small>
  </div>
</div>
                  </div>

                  <div class="form-group">
                    <label>Payment Type</label>
                    <select name="payment_type" class="form-control">
                      <?php
                        $opts=['one_time','monthly','quarterly','yearly','custom']; $cur=$txn?$txn->payment_type:'one_time';
                        foreach($opts as $o){ echo '<option value="'.$o.'"'.($cur==$o?' selected':'').'>'.ucfirst(str_replace('_',' ',$o)).'</option>'; }
                      ?>
                    </select>
                  </div>

                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label>Last Payment Date</label>
                        <input type="date" class="form-control" 
                          name="last_payment_date" 
                          value="<?php echo $txn ? html_escape($txn->last_payment_date) : ''; ?>"readonly>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="next_payment_due">Next Payment Due</label>
                        <input id="next_payment_due" type="text" name="next_payment_due"
                               value="<?= _d($txn->next_payment_due ?? '') ?>" readonly class="form-control">
                      </div>
                    </div>
                  </div>

                  <!-- Toggle (unchecked checkboxes don't submit -> add hidden 0) -->
                  <input type="hidden" name="due_reminder_active" value="0">
                  <div class="checkbox checkbox-primary">
                    <input id="due_reminder_active" type="checkbox" name="due_reminder_active" value="1"
                           <?= !empty($txn->due_reminder_active) ? 'checked' : '' ?>>
                    <label for="due_reminder_active">Due Reminder Active</label>
                  </div>

                  <div class="row">
                    <div class="col-md-4">
                      <div class="form-group">
                        <label for="due_reminder_days_before">Days Before Due</label>
                        <input id="due_reminder_days_before" type="number" name="due_reminder_days_before" min="0"
                               value="<?= isset($txn->due_reminder_days_before) ? (int)$txn->due_reminder_days_before : 15 ?>"
                               class="form-control">
                      </div>
                    </div>
                    <div class="col-md-8" style="margin-top:28px;">
                      <!-- Read-only status flags -->
                      <div class="row">
                        <div class="col-md-6">
                          <div class="checkbox checkbox-primary">
                            <input id="due_reminder_sent" type="checkbox"
                                   <?= ($txn && (int)$txn->due_reminder_sent === 1) ? 'checked' : '' ?> disabled>
                            <label for="due_reminder_sent">X-days-before Email Sent</label>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="checkbox checkbox-primary">
                            <input id="due_day_email_sent" type="checkbox"
                                   <?= ($txn && !empty($txn->due_day_email_sent)) ? 'checked' : '' ?> disabled>
                            <label for="due_day_email_sent">Due-Day Email Sent</label>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="text-right">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save</button>
                    <a href="<?php echo admin_url('student_sponsor_portal/transactions'); ?>" class="btn btn-default">Cancel</a>
                  </div>
                </form>
              </div>

              <!-- ====================== PAYMENTS TAB ====================== -->
              <div role="tabpanel" class="tab-pane <?php echo (isset($_GET['tab']) && $_GET['tab']==='payments') ? 'active' : ''; ?>" id="tab_payments">
                <?php if(!$txn): ?>
                  <div class="alert alert-info">Please save the transaction first to add payments.</div>
                <?php else: ?>
                  <?php
                    if (!isset($payments)) {
                      $this->db->where('transaction_id', (int)$txn->id);
                      $this->db->order_by('payment_date', 'DESC');
                      $payments = $this->db->get(db_prefix().'sponsor_payments')->result();
                    }
                    $paid = 0.0; foreach($payments as $p){ $paid += (float)$p->amount; }
                    $balance = max(0, (float)$txn->total_amount - $paid);
                  ?>

                  <div class="row">
                    <div class="col-md-4">
                      <div class="panel panel-default">
                        <div class="panel-heading"><strong>Summary</strong></div>
                        <div class="panel-body">
                          <p><strong>Total:</strong> <?php echo _format_number($txn->total_amount); ?> <?php echo html_escape($txn->currency); ?></p>
                          <p><strong>Paid:</strong> <?php echo _format_number($paid); ?> <?php echo html_escape($txn->currency); ?></p>
                          <p><strong>Balance:</strong> <?php echo _format_number($balance); ?> <?php echo html_escape($txn->currency); ?></p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <h4 style="margin-top:0;">Add Payment</h4>
                  <form method="post" action="<?php echo admin_url('student_sponsor_portal/add_payment/'.(int)$txn->id); ?>" class="m-b-30" novalidate>
                    <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                    <input type="hidden" name="sponsor_id" value="<?php echo (int)$txn->sponsor_id; ?>">
                    <?php $student_id = $txn->school_student_id ?: $txn->university_student_id; ?>
                    <input type="hidden" name="student_id" value="<?php echo (int)$student_id; ?>">

                    <div class="row">
                      <div class="col-md-3"><div class="form-group">
                        <label>Payment Date</label>
                        <input type="date" class="form-control" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                      </div></div>
                      <div class="col-md-3"><div class="form-group">
                        <label>Amount</label>
                        <input type="number" step="0.01" class="form-control" name="amount" required>
                      </div></div>
                      <div class="col-md-2"><div class="form-group">
                        <label>Currency</label>
                        <input type="text" class="form-control" name="currency" value="<?php echo html_escape($txn->currency); ?>" required>
                      </div></div>
                      <div class="col-md-4"><div class="form-group">
                        <label>Note</label>
                        <input type="text" class="form-control" name="note" placeholder="Optional">
                      </div></div>
                    </div>

                    <div class="text-right">
                      <button type="submit" class="btn btn-success"><i class="fa fa-plus"></i> Add Payment</button>
                    </div>
                  </form>

                  <h4>Payment History</h4>
                  <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>Date</th>
                          <th>Amount</th>
                          <th>Currency</th>
                          <th>Note</th>
                          <th>Created By</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (empty($payments)): ?>
                          <tr><td colspan="7" class="text-center text-muted">No payments yet.</td></tr>
                        <?php else: $i=1; foreach($payments as $p): ?>
                          <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo _d($p->payment_date); ?></td>
                            <td><?php echo _format_number($p->amount); ?></td>
                            <td><?php echo html_escape($p->currency); ?></td>
                            <td><?php echo html_escape($p->note); ?></td>
                            <td>
                              <?php
                                if (!empty($p->created_by)) {
                                  $u = $this->db->select('firstname,lastname')->where('staffid',(int)$p->created_by)->get(db_prefix().'staff')->row();
                                  echo $u ? html_escape(trim($u->firstname.' '.$u->lastname)) : '-';
                                } else { echo '-'; }
                              ?>
                            </td>
                            <td>
                              <a class="btn btn-xs btn-default" href="<?php echo admin_url('student_sponsor_portal/transaction/'.(int)$txn->id.'?tab=payments&edit_payment='.(int)$p->id); ?>">
                                <i class="fa fa-pencil"></i>
                              </a>
                              <a class="btn btn-xs btn-danger" onclick="return confirm('Delete this payment?');" href="<?php echo admin_url('student_sponsor_portal/delete_payment/'.(int)$txn->id.'/'.(int)$p->id); ?>">
                                <i class="fa fa-trash"></i>
                              </a>
                            </td>
                          </tr>
                        <?php endforeach; endif; ?>
                      </tbody>
                    </table>
                  </div>

                  <?php
                    $editing_payment = null;
                    if ($txn && isset($_GET['edit_payment'])) {
                      $edit_id = (int) $_GET['edit_payment'];
                      if (!isset($payment_to_edit)) {
                        $payment_to_edit = $this->db->where('id',$edit_id)->get(db_prefix().'sponsor_payments')->row();
                      }
                      if ($payment_to_edit && (int)$payment_to_edit->transaction_id === (int)$txn->id) {
                        $editing_payment = $payment_to_edit;
                      }
                    }
                  ?>

                  <?php if ($editing_payment): ?>
                    <hr>
                    <h4>Edit Payment #<?php echo (int)$editing_payment->id; ?></h4>
                    <form method="post" action="<?php echo admin_url('student_sponsor_portal/edit_payment/'.(int)$txn->id.'/'.(int)$editing_payment->id); ?>" novalidate>
                      <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                      <div class="row">
                        <div class="col-md-3"><div class="form-group">
                          <label>Payment Date</label>
                          <input type="date" class="form-control" name="payment_date" value="<?php echo html_escape($editing_payment->payment_date); ?>" required>
                        </div></div>
                        <div class="col-md-3"><div class="form-group">
                          <label>Amount</label>
                          <input type="number" step="0.01" class="form-control" name="amount" value="<?php echo html_escape($editing_payment->amount); ?>" required>
                        </div></div>
                        <div class="col-md-2"><div class="form-group">
                          <label>Currency</label>
                          <input type="text" class="form-control" name="currency" value="<?php echo html_escape($editing_payment->currency); ?>" required>
                        </div></div>
                        <div class="col-md-4"><div class="form-group">
                          <label>Note</label>
                          <input type="text" class="form-control" name="note" value="<?php echo html_escape($editing_payment->note); ?>">
                        </div></div>
                      </div>
                      <div class="text-right">
                        <a class="btn btn-default" href="<?php echo admin_url('student_sponsor_portal/transaction/'.(int)$txn->id.'?tab=payments'); ?>">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Update Payment</button>
                      </div>
                    </form>
                  <?php endif; ?>

                  <!-- EMAIL PREVIEW SECTION - MOVED INSIDE PAYMENTS TAB -->
                  <?php if (isset($email_template) && $email_template): ?>
                    <hr>
                    <h4>Due Payment Email Preview</h4>
                    <div class="row">
                      <div class="col-md-8">
                        <div class="panel panel-info">
                          <div class="panel-heading">
                            <strong>Subject:</strong> <?php echo html_escape($email_template['subject']); ?>
                          </div>
                          <div class="panel-body">
                            <?php echo $email_template['body']; ?>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="well">
                          <h5>Send Email</h5>
                          <p class="text-muted">Send due payment reminder to sponsor.</p>
                          <a href="<?php echo admin_url('student_sponsor_portal/send_test_email/'.(int)$txn->id); ?>" 
                             class="btn btn-success btn-block"
                             onclick="return confirm('Send due payment reminder email now?');">
                            <i class="fa fa-envelope"></i> Send Email
                          </a>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>

                <?php endif; ?>
              </div>
            </div><!-- /.tab-content -->

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php init_tail(); ?>

<!-- Select2 assets AFTER init_tail() -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>

<style>
  /* a little polish */
  .select2-results__option .text-muted{color:#777}
  .select2-container--default .select2-selection--single{
    height:38px;padding:5px 8px;border:1px solid #d1d5db;border-radius:4px
  }
  .select2-selection__rendered{line-height:26px}
  .select2-selection__arrow{height:36px}
</style>

<script>
(function(){
  var base = '<?php echo admin_url('student_sponsor_portal/'); ?>';

  // Perfex CSRF helper
  var CSRF = (typeof csrfData !== 'undefined') ? csrfData : {
    token_name:'<?php echo $this->security->get_csrf_token_name(); ?>',
    hash:'<?php echo $this->security->get_csrf_hash(); ?>'
  };

  function ajaxCfg(url){
    return {
      url: url,
      type: 'POST',
      dataType: 'json',
      delay: 200,
      data: function (params) {
        var d = { q: params.term || '', page: params.page || 1 };
        d[CSRF.token_name] = CSRF.hash;   // send csrf
        return d;
      },
      processResults: function(resp){
        // refresh csrf for next call (Perfex rotates hashes)
        if (resp && resp[CSRF.token_name]) { CSRF.hash = resp[CSRF.token_name]; }
        // resp must contain {results:[...], pagination:{more:bool}}
        return resp && resp.results ? resp : { results: [] };
      },
      cache: true
    };
  }

  function tmpl(item){
    if(!item.id) return item.text;
    var name = item.name || item.text || '';
    var meta = item.meta || item.extra || '';
    var $row = $('<div><div>'+ $('<div/>').text(name).html() +'</div></div>');
    if(meta){ $row.append('<small class="text-muted">'+ $('<div/>').text(meta).html() +'</small>'); }
    return $row;
  }
  function tmplSel(item){ return item.name || item.text || ''; }

  // Sponsor
  $('#sponsor_picker').select2({
    width:'100%', placeholder:'-- Select Sponsor --', allowClear:true,
    ajax: ajaxCfg(base + 'search_sponsors'),
    templateResult: tmpl, templateSelection: tmplSel
  }).on('select2:select', function(e){ $('#sponsor_id').val(e.params.data.id); })
    .on('select2:clear',  function(){ $('#sponsor_id').val(''); });

  // School
  $('#school_picker').select2({
    width:'100%', placeholder:'-- None --', allowClear:true,
    ajax: ajaxCfg(base + 'search_school_students'),
    templateResult: tmpl, templateSelection: tmplSel
  }).on('select2:select', function(e){
      $('#school_student_id').val(e.params.data.id);
      // XOR with university
      $('#university_picker').val(null).trigger('change');
      $('#university_student_id').val('');
    }).on('select2:clear', function(){ $('#school_student_id').val(''); });

  // University
  $('#university_picker').select2({
    width:'100%', placeholder:'-- None --', allowClear:true,
    ajax: ajaxCfg(base + 'search_university_students'),
    templateResult: tmpl, templateSelection: tmplSel
  }).on('select2:select', function(e){
      $('#university_student_id').val(e.params.data.id);
      // XOR with school
      $('#school_picker').val(null).trigger('change');
      $('#school_student_id').val('');
    }).on('select2:clear', function(){ $('#university_student_id').val(''); });

  // Preselect values on edit
  <?php if (!empty($txn) && (int)$txn->sponsor_id): ?>
    (function(){ var id='<?php echo (int)$txn->sponsor_id; ?>', text='<?php
      $nm = $this->db->select('name')->where('id',(int)$txn->sponsor_id)->get(db_prefix().'sponsor_records')->row();
      echo html_escape($nm ? $nm->name : '');
    ?>'; if(text){ var o=new Option(text,id,true,true); $('#sponsor_picker').append(o).trigger('change'); } })();
  <?php endif; ?>

  <?php if (!empty($txn) && (int)$txn->school_student_id): ?>
    (function(){ var id='<?php echo (int)$txn->school_student_id; ?>', text='<?php
      $nm = $this->db->select('name')->where('id',(int)$txn->school_student_id)->get(db_prefix().'school_students')->row();
      echo html_escape($nm ? $nm->name : '');
    ?>'; if(text){ var o=new Option(text,id,true,true); $('#school_picker').append(o).trigger('change'); } })();
  <?php endif; ?>

  <?php if (!empty($txn) && (int)$txn->university_student_id): ?>
    (function(){ var id='<?php echo (int)$txn->university_student_id; ?>', text='<?php
      $nm = $this->db->select('name')->where('id',(int)$txn->university_student_id)->get(db_prefix().'university_students')->row();
      echo html_escape($nm ? $nm->name : '');
    ?>'; if(text){ var o=new Option(text,id,true,true); $('#university_picker').append(o).trigger('change'); } })();
  <?php endif; ?>
})();
</script>