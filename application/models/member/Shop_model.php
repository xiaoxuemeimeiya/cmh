<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Shop_model extends CI_Model
{

    public function __construct()
    {
        /**
         * 载入数据库类
         */
        $this->load->database();
    }

    /**
     * 更新店铺
     * @return array
     */
    public function update($data_post = array())
    {
        $update_data = array(
            'shop_name'        => $data_post['shop_name'],
            'logo'             => $data_post['logo'],
            'cove_img'         => $data_post['cove_img'],
            'tel'              => $data_post['tel'],
            'open'             => $data_post['open'],
            'per_price'        => $data_post['per_price'],
            'email'            => $data_post['email'],
            'customer_url'     => $data_post['customer_url'],
            'business_license' => $data_post['business_license'],
            'prov'             => $data_post['prov'],
            'city'             => $data_post['city'],
            'area'             => $data_post['area'],
            'address'          => $data_post['address'],
            'desc'             => $data_post['desc'],
        );

        if (empty($update_data['shop_name'])) return '店铺名称不能为空';
        if (empty($update_data['logo'])) return '店铺LOGO不能为空';
        if (empty($update_data['business_license'])) return '店铺营业执照不能为空';
        $this->load->model('loop_model');
        if (!empty($data_post['m_id'])) {
            //查询是否会员是否注册
            $member_data = $this->loop_model->get_id('member', $data_post['m_id']);
            if (!empty($member_data)) {
                $member_shop_data = $this->loop_model->get_where('member_shop', array('m_id' => $data_post['m_id']));
                if (!empty($member_shop_data)) {
                    //修改
                    $update_data['endtime'] = time();
                    $res                    = $this->loop_model->update_where('member_shop', $update_data, array('m_id' => $data_post['m_id']));
                } else {
                    //增加
                    $update_data['addtime'] = time();
                    $update_data['endtime'] = time();
                    $update_data['m_id']    = $data_post['m_id'];
                    $res                    = $this->loop_model->insert('member_shop', $update_data);
                }
                if (!empty($res)) {
                    return 'y';
                } else {
                    return '保存失败';
                }
            } else {
                return '请先注册成为会员';
            }
        }
    }
    /**
     * 更新商户
     * @return array
     */
    public function update_mch($data_post = array())
    {
        $update_data = array(
            'name'             => $data_post['name'],
            'mch_id'           => $data_post['mch_id'],
            'shop_id'          => $data_post['shop_id'],
            'status'           =>1
        );

        if (empty($update_data['name'])) return '商户名称不能为空';
        if (empty($update_data['mch_id'])) return '商户号不能为空';
        if (empty($update_data['shop_id'])) return '店铺id不能为空';
        $this->load->model('loop_model');
        if (!empty($data_post['id'])) {
            //查询是否有数据
            $member_data = $this->loop_model->get_id('merchant_detail', $data_post['id']);
            if (!empty($member_data)) {
                    $update_data['addtime'] = time();
                    $res                    = $this->loop_model->update_where('merchant_detail', $update_data, array('id' => $data_post['id']));

                if (!empty($res)) {
                    return 'y';
                } else {
                    return '保存失败';
                }
            } else {
                //增加
                $update_data['addtime'] = time();
                $res                    = $this->loop_model->insert('merchant_detail', $update_data);

                if (!empty($res)) {
                    return 'y';
                } else {
                    return '保存失败';
                }
            }
        }else{
            //增加
            $update_data['addtime'] = time();
            $res                    = $this->loop_model->insert('merchant_detail', $update_data);

            if (!empty($res)) {
                return 'y';
            } else {
                return '保存失败';
            }
        }
    }


    /**
     * 计算店铺等级
     * @param int $goods_comment 好评数
     * @return int
     */
    function shop_level($goods_comment = '')
    {
        if (!empty($goods_comment)) {
            return ceil($goods_comment / 100);
        }
        return 1;
    }

    /**
     * 店铺列表
     */

    function search($search_where,$distance){
        $this->db->from('member_shop as g');
        $select = 'm_id,shop_name,logo,cove_img,tel,email,business_license,prov,desc,goods_comment,level,banner_url,address';
        //$this->db->select('m_id,shop_name,logo,tel,email,business_license,prov,ga.area_name as city,gae.area_name as area,address,desc,goods_comment,level,banner_url');

        if($search_where['city']){
            //连接表单
            $select .= ',ga.area_name as city';
            //$this->db->join( $this->db->dbprefix('areas') ." as ga", "ga.area_id=g.city");//用编码
            //$this->db->where('g.city',$search_where['city']);
            $this->db->join( $this->db->dbprefix('areas') ." as ga", "ga.area_id=g.city","left");//用文字
            $this->db->where('ga.area_name',$search_where['city']);
        }
        if($search_where['area']){
            //连接表单
            $select .= ',gae.area_name as area';
            //$this->db->join( $this->db->dbprefix('areas') ." as gae", "gae.area_id=g.area");
            //$this->db->where('g.area',$search_where['area']);
            $this->db->join( $this->db->dbprefix('areas') ." as gae", "gae.area_id=g.area","left");
            $this->db->where('gae.area_name',$search_where['area']);

        }
        $this->db->select($select);

        if($search_where['shop_name']){
            $this->db->like('shop_name', $search_where['shop_name']);
        }

        //分页
        $page = (int)$search_where['page'];//是否有传入参数
        if (empty($page)) $page = (int)$this->input->get_post('per_page', true);//接收url分页
        if (empty($page)) $page = 1;
        if (empty($limit)) $limit = config_item('shop_list_pagesize');
        $this->db->limit($limit, $limit * ($page - 1));

        //根据位置帅选
        $query      = $this->db->get();
        $goods_data = $query->result_array();//echo $this->db->last_query()."<br>";
        //根据位置排序
        return $goods_data;
        //$this->db->order_by('sortnum', 'asc');
    }

    //计算距离
    /**
     * 计算两组经纬度坐标 之间的距离
     * params ：lat1 纬度1； lng1 经度1； lat2 纬度2； lng2 经度2； len_type （1:m or 2:km);
     * return m or km
     */
    public function GetDistance($lat1, $lng1, $lat2, $lng2, $len_type = 1, $decimal = 2)
    {
        define('EARTH_RADIUS', 6378.137);//地球半径
        define('PI', 3.1415926);//圆周率
        $radLat1 = $lat1 * PI / 180.0;
        $radLat2 = $lat2 * PI / 180.0;
        $a = $radLat1 - $radLat2;
        $b = ($lng1 * PI / 180.0) - ($lng2 * PI / 180.0);
        $s = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1) * cos($radLat2) * pow(sin($b/2),2)));
        $s = $s * EARTH_RADIUS;
        $s = round($s * 1000);
        if ($len_type > 1)
        {
            $s /= 1000;
        }
        return round($s, $decimal);
    }

    /**
     * 店铺详情
     */
    public function shop_detail($id){
        $this->db->from('member_shop as l');
        $select = 'm_id,shop_name,logo,cove_img,tel,email,business_license,g.area_name as prov,ga.area_name as city,gae.area_name as area,address,desc,goods_comment,level,banner_url,open,per_price';
        $this->db->select($select);
        $this->db->join( $this->db->dbprefix('areas') ." as g", "g.area_id=l.prov");
        $this->db->join( $this->db->dbprefix('areas') ." as ga", "ga.area_id=l.city");
        $this->db->join( $this->db->dbprefix('areas') ." as gae", "gae.area_id=l.area");

        $this->db->where('l.m_id',$id);

        $query      = $this->db->get();
        $goods_data = $query->result_array();//echo $this->db->last_query()."<br>";
        //根据位置排序
        return $goods_data;
        //$this->db->order_by('sortnum', 'asc');
    }

    /**
     * 店铺商品
     */
    public function shop_goods($type,$shop_id,$page){
        //根据type选择不同类型的商品
        switch($type){
            case 3:
                //活动(确定方案在进行)
                break;
            case 4:
                //其他商品
                break;
            default:
                //优惠券,套餐券
                if(!$type) $type = 1;
                $list = $this->db->from('goods as l');
                $select = 'id,name,sub_name,sell_price,market_price,image,store_nums,sale,start_time,end_time,type,g.desc';
                $this->db->join( $this->db->dbprefix('goods_desc') ." as g", "g.goods_id=l.id");
                $this->db->select($select);
                $this->db->where('shop_id',$shop_id);
                $this->db->where('cat_id',$type);

                //分页
                $page = (int)$page;//是否有传入参数
                if (empty($page)) $page = (int)$this->input->get_post('per_page', true);//接收url分页
                if (empty($page)) $page = 1;
                if (empty($limit)) $limit = config_item('shop_list_pagesize');
                $this->db->limit($limit, $limit * ($page - 1));

                $query      = $this->db->get();
                $goods_list = $query->result_array();//echo $this->db->last_query()."<br>";

                $goods_count = $this->db->count_all_results();
                $page_count  = ceil($goods_count / $limit);
                $reslut_array = array('goods_list' => $goods_list, 'page_count' => $page_count);
        }

        return $reslut_array;
    }

    /**
     * 店铺商品标题
     */
    public function shop_goods_cat($type,$shop_id){
        //根据type选择不同类型的商品
        switch($type){
            case 3:
                //活动(确定方案在进行)
                break;
            case 4:
                //其他商品
                break;
            default:
                //优惠券,套餐券
                if(!$type) $type = 1;
                $list = $this->db->from('goods as l');
                $select = 'id,name';
                $this->db->select($select);
                $this->db->where('shop_id',$shop_id);
                $this->db->where('cat_id',$type);
                $query      = $this->db->get();
                $goods_list = $query->result_array();//echo $this->db->last_query()."<br>";
        }
        return $goods_list;
    }
}
