<?php
namespace Verba\Mod\Acp;

class Node extends \Verba\Configurable
{
    public $gid;
    public $id;
    public $gidx;
    public $idx;
    public $ot;
    public $iid;
    public $acpNodeType;
    public $level = 0;
    public $titleLangKey = '';
    public $title;
    public $titleSubstField;
    public $prefix;
    public $inheritPrefix = true;
    public $items = null;
    public $itemsConf = array(
        'ot' => null,
        'pot' => null,
        'piid' => null,
        'order' => null,
    );
    public $hasItems = false;
    public $_itemsConfByLevel = array();
    public $_itemsConfByNode = array();
    public $tabsets = array();
    public $menu = array();
    public $objects = array();
    /**
     * @var Node
     */
    public $parent;
    public $_propsToExport = array('id', 'gid', 'idx', 'gidx', 'iid', 'ot', 'acpNodeType', 'title', 'level', 'tabsets', 'objects', 'hasItems', 'menu', 'itemData');
    public $itemData = array();

    function __construct($parent = null, $id = null, $cfg = null)
    {
        $this->parent = $parent;
        $this->id = (string)$id;
        $this->gid = is_object($parent) && !empty($parent->gid) ? $parent->gid . '.' . $this->id : $this->id;

        $this->idx = is_numeric($id) ? 'i' . $id : $id;
        $this->gidx = is_object($parent) && !empty($parent->gidx) ? $parent->gidx . '.' . $this->idx : $this->idx;

        if (!is_array($cfg)) {
            $cfg = array();
        }
        if (is_object($this->parent) && $this->parent instanceof Node) {
            $this->level = $this->parent->level + 1;
            $this->tabsets = $this->parent->tabsets;
            if (count($cfg)) {
                if (isset($cfg['itemsConf']) && is_array($cfg['itemsConf'])) {
                    $cfg['itemsConf'] = array_replace_recursive($this->parent->itemsConf, $cfg['itemsConf']);
                } else {
                    $cfg['itemsConf'] = $this->parent->itemsConf;
                }
                $this->itemsConf['piid'] = null;
            }
            // inherit items config from parent
            $this->_itemsConfByLevel = &$this->parent->_itemsConfByLevel;
            $this->_itemsConfByNode = &$this->parent->_itemsConfByNode;
        }

        if (array_key_exists('tabsets', $cfg)) {
            $tabsetsCfg = $cfg['tabsets'];
            unset($cfg['tabsets']);
        } else {
            $tabsetsCfg = false;
        }

        if (array_key_exists('menu', $cfg)) {
            $menuCfg = $cfg['menu'];
            unset($cfg['menu']);
        }

        if (count($cfg)) {
            if (array_key_exists('itemsConf', $cfg)) {
                $icfg = array('itemsConf' => $cfg['itemsConf']);
                unset($cfg['itemsConf']);
            }

            if (isset($cfg['ot']) && !empty($cfg['ot'])) {
                $ot = $cfg['ot'];
            } elseif (!empty($this->ot)) {
                $ot = $this->ot;
            }

            if (isset($cfg['iid']) && !empty($cfg['iid'])) {
                $iid = $cfg['iid'];
            } elseif (!empty($this->iid)) {
                $iid = $this->iid;
            }

            if (!array_key_exists('__db_loaded', $cfg)
                && isset($ot)
                && isset($iid)
                && is_array($nodeItemData = $this->loadSelfData($ot, $iid))) {
                $cfg = array_replace_recursive($nodeItemData, $cfg);
            }

            $this->applyConfigDirect($cfg);
            if (isset($icfg)) {
                $this->applyConfigDirect($icfg);
            }
            $this->copyItemData($cfg);
        }

        if (!is_string($this->title)) {
            if ($this->titleLangKey && is_string($this->title = \Verba\Lang::get($this->titleLangKey))) {
            } elseif (is_string($this->titleSubstField) && isset($cfg[$this->titleSubstField])) {
                $this->title = (string)$cfg[$this->titleSubstField];
            } else {
                $this->title = 'Missing Node Name';
            }
        }
        if ($this->ot) {
            $this->ot = \Verba\_oh($this->ot)->getCode();
        }
        // tabsets
        $tabsets = $this->tabsets();
        if (!is_array($tabsets) || !count($tabsets) && is_array($tabsetsCfg) && count($tabsetsCfg)) {
            $tabsets = $tabsetsCfg;
            $tabsetsCfg = false;
        }
        if (is_array($tabsets) && count($tabsets)) {
            foreach ($tabsets as $tsAction => $tsCfg) {
                $tsClassName = false;
                if (is_string($tsCfg)) {
                    $tsCfg = array('class' => $tsCfg);
                }

                if (is_array($tabsetsCfg) && array_key_exists($tsAction, $tabsetsCfg)) {
                    //tabsetClass redefined as string value
                    if (is_string($tabsetsCfg[$tsAction])) {
                        $tabsetsCfg[$tsAction] = array('class' => $tabsetsCfg[$tsAction]);
                    }
                    if ($tabsetsCfg[$tsAction] === false || $tabsetsCfg[$tsAction] === null) {
                        $tsCfg = false;
                    } elseif (!is_array($tsCfg) && is_array($tabsetsCfg[$tsAction])) {
                        $tsCfg = $tabsetsCfg[$tsAction];
                    } elseif (is_array($tsCfg) && is_array($tabsetsCfg[$tsAction])) {
                        $tsCfg = array_replace_recursive($tsCfg, $tabsetsCfg[$tsAction]);
                    }
                }

                if ($tsCfg === false || $tsCfg === null) {
                    continue;
                }

                if (isset($tsCfg['class'])) {
                    $tsClassName = $tsCfg['class'];
                    unset($tsCfg['class']);
                }

                $this->addTabset($tsAction, $tsClassName, (is_array($tsCfg) && !empty($tsCfg) ? $tsCfg : null));
            }
        }
        // menu
        $menu = $this->menu();
        if (is_array($menu)) {
            if (is_array($menuCfg)) {
                $menu = array_replace_recursive($menu, $menuCfg);
            }
            foreach ($menu as $mAction => $mCfg) {
                $this->addMenuItem($mAction, $mCfg);
            }
        }
    }

