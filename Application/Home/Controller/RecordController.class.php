<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
use Think\Log;
use Think\Upload;
use Home\Controller\AppResult;
class RecordController extends AuthController {
    public function __construct(){
        parent::__construct();
    }
    public function index(){
        $this->display();
    }
    

    public function recordList(){
        $s_time = date("Y-m-d",time())." 00:00:00";
        $e_time = date("Y-m-d",time())." 23:59:59";
         
        if(IS_AJAX){
            $start = isset($_REQUEST['start'])?$_REQUEST['start']:0;
            $length = isset($_REQUEST['length'])?$_REQUEST['length']:10;
            $SearchMode = isset($_REQUEST['SearchMode'])?$_REQUEST['SearchMode']:0;
            $search_key = $_REQUEST['search_key'];
            $has_video = $_REQUEST['has_video'];
            $stime = isset($_REQUEST['stime'])?$_REQUEST['stime']:1;
            $InOut = $_REQUEST['InOut'];
            $chk_CN = $_REQUEST['chk_CN'];
            $datetimepicker_start = isset($_REQUEST['datetimepicker_start'])?$_REQUEST['datetimepicker_start']:$s_time;
            $datetimepicker_end = isset($_REQUEST['datetimepicker_end'])?$_REQUEST['datetimepicker_end']:$e_time;
            //$name = $_REQUEST['search']['value'];
            $m = M("rec_cdrinfo");
            $m_bak = M("rec_bakinfo");
            
            $sEcho = $_REQUEST['sEcho']; // DataTables 用来生成的信息
            $output['sEcho'] = $sEcho;
            $field="N_SN,N_ChannelID,D_StartTime,D_StopTime,V_VoiceFile,N_FileFormat,";
            $field.="N_CallDirection,V_Caller,V_Called,V_Ext,N_BackUp,N_Lock,N_Alarm,";
            $field.="N_AvoidRec,N_DeleteFlag,N_Locker,V_Path,V_NetPath,";
            $field.="V_Diverter,V_Diverted,N_IsTalk,ReMark ";
            
            
            $where  .=  " D_StartTime between '{$datetimepicker_start}' and '$datetimepicker_end' ";
            
            if(!empty($chk_CN) && $chk_CN != 'null'){
                $where  .= " and N_ChannelID in ($chk_CN)";
            }
          
            if($search_key != ""){//通话号码(姓名)的取值，0则号码，1则姓名
                if($SearchMode ==1){
                    $key=getNumByName($search_key);
                    if($key==""){
                        $where .= ' and N_SN = -1';//姓名没匹配到号码，直接设置一个不成立的条件
                    }else{
                        $where .= " and (V_caller in ('{$key}') or V_called in('{$key}') or v_ext in('{$key}')) ";
                    }
                }else{
                    $where .= " and (V_caller like '".$search_key."%' or V_called like '".$search_key."%'  or v_ext like '".$search_key."%')";
                }
                //echo $where;die;
            }
            if ($has_video==1) {//只查有录象的记录 
                $sql = "select `recid` from tab_ved_cdrinfo union select `recid` from tab_ved_bakinfo order by `recid` ";
                $res = M()->query($sql);
                $recid = "";
                if($res){
                    foreach ($res as $v){
                        if($recid == ""){
                            $recid = $v['recid'];
                        }
                        else 
                        {
                            $recid = $recid .",".$v['recid'];
                        } 
                    }
                }
                if(!empty($recid)){
                   $where .= " and N_SN in ($recid)"; 
                }
                else{
                   $where .= " and N_SN = -1 ";
                }                 
            }
            //echo $where;
            //按拨叫方向查询
            if($InOut != 2){
                $where .= " and N_CallDirection = '{$InOut}' ";
            }
            $where.=" and  (unix_timestamp(D_StopTime)-unix_timestamp(D_StartTime))>=$stime ";
            $CntNoBackup=$m->field("count(*) cnt")->where($where)->find();	//未备份部分数据
            $CntNoBackup=$CntNoBackup['cnt'];
            $CntBackup = $m_bak->field("count(*) cnt")->where($where)->find();	//已备份部分数据
            $CntBackup=$CntBackup['cnt'];
            $total = (int)$CntNoBackup + (int)$CntBackup;
            $rs = array();
            if($CntBackup==0)
            {
                //无已备份数据，读取未备份数据即可
                $rs= $m->field($field)->where($where)->limit($start,$length)->order("D_StartTime desc")->select();
            }
            else
            {
                if($CntNoBackup==0)
                {
                    //有已备份数据，无未备份数据。读取已备份份数据即可
                    $rs= $m_bak->field($field)->where($where)->limit($start,$length)->order("D_StartTime desc")->select();
                }
                else 
                {
                    //未备份和已备份都有，需要读取两张表
                    //计算未备份数据有多少页$NotBak_pagecnt
                    if($CntNoBackup % $length==0){
                        $NotBak_pagecnt=($CntNoBackup / $length);
                    }else{
                        $NotBak_pagecnt=(int)($CntNoBackup/$length)+1;
                    } 
                    $NumNotShowed = $CntNoBackup - $start;	//未备份数据还有多少条未显示
                    
                    if($NumNotShowed>0){
                        if($NumNotShowed >$length || $NumNotShowed == $length)
                        {
                            $rs= $m->field($field)->where($where)->limit($start,$length)->order("D_StartTime desc")->select();
                        }
                        else
                        {
                            //未备份数据不足一页的数据，需要同时读取备份与未备份数据
                            $rsNoBak= $m->field($field)->where($where)->limit($start,$length)->order("D_StartTime desc")->select();
                            $rsBak= $m_bak->field($field)->where($where)->limit(0,($length-$NumNotShowed))->order("D_StartTime desc")->select();
                            $rs= array_merge($rsNoBak,$rsBak); 
                        }
                   }
                   else
                   {
                       //$NumNotShowed=0或者$NumNotShowed<0,则未备份录音已经显示完毕，仅查询备份表(tab_rec_bakinfo)即可
                       $rs= $m_bak->field($field)->where($where)->limit($start-$CntNoBackup,$length)->order("D_StartTime desc")->select();
                   }
                }    
            }
           
            if($rs){ 
                foreach ($rs as $k=>$v)
                {
                    $fileplay ="<i class='fa fa-volume-up ' style=\"cursor:pointer\" onclick=\"play('".$v["n_sn"]."','".$v["v_voicefile"]."')\"></i>";
                    
                    $rs[$k]['n_channelinfo'] = getChannelNameById($v['n_channelid']);
                    $rs[$k]['v_caller'] = getNameByPhoneNum($v['v_caller']);
                    $rs[$k]['v_called'] = getNameByPhoneNum($v['v_called']);
                    $rs[$k]['v_ext'] = getNameByPhoneNum($v['v_ext']);
                    $rs[$k]['n_calldirection'] = $v['n_calldirection']==1?'拨出':'来电';
                    $rs[$k]['longtime'] = DateDiff($v['d_starttime'],$v['d_stoptime']);
                    $rs[$k]['v_voicefileplay'] = $fileplay;
                    $rs[$k]['local_video'] = getVideoByRecID($v['n_sn'],0);
                    $rs[$k]['remote_video'] = getVideoByRecID($v['n_sn'],1);
                    $rs[$k]['translate'] = get_translate($v['n_recid']);
                }
            }

            
            $output['aaData'] = $rs;
            $output['iTotalDisplayRecords'] = $total;    //如果有全局搜索，搜索出来的个数
            $output['iTotalRecords'] = $total; //总共有几条数据
            echo json_encode($output); //最后把数据以json格式返回
            die;
        }
        
        //列出所有通道
        $rsChno = M('drcu_circfg')->order('dispchno')->field("distinct chno")->select();
        if($rsChno){
            foreach ($rsChno as $key => $value) {
                 $rsChno[$key]['channelname'] = $this->getIDByName($value['chno']);
            }
        }
        $this->assign("rsChno",$rsChno);
        
        $this->assign("datetimepicker_start",$s_time);
        $this->assign("datetimepicker_end",$e_time);
        $this->assign("CurrentPage",'recordList');
        $this->display();
    }
    
