<?php
/* @var $this QutionController */
/* @var $model Qution */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'qution-form',
	// Please note: When you enable ajax validation, make sure the corresponding
	// controller action is handling ajax validation correctly.
	// There is a call to performAjaxValidation() commented in generated controller code.
	// See class documentation of CActiveForm for details on this.
	'enableAjaxValidation'=>false,
)); ?>

<span class="label label-green">Fields with <span class="required">*</span> are required.</span>

	<?php echo $form->errorSummary($model); ?>

		<?php echo $form->hiddenField($model,'main_id',array('value'=>$_GET['idmain'])); ?>
		<?php echo $form->hiddenField($model,'datekey',array('size'=>30,'maxlength'=>30,'value'=>date('Y-m-d'))); ?>
		<?php echo $form->hiddenField($model,'userkey',array('size'=>30,'maxlength'=>30,'value'=>$_SESSION['UserID'])); ?>

	<div class="form-group form-group-label">
		<?php echo $form->labelEx($model,'name',array('class'=>'floating-label')); ?>
		<?php echo $form->textField($model,'name',array('size'=>60,'maxlength'=>200,'class'=>'form-control')); ?>
		<?php echo $form->error($model,'name'); ?>
	</div>

	<div class="form-group form-group-label">
		<?php echo $form->labelEx($model,'detail',array('class'=>'floating-label')); ?>
		<?php echo $form->textField($model,'detail',array('size'=>60,'maxlength'=>200,'class'=>'form-control')); ?>
		<?php echo $form->error($model,'detail'); ?>
	</div>


	<div class="form-group form-group-label">
	<b>ประเภทข้อมูล</b>	<?php echo $form->dropDownList($model,'type_id',array('1'=>'ข้อความ','2'=>'ตัวเลือก','3'=>'ใช่/ไม่ใช่'),array('class'=>'form-control')); ?>
        
		<?php echo $form->error($model,'type_id'); ?>
	</div>

	<div class="form-group form-group-label">
	<b>กลุ่ม</b><?php echo $form->dropDownList($model,'types',array('1'=>'คำถามวิจัย','2'=>'ข้อมูลพื้นฐาน'),array('class'=>'form-control')); ?>
        
		<?php echo $form->error($model,'types'); ?>
	</div>


	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'เพิ่ม' : 'บันทึก',array('class'=>'btn btn-brand waves-attach waves-light')); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->