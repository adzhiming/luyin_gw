<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
use Think\Db;
use Think\Log;
use Think\upload;

class SystemController extends AuthController {
    public function _initialize() {
        $this->assign("SystemSub",1);
    }
    
    public function index(){
        $this->display();
    }
    //通道参数
    public function channelParameter(){
        $ChannelType = $this->getParam("type");
        if(IS_POST){
            //var_dump($_REQUEST);
            $n_channelno = $this->getParam("n_channelno"); 
            $clo_name = $this->getParam("clo_name");
            $k = count($n_channelno);
            $i = 0;
            foreach ($clo_name as $key => $value) {
                 $data = array();
                 $data[$value] = $this->getParam($value);
                 $data["hid_".$value] = $this->getParam("hid_".$value);

                 for($i=0;$i<$k;$i++){
                     
                    
                    if($data[$value][$i] != $data["hid_".$value][$i]){
                        $update = array();
                       
                        
                        
                        $update['V_Value'] = $data[$value][$i];
                        
                        $rs = M('sys_paramschannel')
                              ->where("N_ChannelNo='".$n_channelno[$i]."' and V_ParamsName = '".$value."' ")
                              ->save($update);
                              // echo M()->getLastSql()."<br>"; 

                    }
                     $IsVideoChannel = $this->getParam("IsVideoChannel_{$i}",0);
                     //echo $IsVideoChannel."<br>"; 
                     $update2 = array();
                     $update2['V_Value'] = $IsVideoChannel;
                     $rs = M('sys_paramschannel')
                              ->where("N_ChannelNo='".$n_channelno[$i]."' and V_ParamsName = 'IsVideoChannel' ")
                              ->save($update2);
                               
                 }
            }
            echo "<script>alert('修改成功');window.location.href='/home/System/channelParameter'</script>";
            
        }
        //如果没第一条“设为录象通道”数据，则插入
        $rs = M('sys_paramschannel')->where("N_ChannelNo=10000 and V_ParamsName='IsVideoChannel'")->find();
        if (!$rs) {
            $data['N_ChannelNo'] = '10000';
            $data['V_ParamsName'] = 'IsVideoChannel';
            $data['V_ParamsNameCh'] = '设为录象通道';
            $data['V_Value'] = '0';
            $data['N_Change'] = '0';
            $data['V_DefaultValue'] = '0';
            $data['V_Describe'] = '录象通道：0为非录象通道，1为录象通道';
            $data['N_ModifyLevel'] = '0';
            $data['advPara'] = '5';
            $data['V_Verify'] = '';
            
            $rsInsert = M('sys_paramschannel')->add($data);
            
        } 
        $config = M('sys_channelconfig')->field("N_channeltype")->order("N_channeltype asc")->find();
         
        $ChannelType = empty($ChannelType)?$config['n_channeltype']:$ChannelType;

        //查找通道
        $field ="a.N_ChannelNo";
        $rs = M('sys_paramschannel a')
              ->join("tab_sys_channelconfig b on a.N_ChannelNo = b.N_ChannelNo",'inner')
              ->field($field)
              ->where("b.N_ChannelType = '{$ChannelType}'")
              ->group("a.N_ChannelNo")->select();
        
        $channelNo = array();
        $checkbox=0;//录象通道checkbox变量
       if($rs){
            foreach ($rs as $k=>$v){
                $channelNo = $v['n_channelno'];
                $where = array();
                $where['N_ChannelNo'] = $channelNo;
                $where['advPara'] = array("gt",0);
                $rsTitle = M('sys_paramschannel')->field("v_paramsname,V_ParamsNameCh")->where($where)->order('advPara')->select();
                if($rsTitle){
                   
                    foreach($rsTitle as $kl=> $vl){ 

                         if (($ChannelType != 33 && $vl["v_paramsname"] == 'IsVideoChannel')
                            || ($vl["v_paramsname"] == "ChannelUserDisable" && $ChannelType == '33')
                            || ($vl["v_paramsname"]=="DefaultRoute")
                            || ($vl["v_paramsname"]=="n_channelno")
                            )
                         {
                            continue;
                         } 
                         else{
                            $rs[$k][$vl["v_paramsname"]] = $this->getChannelInfoBychannelNo($channelNo,$vl["v_paramsname"]);
                          //echo $vl["v_paramsname"]."==". $rs[$k][$vl["v_paramsname"]]['v_value']."<br>";
                           $rs[$k][$vl["v_paramsname"]]["input"] = $this->parame_input($vl["v_paramsname"],$rs[$k][$vl["v_paramsname"]]['v_value'],$checkbox,$channelNo);

                          

                         }
                          
                    }
                   
                }   

                 
               /*               
                $rs[$k]['ChnName'] = $this->getChannelInfoBychannelNo($channelNo,"ChnName");
                $rs[$k]['ExtPhoneNumber'] = $this->getChannelInfoBychannelNo($channelNo,"ExtPhoneNumber");
                $rs[$k]['CircuitNo'] = $this->getChannelInfoBychannelNo($channelNo,"CircuitNo");
                $rs[$k]['ExtPhoneNumber2'] = $this->getChannelInfoBychannelNo($channelNo,"ExtPhoneNumber2");
                $rs[$k]['IsChkDTMF'] = $this->getChannelInfoBychannelNo($channelNo,"IsChkDTMF");
                $rs[$k]['CircuitNo2'] = $this->getChannelInfoBychannelNo($channelNo,"CircuitNo2");
                $rs[$k]['IsVideoChannel'] = $this->getChannelInfoBychannelNo($channelNo,"IsVideoChannel");
                $rs[$k]['RecordMode'] = $this->getChannelInfoBychannelNo($channelNo,"RecordMode");
                $rs[$k]['ChannelUserDisable'] = $this->getChannelInfoBychannelNo($channelNo,"ChannelUserDisable");
                */
                $checkbox++;
            }
        } 

       /*echo "<pre>";
        print_r($rs);
        echo "</pre>";*/ 
       /*foreach ($rs as $key => $value) {
             foreach ($value as $k => $v) {
                 //echo $k."==".$v."<br>";
                 var_dump($v)."<br>";
             }
        }*/
        
        //查找标题
        $where = array();
        $where['N_ChannelNo'] = array("in",$channelNo);
        $where['advPara'] = array("gt",0);
        $rsTitle = M('sys_paramschannel')->field("v_paramsname,V_ParamsNameCh")->where($where)->order('advPara')->select();
        
        foreach ($rsTitle as $k=>$v){
            if ($ChannelType != 33 && $v["v_paramsname"] == 'IsVideoChannel')
            {
                unset($rsTitle[$k]);
            }//IP录音不要通道禁用启用
            elseif ($v["v_paramsname"] == "ChannelUserDisable" && $ChannelType == '33')
            {
                unset($rsTitle[$k]);
            }elseif ($v["v_paramsname"]=="DefaultRoute")
            {
                unset($rsTitle[$k]);
            }
        }
        
        //录音类型
        $channeltypelist = M("sys_channelconfig")->field("distinct N_channeltype")->order("N_Channeltype")->select();
        foreach ($channeltypelist as $k=>$v){
            $type = M("sys_channeltype")->where("V_TypeID = '{$v['n_channeltype']}'")->find();
            $channeltypelist[$k]['typename'] = $type['v_typename'];

        }
       /* echo "<pre>";
        print_r($rs);
        echo "</pre>";*/
        
        $this->assign("channeltype",$ChannelType);
        $this->assign("channeltypelist",$channeltypelist);
        $this->assign("rsTitle",$rsTitle);
        $this->assign("rs",$rs);
        $this->assign("CurrentPage",'channel');
        $this->display();
    }
    
