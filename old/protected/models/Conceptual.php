<?php

/**
 * This is the model class for table "dt_conceptual".
 *
 * The followings are the available columns in table 'dt_conceptual':
 * @property integer $id
 * @property string $name
 * @property integer $main_id
 * @property integer $input
 * @property integer $process
 * @property integer $output
 * @property integer $problem
 * @property integer $success
 * @property string $datekey
 */
class Conceptual extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'dt_conceptual';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('id', 'required'),
			array('id, main_id, input, process, output, problem, success', 'numerical', 'integerOnly'=>true),
			array('name, datekey', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, name, main_id, input, process, output, problem, success, datekey', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'name' => 'Name',
			'main_id' => 'Main',
			'input' => 'Input',
			'process' => 'Process',
			'output' => 'Output',
			'problem' => 'Problem',
			'success' => 'Success',
			'datekey' => 'Datekey',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('main_id',$this->main_id);
		$criteria->compare('input',$this->input);
		$criteria->compare('process',$this->process);
		$criteria->compare('output',$this->output);
		$criteria->compare('problem',$this->problem);
		$criteria->compare('success',$this->success);
		$criteria->compare('datekey',$this->datekey,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Conceptual the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
