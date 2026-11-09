<?php
/* @var $this ConceptualController */
/* @var $data Conceptual */
?>

<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('id')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->id), array('view', 'id'=>$data->id)); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('name')); ?>:</b>
	<?php echo CHtml::encode($data->name); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('main_id')); ?>:</b>
	<?php echo CHtml::encode($data->main_id); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('input')); ?>:</b>
	<?php echo CHtml::encode($data->input); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('process')); ?>:</b>
	<?php echo CHtml::encode($data->process); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('output')); ?>:</b>
	<?php echo CHtml::encode($data->output); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('problem')); ?>:</b>
	<?php echo CHtml::encode($data->problem); ?>
	<br />

	<?php /*
	<b><?php echo CHtml::encode($data->getAttributeLabel('success')); ?>:</b>
	<?php echo CHtml::encode($data->success); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('datekey')); ?>:</b>
	<?php echo CHtml::encode($data->datekey); ?>
	<br />

	*/ ?>

</div>