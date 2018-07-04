<?php
class ASLang {

    public static function all($jsonEncode = true) {
		$language = self::getLanguage();

		$trans = self::getTrans($language);
		
		if ( $jsonEncode )
			return json_encode($trans);
		else
			return $trans;
	}

    public static function get($key, $bindings = array() ) {
		$language = self::getLanguage();

		$trans = self::getTrans($language);

		if ( ! isset ( $trans[$key] ) )
			return '';

		$value = $trans[$key];

		if ( ! empty($bindings) ) {
			foreach ( $bindings as $key => $val )
				$value = str_replace('{'.$key.'}', $val, $value);
		}

		return $value;
	}

    public static function setLanguage($language) {

		if ( self::isValidLanguage($language) ) {
			setcookie('as_lang', $language, time() * 60 * 60 * 24 * 365, '/');

			ASSession::set('as_lang', $language);

			header('Location: ' . $_SERVER['PHP_SELF']);
		}
		
	}

    public static function getLanguage() {
        if ( isset ( $_COOKIE['as_lang'] ) && self::isValidLanguage ( $_COOKIE['as_lang'] ) )
            return $_COOKIE['as_lang'];
        else
            return ASSession::get('as_lang', DEFAULT_LANGUAGE);
    }

    private static function getTrans($language) {
		$file = self::getFile($language);

		if ( ! self::isValidLanguage($language) )
			die('Language file doesn\'t exist!');
		else {
			$language = include $file;
			return $language;
		}
	}

    private static function getFile($language) {
		return dirname(__DIR__) . '/Lang/' . $language . '.php';
	}

    private static function isValidLanguage($lang) {
		$file = self::getFile($lang);

		if ( ! file_exists( $file ) )
			return false;
		else
			return true;
	}

}