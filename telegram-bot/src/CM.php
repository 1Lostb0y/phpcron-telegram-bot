<?php

namespace phpcron\CronBot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultArticle;
use Longman\TelegramBot\Entities\InputMessageContent\InputTextMessageContent;

class CM
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


    /*
     * Start Command Code
     */
    public static function CM_Start(){

        if(!self::$Dt->text) {
            if(!self::$Dt->user_id) {
                Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => self::$Dt->L->_('StartBot'),
                    'parse_mode' => 'HTML'
                ]);
            }
        }elseif(strpos(self::$Dt->text, 'joinToGAME_') !== false) {
            $CheckBan = GR::CheckUserInBan(self::$Dt->user_id);
            if($CheckBan){
                if($CheckBan['state'] == false){
                    if(isset($CheckBan['key'])) {
                        switch ($CheckBan['key']) {
                            case 'ban_ever':
                                $UserLang = self::$Dt->L->_($CheckBan['key']);
                                return Request::sendMessage(['chat_id' => self::$Dt->user_id,
                                    'text' => $UserLang,
                                    'parse_mode' => 'HTML']);
                                break;
                            case 'ban_to':
                                $UserLang = self::$Dt->L->_($CheckBan['key'],array("{0}" => jdate('Y-m-d H:i:s',$CheckBan['time'])));
                                return Request::sendMessage(['chat_id' => self::$Dt->user_id,
                                    'text' => $UserLang,
                                    'parse_mode' => 'HTML']);
                                break;
                        }
                    }
                }
            }



            $checkLastGame = GR::CheckPlayerInGame();
            if($checkLastGame || RC::CheckExit('GamePl:join_user:'.self::$Dt->user_id)){
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => self::$Dt->L->_('YouInGame'),
                    'parse_mode' => 'HTML'
                ]);
            }


            if(self::$Dt->allow > 0){
                $checkName = GR::CheckNameInGame();
                if($checkName == 0){
                    $max = (RC::CheckExit('GamePl:gameModePlayer') ? SE::GetMaxPl(RC::Get('GamePl:gameModePlayer')) : (RC::CheckExit('max_player') ? RC::Get("max_player") : 45 ));
                    if($max <= GR::CountPlayer()){
                        return Request::sendMessage([
                            'chat_id' => self::$Dt->user_id,
                            'text' => self::$Dt->LG->_('MaxPlayer',array("{0}" => GR::CountPlayer())),
                            'parse_mode' => 'HTML'
                        ]);
                    }
                    if(GR::CheckGameId()){
                        $Mode = RC::Get('GamePl:gameModePlayer');
                        $time = RC::Get( 'timer');
                        $leftTime = $time - time();
                        if($leftTime <= 0 || RC::Get( 'game_state') !== "join"){
                            return false;
                        }

                        $GroupLink = RC::Get('group_link') ?? 0;
                        $gp_name = RC::Get('group_name') ?? 'Unknow';
                        if($GroupLink) {$group_name = '<a href="' . $GroupLink . '">' . $gp_name . '</a>';}else{$group_name = $gp_name;}

                        if(RC::CheckExit('GamePl:join_user:'.self::$Dt->user_id)){
                            return false;
                        }

                        if($Mode == 'coin'){
                            if( (int) self::$Dt->Player['coin'] < 5000){
                                return Request::sendMessage([
                                    'chat_id' => self::$Dt->user_id,
                                    'text' => self::$Dt->L->_('NotAnogthCoin'),
                                    'disable_web_page_preview' => 'true',
                                    'parse_mode' => 'HTML'
                                ]);
                            }
                            GR::UpdateCoin(((int) self::$Dt->Player['coin'] - 9999), self::$Dt->user_id);
                            Request::sendMessage([
                                'chat_id' => self::$Dt->user_id,
                                'text' => self::$Dt->L->_('MinCoin'),
                                'disable_web_page_preview' => 'true',
                                'parse_mode' => 'HTML'
                            ]);
                        }

                        GR::PlayerJoinTheGame();
                        return Request::sendMessage([
                            'chat_id' => self::$Dt->user_id,
                            'text' => self::$Dt->LG->_('JoinTheGame', array("{0}" => $group_name)),
                            'disable_web_page_preview' => 'true',
                            'parse_mode' => 'HTML'
                        ]);

                    }

                    return Request::sendMessage([
                        'chat_id' => self::$Dt->user_id,
                        'text' => self::$Dt->LG->_('NotFoundGameId'),
                        'parse_mode' => 'HTML'
                    ]);
                }else{
                    Request::sendMessage([
                        'chat_id' => self::$Dt->user_id,
                        'text' => self::$Dt->LG->_('NotNameAllow',  array("{0}" => self::$Dt->fullname) ),
                        'parse_mode' => 'HTML'
                    ]);
                }
            }else{
                Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => self::$Dt->LG->_('NotAllowToJoin'),
                    'parse_mode' => 'HTML'
                ]);
            }

        }

    }



    public static function ChangeGroup(){
        $NOp = RC::NoPerfix();
        $UserLastGroup = GR::GetUserLastGroupId();
        Request::editMessageReplyMarkup([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'reply_markup' =>  new InlineKeyboard([]),
        ]);

        if($UserLastGroup['chat_id'] == self::$Dt->chat_id){
            return false;
        }
        GR::ChangeUserGroup();

        if($NOp->exists('change_group:'.self::$Dt->user_id)) {
            $lastChange = $NOp->get('change_group:' . self::$Dt->user_id);
            if($lastChange >= 5){
                return   Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => self::$Dt->L->_('ErrorGroupChange'),
                    'parse_mode' => 'HTML'
                ]);
            }
            $NOp->set('change_group:' . self::$Dt->user_id, (int) $lastChange + 1);

        }else{
            $lastChange = 1;
            $NOp->set('change_group:' . self::$Dt->user_id,   1);
            $NOp->expire('change_group:' . self::$Dt->user_id,28800);
        }





        return   Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('ChangeSuccessGroup',
                $NOp->get($UserLastGroup['chat_id'].":group_name")
                ,$NOp->get(self::$Dt->chat_id.":group_name")
                ,4 - $lastChange
            ),
            'parse_mode' => 'HTML'
        ]);

    }
    public static function SendMessageGroup(){
        $groups = GR::GetGroups();

        foreach ($groups as $row){

            $WhiteList = GR::GetWhiteList($row['chat_id']);
            if(!$WhiteList){
                Request::sendMessage([
                    'chat_id' =>$row['chat_id'],
                    'text' => self::$Dt->L->_('NotGroupAvi'),
                    'parse_mode' => 'HTML'
                ]);

                Request::leaveChat(['chat_id' => $row['chat_id']]);
            }
        }


    }
    public static function BotAddToGroup(){

        Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => self::$Dt->L->_('BotWelcomeToGroup'),
            'parse_mode' => 'HTML'
        ]);
    }
    public static function CM_SetLink(){

        if(!self::$Dt->text || self::$Dt->typeChat == "private"){
            return false;
        }
        if(GR::is_url(self::$Dt->text) == false){
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => self::$Dt->L->_('NotValidUrl'),
                'parse_mode' => 'HTML'
            ]);
        }
        RC::GetSet(self::$Dt->text,'group_link');
        $group_name  =  '<a href="'.self::$Dt->text.'">'.self::$Dt->groupName.'</a>';
        RC::GetSet(self::$Dt->groupName,'group_name');
        GR::UpdateGroupLink(self::$Dt->chat_id,self::$Dt->text);
        return Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => self::$Dt->L->_('SetLinkOk',array("{0}" =>$group_name)),
            'reply_to_message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => 'true'
        ]);
    }
    public static function ReCodeLang($code){
        switch ($code){
            case 'fa':
                return 'فارسی';
                break;
            case 'en':
                return 'English';
                break;
            case 'fr':
                return 'French';
                break;
            default:
                return "Unknown : [{$code}]";
                break;
        }
    }

    /*
     * Set Lang Command Code
     */

    public static function GetLangKeyboad($Callback){
        $allow_LangCode = [];
        $re = [];
        $files = preg_grep('~^main_.*\.ini~', scandir(BASE_DIR . "Strong/Game_Mode/"));
        foreach($files as $file){
            $file = str_replace('main_','',$file);
            $file = str_replace('.ini','',$file);
            if(!in_array($file,$allow_LangCode)){
                array_push($allow_LangCode,$file);
                $re[] =
                    ['text' => self::ReCodeLang($file), 'callback_data' => $Callback.$file ]
                ;

            }
        }

        if($allow_LangCode) {
            $max_per_row = 2; // or however many you want!
            $per_row = sqrt(count($re));
            $rows = array_chunk($re, $per_row === floor($per_row) ? $per_row : $max_per_row);
            $reply_markup = new InlineKeyboard(...$rows);
            return $reply_markup;
        }
        return false;
    }
    public static function CM_Setlang(){

        $reply_markup = self::GetLangKeyboad('UserLang_');
        if($reply_markup) {
            $re = Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('ChangeUserLang',array("{0}" => self::ReCodeLang(self::$Dt->defaultLang))),
                'reply_markup' => $reply_markup,
            ]);
            if($re->isOk()) {
                if (self::$Dt->typeChat !== "private") {
                    Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => "<strong>" . self::$Dt->L->_('pmSendToPrivate') . "</strong>",
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML',
                    ]);
                }
            }else{
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => "<strong>" . self::$Dt->L->_('PleaseStartBot') . "</strong>",
                    'reply_to_message_id' => self::$Dt->message_id,
                    'parse_mode' => 'HTML',
                ]);
            }

        }


    }




    public static function GetGameMode($for){
        self::$Dt->collection->Players->updateOne(
            ['user_id' => self::$Dt->user_id],
            ['$set' => ['def_lang' => $for]]
        );
        $reply_markup = self::_getGameMode($for,'UserGameMode_');
        if($reply_markup) {
            self::$Dt->LM = new Lang(FALSE);
            self::$Dt->LM->load(self::$Dt->default_mode."_".$for, FALSE);

            $re = Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'message_id' => self::$Dt->message_id,
                'text' => self::$Dt->L->_('ChangeGameModeUser', array("{0}" => self::ReCodeLang($for), "{1}" => self::$Dt->LM->_('game_mode'))),
                'reply_markup' => $reply_markup,
            ]);
        }

    }

    public static function _getGameMode($for,$Callback,$AddAll = false){
        $re = [];
        $Allows = [];
        $files = preg_grep('~^.*_'.$for.'\.ini~', scandir(BASE_DIR . "Strong/Game_Mode/"));
        $lst = new Lang(FALSE);

        if($AddAll){
            $re[] =
                ['text' => self::$Dt->L->_('AllGroup'), 'callback_data' => $Callback."all"];
        }

        foreach($files as $file){
            $file = str_replace('_'.$for,'',$file);
            $file = str_replace('.ini','',$file);
            if(!in_array($file,$Allows)  && $file !== "main"){
                array_push($Allows,$file);
                $lst->load($file."_".$for, FALSE);
                $re[] =
                    ['text' => $lst->_('game_mode'), 'callback_data' => $Callback. $file];
            }
        }


        if($Allows) {
            $max_per_row = 2; // or however many you want!
            $per_row = sqrt(count($re));
            $rows = array_chunk($re, $per_row === floor($per_row) ? $per_row : $max_per_row);
            $reply_markup = new InlineKeyboard(...$rows);
            return $reply_markup;
        }

        return false;
    }
    public static function ChangeGameMode($to){
        self::$Dt->collection->Players->updateOne(
            ['user_id' => self::$Dt->user_id],
            ['$set' => ['game_mode' => $to]]
        );
        self::$Dt->LM = new Lang(FALSE);
        self::$Dt->LM->load($to."_".self::$Dt->defaultLang, FALSE);
        Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'text' => self::$Dt->L->_('changedUserLangTo',array("{0}" => self::ReCodeLang(self::$Dt->defaultLang),"{1}" => self::$Dt->LM->_('game_mode'))),
            'parse_mode' => 'HTML',
        ]);

    }


    public static function CM_Help(){
        $site_link = 'https://boldwolf.ir';
        $sup_link = 'https://t.me/BoldwereWolfSupport';
        $group_link = 'https://t.me/BoldwereWolf';
        $edu_link = "https://t.me/BoldwereWolfEdu";

        $array = array("{0}" =>$site_link ,"{1}" =>  $sup_link ,"{2}" =>$group_link ,"{3}" =>$edu_link  );
        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  self::$Dt->L->_('HelpCommand',$array),
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => 'true'
        ]);

    }

    public static function CM_Config(){

        if(self::$Dt->typeChat == "private") {
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' =>  self::$Dt->L->_('SendToGroup'),
                'parse_mode' => 'HTML',
            ]);
        }

        if(self::$Dt->admin == 0){
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => "<strong>" . self::$Dt->L->_('YouNotAdminGp') . "</strong>",
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
            ]);
        }


        $reply_markup = self::GroupConfigKeyboard();
        $re = Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('whoconfig'),
            'reply_markup' => $reply_markup,
        ]);
        if($re->isOk()) {
            if (self::$Dt->typeChat !== "private") {
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => "<strong>" . self::$Dt->L->_('ConfigSendPrvaite') . "</strong>",
                    'reply_to_message_id' => self::$Dt->message_id,
                    'parse_mode' => 'HTML',
                ]);
            }
        }else{
            Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => "<strong>" . self::$Dt->L->_('PleaseStartBot') . "</strong>",
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
            ]);
        }



    }


    public static function GroupConfigKeyboard(){

        return  new InlineKeyboard([
            ['text' => self::$Dt->L->_('Config_time'), 'callback_data' => 'setting_time/'.self::$Dt->chat_id], ['text' => self::$Dt->L->_('config_roles') , 'callback_data' => 'setting_role/'.self::$Dt->chat_id]
        ],[
            ['text' => self::$Dt->L->_('config_games'), 'callback_data' => 'setting_game/'.self::$Dt->chat_id], ['text' => self::$Dt->L->_('config_group') , 'callback_data' => 'setting_group/'.self::$Dt->chat_id]
        ],[
            ['text' => self::$Dt->L->_('config_save'), 'callback_data' => 'config_done']
        ]);

    }

    public static function configDone(){
        Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'text' => self::$Dt->L->_('config_done'),
            'parse_mode' => 'HTML',
        ]);
    }

    public static function BackToConfig(){
        $keyBoard = self::GroupConfigKeyboard();
        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'text' => self::$Dt->L->_('whoconfig'),
            'reply_markup' => $keyBoard,
        ]);
    }
    public static function GetConfigKeyboard($type){

        switch ($type){
            case 'role':
                $keyboard =  new InlineKeyboard([
                    ['text' =>  self::$Dt->L->_('config_role_fool'), 'callback_data' => 'configRoles_Fool/'.self::$Dt->chat_id], ['text' =>  self::$Dt->L->_('config_role_hypocrite') , 'callback_data' => 'configRoles_hypocrite/'.self::$Dt->chat_id]
                ],[
                    ['text' =>  self::$Dt->L->_('config_role_cult'), 'callback_data' => 'configRoles_Cult/'.self::$Dt->chat_id], ['text' =>  self::$Dt->L->_('config_role_Lucifer'), 'callback_data' => 'configRoles_lucifer/'.self::$Dt->chat_id]
                ],[
                    ['text' => self::$Dt->L->_('config_Back'), 'callback_data' => 'backtoconfig/'.self::$Dt->chat_id]
                ]);
                break;
            case 'game':
                $keyboard =   new InlineKeyboard([
                    ['text' => self::$Dt->L->_('config_game_cultHunterExposeRole'), 'callback_data' => 'configGame_cultHunterExposeRole/'.self::$Dt->chat_id], ['text' =>  self::$Dt->L->_('config_game_cultHunterCountNightShow') , 'callback_data' => 'configGame_cultHunterCountNightShow/'.self::$Dt->chat_id]
                ],[
                    ['text' =>  self::$Dt->L->_('config_game_RandomeMode'), 'callback_data' => 'configGame_RandomeMode/'.self::$Dt->chat_id], ['text' => self::$Dt->L->_('config_game_Voting_secretly') , 'callback_data' => 'configGame_VotingSecretly/'.self::$Dt->chat_id]
                ],[
                    ['text' => self::$Dt->L->_('config_game_CountSecretVoting'), 'callback_data' => 'configGame_CountSecretVoting/'.self::$Dt->chat_id], ['text' => self::$Dt->L->_('config_game_PlayerNameSecretVoting') , 'callback_data' => 'configGame_PlayerNameSecretVoting/'.self::$Dt->chat_id]
                ],[
                    ['text' => self::$Dt->L->_('config_Back'), 'callback_data' => 'backtoconfig/'.self::$Dt->chat_id]
                ]);
                break;
            case 'time':
                $keyboard =  new InlineKeyboard([
                    ['text' =>  self::$Dt->L->_('config_time_NightTimer'), 'callback_data' => 'configTimer_night/'.self::$Dt->chat_id], ['text' => self::$Dt->L->_('config_time_DayTimer') , 'callback_data' => 'configTimer_day/'.self::$Dt->chat_id]
                ],[
                    ['text' =>  self::$Dt->L->_('config_time_VotingTimer'), 'callback_data' => 'configTimer_Vote/'.self::$Dt->chat_id], ['text' => self::$Dt->L->_('config_time_SecretVoteTimer') , 'callback_data' => 'configTimer_SectetVote/'.self::$Dt->chat_id]
                ],[
                    ['text' =>  self::$Dt->L->_('config_time_JoinTimer'), 'callback_data' => 'configTimer_join/'.self::$Dt->chat_id], ['text' =>  self::$Dt->L->_('config_time_ExtendTimer') , 'callback_data' => 'configTimer_Extend/'.self::$Dt->chat_id]
                ],[
                    ['text' => self::$Dt->L->_('config_Back'), 'callback_data' => 'backtoconfig/'.self::$Dt->chat_id]
                ]);
                break;
            case 'group':
                $keyboard =   new InlineKeyboard([
                    ['text' =>  self::$Dt->L->_('config_group_Language'), 'callback_data' => 'configGroup_Languge/'.self::$Dt->chat_id], ['text' => self::$Dt->L->_('config_group_gameMode') , 'callback_data' => 'configGroup_GameMode/'.self::$Dt->chat_id]
                ],[
                    ['text' =>  self::$Dt->L->_('config_group_ExposeRole'), 'callback_data' => 'configGroup_ExposeRole/'.self::$Dt->chat_id], ['text' =>self::$Dt->L->_('config_group_ExposeRoleOn') , 'callback_data' => 'configGroup_ExposeRoleOn/'.self::$Dt->chat_id]
                ],[
                    ['text' => self::$Dt->L->_('config_group_showId'), 'callback_data' => 'configGroup_showId/'.self::$Dt->chat_id], ['text' =>self::$Dt->L->_('config_group_Flee') , 'callback_data' => 'configGroup_Flee/'.self::$Dt->chat_id]
                ],[
                    ['text' => self::$Dt->L->_('config_group_MaxPlayer'), 'callback_data' => 'configGroup_MaxPlayer/'.self::$Dt->chat_id], ['text' =>self::$Dt->L->_('config_group_Extend') , 'callback_data' => 'configGroup_Extend/'.self::$Dt->chat_id]
                ],[
                    ['text' =>self::$Dt->L->_('config_group_PinMessage') , 'callback_data' => 'configGroup_PinMessage/'.self::$Dt->chat_id] , ['text' => self::$Dt->L->_('config_group_Roles'), 'callback_data' => 'configGroup_Roles/'.self::$Dt->chat_id]
                ],[
                    ['text' => 'بازگشت', 'callback_data' => 'backtoconfig/'.self::$Dt->chat_id]
                ]);
                break;
            case 'unlockAll':
                GR::UnlockAllRole();
                return self::ConfigGroup("Roles");
                break;

        }

        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'text' => self::$Dt->L->_('whoconfig'),
            'reply_markup' => $keyboard,
        ]);

    }

    public static function ConfigRole($type){
        switch ($type){
            case 'Fool':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/role_fool"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/role_fool"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_role/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("role_fool");
                $text = self::$Dt->L->_('allowNaqshAhmaq',  array("0" => self::$Dt->L->_($current)));
                break;
            case 'hypocrite':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/role_hypocrite"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/role_hypocrite"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_role/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("role_hypocrite");
                $text = self::$Dt->L->_('allowNaqshMonfeq', array("0" => self::$Dt->L->_($current)));
                break;
            case 'Cult':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/role_Cult"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/role_Cult"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_role/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("role_Cult");
                $text = self::$Dt->L->_('allowNaqshferqe', array("0" => self::$Dt->L->_($current)));
                break;
            case 'lucifer':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/role_Lucifer"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/role_Lucifer"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_role/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("role_Lucifer");
                $text = self::$Dt->L->_('allow_lucifer', array("{0}" => self::$Dt->L->_($current)));
                break;
        }

        $data = [
            'chat_id' => self::$Dt->user_id,
            'text' => $text,
            'message_id' => self::$Dt->message_id,
            'reply_markup' => $inline_keyboard,
        ];
        return Request::editMessageText($data);

    }

    public static function ConfigGroup($type){
        switch ($type){
            case 'GameMode':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('Normal'), 'callback_data' => 'configureGroup_Normal/' . self::$Dt->chat_id."/type_mode"]
                    ], [
                    ['text' => self::$Dt->L->_('Chaos'), 'callback_data' => 'configureGroup_Chaos/' . self::$Dt->chat_id."/type_mode"]
                ], [
                    ['text' => self::$Dt->L->_('Players'), 'callback_data' => 'configureGroup_Players/' . self::$Dt->chat_id."/type_mode"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_group/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("type_mode") ?? "Players";
                $text = self::$Dt->L->_('chnageGameMode', array("{0}" => self::ReCodeLang($current)));
                break;
            case 'Languge':
                $inline_keyboard = self::GetLangKeyboad('GroupLang/'.self::$Dt->chat_id."/");
                $current = GR::GetGroupSe("lang") ?? "fa";
                $text = self::$Dt->L->_('ChangeGroupLang', array("{0}" => self::ReCodeLang($current)));
                break;
            case 'ExposeRoleOn':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('show'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/expose_role_after_dead"]
                    ], [
                    ['text' => self::$Dt->L->_('hidden'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/expose_role_after_dead"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_group/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("expose_role_after_dead");
                $text = self::$Dt->L->_('efshaNaqshSetting',array("0"=>  self::$Dt->L->_($current)));
                break;
            case 'PinMessage':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/PinMessage_on_group"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/PinMessage_on_group"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_group/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("PinMessage_on_group");
                $text = self::$Dt->L->_('PinMessage_on_group', array("{0}" => self::$Dt->L->_($current)));
                break;
            case 'Roles':
                $inline_keyboard = GR::RolesKeyboard();
                $text = self::$Dt->L->_('HowToCustomRole');
                break;
            case 'ExposeRole':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onlyUp'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/expose_role"]
                    ], [
                    ['text' => self::$Dt->L->_('rolNo'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/expose_role"]
                ], [
                    ['text' => self::$Dt->L->_('all'), 'callback_data' => 'configureGroup_all/' . self::$Dt->chat_id."/expose_role"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_group/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("expose_role");
                $text = self::$Dt->L->_('HowToshowRol', array("{0}"=>self::$Dt->L->_($current)));
                break;
            case 'Flee':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/Flee"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/Flee"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_group/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("allow_flee");
                $text = self::$Dt->L->_('allowFleeAtGame', array("{0}"=>self::$Dt->L->_($current)));
                break;
            case 'showId':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('show'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/show_user_id"]
                    ], [
                    ['text' => self::$Dt->L->_('hidden'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/show_user_id"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_group/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("show_user_id");
                $text = self::$Dt->L->_('allowShowId', array("{0}"=>self::$Dt->L->_($current)));
                break;
            case 'Extend':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/allow_extend"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/allow_extend"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_group/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("allow_extend");
                $text = self::$Dt->L->_('extendForPlayer',array("{0}"=>self::$Dt->L->_($current)));
                break;
            case 'MaxPlayer':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => 15, 'callback_data' => 'configureGroup_15/' . self::$Dt->chat_id."/max_player"]
                    ], [
                    ['text' => 20, 'callback_data' => 'configureGroup_20/' . self::$Dt->chat_id."/max_player"]
                ], [
                    ['text' => 30, 'callback_data' => 'configureGroup_30/' . self::$Dt->chat_id."/max_player"]
                ], [
                    ['text' => 35, 'callback_data' => 'configureGroup_35/' . self::$Dt->chat_id."/max_player"]
                ], [
                    ['text' => 45, 'callback_data' => 'configureGroup_45/' . self::$Dt->chat_id."/max_player"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_group/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("max_player") ?? 35;
                $text = self::$Dt->L->_('MaxPlayerJoin',array("{0}"=>$current));
                break;

        }

        $data = [
            'chat_id' => self::$Dt->user_id,
            'text' => $text,
            'message_id' => self::$Dt->message_id,
            'reply_markup' => $inline_keyboard,
        ];
        return Request::editMessageText($data);

    }


    public static function ConfigTimer($type){
        switch ($type){
            case 'day':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => 60, 'callback_data' => 'configureGroup_60/' . self::$Dt->chat_id."/day_timer"]
                    ], [
                    ['text' => 90, 'callback_data' => 'configureGroup_90/' . self::$Dt->chat_id."/day_timer"]
                ], [
                    ['text' => 120, 'callback_data' => 'configureGroup_120/' . self::$Dt->chat_id."/day_timer"]
                ], [
                    ['text' => 180, 'callback_data' => 'configureGroup_180/' . self::$Dt->chat_id."/day_timer"]
                ], [
                    ['text' => 300, 'callback_data' => 'configureGroup_300/' . self::$Dt->chat_id."/day_timer"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_time/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("day_timer") ?? 90;
                $text = self::$Dt->L->_('timeDayFaq',array("{0}"=>$current));
                break;
            case 'night':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => 60, 'callback_data' => 'configureGroup_60/' . self::$Dt->chat_id."/night_timer"]
                    ], [
                    ['text' => 90, 'callback_data' => 'configureGroup_90/' . self::$Dt->chat_id."/night_timer"]
                ], [
                    ['text' => 120, 'callback_data' => 'configureGroup_120/' . self::$Dt->chat_id."/night_timer"]
                ], [
                    ['text' => 180, 'callback_data' => 'configureGroup_180/' . self::$Dt->chat_id."/night_timer"]
                ], [
                    ['text' => 300, 'callback_data' => 'configureGroup_300/' . self::$Dt->chat_id."/night_timer"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_time/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("night_timer") ?? 90;
                $text = self::$Dt->L->_('timeNightTimer',array("{0}"=>$current));
                break;
            case 'Vote':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => 90, 'callback_data' => 'configureGroup_90/' . self::$Dt->chat_id."/vote_timer"]
                    ], [
                    ['text' => 120, 'callback_data' => 'configureGroup_120/' . self::$Dt->chat_id."/vote_timer"]
                ], [
                    ['text' => 180, 'callback_data' => 'configureGroup_180/' . self::$Dt->chat_id."/vote_timer"]
                ], [
                    ['text' => 300, 'callback_data' => 'configureGroup_300/' . self::$Dt->chat_id."/vote_timer"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_time/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("vote_timer") ?? 90;
                $text = self::$Dt->L->_('lynchTimerFq',array("{0}"=>$current));
                break;
            case 'SectetVote':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => 90, 'callback_data' => 'configureGroup_90/' . self::$Dt->chat_id."/secret_timer"]
                    ], [
                    ['text' => 120, 'callback_data' => 'configureGroup_120/' . self::$Dt->chat_id."/secret_timer"]
                ], [
                    ['text' => 180, 'callback_data' => 'configureGroup_180/' . self::$Dt->chat_id."/secret_timer"]
                ], [
                    ['text' => 300, 'callback_data' => 'configureGroup_300/' . self::$Dt->chat_id."/vote_timer"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_time/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("secret_timer") ?? 90;
                $text = self::$Dt->L->_('lynchFqt',array("{0}"=>$current));
                break;
            case 'join':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => 60, 'callback_data' => 'configureGroup_60/' . self::$Dt->chat_id."/join_timer"]
                    ], [
                    ['text' => 90, 'callback_data' => 'configureGroup_90/' . self::$Dt->chat_id."/join_timer"]
                ], [
                    ['text' => 120, 'callback_data' => 'configureGroup_120/' . self::$Dt->chat_id."/join_timer"]
                ], [
                    ['text' => 180, 'callback_data' => 'configureGroup_180/' . self::$Dt->chat_id."/join_timer"]
                ], [
                    ['text' => 300, 'callback_data' => 'configureGroup_300/' . self::$Dt->chat_id."/join_timer"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_time/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("join_timer") ?? 90;
                $text = self::$Dt->L->_('timeJoinTimer',array("{0}"=>$current));
                break;
            case 'Extend':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => 60, 'callback_data' => 'configureGroup_60/' . self::$Dt->chat_id."/max_extend_timer"]
                    ], [
                    ['text' => 90, 'callback_data' => 'configureGroup_90/' . self::$Dt->chat_id."/max_extend_timer"]
                ], [
                    ['text' => 120, 'callback_data' => 'configureGroup_120/' . self::$Dt->chat_id."/max_extend_timer"]
                ], [
                    ['text' => 180, 'callback_data' => 'configureGroup_180/' . self::$Dt->chat_id."/max_extend_timer"]
                ], [
                    ['text' => 300, 'callback_data' => 'configureGroup_300/' . self::$Dt->chat_id."/max_extend_timer"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_time/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("max_extend_timer") ?? 90;
                $text = self::$Dt->L->_('maxTimesetting',array("{0}"=>$current));
                break;

        }
        $data = [
            'chat_id' => self::$Dt->user_id,
            'text' => $text,
            'message_id' => self::$Dt->message_id,
            'reply_markup' => $inline_keyboard,
        ];
        return Request::editMessageText($data);
    }

    public static function ConfigGame($type){
        switch ($type){
            case 'cultHunterExposeRole':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/cult_hunter_expose_role"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/cult_hunter_expose_role"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_game/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("cult_hunter_expose_role");
                $text = self::$Dt->L->_('Hunting_shekar', array("0" => self::$Dt->L->_($current)));
                break;
            case 'cultHunterCountNightShow':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => 1, 'callback_data' => 'configureGroup_1/' . self::$Dt->chat_id."/cultHunter_NightShow"]
                    ], [
                    ['text' => 2, 'callback_data' => 'configureGroup_2/' . self::$Dt->chat_id."/cultHunter_NightShow"]
                ], [
                    ['text' => 3, 'callback_data' => 'configureGroup_3/' . self::$Dt->chat_id."/cultHunter_NightShow"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_game/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("cultHunter_NightShow") ?? 2;
                $text = self::$Dt->L->_('Hunting_shekar', array("0" => $current));
                break;
            case 'VotingSecretly':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/secret_vote"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/secret_vote"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_game/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("secret_vote");
                $text = self::$Dt->L->_('SecretVoteEnable',array("0" => self::$Dt->L->_($current)));
                break;
            case 'RandomeMode':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/randome_mode"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/randome_mode"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_game/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("randome_mode");
                $text = self::$Dt->L->_('allowRandomMode', array("0" => self::$Dt->L->_($current)));
                break;
            case 'CountSecretVoting':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/secret_vote_count"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/secret_vote_count"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_game/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("secret_vote_count");
                $text = self::$Dt->L->_('type_hide_vote_end', array("0" => self::$Dt->L->_($current)));
                break;
            case 'PlayerNameSecretVoting':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/secret_vote_name"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/secret_vote_name"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_game/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("secret_vote_name");
                $text = self::$Dt->L->_('type_hide_vote_show_userName', array("0" => self::$Dt->L->_($current)));
                break;
        }

        $data = [
            'chat_id' => self::$Dt->user_id,
            'text' => $text,
            'message_id' => self::$Dt->message_id,
            'reply_markup' => $inline_keyboard,
        ];
        return Request::editMessageText($data);
    }
    public static function ChangeGroupConfig($key,$val){
        $back = true;
        switch ($key){
            case 'role_fool':
                $change = self::$Dt->L->_('role_fool_change',  array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'role_hypocrite':
                $change = self::$Dt->L->_('role_hypocrite_change',  array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'role_Cult':
                $change = self::$Dt->L->_('role_Cult_change',  array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'role_Lucifer':
                $change = self::$Dt->L->_('role_Lucifer_change',  array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'type_mode':
                $change = self::$Dt->L->_('TypeModeChangedTo',  array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'expose_role_after_dead':
                $change = self::$Dt->L->_('changeEfshaNaqsh',  array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'expose_role':
                $change = self::$Dt->L->_('rolChnaged',  array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'PinMessage_on_group':
                $change = self::$Dt->L->_('PinMessage_on_groupChange', array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'Flee':
                $change = self::$Dt->L->_('fleeSettingChanged',  array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'show_user_id':
                $change = self::$Dt->L->_('chnagedShowId',  array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'allow_extend':
                $change = self::$Dt->L->_('extendforPlayerChang',  array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'max_player':
                $change = self::$Dt->L->_('changeMaxPlayer',array("{0}"=> $val));
                break;
            case 'day_timer':
                $change = self::$Dt->L->_('chnageDayTimerSetting',array("{0}"=> $val));
                break;
            case 'night_timer':
                $change = self::$Dt->L->_('chnageNightTimerSetting',array("{0}"=> $val));
                break;
            case 'vote_timer':
                $change = self::$Dt->L->_('ChangeVoteTimer',array("{0}"=> $val));
                break;
            case 'secret_timer':
                $change = self::$Dt->L->_('changelynchTimer',array("{0}"=> $val));
                break;
            case 'join_timer':
                $change = self::$Dt->L->_('changeJoinTimer',array("{0}"=> $val));
                break;
            case 'max_extend_timer':
                $change = self::$Dt->L->_('chnagedMaxTimeJoin',array("{0}"=> $val));
                break;
            case 'cultHunter_NightShow':
                $change = self::$Dt->L->_('changeHuntingShekarDay', array("{0}"=> $val));
                break;
            case 'cult_hunter_expose_role':
                $change = self::$Dt->L->_('changeHuntingShekar', array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'secret_vote':
                $change = self::$Dt->L->_('SecretVoteEnableChange', array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'randome_mode':
                $change = self::$Dt->L->_('chnagedRandMode', array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'secret_vote_count':
                $change = self::$Dt->L->_('type_hide_vote_end_l', array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'secret_vote_name':
                $change = self::$Dt->L->_('type_hide_vote_show_userName_l', array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'role_rosta':
            case 'role_feramason':
            case 'role_pishgo':
            case 'role_karagah':
            case 'role_elahe':
            case 'role_tofangdar':
            case 'role_rishSefid':
            case 'role_Gorgname':
            case 'role_Nazer':
            case 'role_Hamzad':
            case 'role_kalantar':
            case 'role_Fereshte':
            case 'role_Ahangar':
            case 'role_KhabGozar':
            case 'role_Khaen':
            case 'role_Kadkhoda':
            case 'role_Mast':
            case 'role_Vahshi':
            case 'role_Shahzade':
            case 'role_faheshe':
            case 'role_ngativ':
            case 'role_ahmaq':
            case 'role_PishRezerv':
            case 'role_PesarGij':
            case 'role_NefrinShode':
            case 'role_Solh':
            case 'role_shekar':
            case 'role_clown':
            case 'role_Ruler':
            case 'role_Spy':
            case 'role_Sweetheart':
            case 'role_Knight':
            case 'role_Botanist':
            case 'role_Watermelon':
            case 'role_monafeq':
            case 'role_ferqe':
            case 'role_Royce':
            case 'role_Qatel':
            case 'role_Archer':
            case 'role_lucifer':
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
            case 'role_trouble':
            case 'role_Chemist':
            case 'role_Augur':
            case 'role_GraveDigger':
                $getKey = (RC::CheckExit($key) ?  RC::Get($key) : "off");
                $val = ($getKey == "on" ? "off" : "on");
                $back = false;
                break;
        }


        if(isset($change)) {
            Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('changedSetting', array("{0}" => $change)),
                'parse_mode' => 'HTML',
            ]);
        }

        GR::ChangeConfig($val,$key);

        if($back) {
            self::BackToConfig();
        }else{
            self::ConfigGroup("Roles");
        }
    }


    public static function ChangeGroupLang($to){
        RC::GetSet($to,'lang');
        $inline_keyboard = self::_getGameMode($to,'ChangeGroupGameMode/'.self::$Dt->chat_id."/");
        $data = [
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('config_changeLang',array("{0}" => self::$Dt->L->_((RC::CheckExit('game_mode') ? RC::Get('game_mode') : "general") ))),
            'message_id' => self::$Dt->message_id,
            'reply_markup' => $inline_keyboard,
        ];
        return Request::editMessageText($data);
    }

    public static function ChangeGroupGameMode($to){
        RC::GetSet($to,'game_mode');
        self::$Dt->LM = new Lang(FALSE);
        self::$Dt->LM->load($to."_".RC::Get('lang'), FALSE);
        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  self::$Dt->L->_('langChangeTo',array("{0}" => self::$Dt->LM->_('game_mode'))),
            'parse_mode' => 'HTML',
        ]);
        self::BackToConfig();
        return true;
    }

    public static function CM_Players(){
        if(self::$Dt->typeChat !== "private") {
            $checkStartGame = GR::CheckGPGameState();
            switch ($checkStartGame){
                case 2:
                case 1:
                    $Message_id = RC::Get('Player_ListMessage_ID');
                    if($Message_id){
                        $re = Request::sendMessage([
                            'chat_id' => self::$Dt->chat_id,
                            'text' => self::$Dt->LG->_('playerList'),
                            'reply_to_message_id' => $Message_id,
                        ]);
                        if($re->isOk()) {
                            RC::rpush($re->getResult()->getMessageId(),'deleteMessage');
                        }
                    }
                    break;
                default:
                    return false;
                    break;
            }
        }
        return false;
    }
    public static function CM_Join(){
        if(self::$Dt->typeChat !== "private") {
            $checkStartGame = GR::CheckGPGameState();
            switch ($checkStartGame){
                case 0:
                    Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->LG->_('GameNotCreate'),
                        'parse_mode' => 'HTML'
                    ]);
                    break;
                case 2:
                    $inline_keyboard = new InlineKeyboard(
                        [
                            ['text' => self::$Dt->LG->_('joinToGame'), 'url' => self::$Dt->JoinLink]
                        ]

                    );
                    $result = Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->LG->_('startLastGame'),
                        'reply_markup' => $inline_keyboard,
                    ]);
                    if($result->isOk()) {
                        RC::rpush($result->getResult()->getMessageId(),'deleteMessage');
                    }

                    break;
                case 3:
                    $inline_keyboard = new InlineKeyboard(
                        [
                            ['text' => self::$Dt->LG->_('JoinChallenge'), 'url' => self::$Dt->ChallengeJoin]
                        ]

                    );
                    $result = Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->LG->_('StartLastChallenge'),
                        'reply_markup' => $inline_keyboard,
                    ]);
                    if($result->isOk()) {
                        RC::rpush($result->getResult()->getMessageId(),'ch:deleteMessage');
                    }
                    break;
            }
        }
    }


    public static function CM_StartGame($Mode){


        if(!self::$Dt->typeChat){
            die('');
        }
        if(self::$Dt->typeChat == "private") {
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' =>  self::$Dt->L->_('SendToGroup'),
                'parse_mode' => 'HTML',
            ]);
        }


        /*
        $Array = [-1001162150617];
        if(!in_array(self::$Dt->chat_id,$Array)){
            return Request::sendMessage(['chat_id' => self::$Dt->chat_id,
                'text' => self::$Dt->L->_('BotInMen'),
                'parse_mode' => 'HTML']);
        }
        */
        /*
                $NoP= RC::NoPerfix();

                if(!$NoP->exists(self::$Dt->chat_id.':group_link')){

                    return  Request::sendMessage(['chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('NotLinkSet'),
                        'parse_mode' => 'HTML']);
                }

               */




        $White = [-1001172296255,-1001479992705,-1001421353562,-1001162150617];

        if(!in_array(self::$Dt->chat_id,self::$Dt->AllowGroups)){
            return Request::leaveChat(['chat_id'=> self::$Dt->chat_id]);
        }

        $CheckBan = GR::CheckUserInBan(self::$Dt->user_id);
        if($CheckBan){
            if($CheckBan['state'] == false){
                if(isset($CheckBan['key'])) {
                    switch ($CheckBan['key']) {
                        case 'ban_ever':
                            $UserLang = self::$Dt->L->_($CheckBan['key']);
                            return Request::sendMessage(['chat_id' => self::$Dt->user_id,
                                'text' => $UserLang,
                                'parse_mode' => 'HTML']);
                            break;
                        case 'ban_to':
                            $UserLang = self::$Dt->L->_($CheckBan['key'],array("{0}" => jdate('Y-m-d H:i:s',$CheckBan['time'])));
                            return Request::sendMessage(['chat_id' => self::$Dt->user_id,
                                'text' => $UserLang,
                                'parse_mode' => 'HTML']);
                            break;
                    }
                }
            }
        }

        if(self::$Dt->typeChat !== "private") {
            $checkStartGame = GR::CheckGPGameState();
            switch ($checkStartGame){
                case 0:
                    if(!RC::CheckExit('SetUpRoles')){
                        GR::UnlockAllRole();
                        RC::GetSet(true,'SetUpRoles');
                    }

                    if($Mode == "Vampire"){
                        if(RC::Get('role_Vampire') == "off" || RC::Get('role_Bloodthirsty') == "off"){
                            return   $results = Request::sendMessage([
                                'chat_id' => self::$Dt->chat_id,
                                'text' => self::$Dt->L->_('DisabledVampireMode'),
                            ]);
                        }
                    }

                    GR::StartGameForGroup();
                    RC::GetSet($Mode,'GamePl:gameModePlayer');
                    $inline_keyboard = new InlineKeyboard(
                        [
                            ['text' => self::$Dt->LG->_('joinToGame'), 'url' => self::$Dt->JoinLink ]
                        ]
                    );
                    $result = Request::sendVideo([
                        'chat_id' => self::$Dt->chat_id,
                        'video' => RC::RandomGif('start_game',$Mode),
                        'caption' => self::$Dt->LG->_('startAtGame_'.$Mode, array("{0}" => '<a href="tg://user?id=' . self::$Dt->user_id . '">' . self::$Dt->fullname . '</a>' )).PHP_EOL.self::$Dt->LG->_('StartGameFooter'),
                        'parse_mode' => 'HTML',
                        'reply_markup' => $inline_keyboard,
                    ]);
                    if($result->isOk()){
                        RC::rpush($result->getResult()->getMessageId(),'EditMarkup');
                    }else{
                        Request::sendMessage([
                            'chat_id' => self::$Dt->chat_id,
                            'text' => self::$Dt->L->_('NotBotEnableGifOnGroup'),

                        ]);
                    }
                    $results = Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->LG->_('player'),
                    ]);
                    if($results->isOk()){
                        if(RC::Get('PinMessage_on_group') == "onr") {
                            Request::pinChatMessage(['chat_id' => self::$Dt->chat_id, "message_id" => $results->getResult()->getMessageId()]);
                        }
                        RC::GetSet($results->getResult()->getMessageId(),'Player_ListMessage_ID');
                    }
                    break;
                case 2:
                    $inline_keyboard = new InlineKeyboard(
                        [
                            ['text' => self::$Dt->LG->_('joinToGame'), 'url' => self::$Dt->JoinLink]
                        ]

                    );
                    $result = Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->LG->_('startLastGame'),
                        'reply_markup' => $inline_keyboard,
                    ]);
                    if($result->isOk()) {
                        RC::rpush($result->getResult()->getMessageId(),'deleteMessage');
                    }

                    break;
                case 3:
                    $inline_keyboard = new InlineKeyboard(
                        [
                            ['text' => self::$Dt->LG->_('JoinChallenge'), 'url' => self::$Dt->ChallengeJoin]
                        ]

                    );
                    $result = Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->LG->_('StartLastChallenge'),
                        'reply_markup' => $inline_keyboard,
                    ]);
                    if($result->isOk()) {
                        RC::rpush($result->getResult()->getMessageId(),'ch:deleteMessage');
                    }
                    break;
                default:

                    return false;
                    break;
            }
        }else{
            Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' =>  self::$Dt->LG->_('GameStartOnGroup'),
                'parse_mode' => 'HTML',
            ]);
        }

    }


    public static function CM_Extend(){
        $status = GR::CheckGPGameState();
        switch ($status){
            case 0:
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->LG->_('GameNotCreate'),
                    'parse_mode' => 'HTML'
                ]);
                break;
            case 2:
                if(RC::Get('allow_extend') == "offr" and self::$Dt->admin == 0){
                    return  Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => "<strong>" . self::$Dt->L->_('AllowExtendForAdmin') . "</strong>",
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML',
                    ]);
                }
                if(!is_numeric(self::$Dt->text)){
                    self::$Dt->text = 30;
                }
                if(self::$Dt->admin == 0 and self::$Dt->text < 0){
                    return  Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => "<strong>" . self::$Dt->L->_('NotAllowUserminusExtend') . "</strong>",
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML',
                    ]);
                }
                $times = RC::Get('timer') - time();
                if($times <= 0 ){
                    return false;
                }
                $re = GR::ExtendToGame();
                $text = ($re['extTime'] < 0) ? self::$Dt->LG->_('ExtendToTimeManfi',array("{0}"=> $re['extTime'],"{1}" => $re['ToLeft'])) : self::$Dt->LG->_('ExtendToTime',array("{0}"=> $re['extTime'], "{1}" =>$re['ToLeft']));
                $re = Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                ]);
                if($re->isOk()) {
                    RC::rpush($re->getResult()->getMessageId(),'deleteMessage');
                }
                break;
            default:
                return false;
                break;
        }
    }

    public static function CM_Flee(){
        $status = GR::CheckGPGameState();
        switch ($status) {
            case 2:
                if(RC::Get('allow_flee') == "offr" and self::$Dt->admin == 0){
                    return  Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('NotAllowFlee'),
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML',
                    ]);
                }
                if(!GR::CheckPlayerJoined()){
                    return  Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('NotInGameForFlee'),
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML',
                    ]);
                }
                GR::UserFlee();
                Request::deleteMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'message_id' => self::$Dt->message_id,
                ]);
                return  Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->LG->_('okFlee',array("{0}" => self::$Dt->user_link)).PHP_EOL.self::$Dt->LG->_('FleeCoutPlayer',array("{0}" => GR::CountPlayer())),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 1:
                return  Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => "<strong>" . self::$Dt->L->_('NotAllowFleeInGame') . "</strong>",
                    'reply_to_message_id' => self::$Dt->message_id,
                    'parse_mode' => 'HTML',
                ]);
                break;
            default:
                return false;
                break;
        }
    }


    public static function CM_Nextgame(){
        if(self::$Dt->typeChat !== "private"){

            $GroupName = ( RC::Get('group_link') !== "") ? '<a href="' . RC::Get('group_link') . '">' . RC::Get('group_name') . '</a>' : RC::Get('group_name') ;
            $checkPlayerNextGame = GR::CheckPlayerInNextGame();
            if($checkPlayerNextGame){
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('cancele_ok'), 'callback_data' => 'cancel_nextgame/'.self::$Dt->chat_id]
                    ]
                );
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => self::$Dt->L->_('AlreadyOnWaitList',array("{0}" => $GroupName)),
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inline_keyboard,
                    'disable_web_page_preview' => 'true',
                ]);
            }
            GR::AddPlayerToNextGame();
            $inline_keyboard = new InlineKeyboard(
                [
                    ['text' => self::$Dt->L->_('cancele_ok'), 'callback_data' => 'cancel_nextgame/'.self::$Dt->chat_id]
                ]
            );
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('AddedToWaitList',array("{0}" => $GroupName)),
                'parse_mode' => 'HTML',
                'reply_markup' => $inline_keyboard,
                'disable_web_page_preview' => 'true',
            ]);
        }
    }

    public static function cancel_nextgame(){
        Request::editMessageReplyMarkup([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'reply_markup' =>  new InlineKeyboard([]),
        ]);
        GR::RemoveFromNextGame();
    }

    public static function CM_ForceStart(){
        if(self::$Dt->typeChat !== "private") {

            if(self::$Dt->admin == 0){
                return  Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->L->_('NotAllowForUser'),
                    'reply_to_message_id' => self::$Dt->message_id,
                    'parse_mode' => 'HTML',
                ]);
            }

            $status = GR::CheckGPGameState();
            switch ($status) {
                case 0:
                    return Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->LG->_('GameNotCreate'),
                        'parse_mode' => 'HTML'
                    ]);
                    break;
                case 2:
                    RC::GetSet(0, 'timer');
                    break;
                case 1:
                    return false;
                    break;
                default:
                    return false;
                    break;
            }


        }
    }

    public static function CM_Addtest(){
        if(self::$Dt->admin == 0){
            return false;
        }
        $ar = [
            [ 'user_id' => 679902906, 'name' => 'king amir'],
            [ 'user_id' => 769689740, 'name' => 'noghte'],
            [ 'user_id' => 630127836, 'name' => 'amir karimi'],
            [ 'user_id' => 556635252, 'name' => 'khalil'],
            [ 'user_id' => 764859315, 'name' => 'ᎬᏙᏆᏞ'],
        ];
        foreach ($ar as $item) {
            GR::Addtest($item['name'],$item['user_id']);
        }
        return  Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => 'Added',
            'reply_to_message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
        ]);

    }

    public static function NightSelectedCheck($Selected){
        $Ex = explode('/',self::$Dt->data);
        $user_id =  $Ex['2'];

        if($Selected == "LuciferSelectTeam"){
            $user_id = self::$Dt->user_id;
        }

        if(self::$Dt->in_game == 0){
            return self::Error(self::$Dt->L->_('Error_NotInGame'));
        }
        $U_D = GR::_GetPlayer($user_id);

        if($U_D == false){
            return Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('NotFoundPlayer',array("{0}" =>$user_id)),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }

        if(RC::Get('game_state') !== "night"){
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');

            return Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('endTime'),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }
        $Name = GR::ConvertName($user_id,$U_D['fullname_game']);

        $MeRole = self::$Dt->user_role."_n";

        if(RC::CheckExit('GamePl:Selected:'.self::$Dt->user_id.":user")){
            return false;
        }

        $Team = false;

        switch ($Selected){
            case 'Hamzad':
                // چک کن نقشش با درخواست ارسالی هماهنگ باشه
                if(self::$Dt->user_role !== "role_Hamzad"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Hamzad');
                break;
            case 'Lucifer':
                // چک کن نقشش با درخواست ارسالی هماهنگ باشه
                if(self::$Dt->user_role !== "role_lucifer"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user");
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::GetSet(true,'GamePl:role_lucifer:checkLucifer');
                break;
            case 'LiLis':
                if(self::$Dt->user_role !== "role_Lilis"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
                case 'viego':
                if(self::$Dt->user_role !== "role_viego"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'babr':
                if(self::$Dt->user_role !== "role_babr"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
           break;
            case 'BrideTheDead':
                if(self::$Dt->user_role !== "role_BrideTheDead"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                break;

                case 'Orlok':
                if(self::$Dt->user_role !== "role_orlok"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Dozd':
                if(self::$Dt->user_role !== "role_dozd"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
                case 'wolfsilver':
                if(self::$Dt->user_role !== "role_wolfsilver"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'LuciferSelectTeam':
                // چک کن نقشش با درخواست ارسالی هماهنگ باشه
                if(self::$Dt->user_role !== "role_lucifer"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                $Team =  $Ex['2'];
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user");
                RC::GetSet($Team,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Cupe':
                if(self::$Dt->user_role !== "role_elahe"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:lover');
                RC::GetSet($Name,'GamePl:namer:love');

                $rows = GR::GetPlayerNonKeyboard([$user_id], 'NightSelect_Cupe2');
                $inline_keyboard = new InlineKeyboard(...$rows);
                return Request::editMessageText([
                    'chat_id' => self::$Dt->user_id,
                    'text' => self::$Dt->LG->_('AskCupid2'),
                    'message_id' => self::$Dt->message_id,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inline_keyboard,
                ]);
                break;
            case 'Cupe2':
                if(self::$Dt->user_role !== "role_elahe"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }

                RC::GetSet($user_id,'GamePl:love:'.RC::Get('GamePl:lover'));
                RC::GetSet($Name,'GamePl:name:love:'.RC::Get('GamePl:lover'));

                RC::GetSet(RC::Get('GamePl:lover'),'GamePl:love:'.$user_id);
                RC::GetSet(RC::Get('GamePl:namer:love'),'GamePl:name:love:'.$user_id);

                break;
            case 'Vahshi':
                if(self::$Dt->user_role !== "role_Vahshi"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Olgo');
                RC::GetSet($Name,'GamePl:OlgoName');
                break;
            case 'Firefighter':
                if(self::$Dt->user_role !== "role_Firefighter"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user");
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::rpush(['user_id'=>$user_id,'fullname'=> $U_D['fullname_game'],'link' => $Name,'role'=> $U_D['user_role']],'GamePl:FirefighterList','json');
                break;
            case 'Honey':
                if(self::$Dt->user_role !== "role_Honey"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user");
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
             case 'IceDragon':
                if(self::$Dt->user_role !== "role_IceDragon"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user");
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
             break;
            case 'KentVampire':
                if(self::$Dt->user_role !== "role_kentvampire"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user");
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'IceQueen':
                if(self::$Dt->user_role !== "role_IceQueen"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user");
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::rpush(['user_id'=>$user_id,'fullname'=> $U_D['fullname_game'],'link' => $Name,'role'=> $U_D['user_role']],'GamePl:role_IceQueen:'.$user_id,'json');
                break;

            case 'Shekar':
                if(self::$Dt->user_role !== "role_shekar"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                break;
            case 'Fool':
                if(self::$Dt->user_role !== "role_ahmaq"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
                case 'Phoenix':
                if(self::$Dt->user_role !== "role_Phoenix"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Negativ':
                if(self::$Dt->user_role !== "role_ngativ"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
           case 'Mouse':
                if(self::$Dt->user_role !== "role_Mouse"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
           case 'Khalifa':
                if(self::$Dt->user_role !== "role_Khalifa"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Natasha':
                if(self::$Dt->user_role !== "role_faheshe"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                RC::GetSet($user_id,'GamePl:role_faheshe:inhome:'.$user_id);

                break;

            case 'Archer':
                if(self::$Dt->user_role !== "role_Archer"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Joker':
                if(self::$Dt->user_role !== "role_Joker"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
           break;
           case 'Harly':
                if(self::$Dt->user_role !== "role_Harly"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
           break;
           case 'Cow':
                if(self::$Dt->user_role !== "role_Cow"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
               RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
               RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
               RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
           break;
            case 'Watermelon':
                if(self::$Dt->user_role !== "role_Watermelon"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Knight':
                if(self::$Dt->user_role !== "role_Knight"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                break;
            case 'Killer':
                if(self::$Dt->user_role !== "role_Qatel"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                break;
                case 'Hilda':
                if(self::$Dt->user_role !== "role_hilda"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                break;
            case 'Angel':
                if(self::$Dt->user_role !== "role_Fereshte"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($Name,'GamePl:role_angel:AngelNameSaved');
                RC::GetSet(self::$Dt->user_id,'GamePl:role_angel:AngelIn:'.$user_id);
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                break;
            case 'WhiteWolf':
                if(self::$Dt->user_role !== "role_WhiteWolf"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($Name,'GamePl:role_WhiteWolf:AngelNameSaved');
                RC::GetSet(self::$Dt->user_id,'GamePl:role_WhiteWolf:AngelIn:'.$user_id);
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                break;
             case 'morgana':
                if(self::$Dt->user_role !== "role_morgana"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($Name,'GamePl:role_morgana:AngelNameSaved');
                RC::GetSet(self::$Dt->user_id,'GamePl:role_morgana:AngelIn:'.$user_id);
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                break;
            case 'Feranc':
                if(self::$Dt->user_role !== "role_franc"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                if(!RC::CheckExit('GamePl:FrancNightOk')) {
                    RC::GetSet($Name, 'GamePl:role_franc:AngelNameSaved');
                    RC::GetSet(self::$Dt->user_id, 'GamePl:role_franc:AngelIn:' . $user_id);
                    RC::GetSet($user_id, 'GamePl:UserInHome:' . self::$Dt->user_id);
                    RC::GetSet(self::$Dt->user_link, 'GamePl:UserInHome:' . self::$Dt->user_id . ":name");
                    RC::GetSet(self::$Dt->LG->_($MeRole), 'GamePl:UserInHome:' . self::$Dt->user_id . ":role");
                }
                break;
            case 'Wolf':
                $Wolf_role = SE::WolfRole();

                if(self::$Dt->user_role == "role_forestQueen"){
                    if (RC::CheckExit('GamePl:role_forestQueen:AlphaDead')) {
                        array_push($Wolf_role,'role_forestQueen');
                    }
                }

                if(!in_array(self::$Dt->user_role,$Wolf_role)){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user");
                $countTeam = GR::_GetByTeam('wolf');
                if(count($countTeam) > 1){
                    $msg = self::$Dt->LG->_('eatUser',array("{0}"=>self::$Dt->user_link,"{1}" => $Name));
                    GR::SendForWolfTeam($msg,true);
                    RC::rpush(self::$Dt->user_id,'GamePl:Selected:Wolf:'.$user_id);
                    RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                }else{
                    RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                    RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                    RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                    RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                }

                break;
            case 'Vampire':
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user");
                $countTeam = GR::_GetByTeam('vampire');
                if(count($countTeam) > 1){
                    $msg = (RC::CheckExit('GamePl:VampireFinded') ? self::$Dt->LG->_('MessageGoHomeFinde',array("{0}"=> self::$Dt->user_link, "{1}" => $Name)) : self::$Dt->LG->_('MessageGoHome',array("{0}"=> self::$Dt->user_link, "{1}" =>$Name)));
                    GR::SendForVampireTeam($msg,self::$Dt->user_id);
                    RC::rpush(self::$Dt->user_id,'GamePl:Selected:Vampire:'.$user_id);
                    RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                }else{
                    RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                    RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                    RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                    RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                }
                break;
            case 'Enchanter':
                if(self::$Dt->user_role !== "role_enchanter"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Chemist':
                if(self::$Dt->user_role !== "role_Chemist"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Ferqe':
                if(self::$Dt->user_role !== "role_ferqe"){
                    if(self::$Dt->user_role !== "role_Royce") {
                        return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    }
                }
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user");
                $countTeam = GR::_GetByTeam('ferqeTeem');
                if(count($countTeam) > 1){
                    $msg = self::$Dt->LG->_('CultistVotedConvert',array("{0}" => self::$Dt->user_link,"{1}" => $Name));
                    GR::SendForCultTeam($msg,true);
                    RC::rpush(self::$Dt->user_id,'GamePl:Selected:Cult:'.$user_id);
                    RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                }else{
                    RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                }
                break;
            case 'Sear':
                if(self::$Dt->user_role !== "role_pishgo"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Huntsman':
                if(self::$Dt->user_role !== "role_Huntsman"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Jado':
                if(self::$Dt->user_role !== "role_WolfJadogar"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
        }

        RC::GetSet(self::$Dt->message_id,'GamePl:new:MessageNightSend:'.self::$Dt->user_id);


        RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->LG->_('SelectOk',array("{0}" => ($Team ? self::GetTeam($Team) : $U_D['fullname']))),
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);
    }

    public static function GetTeam($Team){
        switch ($Team){
            case 'wolf':
                return "تیم گرگ";
                break;
            case 'rosta':
                return "تیم روستا";
                break;
            case 'vampire':
                return "تیم ومپایر";
                break;
            case 'ferqeTeem':
                return "تیم فرقه";
                break;
            case 'qatel':
                return "تیم قاتل";
                break;
            default:
                return "نامشخص";
                break;
        }
    }

    public static function FighterFight(){
        if(RC::Get('GamePl:Night_no') > 0 && RC::CheckExit('GamePl:FirefighterList')) {
            RC::GetSet(true,'GamePl:FirefighterOk');
            RC::GetSet(self::$Dt->message_id,'GamePl:new:MessageNightSend:'.self::$Dt->user_id);
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            return Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('SelectOk',array("{0}" => self::$Dt->LG->_('ButtenFireFighter'))),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }

        return false;
    }

    public static function NightSelectDodge($Selected){
        $Ex = explode('/',self::$Dt->data);
        $user_id = self::$Dt->user_id;
        if(isset($Ex['2'])) {
            $user_id = $Ex['2'];
        }

        $ForUser = RC::Get('GamePl:role_lucifer:NightSelect');
        $Me_user = GR::_GetPlayer($ForUser);
        $Me_userLink = GR::ConvertName($Me_user['user_id'],$Me_user['fullname_game']);
        if(self::$Dt->in_game == 0){
            return self::Error(self::$Dt->L->_('Error_NotInGame'));
        }
        $U_D = GR::_GetPlayer($user_id);

        if($U_D == false){
            return Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('NotFoundPlayer',array("{0}" => $user_id)),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }

        if(RC::Get('game_state') !== "night"){
            RC::GetSet(self::$Dt->message_id,'GamePl:new:MessageNightSend:'.self::$Dt->user_id);
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            return Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('endTime'),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }
        $Name = GR::ConvertName($user_id,$U_D['fullname_game']);

        $MeRole = $Me_user['user_role']."_n";

        if(RC::CheckExit('GamePl:Selected:'.self::$Dt->user_id.":user:dodge")){
            return false;
        }

        switch ($Selected){
            case 'role_Firefighter':
                if($Me_user['user_role'] !== "role_Firefighter"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet(true,'GamePl:Selected:'.$Me_user['user_id'].":user:dodge");
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                RC::rpush(['user_id'=>$user_id,'fullname'=> $U_D['fullname_game'],'link' => $Name,'role'=> $U_D['user_role']],'GamePl:FirefighterList','json');
                break;
                case 'role_Cow':
                if($Me_user['user_role'] !== "role_Cow"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                    RC::GetSet(true,'GamePl:Selected:'.$Me_user['user_id'].":user:dodge");
                    RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'role_Honey':
                if($Me_user['user_role'] !== "role_Honey"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet(true,'GamePl:Selected:'.$Me_user['user_id'].":user:dodge");
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'role_IceQueen':
                if($Me_user['user_role'] !== "role_IceQueen"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet(true,'GamePl:Selected:'.$Me_user['user_id'].":user:dodge");
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                RC::rpush(['user_id'=>$user_id,'fullname'=> $U_D['fullname_game'],'link' => $Name,'role'=> $U_D['user_role']],'GamePl:role_IceQueen:'.$user_id,'json');
                break;
            case 'role_ahmaq':
                if($Me_user['user_role']  !== "role_ahmaq"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
                case 'role_Phoenix':
                if($Me_user['user_role']  !== "role_Phoenix"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'role_ngativ':
                if($Me_user['user_role'] !== "role_ngativ"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
                case 'role_kentvampire':
                if($Me_user['user_role'] !== "role_kentvampire"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'role_faheshe':
                if($Me_user['user_role'] !== "role_faheshe"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                RC::GetSet($user_id,'GamePl:UserInHome:'.$Me_user['user_id']);
                RC::GetSet($Me_userLink,'GamePl:UserInHome:'.$Me_user['user_id'].":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.$Me_user['user_id'].":role");
                RC::GetSet($user_id,'GamePl:role_faheshe:inhome:'.$user_id);

                break;
            case 'role_Archer':
                if($Me_user['user_role'] !== "role_Archer"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'role_Chemist':
                if($Me_user['user_role'] !== "role_Chemist"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            //Watermelon
            case 'role_Watermelon':
                if($Me_user['user_role'] !== "role_Watermelon"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'role_Knight':
                if($Me_user['user_role']  !== "role_Knight"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                RC::GetSet($user_id,'GamePl:UserInHome:'.$Me_user['user_id']);
                RC::GetSet($Me_userLink,'GamePl:UserInHome:'.$Me_user['user_id'].":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.$Me_user['user_id'].":role");
                break;
            case 'role_Qatel':
                if($Me_user['user_role'] !== "role_Qatel"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                RC::GetSet($user_id,'GamePl:UserInHome:'.$Me_user['user_id']);
                RC::GetSet($Me_userLink,'GamePl:UserInHome:'.$Me_user['user_id'].":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.$Me_user['user_id'].":role");
                break;
            case 'role_Huntsman':
                if($Me_user['user_role'] !== "role_Huntsman"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'role_Fereshte':
                if($Me_user['user_role'] !== "role_Fereshte"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($Name,'GamePl:role_angel:AngelNameSaved');
                RC::GetSet($Me_user['user_id'],'GamePl:role_angel:AngelIn:'.$user_id);
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                RC::GetSet($user_id,'GamePl:UserInHome:'.$Me_user['user_id']);
                RC::GetSet($Me_userLink,'GamePl:UserInHome:'.$Me_user['user_id'].":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.$Me_user['user_id'].":role");
                break;
            case 'role_WhiteWolf':
                if($Me_user['user_role'] !== "role_WhiteWolf"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($Name,'GamePl:role_WhiteWolf:AngelNameSaved');
                RC::GetSet($Me_user['user_id'],'GamePl:role_WhiteWolf:AngelIn:'.$user_id);
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                RC::GetSet($user_id,'GamePl:UserInHome:'.$Me_user['user_id']);
                RC::GetSet($Me_userLink,'GamePl:UserInHome:'.$Me_user['user_id'].":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.$Me_user['user_id'].":role");
                break;
            case 'role_forestQueen':
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_Wolfx':
            case 'role_WolfAlpha':

                $Wolf_role = SE::WolfRole();

                if($Me_user['user_role']  == "role_forestQueen"){
                    if (RC::CheckExit('GamePl:role_forestQueen:AlphaDead')) {
                        array_push($Wolf_role,'role_forestQueen');
                    }
                }

                if(!in_array($Me_user['user_role'] ,$Wolf_role)){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }

                RC::GetSet(true,'GamePl:Selected:'.$Me_user['user_id'].":user:dodge");
                $countTeam = GR::_GetByTeam('wolf');
                if(count($countTeam) > 1){
                    $msg = self::$Dt->LG->_('eatUser',array("{0}"=>$Me_userLink,"{1}" => $Name));
                    GR::SendForWolfTeam($msg,true);

                    RC::Lrem('GamePl:Selected:Wolf:'.$user_id,1,$Me_user['user_id']);

                    RC::rpush($Me_user['user_id'],'GamePl:Selected:Wolf:'.$user_id);
                    RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                }else{
                    RC::GetSet($user_id,'GamePl:UserInHome:'.$Me_user['user_id']);
                    RC::GetSet($Me_userLink,'GamePl:UserInHome:'.$Me_user['user_id'].":name");
                    RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.$Me_user['user_id'].":role");
                    RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                }

                break;
            case 'role_Vampire':
            case 'role_Bloodthirsty':
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user:dodge");
                $countTeam = GR::_GetByTeam('vampire');
                if(count($countTeam) > 1){
                    $msg = (RC::CheckExit('GamePl:VampireFinded') ? self::$Dt->LG->_('MessageGoHomeFinde',array("{0}" => $Me_userLink,"{1}" => $Name)) : self::$Dt->LG->_('MessageGoHome',array("{0}" => $Me_userLink,"{1}" =>$Name)));
                    GR::SendForVampireTeam($msg,$Me_user['user_id']);
                    RC::Lrem('GamePl:Selected:Vampire:'.$user_id,1,$Me_user['user_id']);

                    RC::rpush($Me_user['user_id'],'GamePl:Selected:Vampire:'.$user_id);
                    RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                }else{
                    RC::GetSet($user_id,'GamePl:UserInHome:'.$Me_user['user_id']);
                    RC::GetSet($Me_userLink,'GamePl:UserInHome:'.$Me_user['user_id'].":name");
                    RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.$Me_user['user_id'].":role");
                    RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                }
                break;
            case 'role_enchanter':
                if($Me_user['user_role'] !== "role_enchanter"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'role_ferqe':
            case 'role_Royce':
                if($Me_user['user_role'] !== "role_ferqe"){
                    if($Me_user['user_role'] !== "role_Royce") {
                        return self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    }
                }
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user:dodge");
                $countTeam = GR::_GetByTeam('ferqeTeem');
                if(count($countTeam) > 1){
                    $msg = self::$Dt->LG->_('CultistVotedConvert',array("{0}" => $Me_userLink,"{1}" =>$Name));
                    GR::SendForCultTeam($msg,true);
                    RC::Lrem('GamePl:Selected:Cult:'.$user_id,1,$Me_user['user_id']);

                    RC::rpush($Me_user['user_id'],'GamePl:Selected:Cult:'.$user_id);
                    RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                }else{
                    RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                }
                break;
            case 'role_pishgo':
                if($Me_user['user_role'] !== "role_pishgo"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'role_WolfJadogar':
                if($Me_user['user_role'] !== "role_WolfJadogar"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
        }

        RC::GetSet(self::$Dt->message_id,'GamePl:new:MessageNightSend:'.self::$Dt->user_id);
        RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->LG->_('SelectOk',array("{0}" => $U_D['fullname'])),
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);
    }
    public static function DaySelectedDodge($Type){
        $Ex = explode('/',self::$Dt->data);
        $ForUser = RC::Get('GamePl:role_lucifer:DodgeDay');
        $Me_user = GR::_GetPlayer($ForUser);
        $Me_userLink = GR::ConvertName($Me_user['user_id'],$Me_user['fullname_game']);
        $user_id = self::$Dt->user_id;
        if(isset($Ex['2'])) {
            $user_id = (float) $Ex['2'];
        }

        if(self::$Dt->in_game == 0){
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            return self::Error(self::$Dt->L->_('Error_NotInGame'));
        }

        $U_D = GR::_GetPlayer($user_id);

        if(!$U_D){
            RC::GetSet(self::$Dt->message_id,'GamePl:new:MessageNightSend:'.self::$Dt->user_id);
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            return Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('NotFoundPlayer',array("{0}" =>$user_id)),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }

        if(RC::CheckExit('GamePl:Selected:'.self::$Dt->user_id.":user") && $Me_user['user_role'] !== "role_Solh"){
            return false;
        }

        if(self::$Dt->user_role !== "role_Solh") {
            RC::GetSet(true, 'GamePl:Selected:' . self::$Dt->user_id . ":user");
        }
        $MeRole = $Me_user['user_role']."_n";
        $EdaitMarkup = false;
        switch ($Type){
            case 'Karagah':
                if($Me_user['user_role'] !== "role_karagah"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'Margita':
                if($Me_user['user_role'] !== "role_Margita"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
                case 'BlackKnight':
                if($Me_user['user_role'] !== "role_BlackKnight"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                 RC::GetSet($user_id,'GamePl:UserInHome:'.$Me_user['user_id']);
                 RC::GetSet($Me_userLink,'GamePl:UserInHome:'.$Me_user['user_id'].":name");
                 RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.$Me_user['user_id'].":role");

                break;
                case 'role_Madosa':
                if($Me_user['user_role'] !== "role_Madosa"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
             case 'Princess':
                if($Me_user['user_role'] !== "role_Princess"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
             break;
            case 'Spy':
                if($Me_user['user_role'] !== "role_Spy"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'Gunner':
                if($Me_user['user_role'] !== "role_tofangdar"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'KentVampire':
                if($Me_user['user_role'] !== "role_kentvampire"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;

            default:
                break;
        }
        RC::GetSet(self::$Dt->message_id,'GamePl:new:MessageNightSend:'.self::$Dt->user_id);
        RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->LG->_('SelectOk',array("{0}" => $U_D['fullname_game'])),
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);

    }

    public static function DodgeVote(){
        $Ex = explode('/',self::$Dt->data);
        if(isset($Ex['2'])) {
            $user_id = (float) $Ex['2'];
        }
        $ForUser = RC::Get('GamePl:role_lucifer:DodgeVote');
        $Me_user = GR::_GetPlayer($ForUser);
        $Me_userLink = GR::ConvertName($Me_user['user_id'],$Me_user['fullname_game']);
        if(self::$Dt->in_game == 0){
            RC::Del('GamePl:MessageNightSendDodgeVote:'.self::$Dt->user_id);
            return self::Error(self::$Dt->L->_('Error_NotInGame'));
        }
        $U_D = GR::_GetPlayer($user_id);

        $U_F_fullname = $U_D['fullname_game'];
        if(!$U_D){
            RC::Del('GamePl:MessageNightSendDodgeVote:'.self::$Dt->user_id);
            return Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('NotFoundPlayer',array("{0}" => $user_id)),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }

        RC::Del('GamePl:DontVote:'.$Me_user['user_id']);
        // چک میکنیم صلح شده یا نه
        if(RC::CheckExit('GamePl:role_Solh:GroupInSolh')){
            RC::Del('GamePl:MessageNightSendDodgeVote:'.self::$Dt->user_id);
            return Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('selectSolh'),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }

        if(RC::Get('game_state') !== "vote"){
            RC::Del('GamePl:MessageNightSendDodgeVote:'.self::$Dt->user_id);
            return Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('endTime'),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }


        if(RC::CheckExit('GamePl:Selected:'.self::$Dt->user_id.":user:vote:Dodge")){
            return false;
        }

        RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user:vote:Dodge");

        if($Me_user['user_role'] == "role_PesarGij"){
            if(mt_rand(0,100) < 50 ) {
                $Random = GR::GetRoleRandom([$user_id,self::$Dt->user_id]);
                $U_D = GR::_GetPlayer($Random['user_id']);
                $user_id = $Random['user_id'];
            }
        }

        $Name = GR::ConvertName($user_id,$U_D['fullname_game']);


        GR::SaveVoteMessageDodge($Name,$Me_userLink);

        RC::GetSet(true,'GamePl:VoteList:'.$user_id);
        RC::GetSet((RC::Get('GamePl:VoteCount') + 1 ) ,'GamePl:VoteCount');
        if($Me_user['user_role'] == "role_Kadkhoda" and RC::CheckExit('GamePl:role_Kadkhoda:MayorReveal')){
            RC::rpush(['user_id' =>$Me_user['user_id']  ,'name' => $Me_userLink],'GamePl:Selected:Vote:'.$user_id);
        }
        RC::rpush(['user_id' => $Me_user['user_id'] ,'name' => $Me_userLink],'GamePl:Selected:Vote:'.$user_id,'json');
        RC::Del('GamePl:MessageNightSendDodgeVote:'.self::$Dt->user_id);


        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->LG->_('SelectOk',array("{0}" => $U_F_fullname)),
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);
    }
    public static function VoteUser(){
        $Ex = explode('/',self::$Dt->data);
        $user_id =  $Ex['2'];

        if(self::$Dt->in_game == 0){
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            return self::Error(self::$Dt->L->_('Error_NotInGame'));
        }
        $U_D = GR::_GetPlayer($user_id);

        $U_F_fullname = $U_D['fullname'];
        if($U_D == false){
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            return Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('NotFoundPlayer',array("{0}" =>$user_id)),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }

        RC::Del('GamePl:DontVote:'.self::$Dt->user_id);
        // چک میکنیم صلح شده یا نه
        if(RC::CheckExit('GamePl:role_Solh:GroupInSolh')){
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            return Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('selectSolh'),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }

        if(RC::Get('game_state') !== "vote"){
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            return Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('endTime'),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }


        if(RC::CheckExit('GamePl:Selected:'.self::$Dt->user_id.":user:vote")){
            return false;
        }
        RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user:vote");
        if(self::$Dt->user_role == "role_PesarGij"){
            if(mt_rand(0,100) < 50 ) {
                $Random = GR::GetRoleRandom([$user_id,self::$Dt->user_id]);
                $U_D = GR::_GetPlayer($Random['user_id']);
                $user_id = $Random['user_id'];
            }
        }


        $Name = GR::ConvertName($user_id,$U_D['fullname_game']);


        GR::SaveVoteMessage($Name);
        RC::GetSet(true,'GamePl:VoteList:'.$user_id);
        RC::GetSet((RC::Get('GamePl:VoteCount') + 1 ) ,'GamePl:VoteCount');
        if(self::$Dt->user_role == "role_Kadkhoda" and RC::CheckExit('GamePl:role_Kadkhoda:MayorReveal')){
            RC::rpush(['user_id' => self::$Dt->user_id ,'name' => self::$Dt->user_link],'GamePl:Selected:Vote:'.$user_id);
        }
        // GR::SaveVoteUser((int) $user_id,self::$Dt->user_id,self::$Dt->user_link);

        RC::rpush(['user_id' => self::$Dt->user_id ,'name' => self::$Dt->user_link],'GamePl:Selected:Vote:'.$user_id,'json');
        RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');


        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->LG->_('SelectOk',array("{0}" => $U_F_fullname)),
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);


    }


    public static function DaySelectedCheck($Selected){
        $Ex = explode('/',self::$Dt->data);
        $user_id = self::$Dt->user_id;
        if(isset($Ex['2'])) {
            $user_id = $Ex['2'];
        }

        if(self::$Dt->in_game == 0){
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            return self::Error(self::$Dt->L->_('Error_NotInGame'));
        }

        $U_D = GR::_GetPlayer($user_id);

        if($U_D == false){
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            return Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('NotFoundPlayer',array("{0}" =>$user_id)),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }

        if(RC::CheckExit('GamePl:Selected:'.self::$Dt->user_id.":user") && self::$Dt->user_role !== "role_Solh"){
            return false;
        }

        if(self::$Dt->user_role == "role_Solh" && RC::CheckExit('GamePl:role_Solh:GroupInSolh')){
            return false;
        }
        if(self::$Dt->user_role !== "role_Solh") {
            RC::GetSet(true, 'GamePl:Selected:' . self::$Dt->user_id . ":user");
        }
        $MeRole = self::$Dt->user_role."_n";
        $EdaitMarkup = false;
        switch ($Selected){
            case 'Karagah':
                if(self::$Dt->user_role !== "role_karagah"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
                case 'shahzade':
                if(self::$Dt->user_role !== "role_Shahzade"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                    RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
                case 'feriga':
                if(self::$Dt->user_role !== "role_feriga"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
                case 'Princess':
                if(self::$Dt->user_role !== "role_Princess"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
                case 'madosa':
                if(self::$Dt->user_role !== "role_Madosa"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'midwolf':
                if(self::$Dt->user_role !== "role_midwolf"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'BlackKnight':
                if(self::$Dt->user_role  !== "role_BlackKnight"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Spy':
                if(self::$Dt->user_role !== "role_Spy"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
                case 'Margita':
                if(self::$Dt->user_role !== "role_Margita"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Tofangdar':
                if(self::$Dt->user_role !== "role_tofangdar"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
                case 'KentVampire':
                   if(self::$Dt->user_role !== "role_kentvampire"){
                     return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                  }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Solh':
                if(self::$Dt->user_role !== "role_Solh"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }


                $UnlockIn = (RC::Get('GamePl:Day_no') + 1);
                RC::GetSet($UnlockIn,'GamePl:role_Solh:GroupInSolh');
                RC::GetSet(true,'GamePl:solhIsSolh');
                $GroupMessage =  self::$Dt->LG->_('PacifistNoLynch',array("{0}"=>self::$Dt->user_link));

                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => $GroupMessage,
                    'parse_mode'=> 'HTML'
                ]);
                if(RC::Get('game_state') == "vote"){
                    RC::GetSet( time(),'timer');
                }
                $EdaitMarkup = true;
                break;

            case 'Kadkhoda':
                if(self::$Dt->user_role !== "role_Kadkhoda"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet(true,'GamePl:role_Kadkhoda:MayorReveal');
                $GroupMessage =  self::$Dt->LG->_('MayorReveal',array("{0}"=>self::$Dt->user_link));
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => $GroupMessage,
                    'parse_mode'=> 'HTML'
                ]);
                $EdaitMarkup = true;
                break;

            case 'Ruler':
                if(self::$Dt->user_role !== "role_Ruler"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet(RC::Get('GamePl:Day_no') + 1,'GamePl:role_Ruler:RulerOk');
                RC::GetSet(true,'GamePl:'.self::$Dt->user_role.':notSend');
                $GroupMessage =  self::$Dt->LG->_('RulerNowRul',array("{0}" =>self::$Dt->user_link));
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => $GroupMessage,
                    'parse_mode'=> 'HTML'
                ]);
                $EdaitMarkup = true;
                break;

            case 'Khabgozar_Yes':
                if(self::$Dt->user_role !== "role_KhabGozar"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }

                if(RC::Get('game_state') !== "day"){
                    RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
                    return Request::editMessageReplyMarkup([
                        'chat_id' => self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'reply_markup' => new InlineKeyboard([]),
                    ]);
                }

                RC::GetSet(RC::Get('GamePl:Night_no'),'GamePl:KhabgozarOk_in');
                RC::GetSet(RC::Get('GamePl:Night_no') + 1,'GamePl:NotSendNight');
                RC::GetSet(RC::Get('GamePl:Night_no') + 1,'GamePl:KhabgozarOk');
                RC::GetSet(true,'GamePl:'.self::$Dt->user_role.':notSend');
                $GroupMessage =  self::$Dt->LG->_('SandmanSleepAll',array("{0}" => self::$Dt->user_link));
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => $GroupMessage,
                    'parse_mode'=> 'HTML'
                ]);
                $EdaitMarkup = true;
                break;
            case 'Khabgozar_No':
                $EdaitMarkup = true;
                break;
            case 'SendBittenYes':
                $Player = GR::_GetPlayerByrole('role_Botanist');
                if($Player){
                    $inline_keyboard = new InlineKeyboard([
                        ['text' => self::$Dt->LG->_('btnOkUser'), 'callback_data' => "DaySelect_BotanistOk/" . self::$Dt->chat_id],
                        ['text' => self::$Dt->LG->_('btnNoUser'), 'callback_data' => "DaySelect_BotanistNo/" . self::$Dt->chat_id]
                    ]);
                    $result = Request::sendMessage([
                        'chat_id' => $Player['user_id'],
                        'text' => self::$Dt->LG->_('BotanistMessage',RC::Get('GamePl:FllowCount') ?? 1),
                        'parse_mode' => 'HTML',
                        'reply_markup' => $inline_keyboard,
                    ]);
                    if($result->isOk()) {
                        RC::GetSet(self::$Dt->user_link,'GamePl:role_Botanist:link');
                        Request::sendMessage([
                            'chat_id' => self::$Dt->user_id,
                            'text' => self::$Dt->LG->_('OkSendToBotanist'),
                            'parse_mode'=> 'HTML'
                        ]);
                        RC::GetSet($result->getResult()->getMessageId(), 'GamePl:EditMarkup:' . $Player['user_id']);
                    }
                }
                $EdaitMarkup = true;
                break;
            case 'SendBittenNo':
                $EdaitMarkup = true;
                break;
            case 'BotanistOk':
                $for = RC::Get('GamePl:role_Botanist:bittaned:for');
                $MessagePl = self::$Dt->LG->_('BotanistMessageOk',RC::Get('GamePl:role_Botanist:link'));
                if($for == "wolf"){
                    RC::Del('GamePl:EnchanterBittanPlayer');
                    RC::Del('GamePl:BittanPlayer');
                    GR::SendForWolfTeam($MessagePl);
                }elseif($for == "vampire"){
                    RC::Del('GamePl:VampireBitten');
                    GR::SendForVampireTeam($MessagePl);
                }

                Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => self::$Dt->LG->_('BotanistM',RC::Get('GamePl:role_Botanist:link')),
                    'parse_mode'=> 'HTML'
                ]);

                $UserId = RC::Get('GamePl:role_Botanist:bittaned');
                Request::sendMessage([
                    'chat_id' => $UserId,
                    'text' => self::$Dt->LG->_('OkMessagePlayer',self::$Dt->user_link),
                    'parse_mode'=> 'HTML'
                ]);
                RC::DelKey('GamePl:role_Botanist:*');
                $EdaitMarkup = true;
                break;
            case 'BotanistNo':
                $UserId = RC::Get('GamePl:role_Botanist:bittaned');
                Request::sendMessage([
                    'chat_id' => $UserId,
                    'text' => self::$Dt->LG->_('BotanistNo'),
                    'parse_mode'=> 'HTML'
                ]);
                RC::DelKey('GamePl:role_Botanist:*');
                $EdaitMarkup = true;
                break;

            case 'SharlatanTofan':
                if(self::$Dt->user_role !== "role_Sharlatan"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                // آهنگری که شب اعلام نقش خواب گذار نقره پخش کند
                if(RC::Get('GamePl:KhabgozarOk')){
                    RC::Del('GamePl:KhabgozarOk');
                }
                if(RC::CheckExit('GamePl:AhangarOk')){
                    RC::Del('GamePl:AhangarOk');
                }

                RC::GetSet((RC::Get('GamePl:Day_no') + 1),'GamePl:SharlatanInTofan');
                $GroupMessage = self::$Dt->LG->_('SharlatanTofanMessageDay');
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => $GroupMessage,
                    'parse_mode'=> 'HTML'
                ]);
                $EdaitMarkup = true;

            break;
            case 'SharlatanTabar':
                if(self::$Dt->user_role !== "role_Sharlatan"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }

                RC::GetSet(true,'GamePl:SharlatanINTabar');
                $EdaitMarkup = true;

                break;
            case 'Ahangar_no':
            case 'isra_no':
            case 'trouble_no':
                $EdaitMarkup = true;
           break;
            case 'Ahangar_Yes':
                if(self::$Dt->user_role !== "role_Ahangar"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }

                if(RC::Get('game_state') !== "day"){
                    RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
                    return Request::editMessageReplyMarkup([
                        'chat_id' => self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'reply_markup' => new InlineKeyboard([]),
                    ]);
                }

                // آهنگری که شب اعلام نقش خواب گذار نقره پخش کند
                if(RC::Get('GamePl:KhabgozarOk_in') == RC::Get('GamePl:Night_no') ){
                    GR::SavePlayerAchivment(self::$Dt->user_id,'Wasted_Silver');
                }

                RC::GetSet((RC::Get('GamePl:Night_no') + 1),'GamePl:AhangarOk');
                RC::GetSet(true,'GamePl:'.self::$Dt->user_role.':notSend');
                $GroupMessage =  self::$Dt->LG->_('BlacksmithSpreadSilver',array("{0}" => self::$Dt->user_link));
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => $GroupMessage,
                    'parse_mode'=> 'HTML'
                ]);
                $EdaitMarkup = true;
                break;
                case 'isra_Yes':
                if(self::$Dt->user_role !== "role_isra"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }

                if(RC::Get('game_state') !== "day"){
                    RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
                    return Request::editMessageReplyMarkup([
                        'chat_id' => self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'reply_markup' => new InlineKeyboard([]),
                    ]);
                }



                RC::GetSet(((int) RC::Get('GamePl:Night_no') + 1),'GamePl:IsraOk');
                RC::GetSet(true,'GamePl:'.self::$Dt->user_role.':notSend');
                $GroupMessage =  self::$Dt->LG->_('Help_massege_in_group',array("{0}" => self::$Dt->user_link));
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => $GroupMessage,
                    'parse_mode'=> 'HTML'
                ]);
                $EdaitMarkup = true;
                break;
            case 'trouble_yes':
                if(self::$Dt->user_role !== "role_trouble"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }

                if(RC::Get('game_state') !== "day"){
                    RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
                    return Request::editMessageReplyMarkup([
                        'chat_id' => self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'reply_markup' => new InlineKeyboard([]),
                    ]);
                }

                RC::GetSet(true,'GamePl:trouble');
                RC::GetSet(true,'GamePl:'.self::$Dt->user_role.':notSend');
                $GroupMessage =  self::$Dt->LG->_('troubleGroupMessage',array("{0}" => self::$Dt->user_link));
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => $GroupMessage,
                    'parse_mode'=> 'HTML'
                ]);
                $EdaitMarkup = true;
                break;

        }
        RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');

        if($EdaitMarkup){
            Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('SelectOk_no'),
                'parse_mode'=> 'HTML'
            ]);

            return Request::editMessageReplyMarkup([
                'chat_id' => self::$Dt->user_id,
                'message_id' => self::$Dt->message_id,
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }
        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->LG->_('SelectOk',array("{0}" => $U_D['fullname'])),
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);
    }


    public static function RemoveMarkUp(){
        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => 'روز خوبی داشته باشید',
            'message_id' => self::$Dt->message_id,
            'reply_markup' => new InlineKeyboard([]),
        ]);
    }
    public static function Error($msg){
        if(empty($msg)){
            return false;
        }
        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => $msg,
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);
    }


    public static function Skip(){
        if(self::$Dt->in_game == 0 && self::$Dt->user_role !== "role_kalantar" && !RC::CheckExit('GamePl:HunterKill')){
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            return self::Error(self::$Dt->L->_('Error_NotInGame'));
        }

        if(self::$Dt->user_role == "role_kalantar" && RC::CheckExit('GamePl:HunterKill')){
            RC::GetSet( time(),'timer');
            RC::GetSet(self::$Dt->user_link,'GamePl:kalantar_fullname');
            RC::Del('GamePl:HunterKill');
        }

        RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->LG->_('SelectOk',array("{0}" => 'skip')),
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);
    }

    public static function KalanShot(){
        $Ex = explode('/',self::$Dt->data);
        $user_id = self::$Dt->user_id;
        if(isset($Ex['2'])) {
            $user_id = $Ex['2'];
        }

        $U_D = GR::_GetPlayer($user_id);

        if($U_D == false){
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            return Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('NotFoundPlayer',array("{0}" =>$user_id)),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }

        RC::GetSet(self::$Dt->user_id,'GamePl:kalantar_userid');
        RC::GetSet(self::$Dt->user_link,'GamePl:kalantar_fullname');
        RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
        RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
        RC::Del('GamePl:CheckNight');
        RC::GetSet( time(),'timer');
        //  RC::Del('GamePl:HunterKill');
        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->LG->_('SelectOk',array("{0}" => $U_D['fullname'])),
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);
    }
    public static function CM_Ping(){
        $starttime = microtime(true);
        $host = 'www.bot.boldwolf.ir';
        $ping = new Ping($host);
        self::$Dt->Latency = $ping->ping();
        self::$Dt->LatencyM = (self::$Dt->Latency['time'] ?  self::$Dt->Latency['time'] : 'Host could not be reached.');
        $stoptime  = microtime(true);
        $status = ($stoptime - $starttime) * 1000;
        $MessageRe = self::$Dt->L->_('PingT', array("{0}" => self::$Dt->LatencyM." ms" , "{1}" => date("i:s", floor($status) )));

        Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => $MessageRe,
            'reply_to_message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
        ]);
    }

    public static function CM_Smite(){
        $status = GR::CheckGPGameState();
        switch ($status) {
            case 0:
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->LG->_('GameNotCreate'),
                    'parse_mode' => 'HTML'
                ]);
                break;
            case 2:
                if(self::$Dt->admin == 0){
                    return Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => "<strong>" . self::$Dt->L->_('YouNotAdminGp') . "</strong>",
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML',
                    ]);
                }

                if(isset(self::$Dt->message->getEntities()[1])){
                    if(self::$Dt->message->getEntities()[1]->getUser()) {
                        $user_id = self::$Dt->message->getEntities()[1]->getUser()->getId();
                    }
                }

                $Text = self::$Dt->text;
                if(isset($Text)) {
                    if(is_numeric($Text) and strlen($Text) > 7) {
                        $user_id = self::$Dt->text;
                    }elseif(preg_match("/^(?:[a-zA-Z0-9?. ]?)+@([a-zA-Z0-9]+)(.+)?$/",$Text,$matches)){
                        $username = $matches[0];
                    }
                    // اگه با ای دی بود
                    if(isset($user_id)){
                        if(GR::CheckPlayerJoined($user_id)){
                            $Player = GR::_GetPlayerName($user_id);
                            GR::UserSmiteInGame($user_id);
                            return  Request::sendMessage([
                                'chat_id' => self::$Dt->chat_id,
                                'text' => self::$Dt->L->_('PlayerSmite',array("{0}" => GR::ConvertName($user_id,$Player), "{1}" => GR::CountPlayer())),
                                'parse_mode' => 'HTML'
                            ]);
                        }
                        return  Request::sendMessage([
                            'chat_id' => self::$Dt->chat_id,
                            'text' => self::$Dt->L->_('NotFindeSmiteUserId',array("{0}" => $user_id)),
                            'reply_to_message_id' => self::$Dt->message_id,
                            'parse_mode' => 'HTML'
                        ]);
                    }
                    if(isset($username)){
                        $check = GR::CheckUserByUsername($username);
                        if(!$check){
                            return  Request::sendMessage([
                                'chat_id' => self::$Dt->chat_id,
                                'text' => self::$Dt->L->_('NotFindeSmiteUserName',array("{0}" => $username)),
                                'reply_to_message_id' => self::$Dt->message_id,
                                'parse_mode' => 'HTML'
                            ]);
                        }

                        GR::UserSmiteInGame($check['user_id']);
                        return  Request::sendMessage([
                            'chat_id' => self::$Dt->chat_id,
                            'text' => self::$Dt->L->_('PlayerSmite', array("{0}" => GR::ConvertName($check['user_id'],$check['fullname_game']), "{1}" => GR::CountPlayer())),
                            'parse_mode' => 'HTML'
                        ]);
                    }

                    if(!self::$Dt->ReplayTo) {
                        return Request::sendMessage([
                            'chat_id' => self::$Dt->chat_id,
                            'text' => self::$Dt->L->_('PleaseInsetValueForSmite'),
                            'reply_to_message_id' => self::$Dt->message_id,
                            'parse_mode' => 'HTML'
                        ]);
                    }
                }

                if(self::$Dt->ReplayTo) {
                    $user_id = self::$Dt->ReplayTo;
                }
                if(GR::CheckPlayerJoined($user_id)) {
                    $Player = GR::_GetPlayerName($user_id);
                    GR::UserSmiteInGame($user_id);
                    return Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('PlayerSmite', array("{0}" => GR::ConvertName($user_id, $Player), "{1}" => GR::CountPlayer())),
                        'parse_mode' => 'HTML'
                    ]);
                }
                if(!isset($user_id)){
                    $user_id = "نام کاربری را وارد نمایید مانند  /smite @new";
                }
                return  Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->L->_('NotFindeSmiteUserId',$user_id),
                    'reply_to_message_id' => self::$Dt->message_id,
                    'parse_mode' => 'HTML'
                ]);
                break;
            case 1:
                if(self::$Dt->admin == 0){
                    return Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => "<strong>" . self::$Dt->L->_('YouNotAdminGp') . "</strong>",
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML',
                    ]);
                }

                $Text = self::$Dt->text;
                if(isset($Text)) {
                    if(is_numeric($Text) and strlen($Text) > 7) {
                        $user_id = self::$Dt->text;
                    }elseif(preg_match("/^(?:[a-zA-Z0-9?. ]?)+@([a-zA-Z0-9]+)(.+)?$/",$Text,$matches)){
                        $username = $matches[0];
                    }
                    // اگه با ای دی بود
                    if(isset($user_id)){
                        if(RC::CheckExit('GamePl:join_user:'.$user_id)){
                            RC::rpush($user_id,'GamePl:SmitePlayer');
                            return true;
                        }
                        return  Request::sendMessage([
                            'chat_id' => self::$Dt->chat_id,
                            'text' => self::$Dt->L->_('NotFindeSmiteUserId',$user_id),
                            'reply_to_message_id' => self::$Dt->message_id,
                            'parse_mode' => 'HTML'
                        ]);
                    }
                    if(isset($username)){
                        $check = GR::CheckUserByUsername($username);
                        if(!$check){
                            return  Request::sendMessage([
                                'chat_id' => self::$Dt->chat_id,
                                'text' => self::$Dt->L->_('NotFindeSmiteUserName',$username),
                                'reply_to_message_id' => self::$Dt->message_id,
                                'parse_mode' => 'HTML'
                            ]);
                        }
                        RC::rpush($check['user_id'],'GamePl:SmitePlayer');
                        return true;
                    }

                    if(self::$Dt->ReplayTo) {
                        if(isset($user_id)) {
                            if ($user_id !== self::$Dt->ReplayTo) {
                                return Request::sendMessage([
                                    'chat_id' => self::$Dt->chat_id,
                                    'text' => self::$Dt->L->_('PleaseInsetValueForSmite'),
                                    'reply_to_message_id' => self::$Dt->message_id,
                                    'parse_mode' => 'HTML'
                                ]);
                            }
                        }
                    }

                }

                if(self::$Dt->ReplayTo) {
                    $user_id = self::$Dt->ReplayTo;
                    if (RC::CheckExit('GamePl:join_user:' . $user_id)) {
                        RC::rpush($user_id, 'GamePl:SmitePlayer');
                        return true;
                    }
                }
                $user_id = "None";
                return  Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->L->_('NotFindeSmiteUserId',$user_id),
                    'reply_to_message_id' => self::$Dt->message_id,
                    'parse_mode' => 'HTML'
                ]);

                break;
            default:
                return false;
                break;
        }
    }


    public static function CM_Stats(){
        $user_id = self::$Dt->ReplayTo ?? self::$Dt->user_id;
        $Stats = GR::GetStats($user_id);
        if($Stats){
            return  Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => $Stats,
                'parse_mode' => 'HTML',
            ]);
        }

        return  Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => self::$Dt->L->_('no_state'),
            'reply_to_message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
        ]);
    }


    public static function CM_Score(){

        $Score = GR::GetScore();
        if(!$Score){
            return false;
        }
        $re = Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => $Score,
            'parse_mode' => 'HTML',
        ]);

        if(!$re->isOk()) {
            Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => "<strong>" . self::$Dt->L->_('PleaseStartBot') . "</strong>",
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
            ]);
        }


    }


    public static function CM_Killme(){
        $user_id = self::$Dt->ReplayTo ?? self::$Dt->user_id;
        $KillMe  = GR::GetKillMe($user_id);

        if($KillMe){
            return  Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => $KillMe,
                'parse_mode' => 'HTML',
            ]);
        }

    }

    public static function CM_Kills(){
        $user_id = self::$Dt->ReplayTo ?? self::$Dt->user_id;
        $Kills  = GR::GetKills($user_id);

        if($Kills){
            return  Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => $Kills,
                'parse_mode' => 'HTML',
            ]);
        }
    }


    public static function CM_Myideals(){

        $Lang = false;
        (RC::CheckExit('AfkedPlayer:'.self::$Dt->user_id) ? $Lang .= self::$Dt->L->_('AfkedIdels',array("{0}" => self::$Dt->user_link, "{1}" => RC::Get('AfkedPlayer:'.self::$Dt->user_id))) : false);
        $checkTop = RC::LRange(0,-1,'UserIdles:'.self::$Dt->user_id);
        if($checkTop){
            $re = [];
            $REArray = array_reverse($checkTop);
            $slice = array_slice($REArray,0,5);
            foreach ($slice as $row){
                array_push($re,$row);
            }
            if($re){
                $Lang .= PHP_EOL.implode(PHP_EOL,$re);
            }
        }
        if($Lang){
            return  Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => $Lang,
                'parse_mode' => 'HTML',
            ]);
        }
    }


    public static function CM_RoleList($l = 10, $m = 0){
        $result = self::$Dt->collection->role_list->find(['state' => 1],[
            "limit" => $l,
            "skip" => $m
        ]);
        if($result) {
            $array = iterator_to_array($result);
            $defultLang = self::$Dt->defaultLang;
            $defultMode = (self::$Dt->default_mode ? self::$Dt->default_mode : "general");
            $L = new Lang(FALSE);
            $L->load($defultMode."_".$defultLang, FALSE);

            if($array){
                $total = self::$Dt->collection->role_list->count(['state' => 1]);
                $send = 0;
                $re = [];
                foreach ($array as $item) {
                    if($send <= 10) {
                        $txt = "/" . $item['Key'] . " - " . $L->_($item['role']."_n");
                        array_push($re,$txt);
                    }
                }

                $allSend = $l + $m;
                $sends = $total - $l;
                $data = [
                    'chat_id' => self::$Dt->user_id,
                    'text' => implode(PHP_EOL,$re),
                ];
                Request::sendMessage($data);
                if($total >= $sends){
                    self::CM_RoleList(10, $allSend);
                }

            }
        }
    }

    public static function CM_Command($Command){

        $Command =  GR::_GetCommand($Command);
        if($Command){
            $defultLang = self::$Dt->defaultLang;
            $defultMode = self::$Dt->def_mode ?? "general";
            $L = new Lang(FALSE);
            $L->load($defultMode."_".$defultLang, FALSE);
            $Message = $L->_($Command['Key']);
            $data = [
                'chat_id' => self::$Dt->user_id,
                'text' => $Message,
                'parse_mode'=> 'HTML'
            ];
            Request::sendMessage($data);
        }
    }

    public static function BanPlayer($str){
        $Ex = explode('/',self::$Dt->data);
        $user_id = self::$Dt->user_id;
        if(isset($Ex['2'])) {
            $user_id = $Ex['2'];
        }
        $BanDetial = GR::BanDetial($user_id);

        switch ($str){
            case 'remove':
            case 'No':
                $UserMessage = "شما توسط %s به لیست بن به دلیل %s اضافه شده بودید ولی اینبار %s شما رو بخشیدن و اکنون میتوانید بازی کنید";
                self::EditMarkupBan('No',['name'=> $BanDetial['link']]);
                GR::RemoveFromBanList($user_id);
                return  Request::sendMessage([
                    'chat_id' => $user_id,
                    'text' => vsprintf($UserMessage,[self::$Dt->user_link,$BanDetial['ban_for'],self::$Dt->user_link]),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case '30min':
                $time = strtotime('+30 minute');
                GR::ChangeBanUntilTime($time,$user_id);
                self::EditMarkupBan('30m',['name'=> $BanDetial['link']]);
                $UserMessage = "شما تا %s دقیقه دیگر در لیست بن میباشد و نمتوانید بازی کنید.
                 در ساعت %s مجدد میتوانید بازی کنید.
                  مدیر محدود کننده : %s";
                return  Request::sendMessage([
                    'chat_id' => $user_id,
                    'text' => vsprintf($UserMessage,[30,jdate('H:i:s',$time),self::$Dt->user_link]),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case '1d':
                $time = strtotime('+1 day');
                GR::ChangeBanUntilTime($time,$user_id);
                self::EditMarkupBan('1d',['name'=> $BanDetial['link']]);
                $UserMessage = "شما تا %s روز دیگر در لیست بن میباشد و نمتوانید بازی کنید.
                 در تاریخ %s مجدد میتوانید بازی کنید.
                  مدیر محدود کننده : %s";
                return  Request::sendMessage([
                    'chat_id' => $user_id,
                    'text' => vsprintf($UserMessage,[1,jdate('Y-m-d H:i:s',$time),self::$Dt->user_link]),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case '1w':
                $time = strtotime('+1 week');
                GR::ChangeBanUntilTime($time,$user_id);
                self::EditMarkupBan('1w',['name'=> $BanDetial['link']]);
                $UserMessage = "شما تا %s هفته دیگر در لیست بن میباشد و نمتوانید بازی کنید.
                 در تاریخ %s مجدد میتوانید بازی کنید.
                  مدیر محدود کننده : %s";
                return  Request::sendMessage([
                    'chat_id' => $user_id,
                    'text' => vsprintf($UserMessage,[1,jdate('Y-m-d H:i:s',$time),self::$Dt->user_link]),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case '1m':
                $time = strtotime('+1 month');
                GR::ChangeBanUntilTime($time,$user_id);
                self::EditMarkupBan('1m',['name'=> $BanDetial['link']]);
                $UserMessage = "شما تا %s ماه دیگر در لیست بن میباشد و نمتوانید بازی کنید.
                 در تاریخ %s مجدد میتوانید بازی کنید.
                  مدیر محدود کننده : %s";
                return  Request::sendMessage([
                    'chat_id' => $user_id,
                    'text' => vsprintf($UserMessage,[1,jdate('Y-m-d H:i:s',$time),self::$Dt->user_link]),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case '1y':
                $time = strtotime('+1 years');
                GR::ChangeBanUntilTime($time,$user_id);
                self::EditMarkupBan('1y',['name'=> $BanDetial['link']]);
                $UserMessage = "شما تا %s سال دیگر در لیست بن میباشد و نمتوانید بازی کنید.
                 در تاریخ %s مجدد میتوانید بازی کنید.
                  مدیر محدود کننده : %s";
                return  Request::sendMessage([
                    'chat_id' => $user_id,
                    'text' => vsprintf($UserMessage,[1,jdate('Y-m-d H:i:s',$time),self::$Dt->user_link]),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'ban':
                GR::ChangeBanUntilTime(1,$user_id);
                self::EditMarkupBan('ban',['name'=> $BanDetial['link']]);
                $UserMessage = "شما برای همیشه  در لیست بن میباشد و نمتوانید بازی کنید.
                  مدیر محدود کننده : %s";
                return  Request::sendMessage([
                    'chat_id' => $user_id,
                    'text' => vsprintf($UserMessage,[self::$Dt->user_link]),
                    'parse_mode' => 'HTML',
                ]);
                break;
        }
    }

    public static function EditMarkupBan($type,$data){
        switch ($type){
            case 'No':
                $L = "شما از خطای %s گذشت نمودید و اکنون در لیست بن نمیباشد.";
                GR::AddActivity( vsprintf('مدیر %s به از خطای کاربر %s گذشت کرد.',[self::$Dt->user_link,self::$Dt->PlayerLink]));
                $text = vsprintf($L,[$data['name']]);
                break;
            case '30m':
                $L = "شما  30 دقیقه %s را در لیست بن قرار دادید.";
                $text = vsprintf($L,[$data['name']]);
                GR::AddActivity( vsprintf('مدیر %s به مدت 30 دقیقه کاربر %s رو به لیست بن اضافه کرد.',[self::$Dt->user_link,self::$Dt->PlayerLink]));
                break;
            case '1d':
                $L = "شما  1 روز %s را در لیست بن قرار دادید.";
                $text = vsprintf($L,[$data['name']]);
                GR::AddActivity( vsprintf('مدیر %s به مدت 1 روز کاربر %s رو به لیست بن اضافه کرد.',[self::$Dt->user_link,self::$Dt->PlayerLink]));
                break;
            case '1w':
                $L = "شما  1 هفته %s را در لیست بن قرار دادید.";
                $text = vsprintf($L,[$data['name']]);
                GR::AddActivity( vsprintf('مدیر %s به مدت 1 هفته کاربر %s رو به لیست بن اضافه کرد.',[self::$Dt->user_link,self::$Dt->PlayerLink]));
                break;
            case '1m':
                $L = "شما  1 ماه %s را در لیست بن قرار دادید.";
                $text = vsprintf($L,[$data['name']]);
                GR::AddActivity( vsprintf('مدیر %s به مدت 1 ماه کاربر %s رو به لیست بن اضافه کرد.',[self::$Dt->user_link,self::$Dt->PlayerLink]));
                break;
            case '1y':
                $L = "شما  1 سال %s را در لیست بن قرار دادید.";
                $text = vsprintf($L,[$data['name']]);
                GR::AddActivity( vsprintf('مدیر %s به مدت 1 سال کاربر %s رو به لیست بن اضافه کرد.',[self::$Dt->user_link,self::$Dt->PlayerLink]));
                break;
            case 'ban':
                $L = "شما  برای همیشه %s را در لیست بن قرار دادید.";
                GR::AddActivity( vsprintf('مدیر %s برای همیشه کاربر %s رو به لیست بن اضافه کرد.',[self::$Dt->user_link,self::$Dt->PlayerLink]));
                $text = vsprintf($L,[$data['name']]);
                break;
        }
        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => $text,
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);


    }

    public static function CM_BanPlayer(){

        $Admin = GR::CheckUserGlobalAdmin(self::$Dt->user_id);
        if($Admin){
            if($Admin['ban_player'] == 0){
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => "دسترسی به این بخش برای شما محدود شده است",
                    'parse_mode' => 'HTML',
                ]);
            }
            // $user_id = self::$Dt->ReplayTo;


            $Text = self::$Dt->text;
            if(isset($Text)) {
                if (preg_match("/^(?:[a-zA-Z0-9?. ]?)+@([a-zA-Z0-9]+)(.+)?$/", $Text, $matches)) {
                    $username = $matches[0];
                }
            }

            if(isset($username)){
                $check = GR::CheckPlayerByUsername($username);
                if(!$check){
                    return  Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('NotFindeSmiteUserName',$username),
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML'
                    ]);
                }

                $user_id = $check['user_id'];
                $fullname = $check['fullname'];
                $link = GR::ConvertName($user_id,$fullname);
            }else {
                $user_id = $Text;
            }




            if(isset($user_id)){
                $checkInBanList = GR::CheckPlayerInBanList($user_id);
                if($checkInBanList){
                    if($checkInBanList['state'] == true) {
                        if(isset($checkInBanList['key'])) {
                            switch ($checkInBanList['key']) {
                                case 'ban_ever':
                                    $UserLang = "همیشه";
                                    break;
                                case 'ban_to':
                                    $UserLang = jdate('Y-m-d H:i:s',$checkInBanList['time']);
                                    break;
                            }
                        }

                        $Lang = "کاربر %s از قبل در لیست بن میباشد.".PHP_EOL;
                        $Lang .= PHP_EOL."توضیحات لیست بن :".PHP_EOL;
                        $Lang .= "مدت زمان بن : ".$UserLang;
                        $Lang .= PHP_EOL." بن توسط : ".$checkInBanList['ban_by'];
                        $Lang .= PHP_EOL."به دلیل : ".$checkInBanList['for'];

                        return Request::sendMessage([
                            'chat_id' => self::$Dt->user_id,
                            'text' => vsprintf($Lang, [(isset($link) ? $link : self::$Dt->PlayerLink)]),
                            'parse_mode' => 'HTML',
                        ]);
                    }
                }
                GR::AddPlayerBanList($user_id);

                //  GR::AddActivity( vsprintf('مدیر %s به لیست بن از بازی اضافه کرد %s  به دلیل : %s رو.',[self::$Dt->user_link,self::$Dt->PlayerLink,$Text]));
                $inline_keyboard =  GR::GetBanlistKeyboard($Admin,$user_id);
                $Lang = "افزودن کاربر %s به لیست بن به دلیل : %s";
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => vsprintf($Lang,[(isset($fullname) ? $fullname : self::$Dt->ReplayFullname),$Text]),
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inline_keyboard,
                ]);
            }
        }

    }

    public static function PromateGlobalAdmin(){
        $Admin = GR::CheckUserGlobalAdmin(self::$Dt->user_id);
        if($Admin){
            if($Admin['onwer'] !== "Creator"){
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => "دسترسی به این بخش برای شما محدود شده است",
                    'parse_mode' => 'HTML',
                ]);
            }
            if(!self::$Dt->ReplayTo){
                return false;
            }
            $user_id = self::$Dt->ReplayTo;
            $Admin = GR::CheckUserGlobalAdmin(self::$Dt->ReplayTo);
            if($Admin){
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => vsprintf('مدیر %s از قبل در لیست مدیران موجود میباشد',[self::$Dt->ReplayFullname]),
                    'parse_mode' => 'HTML',
                ]);
            }

            GR::AddActivity( vsprintf('مدیر %s به لیست مدیران اضافه کرد %s رو.',[self::$Dt->user_link,self::$Dt->PlayerLink]));
            GR::AddToAdminList();
            GR::GetAdminSetting($user_id);
            return true;
        }
    }

    public static function AdminSetting(){
        $Ex = explode('/',self::$Dt->data);
        $Key = $Ex['1'];
        $user_id = $Ex['2'];
        $adminDetial  = GR::CheckUserGlobalAdmin($user_id);
        $Val = ($adminDetial[$Key] == 1 ? 0 : 1);
        GR::ChangeAdminSetting($Key,$Val,$user_id);
        $adminDetial2  = GR::CheckUserGlobalAdmin($user_id);
        $InlineKeyboard = GR::GetAdminKeyboard($adminDetial2);
        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => vsprintf('تنظیمات دسترسی مدیر : %s',[$adminDetial['fullname']]),
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => $InlineKeyboard,
        ]);
    }

    public static function CM_AdminSetting(){
        $Admin = GR::CheckUserGlobalAdmin(self::$Dt->user_id);
        if($Admin){
            if($Admin['onwer'] !== "Creator"){
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => "دسترسی به این بخش برای شما محدود شده است",
                    'parse_mode' => 'HTML',
                ]);
            }


            $user_id = self::$Dt->ReplayTo ?? self::$Dt->text;
            $name = self::$Dt->ReplayTo ?? "null";
            $Admin = GR::CheckUserGlobalAdmin($user_id);
            if(!$Admin){
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => vsprintf('%s در لیست مدیریت وجود ندارد',[self::$Dt->ReplayFullname ?? self::$Dt->text]),
                    'parse_mode' => 'HTML',
                ]);
            }

            GR::GetAdminSetting($user_id);
            return true;
        }
    }
    public static function RemoveAsBanList(){
        $Admin = GR::CheckUserGlobalAdmin(self::$Dt->user_id);
        if($Admin) {
            if ($Admin['remove_ban'] == 0) {
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => "دسترسی به این بخش برای شما محدود شده است",
                    'parse_mode' => 'HTML',
                ]);
            }

            $user_id = self::$Dt->ReplayTo;
            if ($user_id) {
                $checkInBanList = GR::CheckPlayerInBanList($user_id);
                if(!$checkInBanList){
                    $Lang = "کاربر %s در لیست بن نمیباشد.";
                    return  Request::sendMessage([
                        'chat_id' => self::$Dt->user_id,
                        'text' => vsprintf($Lang,[self::$Dt->ReplayFullname]),
                        'parse_mode' => 'HTML',
                    ]);
                }



                GR::RemoveFromBanList($user_id);
                GR::AddActivity( vsprintf('مدیر %s از لیست بن بازی خارج کرد کاربر %s رو.',[self::$Dt->user_link,self::$Dt->PlayerLink]));
                Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => vsprintf('کاربر %s با موفقیت از لیست بن خارج شد',[self::$Dt->PlayerLink]),
                    'parse_mode' => 'HTML',
                ]);

                return  Request::sendMessage([
                    'chat_id' => $user_id,
                    'text' => vsprintf('تبریک میگم الان دیگه توی لیست سیاه ربات نویسی و توسط %s از لیست بن خارج شدی.',[self::$Dt->user_link]),
                    'parse_mode' => 'HTML',
                ]);
            }
        }
    }


    public static function CM_Achievement(){
        $Achio = GR::GetAchievement();

    }

    public static function CM_NewChatTitle($title){

        Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => vsprintf('Changed Group Name : %s To : (%s)',[RC::Get('group_name') ?? "null",$title]),
            'parse_mode' => 'HTML',
        ]);
        RC::GetSet($title,'group_name');
        return true;
    }

    public static function CM_ChatId(){

        Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => self::$Dt->chat_id,
            'parse_mode' => 'HTML',
        ]);
    }
    public static function CM_Normal(){


        if((int) self::$Dt->user_id !== 556635252){
            return false;
        }



        $Avg = GR::GetAvg();


        foreach ($Avg as $row) {
            $NoPerfix = RC::NoPerfix();
            if($NoPerfix->get("{$row['_id']['group_id']}:group_link")) {

                $searchObject = $row['_id']['game_mode'];
                $keys = GR::searchForId($searchObject, $row['_id']['group_lang'],$Avg);

                $STD = [];
                $GameTime  = array_column($keys,'avg_gameTime');
                $GameTimeS  = GR::Stand_Deviation($GameTime);
                $STD['GameTime']  = ($GameTimeS > 0 ? $GameTimeS : 1);
                $STD['SumGameTime'] = 1;
                if($GameTimeS > 0) {
                    $STD['SumGameTime'] =  array_sum($GameTime) / count(array_filter($GameTime));
                }

                $NobesPlayer = array_column($keys,'avg_nobeplayer');
                $NobesPlayerS = GR::Stand_Deviation($NobesPlayer);
                $STD['NobesPlayer'] = ($NobesPlayerS > 0 ? $NobesPlayerS : 1);
                $STD['SumNobesPlayer'] = 1;
                if($NobesPlayerS > 0) {
                    $STD['SumNobesPlayer'] =  array_sum($NobesPlayer) / count(array_filter($NobesPlayer));
                }

                $AfkedPlayer = array_column($keys,'avg_afkedplayer');
                $AfkedPlayerS = GR::Stand_Deviation($AfkedPlayer);
                $STD['AfkedPlayer']  = ($AfkedPlayerS > 0 ? $AfkedPlayerS : 1);
                $STD['SumAfkedPlayer'] = 1;
                if($AfkedPlayerS > 0) {
                    $STD['SumAfkedPlayer'] = array_sum($AfkedPlayer) / count(array_filter($AfkedPlayer));
                }



                $PlayerCount= array_column($keys,'avg_PlayerCount');
                $PlayerCountS  = GR::Stand_Deviation($PlayerCount);
                $STD['PlayerCount']  = ($PlayerCountS > 0 ? $PlayerCountS : 1);
                $STD['SumPlayerCount'] = 1;
                if($PlayerCountS > 0) {
                    $STD['SumPlayerCount'] = array_sum($PlayerCount) / count(array_filter($PlayerCount));
                }
                $GameCount= array_column($keys,'count');
                $GameCountS  = GR::Stand_Deviation($GameCount);
                $STD['count'] = ($GameCountS > 0 ? $GameCountS : 1);
                $STD['SumCount'] = 0;
                if($GameCountS > 0){
                    $STD['SumCount'] =  array_sum($GameCount) / count(array_filter($GameCount));
                }

                $STD['count'] = ($STD['count'] == 0 ? 1 : $STD['count'] );

                $STD['PlayerCount'] = ($STD['PlayerCount'] == 0 ? 1 : $STD['PlayerCount'] );
                $STD['NobesPlayer'] = ($STD['NobesPlayer'] == 0 ? 1 : $STD['NobesPlayer'] );
                $STD['AfkedPlayer'] = ($STD['AfkedPlayer'] == 0 ? 1 : $STD['AfkedPlayer'] );
                $STD['GameTime'] = ($STD['GameTime'] == 0 ? 1 : $STD['GameTime'] );


                $score =
                    (($row['avg_PlayerCount'] - $STD['SumPlayerCount'] ) / ($STD['PlayerCount'] ?? 1)) * 5
                    + (($row['avg_gameTime'] - $STD['SumGameTime']) / ($STD['GameTime'] ?? 1 )) * 4
                    + (($row['avg_nobeplayer'] - $STD['SumNobesPlayer']) / ($STD['NobesPlayer'] ?? 1)) * -1
                    + (( $row['avg_afkedplayer'] ?? 0 - $STD['SumAfkedPlayer'] ?? 0) / ($STD['AfkedPlayer'] ?? 1)) * -3
                    + (( $row['count'] - $STD['SumCount']) / ($STD['count'] ?? 1)) * 2;

                GR::SaveGroupList($row['_id']['game_mode'],$row['_id']['group_lang'],$row['_id']['group_id'],$score,$row,$NoPerfix->get("{$row['_id']['group_id']}:group_name"));

                $GroupName = '<a href="' . $NoPerfix->get("{$row['_id']['group_id']}:group_link") . '">';
                $GroupName .= $NoPerfix->get("{$row['_id']['group_id']}:group_name");
                $GroupName .= "</a>";

                Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => $GroupName.">> Score: {$score} >> on Game Mode:".$row['_id']['game_mode'].":".$row['_id']['group_lang'],
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => 'true',
                ]);
            }
        }
    }


    public static function SendGroupList($lang,$mode){
        self::$Dt->LM = new Lang(FALSE);
        self::$Dt->LM->load("{$mode}_".$lang, FALSE);


        $re = Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'text' => self::$Dt->L->_('ListGroupFor', array("{0}"=> self::ReCodeLang($lang), "{1}" => self::$Dt->LM->_('game_mode'))),
            'reply_markup' => new InlineKeyboard([]),
        ]);
        GR::GetGroupList($lang,$mode);
    }
    public static function SelectGroupList($for){
        $reply_markup = self::_getGameMode($for,"GroupGameMode_{$for}_",true);
        if($reply_markup) {
            self::$Dt->LM = new Lang(FALSE);
            self::$Dt->LM->load("main_".$for, FALSE);

            $re = Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'message_id' => self::$Dt->message_id,
                'text' => self::$Dt->LM->_('GetListForMode',array("{0}" => self::ReCodeLang($for))),
                'reply_markup' => $reply_markup,
            ]);
        }
    }
    public static function CM_GroupList(){
        $reply_markup = self::GetLangKeyboad('Grouplist_');
        if($reply_markup) {
            $re = Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('GetGroupList_Step_Lang'),
                'reply_markup' => $reply_markup,
            ]);
            if($re->isOk()) {
                if (self::$Dt->typeChat !== "private") {
                    Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => "<strong>" . self::$Dt->L->_('pmSendToPrivate') . "</strong>",
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML',
                    ]);
                }
            }else{
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => "<strong>" . self::$Dt->L->_('PleaseStartBot') . "</strong>",
                    'reply_to_message_id' => self::$Dt->message_id,
                    'parse_mode' => 'HTML',
                ]);
            }

        }

    }

    public static function CM_Sync(){
        $SyncData = self::SyncUser(self::$Dt->user_id);
        if($SyncData){

            $array = array("{0}" => $SyncData['total_game_play'] ,"{1}" => $SyncData['game_won'] , "{2}" => $SyncData['game_lost']  ,"{3}" => $SyncData['game_survived']);
            $Stats = self::$Dt->L->_('StateS',$array);
            $Nop = RC::NoPerfix();
            $Nop->set('user:stats:'.self::$Dt->user_id,$Stats);
            $PlayerM = self::$Dt->L->_('SyncUser',$array);
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => $PlayerM,
                'parse_mode' => 'HTML',
            ]);
        }
        return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => 'هوز بازی برای شما ثبت نشده است',
            'parse_mode' => 'HTML',
        ]);

    }

    public static function CM_Gets(){

        $NoP = RC::NoPerfix();
        if(self::$Dt->ReplayTo){
            if($NoP->exists('user:stats:'.self::$Dt->ReplayTo)){
                return Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->L->_('StatsG',array("{0}" => self::$Dt->PlayerLink, "{1}" => $NoP->get('user:stats:'.self::$Dt->ReplayTo))),
                    'parse_mode' => 'HTML',
                ]);
            }else{
                return Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->L->_('NoStateInW',array("{0}"=> self::$Dt->user_link)),
                    'parse_mode' => 'HTML',
                ]);
            }
        }

        if($NoP->exists('user:stats:'.self::$Dt->user_id)){
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => self::$Dt->L->_('StatsG',array("{0}" => self::$Dt->user_link, "{1}" => $NoP->get('user:stats:'.self::$Dt->user_id))),
                'parse_mode' => 'HTML',
            ]);
        }else{
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => self::$Dt->L->_('NoStateInW',array("{0}"=> self::$Dt->user_link)),
                'parse_mode' => 'HTML',
            ]);
        }


    }

    public static function is_404($url) {
        $handle = curl_init($url);
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);

        /* Get the HTML or whatever is linked in $url. */
        $response = curl_exec($handle);

        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        return $httpCode;
    }

    public static function SyncUser($user_id){
        if( self::is_404("https://www.tgwerewolf.com/Stats/PlayerStats/?pid=" . $user_id) == "200" ) {
            $data = file_get_contents("https://www.tgwerewolf.com/Stats/PlayerStats/?pid=" . $user_id);
            if ($data) {
                $re = json_decode($data);
                if (empty($re)) {

                    return 0;
                } else {
                    preg_match_all('!\d+!', $re, $matches);
                    return ['total_game_play' => $matches['0']['0'], 'game_won' => $matches['0']['1'], 'game_lost' => $matches['0']['3'], 'game_survived' => $matches['0']['5']];
                }
            } else {
                return 0;
            }
        }else{
            return 0;
        }

    }



    public static function CM_ModeInfo(){
        if(self::$Dt->typeChat !== "private") {
            $checkStartGame = GR::CheckGPGameState();
            switch ($checkStartGame){
                case 0:
                    Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('NotGameMode'),
                        'parse_mode' => 'HTML'
                    ]);
                    break;
                case 2:
                case 1:
                    $GameMode = RC::Get('GamePl:gameModePlayer');
                    $Lang = self::$Dt->L->_($GameMode.'_modinfo');
                    Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => $Lang,
                        'parse_mode' => 'HTML'
                    ]);
                    break;
            }
        }
    }


    public static function SendMessageToPV($from_chat_id,$Message_id){
        //$data = GR::GetPlayerLists();

        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => $from_chat_id."|".$Message_id,
            'message_id' => $Message_id
        ]);

        //   $NoP = RC::NoPerfix();
        //   $countSend =  0;
        //  foreach ($data as $row) {

        //     if($NoP->exists('SendPvUser2:'.$row['user_id'])){
        //     continue;
        //   }

        //  $re = role_trouble
        //      'chat_id' => $row['user_id'],
        //      'from_chat_id' => $from_chat_id,
        //      'message_id' => $Message_id
        //   ]);
        //  if($re->isOk()){
        // $countSend++;
        //  }
        //  $NoP->set('SendPvUser2:'.$row['user_id'],true);
        /// }

        //  Request::sendMessage([
        //      'chat_id' => self::$Dt->user_id,
        //     'text' => "Send For: ".$countSend,
        //     'parse_mode'=> 'HTML',
        //  ]);

    }


    public static function CM_Reset(){

        if((int) self::$Dt->user_id !== 556635252){
            return false;
        }

        $NoP = RC::NoPerfix();
        $Keys = $NoP->keys('userGameTime:*');
        foreach ($Keys as $key){
            $NoP->set($key,0);
        }

        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => "Reset Count: ".count($Keys),
            'parse_mode'=> 'HTML',
        ]);

    }

    public static function CM_Getstatus(){
        $info = Request::getWebhookInfo();
        if($info->ok == true){
            $state = self::$Dt->L->_('status_ok');
        }else{
            $state = self::$Dt->L->_('status_off');
        }

        Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => $state,
            'parse_mode'=> 'HTML',
        ]);

    }


    public static function CM_RunInfo(){
        /*
                $NoP = RC::NoPerfix();
                $keys  = $NoP->keys('userGameTime:*');
                foreach ($keys as $row){
                    $NoP->del($row);
                }
        */
        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('RunInfo',array('{0}' => GR::GetUptime() , '{1}' => GR::get_tgame() ,'{2}' => GR::get_tplayer())),
            'parse_mode'=> 'HTML',
        ]);

    }



    // Challenge Game
    public static function CM_StartChallenge(){

        if(self::$Dt->typeChat !== "private") {
            $checkStartGame = GR::CheckGPGameState();

            switch ($checkStartGame) {
                case 0:
                    $inline_keyboard = new InlineKeyboard(
                        [['text' => self::$Dt->L->_('JoinChallenge'), 'url' => self::$Dt->JoinLink]]
                    );
                    $result = Request::sendVideo(['chat_id' => self::$Dt->chat_id,
                        'video' => RC::RandomGif('start_challenge'),
                        'caption' => self::$Dt->L->_('StartChallengeGame', self::$Dt->user_link),
                        'parse_mode' => 'HTML',
                        'reply_markup' => $inline_keyboard,]);
                    if ($result->isOk()) {
                        RC::rpush($result->getResult()->getMessageId(), 'ch:EditMarkup');
                    }
                    else {
                        Request::sendMessage([
                            'chat_id' => self::$Dt->chat_id,
                            'text' => self::$Dt->L->_('NotBotEnableGifOnGroup'),

                        ]);
                    }
                    Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('ChallengePlayers', 0, ''),
                        'parse_mode' => 'HTML',
                    ]);

                    $re = Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('ChallengeStart'),
                        'parse_mode' => 'HTML',
                    ]);
                    if ($re->isOk()) {
                        RC::rpush($re->getResult()->getMessageId(), 'ch:deleteMessage');
                    }
                    break;
                case 3:
                    $inline_keyboard = new InlineKeyboard(
                        [
                            ['text' => self::$Dt->LG->_('JoinChallenge'), 'url' => self::$Dt->ChallengeJoin]
                        ]

                    );
                    $result = Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->LG->_('StartLastChallenge'),
                        'reply_markup' => $inline_keyboard,
                    ]);
                    if($result->isOk()) {
                        RC::rpush($result->getResult()->getMessageId(),'ch:deleteMessage');
                    }
                    break;
                case 2:
                    $inline_keyboard = new InlineKeyboard(
                        [
                            ['text' => self::$Dt->LG->_('joinToGame'), 'url' => self::$Dt->JoinLink]
                        ]

                    );
                    $result = Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->LG->_('startLastGame'),
                        'reply_markup' => $inline_keyboard,
                    ]);
                    if($result->isOk()) {
                        RC::rpush($result->getResult()->getMessageId(),'deleteMessage');
                    }

                    break;
                default:
                    return false;
                    break;

            }
        }
    }


    public static function CM_KillGame(){
        if(self::$Dt->typeChat !== "private") {

            if(self::$Dt->admin == 0){
                return  Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->L->_('NotAllowForUser'),
                    'reply_to_message_id' => self::$Dt->message_id,
                    'parse_mode' => 'HTML',
                ]);
            }


            $checkStartGame = GR::CheckGPGameState();
            switch ($checkStartGame){
                case 0:
                    Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('NotGameForKill'),
                        'parse_mode' => 'HTML'
                    ]);
                    break;
                case 2:
                case 1:
                    GR::KillGame();
                    Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('KillGame',array("{0}" => self::$Dt->user_link)),
                        'parse_mode' => 'HTML'
                    ]);

                    break;
            }
        }
    }


    public static function CM_Live(){



        $List = GR::GetLive();

        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => $List,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => 'true',
        ]);

    }

    public static  function CallBackQuery(){

        $Nop = RC::NoPerfix();

        $data    = ['inline_query_id' => self::$Dt->inline->getId(),'cache_time ' => 0 ,'is_personal'=> true];

        $results = [];

        $List = GR::GetUserdeaths();

        if($Nop->exists('user_state_chache:'.self::$Dt->user_id)){
            $Stats = $Nop->get('user_state_chache:'.self::$Dt->user_id);
        }else {
            $Stats = GR::GetStats(self::$Dt->user_id);
            $Nop->set('user_state_chache:' . self::$Dt->user_id, $Stats);
            $Nop->expire('user_state_chache:' . self::$Dt->user_id, 300);
        }

        if($Stats){
            $Stats = $Stats;
        }else{
            $Stats = self::$Dt->L->_('emptyStates');
        }




        if($Nop->exists('user_Social:'.self::$Dt->user_id)){
            $Social = $Nop->get('user_Social:'.self::$Dt->user_id);
        }else {
            $Social = GR::GetSocialUser();
            $Nop->set('user_Social:'.self::$Dt->user_id,$Social) ;
            $Nop->expire('user_Social:'.self::$Dt->user_id,1500);
        }




        $articles = [
            [
                'id'                    => '001',
                'title'                 => 'فعالیت ها',
                'description'           => 'کجا ها مردید و چند درصد',
                'input_message_content' => new InputTextMessageContent(['message_text' => $List,'parse_mode'=> 'html']),
            ],
            [
                'id'                    => '002',
                'title'                 => 'وضعیت بازی',
                'description'           => 'وضعیت بازی شما در جغد تاریکی',
                'input_message_content' => new InputTextMessageContent(['message_text' => ' ' . $Stats,'parse_mode'=> 'html']),
            ],
            [
                'id'                    => '003',
                'title'                 => 'آمار بازی',
                'description'           => 'آمار بازی شما با دوستان بیشترین لاوری  و...' ,
                'input_message_content' => new InputTextMessageContent(['message_text' => $Social ,'parse_mode'=> 'html']),
            ],
        ];

        foreach ($articles as $article) {
            $results[] = new InlineQueryResultArticle($article);
        }


        $data['results'] = '[' . implode(',', $results) . ']';

        return Request::answerInlineQuery($data);
    }


    public static function CM_GroupStats(){

        if(self::$Dt->typeChat == "private") {
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' =>  self::$Dt->L->_('SendToGroup'),
                'parse_mode' => 'HTML',
            ]);
        }

        if(self::$Dt->admin == 0){
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => "<strong>" . self::$Dt->L->_('YouNotAdminGp') . "</strong>",
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
            ]);
        }

        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => GR::GroupStats(),
            'parse_mode' => 'HTML',
        ]);


        if (self::$Dt->typeChat !== "private") {
            Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => "<strong>" . self::$Dt->L->_('pmSendToPrivate') . "</strong>",
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
            ]);
        }

    }

    public static function CM_GetCoin(){
        $NoP = RC::NoPerfix();

        if($NoP->exists('userGetCoin:'.self::$Dt->user_id)){
            $InTime  = $NoP->get('userGetCoin:'.self::$Dt->user_id);
            $Left = time() - $InTime;
            $Minux = 10 - floor($Left / 60) ;
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('LastGetCoins',$Minux),
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
            ]);
        }

        $UserCr = GR::GetUserCredit();
        $New = $UserCr + 60;
        GR::MinCreditCredit($New);

        $NoP->set('userGetCoin:'.self::$Dt->user_id,time());
        $NoP->expire('userGetCoin:'.self::$Dt->user_id,600);

        return  Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('GetCoin',$New),
            'parse_mode' => 'HTML',
        ]);

    }


    public static function CM_MyCoin(){

        $UserCr = GR::GetUserCredit();
        return  Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('MyCoinD',$UserCr),
            'parse_mode' => 'HTML',
        ]);

    }

    public static function CM_Dontate(){
        $inline_keyboard = new InlineKeyboard(
            [
                ['text' => self::$Dt->L->_('DonateTextU'), 'url' => "https://idpay.ir/onyxwerewolf"]
            ]

        );
        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('DonateText',array("{0}" => self::$Dt->user_link)),
            'reply_markup' => $inline_keyboard,
            'parse_mode' => 'html'
        ]);

    }


    /** @noinspection MissingIssetImplementationInspection */
    public static function CM_addfriend(){



        $Text = self::$Dt->text;
        if(isset($Text)) {
            if (is_numeric($Text) and strlen($Text) > 7) {
                $user_id = self::$Dt->text;
            } elseif (preg_match("/^(?:[a-zA-Z0-9?. ]?)+@([a-zA-Z0-9]+)(.+)?$/", $Text, $matches)) {
                $username = $matches[0];
                $CheckUsername  = GR::CheckPlayerByUsername($username);
                if(!$CheckUsername){
                    return  Request::sendMessage([
                        'chat_id' => self::$Dt->user_id,
                        'text' => self::$Dt->L->_('NotFoundUser'),
                        'parse_mode' => 'HTML'
                    ]);
                }

                $user_id = $CheckUsername['user_id'];
            }
        }



        if(isset(self::$Dt->ReplayTo)){
            $user_id = self::$Dt->ReplayTo;
            $fullname = self::$Dt->fullname;
        }


        if(!isset($user_id)){
            return  Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('NotFoundUser'),
                'parse_mode' => 'HTML'
            ]);
        }


        $CheckUser = GR::CheckUserById($user_id);
        if(!$CheckUser){
            return  Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('NotFoundUser'),
                'parse_mode' => 'HTML'
            ]);
        }
        if($user_id == self::$Dt->user_id){
            return  Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('NotYouFriend'),
                'parse_mode' => 'HTML'
            ]);
        }


        $fullname =  GR::ConvertName($CheckUser['user_id'],$CheckUser['fullname']);

        $CheckLastFriend = GR::CheckLastFriend($user_id);
        if($CheckLastFriend){
            return  Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('LastIn',$fullname),
                'parse_mode' => 'HTML'
            ]);
        }

        $Np = RC::NoPerfix();

        if($Np->exists("userAddReq:{$user_id}:".self::$Dt->user_id)){
            $msg_id = $Np->get("userAddReq:{$user_id}:".self::$Dt->user_id);
            $Ex = explode("|",$msg_id);
            $inline_keyboard = new InlineKeyboard(
                [
                    ['text' => self::$Dt->L->_('AddedFriendNo'), 'callback_data' => "AddFriend_remove/" . $user_id."/".$Ex['1']]
                ]
            );
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('LastSendReq', $fullname),
                'reply_markup' => $inline_keyboard,
                'parse_mode' => 'html'
            ]);
        }

        $inline_keyboard2 = new InlineKeyboard(
            [['text' => self::$Dt->L->_('AddFriendBackNo'),'callback_data' => "AddFriend_no/".self::$Dt->user_id ],['text' => self::$Dt->L->_('AddFriendBackOk'),'callback_data' => "AddFriend_ok/".self::$Dt->user_id ]],
            [['text' => self::$Dt->L->_('AddFriendBackOkBack'),'callback_data' => "AddFriend_addback/".self::$Dt->user_id ]]
        );
        $re = Request::sendMessage([
            'chat_id' => $user_id,
            'text' => self::$Dt->L->_('AddFriendCallBack',self::$Dt->user_link),
            'reply_markup' => $inline_keyboard2,
            'parse_mode' => 'html'
        ]);

        if($re->isOk()) {
            $msg_id = $re->getResult()->getMessageId();
            $inline_keyboard = new InlineKeyboard(
                [
                    ['text' => self::$Dt->L->_('AddedFriendNo'), 'callback_data' => "AddFriend_remove/" . $user_id."/".$msg_id]
                ]

            );
            $re = Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('AddedFriendToList', self::$Dt->user_link, $fullname),
                'reply_markup' => $inline_keyboard,
                'parse_mode' => 'html'
            ]);
            if($re->isOk()){
                $Np->set("userAddReq:".$user_id.":".self::$Dt->user_id,$re->getResult()->getMessageId()."|".$msg_id."|".self::$Dt->fullname);
            }
            return true;
        }
        return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('NotSend', $fullname),
            'parse_mode' => 'html'
        ]);


    }

    public static function FriendR($cm,$user_id,$msg_id = false){
        $Np = RC::NoPerfix();
        switch ($cm){
            case 'AddFriend_remove':
                if($Np->exists("userAddReq:{$user_id}:".self::$Dt->user_id)){
                    $Get = $Np->get("userAddReq:{$user_id}:".self::$Dt->user_id);
                    $Ex = explode("|",$Get);
                    $msg_id = $Ex['1'];

                    $re = Request::deleteMessage([
                        'chat_id' => $user_id,
                        'message_id' => $msg_id,
                    ]);

                    $Np->del("userAddReq:{$user_id}:".self::$Dt->user_id);
                    $inline_keyboard = new InlineKeyboard([]);
                    return Request::editMessageText([
                        'chat_id' => self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'text' => self::$Dt->L->_('RemoveSuccess'),
                        'reply_markup' => $inline_keyboard,
                    ]);
                }

                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => self::$Dt->L->_('RemoveNotFind'),
                    'parse_mode' => 'html'
                ]);
                break;

            case 'AddFriend_no':
                if($Np->exists("userAddReq:".self::$Dt->user_id.":".$user_id)) {
                    $Get = $Np->get("userAddReq:".self::$Dt->user_id.":".$user_id);
                    $Ex = explode("|", $Get);
                    Request::sendMessage([
                        'chat_id' => $user_id,
                        'text' => self::$Dt->L->_('AddFriendNoBacks',self::$Dt->user_link),
                        'parse_mode' => 'html'
                    ]);
                    Request::editMessageReplyMarkup([
                        'chat_id' =>  $user_id,
                        'message_id' => $Ex['0'],
                        'reply_markup' => new InlineKeyboard([]),
                    ]);
                    $Np->del("userAddReq:".self::$Dt->user_id.":".$user_id);

                    Request::sendMessage([
                        'chat_id' => self::$Dt->user_id,
                        'text' => self::$Dt->L->_('RemoveRequestS',$Ex['2']),
                        'parse_mode' => 'html'
                    ]);
                    return Request::editMessageReplyMarkup([
                        'chat_id' =>  self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'reply_markup' => new InlineKeyboard([]),
                    ]);

                }
                break;
            case 'AddFriend_ok':
                if($Np->exists("userAddReq:".self::$Dt->user_id.":".$user_id)) {
                    $Get = $Np->get("userAddReq:".self::$Dt->user_id.":".$user_id);
                    $Ex = explode("|", $Get);

                    Request::editMessageReplyMarkup([
                        'chat_id' =>  $user_id,
                        'message_id' => $Ex['0'],
                        'reply_markup' => new InlineKeyboard([]),
                    ]);
                    Request::sendMessage([
                        'chat_id' => $user_id,
                        'text' => self::$Dt->L->_('AddFriendIn',self::$Dt->user_link),
                        'parse_mode' => 'html'
                    ]);

                    GR::AddToFriendS($user_id,self::$Dt->user_id);

                    Request::sendMessage([
                        'chat_id' => self::$Dt->user_id,
                        'text' => self::$Dt->L->_('AddFriendOk',$Ex['2']),
                        'parse_mode' => 'html'
                    ]);
                    return Request::editMessageReplyMarkup([
                        'chat_id' =>  self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'reply_markup' => new InlineKeyboard([]),
                    ]);

                }
                break;
            case 'AddFriend_addback':
                if($Np->exists("userAddReq:".self::$Dt->user_id.":".$user_id)) {
                    $Get = $Np->get("userAddReq:".self::$Dt->user_id.":".$user_id);
                    $Ex = explode("|", $Get);

                    Request::editMessageReplyMarkup([
                        'chat_id' =>  $user_id,
                        'message_id' => $Ex['0'],
                        'reply_markup' => new InlineKeyboard([]),
                    ]);
                    Request::sendMessage([
                        'chat_id' => $user_id,
                        'text' => self::$Dt->L->_('AddFriendIn',self::$Dt->user_link),
                        'parse_mode' => 'html'
                    ]);

                    GR::AddToFriendS($user_id,self::$Dt->user_id);
                    GR::AddToFriendS(self::$Dt->user_id,$user_id);
                    Request::sendMessage([
                        'chat_id' => self::$Dt->user_id,
                        'text' => self::$Dt->L->_('AddFriendOk',$Ex['2']),
                        'parse_mode' => 'html'
                    ]);
                    return Request::editMessageReplyMarkup([
                        'chat_id' =>  self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'reply_markup' => new InlineKeyboard([]),
                    ]);

                }
                break;

        }
    }


    public static function CM_AddGroup(){


        $Text = self::$Dt->text;

        if(isset($Text)) {
            if (is_numeric($Text) and strlen($Text) > 7) {
                $chat_id = self::$Dt->text;
            }else {
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => "لطفا ای دی گروه را همراه با کامند ارسال کنید",
                    'parse_mode' => 'HTML',
                ]);
            }

        }else {
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => "لطفا ای دی گروه را همراه با کامند ارسال کنید",
                'parse_mode' => 'HTML',
            ]);
        }


        GR::AddWhiteList($chat_id);
        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => "گروه مورد نظر با موفقیت به لیست اضافه شد.",
            'parse_mode' => 'HTML',
        ]);

        return Request::sendMessage([
            'chat_id' => $chat_id,
            'text' => "گروه شما با موفقیت تا تاریخ".jdate('Y-m-d H:i:s',strtotime('+30 day', time()))." به فهرست مجار برای بازی اضافه شد از این پس میتوانید بازی کنید در این گروه ♥.",
            'parse_mode' => 'HTML',
        ]);


    }

    public static function CM_MyLevel(){

        $L = GR::GetLevel();

        if($L){
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' =>$L,
                'parse_mode' => 'html'
            ]);
        }

        return false;
    }

    public static function CM_setcultmessage(){

        if(self::$Dt->typeChat == "private") {
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' =>  self::$Dt->L->_('SendToGroup'),
                'parse_mode' => 'HTML',
            ]);
        }

        if(self::$Dt->admin == 0){
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => "<strong>" . self::$Dt->L->_('YouNotAdminGp') . "</strong>",
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
            ]);
        }

        return true;

    }

    public static function CM_Profile(){

        $UserId = (self::$Dt->ReplayTo  ? self::$Dt->ReplayTo : self::$Dt->user_id);
        $GetState = GR::GetDAState($UserId);

        if($GetState['lang']){
            $Keyboard = [];
            if(self::$Dt->user_id == (float) $UserId){
                $Keyboard[] = [
                    ['text' => 'ثبت تصویر پروفایل 📸' ,'callback_data' => 'SetProfilePic/'.$UserId]
                ];
            }

            $inline_keyboard = (count($Keyboard) ? new InlineKeyboard(...$Keyboard) : new InlineKeyboard([[]]));

            if(isset($GetState['da']['avatar'])){
                if($GetState['da']['avatar']) {
                    if ($GetState['da']['avatar_type'] == 'document') {
                        return Request::sendVideo([
                            'chat_id' => self::$Dt->chat_id,
                            'video' => $GetState['da']['avatar'],
                            'caption' => $GetState['lang'],
                            'reply_to_message_id' => self::$Dt->message_id,
                            'reply_markup' => $inline_keyboard,
                            'parse_mode' => 'HTML',
                        ]);
                    }
                    if ($GetState['da']['avatar_type'] == 'photo') {
                        return Request::sendPhoto([
                            'chat_id' => self::$Dt->chat_id,
                            'photo' => $GetState['da']['avatar'],
                            'caption' => $GetState['lang'],
                            'reply_to_message_id' => self::$Dt->message_id,
                            'reply_markup' => $inline_keyboard,
                            'parse_mode' => 'HTML',
                        ]);
                    }
                }
            }
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => $GetState['lang'],
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => $inline_keyboard,
            ]);
        }

        return Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => self::$Dt->L->_('NoStateDA'),
            'reply_to_message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML'
        ]);

    }


    public static function  SetProfilePic($userid){
        if((float) $userid !== self::$Dt->user_id){
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id,'text' => '❌ شما دسترسی ندارید','show_alert' => true]);
        }

        $Coin = (float) self::$Dt->Player['coin'];
        $needed_coin = PROFILE_COIN;
         if($Coin < $needed_coin){
             return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id,'text' => '❌ شما برای ثبت تصویر پروفایل نیاز به '.PROFILE_COIN.' سکه دارید','show_alert' => true]);
         }

        $NOp = RC::NoPerfix();
        $NOp->set('SetProfilePic:'.$userid,true);
        return Request::sendMessage([
            'chat_id' => $userid,
            'text' => 'لطفا تصویر/گیف مورد نظر را ارسال کنید:',
            'parse_mode' => 'HTML'
        ]);
    }

    public static function CM_SendFile($type,$doc){
        $NOp = RC::NoPerfix();
        if(!$NOp->exists('SetProfilePic:'.self::$Dt->user_id)){
            return false;
        }
        $needed_coin = PROFILE_COIN;

        $NOp->del('SetProfilePic:'.self::$Dt->user_id);
        ($type === 'photo') && $doc = end($doc);
        $file_id = $doc->getFileId();
        $file    = Request::getFile(['file_id' => $file_id]);
        if ($file->isOk() && Request::downloadFile($file->getResult())) {
            $FilePath = 'https://bot.boldwolf.ir/Download/' . $file->getResult()->getFilePath();

            GR::UpdateCoin(((int) self::$Dt->Player['coin'] - $needed_coin), self::$Dt->user_id);

            GR::UpdateUserPhoto($FilePath,$type,self::$Dt->user_id);
            if($type === 'photo') {
                $result = Request::sendPhoto([
                    'chat_id' => self::$Dt->user_id,
                    'caption' => '✅ تصویر پروفایل شما با موفقیت بروزرسانی شد',
                    'photo' => $FilePath,
                ]);

                $result = Request::sendPhoto([
                    'chat_id' => 131759635,//131759635
                    'caption' => self::$Dt->user_id.PHP_EOL.$FilePath.PHP_EOL.self::$Dt->fullname.PHP_EOL.self::$Dt->username,
                    'photo' => $FilePath,
                ]);

            }else{
                $result = Request::sendVideo([
                    'chat_id' => self::$Dt->user_id,
                    'video' => $FilePath,
                    'caption' => '✅ تصویر پروفایل شما با موفقیت بروزرسانی شد',
                    'parse_mode' => 'HTML',
                ]);

                $result = Request::sendVideo([
                    'video' => $FilePath,
                    'chat_id' => 131759635,//131759635
                    'caption' => self::$Dt->user_id.PHP_EOL.$FilePath.PHP_EOL.self::$Dt->fullname.PHP_EOL.self::$Dt->username,
                    'parse_mode' => 'HTML',
                ]);
            }


        }else{
            $re = Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => 'not Can Download',
                'parse_mode' => 'HTML',
            ]);
        }

    }

    public static function CM_Hero(){
        $UserId = (self::$Dt->ReplayTo  ? self::$Dt->ReplayTo : self::$Dt->user_id);
        $GetHero= GR::GetHero($UserId);

        if($GetHero){
            return Request::sendPhoto([
                'chat_id' => $UserId,
                'photo' => Request::encodeFile($GetHero['image']),
                'caption' => $GetHero['text'],
                'parse_mode' => 'HTML'
            ]);
        }

        return Request::sendMessage([
            'chat_id' => $UserId,
            'text' => self::$Dt->L->_('NoStateDA'),
            'parse_mode' => 'HTML'
        ]);
    }


    public static function CM_AllianCeranking(){

    }

    public static function CM_SetGif(){
        if(!in_array(self::$Dt->user_id,self::$Dt->Bot_admins)){
            return false;
        }
        $unicodes = array( '1F600','1F603','1F604','1F601','1F606','1F605','1F923','1F602','1F642','1F643','1F609','1F60A','1F607','1F970','1F60D','1F929','1F618','1F617','263A','1F61A','1F619','1F60B','1F61B','1F61C','1F92A','1F61D','1F911','1F917','1F92D','1F92B','1F914','1F910','1F928','1F610','1F611','1F636','1F60F','1F612','1F644','1F62C','1F925','1F60C','1F614','1F62A','1F924','1F634','1F637','1F912','1F915','1F922','1F92E','1F927','1F975','1F976','1F974','1F635','1F92F','1F920','1F973','1F60E','1F913','1F9D0','1F615','1F61F','1F641','2639','1F62E','1F62F','1F632','1F633','1F97A','1F626','1F627','1F628','1F630','1F625','1F622','1F62D','1F631','1F616','1F623','1F61E','1F613','1F629','1F62B','1F971','1F624','1F621','1F620','1F92C','1F608','1F47F','1F480','2620','1F4A9','1F921','1F479','1F47A','1F47B','1F47D','1F47E','1F916','1F63A','1F638','1F639','1F63B','1F63C','1F63D','1F640','1F63F','1F63E','1F648','1F649','1F64A','1F48B','1F48C','1F498','1F49D','1F496','1F497','1F493','1F49E','1F495','1F49F','2763','1F494','2764','1F9E1','1F49B','1F49A','1F499','1F49C','1F90E','1F5A4','1F90D','1F4AF','1F4A2','1F4A5','1F4AB','1F4A6','1F4A8','1F573','1F4A3','1F4AC','1F441','FE0F','200D','1F5E8','FE0F','1F5E8','1F5EF','1F4AD','1F4A4','1F44B','1F91A','1F590','270B','1F596','1F44C','1F90F','270C','1F91E','1F91F','1F918','1F919','1F448','1F449','1F446','1F595','1F447','261D','1F44D','1F44E','270A','1F44A','1F91B','1F91C','1F44F','1F64C','1F450','1F932','1F91D','1F64F','270D','1F485','1F933','1F4AA','1F9BE','1F9BF','1F9B5','1F9B6','1F442','1F9BB','1F443','1F9E0','1F9B7','1F9B4','1F440','1F441','1F445','1F444','1F476','1F9D2','1F466','1F467','1F9D1','1F471','1F468','1F9D4','1F471','200D','2642','FE0F','1F468','200D','1F9B0','1F468','200D','1F9B1','1F468','200D','1F9B3','1F468','200D','1F9B2','1F469','1F471','200D','2640','FE0F','1F469','200D','1F9B0','1F469','200D','1F9B1','1F469','200D','1F9B3','1F469','200D','1F9B2','1F9D3','1F474','1F475','1F64D','1F64D','200D','2642','FE0F','1F64D','200D','2640','FE0F','1F64E','1F64E','200D','2642','FE0F','1F64E','200D','2640','FE0F','1F645','1F645','200D','2642','FE0F','1F645','200D','2640','FE0F','1F646','1F646','200D','2642','FE0F','1F646','200D','2640','FE0F','1F481','1F481','200D','2642','FE0F','1F481','200D','2640','FE0F','1F64B','1F64B','200D','2642','FE0F','1F64B','200D','2640','FE0F','1F9CF','1F9CF','200D','2642','FE0F','1F9CF','200D','2640','FE0F','1F647','1F647','200D','2642','FE0F','1F647','200D','2640','FE0F','1F926','1F926','200D','2642','FE0F','1F926','200D','2640','FE0F','1F937','1F937','200D','2642','FE0F','1F937','200D','2640','FE0F','1F468','200D','2695','FE0F','1F469','200D','2695','FE0F','1F468','200D','1F393','1F469','200D','1F393','1F468','200D','1F3EB','1F469','200D','1F3EB','1F468','200D','2696','FE0F','1F469','200D','2696','FE0F','1F468','200D','1F33E','1F469','200D','1F33E','1F468','200D','1F373','1F469','200D','1F373','1F468','200D','1F527','1F469','200D','1F527','1F468','200D','1F3ED','1F469','200D','1F3ED','1F468','200D','1F4BC','1F469','200D','1F4BC','1F468','200D','1F52C','1F469','200D','1F52C','1F468','200D','1F4BB','1F469','200D','1F4BB','1F468','200D','1F3A4','1F469','200D','1F3A4','1F468','200D','1F3A8','1F469','200D','1F3A8','1F468','200D','2708','FE0F','1F469','200D','2708','FE0F','1F468','200D','1F680','1F469','200D','1F680','1F468','200D','1F692','1F469','200D','1F692','1F46E','1F46E','200D','2642','FE0F','1F46E','200D','2640','FE0F','1F575','1F575','FE0F','200D','2642','FE0F','1F575','FE0F','200D','2640','FE0F','1F482','1F482','200D','2642','FE0F','1F482','200D','2640','FE0F','1F477','1F477','200D','2642','FE0F','1F477','200D','2640','FE0F','1F934','1F478','1F473','1F473','200D','2642','FE0F','1F473','200D','2640','FE0F','1F472','1F9D5','1F935','1F470','1F930','1F931','1F47C','1F385','1F936','1F9B8','1F9B8','200D','2642','FE0F','1F9B8','200D','2640','FE0F','1F9B9','1F9B9','200D','2642','FE0F','1F9B9','200D','2640','FE0F','1F9D9','1F9D9','200D','2642','FE0F','1F9D9','200D','2640','FE0F','1F9DA','1F9DA','200D','2642','FE0F','1F9DA','200D','2640','FE0F','1F9DB','1F9DB','200D','2642','FE0F','1F9DB','200D','2640','FE0F','1F9DC','1F9DC','200D','2642','FE0F','1F9DC','200D','2640','FE0F','1F9DD','1F9DD','200D','2642','FE0F','1F9DD','200D','2640','FE0F','1F9DE','1F9DE','200D','2642','FE0F','1F9DE','200D','2640','FE0F','1F9DF','1F9DF','200D','2642','FE0F','1F9DF','200D','2640','FE0F','1F486','1F486','200D','2642','FE0F','1F486','200D','2640','FE0F','1F487','1F487','200D','2642','FE0F','1F487','200D','2640','FE0F','1F6B6','1F6B6','200D','2642','FE0F','1F6B6','200D','2640','FE0F','1F9CD','1F9CD','200D','2642','FE0F','1F9CD','200D','2640','FE0F','1F9CE','1F9CE','200D','2642','FE0F','1F9CE','200D','2640','FE0F','1F468','200D','1F9AF','1F469','200D','1F9AF','1F468','200D','1F9BC','1F469','200D','1F9BC','1F468','200D','1F9BD','1F469','200D','1F9BD','1F3C3','1F3C3','200D','2642','FE0F','1F3C3','200D','2640','FE0F','1F483','1F57A','1F574','1F46F','1F46F','200D','2642','FE0F','1F46F','200D','2640','FE0F','1F9D6','1F9D6','200D','2642','FE0F','1F9D6','200D','2640','FE0F','1F9D7','1F9D7','200D','2642','FE0F','1F9D7','200D','2640','FE0F','1F93A','1F3C7','26F7','1F3C2','1F3CC','1F3CC','FE0F','200D','2642','FE0F','1F3CC','FE0F','200D','2640','FE0F','1F3C4','1F3C4','200D','2642','FE0F','1F3C4','200D','2640','FE0F','1F6A3','1F6A3','200D','2642','FE0F','1F6A3','200D','2640','FE0F','1F3CA','1F3CA','200D','2642','FE0F','1F3CA','200D','2640','FE0F','26F9','26F9','FE0F','200D','2642','FE0F','26F9','FE0F','200D','2640','FE0F','1F3CB','1F3CB','FE0F','200D','2642','FE0F','1F3CB','FE0F','200D','2640','FE0F','1F6B4','1F6B4','200D','2642','FE0F','1F6B4','200D','2640','FE0F','1F6B5','1F6B5','200D','2642','FE0F','1F6B5','200D','2640','FE0F','1F938','1F938','200D','2642','FE0F','1F938','200D','2640','FE0F','1F93C','1F93C','200D','2642','FE0F','1F93C','200D','2640','FE0F','1F93D','1F93D','200D','2642','FE0F','1F93D','200D','2640','FE0F','1F93E','1F93E','200D','2642','FE0F','1F93E','200D','2640','FE0F','1F939','1F939','200D','2642','FE0F','1F939','200D','2640','FE0F','1F9D8','1F9D8','200D','2642','FE0F','1F9D8','200D','2640','FE0F','1F6C0','1F6CC','1F9D1','200D','1F91D','200D','1F9D1','1F46D','1F46B','1F46C','1F48F','1F469','200D','2764','FE0F','200D','1F48B','200D','1F468','1F468','200D','2764','FE0F','200D','1F48B','200D','1F468','1F469','200D','2764','FE0F','200D','1F48B','200D','1F469','1F491','1F469','200D','2764','FE0F','200D','1F468','1F468','200D','2764','FE0F','200D','1F468','1F469','200D','2764','FE0F','200D','1F469','1F46A','1F468','200D','1F469','200D','1F466','1F468','200D','1F469','200D','1F467','1F468','200D','1F469','200D','1F467','200D','1F466','1F468','200D','1F469','200D','1F466','200D','1F466','1F468','200D','1F469','200D','1F467','200D','1F467','1F468','200D','1F468','200D','1F466','1F468','200D','1F468','200D','1F467','1F468','200D','1F468','200D','1F467','200D','1F466','1F468','200D','1F468','200D','1F466','200D','1F466','1F468','200D','1F468','200D','1F467','200D','1F467','1F469','200D','1F469','200D','1F466','1F469','200D','1F469','200D','1F467','1F469','200D','1F469','200D','1F467','200D','1F466','1F469','200D','1F469','200D','1F466','200D','1F466','1F469','200D','1F469','200D','1F467','200D','1F467','1F468','200D','1F466','1F468','200D','1F466','200D','1F466','1F468','200D','1F467','1F468','200D','1F467','200D','1F466','1F468','200D','1F467','200D','1F467','1F469','200D','1F466','1F469','200D','1F466','200D','1F466','1F469','200D','1F467','1F469','200D','1F467','200D','1F466','1F469','200D','1F467','200D','1F467','1F5E3','1F464','1F465','1F463','1F9B0','1F9B1','1F9B3','1F9B2','1F435','1F412','1F98D','1F9A7','1F436','1F415','1F9AE','1F415','200D','1F9BA','1F429','1F43A','1F98A','1F99D','1F431','1F408','1F981','1F42F','1F405','1F406','1F434','1F40E','1F984','1F993','1F98C','1F42E','1F402','1F403','1F404','1F437','1F416','1F417','1F43D','1F40F','1F411','1F410','1F42A','1F42B','1F999','1F992','1F418','1F98F','1F99B','1F42D','1F401','1F400','1F439','1F430','1F407','1F43F','1F994','1F987','1F43B','1F428','1F43C','1F9A5','1F9A6','1F9A8','1F998','1F9A1','1F43E','1F983','1F414','1F413','1F423','1F424','1F425','1F426','1F427','1F54A','1F985','1F986','1F9A2','1F989','1F9A9','1F99A','1F99C','1F438','1F40A','1F422','1F98E','1F40D','1F432','1F409','1F995','1F996','1F433','1F40B','1F42C','1F41F','1F420','1F421','1F988','1F419','1F41A','1F40C','1F98B','1F41B','1F41C','1F41D','1F41E','1F997','1F577','1F578','1F982','1F99F','1F9A0','1F490','1F338','1F4AE','1F3F5','1F339','1F940','1F33A','1F33B','1F33C','1F337','1F331','1F332','1F333','1F334','1F335','1F33E','1F33F','2618','1F340','1F341','1F342','1F343','1F347','1F348','1F349','1F34A','1F34B','1F34C','1F34D','1F96D','1F34E','1F34F','1F350','1F351','1F352','1F353','1F95D','1F345','1F965','1F951','1F346','1F954','1F955','1F33D','1F336','1F952','1F96C','1F966','1F9C4','1F9C5','1F344','1F95C','1F330','1F35E','1F950','1F956','1F968','1F96F','1F95E','1F9C7','1F9C0','1F356','1F357','1F969','1F953','1F354','1F35F','1F355','1F32D','1F96A','1F32E','1F32F','1F959','1F9C6','1F95A','1F373','1F958','1F372','1F963','1F957','1F37F','1F9C8','1F9C2','1F96B','1F371','1F358','1F359','1F35A','1F35B','1F35C','1F35D','1F360','1F362','1F363','1F364','1F365','1F96E','1F361','1F95F','1F960','1F961','1F980','1F99E','1F990','1F991','1F9AA','1F366','1F367','1F368','1F369','1F36A','1F382','1F370','1F9C1','1F967','1F36B','1F36C','1F36D','1F36E','1F36F','1F37C','1F95B','2615','1F375','1F376','1F37E','1F377','1F378','1F379','1F37A','1F37B','1F942','1F943','1F964','1F9C3','1F9C9','1F9CA','1F962','1F37D','1F374','1F944','1F52A','1F3FA','1F30D','1F30E','1F30F','1F310','1F5FA','1F5FE','1F9ED','1F3D4','26F0','1F30B','1F5FB','1F3D5','1F3D6','1F3DC','1F3DD','1F3DE','1F3DF','1F3DB','1F3D7','1F9F1','1F3D8','1F3DA','1F3E0','1F3E1','1F3E2','1F3E3','1F3E4','1F3E5','1F3E6','1F3E8','1F3E9','1F3EA','1F3EB','1F3EC','1F3ED','1F3EF','1F3F0','1F492','1F5FC','1F5FD','26EA','1F54C','1F6D5','1F54D','26E9','1F54B','26F2','26FA','1F301','1F303','1F3D9','1F304','1F305','1F306','1F307','1F309','2668','1F3A0','1F3A1','1F3A2','1F488','1F3AA','1F682','1F683','1F684','1F685','1F686','1F687','1F688','1F689','1F68A','1F69D','1F69E','1F68B','1F68C','1F68D','1F68E','1F690','1F691','1F692','1F693','1F694','1F695','1F696','1F697','1F698','1F699','1F69A','1F69B','1F69C','1F3CE','1F3CD','1F6F5','1F9BD','1F9BC','1F6FA','1F6B2','1F6F4','1F6F9','1F68F','1F6E3','1F6E4','1F6E2','26FD','1F6A8','1F6A5','1F6A6','1F6D1','1F6A7','2693','26F5','1F6F6','1F6A4','1F6F3','26F4','1F6E5','1F6A2','2708','1F6E9','1F6EB','1F6EC','1FA82','1F4BA','1F681','1F69F','1F6A0','1F6A1','1F6F0','1F680','1F6F8','1F6CE','1F9F3','231B','23F3','231A','23F0','23F1','23F2','1F570','1F55B','1F567','1F550','1F55C','1F551','1F55D','1F552','1F55E','1F553','1F55F','1F554','1F560','1F555','1F561','1F556','1F562','1F557','1F563','1F558','1F564','1F559','1F565','1F55A','1F566','1F311','1F312','1F313','1F314','1F315','1F316','1F317','1F318','1F319','1F31A','1F31B','1F31C','1F321','2600','1F31D','1F31E','1FA90','2B50','1F31F','1F320','1F30C','2601','26C5','26C8','1F324','1F325','1F326','1F327','1F328','1F329','1F32A','1F32B','1F32C','1F300','1F308','1F302','2602','2614','26F1','26A1','2744','2603','26C4','2604','1F525','1F4A7','1F30A','1F383','1F384','1F386','1F387','1F9E8','2728','1F388','1F389','1F38A','1F38B','1F38D','1F38E','1F38F','1F390','1F391','1F9E7','1F380','1F381','1F397','1F39F','1F3AB','1F396','1F3C6','1F3C5','1F947','1F948','1F949','26BD','26BE','1F94E','1F3C0','1F3D0','1F3C8','1F3C9','1F3BE','1F94F','1F3B3','1F3CF','1F3D1','1F3D2','1F94D','1F3D3','1F3F8','1F94A','1F94B','1F945','26F3','26F8','1F3A3','1F93F','1F3BD','1F3BF','1F6F7','1F94C','1F3AF','1FA80','1FA81','1F3B1','1F52E','1F9FF','1F3AE','1F579','1F3B0','1F3B2','1F9E9','1F9F8','2660','2665','2666','2663','265F','1F0CF','1F004','1F3B4','1F3AD','1F5BC','1F3A8','1F9F5','1F9F6','1F453','1F576','1F97D','1F97C','1F9BA','1F454','1F455','1F456','1F9E3','1F9E4','1F9E5','1F9E6','1F457','1F458','1F97B','1FA71','1FA72','1FA73','1F459','1F45A','1F45B','1F45C','1F45D','1F6CD','1F392','1F45E','1F45F','1F97E','1F97F','1F460','1F461','1FA70','1F462','1F451','1F452','1F3A9','1F393','1F9E2','26D1','1F4FF','1F484','1F48D','1F48E','1F507','1F508','1F509','1F50A','1F4E2','1F4E3','1F4EF','1F514','1F515','1F3BC','1F3B5','1F3B6','1F399','1F39A','1F39B','1F3A4','1F3A7','1F4FB','1F3B7','1F3B8','1F3B9','1F3BA','1F3BB','1FA95','1F941','1F4F1','1F4F2','260E','1F4DE','1F4DF','1F4E0','1F50B','1F50C','1F4BB','1F5A5','1F5A8','2328','1F5B1','1F5B2','1F4BD','1F4BE','1F4BF','1F4C0','1F9EE','1F3A5','1F39E','1F4FD','1F3AC','1F4FA','1F4F7','1F4F8','1F4F9','1F4FC','1F50D','1F50E','1F56F','1F4A1','1F526','1F3EE','1FA94','1F4D4','1F4D5','1F4D6','1F4D7','1F4D8','1F4D9','1F4DA','1F4D3','1F4D2','1F4C3','1F4DC','1F4C4','1F4F0','1F5DE','1F4D1','1F516','1F3F7','1F4B0','1F4B4','1F4B5','1F4B6','1F4B7','1F4B8','1F4B3','1F9FE','1F4B9','1F4B1','1F4B2','2709','1F4E7','1F4E8','1F4E9','1F4E4','1F4E5','1F4E6','1F4EB','1F4EA','1F4EC','1F4ED','1F4EE','1F5F3','270F','2712','1F58B','1F58A','1F58C','1F58D','1F4DD','1F4BC','1F4C1','1F4C2','1F5C2','1F4C5','1F4C6','1F5D2','1F5D3','1F4C7','1F4C8','1F4C9','1F4CA','1F4CB','1F4CC','1F4CD','1F4CE','1F587','1F4CF','1F4D0','2702','1F5C3','1F5C4','1F5D1','1F512','1F513','1F50F','1F510','1F511','1F5DD','1F528','1FA93','26CF','2692','1F6E0','1F5E1','2694','1F52B','1F3F9','1F6E1','1F527','1F529','2699','1F5DC','2696','1F9AF','1F517','26D3','1F9F0','1F9F2','2697','1F9EA','1F9EB','1F9EC','1F52C','1F52D','1F4E1','1F489','1FA78','1F48A','1FA79','1FA7A','1F6AA','1F6CF','1F6CB','1FA91','1F6BD','1F6BF','1F6C1','1FA92','1F9F4','1F9F7','1F9F9','1F9FA','1F9FB','1F9FC','1F9FD','1F9EF','1F6D2','1F6AC','26B0','26B1','1F5FF','1F3E7','1F6AE','1F6B0','267F','1F6B9','1F6BA','1F6BB','1F6BC','1F6BE','1F6C2','1F6C3','1F6C4','1F6C5','26A0','1F6B8','26D4','1F6AB','1F6B3','1F6AD','1F6AF','1F6B1','1F6B7','1F4F5','1F51E','2622','2623','2B06','2197','27A1','2198','2B07','2199','2B05','2196','2195','2194','21A9','21AA','2934','2935','1F503','1F504','1F519','1F51A','1F51B','1F51C','1F51D','1F6D0','269B','1F549','2721','2638','262F','271D','2626','262A','262E','1F54E','1F52F','2648','2649','264A','264B','264C','264D','264E','264F','2650','2651','2652','2653','26CE','1F500','1F501','1F502','25B6','23E9','23ED','23EF','25C0','23EA','23EE','1F53C','23EB','1F53D','23EC','23F8','23F9','23FA','23CF','1F3A6','1F505','1F506','1F4F6','1F4F3','1F4F4','2640','2642','2695','267E','267B','269C','1F531','1F4DB','1F530','2B55','2705','2611','2714','2716','274C','274E','2795','2796','2797','27B0','27BF','303D','2733','2734','2747','203C','2049','2753','2754','2755','2757','3030','00A9','00AE','2122','0023','FE0F','20E3','002A','FE0F','20E3','0030','FE0F','20E3','0031','FE0F','20E3','0032','FE0F','20E3','0033','FE0F','20E3','0034','FE0F','20E3','0035','FE0F','20E3','0036','FE0F','20E3','0037','FE0F','20E3','0038','FE0F','20E3','0039','FE0F','20E3','1F51F','1F520','1F521','1F522','1F523','1F524','1F170','1F18E','1F171','1F191','1F192','1F193','2139','1F194','24C2','1F195','1F196','1F17E','1F197','1F17F','1F198','1F199','1F19A','1F201','1F202','1F237','1F236','1F22F','1F250','1F239','1F21A','1F232','1F251','1F238','1F234','1F233','3297','3299','1F23A','1F235','1F534','1F7E0','1F7E1','1F7E2','1F535','1F7E3','1F7E4','26AB','26AA','1F7E5','1F7E7','1F7E8','1F7E9','1F7E6','1F7EA','1F7EB','2B1B','2B1C','25FC','25FB','25FE','25FD','25AA','25AB','1F536','1F537','1F538','1F539','1F53A','1F53B','1F4A0','1F518','1F533','1F532','1F3C1','1F6A9','1F38C','1F3F4','1F3F3','1F3F3','FE0F','200D','1F308','1F3F4','200D','2620','FE0F','1F1E6','1F1E8','1F1E6','1F1E9','1F1E6','1F1EA','1F1E6','1F1EB','1F1E6','1F1EC','1F1E6','1F1EE','1F1E6','1F1F1','1F1E6','1F1F2','1F1E6','1F1F4','1F1E6','1F1F6','1F1E6','1F1F7','1F1E6','1F1F8','1F1E6','1F1F9','1F1E6','1F1FA','1F1E6','1F1FC','1F1E6','1F1FD','1F1E6','1F1FF','1F1E7','1F1E6','1F1E7','1F1E7','1F1E7','1F1E9','1F1E7','1F1EA','1F1E7','1F1EB','1F1E7','1F1EC','1F1E7','1F1ED','1F1E7','1F1EE','1F1E7','1F1EF','1F1E7','1F1F1','1F1E7','1F1F2','1F1E7','1F1F3','1F1E7','1F1F4','1F1E7','1F1F6','1F1E7','1F1F7','1F1E7','1F1F8','1F1E7','1F1F9','1F1E7','1F1FB','1F1E7','1F1FC','1F1E7','1F1FE','1F1E7','1F1FF','1F1E8','1F1E6','1F1E8','1F1E8','1F1E8','1F1E9','1F1E8','1F1EB','1F1E8','1F1EC','1F1E8','1F1ED','1F1E8','1F1EE','1F1E8','1F1F0','1F1E8','1F1F1','1F1E8','1F1F2','1F1E8','1F1F3','1F1E8','1F1F4','1F1E8','1F1F5','1F1E8','1F1F7','1F1E8','1F1FA','1F1E8','1F1FB','1F1E8','1F1FC','1F1E8','1F1FD','1F1E8','1F1FE','1F1E8','1F1FF','1F1E9','1F1EA','1F1E9','1F1EC','1F1E9','1F1EF','1F1E9','1F1F0','1F1E9','1F1F2','1F1E9','1F1F4','1F1E9','1F1FF','1F1EA','1F1E6','1F1EA','1F1E8','1F1EA','1F1EA','1F1EA','1F1EC','1F1EA','1F1ED','1F1EA','1F1F7','1F1EA','1F1F8','1F1EA','1F1F9','1F1EA','1F1FA','1F1EB','1F1EE','1F1EB','1F1EF','1F1EB','1F1F0','1F1EB','1F1F2','1F1EB','1F1F4','1F1EB','1F1F7','1F1EC','1F1E6','1F1EC','1F1E7','1F1EC','1F1E9','1F1EC','1F1EA','1F1EC','1F1EB','1F1EC','1F1EC','1F1EC','1F1ED','1F1EC','1F1EE','1F1EC','1F1F1','1F1EC','1F1F2','1F1EC','1F1F3','1F1EC','1F1F5','1F1EC','1F1F6','1F1EC','1F1F7','1F1EC','1F1F8','1F1EC','1F1F9','1F1EC','1F1FA','1F1EC','1F1FC','1F1EC','1F1FE','1F1ED','1F1F0','1F1ED','1F1F2','1F1ED','1F1F3','1F1ED','1F1F7','1F1ED','1F1F9','1F1ED','1F1FA','1F1EE','1F1E8','1F1EE','1F1E9','1F1EE','1F1EA','1F1EE','1F1F1','1F1EE','1F1F2','1F1EE','1F1F3','1F1EE','1F1F4','1F1EE','1F1F6','1F1EE','1F1F7','1F1EE','1F1F8','1F1EE','1F1F9','1F1EF','1F1EA','1F1EF','1F1F2','1F1EF','1F1F4','1F1EF','1F1F5','1F1F0','1F1EA','1F1F0','1F1EC','1F1F0','1F1ED','1F1F0','1F1EE','1F1F0','1F1F2','1F1F0','1F1F3','1F1F0','1F1F5','1F1F0','1F1F7','1F1F0','1F1FC','1F1F0','1F1FE','1F1F0','1F1FF','1F1F1','1F1E6','1F1F1','1F1E7','1F1F1','1F1E8','1F1F1','1F1EE','1F1F1','1F1F0','1F1F1','1F1F7','1F1F1','1F1F8','1F1F1','1F1F9','1F1F1','1F1FA','1F1F1','1F1FB','1F1F1','1F1FE','1F1F2','1F1E6','1F1F2','1F1E8','1F1F2','1F1E9','1F1F2','1F1EA','1F1F2','1F1EB','1F1F2','1F1EC','1F1F2','1F1ED','1F1F2','1F1F0','1F1F2','1F1F1','1F1F2','1F1F2','1F1F2','1F1F3','1F1F2','1F1F4','1F1F2','1F1F5','1F1F2','1F1F6','1F1F2','1F1F7','1F1F2','1F1F8','1F1F2','1F1F9','1F1F2','1F1FA','1F1F2','1F1FB','1F1F2','1F1FC','1F1F2','1F1FD','1F1F2','1F1FE','1F1F2','1F1FF','1F1F3','1F1E6','1F1F3','1F1E8','1F1F3','1F1EA','1F1F3','1F1EB','1F1F3','1F1EC','1F1F3','1F1EE','1F1F3','1F1F1','1F1F3','1F1F4','1F1F3','1F1F5','1F1F3','1F1F7','1F1F3','1F1FA','1F1F3','1F1FF','1F1F4','1F1F2','1F1F5','1F1E6','1F1F5','1F1EA','1F1F5','1F1EB','1F1F5','1F1EC','1F1F5','1F1ED','1F1F5','1F1F0','1F1F5','1F1F1','1F1F5','1F1F2','1F1F5','1F1F3','1F1F5','1F1F7','1F1F5','1F1F8','1F1F5','1F1F9','1F1F5','1F1FC','1F1F5','1F1FE','1F1F6','1F1E6','1F1F7','1F1EA','1F1F7','1F1F4','1F1F7','1F1F8','1F1F7','1F1FA','1F1F7','1F1FC','1F1F8','1F1E6','1F1F8','1F1E7','1F1F8','1F1E8','1F1F8','1F1E9','1F1F8','1F1EA','1F1F8','1F1EC','1F1F8','1F1ED','1F1F8','1F1EE','1F1F8','1F1EF','1F1F8','1F1F0','1F1F8','1F1F1','1F1F8','1F1F2','1F1F8','1F1F3','1F1F8','1F1F4','1F1F8','1F1F7','1F1F8','1F1F8','1F1F8','1F1F9','1F1F8','1F1FB','1F1F8','1F1FD','1F1F8','1F1FE','1F1F8','1F1FF','1F1F9','1F1E6','1F1F9','1F1E8','1F1F9','1F1E9','1F1F9','1F1EB','1F1F9','1F1EC','1F1F9','1F1ED','1F1F9','1F1EF','1F1F9','1F1F0','1F1F9','1F1F1','1F1F9','1F1F2','1F1F9','1F1F3','1F1F9','1F1F4','1F1F9','1F1F7','1F1F9','1F1F9','1F1F9','1F1FB','1F1F9','1F1FC','1F1F9','1F1FF','1F1FA','1F1E6','1F1FA','1F1EC','1F1FA','1F1F2','1F1FA','1F1F3','1F1FA','1F1F8','1F1FA','1F1FE','1F1FA','1F1FF','1F1FB','1F1E6','1F1FB','1F1E8','1F1FB','1F1EA','1F1FB','1F1EC','1F1FB','1F1EE','1F1FB','1F1F3','1F1FB','1F1FA','1F1FC','1F1EB','1F1FC','1F1F8','1F1FD','1F1F0','1F1FE','1F1EA','1F1FE','1F1F9','1F1FF','1F1E6','1F1FF','1F1F2','1F1FF','1F1FC','1F3F4','E0067','E0062','E0065','E006E','E0067','E007F','1F3F4','E0067','E0062','E0073','E0063','E0074','E007F','1F3F4','E0067','E0062','E0077','E006C','E0073','E007F' );

        preg_match(  '/[\x{' . implode( '}\x{', $unicodes ) . '}]/u', self::$Dt->text, $matches_emo );

        if(!count($matches_emo)){
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => 'هیچ اموجی یافت نشد!  [/setemojy em user_id]',
                'parse_mode' => 'HTML'
            ]);
        }

        $Emojy = $matches_emo[0];
        $username = false;
        $fullname = false;
        $user_id = false;
        preg_match("/([0-9]+)/",  self::$Dt->text, $matches);
        if(count($matches)){
            $user_id = (float) $matches[0];
        }

        preg_match('/\B@(\w+)/',  self::$Dt->text, $matches_username);
        if(count($matches_username)){
            $username = $matches_username[0];
        }

        if(!$username && !$user_id){
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => 'لطفا نام کاربری و یا شناسه کاربری را در متن قرار دهید نام کاربری با @ شروع میشود',
                'parse_mode' => 'HTML'
            ]);
        }

        if($username){
            $FindUser = GR::CheckPlayerByUsername($username);
            if(!$FindUser){
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => 'کاربری با نام کاربری '.$username.' یافت نشد !',
                    'parse_mode' => 'HTML'
                ]);
            }
        }

        if($user_id){
            $FindUser = GR::CheckUserById($user_id);
            if(!$FindUser){
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => 'کاربری با شناسه کاربری '.$user_id.' یافت نشد !',
                    'parse_mode' => 'HTML'
                ]);
            }
        }

        $fullname =  $FindUser['fullname'];
        $UserId = $FindUser['user_id'];
        $coin = (float) $FindUser['coin'];
        $NeedCoin = EMOJI_COIN;
        if($coin < $NeedCoin){
           return Request::sendMessage([
                'chat_id' => $UserId,
                'text' => '❌ عدم موجودی ، موجودی کاربر: '.$coin,
                'parse_mode' => 'HTML'
            ]);
        }
        GR::UpdateCoin($coin - $NeedCoin,$UserId);
        GR::ChangeEmoji($Emojy,$UserId);
        Request::sendMessage([
            'chat_id' => $UserId,
            'text' => 'اموجی شما به '.$Emojy.PHP_EOL."تغییر کرد .",
            'parse_mode' => 'HTML'
        ]);
        return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => 'اموجی کاربر '.$fullname.PHP_EOL."با موفقییت ثبت شد .",
            'parse_mode' => 'HTML'
        ]);

    }

    public static function CM_RemoveGif(){
        if(!in_array(self::$Dt->user_id,self::$Dt->Bot_admins)){
            return false;
        }


        $username = false;
        $fullname = false;
        $user_id = false;
        preg_match("/([0-9]+)/",  self::$Dt->text, $matches);
        if(count($matches)){
            $user_id = (float) $matches[0];
        }

        preg_match('/\B@(\w+)/',  self::$Dt->text, $matches_username);
        if(count($matches_username)){
            $username = $matches_username[0];
        }

        if(!$username && !$user_id){
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => 'لطفا نام کاربری و یا شناسه کاربری را در متن قرار دهید نام کاربری با @ شروع میشود',
                'parse_mode' => 'HTML'
            ]);
        }

        if($username){
            $FindUser = GR::CheckPlayerByUsername($username);
            if(!$FindUser){
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => 'کاربری با نام کاربری '.$username.' یافت نشد !',
                    'parse_mode' => 'HTML'
                ]);
            }
        }

        if($user_id){
            $FindUser = GR::CheckUserById($user_id);
            if(!$FindUser){
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => 'کاربری با شناسه کاربری '.$user_id.' یافت نشد !',
                    'parse_mode' => 'HTML'
                ]);
            }
        }

        $fullname =  $FindUser['fullname'];
        $UserId = $FindUser['user_id'];
        GR::ChangeEmoji('',$UserId);

        Request::sendMessage([
            'chat_id' => $UserId,
            'text' => 'اموجی شما توسط مدیر حذف شد ',
            'parse_mode' => 'HTML'
        ]);
        return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => 'اموجی کاربر '.$fullname.PHP_EOL."با موفقییت حذف شد .",
            'parse_mode' => 'HTML'
        ]);



    }
    public static function CM_ChangeCoin(){


        if(!in_array(self::$Dt->user_id,self::$Dt->Bot_admins)){
            return false;
        }
        if(self::$Dt->ReplayTo) {
            $UserId = self::$Dt->ReplayTo;
        }

        if(isset(self::$Dt->message->getEntities()[1])){
            if(self::$Dt->message->getEntities()[1]->getUser()) {
                $UserId = self::$Dt->message->getEntities()[1]->getUser()->getId();
            }
        }

        $Text = self::$Dt->text;
        $Explode = explode(' ',$Text);
        if(isset($Text) && count($Explode) > 1) {
            if(is_numeric($Explode[0]) and strlen($Explode[0]) > 7) {
                $UserId = (float) trim(self::$Dt->text);
            }elseif(preg_match("/^(?:[a-zA-Z0-9?. ]?)+@([a-zA-Z0-9]+)(.+)?$/",$Text,$matches)){
                $username = $matches[0];
            }

            if(isset($username)){
                $check = GR::CheckUserByUsername($username);
                if(!$check){
                    return  Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('NotFindeSmiteUserName',array("{0}" => $username)),
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML'
                    ]);
                }
                $UserId = $check['user_id'];
            }



            }

        $GetPlayer = false;
        if(isset($UserId)) {
            $GetPlayer = GR::CheckUserById($UserId);
        }
        if(!$GetPlayer){
            return Request::sendMessage([
                'chat_id' => 556635252,
                'text' => self::$Dt->L->_('NoStateDA'),
                'parse_mode' => 'HTML'
            ]);
        }
        $Name = GR::ConvertName($GetPlayer['user_id'],$GetPlayer['fullname']);
        if(self::$Dt->text == ""){
            return false;
        }
        $Text = self::$Dt->text;
        $CountEX = count(explode(" ",$Text));

        $Explode = explode(' ',$Text);
        $lastCoin = (isset($GetPlayer['coin']) ? (int) $GetPlayer['coin'] : 0);
        $CheckF = ($CountEX <= 1 ? substr($Explode[0],0,1) : substr($Explode[1],0,1) ) ;
        $Fainals = str_replace(['-','+'],'', ($CountEX <= 1 ?  $Explode[0] : $Explode[1]) );
        $FainalCoin =  ($CheckF === '+' ? $lastCoin +$Fainals : $lastCoin - $Fainals);
        GR::UpdateCoin($FainalCoin,$GetPlayer['user_id']);
        Request::sendMessage([
            'chat_id' => 556635252,
            'text' => self::$Dt->L->_('ChangeCoinPlayer',array("{0}" => $Fainals,"{1}" => $Name, "{2}" => ($CheckF === '-' ? self::$Dt->L->_('Min') : self::$Dt->L->_('Plus')) ,"{3}" => number_format($FainalCoin))),
            'parse_mode' => 'HTML'
        ]);

        return  Request::sendMessage([
            'chat_id' => $GetPlayer['user_id'],
            'text' => self::$Dt->L->_('MessagePlayer',array("{0}" => $Fainals, "{1}" => ($CheckF === '-' ? self::$Dt->L->_('Min') : self::$Dt->L->_('Plus')),'{2}' =>  number_format($FainalCoin) )),
            'parse_mode' => 'HTML'
        ]);

    }


    public static function CM_ChangeBlood(){
        if(!in_array(self::$Dt->user_id,self::$Dt->Bot_admins)){
            return false;
        }
        if(self::$Dt->ReplayTo) {
            $UserId = self::$Dt->ReplayTo;
        }

        if(isset(self::$Dt->message->getEntities()[1])){
            if(self::$Dt->message->getEntities()[1]->getUser()) {
                $UserId = self::$Dt->message->getEntities()[1]->getUser()->getId();
            }
        }

        $Text = self::$Dt->text;
        $Explode = explode(' ',$Text);
        if(isset($Text) && count($Explode) > 1) {
            if(is_numeric($Explode[0]) and strlen($Explode[0]) > 7) {
                $UserId = (float) trim(self::$Dt->text);
            }elseif(preg_match("/^(?:[a-zA-Z0-9?. ]?)+@([a-zA-Z0-9]+)(.+)?$/",$Text,$matches)){
                $username = $matches[0];
            }

            if(isset($username)){
                $check = GR::CheckUserByUsername($username);
                if(!$check){
                    return  Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('NotFindeSmiteUserName',array("{0}" => $username)),
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML'
                    ]);
                }
                $UserId = $check['user_id'];
            }



        }


        $GetPlayer = GR::CheckUserById($UserId);

        if(!$GetPlayer){
            return Request::sendMessage([
                'chat_id' => 556635252,
                'text' => self::$Dt->L->_('NoStateDA'),
                'parse_mode' => 'HTML'
            ]);
        }

        $Name = GR::ConvertName($GetPlayer['user_id'],$GetPlayer['fullname']);
        if(self::$Dt->text == ""){
            return false;
        }
        $Text = self::$Dt->text;
        $CountEX = count(explode(" ",$Text));

        $GETDA = GR::CheckDaPlayer($GetPlayer['user_id']);
        if(!$GETDA) return false;
        $Explode = explode(' ',$Text);
        $lastCoin = (isset($GETDA['Duelslost']) ? (int) $GETDA['Duelslost'] : 0);
        $CheckF = ($CountEX <= 1 ? substr($Explode[0],0,1) : substr($Explode[1],0,1) ) ;
        $Fainals = str_replace(['-','+'],'', ($CountEX <= 1 ?  $Explode[0] : $Explode[1]) );
        $FainalCoin =  ($CheckF === '+' ? $lastCoin +$Fainals : $lastCoin - $Fainals);
        GR::UpdateBlood($FainalCoin,$GetPlayer['user_id']);
        Request::sendMessage([
            'chat_id' => 556635252,
            'text' => self::$Dt->L->_('ChangeBloodPlayer',array("{0}" => $Fainals,"{1}" => $Name, "{2}" => ($CheckF === '-' ? self::$Dt->L->_('Min') : self::$Dt->L->_('Plus')) ,"{3}" => number_format($FainalCoin))),
            'parse_mode' => 'HTML'
        ]);

        return  Request::sendMessage([
            'chat_id' => $GetPlayer['user_id'],
            'text' => self::$Dt->L->_('MessageBloodPlayer',array("{0}" => $Fainals, "{1}" => ($CheckF === '-' ? self::$Dt->L->_('Min') : self::$Dt->L->_('Plus')),'{2}' =>  number_format($FainalCoin) )),
            'parse_mode' => 'HTML'
        ]);

    }


    public static function CM_ChangeState(){
        if(!in_array(self::$Dt->user_id,self::$Dt->Bot_admins)){
            return false;
        }

        $Text = (self::$Dt->text ? self::$Dt->text : false );

        if(!$Text){
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' =>  "لطفا ای دی حساب کاربری قبلی و جدید را وارد کنید.",
                'parse_mode' => 'HTML',
            ]);
        }

        $ExplodeText = explode(' ',$Text);

        if(!isset($ExplodeText[0])) return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "لطفا ای دی حساب کاربری قبلی را وارد کنید.",
            'parse_mode' => 'HTML',
        ]);
        if(!isset($ExplodeText[1])) return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "لطفا ای دی حساب کاربری جدید را وارد کنید.",
            'parse_mode' => 'HTML',
        ]);

        $LastUserID = (float)  $ExplodeText[0];
        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "1️⃣ در حال یافتن حساب کاربری قبلی ...",
            'parse_mode' => 'HTML',
        ]);

        $CheckLastID = GR::CheckUserById($LastUserID);
        if(!$CheckLastID) return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "حساب کاربری قبلی یافت نشد.",
            'parse_mode' => 'HTML',
        ]);

        $NewUserId = (float)  $ExplodeText[1];

        Request::sendMessage([
            'chat_id' => $NewUserId,
            'text' =>  "حساب کاربری شما در حال انتقال از اکانت قبلی به جدید میباشد پایان عملیات پیام انتقال را دریافت خواهید کرد.",
            'parse_mode' => 'HTML',
        ]);

        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "2️⃣ در حال یافتن حساب کاربری جدید ...",
            'parse_mode' => 'HTML',
        ]);

        $CheckNewUserID = GR::CheckUserById($NewUserId);
        if(!$CheckNewUserID) return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "حساب کاربری جدید یافت نشد.",
            'parse_mode' => 'HTML',
        ]);

        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "3️⃣ درحال انقال استیت قبلی به جدید...",
            'parse_mode' => 'HTML',
        ]);

        self::$Dt->collection->Players->updateOne(
            ['user_id' => $NewUserId],
            ['$set' => [
                'total_game' => ((float) $CheckNewUserID['total_game'] + (float)  $CheckLastID['total_game']),
                'SurviveTheGame' => ((float) $CheckNewUserID['SurviveTheGame'] + (float)  $CheckLastID['SurviveTheGame']),
                'SlaveGames' => ((float) $CheckNewUserID['SlaveGames'] + (float)  $CheckLastID['SlaveGames']),
                'LoserGames' => ((float) $CheckNewUserID['LoserGames'] + (float)  $CheckLastID['LoserGames']),
                'credit' => ((float) $CheckNewUserID['credit'] + (float)  $CheckLastID['credit']),
                'coin' => ((float) $CheckNewUserID['coin'] + (float)  $CheckLastID['coin']),
                'top' => ((float) $CheckNewUserID['top'] + (float)  $CheckLastID['top']),
                'Site_Password' => ((float) $CheckNewUserID['Site_Password'] + (float)  $CheckLastID['Site_Password']),
                'Site_Username' => ((float) $CheckNewUserID['Site_Username'] + (float)  $CheckLastID['Site_Username']),
            ]]
        );

        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "✅ استیت با موفقیت انتقال یافت.",
            'parse_mode' => 'HTML',
        ]);

        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "4️⃣ در حال انتقال کیل ها ...",
            'parse_mode' => 'HTML',
        ]);

        self::$Dt->collection->game_activity->updateMany(
            ['player_id' => $LastUserID],
            ['$set' => [
                'player_id' => $NewUserId,
            ]]
        );


        self::$Dt->collection->game_activity->updateMany(
            ['to' => $LastUserID],
            ['$set' => [
                'to' => $NewUserId,
            ]]
        );

        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "✅ کیل ها با موفقیت منقل شد.",
            'parse_mode' => 'HTML',
        ]);

        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "✅ اطلاعات حساب کاربری با موفقیت منتقل شد.",
            'parse_mode' => 'HTML',
        ]);


        return Request::sendMessage([
            'chat_id' => $NewUserId,
            'text' =>  "حساب کاربری قبلی شما به حساب کاربری جدید شما انتقال یافت ",
            'parse_mode' => 'HTML',
        ]);

    }

    public static function BuyRole(){
        if((int) self::$Dt->user_id !== 556635252  && (int) self::$Dt->user_id !==  630127836 ){
            return false;
        }
        $UserId = false;
        if(self::$Dt->ReplayTo) {
            $UserId = self::$Dt->ReplayTo;
        }

        if(isset(self::$Dt->message->getEntities()[1])){
            if(self::$Dt->message->getEntities()[1]->getUser()) {
                $UserId = self::$Dt->message->getEntities()[1]->getUser()->getId();
            }
        }

        $Text = self::$Dt->text;
        $Explode = explode(' ',$Text);
        if(isset($Text) && count($Explode) > 0) {
            if(is_numeric($Explode[0]) and strlen($Explode[0]) > 7) {
                $UserId = (float) trim(self::$Dt->text);
            }elseif(preg_match("/^(?:[a-zA-Z0-9?. ]?)+@([a-zA-Z0-9]+)(.+)?$/",$Text,$matches)){
                $username = $matches[0];
            }

            if(isset($username)){
                $check = GR::CheckUserByUsername($username);
                if(!$check){
                    return  Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('NotFindeSmiteUserName',array("{0}" => $username)),
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML'
                    ]);
                }
                $UserId = $check['user_id'];
            }



        }

        if(!$UserId){
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => 'بر روی کاربر مورد نظر ریپلای نمایید و یا ایدی عددی آنرا وارد کنید!',
                'parse_mode' => 'HTML'
            ]);
        }

        $GetPlayer = GR::CheckUserById($UserId);

        if(!$GetPlayer){
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => 'کاربر یافت نشد!',
                'parse_mode' => 'HTML'
            ]);
        }

        $lastCheck  = GR::checkLastByRole($GetPlayer['user_id'],'role_dozd');
       if($lastCheck){
           return Request::sendMessage([
               'chat_id' => self::$Dt->user_id,
               'text' => 'این کاربر از قبل این نقش را خریداری کرده است!',
               'parse_mode' => 'HTML'
           ]);
       }
       $insert = GR::ByRole((float) $GetPlayer['user_id'],'role_dozd');
         Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => 'با موفقیت نقش برای کاربر '.$GetPlayer['fullname']."(".$GetPlayer['user_id'].")".' خریداری شد!',
            'parse_mode' => 'HTML'
        ]);
        return Request::sendMessage([
            'chat_id' => $GetPlayer['user_id'],
            'text' => 'نقش دزد  😈 برای شما فعال شد! با ارسال دستور /mysetting میتوانید آنرا مدیریت نمایید!',
            'parse_mode' => 'HTML'
        ]);

    }



    public static function CM_MySetting(){
        $BuyPlayer =  GR::GetRoleBuy(self::$Dt->user_id);
        if(!$BuyPlayer){
            return  Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('NotBuyRole'),
                'parse_mode' => 'HTML',
            ]);
        }

        $re = [];
        foreach ($BuyPlayer as $row){
            $Active =  ($row['active'] ? "✅" : "⛔️");
            $re[] = [
                ['text' => "نقش :   ".self::$Dt->LG->_($row['role']."_n")."     ".$Active, 'callback_data' => "SGFDRol|" . $row['role']]
            ];
        }


        $inline_keyboard = new InlineKeyboard(...$re);

        return  Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('MyRoleSetting'),
            'parse_mode' => 'HTML',
            'reply_markup' => $inline_keyboard,
        ]);
    }


    public static function ChangeRoleSetting($Ex){
        $role = $Ex[1];
        GR::UpdateSettingRole($role);
        $BuyPlayer =  GR::GetRoleBuy(self::$Dt->user_id);
        if(!$BuyPlayer){
            return  Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('NotBuyRole'),
                'parse_mode' => 'HTML',
            ]);
        }

        $re = [];
        foreach ($BuyPlayer as $row){
            $Active =  ($row['active'] ? "✅" : "⛔️");
            $re[] = [
                ['text' => "نقش :   ".self::$Dt->LG->_($row['role']."_n")."     ".$Active, 'callback_data' => "SGFDRol|" . $row['role']]
            ];
        }


        $inline_keyboard = new InlineKeyboard(...$re);
        Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('MyRoleSetting'),
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => $inline_keyboard,
        ]);


    }


    public static function  CM_KillList($type = 'day',$send = true){

        $Nop = RC::Noperfix();



        if($type == 'day') {
            $date = date('Y-m-d');
            $end = date('Y-m-d',strtotime( '+1 '.$type));
        }else{
            $date = date('Y-m-d',strtotime( ' -1 '.$type));
            $end = date('Y-m-d');
        }


        if(!$Nop->exists('GetDataKills2:'.$end.":".$date)) {
            $Lists = GR::getKillTopList($date, $end);
            $MSg = GR::ConvertListData($Lists);
            $EndText = self::$Dt->L->_('GetListKills', array("{0}" => self::$Dt->L->_($type), '{1}' => jdate('Y-m-d',strtotime($date)), '{2}' => jdate('Y-m-d',strtotime($end)), '{3}' => $MSg));
            $Nop->set('GetDataKills2:'.$end.":".$date,$EndText);
            if($type == 'day') {
                $Nop->expire('GetDataKills2:'.$end.":".$date,3600);
            }
        }else{
            $EndText = $Nop->get('GetDataKills2:'.$end.":".$date);

        }


        $reply_markup =  new InlineKeyboard(
            [
                ['text' => self::$Dt->L->_('TodayKillList')." ".($type == 'day' ? '✅' : ''), 'callback_data' => "getKilllist/day" ],
                ['text' => self::$Dt->L->_('WeekKillList')." ".($type == 'week' ? '✅' : ''), 'callback_data' => "getKilllist/week"],
                ['text' => self::$Dt->L->_('MonthList')." ".($type == 'month' ? '✅' : ''), 'callback_data' => "getKilllist/month" ],

            ]
        );

        if($send) {
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => $EndText,
                'parse_mode' => 'HTML',
                'reply_markup' => $reply_markup,
            ]);
        }

        $re = Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'text' => $EndText,
            'reply_markup' => $reply_markup,
            'parse_mode' => 'HTML'
        ]);
        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);


    }

}