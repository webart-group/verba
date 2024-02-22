<?php
namespace Verba;

use DBDriver\mysql\Driver;
use Exception;
use Verba\Data\Boolean;
use Verba\FileSystem\Local;
use Verba\Mod\User\Authorization\BearerTokenAuthenticator;
use Verba\Mod\User\Model\GuestUser;
use Verba\Mod\User\Model\User;
use Verba\ObjectType\DataVault;

/**
 * Системное ядро. Основная функция - инициализация движка, системных компонент,
 * формирование конфигурации - системных констант и путей, установка локали, установка соединения с БД.
 * Кроме этого, предоставление интерфейсов по иниациализации модулей.
 * Является контейнером для экземпляров классов модулей, DB-интерфейса, Хранителя Ключей (KeyKeeper),
 * профиля пользователя (U), страницы (Page).
 *
 * @package engine
 *
 * @subpackage core
 *
 */
class Hive extends Configurable
{
    /**
     * @var static
     */
    public static $self;

    /**
     * @var string Тип операционной системы хоста
     */
    static $platform;

    /**
     * @var bool Флаг режима отладки
     */
    static $debug = false;
    static $cacheEnable;
    /**
     * @var object содержит конфигурацию ядра для текущего проекта.
     */
    protected $config;
    public static $default_config = array();
    /**
     * @var object некоторые параметры текущего вызова скрипта.
     */
    public static $current = array('back_url' => false);

    /**
     * @var array массив распарсеных фрагментов URL-а из $_SERVER['REQUEST_URI']
     */
    public $url_fragments = array();

    /**
     * @var array список активированных в ходе работы скрипта модулей
     */
    public $activated_modules = array();

    /**
     * @var object доступные локали движка
     *     \Verba\Hive::$lc['exists'] array -
     *     \Verba\Hive::$lc['current'] string -
     *     \Verba\Hive::$lc['default'] string -
     */
    public static $lc = array('current' => false, 'default' => false, 'exists' => array());

    /**
     * @var object инициированные соединения с БД
     */
    public $connections;

    /**
     * @var integer счетчик запросов к БД
     */
    public $query_counter = 0;

    /**
     * @var object хранит ссылку на интерфейс для работы с файловой системой
     *
     * @see  \Verba\FileSystem\Local
     */
    public $FSLocal;

    /**
     * @var object ссылку на объект активной страницы
     *
     * @see Page
     */
    public $Page;

    /**
     * @var object общий интерфейс работы с SQL базой
     *
     * @see db
     * @see query
     */
    private $SQL;

    /**
     * @var\Verba\Mod\User\Model\User объект представления активного пользователя
     *
     * @see\Verba\Mod\User\Model\User
     */

    private $U = false;

    /**
     * @var object ccылка на экземпляр класса операций с ключами доступа.
     *
     * @see KeyKeeper
     */
    private $KK = false;

    /**
     * @var array содержит список установленных в систему модулей с информацие по инициализации каждого из них. При инициализации модуля, ссылка на экземпляр класса хранится в modules[id_модуля][self].
     */
    protected static $modules;
    protected static $__modules_autoload = [];

    private $data_vaults = array();

    private $ots = array('binds' => array(), 'items' => array());

    public static $reservedNames = ['string', 'float'];

    /**
     * Определяет и устанавливает значение типа операционной системы на основе наличия переменной окружения сервера $_SERVER['WINDIR'].
     * В зависимости от ее присутствия свойство $platform может принимать значения win|unix.
     * @return null
     */
    static function setPlatform()
    {
        if (isset($_SERVER['WINDIR']) || isset($_SERVER['windir'])) {
            self::$platform = 'win';
        } else {
            self::$platform = 'unix';
        }

        return self::$platform;
    }

    /**
     * Возвращает значение типа операционной системы.
     *
     * @return string win|unix
     */
    static function getPlatform()
    {
        return self::$platform;
    }

    /**
     * Возвращает значение типа операционной системы.
     *
     * @return string win|unix
     */
    static function getCRLF()
    {
        return self::getPlatform() === 'win' ? "\r\n" : "\n";
    }

    /**
     * Конструктор ядра. Зачитывает конфигурацию проекта из директории локального движка.
     * Формирует набор системных путей, системных констант. Происходит определение текущего языка и выставление соответствующей локали PHP.
     * Установка соединения с БД. Подключение файлов основных системных компонент и другое..
     *
     * @return void
     */
    function __construct($cfg)
    {
        self::$self = $this;

        self::setPlatform();

        define('SYS_PLATFORM', self::$platform);
        $this->initConfigurator(null, null, 'config');
        $this->config = self::$default_config;

        if (is_array($cfg)) {
            $this->applyConfigDirect($cfg);
        }

        $this->handleDebugConfig();
        $cfg = $this->gC();
        self::$cacheEnable = (bool)$cfg['cacheEnable'];

        // базовый хост
        define('SYS_PRIMARY_HOST', isset($cfg['primary_host']) ? $cfg['primary_host']
            : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null));
        // истина если текущий хост - промысловый
        define('SYS_IS_PRODUCTION', isset($cfg['is_production']) ? (bool)$cfg['is_production'] : true);

        // текущий хост
        define('SYS_THIS_HOST', $cfg['this_host']);
        define('SYS_REQUEST_PROTO', $GLOBALS['SCRIPT_URI_PARSED']['scheme'] ?? ($_SERVER['REQUEST_SCHEME'] ?? 'http'));

        define('SYS_VERSION', $cfg['version']);
        define('SYS_DATABASE', $cfg['db']['database']);
        define('SYS_LC_DEFAULT', $cfg['lang']['locale']['default']);

        // Системные пути
        define('APP_RESOURCES_DIR', SYS_ROOT.'/resources');
        define('APP_CODE', SYS_ROOT.'/'.$cfg['path']['engine']);
        define('SYS_PATH_MODULES', APP_CODE.'/Mod');
        define('APP_PUB_DIR', SYS_ROOT.'/'.$cfg['path']['pub']);


        define('SYS_TEMPLATES_DIR', APP_RESOURCES_DIR . '/templates');
        define('SYS_EXTERNALS_DIR', APP_PUB_DIR . '/externals');
        define('SYS_EXTERNALS_URL', '/externals');
        define('SYS_CONFIGS_DIR', APP_RESOURCES_DIR . '/configs');

