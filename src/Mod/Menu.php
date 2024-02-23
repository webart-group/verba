<?php

namespace Verba\Mod;

class Menu extends \Verba\Mod
{
    const MAIN_MENU_ROOT_ID = 1;

    use \Verba\ModInstance;
    protected $currentItem;
    protected $currentItemParents = array();
    protected $isChainLoaded = null;

    protected $urlFragments = null;

    public $chain_nodes = null;

    function addMenuChain()
    {
        if (func_num_args() < 1) {
            return;
        }
        for ($i = 0; $i < func_num_args(); $i++) {
            $this->chain_nodes[] = func_get_arg($i);
        }
    }

    function setUrlFragments(array $urlFragments)
    {
        $this->urlFragments = $urlFragments;
    }

    function setChainIsLoaded(bool $val)
    {
        $this->isChainLoaded = $val;
    }

    function getChain()
    {
        if ($this->isChainLoaded === null) {
            $this->loadChainData();
        }
        return $this->chain_nodes;
    }

    protected function loadChainData()
    {
        $this->isChainLoaded = true;
        if (!is_array($this->chain_nodes)) {
            $this->chain_nodes = array();
        }
        array_unshift($this->chain_nodes, $this->getActiveNode());
        $menu_parents = $this->getMenuParents();

        if (!is_array($menu_parents)) {
            return $this->chain_nodes;
        }
        // add current chain to parents chain
        $this->chain_nodes = array_merge($menu_parents, $this->chain_nodes);

        return $this->chain_nodes;
    }

    function getMenuItems($iids, $byParent = false)
    {

        if (!\Verba\reductionToArray($iids) || !reset($iids)) return false;

        $byParent = (bool)$byParent;
        $oh = \Verba\_oh('menu');

        $qm = new \Verba\QueryMaker($oh->getID(), false, false);
        $qm->createAlias($oh->vltT(), $oh->vltDB());
        list($a_path, $t_path) = $qm->createAlias('_path_domains', SYS_DATABASE);
        $qm->addSelect('title');
        $qm->addSelect('url');
        $qm->addSelect("CONCAT_WS(IF(CHAR_LENGTH(`$a_path`.`cname`) > 0, '." . SYS_PRIMARY_HOST . "', '" . SYS_PRIMARY_HOST . "'), `$a_path`.`cname`, `" . $qm->getAlias() . "`.`url`)", '', 'full_url', true);
        $qm->addSelect('domain');
        $qm->addSelect('alt');

        if ($byParent) {
            $parentId = current($iids);
            $la = $qm->createAlias($oh->vltT($oh->getID()));
            $qm->addCJoin(array(array('a' => $la)),
                array(array('p' => array('t' => $la, 'f' => 'ch_iid'),
                    's' => array('t' => $qm->getAlias(), 'f' => 'id'),
                )));

            $qm->addOrder(array('priority' => 'd'));
            $qm->addWhere("`" . $la . "`.`p_ot_id`='" . $oh->getID() . "'");
            $qm->addWhere("`" . $la . "`.`ch_ot_id`='" . $oh->getID() . "'");
            $qm->addWhere("`" . $la . "`.`p_iid`='" . $parentId . "'");
        } else {
            $qm->addWhere($this->DB()->makeWhereStatement($iids, $oh->getPAC(), $qm->getAlias()));
        }
        $qm->addCJoin(array(array('a' => $a_path)),
            array(array('p' => array('t' => $t_path, 'f' => 'id'),
                's' => array('t' => $qm->getTable(), 'f' => 'domain'),
            )), false, 'RIGHT');

        $qm->setQuery();

        return $this->DB()->query($qm->getQuery());
    }

    function getMenuItemsByMixedArray($iids, $byParent = false)
    {
        $byParent = (bool)$byParent;

        if (!\Verba\reductionToArray($iids))
            return false;

        $result = !$byParent ? $iids : array();

        if (is_object($oRes = $this->getMenuItems($iids, $byParent)) && $oRes->getNumRows() > 0) {
            $_menu_PAC = \Verba\_oh('menu')->getPAC();
            while ($row = $oRes->fetchRow()) {
                $result[$row[$_menu_PAC]] = $row;
            }
        }

        return $result;
    }

