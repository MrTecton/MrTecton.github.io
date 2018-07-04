<?php
class ASLogin {

    private $db = null;

    function __construct() {
       $this->db = ASDatabase::getInstance();
    }

    public function byId($id) {
        if ( $id != 0 && $id != '' && $id != null ) {
            $this->_updateLoginDate($id);
            ASSession::set("user_id", $id);
            if(LOGIN_FINGERPRINT == true)
                ASSession::set("login_fingerprint", $this->_generateLoginString ());
        }
    }
    
    public function isLoggedIn() {
        if(ASSession::get("user_id") == null)
             return false;
        
        if(LOGIN_FINGERPRINT == true) {
            $loginString  = $this->_generateLoginString();
            $currentString = ASSession::get("login_fingerprint");
            if($currentString != null && $currentString == $loginString)
                return true;
            else  {
                $this->logout();
                return false;
            }
        }
        
        return true;        
    }
    
    public function userLogin($username, $password) {
        $errors = $this->_validateLoginFields($username, $password);
        if(count($errors) != 0) {
            $result = implode("<br />", $errors);
            echo $result;
        }
        
        if($this->_isBruteForce()) {
            echo ASLang::get('brute_force');
            return;
        }
        
        $password = $this->_hashPassword($password);
        $result = $this->db->select(
                    "SELECT * FROM `as_users`
                     WHERE `username` = :u AND `password` = :p",
                     array(
                       "u" => $username,
                       "p" => $password
                     )
                  );
        
        if(count($result) == 1) 
        {
            if($result[0]['confirmed'] == "N") {
                echo ASLang::get('user_not_confirmed');
                return false;
            }

            if($result[0]['banned'] == "Y") {
                $this->increaseLoginAttempts();

                echo ASLang::get('user_banned');
                return false;
            }

            $this->_updateLoginDate($result[0]['user_id']);
            ASSession::set("user_id", $result[0]['user_id']);
            if(LOGIN_FINGERPRINT == true)
                ASSession::set("login_fingerprint", $this->_generateLoginString ());
            
            return true;
        }
        else {
            $this->increaseLoginAttempts();
            echo ASLang::get('wrong_username_password');
            return false;
        }
    }
    
    public function increaseLoginAttempts() {
        $date    = date("Y-m-d");
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $table   = 'as_login_attempts';
       
        $loginAttempts = $this->_getLoginAttempts();
        
        if($loginAttempts > 0)
            $this->db->update (
                        $table, 
                        array( "attempt_number" => $loginAttempts + 1 ), 
                        "`ip_addr` = :ip_addr AND `date` = :d", 
                        array( "ip_addr" => $user_ip, "d" => $date)
                      );
        else
            $this->db->insert($table, array(
                "ip_addr" => $user_ip,
                "date"    => $date
            ));
    }
    
    public function logout() {
        ASSession::destroySession();
    }

    public function _isBruteForce() {
        $loginAttempts = $this->_getLoginAttempts();
        if($loginAttempts > LOGIN_MAX_LOGIN_ATTEMPTS)
            return true;
        else
            return false;
    }
    
    private function _validateLoginFields($username, $password) {
        $id     = $_POST['id'];
        $errors = array();
        
        if($username == "")
            $errors[] = ASLang::get('username_required');
        
        if($password == "")
            $errors[] = ASLang::get('password_required');
        
        return $errors;
    }
    
    private function _generateLoginString() {
        $userIP = $_SERVER['REMOTE_ADDR'];
        $userBrowser = $_SERVER['HTTP_USER_AGENT'];
        $loginString = hash('sha512',$userIP.$userBrowser);
        return $loginString;
    }
    private function _getLoginAttempts() {
        $date = date("Y-m-d");
        $user_ip = $_SERVER['REMOTE_ADDR'];
        
         $query = "SELECT `attempt_number`
                   FROM `as_login_attempts`
                   WHERE `ip_addr` = :ip AND `date` = :date";
                      
         
        $result = $this->db->select($query, array(
            "ip"    => $user_ip,
            "date"  => $date
        ));
        if(count($result) == 0)
            return 0;
        else
            return intval($result[0]['attempt_number']);
    }
    
    private function _hashPassword($password) {
        $register = new ASRegister();
        return $register->hashPassword($password);
    }
    
    private function _updateLoginDate($userid) {
        $this->db->update(
                    "as_users",
                    array("last_login" => date("Y-m-d H:i:s")),
                    "user_id = :u",
                    array( "u" => $userid)
                );
    }
    
}

