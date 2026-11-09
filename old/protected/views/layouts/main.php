<?php /* @var $this Controller */
 if(Yii::app()->user->isGuest) { header('Location: index.php?r=site/login'); } 
 ?>            

<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>ระบบวิเคราะห์ข้อมูลเชิงคุณภาพ</title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <!-- Bootstrap 3.3.4 -->
    <link href="/theme/AdminLTE/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- Font Awesome Icons -->
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
    <link href="/theme/AdminLTE/plugins/datatables/dataTables.bootstrap.css" rel="stylesheet" type="text/css" />
  <link rel="stylesheet" href="/theme/AdminLTE/plugins/iCheck/all.css">

    <!-- Ionicons -->
    <link href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css" rel="stylesheet" type="text/css" />
    <!-- Theme style -->
    <link href="/theme/AdminLTE/dist/css/AdminLTE.min.css" rel="stylesheet" type="text/css" />
    <!-- AdminLTE Skins. Choose a skin from the css/skins 
         folder instead of downloading all of them to reduce the load. -->
    <link href="/theme/AdminLTE/dist/css/skins/_all-skins.min.css" rel="stylesheet" type="text/css" />
	<link href="https://fonts.googleapis.com/css?family=Kodchasan" rel="stylesheet">
<style type="text/css">
body{
font-family: 'Kodchasan', sans-serif;
}
</style>

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>
  <body class="skin-blue sidebar-mini">
    <!-- Site wrapper -->
    <div class="wrapper">
   <?php
   $useragent=$_SERVER['HTTP_USER_AGENT'];

if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))){}else{
?>
   
      <header class="main-header">
        <!-- Logo -->
        <a href="index.php" class="logo">
          <!-- mini logo for sidebar mini 50x50 pixels -->
          <!-- logo for regular state and mobile devices -->
        </a>
        <!-- Header Navbar: style can be found in header.less -->
        <nav class="navbar navbar-static-top" role="navigation">
          <!-- Sidebar toggle button-->
          <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <div class="navbar-custom-menu">
          </div>
        </nav>
      </header>
<?php } ?>
      <!-- =============================================== -->
      <!-- Left side column. contains the sidebar -->
      <aside class="main-sidebar">
        <!-- sidebar: style can be found in sidebar.less -->
        <section class="sidebar">
          <!-- Sidebar user panel -->
          <div class="user-panel">

              <p style="color:#FFFFFF"><?php echo $_SESSION['fullname'] ?></p>
              <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
          </div>
          <!-- search form 
          <form action="#" method="get" class="sidebar-form">
            <div class="input-group">
              <input type="text" name="q" class="form-control" placeholder="Search..."/>
              <span class="input-group-btn">
                <button type='submit' name='search' id='search-btn' class="btn btn-flat"><i class="fa fa-search"></i></button>
              </span>
            </div>
          </form>-->
          <!-- /.search form -->
          <!-- sidebar menu: : style can be found in sidebar.less -->
          <ul class="sidebar-menu">
            <li class="header"><i class="fa fa-dashboard"></i>
 เมนูหลัก</li>
