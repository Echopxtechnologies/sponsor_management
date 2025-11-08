<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Usertype_model extends App_Model
{
    // all=true -> return ALL groups; all=false -> only "Type:" groups
    public function get_type_groups($all = false)
    {
        $this->db->select('id,name')->from('tblcustomers_groups');
        if (!$all) { $this->db->like('name', USER_TYPE_PREFIX, 'after'); }
        return $this->db->order_by('name','asc')->get()->result_array();
    }

    public function get_customer_type_group_ids($customer_id, $all = false)
    {
        $this->db->select('cgi.groupid')
                 ->from('tblcustomer_groups_in cgi')
                 ->join('tblcustomers_groups cg','cg.id=cgi.groupid','inner')
                 ->where('cgi.customer_id',(int)$customer_id);
        if (!$all) { $this->db->like('cg.name', USER_TYPE_PREFIX, 'after'); }
        $rows = $this->db->get()->result_array();
        return array_map(fn($r)=>(int)$r['groupid'], $rows);
    }

    public function sync_customer_type_groups($customer_id, $selected_ids = [], $all = false)
    {
        $selected_ids = array_values(array_unique(array_map('intval',(array)$selected_ids)));
        $universe_ids = array_map(fn($g)=>(int)$g['id'], $this->get_type_groups($all));
        if (!$universe_ids) return;

        $this->db->where('customer_id',(int)$customer_id)->where_in('groupid',$universe_ids);
        if ($selected_ids) $this->db->where_not_in('groupid',$selected_ids);
        $this->db->delete('tblcustomer_groups_in');

        foreach ($selected_ids as $gid) {
            if (!in_array($gid, $universe_ids, true)) continue;
            $exists = $this->db->where(['customer_id'=>$customer_id,'groupid'=>$gid])
                               ->get('tblcustomer_groups_in')->row();
            if (!$exists) {
                $this->db->insert('tblcustomer_groups_in',[
                    'customer_id'=>(int)$customer_id, 'groupid'=>(int)$gid
                ]);
            }
        }
    }

    public function ensure_username_cf_id()
    {
        $cf = $this->db->where('fieldto','customers')->where('slug','username')
                       ->get('tblcustomfields')->row();
        if ($cf) return (int)$cf->id;

        $this->db->insert('tblcustomfields', [
            'fieldto'=>'customers','name'=>'Username','slug'=>'username',
            'type'=>'input','active'=>1,'show_on_table'=>1,'required'=>0,
        ]);
        return (int)$this->db->insert_id();
    }
}
