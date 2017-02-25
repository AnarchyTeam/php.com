<?php

/**
 * Created by PhpStorm.
 * User: luqman
 * Date: 2/25/17
 * Time: 6:20 PM
 */
class DB
{
    public static $db;

    public static function getDB(){
        if(! isset(self::$db)){
            $config = [
                'host' => getenv('DB_HOST'),
                'user' => getenv('DB_USER'),
                'password' => getenv('DB_PASSWORD'),
                'dbname' => getenv('DB_NAME')
            ];
            self::$db = new PDO("mysql:host=".$config['host'].";dbname=".$config['dbname'], $config['user'], $config['password']);
            self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        return self::$db;
    }
}