<?php
namespace Verba\Mod\Acp;

class Tab extends \Verba\Configurable
{
    public $name = false;
    public $viewName = '';
    public $button = array(
        'title' => ''
    );
    public $title;
    public $ot = null;
    public $otId = null;
    public $iid;
    public $url = false;
    public $pot = false;
    public $piid;
    public $potId;
    public $action;
    public $linkedTo = array('type' => null);
    public $instanceOf = array('type' => null, 'id' => null);
    public $states = array();

    function __construct($cfg = null)
    {
        if (!is_array($cfg)) {
            $cfg = array();
        }

        $reflection = new \ReflectionClass($this);
        $thisClassName = $reflection->getShortName();
        $a = explode('_', $thisClassName);
        $this->name = array_pop($a);

        if (array_key_exists('states', $cfg)) {
            $statesFromCfg = $cfg['states'];
            unset($cfg['states']);
        } else {
            $statesFromCfg = false;
        }

        if (count($cfg)) {
            $this->applyConfigDirect($cfg);
        }
        $title = false;
        if (isset($this->button['title']) && !empty($this->button['title'])) {
            $val = (string)$this->button['title'];
            $title = \Verba\Lang::get($val);
            if ($title === null) {
                $title = $val;
            }
        }
        if (!$title && is_string($this->title)) {
            $title = $this->title;
        }

        $this->button['title'] = !is_string($title) ? '???' : $title;


        if ($this->ot) {
            $this->otId = \Verba\_oh($this->ot)->getId();
        }

        if ($this->pot) {
            $this->pot = \Verba\potToArray($this->pot, $this->piid);
        }

        // states
        $states = $this->states();
        if (is_array($states)) {
            if (is_array($statesFromCfg)) {
                $states = array_replace_recursive($states, $statesFromCfg);
            }
            if (count($states)) {
                foreach ($states as $state => $stCfg) {
                    $this->addState($state, $stCfg);
                }
            }
        }
    }

    function setTitle($val)
    {
        $this->title = $val;
    }

    function addState($state, $cfg)
    {
        if ($cfg['type'] == 'tabset') {
            $tabsetName = $cfg['name'];
            if ($cfg['cfg']) {
                $tsCfg = $cfg['cfg'];
                unset($cfg['cfg']);
            } else {
                $tsCfg = null;
            }
            $Ts = \Verba\Mod\Acp\Tabset::createTabsetByName($tabsetName, $tsCfg);
            if (!is_object($Ts)) {
                return false;
            }
            $this->states[$state] = $Ts;
        }
    }

    function states()
    {
        return array();
    }
}
