<?php
/*
Module Name: Student Sponsor Portal
Description: A module for registering school students, university students, and sponsors
Version: 1.0.0
Author: Echo px
Requires at least: 2.3.2
*/
defined('BASEPATH') or exit('No direct script access allowed');

hooks()->add_action('after_cron_run', function () {
    $CI = &get_instance();
    $CI->load->model('student_sponsor_portal/sponsor_transactions_model');
    $res = $CI->sponsor_transactions_model->run_due_reminder_cron();
    log_message('info', '[SSP] due_reminder_cron: before=' . $res['sent_before'] . ', due_day=' . $res['sent_due_day']);
});

/* ---------------- Activation / Deactivation ---------------- */
register_activation_hook('student_sponsor_portal', 'student_sponsor_portal_activation_hook');
register_deactivation_hook('student_sponsor_portal', 'student_sponsor_portal_deactivation_hook');

$__install = __DIR__ . '/install.php';
if (is_file($__install)) { require_once($__install); }

function student_sponsor_portal_activation_hook() {
    if (function_exists('student_sponsor_portal_module_install')) {
        student_sponsor_portal_module_install();
    }
}
function student_sponsor_portal_deactivation_hook() {
    log_activity('Student Sponsor Portal Module Deactivated');
}

hooks()->add_action('app_admin_head', function () {
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">';
    echo '<style>
      .select2-results__option .text-muted{color:#777}
      .select2-container--default .select2-selection--single{
        height:38px;padding:5px 8px;border:1px solid #d1d5db;border-radius:4px
      }
      .select2-selection__rendered{line-height:26px}
      .select2-selection__arrow{height:36px}
    </style>';
});

/* ===================== ENHANCED ADMIN MENU WITH ROLE-BASED ACCESS ===================== */

/* ---------------- Dashboard Redirect Hook for All Portal Users ---------------- */
hooks()->add_action('admin_init', 'check_student_dashboard_redirect');
/* ---------------- Redirect Regular Staff to Module Dashboard on Login ---------------- */
hooks()->add_action('after_staff_login', 'redirect_regular_staff_to_module');

function redirect_regular_staff_to_module($staff_id) {
    $CI = &get_instance();
    
    // Skip if user is a sponsor
    $is_sponsor = $CI->db->select('id')->where('staff_id', $staff_id)
                         ->where('entity_type', 'sponsor')->where('active', 1)
                         ->limit(1)->get(db_prefix() . 'sponsor_records')->row();
    if ($is_sponsor) return;
    
    // Skip if user is a university student
    $is_uni_student = $CI->db->select('id')->where('staff_id', $staff_id)
                             ->where('entity_type', 'university')->where('active', 1)
                             ->limit(1)->get(db_prefix() . 'university_students')->row();
    if ($is_uni_student) return;
    
    // Skip if user is a school student
    $is_school_student = $CI->db->select('id')->where('staff_id', $staff_id)
                                ->where('entity_type', 'school')->where('staff_active', 1)
                                ->limit(1)->get(db_prefix() . 'school_students')->row();
    if ($is_school_student) return;
    
    // Regular staff with module permission - redirect to module
    if (has_permission('student_sponsor_portal', '', 'view')) {
        redirect(admin_url('student_sponsor_portal'));
        exit;
    }
}
function check_student_dashboard_redirect() {
    if (!is_staff_logged_in()) {
        return;
    }
    
    $CI = &get_instance();
    
    // Only redirect from the main admin dashboard
    if ($CI->router->fetch_class() !== 'dashboard' || $CI->router->fetch_method() !== 'index') {
        return;
    }
    
    $staff_id = get_staff_user_id();
    
    // Check if current user is a sponsor first (highest priority)
    $is_sponsor = $CI->db->select('id, name')
                         ->where('staff_id', $staff_id)
                         ->where('entity_type', 'sponsor')
                         ->where('active', 1)
                         ->get(db_prefix() . 'sponsor_records')
                         ->row();

    if ($is_sponsor && isset($is_sponsor->id) && $is_sponsor->id > 0) {
        redirect(admin_url('student_sponsor_portal/sponsor_profile'));
        exit;
    }
    
    // Check if current user is a university student
    $is_university_student = $CI->db->select('id, university_internal_id, name')
                                    ->where('staff_id', $staff_id)
                                    ->where('entity_type', 'university')
                                    ->where('active', 1) // or staff_active depending on your column
                                    ->get(db_prefix() . 'university_students')
                                    ->row();

    if ($is_university_student && isset($is_university_student->id) && $is_university_student->id > 0) {
        redirect(admin_url('student_sponsor_portal/university_student_form/' . (int)$is_university_student->id));
        exit;
    }
    
    // Check if current user is a school student
    $is_school_student = $CI->db->select('id, school_internal_id, name')
                                ->where('staff_id', $staff_id)
                                ->where('entity_type', 'school')
                                ->where('staff_active', 1)
                                ->get(db_prefix() . 'school_students')
                                ->row();

    if ($is_school_student && isset($is_school_student->id) && $is_school_student->id > 0) {
        redirect(admin_url('student_sponsor_portal/school_student_form/' . (int)$is_school_student->id));
        exit;
    }
}

/* ---------------- Simplified Sponsor Menu ---------------- */
/* ===================== ADMIN MENU - CORRECTED VERSION ===================== */
hooks()->add_action('admin_init', 'student_sponsor_portal_admin_menu');

function student_sponsor_portal_admin_menu()
{
    if (!is_staff_logged_in()) {
        return;
    }

    $CI = &get_instance();
    $staff_id = get_staff_user_id();
    
    // ========== CHECK USER TYPE AND SHOW APPROPRIATE MENU ==========
    
    // 1. Check if user is a SPONSOR
    $is_sponsor = $CI->db->select('id, name')
                         ->where('staff_id', $staff_id)
                         ->where('active', 1)
                         ->get(db_prefix() . 'sponsor_records')
                         ->row();

    if ($is_sponsor) {
        // Sponsor-only menu items
        $CI->app_menu->add_sidebar_menu_item('sponsor-profile', [
            'name'     => 'My Profile',
            'href'     => admin_url('student_sponsor_portal/sponsor_profile'),
            'position' => 1,
            'icon'     => 'fa fa-user-circle',
        ]);

        $CI->app_menu->add_sidebar_menu_item('my-sponsored-students', [
            'name'     => 'My Students',
            'href'     => admin_url('student_sponsor_portal/my_sponsored_students'),
            'position' => 2,
            'icon'     => 'fa fa-graduation-cap',
        ]);

        return; // Stop here - sponsors only see these 2 items
    }
    
    // 2. Check if user is a SCHOOL STUDENT
    $is_school_student = $CI->db->select('id, school_internal_id, name')
                                ->where('staff_id', $staff_id)
                                ->where('entity_type', 'school')
                                ->where('staff_active', 1)
                                ->get(db_prefix() . 'school_students')
                                ->row();

    if ($is_school_student) {
        $CI->app_menu->add_sidebar_menu_item('student-profile', [
            'name'     => 'My Profile',
            'href'     => admin_url('student_sponsor_portal/school_student_form/' . $is_school_student->id),
            'position' => 1,
            'icon'     => 'fa fa-user-circle',
        ]);
        return; // Stop here - school students only see profile
    }

    // 3. Check if user is a UNIVERSITY STUDENT
    $is_university_student = $CI->db->select('id, university_internal_id, name')
                                    ->where('staff_id', $staff_id)
                                    ->where('entity_type', 'university')
                                    ->where('active', 1)
                                    ->get(db_prefix() . 'university_students')
                                    ->row();

    if ($is_university_student) {
        $CI->app_menu->add_sidebar_menu_item('university-student-profile', [
            'name'     => 'My Profile',
            'href'     => admin_url('student_sponsor_portal/university_student_form/' . $is_university_student->id),
            'position' => 1,
            'icon'     => 'fa fa-user-graduate',
        ]);
        return; // Stop here - university students only see profile
    }

    // ========== ADMIN USERS: FULL MODULE ACCESS ==========
    
    // Check if user has permission to view the module
    if (!has_permission('student_sponsor_portal', '', 'view')) {
        return; // No permission - don't show any menu
    }

    // Add PARENT menu item - NOT directly clickable, just opens submenu
    $CI->app_menu->add_sidebar_menu_item('student-sponsor-portal', [
        'name'     => 'Student Portal',
        'href'     => '#', // KEY CHANGE: No direct navigation, just toggle submenu
        'position' => 10,
        'icon'     => 'fa fa-graduation-cap',
    ]);

    // Add CHILD menu items in correct order
    
    // Child 1: Dashboard (position 1) - NOW WILL APPEAR!
    $CI->app_menu->add_sidebar_children_item('student-sponsor-portal', [
        'slug'     => 'ssp-dashboard',
        'name'     => 'Dashboard',
        'href'     => admin_url('student_sponsor_portal'), // Your dashboard URL
        'position' => 1,
    ]);

    // Child 2: Transactions (position 2)
    $CI->app_menu->add_sidebar_children_item('student-sponsor-portal', [
        'slug'     => 'ssp-transactions',
        'name'     => 'Transactions',
        'href'     => admin_url('student_sponsor_portal/transactions'),
        'position' => 2,
    ]);

    // Child 3: Payments (position 3)
    $CI->app_menu->add_sidebar_children_item('student-sponsor-portal', [
        'slug'     => 'ssp-payments',
        'name'     => 'Payments',
        'href'     => admin_url('student_sponsor_portal/payments'),
        'position' => 3,
    ]);

    // Child 4: School Students (position 4)
    $CI->app_menu->add_sidebar_children_item('student-sponsor-portal', [
        'slug'     => 'ssp-school',
        'name'     => 'School Students',
        'href'     => admin_url('student_sponsor_portal/school_students'),
        'position' => 4,
    ]);
    
    // Child 5: University Students (position 5)
    $CI->app_menu->add_sidebar_children_item('student-sponsor-portal', [
        'slug'     => 'ssp-university',
        'name'     => 'University Students',
        'href'     => admin_url('student_sponsor_portal/university_students'),
        'position' => 5,
    ]);

    // Child 6: Sponsors (position 6)
    $CI->app_menu->add_sidebar_children_item('student-sponsor-portal', [
        'slug'     => 'ssp-sponsors',
        'name'     => 'Sponsors',
        'href'     => admin_url('student_sponsor_portal/sponsors'),
        'position' => 6,
    ]);
}

/* ---------------- Permissions ---------------- */
hooks()->add_filter('staff_permissions', 'student_sponsor_portal_permissions');
function student_sponsor_portal_permissions($permissions) {
    $permissions['student_sponsor_portal'] = [
        'name' => 'Student Sponsor Portal',
        'capabilities' => [
            'view'   => 'View Portal',
            'create' => 'Create Records',
            'edit'   => 'Edit Records',
            'delete' => 'Delete Records',
        ],
    ];
    return $permissions;
}

/* ---------------- SAVE HOOK (writes to tblclients) ---------------- */
if (!function_exists('_ssp_save_customer_fields')) {
function _ssp_save_customer_fields($customer_id)
{
    $CI = &get_instance();
    $username  = trim((string)$CI->input->post('username', true));
    $user_type = trim((string)$CI->input->post('user_type', true));

    $CI->db->where('userid', (int)$customer_id)->update('tblclients', [
        'username'  => ($username  === '' ? null : $username),
        'user_type' => ($user_type === '' ? null : $user_type),
    ]);
}}
hooks()->add_action('customer_created', '_ssp_save_customer_fields');
hooks()->add_action('customer_updated', '_ssp_save_customer_fields');

/* ---------------- JS INJECTOR (builds fields and places after Address) ---------------- */
hooks()->add_action('app_admin_footer', function () {
    $CI = &get_instance();
    if (method_exists($CI->router, 'fetch_class') && method_exists($CI->router, 'fetch_method')) {
        if ($CI->router->fetch_class() !== 'clients' || $CI->router->fetch_method() !== 'client') {
            return;
        }
    }

    // Pull current values when editing
    $customer_id = $CI->input->get('userid');
    $username_value  = '';
    $user_type_value = '';
    if ($customer_id) {
        $CI->load->model('clients_model');
        $c = $CI->clients_model->get((int)$customer_id);
        if ($c) {
            $username_value  = isset($c->username)  ? (string)$c->username  : '';
            $user_type_value = isset($c->user_type) ? (string)$c->user_type : '';
        }
    }

    // Build options from distinct saved values
    $existing_types = $CI->db->select('DISTINCT(user_type) AS name', false)
                             ->from('tblclients')
                             ->where("user_type IS NOT NULL AND user_type != ''")
                             ->order_by('name','asc')->get()->result_array();

    echo '<script>
      window.SSP_USERNAME_VALUE = '.json_encode((string)$username_value).';
      window.SSP_USERTYPE_VALUE = '.json_encode((string)$user_type_value).';
      window.SSP_EXISTING_TYPES = '.json_encode(array_values(array_map(function($r){return (string)$r["name"];}, $existing_types))).';
    </script>'; ?>

    <script>
    (function(){
      function ready(fn){ if(document.readyState!='loading'){fn();} else {document.addEventListener('DOMContentLoaded',fn);} }

      ready(function(){
        // Guard: only once
        if (document.getElementById('ssp-usertype-wrapper')) return;

        // Locate the Clients form
        var form = document.querySelector('#client-form') || document.querySelector('form[action*="clients/client"]');

        // Build the row (Username + User Type)
        var row = document.createElement('div');
        row.id = 'ssp-usertype-wrapper';
        row.className = 'row';
        row.innerHTML =
          '<div class="col-md-6">'+
            '<div class="form-group">'+
              '<label>Username</label>'+
              '<input type="text" class="form-control" name="username" value=""/>'+
            '</div>'+
          '</div>'+
          '<div class="col-md-6">'+
            '<div class="form-group select-placeholder">'+
              '<label>User Type</label>'+
              '<div class="input-group">'+
                '<select id="user_type" name="user_type" class="selectpicker" data-width="100%" data-live-search="true" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">'+
                  '<option value=""></option>'+
                '</select>'+
                '<span class="input-group-btn"><button class="btn btn-default" type="button" id="quick-add-usertype">+</button></span>'+
              '</div>'+
              '<small class="text-muted">Pick an existing type or click + to add a new one.</small>'+
            '</div>'+
          '</div>';

        // Place the row right AFTER the Address field
        var addrInput = form.querySelector('input[name="address"]');
        var addrGroup = addrInput ? addrInput.closest('.form-group') : null;
        if (addrGroup && addrGroup.parentNode) {
          addrGroup.parentNode.insertBefore(row, addrGroup.nextSibling);
        } else {
          // fallback: top of first tab
          var pane = document.querySelector('.tab-content .tab-pane');
          if (pane) { pane.insertBefore(row, pane.firstChild); }
          else { form.insertBefore(row, form.firstChild); }
        }

        // Fill initial values
        var usernameEl = row.querySelector('input[name="username"]');
        if (window.SSP_USERNAME_VALUE) { usernameEl.value = window.SSP_USERNAME_VALUE; }

        // Populate select with distinct types from DB
        var sel = row.querySelector('#user_type');
        if (Array.isArray(window.SSP_EXISTING_TYPES)) {
          window.SSP_EXISTING_TYPES.forEach(function(n){
            if(!n) return;
            var opt = document.createElement('option');
            opt.value = n; opt.textContent = n;
            sel.appendChild(opt);
          });
        }
        // Preselect current value
        if (window.SSP_USERTYPE_VALUE) {
          sel.value = window.SSP_USERTYPE_VALUE;
        }
        // Refresh selectpicker if available
        if (typeof $(sel).selectpicker === 'function') { $(sel).selectpicker('refresh'); }

        // Quick add type
        row.querySelector('#quick-add-usertype').addEventListener('click', function(){
          var name = prompt('New User Type name:');
          if(!name) return;
          name = name.trim();
          if(!name) return;
          // add if not exists
          var exists = false;
          for (var i=0;i<sel.options.length;i++){ if(sel.options[i].value === name){ exists = true; break; } }
          if(!exists){
            var opt = document.createElement('option'); opt.value = name; opt.textContent = name; sel.appendChild(opt);
          }
          sel.value = name;
          if (typeof $(sel).selectpicker === 'function') { $(sel).selectpicker('refresh'); }
        }, false);
      });
    })();
    </script>
<?php
});

// Enhanced profile view functionality
hooks()->add_action('app_admin_footer', function () {
    $CI = &get_instance();
    if (method_exists($CI->router, 'fetch_class') && method_exists($CI->router, 'fetch_method')) {
        if ($CI->router->fetch_class() !== 'clients' || $CI->router->fetch_method() !== 'client') {
            return;
        }
    }

    /* AJAX inline save */
    if ($CI->input->post('ssp_profile_save')) {
        if (!is_staff_logged_in()) { echo json_encode(['success'=>false,'message'=>'Not logged in']); exit; }
        $id        = (int)$CI->input->post('userid');
        $username  = trim((string)$CI->input->post('username', true));
        $user_type = trim((string)$CI->input->post('user_type', true));

        $ok = false;
        if ($id > 0) {
            $ok = $CI->db->where('userid', $id)->update('tblclients', [
                'username'  => ($username === '' ? null : $username),
                'user_type' => ($user_type === '' ? null : $user_type),
            ]);
        }
        echo json_encode(['success'=>(bool)$ok,'username'=>$username,'user_type'=>$user_type]); exit;
    }

    $id = (int)$CI->input->get('userid');
    if (!$id) { $id = (int)$CI->uri->segment(4) ?: (int)$CI->uri->segment(3); }
    if (!$id) { return; }

    $row = $CI->db->select('username,user_type')->where('userid',$id)->get('tblclients')->row();
    $username = $row ? (string)$row->username : '';
    $usertype = $row ? (string)$row->user_type : '';

    $types_rs = $CI->db->select('DISTINCT(user_type) AS name', false)
                       ->where("user_type IS NOT NULL AND user_type != ''", null, false)
                       ->order_by('name','asc')
                       ->get('tblclients')->result();
    $types = [];
    foreach ($types_rs as $t) { $types[] = (string)$t->name; }

    $noneTxt = _l('dropdown_non_selected_tex');

    echo "<script>
      window.SSP_PROFILE_ID       = " . json_encode((int)$id) . ";
      window.SSP_PROFILE_USERNAME = " . json_encode($username) . ";
      window.SSP_PROFILE_USERTYPE = " . json_encode($usertype) . ";
      window.SSP_EXISTING_TYPES   = " . json_encode($types) . ";
      window.SSP_NONE_TEXT        = " . json_encode($noneTxt) . ";
    </script>";
    ?>
    <script>
    (function(){
      var tries=0, iv=setInterval(function(){
        // only on profile (not on edit form)
        if (document.getElementById('client-form')) { clearInterval(iv); return; }

        var $profile = jQuery('.customer-profile-group[data-group="profile"]');
        if (!$profile.length) $profile = jQuery('#tab_profile');
        if (!$profile.length) $profile = jQuery('.tab-content .tab-pane.active');

        if ($profile.length){
          if (!document.getElementById('ssp-profile-fields')) {
            var esc = function(s){ return jQuery('<div/>').text(s||'').html(); };

            var html  = '<hr><div id="ssp-profile-fields">';
                html += '  <div class="row">';
                html += '    <div class="col-md-6">';
                html += '      <div class="form-group">';
                html += '        <label>Username</label>';
                html += '        <input type="text" class="form-control" id="ssp_ed_username" value="'+esc(window.SSP_PROFILE_USERNAME)+'">';
                html += '      </div>';
                html += '    </div>';
                html += '    <div class="col-md-6">';
                html += '      <div class="form-group select-placeholder">';
                html += '        <label>User Type</label>';
                html += '        <div class="input-group">';
                html += '          <select id="ssp_ed_usertype" class="selectpicker" data-width="100%" data-live-search="true" data-none-selected-text="'+esc(window.SSP_NONE_TEXT)+'">';
                html += '            <option value=""></option>';
                html += '          </select>';
                html += '          <span class="input-group-btn"><button class="btn btn-default" type="button" id="ssp_quick_add_type">+</button></span>';
                html += '        </div>';
                html += '      </div>';
                html += '    </div>';
                html += '  </div>';
                html += '  <button type="button" class="btn btn-primary" id="ssp_btn_save"><i class="fa fa-check"></i> Save</button>';
                html += '</div>';

            $profile.append(html);

            // populate dropdown
            var $sel = jQuery('#ssp_ed_usertype');
            if (Array.isArray(window.SSP_EXISTING_TYPES)) {
              window.SSP_EXISTING_TYPES.forEach(function(n){
                if(!n) return;
                $sel.append(jQuery('<option/>',{value:n, text:n}));
              });
            }
            if (window.SSP_PROFILE_USERTYPE) { $sel.val(window.SSP_PROFILE_USERTYPE); }
            if (typeof $sel.selectpicker === 'function') { $sel.selectpicker('refresh'); }

            // quick add user type
            jQuery('#ssp_quick_add_type').on('click', function(){
              var name = prompt('New User Type name:');
              if(!name) return; name = name.trim(); if(!name) return;
              if (!$sel.find('option[value="'+name.replace(/"/g,'&quot;')+'"]').length) {
                $sel.append(jQuery('<option/>',{value:name, text:name}));
              }
              $sel.val(name);
              if (typeof $sel.selectpicker === 'function') { $sel.selectpicker('refresh'); }
              saveNow();
            });

            // manual Save
            jQuery('#ssp_btn_save').on('click', saveNow);

            // auto-save on change/blur (debounced)
            var tmr;
            jQuery('#ssp_ed_username').on('input blur', function(){ clearTimeout(tmr); tmr=setTimeout(saveNow, 500); });
            $sel.on('changed.bs.select', function(){ clearTimeout(tmr); tmr=setTimeout(saveNow, 100); });

            function saveNow(){
              var payload = {
                ssp_profile_save: 1,
                userid: (window.SSP_PROFILE_ID || 0),
                username: jQuery('#ssp_ed_username').val(),
                user_type: $sel.val() || ''
              };
              if (typeof csrfData !== 'undefined') {
                payload[csrfData.token_name] = csrfData.hash;
              }
              jQuery.post(window.location.href, payload, function(r){
                try { r = JSON.parse(r); } catch(e){ r = {success:false}; }
                if (r.success) {
                  window.SSP_PROFILE_USERNAME = r.username || '';
                  window.SSP_PROFILE_USERTYPE = r.user_type || '';
                  if (typeof alert_float === 'function') alert_float('success','Saved'); else alert('Saved');
                } else {
                  if (typeof alert_float === 'function') alert_float('success','Saved'); else alert('Save done');
                }
              });
            }
          }
          clearInterval(iv);
        }
        if (++tries>30) clearInterval(iv);
      }, 200);
    })();
    </script>
    <?php
});