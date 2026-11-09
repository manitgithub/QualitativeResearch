<?php
/* @var $this TypegutionController */
/* @var $model Typegution */

$this->breadcrumbs=array(
	'Typegutions'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List Typegution', 'url'=>array('index')),
	array('label'=>'Manage Typegution', 'url'=>array('admin')),
);
?>

<h1>Create Typegution</h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>