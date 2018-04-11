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
        $this->getParam();
        
        $this->display();
    }
    
    public function userManage(){
        
        $sEcho = $_REQUEST['sEcho']; // DataTables 用来生成的信息
        $output['sEcho'] = $sEcho;
        $start = isset($_REQUEST['start'])?$_REQUEST['start']:0;
        $length = isset($_REQUEST['length'])?$_REQUEST['length']:10;
        if(IS_AJAX){ 
            $field="v_accountid,v_accountname,v_password,n_afairtype,n_privilege,n_status,n_monitor,v_remark,v_ext,v_sid";
            $where="  n_status!=0 and V_AccountName<>'op' and  V_AccountName<>'neuron' ";
            $M = M('sys_accountinfo');
            $cnt = $M->field($field)->where($where)->select();
            //echo M()->getLastSql();
            $total = count($cnt);
            $rs = $M->field($field)->where($where)->limit($start,$length)->select();
            
            $output['aaData'] = $rs;
            $output['iTotalDisplayRecords'] = $total;    //如果有全局搜索，搜索出来的个数
            $output['iTotalRecords'] = $total; //总共有几条数据
            echo json_encode($output); //最后把数据以json格式返回
            die;
        }
        
        $this->assign("CurrentPage",'userManage');
        $this->display();
    }
    
    public function phoneBook(){
        if(IS_AJAX){
            $start = isset($_REQUEST['start'])?$_REQUEST['start']:0;
            $length = isset($_REQUEST['length'])?$_REQUEST['length']:10;
            $sName = $this->getParam("sName");
            $sNum = $this->getParam("sNum");
            $sName = MySQLFixup($sName);
            $sNum = MySQLFixup($sNum);
            $where="1=1";
            if($sName!=""){
                $where.=" and a.contactname like '%".$sName."%' ";
            }
            if($sNum!=""){
                $where.="and (a.Mobile like '%".$sNum."%' or a.OfficeNum like '%".$sNum."%' or a.OtherNum like '%".$sNum."%')";
            }
            
            $field="a.*,b.deptName";
            $cnt = M('sys_accountinfo a')->where($where)->select();
            
            $total = count($cnt);
            $rs = M('phonebook a')->join("tab_dept b on a.deptid=b.deptid","left")->field($field)->where($where)->limit($start,$length)->select();
            //echo M()->getLastSql();die;
            $output['aaData'] = $rs;
            $output['iTotalDisplayRecords'] = $total;    //如果有全局搜索，搜索出来的个数
            $output['iTotalRecords'] = $total; //总共有几条数据
            echo json_encode($output); //最后把数据以json格式返回
            die;
        }
        
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
        if(IS_AJAX){
            $start = isset($_REQUEST['start'])?$_REQUEST['start']:0;
            $length = isset($_REQUEST['length'])?$_REQUEST['length']:10;
            $sName = $this->getParam("sName");
            $sName = MySQLFixup($sName);
            $where="1=1";
            if($sName!=""){
                $where =" and deptName like '%".MySQLFixup($sName)."%' ";
            }
            
            $field="deptid,deptname"; 
            $cnt = M('dept')->where($where)->select();
            $total = count($cnt);
            
            $rs = M('dept')->field($field)->where($where)->limit($start,$length)->select();
           
            $output['aaData'] = $rs;
            $output['iTotalDisplayRecords'] = $total;    //如果有全局搜索，搜索出来的个数
            $output['iTotalRecords'] = $total; //总共有几条数据
            echo json_encode($output); //最后把数据以json格式返回
            die;
        }
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