<?php if($_GET['id']!=''){?>
   <li >
              <a href="index.php">
<i class="fa fa-backward" aria-hidden="true"></i><span>เลือกหัวข้องานวิจัย</span>
              </a>
            </li>

   <li >
              <a href="index.php?r=main/view&id=<?php echo $_GET[id];?>&menu=submenu">
                <i class="fa fa-dashboard"></i>
                <span>หน้าแรก</span>
              </a>
            </li>
                         
                   <li class="treeview">
              <a data-toggle="modal" href="index.php?r=qution/create&idmain=<?php echo $_GET[id];?>" data-target="#myModal">
                <i class="fa fa-pencil fa-fw"></i>
                <span>เพิ่มคำถาม</span>
              </a>
            </li>
                   <!--     <li class="treeview">
              <a data-toggle="modal"  data-target="#myModal" href="index.php?r=site/page&view=interview&idm=<?php echo $_GET[id];?>">
                <i class="fa fa-book fa-fw"></i>
                <span>บันทึกผู้ให้ข้อมูล</span>
              </a>
            </li>-->
            
                        <li><a href="#"onClick="javascript:window.open('index.php?r=site/page&view=interview&idm=<?php echo $_GET[id];?>', '','nenuber=no,toorlbar=no,location=no,scrollbars=no, status=no,resizable=no,width=1200,height=600,top=50 ' )">           <i class="fa fa-microphone"></i> <span>บันทึกผู้ให้ข้อมูล</span></a></li>

  <li class="treeview">
              <a href="index.php?r=conceptual&id=<?php echo $_GET[id];?>">
                <i class="fa fa-files-o"></i>
                <span>บันทึก ConceptualMap</span>
              </a>
            </li>
              <li class="treeview">
              <a href="index.php?r=site/page&view=search&id=<?php echo $_GET[id];?>&menu=submenu">
                <i class="fa fa-search"></i>
                <span>สืบค้นเนื้อหา</span>
              </a>
            </li>
                         <li class="treeview">
              <a href="index.php?r=site/page&view=search2&id=<?php echo $_GET[id];?>&menu=submenu">
                <i class="fa fa-search"></i>
                <span>สืบค้นเนื้อหาละเอียด</span>
              </a>
            </li> 
            
<?php } else {?> 
                       <li >
              <a href="index.php">
                <i class="fa fa-dashboard"></i>
                <span>หน้าแรก</span>
              </a>
            </li>

  <li class="treeview">
              <a href="index.php?r=main/create">
                <i class="fa fa-files-o"></i>
                <span>เพิ่มข้อมูลงานวิจัย</span>
              </a>
            </li>

<?php }?>
<?php if($_GET['id']!=''){?>
            <li class="header"> <i class="fa fa-page"></i> รายงาน</li>
            <li><a href="index.php?r=site/page&view=report1&id=<?php echo $_GET[id];?>&menu=submenu"><i class="fa fa-circle-o text-yellow"></i> <span>ข้อมูลตามผู้ให้ข้อมูล</span></a></li>
            <li><a href="index.php?r=site/page&view=report3&id=<?php echo $_GET[id];?>&menu=submenu"><i class="fa fa-circle-o text-yellow"></i> <span>ข้อมูลตามคำถามการวิจัย</span></a></li>
             <li><a href="index.php?r=site/page&view=report3edit&id=<?php echo $_GET[id];?>&menu=submenu"><i class="fa fa-circle-o text-yellow"></i> <span>ปรับปรุงข้อมูล<br>[แยกข้อคำถาม]</span></a></li>
           
            <li><a href="index.php?r=site/page&view=report3filter&id=<?php echo $_GET[id];?>&menu=submenu"><i class="fa fa-circle-o text-yellow"></i> <span>ข้อมูลตามคำถามการวิจัย<br>[แยกข้อคำถาม]</span></a></li>
        	 <li><a href="index.php?r=site/page&view=report3filterup1&id=<?php echo $_GET[id];?>&menu=submenu"><i class="fa fa-circle-o text-yellow"></i> <span>ข้อมูลตามคำถามการวิจัย<br>[ยกประเด็น1]</span></a></li>
			<li><a href="index.php?r=site/page&view=report3filterup2&id=<?php echo $_GET[id];?>&menu=submenu"><i class="fa fa-circle-o text-yellow"></i> <span>ข้อมูลตามคำถามการวิจัย<br>[ยกประเด็น2]</span></a></li>
 			<li><a href="index.php?r=site/page&view=report3full&id=<?php echo $_GET[id];?>&menu=submenu"><i class="fa fa-circle-o text-yellow"></i> <span>รายการข้อมูลยกประเด็น</span></a></li>
            <li><a href="index.php?r=site/page&view=report2&id=<?php echo $_GET[id];?>&menu=submenu"><i class="fa fa-circle-o text-yellow"></i> <span>สถิติ</span></a></li>
            <li><a href="#"onClick="javascript:window.open('index.php?r=site/page&view=tree&id=<?php echo $_GET[id];?>&tree=1', '','nenuber=no,toorlbar=no,location=no,scrollbars=no, status=no,resizable=no,width=1200,height=600,top=50 ' )">           <i class="fa fa-circle-o text-yellow"></i> <span>แผนผังความคิด</span></a></li>
   <!--         <li><a href="#"onClick="javascript:window.open('index.php?r=site/page&view=tree&id=<?php echo $_GET[id];?>&tree=2', '','nenuber=no,toorlbar=no,location=no,scrollbars=no, status=no,resizable=no,width=1200,height=600,top=50 ' )">           <i class="fa fa-circle-o text-yellow"></i> <span>Typolopy</span></a></li>-->
               <li><a href="#"onClick="javascript:window.open('index.php?r=site/page&view=tree&id=<?php echo $_GET[id];?>&tree=4', '','nenuber=no,toorlbar=no,location=no,scrollbars=no, status=no,resizable=no,width=1200,height=600,top=50 ' )">           <i class="fa fa-circle-o text-yellow"></i> <span>Typolopy</span></a></li>
         
                        <li><a href="#"onClick="javascript:window.open('index.php?r=site/page&view=tree&id=<?php echo $_GET[id];?>&tree=5', '','nenuber=no,toorlbar=no,location=no,scrollbars=no, status=no,resizable=no,width=1200,height=600,top=50 ' )">           <i class="fa fa-circle-o text-yellow"></i> <span>Typolopy2</span></a></li>

               <li><a href="#"onClick="javascript:window.open('index.php?r=site/page&view=tree&id=<?php echo $_GET[id];?>&tree=6', '','nenuber=no,toorlbar=no,location=no,scrollbars=no, status=no,resizable=no,width=1200,height=600,top=50 ' )">           <i class="fa fa-circle-o text-yellow"></i> <span>Typolopy3</span></a></li>

         
            <li><a href="index.php?r=site/page&view=report4&id=<?php echo $_GET[id];?>&menu=submenu"><i class="fa fa-circle-o text-yellow"></i> <span>ConceptualMap</span></a></li>

<?php }?>


