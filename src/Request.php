<?php
namespace Verba;

class Request
{
    public $uf = array();
    public $uf_str = array();
    public $iid;
    public $node;
    public $action;
    public $key;
    public $ot_id;
    public $ot_code;
    public $pot;

    public $params = array();
    public $tempData = array();

    protected $request_uri = null;

    protected $__post = null;

    function __construct($action = null, $iid = null, $ot_id = null, $key = null, $pot = null, $piid = null, $uf = null)
    {
        if (is_array($action)) {
            $rq = $action;
        } elseif (!is_array($action) && $action !== null) {
            $rq = array(
                'action' => $action,
                'iid' => $iid,
                'ot_id' => $ot_id,
                'key' => $key,
                'pot' => $pot,
                'piid' => $piid,
                'uf' => $uf,
            );
        } else {
            $rq = array();
        }

        $this->refresh($rq);
    }

    function refresh($rq = null)
    {
        if (!is_array($rq)) {
            $rq = $this->asArray();
        }

        //Url-fragments
        if (isset($rq['uf']) && is_array($rq['uf'])) {
            $this->uf = $rq['uf'];
            $this->uf_str = '/' . implode('/', $this->uf);
        } else {
            $url = $GLOBALS['SCRIPT_URI_PARSED'];
            if (isset($url['path'])
                && !empty($url['path'])
                && $url['path'] !== '/') {
                $this->uf_str = $url['path'];
                $this->uf = explode('/', substr($url['path'], 1));
            }
        }

        if (is_string($rq['request_uri']) && !empty($rq['request_uri'])) {
            $this->request_uri = $rq['request_uri'];
        } else {
            $this->request_uri = $this->uf_str;
        }

        //node
        if (isset($rq['node']) && !empty($rq['node'])) {
            $this->node = (string)$rq['node'];
        } elseif (isset($this->uf[0])) {
            $this->node = (string)$this->uf[0];
        } else {
            $this->node = '';
        }

        //action
        if (isset($rq['action']) && !empty($rq['action'])) {
            $this->action = strtolower($rq['action']);
        } elseif (isset($_REQUEST['action'])) {
            $this->action = strtolower($_REQUEST['action']);
        }

        //ot_id
        if (isset($rq['ot_id'])) {
            $otsome = $rq['ot_id'];
        } elseif (isset($rq['ot_code'])) {
            $otsome = $rq['ot_code'];
        } elseif (isset($rq['ot'])) {
            $otsome = $rq['ot'];
        } elseif (isset($_REQUEST['ot_id'])) {
            $otsome = $_REQUEST['ot_id'];
        } elseif (isset($_REQUEST['ot_code'])) {
            $otsome = $_REQUEST['ot_code'];
        } elseif (isset($_REQUEST['ot'])) {
            $otsome = $_REQUEST['ot'];
        }

        if (isset($otsome)) {
            $this->setOt($otsome);
        }
        if ($this->ot_id) {
            $oh = \Verba\_oh($this->ot_id);
        } else {
            $oh = false;
        }
        //key
        if (isset($rq['key']) && !empty($rq['key']) && !\Data\Boolean::isStrBool($rq['key'])) {
            $this->key = $rq['key'];
        } elseif (is_object($oh)) {
            $this->key = $oh->getBaseKey();
        } else {
            $this->key = false;
        }

        //iid
        $this->iid = $this->extractID($rq);

        //parent ot
        if (isset($rq['pot'])) {
            $pot = $rq['pot'];
        } elseif (isset($_REQUEST['pot'])) {
            $pot = $_REQUEST['pot'];
        }

        if (isset($pot)) {
            $this->pot = potToArray($pot,
                array_key_exists('piid', $rq)
                    ? $rq['piid']
                    : (isset($_REQUEST['piid'])
                    ? $_REQUEST['piid']
                    : null)
            );
        }


    }

    function setOt($otsome)
    {
        if (is_numeric($otsome = \Verba\isOt($otsome))) {
            $oh = \Verba\_oh($otsome);
            $this->ot_id = $oh->getID();
            $this->ot_code = $oh->getCode();
        } elseif (is_object($otsome) && $otsome instanceof \Verba\Model) {
            $this->ot_id = $otsome->getID();
            $this->ot_code = $otsome->getCode();
        } else {
            $this->ot_id = null;
            $this->ot_code = null;
        }
        return $this->ot_id;
    }

