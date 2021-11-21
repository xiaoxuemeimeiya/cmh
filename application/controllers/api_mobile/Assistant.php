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

    
    /**
     * 我的订单列表
     * type(null-全部订单，1-待付款，2-已支付，3-待收货，4-已完成，5-已退款，6-已取消，10-退款/售后）
     * status(null-全部订单，1-待付款，2-待发货，3-待收货，4-待评价，10-退款/售后）
     * */
   public function order_list()
   {
       //自动执行start********************************************
       $m_id     = (int)$this->input->get_post('m_id');
       //根据店员获取店铺
       $shop_data = $this->loop_model->get_where('member_shop_assistant',array('id'=>$m_id),'id,shop_id');
       $where_data['where']['o.shop_id'] = $shop_data['shop_id'];
       $this->load->model('order/order_model');
       $this->order_model->auto_cancel();//自动取消超时的订单
       $this->order_model->auto_confirm();//自动确认超时的订单
       //自动执行end**********************************************

       $pagesize = 10;//分页大小
       $page     = (int)$this->input->get_post('page');
       $page <= 1 ? $page = 1 : $page = $page;//当前页数
       //搜索条件start
       
       //状态
        $where_data['sql'] = '((o.status=2) or (o.status=3) or (o.status=4) or (o.status=5))';
        
       //支付状态
       $where_data['where']['payment_status'] = 1;

       //关键字
       /*
       $keyword_where = $this->input->get_post('keyword_where');
       $keyword       = $this->input->get_post('keyword');
       if (!empty($keyword_where) && !empty($keyword)) $where_data['where'][$keyword_where] = $keyword;
       $search_where = array(
           'keyword_where'  => $keyword_where,
           'keyword'        => $keyword,
       );
       */

       //搜索条件end
       $where_data['select'] = "o.id,o.order_no,FROM_UNIXTIME(o.offtime,'%Y-%m-%d %H:%i:%s') offtime,o.status,round(o.sku_price_real / 100, 2) as sku_price_real,FROM_UNIXTIME(o.paytime,'%Y-%m-%d %H:%i:%s') paytime,m.nickname,m.headimgurl,k.name,k.image,s.m_id as shop_id,s.shop_name,FROM_UNIXTIME(k.start_time,'%Y-%m-%d %H:%i:%s') start_time,FROM_UNIXTIME(k.end_time,'%Y-%m-%d %H:%i:%s') end_time";
       $where_data['join']   = array(
           array('member_oauth as m', 'o.m_id=m.id'),
           array('goods as k', 'o.good_id=k.id'),
           array('member_shop as s', 's.m_id=o.shop_id'),
       );
       //查到数据
       $order_list = $this->loop_model->get_list('order as o', $where_data, $pagesize, $pagesize * ($page - 1), 'o.id desc');//列表
       //assign('list', $order_list);
       //开始分页start
       $all_rows = $this->loop_model->get_list_num('order as o', $where_data);//所有数量
       //assign('page_count', ceil($all_rows / $pagesize));
       //开始分页end
       $this->ResArr['code'] = 200;
       $this->ResArr['data'] = [
           'list'=>$order_list,
           'page_count'=> ceil($all_rows / $pagesize)
       ];;
       echo ch_json_encode($this->ResArr);exit;
   }
    
    /**
     * 推出登录
     */
    public function login_out(){
        $m_id = $this->input->post('m_id');
        cache('del', 'assistant_token_' . $m_id, '');//保存token
        $this->ResArr['code'] = 200;
        $this->ResArr['msg'] = '退出登录成功';
        echo ch_json_encode($this->ResArr);exit;
    }
}
