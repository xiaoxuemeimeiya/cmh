<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Index extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('loop_model');
    }

    /**
     * 新版首页
     */
    public function index(){
        $type = $this->input->get_post('type', true) ? $this->input->get_post('type', true) : 1 ;//1-产品服务，2-优惠
        $this->load->model('goods/goods_model');
        if($type == 1){
            $search_where['cat_type'] = [1,2];
            //$res_data = $this->goods_model->search_index($search_where,1);
            $res_data = $this->goods_model->search_index1($search_where,1);
        }else{
            $search_where['cat_type'] = 3;
            //热门
            if(!empty($this->input->get_post('is_hot', true))){
                $search_where['is_hot'] = $this->input->get_post('is_hot', true);
                //查询数据
                $res_data = $this->goods_model->search_index($search_where,2);
            }else if(!empty($this->input->get_post('is_new', true))){
                //最新
                $search_where['is_new'] = $this->input->get_post('is_new', true);
                //查询数据
                $res_data = $this->goods_model->search_index($search_where,2);
            }else{
                $res_data = $this->goods_model->search_index($search_where,2);
            }
        }
        $this->ResArr["code"] = 200;
        $this->ResArr["data"] = $res_data;
        echo json_encode($this->ResArr);exit;

    }
    /*
    public function index(){
        $cat_id  = $this->input->get_post('cat_id', true);
        $this->load->model('goods/goods_model');
        $search_where['cat_id'] = $cat_id;
        $list = $this->goods_model->search($search_where, '');
        $this->ResArr["code"] = 200;
        $this->ResArr["data"] = $list;
        echo json_encode($this->ResArr);exit;
    }
    */

    /**
     * 广告拿出来
     */
    public function adv()
    {
        //（1-手机版首页banner，2-pc版首页banner，3-pc版首页banner下）
        $position_id  = $this->input->get_post('position_id', true) ? $this->input->get_post('position_id', true) : 1;//默认首页轮播图
        $where['where']['position_id'] = $position_id;
        $list = $this->loop_model->get_list('adv', $where);
        $this->ResArr["code"] = 200;
        $this->ResArr["data"] = $list;
        echo json_encode($this->ResArr);exit;
    }

    /**
     * 优惠券列表
     */
    public function coupon_list()
    {
        //$cat_id  = $this->input->get_post('cat_id', true);
        $cat_id = 1;//优惠券
        $page = $this->input->get_post('page', true) ? $this->input->get_post('page', true) : 1;
        if (empty($cat_id)) {
            $this->ResArr["code"] = 3;
            $this->ResArr["msg"] = "参数缺失cat_id";
            echo json_encode($this->ResArr);exit;
        }
        //搜索条件
        $search_where = array(
            //'cat_id'       => $this->input->get_post('cat_id', true),
            'cat_id'       => $cat_id,
            //'min_price'    => $this->input->get_post('min_price', true),
            //'max_price'    => $this->input->get_post('max_price', true),
            //'limit'        => (int)$this->input->get_post('limit', true),//显示数量
        );
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
        $this->ResArr["data"] = $res_data;
        echo json_encode($this->ResArr);exit;
    }

    /**
     * 产品与服务列表
     */
    public function goods_list()
    {
        $cat_id  = 3;
        $search_where['cat_id'] = $cat_id;
        $page = $this->input->get_post('page', true) ? $this->input->get_post('page', true) : 1;
        //搜索条件
        $shop_id  = $this->input->get_post('shop_id', true);
        if (empty($shop_id)) {
            $this->ResArr["code"] = 3;
            $this->ResArr["msg"] = "参数缺失$shop_id";
            echo json_encode($this->ResArr);exit;
        }
        $search_where['shop_id'] = $shop_id;
        //热门
        if(!empty($this->input->get_post('is_hot', true))){
            $search_where['is_hot'] = $this->input->get_post('is_hot', true);
        }
        //最新
        if(!empty($this->input->get_post('is_new', true))){
            $search_where['is_new'] = $this->input->get_post('is_new', true);
        }
        $search_where['page'] = $page;
        //查询数据
        $this->load->model('goods/goods_model');
        $res_data = $this->goods_model->search_service($search_where);
     
        $this->ResArr["code"] = 200;
        $this->ResArr["data"] = $res_data;
        echo json_encode($this->ResArr);exit;
    }
    /**
     * 商品详情
     */
    public function detail()
    {
        $id = $this->input->get_post('id', true);
        if(!$id){
            $this->ResArr["code"] = 3;
            $this->ResArr["msg"] = "参数缺失";
            echo json_encode($this->ResArr);exit;
        }
        $this->load->model('goods/goods_model');
        $item = $this->goods_model->get_id($id);
        if(!$item){
            $this->ResArr["code"] =17;
            $this->ResArr["msg"] = "商品不存在或者已下架";
            echo json_encode($this->ResArr);exit;
        }
        //查看用户是否购买过此商品(商品可多次购买)
        /*
        $item['is_buy'] = 0;
        $m_id = $this->input->get_post('m_id', true);
        if($m_id){
            $where_data['where']['good_id'] = $id;
            $where_data['where']['m_id'] = $m_id;
            $where_data['where']['payment_status'] = 1;//已经支付
            $where_data['where_in']['status'] = [2,3,4,5];//没有退款
            $order = $this->loop_model->get_list('order', $where_data, '', '', 'id asc');
            if($order){
                $item['is_buy'] = 1;
            }
        }
        */
        //是否是套餐券
        if($item['cat_type'] == 2 && $item['type'] == 2){
            //限量
            //$start_time = date('m月d',time());//今天
            //$item['date'][0]['day'] = $start_time;
            //$item['date'][0]['status'] = 0;//结束
            for($i=1 ; $i<=7 ; $i++){
                //$item['date'][$i-1]['day'] = date('m月d',time()+($i-1)*24*3600);
                $day = date('m月d',time()+($i-1)*24*3600);
                $date_item = [];
                $date_item['day'] = $day;
                //查看是否有选中i
                $where['year'] = date("Y",time());
                $where['goods_id'] = $id;
                $where['month'] = date('n',time()+($i-1)*24*3600);//m加0，n不加0
                $where['date'] = date('d',time()+($i-1)*24*3600);
                $isset_date = $this->loop_model->get_where('goods_date',$where); 
                if($isset_date){
                    if($isset_date['limit'] && $isset_date['limit']-$isset_date['use']>0){
                        //$item['date'][$i-1]['limit'] = $isset_date['limit'];
                        //$item['date'][$i-1]['re_limit'] = $isset_date['limit']-$isset_date['use'];
                        //$item['date'][$i-1]['status'] = 1;//可抢
                        $date_item['limit'] = $isset_date['limit'];
                        $date_item['re_limit'] = $isset_date['limit']-$isset_date['use'];
                        $date_item['status'] = 1;//可抢
                    }else{
                        //$item['date'][$i-1]['limit'] = $isset_date['limit'];
                        //$item['date'][$i-1]['re_limit'] = $isset_date['limit']-$isset_date['use'];
                        //$item['date'][$i-1]['status'] = 3;//已抢光
                        $date_item['limit'] = $isset_date['limit'];
                        $date_item['re_limit'] = $isset_date['limit']-$isset_date['use'];
                        $date_item['status'] = 3;
                    }

                }else{
                    //没有，不可抢
                    //$item['date'][$i-1]['limit'] = 0;
                    //$item['date'][$i-1]['re_limit'] = 0;
                    //$item['date'][$i-1]['status'] = 2;//不可抢
                    $date_item['limit'] = 0;
                    $date_item['re_limit'] = 0;
                    $date_item['status'] = 2;//不可抢
                    //查看是否还有数量
                }
                $item['date'][] = $date_item;
            }
        }
        $this->load->model('goods/shop_category_model');
        $detail = $this->shop_category_model->get_all_name_cat1($item['id'],0);
        $item['desc'] = $detail;
        $need_know = $this->shop_category_model->get_all_name_cat2($item['id'],0);
        $item['need_know'] = $need_know;
        $this->ResArr["code"] = 200;
        $this->ResArr["data"]= $item;
        echo json_encode($this->ResArr);exit;
    }


}
