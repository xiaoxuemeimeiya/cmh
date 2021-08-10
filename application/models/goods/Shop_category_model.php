<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Shop_category_model extends CI_Model
{

    public function __construct()
    {
        /**
         * 载入数据库类
         */
        $this->load->database();
    }

    /**
     * 后台查询所有数据
     * @return array
     */
    public function get_all($shop_id, $reid = 0)
    {
        $reid = (int)$reid;
        $this->db->where('reid', $reid);
        $this->db->where('shop_id', $shop_id);
        $this->db->order_by('sortnum asc,id asc');
        $query = $this->db->get('goods_shop_category');
        $list = $query->result_array();//echo $this->db->last_query();
        foreach ($list as $key) {
            $key['down'] = self::get_all($shop_id, $key['id']);
            $cat_list[] = $key;
        }
        return $cat_list;
    }

    /**
     * 查询指定ID的所有下级菜单id
     * @param int $reid id
     * @return array
     */
    public function get_reid_down($shop_id, $reid = '')
    {
        $reid = (int)$reid;
        if (!empty($reid)) {
            $this->db->where(array('reid' => $reid));
            $this->db->where('shop_id', $shop_id);
            $query = $this->db->get('goods_shop_category');
            $reid_list = $query->result_array();
            foreach ($reid_list as $key) {
                $id[] = $key['id'];
                $down_id = $this->get_reid_down($shop_id, $key['id']);
                if (!empty($down_id)) {
                    $id = array_merge($id, $down_id);
                }
            }
            return $id;
        }
    }

    /**
     * 后台查询所有数据
     * @return array
     */
    public function get_all_cat1($shop_id, $reid = 0,$goods_id)
    {
        $reid = (int)$reid;
        $this->db->from('goods_shop_cat1 a');
        if($reid>0){
            $this->db->select('b.name as reid_name,a.id,a.name,a.desc,a.price,a.flag,a.image');
            $this->db->where('a.reid !=', 0);
            $this->db->where('a.shop_id', $shop_id);
            $this->db->where('a.goods_id', $goods_id);
            $this->db->join('goods_shop_cat1 b','a.reid=b.id','left');
        }else{
            $this->db->select('a.id,a.name,a.desc,a.price,a.flag');
            $this->db->where('a.reid', $reid);
            $this->db->where('a.shop_id', $shop_id); 
        }
        $this->db->order_by('a.sortnum asc,a.id asc');
        $query = $this->db->get();
        $list = $query->result_array();//echo $this->db->last_query();
        return $list;
    }

    /**
     * 后台查询所有数据
     * @return array
     */
    public function get_all_cat2($shop_id, $reid = 0,$goods_id)
    {
        $reid = (int)$reid;
        $this->db->from('goods_shop_cat2 a');
        if($reid>0){
            $this->db->select('b.name as reid_name,a.id,a.name,a.flag');
            $this->db->where('a.reid !=', 0);
            $this->db->where('a.shop_id', $shop_id);
            $this->db->where('a.goods_id', $goods_id);
            $this->db->join('goods_shop_cat2 b','a.reid=b.id','left');
        }else{
            $this->db->select('a.id,a.name,a.flag');
            $this->db->where('a.reid', $reid);
            $this->db->where('a.shop_id', $shop_id);
        }
        $this->db->order_by('a.sortnum asc,a.id asc');
        $query = $this->db->get();
        $list = $query->result_array();//echo $this->db->last_query();

        return $list;
    }

    /**
     * 后台查询所有数据
     * @return array
     */
    public function get_all_name_cat1($goods_id,$reid = 0)
    {
        $reid = (int)$reid;
        if($reid == 0){
            $this->db->from('goods_shop_cat1 a');
            $this->db->select('a.id,a.name');
            $this->db->where('a.reid', $reid);
            $this->db->where('b.goods_id', $goods_id);
            $this->db->join('goods_shop_cat1 b','a.id=b.reid','left');
            $this->db->order_by('a.sortnum asc,a.id asc');
            $query = $this->db->get();
            $list = $query->result_array();//echo $this->db->last_query();
        }else{
            $this->db->select('id,name,desc,price,flag');
            $this->db->where('reid', $reid);
            $this->db->where('goods_id', $goods_id);
            $this->db->order_by('sortnum asc,id asc');
            $query = $this->db->get('goods_shop_cat1');
            $list = $query->result_array();//echo $this->db->last_query();
        }

        foreach ($list as $key) {
            $key['down'] = self::get_all_name_cat1($goods_id,$key['id']);
            $cat_list[] = $key;
        }
        return $cat_list;
    }

    /**
     * 后台查询所有数据
     * @return array
     */
    public function get_all_name_cat2($goods_id,$reid = 0)
    {
        $reid = (int)$reid;
        if($reid == 0){
            $this->db->from('goods_shop_cat2 a');
            $this->db->select('a.id,a.name');
            $this->db->where('a.reid', $reid);
            $this->db->where('b.goods_id', $goods_id);
            $this->db->order_by('a.sortnum asc,a.id asc');
            $query = $this->db->get('goods_shop_cat2');
            $list = $query->result_array();//echo $this->db->last_query();
        }else{
            $this->db->select('id,name,desc,price,flag');
            $this->db->where('reid', $reid);
            $this->db->where('goods_id', $goods_id);
            $this->db->order_by('sortnum asc,id asc');
            $query = $this->db->get('goods_shop_cat2');
            $list = $query->result_array();//echo $this->db->last_query();
        }

        foreach ($list as $key) {
            $key['down'] = self::get_all_name_cat2($goods_id,$key['id']);
            $cat_list[] = $key;
        }
        return $cat_list;
    }
}
