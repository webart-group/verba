<?php

namespace Verba\Mod;

use Configurable;
use Exception;
use ObjectType\Attribute;
use Verba\Branch;
use Verba\Hive;
use Verba\Mod;
use Verba\ModInstance;
use Verba\QueryMaker;
use function Verba\_mod;
use function Verba\_oh;

class Product extends Mod
{
    use ModInstance;
    protected $productHandlers = array();
    protected $phCfg = null;


    /**
     * @param $ot
     * @param $iid
     * @return array|bool
     */
    function getCatsByProduct($ot, $iid)
    {
        $oh = _oh($ot);
        $prodOtId = $oh->getID();
        $_catalog = _oh('catalog');
        $catOtId = $_catalog->getID();
        $mCat = _mod('catalog');

        $brn = Branch::get_branch(array($oh->getID() => array('aot' => array($catOtId), 'iids' => $iid)), 'up', 5);
        $threads = array();
        foreach ($brn['pare'][$prodOtId][$iid][$catOtId] as $ccatId) {
            $threads[$ccatId] = Branch::build_tree($brn, 2, array($catOtId => array($ccatId => $ccatId)));
        }
        //$plainChain = \Verba\Branch::build_tree($brn, 2);
        if (!$threads) {
            return false;
        }
        $foundedParentId = false;
        if (count($threads) == 1) {
            reset($threads);
            $tread = current($threads);
            //rm #1 кат // вообще не оч понятно зачем это нужно
            //array_pop($tread);
            $items = $mCat->getItems($tread, true);

        } elseif (count($threads) > 1) {
            $all_cat_iids = array_unique(call_user_func_array('array_merge', $threads));
            $all_items = $mCat->getItems($all_cat_iids, true);

            if (isset($_REQUEST['slID'])
                && preg_match("/c" . $_catalog->getID() . "_(\d+)/i", $_REQUEST['slID'], $_)
                && array_key_exists($_[1], $threads)) {
                $foundedParentId = $_[1];
            } else {

                foreach ($threads as $treadId => $treadNodes) {
                    $prodParentCatId = current($treadNodes);
                    if ($all_items[$prodParentCatId]['itemsOtId'] == $prodOtId) {
                        $foundedParentId = $treadId;
                        break;
                    }
                }
            }

            if (!$foundedParentId) {
                reset($threads);
                $tread = current($threads);
            } else {
                $tread = $threads[$foundedParentId];
            }
            array_pop($tread);
            $items = array_intersect_key($all_items, array_flip($tread));

        } else {
            return false;
        }

        return $items;
    }

    function getItem($ot, $iid, $catalogId = false, $loadVariants = true)
    {
        $item = $this->loadItem($ot, $iid, $catalogId);
        if (!$item) {
            return false;
        }
        if (!$loadVariants) {
            return $item;
        }

        $item['_variants'] = $this->loadItem($ot, $iid, $catalogId, true);
        return $item;
    }