   public function getIDByName($id){
        $rs = M("sys_paramschannel")->field("V_Value")->where("N_ChannelNo='{$id}' and V_ParamsName ='ChnName'")->find();
        if($rs){
            $str = empty($rs["v_value"])?$id:$rs["v_value"];
        }
        else{
            $str = $id;
        }
        return $str;
    
    }

    public function recordCount(){
        $s_time = date("Y-m-d",time())." 00:00:00";
        $e_time = date("Y-m-d",time())." 23:59:59";
        $datetimepicker_start = isset($_REQUEST['datetimepicker_start'])?$_REQUEST['datetimepicker_start']:$s_time;
        $datetimepicker_end = isset($_REQUEST['datetimepicker_end'])?$_REQUEST['datetimepicker_end']:$e_time;
        $SearchMode = isset($_REQUEST['SearchMode'])?$_REQUEST['SearchMode']:1;
        
        if(IS_AJAX){
            $sEcho = $_REQUEST['sEcho']; // DataTables 用来生成的信息
            $output['sEcho'] = $sEcho;
            $start = isset($_REQUEST['start'])?$_REQUEST['start']:0;
            $length = isset($_REQUEST['length'])?$_REQUEST['length']:10;
            
            
             
             
            //如没设定开始日期，则默认为上个月的今天
            $bTime=isset($_POST["bTime"])?$_POST["bTime"]:$lastmonth = date("Y-m-d",mktime(0,0,0,date("m")-1 ,date("d"),date("Y")));
            //如没设定开始日期，则默认为昨天
            $eTime=isset($_POST["eTime"])?$_POST["eTime"]:date("Y-m-d",mktime(0,0,0,date("m") ,date("d")-1,date("Y")));
            
            $where  = " D_StartTime >= '{$datetimepicker_start}' and D_StartTime <= '{$datetimepicker_end}' ";
            $where  .= " and D_StopTime != '0000-00-00 00:00:00' ";
            
            if($SearchMode == 1)
            {
                
                $field = "N_ChannelID,Count(N_SN) as 'cAmount',Sum(ABS(TIMESTAMPDIFF(SECOND,D_StartTime,D_StopTime))) as cSecond";
                $cnt = M('rec_cdrinfo')->field("N_ChannelID ")->where($where)->group('N_ChannelID')->select();
                $total = count($cnt);
                
                $rs = M('rec_cdrinfo')->field($field)->where($where)->group('N_ChannelID')->limit($start,$length)->select();
                //echo M()->getLastSql();
                if($rs){
                    foreach ($rs as $k=>$v){
                        $rs[$k]['recordtime'] = formatTime($v["csecond"]);
                        $data = $this->videoSimpleCount($v['n_channelid'],$where);
                        $rs[$k]['vtimes'] = $data['vtimes'];
                        $rs[$k]['vsecond'] = $data['vsecond'];
                    }
                }
               
            }
            else
            {
                
                $field = "*";
                $cnt = M('rec_cdrinfo')->field("count(*) cnt ")->where($where)->find();
                $total = $cnt['cnt'];
                $rs = M('rec_cdrinfo')->field($field)->where($where)->limit($start,$length)->select();
               
                if($rs){
                    foreach ($rs as $k=>$v){
                        $rs[$k]['longtime'] = DateDiff($v['d_starttime'],$v['d_stoptime']);
                        $rs[$k]['n_calldirection'] = $v['n_calldirection']==1?'拨出':'来电';
                    }
                }
            }
            
            $output['aaData'] = $rs;
            $output['iTotalDisplayRecords'] = $total;    //如果有全局搜索，搜索出来的个数
            $output['iTotalRecords'] = $total; //总共有几条数据
            echo json_encode($output); //最后把数据以json格式返回
            die;
        }
        $this->assign("SearchMode",$SearchMode);
        $this->assign("datetimepicker_start",$datetimepicker_start);
        $this->assign("datetimepicker_end",$datetimepicker_end);
        $this->assign("CurrentPage",'recordCount');
        $this->display();
    }

