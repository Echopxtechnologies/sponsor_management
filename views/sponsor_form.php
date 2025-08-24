<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="panel_s">
                    <div class="panel-body">
                        <!-- Header Section -->
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="customer-profile-group-heading">
                                    <i class="fa fa-user-plus"></i> <?php echo isset($sponsor) ? 'Edit Sponsor' : 'Add New Sponsor'; ?>
                                </h4>
                            </div>
                            <div class="col-md-4 text-right">
                                <a href="<?php echo admin_url('student_sponsor_portal/sponsors'); ?>" class="btn btn-default">
                                    <i class="fa fa-arrow-left"></i> Back to List
                                </a>
                            </div>
                        </div>
                        <hr class="hr-panel-heading">
                        
                        <?php echo form_open(admin_url('student_sponsor_portal/sponsor_form'), ['id' => 'sponsor-form']); ?>
                        <?php if(isset($sponsor)): ?>
                            <input type="hidden" name="sponsor_id" value="<?= isset($sponsor['id']) ? (int)$sponsor['id'] : 0; ?>">
                        <?php endif; ?>
                        
                        <!-- Navigation tabs -->
                        <div class="horizontal-tabs">
                            <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
                                <li role="presentation" class="active">
                                    <a href="#tab_basic_info" aria-controls="tab_basic_info" role="tab" data-toggle="tab">
                                        <i class="fa fa-user"></i> Basic Information
                                    </a>
                                </li>
                                <li role="presentation">
                                    <a href="#tab_contact_info" aria-controls="tab_contact_info" role="tab" data-toggle="tab">
                                        <i class="fa fa-phone"></i> Contact Details
                                    </a>
                                </li>
                                <li role="presentation">
                                    <a href="#tab_bank_info" aria-controls="tab_bank_info" role="tab" data-toggle="tab">
                                        <i class="fa fa-bank"></i> Banking Information
                                    </a>
                                </li>
                                <li role="presentation">
                                    <a href="#tab_sponsorship_info" aria-controls="tab_sponsorship_info" role="tab" data-toggle="tab">
                                        <i class="fa fa-calendar"></i> Sponsorship Details
                                    </a>
                                </li>
                                <li role="presentation">
                                <a href="#staff-account" aria-controls="staff-account" role="tab" data-toggle="tab">
                                    <i class="fa fa-user-circle"></i> Staff Account
                                </a>
                            </li>
                            </ul>
                        </div>

                        <div class="tab-content">
                            <!-- Basic Information Tab -->
                            <div role="tabpanel" class="tab-pane active" id="tab_basic_info">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name" class="control-label">
                                                <span class="text-danger">*</span> Sponsor Name
                                            </label>
                                            <input type="text" name="name" id="name" required class="form-control" 
                                                   value="<?php echo isset($sponsor) ? htmlspecialchars($sponsor['name']) : ''; ?>"
                                                   placeholder="Enter full name or organization name">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="sponsor_type" class="control-label">
                                                <span class="text-danger">*</span> Sponsor Type
                                            </label>
                                            <select name="sponsor_type" id="sponsor_type" required class="form-control selectpicker">
                                                <option value="">Select Sponsor Type</option>
                                                <option value="individual" <?php echo (isset($sponsor) && $sponsor['sponsor_type'] == 'individual') ? 'selected' : ''; ?>>Individual</option>
                                                <option value="company" <?php echo (isset($sponsor) && $sponsor['sponsor_type'] == 'company') ? 'selected' : ''; ?>>Company</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="sponsor_occupation" class="control-label">Occupation</label>
                                            <input type="text" name="sponsor_occupation" id="sponsor_occupation" 
                                                   class="form-control" 
                                                   value="<?php echo isset($sponsor) ? htmlspecialchars($sponsor['sponsor_occupation']) : ''; ?>"
                                                   placeholder="Enter occupation or business type">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information Tab -->
                            <div role="tabpanel" class="tab-pane" id="tab_contact_info">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email" class="control-label">Email Address</label>
                                            <input type="email" name="email" id="email" class="form-control" 
                                                   value="<?php echo isset($sponsor) ? htmlspecialchars($sponsor['email']) : ''; ?>"
                                                   placeholder="Enter email address">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="country_id" class="control-label">Country</label>
                                            <select name="country_id" id="country_id" class="form-control selectpicker" 
                                                    data-live-search="true" onchange="updatePhoneCode()">
                                                <option value="">Select Country</option>
                                                <option value="US" data-code="US">United States</option>
                                                <option value="IN" data-code="IN">India</option>
                                                <option value="GB" data-code="GB">United Kingdom</option>
                                                <option value="CA" data-code="CA">Canada</option>
                                                <option value="AU" data-code="AU">Australia</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="phone_code" class="control-label">Country Code</label>
                                            <input type="text" name="phone_code" id="phone_code" class="form-control" 
                                                   value="<?php echo isset($sponsor) ? htmlspecialchars($sponsor['phone_code']) : '+1'; ?>"
                                                   placeholder="+1">
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-group">
                                            <label for="contact_no" class="control-label">Phone Number</label>
                                            <input type="tel" name="contact_no" id="contact_no" class="form-control" 
                                                   value="<?php echo isset($sponsor) ? htmlspecialchars($sponsor['contact_no']) : ''; ?>"
                                                   placeholder="Enter phone number">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="address" class="control-label">Address</label>
                                            <textarea name="address" id="address" class="form-control" rows="3" 
                                                    placeholder="Enter complete address"><?php echo isset($sponsor) ? htmlspecialchars($sponsor['address']) : ''; ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="city" class="control-label">City</label>
                                            <input type="text" name="city" id="city" class="form-control" 
                                                   value="<?php echo isset($sponsor) ? htmlspecialchars($sponsor['city']) : ''; ?>"
                                                   placeholder="Enter city">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="state_id" class="control-label">State/Province</label>
                                            <select name="state_id" id="state_id" class="form-control selectpicker" 
                                                    data-live-search="true">
                                                <option value="">Select State/Province</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="zip" class="control-label">ZIP/Postal Code</label>
                                            <input type="text" name="zip" id="zip" class="form-control" 
                                                   value="<?php echo isset($sponsor) ? htmlspecialchars($sponsor['zip']) : ''; ?>"
                                                   placeholder="Enter ZIP/Postal code">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Banking Information Tab -->
                            <div role="tabpanel" class="tab-pane" id="tab_bank_info">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="sponsor_name_of_the_bank_id" class="control-label">Bank Name</label>
                                            <select name="sponsor_name_of_the_bank_id" id="sponsor_name_of_the_bank_id" 
                                                    class="form-control selectpicker" data-live-search="true">
                                                <option value="">Select Bank</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="sponsor_bank_branch_info" class="control-label">Branch Name</label>
                                            <input type="text" name="sponsor_bank_branch_info" id="sponsor_bank_branch_info" 
                                                   class="form-control" 
                                                   value="<?php echo isset($sponsor) ? htmlspecialchars($sponsor['sponsor_bank_branch_info']) : ''; ?>"
                                                   placeholder="Enter branch name">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="sponsor_bank_branch_number" class="control-label">IFSC Code / Branch Number</label>
                                            <input type="text" name="sponsor_bank_branch_number" id="sponsor_bank_branch_number" 
                                                   class="form-control" 
                                                   value="<?php echo isset($sponsor) ? htmlspecialchars($sponsor['sponsor_bank_branch_number']) : ''; ?>"
                                                   placeholder="Enter IFSC code or branch number">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="sponsor_bank_account_no" class="control-label">Bank Account Number</label>
                                            <input type="text" name="sponsor_bank_account_no" id="sponsor_bank_account_no" 
                                                   class="form-control" 
                                                   value="<?php echo isset($sponsor) ? htmlspecialchars($sponsor['sponsor_bank_account_no']) : ''; ?>"
                                                   placeholder="Enter bank account number">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sponsorship Details Tab -->
                            <div role="tabpanel" class="tab-pane" id="tab_sponsorship_info">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="membership_start_date" class="control-label">Membership Start Date</label>
                                            <input type="date" name="membership_start_date" 
                                                   id="membership_start_date" class="form-control"
                                                   value="<?php echo isset($sponsor) ? $sponsor['membership_start_date'] : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="membership_end_date" class="control-label">Membership Renewal Date</label>
                                            <input type="date" name="membership_end_date" 
                                                   id="membership_end_date" class="form-control"
                                                   value="<?php echo isset($sponsor) ? $sponsor['membership_end_date'] : ''; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="sponsor_frequency" class="control-label">Sponsor Frequency</label>
                                            <select name="sponsor_frequency" id="sponsor_frequency" class="form-control selectpicker">
                                                <option value="">Select Frequency</option>
                                                <option value="one_time" <?php echo (isset($sponsor) && $sponsor['sponsor_frequency'] == 'one_time') ? 'selected' : ''; ?>>One-time</option>
                                                <option value="monthly" <?php echo (isset($sponsor) && $sponsor['sponsor_frequency'] == 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                                                <option value="half_yearly" <?php echo (isset($sponsor) && $sponsor['sponsor_frequency'] == 'half_yearly') ? 'selected' : ''; ?>>Half-yearly</option>
                                                <option value="yearly" <?php echo (isset($sponsor) && $sponsor['sponsor_frequency'] == 'yearly') ? 'selected' : ''; ?>>Yearly</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Staff Account Tab -->
                            <div role="tabpanel" class="tab-pane" id="staff-account">
                            <div class="row">
                                <div class="col-md-12">
                                <div class="checkbox checkbox-primary">
                                    <input type="checkbox" id="create_staff" name="create_staff" value="1"
                                    <?= (isset($sponsor) && !empty($sponsor['staff_id'])) ? 'checked' : ''; ?>>
                                    <label for="create_staff">Create a Perfex Staff login for this student</label>
                                </div>
                                </div>
                            </div>

                            <!-- Login Email / Names / Password -->
                            <div class="row">
                                <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label">Login Email</label>
                                    <input type="email" name="staff_email" id="staff_email" class="form-control"
                                        value="<?= isset($sponsor) ? htmlspecialchars($sponsor['email']) : ''; ?>"
                                        placeholder="email@domain.com">
                                </div>
                                </div>
                                <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label">First Name</label>
                                    <input type="text" name="staff_firstname" id="staff_firstname" class="form-control"
                                        value="<?= isset($sponsor) ? htmlspecialchars($sponsor['name']) : ''; ?>">
                                </div>
                                </div>
                                <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label">Last Name</label>
                                    <input type="text" name="staff_lastname" id="staff_lastname" class="form-control" require>
                                </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label">Password </label>
                                    <input type="text" name="staff_password" id="staff_password" class="form-control" require>
                                </div>
                                </div>
                                <div class="col-md-4">
                                <div class="checkbox checkbox-primary" style="margin-top:28px;">
                                    <input type="checkbox" id="staff_active" name="staff_active" value="1" checked>
                                    <label for="staff_active">Allow this user to login (Active)</label>
                                </div>
                                </div>
                                <!-- <div class="col-md-4">
                                <div class="checkbox checkbox-primary" style="margin-top:28px;">
                                    <input type="checkbox" id="send_staff_welcome" name="send_staff_welcome" value="1">
                                    <label for="send_staff_welcome">Send welcome email</label>
                                </div>
                                </div> -->
                            </div>

                            <?php if (!empty($sponsor['id'])): ?>
                                <button type="submit"
                                        class="btn btn-success"
                                        formaction="<?= admin_url('student_sponsor_portal/grant_sponsor_access'); ?>"
                                        formmethod="post">
                                    Grant Portal Access
                                </button>

                                <button type="submit"
                                        class="btn btn-danger"
                                        formaction="<?= admin_url('student_sponsor_portal/revoke_sponsor_access'); ?>"
                                        formmethod="post">
                                    Revoke Portal Access
                                </button>
                                <?php endif; ?>

                            </div>




                        </div>

                        <!-- Form Actions -->
                            <div class="form-group" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fa fa-save"></i> <?= isset($sponsor) ? 'Update Sponsor' : 'Save Sponsor'; ?>
                            </button>
                            <a href="<?= admin_url('student_sponsor_portal/sponsors'); ?>" class="btn btn-default btn-lg">
                                <i class="fa fa-arrow-left"></i> Cancel
                            </a>
                            </div>

                        
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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