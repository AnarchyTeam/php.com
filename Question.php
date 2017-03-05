<?php

/**
 * Created by PhpStorm.
 * User: luqman
 * Date: 3/5/17
 * Time: 1:20 PM
 */

use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;

class Question
{
    const LEVEL_1 = 130;
    const LEVEL_2 = self::LEVEL_1 + 81;
    const LEVEL_3 = self::LEVEL_2 + 43;

    public $user;
    public $level;
    public $type;

    private $lists;
    private $answer;
    private $options;
    private $total;

    public function __construct(User $user)
    {
        $this->user = $user;
        if($user->current_score < self::LEVEL_1){
            $this->level = 1;
        }elseif ($user->current_score < self::LEVEL_2){
            $this->level = 2;
        }elseif ($user->current_score < self::LEVEL_3){
            $this->level = 3;
        }else{
            $this->level = 4;
        }
        $this->type = mt_rand(1,3);
    }

    /**
     * @return TemplateMessageBuilder
     */
    public function generate(){

        while (empty($this->answer)){
            $this->getLists();
            $this->getAnswer();
            $this->getOptions();
        }

        $line_options = [];
        foreach ($this->options as $option) {
            if($this->type == 1){
                $label = $option['full_name'];
                $text = $option['image'];
            }elseif ($this->type == 2){
                $label = $option['capital'];
                $text = $option['capital'];
            }else{
                $label = $option['region'];
                $text = $option['region'];
            }
            $line_options[] = new MessageTemplateActionBuilder($label, $text);
        }

        if($this->type == 1){
            $question = "Bendera negara apakah ini?";
            $title = "Negara";
        }elseif ($this->type == 2){
            $question = "Apakah ibukota negara yang memiliki bendera ini?";
            $title = "Ibukota";
        }else{
            $question = "Terletak di kawasan manakan negara yang memiliki bendera ini?";
            $title = "Kawasan";
        }

        $button_template = new ButtonTemplateBuilder($title, $question, $this->answer['url'], $line_options);

        return new TemplateMessageBuilder('Gunakan Line Apps untuk melihat soal ini', $button_template);
   }

   private function getLists(){
       $query = "SELECT * FROM countries WHERE level = {$this->level}";
       if(! empty($this->user->answered)){
           $query .= " AND id NOT IN ({$this->user->answered})";
       }

       if($this->type == 2){
           $query .= " AND capital != ''";
       }

       $this->lists = DB::getDB()->query($query)->fetchAll(PDO::FETCH_ASSOC);
       $this->total = count($this->lists);
   }

   private function getAnswer(){
       $this->answer = $this->lists[mt_rand(0, ($this->total - 1) )];
   }

   private function getOptions(){
       $options = [];
       $ids = [$this->answer['id']];
       while (count($options) < 3){
           $option = $this->lists[mt_rand(0, $this->total)];
           if(! in_array($option['id'], $ids)){
               $options[] = $option;
               $ids[] = $option['id'];
           }
       }
       $this->options = $options;
       $this->options[] = $this->answer;
       shuffle($this->options);
   }


}