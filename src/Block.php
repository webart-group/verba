<?php
namespace Verba;

class Block extends Configurable implements BlockInterface
{

    public $contentType = 'text';
    public $content;

    /**
     * @var Request
     */
    public $request;
    /**
     * short link to $this->request property
     *
     * @var Request
     */
    public $rq;

    public $items = array();
    public $role;

    public $mute;
    /**
     * @var \Verba\Model
     */
    public $oh;

    /**
     * @var Block
     */
    protected $_parent;
    protected $_invoker;
    /**
     * @var string
     */
    protected $_cache_alias;
    private $_cache_dir = '';
    /**
     * @var \Verba\Cache
     */
    protected $_cache;
    public static $roleRegistry = [];

    const EV_PREPARE_ITEMS_BEFORE = 'prepare_items_before';
    const EV_PREPARE_ITEMS_AFTER = 'prepare_items_after';
    const EV_PREPARE_BEFORE = 'prepare_before';
    const EV_PREPARE_AFTER = 'prepare_after';

    const EV_BUILD_ITEMS_BEFORE = 'build_items_before';
    const EV_BUILD_ITEMS_AFTER = 'build_items_after';
    const EV_BUILD_BEFORE = 'build_before';
    const EV_BUILD_AFTER = 'build_after';

    function __construct($rq = null, $cfg = null)
    {
        $this->initConfigurator(false, false, false);

        $this->rq = &$this->request;

        if (is_object($rq)) {

            if ($rq instanceof BlockInterface) {
                $this->_invoker =
                $this->_parent = $rq;
                if ($this->_invoker->getRequest() instanceof \Verba\Request) {
                    $this->request = $this->_invoker->getRequest();
                } else {
                    $this->request = new \Verba\Request($this->_invoker->getRequest());
                }
            } elseif ($rq instanceof \Verba\Request) {
                $this->request = $rq;
            }
            $this->request->refresh();

        } elseif (is_array($rq)) {

            $this->request = new Request($rq);

        } else {

            $this->request = new Request();

        }
        $this->handleDefaultValueProps();
        $this->applyConfigDirect($cfg);

        $this->init();
    }

    function getGuard(){
        if(!is_object($this->guard)){
            $this->guard = new Guard($this->guard);
        }
    }

    function getRequest(){
        return $this->rq;
    }

    function handleDefaultValueProps()
    {
        $pubProps = \Verba\Hive::getObjectVars($this);
        foreach ($pubProps as $propName => $val) {
            if (empty($val)
                || !is_callable(array($this, ($mtd = 'set' . ucfirst($propName))))) {
                continue;
            }
            $this->$propName = null;
            $this->$mtd($val);
        }
    }

    function cache()
    {
        if ($this->_cache === null) {
            if (!is_string($this->_cache_alias) || empty($this->_cache_alias)) {
                $this->_cache = false;
            } else {
                $this->_cache = new \Verba\Cache($this->makeCachePath());
            }

        }
        return $this->_cache;
    }

    function makeCachePath()
    {
        return $this->_cache_dir . '/' . $this->_cache_alias . ".cache";
    }

    function removeItem($k)
    {
        if (isset($this->items[$k])) {
            unset($this->items[$k]);
        }
    }

    function init()
    {

    }

    function setContent($var)
    {
        $this->content = $var;
    }

    function getContent()
    {
        return $this->content;
    }

    function route()
    {
        return $this;
    }

    function run()
    {
        $this->prepareItems();
        $this->prepare();
        $this->buildItems();
        $this->build();
        return $this->content;
    }

    function prepare()
    {

    }

    function prepareItems()
    {
        if (!count($this->items)) {
            return;
        }
        foreach ($this->items as $item) {

            if (!$item instanceof BlockInterface) {
                continue;
            }
            $item->fire(self::EV_PREPARE_ITEMS_BEFORE);
            $item->prepareItems();
            $item->fire(self::EV_PREPARE_ITEMS_AFTER);

            $item->fire(self::EV_PREPARE_BEFORE);
            $item->prepare();
            $item->fire(self::EV_PREPARE_AFTER);
        }
    }

