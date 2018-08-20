<?php
namespace Home\Controller;
use Think\Controller;
use Think\Verify;
use Think\Model;
use Think\Db;

class LoginController extends Controller
{
	public function index()
	{
		 
		$this->assign("CurrentPage",'home');
        $this->display();
	}
	
	public function login(){
	  
		if(IS_POST){
		    $captcha = I('post.captcha');
		    $where = array();
		   // if($this->check_verify($captcha)==true){
		    if(1){	
				$userModel = M("sys_accountinfo");
				$where['V_Password'] = $_REQUEST['password'];
				$where['V_AccountName'] = $_REQUEST['username'];
				//检测是否从monitor登录，如果有则从HID_INAME接收用户名
				if(isset($_POST["HID_INAME"])){	
					if($_POST["HID_INAME"]!=""){
						$where['V_AccountName'] =$_POST["HID_INAME"];
					}
				}
				$upwd=isset($_POST["IPASSWORD"])?$_POST["IPASSWORD"]:"";

				if($upwd != ''){
                    $where['V_Password'] = $_REQUEST['password'];
				}
				$rs  = $userModel->where($where)->find();
				
				if($rs) {
				    session("uAccount",$rs['v_accountid']);//用户ID
				    session("uName",$rs['v_accountname']);//用户账号
				    session("uRights",$rs['n_afairtype']);//用户权限
				    session("uLogTime",date("Y-m-d H:i:s"));//登录时间
				    session("uPrivilege",$rs['n_privilege']);//有权设备
				    session("uLogIP",getenv('REMOTE_ADDR'));//登录地址
				    session("V_Ext",$rs['v_ext']);//有权分机
				    session("V_SID",$rs['v_sid']);//有权工作站
				    session("admininfo",$rs);
				    
				    $returnData['url']                   = "/home/record/recordList";
				    $returnData['msg'] = "登录成功";
				    $returnData['code'] =1;
				    
				}
				else{
				    $returnData['msg'] = "密码错误";
				    $returnData['code'] =0;
				}
			}
			else
			{
			    $returnData['msg'] = "验证码错误";
			    $returnData['code'] =0;
			}
			//$returnData = (object) $returnData;
			$this->ajaxReturn($returnData);
		}
	}
	
	//退出登录
	public function login_out(){
		session("admininfo",null);
		$redirect ="/home/login/index";
		$this->success('退出成功', $redirect);
	}
	
	//验证码生成
	public function get_verify_png(){
		header("Content-type: image/png");
		$config =    array(
				'fontSize'    =>    50,    // 验证码字体大小
				'length'      =>    5,     // 验证码位数
				'useNoise'    =>    true, // 关闭验证码杂点
				'useCurve' => false,
				'fontttf' => '4.ttf',
				'imageW' => 0,
				'imageH' => 0
		);
		$Verify = new  Verify($config);
		$Verify->codeSet = '0123456789';
		$Verify->entry();
	}
	
	// 检测输入的验证码是否正确，$code为用户输入的验证码字符串
	public function check_verify($code, $id = ''){
		$verify = new  Verify();
		return $verify->check($code, $id);
	}

    public function uploadtest(){

       $this->assign("CurrentPage",'home');
        $this->display();
    }

	public function upload_translate(){
		$upload = new \Think\Upload();// 实例化上传类
	    $upload->maxSize   =     31457280 ;// 设置附件上传大小
	    $upload->exts      =     array('txt');// 设置附件上传类型
	    $upload->rootPath  =     './Uploads/'; // 设置附件上传根目录
	    $upload->savePath  =     ''; // 设置附件上传（子）目录
	    $upload->saveName = ''; //保持文件名不变
	    $upload->autoSub = true;
	    $upload->replace = true;
        $upload->subName = array('date','Ymd');
	    // 上传文件 
	    $info   =   $upload->upload();
	    $returnData = array();
	    if(!$info) {// 上传错误提示错误信息 
	        $returnData['msg'] = $upload->getError();
			$returnData['code'] =0;
	    }else{// 上传成功
	    	//获取文件内容
	       foreach($info as $file){               
	    	 	$FilePath = $file['savepath'].$file['savename'];
           }
            $fileurl = './Uploads/'.$FilePath;
            if(file_exists($fileurl)){ 
               $houzhui = substr(strrchr($file['savename'], '.'), 1);
               $fileid = basename($file['savename'],".".$houzhui); 
               $content = file_get_contents($fileurl);
               $content = trim($content);
               $rs = M("mgrid","cr_")->where("mgrid = '{$fileid}'")->save(array('contents'=>$content));
               if(false !== $rs){
                  $returnData['msg'] = "上传成功!";
			      $returnData['code'] =1;
               }
               else{
               	  $returnData['msg'] = "文件上传成功,翻译内容保存失败";
			      $returnData['code'] =0;
               }
            }
            else
            {
                $returnData['msg'] = "文件上传成功,翻译内容保存失败";
			    $returnData['code'] =0;
            }
	    	
	        
	    }
	    $this->ajaxReturn($returnData);
	}

	//获取录音文件
	public function getRecordUrl(){
		$mgrid = $_REQUEST['mgrid'];
		if(empty($mgrid)){
		   $returnData['code'] =0;	
           $returnData['msg'] = "参数不能为空";
		   $returnData['data'] ="";
		}
		$rs = M('mgrid','cr_')->where("mgrid = '{$mgrid}'")->find();
		  
		if($rs){
			$returnData['msg'] = "获取成功";
		    $returnData['code'] = 1;
		    $returnData['data'] = host().$rs['fileurl'];
		}
		else{
			$returnData['msg'] = "录音文件不存在，获取失败";
		    $returnData['code'] = 0;
		    $returnData['data'] = "";
		}
		$this->ajaxReturn($returnData);
	}


	public function getfanyi(){
		$n_sn = $_REQUEST['n_sn'];
		if(empty($n_sn)){
		   $returnData['code'] =0;	
           $returnData['msg'] = "参数不能为空";
		   $returnData['data'] ="";
		}
		$rs = M('mgrid','cr_')->where("N_SN = '{$n_sn}'")->find();
		  
		if($rs){
			$returnData['msg'] = "获取成功";
		    $returnData['code'] = 1;
		    $returnData['data'] = $rs['contents'];
		}
		else{
			$returnData['msg'] = "翻译内容获取失败";
		    $returnData['code'] = 0;
		    $returnData['data'] = "";
		}
		$this->ajaxReturn($returnData);		
	}
}