    function getActiveNode($returnAsArray = true, $frash = false)
    {
        $returnAsArray = (bool)$returnAsArray;
        $frash = (bool)$frash;
        if ($this->currentItem === null || $frash) {
            $this->findAndLoadCurrentItem();
        }
        if (!$this->currentItem) {
            return $this->currentItem;
        }
        $_menu = \Verba\_oh('menu');
        return $returnAsArray ? $this->currentItem : $this->currentItem[$_menu->getPAC()];
    }

    function findAndLoadCurrentItem($urlFragmentsNum = false)
    {

        $this->currentItem = false;
        $this->currentItemParents = [];
        $_menu = \Verba\_oh('menu');

        if(!is_array($this->urlFragments)) {
            global $response;
            $this->urlFragments = $response->request->uf;
        }

        $urlFragmentsNum = (int)$urlFragmentsNum;
        $url = $urlFragmentsNum > 0
            ? array_slice($this->urlFragments, 0, $urlFragmentsNum)
            : $this->urlFragments;

        $q = "SELECT ";
        $attrs = $_menu->getAttrs(true);
        foreach ($attrs as $attr) {
            if ($_menu->A($attr)->isLcd()) {
                $attr_lc = $attr . '_' . SYS_LOCALE;
                $q .= "`$attr_lc` as `$attr`, ";
                $q .= "`$attr_lc`, ";
            } else {
                $q .= "`$attr`, ";
            }
        }
        $q .= "`ot_id`, LENGTH(`url`) as `url_length` FROM " . $_menu->vltURI() . " WHERE ";
        $urlStr = '/';
        $q .= "`url` = '" . $this->DB()->escape_string(urldecode($urlStr)) . "' ||";
        if (count($url)) {
            foreach ($url as $fragment) {
                $urlStr .= urldecode($fragment);
                $q .= "`url` = '" . $this->DB()->escape_string($urlStr) . "' ||";
                $urlStr .= '/';
            }
        }
        $q = mb_substr($q, 0, mb_strlen($q) - 2);
        $q .= "ORDER BY `url_length` DESC, priority DESC";
        $res = $this->DB()->query($q);
        if (!is_object($res) || !$res->getNumRows()) {
            return $this->currentItem;
        }

        $this->currentItem = $res->fetchRow();

        while ($row = $res->fetchRow()) {
            array_unshift($this->currentItemParents, $row);
        }

        return $this->currentItem;
    }

    function getMenuIdByLevel($lvl = 0)
    {
        $lvl = (int)$lvl;
        $_menu = \Verba\_oh('menu');
        return array_key_exists($lvl, $this->currentItemParents)
            ? $this->currentItemParents[$lvl][$_menu->getPAC()]
            : false;
    }

    function getMenuParents()
    {
        return $this->currentItemParents;
    }

    function getMenuParentsCount()
    {
        return count($this->currentItemParents);
    }

    /**
     * @param $ah ActionHandler
     * @param $A \ObjectType\Attribute
     * @param $inherit
     * @return null|string
     */
    function detectMenuItemParentPrefix($ah, $A, $inherit)
    {
        $prefix = null;
        $inherit = (bool)$inherit;
        try {
            if ($ah->getAction() == 'edit'
                && !$inherit) {
                throw new \Exception();
            }

            $ot_id = $ah->getOh()->getID();
            $piid = false;
            if ($ah->getAction() == 'edit') {
                $br = \Verba\Branch::get_branch(array($ot_id => array('aot' => $ot_id, 'iids' => $ah->getIID())), 'up', 1);
                if (!$br || !$br['pare']) {
                    throw new \Exception('');
                }
                $piid = current($br['pare'][$ot_id][$ah->getIID()][$ot_id]);
            } else {
                $pts = $ah->getParents();
                if (is_array($pts) && count($pts) && array_key_exists($ot_id, $pts) && is_array($pts[$ot_id])) {
                    reset($pts[$ot_id]);
                    $piid = current($pts[$ot_id]);
                }
            }
            if (!$piid || !is_array($pItem = $ah->getOh()->getData($piid, 1))
                || !array_key_exists($A->getCode(), $pItem)) {
                throw new \Exception('');
            }
            $prefix = $pItem[$A->getCode()];

        } catch (\Exception $e) {
            if (is_string($e->getMessage())) {
                $prefix = $e->getMessage();
            }
        }
        if (is_string($prefix) && !empty($prefix) && mb_substr($prefix, -1) != '/') {
            $prefix .= '/';
        }
        return $prefix;
    }
}
