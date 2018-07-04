<?php
class Hybrid_Provider_Model_OAuth1 extends Hybrid_Provider_Model
{
	public $request_tokens_raw = null;
	public $access_tokens_raw  = null;
	
	function errorMessageByStatus( $code = null ) { 
		$http_status_codes = ARRAY(
			200 => "OK: Success!",
			304 => "Not Modified: There was no new data to return.",
			400 => "Bad Request: The request was invalid.",
			401 => "Unauthorized.",
			403 => "Forbidden: The request is understood, but it has been refused.",
			404 => "Not Found: The URI requested is invalid or the resource requested does not exists.",
			406 => "Not Acceptable.", 
			500 => "Internal Server Error: Something is broken.",
			502 => "Bad Gateway.",
			503 => "Service Unavailable."
		);

		if( ! $code && $this->api ) 
			$code = $this->api->http_code;

		if( isset( $http_status_codes[ $code ] ) )
			return $code . " " . $http_status_codes[ $code ];
	}

	function initialize()
	{
		if ( ! $this->config["keys"]["key"] || ! $this->config["keys"]["secret"] ){
			throw new Exception( "Your application key and secret are required in order to connect to {$this->providerId}.", 4 );
		}

		require_once Hybrid_Auth::$config["path_libraries"] . "OAuth/OAuth.php";
		require_once Hybrid_Auth::$config["path_libraries"] . "OAuth/OAuth1Client.php";

		if( $this->token( "access_token" ) ){
			$this->api = new OAuth1Client( 
				$this->config["keys"]["key"], $this->config["keys"]["secret"],
				$this->token( "access_token" ), $this->token( "access_token_secret" ) 
			);
		}

		elseif( $this->token( "request_token" ) ){
			$this->api = new OAuth1Client( 
				$this->config["keys"]["key"], $this->config["keys"]["secret"], 
				$this->token( "request_token" ), $this->token( "request_token_secret" ) 
			);
		}

		else{
			$this->api = new OAuth1Client( $this->config["keys"]["key"], $this->config["keys"]["secret"] );
		}

		if( isset( Hybrid_Auth::$config["proxy"] ) ){
			$this->api->curl_proxy = Hybrid_Auth::$config["proxy"];
		}
	}

	function loginBegin()
	{
		$tokens = $this->api->requestToken( $this->endpoint ); 

		$this->request_tokens_raw = $tokens;
		
		if ( $this->api->http_code != 200 ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ), 5 );
		}

		if ( ! isset( $tokens["oauth_token"] ) ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid oauth token.", 5 );
		}

		$this->token( "request_token"       , $tokens["oauth_token"] ); 
		$this->token( "request_token_secret", $tokens["oauth_token_secret"] ); 

		Hybrid_Auth::redirect( $this->api->authorizeUrl( $tokens ) );
	}

	function loginFinish()
	{
		$oauth_token    = (array_key_exists('oauth_token',$_REQUEST))?$_REQUEST['oauth_token']:"";
		$oauth_verifier = (array_key_exists('oauth_verifier',$_REQUEST))?$_REQUEST['oauth_verifier']:"";

		if ( ! $oauth_token || ! $oauth_verifier ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid oauth verifier.", 5 );
		}

		$tokens = $this->api->accessToken( $oauth_verifier );

		$this->access_tokens_raw = $tokens;

		if ( $this->api->http_code != 200 ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ), 5 );
		}

		if ( ! isset( $tokens["oauth_token"] ) ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid access token.", 5 );
		}

		$this->deleteToken( "request_token"        );
		$this->deleteToken( "request_token_secret" );

		$this->token( "access_token"        , $tokens['oauth_token'] );
		$this->token( "access_token_secret" , $tokens['oauth_token_secret'] ); 

		$this->setUserConnected(); 
	}
}
