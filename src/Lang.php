<?php
namespace Verba;

class Lang extends Base
{
    public static $lang;
    private static $srcPath;
    private static $data = array();
    private static $compilePath;
    private static $compileJsPath;
    private static $compileJsPathRel;
    private static $debug = null;
    public static $languages = [
        'en' => [['en_US.utf8',], ['eng'], 'eng', 'English'],
        'ua' => array(['ru_UA.utf8'], ['ukrainian'], 'укр', 'Українська'),
        'ru' => array(['ru_RU', 'ru_RU.utf8'], ['russian_Russia'], 'рус', 'Русский')
    ];
    public static $config;
    public static $_config_default;

    public static $_toClient = array();

    static function init($requestedLC, $cfg = null)
    {
        if (self::$debug === null) self::$debug = Hive::$debug;
        self::$config = array_replace_recursive(self::$_config_default, $cfg);
        if (is_array(self::$config['locale']['used'])) {
            foreach (self::$config['locale']['used'] as $k => $lc) {
                if (!self::isLCValid($lc))
                    unset(self::$config['locale']['used']);
            }
        }

        if (empty(self::$config['locale']['used']))
            throw new \Exception('No one language locale specified. Check system config');

        if (empty(self::$config['locale']['default']) || !self::isLCValid(self::$config['locale']['default'])) {
            throw new \Exception('Default language not specified. Check system config');
        }
        //define('SYS_LC_DEFAULT', self::$config['locale']['default']);

        self::setLocale($requestedLC);

        self::$srcPath = APP_RESOURCES_DIR . '/languages';


        self::$compilePath = SYS_VAR_DIR . '/languages';
        self::$compileJsPath = SYS_PUB_VAR . '/languages';
        self::$compileJsPathRel = SYS_PUB_VAR_URL . '/languages';

        self::loadLang(self::$lang);
    }

    /**
     * Инициализация установки локали PHP. Если в пришедших параметрах есть ключ lc
     * и его значение отличается от кода локали предыдушей жизни скрипта - смена языка.
     *
     * @return string|false ошибка в случае невозможности установки PHP локали. В случае смены языка - редирект на последнюю запомненный URL в сессии.
     */
    static function setLocale($requestedLC = false)
    {

        $previous = isset($_SESSION['lang']['locale']) ? $_SESSION['lang']['locale'] : false;
        $try2apply = is_string($requestedLC) && !empty($requestedLC) && $previous !== $requestedLC ? $requestedLC : $previous;

        if (!self::isLCValid($try2apply))
            $try2apply = SYS_LC_DEFAULT;

        $lcPropMtd = \Verba\Hive::getPlatform() == 'win' ? 'getLCWinCode' : 'getLCUnixCode';
        $args = self::$lcPropMtd($try2apply);
        array_unshift($args, LC_ALL);
        $r = call_user_func_array('setlocale', $args);
        if ($r !== false) {
            $locale = $try2apply;
        } else {
            \Verba\Loger::create(__CLASS__)->error('Unable to set locale [' . var_export($try2apply, true) . ']');
            $locale = SYS_LC_DEFAULT;
        }

        define('SYS_LOCALE', $locale);
        self::$lang = $locale;

        setlocale(LC_NUMERIC, 'C');

        return \setlocale(LC_ALL, 0);
    }

    static function getUsedLC()
    {
        return self::$config['locale']['used'];
    }

    static protected function getExistsLC()
    {
        return array_keys(self::$languages);
    }

    static function getDefaultLC()
    {
        return self::$config['locale']['default'];
    }

    static function getLCUnixCode($lcCode)
    {
        return isset(self::$languages[$lcCode][0]) ? self::$languages[$lcCode][0] : false;
    }

    static function getLCWinCode($lcCode)
    {
        return isset(self::$languages[$lcCode][1]) ? self::$languages[$lcCode][1] : false;
    }

    static function getLCShortName($lcCode)
    {
        return isset(self::$languages[$lcCode][2]) ? self::$languages[$lcCode][2] : false;
    }

    static function getLCName($lcCode)
    {
        return isset(self::$languages[$lcCode][3]) ? self::$languages[$lcCode][3] : false;
    }

    static function isLCValid($lcCode)
    {
        return isset(self::$languages[$lcCode]);
    }

