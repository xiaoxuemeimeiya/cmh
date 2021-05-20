<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Shop extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
        $this->load->model('member/shop_model');
    }

    /**
     * 店铺列表
     */
    public function index()
    {
        $shop_name  = $this->input->get_post('shop_name', true);//店铺名称
        $city  = $this->input->get_post('city', true);//地理位置
        $area  = $this->input->get_post('area', true);//地理位置
        $page = $this->input->get_post('page', true) ? $this->input->get_post('page', true) : 1;
        $search_where = [];
        $like = [];
        if (!empty($shop_name)) {
            $search_where['shop_name'] = $shop_name;
        }
        if (!empty($city)) {
            $search_where['city'] = $city;
        }
        if (!empty($area)) {
            $search_where['area'] = $area;
        }
        $search_where['page'] = $page;
        //查询数据
        $res_data = $this->shop_model->search($search_where,'');

        $this->ResArr["code"] = 200;
        $this->ResArr["data"]['goods'] = $res_data;
        echo json_encode($this->ResArr);exit;
    }

    //店铺详情
    public function detail(){
        $shop_id  = $this->input->get_post('shop_id', true);
        if(!$shop_id){
            $this->ResArr["code"] = 3;
            $this->ResArr["msg"] = "参数缺失";
            echo json_encode($this->ResArr);exit;
        }
        $this->load->model('member/shop_model');
        $res_data = $this->shop_model->shop_detail($shop_id);
        $this->ResArr["code"] = 200;
        $this->ResArr["data"] = $res_data;
        echo json_encode($this->ResArr);exit;
    }

    //店铺商品
    public function goods()
    {
        $type  = $this->input->get_post('type', true);//type：=1优惠券，=2套餐券，=3活动
        $shop_id  = (int)$this->input->get_post('shop_id', true);//店铺id

        switch($type){
            case 2:
                //套餐券
                $data = $this->shop_model->shop_goods($type,$shop_id);
                break;
            case 3:
                //活动
                $data = $this->shop_model->shop_goods($type,$shop_id);
                break;
            default:
                //优惠券
                $data = $this->shop_model->shop_goods($type,$shop_id);
        }

        $this->ResArr["code"] = 200;
        $this->ResArr["data"]['goods'] = $data;
        echo json_encode($this->ResArr);exit;
    }


}
