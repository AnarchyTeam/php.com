<?php

/**
 * Created by PhpStorm.
 * User: luqman
 * Date: 2/25/17
 * Time: 6:27 PM
 */

class User{

    public $id,
        $user_id,
        $display_name,
        $current_score,
        $high_score,
        $line_id,
        $life,
        $answered;

    private $db;

    public function __construct()
    {
        $this->db = DB::getDB();
    }

    public function insert(){
        $sql = "INSERT INTO flag_quiz.users (user_id, display_name, current_score, high_score, line_id, life, answered) VALUES (:user_id, :display_name, 0, 0, :line_id, 5, '')";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $this->user_id,
            'display_name' => $this->display_name,
            'line_id' => $this->line_id
        ]);
        $this->id = $this->db->lastInsertId();
        $this->current_score = 0;
        $this->high_score = 0;
        $this->life = 5;
        $this->answered = '';
    }

    public static function exist($user_id){
        $statement = DB::getDB()->prepare("SELECT * FROM flag_quiz.users WHERE user_id = :user_id");
        $statement->execute(['user_id' => $user_id]);

        return $statement->rowCount() > 0;
    }

    /**
     * @param $params
     * @return User
     */
    public static function findOne($params){
        $sql = "SELECT * FROM flag_quiz.users";
        if(! empty($params)){
            $sql .= " WHERE";
            foreach (array_keys($params) as $key) {
                $sql .= " {$key} = :{$key} AND";
            }
            $sql = substr($sql, 0, -3);
        }
        $stmt = DB::getDB()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchObject('User');

    }
}