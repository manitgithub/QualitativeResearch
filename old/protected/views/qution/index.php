<?php
/* @var $this QutionController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Qutions',
);

$this->menu=array(
	array('label'=>'Create Qution', 'url'=>array('create')),
	array('label'=>'Manage Qution', 'url'=>array('admin')),
);
?>

<h1>Qutions</h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
