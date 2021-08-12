<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Category extends CI_Controller
{

    private $admin_data;//后台用户登录信息

    public function __construct()
    {
        parent::__construct();
        $this->load->helpers('manager_helper');
        $this->admin_data = manager_login();
        assign('admin_data', $this->admin_data);
        $this->load->model('loop_model');
        $this->shop_id = 1;//系统默认1
    }

    /**
     * 列表
     */
    public function index()
    {
        //查到数据
        $this->load->model('goods/category_model');
        $list = $this->category_model->get_all();//列表
        assign('list', $list);//print_r($list);
        display('/goods/category/list.html');
    }

    /**
     * 添加
     */
    public function add($reid = 0)
    {
        $reid = (int)$reid;
        assign('reid', $reid);
        $this->load->helpers('upload_helper');//加载上传文件插件
        display('/goods/category/add.html');
    }

    /**
     * 编辑
     */
    public function edit($id)
    {
        $id = (int)$id;
        if (!empty($id)) {
            $item = $this->loop_model->get_id('goods_category', $id);
            assign('item', $item);
        }

        $this->load->helpers('upload_helper');//加载上传文件插件
        display('/goods/category/add.html');
    }

    /**
     * 保存数据
     */
    public function save()
    {
        if (is_post()) {
            $data_post   = $this->input->post(NULL, true);
            $update_data = array(
                'name'    => $data_post['name'],
                'sortnum' => (int)$data_post['sortnum'],
                'reid'    => $data_post['reid'] != '' ? $data_post['reid'] : 0,
                'image'   => $data_post['image'],
                'flag'    => $data_post['flag'],
            );

            if (empty($update_data['name'])) {
                error_json('分类名称不能为空');
            }
            if (!empty($data_post['id'])) {
                //修改数据
                $res = $this->loop_model->update_id('goods_category', $update_data, $data_post['id']);
                admin_log_insert('修改分类' . $data_post['id']);
            } else {
                //增加数据
                $res = $this->loop_model->insert('goods_category', $update_data);
                admin_log_insert('增加分类' . $res);
            }
            if (!empty($res)) {
                error_json('y');
            } else {
                error_json('保存失败');
            }
        } else {
            error_json('提交方式错误');
        }

    }

    /**
     * 删除数据
     */
    public function delete()
    {
        $id = (int)$this->input->post('id', true);
        if (empty($id)) error_json('id不能为空');
        $re_item = $this->loop_model->get_where('goods_category', array('reid' => $id));
        if (!empty($re_item)) {
            error_json('下级栏目不为空不能删除');
        } else {
            $res = $this->loop_model->delete_id('goods_category', $id);
            if (!empty($res)) {
                admin_log_insert('删除分类' . $id);
                error_json('y');
            } else {
                error_json('删除失败');
            }
        }
    }

    /**
     * 列表
     */
    public function list1()
    {
        //查到数据
        $this->load->model('goods/shop_category_model');
        $list = $this->shop_category_model->get_all_cat1($this->shop_id,0,'');//列表
        assign('list', $list);//print_r($list);
        display('/goods/category/list1.html');
    }

    /**
     * 添加
     */
    public function add1($reid = 0)
    {
        $reid = (int)$reid;
        assign('reid', $reid);
        $this->load->helpers('upload_helper');//加载上传文件插件
        display('/goods/category/add1.html');
    }

    /**
     * 编辑
     */
    public function edit1($id)
    {
        $id = (int)$id;
        if (!empty($id)) {
            $item = $this->loop_model->get_where('goods_shop_cat1', array('id' => $id, 'shop_id' => $this->shop_id));
            assign('item', $item);
        }

        $this->load->helpers('upload_helper');//加载上传文件插件
        display('/goods/category/add1.html');
    }

    /**
     * 保存数据
     */
    public function save1()
    {
        if (is_post()) {
            $data_post   = $this->input->post(NULL, true);
            $update_data = array(
                'name'    => $data_post['name'],
                'sortnum' => (int)$data_post['sortnum'],
                'reid'    => $data_post['reid'] != '' ? $data_post['reid'] : 0,
                'image'   => $data_post['image'],
                'flag'    => $data_post['flag'],
            );

            if (empty($update_data['name'])) {
                error_json('分类名称不能为空');
            }
            if (!empty($data_post['id'])) {
                //修改数据
                $res = $this->loop_model->update_where('goods_shop_cat1', $update_data, array('id' => $data_post['id'], 'shop_id' => $this->shop_id));
                admin_log_insert('修改分类' . $data_post['id']);
            } else {
                //增加数据
                $update_data['shop_id'] = $this->shop_id;
                $res                    = $this->loop_model->insert('goods_shop_cat1', $update_data);
                admin_log_insert('增加分类' . $res);
            }
            if (!empty($res)) {
                error_json('y');
            } else {
                error_json('保存失败');
            }
        } else {
            error_json('提交方式错误');
        }

    }

    /**
     * 删除数据
     */
    public function delete1()
    {
        $id = (int)$this->input->post('id', true);
        if (empty($id)) error_json('id不能为空');
        $re_item = $this->loop_model->get_where('goods_shop_cat1', array('reid' => $id, 'shop_id' => $this->shop_id));
        if (!empty($re_item)) {
            error_json('下级栏目不为空不能删除');
        } else {
            $res = $this->loop_model->delete_where('goods_shop_cat1', array('id' => $id, 'shop_id' => $this->shop_id));
            if (!empty($res)) {
                admin_log_insert('删除分类' . $id);
                error_json('y');
            } else {
                error_json('删除失败');
            }
        }
    }

    /**
     * 列表
     */
    public function list2()
    {
        //查到数据
        $this->load->model('goods/shop_category_model');
        $list = $this->shop_category_model->get_all_cat2($this->shop_id,0,'');//列表
        assign('list', $list);//print_r($list);
        display('/goods/category/list2.html');
    }

    /**
     * 添加
     */
    public function add2($reid = 0)
    {
        $reid = (int)$reid;
        assign('reid', $reid);
        $this->load->helpers('upload_helper');//加载上传文件插件
        display('/goods/category/add2.html');
    }

    /**
     * 编辑
     */
    public function edit2($id)
    {
        $id = (int)$id;
        if (!empty($id)) {
            $item = $this->loop_model->get_where('goods_shop_cat2', array('id' => $id, 'shop_id' => $this->shop_id));
            assign('item', $item);
        }

        $this->load->helpers('upload_helper');//加载上传文件插件
        display('/goods/category/add2.html');
    }

    /**
     * 保存数据
     */
    public function save2()
    {
        if (is_post()) {
            $data_post   = $this->input->post(NULL, true);
            $update_data = array(
                'name'    => $data_post['name'],
                'sortnum' => (int)$data_post['sortnum'],
                'reid'    => $data_post['reid'] != '' ? $data_post['reid'] : 0,
                'image'   => $data_post['image'],
                'flag'    => $data_post['flag'],
            );

            if (empty($update_data['name'])) {
                error_json('分类名称不能为空');
            }
            if (!empty($data_post['id'])) {
                //修改数据
                $res = $this->loop_model->update_where('goods_shop_cat2', $update_data, array('id' => $data_post['id'], 'shop_id' => $this->shop_id));
                admin_log_insert('修改分类' . $data_post['id']);
            } else {
                //增加数据
                $update_data['shop_id'] = $this->shop_id;
                $res                    = $this->loop_model->insert('goods_shop_cat2', $update_data);
                admin_log_insert('增加分类' . $res);
            }
            if (!empty($res)) {
                error_json('y');
            } else {
                error_json('保存失败');
            }
        } else {
            error_json('提交方式错误');
        }

    }

    /**
     * 删除数据
     */
    public function delete2()
    {
        $id = (int)$this->input->post('id', true);
        if (empty($id)) error_json('id不能为空');
        $re_item = $this->loop_model->get_where('goods_shop_cat2', array('reid' => $id, 'shop_id' => $this->shop_id));
        if (!empty($re_item)) {
            error_json('下级栏目不为空不能删除');
        } else {
            $res = $this->loop_model->delete_where('goods_shop_cat2', array('id' => $id, 'shop_id' => $this->shop_id));
            if (!empty($res)) {
                admin_log_insert('删除分类' . $id);
                error_json('y');
            } else {
                error_json('删除失败');
            }
        }
    }

    /****
     * 以下是商品的详情
     */

    /**
     * 列表
     */
    public function goods_list1($goods_id)
    {
        //查到数据
        $shop_id = $this->input->get_post('shop_id');
        //查到数据
        $this->load->model('goods/shop_category_model');
        $list = $this->shop_category_model->get_all_cat1($shop_id,1,$goods_id);//列表
        assign('list', $list);
        assign('shop_id', $shop_id);
        assign('goods_id', $goods_id);
        display('/goods/category/good_list1.html');
    }

    /**
     * 添加
     */
    public function goods_add1($reid = 0)
    {
        $goods_id = $this->input->get_post('goods_id');
        $shop_id = $this->input->get_post('shop_id');
        $reid = (int)$reid;
        assign('reid', $reid);
        assign('goods_id', $goods_id);
        assign('shop_id', $shop_id);
        $this->load->model('goods/shop_category_model');
        $top_list = $this->shop_category_model->get_all_cat1($shop_id,0,'');//列表
        assign('top_list', $top_list);
        $this->load->helpers('upload_helper');//加载上传文件插件
        display('/goods/category/good_add1.html');
    }

    /**
     * 编辑
     */
    public function goods_edit1($id)
    {
        $shop_id = $this->input->get_post('shop_id');
        $id = (int)$id;
        if (!empty($id)) {
            $item = $this->loop_model->get_where('goods_shop_cat1', array('id' => $id, 'shop_id' => $shop_id));
            assign('item', $item);
            assign('reid', $item['reid']);
        }

        $this->load->model('goods/shop_category_model');
        $top_list = $this->shop_category_model->get_all_cat1($shop_id,0,'');//列表
        assign('top_list', $top_list);
        
        assign('goods_id', $id);
        assign('shop_id', $shop_id);
        $this->load->helpers('upload_helper');//加载上传文件插件
        display('/goods/category/good_add1.html');
    }

    /**
     * 保存数据
     */
    public function goods_save1()
    {
        if (is_post()) {
            $data_post   = $this->input->post(NULL, true);
            $update_data = array(
                'name'    => $data_post['name'],
                'desc'    => $data_post['desc'],
                'price'    => $data_post['price'],
                'flag'    => $data_post['flag'],
                'sortnum' => (int)$data_post['sortnum'],
                'reid'    => $data_post['reid'] != '' ? $data_post['reid'] : 0,
                'image'   => $data_post['image'],
                'goods_id'   => $data_post['goods_id'],
            );

            if (empty($update_data['name'])) {
                error_json('分类名称不能为空');
            }
            if (!empty($data_post['id'])) {
                //修改数据
                $res = $this->loop_model->update_where('goods_shop_cat1', $update_data, array('id' => $data_post['id'], 'shop_id' => $data_post['shop_id']));
                admin_log_insert('修改分类' . $data_post['id']);
            } else {
                //增加数据
                $update_data['shop_id'] = $data_post['shop_id'];
                $res                    = $this->loop_model->insert('goods_shop_cat1', $update_data);
                admin_log_insert('增加分类' . $res);
            }
            if (!empty($res)) {
                error_json('y');
            } else {
                error_json('保存失败');
            }
        } else {
            error_json('提交方式错误');
        }

    }

    /**
     * 删除数据
     */
    public function goods_delete1()
    {
        $id = (int)$this->input->post('id', true);
        if (empty($id)) error_json('id不能为空');
        $re_item = $this->loop_model->get_where('goods_shop_cat1', array('reid' => $id, 'shop_id' => $this->shop_id));
        if (!empty($re_item)) {
            error_json('下级栏目不为空不能删除');
        } else {
            $res = $this->loop_model->delete_where('goods_shop_cat1', array('id' => $id, 'shop_id' => $this->shop_id));
            if (!empty($res)) {
                admin_log_insert('删除分类' . $id);
                error_json('y');
            } else {
                error_json('删除失败');
            }
        }
    }



    /**
     * 列表
     */
    public function good_list2($goods_id)
    {
        //查到数据
        $this->load->model('goods/category_model');
        $list = $this->category_model->get_all_cat2($goods_id);//列表
        assign('list', $list);//print_r($list);
        assign('goods_id', $goods_id);
        display('/goods/category/list2.html');
    }

    /**
     * 添加
     */
    public function goods_add2($reid = 0)
    {
        $goods_id = $this->input->get_post('goods_id');
        $reid = (int)$reid;
        assign('reid', $reid);
        assign('goods_id', $goods_id);
        $this->load->helpers('upload_helper');//加载上传文件插件
        display('/goods/category/add2.html');
    }

    /**
     * 编辑
     */
    public function goods_edit2($id)
    {
        $id = (int)$id;
        if (!empty($id)) {
            $item = $this->loop_model->get_id('goods_cat2', $id);
            assign('item', $item);
        }

        $this->load->helpers('upload_helper');//加载上传文件插件
        display('/goods/category/add2.html');
    }

    /**
     * 保存数据
     */
    public function goods_save2()
    {
        if (is_post()) {
            $data_post   = $this->input->post(NULL, true);
            $update_data = array(
                'name'    => $data_post['name'],
                'sortnum' => (int)$data_post['sortnum'],
                'reid'    => $data_post['reid'] != '' ? $data_post['reid'] : 0,
                'image'   => $data_post['image'],
                'goods_id'=> $data_post['goods_id'],
                'flag'    => $data_post['flag'],
            );

            if (empty($update_data['name'])) {
                error_json('分类名称不能为空');
            }
            if (!empty($data_post['id'])) {
                //修改数据
                $res = $this->loop_model->update_id('goods_cat2', $update_data, $data_post['id']);
                admin_log_insert('修改分类' . $data_post['id']);
            } else {
                //增加数据
                $res = $this->loop_model->insert('goods_cat2', $update_data);
                admin_log_insert('增加分类' . $res);
            }
            if (!empty($res)) {
                error_json('y');
            } else {
                error_json('保存失败');
            }
        } else {
            error_json('提交方式错误');
        }

    }

    /**
     * 删除数据
     */
    public function goods_delete2()
    {
        $id = (int)$this->input->post('id', true);
        if (empty($id)) error_json('id不能为空');
        $re_item = $this->loop_model->get_where('goods_cat2', array('reid' => $id));
        if (!empty($re_item)) {
            error_json('下级栏目不为空不能删除');
        } else {
            $res = $this->loop_model->delete_id('goods_cat2', $id);
            if (!empty($res)) {
                admin_log_insert('删除分类' . $id);
                error_json('y');
            } else {
                error_json('删除失败');
            }
        }
    }

}
