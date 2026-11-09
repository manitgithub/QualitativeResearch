<?php
/* @var $this DtInformationController */
/* @var $model DtInformation */

$this->breadcrumbs=array(
	'Dt Informations'=>array('index'),
	$model->name,
);

$this->menu=array(
	array('label'=>'List DtInformation', 'url'=>array('index')),
	array('label'=>'Create DtInformation', 'url'=>array('create')),
	array('label'=>'Update DtInformation', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete DtInformation', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage DtInformation', 'url'=>array('admin')),
);
?>

<h1>View DtInformation #<?php echo $model->id; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'name',
	),
)); ?>