	public function diskManerage(){
		if(IS_AJAX){
			$sEcho = $_REQUEST['sEcho']; // DataTables 用来生成的信息
            $output['sEcho'] = $sEcho;
            
            $out['D_id'] = 1;
            $out['D_name'] = 'C:/';
            $out['D_used'] = '500';
            $out['D_total'] = '1000';
            $out['D_path'] = 'D:/files/';
            $out['D_space'] = '1024';
            
            for($i=1;$i<=5;$i++){
                $aaData[]=$out;
                $output['aaData'] = $aaData;
            }
            
            $output['iTotalDisplayRecords'] = 5;    //如果有全局搜索，搜索出来的个数
            $output['iTotalRecords'] = 5; //总共有几条数据
            echo json_encode($output); //最后把数据以json格式返回
            die;
		}
	}
	
	//查询录象的时长、次数
	public function videoSimpleCount($N_ChannelID,$where)
	{
	    $sql = "select `recid` from tab_ved_cdrinfo union select `recid` from tab_ved_bakinfo order by `recid` ";
	    $res = M()->query($sql);
	    $recid = "";
	    if($res){
	        foreach ($res as $v){
	            if($recid == ""){
	                $recid = $v['recid'];
	            }
	            else
	            {
	                $recid = $recid .",".$v['recid'];
	            }
	        }
	    }
	    $where2 ="";
	    if(!empty($recid)) $where2 .= " and N_SN in ($recid)";
	    
	    $sql="Select N_ChannelID,Count(N_SN) as 'cAmount',";
	    $sql.="Sum(ABS(TIMESTAMPDIFF(SECOND,D_StartTime,D_StopTime))) as 'cSecond' from tab_rec_bakinfo where ".$where."
	    AND `N_ChannelID`='$N_ChannelID' {$where2}";
	    $sql.=" Group by N_ChannelID ";
	    $rowsSimpleVideo = M()->query($sql);
	    $data = array();
	    $data["vtimes"] = $rowsSimpleVideo["cAmount"]==""?'0':$rowsSimpleVideo["cAmount"];
	    $data["vsecond"] = formatTime($rowsSimpleVideo["cSecond"]);
	    return $data;
	}
	
	//录音播放器
	public function recordPlayer()
	{
	    $listItem=$this->getParam("listItem");
	    $fileName="";
	    if($listItem==""){
	        JS_alert("播放列表为空，请选择要播放的录象");
	    }
	    $SelectStr="N_SN,N_RECID,N_RECSEQ,N_ChannelID,D_StartTime,D_StopTime,V_Diverter,V_Diverted";
	    $SelectStr.=",N_CallDirection,V_Caller,V_Called,V_Ext";
	    $SelectStr.=",v_path,v_netpath,v_voicefile,(unix_timestamp(D_StopTime)-unix_timestamp(D_StartTime)) AS reclong ";
	    
	    $aryItem=explode(",",$listItem);
	    $rsPlay = array();
	    $rsPlay_bak = array();
	    if(count($aryItem)>1){//试听多个录音
	        $rs = M('rec_cdrinfo')->where("N_SN in($listItem)")->select();
	        $cnt = count($rs);
	        $rsPlay = $rs;
	        
	        $rs_bak = M('rec_bakinfo')->where("N_SN in($listItem)")->select();
	        $cnt_bak = count($rs_bak);
	        $rsPlay_bak = $rs_bak;
	        
	        if(($cnt+$cnt_bak)==0){
	            JS_alert("没找到录象文件，可能已被删除");
	            //JScript("self.close();");
	        }
	    }else{//试听一个录音，根据录音子ID判断是否有相关录音，有则罗列出来一起试听。
	        $rs = M('rec_cdrinfo')->field("N_RECID")->where("N_SN = '{$listItem}' ")->find();
			 
	        $recid = "";
			$rsPlay = false;
	        if(!empty($rs["n_recid"])){
				 
	            $recid = $rs["n_recid"];
				$rsPlay = M('rec_cdrinfo')->field($SelectStr)->where("N_RECID = '{$recid}' ")->order('N_RECSEQ')->select();
	        }
	        
	         
	        $fileName=$this->getParam("fname");
            //加载页面后，首先播放的录音
	        if(!$rsPlay){
				
                $rs = M('rec_bakinfo')->field("N_RECID")->where("N_SN = '{$listItem}' ")->find();
				$rsPlay = false;
                $recid = "";
                if(!empty($rs["n_recid"])){
                    $recid = $rs["n_recid"];
					 
					$rsPlay = M('rec_bakinfo')->field($SelectStr)->where("N_RECID = '{$recid}' ")->order('N_RECSEQ')->select();
                }
	             
	            if(!$rsPlay){	//再次无记录，关闭试听页面
	                JS_alert("没找到录音文件，可能已被删除");
					exit;
	                //JScript("self.close();");
	            }
	        }
	    }
	    $rsPlayTotal = array_merge($rsPlay,$rsPlay_bak);
	    if($rsPlayTotal){
	        foreach ($rsPlayTotal as $k=>$v){
	            $rsPlayTotal[$k]['field_url'] = host().$v["v_netpath"].$v["v_voicefile"];
	            $rsPlayTotal[$k]['fx'] =  fx($v["n_calldirection"]);
	        }
	    }
	    
	    $this->assign("rsPlayTotal",$rsPlayTotal);
	    $this->assign("fileName",$fileName);
	    $this->display();
	}
	
