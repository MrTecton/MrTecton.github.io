<?php
class Hybrid_Provider_Adapter
{
	public $id       = NULL ;

	public $config   = NULL ;

	public $params   = NULL ; 

	public $wrapper  = NULL ;

	public $adapter  = NULL ;

	function factory( $id, $params = NULL )
	{
		Hybrid_Logger::info( "Enter Hybrid_Provider_Adapter::factory( $id )" );

		$this->id     = $id;
		$this->params = $params;
		$this->id     = $this->getProviderCiId( $this->id );
		$this->config = $this->getConfigById( $this->id );

		if( ! $this->id ){
			throw new Exception( "No provider ID specified.", 2 ); 
		}

		if( ! $this->config ){
			throw new Exception( "Unknown Provider ID, check your configuration file.", 3 ); 
		}

		if( ! $this->config["enabled"] ){
			throw new Exception( "The provider '{$this->id}' is not enabled.", 3 );
		}

		if( isset( $this->config["wrapper"] ) && is_array( $this->config["wrapper"] ) ){
			require_once $this->config["wrapper"]["path"];

			if( ! class_exists( $this->config["wrapper"]["class"] ) ){
				throw new Exception( "Unable to load the adapter class.", 3 );
			}

			$this->wrapper = $this->config["wrapper"]["class"];
		}
		else{ 
			require_once Hybrid_Auth::$config["path_providers"] . $this->id . ".php" ;

			$this->wrapper = "Hybrid_Providers_" . $this->id; 
		}

		$this->adapter = new $this->wrapper( $this->id, $this->config, $this->params );

		return $this;
	}

	function login()
	{
		Hybrid_Logger::info( "Enter Hybrid_Provider_Adapter::login( {$this->id} ) " );

		if( ! $this->adapter ){
			throw new Exception( "Hybrid_Provider_Adapter::login() should not directly used." );
		}

		foreach( Hybrid_Auth::$config["providers"] as $idpid => $params ){
			Hybrid_Auth::storage()->delete( "hauth_session.{$idpid}.hauth_return_to"    );
			Hybrid_Auth::storage()->delete( "hauth_session.{$idpid}.hauth_endpoint"     );
			Hybrid_Auth::storage()->delete( "hauth_session.{$idpid}.id_provider_params" );
		}

		$this->logout();

		$HYBRID_AUTH_URL_BASE = Hybrid_Auth::$config["base_url"];

		$this->params["hauth_token"] = session_id();

		$this->params["hauth_time"]  = time();

		$this->params["login_start"] = $HYBRID_AUTH_URL_BASE . ( strpos( $HYBRID_AUTH_URL_BASE, '?' ) ? '&' : '?' ) . "hauth.start={$this->id}&hauth.time={$this->params["hauth_time"]}";

		$this->params["login_done"]  = $HYBRID_AUTH_URL_BASE . ( strpos( $HYBRID_AUTH_URL_BASE, '?' ) ? '&' : '?' ) . "hauth.done={$this->id}";

		Hybrid_Auth::storage()->set( "hauth_session.{$this->id}.hauth_return_to"    , $this->params["hauth_return_to"] );
		Hybrid_Auth::storage()->set( "hauth_session.{$this->id}.hauth_endpoint"     , $this->params["login_done"] ); 
		Hybrid_Auth::storage()->set( "hauth_session.{$this->id}.id_provider_params" , $this->params );

		Hybrid_Auth::storage()->config( "CONFIG", Hybrid_Auth::$config );

		Hybrid_Logger::debug( "Hybrid_Provider_Adapter::login( {$this->id} ), redirect the user to login_start URL." );

		Hybrid_Auth::redirect( $this->params["login_start"] );
	}

	function logout()
	{
		$this->adapter->logout();
	}

	public function isUserConnected()
	{
		return $this->adapter->isUserConnected();
	}

	public function __call( $name, $arguments ) 
	{
		Hybrid_Logger::info( "Enter Hybrid_Provider_Adapter::$name(), Provider: {$this->id}" );

		if ( ! $this->isUserConnected() ){
			throw new Exception( "User not connected to the provider {$this->id}.", 7 );
		} 

		if ( ! method_exists( $this->adapter, $name ) ){
			throw new Exception( "Call to undefined function Hybrid_Providers_{$this->id}::$name()." );
		}

		if( count( $arguments ) ){
			return $this->adapter->$name( $arguments[0] ); 
		} 
		else{
			return $this->adapter->$name(); 
		}
	}

	public function getAccessToken()
	{
		if( ! $this->adapter->isUserConnected() ){
			Hybrid_Logger::error( "User not connected to the provider." );

			throw new Exception( "User not connected to the provider.", 7 );
		}

		return
			ARRAY(
				"access_token"        => $this->adapter->token( "access_token" )       ,
				"access_token_secret" => $this->adapter->token( "access_token_secret" ),
				"refresh_token"       => $this->adapter->token( "refresh_token" )      ,
				"expires_in"          => $this->adapter->token( "expires_in" )         ,
				"expires_at"          => $this->adapter->token( "expires_at" )         ,
				);
	}

	function api()
	{
		if( ! $this->adapter->isUserConnected() ){
			Hybrid_Logger::error( "User not connected to the provider." );

			throw new Exception( "User not connected to the provider.", 7 );
		}

		return $this->adapter->api;
	}

	function returnToCallbackUrl()
	{ 
		$callback_url = Hybrid_Auth::storage()->get( "hauth_session.{$this->id}.hauth_return_to" );

		Hybrid_Auth::storage()->delete( "hauth_session.{$this->id}.hauth_return_to"    );
		Hybrid_Auth::storage()->delete( "hauth_session.{$this->id}.hauth_endpoint"     );
		Hybrid_Auth::storage()->delete( "hauth_session.{$this->id}.id_provider_params" );

		Hybrid_Auth::redirect( $callback_url );
	}

	function getConfigById( $id )
	{ 
		if( isset( Hybrid_Auth::$config["providers"][$id] ) ){
			return Hybrid_Auth::$config["providers"][$id];
		}

		return NULL;
	}

	function getProviderCiId( $id )
	{
		foreach( Hybrid_Auth::$config["providers"] as $idpid => $params ){
			if( strtolower( $idpid ) == strtolower( $id ) ){
				return $idpid;
			}
		}

		return NULL;
	}
}
