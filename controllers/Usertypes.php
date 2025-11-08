<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Usertypes extends AdminController
{
    public function quick_add()
    {
        if (!is_staff_logged_in()) show_404();

        $name = trim($this->input->post('name'));
        if ($name === '') { echo json_encode(['success'=>false,'message'=>'Name required']); die; }

        $full = USER_TYPE_PREFIX . ' ' . $name;

        // avoid duplicates
        $dup = $this->db->where('name',$full)->get('tblcustomers_groups')->row();
        if ($dup) { echo json_encode(['success'=>true,'id'=>(int)$dup->id,'name'=>$full]); die; }

        $this->db->insert('tblcustomers_groups', ['name'=>$full]);
        echo json_encode(['success'=>true,'id'=>$this->db->insert_id(),'name'=>$full]); die;
    }
}
