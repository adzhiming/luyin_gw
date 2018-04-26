<?php
namespace Home\Controller;
use Think\Controller;
use Think\Model;
use Think\Db;
use Think\Log;

class OtherController extends AuthController {
    public function _initialize() {
    }
    
    public function index(){
        $this->display();
    }

    public function download(){
        $sEcho = $_REQUEST['sEcho']; // DataTables 用来生成的信息
        $output['sEcho'] = $sEcho;
        $start = isset($_REQUEST['start'])?$_REQUEST['start']:0;
        $length = isset($_REQUEST['length'])?$_REQUEST['length']:10;
        $searchTime = $this->getParam("searchTime",date("Ym",strtotime("-100 day")));
        $orderby=isset($_POST["orderby"])?$_POST["orderby"]:"asc";
        if(IS_AJAX){
            
            $field =" *,left(substring_index(V_DstName,'\\\\',-1),8) as `vDay` ";
            $rs = M('bak_record')->field($field)->where("left(substring_index(V_DstName,'\\\\',-1),6)='{$searchTime}' and N_Status=1 ")
                 ->order("left(substring_index(V_DstName,'\\\\',-1),8) {$orderby}")->select();
            $total = count($rs);
            
            $rs = M('bak_record')->field($field)->where("left(substring_index(V_DstName,'\\\\',-1),6)='{$searchTime}' and N_Status=1 ")
            ->order("left(substring_index(V_DstName,'\\\\',-1),8) {$orderby}")->limit($start,$length)->select();
            if($rs){
                foreach ($rs as $k=>$v){
                    if(isset($v["vday"])){
                        if(isset($RecList[$v["vday"]]["dstname"])){
                            $RecList[$v["vday"]]["dstname"].="<@>".$v["v_dstname"];
                        }else{
                            $RecList[$v["vday"]]["dstname"]=$v["v_dstname"];
                        }
                    }else{
                        $RecList[$v["vday"]]["dstname"]=$v["v_dstname"];
                    }
                }
            }
            if($RecList){
                $list = array();
                $i=0;
                $str = "";
                foreach ($RecList as $kl=>$vl){
                    $list[$i]['datetime'] = $this->dateformat($kl,2);
                    $fielinfo = pathinfo($vl['dstname']);
                    $list[$i]['fielurl'] = $this->formatURL($vl['dstname']);
                    $list[$i]['basename'] = $fielinfo['basename'];
                    if(!file_exists($list[$i]['fielurl'])){
                        $str = "<a href='Javascript:alert(\"下载失败，可能文件已被删除\")'>";
                        $str .= "<img src='/Public/assets/images/rar.gif' height='20' border='0' alt='文件不存在' />{$fielinfo['basename']}</a>　";
                    }else{
                        $str = "<a href='".$list[$i]['fielurl']."' title='{$vl['dstname']}'>";
                        $str .= "<img src='/Public/assets/images/rar.gif' height='20' border='0' alt='点击下载文件' />{$fielinfo['basename']}</a>　";
                    }
                    $list[$i]['showinfo'] = $str;
                    $i++;
                }
            }
            $output['aaData'] = $list;
            $output['iTotalDisplayRecords'] = $total;    //如果有全局搜索，搜索出来的个数
            $output['iTotalRecords'] = $total; //总共有几条数据
            echo json_encode($output); //最后把数据以json格式返回
            die;
        }
        $this->assign("CurrentPage",'download');
        $this->display();
    }
    
    public function dateformat($date,$mode){
        if($mode==1){
            return substr($date,0,4)."年".substr($date,4,2)."月";
        }else{
            return substr($date,0,4)."年".substr($date,4,2)."月".substr($date,6,2)."日";
        }
    }
    
    //格式化备份文件的URL
    public function formatURL($path){
        $sql="select a.N_StationID";
        $sql.=" from tab_sys_diskspaceinfo, tab_rec_RecordDiskCfg a where V_RecordDiskPath like CONCAT(V_DiskVolumeName,'%')";
        //echo $sql;
        $rsUsing=mysql_query($sql);
        $UsingStation=mysql_result($rsUsing,0,"N_StationID");//当前使用工作站号
        //备份目录格式D:\RecFileBak\200911\20091126.zip
        //需要从中截取备份路径部分(D:\RecFileBak\)，转换成虚拟目录路径
        $path=str_replace("\\","/",$path);	//备份目录路径的年月+文件名部分
        //echo $path;
        $path=explode("/",$path);
        $url="";
        for($n=0;$n<count($path)-2;$n++){	//截取备份路径部分。最后2个元素是年月文件夹和文件名，不属于系统设定的备份路径
            if($url==""){
                $url=$path[$n];
            }else{
                $url.="_".$path[$n];
            }
            
        }
        //格式化成虚拟路径(“:”和“\”都替换成下划线“_”)
        $url=str_replace(":","_",$url);
        $folder="/".$path[count($path)-2]."/".$path[count($path)-1];
        //和服务器IP、端口，构建成完整的URL地址
        $url=host()."STD".$UsingStation."_".$url.$folder;
        //echo $url;
        return $url;
    }
    
    
}