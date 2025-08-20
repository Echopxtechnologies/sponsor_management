<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Student_sponsor_portal extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('student_sponsor_portal/school_model');
        $this->load->model('student_sponsor_portal/university_model');
        $this->load->model('student_sponsor_portal/sponsor_model');
    }

    public function index()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        $data['title'] = 'Student Sponsor Portal Dashboard';
        $data['school_count'] = $this->school_model->count_all();
        $data['university_count'] = $this->university_model->count_all();
        $data['sponsor_count'] = $this->sponsor_model->count_all();
        
        $this->load->view('student_sponsor_portal/dashboard', $data);
    }

    public function school_form()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        if ($this->input->post()) {
            if (!has_permission('student_sponsor_portal', '', 'create')) {
                access_denied('student_sponsor_portal');
            }
            
            $id = $this->school_model->add($this->input->post());
            if ($id) {
                set_alert('success', 'School student registered successfully');
                redirect(admin_url('student_sponsor_portal/school_form'));
            }
        }
        
        $data['title'] = 'Register School Student';
        $this->load->view('student_sponsor_portal/school_form', $data);
    }

    public function university_form()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        if ($this->input->post()) {
            if (!has_permission('student_sponsor_portal', '', 'create')) {
                access_denied('student_sponsor_portal');
            }
            
            $id = $this->university_model->add($this->input->post());
            if ($id) {
                set_alert('success', 'University student registered successfully');
                redirect(admin_url('student_sponsor_portal/university_form'));
            }
        }
        
        $data['title'] = 'Register University Student';
        $this->load->view('student_sponsor_portal/university_form', $data);
    }

    public function sponsor_form()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        if ($this->input->post()) {
            if (!has_permission('student_sponsor_portal', '', 'create')) {
                access_denied('student_sponsor_portal');
            }
            
            $id = $this->sponsor_model->add($this->input->post());
            if ($id) {
                set_alert('success', 'Sponsor registered successfully');
                redirect(admin_url('student_sponsor_portal/sponsor_form'));
            }
        }
        
        $data['title'] = 'Register Sponsor';
        $this->load->view('student_sponsor_portal/sponsor_form', $data);
    }

    public function get_stats()
    {
        if (!has_permission('student_sponsor_portal', '', 'view')) {
            access_denied('student_sponsor_portal');
        }

        $stats = [
            'school_students' => $this->school_model->count_all(),
            'university_students' => $this->university_model->count_all(),
            'sponsors' => $this->sponsor_model->count_all(),
        ];

        echo json_encode($stats);
    }
}
?>