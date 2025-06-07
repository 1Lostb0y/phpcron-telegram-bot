<?php
namespace phpcron\CronBot;


class R
{
    /**
     * Cron object
     *
     * @var \phpcron\CronBot\cron
     */
    private static $CN;

    private static $R;

    public static function initialize(cron $CN)
    {
        if (!($CN instanceof cron)) {
            throw new Exception\CronException('Invalid Redis Pointer!');
        }
        self::$CN = $CN;
        self::$R = self::$CN->redis;



    }
    public static function NoPerfix(){
      return self::$CN->redis;
    }

    public static function GetKey($key){
        return self::$CN->chat_id.":".$key;
    }


    public static function push($data,$key,$type = ""){
        if($type == "json"){
            $data = json_encode($data);
        }
        self::$R->rPush(self::GetKey($key), $data);
        return true;
    }

    public static function rpush($data,$key,$type = ""){
        if($type == "json"){
            $data = json_encode($data);
        }
        self::$R->rPush(self::GetKey($key), $data);
    }

    public static function Ex($time,$key){
        self::$R->expire(self::GetKey($key), $time);
    }
    public static function CheckExit($key){
        $check = self::$R->exists(self::GetKey($key));
        return $check;
    }
    public static function Get($key){
        if(empty($key)){
            return false;
        }
        return self::$R->get(self::GetKey($key));
    }
    public static function GetSet($val,$key,$type = ""){
        if(empty($key)){
            return false;
        }
        if($type == "json"){
            $val = json_encode($val);
        }
        return self::$R->getSet(self::GetKey($key),$val);
    }

    public static function Del($key){
        if(empty($key)){
            return false;
        }
        self::$R->del(self::GetKey($key));


        return true;
    }
    public static function LRem($rem,$as,$key){
        if(empty($key)){
            return false;
        }
        return self::$R->lrem(self::GetKey($key), $as,$rem);
    }

    public static function LRange($as,$to,$key){
        if(empty($key)){
            return false;
        }
        return self::$R->lrange(self::GetKey($key), $as,$to);
    }

    public static function Keys($key){
        if(empty($key)){
            return false;
        }
        return self::$R->keys(self::GetKey($key));
    }

    public static function DelKey($key){
        if(empty($key)){
            return false;
        }
        $data = self::$R->keys(self::GetKey($key));
        foreach ($data as $row){
            self::$R->del($row);
        }

        return true;
    }
    public static function Sleep($secend){
        self::$R->getSet('sleep',true);
        self::$R->expire('sleep', $secend);
    }

    public static function Sort($key,$sort){
        return self::$R->sort(self::GetKey($key), array('sort' => $sort));
    }
    public static function RandomGif($key){
        $ar = [
            'start_game' =>[
                '13Z6M89Ak65Gxy',
                'dW0rcEXzdqDkCIpbE6',
                '3ohzdQUe71SON8Ao7K',
                'YNk9HRcH9zJfi'
            ],
            'startchoas' =>
                ['lNG02dpc9E3lmlvb3S'],
            'win_rosta' => [
                'l0d34qhupTf2WGypip',
                'UX63DfSrhoE5olmy3N',
                'J5FX1ksUVLTXwrJq58',
                '3KC2jD2QcBOSc',
                '3EfgWHj0YIDrW'
            ],
            'win_ferqe' => [
                'mBdlYQjXaWrm7QG0hr',
                'dL5qLAlhMn3va',
                'Jh1OUT6Pw9j9K',
            ],
            'win_wolf' => [
                'iQd8LQFSTsN4Q',
                'Qz5fQFZArQuBADXvLI',
                '122teRA3vWUZ9u',
                '9ltCaW2Tkc2qI',
                'cQAATp9nWV0lO',
                'BBzSyIfPJUREc'
            ],
            'nothing' => [
                'nYogYgSmIJaIo',
            ],
            'win_lover' => [
                'ZbTHNleoxODNOqmdbt',
                'rkSu72ptAZseQ',
            ],
            'win_trap' => [
                'Z1LYiyIPhnG9O',
            ],
            'win_firefighter' => [
                'H1AUNLbQb52MOx7JLF',
                'TIA3X7L65HENllpNVP',
            ],
            'win_vampire' => [
                'kaehtC8QT8nqpqAJnm',
            ],
            'winner_monafeq' => [
                'jUbTTgmm89aUtwhhz9',
            ]


        ];
        if(isset($ar[$key])) {
            $data_re = $ar[$key];
            Shuffle($data_re);
            Shuffle($data_re);
            Shuffle($data_re);
            Shuffle($data_re);
            Shuffle($data_re);
            $gifs = "https://media.giphy.com/media/{$data_re['0']}/giphy.gif";
            return $gifs;
        }else{
            return 'https://media.giphy.com/media/XBtycwmXhn2uohy8LW/giphy.gif';
        }

    }

}
