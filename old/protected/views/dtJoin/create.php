    <meta charset="UTF-8">

<?php
/* @var $this DtJoinController */
/* @var $model DtJoin */

$this->breadcrumbs=array(
	'Dt Joins'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List DtJoin', 'url'=>array('index')),
	array('label'=>'Manage DtJoin', 'url'=>array('admin')),
);
?>

<h3>เพิ่มผู้ใช้งานระบบ</h3>
<?php $this->renderPartial('_form', array('model'=>$model)); ?>