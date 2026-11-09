<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
</head>

<body>
<div class="row">          
                   <div class="col-md-12 col-sm-6">


                                     <table class="table"  id="example1">
    				<thead>		
    				 <tr>
     					<th>หัวข้อ</th>
     				</tr>
                    </thead> 
                <tbody>
                       <?php 
$user = Yii::app()->session['UserID'];
$r = 0;
if($_SESSION['Group']=='1'){
$Main = Main::model()->findAll();
}else {
$Main = Main::model()->findAll(array("condition"=>" userkey = '$user'"));

}
foreach($Main as $Mains) {
$r++;
?>
<tr> <td>
                                                <div class="box box-success">

                <div class="box-header with-border">
                  <h3 class="box-title"><?php echo $Mains->name;?></h3>
                  <div class="box-tools pull-right">
                    <button class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
                  </div><!-- /.box-tools -->
                </div><!-- /.box-header -->
                <div class="box-body">
 <a href="/index.php?r=site/page&view=interviewm&idm=<?php echo $Mains->id;?>" class="btn btn-success btn-block"><i class="fa fa-microphone" aria-hidden="true"></i><span> สัมภาษณ์ทุกหัวข้อ</span></a>

 <a href="/index.php?r=main/viewm&id=<?php echo $Mains->id;?>" class="btn btn-info btn-block"><i class="fa fa-microphone" aria-hidden="true"></i><span> สัมภาษณ์รายหัวข้อ</span></a>
            <?php 

			$dtjoin = DtJoin::model()->findAll(array("condition"=>"mainid = '$Mains->id'"));
foreach($dtjoin as $data) {
?><br>M:
<?=Userkey::model()->findByPk($data->userid)->Name?>
<?php
}
			?>                         
</div></div>
   
    

</td></tr>     <?php } ?></tbody>
</table>
</div></div>

	</div>	</div>	</div>			



				</div>

</body>
</html>
