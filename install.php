<?php

defined('BASEPATH') or exit('No direct script access allowed');

function student_sponsor_portal_module_install()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install/sql_install.php');
}