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
		$info = $this->loop_model->get_where('member_shop_assistant',array('id'=>$m_id),'id,username,full_name,tel,shop_id,mch_id');
        $detail = $this->loop_model->get_where('member_shop',array('m_id'=>$info['shop_id']),'shop_name,logo,cove_img');
        $this->ResArr['code'] = 200;
        $this->ResArr['data'] = array_merge($info,$detail);
        echo ch_json_encode($this->ResArr);exit;
    }

    /***
     * 核销
     */
    public function verify()
    {
        //获取内容
        $code = $this->input->post('code');
        if(!$code){
            $this->ResArr['code'] = 4;
            $this->ResArr['msg'] = '核销码不存在';
            echo ch_json_encode($this->ResArr);exit;
        }
        $info = $this->loop_model->get_where('order',array('code'=>$code,'status'=>2),'id,shop_id,good_id,starttime,endtime,');//获取已经支付的对应订单
        if(!$info){
            $this->ResArr['code'] = 4;
            $this->ResArr['msg'] = '核销码错误';
            echo ch_json_encode($this->ResArr);exit;
        }
        $m_id     = (int)$this->input->get_post('m_id');
       //根据店员获取店铺
       $shop_data = $this->loop_model->get_where('member_shop_assistant',array('id'=>$m_id),'id,shop_id');
       if($info['shop_id'] != $shop_data['shop_id']){
            $this->ResArr['code'] = 4;
            $this->ResArr['msg'] = '核销码不能在该店使用';
            echo ch_json_encode($this->ResArr);exit;
       }
        
        //查看核销码是否过期
        if(time()>$info['starttime'] && time()< $info['endtime']){
            //查看核销码是否是月卡，不是月卡的话修改状态，是月卡的话判断是否次数已经用完
            $goods = $this->loop_model->get_where('goods',array('id'=>$info['good_id']),'cat_type,cat_id,type,num');
            if($goods['cat_type'] == 2 && $goods['type'] == 3){
                //是月卡，查看次数是否足够
                $count = $this->loop_model->get_where('verify',array('order_id'=>$info['id'],'goods_id'=>$info['good_id']));
                if($count >= $goods['num']){
                    $this->ResArr['code'] = 4;
                    $this->ResArr['msg'] = '核销码已使用完';
                    echo ch_json_encode($this->ResArr);exit;
                }else{
                    $insert['order_id'] = $info['id'];
                    $insert['type'] = 2;
                    $insert['addtime'] = time();
                    $insert['goods_id'] = $info['good_id'];
                    $res = $this->loop_model->insert('verify',$insert);
                    if($res){
                        $this->ResArr['code'] = 200;
                        $this->ResArr['msg'] = '核销成功';
                        echo ch_json_encode($this->ResArr);exit;
                    }else{
                        $this->ResArr['code'] = 4;
                        $this->ResArr['msg'] = '核销失败，请联系客服';
                        echo ch_json_encode($this->ResArr);exit;
                    }
                }
            }else{
                //一次性的优惠券或者套餐券
                $update['status'] = 4;
                $res = $this->loop_model->update_where('order',$update,array('id'=>$info['id']));
                if($res){
                    $insert['order_id'] = $info['id'];
                    $insert['type'] = 1;
                    $insert['addtime'] = time();
                    $insert['goods_id'] = $info['good_id'];
                    $res = $this->loop_model->insert('verify',$insert);
                    $this->ResArr['code'] = 200;
                    $this->ResArr['msg'] = '核销成功';
                    echo ch_json_encode($this->ResArr);exit;
                }else{
                    $this->ResArr['code'] = 4;
                    $this->ResArr['msg'] = '核销失败，请联系客服';
                    echo ch_json_encode($this->ResArr);exit;
                }
            }
        }else{
            $this->ResArr['code'] = 4;
            $this->ResArr['msg'] = '核销码已过期';
            echo ch_json_encode($this->ResArr);exit;
        }


    }

    
    /**
     * 核销列表
     * type(null-全部订单，1-待付款，2-已支付，3-待收货，4-已完成，5-已退款，6-已取消，10-退款/售后）
     * status(null-全部订单，1-待付款，2-待发货，3-待收货，4-待评价，10-退款/售后）
     * */
   public function order_list()
   {
       //自动执行start********************************************
       $m_id     = (int)$this->input->get_post('m_id');
       //根据店员获取店铺
       $shop_data = $this->loop_model->get_where('member_shop_assistant',array('id'=>$m_id),'id,shop_id');
       $pagesize = 10;//分页大小
       $page     = (int)$this->input->get_post('page');
       $page <= 1 ? $page = 1 : $page = $page;//当前页数
       /*
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
       */
        $where_data['select'] = "o.id,o.order_no,FROM_UNIXTIME(a.addtime,'%Y-%m-%d %H:%i:%s') offtime,o.status,round(o.sku_price_real / 100, 2) as sku_price_real,FROM_UNIXTIME(o.paytime,'%Y-%m-%d %H:%i:%s') paytime,m.nickname,m.headimgurl,k.name,k.image,s.m_id as shop_id,s.shop_name,FROM_UNIXTIME(k.start_time,'%Y-%m-%d %H:%i:%s') start_time,FROM_UNIXTIME(k.end_time,'%Y-%m-%d %H:%i:%s') end_time";
        $where_data['join']   = array(
            array('order as o', 'a.order_id=o.id'),
            array('member_oauth as m', 'o.m_id=m.id'),
           array('goods as k', 'o.good_id=k.id'),
           array('member_shop as s', 's.m_id=o.shop_id'),
        );
        $where_data['where']['o.shop_id'] = $shop_data['shop_id'];
        $list = $this->loop_model->get_list('verify as a',$where_data,$pagesize, $pagesize * ($page - 1), 'o.id desc');
        $all_rows = $this->loop_model->get_list_num('verify as a', $where_data);//所有数量
        $this->ResArr['code'] = 200;
        $this->ResArr['data'] = [
            'list'=>$list,
            'page_count'=> ceil($all_rows / $pagesize)
        ];
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
