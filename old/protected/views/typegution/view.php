<?php
/* @var $this TypegutionController */
/* @var $model Typegution */

$this->breadcrumbs=array(
	'Typegutions'=>array('index'),
	$model->name,
);

$this->menu=array(
	array('label'=>'List Typegution', 'url'=>array('index')),
	array('label'=>'Create Typegution', 'url'=>array('create')),
	array('label'=>'Update Typegution', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete Typegution', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage Typegution', 'url'=>array('admin')),
);
?>

<h1>View Typegution #<?php echo $model->id; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'name',
	),
)); ?>
