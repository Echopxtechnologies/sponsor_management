<?php
/*
Module Name: Student Sponsor Portal
Description: A module for registering school students, university students, and sponsors
Version: 1.0.0
Author: Raju
Requires at least: 2.3.2
*/

defined('BASEPATH') or exit('No direct script access allowed');

// Register activation hook
register_activation_hook('student_sponsor_portal', 'student_sponsor_portal_activation_hook');

// Register deactivation hook  
register_deactivation_hook('student_sponsor_portal', 'student_sponsor_portal_deactivation_hook');
require_once(__DIR__ . '/install.php'); // ✅ Load the function
// Module activation function
function student_sponsor_portal_activation_hook() {
    student_sponsor_portal_module_install(); // ✅ call function
}


// Module deactivation function
function student_sponsor_portal_deactivation_hook() {
    log_activity('Student Sponsor Portal Module Deactivated');
}

// Add admin menu items
hooks()->add_action('admin_init', 'student_sponsor_portal_admin_menu');

function student_sponsor_portal_admin_menu() {
    $CI = &get_instance();
    
    if (has_permission('student_sponsor_portal', '', 'view')) {
        // Main menu item
        $CI->app_menu->add_sidebar_menu_item('student-sponsor-portal', [
            'name'     => 'Student Portal',
            'href'     => admin_url('student_sponsor_portal'),
            'position' => 36,
            'icon'     => 'fa fa-graduation-cap',
        ]);
        
        // Sub menu items
        $CI->app_menu->add_sidebar_children_item('student-sponsor-portal', [
            'slug'     => 'school-registration',
            'name'     => 'School Students',
            'href'     => admin_url('student_sponsor_portal/school_form'),
            'position' => 1,
        ]);
        
        $CI->app_menu->add_sidebar_children_item('student-sponsor-portal', [
            'slug'     => 'university-registration',
            'name'     => 'University Students',
            'href'     => admin_url('student_sponsor_portal/university_form'),
            'position' => 2,
        ]);
        
        $CI->app_menu->add_sidebar_children_item('student-sponsor-portal', [
            'slug'     => 'sponsor-registration',
            'name'     => 'Sponsors',
            'href'     => admin_url('student_sponsor_portal/sponsor_form'),
            'position' => 3,
        ]);
        
        $CI->app_menu->add_sidebar_children_item('student-sponsor-portal', [
            'slug'     => 'portal-dashboard',
            'name'     => 'Dashboard',
            'href'     => admin_url('student_sponsor_portal'),
            'position' => 4,
        ]);
    }
}

// Add permissions
hooks()->add_filter('staff_permissions', 'student_sponsor_portal_permissions');

function student_sponsor_portal_permissions($permissions) {
    $permissions['student_sponsor_portal'] = [
        'name' => 'Student Sponsor Portal',
        'capabilities' => [
            'view' => 'View Portal',
            'create' => 'Create Records',
            'edit' => 'Edit Records',
            'delete' => 'Delete Records'
        ]
    ];
    
    return $permissions;
}



?>