<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Shop extends CI_Controller
{

    private $admin_data;//后台用户登录信息

    public function __construct()
    {
        parent::__construct();
        $this->load->helpers('manager_helper');
        $this->admin_data = manager_login();
        assign('admin_data', $this->admin_data);
        $this->load->model('loop_model');
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
        $status = $this->input->post_get('status');
        if (!empty($status)) {
            $where_data['where']['s.status'] = $status;
        } else {
            $where_data['where']['s.status!='] = 1;
        }
        //用户名
        $username = $this->input->post_get('username');
        if (!empty($username)) $where_data['where']['username'] = $username;

        //店铺名
        $shop_name = $this->input->post_get('shop_name');
        if (!empty($shop_name)) $where_data['like']['shop_name'] = $shop_name;
        $search_where = array(
            'status' => $status,
            'username' => $username,
            'shop_name'=>$shop_name
        );
        assign('search_where', $search_where);
        //搜索条件end
        $where_data['join'] = array(
            array('member as m', 's.m_id=m.id')
        );
        //查到数据
        $list = $this->loop_model->get_list('member_shop as s', $where_data, $pagesize, $pagesize * ($page - 1), 'm.id desc');//列表
        assign('list', $list);
        //开始分页start
        $all_rows = $this->loop_model->get_list_num('member_shop as s', $where_data);//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        //开始分页end

        assign('status', array('0' => '正常', 1 => '删除', 2 => '锁定'));//状态
        display('/member/shop/list.html');
    }

    /**
     * 添加
     */
    public function add($m_id)
    {
        $m_id = (int)$m_id;
        if (!empty($m_id)) {
            $this->load->helpers('upload_helper');//加载上传文件插件
            $member_shop         = $this->loop_model->get_where('member_shop', array('m_id' => $m_id));
            $member_shop['m_id'] = $m_id;
            assign('item', $member_shop);
            display('/member/shop/add.html');
        } else {
            error_json('请先注册会员');
        }
    }

    /**
     * 添加收账方列表
     */
    public function add_mch($m_id){
        $m_id = (int)$m_id;
        $pagesize = 20;//分页大小
        $page     = (int)$this->input->get('per_page');
        $page <= 1 ? $page = 1 : $page = $page;//当前页数
        $where_data['where']['shop_id'] = $m_id;
        $list = $this->loop_model->get_list('merchant_detail',$where_data,$pagesize, $pagesize * ($page - 1), 'id desc');
        assign('list', $list);
        //开始分页start
        $all_rows = $this->loop_model->get_list_num('merchant_detail', $where_data);//所有数量
        assign('page_count', ceil($all_rows / $pagesize));
        //开始分页end
        assign('status', array('0' => '正常', 1 => '删除', 2 => '锁定'));//状态
        assign('shop_id', $m_id);

        display('/member/shop/add_mch.html');
    }

    /**
     * 修改收账方
     */
    public function add_mch_edit($m_id){
        $m_id = (int)$m_id;
        $id = $this->input->get('id');
        if(empty($id)){
            //添加
            assign('shop_id', $m_id);
        }else{
            //修改
            $where_data['id'] = $id;
            $detail = $this->loop_model->get_where('merchant_detail',$where_data);
            assign('detail', $detail);
            assign('shop_id', $m_id);
        }
        display('/member/shop/add_mch_edit.html');
    }

    /**
     * 保存收账房
     */
    public function add_mch_save(){
        if (is_post()) {
            $data_post = $this->input->post(NULL, true);
            $this->load->model('member/shop_model');
            $res = $this->shop_model->update_mch($data_post);
            if (!empty($res)) {
                error_json($res);
            } else {
                error_json('保存失败');
            }
        } else {
            error_json('提交方式错误');
        }
    }

    /**
     * 收账方状态改变
     */
    public function add_mch_status(){
        $data_post = $this->input->post(NULL, true);

        //获取特约商户的信息
        $detail = $this->loop_model->get_where('merchant_detail',array('id'=>$data_post['id']));
        if(!$detail){
            error_json('商家不存在');
        }
        //获取店铺详情
        $shop = $this->loop_model->get_where('member_shop',array('m_id'=>$data_post['m_id']));
        if(!$shop){
            error_json('店家不存在');
        }else{
            if(!$shop['mch_id']){
                error_json('店家商户号缺失');
            }
        }
        $this->load->library('minipay/WxPayApi');
        $this->load->library('minipay/WxPayJsApiPay');
        $this->load->library('minipay/WxPayConfig');
        $this->load->library('minipay/JsApiPay');
        /*
        $Receiver = [
            'type' => 'MERCHANT_ID',
            'account' => '1515139181',//根据商户查找商户的商户号
            'name' => '广州族迹信息技术有限公司',//商户的名称（全称呼）
            'relation_type' => 'STORE_OWNER'
        ];
        */
        $Receiver = [
            'type' => 'MERCHANT_ID',
            'account' => $detail['mch_id'],//根据商户查找商户的商户号
            'name' => $detail['name'],//商户的名称（全称呼）
            'relation_type' => 'STORE_OWNER'
        ];

        $input = new \WxPayUnifiedOrder();
        $input->SetSubMch_id($shop['m_id']);
        $input->SetReceiver(json_encode($Receiver,256|64));
        //$input->SetNotify_url("http://".$_SERVER["SERVER_NAME"]."/api_mobile/notify");
        $config = new \WxPayConfig();
        $order = \WxPayApi::addunifiedOrder($config, $input);

        if($order["return_code"]=="SUCCESS" && $order["result_code"]=="SUCCESS" ){
           /*
            $this->ResArr['code'] = 200;
            $this->ResArr['msg'] = '添加成功';
           */
            error_json('y');
        }else{
            /*
            $this->ResArr['code'] = 3;
            $this->ResArr['msg'] = $order["err_code"];
            $this->ResArr['data'] = $order["err_code_des"];
            */
            error_json($order["err_code_des"]);
        }
        //echo json_encode($this->ResArr);
    }

    /**
     * 编辑
     */
    public function edit($m_id)
    {
        $m_id = (int)$m_id;
        if (empty($m_id)) msg('id错误');
        $member_shop = $this->loop_model->get_where('member_shop', array('m_id' => $m_id));
        if(!file_exists("/uploads/ercode/qr_".$m_id.".png")){
            if(cache('get', 'access_token')){
                $access_token = cache('get', 'access_token');
            }else{
                $smallapp_appid  = config_item('miniApp_appid');//appid
                $smallapp_secret = config_item('miniApp_secret');//secret
                $gettokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$smallapp_appid."&secret=".$smallapp_secret;
                $result = curl_get($gettokenUrl);
                $info = json_decode($result,true);
                if(isset($info["errcode"])){
                    $this->ResArr['code'] = $info["errcode"];
                    $this->ResArr['msg'] = $info["errmsg"];
                    echo ch_json_encode($this->ResArr);exit;
                }else{
                    cache('save', 'access_token', $info['access_token'], time() + 7000);//保存token
                    $access_token = $info['access_token'];
                }
            }
            $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$access_token;
            $param['scene'] = 'storeId='.$m_id;
            $param['width'] = 280;
            $param['is_hyaline'] = false;
            $param['page'] = "pages/main/store/store";
            $param = json_encode($param);
            $res = curl_post($url,$param);
            file_put_contents("uploads/ercode/qr_".$m_id.".png", $res);
            $update_data['ercode'] = "/uploads/ercode/qr_".$m_id.".png";

            $this->loop_model->update_where('member_shop', $update_data, array('m_id' => $m_id));
            $member_shop['ercode'] = $update_data['ercode'];
        }
        assign('item', $member_shop);
        $this->load->helpers('upload_helper');//加载上传文件插件
        display('/member/shop/add.html');
    }

    /**
     * 保存数据
     */
    public function save()
    {
        if (is_post()) {
            $data_post = $this->input->post(NULL, true);
            $this->load->model('member/shop_model');
            $res = $this->shop_model->update($data_post);
            if (!empty($res)) {
                error_json($res);
            } else {
                error_json('保存失败');
            }
        } else {
            error_json('提交方式错误');
        }

    }

    /**
     * 删除数据到回收站
     */
    public function delete_recycle()
    {
        $id = $this->input->post('id', true);
        if (empty($id)) error_json('id错误');
        if ($id == 1 || in_array(1, $id)) {
            error_json('商城自营不允许删除');
        }
        $res = $this->loop_model->update_where('member_shop', array('status' => 1), array('where_in' => array('m_id' => $id)));
        if (!empty($res)) {
            if (is_array($id)) $id = join(',', $id);
            admin_log_insert('删除店铺到回收站' . $id);
            error_json('y');
        } else {
            error_json('删除失败');
        }
    }

    /**
     * 回收站还原
     */
    public function reduction_recycle()
    {
        $id = $this->input->post('id', true);
        if (empty($id)) error_json('id错误');
        $res = $this->loop_model->update_where('member_shop', array('status' => 0), array('where_in' => array('m_id' => $id)));
        if (!empty($res)) {
            if (is_array($id)) $id = join(',', $id);
            admin_log_insert('还原店铺' . $id);
            error_json('y');
        } else {
            error_json('还原失败');
        }
    }

    /**
     * 彻底删除数据
     */
    public function delete()
    {
        $id = $this->input->post('id', true);
        if (empty($id)) error_json('id错误');
        if ($id == 1 || in_array(1, $id)) {
            error_json('商城自营不允许删除');
        }
        $res = $this->loop_model->delete_where('member_shop', array('where_in' => array('m_id' => $id)));
        if (!empty($res)) {
            if (is_array($id)) $id = join(',', $id);
            admin_log_insert('彻底删除店铺' . $id);
            error_json('y');
        } else {
            error_json('删除失败');
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
        $res                   = $this->loop_model->update_where('member_shop', $update_data, array('where_in' => array('m_id' => $id)));
        if (!empty($res)) {
            if (is_array($id)) $id = join(',', $id);
            admin_log_insert('修改店铺status为' . $status . 'id为' . $id);
            error_json('y');
        } else {
            error_json('操作失败');
        }
    }

    /**
     * 查看店铺
     */
    public function view($m_id)
    {
        $m_id = (int)$m_id;
        if (empty($m_id)) msg('id错误');
        $member_shop = $this->loop_model->get_where('member_shop', array('m_id' => $m_id));
        assign('item', $member_shop);
        $this->load->helpers('upload_helper');//加载上传文件插件
        display('/member/shop/view.html');
    }
}
