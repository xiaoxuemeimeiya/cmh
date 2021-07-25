<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Order extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        // 响应类型
        header('Access-Control-Allow-Methods:GET, POST, PUT,DELETE');
        // 响应头设置
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        $this->load->model('loop_model');
        $this->load->helpers('order_helper');
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
       $where_data['where']['o.m_id'] = $m_id;
       $this->load->model('order/order_model');
       $this->order_model->auto_cancel();//自动取消超时的订单
       $this->order_model->auto_confirm();//自动确认超时的订单
       //自动执行end**********************************************

       $pagesize = 10;//分页大小
       $page     = (int)$this->input->get_post('page');
       $page <= 1 ? $page = 1 : $page = $page;//当前页数
       //搜索条件start
       //是否删除
       $is_del = $this->input->get_post('is_del');
       if ($is_del == 1) {
           $where_data['where']['is_del'] = $is_del;
       } else {
           $where_data['where']['is_del'] = 0;
       }
       //状态
       $status = $this->input->get_post('type');
       if ($status == 1) {
           //待支付的
           $where_data['where']['o.status']      = 1;
       } elseif ($status == 2) {
           //已支付
           $where_data['where']['o.status']      = 2;
       } elseif ($status == 3) {
           //待收货
           $where_data['where']['o.status']      = 3;
       } elseif ($status == 4) {
           //已完成
           $where_data['sql'] = '((o.status=4) or (o.status=5))';
       }elseif ($status == 5) {
           //已退款
           $where_data['sql'] = '((o.status=6) or (o.status=7))';
       }elseif ($status == 6) {
           //已取消
           $where_data['sql'] = '((o.status=8) or (o.status=9))';
       }elseif ($status != '' && $status >0) {
           $where_data['where']['o.status'] = $status;
       }

       //支付状态
       $payment_status = $this->input->get_post('payment_status');
       if ($payment_status != '') $where_data['where']['payment_status'] = $payment_status;

       //关键字
       $keyword_where = $this->input->get_post('keyword_where');
       $keyword       = $this->input->get_post('keyword');
       if (!empty($keyword_where) && !empty($keyword)) $where_data['where'][$keyword_where] = $keyword;
       $search_where = array(
           'is_del'         => $is_del,
           'status'         => $status,
           'payment_status' => $payment_status,
           'keyword_where'  => $keyword_where,
           'keyword'        => $keyword,
       );
       //assign('search_where', $search_where);
       //搜索条件end
       $where_data['select'] = 'o.id,o.order_no,o.payment_status,o.status,o.sku_price_real,o.addtime,o.paytime,m.nickname,m.headimgurl,k.name,k.image,s.shop_name,k.start_time,k.end_time';
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
       echo json_encode($this->ResArr);exit;
   }

    /**
     * 订单详情
     */
    public function order_detail()
    {
        $post_data = $this->input->get_post(NULL);
        if (empty($post_data['id'])){
            $this->ResArr['code'] = 15;
            $this->ResArr['msg'] = '订单id不能为空';
            echo json_encode($this->ResArr);exit;
        }
        $order_data = $this->loop_model->get_where('order',array('id'=>$post_data['id']),'id,m_id,good_id,order_no,status,sku_price_real,addtime,paytime');
        if (!$order_data){
            $this->ResArr['code'] = 16;
            $this->ResArr['msg'] = '该订单不存在';
            echo json_encode($this->ResArr);exit;
        }
        $user = $this->loop_model->get_where('member_oauth',array('id'=>$order_data['m_id']),'nickname,headimgurl');
        $good = $this->loop_model->get_where('goods',array('id'=>$order_data['good_id']),'shop_id,name,image,start_time,end_time');
        $shop = $this->loop_model->get_where('member_shop',array('m_id'=>$good['shop_id']),'shop_name');
        $order_data['nickname'] = $user['nickname'];
        $order_data['headimgurl'] = $user['headimgurl'];
        $order_data['name'] = $good['name'];
        $order_data['shop_name'] = $shop['shop_name'];
        $order_data['start_time'] = $good['start_time'];
        $order_data['end_time'] = $good['end_time'];
        $this->ResArr['code'] = 200;
        $this->ResArr['data'] = $order_data;
        echo json_encode($this->ResArr);exit;
    }

    /**
     * 订单提交
     */
    public function commit()
    {
        $good_id   = $this->input->get_post('good_id');//选择的商品,
        $m_id   = $this->input->get_post('m_id');//用户,

        if (empty($good_id)) {
            $this->ResArr["code"] = 14;
            $this->ResArr["msg"]= '缺失商品id';
            echo json_encode($this->ResArr);exit;
        }
        $payment_id    = 3;//支付方式（微信支付）
        /*
        $where['where']['good_id'] = $good_id;
        $where['where']['m_id'] = $m_id;
        $where['where_in']['status'] = [2,3,4,5];
        $orderData = $this->loop_model->get_list('order',$where);
        if(count($orderData) > 0){
            $this->ResArr["code"] = 11;
            $this->ResArr["msg"]= '该商品已购买';
            echo json_encode($this->ResArr);exit;
        }
        */
        $goodData = $this->loop_model->get_where('goods',array('id'=>$good_id,'status'=>0));
        if(!$goodData){
            $this->ResArr["code"] = 12;
            $this->ResArr["msg"]= '该商品已下架';
            echo json_encode($this->ResArr);exit;
        }
        //组合订单数据
        $order_data = array(
            'm_id'                => $this->input->get_post('m_id'),
            'order_no'            => date('YmdHis') . get_rand_num('int', 6),
            'payment_id'          => $payment_id,
            'good_id'             => $good_id,
            'status'              => 1,
            'sku_price'           => price_format($goodData['market_price']),
            'sku_price_real'      => price_format($goodData['sell_price']),
            'addtime'             => time(),
            'shop_id'             => $goodData['shop_id']
        );
        //订单总价
        $order_data['order_price'] =price_format($goodData['sell_price']);
        if ($order_data['order_price'] <= 0) $order_data['order_price'] = 0;//订单少于0元的时候直接等于0元

        //查看该用户是否绑定其他用户
        /*
        $userbind = $this->loop_model->get_where('user_bind',array('bind_id'=>$this->input->get_post('m_id'),'status'=>1));
        if($userbind && time()-$userbind['addtime'] < 180*24*3600){
            //判断绑定是否过期
            $order_data['share_uid']  = $userbind['m_id'];//分享者id
        }else{
            $order_data['share_uid']  = $this->input->get_post('share_uid') ? $this->input->get_post('share_uid') : '';//分享者id
        }
        */
        $this->load->model('order/order_model');
        //添加订单商品;
        //判断是否是限时套餐
        if($goodData['cat_type'] == 2 && $goodData['type'] == 2){
            //判断是否传日期
            if(!empty($this->input->get_post('date'))){
                $res = $this->order_model->add($order_data,'');
                if(!$res){
                    $this->ResArr["code"] = 13;
                    $this->ResArr["msg"]= '生成订单失败 ';
                    echo json_encode($this->ResArr);exit;
                }
                //插入数据
                $date_insert['order_id'] = $res;
                $date_insert['date'] = $this->input->get_post('date');
                $date_insert['addtime'] = time();
                $res1 = $this->loop_model->insert('order_limit_date',$date_insert);
            }else{
                //限量商品，请选择时间
                $this->ResArr["code"] = 12;
                $this->ResArr["msg"]= '请选择购买的使用时间';
                echo json_encode($this->ResArr);exit;
            }
        }else{
            $res = $this->order_model->add($order_data,'');
            if(!$res){
                $this->ResArr["code"] = 13;
                $this->ResArr["msg"]= '生成订单失败 ';
                echo json_encode($this->ResArr);exit;
            }
        }

        //订单金额为0时，订单自动完成
        if ($order_data['order_price'] == 0) {
            $this->order_model->update_pay_status($order_data['order_no']);
        }
        $all_order_price = $order_data['order_price'];
        $order_no[]      = $order_data['order_no'];

        //是否生成返利订单
        if(isset($order_data['share_uid']) && !empty($order_data['share_uid'])){
            //插入分佣
            $sameorder = $this->loop_model->get_where('order',['m_id'=>$order_data['share_uid'],'good_id'=> $good_id,'payment_status'=>1],'','paytime desc');
            $total = $this->loop_model->get_list_num('order',['where'=>['m_id'=>$order_data['share_uid'],'good_id'=> $good_id,'payment_status'=>1]]);
            if($sameorder && $total<5){
                //返佣20%
                $rakedata['share_order_id'] = $sameorder['id'];
                $rakedata['order_id'] = $res;
                $rakedata['rake_id'] = 0;
                $rakedata['rake_price'] = $order_data['sku_price_real']*0.2;
                $rakedata['order_price'] = $order_data['sku_price_real'];
                $rakedata['rate'] = 20;
                $rakedata['addtime'] = time();
                $rakeres = $this->loop_model->insert('order_rake',$rakedata);
            }elseif($sameorder && $total>5){
                //返佣5%
                $dissameorder = $this->loop_model->get_where('order',['m_id'=>$order_data['share_uid'],'payment_status'=>1],'','paytime desc');
                $rakedata['share_order_id'] = $dissameorder['id'];
                $rakedata['order_id'] = $res;
                $rakedata['rake_id'] = 0;
                $rakedata['rake_price'] = $order_data['sku_price_real']*0.05;
                $rakedata['order_price'] = $order_data['sku_price_real'];
                $rakedata['rate'] = 5;
                $rakedata['addtime'] = time();
                $rakeres = $this->loop_model->insert('order_rake',$rakedata);
            }else{
                $dissameorder = $this->loop_model->get_where('order',['m_id'=>$order_data['share_uid'],'payment_status'=>1],'','paytime desc');
                if($dissameorder){
                    //返佣5%
                    $rakedata['share_order_id'] = $dissameorder['id'];
                    $rakedata['order_id'] = $res;
                    $rakedata['rake_id'] = 0;
                    $rakedata['rake_price'] = $order_data['sku_price_real']*0.05;
                    $rakedata['order_price'] = $order_data['sku_price_real'];
                    $rakedata['rate'] = 5;
                    $rakedata['addtime'] = time();
                    $rakeres = $this->loop_model->insert('order_rake',$rakedata);
                }
            }
        }
        //订单金额为0时，订单自动完成
        if ($all_order_price <= 0) {
            $this->ResArr["code"] = 200;
            $this->ResArr["pay"] = 1;
            $this->ResArr["msg"]= '支付成功';
            echo json_encode($this->ResArr);exit;
        } else {
            $this->ResArr["code"] = 200;
            $this->ResArr["pay"] = 0;
            $this->ResArr["data"] = $order_no;
            $this->ResArr["msg"]= '生成订单请去支付';
            echo json_encode($this->ResArr);exit;
        }
    }

    /**
     * 订单评论提交
     */
    public function comment()
    {
        if (is_post()) {
            $id = (int)$this->input->get_post('order_id', true);
            $m_id = (int)$this->input->get_post('m_id', true);
            if (empty($id)) error_json('订单ID错误');
            $order_data = $this->loop_model->get_where('order', array('id' => $id));
            if (is_comment($order_data)) {
                //$order_sku     = $this->loop_model->get_list('order_sku', array('where' => array('order_id' => $order_data['id'])));//商品列表
                $comment_level = $this->input->get_post('comment_level', true);//评价等级
                $desc          = $this->input->get_post('desc', true);//评价内容

                //开始修改订单状态
                $res = $this->loop_model->update_where('order', array('status' => 5), array('id' => $id, 'm_id' => $m_id));
                if (!empty($res)) {
                    $level_goods_num = 0;//好评数量
                    $level_bad_num   = 0;//差评数量

                    if ($comment_level == 1) $level_goods_num++;
                    if ($comment_level == 3) $level_bad_num++;
                    $comment_data = array(
                        'goods_id'     => $order_data['good_id'],
                        'shop_id'      => $order_data['shop_id'],
                        'm_id'         => $this->input->get_post('m_id'),
                        'order_id'     => $id,
                        //'order_sku_id' => $key['id'],
                        //'sku_value'    => $key['sku_value'],
                        'level'        => $comment_level,
                        'service_level'       => $this->input->get_post('service_level'),
                        'environment_level'   => $this->input->get_post('environment_level'),
                        'social_level'        => $this->input->get_post('social_level'),
                        'desc'         => $desc,
                        'addtime'      => time(),
                    );
                    $this->loop_model->insert('goods_comment', $comment_data);

                    //修改商品评论数量
                    $this->loop_model->update_id('goods', array('set' => array(array('comments', 'comments+1'))), $order_data['good_id']);
                    //判断是否有图片
                    $image_list = $this->input->get_post('image_list',true);
                    if(!empty($image_list)){
                        $image_data = [];
                        foreach($image_list as $k=>$v){
                            $image_data[$k]['order_id'] = $id;
                            $image_data[$k]['url'] = $v;
                        }
                        $image_res = $this->loop_model->insert('goods_comment_image', $image_data,true);
                    }

                    //修改店铺评价数
                    if ($level_goods_num > 0 || $level_bad_num > 0) {
                        //查询目前好评数
                        $shop_data     = $this->loop_model->get_where('member_shop', array('m_id' => $order_data['shop_id']));
                        $goods_comment = $shop_data['goods_comment'] + $level_goods_num - $level_bad_num;//计算本次修改好评数
                        if ($goods_comment < 0) $goods_comment = 0;
                        $this->load->model('member/shop_model');
                        $shop_level = $this->shop_model->shop_level($goods_comment);//店铺等级
                        $this->loop_model->update_where('member_shop', array('goods_comment' => $goods_comment, 'level' => $shop_level), array('m_id' => $order_data['shop_id']));
                    }
                    error_json('y');
                }

            } elseif ($order_data['status'] == 5) {
                error_json('订单已经评价');
            } else {
                error_json('订单还不能评价');
            }
        }
    }
}
