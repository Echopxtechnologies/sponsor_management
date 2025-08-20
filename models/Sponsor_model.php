<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Sponsor_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function add($data)
    {
        $insert_data = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'sponsor_type' => $data['sponsor_type'],
        ];
        
        $this->db->insert(db_prefix() . 'sponsor_records', $insert_data);
        $sponsor_id = $this->db->insert_id();

        // Also create a client record
        $this->load->model('clients_model');
        $this->clients_model->add([
            'firstname' => $data['name'],
            'email' => $data['email'],
            'phonenumber' => $data['phone'],
            'custom_fields' => [
                'contact_type' => 'sponsor'
            ]
        ]);

        return $sponsor_id;
    }

    public function get_all()
    {
        return $this->db->get(db_prefix() . 'sponsor_records')->result();
    }

    public function count_all()
    {
        return $this->db->count_all_results(db_prefix() . 'sponsor_records');
    }

    public function get_by_id($id)
    {
        return $this->db->get_where(db_prefix() . 'sponsor_records', ['id' => $id])->row();
    }
}