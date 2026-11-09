<?php
/* @var $this InterpretController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Interprets',
);

$this->menu=array(
	array('label'=>'Create Interpret', 'url'=>array('create')),
	array('label'=>'Manage Interpret', 'url'=>array('admin')),
);
?>

<h1>Interprets</h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
