<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
use Think\Log;
use Think\Upload;
class RecordController extends AuthController {
    public function __construct(){
        parent::__construct();
    }
    public function index(){
        $this->display();
    }
    

    public function recordList(){
        $s_time = date("Y-m-d",strtotime("-300 day"))." 00:00:00";
        $e_time = date("Y-m-d",time())." 23:59:59";
         
        if(IS_AJAX){
            $start = isset($_REQUEST['start'])?$_REQUEST['start']:0;
            $length = isset($_REQUEST['length'])?$_REQUEST['length']:10;
            $SearchMode = isset($_REQUEST['SearchMode'])?$_REQUEST['SearchMode']:0;
            $search_key = $_REQUEST['search_key'];
            $has_vedio = $_REQUEST['has_vedio'];
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
            if ($has_vedio==1) {//只查有录象的记录
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
            $CntNoBackup=$m->field("count(*) cnt")->where($where)->find();	//未备份部分数据
            $CntNoBackup=$CntNoBackup['cnt'];
            $CntBackup = $m_bak->field("count(*) cnt")->where($where)->find();	//已备份部分数据
            $CntBackup=$CntBackup['cnt'];
            $total = (int)$CntNoBackup + (int)$CntBackup;
            $rs = array();
            if($CntBackup==0)
            {
                //无已备份数据，读取未备份数据即可
                $rs= $m->field($field)->where($where)->limit($start,$length)->select();
            }
            else
            {
                if($CntNoBackup==0)
                {
                    //有已备份数据，无未备份数据。读取已备份份数据即可
                    $rs= $m_bak->field($field)->where($where)->limit($start,$length)->select();
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
                            $rs= $m->field($field)->where($where)->limit($start,$length)->select();
                        }
                        else
                        {
                            //未备份数据不足一页的数据，需要同时读取备份与未备份数据
                            $rsNoBak= $m->field($field)->where($where)->limit($start,$length)->select();
                            $rsBak= $m_bak->field($field)->where($where)->limit(0,($length-$NumNotShowed))->select();
                            $rs= array_merge($rsNoBak,$rsBak); 
                        }
                   }
                   else
                   {
                       //$NumNotShowed=0或者$NumNotShowed<0,则未备份录音已经显示完毕，仅查询备份表(tab_rec_bakinfo)即可
                       $rs= $m_bak->field($field)->where($where)->limit($start-$CntNoBackup,$length)->select();
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
                    $rs[$k]['local_video'] = getVedioByRecID($v['n_sn'],0);
                    $rs[$k]['remote_video'] = getVedioByRecID($v['n_sn'],1);
                }
            }

            
            $output['aaData'] = $rs;
            $output['iTotalDisplayRecords'] = $total;    //如果有全局搜索，搜索出来的个数
            $output['iTotalRecords'] = $total; //总共有几条数据
            echo json_encode($output); //最后把数据以json格式返回
            die;
        }
        
        //列出所有通道
        $rsChno = M('sys_paramschannel')->order('N_ChannelNo')->field("distinct N_ChannelNo")->select();
        $this->assign("rsChno",$rsChno);
        
        $this->assign("datetimepicker_start",$s_time);
        $this->assign("datetimepicker_end",$e_time);
        $this->assign("CurrentPage",'recordList');
        $this->display();
    }
    
    public function recordCount(){
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
                        $data = $this->vedioSimpleCount($v['n_channelid'],$where);
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
	public function vedioSimpleCount($N_ChannelID,$where)
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
	    $rowsSimpleVedio = M()->query($sql);
	    $data = array();
	    $data["vtimes"] = $rowsSimpleVedio["cAmount"]==""?'0':$rowsSimpleVedio["cAmount"];
	    $data["vsecond"] = formatTime($rowsSimpleVedio["cSecond"]);
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
	        if($rs){
	            $recid = $rs["n_recid"];
	        }
	        $rsPlay = M('rec_cdrinfo')->field($SelectStr)->where("N_RECID = '{$recid}' ")->order('N_RECSEQ')->select();
	        
	        $fileName=$this->getParam("fname");//加载页面后，首先播放的录音
	        if(!$rsPlay){
	            $rsPlay = M('rec_bakinfo')->field($SelectStr)->where("N_RECID = '{$recid}' ")->order('N_RECSEQ')->select();
	            if(!$rsPlay){	//再次无记录，关闭试听页面
	                JS_alert("没找到录音文件，可能已被删除");
	                //JScript("self.close();");
	            }
	        }
	    }
	    $rsPlayTotal = array_merge($rsPlay,$rsPlay_bak);
	    if($rsPlayTotal){
	        foreach ($rsPlayTotal as $k=>$v){
	            $rsPlayTotal[$k]['field_url'] = "http://local.lyxtgw.com/".$v["v_netpath"].$v["v_voicefile"];
	            $rsPlayTotal[$k]['fx'] =  fx($v["n_calldirection"]);
	        }
	    }
	    
	    $this->assign("rsPlayTotal",$rsPlayTotal);
	    $this->assign("fileName",$fileName);
	    $this->display();
	}
}