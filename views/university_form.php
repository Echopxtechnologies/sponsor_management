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
                        
                        <?php echo form_open(admin_url('student_sponsor_portal/university_form')); ?>
                        
                        <div class="form-group">
                            <label for="name" class="control-label">Full Name *</label>
                            <input type="text" name="name" id="name" required class="form-control" placeholder="Enter student's full name">
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
                            <label for="program" class="control-label">Program/Course</label>
                            <input type="text" name="program" id="program" class="form-control" placeholder="Enter program or course name">
                        </div>
                        
                        <div class="form-group">
                            <label for="year" class="control-label">Year of Study</label>
                            <select name="year" id="year" class="form-control">
                                <option value="">Select Year</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                                <option value="5th Year">5th Year</option>
                                <option value="Graduate">Graduate</option>
                                <option value="Post Graduate">Post Graduate</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="dob" class="control-label">Date of Birth</label>
                            <input type="date" name="dob" id="dob" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-success">Register Student</button>
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