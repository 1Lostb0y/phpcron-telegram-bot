<?php

namespace phpcron\CronBot;
use Longman\TelegramBot\Request;
use MongoDB\Client;
use RandomLib\Factory;
use SecurityLib\Strength;

class cron
{
    /**
     * Version
     *
     * @var string
     */
    protected $version = '0.1.1';

    /**
     * Group ID
     *
     * @var string
     */
    protected $group_id = '';

    /**
     * Game ID
     *
     * @var string
     */
    public $game_id = '';
    
    /**
     * User ID
     *
     * @var int
     */
    public $user_id;

    /**
     * MongoDB collection
     * 
     * @var \MongoDB\Database
     */
    public $collection;

    /**
     * Redis client
     * 
     * @var \Predis\Client
     */
    public $redis;

    /**
     * Game mode
     * 
     * @var string
     */
    public $game_mode;

    /**
     * Default language
     * 
     * @var string
     */
    public $def_lang;

    /**
     * Start time
     * 
     * @var int
     */
    public $StartAt;

    /**
     * Join link
     * 
     * @var string
     */
    public $JoinLink;

    /**
     * Random generator
     * 
     * @var \RandomLib\Generator
     */
    public $R;

    /**
     * Data array
     * 
     * @var array
     */
    public $data;

    /**
     * Game language
     * 
     * @var Lang
     */
    public $LG;

    /**
     * Main language
     * 
     * @var Lang
     */
    public $L;

    /**
     * Chat ID
     * 
     * @var float
     */
    public $chat_id;

    /**
     * Group name
     * 
     * @var string
     */
    public $group_name;

    /**
     * Database configuration
     * 
     * @var array
     */
    private static $dbConfig = [
        'redis' => [
            'scheme' => 'tcp',
            'host' => 'localhost',
            'port' => 6379,
            'database' => 6
        ],
        'mongodb' => [
            'uri' => 'mongodb://178.63.71.188:60458',
            'options' => [
                'username' => 'sharuser',
                'password' => 'OczEZlGEP5',
                'ssl' => false,
                'authSource' => 'admin'
            ],
            'db' => 'bold'
        ]
    ];

    /**
     * Constructor
     *
     * @param array $data Configuration data
     * @throws \InvalidArgumentException If data is not an array
     */
    public function __construct($data)
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Constructor parameter must be an array');
        }

        $this->initConnections();
        $this->setProperties($data);
        $this->initComponents();
    }

    /**
     * Initialize database connections
     */
    private function initConnections()
    {
        // Initialize Redis
        $this->redis = new \Predis\Client(self::$dbConfig['redis']);
        
        // Initialize MongoDB
        $this->collection = (new Client(
            self::$dbConfig['mongodb']['uri'], 
            self::$dbConfig['mongodb']['options']
        ))->{self::$dbConfig['mongodb']['db']};
    }

    /**
     * Set all properties from data
     * 
     * @param array $data Configuration data
     */
    private function setProperties(array $data)
    {
        // Set basic properties
        $this->chat_id = (float) $data['group_id'] ?? 0;
        $this->game_id = $data['game_id'] ?? 0;
        $this->user_id = $data['user_id'] ?? 0;
        $this->game_mode = $data['game_mode'] ?? "general";
        $this->def_lang = $data['def_lang'] ?? "fa";
        $this->StartAt = $data['StartAt'] ?? time();
        $this->JoinLink = JOIN_URL . $this->game_id;
        $this->data = $data;
        
        // Initialize random generator
        $factory = new Factory();
        $this->R = $factory->getGenerator(new Strength(Strength::MEDIUM));
        $this->data = $data;
        $this->LG = new Lang(FALSE);
        $this->LG->load($this->game_mode."_".$this->def_lang, FALSE);
        $this->L = new Lang(FALSE);
        $this->L->load("main_".$this->def_lang, FALSE);
        
        // Initialize languages
        $this->LG = new Lang(FALSE);
        $this->LG->load($this->game_mode . "_" . $this->def_lang, FALSE);
        
        $this->L = new Lang(FALSE);
        $this->L->load("main_" . $this->def_lang, FALSE);
    }

    /**
     * Initialize all components
     */
    private function initComponents()
    {
        // Initialize components in one batch
        $components = [R::class, HL::class, Handler::class, join::class, NG::class, VT::class, DY::class, CM::class];
        
        foreach ($components as $component) {
            $component::initialize($this);
        }
        
        // Set group name after R is initialized
        $this->group_name = R::Get('group_link') 
            ? '<a href="' . R::Get('group_link') . '">' . R::Get('group_name') . '</a>' 
            : R::Get('group_name');
    }

    /**
     * Handle game events
     */
    public function handler()
    {
        Handler::Handel();
    }
}