	public function videoPlayer(){
	    $listItem=$this->getParam("listItem");
	    //只保留有录象的选项
	    $sqlVideo ="select `RecID` from tab_ved_cdrinfo where `RecID` in($listItem) union select `RecID` from tab_ved_bakinfo where `RecID` in($listItem) order by `RecID`";
	    //echo $sqlVedio;
	    $rsVideo=M()->query($sqlVideo);
	    $listVideo="";
	    foreach ($rsVideo as $v){
	        if($listVideo == ""){
	            $listVideo = $v['recid'];
	        }
	        else
	        {
	            $listVideo = $listVideo .",".$v['recid'];
	        }
	    }
	    
	    $listItem = $listVideo;
	    //echo $listItem.'<br/>';
	    
	    $fileName="";
	    if($listItem ==""){
	        JS_alert("录像文件不存在，或者已被删除");
	        echo "<script type='text/javascript'>window.close();</script>";
	        exit();
	    }
	    
	    $SelectStr="N_SN,N_RECID,N_RECSEQ,N_ChannelID,D_StartTime,D_StopTime,V_Diverter,V_Diverted";
	    $SelectStr.=",N_CallDirection,V_Caller,V_Called,V_Ext,V_Path";
	    $SelectStr.=",v_path,v_netpath,v_voicefile,(unix_timestamp(D_StopTime)-unix_timestamp(D_StartTime)) AS reclong ";
	    
	    $aryItem=explode(",",$listItem);
	    $rsPlay = array();
	    $rsPlay_bak = array();
	    if(count($aryItem)>1){//试听多个录音
	        $sql="select {$SelectStr} from tab_rec_cdrinfo where N_SN in($listItem)";
	        $rsPlay=M()->query($sql);
	        $sql="select {$SelectStr} from tab_rec_bakinfo where N_SN in($listItem)";
	        $rsPlay_bak=M()->query($sql);
	        $cnt=count($rsPlay);
	        $cnt1=count($rsPlay_bak);
	        if(($cnt+$cnt1)==0){
	            JS_alert("没找到相关文件，可能已被删除");
	            //JScript("self.close();");
	        }
	    }else{
	        $sql="select {$SelectStr} from tab_rec_cdrinfo where N_SN=$listItem";
	        //查找录象文件
	        $sqlVideo="select `RecID`,`LocalFileName`,`RemoteFileName` from tab_ved_cdrinfo where `RecID`='$listItem' union select `RecID`,`LocalFileName`,`RemoteFileName` from tab_ved_bakinfo where `RecID`='$listItem' order by `recid`";//获取录象记录
	        $rsVideo=M()->query($sqlVideo);
	        $rowsVideo=$rsVideo[0];
	        //查找录象文件
	        $fileSelect=isset($_GET["fname"])?$_GET["fname"]:"";//加载页面后，首先播放的录音
	        $fileName=($fileSelect==""?"":$rowsVideo[$fileSelect]);
	        $rsPlay=M()->query($sql);
	        if(count($rsPlay)==0){
	            $rsPlay=M()->query(str_replace("tab_rec_cdrinfo","tab_rec_bakinfo",$sql));//录音信息表无相关信息时，查询备份表
	            if(count($rsPlay)==0){	//再次无记录，关闭试听页面
	                JS_alert("没找到相关文件，可能已被删除");
	                //JScript("self.close();");
	            }
	        }
	    }
	    $rsPlayTotal = array_merge($rsPlay,$rsPlay_bak);
	    $rows = array();
	    if($rsPlayTotal){
	        foreach ($rsPlayTotal as $k=>$v){
	            $sqlVideo="select `RecID`,`LocalFileName`,`RemoteFileName` from tab_ved_cdrinfo where `RecID`='{$v['n_sn']}' union select `RecID`,`LocalFileName`,`RemoteFileName` from tab_ved_bakinfo where `RecID`='{$v['n_sn']}' order by `recid`";
	            $rs = M()->query($sqlVideo);
	            $VideoRs = $rs[0];
	            //  本地按实际路径读取，远程访问按网络路径读取
	            if (host()=='http://127.0.0.1:80/'||host()=='http://localhost/') {
	                $rows[$k]['localurl'] = $v['v_path'].$VideoRs["localfilename"];
	                $rows[$k]['remoteurl'] = $v['v_path'].$VideoRs["remotefilename"];
	            }else{
	                $rows[$k]['localurl'] = host().$v["v_netpath"].$VideoRs["localfilename"];
	                $rows[$k]['remoteurl'] = host().$v["v_netpath"].$VideoRs["remotefilename"];
	            }
	            
	            if($fileSelect == 'localfilename' && trim($VideoRs["localfilename"]) !='')
	            {
	                $rows[$k]['localfilename'] = $VideoRs["localfilename"];
	                $rows[$k]['remotefilename'] = "";
	            }
	            else if($fileSelect == 'remotefilename' && trim($VideoRs["remotefilename"]) !='')
	            {
	                $rows[$k]['localfilename'] = "";
	                $rows[$k]['remotefilename'] = $VideoRs["remotefilename"];
	            }
	            else{
	                $rows[$k]['localfilename'] = $VideoRs["localfilename"];
	                $rows[$k]['remotefilename'] = $VideoRs["remotefilename"];
	            }
	           
	            $rows[$k]['n_channelid'] = $v["n_channelid"];
	            $rows[$k]['v_caller'] = $v["v_caller"];
	            $rows[$k]['v_called'] = $v["v_called"];
	            $rows[$k]['fx'] =  fx($v["n_calldirection"]);
	            $rows[$k]['d_starttime'] = $v["d_starttime"];
	            $rows[$k]['v_ext'] = $v["v_ext"];
	        }
	    }
	    else
	    {
	        JS_alert("没找到相关文件，可能已被删除");
	    }
	    
	    $this->assign("rows",$rows);
	    $this->assign("fileSelect",$fileSelect);
	    $this->assign("fileName",$fileName);
	    $this->display();
	}
	
	
	//删除录音记录
	public function delRecord(){
	    if(IS_POST){
	        //根据选中的ID，筛选非锁定录音并获取相关路径
	        $AppResult = new AppResult();
	        $strID = $this->getParam("strID");
	        if($strID==""){
	            $AppResult->code = 0;
	            $AppResult->data ="";
	            $AppResult->msg = "未选中要删除的记录";
	            $AppResult->returnJSON();
	        }
	        $msg=$this->del("tab_rec_cdrinfo",$strID);
	        $msg1=$this->del("tab_rec_bakinfo",$strID);
	        if($msg!=$msg1){
	            $msg=$msg."\\r\\n".$msg1;
	        }
	        
	        $AppResult->code = 1;
	        $AppResult->data ="";
	        $AppResult->message = $msg;
	        $AppResult->returnJSON();
	         
	    }
	}
	
