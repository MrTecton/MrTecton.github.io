<?php
class ASRegister {

    private $mailer;

    private $db = null;

    function __construct() {
       
        $this->db = ASDatabase::getInstance();

        $this->mailer = new ASEmail();
    }
    
    public function register($data) {
        $user = $data['userData'];
        
        $errors = $this->validateUser($data);
        
        if(count($errors) == 0) {
            $key = $this->_generateKey();

            MAIL_CONFIRMATION_REQUIRED === true ? $confirmed = 'N' : $confirmed = 'Y';
            
            $this->db->insert('as_users', array(
                "email"     => $user['email'],
                "username"  => strip_tags($user['username']),
                "password"  => $this->hashPassword($user['password']),
                "confirmed" => $confirmed,
                "confirmation_key"  => $key,
                "register_date"     => date("Y-m-d")     
            ));

            $userId = $this->db->lastInsertId();

            $this->db->insert('as_user_details', array( 'user_id' => $userId ));
            
            if ( MAIL_CONFIRMATION_REQUIRED ) {
                $this->mailer->confirmationEmail($user['email'], $key);
                $msg = ASLang::get('success_registration_with_confirm');
            }
            else
                $msg = ASLang::get('success_registration_no_confirm');
            
            $result = array(
                "status" => "success",
                "msg"    => $msg
            );
            
            echo json_encode($result);
        }
        else {
            $result = array(
                "status" => "error",
                "errors" => $errors
            );
            
            echo json_encode ($result);
        }
    }

    public function getByEmail($email) {
        $result = $this->db->select("SELECT * FROM `as_users` WHERE `email` = :e", array( 'e' => $email ));
        if ( count ( $result ) > 0 )
            return $result[0];
        return $result;
    }

    public function getBySocial($provider, $id) {
        $result = $this->db->select('SELECT * FROM `as_social_logins` WHERE `provider` = :p AND `provider_id` = :id ', array(
            'p'  => $provider,
            'id' => $id
        ));

        if ( count ( $result ) > 0 ) {
            $res = $result[0];
            $user = new ASUser($res['user_id']);
            return $user->getInfo();
        }

        else
            return $result;
    }

    public function registeredViaSocial($provider, $id) {
        $result = $this->getBySocial($provider, $id);

        if ( count ( $result ) === 0 )
            return false;
        else
            return true;
    }

    public function addSocialAccount($userId, $provider, $providerId) {
        $this->db->insert('as_social_logins', array(
            'user_id' => $userId,
            'provider' => $provider,
            'provider_id' => $providerId,
            'created_at' => date('Y-m-d H:i:s')
        ));
    }

    public function forgotPassword($userEmail) {

        $validator = new ASValidator();
        $errors = array();
        if($userEmail == "")
            $errors[] = ASLang::get('email_required');
        if( ! $validator->emailValid($userEmail) )
            $errors[] = ASLang::get('email_wrong_format');
        
        if( ! $validator->emailExist($userEmail) )
            $errors[] = ASLang::get('email_not_exist');

        $login = new ASLogin();

        if($login->_isBruteForce())
            $errors[] = ASLang::get('brute_force');
        
        if(count($errors) == 0) {
            $key = $this->_generateKey();
            
            $this->db->update(
                        'as_users', 
                         array(
                             "password_reset_key" => $key,
                             "password_reset_confirmed" => 'N',
                             "password_reset_timestamp" => date('Y-m-d H:i:s')
                         ),
                         "`email` = :email",
                         array("email" => $userEmail)
                    );

            $login->increaseLoginAttempts();
            
            $this->mailer->passwordResetEmail($userEmail, $key);
        }
        else
            echo json_encode ($errors);
    }
    
    public function resetPassword($newPass, $passwordResetKey) {
        $validator = new ASValidator();
        if ( ! $validator->prKeyValid($passwordResetKey) ) {
            echo 'Invalid password reset key!';
            return;
        }

        $pass = $this->hashPassword($newPass);
        $this->db->update(
                    'as_users', 
                    array("password" => $pass, 'password_reset_confirmed' => 'Y', 'password_reset_key' => ''),
                    "`password_reset_key` = :prk ",
                    array("prk" => $passwordResetKey)
                );
    }
    
     public function hashPassword($password) {
        $salt = "$2a$" . PASSWORD_BCRYPT_COST . "$" . PASSWORD_SALT;
        
        if(PASSWORD_ENCRYPTION == "bcrypt") {
            $newPassword = crypt($password, $salt);
        }
        else {
            $newPassword = $password;
            for($i=0; $i<PASSWORD_SHA512_ITERATIONS; $i++)
                $newPassword = hash('sha512',$salt.$newPassword.$salt);
        }
        
        return $newPassword;
     }
    
     public function botProtection() {
        ASSession::set("bot_first_number", rand(1,9));
        ASSession::set("bot_second_number", rand(1,9));
    }

    public function validateUser($data, $botProtection = true) {
        $id     = $data['fieldId'];
        $user   = $data['userData'];
        $errors = array();
        $validator = new ASValidator();
        
        if( $validator->isEmpty($user['email']) )
            $errors[] = array( 
                "id"    => $id['email'],
                "msg"   => ASLang::get('email_required') 
            );
        
        if( $validator->isEmpty($user['username']) )
            $errors[] = array( 
                "id"    => $id['username'],
                "msg"   => ASLang::get('username_required')
            );
        
        if( $validator->isEmpty($user['password']) )
            $errors[] = array( 
                "id"    => $id['password'],
                "msg"   => ASLang::get('password_required')
            );
        
        if($user['password'] != $user['confirm_password'])
            $errors[] = array( 
                "id"    => $id['confirm_password'],
                "msg"   => ASLang::get('passwords_dont_match')
            );
        
        if( ! $validator->emailValid($user['email']) )
            $errors[] = array( 
                "id"    => $id['email'],
                "msg"   => ASLang::get('email_wrong_format')
            );
        
        if( $validator->emailExist($user['email']) )
            $errors[] = array( 
                "id"    => $id['email'],
                "msg"   => ASLang::get('email_taken')
            );
        
        if( $validator->usernameExist($user['username']) )
            $errors[] = array( 
                "id"    => $id['username'],
                "msg"   => ASLang::get('username_taken')
            );
        
        if ( $botProtection )
        {
            $sum = ASSession::get("bot_first_number") + ASSession::get("bot_second_number");
            if($sum != intval($user['bot_sum']))
                $errors[] = array( 
                    "id"    => $id['bot_sum'],
                    "msg"   => ASLang::get('wrong_sum')
                );
        }        
        
        return $errors;
    }

    public function randomPassword($length = 7) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    public function socialToken() {
        return $this->randomPassword(40);
    }

    private function _generateKey() {
        return md5(time() . PASSWORD_SALT . time());
    }
    
}
