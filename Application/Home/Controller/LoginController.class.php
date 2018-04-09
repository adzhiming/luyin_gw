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
		    if($this->check_verify($captcha)==true){
				$userModel = M("sys_accountinfo");
				$where['password'] = $_REQUEST['password'];
				$where['username'] = $_REQUEST['username'];
				$rs                = $userModel->where($where)->find();
				$returnData['url']                   = "/home/index";
				if($rs) {
				    session("admininfo",$rs);
				    $returnData['msg'] = "登录成功";
				    $returnData['code'] =1;
				    
				}
				else{
				    $returnData['msg'] = "密码错误";
				    $returnData['code'] =1;
				}
			}
			else
			{
			    $returnData['msg'] = "验证码错误";
			    $returnData['code'] =1;
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
}