<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Cat extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        // 响应类型
        header('Access-Control-Allow-Methods:GET, POST, PUT,DELETE');
        // 响应头设置
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        $this->load->model('loop_model');
    }

    /**
     * 分类列表
     */
    public function cat()
    {
        $where_data['select'] = 'id,name,image,reid';
        $where_data['where']['reid'] = 1;//免费
        $list_data            = $this->loop_model->get_list('goods_category', $where_data, '', '', 'sortnum asc,id asc');
        $this->ResArr["code"] = 200;
        $this->ResArr["data"] = $list_data;
        echo json_encode($this->ResArr);exit;

    }

    public function day_rate_log(){
        $this->load->helpers('wechat_helper');
        //搜索条件start
        $where_data['where']['type'] =1;
        $where_data['where']['state'] = 0;
        $where_data['where']['date'] = date("Y-m-d",time());
        //判断时间（每晚10-10：30执行）
        $starttime = strtotime(date("Y-m-d",time()))+22*60*60;
        $endtime = strtotime(date("Y-m-d",time()))+22*60*60+30*60;

        if(time() >= $starttime && time() <=$endtime ){
            //执行
            $where_data['select'] = 'sum(cash) as cash,m_id,date';
            //查到数据
            $list_data = $this->loop_model->get_group_list('cash', $where_data,'', '', 'id desc','m_id,date','');//列表
            foreach($list_data as $v){
                //根据date查找
                $date = date("Y-m-d",time());
                $res1 = $this->loop_model->update_where('cash', ['state'=>1],['date'=>$date,'type'=>1] );
                //生成提现记录
                $insertdate['m_id'] = $v['m_id'];
                $insertdate['type'] = 2;//提现
                $insertdate['addtime'] = time();
                $insertdate['date'] = $v['date'];
                $insertdate['cash'] = $v['cash'];

                $res = $this->loop_model->insert('cash',$insertdate );
                lyLog(var_export(array('data'=>$insertdate,'statue'=>$res),true) , "res" , true);
            }
        }
    }

}