    function loadSelfData($ot, $iid)
    {
        $_oh = \Verba\_oh($ot);
        return $_oh->getData($iid, 1);
    }

    function copyItemData($cfg)
    {
        if (!is_array($cfg)
            || empty($cfg)
            || !is_array($this->itemData)
            || empty($this->itemData)) {
            return;
        }
        $this->itemData = \Verba\Configurable::substNumIdxAsStringValues($this->itemData, null);
        foreach ($this->itemData as $k => $v) {
            if (!array_key_exists($k, $cfg)) {
                $this->itemData[$k] = null;
                continue;
            }
            $this->itemData[$k] = $cfg[$k];
        }
        return $this->itemData;
    }

    function setOt($val)
    {
        if (!isset($val)) {
            return false;
        }
        $this->ot = \Verba\_oh($val)->getCode();
    }

    function setPot($val)
    {
        if (!isset($val)) {
            return false;
        }
        $this->pot = \Verba\_oh($val)->getCode();
    }

    function setPiid($val)
    {
        if (!isset($val)) {
            return false;
        }
        $this->piid = $val;
    }

    function setIid($val)
    {
        if (!isset($val)) {
            return false;
        }
        $this->iid = $val;
    }

    function getIid()
    {
        if (is_numeric($this->iid) || is_string($this->iid)) {
            return $this->iid;
        } elseif (is_numeric($this->id) || is_string($this->id)) {
            return $this->id;
        }
        return false;

    }

