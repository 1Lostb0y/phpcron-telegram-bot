<?php

namespace phpcron\CronBot;

use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;
use MongoDB\Client;


class Hook
{
    /**
     * Version
     *
     * @var string
     */
    protected $version = '0.1.1';



    public function __construct($Data)
    {


        $oUpdate = new Update($Data, BOT_USERNAME);
        $UpdateType = $oUpdate->getUpdateType();
        $this->Replay = false;
        $this->ReplayFullname = false;
        $this->PlayerLink = false;
        $this->Command = 0;
        $this->Bot_admins = explode(',',BOT_ADMINS);
        $this->AllowGroups = explode(',',ALLOW_GROUPS);

        if($UpdateType == "inline_query"){
            $oUpdate = $oUpdate->getInlineQuery();
            $from = $oUpdate->getFrom();
            $Query = $oUpdate->getQuery();
            $this->query = $Query;
            $this->inline = $oUpdate;
            $this->typeChat = $UpdateType;

        }elseif($UpdateType == "callback_query"){
            $oUpdate = $oUpdate->getCallbackQuery();
            $from = $oUpdate->getFrom();
            $oMessage = $oUpdate->getMessage();

            $this->callback_query_id = $oUpdate->getId();
            $this->callback_data = $oUpdate->getData();
            $this->callback_id = $oUpdate->getId();
            $this->callback_text = $oUpdate->getText();
            $this->callback_string = $oUpdate->getData();
            $string =  $oUpdate->getData();
            $this->data = $string;

            $ex =  explode('/',$string);
            if(isset($ex['1'])){
                $this->Command = 1;
                $chat_ids = $ex['1'];
            }

        }elseif($UpdateType == "edited_message"){
            $oUpdate = $oUpdate->getChannelPost();
            die('Edit');
        }elseif($UpdateType == "edited_channel_post"){
            $oUpdate = $oUpdate->getChannelPost();
            die('Edit');
        }elseif($UpdateType == "channel_post"){
            $oMessage = $oUpdate->getChannelPost();
            die('Edit');

        }else{
            $oMessage = $oUpdate->getMessage();
            if(!$oMessage->getFrom()) {
                die('Block');
            }
                $from = $oMessage->getFrom();
            
        }
        $this->collection = (new Client)->bold;

        $BOTAdd = false;
        if(isset($oMessage)) {
            if($oMessage->getNewChatMembers()) {
                if ($oMessage->botAddedInChat()) {
                    $BOTAdd = true;
                }
            }
        }




        /*


            if(!$oMessage->getCommand()){
                $ogp = $oMessage->getChat();
                $Type= $ogp->getType();
                     $cn = $this->collection->Players;
                      $ogp = $oMessage->getChat();
                      $chat_id = $ogp->getId();
                      $user_id = $from->getId();





                    $user_name = $from->getUsername() ?? 'null';
                     $full_name = $from->getFirstName() . " " . $from->getLastName();
                      $fullnames = preg_replace('/<?/', '', preg_replace('/<*?>/', '', $full_name)) ?? 'null';

                    $count = $cn->findOne(['user_id' => $user_id]);
                        // Send Message If User Change Name
                        if ($count) {
                            $array = iterator_to_array($count);
                            if ($array['fullname'] !== $fullnames) {
                                $cn->updateOne(
                                    ['user_id' => $user_id],
                                    ['$set' => ['fullname' => $fullnames, 'username' => $user_name]]
                                );

                                $L = "User " . $array['fullname'] . " (ID:" . $user_id . ") ";
                                $L .= " He changed his name to " . $fullnames;

                                if ($array['username'] !== $user_name) {
                                    $L .= "- Username: @" . $array['username'] . " To: @" . $user_name;
                                }


                                Request::sendMessage([
                                    'chat_id' => $chat_id,
                                    'text' => $L,
                                ]);

                            }
                        }



            }

        */

        $redis = new  \Predis\Client(array(
            'scheme' => 'tcp',
            'host' => 'localhost',
            'port' => 6379,
            'database' => 1
        ));
        $this->ReplayTo = false;
        $this->message_type = false;
        $this->userMode = false;
        $this->PlayerLink = "Unknow";
        $this->doc = false;
        if(isset($from)) {
            if (isset($oMessage)) {

                $this->message = $oMessage;
                $message_id = $oMessage->getMessageId();
                $ogp = $oMessage->getChat();


                if ($oMessage->getReplyToMessage()) {
                    $Replay = $oMessage->getReplyToMessage();
                    $this->Replay = $Replay;
                    $user_id = $Replay->getFrom()->getId();
                    $this->ReplayTo = $user_id;
                    $this->ReplayUsername = $Replay->getFrom()->getUsername();
                    $FullnameReplay = preg_replace('/<?/', '', preg_replace('/<*?>/', '', $Replay->getFrom()->getFirstName() . " " . $Replay->getFrom()->getLastName()));
                    $this->ReplayFullname = preg_replace('/<?/', '', preg_replace('/<*?>/', '', $Replay->getFrom()->getFirstName() . " " . $Replay->getFrom()->getLastName()));
                    $this->PlayerLink = '<a href="tg://user?id=' . $user_id . '">' . $FullnameReplay . '</a>';

                }
            }
            if (isset($ogp) && isset($oMessage)) {
                $chat_id = $chat_ids ?? $ogp->getId();
                $typeChat = $ogp->getType();
                $text = trim($oMessage->getText(true));
                $this->groupName = $ogp->getTitle();
                $this->chat_id = (float) $chat_id;
                $this->typeChat = $typeChat;
                $this->text = $text;
            } else {
                $this->chat_id = 0;
            }

            $username = $from->getUsername() ?? 'null';
            $user_id = $from->getId();
            $full_name = $from->getFirstName() . " " . $from->getLastName();
            $lang_code = $from->getLanguageCode();
            $this->userMode = "general";
            $this->username = $username;
            $this->user_id = (float)$user_id;
            $this->admin = 0;
            $this->allow = 0;




            /*
            if (!$redis->exists('forward22:' . $user_id)) {
                Request::forwardMessage([
                    'chat_id' => $user_id,
                    'from_chat_id' => -1001412066775,
                    'message_id' => 138
                ]);
                $redis->set('forward22:' . $user_id,true);
            }
            */




            $this->redis = $redis;

            if (isset($message_id)) {
                $this->message_id = $message_id;
            }


            if(isset($oMessage)){
                if($oMessage->getCommand()){
                    $this->Command = 1;
                    if($redis->exists('userBlocked:'.$this->user_id)){
                        $redis->del(['userBlocked:'.$this->user_id]);
                        die('Block');
                    }

                    $Nop = $redis;

                    if($Nop->exists('user_flood:'.$this->user_id)){
                        $Get = $Nop->get('user_flood:'.$this->user_id);
                        $MinTime = (int) $Get + 3;
                        if($MinTime > time()){
                            $CountSpam = 1;
                            if($Nop->exists('CountSpaming:'.$this->user_id)){
                                $CountSpam = (int) $Nop->get('CountSpaming:'.$this->user_id) + 1;
                            }
                            $Nop->set('CountSpaming:'.$this->user_id,$CountSpam);
                            if($CountSpam > 15){
                                $Nop->set('userBlocked:'.$this->user_id,true);
                                Request::sendMessage([
                                    'chat_id' => $this->user_id,
                                    'text' => "شما بدلیل اسپم نمودن دستورات ربات از ربات بصورت دائمی بن شده اید!",
                                    'parse_mode' => 'HTML',
                                ]);
                            }

                            die('Flood');
                        }
                    }
                    $Nop->set('user_flood:'.$this->user_id,time());
                    $Nop->del('CountSpaming:'.$this->user_id);


                }

            }


            $this->fullname = preg_replace('/<?/', '', preg_replace('/<*?>/', '', $full_name)) ?? 'null';

            $this->lang_code = $this->checkLangValidate($lang_code) ?? "fa";




            if (isset($ogp)) {
                $this->GetGameId();
                $this->game_id = $this->GameCurrentId ?? $this->generate_username(time() . $user_id);
                $this->JoinLink = JOIN_URL . $this->game_id;
                $this->ChallengeJoin = Challenge_URL . $this->game_id;

            }
            $Pl = $this->CountPlayer();
            $this->Coin = [
                'Mighty' => 60,
                'Vampire' => 80,
                'Romantic' => 50,
                'Normal' => 40,

            ];
            $this->def_lang = 'fa';
            $this->default_mode = 'general';
            $this->user_link = '<a href="tg://user?id=' . $this->user_id . '">' . $this->fullname . '</a>';


            $defultLang = ($this->def_lang ? $this->def_lang : $this->lang_code);
            $this->def_mode = ($this->default_mode ? $this->default_mode :  $this->userMode);
            $this->defaultLang = $defultLang;
            $L = new Lang(FALSE);
            $L->load("main_" . $defultLang, FALSE);
            $this->L = $L;



            $this->checkUser($Pl);




            /*

            if (!$redis->exists('forward7:' . $this->user_id)) {
                Request::forwardMessage([
                    'chat_id' => $this->user_id,
                    'from_chat_id' => -1001411379620,
                    'message_id' => 127
                ]);
                $redis->set('forward7:' . $this->user_id,true);
            }

            */



            RC::initialize($this);
            if ($UpdateType !== "inline_query") {
                $mode = "general";
                if($this->Command) {
                    $mode = (RC::Get('game_mode') ? RC::Get('game_mode') : "general");
                }
                $this->CheckUserIsAdmin();

                $this->GroupGameMode = ($this->GameModeList($mode) ? $mode :  "general");
                $this->GroupDefLang = $this->checkLangValidate(RC::Get('lang'));

                $LG = new Lang(FALSE);
                $LG->load($this->GroupGameMode . "_" . $this->GroupDefLang, FALSE);
                $this->LG = $LG;
                $this->checkGroup();

            }


            GR::initialize($this);
            $CheckBan = GR::CheckUserInBan($this->user_id);
            if ($CheckBan) {
                if ($CheckBan['state'] == false) {
                    if (isset($CheckBan['key'])) {
                        switch ($CheckBan['key']) {
                            case 'ban_ever':
                                $UserLang = $this->L->_($CheckBan['key']);
                                Request::sendMessage(['chat_id' => $this->user_id,
                                    'text' => $UserLang,
                                    'parse_mode' => 'HTML']);
                                die("Block");

                            case 'ban_to':
                                $UserLang = $this->L->_($CheckBan['key'], jdate('Y-m-d H:i:s', $CheckBan['time']));
                                Request::sendMessage(['chat_id' => $this->user_id,
                                    'text' => $UserLang,
                                    'parse_mode' => 'HTML']);
                                die("Block");

                        }
                    }
                }
            }
 
            CM::initialize($this);

        }

    }