	//删除录音记录
	//$tab	----操作表
	//$strID ---要删除的ID字符串
	function del($tab,$strID){
	    //根据ID字符串筛选出未锁定记录。
	    $AppResult = new AppResult();
	    
	    $sql="select n_sn,v_path,v_voicefile from {$tab} where n_sn in(".$strID.") and N_Lock!=1;";//筛选出未锁定记录
	    $rs=M()->query($sql);
	    if($rs){
	        foreach ($rs as $v){
	            $sql="delete from {$tab} where n_sn='{$v['n_sn']}'";
	            $del=M()->execute($sql);
	            if(false !== $del){
	                $delid[]=$v["n_sn"];//记录被删除的ID
	                $path=$v["v_path"].$v["v_voicefile"];//取得文件路径
	                if(file_exists($path)){	
	                    //成功删除记录，则删除相关文件(如果文件存在的话)
	                    //录音记录判断，如果有其他录音记录共享此文件，则此文件不删除
	                    $whereRecShare=" where 1=1";
	                    $whereRecShare.=" and UPPER(V_VoiceFile) =UPPER('{$v['v_voicefile']}') ";
	                    $sqlRecShare="select n_sn from tab_rec_cdrinfo $whereRecShare limit 0,1 union select n_sn from tab_rec_bakinfo $whereRecShare limit 0,1;";//
	                    $rsRecShare=M()->query($sqlRecShare);
	                    if (count($rsRecShare)>0) {
	                        $log="成功删除录音记录(ID=".$v["n_sn"].")；因为文件".MySQLFixup($path)."至少还在录音记录(ID=".$rsRecShare["n_sn"].")使用，所以不删除";
	                    }//录音记录判断，如果有其他录音记录共享此文件，则此文件不删除
	                    else
	                    {
	                        if(@unlink($path)){	//尝试删除文件
	                            $log="成功删除录音记录(ID=".$v["n_sn"].")：".MySQLFixup($path);
	                        }else{
	                            $log="成功删除录音(ID=".$v["n_sn"].")。相关文件(".MySQLFixup($path).")删除失败";
	                        }
	                    }
	                }
	            }else{
	                $log="成功删除录音(ID=".$v["n_sn"].")。相关文件(".MySQLFixup($path).")不存在，可能已被删除。";
	                addSysLog($log);
	            }
	            //删除录象记录
	            $flagVediodel=0;	//录象记录删除标志 1为已删除记录，0为无记录
	            $sqlVediorec="select `RecID`,`LocalFileName`,`RemoteFileName` from tab_ved_cdrinfo where `RecID`='{$v['n_sn']}' union select `RecID`,`LocalFileName`,`RemoteFileName` from tab_ved_bakinfo where `RecID`='{$v['n_sn']}' order by `recid`";//获取录象记录
	            //echo $sqlVediorec;
	            $rowsVediorecrs=M()->query($sqlVediorec);
	            $rowsVediorec = $rowsVediorecrs[0];
	            //var_dump($rowsVediorec);
	            $vedioTable=array('tab_ved_cdrinfo','tab_ved_bakinfo');
	            for ($i=0;$i<2;$i++){
	                $sqlVediodel="delete from {$vedioTable[$i]} where `RecID`=".$v["n_sn"];
	                //echo $sqlVediodel;
	                $del = M()->execute($sqlVediodel);
	                if($del){//判断是否有删除的记录
	                    $flagVediodel=1;
	                    break;
	                }
	            }
	            if($flagVediodel==1){
	                $delid[]=$v["n_sn"];//记录被删除的ID
	                $pathLocal=$v["v_path"].$rowsVediorec["localfilename"];//取得主叫录象路径
	                $pathRemote=$v["v_path"].$rowsVediorec["remotefilename"];//取得被叫录象路径
	                if(file_exists($pathLocal)||file_exists($pathRemote)){	//成功删除记录，则删除相关文件(如果文件存在的话)
	                    if (file_exists($pathLocal)) {
	                        //录象记录判断，如果有其他录象记录共享此文件，则此文件不删除
	                        $whereVedShare=" where 1=1";
	                        $whereVedShare.=" and (LocalFileName ='{$rowsVediorec["localfilename"]}' or RemoteFileName ='{$rowsVediorec["localfilename"]}') ";
	                        $sqlVedShare="select RecID from tab_ved_cdrinfo $whereVedShare limit 0,1 union select RecID from tab_ved_bakinfo $whereVedShare limit 0,1;";//
	                        $rsVedShare=M()->query($sqlVedShare);
	                        if (count($rsVedShare)>0) {
	                            $log="成功删除录象记录(RecID=".$v["n_sn"].")：因为主叫录象文件".MySQLFixup($pathLocal)."至少还在录象记录(RecID=".$rsVedShare["recid"].")使用，所以不删除；";
	                        }else//录象记录判断，如果有其他录象记录共享此文件，则此文件不删除
	                        {
	                            if(@unlink($pathLocal)){	//尝试删除文件
	                                $log="成功删除录象记录(RecID=".$v["n_sn"].")：主叫录象文件".MySQLFixup($pathLocal)."成功删除；";
	                            }else{
	                                $log="成功删除录象(RecID=".$v["n_sn"].")。主叫录象文件(".MySQLFixup($pathLocal).")删除失败；";
	                            }
	                        }
	                    }else {
	                        $log="成功删除录象(RecID=".$v["RecID"].")。主叫录象文件(".MySQLFixup($pathLocal).")不存在，可能已被删除；";
	                    }
	                    if (file_exists($pathRemote)) {
	                        //录象记录判断，如果有其他录象记录共享此文件，则此文件不删除
	                        $whereVedShare=" where 1=1";
	                        $whereVedShare.=" and (LocalFileName ='{$rowsVediorec["remotefilename"]}' or RemoteFileName ='{$rowsVediorec["remotefilename"]}') ";
	                        $sqlVedShare="select RecID from tab_ved_cdrinfo $whereVedShare limit 0,1 union select RecID from tab_ved_bakinfo $whereVedShare limit 0,1;";//
	                        $rsVedShare=M()->query($sqlVedShare);
	                        if (count($rsVedShare)>0) {
	                            $log.="因为被叫录象文件".MySQLFixup($pathRemote)."至少还在录象记录(RecID=".$rsVedShare["recid"].")使用，所以不删除；";
	                        }else//录象记录判断，如果有其他录象记录共享此文件，则此文件不删除
	                        {
	                            if(@unlink($pathRemote)){	//尝试删除文件
	                                $log.=" 被叫录象文件(".MySQLFixup($pathRemote).")成功删除";
	                            }else{
	                                $log.="被叫录象文件(".MySQLFixup($pathRemote).")删除失败";
	                            }
	                        }
	                    }else {
	                        $log.="被叫录象文件(".MySQLFixup($pathRemote).")不存在，可能已被删除。";
	                    }
	                }else{	//
	                    $log="成功删除录象(RecID=".$v["n_sn"].")。相关录象文件(".MySQLFixup($pathLocal)."和".MySQLFixup($pathRemote).")都不存在，可能已被删除。";
	                }
	                addSysLog($log);
	            }
	        }
	    }
	    //返回操作信息
	    if(count($rs)>0){
	        $delid=implode($delid,",");//将数组转换为字符串
	        if(strlen($strID)==strlen($delid)){
	            return "成功删除记录";
	        }else{
	            return "成功删除记录(锁定状态的记录未能删除)";
	        }
	    }else{//("未能删除记录：所选记录为锁定状态");
	        return "未能删除记录（锁定状态）";
	    }
	}
	
	
	//打包下载
	public function recordZip()
	{
	    $AppResult = new AppResult();
	    $strID=$this->getParam("strID");
	    $where=" where N_SN in({$strID})";
	    //--------------------------------
	    $retMsg = "";
	    if($strID!=""){
	        $retMsg = $this->CreateZip($where);
	    }else{
	        $retMsg = "未选择下载文件";
	    }
	    $AppResult->code = 1;
	    $AppResult->data = "";
	    $AppResult->message = $retMsg;
	    $AppResult->returnJSON();
	}
	
