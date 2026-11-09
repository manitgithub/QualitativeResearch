<?php
/* @var $this MainController */
/* @var $model Main */

$this->breadcrumbs=array(
	'Mains'=>array('index'),
	$model->name=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List Main', 'url'=>array('index')),
	array('label'=>'Create Main', 'url'=>array('create')),
	array('label'=>'View Main', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Manage Main', 'url'=>array('admin')),
);
?>

<h1>Update Main <?php echo $model->id; ?></h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>