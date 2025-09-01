<?php defined('BASEPATH') or exit('No direct script access allowed');

// University + sponsors (existing controller)
$route['admin/student_sponsor_portal']                             = 'student_sponsor_portal/index';
$route['admin/student_sponsor_portal/university_students']         = 'student_sponsor_portal/university_students';
$route['admin/student_sponsor_portal/university_form']             = 'student_sponsor_portal/university_form';
$route['admin/student_sponsor_portal/university_student_form']     = 'student_sponsor_portal/university_student_form/$1';
$route['admin/student_sponsor_portal/grant_university_access']     = 'student_sponsor_portal/grant_university_access/$1';
$route['admin/student_sponsor_portal/revoke_university_access']    = 'student_sponsor_portal/revoke_university_access/$1';
$route['admin/student_sponsor_portal/sponsors']                    = 'student_sponsor_portal/sponsors';
$route['admin/student_sponsor_portal/sponsor_form']                = 'student_sponsor_portal/sponsor_form/$1';
$route['admin/student_sponsor_portal/payments']                    = 'student_sponsor_portal/payments';
$route['admin/student_sponsor_portal/transactions']                = 'student_sponsor_portal/transactions';
$route['admin/student_sponsor_portal/transaction']                 = 'student_sponsor_portal/transaction/$1';
$route['admin/student_sponsor_portal/transaction_save']            = 'student_sponsor_portal/transaction_save';
$route['admin/student_sponsor_portal/transaction_delete']          = 'student_sponsor_portal/transaction_delete/$1';
$route['admin/student_sponsor_portal/add_payment/(:num)']          = 'student_sponsor_portal/add_payment/$1';
$route['admin/student_sponsor_portal/edit_payment/(:num)/(:num)']  = 'student_sponsor_portal/edit_payment/$1/$2';
$route['admin/student_sponsor_portal/delete_payment/(:num)/(:num)']= 'student_sponsor_portal/delete_payment/$1/$2';

// SCHOOL STUDENTS - All using main controller now
$route['admin/student_sponsor_portal/school_students']             = 'student_sponsor_portal/school_students';
$route['admin/student_sponsor_portal/school_student_form']         = 'student_sponsor_portal/school_student_form';
$route['admin/student_sponsor_portal/school_student_form/(:num)']  = 'student_sponsor_portal/school_student_form/$1';
$route['admin/student_sponsor_portal/delete_school_student']       = 'student_sponsor_portal/delete_school_student';
$route['admin/student_sponsor_portal/get_school_student']          = 'student_sponsor_portal/get_school_student';
$route['admin/student_sponsor_portal/export_school_students']      = 'student_sponsor_portal/export_school_students';
$route['admin/student_sponsor_portal/add_school_name']             = 'student_sponsor_portal/add_school_name';
$route['admin/student_sponsor_portal/upload_school_report_card']   = 'student_sponsor_portal/upload_school_report_card';
$route['admin/student_sponsor_portal/get_school_report_cards/(:num)'] = 'student_sponsor_portal/get_school_report_cards/$1';
$route['admin/student_sponsor_portal/download_school_report_card/(:num)'] = 'student_sponsor_portal/download_school_report_card/$1';
$route['admin/student_sponsor_portal/delete_school_report_card/(:num)'] = 'student_sponsor_portal/delete_school_report_card/$1';
$route['admin/student_sponsor_portal/display_school_photo/(:num)'] = 'student_sponsor_portal/display_school_photo/$1';
$route['admin/student_sponsor_portal/search_school_students']      = 'student_sponsor_portal/search_school_students';
$route['admin/student_sponsor_portal/grant_school_access/(:num)']  = 'student_sponsor_portal/grant_school_access/$1';
$route['admin/student_sponsor_portal/revoke_school_access/(:num)'] = 'student_sponsor_portal/revoke_school_access/$1';

// University routes
$route['admin/student_sponsor_portal/search_sponsors']             = 'student_sponsor_portal/search_sponsors';
$route['admin/student_sponsor_portal/search_university_students']  = 'student_sponsor_portal/search_university_students';
$route['admin/student_sponsor_portal/upload_report_card']          = 'student_sponsor_portal/upload_report_card';
$route['admin/student_sponsor_portal/get_report_cards']            = 'student_sponsor_portal/get_report_cards';
$route['admin/student_sponsor_portal/download_report_card/(:num)'] = 'student_sponsor_portal/download_report_card/$1';
$route['admin/student_sponsor_portal/delete_report_card']          = 'student_sponsor_portal/delete_report_card';
$route['admin/student_sponsor_portal/display_profile_photo/(:num)']= 'student_sponsor_portal/display_profile_photo/$1';