	//根据ID串，获取相应文件并打包
	//需要php.ini中  zlib.output_compression = On    如果是php5.6以下要启用extension=php_zip.dll，php7已经集成zip扩展
	public function CreateZip($where){
	    $zip=new \ZipArchive();
	    $zipName = date("Ymdihs")."_".$_SESSION["uName"].".zip";
	    $csvName = date("Ymdihs")."_".$_SESSION["uName"].".csv";
	    $filename = "./Uploads/".$zipName;
	    $retMsg = "";
	    if($zip->open($filename, \ZIPARCHIVE::CREATE)!==TRUE) {
	        $retMsg .= "无法创建 {$filename}";
	    }else{
	        $FileInfo=$this->getPath($where);
	        //echo "<pre>";
	        //print_r($FileInfo);
	        //echo "</pre>";
	        $Paths=$FileInfo["file"];
	        $csv=$FileInfo["csv"];
	        $fp=fopen("./Uploads/".$csvName,"w");
	        $msg="";//记录下载详细信息;
	        $n=0;
	        $lost=0;
	        for($i=0;$i<count($Paths);$i++){
	            if(file_exists($Paths[$i]["fpath"])){
	                $zip->addFile($Paths[$i]["fpath"],$Paths[$i]["fname"]);
	                //implode(",",$csv[$i]);将数组变成字符串，用逗号分隔。
	                //$csv[$i]["v_path"]="";
	                fwrite($fp,implode(",",$csv[$i])."\r\n");
	                $n++;
	            }else{
	                $msg.=(++$lost).")：".$Paths[$i]["fpath"].".<br />";
	            }
	        }
	        fclose($fp);
	        if(file_exists("./Uploads/".$csvName)){
	            $zip->addFile("./Uploads/".$csvName,$csvName);
	        }
	        $zip->close();
	        unset($FileInfo,$Paths,$csv);
	        unlink("./Uploads/".$csvName);
	        if($n!=0){
	            $retMsg .= "<div style=\"color:red;font-size:14px;\">压缩完成，";
	            $retMsg .= "共压缩了: $n 个文件，点击下载:<a target='_blank' href='/Uploads/$zipName' style='color:#00f'>$zipName</a></div>";
	            if($msg!=""){
	                $retMsg .= "<hr /><div style=\"color:#f00;\">";
	                $retMsg .= "部分录音文件丢失，可能已被删除或转移到其他目录。以下为丢失文件列表</div>{$msg}";
	            }
	        }else{
	            $retMsg .= "下载失败，所选文件已被删除或转移到其他目录";
	        }
	        
	    }
	    return $retMsg;
	}
	
	
	//根据ID串获取录音文件地址
	//返回数组
	function getPath($where){
	    //注：需要在打包时同时生成CSV文件压缩在里面，所以需要的信息比较多。
	    //需要调整csv文件以配合本地播放软件时，调整此字段($sel值)即可。
	    //v_path,v_netpath,v_voicefile是必选字段
	    $sel="n_sn,n_channelid,d_starttime,d_stoptime,v_voicefile,n_fileformat,n_calldirection,";
	    $sel.="v_caller,v_called,v_ext,v_diverter,v_diverted,v_path,v_netpath,n_istalk,remark";
	    $sql="select {$sel} from tab_rec_cdrinfo ".$where;
	    //echo $sql;
	    $rows=M()->query($sql);
	    if($rows){
    	    foreach ($rows as $k=>$v)
    	    {
    	        $fpath=$v["v_path"].$v["v_voicefile"];
    	        $fname=$v["v_voicefile"];
    	        $csv[]=$rows;
    	        $path[]=array("fpath"=>"$fpath","fname"=>"$fname");
    	    }
	    }
	    $sql=str_replace("tab_rec_cdrinfo","tab_rec_bakinfo",$sql);
	    $rs=M()->query($sql);
	    if($rows){
	        foreach ($rows as $k=>$v)
	        {
	            $fpath=$v["v_path"].$v["v_voicefile"];
	            $fname=$v["v_voicefile"];
	            $csv[]=$rows;
	            $path[]=array("fpath"=>"$fpath","fname"=>"$fname");
	        }
	    }
	    //返回数组，包含2个元素，file包括文件信息，csv包括要生成CSV文件的内容。都是数组格式
	    $aryReturn=array("file"=>$path,"csv"=>$csv);
	    return $aryReturn;
	}
	

