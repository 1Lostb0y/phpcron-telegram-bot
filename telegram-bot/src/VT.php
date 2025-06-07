<?php

namespace phpcron\CronBot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;

class VT
{
    /**
     * Cron objectf
     *
     * @var \phpcron\CronBot\cron
     */
    private static $Dt;

    public static function initialize(cron $Dt)
    {

        if (!($Dt instanceof cron)) {
            throw new Exception\CronException('Invalid Hook Pointer!');
        }

        self::$Dt = $Dt;
    }

    public static function Handel(){

        self::SendVote();

        if(R::CheckExit('GamePl:Update_vote') == false){
            self::CheckVoteMessage();
        }
    }

    public static function TroubleVote(){
        if(HL::CheckEndGame()){
            return false;
        }
        HL::ChangeGameStatus('vote');
        HL::EditMarkupKeyboard();
        R::DelKey('GamePl:Selected:Vote:*'); // پاک کردن انتخاب ها
        R::DelKey('GamePl:HoneyUser:*');
        R::Del('GamePl:SendVote');
        R::Del('GamePl:CheckVote');
        R::Del('GamePl:CheckVoteSend');
        if(R::CheckExit('GamePl:HunterKillVote')){
            R::Del('GamePl:HunterKillVote');
        }
        R::DelKey('GamePl:Selected:*'); // پاک کردن انتخاب ها
        $GroupMessage =  self::$Dt->LG->_('troubleGroupMessageS');

        return Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => $GroupMessage,
            'parse_mode'=> 'HTML'
        ]);


    }

    public static function GetUserVoteTo($user_id){
        $R = R::LRange(0,-1,'GamePl:Selected:Vote:'.$user_id);
        $re = [];
        $Voter = [];
        foreach ($R as $row){
            $json = json_decode($row,true);
            if(is_array($json)) {
                if (!in_array($json['user_id'], $Voter)) {
                    array_push($Voter, $json['user_id']);
                    array_push($re, $json['name']);
                }
            }
        }

        return implode(',',$re);
    }

    public static function GetUserVoteToR($user_id){
        $R = R::LRange(0,-1,'GamePl:Selected:Vote:'.$user_id);
        $Voter = [];
        foreach ($R as $row){
            $json = json_decode($row,true);
            if(is_array($json)) {
                if (!in_array($json['user_id'], $Voter)) {
                    $Voter[] = ['user_id' => $json['user_id'], 'name' => $json['name']];
                }
            }
        }

        return $Voter;
    }

    public static function CheckVote(){

        if(R::CheckExit('GamePl:CheckVote')){
            return false;
        }
        R::GetSet(true,'GamePl:CheckVote');


        if(R::CheckExit('GamePl:HunterKillVote')){
            return false;
        }

        if(R::CheckExit('GamePl:trouble:ok')){
            R::Del('GamePl:trouble');
            R::Del('GamePl:trouble:ok');
        }

        if(R::CheckExit('GamePl:SharlatanINTabar')){
            $GroupMessage = self::$Dt->LG->_('SharlatanChobeDar');
            HL::SaveMessage($GroupMessage);
            return true;
        }

        // چک میکنیم اگه صلح بود پیام ما این باشه
        if(R::CheckExit('GamePl:role_Solh:GroupInSolh')){
            $GroupMessage = self::$Dt->LG->_('PacifistNoLynchNow');
            HL::SaveMessage($GroupMessage);
            return true;
        }

        $Vote = self::CollectVote();
        if(!$Vote){
            return false;
        }
     
        R::Del('GamePl:VoteCount');

            if(isset($Vote['data'])) {
                $VoteData = $Vote['data'];
                // اگه رای گیری مخفی فعال بود
                if (R::Get('secret_vote') == "onr" && R::Get('secret_vote_count') == "onr") {
                    $Re = [];
                    foreach ($VoteData as $row) {
                        $Player = HL::_getPlayer($row['user_id']);
                        $PlayerName = HL::ConvertName($Player['user_id'], $Player['fullname_game']);
                        $NameVote = (R::Get('secret_vote_name') == "onr" ? self::GetUserVoteTo($row['user_id']) : "");
                        $Lang = self::$Dt->LG->_('SecretLynchResultNumber', array("{0}" => $row['total'], "{1}" => $PlayerName, "{2}" => $NameVote)) . PHP_EOL;
                        array_push($Re, $Lang);
                    }
                    $langRe = self::$Dt->LG->_('SecretLynchResultFull', array("{0}" => PHP_EOL . implode(PHP_EOL, $Re)));
                    HL::SendMessage($langRe);
                }
            }

            if($Vote['state'] == false){
                if(R::CheckExit('GamePl:role_Ruler:RulerOk')){
                    $GroupMessage = self::$Dt->LG->_('RuleTimeEnd');
                    HL::SaveMessage($GroupMessage);
                    return true;
                }

                $GroupMessage = self::$Dt->LG->_('no_kill');
                HL::SaveMessage($GroupMessage);
                return true;
            }


        $UserDetial = HL::_getPlayer($Vote['user_id']);

        $ArrayM = self::GetUserVoteToR($Vote['user_id']);

        $Name = HL::ConvertName($UserDetial['user_id'],$UserDetial['fullname_game']);

        if($UserDetial['user_role'] == "role_Shahzade" && R::CheckExit('GamePl:role_Shahzade:KillShahzade') == false){
            $GroupMessage = self::$Dt->LG->_('KillShahzade', array("{0}" =>  $Name));
            HL::SaveMessage($GroupMessage);
            R::GetSet(true,'GamePl:role_Shahzade:KillShahzade');
            return true;
        }elseif($UserDetial['user_role'] == "role_Shahzade" && R::CheckExit('GamePl:role_Shahzade:KillShahzade')){
            //Spoiled_Rich_Brat » شاهزاده باشیو دو شب اعدامت کنن
            HL::SavePlayerAchivment($UserDetial['user_id'],'Spoiled_Rich_Brat');
        }elseif($UserDetial['user_role'] == "role_BlackKnight"){
            if(R::CheckExit('GamePl:BlackVoteNo')){
                $Count =  (int) R::Get('GamePl:BlackVoteNo') - 1;
                if($Count <= 0 ){
                    R::Del('GamePl:BlackVoteNo');
                }
                $GroupMessage = self::$Dt->LG->_('BlackKnightKillVote', array("{0}" => $Name));
                HL::SaveMessage($GroupMessage);
                R::GetSet($Count,'GamePl:BlackVoteNo');
                return true;
            }
        }
        if($UserDetial['user_role'] == "role_feriga" && !R::CheckExit('GamePl:role_feriga:hilled')){
            $GroupMessage = self::$Dt->LG->_('ViegoHillFeriga', array("{0}" =>  $Name));
            HL::SaveMessage($GroupMessage);
            R::GetSet(true,'GamePl:role_feriga:hilled');
            return true;
        }

        if($UserDetial['user_role'] == "role_viego" && !R::CheckExit('GamePl:role_viego:hilled')){
            $GroupMessage = self::$Dt->LG->_('FerigaNotKillInVote', array("{0}" =>  $Name));
            HL::SaveMessage($GroupMessage);
            R::GetSet(true,'GamePl:role_viego:hilled');
            return true;
        }

        if($UserDetial['user_role'] == "role_monafeq"){
            $Array = array(
                "{0}" => $Name,
                "{1}" => self::$Dt->LG->_('user_role',array("{0}" => self::$Dt->LG->_($UserDetial['user_role']."_n")))
            );
            $GroupMessage = self::$Dt->LG->_('killed_user',$Array);
            HL::SaveMessage($GroupMessage);
            HL::SavePlayerAchivment($UserDetial['user_id'],'Masochist');
            HL::UserDead($UserDetial,'vote');
            HL::SaveVoteKillVote($ArrayM,$UserDetial);
            HL::SaveGameActivity($UserDetial ,'vote',['user_id' => 0 ,'fullname'=> 0 ]);
            HL::GamedEnd('monafeq');
        }

        if(R::CheckExit("GamePl:KhalifaSelectRole")){
                $USerID = R::Get("GamePl:KhalifaSelectRoleUserId");
                if((float) $UserDetial['user_id'] == (float) $USerID){
                    R::Del('GamePl:KhalifaSelectRoleAs');
                    R::Del('GamePl:KhalifaSelectRole');
                    $GroupMessage = self::$Dt->LG->_('VoteKillPlayer',array("{0}" => $Name));
                    HL::SaveMessage($GroupMessage);
                }
        }
        $Array = array(
            "{0}" => $Name,
            "{1}" => self::$Dt->LG->_('user_role',array("{0}" => self::$Dt->LG->_($UserDetial['user_role']."_n")))
        );


        $GroupMessage = self::$Dt->LG->_('killed_user',$Array);

        if(R::CheckExit('GamePl:role_Ruler:RulerOk')){
            R::GetSet(true,'GamePl:RulerOkSend');
            $GroupMessage = self::$Dt->LG->_('RulerKillPl',$Array);
        }

        if($UserDetial['user_role'] == "role_kalantar"){
            HL::HunterKill($GroupMessage,$UserDetial['user_id'],'vote');
            HL::UserDead($UserDetial,'vote');
            HL::SaveVoteKillVote($ArrayM,$UserDetial);
            HL::SaveGameActivity($UserDetial ,'vote',['user_id' => 0 ,'fullname'=> 0 ]);
            R::DelKey('GamePl:Selected:Vote:*');
            return true;
        }

        // در نقش پیشگو شب اول اعدام بشید
        if($UserDetial['user_role'] == "role_pishgo"){
            if(R::Get('GamePl:Night_no') == 0 ){
                HL::SavePlayerAchivment($UserDetial['user_id'],'I_See_a_Lack_of_Trust');
            }
        }
        HL::SaveMessage($GroupMessage);
        HL::UserDead($UserDetial,'vote');
        HL::SaveVoteKillVote($ArrayM,$UserDetial);
        HL::SaveGameActivity($UserDetial ,'vote',['user_id' => 0 ,'fullname'=> 0 ]);
        return true;
    }
    public static function CollectVote(){
        $Keys = R::Keys('GamePl:Selected:Vote:*'); // دریافت تمام داده های توی این پترن
        if(!$Keys){
            return false;
        }
        $in = []; // لیست چک
        $Selected = []; //داده هامون
        foreach ($Keys as $row){
            $Ex = explode(':',$row);
            $Keys = "{$Ex['1']}:{$Ex['2']}:{$Ex['3']}:{$Ex['4']}";
            $Get = R::LRange(0,-1,$Keys);
            // چک میکنیم ای دی کاربر از قبل توی لیست چک شده های ما موجود نباشه، البته الزامی هم نیست ولی برای اطمینان اضافه میکنیم
            if(!in_array($Ex['4'],$in)){
                // ای دی کاربر رو به لیست چک شده هامون اضافه میکنیم
                array_push($in,$Ex['4']);
                // بعد داده های مورد نیازمون رو اضافه میکنیم به داده های تاییدی
                array_push($Selected,['total' => count($Get) ,'user_id'=> $Ex['4']]);
            }
        }

        if(count($Selected) > 0){
            $columns = array_column($Selected, 'total');
            array_multisort($columns, SORT_DESC, $Selected);
            $max = 0; // بزرگترین عددمون
            $maxTotal = [];
            foreach ($Selected as $row){
                // چک میکنیم اگه عدد بزرگتر از ارایه max ما بود
                if($row['total'] >= $max){
                    $max = $row['total'];
                    // ای دی کاربر رو هم اضافه کن به عدد خروجی
                    array_push($maxTotal,$row['user_id']);

                }
            }
            if(count($maxTotal) > 1 || count($maxTotal) == 0){
                return ['state'=> false,'data'=> $Selected];
            }

            return ['user_id'=> $maxTotal['0'],'state'=> true,'data'=> $Selected];
        }


        return ['state'=> false,'data'=> $Selected];
    }

    public static function CheckVoteMessage(){
        $Message = R::LRange(0,-1,'GamePl:VoteMessage');
        R::Del('GamePl:VoteMessage');
        if($Message){
            $Implo = [];
            foreach ($Message as $row){
                array_push($Implo,$row);
            }


            if($Implo){
                if(R::Get('secret_vote') == "onr"){
                    $Msg = self::$Dt->LG->_('lynic_to', array("{0}" => R::Get('GamePl:VoteCount'), "{1}" => HL::_getCountPlayer()));
                    HL::SendMessage($Msg);
                    return true;
                }
                $Message = implode(PHP_EOL.PHP_EOL,$Implo);
                HL::SendMessage($Message);

                return true;
            }
        }

        return false;
    }
    public static function SendUserMessageDodge($user_id){
        $userMessage = self::$Dt->LG->_('DodgeYou');
        HL::SendMessage($userMessage,$user_id);
    }
    public static function CheckDodge($row){
        if(R::CheckExit('GamePl:role_lucifer:DodgeVote:'.$row['user_id'])) {
            $Lucifer = HL::_getPlayerByRole('role_lucifer');

            if ($Lucifer == false) {
                return false;
            }
            self::SendUserMessageDodge($row['user_id']);
            // اسم بازیکن رو با لینکش میگیریم
            $U_name = HL::ConvertName($row['user_id'],$row['fullname_game']);
            $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'DdgSlVt');
            $inline_keyboard = new InlineKeyboard(...$rows);
            $result =  Request::sendMessage([
                'chat_id' => $Lucifer['user_id'],
                'text' => self::$Dt->LG->_('DodgehowVote',array("{0}" => $U_name)),
                'reply_markup' => $inline_keyboard,
                'parse_mode' => 'HTML',
            ]);
            if($result->isOk()){
                R::GetSet($result->getResult()->getMessageId(),'GamePl:MessageNightSendDodgeVote:'.$Lucifer['user_id']);
            }

            return true;
        }

        return false;
    }

    public static function SendVoteCheck($user_id = false,$count = false){
        $Check = R::LRange(0,-1,'GamePl:SendVote');
        if($count){
            return count($Check);
        }
        if($Check){
            if(in_array($user_id,$Check)){
                return true;
            }
        }

        return false;
    }
    public static function SendVote(){




        if(R::CheckExit('GamePl:role_Solh:GroupInSolh')){
            return false;
        }
        if(R::CheckExit('GamePl:SharlatanINTabar')){
            return false;
        }


        $Players = HL::_getOnPlayers();
        $count = 0;
        $CountPlayer = count($Players);
        $checkAllSend = self::SendVoteCheck(false,true);

        if($CountPlayer == $checkAllSend){
            return false;
        }
        foreach ($Players  as $row){
            $count++;
            if($count == $CountPlayer){
                R::GetSet(true,'GamePl:CheckVoteSend');
            }

            if(R::CheckExit('GamePl:role_Ruler:RulerOk')){
                if($row['user_role'] !== "role_Ruler"){
                    continue;
                }
            }
            if(self::SendVoteCheck($row['user_id'])){
                continue;
            }

            R::rpush($row['user_id'],'GamePl:SendVote');


            $CheckDodge = self::CheckDodge($row);
            if($CheckDodge){
                continue;
            }
            $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'VoteSelect');
            $inline_keyboard = new InlineKeyboard(...$rows);
            $result =  Request::sendMessage([
                'chat_id' => $row['user_id'],
                'text' => self::$Dt->LG->_('howVote'),
                'reply_markup' => $inline_keyboard,
                'parse_mode' => 'HTML',
            ]);
            if($result->isOk()){
                R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
            }
        }


    }


}
