<?php
/* @var $this ConceptualController */
/* @var $model Conceptual */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'conceptual-form',
	// Please note: When you enable ajax validation, make sure the corresponding
	// controller action is handling ajax validation correctly.
	// There is a call to performAjaxValidation() commented in generated controller code.
	// See class documentation of CActiveForm for details on this.
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'id'); ?>
		<?php echo $form->textField($model,'id'); ?>
		<?php echo $form->error($model,'id'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'name'); ?>
		<?php echo $form->textArea($model,'name',array('rows'=>6, 'cols'=>50)); ?>
		<?php echo $form->error($model,'name'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'main_id'); ?>
		<?php echo $form->textField($model,'main_id'); ?>
		<?php echo $form->error($model,'main_id'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'input'); ?>
		<?php echo $form->textField($model,'input'); ?>
		<?php echo $form->error($model,'input'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'process'); ?>
		<?php echo $form->textField($model,'process'); ?>
		<?php echo $form->error($model,'process'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'output'); ?>
		<?php echo $form->textField($model,'output'); ?>
		<?php echo $form->error($model,'output'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'problem'); ?>
		<?php echo $form->textField($model,'problem'); ?>
		<?php echo $form->error($model,'problem'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'success'); ?>
		<?php echo $form->textField($model,'success'); ?>
		<?php echo $form->error($model,'success'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'datekey'); ?>
		<?php echo $form->textField($model,'datekey'); ?>
		<?php echo $form->error($model,'datekey'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->