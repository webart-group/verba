<?php
namespace Verba\Mod;

class Catalog extends \Verba\Mod
{
    use \Verba\ModInstance;
    protected $otic_ot = 'catalog';

    function makeAction($bp)
    {
        switch ($bp['action']) {
            default :
                $handler = null;
        }
        //default actions
//    if(!$handler){
//      $handler = parent::makeAction($bp);
//    }
        return $handler;
    }

    function getItemsByParent($iid)
    {

        $_cat = \Verba\_oh('catalog');
        $pac = $_cat->getPAC();
        $catot = $_cat->getID();
        $br = \Verba\Branch::get_branch(array($catot => array('iids' => $iid, 'aot' => $catot)), 'down', 2, true, false);
        if (!isset($br['handled'][$catot]) || empty($br['handled'][$catot])) {
            return false;
        }

        return array(
            'br' => $br,
            'data' => $_cat->getData($br['handled'][$catot], true, true)
        );
    }

    function addCatsToBreadcrumbs($catsData, $urlPref = '/catalog')
    {
        if (!is_array($catsData)) {
            return false;
        }
        $mMenu = \Verba\_mod('menu');
        $_catalog = \Verba\_oh('catalog');
        if (!is_string($urlPref)) {
            $urlPref = '';
        }
        foreach ($catsData as $cCat) {
            $urlPref .= '/' . $cCat[$_catalog->getStringPAC()];
            $mMenu->addMenuChain(array(
                'ot_id' => $cCat['ot_id'],
                $_catalog->getPAC() => $cCat[$_catalog->getPAC()],
                'title' => $cCat['title'],
                'url' => $urlPref
            ));
        }
    }

    function getCatsChain($uf, $shift = 1)
    {
        $shift = (int)$shift;

        if (count($uf)
            && $uf[count($uf) - 1] == false) {
            array_pop($uf);
        }
        if ($shift) {
            $catalogCodes = array_slice($uf, $shift);
        } else {
            $catalogCodes = $uf;
        }
        if (empty($catalogCodes)) {
            $rootId = 1;
            $catalogCodes = array($rootId);
        }
        return $this->getItemsByCodesChain($catalogCodes);
    }

    function getItemsByCodesChain($catsChain)
    {
        if (!is_array($catsChain) || empty($catsChain)) {
            false;
        }
        $chr = '';
        foreach ($catsChain as $cf) {
            $chr = $chr . '/' . $cf;
            $where[] = $this->DB()->escape($chr);
        }
        $r = array();
        $_cat = \Verba\_oh('catalog');
        $qm = new \Verba\QueryMaker($_cat, false, true);
        $qm->addWhere("`fullcode` IN('" . implode("','", $where) . "')");
        $qm->addOrder(array('fullcode' => 'a'));
        $sqlr = $qm->run();
        if ($sqlr && $sqlr->getNumRows()) {
            while ($row = $sqlr->fetchRow()) {
                $r[$row['id']] = $row;
            }
        }
        $lastInWhere = strtolower(end($where));
        $lastInChain = end($r);
        $lastInChain = strtolower($lastInChain['fullcode']);
        if ($lastInChain != $lastInWhere) {
            return false;
        }
        return $r;
    }
}