    function extractID($rq = null)
    {
        if (is_array($rq) && isset($rq['iid'])) {
            return $rq['iid'];
        } elseif (isset($_REQUEST['iid']) && !empty($_REQUEST['iid'])) {
            $iid = $_REQUEST['iid'];
        }

        return isset($iid)
        && (is_numeric($iid) && ($iid = intval($iid)) || is_string($iid))
        && $iid
        && !\Verba\Data\Boolean::isStrBool($_REQUEST['iid'])
            ? $iid
            : false;
    }

    function getIid()
    {
        return $this->iid;
    }

    function getId()
    {
        return $this->getIid();
    }

    function setIid($val)
    {
        return $this->setId($val);
    }

    function setId($val)
    {
        return ($this->iid = is_numeric($val) ? (int)$val : trim($val));
    }

    function asArray($extended = array())
    {
        $r = array(
            'action' => $this->action,
            'node' => $this->node,
            'iid' => $this->iid,
            'ot_id' => $this->ot_id,
            'ot_code' => $this->ot_code,
            'key' => $this->key,
            'pot' => $this->pot,
            'uf' => $this->uf,
        );

        if (!is_array($extended) || empty($extended)) {
            $extended = array();
        }
        $r = array_replace_recursive($this->params, $r, $extended);

        $r['request_uri'] = $this->request_uri;

        return $r;
    }

    function addParam($params = array())
    {
        $this->addParams($params);
    }

    function addParams($params = array())
    {
        $params = (array)$params;
        $this->params = array_replace_recursive($this->params, $params);
    }

    /**
     * put your comment there...
     *
     * @param mixed $var
     * @param mixed $global
     */
    function getParam($var = null, $global = true)
    {
        $global = (bool)$global;

        if ($var === null || $var === false) {
            return $global ? $this->params + $_REQUEST : $this->params;
        }
        $var = (string)$var;
        if (!isset($this->params[$var])) {
            if ($global && array_key_exists($var, $_REQUEST)) {
                return $_REQUEST[$var];
            }
        } else {
            return $this->params[$var];
        }

        return null;
    }

    /**
     * Alias to getParam
     */
    function getParams()
    {
        return call_user_func_array(array($this, 'getParam'), func_get_args());
    }

    function clearParams()
    {
        $this->params = array();
    }

    function addTempData($data = array())
    {
        $this->tempData = array_replace_recursive($this->tempData, (array)$data);
    }

    function getTempData($key = false)
    {
        $key = $key !== false ? (string)$key : $key;
        return $key === false
            ? $this->tempData
            : (
            array_key_exists($key, $this->tempData)
                ? $this->tempData[$key]
                : null
            );
    }

    function getParents()
    {
        if (!is_array($this->pot)) {
            $this->pot = arrray();
        }
        return $this->pot;
    }

    function addParent($pot, $piid)
    {

        $_oh = \Verba\_oh($pot);
        if (!(is_string($piid) || is_numeric($piid)) || empty($piid)) {
            return false;
        }

        $this->getParents();
        if (!array_key_exists($pot, $this->pot)
            || !is_array($this->pot[$pot])) {
            $this->pot[$pot] = array();
        }
        $this->pot[$pot][$piid] = $piid;
    }

    function setParent($pot, $piid)
    {
        $this->pot = [];
        $this->addParent($pot,$piid);
    }

    /**
     *
     * @return array(pot, piid);
     */
    function getFirstParent()
    {
        if (!count($this->pot)) {
            return array(false, false);
        }
        reset($this->pot);
        $pot = key($this->pot);

        return is_array($this->pot[$pot]) && !empty($this->pot[$pot])
            ? array($pot, current($this->pot[$pot]))
            : array(false, false);
    }

    function shifted($offset = 1)
    {
        $offset = (int)$offset;
        if ($offset < 1) {
            $offset = 1;
        }

        $cfg = $this->asArray();
        $cfg['node'] = null;
        if (count($cfg['uf'])) {
            $cfg['uf'] = array_slice($cfg['uf'], $offset);
        }
        return new Request($cfg);
    }

    function shift($offset = 1)
    {
        return $this->shifted($offset);
    }

    function shiftAndClone($offset = 1)
    {
        return $this->shifted($offset);
    }

    function getRequestUri()
    {
        return $this->request_uri;
    }

    function post($key = null)
    {
        if(isset($_POST) && is_array($_POST) && count($_POST)){
            $this->__post = &$_POST;
        }elseif(is_array($post = json_decode(file_get_contents("php://input"), true))){
            $this->__post = $post;
        }

        if($key === null){
            return $this->__post;
        }

        return array_key_exists($key, $this->__post) ? $this->__post[$key] : null;
    }
}