    //高级参数
    public function paramsChannel(){
        $channelid=isset($_GET["channelid"])?$_GET["channelid"]:"9999";
        $todo=isset($_POST["todo"])?$_POST["todo"]:"";
        if(IS_POST){
            if($todo=="update"){
                $msg="";
                $paraList=$this->getParam('paraList');                                   //参数名称列表;
                $paraList=explode(",",$paraList);
                for($i=0;$i<count($paraList);$i++){
                    $paraValue[$i]=$_POST[$paraList[$i]];
                }
                if($channelid==9999){
                    $channeltype=9999;
                }else
                {
                    $rs = M('sys_channelconfig')->field('N_channeltype')->where("n_channelno='{$channelid}'")->find();
                    $channeltype=$rs['n_channeltype'];
                }
                if($channeltype==3){
                    $msg="成功修改参数设置，请重新启动服务管理器里面的NEU_TECH_VCR服务";
                }elseif($channeltype==33)
                {
                    $msg="成功修改参数设置，请重新启动服务管理器里面的NEU_TECH_MED和NEU_TECH_SIG服务";
                }else
                {
                    $msg="成功修改参数设置，请重新启动服务管理器里面的NEU_TECH_VCR、NEU_TECH_MED和NEU_TECH_SIG服务";
                }                               //更新设置
                for($i=0;$i<count($paraList);$i++){                        
                    //遍历所有参数
                    $where = array();
                    $update = array();
                    $where['V_ParamsName'] = $paraList[$i];
                    if($channelid != 9999){
                        $where['N_ChannelNo'] = $channelid;
                    }
                    $update['V_Value'] = MySQLFixup($paraValue[$i]);
                    $update['N_Change'] =1;
                    $rs = M('sys_paramschannel')->where($where)->save($update);
                      
                    if(false === $rs){
                        $msg=$msg."修改参数[".$paraList[$i]."]时发生错误，操作失败。\\n";
                    }else{
                        if($paraValue[$i] != $_POST["hid".$paraList[$i]]){        //参数值发生了改变
                            if($channelid=9999){
                                addSysLog("通过浏览器成功修改所有通道参数".$paraList[$i]."=".MySQLFixup($paraValue[$i]));
                            }else{
                                addSysLog("通过浏览器成功修改通道".$channelid."参数".$paraList[$i]."=".MySQLFixup($paraValue[$i]));
                            }
                        }               
                    }
                }
               
             }
             else
             {
                if($channelid==9999)
                {
                    $channeltype=9999;
                }
                else
                {
                    $rs = M('sys_channelconfig')->field('N_channeltype')->where("n_channelno='{$channelid}'")->find();
                    $channeltype=$rs['n_channeltype'];
                }                                                       //恢复默认设置
                $msg="恢复默认值成功，";
                if($channeltype==3){
                    $msg.="请重新启动服务管理器的NEU_TECH_VCR服务";
                }elseif($channeltype==33)
                {
                    $msg.="请重新启动服务管理器里面的NEU_TECH_MED和NEU_TECH_SIG服务";
                }else
                {
                    $msg.="请重新启动服务管理器里面的NEU_TECH_VCR、NEU_TECH_MED和NEU_TECH_SIG服务";
                }
                for($i=0;$i<count($paraList);$i++){
                    $where = array();
                    $update = array();
                    $where['V_ParamsName'] = $paraList[$i];
                    $update['V_Value'] = 'V_DefaultValue';
                    

                    
                    $log="通过浏览器恢复通道参数".$paraList[$i]."默认值（所有通道）";
                    if($channelid!=9999){
                        $where['N_ChannelNo'] = $channelid;
                        $log="通过浏览器恢复通道".$channelid."参数".$paraList[$i]."默认值";
                    }
                    $rs = M('sys_paramschannel')->where($where)->save($update);
                    //echo $sql."<br />";
                    if(false === $rs){
                        $msg=$msg."参数[".$paraList[$i]."]恢复默认值时发生错误，操作失败。\\n";
                    }else{
                        addSysLog($log);
                    }
                }
             } 
             $url ="/home/System/paramsChannel/channelid/{$channelid}";
             JS_alert($msg,$url,true); exit; 
        }
        
        $sql = "select a.*,b.n_channeltype,b.n_channelno from tab_sys_paramschannel a,tab_sys_channelconfig b ";
        $sql=$sql."where a.N_ChannelNo=".$channelid." and a.advpara=0 and a.N_ChannelNo=b.N_ChannelNo and a.V_ParamsName !='ChannelPort' order by a.v_paramsnameCH";
        //$sql_="select N_ChannelNo from tab_sys_paramschannel order by N_ChannelNo limit 0,1;";
        $sql=str_replace("a.N_ChannelNo=9999","a.N_ChannelNo=(select N_ChannelNo from tab_sys_paramschannel order by N_ChannelNo limit 0,1)",$sql);//全部通道，显示通道号为0的通道
        $rs = M()->query($sql);
        if($rs){
            $strParaName = "";
            foreach ($rs as $k=>$v)
            {
                $rs[$k]['paramsset'] = $this->getInput($v["v_paramsname"],$v["v_value"],$v["n_modifylevel"],$v["v_verify"]);
                $strParaName=$strParaName.($strParaName==""?"":",").$v["v_paramsname"];
            }
        }
        $sql="select distinct N_ChannelNo from tab_sys_paramschannel order by N_ChannelNo";
        $channelist = M()->query($sql);

        $this->assign('channelid',$channelid);
        $this->assign("channelist",$channelist);
        $this->assign("strParaName",$strParaName);
        $this->assign("rs",$rs);
        $this->assign("CurrentPage",'channel');
        $this->display();
    }
    
    //系统参数
    public function systemParameter(){
        $todo=isset($_POST["todo"])?$_POST["todo"]:"";
        if(IS_POST){
            if($todo=="update"){
                $pName=explode(",",$_POST["strPname"]);//参数列表，分解成数组；
                for($i=0;$i<count($pName);$i++){
                    $p=$pName[$i];
                    //当提交过来的值为0时，不能使用字符过滤函数，否则0边空值。
                    $pv=MySQLFixup($_POST[$p]);
                    $hidpv=MySQLFixup($_POST["hid".$p]);
                    if($pv!=$hidpv){
                        $update = array();
                        $where = array();
                        $where['V_ParamsName'] = $p;
                        $update['N_Change'] = -1;
                        $update['V_Value'] = $pv;
                        $rs = M("sys_paramssystem")->where($where)->save($update);
                         
                        if(false === $rs){
                            $errMsg=$errMsg.(($errMsg=="")?"":"\\n")."提交参数[".$p."]发生未知错误，操作失败!";
                        }else{
                            if($p=="Sys_AutoBakDir"){//修改自动备份目录，则即时更新备份目录的虚拟路径(修改apache配置文件)
                                $tmp=str_replace("\\\\","\\",$pv);
                                if(substr($tmp,-1,1)=="\\"){
                                    $tmp=substr($tmp,0,(strlen($tmp)-1));
                                }
                                $rp1=array(":","\\");
                                $rp2=array("_","_");
                                $netPath="STD1_".str_replace($rp1,$rp2,$tmp);
                                //确定
                                //echo $netPath;
                                CreatNetPath($netPath,$tmp);
                            }
                            addSysLog("通过浏览器成修改系统参数:".$p."=".$pv);
                            $errMsg="修改成功";
                        }
                    }
                }
            }
            elseif($todo=="setDefault"){
                $update = array();
                $where = array();
                $where['V_ParamsName'] = 'Sys_License_Count';
                $where['N_ModifyLevel'] = 0;
                $update['N_Change'] = -1;
                $update['V_Value'] = 'V_DefaultValue';
                $rs = M("sys_paramssystem")->where($where)->save($update);

             
                if(false === $rs){
                    $errMsg="发生未知错误，操作失败!";
                }
                if($errMsg==""){
                    addSysLog("系统参数恢复默认值");
                    $errMsg="成功恢复参数默认设置";
                } 
            }

            $url ="/home/System/systemParameter";
            JS_alert($errMsg,$url,true); exit; 
       }
    
        $strPname = "";
        $rs = M("sys_paramssystem")->where("N_ModifyLevel< 2")->order("V_ParamsNameCh")->select();
        if($rs){
            foreach ($rs as $k=>$v)
            {
                $rs[$k]['paramsset'] = $this->showParaObj($v["v_paramsname"],$v["v_value"],$v["n_modifylevel"]);
                $strPname=$strPname.(($strPname=="")?"":",").$v['v_paramsname'];
            }
        }

        $this->assign("strPname",$strPname);
        $this->assign("rs",$rs);
        $this->assign("CurrentPage",'system');
        $this->display();
    }
    