        define('SYS_IMAGES_DIR', SYS_ROOT . '/' . $cfg['path']['images']);
        define('SYS_IMAGES_URL', '/' . $cfg['path']['images']);
        define('SYS_CSS_URL', '/' . $cfg['path']['css']);
        define('SYS_JS_DIR', SYS_ROOT . '/' . $cfg['path']['js']);
        define('SYS_JS_URL', '/' . $cfg['path']['js']);
        define('SYS_UPLOAD_DIR', APP_PUB_DIR . '/' . $cfg['path']['upload']); // Директория аплоада
        define('SYS_UPLOAD_URL', '/' . $cfg['path']['upload']); // Путь директории аплоада юзерфайлов относительно рута
        define('SYS_VAR_DIR', SYS_ROOT . '/' . $cfg['path']['var']);//
        define('SYS_VAR_URL', '/' . $cfg['path']['var']);

        define('SYS_PUB_VAR', APP_PUB_DIR.'/var');//
        define('SYS_PUB_VAR_URL', '/var');

        define('SYS_PUB_CACHE', SYS_PUB_VAR . '/_cache');
        define('SYS_PUB_CACHE_URL', SYS_PUB_VAR_URL . '/_cache');

        define('SYS_CACHE_DIR', SYS_VAR_DIR . $cfg['path']['cache']);

        define('FILEINFO_MAGIC', $cfg['fileinfo_magic']);
        define('USR_GUEST_GROUP_ID', 20);
        define('USR_GUEST_GROUP_CODE', 'guest');
        define('USR_AUTH_GROUP_ID', 21);
        define('USR_AUTH_GROUP_CODE', 'authorized');
        define('USR_ADMIN_GROUP_ID', 22);
        define('USR_ADMIN_GROUP_CODE', 'admin');
        define('SYS_USER_ID', 3);
        define('SYS_CRYPTKEY', $cfg['cryptKey']);

        //subdomain
        if (strlen(SYS_THIS_HOST) !== strlen(SYS_PRIMARY_HOST)) {
            $subdomain = substr(SYS_THIS_HOST, 0, -(strlen(SYS_PRIMARY_HOST) + 1));
            if (is_string($subdomain) && !empty($subdomain) && $subdomain != 'www') {
                $this->sC($subdomain, 'subdomain');
            }
        }
        define('SYS_SUBDOMAIN', $this->gC('subdomain'));

        //Ключ скрипта
        define('SYS_SCRIPT_KEY', $this->make_random_string(10, 10));
        $this->sC(SYS_SCRIPT_KEY, 'script_key');

        // MySQL соединение
        $this->DB = $this->DbConnect();

        if(isset($_SESSION['hive']['current']['back_url'])) {
            self::setBackURL($_SESSION['hive']['current']['back_url']);
        }

        //FS handler
        $this->FSLocal = new Local;
        //  \Verba\Lang class
        Lang::init(isset($_REQUEST['lc']) ? $_REQUEST['lc'] : false, $cfg['lang']);

