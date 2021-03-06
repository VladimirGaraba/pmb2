<?php
defined('BASEPATH') OR exit('No direct script access allowed');
Class Commissions extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('Transaction_M');
        $this->load->model('Commission_M');
        $this->load->model('User_Model');
    }
    
    /*
     * User Commission Information
     */
    
    public function index($page = 'commissions', $data = array()){

        if($this->session->userdata('isUserLoggedIn')){
        	$Userid = $this->session->userdata('userid');
			$money = $this->User_Model->get_user($Userid)->Wallet;
			if($money <= 50){
                $this->session->set_flashdata('notice', 'Insufficient Account Balance. Please add fund to your wallet to proceed!');
            }
            if(!isset($this->User_Model->get_user($Userid)->Picture)){
                $image ='';
            } else{
                $image = $this->User_Model->get_user($Userid)->Picture;
            }
            $head = array(
                'title' => 'pmb', 
                'money' => $money,
                'image' => $image
            );
            array_push($data, array('money' => $money));
            $this->load->view('user-templates/header', $head);
            $this->load->view('pages/user/'. $page, $data);
			$this->load->view('user-templates/footer');
        }
        else{
            redirect('/');
        }
    }

    
    public function add_commission(){

        $page = 'add-commission';
        $data = array();
        $id = $this->session->userdata('userid');
        if ($this->session->set_flashdata('warning')) {
            $data['warning'] = $this->session->set_flashdata('warning');
            unset($_SESSION['warning']);
        }
        if ($this->session->set_flashdata('success_msg')) {
            $data['success_msg'] = $this->session->set_flashdata('success_msg');
            unset($_SESSION['success_msg']);
        }
        if ($this->session->set_flashdata('error_msg')) {
            $data['error_msg'] = $this->session->set_flashdata('error_msg');
            unset($_SESSION['error_msg']);
        }
        if ($this->input->post()) {
            $this->form_validation->set_rules('amount', 'Amount', 'required|numeric');
            $this->form_validation->set_rules( 'gateway', 'Select Payment Gateway...', 'required');
            if($this->input->post('gateway') === 'Paystack'){
                $transactionid = 'STXN'. time();
            } else{
                $transactionid = 'B-STXN'. time();
            }
            $userData = array(
                'User_id' => $this->session->userdata('userid'),
                'Gateway' => $this->input->post('gateway'),
                'Amount' => $this->input->post('amount'),
                'TransactionID' => $transactionid,
                'TransactionBy' => $this->session->userdata('username'),
                'Status' => 'Completed',
                'Created' => date("Y-m-d")
            );
            $amount = intval($this->input->post('amount'));
            if($amount < 100){
                $this->session->set_flashdata('warning', 'Sorry! you can only commission minimum of N100');
                redirect('user/add-commission');
            }
            if ($this->form_validation->run() == true) {
                $res = $this->Transaction_M->add_commission($userData);
                if($res){
                    $this->session->set_flashdata('success_msg', 'Your Profile have been updated successfully.');
                    redirect('user/add-commission');
                } 
            } else {
                    $this->session->set_flashdata('error_msg', 'Some problems occurred, please try again.');
                    redirect('user/add-commission');
                }
        } else{
            $this->index($page, $data);
        }
    }

    public function view(){

        $Userid = $this->session->userdata('userid');
        $data = array();
        $config = array();
        $config["base_url"] = base_url() . "Commissions/view";
        $config["total_rows"] = $this->Commission_M->get_count_commission($Userid);
        $config["per_page"] = 10;
        $config["uri_segment"] = 2;
        $this->pagination->initialize($config);
        $page = ($this->uri->segment(2)) ? $this->uri->segment(2) : 0;
        $data["links"] = $this->pagination->create_links();
        $data['commissions'] = $this->Commission_M->get_all_commission($config["per_page"], $page, $Userid);
        $data['flag'] = true;
        $this->index($page, $data);
    }
}