    //导出系统配置
    public function down_systemParameter(){
        header("Content-type: text/html; charset=utf-8");
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.Reader.Excel2007.php");
        Vendor("PHPExcel.PHPExcel.Reader.Excel5");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        
        $excel = new \PHPExcel();
        
        //指定工作簿
        $excel->setActiveSheetIndex(0); 
        //设置列宽
        $excel->getActiveSheet()->getColumnDimension('A')->setWidth('15');
        $excel->getActiveSheet()->getColumnDimension('B')->setWidth('15');
        $excel->getActiveSheet()->getColumnDimension('C')->setWidth('15');
        $excel->getActiveSheet()->getColumnDimension('D')->setWidth('15');
        $excel->getActiveSheet()->getColumnDimension('E')->setWidth('15');
        $excel->getActiveSheet()->getColumnDimension('F')->setWidth('15');
        
        
        $excel->getActiveSheet()->setTitle('系统配置'); //excel标题
        //设置行标题
        $excel->getActiveSheet()->setCellValue("A1", "V_ParamsName");         
        $excel->getActiveSheet()->setCellValue("B1", "V_ParamsNameCh");
        $excel->getActiveSheet()->setCellValue("C1", "V_Value");
        $excel->getActiveSheet()->setCellValue("D1", "N_Change");
        $excel->getActiveSheet()->setCellValue("E1", "V_DefaultValue");
        $excel->getActiveSheet()->setCellValue("F1", "V_Describe");
        $excel->getActiveSheet()->setCellValue("G1", "N_ModifyLevel");
        $excel->getActiveSheet()->setCellValue("H1", "V_Verify");

        $selStr="V_ParamsName,V_ParamsNameCh,V_Value,N_Change,V_DefaultValue,V_Describe,N_ModifyLevel,V_Verify";
        //$sql="select ".$selStr." from tab_sys_paramssystem where N_ModifyLevel=0";    //将所有用户可修改的参数导出
       // $sql="select ".$selStr." from tab_sys_paramssystem where N_ModifyLevel=0";  //将所有用户参数导出
        $rs = M('sys_paramssystem')->field($selStr)->where("N_ModifyLevel = 0")->select();
        if($rs){
            $i=1;
            foreach ($rs as $k => $v)
            {
                $i++;
                $excel->getActiveSheet()->setCellValue("A".$i,str_replace("\r\n","",str_replace(",","，",$v["v_paramsname"])));
                $excel->getActiveSheet()->setCellValue("B".$i,str_replace("\r\n","",str_replace(",","，",$v["v_paramsnamech"])));
                $excel->getActiveSheet()->setCellValue("C".$i,str_replace("\r\n","",str_replace(",","，",$v["v_value"])));
                $excel->getActiveSheet()->setCellValue("D".$i,$v["n_change"]);
                $excel->getActiveSheet()->setCellValue("E".$i,str_replace("\r\n","",str_replace(",","，",$v["v_defaultvalue"])));
                $excel->getActiveSheet()->setCellValue("F".$i,str_replace("\r\n","",str_replace(",","，",$v["v_describe"])));
                $excel->getActiveSheet()->setCellValue("G".$i,$v["n_modifylevel"]);
                $excel->getActiveSheet()->setCellValue("H".$i,str_replace("\r\n","",str_replace(",","，",$v["v_verify"])));

            }
        }
        $title ='系统配置';
        $date = date('Y-m-d',time());
        
        $data = array();
        $fileurl = "Uploads/phoneBook/". $title."_".$date.".xls";
       
        $filename = iconv('utf-8', "gb2312", $fileurl);
        
        $objwriter = \PHPExcel_IOFactory::createWriter($excel,'Excel5');
        $objwriter->save($filename);
        $this->returnJSON(1,'导出成功',"/".$fileurl);
    }

   //恢复备份配置
    public function backupUpload(){
         if(IS_POST){
             $type=isset($_POST["type"])?$_POST["type"]:"system";
             $file = $_FILES; 
             //上传
             $filePath = $this->uploadFile($file);
              
             //读取excel
             Vendor("PHPExcel.PHPExcel");
             Vendor("PHPExcel.PHPExcel.Reader.Excel2007.php");
             Vendor("PHPExcel.PHPExcel.Reader.Excel5");
             Vendor("PHPExcel.PHPExcel.IOFactory");
         
             if(empty($filePath) or !file_exists($filePath)){die('file not exists');}
             $PHPReader = new \PHPExcel_Reader_Excel2007();        //建立reader对象
             if(!$PHPReader->canRead($filePath)){
                 $PHPReader = new \PHPExcel_Reader_Excel5();
                 if(!$PHPReader->canRead($filePath)){
                      echo 'no Excel';
                     return ;
                }
             }
             
            $objReader = \PHPExcel_IOFactory::createReader('Excel5');
            $objPHPExcel = $objReader->load($filePath,$encode='utf-8');
            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow(); // 取得总行数
            $highestColumn = $sheet->getHighestColumn(); // 取得总列数
            
            if($type == 'system'){
                $m = M('sys_paramssystem');
                //删除原有数据
                $ParaStr="V_ParamsName,V_ParamsNameCh,V_Value,N_Change,V_DefaultValue,V_Describe,N_ModifyLevel,V_Verify";
                $tbName="tab_sys_paramssystem"; //系统参数表
                $del = M('sys_paramssystem')->where("N_ModifyLevel=0")->delete();
                }
            else{
                $m = M('sys_paramschannel');
                $ParaStr="N_ChannelNo,V_ParamsName,V_ParamsNameCh,V_Value,N_Change,V_DefaultValue,V_Describe,N_ModifyLevel,advPara,V_Verify";
                $tbName="tab_sys_paramschannel";//通道参数表
                $del = M('sys_paramschannel')->where("N_ModifyLevel=0 or advPara=0")->delete(); 
            }
            

            $cols=explode(",",$ParaStr);//字段名数组
 
            $msg = "";
            if(false !== $del){
                for($i=1;$i<$highestRow+1;$i++){
                    $insert = array();

                    for($currentColumn = 'A';$currentColumn<= $highestColumn; $currentColumn++){
                        if($i ==1){
                            $val = $objPHPExcel->getActiveSheet()->getCell($currentColumn.$i)->getValue(); 
                            
                            if(!in_array($val,$cols)){
                                 $this->error("文件内容格式错误！请确保导入文件的正确性。");
                            }
                        }
                        else
                        {
                            $val = $objPHPExcel->getActiveSheet()->getCell($currentColumn.'1')->getValue();
                            $v = MySQLFixup(str_replace("，",",",$objPHPExcel->getActiveSheet()->getCell($currentColumn.$i)->getValue()));
                            $v = empty($v)?'':$v;
                            $insert[$val] = $v;
                             
                        }
                    }
                    if($i !=1){
                        $rs = $m->add($insert);
                        $sql = M()->getLastSql();
                        if(false ==$rs){
                            $msg .= "第{$i}行发生错误：".mysql_error()."<br>";
                        }
                        else{
                            $msg .= "第{$i}行恢复成功！;<br>";
                        }
                    }
                   
                }
            }
            
             $this->success($msg,'',5);
             exit;
         }   
         $type = empty($_REQUEST['type'])?'system':$_REQUEST['type'];
         if($type == 'system'){
             $title = "恢复系统参数设置";
             $this->assign("CurrentPage",'system');
         }
         else{
            $title = "恢复通道参数设置";
            $this->assign("CurrentPage",'channel');
         }
         $this->assign("title",$title);
         $this->assign("type",$type);
         
         $this->display();
    }


    //导出通道参数配置
    public function down_channelParameter(){
        header("Content-type: text/html; charset=utf-8");
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.Reader.Excel2007.php");
        Vendor("PHPExcel.PHPExcel.Reader.Excel5");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        
        $excel = new \PHPExcel();
        
        //指定工作簿
        $excel->setActiveSheetIndex(0); 
        //设置列宽
        $excel->getActiveSheet()->getColumnDimension('A')->setWidth('15');
        $excel->getActiveSheet()->getColumnDimension('B')->setWidth('15');
        $excel->getActiveSheet()->getColumnDimension('C')->setWidth('15');
        $excel->getActiveSheet()->getColumnDimension('D')->setWidth('15');
        $excel->getActiveSheet()->getColumnDimension('E')->setWidth('15');
        $excel->getActiveSheet()->getColumnDimension('F')->setWidth('15');
        
        
        $excel->getActiveSheet()->setTitle('通道参数配置'); //excel标题
        //设置行标题
        $excel->getActiveSheet()->setCellValue("A1", "N_ChannelNo");         
        $excel->getActiveSheet()->setCellValue("B1", "V_ParamsName");
        $excel->getActiveSheet()->setCellValue("C1", "V_ParamsNameCh");
        $excel->getActiveSheet()->setCellValue("D1", "V_Value");
        $excel->getActiveSheet()->setCellValue("E1", "N_Change");
        $excel->getActiveSheet()->setCellValue("F1", "V_DefaultValue");
        $excel->getActiveSheet()->setCellValue("G1", "V_Describe");
        $excel->getActiveSheet()->setCellValue("H1", "N_ModifyLevel");
        $excel->getActiveSheet()->setCellValue("I1", "advPara");
        $excel->getActiveSheet()->setCellValue("J1", "V_Verify");

        $selStr="N_ChannelNo,V_ParamsName,V_ParamsNameCh,V_Value,N_Change,V_DefaultValue,V_Describe,N_ModifyLevel,advPara,V_Verify";
        //$sql="select ".$selStr." from tab_sys_paramssystem where N_ModifyLevel=0";    //将所有用户可修改的参数导出
       // $sql="select ".$selStr." from tab_sys_paramssystem where N_ModifyLevel=0";  //将所有用户参数导出
        $rs = M('sys_paramschannel')->field($selStr)->order("N_ChannelNo,V_ParamsName")->select();
        if($rs){
            $i=1;
            foreach ($rs as $k => $v)
            {
                $i++;
                $excel->getActiveSheet()->setCellValue("A".$i,str_replace("\r\n","",str_replace(",","，",$v["n_channelno"])));
                $excel->getActiveSheet()->setCellValue("B".$i,str_replace("\r\n","",str_replace(",","，",$v["v_paramsname"])));
                $excel->getActiveSheet()->setCellValue("C".$i,str_replace("\r\n","",str_replace(",","，",$v["v_paramsnamech"])));
                $excel->getActiveSheet()->setCellValue("D".$i,str_replace("\r\n","",str_replace(",","，",$v["v_value"])));
                $excel->getActiveSheet()->setCellValue("E".$i,$v["n_change"]);
                $excel->getActiveSheet()->setCellValue("F".$i,str_replace("\r\n","",str_replace(",","，",$v["v_defaultvalue"])));
                $excel->getActiveSheet()->setCellValue("G".$i,str_replace("\r\n","",str_replace(",","，",$v["v_describe"])));
                $excel->getActiveSheet()->setCellValue("H".$i,$v["n_modifylevel"]);
                $excel->getActiveSheet()->setCellValue("I".$i,$v["advpara"]);
                $excel->getActiveSheet()->setCellValue("J".$i,str_replace("\r\n","",str_replace(",","，",$v["v_verify"])));

            }
        }
        $title ='通道参数配置';
        $date = date('Y-m-d',time());
        
        $data = array();
        $fileurl = "Uploads/phoneBook/". $title."_".$date.".xls";
       
        $filename = iconv('utf-8', "gb2312", $fileurl);
        
        $objwriter = \PHPExcel_IOFactory::createWriter($excel,'Excel5');
        $objwriter->save($filename);
        $this->returnJSON(1,'导出成功',"/".$fileurl);
    }



