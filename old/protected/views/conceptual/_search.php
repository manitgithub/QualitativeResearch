<?php
/* @var $this ConceptualController */
/* @var $model Conceptual */
/* @var $form CActiveForm */
?>

<div class="wide form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'action'=>Yii::app()->createUrl($this->route),
	'method'=>'get',
)); ?>

	<div class="row">
		<?php echo $form->label($model,'id'); ?>
		<?php echo $form->textField($model,'id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'name'); ?>
		<?php echo $form->textArea($model,'name',array('rows'=>6, 'cols'=>50)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'main_id'); ?>
		<?php echo $form->textField($model,'main_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'input'); ?>
		<?php echo $form->textField($model,'input'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'process'); ?>
		<?php echo $form->textField($model,'process'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'output'); ?>
		<?php echo $form->textField($model,'output'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'problem'); ?>
		<?php echo $form->textField($model,'problem'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'success'); ?>
		<?php echo $form->textField($model,'success'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'datekey'); ?>
		<?php echo $form->textField($model,'datekey'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton('Search'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- search-form -->