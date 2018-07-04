<?php
class ASValidator {

    private $db;

    public function __construct() {
        $this->db = ASDatabase::getInstance();
    }

    public function isEmpty($in) {
        if ( is_array($in) )
            return empty($in);
        elseif ( $in == '' )
            return true;
        else
            return false;
    }

    public function longerThan($string, $numOfCharacters) {
        if ( strlen($string) > $numOfCharacters )
            return TRUE;
        return FALSE;
    }

    public function emailValid($email) {
        return preg_match("/^[_a-z0-9-]+(\.[_a-z0-9+-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i", $email);
    }

    public function usernameExist($username) {
        $table  = 'as_users';
        $column = 'username';
        return $this->exist($table, $column, $username);
    }

    public function emailExist($email) {
        $table  = 'as_users';
        $column = 'email';
        return $this->exist($table, $column, $email);
    }

    public function roleExist($role) {
        $table  = 'as_user_roles';
        $column = 'role';
        return $this->exist($table, $column, $role);
    }

    public function prKeyValid($key) {
        if ( strlen($key) != 32 )
            return FALSE;

        $result = $this->db->select('SELECT * FROM `as_users` WHERE `password_reset_key` = :k', array(
            'k' => $key
        ));

        if ( count ( $result ) !== 1 )
            return FALSE;

        $result = $result[0];

        if ( $result['password_reset_confirmed'] == 'Y' )
            return FALSE;

        $now = date('Y-m-d H:i:s');
        $requestedAt = $result['password_reset_timestamp'];

        if ( strtotime($now . ' -'.PASSWORD_RESET_KEY_LIFE.' minutes') > strtotime($requestedAt) )
            return FALSE;

        return TRUE;
    }

    private function exist($table, $column, $value) {

        $result = $this->db->select("SELECT * FROM `$table` WHERE `$column` = :val", array( 'val' => $value ));

        if ( count ( $result ) > 0 )
            return TRUE;
        else
            return FALSE;
    }

}