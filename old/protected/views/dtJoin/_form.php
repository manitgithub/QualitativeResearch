<?php
/* @var $this DtJoinController */
/* @var $model DtJoin */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'dt-join-form',
	// Please note: When you enable ajax validation, make sure the corresponding
	// controller action is handling ajax validation correctly.
	// There is a call to performAjaxValidation() commented in generated controller code.
	// See class documentation of CActiveForm for details on this.
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->hiddenField($model,'mainid',array('value'=>$_GET['id'])); ?>
		<?php echo $form->error($model,'mainid'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'userid'); ?>
        <?php echo $form->dropDownList($model,'userid', CHtml::listData(Userkey::model()->findAll(),'UserID', 'Name'));?>
		<?php echo $form->error($model,'userid'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'เพิ่ม' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->