<?php

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
class UserIdentity extends CUserIdentity
{
	/**
	 * Authenticates a user.
	 * The example implementation makes sure if the username and password
	 * are both 'demo'.
	 * In practical applications, this should be changed to authenticate
	 * against some persistent user identity storage (e.g. database).
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate()
	{
		$user = Userkey::model()->find('LoginName=?',array($this->username));
		if($user===null){
			$this->errorCode=self::ERROR_USERNAME_INVALID;
		} else if(!$user->validatePassword($this->password)){
			$this->errorCode=self::ERROR_USERNAME_INVALID;
		} else if($user->Stats == '0'){
			$this->errorCode=self::ERROR_USERNAME_INVALID;
		} else {
		//	$this->id_user=$user->user_id;
			$this->username=$user->LoginName;
			$this->errorCode=self::ERROR_NONE;
		//	include 'function.inc';
			
			
	Yii::app()-> session['UserID'] = $user->UserID; 	
	Yii::app()-> session['DivID'] = $user->DivID; 		
	Yii::app()-> session['fullname'] = $user->Name.' '.$user->Surname; 		
	Yii::app()-> session['PositionID'] = $user->PositionID; 		
	Yii::app()-> session['Group'] = $user->Group; 		
	$connection=Yii::app()->db;  			
	
	$a = date('Y-m-d H:i:s');
	$ip = $_SERVER['REMOTE_ADDR']; 
	$Reader=$connection->createCommand("INSERT INTO dt_log ( user, logtime, ip) VALUES ('$user->UserID', '$a', '$ip');")->query();

	
		}
		return $this->errorCode==self::ERROR_NONE;
	}
}
