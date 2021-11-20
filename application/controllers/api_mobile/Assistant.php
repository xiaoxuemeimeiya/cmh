<?php

defined('BASEPATH') OR exit('No direct script access allowed');
class Assistant extends ST_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
        $this->ResArr = [];
    }

    /***
     * 店员登录
     */
    public function login()
    {
        $username = trim($this->input->post('username', true));
        $password = trim($this->input->post('password', true));
        if (!empty($username) && !empty($password)) {
            $this->load->model('loop_model');
            $shop_shop_data = $this->loop_model->get_where('member_shop_assistant', array('username' => $username));
            
            $shop_data = array(
                'id'           => $shop_shop_data['id'],
                'username'     => $shop_shop_data['username'],
                'password'     => $shop_shop_data['password'],
                'salt'         => $shop_shop_data['salt'],
                'admin_status' => $shop_shop_data['status'],
            );

            if ($shop_data['username'] == '') {
                $this->ResArr['code'] = 4;
                $this->ResArr['msg'] = '用户名不存在';
                echo ch_json_encode($this->ResArr);exit;
            } elseif ($shop_data['password'] != md5(md5($password) . $shop_data['salt'])) {
                $this->ResArr['code'] = 4;
                $this->ResArr['msg'] = '密码错误';
                echo ch_json_encode($this->ResArr);exit;
            } elseif ($shop_data['status'] != 0 || $shop_data['admin_status'] != 0) {
                $this->ResArr['code'] = 4;
                $this->ResArr['msg'] = '帐号被管理员锁定';
                echo ch_json_encode($this->ResArr);exit;
            } else {
                $token = md5($shop_shop_data['id'] . $shop_shop_data['salt']);
                $tokenData = [
                    'm_id' => $shop_shop_data['id'],
                    'token' => $token,
                    'timestamp' => time() + 5 * 24 * 3600,
                ];
                cache('save', 'assistant_token_' . $shop_shop_data['id'], $token, time() + 30 * 24 * 3600);//保存token
                //shop_admin_log_insert($shop_shop_data['username'] . '登录系统');
                $this->ResArr['code'] = 200;
                $this->ResArr['data'] = $tokenData;
                echo ch_json_encode($this->ResArr);exit;
            }
        } else {
            $this->ResArr['code'] = 4;
            $this->ResArr['msg'] = '账号和密码不能为空';
            echo ch_json_encode($this->ResArr);exit;
        }
    }
	 /***
     *用户信息
     */
    public function detail()
    {
		$m_id = $this->input->post('m_id');
		$info = $this->loop_model->get_where('member_shop_assistant',array('id'=>$m_id),'id,username,full_name,tel,shop_id');
        $detail = $this->loop_model->get_where('member_shop',array('m_id'=>$info['shop_id']),'shop_name,logo,cove_img');
        $this->ResArr['code'] = 200;
        $this->ResArr['data'] = array_merge($info,$detail);
        echo ch_json_encode($this->ResArr);exit;
    }

    /***
     * 我的核销
     */
    public function fill_list()
    {
        $pagesize = 20;//分页大小
        $page     = (int)$this->input->get('per_page');
        $page <= 1 ? $page = 1 : $page = $page;//当前页数
        $date = $this->input->get('date');
        $m_id = $this->input->get('m_id');
        if(!$date){
            $date = strtotime(date('Y-m',time()));
        }
        $start_time = strtotime($date);
        $end_time =  $end_time = strtotime('+1 month',strtotime($date));
        $where['where']['addtime >='] = $start_time;
        $where['where']['addtime <'] = $end_time;
        $where['where']['m_id']= $m_id;
        $where['select']= array('id,m_id,amount,note,add_money,consume,FROM_UNIXTIME(addtime,"%m月%d %H:%i") as addtime');
        $recharge_list = $this->loop_model->get_list('order_collection_doc',$where, $pagesize, $pagesize * ($page - 1),'');
        $all_rows = $this->loop_model->get_list_num('order_collection_doc',$where,'','','');
        $pages = ceil($all_rows / $pagesize);
        //获取本月的总赚费用，以及获取本月的总花费用
        $where['select'] = array('sum(amount) as total_amount');
        $where1 = $where;
        $where1['where']['amount >'] = 0;
        $make_in = $this->loop_model->get_list('order_collection_doc',$where1,'','','');
        $where2 = $where;
        $where2['where']['amount <'] = 0;
        $make_out = $this->loop_model->get_list('order_collection_doc',$where2,'','','');
        $list['recharge_list'] = $recharge_list;
        $list['pages'] = $pages;
        $list['make_money']= $make_in[0]['total_amount'];
        $list['consume_money']= $make_out[0]['total_amount'] ;
        error_json($list);
    }

}
