<?php
/* @var $this DtJoinController */
/* @var $model DtJoin */

$this->breadcrumbs=array(
	'Dt Joins'=>array('index'),
	$model->id=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List DtJoin', 'url'=>array('index')),
	array('label'=>'Create DtJoin', 'url'=>array('create')),
	array('label'=>'View DtJoin', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Manage DtJoin', 'url'=>array('admin')),
);
?>

<h1>Update DtJoin <?php echo $model->id; ?></h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>