    public static function GameModeList($Mode){
        $ArrayMode = explode(',',ALLOW_MODES);
        return (in_array($Mode,$ArrayMode) ?  true : false);
    }
    public  function checkLangValidate($lang){
        $Lang = array('fa','en','fr');
        if(in_array($lang,$Lang)){
            return $lang;
        }else{
            return 'fa';
        }
    }
    public function CheckUserIsAdmin(){
        // Your ID
        if($this->user_id == 630127836 || $this->user_id == 556635252){
            $this->allow = 1;
            $this->admin = 1;
            return true;
        }

        $chatUser = Request::getChatMember([
            'user_id' => $this->user_id,
            'chat_id' => $this->chat_id,
        ])->getResult();
        $status = 0;
        if($chatUser) {
            $status = $chatUser->getStatus();
        }
        switch ($status){
            case 'administrator':
            case 'creator':
                $this->allow = 1;
                $this->admin = 1;
                break;
            case 'member':
                $this->allow = 1;
                break;
            case 'restricted':
                $this->allow = 1;
                break;
            default:
                $this->allow = 0;
                break;
        }
    }

    function generate_username($string_name="wop", $rand_no = 200){
        $username_parts = array_filter(explode(" ", strtolower($string_name))); //explode and lowercase name
        $username_parts = array_slice($username_parts, 0, 2); //return only first two arry part

        $part1 = (!empty($username_parts[0]))?substr($username_parts[0], 0,8):""; //cut first name to 8 letters
        $part2 = (!empty($username_parts[1]))?substr($username_parts[1], 0,5):""; //cut second name to 5 letters
        $part3 = ($rand_no)?rand(0, $rand_no):"";

        $username = $part1. str_shuffle($part2). $part3; //str_shuffle to randomly shuffle all characters
        return $username;
    }

