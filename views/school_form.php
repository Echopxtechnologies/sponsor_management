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
                                    <i class="fa fa-graduation-cap"></i> <?php echo isset($student) ? 'Edit School Student' : 'Register School Student'; ?>
                                </h4>
                            </div>
                            <div class="col-md-4 text-right">
                                <a href="<?php echo admin_url('student_sponsor_portal/school_students'); ?>" class="btn btn-default">
                                    <i class="fa fa-arrow-left"></i> Back to List
                                </a>
                            </div>
                        </div>
                        <hr class="hr-panel-heading">

                        <?php echo form_open(admin_url('student_sponsor_portal/school_student_form'), ['id' => 'student-form']); ?>
                        <?php if(isset($student)): ?>
                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                        <?php endif; ?>

                        <!-- Tab Navigation -->
                        <ul class="nav nav-tabs" role="tablist">
                            <li role="presentation" class="active">
                                <a href="#student-info" aria-controls="student-info" role="tab" data-toggle="tab">
                                    <i class="fa fa-user"></i> Student Info
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#sponsorship" aria-controls="sponsorship" role="tab" data-toggle="tab">
                                    <i class="fa fa-heart"></i> Sponsorship
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#bank-details" aria-controls="bank-details" role="tab" data-toggle="tab">
                                    <i class="fa fa-bank"></i> Bank Details
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#additional-info" aria-controls="additional-info" role="tab" data-toggle="tab">
                                    <i class="fa fa-info-circle"></i> Additional Info
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#report-card" aria-controls="report-card" role="tab" data-toggle="tab">
                                    <i class="fa fa-graduation-cap"></i> Report Card
                                </a>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" style="margin-top: 20px;">
                            
                            <!-- Student Info Tab -->
                            <div role="tabpanel" class="tab-pane active" id="student-info">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name" class="control-label">Full Name *</label>
                                            <input type="text" name="name" id="name" required class="form-control" 
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['name']) : ''; ?>"
                                                   placeholder="Enter student's full name">
                                        </div>

                                        <div class="form-group">
                                            <label for="email" class="control-label">Email Address</label>
                                            <input type="email" name="email" id="email" class="form-control" 
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['email']) : ''; ?>"
                                                   placeholder="Enter email address">
                                        </div>

                                        <div class="form-group">
                                            <label for="phone" class="control-label">Phone Number</label>
                                            <input type="text" name="phone" id="phone" class="form-control" 
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['phone']) : ''; ?>"
                                                   placeholder="Enter phone number">
                                        </div>

                                        <div class="form-group">
                                            <label for="grade" class="control-label">Grade/Class</label>
                                            <select name="grade" id="grade" class="form-control">
                                                <option value="">Select Grade</option>
                                                <option value="1" <?php echo (isset($student) && $student['grade'] == '1') ? 'selected' : ''; ?>>Grade 1</option>
                                                <option value="2" <?php echo (isset($student) && $student['grade'] == '2') ? 'selected' : ''; ?>>Grade 2</option>
                                                <option value="3" <?php echo (isset($student) && $student['grade'] == '3') ? 'selected' : ''; ?>>Grade 3</option>
                                                <option value="4" <?php echo (isset($student) && $student['grade'] == '4') ? 'selected' : ''; ?>>Grade 4</option>
                                                <option value="5" <?php echo (isset($student) && $student['grade'] == '5') ? 'selected' : ''; ?>>Grade 5</option>
                                                <option value="6" <?php echo (isset($student) && $student['grade'] == '6') ? 'selected' : ''; ?>>Grade 6</option>
                                                <option value="7" <?php echo (isset($student) && $student['grade'] == '7') ? 'selected' : ''; ?>>Grade 7</option>
                                                <option value="8" <?php echo (isset($student) && $student['grade'] == '8') ? 'selected' : ''; ?>>Grade 8</option>
                                                <option value="9" <?php echo (isset($student) && $student['grade'] == '9') ? 'selected' : ''; ?>>Grade 9</option>
                                                <option value="10" <?php echo (isset($student) && $student['grade'] == '10') ? 'selected' : ''; ?>>Grade 10</option>
                                                <option value="11" <?php echo (isset($student) && $student['grade'] == '11') ? 'selected' : ''; ?>>Grade 11</option>
                                                <option value="12" <?php echo (isset($student) && $student['grade'] == '12') ? 'selected' : ''; ?>>Grade 12</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="school_id" class="control-label">School ID</label>
                                            <input type="text" name="school_id" id="school_id" class="form-control" 
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['school_id']) : ''; ?>"
                                                   placeholder="Enter school ID number">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="dob" class="control-label">Date of Birth</label>
                                            <input type="date" name="dob" id="dob" class="form-control"
                                                   value="<?php echo isset($student) ? $student['dob'] : ''; ?>">
                                        </div>

                                        <div class="form-group">
                                            <label for="school_type" class="control-label">School Type</label>
                                            <input type="text" name="school_type" id="school_type" class="form-control" 
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['school_type']) : ''; ?>"
                                                   placeholder="e.g. Type 1AB">
                                        </div>

                                        <div class="form-group">
                                            <label for="school_name" class="control-label">School Name</label>
                                            <input type="text" name="school_name" id="school_name" class="form-control"
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['school_name']) : ''; ?>">
                                        </div>

                                        <div class="form-group">
                                            <label for="graduation_exam_date" class="control-label">Graduation Exam Date</label>
                                            <input type="date" name="graduation_exam_date" id="graduation_exam_date" class="form-control"
                                                   value="<?php echo isset($student) ? $student['graduation_exam_date'] : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sponsorship Tab -->
                            <div role="tabpanel" class="tab-pane" id="sponsorship">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="sponsorship_start" class="control-label">Sponsorship Start Date</label>
                                            <input type="date" name="sponsorship_start" id="sponsorship_start" class="form-control"
                                                   value="<?php echo isset($student) ? $student['sponsorship_start'] : ''; ?>">
                                        </div>

                                        <div class="form-group">
                                            <label for="sponsorship_end" class="control-label">Sponsorship End Date</label>
                                            <input type="date" name="sponsorship_end" id="sponsorship_end" class="form-control"
                                                   value="<?php echo isset($student) ? $student['sponsorship_end'] : ''; ?>">
                                        </div>

                                        <div class="form-group">
                                            <label for="sponsors" class="control-label">Sponsors</label>
                                            <input type="text" name="sponsors" id="sponsors" class="form-control" 
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['sponsors']) : ''; ?>"
                                                   placeholder="Enter sponsor names">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="introduced_by" class="control-label">Introduced By</label>
                                            <input type="text" name="introduced_by" id="introduced_by" class="form-control" 
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['introduced_by']) : ''; ?>"
                                                   placeholder="Person who introduced the student">
                                        </div>

                                        <div class="form-group">
                                            <label for="introduced_phone" class="control-label">Introducer's Phone</label>
                                            <input type="text" name="introduced_phone" id="introduced_phone" class="form-control" 
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['introduced_phone']) : ''; ?>"
                                                   placeholder="Contact number">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bank Details Tab -->
                            <div role="tabpanel" class="tab-pane" id="bank-details">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="bank_name" class="control-label">Bank Name</label>
                                            <input type="text" name="bank_name" id="bank_name" class="form-control" 
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['bank_name']) : ''; ?>"
                                                   placeholder="Name of the bank">
                                        </div>

                                        <div class="form-group">
                                            <label for="bank_branch_number" class="control-label">Bank Branch Number</label>
                                            <input type="text" name="bank_branch_number" id="bank_branch_number" class="form-control" 
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['bank_branch_number']) : ''; ?>"
                                                   placeholder="Branch code/number">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="bank_account_number" class="control-label">Bank Account Number</label>
                                            <input type="text" name="bank_account_number" id="bank_account_number" class="form-control" 
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['bank_account_number']) : ''; ?>"
                                                   placeholder="Account number">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="bank_branch_info" class="control-label">Bank Branch Information</label>
                                            <textarea name="bank_branch_info" id="bank_branch_info" class="form-control" rows="3" 
                                                    placeholder="Additional branch details, address, etc."><?php echo isset($student) ? htmlspecialchars($student['bank_branch_info']) : ''; ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Info Tab -->
                            <div role="tabpanel" class="tab-pane" id="additional-info">
                                <!-- Family Information -->
                                <h5><i class="fa fa-users"></i> Family Information</h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="father_name" class="control-label">Father's Name</label>
                                            <input type="text" name="father_name" id="father_name" class="form-control"
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['father_name']) : ''; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="father_income" class="control-label">Father's Income</label>
                                            <input type="text" name="father_income" id="father_income" class="form-control" 
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['father_income']) : ''; ?>"
                                                   placeholder="Monthly income">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="mother_name" class="control-label">Mother's Name</label>
                                            <input type="text" name="mother_name" id="mother_name" class="form-control"
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['mother_name']) : ''; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="mother_income" class="control-label">Mother's Income</label>
                                            <input type="text" name="mother_income" id="mother_income" class="form-control" 
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['mother_income']) : ''; ?>"
                                                   placeholder="Monthly income">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="guardian_name" class="control-label">Guardian's Name</label>
                                            <input type="text" name="guardian_name" id="guardian_name" class="form-control"
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['guardian_name']) : ''; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="guardian_income" class="control-label">Guardian's Income</label>
                                            <input type="text" name="guardian_income" id="guardian_income" class="form-control" 
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['guardian_income']) : ''; ?>"
                                                   placeholder="Monthly income">
                                        </div>
                                    </div>
                                </div>

                                <!-- Address Information -->
                                <h5><i class="fa fa-map-marker"></i> Address Information</h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="address" class="control-label">Address</label>
                                            <textarea name="address" id="address" class="form-control" rows="3" 
                                                    placeholder="Complete address"><?php echo isset($student) ? htmlspecialchars($student['address']) : ''; ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="district" class="control-label">District</label>
                                            <input type="text" name="district" id="district" class="form-control"
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['district']) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="postal_code" class="control-label">Postal Code</label>
                                            <input type="text" name="postal_code" id="postal_code" class="form-control"
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['postal_code']) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="country" class="control-label">Country</label>
                                            <input type="text" name="country" id="country" class="form-control" 
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['country']) : 'Sri Lanka'; ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Comments -->
                                <h5><i class="fa fa-comments"></i> Comments & Notes</h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="background_information" class="control-label">Background Information</label>
                                            <textarea name="background_information" id="background_information" class="form-control" rows="3" 
                                                    placeholder="Student's background, family situation, etc."><?php echo isset($student) ? htmlspecialchars($student['background_information']) : ''; ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="internal_comment" class="control-label">Internal Comment</label>
                                            <textarea name="internal_comment" id="internal_comment" class="form-control" rows="3" 
                                                    placeholder="Internal notes (not visible to sponsors)"><?php echo isset($student) ? htmlspecialchars($student['internal_comment']) : ''; ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="external_comment" class="control-label">External Comment</label>
                                            <textarea name="external_comment" id="external_comment" class="form-control" rows="3" 
                                                    placeholder="Comments visible to sponsors"><?php echo isset($student) ? htmlspecialchars($student['external_comment']) : ''; ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Report Card Tab -->
                            <div role="tabpanel" class="tab-pane" id="report-card">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="academic_year" class="control-label">Academic Year</label>
                                            <input type="text" name="academic_year" id="academic_year" class="form-control" 
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['academic_year']) : ''; ?>"
                                                   placeholder="e.g., 2024-2025">
                                        </div>
                                        <div class="form-group">
                                            <label for="term" class="control-label">Term/Semester</label>
                                            <select name="term" id="term" class="form-control">
                                                <option value="">Select Term</option>
                                                <option value="1st_term" <?php echo (isset($student) && $student['term'] == '1st_term') ? 'selected' : ''; ?>>1st Term</option>
                                                <option value="2nd_term" <?php echo (isset($student) && $student['term'] == '2nd_term') ? 'selected' : ''; ?>>2nd Term</option>
                                                <option value="3rd_term" <?php echo (isset($student) && $student['term'] == '3rd_term') ? 'selected' : ''; ?>>3rd Term</option>
                                                <option value="annual" <?php echo (isset($student) && $student['term'] == 'annual') ? 'selected' : ''; ?>>Annual</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="overall_grade" class="control-label">Overall Grade</label>
                                            <input type="text" name="overall_grade" id="overall_grade" class="form-control" 
                                                   value="<?php echo isset($student) ? htmlspecialchars($student['overall_grade']) : ''; ?>"
                                                   placeholder="A, B, C, etc.">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="percentage" class="control-label">Overall Percentage</label>
                                            <input type="number" name="percentage" id="percentage" class="form-control" 
                                                   value="<?php echo isset($student) ? $student['percentage'] : ''; ?>"
                                                   placeholder="85.5" step="0.1" min="0" max="100">
                                        </div>
                                        <div class="form-group">
                                            <label for="class_rank" class="control-label">Class Rank</label>
                                            <input type="number" name="class_rank" id="class_rank" class="form-control" 
                                                   value="<?php echo isset($student) ? $student['class_rank'] : ''; ?>"
                                                   placeholder="Position in class">
                                        </div>
                                        <div class="form-group">
                                            <label for="attendance" class="control-label">Attendance (%)</label>
                                            <input type="number" name="attendance" id="attendance" class="form-control" 
                                                   value="<?php echo isset($student) ? $student['attendance'] : ''; ?>"
                                                   placeholder="95" min="0" max="100">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="teacher_comments" class="control-label">Teacher Comments</label>
                                            <textarea name="teacher_comments" id="teacher_comments" class="form-control" rows="4" 
                                                    placeholder="Teacher's feedback on student performance"><?php echo isset($student) ? htmlspecialchars($student['teacher_comments']) : ''; ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="subjects_performance" class="control-label">Subject-wise Performance</label>
                                            <textarea name="subjects_performance" id="subjects_performance" class="form-control" rows="4" 
                                                    placeholder="Mathematics: A, Science: B+, English: A-, etc."><?php echo isset($student) ? htmlspecialchars($student['subjects_performance']) : ''; ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-group" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fa fa-save"></i> <?php echo isset($student) ? 'Update Student' : 'Register Student'; ?>
                            </button>
                            <a href="<?php echo admin_url('student_sponsor_portal/school_students'); ?>" class="btn btn-default btn-lg">
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

<!-- Add some custom CSS for better tab styling -->
<style>
.nav-tabs > li > a {
    font-weight: 500;
}
.nav-tabs > li.active > a,
.nav-tabs > li.active > a:hover,
.nav-tabs > li.active > a:focus {
    background-color: #f8f9fa;
    border-bottom-color: transparent;
}
.tab-content {
    background-color: #f8f9fa;
    padding: 20px;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 4px 4px;
}
.tab-content h5 {
    color: #337ab7;
    margin-top: 20px;
    margin-bottom: 10px;
}
.tab-content h5:first-child {
    margin-top: 0;
}
.tab-content hr {
    margin: 10px 0 20px 0;
}
</style>

<script>
// Form validation
$(document).ready(function() {
    $('#student-form').on('submit', function(e) {
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
});
</script>

<?php init_tail(); ?>