<?php if($_SESSION['Group']=='1'){?>
  <li > <a href="index.php?r=userkey/admin"><i class="fa fa-user" aria-hidden="true"></i><span>ข้อมูลผู้ใช้</span> </a> </li>  
<?php }?>

                                 <li >
              <a href="index.php?r=site/logout">
<i class="fa fa-sign-out" aria-hidden="true"></i>
                <span>ออกจากระบบ</span>
              </a>
            </li>  

          </ul>
        </section>
        <!-- /.sidebar -->
      </aside>

      <!-- =============================================== -->

      <!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header)         <section class="content-header">
          <h1>
            Blank page
            <small>it all starts here</small>
          </h1>
          <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="#">Examples</a></li>
            <li class="active">Blank page</li>
          </ol>
        </section>
-->

        <!-- Main content -->
        <section class="content">
        
   	<?php echo $content; ?>


        </section><!-- /.content -->
      </div><!-- /.content-wrapper -->

      <footer class="main-footer">
        <div class="pull-right hidden-xs">
          <b>Version</b> 2.0
        </div>
        <strong>Copyright &copy; 2016 สำนักพยาบาลมหาวิทยาลัยวลัยลักษณ์.</strong> All rights reserved.
      </footer>
      
      <!-- Control Sidebar -->      
      <aside class="control-sidebar control-sidebar-dark">                
        <!-- Create the tabs -->
        <ul class="nav nav-tabs nav-justified control-sidebar-tabs">
          <li><a href="#control-sidebar-home-tab" data-toggle="tab"><i class="fa fa-home"></i></a></li>
          
          <li><a href="#control-sidebar-settings-tab" data-toggle="tab"><i class="fa fa-gears"></i></a></li>
        </ul>
        <!-- Tab panes -->
        <div class="tab-content">
          <!-- Home tab content -->
          <div class="tab-pane" id="control-sidebar-home-tab">
            <h3 class="control-sidebar-heading">Recent Activity</h3>
            <ul class='control-sidebar-menu'>
              <li>
                <a href='javascript::;'>
                  <i class="menu-icon fa fa-birthday-cake bg-red"></i>
                  <div class="menu-info">
                    <h4 class="control-sidebar-subheading">Langdon's Birthday</h4>
                    <p>Will be 23 on April 24th</p>
                  </div>
                </a>
              </li>
              <li>
                <a href='javascript::;'>
                  <i class="menu-icon fa fa-user bg-yellow"></i>
                  <div class="menu-info">
                    <h4 class="control-sidebar-subheading">Frodo Updated His Profile</h4>
                    <p>New phone +1(800)555-1234</p>
                  </div>
                </a>
              </li>
              <li>
                <a href='javascript::;'>
                  <i class="menu-icon fa fa-envelope-o bg-light-blue"></i>
                  <div class="menu-info">
                    <h4 class="control-sidebar-subheading">Nora Joined Mailing List</h4>
                    <p>nora@example.com</p>
                  </div>
                </a>
              </li>
              <li>
                <a href='javascript::;'>
                  <i class="menu-icon fa fa-file-code-o bg-green"></i>
                  <div class="menu-info">
                    <h4 class="control-sidebar-subheading">Cron Job 254 Executed</h4>
                    <p>Execution time 5 seconds</p>
                  </div>
                </a>
              </li>
            </ul><!-- /.control-sidebar-menu -->

            <h3 class="control-sidebar-heading">Tasks Progress</h3> 
            <ul class='control-sidebar-menu'>
              <li>
                <a href='javascript::;'>               
                  <h4 class="control-sidebar-subheading">
                    Custom Template Design
                    <span class="label label-danger pull-right">70%</span>
                  </h4>
                  <div class="progress progress-xxs">
                    <div class="progress-bar progress-bar-danger" style="width: 70%"></div>
                  </div>                                    
                </a>
              </li> 
              <li>
                <a href='javascript::;'>               
                  <h4 class="control-sidebar-subheading">
                    Update Resume
                    <span class="label label-success pull-right">95%</span>
                  </h4>
                  <div class="progress progress-xxs">
                    <div class="progress-bar progress-bar-success" style="width: 95%"></div>
                  </div>                                    
                </a>
              </li> 
              <li>
                <a href='javascript::;'>               
                  <h4 class="control-sidebar-subheading">
                    Laravel Integration
                    <span class="label label-waring pull-right">50%</span>
                  </h4>
                  <div class="progress progress-xxs">
                    <div class="progress-bar progress-bar-warning" style="width: 50%"></div>
                  </div>                                    
                </a>
              </li> 
              <li>
                <a href='javascript::;'>               
                  <h4 class="control-sidebar-subheading">
                    Back End Framework
                    <span class="label label-primary pull-right">68%</span>
                  </h4>
                  <div class="progress progress-xxs">
                    <div class="progress-bar progress-bar-primary" style="width: 68%"></div>
                  </div>                                    
                </a>
              </li>               
            </ul><!-- /.control-sidebar-menu -->         

          </div><!-- /.tab-pane -->
      <!-- Add the sidebar's background. This div must be placed
           immediately after the control sidebar -->
      <div class='control-sidebar-bg'></div>
    </div><!-- ./wrapper -->



    <!-- jQuery 2.1.4 -->
    <script src="/theme/AdminLTE/plugins/jQuery/jQuery-2.1.4.min.js"></script>
    <!-- Bootstrap 3.3.2 JS -->
    <script src="/theme/AdminLTE/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <!-- SlimScroll -->
    <script src="/theme/AdminLTE/plugins/slimScroll/jquery.slimscroll.min.js" type="text/javascript"></script>
    <!-- FastClick -->
    <script src='/theme/AdminLTE/plugins/fastclick/fastclick.min.js'></script>
    <!-- AdminLTE App -->

    <script src="/theme/AdminLTE/dist/js/app.min.js" type="text/javascript"></script>
     <script src="/theme/AdminLTE/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="/theme/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js"></script>
  <script src="/theme/AdminLTE/plugins/iCheck/icheck.min.js"></script>
 
    <!-- Demo -->
    <script src="/theme/AdminLTE/dist/js/demo.js" type="text/javascript"></script>   
    
     <script type="text/javascript">
      $(function () {
        $("#example1").dataTable( {
    "order": [0, "asc"],
	  "pageLength": 50,
    "columnDefs": [ {
      "targets"  : 'no-sort',
      "orderable": false,
    }]
});
        $("#example3").dataTable();
        $("#example4").dataTable();
        $("#example5").dataTable();
        $("#example6").dataTable();
        $("#example7").dataTable();
        $("#example8").dataTable();
        $("#example9").dataTable();
        $("#example10").dataTable();
        $("#example11").dataTable();
        $("#example12").dataTable();
        $("#example13").dataTable();
        $("#example14").dataTable();
        $("#example15").dataTable();
        $("#example16").dataTable();
        $("#example17").dataTable();
        $("#example18").dataTable();
        $("#example19").dataTable();

        $('#example2').dataTable();
      });
	      $('input[type="checkbox"].flat-red, input[type="radio"].flat-red').iCheck({
      checkboxClass: 'icheckbox_flat-green',
      radioClass   : 'iradio_flat-green'
    })

    </script>
  </body>
</html>