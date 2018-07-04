<?php
return
	array(
		"base_url" => SOCIAL_CALLBACK_URI,

		"providers" => array ( 
			"OpenID" => array (
				"enabled" => false
			),

			"Yahoo" => array ( 
				"enabled" => false,
				"keys"    => array ( "id" => "", "secret" => "" ),
			),

			"AOL"  => array ( 
				"enabled" => false 
			),

			"Google" => array ( 
				"enabled" => GOOGLE_ENABLED,
				"keys"    => array ( "id" => GOOGLE_ID, "secret" => GOOGLE_SECRET ), 
			),

			"Facebook" => array ( 
				"enabled" => FACEBOOK_ENABLED,
				"keys"    => array ( "id" => FACEBOOK_ID, "secret" => FACEBOOK_SECRET ), 
			),

			"Twitter" => array ( 
				"enabled" => TWITTER_ENABLED,
				"keys"    => array ( "key" => TWITTER_KEY, "secret" => TWITTER_SECRET ) 
			),

			"Live" => array ( 
				"enabled" => false,
				"keys"    => array ( "id" => "", "secret" => "" ) 
			),

			"MySpace" => array ( 
				"enabled" => false,
				"keys"    => array ( "key" => "", "secret" => "" ) 
			),

			"LinkedIn" => array ( 
				"enabled" => false,
				"keys"    => array ( "key" => "", "secret" => "" ) 
			),

			"Foursquare" => array (
				"enabled" => false,
				"keys"    => array ( "id" => "", "secret" => "" ) 
			),
		),

		"debug_mode" => false,

		"debug_file" => "",
	);
