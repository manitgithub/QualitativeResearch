<?php
/* @var $this UserkeyController */
/* @var $model Userkey */

$this->breadcrumbs=array(
	'Userkeys'=>array('index'),
	$model->Name,
);

$this->menu=array(
	array('label'=>'List Userkey', 'url'=>array('index')),
	array('label'=>'Create Userkey', 'url'=>array('create')),
	array('label'=>'Update Userkey', 'url'=>array('update', 'id'=>$model->UserID)),
	array('label'=>'Delete Userkey', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->UserID),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage Userkey', 'url'=>array('admin')),
);
?>

<h1>View Userkey #<?php echo $model->UserID; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'UserID',
		'PrefixID',
		'Name',
		'Surname',
		'LoginName',
		'PWD',
		'DivID',
		'PositionID',
		'Group',
		'LastUpdate',
		'LastUser',
		'pic',
		'Stats',
	),
)); ?>
