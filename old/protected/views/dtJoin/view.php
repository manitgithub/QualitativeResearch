<?php
/* @var $this DtJoinController */
/* @var $model DtJoin */

$this->breadcrumbs=array(
	'Dt Joins'=>array('index'),
	$model->id,
);

$this->menu=array(
	array('label'=>'List DtJoin', 'url'=>array('index')),
	array('label'=>'Create DtJoin', 'url'=>array('create')),
	array('label'=>'Update DtJoin', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete DtJoin', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage DtJoin', 'url'=>array('admin')),
);
?>

<h1>View DtJoin #<?php echo $model->id; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'mainid',
		'userid',
	),
)); ?>
