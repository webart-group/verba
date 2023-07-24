<?php
namespace Verba;

class FastTemplate
{

    public $path = array();

    public $FILELIST = array();//  Holds the array of filehandles
    //  FILELIST[HANDLE] == "fileName"

    public $PARSEVARS = array();//  Holds the array of Variable
    //  handles.
    //  PARSEVARS[HANDLE] == "value"

    public $LOADED = array();//  We only want to load a template
    //  once - when it's used.
    //  LOADED[FILEHANDLE] == 1 if loaded
    //  undefined if not loaded yet.

    public $ROOT = '';        //  Holds path-to-templates

    public $WIN32 = false;    //  Set to true if this is a WIN32 server

    public $ERROR = '';        //  Holds the last error message

    public $LAST = '';        //  Holds the HANDLE to the last
    //  template parsed by parse()

    public $STRICT = true;    //  Strict template checking.
    //  Unresolved vars in templates will
    //  generate a warning when found.
    public $template_ext = 'tpl';
    public $filecount = 0;

    public $sharedKey = null;

    protected static $SHARED = array();


//  ************************************************************

    function __construct($tplPath = null)
    {
        if($tplPath === null) {
            $tplPath = SYS_TEMPLATES_DIR;
        }
        $this->setRoot($tplPath);
    }

    static function getShared($sharedKey, $cfg = false)
    {
        if (!is_string($sharedKey) || !$sharedKey) {
            return false;
        }

        if (!is_array($cfg)) {
            $cfg = array();
        }

        $tplPath = array_key_exists('tplPath', $cfg) ? $cfg['tplPath'] : SYS_TEMPLATES_DIR;

        $FT = new FastTemplate($tplPath);
        $FT->setSharedKey($sharedKey);
        $justInitiated = false;
        if (!array_key_exists($sharedKey, self::$SHARED)) {
            self::$SHARED[$sharedKey] = new FastTemplate\SharedData();
            $justInitiated = true;
        }
        self::$SHARED[$sharedKey]->init($FT);

        // применение конфига
        if ($justInitiated) {
            if (array_key_exists('templates', $cfg)) {
                $FT->define($cfg['templates']);
            }
        }

        return $FT;
    }

    function setSharedKey($sharedKey)
    {

        if (!is_string($sharedKey)) {
            return false;
        }

        $this->sharedKey = $sharedKey;

        return true;
    }

    function clearShared($key = false)
    {

        if (!is_string($key)) {
            $key = $this->sharedKey;
        }
        if (!array_key_exists($key, self::$SHARED)) {
            return false;
        }
        unset(self::$SHARED[$key]);

        return true;
    }

    function clearAllShared()
    {
        self::$SHARED = array();
    }

    function setRoot($tplPath)
    {
        $this->path[0] = $tplPath . '/';
    }

    function addPath($tplPath)
    {
        $this->path[] = $tplPath . '/';
    }

    function getCurrentPath()
    {
        return $this->path[0];
    }

    function loadTemplate($fileTag)
    {
        if (array_key_exists($fileTag, $this->LOADED)) {
            return $this->LOADED[$fileTag];
        }

        if (!array_key_exists($fileTag, $this->FILELIST) || !is_string($this->FILELIST[$fileTag])) {
            return false;
        }

        $this->filecount++;
        $templateContent = false;
        $i = 0;
        while (!is_string($templateContent) && $i < count($this->path)) {
            $path = $this->path[$i];
            $templateContent = @file_get_contents($path . $this->FILELIST[$fileTag], FILE_USE_INCLUDE_PATH);
            $i++;
        }

        $this->LOADED[$fileTag] = $templateContent;

        return is_string($this->LOADED[$fileTag]);
    }

    function parse_template($template)
    {
        //  This routine get's called by parse() and does the actual
        //  {VAR} to VALUE conversion within the template.

        preg_match_all("/\{(\w+)\}|\{\*([\w\s]+)\*\}/", $template, $templ);

        $templ[1] = array_flip($templ[1]);
        unset($templ[1]['']);
        if (count($templ[1])) {
            foreach ($templ[1] as $VarTag => $no_matter) {
                if ($VarTag == '') continue;
                $VarTagUpper = strtoupper($VarTag);
                if (isset($this->PARSEVARS[$VarTagUpper])) {
                    $template = str_replace('{' . $VarTag . '}', $this->PARSEVARS[$VarTagUpper], $template);
                }
            }
        }

        $templ[2] = array_flip($templ[2]);
        unset($templ[2]['']);
        if (count($templ[2])) {
            foreach ($templ[2] as $langKey => $no_matter) {
                if ($langKey == '') continue;
                if (is_string($langKeyValue = $this->parseLang($langKey))) {
                    $template = str_replace('{*' . $langKey . '*}', $langKeyValue, $template);
                }
            }
        }
        return $template;
    }

    function parseLang($langKey, $tplVar = false, $lang = false)
    {
        $lang = is_string($lang) ? $lang : SYS_LOCALE;

        $templateBody =  \Verba\Lang::getFromLang($lang, $langKey);
        if ($templateBody === null) {
            return null;
        }
        $r = $this->parse_template($templateBody);

        if (is_string($tplVar) || is_numeric($tplVar)) {
            $this->assign($tplVar, $r);
        }

        return $r;
    }

