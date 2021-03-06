<?php
/**
 * Created by PhpStorm.
 * User: bin.shen
 * Date: 5/2/16
 * Time: 09:56
 */

 if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once "Wx_controller.php";
class Wx_api extends CI_controller {
    private $return_success = array('status' => 1, 'msg' => '', 'result' => array());
    private $return_fail = array('status' => -1, 'msg' => '操作失败!', 'result' => array());
    public function __construct()
    {
        parent::__construct();
        ini_set('date.timezone','Asia/Shanghai');
        $this->load->model('wx_index_model');

    }

    public function ajaxReturn($data){
        exit(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public function api_user_info(){
        if($this->session->userdata('openid')){
            $data = $this->wx_index_model->api_user_info();
            echo json_encode($data);
        }
    }

    public function sendSms(){
        if(!$this->session->userdata('openid')){
            $this->return_success['msg'] = '成功!';
            $this->ajaxReturn($this->return_success);
        }

        $type = $this->input->get('type');
        $mobile = $this->input->get('mobile');
        if(!$mobile){
            $this->return_fail['msg'] = '电话号码不能为空!';
            $this->ajaxReturn($this->return_fail);
        }
        $this->load->model('sms_model');

        //随机一个验证码
        $code = rand(10000, 99999);
        $res = $this->sms_model->send_code($mobile, '房猫服务中心', $code, $type);
        $this->ajaxReturn($res);
    }

    public function get_region()
    {
        $parent_id = $this->input->get('parent_id');
        $res = $this->wx_index_model->get_region($parent_id);
        $return_ = ['status' => 1, 'msg' => '获取成功', 'result' => $res];
        echo json_encode($return_);
    }

    public function check_region(){
        $res = $this->wx_index_model->check_region();
        $this->ajaxReturn($res);
    }

    public function test_wx_post(){
        die('...');
        $data_msg = array(
            'first' => array(
                'value' => "赎楼工作单审核通过!",
                'color' => '#FF0000'
            ),
            'keyword1' => array(
                'value' => '赎楼工作单',
                'color' => '#FF0000'
            ),
            'keyword2' => array(
                'value' => '审核通过',
                'color' => '#FF0000'
            ),
            'keyword3' => array(
                'value' => date('Y-m-d H:i:s'),
                'color' => '#FF0000'
            ),
            'remark' => array(
                'value' => '感谢您对我们工作的信任,请点击查看需要携带的资料!',
                'color' => '#FF0000'
            )
        );
        $this->wx_index_model->wxpost($this->config->item('WX_YY'), $data_msg, 8, $this->config->item('img_url_DBY') . '/wx_users/foreclosure_detail7/' . '1');
    }
}