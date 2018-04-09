<?php if (!defined('THINK_PATH')) exit();?>﻿<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
        <meta name="description" content="">
        <meta name="author" content="">

	
        <title>录音系统</title>

        <link href="/Public/assets/bootstrap/css/style.default.css" rel="stylesheet">
		
    </head>

    <body>
		
                   <header>
            <div class="headerwrapper">
                <div class="header-left">
                    <a href="index.html" class="logo">
                        <img src="/Public/assets/images/picture/logo.png" alt="" /> 
                    </a>
                    <div class="pull-right">
                        <a href="#" class="menu-collapse">
                            <i class="fa fa-bars"></i>
                        </a>
                    </div>
                </div><!-- header-left -->
                <div class="header-right">
                    <div class="pull-right">
                        
                        <div class="btn-group btn-group-list btn-group-notification">
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                          
                              <span class="badge">6.2.0.5录音管理系统</span>
                            </button>
                           
                        </div><!-- btn-group -->
                        
                       
                       
                        
                    </div><!-- pull-right -->
                
            </div><!-- headerwrapper -->
        </header>
        
      
        <section>
            <div class="mainwrapper">
			   
			    <div class="leftpanel">
                    <div class="media profile-left">
                        <div class="panel-icon icon-globe"><i class="fa fa-user"></i></div>
                        <div class="media-body">
                            <h4 class="media-heading">admin</h4>
                            <span class="user-options"><a href="#"><i class="glyphicon glyphicon-user"></i></a>
                             
                              <a href="#"><i class="glyphicon glyphicon-log-out"></i></a>
							</span>
                        </div>
                    </div><!-- media -->
                    
                    
                    <ul class="nav nav-pills nav-stacked">
                        <li <?php if($CurrentPage == 'home'): ?>class='active'<?php endif; ?> ><a href="/home/index/index"><i class="fa fa-home"></i> <span>系统概况</span></a></li>
                        <li <?php if($CurrentPage == 'recordList'): ?>class='active'<?php endif; ?> ><a href="/home/record/recordList"><i class="fa fa-search-minus"></i> <span>录音查询</span></a></li>
                        <li <?php if($CurrentPage == 'recordCount'): ?>class='active'<?php endif; ?> ><a href="/home/record/recordCount"><i class="fa fa-bar-chart"></i> <span>录音统计</span></a></li>
                        <li class="parent"><a href="#"><i class="fa fa-id-card"></i> <span>账号管理</span></a>
                            <ul class="children"  <?php if($AccountSub == '1'): ?>style="display:block"<?php endif; ?>  >
                                <li <?php if($CurrentPage == 'userManage'): ?>class='active'<?php endif; ?> ><a href="/home/Account/userManage">用户管理</a></li>
                                <li <?php if($CurrentPage == 'phoneBook'): ?>class='active'<?php endif; ?> ><a href="/home/Account/phoneBook">电话簿</a></li>
                                <li <?php if($CurrentPage == 'departMent'): ?>class='active'<?php endif; ?> ><a href="/home/Account/departMent">分组管理</a></li>
                            </ul>
                        </li>
                        <li class="parent"><a href="#"><i class="fa fa-windows"></i> <span>系统管理</span></a>
                            <ul class="children"  <?php if($SystemSub == '1'): ?>style="display:block"<?php endif; ?> >
                                <li <?php if($CurrentPage == 'channel'): ?>class='active'<?php endif; ?> ><a href="/home/System/channelParameter">通道参数</a></li>
                                <li <?php if($CurrentPage == 'system'): ?>class='active'<?php endif; ?> ><a href="/home/System/systemParameter">系统参数</a></li>
                                <li <?php if($CurrentPage == 'license'): ?>class='active'<?php endif; ?> ><a href="/home/System/licenseInfo">注册信息</a></li>
                                <li <?php if($CurrentPage == 'disk'): ?>class='active'<?php endif; ?> ><a href="/home/System/diskParameter">硬盘参数</a></li>
                                <li <?php if($CurrentPage == 'ip'): ?>class='active'<?php endif; ?> ><a href="/home/System/ipLimint">IP限制</a></li>
                                <li><a href="sliders.html">工作站配置</a></li>
                                <li><a href="tabs-accordions.html">操作日志</a></li>  
                                <li><a href="tabs-accordions.html">警告日志</a></li>                              
                            </ul>
                        </li>
						
                        <li class="parent"><a href="#"><i class="fa fa-file-text"></i> <span>相关下载</span></a>
                            <ul class="children">
                                <li><a href="notfound.html">备份录音下载</a></li>
                                <li><a href="blank.html">录音软件</a></li>
                            </ul>
                        </li>
                       <li><a href="/home/Login/login_out"><i class="fa fa-sign-out"></i> <span>退出系统</span></a></li> 
                    </ul>
                   
					
					<!-- <ul class="nav nav-pills nav-stacked">
                        <li class="active"><a href="/index/index"><i class="fa fa-home"></i> <span>系统概况</span></a></li>
                        <li><a href="/index/record/recordList"><i class="fa fa-search-minus"></i> <span>用户管理</span></a></li>
						<li><a href="graphs.html"><i class="fa fa-bar-chart-o"></i> <span>Graphs &amp; Charts</span></a></li>
                        <li class="parent"><a href="#"><i class="fa fa-suitcase"></i> <span>UI Elements</span></a>
                            <ul class="children">
                                <li><a href="alerts.html">Alerts &amp; Notifications</a></li>
                                <li><a href="buttons.html">Buttons</a></li>
                                <li><a href="extras.html">Extras</a></li>
                                
                                
                                <li><a href="modals.html">Modals</a></li>
                                <li><a href="widgets.html">Panels &amp; Widgets</a></li>
                                <li><a href="sliders.html">Sliders</a></li>                                
                                <li><a href="tabs-accordions.html">Tabs &amp; Accordions</a></li>
                                <li><a href="typography.html">Typography</a></li>
                            </ul>
                        </li>
						<li><a href="icons.html"><i class="fa fa-cube"></i> <span>Icons</span></a></li>
                        <li class="parent"><a href="#"><i class="fa fa-edit"></i> <span>Forms</span></a>
                            <ul class="children">
                                <li><a href="code-editor.html">Code Editor</a></li>
                                <li><a href="general-forms.html">General Forms</a></li>
                                <li><a href="form-layouts.html">Layouts</a></li>
                                <li><a href="wysiwyg.html">Text Editor</a></li>
                                <li><a href="form-validation.html">Validation</a></li>
                                <li><a href="form-wizards.html">Wizards</a></li>
                            </ul>
                        </li>
                        <li class="parent"><a href="#"><i class="fa fa-bars"></i> <span>Tables</span></a>
                            <ul class="children">
                                <li><a href="basic-tables.html">Basic Tables</a></li>
                                <li><a href="data-tables.html">Data Tables</a></li>
                            </ul>
                        </li>
                        <li><a href="maps.html"><i class="fa fa-map-marker"></i> <span>Maps</span></a></li>
                        <li class="parent"><a href="#"><i class="fa fa-file-text"></i> <span>Pages</span></a>
                            <ul class="children">
                                <li><a href="notfound.html">404 Page</a></li>
                                <li><a href="blank.html">Blank Page</a></li>
                                <li><a href="calendar.html">Calendar</a></li>
                                <li><a href="invoice.html">Invoice</a></li>
                                <li><a href="locked.html">Locked Screen</a></li>
                                <li><a href="media-manager.html">Media Manager</a></li>
                                <li><a href="people-directory.html">People Directory</a></li>
                                <li><a href="profile.html">Profile</a></li>                                
                                <li><a href="search-results.html">Search Results</a></li>
                                <li><a href="signin.html">Sign In</a></li>
                                <li><a href="signup.html">Sign Up</a></li>
                            </ul>
                        </li>
                        
                    </ul> -->
                </div>
               
                <div class="mainpanel">
                    <div class="contentpanel">
                        <div class="row row-stat">
                        
                        <div class="col-md-2">
                                <div class="panel noborder">
                                    <div class="panel-heading noborder">
                                        <div class="panel-btns">
                                            <a href="#" class="panel-close tooltips" data-toggle="tooltip" title="Close Panel"><i class="fa fa-times"></i></a>
                                        </div><!-- panel-btns -->
                                        <div class="panel-icon icon-globe"><i class="fa fa-signal"></i></div>
                                        <div class="media-body">                                          
                                            <h3 class="mt5">322</h3>
											<h5 class="md-title nomargin">当日录音数量</h5>
                                        </div><!-- media-body -->
                                    </div><!-- panel-body -->
                                </div><!-- panel -->
                            </div><!-- col-md-3 -->
						    <div class="col-md-2">
                                <div class="panel noborder">
                                    <div class="panel-heading noborder">
                                        <div class="panel-btns">
                                            <a href="#" class="panel-close tooltips" data-toggle="tooltip" title="Close Panel"><i class="fa fa-times"></i></a>
                                        </div><!-- panel-btns -->
                                        <div class="panel-icon icon-user"><i class="fa fa-warning"></i></div>
                                        <div class="media-body">
                                            <h3 class="mt5">2123</h3>
                                            <h5 class="md-title nomargin">未处理告警</h5>											
                                        </div><!-- media-body -->
                                    </div><!-- panel-body -->
                                </div><!-- panel -->
                            </div><!-- col-md-3 -->
							
							
							
                            <div class="col-md-2">
                                <div class="panel noborder">
                                    <div class="panel-heading noborder">
                                        <div class="panel-btns">
                                            <a href="#" class="panel-close tooltips" data-toggle="tooltip" title="Close Panel"><i class="fa fa-times"></i></a>
                                        </div><!-- panel-btns -->
                                        <div class="panel-icon icon-envelope"><i class="fa fa-volume-control-phone"></i></div>
                                        <div class="media-body">                                           
                                            <h3 class="mt5">30</h3>
											<h5 class="md-title nomargin">当日活跃通道数</h5>
                                        </div><!-- media-body -->
                                    </div><!-- panel-body -->
                                </div><!-- panel -->
                            </div><!-- col-md-3 -->
							
							<div class="col-md-2">
                                <div class="panel noborder">
                                    <div class="panel-heading noborder">
                                        <div class="panel-btns">
                                            <a href="#" class="panel-close tooltips" data-toggle="tooltip" title="Close Panel"><i class="fa fa-times"></i></a>
                                        </div><!-- panel-btns -->
                                        <div class="panel-icon icon-gavel"><i class="fa fa-file-audio-o"></i></div>
                                        <div class="media-body">                                           
                                            <h3 class="mt5">23546</h3>
											<h5 class="md-title nomargin">未备份录音数</h5>
                                        </div><!-- media-body -->
                                    </div><!-- panel-body -->
                                </div><!-- panel -->
                            </div><!-- col-md-3 -->
                            
                            <div class="col-md-4">
                                <div class="panel noborder">
                                    <div class="panel-heading noborder">
                                        <div class="panel-btns">
                                            <a href="#" class="panel-close tooltips" data-toggle="tooltip" title="Close Panel"><i class="fa fa-times"></i></a>
                                        </div><!-- panel-btns -->
                                        <div class="panel-icon icon-gavel"><i class="fa  fa-hdd-o"></i></div>
                                        <div class="media-body">                                           
                                            <div class="progress" style="background-color:#babbbe">
												<div class="progress-bar" role="progressbar" aria-valuenow="60"
													 aria-valuemin="0" aria-valuemax="100" style="width: 40%;">
												</div>
											</div>
											 <h5 class="md-title nomargin" style="margin-top:10px;">已使用40% </h5> 
                                        </div><!-- media-body -->
                                    </div><!-- panel-body -->
                                </div><!-- panel -->
                            </div><!-- col-md-3 -->
                        </div><!-- row -->
						
						<!-- 最近一个月录音数据 -->					    
						<div class="row">
						    
                            <div class="col-md-12">
                               <!--  <div class="panel panel-default">
                                    <div class="panel-body padding15">
                                        <h5 class="md-title mt0 mb10">最近30天数据统计（单位：条）</h5>
                                        <div id="basicFlotLegend" class="flotLegend"></div>
                                        <div id="bar-chart" class="height300"></div>
                                    </div>panel-body
                                    
                                </div>panel -->
                                <div id="container" style="min-width:600px; height:250px;"></div>
                            </div>
                        </div><!-- row -->
                        <br>
                        <!-- 所有录音通道数据统计-->					    
						<div class="row">
						    
                            <div class="col-md-12">
                             
                                <div id="container2" style="min-width:600px; height:250px;"></div>
                            </div>
                        </div><!-- row -->
               
                        <div class="row">
                            <div class="col-md-12">
                                
                            </div><!-- col-md-4 -->
                        </div><!-- row -->
                        
						
						
	

                    </div><!-- contentpanel --> <div style="clear:both"></div>
                </div><!-- mainpanel -->
               
            </div><!-- mainwrapper -->
        </section>
		
        <script src="/Public/assets/bootstrap/js/jquery-1.11.1.min.js"></script>
        <script src="/Public/assets/bootstrap/js/bootstrap.min.js"></script>
        <script src="/Public/assets/bootstrap/js/hightstock/highstock.js"></script>
        <script src="/Public/assets/bootstrap/js/hightstock/exporting.js"></script>
        <script src="/Public/assets/bootstrap/js/hightstock/highcharts-zh_CN.js"></script>
        <script src="/Public/assets/bootstrap/js/custom.js"></script> 
 		<script>

 		$(function() {
 			$('#container').highcharts({
 		        title: {
 		            text: '数据统计'
 		        },
 		       subtitle: {
		            text: '今天各通道录音录像统计'
		        },
 		        xAxis: {
 		        	type: 'category',
 		            title: {
 		                text: null
 		            },
 		            min: 0,
 		            max: 4,
 		            scrollbar: {
 		                enabled: true
 		            },
 		           categories: ['1001通道(电路)', ' 1002通道(电路)', '1003通道(电路)', '1004通道(电路)', '1005通道(电路)'],
 		            tickLength: 0
 		        },
 		       yAxis: {
		            min: 0,
		            max: 800,
		            title: {
		                text: '录音记录',
		                align: 'high'
		            }
		        },
 		        plotOptions: {
 		            series: {
 		                stacking: 'normal'
 		            }
 		        },
 		        series: [{
 		            type: 'column',
 		            name: '录音',
 		            data: [30, 200, 300, 300, 40]
 		        }, {
 		            type: 'column',
 		            name: '录像',
 		            data: [20, 0, 500, 700, 60]
 		        }]
 		    });
 			
 		    
 		});

 		
 		
 		
 		
 		
 		var chart = Highcharts.chart('container2', {
 		    title: {
 		        text: '数据统计'
 		    },
 		    subtitle: {
 		        text: '最近一个月录音录像数据统计'
 		    },
 		    yAxis: {
 		        title: {
 		            text: '数据记录（条）'
 		        }
 		    },
 		    legend: {
 		        layout: 'vertical',
 		        align: 'right',
 		        verticalAlign: 'middle'
 		    },
 		    plotOptions: {
 		        series: {
 		            label: {
 		                connectorAllowed: false
 		            },
 		            pointStart: 10
 		        }
 		    },
 		    series: [{
 		        name: '录音',
 		        data: [43934, 52503, 57177, 69658, 97031, 119931, 137133, 154175]
 		    }, {
 		        name: '录像',
 		        data: [24916, 24064, 29742, 29851, 32490, 30282, 38121, 40434]
 		    }],
 		    responsive: {
 		        rules: [{
 		            condition: {
 		                maxWidth: 500
 		            },
 		            chartOptions: {
 		                legend: {
 		                    layout: 'horizontal',
 		                    align: 'center',
 		                    verticalAlign: 'bottom'
 		                }
 		            }
 		        }]
 		    }
 		});
 		
		</script>
		
    </body>
</html>