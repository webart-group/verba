<?php

namespace Mod\Product\Types;

class Product extends \Verba\Configurable
{
    /**
     * @var \Model, ObjectType
     */
    protected $oh;
    protected $cfgHashKey = 'product';
    public $hashFields = array();
    public $extraFields = array();

    protected $items2prepare;
    protected $iidsHashes;

    /**
     * текущая строка в итерации обработки
     */
    protected $row = array();
    /**
     * данные о родителе (базовом продукте) для текущей строки
     */
    protected $parentData = [];
    protected $cartItemClassName = '\\Mod\\Cart\\Item';

    function __construct($oh, $cfg)
    {
        $this->oh = \Verba\_oh($oh);
        $this->applyConfigDirect($cfg);
    }

    function setHashFields($val)
    {
        if (!is_array($val)) {
            return false;
        }
        $this->hashFields = $val;
    }

    function getHashFields()
    {
        return $this->hashFields;
    }

    function prepareToCart($cart, $_product, &$iidsHashes, &$items)
    {

        $this->items2prepare = $items;
        $this->iidsHashes = $iidsHashes;

        \Verba\_mod('Image');
        $_catalog = \Verba\_oh('catalog');
        $_product = \Verba\_oh($_product);
        $qm = new \Verba\QueryMaker($_product, false, true);
        list($palias) = $qm->createAlias();
        list($ctalias) = $qm->createAlias($_catalog->vltT(), $_catalog->vltDB());

        list($prntA) = $qm->createAlias($_product->vltT($_product), $_product->vltDB($_product), 'prnt');

        //Catalog Join
        $rule = $_product->getRule($_catalog);
        list($lcA) = $qm->createAlias($rule['table'], $rule['db'], '_catLnk');
        if ($rule['rule'] == 'fid') {

            $qm->addCJoin(array(array('a' => $ctalias)),
                array(
                    array('p' => array('a' => $palias, 'f' => $rule['glue_field']),
                        's' => array('a' => $ctalias, 'f' => $_catalog->getPAC()),
                    ),
                ), false, null, 'LEFT'
            );

        } else { //link with links table

            $qm->addCJoin(array(array('a' => $lcA)),
                array(
                    array('p' => array('a' => $lcA, 'f' => 'p_ot_id'),
                        's' => $_catalog->getID(),
                    ),
                    array('p' => array('a' => $lcA, 'f' => 'ch_ot_id'),
                        's' => $_product->getID(),
                    ),
                    array('p' => array('a' => $lcA, 'f' => 'ch_iid'),
                        's' => array('a' => $palias, 'f' => $_product->getPAC()),
                    ),
                ), false, null, 'LEFT'
            );

            $qm->addCJoin(array(array('a' => $ctalias)),
                array(
                    array('p' => array('a' => $lcA, 'f' => 'p_iid'),
                        's' => array('a' => $ctalias, 'f' => $_catalog->getPAC()),
                    ),
                ), false, null, 'LEFT'
            );
        }

        $qm->addSelectPastFrom('code', $ctalias, 'ccode');
        $qm->addSelectPastFrom('title_' . SYS_LOCALE, $ctalias, 'ctitle');
        $qm->addSelectPastFrom($_catalog->getPAC(), $ctalias, 'p_iid');

        // add Parent Data
        $qm->addSelectPastFrom('parentId', $palias);
        $parentTitleName = $_product->A('title')->isLcd() ? 'title_' . SYS_LOCALE : 'title';
        //$qm->addSelectPastFrom("IF(`".$palias."`.parentId > 0 && `".$palias."`.`parentId` IS NOT NULL,
        $qm->addSelectPastFrom("IF(`" . $palias . "`.parentId > 0,
CONCAT_WS(':', CAST(`" . $prntA . "`.`" . $parentTitleName . "` AS CHAR), CAST(`" . $prntA . "`.`price` AS CHAR), cast(`" . $prntA . "`.`picture` AS CHAR)), '') AS `parentData`", null, null, true);
        $qm->addCJoin(array(array('a' => $prntA)),
            array(
                array('p' => array('a' => $palias, 'f' => 'parentId'),
                    's' => array('a' => $prntA, 'f' => $_product->getPAC()),
                ),
            )
        );

        $qm->addWhere("`" . $palias . "`.`" . $_product->getPAC() . "` IN (" . ("'" . implode("','", array_keys($iidsHashes)) . "'") . ")");
        $qm->addGroupBy($_product->getPAC());
        //$q = $qm->getQuery();
        $sqlr = $qm->run();

        if (!$sqlr || !$sqlr->getNumRows()) {
            return null;
        }

        if (!class_exists($this->cartItemClassName))
        {
            $this->log()->error('Unable to find class or file for declared Cart Item class: ' . var_export($this->cartItemClassName, true));
            $cartItemclassName = '\\Mod\\Cart\\Item';
        } else {
            $cartItemclassName = $this->cartItemClassName;
        }

        while ($this->row = $sqlr->fetchRow()) {
            $id = $this->row[$_product->getPAC()];
            if (isset($this->row['parentData']) && !empty($this->row['parentData'])) {
                $this->parentData = self::extractParentDataFromSql($this->row['parentData']);
            }
            $this->extractImage();
            $this->extractPrice();
            $this->extractTitle();
            $this->extractCustoms();

            $this->row['catalogId'] = $this->row['p_iid'];
            unset($this->row['p_iid']);

            foreach ($iidsHashes[$id] as $cHash) {
                $props = array_replace_recursive($items[$cHash], $this->row);
                $items[$cHash] = new $cartItemclassName($cart, $cHash, $props);
            }
        }

        $this->items2prepare = null;
        $this->iidsHashes = null;
        return $items;
    }

