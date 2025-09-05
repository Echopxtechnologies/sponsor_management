<?php defined('BASEPATH') or exit('No direct script access allowed');

// Main portal access - redirects school students to their profile
$route['admin/student_sponsor_portal'] = 'student_sponsor_portal/index';

/* =================================================================================== */
/* =====================         SCHOOL STUDENTS ROUTES            =================== */
/* =================================================================================== */

// School student form - accessible by both admin and school students (with restrictions)
$route['admin/student_sponsor_portal/school_students'] = 'student_sponsor_portal/school_students';
$route['admin/student_sponsor_portal/school_student_form'] = 'student_sponsor_portal/school_student_form';
$route['admin/student_sponsor_portal/school_student_form/(:num)'] = 'student_sponsor_portal/school_student_form/$1';

// School student AJAX endpoints - accessible by school students for their own records
$route['admin/student_sponsor_portal/get_school_student'] = 'student_sponsor_portal/get_school_student';
$route['admin/student_sponsor_portal/display_school_photo/(:num)'] = 'student_sponsor_portal/display_school_photo/$1';

// Report card management - accessible by school students for their own records
$route['admin/student_sponsor_portal/upload_school_report_card'] = 'student_sponsor_portal/upload_school_report_card';
$route['admin/student_sponsor_portal/get_school_report_cards/(:num)'] = 'student_sponsor_portal/get_school_report_cards/$1';
$route['admin/student_sponsor_portal/download_school_report_card/(:num)'] = 'student_sponsor_portal/download_school_report_card/$1';

// ADMIN-ONLY School Routes
$route['admin/student_sponsor_portal/delete_school_student'] = 'student_sponsor_portal/delete_school_student';
$route['admin/student_sponsor_portal/export_school_students'] = 'student_sponsor_portal/export_school_students';
$route['admin/student_sponsor_portal/add_school_name'] = 'student_sponsor_portal/add_school_name';
$route['admin/student_sponsor_portal/add_bank'] = 'student_sponsor_portal/add_bank';
$route['admin/student_sponsor_portal/delete_school_report_card/(:num)'] = 'student_sponsor_portal/delete_school_report_card/$1';
$route['admin/student_sponsor_portal/search_school_students'] = 'student_sponsor_portal/search_school_students';
$route['admin/student_sponsor_portal/grant_school_access/(:num)'] = 'student_sponsor_portal/grant_school_access/$1';
$route['admin/student_sponsor_portal/revoke_school_access/(:num)'] = 'student_sponsor_portal/revoke_school_access/$1';

/* =================================================================================== */
/* =====================       UNIVERSITY STUDENTS ROUTES (ADMIN ONLY) ============== */
/* =================================================================================== */

$route['admin/student_sponsor_portal/university_students'] = 'student_sponsor_portal/university_students';
$route['admin/student_sponsor_portal/university_form'] = 'student_sponsor_portal/university_form';
$route['admin/student_sponsor_portal/university_student_form'] = 'student_sponsor_portal/university_student_form';
$route['admin/student_sponsor_portal/university_student_form/(:num)'] = 'student_sponsor_portal/university_student_form/$1';
$route['admin/student_sponsor_portal/grant_university_access/(:num)'] = 'student_sponsor_portal/grant_university_access/$1';
$route['admin/student_sponsor_portal/revoke_university_access/(:num)'] = 'student_sponsor_portal/revoke_university_access/$1';
$route['admin/student_sponsor_portal/search_university_students'] = 'student_sponsor_portal/search_university_students';
$route['admin/student_sponsor_portal/upload_report_card'] = 'student_sponsor_portal/upload_report_card';
$route['admin/student_sponsor_portal/get_report_cards'] = 'student_sponsor_portal/get_report_cards';
$route['admin/student_sponsor_portal/download_report_card/(:num)'] = 'student_sponsor_portal/download_report_card/$1';
$route['admin/student_sponsor_portal/delete_report_card'] = 'student_sponsor_portal/delete_report_card';
$route['admin/student_sponsor_portal/display_profile_photo/(:num)'] = 'student_sponsor_portal/display_profile_photo/$1';
$route['admin/student_sponsor_portal/add_university_name'] = 'student_sponsor_portal/add_university_name';
$route['admin/student_sponsor_portal/add_university_program'] = 'student_sponsor_portal/add_university_program';
$route['admin/student_sponsor_portal/add_country_ajax'] = 'student_sponsor_portal/add_country_ajax';
$route['admin/student_sponsor_portal/add_university_ajax'] = 'student_sponsor_portal/add_university_ajax';
$route['admin/student_sponsor_portal/add_program_ajax'] = 'student_sponsor_portal/add_program_ajax';
$route['admin/student_sponsor_portal/add_bank_ajax'] = 'student_sponsor_portal/add_bank_ajax';

/* =================================================================================== */
/* =====================    UNIVERSITY STUDENT PORTAL ROUTES        ================== */
/* =================================================================================== */

