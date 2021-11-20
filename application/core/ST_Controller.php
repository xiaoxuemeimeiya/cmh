<?php

defined('BASEPATH') OR exit('No direct script access allowed');
use think\facade\Request;
class ST_Controller extends CI_Controller {

    private $whiteList = [
        'api_mobile/assistant/login',
    ];
	
	public function __construct()
	{
		parent::__construct();
        $this->load->model('loop_model');
        $this->validateRequest();
	}

    public function validateRequest()
    {
        header('Access-Control-Allow-Origin: *');
        // 响应类型
        header('Access-Control-Allow-Methods:GET, POST, PUT,DELETE');
        // 响应头设置
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        $formData = $_REQUEST;
        $module_name = $this->uri->segment(1);//输出--模块名称
        $class = $this->uri->segment(2);//输出--控制器名称--父
        $controller_name = $this->uri->segment(3);//输出类名称---子
        $action = $this->uri->segment(4);//输出--方法名称
        //$active_url=$module_name.'/'.$controller_name.'/'.$action;
        //$url = $formData['url'];
        $url = $module_name.'/'.$class.'/'.$controller_name;
        if($action){
            $url = $url.'/'.$action;
        }
        if (!in_array($url, $this->whiteList)) {
            /*
            if (empty($formData['m_id']) || empty($formData['timestamp']) ||
                empty($formData['sign']) || empty($formData['token'])
            ) {
                error_json('参数缺失');
            }
            */
            if (empty($formData['m_id']) || empty($formData['timestamp']) || empty($formData['token'])
            ) {
                error_json('参数缺失');
            }

            $user = $this->loop_model->get_where('member_shop_assistant',  array('id' => $formData['m_id'],'status'=>0));
            if(!$user) {
                error_json('用户不存在');
            }
            //暂时关闭token验证

            if (!$this->tokenVerify($formData['token'], $formData['m_id'])) {
                error_json('令牌失效','3');
            }

        }
        // sign verify
        /*暂时不验证签名
        if (!$this->signVerify($formData, $formData['sign'],$formData['m_id'])) {
            error_json('签名错误');
         }
        */
        return true;
    }

    public function tokenVerify($token, $m_id)
    {
        $userToken = cache('get', 'assistant_token_'.$m_id);
        if (empty($userToken)) {
            error_json('令牌失效11');
            return false;
        }
        if (!empty($userToken) && $token !== $userToken) {
            error_json('token different');
            return false;
        }
        return true;
    }

    public function signVerify($formData, $sign,$m_id)
    {
        $checkSign = self::calculateSignature($formData, cache('get', 'assistant_token_'.$m_id));
        return ($checkSign === $sign);
    }

    public static function paramsFilter($params)
    {
        $paramsFilter = array();
        while (list ($key, $value) = each ($params)) {
            if ($key == "r" || $key == "sign" || $key == "ui" || $key == "thumb"|| $value == "") {
                continue;
            } else {
                $paramsFilter[$key] = $params[$key];
            }
        }
        return $paramsFilter;
    }

    public static function paramsSort($params)
    {
        ksort($params);
        reset($params);
        return $params;
    }

    public static function createLinkString($params)
    {
        $string = "";
        while (list ($key, $val) = each($params)) {
            $string .= $key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $string = substr($string, 0, count($string) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $string = stripslashes($string);
        }

        return $string;
    }

    public static function calculateSignature($formData, $salt)
    {
        $filterParams = self::paramsFilter($formData);
        $sortParams = self::paramsSort($filterParams);
        $prepareParams = self::createLinkString($sortParams);
        return md5($prepareParams . $formData['timestamp'] . $salt);
    }

}