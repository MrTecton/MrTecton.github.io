<?php
class Hybrid_Providers_Facebook extends Hybrid_Provider_Model
{
	public $scope = "email, user_about_me, user_birthday, user_hometown, user_website, read_stream, offline_access, publish_stream, read_friendlists";

	function initialize() 
	{
		if ( ! $this->config["keys"]["id"] || ! $this->config["keys"]["secret"] ){
			throw new Exception( "Your application id and secret are required in order to connect to {$this->providerId}.", 4 );
		}

		if ( ! class_exists('FacebookApiException', false) ) {
			require_once Hybrid_Auth::$config["path_libraries"] . "Facebook/base_facebook.php";
			require_once Hybrid_Auth::$config["path_libraries"] . "Facebook/facebook.php";
		}
		
		if ( isset ( Hybrid_Auth::$config["proxy"] ) ) {
			BaseFacebook::$CURL_OPTS[CURLOPT_PROXY] = Hybrid_Auth::$config["proxy"];
		}

		$this->api = new Facebook( ARRAY( 'appId' => $this->config["keys"]["id"], 'secret' => $this->config["keys"]["secret"] ) ); 

		if ( $this->token("access_token") ) {
			$this->api->setAccessToken( $this->token("access_token") );
			$this->api->setExtendedAccessToken();
			$access_token = $this->api->getAccessToken();

			if( $access_token ){
				$this->token("access_token", $access_token );
				$this->api->setAccessToken( $access_token );
			}

			$this->api->setAccessToken( $this->token("access_token") );
		}

		$this->api->getUser();
	}

	function loginBegin()
	{
		$parameters = array("scope" => $this->scope, "redirect_uri" => $this->endpoint, "display" => "page");
		$optionals  = array("scope", "redirect_uri", "display");

		foreach ($optionals as $parameter){
			if( isset( $this->config[$parameter] ) && ! empty( $this->config[$parameter] ) ){
				$parameters[$parameter] = $this->config[$parameter];
			}
		}

		$url = $this->api->getLoginUrl( $parameters );

		Hybrid_Auth::redirect( $url );
	}

	function loginFinish()
	{ 
		if ( isset( $_REQUEST['error'] ) && $_REQUEST['error'] == "access_denied" ){ 
			throw new Exception( "Authentication failed! The user denied your request.", 5 );
		}

		if ( ! $this->api->getUser() ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid user id.", 5 );
		}

		$this->setUserConnected();

		$this->token( "access_token", $this->api->getAccessToken() );
	}

	function logout()
	{ 
		$this->api->destroySession();

		parent::logout();
	}

	function getUserProfile()
	{
		try{ 
			$data = $this->api->api('/me'); 
		}
		catch( FacebookApiException $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error: $e", 6 );
		} 

		if ( ! isset( $data["id"] ) ){ 
			throw new Exception( "User profile request failed! {$this->providerId} api returned an invalid response.", 6 );
		}

		$this->user->profile->identifier    = (array_key_exists('id',$data))?$data['id']:"";
		$this->user->profile->displayName   = (array_key_exists('name',$data))?$data['name']:"";
		$this->user->profile->firstName     = (array_key_exists('first_name',$data))?$data['first_name']:"";
		$this->user->profile->lastName      = (array_key_exists('last_name',$data))?$data['last_name']:"";
		$this->user->profile->photoURL      = "https://graph.facebook.com/" . $this->user->profile->identifier . "/picture?width=150&height=150";
		$this->user->profile->profileURL    = (array_key_exists('link',$data))?$data['link']:""; 
		$this->user->profile->webSiteURL    = (array_key_exists('website',$data))?$data['website']:""; 
		$this->user->profile->gender        = (array_key_exists('gender',$data))?$data['gender']:"";
		$this->user->profile->description   = (array_key_exists('bio',$data))?$data['bio']:"";
		$this->user->profile->email         = (array_key_exists('email',$data))?$data['email']:"";
		$this->user->profile->emailVerified = (array_key_exists('email',$data))?$data['email']:"";
		$this->user->profile->region        = (array_key_exists("hometown",$data)&&array_key_exists("name",$data['hometown']))?$data['hometown']["name"]:"";

		if( array_key_exists('birthday',$data) ) {
			list($birthday_month, $birthday_day, $birthday_year) = explode( "/", $data['birthday'] );

			$this->user->profile->birthDay   = (int) $birthday_day;
			$this->user->profile->birthMonth = (int) $birthday_month;
			$this->user->profile->birthYear  = (int) $birthday_year;
		}

		return $this->user->profile;
 	}

