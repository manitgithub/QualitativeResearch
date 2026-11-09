<?php
/* @var $this DtInformationController */
/* @var $model DtInformation */

$this->breadcrumbs=array(
	'Dt Informations'=>array('index'),
	$model->name=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List DtInformation', 'url'=>array('index')),
	array('label'=>'Create DtInformation', 'url'=>array('create')),
	array('label'=>'View DtInformation', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Manage DtInformation', 'url'=>array('admin')),
);
?>

<h1>Update DtInformation <?php echo $model->id; ?></h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>