    static function extractParentDataFromSql($str)
    {
        $a = explode(':', $str);
        $p = array();
        $p['title'] = $a[0];
        $p['price'] = $a[1];
        $p['picture'] = $a[2];
        return $p;
    }

    function extractImage()
    {

        /* _picture_config не обработана */

//    if(!empty($this->row['picture'])){
//      $pcfg = $this->row['_picture_config'];
//      $picture = $this->row['picture'];
//    }elseif(isset($this->parentData['_picture_config'])
//      && !empty($this->parentData['_picture_config'])
//      && !empty($this->parentData['picture'])){
//      $pcfg = $this->parentData['_picture_config'];
//      $picture = $this->parentData['picture'];
//    }else{
//      $pcfg = false;
//      $picture = false;
//    }
//    if($pcfg){
//      $this->row['image'] = Image::getImageConfig($pcfg)->getFullUrl(basename($picture), 'thumbs');
//    }else{
//      $this->row['image'] = null;
//    }
    }

    function extractPrice()
    {
        $this->row['price'] = \Verba\reductionToCurrency($this->row['price']);
        if (!$this->row['price'] && !empty($this->parentData['price'])) {
            $this->row['price'] = \Verba\reductionToCurrency($this->parentData['price']);
        }
    }

    function extractTitle()
    {
        $this->row['title'] =
            !empty($this->row['title'])
                ? $this->row['title']
                : (isset($this->parentData['title']) && !empty($this->parentData['title'])
                ? $this->parentData['title']
                : '');
    }

    function extractCustoms()
    {

    }

    /**
     * @param $rqItem
     * @param $prodItem \Model\Item
     * @return string
     */
    function generateItemHash($rqItem, $prodItem)
    {


        $h = $prodItem->oh()->getID() . $prodItem->getId();

        if (!is_array($rqItem)) {
            $rqItem = array();
        }

        if (isset($rqItem['_hash']) && is_array($rqItem['_hash']) && !empty($rqItem['_hash'])) {
            $hashData = &$rqItem['_hash'];
        } elseif (isset($rqItem['_extra']) && is_array($rqItem['_extra']) && !empty($rqItem['_extra'])) {
            $hashData = &$rqItem['_extra'];
        } else {
            $hashData = false;
        }

        if (!count($this->hashFields) || !$hashData) {
            return md5($h);
        }

        foreach ($this->hashFields as $key) {
            if (isset($hashData[$key])) {
                $v = $hashData[$key];
            } elseif (isset($rqItem[$key])) {
                $v = $rqItem[$key];
            } else {
                continue;
            }
            $h .= $v;
        }
        return md5($h);
    }

    function isDownloadable()
    {
        return false;
    }

    function handleOrderStatusChange($items, $order, $ae = null)
    {
        return;
    }

    function sold($items, $ah)
    {

    }

    function sellCanceled($items, $ah)
    {

    }
}
