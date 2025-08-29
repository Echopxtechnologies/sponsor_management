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
                    <?php
                      echo render_select(
                        'country_id',
                        array_map(function($c){ return ['id'=>$c['id'],'name'=>$c['name']]; }, $countries),
                        ['id','name'],
                        'Country',
                        $sponsor['country_id'] ?? '',
                        ['id'=>'country_id','data-live-search'=>true,'class'=>'selectpicker']
                      );
                    ?>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-3">
                    <?= render_input('phone_code', 'Country Code', html_escape($sponsor['phone_code'] ?? ''), 'text', ['id'=>'phone_code']); ?>
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
                    <?php
                      echo render_select(
                        'state_id',
                        array_map(function($s){ return ['id'=>$s['id'],'name'=>$s['name']]; }, $states),
                        ['id','name'],
                        'State/Province',
                        $sponsor['state_id'] ?? '',
                        ['id'=>'state_id','data-live-search'=>true,'class'=>'selectpicker']
                      );
                    ?>
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



              <!-- SPONSORSHIP -->
              <div class="tab-pane" id="tab_sponsorship">
                <div class="row">
                  <div class="col-md-6">
                    <?= render_date_input('membership_start_date', 'Start Date', $sponsor['membership_start_date'] ?? ''); ?>
                  </div>
                  <div class="col-md-6">
                    <?= render_date_input('membership_end_date', 'Renewal Date', $sponsor['membership_end_date'] ?? ''); ?>
                  </div>
                </div>

                <?= render_select(
                  'sponsor_frequency',
                  [
                    ['id'=>'one_time','name'=>'One-time'],
                    ['id'=>'monthly','name'=>'Monthly'],
                    ['id'=>'half_yearly','name'=>'Half-yearly'],
                    ['id'=>'yearly','name'=>'Yearly']
                  ],
                  ['id','name'],
                  'Frequency',
                  $sponsor['sponsor_frequency'] ?? ''
                ); ?>
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
              <button type="submit" class="btn btn-primary">
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

<?php init_tail(); ?>

<script>
/* -------- Phone code helper (optional mapping) -------- */
const countryPhoneCodes = {
  'US': '+1','CA': '+1','IN': '+91','GB': '+44','AU': '+61','DE': '+49','FR': '+33',
  'JP': '+81','CN': '+86','BR': '+55','RU': '+7','IT': '+39','ES': '+34','MX': '+52',
  'KR': '+82','NL': '+31','SE': '+46','NO': '+47','DK': '+45','FI': '+358','CH': '+41',
  'AT': '+43','BE': '+32','PL': '+48','AE': '+971','SA': '+966','EG': '+20','ZA': '+27',
  'NG': '+234','KE': '+254'
};
function updatePhoneCodeFromCountryOption() {
  var $opt = $('#country_id option:selected');
  var code = $opt.data('code'); // if you store ISO code in data-code
  if (code && countryPhoneCodes[code]) $('#phone_code').val(countryPhoneCodes[code]);
}

/* -------- Form validation -------- */
$(function(){
  // Initialize selectpicker
  if ($.fn.selectpicker) $('.selectpicker').selectpicker();

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
    }
  });

  $('input,select').on('change keyup', function(){ $(this).removeClass('has-error'); });

  // Country -> States AJAX
  function refreshSelectpicker($el){ if ($.fn.selectpicker) $el.selectpicker('refresh'); }

  $('#country_id').on('changed.bs.select', function(){
    var cid = $(this).val();
    updatePhoneCodeFromCountryOption();

    var $state = $('#state_id');
    $state.empty();
    refreshSelectpicker($state);
    if (!cid) return;

    $.get('<?= admin_url('student_sponsor_portal/ajax_states/'); ?>'+cid, function(resp){
      var r; try { r = JSON.parse(resp); } catch(e){ r = {results:[]}; }
      if (typeof csrfData !== 'undefined' && r && r[csrfData.token_name]) csrfData.hash = r[csrfData.token_name];
      (r.results || []).forEach(function(s){
        $state.append($('<option/>',{value:s.id,text:s.name}));
      });
      refreshSelectpicker($state);
    });
  });

  // Add Bank open
  $('#btn-add-bank').on('click', function(){
    $('#nb_name,#nb_branch,#nb_ifsc').val('');
    $('#modalAddBank').modal('show');
    setTimeout(function(){ $('#nb_name').focus(); }, 250);
  });

  // Add Bank save
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
</style>
