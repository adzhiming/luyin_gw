<?php
namespace Home\Controller;
use Think\Controller;
class AuthController extends Controller {
    public function __construct(){
        parent::__construct();
        $checkLogin = $this->checkLogin();
        if(!$checkLogin){
            $redirect ="/home/login/index";
            $this->error('未登陆，请登陆！', $redirect);
            die;
        }
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
}