    //上传
 public function uploadFile(){
         $upload = new \Think\Upload();
         $upload->maxSize = 314572800 ;// 设置附件上传大小
         $upload->exts = array('jpg', 'gif', 'png', 'jpeg', 'xls');// 设置附件上传类型
         $upload->rootPath = './Uploads/'; // 设置附件上传根目录
         $upload->savePath = ''; // 设置附件上传（子）目录
         $upload->subName  = array('date','Ymd');
         // 上传文件
         $info = $upload->upload();
         $info = $info['PZfile'];
         if(!$info) {
            // 上传错误提示错误信息
            $this->error($upload->getError());
         }
    return  './Uploads/' . $info['savepath'] . $info['savename'];
 }

    //注册信息
    public function licenseInfo(){
        if(IS_POST){
            //接收到提交表单值，更新注册信息
            $LicenseKey = $this->getParam("LicenseKey");
            $N_Stationid = $this->getParam("N_Stationid");
            $N_Board = $this->getParam("N_Board");
            if($LicenseKey){
                $service="";
                $banka_num=0;$ruanjiangou_num=0;
                for($i=0;$i<count($LicenseKey);$i++){
                    $where = array();
                    $v_licensekey = MySQLFixup($LicenseKey[$i]);
                    $n_stationid = $N_Stationid[$i];
                    $n_boardserialno = $N_Board[$i];
                    $sql ="update tab_sys_license set v_licensekey = '{$v_licensekey}' where  n_stationid = '{$N_Stationid[$i]}' and n_boardserialno = '{$N_Board[$i]}' ";
                    $upd = M()->execute($sql);
                    $find = M('sys_license')->where(" n_stationid = '{$N_Stationid[$i]}' and n_boardserialno = '{$N_Board[$i]}' ")->field("N_LicenseType")->find();
                    if($find['n_licensetype']==0){
                        $banka_num++;
                    }else{
                        $ruanjiangou_num++;
                    }
                }
                if($banka_num>0){
                    $service.="NEU_TECH_VCR";
                }
                if($ruanjiangou_num>0){
                    $banka_num>0?($service.="、"):'';
                    $service.="NEU_TECH_MED和NEU_TECH_SIG";
                }
                JS_alert("您已经输入注册信息，\\r\\r请重新启动服务管理器里面的".$service."服务，验证您的注册是否合法");
            }
          
        }
        $where = array();
        $where['N_ModifyLevel'] = 2;
        $rs = M('sys_license')->where($where)->select();
        if($rs){
            foreach ($rs as $k=>$v)
            {
                if($v['n_licenseisvalid'] ==1)
                {
                    $rs[$k]['usertype'] = "正式用户";
                }
                else
                {
                    $rs[$k]['usertype'] = "未注册用户";
                }
                if($v['n_licensetype'] ==1)
                {
                    $rs[$k]['licensetype'] = "软交换狗";
                    $cnt = M("sys_paramssystem")->where("V_ParamsName='Sys_IpCh_Count'")->field("V_Value")->find();
                    $tmpNum=$cnt['v_value'];
                    
                    $Vcnt = M("sys_paramssystem")->where("V_ParamsName='Sys_Vedio_Count'")->field("V_Value")->find();
                    $tmpNum.="/".$Vcnt['v_value']."（录音/录象）";
                    $rs[$k]['tmpnum'] = $tmpNum;
                }
                else
                {
                    $rs[$k]['licensetype'] = "板卡";
                    $Lcnt = M("sys_paramssystem")->where("V_ParamsName='Sys_License_Count'")->field("V_Value")->find();
                    $tmpNum=$Lcnt['v_value'];
                    $rs[$k]['tmpnum'] = $tmpNum;
                   
                }
            }
        }
        $this->assign("rs",$rs);
        $this->assign("CurrentPage",'license');
        $this->display();
    }
    
