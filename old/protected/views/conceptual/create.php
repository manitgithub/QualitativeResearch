<?php
/* @var $this ConceptualController */
/* @var $model Conceptual */

$this->breadcrumbs=array(
	'Conceptuals'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List Conceptual', 'url'=>array('index')),
	array('label'=>'Manage Conceptual', 'url'=>array('admin')),
);
?>

<h1>Create Conceptual</h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>