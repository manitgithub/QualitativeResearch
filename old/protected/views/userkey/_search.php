<?php
/* @var $this UserkeyController */
/* @var $model Userkey */
/* @var $form CActiveForm */
?>

<div class="wide form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'action'=>Yii::app()->createUrl($this->route),
	'method'=>'get',
)); ?>

	<div class="row">
		<?php echo $form->label($model,'UserID'); ?>
		<?php echo $form->textField($model,'UserID'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'PrefixID'); ?>
		<?php echo $form->textField($model,'PrefixID',array('size'=>30,'maxlength'=>30)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'Name'); ?>
		<?php echo $form->textField($model,'Name',array('size'=>60,'maxlength'=>100)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'Surname'); ?>
		<?php echo $form->textField($model,'Surname',array('size'=>60,'maxlength'=>100)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'LoginName'); ?>
		<?php echo $form->textField($model,'LoginName',array('size'=>30,'maxlength'=>30)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'PWD'); ?>
		<?php echo $form->textField($model,'PWD',array('size'=>30,'maxlength'=>30)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'DivID'); ?>
		<?php echo $form->textField($model,'DivID'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'PositionID'); ?>
		<?php echo $form->textField($model,'PositionID'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'Group'); ?>
		<?php echo $form->textField($model,'Group',array('size'=>1,'maxlength'=>1)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'LastUpdate'); ?>
		<?php echo $form->textField($model,'LastUpdate',array('size'=>50,'maxlength'=>50)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'LastUser'); ?>
		<?php echo $form->textField($model,'LastUser'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'pic'); ?>
		<?php echo $form->textField($model,'pic',array('size'=>60,'maxlength'=>100)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'Stats'); ?>
		<?php echo $form->textField($model,'Stats'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton('Search'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- search-form -->