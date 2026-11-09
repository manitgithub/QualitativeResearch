<?php
/* @var $this TypegutionController */
/* @var $model Typegution */

$this->breadcrumbs=array(
	'Typegutions'=>array('index'),
	$model->name=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List Typegution', 'url'=>array('index')),
	array('label'=>'Create Typegution', 'url'=>array('create')),
	array('label'=>'View Typegution', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Manage Typegution', 'url'=>array('admin')),
);
?>

<h1>Update Typegution <?php echo $model->id; ?></h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>