	function getUserContacts()
	{
		try{ 
			$response = $this->api->api('/me/friends'); 
		}
		catch( FacebookApiException $e ){
			throw new Exception( "User contacts request failed! {$this->providerId} returned an error: $e" );
		} 
 
		if( ! $response || ! count( $response["data"] ) ){
			return ARRAY();
		}

		$contacts = ARRAY();
 
		foreach( $response["data"] as $item ){
			$uc = new Hybrid_User_Contact();

			$uc->identifier  = (array_key_exists("id",$item))?$item["id"]:"";
			$uc->displayName = (array_key_exists("name",$item))?$item["name"]:"";
			$uc->profileURL  = "https://www.facebook.com/profile.php?id=" . $uc->identifier;
			$uc->photoURL    = "https://graph.facebook.com/" . $uc->identifier . "/picture?width=150&height=150";

			$contacts[] = $uc;
		}

		return $contacts;
 	}

	function setUserStatus( $status )
	{
		$parameters = array();

		if( is_array( $status ) ){
			$parameters = $status;
		}
		else{
			$parameters["message"] = $status; 
		}

		try{ 
			$response = $this->api->api( "/me/feed", "post", $parameters );
		}
		catch( FacebookApiException $e ){
			throw new Exception( "Update user status failed! {$this->providerId} returned an error: $e" );
		}
 	}

	function getUserActivity( $stream )
	{
		try{
			if( $stream == "me" ){
				$response = $this->api->api( '/me/feed' ); 
			}
			else{
				$response = $this->api->api('/me/home'); 
			}
		}
		catch( FacebookApiException $e ){
			throw new Exception( "User activity stream request failed! {$this->providerId} returned an error: $e" );
		} 

		if( ! $response || ! count(  $response['data'] ) ){
			return ARRAY();
		}

		$activities = ARRAY();

		foreach( $response['data'] as $item ){
			if( $stream == "me" && $item["from"]["id"] != $this->api->getUser() ){
				continue;
			}

			$ua = new Hybrid_User_Activity();

			$ua->id                 = (array_key_exists("id",$item))?$item["id"]:"";
			$ua->date               = (array_key_exists("created_time",$item))?strtotime($item["created_time"]):"";

			if( $item["type"] == "video" ){
				$ua->text           = (array_key_exists("link",$item))?$item["link"]:"";
			}

			if( $item["type"] == "link" ){
				$ua->text           = (array_key_exists("link",$item))?$item["link"]:"";
			}

			if( empty( $ua->text ) && isset( $item["story"] ) ){
				$ua->text           = (array_key_exists("link",$item))?$item["link"]:"";
			}

			if( empty( $ua->text ) && isset( $item["message"] ) ){
				$ua->text           = (array_key_exists("message",$item))?$item["message"]:"";
			}

			if( ! empty( $ua->text ) ){
				$ua->user->identifier   = (array_key_exists("id",$item["from"]))?$item["from"]["id"]:"";
				$ua->user->displayName  = (array_key_exists("name",$item["from"]))?$item["from"]["name"]:"";
				$ua->user->profileURL   = "https://www.facebook.com/profile.php?id=" . $ua->user->identifier;
				$ua->user->photoURL     = "https://graph.facebook.com/" . $ua->user->identifier . "/picture?type=square";

				$activities[] = $ua;
			}
		}

		return $activities;
 	}
}
