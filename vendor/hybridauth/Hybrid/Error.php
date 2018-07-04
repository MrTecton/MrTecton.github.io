<?php
class Hybrid_Error
{
	public static function setError( $message, $code = NULL, $trace = NULL, $previous = NULL )
	{
		Hybrid_Logger::info( "Enter Hybrid_Error::setError( $message )" );

		Hybrid_Auth::storage()->set( "hauth_session.error.status"  , 1         );
		Hybrid_Auth::storage()->set( "hauth_session.error.message" , $message  );
		Hybrid_Auth::storage()->set( "hauth_session.error.code"    , $code     );
		Hybrid_Auth::storage()->set( "hauth_session.error.trace"   , $trace    );
		Hybrid_Auth::storage()->set( "hauth_session.error.previous", $previous );
	}

	public static function clearError()
	{ 
		Hybrid_Logger::info( "Enter Hybrid_Error::clearError()" );

		Hybrid_Auth::storage()->delete( "hauth_session.error.status"   );
		Hybrid_Auth::storage()->delete( "hauth_session.error.message"  );
		Hybrid_Auth::storage()->delete( "hauth_session.error.code"     );
		Hybrid_Auth::storage()->delete( "hauth_session.error.trace"    );
		Hybrid_Auth::storage()->delete( "hauth_session.error.previous" );
	}

	public static function hasError()
	{ 
		return (bool) Hybrid_Auth::storage()->get( "hauth_session.error.status" );
	}

	public static function getErrorMessage()
	{ 
		return Hybrid_Auth::storage()->get( "hauth_session.error.message" );
	}

	public static function getErrorCode()
	{ 
		return Hybrid_Auth::storage()->get( "hauth_session.error.code" );
	}

	public static function getErrorTrace()
	{ 
		return Hybrid_Auth::storage()->get( "hauth_session.error.trace" );
	}

	public static function getErrorPrevious()
	{ 
		return Hybrid_Auth::storage()->get( "hauth_session.error.previous" );
	}
}
