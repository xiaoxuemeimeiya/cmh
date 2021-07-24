<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Category_model extends CI_Model
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
    public function get_all($reid = 0)
    {
        $reid = (int)$reid;
        $this->db->where('reid', $reid);
        $this->db->order_by('sortnum asc,id asc');
        $query = $this->db->get('goods_category');
        $list = $query->result_array();//echo $this->db->last_query();
        foreach ($list as $key) {
            $key['down'] = self::get_all($key['id']);
            $cat_list[] = $key;
        }
        return $cat_list;
    }

    /**
     * 查询指定ID的所有下级菜单id
     * @param int $reid id
     * @return array
     */
    public function get_reid_down($reid = '')
    {
        $reid = (int)$reid;
        if (!empty($reid)) {
            $id[] = $reid;
            $this->db->where(array('reid' => $reid));
            $query = $this->db->get('goods_category');
            $reid_list = $query->result_array();
            foreach ($reid_list as $key) {
                $id[] = $key['id'];
                $down_id = $this->get_reid_down($key['id']);
                if (!empty($down_id)) {
                    $id = array_merge($id, $down_id);
                }
            }
            return $id;
        }
    }

    /**
     * 查询指定ID的顶级分类id
     * @param int $reid id
     * @return array
     */
    public function get_top_id($id = '')
    {
        $id = (int)$id;
        if (!empty($id)) {
            $query = $this->db->get_where('goods_category', array('id' => $id));
            $cat_data = $query->row_array();
            if ($cat_data['reid']!=0) {
                return self::get_top_id($cat_data['reid']);
            } else {
                return $cat_data;
            }
        }
    }

    /**
     * 后台查询所有数据
     * @return array
     */
    public function get_all_cat1($goods_id,$reid = 0)
    {
        $reid = (int)$reid;
        $this->db->where('reid', $reid);
        $this->db->where('goods_id', $goods_id);
        $this->db->order_by('sortnum asc,id asc');
        $query = $this->db->get('goods_cat1');
        $list = $query->result_array();//echo $this->db->last_query();
        foreach ($list as $key) {
            $key['down'] = self::get_all_cat1($goods_id,$key['id']);
            $cat_list[] = $key;
        }
        return $cat_list;
    }

    /**
     * 后台查询所有数据
     * @return array
     */
    public function get_all_cat2($goods_id,$reid = 0)
    {
        $reid = (int)$reid;
        $this->db->where('reid', $reid);
        $this->db->where('goods_id', $goods_id);
        $this->db->order_by('sortnum asc,id asc');
        $query = $this->db->get('goods_cat2');
        $list = $query->result_array();//echo $this->db->last_query();
        foreach ($list as $key) {
            $key['down'] = self::get_all_cat2($goods_id,$key['id']);
            $cat_list[] = $key;
        }
        return $cat_list;
    }

    /**
     * 后台查询所有数据
     * @return array
     */
    public function get_all_name_cat1($goods_id,$reid = 0)
    {
        $reid = (int)$reid;
        if($reid == 0){
            $this->db->select('id,name');
        }else{
            $this->db->select('id,name,desc,price,flag');
        }
        $this->db->where('reid', $reid);
        $this->db->where('goods_id', $goods_id);
        $this->db->order_by('sortnum asc,id asc');
        $query = $this->db->get('goods_cat1');
        $list = $query->result_array();//echo $this->db->last_query();
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
        $this->db->select('id,name');
        $this->db->where('reid', $reid);
        $this->db->where('goods_id', $goods_id);
        $this->db->order_by('sortnum asc,id asc');
        $query = $this->db->get('goods_cat2');
        $list = $query->result_array();//echo $this->db->last_query();
        foreach ($list as $key) {
            $key['down'] = self::get_all_name_cat2($goods_id,$key['id']);
            $cat_list[] = $key;
        }
        return $cat_list;
    }
}
