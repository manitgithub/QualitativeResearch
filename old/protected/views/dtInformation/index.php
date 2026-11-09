<?php
/* @var $this DtInformationController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Dt Informations',
);

$this->menu=array(
	array('label'=>'Create DtInformation', 'url'=>array('create')),
	array('label'=>'Manage DtInformation', 'url'=>array('admin')),
);
?>

<h1>Dt Informations</h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
