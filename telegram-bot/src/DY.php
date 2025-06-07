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

        if(R::CheckExit('GamePl:KhalifaSelectRole')) {
            if ((int)R::Get('GamePl:Day_no') == (int)R::Get('GamePl:KhalifaSelectRole')) {
               $GroupMessage = self::$Dt->LG->_('NotFindKhalifaRoleGroup');
               HL::SaveMessage($GroupMessage);
                HL::GamedEnd('monafeq');
                return false;
            }
        }
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


        /*
        if(R::CheckExit('GamePl:vgWin')){
            HL::GamedEnd('vf');
            return false;
        }
        */

        self::CheckMidWolf();
        self::CheckFeriga();

        R::GetSet(true,'GamePl:CheckDay');
        self::CheckSharlatan();
        self::CheckShahzade();
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
        self::CheckBlackKnight();
        // چک کردن جاسوس
        self::CheckSpy();
        self::CheckMargita();
        self::CeckMados();
        return true;
    }

    public static function CheckMidWolf(){

        // شب اول چک نکن
        if(R::Get('GamePl:Day_no') == 0){
            return false;
        }

        if(R::Get('GamePl:Day_no') !== R::Get('GamePl:midwolfSendDay')){
            return false;
        }
        $MidWolf = HL::_getPlayerByRole('role_midwolf');
        if(!$MidWolf){
            return false;
        }

        R::GetSet(R::Get('GamePl:midwolfSendDay') + 1,'GamePl:midwolfSendDay');
        // اگر مید ولف مرده بود بازم باید بیخیال بشیم مسلما
        if($MidWolf['user_state'] !== 1){
            return false;
        }
        if(R::CheckExit('GamePl:Selected:'.$MidWolf['user_id']) == false){
            return false;
        }

        $selected = R::Get('GamePl:Selected:'.$MidWolf['user_id']);

        $MidWolfName = HL::ConvertName($MidWolf['user_id'],$MidWolf['fullname_game']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);

        switch ($Detial['user_role']){
            case 'role_WolfJadogar':
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_Wolfx':
            case 'role_WolfAlpha':
            case 'role_Honey':
            case 'role_enchanter':
            case 'role_WhiteWolf':
            case 'role_forestQueen':
            case 'role_betaWolf':
                $MidWolfMessage = self::$Dt->LG->_('MidWolfWhenAttakWolf',array("{0}" => $U_name));
                return HL::SendMessage($MidWolfMessage,$MidWolf['user_id']);
                break;
            default:
                if(HL::R(100) < SE::_s('CovertMidWolf')){
                    // Player Messsage
                    $PlayerMessageBitten = self::$Dt->LG->_('PlayerMessageWhenMidWolfBitten', array(
                        "{0}" => $MidWolfName
                    ));
                    HL::SendMessage($PlayerMessageBitten,$Detial['user_id']);

                    $MidWolfMessage =  self::$Dt->LG->_('MidwolfWhenBittenPlayer', array(
                        "{0}" => $U_name
                    ));
                    HL::SendMessage($MidWolfMessage,$MidWolf['user_id']);

                    // Wolf Message
                    $WolfMsg = self::$Dt->LG->_('WolfMessageWhenMidBitten',array(
                        "{0}" => $U_name
                    ));
                    HL::SendForWolfTeam($WolfMsg,[$MidWolf['user_id']]);

                    HL::BittanPlayerMidnight($selected);

                    return false;
                }


                // Group Message
                $GroupMessage = self::$Dt->LG->_('MidWolfAttackPlayerGroupMessage',array(
                    "{0}" => $U_name,
                    "{1}" => self::$Dt->LG->_($Detial['user_role']."_n")
                ));

                HL::SaveMessage($GroupMessage);
                // Player Message
                $MsgUser = self::$Dt->LG->_('eat_you');
                HL::SendMessage($MsgUser,$Detial['user_id'],'eat_wolf');
                HL::UserDead($Detial,'eat');
                HL::SaveGameActivity($Detial,'eat',$MidWolf);

                return true;
                break;
        }

    }

    public static function CheckMargita(){
        if(R::Get('GamePl:Day_no') !== R::Get('GamePl:MargitaSendDay')){
            return false;
        }
        $Margita = HL::_getPlayerByRole('role_Margita');
        if($Margita == false){
            return false;
        }
        R::GetSet(R::Get('GamePl:MargitaSendDay') + 2,'GamePl:MargitaSendDay');
        if($Margita['user_state'] !== 1){
            return false;
        }
        if(!R::CheckExit('GamePl:Selected:'.$Margita['user_id'])){
            return false;
        }
        $MargitaName = HL::ConvertName($Margita['user_id'],$Margita['fullname_game']);

        $selected = R::Get('GamePl:Selected:'.$Margita['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($selected,$Detial['fullname_game']);

        if($Detial['user_state'] !== 1){
            return false;
        }

        switch ($Detial['user_role']){
            case 'role_betaWolf':
                $KillerMessage = self::$Dt->LG->_('KillPlMessage');
                HL::SendMessage($KillerMessage,$Margita['user_id']);
                $GroupMessage =  self::$Dt->LG->_('KillPLMessageGroup',array("{0}" => $MargitaName ,"{1}" => self::$Dt->LG->_($Margita['user_role']."_n")));
                HL::SaveMessage($GroupMessage);
                HL::UserDead($Margita,'betawolf');

                return false;
             break;
            case 'role_Joker':
                $MargitaMsg = self::$Dt->LG->_('GuardJokerMArgita',array("{0}" => $U_name));
                HL::SendMessage($MargitaMsg,$Margita['user_id']);
                $PlayerMsg = self::$Dt->LG->_('JokerMessageMargita');
                HL::SendMessage($PlayerMsg,$Detial['user_id']);
                return false;
           break;
            default:
                $PlayerMsg = self::$Dt->LG->_('MargitaKillPlayer');
                HL::SendMessage($PlayerMsg,$Detial['user_id']);
                $GroupMsg = self::$Dt->LG->_('GroupMessageMArgita',array("{0}" => $U_name,"{1}" => self::$Dt->LG->_($Detial['user_role']."_n")));
                HL::SaveMessage($GroupMsg);
                HL::UserDead($Detial,'Margita');
                HL::SaveGameActivity($Detial,'Margita',$Margita);
                  return true;
            break;
        }


        return true;

    }

    public static function CheckShahzade(){

        if(!R::CheckExit('GamePl:MargaritaKilled')){
            return false;
        }

        $shahzade = HL::_getPlayerByRole('role_Shahzade');
        if($shahzade == false){
            return false;
        }
        if($shahzade['user_state'] !== 1){
            return false;
        }

        if(!R::CheckExit('GamePl:Selected:'.$shahzade['user_id'])){
            return false;
        }

        $ShahzadeName = HL::ConvertName($shahzade['user_id'],$shahzade['fullname_game']);

        $selected = R::Get('GamePl:Selected:'.$shahzade['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($selected,$Detial['fullname_game']);

        if($Detial['user_state'] !== 1){

            return false;
        }

        switch ($Detial['user_role']){
            default:

                if(HL::R(100) <= 70){

                    $PlayerMessage  = self::$Dt->LG->_('PlayerMessageKillShazade');
                    HL::SendMessage($PlayerMessage,$Detial['user_id']);

                    $ShahzadeMessage = self::$Dt->LG->_('ShahzadeKillPlayerMessage',array(
                        "{0}" => $U_name,
                    ));
                    HL::SendMessage($ShahzadeMessage,$shahzade['user_id']);

                    $GroupMessage  = self::$Dt->LG->_('GroupMessageKillPlayerShahzade',array(
                        "{0}" => $U_name,
                        "{1}" => self::$Dt->LG->_($Detial['user_role']."_n")
                    ));
                    HL::SaveMessage($GroupMessage);
                    HL::UserDead($Detial,'shahzade');
                    HL::SaveGameActivity($Detial,'shahzade',$shahzade);
                    return true;
                }

                $ShahzadeMessage  = self::$Dt->LG->_('ShahzadeMessageNoKillPlayer',array(
                    "{0}" => $U_name,
                ));
                HL::SendMessage($ShahzadeMessage,$shahzade['user_id']);
            break;
        }


        return true;

    }

    public static function CheckFeriga(){
        $feriga = HL::_getPlayerByRole('role_feriga');

        if($feriga == false){
            return false;
        }


        // اگر شیطان مرده بود بازم باید بیخیال بشیم مسلما
        if($feriga['user_state'] !== 1){

            return false;
        }
        if(R::CheckExit('GamePl:Selected:'.$feriga['user_id']) == false){
            return false;
        }
        $selected = R::Get('GamePl:Selected:'.$feriga['user_id']);
        $Detial = HL::_getPlayer($selected);
        if($Detial['user_state'] !== 1){
            return false;
        }
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);
        $CowName = HL::ConvertName($feriga['user_id'],$feriga['fullname_game']);



        /*
        if($Detial['user_role'] == 'role_viego'){
            R::GetSet(true,'GamePl:vgWin');
            return false;
        }
        */


        $GroupMessage = self::$Dt->LG->_('FerigaKillPlayer',array("{0}" => $U_name,"{1}" =>  self::$Dt->LG->_('user_role',array("{0}"=> self::$Dt->LG->_($Detial['user_role']."_n")))));
        HL::SaveMessage($GroupMessage);
        HL::UserDead($Detial,'kill');
        HL::SaveGameActivity($Detial,'kill',$feriga);
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
                     $PlayerMessage = ($Detial['user_role'] == "role_Qatel" ? self::$Dt->LG->_('PrincessPrisonerKiller') : self::$Dt->LG->_('PrincessPrisonerKnight')  );
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
    public static function CheckBlackKnight(){
        $Black = HL::_getPlayerByRole('role_BlackKnight');
        if($Black == false){
            return false;
        }
        if($Black['user_state'] !== 1){
            return false;
        }
        if(!R::CheckExit('GamePl:Selected:'.$Black['user_id'])){
            return false;
        }

        $selected = R::Get('GamePl:Selected:'.$Black['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($selected,$Detial['fullname_game']);

        if(R::CheckExit('GamePl:role_angel:AngelIn:'.$Detial['user_id'])){
            $CowAngelBlocked = self::$Dt->LG->_('CowHiler',array("{0}" =>$U_name));
            HL::SendMessage($CowAngelBlocked,$Black['user_id']);
            $PlayerMessage = self::$Dt->LG->_('BlackKnightDPlayerAngelMessagePL');
            HL::SendMessage($PlayerMessage,$Detial['user_id']);
            $AngelId = R::Get('GamePl:role_angel:AngelIn:'.$Detial['user_id']);
            $AngelMessage =  self::$Dt->LG->_('BlackKnightDPlayerAngelMessageANG',array("{0}" =>$U_name));
            HL::SendMessage($AngelMessage,$AngelId);
            return true;
        }

        $Groupsg = self::$Dt->LG->_('BlackKnightDeadPlayerGroup',array("{0}" => $U_name,"{1}" => self::$Dt->LG->_($Detial['user_role']."_n")));
        HL::SaveMessage($Groupsg);
        $PLayerMsg = self::$Dt->LG->_('BlackKnightDeadPlayerMessage');
        HL::SendMessage($PLayerMsg,$Detial['user_id']);
        HL::UserDead($Detial,'Black');
        HL::SaveGameActivity($Detial,'Black',$Black);
        return true;
    }

    public static function CeckMados(){
        $Madosa = HL::_getPlayerByRole('role_Madosa');
        if($Madosa == false){
            return false;
        }
        if($Madosa['user_state'] !== 1){
            return false;
        }
        if(!R::CheckExit('GamePl:Selected:'.$Madosa['user_id'])){
            return false;
        }
        $selected = R::Get('GamePl:Selected:'.$Madosa['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($selected,$Detial['fullname_game']);

        $GroupMessage = self::$Dt->LG->_('MadosaKillDay',array("{0}" => $U_name))." ".self::$Dt->LG->_('user_role',array("{0}" => self::$Dt->LG->_($Detial['user_role']."_n")));
        HL::SaveMessage($GroupMessage);
        HL::UserDead($Detial,'Madosa');
        HL::SaveGameActivity($Detial,'Madosa',$Madosa);
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
        if($Detial['user_role'] == "role_Wolfx"){
            $Detial['user_role'] = "role_rosta";
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
                case 'role_Madosa':
                    $rows = HL::GetPlayerNonKeyboard([$Lucifer['user_id']], 'DySlDodge_madosa');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $Lucifer['user_id'],
                        'text' => self::$Dt->LG->_('MadosaAskDay'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$Lucifer['user_id'],'GamePl:MessageNightSend');
                    }
                    break;
                case 'role_karagah':
                    $rows = HL::GetPlayerNonKeyboard([$Lucifer['user_id']], 'DySlDodge_Karagah');
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
                case 'role_BlackKnight':
                    $rows = HL::GetPlayerNonKeyboard([$Lucifer['user_id']], 'DySlDodge_BlackKnight');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $Lucifer['user_id'],
                        'text' => self::$Dt->LG->_('BlackKnightAsk'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$Lucifer['user_id'],'GamePl:MessageNightSend');
                    }
                    break;
                case 'role_Margita':
                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'DySlDodge_Margita');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $Lucifer['user_id'],
                        'text' => self::$Dt->LG->_('AskMargita'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$Lucifer['user_id'],'GamePl:MessageNightSend');
                    }
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

    public static function CheckSharlatan(){
        if(R::CheckExit('GamePl:SharlatanInTofan')){
            if(R::Get('GamePl:SharlatanInTofan') == R::Get('GamePl:Day_no')){
               $RandomRole =  HL::GetRoleRandom(['role_Qatel','role_Archer','role_Sharlatan','role_hilda']);
               if($RandomRole){
                   $U_name = HL::ConvertName($RandomRole['user_id'],$RandomRole['fullname_game']);
                   $GroupMessage = self::$Dt->LG->_('SharlatanTofanMessage',array("{0}" => $U_name,"{1}" => self::$Dt->LG->_($RandomRole['user_role']."_n")));
                   HL::SaveMessage($GroupMessage);
                   HL::UserDead($RandomRole['user_id'],'Sharlatan');
                   $Sharlatan = HL::_getPlayerByRole('role_Sharlatan');
                   if($Sharlatan) {
                       HL::SaveGameActivity($RandomRole, 'Sharlatan', $Sharlatan);
                   }
                   R::GetSet(3,'timer');
                   R::GetSet(((int) R::Get('GamePl:SharlatanTofan')) - 1 , 'GamePl:SharlatanTofan');
                   return true;
               }

            }
        }
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


        $Players = HL::_getPlayerINRole(['role_Princess','role_feriga','role_Shahzade','role_midwolf','role_BlackKnight','role_Sharlatan','role_Margita','role_Madosa','role_kentvampire','role_isra','role_Solh','role_tofangdar','role_Kadkhoda','role_Ruler','role_karagah','role_Spy','role_trouble','role_Ahangar','role_KhabGozar']);

        $CountPlayer = count($Players);
        $checkCount =  self::SendDayCheck(false,true);
        if($checkCount == $CountPlayer){
            return false;
        }
        foreach ($Players  as $row){

            if(R::CheckExit('GamePl:PrincessPrisoner:'.$row['user_id'])){
                continue;
            }

            if(R::CheckExit('GamePl:SharlatanInTofan')){
                if(R::Get('GamePl:SharlatanInTofan') == R::Get('GamePl:Day_no')){
                   continue;
                }
            }

            if(self::SendDayCheck($row['user_id'])){
                continue;
            }
            self::UserInConvert($row['user_id']);

            if( R::CheckExit('GamePl:NotSend_'.$row['user_role'])  || R::CheckExit('GamePl:'.$row['user_role'].":notSend")  ){
                continue;
            }

            R::rpush($row['user_id'],'GamePl:SendDayRole');
            $CheckDodge = self::CheckDodge($row);
            if($CheckDodge){
                continue;
            }

            switch ($row['user_role']){
                case 'role_midwolf':
                    if(R::Get('GamePl:Day_no') == 0){
                        continue 2;
                    }

                    if(R::Get('GamePl:Day_no') !== R::Get('GamePl:midwolfSendDay')){
                        continue 2;
                    }

                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'DaySelect_midwolf');
                    $inline_keyboard = new InlineKeyboard(...$rows);

                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('AskMidWolf'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);

                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                    }
                    break;

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
                case 'role_BlackKnight':
                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'DaySelect_BlackKnight');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('BlackKnightAsk'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                    }
                    break;
                    case 'role_feriga':
                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'DaySelect_feriga');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('AskFeriga'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
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
                    case 'role_Margita':
                        if(R::Get('GamePl:Day_no') !== R::Get('GamePl:MargitaSendDay')){
                            continue 2;
                        }
                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'DaySelect_Margita');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('AskMargita'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                    }
                    break;
                    case 'role_Madosa':
                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'DaySelect_madosa');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('MadosaAskDay'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                    }
                    break;
                case 'role_Princess':
                    if((int) R::Get('GamePl:Night_no') <= 2)  continue 2;

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
                    case 'role_Shahzade':
                        if(!R::CheckExit('GamePl:MargaritaKilled')){
                            continue 2;
                        }
                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'DaySelect_shahzade');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('AskShahzade'),
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
                    case 'role_Sharlatan':
                        if((int) R::Get('GamePl:SharlatanTofan')  == 0 && (int) R::Get('GamePl:SharlatanTabar') == 0){
                            continue 2;
                        }
                        $ReKeybaord = [];
                        if((int) R::Get('GamePl:SharlatanTofan') > 0){
                            $ReKeybaord[] = [
                               ['text' => self::$Dt->LG->_('SharlatanBtn_Tofan', array("{0}" => R::Get('GamePl:SharlatanTofan'))), 'callback_data' => "DaySelect_SharlatanTofan/" . self::$Dt->chat_id]
                         ];
                        }
                        if((int) R::Get('GamePl:SharlatanTabar') > 0){
                            $ReKeybaord[] =[
                                ['text' => self::$Dt->LG->_('SharlatanBtn_Edam', array("{0}" => R::Get('GamePl:SharlatanTabar'))), 'callback_data' => "DaySelect_SharlatanTabar/" . self::$Dt->chat_id]
                            ];
                        }

                    $inline_keyboard = (count($ReKeybaord) ? new InlineKeyboard(...$ReKeybaord) : new InlineKeyboard([[]]));
                     if(count($ReKeybaord)) {
                         $result = Request::sendMessage([
                             'chat_id' => $row['user_id'],
                             'text' => self::$Dt->LG->_('AskSarlatan'),
                             'parse_mode' => 'HTML',
                             'reply_markup' => $inline_keyboard,
                         ]);
                         if ($result->isOk()) {
                             R::GetSet($result->getResult()->getMessageId(), 'GamePl:role_Sharlatan:Message_id:' . $row['user_id']);
                             R::rpush($result->getResult()->getMessageId() . "_" . $row['user_id'], 'GamePl:EditMarkup');
                         }
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
