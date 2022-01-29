<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Pay extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
        $this->load->helpers('wechat_helper');
    }

    /**
     * 支付
     */
    public function index()
    {
        $order_no    = $this->input->get_post('order_no');//订单号,多个之间用,隔开
        if (empty($order_no)) {
            $this->ResArr['code'] = 3;
            $this->ResArr['msg'] = '参数缺失';
            echo ch_json_encode($this->ResArr);exit;
        }
        $order_price  = 0;
        $order_data = $this->loop_model->get_where('order', array('order_no' => $order_no, 'status' => 1));
        if (!empty($order_data)) {
            $order_no_data = $order_no;
            $order_price     = $order_price + $order_data['order_price'];//支付金额
        } else {
            $this->ResArr['code'] = 101;
            $this->ResArr['msg'] = '订单信息错误,或者订单已支付';
            echo ch_json_encode($this->ResArr);exit;
        }
        $payment_id      = 3;//微信支付
        $pay_data = array(
            'order_body'  => '订单支付',
            'order_no'    => $order_no,//支付单号
            'order_price' => $order_price > 0 ? $order_price : 10 ,//支付金额(默认为支付0.1)
            'payment_id'  => $payment_id,//支付方式
        );
        $user = $this->loop_model->get_where('member_oauth',array('id'=>$order_data['m_id']));
        $openid = $user['openid'];
        $this->load->library('minipay/WxPayApi');
        $this->load->library('minipay/WxPayJsApiPay');
        $this->load->library('minipay/WxPayConfig');
        $this->load->library('minipay/JsApiPay');
        //require_once Env::get('ROOT_PATH').'extend/minipay/WxPay.Api.php';
        //require_once Env::get('ROOT_PATH').'extend/minipay/WxPay.JsApiPay.php';
        //require_once Env::get('ROOT_PATH').'extend/minipay/WxPay.Config.php';
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($pay_data["order_body"]);
        $input->SetAttach("");
        $input->SetOut_trade_no($pay_data['order_no']);
        $input->SetTotal_fee($pay_data['order_price']);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $shop_data = $this->loop_model->get_where('member_shop', array('m_id' => $order_data['shop_id'], 'status' => 0));
        $input->SetSubMch_id($shop_data['mch_id']);
        $input->SetReceipt("Y");
        $input->SetProfit_sharing("Y");//是否分账
        $input->SetGoods_tag($pay_data["order_body"]);
        //$input->SetNotify_url("https://".$_SERVER["SERVER_NAME"]."/miniapp/notify");
        $input->SetNotify_url("http://".$_SERVER["SERVER_NAME"]."/api_mobile/notify");
        //$input->SetNotify_url("https://".$_SERVER["SERVER_NAME"]."/miniapp/notify");
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openid);
        $config = new \WxPayConfig();
        $order = \WxPayApi::unifiedOrder($config, $input);
        $tools = new \JsApiPay();
        $jsApiParameters = $tools->GetJsApiParameters($order);
        if($order["return_code"]=="SUCCESS"){
            //lyLog(var_export($order,true) , "oncourse" , true);
            $this->ResArr['code'] = 200;
            $this->ResArr['data'] = json_decode($jsApiParameters,true);
        }else{
            $this->ResArr['code'] = 3;
            $this->ResArr['msg'] = "pay data error!";
        }
        echo ch_json_encode($this->ResArr);
    }

    /**
     * 订单或充值支付客户端回调
     */
    public function callback($payment_id, $client = '')
    {
        $payment_data = $this->loop_model->get_where('payment', array('id' => $payment_id, 'status' => 0));

        if (!empty($payment_data)) {
            //开始支付回调处理
            $patment_class_name = $payment_data['class_name'];
			$this->load->library($patment_class_name);
            //$this->load->library('payment/' . $patment_class_name . '/' . $patment_class_name);
            $pay_res = $this->$patment_class_name->callback();
            if ($pay_res['status'] == 'y') {
                error_json('支付成功');
            } else {
                error_json($pay_res['info']);
            }
        } else {
            error_json('支付方式错误');
        }
    }

    /**
     * 订单或这个充值支付服务端回调
     */
    public function server_callback($payment_id)
    {
        $payment_data = $this->loop_model->get_where('payment', array('id' => $payment_id, 'status' => 0));

        if (!empty($payment_data)) {
            //开始支付回调处理
            $patment_class_name = $payment_data['class_name'];
			$this->load->library($patment_class_name);
            //$this->load->library('payment/' . $patment_class_name . '/' . $patment_class_name);
            $pay_res = $this->$patment_class_name->server_callback();
            if ($pay_res['status'] == 'y') {
                //充值方式
                if (stripos($pay_res['order_no'], 'on') !== false) {
                    $order_no_data = explode('on', $pay_res['order_no']);
                    $recharge_no   = isset($order_no_data[1]) ? $order_no_data[1] : 0;
                    $this->load->model('member/user_online_recharge');
                    $res = $this->user_online_recharge->update_pay_status($recharge_no);
                    if ($res == 'y') {
                        $this->loop_model->update_where('member_user_online_recharge', array('payment_no' => $pay_res['transaction_id']), array('recharge_no' => $recharge_no));//保存交易流水号
                        $this->$patment_class_name->success();
                    } else {
                        $this->$patment_class_name->error();
                    }
                } else {
                    $order_no = cache('get', $pay_res['order_no']);//取的订单缓存
                    //订单付款
                    foreach ($order_no as $key) {
                        $this->load->model('order/order_model');
                        $order_res = $this->order_model->update_pay_status($key);
                        if ($order_res == 'y') {
                            if (!empty($pay_res['transaction_id'])) {
                                $this->loop_model->update_where('order', array('payment_no' => $pay_res['transaction_id']), array('order_no' => $key));//保存交易流水号
                            }
                        }
                    }
                    if ($order_res == 'y') {
                        $this->$patment_class_name->success();
                    } else {
                        $this->$patment_class_name->error();
                    }
                }
            } else {
                $this->$patment_class_name->error();
            }
        } else {
            echo '支付方式错误';
        }
    }

    /**
     * 添加分账方（添加特约商户的时候需要先调用该接口）
     */
    public function add_mch(){
        $order_no    = $this->input->get_post('order_no');//订单号,多个之间用,隔开
        if (empty($order_no)) {
            $this->ResArr['code'] = 3;
            $this->ResArr['msg'] = '参数缺失';
            echo ch_json_encode($this->ResArr);exit;
        }
        //获取特约商户的信息

        $this->load->library('minipay/WxPayApi');
        $this->load->library('minipay/WxPayJsApiPay');
        $this->load->library('minipay/WxPayConfig');
        $this->load->library('minipay/JsApiPay');
        $Receiver = [
            'type' => 'MERCHANT_ID',
            'account' => '1515139181',//根据商户查找商户的商户号
            'name' => '广州族迹信息技术有限公司',//商户的名称（全称呼）
            'relation_type' => 'STORE_OWNER'
        ];

        $input = new \WxPayUnifiedOrder();
        $order_data = $this->loop_model->get_where('order', array('order_no' => $order_no));
        $shop_data = $this->loop_model->get_where('member_shop', array('m_id' => $order_data['shop_id']));
        $input->SetSubMch_id($shop_data['mch_id']);
        //$input->SetSubMch_id('1608890757');
        $input->SetReceiver(json_encode($Receiver,256|64));
        //$input->SetNotify_url("http://".$_SERVER["SERVER_NAME"]."/api_mobile/notify");
        $config = new \WxPayConfig();
        $order = \WxPayApi::addunifiedOrder($config, $input);
        if($order["return_code"]=="SUCCESS" && $order["result_code"]=="SUCCESS" ){
            //lyLog(var_export($order,true) , "oncourse" , true);
            $this->ResArr['code'] = 200;
            $this->ResArr['msg'] = '添加成功';
        }else{
            $this->ResArr['code'] = 3;
            $this->ResArr['msg'] = $order["err_code"];
            $this->ResArr['data'] = $order["err_code_des"];
        }
        echo ch_json_encode($this->ResArr);
    }
    /**分账**/
    public function sub_pay(){
        $order_no    = $this->input->get_post('order_no');//订单号,多个之间用,隔开
        if (empty($order_no)) {
            $this->ResArr['code'] = 3;
            $this->ResArr['msg'] = '参数缺失';
            echo ch_json_encode($this->ResArr);exit;
        }
        $order_data = $this->loop_model->get_where('order', array('order_no' => $order_no, 'status' => 2));

        $this->load->library('minipay/WxPayApi');
        $this->load->library('minipay/WxPayJsApiPay');
        $this->load->library('minipay/WxPayConfig');
        $this->load->library('minipay/JsApiPay');
        $Receivers = [
            'type' => 'MERCHANT_ID',
            'account' => '1515139181',
            'amount' =>10,
            'description' => '分账'
        ];
        $input = new \WxPayUnifiedOrder();
        $input->SetTransaction_id($order_data['payment_no']);
        $input->SetOut_order_no($order_data['order_no']);
        $shop_data = $this->loop_model->get_where('member_shop', array('m_id' => $order_data['shop_id']));
        $input->SetSubMch_id($shop_data['mch_id']);
        //$input->SetSubMch_id('1608890757');
        $input->SetReceivers(json_encode($Receivers,256|64));
        //$input->SetNotify_url("http://".$_SERVER["SERVER_NAME"]."/api_mobile/notify");
        $config = new \WxPayConfig();
        $order = \WxPayApi::subunifiedOrder($config, $input);

        if($order["return_code"]=="SUCCESS"){
            //lyLog(var_export($order,true) , "oncourse" , true);
            $this->ResArr['code'] = 200;

        }else{
            $this->ResArr['code'] = 3;
            $this->ResArr['msg'] = "pay data error!";
        }
        echo ch_json_encode($this->ResArr);
    }

    /**查询订单待分账金额*/
    public function re_pay(){
        $order_no    = $this->input->get_post('order_no');//订单号,多个之间用,隔开
        if (empty($order_no)) {
            $this->ResArr['code'] = 3;
            $this->ResArr['msg'] = '参数缺失';
            echo ch_json_encode($this->ResArr);exit;
        }
        $order_data = $this->loop_model->get_where('order', array('order_no' => $order_no, 'status' => 2));

        $this->load->library('minipay/WxPayApi');
        $this->load->library('minipay/WxPayJsApiPay');
        $this->load->library('minipay/WxPayConfig');
        $this->load->library('minipay/JsApiPay');


        $input = new \WxPayUnifiedOrder();
        $input->SetTransaction_id($order_data['payment_no']);
        //$input->SetNotify_url("http://".$_SERVER["SERVER_NAME"]."/api_mobile/notify");
        $config = new \WxPayConfig();
        $order = \WxPayApi::reunifiedOrder($config, $input);

        if($order["return_code"]=="SUCCESS"){
            //lyLog(var_export($order,true) , "oncourse" , true);
            $this->ResArr['code'] = 200;

        }else{
            $this->ResArr['code'] = 3;
            $this->ResArr['msg'] = "pay data error!";
        }
        echo ch_json_encode($this->ResArr);
    }

    /**完结分账*/
    public function finish_pay(){
        $order_no    = $this->input->get_post('order_no');//订单号,多个之间用,隔开
        if (empty($order_no)) {
            $this->ResArr['code'] = 3;
            $this->ResArr['msg'] = '参数缺失';
            echo ch_json_encode($this->ResArr);exit;
        }
        $order_data = $this->loop_model->get_where('order', array('order_no' => $order_no, 'status' => 2));
        $this->load->library('minipay/WxPayApi');
        $this->load->library('minipay/WxPayJsApiPay');
        $this->load->library('minipay/WxPayConfig');
        $this->load->library('minipay/JsApiPay');


        $input = new \WxPayUnifiedOrder();
        $input->SetTransaction_id($order_data['payment_no']);
        $input->SetOut_order_no($order_data['order_no']);
        $input->SetDescription ('分账已完结');
        $input->SetSubMch_id('1608890757');
        //$input->SetNotify_url("http://".$_SERVER["SERVER_NAME"]."/api_mobile/notify");
        $config = new \WxPayConfig();
        $order = \WxPayApi::finishunifiedOrder($config, $input);

        if($order["return_code"]=="SUCCESS"){
            //lyLog(var_export($order,true) , "oncourse" , true);
            $this->ResArr['code'] = 200;

        }else{
            $this->ResArr['code'] = 3;
            $this->ResArr['msg'] = "pay data error!";
        }
        echo ch_json_encode($this->ResArr);
    }

    //回退金额
    public function refund(){
        $order_no    = $this->input->get_post('order_no');//订单号,多个之间用,隔开
        if (empty($order_no)) {
            $this->ResArr['code'] = 3;
            $this->ResArr['msg'] = '参数缺失';
            echo ch_json_encode($this->ResArr);exit;
        }
        $order_data = $this->loop_model->get_where('order', array('order_no' => $order_no, 'status' => 2));
        //未支付或者已付款之外的其他状态
        if(!$order_data){
            $this->ResArr['code'] = 4;
            $this->ResArr['msg'] = '订单错误';
            echo ch_json_encode($this->ResArr);exit;
        }
        //获取商家
        $shop_data = $this->loop_model->get_where('member_shop', array('m_id' => $order_data['shop_id'], 'status' => 0));
        if(!$shop_data){
            $this->ResArr['code'] = 4;
            $this->ResArr['msg'] = '正在处理中';
            echo ch_json_encode($this->ResArr);exit;
        }
        $total_fee = $order_data['order_price'];
        //require_once Env::get('ROOT_PATH') . 'extend/minipay/WxPay.Api.php';
        //require_once Env::get('ROOT_PATH') . 'extend/minipay/WxPay.JsApiPay.php';
        //require_once Env::get('ROOT_PATH') . 'extend/minipay/WxPay.Config.php';

        $this->load->library('minipay/WxPayApi');
        $this->load->library('minipay/WxPayJsApiPay');
        $this->load->library('minipay/WxPayConfig');
        $this->load->library('minipay/JsApiPay');
        //$out_trade_no = time().getRandChar(18);
        $out_refund_no = time() . get_rand_num('int', 10);
        $input = new \WxPayRefund();
        //$input->SetBody("退款申请");
        //$input->SetAttach("在线提问");
        $input->SetOut_trade_no($order_data['order_no']);
        $input->SetOut_refund_no($out_refund_no);
        $input->SetTotal_fee($total_fee);
        $input->SetRefund_fee($total_fee);
        $input->SetSubMch_id($shop_data['mch_id']);
        $config = new \WxPayConfig();
        $input->SetNotify_url("http://".$_SERVER["SERVER_NAME"]."/api_mobile/notify");
        $refundOrder = \WxPayApi::subrefund($config, $input);
        if ($refundOrder["return_code"] == "SUCCESS" && $refundOrder['result_code'] == 'SUCCESS') {
            lyLog(var_export($refundOrder, true), "refund", true);
            $UpdataWhere['id'] = $order_data["id"];
            $updateData['state'] = 6;//状态改为退款
            $updateData['refund_time'] = time();
            $updateData['refund_success_time'] = time();
            $res = $this->loop_model->update_where('order',$updateData, $UpdataWhere);

            $add['openid'] = $order_data['openid'];
            $add['order_id'] = $order_data['id'];
            //$add['money'] = $order['total_fee'] / 100;
            $add['money'] = $order_data['wx_account'] / 100;
            $add['addtime'] = time();
            $res1 = $this->loop_model->insert('refund',$add);
            $this->ResArr['code'] = "1";
            $this->ResArr['msg'] = "退款成功";
            echo ch_json_encode($this->ResArr);exit;
        } else {
            //退款失败
            //原因
            $reason = (empty($refundOrder['err_code_des']) ? $refundOrder['return_msg'] : $refundOrder['err_code_des']);
            $this->ResArr['code'] = "2";
            $this->ResArr['msg'] = $reason;
            echo ch_json_encode($this->ResArr);exit;
        }
    }

    //退款
    /*
    public function refund(){
        $order_no    = $this->input->get_post('order_no');//订单号,多个之间用,隔开
        if (empty($order_no)) {
            $this->ResArr['code'] = 3;
            $this->ResArr['msg'] = '参数缺失';
            echo ch_json_encode($this->ResArr);exit;
        }
        $order_data = $this->loop_model->get_where('order', array('order_no' => $order_no, 'status' => 2));
        //未支付或者已付款之外的其他状态
        if(!$order_data){
            $this->ResArr['code'] = 4;
            $this->ResArr['msg'] = '订单错误';
            echo ch_json_encode($this->ResArr);exit;
        }
        //获取商家
        $shop_data = $this->loop_model->get_where('member_shop', array('m_id' => $order_data['shop_id'], 'status' => 0));
        if(!$shop_data){
            $this->ResArr['code'] = 4;
            $this->ResArr['msg'] = '正在处理中';
            echo ch_json_encode($this->ResArr);exit;
        }
        $total_fee = $order_data['order_price'];
        //require_once Env::get('ROOT_PATH') . 'extend/minipay/WxPay.Api.php';
        //require_once Env::get('ROOT_PATH') . 'extend/minipay/WxPay.JsApiPay.php';
        //require_once Env::get('ROOT_PATH') . 'extend/minipay/WxPay.Config.php';
        $this->load->library('minipay/WxPayApi');
        $this->load->library('minipay/WxPayJsApiPay');
        $this->load->library('minipay/WxPayConfig');
        $this->load->library('minipay/JsApiPay');
        //$out_trade_no = time().getRandChar(18);
        $out_refund_no = time() . getRandChar(18);
        $input = new \WxPayRefund();
        //$input->SetBody("退款申请");
        //$input->SetAttach("在线提问");
        $input->SetOut_trade_no($order_data['order_no']);
        $input->SetOut_refund_no($out_refund_no);
        $input->SetTotal_fee($total_fee);
        $input->SetRefund_fee($total_fee);
        $config = new \WxPayConfig();
        $input->SetOp_user_id($config->GetMerchantId($order_data['MerchantId']));
        $config->GetMerchantId($order_data['mch_id']);
        $config->GetKey($order_data['key']);
        if ($_SERVER["SERVER_NAME"] == "devuser.mylyh.com") {
            $input->SetNotify_url("http://" . $_SERVER["SERVER_NAME"] . "/miniapp/renotify");
        } else {
            $input->SetNotify_url("https://" . $_SERVER["SERVER_NAME"] . "/miniapp/renotify");
        }
        $refundOrder = \WxPayApi::refund($config, $input);
            if ($refundOrder["return_code"] == "SUCCESS" && $refundOrder['result_code'] == 'SUCCESS') {
                lyLog(var_export($refundOrder, true), "refund", true);
                $UpdataWhere['id'] = $order_data["id"];
                $updateData['state'] = 5;//状态改为退款
                $updateData['refund_time'] = time();
                $updateData['refund_success_time'] = time();
                $res = Db::table('order')->where($UpdataWhere)->update($updateData);

                $add['openid'] = $order_data['openid'];
                $add['order_id'] = $order_data['id'];
                //$add['money'] = $order['total_fee'] / 100;
                $add['money'] = $order_data['wx_account'] / 100;
                $add['addtime'] = time();
                $res1 = Db::table('refund')->insert($add);
                $this->ResArr['code'] = "1";
                $this->ResArr['msg'] = "退款成功";
                return json($this->ResArr);
                //给用户退款

            } else if (($refundOrder['return_code'] == 'FAIL') || ($refundOrder['result_code'] == 'FAIL')) {
                //退款失败
                //原因
                $reason = (empty($refundOrder['err_code_des']) ? $refundOrder['return_msg'] : $refundOrder['err_code_des']);
                $this->ResArr['code'] = "2";
                $this->ResArr['msg'] = $reason;
            } else {
                $this->ResArr['code'] = "2";
                $this->ResArr['msg'] = "pay data error!";
            }
    }
    */
    public function notify_cash (){
        $postStr = file_get_contents("php://input");

        lyLog(var_export($postStr,true) , "notify_cash111" , true);
        $orderData = isset($postStr)? $postStr : '';
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($orderData,'simpleXMLElement',LIBXML_NOCDATA)),true);
        lyLog(var_export($data,true) , "notify_cash" , true);
    }
}
