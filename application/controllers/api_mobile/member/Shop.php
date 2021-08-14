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

    /**
     * 店铺优惠券/套餐顶部标题
     *
     */
    public function goods_cat(){
        $type  = $this->input->get_post('type', true);//type：=1优惠券，=2套餐券，=3活动
        $shop_id  = (int)$this->input->get_post('shop_id', true);//店铺id
        if(!$shop_id){
            $this->ResArr["code"] = 3;
            $this->ResArr["msg"] = "参数缺失";
            echo json_encode($this->ResArr);exit;
        }
        switch($type){
            case 2:
                //套餐券
                $data = $this->shop_model->shop_goods_cat($type,$shop_id);
                break;
            case 3:
                //活动
                $data = $this->shop_model->shop_goods_cat($type,$shop_id);
                break;
            default:
                //优惠券
                $data = $this->shop_model->shop_goods_cat($type,$shop_id);
        }
        $this->ResArr["code"] = 200;
        $this->ResArr["data"] = $data;
        echo json_encode($this->ResArr);exit;
    }

    //店铺优惠券/套餐
    public function goods()
    {
        $page = $this->input->get_post('page', true) ? $this->input->get_post('page', true) : 1;
        $type  = $this->input->get_post('type', true);//type：=1优惠券，=2套餐券，=3活动
        $shop_id  = (int)$this->input->get_post('shop_id', true);//店铺id
        if(!$shop_id){
            $this->ResArr["code"] = 3;
            $this->ResArr["msg"] = "参数缺失";
            echo json_encode($this->ResArr);exit;
        }
        switch($type){
            case 2:
                //套餐券
                $data = $this->shop_model->shop_goods($type,$shop_id,$page);
                foreach($data['goods_list'] as $k=>$v){
                    if($v['type'] == 2){
                        //限量
                        for($i=1 ; $i<=7 ; $i++){
                            $day = date('m月d',time()+($i-1)*24*3600);
                            $date_item = [];
                            $date_item['day'] = $day;
                            //查看是否有选中i
                            $where['year'] = date("Y",time());
                            $where['goods_id'] = $v['id'];
                            $where['month'] = date('n',time()+($i-1)*24*3600);//m加0，n不加0
                            $where['date'] = date('d',time()+($i-1)*24*3600);
                            $isset_date = $this->loop_model->get_where('goods_date',$where);
                            if($isset_date){
                                if($isset_date['limit'] && $isset_date['limit']-$isset_date['use']>0){
                                    $date_item['limit'] = $isset_date['limit'];
                                    $date_item['re_limit'] = $isset_date['limit']-$isset_date['use'];
                                    $date_item['status'] = 1;//可抢
                                }else{
                                    $date_item['limit'] = $isset_date['limit'];
                                    $date_item['re_limit'] = $isset_date['limit']-$isset_date['use'];
                                    $date_item['status'] = 3;
                                }

                            }else{
                                $date_item['limit'] = 0;
                                $date_item['re_limit'] = 0;
                                $date_item['status'] = 2;//不可抢
                            }
                            $data['goods_list'][$k]['date'][] = $date_item;
                        }
                    }
                }
                break;
            case 3:
                //活动
                $data = $this->shop_model->shop_goods($type,$shop_id,$page);
                break;
            default:
                //优惠券
                $data = $this->shop_model->shop_goods($type,$shop_id,$page);
        }
        $this->ResArr["code"] = 200;
        $this->ResArr["data"] = $data;
        echo json_encode($this->ResArr);exit;
    }


}
