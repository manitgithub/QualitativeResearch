<?php
/* @var $this InterpretController */
/* @var $data Interpret */
?>

<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('id')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->id), array('view', 'id'=>$data->id)); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('name')); ?>:</b>
	<?php echo CHtml::encode($data->name); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('types')); ?>:</b>
	<?php echo CHtml::encode($data->types); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('anwer_id')); ?>:</b>
	<?php echo CHtml::encode($data->anwer_id); ?>
	<br />


</div>