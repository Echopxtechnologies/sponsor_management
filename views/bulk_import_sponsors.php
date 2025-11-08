<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <div class="panel_s">
          <div class="panel-body">
            
            <!-- Header -->
            <div class="row">
              <div class="col-md-8">
                <h4 class="customer-profile-group-heading">
                  <i class="fa fa-upload"></i> <?php echo $title; ?>
                </h4>
                <p class="text-muted">Import multiple sponsors from Excel or CSV files</p>
              </div>
              <div class="col-md-4 text-right">
                <a href="<?php echo admin_url('student_sponsor_portal/sponsors'); ?>" class="btn btn-default">
                  <i class="fa fa-arrow-left"></i> Back to Sponsors
                </a>
              </div>
            </div>
            
            <hr class="hr-panel-heading">

            <!-- Instructions Panel -->
            <div class="alert alert-info">
              <h5><i class="fa fa-info-circle"></i> Import Instructions</h5>
              <ul style="margin-bottom: 0;">
                <li><strong>Step 1:</strong> Download the Excel template to see the required format</li>
                <li><strong>Step 2:</strong> Fill in your sponsor data using the template</li>
                <li><strong>Step 3:</strong> Upload your completed Excel or CSV file below</li>
                <li><strong>Supported formats:</strong> .xlsx, .xls, .csv (max 20MB)</li>
                <li><strong>Updates:</strong> Sponsors with existing email/account number will be updated</li>
                <li><strong>Sponsor Types:</strong> Individual, Organization, Corporate, Foundation, etc.</li>
              </ul>
            </div>

            <!-- Download Template -->
            <div class="row" style="margin-bottom: 30px;">
              <div class="col-md-12">
                <div class="well well-sm">
                  <div class="row">
                    <div class="col-md-8">
                      <h5 style="margin-top: 5px;"><i class="fa fa-download"></i> Download Import Template</h5>
                      <p class="text-muted">Get the Excel template with all required columns, sample data, and instructions</p>
                    </div>
                    <div class="col-md-4 text-right">
                      <a href="<?php echo admin_url('student_sponsor_portal/download_sponsors_template'); ?>" 
                         class="btn btn-info btn-lg">
                        <i class="fa fa-download"></i> Download Excel Template
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- File Format Support -->
            <div class="row" style="margin-bottom: 20px;">
              <div class="col-md-4">
                <div class="format-support">
                  <div class="format-icon">
                    <i class="fa fa-file-excel-o fa-2x text-success"></i>
                  </div>
                  <h6>Excel Files (.xlsx, .xls)</h6>
                  <p class="text-muted">Recommended format with advanced features</p>
                </div>
              </div>
              <div class="col-md-4">
                <div class="format-support">
                  <div class="format-icon">
                    <i class="fa fa-file-text-o fa-2x text-info"></i>
                  </div>
                  <h6>CSV Files (.csv)</h6>
                  <p class="text-muted">Universal format, simple and compatible</p>
                </div>
              </div>
              <div class="col-md-4">
                <div class="format-support">
                  <div class="format-icon">
                    <i class="fa fa-cloud-upload fa-2x text-primary"></i>
                  </div>
                  <h6>20MB Max Size</h6>
                  <p class="text-muted">Support for large sponsor databases</p>
                </div>
              </div>
            </div>

            <!-- Upload Form -->
            <?php echo form_open_multipart(admin_url('student_sponsor_portal/bulk_import_sponsors'), ['id' => 'import-form']); ?>
            
            <div class="form-group">
              <label for="import_file" class="control-label">
                <i class="fa fa-file-o"></i> Select Excel or CSV File *
              </label>
              <input type="file" 
                     name="import_file" 
                     id="import_file" 
                     class="form-control" 
                     accept=".xlsx,.xls,.csv" 
                     required>
              <small class="text-muted">
                Supported formats: Excel (.xlsx, .xls) and CSV (.csv). Maximum file size: 20MB
              </small>
            </div>

            <div class="form-group">
              <div class="checkbox checkbox-primary">
                <input type="checkbox" id="confirm_backup" required>
                <label for="confirm_backup">
                  I understand that this import will add/update sponsor records and I have backed up my data
                </label>
              </div>
            </div>

            <div class="form-group">
              <button type="submit" class="btn btn-primary btn-lg" id="btn-import">
                <i class="fa fa-upload"></i> Import Sponsors
              </button>
              <a href="<?php echo admin_url('student_sponsor_portal/sponsors'); ?>" class="btn btn-default btn-lg">
                <i class="fa fa-times"></i> Cancel
              </a>
            </div>

            <?php echo form_close(); ?>

            <!-- Import Errors Display -->
            <?php if (!empty($import_errors)): ?>
              <div class="alert alert-warning" style="margin-top: 30px;">
                <h5><i class="fa fa-exclamation-triangle"></i> Import Errors/Warnings</h5>
                <div style="max-height: 300px; overflow-y: auto;">
                  <?php foreach ($import_errors as $error): ?>
                    <div style="margin-bottom: 5px;">• <?php echo html_escape($error); ?></div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

            <!-- Import Features -->
            <div class="row" style="margin-top: 40px;">
              <div class="col-md-6">
                <div class="panel panel-default">
                  <div class="panel-heading">
                    <h5><i class="fa fa-star text-danger"></i> Required Fields</h5>
                  </div>
                  <div class="panel-body">
                    <ul class="list-unstyled">
                      <li><i class="fa fa-check text-success"></i> <strong>Name</strong> - Sponsor's full name or organization</li>
                      <li><i class="fa fa-info-circle text-info"></i> Email (recommended for updates)</li>
                    </ul>
                    <small class="text-muted">
                      Other fields are optional but recommended for complete profiles
                    </small>
                  </div>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="panel panel-default">
                  <div class="panel-heading">
                    <h5><i class="fa fa-cogs text-primary"></i> Import Features</h5>
                  </div>
                  <div class="panel-body">
                    <ul class="list-unstyled">
                      <li><i class="fa fa-refresh text-success"></i> Update existing sponsors automatically</li>
                      <li><i class="fa fa-plus text-info"></i> Create banks/countries if not found</li>
                      <li><i class="fa fa-calendar text-primary"></i> Excel date format support</li>
                      <li><i class="fa fa-list text-success"></i> Detailed error reporting</li>
                      <li><i class="fa fa-users text-warning"></i> Sponsor type validation</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <!-- Column Reference -->
            <div class="row">
              <div class="col-md-12">
                <div class="panel panel-default">
                  <div class="panel-heading">
                    <h5><i class="fa fa-table text-info"></i> Column Reference</h5>
                  </div>
                  <div class="panel-body">
                    <div class="row">
                      <div class="col-md-4">
                        <h6>Basic Information</h6>
                        <ul class="list-unstyled small">
                          <li>• Name, Email, Phone</li>
                          <li>• Address, City, Postal Code</li>
                          <li>• Country</li>
                        </ul>
                      </div>
                      <div class="col-md-4">
                        <h6>Sponsor Details</h6>
                        <ul class="list-unstyled small">
                          <li>• Sponsor Type, Frequency</li>
                          <li>• Occupation</li>
                          <li>• Company information</li>
                        </ul>
                      </div>
                      <div class="col-md-4">
                        <h6>Additional Information</h6>
                        <ul class="list-unstyled small">
                          <li>• Bank details</li>
                          <li>• Membership dates</li>
                          <li>• Notes/comments</li>
                        </ul>
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

