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
    
    //导出excel
    public function downloadExcel(){
        Vendor("excel.PHPExcel");
        Vendor("excel.PHPExcel.Reader.Excel2007.php");
        Vendor("excel.PHPExcel.Reader.Excel5");
        Vendor("excel.PHPExcel.IOFactory");
        
        $excel = new \PHPExcel();
        
        
        //设置列宽
        $excel->getActiveSheet()->getColumnDimension('D')->setWidth('14');
        $excel->getActiveSheet()->getColumnDimension('E')->setWidth('14');
        $excel->getActiveSheet()->getColumnDimension('F')->setWidth('14');
        $excel->getActiveSheet()->getColumnDimension('G')->setWidth('14');
        $excel->getActiveSheet()->getColumnDimension('H')->setWidth('14');
        
        
        
        $excel->getActiveSheet()->setTitle('交班报表'); //excel标题
        //设置行标题
        $datahead = array(3 => '微信收入', 4 => '支付宝收入', 5 => '现金收入',6 => '会员卡线下充值',7 => '会员卡支付总额',8 => '退款',9=>'合计');
        
     
        
        if ($groupbyoperator != '1'  or ($groupbyoperator == '1'  && $groupbydate == '1')){
            $datahead[0] ='交班时间';
        }
        if ($groupbydate != '1'  or ($groupbyoperator == '1'  && $groupbydate == '1')){
            $datahead[1] ='操作员';
        }
        if ($groupbyoperator == '1' || $groupbydate == '1'){
            unset($datahead[2]);
        }
        else
        {
            $datahead[2] ='SN码';
        }
        ksort($datahead);
        $i = 0;
        $sortarr = array();
        foreach ($datahead as  $v){
            $sortarr[$i] =$v;
            $i++;
        }
        $datahead =  $sortarr;
        //动态插入行标题并设置样式
        foreach ($datahead as $k => $v){
            $excel->getActiveSheet()->getStyleByColumnAndRow($k,1)
            ->getFont()->setBold(true);//字体加粗
            $excel->getActiveSheet()->getStyleByColumnAndRow($k,1)->
            getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);//文字居中
            $excel->getActiveSheet()->setCellValueByColumnAndRow($k,1,$v);
        }
        if($group !=''){
            $list = M()->fetchSql(false)->query("select dctime,AccountDate,operator,MachineID,sum(WxAmount) WxAmount,sum(ZfbAmount) ZfbAmount,sum(memberpayAmount) memberpayAmount,sum(memberRechargeAmt) memberRechargeAmt,sum(OtherAmount) OtherAmount,sum(RefundAmount) RefundAmount,sum(WxAmount) + sum(ZfbAmount) + sum(memberRechargeAmt) + sum(OtherAmount) as totalmoney from daily_closing where ".$where.$group."   order by dctime desc ");
        }
        else
        {
            $list = M()->fetchSql(false)->query("select dctime,AccountDate,operator,MachineID,WxAmount,ZfbAmount,memberpayAmount,memberRechargeAmt,OtherAmount,RefundAmount,sum(WxAmount + ZfbAmount + memberRechargeAmt + OtherAmount) as totalmoney from daily_closing where ".$where."  group by dctime order by dctime desc ");
        }

        // $excel->setActiveSheetIndex(0);
        foreach ($list as $k =>$v){
            $k = $k+2;
            if($groupbyoperator == '1'  && $groupbydate == '1'){ //按操作员和时间
                $excel->setActiveSheetIndex(0)
                ->setCellValue('A'.$k,$v['accountdate'])
                ->setCellValue('B'.$k,$v['operator'])
                ->setCellValue('C'.$k,$v['wxamount']/100 != 0 ? $v['wxamount']/100 : '0')
                ->setCellValue('D'.$k,$v['zfbamount']/100 != 0 ? $v['zfbamount']/100 : '0')
                ->setCellValue('E'.$k,$v['otheramount']/100 != 0 ? $v['otheramount']/100 : '0')
                ->setCellValue('F'.$k,$v['memberrechargeamt']/100 != 0 ? $v['memberrechargeamt']/100 : '0')
                ->setCellValue('G'.$k,$v['memberpayamount']/100 != 0 ? $v['memberpayamount']/100 : '0')
                ->setCellValue('H'.$k,$v['refundamount']/100 != 0 ? $v['refundamount']/100 : '0')
                ->setCellValue('I'.$k,$v['totalmoney']/100 != 0 ? $v['totalmoney']/100 : '0');
            }   
            else{
                if($groupbyoperator == '1'  && $groupbydate != '1'){ //只按操作员
                    $excel->setActiveSheetIndex(0)
                    ->setCellValue('A'.$k,$v['operator'])
                    ->setCellValue('B'.$k,$v['wxamount']/100 != 0 ? $v['wxamount']/100 : '0')
                    ->setCellValue('C'.$k,$v['zfbamount']/100 != 0 ? $v['zfbamount']/100 : '0')
                    ->setCellValue('D'.$k,$v['otheramount']/100 != 0 ? $v['otheramount']/100 : '0')
                    ->setCellValue('E'.$k,$v['memberrechargeamt']/100 != 0 ? $v['memberrechargeamt']/100 : '0')
                    ->setCellValue('F'.$k,$v['memberpayamount']/100 != 0 ? $v['memberpayamount']/100 : '0')
                    ->setCellValue('G'.$k,$v['refundamount']/100 != 0 ? $v['refundamount']/100 : '0')
                    ->setCellValue('H'.$k,$v['totalmoney']/100 != 0 ? $v['totalmoney']/100 : '0');
                }
                elseif($groupbyoperator != '1'  && $groupbydate == '1'){ //只按时间
                    $excel->setActiveSheetIndex(0)
                    ->setCellValue('A'.$k,$v['accountdate'])
                    ->setCellValue('B'.$k,$v['wxamount']/100 != 0 ? $v['wxamount']/100 : '0')
                    ->setCellValue('C'.$k,$v['zfbamount']/100 != 0 ? $v['zfbamount']/100 : '0')
                    ->setCellValue('D'.$k,$v['otheramount']/100 != 0 ? $v['otheramount']/100 : '0')
                    ->setCellValue('E'.$k,$v['memberrechargeamt']/100 != 0 ? $v['memberrechargeamt']/100 : '0')
                    ->setCellValue('F'.$k,$v['memberpayamount']/100 != 0 ? $v['memberpayamount']/100 : '0')
                    ->setCellValue('G'.$k,$v['refundamount']/100 != 0 ? $v['refundamount']/100 : '0')
                    ->setCellValue('H'.$k,$v['totalmoney']/100 != 0 ? $v['totalmoney']/100 : '0');
                }
                else
                {
                    $excel->setActiveSheetIndex(0)
                    ->setCellValue('A'.$k,$v['dctime'])
                    ->setCellValue('B'.$k,$v['operator'])
                    ->setCellValue('C'.$k,$v['machineid'])
                    ->setCellValue('D'.$k,$v['wxamount']/100 != 0 ? $v['wxamount']/100 : '0')
                    ->setCellValue('E'.$k,$v['zfbamount']/100 != 0 ? $v['zfbamount']/100 : '0')
                    ->setCellValue('F'.$k,$v['otheramount']/100 != 0 ? $v['otheramount']/100 : '0')
                    ->setCellValue('G'.$k,$v['memberrechargeamt']/100 != 0 ? $v['memberrechargeamt']/100 : '0')
                    ->setCellValue('H'.$k,$v['memberpayamount']/100 != 0 ? $v['memberpayamount']/100 : '0')
                    ->setCellValue('I'.$k,$v['refundamount']/100 != 0 ? $v['refundamount']/100 : '0')
                    ->setCellValue('J'.$k,$v['totalmoney']/100 != 0 ? $v['totalmoney']/100 : '0');
                }
            }        
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