<?php
/* @var $this InterviewController */
/* @var $data Interview */
?>

<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('id')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->id), array('view', 'id'=>$data->id)); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('peopleid')); ?>:</b>
	<?php echo CHtml::encode($data->peopleid); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('qid')); ?>:</b>
	<?php echo CHtml::encode($data->qid); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('value')); ?>:</b>
	<?php echo CHtml::encode($data->value); ?>
	<br />


</div>