    function loadItem($ot, $iid, $catalogId = false, $isVariants = false)
    {
        $r = array();
        $iid = $this->DB()->escape_string($iid);
        $_product = _oh($ot);
        $_catalog = _oh('catalog');
        $_image = _oh('image');
        $mImage = _mod('image');
        $qm = new QueryMaker($_product, false, true);
        list($ptalias) = $qm->createAlias($_product->vltT(), $_product->vltDB());
        list($ctalias) = $qm->createAlias($_catalog->vltT(), $_catalog->vltDB());
        list($ialias, $itable) = $qm->createAlias($_image->vltT());
        list($ilalias, $iltable) = $qm->createAlias($_product->vltT($_image->getID()), $_product->vltURI($_image->getID()), 'imglnk');
        list($lcA, $lcT, $lcD) = $qm->createAlias($_product->vltT($_catalog));

        $qm->addGroupBy(array($_product->getPAC()));
        if ($isVariants) {
            $qm->addWhere($iid, 'parentId', 'parentId', array(null, null, $ptalias));
        } else {
            $qm->addCJoin(array(array('a' => $lcA)),
                array(
                    array('p' => array('a' => $lcA, 'f' => 'p_ot_id'),
                        's' => $_catalog->getID(),
                    ),
                    array('p' => array('a' => $lcA, 'f' => 'p_iid'),
                        's' => '(\'' . $this->DB()->escape_string($catalogId) . '\')',
                        'asis' => true,
                        'o' => 'IN'
                    ),
                    array('p' => array('a' => $lcA, 'f' => 'ch_iid'),
                        's' => array('a' => $ptalias, 'f' => $_product->getPAC()),
                    ),
                ), false, null, 'LEFT'
            );
            $qm->addWhere('`' . $ptalias . '`.`' . $_product->getPAC() . '` = \'' . $iid . '\'');
            $qm->addSelectPastFrom('title_' . SYS_LOCALE, $ctalias, 'ctitle');
            $qm->addSelectPastFrom($_catalog->getPAC(), $ctalias, 'p_iid');
            $qm->addCJoin(array(array('a' => $ctalias)),
                array(
                    array('p' => array('a' => $lcA, 'f' => 'p_iid'),
                        's' => array('a' => $ctalias, 'f' => $_catalog->getPAC()),
                    ),
                    array('p' => array('a' => $ctalias, 'f' => 'active'),
                        's' => '1',
                    ),
                ), false, null, 'RIGHT'
            );
            $qm->addWhere('`' . $ctalias . '`.`active` = 1');
        }
        $qm->addWhere('`' . $ptalias . '`.`active` = 1');

        // images
        $qm->addSelect('GROUP_CONCAT(DISTINCT CONCAT_WS(\':\',
      CAST(`' . $ialias . '`.`' . $_image->getPAC() . '` AS CHAR),
      CAST(`' . $ialias . '`.`priority` AS CHAR),
      CAST(`' . $ialias . '`.`_storage_file_name_config` AS CHAR),
      `' . $ialias . '`.`storage_file_name`))', false, '_images', true);
        $qm->addCJoin(array(array('a' => $ilalias)),
            array(
                array('p' => array('a' => $ilalias, 'f' => 'p_iid'),
                    's' => array('a' => $ptalias, 'f' => $_product->getPAC())),
                array('p' => array('a' => $ilalias, 'f' => 'p_ot_id'),
                    's' => $_product->getID()),
                array('p' => array('a' => $ilalias, 'f' => 'ch_ot_id'),
                    's' => $_image->getID())));
        $qm->addCJoin(array(array('a' => $ialias)),
            array(
                array('p' => array('a' => $ialias, 'f' => $_image->getPAC()),
                    's' => array('a' => $ilalias, 'f' => 'ch_iid'))));

