<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ajax_api extends CI_Controller {

    /**
     * Ajax控制器
     * @version 1.0
     * @author yaobin <bin.yao@thmarket.cn>
     * @date 2017-12-20n
     * @Copyright (C) 2017, Tianhuan Co., Ltd.
     */
	public function __construct()
    {
        parent::__construct();
        ini_set('date.timezone','Asia/Shanghai');
        $this->load->library('image_lib');
        $this->load->helper('directory');
        $this->load->model('manager_model');
    }


    /**
     * 上传头像
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-3-31
     */
    public function upload_head(){
        $admin_info = $this->session->userdata('admin_info');
        if(!$admin_info){
            echo -1;//如果没有登陆 不可上传,以免有人恶意上传图片占用服务器资源
        }
        $dir = FCPATH . '/upload_files/head';
        if(!is_dir($dir)){
            mkdir($dir,0777,true);
        }
        $config['upload_path'] = './upload_files/head/';
        $config['allowed_types'] = 'gif|jpg|png|jpeg';
        $config['encrypt_name'] = true;
        $config['max_size'] = '3200';
        $this->load->library('upload', $config);

        if ( ! $this->upload->do_upload('userfile')){
            echo 1;
        }else{
            $pic_arr = $this->upload->data();
            echo $pic_arr['file_name'];
        }
    }

    /**
     * 上传头像
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-3-31
     */
    public function get_car_city(){
        $province = $this->input->post('province');
        $res = $this->manager_model->get_car_city(trim($province));
        echo json_encode($res);
        die;
    }


    /**
     * 注册所使用的短信验证
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-3-31
     */
    public function get_register_code($phone){
        $check = $this->manager_model->get_userByPhone(trim($phone));
        if($check){
            echo -2;
        }else{
            $this->get_phone_code(trim($phone));
        }
        die;
    }


    /**
     * 修改信息所使用的短信验证
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-3-31
     */
    public function get_edit_code($phone){
        $check = $this->manager_model->get_userByPhone(trim($phone));
        if($check){
            if($check['id'] != $this->session->userdata('driver_id')){
                echo -2;exit();
            }
        }
        $this->get_phone_code(trim($phone));
        die;
    }

    /**
     * 登陆所使用的短信验证
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-3-31
     */
    public function get_login_code($phone){
        $check = $this->manager_model->get_userByPhone(trim($phone));
        if($check){
            if($check['status'] != 1){
                echo -4;//此会员已经禁用
                exit();
            }
            if($check['type'] != 1){
                echo -3;//此会员不是认证司机
                exit();
            }
            $this->get_phone_code(trim($phone));
        }else{
            echo -2;
        }
        die;
    }

    /**
     * 商户登陆所使用的短信验证
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-3-31
     */
    public function get_login_code4business($phone){
        $check = $this->manager_model->get_businessByPhone(trim($phone));
        if($check == 1){
            $this->get_phone_code(trim($phone));
        }else{
            echo $check;
        }
        die;
    }


    public function test2(){
        //$this->system_model->wxpost_sys_business_bespeak('苏E5G52G',1,'44');
    }




}
