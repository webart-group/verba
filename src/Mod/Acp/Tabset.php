<?php

namespace Verba\Mod\Acp;

class Tabset extends \Verba\Configurable
{

    public $tabs = array();
    public $tabsAdded = 0;
    public $name;
    protected $node;

    function __construct($cfg = null, $node = false)
    {
        if (!is_array($cfg)) {
            $cfg = array();
        }
        if ($node instanceof Node) {
            $this->node = $node;
        }
        $cn = \explode('_', get_class($this));
        $this->name = \array_pop($cn);

        // tabs
        if (array_key_exists('tabs', $cfg)) {
            $tabsCfg = $cfg['tabs'];
            unset($cfg['tabs']);
        }

        if (count($cfg)) {
            $this->applyConfigDirect($cfg);
        }

        $tabs = $this->tabs();
        if (!is_array($tabs)) {
            return;
        }
        $tabs = \Verba\Configurable::substNumIdxAsStringValues($tabs);
        if (!is_array($tabsCfg)) {
            $tabsCfg = array();
        } else {
            $tabsCfg = \Verba\Configurable::substNumIdxAsStringValues($tabsCfg);
        }

        $tabs = array_replace_recursive($tabs, $tabsCfg);

        foreach ($tabs as $tKey => $tCfg) {
            if ($tCfg === false) {
                continue;
            }
            $tName = $tAlias = false;
            if (is_array($tCfg)) {
                if (isset($tCfg['class'])) {
                    $tName = $tCfg['class'];
                    unset($tCfg['class']);
                }
                if (isset($tCfg['alias'])) {
                    $tAlias = $tCfg['alias'];
                    unset($tCfg['alias']);
                }
            }
            if (!$tName) {
                if (is_string($tCfg)) {
                    $tName = $tCfg;
                } elseif (!is_numeric($tKey)) {
                    $tName = $tKey;
                }
            }

            if (!$tAlias) {
                if (!is_numeric($tKey)) {
                    $tAlias = $tKey;
                } else {
                    $tAlias = $tName;
                }
            }
            if (!$tName || !$tAlias) {
                continue;
            }

            $this->addTab($tAlias, $tName, $tCfg);
        }

    }

    function addTab($tAlias, $tName, $cfg = null)
    {
        if($tName == 'List'){
            $tName = 'Tab'.$tName;
        }
        $basePath = '\Verba\Mod\Acp\Tab';
        $paths = [
            $basePath,
            $basePath.'\TabList',
            $basePath.'\Form',
        ];
        $tClassName = $tName;
        if (!class_exists($tClassName)) {
            $tClassName = false;
            foreach ($paths as $tabsPath) {
                if(class_exists($tabsPath.'\\' . ucfirst($tName))) {
                    $tClassName = $tabsPath.'\\' . ucfirst($tName);
                    break;
                }
            }
            if (!$tClassName) {
                $tClassName = Tab::class;
            }
        }
        $this->tabs[$tAlias] = new $tClassName($cfg);
        $this->tabsAdded++;
    }

    function tabs()
    {
        return [];
    }

    static function createTabsetByName($tsClassName, $cfg, $node = false)
    {
        if (!class_exists($tsClassName)) {
            if (!class_exists($tsClassName = '\Verba\Mod\Acp\Tabset\\'.$tsClassName)) {
                $tsClassName = Tabset::class;
            }
        }

        return new $tsClassName($cfg, $node);
    }
}
