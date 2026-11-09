<?php
/* @var $this ConceptualController */
/* @var $model Conceptual */

$this->breadcrumbs=array(
	'Conceptuals'=>array('index'),
	$model->name,
);

$this->menu=array(
	array('label'=>'List Conceptual', 'url'=>array('index')),
	array('label'=>'Create Conceptual', 'url'=>array('create')),
	array('label'=>'Update Conceptual', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete Conceptual', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage Conceptual', 'url'=>array('admin')),
);
?>

<h1>View Conceptual #<?php echo $model->id; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'name',
		'main_id',
		'input',
		'process',
		'output',
		'problem',
		'success',
		'datekey',
	),
)); ?>
