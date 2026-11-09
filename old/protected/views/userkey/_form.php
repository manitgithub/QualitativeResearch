<?php
/* @var $this UserkeyController */
/* @var $model Userkey */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'userkey-form',
	// Please note: When you enable ajax validation, make sure the corresponding
	// controller action is handling ajax validation correctly.
	// There is a call to performAjaxValidation() commented in generated controller code.
	// See class documentation of CActiveForm for details on this.
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>



	<div class="form-group form-group-label">
		<?php echo $form->labelEx($model,'Name',array('class'=>'floating-label')); ?>
		<?php echo $form->textField($model,'Name',array('size'=>60,'maxlength'=>100,'class'=>'form-control')); ?>
		<?php echo $form->error($model,'Name'); ?>
	</div>



	<div class="form-group form-group-label">
		<?php echo $form->labelEx($model,'LoginName',array('class'=>'floating-label')); ?>
		<?php echo $form->textField($model,'LoginName',array('size'=>30,'maxlength'=>30,'class'=>'form-control')); ?>
		<?php echo $form->error($model,'LoginName'); ?>
	</div>

	<div class="form-group form-group-label">
		<?php echo $form->labelEx($model,'PWD',array('class'=>'floating-label')); ?>
		<?php echo $form->PasswordField($model,'PWD',array('size'=>30,'maxlength'=>30,'class'=>'form-control')); ?>
		<?php echo $form->error($model,'PWD'); ?>
	</div>




<!--
	<div class="row">
		<?php echo $form->labelEx($model,'Group'); ?>
		<?php echo $form->textField($model,'Group',array('size'=>1,'maxlength'=>1)); ?>
		<?php echo $form->error($model,'Group'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'LastUpdate'); ?>
		<?php echo $form->textField($model,'LastUpdate',array('size'=>50,'maxlength'=>50)); ?>
		<?php echo $form->error($model,'LastUpdate'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'LastUser'); ?>
		<?php echo $form->textField($model,'LastUser'); ?>
		<?php echo $form->error($model,'LastUser'); ?>
	</div>
-->

	<div class="row">
		<?php echo $form->hiddenField($model,'Stats',array('value'=>'1')); ?>
		<?php echo $form->error($model,'Stats'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save',array('class'=>'btn btn-brand waves-attach waves-light')); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->