    function addItem($itemId, $itemCfg = null)
    {
        if (!is_array($this->items)) {
            $this->items = array();
        }
        $itemId = (string)array_pop(explode('.', (string)$itemId));
        $itemGId = !empty($this->gid) ? $this->gid . '.' . $itemId : $itemId;
        if (!is_array($itemCfg)) {
            $itemCfg = $this->itemsConf;
        } else {
            $itemCfg = array_replace_recursive($this->itemsConf, $itemCfg);
        }
        $itemLvl = $this->level + 1;
        // in byLevel
        if (isset($this->_itemsConfByLevel[$itemLvl]) && is_array($this->_itemsConfByLevel[$itemLvl])) {
            $itemCfg = array_replace_recursive($itemCfg, $this->_itemsConfByLevel[$itemLvl]);
        }
        // in byNode
        if (isset($this->_itemsConfByNode[$itemGId]) && is_array($this->_itemsConfByNode[$itemGId])) {
            $itemCfg = array_replace_recursive($itemCfg, $this->_itemsConfByNode[$itemGId]);
        }

        //AcpNoedType
        if (isset($itemCfg['acpnodetype']) || isset($itemCfg['acpNodeType'])) {
            $acpNodeType = isset($itemCfg['acpnodetype']) ? $itemCfg['acpnodetype'] : $itemCfg['acpNodeType'];
        }// in current itemsConf
        elseif (isset($this->itemsConf['acpNodeType'])) {
            $acpNodeType = $this->itemsConf['acpNodeType'];
        } else {// inherit current node type
            $acpNodeType = $this->acpNodeType;
        }

        if (!isset($acpNodeType) || !class_exists(($nodeClass = '\Verba\Mod\Acp\Node\\' . strtolower($acpNodeType)))) {
            $nodeClass = '\Verba\Mod\Acp\Node';
        }

        $this->items[$itemId] = new $nodeClass($this, $itemId, $itemCfg);
    }

    function setItems($val)
    {
        if (!is_array($val)) return false;
        foreach ($val as $itemId => $itemCfg) {
            $this->addItem($itemId, $itemCfg);
        }
    }

    function getItems()
    {
        if ($this->items === null) {
            $this->items = array();
            $this->loadItems();
        }
        return $this->items;
    }

    function setItemsConf($val)
    {
        if (isset($val['byLevel'])) {
            unset($this->_itemsConfByLevel);
            $this->_itemsConfByLevel = $val['byLevel'];
            unset($val['byLevel']);
        }
        if (isset($val['byNode'])) {
            unset($this->_itemsConfByNode);
            $this->_itemsConfByNode = $val['byNode'];
            unset($val['byNode']);
        }
        // now node have parent itemsConf
        // inherit items config from byLevel key if exists
        // apply $val conf
        $this->itemsConf = array_replace_recursive($this->itemsConf, $val);
        if (isset($this->itemsConf['pot'])) {
            $this->itemsConf['piid'] = $this->iid ? $this->iid : $this->id;
        }
        // apply byLevel conf if exists
        if (array_key_exists($this->level, $this->_itemsConfByLevel)) {
            $this->itemsConf = array_replace_recursive($this->itemsConf, $this->_itemsConfByLevel[$this->level]);
        }
        // apply byNode conf if exists
        if (array_key_exists($this->gid, $this->_itemsConfByNode)) {
            $this->itemsConf = array_replace_recursive($this->itemsConf, $this->_itemsConfByNode[$this->gid]);
        }
    }