    static function getSrcPath()
    {
        return self::$srcPath;
    }

    private static function addLangCode($lang)
    {
        if (self::isLCValid($lang)) {
            if (!array_key_exists($lang, self::$data))
                self::$data[$lang] = array();
            return true;
        }
        return false;
    }

    static function getDctFilename($lang)
    {
        return $lang . '.compiled.php';
    }

    static function getDctFilepath($filename = false)
    {
        return is_string($filename) && !empty($filename) ? self::$compilePath . '/' . $filename : self::$compilePath;
    }

    private static function loadLang($lang)
    {
        if (!self::addLangCode($lang)) {
            return false;
        }
        $path = self::getDctFilepath(self::getDctFilename($lang));
        if (\Verba\Hive::$cacheEnable && is_readable($path)) {
            self::$data[$lang] = require_once($path);
            return true;
        }
        self::$data[$lang] = self::compileLangFile($lang);

        if (!self::$data[$lang]) {
            return false;
        }

        return true;
    }

    private static function compileLangFile($lang)
    {

        $lcSrcPath = self::$srcPath . '/' . $lang;

        $LangArr = self::loadLangSrc($lcSrcPath);
        //self::compileJsLangFile($lang, $LangArr);
        if (!is_array($LangArr) || !count($LangArr)) return false;

        $compiledLangPath = self::getDctFilepath(self::getDctFilename($lang));
        if (!\Verba\FileSystem\Local::needDir(self::getDctFilepath())) {
            \Verba\Loger::create(__CLASS__)->error('Unable to create php-lang dir [' . var_export($compiledLangPath, true) . ']');
            return false;
        }
        if (!file_put_contents($compiledLangPath, "<?php return " . var_export($LangArr, true) . "?>", LOCK_EX)) {
            \Verba\Loger::create(__CLASS__)->error('Unable to write php-lang file. [' . var_export($compiledLangPath, true) . ']');
            return false;
        }

        return $LangArr;
    }

    private static function loadLangSrc($srcPath){

        if(!is_dir($srcPath)){
            return false;
        }

        $LANG = [];

        $langSrcFiles =  \Verba\FileSystem\Local::scandir($srcPath, 1, true, '\Verba\Lang::filterFilesList');
        if (!is_array($langSrcFiles) || !count($langSrcFiles)) {
            return $LANG;
        }

        foreach ($langSrcFiles as $filepath) {
            $r = require_once($filepath);
            if (!is_array($r)) {
                continue;
            }
            $LANG += $r;
        }

        return $LANG;
    }

    static function filterFilesList($filename, $isD)
    {
        return $isD && basename($filename) == '.svn'
            ? null
            : $filename;
    }

    static function getFromLang($lang, $path, $args = null)
    {

        if (!is_array($path) && !(is_string($path) && is_array($path = explode(' ', $path))) || count($path) < 1 || !self::isLCValid($lang))
            return false;

        if (!array_key_exists($lang, self::$data) && !self::loadLang($lang)
            || !is_array(self::$data[$lang])) {
            return false;
        }

        $v = &self::$data[$lang];
        if (count($path) > 0) {
            foreach ($path as $c_node) {
                if (!is_array($v) || !array_key_exists($c_node, $v)) return null;
                $v = &$v[$c_node];
            }
        }

        if (isset($args) && is_array($args) && is_string($v)) {
            $r = $v;
            foreach ($args as $k => $val) {
                $r = str_replace("{" . $k . "}", $val, $r);
            }
            return $r;
        }
        return $v;
    }

    /**
     * Returns text in current language from dictionary by passed key chain
     *
     * @param string $path space separated keys into lang file
     * @param array $args array(key => value), {key} will be substituted by value into phrase
     */
    static function get($path, $args = null)
    {
        $r = self::getFromLang(self::$lang, $path, $args);
        if (!$r && self::$lang != SYS_LC_DEFAULT) {
            $r = self::getFromLang(SYS_LC_DEFAULT, $path, $args);
        }
        return $r;
    }

    static function dbg()
    {
        echo '<pre>';
        echo '<b>current lang</b>: ' . self::$lang . "\n";
        echo '<b>default lang</b>: ' . SYS_LC_DEFAULT . "\n";
        echo '<b>LangfileRoot</b>: ' . self::$srcPath . "\n";
        echo '<b>Loaded</b>: ' . print_r(self::$data, true) . "\n";
        echo '</pre>';
    }