        //memcache
//        if ($cfg['memcache'] == true) {
//            $this->memcache = memcache_connect('localhost', 11211);
//            if (!$this->memcache) {
//                throw new Exception('Unable to init memcache');
//            }
//        }
    }

    /**
     * Деструктор класса, основной задачей которого является
     * сохранение в сессию значения локали.
     */
    function __destruct()
    {
        $_SESSION['hive']['current']['back_url'] = self::getBackURL();

        // Save current locale
        if ($_SESSION['lang']['locale'] !== Lang::$lang) {
            $_SESSION['lang']['locale'] = Lang::$lang;
        }

        if(is_object($this->U)){
            $_SESSION['hive']['U'] = serialize($this->U->getAuthorized()
                ? $this->U->getId()
                : $this->U);
        }

        if ($this->gC('__clearCache')) {
            $this->sC(false, '__clearCache');
            $this->clearCache();
        }
    }

    public function saveLogs()
    {
        Loger::saveToDB();
    }

    function handleDebugConfig()
    {
        $dcfg = $this->gC('debug');
        self::$debug = !($dcfg == null);
    }

    /**
     * Загрузка из БД информации о доступных системе модулях с параметрами их инициализации.
     * Результат выборки сохраняется в свойство \Verba\Hive::$modules
     * @param int $mod_id id модуля.
     * @return boolean
     * @see Hive
     */
    protected function loadModulesList()
    {
        $query = 'SELECT `id`, `code`, `path`, `autoload`, `zavisitOt` FROM `' . SYS_DATABASE . '`.`_modules`';

        $resObj = $this->DB->query($query);
        if (!is_object($resObj) || $resObj->getNumRows() < 1) {
            return false;
        }
        $autoload = array();
        while ($row = $resObj->fetchRow()) {

            self::$modules[strtolower($row['code'])] = [
                'path' => $row['path'],
                'code' => $row['code'],
                'dependsOn' => is_string($row['zavisitOt']) && strlen($row['zavisitOt']) ? explode(',', $row['zavisitOt']) : null,
                'instance' => null
            ];

            if ($row['autoload']) {
                self::$__modules_autoload[] = $row['code'];
            }
        }
        return true;
    }

    /**
     * Инициализирует модуль
     *
     * @param string $code код модуля.
     * @return false|\Mod объект модуля либо ошибку
     */
    function getModule($code)
    {
        $code = strtolower($code);

        if (!array_key_exists($code, self::$modules)){
            return false;
        }

        if(self::$modules[$code]['instance'] === null){

            if (is_array(self::$modules[$code]['dependsOn'])) {
                foreach (self::$modules[$code]['dependsOn'] as $requiredModCode) {
                    self::getModule($requiredModCode);
                }
            }
            $modClass = '\\App\\Mod\\'.ucfirst(strtolower(self::$modules[$code]['code']));

            if(!class_exists($modClass)){
                $modClass = '\Verba\\Mod\\' . self::$modules[$code]['code'];
                if(!class_exists($modClass)) {
                    throw new Exception('Unknow mod - '.self::$modules[$code]['code']);
                }
            }

            self::$modules[$code]['instance'] = call_user_func([$modClass, 'getInstance']);
        }

        return self::$modules[$code]['instance'];
    }

    /**
     * Проверка наличия активированного модуля.
     * @param int $mod_id id модуля.
     * @return boolean.
     */
    static function isModuleActivated($code)
    {
        return isset(self::$modules[$code]['instance']) && is_object(self::$modules[$code]['instance']);
    }

    static function isModExists($code)
    {
        if (is_object($code) && $code instanceof Mod) {
            $code = $code->getCode();
        }else{
            $code = strtolower($code);
        }

        return array_key_exists($code, self::$modules);
    }

    /**
     * Инициализация системных классов - \Verba\Mod\User\Model\User, KeyKeeper.
     * Ссылки на созданные объекты хранятся в свойствах класса
     * @return void
     * @see \U
     * @see KeyKeeper
     */
    function prepare()
    {
        $this->loadOtList();
        $this->loadModulesList();

        $this->KK = new KeyKeeper();
        $this->initUser();

        $this->initAutoloadModules();

        $this->updateUserActivity();
    }

    function cliEnv()
    {
        if (!isset($_SERVER['SERVER_SOFTWARE'])
            || $_SERVER['SERVER_SOFTWARE'] != 'hive-cli-gateway') {
            return;
        }
        $U = $this->U();
        if (!$U->getAuthorized()) {
            $mUser = _mod('User');
            $newU = $mUser->authorizeAsSystem();
        }
    }

    function initUser()
    {
        $authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

        $U = null;

        if($authorizationHeader){
            $bt = new BearerTokenAuthenticator($authorizationHeader);
            $U = $bt->authorize();

            $userAuthToken = $bt->getUserAuthToken();
        }

        if(isset($userAuthToken) && !empty($userAuthToken->session_id)
            && session_id() !== $userAuthToken->session_id) {
            session_abort();
            session_id($userAuthToken->session_id);
            session_start();
        }

        $this->setUser($U);

        return $this->U;

//        if (isset($_SESSION['hive']['U'])) {
//            $U = unserialize($_SESSION['hive']['U']);
//            if (is_object($U) && $U instanceof User) {
//                // если в сессии сохранен авторизированный юзер, получаем его ID и перегружаем
//                if ($U->getAuthorized() || $U->requireRefresh()) {
//                    $U = $U->getID();
//                }
//            } elseif (!is_int($U)) {
//                $U = null;
//            }
//        } else {
//            $U = new User();
//        }
    }

    function initAutoloadModules(){

        if (!is_array(self::$__modules_autoload) || !count(self::$__modules_autoload)) {
            return null;
        }
        foreach (self::$__modules_autoload as $code){
            self::getModule($code);
        }
    }

    function destroyUser()
    {
        $this->U = new GuestUser();
    }

    /**
     * @param $udata User|integer|array
     */
    function setUser($udata)
    {

        if (is_object($udata) && $udata instanceof User) {
            $this->U = $udata;

        } else {
            $this->U = new GuestUser();
        }

        return $this->U;
    }

    function updateUserActivity()
    {
        if (!$this->U->getAuthorized() || !$this->U->getId()) {
            return false;
        }

        $_user = _oh('user');
        $q = "UPDATE " . $_user->vltURI() . " SET `last_activity` = '" . $this->DB()->formatDateTime() . "' 
    WHERE `" . $_user->getPAC() . "` = " . $this->U->getId() . " LIMIT 1";

        $this->DB()->query($q);

        return true;
    }

    /**
     * Создание в системе SQL-соединения по переданным праметрам и в случае успеха
     * сохранение объекта интерфейса соединения во внутреннем свойстве класса.
     *
     * @param array $cp - массив параметров подключения. Имеет вид array(
     *  'host'=> хост,
     *  'port'=> порт SQL-сервера,
     *  'user'=> пользователь авторизации,
     *  'password'=> пароль,
     *  ['db'] => если передано, после успешной установки соединения будет выбрана эта БД)
     * @param string $type тип SQL-сервера. На данный момент поддерживается только работа с MySQL
     *
     * @return \Verba\DBDriver\mysql\Driver|false объект интерфейса соединения либо ошибку
     * @see db
     * @see query
     */
    function DbConnect($connectData = false, $driverType = 'mysql')
    {
        if ($connectData === false) {
            $connectData = $this->gC('db');
            $connectData['database'] = false;
        }

        $driverType = strtolower($driverType);

        if (isset($this->connections[$driverType][$connectData["host"]][$connectData['user']])
            && is_object($this->connections[$driverType][$connectData['host']][$connectData['user']]))
        {
            return $this->connections[$driverType][$connectData["host"]][$connectData['user']];
        }

        $mt = '\Verba\DBDriver\\'.$driverType.'\Driver';
        if (!class_exists($mt)) {
            throw new Exception('Unable to load DB driver [' . var_export($driverType, true) . ']');
        }
        $conObj = new $mt($connectData, $this->gC('debug'));

        if (is_object($conObj->getResource())) {
            if (isset($this->connections[$driverType]) && !is_array($this->connections[$driverType]))
                $this->connections[$driverType] = array();

            return ($this->connections[$driverType][$connectData['host']][$connectData['user']] = $conObj);
        } else {
            return false;
        }
    }

    /**
     * Возвращает интерфейс работы с БД
     *
     * @return Driver
     */
    function DB()
    {
        return is_object($this->DB) ? $this->DB : ($this->DB = $this->DbConnect());
    }

    /**
     * Генерация случайной строки.
     *
     * @param int $min минимальная длина сгенерированной строки
     * @param int $max максимальная  длина сгенерированной строки
     * @param string|false l- буквенные символы верхнего и нижнего регистров, плюс цифры; false - только буквенные символы нижнего регистра;
     *
     * @return string сгенерированная случайная строка
     */
    static function make_random_string($min = false, $max = false, $alf = false)
    {

        $alfabet = range('a', 'z');

        if ($alf !== 'l') {
            $alfabet = array_merge($alfabet, range('A', 'Z'), range(0, 9));
        }
        $alfabet = array_flip($alfabet);

        $min = is_numeric($min) && $min <= 32 && $min > 0 ? $min : 1;
        $max = is_numeric($max) && $max >= $min && $max <= 32 ? $max : 32;
        $r = array_rand($alfabet, rand($min, $max));
        shuffle($r);
        return implode('', $r);
    }

    /**
     * Инициализирует если еще не было вызовов в ходе работы движка и возвращает
     * объект работы с файловой системой.
     *
     * @return  Local объект  \Verba\FileSystem\Local или false
     * @see  \Verba\FileSystem\Local
     */
    function getFS()
    {
        return is_object($this->FSLocal) ? $this->FSLocal : false;
    }

    /**
     * Записывает в свойство класса 'back_url'
     *
     * @param string $url URL обратной ссылки, если не передано будет установлен текущий $_SERVER['REQUEST_URI']
     * @return void
     */
    static function setBackURL($url = false)
    {
        if (is_string($url) && !empty($url)) {
            $Url = new Url($url);
            self::$current['back_url'] = $Url->get(true);
        }

        return self::$current['back_url'];
    }

    /**
     * Возвращает значение 'back_url'
     *
     * @return string последний запомненный URL
     */
    static function getBackURL()
    {
        return self::$current['back_url'];
    }

    static function saveBackUrl(){
        return self::setBackURL(self::genCurrentUrl());
    }

    static function genCurrentUrl(){
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * Возвращает текущий объект Page
     *
     * @return Page
     * @see Page
     */
    function getPage()
    {
        return $this->Page;
    }

    /**
     * Возвращает текущий объект  U
     *
     * @return User
     * @see \Verba\Mod\User\Model\User
     */
    function U()
    {
        return $this->U;
    }

    /**
     * Возвращает текущий объект KeyKeeper
     *
     * @return object объект KeyKeeper
     * @see KeyKeeper
     */
    function KK()
    {
        return $this->KK;
    }

    /**
     * Загружает из БД параметры хранища ОТ по его id и ключу доступа после чего создает экземпляр объекта хранилища.
     * @param int $vlt_id id ваулта
     * @param bool $avtoload если передано true то в случае отсутствия данных по ваулту, будет выполнена попытка загрузить их из базы.
     * @return object \Verba\ObjectType\DataVault в случае успеха, иначе - false
     * @see \Verba\ObjectType\DataVault
     */
    function getDataVaultById($vlt_id, $avtoload = true)
    {
        return is_object($this->data_vaults[$vlt_id]) // ваулт уже был загружен
        || ($avtoload && $this->loadDataVaultById($vlt_id) && is_object($this->data_vaults[$vlt_id])) //
            ? $this->data_vaults[$vlt_id]
            : false;
    }

    function loadDataVaultById($vlt_id)
    {
        if (!is_string($where_statement = $this->DB->makeWhereStatement($vlt_id, 'vlt_id')))
            return false;

        $query = "SELECT * FROM " . SYS_DATABASE . "._obj_data_vaults as vlt WHERE $where_statement";

        if (!($oRes = $this->DB->query($query)) || $oRes->getNumRows() < 1)
            return false;

        while ($row = $oRes->fetchRow()) {
            $this->addDataVault($row['vlt_id'], $row);
        }
    }

    function addDataVault($vlt_id, $row)
    {
        $vlt_id = (int)$vlt_id;
        if (array_key_exists($vlt_id, $this->data_vaults) && is_object($this->data_vaults[$vlt_id])) {
            return true;
        }
        $this->data_vaults[$vlt_id] = new DataVault($row);
        return is_object($this->data_vaults[$vlt_id]);
    }

    static function getRealIncludeLocation($path)
    {
        if (Hive::getPlatform() == 'win') $path = str_replace('/', '\\', $path);
        foreach (array_reverse(get_included_files()) as $k => $v) {
            if (strpos($v, $path) !== false) {
                return $v;
            }
        }
        return false;
    }

    function getOtList()
    {
        return $this->ots['binds'];
    }

    function loadOtList()
    {

        $query = "SELECT `id`, `ot_id`, `base`, `ot_code`, `handler`
    FROM `" . SYS_DATABASE . "`.`_obj_types`
    ORDER BY base, id";

        if (!($oRes = $this->DB()->query($query))) {
            throw new Exception('Unable to load OT list - sql Error: (' . $this->DB()->getLastError() . ')');
        }
        if ($oRes->getNumRows() < 1) {
            throw new Exception('OT list is empty');
        }

        while ($row = $oRes->fetchRow()) {
            $this->ots['binds'][(int)$row['id']] = $row['ot_code'];

            $this->ots['items'][$row['id']] = array(
                'code' => $row['ot_code'],
                'class' => $row['handler'],
                'base' => false,
                'instance' => false,
                'descendants_direct' => array(),
                'descendants_all' => array(),
                'ancestors' => array(),
            );
            if ($row['base']) {
                $this->ots['items'][$row['id']]['base'] = $row['base'];
                $this->ots['items'][$row['base']]['descendants_direct'][$row['id']] =
                $this->ots['items'][$row['base']]['descendants_all'][$row['id']] = (int)$row['id'];
            }
        }

        foreach ($this->ots['items'] as $ot_id => $ot_data) {
            if (!count($ot_data['descendants_direct'])) {
                continue;
            }
            $this->findAllNodeDescendats($ot_id, $ot_data);
        }

        return true;
    }

    function findAllNodeDescendats($nodeId, $nodeData)
    {
        if (!is_array($nodeData['descendants_direct']) || !count($nodeData['descendants_direct'])) {
            return null;
        }
        foreach ($nodeData['descendants_direct'] as $dsc_id) {
            $this->findAllNodeDescendats($dsc_id, $this->ots['items'][$dsc_id]);
        }

        if ($nodeData['base']) {
            $this->ots['items'][$nodeData['base']]['descendants_all'] += $this->ots['items'][$nodeId]['descendants_all'];
        }
    }

    /**
     * По цифровому id возвращает символьный код ОТ
     * @param int $needle
     * @return string|false
     */
    function otIdToCode($needle)
    {
        return array_key_exists($needle, $this->ots['binds']) ? $this->ots['binds'][$needle] : false;
    }

    /**
     * По коду ОТ возвращает цифровой id Типа Объектов
     * @param string $needle
     * @return integer|false
     */
    function otCodeToId($needle)
    {
        return count($found = array_keys($this->ots['binds'], $needle)) ? current($found) : false;
    }

    function otSomeToId($needle)
    {
        if (!is_numeric($needle) && is_string($needle)) {
            return $this->otCodeToId(strtolower($needle));
        } else {
            return array_key_exists($needle, $this->ots['binds']) ? $needle : false;
        }
    }

    /**
     * Возвращает объект ObjectHadler-а. Если в ходе работы это первый вызов OH для этого id - пытается инициализировать его.
     * @param int|string $ot_id id или код ОТ.
     * @return Model
     * @see Model
     */
    function oh($ot_id)
    {
        $input_ot_id = $ot_id;

        if (!is_numeric($ot_id)) {
            $ot_id = $this->otCodeToId($ot_id);
        }

        if (!isset($this->ots['items'][$ot_id])) {
            throw new Exception('Unknown OT - (' . $input_ot_id . ')');
        }

        $otcache = $this->gC('otCasheEnable');
//        $classPath = is_string($this->ots['items'][$ot_id]['class'])
//            ? SYS_OTYPES_DIR . '/' . $this->ots['items'][$ot_id]['class'] . '.php'
//            : false;

        if (is_object($this->ots['items'][$ot_id]['instance'])) {

            return $this->ots['items'][$ot_id]['instance'];

        } elseif ($otcache && is_object($oh = $this->otFromCache($ot_id))) {

            return ($this->ots['items'][$ot_id]['instance'] = $oh);

        }

        $loader = empty($this->ots['items'][$ot_id]['class'])
            ? Model::class : $this->ots['items'][$ot_id]['class'];

        $base_ot = $this->ots['items'][$ot_id]['base']
            ? $this->ots['items'][$ot_id]['base'] : false;

        $this->ots['items'][$ot_id]['instance'] = new $loader($ot_id, $base_ot);

        if ($otcache) {
            $this->otToCache($this->ots['items'][$ot_id]['instance']);
        }
        return $this->ots['items'][$ot_id]['instance'];
    }

    function isOt($otsome)
    {
        if (empty($otsome)) {
            return false;
        }

        if (is_object($otsome) && $otsome instanceof Model) {
            $otsome = $otsome->getID();
        }

        if (is_string($otsome)) {
            if (!is_numeric($otsome)) {
                $otsome = $this->otCodeToId($otsome);
            }
        }

        if (!is_integer($otsome)) {
            $otsome = (int)$otsome;
        }

        return array_key_exists($otsome, $this->ots['items']) ? $otsome : false;
    }

    function getOtCacheDirname()
    {
        return SYS_CACHE_DIR . '/system/otypes';
    }

    function getOtCacheFilename($otcode)
    {
        return $this->getOtCacheDirname() . '/' . $otcode . '.cache.' . SYS_LOCALE . '.php';
    }

    function otFromCache($ot_id)
    {
        $cachefile = $this->getOtCacheFilename($this->otIdToCode($ot_id));

        if (!is_file($cachefile = $this->getOtCacheFilename($this->otIdToCode($ot_id)))
            || !is_object($oh = unserialize(file_get_contents($cachefile)))
            || !($oh instanceof Model)) {
            return false;
        }
        return $oh;
    }

    function otToCache($oh)
    {
        $cachefile = $this->getOtCacheFilename($oh->getCode());
        $str = serialize($oh);
        if (false === @file_put_contents($cachefile, $str)) {
             Local::needDir(dirname($cachefile));
            file_put_contents($cachefile, $str);
        }
    }

    function clearOtCache()
    {
         Local::dirDeleteRecursive($this->getOtCacheDirname(), false, false, false);
    }

    function clearCache()
    {
        $this->sC(1, '__clearCache');
        unset(
            $_SESSION['list'],
            $_SESSION['acp'],
            $_SESSION['selections']
        );
        $this->clearOtCache();
    }

    function getOtDescendants($ot_id)
    {
        return isset($this->ots['items'][$ot_id]['descendants_all'])
            ? $this->ots['items'][$ot_id]['descendants_all']
            : null;
    }

    function getOtDescendantsDirect($ot_id)
    {
        return isset($this->ots['items'][$ot_id]['descendants_direct'])
            ? $this->ots['items'][$ot_id]['descendants_direct']
            : null;
    }

    static function getObjectVars($obj)
    {
        return get_object_vars($obj);
    }

    static function loadFormMakerClass()
    {
        return class_exists('\Verba\Act\Form');
    }

    static function loadMakeListClass()
    {
        return class_exists('\Verba\Act\MakeList');
    }

    static function explodeHandlerParamAsArray($str)
    {

        if (is_string($str)) {
            $pairs = explode(',', $str);
            $str = false;
            if (is_array($pairs) && count($pairs)) {
                $str = array();
                foreach ($pairs as $cbind) {
                    list($cvalueId, $cacode) = explode('-', $cbind);
                    if ($cvalueId && $cacode) {
                        $str[$cvalueId] = $cacode;
                    }
                }
            }
        }
        return $str;
    }

    static function stringToHandlerParts($handlerStr)
    {
        if (!preg_match("/([a-z_0-9\\\\]+)(?:\((.*)\))?$/i", $handlerStr, $_buf)) {
            return array($handlerStr, null);
        }

        $className = $_buf[1];
        $cfg = array();
        if (isset($_buf[2]) && !empty($_buf[2])) {
            $cfgParts = explode(';', $_buf[2]);
            foreach ($cfgParts as $cfgPair) {
                list($cfgKey, $cfgValue) = explode('=', $cfgPair);
                $cfg[$cfgKey] = $cfgValue;
            }
        }
        return array($className, $cfg);
    }

    static function stringToHandlers($str)
    {
        $r = array();
        if (!is_string($str) || !strlen($str)) {
            return $r;
        }
        $handlers = explode('~', $str);
        foreach ($handlers as $ch) {
            list($handlerName, $handlerCfg) = self::stringToHandlerParts($ch);
            $r[$handlerName] = is_array($handlerCfg) ? $handlerCfg : [];
        }
        return $r;
    }

    // Common functions

    static function initTpl(){
        return new FastTemplate;
    }

    static function conf($path)
    {
        global $S;
        return $S->gC($path);
    }
}

