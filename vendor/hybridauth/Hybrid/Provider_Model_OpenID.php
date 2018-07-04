<?php
class Hybrid_Provider_Model_OpenID extends Hybrid_Provider_Model
{
	public $openidIdentifier = ""; 

	function initialize()
	{
		if( isset( $this->params["openid_identifier"] ) ){
			$this->openidIdentifier = $this->params["openid_identifier"];
		}

		require_once Hybrid_Auth::$config["path_libraries"] . "OpenID/LightOpenID.php"; 
		
		Hybrid_Auth::$config['proxy'] = isset(Hybrid_Auth::$config['proxy'])?Hybrid_Auth::$config['proxy']:'';
		
		$this->api = new LightOpenID( parse_url( Hybrid_Auth::$config["base_url"], PHP_URL_HOST), Hybrid_Auth::$config["proxy"] ); 
	}

	function loginBegin()
	{
		if( empty( $this->openidIdentifier ) ){
			throw new Exception( "OpenID adapter require the identity provider identifier 'openid_identifier' as an extra parameter.", 4 );
		}

		$this->api->identity  = $this->openidIdentifier;
		$this->api->returnUrl = $this->endpoint;
		$this->api->required  = ARRAY( 
			'namePerson/first'       ,
			'namePerson/last'        ,
			'namePerson/friendly'    ,
			'namePerson'             ,

			'contact/email'          ,

			'birthDate'              ,
			'birthDate/birthDay'     ,
			'birthDate/birthMonth'   ,
			'birthDate/birthYear'    ,

			'person/gender'          ,
			'pref/language'          , 

			'contact/postalCode/home',
			'contact/city/home'      ,
			'contact/country/home'   , 

			'media/image/default'    ,
		);

		Hybrid_Auth::redirect( $this->api->authUrl() );
	}

	function loginFinish()
	{
		if( $this->api->mode == 'cancel'){
			throw new Exception( "Authentication failed! User has canceled authentication!", 5 );
		}

		if( ! $this->api->validate() ){
			throw new Exception( "Authentication failed. Invalid request recived!", 5 );
		}

		$response = $this->api->getAttributes();

		$this->user->profile->identifier  = $this->api->identity;

		$this->user->profile->firstName   = (array_key_exists("namePerson/first",$response))?$response["namePerson/first"]:"";
		$this->user->profile->lastName    = (array_key_exists("namePerson/last",$response))?$response["namePerson/last"]:"";
		$this->user->profile->displayName = (array_key_exists("namePerson",$response))?$response["namePerson"]:"";
		$this->user->profile->email       = (array_key_exists("contact/email",$response))?$response["contact/email"]:"";
		$this->user->profile->language    = (array_key_exists("pref/language",$response))?$response["pref/language"]:"";
		$this->user->profile->country     = (array_key_exists("contact/country/home",$response))?$response["contact/country/home"]:""; 
		$this->user->profile->zip         = (array_key_exists("contact/postalCode/home",$response))?$response["contact/postalCode/home"]:""; 
		$this->user->profile->gender      = (array_key_exists("person/gender",$response))?$response["person/gender"]:""; 
		$this->user->profile->photoURL    = (array_key_exists("media/image/default",$response))?$response["media/image/default"]:""; 

		$this->user->profile->birthDay    = (array_key_exists("birthDate/birthDay",$response))?$response["birthDate/birthDay"]:""; 
		$this->user->profile->birthMonth  = (array_key_exists("birthDate/birthMonth",$response))?$response["birthDate/birthMonth"]:""; 
		$this->user->profile->birthYear   = (array_key_exists("birthDate/birthDate",$response))?$response["birthDate/birthDate"]:"";  

		if( ! $this->user->profile->displayName ) {
			$this->user->profile->displayName = trim( $this->user->profile->lastName . " " . $this->user->profile->firstName ); 
		}

		if( isset( $response['namePerson/friendly'] ) && ! empty( $response['namePerson/friendly'] ) && ! $this->user->profile->displayName ) { 
			$this->user->profile->displayName = (array_key_exists("namePerson/friendly",$response))?$response["namePerson/friendly"]:"" ; 
		}

		if( isset( $response['birthDate'] ) && ! empty( $response['birthDate'] ) && ! $this->user->profile->birthDay ) {
			list( $birthday_year, $birthday_month, $birthday_day ) = (array_key_exists('birthDate',$response))?$response['birthDate']:"";

			$this->user->profile->birthDay      = (int) $birthday_day;
			$this->user->profile->birthMonth    = (int) $birthday_month;
			$this->user->profile->birthYear     = (int) $birthday_year;
		}

		if( ! $this->user->profile->displayName ){
			$this->user->profile->displayName = trim( $this->user->profile->firstName . " " . $this->user->profile->lastName );
		}

		if( $this->user->profile->gender == "f" ){
			$this->user->profile->gender = "female";
		}

		if( $this->user->profile->gender == "m" ){
			$this->user->profile->gender = "male";
		} 

		$this->setUserConnected();

		Hybrid_Auth::storage()->set( "hauth_session.{$this->providerId}.user", $this->user );
	}

	function getUserProfile()
	{
		$this->user = Hybrid_Auth::storage()->get( "hauth_session.{$this->providerId}.user" ) ;

		if ( ! is_object( $this->user ) ){
			throw new Exception( "User profile request failed! User is not connected to {$this->providerId} or his session has expired.", 6 );
		} 

		return $this->user->profile;
	}
}
