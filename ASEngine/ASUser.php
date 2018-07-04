<?php
class ASUser {

    private $userId;

    private $db = null;

    function __construct($userId) {
        $this->userId = $userId;

        $this->db = ASDatabase::getInstance();
    }

    public function getAll() {
        $query = "SELECT `as_users`.`email`, `as_users`.`username`,`as_users`.`last_login`, `as_user_details`.*
                    FROM `as_users`, `as_user_details`
                    WHERE `as_users`.`user_id` = :id
                    AND `as_users`.`user_id` = `as_user_details`.`user_id`";

        $result = $this->db->select($query, array( 'id' => $this->userId ));

        if ( count ( $result ) > 0 )
            return $result[0];
        else
            return null;
    }

    public function add( $postData ) {

        $result = array();
        $reg = new ASRegister();
        $errors = $reg->validateUser($postData, false);

        if ( count ($errors) > 0 )
            $result = array(
                "status" => "error",
                "errors" => $errors
            );
        else {
            $data = $postData['userData'];

            $this->db->insert('as_users',  array (
                'email'         => $data['email'],
                'username'      => $data['username'],
                'password'      => $reg->hashPassword($data['password']),
                'confirmed'     => 'Y',
                'register_date' => date('Y-m-d H:i:s')
            ));

            $id = $this->db->lastInsertId();

            $this->db->insert('as_user_details', array (
                'user_id'    => $id,
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'phone'      => $data['phone'],
                'address'    => $data['address']
            ) );

            $result = array (
                "status" => "success",
                "msg"    => ASLang::get("user_added_successfully")
            );
        }

        return $result;
    }

    public function updateUser($data) {

        $errors = $this->_validateUserUpdate($data);

        if ( count ( $errors ) > 0 )
            echo json_encode(array(
                "status" => "error",
                "errors" => $errors
            ));
        else
        {

            $userData = $data['userData'];
            $currInfo = $this->getInfo();

            $userInfo = array();

            if ( $currInfo['email'] != $userData['email'] )
                $userInfo['email'] = $userData['email'];

            if ( $currInfo['username'] != $userData['username'] )
                $userInfo['username'] = $userData['username'];

            if ( $userData['password'] != hash('sha512','') ) {
                $password = $this->_hashPassword($userData['password']);
                if ( $currInfo['password'] != $password )
                    $userInfo['password'] = $password;
            }

            if ( count($userInfo) > 0 )
                $this->updateInfo($userInfo);

            $this->updateDetails(array(
                'first_name' => $userData['first_name'],
                'last_name'  => $userData['last_name'],
                'phone'      => $userData['phone'],
                'address'    => $userData['address']
            ));

            echo json_encode(array(
                "status" => "success",
                "msg" => ASLang::get("user_updated_successfully")
            ));
        }
    }
    
    public function id($newId = null) {
        if($newId != null)
            $this->userId = $newId;
        return $this->userId;
    }

    public function isAdmin() {
        if ( $this->userId == null )
            return false;

        $role = $this->getRole();
        if($role == "admin")
            return true;
        return false;
    }

    public function updatePassword($oldPass,$newPass) {
        $oldPass = $this->_hashPassword($oldPass);
        $newPass = $this->_hashPassword($newPass);
        
        $info = $this->getInfo();
        
        if($oldPass == $info['password'])
            $this->updateInfo(array( "password" => $newPass ));
        else
            echo ASLang::get('wrong_old_password');
    }

    public function changeRole() {
        $role = $_POST['role'];

        $result = $this->db->select("SELECT * FROM `as_user_roles` WHERE `role_id` = :r", array( "r" => $role ));

        if(count($result) == 0)
            return;

        $this->updateInfo(array( "user_role" => $role ));

        return $result[0]['role'];
    }

    public function getRole() {
        $result = $this->db->select(
                      "SELECT `as_user_roles`.`role` as role 
                       FROM `as_user_roles`,`as_users`
                       WHERE `as_users`.`user_role` = `as_user_roles`.`role_id`
                       AND `as_users`.`user_id` = :id",
                       array( "id" => $this->userId)
                    );

        return $result[0]['role'];
    }

    public function getInfo() {
        $result = $this->db->select(
                    "SELECT * FROM `as_users` WHERE `user_id` = :id",
                    array ("id" => $this->userId)
                  );
        if ( count($result) > 0 )
            return $result[0];
        else
            return null;
    }

    public function updateInfo($updateData) {
        $this->db->update(
                    "as_users", 
                    $updateData, 
                    "`user_id` = :id",
                    array( "id" => $this->userId )
               );
    }

    public function getDetails() {
        $result = $this->db->select(
                    "SELECT * FROM `as_user_details` WHERE `user_id` = :id",
                    array ("id" => $this->userId)
                  );

        if(count($result) == 0)
            return array(
                "first_name" => "",
                "last_name"  => "",
                "address"    => "",
                "phone"      => "",
                "empty"      => true
            );

        return $result[0];
    }

    public function updateDetails($details) {
        $currDetails = $this->getDetails();
        if(isset($currDetails['empty'])) {
            $details["user_id"] = $this->userId;
            $this->db->insert("as_user_details", $details);
        }
        else
            $this->db->update (
                "as_user_details",
                $details,
                "`user_id` = :id",
                array( "id" => $this->userId )
            );
    }

    public function deleteUser() {
        $this->db->delete("as_users", "user_id = :id", array( "id" => $this->userId ));
        $this->db->delete("as_user_details","user_id = :id", array( "id" => $this->userId ));
        $this->db->delete("as_comments","posted_by = :id", array( "id" => $this->userId ));
        $this->db->delete("as_social_logins","user_id = :id", array( "id" => $this->userId ));
    }

    private function _validateUserUpdate($data) {
        $id     = $data['fieldId'];
        $user   = $data['userData'];
        $errors = array();
        $validator = new ASValidator();

        $userInfo = $this->getInfo();
        if ( $userInfo == null ) {
            $errors[] = array(
                "id"    => $id['email'],
                "msg"   => ASLang::get('user_dont_exist')
            );
            return $errors;
        }

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

        if( ! $user['password'] == hash('sha512','') && ($user['password'] != $user['confirm_password'] ))
            $errors[] = array(
                "id"    => $id['confirm_password'],
                "msg"   => ASLang::get('passwords_dont_match')
            );

        if( ! $validator->emailValid($user['email']) )
            $errors[] = array(
                "id"    => $id['email'],
                "msg"   => ASLang::get('email_wrong_format')
            );

        if($user['email'] != $userInfo['email'] && $validator->emailExist($user['email']))
            $errors[] = array(
                "id"    => $id['email'],
                "msg"   => ASLang::get('email_taken')
            );

        if($user['username'] != $userInfo['username'] && $validator->usernameExist($user['username']) )
            $errors[] = array(
                "id"    => $id['username'],
                "msg"   => ASLang::get('username_taken')
            );

        return $errors;
    }
    
    private function _hashPassword($password) {
        $register = new ASRegister();
        return $register->hashPassword($password);
    }
}
