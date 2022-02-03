<?php
namespace Mod\Search\Block\Result;

class FoundedList extends \Verba\Block\Html
{

    public $list;
    public $parseFilters = false;
    public $optionsBlock;
    public $ot;
    public $cfg = 'public products';
    public $dcfg = false;
    public $q;

    function build()
    {

        if (!is_string($this->q) || empty($this->q)) {
            $this->content = '';
            return $this->content;
        }

        $this->addCss(array(
            array('products promotion')
        ));

        $_catalog = \Verba\_oh('catalog');
        $_image = \Verba\_oh('image');


        $cacheKey = false;

        $this->ot = is_string($this->ot) && $this->ot ? $this->ot : 'product';
        $_product = \Verba\_oh($this->ot);

//    $branch = branch::get_branch(array($_catalog->getID() => array('aot' => array($_catalog->getID()), 'iids'=> array($piid))), 'down', 3, false, false);
//    $catIids = array($piid);
//    if(is_array($branch['handled'][$_catalog->getID()])
//    && count($branch['handled'][$_catalog->getID()])){
//      $catIids += $branch['handled'][$_catalog->getID()];
//    }

        $this->list = init_list_free($_product->getID(), $cacheKey, array(
            //'pot' => $pot,
            //'piid' => $piid,
            'cfg' => $this->cfg,
            'dcfg' => $this->dcfg,
        ));

        $this->list->_block = $this;

        $qm = $this->list->QM();

        list($palias, $ptable, $db) = $qm->createAlias($_product->vltT());
        list($ctalias, $ctable, $cdb) = $qm->createAlias($_catalog->vltT());
        list($lcA, $lcT, $lcD) = $qm->createAlias($_product->vltT($_catalog));
        $qm->addGroupBy(array('id'));
        $qm->addWhere($palias . '.`active` > 0');
        $qm->addOrder(array('priority' => 'd', $_product->getPAC() => 'd'));
        $qm->addWhere($palias . '.`tmp` = 0');
        //подключение таблицы связей каталог-продукты
        $qm->addCJoin(array(array('a' => $lcA)),
            array(
                array('p' => array('a' => $lcA, 'f' => 'p_ot_id'),
                    's' => $_catalog->getID(),
                ),
                array('p' => array('a' => $lcA, 'f' => 'ch_ot_id'),
                    's' => array('a' => $palias, 'f' => 'ot_id'),
                ),
                array('p' => array('a' => $lcA, 'f' => 'ch_iid'),
                    's' => array('a' => $palias, 'f' => $_product->getPAC()),
                ),
            ), false, null, 'LEFT'
        );

        // подключение таблицы каталога для выборки данных каталога
        //$qm->addSelectPastFrom('title_ru', $ctalias, 'ctitle');
        //$qm->addSelectPastFrom('code', $ctalias, 'ccode');
        $qm->addSelectPastFrom($_catalog->getPAC(), $ctalias, 'catId');
        $qm->addCJoin(array(array('a' => $ctalias)),
            array(
                array('p' => array('a' => $lcA, 'f' => 'p_iid'),
                    's' => array('a' => $ctalias, 'f' => $_catalog->getPAC()),
                ),
                array('p' => array('a' => $ctalias, 'f' => 'active'),
                    's' => '1',
                ),
            ), false, null, 'LEFT'
        );

        //Promos
        $_promo = \Verba\_oh('promotion');
        list($promoA, $promoT, $promoDb) = $qm->createAlias($_promo->vltT());
        list($lpA, $lpT, $lpD) = $qm->createAlias($_promo->vltT($_product));
        $qm->addCJoin(array(array('a' => $lpA)),
            array(
                array('p' => array('a' => $lpA, 'f' => 'p_ot_id'),
                    's' => $_promo->getID(),
                ),
                array('p' => array('a' => $lpA, 'f' => 'ch_ot_id'),
                    's' => $_product->getID(),
                ),
                array('p' => array('a' => $lpA, 'f' => 'ch_iid'),
                    's' => array('a' => $palias, 'f' => $_product->getPAC()),
                ),
            ), false, null, 'LEFT'
        );

        $qm->addSelect('GROUP_CONCAT(DISTINCT CONCAT_WS(\'^\', CAST(`' . $promoA . '`.`id` AS CHAR), CAST(`' . $promoA . '`.`title_' . SYS_LOCALE . '` AS CHAR), CAST(`' . $promoA . '`.`annotation_' . SYS_LOCALE . '` AS CHAR)) SEPARATOR \'~\')', false, 'promos', true);
        $qm->addCJoin(array(array('a' => $promoA)),
            array(
                array('p' => array('a' => $promoA, 'f' => 'id'),
                    's' => array('a' => $lpA, 'f' => 'p_iid'),
                ),
            )
            , true);

        //Products Variants
        $qm->addWhere('(' . $palias . '.`productId` = 0)');
        $pta = $palias . '_1';
        list($palias_1, $ptable_1, $db_1) = $qm->createAlias($_product->vltT(), false, $pta);
        $qm->addCJoin(array(array('a' => $palias_1)),
            array(
                array('p' => array('a' => $palias_1, 'f' => 'productId'),
                    's' => array('a' => $palias, 'f' => $_product->getPAC()),
                ),
                array('p' => array('a' => $palias_1, 'f' => 'active'),
                    's' => '1',
                ),
            )
            , true);
        $qm->addSelect('GROUP_CONCAT(DISTINCT CONCAT_WS(\':\', CAST(`' . $pta . '`.`id` AS CHAR), CAST(`' . $pta . '`.`price` AS CHAR), CAST(`' . $pta . '`.`size` AS CHAR), CAST(`' . $pta . '`.`size_unit` AS CHAR)) SEPARATOR \'#\')', false, 'variant', true);

        //add where
        $wg = $qm->addWhereGroup('srch_rq');
        $wg->addWhere('%' . $this->DB()->escape($this->q) . '%', 'title', 'title_' . SYS_LOCALE, null, 'LIKE');
        $wg->addWhere('%' . $this->DB()->escape($this->q) . '%', 'articul', 'articul', null, 'LIKE', '||');

//    if($this->parseFilters){
//      $filtertBlock = new product_listFilters($this);
//    }

        $this->content = $this->list->generateList();
        $q = $qm->getQuery();

        $this->optionsBlock = new product_listOptions($this);
        $this->optionsBlock->prepare();
        $this->optionsBlock->build();

        return $this->content;
    }
}
