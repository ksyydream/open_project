<?php
/**
 * Created by PhpStorm.
 * User: bin.shen
 * Date: 6/2/16
 * Time: 21:22
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Agency extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        ini_set('date.timezone','Asia/Shanghai');
        $this->load->model('map_model');
    }

    //重载smarty方法assign
    public function assign($key,$val) {
        $this->cismarty->assign($key,$val);
    }

    //重载smarty方法display
    public function display($html) {
        $this->cismarty->display($html);
    }

    public function download(){
        $user_name = trim($this->input->post('username'));
        $code = trim($this->input->post('userid'));
        $data = $this->map_model->exam_download($user_name, $code);
        if(!$data)
            redirect(base_url('/agency/index'));
        $this->assign('data', $data);
        $this->display('agency/download.html');
    }

    public function index(){

        $this->display('agency/search.html');
    }

    public function check_user(){
        $user_name = trim($this->input->post('username'));
        $code = trim($this->input->post('userid'));
        $res = $this->map_model->check_exam_user($user_name, $code);
        echo json_encode($res);
        die;
    }

    public function sendSms(){
        die('test....');
        $this->load->model('sms_model');
        $ali_templateCode = $this->config->item('ali_templateCode');
        $res = $this->sms_model->sendSms('18914970292', '房猫服务中心', '1111', $ali_templateCode['1']);
        die(var_dump($res));
    }
}

