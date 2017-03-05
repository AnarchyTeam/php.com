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
        $answered,
        $answer_needed,
        $last_question;

    private $db;

    public function __construct()
    {
        $this->db = DB::getDB();
    }

    public function insert(){
        $sql = "INSERT INTO users (user_id, display_name, current_score, high_score, line_id, life, answered, answer_needed, last_question) VALUES (:user_id, :display_name, 0, 0, :line_id, 5, '', '', '')";

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
        $this->answer_needed = '';
        $this->last_question = '';
    }

    public static function exist($user_id){
        $statement = DB::getDB()->prepare("SELECT * FROM users WHERE user_id = :user_id");
        $statement->execute(['user_id' => $user_id]);

        return $statement->rowCount() > 0;
    }

    public function save(){
        $sql = "UPDATE users SET current_score=:current_score, high_score=:high_score, life=:life, answered=:answered, answer_needed=:answer_needed, last_question=:last_question WHERE id={$this->id}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'current_score' => $this->current_score,
            'high_score' => $this->high_score,
            'life' => $this->life,
            'answered' => $this->answered,
            'answer_needed' => $this->answer_needed,
            'last_question' => $this->last_question
        ]);
    }

    public static function getTopTen(){
        $query = "SELECT * FROM users ORDER BY high_score DESC LIMIT 2";

        $users = DB::getDB()->query($query)->fetchAll(PDO::FETCH_ASSOC);
        $result = "Top 10 Global\n\n";
        for ($i = 0; $i < count($users); $i++){
            $result .= ($i+1).". {$users[$i]['display_name']} - {$users[$i]['high_score']}";
            if($i+1 < count($users)){
                $result ."\n";
            }
        }
        return new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($result);
    }

    /**
     * @param $params
     * @return User
     */
    public static function findOne($params){
        $sql = "SELECT * FROM users";
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