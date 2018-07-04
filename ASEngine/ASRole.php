<?php
class ASRole {

    private $db = null;

    private $validator;

    public function __construct() {
        $this->db = ASDatabase::getInstance();
        $this->validator = new ASValidator();
    }

    public function getId($name) {
        $result = $this->db->select("SELECT `role_id` FROM `as_user_roles` WHERE `role` = :r", array( 'r' => $name ));
        if ( count ( $result ) > 0 )
            return $result[0]['role_id'];
        else
            return null;
    }

    public function name($id) {
        $result = $this->db->select("SELECT `role` FROM `as_user_roles` WHERE `role_id` = :id", array( 'id' => $id ));
        if ( count ( $result ) > 0 )
            return $result[0]['role_id'];
        else
            return null;
    }

    public function add($name) {
        $result = array();

        if ( ! $this->validator->roleExist($name) )
        {
            $this->db->insert("as_user_roles", array("role" => strtolower(strip_tags($_POST['role']))));
            $result = array(
                "status"   => "success",
                "roleName" => strip_tags($_POST['role']),
                "roleId"   => $this->db->lastInsertId()
            );
        }
        else
        {
            $result = array(
                "status" => "error",
                "message" => ASLang::get('role_taken')
            );
        }

        return $result;
    }

    public function delete($id) {
        if(in_array($_POST['roleId'], array(1,2,3)) )
            exit();

        $this->db->delete("as_user_roles", "role_id = :id", array( "id" => $id ));

        $this->db->update("as_users", array( 'user_role' => "1" ), "user_role = :r", array( "r" => $id ) );
    }

} 