    /**
     * @return string
     */
    function build()
    {
        return $this->content;
    }

    function buildItems()
    {

        if (!count($this->items)) {
            return;
        }

        foreach ($this->items as $item) {
            $item->fire(self::EV_BUILD_BEFORE);
            $item->build();
            $item->fire(self::EV_BUILD_AFTER);
        }

    }

    function output()
    {
        echo $this->content;
    }

    function implodeItemsContent($sep = '')
    {
        $r = isset($this->content) ? (string)$this->content : '';

        if (!count($this->items)) {
            return $r;
        }

        foreach ($this->items as $item) {
            $itemType = gettype($item);
            if ($itemType == 'object' && $item instanceof BlockInterface) {
                if (!isset($item->content) || empty($item->content)) {
                    continue;
                }
                $c = $item->content;
            } elseif ($itemType == 'string') {
                $c = $item;
            } elseif ($itemType == 'function') {
                $c = $item($this);
            } else {
                continue;
            }

            $r = strlen($r) ? $r . $sep . ($c) : $c;
        }
        return (string)$r;
    }

    function parent($role = null)
    {
        if (!$role
            || $this->_parent->role == $role) {
            return $this->_parent;
        }

        return $this->_parent instanceof Block
            ? $this->_parent->parent($role)
            : null;
    }

    function getParent($role = null)
    {
        return $this->parent($role);
    }

    /**
     * @param $role
     * @return \Verba\Block\Html|null
     */
    function getBlockByRole($role)
    {
        if (isset(self::$roleRegistry[$role]) && !empty(self::$roleRegistry[$role])) {
            return self::$roleRegistry[$role];
        }
        return null;
    }

    function setRole($role)
    {
        if (!is_string($role) || empty($role)) {
            return false;
        }
        $this->unregisterRole($this->role);
        $this->role = $role;
        $this->registerRole($this->role);

        return $this->role;
    }

    function getRole()
    {
        return $this->role;
    }

    function registerRole($role)
    {
        self::$roleRegistry[$this->role] = $this;
    }

    function unregisterRole($role)
    {
        if (is_string($role) && isset(self::$roleRegistry[$role])) {
            unset(self::$roleRegistry[$role]);
        }
    }

    function getItem($key = null)
    {
        if (!is_array($this->items) || !count($this->items)) {
            return null;
        }
        reset($this->items);

        return $key !== null
            ? (array_key_exists($key, $this->items)
                ? $this->items[$key]
                : null)
            : current($this->items);
    }

    function getItems()
    {
        return $this->items;
    }

    function setItems($var)
    {
        $this->items = is_array($var) ? $var : [$var];

        return $this->items;
    }

    function addItems($items)
    {
        if (!is_array($items)) {
            $items = [ $items ];
        }
        $added = [];
        foreach ($items as $k => $v) {
            if ($v === false
                && array_key_exists($k, $this->items)) {
                unset($this->items[$k]);
                continue;
            }
            if (is_numeric($k)) {
                $this->items[] = $v;
            } elseif (is_string($k)) {
                $this->items[$k] = $v;
            }

            if ($v instanceof BlockInterface) {
                $v->_parent = $this;
            }
            $added[] = $v;
        }

        return $added;
    }

    function setMute($val)
    {
        $this->mute = (bool)$val;
    }

    function mute()
    {
        $this->mute = true;
    }

    function unmute()
    {
        $this->mute = false;
    }

    function isMuted()
    {
        return $this->mute === true;
    }

    function oh()
    {
        return $this->getOh();
    }

    function getOh()
    {
        if ($this->oh === null) {
            $this->oh = false;
            if ($this->rq->ot_id || $this->rq->ot_code) {
                $this->oh = \Verba\_oh($this->rq->ot_id ? $this->rq->ot_id : $this->rq->ot_code);
            }
        }

        return $this->oh;
    }
}
