  <?php $this->layout = 'noThemes';?>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<?php error_reporting( error_reporting() & ~E_NOTICE );?>
<?php if($_GET['tree']=='1'){?>
  <iframe src="index.php?r=site/page&view=treedata2&idm=<?php echo $_GET['id'];?>" width="100%" height="400" frameBorder="0" scrolling="no"></iframe>
<?php } else if($_GET['tree']=='4'){?>
ข้อมูลมาจากสรุปประเด็นช่อง 1 วิเคราะห์ข้อความที่เหมือนกัน และระบุ รหัสผู้ตอบแบบสอบถาม
  <iframe src="index.php?r=site/page&view=treedata4&idm=<?php echo $_GET['id'];?>" width="100%" height="400" frameBorder="0" scrolling="no"></iframe>
<?php }else if($_GET['tree']=='5'){?>
ข้อมูลมาจากสรุปประเด็นช่อง 2 วิเคราะห์ข้อความที่เหมือนกัน และระบุ รหัสผู้ตอบแบบสอบถาม
  <iframe src="index.php?r=site/page&view=treedata5&idm=<?php echo $_GET['id'];?>" width="100%" height="400" frameBorder="0" scrolling="no"></iframe>
<?php }else if($_GET['tree']=='6'){?>
ข้อมูลมาจากสรุปประเด็นช่อง 3 วิเคราะห์ข้อความที่เหมือนกัน และระบุ รหัสผู้ตอบแบบสอบถาม
  <iframe src="index.php?r=site/page&view=treedata6&idm=<?php echo $_GET['id'];?>" width="100%" height="400" frameBorder="0" scrolling="no"></iframe>
<?php } else {?>
  <iframe src="index.php?r=site/page&view=treedata3&idm=<?php echo $_GET['id'];?>" width="100%" height="400" frameBorder="0" scrolling="no"></iframe>
<?php } ?>