Hive::$default_config = array(
    'debug' => array(
        'sqlQueriesLog' => 0,
        'email' => 'pitonio@gmail.com',
        'mailSilence' => false,
        'mailSubjectPrefix' => false,
    ),
    'css_compile' => 0,
    'js_compile' => 0,
    'version' => 1,
    'cacheEnable' => 1,
    'otCasheEnable' => 1,
    'clearOtCache' => false,
    'lang' => array(
        'locale' => array(
            'used' => array(),
            'default' => ''
        ),
    ),
    'log' => array(
        'saveToDb' => array(
            'allow' => [],
            'disallow' => []
        ),
    ),
    'primary_host' => '',
    'isNationalDomain' => false,
    'subdomain' => '',
    'this_host' => $_SERVER['HTTP_HOST'],
    'prefix' => '',

    'db' => array(
        'host' => 'localhost',
        'port' => 3306,
        'user' => '',
        'password' => '',
        'database' => '',
    ),

    'session_cookies' => true,
    'smtp_host' => 'localhost',
    'smtp_port' => 25,

    'memcache' => false,
    'fileinfo_magic' => '',
    'script_key' => '',
    'path' => [
        'root' => '',
        'engine' => 'App',
        'images' => 'images', // relartive to webroot
        'css' => 'css',
        'js' => 'js',
        'upload' => 'userfiles',
        'var' => 'var',
        'pub' => 'public',
        'cache_dir' => 'cache',
    ],
    'cryptKey' => '',
    '__clearCache' => false,
);

