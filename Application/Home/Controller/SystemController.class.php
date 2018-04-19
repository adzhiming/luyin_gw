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
    
    public function systemParameter(){
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
}