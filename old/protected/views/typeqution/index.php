<?php
/* @var $this TypequtionController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Typequtions',
);

$this->menu=array(
	array('label'=>'Create Typeqution', 'url'=>array('create')),
	array('label'=>'Manage Typeqution', 'url'=>array('admin')),
);
?>

<h1>Typequtions</h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