<style>
.format-support {
  text-align: center;
  padding: 20px;
  border: 1px solid #e9ecef;
  border-radius: 4px;
  height: 140px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.format-icon {
  margin-bottom: 10px;
}

.format-support h6 {
  margin-bottom: 8px;
  font-weight: 600;
}

.format-support p {
  margin-bottom: 0;
  font-size: 12px;
}

.progress-bar-custom {
  background-color: #337ab7;
  transition: width 0.3s ease-in-out;
}

#upload-progress {
  display: none;
  margin-top: 15px;
}
</style>

<script>
$(document).ready(function() {
  var submitting = false;
  
  $('#import-form').on('submit', function(e) {
    if (submitting) {
      e.preventDefault();
      return false;
    }
    
    var fileInput = $('#import_file')[0];
    if (!fileInput.files || !fileInput.files[0]) {
      alert('Please select a file to import');
      e.preventDefault();
      return false;
    }
    
    var file = fileInput.files[0];
    var maxSize = 20 * 1024 * 1024; // 20MB
    
    if (file.size > maxSize) {
      alert('File size exceeds 20MB limit. Please choose a smaller file.');
      e.preventDefault();
      return false;
    }
    
    // Check file extension
    var fileName = file.name.toLowerCase();
    var allowedExtensions = ['.xlsx', '.xls', '.csv'];
    var isValidExtension = allowedExtensions.some(function(ext) {
      return fileName.endsWith(ext);
    });
    
    if (!isValidExtension) {
      alert('Please select a valid Excel (.xlsx, .xls) or CSV (.csv) file.');
      e.preventDefault();
      return false;
    }
    
    if (!$('#confirm_backup').is(':checked')) {
      alert('Please confirm that you have backed up your data before proceeding');
      e.preventDefault();
      return false;
    }
    
    var fileType = fileName.endsWith('.csv') ? 'CSV' : 'Excel';
    if (!confirm('Are you sure you want to import this ' + fileType + ' file? This will add/update sponsor records in the database.')) {
      e.preventDefault();
      return false;
    }
    
    submitting = true;
    $('#btn-import').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Importing...');
    
    // Show progress indicator
    var progressHtml = '<div id="upload-progress" class="progress" style="display: block;">' +
                      '<div class="progress-bar progress-bar-custom" role="progressbar" style="width: 0%">' +
                      '<span class="sr-only">0% Complete</span></div></div>' +
                      '<p class="text-info" style="margin-top: 10px;">' +
                      '<i class="fa fa-info-circle"></i> Processing ' + fileType + ' file... This may take several minutes for large files.</p>';
    
    $(this).after(progressHtml);
    
    // Simulate progress for user feedback
    var progress = 0;
    var progressInterval = setInterval(function() {
      progress += Math.random() * 15;
      if (progress > 85) progress = 85;
      $('.progress-bar').css('width', progress + '%');
    }, 500);
    
    this.progressInterval = progressInterval;
  });
  
  // File input change handler
  $('#import_file').on('change', function() {
    var file = this.files[0];
    if (file) {
      var maxSize = 20 * 1024 * 1024;
      if (file.size > maxSize) {
        $(this).val('');
        alert('File size exceeds 20MB limit. Please choose a smaller file.');
        return;
      }
      
      var fileName = file.name.toLowerCase();
      var allowedExtensions = ['.xlsx', '.xls', '.csv'];
      var isValidExtension = allowedExtensions.some(function(ext) {
        return fileName.endsWith(ext);
      });
      
      if (!isValidExtension) {
        $(this).val('');
        alert('Please select a valid Excel (.xlsx, .xls) or CSV (.csv) file.');
        return;
      }
      
      var fileSize = (file.size / 1024 / 1024).toFixed(2);
      var fileType = fileName.endsWith('.csv') ? 'CSV' : 'Excel';
      var fileInfo = fileType + ' file selected: ' + file.name + ' (' + fileSize + ' MB)';
      
      if (typeof alert_float === 'function') {
        alert_float('info', fileInfo);
      }
    }
  });
  
  $('#upload-progress').remove();
});
</script>

<?php init_tail(); ?>