<?php
/* @var $this UserkeyController */
/* @var $model Userkey */

$this->breadcrumbs=array(
	'Userkeys'=>array('index'),
	'Manage',
);

$this->menu=array(
	array('label'=>'List Userkey', 'url'=>array('index')),
	array('label'=>'Create Userkey', 'url'=>array('create')),
);

Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$('#userkey-grid').yiiGridView('update', {
		data: $(this).serialize()
	});
	return false;
});
");
?>


<?php $this->widget('zii.widgets.grid.CGridView', array(
	'id'=>'userkey-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'columns'=>array(
		'UserID',
		//'PrefixID',
		'Name',
		//'Surname',
		'LoginName',
		        array(            // display 'author.username' using an expression
            'name'=>'Stats',
            'value'=>$data->Stats!='1'?'ใช้งาน':'ปิดการใช้งาน',
        ),

		//'PWD',
		/*
		'DivID',
		'PositionID',
		'Group',
		'LastUpdate',
		'LastUser',
		'pic',
		'Stats',
		*/
		//array('class'=>'CButtonColumn',),
	),
)); ?>
