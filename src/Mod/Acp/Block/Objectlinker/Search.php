<?php

namespace Verba\Mod\Acp\Block\Objectlinker;

class Search extends \Verba\Block\Json
{

    function build()
    {

        $str = isset($_REQUEST['val']) ? trim($_REQUEST['val']) : false;
        $_oh = isset($_REQUEST['otype']) ? \Verba\_oh($_REQUEST['otype']) : false;
        $cfg = $_SESSION['acp']['object-linker'][$_REQUEST['cache_id']];
        if (isset($cfg['aot'][$_oh->getID()])) {
            $c_cfg = $cfg['aot'][$_oh->getID()];
        }
        $iid = isset($_REQUEST['iid']) && !empty($_REQUEST['iid']) && !\Verba\Data\Boolean::isStrBool($_REQUEST['iid'])
            ? $_REQUEST['iid']
            : false;

        if (!$cfg || !is_array($cfg) || !$c_cfg || !is_array($c_cfg)) {
            throw new \Exception('Unable to find cache id');
        }
        if (!$str || !$_oh) {
            throw new \Exception('Bad incoming data');
        }

        $this->content = array(
            'items' => array(),
            'count' => 0,
            'total' => 0,
            'ot_id' => $_oh->getID(),
        );
        $ot_id = $_oh->getID();
        $field_name = $_oh->A('title')->isLcd() ? 'title_' . SYS_LOCALE : 'title';

        $qm = new \Verba\QueryMaker($_oh, false, $c_cfg['attr']);
        $qm->addSelectProp('SQL_CALC_FOUND_ROWS');
        $qm->addWhere($_oh->getID(), 'ot_id');
        $qm->addWhere("%" . $str . "%", 'title', $field_name, false, 'LIKE');
        $qm->addOrder(array('priority' => 'd'));
        $qm->addLimit(10);
        if (isset($cfg['aot'][$ot_id]['search']['addWhere']) && is_array($cfg['aot'][$ot_id]['search']['addWhere'])) {
            foreach ($cfg['aot'][$ot_id]['search']['addWhere'] as $cwhere) {
                $qm->addWhere($cwhere);
            }
        }
        if ($iid) {
            list($a) = $qm->createAlias();
            $qm->addWhere("`" . $a . "`.`" . $_oh->getPAC() . "` NOT IN('" . $this->DB()->escape((string)$iid) . "')");
        }

        $q = $qm->getQuery();
        $sqlr = $this->DB()->query($q);
        if (!$sqlr) {
            throw new Exception('Database search error');
        }

        if (!$sqlr->getNumRows()) {
            return $this->content;
        }
        $sqlr_t = $this->DB()->query('SELECT FOUND_ROWS()');
        $this->content['total'] = $sqlr_t->getFirstValue();
        $mImg = \Verba\_mod('image');
        $pac = $_oh->getPAC();
        while ($row = $sqlr->fetchRow()) {

            if (!empty($row['picture'])) {
                $iCfg = $mImg->getImageConfig($_oh->p('picture_config'));
                $pic_url = $iCfg->getFullUrl(basename($row['picture']), 'acp-list');
            } else {
                $pic_url = false;
            }
            $row['id'] = $row[$pac];
            $row['ot_code'] = $_oh->getCode();
            $row['picture'] = $pic_url;
            $this->content['items'][$_oh->getID() . '_' . $row[$pac]] = $row;
            $this->content['count']++;
        }

        return $this->content;
    }

}
