<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Shop extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
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
        //根据地址来排序


        $search_where['page'] = $page;
        //查询数据
        $this->load->model('member/shop_model');
        $res_data = $this->shop_model->search($search_where,'');

        $this->ResArr["code"] = 200;
        $this->ResArr["data"]['goods'] = $res_data;
        echo json_encode($this->ResArr);exit;
    }

    //店铺商品
    public function goods()
    {
        $shop_id  = $this->input->get_post('shop_id', true);
        $shop_name  = $this->input->get_post('shop_name', true);
        $page = $this->input->get_post('page', true) ? $this->input->get_post('page', true) : 1;
        if (!empty($shop_id)) {
            $where['shop_id'] = $shop_id;
        }
        if (!empty($shop_name)) {
            $where['shop_name'] = $shop_name;
        }
        if (!empty($shop_name)) {
            $where['shop_name'] = $shop_name;
        }
        //搜索条件
        /*
        $search_where = array(
            'cat_id'       => $this->input->get_post('cat_id', true),
        );
        */
        //属性条件
        $attr = $this->input->get_post('attr', true);
        if (!empty($attr)) {
            foreach ($attr as $v => $k) {
                $search_where['attr'][$v] = $k;
            }
        }
        $search_where['page'] = $page;
        //查询数据
        $this->load->model('goods/goods_model');
        $res_data = $this->goods_model->search($search_where, '');

        $this->ResArr["code"] = 200;
        $this->ResArr["data"]['goods'] = $res_data;
        echo json_encode($this->ResArr);exit;
    }

    /**
     * 删除收藏商品
     */
    public function delete_favorite()
    {
        if (is_post()) {
            $id = $this->input->get_post('id', true);
            if (!empty($id)) {
                if (is_array($id)) {
                    $res = $this->loop_model->delete_where('member_shop_favorite', array('where_in' => array('id' => $id), 'where' => array('m_id' => $this->member_data['id'])));
                } else {
                    $id = (int)$id;
                    $res = $this->loop_model->delete_where('member_shop_favorite', array('where' => array('m_id' => $this->member_data['id'], 'id' => $id)));
                }
                if (!empty($res)) {
                    error_json('y');
                } else {
                    error_json('取消收藏失败');
                }
            }
        }
    }

}
