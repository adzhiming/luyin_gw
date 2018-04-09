<?php if (!defined('THINK_PATH')) exit();?>﻿<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
        <meta name="description" content="">
        <meta name="author" content="">

	
        <title>登录系统</title>

        <style type="text/css">
*{
    font: 13px/1.5 'å¾®è½¯é›…é»‘', Verdana, Helvetica, Arial, sans-serif;
    -webkit-box-sizing: border-box;
    -moz-box-sizing: border-box;
    -box-sizing: border-box;
    padding:0;
    margin:0;
    list-style:none;
    box-sizing: border-box;
}

body,html{
    height:100%;
    overflow:hidden;
}
body{
    background-color:#f0f1ef;
    background-size: cover;
}
a{
    color:#27A9E3;
    text-decoration:none;
    cursor:pointer;
}
.login{
    margin: 150px auto 0 auto;
    min-height: 420px;
    max-width: 420px;
    padding: 40px;
    background-color: #ffffff;
    margin-left: auto;
    margin-right: auto;
    border-radius: 4px;
    /* overflow-x: hidden; */
    box-sizing: border-box;
}
a.logo{
    display: block;
    height: 58px;
    width: 167px;
    margin: 0 auto 30px auto;
    background-size: 167px 42px;
}
.message {
    margin: -20px -41px 0 -58px;
    padding: 18px 10px 18px 60px;
    background: #27A9E3;
    position: relative;
    color: #fff;
    font-size: 16px;
	text-align: center;
}
#darkbannerwrap {
    background: url(../../Public/assets/images/aiwrap.png);
    width: 18px;
    height: 10px;
    margin: 0 0 20px -58px;
    position: relative;
}

input[type=text],
input[type=file],
input[type=password],
input[type=email], select {
    border: 1px solid #DCDEE0;
    vertical-align: middle;
    border-radius: 3px;
    height: 50px;
    padding: 0px 16px;
    font-size: 14px;
    color: #555555;
    outline:none;
    width:100%;
}
input[type=text]:focus,
input[type=file]:focus,
input[type=password]:focus,
input[type=email]:focus, select:focus {
    border: 1px solid #27A9E3;
}


input[type=submit],
input[type=button]{
    display: inline-block;
    vertical-align: middle;
    padding: 12px 24px;
    margin: 0px;
    font-size: 18px;
    line-height: 24px;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    cursor: pointer;
    color: #ffffff;
    background-color: #27A9E3;
    border-radius: 3px;
    border: none;
    -webkit-appearance: none;
    outline:none;
    width:100%;
}
hr.hr15 {
    height: 15px;
    border: none;
    margin: 0px;
    padding: 0px;
    width: 100%;
}
hr.hr20 {
    height: 20px;
    border: none;
    margin: 0px;
    padding: 0px;
    width: 100%;
}

.copyright{
    font-size:14px;
    color:rgba(255,255,255,0.85);
    display:block;
    position:absolute;
    bottom:15px;
    right:15px;
}

.loginbtn{
	    display: inline-block;
    vertical-align: middle;
    padding: 12px 24px;
    margin: 0px;
    font-size: 18px;
    line-height: 24px;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    cursor: pointer;
    color: #ffffff;
    background-color: #27A9E3;
    border-radius: 3px;
    border: none;
    -webkit-appearance: none;
    outline: none;
    width: 100%;
}
       </style>
    </head>

    <body>
        
        <section>
				   <div class="login" >
					    <div class="message">录音管理系统</div>
					    <div id="darkbannerwrap"></div>
					    
					    <form  >
							<input name="action" value="login" type="hidden">
							<input name="username" id="username"  placeholder="用户名" required=""  type="text">
							<hr class="hr15">
							<input name="password"  id="password" placeholder="密码" required="" type="password">
							<hr class="hr15">
							<div>
								<div style="width:60%;float:left">
								<input name="captcha"  id="captcha" placeholder="验证码" required="" type="text" style="width:100%">
								</div>
								<div style="width:39%;float:left;padding:0;"  class="identimg" >
							    <img  id="verify" src="/home/login/get_verify_png" alt="点击刷新" style="width:100%;height: 49px;"/>
							   
							    </div>
							     <p class="tips" style="color:#ff0000;text-align:center;display:none "></p>
							</div>
							<hr class="hr15">
							<div class="loginbtn" onclick="submit()">登录</div>
							<hr class="hr20">
							<!-- 帮助 <a onClick="alert('请联系管理员')">忘记密码</a> -->
						</form>
				</div>
			   
                
        </section>
		
        <script src="/Public/assets/bootstrap/js/jquery-1.11.1.min.js"></script>

		<script type="text/javascript">
		$('.identimg').click(function (){
			  $('#verify').attr('src','/home/login/get_verify_png?t='+new Date().getTime());
		})
		
		//登录
	function submit(){
		$(".tips").hide();
            var username = $("#username").val();
            if ($.trim(username) == "") {
                $(".tips").text("请输入用户名");
                $(".tips").show();
                return;
			}
            var password = $("#password").val();
            if ($.trim(password) == "") {
                $(".tips").text("请输入密码");
                $(".tips").show();
                return;
			}
            var captcha = $("#captcha").val();
            if ($.trim(captcha) == "") {
                $(".tips").text("请输入验证码");
                $(".tips").show();
                return;
			}

	
		$.ajax({
			type:"post",
			url:"/home/login/login/",
			async:true,
			data:{username:username,password:password,captcha:captcha},
			success:function(data){
				 console.log(data)
			    if(data.code){
				    $(".tips").text(data.msg);
					$(".tips").show();
					location.href=data.url;
				}else{
				   
					$(".tips").text(data.msg);
                	$(".tips").show();
                	getAgain();
					return;
				} 
			}
		});
	}
	
 
 function getAgain(){
    $('#verify').attr('src','/home/login/get_verify_png?t='+new Date().getTime());
 }
		</script>
    </body>
</html>