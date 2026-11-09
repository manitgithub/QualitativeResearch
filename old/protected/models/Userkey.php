<?php

/**
 * This is the model class for table "userkey".
 *
 * The followings are the available columns in table 'userkey':
 * @property integer $UserID
 * @property string $PrefixID
 * @property string $Name
 * @property string $Surname
 * @property string $LoginName
 * @property string $PWD
 * @property integer $DivID
 * @property integer $PositionID
 * @property string $Group
 * @property string $LastUpdate
 * @property integer $LastUser
 * @property string $pic
 * @property integer $Stats
 */
class Userkey extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'dt_userkey';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('Name, LoginName, PWD', 'required'),
			array('DivID, PositionID, LastUser, Stats', 'numerical', 'integerOnly'=>true),
			array('PrefixID, LoginName, PWD', 'length', 'max'=>30),
			array('Name, Surname, pic', 'length', 'max'=>100),
			array('Group', 'length', 'max'=>1),
			array('LastUpdate', 'length', 'max'=>50),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('UserID, PrefixID, Name, Surname, LoginName, PWD, DivID, PositionID, Group, LastUpdate, LastUser, pic, Stats', 'safe', 'on'=>'search'),
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
			'UserID' => 'User',
			'PrefixID' => 'Prefix',
			'Name' => 'ชื่อ-สกุล',
			'Surname' => 'Surname',
			'LoginName' => 'Username',
			'PWD' => 'Password',
			'DivID' => 'Div',
			'PositionID' => 'Position',
			'Group' => 'Group',
			'LastUpdate' => 'Last Update',
			'LastUser' => 'Last User',
			'pic' => 'Pic',
			'Stats' => 'Stats',
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

		$criteria->compare('UserID',$this->UserID);
		$criteria->compare('PrefixID',$this->PrefixID,true);
		$criteria->compare('Name',$this->Name,true);
		$criteria->compare('Surname',$this->Surname,true);
		$criteria->compare('LoginName',$this->LoginName,true);
		$criteria->compare('PWD',$this->PWD,true);
		$criteria->compare('DivID',$this->DivID);
		$criteria->compare('PositionID',$this->PositionID);
		$criteria->compare('Group',$this->Group,true);
		$criteria->compare('LastUpdate',$this->LastUpdate,true);
		$criteria->compare('LastUser',$this->LastUser);
		$criteria->compare('pic',$this->pic,true);
		$criteria->compare('Stats',$this->Stats);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Userkey the static model class
	 */


public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
		public function validatePassword($password)
	{
		return $this->PWD === $password;
	}
}
