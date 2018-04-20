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
    //通道参数
    public function channelParameter(){
        $ChannelType = $this->getParam("type");
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
        if($rs){
            foreach ($rs as $k=>$v){
                $channelNo = $v['n_channelno'];
                $rs[$k]['ChnName'] = $this->getChannelInfoBychannelNo($channelNo,"ChnName");
                $rs[$k]['ExtPhoneNumber'] = $this->getChannelInfoBychannelNo($channelNo,"ExtPhoneNumber");
                $rs[$k]['CircuitNo'] = $this->getChannelInfoBychannelNo($channelNo,"CircuitNo");
                $rs[$k]['ExtPhoneNumber2'] = $this->getChannelInfoBychannelNo($channelNo,"ExtPhoneNumber2");
                $rs[$k]['IsChkDTMF'] = $this->getChannelInfoBychannelNo($channelNo,"IsChkDTMF");
                $rs[$k]['CircuitNo2'] = $this->getChannelInfoBychannelNo($channelNo,"CircuitNo2");
                $rs[$k]['IsVideoChannel'] = $this->getChannelInfoBychannelNo($channelNo,"IsVideoChannel");
                $rs[$k]['RecordMode'] = $this->getChannelInfoBychannelNo($channelNo,"RecordMode");
                $rs[$k]['ChannelUserDisable'] = $this->getChannelInfoBychannelNo($channelNo,"ChannelUserDisable");
            }
        } 
        
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
       /*   echo "<pre>";
        print_r($rsTitle);
        echo "</pre>";  */ 
        
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
        $sql = "select a.*,b.n_channeltype,b.n_channelno from tab_sys_paramschannel a,tab_sys_channelconfig b ";
        $sql=$sql."where a.N_ChannelNo=".$channelid." and a.advpara=0 and a.N_ChannelNo=b.N_ChannelNo and a.V_ParamsName !='ChannelPort' order by a.v_paramsnameCH";
        //$sql_="select N_ChannelNo from tab_sys_paramschannel order by N_ChannelNo limit 0,1;";
        $sql=str_replace("a.N_ChannelNo=9999","a.N_ChannelNo=(select N_ChannelNo from tab_sys_paramschannel order by N_ChannelNo limit 0,1)",$sql);//全部通道，显示通道号为0的通道
        $rs = M()->query($sql);
        if($rs){
            foreach ($rs as $k=>$v)
            {
                $rs[$k]['paramsset'] = $this->getInput($v["v_paramsname"],$v["v_value"],$v["n_modifylevel"],$v["v_verify"]);
            }
        }
        $this->assign("rs",$rs);
        $this->assign("CurrentPage",'channel');
        $this->display();
    }
    
    //系统参数
    public function systemParameter(){
        $rs = M("sys_paramssystem")->where("N_ModifyLevel< 2")->order("V_ParamsNameCh")->select();
        if($rs){
            foreach ($rs as $k=>$v)
            {
                $rs[$k]['paramsset'] = $this->showParaObj($v["v_paramsname"],$v["v_value"],$v["n_modifylevel"]);
            }
        }
        $this->assign("rs",$rs);
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
                $obj="<select name='.$pn.' class='w'>";
                for(;;){
                    $i=$i+5;
                    if($i>60){break;}
                    $obj.="<option value='{$i}'".selected($i,$pv).">".$i."</option>";
                }
                $obj.="</select>";
                break;
            case "Sys_AutoBakPeriod":
                $obj="<select name=".$pn."  class='w'>";
                for(;;){
                    $i=$i+5;
                    if($i>60){break;}
                    $obj.="<option value='{$i}'".selected($i,$pv).">".$i."</option>";
                }
                $obj.="</select>";
                break;
            case "Sys_EnableBackup":		//是否允许自动备份,是否允许删除已经锁定的文件
                $obj="<select name=".$pn." class='w'>";
                $obj.="<option value='0'".selected(0,$pv).">不允许</option>";
                $obj.="<option value='1'".selected(1,$pv).">允许</option>";
                $obj.="</select>";
                break;
            case "Sys_AutoBakDir":
            case "Sys_AutoBakDataPath":
                //$obj="<input type='text' onblur=\"check(this,'{$pn}')\" ";
                //已禁止直接输入路径，可确保路径格式，无需再次检查
                $obj="<input type='text' readonly  class='txtBox w' ";
                $obj.=" value=".$pv." name='.$pn.' ";
                if($mlv==0){//只读参数，则不允许修改。点击控件时不弹出路径选择
                    $obj.=" onclick=\"SetPath(this,0)\"";
                }else{
                    $obj.=" title='此参数禁止修改' style='color:#999;' onfocus='this.blur()'";
                }
                $obj.=" />";
                break;
            case "Sys_EnableDelLockFile":	//是否允许自动备份,是否允许删除已经锁定的文件
                $obj="<select name=".$pn." class='w'>";
                $obj.="<option value='0'".selected(0,$pv).">不允许</option>";
                $obj.="<option value='1'".selected(1,$pv).">允许</option>";
                $obj.="</select>";
                break;
            case "Sys_AutoBakClock":	//自动备份时间，只允许选择凌晨3点至5点备份
                $obj="<select name=".$pn." class='w'>";
                $obj.="<option value='3:00'".selected("3:00",$pv).">03:00</option>";
                $obj.="<option value='4:00'".selected("4:00",$pv).">04:00</option>";
                $obj.="<option value='5:00'".selected("5:00",$pv).">05:00</option>";
                $obj.="</select>";
                break;
            default:
                //$obj="<input type='text' onblur=\"check(this,'{$pn}','".$arr[$pn]["Regex"]."','".$arr[$pn]["ErrMsg"]."')\" ";
                $obj="<input type='text' maxlength='20' onblur=\"check(this,'{$pn}')\"  class='txtBox' ";
                $obj.=" value='{$pv}' name=".$pn.canModify($mlv)." class='w'/>";
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
                $tmp="<input type='text' maxlength='20' class='txtBox' title='".$V_Verify."' onblur='CheckVerify(this)'";
                $tmp.="name='{$paraName}' value='".$v.canModify($mlv)."' class='w'/>";
        }
        return $tmp;
    }
}