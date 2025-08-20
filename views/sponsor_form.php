<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="customer-profile-group-heading"><?php echo $title; ?></h4>
                        <hr class="hr-panel-heading">
                        
                        <?php echo form_open(admin_url('student_sponsor_portal/sponsor_form')); ?>
                        
                        <div class="form-group">
                            <label for="name" class="control-label">Full Name / Organization Name *</label>
                            <input type="text" name="name" id="name" required class="form-control" placeholder="Enter name or organization name">
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="control-label">Email Address</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="Enter email address">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone" class="control-label">Phone Number</label>
                            <input type="text" name="phone" id="phone" class="form-control" placeholder="Enter phone number">
                        </div>
                        
                        <div class="form-group">
                            <label for="sponsor_type" class="control-label">Sponsor Type</label>
                            <select name="sponsor_type" id="sponsor_type" class="form-control">
                                <option value="">Select Type</option>
                                <option value="individual">Individual</option>
                                <option value="company">Company</option>
                                <option value="foundation">Foundation</option>
                                <option value="government">Government</option>
                                <option value="ngo">NGO</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-info">Register Sponsor</button>
                            <a href="<?php echo admin_url('student_sponsor_portal'); ?>" class="btn btn-default">Back to Dashboard</a>
                        </div>
                        
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>