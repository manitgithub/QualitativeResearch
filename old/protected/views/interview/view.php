<?php
/* @var $this InterviewController */
/* @var $model Interview */

$this->breadcrumbs=array(
	'Interviews'=>array('index'),
	$model->id,
);

$this->menu=array(
	array('label'=>'List Interview', 'url'=>array('index')),
	array('label'=>'Create Interview', 'url'=>array('create')),
	array('label'=>'Update Interview', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete Interview', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage Interview', 'url'=>array('admin')),
);
?>

<h1>View Interview #<?php echo $model->id; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'peopleid',
		'qid',
		'value',
	),
)); ?>
