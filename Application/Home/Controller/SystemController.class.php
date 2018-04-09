<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
use Think\Log;

class SystemController extends AuthController {
    public function _initialize() {
        $this->assign("SystemSub",1);
    }
    
    public function index(){
        $this->display();
    }
    
    public function channelParameter(){
        $this->assign("CurrentPage",'channel');
        $this->display();
    }
    
    public function systemParameter(){
        $this->assign("CurrentPage",'system');
        $this->display();
    }
    
    public function licenseInfo(){
        $this->assign("CurrentPage",'license');
        $this->display();
    }
    
    public function diskParameter(){
        $this->assign("CurrentPage",'disk');
        $this->display();
    }
    
    public function ipLimint(){
        $this->assign("CurrentPage",'ip');
        $this->display();
    }
}