<?php
/* @var $this TypequtionController */
/* @var $model Typeqution */

$this->breadcrumbs=array(
	'Typequtions'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List Typeqution', 'url'=>array('index')),
	array('label'=>'Manage Typeqution', 'url'=>array('admin')),
);
?>

<h1>Create Typeqution</h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>