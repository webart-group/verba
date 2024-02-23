<?php

namespace Verba\Html;

trait Includes
{
    public $headers = [];
    public $namedMeta = array();
    public $headTags = array();
    public $metaProperties = array();
    public $css = array();
    public $scripts = array();
    public $jsAfter = array();
    public $jsBefore = array();
    public $_htmlIncludes_internal_priority;

    public $inccfg = array(
        'css' => array('loc' => SYS_CSS_URL, 'prop' => 'css', 'ext' => '.css'),
        'js' => array('loc' => SYS_JS_URL, 'prop' => 'scripts', 'ext' => '.js')
    );

    function mergeHtmlIncludes($h)
    {
        if (property_exists($h, 'inccfg') && is_array($h->inccfg)) {
            foreach ($h->inccfg as $k => $propCfg) {
                if (!property_exists($h, $propCfg['prop'])
                    || !is_array($h->{$propCfg['prop']}) || !count($h->{$propCfg['prop']})
                    || !property_exists($this, $propCfg['prop'])) {
                    continue;
                }
                foreach ($h->{$propCfg['prop']} as $hash => $incFile) {
                    if (array_key_exists($hash, $this->{$propCfg['prop']})) {
                        continue;
                    }
                    $this->{$propCfg['prop']}[$hash] = $incFile;
                }
            }
        }
        if (property_exists($h, 'jsBefore') && is_array($h->jsBefore) && count($h->jsBefore)) {
            if (!empty($h->jsBefore)) {
                $this->jsBefore = array_merge($this->jsBefore, $h->jsBefore);
            }
        }
        if (property_exists($h, 'jsAfter') && is_array($h->jsAfter) && count($h->jsAfter)) {
            if (!empty($h->jsAfter)) {
                $this->jsAfter = array_merge($this->jsAfter, $h->jsAfter);
            }
        }
        if (property_exists($h, 'headers') && is_array($h->headers) && count($h->headers)) {
            if (!empty($h->headers)) {
                $this->headers = array_merge($this->headers, $h->headers);
            }
        }
        if (property_exists($h, 'namedMeta') && is_array($h->namedMeta) && count($h->namedMeta)) {
            if (!empty($h->namedMeta)) {
                $this->namedMeta = array_merge($this->namedMeta, $h->namedMeta);
            }
        }
        if (property_exists($h, 'metaProperties') && is_array($h->metaProperties) && count($h->metaProperties)) {
            if (!empty($h->metaProperties)) {
                $this->metaProperties = array_merge($this->metaProperties, $h->metaProperties);
            }
        }
        if (property_exists($h, 'headTags') && is_array($h->headTags) && count($h->headTags)) {
            if (!empty($h->headTags)) {
                $this->headTags = array_merge($this->headTags, $h->headTags);
            }
        }

    }

    function getInternalPrority()
    {
        if (!is_object($this->_htmlIncludes_internal_priority)) {
            $this->_htmlIncludes_internal_priority = new Includes\InternalPriority();
        }
        return $this->_htmlIncludes_internal_priority;
    }

    function setHeaders($val)
    {
        if (!is_array($this->headers)) {
            $this->headers = array();
        }

        if (empty($val)) {
            return false;
        }

        if (!is_array($val)) {
            $val = array($val);
        }

        $this->headers = array_merge($this->headers, $val);

        return $this->headers;
    }

    function addHeader()
    {
        $args = func_get_args();
        switch (count($args)) {
            case 1:
                $this->headers[] = $args[0];
                break;
            case 2:
                $this->headers[$args[0]] = $args[1];
                break;
        }
    }

    private function handleProxyMetaData($args, $action)
    {
//    if($args[0] == 'title'){
//      $method = $action.'Title';
//      array_shift($args);
//    }else{
//      $method = $action.'NamedMeta';
//    }
//    return array($method, $args);
        return array($action . 'NamedMeta', $args);
    }

    /**
     * @param mixed $metaName
     * @param mixed $str
     * @param mixed $conn
     */
    function setMeta()
    {
        $args = func_get_args();
        list($method, $args) = $this->handleProxyMetaData($args, 'set');
        return call_user_func_array(array($this, $method), $args);
    }

    /**
     * @param mixed $metaName
     * @param mixed $str
     * @param mixed $conn
     * @param mixed $ctx
     */
    function prependMeta()
    {
        $args = func_get_args();
        list($method, $args) = $this->handleProxyMetaData($args, 'prepend');
        return call_user_func_array(array($this, $method), $args);
    }

    /**
     * @param mixed $metaName
     * @param mixed $str
     * @param mixed $conn
     */
    function appendMeta()
    {
        $args = func_get_args();
        list($method, $args) = $this->handleProxyMetaData($args, 'append');
        return call_user_func_array(array($this, $method), $args);
    }