/**
 * Возвращает экземпляр класса обработчика объектов для соответствующего $oh
 * @param int|string $oh id или символьный код Типа Объектов
 * @return Model
 * @see Model
 */
function _oh($oh)
{
    global $S;

    return is_object($oh) && $oh instanceof Model
        ? $oh
        : $S->oh($oh);
}

function isOt($some)
{
    global $S;
    return $S->isOt($some);
}

/**
 * Возвращает объект модуля по его id или символьному коду.
 * @param int|string $mod id или символьный код модуля
 * @return\Mod объект запрашиваемого модуля.
 * @see\Mod
 */
function _mod($mod)
{
    global $S;
    return $S->getModule($mod);
}

/**
 * Returns current user object.
 * @return User
 */
function User()
{
    return getUser();
}

/**
 * Returns current user object.
 * @return User
 */
function getUser()
{
    global $S;
    return $S->U();
}

/**
 * Returns current Page object.
 * @return object Page
 * @see\Mod
 */
function Page()
{
    global $S;
    return $S->getPage();
}

/**
 * Принимает массив и просеивает на предмет не числовых или строковых значений.
 * Если $str_allowed = true строковые допускаются.
 * @param array|int|string $inputstream массив, числовое либо строка. Если будет передан не массив то будет попытка првести к массиву.
 * @param bool $str_allowed флаг разрешающий присутствие строковых значений.
 * @param bool $return если false возращает булево значение, все операции производились над массивом. Если true - вернет обработанный массив.
 * @return array|false массив из целочисленных (и строковых, если $str_allowed == true) значений. Если массив пуст, возвращает ошибку.
 */
