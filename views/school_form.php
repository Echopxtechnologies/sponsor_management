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
                        
                        <?php echo form_open(admin_url('student_sponsor_portal/school_form')); ?>
                        
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
                            <label for="grade" class="control-label">Grade/Class</label>
                            <select name="grade" id="grade" class="form-control">
                                <option value="">Select Grade</option>
                                <option value="1">Grade 1</option>
                                <option value="2">Grade 2</option>
                                <option value="3">Grade 3</option>
                                <option value="4">Grade 4</option>
                                <option value="5">Grade 5</option>
                                <option value="6">Grade 6</option>
                                <option value="7">Grade 7</option>
                                <option value="8">Grade 8</option>
                                <option value="9">Grade 9</option>
                                <option value="10">Grade 10</option>
                                <option value="11">Grade 11</option>
                                <option value="12">Grade 12</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="school_id" class="control-label">School ID</label>
                            <input type="text" name="school_id" id="school_id" class="form-control" placeholder="Enter school ID number">
                        </div>
                        
                        <div class="form-group">
                            <label for="dob" class="control-label">Date of Birth</label>
                            <input type="date" name="dob" id="dob" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Register Student</button>
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