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
                                    <?= isset($sponsor) ? 'Edit Sponsor' : 'Add New Sponsor'; ?>
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
                            <!-- Basic Tab -->
                            <div class="tab-pane active" id="tab_basic">
                                <div class="row">
                                    <div class="col-md-6">
                                        <?= render_input('name', 'Sponsor Name *', html_escape($sponsor['name'] ?? ''), 'text', ['required' => true]); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?= render_select('sponsor_type', [
                                            ['id'=>'individual','name'=>'Individual'],
                                            ['id'=>'company','name'=>'Company']
                                        ], ['id','name'], 'Sponsor Type *', $sponsor['sponsor_type'] ?? '', ['required' => true]); ?>
                                    </div>
                                </div>
                                <?= render_input('sponsor_occupation', 'Occupation', html_escape($sponsor['sponsor_occupation'] ?? '')); ?>
                            </div>

                            <!-- Contact Tab -->
                            <div class="tab-pane" id="tab_contact">
                                <div class="row">
                                    <div class="col-md-6">
                                        <?= render_input('email', 'Email', html_escape($sponsor['email'] ?? ''), 'email'); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?= render_select('country_id', [], [], 'Country', $sponsor['country_id'] ?? ''); ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <?= render_input('phone_code', 'Country Code', html_escape($sponsor['phone_code'] ?? ''), 'text'); ?>
                                    </div>
                                    <div class="col-md-9">
                                        <?= render_input('contact_no', 'Phone', html_escape($sponsor['contact_no'] ?? ''), 'tel'); ?>
                                    </div>
                                </div>
                                <?= render_textarea('address', 'Address', html_escape($sponsor['address'] ?? '')); ?>
                                <div class="row">
                                    <div class="col-md-4"><?= render_input('city', 'City', html_escape($sponsor['city'] ?? '')); ?></div>
                                    <div class="col-md-4"><?= render_select('state_id', [], [], 'State/Province', $sponsor['state_id'] ?? ''); ?></div>
                                    <div class="col-md-4"><?= render_input('zip', 'Postal Code', html_escape($sponsor['zip'] ?? '')); ?></div>
                                </div>
                            </div>

                            <!-- Bank Tab -->
                            <div class="tab-pane" id="tab_bank">
                                <div class="row">
                                    <div class="col-md-6"><?= render_select('bank_id', [], [], 'Bank', $sponsor['bank_id'] ?? ''); ?></div>
                                    <div class="col-md-6"><?= render_input('sponsor_bank_branch_info', 'Branch Name', html_escape($sponsor['sponsor_bank_branch_info'] ?? '')); ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6"><?= render_input('sponsor_bank_branch_number', 'Branch/IFSC', html_escape($sponsor['sponsor_bank_branch_number'] ?? '')); ?></div>
                                    <div class="col-md-6"><?= render_input('sponsor_bank_account_no', 'Account Number', html_escape($sponsor['sponsor_bank_account_no'] ?? '')); ?></div>
                                </div>
                            </div>

                            <!-- Sponsorship Tab -->
                            <div class="tab-pane" id="tab_sponsorship">
                                <div class="row">
                                    <div class="col-md-6"><?= render_date_input('membership_start_date', 'Start Date', $sponsor['membership_start_date'] ?? ''); ?></div>
                                    <div class="col-md-6"><?= render_date_input('membership_end_date', 'Renewal Date', $sponsor['membership_end_date'] ?? ''); ?></div>
                                </div>
                                <?= render_select('sponsor_frequency', [
                                    ['id'=>'one_time','name'=>'One-time'],
                                    ['id'=>'monthly','name'=>'Monthly'],
                                    ['id'=>'half_yearly','name'=>'Half-yearly'],
                                    ['id'=>'yearly','name'=>'Yearly']
                                ], ['id','name'], 'Frequency', $sponsor['sponsor_frequency'] ?? ''); ?>
                            </div>

                            <!-- Staff Account Tab -->
                            <div class="tab-pane" id="tab_staff">
                                <div class="checkbox checkbox-primary">
                                    <input type="checkbox" id="create_staff" name="create_staff" value="1" <?= !empty($sponsor['staff_id']) ? 'checked' : ''; ?>>
                                    <label for="create_staff">Create a Staff login</label>
                                </div>
                                <div class="row">
                                    <div class="col-md-4"><?= render_input('staff_email', 'Login Email', html_escape($sponsor['email'] ?? ''), 'email'); ?></div>
                                    <div class="col-md-4"><?= render_input('staff_firstname', 'First Name', html_escape($sponsor['name'] ?? '')); ?></div>
                                    <div class="col-md-4"><?= render_input('staff_lastname', 'Last Name'); ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4"><?= render_input('staff_password', 'Password', '', 'password'); ?></div>
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
                        </div>

                        <!-- Actions -->
                        <div class="form-group" style="margin-top:20px; border-top:1px solid #eee; padding-top:20px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> <?= isset($sponsor) ? 'Update Sponsor' : 'Save Sponsor'; ?>
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

