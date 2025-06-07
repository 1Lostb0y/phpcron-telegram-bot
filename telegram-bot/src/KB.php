<?php

namespace phpcron\CronBot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;

class KB
{
    /**
     * Cron object
     *
     * @var \phpcron\CronBot\cron
     */
    private static $Dt;

    public static function initialize(Hook $Dt)
    {

        if (!($Dt instanceof Hook)) {
            throw new Exception\CronException('Invalid Hook Pointer!');
        }

        self::$Dt = $Dt;
    }


    public static function GetGroupSe($key){
        if(!$key){
            return false;
        }
        $get = RC::Get($key);
        return $get ?? "Unknown";
    }


}