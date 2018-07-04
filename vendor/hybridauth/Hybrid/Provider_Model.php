<?php
abstract class Hybrid_Provider_Model
{
	public $providerId = NULL;

	public $config     = NULL;

	public $params     = NULL;

	public $endpoint   = NULL; 

	public $user       = NULL;

	public $api        = NULL; 

	function __construct( $providerId, $config, $params = NULL )
	{
		if( ! $params ){
			$this->params = Hybrid_Auth::storage()->get( "hauth_session.$providerId.id_provider_params" );
		}
		else{
			$this->params = $params;
		}

		$this->providerId = $providerId;

		$this->endpoint = Hybrid_Auth::storage()->get( "hauth_session.$providerId.hauth_endpoint" );

		$this->config = $config;

		$this->user = new Hybrid_User();
		$this->user->providerId = $providerId;

		$this->initialize(); 

		Hybrid_Logger::debug( "Hybrid_Provider_Model::__construct( $providerId ) initialized. dump current adapter instance: ", serialize( $this ) );
	}

	abstract protected function initialize(); 

	abstract protected function loginBegin();

	abstract protected function loginFinish();

	function logout()
	{
		Hybrid_Logger::info( "Enter [{$this->providerId}]::logout()" );

		$this->clearTokens();

		return TRUE;
	}

	function getUserProfile()
	{
		Hybrid_Logger::error( "HybridAuth do not provide users contats list for {$this->providerId} yet." ); 
		
		throw new Exception( "Provider does not support this feature.", 8 ); 
	}

	function getUserContacts() 
	{
		Hybrid_Logger::error( "HybridAuth do not provide users contats list for {$this->providerId} yet." ); 
		
		throw new Exception( "Provider does not support this feature.", 8 ); 
	}

	function getUserActivity( $stream ) 
	{
		Hybrid_Logger::error( "HybridAuth do not provide user's activity stream for {$this->providerId} yet." ); 
		
		throw new Exception( "Provider does not support this feature.", 8 ); 
	}

	function setUserStatus( $status )
	{
		Hybrid_Logger::error( "HybridAuth do not provide user's activity stream for {$this->providerId} yet." ); 
		
		throw new Exception( "Provider does not support this feature.", 8 ); 
	}

	public function isUserConnected()
	{
		return (bool) Hybrid_Auth::storage()->get( "hauth_session.{$this->providerId}.is_logged_in" );
	}

	public function setUserConnected()
	{
		Hybrid_Logger::info( "Enter [{$this->providerId}]::setUserConnected()" );
		
		Hybrid_Auth::storage()->set( "hauth_session.{$this->providerId}.is_logged_in", 1 );
	}

	public function setUserUnconnected()
	{
		Hybrid_Logger::info( "Enter [{$this->providerId}]::setUserUnconnected()" );
		
		Hybrid_Auth::storage()->set( "hauth_session.{$this->providerId}.is_logged_in", 0 ); 
	}

	public function token( $token, $value = NULL )
	{
		if( $value === NULL ){
			return Hybrid_Auth::storage()->get( "hauth_session.{$this->providerId}.token.$token" );
		}
		else{
			Hybrid_Auth::storage()->set( "hauth_session.{$this->providerId}.token.$token", $value );
		}
	}

	public function deleteToken( $token )
	{
		Hybrid_Auth::storage()->delete( "hauth_session.{$this->providerId}.token.$token" );
	}

	public function clearTokens()
	{ 
		Hybrid_Auth::storage()->deleteMatch( "hauth_session.{$this->providerId}." );
	}
}
