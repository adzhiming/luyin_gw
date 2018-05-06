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
        $rsUsing = M('sys_diskspaceinfo')->join("tab_rec_recorddiskcfg a")->field($field)->where("V_RecordDiskPath  like CONCAT(V_DiskVolumeName,'%')")->select();
        $curDisk = substr($rsUsing[0]['v_recorddiskpath'],0,2);
        $curDiskNum = substr($rsUsing[0]['v_recorddiskpath'],0,1);
        if($curDisk){
             $diskinfo = M('sys_diskspaceinfo')->where("V_DiskVolumeName = '{$curDisk}'")->find();
             $yiyong = (100-round($diskinfo["n_freespace"]/$diskinfo["n_totalspace"]*100,2));
        }
        //当日各通道录音录像统计
        $channelData = array();
        $channelCat = array();
        $voice_count = array();
        $video_count = array();
        $channelrs = M("sys_channelconfig")->select();
        if($channelrs){
            foreach ($channelrs as $k=>$v)
            {
                $tongji = array();
                $channelData[$k]['n_channeltype'] = $v['n_channeltype'];
                $channelData[$k]['n_channelno'] = $v['n_channelno'];
                $tongji = getTodayChannelCount($v['n_channelno'],$days=""); 
                $voice_count[] = $tongji['voiceCnt'];
                $video_count[] = $tongji['videoCnt'];
          
                $channelCat[] = $v['n_channelno']."号通道";
            }
        }
       
        //获取最近30天录音录像记录数
        $days = array();
        $voice_day_count = array();
        $video_day_count = array();
         $monthCount = getRecordVideoCount($seachType='month');
         foreach ($monthCount as $kl => $vl) {
              $days[] = $kl;
              $voice_day_count[] = $kl['voiceCnt'];
              $video_day_count[] = $kl['videoCnt'];
         }
       /* echo "<pre>";
        print_r($monthCount);
        echo "</pre>";*/

        $this->assign("channelCat",json_encode($channelCat));
        $this->assign("voice_count",json_encode($voice_count));
        $this->assign("video_count",json_encode($video_count));

        $this->assign("days",json_encode($days));
        $this->assign("voice_day_count",json_encode($voice_day_count));
        $this->assign("video_day_count",json_encode($video_day_count));

        $this->assign("curDiskNum",$curDiskNum);
        $this->assign("yiyong",$yiyong);
        $this->assign("todayRecordNum",$todayRecordNum);
        $this->assign("alarmlogNum",$alarmlogNum);
        $this->assign("channelNum",$channelNum);
        $this->assign("CurrentPage",'home');
        $this->display();
    }
}