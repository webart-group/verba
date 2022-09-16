<?php

namespace Verba\Mod;

class Search extends \Verba\Mod
{
    use \Verba\ModInstance;

    function handleQuery($query)
    {

        try {
            $_prod = \Verba\_oh('product');
            $sq = \mb_strtolower(\trim($query));
            $hash = $this->makeHash($sq);
            //$q = "SELECT * FROM ".SYS_DATABASE.".`searches` WHERE hash = '".$this->DB()->escape($hash)."'";
            //$sqlr = $this->DB()->query($q);
            if (!$this->updateSearch($hash, array('ip' => \Verba\getClientIP()))) {
                if (!$this->createSearch($sq, $hash)) {
                    throw new \Exception('Unable to create search session.');
                }
            }
            $sqlr = $this->DB()->query(
                "INSERT INTO " . SYS_DATABASE . ".`search_sessions` (`query`,`hash`,`created`,`ip`)
                 VALUES (
                  '" . $this->DB()->escape($sq) . "',
                  '" . \mb_strtolower($this->DB()->escape($hash)) . "',
                  '" . date('Y-m-d H:i:s') . "',
                  '" . \Verba\getClientIP() . "'
              )");

            $r = $this->runSearch($sq);
        } catch (\Exception $e) {
            $r = $e->getMessage();
        }

        return $r;
    }

    function runSearch($sq, $ccfg = array())
    {

        $_product = \Verba\_oh('product');
        $_catalog = \Verba\_oh('catalog');
        $cur = \Verba\_mod('cart')->getCurrency();
        $hash = $this->makeHash($sq);
        $r = array(
            'total' => 0,
            'count' => 0,
            'items' => array(),
            'allResultsUrl' => '/search/' . $hash,
        );
        $cfg = array(
            'attrs' => array('title', 'picture', '_picture_config', 'url_code', 'price')
        );
        if (is_array($ccfg) && !empty($ccfg)) {
            $cfg = array_replace_recursive($cfg, $ccfg);
        }

        $qm = new \Verba\QueryMaker($_product, false, $cfg['attrs']);
        $qm->addSelectProp('SQL_CALC_FOUND_ROWS');
        $qm->addLimit(10);
        list($palias, $ptable, $db) = $qm->createAlias($_product->vltT());
        list($ctalias, $ctable, $cdb) = $qm->createAlias($_catalog->vltT());
        list($lcA, $lcT, $lcD) = $qm->createAlias($_product->vltT($_catalog));
        $qm->addGroupBy(array('id'));
        $qm->addWhere($palias . '.`active` > 0');
        $qm->addWhere($palias . '.`tmp` = 0');
        $qm->addOrder(array('priority' => 'd', $_product->getPAC() => 'd'));

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
        $wg->addWhere('%' . $sq . '%', 'title', 'title_' . SYS_LOCALE, null, 'LIKE');
        $wg->addWhere('%' . $sq . '%', 'articul', 'articul', null, 'LIKE', '||');

        $q = $qm->getQuery();

        $sqlr = $this->DB()->query($q);
        $sqlr_t = $this->DB()->query('SELECT FOUND_ROWS()');
        $r['total'] = (int)$sqlr_t->getFirstValue();
        $r['count'] = (int)$sqlr->getNumRows();

        $mImg = \Verba\_mod('image');
        $cats = array();
        while ($row = $sqlr->fetchRow()) {
            $oh = \Verba\_oh($row['ot_id']);
            $pac = $oh->getPAC();
            if (!empty($row['picture'])) {
                $iCfg = $mImg->getImageConfig($row['_picture_config']);
                $pic_url = $iCfg->getFullUrl(basename($row['picture']), 'thumbs');
            } else {
                $pic_url = false;
            }

            $prices = array($row['price']);
            if (!empty($row['variant'])) {
                $cv = explode('#', $row['variant']);
                foreach ($cv as $v) {
                    $v = explode(':', $v);
                    $prices[] = \Verba\reductionToCurrency($v[1] * $cur['rate']);
                }
            }
            sort($prices);
            $row['price'] = current($prices);
            $row['id'] = $row[$pac];
            $row['url'] = Seo::idToSeoStr($row);
            $row['ot_code'] = $oh->getCode();
            $row['picture'] = $pic_url;
            unset($row['variant'], $row['promos'], $row['key_id'], $row['url_code'], $row['_picture_config']);
            $r['items'][$oh->getID() . '_' . $row[$pac]] = $row;
            $cats[$row['catId']] = $row['catId'];
        }
        $br = \Verba\Branch::get_branch(array($_catalog->getID() => array('aot' => $_catalog->getID(), 'iids' => array_keys($cats))), 'up', 5, true, true);
        $tree = \Verba\Branch::build_plain_chains($br);
        foreach ($tree as $tid => $tnodes) {
            array_pop($tnodes);
            $tree[$tid] = $tnodes;
        }
        $r['catalogs']['chains'] = $tree;
        $r['catalogs']['data'] = $_catalog->getData($br['handled'][$_catalog->getID()], true, array('title', 'code', 'fullcode'));
        return $r;
    }

    function makeHash($q)
    {
        return strtolower(md5($q));
    }

    function looksLikeHash($str)
    {
        return is_string($str) && preg_match('/[abcdef0-9]{32}/i', $str);
    }

    function findQByHash($hash)
    {
        if (!is_string($hash)) {
            return false;
        }
        $sqlr = $this->DB()->query("SELECT `query` FROM " . SYS_DATABASE . ".`searches` WHERE `hash` = '" . \mb_strtolower($this->DB()->escape($hash)) . "'");
        if (!$sqlr || !$sqlr->getNumRows()) {
            return false;
        }
        return $sqlr->getFirstValue();
    }

    function createSearch($sq, $hash)
    {
        $sqlr = $this->DB()->query(
            "INSERT INTO " . SYS_DATABASE . ".`searches` (
      `query`,
      `hash`,
      `created`,
      `modified`,
      `count`,
      `last_ip`
      ) VALUES (
        '" . $this->DB()->escape(\mb_strtolower(trim($sq))) . "',
        '" . \mb_strtolower($this->DB()->escape($hash)) . "',
        '" . date('Y-m-d H:i:s') . "',
        '" . date('Y-m-d H:i:s') . "',
        '1',
        '" . \Verba\getClientIP() . "'
      )");
        $sid = $sqlr->getInsertId();
        if (!$sid) {
            return false;
        }
        return $sid;
    }

    function updateSearch($idOrHash, $data)
    {
        $field = is_numeric($idOrHash) ? 'id' : 'hash';
        $q = "UPDATE " . SYS_DATABASE . ".`searches` SET
    `count` = `count`+1,
    `last_ip` = '" . $this->DB()->escape(ip2long($data['ip'])) . "',
    `modified` = '" . date('Y-m-d H:i:s') . "'
    WHERE
      `" . $field . "` = '" . $this->DB()->escape($idOrHash) . "'
    LIMIT 1";
        $sqlr = $this->DB()->query($q);
        return $sqlr->getAffectedRows() > 0 ? true : false;
    }
}
