<?php

defined('BASEPATH') or exit('No direct script access allowed');

function student_sponsor_portal_module_install()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install/sql_install.php');
}
$CI = &get_instance();
$cf = $CI->db->where('fieldto','customers')->where('slug','username')->get('tblcustomfields')->row();
if (!$cf) {
    $CI->db->insert('tblcustomfields', [
        'fieldto'=>'customers','name'=>'Username','slug'=>'username',
        'type'=>'input','active'=>1,'show_on_table'=>1,'required'=>0,
    ]);
}