    public function randomPassword(
        $length,
        $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    )
    {
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        if ($max < 1) {
            throw new Exception('$keyspace must be at least two characters long');
        }
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
    }

    public function GetGameId(){
        $data = $this->text;
        if($data){
            if(strpos($this->text, 'joinToGAME_') !== false){
                $EX = explode('_',$data);
                $this->GameCurrentId = $EX['1'];
                $cn = $this->collection->games;
                $result = $cn->findOne(['game_id' => $EX['1']]);
                if($result) {
                    $this->chat_id = $result['group_id'];
                    return true;
                }
            }
        }
        $cn = $this->collection->games;
        $result = $cn->findOne(['group_id' => (float) $this->chat_id]);
        if(isset($result['game_id'])) {
            $this->GameCurrentId = $result['game_id'];
            return true;
        }
        return false;
    }
    public function CountPlayer(){
        $this->Player = [];

        // Database connection
        $cn = $this->collection->Players;
        /*Checking is there anyone with this name
          * Retun Number Integer
         */
        $count = $cn->findOne(['user_id' => $this->user_id]);

        $Cn_Data = ($count ? 1 : 0);
        $this->ExitPlayer = $Cn_Data;
        $array = [];
        if ($Cn_Data) {
            $array = iterator_to_array($count);
            $this->Player =$array;
            $this->def_lang = $array['def_lang'] ?? "fa";
            $this->default_mode = ($array['game_mode'] ? $array['game_mode'] : "general");
        }


        return ['cn' => $Cn_Data];



    }
    public function checkGroup(){

        /*
        if($this->typeChat == "group" || $this->typeChat == "supergroup"){
        $Array = [];
        if(in_array($this->chat_id,$Array)){
            Request::leaveChat(['chat_id'=> $this->chat_id]);
            Request::sendMessage([
                'chat_id' => 630127836,
                'text' => "leaved",
            ]);
        }
        if(empty($this->groupName)){
            return false;
        }
        $cn = $this->collection->groups;
        $count = $cn->count(['chat_id' => $this->chat_id]);

        if($count > 0) {
            $find = $cn->findOne(['chat_id' => $this->chat_id]);
            $array = iterator_to_array($find);
            if ($array) {

                if ($this->groupName !== $array['group_name']) {
                    $cn->updateOne(
                        ['chat_id' => $this->chat_id],
                        ['$set' => ['group_name' => $this->groupName]]
                    );

                    RC::GetSet($this->groupName, 'group_name');
                    $L = "Last Group Name :" . $array['group_name'] . " New Group Name Update To:  " . $this->groupName;
                   return Request::sendMessage([
                        'chat_id' => $this->chat_id,
                        'text' => $L,
                    ]);

                }
            }
        }
       }
        return false;
        */
    }
    public function checkUser($countPlayer){
        // Database connection



        $l = $countPlayer;
        $count = $l['cn'];

        // Check if the Message is in a private message
        if($this->typeChat == "private"){
            $cn = $this->collection->Players;
            // Add user to player list if not available in database
            if($count == 0){

                $usernameSite = $this->generate_username(($this->username !== "null" ? $this->username : "wopuser_" ));
                $passwordSite = $this->randomPassword(6);

                $cn->insertOne([
                    'username'          =>      $this->username,
                    'fullname'          =>      $this->fullname,
                    'user_id'           =>      $this->user_id,
                    'lang_code'         =>      $this->lang_code,
                    'def_lang'          =>      $this->lang_code,
                    'game_mode'         =>      $this->userMode,
                    'coin'              =>       500,
                    'top'               =>      0,
                    'credit'            =>      0,
                    'total_game'        =>      0,
                    'SurviveTheGame'    =>      0,
                    'SlaveGames'        =>      0,
                    'LoserGames'        =>      0,
                    'TheFirstGame'      =>      jdate('Y-m-d H:i:s'),
                    'TheFirstGameGMT'   =>      date('Y-m-d H:i:s'),
                    'TheLastGame'       =>      0,
                    'Site_Username'     =>      $usernameSite,
                    'Site_Password'     =>      $passwordSite,
                    'LoginToSite'       =>      0,
                    'ActivePhone'       =>      "",
                    'PhoneNumber'       =>      0,
                ]);

                /*
                Request::sendMessage([
                    'chat_id' => $this->user_id,
                    'text' => $this->L->_('userPassWordSite',$usernameSite,$passwordSite),
                    'parse_mode' => 'HTML'
                ]);
                */

            }


        }

        if($this->typeChat == "private" || $this->typeChat == "callback_query"){

            /*
             * چک کردن آیا کاربر در بازی وجود دارد یا نه
             * اگر بود اطلاعاتش در یک ارایه ذخیره بشه
             */
            $Pl = $this->collection->games_players;
            $countPl = $Pl->count(['user_id' => $this->user_id,'user_state' => 1]);

            if($countPl){
                $result = $Pl->findOne(['user_id' => $this->user_id,'user_state' => 1]);
                $array = iterator_to_array($result);
                $this->user_role = $array['user_role'] ?? "Unknow";
                $this->team = $array['team'] ?? "Unknow";
                $this->user_state = $array['user_state'];
                $this->in_game = 1;
            }else{
                $this->user_state = 0;
                $this->in_game = 0 ;
                $this->user_role = "none";
            }


        }

    }
    // End Check User Function



}