    //磁盘参数Sys_AutoBakDir
    public function diskParameter(){
        $CurrentStation=isset($_POST["SelStationID"])?$_POST["SelStationID"]:"";        //当前查看(设置)的工作站编号
        if(IS_POST){
            //var_dump($_POST["hid_UsingPath"]);
            $aryDisk=$_POST["DrvName"];             //磁盘列表
            $aryOldPath=$_POST["hid_path"];         //各磁盘录音保存路路径(修改后)
            $aryNewPath=$_POST["path"];             //各磁盘录音保存路路径(修改前)
            $oldSpace=$_POST["hid_LessFreeSpace"];  //磁盘预留空间(修改前)
            $newSpace=$_POST["N_LessFreeSpace"];    //磁盘预留空间(修改后)
            $newDisk=$_POST["newDisk_path"];        //新选中使用的磁盘
            $UsingPath=$_POST["hid_UsingPath"];     //提交修改前使用的录音路径。
            $RestartVCR="0";                        //是否需要重启VCR服务，弹出告警信息
            $DiskIsChange=(substr($newDisk,0,2)==substr($UsingPath,0,2))?0:1;//修改使用的磁盘时，$DiskIsChange=1
            $msg1="";
            for($i=0;$i<count($aryNewPath);$i++){
                //修改磁盘预留空间
                if($oldSpace[$i]!=$newSpace[$i]){
                    $update = array();
                    $update['N_LessFreeSpace'] = $newSpace[$i];
                    $rs = M('sys_diskspaceinfo')->where("V_DiskVolumeName='{$aryDisk[$i]}' and N_StationId='{$CurrentStation}'")->save($update);
                   
                    if(false !== $rs){
                        addSysLog("通过浏览器修改".$aryDisk[$i]."盘预留空间为".$newSpace[$i]."MB");
                        $msg1.="\\n成功修改".$aryDisk[$i]."盘预留空间为".$newSpace[$i]."MB";
                    }
                }
                //修改文件保存路径
                if($aryNewPath[$i]!=$aryOldPath[$i]){//修改了路径
                    $p=$aryNewPath[$i];         //新路径
                    $st=($p=="")?"-2":"-1";     //路径为空，状态设置为-2，非空并且已经修改，状态设置为-1
                    if($aryDisk[$i]!=substr($p,0,2) && ($p!="")){
                        JS_alert($aryDisk[$i]."盘路径错误，请在".$aryDisk[$i]."盘选择保存路径");
                        JS_tourl("MSQL_disk_paramConfig.php");
                        exit(0);
                    }else{             
                        if(strlen($p)<3 &&(substr($_POST["hid_UsingPath"],0,2)==$aryDisk[$i])){
                            JS_alert("当前使用磁盘不能设置为空");
                            JS_tourl("MSQL_disk_paramConfig.php");
                            exit(0);
                        }else{
                            if(substr($p,strlen($p)-1,1)!="\\" &&$p!=""){$p=$p."\\";}   //确保路径末尾带有“\”

                            if(CreatDir($p)){   //CreatDir：判断路径是否存在，不存在则尝试创建，创建成功返回1；失败则返回0。
                                
                                if($p!=""){ 
                                    $netpath="STD".$CurrentStation."_".formatNetPath($p);
                                }else{$netpath="";}
                                if($p!=""){ 
                                   // echo $UsingPath."==".$p;die;
                                    CreatNetPath($netpath,$p);  //修改某个磁盘的保存路径时，创建虚拟目录 add by lzh @2009-8-4
                                    if(substr($UsingPath,0,1)==substr($p,0,1)){
                                        //修改的路径是当前使用路径，需要将路径同步到tab_rec_recorddiskcfg
                                        $netpath="STD".$CurrentStation."_".formatNetPath($newDisk);
                           
                                        $del = M('rec_recorddiskcfg')->where('1')->delete();
                                        if(false !== $del){
                                            $insert = array();
                                            $insert['N_StationID'] = $CurrentStation;
                                            $insert['V_RecordDiskPath'] = $newDisk;
                                            $insert['V_NetPath'] = $netpath;
                                            $insert['N_UsingState'] = 1;
                                            $rs = M('rec_recorddiskcfg')->add($insert);

                                            if($rs){
                                                $tmp="通过浏览器修改当前使用磁盘为：".substr($newDisk,0,1);
                                                $RestartVCR=1;  //修改了录音磁盘，提示重启VCR服务
                                                addSysLog($tmp);
                                                $msg1.="\\r\\n".$tmp;
                                            }
                                        }
                                        
                                    }
                                }
                                $p=str_replace("\\","\\\\",$p);
                                $upd = array();
                                $upd['V_RcDiskPath4VPath'] = $p;
                                $upd['N_RcWavPathState'] = $st;
                                $upd['V_RcVPath'] = $netpath;
                                 
 
                                $uprs = M('sys_diskspaceinfo')
                                      ->where("V_DiskVolumeName='".$aryDisk[$i]."' and N_StationId='".$CurrentStation."'")
                                      ->save($upd);
                                 //echo M()->getLastSql(); 
                                if(false !== $uprs){
                                
                                    if($p!=""){
                                    addSysLog("通过浏览器修改".$aryDisk[$i]."盘录音保存路径为".$p);
                                    $RestartVCR=1;  //修改了录音磁盘，提示重启VCR服务
                                    $msg1=$msg1."\\n成功修改".$aryDisk[$i]."盘录音保存路径为".$p;
                                    }else{
                                        addSysLog("通过浏览器清空".$aryDisk[$i]."盘录音保存路径");
                                        $msg1=$msg1."\\n成功清空".$aryDisk[$i]."盘录音保存路径";
                                    }
                                } 
                            }else{ 
                                JS_alert("创建路径【{$p}】失败，请重新设置路径");
                            }
                        }
                        
                    }
                }
            }
            //修改录音文件保存磁盘
            if($DiskIsChange==1){

            //修改了文件保存磁盘，判断选用的磁盘是否设置了路径
                $netpath="STD".$CurrentStation."_".formatNetPath($newDisk);
                $del = M('rec_recorddiskcfg')->where('1')->delete();
                if(false !== $del){
                    $insert = array();
                    $insert['N_StationID'] = $CurrentStation;
                    $insert['V_RecordDiskPath'] = $newDisk;
                    $insert['V_NetPath'] = $netpath."/";
                    $insert['N_UsingState'] = 1;
                    $rs = M('rec_recorddiskcfg')->add($insert); 
                   
                    
                    if($rs){
                        $tmp="通过浏览器修改当前使用磁盘为：".substr($newDisk,0,1);
                        $RestartVCR=1;
                        addSysLog($tmp);
                        $msg1.="\\r\\n".$tmp;
                    }
                }
                //echo $sql;
            }
            
            if($msg1!=""){
                if($RestartVCR==1){
                    $service="";
                    $nSQL="select count(N_ChannelNo) from tab_sys_channelconfig where N_Channeltype=3";
                    //echo $nSQL;
                    $rsGaozu=mysql_query($nSQL);
                    if(mysql_result($rsGaozu,0,0)>0){
                        $service.="NEU_TECH_VCR、";
                    }
                    $msg1.="\\r\\r\\n【重启系统".$service."NEU_TECH_MED和NEU_TECH_SIG服务后生效】";
                }
                JS_alert($msg1);    
            }
        }

        //获取当前使用的录音路径及磁盘;
        
        $field=" a.N_StationID,CONCAT(V_RcDiskPath4VPath,DATE_FORMAT(Now(),'%Y%m'),'\\\\',DATE_FORMAT(Now(),'%Y%m%d'),'\\\\') as V_RecordDiskPath ";
        $rsUsing = M('sys_diskspaceinfo')->join("tab_rec_recorddiskcfg a")->field($field)->where("V_RecordDiskPath  like CONCAT(V_DiskVolumeName,'%')")->select();
        
        //获取当前工作站的磁盘列表
        if($CurrentStation==""){$CurrentStation=$rsUsing[0]['n_stationid'];}
        $rsDisk = M("sys_diskspaceinfo")->where("N_StationId='{$CurrentStation}'")->select();
        foreach ($rsDisk as $k=>$v){
            $rsDisk[$k]['diskval'] = substr($v["v_diskvolumename"],0,1);
            $rsDisk[$k]['yiyong'] = (100-round($v["n_freespace"]/$v["n_totalspace"]*100,2));
            $rsDisk[$k]['shenxia'] = round($v["n_freespace"]/$v["n_totalspace"]*100,2);
            $rsDisk[$k]['keyong'] = $v["n_freespace"]/1000;
            $rsDisk[$k]['currentdisk'] = substr($v["v_rcdiskpath4vpath"],0,3);
            if(1){

            }
        }
        
        
        $this->assign("CurrentStation",$CurrentStation);
        $this->assign("rsDisk",$rsDisk);
        $this->assign("rsUsing",$rsUsing); 
        $this->assign("UsingPath",$rsUsing[0]['v_recorddiskpath']);
        $this->assign("useingstation",substr($rsUsing[0]['v_recorddiskpath'],0,3));
        $this->assign("CurrentPage",'disk');
        $this->display();
    }
    

