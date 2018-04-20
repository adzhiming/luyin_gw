<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
use Think\Log;
use Think\Upload;
use Home\Controller\AppResult;
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
            $where="  n_status != 0 and V_AccountName <> 'op' and  V_AccountName <> 'neuron' ";
            $M = M('sys_accountinfo');
            $cnt = $M->field($field)->where($where)->select();
            //echo M()->getLastSql();
            $total = count($cnt);
            $rs = $M->field($field)->where($where)->limit($start,$length)->select();
            if($rs){
                foreach ($rs as $k=>$v){
                    $Managestr = "";
                    $accountstr = "";
                    $accountidstr = "";
                    $u=$v["v_accountname"];
                    $uid=$v["v_accountid"];
                    $rs[$k]['outcando'] = "<font color='red'>".OutCanDo($v['n_afairtype'])."</font>";
                    $rs[$k]['out_ext'] = Out_ext($v["v_ext"]);
                    $rs[$k]['out_site'] =  station($v["v_sid"]);;
                    $canMng  = substr($_SESSION['uRights'],4,1);//管理权
                    if($canMng==1){
                        $accountidstr = "<a href='userEdit?ACID=$uid'>$uid</a>";
                        $accountstr ="<a href='userEdit?ACID=$uid'>$u</a>";
                        $Managestr .= " <a href='userEdit?ACID=$uid'>编辑</a>　|　";
                        if($u !="admin"){
                            $Managestr .= "<a href=\"javascript:del($uid,'{$u}')\">删除</a>";
                        }else{
                            $Managestr .= "<span style=\"text-decoration:line-through;\">删除</span>";
                        }
                    }else{
                        $accountidstr = $uid;
                        $accountstr = $u;
                        $Managestr .= " <span style=\"text-decoration:line-through;\">编辑</span>　|　";
                        $Managestr .= "<span style=\"text-decoration:line-through;\">删除</span>";
                    }
                    $rs[$k]['out_accountid'] = $accountidstr;
                    $rs[$k]['out_accountname'] = $accountstr;
                    $rs[$k]['out_manage'] = $Managestr;
                    
                }
            }
           
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
            $where="1=1 ";
            if($sName !=""){
                $where.=" and a.contactname like '%".$sName."%' ";
            }
            if($sNum !=""){
                $where.=" and (a.Mobile like '%".$sNum."%' or a.OfficeNum like '%".$sNum."%' or a.OtherNum like '%".$sNum."%')";
            }
            
            $field="a.*,b.deptName";
            $cnt = M('phonebook a')->field("count(*) cnt ")->where($where)->find();
            $total = $cnt['cnt'];
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
        if(IS_POST){
            $AppResult = new AppResult();
            $username = $this->getParam("username");
            $password = $this->getParam("password");
            $canItems = $this->getParam("canItems");
            $privilege = $this->getParam("privilege");
            $stationid = $this->getParam("stationid");
            $remark = $this->getParam("remark");
            $has = M('sys_accountinfo')->where("n_status != 0 and V_AccountName = '".MySQLFixup($username)."'")->find();
            
            if($has){
                $AppResult->code = 0;
                $AppResult->message = "新增用户失败,系统已存在用户".$username."！";
                $AppResult->data = "";
                
            }else{
                $data = array();
                $data['V_AccountName'] = $username;
                $data['V_Password'] = $password;
                $data['N_AfairType'] = $canItems;
                $data['N_Privilege'] = 9;
                $data['N_Status'] = 1;
                $data['V_Remark'] = $remark;
                $data['V_Ext'] = formatSTR($privilege);
                $data['V_SID'] = $stationid;
                
                $rs = M('sys_accountinfo')->add($data);
                if($rs){
                    addSysLog("通过浏览器新增用户:".MySQLFixup($username));
                    $AppResult->code = 1;
                    $AppResult->message = "成功添加新用户:".$username;
                    $AppResult->data = "";
                }
                else{
                    $AppResult->code = 0;
                    $AppResult->message = "新增用户失败,数据库操作失败";
                    $AppResult->data = "";
                }
            }
            $AppResult->returnJSON();
        }
        $rs = M('station')->field("N_SID,V_SNAME,V_RNUM,V_MEMO")->order('V_SNAME')->select();
         
        $this->assign("station",$rs);
        $this->assign("CurrentPage",'userManage');
        $this->display();
    }
    public function userEdit(){
        if(IS_POST){
            $AppResult = new AppResult();
            $accountid = $this->getParam("accountid");
            $username = $this->getParam("username");
            $password = $this->getParam("password");
            $canItems = $this->getParam("canItems");
            $privilege = $this->getParam("privilege");
            $stationid = $this->getParam("stationid");
            $remark = $this->getParam("remark");
            
            $data = array();
            $data['V_AccountName'] = $username;
            $data['V_Password'] = $password;
            $data['N_AfairType'] = str_replace(",","",str_replace("，",",", $canItems));;
            $data['N_Privilege'] = 9;
            $data['N_Status'] = 1;
            $data['V_Remark'] = $remark;
            $data['V_Ext'] = formatSTR($privilege);
            $data['V_SID'] = $stationid;
            if(empty($accountid)){
                $AppResult->code = 0;
                $AppResult->message = "请选择用户更新";
                $AppResult->data = "";
                $AppResult->returnJSON();
            }
           
            $rs = M('sys_accountinfo')->where("v_accountid = '{$accountid}'")->save($data);
            if(false !== $rs){
                addSysLog("通过浏览器修改用户:".MySQLFixup($username));
                $AppResult->code = 1;
                $AppResult->message = "成功修改用户:".$username;
                $AppResult->data = "";
            }
            else{
                $AppResult->code = 0;
                $AppResult->message = "修改用户失败,数据库操作失败";
                $AppResult->data = "";
            }
            $AppResult->returnJSON();
        }
        $accountid = $this->getParam('ACID');
        $rs = M('sys_accountinfo')->where(" v_accountid = '".$accountid."'")->find();
        if(!$rs){
            JS_alert("非法操作");
        }
        $stationArr = explode(',', $rs['v_sid']);
        $rs['chaxun'] = substr($rs['n_afairtype'],0,1);
        $rs['shanchu'] = substr($rs['n_afairtype'],1,1);
        $rs['guanli'] = substr($rs['n_afairtype'],2,1);
        $rs['suoding'] = substr($rs['n_afairtype'],3,1);
       
        $rs_station = M('station')->field("N_SID,V_SNAME,V_RNUM,V_MEMO")->order('V_SNAME')->select();
        foreach ($rs_station as $k=>$v){
            $rs_station[$k]['selected'] = 0;
            if(in_array($v['n_sid'], $stationArr)){
                $rs_station[$k]['selected'] = 1;
            }
            
        }
        $this->assign("station",$rs_station);
        $this->assign("accountInfo",$rs);
        $this->assign("CurrentPage",'userManage');
        $this->display();
    }
    
    public function userDel(){
        if(IS_POST){
            $AppResult = new AppResult();
            $userID = $this->getParam("uid");
            $username = $this->getParam("username");
            
            if(empty($userID)){
                $AppResult->code = 0;
                $AppResult->message = "请选中要删除的用户";
                $AppResult->data = "";
            }
            $data = array();
            $data['N_Status'] = 0;
            $rs = M('sys_accountinfo')->where("V_AccountId= '{$userID}'")->save($data);
            if(false !== $rs){
                $msg="成功删除用户:".$username;
                addSysLog($msg);
                $AppResult->code = 1;
                $AppResult->message = "删除成功";
                $AppResult->data = "";
            }
            $AppResult->returnJSON();
        }
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
            $where=" 1=1 ";
            if($sName!=""){
                $where .=" and DeptName like '%".MySQLFixup($sName)."%' ";
            }
            
            $field="DeptID,DeptName";  
            $cnt = M('dept')->where($where)->select(false);
            $total = count($cnt);
            
            $rs = M('dept')->field($field)->where($where)->limit($start,$length)->select();
            if($rs){
                foreach ($rs as $k=>$v){
                    $Managestr = "";
                    $Managestr .= " <a href='departMentEdit?depID={$v['deptid']}'>编辑</a>　|　";
                    $Managestr .=  "<a href=\"javascript:del('{$v['deptid']}','{$v['deptname']}')\">删除</a>";
                    $rs[$k]['outManage'] = $Managestr;
                }
            }    
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
        if(IS_POST){
            $AppResult = new AppResult();
            $departName = $this->getParam("departName");
            $rs = M('dept')->where("DeptName='".MySQLFixup($departName)."'")->find();
            if(!$rs){
                $data = array();
                $data['DeptName'] = $departName;
                $rs = M('dept')->add($data);
                if($rs){
                    $AppResult->code = 1;
                    $AppResult->data ="";
                    $AppResult->message = "分组添加成功";
                }
                else
                {
                    $AppResult->code = 0;
                    $AppResult->data ="";
                    $AppResult->message = "分组添加失败";
                }
            }
            else
            {
                $AppResult->code = 0;
                $AppResult->data ="";
                $AppResult->message = "已存在同名分组:$departName";
            }
            $AppResult->returnJSON();
        } 
        $this->assign("CurrentPage",'departMent');
        $this->display();
    }
    
    public function departMentEdit(){
        $depID = $this->getParam("depID");
        if(empty($depID)){
            JS_alert("请选中要编辑的部门");
        }
        if(IS_POST){
            $depName = $this->getParam("depName");
            if(empty($depName)){
                JS_alert("请部门名称");
            }
            $data = array();
            $data['DeptName'] = $depName;
            $rs = M('dept')->where("DeptID = '{$depID}'")->save($data);
            if(false !== $rs){
                $this->success("更新成功");
                die;
            }
        }
        $rs = M('dept')->where("DeptID = '{$depID}'")->find();
        
        $this->assign("rs",$rs);
        $this->assign("CurrentPage",'departMent');
        $this->display();
    }
    
    public function departMentDel(){
        $depID = $this->getParam("depID");
        $AppResult = new AppResult();
        if(empty($depID)){
            $AppResult->code = 0;
            $AppResult->data ="";
            $AppResult->message = "请选中要编辑的部门";
            $AppResult->returnJSON();
        }
        if(IS_POST){
            $depName = $this->getParam("depName");
            $rs = M('phonebook')->where("deptid = '{$depID}'")->find();
            if(!$rs)
            {
                $delrs = M('dept')->where("deptid = '{$depID}'")->delete();
                if($delrs){
                    $msg="成功删除分组记录".$depName;
                    addSysLog($msg);
                    $AppResult->code = 1;
                    $AppResult->data ="";
                    $AppResult->message = $msg;
                }
                else{
                    $AppResult->code = 0;
                    $AppResult->data ="";
                    $AppResult->message = "删除失败";
                }
            }else{
                $AppResult->code = 0;
                $AppResult->data ="";
                $AppResult->message = "删除失败：分组数据非空";
            }
            $AppResult->returnJSON();
        }
    }
    
    //导出excel
    public function downloadPhoneExcel(){
        Vendor("excel.PHPExcel");
        Vendor("excel.PHPExcel.Reader.Excel2007.php");
        Vendor("excel.PHPExcel.Reader.Excel5");
        Vendor("excel.PHPExcel.IOFactory");
        
        $excel = new \PHPExcel();
        
        //指定工作簿
        $Excel->setActiveSheetIndex(0); 
        //设置列宽
        $excel->getActiveSheet()->getColumnDimension('A')->setWidth('15');
        $excel->getActiveSheet()->getColumnDimension('B')->setWidth('15');
        $excel->getActiveSheet()->getColumnDimension('C')->setWidth('15');
        $excel->getActiveSheet()->getColumnDimension('D')->setWidth('15');
        $excel->getActiveSheet()->getColumnDimension('E')->setWidth('15');
        $excel->getActiveSheet()->getColumnDimension('F')->setWidth('15');
        
        
        $excel->getActiveSheet()->setTitle('交班报表'); //excel标题
        //设置行标题
        $excel->getActiveSheet()->setCellValue("A1", "姓名");         
        $excel->getActiveSheet()->setCellValue("B1", "性别");
        $excel->getActiveSheet()->setCellValue("C1", "移动电话");
        $excel->getActiveSheet()->setCellValue("D1", "办公电话");
        $excel->getActiveSheet()->setCellValue("E1", "其他电话");
        $excel->getActiveSheet()->setCellValue("F1", "备注");

         
        //获取并填充数据
        $rs = M('phonebook')->order('contactname')->select();
        
        $i=2;
        foreach ($variable as $key => $value) {
            $Excel->getActiveSheet()->setCellValue("A".$i,$value["contactname"]);
            $Excel->getActiveSheet()->setCellValue("B".$i,$value["sex"]);
            $Excel->getActiveSheet()->setCellValue("C".$i,$value["mobile"]);
            $Excel->getActiveSheet()->setCellValue("D".$i,$value["officenum"]);
            $Excel->getActiveSheet()->setCellValue("E".$i,$value["othernum"]);
            $Excel->getActiveSheet()->setCellValue("F".$i,$value["remark"]);
            $i++;
        } 
        
      
        $title ='交班报表';
        $date = date('Y-m-d');
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$title.'_'.urlencode($date).'".xls');
        $objwriter = \PHPExcel_IOFactory::createWriter($excel,'Excel5');
        $objwriter->save('php://output');
     }

 
} 