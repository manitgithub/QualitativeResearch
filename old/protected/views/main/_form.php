<?php
/* @var $this MainController */
/* @var $model Main */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'main-form',
	// Please note: When you enable ajax validation, make sure the corresponding
	// controller action is handling ajax validation correctly.
	// There is a call to performAjaxValidation() commented in generated controller code.
	// See class documentation of CActiveForm for details on this.
	'enableAjaxValidation'=>false,
)); ?>


	<?php echo $form->errorSummary($model); ?>

	<div class="form-group form-group-label">
		<?php echo $form->labelEx($model,'name',array('class'=>'floating-label')); ?>
		<?php echo $form->textField($model,'name',array('class'=>'form-control','size'=>60,'maxlength'=>200)); ?>
		<?php echo $form->error($model,'name'); ?>
	</div>

		<?php echo $form->hiddenField($model,'date',array('size'=>30,'maxlength'=>30,'value'=>date('Y-m-d'))); ?>

		<?php echo $form->hiddenField($model,'userkey',array('value'=>$_SESSION['UserID'])); ?>

<div class="form-group form-group-label">
		<?php echo $form->labelEx($model,'detail',array('class'=>'floating-label')); ?>
		<?php echo $form->textArea($model,'detail',array('class'=>'form-control','rows'=>6, 'cols'=>50)); ?>
		<?php echo $form->error($model,'detail'); ?>
	</div>

		<?php echo CHtml::submitButton($model->isNewRecord ? 'เพิ่ม' : 'บันทึก',array('class'=>'btn btn-info')); ?>

<?php $this->endWidget(); ?>

</div><!-- form -->