<?php
/* @var $this QutionController */
/* @var $model Qution */

$this->breadcrumbs=array(
	'Qutions'=>array('index'),
	$model->name,
);

$this->menu=array(
	array('label'=>'List Qution', 'url'=>array('index')),
	array('label'=>'Create Qution', 'url'=>array('create')),
	array('label'=>'Update Qution', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete Qution', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage Qution', 'url'=>array('admin')),
);
?>

<h1>View Qution #<?php echo $model->id; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'main_id',
		'name',
		'detail',
		'datekey',
		'userkey',
		'type_id',
	),
)); ?>
