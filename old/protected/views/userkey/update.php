<?php
/* @var $this UserkeyController */
/* @var $model Userkey */

$this->breadcrumbs=array(
	'Userkeys'=>array('index'),
	$model->Name=>array('view','id'=>$model->UserID),
	'Update',
);

$this->menu=array(
	array('label'=>'List Userkey', 'url'=>array('index')),
	array('label'=>'Create Userkey', 'url'=>array('create')),
	array('label'=>'View Userkey', 'url'=>array('view', 'id'=>$model->UserID)),
	array('label'=>'Manage Userkey', 'url'=>array('admin')),
);
?>

<h1>Update Userkey <?php echo $model->UserID; ?></h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>