<?php
/* @var $this MainController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Mains',
);

$this->menu=array(
	array('label'=>'Create Main', 'url'=>array('create')),
	array('label'=>'Manage Main', 'url'=>array('admin')),
);
?>

<h1>Mains</h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