function convertToIdList(&$inputstream, $str_allowed = false, $return = false)
{
    if ($return) $handle = $inputstream;
    else $handle = &$inputstream;

    if (is_array($handle) || settype($handle, 'array')) {
        foreach ($handle as $key => $value) {
            if (!is_numeric($value) && !($str_allowed && is_string($value))) {
                unset($handle[$key]);
            }
        }
    }

    return is_array($handle) && !empty($handle)
        ? (!$return ? true : $handle) : false;
}

/**
 * Перенаправление пользовательнского агента по заданному URL-у. Если $location не задан - перенаправление на индексную страницу сервиса.
 * @param string $location
 * @return void
 */
function Xredirect($location = '')
{
    global $S;
    switch ($location) {
        default:
            $location = empty($location) ? Hive::getBackURL() : $location;
            header("Location: $location");
    }
    if (isset($S) && is_object($S)) {
        $S->__destruct();
    }
    exit;
}

/**
 * Добавляет в GET строку запроса переменную со значением
 * @param string $query_string строка запроса в которую необходимо добавить переменную.
 * @param string $var название и значение переменной в GET-формате записи. Например, 'var=value'.
 * @return string результирующая строка.
 */
function var2url($url, $params = false)
{
    $url = new Url($url);
    $url->setParams($params);
    return $url->get();
}

function array_reverse_recursive($haystack, $result = array())
{
    foreach ($haystack as $key => $subarray) {
        if (is_array($subarray)) {
            $rKey = key($result);
            $count = count($result);
            $result[key($subarray)] = !count($result)
                ? array($key => $key)
                : $result;
            if (count($result) > 1) {
                unset($result[$rKey]);
            }

            $result = array_reverse_recursive($subarray, $result);
        }
    }
    return $result;
}

function array_search_recursive($needle, $haystack)
{
    $path = array();
    foreach ($haystack as $id => $val) {
        if ($val === $needle) {
            $path[] = $id;
            break;
            # ^^this breaks out of loop when it finds needle
        } elseif (is_array($val)) {
            $found = array_search_recursive($needle, $val);
            if (count($found) > 0) {
                $path[$id] = $found;
                break;
                # ^^this breaks out of loop when recursive call found needl
            }
        }
    }
    return $path;
}

/**
 * Возвращает IP-адрес клиента, полученный из переменных окружения
 *
 * @return string|null IP-адрес или null
 */
function getClientIP()
{
    return $_SERVER['REMOTE_ADDR'];
}

/**
 * Базовая функция конвертирования timestamp-времени в строковое отображение
 * @param int $timestamp время в формате UNIX-timestamp
 * @param string $view формат вывода
 * @return string|false возвращает строку сгенерированную strftime либо ошибку
 */
function make_strftime($timestamp = 0, $view = '')
{
    if (!is_int($timestamp = intval($timestamp)) || $timestamp < 1)
        return false;

    if ($view == 'WithTime') {
        return utf8fix(strftime("%d %b. %Y %H:%M", $timestamp));
    } else {
        return utf8fix(strftime("%d %b. %Y", $timestamp));
    }
}

/**
 * Общая callback-функция для сортировки массива объектов обладающих свойством priority
 * @param object $a первый объект
 * @param object $b второй объект
 * @return int 0|1|-1
 */
function sort_by_priority($a, $b)
{
    if ($a->priority == $b->priority)
        return 0;
    else
        return ($a->priority > $b->priority) ? +1 : -1;
}

function sortByPriorityAsArray($a, $b, $reverse = false)
{
    if ($a['priority'] == $b['priority'])
        return 0;

    return ($a['priority'] > $b['priority']) ? 1 : -1;
}

function sortByPriorityAsArrayDesc($a, $b)
{
    if ($a['priority'] == $b['priority'])
        return 0;
    else
        return ($a['priority'] > $b['priority']) ? -1 : 1;
}

/**
 * Возвращает нужную форму слова ($word_root + $padeji[x]) в зависимости от количества $quantity
 * @param int $q количество объектов
 * @param string $word_root корень слова, например - 'объект'
 * @param array $padeji массив окончаний для соответствующих падежей. Наример,
 *       [0] => 'ов' (родительный(мн.) - объект[ов])
 *       [1] => ''   (именительный - объект[])
 *       [2] => 'а'  (винительный(мн.) - объект[а])
 *
 * @return string|false слово в соответствующем $q падеже или ошибку
 */
function make_padej_ru($q, $word_root, $padeji = array(0 => '', 1 => '', 2 => ''))
{

    if (!is_numeric($q) || !is_string($word_root))
        return false;

    $q = intval($q);
    settype($q, 'string');
    $ql = strlen($q);
    if ($ql > 1 && $q[$ql - 2] == '1') {
        return $word_root . $padeji[0]; // объектов
    } else {
        switch ($q[$ql - 1]) {
            case '0':
            case '5':
            case '6':
            case '7':
            case '8':
            case '9':
                return $word_root . $padeji[0]; // объектов
            case '1':
                return $word_root . $padeji[1];// объект
            case '2':
            case '3':
            case '4':
                return $word_root . $padeji[2];// объекта
        }
    }
}