<?php init_tail(); ?>


<script>
// Country to phone code mapping
const countryPhoneCodes = {
    'US': '+1', 'CA': '+1', 'IN': '+91', 'GB': '+44', 'AU': '+61', 'DE': '+49', 
    'FR': '+33', 'JP': '+81', 'CN': '+86', 'BR': '+55', 'RU': '+7', 'IT': '+39', 
    'ES': '+34', 'MX': '+52', 'KR': '+82', 'NL': '+31', 'SE': '+46', 'NO': '+47', 
    'DK': '+45', 'FI': '+358', 'CH': '+41', 'AT': '+43', 'BE': '+32', 'PL': '+48',
    'AE': '+971', 'SA': '+966', 'EG': '+20', 'ZA': '+27', 'NG': '+234', 'KE': '+254'
};

function updatePhoneCode() {
    const countrySelect = document.getElementById('country_id');
    const phoneCodeInput = document.getElementById('phone_code');
    const selectedOption = countrySelect.options[countrySelect.selectedIndex];
    
    if (selectedOption && selectedOption.dataset.code) {
        const countryCode = selectedOption.dataset.code;
        if (countryPhoneCodes[countryCode]) {
            phoneCodeInput.value = countryPhoneCodes[countryCode];
        }
    }
}

$(document).ready(function() {
    // Form validation
    $('#sponsor-form').on('submit', function(e) {
        let isValid = true;
        
        // Validate required fields
        $('input[required], select[required]').each(function() {
            if ($(this).val() === '') {
                isValid = false;
                $(this).addClass('has-error');
                // Show the tab containing the error
                const tabPane = $(this).closest('.tab-pane');
                if (tabPane.length > 0) {
                    $('a[href="#' + tabPane.attr('id') + '"]').tab('show');
                }
            } else {
                $(this).removeClass('has-error');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert_float('danger', 'Please fill in all required fields.');
            return false;
        }
    });
    
    // Remove error class on input
    $('input, select').on('change keyup', function() {
        $(this).removeClass('has-error');
    });
    
    // Initialize selectpicker if available
    if ($.fn.selectpicker) {
        $('.selectpicker').selectpicker();
    }
});
</script>

<style>
.has-error {
    border-color: #e74c3c !important;
}

.nav-tabs-horizontal {
    border-bottom: 2px solid #ddd;
}

.nav-tabs-horizontal > li > a {
    border-radius: 4px 4px 0 0;
    margin-right: 2px;
}

.nav-tabs-horizontal > li.active > a,
.nav-tabs-horizontal > li.active > a:hover,
.nav-tabs-horizontal > li.active > a:focus {
    background-color: #fff;
    border-color: #ddd #ddd transparent;
    border-bottom-color: transparent;
}

.tab-content {
    padding: 20px 0;
    min-height: 400px;
}

.text-danger {
    color: #e74c3c;
}

.form-group label .fa {
    margin-right: 5px;
}
</style>

<?php init_tail(); ?>