<?php
/* @var $this InterpretController */
/* @var $model Interpret */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'interpret-form',
	// Please note: When you enable ajax validation, make sure the corresponding
	// controller action is handling ajax validation correctly.
	// There is a call to performAjaxValidation() commented in generated controller code.
	// See class documentation of CActiveForm for details on this.
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'name'); ?>
		<?php echo $form->textArea($model,'name',array('rows'=>6, 'cols'=>50)); ?>
		<?php echo $form->error($model,'name'); ?>
	</div>
		<?php echo $form->hiddenField($model,'channel',array('value'=>$_GET['channel'])); ?>
		<?php echo $form->hiddenField($model,'qid',array('value'=>$_GET['qid'])); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'types'); ?>
        <?php

		
$accountStatus = array('1'=>'input', '2'=>'process', '3'=>'output');
echo $form->radioButtonList($model,'types',$accountStatus,
array('separator'=>' ',
'labelOptions'=>array('style'=>'display:inline'), // add this code
));
        ?>

		<?php echo $form->error($model,'types'); ?>
	</div>
	<?php if($model->isNewRecord){?>
	<div class="row">
		<?php echo $form->hiddenField($model,'anwer_id',array('value'=>$_GET['Aid'])); ?>
		<?php echo $form->error($model,'anwer_id'); ?>
	</div>
	<?php } ?>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'เพิ่ม' : 'บันทึก',array('class'=>'btn btn-info')); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->