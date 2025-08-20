<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="customer-profile-group-heading"><?php echo $title; ?></h4>
                        <hr class="hr-panel-heading">
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="panel panel-primary">
                                    <div class="panel-heading">
                                        <h3 class="panel-title">
                                            <i class="fa fa-child"></i> School Students
                                        </h3>
                                    </div>
                                    <div class="panel-body text-center">
                                        <h2><?php echo $school_count; ?></h2>
                                        <a href="<?php echo admin_url('student_sponsor_portal/school_form'); ?>" class="btn btn-primary">
                                            Register New
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="panel panel-success">
                                    <div class="panel-heading">
                                        <h3 class="panel-title">
                                            <i class="fa fa-graduation-cap"></i> University Students
                                        </h3>
                                    </div>
                                    <div class="panel-body text-center">
                                        <h2><?php echo $university_count; ?></h2>
                                        <a href="<?php echo admin_url('student_sponsor_portal/university_form'); ?>" class="btn btn-success">
                                            Register New
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="panel panel-info">
                                    <div class="panel-heading">
                                        <h3 class="panel-title">
                                            <i class="fa fa-handshake-o"></i> Sponsors
                                        </h3>
                                    </div>
                                    <div class="panel-body text-center">
                                        <h2><?php echo $sponsor_count; ?></h2>
                                        <a href="<?php echo admin_url('student_sponsor_portal/sponsor_form'); ?>" class="btn btn-info">
                                            Register New
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row margin-top-20">
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h3 class="panel-title">Quick Actions</h3>
                                    </div>
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <a href="<?php echo admin_url('student_sponsor_portal/school_form'); ?>" class="btn btn-block btn-primary">
                                                    <i class="fa fa-plus"></i> Add School Student
                                                </a>
                                            </div>
                                            <div class="col-md-3">
                                                <a href="<?php echo admin_url('student_sponsor_portal/university_form'); ?>" class="btn btn-block btn-success">
                                                    <i class="fa fa-plus"></i> Add University Student
                                                </a>
                                            </div>
                                            <div class="col-md-3">
                                                <a href="<?php echo admin_url('student_sponsor_portal/sponsor_form'); ?>" class="btn btn-block btn-info">
                                                    <i class="fa fa-plus"></i> Add Sponsor
                                                </a>
                                            </div>
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-block btn-warning" onclick="refreshStats()">
                                                    <i class="fa fa-refresh"></i> Refresh Stats
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function refreshStats() {
    $.ajax({
        url: '<?php echo admin_url('student_sponsor_portal/get_stats'); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            location.reload();
        },
        error: function() {
            alert('Failed to refresh stats');
        }
    });
}
</script>

<?php init_tail(); ?>