    //统计打印
    public function printPage()
    {
        $s_time = date("Y-m-d",strtotime("-300 day"))." 00:00:00";
        $e_time = date("Y-m-d",time())." 23:59:59";
        $SearchMode = isset($_REQUEST['SearchMode'])?$_REQUEST['SearchMode']:1;
        
        if(IS_AJAX){
            $sEcho = $_REQUEST['sEcho']; // DataTables 用来生成的信息
            $output['sEcho'] = $sEcho;
            $start = isset($_REQUEST['start'])?$_REQUEST['start']:0;
            $length = isset($_REQUEST['length'])?$_REQUEST['length']:10;
            $datetimepicker_start = isset($_REQUEST['datetimepicker_start'])?$_REQUEST['datetimepicker_start']:$s_time;
            $datetimepicker_end = isset($_REQUEST['datetimepicker_end'])?$_REQUEST['datetimepicker_end']:$e_time;
            
             
             
            //如没设定开始日期，则默认为上个月的今天
            $bTime=isset($_POST["bTime"])?$_POST["bTime"]:$lastmonth = date("Y-m-d",mktime(0,0,0,date("m")-1 ,date("d"),date("Y")));
            //如没设定开始日期，则默认为昨天
            $eTime=isset($_POST["eTime"])?$_POST["eTime"]:date("Y-m-d",mktime(0,0,0,date("m") ,date("d")-1,date("Y")));
            
            $where  = " D_StartTime >= '{$datetimepicker_start}' and D_StartTime <= '{$datetimepicker_end}' ";
            $where  .= " and D_StopTime != '0000-00-00 00:00:00' ";
            
            if($SearchMode == 1)
            {
                
                $field = "N_ChannelID,Count(N_SN) as 'cAmount',Sum(ABS(TIMESTAMPDIFF(SECOND,D_StartTime,D_StopTime))) as cSecond";
                $cnt = M('rec_cdrinfo')->field("N_ChannelID ")->where($where)->group('N_ChannelID')->select();
                $total = count($cnt);
                
                $rs = M('rec_cdrinfo')->field($field)->where($where)->group('N_ChannelID')->limit($start,$length)->select();
                
                if($rs){
                    foreach ($rs as $k=>$v){
                        $rs[$k]['recordtime'] = formatTime($v["csecond"]);
                        $data = $this->videoSimpleCount($v['n_channelid'],$where);
                        $rs[$k]['vtimes'] = $data['vtimes'];
                        $rs[$k]['vsecond'] = $data['vsecond'];
                    }
                }
               
            }
            else
            {
                
                $field = "*";
                $cnt = M('rec_cdrinfo')->field("count(*) cnt ")->where($where)->find();
                $total = $cnt['cnt'];
                $rs = M('rec_cdrinfo')->field($field)->where($where)->limit($start,$length)->select();
               
                if($rs){
                    foreach ($rs as $k=>$v){
                        $rs[$k]['longtime'] = DateDiff($v['d_starttime'],$v['d_stoptime']);
                        $rs[$k]['n_calldirection'] = $v['n_calldirection']==1?'拨出':'来电';
                    }
                }
            }
            
            $output['aaData'] = $rs;
            $output['iTotalDisplayRecords'] = $total;    //如果有全局搜索，搜索出来的个数
            $output['iTotalRecords'] = $total; //总共有几条数据
            echo json_encode($output); //最后把数据以json格式返回
            die;
        }
        $this->assign("SearchMode",$SearchMode);
        $this->assign("datetimepicker_start",$s_time);
        $this->assign("datetimepicker_end",$e_time);
        $this->assign("CurrentPage",'recordCount');
        $this->display();
    }
    
