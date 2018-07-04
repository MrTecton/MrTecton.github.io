<?php

class ASComment {

    private $db = null;

    function __construct() {
        $this->db = ASDatabase::getInstance();
    }

    public function insertComment($userId, $comment) {
        $user     = new ASUser($userId);
        $userInfo = $user->getInfo();
        $datetime = date("Y-m-d H:i:s");

        $this->db->insert("as_comments",  array(
            "posted_by"      => $user->id(),
            "posted_by_name" => $userInfo['username'],
            "comment"        => strip_tags($comment),
            "post_time"      => $datetime
        ));
        $result = array(
            "user"      => $userInfo['username'],
            "comment"   => stripslashes( strip_tags($comment) ),
            "postTime"  => $datetime
        );
        return json_encode($result);
    }

    public function getUserComments($userId) {
        $result = $this->db->select(
                    "SELECT * FROM `as_comments` WHERE `user_id` = :id",
                    array ("id" => $userId)
                  );

        return $result;
    }

    public function getComments($limit = 7) {
        return $this->db->select("SELECT * FROM `as_comments` ORDER BY `post_time` DESC LIMIT $limit");
    }
}
