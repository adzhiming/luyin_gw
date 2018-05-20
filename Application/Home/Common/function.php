<?php 

/* 获取某天录音录像统计 */
function getTodayChannelCount($ChannelNo='',$days){
    $returnData['voiceCnt'] = 0;
    $returnData['videoCnt'] = 0;
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
    else{
       $returnData['voiceCnt'] = '0'; 
    }
    //统计录像
    $sql = "select count(b.VID) cnt from tab_rec_cdrinfo a left join tab_ved_bakinfo b on a.N_RECID = b.RecID where {$where}";
    $rs = M()->query($sql);

    if($rs){
        $returnData['videoCnt'] = empty($rs[0]['cnt'])?'0':$rs[0]['cnt'];
    }
    else{
       $returnData['videoCnt'] = '0'; 
    }
    return $returnData;
}

/* author lzm
 * date 2018-03-29 
 *录音录像统计
 */
function getRecordVideoCount($seachType='day')
{
    $channelData = array();
    if($seachType){
        switch ($seachType){
            case 'day':
                $returnData = array();
                $returnData['voiceCnt'] = 0;
                $returnData['videoCnt'] = 0;
                //统计录音
                $rs = M('rec_cdrinfo')->field("count(*) cnt")->where(" TO_DAYS(D_StartTime) = TO_DAYS(NOW())")->find();
                if($rs){
                    $returnData['voiceCnt'] = $rs['cnt'];
                }
                
                //统计录像
                $sql = "select count(b.VID) cnt from tab_rec_cdrinfo a left join tab_ved_bakinfo b on a.N_RECID = b.RecID where TO_DAYS(a.D_StartTime) = TO_DAYS(NOW())'";
                $rs = M()->query($sql);
                if($rs){
                    $returnData['videoCnt'] = $rs[0]['cnt'];
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
function getVideoByRecID($id,$type)
{
    $pre = C('DB_PREFIX');
    $str_video="";
    $field = "RecID,LocalFileName,RemoteFileName";
    $where['RecID'] = $id;
    $retrunData = array(); 
    //$rows = M()->field($field)->table("{$pre}ved_cdrinfo")->union("SELECT {$field} FROM {$pre}ved_bakinfo")->where($where)->find();
    $sql="select {$field} from {$pre}ved_cdrinfo where `RecID`='$id' union select `RecID`,`LocalFileName`,`RemoteFileName` from {$pre}ved_bakinfo where `RecID`='$id'";
    $rs = M()->query($sql);
    $rows = $rs[0];
    if($rows)
    {
        if($type == 0){
            if ($rows['localfilename']) {
                $str_video="<i class='fa fa-play-circle-o ' style=\"cursor:pointer\" onclick=\"vplay('".$rows["recid"]."','localfilename')\"></i>";
            }else {
                $str_video="---";
            }
        }
        if($type == 1){
            if($rows['remotefilename']){
                $str_video="<i class='fa fa-play-circle-o ' style=\"cursor:pointer\" onclick=\"vplay('".$rows["recid"]."','remotefilename')\"></i>";
            }else {
                $str_video="---";
            }
        }
    }
    else
    {
        $str_video="---";
    }
    return $str_video;
   
}


//MySQL语句格式化，避免特殊字符造成SQL语法错误
function MySQLFixup($str){
    if(is_numeric($str)) return $str;
    if(empty($str)) return;
    if($str=="") return $str;
    $str=str_replace("'","''",$str);
    $str=str_replace("\\","\\\\",$str);
    return $str;
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

/*客户端弹出警告信息*/
function JS_alert($msg,$url,$flag=false){
    header("Content-type: text/html; charset=utf-8"); 
    if($flag){
        echo"<script type='text/javascript'>alert('$msg');window.location.href='{$url}'</script>";
    }
    else{
        echo"<script type='text/javascript'>alert('$msg');</script>";
    }
}


//返回服务器IP及端口，格式：http://192.168.0.1:8080/
function host(){
    return "http://{$_SERVER['SERVER_ADDR']}:{$_SERVER['SERVER_PORT']}/";
}

function fx($n){
    if($n!=""){
        return ($n==1)?"拨出":"来电";
    }else{
        return "";
    }
}

//能否删除
function canDel(){
    return substr($_SESSION['uRights'],1,1);
}


//添加系统日志
function addSysLog($evtContent){
    $sql="insert into tab_sys_accountlog(V_AccountId,V_LogIP,D_LogTime,V_Description) ";
    $sql=$sql."Values(".$_SESSION["uAccount"].",'".$_SESSION['uLogIP']."','".date("Y-m-d H:i:s",time())."','".$evtContent."')";
    $rs = M()->execute($sql);
    return true;
}


//输出用户权限
function OutCanDo($can){
    $canStr=array("查询","删除","管理","锁定");
    $str="";
    if($can<1000){$can=$can+1000;}//确保权限值为4位数
    $can=substr($can,0,4);
    for($i=0;$i<strlen($can);$i++){
        $str.=((substr($can,$i,1)=="1")?($canStr[$i].","):"");
    }
    return substr($str,0,strlen($str)-1);
}

function Out_ext($ext){
    if($ext==""){
        return "全部";
    }else{
        if(strlen($ext)>20){
            return substr($ext,0,20)."……";
        }else{
            return $ext;
        }
    }
}

function station($sid){
    if(empty($sid)){return "";}
    if(is_null($sid)){return "";}
    if($sid==""){return "";}
    $str="select v_sname from tab_station where n_sid in(".$sid.")order by v_sname";
    $r1=M()->query($str);
    $str="";
    foreach($r1 as $v){
        $str=$str.(($str=="")?"":",").$v["v_sname"];
        if(strlen($str)>=20){
            $str.="…";
            return $str;
        }
    }
    return $str;
}

//格式化字符串，去除非数字字符，允许逗号(,)
function formatSTR($str){
    if($str!=""){
        $str=str_replace("，",",",$str);//确保逗号都为英文输入法的逗号
        $str=str_replace(",","+",$str);//将逗号替换为"+"，便于下一步的验证
        $str=filter_var($str, FILTER_SANITIZE_NUMBER_INT);//过滤器删除数字中所有非法的字符,允许所有数字以及 + -。
        $str=str_replace("+",",",$str);//再将+替换回,
        $str=preg_replace("/,{2,}/",",",$str);
        if(substr($str,0,1)==","){//左侧第一个字符如果是逗号，则去除第一个字符
            $str=substr($str,1,strlen($str));
        }
        if(substr($str,strlen($str)-1,1)==","){//最后一个字符如果是逗号，则去除最后一个字符
            $str=substr($str,0,strlen($str)-1);
        }
    }
    return $str;
}

//输出select控件时，判断默认选中的options
function selected($v1,$v2){
    if($v1==$v2){return " selected ";}
    else{return "";}
}

//是否允许修改，否则设置控件属性readonly
function canModify($n){
    if($n==1){
        return " readonly ";
    }else{
        return "";
    }
}

//根据用户id获取用户信息
function getUserInfo($uid){
    if(is_null($uid)){$uid="";}
    if($uid!=""){
        $sql="select * from tab_sys_accountinfo where V_AccountId='{$uid}'";
        $r1=M()->query($sql);
        if($r1){
            return $r1[0];	//用户账号
        }else{
            return "已删除用户";
        }
    }else{
        return "";
    }
}

//编辑apache配置文件httpd.conf，创建虚拟目录实现远程文件试听
//入参：$netPath:虚拟目录名称；$vpath:虚拟目录指向的物理路径
//返回值: 0:创建失败
//        1:创建路径成功；
//        2:虚拟目录已经存在，无需创建
//add by lzh @2009-8-3
function CreatNetPath($netPath,$vpath){
    if(substr($vpath,-1,1)=="\\"){  //去除路径右侧的斜杠\
        $vpath=substr($vpath,0,strlen($vpath)-1);
    }
    $filename="C:\\NeuTech\\Apache2.2\\conf\\httpd.conf";//默认apache配置文件路径
    //$filename="C:\\Program Files\\Apache Software Foundation\\Apache2.2\\conf\\httpd.conf";
    $ArrConf=file($filename);

    //判断配置文件里是否已经存在同名虚拟路径
    while (list($key, $val) = each($ArrConf)) {
        if(substr_count($ArrConf[$key],"Alias '/{$netPath}' '{$vpath}'")){
            //echo "虚拟路径已经存在",$ArrConf[$key],"Alias /{$netPath} '{$vpath}'";
            return 2;
            break;
        }
    }
    unset($ArrConf);
    $f=fopen($filename,"a+");   
    fwrite($f,"\r\nAlias '/{$netPath}' '{$vpath}'\r\n");
    fwrite($f,"<Directory '{$vpath}'>\r\n");
    fwrite($f,"\tOrder allow,deny\r\n");
    fwrite($f,"\tAllow from all\r\n");
    fwrite($f,"</Directory>\r\n");
    fclose($f);
}

//创建物理路径,支持多级,$path必须为绝对路径
//add by lzh @2009-8-3
function CreatDir($path){
    if(strlen($path)>3){    //最短有效路径长度，如：D:\a
        $ArrDir=explode("\\",$path);
        $l=count($ArrDir);
        $tmpdir=$ArrDir[0];
        for($i=1;$i<$l;$i++){
            if(!is_dir($tmpdir."\\".$ArrDir[$i])){
                if(!@mkdir($tmpdir."\\".$ArrDir[$i],0777)){
                    // "创建路径失败".$tmpdir."\\".$ArrDir[$i];
                    return 0;
                };
            }
            $tmpdir.="\\".$ArrDir[$i];
            //echo $tmpdir."<br />";
        }
    }
    return 1;
}

function formatNetPath($p){
    $p=str_replace(":","_",$p);
    $p=str_replace("\\","_",$p);
    $p=str_replace(" ","_",$p);
    $p=substr($p,0,strlen($p)-1);
//echo $p;
    return $p;
}