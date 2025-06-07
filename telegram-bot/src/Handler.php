<?php

namespace phpcron\CronBot;

use Lcobucci\JWT\Keys;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;

class Handler
{
    /**
     * Cron object
     *
     * @var \phpcron\CronBot\cron
     */
    private static $Dt;

    public static function initialize(cron $H)
    {

        if (!($H instanceof cron)) {
            throw new Exception\CronException('Invalid Hook Pointer!');
        }

        self::$Dt = $H;
    }

    public static function Handel(){
        $game_state = HL::_getGameState();


        //   $RN = R::NoPerfix();
        // $RN->set('-1001162150617:time',0);


        // R::Del('GamePl:CheckNight') ;
        // R::GetSet( time() + 2,'timer');
        //echo R::Get('timer') - time();


        //  $CountPlayer = HL::_getCountPlayer();

        // if($CountPlayer == 0){
        //   HL::GroupClosedThGame();
        //}

        if(R::CheckExit('GamePl:InComplater') == true){
            return false;
        }




        /*

         $array_close = [-1001155046247,-1001257703456];
         if(in_array(self::$Dt->chat_id,$array_close)){
             HL::GroupClosedThGame();
         }
        */





        if(R::CheckExit('GamePl:GameIsEnd')){



            return false;
        }






        $Cm = R::GetSet(true,'GamePl:InComplater');
        R::Ex(3,'GamePl:InComplater');

        switch ($game_state){
            case 'night':
            case 'vote':
            case 'day':

                if(R::CheckExit('GamePl:CheckNight')){
                    $Time = R::Get('GamePl:CheckNight');
                    $Left = time() - $Time;
                    if($Left > R::Get('night_timer')){
                        R::Del('GamePl:CheckNight') ;
                    }
                }


                HL::CheckSmite();
                if(R::CheckExit('GamePl:HunterKill')  == false) {
                    $CheckEndGame = HL::CheckEndGame();
                    if ($CheckEndGame && R::CheckExit('GamePl:HunterKill') == false and R::CheckExit('GamePl:RoleAssinged')) {
                        HL::GamedEnd($CheckEndGame);
                        return true;
                    }
                }

                $AlphaCheck = true;
                if(R::CheckExit('GamePl:DeadforestQueen')){
                    if(R::Get('GamePl:Night_no') == R::Get('GamePl:DeadforestQueen')) {
                        $AlphaCheck = false;
                    }
                }

                if(R::CheckExit('GamePl:HunterKill') == false and $AlphaCheck == true) {
                    HL::checkTime();
                }

                HL::CheckTimer();

                break;
        }


        switch ($game_state){
            case 'join':
                join::Handel();
                break;
            case 'night':
                NG::Handel();

                break;
            case 'vote':

                VT::Handel();

                break;
            case 'day':
                DY::Handel();

                break;
        }


        R::Del('GamePl:InComplater');

    }

}