    function loadItems()
    {
        if (!$this->itemsConf['ot']) {
            return false;
        }
        $oh = \Verba\_oh($this->itemsConf['ot']);
        $qm = new \Verba\QueryMaker($oh->getID(), false, true);
        list($alias, $table) = $qm->createAlias();

        if (isset($this->itemsConf['pot'])) {
            $pot = \Verba\_oh($this->itemsConf['pot']);
        } elseif ($this->ot) {
            $pot = \Verba\_oh($this->ot);
        }

        if (isset($pot)) {
            if (!$oh->inFamily($pot->getID())) {
                return false;
            }
            if ($this->itemsConf['piid']) {
                $piid = $this->itemsConf['piid'];
            } elseif ($this->iid) {
                $piid = $this->iid;
            } else {
                $piid = $this->id;
            }

            list($linkAlias, $linktable) = $qm->createAlias($oh->vltT($pot->getID()));
            $qc = $qm->addConditionByLinkedOT($pot->getID(), $piid);

            if ($oh->getID() == $pot->getID()) {
                $qc->setRelation(2);
            }
        }
        $defOrder = array();
        if ($oh->isA('priority')) {
            $defOrder['priority'] = 'd';
        }
        $defOrder[$oh->getPAC()] = 'd';

        $order = is_array($this->itemsConf['order'])
            ? $this->itemsConf['order']
            : $defOrder;
        $qm->addOrder($order);
        $qm->addGroupBy($oh->getPAC());
        $q = $qm->getQuery();
        $sqlr = $this->DB()->query($q);
        if (!is_object($sqlr) || $sqlr->getNumRows() == 0) {
            return false;
        }
        $nodes = array();
        while ($row = $sqlr->fetchRow()) {
            $iid = $row[$oh->getPAC()];
            $nodes[$iid] = $row;
            $nodes[$iid]['__db_loaded'] = true;
        }
        $branch = \Verba\Branch::get_branch(array($oh->getID() => array('iids' => array_keys($nodes), 'aot' => $oh->getID())));
        if (is_array($branch['pare'][$oh->getID()]) && count($branch['pare'][$oh->getID()])) {
            foreach ($branch['pare'][$oh->getID()] as $key => $value) {
                if (!is_array($nodes[$key])) {
                    continue;
                }
                $nodes[$key]['objects'] = $value;
            }
        }

        foreach ($nodes as $itemId => $itemData) {
            $this->addItem($itemId, $itemData);
        }
        return $nodes;
    }

    function getItem($nodeId)
    {
        $items = $this->getItems();
        if (!is_array($items) || !array_key_exists($nodeId, $items)) {
            return false;
        }
        return $items[$nodeId];
    }

    function setTitle($val)
    {
        $this->title = (string)$val;
    }

    function setAcpNodeType($val)
    {
        $this->acpNodeType = (string)$val;
    }

    function setLevel($val)
    {
        $this->level = (int)$val;
    }

    function addTabset($action, $tabsetName, $cfg = null)
    {
        $Ts = \Verba\Mod\Acp\Tabset::createTabsetByName($tabsetName, $cfg, $this);
        if (!is_string($action) || !is_object($Ts)) {
            return false;
        }

        $this->tabsets[$action] = $Ts;
        return true;
    }

    function tabsets()
    {
        return array();
    }

    function menu()
    {
        return array();
    }

    function addMenuItem($action, $cfg)
    {
        if (!is_array($cfg)) {
            return false;
        }
        if ($cfg['type'] == 'tabset') {
            $tabsetName = $cfg['name'];
            if ($cfg['cfg']) {
                $tsCfg = $cfg['cfg'];
                unset($cfg['cfg']);
            } else {
                $tsCfg = null;
            }
            if (!$this->addTabset($action, $tabsetName, $tsCfg)) {
                return false;
            }
        }
        $this->menu[$action] = $cfg;
    }

    function exportAsCfg()
    {
        $r = array();
        foreach ($this->_propsToExport as $prop) {
            if (!property_exists($this, $prop)) {
                continue;
            }
            $r[$prop] = $this->$prop;
        }

        if (is_array($this->items)) {
            foreach ($this->items as $itemId => $Item) {
                $r['items'][$Item->idx] = $Item->exportAsCfg();
            }
        }
        return $r;
    }
}

?>