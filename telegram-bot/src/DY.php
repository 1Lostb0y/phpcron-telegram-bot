<?php

namespace phpcron\CronBot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;

class DY
{
    /**
     * Cron object
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

        self::LoverMessage();

        self::SendDayRole();

    }
    public static function LoverMessage(){

        if(R::Get('GamePl:Day_no') > 2 ){
            return false;
        }
        $data = R::Keys('GamePl:love:*');

        if($data) {
            foreach ($data as $key) {
                $ex =  explode(":",$key);
                $GetLover = R::Get("{$ex['1']}:{$ex['2']}:{$ex['3']}");
                if(R::CheckExit('GamePl:SendLoverMessage:'.$ex['3'])){
                    continue;
                }

                R::GetSet(true, 'GamePl:SendLoverMessage:'.$GetLover);
                R::GetSet(true, 'GamePl:SendLoverMessage:'.$ex['3']);

                HL::SaveGameActivity(['user_id' => $ex['3'] ,'fullname'=>  R::Get('GamePl:name:love:' . $ex['3']) ],'love',['user_id' => $GetLover ,'fullname'=>  R::Get('GamePl:name:love:' .$GetLover) ]);
                HL::SaveGameActivity(['user_id' => $GetLover ,'fullname'=>  R::Get('GamePl:name:love:' .$GetLover) ],'love',['user_id' => $ex['3'] ,'fullname'=>  R::Get('GamePl:name:love:' . $ex['3']) ]);

                $GameMode = R::Get('GamePl:gameModePlayer');

                $IDMsg = ($GameMode == "Romantic" ? "RomanticModeMessage" : "CupidChosen");
                $IDMsg2 = ($GameMode == "Romantic" ? "RomanticModeMessage" : "CupidChosen2");
                $LoveMessage = self::$Dt->LG->_($IDMsg,array("{0}" =>  R::Get('GamePl:name:love:' . $ex['3'])));
                HL::SendMessage($LoveMessage, $ex['3']);

                if(is_numeric($GetLover)) {

                    $LoverMessage2 = self::$Dt->LG->_($IDMsg2, array("{0}" =>R::Get('GamePl:name:love:' . $GetLover)));
                    HL::SendMessage($LoverMessage2, $GetLover);
                }

            }

        }

        return true;
    }

    public static function CheckDay(){

        if(R::CheckExit('GamePl:CheckDay')){
            return false;
        }
        R::GetSet(true,'GamePl:CheckDay');
        // چک کردن کنت
        self::CheckKent();
        // چک کردن تفنگدار
        self::CheckTofangdar();

        if(R::CheckExit('GamePl:HunterKill')){
            return false;
        }
        self::CheckPrincess();
        // چک کردن کاراگاه
        self::CheckKaragah();
        // چک کردن جاسوس
        self::CheckSpy();

        return true;
    }

    public static function CheckPrincess(){
        if(R::Get('GamePl:Night_no') <= 2)  return false;

        $Princess = HL::_getPlayerByRole('role_Princess');
        if(!$Princess) return false;
        if(!R::CheckExit('GamePl:Selected:'.$Princess['user_id'])){
            return false;
        }
        $selected = R::Get('GamePl:Selected:'.$Princess['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($selected,$Detial['fullname_game']);
        if($Detial['user_state'] !== 1){
            return false;
        }

        switch($Detial['user_role']){
            case 'role_Knight':
            case 'role_Qatel':
               if(SE::_s('EscapeKillerKnight') < HL::R(100)) {
                   $PrincessMessage = self::$Dt->LG->_('PrincessPrisonerVampireAndKnight',array("{0}" => $U_name));
                   HL::SendMessage($PrincessMessage,$Princess['user_id']);
                     $PlayerMessage = ($Detial['role_Qatel'] ? self::$Dt->LG->_('PrincessPrisonerKiller') : self::$Dt->LG->_('PrincessPrisonerKnight')  );
                     HL::SendMessage($PlayerMessage,$Detial['user_id']);
                   return  true;
               }
               HL::SendPrincessMessage($Detial,$Princess);
               return  true;
            break;
            case 'role_shekar':
            case 'role_Ruler':
                $PrincessMessage = self::$Dt->LG->_('PrincessPrisonerVampireAndKnight',array("{0}" => $U_name));
                HL::SendMessage($PrincessMessage,$Princess['user_id']);
                return  true;
            break;
            case 'role_Bloodthirsty':
                $VampireMessage = self::$Dt->LG->_("PrincessPrisonerVampireTeamFlee",array("{0}" => $U_name));
                HL::SendForVampireTeam($VampireMessage,$Detial['user_id']);
                $BloodMessage = self::$Dt->LG->_("PrincessPrisonerVampireTeamFleeForBlood");
                HL::SendMessage($BloodMessage,$Detial['user_id']);
                $PrincessMessage = self::$Dt->LG->_('PrincessPrisonerSuccess',array("{0}" => $U_name));
                HL::SendMessage($PrincessMessage,$Princess['user_id']);
                return  true;
            break;
            case 'role_Firefighter':
            case 'role_IceQueen':
                $PrincessMessage = self::$Dt->LG->_('PrincessPrisonerNotFind',array("{0}" => $U_name));
                HL::SendMessage($PrincessMessage,$Princess['user_id']);
                return  true;
           break;
            default:
                HL::SendPrincessMessage($Detial,$Princess);
                return  true;
            break;
        }

    }
    public static function CheckKent(){
        if(!R::CheckExit('GamePl:KentVampireConvert')){
            return false;
        }
        $kent = HL::_getPlayerByRole('role_kentvampire');
        if($kent == false){
            return false;
        }
        if($kent['user_state'] !== 1){
            return false;
        }
        if(!R::CheckExit('GamePl:Selected:'.$kent['user_id'])){
            return false;
        }
        $selected = R::Get('GamePl:Selected:'.$kent['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($selected,$Detial['fullname_game']);

        if($Detial['user_state'] !== 1){
            return false;
        }


        $GroupMessage = self::$Dt->LG->_('KentVampireKillPlayer',array("{0}" => $U_name, "{1}" => self::$Dt->LG->_($Detial['user_role']."_n") ));
        HL::SaveMessage($GroupMessage);
        HL::UserDead($Detial,'Kent');
        HL::SaveGameActivity($kent,'KentKill',$Detial);

        return true;
    }

    public static function CheckSpy(){
        $Spy = HL::_getPlayerByRole('role_Spy');
        if($Spy == false){
            return false;
        }
        if($Spy['user_state'] !== 1){
            return false;
        }
        if(!R::CheckExit('GamePl:Selected:'.$Spy['user_id'])){
            return false;
        }

        $selected = R::Get('GamePl:Selected:'.$Spy['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($selected,$Detial['fullname_game']);

        switch ($Detial['user_role']){
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_Wolfx':
            case 'role_WolfAlpha':
            case 'role_Qatel':
            case 'role_Archer':
            case 'role_shekar':
            case 'role_kalantar':
            case 'role_tofangdar':
            case 'role_Firefighter':
            case 'role_IceQueen':
            case 'role_Vampire':
            case 'role_Bloodthirsty':
            case 'role_Knight':
                $SpyMessage = self::$Dt->LG->_('SpySeeMessage',array("{0}" =>$U_name));
                HL::SendMessage($SpyMessage,$Spy['user_id']);
                return true;
                break;
            case 'role_forestQueen':
                if(R::CheckExit('GamePl:role_forestQueen:AlphaDead')){
                    $SpyMessage = self::$Dt->LG->_('SpySeeMessage',array("{0}" =>$U_name));
                    HL::SendMessage($SpyMessage,$Spy['user_id']);
                    return true;
                }
                $SpyMessage = self::$Dt->LG->_('SpySeeMessageNo',array("{0}" =>$U_name));
                HL::SendMessage($SpyMessage,$Spy['user_id']);
                return true;
                break;
            default:
                $SpyMessage = self::$Dt->LG->_('SpySeeMessageNo',array("{0}" =>$U_name));
                HL::SendMessage($SpyMessage,$Spy['user_id']);
                return true;
                break;
        }

        return false;
    }
    public static function CheckTofangdar(){

        $Tofangdar = HL::_getPlayerByRole('role_tofangdar');
        // اگر قاتل نبود،مسلما باید بیخیال بشیم
        if($Tofangdar == false){
            return false;
        }
        // اگر قاتل مرده بود بازم باید بیخیال بشیم مسلما
        if($Tofangdar['user_state'] !== 1){
            return false;
        }
        // اگر قاتل انتخابی نکرد بازم لزومی نداره چک کنیم
        if(!R::CheckExit('GamePl:Selected:'.$Tofangdar['user_id'])){
            return false;
        }


        // خب حالا مطمعن شدیم تفنگدار هم هست،هم زندس، هم انتخابشو انجام داده حالا چک کردن رو شروع میکنیم
        $selected = R::Get('GamePl:Selected:'.$Tofangdar['user_id']);
        $Detial = HL::_getPlayer($selected);

        // اگه مرده بود  طرف چک کردنو بیخیال میشیم
        if($Detial['user_state'] !== 1){
            return false;
        }
        // اسم تفنگدار
        $TofangdarName = HL::ConvertName($Tofangdar['user_id'],$Tofangdar['fullname_game']);

        // اسم بازیکن رو با لینکش میگیریم
        $U_name = HL::ConvertName($selected,$Detial['fullname_game']);

        switch ($Detial['user_role']){
            case 'role_rishSefid':
                $GroupMessage = self::$Dt->LG->_('GunnerShotWiseElder',array("{0}" => $TofangdarName,"{1}"=> $U_name));
                HL::SaveMessage($GroupMessage);
                HL::UserDead($selected,'shot');
                HL::SaveGameActivity($Detial,'shot',$Tofangdar);
                // تفنگدار تبدیل به روستایی میشه
                HL::ConvertPlayer($Tofangdar['user_id'],'role_rosta');
                $TofangdarMessage = self::$Dt->LG->_('role_rosta');
                HL::SendMessage($TofangdarMessage,$Tofangdar['user_id']);
                R::GetSet((R::Get('GamePl:GunnerBult') - 1),'GamePl:GunnerBult');
                return true;
                break;
            case 'role_betaWolf':
                $GroupMessage = self::$Dt->LG->_("betaWolf_Tofangdar",array("{0}" => $TofangdarName,"{1}" => $U_name));
                HL::SaveMessage($GroupMessage);
                HL::UserDead($selected,'shot');
                HL::SaveGameActivity($Detial,'shot',$Tofangdar);
                HL::UserDead($Tofangdar,'BetaWolf');
                HL::SaveGameActivity($Tofangdar,'BetaWolf',$Detial);
                return true;
             break;
            default:
                $UseRole = self::$Dt->LG->_($Detial['user_role']."_n");
                $GroupMessage = self::$Dt->LG->_('DefaultShot',array("{0}" =>$TofangdarName,"{1}"=> $U_name,"{2}"=> self::$Dt->LG->_('user_role',array("{0}" =>$UseRole))));
                R::GetSet((R::Get('GamePl:GunnerBult') - 1),'GamePl:GunnerBult');
                if($Detial['user_role'] == "role_kalantar"){
                    HL::HunterKill($GroupMessage,$Detial['user_id'],'shot');
                    HL::UserDead($selected,'shot');
                    HL::SaveGameActivity($Detial,'shot',$Tofangdar);
                    R::Del('GamePl:Selected:'.$Tofangdar['user_id']);
                    return true;
                }
                HL::SaveMessage($GroupMessage);
                HL::UserDead($selected,'shot');
                HL::SaveGameActivity($Detial,'shot',$Tofangdar);
                return true;
                break;
        }
    }

    public static function CheckKaragah(){
        $Karagah = HL::_getPlayerByRole('role_karagah');
        // اگر قاتل نبود،مسلما باید بیخیال بشیم
        if($Karagah == false){
            return false;
        }
        // اگر قاتل مرده بود بازم باید بیخیال بشیم مسلما
        if($Karagah['user_state'] !== 1){
            return false;
        }
        // اگر قاتل انتخابی نکرد بازم لزومی نداره چک کنیم
        if(!R::CheckExit('GamePl:Selected:'.$Karagah['user_id'])){
            return false;
        }

        // خب حالا مطمعن شدیم کاراگاه هم هست،هم زندس، هم انتخابشو انجام داده حالا چک کردن رو شروع میکنیم
        $selected = R::Get('GamePl:Selected:'.$Karagah['user_id']);
        $Detial = HL::_getPlayer($selected);



        // اسم بازیکن رو با لینکش میگیریم
        $U_name = HL::ConvertName($selected,$Detial['fullname_game']);

        if(R::CheckExit('GamePl:HoneyUser:'.$Detial['user_id'])){
            $HoneyChangeRole = "role_WolfGorgine_n";
        }
        $UserRole = $Detial['user_role']."_n";
        $KaragahMessage = self::$Dt->LG->_('DetectiveSnoop',array("{0}" =>$U_name,"{1}" => self::$Dt->LG->_($HoneyChangeRole ?? $UserRole )));
        HL::SendMessage($KaragahMessage,$Karagah['user_id']);

        return true;
    }

    public static function UserInConvert($user_id){
        $Botanist = HL::_getPlayerByRole('role_Botanist');
        if(!$Botanist){
            return false;
        }
        if($Botanist['user_state'] !== 1){
            return false;
        }

        if(R::Get('GamePl:BittanPlayer') == $user_id || R::Get('GamePl:EnchanterBittanPlayer') == $user_id ){
            R::GetSet($user_id,'GamePl:role_Botanist:bittaned');
            R::GetSet('wolf','GamePl:role_Botanist:bittaned:for');
            $inline_keyboard = new InlineKeyboard([
                ['text' => self::$Dt->LG->_('Btn_okSend'), 'callback_data' => "DaySelect_SendBittenYes/" . self::$Dt->chat_id],
                ['text' => self::$Dt->LG->_('Btn_NotOk'), 'callback_data' => "DaySelect_SendBittenNo/" . self::$Dt->chat_id]
            ]);
            $result = Request::sendMessage([
                'chat_id' => $user_id,
                'text' => self::$Dt->LG->_('UserBittenByWolf'),
                'parse_mode' => 'HTML',
                'reply_markup' => $inline_keyboard,
            ]);
            if($result->isOk()) {
                R::rpush($result->getResult()->getMessageId()."_".$user_id,'GamePl:EditMarkup');
            }
            return true;
        }
        if(R::Get('GamePl:VampireBitten') == $user_id){
            R::GetSet($user_id,'GamePl:role_Botanist:bittaned');
            R::GetSet('vampire','GamePl:role_Botanist:bittaned:for');
            $inline_keyboard = new InlineKeyboard([
                ['text' => self::$Dt->LG->_('Btn_okSend'), 'callback_data' => "DaySelect_SendBittenYes/" . self::$Dt->chat_id],
                ['text' => self::$Dt->LG->_('Btn_NotOk'), 'callback_data' => "DaySelect_SendBittenNo/" . self::$Dt->chat_id]
            ]);
            $result = Request::sendMessage([
                'chat_id' => $user_id,
                'text' => self::$Dt->LG->_('UserBittenVampire'),
                'parse_mode' => 'HTML',
                'reply_markup' => $inline_keyboard,
            ]);
            if($result->isOk()) {
                R::rpush($result->getResult()->getMessageId()."_".$user_id,'GamePl:EditMarkup');
            }
            return true;
        }

        return false;
    }

    public static function SendUserMessageDodge($user_id){
        $userMessage = self::$Dt->LG->_('DodgeYou');
        HL::SendMessage($userMessage,$user_id);
    }
    public static function CheckDodge($row){
        if(R::CheckExit('GamePl:role_lucifer:DodgeDay:'.$row['user_id'])){
            $Lucifer = HL::_getPlayerByRole('role_lucifer');

            if($Lucifer == false){
                return false;
            }
            self::SendUserMessageDodge($row['user_id']);
            switch ($row['user_role']){
                case 'role_tofangdar':
                    $rows = HL::GetPlayerNonKeyboard([], 'DySlDodge_Gunner');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $Lucifer['user_id'],
                        'text' => self::$Dt->LG->_('AskShoot',array("{0}" =>R::Get('GamePl:GunnerBult'))),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$Lucifer['user_id'],'GamePl:MessageNightSend');
                    }
                    return true;
                    break;
                case 'role_karagah':
                    $rows = HL::GetPlayerNonKeyboard([], 'DySlDodge_Karagah');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $Lucifer['user_id'],
                        'text' => self::$Dt->LG->_('howEstelamIs'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$Lucifer['user_id'],'GamePl:MessageNightSend');
                    }
                    return true;
                    break;
                    case 'role_Princess':
                    $rows = HL::GetPlayerNonKeyboard([], 'DySlDodge_Princess');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $Lucifer['user_id'],
                        'text' => self::$Dt->LG->_('AskPrincess'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$Lucifer['user_id'],'GamePl:MessageNightSend');
                    }
                    return true;
                    break;
                case 'role_Spy':
                    $rows = HL::GetPlayerNonKeyboard([], 'DySlDodge_Spy');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $Lucifer['user_id'],
                        'text' => self::$Dt->LG->_('SpyAsk'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$Lucifer['user_id'],'GamePl:MessageNightSend');
                    }
                    return true;
                    break;
                    case 'role_kentvampire':
                    $rows = HL::GetPlayerNonKeyboard([], 'DySlDodge_KentVampire');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $Lucifer['user_id'],
                        'text' => self::$Dt->LG->_('AskDayKentVampire'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$Lucifer['user_id'],'GamePl:MessageNightSend');
                    }
                    return true;
                    break;
                default:
                    return false;
                    break;
            }

        }

        return false;
    }
    public static function SendDayCheck($user_id = false,$count = false){
        $Check = R::LRange(0,-1,'GamePl:SendDayRole');
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

    public static function SendDayRole(){


        $Players = HL::_getPlayerINRole(['role_Princess','role_kentvampire','role_isra','role_Solh','role_tofangdar','role_Kadkhoda','role_Ruler','role_karagah','role_Spy','role_trouble','role_Ahangar','role_KhabGozar']);

        $CountPlayer = count($Players);
        $checkCount =  self::SendDayCheck(false,true);
        if($checkCount == $CountPlayer){
            return false;
        }
        foreach ($Players  as $row){
            if(R::CheckExit('GamePl:PrincessPrisoner:'.$row['user_id'])){
                continue;
            }
            if(self::SendDayCheck($row['user_id'])){
                continue;
            }
            self::UserInConvert($row['user_id']);
            if( R::CheckExit('GamePl:NotSend_'.$row['user_role']) == true || R::CheckExit('GamePl:'.$row['user_role'].":notSend") == true ){
                continue;
            }

            R::rpush($row['user_id'],'GamePl:SendDayRole');
            $CheckDodge = self::CheckDodge($row);
            if($CheckDodge){
                continue;
            }
            switch ($row['user_role']){
                case 'role_Solh':
                    $inline_keyboard = new InlineKeyboard([
                        ['text' => self::$Dt->LG->_('solh_btn'), 'callback_data' => "DaySelect_Solh/" . self::$Dt->chat_id]
                    ]);
                    $result = Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('solh_L'),
                        'parse_mode' => 'HTML',
                        'reply_markup' => $inline_keyboard,
                    ]);
                    if($result->isOk()) {
                        R::GetSet($result->getResult()->getMessageId(), 'GamePl:role_solh:Message_id:' . $row['user_id']);
                        R::GetSet(true, 'GamePl:NotSend_role_Solh');
                    }
                    break;
                case 'role_tofangdar':
                    if(R::Get('GamePl:GunnerBult') <= 0){
                        continue 2;
                    }
                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'DaySelect_Tofangdar');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('AskShoot',array("{0}" =>R::Get('GamePl:GunnerBult'))),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                    }
                    break;
                case 'role_kentvampire':
                    if(!R::CheckExit('GamePl:KentVampireConvert')){
                        continue 2;
                    }
                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'DaySelect_KentVampire');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('AskDayKentVampire'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                     if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                     }
                    break;
                case 'role_Kadkhoda':
                    $inline_keyboard = new InlineKeyboard([
                        ['text' => self::$Dt->LG->_('Kadkhoda_btn'), 'callback_data' => "DaySelect_Kadkhoda/" . self::$Dt->chat_id]
                    ]);
                    $result = Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('Kadkhoda_l'),
                        'parse_mode' => 'HTML',
                        'reply_markup' => $inline_keyboard,
                    ]);
                    if($result->isOk()) {
                        R::GetSet($result->getResult()->getMessageId(), 'GamePl:role_Kadkhoda:Message_id:' . $row['user_id']);
                        R::GetSet(true, 'GamePl:NotSend_role_Kadkhoda');
                    }
                    break;
                case 'role_Ruler':
                    $inline_keyboard = new InlineKeyboard([
                        ['text' => self::$Dt->LG->_('RulerButton'), 'callback_data' => "DaySelect_Ruler/" . self::$Dt->chat_id]
                    ]);
                    $result = Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('RulerAsk'),
                        'parse_mode' => 'HTML',
                        'reply_markup' => $inline_keyboard,
                    ]);
                    if($result->isOk()) {
                        R::GetSet($result->getResult()->getMessageId(), 'GamePl:role_Ruler:Message_id:' . $row['user_id']);
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:EditMarkup');
                    }
                    break;

                case 'role_karagah':
                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'DaySelect_Karagah');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('howEstelamIs'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                    }
                    break;
                case 'role_Princess':
                    if(R::Get('GamePl:Night_no') <= 2)  continue 2;

                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'DaySelect_Princess');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('AskPrincess'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                    }
                    break;
                case 'role_Spy':
                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'DaySelect_Spy');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('SpyAsk'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                    }
                    break;
                case 'role_trouble':
                    $inline_keyboard = new InlineKeyboard([
                        ['text' => self::$Dt->LG->_('troubleBtnYes'), 'callback_data' => "DaySelect_trouble_yes/" . self::$Dt->chat_id],
                        ['text' => self::$Dt->LG->_('troubleBtnNo'), 'callback_data' => "DaySelect_trouble_no/" . self::$Dt->chat_id]
                    ]);
                    $result = Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('Asktrouble'),
                        'parse_mode' => 'HTML',
                        'reply_markup' => $inline_keyboard,
                    ]);
                    if($result->isOk()) {
                        R::GetSet($result->getResult()->getMessageId(), 'GamePl:role_trouble:Message_id:' . $row['user_id']);
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:EditMarkup');
                    }
                    break;
                case 'role_Ahangar':
                    $inline_keyboard = new InlineKeyboard([
                        ['text' => self::$Dt->LG->_('ahangar_btn'), 'callback_data' => "DaySelect_Ahangar_no/" . self::$Dt->chat_id],
                        ['text' => self::$Dt->LG->_('ahangar_btnY'), 'callback_data' => "DaySelect_Ahangar_Yes/" . self::$Dt->chat_id]
                    ]);
                    $result = Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('ahangar_L'),
                        'parse_mode' => 'HTML',
                        'reply_markup' => $inline_keyboard,
                    ]);
                    if($result->isOk()) {
                        R::GetSet($result->getResult()->getMessageId(), 'GamePl:role_Ahangar:Message_id:' . $row['user_id']);
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:EditMarkup');
                    }
                    break;
                    case 'role_isra':
                    $inline_keyboard = new InlineKeyboard([
                        ['text' => self::$Dt->LG->_('isra_in'), 'callback_data' => "DaySelect_isra_no/" . self::$Dt->chat_id],
                        ['text' => self::$Dt->LG->_('isra_inY'), 'callback_data' => "DaySelect_isra_Yes/" . self::$Dt->chat_id]
                    ]);
                    $result = Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('Ask_Israhelp'),
                        'parse_mode' => 'HTML',
                        'reply_markup' => $inline_keyboard,
                    ]);
                    if($result->isOk()) {
                        R::GetSet($result->getResult()->getMessageId(), 'GamePl:role_isra:Message_id:' . $row['user_id']);
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:EditMarkup');
                    }
                    break;

                case 'role_KhabGozar':
                    $inline_keyboard = new InlineKeyboard([
                        ['text' => self::$Dt->LG->_('KHABGOZAR_BTN'), 'callback_data' => "DaySelect_Khabgozar_Yes/" . self::$Dt->chat_id],
                        ['text' => self::$Dt->LG->_('KHABGOZAR_BTN_N'), 'callback_data' => "DaySelect_Khabgozar_No/" . self::$Dt->chat_id]
                    ]);
                    $result = Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('KHABGOZAR_l'),
                        'parse_mode' => 'HTML',
                        'reply_markup' => $inline_keyboard,
                    ]);
                    if($result->isOk()) {
                        R::GetSet($result->getResult()->getMessageId(), 'GamePl:role_KhabGozar:Message_id:' . $row['user_id']);
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:EditMarkup');
                    }
                    break;
            }
        }


    }


}
