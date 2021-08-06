<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Upload extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 上传
     */
    public function index()
    {
        //根据请求之地址判断是否登录
        $m_id = $this->input->get_post('m_id',true);
        //只有登录用户存在的时候才能上传
        if (!empty($m_id)) {
            $file_name = $this->input->get_post('file_name', true);//上传文件的文本域名称
            if (empty($file_name)) $file_name = 'file_name';
            //$width       = (int)$this->input->get_post('width', true);//裁剪的宽度
            //$height      = (int)$this->input->get_post('height', true);//裁剪的宽度
            //$crop        = (int)$this->input->get_post('crop', true);//裁剪的宽度
            //$orientation = (int)$this->input->get_post('orientation', true);//图片方向
            $this->load->model('upload_model');
            $res = $this->upload_model->comment_upload($file_name, $width, $height, $crop, $orientation);
            if(isset($res['status']) && $res['status'] == 'success' ){
                $this->ResArr['code'] = 200;
                $this->ResArr['msg'] = '添加成功';
            }else{
                $this->ResArr['code'] = 15;
                $this->ResArr['msg'] = $res['error'];
            }
            //echo json_encode($res);
            echo json_encode($this->ResArr);exit;
        }
    }
}
