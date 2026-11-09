<?php
/* @var $this TypequtionController */
/* @var $model Typeqution */

$this->breadcrumbs=array(
	'Typequtions'=>array('index'),
	$model->name,
);

$this->menu=array(
	array('label'=>'List Typeqution', 'url'=>array('index')),
	array('label'=>'Create Typeqution', 'url'=>array('create')),
	array('label'=>'Update Typeqution', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete Typeqution', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage Typeqution', 'url'=>array('admin')),
);
?>

<h1>View Typeqution #<?php echo $model->id; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'name',
	),
)); ?>
