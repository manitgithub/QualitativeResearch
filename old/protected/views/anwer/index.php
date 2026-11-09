<?php
/* @var $this AnwerController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Anwers',
);

$this->menu=array(
	array('label'=>'Create Anwer', 'url'=>array('create')),
	array('label'=>'Manage Anwer', 'url'=>array('admin')),
);
?>

<h1>Anwers</h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
