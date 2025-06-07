<?php

namespace phpcron\CronBot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;

class NG
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
        if(R::CheckExit('GamePl:KhabgozarOk')){
            return false;
        }

        $Sends = self::SendNightRole();

        self::ElamShekar();
        // اگه پیام لاور ها ارسال نشده بود بفرست


        if($Sends){
            R::GetSet(true,'GamePl:NightRoleSends');
        }
        self::CheckLucifer();
    }
    public static function ElamShekar(){

        if(R::Get('cult_hunter_expose_role') == "offr"){
            return false;
        }

        if(R::CheckExit('GamePl:CultHunterMessageSend')){
            return false;
        }
        $CultHunter = HL::_getPlayerByRole('role_shekar');
        // اگر قاتل نبود،مسلما باید بیخیال بشیم
        if($CultHunter == false){
            return false;
        }
        // اگر شکارچی مرده بود بازم باید بیخیال بشیم مسلما
        if($CultHunter['user_state'] !== 1){
            return false;
        }
        $GameMode = R::Get('GamePl:gameModePlayer');

        if($GameMode == "Romantic"){
            return false;
        }
        R::GetSet(true,'GamePl:CultHunterMessageSend');
        $CultHunterName = HL::ConvertName($CultHunter['user_id'],$CultHunter['fullname_game']);
        $MsgGroup = self::$Dt->LG->_('Shekar_msg',array("{0}" => $CultHunterName, "{1}" => R::Get('cultHunter_NightShow')));
        HL::SendMessage($MsgGroup);
    }
    public static function CheckNight(){

        // $mode = R::Get('GamePl:gameModePlayer');

        R::GetSet(time(),'GamePl:CheckNight');
        /*
         * ترتیب لیست چک کردن کار هایی که توی شب انجام شده :
         * قاتل
         * گرگ
         * شکارچی
         * فرقه گرا
         * فاحشه (ناتاشا)
         * فرشته نگهبان
         * پیشگو
         * احمق
         * نگاتیو
         * جادوگرذلر
         */



        if(R::CheckExit('GamePl:IsraOk')){
            if(R::Get('GamePl:IsraOk') == R::Get('GamePl:Night_no')){
                self::DeadPlayerNotInHomeIsra();
            }
        }

        if(R::CheckExit('GamePl:DeadforestQueen')){
            if(R::Get('GamePl:Night_no') == R::Get('GamePl:DeadforestQueen')) {
                self::DeadPlayerNotInHome();
            }
        }

        self::CheckJoker();
        self::CheckHarly();
        // چک کردن شوالیه
        self::CheckKnight();

        // چک کردن گرگ
        self::WolfTeam();
        //اگه توله رو کشته بودن باز باز کن برای گرگ بتونه 2 تا رو بخوره

        if(R::CheckExit('GamePl:WolfCubeDead') && R::CheckExit('GamePl:SendWolfCubeDead') == false){
            if(R::Get('GamePl:WolfCubeDead') == R::Get('GamePl:Night_no')) {
                HL::EditMarkupKeyboard();
                HL::PlusTime(45);
                HL::UnlockForTeam('wolf', true);
                R::GetSet(true,'GamePl:SendWolfCubeDead');
                R::Del('GamePl:CheckNight');
                R::Del('GamePl:SendNightAll');
                return false;
            }
        }


        // چک کردن قاتل
        self::GetKiller();
        self::GetHilda();
        if(R::CheckExit('GamePl:HunterKill')){
            return false;
        }


        // چک کردن شیمیدان
        self::CheckChemist();

        // چک کردن پادشاه آتش
        self::CheckFireFighter();

        // چک کردن کماندار
        self::CheckArcher();
        // چک کردن تیم ومپایر
        self::CheckVampire();
        // چک کردن شکارچی
        self::GetCultHunter();
        // چک کردن فرقه
        self::CheckCult();
        // چک کردن شب اول لوسیفر
        self::CheckLuciferTeam();
        // چک کردن فرشته نگهبان
        self::GetAngel();
        // چک کردن ملکه یخی
        self::CheckIceQueean();
        // چک کردن عجوزه
        self::CheckHoney();
        // چک کردن افسونگر
        self::CheckEnchanter();
        // چک کردن فاحشه (ناتاشا)
        self::GetFaheshe();
        // چک کردن کنت ومپایر
        self::Checkkent();
        // چک کردن هانتسمن
        self::CheckHuntsman();

        self::CheckMouse();

        self::CheckAugur();
        // چک کردن پیشگو
        self::GetSearSee();
        // چک کردن گروهی احمق
        self::GetAhmaqSeeGroup();
        // چک کردن احمق
        // self::GetAhmaqSee();

        // چک کردن نگاتیو
        self::GetNegativ();
        // چک کردن جادوگر
        self::GetJado();

        self::Watermelon();

        if(R::CheckExit('GamePl:RoyceDead')){
            if(R::Get('GamePl:RoyceDead') == R::Get('GamePl:Night_no')){
                HL::RoyceDeadSelect();
            }
        }

        // » یکی از چهار نفری باشید که شب اول مردند
        if(R::Get('GamePl:Night_no') == 0) {
            $GetKil = R::LRange(0,-1,'GamePl:NightKill');
            if(count($GetKil) >= 4){
                foreach ($GetKil as $user_id){
                    HL::SavePlayerAchivment($user_id,'Sunday_Bloody_Sunday');
                }
            }
            R::Del('GamePl:NightKill');
        }
        R::DelKey('GamePl:role_faheshe:inhome:*');
        R::DelKey('GamePl:UserInHome:*');
        R::Del('GamePl:role_angel:AngelNameSaved');
        R::DelKey('GamePl:role_angel:*');
        R::Del('GamePl:role_WhiteWolf:AngelSaved');
        R::Del('GamePl:role_WhiteWolf:AngelIn:*');
        return true;
    }

    public static function CheckMadosa($Attacker,$AttackerName){
        switch ($Attacker['user_role']){
            case 'role_faheshe':
                $GpMesssage = self::$Dt->LG->_('MadosaFaheshe',$AttackerName);
                HL::SaveMessage($GpMesssage);
                HL::UserDead($Attacker,'madosa');
                return true;
            break;
            case 'role_shekar':
                if(10 < HL::R(100)) {
                    $GpMesssage = self::$Dt->LG->_('MadosaCultHunter', $AttackerName);
                    HL::SaveMessage($GpMesssage);
                    HL::UserDead($Attacker,'madosa');
                    return true;
                }else{
                    return false;
                }
                break;
            case 'role_Knight':
                $GpMesssage = self::$Dt->LG->_('MadosaKnight',$AttackerName);
                HL::SaveMessage($GpMesssage);
                HL::UserDead($Attacker,'madosa');
                return true;
                break;
            case 'role_Qatel':
                $GpMesssage = self::$Dt->LG->_('MadosaKiller',$AttackerName);
                HL::SaveMessage($GpMesssage);
                HL::UserDead($Attacker,'madosa');
                return true;
                break;
            case 'role_Fereshte':
                $GpMesssage = self::$Dt->LG->_('MadosaFereshte',$AttackerName);
                HL::SaveMessage($GpMesssage);
                HL::UserDead($Attacker,'madosa');
                return true;
                break;
            case 'role_hilda':
                $GpMesssage = self::$Dt->LG->_('MadosaHilda',$AttackerName);
                HL::SaveMessage($GpMesssage);
                HL::UserDead($Attacker,'madosa');
                return true;
                break;
            case 'role_ferqe':
            case 'role_Royce':
                $GpMesssage = self::$Dt->LG->_('MdosaCult',$AttackerName);
                HL::SaveMessage($GpMesssage);
                HL::UserDead($Attacker,'madosa');
                return true;
                break;
           case 'role_Vampire':
                $GpMesssage = self::$Dt->LG->_('MadosaVampire',$AttackerName);
                HL::SaveMessage($GpMesssage);
                HL::UserDead($Attacker,'madosa');
                return true;
                break;
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_Wolfx':
            case 'role_WolfAlpha':
            case 'role_forestQueen':
                $GpMesssage = self::$Dt->LG->_('MadosaWolf',$AttackerName);
                HL::SaveMessage($GpMesssage);
                HL::UserDead($Attacker,'madosa');
                return true;
                break;
            default:
                return  false;
           break;
        }
    }

    public static function CheckAttack($Joker,$type,$Attacker,$AttckerName,$Team = false){
        $Harly = HL::_getPlayerByRole('role_Harly');
        if(!$Harly) return false;
        if(!is_array($Attacker)){
            $Attacker = HL::_getPlayerById($Attacker);
        }
        $JokerName = HL::ConvertName($Joker['uer_id'],$Joker['fullname_game']);

        switch ($type){
            case 'wolf':
                if($Team){
                    $HarlyMessage = self::$Dt->LG->_('HarlyWhenAttackJoker');
                    HL::SendMessage($HarlyMessage,$Harly['user_id']);
                    $JokerMessage = self::$Dt->LG->_('JokerMessageWhenAttack');
                    HL::SendMessage($JokerMessage,$Joker['user_id']);
                    $WolfMsg = self::$Dt->LG->_('WolfMessageWhenAttakJoker');
                    HL::SendForWolfTeam($WolfMsg,[$Attacker['user_id']]);
                    if(50  < HL::R(100)) {
                        $groupMessage = self::$Dt->LG->_('GroupMessageWhenWolfAttack', array("{0}" => $AttckerName));
                        HL::SaveMessage($groupMessage);
                        HL::UserDead($Attacker, 'harly');
                    }
                    return  true;
                }
                $WolfMsg = self::$Dt->LG->_('OneWolfAttackJoker',$JokerName);
                HL::SendMessage($WolfMsg,$Attacker['user_id']);
                $HarlyMessage = self::$Dt->LG->_('HarlyWhenAttackJoker');
                HL::SendMessage($HarlyMessage,$Harly['user_id']);
                $JokerMessage = self::$Dt->LG->_('JokerMessageWhenAttack');
                HL::SendMessage($JokerMessage,$Joker['user_id']);
                return true;
            break;
            case 'killer':
                $KillerMessage = self::$Dt->LG->_('KillerMessageWhenAttack',$JokerName);
                HL::SendMessage($KillerMessage,$Attacker['user_id']);
                $HarlyMessage = self::$Dt->LG->_('HarlyWhenAttackJoker');
                HL::SendMessage($HarlyMessage,$Harly['user_id']);
                $JokerMessage = self::$Dt->LG->_('JokerMessageWhenAttack');
                HL::SendMessage($JokerMessage,$Joker['user_id']);
                return true;
            break;
            case 'vampire':
                $VampireMessage = self::$Dt->LG->_('VampireAttack',$JokerName);
                HL::SendForVampireTeam($VampireMessage);
                $HarlyMessage = self::$Dt->LG->_('HarlyWhenAttackJoker');
                HL::SendMessage($HarlyMessage,$Harly['user_id']);
                $JokerMessage = self::$Dt->LG->_('JokerMessageWhenAttack');
                HL::SendMessage($JokerMessage,$Joker['user_id']);
                return  true;
            break;
            default:
                return  false;
            break;
        }
    }

    public static function GraveDiggerCheck(){
        $Grave = HL::_getPlayerByRole('role_GraveDigger');
        if($Grave == false){
            return false;
        }
        // اگر هانستمن مرده بود بازم باید بیخیال بشیم مسلما
        if($Grave['user_state'] !== 1){

            return false;
        }

        if(!R::CheckExit('playerDeadName')){
            $GraveMessage  = self::$Dt->LG->_('DigNoGraves');
            HL::SendMessage($GraveMessage,$Grave['user_id']);
            return true;
        }else{
            $GravName  = R::LRange(0,-1,'playerDeadName');
            $GraveMessage  = self::$Dt->LG->_('DigGraves',array("{0}" => implode(',',$GravName)));
            HL::SendMessage($GraveMessage,$Grave['user_id']);
            return true;
        }
    }
    public static function CheckPhoenix(){
        if(R::Get('GamePl:Night_no') !== 2) return false;

        $Phoenix = HL::_getPlayerByRole('role_Phoenix');
        if($Phoenix == false){
            return false;
        }
        if(R::CheckExit('GamePl:Selected:'.$Phoenix['user_id']) == false){
            return false;
        }

        $selected = R::Get('GamePl:Selected:'.$Phoenix['user_id']);

        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);

        $PlayerMessage = self::$Dt->LG->_('MessagePhoenixForOne');
        HL::SendMessage($PlayerMessage,$Detial['user_id']);
        $PhoenixMessage = self::$Dt->LG->_('MessagePhoenixSuccess',array("{0}" => $U_name));
        HL::SendMessage($PhoenixMessage,$Phoenix['user_id']);
        R::GetSet(true,'GamePl:PhoenixHealer:'.$Detial['user_id']);
        return  true;
    }
    public static function CheckBetaWolf(){
        $Beta = HL::_getPlayerByRole('role_betaWolf');
        if($Beta == false){
            return false;
        }

        $NightNo = (int) R::Get('GamePl:Night_no');
        if(!R::CheckExit('GamePl:BetaWolfSend')){
            $BetaMessge = self::$Dt->LG->_('role_betaWolfFindTow');
            HL::SendMessage($BetaMessge,$Beta['user_id']);
            R::GetSet( $NightNo + 1,'GamePl:BetaWolfSend');
            return true;
        }elseif((int) R::Get('GamePl:BetaWolfSend') == $NightNo){
            $NonPlayer = [$Beta['user_id']];

            if(R::CheckExit('GamePl:BetaWolfSendOk')){
                $GetData = json_decode(R::Get('GamePl:BetaWolfSendOk'),true);
                foreach ($GetData as $row){
                    array_push($NonPlayer,$row);
                }

            }

            $RandomPlayer = HL::GetUserRandomNonWolf($NonPlayer);
            if($RandomPlayer){
                $U_name = HL::ConvertName($RandomPlayer['user_id'],$RandomPlayer['fullname_game']);
                $BetaMessage = self::$Dt->LG->_('betaWolf_See',array("{0}" => $U_name , '{1}'=> self::$Dt->LG->_($RandomPlayer['user_role']."_n")));
                HL::SendMessage($BetaMessage,$RandomPlayer['user_id']);
                array_push($NonPlayer,$RandomPlayer['user_id']);
            }
            R::GetSet($NonPlayer,'GamePl:BetaWolfSendOk','json');
            R::GetSet( $NightNo + 1,'GamePl:BetaWolfSend');
            return true;
        }else {
            $BetaMessge = self::$Dt->LG->_('role_betaWolfFindTow');
            HL::SendMessage($BetaMessge,$Beta['user_id']);
            R::GetSet( $NightNo + 1,'GamePl:BetaWolfSend');
            return true;
        }


    }
    public static function CheckHuntsman(){
        $Huntsman = HL::_getPlayerByRole('role_Huntsman');

        if($Huntsman == false){
            return false;
        }
        // اگر هانستمن مرده بود بازم باید بیخیال بشیم مسلما
        if($Huntsman['user_state'] !== 1){

            return false;
        }

        if(R::CheckExit('GamePl:Selected:'.$Huntsman['user_id']) == false){
            return false;
        }
        $selected = R::Get('GamePl:Selected:'.$Huntsman['user_id']);

        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);

        $UserMessage = self::$Dt->LG->_('SuccessHuntsmanGUserMessage');
        HL::SendMessage($UserMessage,$Detial['user_id']);
        $HuntsmanMessage = self::$Dt->LG->_('SuccessHuntsmanG',array("{0}" => $U_name));
        HL::SendMessage($HuntsmanMessage,$Huntsman['user_id']);

        R::GetSet(true,'GamePl:HuntsmanTraps:'.$Detial['user_id']);
        R::GetSet(R::Get('GamePl:HuntsmanT') - 1,'GamePl:HuntsmanT');

        return true;
    }

    public static function CheckChemist(){
        $Chemist = HL::_getPlayerByRole('role_Chemist');

        if($Chemist == false){
            return false;
        }
        // اگر شیطان مرده بود بازم باید بیخیال بشیم مسلما
        if($Chemist['user_state'] !== 1){

            return false;
        }

        $ChemistName = HL::ConvertName($Chemist['user_id'],$Chemist['fullname_game']);

        if(R::CheckExit('GamePl:Selected:'.$Chemist['user_id']) == false){
            return false;
        }
        $selected = R::Get('GamePl:Selected:'.$Chemist['user_id']);

        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);
        if($Detial['user_state'] !== 1){
            $ChemistMessage = self::$Dt->LG->_('ChemistTargetDead',array("{0}" =>$U_name));
            HL::SendMessage($ChemistMessage,$Chemist['user_id']);
            return true;
        }

        if($Detial['team'] !== "wolf") {
            if (R::CheckExit('GamePl:UserInHome:' . $selected)) {
                $ChemistMessage = self::$Dt->LG->_('ChemistTargetEmpty', array("{0}" =>$U_name));
                HL::SendMessage($ChemistMessage, $Chemist['user_id']);
                return true;
            }
        }
        switch ($Detial['user_role']){
            case 'role_Qatel':
                if(HL::R(100) < 80){
                    $SKMessage = self::$Dt->LG->_('ChemistVisitYouSK',array("{0}" =>$ChemistName));
                    HL::SendMessage($SKMessage,$Detial['user_id']);

                    $ChemistMessage = self::$Dt->LG->_('ChemistSK',array("{0}" =>$U_name));
                    HL::SendMessage($ChemistMessage,$Chemist['user_id']);

                    $GroupMessage = self::$Dt->LG->_('ChemistSKPublic',array("{0}" =>$ChemistName));
                    HL::SaveMessage($GroupMessage);
                    HL::UserDead($Chemist,'Chemist_SK');

                    return true;
                }
                break;
        }

        if(HL::R(100) < SE::_s('ChemistSuccessChance')){
            $TargetMessage  = self::$Dt->LG->_('ChemistVisitYouSuccess');
            HL::SendMessage($TargetMessage,$Detial['user_id']);

            $ChemistMessage = self::$Dt->LG->_('ChemistSuccess',array("{0}" =>$U_name));
            HL::SendMessage($ChemistMessage,$Chemist['user_id']);

            if($Detial['user_role'] == "role_rishSefid"){
                $ElderMessage = self::$Dt->LG->_('ChemistKillWiseElder');
                HL::SendMessage($ElderMessage,$Detial['user_id']);
                HL::ConvertPlayer($Chemist['user_id'],'role_rosta');
                $ChemistMessage2 = self::$Dt->LG->_('ChemistKillWiseMessage',array("{0}" =>$U_name));
                HL::SendMessage($ChemistMessage2,$Chemist['user_id']);
            }

            $GroupMessage = self::$Dt->LG->_('ChemistSuccessPublic',array("{0}" => $U_name))." ".self::$Dt->LG->_('user_role',array("{0}" => self::$Dt->LG->_($Detial['user_role']."_n")));
            HL::SaveMessage($GroupMessage);
            HL::UserDead($Detial,'Chemist_kill');

        }else {
            $TargetMessage  = self::$Dt->LG->_('ChemistVisitYouFail',array("{0}" =>$ChemistName));
            HL::SendMessage($TargetMessage,$Detial['user_id']);

            $ChemistMessage = self::$Dt->LG->_('ChemistFail',array("{0}" =>$U_name));
            HL::SendMessage($ChemistMessage,$Chemist['user_id']);

            $GroupMessage = self::$Dt->LG->_('ChemistFailPublic',array("{0}" =>$ChemistName));
            HL::SaveMessage($GroupMessage);
            HL::UserDead($Chemist,'kill_Chemist');
        }

        return true;
    }
    public static function Checkkent(){
        $kent = HL::_getPlayerByRole('role_kentvampire');

        if($kent == false){
            return false;
        }
        // اگر کنت ومپایر مرده بود  باید بیخیال بشیم مسلما
        if($kent['user_state'] !== 1){

            return false;
        }

        $kentName = HL::ConvertName($kent['user_id'],$kent['fullname_game']);

        if(R::CheckExit('GamePl:Selected:'.$kent['user_id']) == false){
            return false;
        }
        $selected = R::Get('GamePl:Selected:'.$kent['user_id']);

        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);
        if($Detial['user_state'] !== 1){
            return true;
        }
        switch ($Detial['user_role']){
            case 'role_kalantar':
                $KentMessage = self::$Dt->LG->_('KentVampireFind',array("{0}" => $U_name,"{1}" => self::$Dt->LG->_($Detial['user_role']."_n")));
                HL::SendMessage($KentMessage,$kent['user_id']);
                return true;
            break;
            case 'role_Augur':
            case 'role_Huntsman':
            case 'role_Chemist':
            case 'role_Qatel':
            case 'role_lucifer':
            case 'role_shekar':
            case 'role_ferqe':
            case 'role_Royce':
            case 'role_faheshe':
            case 'role_Firefighter':
            case 'role_IceQueen':
            case 'role_Knight':
            case 'role_forestQueen':
            case 'role_Archer':
            case 'role_Bloodthirsty':
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_Wolfx':
            case 'role_WolfAlpha':
            case 'role_WhiteWolf':
               $KentMessage = self::$Dt->LG->_('KentVampireFind',array("{0}" => $U_name,"{1}" => self::$Dt->LG->_($Detial['user_role']."_n")));
               HL::SendMessage($KentMessage,$kent['user_id']);
               return true;
            break;
            default:
                $KentMessage = self::$Dt->LG->_('KentVampireNoFind',array("{0}" => $U_name));
                HL::SendMessage($KentMessage,$kent['user_id']);
            break;

        }

        return true;
    }


    public static function CheckAugur(){

        $Augure = HL::_getPlayerByRole('role_Augur');
        // اگر گرگ سفید نبود،مسلما باید بیخیال بشیم
        if($Augure == false){
            return false;
        }
        // اگر گرگ سفید مرده بود بازم باید بیخیال بشیم مسلما
        if($Augure['user_state'] !== 1){
            return false;
        }

        $Mode = R::Get('GamePl:gameModePlayer');
        $ModeRole  = SE::GetModeRole($Mode);
        array_push($ModeRole,'role_WolfTolle');
        array_push($ModeRole,'role_WolfGorgine');
        array_push($ModeRole,'role_Wolfx');
        array_push($ModeRole,'role_WolfAlpha');

        shuffle($ModeRole);
        $Players = HL::_getPlayers();
        shuffle($Players);
        $RoleColumn = array_column($Players,'user_role');
        $NotIn = array_diff($ModeRole,$RoleColumn);
        shuffle($NotIn);

        if($NotIn){
            $Random = array_rand($NotIn,3);
            $RoleSend = $ModeRole[$Random['0']]."_n";
            $AugureMessage  = self::$Dt->LG->_('AugurSees',array("{0}" => self::$Dt->LG->_($RoleSend)));
            HL::SendMessage($AugureMessage,$Augure['user_id']);
        }else {
            $AugureMessage  = self::$Dt->LG->_('AugurSeesNothing');
            HL::SendMessage($AugureMessage,$Augure['user_id']);
        }
        return true;

    }


    public static function Watermelon(){
        $Watermelon = HL::_getPlayerByRole('role_Watermelon');

        if($Watermelon == false){
            return false;
        }
        // اگر شیطان مرده بود بازم باید بیخیال بشیم مسلما
        if($Watermelon['user_state'] !== 1){

            return false;
        }

        // خب حالا مطمعن شدیم گرگ سفید هم هست،هم زندس، هم انتخابشو انجام داده حالا چک کردن رو شروع میکنیم
        $WatermelonName = HL::ConvertName($Watermelon['user_id'],$Watermelon['fullname_game']);

        if(R::CheckExit('GamePl:Selected:'.$Watermelon['user_id']) == false){
            return false;
        }
        $selected = R::Get('GamePl:Selected:'.$Watermelon['user_id']);

        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);


        $UserMessage = self::$Dt->LG->_('WatermelonChoseUser');
        HL::SendMessage($UserMessage,$Detial['user_id']);
        $WatermelonMessage = self::$Dt->LG->_('WatermelonChoseSuccess',$U_name);
        HL::SendMessage($WatermelonMessage,$Watermelon['user_id']);

        return true;
    }


    public static function CheckLuciferTeam(){
        $Lucifer = HL::_getPlayerByRole('role_lucifer');

        if($Lucifer == false){
            return false;
        }
        // اگر شیطان مرده بود بازم باید بیخیال بشیم مسلما
        if($Lucifer['user_state'] !== 1){

            return false;
        }

        if(R::Get('GamePl:Night_no') > 0){
            return false;
        }

        // خب حالا مطمعن شدیم گرگ سفید هم هست،هم زندس، هم انتخابشو انجام داده حالا چک کردن رو شروع میکنیم
        $LuciferName = HL::ConvertName($Lucifer['user_id'],$Lucifer['fullname_game']);

        if(R::CheckExit('GamePl:Selected:'.$Lucifer['user_id']) == false){

            return false;
        }
        $selected = R::Get('GamePl:Selected:'.$Lucifer['user_id']);


        $P_Team = HL::PlayerByTeam();
        $Wolf =  (count($P_Team['wolf']) > 0 ? $P_Team['wolf'] : false);
        $ferqe =  (count($P_Team['ferqe']) > 0 ? $P_Team['ferqe'] : false);
        $Vampire =  (count($P_Team['vampire']) > 0 ? $P_Team['vampire'] : false);
        $Qatel =  (count($P_Team['Qatel']) > 0 ? $P_Team['Qatel'] : false);

        switch ($selected){
            case 'rosta':
                $LuciferMessage = self::$Dt->LG->_('LuciferChangedToTeam',array("{0}" => self::$Dt->LG->_('RostaTeam')));
                HL::SendMessage($LuciferMessage,$Lucifer['user_id']);
                HL::ChangeLuciferTeam($selected,$Lucifer['user_id']);
                return true;
                break;
            case 'wolf':
                $TeamName = ($Wolf ? implode(',',array_column($Wolf,'Link')) : false);
                $LuciferMessage = self::$Dt->LG->_('LuciferChangedToTeam',array("{0}" =>self::$Dt->LG->_('WolfTeams'))).($TeamName ? PHP_EOL.self::$Dt->LG->_('LuciferTeamInfo',array("{0}" =>$TeamName)) : "");
                HL::SendMessage($LuciferMessage,$Lucifer['user_id']);

                if($TeamName){
                    $TeamMessage = self::$Dt->LG->_('LuciferChangeTeamToMessage',array("{0}" =>$LuciferName));
                    HL::SendForWolfTeam($TeamMessage,false);
                }
                HL::ChangeLuciferTeam($selected,$Lucifer['user_id']);
                return true;
                break;
            case 'ferqeTeem':
                $TeamName = ($ferqe ? implode(',',array_column($ferqe,'Link')) : false);
                $LuciferMessage = self::$Dt->LG->_('LuciferChangedToTeam',array("{0}" => self::$Dt->LG->_('FerqeTeam'))).($TeamName ? PHP_EOL.self::$Dt->LG->_('LuciferTeamInfo',array("{0}" =>$TeamName)) : "");
                HL::SendMessage($LuciferMessage,$Lucifer['user_id']);

                if($TeamName){
                    $TeamMessage = self::$Dt->LG->_('LuciferChangeTeamToMessage',array("{0}" =>$LuciferName));
                    HL::SendForCultTeam($TeamMessage,false);
                }
                HL::ChangeLuciferTeam($selected,$Lucifer['user_id']);
                return true;
                break;
            case 'vampire':
                $TeamName = ($Vampire ? implode(',',array_column($Vampire,'Link')) : false);
                $LuciferMessage = self::$Dt->LG->_('LuciferChangedToTeam',array("{0}" =>self::$Dt->LG->_('VampireTeams'))).($TeamName ? PHP_EOL.self::$Dt->LG->_('LuciferTeamInfo',array("{0}" =>$TeamName)) : "");
                HL::SendMessage($LuciferMessage,$Lucifer['user_id']);

                if($TeamName){
                    $TeamMessage = self::$Dt->LG->_('LuciferChangeTeamToMessage',array("{0}" =>$LuciferName));
                    HL::SendForVampireTeam($TeamMessage,false);
                }
                HL::ChangeLuciferTeam($selected,$Lucifer['user_id']);
                return true;
                break;
            case 'qatel':
                $TeamName = ($Qatel ? implode(',',array_column($Qatel,'Link')) : false);
                $LuciferMessage = self::$Dt->LG->_('LuciferChangedToTeam',array("{0}" =>self::$Dt->LG->_('QatelTeam'))).($TeamName ? PHP_EOL.self::$Dt->LG->_('LuciferTeamInfo',array("{0}" =>$TeamName)) : "");
                HL::SendMessage($LuciferMessage,$Lucifer['user_id']);

                if($TeamName){
                    $TeamMessage = self::$Dt->LG->_('LuciferChangeTeamToMessage',array("{0}" =>$LuciferName));
                    HL::SendForQatelTeam($TeamMessage,false);
                }
                HL::ChangeLuciferTeam($selected,$Lucifer['user_id']);

                return true;
                break;

        }


        return false;

    }
    public static function CheckLucifer(){

        $Lucifer = HL::_getPlayerByRole('role_lucifer');

        if($Lucifer == false){
            return false;
        }
        // اگر شیطان مرده بود بازم باید بیخیال بشیم مسلما
        if($Lucifer['user_state'] !== 1){
            return false;
        }

        if(R::Get('GamePl:Night_no') == 0){
            return true;
        }

        if(R::Get('GamePl:ClearLasTLucifer') < R::Get('GamePl:Night_no')){
            R::DelKey('GamePl:role_lucifer:*');
        }
        // اگر  شیطان انتخابی نکرد بازم لزومی نداره چک کنیم
        if(!R::CheckExit('GamePl:Selected:'.$Lucifer['user_id'])){

            return false;
        }


        R::GetSet(R::Get('GamePl:Night_no'),'GamePl:ClearLasTLucifer');
        // خب حالا مطمعن شدیم گرگ سفید هم هست،هم زندس، هم انتخابشو انجام داده حالا چک کردن رو شروع میکنیم
        $LuciferName = HL::ConvertName($Lucifer['user_id'],$Lucifer['fullname_game']);
        $selected = R::Get('GamePl:Selected:'.$Lucifer['user_id']);


        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);

        R::Del('GamePl:Selected:'.$Lucifer['user_id']);

        // اگه مرده بود  طرف چک کردنو بیخیال میشیم
        if($Detial['user_state'] !== 1){
            $LuciferMessage = self::$Dt->LG->_('DodgeDeadPlayer',array("{0}" =>$U_name));
            HL::SendMessage($LuciferMessage, $Lucifer['user_id']);
            R::Del('GamePl:role_lucifer:checkLucifer');
            return false;
        }
        switch ($Detial['user_role']) {
            case 'role_shekar':
                $MsgLucifer = self::$Dt->LG->_('LuciferInCultHunter',array("{0}" =>$U_name));
                HL::SendMessage($MsgLucifer,$Lucifer['user_id']);
                $MsgCultHunter = self::$Dt->LG->_('LuciferCultHunterDodge');
                HL::SendMessage($MsgCultHunter,$Detial['user_id']);
                R::Del('GamePl:role_lucifer:checkLucifer');
                return false;
                break;
            case 'role_Phoenix':
                if(R::Get('GamePl:Night_no') == 2){
                    self::DodgeNightSelect($Detial, $Lucifer);
                    R::Del('GamePl:role_lucifer:checkLucifer');
                    return false;
                }

                $LuciferMessage = self::$Dt->LG->_('LuciferDodgeVote',array("{0}" =>$U_name));
                HL::SendMessage($LuciferMessage, $Lucifer['user_id']);
                R::GetSet(true,'GamePl:role_lucifer:DodgeVote:'.$Detial['user_id']);
                R::GetSet($Detial['user_id'],'GamePl:role_lucifer:DodgeVote');
                R::Del('GamePl:role_lucifer:checkLucifer');
                return  true;
            break;
            case 'role_Qatel':
                if(SE::_s('DodgeQatelDead') < HL::R(100)) {
                    $GroupMessage = self::$Dt->LG->_('LuciferDodgeQatelGroupMessage', array("{0}" => $LuciferName));
                    HL::SaveMessage($GroupMessage);
                    HL::UserDead($Lucifer, 'kill_dodge_qatel');
                    HL::SaveGameActivity($Lucifer,'kill',$Detial);
                    R::Del('GamePl:role_lucifer:checkLucifer');
                    return true;
                }

                /*
                //chalesh A
                $noP = R::NoPerfix();
                if(!$noP->exists('user_Medal_lucifer:'.$Lucifer['user_id'])) {
                    HL::InsertMedal($Lucifer['user_id'], '', 'تبریک شما مدال گول زدن قاتل در چالش ما را برنده شدید .');
                    $noP->set('user_Medal_lucifer:'.$Lucifer['user_id'],true);
                }
                if(!$noP->exists('user_Medal_lucifer_ins:'.$Lucifer['user_id'])) {
                    $noP->getset('user_Medal_lucifer_ins:'.$Lucifer['user_id'],true);
                }
                */
                self::DodgeNightSelect($Detial, $Lucifer);
                R::Del('GamePl:role_lucifer:checkLucifer');
                return false;
                break;
            case 'role_WolfAlpha':
                $GroupMessage = self::$Dt->LG->_('LuciferDodgeWolfGroupMessage',array("{0}" =>  $LuciferName));
                HL::SaveMessage($GroupMessage);
                HL::UserDead($Lucifer, 'wolf_dodge');
                HL::SaveGameActivity($Lucifer,'eat',$Detial);
                R::Del('GamePl:role_lucifer:checkLucifer');
                return true;
                break;
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_Wolfx':
                if(SE::_s('DodgeWolfDead') < HL::R(100)) {
                    $GroupMessage = self::$Dt->LG->_('LuciferDodgeWolfGroupMessage', array("{0}" =>$LuciferName));
                    HL::SaveMessage($GroupMessage);
                    HL::UserDead($Lucifer, 'wolf_dodge');
                    HL::SaveGameActivity($Lucifer,'eat',$Detial);
                    R::Del('GamePl:role_lucifer:checkLucifer');
                    return true;
                }
                /*
                           //chalesh A
                           $noP = R::NoPerfix();
                           if(!$noP->exists('user_Medal_lucifer:'.$Lucifer['user_id'])) {
                               HL::InsertMedal($Lucifer['user_id'], '', 'تبریک، شما مدال  جدید گول زدن گرگ در چالش ما را برنده شدید.');
                           }*/

                self::DodgeNightSelect($Detial,$Lucifer);
                R::Del('GamePl:role_lucifer:checkLucifer');
                return true;
                break;

            case 'role_Huntsman':
                if(R::Get('GamePl:HuntsmanT') <= 0){
                    $LuciferMessage = self::$Dt->LG->_('LuciferDodgeVote',array("{0}" =>$U_name));
                    HL::SendMessage($LuciferMessage, $Lucifer['user_id']);
                    R::GetSet(true,'GamePl:role_lucifer:DodgeVote:'.$Detial['user_id']);
                    R::GetSet($Detial['user_id'],'GamePl:role_lucifer:DodgeVote');
                }
                self::DodgeNightSelect($Detial,$Lucifer);
                R::Del('GamePl:role_lucifer:checkLucifer');
                break;
            case 'role_pishgo':
            case 'role_faheshe':
            case 'role_Fereshte':
            case 'role_enchanter':
            case 'role_ahmaq':
            case 'role_ferqe':
            case 'role_Honey':
            case 'role_Firefighter':
            case 'role_IceQueen':
            case 'role_WolfJadogar':
            case 'role_ngativ':
            case 'role_Vampire':
            case 'role_Chemist':
                self::DodgeNightSelect($Detial,$Lucifer);
                R::Del('GamePl:role_lucifer:checkLucifer');
                return true;
                break;
            case 'role_kentvampire':
                if(R::CheckExit('GamePl:KentVampireConvert')){
                    $LuciferMessage = self::$Dt->LG->_('LuciferDodgeDayRole',array("{0}" =>$U_name));
                    HL::SendMessage($LuciferMessage, $Lucifer['user_id']);
                    R::GetSet(true,'GamePl:role_lucifer:DodgeDay:'.$Detial['user_id']);
                    R::GetSet($Detial['user_id'],'GamePl:role_lucifer:DodgeDay');
                    R::Del('GamePl:role_lucifer:checkLucifer');
                }else{
                    self::DodgeNightSelect($Detial,$Lucifer);
                    R::Del('GamePl:role_lucifer:checkLucifer');
                }
            break;
            case 'role_Knight':
                if(R::Get('GamePl:KnightSendFor') > R::Get('GamePl:Night_no')){
                    $LuciferMessage = self::$Dt->LG->_('LuciferDodgeVote',array("{0}" =>$U_name));
                    HL::SendMessage($LuciferMessage, $Lucifer['user_id']);
                    R::GetSet(true,'GamePl:role_lucifer:DodgeVote:'.$Detial['user_id']);
                    R::GetSet($Detial['user_id'],'GamePl:role_lucifer:DodgeVote');
                }
                self::DodgeNightSelect($Detial,$Lucifer);
                R::Del('GamePl:role_lucifer:checkLucifer');
                return true;
                break;
            case 'role_Archer':
                if(R::Get('GamePl:ArcherSendFor') > R::Get('GamePl:Night_no')){
                    $LuciferMessage = self::$Dt->LG->_('LuciferDodgeVote',array("{0}" =>$U_name));
                    HL::SendMessage($LuciferMessage, $Lucifer['user_id']);
                    R::GetSet(true,'GamePl:role_lucifer:DodgeVote:'.$Detial['user_id']);
                    R::GetSet($Detial['user_id'],'GamePl:role_lucifer:DodgeVote');
                }
                self::DodgeNightSelect($Detial,$Lucifer);
                R::Del('GamePl:role_lucifer:checkLucifer');
                return true;
                break;
            case 'role_forestQueen':
                if (R::CheckExit('GamePl:role_forestQueen:AlphaDead')) {
                    if (SE::_s('DodgeWolfDead') < HL::R(100)) {
                        $GroupMessage = self::$Dt->LG->_('LuciferDodgeWolfGroupMessage', array("{0}" => $LuciferName));
                        HL::SaveMessage($GroupMessage);
                        HL::UserDead($Lucifer, 'wolf_dodge_qatel');
                        HL::SaveGameActivity($Lucifer,'eat',$Detial);
                        R::Del('GamePl:role_lucifer:checkLucifer');
                        return true;
                    }
                    self::DodgeNightSelect($Detial, $Lucifer);
                    R::Del('GamePl:role_lucifer:checkLucifer');
                    return true;
                }
                $LuciferMessage = self::$Dt->LG->_('LuciferDodgeVote',array("{0}" => $U_name));
                HL::SendMessage($LuciferMessage, $Lucifer['user_id']);
                R::GetSet(true,'GamePl:role_lucifer:DodgeVote:'.$Detial['user_id']);
                R::GetSet($Detial['user_id'],'GamePl:role_lucifer:DodgeVote');
                R::Del('GamePl:role_lucifer:checkLucifer');
                return true;
                break;
            case 'role_WhiteWolf':
                if (R::CheckExit('GamePl:WhiteWolfToWolf')) {
                    if (SE::_s('DodgeWolfDead') < HL::R(100)) {
                        $GroupMessage = self::$Dt->LG->_('LuciferDodgeWolfGroupMessage', array("{0}" => $LuciferName));
                        HL::SaveMessage($GroupMessage);
                        HL::UserDead($Lucifer, 'wolf_dodge_qatel');
                        HL::SaveGameActivity($Lucifer,'eat',$Detial);
                        R::Del('GamePl:role_lucifer:checkLucifer');
                        return true;
                    }
                    self::DodgeNightSelect($Detial, $Lucifer);
                    R::Del('GamePl:role_lucifer:checkLucifer');
                    return true;
                }
                $LuciferMessage = self::$Dt->LG->_('LuciferDodgeVote',array("{0}" => $U_name));
                HL::SendMessage($LuciferMessage, $Lucifer['user_id']);
                R::GetSet(true,'GamePl:role_lucifer:DodgeVote:'.$Detial['user_id']);
                R::GetSet($Detial['user_id'],'GamePl:role_lucifer:DodgeVote');
                R::Del('GamePl:role_lucifer:checkLucifer');
                return true;
                break;
            case 'role_Bloodthirsty':
                if(R::CheckExit('GamePl:Bloodthirsty')){
                    if(SE::_s('DodgeBloodDead') < HL::R(100)) {
                        $GroupMessage = self::$Dt->LG->_('LuciferDodgeBloodGroupMessage',array("{0}" => $LuciferName));
                        HL::SaveMessage($GroupMessage);
                        HL::UserDead($Lucifer, 'vampireblood_dodge_qatel');
                        HL::SaveGameActivity($Lucifer,'vampire',$Detial);
                        R::Del('GamePl:role_lucifer:checkLucifer');
                        return true;
                    }
                    self::DodgeNightSelect($Detial,$Lucifer);
                    R::Del('GamePl:role_lucifer:checkLucifer');
                    return  true;
                }
                $LuciferMessage = self::$Dt->LG->_('LuciferDodgeVote',array("{0}" =>$U_name));
                HL::SendMessage($LuciferMessage, $Lucifer['user_id']);
                R::GetSet(true,'GamePl:role_lucifer:DodgeVote:'.$Detial['user_id']);
                R::GetSet($Detial['user_id'],'GamePl:role_lucifer:DodgeVote');
                R::Del('GamePl:role_lucifer:checkLucifer');
                return true;
                break;
            case 'role_Spy':
            case 'role_karagah':
            case 'role_Princess':
                $LuciferMessage = self::$Dt->LG->_('LuciferDodgeDayRole',array("{0}" =>$U_name));
                HL::SendMessage($LuciferMessage, $Lucifer['user_id']);
                R::GetSet(true,'GamePl:role_lucifer:DodgeDay:'.$Detial['user_id']);
                R::GetSet($Detial['user_id'],'GamePl:role_lucifer:DodgeDay');
                R::Del('GamePl:role_lucifer:checkLucifer');
                return false;
                break;
            case 'role_tofangdar':
                if(R::Get('GamePl:GunnerBult') <= 0){
                    $LuciferMessage = self::$Dt->LG->_('LuciferDodgeVote',array("{0}" =>$U_name));
                    HL::SendMessage($LuciferMessage, $Lucifer['user_id']);
                    R::GetSet(true,'GamePl:role_lucifer:DodgeVote:'.$Detial['user_id']);
                    R::Del('GamePl:role_lucifer:checkLucifer');
                    return true;
                }
                $LuciferMessage = self::$Dt->LG->_('LuciferDodgeDayRole',array("{0}" =>$U_name));
                HL::SendMessage($LuciferMessage, $Lucifer['user_id']);
                R::GetSet(true,'GamePl:role_lucifer:DodgeDay:'.$Detial['user_id']);
                R::GetSet($Detial['user_id'],'GamePl:role_lucifer:DodgeDay');
                R::Del('GamePl:role_lucifer:checkLucifer');
                return false;
                break;
            default:
                $LuciferMessage = self::$Dt->LG->_('LuciferDodgeVote',array("{0}" =>$U_name));
                HL::SendMessage($LuciferMessage, $Lucifer['user_id']);
                R::GetSet(true,'GamePl:role_lucifer:DodgeVote:'.$Detial['user_id']);
                R::GetSet($Detial['user_id'],'GamePl:role_lucifer:DodgeVote');
                R::Del('GamePl:role_lucifer:checkLucifer');
                return false;
                break;
        }



    }



    public static function DodgeNightSelect($Detial,$luId){
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);

        $Messge_id =(R::CheckExit('GamePl:MessageNightSend:'.$Detial['user_id']) ? R::Get('GamePl:MessageNightSend:'.$Detial['user_id']) : false);

        $selectedNew =(R::CheckExit('GamePl:new:MessageNightSend:'.$Detial['user_id']) ? R::Get('GamePl:new:MessageNightSend:'.$Detial['user_id']) : false);

        if($Messge_id){
            Request::editMessageText([
                'chat_id' => $Detial['user_id'],
                'message_id' => $Messge_id,
                'text' => self::$Dt->LG->_('DodgePlayerNight'),
                'reply_markup' =>  new InlineKeyboard([]),
            ]);
            R::Del('GamePl:MessageNightSend:'.$Detial['user_id']);
        } elseif($selectedNew){
            $userMessage = self::$Dt->LG->_('LuciferGGD');
            HL::SendMessage($userMessage, $Detial['user_id']);
            R::Del('GamePl:new:MessageNightSend:'.$Detial['user_id']);
        }

        $Selected = (R::CheckExit('GamePl:Selected:'.$Detial['user_id']) ? R::Get('GamePl:Selected:'.$Detial['user_id']) : false);
        if($Selected) {
            $Dt = HL::_getPlayerById($Selected);
            $DtName =  HL::ConvertName($Dt['user_id'],$Dt['fullname_game']);
        }
        $LuciferMessage = ($Selected ? self::$Dt->LG->_('DodgePlayerNight_select',array("{0}" => $U_name, "{1}" => $DtName)) : self::$Dt->LG->_('DodgePlayerNight_Notselect',array("{0}" =>$U_name)));
        HL::SendMessage($LuciferMessage, $luId['user_id']);


        R::GetSet($Detial['user_id'],'GamePl:role_lucifer:NightSelect');

        $rows = HL::GetPlayerNonKeyboard([], 'NghddgDlec_'.$Detial['user_role']);
        $inline_keyboard = new InlineKeyboard(...$rows);
        $result =  Request::sendMessage([
            'chat_id' => $luId['user_id'],
            'text' => self::GetMessageNight($Detial['user_role']),
            'reply_markup' => $inline_keyboard,
            'parse_mode' => 'HTML',
        ]);
        if($result->isOk()){
            R::rpush($luId['user_id'],'GamePl:SendNight');
            R::rpush($result->getResult()->getMessageId()."_".$luId['user_id'],'GamePl:MessageNightSend');
        }

    }



    public static function GetWhiteWolf(){
        $WhiteWolf = HL::_getPlayerByRole('role_WhiteWolf');
        // اگر گرگ سفید نبود،مسلما باید بیخیال بشیم
        if($WhiteWolf == false){
            return false;
        }
        // اگر گرگ سفید مرده بود بازم باید بیخیال بشیم مسلما
        if($WhiteWolf['user_state'] !== 1){
            return false;
        }
        // اگر  گرگ سفید انتخابی نکرد بازم لزومی نداره چک کنیم
        if(!R::CheckExit('GamePl:Selected:'.$WhiteWolf['user_id'])){
            return false;
        }

        // خب حالا مطمعن شدیم گرگ سفید هم هست،هم زندس، هم انتخابشو انجام داده حالا چک کردن رو شروع میکنیم
        $WhiteWolfName = HL::ConvertName($WhiteWolf['user_id'],$WhiteWolf['fullname_game']);
        $selected = R::Get('GamePl:Selected:'.$WhiteWolf['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);

        // اگه مرده بود  طرف چک کردنو بیخیال میشیم
        if($Detial['user_state'] !== 1){
            return false;
        }

        switch ($Detial['user_role']) {
            default:
                $GetNameSaved = R::Get('GamePl:role_WhiteWolf:AngelNameSaved');
                $MessageAngel = (R::CheckExit('GamePl:role_WhiteWolf:AngelSaved') == false ?  self::$Dt->LG->_('WhiteWolfAngel',array("{0}" => $GetNameSaved)) : "" );
                if($MessageAngel) {
                    HL::SendMessage($MessageAngel, $WhiteWolf['user_id']);
                    R::Del('GamePl:role_WhiteWolf:AngelSaved');
                    R::Del('GamePl:role_WhiteWolf:AngelNameSaved');
                }
                return  true;
                break;
        }

        return false;
    }

    public static function CheckHoney(){
        $Honey = HL::_getPlayerByRole('role_Honey');
        if($Honey == false){
            return false;
        }


        if($Honey['user_state'] !== 1){
            return false;
        }

        if(!R::CheckExit('GamePl:Selected:'.$Honey['user_id'])){
            return false;
        }

        $selected = R::Get('GamePl:Selected:'.$Honey['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);

        switch ($Detial['user_role']){
            case 'role_shekar':
                if(HL::R(100) < 50){
                    self::HoneyOk($selected);
                    $HoneyMessage = self::$Dt->LG->_('SuccessHoneyChangeRole',array("{0}" => $U_name));
                    HL::SendMessage($HoneyMessage,$Honey['user_id']);
                    return true;
                }
                $HoneyMessage = self::$Dt->LG->_('dont_honeyCultHunter',array("{0}" => $U_name));
                HL::SendMessage($HoneyMessage,$Honey['user_id']);
                return true;
                break;
            default:
                self::HoneyOk($selected);
                $HoneyMessage = self::$Dt->LG->_('SuccessHoneyChangeRole',array("{0}" => $U_name));
                HL::SendMessage($HoneyMessage,$Honey['user_id']);
                return true;
                break;
        }

        return false;
    }

    public static function HoneyOk($user_id){
        R::GetSet($user_id,'GamePl:HoneyUser:'.$user_id);
    }
    public static function GetTeamVampireSelected(){

        $Keys = R::Keys('GamePl:Selected:Vampire:*'); // دریافت تمام داده های توی این پترن

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
                    // ارایه رو برابر با عدد بزرگتر کن
                    $max = $row['total'];
                    // ای دی کاربر رو هم اضافه کن به عدد خروجی
                    array_push($maxTotal,$row['user_id']);
                }
            }

            if($maxTotal){
                return $maxTotal['0'];
            }
            return $Selected['0']['user_id'];
        }

        return false;
    }

    public static function CheckVampire(){
        $P_Team = HL::PlayerByTeam();
        $Vampire =  (count($P_Team['vampire']) > 0 ? $P_Team['vampire'] : false);
        // چک کن تیم ومپایر وجود داره
        if($Vampire == false){
            return false;
        }

        $VampireName = ($Vampire ? implode(',',array_column($Vampire,'Link')) : false);

        $count_vampire = count($P_Team['vampire']);
        if($count_vampire == 1){
            $Me_user_id = $Vampire['0']['user_id'];
            if(!R::CheckExit('GamePl:Selected:'.$Me_user_id)){
                return false;
            }
            $Me_name =  $Vampire['0']['Link'];
            $selected = R::Get('GamePl:Selected:'.$Me_user_id);

        }else{
            // مجموع انتخاب تیم ومپایر ها رو حساب کن و یه ای دی بده
            $GetSelected = self::GetTeamVampireSelected();

            if(empty($GetSelected)){
                return false;
            }
            $selected = $GetSelected;
            // دریافت نام هم تیمی گرگ ها
            $LastVampire = HL::_getLastVampire();
            $Me_user_id = $LastVampire['user_id'];
            $Me_name =  HL::ConvertName($LastVampire['user_id'],$LastVampire['fullname_game']);
        }

        $Detial = HL::_getPlayer($selected);
        if($Detial['user_state'] !== 1){
            return false;
        }
        $U_name = HL::ConvertName($selected,$Detial['fullname_game']);

        $Bloodthirsty = false;
        if(R::CheckExit('GamePl:Bloodthirsty')){
            $Bdt = HL::_getPlayerByRole('role_Bloodthirsty');
            if($Bdt['user_state'] !== 1){
                $BloodthirstyName = HL::ConvertName($Bdt['user_id'],$Bdt['fullname_game']);
                $Bloodthirsty = true;
            }
        }
        $CheckPhoenix = HL::CheckPhoenixHeal($Detial);
        if($CheckPhoenix){
            $PlayerMessage = self::$Dt->LG->_('MassageAttack');
            HL::SendMessage($PlayerMessage,$Detial['user_id']);
            $AttackerMessage = self::$Dt->LG->_('MessageForVampire',array("{0}" => $U_name));
            HL::SendForVampireTeam($AttackerMessage);
            return  true;
        }


        switch ($Detial['user_role']){
            case 'role_Sweetheart':
                if(R::Get('GamePl:SweetheartLove:team') == "vampire"){
                    if(R::CheckExit('GamePl:VampireConvert')) {
                        // شانس ومپایر رو امتحان میکنیم که میتونه گاز بزنه یا نه
                        if (HL::R(100) < R::Get('GamePl:VampireConvert')) {
                            // به طرف خبر میدیم که گازیده شده
                            $PlayerMessage = self::$Dt->LG->_('VampireConvertUser');
                            HL::SendMessage($PlayerMessage, $Detial['user_id']);
                            // به ومپایر ها خبر میدیم که طرفو گازیدن جا خوردنش
                            $VampireMessaage = ($Bloodthirsty ? self::$Dt->LG->_('VampireConvertByBlood',array("{0}" => $U_name,"{1}" => $BloodthirstyName)) : ($count_vampire > 1 ? self::$Dt->LG->_('VampireConvertTeam',array("{0}" => $Me_name, "{1}"=> $U_name)) : self::$Dt->LG->_('VampireConvert', array("{0}" => $U_name))));
                            HL::SendForVampireTeam($VampireMessaage);
                            // خب حالا طرف رو میذاریم توی لیست گاز زده ها
                            HL::VampireConvert($Detial['user_id']);
                            return true;
                        }
                        // اگه اصیل آزاد بود طرف میمیره
                        if(R::CheckExit('GamePl:Bloodthirsty')){
                            // در این صورت طرف میمیره
                            $groupMessage = self::$Dt->LG->_('VampireKillPlayer',array("{0}" =>$U_name,"{1}" => self::$Dt->LG->_('user_role',array("{0}"=> self::$Dt->LG->_($Detial['user_role']."_n")))));
                            HL::SaveMessage($groupMessage);
                            HL::UserDead($Detial,'Vampire');
                            HL::SaveKillVampire($Vampire,$Detial);
                            return true;
                        }
                    }
                    if (HL::R(100) < SE::_s('VampireChangeNotKill')) {
                        $VampireMessage = ($count_vampire > 1 ? self::$Dt->LG->_('VampireMessageNoKillTeam',array("{0}" => $Me_name, "{1}" => $U_name)) : self::$Dt->LG->_('VampireMessageNoKill',array("{0}" =>$U_name)));
                        HL::SendForVampireTeam($VampireMessage);
                        $PlayerMessage = self::$Dt->LG->_('VampireMessageNoKillPlayer');
                        HL::SendMessage($PlayerMessage, $Detial['user_id']);
                        return false;
                    }
                    $MsgUser = self::$Dt->LG->_('eat_Vampire');
                    HL::SendMessage($MsgUser,$selected,'eat_vampire');
                    // در این صورت طرف میمیره
                    $groupMessage = self::$Dt->LG->_('VampireKillPlayer',array("{0}" =>$U_name,"{1}" => self::$Dt->LG->_('user_role',array("{0}"=> self::$Dt->LG->_($Detial['user_role']."_n")))));
                    HL::SaveMessage($groupMessage);
                    HL::UserDead($Detial,'Vampire');
                    HL::SaveKillVampire($Vampire,$Detial);
                    return true;
                }

                $PlayerMesssage =($count_vampire > 1 ? self::$Dt->LG->_('MsgWolfPlayerLoverVampires',array("{0}" =>$U_name)) : self::$Dt->LG->_('MsgWolfPlayerLoverVampireOne',array("{0}" =>$U_name)));
                HL::SendMessage($PlayerMesssage,$Me_user_id);
                if($count_vampire > 1){
                    $VampireTeamMessage = self::$Dt->LG->_('MsgVampires',array("{0}" => $U_name, "{1}" => $Me_name));
                    HL::SendForVampireTeam($VampireTeamMessage,$Me_user_id);
                }
                HL::LoverBYSweetheart($Me_user_id,'vampire');
                return true;
                break;
            case 'role_Joker':
                $checkAttack = self::CheckAttack($Detial,'vampire',$Me_user_id,$Me_name,($count_vampire > 1 ? true : false));
                if($checkAttack){
                    return  true;
                }
                if(R::CheckExit('GamePl:VampireConvert')) {
                    // شانس ومپایر رو امتحان میکنیم که میتونه گاز بزنه یا نه
                    if (HL::R(100) < R::Get('GamePl:VampireConvert')) {
                        // به طرف خبر میدیم که گازیده شده
                        $PlayerMessage = self::$Dt->LG->_('VampireConvertUser');
                        HL::SendMessage($PlayerMessage, $Detial['user_id']);
                        // به ومپایر ها خبر میدیم که طرفو گازیدن جا خوردنش
                        $VampireMessaage = ($Bloodthirsty ? self::$Dt->LG->_('VampireConvertByBlood',array("{0}" => $U_name,"{1}" => $BloodthirstyName)) : ($count_vampire > 1 ? self::$Dt->LG->_('VampireConvertTeam',array("{0}" => $Me_name, "{1}"=> $U_name)) : self::$Dt->LG->_('VampireConvert', array("{0}" => $U_name))));
                        HL::SendForVampireTeam($VampireMessaage);
                        // خب حالا طرف رو میذاریم توی لیست گاز زده ها
                        HL::VampireConvert($Detial['user_id']);
                        return true;
                    }
                    // اگه اصیل آزاد بود طرف میمیره
                    if(R::CheckExit('GamePl:Bloodthirsty')){
                        // در این صورت طرف میمیره
                        $groupMessage = self::$Dt->LG->_('VampireKillPlayer',array("{0}" =>$U_name,"{1}" => self::$Dt->LG->_('user_role',array("{0}"=> self::$Dt->LG->_($Detial['user_role']."_n")))));
                        HL::SaveMessage($groupMessage);
                        HL::UserDead($Detial,'Vampire');
                        HL::SaveKillVampire($Vampire,$Detial);
                        return true;
                    }
                }
                if (HL::R(100) < SE::_s('VampireChangeNotKill')) {
                    $VampireMessage = ($count_vampire > 1 ? self::$Dt->LG->_('VampireMessageNoKillTeam',array("{0}" => $Me_name, "{1}" => $U_name)) : self::$Dt->LG->_('VampireMessageNoKill',array("{0}" =>$U_name)));
                    HL::SendForVampireTeam($VampireMessage);
                    $PlayerMessage = self::$Dt->LG->_('VampireMessageNoKillPlayer');
                    HL::SendMessage($PlayerMessage, $Detial['user_id']);
                    return false;
                }
                $MsgUser = self::$Dt->LG->_('eat_Vampire');
                HL::SendMessage($MsgUser,$selected,'eat_vampire');
                // در این صورت طرف میمیره
                $groupMessage = self::$Dt->LG->_('VampireKillPlayer',array("{0}" =>$U_name,"{1}" => self::$Dt->LG->_('user_role',array("{0}"=> self::$Dt->LG->_($Detial['user_role']."_n")))));
                HL::SaveMessage($groupMessage);
                HL::UserDead($Detial,'Vampire');
                HL::SaveKillVampire($Vampire,$Detial);

            break;
            case 'role_Qatel':
                // ارسال پیام برای کسی که رفت خونه قاتل
                $UserMessage = self::$Dt->LG->_("VampireDeadByKiller",array("{0}" =>$U_name));
                HL::SendMessage($UserMessage,$Me_user_id);
                $GroupMessage = self::$Dt->LG->_('VampireDeadByKillerGroupMessage',array("{0}" =>$Me_name));
                HL::SaveMessage($GroupMessage);
                HL::UserDead($Me_user_id,'kill');
                HL::SaveGameActivity(['user_id' => $Me_user_id,'fullname'=> $Me_name],'kill',$Detial);
                $KillerMessage = self::$Dt->LG->_('VampireConKiller',array("{0}" =>$Me_name));
                HL::SendMessage($KillerMessage,$selected);
                if($count_vampire > 1) {
                    $VampireTeamMessage = self::$Dt->LG->_('VampireDeadByKillerTeam', array("{0}" => $U_name,"{1}" => $Me_name));
                    HL::SendForVampireTeam($VampireTeamMessage);
                }

                return true;
                break;
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_Wolfx':
            case 'role_WolfAlpha':
                if(HL::R(100) < SE::_s('VampireChangeWolfDU')){
                    $VampireMessage = self::$Dt->LG->_('VampireDeadWolf',array("{0}" =>$U_name));
                    HL::SendMessage($VampireMessage,$Me_user_id);
                    $GroupMessage = self::$Dt->LG->_('VampireDeadWolfGroupMessage',array("{0}" =>$Me_name));
                    HL::SaveMessage($GroupMessage);
                    HL::UserDead($Me_user_id,'eat');
                    HL::SaveGameActivity(['user_id' => $Me_user_id,'fullname'=> $Me_name],'eat',$Detial);
                    if($count_vampire > 1){
                        $VampireTeamMessage = self::$Dt->LG->_('VampireDeadWolfTeam',array("{0}" => $Me_name, "{1}"=> $U_name));
                        HL::SendForVampireTeam($VampireTeamMessage);
                    }
                    return true;
                }
                $VampireMessage = ($count_vampire > 1 ? self::$Dt->LG->_('VampireMessageNoKillTeam',array("{0}" => $Me_name, "{1}" => $U_name)) : self::$Dt->LG->_('VampireMessageNoKill',array("{0}" =>$U_name)));
                HL::SendForVampireTeam($VampireMessage);
                return true;
                break;
            case 'role_shekar':
                $VampireMessaage = ($count_vampire > 1 ? self::$Dt->LG->_('VampireDeadCHTeam',array("{0}" =>  $Me_name, "{1}" => $U_name)) : self::$Dt->LG->_('VampireDeadCH',array("{0}" =>$U_name)));
                $GroupMessage = self::$Dt->LG->_('VampireDeadCHGroupMessage',array("{0}" => $Me_name));
                HL::SaveMessage($GroupMessage);
                HL::UserDead($Me_user_id,'CultHunter');
                HL::SaveGameActivity(['user_id' => $Me_user_id,'fullname'=> $Me_name],'cult',$Detial);
                HL::SendForVampireTeam($VampireMessaage);
                return true;
                break;
            case 'role_kalantar':
                if(R::CheckExit('GamePl:VampireConvert')){
                    // شانس ومپایر رو امتحان میکنیم که میتونه گاز بزنه یا نه
                    if(HL::R(100) < R::Get('GamePl:VampireConvert')){
                        // به طرف خبر میدیم که گازیده شده
                        $PlayerMessage = self::$Dt->LG->_('VampireConvertUser');
                        HL::SendMessage($PlayerMessage,$Detial['user_id']);
                        // به ومپایر ها خبر میدیم که طرفو گازیدن جا خوردنش
                        $VampireMessaage = ($Bloodthirsty ? self::$Dt->LG->_('VampireConvertByBlood',array("{0}" => $U_name,"{1}" => $BloodthirstyName)) : ($count_vampire > 1 ? self::$Dt->LG->_('VampireConvertTeam',array("{0}" => $Me_name, "{1}"=> $U_name)) : self::$Dt->LG->_('VampireConvert', array("{0}" => $U_name))));
                        HL::SendForVampireTeam($VampireMessaage);
                        // خب حالا طرف رو میذاریم توی لیست گاز زده ها
                        HL::VampireConvert($Detial['user_id']);
                        return true;

                    }
                    $MsgUser = self::$Dt->LG->_('eat_Vampire');
                    HL::SendMessage($MsgUser,$selected,'eat_vampire');
                    // در این صورت طرف میمیره
                    $groupMessage = self::$Dt->LG->_('VampireKillPlayer',array("{0}" =>$U_name,"{1}" => self::$Dt->LG->_('user_role',array("{0}"=> self::$Dt->LG->_($Detial['user_role']."_n")))));
                    HL::SaveMessage($groupMessage);
                    HL::UserDead($Detial,'Vampire');
                    HL::SaveGameActivity($Detial,'vampire',['user_id' => $Me_user_id,'fullname'=> $Me_name]);
                    HL::SaveKillVampire($Vampire,$Detial);
                    return true;
                }

                $Bloodthirsty = HL::_getPlayerByRole('role_Bloodthirsty');
                if($Bloodthirsty['user_state'] !== 1){
                    return false;
                }

                if(HL::R(100) < SE::_s('KalanVampireDead')) {
                    $GroupMessage = self::$Dt->LG->_('VampireDeadByKalan',array("{0}" => $U_name,"{1}" => $Me_name));
                    HL::SaveMessage($GroupMessage);
                    HL::UserDead($Me_user_id,'Shot');
                    HL::SaveGameActivity(['user_id' => $Me_user_id,'fullname'=> $Me_name],'shot',$Detial);
                }

                $VampireMessaage = ($count_vampire > 1 ? self::$Dt->LG->_('FindeVampireTeam',array("{0}" => $Me_name, "{1}" => $U_name)) : self::$Dt->LG->_('FindeVampire',array("{0}" => $U_name)));
                HL::SendForVampireTeam($VampireMessaage,$Detial['user_id']);
                $BloodthirstyMessage = self::$Dt->LG->_('FindeVampireBloodMessage',array("{0}" =>$Me_name));
                HL::SendMessage($BloodthirstyMessage,$Bloodthirsty['user_id']);
                $groupMessage = self::$Dt->LG->_('VampireKillPlayer',array("{0}" =>$U_name,"{1}" => self::$Dt->LG->_('user_role',array("{0}"=> self::$Dt->LG->_($Detial['user_role']."_n")))));
                HL::SaveMessage($groupMessage);
                HL::UserDead($Detial,'Vampire');
                HL::SaveKillVampire($Vampire,$Detial);
                R::GetSet(R::Get('GamePl:Night_no'),'GamePl:NotSend:'.$Bloodthirsty['user_id']);
                R::GetSet(true,'GamePl:VampireFinded');
                R::GetSet(SE::_s('BVampireChangeConvet'),'GamePl:VampireConvert');
                R::GetSet(true,'GamePl:Bloodthirsty');
                return true;
                break;
            case 'role_Bloodthirsty':
                $Kalan = HL::_getPlayerByRole('role_kalantar');
                $KalanName = HL::ConvertName($Kalan['user_id'],$Kalan['fullname_game']);
                // به دسته ومپایر ها اطلاع میدیم که کلانتر کیه
                $VampireMessage = ($count_vampire > 1 ? self::$Dt->LG->_('FindVampireMessageTeam',array("{0}" => $Me_name,"{1}"=> $U_name, "{2}"=>$KalanName)) : self::$Dt->LG->_('FindVampireMessage',array( "{0}"=> $U_name, "{1}" => $KalanName)));
                HL::SendForVampireTeam($VampireMessage);
                // به کلانتر میگیم ک هم تیمی هاش اومدن و تونستن هویت کلانتر رو پیدا کنن
                $BloodthirstyMessage = self::$Dt->LG->_('FindeVampireBldMessage',array("{0}" =>$KalanName));
                HL::SendMessage($BloodthirstyMessage,$Detial['user_id']);
                return true;
                break;
            default:
                if(R::CheckExit('GamePl:VampireConvert')) {
                    // شانس ومپایر رو امتحان میکنیم که میتونه گاز بزنه یا نه
                    if (HL::R(100) < R::Get('GamePl:VampireConvert')) {
                        // به طرف خبر میدیم که گازیده شده
                        $PlayerMessage = self::$Dt->LG->_('VampireConvertUser');
                        HL::SendMessage($PlayerMessage, $Detial['user_id']);
                        // به ومپایر ها خبر میدیم که طرفو گازیدن جا خوردنش
                        $VampireMessaage = ($Bloodthirsty ? self::$Dt->LG->_('VampireConvertByBlood',array("{0}" => $U_name,"{1}" => $BloodthirstyName)) : ($count_vampire > 1 ? self::$Dt->LG->_('VampireConvertTeam',array("{0}" => $Me_name, "{1}"=> $U_name)) : self::$Dt->LG->_('VampireConvert', array("{0}" => $U_name))));
                        HL::SendForVampireTeam($VampireMessaage);
                        // خب حالا طرف رو میذاریم توی لیست گاز زده ها
                        HL::VampireConvert($Detial['user_id']);
                        return true;
                    }
                    // اگه اصیل آزاد بود طرف میمیره
                    if(R::CheckExit('GamePl:Bloodthirsty')){
                        // در این صورت طرف میمیره
                        $groupMessage = self::$Dt->LG->_('VampireKillPlayer',array("{0}" =>$U_name,"{1}" => self::$Dt->LG->_('user_role',array("{0}"=> self::$Dt->LG->_($Detial['user_role']."_n")))));
                        HL::SaveMessage($groupMessage);
                        HL::UserDead($Detial,'Vampire');
                        HL::SaveKillVampire($Vampire,$Detial);
                        return true;
                    }
                }
                if (HL::R(100) < SE::_s('VampireChangeNotKill')) {
                    $VampireMessage = ($count_vampire > 1 ? self::$Dt->LG->_('VampireMessageNoKillTeam',array("{0}" => $Me_name, "{1}" => $U_name)) : self::$Dt->LG->_('VampireMessageNoKill',array("{0}" =>$U_name)));
                    HL::SendForVampireTeam($VampireMessage);
                    $PlayerMessage = self::$Dt->LG->_('VampireMessageNoKillPlayer');
                    HL::SendMessage($PlayerMessage, $Detial['user_id']);
                    return false;
                }
                $MsgUser = self::$Dt->LG->_('eat_Vampire');
                HL::SendMessage($MsgUser,$selected,'eat_vampire');
                // در این صورت طرف میمیره
                $groupMessage = self::$Dt->LG->_('VampireKillPlayer',array("{0}" =>$U_name,"{1}" => self::$Dt->LG->_('user_role',array("{0}"=> self::$Dt->LG->_($Detial['user_role']."_n")))));
                HL::SaveMessage($groupMessage);
                HL::UserDead($Detial,'Vampire');
                HL::SaveKillVampire($Vampire,$Detial);
                return true;
                break;
        }
    }
    public static function CheckIceQueean(){
        $IceQueen = HL::_getPlayerByRole('role_IceQueen');
        if($IceQueen == false){
            return false;
        }


        if($IceQueen['user_state'] !== 1){
            return false;
        }

        if(!R::CheckExit('GamePl:Selected:'.$IceQueen['user_id'])){
            return false;
        }

        $selected = R::Get('GamePl:Selected:'.$IceQueen['user_id']);
        $Detial = HL::_getPlayer($selected);

        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);
        if($Detial['user_state'] !== 1){
            $IceQueenMessage = self::$Dt->LG->_('IceQueanDeadPlayer',array("{0}" =>$U_name));
            HL::SendMessage($IceQueenMessage,$IceQueen['user_id']);
            return false;
        }

        if(R::CheckExit('GamePl:role_WhiteWolf:AngelIn:'.$Detial['user_id'])){
            $MessageForPlayer = self::$Dt->LG->_('WolfMessageGourdWhiteWolf');
            HL::SendMessage($MessageForPlayer,$Detial['user_id']);
            $WhiteWolfId = R::Get('GamePl:role_WhiteWolf:AngelIn:'.$Detial['user_id']);
            $MessageForWhiteWolf = self::$Dt->LG->_('WhiteWolfGourdIceQueenMessage',array("{0}" =>$U_name));
            HL::SendMessage($MessageForWhiteWolf,$WhiteWolfId);
            $MsgIceQueen = self::$Dt->LG->_('WhiteWolfGourdIceQueen',array("{0}" =>$U_name));
            HL::SendMessage($MsgIceQueen,$IceQueen['user_id']);
            R::GetSet($U_name,'GamePl:role_WhiteWolf:AngelSaved');
            return true;
        }


        if(R::CheckExit('GamePl:role_angel:AngelIn:'.$Detial['user_id'])){
            $IceQueenAngelBlocked = self::$Dt->LG->_('IceQueenIcDPlayerInAngel',array("{0}" =>$U_name));
            HL::SendMessage($IceQueenAngelBlocked,$IceQueen['user_id']);
            $PlayerMessage = self::$Dt->LG->_('IceQueenIcDPlayerAngelMessagePL');
            HL::SendMessage($PlayerMessage,$Detial['user_id']);
            $AngelId = R::Get('GamePl:role_angel:AngelIn:'.$Detial['user_id']);
            $AngelMessage =  self::$Dt->LG->_('IceQueenIcDPlayerAngelMessageANG',array("{0}" =>$U_name));
            HL::SendMessage($AngelMessage,$AngelId);
            return true;
        }

        // اگه برای دومین شب میخواست منجمد شه طرف میمیره
        if(R::CheckExit('GamePl:IceQueenIced:'.$Detial['user_id'])){
            $PlayerMessage = self::$Dt->LG->_('IceQueenDeadPlayerTowNight');
            HL::SendMessage($PlayerMessage,$Detial['user_id']);
            $GroupMessage =  self::$Dt->LG->_('IceQueenDeadPlayerGroupMsg',array("{0}" =>$U_name,"{1}" => self::$Dt->LG->_('user_role',array("{0}" =>self::$Dt->LG->_($Detial['user_role']."_n")))));
            HL::SaveMessage($GroupMessage);
            HL::UserDead($Detial,'IceQueen');
            HL::SaveGameActivity($Detial,'ice',$IceQueen);
            R::DelKey('GamePl:IceQueenIced*');
            return true;
        }

        $PlayerMessage = self::$Dt->LG->_('IceQueenIcDPlayer');
        HL::SendMessage($PlayerMessage,$Detial['user_id']);
        $IceQueenMessage = self::$Dt->LG->_('IceQueenIcDPlayerOk',array("{0}" =>$U_name));
        HL::SendMessage($IceQueenMessage,$IceQueen['user_id']);

        R::GetSet(true,'GamePl:IceQueenIced');
        R::rpush(true,'GamePl:IceQueenIced:'.$Detial['user_id']);
        R::GetSet(R::Get('GamePl:Night_no') + 1,'GamePl:NotSend:'.$Detial['user_id']);
        return true;
    }

    public static function CheckInHomePlayer($user_id,$link,$Fire){
        $InhomeList = R::Keys('GamePl:UserInHome:*');

        foreach ($InhomeList as $row){
            $Ex = explode(':',$row);
            if(isset($Ex['4'])){
                continue;
            }
            $Key = "{$Ex['1']}:{$Ex['2']}:{$Ex['3']}";
            $Get = R::Get($Key);

            if($Get == $user_id){
                $PlayerName = R::Get($Key.':name');

                $PlayerRole =  R::Get($Key.':role');
                $GroupMessage = self::$Dt->LG->_('FireFighterPlayerInHomeDead',array("{0}" => $PlayerName,"{1}" => $link, "{2}" => self::$Dt->LG->_('user_role',array("{0}" =>$PlayerRole))));
                HL::SaveMessage($GroupMessage);
                $PlayerMessage = self::$Dt->LG->_('FireFighterPKIP',array("{0}" =>$link));
                HL::SendMessage($PlayerMessage,$Ex['3']);
                HL::UserDead($Ex['3'],'fireFighter');
                HL::SaveGameActivity(['user_id' =>$Ex['3'] ,'fullname'=> $PlayerName ],'fire',$Fire);
            }
        }
    }
    public static function CheckFireFighter(){
        $Firefighter = HL::_getPlayerByRole('role_Firefighter');
        if($Firefighter == false){
            return false;
        }
        if($Firefighter['user_state'] !== 1){
            return false;
        }

        if(R::CheckExit('GamePl:FirefighterOk')){
            $List = R::LRange(0,-1,'GamePl:FirefighterList');

            $Re = [];
            foreach ($List as $data){
                $row = json_decode($data,true);
                $Detial = HL::_getPlayer($row['user_id']);
                if($Detial['user_state'] !== 1){
                    continue;
                }
                if(R::CheckExit('GamePl:role_WhiteWolf:AngelIn:'.$row['user_id'])){
                    $MessageForPlayer = self::$Dt->LG->_('WolfMessageGourdWhiteWolf');
                    HL::SendMessage($MessageForPlayer,$row['user_id']);
                    $WhiteWolfId = R::Get('GamePl:role_WhiteWolf:AngelIn:'.$row['user_id']);
                    $MessageForWhiteWolf = self::$Dt->LG->_('WhiteWolfGourdFireFighter',array("{0}" => $row['link']));
                    HL::SendMessage($MessageForWhiteWolf,$WhiteWolfId);
                    $MessageForFireFighter = self::$Dt->LG->_('WhiteWolfGourdKnightMessage',array("{0}" =>$row['link']));
                    HL::SendMessage($MessageForFireFighter,$Firefighter['user_id']);
                    continue;
                }
                if(R::CheckExit('GamePl:role_angel:AngelIn:'.$row['user_id'])){
                    $MessageForPlayer = self::$Dt->LG->_('AngelInHomeForPlayer');
                    HL::SendMessage($MessageForPlayer,$row['user_id']);
                    $AngelId = R::Get('GamePl:role_angel:AngelIn:'.$row['user_id']);
                    $MessageForAngel = self::$Dt->LG->_('AngelInHomeForAngel',array("{0}" =>$row['link']));
                    HL::SendMessage($MessageForAngel,$AngelId);
                    $MessageForFireFighter = self::$Dt->LG->_('AngelInHomeForFireFighter',array("{0}" =>$row['link']));
                    HL::SendMessage($MessageForFireFighter,$Firefighter['user_id']);
                    R::GetSet($row['link'],'GamePl:role_angel:AngelSaved');
                    continue;
                }



                $MessageForPlayer = self::$Dt->LG->_('FireFighterMessageForPlayer');
                HL::SendMessage($MessageForPlayer,$row['user_id']);

                if(count($List)  < 3) {
                    $GroupMessage = self::$Dt->LG->_('FireFighterKillPlayerGroupMessage', array("{0}" => $row['link'], "{1}" => self::$Dt->LG->_('user_role', array("{0}" => self::$Dt->LG->_($Detial['user_role']  . "_n")))));
                    HL::SaveMessage($GroupMessage);
                }else{
                    array_push($Re,$row['link']." - ".self::$Dt->LG->_('user_role', array("{0}" => self::$Dt->LG->_($Detial['user_role'] . "_n"))));
                }

                self::CheckInHomePlayer($row['user_id'],$row['link'],$Firefighter);
                HL::UserDead($row['user_id'],'fireFighter');
                HL::SaveGameActivity($Detial,'fire',$Firefighter);
            }

            if($Re){
                $GroupMessage = self::$Dt->LG->_('FireFighterKillPlayerGroupMessageK',array("{0}" =>implode(PHP_EOL,$Re)));
                HL::SaveMessage($GroupMessage);
            }

            R::Del('GamePl:FirefighterList');
            R::Del('GamePl:FirefighterOk');
            return true;
        }

        if(!R::CheckExit('GamePl:Selected:'.$Firefighter['user_id'])){
            return false;
        }


        $selected = R::Get('GamePl:Selected:'.$Firefighter['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);
        R::rpush(['user_id'=>$Detial['user_id'],'link'=>$U_name,'role'=>$Detial['user_role']],'GamePl:FirefighterList','json');

        $FirefighterMessage = self::$Dt->LG->_('FireFighterOk',array("{0}" => $U_name));
        HL::SendMessage($FirefighterMessage,$Firefighter['user_id']);
        return true;
    }
    public static function CheckJoker(){
        $Joker = HL::_getPlayerByRole('role_Joker');
        if($Joker == false){
            return false;
        }
        if($Joker['user_state'] !== 1){
            return false;
        }
        if(!R::CheckExit('GamePl:Selected:'.$Joker['user_id'])){
            return false;
        }
        $selected = R::Get('GamePl:Selected:'.$Joker['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);

        $FindCount = (R::CheckExit('GamePl:FindedBook') ? (int) R::Get('GamePl:FindedBook') : 0);

        switch ($Detial['user_role']){
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_Wolfx':
            case 'role_WolfAlpha':
            case 'role_Qatel':
            case 'role_hilda':
            case 'role_Archer':
                $Harly = HL::_getPlayerByRole('role_Harly');
                if(!$Harly){
                    $PlayerMessage = self::$Dt->LG>_('PlayerMessageWhenKillByJoker');
                    HL::SendMessage($PlayerMessage,$Detial['user_id']);
                    $GroupMessage = self::$Dt->LG->_("GroupMessageWhenKillEnemy",array("{0}" => $U_name,"{1}" => self::$Dt->LG->_("user_role",array("{0}" =>self::$Dt->LG->_($Detial['user_role']."_n")))));
                    HL::SaveMessage($GroupMessage);
                    HL::UserDead($Detial,'Joker');
                }
                if(R::CheckExit('GamePl:BookIn:'.$Detial['user_id'])){
                    $JokerMessage = self::$Dt->LG->_('SuccessFindJoker',$U_name);
                    HL::SendMessage($JokerMessage,$Joker['user_id']);
                    R::GetSet($FindCount + 1,'GamePl:FindedBook');
                    return  true;
                }
                 $JokerMessage = self::$Dt->LG->_('FiledFindJoker',$U_name);
                 HL::SendMessage($JokerMessage,$Joker['user_id']);
                 return  true;
            break;
            default:
                if(R::CheckExit('GamePl:BookIn:'.$Detial['user_id'])){
                    $JokerMessage = self::$Dt->LG->_('SuccessFindJoker',$U_name);
                    HL::SendMessage($JokerMessage,$Joker['user_id']);
                    R::GetSet($FindCount + 1,'GamePl:FindedBook');
                    return  true;
                }
                $JokerMessage = self::$Dt->LG->_('FiledFindJoker',$U_name);
                HL::SendMessage($JokerMessage,$Joker['user_id']);
                return  true;
            break;
        }


    }
    public static function CheckHarly(){
        $Harly = HL::_getPlayerByRole('role_Harly');
        if($Harly == false){
            return false;
        }
        if($Harly['user_state'] !== 1){
            return false;
        }
        if(!R::CheckExit('GamePl:Selected:'.$Harly['user_id'])){
            return false;
        }
        $selected = R::Get('GamePl:Selected:'.$Harly['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);

        $FindCount = (R::CheckExit('GamePl:FindedBook') ? (int) R::Get('GamePl:FindedBook') : 0);

        switch ($Detial['user_role']){
            default:
                if(R::CheckExit('GamePl:BookIn:'.$Detial['user_id'])){
                    $HarlyMessage = self::$Dt->LG->_('SuccessHarlyFind',$U_name);
                    HL::SendMessage($HarlyMessage,$Harly['user_id']);
                    R::GetSet($FindCount + 1,'GamePl:FindedBook');
                    return  true;
                }
                $HarlyMessage = self::$Dt->LG->_('FiledHarlyFind',$U_name);
                HL::SendMessage($HarlyMessage,$Harly['user_id']);
                return  true;
            break;
        }


    }
    public static function CheckKnight(){
        $Knight = HL::_getPlayerByRole('role_Knight');
        if($Knight == false){
            return false;
        }
        if($Knight['user_state'] !== 1){
            return false;
        }
        if(!R::CheckExit('GamePl:Selected:'.$Knight['user_id'])){
            return false;
        }
        $KnightName = HL::ConvertName($Knight['user_id'],$Knight['fullname_game']);
        $selected = R::Get('GamePl:Selected:'.$Knight['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);

        // اگه مرده بود  طرف چک کردنو بیخیال میشیم،به افسونگر میگیم طرف مردس
        if($Detial['user_state'] !== 1){
            $KnightMessage = self::$Dt->LG->_('KnightPlayerIsDeadSee',array("{0}" =>  $U_name));
            HL::SendMessage($KnightMessage,$Knight['user_id']);
            return false;
        }

        if(R::CheckExit('GamePl:HuntsmanTraps:'.$Detial['user_id'])){
            if(HL::R(100) >= 50){
                $Huntsman = HL::_getPlayerByRole('role_Huntsman');
                if($Huntsman){
                    if($Huntsman['user_state'] == 1){

                        // پیام برای هانتسمن
                        $HuntsManMessage = self::$Dt->LG->_('HuntsmanMessageKillPlayer',array("{0}" =>$KnightName));
                        HL::SendMessage($HuntsManMessage,$Huntsman['user_id']);
                        // پیام برای شخصی که گرفتار شد
                        $userMessage = self::$Dt->LG->_('HuntsmanKillPlayerMessage',array("{0}" =>$U_name));
                        HL::SendMessage($userMessage, $Knight['user_id']);

                        // پیام گروه
                        $groupMessage= self::$Dt->LG->_('HuntsmanKillPlayerGroupMesssage',array("{0}" =>$KnightName)).self::$Dt->LG->_('user_role',array("{0}" =>self::$Dt->LG->_($Knight['user_role']."_n")));
                        HL::SaveMessage($groupMessage);
                        // پشتن پلیر
                        HL::UserDead($Knight,'HuntsmanKill');
                        HL::SaveGameActivity($Knight,'hunts',$Huntsman);
                        // حذف تله
                        R::Del('GamePl:HuntsmanTraps:'.$Detial['user_id']);

                    }
                }else{
                    R::Del('GamePl:HuntsmanTraps:'.$Detial['user_id']);
                }
            }
        }


        $CheckPhoenix = HL::CheckPhoenixHeal($Detial);
        if($CheckPhoenix){
            $PlayerMessage = self::$Dt->LG->_('MassageAttack');
            HL::SendMessage($PlayerMessage,$Detial['user_id']);
            $AttackerMessage = self::$Dt->LG->_('MessageForKnight',array("{0}" => $U_name));
            HL::SendMessage($AttackerMessage,$Knight['user_id']);
            return  true;
        }


        switch ($Detial['user_role']){
            case 'role_Sweetheart':
                if(R::Get('GamePl:SweetheartLove:team') == "Knight"){
                    $KnightMessage = self::$Dt->LG->_('KnightNoKillUser',array("{0}" =>$U_name));
                    HL::SendMessage($KnightMessage,$Knight['user_id']);
                    return true;
                }
                $KnightMessage = self::$Dt->LG->_('MsgPlayerKNLoved',array("{0}" =>$U_name));
                HL::SendMessage($KnightMessage,$Knight['user_id']);
                HL::LoverBYSweetheart($Knight['user_id'],'Knight');
                return true;
                break;
            case 'role_forestQueen':
                if(R::CheckExit('GamePl:role_forestQueen:AlphaDead') == false){
                    $KnightMessage = self::$Dt->LG->_('KnightNoKillUser',array("{0}" =>$U_name));
                    HL::SendMessage($KnightMessage,$Knight['user_id']);
                }

                if(R::CheckExit('GamePl:role_WhiteWolf:AngelIn:'.$Detial['user_id'])){
                    $MessageForPlayer = self::$Dt->LG->_('WolfMessageGourdWhiteWolf');
                    HL::SendMessage($MessageForPlayer,$Detial['user_id']);
                    $WhiteWolfId = R::Get('GamePl:role_WhiteWolf:AngelIn:'.$Detial['user_id']);
                    $MessageForWhiteWolf = self::$Dt->LG->_('WhiteWolfGourdKnight',array("{0}" =>$U_name));
                    HL::SendMessage($MessageForWhiteWolf,$WhiteWolfId);
                    $MsgKiller = self::$Dt->LG->_('WhiteWolfGourdKnightMessage',array("{0}" =>$U_name));
                    HL::SendMessage($MsgKiller,$Knight['user_id']);
                    R::GetSet($U_name,'GamePl:role_WhiteWolf:AngelSaved');
                    return true;
                }

                $KnightMessage = self::$Dt->LG->_('KnightKillPlayer',array("{0}" =>$U_name));
                HL::SendMessage($KnightMessage,$Knight['user_id']);
                $PlayerMessage = self::$Dt->LG->_('KnightKillPlayerMessage');
                HL::SendMessage($PlayerMessage,$Detial['user_id']);
                $GroupMessage = self::$Dt->LG->_('KnightKillPlayerGroupMessage',array("{0}" => $U_name, "{1}"=> self::$Dt->LG->_("user_role",array("{0}" =>self::$Dt->LG->_($Detial['user_role']."_n")))));
                HL::SaveMessage($GroupMessage);
                HL::UserDead($Detial,'Knight_kill');
                HL::SaveGameActivity($Detial,'knight',$Knight);
                break;
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_Wolfx':
            case 'role_WolfAlpha':

                if(R::CheckExit('GamePl:role_WhiteWolf:AngelIn:'.$Detial['user_id'])){
                    $MessageForPlayer = self::$Dt->LG->_('WolfMessageGourdWhiteWolf');
                    HL::SendMessage($MessageForPlayer,$Detial['user_id']);
                    $WhiteWolfId = R::Get('GamePl:role_WhiteWolf:AngelIn:'.$Detial['user_id']);
                    $MessageForWhiteWolf = self::$Dt->LG->_('WhiteWolfGourdKnight',array("{0}" =>$U_name));
                    HL::SendMessage($MessageForWhiteWolf,$WhiteWolfId);
                    $MsgKiller = self::$Dt->LG->_('WhiteWolfGourdKnightMessage',array("{0}" =>$U_name));
                    HL::SendMessage($MsgKiller,$Knight['user_id']);
                    R::GetSet($U_name,'GamePl:role_WhiteWolf:AngelSaved');
                    return true;
                }

                $KnightMessage = self::$Dt->LG->_('KnightKillPlayer',array("{0}" =>$U_name));
                HL::SendMessage($KnightMessage,$Knight['user_id']);
                $PlayerMessage = self::$Dt->LG->_('KnightKillPlayerMessage');
                HL::SendMessage($PlayerMessage,$Detial['user_id']);
                $GroupMessage = self::$Dt->LG->_('KnightKillPlayerGroupMessage',array("{0}" => $U_name, "{1}"=> self::$Dt->LG->_("user_role",array("{0}"=> self::$Dt->LG->_($Detial['user_role']."_n")))));
                HL::SaveMessage($GroupMessage);
                HL::UserDead($Detial,'Knight_kill');
                HL::SaveGameActivity($Detial,'knight',$Knight);
                return true;
                break;
            case 'role_Qatel':
            case 'role_Archer':
            case 'role_Vampire':
            case 'role_Bloodthirsty':
                $KnightMessage = self::$Dt->LG->_('KnightKillPlayer',array("{0}" =>$U_name));
                HL::SendMessage($KnightMessage,$Knight['user_id']);
                $PlayerMessage = self::$Dt->LG->_('KnightKillPlayerMessage');
                HL::SendMessage($PlayerMessage,$Detial['user_id']);
                $GroupMessage = self::$Dt->LG->_('KnightKillPlayerGroupMessage',array("{0}" => $U_name, "{1}"=> self::$Dt->LG->_("user_role",array("{0}"=>self::$Dt->LG->_($Detial['user_role']."_n")))));
                HL::SaveMessage($GroupMessage);
                HL::UserDead($Detial,'Knight_kill');
                HL::SaveGameActivity($Detial,'knight',$Knight);
                return true;
                break;
            case 'role_betaWolf':

                $KnightMessage = self::$Dt->LG->_('KnightKillPlayer',array("{0}" =>$U_name));
                HL::SendMessage($KnightMessage,$Knight['user_id']);
                $BetaWolfMessage = self::$Dt->LG->_('betaWolf_knight');
                HL::SendMessage($BetaWolfMessage,$Detial['user_id']);

                $GroupMessage = self::$Dt->LG->_('betaWolf_knightGroupMessage',array("{0}" => $KnightName, "{1}"=> $U_name));
                HL::SaveMessage($GroupMessage);
                HL::UserDead($Detial,'Knight_kill');
                HL::SaveGameActivity($Detial,'knight',$Knight);
                HL::UserDead($Knight,'betaWolf_kill');
                HL::SaveGameActivity($Knight,'BetaWolf',$Detial);
                return true;
            break;
            default:
                $KnightMessage = self::$Dt->LG->_('KnightNoKillUser',array("{0}" =>$U_name));
                HL::SendMessage($KnightMessage,$Knight['user_id']);
                return true;
                break;
        }

        return false;
    }

    public static function SaveAchiveMouse($Detial){
        $Nop = R::NoPerfix();
        $Nop->set('user_mouse:'.$Detial['user_id'],true);

        HL::SavePlayerAchivment($Detial['user_id'],'Mouse_Win');
    }
    public static function CheckMouse(){
        $Mouse = HL::_getPlayerByRole('role_Mouse');
        if($Mouse == false){
            return false;
        }
        if($Mouse['user_state'] !== 1){
            return false;
        }
        if(!R::CheckExit('GamePl:Selected:'.$Mouse['user_id'])){
            return false;
        }
        $MouseName = HL::ConvertName($Mouse['user_id'],$Mouse['fullname_game']);
        $selected = R::Get('GamePl:Selected:'.$Mouse['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);

        // اگه مرده بود  طرف چک کردنو بیخیال میشیم،به افسونگر میگیم طرف مردس
        if($Detial['user_state'] !== 1){
            return false;
        }

        if(R::CheckExit('GamePl:HuntsmanTraps:'.$Detial['user_id'])){
            if(HL::R(100) >= 50){
                $Huntsman = HL::_getPlayerByRole('role_Huntsman');
                if($Huntsman){
                    if($Huntsman['user_state'] == 1){

                        // پیام برای هانتسمن
                        $HuntsManMessage = self::$Dt->LG->_('HuntsmanMessageKillPlayer',$MouseName);
                        HL::SendMessage($HuntsManMessage,$Huntsman['user_id']);
                        // پیام برای شخصی که گرفتار شد
                        $userMessage = self::$Dt->LG->_('HuntsmanKillPlayerMessage',$U_name);
                        HL::SendMessage($userMessage, $Mouse['user_id']);

                        // پیام گروه
                        $groupMessage= self::$Dt->LG->_('HuntsmanKillPlayerGroupMesssage',$MouseName,$MouseName).self::$Dt->LG->_('user_role',self::$Dt->LG->_($Mouse['user_role']."_n"));
                        HL::SaveMessage($groupMessage);
                        // پشتن پلیر
                        HL::UserDead($Mouse,'HuntsmanKill');
                        HL::SaveGameActivity($Mouse,'hunts',$Huntsman);
                        // حذف تله
                        R::Del('GamePl:HuntsmanTraps:'.$Detial['user_id']);

                    }
                }else{
                    R::Del('GamePl:HuntsmanTraps:'.$Detial['user_id']);
                }
            }
        }


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
            case 'role_Firefighter':
            case 'role_IceQueen':
            case 'role_Vampire':
            case 'role_Bloodthirsty':
            case 'role_Qatel':
            case 'role_Archer':
            case 'role_ferqe':
            case 'role_Royce':
            case 'role_monafeq':

                $CultHunter = HL::_getPlayerByRole('role_shekar');

                if($CultHunter) {
                    if($Detial['user_state'] == 1) {
                        $MouseMessage = self::$Dt->LG->_('MouseInD', $U_name);
                        HL::SendMessage($MouseMessage, $Mouse['user_id']);

                        $HunterMessage = self::$Dt->LG->_('CultHunterMessageS', $U_name);
                        HL::SendMessage($MouseMessage, $CultHunter['user_id']);

                        if(R::CheckExit('GamePl:FindManfiMouse')){
                            self::SaveAchiveMouse($Mouse);
                        }else {
                            R::GetSet(1, 'GamePl:FindManfiMouse');
                        }
                        return true;
                    }
                }
                if(R::CheckExit('GamePl:FindManfiMouse')){
                    self::SaveAchiveMouse($Mouse);
                }else {
                    R::GetSet(1, 'GamePl:FindManfiMouse');
                }
                $MouseMessage = self::$Dt->LG->_('CultHunterDead', $U_name);
                HL::SendMessage($MouseMessage, $Mouse['user_id']);
                break;
            default:
                $MouseMessage = self::$Dt->LG->_('MouseInNotD',$U_name);
                HL::SendMessage($MouseMessage,$Mouse['user_id']);
                break;
        }

        return false;
    }

    public static function CheckArcher(){
        $Archer = HL::_getPlayerByRole('role_Archer');
        if($Archer == false){
            return false;
        }
        if($Archer['user_state'] !== 1){
            return false;
        }
        if(!R::CheckExit('GamePl:Selected:'.$Archer['user_id'])){
            return false;
        }
        $ArcherName = HL::ConvertName($Archer['user_id'],$Archer['fullname_game']);
        $selected = R::Get('GamePl:Selected:'.$Archer['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);

        // اگه مرده بود  طرف چک کردنو بیخیال میشیم،به افسونگر میگیم طرف مردس
        if($Detial['user_state'] !== 1){
            $ArcherMessage = self::$Dt->LG->_('ArcherDeadPlayerMessage', array("{0}" => $U_name));
            HL::SendMessage($ArcherMessage,$Archer['user_id']);
            return false;
        }
        $CheckPhoenix = HL::CheckPhoenixHeal($Detial);
        if($CheckPhoenix){
            $PlayerMessage = self::$Dt->LG->_('MassageAttack');
            HL::SendMessage($PlayerMessage,$Detial['user_id']);
            $AttackerMessage = self::$Dt->LG->_('MessageForArcher',array("{0}" => $U_name));
            HL::SendMessage($AttackerMessage,$Archer['user_id']);
            return  true;
        }


        switch ($Detial['user_role']){
            case 'role_Sweetheart':
                if(R::Get('GamePl:SweetheartLove:team') == "archer"){
                    $GroupMessage = self::$Dt->LG->_('ArcherDeadPlayerGroupMessage',array("{0}"=> $U_name,"{1}" => self::$Dt->LG->_('user_role',array("{0}"=> self::$Dt->LG->_($Detial['user_role']."_n")))));
                    HL::SaveMessage($GroupMessage);
                    HL::UserDead($Detial,'Archer_shot');
                    HL::SaveGameActivity($Detial,'archer',$Archer);
                    $PlayerMessage = self::$Dt->LG->_('ArcherDeadPlayer');
                    HL::SendMessage($PlayerMessage,$Detial['user_id']);
                    return true;
                }
                $ArcherMessage = self::$Dt->LG->_('MsgPlayerACLoved',array("{0}"=>$U_name));
                HL::SendMessage($ArcherMessage,$Archer['user_id']);
                HL::LoverBYSweetheart($Archer['user_id'],'archer');
                return true;
                break;
            default:

                if(R::CheckExit('GamePl:role_WhiteWolf:AngelIn:'.$Detial['user_id'])){
                    $MessageForPlayer = self::$Dt->LG->_('WolfMessageGourdWhiteWolf');
                    HL::SendMessage($MessageForPlayer,$Detial['user_id']);
                    $WhiteWolfId = R::Get('GamePl:role_WhiteWolf:AngelIn:'.$Detial['user_id']);
                    $MessageForWhiteWolf = self::$Dt->LG->_('WhiteWolfGourdArcher',array("{0}" => $U_name));
                    HL::SendMessage($MessageForWhiteWolf,$WhiteWolfId);
                    $MsgKiller = self::$Dt->LG->_('WhiteWolfGourdArcherMessage',array("{0}" => $U_name));
                    HL::SendMessage($MsgKiller,$Archer['user_id']);
                    R::GetSet($U_name,'GamePl:role_WhiteWolf:AngelSaved');
                    return true;
                }


                $GroupMessage = self::$Dt->LG->_('ArcherDeadPlayerGroupMessage',array("{0}"=> $U_name,"{1}" => self::$Dt->LG->_('user_role',array("{0}"=> self::$Dt->LG->_($Detial['user_role']."_n")))));
                HL::SaveMessage($GroupMessage);
                HL::UserDead($Detial,'Archer_shot');
                HL::SaveGameActivity($Detial,'archer',$Archer);
                $PlayerMessage = self::$Dt->LG->_('ArcherDeadPlayer');
                HL::SendMessage($PlayerMessage,$Detial['user_id']);
                return true;
                break;
        }
    }
    public static function DeadPlayerNotInHome(){
        $Keys = R::Keys('GamePl:UserInHome:*');

        if($Keys){
            $DeatId = [];
            foreach ($Keys as $key){
                $Ex = explode(':',$key);
                $user_id = $Ex['3'];
                $Player = HL::_getPlayer($user_id);
                if($Player['team'] == "wolf"){
                    continue;
                }

                if(in_array($Player['user_id'],$DeatId)){
                    continue;
                }
                array_push($DeatId,$Player['user_id']);

                $Name = HL::ConvertName($Player['user_id'],$Player['fullname_game']);
                $GroupMessage = self::$Dt->LG->_('forestQueenDeadGroup',array("{0}"=> $Name,"{1}" => self::$Dt->LG->_('user_role',array("{0}"=> self::$Dt->LG->_($Player['user_role']."_n")))));
                HL::SaveMessage($GroupMessage);
                $PlayerMessage = self::$Dt->LG->_('forestQueenDeadPlayer');
                HL::SendMessage($PlayerMessage,$Player['user_id']);
                HL::UserDead($Player,'DeadforestQueen');
            }
        }

        R::Del('GamePl:DeadforestQueen');

    }
    public static function DeadPlayerNotInHomeIsra(){
        $Keys = R::Keys('GamePl:UserInHome:*');

        if($Keys){
            $DeatId = [];
            foreach ($Keys as $key){
                $Ex = explode(':',$key);
                $user_id = $Ex['3'];
                $Player = HL::_getPlayer($user_id);
                if($Player['team'] == "wolf"){
                    continue;
                }

                if(in_array($Player['user_id'],$DeatId)){
                    continue;
                }
                array_push($DeatId,$Player['user_id']);

                $Name = HL::ConvertName($Player['user_id'],$Player['fullname_game']);
                $GroupMessage = self::$Dt->LG->_('Kill_massege_on_group_isra',array("{0}"=> $Name,"{1}" => self::$Dt->LG->_('user_role',array("{0}"=> self::$Dt->LG->_($Player['user_role']."_n")))));
                HL::SaveMessage($GroupMessage);
                $PlayerMessage = self::$Dt->LG->_('Kill_isra_massege_for_dead_one');
                HL::SendMessage($PlayerMessage,$Player['user_id']);
                HL::UserDead($Player,'isra_kill');
            }
        }

        R::Del('GamePl:IsraOk');

    }

    public static function CheckEnchanter(){
        $Enchanter = HL::_getPlayerByRole('role_enchanter');
        if($Enchanter == false){
            return false;
        }
        if($Enchanter['user_state'] !== 1){
            return false;
        }
        if(!R::CheckExit('GamePl:Selected:'.$Enchanter['user_id'])){
            return false;
        }
        $EnchanterName = HL::ConvertName($Enchanter['user_id'],$Enchanter['fullname_game']);
        $selected = R::Get('GamePl:Selected:'.$Enchanter['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);

        // اگه مرده بود  طرف چک کردنو بیخیال میشیم،به افسونگر میگیم طرف مردس
        if($Detial['user_state'] !== 1){
            $EnchanterMessage = self::$Dt->LG->_('EnchanterDeadPlayer',array("{0}" => $U_name));
            HL::SendMessage($EnchanterMessage,$Enchanter['user_id']);
            return false;
        }

        switch ($Detial['user_role']){
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_Wolfx':
            case 'role_WolfAlpha':
                $EnchanterMessage = self::$Dt->LG->_('EnchanterWolfFinde', array("{0}" => $U_name));
                HL::SendMessage($EnchanterMessage,$Enchanter['user_id']);
                return true;
                break;
            default:
                // به افسونگر میگیم طرف فرشته نگهبان داشت و افسونگر میمیره
                if(R::CheckExit('GamePl:role_angel:AngelIn:'.$Detial['user_id'])){
                    $EnchanterMessage = self::$Dt->LG->_('EnchanterDeadAngelInUserHome',array("{0}" => $U_name));
                    HL::SendMessage($EnchanterMessage,$Enchanter['user_id']);
                    $GroupMessage = self::$Dt->LG->_('EnchanterDeadAngelInUserHomeGroupMessage',array("{0}" =>$EnchanterName));
                    HL::SaveMessage($GroupMessage);
                    HL::UserDead($Enchanter,'Enchanter');
                    return true;
                }
                //چک میکنیم طرف از قبل طلسم نشده باشه
                if(R::CheckExit('GamePl:Enchanter')){
                    $EnchedList = R::Sort('GamePl:Enchanter','desc');
                    if(in_array($Detial['user_id'],$EnchedList)){
                        $EnchanterMessage = self::$Dt->LG->_('EnchanterBefore',array("{0}" =>$U_name));
                        HL::SendMessage($EnchanterMessage,$Enchanter['user_id']);
                        return true;
                    }
                }
                // با موفقیت تونست طلسمشو بذاره
                $PlayerMessage = self::$Dt->LG->_('EnchanterSuccessUser');
                HL::SendMessage($PlayerMessage,$Detial['user_id']);
                $EnchanterMessage = self::$Dt->LG->_('EnchanterSuccess',array("{0}" =>$U_name));
                HL::SendMessage($EnchanterMessage,$Enchanter['user_id']);
                R::rpush($Detial['user_id'],'GamePl:Enchanter');
                return true;
                break;
        }

        return false;

    }

    public static function GetJado(){

        $Jado = HL::_getPlayerByRole('role_WolfJadogar');
        if($Jado == false){
            return false;
        }
        if($Jado['user_state'] !== 1){
            return false;
        }
        if(!R::CheckExit('GamePl:Selected:'.$Jado['user_id'])){
            return false;
        }
        $NegativName = HL::ConvertName($Jado['user_id'],$Jado['fullname_game']);
        $selected = R::Get('GamePl:Selected:'.$Jado['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);



        switch ($Detial['user_role']){
            case 'role_pishgo':
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_Wolfx':
            case 'role_Honey':
            case 'role_enchanter':
            case 'role_WhiteWolf':
            case 'role_forestQueen':
            case 'role_WolfAlpha':
            case 'role_Vampire':
                $JadoMessage = self::$Dt->LG->_('SeerSees',array("{0}" => $U_name,"{1}"=> self::$Dt->LG->_($Detial['user_role']."_n")));
                HL::SendMessage($JadoMessage,$Jado['user_id']);
                return true;
                break;
            default:
                $JadoMessage = self::$Dt->LG->_('SorcererOther',array("{0}" =>$U_name));
                HL::SendMessage($JadoMessage,$Jado['user_id']);
                return true;
                break;
        }

    }

    public static function GetNegativ(){
        $Negativ = HL::_getPlayerByRole('role_ngativ');
        if($Negativ == false){
            return false;
        }
        if($Negativ['user_state'] !== 1){
            return false;
        }
        if(!R::CheckExit('GamePl:Selected:'.$Negativ['user_id'])){
            return false;
        }
        $selected = R::Get('GamePl:Selected:'.$Negativ['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);


        $RandomUser = HL::GetRoleRandom([$Detial['user_role'],'role_ngativ']);
        if(!$RandomUser){
            $NegativMessage = self::$Dt->LG->_('No_role');
            HL::SendMessage($NegativMessage,$Negativ['user_id']);
            return true;
        }

        switch ($RandomUser['user_role']){
            case 'role_Wolfx':
            case 'role_Lucifer':
            case 'role_Khaen':
                $role = "role_rosta_n";
                $NegativMessage = self::$Dt->LG->_('NegSeerSees', array("{0}" => $U_name,"{1}"=>  self::$Dt->LG->_($role)));
                HL::SendMessage($NegativMessage,$Negativ['user_id']);
                return true;
                break;
            case 'role_Gorgname':
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_WolfAlpha':
                $role = "role_WolfGorgine_n";
                $NegativMessage = self::$Dt->LG->_('NegSeerSees', array("{0}" => $U_name,"{1}"=>  self::$Dt->LG->_($role)));
                HL::SendMessage($NegativMessage,$Negativ['user_id']);
                return true;
                break;
            default:
                $role = $RandomUser['user_role'] ?? "role_rosta";
                $NegativMessage = self::$Dt->LG->_('NegSeerSees', array("{0}" => $U_name,"{1}"=>  self::$Dt->LG->_($role."_n")));
                HL::SendMessage($NegativMessage,$Negativ['user_id']);
                return true;
                break;
        }

    }

    public static function GetAhmaqSeeGroup(){
        $Fools = HL::_getPlayerByRoleGroup('role_ahmaq');
        if($Fools == false){
            return false;
        }
        foreach ($Fools as $Fool) {
            if ($Fool['user_state'] !== 1) {
                continue;
            }
            if (!R::CheckExit('GamePl:Selected:' . $Fool['user_id'])) {
                continue;
            }
            $selected = R::Get('GamePl:Selected:' . $Fool['user_id']);
            $Detial = HL::_getPlayer($selected);
            $U_name = HL::ConvertName($Detial['user_id'], $Detial['fullname_game']);


            $RandomUser = HL::GetRoleRandom([$Detial['user_role'], 'role_pishgo']);


            if ($Detial['user_role'] == "role_Nazer") {
                if (HL::R(100) > 50) {

                    HL::SavePlayerAchivment($Fool['user_id'], 'Am_I_Your_Seer');

                    $FollMessage = self::$Dt->LG->_('Searsee',array("{0}" => $U_name,"{1}"=>  self::$Dt->LG->_($Detial['user_role'] . "_n")));
                    HL::SendMessage($FollMessage, $Fool['user_id']);
                    continue;
                }
            }
            switch ($RandomUser['user_role']) {
                case 'role_Wolfx':
                case 'role_Lucifer':
                case 'role_Khaen':
                    $role = "role_rosta_n";
                    $FollMessage = self::$Dt->LG->_('Searsee',array("{0}" => $U_name,"{1}"=> self::$Dt->LG->_($role)));
                    HL::SendMessage($FollMessage, $Fool['user_id']);
                    break;
                case 'role_Gorgname':
                case 'role_WolfTolle':
                case 'role_WolfGorgine':
                case 'role_WolfAlpha':
                    $role = "role_WolfGorgine_n";
                    $FollMessage = self::$Dt->LG->_('Searsee', array("{0}" => $U_name,"{1}"=> self::$Dt->LG->_($role)));
                    HL::SendMessage($FollMessage, $Fool['user_id']);
                    break;
                case 'role_pishgo':
                    $role = "role_ahmaq_n";
                    $FollMessage = self::$Dt->LG->_('Searsee',array("{0}" => $U_name,"{1}"=> self::$Dt->LG->_($role)));
                    HL::SendMessage($FollMessage, $Fool['user_id']);
                    break;
                default:
                    $role = $RandomUser['user_role'] ?? "role_rosta";
                    $FollMessage = self::$Dt->LG->_('Searsee',array("{0}" => $U_name,"{1}"=> self::$Dt->LG->_($role . "_n")));
                    HL::SendMessage($FollMessage, $Fool['user_id']);
                    break;
            }
        }

        return true;

    }
    public static function GetAhmaqSee(){
        $Fool = HL::_getPlayerByRole('role_ahmaq');
        if($Fool == false){
            return false;
        }
        if($Fool['user_state'] !== 1){
            return false;
        }
        if(!R::CheckExit('GamePl:Selected:'.$Fool['user_id'])){
            return false;
        }
        $selected = R::Get('GamePl:Selected:'.$Fool['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);


        $RandomUser = HL::GetRoleRandom([$Detial['user_role'],'role_pishgo']);


        if($Detial['user_role'] == "role_Nazer"){
            if(HL::R(100) > 50){

                HL::SavePlayerAchivment($Fool['user_id'],'Am_I_Your_Seer');

                $FollMessage = self::$Dt->LG->_('Searsee', array("{0}" => $U_name,"{1}"=>  self::$Dt->LG->_($Detial['user_role']."_n")));
                HL::SendMessage($FollMessage, $Fool['user_id']);
                return true;
            }
        }
        switch ($RandomUser['user_role']){
            case 'role_Wolfx':
            case 'role_Lucifer':
            case 'role_Khaen':
                $role = "role_rosta_n";
                $FollMessage = self::$Dt->LG->_('Searsee', array("{0}" => $U_name,"{1}"=> self::$Dt->LG->_($role)));
                HL::SendMessage($FollMessage, $Fool['user_id']);
                return true;
                break;
            case 'role_Gorgname':
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_WolfAlpha':
                $role = "role_WolfGorgine_n";
                $FollMessage = self::$Dt->LG->_('Searsee', array("{0}" => $U_name,"{1}"=> self::$Dt->LG->_($role)));
                HL::SendMessage($FollMessage, $Fool['user_id']);
                return true;
                break;
            case 'role_pishgo':
                $role = "role_ahmaq_n";
                $FollMessage = self::$Dt->LG->_('Searsee', array("{0}" => $U_name,"{1}"=> self::$Dt->LG->_($role)));
                HL::SendMessage($FollMessage, $Fool['user_id']);
                return true;
                break;
            default:
                $role = $RandomUser['user_role'] ?? "role_rosta";
                $FollMessage = self::$Dt->LG->_('Searsee',array("{0}" => $U_name,"{1}"=> self::$Dt->LG->_($role."_n")));
                HL::SendMessage($FollMessage, $Fool['user_id']);
                return true;
                break;
        }


    }
    public static function GetSearSee(){
        $Sear = HL::_getPlayerByRole('role_pishgo');
        if($Sear == false){
            return false;
        }
        if($Sear['user_state'] !== 1){
            return false;
        }
        if(!R::CheckExit('GamePl:Selected:'.$Sear['user_id'])){
            return false;
        }
        $SearName = HL::ConvertName($Sear['user_id'],$Sear['fullname_game']);
        $selected = R::Get('GamePl:Selected:'.$Sear['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);


        if(R::CheckExit('GamePl:HoneyUser:'.$Detial['user_id'])){
            $HoneyChangeRole = "role_WolfGorgine";
        }
        switch ($Detial['user_role']){
            case 'role_Wolfx':
            case 'role_Lucifer':
            case 'role_Khaen':
                $role = $HoneyChangeRole ?? "role_rosta";
                $SearMessage = self::$Dt->LG->_('Searsee', array("{0}" => $U_name,"{1}"=>self::$Dt->LG->_($role."_n")));
                HL::SendMessage($SearMessage, $Sear['user_id']);
                return true;
            case 'role_Gorgname':
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_WolfAlpha':
                $role = "role_WolfGorgine_n";
                $SearMessage = self::$Dt->LG->_('Searsee',array("{0}" => $U_name,"{1}"=> self::$Dt->LG->_($role)));
                HL::SendMessage($SearMessage, $Sear['user_id']);
                return true;
                break;
            default:

                // در نقش پیشگو ناظر رو استعلام کنید
                if($Detial['user_role'] == "role_Nazer"){
                    HL::SavePlayerAchivment($Sear['user_id'],'Should_Have_Known');
                }

                $role = $HoneyChangeRole ?? $Detial['user_role'] ?? "role_rosta";
                $SearMessage = self::$Dt->LG->_('Searsee', array("{0}" => $U_name,"{1}"=> self::$Dt->LG->_($role . "_n")));
                HL::SendMessage($SearMessage, $Sear['user_id']);
                return true;
                break;
        }


    }

    public static function GetAngel(){
        $Angel = HL::_getPlayerByRole('role_Fereshte');
        // اگر فرشته نگهبان نبود،مسلما باید بیخیال بشیم
        if($Angel == false){
            return false;
        }
        // اگر فرشته نگهبان مرده بود بازم باید بیخیال بشیم مسلما
        if($Angel['user_state'] !== 1){
            return false;
        }
        // اگر فرشته نگهبان انتخابی نکرد بازم لزومی نداره چک کنیم
        if(!R::CheckExit('GamePl:Selected:'.$Angel['user_id'])){
            return false;
        }

        // خب حالا مطمعن شدیم فرشته نگهبان هم هست،هم زندس، هم انتخابشو انجام داده حالا چک کردن رو شروع میکنیم
        $AngelName = HL::ConvertName($Angel['user_id'],$Angel['fullname_game']);
        $selected = R::Get('GamePl:Selected:'.$Angel['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);

        // اگه مرده بود  طرف چک کردنو بیخیال میشیم
        if($Detial['user_state'] !== 1){
            return false;
        }

        if(R::CheckExit('GamePl:HuntsmanTraps:'.$Detial['user_id'])){
            if(HL::R(100) >= 50){
                $Huntsman = HL::_getPlayerByRole('role_Huntsman');
                if($Huntsman){
                    if($Huntsman['user_state'] == 1){

                        // پیام برای هانتسمن
                        $HuntsManMessage = self::$Dt->LG->_('HuntsmanMessageKillPlayer',array("{0}" => $AngelName));
                        HL::SendMessage($HuntsManMessage,$Huntsman['user_id']);
                        // پیام برای شخصی که گرفتار شد
                        $userMessage = self::$Dt->LG->_('HuntsmanKillPlayerMessage', array("{0}" => $U_name));
                        HL::SendMessage($userMessage, $Angel['user_id']);

                        // پیام گروه
                        $groupMessage= self::$Dt->LG->_('HuntsmanKillPlayerGroupMesssage', array("{0}" => $AngelName)).self::$Dt->LG->_('user_role',array("{0}" => self::$Dt->LG->_($Angel['user_role']."_n")));
                        HL::SaveMessage($groupMessage);
                        // پشتن پلیر
                        HL::UserDead($Angel,'HuntsmanKill');
                        HL::SaveGameActivity($Detial,'huns',$Huntsman);
                        // حذف تله
                        R::Del('GamePl:HuntsmanTraps:'.$Detial['user_id']);

                    }
                }else{
                    R::Del('GamePl:HuntsmanTraps:'.$Detial['user_id']);
                }
            }
        }

        switch ($Detial['user_role']) {
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_Wolfx':
            case 'role_WolfAlpha':
                $GroupMessage = self::$Dt->LG->_('GAGuardedWolf',array("{0}" =>$AngelName));
                HL::SaveMessage($GroupMessage);
                HL::UserDead($Angel, 'eat');
                HL::SaveGameActivity($Angel,'eat',$Detial);
                $AngelMessage = self::$Dt->LG->_('GuardWolf');
                HL::SendMessage($AngelMessage, $Angel['user_id']);
                R::DelKey('GamePl:role_angel:AngelIn:*');
                return true;
                break;
            case 'role_Qatel':
                $GroupMessage = self::$Dt->LG->_('GAGuardedKiller',array("{0}" =>$AngelName));
                HL::SaveMessage($GroupMessage);
                HL::UserDead($Angel, 'kill');
                HL::SaveGameActivity($Angel,'kill',$Detial);
                $AngelMessage = self::$Dt->LG->_('GuardKiller');
                HL::SendMessage($AngelMessage, $Angel['user_id']);
                R::DelKey('GamePl:role_angel:AngelIn:*');
                return true;
                break;
             case 'role_hilda':
                 if(!R::CheckExit('GamePl:KillerIsKillHildaInGame')) {
                     $GetNameSaved = R::Get('GamePl:role_angel:AngelNameSaved');
                     $MessageAngel = (R::CheckExit('GamePl:role_angel:AngelSaved') == true ? self::$Dt->LG->_('GuardSaved',array("{0}" =>$GetNameSaved)) : self::$Dt->LG->_('GuardNoAttack',array("{0}" =>$GetNameSaved)) );
                     HL::SendMessage($MessageAngel, $Angel['user_id']);
                     R::DelKey('GamePl:role_angel:AngelIn:*');
                     R::Del('GamePl:role_angel:AngelSaved');
                     R::Del('GamePl:role_angel:AngelNameSaved');
                 }
                 $GroupMessage = self::$Dt->LG->_('HillerInHilda',array("{0}" =>$AngelName));
                 HL::SaveMessage($GroupMessage);
                 HL::UserDead($Angel, 'kill');
                 HL::SaveGameActivity($Angel,'kill',$Detial);
                 $AngelMessage = self::$Dt->LG->_('GuardHilda');
                 HL::SendMessage($AngelMessage, $Angel['user_id']);
                 R::DelKey('GamePl:role_angel:AngelIn:*');

                return true;
                break;
            case 'role_Bloodthirsty':
                if(R::CheckExit('GamePl:VampireFinded')) {
                    if(HL::R(100) >= 50){

                        $MessageAngel  = self::$Dt->LG->_('bloodConvertFereshte',array("{0}" =>$U_name));
                        HL::SendMessage($MessageAngel, $Angel['user_id']);

                        $VampireMessaage = self::$Dt->LG->_('bloodConvertMessage',array("{0}" =>$AngelName, "{1}" => $U_name));
                        HL::SendForVampireTeam($VampireMessaage);
                        // خب حالا طرف رو میذاریم توی لیست گاز زده ها
                        HL::VampireConvert($Detial['user_id']);
                        return true;
                    }
                    $MessageAngel  = self::$Dt->LG->_('BloodKillAngelMessage',array("{0}" =>$U_name));
                    HL::SendMessage($MessageAngel, $Angel['user_id']);
                    HL::UserDead($Angel, 'BloodVampire');
                    HL::SaveGameActivity($Angel,'vampire',$Detial);
                    $GroupMessage = self::$Dt->LG->_('BloodKillAngel',array("{0}" =>$AngelName));
                    HL::SaveMessage($GroupMessage);

                    return true;
                }
                $MessageAngel  = self::$Dt->LG->_('GuardEmptyHouse',array("{0}" =>$U_name));
                HL::SendMessage($MessageAngel, $Angel['user_id']);
                return true;
                break;
            default:
                $GetNameSaved = R::Get('GamePl:role_angel:AngelNameSaved');
                $MessageAngel = (R::CheckExit('GamePl:role_angel:AngelSaved') == true ? self::$Dt->LG->_('GuardSaved',array("{0}" =>$GetNameSaved)) : self::$Dt->LG->_('GuardNoAttack',array("{0}" =>$GetNameSaved)) );
                HL::SendMessage($MessageAngel, $Angel['user_id']);
                R::DelKey('GamePl:role_angel:AngelIn:*');
                R::Del('GamePl:role_angel:AngelSaved');
                R::Del('GamePl:role_angel:AngelNameSaved');
                return  true;
                break;
        }

    }
    public static function GetFaheshe(){
        $Faheshe = HL::_getPlayerByRole('role_faheshe');
        // اگر فاحشه نبود،مسلما باید بیخیال بشیم
        if($Faheshe == false){
            return false;
        }
        // اگر فاحشه مرده بود بازم باید بیخیال بشیم مسلما
        if($Faheshe['user_state'] !== 1){
            return false;
        }
        // اگر فاحشه انتخابی نکرد بازم لزومی نداره چک کنیم
        if(!R::CheckExit('GamePl:Selected:'.$Faheshe['user_id'])){
            return false;
        }

        // خب حالا مطمعن شدیم قاحشه هم هست،هم زندس، هم انتخابشو انجام داده حالا چک کردن رو شروع میکنیم
        $FahesheName = HL::ConvertName($Faheshe['user_id'],$Faheshe['fullname_game']);
        $selected = R::Get('GamePl:Selected:'.$Faheshe['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);

        R::Del('GamePl:Selected:'.$Faheshe['user_id']);
        // اگه مرده بود  طرف چک کردنو بیخیال میشیم
        if($Detial['user_state'] !== 1){
            return false;
        }

        // به عنوان ناتاشا، معشوق را ببینید
        if((R::Get('GamePl:love') == $Faheshe['user_id'] && R::Get('GamePl:lover') == $Detial['user_id']) || (R::Get('GamePl:lover') == $Faheshe['user_id'] && R::Get('GamePl:love') == $Detial['user_id'])){
            HL::SavePlayerAchivment($Faheshe['user_id'],'Affectionate');
        }


        if(R::CheckExit('GamePl:HuntsmanTraps:'.$Detial['user_id'])){
            if(HL::R(100) >= 50){
                $Huntsman = HL::_getPlayerByRole('role_Huntsman');
                if($Huntsman){
                    if($Huntsman['user_state'] == 1){

                        // پیام برای هانتسمن
                        $HuntsManMessage = self::$Dt->LG->_('HuntsmanMessageKillPlayer',array("{0}" =>$FahesheName));
                        HL::SendMessage($HuntsManMessage,$Huntsman['user_id']);
                        // پیام برای شخصی که گرفتار شد
                        $userMessage = self::$Dt->LG->_('HuntsmanKillPlayerMessage',array("{0}" =>$U_name));
                        HL::SendMessage($userMessage, $Faheshe['user_id']);

                        // پیام گروه
                        $groupMessage= self::$Dt->LG->_('HuntsmanKillPlayerGroupMesssage',array("{0}" =>$FahesheName)).self::$Dt->LG->_('user_role',array("{0}" =>self::$Dt->LG->_($Faheshe['user_role']."_n")));
                        HL::SaveMessage($groupMessage);
                        // پشتن پلیر
                        HL::UserDead($Faheshe,'HuntsmanKill');
                        // حذف تله
                        R::Del('GamePl:HuntsmanTraps:'.$Detial['user_id']);

                    }
                }else{
                    R::Del('GamePl:HuntsmanTraps:'.$Detial['user_id']);
                }
            }
        }


        $CheckPrisoner = HL::checkUserINPrisoner($Detial);
        if($CheckPrisoner){
            $Msg = self::$Dt->LG->_('PrincessPrisonerFahesheAttack',$U_name);
            HL::SendMessage($Msg,$Faheshe['user_id']);
            return  true;
        }


        R::Del('GamePl:Selected:'.$Faheshe['user_id']);
        switch ($Detial['user_role']){
            case 'role_Sweetheart':
                if(R::Get('GamePl:SweetheartLove:team') == "Faheshe"){
                    $UserMessage = self::$Dt->LG->_('HarlotVisitYou');
                    HL::SendMessage($UserMessage,$Detial['user_id']);
                    $FahesheMessage = self::$Dt->LG->_('HarlotVisitNonWolf',array("{0}" =>$U_name));
                    HL::SendMessage($FahesheMessage,$Faheshe['user_id']);
                    return true;
                }

                $FahesheMessage = self::$Dt->LG->_('MsgPlayerFHLoved',array("{0}" =>$U_name));
                HL::SendMessage($FahesheMessage,$Faheshe['user_id']);
                HL::LoverBYSweetheart($Faheshe['user_id'],'Faheshe');
                return true;
                break;
            case 'role_Bloodthirsty':
                if(R::CheckExit('GamePl:VampireFinded')) {
                    if(HL::R(100) >= 50){

                        $MessageAngel  = self::$Dt->LG->_('bloodConvertNatasha',array("{0}" =>$U_name));
                        HL::SendMessage($MessageAngel, $Faheshe['user_id']);

                        $VampireMessaage = self::$Dt->LG->_('bloodConvertMessageNatasha',array("{0}" =>$FahesheName,"{1}"=> $U_name));
                        HL::SendForVampireTeam($VampireMessaage);
                        // خب حالا طرف رو میذاریم توی لیست گاز زده ها
                        HL::VampireConvert($Detial['user_id']);
                        return true;
                    }
                    $MessageAngel  = self::$Dt->LG->_('BloodKillNatashaMessage',array("{0}" =>$U_name));
                    HL::SendMessage($MessageAngel, $Faheshe['user_id']);
                    HL::UserDead($Faheshe, 'BloodVampire');
                    HL::SaveGameActivity($Faheshe,'vampire',$Detial);
                    $GroupMessage = self::$Dt->LG->_('BloodKillNatasha',array("{0}" =>$FahesheName));
                    HL::SaveMessage($GroupMessage);

                    return true;
                }
                $MessageAngel  = self::$Dt->LG->_('HarlotNotHome',array("{0}" =>$U_name));
                HL::SendMessage($MessageAngel, $Faheshe['user_id']);
                return true;
                break;
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_Wolfx':
            case 'role_WolfAlpha':
                $GroupMessage = ($Detial['user_role'] == "role_WolfGorgine" ? self::$Dt->LG->_('HarlotEaten',array("{0}" =>$FahesheName)) : self::$Dt->LG->_('HarlotFuckedWolfPublic',array("{0}" =>$FahesheName))) ;
                HL::SaveMessage($GroupMessage);
                HL::UserDead($Faheshe,'eat');
                HL::SaveGameActivity($Faheshe,'eat',$Detial);
                $FahesheMessage = self::$Dt->LG->_('HarlotFuckWolf',array("{0}" =>$U_name));
                HL::SendMessage($FahesheMessage,$Faheshe['user_id']);
                return true;
                break;
            case 'role_Qatel':
                $GroupMessage = self::$Dt->LG->_('FahesheInKiller',array("{0}" =>$FahesheName));
                HL::SaveMessage($GroupMessage);
                HL::UserDead($Faheshe,'kill');
                HL::SaveGameActivity($Faheshe,'kill',$Detial);
                $FahesheMessage = self::$Dt->LG->_('HarlotFuckKiller',array("{0}" =>$U_name));
                HL::SendMessage($FahesheMessage,$Faheshe['user_id']);
                return true;
                break;
            case 'role_hilda':
                if(!R::CheckExit('GamePl:KillerIsKillHildaInGame')) {
                    R::GetSet((R::Get('GamePl:VisitSafeCountFaheshe') ?? 0) + 1,'GamePl:VisitSafeCountFaheshe');

                    $UserMessage = self::$Dt->LG->_('HarlotVisitYou');
                    HL::SendMessage($UserMessage,$Detial['user_id']);

                    $FahesheMessage = self::$Dt->LG->_('HarlotVisitNonWolf',array("{0}" =>$U_name));
                    HL::SendMessage($FahesheMessage,$Faheshe['user_id']);
                    return true;
                }
                $GroupMessage = self::$Dt->LG->_('FahesheInHilda',array("{0}" =>$FahesheName));
                HL::SaveMessage($GroupMessage);
                HL::UserDead($Faheshe,'kill');
                HL::SaveGameActivity($Faheshe,'kill',$Detial);
                $FahesheMessage = self::$Dt->LG->_('HarlotFuckHilda',array("{0}" =>$U_name));
                HL::SendMessage($FahesheMessage,$Faheshe['user_id']);
                return true;
             break;
            case 'role_forestQueen':
                if(R::CheckExit('GamePl:role_forestQueen:AlphaDead')){
                    $GroupMessage = self::$Dt->LG->_('HarlotFuckedWolfPublic',array("{0}" =>$FahesheName));
                    HL::SaveMessage($GroupMessage);
                    HL::UserDead($Faheshe,'eat');
                    HL::SaveGameActivity($Faheshe,'eat',$Detial);
                    $FahesheMessage = self::$Dt->LG->_('HarlotFuckWolf',array("{0}" =>$U_name));
                    HL::SendMessage($FahesheMessage,$Faheshe['user_id']);
                    return true;
                }
                $UserMessage = self::$Dt->LG->_('HarlotVisitYou');
                HL::SendMessage($UserMessage,$Detial['user_id']);
                R::GetSet((R::Get('GamePl:VisitSafeCountFaheshe') ?? 0) + 1,'GamePl:VisitSafeCountFaheshe');
                $FahesheMessage = self::$Dt->LG->_('HarlotVisitNonWolf',array("{0}" =>$U_name));
                HL::SendMessage($FahesheMessage,$Faheshe['user_id']);
                return true;
                break;
            case 'role_Royce':
            case 'role_ferqe':
                $FahesheMessage = self::$Dt->LG->_('HarlotDiscoverCult',array("{0}" =>$U_name));
                HL::SendMessage($FahesheMessage,$Faheshe['user_id']);
                return true;
                break;
            default:
                R::GetSet((R::Get('GamePl:VisitSafeCountFaheshe') ?? 0) + 1,'GamePl:VisitSafeCountFaheshe');

                $UserMessage = self::$Dt->LG->_('HarlotVisitYou');
                HL::SendMessage($UserMessage,$Detial['user_id']);

                $FahesheMessage = self::$Dt->LG->_('HarlotVisitNonWolf',array("{0}" =>$U_name));
                HL::SendMessage($FahesheMessage,$Faheshe['user_id']);
                return true;
                break;
        }
    }
    public static function GetTeamCultSelected(){

        $Keys = R::Keys('GamePl:Selected:Cult:*'); // دریافت تمام داده های توی این پترن
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
                    // ارایه رو برابر با عدد بزرگتر کن
                    $max = $row['total'];
                    // ای دی کاربر رو هم اضافه کن به عدد خروجی
                    array_push($maxTotal,$row['user_id']);
                }
            }

            if($maxTotal){
                return $maxTotal['0'];
            }
            return $Selected['0']['user_id'];
        }

        return false;
    }


    public static function CheckCult(){


        $P_Team = HL::PlayerByTeam();
        $Cult =  (count($P_Team['ferqe']) > 0 ? $P_Team['ferqe'] : false);
        // چک کن تیم فرقه گرا وجود داره
        if(!$Cult){
            return false;
        }
        if(R::CheckExit('GamePl:RoyceSelectd2')){
            if(R::Get('GamePl:RoyceDead') == R::Get('GamePl:Night_no')){
                R::Del('GamePl:CheckNight');
                R::Del('GamePl:SendNightAll');
                R::Del('GamePl:RoyceDead');
                R::Del('GamePl:RoyceSelectd2');
            }
        }


        // تعداد فرقه گرا ها رو بشمار
        $count_Cult = count($P_Team['ferqe']);
        $CultName = ($Cult ? implode(',',array_column($Cult,'Link')) : false);
        if($count_Cult > 1){
            // مجموع انتخاب تیم فرقه گرا ها رو حساب کن و یه ای دی بده
            $GetSelected = self::GetTeamCultSelected();
            $selected = $GetSelected;

            if(empty($selected)){
                return false;
            }
            // دریافت نام هم تیمی فرقه گرا ها ها
            $LastCult = HL::_getLastCult();
            $Me_name =  HL::ConvertName($LastCult['user_id'],$LastCult['fullname_game']);
            $Me_user_id = $LastCult['user_id'];
            $Me_Role =  $LastCult['user_role'];
        }else{
            $Me_user_id = $Cult['0']['user_id'];
            if(!R::CheckExit('GamePl:Selected:'.$Me_user_id)){
                return false;
            }
            $Me_name =  $Cult['0']['Link'];
            $Me_Role =  $Cult['0']['role'];
            $selected = R::Get('GamePl:Selected:'.$Me_user_id);
        }

        $Detial = HL::_getPlayer($selected);
        R::GetSet($selected,'GamePl:UserInHome:'.$Me_user_id);
        R::GetSet($Me_name,'GamePl:UserInHome:'.$Me_user_id.":name");
        R::GetSet(self::$Dt->LG->_('role_ferqe_n'),'GamePl:UserInHome:'.$Me_user_id.":role");



        R::DelKey('GamePl:Selected:Cult:*');
        $U_name = HL::ConvertName($selected,$Detial['fullname_game']);


        if($Detial['user_state'] !== 1){
            $CultMessage = ($count_Cult > 1 ? self::$Dt->LG->_('CultVisitDead',array("{0}"=> $Me_name,"{1}" => $U_name)) : self::$Dt->LG->_('CultVisitDeadOne',array("{0}"=>$U_name))) ;
            HL::SendForCultTeam($CultMessage);
            return true;
        }

        if(R::CheckExit('GamePl:HuntsmanTraps:'.$Detial['user_id'])){
            if(HL::R(100) >= 50){
                $Huntsman = HL::_getPlayerByRole('role_Huntsman');
                if($Huntsman){
                    if($Huntsman['user_state'] == 1){

                        // پیام برای هانتسمن
                        $HuntsManMessage = self::$Dt->LG->_('HuntsmanMessageKillPlayer',array("{0}"=>$Me_name));
                        HL::SendMessage($HuntsManMessage,$Huntsman['user_id']);
                        // پیام برای شخصی که گرفتار شد
                        $userMessage = self::$Dt->LG->_('HuntsmanKillPlayerMessage',array("{0}"=>$U_name));
                        HL::SendMessage($userMessage,$Me_name);



                        // پیام گروه
                        $groupMessage= self::$Dt->LG->_('HuntsmanKillPlayerGroupMesssage',array("{0}"=>$Me_name)).self::$Dt->LG->_('user_role',array("{0}"=>self::$Dt->LG->_($Me_Role."_n")));
                        HL::SaveMessage($groupMessage);
                        // پشتن پلیر
                        HL::UserDead($Me_user_id,'HuntsmanKill');
                        HL::SaveGameActivity(['user_id' => $Me_user_id,'fullname'=> $Me_name],'huns',$Detial);
                        if($count_Cult > 1){
                            $CultTeamMessage = self::$Dt->LG->_('HunstmanKillCultMessageMulti',array("{0}"=> $Me_name,"{1}" => $U_name));
                            HL::SendForCultTeam($CultTeamMessage);
                        }

                        // حذف تله
                        R::Del('GamePl:HuntsmanTraps:'.$Detial['user_id']);
                    }
                }else{
                    R::Del('GamePl:HuntsmanTraps:'.$Detial['user_id']);
                }
            }
        }

        $CheckPrisoner = HL::checkUserINPrisoner($Detial);
        if($CheckPrisoner){
            $Msg = self::$Dt->LG->_('PrincessPrisonerCultAttack',$U_name);
            HL::SendForCultTeam($Msg);
            return  true;
        }


        switch ($Detial['user_role']){
            case 'role_Sweetheart':
                if(R::Get('GamePl:SweetheartLove:team') == "Cult"){
                    $MsgUser = self::$Dt->LG->_('CultConvertYou', array("{0}"=> $CultName));
                    HL::SendMessage($MsgUser,$Detial['user_id']);
                    $CultMessage = self::$Dt->LG->_('CultJoin',array("{0}"=> $U_name));
                    HL::SendForCultTeam($CultMessage);
                    R::rpush($Detial['user_id'],'GamePl:SendNight');
                    HL::ConvertPlayer($Detial['user_id'],'role_ferqe');
                    return true;
                }

                $PlayerMessage = ($count_Cult > 1 ? self::$Dt->LG->_('MsgPlayerCultsLoved',array("{0}"=>$U_name)) : self::$Dt->LG->_('MsgPlayerCultLoved',array("{0}"=>$U_name)));
                HL::SendMessage($PlayerMessage,$Me_user_id);
                if($count_Cult > 1 ){
                    $CultMessage = self::$Dt->LG->_('MsgPlayerLoveCultsMessage',array("{0}"=>$Me_name,"{1}" => $U_name));
                    HL::SendForCultTeam($CultMessage,$Me_user_id);
                }

                //HL::SendMessage($MsgCultHunter,$CultHunter['user_id']);
                HL::LoverBYSweetheart($Me_user_id,'Cult');
                return true;
                break;

            case 'role_Qatel':
                if(HL::R(100) < 50 ){
                    $GroupMesssage = ($count_Cult > 1 ? self::$Dt->LG->_('CultConvertKillerPublic',array("{0}"=>$Me_name)) : self::$Dt->LG->_('CultConvertKillerPublicOne',array("{0}"=>$Me_name)));
                    HL::SaveMessage($GroupMesssage);
                    HL::UserDead($Me_user_id,'kill');
                    HL::SaveGameActivity(['user_id' => $Me_user_id,'fullname'=> $Me_name],'kill',$Detial);
                    return true;
                }
                if(R::CheckExit('GamePl:UserInHome:'.$selected) == true){
                    $CultMessage = ($count_Cult > 1 ? self::$Dt->LG->_('CultVisitEmpty',array("{0}"=>$Me_name,"{1}" => $U_name)) : self::$Dt->LG->_('CultVisitEmptyOne',array("{0}"=>$U_name))) ;
                    HL::SendForCultTeam($CultMessage);
                    return true;
                }

                $GroupMesssage = ($count_Cult > 1 ? self::$Dt->LG->_('CultConvertKillerPublic',array("{0}"=>$Me_name)) : self::$Dt->LG->_('CultConvertKillerPublicOne',array("{0}"=>$Me_name)));
                HL::SaveMessage($GroupMesssage);
                HL::UserDead($Me_user_id,'kill');
                HL::SaveGameActivity(['user_id' => $Me_user_id,'fullname'=> $Me_name],'kill',$Detial);
                return true;

                break;

            case 'role_Vampire':
                if (HL::R(100) < 50) {
                    $GroupMesssage = self::$Dt->LG->_('VampireDeadCult',array("{0}"=> $Me_name));
                    HL::SaveMessage($GroupMesssage);
                    HL::UserDead($Me_user_id,'Vampire_Convert');
                    HL::SaveGameActivity(['user_id' => $Me_user_id,'fullname'=> $Me_name],'vampire',$Detial);
                    if ($count_Cult > 1) {
                        $CultMessage = self::$Dt->LG->_('VamireDeadCultR',array("{0}"=> $Me_name,"{1}"=> $U_name));
                        HL::SendForCultTeam($CultMessage);
                    }
                    return true;
                }
                $CultMessage = ($count_Cult > 1 ? self::$Dt->LG->_('CultVisitEmpty',array("{0}"=>$Me_name,"{1}"=> $U_name)) : self::$Dt->LG->_('CultVisitEmptyOne',array("{0}"=>$U_name))) ;
                HL::SendForCultTeam($CultMessage);
                return true;
                break;
            case 'role_Bloodthirsty':
                if(R::CheckExit('GamePl:VampireFinded')) {
                    if (HL::R(100) < 50) {
                        $VampireMessage = self::$Dt->LG->_('VampireMessageCultConvert', array("{0}"=> $U_name));
                        HL::SendForVampireTeam($VampireMessage);
                        if ($count_Cult > 1) {
                            $CultMessage = self::$Dt->LG->_('BloodthirstyCultMessageConvert', array("{0}"=> $Me_name, "{1}"=>$U_name));
                            HL::SendForCultTeam($CultMessage);
                        }
                        $PlayerMessage = self::$Dt->LG->_('PlayerMessageConvertToVampire', array("{0}"=>$U_name));
                        HL::SendMessage($PlayerMessage, $Me_user_id);
                        return true;
                    }

                    $GroupMesssage = self::$Dt->LG->_('GroupMessageDeadCult',array("{0}"=>$Me_name));
                    HL::SaveMessage($GroupMesssage);
                    HL::UserDead($Me_user_id,'Vampire_Convert');
                    HL::SaveGameActivity(['user_id' => $Me_user_id,'fullname'=> $Me_name],'vampire',$Detial);
                    return true;
                }
                $MsgUser = self::$Dt->LG->_('CultAttempt');
                HL::SendMessage($MsgUser,$Detial['user_id']);
                $CultMessage = ($count_Cult > 1 ? self::$Dt->LG->_('CultVisitAttemp',array("{0}"=>$Me_name,"{1}"=> $U_name)) : self::$Dt->LG->_('CultVisitAttempOne',array("{0}"=>$U_name)));
                HL::SendForCultTeam($CultMessage);

                return true;
                break;
            case 'role_shekar':
                $GroupMesssage = self::$Dt->LG->_('CultConvertCultHunter',array("{0}"=>$Me_name,"{1}" =>$U_name));
                HL::SaveMessage($GroupMesssage);
                HL::UserDead($Me_user_id,'kill');
                HL::SaveGameActivity(['user_id' => $Me_user_id,'fullname'=> $Me_name],'cult',$Detial);
                $MsgCultHunter = self::$Dt->LG->_('CultHunterKilledCultVisit',array("{0}"=>$Me_name));
                HL::SendMessage($MsgCultHunter,$Detial['user_id']);
                return true;
                break;
            case 'role_kalantar':
                if(HL::R(100) < 50 ){
                    $MsgUser = self::$Dt->LG->_('CultConvertYou', array("{0}"=>$CultName));
                    HL::SendMessage($MsgUser,$Detial['user_id']);
                    $CultMessage = self::$Dt->LG->_('CultJoin', array("{0}"=>$U_name));
                    HL::SendForCultTeam($CultMessage);
                    R::rpush($Detial['user_id'],'GamePl:SendNight');
                    HL::ConvertPlayer($Detial['user_id'],'role_ferqe');

                    return true;
                }
                if(HL::R(100) < 50) {
                    $GroupMesssage = self::$Dt->LG->_('CultConvertHunter',array("{0}"=>$Me_name,"{1}" => $U_name));
                    HL::SaveMessage($GroupMesssage);
                    HL::UserDead($Me_user_id,'shot');
                    HL::SaveGameActivity(['user_id' => $Me_user_id,'fullname'=> $Me_name],'shot',$Detial);
                    return true;
                }
                $MsgUser = self::$Dt->LG->_('CultAttempt');
                HL::SendMessage($MsgUser,$Detial['user_id']);
                $CultMessage = ($count_Cult > 1 ? self::$Dt->LG->_('CultVisitAttemp',array("{0}"=>$Me_name,"{1}" => $U_name)) : self::$Dt->LG->_('CultVisitAttempOne',array("{0}"=>$U_name)));
                HL::SendForCultTeam($CultMessage);
                return true;
                break;
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_Wolfx':
            case 'role_WolfAlpha':
                if(R::CheckExit('GamePl:UserInHome:'.$selected) == true){
                    $CultMessage = ($count_Cult > 1 ? self::$Dt->LG->_('CultVisitEmpty',array("{0}"=>$Me_name,"{1}" => $U_name)) : self::$Dt->LG->_('CultVisitEmptyOne',array("{0}"=> $U_name))) ;
                    HL::SendForCultTeam($CultMessage);
                    return true;
                }

                $GroupMesssage = self::$Dt->LG->_('CultConvertWolfPublic',array("{0}"=>$Me_name));
                HL::SaveMessage($GroupMesssage);
                HL::UserDead($Me_user_id,'eat');
                HL::SaveGameActivity(['user_id' => $Me_user_id,'fullname'=> $Me_name],'eat',$Detial);
                return true;
                break;
            case 'role_feramason':
                $MsgUser = self::$Dt->LG->_('CultConvertYou', array("{0}"=>$CultName));
                HL::SendMessage($MsgUser,$Detial['user_id']);
                $CultMessage = self::$Dt->LG->_('CultJoin', array("{0}"=>$U_name));
                HL::SendForCultTeam($CultMessage);
                R::rpush($Detial['user_id'],'GamePl:SendNight');
                HL::ConvertPlayer($Detial['user_id'],'role_ferqe');
                // ارسال پیام برای بقیه فراماسون ها که سر جلسه حاظر نشه
                HL::SendMasonAfterChangeRole($U_name);
                return true;
                break;
            default:
                if(R::CheckExit('GamePl:UserInHome:'.$selected) == true){
                    $CultMessage = ($count_Cult > 1 ? self::$Dt->LG->_('CultVisitEmpty',array("{0}"=>$Me_name,"{1}" => $U_name)) : self::$Dt->LG->_('CultVisitEmptyOne',array("{0}"=> $U_name))) ;
                    HL::SendForCultTeam($CultMessage);
                    return true;
                }

                $CultAttemp = self::CultAttemp($Detial['user_role']);
                if($CultAttemp == 1){
                    $MsgUser = self::$Dt->LG->_('CultConvertYou', array("{0}"=> $CultName));
                    HL::SendMessage($MsgUser,$Detial['user_id']);
                    $CultMessage = self::$Dt->LG->_('CultJoin',array("{0}"=> $U_name));
                    HL::SendForCultTeam($CultMessage);
                    R::rpush($Detial['user_id'],'GamePl:SendNight');
                    HL::ConvertPlayer($Detial['user_id'],'role_ferqe');
                    return true;
                }

                $MsgUser = self::$Dt->LG->_('CultAttempt');
                HL::SendMessage($MsgUser,$Detial['user_id']);
                $CultMessage = ($count_Cult > 1 ? self::$Dt->LG->_('CultVisitAttemp',array("{0}"=>$Me_name,"{1}" => $U_name)) : self::$Dt->LG->_('CultVisitAttempOne',array("{0}"=>$U_name)));
                HL::SendForCultTeam($CultMessage);
                return true;
                break;
        }

    }


    public static function CultAttemp($Role){
        switch ($Role){
            case 'role_PesarGij':
            case 'role_Nazer':
            case 'role_rosta':
            case 'role_ahmaq':
            case 'role_Mast':
            case 'role_Khaen':
            case 'role_monafeq':
            case 'role_Kadkhoda':
            case 'role_Sweetheart':
            case 'role_Ruler':
            case 'role_Shahzade':
            case 'role_tofangdar':
            case 'role_feramason':
            case 'role_PishRezerv':
            case 'role_elahe':
            case 'role_Vahshi':
            case 'role_Gorgname':
                return 1;
                break;
            case 'role_IceQueen':
            case 'role_Firefighter':
            case 'role_Archer':
            case 'role_forestQueen':
            case 'role_WhiteWolf':
            case 'role_lucifer':
            case 'role_Huntsman':
            case 'role_hilda':
                return 0;
                break;
            case 'role_Honey':
                if(HL::R(100) < 60){
                    return 1;
                }else{
                    return 0;
                }
                break;
            case 'role_Augur':
                if(HL::R(100) < 40){
                    return 1;
                }else{
                    return 0;
                }
                break;
            case 'role_Chemist':
                if(HL::R(100) < 50){
                    return 1;
                }else{
                    return 0;
                }
                break;
            case 'role_enchanter':
                if(HL::R(100) < 60){
                    return 1;
                }else{
                    return 0;
                }
                break;
            case 'role_Knight':
                if(HL::R(100) < 30){
                    return 1;
                }else{
                    return 0;
                }
                break;
            case 'role_Botanist':
                if(HL::R(100) < 70){
                    return 1;
                }else{
                    return 0;
                }
                break;
            case 'role_Spy':
                if(HL::R(100) < 30){
                    return 1;
                }else{
                    return 0;
                }
                break;
            case 'role_Lucifer':
                if(HL::R(100) < 70){
                    return 1;
                }else{
                    return 0;
                }
                break;
            case 'role_Judge':
                return 1;
                break;
            case 'role_clown':
                return 1;
                break;
            case 'role_WolfJadogar':
                if(HL::R(100) < 40){
                    return 1;
                }else{
                    return 0;
                }
                break;
            case 'role_NefrinShode':
                if(HL::R(100) < 60){
                    return 1;
                }else{
                    return 0;
                }
                break;
            case 'role_Ahangar':
                if(HL::R(100) < 75){
                    return 1;
                }else{
                    return 0;
                }
                break;
            case 'role_KhabGozar':
                if(HL::R(100) < 60){
                    return 1;
                }else{
                    return 0;
                }
                break;
            case 'role_rishSefid':
                if(HL::R(100) < 30){
                    return 1;
                }else{
                    return 0;
                }
                break;
            case 'role_trouble':
                if(HL::R(100) < 40){
                    return 1;
                }else{
                    return 0;
                }
                break;
            case 'role_Solh':
                if(HL::R(100) < 80){
                    return 1;
                }else{
                    return 0;
                }
                break;

            case 'role_pishgo':
                if(HL::R(100) < 40){
                    return 1;
                }else{
                    return 0;
                }
                break;
            case 'role_ngativ':
                if(HL::R(100) < 40){
                    return 1;
                }else{
                    return 0;
                }
                break;
            case 'role_faheshe':
                if(HL::R(100) < 70){
                    return 1;
                }else{
                    return 0;
                }
                break;
            case 'role_karagah':
                if(HL::R(100) < 80){
                    return 1;
                }else{
                    return 0;
                }
                break;
            case 'role_Hamzad':
                return 0;
                break;
            default:
                return 0;
                break;
        }
    }
    public static function GetCultHunter(){
        $CultHunter = HL::_getPlayerByRole('role_shekar');
        // اگر قاتل نبود،مسلما باید بیخیال بشیم
        if($CultHunter == false){
            return false;
        }
        // اگر شکارچی مرده بود بازم باید بیخیال بشیم مسلما
        if($CultHunter['user_state'] !== 1){
            return false;
        }
        // اگر شکارچی انتخابی نکرد بازم لزومی نداره چک کنیم
        if(!R::CheckExit('GamePl:Selected:'.$CultHunter['user_id'])){
            return false;
        }

        // خب حالا مطمعن شدیم شکارچی هم هست،هم زندس، هم انتخابشو انجام داده حالا چک کردن رو شروع میکنیم
        $CultHunterName = HL::ConvertName($CultHunter['user_id'],$CultHunter['fullname_game']);
        $selected = R::Get('GamePl:Selected:'.$CultHunter['user_id']);
        $Detial = HL::_getPlayer($selected);
        $U_name = HL::ConvertName($Detial['user_id'],$Detial['fullname_game']);

        // اگه مرده بود  طرف چک کردنو بیخیال میشیم
        if($Detial['user_state'] !== 1){
            $MsgCultHunter = self::$Dt->LG->_('HunterVisitDead',array("{0}" =>$U_name));
            HL::SendMessage($MsgCultHunter,$CultHunter['user_id']);
            return false;
        }

        if(R::CheckExit('GamePl:HuntsmanTraps:'.$Detial['user_id'])){
            if(HL::R(100) >= 50){
                $Huntsman = HL::_getPlayerByRole('role_Huntsman');
                if($Huntsman){
                    if($Huntsman['user_state'] == 1){

                        // پیام برای هانتسمن
                        $HuntsManMessage = self::$Dt->LG->_('HuntsmanMessageKillPlayer',array("{0}" => $CultHunterName));
                        HL::SendMessage($HuntsManMessage,$Huntsman['user_id']);
                        // پیام برای شخصی که گرفتار شد
                        $userMessage = self::$Dt->LG->_('HuntsmanKillPlayerMessage',array("{0}" => $U_name));
                        HL::SendMessage($userMessage, $CultHunter['user_id']);

                        // پیام گروه
                        $groupMessage= self::$Dt->LG->_('HuntsmanKillPlayerGroupMesssage',array("{0}" => $CultHunterName)).self::$Dt->LG->_('user_role',array("{0}" => self::$Dt->LG->_($CultHunter['user_role']."_n")));
                        HL::SaveMessage($groupMessage);
                        // پشتن پلیر
                        HL::UserDead($CultHunter,'HuntsmanKill');
                        HL::SaveGameActivity($CultHunter,'huns',$Detial);
                        // حذف تله
                        R::Del('GamePl:HuntsmanTraps:'.$Detial['user_id']);

                    }
                }else{
                    R::Del('GamePl:HuntsmanTraps:'.$Detial['user_id']);
                }
            }
        }

        switch ($Detial['user_role']){
            case 'role_Sweetheart':
                if(R::Get('GamePl:SweetheartLove:team') == "CultHunter"){
                    $MsgCultHunter = self::$Dt->LG->_('HunterFailedToFind',array("{0}" =>$U_name));
                    HL::SendMessage($MsgCultHunter,$CultHunter['user_id']);
                    return true;
                }
                $MsgCultHunter = self::$Dt->LG->_('MsgPlayerCHLoved',array("{0}" =>$U_name));
                HL::SendMessage($MsgCultHunter,$CultHunter['user_id']);
                HL::LoverBYSweetheart($CultHunter['user_id'],'CultHunter');
                return true;
                break;
            case 'role_Qatel':
                $GroupMessage = self::$Dt->LG->_('SerialKillerKilledCH',array("{0}" =>$CultHunterName));
                HL::SaveMessage($GroupMessage);
                HL::UserDead($CultHunter,'cultHunterInKiller');
                HL::SaveGameActivity($CultHunter,'kill',$Detial);
                break;
            case 'role_Royce':
            case 'role_ferqe':
                $HunterKillFerqe = (R::Get('GamePl:HunterKillFerqe:'.$CultHunter['user_id']) ?? 0) +1;
                R::GetSet($HunterKillFerqe ,'GamePl:HunterKillFerqe:'.$CultHunter['user_id']);
                // » به عنوان یک شکارچی ، در یک بازی حداقل 3 فرقه گرا را بکشید
                if($HunterKillFerqe >= 3){
                    HL::SavePlayerAchivment($CultHunter['user_id'],'Cultist_Tracker');
                }

                $MsgCultHunter = self::$Dt->LG->_('HunterFindCultist',array("{0}" =>$U_name));
                HL::SendMessage($MsgCultHunter,$CultHunter['user_id']);
                // ارسال پیام برای فرفه
                $CultMessage =  self::$Dt->LG->_('HunterKilledCultistOn');
                HL::SendMessage($CultMessage,$Detial['user_id']);

                $GroupMessage = self::$Dt->LG->_('HunterKilledCultist',array("{0}" =>$U_name));
                HL::SaveMessage($GroupMessage);
                HL::UserDead($Detial,'CultHunter');
                HL::SaveGameActivity($Detial,'cult',$CultHunter);
                HL::SaveKill($CultHunter['user_id'],$Detial['user_id'],'CultHunter');
                return true;
                break;
            default:
                $MsgCultHunter = self::$Dt->LG->_('HunterFailedToFind',array("{0}" =>$U_name));
                HL::SendMessage($MsgCultHunter,$CultHunter['user_id']);
                return true;
                break;
        }

        return false;
    }

    public static function CheckNatashaIn($user_id,$name,$type = "wolf"){
        if(R::CheckExit('GamePl:role_faheshe:inhome:'.$user_id)){
            $Faqheshe = HL::_getPlayerByRole('role_faheshe');
            if($Faqheshe) {
                $FahesheName = HL::ConvertName($Faqheshe['user_id'], $Faqheshe['fullname_game']);
                $MsgGroup = ($type == "wolf" ? self::$Dt->LG->_('HarlotFuckedVictimPublic', array("{0}" => $FahesheName, "{1}" => $name)) : self::$Dt->LG->_('HarlotFuckedKilledPublic', array("{0}" => $FahesheName,"{1}"=> $name)));
                HL::SaveMessage($MsgGroup);
                HL::UserDead($Faqheshe, 'in_home');
                return true;
            }
        }
        return false;
    }
    public static function GetKiller(){

        $Killer = HL::_getPlayerByRole('role_Qatel');
        // اگر قاتل نبود،مسلما باید بیخیال بشیم
        if($Killer == false){
            return false;
        }
        // اگر قاتل مرده بود بازم باید بیخیال بشیم مسلما
        if($Killer['user_state'] !== 1){
            return false;
        }
        // اگر قاتل انتخابی نکرد بازم لزومی نداره چک کنیم
        if(!R::CheckExit('GamePl:Selected:'.$Killer['user_id'])){
            return false;
        }

        $KillerName = HL::ConvertName($Killer,$Killer['fullname_game']);
        // خب حالا مطمعن شدیم قاتل هم هست،هم زندس، هم انتخابشو انجام داده حالا چک کردن رو شروع میکنیم

        $selected = R::Get('GamePl:Selected:'.$Killer['user_id']);
        $Detial = HL::_getPlayer($selected);

        // اگه مرده بود  طرف چک کردنو بیخیال میشیم
        if($Detial['user_state'] !== 1){

            return false;
        }

        // اسم بازیکن رو با لینکش میگیریم
        $U_name = HL::ConvertName($selected,$Detial['fullname_game']);

        if(R::CheckExit('GamePl:HuntsmanTraps:'.$Detial['user_id'])){
            if(HL::R(100) >= 50){
                $Huntsman = HL::_getPlayerByRole('role_Huntsman');
                if($Huntsman){
                    if($Huntsman['user_state'] == 1){

                        // پیام برای هانتسمن
                        $HuntsManMessage = self::$Dt->LG->_('HuntsmanMessageKillPlayer',array("{0}" => $KillerName));
                        HL::SendMessage($HuntsManMessage,$Huntsman['user_id']);
                        // پیام برای شخصی که گرفتار شد
                        $userMessage = self::$Dt->LG->_('HuntsmanKillPlayerMessage',array("{0}" => $U_name));
                        HL::SendMessage($userMessage,$KillerName);


                        // پیام گروه
                        $groupMessage= self::$Dt->LG->_('HuntsmanKillPlayerGroupMesssage',array("{0}" => $KillerName)).self::$Dt->LG->_('user_role',array("{0}" => self::$Dt->LG->_($Killer['user_role']."_n")));
                        HL::SaveMessage($groupMessage);
                        // پشتن پلیر
                        HL::UserDead($Killer,'HuntsmanKill');
                        HL::SaveGameActivity($Killer,'huns',$Huntsman);
                        // حذف تله
                        R::Del('GamePl:HuntsmanTraps:'.$Detial['user_id']);

                    }
                }else{
                    R::Del('GamePl:HuntsmanTraps:'.$Detial['user_id']);
                }
            }
        }

        $CheckPrisoner = HL::checkUserINPrisoner($Detial);
        if($CheckPrisoner){
            $Msg = self::$Dt->LG->_('PrincessPrisonerKillerAttack',$U_name);
            HL::SendMessage($Msg,$Killer['user_id']);
            return  true;
        }

        switch ($Detial['user_role']){
            case 'role_Sweetheart':
                if(R::Get('GamePl:SweetheartLove:team') == "killer"){
                    //  این پیامیه که توی گروه قراره ارسال بشه
                    $GroupMessage = HL::MesssageQatel($Detial['user_role'],$U_name);
                    // پیام مردنش رو به کاربر میفرسیم
                    $MsgUser = self::$Dt->LG->_('SerialKillerKilledYouTow');
                    HL::SendMessage($MsgUser,$selected,'kill_killer');
                    // بعدم میکشیمش

                    HL::SaveGameActivity($Detial,'kill',$Killer);
                    HL::SaveKill($Killer['user_id'], $selected,'kill');

                    // اگه طرف کلانتر بود، پیام رو تو گروه میفرستیم  و صبر میکنیم یکی رو بکشه
                    if($Detial['user_role'] == "role_kalantar"){
                        HL::HunterKill($GroupMessage,$selected,'kill');
                        HL::UserDead($Detial,'kill');
                        self::CheckNatashaIn($selected,$U_name,'killer');

                        R::Del('GamePl:Selected:'.$Killer['user_id']);
                        return true;
                    }
                    // اگه طرف کلانتر نبود پیام رو ذخیره میکنیم
                    HL::SaveMessage($GroupMessage);
                    HL::UserDead($Detial,'kill');
                    self::CheckNatashaIn($selected,$U_name,'killer');
                    return true;
                }

                $MessageKiller = self::$Dt->LG->_('MsgPlayerSKLoved',array("{0}" => $U_name));
                HL::SendMessage($MessageKiller,$Killer['user_id']);
                HL::LoverBYSweetheart($Killer['user_id'],'killer');
                return false;
                break;
            case 'role_Joker':
                $checkAttack = self::CheckAttack($Detial,'killer',$Killer,$KillerName,false);
                if($checkAttack){
                    return  true;
                }
                if(R::CheckExit('GamePl:role_WhiteWolf:AngelIn:'.$Detial['user_id'])){
                    $MessageForPlayer = self::$Dt->LG->_('WolfMessageGourdWhiteWolf');
                    HL::SendMessage($MessageForPlayer,$Detial['user_id']);
                    $WhiteWolfId = R::Get('GamePl:role_WhiteWolf:AngelIn:'.$Detial['user_id']);
                    $MessageForWhiteWolf = self::$Dt->LG->_('WhiteGourdKiller',array("{0}" => $U_name));
                    HL::SendMessage($MessageForWhiteWolf,$WhiteWolfId);
                    $MsgKiller = self::$Dt->LG->_('WhiteGourdKillerMessage',array("{0}" => $U_name));
                    HL::SendMessage($MsgKiller,$Killer['user_id']);
                    R::GetSet($U_name,'GamePl:role_WhiteWolf:AngelSaved');
                    return true;
                }

                // چک میکنیم بازیکن فرشته روشه یا نه، البته اگه طرف گرگ نبود :)
                if(R::CheckExit('GamePl:role_angel:AngelIn:'.$selected) == true && !in_array($Detial['user_role'],SE::WolfRole())){
                    $MsgUser = self::$Dt->LG->_('GuardSavedYou');
                    HL::SendMessage($MsgUser,$selected);
                    $MsgKiller = self::$Dt->LG->_('GuardBlockedKiller',array("{0}" =>  $U_name));
                    HL::SendMessage($MsgKiller,$Killer['user_id']);
                    R::GetSet($U_name,'GamePl:role_angel:AngelSaved');
                    return true;
                }

                if($Detial['user_role'] == "role_Bloodthirsty"){
                    if(!R::CheckExit('GamePl:VampireFinded')) {
                        $MsgQatel = self::$Dt->LG->_('NotInHomeEat',array("{0}" =>  $U_name));
                        HL::SendMessage($MsgQatel, $Killer['user_id']);
                        return true;
                    }
                }

                $CheckPhoenix = HL::CheckPhoenixHeal($Detial);
                if($CheckPhoenix){
                    $PlayerMessage = self::$Dt->LG->_('MassageAttack');
                    HL::SendMessage($PlayerMessage,$Detial['user_id']);
                    $AttackerMessage = self::$Dt->LG->_('MessageForKiller',array("{0}" => $U_name));
                    HL::SendMessage($AttackerMessage,$Killer['user_id']);
                    return  true;
                }

                //  این پیامیه که توی گروه قراره ارسال بشه
                $GroupMessage = HL::MesssageQatel($Detial['user_role'],$U_name);
                // پیام مردنش رو به کاربر میفرسیم
                $MsgUser = self::$Dt->LG->_('SerialKillerKilledYouTow');
                HL::SendMessage($MsgUser,$selected,'kill_killer');
                // بعدم میکشیمش

                HL::SaveGameActivity($Detial,'kill',$Killer);
                HL::SaveKill($Killer['user_id'], $selected,'kill');

                // اگه طرف کلانتر نبود پیام رو ذخیره میکنیم
                HL::SaveMessage($GroupMessage);
                HL::UserDead($Detial,'kill');
                self::CheckNatashaIn($selected,$U_name,'killer');
                return true;
                break;
            break;
            default:

                if(R::CheckExit('GamePl:role_WhiteWolf:AngelIn:'.$Detial['user_id'])){
                    $MessageForPlayer = self::$Dt->LG->_('WolfMessageGourdWhiteWolf');
                    HL::SendMessage($MessageForPlayer,$Detial['user_id']);
                    $WhiteWolfId = R::Get('GamePl:role_WhiteWolf:AngelIn:'.$Detial['user_id']);
                    $MessageForWhiteWolf = self::$Dt->LG->_('WhiteGourdKiller',array("{0}" => $U_name));
                    HL::SendMessage($MessageForWhiteWolf,$WhiteWolfId);
                    $MsgKiller = self::$Dt->LG->_('WhiteGourdKillerMessage',array("{0}" => $U_name));
                    HL::SendMessage($MsgKiller,$Killer['user_id']);
                    R::GetSet($U_name,'GamePl:role_WhiteWolf:AngelSaved');
                    return true;
                }

                // چک میکنیم بازیکن فرشته روشه یا نه، البته اگه طرف گرگ نبود :)
                if(R::CheckExit('GamePl:role_angel:AngelIn:'.$selected) == true && !in_array($Detial['user_role'],SE::WolfRole())){
                    $MsgUser = self::$Dt->LG->_('GuardSavedYou');
                    HL::SendMessage($MsgUser,$selected);
                    $MsgKiller = self::$Dt->LG->_('GuardBlockedKiller',array("{0}" =>  $U_name));
                    HL::SendMessage($MsgKiller,$Killer['user_id']);
                    R::GetSet($U_name,'GamePl:role_angel:AngelSaved');
                    return true;
                }

                if($Detial['user_role'] == "role_Bloodthirsty"){
                    if(!R::CheckExit('GamePl:VampireFinded')) {
                        $MsgQatel = self::$Dt->LG->_('NotInHomeEat',array("{0}" =>  $U_name));
                        HL::SendMessage($MsgQatel, $Killer['user_id']);
                        return true;
                    }
                }

                $CheckPhoenix = HL::CheckPhoenixHeal($Detial);
                if($CheckPhoenix){
                    $PlayerMessage = self::$Dt->LG->_('MassageAttack');
                    HL::SendMessage($PlayerMessage,$Detial['user_id']);
                    $AttackerMessage = self::$Dt->LG->_('MessageForKiller',array("{0}" => $U_name));
                    HL::SendMessage($AttackerMessage,$Killer['user_id']);
                    return  true;
                }

                //  این پیامیه که توی گروه قراره ارسال بشه
                $GroupMessage = HL::MesssageQatel($Detial['user_role'],$U_name);
                // پیام مردنش رو به کاربر میفرسیم
                $MsgUser = self::$Dt->LG->_('SerialKillerKilledYouTow');
                HL::SendMessage($MsgUser,$selected,'kill_killer');
                // بعدم میکشیمش

                HL::SaveGameActivity($Detial,'kill',$Killer);
                HL::SaveKill($Killer['user_id'], $selected,'kill');

                // اگه طرف کلانتر بود، پیام رو تو گروه میفرستیم  و صبر میکنیم یکی رو بکشه
                if($Detial['user_role'] == "role_kalantar"){
                    HL::HunterKill($GroupMessage,$selected,'kill');
                    HL::UserDead($Detial,'kill');
                    self::CheckNatashaIn($selected,$U_name,'killer');
                    R::Del('GamePl:Selected:'.$Killer['user_id']);
                    return true;
                }
                // اگه طرف کلانتر نبود پیام رو ذخیره میکنیم
                HL::SaveMessage($GroupMessage);
                HL::UserDead($Detial,'kill');
                self::CheckNatashaIn($selected,$U_name,'killer');
                return true;
                break;
        }

    }
   public static function GetHilda(){
        if(!R::CheckExit('GamePl:KillerIsKillHildaInGame')) return false;

        $Killer = HL::_getPlayerByRole('role_hilda');
        // اگر هیلدا نبود،مسلما باید بیخیال بشیم
        if($Killer == false){
            return false;
        }
        // اگر هیلدا مرده بود بازم باید بیخیال بشیم مسلما
        if($Killer['user_state'] !== 1){
            return false;
        }
        // اگر قاتل انتخابی نکرد بازم لزومی نداره چک کنیم
        if(!R::CheckExit('GamePl:Selected:'.$Killer['user_id'])){
            return false;
        }

        $KillerName = HL::ConvertName($Killer,$Killer['fullname_game']);
        // خب حالا مطمعن شدیم هیلدا هم هست،هم زندس، هم انتخابشو انجام داده حالا چک کردن رو شروع میکنیم

        $selected = R::Get('GamePl:Selected:'.$Killer['user_id']);
        $Detial = HL::_getPlayer($selected);

        // اگه مرده بود  طرف چک کردنو بیخیال میشیم
        if($Detial['user_state'] !== 1){

            return false;
        }

        // اسم بازیکن رو با لینکش میگیریم
        $U_name = HL::ConvertName($selected,$Detial['fullname_game']);

        if(R::CheckExit('GamePl:HuntsmanTraps:'.$Detial['user_id'])){
            if(HL::R(100) >= 50){
                $Huntsman = HL::_getPlayerByRole('role_Huntsman');
                if($Huntsman){
                    if($Huntsman['user_state'] == 1){

                        // پیام برای هانتسمن
                        $HuntsManMessage = self::$Dt->LG->_('HuntsmanMessageKillPlayer',array("{0}" => $KillerName));
                        HL::SendMessage($HuntsManMessage,$Huntsman['user_id']);
                        // پیام برای شخصی که گرفتار شد
                        $userMessage = self::$Dt->LG->_('HuntsmanKillPlayerMessage',array("{0}" => $U_name));
                        HL::SendMessage($userMessage,$KillerName);


                        // پیام گروه
                        $groupMessage= self::$Dt->LG->_('HuntsmanKillPlayerGroupMesssage',array("{0}" => $KillerName)).self::$Dt->LG->_('user_role',array("{0}" => self::$Dt->LG->_($Killer['user_role']."_n")));
                        HL::SaveMessage($groupMessage);
                        // پشتن پلیر
                        HL::UserDead($Killer,'HuntsmanKill');
                        HL::SaveGameActivity($Killer,'huns',$Huntsman);
                        // حذف تله
                        R::Del('GamePl:HuntsmanTraps:'.$Detial['user_id']);

                    }
                }else{
                    R::Del('GamePl:HuntsmanTraps:'.$Detial['user_id']);
                }
            }
        }

        switch ($Detial['user_role']){
            case 'role_Sweetheart':
                if(R::Get('GamePl:SweetheartLove:team') == "killer"){
                    //  این پیامیه که توی گروه قراره ارسال بشه
                    $GroupMessage = self::$Dt->LG->_('HildaKillPlayer',$U_name,self::$Dt->LG->_('user_role',array("{0}" =>self::$Dt->LG->_($Detial['user_role']."_n"))));
                    // پیام مردنش رو به کاربر میفرسیم
                    $MsgUser = self::$Dt->LG->_('HildaKillerKilledYouTow');
                    HL::SendMessage($MsgUser,$selected,'kill_hilda');
                    // بعدم میکشیمش

                    HL::SaveGameActivity($Detial,'kill',$Killer);
                    HL::SaveKill($Killer['user_id'], $selected,'kill');

                    // اگه طرف کلانتر بود، پیام رو تو گروه میفرستیم  و صبر میکنیم یکی رو بکشه
                    if($Detial['user_role'] == "role_kalantar"){
                        HL::HunterKill($GroupMessage,$selected,'kill');
                        HL::UserDead($Detial,'kill');
                        self::CheckNatashaIn($selected,$U_name,'killer');

                        R::Del('GamePl:Selected:'.$Killer['user_id']);
                        return true;
                    }
                    // اگه طرف کلانتر نبود پیام رو ذخیره میکنیم
                    HL::SaveMessage($GroupMessage);
                    HL::UserDead($Detial,'kill');
                    self::CheckNatashaIn($selected,$U_name,'killer');
                    return true;
                }

                $MessageKiller = self::$Dt->LG->_('MsgPlayerSKLoved',array("{0}" => $U_name));
                HL::SendMessage($MessageKiller,$Killer['user_id']);
                HL::LoverBYSweetheart($Killer['user_id'],'killer');
                return false;
                break;

            default:

                if(R::CheckExit('GamePl:role_WhiteWolf:AngelIn:'.$Detial['user_id'])){
                    $MessageForPlayer = self::$Dt->LG->_('WolfMessageGourdWhiteWolf');
                    HL::SendMessage($MessageForPlayer,$Detial['user_id']);
                    $WhiteWolfId = R::Get('GamePl:role_WhiteWolf:AngelIn:'.$Detial['user_id']);
                    $MessageForWhiteWolf = self::$Dt->LG->_('WhiteGourdKiller',array("{0}" => $U_name));
                    HL::SendMessage($MessageForWhiteWolf,$WhiteWolfId);
                    $MsgKiller = self::$Dt->LG->_('WhiteGourdKillerMessage',array("{0}" => $U_name));
                    HL::SendMessage($MsgKiller,$Killer['user_id']);
                    R::GetSet($U_name,'GamePl:role_WhiteWolf:AngelSaved');
                    return true;
                }

                // چک میکنیم بازیکن فرشته روشه یا نه، البته اگه طرف گرگ نبود :)
                if(R::CheckExit('GamePl:role_angel:AngelIn:'.$selected) == true && !in_array($Detial['user_role'],SE::WolfRole())){
                    $MsgUser = self::$Dt->LG->_('GuardSavedYou');
                    HL::SendMessage($MsgUser,$selected);
                    $MsgKiller = self::$Dt->LG->_('GuardBlockedKiller',array("{0}" =>  $U_name));
                    HL::SendMessage($MsgKiller,$Killer['user_id']);
                    R::GetSet($U_name,'GamePl:role_angel:AngelSaved');
                    return true;
                }

                if($Detial['user_role'] == "role_Bloodthirsty"){
                    if(!R::CheckExit('GamePl:VampireFinded')) {
                        $MsgQatel = self::$Dt->LG->_('NotInHomeEat',array("{0}" =>  $U_name));
                        HL::SendMessage($MsgQatel, $Killer['user_id']);
                        return true;
                    }
                }
                //  این پیامیه که توی گروه قراره ارسال بشه
                $groupMessage= self::$Dt->LG->_('HuntsmanKillPlayerGroupMesssage',array("{0}" => $KillerName)).self::$Dt->LG->_('user_role',array("{0}" => self::$Dt->LG->_($Killer['user_role']."_n")));
                // پیام مردنش رو به کاربر میفرسیم
                $MsgUser = self::$Dt->LG->_('HildaKillerKilledYouTow');
                HL::SendMessage($MsgUser,$selected,'kill_hilda');
                // بعدم میکشیمش

                HL::SaveGameActivity($Detial,'kill',$Killer);
                HL::SaveKill($Killer['user_id'], $selected,'kill');

                // اگه طرف کلانتر بود، پیام رو تو گروه میفرستیم  و صبر میکنیم یکی رو بکشه
                if($Detial['user_role'] == "role_kalantar"){
                    HL::HunterKill($groupMessage,$selected,'kill');
                    HL::UserDead($Detial,'kill');
                    self::CheckNatashaIn($selected,$U_name,'killer');
                    R::Del('GamePl:Selected:'.$Killer['user_id']);
                    return true;
                }
                // اگه طرف کلانتر نبود پیام رو ذخیره میکنیم
                HL::SaveMessage($groupMessage);
                HL::UserDead($Detial,'kill');
                self::CheckNatashaIn($selected,$U_name,'killer');
                return true;
                break;
        }

    }

    public static function GetTeamWolfSelected(){

        $Keys = R::Keys('GamePl:Selected:Wolf:*'); // دریافت تمام داده های توی این پترن

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
                    // ارایه رو برابر با عدد بزرگتر کن
                    $max = $row['total'];
                    // ای دی کاربر رو هم اضافه کن به عدد خروجی
                    array_push($maxTotal,$row['user_id']);
                }
            }

            if($maxTotal){
                return $maxTotal['0'];
            }
            return $Selected['0']['user_id'];
        }

        return false;
    }

    public static function WolfTeam(){
        $P_Team = HL::PlayerByTeam();
        $Wolf =  (count($P_Team['wolf']) > 0 ? $P_Team['wolf'] : false);
        // چک کن تیم گرگا وجود داره
        if(!$Wolf){
            return false;
        }

        if(R::CheckExit('GamePl:SendWolfCubeDead')){
            if(R::Get('GamePl:WolfCubeDead') == R::Get('GamePl:Night_no')){
                R::Del('GamePl:WolfCubeDead');
                R::Del('GamePl:SendWolfCubeDead');
                R::Del('GamePl:CheckNight');
                R::Del('GamePl:SendNightAll');
            }
        }

        // تعداد گرگا رو بشمار
        $count_wolf = count($P_Team['wolf']);

        if($count_wolf > 1){
            // مجموع انتخاب تیم گرگ ها رو حساب کن و یه ای دی بده
            $GetSelected = self::GetTeamWolfSelected();
            if(empty($GetSelected)){
                return false;
            }
            $selected = $GetSelected;
            // دریافت نام هم تیمی گرگ ها
            $WolfName = ($Wolf ? implode(',',array_column($Wolf,'Link')) : false);

            $LastWolfD = HL::_getLastWolf();
            $Me_user_id = $LastWolfD['user_id'];
            $Me_Role = $LastWolfD['user_role'];
            $Me_name =  HL::ConvertName($LastWolfD['user_id'],$LastWolfD['fullname_game']);
        }else{
            $Me_user_id = $Wolf['0']['user_id'];
            if(!R::CheckExit('GamePl:Selected:'.$Me_user_id)){
                return false;
            }
            $Me_Role = $Wolf['0']['role'];
            $Me_name =  $Wolf['0']['Link'];
            $selected = R::Get('GamePl:Selected:'.$Me_user_id);
        }

        $Detial = HL::_getPlayer($selected);
        if($Detial['user_state'] !== 1){
            return false;
        }

        if($count_wolf > 1){
            R::GetSet($selected,'GamePl:UserInHome:'.$Me_user_id);
            R::GetSet($Me_name,'GamePl:UserInHome:'.$Me_user_id.":name");
            R::GetSet(self::$Dt->LG->_($LastWolfD['user_role']),'GamePl:UserInHome:'.$Me_user_id.":role");
        }

        R::DelKey('GamePl:Selected:Wolf:*');
        $U_name = HL::ConvertName($selected,$Detial['fullname_game']);
        $Eat = 0;
        $CheckAlpha = HL::CheckAlphaInGame();
        $Alpha = [];
        if($CheckAlpha){
            $Alpha = HL::_getPlayerByRole('role_WolfAlpha');
            if($Alpha['user_state'] !== 1){
                $CheckAlpha = false;
            }
            $AlphaName = HL::ConvertName($Alpha['user_id'],$Alpha['fullname_game']);
            $AlphaBitten = SE::_s('alpha_convert');
        }
        $Enchanter = false;
        if(R::CheckExit('GamePl:Enchanter')){
            $Get = R::Sort('GamePl:Enchanter','desc');
            if(in_array($Detial['user_id'],$Get)){
                $EnchanterBitten = SE::_s('Enchanter_Conver');
                $Enchanter = true;
            }
        }

        $forestQueen = false;
        if(R::CheckExit('GamePl:role_forestQueen:forestQueenBitten')){
            $forestQueenN = HL::_getPlayerByRole('role_forestQueen');
            $forestQueen = ($forestQueenN['user_state'] !== 1 ? false : true);
            $forestQueenName = HL::ConvertName($forestQueenN['user_id'],$forestQueenN['fullname_game']);
            $forestQueenBitten = SE::_s('forestQueen_Convert');
        }
        $NotInHomeMsg = ($count_wolf > 1 ? self::$Dt->LG->_('HarlotNotHomeWolfGroup',array("{0}" =>$U_name)) : self::$Dt->LG->_('HarlotNotHome',array("{0}" =>$U_name)));

        $CheckPrisoner = HL::checkUserINPrisoner($Detial);
        if($CheckPrisoner){
            $WolfMsg = self::$Dt->LG->_('PrincessPrisonerWolfAttack',$U_name);
            HL::SendForWolfTeam($WolfMsg);
            return  true;
        }

        $CheckPhoenix = HL::CheckPhoenixHeal($Detial);
        if($CheckPhoenix){
            $PlayerMessage = self::$Dt->LG->_('MassageAttack');
            HL::SendMessage($PlayerMessage,$Detial['user_id']);
            $AttackerMessage = self::$Dt->LG->_('MessageForWolfTeam',array("{0}" => $U_name));
            HL::SendForWolfTeam($AttackerMessage);
            return  true;
        }

        if(R::CheckExit('GamePl:HuntsmanTraps:'.$Detial['user_id'])){
            if(HL::R(100) >= 50){
                $Huntsman = HL::_getPlayerByRole('role_Huntsman');
                if($Huntsman){
                    if($Huntsman['user_state'] == 1){

                        // پیام برای هانتسمن
                        $HuntsManMessage = self::$Dt->LG->_('HuntsmanMessageKillPlayer',array("{0}" =>$Me_name));
                        HL::SendMessage($HuntsManMessage,$Huntsman['user_id']);
                        // پیام برای شخصی که گرفتار شد
                        $userMessage = self::$Dt->LG->_('HuntsmanKillPlayerMessage',array("{0}" =>$U_name));
                        HL::SendMessage($userMessage,$Me_user_id);

                        // پیام برای گرگا
                        if($count_wolf > 1){
                            $WolfMessage = self::$Dt->LG->_('HunstmanKillWolfMessageMulti',array("{0}" =>$Me_name, "{1}" => $U_name));
                            HL::SendForWolfTeam($WolfMessage,$selected);
                        }

                        // پیام گروه
                        $groupMessage= self::$Dt->LG->_('HuntsmanKillPlayerGroupMesssage',array("{0}" =>$Me_name)).self::$Dt->LG->_('user_role',array("{0}" =>self::$Dt->LG->_($Me_Role."_n")));
                        HL::SaveMessage($groupMessage);
                        // پشتن پلیر
                        HL::UserDead($Me_user_id,'HuntsmanKill');
                        // حذف تله
                        R::Del('GamePl:HuntsmanTraps:'.$Detial['user_id']);

                    }
                }
            }
        }

        switch ($Detial['user_role']){

            case 'role_Bloodthirsty':

                if(!R::CheckExit('GamePl:VampireFinded')) {
                    HL::SendForWolfTeam($NotInHomeMsg);
                    return true;
                }

                if(HL::R(100) < 40){
                    $groupMessage= self::$Dt->LG->_('BloodDeadWolf',array("{0}" =>$Me_name));
                    HL::SaveMessage($groupMessage);
                    HL::UserDead($Me_user_id,'BloodKill');
                    HL::SaveGameActivity(['user_id'=> $Me_user_id,'fullname'=> $Me_name],'vampire',$Detial);
                    return true;
                }
                $MsgUser = self::$Dt->LG->_('eat_you');
                HL::SendMessage($MsgUser,$selected,'eat_wolf');
                $Gp_Message = self::$Dt->LG->_('wolfEat',array("{0}" =>$U_name,"{1}" => self::$Dt->LG->_('user_role',array("{0}" =>self::$Dt->LG->_($Detial['user_role']."_n")))));
                $array_role_p_message = ['role_karagah', 'role_Fereshte', 'role_ahmaq', 'role_tofangdar', 'role_Mast', 'role_pishgo','role_WolfJadogar','role_enchanter'];
                if(in_array($Detial['user_role'],$array_role_p_message)){
                    $Message = HL::_getDeadMesssage($Detial['user_role'],$U_name);
                    if($Message){
                        $Gp_Message = $Message;
                    }
                }
                HL::SaveMessage($Gp_Message);
                HL::SaveKillWolf($P_Team['wolf'],$Detial);
                if($count_wolf == 1) {
                    HL::SaveKill($Me_user_id, $selected,'eat');
                }
                HL::UserDead($Detial,'eat');
                self::CheckNatashaIn($selected,$U_name,'wolf');

                return true;
                break;
            case 'role_NefrinShode':
                R::GetSet(R::Get('GamePl:Night_no'),'GamePl:NotSend:'.$selected);
                // ارسال یه پیام برای کاربر که تبدیل شده
                $MsgUser = ($count_wolf > 1 ? self::$Dt->LG->_('eat_nefrin').PHP_EOL.self::$Dt->LG->_('WolfTeam',array("{0}" =>$WolfName)) : self::$Dt->LG->_('eat_nefrin'));
                HL::SendMessage($MsgUser,$selected);
                // ارسال پیام برای تیم گرگ ها
                $MsgWolf = self::$Dt->LG->_('eat_nefrinForWolf',array("{0}" =>$U_name));
                HL::SendForWolfTeam($MsgWolf,$selected);
                // حالا کاربر رو تبدیلش میکنیم
                HL::ConvertPlayer($selected,'role_WolfGorgine');
                return true;
                break;
            case 'role_Sweetheart':
                if(R::CheckExit('GamePl:SweetheartLove')){
                    if(R::Get('GamePl:SweetheartLove:team') == "wolf"){
                        if(R::CheckExit('GamePl:role_angel:AngelIn:'.$selected) == true){
                            $MsgUser = self::$Dt->LG->_('GuardSavedYou');
                            HL::SendMessage($MsgUser,$selected);
                            $MsgWolf = self::$Dt->LG->_('is_angelWolf', array("{0}" =>$U_name));
                            HL::SendForWolfTeam($MsgWolf);
                            R::GetSet($U_name,'GamePl:role_angel:AngelSaved');
                            return true;
                        }

                        if($Enchanter){
                            if(HL::R(100) < $EnchanterBitten){
                                $MsgWolf = self::$Dt->LG->_('EnchanterPlayerBitten',array("{0}" =>$U_name));
                                HL::SendForWolfTeam($MsgWolf);
                                $MsgUser = self::$Dt->LG->_('EnchanterPlayerBittenOk');
                                HL::SendMessage($MsgUser,$selected);
                                HL::BittanPlayerEnchanter($selected);
                                return true;
                            }
                        }

                        if($forestQueen){
                            if(HL::R(100) < $forestQueenBitten) {
                                $MsgUser = self::$Dt->LG->_('PlayerBitten');
                                HL::SendMessage($MsgUser, $selected);
                                $MsgWolf = ($count_wolf > 1 ? self::$Dt->LG->_('forestQueenBitten',array("{0}" => $U_name, "{1}" => $forestQueenName)) : self::$Dt->LG->_('forestQueenBittenOne', array("{0}" => $U_name)));
                                HL::SendForWolfTeam($MsgWolf);
                                HL::BittanPlayer($selected);
                                return true;
                            }
                        }

                        if($CheckAlpha){
                            if(HL::R(100) < $AlphaBitten){
                                $MsgUser = self::$Dt->LG->_('PlayerBitten');
                                HL::SendMessage($MsgUser,$selected);
                                $MsgWolf = ($count_wolf > 1 ? self::$Dt->LG->_('PlayerBittenWolves',array("{0}" => $U_name,"{1}" =>  $AlphaName)) : self::$Dt->LG->_('PlayerBittenWolf',array("{0}" => $U_name)) );
                                HL::SendForWolfTeam($MsgWolf);
                                HL::BittanPlayer($selected);
                                return true;
                            }
                        }


                        $MsgUser = self::$Dt->LG->_('eat_you');
                        HL::SendMessage($MsgUser,$selected,'eat_wolf');
                        $Gp_Message = self::$Dt->LG->_('wolfEat',array("{0}" =>$U_name,"{1}" => self::$Dt->LG->_('user_role',array("{0}" =>self::$Dt->LG->_($Detial['user_role']."_n")))));
                        $array_role_p_message = ['role_karagah', 'role_Fereshte', 'role_ahmaq', 'role_tofangdar', 'role_Mast', 'role_pishgo','role_WolfJadogar','role_enchanter'];
                        if(in_array($Detial['user_role'],$array_role_p_message)){
                            $Message = HL::_getDeadMesssage($Detial['user_role'],$U_name);
                            if($Message){
                                $Gp_Message = $Message;
                            }
                        }
                        HL::SaveMessage($Gp_Message);
                        HL::UserDead($Detial,'eat');
                        HL::SaveKillWolf($P_Team['wolf'],$Detial);
                        self::CheckNatashaIn($selected,$U_name,'wolf');
                        return true;
                    }
                }

                $MsgWolfPlayer = ($count_wolf > 1 ? self::$Dt->LG->_('MsgWolfPlayerLoverWolfs',array("{0}" =>$U_name)) : self::$Dt->LG->_('MsgWolfPlayerLoverWolfOne',array("{0}" =>$U_name)));
                HL::SendMessage($MsgWolfPlayer,$Me_user_id);
                if($count_wolf > 1){

                    $WolfMessage = self::$Dt->LG->_('MsgWolfs',array("{0}" =>$U_name));
                    HL::SendForWolfTeam($WolfMessage);
                }
                HL::LoverBYSweetheart($Me_user_id,'wolf');
                return true;
                break;
            case 'role_Mast':
                if(R::CheckExit('GamePl:role_angel:AngelIn:'.$selected) == true){
                    $MsgUser = self::$Dt->LG->_('GuardSavedYou');
                    HL::SendMessage($MsgUser,$selected);
                    $MsgWolf = self::$Dt->LG->_('is_angelWolf', array("{0}" => $U_name));
                    HL::SendForWolfTeam($MsgWolf);
                    R::GetSet($U_name,'GamePl:role_angel:AngelSaved');
                    return true;
                }


                if($Enchanter){
                    if(HL::R(100) < $EnchanterBitten){
                        $MsgWolf = self::$Dt->LG->_('EnchanterPlayerBitten',array("{0}" => $U_name));
                        HL::SendForWolfTeam($MsgWolf);
                        $MsgUser = self::$Dt->LG->_('EnchanterPlayerBittenOk');
                        HL::SendMessage($MsgUser,$selected);
                        HL::BittanPlayerEnchanter($selected);
                        return true;
                    }
                }

                if($forestQueen){
                    if(HL::R(100) < $forestQueenBitten) {
                        $MsgUser = self::$Dt->LG->_('PlayerBitten');
                        HL::SendMessage($MsgUser, $selected);
                        $MsgWolf = ($count_wolf > 1 ? self::$Dt->LG->_('forestQueenBitten',array("{0}" => $U_name, "{1}"=> $forestQueenName)) : self::$Dt->LG->_('forestQueenBittenOne', array("{0}" => $U_name)));
                        HL::SendForWolfTeam($MsgWolf);
                        HL::BittanPlayer($selected);
                        return true;
                    }
                }

                if($CheckAlpha){
                    if(HL::R(100) < $AlphaBitten){
                        $MsgUser = self::$Dt->LG->_('PlayerBitten');
                        HL::SendMessage($MsgUser,$selected);
                        $MsgWolf = ($count_wolf > 1 ? self::$Dt->LG->_('PlayerBittenWolves',array("{0}" => $U_name,"{1}" =>  $AlphaName)) : self::$Dt->LG->_('PlayerBittenWolf',array("{0}" => $U_name)) );
                        HL::SendForWolfTeam($MsgWolf);
                        HL::BittanPlayer($selected);
                        HL::SavePlayerAchivment($Alpha['user_id'],'Lucky_Day');
                        return true;
                    }
                }



                R::GetSet((R::Get('GamePl:Night_no') + 1),'GamePl:MastEat');
                $WolfMessage = ($count_wolf > 1 ? self::$Dt->LG->_('mastEatWolfGR',array("{0}" => $U_name)) : self::$Dt->LG->_('masEatWolfOne',array("{0}" => $U_name)));
                HL::SendForWolfTeam($WolfMessage);

                $MsgUser = self::$Dt->LG->_('eat_you');
                HL::SendMessage($MsgUser,$selected,'eat_wolf');
                $Gp_Message = self::$Dt->LG->_('RoleMast_eat',array("{0}" => $U_name));
                HL::SaveMessage($Gp_Message);
                HL::UserDead($Detial,'eat');
                HL::SaveKillWolf($P_Team['wolf'],$Detial);
                self::CheckNatashaIn($selected,$U_name,'wolf');
                return true;
                break;
            case 'role_rishSefid':
                if(R::CheckExit('GamePl:role_angel:AngelIn:'.$selected) == true){
                    $MsgUser = self::$Dt->LG->_('GuardSavedYou');
                    HL::SendMessage($MsgUser,$selected);
                    $MsgWolf = self::$Dt->LG->_('is_angelWolf', array("{0}" =>$U_name));
                    HL::SendForWolfTeam($MsgWolf);
                    R::GetSet($U_name,'GamePl:role_angel:AngelSaved');
                    return true;
                }


                if(R::CheckExit('GamePl:Eatelder') == false){
                    $MsgUser = self::$Dt->LG->_('EatRishSefidWolfSe');
                    HL::SendMessage($MsgUser,$selected);
                    $MsgWolf = ($count_wolf > 1 ? self::$Dt->LG->_('EatRishSefidTotal',array("{0}" =>$U_name)) : self::$Dt->LG->_('EatRishSefid',array("{0}" =>$U_name)));
                    HL::SendForWolfTeam($MsgWolf);
                    R::GetSet(true,'GamePl:Eatelder');
                    return true;
                }

                if($Enchanter){
                    if(HL::R(100) < $EnchanterBitten){
                        $MsgWolf = self::$Dt->LG->_('EnchanterPlayerBitten',array("{0}" =>$U_name));
                        HL::SendForWolfTeam($MsgWolf);
                        $MsgUser = self::$Dt->LG->_('EnchanterPlayerBittenOk');
                        HL::SendMessage($MsgUser,$selected);
                        HL::BittanPlayerEnchanter($selected);
                        return true;
                    }
                }

                if($forestQueen){
                    if(HL::R(100) < $forestQueenBitten) {
                        $MsgUser = self::$Dt->LG->_('PlayerBitten');
                        HL::SendMessage($MsgUser, $selected);
                        $MsgWolf = ($count_wolf > 1 ? self::$Dt->LG->_('forestQueenBitten',array("{0}" => $U_name, "{1}"=> $forestQueenName)) : self::$Dt->LG->_('forestQueenBittenOne', array("{0}" => $U_name)));
                        HL::SendForWolfTeam($MsgWolf);
                        HL::BittanPlayer($selected);
                        return true;
                    }
                }

                if($CheckAlpha){
                    if(HL::R(100) < $AlphaBitten){
                        $MsgUser = self::$Dt->LG->_('PlayerBitten');
                        HL::SendMessage($MsgUser,$selected);
                        $MsgWolf = ($count_wolf > 1 ? self::$Dt->LG->_('PlayerBittenWolves',array("{0}" => $U_name,"{1}" =>  $AlphaName)) : self::$Dt->LG->_('PlayerBittenWolf',array("{0}" => $U_name)) );
                        HL::SendForWolfTeam($MsgWolf);
                        HL::BittanPlayer($selected);
                        HL::SavePlayerAchivment($Alpha['user_id'],'Lucky_Day');
                        return true;
                    }
                }


                $MsgUser = self::$Dt->LG->_('eat_you');
                HL::SendMessage($MsgUser,$selected,'eat_wolf');
                $Gp_Message = self::$Dt->LG->_('wolfEat',array("{0}" =>$U_name,"{1}" => self::$Dt->LG->_('user_role',array("{0}" =>self::$Dt->LG->_($Detial['user_role']."_n")))));
                HL::SaveMessage($Gp_Message);
                HL::UserDead($Detial,'eat');
                HL::SaveKillWolf($P_Team['wolf'],$Detial);
                self::CheckNatashaIn($selected,$U_name,'wolf');
                return true;
                break;
            case 'role_Joker':
                $LastWolf = HL::_getLastWolf();
                $WolfName = HL::ConvertName($LastWolf['user_id'],$LastWolf['fullname_game']);
                $checkAttack = self::CheckAttack($Detial,'wolf',$LastWolf,$WolfName,($count_wolf > 1 ? true : false));
                if($checkAttack){
                    return  true;
                }
                if(R::CheckExit('GamePl:role_angel:AngelIn:'.$selected) == true){
                    $MsgUser = self::$Dt->LG->_('GuardSavedYou');
                    HL::SendMessage($MsgUser,$selected);
                    $MsgWolf = self::$Dt->LG->_('is_angelWolf', array("{0}" =>$U_name));
                    HL::SendForWolfTeam($MsgWolf);
                    R::GetSet($U_name,'GamePl:role_angel:AngelSaved');
                    return true;
                }



                if($Enchanter){
                    if(HL::R(100) < $EnchanterBitten){
                        $MsgWolf = self::$Dt->LG->_('EnchanterPlayerBitten',array("{0}" =>$U_name));
                        HL::SendForWolfTeam($MsgWolf);
                        $MsgUser = self::$Dt->LG->_('EnchanterPlayerBittenOk');
                        HL::SendMessage($MsgUser,$selected);
                        HL::BittanPlayerEnchanter($selected);
                        return true;
                    }
                }

                if($forestQueen){
                    if(HL::R(100) < $forestQueenBitten) {
                        $MsgUser = self::$Dt->LG->_('PlayerBitten');
                        HL::SendMessage($MsgUser, $selected);
                        $MsgWolf = ($count_wolf > 1 ? self::$Dt->LG->_('forestQueenBitten',array("{0}" => $U_name, "{1}"=> $forestQueenName)) : self::$Dt->LG->_('forestQueenBittenOne', array("{0}" => $U_name)));
                        HL::SendForWolfTeam($MsgWolf);
                        HL::BittanPlayer($selected);
                        return true;
                    }
                }

                if($CheckAlpha){
                    if(HL::R(100) < $AlphaBitten){
                        $MsgUser = self::$Dt->LG->_('PlayerBitten');
                        HL::SendMessage($MsgUser,$selected);
                        $MsgWolf = ($count_wolf > 1 ? self::$Dt->LG->_('PlayerBittenWolves',array("{0}" => $U_name,"{1}" =>  $AlphaName)) : self::$Dt->LG->_('PlayerBittenWolf',array("{0}" => $U_name)) );
                        HL::SendForWolfTeam($MsgWolf);
                        HL::BittanPlayer($selected);
                        HL::SavePlayerAchivment($Alpha['user_id'],'Lucky_Day');
                        return true;
                    }
                }


                $MsgUser = self::$Dt->LG->_('eat_you');
                HL::SendMessage($MsgUser,$selected,'eat_wolf');
                $Gp_Message = self::$Dt->LG->_('wolfEat',array("{0}" =>$U_name,"{1}" => self::$Dt->LG->_('user_role',array("{0}" =>self::$Dt->LG->_($Detial['user_role']."_n")))));
                HL::SaveMessage($Gp_Message);
                HL::UserDead($Detial,'eat');
                HL::SaveKillWolf($P_Team['wolf'],$Detial);
                self::CheckNatashaIn($selected,$U_name,'wolf');
                return true;
                break;
            case 'role_Qatel':
                if(HL::R(100) < 80){
                    $LastWolf = HL::_getLastWolf();
                    $WolfName = HL::ConvertName($LastWolf['user_id'],$LastWolf['fullname_game']);
                    $Gp_Message = self::$Dt->LG->_('SerialKillerKilledWolf',array("{0}" => $WolfName,"{1}" => self::$Dt->LG->_('user_role',array("{0}" => self::$Dt->LG->_($LastWolf['user_role']."_n")))));
                    HL::SaveMessage($Gp_Message);
                    HL::UserDead($LastWolf,'kill');
                    HL::SaveGameActivity($LastWolf,'kill',$Detial);
                    return true;
                }

                if($Enchanter){
                    if(HL::R(100) < $EnchanterBitten){
                        $MsgWolf = self::$Dt->LG->_('EnchanterPlayerBitten',array("{0}" =>$U_name));
                        HL::SendForWolfTeam($MsgWolf);
                        $MsgUser = self::$Dt->LG->_('EnchanterPlayerBittenOk');
                        HL::SendMessage($MsgUser,$selected);
                        HL::BittanPlayerEnchanter($selected);
                        return true;
                    }
                }

                if($forestQueen){
                    if(HL::R(100) < $forestQueenBitten) {
                        $MsgUser = self::$Dt->LG->_('PlayerBitten');
                        HL::SendMessage($MsgUser, $selected);
                        $MsgWolf = ($count_wolf > 1 ? self::$Dt->LG->_('forestQueenBitten',array("{0}" => $U_name, "{1}"=> $forestQueenName)) : self::$Dt->LG->_('forestQueenBittenOne', array("{0}" => $U_name)));
                        HL::SendForWolfTeam($MsgWolf);
                        HL::BittanPlayer($selected);
                        return true;
                    }
                }

                if($CheckAlpha){
                    if(HL::R(100) < $AlphaBitten){
                        $MsgUser = self::$Dt->LG->_('PlayerBitten');
                        HL::SendMessage($MsgUser,$selected);
                        $MsgWolf = ($count_wolf > 1 ? self::$Dt->LG->_('PlayerBittenWolves',array("{0}" => $U_name,"{1}" =>  $AlphaName)) : self::$Dt->LG->_('PlayerBittenWolf',array("{0}" => $U_name)) );
                        HL::SendForWolfTeam($MsgWolf);
                        HL::BittanPlayer($selected);
                        HL::SavePlayerAchivment($Alpha['user_id'],'Lucky_Day');
                        return true;
                    }
                }


                $MsgUser = self::$Dt->LG->_('eat_you');
                HL::SendMessage($MsgUser,$selected,'eat_wolf');
                $Gp_Message = self::$Dt->LG->_('wolfEat',array("{0}" =>$U_name,"{1}" => self::$Dt->LG->_('user_role',array("{0}" =>self::$Dt->LG->_($Detial['user_role']."_n")))));
                HL::SaveMessage($Gp_Message);
                HL::UserDead($Detial,'eat');
                HL::SaveKillWolf($P_Team['wolf'],$Detial);
                self::CheckNatashaIn($selected,$U_name,'wolf');
                return true;
                break;
            case 'role_kentvampire':




                $MsgUser = self::$Dt->LG->_('eat_you');
                HL::SendMessage($MsgUser,$selected,'eat_wolf');
                $Gp_Message = self::$Dt->LG->_('wolfEat',array("{0}" =>$U_name,"{1}" => self::$Dt->LG->_('user_role',array("{0}" =>self::$Dt->LG->_($Detial['user_role']."_n")))));
                HL::SaveMessage($Gp_Message);
                HL::UserDead($Detial,'eat');
                HL::SaveKillWolf($P_Team['wolf'],$Detial);
                self::CheckNatashaIn($selected,$U_name,'wolf');

                if(HL::R(100) < 50){
                    $LastWolf = HL::_getLastWolf();
                    $WolfName = HL::ConvertName($LastWolf['user_id'],$LastWolf['fullname_game']);
                    $Gp_Message = self::$Dt->LG->_('KentVampireKillWolfGroupMessage',array("{0}" => $WolfName,"{1}" => self::$Dt->LG->_('user_role',array("{0}" => self::$Dt->LG->_($LastWolf['user_role']."_n")))));
                    HL::SaveMessage($Gp_Message);
                    HL::UserDead($LastWolf,'KentKill');
                    HL::SaveGameActivity($LastWolf,'KentKill',$Detial);
                    return true;
                }


                break;
            case 'role_faheshe':
                if(R::CheckExit('GamePl:UserInHome:'.$selected)){
                    HL::SendForWolfTeam($NotInHomeMsg);
                    return true;
                }

                if(R::CheckExit('GamePl:role_angel:AngelIn:'.$selected) == true){
                    $MsgUser = self::$Dt->LG->_('GuardSavedYou');
                    HL::SendMessage($MsgUser,$selected);
                    $MsgWolf = self::$Dt->LG->_('is_angelWolf', array("{0}" =>$U_name));
                    HL::SendForWolfTeam($MsgWolf);
                    R::GetSet($U_name,'GamePl:role_angel:AngelSaved');
                    return true;
                }

                if($Enchanter){
                    if(HL::R(100) < $EnchanterBitten){
                        $MsgWolf = self::$Dt->LG->_('EnchanterPlayerBitten',array("{0}" =>$U_name));
                        HL::SendForWolfTeam($MsgWolf);
                        $MsgUser = self::$Dt->LG->_('EnchanterPlayerBittenOk');
                        HL::SendMessage($MsgUser,$selected);
                        HL::BittanPlayerEnchanter($selected);
                        return true;
                    }
                }

                if($forestQueen){
                    if(HL::R(100) < $forestQueenBitten) {
                        $MsgUser = self::$Dt->LG->_('PlayerBitten');
                        HL::SendMessage($MsgUser, $selected);
                        $MsgWolf = ($count_wolf > 1 ? self::$Dt->LG->_('forestQueenBitten',array("{0}" => $U_name, "{1}"=> $forestQueenName)) : self::$Dt->LG->_('forestQueenBittenOne', array("{0}" => $U_name)));
                        HL::SendForWolfTeam($MsgWolf);
                        HL::BittanPlayer($selected);
                        return true;
                    }
                }

                if($CheckAlpha){
                    if(HL::R(100) < $AlphaBitten){
                        $MsgUser = self::$Dt->LG->_('PlayerBitten');
                        HL::SendMessage($MsgUser,$selected);
                        $MsgWolf = ($count_wolf > 1 ? self::$Dt->LG->_('PlayerBittenWolves',array("{0}" => $U_name,"{1}" =>  $AlphaName)) : self::$Dt->LG->_('PlayerBittenWolf',array("{0}" => $U_name)) );
                        HL::SendForWolfTeam($MsgWolf);
                        HL::BittanPlayer($selected);
                        return true;
                    }
                }


                $MsgUser = self::$Dt->LG->_('eat_you');
                HL::SendMessage($MsgUser,$selected,'eat_wolf');
                $Gp_Message = self::$Dt->LG->_('wolfEat',array("{0}" =>$U_name,"{1}" => self::$Dt->LG->_('user_role',array("{0}" =>self::$Dt->LG->_($Detial['user_role']."_n")))));
                HL::SaveMessage($Gp_Message);
                HL::UserDead($Detial,'eat');
                HL::SaveKillWolf($P_Team['wolf'],$Detial);

                return true;

                break;
            case 'role_kalantar':

                if(R::CheckExit('GamePl:role_angel:AngelIn:'.$selected) == true){
                    $MsgUser = self::$Dt->LG->_('GuardSavedYou');
                    HL::SendMessage($MsgUser,$selected);
                    $MsgWolf = self::$Dt->LG->_('is_angelWolf', array("{0}" =>$U_name));
                    HL::SendForWolfTeam($MsgWolf);
                    R::GetSet($U_name,'GamePl:role_angel:AngelSaved');
                    return true;
                }

                $chance = SE::_s('HunterKillWolfChanceBase') + (($count_wolf - 1) * 20);
                // چک کردن شانس کلانتر
                if(HL::R(100) < $chance){
                    $LastWolf = HL::_getLastWolf();
                    $WolfName = HL::ConvertName($LastWolf['user_id'],$LastWolf['fullname_game']);

                    if($count_wolf == 1){

                        HL::SaveGameActivity($LastWolf,'shot',$Detial);
                        $Gp_Message = self::$Dt->LG->_('HunterShotWolf',array("{0}" => $WolfName, "{1}" => self::$Dt->LG->_('user_role',array("{0}" =>self::$Dt->LG->_($LastWolf['user_role']."_n")))));
                        HL::SaveMessage($Gp_Message);
                        HL::UserDead($LastWolf,'shot');
                        return true;
                    }

                    if($count_wolf > 1){

                        HL::SaveGameActivity($Detial,'eat',['user_id'=> $Me_user_id,'fullname'=> $Me_name]);

                        HL::UserDead($LastWolf,'shot');
                        HL::SaveGameActivity($LastWolf,'shot',$Detial);
                        $MsgUser = self::$Dt->LG->_('eat_you');
                        HL::SendMessage($MsgUser,$selected,'eat_wolf');
                        $Gp_Message = self::$Dt->LG->_('HunterShotWolfMulti',array("{0}" => $U_name, "{1}" => $WolfName,"{2}" => self::$Dt->LG->_('user_role',array("{0}" => self::$Dt->LG->_($LastWolf['user_role']."_n")))));
                        HL::SaveMessage($Gp_Message);
                        HL::UserDead($Detial,'eat');
                        self::CheckNatashaIn($selected,$U_name,'wolf');
                        return true;
                    }
                }

                if($Enchanter){
                    if(HL::R(100) < $EnchanterBitten){
                        $MsgWolf = self::$Dt->LG->_('EnchanterPlayerBitten',array("{0}" =>$U_name));
                        HL::SendForWolfTeam($MsgWolf);
                        $MsgUser = self::$Dt->LG->_('EnchanterPlayerBittenOk');
                        HL::SendMessage($MsgUser,$selected);
                        HL::BittanPlayerEnchanter($selected);
                        return true;
                    }
                }

                if($forestQueen){
                    if(HL::R(100) < $forestQueenBitten) {
                        $MsgUser = self::$Dt->LG->_('PlayerBitten');
                        HL::SendMessage($MsgUser, $selected);
                        $MsgWolf = ($count_wolf > 1 ? self::$Dt->LG->_('forestQueenBitten',array("{0}" => $U_name, "{1}"=> $forestQueenName)) : self::$Dt->LG->_('forestQueenBittenOne', array("{0}" => $U_name)));
                        HL::SendForWolfTeam($MsgWolf);
                        HL::BittanPlayer($selected);
                        return true;
                    }
                }

                if($CheckAlpha){
                    if(HL::R(100) < $AlphaBitten){
                        $MsgUser = self::$Dt->LG->_('PlayerBitten');
                        HL::SendMessage($MsgUser,$selected);
                        $MsgWolf = ($count_wolf > 1 ? self::$Dt->LG->_('PlayerBittenWolves',array("{0}" => $U_name,"{1}" =>  $AlphaName)) : self::$Dt->LG->_('PlayerBittenWolf',array("{0}" => $U_name)) );
                        HL::SendForWolfTeam($MsgWolf);
                        HL::BittanPlayer($selected);
                        return true;
                    }
                }


                $MsgUser = self::$Dt->LG->_('eat_you');
                HL::SendMessage($MsgUser,$selected,'eat_wolf');
                $Gp_Message = self::$Dt->LG->_('wolfEat',array("{0}" =>$U_name,"{1}" => self::$Dt->LG->_('user_role',array("{0}" =>self::$Dt->LG->_($Detial['user_role']."_n")))));
                HL::SaveMessage($Gp_Message);
                HL::UserDead($Detial,'eat');
                HL::SaveKillWolf($P_Team['wolf'],$Detial);

                self::CheckNatashaIn($selected,$U_name,'wolf');
                return true;

                break;

            default:
                if(R::CheckExit('GamePl:role_angel:AngelIn:'.$selected) == true){
                    $MsgUser = self::$Dt->LG->_('GuardSavedYou');
                    HL::SendMessage($MsgUser,$selected);
                    $MsgWolf = self::$Dt->LG->_('is_angelWolf', array("{0}" =>$U_name));
                    HL::SendForWolfTeam($MsgWolf);
                    R::GetSet($U_name,'GamePl:role_angel:AngelSaved');
                    return true;
                }


                if($Enchanter){
                    if(HL::R(100) < $EnchanterBitten){
                        $MsgWolf = self::$Dt->LG->_('EnchanterPlayerBitten',array("{0}" =>$U_name));
                        HL::SendForWolfTeam($MsgWolf);
                        $MsgUser = self::$Dt->LG->_('EnchanterPlayerBittenOk');
                        HL::SendMessage($MsgUser,$selected);
                        HL::BittanPlayerEnchanter($selected);
                        return true;
                    }
                }

                if($forestQueen){
                    if(HL::R(100) < $forestQueenBitten) {
                        $MsgUser = self::$Dt->LG->_('PlayerBitten');
                        HL::SendMessage($MsgUser, $selected);
                        $MsgWolf = ($count_wolf > 1 ? self::$Dt->LG->_('forestQueenBitten',array("{0}" => $U_name, "{1}"=> $forestQueenName)) : self::$Dt->LG->_('forestQueenBittenOne', array("{0}" => $U_name)));
                        HL::SendForWolfTeam($MsgWolf);
                        HL::BittanPlayer($selected);
                        return true;
                    }
                }

                if($CheckAlpha){
                    if(HL::R(100) < $AlphaBitten){
                        $MsgUser = self::$Dt->LG->_('PlayerBitten');
                        HL::SendMessage($MsgUser,$selected);
                        $MsgWolf = ($count_wolf > 1 ? self::$Dt->LG->_('PlayerBittenWolves',array("{0}" => $U_name,"{1}" =>  $AlphaName)) : self::$Dt->LG->_('PlayerBittenWolf',array("{0}" => $U_name)) );
                        HL::SendForWolfTeam($MsgWolf);
                        HL::BittanPlayer($selected);
                        //» گرگنما باشیو گرگ آلفا تبدیل به یه گرگ واقعیت کنه
                        if($Detial['user_role'] == "role_Gorgname"){
                            $LastWolf = HL::_getLastWolf();
                            HL::SavePlayerAchivment($Detial['user_id'],'Just_a_Beardy_Guy');
                        }
                        return true;
                    }
                }


                //» همانطور که آخرین گرگ زنده است، خائن را بخور وای نه
                if($count_wolf == 1 && $Detial['user_role'] == "role_Khaen"){
                    $LastWolf = HL::_getLastWolf();
                    HL::SavePlayerAchivment($LastWolf['user_id'],'Condition_Red');
                }

                $MsgUser = self::$Dt->LG->_('eat_you');
                HL::SendMessage($MsgUser,$selected,'eat_wolf');
                $Gp_Message = self::$Dt->LG->_('wolfEat',array("{0}" =>$U_name,"{1}" => self::$Dt->LG->_('user_role',array("{0}" =>self::$Dt->LG->_($Detial['user_role']."_n")))));
                $array_role_p_message = ['role_karagah', 'role_Fereshte', 'role_ahmaq', 'role_tofangdar', 'role_Mast',"role_PishRezerv", 'role_pishgo','role_WolfJadogar','role_enchanter'];
                if(in_array($Detial['user_role'],$array_role_p_message)){
                    $Message = HL::_getDeadMesssage($Detial['user_role'],$U_name);
                    if($Message){
                        $Gp_Message = $Message;
                    }
                }
                HL::SaveMessage($Gp_Message);
                HL::UserDead($Detial,'eat');
                HL::SaveKillWolf($P_Team['wolf'],$Detial);
                self::CheckNatashaIn($selected,$U_name,'wolf');
                return true;
                break;
        }

    }

    public static function GetMessageNight($role){
        $msg = "Msg Not Found";
        switch ($role){
            case 'role_Huntsman':
                $msg = self::$Dt->LG->_('role_hunstmanAsk', array("{0}" => R::Get('GamePl:HuntsmanT')));
                break;
                case 'role_Phoenix':
                $msg = self::$Dt->LG->_('MessagePhoenixForOne');
                break;
            case 'role_Chemist':
                $msg = self::$Dt->LG->_('AskChemist');
                break;
            case 'role_ferqe':
            case 'role_Royce':
                $msg = self::$Dt->LG->_('AskConvert', array("{0}" => ''));
                break;
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_forestQueen':
            case 'role_Wolfx':
            case 'role_WolfAlpha':
                $msg =  self::$Dt->LG->_('HowWasEatUser');
                break;
            case 'role_WolfJadogar':
                $msg =  self::$Dt->LG->_('AskDetect');
                break;
            case 'role_Honey':
                $msg =  self::$Dt->LG->_('Ask_Honey');
                break;
            case 'role_Qatel':
                $msg = self::$Dt->LG->_('AskKill');
                break;
            case 'role_pishgo':
                $msg = self::$Dt->LG->_('howSeeIs');
                break;
            case 'role_elahe':
                $msg =self::$Dt->LG->_('AskCupid1');
                break;
            case 'role_Hamzad':
                $msg = self::$Dt->LG->_('hamzad_L');
                break;
            case 'role_Fereshte':
                $msg = self::$Dt->LG->_('HowAngelIs');
                break;
            case 'role_Vahshi':
                $msg =self::$Dt->LG->_('vahshi_L');
                break;

            case 'role_shekar':
                $msg = self::$Dt->LG->_('howToHoHome');
                break;
            case 'role_ahmaq':
                $msg = self::$Dt->LG->_('howSeeIs');
                break;
            case 'role_ngativ':
                $msg = self::$Dt->LG->_('Negativ_l');
                break;
            case 'role_Mouse':
                $msg = self::$Dt->LG->_('role_MouseAsk');
                break;
            case 'role_faheshe':
                $msg = self::$Dt->LG->_('howFahesheIs');
                break;
            case 'role_WhiteWolf':
                $msg = self::$Dt->LG->_('AskWhiteWolf');
                break;
            case 'role_Vampire':
            case 'role_Bloodthirsty':
                $msg = self::$Dt->LG->_('AskVampire');
                break;
            case 'role_enchanter':
                $msg = self::$Dt->LG->_('AskEnchanter');
                break;
            case 'role_Firefighter':
                $msg = self::$Dt->LG->_('AskFireFighter');
                break;
            case 'role_IceQueen':
                $msg = self::$Dt->LG->_('IceQeenAsk');
                break;
            case 'role_Archer':
                $msg = self::$Dt->LG->_('AskArcher');
                break;
            case 'role_Knight':
                $msg = self::$Dt->LG->_('KnightAsk');
                break;
        }

        return $msg;
    }



    public static function SendNightRole(){



        $Players = HL::_getPlayerINRole(['role_Joker','role_Harly','role_Phoenix','role_hilda','role_kentvampire','role_forestQueen','role_WolfTolle','role_WolfGorgine','role_Wolfx','role_WolfAlpha','role_WhiteWolf','role_WolfJadogar','role_Vampire','role_Bloodthirsty','role_enchanter','role_lucifer','role_Firefighter','role_IceQueen','role_Honey','role_Royce','role_ferqe','role_Qatel','role_Archer','role_pishgo','role_Knight','role_elahe','role_Hamzad','role_Fereshte','role_Huntsman','role_Vahshi','role_shekar','role_ahmaq','role_ngativ','role_faheshe','role_Watermelon','role_Mouse','role_Chemist']);
        $P_Team = HL::PlayerByTeam();
        $Wolf =  (count($P_Team['wolf']) > 0 ? $P_Team['wolf'] : false);
        $ferqe =  (count($P_Team['ferqe']) > 0 ? $P_Team['ferqe'] : false);
        $Vampire =  (count($P_Team['vampire']) > 0 ? $P_Team['vampire'] : false);
        $Qatel =  (count($P_Team['Qatel']) > 0 ? $P_Team['Qatel'] : false);


        $Count = 0;
        $CountPlayer = count($Players);
        foreach ($Players as $row){

            $Count++;

            if($Count == $CountPlayer){
                R::GetSet(time(),'GamePl:SendNightAll');
            }

            if(R::CheckExit('GamePl:NotSendNight')){
                if(R::Get('GamePl:NotSendNight') == R::Get('GamePl:Night_no')){
                    continue;
                }
            }

            if(R::CheckExit('GamePl:NotSend:'.$row['user_id'])){
                if(R::Get('GamePl:NotSend:'.$row['user_id']) == R::Get('GamePl:Night_no')){
                    continue;
                }
            }
            if(R::CheckExit('GamePl:PrincessPrisoner:'.$row['user_id'])){
                 continue;
            }

            if(HL::CheckSendNight($row['user_id']) || R::CheckExit('GamePl:NotSend_'.$row['user_role'])){
                continue;
            }

            $link = HL::_getName($row['fullname_game'],$row['user_id']);

            switch ($row['user_role']){
                case 'role_forestQueen':

                    // اگه اهن زده بودن یا مست خورده بودن برو بعدی رو چک کن
                    if(R::CheckExit('GamePl:role_forestQueen:AlphaDead') == false || R::CheckExit('GamePl:MastEat') || R::CheckExit('GamePl:AhangarOk')){
                        continue 2;
                    }
                    $wolfUserId = ($Wolf ? array_column($Wolf,'user_id') : []);
                    $WolfName = ($Wolf ? implode(',',join::doNotAssign($link,array_column($Wolf,'Link'))) : false);
                    $rows = HL::GetPlayerNonKeyboard($wolfUserId, 'NightSelect_Wolf');
                    $inline_keyboard = new InlineKeyboard(...$rows);

                    $Msg = ($WolfName !== "" ? self::$Dt->LG->_('eatUserTeem',array("{0}" => PHP_EOL.self::$Dt->LG->_('WolfTeam',array("{0}" =>$WolfName)))) : self::$Dt->LG->_('HowWasEatUser') );

                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => $Msg,
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);

                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                    }

                    break;
                case 'role_WolfTolle':
                case 'role_WolfGorgine':
                case 'role_Wolfx':
                case 'role_WolfAlpha':


                    // اگه اهن زده بودن یا مست خورده بودن برو بعدی رو چک کن
                    if(R::CheckExit('GamePl:MastEat') || R::CheckExit('GamePl:AhangarOk')){
                        continue 2;
                    }

                    $wolfUserId = ($Wolf ? array_column($Wolf,'user_id') : []);
                    $WolfName = ($Wolf ? implode(',',join::doNotAssign($link,array_column($Wolf,'Link'))) : false);
                    $rows = HL::GetPlayerNonKeyboard($wolfUserId, 'NightSelect_Wolf');
                    $inline_keyboard = new InlineKeyboard(...$rows);

                    $Msg = ($WolfName !== "" ? self::$Dt->LG->_('eatUserTeem',array("{0}" =>PHP_EOL.self::$Dt->LG->_('WolfTeam',array("{0}" =>$WolfName)))) : self::$Dt->LG->_('HowWasEatUser') );

                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => $Msg,
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);

                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                    }
                    break;
                case 'role_WhiteWolf':

                    $wolfUserId = ($Wolf ? array_column($Wolf,'user_id') : []);
                    $rows = HL::GetPlayerNonKeyboard($wolfUserId, 'NightSelect_WhiteWolf',true);
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('AskWhiteWolf'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                    }
                     break;
                    case 'role_Joker':
                    $HarlyID = HL::GetRoleUserId('role_Harly');
                    $NotIn = ($HarlyID ? [$row['user_id'],$HarlyID] : [$row['user_id']]);

                    $rows = HL::GetPlayerNonKeyboard($NotIn, 'NightSelect_Joker',true);
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('AskJokerFind'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);

                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                    }
                    break;

                    case 'role_Harly':
                        if(!R::CheckExit('GamePl:DiedJoker')) {
                            continue 2;
                        }
                       $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'NightSelect_Harly',true);
                       $inline_keyboard = new InlineKeyboard(...$rows);
                      $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('AskHarly'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);

                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                    }
                    break;
                case 'role_WolfJadogar':


                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'NightSelect_Jado');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('askJado'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                    }
                    break;
                case 'role_Vampire':
                case 'role_Bloodthirsty':
                    if(R::CheckExit('GamePl:Bloodthirsty') == false and $row['user_role'] == "role_Bloodthirsty"){
                        continue 2;
                    }

                    $VampireUserId = ($Vampire ? array_column($Vampire,'user_id') : []);
                    $VampireName = ($Vampire ? implode(',',join::doNotAssign($link,array_column($Vampire,'Link'))) : false);
                    $MessageAsk = (R::CheckExit('GamePl:VampireConvert') ? ($VampireName ? self::$Dt->LG->_('AskWhenBloodTeam',array("{0}" =>$VampireName)) : self::$Dt->LG->_('AskWhenBlood')) : self::$Dt->LG->_('AskVampire'));


                    $rows = HL::GetPlayerNonKeyboard($VampireUserId, 'NightSelect_Vampire');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => $MessageAsk,
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');

                    }
                    break;

                case 'role_kentvampire':
                    if(R::CheckExit('GamePl:KentVampireConvert')){
                        continue 2;
                    }
                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'NightSelect_KentVampire');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' =>  self::$Dt->LG->_('AskKentVampire'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');

                    }
                    break;

                case 'role_enchanter':


                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'NightSelect_Enchanter');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('AskEnchanter'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                    }
                    break;
                case 'role_Chemist':
                    if(R::Get('GamePl:Night_no') == 0) {
                        Request::sendMessage([
                            'chat_id' => $row['user_id'],
                            'text' => self::$Dt->LG->_('ChemistBrewing'),
                            'parse_mode' => 'HTML',
                        ]);
                        R::rpush($row['user_id'], 'GamePl:SendNight');
                    }else{
                        $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'NightSelect_Chemist');
                        $inline_keyboard = new InlineKeyboard(...$rows);
                        $result = Request::sendMessage([
                            'chat_id' => $row['user_id'],
                            'text' => self::$Dt->LG->_('AskChemist'),
                            'reply_markup' => $inline_keyboard,
                            'parse_mode' => 'HTML',
                        ]);
                        if ($result->isOk()) {
                            R::rpush($row['user_id'], 'GamePl:SendNight');
                            R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                        }
                    }
                    break;
                case 'role_lucifer':

                    if(R::Get('GamePl:Night_no') == 0){
                        $KeyboardTeam = [];
                        $KeyboardTeam[] =
                            [
                                ['text' => self::$Dt->LG->_('RostaTeam'), 'callback_data' => "NightSelect_LuciferSelectTeam/" . self::$Dt->chat_id . "/rosta"]
                            ];

                        ($Wolf ? $KeyboardTeam[] =  [
                            ['text' => self::$Dt->LG->_('WolfTeams'), 'callback_data' => "NightSelect_LuciferSelectTeam/" . self::$Dt->chat_id . "/wolf"]
                        ] : "");
                        ($Vampire ? $KeyboardTeam[] =  [
                            ['text' => self::$Dt->LG->_('VampireTeams'), 'callback_data' => "NightSelect_LuciferSelectTeam/" . self::$Dt->chat_id . "/vampire"]
                        ] : "");

                        ($ferqe ? $KeyboardTeam[] =  [
                            ['text' => self::$Dt->LG->_('FerqeTeam'), 'callback_data' => "NightSelect_LuciferSelectTeam/" . self::$Dt->chat_id . "/ferqeTeem"]
                        ] : "");


                        ($Qatel ? $KeyboardTeam[] =  [
                            ['text' => self::$Dt->LG->_('QatelTeam'), 'callback_data' => "NightSelect_LuciferSelectTeam/" . self::$Dt->chat_id . "/qatel"]
                        ] : "");


                        $inline_keyboard = new InlineKeyboard(...$KeyboardTeam);
                        $result =  Request::sendMessage([
                            'chat_id' => $row['user_id'],
                            'text' => self::$Dt->LG->_('SelectedTeamLucifer'),
                            'reply_markup' => $inline_keyboard,
                            'parse_mode' => 'HTML',
                        ]);
                        if($result->isOk()){
                            R::rpush($row['user_id'],'GamePl:SendNight');
                            R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                        }
                    }else {

                        $Team = R::Get("GamePl:user:{$row['user_id']}:team");
                        $userIds = [$row['user_id']];
                        if( $Team == "wolf") {
                            $userIds = ($Wolf ? array_column($Wolf, 'user_id') : []);
                        }elseif($Team == "qatel"){
                            $userIds = ($Qatel ? array_column($Qatel, 'user_id') : []);
                        }elseif($Team == "vampire"){
                            $userIds = ($Vampire ? array_column($Vampire, 'user_id') : []);
                        }elseif($Team == "ferqeTeem"){
                            $userIds = ($ferqe ? array_column($ferqe, 'user_id') : []);
                        }

                        $rows = HL::GetPlayerNonKeyboard($userIds, 'NightSelect_Lucifer');

                        $inline_keyboard = new InlineKeyboard(...$rows);
                        $result = Request::sendMessage([
                            'chat_id' => $row['user_id'],
                            'text' => self::$Dt->LG->_('LuciferNightSelect'),
                            'reply_markup' => $inline_keyboard,
                            'parse_mode' => 'HTML',
                        ]);
                        if ($result->isOk()) {
                            R::rpush($row['user_id'],'GamePl:SendNight');
                            R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                        }
                    }
                    break;
                    break;
                case 'role_Firefighter':

                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'NightSelect_Firefighter');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('AskFireFighter'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                    }
                    break;
                case 'role_IceQueen':

                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'NightSelect_IceQueen');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('IceQeenAsk'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                    }
                    break;

                case 'role_Honey':
                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'NightSelect_Honey');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('Ask_Honey'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');

                    }
                    break;
                case 'role_Royce':
                case 'role_ferqe':
                    $FerqeUserId = ($ferqe ? array_column($ferqe,'user_id') : []);
                    $FerqeName = ($ferqe ? implode(',',join::doNotAssign($link,array_column($ferqe,'Link'))) : false);
                    $rows = HL::GetPlayerNonKeyboard($FerqeUserId, 'NightSelect_Ferqe');
                    $inline_keyboard = new InlineKeyboard(...$rows);

                    $Msg = ($FerqeName  !== "" ? self::$Dt->LG->_('AskConvert',array("{0}" =>PHP_EOL.self::$Dt->LG->_('DiscussWith',array("{0}" =>$FerqeName)))) : self::$Dt->LG->_('AskConvert',array("{0}" =>'')) );

                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => $Msg,
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                        R::rpush($row['user_id'],'GamePl:SendNight');
                    }
                    break;

                case 'role_Qatel':

                    $ArcherID = HL::GetRoleUserId('role_Archer');
                    $NotIn = ($ArcherID ? [$row['user_id'],$ArcherID] : [$row['user_id']]);

                    $rows = HL::GetPlayerNonKeyboard($NotIn, 'NightSelect_Killer');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('AskKill'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                        R::rpush($row['user_id'],'GamePl:SendNight');
                    }
                    break;
                    case 'role_hilda':
                        if(!R::CheckExit('GamePl:KillerIsKillHildaInGame')) {
                            continue 2;
                        }
                    $ArcherID = HL::GetRoleUserId('role_Archer');
                    $NotIn = ($ArcherID ? [$row['user_id'],$ArcherID] : [$row['user_id']]);

                    $rows = HL::GetPlayerNonKeyboard($NotIn, 'NightSelect_Hilda');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('AskHilda'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                        R::rpush($row['user_id'],'GamePl:SendNight');
                    }
                    break;
                case 'role_Archer':
                    // از اونجایی که هر دو شب باس واس کماندار ارسال شه ما چک میکنیم به اون شب رسیده یا نه
                    if(R::Get('GamePl:ArcherSendFor') > R::Get('GamePl:Night_no')){
                        continue 2;
                    }

                    $QatelID = HL::GetRoleUserId('role_Qatel');
                    $NotIn = ($QatelID ? [$row['user_id'],$QatelID] : [$row['user_id']]);
                    $rows = HL::GetPlayerNonKeyboard($NotIn, 'NightSelect_Archer');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('AskArcher'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::GetSet(R::Get('GamePl:Night_no') + 2,'GamePl:ArcherSendFor');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                    }
                    break;
                case 'role_pishgo':

                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'NightSelect_Sear');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('howSeeIs'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                    }
                    break;
                case 'role_Knight':
                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'NightSelect_Knight');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('KnightAsk'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                        R::GetSet(R::Get('GamePl:Night_no') + 0,'GamePl:KnightSendFor');
                    }
                    break;
                case 'role_elahe':

                    $rows = HL::GetPlayerNonKeyboard([], 'NightSelect_Cupe');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('AskCupid1'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::GetSet(true,'GamePl:NotSend_role_elahe');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');

                    }
                    break;
                case 'role_Hamzad':

                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'NightSelect_Hamzad');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('hamzad_L'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::GetSet(true,'GamePl:NotSend_role_Hamzad');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');

                    }
                    break;
                case 'role_Fereshte':


                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'NightSelect_Angel');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('HowAngelIs'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');

                    }
                    break;
                    case 'role_Phoenix':
                        if(R::Get('GamePl:Night_no') !== 2) {
                            continue 2;
                        }
                       $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'NightSelect_Phoenix');
                       $inline_keyboard = new InlineKeyboard(...$rows);
                      $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('AskPhoenix'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                    }
                    break;
                case 'role_Huntsman':

                    $HuntsManCountG = R::Get('GamePl:HuntsmanT') ;
                    if($HuntsManCountG > 0) {
                        $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'NightSelect_Huntsman');
                        $inline_keyboard = new InlineKeyboard(...$rows);
                        $result = Request::sendMessage([
                            'chat_id' => $row['user_id'],
                            'text' => self::$Dt->LG->_('role_hunstmanAsk',array("{0}" =>$HuntsManCountG)),
                            'reply_markup' => $inline_keyboard,
                            'parse_mode' => 'HTML',
                        ]);
                        if ($result->isOk()) {
                            R::rpush($row['user_id'],'GamePl:SendNight');
                            R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');

                        }
                    }
                    break;
                case 'role_Vahshi':


                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'NightSelect_Vahshi');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('vahshi_L'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::GetSet(true,'GamePl:NotSend_role_Vahshi');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');

                    }
                    break;
                case 'role_shekar':


                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'NightSelect_Shekar');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('howToHoHome'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');

                    }
                    break;
                case 'role_ahmaq':


                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'NightSelect_Fool');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('howSeeIs'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                        R::rpush($row['user_id'],'GamePl:SendNight');
                    }
                    break;
                case 'role_Mouse':


                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'NightSelect_Mouse');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('role_MouseAsk'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                        R::rpush($row['user_id'],'GamePl:SendNight');
                    }
                    break;
                case 'role_ngativ':


                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'NightSelect_Negativ');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('Negativ_l'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                        R::rpush($row['user_id'],'GamePl:SendNight');
                    }
                    break;
                case 'role_faheshe':


                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'NightSelect_Natasha');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('howFahesheIs'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($row['user_id'],'GamePl:SendNight');
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                    }
                    break;
                case 'role_Watermelon':


                    $rows = HL::GetPlayerNonKeyboard([$row['user_id']], 'NightSelect_Watermelon');
                    $inline_keyboard = new InlineKeyboard(...$rows);
                    $result =  Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('WatermelonChose'),
                        'reply_markup' => $inline_keyboard,
                        'parse_mode' => 'HTML',
                    ]);
                    if($result->isOk()){
                        R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:MessageNightSend');
                        R::rpush($row['user_id'],'GamePl:SendNight');
                    }
                    break;
                default:
                    break;
            }


        }
        return true;
    }



    public static function VisitGraveDigger(){

    }

}