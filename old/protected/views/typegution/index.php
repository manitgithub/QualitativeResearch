<?php
/* @var $this TypegutionController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Typegutions',
);

$this->menu=array(
	array('label'=>'Create Typegution', 'url'=>array('create')),
	array('label'=>'Manage Typegution', 'url'=>array('admin')),
);
?>

<h1>Typegutions</h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