    /**
     * @param mixed $metaName
     * @param mixed $ctx
     */
    function clearMeta()
    {
        $args = func_get_args();
        list($method, $args) = $this->handleProxyMetaData($args, 'clear');
        return call_user_func_array(array($this, $method), $args);
    }

    function setNamedMeta($metaName, $str = '', $conn = false, $ctx = null)
    {

        if (!is_array($this->namedMeta)) {
            $this->namedMeta = array();
        }
        if (func_num_args() == 1 && is_array($metaName)) {
            $this->namedMeta = $metaName;
            return $this->namedMeta;
        }
        if (!is_string($str) || !is_string($metaName)) {
            return false;
        }
        if (!is_array($ctx)) {
            $ctx = &$this->namedMeta;
        }
        $ctx[$metaName] = $str;
        return $ctx[$metaName];
    }

    function getNamedMeta($metaName = null, $ctx = null)
    {
        if (!isset($metaName)) {
            return $this->namedMeta;
        }
        if (!is_array($ctx)) {
            $ctx = &$this->namedMeta;
        }
        return isset($ctx[$metaName]) ? $ctx[$metaName] : null;
    }

    function prependNamedMeta($metaName, $str, $conn = false, $ctx = null)
    {
        if (!is_string($str)) {
            return false;
        }
        if (!is_array($ctx)) {
            $ctx = &$this->namedMeta;
        }

        if (!isset($ctx[$metaName]) || empty($ctx[$metaName])) {
            $ctx[$metaName] = (string)$str;
        } else {
            $conn = is_string($conn) ? $conn : '';
            $ctx[$metaName] = $str . $conn . $ctx[$metaName];
        }
        return $ctx[$metaName];
    }

    function appendNamedMeta($metaName, $str, $conn = false, $ctx = null)
    {
        if (!is_string($str)) {
            return false;
        }
        if (!is_array($ctx)) {
            $ctx = &$this->namedMeta;
        }
        if (!isset($ctx[$metaName]) || empty($ctx[$metaName])) {
            $ctx[$metaName] = $str;
        } else {
            $conn = is_string($conn) ? $conn : '';
            $ctx[$metaName] .= $conn . $str;
        }
        return $ctx[$metaName];
    }

    function clearNamedMeta($metaName, $ctx = null)
    {
        if (!is_array($ctx)) {
            $ctx = &$this->namedMeta;
        }
        $ctx[$metaName] = '';
        return $ctx[$metaName];
    }

    function addOtag($name, $content = false)
    {
        if (!is_string($name) || empty($name)) {
            return false;
        }
        return $this->addMetaProperty('og:' . $name, $content);
    }

    function addMetaProperty($name, $content = false)
    {
        if (!is_string($name) || empty($name)) {
            return false;
        }
        $this->metaProperties[$name] = $content;
        return true;
    }

    function getMetaProperty($key = null){
        return !isset($key)
            ? $this->metaProperties
            : (
            array_key_exists($key, $this->metaProperties)
                ? $this->metaProperties[$key]
                : null
            );
    }

    function addHeadTag($tagName, $attrs, $content = false)
    {
        if (!is_string($tagName) || empty($tagName)) {
            return false;
        }
        $tagData = array(
            'tag' => $tagName,
            'attrs' => $attrs,
        );
        if ($content !== false) {
            $tagData['content'] = $content;
        }
        $this->headTags[] = $tagData;

        return true;
    }

    function getHeadTags(){
        return $this->headTags;
    }