    //选择磁盘
    public function selectPath(){

        header("Content-type: text/html; charset=utf-8");
        $showFile=isset($_REQUEST["t"])?$_REQUEST["t"]:0; //=1，列出文件夹和文件。=0，只列出文件夹
        $DiskCanView=isset($_REQUEST["disk"])?$_REQUEST["disk"]:"";	//有权浏览的磁盘，为空则无限制
        $d=isset($_REQUEST["currentdisk"])?$_REQUEST["currentdisk"]:"";		//初始路径，为空时则为当前目录
        $Disk = $DiskCanView;
        if($d!=""){								//有传递初始路径，判断传入的是一个目录还是一个具体的文件
            if(strlen($d)==1){					//路径参数只有一个字符时，将其当盘符看待，并在后面增加":/"
                $d.=":\\";
            }elseif(strlen($d)==2){
                $d.="\\"	;
            }
            if(!is_dir($d) and !is_file($d)){	//指定的初始路径无效时，则设置路径为当前路径
                //echo $d;
                $d=$DiskCanView==""?"C:\\":($DiskCanView.":\\");
            }
        }else{
            $d=$DiskCanView==""?"C:\\":($DiskCanView.":\\");							//无指定参数时，默认打开C盘
        }
        if(substr($d,-1,1)!="\\"  and !is_file($d)){//路径最后一个字符不是“\”并且路径所指不是一个文件时，自动加上“\”
            $d.="\\";
        }
        
        //获取磁盘列表
        $disk=array("C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
        $n=0;
        for($i=0;$i<count($disk);$i++){
            if(is_dir($disk[$i].":\\")){
                if(is_readable($disk[$i].":\\")){
                    $Disks[$n++]=$disk[$i];
                }
                
            }
        }
        $d = iconv("utf-8","gbk",$d);
        if(!is_file($d)){
            $fList = scandir($d);//获取文件列表
        }else{
            $d=$DiskCanView==""?"C:\\":($DiskCanView.":\\");
            $fList = scandir($d);//获取文件列表
        }
        
        $i=0;
        $n=0;
        while (list($key, $val) = each($fList)){
            $val = iconv("gbk","utf-8",$val);
            $fType=filetype($d."\\".$val);
            if($fType=="dir"){
                $Folders[$n]=$val;
                $n++;
            }else{
                $Files[$i]=$val;
                $i++;
            }
        }
        $FoldersHtml ="";
        $FilesHtml ="";
        if(isset($Folders)){
            while (list($key, $val) = each($Folders)){
                $val = iconv("gbk","utf-8",$val);
                if(is_readable($d."\\{$val}")){
                    if($val!=".." and $val!="."){
                        //非一级目录时，PHP自动生成“.”和“..”两个路径，分别表示当前路径和上级路径，再次不显示这两个路径
                        //$path=rawurlencode ($d.$val);
                        $path=str_replace("\\","\\\\",$d.$val);
                  
                        $FoldersHtml .=  "<div  class=\"item active\"  title=\"{$val}\" onclick=\"to('t=$showFile&p=$path','$path')\">";
                        $FoldersHtml .=    "<em class='file-ico'></em>";
                        $FoldersHtml .=    "<div class='name'>{$val}</div>";
                        $FoldersHtml .=  "</div>";
                    }
                }
            }
        }
        if(isset($Files) and $showFile==1){
            while (list($key, $val) = each($Files)){
                $val = iconv("gbk","utf-8",$val);
                $FilesHtml = "<div class='file' name='div_file' title=\"{$val}\" onclick='onselect(this,\"{$val}\")' ><img src='/Public/assets/images/ico/xml.gif'><br />{$val}</div>";
            }
        }
      
      $this->assign("FoldersHtml",$FoldersHtml);
      $this->assign("FilesHtml",$FilesHtml);
      $this->assign("showFile",$showFile);
      $this->assign("Disks",$Disks);
      $this->assign("DiskCanView",$DiskCanView);
      $this->assign("d",$d);  
      $content =  $this->fetch('/System/selectPath'); 
      echo $content;exit;
    }
   
    
    //列出文件
    public function getFileList(){
        header("Content-type: text/html; charset=utf-8");
        header("Pragma: no-cache");
        flush();
        $d=isset($_GET["p"])?$_GET["p"]:"";						//初始路径，为空时则为当前目录
        $showFile=isset($_GET["t"])?$_GET["t"]:0; 				//=1，列出文件夹和文件。=0，只列出文件夹
        
        if($d!=""){								//有传递初始路径，判断传入的是一个目录还是一个具体的文件
            if(strlen($d)==1){					//路径参数只有一个字符时，将其当盘符看待，并在后面增加":/"
                $d.=":\\";
            }elseif(strlen($d)==2){
                $d.="\\"	;
            }
            if(!is_dir($d) and !is_file($d)){	//指定的初始路径无效时，则设置路径为当前路径
                $d="C:\\";
            }
        }else{
            $d="C:\\";							//无指定参数时，默认打开C盘
        }
        if(substr($d,-1,1)!="\\"){
            $d.="\\";
        }
      
        $d = iconv("utf-8","gbk",$d);
        if(is_dir($d)){
            if($dire = scandir($d)){
                foreach($dire as $val){
                    $val = iconv("gbk","utf-8",$val);
                      $fType=filetype($d."\\".$val);
                    if($fType=="dir"){
                        $val = iconv("utf-8","gb2312",$val);
                        $Folders[$n]=$val;
                        $n++;
                    }else{
                        $Files[$i]=$val;
                        $i++;
                    }  
                }
            }
        }
        
        if(isset($Folders)){
            $FoldersHtml = "";
            while (list($key, $val) = each($Folders)){
                $val = iconv("utf-8","gb2312",$val);
                if(is_readable($d."\\{$val}")){
                    if($val!=".." and $val!="."){
                        //非一级目录时，PHP自动生成“.”和“..”两个路径，分别表示当前路径和上级路径，再次不显示这两个路径
                        $path=rawurlencode ($d.$val);
                        $path=str_replace("\\","\\\\",$d.$val);
                        /*echo "<div class='file' name='div_folde' title=\"{$val}\" ";
                        echo "onclick=\"to('t={$showFile}&p={$path}','{$path}')\">";
                        echo "<img src='/Public/assets/images/ico/folder.gif'><br />{$val}</div>\r\n";*/

                        $FoldersHtml .=  "<div  class=\"item active\"  title=\"{$val}\" onclick=\"to('t=$showFile&p=$path','$path')\">";
                        $FoldersHtml .=    "<em class='file-ico'></em>";
                        $FoldersHtml .=    "<div class='name'>{$val}</div>";
                        $FoldersHtml .=  "</div>";
                       // 
                    }
                }
                
            }
        }
        if(isset($Files) and $showFile==1){
            while (list($key, $val) = each($Files)){
               $FoldersHtml .=  "<div class='file' name='div_file' title=\"{$val}\" onclick='onselect(this,\"{$val}\")' ><img src='/Public/assets/images/ico/xml.gif'><br />{$val}</div>";
            }
        }
        echo $FoldersHtml;				
    }
    
    //IP限制
    public function ipLimint(){
        $start = isset($_REQUEST['start'])?$_REQUEST['start']:0;
        $length = isset($_REQUEST['length'])?$_REQUEST['length']:10;
        $txtSch =$this->getParam("txtSch");
        $where = array();
        if($txtSch){
            $where["txtSch"] = array("like",$txtSch);
  
        }
        if(IS_AJAX){
            $newIP = $this->getParam("newIP");
            if($newIP){
                $has = M('sys_allowip')->where("ip ='{$newIP}'")->find();
                $addData['IP'] = trim($newIP);
                $addData['flag'] = 1;
                if(!$has){
                  $add = M('sys_allowip')->add($addData);
                }
            }
            $cnt = M('sys_allowip')->where($where)->field("count(*) cnt")->find();
            $total = $cnt['cnt'];
            $rs = M('sys_allowip')->where($where)->order('ip')->limit($start,$length)->select();
            foreach ($rs as $k=>$v)
            {
                $selected = "";
                if($v['flag']){
                    $selected ="selected";
                }
                $ip = base64_encode($v['ip']);
                $select ="<select onchange=\"upd(this.options[this.selectedIndex].value,'{$ip}')\" name=flag[]>".
                                "<option value=0 {$selected}>禁止访问</option>".
                                "<option value=1 {$selected}>允许访问</option>".
                         "</select>";
                $rs[$k]['type'] = $select;
            }
            $output['aaData'] = $rs;
            $output['iTotalDisplayRecords'] = $total;    //如果有全局搜索，搜索出来的个数
            $output['iTotalRecords'] = $total; //总共有几条数据
            echo json_encode($output); //最后把数据以json格式返回
            die;
        }
        $this->assign("CurrentPage",'ip');
        $this->display();
    }
    
    public function updateIP()
    {
        if(IS_POST){
            $AppResult = new AppResult();
            $updflag = $this->getParam("updflag");
            $updip = $this->getParam("updip");
            $updip = base64_decode($updip);
            $sql="update tab_sys_allowip set flag='{$updflag}' where ip='{$updip}'";
            $rs = M()->execute($sql);
            if(false !== $rs){
                $msg="修改IP[$updip]权限为：".($updflag?"允许访问":"禁止访问");
                addSysLog($msg);
                $AppResult->code = 1;
                $AppResult->message = "修改成功";
                $AppResult->data = "";
            }
            else{
                $AppResult->code = 0;
                $AppResult->message = "修改失败";
                $AppResult->data = "";
            }
            $AppResult->returnJSON();
        }
    }
    
    public function delIP(){
        if(IS_POST){
            $AppResult = new AppResult();
            $id = $this->getParam("id");
            $ip = $this->getParam("ip");
            
            if(empty($ip)){
                $AppResult->code = 0;
                $AppResult->message = "请选中要删除的记录";
                $AppResult->data = "";
            }
            
            $rs = M('sys_allowip')->where("id= '{$id}'")->delete();
            if(false !== $rs){
                addSysLog("通过浏览器删除可访问IP：".base64_decode($ip));
                $AppResult->code = 1;
                $AppResult->message = "删除成功";
                $AppResult->data = "";
            }
            $AppResult->returnJSON();
        }
    }
    
    
    //工作站管理
    public function station(){
        $sEcho = $_REQUEST['sEcho']; // DataTables 用来生成的信息
        $output['sEcho'] = $sEcho;
        $start = isset($_REQUEST['start'])?$_REQUEST['start']:0;
        $length = isset($_REQUEST['length'])?$_REQUEST['length']:10;
        if(IS_AJAX){
            $field="n_sid,v_sname,v_rnum,v_memo";
            $where=array();
            $M = M('station');
            $cnt = $M->field($field)->where($where)->select();
            //echo M()->getLastSql();
            $total = count($cnt);
            $rs = $M->field($field)->where($where)->limit($start,$length)->select();
            if($rs){
                foreach ($rs as $k=>$v){
                    $Managestr = "";
                    $accountstr = "";
                    $accountidstr = "";
 
                    $Managestr .= " <a href='stationEdit?N_SID={$v['n_sid']}'>编辑</a>　|　";
                    $Managestr .= "<a href=\"javascript:del({$v['n_sid']},'{$v['v_sname']}')\">删除</a>";
                    $rs[$k]['out_manage'] = $Managestr;
                }
            }
            
            $output['aaData'] = $rs;
            $output['iTotalDisplayRecords'] = $total;    //如果有全局搜索，搜索出来的个数
            $output['iTotalRecords'] = $total; //总共有几条数据
            echo json_encode($output); //最后把数据以json格式返回
            die;
        }
        $this->assign("CurrentPage",'station');
        $this->assign("CurrentPage",'userManage');
        $this->display();
    }
    
    //添加工作站
    public function stationAdd(){
        if(IS_POST){
            $AppResult = new AppResult();
            $stationName = $this->getParam("stationName");
            $recordNum = $this->getParam("recordNum");
            $remark = $this->getParam("remark");
            $has = M('station')->where("v_sname = '".MySQLFixup($stationName)."'")->find();
            
            if($has){
                $AppResult->code = 0;
                $AppResult->message = "添加失败,系统已存在同名工作站".$stationName."！";
                $AppResult->data = "";
                
            }else{
                $data = array();
                $data['V_Sname'] = $stationName;
                $data['V_Rnum'] = $recordNum;
                $data['V_memo'] = $remark;
                $rs = M('station')->add($data);
           
                if($rs){
                    addSysLog("通过浏览器新增工作站:".MySQLFixup($stationName));
                    $AppResult->code = 1;
                    $AppResult->message = "成功添加工作站:".$stationName;
                    $AppResult->data = "";
                }
                else{
                    $AppResult->code = 0;
                    $AppResult->message = "新增失败,数据库操作失败";
                    $AppResult->data = "";
                }
            }
            $AppResult->returnJSON();
        }
        $rs = M('station')->field("N_SID,V_SNAME,V_RNUM,V_MEMO")->order('V_SNAME')->select();
        
        $this->assign("station",$rs);
        $this->assign("CurrentPage",'station');
        $this->display();
    }
    
    //编辑工作站
    public function stationEdit(){
        $AppResult = new AppResult();
        $n_sid = $this->getParam('N_SID');
        $has = M('station')->where("N_SID = '".$n_sid."'")->find();
        
        if($has){
            $AppResult->code = 0;
            $AppResult->message = "非法操作,工作站不存在";
            $AppResult->data = "";
            
        }
        
        if(IS_POST){
            $AppResult = new AppResult();
            $stationName = $this->getParam("stationName");
            $recordNum = $this->getParam("recordNum");
            $remark = $this->getParam("remark");
            $stationID = $this->getParam('stationID');
            $where = array();
            $data = array();
            $where['N_SID'] = $stationID;
            $data['V_Sname'] = $stationName;
            $data['V_Rnum'] = $recordNum;
            $data['V_memo'] = $remark;
            $rs = M('station')->where($where)->save($data);
           
            if(false !== $rs){
                addSysLog("通过浏览器编辑工作站:".MySQLFixup($stationName));
                $AppResult->code = 1;
                $AppResult->message = "成功编辑工作站:".$stationName;
                $AppResult->data = "";
            }
            else{
                $AppResult->code = 0;
                $AppResult->message = "编辑失败,数据库操作失败";
                $AppResult->data = "";
            }

            $AppResult->returnJSON();
        }
        $rs = M('station')->field("N_SID,V_SNAME,V_RNUM,V_MEMO")->where($where)->order('V_SNAME')->find();
        
        $this->assign("station",$rs);
        $this->assign("CurrentPage",'station');
        $this->display();
    }
    
   //工作站删除  
    public function stationDel(){
        if(IS_POST){
            $AppResult = new AppResult();
            $sid = $this->getParam("sid");
            $stationName = $this->getParam("stationName");
            
            if(empty($sid)){
                $AppResult->code = 0;
                $AppResult->message = "请选中要删除的工作站";
                $AppResult->data = "";
            }
            
            $rs = M('station')->where("N_SID= '{$sid}'")->delete();
            if(false !== $rs){
                $msg="成功删除工作站:".$stationName;
                addSysLog($msg);
                $AppResult->code = 1;
                $AppResult->message = "删除成功";
                $AppResult->data = "";
            }
            $AppResult->returnJSON();
        }
    }
    
    //操作日志
    public function operationLog(){
        if(IS_AJAX){
            $start = isset($_REQUEST['start'])?$_REQUEST['start']:0;
            $length = isset($_REQUEST['length'])?$_REQUEST['length']:10;
            $sMode = $this->getParam("sMode");
            $keyWord = $this->getParam("keyWord");
            $keyWord = MySQLFixup($keyWord);
             
            $where= array();
            if($keyWord !="" && $sMode !=''){
                if($sMode=="D_LogTime"){
                    $where="  D_LogTime between '$keyWord 00:00:00' and  '$keyWord 23:59:59' ";
                }elseif($sMode=="V_AccountName"){
                    $where=" V_AccountId in(select V_AccountId from tab_sys_accountinfo where V_AccountName like '%".$keyWord."%')";
                }else{
                    $where=" $sMode like '%".$keyWord."%'";
                }
            }
            
            $cnt = M('sys_accountlog')->field("count(*) cnt ")->where($where)->find();
            $total = $cnt['cnt'];
            $rs = M('sys_accountlog')->where($where)->order("N_LogID desc")->limit($start,$length)->select();
             
            $output['aaData'] = $rs;
            $output['iTotalDisplayRecords'] = $total;    //如果有全局搜索，搜索出来的个数
            $output['iTotalRecords'] = $total; //总共有几条数据
            echo json_encode($output); //最后把数据以json格式返回
            die;
        }
        
        $this->assign("CurrentPage",'operationLog');
        $this->display();
    }
    
    
    //告警日志  
    public function alarmLog(){
        $s_time = date("Y-m-d",strtotime("-300 day"))." 00:00:00";
        $e_time = date("Y-m-d",time())." 23:59:59";
        if(IS_AJAX){
            $start = isset($_REQUEST['start'])?$_REQUEST['start']:0;
            $length = isset($_REQUEST['length'])?$_REQUEST['length']:10;
            $datetimepicker_start = isset($_REQUEST['datetimepicker_start'])?$_REQUEST['datetimepicker_start']:$s_time;
            $datetimepicker_end = isset($_REQUEST['datetimepicker_end'])?$_REQUEST['datetimepicker_end']:$e_time;
            $ClearFlag = $this->getParam("ClearFlag",0);
            $Level = $this->getParam("Level");
            $toClear = $this->getParam("toClear");
            
            $where['D_LogTime'] = array("between","{$datetimepicker_start} , $datetimepicker_end");
            if($ClearFlag !=-1){
                $where['N_ClearFlag'] = $ClearFlag;
            }
            if($Level != ""){
                $where['N_Level'] = $Level;
            }
            
            
            $cnt = M('sys_alarmlog')->field("count(*) cnt ")->where($where)->find();
           // echo M()->getLastSql();
            $total = $cnt['cnt'];
            $rs = M('sys_alarmlog')->where($where)->limit($start,$length)->select();
            if($rs)
            {
                foreach ($rs as $k=>$v){
                    if($v['n_clearflag'] == 0){
                        $rs[$k]['n_zt'] = "<a href=\"javascript:cl('{$v['n_sn']}')\" style='color:#f00' title='点击消除告警'>未消除</a>";
                    }
                    else{
                        $rs[$k]['n_zt'] = "已消除";
                    }
                    $rs[$k]['n_nr'] = $this->Level($v['n_level']);
                    $UserInfo = getUserInfo($v['n_clearuserid']);
                    $rs[$k]['username'] = $UserInfo['v_accountname'];
                }
            }
            $output['aaData'] = $rs;
            $output['iTotalDisplayRecords'] = $total;    //如果有全局搜索，搜索出来的个数
            $output['iTotalRecords'] = $total; //总共有几条数据
            echo json_encode($output); //最后把数据以json格式返回
            die;
        }
        
        $this->assign("datetimepicker_start",$s_time);
        $this->assign("datetimepicker_end",$e_time);
        $this->assign("CurrentPage",'alarmLog');
        $this->display();
    }
    
    
    
    public function getChannelInfoBychannelNo($channelNo,$col)
    {
        $where = array();
        $where['N_ChannelNo'] = $channelNo;
        $where['V_ParamsName'] = $col;
        $rs = M("sys_paramschannel")->where($where)->find();
        $data = array();
        if($rs){
            $data = $rs;
        }
        return $data;
    }
    
    public function showParaObj($pn,$pv,$mlv){
        $obj="";
        $i=0;
        switch($pn){
            case "Sys_AutoBakData"://自动备份周期(天)
                $obj="<select name='{$pn}' id='{$pn}' class='w'>";
                for(;;){
                    $i=$i+5;
                    if($i>60){break;}
                    $obj.="<option value='{$i}'".selected($i,$pv).">".$i."</option>";
                }
                $obj.="</select>";
                break;
            case "Sys_AutoBakPeriod":
                $obj="<select name='{$pn}' id='{$pn}'  class='w'>";
                for(;;){
                    $i=$i+5;
                    if($i>60){break;}
                    $obj.="<option value='{$i}'".selected($i,$pv).">".$i."</option>";
                }
                $obj.="</select>";
                break;
            case "Sys_EnableBackup":		//是否允许自动备份,是否允许删除已经锁定的文件
                $obj="<select name='{$pn}' id='{$pn}' class='w'>";
                $obj.="<option value='0'".selected(0,$pv).">不允许</option>";
                $obj.="<option value='1'".selected(1,$pv).">允许</option>";
                $obj.="</select>";
                break;
            case "Sys_AutoBakDir":
            case "Sys_AutoBakDataPath":
                //$obj="<input type='text' onblur=\"check(this,'{$pn}')\" ";
                //已禁止直接输入路径，可确保路径格式，无需再次检查
                $obj="<input type='text' readonly  class='txtBox w' ";
                $obj.=" value=".$pv." name='{$pn}' id='{$pn}' ";
                if($mlv==0){//只读参数，则不允许修改。点击控件时不弹出路径选择
                    $obj.=" onclick=\"SetPath(this,0)\"";
                }else{
                    $obj.=" title='此参数禁止修改' style='color:#999;' onfocus='this.blur()'";
                }
                $obj.=" />";
                break;
            case "Sys_EnableDelLockFile":	//是否允许自动备份,是否允许删除已经锁定的文件
                $obj="<select name='{$pn}' id='{$pn}' class='w'>";
                $obj.="<option value='0'".selected(0,$pv).">不允许</option>";
                $obj.="<option value='1'".selected(1,$pv).">允许</option>";
                $obj.="</select>";
                break;
            case "Sys_AutoBakClock":	//自动备份时间，只允许选择凌晨3点至5点备份
                $obj="<select name='{$pn}' id='{$pn}' class='w'>";
                $obj.="<option value='3:00'".selected("3:00",$pv).">03:00</option>";
                $obj.="<option value='4:00'".selected("4:00",$pv).">04:00</option>";
                $obj.="<option value='5:00'".selected("5:00",$pv).">05:00</option>";
                $obj.="</select>";
                break;
            default:
                //$obj="<input type='text' onblur=\"check(this,'{$pn}','".$arr[$pn]["Regex"]."','".$arr[$pn]["ErrMsg"]."')\" ";
                $obj="<input type='text' maxlength='20' onblur=\"check(this,'{$pn}')\"  class='txtBox' ";
                $obj.=" value='{$pv}' name=".$pn.canModify($mlv)." id=".$pn.canModify($mlv)." class='w'/>";
                //				.$arr["Sys_AutoBakClock"]["ErrMsg"]."','".$arr["Sys_AutoBakClock"]["ErrMsg"].
        }
        return $obj;
    }
    
    
    //根据参数名称，生成文本框或者下拉框
    public function getInput($paraName,$v,$mlv,$V_Verify){
        $tmp="";
        switch($paraName){
            case "ChannelUserDisable":
                $tmp="<select name='{$paraName}'  class='w'>";
                $tmp.="<option value=0".selected($v,0).">启用　</option>";
                $tmp.="<option value=1".selected($v,1).">禁用　</option>";
                $tmp.="</select>";
                break;
            case "RecordFileFormat":
                //如果不是录音通道就不可以显示其他可选项
                $tmp="<select name='{$paraName}'  class='w'>";
                $tmp.="<option value=6".selected($v,6).">A-率</option>";
                $tmp.="<option value=7".selected($v,7).">μ-率　</option>";
                $tmp.="<option value=49".selected($v,49).">GSM　</option>";
                //$tmp.="<option value=85".selected($v,85).">MP3　</option>";
                $tmp.="</select>";
                break;
            case "RecordMixerSwitch":
                $tmp="<select name='{$paraName}'  class='w'>";
                $tmp.="<option value=0".selected($v,0).">关</option>";
                $tmp.="<option value=1".selected($v,1).">开</option>";
                $tmp.="</select>";
                break;
            case "RecordMode":
                $tmp="<select name='{$paraName}'  class='w'>";
                $tmp.="<option value=1".selected($v,1).">声控</option>";
                $tmp.="<option value=2".selected($v,2).">压控　</option>";
                $tmp.="</select>";
                break;
            case "KeyMonFlag":
                $tmp="<select name='{$paraName}'  class='w'>";
                $tmp.="<option value=0".selected($v,0).">不启动</option>";
                $tmp.="<option value=1".selected($v,1).">启动</option>";
                $tmp.="</select>";
                break;
            case "RecordFileFormat":
                $tmp="<select name='{$paraName}'  class='w'>";
                $tmp.="<option value=6".selected($v,6).">A-率</option>";
                $tmp.="<option value=7".selected($v,7).">μ-率</option>";
                $tmp.="<option value=49".selected($v,49).">GSM</option>";
                $tmp.="<option value=85".selected($v,85).">MP3</option>";
                $tmp.="</select>";
                break;
            case "PlayFileFormat":
                $tmp="<select name='{$paraName}'  class='w'>";
                $tmp.="<option value=6".selected($v,6).">A-率</option>";
                $tmp.="<option value=7".selected($v,7).">μ-率</option>";
                $tmp.="<option value=49".selected($v,49).">GSM</option>";
                $tmp.="<option value=85".selected($v,85).">MP3</option>";
                $tmp.="</select>";
                break;
            default:
                $tmp="<input type='text' maxlength='20' class='txtBox' title='{$V_Verify}' onblur='CheckVerify(this)'";
                $tmp.="name='{$paraName}' value='".$v.canModify($mlv)."' class='w'/>";
        }
        return $tmp;
    }
    
   public function Level($l){
        switch($l){
            //case 1:
            //	return "LOG_EMERG";		// system is unusable
            //case 2:
            //	return "LOG_ALERT";		// action must be taken immediately
            //case 3:
            //	return "LOG_CRIT";		// critical conditions
            case 4:
                return "LOG_ERROR";		// error conditions
                //case 5:
                //	return "LOG_WARNING";	// warning conditions
            case 6:
                return "LOG_NOTICE";	// normal but significant condition
            case 7:
                return "LOG_INFO";		// informational
                //case 8:
                //	return "LOG_DEBUG";		// debug-level messages
            case 0:
                return "未知";
        }
    }


//根据参数名称，生成文本框或者下拉框 //,$V_Verify
public function parame_input($paraName,$v,$checkbox,$channelNo){
    $tmp="";
    global $vedioMaxCount;
    switch($paraName){
        case "IsChkDTMF":
            $tmp="<select name=\"".$paraName."[]\">";
            $tmp=$tmp."<option value=\"1\" ".selected($v,"1").">检测</option>";
            $tmp=$tmp."<option value=\"0\" ".selected($v,"0").">不检测</option>";
            $tmp=$tmp."</select><input name=\"hid_{$paraName}[]\" type='hidden' value=\"{$v}\">";
            break;
        case "ChannelUserDisable":
            $tmp="<select name=\"".$paraName."[]\">";
            $tmp=$tmp."<option value=\"1\" ".selected($v,"1").">禁用</option>";
            $tmp=$tmp."<option value=\"0\" ".selected($v,"0").">启用</option>";
            $tmp=$tmp."</select><input name=\"hid_{$paraName}[]\" type='hidden' value=\"{$v}\">";
            break;
        case "IsVideoChannel"://录象通道参数处理 处理原理：用'checkbox'.$paraName存储当前状态，通过点击修改checkbox框修改其值，'hid'.$paraName存储初始状态
           // $tmp="<input type='checkbox' name=\"".$paraName."\"  ".checked($v,"1")." value='$v'  onclick=\"Checkbox('$paraName',$checkbox,$vedioMaxCount);\"/>";
           // $tmp.="<input name=\"hid_{$paraName}\" type='hidden' value=\"{$v}\">";//checkbox".$paraName为checkbox修改后的隐藏变量

            $tmp="<div class='ckbox ckbox-primary'>";  
            $tmp.="<input type='checkbox'  name='{$paraName}_{$checkbox}'  ".checked($v,"1")." value='1' id='int_{$channelNo}' />";
            $tmp.="<label for='int_{$channelNo}'></label></div>";
            $tmp.="<input name='hid_{$paraName}_{$checkbox}' type='hidden' value='{$v}'>";

            break;  
        case "RecordMode":
//          RM_SIGNAL = 0,  //信令，用户数字中继监控录音
//          RM_BARGEIN = 1, //声控，模拟声控
//          RM_PICKUP = 2,  //压控，模拟压控
//          RM_CTI = 3,     //CTI，交换机CTI控制，泛指华谱，包括模拟和数字复接卡
//          RM_DRCU = 4,    //DRCU，广哈DRCU板，DTMF协议控制
//          RM_SOFTIP = 5,  //软交换IP录音
//          RM_CDR = 11,    //CDR，交换机CDR控制

            $tmp=$tmp."<select name=\"".$paraName."[]\" >";
            $tmp=$tmp."<option value=\"0\" ".selected($v,"0").">无</option>";
            $tmp=$tmp."<option value=\"1\" ".selected($v,"1").">声控</option>";
            $tmp=$tmp."<option value=\"2\" ".selected($v,"2").">压控</option>";
            $tmp=$tmp."<option value=\"3\" ".selected($v,"3").">CTI</option>";
            $tmp=$tmp."<option value=\"4\" ".selected($v,"4").">DRCU</option>";
            $tmp=$tmp."<option value=\"5\" ".selected($v,"5").">软交换</option>";
            $tmp=$tmp."<option value=\"11\" ".selected($v,"11").">CDR</option></select><input name=\"hid_{$paraName}[]\" type='hidden' value=\"{$v}\">";
            break;
        default:
        
        if($paraName=="ExtPhoneNumber"){
            $len=12;//通道号码仅能使用12位
        }else{$len=20;}
        $tmp="<input type=\"text\" maxlength='".$len."' style='width: 150px;' class='txtBox w' name=\"".$paraName."[]\" value=\"".$v."\" />
        <input name=\"hid_{$paraName}[]\"  type='hidden' value=\"{$v}\">";
    }
    return $tmp;
}


}