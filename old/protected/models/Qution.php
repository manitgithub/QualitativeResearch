<?php

/**
 * This is the model class for table "qution".
 *
 * The followings are the available columns in table 'qution':
 * @property integer $id
 * @property integer $main_id
 * @property string $name
 * @property string $detail
 * @property string $datekey
 * @property string $userkey
 * @property integer $type_id
 */
class Qution extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'dt_qution';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('main_id, type_id, types', 'numerical', 'integerOnly'=>true),
			array('name, detail', 'length', 'max'=>200),
			array('datekey, userkey', 'length', 'max'=>30),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, main_id, name, detail, datekey, userkey, type_id, types', 'safe', 'on'=>'search'),
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
			'main_id' => 'Main',
			'name' => 'คำถาม',
			'detail' => 'อธิบาย',
			'datekey' => 'Datekey',
			'userkey' => 'Userkey',
			'type_id' => 'Type',
			'types' => 'ข้อมูลพื้นฐาน/คำถามวิจัย',

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
		$criteria->compare('main_id',$this->main_id);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('detail',$this->detail,true);
		$criteria->compare('datekey',$this->datekey,true);
		$criteria->compare('userkey',$this->userkey,true);
		$criteria->compare('type_id',$this->type_id);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Qution the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
