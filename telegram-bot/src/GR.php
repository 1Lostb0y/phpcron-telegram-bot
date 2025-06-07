<?php

namespace phpcron\CronBot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;

class GR
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


    public static function is_url($uri)
    {
        if (preg_match('/^(http|https|t.me|telegram.me):\\/\\/[a-z0-9_]+([\\-\\.]{1}[a-z_0-9]+)*\\.[_a-z]{2,5}' . '((:[0-9]{1,5})?\\/.*)?$/i', $uri)) {
            return $uri;
        } else {
            return false;
        }
    }


    public static function GetGroupSe($key)
    {
        if (!$key) {
            return false;
        }
        $get = RC::Get($key);
        return $get ?? "Unknown";
    }

    public static function ChangeConfig($val, $key)
    {
        if ($key == "role_Vampire") {
            RC::GetSet($val, "role_Bloodthirsty");
        }
        if ($key == "role_Bloodthirsty") {
            RC::GetSet($val, "role_Vampire");
        }
        if ($key == "role_kalantar" && $val == "off") {
            RC::GetSet($val, "role_Vampire");
            RC::GetSet($val, "role_Bloodthirsty");
        }
        if ($key == "role_ferqe") {
            RC::GetSet($val, "role_shekar");
            RC::GetSet($val, "role_Royce");
        }
        if ($key == "role_shekar") {
            RC::GetSet($val, "role_ferqe");
            RC::GetSet($val, "role_Royce");
        }
        if ($key == "role_Royce" && $val == "on" ) {
            RC::GetSet($val, "role_shekar");
            RC::GetSet($val, "role_ferqe");
        }
        if ($key == "role_IceQueen"){
            RC::GetSet($val, "role_Firefighter");
        }
        if ($key == "role_Firefighter"){
            RC::GetSet($val, "role_IceQueen");
        }
        if ($key == "role_Qatel" && $val == "off"){
            RC::GetSet($val, "role_Archer");
        }
        if ($key == "role_Archer" && $val == "on"){
            RC::GetSet($val, "role_Qatel");
        }

        switch ($key){
            case 'role_WolfJadogar':
            case 'role_Honey':
            case 'role_enchanter':
            case 'role_WhiteWolf':
            case 'role_forestQueen':
            case 'role_Khaen':
            case 'role_NefrinShode':
                if(!self::CheckWolfOn()){
                    RC::GetSet("off", $key);
                    return   Request::sendMessage([
                        'chat_id' => self::$Dt->user_id,
                        'text' =>  self::$Dt->L->_('pleaseEnableOneWolf'),
                        'parse_mode' => 'HTML',
                    ]);
                }
                break;
            case 'role_WolfAlpha':
            case 'role_WolfTolle':
            case 'role_Wolfx':
            case 'role_WolfGorgine':
                RC::GetSet($val, $key);

                if(!self::CheckWolfOn()){
                    RC::GetSet("off", "role_WolfJadogar");
                    RC::GetSet("off", "role_Honey");
                    RC::GetSet("off", "role_enchanter");
                    RC::GetSet("off", "role_WhiteWolf");
                    RC::GetSet("off", "role_forestQueen");
                    RC::GetSet("off", "role_Khaen");
                    RC::GetSet("off", "role_NefrinShode");
                }

                break;
        }



        RC::GetSet($val, $key);
    }


    public static function CheckWolfOn(){
        $Check = false;

        if(RC::Get('role_WolfAlpha') == "on" || RC::Get('role_WolfTolle') == "on" || RC::Get('role_Wolfx') == "on"   || RC::Get('role_WolfGorgine') == "on"  ){
            $Check = true;
        }

        return $Check;
    }


    public static function CheckGPGameState()
    {
        $cns = self::$Dt->collection->challenge_game;
        $checkStartChallenge = $cns->count(['group_id' => self::$Dt->chat_id]);
        if ($checkStartChallenge > 0) {
            $CheckGameStatus1 = $cns->findOne(['group_id' => self::$Dt->chat_id]);
            $array = iterator_to_array($CheckGameStatus1);
            if ($array['game_status'] == "join") {
                return 3;
            }
            return 4;
        }

        $cn = self::$Dt->collection->games;
        $checkStartAsGame = $cn->count(['group_id' => self::$Dt->chat_id]);
        if ($checkStartAsGame > 0) {
            $CheckGameStatus = $cn->findOne(['group_id' => self::$Dt->chat_id]);
            $array = iterator_to_array($CheckGameStatus);
            if ($array['game_status'] == "join") {
                return 2;
            }
            return 1;
        } else {
            return 0;
        }
    }

    public static function StartGameForGroup()
    {

        RC::DelKey('GamePl:*');

        $cn = self::$Dt->collection->games;
        RC::GetSet(self::$Dt->LG->_('OnlyJoinTheGameTime', '%(timer)s'), 'GamePl:ToLeftTimer');
        RC::GetSet(self::$Dt->LG->_('Join_Message', '%(timer)s'), 'GamePl:userJoinLang');
        RC::GetSet(self::$Dt->LG->_('Seconds'), 'GamePl:STxt');
        RC::GetSet(self::$Dt->LG->_('minutes'), 'GamePl:mTxt');
        RC::GetSet(self::$Dt->game_id, 'GamePl:game_id');
        $join = new InlineKeyboard(
            [
                ['text' => self::$Dt->LG->_('joinToGame'), 'url' => self::$Dt->JoinLink]
            ]

        );
        RC::GetSet($join, 'GamePl:JoinKeyboard', 'json');
        RC::GetSet('join', 'game_state');
        RC::GetSet(1, 'GamePl:Day_no');
        RC::GetSet(0, 'GamePl:Night_no');
        RC::GetSet(0, 'GamePl:ArcherSendFor');
        RC::GetSet(0, 'GamePl:KnightSendFor');
        RC::GetSet((time() + (int)(RC::Get('join_timer') ?? 90)), 'timer');
        RC::GetSet(self::$Dt->user_link, 'GamePl:StarterName');

        $cn->insertOne([
            'group_id' => self::$Dt->chat_id,
            'game_id' => self::$Dt->game_id,
            'game_status' => 'join',
            'starter' => self::$Dt->fullname,
            'starter_id' => self::$Dt->user_id,
            'group_name' => RC::Get('group_name'),
            'game_mode' => self::$Dt->GroupGameMode,
            'def_lang' => self::$Dt->GroupDefLang,
            'timer' => time(),
            'force_at' => 0,
            'StartAt' => jdate('Y-m-d H:i:s'),
            'StartAtGMT' => date('Y-m-d H:i:s'),
            'EndAt' => jdate('Y-m-d H:i:s'),
            'EndAtGMT' => date('Y-m-d H:i:s'),
        ]);

    }

    public static function CheckNameInGame()
    {
        $cn = self::$Dt->collection->games_players;
        $count = $cn->count(['fullname' => self::$Dt->fullname, 'game_id' => self::$Dt->game_id, 'group_id' => self::$Dt->chat_id]);

        return $count;
    }

    public static function CountPlayer($group_id = false)
    {

        $chat_id = self::$Dt->chat_id;
        if($group_id){
            $chat_id = $group_id;
        }
        $result = self::$Dt->collection->join_user->findOne(['chat_id' => $chat_id]);

        if($result) {
            $array = iterator_to_array($result);
            return count($array['users']);
        }
        return 0;
    }

    public static function CheckGameId()
    {
        $cn = self::$Dt->collection->games;
        $count = $cn->count(['game_id' => self::$Dt->game_id, 'game_status' => 'join']);
        return $count;
    }

    public static function BotAddedToGroup()
    {
        $cn = self::$Dt->collection->groups;
        $count = $cn->count(['chat_id' => self::$Dt->chat_id]);
        if ($count == 0) {
            $cn->insertOne([
                'chat_id' => self::$Dt->chat_id,
                'group_name' => self::$Dt->groupName,
                'addedById' => self::$Dt->user_id,
                'addedByName' => self::$Dt->fullname,
                'group_link' => null,
                'group_in_list' => 1,
                'group_in_live' => 1,
                'group_point' => 0,
                'group_status' => 'off',
                'group_state' => 1,
                'added_on' => jdate('Y-m-d H:i:s'),
                'added_onGMT' => date('Y-m-d H:i:s'),
            ]);
            RC::GetSet('general', 'game_mode');
            RC::GetSet('fa', 'lang');
            RC::GetSet(self::$Dt->groupName, 'group_name');
            RC::GetSet('onr', 'role_fool');
            RC::GetSet('offr', 'role_hypocrite');
            RC::GetSet('onr', 'role_Cult');
            RC::GetSet('onr', 'role_Lucifer');
            RC::GetSet(90, 'day_timer');
            RC::GetSet(90, 'night_timer');
            RC::GetSet(90, 'vote_timer');
            RC::GetSet(90, 'secret_timer');
            RC::GetSet(90, 'join_timer');
            RC::GetSet(60, 'max_extend_timer');
            RC::GetSet('offr', 'cult_hunter_expose_role');
            RC::GetSet(2, 'cultHunter_NightShow');
            RC::GetSet('offr', 'randome_mode');
            RC::GetSet('offr', 'secret_vote');
            RC::GetSet('onr', 'secret_vote_count');
            RC::GetSet('offr', 'secret_vote_name');
            RC::GetSet('Normal', 'type_mode');
            RC::GetSet('all', 'expose_role');
            RC::GetSet('onr', 'expose_role_after_dead');
            RC::GetSet('offr', 'show_user_id');
            RC::GetSet('onr', 'allow_flee');
            RC::GetSet(35, 'max_player');
            RC::GetSet('offr', 'allow_extend');
        }
    }

    public static function UnlockAllRole(){
        if(!RC::CheckExit('SetUpRoles')) {
            RC::GetSet(true, 'SetUpRoles');
        }
        RC::GetSet("on",'role_rosta');
        RC::GetSet("on",'role_feramason');
        RC::GetSet("on",'role_pishgo');
        RC::GetSet("on",'role_karagah');
        RC::GetSet("on",'role_tofangdar');
        RC::GetSet("on",'role_rishSefid');
        RC::GetSet("on",'role_Gorgname');
        RC::GetSet("on",'role_Nazer');
        RC::GetSet("on",'role_Hamzad');
        RC::GetSet("on",'role_Huntsman');
        RC::GetSet("on",'role_kalantar');
        RC::GetSet("on",'role_Fereshte');
        RC::GetSet("on",'role_Ahangar');
        RC::GetSet("on",'role_KhabGozar');
        RC::GetSet("on",'role_Khaen');
        RC::GetSet("on",'role_Kadkhoda');
        RC::GetSet("on",'role_Mast');
        RC::GetSet("on",'role_Vahshi');
        RC::GetSet("on",'role_Shahzade');
        RC::GetSet("on",'role_Qatel');
        RC::GetSet("on",'role_PishRezerv');
        RC::GetSet("on",'role_PesarGij');
        RC::GetSet("on",'role_NefrinShode');
        RC::GetSet("on",'role_Solh');
        RC::GetSet("on",'role_ahmaq');
        RC::GetSet("on",'role_Royce');
        RC::GetSet("on",'role_faheshe');
        RC::GetSet("on",'role_ngativ');
        RC::GetSet("on",'role_WolfJadogar');
        RC::GetSet("on",'role_trouble');
        RC::GetSet("on",'role_Firefighter');
        RC::GetSet("on",'role_IceQueen');
        RC::GetSet("on",'role_Spy');
        RC::GetSet("on",'role_Ruler');
        RC::GetSet("on",'role_Honey');
        RC::GetSet("on",'role_Knight');
        RC::GetSet("on",'role_forestQueen');
        RC::GetSet("on",'role_enchanter');
        RC::GetSet("on",'role_Archer');
        RC::GetSet("on",'role_Vampire');
        RC::GetSet("on",'role_Bloodthirsty');
        RC::GetSet("on",'role_WolfTolle');
        RC::GetSet("on",'role_WolfGorgine');
        RC::GetSet("on",'role_Wolfx');
        RC::GetSet("on",'role_WolfAlpha');
        RC::GetSet("on",'role_WhiteWolf');
        RC::GetSet("on",'role_forestQueen');
        RC::GetSet("on",'role_trouble');
        RC::GetSet("on",'role_Huntsman');
        RC::GetSet("on",'role_Sweetheart');
        RC::GetSet("on",'role_shekar');
        RC::GetSet("on",'role_ferqe');
        RC::GetSet("on",'role_elahe');
        RC::GetSet("on",'role_monafeq');
        RC::GetSet("on",'role_lucifer');
    }
    public static function CheckPlayerInGame()
    {
        $cn = self::$Dt->collection->games_players;
        $count = $cn->count(['user_id' => self::$Dt->user_id, 'user_state' => 1]);
        return $count;
    }

    public static function ConvertName($user_id, $name)
    {

        return '<a href="tg://user?id=' . $user_id . '">' . $name . '</a>';
    }

    public static function UpdatePlayerList($UpNow = false)
    {
        $cn = self::$Dt->collection->games_players;
        $re = [];
        $result = $cn->find(['group_id' => self::$Dt->chat_id, 'game_id' => self::$Dt->game_id], [
            'sort' => ['join_time' => 1],
        ]);

        foreach ($result as $item) {
            $name = self::ConvertName($item['user_id'], $item['fullname_game']);
            array_push($re, $name);
        }

        $countPlayer = self::CountPlayer();
        $Res = self::$Dt->LG->_('players', array("{0}" => $countPlayer,"{1}" => implode(PHP_EOL, $re)));
        RC::GetSet($Res, 'GamePl:Player_list');

        if (RC::CheckExit('GamePl:time_update') == false && $UpNow == false) {
            RC::GetSet(time(), 'GamePl:time_update');
            RC::Ex(5, 'GamePl:time_update');
            RC::GetSet(time(), 'GamePl:UserJoin');
            RC::Ex(5, 'GamePl:UserJoin');

        }

        return $Res;
    }

    public static function JoinUserSet($name,$user_id){
        $result = self::$Dt->collection->join_user->findOne(['chat_id' => self::$Dt->chat_id]);
        if(!$result) {
            self::$Dt->collection->join_user->insertOne([
                'chat_id' => self::$Dt->chat_id,
                'users' => [['user_id' => $user_id ,'name' => $name]],
            ]);
            return true;
        }

        self::$Dt->collection->join_user->updateOne(array("chat_id"=>self::$Dt->chat_id),array('$push' => [ 'users'=> ['user_id' => $user_id ,'name' => $name]] ));
        return false;
    }

    public static function CheckPlayerJoined($user_id = false){
        if($user_id){
            $user_id = self::$Dt->user_id;
        }else{
            $user_id = self::$Dt->user_id;
        }
        $result = self::$Dt->collection->games_players->findOne(['group_id' => self::$Dt->chat_id,'user_id'=>  $user_id]);
        return $result;
    }

    public static function GetMedl($medl){

        if($medl > 60000){
            return '💎';
        }

        if($medl > 40000){
            return '🏆';
        }

        if($medl > 20000){
            return '✨';
        }

        if($medl > 15000){
            return '🎖';
        }
        if($medl > 10000){
            return '🎗';
        }

        if($medl > 6000){
            return '🏅';
        }

        if($medl > 4000){
            return '🥇';
        }

        if($medl > 2000){
            return '🥈';
        }

        if($medl > 300){
            return '🥉';
        }

        return '';
    }
    public static function PlayerJoinTheGame()
    {

        $time = RC::Get('timer');
        $leftTime = $time - time();
        if ($leftTime <= 10) {
            self::$Dt->text = 30;
            self::ExtendToGame();
        }

        $cn = self::$Dt->collection->games_players;
        $user_id = (RC::Get('show_user_id') == 'offr') ? '' : "  (ID: " . self::$Dt->user_id . ")";
        $NoP = RC::NoPerfix();
        $Medl = "";
        if ($NoP->exists('userGameTime:' . self::$Dt->user_id)) {
            $GameTime = floor($NoP->get('userGameTime:' . self::$Dt->user_id) / 60);
            $Medl = ($GameTime >= 1000 ? "🥇" : ($GameTime >= 800 ? "🥈" : ($GameTime >= 500 ? "🥉" : "")));
        }
        $PlayerData = self::$Dt->Player;
        $GbAdmin = [630127836];
        // $Love = [1091592857];
        $Vip = (in_array(self::$Dt->user_id, $GbAdmin) ? " 💎" : "");//.(in_array(self::$Dt->user_id, $Love) ? " 💜" : "");

        $PlayerEmoji = (isset($PlayerData['ActivePhone']) && $PlayerData['ActivePhone'] !== 0 ? $PlayerData['ActivePhone'] : "");


        $user = self::ConvertName(self::$Dt->user_id, self::$Dt->fullname) . "{$Medl} {$PlayerEmoji} {$Vip} " . $user_id;
        self::JoinUserSet($user,self::$Dt->user_id);
        $fullnames = htmlspecialchars(self::$Dt->fullname) . "{$Medl} {$PlayerEmoji} {$Vip}";
        $cn->insertOne([
            'group_id' => self::$Dt->chat_id,
            'game_id' => self::$Dt->game_id,
            'user_id' => self::$Dt->user_id,
            'username' => self::$Dt->username,
            'fullname' => self::$Dt->fullname,
            'fullname_game' => $fullnames,
            'user_state' => 1,
            'dead_time' => 0,
            'change_time' => 0,
            'user_status' => 'on',
            'user_role' => null,
            'team' => null,
            'join_at' => jdate('Y-m-d H:i:s'),
            'join_time' => time(),
        ]);

        RC::rpush($user, 'GamePl:NewUserJoin');

        self::UpdatePlayerList();

    }

    public static function Addtest($name, $id)
    {

        $time = RC::Get('timer');
        $leftTime = $time - time();
        if ($leftTime <= 10) {
            self::$Dt->text = 30;
            self::ExtendToGame();
        }

        $NoP = RC::NoPerfix();
        $Medl = "";
        if ($NoP->exists('userGameTime:' . $id)) {
            $GameTime = $NoP->get('userGameTime:' . $id);
            $GameTime = floor($GameTime / 60);
            $Medl = ($GameTime > 800 ? "🥇" : $GameTime > 500 ? "🥈" : $GameTime > 150 ? "🥉" : "");
        }
        $GbAdmin = [630127836];
        $Vip = (in_array($id, $GbAdmin) ? " 💎" : "");

        $cn = self::$Dt->collection->games_players;
        $user_id = (RC::Get('show_user_id') == 'offr') ? '' : "  (ID: " . $id . ")";
        $user = self::ConvertName($id, $name) . $user_id;
        RC::GetSet($user, 'GamePl:join_user:' . $id);
        $cn->insertOne([
            'group_id' => self::$Dt->chat_id,
            'game_id' => self::$Dt->game_id,
            'user_id' => $id,
            'fullname' => $name,
            'fullname_game' => $name . "{$Medl}{$Vip}",
            'user_state' => 1,
            'dead_time' => 0,
            'user_status' => 'on',
            'user_role' => null,
            'team' => null,
            'selected_user' => 0,
            'dont_vote' => 0,
            'vote' => 0,
            'join_at' => jdate('Y-m-d H:i:s'),
            'join_time' => time(),
        ]);

        RC::rpush($user, 'GamePl:NewUserJoin');

        self::UpdatePlayerList();
    }

    public static function SaveVoteMessage($Name)
    {

        $MeLink = self::$Dt->user_link;

        $Msg = (RC::CheckExit('GamePl:role_Ruler:RulerOk') ? self::$Dt->LG->_('RulerVoteMessage', array("{0}" => $MeLink, "{1}" => $Name) ): self::$Dt->LG->_('voteUser', array("{0}" => $MeLink, "{1}" => $Name)));
        RC::rpush($Msg, 'GamePl:VoteMessage');


        if (RC::CheckExit('GamePl:role_Ruler:RulerOk')) {
            RC::GetSet(0, 'timer');
        }
        if (RC::CheckExit('GamePl:Update_vote') == false && RC::CheckExit('GamePl:role_Ruler:RulerOk') == false) {
            RC::GetSet(true, 'GamePl:Update_vote');
            RC::Ex((RC::Get('secret_vote') == "onr" ? 4 : 1), 'GamePl:Update_vote');
        }


    }

    public static function SaveVoteMessageDodge($Name, $DodName)
    {

        $MeLink = $DodName;

        $Msg = (RC::CheckExit('GamePl:role_Ruler:RulerOk') ? self::$Dt->LG->_('RulerVoteMessage', array("{0}" => $MeLink, "{1}" => $Name)) : self::$Dt->LG->_('voteUser', array("{0}" =>  $MeLink, "{1}"=> $Name)));
        RC::rpush($Msg, 'GamePl:VoteMessage');

        if (RC::CheckExit('GamePl:role_Ruler:RulerOk')) {
            RC::GetSet(0, 'timer');
        }
        if (RC::CheckExit('GamePl:Update_vote') == false && RC::CheckExit('GamePl:role_Ruler:RulerOk') == false) {
            RC::GetSet(true, 'GamePl:Update_vote');
            RC::Ex((RC::Get('secret_vote') == "onr" ? 4 : 2), 'GamePl:Update_vote');
        }

    }


    public static function ExtendToGame()
    {
        if (self::$Dt->text > RC::Get('max_extend_timer')) {
            self::$Dt->text = RC::Get('max_extend_timer');
        }
        $times = RC::Get('timer') + self::$Dt->text;
        $MxT = $times - time();
        if ($MxT < 10) {
            self::$Dt->text = 10;
            $times = RC::Get('timer') + self::$Dt->text;
            $MxT = $times - time();
        }
        if ($MxT > RC::Get('join_timer')) {
            $times = time() + RC::Get('join_timer');
        }

        $re = $times;
        RC::GetSet($re, 'timer');

        return ['extTime' => self::$Dt->text, 'ToLeft' => gmdate("i:s", $re - time())];
    }

    public static function UserFlee()
    {

        $time = RC::Get('timer');
        $leftTime = $time - time();
        if ($leftTime <= 10) {
            self::$Dt->text = 30;
            self::ExtendToGame();
        }

        self::SaveGameActivity(['user_id' => self::$Dt->user_id ,'fullname' => self::$Dt->fullname] ,'flee' ,['user_id' => 0 ,'fullname' => 0]);
        self::$Dt->collection->join_user->updateOne(array("chat_id"=>self::$Dt->chat_id),array('$pull' => array("users" => ['user_id' => self::$Dt->user_id])));

        self::$Dt->collection->games_players->deleteOne(['group_id' => self::$Dt->chat_id, 'game_id' => self::$Dt->game_id, 'user_id' => self::$Dt->user_id]);
        self::UpdatePlayerList(true);
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


    public static function _GetPlayer($Id)
    {
        $result = self::$Dt->collection->games_players->findOne(['group_id' => self::$Dt->chat_id, 'game_id' => self::$Dt->game_id, 'user_id' => (float)$Id]);
        if ($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }


    public static function _GetCountTeam($Team)
    {
        $count = self::$Dt->collection->games_players->count(['team' => $Team, 'game_id' => self::$Dt->game_id, 'group_id' => self::$Dt->chat_id, 'user_state' => 1, 'user_status' => 'on']);
        return $count;
    }

    public static function _GetByTeam($Team)
    {
        $result = self::$Dt->collection->games_players->find(['team' => $Team, 'game_id' => self::$Dt->game_id, 'group_id' => self::$Dt->chat_id, 'user_state' => 1, 'user_status' => 'on']);
        if ($result) {
            $re = [];
            $array = iterator_to_array($result);
            foreach ($array as $Key => $row) {
                switch ($Team) {
                    case 'wolf':
                        $wolfRole = SE::WolfRole();
                        if (RC::CheckExit('GamePl:role_forestQueen:AlphaDead')) {
                            array_push($wolfRole, 'role_forestQueen');
                        }
                        if (in_array($row['user_role'], $wolfRole)) {
                            $re[] = $array[$Key];
                        }
                        break;
                    case 'vampire':
                        $Vamp_role = ['role_Vampire'];
                        if (RC::CheckExit('GamePl:Bloodthirsty')) {
                            array_push($Vamp_role, 'role_Bloodthirsty');
                        }
                        if (in_array($row['user_role'], $Vamp_role)) {
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

    public static function SendForWolfTeam($msg, $sendMe = false)
    {
        $no_in = ($sendMe = true ? [self::$Dt->user_id] : []);
        $user = self::_GetByTeam('wolf');
        if ($user) {
            foreach ($user as $row) {
                if (!in_array($row['user_id'], $no_in)) {
                    Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => $msg,
                        'parse_mode' => 'HTML',
                    ]);
                }
            }
        }
    }


    public static function SendForVampireTeam($msg, $sendMe = false)
    {
        $no_in = ($sendMe = true ? [self::$Dt->user_id] : []);
        $user = self::_GetByTeam('vampire');
        if ($user) {
            foreach ($user as $row) {
                if (!in_array($row['user_id'], $no_in)) {
                    Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => $msg,
                        'parse_mode' => 'HTML',
                    ]);
                }
            }
        }
    }

    public static function SendForCultTeam($msg, $sendMe = false)
    {
        $no_in = ($sendMe = true ? [self::$Dt->user_id] : []);
        $user = self::_GetByTeam('ferqeTeem');
        if ($user) {
            foreach ($user as $row) {
                if (!in_array($row['user_id'], $no_in)) {
                    Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => $msg,
                        'parse_mode' => 'HTML',
                    ]);
                }
            }
        }
    }

    public static function _getOnPlayers()
    {
        $result = self::$Dt->collection->games_players->find(
            ['group_id' => self::$Dt->chat_id, 'game_id' => self::$Dt->game_id, 'user_state' => 1, 'user_status' => 'on']);
        $array = iterator_to_array($result);
        return $array;
    }

    public static function GetPlayerNonKeyboard($d, $callBack)
    {
        $player = self::_getOnPlayers();
        $re = [];
        foreach ($player as $row) {
            if (!in_array($row['user_id'], $d)) {
                $re[] = [
                    ['text' => $row['fullname'], 'callback_data' => "{$callBack}/" . self::$Dt->chat_id . "/{$row['user_id']}"]
                ];
            }
        }
        switch ($callBack) {
            case 'VoteSelect':
            case 'NightSelect_Hamzad':
            case 'NightSelect_Vahshi':
            case 'NightSelect_Cupe':
                break;
            default:
                $re[] = [
                    ['text' => "skip", 'callback_data' => "skip" . "/" . self::$Dt->chat_id . "/" . $row['user_id']]
                ];
                break;
        }
        return $re;
    }

    public static function GetAchievemntPlayer($user_id)
    {
        $result = self::$Dt->collection->achievement_player->findOne(['user_id' => (float)$user_id]);
        if($result) {

            return count($result['achievements']);
        }
        return 0;
    }

    public static function GetStats($user_id)
    {
        $result = self::$Dt->collection->Players->findOne(['user_id' => (float)$user_id]);
        if ($result) {
            $NoP = RC::NoPerfix();


            $Total_Were = 0;
            /*
            $Win_Were = 0;
            $SalvedWere = 0;
            $LostWere = 0;
            if ($NoP->exists('user:stats:' . $user_id)) {
                $StateWere = $NoP->get('user:stats:' . $user_id);
                preg_match_all('!\d+!', $StateWere, $matches);
                if($matches) {
                    $Total_Were = (isset($matches['0']['0']) ? $matches['0']['0'] : 0);
                    $Win_Were =   (isset($matches['0']['1']) ? $matches['0']['1'] : 0);
                    $LostWere =    (isset($matches['0']['2']) ? $matches['0']['2'] : 0);
                    $SalvedWere =  (isset($matches['0']['3']) ? $matches['0']['3'] : 0);
                }else{
                    $Total_Were = 0;
                    $Win_Were = 0;
                    $LostWere = 0;
                    $SalvedWere = 0;
                }
            }
            */


            $array = iterator_to_array($result);
            if ($array['total_game'] == 0 && $Total_Were == 0) {
                return false;
            }

            $Medl = "";
            $GameTime = 0;
            if ($NoP->exists('userGameTime:' . $user_id)) {
                $GameTime = floor($NoP->get('userGameTime:' . $user_id) / 60);
                $Medl = ($GameTime >= 1000 ? "🥇" : ($GameTime >= 800 ? "🥈" : ($GameTime >= 500 ? "🥉" : "")));
            }


            $UseRLeve = (is_numeric($array['Site_Username']) ? $array['Site_Username'] : 1);

            $KillYou = self::GetYouKill($user_id);

            $KillsName = ($KillYou ? self::_GetPlayerName($KillYou['0']['_id']) : $array['fullname']);
            $KillsCount = ($KillYou ? $KillYou['0']['count'] : 0);


            $KillMe = self::GetKillLastId($user_id);
            $KillmeName = ($KillMe ? self::_GetPlayerName($KillMe['0']['_id']) : $array['fullname']);
            $KillMeCount = ($KillMe ? $KillMe['0']['count'] : 0);

            $Medal = self::_GetPlayerMedal($user_id);
            $MedalUser = "";
            if ($Medal) {
                $MedalUser = self::$Dt->L->_('MedalInfo', array("{0}" => $Medal ) );
            }
            $TotalGame =  $array['total_game']; // $Total_Were +
            $SurviveTheGame = $array['SurviveTheGame']; // + $SalvedWere
            $LostGame =  $array['LoserGames']; // $LostWere +
            $WinGame = $array['SlaveGames']; //  + $Win_Were
            $Achievemnt = self::GetAchievemntPlayer($user_id);
            $SlaveGamesPerc = round(($WinGame * 100) / $TotalGame) . "%";
            $LoserGamesPerc = round(($LostGame * 100) / $TotalGame) . "%";
            $SurviveTheGamePerc = round(($SurviveTheGame * 100) / $TotalGame) . "%";

            $array = array(
                "{0}" =>  "tg://user?id={$array['user_id']}"
            ,"{1}" => $array['fullname'] . " " . $Medl .($result['ActivePhone'] !== 0 ? $result['ActivePhone'] : "")
            ,"{2}" => $Achievemnt
            ,"{3}" => $WinGame
            ,"{4}" => $SlaveGamesPerc
            ,"{5}" => $LostGame
            ,"{6}" => $LoserGamesPerc
            ,"{7}" => $SurviveTheGame
            ,"{8}" => $SurviveTheGamePerc
            ,"{9}" => $TotalGame
            ,"{10}" => $KillsCount
            ,"{11}" => $KillsName
            ,"{12}" => $KillMeCount
            ,"{13}" => $KillmeName
            ,"{14}" => $GameTime
            ,"{15}" => self::$Dt->L->_('level_'.$UseRLeve) . $MedalUser

            );
            $Lang = self::$Dt->L->_('StateUser',$array);
            return $Lang;
        }

        return false;
    }

    public static function _GetPlayerMedal($user_id)
    {
        $result = self::$Dt->collection->PlayerStateMedal->find(['user_id' => (float)$user_id]);
        if ($result) {
            $array = iterator_to_array($result);
            $Column = array_column($array, 'medal');
            return implode('   ', $Column);
        }

        return false;
    }

    public static function _GetPlayerName($user_id)
    {
        $result = self::$Dt->collection->Players->findOne(['user_id' => (float)$user_id]);
        if ($result) {
            $array = iterator_to_array($result);
            return $array['fullname'];
        }

        return false;
    }

    public static function GetPlayer($user_id)
    {
        $result = self::$Dt->collection->Players->findOne(['user_id' => (float)$user_id]);
        if ($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }

    public static function GetScore()
    {
        $result = self::$Dt->collection->Players->find(['top' => ['$gt' => 0]], [
            'limit' => 30,
            'sort' => ['top' => -1]
        ]);
        if ($result) {
            $array = iterator_to_array($result);
            $Re = [];
            foreach ($array as $Key => $row) {
                $Key = $Key + 1;
                $T = $Key . ". ";
                $T .= self::ConvertName($row['user_id'], $row['fullname']);
                $T .= " (" . round($row['top']) . ")";
                ($Key == 1 ? $T .= "🥇" : ($Key == 2 ? $T .= "🥈" : ($Key == 3 ? $T .= "🥉" : "")));
                (self::$Dt->user_id == $row['user_id'] ? $T .= self::$Dt->L->_('You') : "");

                array_push($Re, $T);
            }
            $Me = array_column($array, 'user_id');


            $Lang = self::$Dt->L->_('list_Score', array("{0}" => jdate('Y-m-d H:i:s'))) . PHP_EOL;
            $Lang .= implode(PHP_EOL, $Re);

            if (!in_array(self::$Dt->user_id, $Me)) {
                $Me = self::GetPlayer(self::$Dt->user_id);
                if ($Me) {
                    $Lang .= PHP_EOL . PHP_EOL . "➖➖➖➖➖➖➖➖➖➖➖➖➖➖" . PHP_EOL;
                    $Lang .= self::$Dt->L->_('YourTop', round($Me['top']));
                }
            }

            return $Lang;

        }

        return false;

    }


    public static function KillMe($user_id, $limit = 1)
    {
        $ops = [
            ['$match' => ['kill' => (string)$user_id]],
            ['$group' => ['_id' => '$killer', 'count' => ['$sum' => 1]]],
            ['$sort' => ['count' => -1]],
            ['$limit' => $limit],
        ];

        $result = self::$Dt->collection->kills->aggregate($ops);
        if ($result) {
            $array = iterator_to_array($result);
            return $array;
        }
        return false;
    }

    public static function Kills($user_id, $limit = 1)
    {
        $ops = [
            ['$match' => ['killer' => $user_id]],
            ['$group' => ['_id' => '$kill', 'count' => ['$sum' => 1]]],
            ['$sort' => ['count' => -1]],
            ['$limit' => $limit],
        ];

        $result = self::$Dt->collection->kills->aggregate($ops);
        if ($result) {
            $array = iterator_to_array($result);
            return $array;
        }
        return false;
    }


    public static function GetKillMe($user_id)
    {
        $result = self::$Dt->collection->Players->findOne(['user_id' => (float)$user_id]);
        if ($result) {
            $array = iterator_to_array($result);

            $data = self::KillMe($user_id, 5);
            if ($data) {
                $Re = [];
                foreach ($data as $key => $row) {
                    $name = self::_GetPlayerName($row['_id']);
                    $L = "<strong>" . $row['count'] . "</strong>        ";
                    $L .= "<strong>{$name}</strong>";
                    array_push($Re, $L);
                }

                if ($Re) {
                    $Lang = self::$Dt->L->_('kill', array("{0}" => self::ConvertName($array['user_id'], $array['fullname']) ,"{1}" => implode(PHP_EOL, $Re)));
                    return $Lang;
                }
                return false;
            }
        }
        return false;
    }


    public static function GetKills($user_id)
    {
        $result = self::$Dt->collection->Players->findOne(['user_id' => (float)$user_id]);
        if ($result) {
            $array = iterator_to_array($result);

            $data = self::Kills($user_id, 5);
            if ($data) {
                $Re = [];
                foreach ($data as $key => $row) {
                    $name = self::_GetPlayerName($row['_id']);
                    if ($name) {
                        $L = "<strong>" . $row['count'] . "</strong>        ";
                        $L .= "<strong>{$name}</strong>";
                        array_push($Re, $L);
                    }
                }

                if ($Re) {
                    $Lang = self::$Dt->L->_('kills', array("{0}" => self::ConvertName($array['user_id'], $array['fullname']) ,"{1}" => implode(PHP_EOL, $Re)));
                    return $Lang;
                }
                return false;
            }
        }
        return false;
    }

    public static function UserSmiteInGame($user_id)
    {
        self::$Dt->collection->join_user->updateOne(array("chat_id"=>self::$Dt->chat_id),array('$pull' => array("users" => ['user_id' => $user_id])));
        self::$Dt->collection->games_players->deleteOne(['group_id' => self::$Dt->chat_id, 'game_id' => self::$Dt->game_id, 'user_id' => (float)$user_id]);
        self::UpdatePlayerList(true);
    }

    public static function CheckUserByUsername($username)
    {
        $username = str_replace('@', '', $username);
        $result = self::$Dt->collection->games_players->findOne(['group_id' => self::$Dt->chat_id, 'game_id' => self::$Dt->game_id, 'username' => $username]);
        if ($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }

    public static function CheckPlayerByUsername($username)
    {
        $username = str_replace('@', '', $username);
        $result = self::$Dt->collection->Players->findOne([ 'username' => $username]);
        if ($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }



    public static function CheckUserById($user_id)
    {
        $result = self::$Dt->collection->Players->findOne([ 'user_id' => $user_id]);
        if ($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }


    public static function _GetPlayerByrole($role)
    {
        $result = self::$Dt->collection->games_players->findOne(['group_id' => self::$Dt->chat_id, 'game_id' => self::$Dt->game_id, 'user_role' => $role]);
        if ($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }

    public static function _GetCommand($Command)
    {
        $result = self::$Dt->collection->role_list->findOne(['Key' => $Command]);
        if ($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }

    public static function CheckUserGlobalAdmin($user_id)
    {
        $result = self::$Dt->collection->admin_global->findOne(['user_id' => (float)$user_id, 'state' => 1]);
        if ($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }


    public static function CheckPlayerInBanList($user_id)
    {
        $result = self::$Dt->collection->ban_list->findOne(['user_id' => (float)$user_id]);
        if ($result) {
            $array = iterator_to_array($result);
            if ($array['ban_antilto'] == 1) {
                return ['state' => true, 'key' => 'ban_ever', 'ban_by' => $array['by_name'], 'for' => $array['ban_for']];
            }
            $time = $array['ban_antilto'] - time();
            if ($time <= 0) {
                return ['state' => false];
            }
            return ['state' => true, 'key' => 'ban_to', 'time' => $array['ban_antilto'], 'ban_by' => $array['by_name'], 'for' => $array['ban_for']];
        }

        return false;

    }

    public static function AddPlayerBanList($user_id)
    {
        $cn = self::$Dt->collection->ban_list;
        $cn->insertOne([
            'group_id' => self::$Dt->chat_id,
            'user_id' => $user_id,
            'by' => self::$Dt->user_id,
            'textData' => (isset(self::$Dt->Replay) ? self::$Dt->Replay->getText() : "Global"),
            'by_name' => self::$Dt->user_link,
            'ban_for' => self::$Dt->text ?? null,
            'fullname' => (isset(self::$Dt->Replay) ? self::$Dt->ReplayFullname : "Global"),
            'link' => (isset(self::$Dt->Replay) ?  self::$Dt->PlayerLink : "global"),
            'ban_antilto' => 0,
            'ban_warn' => 0,
            'time' => time(),
            'j_date' => jdate('Y-m-d H:i:s'),
            'm_date' => date('Y-m-d H:i:s')
        ]);
    }

    public static function RemoveFromBanList($user_id)
    {
        self::$Dt->collection->ban_list->deleteOne(['user_id' => (float)$user_id]);
    }

    public static function BanDetial($user_id)
    {
        $result = self::$Dt->collection->ban_list->findOne(['user_id' => (float)$user_id]);
        if ($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }

    public static function ChangeBanUntilTime($time, $user_id)
    {
        self::$Dt->collection->ban_list->updateOne(
            ['user_id' => (float)$user_id],
            ['$set' => ['ban_antilto' => $time]]
        );
    }

    public static function UpdateGroupLink($group_id, $link)
    {
        self::$Dt->collection->groups->updateOne(
            ['chat_id' => self::$Dt->chat_id],
            ['$set' => ['group_link' => $link]]
        );
    }

    public static function CheckUserInBan($user_id)
    {
        $result = self::$Dt->collection->ban_list->findOne(['user_id' => (float)$user_id]);
        if ($result) {
            $array = iterator_to_array($result);
            if ($array['ban_antilto'] == 1) {
                return ['state' => false, 'key' => 'ban_ever'];
            }
            $time = $array['ban_antilto'] - time();
            if ($time <= 0) {
                return ['state' => true];
            }
            return ['state' => false, 'key' => 'ban_to', 'time' => $array['ban_antilto']];
        }

        return false;
    }

    public static function GetRoleRandom($not_in = [])
    {
        $result = self::$Dt->collection->games_players->find([
            'game_id' => self::$Dt->game_id
            , 'group_id' => self::$Dt->chat_id
            , 'user_state' => 1
            , 'user_status' => 'on'
            , 'user_id' => ['$nin' => $not_in]
        ], ['limit' => -1, 'skip' => mt_rand(0, (self::CountPlayer()))]);

        if ($result) {
            $array = iterator_to_array($result);
            if (!isset($array['0'])) {
                return self::GetRoleRandom([$not_in,self::$Dt->user_id]);
            }
            return $array['0'];
        }


        return false;
    }

    public static function AddToAdminList()
    {
        $cn = self::$Dt->collection->admin_global;
        $cn->insertOne([
            'fullname' => self::$Dt->ReplayFullname,
            'user_id' => self::$Dt->ReplayTo,
            'user_name' => self::$Dt->ReplayUsername,
            'onwer' => 'admin',
            'onwer_by' => self::$Dt->fullname,
            'onwer_id' => self::$Dt->user_id,
            'ban_player' => 0,
            'view_banlist' => 0,
            'ban_1_y' => 0,
            'ban_1_a' => 0,
            'ban_1_m' => 0,
            'ban_1_w' => 0,
            'warn' => 0,
            'remove_ban' => 0,
            'report_global' => 0,
            'message_forward_global' => 0,
            'smite_player' => 0,
            'kill_game' => 0,
            'group_ban' => 0,
            'admin_all' => 0,
            'state' => 1,
        ]);
    }

    public static function GetAdminKeyboard($adminDetial)
    {
        $user_id = $adminDetial['user_id'];
        $inline_keyboard = new InlineKeyboard(
            [['text' => "بن کردن کاربران" . ($adminDetial['ban_player'] == 1 ? "✅" : "☑️"), 'callback_data' => "AdminSetting/ban_player/" . $user_id], ['text' => "بن کردن برای یکسال" . ($adminDetial['ban_1_y'] == 1 ? "✅" : "☑️"), 'callback_data' => "AdminSetting/ban_1_y/" . $user_id]],
            [['text' => "بن کردن برای یک ماه" . ($adminDetial['ban_1_m'] == 1 ? "✅" : "☑️"), 'callback_data' => "AdminSetting/ban_1_m/" . $user_id], ['text' => "بن کردن 1 روز" . ($adminDetial['ban_1_a'] == 1 ? "✅" : "☑️"), 'callback_data' => "AdminSetting/ban_1_a/" . $user_id]],
            [['text' => "بن کردن 1 هفته" . ($adminDetial['ban_1_w'] == 1 ? "✅" : "☑️"), 'callback_data' => "AdminSetting/ban_1_w/" . $user_id], ['text' => "وارن دادن" . ($adminDetial['warn'] == 1 ? "✅" : "☑️"), 'callback_data' => "AdminSetting/warn/" . $user_id]],
            [['text' => "بن برای همیشه" . ($adminDetial['ban_all'] == 1 ? "✅" : "☑️"), 'callback_data' => "AdminSetting/ban_all/" . $user_id], ['text' => "حذف از لیست بن" . ($adminDetial['remove_ban'] == 1 ? "✅" : "☑️"), 'callback_data' => "AdminSetting/remove_ban/" . $user_id]],
            [['text' => "ریپرت کردن کاربرن" . ($adminDetial['report_global'] == 1 ? "✅" : "☑️"), 'callback_data' => "AdminSetting/report_global/" . $user_id], ['text' => "ارسال پیام برای بازیکنان" . ($adminDetial['message_forward_global'] == 1 ? "✅" : "☑️"), 'callback_data' => "AdminSetting/message_forward_global/" . $user_id]],
            [['text' => "اسمایت کردن کاربر" . ($adminDetial['smite_player'] == 1 ? "✅" : "☑️"), 'callback_data' => "AdminSetting/smite_player/" . $user_id], ['text' => "بستن بازی" . ($adminDetial['kill_game'] == 1 ? "✅" : "☑️"), 'callback_data' => "AdminSetting/kill_game/" . $user_id]],
            [['text' => "بن کردن گروه" . ($adminDetial['group_ban'] == 1 ? "✅" : "☑️"), 'callback_data' => "AdminSetting/group_ban/" . $user_id], ['text' => "مدیر همه چیز" . ($adminDetial['admin_all'] == 1 ? "✅" : "☑️"), 'callback_data' => "AdminSetting/admin_all/" . $user_id]],
            [['text' => "بستن صفحه", 'callback_data' => "closeBanList"]]
        );

        return $inline_keyboard;
    }

    public static function GetAdminSetting($user_id)
    {
        $adminDetial = self::CheckUserGlobalAdmin($user_id);
        $inline_keyboard = self::GetAdminKeyboard($adminDetial);
        $Lang = "تنظیمات دسترسی مدیر %s";
        return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => vsprintf($Lang, [$adminDetial['fullname']]),
            'parse_mode' => 'HTML',
            'reply_markup' => $inline_keyboard,
        ]);
    }

    public static function ChangeAdminSetting($Key, $to, $user_id)
    {
        self::$Dt->collection->admin_global->updateOne(
            ['user_id' => (float)$user_id],
            ['$set' => [$Key => $to]]
        );
    }

    public static function GetBanlistKeyboard($adminDetial, $user_id)
    {

        $KeyBoard = "";

        $inline_keyboard = new InlineKeyboardInlineKeyboard(
            [['text' => "گذشت از بن" . ($adminDetial['remove_ban'] == 0 ? "🔒" : ""), 'callback_data' => ($adminDetial['remove_ban'] == 1 ? "BanPlayer_No/" . self::$Dt->chat_id . "/" . $user_id : "locked")], ['text' => "حذف از لیست بن" . ($adminDetial['remove_ban'] == 0 ? "🔒" : ""), 'callback_data' => ($adminDetial['remove_ban'] == 1 ? "BanPlayer_remove/" . self::$Dt->chat_id . "/" . $user_id : "locked")]],
            [['text' => "بن برای 30 دقیقه", 'callback_data' => "BanPlayer_30min/" . self::$Dt->chat_id . "/" . $user_id], ['text' => "بن برای 1 روز" . ($adminDetial['ban_1_a'] == 0 ? "🔒" : ""), 'callback_data' => ($adminDetial['ban_1_a'] == 1 ? "BanPlayer_1d/" . self::$Dt->chat_id . "/" . $user_id : "locked")]],
            [['text' => "بن برای یک هفته" . ($adminDetial['ban_1_w'] == 0 ? "🔒" : ""), 'callback_data' => ($adminDetial['ban_1_w'] == 1 ? "BanPlayer_1w/" . self::$Dt->chat_id . "/" . $user_id : "locked")], ['text' => "بن برای 1 ماه" . ($adminDetial['ban_1_m'] == 0 ? "🔒" : ""), 'callback_data' => ($adminDetial['ban_1_m'] == 1 ? "BanPlayer_1m/" . self::$Dt->chat_id . "/" . $user_id : "locked")]],
            [['text' => "بن برای 1 سال" . ($adminDetial['ban_1_y'] == 0 ? "🔒" : ""), 'callback_data' => ($adminDetial['ban_1_y'] == 1 ? "BanPlayer_1y/" . self::$Dt->chat_id . "/" . $user_id : "locked")], ['text' => "بن برای همیشه" . ($adminDetial['ban_all'] == 0 ? "🔒" : ""), 'callback_data' => ($adminDetial['ban_all'] == 1 ? "BanPlayer_ban/" . self::$Dt->chat_id . "/" . $user_id : "locked")]],
            [['text' => "دادن 1 اخطار" . ($adminDetial['warn'] == 0 ? "🔒" : ""), 'callback_data' => ($adminDetial['warn'] == 1 ? "BanPlayer_1warn/" . self::$Dt->chat_id . "/" . $user_id : "locked")], ['text' => "دادن 2 اخطار" . ($adminDetial['warn'] == 0 ? "🔒" : ""), 'callback_data' => ($adminDetial['warn'] == 1 ? "BanPlayer_2warn/" . self::$Dt->chat_id . "/" . $user_id : "locked")]],
            [['text' => "بستن صفحه", 'callback_data' => "closeBanList"]]
        );

        return $inline_keyboard;
    }

    public static function AddActivity($text)
    {
        $cn = self::$Dt->collection->global_activity;
        $cn->insertOne([
            'text' => $text,
            'admin_id' => self::$Dt->user_id,
            'player_id' => self::$Dt->ReplayTo ?? "null",
            'time' => time(),
            'j_date' => jdate('Y-m-d H:i:s'),
            'm_date' => date('Y-m-d H:i:s')
        ]);
    }

    public static function GetAchievement()
    {
        $result = self::$Dt->collection->achievement->find(['state' => 1]);
        if ($result) {
            $array = iterator_to_array($result);
            $Group = [];
            foreach ($array as $row) {
                $Group[][$row['group']] = $row['key'];
            }
            $re_group = [];
            $re = [];
            foreach ($Group as $row) {
                foreach ($row as $key => $row) {
                    if (!in_array($key, $re_group)) {
                        array_push($re_group, $key);
                        array_push($re, "<strong>" . self::$Dt->L->_('Ach_' . $key, count($Group), 0) . "</strong>");
                    }
                    $Lang = "<pre>-" . self::$Dt->L->_($row) . "</pre>" . PHP_EOL;
                    $Lang .= "» " . self::$Dt->L->_($row . "_dic");
                    array_push($re, $Lang);
                }
            }

            $chunked = array_chunk($re, 35);
            foreach ($chunked as $row) {
                Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => implode(PHP_EOL, $row),
                    'parse_mode' => 'HTML',
                ]);
            }

            return true;
        }


        return false;
    }

    public static function GetGroupList($lang, $mode)
    {
        $result = self::$Dt->collection->group_list->find([
            'game_mode' => $mode,
            'lang' => $lang,
            'in_list' => true,

        ], [
            'limit' => 10,
            'sort' => ['score' => -1]
        ]);


        if ($result) {
            $array = iterator_to_array($result);
            $re = [];
            foreach ($array as $row) {

                $NoPerfix = RC::NoPerfix();
                if ($NoPerfix->get("{$row['group_id']}:group_link")) {
                    $List = '<a href="' . $NoPerfix->get("{$row['group_id']}:group_link") . '">';
                    $List .= $NoPerfix->get("{$row['group_id']}:group_name");
                    $List .= "</a>";
                    array_push($re, $List);
                }
            }

            if ($re) {
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => implode(PHP_EOL, $re),
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => 'true'
                ]);
            }
        }

        return false;
    }

    public static function StandradAvg()
    {
        $ops = [
            ['$group' => ['_id' => [
                "game_mode" => '$game_mode',
                "group_lang" => '$group_lang',

            ],
                'avg_gameTime' => ['$avg' => '$game_time'],
                'avg_nobeplayer' => ['$avg' => '$nobes_Player'],
                'avg_afkedplayer' => ['$avg' => '$afked_player'],
                'avg_PlayerCount' => ['$avg' => '$player_count'],
                'STD_PlayerCount' => ['$stdDevPop' => '$player_count'],
                'STD_NobesPlayer' => ['$stdDevPop' => '$nobes_Player'],
                'STD_GameTime' => ['$stdDevPop' => '$game_time'],
                'count' => ['$sum' => 1],
            ]], [
                '$project' => [
                    'avg_gameTime' => '$avg_gameTime',
                    'avg_nobeplayer' => '$avg_nobeplayer',
                    'avg_afkedplayer' => '$avg_afkedplayer',
                    'avg_PlayerCount' => '$avg_PlayerCount',
                    'STD_GameCount' => ['$stdDevPop' => '$count'],
                ]]
        ];

        $result = self::$Dt->collection->group_stats->aggregate($ops);
        if ($result) {
            $array = iterator_to_array($result);
            return $array;
        }

    }

    public static function Stand_Deviation($arr)
    {
        $num_of_elements = count($arr);

        $variance = 0.0;

        // calculating mean using array_sum() method
        $average = array_sum($arr) / $num_of_elements;

        foreach ($arr as $i) {
            // sum of squares of differences between
            // all numbers and means.
            $variance += pow(($i - $average), 2);
        }

        return (float)sqrt($variance / $num_of_elements);
    }

    public static function searchForId($id, $lang, $array)
    {
        $re = [];
        foreach ($array as $key => $val) {
            if ($val['_id']['game_mode'] === $id and $val['_id']['group_lang'] === $lang) {
                array_push($re, $array[$key]);
            }
        }
        return $re;
    }

    public static function SaveGroupList($game_mode, $lang, $group_id, $score, $data, $groupname)
    {
        $cn = self::$Dt->collection->group_list;
        $cn->insertOne([
            'grou_name' => $groupname,
            'group_id' => $group_id,
            'game_mode' => $game_mode,
            'lang' => $lang,
            'avg_PlayerCount' => floor($data['avg_PlayerCount']),
            'avg_gameTime' => floor($data['avg_gameTime']),
            'avg_nobeplayer' => floor($data['avg_nobeplayer']),
            'avg_afkedplayer' => floor($data['avg_afkedplayer']),
            'count' => floor($data['count']),
            'PlayerCount' => floor($data['PlayerCount']),
            'gameTime' => floor($data['gameTime']),
            'nobeplayer' => floor($data['nobeplayer']),
            'afkedplayer' => floor($data['afkedplayer']),
            'in_list' => true,
            'score' => $score,
            'in' => jdate('Y-m-d H:i:s'),
            'in_amd' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function GetAvg()
    {


        $ops = [
            ['$group' => ['_id' => [
                "game_mode" => '$game_mode',
                "group_id" => '$group_id',
                "group_lang" => '$group_lang',

            ],
                'avg_gameTime' => ['$avg' => '$game_time'],
                'avg_nobeplayer' => ['$avg' => '$nobes_Player'],
                'avg_afkedplayer' => ['$avg' => '$afked_player'],
                'avg_PlayerCount' => ['$avg' => '$player_count'],
                'gameTime' => ['$sum' => '$game_time'],
                'nobeplayer' => ['$sum' => '$nobes_Player'],
                'afkedplayer' => ['$sum' => '$afked_player'],
                'PlayerCount' => ['$sum' => '$player_count'],
                'count' => ['$sum' => 1]]],
        ];

        $result = self::$Dt->collection->group_stats->aggregate($ops);
        if ($result) {
            $array = iterator_to_array($result);
            return $array;
        }

    }

    public static function GetPlayerLists()
    {
        $result = self::$Dt->collection->Players->find();

        if ($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }

    public static function GetPlayersCount()
    {
        $cn = self::$Dt->collection->Players;
        $count = $cn->count();

        return $count;
    }


    public static function get_tplayer()
    {
        $result = self::$Dt->collection->games_players->count([]);
        return $result ?? 0;
    }

    public static function GetUptime()
    {
        $str = @file_get_contents('/proc/uptime');
        $num = floatval($str);
        $secs = fmod($num, 60);
        $num = intdiv($num, 60);
        $mins = $num % 60;
        $num = intdiv($num, 60);
        $hours = $num % 24;
        $num = intdiv($num, 24);
        $days = $num;

        return "$days:$hours:$mins.$secs";
    }

    public static function get_tgame()
    {
        $result = self::$Dt->collection->games->count([]);
        return ($result ?? 0);
    }


    public static function SavePlayerAchivment($user_id, $achive_code)
    {
        $result = self::$Dt->collection->achievement_player->count(['achiv_code' => $achive_code, 'user_id' => (float)$user_id]);
        if ($result == 0) {
            self::$Dt->collection->achievement_player->insertOne([
                'achiv_code' => $achive_code,
                'user_id' => $user_id,
                'group_id' => self::$Dt->chat_id,
                'time' => time(),
                'date' => jdate('Y-m-d H:i:s')
            ]);
            $AchMessage = self::$Dt->L->_('AchioUnlock') . PHP_EOL;
            $AchMessage .= self::$Dt->L->_($achive_code) . PHP_EOL;
            $AchMessage .= self::$Dt->L->_($achive_code . "_dic");
            HL::SendMessage($AchMessage, $user_id);
            return true;
        }
        return false;
    }


    public static function ChangeLuciferTeam($to)
    {

    }

    public static function EditMarkupKeyboard()
    {
        $Key = RC::LRange(0,-1,'GamePl:MessageNightSend');
        if($Key) {
            foreach ($Key as $key) {
                $Ex = explode('_', $key);
                $user_id = $Ex['1'];

                $Message_id = $Ex['0'];
                Request::editMessageText([
                    'chat_id' => $user_id,
                    'text' => self::$Dt->L->_('KillGameClose'),
                    'message_id' => $Message_id,
                    'parse_mode' => 'HTML',
                    'reply_markup' => new InlineKeyboard([]),
                ]);
            }
            RC::Del('GamePl:MessageNightSend');
        }
        $Key = RC::keys('GamePl:MessageNightSendDodgeVote:*');
        foreach ($Key as $key) {
            $Ex = explode(':', $key);
            $user_id = $Ex['3'];
            $keys = "{$Ex['1']}:{$Ex['2']}:{$Ex['3']}";
            $Message_id = RC::Get($keys);
            Request::editMessageText([
                'chat_id' => $user_id,
                'text' => self::$Dt->L->_('KillGameClose'),
                'message_id' => $Message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
            RC::Del($keys);
        }


        // ویرایش markUp
        $Key = RC::LRange(0,-1,'GamePl:EditMarkup');
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

            RC::Del('GamePl:EditMarkup');
        }


    }

    public static function DeleteMessage()
    {
        $data = RC::LRange(0, -1, 'deleteMessage');
        foreach ($data as $datum) {
            Request::deleteMessage([
                'chat_id' => self::$Dt->chat_id,
                'message_id' => $datum,
            ]);
        }
        RC::Del('deleteMessage');
        $dataEditMarkup = RC::LRange(0, -1, 'EditMarkup');
        foreach ($dataEditMarkup as $datum) {
            Request::editMessageReplyMarkup([
                'chat_id' => self::$Dt->chat_id,
                'message_id' => $datum,
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }
        RC::Del('EditMarkup');
    }

    public static function KillGame()
    {
        RC::GetSet(true, 'GamePl:GameIsEnd');
        RC::Del('game_state');
        self::$Dt->collection->games_players->deleteMany(['group_id' => self::$Dt->chat_id, 'game_id' => self::$Dt->game_id]);
        self::$Dt->collection->games->deleteOne(['group_id' => self::$Dt->chat_id, 'game_id' => self::$Dt->game_id]);
        self::$Dt->collection->join_user->deleteOne(['chat_id' => self::$Dt->chat_id]);
        self::EditMarkupKeyboard();
        self::DeleteMessage();
    }

    public static function GetWhiteList($chat_id)
    {
        $result = self::$Dt->collection->white_list->findOne(
            ['chat_id' => (string) $chat_id]);
        if ($result) {
            $array = iterator_to_array($result);
            $times = strtotime($array['expire']);
            $timesLeft = $times - time();
            if($timesLeft > 0){
                return true;
            }
            return false;
        }

        return false;
    }

    public static function GetGroups()
    {
        $result = self::$Dt->collection->groups->find([]);
        if ($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }


    public static function RolesKeyboard()
    {
        $keybaord = new InlineKeyboard(
            [
                ['text' => "👱‍♂ " . (RC::Get('role_rosta') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_rosta"]
                , ['text' => "👷  " . (RC::Get('role_feramason') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_feramason"]
                , ['text' => "👳  " . (RC::Get('role_pishgo') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_pishgo"]
                , ['text' => "🕵️  " . (RC::Get('role_karagah') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_karagah"]
            ],
            [
                ['text' => "🔫  " . (RC::Get('role_tofangdar') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_tofangdar"]
                , ['text' => "📚  " . (RC::Get('role_rishSefid') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_rishSefid"]
                , ['text' => "🌚👱  " . (RC::Get('role_Gorgname') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Gorgname"]
                , ['text' => "👁  " . (RC::Get('role_Nazer') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Nazer"]
            ],
            [
                ['text' => "👮‍♂" . (RC::Get('role_kalantar') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_kalantar"]
                , ['text' => "👼  " . (RC::Get('role_Fereshte') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Fereshte"]
                , ['text' => "⚒  " . (RC::Get('role_Ahangar') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Ahangar"]
                , ['text' => "💤  " . (RC::Get('role_KhabGozar') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_KhabGozar"]
            ],
            [
                ['text' => "🎖️  " . (RC::Get('role_Kadkhoda') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Kadkhoda"]
                , ['text' => "🍻  " . (RC::Get('role_Mast') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Mast"]
                , ['text' => "👶  " . (RC::Get('role_Vahshi') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Vahshi"]
                , ['text' => "🤴  " . (RC::Get('role_Shahzade') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Shahzade"]

            ],
            [
                ['text' => "🌀  " . (RC::Get('role_ngativ') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_ngativ"]
                , ['text' => "🃏  " . (RC::Get('role_ahmaq') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_ahmaq"]
                , ['text' => "🙇‍♂" . (RC::Get('role_PishRezerv') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_PishRezerv"]
                , ['text' => "🤕  " . (RC::Get('role_PesarGij') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_PesarGij"]
            ],
            [
                ['text' => "☮️  " . (RC::Get('role_Solh') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Solh"]
                , ['text' => "💂  " . (RC::Get('role_shekar') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_shekar"]
                , ['text' => "👰🏻️" . (RC::Get('role_Sweetheart') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Sweetheart"]
                , ['text' => "👑  " . (RC::Get('role_Ruler') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Ruler"]
            ],
            [
                ['text' => "🗡️  " . (RC::Get('role_Knight') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Knight"]
                , ['text' => "🍉  " . (RC::Get('role_Watermelon') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Watermelon"]
                , ['text' => "👺  " . (RC::Get('role_monafeq') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_monafeq"]
                , ['text' => "👤  " . (RC::Get('role_ferqe') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_ferqe"]
            ],
            [
                ['text' => "🔪️" . (RC::Get('role_Qatel') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Qatel"]
                , ['text' => "🏹" . (RC::Get('role_Archer') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Archer"]
                , ['text' => "👹 " . (RC::Get('role_lucifer') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_lucifer"]
                , ['text' => "🔮  " . (RC::Get('role_WolfJadogar') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_WolfJadogar"]
            ],
            [
                ['text' => "🐺" . (RC::Get('role_WolfGorgine') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_WolfGorgine"]
                , ['text' => "🌝🐺" . (RC::Get('role_Wolfx') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Wolfx"]
                , ['text' => "⚡🐺" . (RC::Get('role_WolfAlpha') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_WolfAlpha"]
                , ['text' => "🧙🏻‍♀" . (RC::Get('role_Honey') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Honey"]
            ],

            [
                ['text' => "🐺🌩" . (RC::Get('role_WhiteWolf') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_WhiteWolf"]
                , ['text' => "🧝🏻‍♀🐺" . (RC::Get('role_forestQueen') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_forestQueen"]
                , ['text' => "🔥🤴🏻" . (RC::Get('role_Firefighter') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Firefighter"]
                , ['text' => "❄👸🏻️" . (RC::Get('role_IceQueen') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_IceQueen"]
            ],
            [
                ['text' => "🧛🏻‍♀" . (RC::Get('role_Bloodthirsty') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Bloodthirsty"]
                , ['text' => "💘️" . (RC::Get('role_elahe') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_elahe"]
                , ['text' => "🎭 " . (RC::Get('role_Hamzad') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Hamzad"]
                , ['text' => "🖕 " . (RC::Get('role_Khaen') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Khaen"]

            ],
            [
                ['text' => "🎩️" . (RC::Get('role_Royce') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Royce"]
                , ['text' => "🦹🏻‍♂" . (RC::Get('role_Spy') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Spy"]
                , ['text' => "😾" . (RC::Get('role_NefrinShode') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_NefrinShode"]
                , ['text' => "💋" . (RC::Get('role_faheshe') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_faheshe"]

            ],
            [
                ['text' => "🧛🏻‍♂" . (RC::Get('role_Vampire') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Vampire"]
                , ['text' => "🧙🏼‍♂" . (RC::Get('role_enchanter') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_enchanter"]
                , ['text' => "🐶️ " . (RC::Get('role_WolfTolle') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_WolfTolle"]
                , ['text' => "🪓" . (RC::Get('role_Huntsman') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Huntsman"]
            ],
            [
                ['text' => "🤯" . (RC::Get('role_trouble') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_trouble"]
                ,['text' => "👨‍🔬" . (RC::Get('role_Chemist') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Chemist"]
                ,['text' => "🦅" . (RC::Get('role_Augur') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_Augur"]
                ,['text' => "☠️" . (RC::Get('role_GraveDigger') == "on" ? "✅" : "⛔️"), 'callback_data' => 'configureGroup_on/' . self::$Dt->chat_id . "/role_GraveDigger"]

            ],
            [
                ['text' => self::$Dt->L->_('UnlokAll'), 'callback_data' => 'setting_unlockAll/' . self::$Dt->chat_id],
                ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_group/' . self::$Dt->chat_id]
            ]
        );

        return $keybaord;
    }


    public static function GetLive(){
        $list = [];
        $NoP = RC::NoPerfix();
        $result = self::$Dt->collection->games->find([]);
        if ($result) {
            $array = iterator_to_array($result);

            foreach ($array as $key =>  $row){
                $Players = self::CountPlayer($row['group_id']);

                $s = $key+1;
                $G = "{$s}. ";
                $G .= '<a href="'.$NoP->get("{$row['group_id']}:group_link") .'">'.$NoP->get("{$row['group_id']}:group_name")."</a>";
                $G .= "  -  <strong> 🙎‍♂ Player $Players  </strong> ";
                $G .= " - ".self::$Dt->L->_($row['game_mode']);
                $G .= " - " .($NoP->get($row['group_id'].':game_state') == "join" ? "<strong> ⏰ Join Time</strong>" : "In Game");
                array_push($list,$G);
            }
        }


        return implode(PHP_EOL.PHP_EOL,$list);
    }



    public static function GetUserdeaths(){

        $user_id  =  self::$Dt->user_id;

        $NoP = RC::NoPerfix();

        if($NoP->exists('UserDeath:'.$user_id)){
            return $NoP->get('UserDeath:'.$user_id);
        }
        $CountLync = self::$Dt->collection->game_activity->count(['player_id'=> $user_id,'actvity'=> 'vote']);

        $CountKiller = self::$Dt->collection->game_activity->count(['player_id'=> $user_id,'actvity'=> 'kill']);

        $CountEat = self::$Dt->collection->game_activity->count(['player_id'=>$user_id,'actvity'=> 'eat']);

        $CountFlee = self::$Dt->collection->game_activity->count(['player_id'=>$user_id, 'actvity'=> 'flee']);

        $CountAfked = self::$Dt->collection->game_activity->count(['player_id'=>$user_id,'actvity'=> 'afk']);

        $CountShot = self::$Dt->collection->game_activity->count(['player_id'=>$user_id,'actvity'=> 'shot']);

        $CountVampire = self::$Dt->collection->game_activity->count(['player_id'=>$user_id,'actvity'=> 'vampire']);

        $CountKnight = self::$Dt->collection->game_activity->count(['player_id'=>$user_id,'actvity'=> 'knight']);

        $Countarcher = self::$Dt->collection->game_activity->count(['player_id'=>$user_id,'actvity'=> 'archer']);

        $CountHunts = self::$Dt->collection->game_activity->count(['player_id'=>$user_id,'actvity'=> 'huns']);

        $CountFire = self::$Dt->collection->game_activity->count(['player_id'=>$user_id,'actvity'=> 'fire']);

        $CountIce = self::$Dt->collection->game_activity->count(['player_id'=>$user_id,'actvity'=> 'ice']);

        $CountCult = self::$Dt->collection->game_activity->count(['player_id'=>$user_id,'actvity'=> 'cult']);


        $CountLoveDead = self::$Dt->collection->game_activity->count(['player_id'=>$user_id,'actvity'=> 'love_dead']);

        $TotalAl = $CountLync + $CountKiller + $CountEat + $CountFlee + $CountAfked + $CountShot + $CountVampire + $CountKnight +  $Countarcher + $CountHunts + $CountLoveDead + $CountFire + $CountIce +$CountCult ;

        $Return = self::$Dt->L->_('DeathList',array("{0}" => self::$Dt->user_link)).PHP_EOL;


        $T_Lync = ($CountLync > 0 ? floor($CountLync * 100 / $TotalAl) : 0);

        $Return .= "<code> {$CountLync} ({$T_Lync}%) </code> ".self::$Dt->L->_('in_vote').PHP_EOL;

        $T_Killer = ($CountKiller > 0 ? floor($CountKiller * 100 / $TotalAl) : 0);
        $Return .= "<code> {$CountKiller} ({$T_Killer}%) </code> ".self::$Dt->L->_('KillerKill').PHP_EOL;

        $T_Eat = ($CountEat > 0 ? floor($CountEat * 100 / $TotalAl) : 0);
        $Return .= "<code> {$CountEat} ({$T_Eat}%) </code> ".self::$Dt->L->_('WolfKill').PHP_EOL;


        $T_Flee = ($CountFlee > 0 ? floor($CountFlee * 100 / $TotalAl) : 0);
        $Return .= "<code> {$CountFlee} ({$T_Flee}%) </code> ".self::$Dt->L->_('FleeKill').PHP_EOL;


        $T_Afked = ($CountAfked > 0 ? floor($CountAfked * 100 / $TotalAl) : 0);
        $Return .= "<code> {$CountAfked} ({$T_Afked}%) </code> ".self::$Dt->L->_('AfkKill').PHP_EOL;

        $T_Shot = ($CountShot > 0 ? floor($CountShot * 100 / $TotalAl) : 0);
        $Return .= "<code> {$CountShot} ({$T_Shot}%) </code> ".self::$Dt->L->_('ShotKill').PHP_EOL;


        $T_Vampire = ($CountVampire > 0 ? floor($CountVampire * 100 / $TotalAl) : 0);
        $Return .= "<code> {$CountVampire} ({$T_Vampire}%) </code> ".self::$Dt->L->_('vampireKill').PHP_EOL;

        $T_Knight = ($CountKnight > 0 ? floor($CountKnight * 100 / $TotalAl) : 0);
        $Return .= "<code> {$CountKnight} ({$T_Knight}%) </code> ".self::$Dt->L->_('KnightKill').PHP_EOL;

        $T_archer= ($Countarcher > 0 ? floor($Countarcher * 100 / $TotalAl) : 0);
        $Return .= "<code> {$Countarcher} ({$T_archer}%) </code> ".self::$Dt->L->_('ArcherKill').PHP_EOL;

        $T_Hunts= ($CountHunts > 0 ? floor($CountHunts * 100 / $TotalAl) : 0);
        $Return .= "<code> {$CountHunts} ({$T_Hunts}%) </code> ".self::$Dt->L->_('HunsKill').PHP_EOL;

        $T_LoveDead = ($CountLoveDead > 0 ? floor($CountLoveDead * 100 / $TotalAl) : 0);
        $Return .= "<code> {$CountLoveDead} ({$T_LoveDead}%) </code> ".self::$Dt->L->_('LoveDeadKill').PHP_EOL;

        $T_Fire = ($CountFire > 0 ? floor($CountFire * 100 / $TotalAl) : 0);
        $Return .= "<code> {$CountFire} ({$T_Fire}%) </code> ".self::$Dt->L->_('FireKill').PHP_EOL;

        $T_Ice = ($CountIce > 0 ? floor($CountIce * 100 / $TotalAl) : 0);
        $Return .= "<code> {$CountIce} ({$T_Ice}%) </code> ".self::$Dt->L->_('IceKill').PHP_EOL;

        $T_Cult = ($CountCult > 0 ? floor($CountCult * 100 / $TotalAl) : 0);
        $Return .= "<code> {$CountCult} ({$T_Cult}%) </code> ".self::$Dt->L->_('CultKill').PHP_EOL;


        $NoP->set('UserDeath:'.$user_id,$Return);
        $NoP->expire('UserDeath:'.$user_id,300);

        return $Return;
    }


    public static function GetKillLastId($user_id = false){

        if(!$user_id){
            $user_id = self::$Dt->user_id;
        }
        $ops = [
            ['$match' => ['player_id' => $user_id,'actvity'=>array('$in' => array('kill','eat','huns','shot','archer','knight','cult','fire','ice','vote_kill')) ]],
            ['$group' => ['_id' => '$to', 'count' => ['$sum' => 1]]],
            ['$sort' => ['count' => -1]],
            ['$limit' => 3],
        ];

        $result = self::$Dt->collection->game_activity->aggregate($ops);
        if ($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }


    public static function GetYouKill($user_id = false){
        if(!$user_id){
            $user_id = self::$Dt->user_id;
        }

        $ops = [
            ['$match' => ['to' =>$user_id,'actvity'=>array('$in' => array('kill','eat','huns','shot','archer','knight','cult','fire','ice','vote_kill')) ]],
            ['$group' => ['_id' => '$player_id', 'count' => ['$sum' => 1]]],
            ['$sort' => ['count' => -1]],
            ['$limit' => 3],
        ];

        $result = self::$Dt->collection->game_activity->aggregate($ops);
        if ($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }


    public static function GetYouInLove(){
        $ops = [
            ['$match' => ['player_id' => (string) self::$Dt->user_id,'actvity'=> array('$in' => array('love')) ]],
            ['$group' => ['_id' => '$to', 'count' => ['$sum' => 1]]],
            ['$sort' => ['count' => -1]],
            ['$limit' => 3],
        ];

        $result = self::$Dt->collection->game_activity->aggregate($ops);
        if ($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }


    public static function GetMaxGamePlayed(){
        $ops = [
            ['$match' => ['player_id.user_id' => self::$Dt->user_id ]],
            ['$unwind' => '$player_id'],
            ['$match' => ['player_id.user_id' =>  array('$nin' => array(self::$Dt->user_id)) ]],
            ['$group'  => ['_id' => '$player_id.user_id', 'count' => ['$sum' => 1]] ],
            ['$sort' => ['count' => -1]],
            ['$limit' => 3],
        ];

        $result = self::$Dt->collection->group_states->aggregate($ops);
        if ($result) {
            $array = iterator_to_array($result);


            return $array;
        }

        return false;
    }




    public static function GetSocialUser(){

        $Re = self::$Dt->L->_('YourSocialState').PHP_EOL.PHP_EOL;


        $MaxGamePlayed = self::GetMaxGamePlayed();

        $Re .= self::$Dt->L->_('YouInGamePlay').PHP_EOL;
        if($MaxGamePlayed){
            $Res = [];
            foreach ($MaxGamePlayed as $key => $row) {
                $name = self::_GetPlayerName($row['_id']);
                $L =  "<code>".$row['count']."</code>";
                $L .= "   {$name}";
                array_push($Res, $L);
            }

            if($Res){
                $Re .= implode(PHP_EOL,$Res);
            }

        }


        $KillMe = self::GetKillLastId();

        $Re .= PHP_EOL.PHP_EOL.self::$Dt->L->_('killYou').PHP_EOL;
        if($KillMe){
            $Res = [];
            foreach ($KillMe as $key => $row) {
                $name = self::_GetPlayerName($row['_id']);
                $L =  "<code>".$row['count']."</code>";
                $L .= "   {$name}";
                array_push($Res, $L);
            }

            if($Res){
                $Re .= implode(PHP_EOL,$Res);
            }

        }


        $KillYou = self::GetYouKill();
        $Re .= PHP_EOL.PHP_EOL.self::$Dt->L->_('YouKill').PHP_EOL;
        if($KillYou){
            $Res = [];
            foreach ($KillYou as $key => $row) {
                $name = self::_GetPlayerName($row['_id']);
                $L =  "<code>".$row['count']."</code>";
                $L .= "   {$name}";
                array_push($Res, $L);
            }

            if($Res){
                $Re .= implode(PHP_EOL,$Res);
            }

        }

        $Love = self::GetYouInLove();
        $Re .= PHP_EOL.PHP_EOL.self::$Dt->L->_('YouInLove').PHP_EOL;
        if($Love){
            $Res3 = [];
            foreach ($Love as $key => $row) {
                $name = self::_GetPlayerName($row['_id']);
                $L =  "<code>".$row['count']."</code>";
                $L .= "   {$name}";
                array_push($Res3, $L);
            }

            if($Res3){
                $Re .= implode(PHP_EOL,$Res3);
            }

        }

        return $Re;
    }


    public static function GetInDayGoupDetial(){
        $ops = [
            ['$match' => ['group_id' => self::$Dt->chat_id  ]],
            ['$group'  => ['_id' => '$group_id', 'count' => ['$sum' => 1]] ],

            ['$sort' => ['count' => -1]],
        ];

        $result = self::$Dt->collection->group_stats->aggregate($ops);
        if ($result) {
            $array = iterator_to_array($result);


            return $array;
        }

        return false;
    }
    public static function GroupStats(){

        $r = self::$Dt->L->_('GroupStatePlayer').PHP_EOL.PHP_EOL;

        $inDay = self::$Dt->L->_('GroupStatePlayer_inDay',array("{0}" => 0));

        $inDayG = self::GetInDayGoupDetial();

        if($inDayG){
            $inDay = self::$Dt->L->_('GroupStatePlayer_inDay',array("{0}" => $inDayG['0']['count']));
        }
        $r .= $inDay;


        return $r;
    }


    public static function GetUserCredit(){
        $cns = self::$Dt->collection->Players;
        $Player = $cns->findOne(['user_id' => self::$Dt->user_id]);
        if ($Player) {
            $array = iterator_to_array($Player);
            return $array['credit'];
        }

        return 0;
    }


    public static function MinCreditCredit($New){
        $cns = self::$Dt->collection->Players;
        $cns->updateOne(
            ['user_id' => self::$Dt->user_id],
            ['$set' => ['credit' => $New]]
        );
        return true;
    }

    public static function GetLevelUPUser($level){


        switch ($level){
            case 2:
                return 1000;
                break;
            case 3:
                return 2000;
                break;
            case 4:
                return 4000;
                break;
            case 5:
                return 7000;
                break;
            case 6:
                return 11000;
                break;
            case 7:
                return 16000;
                break;
            case 8:
                return 22000;
                break;
            case 9:
                return 29000;
                break;
            case 10:
                return 37000;
                break;
            case 11:
                return 46000;
                break;
            case 12:
                return 51000;
                break;
            case 13:
                return 57000;
                break;
            case 14:
                return 64000;
                break;
            case 15:
                return 72000;
                break;
            case 16:
                return 77000;
                break;
            case 17:
                return 83000;
                break;
            case 18:
                return 90000;
                break;
            case 19:
                return 98000;
                break;
            case 20:
                return 107000;
                break;
            case 21:
                return 112000;
                break;
            case 22:
                return 118000;
                break;
            case 23:
                return 125000;
                break;
            case 24:
                return 132000;
                break;
            case 25:
                return 140000;
                break;
            case 26:
                return 148000;
                break;
            case 27:
                return 157000;
                break;
            case 28:
                return 162000;
                break;
            case 29:
                return 168000;
                break;
            case 30:
                return 176000;
                break;
            default:
                return 0;
                break;

        }
    }


    public static function GetLevel(){
        $result = self::$Dt->collection->Players->findOne(['user_id' => self::$Dt->user_id]);
        if ($result) {
            $array = iterator_to_array($result);
            $UserLevel = (is_numeric($array['Site_Username']) ? $array['Site_Username'] : 1);
            $UserXp = (is_numeric($array['Site_Password']) ? $array['Site_Password'] : 0);

            $IPlevel = $UserLevel+1;
            $ForLevel = self::GetLevelUPUser($IPlevel);
            $LeveLLeft = $ForLevel - $UserXp;

            $UserTop = self::$Dt->L->_('level_'.$UserLevel);
            $UserTopForward = self::$Dt->L->_('level_'.$IPlevel);

            $Lang = self::$Dt->L->_('MyLeveLCommend',number_format($UserXp),number_format($LeveLLeft),$UserLevel,$UserTop,$UserTopForward);

            return $Lang;
        }

        return false;
    }

    public static function CheckLastFriend($user_id){
        return self::$Dt->collection->friend_list->count(['user_id'=> self::$Dt->user_id,'friends'=> ['$in' => [(int) $user_id] ] ]);
    }
    public static function AddToFriendS($user_id,$push){
        $count = self::$Dt->collection->friend_list->count(['user_id'=> (int) $user_id]);

        if($count > 0){

            self::$Dt->collection->friend_list->updateOne(array("user_id"=> (int) $user_id),array('$push' => array("friends" => (int) $push)));
            return true;
        }

        self::$Dt->collection->friend_list->insertOne([
            'user_id' => (int) $user_id,
            'friends' => [(int)  $push],
        ]);

        return true;
    }


    public static function CheckPlayerInNextGame(){
        $result = self::$Dt->collection->next_game->findOne(['chat_id' => self::$Dt->chat_id,'users'=> ['$in' => [self::$Dt->user_id] ]]);

        return ($result ? true : false);
    }
    public static function AddPlayerToNextGame(){
        $result = self::$Dt->collection->next_game->findOne(['chat_id' => self::$Dt->chat_id]);
        if(!$result) {
            self::$Dt->collection->next_game->insertOne([
                'chat_id' => self::$Dt->chat_id,
                'users' => [self::$Dt->user_id],
            ]);
            return true;
        }

        self::$Dt->collection->next_game->updateOne(array("chat_id"=>self::$Dt->chat_id),array('$push' => array("users" => self::$Dt->user_id)));
        return false;

    }

    public static function RemoveFromNextGame(){
        self::$Dt->collection->next_game->updateOne(array("chat_id"=>self::$Dt->chat_id),array('$pull' => array("users" => self::$Dt->user_id)));
    }

    public static function checkLastGroup(){
        $result = self::$Dt->collection->player_group->findOne(['user_id' => self::$Dt->user_id,'chat_id'=> self::$Dt->chat_id]);

        return ($result ? true : false);
    }

    public static function GetUserLastGroupId(){
        $result = self::$Dt->collection->player_group->findOne(['user_id' => self::$Dt->user_id]);
        if($result){
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }
    public static function CheckGroup(){
        $result = self::$Dt->collection->player_group->findOne(['user_id' => self::$Dt->user_id]);

        if(!$result) {
            self::$Dt->collection->player_group->insertOne([
                'user_id' => self::$Dt->user_id,
                'chat_id' => self::$Dt->chat_id,
                'update' => jdate('Y-m-d H:i:s')
            ]);
            return false;
        }
        $array = iterator_to_array($result);

        if($array['chat_id'] == self::$Dt->chat_id){
            return 2;
        }
        return $array;
    }


    public static function ChangeUserGroup(){

        self::$Dt->collection->player_group->updateOne(
            ['user_id' => self::$Dt->user_id],
            ['$set' => ['chat_id' => self::$Dt->chat_id, 'update' => jdate('Y-m-d H:i:s')]]
        );

        return true;
    }
    /*

    public static function SaveVoteUser($user_id,$voter_userid,$voter_name){
        $result = self::$Dt->collection->save_vote->findOne(['chat_id' => self::$Dt->chat_id]);
        if(!$result) {
            self::$Dt->collection->save_vote->insertOne([
                'chat_id' => self::$Dt->chat_id,
                'voter' => [$user_id  => ['user_id' => $voter_userid ,'name' => $voter_name ]  ],
            ]);
            return true;
        }


        self::$Dt->collection->save_vote->updateOne(array("chat_id"=>self::$Dt->chat_id),array('$push' => ['voter' =>  [$user_id => ['user_id' => $voter_userid ,'name' => $voter_name ]  ] ]));
        return false;
    }
    */



    public static function AddWhiteList($chat_id){
        self::$Dt->collection->white_list->insertOne([
            'insert_by'=> self::$Dt->user_id,
            'chat_id' => $chat_id,
            'expire' => date('Y-m-d H:i:s',strtotime('+30 day', time())),
            'created' => jdate('Y/m/d/ H:i:s'),
            'status' => 1,
        ]);
    }
}

