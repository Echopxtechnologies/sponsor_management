<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php /** @var string $username_value */ /** @var string $user_type_value */ /** @var array $existing_types */ ?>

<div id="ssp-usertype-wrapper" style="display:none;">
  <div class="row">
    <!-- Username (tblclients.username) -->
    <div class="col-md-6">
      <div class="form-group">
        <label>Username</label>
        <input type="text" class="form-control" name="username"
               value="<?php echo html_escape($username_value); ?>">
      </div>
    </div>

    <!-- User Type (tblclients.user_type) -->
    <div class="col-md-6">
      <div class="form-group select-placeholder">
        <label>User Type</label>
        <div class="input-group">
          <select id="user_type" name="user_type" class="selectpicker"
                  data-width="100%" data-live-search="true"
                  data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
            <option value=""></option>
            <?php foreach ($existing_types as $t): $n = (string)$t['name']; ?>
              <option value="<?php echo html_escape($n); ?>"
                <?php echo ($user_type_value === $n ? 'selected' : ''); ?>>
                <?php echo html_escape($n); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <span class="input-group-btn">
            <button class="btn btn-default" type="button" id="quick-add-usertype">+</button>
          </span>
        </div>
        <small class="text-muted">Pick an existing type or click + to add a new one.</small>
      </div>
    </div>
  </div>

  <!-- Quick-add modal (client-side only; no server insert needed) -->
  <div class="modal fade" id="modalQuickUserType" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document"><div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Add User Type</h4>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Name</label>
          <input type="text" id="quick_usertype_name" class="form-control">
        </div>
        <div class="alert alert-danger hide" id="quick_usertype_error"></div>
      </div>
      <div class="modal-footer"><button type="button" id="btnSaveQuickType" class="btn btn-primary">Save</button></div>
    </div></div>
  </div>
</div>

<script>
$(function(){
  $('#quick-add-usertype').on('click', function(e){
    e.preventDefault();
    $('#quick_usertype_name').val('');
    $('#quick_usertype_error').addClass('hide').text('');
    $('#modalQuickUserType').modal('show');
  });

  // Client-side add: append to select, select it, and rely on form save to persist in tblclients.user_type
  $('#btnSaveQuickType').on('click', function(){
    var name = $.trim($('#quick_usertype_name').val());
    if(!name){ $('#quick_usertype_error').removeClass('hide').text('Please enter a name'); return; }
    var $s = $('#user_type');
    if ($s.find('option[value="'+name.replace(/"/g,'&quot;')+'"]').length === 0) {
      $s.append('<option value="'+$('<div/>').text(name).html()+'">'+$('<div/>').text(name).html()+'</option>');
    }
    $s.selectpicker('refresh');
    $s.selectpicker('val', name);
    $('#modalQuickUserType').modal('hide');
  });
});
</script>
