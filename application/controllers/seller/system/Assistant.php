<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Assistant extends CI_Controller
{

    private $shop_data;//后台用户登录信息

    public function __construct()
    {
        parent::__construct();
        $this->load->helpers('shop_helper');
        $this->shop_data = shop_login();
        assign('shop_data', $this->shop_data);
        $this->load->model('loop_model');
        $this->shop_id = $this->shop_data['id'];
    }

    /**
     * 列表
     */
    public function index()
    {
        $pagesize = 20;//分页大小
        $page     = (int)$this->input->get('per_page');
        $page <= 1 ? $page = 1 : $page = $page;//当前页数
        //搜索条件start
        $where_data = array();
        $status     = $this->input->post_get('status');
        if ($status >= '0') $where_data['where']['status'] = $status;
        //角色
        $role_id = $this->input->post_get('role_id');
        if ($role_id != '') $where_data['where']['role_id'] = $role_id;
        //用户名
        $username = $this->input->post_get('username');
        if (!empty($username)) $where_data['where']['username'] = $username;
        $search_where = array(
            'status'   => $status,
            'role_id'  => $role_id,
            'username' => $username,
        );
        assign('search_where', $search_where);
        $where_data['where']['shop_id'] = $this->shop_id;
        //搜索条件end
        //查到数据
        $list = $this->loop_model->get_list('member_shop_assistant', $where_data, $pagesize, $pagesize * ($page - 1));//列表
        assign('list', $list);
        //开始分页start
        $all_rows = $this->loop_model->get_list_num('member_shop_assistant', $where_data);//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        //开始分页end

        //角色列表
        $role_data    = $this->loop_model->get_list('role', array('where' => array('shop_id' => 1)));
        $role_list[0] = array('id' => 0, 'name' => '超级店员');
        foreach ($role_data as $key) {
            $role_list[$key['id']] = $key;
        }
        assign('role_list', $role_list);

        assign('status', array('0' => '正常', 1 => '锁定'));//状态
        display('/system/assistant/list.html');
    }


    /**
     * 添加编辑
     */
    public function edit($id = '')
    {
        $id = (int)$id;
        if (!empty($id)) {
            $admin = $this->loop_model->get_id('member_shop_assistant', $id);
            assign('item', $admin);
        }

        //角色列表
        $role_list   = $this->loop_model->get_list('role', array('where' => array('status'=>0,'shop_id' => '1')));
        $role_list[] = array('id' => 0, 'name' => '超级店员');
        assign('role_list', $role_list);
        display('/system/assistant/add.html');
    }

    /**
     * 保存数据
     */
    public function save()
    {
        if (is_post()) {
            $data_post   = $this->input->post(NULL, true);
            $update_data = array(
                'username'  => $data_post['username'],
                'full_name' => $data_post['full_name'],
                'role_id'   => $data_post['role_id'],
                'tel'       => $data_post['tel'],
            );

            $this->load->model('loop_model');
            if (!empty($data_post['id'])) {
                //修改密码
                if (!empty($data_post['password'])) {
                    if ($data_post['password'] != $data_post['repassword']) {
                        error_json('两次密码不一样');
                    } else {
                        $update_data['salt']     = get_rand_num();
                        $update_data['password'] = md5(md5($data_post['password']) . $update_data['salt']);
                    }
                }
                $res = $this->loop_model->update_where('member_shop_assistant', $update_data, array('id' => $data_post['id'], 'shop_id' => $this->shop_id));
                shop_admin_log_insert('修改店员' . $data_post['id']);
            } else {
                //增加数据
                if (empty($data_post['username']) || empty($data_post['password'])) {
                    error_json('用户名和密码不能为空');
                } elseif ($data_post['password'] != $data_post['repassword']) {
                    error_json('两次密码不一样');
                } else {
                    $username = $data_post['username'];
                    //判断用户名是否重复
                    $member_data = $this->loop_model->get_where('member_shop_assistant', array('username' => $username));
                    if (!empty($member_data)) {
                        error_json('用户名已经存在');
                    } else {
                        $update_data['username'] = $username;
                        $update_data['salt']     = get_rand_num();
                        $update_data['password'] = md5(md5($data_post['password']) . $update_data['salt']);
                        $update_data['addtime']  = time();
                        $update_data['lasttime'] = time();
                        $update_data['shop_id']  = $this->shop_id;
                        $this->db->insert('member_shop_assistant', $update_data);
                        $res = $this->db->insert_id();
                        shop_admin_log_insert('增加店员' . $res);
                    }
                }
            }
            if (!empty($res)) {
                error_json('y');
            } else {
                error_json('保存失败');
            }
        } else {
            error_json('提交方式错误');
        }

    }

    /**
     * 修改数据状态
     */
    public function update_status()
    {
        $id     = $this->input->post('id', true);
        $status = $this->input->get_post('status', true);
        if (empty($id) || $status == '') error_json('id错误');
        $status                = (int)$status;
        $update_data['status'] = $status;
        $update_where          = array(
            'where_in' => array('id' => $id),
            'where'    => array('shop_id' => $this->shop_id),
        );
        $res                   = $this->loop_model->update_where('member_shop_assistant', $update_data, $update_where);
        if (!empty($res)) {
            if (is_array($id)) $id = join(',', $id);
            shop_admin_log_insert('修改店员status为' . $status . 'id为' . $id);
            error_json('y');
        } else {
            error_json('操作失败');
        }
    }

    /**
     * 验证会员名是否存在
     */
    public function repeat_username()
    {
        $username = $this->input->post('param', true);
        if (!empty($username)) {
            $username    = $username;
            $member_data = $this->loop_model->get_where('member_shop_assistant', array('username' => $username));
            if (!empty($member_data)) {
                error_json('用户名已经存在');
            } else {
                error_json('y');
            }
        }
    }

    /**
     * 修改密码
     */
    public function update_password()
    {
        $shop_id = $this->session->userdata('shop_id');
        $admin = $this->loop_model->get_id('member_shop_assistant', $shop_id['shop_id'], 'username');  
        assign('item', $admin);

        display('/system/admin/update_password.html');
    }

    /**
     * 修改密码保存
     */
    public function update_password_save()
    {
        $shop_id = $this->session->userdata('shop_id');
        $data_post = $this->input->post(NULL, true);
        $admin = $this->loop_model->get_id('member_shop_assistant', $shop_id['shop_id']);
            
        if ($data_post['password'] != $data_post['repassword']) {
            error_json('两次密码不一样');
        } else {
            $update_data['salt']     = get_rand_num();
            $update_data['password'] = md5(md5($data_post['password']) . $update_data['salt']);
            $res = $this->loop_model->update_id('member_shop_assistant', $update_data, $shop_id['shop_id']);
           
            if ($res) {
                error_json('y');
            } else {
                error_json('修改失败');
            }
        }
    
    }
}
