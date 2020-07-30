<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Manager_model extends MY_Model
{

    /**
     * 管理员操作Model
     * @version 1.0
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-29
     * @Copyright (C) 2017, Tianhuan Co., Ltd.
     */

    public function __construct() {
        parent::__construct();
    }

    public function check_login() {
        if (strtolower($this->input->post('verify')) != strtolower($this->session->flashdata('cap')))
            return -1;
        $data = array(
            'user' => trim($this->input->post('user')),
            'password' => password(trim($this->input->post('password'))),
        );
        $row = $this->db->select()->from('admin')->where($data)->get()->row_array();
        if ($row) {
            $data['admin_info'] = $row;
            $this->session->set_userdata($data);
            return 1;
        } else {
            return -2;
        }
    }

    /**
     * 获取用户所能显示的菜单
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-30
     */
    public function get_menu4admin($admin_id = 0) {
        $admin_info = $this->db->select()->from('auth_group g')
            ->join('auth_group_access a', 'g.id=a.group_id', 'left')
            ->where('a.admin_id', $admin_id)->get()->row_array();
        if (!$admin_info) {
            return array();
        }
        $menu_access_arr = explode(",", $admin_info['rules']);
        $this->db->select('id,title,pid,name,icon');
        $this->db->from('auth_rule');
        $this->db->where('islink', 1);
        $this->db->where('status', 1);
        if ($admin_info['group_id'] != 1) {
            $this->db->where_in('id', $menu_access_arr);
        }
        $menu = $this->db->order_by('o asc')->get()->result_array();
        return $menu;
    }

    public function get_action_menu($controller = null, $action = null) {
        $action_new = str_replace('edit', 'list', $action);
        $action_new = str_replace('add', 'list', $action_new);
        $this->db->select('s.id,s.title,s.name,s.tips,s.pid,p.pid as ppid,p.title as ptitle');
        $this->db->from('auth_rule s');
        $this->db->join('auth_rule p', 'p.id = s.pid', 'left');
        $this->db->where('s.name', $controller . '/' . $action_new);
        $row = $this->db->get()->row_array();
        if (!$row) {
            $this->db->select('s.id,s.title,s.name,s.tips,s.pid,p.pid as ppid,p.title as ptitle');
            $this->db->from('auth_rule s');
            $this->db->join('auth_rule p', 'p.id = s.pid', 'left');
            $this->db->where('s.name', $controller . '/' . $action);
            $row = $this->db->get()->row_array();
        }
        return $row;
    }

    public function get_admin($admin_id) {
        $admin_info = $this->db->select('a.*,b.group_id,c.title')->from('admin a')
            ->join('auth_group_access b', 'a.admin_id = b.admin_id', 'left')
            ->join('auth_group c', 'c.id = b.group_id', 'left')
            ->where('a.admin_id', $admin_id)->get()->row_array();
        return $admin_info;
    }

    /**
     *********************************************************************************************
     * 以下代码为系统设置模块
     *********************************************************************************************
     */

    /**
     * 查找所有可添加的菜单
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function get_menu_all() {
        $this->db->select('id,title,pid,name,icon,islink,o');
        $this->db->from('auth_rule');
        $this->db->where('status', 1);
        $menu = $this->db->order_by('o asc')->get()->result_array();
        return $menu;
    }

    /**
     * 获取后台菜单详情
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function menu_info($id) {
        $menu_info = $this->db->select()->from('auth_rule')->where('id', $id)->get()->row_array();
        return $menu_info;
    }

    /**
     * 保存管理员管理
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function menu_save() {
        $data = array(
            'pid' => trim($this->input->post('pid')) ? trim($this->input->post('pid')) : 0,
            'title' => trim($this->input->post('title')) ? trim($this->input->post('title')) : null,
            'name' => trim($this->input->post('name')) ? trim($this->input->post('name')) : '',
            'icon' => trim($this->input->post('icon')) ? trim($this->input->post('icon')) : '',
            'islink' => trim($this->input->post('islink')) ? trim($this->input->post('islink')) : 0,
            'o' => trim($this->input->post('o')) ? trim($this->input->post('o')) : 0,
            'tips' => trim($this->input->post('tips')) ? trim($this->input->post('tips')) : '',
            'cdate' => date('Y-m-d H:i:s', time()),
            'mdate' => date('Y-m-d H:i:s', time())
        );
        if (!$data['title'])
            return -2;//信息不全
        if ($id = $this->input->post('id')) {
            unset($data['cdate']);
            $this->db->where('id', $id)->update('auth_rule', $data);
        } else {
            $this->db->insert('auth_rule', $data);
        }
        return 1;
    }

    /**
     * 删除管理员
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function menu_del($id) {
        if (!$id)
            return -1;
        $rs = $this->db->where('id', $id)->delete('auth_rule');
        if ($rs)
            return 1;
        return -1;
    }

    /**
     *********************************************************************************************
     * 以下代码为个人中心模块
     *********************************************************************************************
     */

    /**
     * 管理员管理
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */

    public function admin_list($page = 1) {
        $data['limit'] = $this->limit;//每页显示多少调数据
        $data['keyword'] = trim($this->input->get('keyword')) ? trim($this->input->get('keyword')) : null;
        $data['field'] = trim($this->input->get('field')) ? trim($this->input->get('field')) : 1;// 1是用户名,2是电话,3是QQ,4是邮箱
        $data['order'] = trim($this->input->get('order')) ? trim($this->input->get('order')) : 1;// 1是desc,2是asc
        $this->db->select('count(1) num');
        $this->db->from('admin a');
        $this->db->join('auth_group_access b', 'a.admin_id = b.admin_id', 'left');
        $this->db->join('auth_group c', 'c.id = b.group_id', 'left');
        if ($data['keyword']) {
            switch ($data['field']) {
                case '1':
                    $this->db->like('a.user', $data['keyword']);
                    break;
                case '2':
                    $this->db->like('a.phone', $data['keyword']);
                    break;
                case '3':
                    $this->db->like('a.qq', $data['keyword']);
                    break;
                case '4':
                    $this->db->like('a.email', $data['keyword']);
                    break;
                default:
                    $this->db->like('a.user', $data['keyword']);
                    break;
            }
        }
        $rs_total = $this->db->get()->row();
        //总记录数
        $total_rows = $rs_total->num;
        $data['total_rows'] = $total_rows;
        //list
        $this->db->select('a.*,b.group_id,c.title');
        $this->db->from('admin a');
        $this->db->join('auth_group_access b', 'a.admin_id = b.admin_id', 'left');
        $this->db->join('auth_group c', 'c.id = b.group_id', 'left');
        if ($data['keyword']) {
            switch ($data['field']) {
                case '1':
                    $this->db->like('a.user', $data['keyword']);
                    break;
                case '2':
                    $this->db->like('a.phone', $data['keyword']);
                    break;
                case '3':
                    $this->db->like('a.qq', $data['keyword']);
                    break;
                case '4':
                    $this->db->like('a.email', $data['keyword']);
                    break;
                default:
                    $this->db->like('a.user', $data['keyword']);
                    break;
            }
        }
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        if ($data['order'] == 1) {
            $this->db->order_by('a.t', 'desc');
        } else {
            $this->db->order_by('a.t', 'asc');
        }
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    /**
     * 查找所有可添加的用户组
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function get_group_all() {
        $this->db->select('id,title');
        $this->db->from('auth_group');
        $this->db->where('status', 1);
        $menu = $this->db->order_by('id asc')->get()->result_array();
        return $menu;
    }

    /**
     * 保存管理员管理
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function admin_save() {
        $data = array(
            'user' => trim($this->input->post('user')) ? trim($this->input->post('user')) : null,
            'sex' => $this->input->post('sex') ? $this->input->post('sex') : 0,
            'head' => $this->input->post('head') ? $this->input->post('head') : null,
            'phone' => trim($this->input->post('phone')) ? trim($this->input->post('phone')) : null,
            'qq' => trim($this->input->post('qq')) ? trim($this->input->post('qq')) : null,
            'email' => trim($this->input->post('email')) ? trim($this->input->post('email')) : null,
            'birthday' => trim($this->input->post('birthday')) ? trim($this->input->post('birthday')) : null,
            't' => time()
        );
        if (!$data['user'] || !$data['head'] || !$data['phone'] || !$data['qq'] || !$data['email'] || !$data['birthday'])
            return $this->fun_fail('信息不全!');
        if (!file_exists(dirname(SELF) . '/upload_files/head/' . $data['head'])) {
            return $this->fun_fail('信息不全,头像异常!');
        }
        if (!$group_id = $this->input->post('group_id')) {
            return $this->fun_fail('需要选择用户组!');
        }
        if (trim($this->input->post('password'))) {
            if (strlen(trim($this->input->post('password'))) < 6) {
                return $this->fun_fail('密码长度不可小于6位!');
            }
            if (is_numeric(trim($this->input->post('password')))) {
                return $this->fun_fail('密码不可是纯数字!');
            }
            $data['password'] = password(trim($this->input->post('password')));
        }
        if ($admin_id = $this->input->post('admin_id')) {
            unset($data['t']);
            $check_ = $this->db->select()->from('admin')
                ->where('user', $data['user'])
                ->where('admin_id <>', $admin_id)
                ->get()->row_array();
            if ($check_) {
                return $this->fun_fail('新建或修改的用户名已存在!');
            }
            $this->db->where('admin_id', $admin_id)->update('admin', $data);
        } else {
            if (!trim($this->input->post('password'))) {
                return $this->fun_fail('新建用户需要设置密码!');
            }
            $check_ = $this->db->select()->from('admin')->where('user', $data['user'])->get()->row_array();
            if ($check_) {
                return $this->fun_fail('新建或修改的用户名已存在!');
            }
            $this->db->insert('admin', $data);
            $admin_id = $this->db->insert_id();
        }
        $this->db->where('admin_id', $admin_id)->delete('auth_group_access');
        $this->db->insert('auth_group_access', array('admin_id' => $admin_id, 'group_id' => $group_id));
        return $this->fun_success('保存成功');
    }

    /**
     * 删除管理员
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function admin_del($id) {
        if (!$id)
            return -1;
        $admin_info = $this->get_admin($id);
        if (!$admin_info)
            return -1;
        if ($admin_info['group_id'] == 1)
            return -2;
        $rs = $this->db->where('admin_id', $id)->delete('admin');
        if ($rs)
            return 1;
        return -1;
    }

    /**
     * 获取用户组信息
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function get_group_detail($id = 0) {
        $group_detail = $this->db->select()->from('auth_group')->where('id', $id)->get()->row_array();
        if (!$group_detail) {
            return -1;
        }
        $group_detail['rules'] = explode(',', $group_detail['rules']);
        return $group_detail;
    }

    /**
     * 保存用户组
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function group_save() {
        $data = array(
            'title' => trim($this->input->post('title')) ? trim($this->input->post('title')) : null,
            'status' => $this->input->post('status') ? $this->input->post('status') : -1,
        );
        if ($data['title'] == "") {
            return -1;
        }
        $rules = $this->input->post('rules') ? $this->input->post('rules') : 0;
        if (is_array($rules)) {
            foreach ($rules as $k => $v) {
                $rules[$k] = intval($v);
            }
            $rules = implode(',', $rules);
        }
        $data['rules'] = $rules;
        if ($group_id = $this->input->post('id')) {
            $this->db->where('id', $group_id)->update('auth_group', $data);
        } else {
            $this->db->insert('auth_group', $data);
        }
        return 1;
    }

    /**
     * 用户组列表
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function group_list($page = 1) {
        $data['limit'] = $this->limit;//每页显示多少调数据
        $this->db->select('count(1) num');
        $this->db->from('auth_group a');
        $rs_total = $this->db->get()->row();
        //总记录数
        $total_rows = $rs_total->num;
        $data['total_rows'] = $total_rows;

        //list
        $this->db->select('a.*');
        $this->db->from("auth_group a");
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        $this->db->order_by('id', 'asc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    /**
     * 删除用户组
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function group_del($id) {
        if (!$id)
            return -1;
        if ($id == 1)
            return -2;
        $rs = $this->db->where('id', $id)->delete('auth_group');
        if ($rs)
            return 1;
        return -1;
    }

    /**
     * 保存管理员管理
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function personal_save($admin_id) {
        $data = array(
            'user' => trim($this->input->post('user')) ? trim($this->input->post('user')) : null,
            'sex' => $this->input->post('sex') ? $this->input->post('sex') : 0,
            'head' => $this->input->post('head') ? $this->input->post('head') : null,
            'phone' => trim($this->input->post('phone')) ? trim($this->input->post('phone')) : null,
            'qq' => trim($this->input->post('qq')) ? trim($this->input->post('qq')) : null,
            'email' => trim($this->input->post('email')) ? trim($this->input->post('email')) : null,
            'birthday' => trim($this->input->post('birthday')) ? trim($this->input->post('birthday')) : null,
        );
        if (!$data['user'] || !$data['head'] || !$data['phone'] || !$data['qq'] || !$data['email'] || !$data['birthday'])
            return $this->fun_fail('信息不全!');
        if (!file_exists(dirname(SELF) . '/upload_files/head/' . $data['head'])) {
            return $this->fun_fail('信息不全!');
        }
        if (trim($this->input->post('password'))) {
            if (strlen(trim($this->input->post('password'))) < 6) {
                return $this->fun_fail('密码长度不可小于6位!');
            }
            if (is_numeric(trim($this->input->post('password')))) {
                return $this->fun_fail('密码不可是纯数字!');
            }
            $data['password'] = password(trim($this->input->post('password')));
        }
        $this->db->where('admin_id', $admin_id)->update('admin', $data);
        return $this->fun_success('保存成功!');
    }

    /**
     *********************************************************************************************
     * 以下代码为考试中心模块
     *********************************************************************************************
     */

    /**
     * 准考证数据列表
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-10-16
     */

    public function exam_user_list($page = 1){
        $data['limit'] = $this->limit;//每页显示多少调数据
        $data['keyword'] = trim($this->input->get('keyword')) ? trim($this->input->get('keyword')) : null;
        $data['status'] = trim($this->input->get('status')) ? trim($this->input->get('status')) : null;

        $this->db->select('count(1) num');
        $this->db->from('exam_user eu');
        if ($data['keyword']) {
            $this->db->group_start();
            $this->db->like('eu.name', $data['keyword']);
            $this->db->or_like('eu.exam_ticket', $data['keyword']);
            $this->db->or_like('eu.exam_path', $data['keyword']);
            $this->db->group_end();
        }

        if ($data['status']) {
            $this->db->where('eu.status', $data['status']);
        }

        $rs_total = $this->db->get()->row();
        //总记录数
        $total_rows = $rs_total->num;
        $data['total_rows'] = $total_rows;
        //list
        $this->db->select('eu.*');
        $this->db->from('exam_user eu');
        if ($data['keyword']) {
            $this->db->group_start();
            $this->db->like('eu.name', $data['keyword']);
            $this->db->or_like('eu.exam_ticket', $data['keyword']);
            $this->db->or_like('eu.exam_path', $data['keyword']);
            $this->db->group_end();
        }

        if ($data['status']) {
            $this->db->where('eu.status', $data['status']);
        }
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        $this->db->order_by('eu.exam_ticket', 'desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    //准考证详情
    public function exam_user_edit($id){
        $this->db->select('eu.*');
        $this->db->from('exam_user eu');
        $this->db->where('eu.id', $id);
        $data = $this->db->get()->row_array();
        return $data;
    }


    /**
     * 准考证 批量处理
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-10-16
     */

    public function upload_exam_user($admin_id) {
        //先查看是否有数据,如果数据库内有数据,不可导入
        $row_ = $this->db->select()->from('exam_user')->get()->row_array();
        if($row_)
            return '列表存在数据,不可导入!';
        if (is_readable('./././upload') == false) {
            mkdir('./././upload');
        }
        if (is_readable('./././upload/excel_upload') == false) {
            mkdir('./././upload/excel_upload');
        }
        $change_row = 0;
        $config['upload_path'] = "./upload/excel_upload";
        $config['allowed_types'] = "*";
        $config['encrypt_name'] = true;
        $config['max_size'] = '200000';
        //$config['encrypt_name']=true;
        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('file')) {
            die(var_dump($this->upload->display_errors()));
        }
        $data = $this->upload->data();
        require_once(APPPATH . 'libraries/PHPExcel/PHPExcel.php');
        require_once(APPPATH . 'libraries/PHPExcel/PHPExcel/IOFactory.php');
        //die(APPPATH . 'libraries/PHPExcel/PHPExcel/IOFactory.php');
        $uploadfile = './upload/excel_upload/' . $data['file_name'];//获取上传成功的Excel
        if ($data['file_ext'] == ".xlsx") {
            $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        } else {
            $objReader = PHPExcel_IOFactory::createReader('Excel5');
        }
        //use excel2007 for 2007 format 注意 linux下需要大小写区分 填写Excel2007   //xlsx使用2007,其他使用Excel5
        $objPHPExcel = $objReader->load($uploadfile);//加载目标Excel
        // 处理企业信息
        $sheet = $objPHPExcel->getSheet(0);//读取第一个sheet

        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $letter = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
        $tableheader = array('序号', '准考证号', '姓名', '手机号', '身份证号', '执业公司', '考试场地', '座位号', '考试时间', '考试地点');
        for ($i = 0; $i < count($tableheader); $i++) {
            $record_hear_name = trim((string)$sheet->getCellByColumnAndRow($letter[$i], 1)->getValue());
            if ($record_hear_name != $tableheader[$i]) {
                return "第" . ($letter[$i] + 1) . "列不是 " . $tableheader[$i] . '!';
            }
        }

        $insert_yes = 0;
        $update_yes = 0;
        $insert_err = 0;
        for ($row = 2; $row <= $highestRow; $row++) {

            $data_insert = array(
                'exam_ticket' => trim((string)$sheet->getCellByColumnAndRow(1, $row)->getValue()),
                'name' => trim((string)$sheet->getCellByColumnAndRow(2, $row)->getValue()),
                'phone' => trim((string)$sheet->getCellByColumnAndRow(3, $row)->getValue()),
                'exam_seat' => trim((string)$sheet->getCellByColumnAndRow(7, $row)->getValue()),
                'exam_room' => trim((string)$sheet->getCellByColumnAndRow(6, $row)->getValue()),
                'exam_time' => trim((string)$sheet->getCellByColumnAndRow(8, $row)->getValue()),
                'exam_path' => trim((string)$sheet->getCellByColumnAndRow(9, $row)->getValue()),
                'code' => trim((string)$sheet->getCellByColumnAndRow(4, $row)->getValue()),
                //'sex' => trim((string)$sheet->getCellByColumnAndRow(5, $row)->getValue()),
                'company' => trim((string)$sheet->getCellByColumnAndRow(5, $row)->getValue()),
                'creater_time' => time(),
                'modify_time' => time(),
                'creater_admin_id' => $admin_id,
                'modify_admin_id' => $admin_id,
                'status' => 1
            );
            //默认头像地址是身份证号
            $data_insert['head_img'] = $this->config->item('base_url') . 'upload/exam_user/' . $data_insert['code'] . '.jpg';
            if (!$data_insert['exam_ticket'] || !$data_insert['code']) {
                $insert_err++;
                continue;
            }
            $check_ticket_ = $this->db->select('exam_ticket')->from('exam_user')->where('exam_ticket', $data_insert['exam_ticket'])->get()->row_array();
            $check_code_ = $this->db->select('code')->from('exam_user')->where('code', $data_insert['code'])->get()->row_array();
            if ($check_ticket_ || $check_code_) {
                if($check_ticket_)
                    die('准考证号 ' . $check_ticket_['exam_ticket'] . '重复');
                if($check_code_)
                    die('身份证号 ' . $check_code_['code'] . '重复');
                //unset($data_insert['creater_time']);
                //unset($data_insert['creater_admin_id']);
                //unset($data_insert['status']);
                //$update_yes++;
                //$this->db->where('exam_ticket', $data_insert['exam_ticket'])->update('exam_user', $data_insert);
            } else {
                $insert_yes++;
                $this->db->insert('exam_user', $data_insert);
            }


        }
        //return '成功新增 ' . $insert_yes . ' 条!成功更新 ' . $update_yes . ' 条!失败 ' . $insert_err . ' 条!';
        return '成功新增 ' . $insert_yes . ' 条!失败 ' . $insert_err . ' 条!';
    }

    public function exam_user_save($admin_id){
        $data_insert = array(
            'exam_ticket' => trim($this->input->post('exam_ticket')) ? trim($this->input->post('exam_ticket')) : null,
            'name' => trim($this->input->post('name')) ? trim($this->input->post('name')) : null,
            'exam_seat' => trim($this->input->post('exam_seat')) ? trim($this->input->post('exam_seat')) : null,
            'exam_room' => trim($this->input->post('exam_room')) ? trim($this->input->post('exam_room')) : null,
            'exam_time' => trim($this->input->post('exam_time')) ? trim($this->input->post('exam_time')) : null,
            'exam_path' => trim($this->input->post('exam_path')) ? trim($this->input->post('exam_path')) : null,
            'code' => trim($this->input->post('code')) ? trim($this->input->post('code')) : null,
            'head_img' => $this->input->post('head_img') ? $this->input->post('head_img') : null,
            'creater_time' => time(),
            'modify_time' => time(),
            'creater_admin_id' => $admin_id,
            'modify_admin_id' => $admin_id,
            'status' => trim($this->input->post('status')) ? trim($this->input->post('status')) : -1,
        );
        if(!$data_insert['exam_ticket'])
            return $this->fun_fail('准考证号不能为空!');
        if(!$data_insert['code'])
            return $this->fun_fail('身份证号不能为空!');
        if(!$data_insert['name'] || !$data_insert['exam_seat'] || !$data_insert['exam_room'] || !$data_insert['exam_time'] || !$data_insert['exam_path'])
            return $this->fun_fail('信息不全!');
        $exam_user_id = trim($this->input->post('exam_user_id')) ? trim($this->input->post('exam_user_id')) : null;

        if($exam_user_id){
            $check_ticket_ = $this->db->select('')->from('exam_user')->where('exam_ticket', $data_insert['exam_ticket'])->where('id <>', $exam_user_id)->get()->row_array();
            if($check_ticket_){
                return $this->fun_fail('此准考证号已被使用!');
            }
            $check_code_ = $this->db->select('')->from('exam_user')->where('code', $data_insert['code'])->where('id <>', $exam_user_id)->get()->row_array();
            if($check_code_){
                return $this->fun_fail('此身份证号已被使用!');
            }
            unset($data_insert['creater_time']);
            unset($data_insert['creater_admin_id']);
            $this->db->where('id', $exam_user_id)->update('exam_user', $data_insert);
        }else{
            $check_ticket_ = $this->db->select('')->from('exam_user')->where('exam_ticket', $data_insert['exam_ticket'])->get()->row_array();
            if($check_ticket_){
                return $this->fun_fail('此准考证号已被使用!');
            }
            $check_code_ = $this->db->select('')->from('exam_user')->where('code', $data_insert['code'])->get()->row_array();
            if($check_code_){
                return $this->fun_fail('此身份证号已被使用!');
            }
            $this->db->insert('exam_user', $data_insert);
        }
        return $this->fun_success('保存成功');
    }

    //清空 准考证 数据,不可恢复
    public function exam_user_delete($admin_id){
        $this->db->where('id >', 0)->delete('exam_user');
        return $this->fun_success('清除成功!');
    }

}
