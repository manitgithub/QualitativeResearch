<?php
/* @var $this UserkeyController */
/* @var $data Userkey */
?>

<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('UserID')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->UserID), array('view', 'id'=>$data->UserID)); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('PrefixID')); ?>:</b>
	<?php echo CHtml::encode($data->PrefixID); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('Name')); ?>:</b>
	<?php echo CHtml::encode($data->Name); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('Surname')); ?>:</b>
	<?php echo CHtml::encode($data->Surname); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('LoginName')); ?>:</b>
	<?php echo CHtml::encode($data->LoginName); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('PWD')); ?>:</b>
	<?php echo CHtml::encode($data->PWD); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('DivID')); ?>:</b>
	<?php echo CHtml::encode($data->DivID); ?>
	<br />

	<?php /*
	<b><?php echo CHtml::encode($data->getAttributeLabel('PositionID')); ?>:</b>
	<?php echo CHtml::encode($data->PositionID); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('Group')); ?>:</b>
	<?php echo CHtml::encode($data->Group); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('LastUpdate')); ?>:</b>
	<?php echo CHtml::encode($data->LastUpdate); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('LastUser')); ?>:</b>
	<?php echo CHtml::encode($data->LastUser); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('pic')); ?>:</b>
	<?php echo CHtml::encode($data->pic); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('Stats')); ?>:</b>
	<?php echo CHtml::encode($data->Stats); ?>
	<br />

	*/ ?>

</div>