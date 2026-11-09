<?php
/* @var $this TypequtionController */
/* @var $model Typeqution */

$this->breadcrumbs=array(
	'Typequtions'=>array('index'),
	$model->name=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List Typeqution', 'url'=>array('index')),
	array('label'=>'Create Typeqution', 'url'=>array('create')),
	array('label'=>'View Typeqution', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Manage Typeqution', 'url'=>array('admin')),
);
?>

<h1>Update Typeqution <?php echo $model->id; ?></h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>