    static function substPlaneLcdAttrByLcArray(&$item, $acode)
    {
        if (!is_array($item) || !count($item)
            || !is_string($acode) || empty($acode)) {
            return $item;
        }
        $locales = self::getUsedLC();
        $item[$acode] = array();
        foreach ($locales as $lc_code) {
            $str_code = $acode . '_' . $lc_code;
            if (isset($item[$str_code])) {
                $item[$acode][$lc_code] = $item[$str_code];
                unset($item[$str_code]);
            }
        }
        return $item;
    }

    static function getJsPathRel()
    {
        return self::$compileJsPathRel;
    }

    static function getJsDctFilepath($filename = false)
    {
        return is_string($filename) ? self::$compileJsPath . '/' . $filename : self::$compileJsPath;
    }

    static function sendToClient($path)
    {

        if (is_bool($path)) {
            self::$_toClient = $path;
            return self::$_toClient;
        }

        if (is_string($path)) {
            $path = array($path);
        }
        if (is_array($path)) {
            foreach ($path as $cpath) {
                self::$_toClient[$cpath] = null;
            }
        }

        return self::$_toClient;
    }

    static function compileJsLangFile($lang = false)
    {
        $lang = !$lang ? self::$lang : $lang;

        if (!is_array(self::$data[$lang]) || !count(self::$data[$lang])) {
            return false;
        }

        if (is_bool(self::$_toClient) && self::$_toClient) {

            $content = &self::$data[$lang];
            $allKeysStr = '~fullcontent~';

        } elseif (is_array(self::$_toClient) && count(self::$_toClient)) {

            $content = array();
            $allKeysStr = '';
            foreach (self::$_toClient as $path => $nomatter) {
                $pathArr = explode(' ', $path);
                if (!is_array($pathArr) || !count($pathArr)) {
                    continue;
                }
                $allKeysStr .= "\t" . $path;
                $v = &self::$data[$lang];
                $cv = &$content;
                foreach ($pathArr as $c_node) {
                    if (!array_key_exists($c_node, $v)) {
                        break;
                    }

                    if (!array_key_exists($c_node, $cv)) {
                        $cv[$c_node] = array();
                    }

                    $cv = &$cv[$c_node];
                    $v = &$v[$c_node];
                }
                $cv = $v;
            }
        } else {
            return null;
        }

        $filenamebody = $lang . '-' . md5($allKeysStr);
        $filename = $filenamebody . '.js';

        $filepath = self::getJsDctFilepath($filename);

        if (!file_put_contents($filepath, 'window._init_lang_data = {' . $lang . ': ' . json_encode($content) . '};')) {
            \Verba\Loger::create(__CLASS__)->error('Unable to write compiled js-lang file [' . var_export($filepath, true) . ']');
            return false;
        }
        return array($filename, $filenamebody, $filepath);
    }

    static function getJsDctFilename($lang)
    {
        return $lang . '.compiled.js';
    }

    static function compileJsLangFile_old($lang, $content)
    {
        if (!$content || !is_array($content) || !count($content)) {
            $content = array();
        }
        $filepath = self::getJsDctFilepath(self::getJsDctFilename($lang));
        $dname = dirname($filepath);
        if (!\Verba\FileSystem\Local::needDir(self::getJsDctFilepath())) {
            \Verba\Loger::create(__CLASS__)->error('Unable to create js-lang dir [' . var_export($filepath, true) . ']');
            return false;
        }
        if (!file_put_contents($filepath, 'Lang.data.' . $lang . ' = ' . json_encode($content) . ';Lang.avaible = ' . json_encode(self::getUsedLC()))) {
            \Verba\Loger::create(__CLASS__)->error('Unable to write compiled js-lang file [' . var_export($filepath, true) . ']');
            return false;
        }
        return true;
    }

    static function getLcData()
    {
        $r = [];
        foreach (self::getUsedLC() as $lc)
        {
            $r[$lc] = [
                'code' => $lc,
                'shortName' => self::getLCShortName($lc),
                'name' => self::getLCName($lc)
            ];
        }
        return $r;
    }
}

Lang::$_config_default = [
    'locale' => [
        'used' => [''],
        'default' => false
    ]
];
