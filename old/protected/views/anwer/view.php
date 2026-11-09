<?php
/* @var $this AnwerController */
/* @var $model Anwer */

$this->breadcrumbs=array(
	'Anwers'=>array('index'),
	$model->name,
);

$this->menu=array(
	array('label'=>'List Anwer', 'url'=>array('index')),
	array('label'=>'Create Anwer', 'url'=>array('create')),
	array('label'=>'Update Anwer', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete Anwer', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage Anwer', 'url'=>array('admin')),
);
?>

<h1>View Anwer #<?php echo $model->id; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'name',
		'qution_id',
		'value',
	),
)); ?>