    public function downloadRecordExcel(){
        $start = isset($_REQUEST['start'])?$_REQUEST['start']:0;
        $length = isset($_REQUEST['length'])?$_REQUEST['length']:10;

        $SearchMode = isset($_REQUEST['SearchMode'])?$_REQUEST['SearchMode']:0;
        $search_key = $_REQUEST['search_key'];
        $has_video = $_REQUEST['has_video'];
        $stime = isset($_REQUEST['stime'])?$_REQUEST['stime']:1;
        $InOut = $_REQUEST['InOut'];
        $chk_CN = $_REQUEST['chk_CN'];
        $datetimepicker_start = isset($_REQUEST['datetimepicker_start'])?$_REQUEST['datetimepicker_start']:$s_time;
        $datetimepicker_end = isset($_REQUEST['datetimepicker_end'])?$_REQUEST['datetimepicker_end']:$e_time;

        $m = M("rec_cdrinfo");
        $m_bak = M("rec_bakinfo");
        
        $field="N_SN,N_ChannelID,D_StartTime,D_StopTime,V_VoiceFile,N_FileFormat,";
        $field.="N_CallDirection,V_Caller,V_Called,V_Ext,N_BackUp,N_Lock,N_Alarm,";
        $field.="N_AvoidRec,N_DeleteFlag,N_Locker,V_Path,V_NetPath,";
        $field.="V_Diverter,V_Diverted,N_IsTalk,ReMark ";
        
        
        $where  .=  " D_StartTime between '{$datetimepicker_start}' and '$datetimepicker_end' ";
        
        if(!empty($chk_CN)){
            $where  .= " and N_ChannelID in ($chk_CN)";
        }
       
        if($search_key != ""){//通话号码(姓名)的取值，0则号码，1则姓名
            if($SearchMode==0){
                $key=getNumByName($search_key);
                if($key==""){
                    $where .= ' and N_SN = -1';//姓名没匹配到号码，直接设置一个不成立的条件
                }else{
                    $where .= " and (V_caller in ('{$key}') or V_called in('{$key}') or v_ext in('{$key}')) ";
                }
            }else{
                $where .= " and (V_caller like '".$search_key."%' or V_called like '".$search_key."%'  orv_ext like '".$search_key."%')";
            }
        }
        if ($has_video==1) {//只查有录象的记录
            $sql = "select `recid` from tab_ved_cdrinfo union select `recid` from tab_ved_bakinfo order by `recid` ";
            $res = M()->query($sql);
            $recid = "";
            if($res){
                foreach ($res as $v){
                    if($recid == ""){
                        $recid = $v['recid'];
                    }
                    else 
                    {
                        $recid = $recid .",".$v['recid'];
                    } 
                }
            }
            if(!empty($recid)) $where .= " and N_SN in ($recid)";
        }
        
        //按拨叫方向查询
        if($InOut!=2){
            $where .= " and N_CallDirection= {$InOut}";
        }
        $where.=" and  (unix_timestamp(D_StopTime)-unix_timestamp(D_StartTime))>=$stime ";
        $CntNoBackup=$m->field("count(*) cnt")->where($where)->find();  //未备份部分数据
        $CntNoBackup=$CntNoBackup['cnt'];
        $CntBackup = $m_bak->field("count(*) cnt")->where($where)->find();  //已备份部分数据
        $CntBackup=$CntBackup['cnt'];
        $total = (int)$CntNoBackup + (int)$CntBackup;
       
        $rs = array();
        if($CntBackup==0)
        {
            //无已备份数据，读取未备份数据即可
            $rs= $m->field($field)->where($where)->select();
        }
        else
        {
            if($CntNoBackup==0)
            {
                //有已备份数据，无未备份数据。读取已备份份数据即可
                $rs= $m_bak->field($field)->where($where)->select();
            }
            else 
            {
                //未备份和已备份都有，需要读取两张表
                //计算未备份数据有多少页$NotBak_pagecnt
                //未备份数据不足一页的数据，需要同时读取备份与未备份数据
                $rsNoBak= $m->field($field)->where($where)->select();
                $rsBak= $m_bak->field($field)->where($where)->select();
                $rs= array_merge($rsNoBak,$rsBak); 
            }    
        }
      
        if($rs){ 
            foreach ($rs as $k=>$v)
            {
                $fileplay ="<i class='fa fa-volume-up ' style=\"cursor:pointer\" onclick=\"play('".$v["n_sn"]."','".$v["v_voicefile"]."')\"></i>";
                
                $rs[$k]['n_channelinfo'] = getChannelNameById($v['n_channelid']);
                $rs[$k]['v_caller'] = getNameByPhoneNum($v['v_caller']);
                $rs[$k]['v_called'] = getNameByPhoneNum($v['v_called']);
                $rs[$k]['v_ext'] = getNameByPhoneNum($v['v_ext']);
                $rs[$k]['n_calldirection'] = $v['n_calldirection']==1?'拨出':'来电';
                $rs[$k]['longtime'] = DateDiff($v['d_starttime'],$v['d_stoptime']);
                $rs[$k]['v_voicefileplay'] = $fileplay;
                $rs[$k]['local_video'] = getVideoByRecID($v['n_sn'],0);
                $rs[$k]['remote_video'] = getVideoByRecID($v['n_sn'],1);
            }
        }
        header("Content-type: text/html; charset=utf-8");
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.Reader.Excel2007.php");
        Vendor("PHPExcel.PHPExcel.Reader.Excel5");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        
        $excel = new \PHPExcel();
        
        //指定工作簿
        $excel->setActiveSheetIndex(0); 
        //设置列宽
        $excel->getActiveSheet()->getColumnDimension('A')->setWidth('5');
        $excel->getActiveSheet()->getColumnDimension('B')->setWidth('5');
        $excel->getActiveSheet()->getColumnDimension('C')->setWidth('10');
        $excel->getActiveSheet()->getColumnDimension('D')->setWidth('10');
        $excel->getActiveSheet()->getColumnDimension('E')->setWidth('10');
        $excel->getActiveSheet()->getColumnDimension('F')->setWidth('10');
        $excel->getActiveSheet()->getColumnDimension('G')->setWidth('5');
        $excel->getActiveSheet()->getColumnDimension('H')->setWidth('10');
        $excel->getActiveSheet()->getColumnDimension('I')->setWidth('10');
        $excel->getActiveSheet()->getColumnDimension('J')->setWidth('10');
        $excel->getActiveSheet()->getColumnDimension('K')->setWidth('5');
        $excel->getActiveSheet()->getColumnDimension('L')->setWidth('5');
        
        
        $excel->getActiveSheet()->setTitle('录音记录'); //excel标题
        //设置行标题
        $excel->getActiveSheet()->setCellValue("A1", "ID");         
        $excel->getActiveSheet()->setCellValue("B1", "线路号");
        $excel->getActiveSheet()->setCellValue("C1", "线路信息");
        $excel->getActiveSheet()->setCellValue("D1", "主叫");
        $excel->getActiveSheet()->setCellValue("E1", "被叫");
        $excel->getActiveSheet()->setCellValue("F1", "录音号码");
        $excel->getActiveSheet()->setCellValue("G1", "方向");
        $excel->getActiveSheet()->setCellValue("H1", "开始时间");
        $excel->getActiveSheet()->setCellValue("I1", "结束时间");
        $excel->getActiveSheet()->setCellValue("J1", "时长");
        $excel->getActiveSheet()->setCellValue("K1", "锁定");
        $excel->getActiveSheet()->setCellValue("L1", "备注");

         
        //获取并填充数据
        if($rs){
            $i=1;
            foreach ($rs as $key => $value) {
                $i++;
                $excel->getActiveSheet()->setCellValue("A".$i,$value["n_sn"]);
                $excel->getActiveSheet()->setCellValue("B".$i,$value["n_channelid"]);
                $excel->getActiveSheet()->setCellValue("C".$i,$value["n_channelinfo"]);
                $excel->getActiveSheet()->setCellValue("D".$i,$value["v_caller"]);
                $excel->getActiveSheet()->setCellValue("E".$i,$value["v_called"]);
                $excel->getActiveSheet()->setCellValue("F".$i,$value["v_ext"]);
                $excel->getActiveSheet()->setCellValue("G".$i,$value["n_calldirection"]);
                $excel->getActiveSheet()->setCellValue("H".$i,$value["d_starttime"]);
                $excel->getActiveSheet()->setCellValue("I".$i,$value["d_stoptime"]);
                $excel->getActiveSheet()->setCellValue("J".$i,$value["longtime"]);
                $excel->getActiveSheet()->setCellValue("K".$i,$value["n_lock"]);
                $excel->getActiveSheet()->setCellValue("K".$i,$value["remark"]);
            }
        } 
        
      
        $title ='录音记录';
        $date = date('Y-m-d',time());
        
        $data = array();
        $fileurl = "Uploads/records/". $title."_".$date.".xls";
       
        $filename = iconv('utf-8', "gb2312", $fileurl);
        
        $objwriter = \PHPExcel_IOFactory::createWriter($excel,'Excel5');
        $objwriter->save($filename);
        $this->returnJSON(1,'导出成功',"/".$fileurl);

    }
}