    function getVar($VarTag)
    {
        $VarTag = strtoupper($VarTag);
        return is_string($VarTag) && isset($this->PARSEVARS[$VarTag])
            ? $this->PARSEVARS[$VarTag]
            : null;
    }

    function getTemplate($fileTag)
    {
        if (!array_key_exists($fileTag, $this->LOADED)) {
            $this->loadTemplate($fileTag);
        }
        return isset($this->LOADED[$fileTag]) && is_string($this->LOADED[$fileTag])
            ? $this->LOADED[$fileTag]
            : false;
    }

    function getPath()
    {
        return $this->path;
    }

    function parse($ReturnVar, $fileTag, $append = null)
    {
        //  The meat of the whole class. The magic happens here.
        $append = (bool)$append;

        if (!$this->loadTemplate($fileTag)) {
            $this->error('Unable to Load Template file. fileTag: [' . var_export($fileTag, true) . '], filename:[' . var_export($this->FILELIST[$fileTag], true) . ']');
        }

        $output = $this->parse_template($this->LOADED[$fileTag]);

        if ($ReturnVar === false) {
            return $output;
        }
        $ReturnVar = strtoupper($ReturnVar);
        $this->PARSEVARS[$ReturnVar] = !$append ? $output : $this->PARSEVARS[$ReturnVar] . $output;
    }

    function FastPrint($VarName)
    {
        $VarName = strtoupper($VarName);
        if (isset($this->PARSEVARS[$VarName]) && is_string($this->PARSEVARS[$VarName])
            && !empty($this->PARSEVARS[$VarName])) {
            print $this->PARSEVARS[$VarName];
        } else {
            if (!isset($this->PARSEVARS[$VarName])) {
                \Verba\Loger::create('FastTemplate')->warning('Nothing parsed, nothing printed. Var to print [' . $VarName . ']');
            }
            print '';
        }

        return;
    }

    function define($fileList, $str = false)
    {

        if (!is_array($fileList) && is_string($fileList) && is_string($str)) {
            $fileList = array($fileList => $str);
        }

        if (!is_array($fileList))
            return false;

        foreach ($fileList as $FileTag => $FileName) {
            $this->FILELIST[$FileTag] = $FileName;
        }
        return true;
    }

    function getFilelist()
    {
        return $this->FILELIST;
    }

    function getTplPath($tplKey)
    {
        return array_key_exists($tplKey, $this->FILELIST) ? $this->FILELIST[$tplKey] : null;
    }

    function clear_vars()
    {
        $args = func_get_args();
        if (!is_array($args) || !count($args)) {
            $this->PARSEVARS = array();
            return true;
        }

        if (count($args) == 1 && is_array($args[0])) {
            $keys = $args[0];
        } else {
            $keys = $args;
        }
        foreach ($keys as $key) {
            if (!array_key_exists($key, $this->PARSEVARS)) {
                continue;
            }
            unset($this->PARSEVARS[$key]);
        }
        return true;
    }

    function clear_tpl()
    {
        $args = func_get_args();
        if (!is_array($args) || !count($args)) {
            $this->LOADED = array();
            $this->FILELIST = array();
            return true;
        }

        if (count($args) == 1 && is_array($args[0])) {
            $keys = $args[0];
        } else {
            $keys = $args;
        }
        foreach ($keys as $key) {
            if (array_key_exists($key, $this->LOADED)) {
                unset($this->LOADED[$key]);
            }
            if (array_key_exists($key, $this->FILELIST)) {
                unset($this->FILELIST[$key]);
            }
        }
        return true;
    }

    function assign($array, $VarValue = false, $append = null)
    {
        $append = (bool)$append;

        if (is_string($array) && (is_string($VarValue) || is_numeric($VarValue))) {
            $array = array($array => $VarValue);
        }

        if (!is_array($array) || !count($array)) return false;

        foreach ($array as $key => $val) {
            $key = strtoupper($key);
            settype($val, 'string');
            if ($append && array_key_exists($key, $this->PARSEVARS)) {
                $this->PARSEVARS[$key] .= $val;
            } else {
                $this->PARSEVARS[$key] = $val;
            }
        }
    }

    function asg($VarName, $VarVal)
    {
        $this->PARSEVARS[strtoupper($VarName)] = (string)$VarVal;
    }

    function error($errorMsg)
    {
        $this->ERROR = $errorMsg;
        throw new \Exception($this->ERROR);
    }

    function isDefined($TplTag)
    {
        return !(!is_string($TplTag)
            || !array_key_exists($TplTag, $this->FILELIST)
            || !$this->FILELIST[$TplTag]);
    }

    /**
     * @param $tpl FastTemplate
     */

    function tieTemplatesFrom($tpl)
    {
        $this->FILELIST = &$tpl->FILELIST;
        $this->LOADED = &$tpl->LOADED;
    }
} // End class.FastTemplate.php3

