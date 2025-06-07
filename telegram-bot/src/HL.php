<?php

namespace phpcron\CronBot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;


class HL
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
    public static function _getGameState(){
        return R::Get('game_state') ?? "end";
    }
    public static function _getGameTimer(){
        return R::Get('timer') ?? 0;
    }

    public static function R($max){
        return mt_rand(0,$max);
    }
    public static function _getJoinKeyboard(){
        $join =  new InlineKeyboard(
            [
                ['text' => self::$Dt->LG->_('joinToGame'), 'url' => self::$Dt->JoinLink]
            ]

        );

        return $join;
    }

    public static function _getCountPlayer(){

        $result = self::$Dt->collection->join_user->findOne(['chat_id' => self::$Dt->chat_id]);

        if($result) {
            $array = iterator_to_array($result);
            return count($array['users']);
        }
        return 0;
    }
    public static function UpdateCoin($Coin,$user_id){
        self::$Dt->collection->Players->updateOne(array("user_id"=>(float) $user_id),  ['$set' => ['coin' => (float) $Coin]] );

        return true;
    }
    public static function FindePlayerRoleBuy($role,$user_id){
        $Data = self::GetRoleBuy();
        if(!$Data){
            return false;
        }
        $Find = false;
        foreach ($Data as $row){
            if($row['_id'] == $role){

                if(in_array($user_id,$row['users'])){
                    $Find = true;
                    break;
                }
            }
        }

        return $Find;

    }
    public static function GetRoleBuy(){
        if(R::CheckExit('GamePl:BuyRole')){
            $Data = json_decode(R::Get('GamePl:BuyRole'),true);
            return $Data;
        }
        $ops = [
            ['$match' => ['active' => true]],
            ['$group' => [
                '_id' => '$role'
                ,'count' => ['$sum' => 1]
                ,"users" => ['$addToSet' => '$user_id']
            ]],

        ];
        $result = self::$Dt->collection->buy_role->aggregate($ops);
        if ($result) {
            $array = iterator_to_array($result);
            R::GetSet($array,'GamePl:BuyRole','json');
            return json_decode(json_encode($array),true);
        }

        return false;
    }
    public static function _getCountPlayers(){

        $result = self::$Dt->collection->games_players->count(['group_id'=> self::$Dt->chat_id,'game_id'=> self::$Dt->game_id]);
        return $result;
    }
    public static function GroupClosedThGame($type = false){
        if($type == 'join'){
            $GameMode = R::Get('GamePl:gameModePlayer');
            if($GameMode == 'coin') {
                $players = self::_getPlayers();
                foreach ($players as $row) {
                    $Player = self::FindUserId($row['user_id']);
                    if ($row['user_state'] == 1) {
                        self::UpdateCoin(((int)$Player['coin'] + 5000), $row['user_id']);
                        Request::sendMessage([
                            'chat_id' => $row['user_id'],
                            'text' => self::$Dt->L->_('BackSendCoinEndGame'),
                            'disable_web_page_preview' => 'true',
                            'parse_mode' => 'HTML'
                        ]);
                    }
                }
            }
        }


        R::GetSet(true,'GamePl:GameIsEnd');
        R::Del('game_state');
        self::$Dt->collection->games_players->deleteMany(['group_id' => self::$Dt->chat_id,'game_id'=> self::$Dt->game_id]);
        self::$Dt->collection->games->deleteOne(['group_id' => self::$Dt->chat_id,'game_id'=> self::$Dt->game_id]);
        self::$Dt->collection->join_user->deleteOne(['chat_id' => self::$Dt->chat_id]);
    }


    public static function _getOnPlayers(){
        $result = self::$Dt->collection->games_players->find(
            ['group_id'=> self::$Dt->chat_id,'game_id'=> self::$Dt->game_id,'user_state' => 1,'user_status' => 'on']);
        $array = iterator_to_array($result);
        return $array;
    }

    public static function _getPlayers(){
        $result = self::$Dt->collection->games_players->find(
            ['group_id'=> self::$Dt->chat_id,'game_id'=> self::$Dt->game_id],
            ['sort' => ['user_state'=> 1,'dead_time' => 1],
            ]);
        $array = iterator_to_array($result);
        return $array;
    }


    public static function VisitHome($visit,$Visitor){

    }
    public static function _getName($fullname,$id){
        return self::ConvertName($id,$fullname);
    }
    public static function _getPlayerList($markDown = false){
        $d = self::_getPlayers();
        $re = [];

        foreach ($d as $row){
            $name  = ($row['user_state'] == 0 || $row['user_state'] == 2 ) ? "*".$row['fullname_game']."*: " : self::ConvertName($row['user_id'],$row['fullname_game'],true)." :";
            $UserRole = ($row['user_state'] == 0 || $row['user_state'] == 2 and R::Get('expose_role_after_dead') == "onr") ?  "*".self::$Dt->LG->_($row['user_role']."_n")."*" ."-" : '';
            $state = ($row['user_state'] == 0 ? self::$Dt->LG->_('is_dead') : ($row['user_state'] == 2 ?  self::$Dt->LG->_('is_smited') :  self::$Dt->LG->_('is_on')));
            $love  = ($row['user_state'] !== 1 ? R::CheckExit('GamePl:love:'.$row['user_id']) ? "- ❤️" : "" : "");
            $Vip   = "";

            array_push($re,$name.$UserRole.$state.$love);
        }
        $list = implode(PHP_EOL,$re);

        $AllPlayer = self::_getCountPlayers();
        $PlayerOn = self::_getCountPlayer();

        $PlayerList = self::$Dt->LG->_('playerlistOn',array("{0}" => "{$PlayerOn}/{$AllPlayer}", "{1}" => $list));
        return $PlayerList;
    }

    public static function ConvertName($user_id,$name,$markDown = false){

        if(is_array($user_id) || is_array($name)){
            return "[Error](tg://user?id=556635252)";
        }
        if($markDown){
            $nameClear = str_replace(['_','*','`','['],' ',$name);
            return "[{$nameClear}](tg://user?id={$user_id})";
        }
        return '<a href="tg://user?id='.$user_id.'">'.$name.'</a>';

    }

    public static function SendPlayerList(){
        $list = self::_getPlayerList(true);

        $re = Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => $list,
            'parse_mode'=> 'Markdown'
        ]);
        if($re->isOk()) {
            R::GetSet($re->getResult()->getMessageId(), 'Player_ListMessage_ID');
        }
    }

    public static function GetGameStatusLang(){
        $status = R::Get('game_state');
        switch ($status){
            case 'night':
                if(R::CheckExit('GamePl:KhabgozarOk')){
                    return self::$Dt->LG->_('SandmanNight');
                }
                return self::$Dt->LG->_('MassgeFortypeSummery_night', array("{0}" => "<strong>".R::Get('night_timer')."</strong>"));
                break;
            case 'day':
                $mSG = (R::CheckExit('GamePl:Kill') == false && R::CheckExit('GamePl:KhabgozarOk') == false ? self::$Dt->LG->_('NoAttakInDay') : false);
                if($mSG){self::SaveMessage($mSG);}
                $Day_no = R::Get('GamePl:Day_no') ??  1;
                return self::$Dt->LG->_('MassgeFortypeSummery_day', array("{0}" =>  "<strong>".R::Get('day_timer')."</strong>")).PHP_EOL.self::$Dt->LG->_('Day_nos',  array("{0}" => "<strong>{$Day_no}</strong>"));
                break;
            case 'vote':
                if(R::CheckExit('GamePl:role_Solh:GroupInSolh')){
                    return false;
                }
                if(R::CheckExit('GamePl:SharlatanINTabar')){
                    return false;
                }

                if(R::CheckExit('GamePl:role_Ruler:RulerOk')){
                    return self::$Dt->LG->_('RulerMessageVoteNow',array("{0}" =>  SE::_s('RulerSecendVote')));
                }

                return (R::Get('secret_vote') == "offr" ? self::$Dt->LG->_('MassgeFortypeSummery_vote',array("{0}" =>  "<strong>".R::Get('vote_timer')."</strong>")) :  self::$Dt->LG->_('MassgeFortypeSummery_Secretvote', array("{0}" => "<strong>".R::Get('secret_timer')."</strong>")));
                break;
        }
    }

    public static function ChangeStartGameTime(){
        R::GetSet(time(),'GamePl:StartedTime');
    }
    public static function ChangeGameStatus(string $to){

        R::GetSet($to,'game_state');
        R::GetSet(true,'GamePl:SetTimer');

        switch ($to){
            case 'night':
                $timer = (int) (R::Get('night_timer') ?? 90);

                if(R::CheckExit('GamePl:KhabgozarOk')){
                    $timer = 0;
                }
                break;
            case 'day':
                $timer = (int) (R::Get('day_timer') ?? 90);
                break;
            case 'vote':
                $timer = (R::Get('secret_vote') == "offr" ? (int) R::Get('vote_timer') ?? 90 :  (int) R::Get('secret_timer') ?? 90);

                if(R::CheckExit('GamePl:role_Ruler:RulerOk')){
                    $timer = SE::_s('RulerSecendVote');
                }

                if(R::CheckExit('GamePl:role_Solh:GroupInSolh')){
                    $timer = 0;
                } 
                if(R::CheckExit('GamePl:SharlatanINTabar')){
                    $timer = 0;
                }
                break;
        }

        $Times = (time() + $timer);
        R::GetSet( $Times,'timer');

        self::$Dt->collection->games->updateOne(
            ['group_id' => self::$Dt->chat_id,'game_id'=> self::$Dt->game_id ],
            ['$set' => ['game_status' => $to, 'timer']]
        );
        R::Del('GamePl:SetTimer');
        return false;

    }
    public static function SaveMessage($msg){
        R::rpush($msg,'GamePl:group_message');
    }
    public static function GetLenMessage(array $Messages){
        $len = 0;
        foreach ($Messages as $message) {
            $len = $len + strlen($message);
        }

        return $len;

    }

    public static function GetSliceMessage($Messages , $AllowLen = 300){
        // تغییر ترتیب از اخر به اول

        $implo = []; // آرایه داده های ترتیب داده شده
        $SingleSend = []; // آرایه داده های تکی
        $implo_len = 0; // تعداد کاراکتر های ارایه ترتیبی
        foreach ( $Messages as $val) {
            if($implo_len <= $AllowLen) {
                array_push($implo, $val);
                $implo_len = $implo_len + strlen($val);
            }else{
                array_push($SingleSend,$val);
            }

        }

        return ['single'=> $SingleSend ,'Implode'=> $implo];
    }
    public static function SendGroupMessage($sendList = false,$Allowlen = 300){
        $Messages = R::LRange(0,-1,'GamePl:group_message');
        $reversed = array_reverse($Messages);
        if($reversed){
            $Get = self::GetSliceMessage($reversed,$Allowlen);

            $SingleSend = $Get['single'];
            $implo = $Get['Implode'];

            if(count($SingleSend) > 0){
                $reversedSingleSend = array_reverse($SingleSend);
                foreach ($reversedSingleSend as $val){
                    Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => $val,
                        'parse_mode' =>'HTML'
                    ]);
                }
            }



            if(count($implo) > 0){
                $reversedimplo = array_reverse($implo);
                $re = implode(PHP_EOL.PHP_EOL,$reversedimplo);
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => $re,
                    'parse_mode' => 'HTML'
                ]);
            }

            R::Del('GamePl:group_message');
        }

        if($sendList == true && R::CheckExit('GamePl:Kill') == true) {
            $list = self::_getPlayerList(true);
            $re = Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => $list,
                'parse_mode' => 'Markdown'
            ]);
            if ($re->isOk()) {
                R::GetSet($re->getResult()->getMessageId(), 'Player_ListMessage_ID');
            }
            (R::CheckExit('GamePl:Kill') == true && R::CheckExit('GamePl:HunterKill') == false && R::CheckExit('GamePl:WolfCubeDead') == false && R::CheckExit('GamePl:RoyceDead') == false ? R::Del('GamePl:Kill') : "");
        }
     }


    public static function PlayerByTeam($Player = false){
        if($Player == false){
            $Player = self::_getOnPlayers();
        }

        $WolfTeam = [];
        $FerqeTeam = [];
        $Fermason = [];
        $vampire = [];
        $Black = [];
        $Qatel = [];
        foreach ($Player as $row){
            switch ($row['user_role']){
                case 'role_forestQueen':
                    if(R::CheckExit('GamePl:role_forestQueen:AlphaDead') == false){
                        continue 2;
                    }
                    array_push($WolfTeam,['user_id'=> $row['user_id'],'Link'=> self::_getName($row['fullname_game'],$row['user_id']),'role'=> $row['user_role'] ]);
                    break;
                case 'role_BlackKnight':
                case 'role_BrideTheDead':
                    $Black[] = ['user_id' => $row['user_id'], 'Link' => self::_getName($row['fullname_game'], $row['user_id']), 'role' => $row['user_role']];
                    break;
                case 'role_WolfTolle':
                case 'role_WolfGorgine':
                case 'role_Wolfx':
                case 'role_WolfAlpha':
                    array_push($WolfTeam,['user_id'=> $row['user_id'],'Link'=> self::_getName($row['fullname_game'],$row['user_id']),'role'=> $row['user_role']  ]);
                    break;
                case 'role_Royce':
                case 'role_ferqe':
                    array_push($FerqeTeam,['user_id'=> $row['user_id'],'Link'=> self::_getName($row['fullname_game'],$row['user_id']),'role'=> $row['user_role']  ]);
                    break;
                case 'role_feramason':
                    array_push($Fermason,['user_id'=> $row['user_id'],'Link'=> self::_getName($row['fullname_game'],$row['user_id']),'role'=> $row['user_role']  ]);
                    break;
                case 'role_Bloodthirsty':
                    if(R::CheckExit('GamePl:Bloodthirsty')){
                        array_push($vampire,['user_id'=> $row['user_id'],'Link'=> self::_getName($row['fullname_game'],$row['user_id']),'role'=> $row['user_role']  ]);
                    }
                    break;
                case 'role_Vampire':
                    array_push($vampire,['user_id'=> $row['user_id'],'Link'=> self::_getName($row['fullname_game'],$row['user_id']) ,'role'=> $row['user_role'] ]);
                break;
                case 'role_kentvampire':
                    if(!R::CheckExit('GamePl:KentVampireConvert')){
                        continue 2;
                    }
                    array_push($vampire,['user_id'=> $row['user_id'],'Link'=> self::_getName($row['fullname_game'],$row['user_id']) ,'role'=> $row['user_role'] ]);
                    break;
                case 'role_Qatel':
                case 'role_Archer':
                 case 'role_Sharlatan':
                    array_push($Qatel,['user_id'=> $row['user_id'],'Link'=> self::_getName($row['fullname_game'],$row['user_id']) ,'role'=> $row['user_role'] ]);
                    break;
            }
        }

        return ['wolf'=> $WolfTeam,'black' => $Black , 'ferqe'=> $FerqeTeam,'Fermason'=> $Fermason,'vampire' => $vampire,'Qatel'=>$Qatel];

    }

    public static function GetPlayerNonKeyboard($d,$callBack,$in_list = false){

        $player = self::_getOnPlayers();
        $re = [];
        foreach($player as  $row){
            $UserRole = $row['user_role'];
            if($UserRole == "role_BrideTheDead"){
                continue;
            }
            
            if($in_list == false) {
                if (!in_array($row['user_id'], $d)) {
                    $re[] = [
                        ['text' => $row['fullname'], 'callback_data' => "{$callBack}/" . self::$Dt->chat_id . "/{$row['user_id']}"]
                    ];
                }
            }else{
                if (in_array($row['user_id'], $d)) {
                    $re[] = [
                        ['text' => $row['fullname'], 'callback_data' => "{$callBack}/" . self::$Dt->chat_id . "/{$row['user_id']}"]
                    ];
                }
            }
        }

        switch ($callBack){
            case 'VoteSelect':
            case 'NightSelect_Hamzad':
            case 'NightSelect_Vahshi':
            case 'NightSelect_Cupe':
                break;
            case 'NightSelect_Firefighter':
                if(R::Get('GamePl:Night_no') > 0 && R::CheckExit('GamePl:FirefighterList')) {
                    $re[] = [
                        ['text' => self::$Dt->LG->_('ButtenFireFighter'), 'callback_data' => "RoleFireFighterFight" . "/" . self::$Dt->chat_id]
                    ];
                }
                break;
            default:
                $re[] = [
                    ['text' => "skip", 'callback_data' => "skip" . "/" . self::$Dt->chat_id]
                ];
                break;
        }


        return $re;
    }


    public static function ChangeLuciferTeam($to,$user_id){
        R::GetSet($to,"GamePl:user:{$user_id}:team");

        $result = self::$Dt->collection->games_players->updateOne(
            ['user_id' => (float) $user_id,'game_id'=> self::$Dt->game_id,'group_id'=> self::$Dt->chat_id],
            ['$set' => ['team' => $to ,'change_time' => time() ]]
        );
    }


    public static function CheckKalantar(){
        $KillFor = R::Get('GamePl:KillFor');
        switch ($KillFor){
            case 'vote':
                R::GetSet(true,'GamePl:HunterKillVote');
            case 'kill':
            case 'shot':
                if(R::CheckExit('GamePl:kalantar_userid')){
                    $selected = R::Get('GamePl:Selected:'.R::Get('GamePl:kalantar_userid'));
                    $KalantarName = R::Get('GamePl:kalantar_fullname');
                    $Detial = self::_getPlayer($selected);
                    $U_name = self::ConvertName($Detial['user_id'],$Detial['fullname_game']);
                    $MessageKey = ($KillFor == "kill" || $KillFor == "shot"  ? 'HunterKilledFinalShot' : 'HunterKilledFinalLynched');

                    $GroupMessage = self::$Dt->LG->_($MessageKey,array("{0}" => $KalantarName, "{1}" => $U_name, "{2}" => self::$Dt->LG->_('user_role', array("{0}"=> self::$Dt->LG->_($Detial['user_role']."_n")))));
                    self::SaveMessage($GroupMessage);

                    // در نقش کلانتر بعد از اعدام شدن یک گرگ یا یک قاتل را بزنید
                    if($Detial['user_role'] == "role_WolfTolle" || $Detial['user_role'] == "role_WolfGorgine" || $Detial['user_role'] == "role_Wolfx"  || $Detial['user_role'] == "role_WolfAlpha" || $Detial['user_role'] == "role_WhiteWolf" || $Detial['user_role'] == "role_Qatel"){
                        self::SavePlayerAchivment(R::Get('GamePl:kalantar_userid'),'Hey_Man_Nice_Shot');
                    }

                    //» کلانتر باشیو تیر قبل مرگت رو برنی به ریش سفید و روستایی ساده بمیری
                    if($Detial['user_role'] == "role_rishSefid"){
                        self::ConvertPlayer(R::Get('GamePl:kalantar_userid'),'role_rosta');
                        self::SavePlayerAchivment(R::Get('GamePl:kalantar_userid'),'Demoted_by_the_Death');
                    }

                    self::UserDead($Detial,'shot_kalantar');
                    HL::SaveGameActivity($Detial,'shot',['user_id'=> (int) R::Get('GamePl:kalantar_userid') ,'fullname' => 's' ]);
                    R::Del('GamePl:HunterKill');
                    R::DelKey('GamePl:kalantar_*');
                    return true;
                }
                if(R::CheckExit('GamePl:kalantar_Skip')){
                    $KalantarName = R::Get('GamePl:kalantar_fullname');
                    $MessageKey = ($KillFor == "kill" || $KillFor == "shot" ? 'HunterSkipChoiceShot' : 'HunterSkipChoiceLynched');
                    $GroupMessage = self::$Dt->LG->_($MessageKey,array("{0}"=> $KalantarName));
                    self::SaveMessage($GroupMessage);
                    R::Del('GamePl:HunterKill');
                    R::DelKey('GamePl:kalantar_*');
                    return true;
                }
                $Kalantar = self::_getPlayerByRole('role_kalantar',true);
                $KalantarName = self::ConvertName($Kalantar['user_id'],$Kalantar['fullname_game']);
                $MessageKey = ($KillFor == "kill" || $KillFor == "shot" ? 'HunterNoChoiceShot' : 'HunterNoChoiceLynched');
                $GroupMessage = self::$Dt->LG->_($MessageKey,array("{0}"=>$KalantarName));
                self::SaveMessage($GroupMessage);
                R::Del('GamePl:HunterKill');
                R::DelKey('GamePl:kalantar_*');
                return true;
                break;
        }
    }

    public static function CheckDontSelectRole(){
        //اگر نقشای الهه ، همزاد،وحشی  انتخابی نکردم اتوماتیک واس انتخاب میکنیم واسشون
        $Key = R::LRange(0,-1,'GamePl:MessageNightSend');
        if($Key) {
            foreach ($Key as $key) {
                $Explod = explode('_',$key);
                $user_id = $Explod['1'];
                $Message_id = $Explod['0'];
                $Detial = self::_getPlayer($user_id);
                switch ($Detial['user_role']) {
                    case 'role_Hamzad':
                        $RandomUser = self::GetUserRandom([$user_id]);
                        R::GetSet($RandomUser['user_id'], 'GamePl:Hamzad');
                        Request::editMessageText([
                            'chat_id' => $user_id,
                            'text' => self::$Dt->LG->_('select_not', array("{0}" => $RandomUser['fullname_game'])),
                            'message_id' => $Message_id,
                            'parse_mode' => 'HTML',
                            'reply_markup' => new InlineKeyboard([]),
                        ]);
                        R::LRem($key,1,'GamePl:MessageNightSend');
                        break;
                    case 'role_lucifer':
                        self::ChangeLuciferTeam("rosta", $user_id);
                        Request::editMessageText([
                            'chat_id' => $user_id,
                            'text' => self::$Dt->LG->_('select_not',array("{0}" => self::$Dt->LG->_('RostaTeam'))),
                            'message_id' => $Message_id,
                            'parse_mode' => 'HTML',
                            'reply_markup' => new InlineKeyboard([]),
                        ]);
                        R::LRem($key,1,'GamePl:MessageNightSend');
                        break;
                    case 'role_Vahshi':
                        $RandomUser = self::GetUserRandom([$user_id]);
                        $Name = self::ConvertName($RandomUser['user_id'], $RandomUser['fullname_game']);
                        R::GetSet($RandomUser['user_id'], 'GamePl:Olgo');
                        R::GetSet($Name, 'GamePl:OlgoName');
                        Request::editMessageText([
                            'chat_id' => $user_id,
                            'text' => self::$Dt->LG->_('select_not', array("{0}" => $RandomUser['fullname_game'])),
                            'message_id' => $Message_id,
                            'parse_mode' => 'HTML',
                            'reply_markup' => new InlineKeyboard([]),
                        ]);
                        R::LRem($key,1,'GamePl:MessageNightSend');
                        break;
                    case 'role_elahe':
                        $RandomUser = self::GetUserRandom([]);
                        $Name = self::ConvertName($RandomUser['user_id'], $RandomUser['fullname_game']);

                        $RandomUser1 = self::GetUserRandom([$RandomUser['user_id']]);
                        $Name1 = self::ConvertName($RandomUser1['user_id'], $RandomUser1['fullname_game']);

                        R::GetSet($RandomUser1['user_id'], 'GamePl:love:' . $RandomUser['user_id']);
                        R::GetSet($Name1, 'GamePl:name:love:' . $RandomUser['user_id']);


                        R::GetSet($RandomUser['user_id'], 'GamePl:love:' . $RandomUser1['user_id']);
                        R::GetSet($Name, 'GamePl:name:love:' . $RandomUser1['user_id']);

                        Request::editMessageText([
                            'chat_id' => $user_id,
                            'text' => self::$Dt->LG->_('endTime'),
                            'message_id' => $Message_id,
                            'parse_mode' => 'HTML',
                            'reply_markup' => new InlineKeyboard([]),
                        ]);
                        R::LRem($key,1,'GamePl:MessageNightSend');
                        break;
                    default:
                        continue 2;
                        break;
                }
            }
        }
    }
    public static function CheckTimer(){

        $timer = self::_getGameTimer();
        $LeftTime = $timer - time();

        if($LeftTime <= 0){
            $game_state = R::Get('game_state');
            if(R::CheckExit('GamePl:StopBlack')){
                self::CheckBlack();
            }
            if(R::CheckExit('GamePl:HunterKill')){
                self::CheckKalantar();
            }
            if(R::CheckExit('GamePl:SendWolfCubeDead')){
                R::Del('GamePl:WolfCubeDead');
                R::Del('GamePl:SendWolfCubeDead');

            }

            switch ($game_state){
                case 'night':

                    if(R::CheckExit('GamePl:CheckNight')){
                        return false;
                    }

                    NG::CheckNight();
                    if(R::CheckExit('GamePl:HunterKill') || R::CheckExit('GamePl:SendWolfCubeDead')   || R::CheckExit('GamePl:StopBlack') || R::CheckExit('GamePl:RoyceSelectd2')){
                        return false;
                    }
                    self::ChangeGameStatus('day');
                    R::Del('GamePl:SendVote');
                    if(R::CheckExit('GamePl:role_Solh:GroupInSolh')){
                        R::Del('GamePl:role_Solh:GroupInSolh');
                    }  
                    if(R::CheckExit('GamePl:SharlatanINTabar')){
                        R::Del('GamePl:SharlatanINTabar');
                        R::GetSet(0,'GamePl:SharlatanTabar');
                    }
                    if(R::Get('GamePl:Day_no') == 1){
                        // همزاد،الهه و .. اگر انتخبا نکردن
                        self::CheckDontSelectRole();
                    }
                    if(R::CheckExit('GamePl:role_Ruler:RulerOk')){
                        R::Del('GamePl:role_Ruler:RulerOk');
                    }
                    R::Del('GamePl:SendNightAll');
                    R::Del('GamePl:CheckNight');
                    R::Del('playerDeadName');
                    break;
                case 'day':

                    DY::CheckDay();
                    if(R::CheckExit('GamePl:HunterKill')){
                        return false;
                    }


                    // اگه خوابگذار خواب زده بود بازش میکنیم
                    if(R::CheckExit('GamePl:KhabgozarOk')){
                        if(R::Get('GamePl:KhabgozarOk') == R::Get('GamePl:Night_no')) {
                            R::Del('GamePl:KhabgozarOk');
                        }
                    }
                    self::ChangeGameStatus('vote');
                    R::Del('GamePl:CheckDay');
                    R::Del('GamePl:SendNight');

                    $Day_no = R::Get('GamePl:Day_no') ?? 1;
                    R::GetSet( ($Day_no + 1) ,'GamePl:Day_no');


                    break;
                case 'vote':

                    if(R::CheckExit('GamePl:Update_vote') == false){
                        VT::CheckVoteMessage();
                    }

                    VT::CheckVoteMessage();
                    VT::CheckVote();
                    if(R::CheckExit('GamePl:StopBlack')){
                        return false;
                    }
                    if(R::CheckExit('GamePl:HunterKill')){
                        return false;
                    }

                    if(R::CheckExit('GamePl:trouble')){
                        VT::TroubleVote();
                        R::GetSet(true,'GamePl:trouble:ok');
                        return false;
                    }


                    R::Del('GamePl:SendDayRole'); // باز کردن نقش روز
                    // خب اگه گرگا مست خورده باشن بهتره بازش کنیم الیته برای شب بعد
                    if(R::CheckExit('GamePl:MastEat')){
                        if(R::Get('GamePl:MastEat') == R::Get('GamePl:Night_no')){
                            R::Del('GamePl:MastEat');
                        }
                    }


                    // خب اگه شارلاتان طوفان زده بود اینجا بازش میکنیم برای فرداشب
                    if(R::CheckExit('GamePl:SharlatanTofan')){
                        if(R::Get('GamePl:SharlatanTofan') == R::Get('GamePl:Day_no')){
                            R::Del('GamePl:SharlatanTofan');
                        }
                    }

                    // خب اگه آهنگ آهن زده بود اینجا بازش میکنیم برای فرداشب
                    if(R::CheckExit('GamePl:AhangarOk')){
                        if(R::Get('GamePl:AhangarOk') == R::Get('GamePl:Night_no')){
                            R::Del('GamePl:AhangarOk');
                        }
                    }

                    if(R::CheckExit('GamePl:HunterKillVote')){
                        R::Del('GamePl:HunterKillVote');
                    }

                    self::BittanCheck();

                    self::ChangeGameStatus('night');

                    R::GetSet(R::Get('GamePl:Night_no') + 1,'GamePl:Night_no');


                    R::DelKey('GamePl:Selected:Vote:*'); // پاک کردن انتخاب ها

                    R::DelKey('GamePl:HoneyUser:*');
                    R::Del('GamePl:CheckVote');
                    R::Del('GamePl:CheckVoteSend');
                    break;
            }
            self::EditMarkupKeyboard();

            if(R::Get('game_state') == "night") {
                if(!R::CheckExit('GamePl:role_Solh:GroupInSolh') && !R::CheckExit('GamePl:role_Ruler:RulerOk') && !R::CheckExit('GamePl:SharlatanINTabar')) {
                    self::DeleteDontVote(); // حذف افرادی که 2 بار رای ندادن
                }
            }

            R::DelKey('GamePl:Selected:*'); // پاک کردن انتخاب ها

            if(self::CheckEndGame()){
                return false;
            }

            $msg = self::GetGameStatusLang();
            self::SaveMessage($msg);
            self::SendGroupMessage(true);
        }
    }
    public static function CheckBlack(){

    }
    public static function UnlockForTeam($setTime = false){
        $P_Team = HL::PlayerByTeam();
        $Wolf =  (count($P_Team['wolf']) > 0 ? $P_Team['wolf'] : false);
        if($Wolf){
            $wolfUserId = ($Wolf ? array_column($Wolf,'user_id') : false);
            if(count($P_Team['wolf']) > 1) {
                R::DelKey('GamePl:Selected:Wolf:*');
            }
            foreach ($wolfUserId as $User_id){
                R::LRem($User_id,1,'GamePl:SendNight');
                R::Del('GamePl:Selected:'.$User_id.":user");
            }
            return true;
        }

        if($setTime){
            R::GetSet((time() - 5),'timer');
            return true;
        }
        return false;
    }

    public static function BittanCheck(){

        if(R::CheckExit('GamePl:EnchanterBittanPlayer')){
            $User_id = R::Get('GamePl:EnchanterBittanPlayer');
            $Detial = self::_getPlayer($User_id);
            if($Detial) {
                if ($Detial['user_state'] !== 1) {
                    R::Del('GamePl:EnchanterBittanPlayer');
                } else {
                    $UserMessage = self::$Dt->LG->_('BittenTurned');
                    self::SendMessage($UserMessage, $User_id);
                    self::ConvertPlayer($User_id, 'role_WolfGorgine');
                    self::CheckPlayerEnchanter($User_id);
                    R::Del('GamePl:EnchanterBittanPlayer');
                }
            }
        }

        if(R::CheckExit('GamePl:BittanPlayer')){
            $User_id = R::Get('GamePl:BittanPlayer');
            $Detial = self::_getPlayer($User_id);
            if($Detial) {
                if ($Detial['user_state'] !== 1) {
                    R::Del('GamePl:BittanPlayer');
                } else {
                    $UserMessage = self::$Dt->LG->_('BittenTurned');
                    self::SendMessage($UserMessage, $User_id);
                    self::ConvertPlayer($User_id, 'role_WolfGorgine');
                    R::Del('GamePl:BittanPlayer');
                }
            }
        }

        if(R::CheckExit('GamePl:MidnightBittanPlayer')){
            $User_id = R::Get('GamePl:MidnightBittanPlayer');
            $Detial = self::_getPlayer($User_id);
            if($Detial) {
                if ($Detial['user_state'] !== 1) {
                    R::Del('GamePl:MidnightBittanPlayer');
                } else {
                    self::ConvertPlayer($User_id, 'role_WolfGorgine');
                    R::Del('GamePl:MidnightBittanPlayer');
                }
            }
        }


        if(R::CheckExit('GamePl:VampireBitten')){
            $User_id = R::Get('GamePl:VampireBitten');
            $Detial = self::_getPlayer($User_id);
            if($Detial) {
                if ($Detial['user_state'] !== 1) {
                    R::Del('GamePl:VampireBitten');
                } else {
                    $UserMessage = self::$Dt->LG->_('BittenTurnedVampire');
                    self::SendMessage($UserMessage, $User_id);
                    self::ConvertPlayer($User_id, 'role_Vampire');
                    R::Del('GamePl:VampireBitten');
                }
            }
        }


        if(R::CheckExit('GamePl:OrlokBittanPlayer')){
            $User_id = R::Get('GamePl:OrlokBittanPlayer');
            $Detial = self::_getPlayer($User_id);
            if($Detial) {
                if ($Detial['user_state'] !== 1) {
                    R::Del('GamePl:OrlokBittanPlayer');
                } else {
                    self::ConvertPlayer($User_id, 'role_Vampire');
                    R::Del('GamePl:OrlokBittanPlayer');
                }
            }
        }

    }


    public static function DeleteDontVote(){

        $Key = R::keys('GamePl:DontVote:*');
        foreach ($Key as $key) {
            $Ex = explode(':', $key);
            $user_id = $Ex['3'];
            $keys = "{$Ex['1']}:{$Ex['2']}:{$Ex['3']}";
            $counter = R::Get($keys);
            if($counter > 1){
                if($user_id) {
                    R::GetSet((int) R::Get('GamePl:AfkedPlayer') + 1, 'GamePl:AfkedPlayer');

                    R::GetSet((R::CheckExit('AfkedPlayer:' . $user_id) ? (R::Get('AfkedPlayer:' . $user_id) + 1) : 1), 'AfkedPlayer:' . $user_id);
                    if (R::CheckExit('AfkedPlayer:' . $user_id) == false) {
                        R::Ex(86400, 'AfkedPlayer:' . $user_id);
                    }

                    $data = self::_getPlayer($user_id);
                    if($data) {
                        $name = self::ConvertName($user_id, $data['fullname_game']);
                        $Msg = self::$Dt->LG->_('afkedPlayerMessage',array("{0}" => $name, "{1}" =>  self::$Dt->LG->_($data['user_role'] . "_n"), "{2}" => self::$Dt->LG->_('AfkedTotal', array("{0}" => $name,"{1}"=> R::Get('AfkedPlayer:' . $user_id)))));
                        self::UserDead($data, 'afked');
                        Request::sendMessage([
                            'chat_id' => self::$Dt->chat_id,
                            'text' => $Msg,
                            'parse_mode' => 'HTML',
                        ]);
                    }
                    R::Del($keys);
                }
            }
        }
    }
    public static function _getPlayer($user_id){
        $result = self::$Dt->collection->games_players->findOne(['group_id'=>  self::$Dt->chat_id,'game_id'=> self::$Dt->game_id,'user_id' => (float) $user_id]);
        if($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }
    public static function EditMarkupKeyboard(){
        $Key = R::LRange(0,-1,'GamePl:MessageNightSend');
        if($Key) {
            foreach ($Key as $key) {
                $Ex = explode('_', $key);
                $user_id = $Ex['1'];
                if (R::Get('game_state') == "night") {
                    $CountDontVote = (R::CheckExit('GamePl:DontVote:' . $user_id) ? 2 : 1);
                    R::GetSet($CountDontVote, 'GamePl:DontVote:' . $user_id);
                }
                Request::editMessageText([
                    'chat_id' => $user_id,
                    'text' => self::$Dt->LG->_('endTime'),
                    'message_id' => $Ex['0'],
                    'parse_mode' => 'HTML',
                    'reply_markup' => new InlineKeyboard([]),
                ]);
            }
            R::Del('GamePl:MessageNightSend');
        }

        $Key = R::keys('GamePl:MessageNightSendDodgeVote:*');
        foreach ($Key as $key){
            $Ex = explode(':',$key);
            $user_id = $Ex['3'];
            $keys = "{$Ex['1']}:{$Ex['2']}:{$Ex['3']}";
            $Message_id = R::Get($keys);
            Request::editMessageText([
                'chat_id' => $user_id,
                'text' => self::$Dt->LG->_('endTime'),
                'message_id' => $Message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
            R::Del($keys);
        }


        // ویرایش markUp
        $Key = R::LRange(0,-1,'GamePl:EditMarkup');
        if($Key) {
            foreach ($Key as $key) {
                $Ex = explode('_', $key);
                $user_id = $Ex['1'];
                $Message_id = $Ex['0'];
                Request::editMessageReplyMarkup([
                    'chat_id' => $user_id,
                    'message_id' => $Message_id,
                    'reply_markup' => new InlineKeyboard([]),
                ]);
            }
            R::Del('GamePl:EditMarkup');
        }


    }


    public static function GetLove(){
        if(R::CheckExit('GamePl:love')){
            return R::Get('GamePl:love');
        }

        return false;
    }

    public static function CountRole($Role){
        $result = self::$Dt->collection->games_players->count(['game_id' => self::$Dt->game_id,'group_id' => self::$Dt->chat_id,'user_state' => 1 ,'user_status' => 'on','user_role' => $Role]);
        return $result;
    }


    public static function ConvertHamzad($Detial,$U_name){
        $Hamzad = HL::_getPlayerByRole('role_Hamzad');
        if($Hamzad == false){
            R::Del('GamePl:Hamzad');
            return false;
        }

        if($Hamzad['user_state'] !== 1){
            return false;
        }
        $RoleUser = self::$Dt->LG->_($Detial['user_role']."_n");
        $HamzadName = self::ConvertName($Hamzad['user_id'],$Hamzad['fullname_game']);


        if(R::CheckExit('GamePl:BookIn:'.$Detial['user_id'])){
            if(!R::CheckExit('GamePl:BookIn:'.$Hamzad['user_id'])){
                R::Del('GamePl:BookIn:'.$Detial['user_id']);
                R::GetSet(true,'GamePl:BookIn:'.$Hamzad['user_id']);
            }else{
                self::RandomBookChange($Detial['user_id']);
            }
        }
        switch ($Detial['user_role']){
            case 'role_Vahshi':
                // اول بهش میگیم که همزادش مرده
                $HamzadMessage = self::$Dt->LG->_('HamzadTabdilshode',array("{0}" => $U_name, "{1}" => $RoleUser));
                self::SendMessage($HamzadMessage,$Hamzad['user_id']);

                // درباره نقشش بهش میگیم که چیکار میتونه بکنه
                self::SendMessage(self::$Dt->LG->_($Detial['user_role']),$Hamzad['user_id']);
                // بهش میگیم اولگو طرف کیه
                $OlgoUser = self::$Dt->LG->_('NewWCRoleModel',array("{0}"=> R::Get('GamePl:OlgoName')));
                self::SendMessage($OlgoUser,$Hamzad['user_id']);
                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role']);
                return true;
                break;
            case 'role_Firefighter':
            case 'role_IceQueen':
                // اول بهش میگیم که همزادش مرده
                $HamzadMessage = self::$Dt->LG->_('HamzadTabdilshode',array("{0}" => $U_name, "{1}" => $RoleUser));
                self::SendMessage($HamzadMessage,$Hamzad['user_id']);
                // درباره نقشش بهش میگیم که چیکار میتونه بکنه
                self::SendMessage(self::$Dt->LG->_($Detial['user_role'],array("{0}" =>'')),$Hamzad['user_id']);

                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role']);
                break;
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_Wolfx':
            case 'role_WolfAlpha':
                $P_Team = HL::PlayerByTeam();
                $Wolf =  (count($P_Team['wolf']) > 0 ? $P_Team['wolf'] : false);
                $WolfName = ($Wolf ? implode(',',array_column($Wolf,'Link')) : false);

                // اگه گرگ تنها بود بهش میگیم تبدیل به گرگ شده،اگه تنها نبود و 2 تا گرگ بودن بهش هم تیمی هاشو میگیم
                $HamzadMessage = ($WolfName == "" ? self::$Dt->LG->_('DGTransToWolf', array("{0}" => $U_name)) : self::$Dt->LG->_('DGTransformToWolf',array("{0}" => $U_name,"{1}" => $WolfName)) );
                self::SendMessage($HamzadMessage,$Hamzad['user_id']);

                // درباره نقشش بهش میگیم که چیکار میتونه بکنه
                self::SendMessage(self::$Dt->LG->_($Detial['user_role']),$Hamzad['user_id']);

                // اگه گرگ دیگه ای بود بهشون اطلاع میدیم که طرف گرگ شده
                if($Wolf) {
                    $WolfTeamMessage = self::$Dt->LG->_('DGToWolf', array("{0}" => $HamzadName));
                    self::SendForWolfTeam($WolfTeamMessage);
                }

                if(R::Get('game_state') == "night"){
                    R::rpush($Hamzad['user_id'],'GamePl:SendNight');
                }
                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role']);
                return true;
                break;
            case 'role_feramason':
                $P_Team = HL::PlayerByTeam();
                $Fermason =  (count($P_Team['Fermason']) > 0 ? $P_Team['Fermason'] : false);
                $FermasonName = ($Fermason ? implode(',',array_column($Fermason,'Link')) : false);

                // اگه هم تیمی داشت بهش میگیم تو پیام
                $HamzadMessage = ($Fermason ? self::$Dt->LG->_('HamzadToFeramasonTeam',array("{0}" => $U_name, "{1}" => $FermasonName)) : self::$Dt->LG->_('HamzadToFeramason',array("{0}" => $U_name)));
                self::SendMessage($HamzadMessage,$Hamzad['user_id']);

                // درباره نقشش بهش میگیم که چیکار میتونه بکنه
                self::SendMessage(self::$Dt->LG->_($Detial['user_role'],array("{0}" =>'')),$Hamzad['user_id']);
                // اگه فراماسون دیگه ای بود بود بهش اطلاع میدیم که همزاد تبدیل شده به فراماسون

                if($Fermason){
                    $MasonMessage = self::$Dt->LG->_('HamzadMeFeramason',array("{0}" =>$HamzadName));
                    self::SendMessageMson($MasonMessage);
                }
                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role']);
                return true;
                break;
            case 'role_Royce':
            case 'role_ferqe':
                $P_Team = HL::PlayerByTeam();
                $ferqe =  (count($P_Team['ferqe']) > 0 ? $P_Team['ferqe'] : false);
                $ferqeName = ($ferqe ? implode(',',array_column($ferqe,'Link')) : false);
                // اگه هم تیمی داشت بهش میگیم تو پیام
                $HamzadMessage = ($ferqe ? self::$Dt->LG->_('HamzadToFerqeTeam',array("{0}" => $U_name, "{1}" => $ferqeName)) : self::$Dt->LG->_('HamzadToFerqe',array("{0}" => $U_name)));
                self::SendMessage($HamzadMessage,$Hamzad['user_id']);


                if($Detial['user_role'] == "role_ferqe") {
                    // درباره نقشش بهش میگیم که چیکار میتونه بکنه
                    self::SendMessage(self::$Dt->LG->_($Detial['user_role'], array("{0}" =>'')), $Hamzad['user_id']);
                }else{
                    // درباره نقشش بهش میگیم که چیکار میتونه بکنه
                    self::SendMessage(self::$Dt->LG->_($Detial['user_role']), $Hamzad['user_id']);
                }
                // اگه فرقه گرای دیگه ای بود بهش میگیم
                if($ferqe){
                    $CultMessage = self::$Dt->LG->_('HamzadMeFerqe',array("{0}" =>$HamzadName));
                    self::SendForCultTeam($CultMessage);
                }

                if(R::Get('game_state') == "night"){

                    R::rpush($Hamzad['user_id'],'GamePl:SendNight');
                }
                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role']);
                return true;
                break;
            case 'role_Nazer':
                // اول بهش میگیم که همزادش مرده
                $HamzadMessage = self::$Dt->LG->_('HamzadTabdilshode',array("{0}" => $U_name,"{1}" => $RoleUser));
                self::SendMessage($HamzadMessage,$Hamzad['user_id']);
                // اگه پیشگو داشتیم بهش میگیم که کی پیشگوئه
                $HamzadMessage = (R::CheckExit('GamePl:SearUser') == true ? self::$Dt->LG->_($Detial['user_role'],array("{0}" => self::$Dt->LG->_('pishgo_not',array("{0}" => R::CheckExit('GamePl:SearUser'))))) : self::$Dt->LG->_($Detial['user_role'],array("{0}" => self::$Dt->LG->_('Not_pishgo'))));
                self::SendMessage($HamzadMessage,$Hamzad['user_id']);
                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role']);
                return true;
                break;
            case 'role_Qatel':
                $QatelMsg = self::$Dt->LG->_('HamzadMeKiller',array("{0}" =>  $U_name));
                $Archer = self::_getPlayerByRole('role_Archer');
                if($Archer){
                    $ArcherName = self::ConvertName($Archer['user_id'],$Archer['fullname_game']);
                    $QatelMsg .= PHP_EOL.self::$Dt->LG->_('role_QatelIfArcher',array("{0}" =>  $ArcherName));
                    $MsgArcher = self::$Dt->LG->_('HamzadMeKillerArcher',array("{0}" => $U_name, "{1}" => $HamzadName));
                    self::SendMessage($MsgArcher,$Archer['user_id']);
                }
                self::SendMessage($QatelMsg,$Hamzad['user_id']);
                self::SendMessage(self::$Dt->LG->_($Detial['user_role'],array("{0}" =>  '')),$Hamzad['user_id']);
                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role']);
                $Hilda = self::_getPlayerByRole('role_hilda');
                 if($Hilda){
                         $HildaMessage = self::$Dt->LG->_('KillerKillHamzadHilda', $HamzadName);
                         self::SendMessage($HildaMessage, $Hilda['user_id']);
                 }
                break;
            case 'role_Archer':
                $ArcherMsg = self::$Dt->LG->_('HamzadMeArcher',array("{0}" => $U_name));
                $Killer = self::_getPlayerByRole('role_Qatel');
                if($Killer){
                    $MsgArcher = self::$Dt->LG->_('HamzadMeKillerArcher',array("{0}" =>  $U_name,"{1}" => $HamzadName));
                    self::SendMessage($MsgArcher,$Killer['user_id']);
                }
                self::SendMessage($ArcherMsg,$Hamzad['user_id']);
                self::SendMessage(self::$Dt->LG->_($Detial['user_role'],array("{0}" => ($Killer ? $HamzadName : self::$Dt->LG->_('DeadKiller')) )),$Hamzad['user_id']);
                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role']);

                break;
            case 'role_kalantar':
                $HunterMessage = self::$Dt->LG->_('HamzadMeHunter',array("{0}" => $U_name));
                if(R::CheckExit('GamePl:Bloodthirsty')) {
                    $Blood = self::_getPlayerByRole('role_Bloodthirsty');
                    if ($Blood) {
                        $BloodName = self::ConvertName($Blood['user_id'],$Blood['fullname']);
                        $HunterMessage .= self::$Dt->LG->_('HamzadMeHunterBooldIn',array("{0}" => $BloodName));

                        $MsgBlood = self::$Dt->LG->_('HamzadMeHunterBlood',array("{0}" => $U_name,"{1}"=> $HamzadName));
                        self::SendMessage($MsgBlood, $Blood['user_id']);
                    }
                }
                self::SendMessage($HunterMessage,$Hamzad['user_id']);
                self::SendMessage(self::$Dt->LG->_($Detial['user_role'],array("{0}" =>'')),$Hamzad['user_id']);
                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role'],true);
                break;
            case 'role_Bloodthirsty':
                $HunterMessage = self::$Dt->LG->_('HamzadMeBlood',array("{0}" =>$U_name));
                $kalantarName = "";
                if(R::CheckExit('GamePl:Bloodthirsty')) {
                    $kalantar = self::_getPlayerByRole('role_kalantar');
                    if ($kalantar) {
                        $kalantarName = self::ConvertName($kalantar['user_id'],$kalantar['fullname']);
                        $HunterMessage .= self::$Dt->LG->_('HamzaBloodInKalantar',array("{0}" =>$kalantarName));

                        $MsgKalanBlood = self::$Dt->LG->_('HamzadMeBloodHunterMasg', array("{0}" => $U_name,"{1}" => $HamzadName));
                        self::SendMessage($MsgKalanBlood, $kalantar['user_id']);
                    }
                }
                self::SendMessage($HunterMessage,$Hamzad['user_id']);
                self::SendMessage(self::$Dt->LG->_($Detial['user_role'],array("{0}" =>$kalantarName)),$Hamzad['user_id']);
                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role'],true);
                break;

            default:
                // اول بهش میگیم که همزادش مرده
                $HamzadMessage = self::$Dt->LG->_('HamzadTabdilshode',array("{0}" => $U_name, "{1}" => $RoleUser));
                self::SendMessage($HamzadMessage,$Hamzad['user_id']);
                // درباره نقشش بهش میگیم که چیکار میتونه بکنه
                self::SendMessage(self::$Dt->LG->_($Detial['user_role']),$Hamzad['user_id']);

                if(R::Get('game_state') == "night"){
                    R::rpush($Hamzad['user_id'],'GamePl:SendNight');
                }

                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role']);
                return true;
                break;
        }
    }


    public static function ConvertOlgo($Detial,$U_name){
        $Vahshi = HL::_getPlayerByRole('role_Vahshi',false);
        if($Vahshi == false){
            R::Del('GamePl:Olgo');
            R::Del('GamePl:OlgoName');
            return false;
        }

        if($Vahshi['user_state'] !== 1){
            return false;
        }
        $VahshiName = self::ConvertName($Vahshi['user_id'],$Vahshi['fullname_game']);

        $P_Team = HL::PlayerByTeam();
        $Wolf =  (count($P_Team['wolf']) > 0 ? $P_Team['wolf'] : false);
        $WolfName = ($Wolf ? implode(',',array_column($Wolf,'Link')) : false);

        $OlgoMessage = ($Wolf ? self::$Dt->LG->_('OlgoChangedToTeam',array("{0}" =>  $U_name, "{1}" => $WolfName)) : self::$Dt->LG->_('OlgoChangedToTone',array("{0}" =>  $U_name)));
        self::SendMessage($OlgoMessage,$Vahshi['user_id']);
        if($Wolf){
            $wolfMessage = self::$Dt->LG->_('OlgoChangedTo',array("{0}" =>  $U_name, "{1}" => $VahshiName));
            self::SendForWolfTeam($wolfMessage);
        }
        if(R::Get('game_state') == "night"){
            R::rpush($Vahshi['user_id'],'GamePl:SendNight');
        }
        self::ConvertPlayer($Vahshi['user_id'],'role_WolfGorgine');

        return true;
    }

    public static function CheckRezrv($U_name){
        $Rezrv = HL::_getPlayerByRole('role_PishRezerv');
        if($Rezrv == false){
            return false;
        }
        if($Rezrv['user_state'] !== 1){
            return false;
        }

        $RezrvName =  self::ConvertName($Rezrv['user_id'],$Rezrv['fullname_game']);
        $RzrvMessage = self::$Dt->LG->_('ApprenticeNowSeer',array("{0}" =>   $U_name));
        self::SendMessage($RzrvMessage,$Rezrv['user_id']);
        R::rpush($Rezrv['user_id'],'GamePl:SendNight');
        R::GetSet($RezrvName,'GamePl:SearUser');
        self::ConvertPlayer($Rezrv['user_id'],'role_pishgo');
        $Nazer = HL::_getPlayerByRole('role_Nazer');
        if($Nazer){
            $NazerMessage = self::$Dt->LG->_('BeholderNewSeer',array("{0}" =>   $RezrvName, "{1}" => $U_name));
            HL::SendMessage($NazerMessage,$Nazer['user_id']);
        }

        return true;
    }

    public static function CheckKhaen(){
        $Khaen = HL::_getPlayerByRole('role_Khaen');
        if($Khaen == false){
            return false;
        }
        if($Khaen['user_state'] !== 1){
            return false;
        }

        $P_Team = HL::PlayerByTeam();
        $Wolf =  (count($P_Team['wolf']) > 0 ? $P_Team['wolf'] : false);

        if($Wolf == false){
            R::GetSet('GamePl:NotSend:'.$Khaen['user_id'],R::Get('GamePl:Night_no'));
            self::ConvertPlayer($Khaen['user_id'],'role_WolfGorgine');
            $KhaenMessage = self::$Dt->LG->_('TraitorTurnWolf');
            self::SendMessage($KhaenMessage,$Khaen['user_id']);
            return true;
        }

        return false;
    }
    
    public static function CheckFranc(){
        $Franc = HL::_getPlayerByRole('role_franc');
        if($Franc === false){
            return false;
        }
        if($Franc['user_state'] !== 1){
            return false;
        }

        $P_Team = self::PlayerByTeam();
        $FrancD =  (count($P_Team['ferqe']) > 0 ? $P_Team['ferqe'] : false);

        if(!$FrancD){
            R::GetSet('GamePl:NotSend:'.$Franc['user_id'],R::Get('GamePl:Night_no'));
            $FrancMessage = self::$Dt->LG->_('FrancDeadCult');
            self::SendMessage($FrancMessage,$Franc['user_id']);
            R::GetSet(true,'GamePl:FrancNightOk');
            R::DelKey('GamePl:role_franc:*');
            return true;
        }

        return false;
    }

    public static function CheckPlayerEnchanter($Del){
        $data = R::Sort('GamePl:Enchanter','desc');
        if (($key = array_search($Del, $data)) !== false) {
            unset($data[$key]);
        }
        return $data;

    }
    public static function ClearEnchanter(){
        if(R::CheckExit('GamePl:Enchanter')){
            $Data = R::LRange(0,-1,'GamePl:Enchanter');
            foreach ($Data as $user_id) {
                $player = self::_getPlayer($user_id);
                if($player) {
                    if($player['user_state'] == 1) {
                        $Message = self::$Dt->LG->_('ClearEnchanter');
                        self::SendMessage($Message, $user_id);
                    }
                }
            }

            R::Del('GamePl:Enchanter');
        }
    }



    public static function CheckSendNight($user_id){
        $data =  R::LRange(0,-1,'GamePl:SendNight');
        if($data){

            if(in_array($user_id,$data)){
                return true;
            }
        }

        return false;
    }

    public static function ConvertforestQueen($Name){
        $forestQueen = HL::_getPlayerByRole('role_forestQueen');
        if($forestQueen == false){
            return false;
        }
        if($forestQueen['user_state'] !== 1){
            return false;
        }

        $forestQueenName = self::ConvertName($forestQueen['user_id'],$forestQueen['fullname_game']);

        $MessageforestQueenConvert = self::$Dt->LG->_('forestQueenConvert',array("{0}" =>  $Name));
        self::SendMessage($MessageforestQueenConvert,$forestQueen['user_id']);

        $WolfMessage = self::$Dt->LG->_('forestQueenConvertForTeamWolf',array("{0}" => $Name, "{1}"=> $forestQueenName));
        self::SendForWolfTeam($WolfMessage);
        R::GetSet(true,'GamePl:role_forestQueen:forestQueenBitten');
        R::GetSet(true, 'GamePl:role_forestQueen:AlphaDead');

    }
    public static function RemoveUser($user_id){
        self::$Dt->collection->join_user->updateOne(array("chat_id"=>self::$Dt->chat_id),array('$pull' => array("users" => ['user_id' => $user_id])));
    }

    public static function RandomBookChange($UserId){
        if(R::CheckExit('GamePl:BookIn:'.$UserId)){
            R::Del('GamePl:BookIn:'.$UserId);
            $NotUserId = [$UserId];
            $GetKeys = R::Keys('GamePl:BookIn:*');
            if($GetKeys){
                foreach ($GetKeys as $key) {
                    $explode = explode(":",$key);
                    array_push($NotUserId,$explode['3']);
                }
            }

            $result = self::$Dt->collection->games_players->findOne([
                'game_id' => self::$Dt->game_id
                , 'group_id' => self::$Dt->chat_id
                , 'user_state' => 1
                , 'user_status' => 'on'
                , 'user_role' => ['$nin' => ['role_Harly','role_Joker']]
                , 'user_id' => ['$nin' => $NotUserId]
            ]);
            if ($result) {
                $array = iterator_to_array($result);
                R::GetSet(true,'GamePl:BookIn:'.$array['user_id']);
            }
        }
    }
    public static function UserDead($Detial,$for){
        if(!is_array($Detial)){
            $Detial = self::_getPlayer($Detial);
        }



        if($for == "afked") {
            HL::SaveGameActivity($Detial, 'afk', ['user_id' => 0, 'fullname' => 0]);
        }

        $RNo = R::NoPerfix();
        $TimeGame = time() -  R::Get('GamePl:StartedTime');
        $left_times = ($RNo->exists('userGameTime:'.$Detial['user_id']) ? $RNo->get('userGameTime:'.$Detial['user_id']) : 0);
        $RNo->set('userGameTime:'.$Detial['user_id'], $left_times +  $TimeGame);

        $user_id = $Detial['user_id'];
        $Name = self::ConvertName($user_id,$Detial['fullname_game']);

        R::rpush($Name,'playerDeadName');
        $status = 0;
        if($for == "smite"){
            $status = 2;
        }
        $result = self::$Dt->collection->games_players->updateOne(
            ['user_id' => (float) $user_id,'game_id'=> self::$Dt->game_id,'group_id'=> self::$Dt->chat_id],
            ['$set' => ['user_state' => $status, 'user_status' => $for ,'dead_time' => time() ]]
        );

        if(R::CheckExit("GamePl:KhalifaSelectRole")){
            $USerID = R::Get("GamePl:KhalifaSelectRoleUserId");
            if((float) $Detial['user_id'] == (float) $USerID){
                R::Del('GamePl:KhalifaSelectRoleAs');
                R::Del('GamePl:KhalifaSelectRole');
                $GroupMessage = self::$Dt->LG->_('VoteKillPlayer',array("{0}" => $Name));
                self::SaveMessage($GroupMessage);
            }
        }


        if(R::Get('GamePl:Night_no') == 0) {
            R::rpush($Detial['user_id'], 'GamePl:NightKill');
        }

        /*
        if(R::CheckExit('GamePl:KhalifaSelectRole')){
            $Role = R::Get('amePl:KhalifaSelectRoleAs');
            if($Detial['user_role'] == $Role){
                R::Del('GamePl:KhalifaSelectRoleAs');
                R::Del('GamePl:KhalifaSelectRole');
            }
        }
        */

        if(R::CheckExit('GamePl:love:'.$user_id)  && R::CheckExit('GamePl:CheckLover:'.$user_id) == false){
            if($for == "afked"){
                R::GetSet(true,'GamePl:CheckLover:'.$user_id);
            }else {
                $LoverId = R::Get('GamePl:love:'.$user_id);
                $LoverDetial = self::_getPlayer($LoverId);
                if($LoverDetial) {
                    if($LoverDetial['user_state'] == 1) {
                        $LoverName = self::ConvertName($LoverDetial['user_id'], $LoverDetial['fullname_game']);
                        $GroupMessage = self::$Dt->LG->_('LoverDied', array("{0}" => $Name, "{1}"=>  $LoverName,"{2}"=> self::$Dt->LG->_('user_role', array("{0}" => self::$Dt->LG->_($LoverDetial['user_role'] . "_n")))));
                        if (R::CheckExit('GamePl:HunterKill')) {
                            HL::SendMessage($GroupMessage);
                        } else {
                            self::SaveMessage($GroupMessage);
                        }
                        R::GetSet(true, 'GamePl:CheckLover:' . $LoverId);
                        R::GetSet(true, 'GamePl:CheckLover:' . $user_id);
                        HL::SaveGameActivity($LoverDetial, 'love_dead', $Detial);

                        if ($LoverDetial['user_role'] == "role_kalantar") {
                            self::HunterKill($GroupMessage, $LoverDetial['user_id'], 'kill');
                        }
                        self::UserDead($LoverDetial, 'love');
                    }
                }
            }

        }







        if($Detial['user_role'] == "role_Sweetheart"){
            if(R::CheckExit('GamePl:SweetheartLove')){
                R::DelKey('GamePl:SweetheartLove*');
            }
        }

        // چک میکنیم اگه کاربر تو لیست طلسم ها بود پاک شه
        if(R::CheckExit('GamePl:Enchanter')){
            self::CheckPlayerEnchanter($Detial['user_id']);
        }



        // اگه طرف  افسونگر بود تمام طلسم ها پاک شه
        if($Detial['user_role'] == "role_enchanter"){
            self::ClearEnchanter();
        }


        // اگه طرف وحشی بود پاک میکنیم الگوش کی بوده
        if($Detial['user_role'] == "role_Vahshi"){
            R::Del('GamePl:Olgo');
        }

        // چک میکنیم طرف الگو کسی بوده یا نه
        if(R::CheckExit('GamePl:Olgo')){
            $OlgoId = R::Get('GamePl:Olgo');
            if($OlgoId == $user_id){
                self::ConvertOlgo($Detial,$Name);
                R::Del('GamePl:Olgo');
                R::Del('GamePl:OlgoName');
            }
        }


        if($Detial['user_role'] == "role_Ruler"){
            if(R::CheckExit('GamePl:role_Ruler:RulerOk') && R::CheckExit('GamePl:RulerOkSend') == false){
                $groupMessage = self::$Dt->LG->_('RulerIsDead');
                self::SaveMessage($groupMessage);
                R::Del('GamePl:role_Ruler:RulerOk');
            }
        }

        if($Detial['user_role'] == "role_Fereshte"){
            R::DelKey('GamePl:role_angel:*');
        }

        // اگر همزاد بود نقش نگرفت پاک کن همزاد کی بوده
        if($Detial['user_role'] == "role_Hamzad"){
            R::Del('GamePl:Hamzad');
        }
        // چک میکنیم طرف  همزاد داشت یا نه
        if(R::CheckExit('GamePl:Hamzad')){
            $HamzadId = R::Get('GamePl:Hamzad');
            if($HamzadId == $user_id){
                self::ConvertHamzad($Detial,$Name);
                R::Del('GamePl:Hamzad');
                R::DelKey("GamePl:UserInHome:{$Detial['user_role']}*");
                R::DelKey("GamePl:Selected:{$Detial['user_role']}*");
                R::GetSet(true,'GamePl:Kill');
                R::DelKey("GamePl:{$Detial['user_role']}:*");
                self::RemoveUser($user_id);
                return true;
            }
        }

        if($Detial['user_role'] == "role_Margita"){
            $Shahzade = self::_getPlayerByRole('role_Shahzade');
            if($Shahzade) {
                if ($Shahzade['user_state'] == 1) {
                    $Name = self::ConvertName($Shahzade['user_id'],$Shahzade['fullname_game']);
                    $shahzadeMessage = self::$Dt->LG->_('DieMargaritaShahzadeMessage',array("{0}" => $Name));
                    self::SendMessage($shahzadeMessage,$Shahzade['user_id']);
                    R::GetSet(true,'GamePl:MargaritaKilled');
                }
            }
        }
        
        if($Detial['user_role'] == 'role_feriga'){
            R::GetSet(true,'GamePl:Killed_feriga');
        }


        if($Detial['user_role'] == 'role_viego'){
            R::GetSet(true,'GamePl:Killed_viego');
        }


        if($Detial['user_role'] == 'role_Firefighter'){
            self::Fired($Detial);
        }
        if($Detial['user_role'] == "role_Qatel" || $Detial['user_role'] == "role_Archer" || $Detial['user_role'] == "role_hilda"){
            $Morgana = self::_getPlayerByRole('role_morgana');
           if($Morgana) {
               $checkRole = self::_getPlayerINRole(['role_Qatel','role_Archer','role_hilda']);

               if (!$checkRole) {
                 $MorganaMessage = self::$Dt->LG->_('AskMorganaWhenKillAll',array("{0}" => $Name));
                 self::SendMessage($MorganaMessage,$Morgana['user_id']);
                   R::GetSet(true,'GamePl:KillAllKillerTeam');
               }
           }

        }
        if($Detial['user_role'] == "role_BlackKnight"){
            $Birde = self::_getPlayerByRole('role_BrideTheDead');
            if($Birde) {
                if ($Birde['user_state'] == 1) {
                    $Name = self::ConvertName($Birde['user_id'],$Birde['fullname_game']);
                    $GroupMsg = self::$Dt->LG->_('BrideTheDeadBlackDie',array("{0}" => $Name));
                    self::SaveMessage($GroupMsg);
                    self::UserDead($Birde,'black');
                }
            }
        }




        if(R::CheckExit('GamePl:BookIn:'.$Detial['user_id'])){
          self::RandomBookChange($Detial['user_id']);
        }

        if($Detial['user_role'] == "role_Joker"){
            $Harly = HL::_getPlayerByRole('role_Harly');
            if($Harly){
                $HarlyMessage = self::$Dt->LG->_('HarlyWhenDiedJoker');
                self::SendMessage($HarlyMessage,$Harly['user_id']);
                R::GetSet(true,'GamePl:DiedJoker');
             }
        }
        if($Detial['user_role'] == "role_Sharlatan"){
           R::Del('GamePl:SharlatanInTofan');
        }
        if($Detial['user_role'] == "role_Khalifa"){
           $PlayerMsg = self::$Dt->LG->_('PlayerMessageFindKhalifa');
           self::SendMessage($PlayerMsg,R::Get('GamePl:KhalifaSelectRoleUserId'));
           R::Del('GamePl:KhalifaSelectRoleAs');
            R::Del('GamePl:KhalifaSelectRole');
        }

        if($Detial['user_role'] == "role_Harly"){
            $Joker = HL::_getPlayerByRole('role_Joker');
            if($Joker){
                $JokerMessage = self::$Dt->LG->_('JokerMessageWhenHalryDied');
                self::SendMessage($JokerMessage,$Joker['user_id']);
                R::GetSet(true,'GamePl:DiedHarly');
            }
        }

        if($Detial['user_role'] === "role_Princess"){
            $GetPlayer = R::Keys('GamePl:PrincessPrisoner:*');
            if($GetPlayer) {
                foreach ($GetPlayer as $row){
                    $explode = explode(':',$row);
                    if(isset($explode['3'])) {
                        $UserId = $explode['3'];
                         $Player =  self::_getPlayer($UserId);
                        if($Player['user_state'] !== 1){
                            continue;
                        }
                        $PlayerMessage = self::$Dt->LG->_('PrincessDead');
                        self::SendMessage($PlayerMessage,$Player['user_id']);
                    }

                }
                R::DelKey('GamePl:PrincessPrisoner:*');
            }
        }
        if($Detial['user_role'] === "role_Phoenix"){
            $GetPlayer = R::Keys('GamePl:PhoenixHealer:*');
            if($GetPlayer) {
                foreach ($GetPlayer as $row){
                    $explode = explode(':',$row);
                    if(isset($explode['3'])) {
                        $UserId = $explode['3'];
                         $Player =  self::_getPlayer($UserId);
                        if($Player['user_state'] !== 1){
                            continue;
                        }
                        $PlayerMessage = self::$Dt->LG->_('MessagePlayerPhoenixDead');
                        self::SendMessage($PlayerMessage,$Player['user_id']);
                    }

                }
                R::DelKey('GamePl:PhoenixHealer:*');
            }
        }

        if($Detial['user_role'] == "role_shekar"){
            $Huntsman = HL::_getPlayerByRole('role_Huntsman');
            if($Huntsman) {
                if ($Huntsman['user_state'] == 1) {
                    R::GetSet(R::Get('GamePl:Night_no'),'GamePl:NotSend:'.$Huntsman['user_id']);
                    $HuntsmanMessage = self::$Dt->LG->_('HuntsmanDeadCultHulter',array("{0}" => $Name));
                    HL::SendMessage($HuntsmanMessage,$Huntsman['user_id']);
                    self::ConvertPlayer($Huntsman['user_id'],'role_shekar');

                }
            }
        }
        if($Detial['user_role'] == "role_IceQueen" || $Detial['user_role'] == "role_Firefighter" ){
            $Fire = self::_getPlayerByRole('role_Firefighter',false);
            $Ice = self::_getPlayerByRole('role_IceQueen',false);
            if(!$Fire && !$Ice){
                $LILis = self::_getPlayerByRole('role_Lilis',false);
                if($LILis && !R::CheckExit('GamePl:DieFireAndIc')) {
                    $LilisMessage = self::$Dt->LG->_('KillAllTeamLilis');
                    self::SendMessage($LilisMessage,$LILis['user_id']);
                    R::GetSet(true,'GamePl:DieFireAndIc');
                }
            }
        }
        // اگر توله مرده بود  2 بار بتونن بخورن
        if($Detial['user_role'] == "role_WolfTolle" && $for !== "afked"){
            R::GetSet((R::Get('GamePl:Night_no') + 1),'GamePl:WolfCubeDead');
        }


        // اگه رئیس فرقه مورده بود به فرقه گرا ها پیام بده بگو که شعد بعد میتونن 2 نفرو دعوت بدن
        if($Detial['user_role'] == "role_Royce" and $for !== "afked"){
            $CultMessage = self::$Dt->LG->_('RoyceDead',array("{0}" =>$Name));
            R::GetSet((R::Get('GamePl:Night_no') + 1), 'GamePl:RoyceDead');
            self::SendForCultTeam($CultMessage,$Detial['user_id']);
        }


        if($Detial['user_role'] == "role_Bloodthirsty"){
            $VampireMessage = self::$Dt->LG->_('DeadBldBeforeFinde',array("{0}" =>$Name));
            self::SendForVampireTeam($VampireMessage);
            R::GetSet(20,'GamePl:VampireConvert');
            R::Del('GamePl:Bloodthirsty');
            R::Del('GamePl:VampireFinded');
        }
        if($Detial['user_role'] == "role_kalantar"){
            if(R::CheckExit('GamePl:BloodthirstyInGame') && R::CheckExit('GamePl:VampireFinded') == false){
                $Bloodthirsty = HL::_getPlayerByRole('role_Bloodthirsty',false);
                if($Bloodthirsty){
                    $P_Team = HL::PlayerByTeam();
                    $Vampire =  (count($P_Team['vampire']) > 0 ? $P_Team['vampire'] : false);
                    $VampireName = ($Vampire ? implode(',',array_column($Vampire,'Link')) : false);
                    if($Vampire) {
                        // ارسال پیام برای تیم ومپایر
                        $VampireMessage = self::$Dt->LG->_('VampireDeadHunterBeforeFinde',array("{0}" =>  $Name,"{1}"=> R::Get('GamePl:BloodthirstyInGame')));
                        HL::SendForVampireTeam($VampireMessage, $Bloodthirsty['user_id']);
                    }
                    // ارسال پیام برای اصیل
                    $BlooadMessage = self::$Dt->LG->_('VampireDeadHunterBeforeFindeBlodMessage',array("{0}" => $Name, "{1}"=> $Vampire ? $VampireName : self::$Dt->LG->_('VampireNoTeam')));
                    HL::SendMessage($BlooadMessage,$Bloodthirsty['user_id']);

                    R::GetSet('GamePl:NotSend:'.$Bloodthirsty['user_id'],R::Get('GamePl:Night_no'));
                    R::GetSet(true,'GamePl:VampireFinded');
                    R::GetSet(SE::_s('BVampireChangeConvet'),'GamePl:VampireConvert');
                    R::GetSet(true,'GamePl:Bloodthirsty');
                }

            }
        }
        // چک میکنیم اگه طرف آلفا بود ملکه جنگل نقش بگیره
        if($Detial['user_role'] == "role_WolfAlpha"){
            self::ConvertforestQueen($Name);
        }

        if($Detial['user_role'] == 'role_shekar'){
            R::GetSet(true,'GamePl:cult_hunterKill');
        }




        // چک میکنیم اگه طرف آلفا بود ملکه جنگل نقش بگیره
        if($Detial['user_role'] == "role_forestQueen" && $for !== "afked"){
            if(R::CheckExit('GamePl:role_forestQueen:AlphaDead') == false) {
                R::GetSet((R::Get('GamePl:Night_no') + 1), 'GamePl:DeadforestQueen');
            }
        }


        // اگه طرف پیشگو بوده چک میکنیم پیش رزرو هست یا نه
        if($Detial['user_role'] == "role_pishgo"){
            self::CheckRezrv($Name);
        }


        if($Detial['user_role'] == "role_IceQueen"){
            if(R::CheckExit('GamePl:IceQueenIced')){
                R::DelKey('GamePl:IceQueenIced*');
            }
        }
        if($Detial['user_role'] == "role_Qatel") {
            $Hilda = self::_getPlayerByRole('role_hilda');
            if ($Hilda) {
                $HildaMessage = self::$Dt->LG->_('KillerKillHilda', array("{0}" => $Name));
                self::SendMessage($HildaMessage, $Hilda['user_id']);
                R::GetSet(true,'GamePl:KillerIsKillHildaInGame');
            }
        }

        R::DelKey("GamePl:UserInHome:{$Detial['user_role']}*");
        R::DelKey("GamePl:Selected:{$Detial['user_role']}*");
        R::GetSet(true,'GamePl:Kill');
        R::DelKey("GamePl:{$Detial['user_role']}:*");
        self::RemoveUser($user_id);

        if($Detial['team'] == "wolf") {
            // چک میکنیم خائن هست تو بازی یا نه
            self::CheckKhaen();
            // چک کردن برای تبدیل به گرگ اگه همه گرگا مونده بودن
            self::CheckWhiteWolf();
        }

        if($Detial['team'] == "ferqeTeem") {
            // چک میکنیم فرقه ای تو بازی هست یا نه
            self::CheckFranc();
        }
        
        if($Detial['team'] == "vampire") {
            self::CheckKentVampire();
        }
        return true;
    }

    public static function Fired($Firefighter){
        $List = R::LRange(0,-1,'GamePl:FirefighterList');

        $Re = [];
        foreach ($List as $data){
            $row = json_decode($data,true);
            $Detial = self::_getPlayer($row['user_id']);
            if($Detial['user_state'] !== 1){
                continue;
            }
            if(R::CheckExit('GamePl:role_WhiteWolf:AngelIn:'.$row['user_id'])){
                $MessageForPlayer = self::$Dt->LG->_('WolfMessageGourdWhiteWolf');
                self::SendMessage($MessageForPlayer,$row['user_id']);
                $WhiteWolfId = R::Get('GamePl:role_WhiteWolf:AngelIn:'.$row['user_id']);
                $MessageForWhiteWolf = self::$Dt->LG->_('WhiteWolfGourdFireFighter',array("{0}" => $row['link']));
                self::SendMessage($MessageForWhiteWolf,$WhiteWolfId);
                continue;
            }

            if(R::CheckExit('GamePl:role_morgana:AngelIn:'.$row['user_id'])){
                $MessageForPlayer = self::$Dt->LG->_('WolfMessageGourdMorgana');
                self::SendMessage($MessageForPlayer,$row['user_id']);
                $WhiteWolfId = R::Get('GamePl:role_morgana:AngelIn:'.$row['user_id']);
                $MessageForWhiteWolf = self::$Dt->LG->_('MorganaHealSuccess',array("{0}" =>$row['link']));
                self::SendMessage($MessageForWhiteWolf,$WhiteWolfId);
                R::GetSet($row['link'],'GamePl:role_morgana:AngelSaved');
                continue;
            }

            if(R::CheckExit('GamePl:role_angel:AngelIn:'.$row['user_id'])){
                $MessageForPlayer = self::$Dt->LG->_('AngelInHomeForPlayer');
                self::SendMessage($MessageForPlayer,$row['user_id']);
                $AngelId = R::Get('GamePl:role_angel:AngelIn:'.$row['user_id']);
                $MessageForAngel = self::$Dt->LG->_('AngelInHomeForAngel',array("{0}" =>$row['link']));
                self::SendMessage($MessageForAngel,$AngelId);
                R::GetSet($row['link'],'GamePl:role_angel:AngelSaved');
                continue;
            }
            if(R::CheckExit('GamePl:role_franc:AngelIn:'.$Detial['user_id'])){
                $MessageForPlayer = self::$Dt->LG->_('PlayerMessageFrancS');
                self::SendMessage($MessageForPlayer,$Detial['user_id']);
                $FreancId = R::Get('GamePl:role_franc:AngelIn:'.$Detial['user_id']);
                $MessageForFranc = self::$Dt->LG->_('FireAttackCult',array("{0}" => $row['link']));
                self::SendMessage($MessageForFranc,$FreancId);
                R::GetSet($row['link'],'GamePl:role_franc:AngelSaved');
                continue;
            }



            $MessageForPlayer = self::$Dt->LG->_('FireFighterMessageForPlayer');
            self::SendMessage($MessageForPlayer,$row['user_id'],'firefighter_you');

            if(count($List)  < 3) {
                $GroupMessage = self::$Dt->LG->_('FireFighterKillPlayerGroupMessage', array("{0}" => $row['link'], "{1}" => self::$Dt->LG->_('user_role', array("{0}" => self::$Dt->LG->_($Detial['user_role']  . "_n")))));
                self::SaveMessage($GroupMessage);
            }else{
                array_push($Re,$row['link']." - ".self::$Dt->LG->_('user_role', array("{0}" => self::$Dt->LG->_($Detial['user_role'] . "_n"))));
            }

            NG::CheckInHomePlayer($row['user_id'],$row['link'],$Firefighter);
            self::UserDead($Detial,'fireFighter');
            self::SaveGameActivity($Detial,'fire',$Firefighter);
        }

        if($Re){
            $GroupMessage = self::$Dt->LG->_('FireFighterKillPlayerGroupMessageK',array("{0}" =>implode(PHP_EOL,$Re)));
            self::SaveMessage($GroupMessage);
        }

        R::Del('GamePl:FirefighterList');
        R::Del('GamePl:FirefighterOk');
    }
    public static function CheckKentVampire(){

        $kent = HL::_getPlayerByRole('role_kentvampire');
        if(!$kent){
            return false;
        }

        if($kent['user_state'] !== 1){
            return false;
        }


        $P_Team = self::PlayerByTeam();
        $Vampire =  (count($P_Team['vampire']) > 0 ? $P_Team['vampire'] : false);

        if(!$Vampire){
            R::GetSet(true,'GamePl:KentVampireConvert');
            $kentMessage = self::$Dt->LG->_('KentVampireKillAllVampire');
            self::SendMessage($kentMessage,$kent['user_id']);
        }

        return true;
    }

    public static function CheckWhiteWolf(){
        $WhiteWolf = HL::_getPlayerByRole('role_WhiteWolf');
        if($WhiteWolf == false){
            return false;
        }
        if($WhiteWolf['user_state'] !== 1){
            return false;
        }

        $P_Team = HL::PlayerByTeam();
        $Wolf =  (count($P_Team['wolf']) > 0 ? $P_Team['wolf'] : false);

        if($Wolf == false){
            R::GetSet('GamePl:NotSend:'.$WhiteWolf['user_id'],R::Get('GamePl:Night_no'));
            self::ConvertPlayer($WhiteWolf['user_id'],'role_WolfGorgine');
            $WhiteWolfMessage = self::$Dt->LG->_('WhiteWolfDeadAllWolf');
            self::SendMessage($WhiteWolfMessage,$WhiteWolf['user_id']);
            R::GetSet(true,'GamePl:WhiteWolfToWolf');
            return true;
        }

        return false;
    }
    public static function _getPlayerByRole($role,$dead = false){
        if($dead == false){
            $query = ['game_id' => self::$Dt->game_id,'group_id' => self::$Dt->chat_id,'user_role' => $role,'user_state' => 1];
        }else{
            $query = ['game_id' => self::$Dt->game_id,'group_id' => self::$Dt->chat_id,'user_role' => $role,'user_state' => 0];
        }
        $result = self::$Dt->collection->games_players->findOne($query);
        if($result) {
            $array = iterator_to_array($result);
            return $array;
        }
        return false;
    }
    public static function _getPlayerByRoleGroup($role,$dead = false){
        if($dead == false){
            $query = ['game_id' => self::$Dt->game_id,'group_id' => self::$Dt->chat_id,'user_role' => $role,'user_state' => 1];
        }else{
            $query = ['game_id' => self::$Dt->game_id,'group_id' => self::$Dt->chat_id,'user_role' => $role,'user_state' => 0];
        }
        $result = self::$Dt->collection->games_players->find($query);
        if($result) {
            $array = iterator_to_array($result);
            return $array;
        }
        return false;
    }

    public static function _getPlayerById($user_id){
        $result = self::$Dt->collection->games_players->findOne(['game_id' => self::$Dt->game_id,'group_id' => self::$Dt->chat_id,'user_id' => $user_id]);
        if($result) {
            $array = iterator_to_array($result);
            return $array;
        }
        return false;
    }
    public static function SendMessage($msg,$chat_id = false,$Gif = false,$markdown = false){
        if($chat_id == false){
            $chat_id = self::$Dt->chat_id;
        }

        if($Gif){
            $GifKey = SE::GetGif($Gif);
            if($GifKey) {
                return Request::sendVideo([
                    'chat_id' => $chat_id,
                    'video' => $GifKey,
                    'caption' => $msg,
                    'parse_mode' => (!$markdown ? 'HTML' : 'Markdown'),
                ]);
            }
        }

        Request::sendMessage([
            'chat_id' => $chat_id,
            'text' => $msg,
            'parse_mode' =>  (!$markdown ? 'HTML' : 'Markdown'),
        ]);
    }


    public static function _GetByTeam($Team){
        $result = self::$Dt->collection->games_players->find(['team' => $Team,'game_id' => self::$Dt->game_id,'group_id' => self::$Dt->chat_id,'user_state' => 1 ,'user_status' => 'on']);
        if($result) {
            $re = [];
            $array = iterator_to_array($result);
            foreach ($array as $Key =>  $row){
                if($row['user_role'] == 'role_forestQueen') {
                    if (R::CheckExit('GamePl:role_forestQueen:AlphaDead') == false) {
                        continue;
                    }
                    $re[] = $array[$Key];
                }

                if($row['user_role'] == "role_Bloodthirsty"){
                    if(R::CheckExit('GamePl:Bloodthirsty') == false){
                        continue;
                    }
                    $re[] = $array[$Key];
                    continue;
                }
                switch ($Team){
                    case 'wolf':
                        $wolfRole = SE::WolfRole();
                        if(in_array($row['user_role'],$wolfRole)){
                            $re[] = $array[$Key];
                        }
                        break;
                    default:
                        $re[] = $array[$Key];
                        break;
                }
            }

            return $re;
        }
        return false;
    }

    public static function SendForVampireTeam($msg,$sendMe = false,$gif = false){
        $no_in = ($sendMe ? [$sendMe] : []);
        $user = self::_GetByTeam('vampire');
        if($user){
            foreach ($user as $row){
                if(!in_array($row['user_id'],$no_in)) {
                    self::SendMessage($msg,$row['user_id'],$gif);
                }
            }
        }
    }
    public static function SendForQatelTeam($msg,$sendMe = false,$gif = false){
        $no_in = ($sendMe ? [$sendMe] : []);
        $user = self::_GetByTeam('qatel');
        if($user){
            foreach ($user as $row){
                if(!in_array($row['user_id'],$no_in)) {
                    self::SendMessage($msg,$row['user_id'],$gif);
                }
            }
        }
    }



    public static function SendForWolfTeam($msg,$sendMe = false,$gif = false){
        $no_in = ($sendMe == true ? [$sendMe] : []);
        $user = self::_GetByTeam('wolf');
        if($user){
            foreach ($user as $row){
                if(!in_array($row['user_id'],$no_in)) {
                    self::SendMessage($msg,$row['user_id'],$gif);
                }
            }
        }
    }

    public static function SendForCultTeam($msg,$sendMe = false,$gif = false){
        $no_in = ($sendMe == true ? [$sendMe] : []);
        $user = self::_GetByTeam('ferqeTeem');
        if($user){
            foreach ($user as $row){
                if($row['user_role'] == "role_IceDragon"){
                    continue;
                }

                if(!in_array($row['user_id'],$no_in)) {
                    self::SendMessage($msg,$row['user_id'],$gif);
                }
            }
        }
    }

    public static function ConvertPlayer($user_id,$to,$hamzad = false){
        $Detial = self::_getPlayer($user_id);

        if($Detial['user_role'] == "role_enchanter"){
            self::ClearEnchanter();
        }
        $PlayerName = self::ConvertName($Detial['user_id'],$Detial['fullname_game']);

        if($Detial['user_role'] == "role_pishgo"){
            $Rezrv = HL::_getPlayerByRole('role_PishRezerv');
            if($Rezrv){
                if($Rezrv['user_state'] == 1){
                    $RezrvName =  self::ConvertName($Rezrv['user_id'],$Rezrv['fullname_game']);
                    $RzrvMessage = self::$Dt->LG->_('ChangeRolePishgo');
                    self::SendMessage($RzrvMessage,$Rezrv['user_id']);
                    R::rpush($Rezrv['user_id'],'GamePl:SendNight');
                    R::GetSet($RezrvName,'GamePl:SearUser');
                    self::ConvertPlayer($Rezrv['user_id'],'role_pishgo');
                    $Nazer = HL::_getPlayerByRole('role_Nazer');
                    if($Nazer){
                        $NazerMessage = self::$Dt->LG->_('MessageForNazer',array("{0}" => $PlayerName,"{1}"=> $RezrvName));
                        HL::SendMessage($NazerMessage,$Nazer['user_id']);
                    }
                }
            }
        }

        if($Detial['user_role'] == "role_shekar" && $Detial['user_role'] !== 'role_Huntsman'){
            $Huntsman = HL::_getPlayerByRole('role_Huntsman');
            if($Huntsman) {
                if ($Huntsman['user_state'] == 1) {
                    R::GetSet(R::Get('GamePl:Night_no'),'GamePl:NotSend:'.$Huntsman['user_id']);
                    $HuntsmanMessage = self::$Dt->LG->_('HuntsmanDeadCultHulter',array("{0}" => $PlayerName));
                    HL::SendMessage($HuntsmanMessage,$Huntsman['user_id']);
                    self::ConvertPlayer($Huntsman['user_id'],'role_shekar');
                }
            }
        }

        if($Detial['user_role'] == "role_kalantar" && $hamzad == false){

            if(R::CheckExit('GamePl:BloodthirstyInGame') && R::CheckExit('GamePl:VampireFinded') == false){
                $Bloodthirsty = HL::_getPlayerByRole('role_Bloodthirsty',false);
                if($Bloodthirsty) {
                    if ($Bloodthirsty['user_state'] == 1) {
                        $P_Team = HL::PlayerByTeam();
                        $Vampire = (count($P_Team['vampire']) > 0 ? $P_Team['vampire'] : false);
                        $VampireName = ($Vampire ? implode(',', array_column($Vampire, 'Link')) : false);
                        if ($Vampire) {
                            // ارسال پیام برای تیم ومپایر
                            $VampireMessage = self::$Dt->LG->_('VampireChangeRoleBeforeFinde', array("{0}" => R::Get('GamePl:BloodthirstyInGame')));
                            HL::SendForVampireTeam($VampireMessage, $Bloodthirsty['user_id']);
                        }
                        // ارسال پیام برای اصیل
                        $BlooadMessage = self::$Dt->LG->_('VampireChangeHunterBeforeFindeBlodMessage', array("{0}" => $Vampire ? $VampireName : self::$Dt->LG->_('VampireNoTeam')));
                        HL::SendMessage($BlooadMessage, $Bloodthirsty['user_id']);

                        R::GetSet('GamePl:NotSend:' . $Bloodthirsty['user_id'], R::Get('GamePl:Night_no'));
                        R::GetSet(true, 'GamePl:VampireFinded');
                        R::GetSet(SE::_s('BVampireChangeConvet'), 'GamePl:VampireConvert');
                        R::GetSet(true, 'GamePl:Bloodthirsty');
                    }
                }

            }
        }

        switch ($to){
            case 'role_rishSefid':
                R::Del('GamePl:Eatelder');
                break;
            case 'role_tofangdar':
                R::GetSet(2,'GamePl:GunnerBult');
                break;
            case 'role_Kadkhoda':
                R::Del('GamePl:NotSend_role_Kadkhoda');
                break;
            case 'role_Solh':
                break;
        }

        R::GetSet(SE::GetRoleTeam($to),"GamePl:user:{$user_id}:team");
        R::GetSet($to,"GamePl:user:{$user_id}:role");
        R::GetSet(true,'GamePl:ChangedUserRole:'.$user_id);
        // » در یک بازی نقشتون تغییر پیدا کنه
        HL::SavePlayerAchivment($user_id,'Change_Sides_Works');
        if($to !== "role_ferqe"){
            R::GetSet((R::Get("GamePl:UserXchangeRole:{$user_id}") ?? 0 ) + 1,"GamePl:UserXchangeRole:{$user_id}");
        }
        $result = self::$Dt->collection->games_players->updateOne(
            ['user_id' => (float) $user_id,'game_id'=> self::$Dt->game_id,'group_id'=> self::$Dt->chat_id],
            ['$set' => ['user_role' => $to, 'team' => SE::GetRoleTeam($to) ,'change_time' => time() ]]
        );

    }

    public static function CheckAlphaInGame(){
        $result = self::$Dt->collection->games_players->count(['team' => 'wolf','game_id' => self::$Dt->game_id,'group_id' => self::$Dt->chat_id,'user_state' => 1 ,'user_status' => 'on','user_role' => 'role_WolfAlpha']);
        return $result;
    }


    public static function _getLastVampire(){

        $RoleVampire = ['role_Vampire'];

        $result = self::$Dt->collection->games_players->findOne([
            'team' => 'vampire'
            ,'game_id' => self::$Dt->game_id
            ,'group_id' => self::$Dt->chat_id
            ,'user_state' => 1
            ,'user_status' => 'on'
            ,'user_role' => ['$in' => $RoleVampire]
        ],[
            'sort' => ['change_time' => -1],
        ]);

        if($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }



    public static function _getPlayerINRole($role){


        $result = self::$Dt->collection->games_players->find([
            'game_id' => self::$Dt->game_id
            ,'group_id' => self::$Dt->chat_id
            ,'user_state' => 1
            ,'user_status' => 'on'
            ,'user_role' => ['$in' => $role]
        ]);

        if($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }

    public static function _getLastWolf(){
        $wolfRole = SE::WolfRole();
        if (R::CheckExit('GamePl:role_forestQueen:AlphaDead')) {
            array_push($wolfRole,'role_forestQueen');
        }

        $result = self::$Dt->collection->games_players->findOne([
            'team' => 'wolf'
            ,'game_id' => self::$Dt->game_id
            ,'group_id' => self::$Dt->chat_id
            ,'user_state' => 1
            ,'user_status' => 'on'
            ,'user_role' => ['$in' => $wolfRole]
        ],[
            'sort' => ['change_time' => -1],
        ]);

        if($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }

    public static function BittanPlayer($user_id){
        R::GetSet($user_id,'GamePl:BittanPlayer');
    }


    public static function BittanPlayerEnchanter($user_id){
        R::GetSet((float) $user_id,'GamePl:EnchanterBittanPlayer');
    }



    public static function BittanPlayerMidnight($user_id){
        R::GetSet((float) $user_id,'GamePl:MidnightBittanPlayer');
    }

    public static function BittanPlayerOrlok($user_id){
        R::GetSet((float) $user_id,'GamePl:OrlokBittanPlayer');
    }


    public static function _getDeadMesssage($role,$name){

        switch ($role){
            case 'role_pishgo':
                return self::$Dt->LG->_('RolePishgo_eat',array("{0}" =>$name));
                break;
            case 'role_Mast':
                return self::$Dt->LG->_('RoleMast_eat',array("{0}" =>$name));
                break;
            case 'role_tofangdar':
                return self::$Dt->LG->_('RoleTofangdar_eat',array("{0}" =>$name));
                break;
            case 'role_ahmaq':
                return self::$Dt->LG->_('RoleAhmag_eat',array("{0}" =>$name));
                break;
            case 'role_karagah':
                return self::$Dt->LG->_('roleKaragh_eat',array("{0}" =>$name));
                break;
            case 'role_WolfJadogar':
                return self::$Dt->LG->_('SorcererEaten',array("{0}" =>$name));
                break;
            case 'role_enchanter':
                return self::$Dt->LG->_('EatenEnchanter',array("{0}" =>$name));
                break;
            case 'role_PishRezerv':
                return self::$Dt->LG->_('ApprenticeSeerEaten',array("{0}" =>$name));
                break;
            case 'role_hilda':
                return self::$Dt->LG->_('WolfKillHilda',array("{0}" =>$name));
                break;
        }

        return false;
    }

    public static function MesssageQatel($user_role,$name){

        switch ($user_role){
            case 'role_ferqe':
                return self::$Dt->LG->_('CultistKilled',array("{0}" =>$name));
                break;
            case 'role_Mast':
                return self::$Dt->LG->_('DrunkKilled',array("{0}" =>$name));
                break;
            case 'role_elahe':
                return self::$Dt->LG->_('CupidKilled',array("{0}" =>$name));
                break;
            case 'role_Ahangar':
                return self::$Dt->LG->_('BlacksmithKilled',array("{0}" =>$name));
                break;
            case 'role_Fereshte':
                return self::$Dt->LG->_('GuardianAngelKilled',array("{0}" =>$name));
                break;
            case 'role_tofangdar':
                return self::$Dt->LG->_('GunnerKilled',array("{0}" =>$name));
                break;
            case 'role_Kadkhoda':
                return self::$Dt->LG->_('MayorKilled',array("{0}" =>$name));
                break;
            case 'role_Shahzade':
                return self::$Dt->LG->_('PrinceKilled',array("{0}" =>$name));
                break;
            case 'role_pishgo':
                return self::$Dt->LG->_('SeerKilled',array("{0}" =>$name));
                break;
            default:
                return self::$Dt->LG->_('DefaultKilled',array("{0}" => $name, "{1}" => self::$Dt->LG->_('user_role',array("{0}" =>self::$Dt->LG->_($user_role."_n")))));
                break;
        }

        return false;
    }


    public static function PlusTime($Second){
        $Time = R::Get('timer');
        R::GetSet(($Time + $Second),'timer');
    }

    public static function WolfCubeDead(){
        $P_Team = HL::PlayerByTeam();
        $Wolf =  (count($P_Team['wolf']) > 0 ? $P_Team['wolf'] : false);
        if($Wolf){
            $WolfUserId = ($Wolf ? array_column($Wolf,'user_id') : false);
            self::EditMarkupKeyboard();
            self::PlusTime(30);
            R::GetSet(true, 'GamePl:WolfCubeSelect2');
            self::SendGroupMessage(false);
            R::Del('GamePl:SendNightAll');
            R::Del('GamePl:CheckNight');
            (count($P_Team['wolf']) > 1 ? R::DelKey('GamePl:Selected:Wolf:*') : R::Del('GamePl:Selected:'.$WolfUserId['0']));
            foreach ($WolfUserId as $user_id){
                R::LRem($user_id,1,'GamePl:SendNight');
            }
        }

    }

    public static function RoyceDeadSelect(){
        $P_Team = HL::PlayerByTeam();
        $Cult =  (count($P_Team['ferqe']) > 0 ? $P_Team['ferqe'] : false);
        if($Cult) {
            $FerqeUserId = ($Cult ? array_column($Cult,'user_id') : false);
            self::EditMarkupKeyboard();
            R::GetSet(true, 'GamePl:RoyceSelectd2');
            R::DelKey('GamePl:Selected:*'); // پاک کردن انتخاب ها
            self::PlusTime(30);
            R::Del('GamePl:CheckNight');
            R::Del('GamePl:SendNightAll');
            foreach ($FerqeUserId as $user_id){
                R::LRem($user_id,1,'GamePl:SendNight');
            }
        }
    }
    public static function HunterKill($Gp_message,$User_id = false,$For = 'kill'){
        self::EditMarkupKeyboard();
        R::GetSet(true,'GamePl:HunterKill');
        R::GetSet($For,'GamePl:KillFor');
        // به زمان بازی اضافه میکنیم
        self::PlusTime(45);
        self::SendMessage($Gp_message);
        if($User_id == false){
            return false;
        }
        switch ($For){
            case 'kill':
            case 'shot':
                $KalanMessage = self::$Dt->LG->_('HunterShotChoice');
                $rows = self::GetPlayerNonKeyboard([$User_id], 'Kalantar_shot');
                $inline_keyboard = new InlineKeyboard(...$rows);
                $result =  Request::sendMessage([
                    'chat_id' => $User_id,
                    'text' => $KalanMessage,
                    'reply_markup' => $inline_keyboard,
                    'parse_mode' => 'HTML',
                ]);
                if($result->isOk()){
                    R::rpush($result->getResult()->getMessageId()."_".$User_id,'GamePl:MessageNightSend');
                }
                return true;
                break;
            case 'vote':

                $KalanMessage = self::$Dt->LG->_('HunterLynchedChoice');
                $rows = self::GetPlayerNonKeyboard([$User_id], 'Kalantar_shot');
                $inline_keyboard = new InlineKeyboard(...$rows);
                $result =  Request::sendMessage([
                    'chat_id' => $User_id,
                    'text' => $KalanMessage,
                    'reply_markup' => $inline_keyboard,
                    'parse_mode' => 'HTML',
                ]);
                if($result->isOk()){
                    R::rpush($result->getResult()->getMessageId()."_".$User_id,'GamePl:MessageNightSend');
                }
                return true;
                break;
        }
    }

    public static function _getLastCult(){
        $result = self::$Dt->collection->games_players->findOne([
            'team' => 'ferqeTeem'
            ,'game_id' => self::$Dt->game_id
            ,'group_id' => self::$Dt->chat_id
            ,'user_state' => 1
            ,'user_status' => 'on'
            ,'user_role' => 'role_ferqe'
        ],[
            'sort' => ['change_time' => -1],
        ]);

        if($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }

    public static function SendMessageMson($Msg){
        $result = self::$Dt->collection->games_players->find(['user_role' => 'role_feramason','user_state' => 1,'user_status'=> 'on']);
        if($result){
            $array = iterator_to_array($result);
            foreach ($array as $row){
                self::SendMessage($Msg,$row['user_id']);
            }
        }
    }
    public static function SendMasonAfterChangeRole($Name){
        $result = self::$Dt->collection->games_players->find(['user_role' => 'role_feramason','user_state' => 1,'user_status'=> 'on']);

        if($result){
            $array = iterator_to_array($result);
            $Msg = self::$Dt->LG->_('MasonConverted',array("{0}" => $Name));
            foreach ($array as $row){
                self::SendMessage($Msg,$row['user_id']);
            }
        }
    }

    public static function GetRoleRandom($not_in = []){
        $result = self::$Dt->collection->games_players->find([
            'game_id' => self::$Dt->game_id
            ,'group_id' => self::$Dt->chat_id
            ,'user_state' => 1
            ,'user_status' => 'on'
            ,'user_role' => ['$nin' => $not_in]
        ],[ 'limit' => -1 ,'skip' => mt_rand( 0, (self::_getCountPlayers()) )]);

        if($result) {
            $array = iterator_to_array($result);
            if(!isset($array['0'])){
                return self::GetRoleRandom($not_in);
            }
            return $array['0'];
        }


        return false;
    }

    public static function GetUserRandom($not_in = []){
        $result = self::$Dt->collection->games_players->find([
            'game_id' => self::$Dt->game_id
            ,'group_id' => self::$Dt->chat_id
            ,'user_state' => 1
            ,'user_status' => 'on'
            ,'user_id' => ['$nin' => $not_in]
        ],[ 'limit' => -1 ,'skip' => mt_rand( 0, (self::_getCountPlayers()) )]);

        if($result) {
            $array = iterator_to_array($result);
            if(!isset($array['0'])){
                return self::GetUserRandom($not_in);
            }
            if(in_array($array['0']['user_id'],$not_in)){
                return self::GetUserRandom($not_in);
            }
            return $array['0'];
        }


        return false;
    }

    public static function GetUserRandomNonWolf($not_in = []){
        $result = self::$Dt->collection->games_players->find([
            'game_id' => self::$Dt->game_id
            ,'group_id' => self::$Dt->chat_id
            ,'team' => ['$nin' => ['wolf']]
            ,'user_state' => 1
            ,'user_status' => 'on'
            ,'user_id' => ['$nin' => $not_in]
        ],[ 'limit' => -1 ,'skip' => mt_rand( 0, (self::_getCountPlayers()) )]);

        if($result) {
            $array = iterator_to_array($result);
            if(!isset($array['0'])){
                return self::GetUserRandom($not_in);
            }
            if(in_array($array['0']['user_id'],$not_in)){
                return self::GetUserRandom($not_in);
            }
            return $array['0'];
        }


        return false;
    }


    public static function checkTime(){

        if(R::CheckExit('GamePl:SetTimer')){
            return false;
        }

        $time = R::Get('timer');

        $l = $time -  time();


        $GameStatus = R::Get('game_state');
        switch ($GameStatus) {
            case 'night':


                if(R::CheckExit('GamePl:role_lucifer:checkLucifer')){
                    return false;
                }
                $NightTime  = R::Get('night_timer');
                $S_Time  = $NightTime / 2;
                if($l > $S_Time){
                    return false;
                }

                $SendNight = R::LRange(0,-1,'GamePl:SendNight');
                if(count($SendNight) > 0){
                    $Keys = R::LRange(0,-1,'GamePl:MessageNightSend');
                    if(count($Keys) <= 0){
                        R::GetSet( time() - 5,'timer');
                    }
                }
                break;
            case 'vote':

                $CountPlayer = R::LRange(0,-1,'GamePl:SendVote');
                $CountSends = count($CountPlayer);
                if($CountSends > 1) {
                    $Keys = R::LRange(0,-1,'GamePl:MessageNightSend');
                    if (count($Keys) <= 0 ) {
                        R::GetSet(time() - 5, 'timer');
                    }
                }
                break;
        }
    }




    public static function GetAlivePlayer(){
        $Players = self::_getOnPlayers();

        $OnTeam = [];
        $OnRole = [];

        $Count['rosta'] = 0;
        $Count['wolf'] = 0;
        $Count['ferqeTeem'] = 0;
        $Count['qatel'] = 0;
        $Count['lucifer'] = 0;
        $Count['monafeq'] = 0;
        $Count['Firefighter'] = 0;
        $Count['vampire'] = 0;
        $Count['joker'] = 0;
        $Count['black'] = 0;

        foreach ($Players as $row){


            switch ($row['user_role']){
                case 'role_WolfTolle':
                case 'role_WolfGorgine':
                case 'role_Wolfx':
                case 'role_WolfAlpha':
                case 'role_midwolf':
                    $Count['wolf']++;
                    break;
                case 'role_Qatel':
                case 'role_Archer':
                case 'role_hilda':
                    $Count['qatel']++;
                    break;
                case 'role_BlackKnight':
                case 'role_BrideTheDead':
                    $Count['black']++;
                    break;
                case 'role_forestQueen':
                    if (R::CheckExit('GamePl:role_forestQueen:AlphaDead')) {
                        $Count['wolf']++;
                    }
                    break;
                case 'role_Vampire':
                case 'role_orlok':
                case 'role_Bloodthirsty':
                case 'role_kentvampire':
                    $Count['vampire']++;
                    break;
                default:
                    if($row['user_role'] == "role_WolfJadogar" || $row['user_role'] == "role_Honey" || $row['user_role'] == "role_enchanter"){
                        $Count['rosta']++;
                    }else{
                        if(isset($Count[$row['team']])) {
                            $Count[$row['team']]++;
                        }
                    }
                    break;
            }
            array_push($OnRole,['user_id' => $row['user_id'],'link' => self::ConvertName($row['user_id'],$row['fullname_game']),'user_role'=> $row['user_role'],'team'=> $row['team']]);
            if(!in_array($row['team'],$OnTeam)){
                if($row['team'] == "wolf"){
                    $Wolf = SE::WolfRole();
                    if (R::CheckExit('GamePl:role_forestQueen:AlphaDead')) {
                        array_push($Wolf,'role_forestQueen');
                    }

                    if(in_array($row['user_role'],$Wolf)){
                        array_push($OnTeam, $row['team']);
                    }
                }elseif($row['team'] == "rosta" || $row['team'] == "ferqeTeem" || $row['team'] == "qatel" || $row['team'] == "vampire" || $row['team'] == "monafeq" || $row['team'] == "lucifer" || $row['team'] == "Firefighter"  || $row['team'] == "black"){
                    array_push($OnTeam, $row['team']);
                }
            }
        }

        return ['on_role' => $OnRole,'on_team' => $OnTeam,'Count' => $Count];
    }

    public static function GetRoleNotIn($Team){
        $result = self::$Dt->collection->games_players->find([
            'game_id' => self::$Dt->game_id
            ,'group_id' => self::$Dt->chat_id
            ,'user_state' => 1
            ,'user_status' => 'on'
            ,'team' => ['$nin' => [$Team]]
        ]);

        if($result){
            $array = iterator_to_array($result);
            if(!$array){
                return false;
            }
            return $array['0'];
        }
    }


    public static function CheckingLove(){
        $data = R::Keys('GamePl:love:*');

        $checkTeams = false;
        $checkWolfTeam = false;
        $loverHel = true;
        foreach ($data as $key){
            $ex =  explode(":",$key);
            $keyFull = "{$ex['1']}:{$ex['2']}:{$ex['3']}";
            $GetLover = R::Get($keyFull);

            if(R::Get("GamePl:user:{$GetLover}:team") == R::Get("GamePl:user:{$ex['3']}:team")){
                $checkWolfTeam = true;
            }
            if(R::CheckExit('GamePl:CheckLover:'.$ex['3']) || R::CheckExit('GamePl:CheckLover:'.$GetLover)){
                $loverHel = false;
            }

        }


        return ['count' => count($data),'wolfTeam'=> $checkWolfTeam,'heals'=>$loverHel];
    }
    public static function CheckEndGame(){


        // چک میکنیم کسی هست واسه تبدیل یا نه
        if(R::CheckExit('GamePl:EnchanterBittanPlayer')){
            return false;
        }
        if(R::CheckExit('GamePl:MidnightBittanPlayer')){
            return false;
        }

        if(R::CheckExit('GamePl:OrlokBittanPlayer')){
            return false;
        }
        // چک میکنیم کسی هست واسه تبدیل یا نه
        if(R::CheckExit('GamePl:VampireBitten')){
            return false;
        }

        // چک میکنیم کسی هست واسه تبدیل یا نه
        if(R::CheckExit('GamePl:BittanPlayer')){
            return false;
        }

        $CountPlayer = self::_getCountPlayer();
        // اگه کسی زنده نبود بازی تموم میشه
        if($CountPlayer == 0){
            return 'nothing';
        }



        $AliveTeam = self::GetAlivePlayer();

        $RoleOn = array_column($AliveTeam['on_role'], 'user_role');
        $Team = $AliveTeam['on_team'];
        $CountTeam = $AliveTeam['Count'];


        switch ($CountPlayer){
            case 0:
                return 'nothing';
                break;
            case 1:
                // چک میکنیم اگه ، جادوگر ،منافق، عجوزه و یا شیطان فقط زنده بود کسی برنده نشه
                if(in_array('role_WolfJadogar',$RoleOn) || in_array('role_monafeq',$RoleOn)  || in_array('role_Honey',$RoleOn)  || in_array('role_enchanter',$RoleOn) ){

                    // » جاوگر باشیو تنها بازمانده روستا
                    if(in_array('role_WolfJadogar',$RoleOn)){
                        HL::SavePlayerAchivment($AliveTeam['on_role'][0]['user_id'],'Time_to_retire');
                    }

                    if(in_array('role_monafeq',$RoleOn)) {
                        $MonafKey = array_keys($RoleOn,"role_kalantar"); $Monaf_key = $MonafKey['0'];$Monaf_name = $AliveTeam['on_role'][$Monaf_key]['link'];

                        $GroupMessage = self::$Dt->LG->_('TannerEnd', array("{0}" => $Monaf_name));
                        self::SaveMessage($GroupMessage);
                    }

                    return 'nothing';
                }

                $TeamOn = $AliveTeam['on_role']['0']['team'];
                return $TeamOn;

                break;
            case 2:
                if(R::CheckExit('GamePl:SweetheartLove') && in_array('role_Sweetheart',$RoleOn)){
                    return 'lover';
                }

                $checkLove = self::CheckingLove();
                if($checkLove['count'] == 2 && $checkLove['heals']){
                    return 'lover';
                }

                if(in_array('role_BlackKnight',$RoleOn) || in_array('role_BrideTheDead',$RoleOn) ){
                    $GeN = self::GetRoleNotIn('black');
                    if($GeN){
                        self::UserDead($GeN['user_id'],'black');
                    }
                    return 'black';
                }

                // چک میکنیم اگه ، منافق با جادو ... زنده بود کسی برنده نشه
                if(in_array('role_monafeq',$RoleOn) and in_array('role_WolfJadogar',$RoleOn)   || in_array('role_Honey',$RoleOn)  || in_array('role_enchanter',$RoleOn) || in_array('role_Joker',$RoleOn) || in_array('role_Harly',$RoleOn)){
                    return 'nothing';
                }

                // اگه آتیش با هرکی ب زنده موند
                if(in_array('role_Firefighter',$RoleOn)){
                    $SearchShkey = array_keys($RoleOn, "role_Firefighter");
                    $Qatel_key = $SearchShkey['0'];
                    $AnyKey = ($Qatel_key == 0 ? 1 : 0);
                    self::UserDead($AliveTeam['on_role'][$AnyKey]['user_id'],'Firefighter');
                    $Anyname = $AliveTeam['on_role'][$AnyKey]['link'];
                    $GroupMessage = self::$Dt->LG->_('FirefighterEnd',array("{0}" => $Anyname));
                    self::SaveMessage($GroupMessage);
                    return 'Firefighter';
                }
                // اگه ملکه بخ از تیم، آتیش با هرکی ب زنده موند
                if(in_array('role_Firefighter',$RoleOn)){
                    $SearchShkey = array_keys($RoleOn, "role_IceQueen");
                    $Qatel_key = $SearchShkey['0'];
                    $AnyKey = ($Qatel_key == 0 ? 1 : 0);
                    self::UserDead($AliveTeam['on_role'][$AnyKey]['user_id'],'Firefighter');
                    $Anyname = $AliveTeam['on_role'][$AnyKey]['link'];
                    $GroupMessage = self::$Dt->LG->_('IceFirefighterEnd',array("{0}" => $Anyname));
                    self::SaveMessage($GroupMessage);
                    return 'Firefighter';
                }


                // اگه ومپ اصیل آزاد بود و با کلانتر زنده مونده بود
                if(R::CheckExit('GamePl:Bloodthirsty')){
                    if(in_array('role_kalantar',$RoleOn) && in_array('role_Bloodthirsty',$RoleOn)){
                        $WolfData = self::GetRoleNotIn('vampire');$WolfName = self::ConvertName($WolfData['user_id'],$WolfData['fullname_game']);
                        $KalanKey = array_keys($RoleOn,"role_kalantar"); $Kalan_key = $KalanKey['0'];$Kalan_name = $AliveTeam['on_role'][$Kalan_key]['link'];$Kalan_id = $AliveTeam['on_role'][$Kalan_key]['user_id'];
                        // کلانتر شانسشو امتحان میکنه اگه تیر بزنه روستا میبره وگرنه برنده بازی گرگه
                        if(self::R(100) < SE::_s('HunterKillVampireChanceBase')){
                            $GroupMessage = self::$Dt->LG->_('HunterKillsVampireEnd',array("{0}" =>  $Kalan_name,"{1}" => $WolfName));
                            self::SaveMessage($GroupMessage);
                            self::UserDead($WolfData['user_id'],'shot');

                            return 'rosta';
                        }
                        $GroupMessage = self::$Dt->LG->_('VampireKillsHunterEnd',array("{0}" => $Kalan_name,"{1}" => $WolfName));
                        self::SaveMessage($GroupMessage);
                        self::UserDead($Kalan_id,'vampire');
                        return 'vampire';
                    }
                }
                // اگه ومپایر با کلانتر زنده موند
                if((in_array('role_kalantar', $RoleOn) && in_array('role_Vampire', $RoleOn)) || (in_array('role_Bloodthirsty', $RoleOn) && in_array('role_kalantar', $RoleOn))){
                    $WolfData = self::GetRoleNotIn('vampire');$WolfName = self::ConvertName($WolfData['user_id'],$WolfData['fullname_game']);
                    $KalanKey = array_keys($RoleOn,"role_kalantar"); $Kalan_key = $KalanKey['0'];$Kalan_name = $AliveTeam['on_role'][$Kalan_key]['link'];$Kalan_id = $AliveTeam['on_role'][$Kalan_key]['user_id'];
                    // کلانتر شانسشو امتحان میکنه اگه تیر بزنه روستا میبره وگرنه برنده بازی گرگه
                    if(self::R(100) < SE::_s('HunterKillVampireChanceBase')){
                        $GroupMessage = self::$Dt->LG->_('HunterKillsVampireEnd',array("{0}" => $Kalan_name,"{1}" => $WolfName));
                        self::SaveMessage($GroupMessage);
                        self::UserDead($WolfData['user_id'],'shot');
                        return 'rosta';
                    }
                    $GroupMessage = self::$Dt->LG->_('VampireKillsHunterEnd',array("{0}" =>  $Kalan_name,"{1}" => $WolfName));
                    self::SaveMessage($GroupMessage);
                    self::UserDead($Kalan_id,'vampire');
                    return 'vampire';
                }


                if(in_array('role_kalantar',$RoleOn) && in_array('wolf',$Team)){
                    $WolfData = self::GetRoleNotIn('rosta');$WolfName = self::ConvertName($WolfData['user_id'],$WolfData['fullname_game']);
                    $KalanKey = array_keys($RoleOn,"role_kalantar"); $Kalan_key = $KalanKey['0'];$Kalan_name = $AliveTeam['on_role'][$Kalan_key]['link'];$Kalan_id = $AliveTeam['on_role'][$Kalan_key]['user_id'];
                    // کلانتر شانسشو امتحان میکنه اگه تیر بزنه روستا میبره وگرنه برنده بازی گرگه
                    if(self::R(100) < SE::_s('HunterKillWolfChanceBase')){
                        $GroupMessage = self::$Dt->LG->_('HunterKillsWolfEnd',array("{0}" =>  $Kalan_name, "{1}" => $WolfName));
                        self::SaveMessage($GroupMessage);
                        self::UserDead($WolfData['user_id'],'shot');
                        return 'rosta';
                    }
                    $GroupMessage = self::$Dt->LG->_('WolfKillsHunterEnd',array("{0}" => $Kalan_name,"{1}"=> $WolfName));
                    self::SaveMessage($GroupMessage);
                    self::UserDead($Kalan_id,'eat');
                    return 'wolf';
                }
                // اگه کلانتر با قاتل زنده بود ، کلانتر تیر بزنه به قاتل، کسی برنده نمیشه
                if(in_array('role_kalantar',$RoleOn) && in_array('role_Qatel',$RoleOn)){
                    $QatelKey = array_keys($RoleOn,"role_Qatel"); $Qatel_key = $QatelKey['0'];$Qatel_name = $AliveTeam['on_role'][$Qatel_key]['link'];$Qatel_id = $AliveTeam['on_role'][$Qatel_key]['user_id'];
                    $KalanKey = array_keys($RoleOn,"role_kalantar"); $Kalan_key = $KalanKey['0'];$Kalan_name = $AliveTeam['on_role'][$Kalan_key]['link'];$Kalan_id = $AliveTeam['on_role'][$Kalan_key]['user_id'];

                    $GroupMessage = self::$Dt->LG->_('SKHunterEnd',array("{0}" => $Qatel_name,"{1}" => $Kalan_name));
                    self::SaveMessage($GroupMessage);
                    self::SavePlayerAchivment($Qatel_id,'Double_Kill');
                    self::SavePlayerAchivment($Kalan_id,'Double_Kill');
                    self::UserDead($Qatel_id,'shot');
                    self::UserDead($Kalan_id,'kill');
                    return 'nothing';
                }
                // اگه شکارچی با یه فرقه گرا زنده بمونه شکارچی فرقه رو میکشه و میبره
                if(in_array('role_shekar',$RoleOn) && in_array('role_ferqe',$RoleOn)){
                    $shekarKey = array_keys($RoleOn,"role_shekar"); $shekar_key = $shekarKey['0'];$shekar_name = $AliveTeam['on_role'][$shekar_key]['link'];$shekar_id = $AliveTeam['on_role'][$shekar_key]['user_id'];
                    $ferqeKey = array_keys($RoleOn,"role_ferqe"); $ferqe_key = $ferqeKey['0'];$ferqe_name = $AliveTeam['on_role'][$ferqe_key]['link'];$ferqe_id = $AliveTeam['on_role'][$ferqe_key]['user_id'];
                    $GroupMessage = self::$Dt->LG->_('CHKillsCultistEnd',array("{0}" => $ferqe_name,"{1}" => $shekar_name));
                    self::SaveMessage($GroupMessage);
                    self::UserDead($ferqe_id,'CultHuner');
                    return 'rosta';
                }

                if(in_array('role_Qatel',$RoleOn)){
                    $SearchShkey = array_keys($RoleOn, "role_Qatel");
                    $Qatel_key = $SearchShkey['0'];
                    $AnyKey = ($Qatel_key == 0 ? 1 : 0);
                    $Killer_name = $AliveTeam['on_role'][$Qatel_key]['link'];
                    $GroupMessage = self::$Dt->LG->_('SerialKillerWinsOverpower',array("{0}" => $Killer_name,"{1}" => $AliveTeam['on_role'][$AnyKey]['link']));
                    self::SaveMessage($GroupMessage);
                    self::UserDead($AliveTeam['on_role'][$AnyKey]['user_id'],'kill');
                    return 'qatel';
                }

                if(in_array('role_morgana',$RoleOn)){
                    $SearchShkey = array_keys($RoleOn, "role_morgana");
                    $Qatel_key = $SearchShkey['0'];
                    $AnyKey = ($Qatel_key == 0 ? 1 : 0);
                    $Killer_name = $AliveTeam['on_role'][$Qatel_key]['link'];
                    $GroupMessage = self::$Dt->LG->_('SerialKillerWinsOverpowerMorgana',array("{0}" => $Killer_name,"{1}" => $AliveTeam['on_role'][$AnyKey]['link']));
                    self::SaveMessage($GroupMessage);
                    self::UserDead($AliveTeam['on_role'][$AnyKey]['user_id'],'kill');
                    return 'qatel';
                }

                if(in_array('role_Archer',$RoleOn)){
                    $SearchShkey = array_keys($RoleOn, "role_Archer");
                    $Qatel_key = $SearchShkey['0'];
                    $AnyKey = ($Qatel_key == 0 ? 1 : 0);

                    self::UserDead($AliveTeam['on_role'][$AnyKey]['user_id'],'kill');
                    return 'qatel';
                }
                if(in_array('role_hilda',$RoleOn)){
                    $SearchShkey = array_keys($RoleOn, "role_hilda");
                    $Qatel_key = $SearchShkey['0'];
                    $AnyKey = ($Qatel_key == 0 ? 1 : 0);

                    self::UserDead($AliveTeam['on_role'][$AnyKey]['user_id'],'kill');
                    return 'qatel';
                }
                if(in_array('wolf',$Team)){
                    $GeN = self::GetRoleNotIn('wolf');
                    if($GeN) {
                        self::UserDead($GeN['user_id'], 'eat');
                    }
                    return 'wolf';
                }
                if(in_array('ferqeTeem',$Team)){
                    $CountTeams = self::CountRole('role_ferqe');
                    if($CountTeams == 2){
                        return 'ferqeTeem';
                    }
                    $SearchShkey = array_keys($RoleOn, "role_ferqe");
                    if(isset($SearchShkey['0'])) {
                        $Ferqe_key = $SearchShkey['0'];
                        $AnyKey = ($Ferqe_key == 0 ? 1 : 0);
                        self::ConvertPlayer($AliveTeam['on_role'][$AnyKey]['user_id'], 'role_ferqe');
                        return 'ferqeTeem';
                    }else{
                        return 'ferqeTeem';
                    }
                }
                break;
            default:
                break;
        }


        if(in_array('role_Joker',$RoleOn) || in_array('role_Harly',$RoleOn)){
            if(R::CheckExit('GamePl:FindedBook')){
                if((int) R::Get('GamePl:FindedBook') >= 3){
                    return 'joker';
                }
            }elseif($CountPlayer <= 3){
                return 'joker';
            }
        }


        // اگه قاتل بود بازی تموم نمیشه مسلما
        if(in_array('qatel',$Team)){
            // اگر کماندار با قاتل بود
            if(in_array('role_Qatel',$RoleOn)  || in_array('role_Archer',$RoleOn) ||  in_array('role_morgana',$RoleOn)){
                // اگر تعداد تیم قاتل برابر یا مساوی با بقییه تیم ها بود
                if($CountTeam['qatel'] >= $CountTeam['wolf']+$CountTeam['rosta']+$CountTeam['ferqeTeem']+$CountTeam['Firefighter']+$CountTeam['vampire']+$CountTeam['black'] +$CountTeam['monafeq']){
                    // چک میکنیم اگه شب بعد کماندار تیر داره و تیم قاتل برابر با بقییه تیم هاست تیم قاتل میبره
                    $ArcherSend = R::Get('GamePl:ArcherSendFor');
                    $Night_now = R::Get('GamePl:Night_no') + 1;
                    if($ArcherSend == $Night_now){

                        return 'qatel';
                    }
                }
            }
            return false;
        }


        if($CountTeam['black'] >= ($CountTeam['rosta']+$CountTeam['monafeq']) && $CountTeam['Firefighter'] == 0 && $CountTeam['wolf'] == 0 && $CountTeam['vampire'] == 0 && $CountTeam['ferqeTeem'] == 0 ){
            return 'black';
        }
        if(in_array('role_BlackKnight',$RoleOn) || in_array('role_BrideTheDead',$RoleOn)){
            return false;
        }

        if($CountTeam['Firefighter'] >= ($CountTeam['rosta']+$CountTeam['monafeq']) && $CountTeam['wolf'] == 0 && $CountTeam['vampire'] == 0 && $CountTeam['black'] == 0 && $CountTeam['ferqeTeem'] == 0 ){
            return 'Firefighter';
        }


        // اگه پادشاه آتش بود بازی تموم نمیشه مسلما
        if(in_array('role_Firefighter',$RoleOn)){
            return false;
        }
        // اگه پادشاه آتش بود بازی تموم نمیشه مسلما
        if(in_array('role_IceQueen',$RoleOn)){
            return false;
        }

        if($CountTeam['ferqeTeem'] > 0 && $CountTeam['Firefighter'] == 0 && $CountTeam['monafeq'] == 0 && $CountTeam['rosta'] == 0 && $CountTeam['black'] == 0 && $CountTeam['wolf'] == 0 &&  $CountTeam['vampire'] == 0){
            return 'ferqeTeem';
        }

        // اگه فقط یه تیم موند اون تیم برندست
        if(count($Team) == 1){
            return $Team['0'];
        }


        if($CountTeam['wolf'] >= ($CountTeam['rosta']+$CountTeam['ferqeTeem']+$CountTeam['Firefighter']+$CountTeam['vampire']+$CountTeam['monafeq']+$CountTeam['black'])){
            $checkLove = self::CheckingLove();
            if($checkLove['count'] == 2 and $checkLove['heals']) {
                $TeamLove = $checkLove['wolfTeam'];
                $TotalOnTeam = $CountTeam['rosta']+$CountTeam['ferqeTeem']+$CountTeam['Firefighter']+$CountTeam['vampire']+$CountTeam['monafeq'];
                if (in_array('role_tofangdar', $RoleOn) && R::CheckExit('GamePl:GunnerBult')) {
                    if(($TotalOnTeam == $CountTeam['wolf']) || ($TotalOnTeam + 1 == $CountTeam['wolf']  && $TeamLove)){
                        return false;
                    }
                }
            }

            return 'wolf';
        }

        if($CountTeam['vampire'] >= ($CountTeam['rosta']+$CountTeam['Firefighter']+$CountTeam['monafeq']) && $CountTeam['black'] == 0 && $CountTeam['wolf'] == 0 && $CountTeam['ferqeTeem'] == 0){
            return 'vampire';
        }

        if($CountTeam['wolf'] == 0 && $CountTeam['Firefighter'] == 0 && $CountTeam['black'] == 0 && $CountTeam['ferqeTeem'] == 0  && $CountTeam['vampire'] == 0 && $CountTeam['Firefighter'] == 0  && $CountTeam['rosta'] > 0){
            return 'rosta';
        }

        return false;
    }


    public static function GameEndMessage($Winner){

        $WinnerTeam = $Winner;

        switch ($WinnerTeam){
            case 'rosta':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_rosta'),
                    'caption' => self::$Dt->LG->_('winner_rosta'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'vf':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_vf'),
                    'caption' => self::$Dt->LG->_('winner_vf'),
                    'parse_mode' => 'HTML',
                ]);
                break;
                case 'black':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_black'),
                    'caption' => self::$Dt->LG->_('winner_black'),
                    'parse_mode' => 'HTML',
                ]);
                break;
                case 'joker':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_joker'),
                    'caption' => self::$Dt->LG->_('winner_joker'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'ferqeTeem':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_ferqe'),
                    'caption' => self::$Dt->LG->_('winner_ferqeTeem'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'wolf':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_wolf'),
                    'caption' => self::$Dt->LG->_('winner_wolf'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'nothing':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('nothing'),
                    'caption' => self::$Dt->LG->_('winner_nothing'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'qatel':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_qatel'),
                    'caption' => self::$Dt->LG->_('winner_qatel'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'lover':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_lover'),
                    'caption' => self::$Dt->LG->_('winner_lover'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'monafeq':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_trap'),
                    'caption' => self::$Dt->LG->_('winner_monafeq'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'Firefighter':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_firefighter'),
                    'caption' => self::$Dt->LG->_('win_Firefighter'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'vampire':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_vampire'),
                    'caption' => self::$Dt->LG->_('win_vampire'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'SweetheartLove':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_lover'),
                    'caption' => self::$Dt->LG->_('winner_lover'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            default:
                return true;
                break;
        }
    }

    public static function CheckLover($user_id,$Team){
        if(R::CheckExit('GamePl:love:'.$user_id)){

            $love= R::Get('GamePl:love:'.$user_id);
            $Lover = R::Get('GamePl:love:'.$love);

            $loveTeam = SE::GetRoleTeam(R::Get('GamePl:user:'.$Lover.":role"));
            $loverTeam = SE::GetRoleTeam(R::Get('GamePl:user:'.$love.":role"));

            if($loveTeam == $Team || $loverTeam == $Team || $Team == "lover"){
                return true;
            }
            return false;
        }
        return false;
    }


    public static function TopPlayer($UserRole,$TopPlayers,$isOn,$Win){
        $AllPlayer = self::_getCountPlayers();
        $PlayerOn = self::_getCountPlayer();
        $TopRole = SE::_GetTop($UserRole);

        $TopPlayer = ($AllPlayer / $PlayerOn) * $Win + $TopRole + $isOn;
        $Top = (is_nan(floor($TopPlayer)) ? : floor($TopPlayer));
        $TopEnd = ($Win == 1 ? $TopPlayers + $Top : $TopPlayers - $Top);
        $TopEnd = (is_nan($TopEnd) ? 0 : $TopEnd);

        return ['TopUser' => ($Win == 1 ?  "+$Top" :  "-$Top"),'TotalTop' => $TopEnd];
    }

    public static function AddPlayerAchio($user_id,$AchioKey){

    }

    public static function CheckPlayerAchio($user_id,$AchioKey){
        //achievement
    }

    public static function GetLevelUPUser($xp){
        $FXp = (int) $xp;

        $Level = 1;
        if($FXp > 1000 && $FXp < 2000){
            $Level = 2;
        }elseif($FXp > 2000 && $FXp < 4000){
            $Level = 3;
        }elseif($FXp > 4000 && $FXp < 7000){
            $Level = 4;
        }elseif($FXp > 7000 && $FXp < 11000){
            $Level = 5;
        }elseif($FXp > 11000 && $FXp < 16000){
            $Level = 6;
        }elseif($FXp > 16000 && $FXp < 22000){
            $Level = 7;
        }elseif($FXp > 22000 && $FXp < 29000){
            $Level = 8;
        }elseif($FXp > 29000 && $FXp < 37000){
            $Level = 9;
        }elseif($FXp > 37000 && $FXp < 46000){
            $Level = 10;
        }elseif($FXp > 46000 && $FXp < 51000){
            $Level = 11;
        }elseif($FXp > 51000 && $FXp < 57000){
            $Level = 12;
        }elseif($FXp > 57000 && $FXp < 64000){
            $Level = 13;
        }elseif($FXp > 64000 && $FXp < 72000){
            $Level = 14;
        }elseif($FXp > 72000 && $FXp < 77000){
            $Level = 15;
        }elseif($FXp > 77000 && $FXp < 83000){
            $Level = 16;
        }elseif($FXp > 83000 && $FXp < 90000){
            $Level = 17;
        }elseif($FXp > 90000 && $FXp < 98000){
            $Level = 18;
        }elseif($FXp > 98000 && $FXp < 107000){
            $Level = 19;
        }elseif($FXp > 107000 && $FXp < 112000){
            $Level = 20;
        }elseif($FXp > 112000 && $FXp < 118000){
            $Level = 21;
        }elseif($FXp > 118000 && $FXp < 125000){
            $Level = 22;
        }elseif($FXp > 125000 && $FXp < 132000){
            $Level = 23;
        }elseif($FXp > 132000 && $FXp < 140000){
            $Level = 24;
        }elseif($FXp > 140000 && $FXp < 148000){
            $Level = 25;
        }elseif($FXp > 148000 && $FXp < 157000){
            $Level = 26;
        }elseif($FXp > 157000 && $FXp < 162000){
            $Level = 27;
        }elseif($FXp > 162000 && $FXp < 168000){
            $Level = 28;
        }elseif($FXp > 168000 && $FXp < 176000){
            $Level = 29;
        }elseif($FXp > 176000 && $FXp < 230000){
            $Level = 30;
        }elseif($FXp > 230000 && $FXp < 340000){
            $Level = 31;
        }elseif($FXp > 340000 && $FXp < 440000){
            $Level = 32;
        }elseif($FXp > 440000 && $FXp < 560000){
            $Level = 33;
        }elseif($FXp > 560000 && $FXp < 670000){
            $Level = 34;
        }elseif($FXp > 670000 && $FXp < 790000){
            $Level = 35;
        }elseif($FXp > 790000 && $FXp < 880000){
            $Level = 36;
        }elseif($FXp > 880000 && $FXp < 990000){
            $Level = 37;
        }elseif($FXp > 990000 && $FXp < 1010000){
            $Level = 38;
        }elseif($FXp > 1010000 && $FXp < 1025000){
            $Level = 39;
        }elseif($FXp > 1025000 && $FXp < 1065000 ){
            $Level = 40;
        }elseif($FXp > 1065000 && $FXp < 1095000 ){
            $Level = 41;
        }elseif($FXp > 1095000 && $FXp < 1105000 ){
            $Level = 42;
        }elseif($FXp > 1105000 && $FXp < 1109000 ){
            $Level = 43;
        }elseif($FXp > 1109000 && $FXp < 1110000 ){
            $Level = 44;
        }elseif($FXp > 1110000 && $FXp < 1120000 ){
            $Level = 45;
        }elseif($FXp > 1120000 && $FXp < 1128000 ){
            $Level = 46;
        }elseif($FXp > 1128000 && $FXp < 1138000 ){
            $Level = 47;
        }elseif($FXp > 1138000 && $FXp < 1148000 ){
            $Level = 48;
        }elseif($FXp > 1148000 && $FXp < 1168000 ){
            $Level = 48;
        }elseif($FXp > 1168000 && $FXp < 1178000 ){
            $Level = 49;
        }elseif($FXp > 1178000  ){
            $Level = 50;
        }

        return $Level;
    }
    public static function SaveUserState($Detial,$Win,$AllPl){
        $Win = ($Win == "win" ? 1 : 0);
        $Los = ($Win == "lost" ? 1 : 0);
        $IsOn = ($Detial['user_state'] == 1 ? 1 : 0);

        $result = self::$Dt->collection->Players->findOne(['user_id' => (float) $Detial['user_id']]);
        if($result) {
            $array = iterator_to_array($result);


            $UserLevel = (!is_numeric($array['Site_Username']) ? 1 : $array['Site_Username']) ;
            $UserXp = (!is_numeric($array['Site_Password']) ? 0 : $array['Site_Password']) ;
            $Xp = 50;
            $plus = 0;
            $LevelPlus = 1.2;


            if($Win){

                switch ($Detial['team']){
                    case 'rosta':
                        $plus = 25;
                        break;
                    case 'ferqeTeem':
                        $plus = 35;
                        break;
                    case 'qatel':
                        $plus = 40;
                        break;
                    case 'wolf':
                        $plus = 30;
                        break;
                    case 'Firefighter':
                        $plus = 50;
                        break;
                    case 'vampire':
                        $plus = 45;
                        break;
                }

            }


            if($UserLevel  < 10 and  $UserLevel > 5){
                $LevelPlus = 2.1;
            }elseif($UserLevel  < 15 and  $UserLevel > 10){
                $LevelPlus = 3.3;
            }elseif($UserLevel  < 20 and  $UserLevel > 15){
                $LevelPlus = 4.2;
            }elseif($UserLevel  < 25 and  $UserLevel > 20){
                $LevelPlus = 5.1;
            }elseif($UserLevel  < 30 and  $UserLevel > 25){
                $LevelPlus = 6;
            }

            $finalXp = (round($Xp) + round($plus)) * $LevelPlus;

            $FXp = round($UserXp + $finalXp);
            $UserLevelN = self::GetLevelUPUser($FXp);

            if($UserLevelN > $UserLevel){
                $LAng = self::$Dt->L->_('NewLevel',array("{0}" => self::$Dt->L->_('level_'.$UserLevel),"{1}" => self::$Dt->L->_('level_'.$UserLevelN)));
                HL::SendMessage($LAng,$Detial['user_id']);
            }

            //$TopPlayer = self::TopPlayer($Detial['user_role'],$array['top'],$IsOn,$Win);

            //  $UserIdles = $TopPlayer['TopUser'];

            //  $T_Top=  round(($TopPlayer['TotalTop'] < 0 ? 0 : $TopPlayer['TotalTop']));
            //   if($T_Top > 0) {
            //  $Lang = ($UserIdles < 0 ? "امتیاز کم شد  {$UserIdles}  امتیاز کلی کنونی {$T_Top}" : "امتیاز اضافه شد  {$UserIdles}  امتیاز کلی کنونی {$T_Top}");
            //    R::rpush($Lang, 'UserIdles:' . $Detial['user_id']);
            // }

            // در یک بازی 10 نفره یا بیشتر با نقش مست زنده بماند.
            if($AllPl >= 10 && $Detial['user_role'] == "role_Mast" && $Detial['user_state'] == 1){
                self::SavePlayerAchivment($Detial['user_id'],'Wobble_Wobble');
            }
            // در یک بازی 20 نفره یا بیشتر از 20نفره زنده بمانید و یک رای هم نگیرید
            if($AllPl >= 20 && R::CheckExit('GamePl:VoteList:'.$Detial['user_id']) == false && $Detial['user_state'] == 1){
                self::SavePlayerAchivment($Detial['user_id'],'Inconspicuous');
            }


            $GameMode = R::Get('GamePl:gameModePlayer');
            if($GameMode == "Mighty"){
                self::SavePlayerAchivment($Detial['user_id'],'Welcome_to_the_Asylum');
            }
            if($AllPl == 5){
                self::SavePlayerAchivment($Detial['user_id'],'Introvert');
            }
            if($AllPl == 35){
                self::SavePlayerAchivment($Detial['user_id'],'Enochlophobia');
            }
            if($array['total_game'] + 1 > 0){
                self::SavePlayerAchivment($Detial['user_id'],'Welcome_to_Hell');
            }
            if($array['total_game'] + 1 == 100){
                self::SavePlayerAchivment($Detial['user_id'],'Dedicated');
            }
            if($array['total_game'] + 1 == 1000){
                self::SavePlayerAchivment($Detial['user_id'],'Obsessed');
            }
            if(R::CheckExit('GamePl:AmirKarimiInGame')){
                self::SavePlayerAchivment($Detial['user_id'],'Just_a_Beareen_Teams');
            }
            // توی 100 بازی زنده بمونید
            if($array['SurviveTheGame'] + $IsOn == 100){
                self::SavePlayerAchivment($Detial['user_id'],'Survivalist');
            }

            // در نقش ناتاشا 5 شب به خونه 5 بازیکن متفاوت و سیف بروید و خونه نمانید (اسکیپ نزنید)
            if(R::CheckExit('GamePl:VisitSafeCountFaheshe') && $Detial['user_role'] == "role_faheshe"){
                if(R::Get('GamePl:VisitSafeCountFaheshe') >= 5 ){
                    self::SavePlayerAchivment($Detial['user_id'],'Promiscuous');
                }
            }

            // یکی از حداقل دو ماسون باقی مانده در یک بازی باشید
            if($Detial['user_role'] == "role_feramason" && $Detial['user_state'] == 1){
                $GetTeam = self::PlayerByTeam();
                if(count($GetTeam['Fermason']) >= 2){
                    self::SavePlayerAchivment($Detial['user_id'],'Mason_Brother');
                }
            }

            // تغییر نقش دو بار در یک بازی- تبدیل فرقه شمارش نمی شود
            if(R::CheckExit("GamePl:UserXchangeRole:{$Detial['user_id']}")){
                if(R::Get("GamePl:UserXchangeRole:{$Detial['user_id']}") >= 2){
                    self::SavePlayerAchivment($Detial['user_id'],'Double_Shifter');
                }
            }

            $CountCount = ($Win ? $AllPl * 10 : $AllPl * 4);

            self::$Dt->collection->Players->updateOne(
                ['user_id' => (float) $Detial['user_id']],
                ['$set' => [
                    'coin' => ((isset($array['coin']) ? $array['coin'] : 0) +  $CountCount),
                    'total_game' => $array['total_game'] + 1
                    ,'LoserGames'=> $array['LoserGames'] + $Los
                    ,'SlaveGames' => $array['SlaveGames'] + $Win
                    , 'SurviveTheGame' => $array['SurviveTheGame'] + $IsOn
                    //, 'top' => ($TopPlayer['TotalTop'] < 0 ? 0 : $TopPlayer['TotalTop'])
                    , 'Site_Username' => $UserLevelN
                    , 'Site_Password' => $FXp
                ]]
            );

            return $array['total_game'];
        }
    }

    public static function SendListEndGame($Winner){
        $Players = self::_getPlayers();
        $Re = [];
        $NobesCount = 0;
        $AllPlayer = self::_getCountPlayers();
        $PlayerOn = self::_getCountPlayer();
        $winners = [];

        foreach ($Players as $row){
            //شرکت در یک بازی که هیچ برنده ای ندارد
            if($Winner == "nothing"){
                self::SavePlayerAchivment($row['user_id'],'Death_Village');
            }


            if($row['user_state'] == 1) {
                // ثبت زمان بازی کردن کاربر
                $RNo = R::NoPerfix();
                $TimeGame = time() - R::Get('GamePl:StartedTime');
                $left_times = ($RNo->exists('userGameTime:'.$row['user_id']) ? $RNo->get('userGameTime:'.$row['user_id']) : 0);
                $RNo->set('userGameTime:' . $row['user_id'], $left_times + $TimeGame);
            }

            $CheckWin =  (self::CheckLover($row['user_id'],$Winner) == true ? "win" : ($row['user_role'] == "role_Hamzad" ? "lost" : ($row['user_state'] == 2 ? "smite" :  ($row['team'] == $Winner ? "win" : "lost"))));
            if($row['user_role'] == "role_dozd"){
                $CheckWin = "win";
            }
            if($row['user_role'] == "role_Watermelon"){
                $CheckWin = "win";
            }
            if($CheckWin == 'win'){
                array_push($winners,$row['user_id']);
            }


            // از اول بازی فرقه گرا باشی و زنده بمونی و ببری
            if(($row['user_role'] == "role_ferqe" && !R::CheckExit('GamePl:ChangedUserRole:' . $row['user_id']) && $row['user_state'] == 1 && $CheckWin == "win") || ($row['user_role'] == "role_Royce" && !R::CheckExit('GamePl:ChangedUserRole:' . $row['user_id']) && $row['user_state'] == 1 && $CheckWin == "win")){
                self::SavePlayerAchivment($row['user_id'],'Cult_Leader');
            }

            $WinOrLost = ($CheckWin == "win" ? self::$Dt->LG->_('winner') : ($CheckWin == "lost" ? self::$Dt->LG->_('loset') : self::$Dt->LG->_('is_smited')));
            $OnOrDead = ($row['user_state'] == 1 ? self::$Dt->LG->_('is_on')."-" : self::$Dt->LG->_('is_dead')."-");
            $Lover = (R::CheckExit('GamePl:love:'.$row['user_id'])  ? "❤️" : "");


            $UserRole = (R::Get('expose_role') == "all" ? "*".($row['user_role'] !== "" ? self::$Dt->LG->_($row['user_role']."_n") : "Error")."*" : "");
            $Name =  self::ConvertName($row['user_id'],$row['fullname_game'],true);
            $End = $Name.": ".$Lover." ".$OnOrDead.$UserRole." ".$WinOrLost;
            $CountGame = self::SaveUserState($row,$CheckWin,$AllPlayer);
            if($CountGame < 50){
                $NobesCount++;
            }

            // » بازی 35نفر رو با نقش قاتل زنجیره ای پیروز بشید
            if($Winner == "qatel" && $row['user_role'] == "role_Qatel" && $row['user_state'] == 1 && $AllPlayer >= 35 ){
                HL::SavePlayerAchivment($row['user_id'],'Psychopath_Killer');
            }
            array_push($Re,$End);
        }

        $GameMode = R::Get('GamePl:gameModePlayer');
        if($GameMode == 'coin'){
            $AllCoin = ((int) $AllPlayer  * 5000) - 500;
            $countWinner = count($winners);
            $FonPlayerOnCoin = round($AllCoin / $countWinner);
            foreach($winners as $user_id){
                $result = self::$Dt->collection->Players->findOne(['user_id' => (float)$user_id]);
                if($result) {
                    $array = iterator_to_array($result);
                    self::UpdateCoin((((int) $array['coin']) + ((int) $FonPlayerOnCoin)), $user_id);
                    Request::sendMessage([
                        'chat_id' => $user_id,
                        'text' => self::$Dt->L->_('WinCoinEndGame', array("{0}" => $FonPlayerOnCoin)),
                        'disable_web_page_preview' => 'true',
                        'parse_mode' => 'HTML'
                    ]);
                }
            }

        }

        $TimeToLeft = (time() - R::Get('GamePl:StartedTime'));

        $GroupMessage = self::$Dt->LG->_('endGame',array("{0}" =>"{$PlayerOn}/$AllPlayer","{1}" => implode(PHP_EOL,$Re),"{2}" => gmdate("G:i:s", $TimeToLeft)));

        self::SendMessage($GroupMessage,false,false,true);


        // ذخیره اطلاعات بازی گروه
        self::GroupStats(['game_time' => $TimeToLeft,'player_count' => $AllPlayer ,'nobes_player' => $NobesCount]);

        self::SaveGameEndData($Players,$AllPlayer);


        return $Re;
    }
    public static function FindUserId($id){
        $result = self::$Dt->collection->Players->findOne(['user_id' => (float) $id]);
        if ($result) {
            $array = iterator_to_array($result);
            return $array;
        }
        return false;
    }
    public static function GroupStats($Data){
        $afked = (int) (R::CheckExit('GamePl:AfkedPlayer') ? R::Get('GamePl:AfkedPlayer') : 0);
        self::$Dt->collection->group_stats->insertOne([
            'group_id'       =>    self::$Dt->chat_id,
            'group_name'     =>    R::Get('group_name'),
            'game_mode'      =>    self::$Dt->game_mode,
            'group_lang'     =>    self::$Dt->def_lang,
            'game_id'        =>    self::$Dt->game_id,
            'player_count'   =>    $Data['player_count'],
            'game_time'      =>     floor($Data['game_time'] / 60),
            'nobes_Player'   =>    $Data['nobes_player'],
            'afked_player'   =>    round((($afked > 0 ? $afked : 1) / $Data['player_count'])),
            'm_date'         =>    date('Y-m-d H:i:s'),
            'k_date'         =>    jdate('Y-m-d'),
            'k_time'         =>    jdate('H:i:s'),
            'time'           =>    time()
        ]);
    }

    public static function GamedEnd($Winner){

        R::GetSet(true,'GamePl:GameIsEnd');
        // اگه پیامی بود ویرایش میکنیم
        self::EditMarkupKeyboard();
        // اگه پیامی بود بفرست
        self::SendGroupMessage(false);
        // پیام بردن رو بفرست تو گروه
        self::GameEndMessage($Winner);
        // لیست برد و باخت رو هم میفرستیم تو بازی
        self::SendListEndGame($Winner);
        self::GroupClosedThGame();

    }

    public static function SaveKillWolf($wolfTeam,$Detial){

        foreach ($wolfTeam as $row){
                HL::SaveGameActivity($Detial,'eat',['user_id'=> $row['user_id'],'fullname'=> $row['Link']]);
        }
    }
    public static function SaveKillVampire($vampireTeam,$Detial){

        foreach ($vampireTeam as $row){
                HL::SaveGameActivity($Detial,'vampire',['user_id'=> $row['user_id'],'fullname'=> $row['Link']]);
        }
    }
    public static function SaveVoteKillVote($Array,$Detial){
        if(is_array($Array)){
            foreach ($Array as $row){
                if(!empty($row['name']) && $row['user_id']) {
                    HL::SaveGameActivity($Detial, 'vote_kill', ['user_id' => $row['user_id'], 'fullname' => $row['name']]);
                }
            }
        }
    }


    public static function SaveKill($killer,$kill,$for){
        $result = self::$Dt->collection->kills->count(['killer' => (float) $killer , 'game_id' => self::$Dt->game_id]);
        if($result == 0) {
            self::$Dt->collection->kills->insertOne([
                'killer'          =>    $killer,
                'kill'            =>    $kill,
                'for'             =>    $for,
                'game_id'         =>    self::$Dt->game_id,
                'time'            =>    time(),
                'group_id'        =>    self::$Dt->chat_id
            ]);
        }
    }

    public static function GetRoleUserId($role){
        $result = self::$Dt->collection->games_players->findOne(['user_role' => $role,'group_id'=> self::$Dt->chat_id,'game_id'=> self::$Dt->game_id,'user_state'=> 1,'user_status' => "on"]);
        if($result) {
            $array = iterator_to_array($result);
            return $array['user_id'];
        }
        false;
    }

    public static function LoverBYSweetheart($User_id,$team){
        $Detial = self::_getPlayer($User_id);
        $U_name = self::ConvertName($Detial['user_id'],$Detial['fullname_game']);
        $Sweetheart = self::_getPlayerByRole('role_Sweetheart');


        if(R::CheckExit('GamePl:love:'.$Detial['user_id'])){
            $LoverId = R::Get('GamePl:love:'.$Detial['user_id']);
            $LoverDetial = self::_getPlayer($LoverId);
            if($LoverDetial['user_state'] == 1) {
                R::GetSet(true,'GamePl:CheckLover:'.$LoverId);
                $LoverName = self::ConvertName($LoverDetial['user_id'], $LoverDetial['fullname_game']);
                $GroupMessage = self::$Dt->LG->_('MsgGroupDeadLastLove', array("{0}" => $LoverName, "{1}" => self::$Dt->LG->_('user_role', array("{0}" => self::$Dt->LG->_($LoverDetial['user_role'] . "_n")))));
                self::SaveMessage($GroupMessage);
                self::UserDead($LoverId, 'Sweetheart');
                R::Del('GamePl:love:'.$LoverId);
                HL::SaveGameActivity($LoverDetial,'love_dead',$Detial);
                $MessagePlayer = self::$Dt->LG->_('MsgPlayerDeadLastLove');
                self::SendMessage($MessagePlayer, $LoverId);
                $SweetheartMessage = self::$Dt->LG->_('MsgSweetHeartLastLoveDead', array("{0}" => $LoverName));
                self::SendMessage($SweetheartMessage, $Detial['user_id']);
            }
        }

        if(R::CheckExit('GamePl:love:'.$Sweetheart['user_id'])){
            $LoverId = R::Get('GamePl:love:'.$Sweetheart['user_id']);
            $LoverDetial = self::_getPlayer($LoverId);
            if($LoverDetial['user_state'] == 1) {
                R::GetSet(true,'GamePl:CheckLover:'.$LoverId);
                $LoverName = self::ConvertName($LoverDetial['user_id'], $LoverDetial['fullname_game']);
                $GroupMessage = self::$Dt->LG->_('MsgGroupDeadLastLove',  array("{0}" => $LoverName,"{1}" => self::$Dt->LG->_('user_role',  array("{0}" => self::$Dt->LG->_($LoverDetial['user_role'] . "_n")))));
                self::SaveMessage($GroupMessage);
                self::UserDead($LoverId, 'Sweetheart');
                R::Del('GamePl:love:'.$LoverId);
                HL::SaveGameActivity($LoverDetial,'love_dead',$Sweetheart);
                $MessagePlayer = self::$Dt->LG->_('MsgPlayerDeadLastLove');
                self::SendMessage($MessagePlayer, $LoverId);
                $SweetheartMessage = self::$Dt->LG->_('MsgSweetHeartLastLoveDead', array("{0}" => $LoverName));
                self::SendMessage($SweetheartMessage, $Sweetheart['user_id']);
            }
        }


        HL::SaveGameActivity(['user_id' => $Sweetheart['user_id'],'fullname'=>$Sweetheart['fullname'] ],'love',$Detial);
        HL::SaveGameActivity($Detial,'love',['user_id' => $Sweetheart['user_id'],'fullname'=>$Sweetheart['fullname'] ]);


        R::GetSet($User_id,'GamePl:love:'.$Sweetheart['user_id']);
        R::GetSet($Sweetheart['user_id'],'GamePl:love:'.$User_id);

        R::GetSet($User_id,'GamePl:SweetheartLove');
        R::GetSet($team,'GamePl:SweetheartLove:team');
        R::GetSet($U_name,'GamePl:SweetheartLove:name');
        R::GetSet(true,'GamePl:SweetheartLove:'.$User_id);
        R::GetSet(self::$Dt->LG->_('user_role',self::$Dt->LG->_($Detial['user_role']."_n")),'GamePl:SweetheartLove:role');
        $SweetheartMessage = self::$Dt->LG->_('MsgLoveSweetHeart',array("{0}" => $U_name));
        self::SendMessage($SweetheartMessage,$Sweetheart['user_id']);

        return true;
    }


    public static function CheckSmite(){
        $data = R::LRange(0,-1,'GamePl:SmitePlayer');
        if($data){
            foreach ($data as $user_id){
                $Player = self::_getPlayer($user_id);
                if($Player){
                    $name = self::ConvertName($Player['user_id'],$Player['fullname_game']);
                    $role = self::$Dt->LG->_('user_role',array("{0}" => self::$Dt->LG->_($Player['user_role']."_n")));
                    $Lang = self::$Dt->LG->_('PlayerFlee',array("{0}" => $name,"{1}" => $role));
                    self::UserDead($Player,'smite');
                    self::SendMessage($Lang);
                }
            }
            R::Del('GamePl:SmitePlayer');
        }
    }


    public static function VampireConvert($user_id){
        R::GetSet($user_id,'GamePl:VampireBitten');
    }


    public static function SavePlayerAchivment($user_id,$achive_code){
        $result = self::$Dt->collection->achievement_player->findOne(['user_id' => $user_id]);
        if(!$result) {
            self::$Dt->collection->achievement_player->insertOne([
                'user_id' => $user_id,
                'achievements' => [$achive_code],
            ]);
            $AchMessage = self::$Dt->L->_('AchioUnlock').PHP_EOL;
            $AchMessage .= self::$Dt->L->_($achive_code).PHP_EOL;
            $AchMessage .= self::$Dt->L->_($achive_code."_dic");
            HL::SendMessage($AchMessage, $user_id);
            return true;
        }

        $updateResult = self::$Dt->collection->achievement_player->updateOne(array("user_id" => $user_id, 'achievements' => ['$nin' => [$achive_code]]), array('$push' => array("achievements" => $achive_code)));
        if($updateResult->getMatchedCount() > 0){
            $AchMessage = self::$Dt->L->_('AchioUnlock').PHP_EOL;
            $AchMessage .= self::$Dt->L->_($achive_code).PHP_EOL;
            $AchMessage .= self::$Dt->L->_($achive_code."_dic");
            HL::SendMessage($AchMessage, $user_id);
        }

        return false;
    }


    public static function InsertMedal($user_id,$MedalIcon,$info){
        HL::SendMessage($info, $user_id);
        self::$Dt->collection-> PlayerStateMedal ->insertOne([
            'user_id'       =>    $user_id,
            'medal'     =>    $MedalIcon,
            'medal_info'      =>    $info,
        ]);
    }
    public static function SaveGameActivity($d,$actvity,$to){
        self::$Dt->collection->   game_activity ->insertOne([
            'chat_id'       =>    self::$Dt->chat_id,
            'game_id'     =>     self::$Dt->game_id,
            'player_id'      =>   $d['user_id'],
            'player_name' =>      $d['fullname'],
            'actvity'   => $actvity,
            'to'   => $to['user_id'],
            'to_name' => $to['fullname'],
            'm_date'         =>    date('Y-m-d H:i:s'),
            'jdate'          => jdate('Y-m-d H:i:s')
        ]);
    }

    public static function SaveGameEndData($players,$count_player){
        self::$Dt->collection-> group_states ->insertOne([
            'chat_id'       =>    self::$Dt->chat_id,
            'game_id'     =>     self::$Dt->game_id,
            'player_id'      =>   $players,
            'count_player'   => $count_player,
            'm_date'         =>    date('Y-m-d H:i:s'),
            'jdate'          => jdate('Y-m-d H:i:s')
        ]);
    }

    public static function GetUserForLove($not){
        $player = self::GetUserRandom([$not]);

    }


    public static function GetNextGame(){
        $result = self::$Dt->collection->next_game->findOne(['chat_id' => self::$Dt->chat_id]);

        if($result){
            $array = iterator_to_array($result);
            if($array['users']){
                return $array['users'];
            }

            return false;
        }

        return false;
    }

    public static function DeleteNextList(){
        self::$Dt->collection->next_game->deleteOne(['chat_id' => self::$Dt->chat_id]);
    }

    public static function SendPrincessMessage($user,$Princess){
        $U_name = self::ConvertName($user['user_id'],$user['fullname_game']);

        switch ($user['user_role']){
            case 'role_Vampire':
                $VampireMessage = self::$Dt->LG->_("PrincessPrisonerVampireTeam",array("{0}" => $U_name));
                self::SendForVampireTeam($VampireMessage,$user['user_id'],'prince_zd');
            break;
            case 'role_ferqe':
            case 'role_Royce':
                $CultMessage = self::$Dt->LG->_("PrincessPrisonerCultTeam",array("{0}" => $U_name));
                self::SendForCultTeam($CultMessage,$user['user_id'],'prince_zd');
              break;
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_Wolfx':
            case 'role_WolfAlpha':
                $WolfMessage = self::$Dt->LG->_("PrincessPrisonerWolfTeam",array("{0}" => $U_name));
                self::SendForWolfTeam($WolfMessage,$user['user_id'],'prince_zd');
            break;
            case 'role_Qatel':
                $Hilda = HL::_getPlayerByRole('role_hilda');
                if($Hilda) {
                    $HildaMessage = self::$Dt->LG->_("PrincessPrisonerHilda", array("{0}" => $U_name));
                    self::SendMessage($HildaMessage,$Hilda['user_id'],'prince_zd');
                }
                $Archer = HL::_getPlayerByRole('role_Archer');
                if($Archer) {
                    $ArcherMessage =  self::$Dt->LG->_("PrincessPrisonerKillerArcher", array("{0}" => $U_name));
                    self::SendMessage($ArcherMessage,$Archer['user_id'],'prince_zd');
                }
           break;
        }

        $PlayerMessage = self::$Dt->LG->_('PrincessPrisoner');
        self::SendMessage($PlayerMessage,$user['user_id'],'prince_zd');
        R::GetSet(true,'GamePl:PrincessPrisoner:'.$user['user_id']);
        $PrincessMessage = self::$Dt->LG->_('PrincessPrisonerSuccess',array("{0}" => $U_name));
        HL::SendMessage($PrincessMessage,$Princess['user_id']);
        return true;
    }

     public static  function GetMadosaMessage($Detial){
         $Name = self::ConvertName($Detial['user_id'],$Detial['fullname_game']);
         switch ($Detial['user_role']){
             case 'role_shekar':
                 $GroupMessage = self::$Dt->LG->_('MadosaCultHunter',array("{0}" => $Name));
                 self::SaveMessage($GroupMessage);
                 self::UserDead($Detial,'madosa');
                 return true;
             break;
             case 'role_faheshe':
                 $GroupMessage = self::$Dt->LG->_('MadosaFaheshe',array("{0}" => $Name));
                 self::SaveMessage($GroupMessage);
                 self::UserDead($Detial,'madosa');
                 return true;
                 break;
             case 'role_WolfTolle':
             case 'role_WolfGorgine':
             case 'role_Wolfx':
             case 'role_WolfAlpha':
             case 'role_forestQueen':
             case 'role_WhiteWolf':
                 $GroupMessage = self::$Dt->LG->_('MadosaWolf',array("{0}" => $Name));
                 self::SaveMessage($GroupMessage);
                 self::UserDead($Detial,'madosa');
                 return true;
                 break;
             case 'role_ferqe':
             case 'role_Royce':
             $GroupMessage = self::$Dt->LG->_('MdosaCult',array("{0}" => $Name));
                 self::SaveMessage($GroupMessage);
                 self::UserDead($Detial,'madosa');
                 return true;
                 break;
             case 'role_Vampire':
             case 'role_Bloodthirsty':
             case 'role_kentvampire':
                 $GroupMessage = self::$Dt->LG->_('MadosaVampire',array("{0}" => $Name));
                 self::SaveMessage($GroupMessage);
                 self::UserDead($Detial,'madosa');
                 return true;
                 break;
             case 'role_Knight':
                 $GroupMessage = self::$Dt->LG->_('MadosaKnight',array("{0}" => $Name));
                 self::SaveMessage($GroupMessage);
                 self::UserDead($Detial,'madosa');
                 return true;
                 break;
                 case 'role_Qatel':
                 $GroupMessage = self::$Dt->LG->_('MadosaKiller',array("{0}" => $Name));
                 self::SaveMessage($GroupMessage);
                 self::UserDead($Detial,'madosa');
                 return true;
                 break;
             case 'role_Fereshte':
                 $GroupMessage = self::$Dt->LG->_('MadosaFereshte',array("{0}" => $Name));
                 self::SaveMessage($GroupMessage);
                 self::UserDead($Detial,'madosa');
                 return true;
                 break;
             case 'role_hilda':
                 $GroupMessage = self::$Dt->LG->_('MadosaHilda',array("{0}" => $Name));
                 self::SaveMessage($GroupMessage);
                 self::UserDead($Detial,'madosa');
                 return true;
                 break;
         }
    }
    
    public static function checkUserINPrisoner($Detial){
        if(!R::CheckExit('GamePl:PrincessPrisoner:'.$Detial['user_id'])) return false;
        return  true;
    }
    public static function CheckPhoenixHeal($Detial){
        if(!R::CheckExit('GamePl:PhoenixHealer:'.$Detial['user_id'])) return false;
        return  true;
    }
    public static function GetPlayer($user_id){
        $user_id = (float) $user_id;
        $result = self::$Dt->collection->Players->findOne(['user_id'=>  $user_id]);
        if($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }
}
