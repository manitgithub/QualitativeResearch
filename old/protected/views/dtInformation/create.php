<?php
/* @var $this DtInformationController */
/* @var $model DtInformation */

$this->breadcrumbs=array(
	'Dt Informations'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List DtInformation', 'url'=>array('index')),
	array('label'=>'Manage DtInformation', 'url'=>array('admin')),
);
?>

<h1>Create DtInformation</h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>