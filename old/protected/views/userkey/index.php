<?php
/* @var $this UserkeyController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Userkeys',
);

$this->menu=array(
	array('label'=>'Create Userkey', 'url'=>array('create')),
	array('label'=>'Manage Userkey', 'url'=>array('admin')),
);
?>

<h1>Userkeys</h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
