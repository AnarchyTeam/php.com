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
    public $answer;
    private $options;
    public $total;

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
        if($user->current_score < 254){
            $this->type = 1;
        }else{
            $this->type = mt_rand(1,3);
        }
    }

    /**
     * @return TemplateMessageBuilder
     */
    public function generate(){

        while (empty($this->answer) || empty($this->options)){
            $this->getLists();
            $this->getAnswer();
            $this->getOptions();
        }

        $line_options = [];
        $answer_column = '';
        $serialize_option = [];
        foreach ($this->options as $option) {
            if($this->type == 1){
                if(strlen($option['full_name']) > 20){
                    $label = $option['image'];
                    $label = str_replace("_", " ", $label);
                    $label = ucwords($label);
                    if(strlen($label) > 20){
                        $label = substr($label,0,20);
                    }
                }else{
                    $label = $option['full_name'];
                }
                $text = $option['image'];
            }elseif ($this->type == 2){
                $label = $option['capital'];
                $text = $option['capital'];
            }else{
                $label = $option['region'];
                $text = $option['region'];
            }
            $serialize_option += [$text => $label];
            $line_options[] = new MessageTemplateActionBuilder($label, $text);
        }

        $title = "Pertanyaan ke ".($this->user->current_score + 1);
        if($this->type == 1){
            $question = "Bendera negara apakah ini?";
            $answer_column = 'image';
        }elseif ($this->type == 2){
            $question = "Apakah ibukota negara yang memiliki bendera ini?";
            $answer_column = 'capital';
        }else{
            $question = "Terletak di kawasan manakan negara yang memiliki bendera ini?";
            $answer_column = 'region';
        }

        if(empty($this->user->answered)){
            $this->user->answered = $this->answer['id'];
        }else{
            $this->user->answered .= ",{$this->answer['id']}";
        }
        $this->user->answer_needed = $this->answer[$answer_column];

        $serialize = [
            'title' => $title,
            'question' => $question,
            'url' => $this->answer['url'],
            'options' => $serialize_option
        ];
        $this->user->last_question = json_encode($serialize);
        $this->user->save();

        $button_template = new ButtonTemplateBuilder($title, $question, $this->answer['url'], $line_options);


        return new TemplateMessageBuilder('Gunakan Line Apps untuk melihat soal ini', $button_template);
   }

   public static function deserializeQuestion($json){
        $last_question = json_decode($json);

       foreach ($last_question->options as $text => $label) {
           $options[] = new MessageTemplateActionBuilder($label, $text);
       }

       $button_template = new ButtonTemplateBuilder($last_question->title, $last_question->question, $last_question->url, $options);

       return new TemplateMessageBuilder('Gunakan Line Apps untuk melihat soal ini', $button_template);
   }

   public static function getMenu(){
       $options[] = new MessageTemplateActionBuilder('Mulai main', 'mulai');
       $options[] = new MessageTemplateActionBuilder('Lihat high score', 'hi_score');
       $options[] = new MessageTemplateActionBuilder('Lihat top 10', 'global_rank');

       $button_template = new ButtonTemplateBuilder('Menu', 'Apa yang ingin Kakak lakukan?', null, $options);

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
           $option = $this->lists[mt_rand(0, ($this->total - 1) )];
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