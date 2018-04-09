<?php 

/* 获取某天录音录像统计 */
function getTodayChannelCount($ChannelNo='',$days){
    $returnData['voiceCnt'] = 0;
    $returnData['vedioCnt'] = 0;
    $days = empty($days)?date("Y-m-d",time()):$days;
    $where ="DATE_FORMAT(a.D_StartTime,'%Y-%m-%d') = '{$days}'";
    if($ChannelNo){
        $where .=" and a.N_ChannelID = '{$ChannelNo}'";
    }
    //统计录音
    $rs = M('rec_cdrinfo a')->field("count(*) cnt")->where($where)->find();
    if($rs){
        $returnData['voiceCnt'] = $rs['cnt'];
    }
    //统计录像
    $sql = "select count(b.VID) cnt from tab_rec_cdrinfo a left join tab_ved_bakinfo b on a.N_RECID = b.RecID where {$where}";
    $rs = M()->query($sql);
    if($rs){
        $returnData['vedioCnt'] = $rs[0]['cnt'];
    }
    return $returnData;
}

/* author lzm
 * date 2018-03-29 
 *录音录像统计
 */
function getRecordVedioCount($seachType='day')
{
    $channelData = array();
    if($seachType){
        switch ($seachType){
            case 'day':
                $returnData = array();
                $returnData['voiceCnt'] = 0;
                $returnData['vedioCnt'] = 0;
                //统计录音
                $rs = M('rec_cdrinfo')->field("count(*) cnt")->where(" TO_DAYS(D_StartTime) = TO_DAYS(NOW())")->find();
                if($rs){
                    $returnData['voiceCnt'] = $rs['cnt'];
                }
                
                //统计录像
                $sql = "select count(b.VID) cnt from tab_rec_cdrinfo a left join tab_ved_bakinfo b on a.N_RECID = b.RecID where TO_DAYS(a.D_StartTime) = TO_DAYS(NOW())'";
                $rs = M()->query($sql);
                if($rs){
                    $returnData['vedioCnt'] = $rs[0]['cnt'];
                }
                return $returnData;
            break;  
            
            case 'week':
                $returnData = array();
                for($i=-7;$i<0;$i++){
                    $dates = date('Y-m-d', strtotime("{$i} days"));
                    $statistics = getTodayChannelCount($ChannelNo='',$dates);
                    $returnData[$dates] = $statistics;
                }
                return $returnData;
            break;
            
            case 'month':
                $returnData = array();
                for($i=-30;$i<0;$i++){
                    $dates = date('Y-m-d', strtotime("{$i} days"));
                    $statistics = getTodayChannelCount($ChannelNo='',$dates);
                    $returnData[$dates] = $statistics;
                }
                return $returnData;
            break;
            
            case 'year':
                
            break;
            default:
                
            break;
        }
    }
}

//根据录音通道ID或者通道名称
function getChannelNameById($id)
{
    if(empty($id))
    {
        return '';
    }
    $where['N_ChannelNo'] = $id;
    $where['V_ParamsName'] = "ChnName";
    $rs = M("sys_paramschannel")->field("V_Value")->where($where)->find();
    if($rs)
    {
        return $rs['v_value'];
    }
    return '';
}


//电话号码关联姓名
function getNameByPhoneNum($num){
    if(!strlen($num)>0){return "";}
    
    $rs = M('phonebook')
          ->where("Mobile ='{$num}' or OfficeNum ='{$num}' or OtherNum ='{$num}' or remark='{$num}'")
          ->find();
    if($rs)
    {
        return $rs['contactname']."：".$num;
    }
    return $num;
}

//按姓名查找时，先根据姓名从号簿中查找到相关应的号码。
function getNumByName($name){
    $name = trim($name);
    $strNum = "";
    if($name!=""){
        $rs = M('phonebook')->field("Mobile,OfficeNum,OtherNum")->where("contactName like'%".$name."%'")->find();
        if($rs)
        {
            if(!empty($rs['mobile'])){
                $strNum =  $rs['mobile'];
            }
            if(!empty($rs['0fficenum'])){
                if($strNum == ""){
                    $strNum =  $rs['0fficenum'];
                }
                else
                {
                    $strNum = $strNum .",". $rs['0fficenum'];
                }
            }
            if(!empty($rs['othernum'])){
                if($strNum == ""){
                    $strNum =  $rs['othernum'];
                }
                else
                {
                    $strNum = $strNum .",". $rs['othernum'];
                }
            }
        }
    }  
    return $strNum;
}

//根据RecID获取主叫、被叫录象
function getVedioByRecID($id,$type)
{
    $pre = C('DB_PREFIX');
    $str_vedio="";
    $field = "RecID,LocalFileName,RemoteFileName";
    $where['RecID'] = $id;
    $retrunData = array(); 
    $rows = M()->field($field)->table("{$pre}ved_cdrinfo")->union("SELECT {$field} FROM {$pre}ved_bakinfo",true)->where($where)->find();
    if($rows)
    {
        if($type == 0){
            if ($rows['LocalFileName']) {
                $str_vedio.="<i class='fa fa-volume-up video-play' style=\"cursor:pointer\" onclick=\"vplay('".$rows["recid"]."','LocalFileName')\"></i>";
            }else {
                $str_vedio.="---";
            }
        }
        if($type == 1){
            if($rows['RemoteFileName'] ){
                $str_vedio.="<i class='fa fa-volume-up video-play' style=\"cursor:pointer\" onclick=\"vplay('".$rows["recid"]."','RemoteFileName')\"></i>";
            }else {
                $str_vedio.="---";
            }
        }
    }
    else
    {
        $str_vedio.="---";
    }
    return $str_vedio;
   
}

//比较两个日期之间差别多少秒，
//参数格式必须为标准时间日期格式，如：2018-3-5 14:50:06
function DateDiff($d1,$d2){
    //日期比较函数
    if(is_string($d1)){$d1=strtotime($d1);}
    if(is_string($d2)){$d2=strtotime($d2);}
    return formatTime($d2-$d1);
}

//格式化时长，输入参数为秒，转为hh:mm:ss样式
function formatTime($s){
    if($s==0 || $s<0){
        return "00:00:00";
    }else{
        $h=0;
        $m=0;
        if($s>59){
            $m=(int)($s / 60);
            $s=$s % 60;
        }
        if($m>59){
            $h=(int)($m / 60);
            $m=$m % 60;
        }
        return dbNum($h).":".dbNum($m).":".dbNum($s);
    }
}

//小于10的数，前面加0
function dbNum($i){
    if($i<10){return "0".$i;}else{return $i;}
}