/**
 * Функция вызывает make_padej_ru()
 * @see make_padej_ru()
 */
function make_padej_ua()
{
    $args = func_get_args();
    return call_user_func_array('\Verba\make_padej_ru', $args);
}

/**
 * Эмуляция make_padej_, функция просто добавляет 's' к корню, без какого либо морфологического анализа корня слова.
 * @param int $quantity количество объектов
 * @param string $word_root корень слова
 * @return string|false если $quantity > 1 $word_root+s
 * @see make_padej_ru()
 */
function make_padej_en($quantity, $word_root)
{

    if (!is_numeric($quantity) || empty($word_root))
        return false;

    if ($quantity > 1)
        $result = $word_root . 's';
    else
        $result = $word_root;

    return $result;
}

/**
 * Вырезает фрагмент заданной длины из HTML-текста с учетом ненарушения целостности тэгов.
 * @param string $text исходный текст
 * @param int $str_len длина строки
 * @param int $count
 * @param array $ar
 * @param string $flag
 * @param string $fl
 *
 * @return string
 */
function HTMLGetFormattedText(&$text, $str_len = 150, $count = 0, &$cut_str = "", &$ar = array(), $flag = "false", &$fl = "false")
{
    //Файл меньше резаной стоки?
    if ($flag == "false" && $fl == "false") {
        $flag = "true";

        $all_text_len = mb_strlen(preg_replace('/(<[^>|<]*)>|(\t*\n*\r*)*|\&nbsp;*|\&quot;*/', '', $text));
        if ($all_text_len < $str_len) {
            $str_len = $all_text_len;
            $fl = "true";
        }
    }

    if ($flag == "true") {
        //Строка до открываущего тега
        preg_match("/[\w*\W*\s*][^<]*/", $text, $out);

        // В стоке выбираем тег
        preg_match("/<[^IMG|BR|br|p|img][^>|<]*>|<b>/", $out[0], $teg);

        //Получаем чистую строку без тегов и т.д.
        $result = preg_replace("/(<[^>|<]*)>|(\t*\n*\r*)*|&nbsp;*|&quot;*/", "", $out[0]);
        $count += mb_strlen($result);

        // Определяем имя открыв.тега
        if (preg_match("/<(\s*\w+\s*)/", $teg[0], $t_name)) {
            $t_name[1] = trim($t_name[1]);
            array_push($ar, "</" . $t_name[1] . ">");
            krsort($ar);
        }

        // Если закрывающий тег
        if (preg_match("/<\s*(\/(\s*\w+\s*))/", $teg[0], $is_cl)) {
            $is_cl[1] = trim($is_cl[1]);
            if ((count($ar) != 0) && (in_array("<" . $is_cl[1] . ">", $ar))) {
                $ar = str_replace("<" . $is_cl[1] . ">", "", $ar);
            }
        }
        if ($count == $str_len) {
            $cut_str = $cut_str . $out[0];
        } elseif ($count < $str_len) {
            $cut_str .= $out[0];

            // Удаляем обработанную строку из начального текста
            $new_text = preg_replace("/[\w*\W*\s*][^<]*/", "", $text, 1);
            HTMLGetFormattedText($new_text, $str_len, $count, $cut_str, $ar, $flag, $fl);
        } else {
            preg_match_all("/&quot;|&nbsp;|(\n*\r*)*/", $out[0], $tabs);
            $col_other = mb_strlen(implode($tabs[0]));
            $count = $count + $col_other;
            $limit = $str_len - $count;
            $last_str = mb_substr($out[0], 0, $limit);
            preg_match_all("/&quot;|&(\w{1,4})|&(\w{1,4}\;)/", $last_str, $tabi);
            if (count($tabi[0]) != 0) {
                $count = $count - mb_strlen(implode($tabi[0]));
                $limit = $str_len - $count;
                $last_str = mb_substr($out[0], 0, $limit);
            }
            $cut_str = $cut_str . $last_str;
        }
        $end = '';
        foreach ($ar as $one) {
            if ($one != '') {
                $end .= $one;
            }
        }
        $end = trim($end);
        /*  echo $cut_str.$end;
            echo $count."<br>";
            echo $str_len; */
        return $cut_str . $end;
    } else {
        return $text;
    }
}

function close_dangling_tags($html)
{
    //сначала берем все открытые теги
    preg_match_all("#<([a-z]+)( .*)?(?!/)>#iU", $html, $result);
    $openedtags = $result[1];
    foreach ($openedtags as $key => $value) {
        if ($value == 'param') {
            unset($openedtags[$key]);
        }
    }

    // после все закрытые
    preg_match_all("#</([a-z]+)>#iU", $html, $result);
    $closedtags = $result[1];
    $len_opened = count($openedtags);

    # все теги закрыты
    if (count($closedtags) == $len_opened) {
        return $html;
    }

    $openedtags = array_reverse($openedtags);
    # close tags
    for ($i = 0; $i < $len_opened; $i++) {
        if (!in_array($openedtags[$i], $closedtags)) {
            $html .= '</' . $openedtags[$i] . '>';
        } else {
            unset($closedtags[array_search($openedtags[$i], $closedtags)]);
        }
    }
    return $html;
}

/**
 * Извлекает из массива значений выполенной SQL выборки значения для predefined-атрибутов, преобразуя код поля к [код_поля]__value.
 * @param string $c_attr_code символьный код атрибута
 * @param array $row массив значений, где ключами являются коды атрибутов.
 * @return string|false
 */
function exp_predefined($c_attr_code, $row)
{
    if (is_array($row) && isset($row[$c_attr_code . '__value']) && !empty($row[$c_attr_code . '__value']))
        return $row[$c_attr_code . '__value'];

    return false;
}

/**
 * Генерация HTML-фрагмента вывода результатов выполнения операций например, добавления записей, изменения и т.п.
 * Выводятся информационные сообщения сервиса и через определенный таймаут выполняется переадресация браузера.
 * @param string $url URL переадресации
 *
 * @return string
 */
