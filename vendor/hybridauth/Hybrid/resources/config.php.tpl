<?php
return 
	array(
		"base_url" => "#GLOBAL_HYBRID_AUTH_URL_BASE#", 

		"providers" => array ( 
			"OpenID" => array (
				"enabled" =>
			),

			"AOL"  => array ( 
				"enabled" =>
			),

			"Yahoo" => array ( 
				"enabled" =>
				"keys"    => array ( "id" => "#YAHOO_APPLICATION_APP_ID#", "secret" => "#YAHOO_APPLICATION_SECRET#" )
			),

			"Google" => array ( 
				"enabled" =>
				"keys"    => array ( "id" => "#GOOGLE_APPLICATION_APP_ID#", "secret" => "#GOOGLE_APPLICATION_SECRET#" )
			),

			"Facebook" => array ( 
				"enabled" =>
				"keys"    => array ( "id" => "#FACEBOOK_APPLICATION_APP_ID#", "secret" => "#FACEBOOK_APPLICATION_SECRET#" )
			),

			"Twitter" => array ( 
				"enabled" =>
				"keys"    => array ( "key" => "#TWITTER_APPLICATION_KEY#", "secret" => "#TWITTER_APPLICATION_SECRET#" ) 
			),

			"Live" => array ( 
				"enabled" =>
				"keys"    => array ( "id" => "#LIVE_APPLICATION_APP_ID#", "secret" => "#LIVE_APPLICATION_SECRET#" ) 
			),

			"MySpace" => array ( 
				"enabled" =>
				"keys"    => array ( "key" => "#MYSPACE_APPLICATION_KEY#", "secret" => "#MYSPACE_APPLICATION_SECRET#" ) 
			),

			"LinkedIn" => array ( 
				"enabled" =>
				"keys"    => array ( "key" => "#LINKEDIN_APPLICATION_KEY#", "secret" => "#LINKEDIN_APPLICATION_SECRET#" ) 
			),

			"Foursquare" => array (
				"enabled" =>
				"keys"    => array ( "id" => "#FOURSQUARE_APPLICATION_APP_ID#", "secret" => "#FOURSQUARE_APPLICATION_SECRET#" ) 
			),
		),

		"debug_mode" => false,

		"debug_file" => ""
	);