    /**
     * $a->addCss('file', 'path');
     *
     * $a->addCss(array('file'));
     * $a->addCss(array('file1 file2','path2'), array('file3 file4', 'path3-4'));
     *
     * $a->addCss(array(
     *    array('file','path'),
     *    array('file2 file4','path2'),
     *    array('file3 file4'),
     * ));
     *
     */
    function addInc($type)
    {

        if (!array_key_exists($type, $this->inccfg)) {
            return false;
        }

        $args = array_slice(func_get_args(), 1);

        if (!is_array($args) || !count($args)) {
            return false;
        }
        $files = array();

        $priority = end($args);
        if (is_int($priority)) {
            array_pop($args);
        } else {
            $priority = 0;
        }
        reset($args);

        $internal_priority = $this->getInternalPrority();

        // addCss('file', 'path');
        if (is_string($args[0])) {

            $files = array($args);

            // addCss(array('file','path'));
            // addCss(array(array('file0','file1'),'path'));
        } elseif (is_array($args[0]) && isset($args[0][0]) && is_string($args[0][0])) {

            $files = $args;

            // addCss(array(array('file','path')));
            // addCss(array(array(array('file0','file1'),'path')));
        } elseif (is_array($args[0][0]) && isset($args[0][0][0]) && is_string($args[0][0][0])) {

            $files = $args[0];

        }

        if (!count($files)) {
            return false;
        }

        $point = &$this->{$this->inccfg[$type]['prop']};
        if (!is_array($point)) {
            $point = array();
        }

        foreach ($files as $c_cfg) {
            if (!is_array($c_cfg) || !isset($c_cfg[0])) {
                continue;
            }

            if (is_string($c_cfg[0])) {
                $c_cfg[0] = explode(' ', $c_cfg[0]);
            }

            if (!is_array($c_cfg[0])) {
                continue;
            }
            $attrs = !isset($c_cfg[3]) ? false : (array)$c_cfg[3];
            $host = $subway = false;

            $subway = !isset($c_cfg[1]) || !is_string($c_cfg[1])
                ? $this->inccfg[$type]['loc']
                : ($c_cfg[1][0] == '/'
                    ? $c_cfg[1]
                    : $this->inccfg[$type]['loc'] . '/' . $c_cfg[1]);


            if (!isset($c_cfg[2]) || !is_string($c_cfg[2])) {
                $host = '';
            } else {
                if (mb_strpos($c_cfg[2], '//') !== false) {
                    $host = $c_cfg[2];
                } else {
                    $host = '//' . $c_cfg[2];
                }
            }

            $query = !isset($c_cfg[4]) ? false : (array)$c_cfg[4];

            $params = !isset($c_cfg[5]) ? false : (array)$c_cfg[5];


            foreach ($c_cfg[0] as $cfilename) {
                if (!$cfilename && is_string($attrs['url'])) {
                    $full_path = $attrs['url'];
                    unset($attrs['url']);
                } elseif (is_string($host) && is_string($subway)) {
                    $full_path = $host . $subway . '/' . $cfilename . $this->inccfg[$type]['ext'];
                } else {
                    continue;
                }
                $hash = md5($full_path);
                if (array_key_exists($hash, $point)) {
                    continue;
                }

                $point[$hash] = array(
                    'hash' => $hash,
                    'url' => $full_path,
                    'attrs' => $attrs,
                    'file' => $cfilename,
                    'query' => $query,
                    'params' => $params,
                    'priority' => (float)($priority . '.' . ($internal_priority->getNewValue($type, $priority))),
                );
            }
        }

        return true;
    }

    /**
     *$css_filenames, $subway = null, $host = null, $attrs = null, $query = null, $encode_query = null
     */
    function addCss()
    {
        $args = func_get_args();
        array_unshift($args, 'css');
        return call_user_func_array(array($this, 'addInc'), $args);
    }

    function setCss()
    {
        $args = func_get_args();
        array_unshift($args, 'css');
        return call_user_func_array(array($this, 'addInc'), $args);
    }

    function getCss(){
        return $this->css;
    }

    function getScripts(){
        return $this->scripts;
    }

    /**
     *$script_filenames, $subway = null, $host = null, $attrs = null, $query = null, $params = null, $params
     *
     * $params = array with optional flags: encode_query, addversion
     */
    function addScripts()
    {
        $args = func_get_args();
        array_unshift($args, 'js');
        return call_user_func_array(array($this, 'addInc'), $args);
    }

    function setScripts()
    {
        $args = func_get_args();
        array_unshift($args, 'js');
        return call_user_func_array(array($this, 'addInc'), $args);
    }

    function setJsBefore($val)
    {
        if (!is_string($val) || !is_array($val) || empty($val)) {
            return false;
        }
        if (is_string($val)) {
            $val = array($val);
        }
        foreach ($val as $k => $v) {
            $this->addJsBefore($v, (is_numeric($k) ? null : $k));
        }
        return true;
    }

    function addJsBefore($main_variable, $key = null)
    {
        if (is_string($key)) {
            $this->jsBefore[$key] = $main_variable;
        } else {
            $this->jsBefore[] = $main_variable;
        }
    }

    function setJsAfter($val)
    {
        if (!is_string($val) || !is_array($val) || empty($val)) {
            return false;
        }
        if (is_string($val)) {
            $val = array($val);
        }
        foreach ($val as $k => $v) {
            $this->addJsAfter($v, (is_numeric($k) ? null : $k));
        }
        return true;
    }

    function addJsAfter($main_variable, $key = null)
    {
        if (is_string($key)) {
            $this->jsAfter[$key] = $main_variable;
        } else {
            $this->jsAfter[] = $main_variable;
        }
    }

}