<?php
/* @var $this InterviewController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Interviews',
);

$this->menu=array(
	array('label'=>'Create Interview', 'url'=>array('create')),
	array('label'=>'Manage Interview', 'url'=>array('admin')),
);
?>

<h1>Interviews</h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
