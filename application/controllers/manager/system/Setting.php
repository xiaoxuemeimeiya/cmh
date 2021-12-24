<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Setting extends CI_Controller
{

    private $admin_data;//后台用户登录信息

    public function __construct()
    {
        parent::__construct();
        $this->load->helpers('manager_helper');
        $this->admin_data = manager_login();
        assign('admin_data', $this->admin_data);
        $this->load->model('loop_model');
        //$this->shop_id = $this->shop_data['id'];
        $this->shop_id = 1;//管理后台默认为1
    }

    /**
     * 编辑
     */
    public function index()
    {
        $member_shop               = $this->loop_model->get_where('member_shop', array('m_id' => $this->shop_id));
        $member_shop['banner_url'] = json_decode($member_shop['banner_url'], true);
        if(!file_exists("/uploads/ercode/qr_".$member_shop['m_id'].".png")){
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
                    cache('save', 'access_token', $info['access_token'],  7000);//保存token
                    $access_token = $info['access_token'];
                }
            }
            $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$access_token;
            //$param['scene'] = 'shop_id='.$member_shop['m_id'];
            $param['scene'] = $member_shop['m_id'];
            $param['width'] = 280;
            $param['is_hyaline'] = false;
            $param['page'] = "pages/main/store/store";
            $param = json_encode($param);
            $res = curl_post($url,$param);
            file_put_contents("uploads/ercode/qr_".$member_shop['m_id'].".png", $res);
            $update_data['ercode'] = "/uploads/ercode/qr_".$member_shop['m_id'].".png";

            $this->loop_model->update_where('member_shop', $update_data, array('m_id' => $member_shop['m_id']));
            $member_shop['ercode'] = $update_data['ercode'];
        }
        assign('item', $member_shop);
        $this->load->helpers('upload_helper');//加载上传文件插件
        display('/system/setting/index.html');
    }

    /**
     * 保存数据
     */
    public function save()
    {
        if (is_post()) {
            $data_post        = $this->input->post(NULL, true);
            $member_shop_data = $this->loop_model->get_where('member_shop', array('m_id' => $this->shop_id));
            //banner
            if (!empty($data_post['banner_name'])) {
                foreach ($data_post['banner_name'] as $v => $k) {
                    $banner_url[] = array(
                        'name' => $k,
                        'link' => $data_post['banner_link'][$v],
                        'url'  => $data_post['banner_url'][$v],
                    );
                }
            }

            $update_data = array(
                'shop_name'    => $data_post['shop_name'],
                'logo'         => $data_post['logo'],
                'tel'          => $data_post['tel'],
                'email'        => $data_post['email'],
                'customer_url' => $data_post['customer_url'],
                'cove_img'     => $data_post['cove_img'],
                'prov'         => $data_post['prov'],
                'city'         => $data_post['city'],
                'area'         => $data_post['area'],
                'address'      => $data_post['address'],
                'desc'         => $data_post['desc'],
                'per_price'    => $data_post['per_price'],
                'open'         => $data_post['open'],
                'banner_url'   => json_encode($banner_url),
                'business_license' => $data_post['business_license']
            );

            //if (!empty($data_post['business_license']) && $member_shop_data['status'] == 2) $update_data['business_license'] = $data_post['business_license'];

            $res = $this->loop_model->update_where('member_shop', $update_data, array('m_id' => $this->shop_id));
            if (!empty($res)) {
                error_json('y');
            } else {
                error_json('保存失败');
            }
        } else {
            error_json('提交方式错误');
        }

    }
}
