<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
use Home\Controller\AppResult;
class AuthController extends Controller {
    public function __construct(){
        parent::__construct();
        header("Content-type: text/html; charset=utf-8"); 
        $checkLogin = $this->checkLogin();
        if(!$checkLogin){
            $redirect ="/home/login/index";
            $this->error('未登陆，请登陆！', $redirect);
            die;
        }
        $this->assign("admininfo",session("admininfo"));
         
    }
    public function index(){
        
    }
    public function checkLogin()
    {
        $admin = session("admininfo");
        if(empty($admin)){
            return false;
        }
        return true;
    }
    
    public function getParamEmptyDieTip($name, $dieReason = "") {
        $value = $this->trimEmptyStr($this->params[$name]);
        if ($value === null || $value === "") {
            $value = $this->trimEmptyStr($_GET[$name]);
        }
        if ($value === null || $value === "") {
            $value = $this->trimEmptyStr($_POST[$name]);
        }
        if ($value === null || $value === "") {
            $appResult = new AppResult();
            $appResult->code = 1;
            if (empty($dieReason)) {
                $dieReason = '参数' . $name . '不能为空！';
            }
            $appResult->message = $dieReason;
            $appResult->returnJSON();
        }
        
        return $value;
    }
   
    /**
     * 返回json结果
     *
     * @param
     *            $code
     * @param
     *            $message
     * @param string $data
     */
    public function returnJSON($code = 1, $message = '操作出错!', $data = '') {
        $appResult = new AppResult();
        $appResult->code = $code;
        $appResult->message = $message;
        $appResult->data = $data;
        $appResult->returnJSON();
    }
    /*
     * @autohr lzm
     * @intro 获取对应键值参数，若空设为默认值
     */
    public function getParam($name, $value_while_empty = "") {
        $value = $this->trimEmptyStr($this->params[$name]);
        if ('goods' == $name) {
            $value = $_GET[$name];
            if ($value === null || $value === "")
                $value = $_POST[$name];
        }
        if ($value === null || $value === "") {
            $value = $this->trimEmptyStr($_GET[$name]);
            if ($value === null || $value === "") {
                $value = $this->trimEmptyStr($_POST[$name]);
            }
            if ($value === null || $value === "") {
                $value = $value_while_empty;
            }
        }
        return $value;
    }
    /**
     * 过滤参数
     *
     * @param unknown $val
     * @return return_type
     * @author lzm
     * @date 2018年2月22日
     */
    public function trimEmptyStr($val) {
        $cleanVal = '';
        if (is_array($val)) {
            $cleanVal = array_map('trim', $val);
            $cleanVal = array_map('htmlspecialchars', $cleanVal);
        }
        else if (is_string($val)) {
            $cleanVal = trim($val);
            $cleanVal = htmlspecialchars($cleanVal);
        }
        else {
            $cleanVal = $val;
        }
        return $cleanVal;
    }
}