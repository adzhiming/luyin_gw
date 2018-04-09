<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
use Think\Log;
use Think\Upload;
class AccountController extends AuthController {
    public function _initialize() {
        $this->assign("AccountSub",1);
    }
    public function index(){
        $this->display();
    }
    
    public function userManage(){
       
        $this->assign("CurrentPage",'userManage');
        $this->display();
    }
    
    public function phoneBook(){
        $this->assign("CurrentPage",'phoneBook');
        $this->display();
    }
    
    public function userAdd(){
        
        $this->assign("CurrentPage",'userManage');
        $this->display();
    }
    public function userEdit(){
        
        $this->assign("CurrentPage",'userManage');
        $this->display();
    }
    public function phoneBookAdd(){
        
        $this->assign("CurrentPage",'phoneBook');
        $this->display();
    }
    
    public function phoneBookEdit(){
        
        $this->assign("CurrentPage",'phoneBook');
        $this->display();
    }
    
    public function departMent(){
        $this->assign("CurrentPage",'departMent');
        $this->display();
    }
    
    public function departMentAdd(){
        $this->assign("CurrentPage",'departMent');
        $this->display();
    }
    
    public function departMentEdit(){
        $this->assign("CurrentPage",'departMent');
        $this->display();
    }
    
} 