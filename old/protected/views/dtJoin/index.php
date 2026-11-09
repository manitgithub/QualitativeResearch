<?php
/* @var $this DtJoinController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Dt Joins',
);

$this->menu=array(
	array('label'=>'Create DtJoin', 'url'=>array('create')),
	array('label'=>'Manage DtJoin', 'url'=>array('admin')),
);
?>

<h1>Dt Joins</h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