// University student form - accessible by both admin and university students (with restrictions)
$route['admin/student_sponsor_portal/university_student_form'] = 'student_sponsor_portal/university_student_form';
$route['admin/student_sponsor_portal/university_student_form/(:num)'] = 'student_sponsor_portal/university_student_form/$1';

// University student AJAX endpoints - accessible by university students for their own records
$route['admin/student_sponsor_portal/get_university_student'] = 'student_sponsor_portal/get_university_student';
$route['admin/student_sponsor_portal/display_profile_photo/(:num)'] = 'student_sponsor_portal/display_profile_photo/$1';

// Report card management - accessible by university students for their own records
$route['admin/student_sponsor_portal/upload_university_report_card'] = 'student_sponsor_portal/upload_university_report_card';
$route['admin/student_sponsor_portal/get_university_report_cards/(:num)'] = 'student_sponsor_portal/get_university_report_cards/$1';
$route['admin/student_sponsor_portal/download_university_report_card/(:num)'] = 'student_sponsor_portal/download_university_report_card/$1';

// University specific AJAX endpoints for form helpers
$route['admin/student_sponsor_portal/add_university_ajax'] = 'student_sponsor_portal/add_university_ajax';
$route['admin/student_sponsor_portal/add_program_ajax'] = 'student_sponsor_portal/add_program_ajax';
$route['admin/student_sponsor_portal/add_country_ajax'] = 'student_sponsor_portal/add_country_ajax';
$route['admin/student_sponsor_portal/add_bank_ajax'] = 'student_sponsor_portal/add_bank_ajax';

/* =================================================================================== */
/* =====================           SPONSORS ROUTES (ADMIN ONLY)     =================== */
/* =================================================================================== */

$route['admin/student_sponsor_portal/sponsors'] = 'student_sponsor_portal/sponsors';
$route['admin/student_sponsor_portal/sponsor_form'] = 'student_sponsor_portal/sponsor_form/$1';
$route['admin/student_sponsor_portal/sponsor_form/(:num)'] = 'student_sponsor_portal/sponsor_form/$1';
$route['admin/student_sponsor_portal/get_sponsor'] = 'student_sponsor_portal/get_sponsor';
$route['admin/student_sponsor_portal/delete_sponsor'] = 'student_sponsor_portal/delete_sponsor';
$route['admin/student_sponsor_portal/grant_sponsor_access/(:num)'] = 'student_sponsor_portal/grant_sponsor_access/$1';
$route['admin/student_sponsor_portal/revoke_sponsor_access/(:num)'] = 'student_sponsor_portal/revoke_sponsor_access/$1';
$route['admin/student_sponsor_portal/search_sponsors'] = 'student_sponsor_portal/search_sponsors';
$route['admin/student_sponsor_portal/get_available_students'] = 'student_sponsor_portal/get_available_students';
$route['admin/student_sponsor_portal/get_sponsored_students'] = 'student_sponsor_portal/get_sponsored_students';
$route['admin/student_sponsor_portal/search_students_for_sponsor'] = 'student_sponsor_portal/search_students_for_sponsor';
$route['admin/student_sponsor_portal/get_student_details'] = 'student_sponsor_portal/get_student_details';
$route['admin/student_sponsor_portal/ajax_bank_create'] = 'student_sponsor_portal/ajax_bank_create';
$route['admin/student_sponsor_portal/ajax_states/(:num)'] = 'student_sponsor_portal/ajax_states/$1';

/* =================================================================================== */
/* =====================     TRANSACTIONS & PAYMENTS (ADMIN ONLY)   =================== */
/* =================================================================================== */

$route['admin/student_sponsor_portal/payments'] = 'student_sponsor_portal/payments';
$route['admin/student_sponsor_portal/transactions'] = 'student_sponsor_portal/transactions';
$route['admin/student_sponsor_portal/transaction'] = 'student_sponsor_portal/transaction/$1';
$route['admin/student_sponsor_portal/transaction/(:num)'] = 'student_sponsor_portal/transaction/$1';
$route['admin/student_sponsor_portal/transaction_save'] = 'student_sponsor_portal/transaction_save';
$route['admin/student_sponsor_portal/transaction_delete/(:num)'] = 'student_sponsor_portal/transaction_delete/$1';
$route['admin/student_sponsor_portal/add_payment/(:num)'] = 'student_sponsor_portal/add_payment/$1';
$route['admin/student_sponsor_portal/edit_payment/(:num)/(:num)'] = 'student_sponsor_portal/edit_payment/$1/$2';
$route['admin/student_sponsor_portal/delete_payment/(:num)/(:num)'] = 'student_sponsor_portal/delete_payment/$1/$2';
$route['admin/student_sponsor_portal/email_preview/(:num)'] = 'student_sponsor_portal/email_preview/$1';
$route['admin/student_sponsor_portal/send_test_email/(:num)'] = 'student_sponsor_portal/send_test_email/$1';

/* =================================================================================== */
/* =====================              UTILITY ROUTES               =================== */
/* =================================================================================== */

$route['admin/student_sponsor_portal/get_stats'] = 'student_sponsor_portal/get_stats';