function resultReport($url = false, $timeout = 6, $message = false, $template = 'common/action_result.tpl')
{

    $tpl = Hive::initTpl();
    $url = is_string($url) && !empty($url) ? $url : Hive::getBackURL();
    if ($timeout !== null) {
        $timeout = intval($timeout);

        $tpl->define(array('exp_action_result' => $template));

        $tpl->assign(array(
            'AR_MAIN_MESSAGES' => is_string($message) ? $message : Loger::getAllMessages(false, 'event', false),
            'JS_RELOCATE_TIME_SECONDS' => $timeout,
            'JS_RELOCATE_TIME_URL' => $url,
            'SESSION_ID_HIDDEN' => '<input type="hidden" name="' . session_name() . '" value="' . session_id() . '" />',
        ));

        return $tpl->parse(false, 'exp_action_result');
    } else {
        global $S;
        $S->getPage()->addHeader('Location', $url);
        return '';
    }
}

/**
 * Если $val numeric или string конвертирует в массив.
 * @param number|string|array $val
 * @param string $separator Разделитель по которому будет разбита входящая $val (если $val строка).
 * @return true|false true если изначально $val является массивом либо в случае успешной конвертации в массив $val. Иначе - false
 */
function reductionToArray(&$val, $separator = false)
{
    if (is_string($separator) && is_string($val)) {
        $val = explode($separator, $val);
    }
    return is_array($val) || (is_numeric($val) || is_string($val)) && settype($val, 'array')
        ? true
        : false;
}

function reductionToCurrency($val, $precision = 2)
{
    return round((float)$val, (int)$precision);
}

function reductionToFloat($val, $strictType = true, $precision = 5)
{
    $r = floatval($val);
    return $strictType ? round($r, $precision) : sprintf("%.{$precision}f", $r);
}

function array_matches2list($a, $length, $empty = null, $start = 1)
{
    $r = array();
    for ($i = 0; $i < $length; ++$i)
        $r[$i] = !empty($a[$start + $i]) ? $a[$start + $i] : $empty;
    return $r;
}

function div($arg1, $arg2, $default = null)
{
    if (!$arg2) return $default;
    return $arg1 / $arg2;
}

function hextostr($x)
{
    $s = '';
    foreach (explode("\n", trim(chunk_split($x, 2))) as $h) {
        $s .= chr(hexdec($h));
    }
    return ($s);
}

function strtohex($x)
{
    $s = '';
    foreach (str_split($x) as $c) {
        $s .= sprintf("%02X", ord($c));
    }
    return ($s);
}

function utf8fix($arg)
{
    return iconv(false, 'UTF-8', $arg);
}

function mb_capitalize($arg)
{
    return mb_strtoupper(mb_substr($arg, 0, 1)) . mb_substr($arg, 1);
}

function mb_truncate($str, $limit = 24)
{
    $str = (string)$str;
    if (!empty($str) && $limit && mb_strlen($str) > $limit)
        $str = mb_substr($str, 0, $limit - 3) . '...';
    return $str;
}

function translit($str, $subst = array(), $lang = 'ru')
{
    $ru = array(
        'а' => 'a', 'б' => 'b', 'в' => 'v',
        'г' => 'g', 'д' => 'd', 'е' => 'e',
        'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
        'и' => 'i', 'й' => 'y', 'к' => 'k',
        'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r',
        'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'х' => 'h', 'ц' => 'c',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
        'ь' => '\'', 'ы' => 'y', 'ъ' => '\'',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        'А' => 'A', 'Б' => 'B', 'В' => 'V',
        'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
        'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z',
        'И' => 'I', 'Й' => 'Y', 'К' => 'K',
        'Л' => 'L', 'М' => 'M', 'Н' => 'N',
        'О' => 'O', 'П' => 'P', 'Р' => 'R',
        'С' => 'S', 'Т' => 'T', 'У' => 'U',
        'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
        'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch',
        'Ь' => '\'', 'Ы' => 'Y', 'Ъ' => '\'',
        'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
        //ua
    );

    $ua = array(
        'и' => 'y', 'И' => 'Y',
        'i' => 'i', 'I' => 'I', 'ґ' => 'g', 'Ґ' => 'G',
        'є' => 'je', 'Є' => 'Je', 'ї' => 'ji', 'Ї' => 'Ji'
    );

    switch ($lang) {
        case 'ua':
            $converter = $ru + $ua;
            break;
        default:
            $converter = $ru;
    }
    $subst = (array)$subst;
    if (count($subst)) {
        $converter = array_merge($converter, $subst);
    }

    return strtr($str, $converter);
}

function strConvertToSeo($str, $lang = 'ru')
{
    $r = translit($str, array(' ' => '-'), $lang);
    $r = preg_replace(array('/\W+/', '/\-+/'), array('-', '-'), $r);
    $r = strtolower(trim($r, '-'));
    return $r;
}

function isTimestampValid($timestamp)
{
    return ((int)($timestamp) == $timestamp)
        && ($timestamp <= PHP_INT_MAX)
        && ($timestamp >= ~PHP_INT_MAX);
}

/**
 *
 * @param mixed $ot_id
 * @param mixed $key_id
 * @param mixed $slID
 * @return Selection
 */
function init_selection($ot_id, $key_id = false, $slID = false)
{
    $selection = false;
    if (is_string($slID) && !empty($slID)){
        $selectionID = $slID;
    } elseif (isset($_REQUEST['slID'])) {
        $selectionID = $_REQUEST['slID'];
    } elseif (isset($_REQUEST['slId'])) {
        $selectionID = $_REQUEST['slId'];
    } else {
        $selectionID = false;
    }

    if (is_string($selectionID)
        && isset($_SESSION['selections'][$selectionID])
        && is_array($_SESSION['selections'][$selectionID])
        && array_key_exists('data', $_SESSION['selections'][$selectionID])
        && is_object($selection = unserialize($_SESSION['selections'][$selectionID]['data']))
        && $selection instanceof Selection
    ) {
        $_SESSION['selections'][$selectionID]['time'] = time();
        $selection->setCacheUsed(true);
    } elseif ($ot_id) {
        $selection = new Selection($ot_id, $selectionID, $key_id);
    }
    return $selection;
}

function get_object_vars_public($obj)
{
    return get_object_vars($obj);
}

function potToArray($pot, $piid = null)
{
    $r = array();
    if (!is_array($pot)) {
        $pot = _oh($pot);
        if ($pot && $pot = $pot->getID()) {
            $r[$pot] = array();
            if ($piid && !Boolean::isStrBool($piid)) {
                $r[$pot][$piid] = $piid;
            }
        }
    } else {
        foreach ($pot as $pot_id => $piid) {
            $r[$pot_id] = is_array($piid) ? $piid : array($piid => $piid);
        }
    }
    return $r;
}

function esc($str)
{
    global $S;
    return $S->DB()->escape($str);
}
