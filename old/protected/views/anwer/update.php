<?php
/* @var $this AnwerController */
/* @var $model Anwer */

$this->breadcrumbs=array(
	'Anwers'=>array('index'),
	$model->name=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List Anwer', 'url'=>array('index')),
	array('label'=>'Create Anwer', 'url'=>array('create')),
	array('label'=>'View Anwer', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Manage Anwer', 'url'=>array('admin')),
);
?>

<h1>Update Anwer <?php echo $model->id; ?></h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>