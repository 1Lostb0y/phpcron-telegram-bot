<?php
namespace phpcron\CronBot;

/*
 *  LangQuery - PHP Language Class
 *  https://github.com/enesnr/langquery/
 */

class Lang {

    public $default = "main_fa";
    /* All language abbreviations list : http://msdn.microsoft.com/en-us/library/ms693062(v=vs.85).aspx      */
    public $list,$data,$current;
    public $language_folder = BASE_DIR."Strong/Game_Mode";
    public $get_parameter = "lang";
    public $cookie_name = "lang";
    public $cookie_expire = 31536000; //365*24*60*60
    public function __construct($auto=TRUE){

        if(!is_dir($this->language_folder)){die("LANGQUERY : No language folder /$this->language_folder/");}
        $this->list = str_replace(".ini","",array_slice(scandir($this->language_folder),2));
        if(count($this->list)==0){
            die("LANGQUERY : No language file in /$this->language_folder/ directory. Please add at least default language $this->default.ini");
        }
        if($auto){
            if($this->is_valid($_GET[$this->get_parameter])){
                $this->load($_GET[$this->get_parameter]);
            }else{
                if($this->is_valid($_COOKIE[$this->cookie_name])){
                    $this->load($_COOKIE[$this->cookie_name]);
                }elseif($this->is_valid($this->browser())){
                    $this->load($this->browser(),FALSE);
                }else{
                    $this->load($this->default,FALSE);
                }
            }
        }
    }
    public function _($key,$array = array()){
        $arguments=$array;


        preg_match("/(?P<operation>[>]?)(\[(?P<language>\w+)\])?(?P<key>\w+)/", $key, $matches);
        $text = $this->find($matches['key'], $arguments, $matches['language']);
        if($matches['operation']==">"){
            echo($text);
        }else{
            return $text;
        }
    }
    public function browser(){
        return substr($_SERVER["HTTP_ACCEPT_LANGUAGE"],0,2);
    }
    public function is_valid($language){
        return in_array($language,$this->list);
    }
    public function load($language,$save_cookie=TRUE){
        $this->ini_loader($language);
        $this->current = $language;
        if($save_cookie) setcookie($this->cookie_name, $language, time()+$this->cookie_expire,"/");
    }
    public function free($language=""){
        if($language==""){
            $this->data = array();
        }else{
            if(isset($this->data[$language])) unset($this->data[$language]);
        }
    }
    private function ini_loader($language){
        if(!isset($this->data[$language])){
            if($this->is_valid($language)){
                $this->data[$language] = parse_ini_file("$this->language_folder/$language.ini");
            }else{
                die("LANGQUERY : No such language file : $this->language_folder/$language.ini");
            }
        }
    }
    private function SaveLog($key){
        // setup notifier
        $API_KEY  = '854021534:AAFJpsCrHW8LDLfooZaI5_rcvlP3BHPd-nY'; // Replace 'XXXXXXXXXX' with your bot's API token
        $DEV_ID   = '-1001162150617'; // Replace 'XXXXXXXXXX' with your Telegram user ID (use /whoami command)

        // get incomming message
        $incoming = file_get_contents('php://input');

        // if message exist convert it into array
        $incoming = !empty($incoming) ? json_decode(file_get_contents('php://input'), true) : false ;

        // developer notification message text
        $message  = $key." [ErrorLang]";

        // developer notification message settings
        $fields_string = '';
        $url = 'https://api.telegram.org/bot'.$API_KEY.'/sendMessage';

        $fields = [
            'chat_id' => urlencode($DEV_ID),
            'parse_mode' => urlencode('HTML'),
            'text' => urlencode(''.$message)
        ];

        //url-ify the data for the POST
        foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        rtrim($fields_string, '&');

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, count($fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

        //execute post
        $result = curl_exec($ch);

        //close connection
        curl_close($ch);

        // Uncomment following line and change path to store errors log in custom file
        // file_put_contents( __DIR__ .'/custom_errors.log', ($result?'Notified: '.var_export($result, true).PHP_EOL:'Not notified: '.var_export($result, true).PHP_EOL).$message . PHP_EOL, FILE_APPEND );

        // Sending 200 response code
        header('X-PHP-Response-Code: 200', true, 200);

        exit();

    }
    private function find($key,$arguments="",$language=""){
        if($language==""){
            $language=$this->current;
        }else{
            $this->ini_loader($language);
        }
        if(!isset($this->data[$language][$key])) {
            $Str_replace = explode('_', $language);
            $Lang = $Str_replace['1'];
            $General = parse_ini_file("$this->language_folder/general_fa.ini");
        }

        if(isset($this->data[$language][$key])){

            $text = $this->data[$language][$key];

            if(is_array($arguments)) {
                foreach ($arguments as $key => $val) {

                    $text = str_replace($key, $val, $text);
                }
            }

            return $text;
        }

        $text = $General[$key];

        if(is_array($arguments)) {
            foreach ($arguments as $key => $val) {
                $text = str_replace($key, $val, $text);
            }
        }
        return $text;


    }
    public function js(){
        $jscode  = <<<EOT
/*! sprintf.js | Copyright (c) 2007-2013 Alexandru Marasteanu <hello at alexei dot ro> | 3 clause BSD license */(function(e){function r(e){return Object.prototype.toString.call(e).slice(8,-1).toLowerCase()}function i(e,t){for(var n=[];t>0;n[--t]=e);return n.join("")}var t=function(){return t.cache.hasOwnProperty(arguments[0])||(t.cache[arguments[0]]=t.parse(arguments[0])),t.format.call(null,t.cache[arguments[0]],arguments)};t.format=function(e,n){var s=1,o=e.length,u="",a,f=[],l,c,h,p,d,v;for(l=0;l<o;l++){u=r(e[l]);if(u==="string")f.push(e[l]);else if(u==="array"){h=e[l];if(h[2]){a=n[s];for(c=0;c<h[2].length;c++){if(!a.hasOwnProperty(h[2][c]))throw t('[sprintf] property "%s" does not exist',h[2][c]);a=a[h[2][c]]}}else h[1]?a=n[h[1]]:a=n[s++];if(/[^s]/.test(h[8])&&r(a)!="number")throw t("[sprintf] expecting number but found %s",r(a));switch(h[8]){case"b":a=a.toString(2);break;case"c":a=String.fromCharCode(a);break;case"d":a=parseInt(a,10);break;case"e":a=h[7]?a.toExponential(h[7]):a.toExponential();break;case"f":a=h[7]?parseFloat(a).toFixed(h[7]):parseFloat(a);break;case"o":a=a.toString(8);break;case"s":a=(a=String(a))&&h[7]?a.substring(0,h[7]):a;break;case"u":a>>>=0;break;case"x":a=a.toString(16);break;case"X":a=a.toString(16).toUpperCase()}a=/[def]/.test(h[8])&&h[3]&&a>=0?"+"+a:a,d=h[4]?h[4]=="0"?"0":h[4].charAt(1):" ",v=h[6]-String(a).length,p=h[6]?i(d,v):"",f.push(h[5]?a+p:p+a)}}return f.join("")},t.cache={},t.parse=function(e){var t=e,n=[],r=[],i=0;while(t){if((n=/^[^\x25]+/.exec(t))!==null)r.push(n[0]);else if((n=/^\x25{2}/.exec(t))!==null)r.push("%");else{if((n=/^\x25(?:([1-9]\d*)\$|\(([^\)]+)\))?(\+)?(0|'[^$])?(-)?(\d+)?(?:\.(\d+))?([b-fosuxX])/.exec(t))===null)throw"[sprintf] huh?";if(n[2]){i|=1;var s=[],o=n[2],u=[];if((u=/^([a-z_][a-z_\d]*)/i.exec(o))===null)throw"[sprintf] huh?";s.push(u[1]);while((o=o.substring(u[0].length))!=="")if((u=/^\.([a-z_][a-z_\d]*)/i.exec(o))!==null)s.push(u[1]);else{if((u=/^\[(\d+)\]/.exec(o))===null)throw"[sprintf] huh?";s.push(u[1])}n[2]=s}else i|=2;if(i===3)throw"[sprintf] mixing positional and named placeholders is not (yet) supported";r.push(n)}t=t.substring(n[0].length)}return r};var n=function(e,n,r){return r=n.slice(0),r.splice(0,0,e),t.apply(null,r)};e.sprintf=t,e.vsprintf=n})(typeof exports!="undefined"?exports:window);
EOT;
        $jscode .= '
			$L = function(){
				data = '.$this->json().'
				if(arguments.length==0)
					return data;
				if(arguments.length==1)
					return data[arguments[0]];
				if(arguments.length>1){
					var args = Array.prototype.slice.call(arguments);
					return vsprintf(data[args[0]],args.slice(1));
				}
			};
		';
        return $jscode;
    }
    public function json(){
        return json_encode($this->data[$this->current]);
    }
}
