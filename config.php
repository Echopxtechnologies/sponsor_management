<?php

defined('BASEPATH') or exit('No direct script access allowed');

$config['module_name']        = 'student_sponsor_portal';
$config['module_description'] = 'Student and Sponsor Registration Portal with Role-based Access';
$config['module_version']     = '1.1.0';
$config['requires_at_least']  = '2.3.0';
$config['author']             = 'Emmanuel';

// Module configuration for role-based access
$config['enable_school_student_access'] = true;
$config['school_student_restricted_fields'] = [
    'sponsorship_start_date',
    'sponsorship_end_date',
    'introduced_by',
    'introduced_phone',
    'internal_comment',
    'external_comment',
    'school_internal_id',
    'school_type'
];