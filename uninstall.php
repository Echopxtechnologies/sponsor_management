<?php

defined('BASEPATH') or exit('No direct script access allowed');

function student_sponsor_portal_module_uninstall()
{
    $CI = &get_instance();
    
    // Uncomment to drop tables on uninstall
    // $CI->db->query("DROP TABLE IF EXISTS " . db_prefix() . "school_students");
    // $CI->db->query("DROP TABLE IF EXISTS " . db_prefix() . "university_students");
    // $CI->db->query("DROP TABLE IF EXISTS " . db_prefix() . "sponsor_records");
}