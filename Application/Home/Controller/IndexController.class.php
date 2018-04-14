<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends AuthController {
    
    public function index(){
        //当日录音数量
        $todayRecordNum = 0;
        $rs = M("rec_cdrinfo")->field("count(*) as cnt")->where("TO_DAYS(D_StartTime) = TO_DAYS(NOW())")->find();
        if(false !== $rs){
            $todayRecordNum = $rs['cnt'];
        }
        
        //未处理告警数
        $alarmlogNum = 0;
        $alarmlog = M("sys_alarmlog")->field("count(*) as cnt")->where("N_ClearFlag = 0")->find();
        if(false !== $alarmlog){
            $alarmlogNum = $alarmlog['cnt'];
        }
        
        //当日活跃通道数
        $channelNum = 0;
        $channelNumRs = M("rec_cdrinfo")->field("count(distinct N_ChannelID) as cnt")->where("TO_DAYS(D_StartTime) = TO_DAYS(NOW())")->find();
        if(false !== $channelNumRs){
            $channelNum = $channelNumRs['cnt'];
        }
        
        //当前录音磁盘使用情况
        
        //当日各通道录音录像统计
        $channelData = array();
        $channelrs = M("sys_channelconfig")->select();
        if($channelrs){
            foreach ($channelrs as $k=>$v)
            {
                $channelData[$k]['n_channeltype'] = $v['n_channeltype'];
                $channelData[$k]['n_channelno'] = $v['n_channelno'];
                $tongji = getTodayChannelCount($v['n_channelno'],$days=""); 
                $channelData[$k]['voice_count'] = $tongji['voiceCnt'];
                $channelData[$k]['vedio_count'] = $tongji['vedioCnt'];
            }
        }
        
        //获取最近30天录音录像记录数
         $monthCount = getRecordVideoCount($seachType='month');
         /* echo "<pre>";
         print_r($monthCount);
         echo "</pre>"; */
         $this->assign("monthCount",$monthCount);
        $this->assign("CurrentPage",'home');
        $this->display();
    }
}