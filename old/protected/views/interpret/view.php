<?php
/* @var $this InterpretController */
/* @var $model Interpret */

$this->breadcrumbs=array(
	'Interprets'=>array('index'),
	$model->name,
);

$this->menu=array(
	array('label'=>'List Interpret', 'url'=>array('index')),
	array('label'=>'Create Interpret', 'url'=>array('create')),
	array('label'=>'Update Interpret', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete Interpret', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage Interpret', 'url'=>array('admin')),
);
?>

<h1>View Interpret #<?php echo $model->id; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'name',
		'types',
		'anwer_id',
	),
)); ?>
