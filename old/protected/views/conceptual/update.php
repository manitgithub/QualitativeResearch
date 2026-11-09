<?php
/* @var $this ConceptualController */
/* @var $model Conceptual */

$this->breadcrumbs=array(
	'Conceptuals'=>array('index'),
	$model->name=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List Conceptual', 'url'=>array('index')),
	array('label'=>'Create Conceptual', 'url'=>array('create')),
	array('label'=>'View Conceptual', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Manage Conceptual', 'url'=>array('admin')),
);
?>

<h1>Update Conceptual <?php echo $model->id; ?></h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>