        $q = $qm->getQuery();
        $sqlr = $this->DB()->query($q);
        if (!$sqlr || !$sqlr->getNumRows()) {
            return $r;
        }
        while ($item = $sqlr->fetchRow()) {
            $item['_images'] = $mImage->getImgDataFromString($item['_images'], true);
            $r[] = $item;
        }
        if (!$isVariants) {
            return array_shift($r);
        }
        return $r;
    }

    function getPhCfg()
    {
        if ($this->phCfg !== null) {
            return $this->phCfg;
        }
        $cfg = $this->gC('productHandler');
        if (!is_array($cfg) || empty($cfg)) {
            throw new Exception('Unable to find product handler cfg');
        }
        $this->phCfg = Configurable::substNumIdxAsStringValues($cfg);
        if (!is_array($this->phCfg)) {
            $this->phCfg = array();
        }
        return $this->phCfg;
    }

    function getProductHandler($oh)
    {
        $oh = _oh($oh);
        $code = $oh->getCode();
        if (array_key_exists($code, $this->productHandlers)
            && is_object($this->productHandlers[$code])) {
            return $this->productHandlers[$code];
        }

        $prodClassName = get_class($oh);

        $cfg = $this->getPhCfg();

        if (array_key_exists($code, $cfg)) {
            $classSfx = isset($cfg[$code]['_class']) ? $cfg[$code]['_class'] : $code;
            $hCfg = $cfg[$code];
        } elseif (array_key_exists($prodClassName, $cfg)) {
            $classSfx = isset($cfg[$prodClassName]['_class']) ? $cfg[$prodClassName]['_class'] : $prodClassName;
            $hCfg = $cfg[$prodClassName];
        } else { //$oh instanceof ot_prodUniq or unknown
            $classSfx = $cfg['_default']['_class'];
            $hCfg = $cfg['_default'];
        }

        if (isset($hCfg['_class'])) {
            unset($hCfg['_class']);
        }
        if (!isset($classSfx)) {
            throw new Exception('Unable to detect Product Handler class');
        }

        $className = $classSfx;
        if (!class_exists($className)) {
            throw new Exception('Unable load Product Type class [' . $code . ']');
        }

        $this->productHandlers[$code] = new $className($oh, $hCfg);
        return $this->productHandlers[$code];
    }

    function getProductGroupAttrFilterType($item, $A)
    {
        $r = '';
        Hive::loadMakeListClass();
        if (isset($item['filtertype']) && is_string($item['filtertype'])
            && !empty($item['filtertype'])
            && class_exists('ListFilter_' . ucfirst($item['filtertype']))) {
            return $item['filtertype'];
        }

        if (!$A instanceof Attribute) {
            return $r;
        }

        if ($A->isForeignId()) {
            $r = 'ForeignId';
        } elseif ($A->data_type == 'multiple') {
            $r = 'VariantsMulti';
        } elseif ($A->isPredefined()) {
            $r = 'Predefined';
        }
        return $r;
    }

    function genAefCustomAttrs($aef)
    {
        $attrs = $aef->oh->getAttrsByBehaviors('custom');
        if (!$attrs) {
            return '';
        }
        $tpl = $aef->tpl();
        $tpl->define(array(
            'prod-aef-custom-attrs-wrap' => '/product/acp/aef/custom-wrap.tpl',
            'prod-aef-custom-attrs-item' => '/product/acp/aef/custom-item.tpl',
        ));

        $tpl->assign(array('AEF_CUSTOM_ATTRIBUTES_ROWS' => ''));

        foreach ($attrs as $attr_id) {
            $A = $aef->oh->A($attr_id);
            $acode = $A->getCode();
            if (array_key_exists(strtolower($acode), $aef->founded_in_particle)) {
                continue;
            }
            $tpl->assign(array(
                'AEF_CUSTOM_ATTR_NAME' => $tpl->getVar('ANAME_' . strtoupper($acode) . '_0'),
                'AEF_CUSTOM_ATTR_VALUE' => $tpl->getVar('AVALUE_' . strtoupper($acode) . '_0')
            ));
            $tpl->parse('AEF_CUSTOM_ATTRIBUTES_ROWS', 'prod-aef-custom-attrs-item', true);
        }

        return $tpl->parse(false, 'prod-aef-custom-attrs-wrap');
    }

    function sortVariants($a, $b)
    {
        $v = array(
            'a' => reductionToFloat($a['size']),
            'b' => reductionToFloat($b['size']),
        );
        $units = array(
            'a' => $a['size_unit'],
            'b' => $b['size_unit'],
        );
        foreach (array('a', 'b') as $k) {
            switch ($units[$k]) {
                case 158: //l
                case 162: //ml
                case 157: //kg
                case 161: //g
                    switch ($units[$k]) {
                        case 157://kg
                        case 158://l
                            $v[$k] = $v[$k] * 1000;
                            break;
                    }
                    break;
                case 159: //cm
                case 163: //m
                case 164: //mm
                    switch ($units[$k]) {
                        case 159://cm
                            $v[$k] = $v[$k] * 10;
                        case 158://m
                            $v[$k] = $v[$k] * 1000;
                            break;
                    }
                    break;
            }
        }


        if ($v['a'] == $v['b']) {
            return 0;
        }
        return ($v['a'] < $v['b']) ? -1 : 1;
    }
}
