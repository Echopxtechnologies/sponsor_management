<?php
defined('BASEPATH') or exit('No direct script access allowed');

class School_model extends App_Model
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
            'grade' => $data['grade'],
            'dob' => $data['dob'],
            'school_id' => $data['school_id'],
        ];
        
        $this->db->insert(db_prefix() . 'school_students', $insert_data);
        $student_id = $this->db->insert_id();

        // Also create a client record
        $this->load->model('clients_model');
        $this->clients_model->add([
            'firstname' => $data['name'],
            'email' => $data['email'],
            'phonenumber' => $data['phone'],
            'custom_fields' => [
                'contact_type' => 'school_student'
            ]
        ]);

        return $student_id;
    }

    public function get_all()
    {
        return $this->db->get(db_prefix() . 'school_students')->result();
    }

    public function count_all()
    {
        return $this->db->count_all_results(db_prefix() . 'school_students');
    }

    public function get_by_id($id)
    {
        return $this->db->get_where(db_prefix() . 'school_students', ['id' => $id])->row();
    }
}