<?php
namespace Verba\Mod;

class Links extends \Verba\Mod
{
    use \Verba\ModInstance;
    private $_loadedCfgs = array();

    public static $config_default = array();

    public $_lastExtractedLinkingData = array();


    function makeGID($row)
    {

        if (!is_array($row) || empty($row)) {
            return false;
        }

        if (isset($row['p_ot_id'])) {
            return $row['p_ot_id'] . '_' . $row['p_iid'] . '_' . $row['ch_ot_id'] . '_' . $row['ch_iid'];
        } elseif (isset($row[0])
            && $row[1]
            && $row[2]
            && $row[3]
        ) {
            return $row[0] . '_' . $row[1] . '_' . $row[2] . '_' . $row[3];
        }

        return false;
    }

    function castMid()
    {

        $args = func_get_args();
        $argsNum = func_num_args();
        if ($argsNum == 2) {

        }

        return false;
    }

    /*  function makeAction($bp){
        switch($bp['action']){
          case 'linkinglist':
                $handler = 'linkingList'; break;
          case 'link':
                $handler = 'link'; break;
          case 'unlink':
                $handler = 'unlink'; break;

          case 'browselist':
                $handler = 'browseList'; break;

          case 'parentlink':
                $handler = 'parentLink'; break;
          case 'parentunlink':
                $handler = 'parentUnlink'; break;
          default:
                $handler = false; break;
        }
        return $handler;
      }*/

    function getCfg($cfgName)
    {
        if (is_array($cfgName)) {
            return $cfgName;
        }
        if (!is_string($cfgName) || empty($cfgName)) {
            return false;
        }

        $cfgPath = SYS_CONFIGS_DIR . '/linking/lk.' . $cfgName . '.php';
        if (!array_key_exists($cfgPath, $this->_loadedCfgs)) {
            $this->_loadedCfgs[$cfgPath] = @include($cfgPath);
            if (!is_array($this->_loadedCfgs[$cfgPath])) {
                $this->_loadedCfgs[$cfgPath] = false;
                $this->log()->error('Unable to load linking Cfg [' . var_export($cfgPath, true) . ']');
            }
        }
        return $this->_loadedCfgs[$cfgPath];
    }

    function extractLinkingData($rq, $cfg, $action)
    {

        $r = array('primary', 'secondary');
        foreach (array('primary', 'secondary') as $i => $key) {
            $r[$i] = $this->extractItemsFromEnv($action, $key, $rq, $cfg);
        }
        // extData
        $r[2] = array();

        if ($action == 'link' || $action == 'update') {
            $r[2] = $rq->getParam('extData', true);
        }
        $this->_lastExtractedLinkingData = $r;
        return $r;
    }

    /**
     * put your comment there...
     *
     * @param string $key
     * @param \Verba\Request $rq
     * @param array $cfg
     */

    function extractItemsFromEnv($action, $key, $rq, $cfg)
    {
        switch ($key) {
            case 'primary':
                $short = 'prim';
                break;
            case 'secondary':
                $short = 'sec';
                break;
            default:
                return false;
        }
        $items = false;


        if (isset($_REQUEST[$short . 'Ot']) && !isset($_REQUEST[$short . 'SlID'])) {

            $items = array($_REQUEST[$short . 'Ot'] => array($_REQUEST[$short . 'Id']));

        } elseif ($_REQUEST[$short . 'SlID']) {
            $SlID = $_REQUEST[$short . 'SlID'];
            $sl = \Verba\init_selection(false, false, $SlID);

            if (!$sl->getSelectedCount()) {
                throw new \Exception(ucfirst($key) . ' items list is empty');
            }
            $items = $sl->getSelected();
        } elseif (is_object($rq) && $rq instanceof \Verba\Request) {
            $rqData = $rq->getParam($short, true);
            if (is_array($rqData) && is_array(current($rqData))) {
                $items = $rqData;
            }
        }
        return $items;
    }

    function initLinkingFace($lnkData)
    {

        $cfg = $this->getCfg($lnkData['cfgName']);
        $tpl = $this->tpl();
        $tpl->define(array(
            'lk-page' => '/linking/page.tpl',
            'lk-wrap' => '/linking/select-wrap.tpl',
            'lk-template' => '/linking/template.tpl',
        ));

        $cfg['client']['root'] = array(
            'ot' => isset($cfg['nodes']['ot']) ? \Verba\_oh($cfg['nodes']['ot'])->getID() : null,
            'id' => isset($cfg['nodes']['id']) ? $cfg['nodes']['id'] : null,
        );
        if (isset($lnkData['post'])) {
            $cfg['client']['post'] = $lnkData['post'];
        }

        $tpl->assign(array(
            'LKG_SANDBOX' => $cfg['client']['sandboxSelector'],
            'LKG_CFG' => json_encode($cfg['client']),
        ));

        $tpl->parse('LK_TEMPLATE', 'lk-template');
        $tpl->parse('LK_CONTENT', 'lk-wrap');
        return $tpl->parse(false, 'lk-page');
    }

    //
    function loadNode($lnkData)
    {
        try {
            $cfg = $this->getCfg($lnkData['cfgName']);

            if (isset($cfg['nodes']['ot'])) {
                $parentOh = \Verba\_oh($cfg['nodes']['ot']);
                if (isset($lnkData['rq']['nodeId'])) {
                    $parentId = $lnkData['rq']['nodeId'];
                } else {
                    $parentId = $cfg['nodes']['id'];
                }
                $parentOt = $parentOh->getID();
                $group = isset($cfg['nodes']['group']) ? (bool)$cfg['nodes']['group'] : false;
            }

            if (!isset($cfg['items']['ot'])
                || !is_object($_itm = \Verba\_oh($cfg['items']['ot']))) {
                throw new \Exception('Unknown target');
            }
            $fullFamily = isset($cfg['items']['fullFamily']) ? (bool)$cfg['items']['fullFamily'] : false;
            $vltUrl = $_itm->vltURI($parentOh);

            $qm = new \Verba\QueryMaker($_itm, false, true);
            if ($parentOt) {
                $pQ = $qm->addConditionByLinkedOT($parentOt, $parentId);
                $pQ->setRootOt($parentOt);
                if (isset($cfg['items']['relation'])
                    && $cfg['items']['relation']) {
                    $pQ->setRelation($cfg['items']['relation']);
                }

            }
            $qm->addOrder(array('priority' => 'd'));

            $q = $qm->getQuery();
            $sqlr = $this->DB()->query($q);

            $r = array(
                'id' => $parentId,
                'ot' => $parentOt,
                'title' => '?',
                'nodes' => array(),
                'items' => array(),
            );

            if (!$sqlr || !$sqlr->getNumRows()) {

            } else {
                while ($row = $sqlr->fetchRow()) {
                    if (!$row['ot_id']) {
                        continue;
                    }
                    $_coh = \Verba\_oh($row['ot_id']);
                    $r['items'][$row[$_coh->getPAC()]] = array(
                        'ot' => $row['ot_id'],
                        'id' => $row[$_coh->getPAC()],
                        '_props' => $row,
                    );
                }
            }


            if ($group) {
                $br = \Verba\Branch::get_branch(array($parentOt => array('iids' => $parentId, 'aot' => $parentOt)));
                if (isset($br['pare'][$parentOt][$parentId][$parentOt])
                    && !empty($br['pare'][$parentOt][$parentId][$parentOt])) {

                    $childsParents = &$br['pare'][$parentOt][$parentId][$parentOt];

                    $ch = \Verba\Branch::get_branch(array($parentOt => array('iids' => $childsParents, 'aot' => $parentOt)));

                    $parentsData = $parentOh->getData($br['handled'][$parentOt], true);

                    foreach ($childsParents as $cpId) {
                        $r['nodes'][$cpId] = array(
                            'id' => $cpId,
                            'title' => $parentsData[$cpId]['title'],
                            'ot' => $parentsData[$cpId]['ot_id'],
                            'haveNodes' => isset($ch['pare'][$parentOt][$cpId][$parentOt]),
                        );
                    }
                }
                $r['title'] = $parentsData[$parentId]['title'];
            }

            return $r;
        } catch (\Exception $e) {
            $r = $e->getMessage();
            return $r;
        }
    }

    /**
     * put your comment there...
     *
     * @param \Act\MakeList $list
     * @return string
     */
    function makeLinkingButton($list, $buttonName, &$buttonCfg)
    {
        $url = new \Url($buttonCfg['url']);
        $params = $url->getParams();
        $this->fillPostParamsByButtonCfg($list, $buttonCfg, $params);
        $url->setParams($params);
        $urlStr = $url->get();

        // add formed data to js-worker config
        $jsWrkCfg = &$this->extractJsWorkerConfig($buttonCfg['jsWorkerClass'], $buttonCfg);
        if (is_array($jsWrkCfg)) {
            // add button class and button action url
            $jsWrkCfg['btnClass'] = $buttonCfg['class'];
            $jsWrkCfg['url'] = $urlStr;
        }
        return $urlStr;
    }

    function fillPostParamsByButtonCfg($list, $buttonCfg, &$params)
    {

        if (isset($buttonCfg['primType'])) {
            switch ($buttonCfg['primType']) {
                case 'selectionItems':
                    $params['primSlID'] = $list->getID();
                    break;
                case 'selectionParent':
                    $params['primOt'] = $list->getFirstParentOt();
                    $params['primId'] = $list->getFirstParentIid();
                    break;
            }
        }

        if (isset($buttonCfg['secType'])) {
            switch ($buttonCfg['secType']) {
                case 'selectionParent':
                    $params['secOt'] = $list->getFirstParentOt();
                    $params['secId'] = $list->getFirstParentIid();
                    break;
                case 'selectionItems':
                    $params['secSlID'] = $list->getID();
                    break;
            }
        }
    }

    function link($rq, $cfg)
    {

        return $this->handlePlainLinking('link', $rq, $cfg);
    }

    function unlink($rq, $cfg)
    {

        return $this->handlePlainLinking('unlink', $rq, $cfg);

    }

    function update($rq, $cfg)
    {

        return $this->handlePlainLinking('update', $rq, $cfg);

    }

    protected function handlePlainLinking($act, $rq, $cfg)
    {
        if (($act != 'link'
            && $act != 'unlink'
            && $act != 'update')) {
            throw  new \Verba\Exception\Building('Unknown linking method');
        }

        $cfg = $this->getCfg($cfg);
        if (!is_array($cfg)) {
            throw  new \Verba\Exception\Building('Unknown linking cfg');
        }

        list($primary, $secondary, $extData)
            = $this->extractLinkingData($rq, $cfg, $act);

        $ruleAlias = isset($cfg['rule']) ? $cfg['rule'] : false;
        $relation = isset($cfg['relation']) ? $cfg['relation'] : false;

        $extData = $this->applyExtDataHandlers($primary, $secondary, $cfg, $extData, $act);

        if ($rq instanceof \Verba\Request) {
            $rq->addTempData(array(
                'primary' => $primary,
                'secondary' => $secondary,
                'extData' => $extData
            ));
        }

        if (!$primary || !$secondary) {
            throw  new \Verba\Exception\Building('No primary or secondary items selected');
        }
        $fr = intval($relation);
        if (!$fr || $fr < 1 || $fr > 3) {
            $fr = 1;
        }

        switch ($act) {
            case 'link':
            case 'unlink':
                $mth = $act;
                break;
            case 'update':
                $mth = 'updateLink';
                break;
        }
        $r = array();

        foreach ($primary as $pot => $pIids) {
            $poh = \Verba\_oh($pot);
            foreach ($pIids as $piid) {
                $r += $poh->$mth($piid, $secondary, $ruleAlias, $fr, $extData);
            }
        }
        return $r;
    }

    function &extractJsWorkerConfig($jsWorkerClass, &$buttonCfg)
    {
        if (!is_array($buttonCfg)) {
            $r = false;
            return $r;
        }

        //if worker block missed in button cfg, add it
        if (!isset($buttonCfg['workers'])
            || !is_array($buttonCfg['workers'])
            || !count($buttonCfg['workers'])) {
            $wA = $jsWorkerClass . rand(0, 1000);
            $buttonCfg['workers'] = array(
                $wA => array(
                    '_className' => $jsWorkerClass,
                )
            );
        }
        // getting worker alias
        if (!isset($wA) || !$wA) {
            reset($buttonCfg['workers']);
            $wA = key($buttonCfg['workers']);
        };

        $r = &$buttonCfg['workers'][$wA];
        return $r;
    }

    function applyExtDataHandlers($primary, $secondary, $cfg, $extData, $action)
    {

        if (is_array($extData) && !empty($extData)
            && count($primary) == 1 && count(current($primary)) == 1
            && count($secondary) == 1
            && count(current($secondary)) == 1) {
            $pot = key($primary);
            $piid = current(current($primary));
            $sot = key($secondary);
            $siid = current(current($secondary));
        } else {
            return $extData;
        }

        $extDataHandledValues = array();
        $extDataExists = array();

        $mid = array(
            0 => \Verba\_oh($pot)->getID(),
            1 => $piid,
            2 => \Verba\_oh($sot)->getID(),
            3 => $siid,
        );

        $action = \Act\AddEditHandler::make_action_sign($action, $this->makeGID($mid));
        $_poh = \Verba\_oh($pot);
        $_soh = \Verba\_oh($sot);
        $pItem = $_poh->getData($piid, 1);
        $sItem = $_soh->getData($siid, 1);
        $ruleAlias = $cfg['rule'];
        $rule = $_poh->getRule($_soh, $ruleAlias);

        $qe = "SELECT * FROM " . $rule['uri'] . "
WHERE
`p_ot_id` = '" . $this->DB()->escape($mid[0]) . "'
&& `p_iid` = '" . $this->DB()->escape($piid) . "'
&& `ch_ot_id` = '" . $this->DB()->escape($mid[2]) . "'
&& `ch_iid` = '" . $this->DB()->escape($siid) . "'
&& `rule_alias` = '" . $this->DB()->escape($ruleAlias) . "'
";
        $sqlr = $this->DB()->query($qe);
        if ($sqlr && $sqlr->getNumRows()) {
            $extDataExists = $sqlr->fetchRow();
        }
        $extDataHandledValues = $extData;

        if (is_array($cfg['extFields']) && !empty($cfg['extFields'])) {

            foreach ($cfg['extFields'] as $fieldName => $fcfg) {
                if (!is_array($fcfg)
                    || !isset($fcfg['handlers'])
                    || !is_array($fcfg['handlers'])
                    || empty($fcfg['handlers'])) {
                    continue;
                }
                $fieldValue = isset($extData[$fieldName])
                    ? $extData[$fieldName]
                    : null;

                foreach ($fcfg['handlers'] as $handler) {
                    if (!\Hive::isModExists($handler[0])) {
                        continue;
                    }
                    $mod = \Verba\_mod($handler[0]);
                    $meth = $handler[1];
                    if (!method_exists($mod, $handler[1])) {
                        continue;
                    }

                    $fieldValue = $mod->$meth($action, $fieldName, $fieldValue, $mid, $extData, $extDataHandledValues, $extDataExists, $pItem, $sItem);
                    if ($fieldValue === null) {
                        continue;
                    }
                }
                if ($fieldValue !== null) {
                    $extDataHandledValues[$fieldName] = $fieldValue;
                }
            }
        }
        return $extDataHandledValues;
    }

    // List linking

    /**
     * put your comment there...
     *
     * @param MakeList $list
     * @return string
     */
    function handleLinkingListCallWorker($list, $buttonName, &$buttonCfg, $cfg)
    {

        if (!isset($cfg['parent']) || !is_string($cfg['parent'])) {
            $cfg['parent'] = 'secondary';
        }
        if (isset($buttonCfg['linkingalias'])) {
            $linkingAlias = $buttonCfg['linkingalias'];
        } else {
            $linkingAlias = $buttonName;
        }
        $linkingCfg = $list->getExtData('linking');
        if (!is_array($linkingCfg)) {
            $linkingCfg = array();
        }
        $linkingCfg[$linkingAlias] = $cfg;
        $list->addExtData('linking', $linkingCfg);

        $url = new \Url($buttonCfg['url']);
        $params = $url->getParams();
        $params['lalias'] = $linkingAlias;
        $params['primSlId'] = $list->getID();
        $params['primOt'] = $list->oh()->getID();
        $url->setParams($params);
        $urlStr = $url->get();

        if (!isset($buttonCfg['workers'])
            || !is_array($buttonCfg['workers'])
            || !count($buttonCfg['workers'])) {
            $wA = 'btnLinkingListCall' . rand(0, 100);
            $buttonCfg['workers'] = array(
                $wA => array(
                    'name' => 'btnLinkerListCall',
                )
            );
        }
        if (!$wA) {
            reset($buttonCfg['workers']);
            $wA = key($buttonCfg['workers']);
        };
        if (!isset($buttonCfg['workers'][$wA]['cfg']) || !is_array($buttonCfg['workers'][$wA]['cfg'])) {
            $buttonCfg['workers'][$wA]['cfg'] = array();
        }

        $buttonCfg['workers'][$wA]['cfg']['btnClass'] = $buttonCfg['class'];
        $buttonCfg['workers'][$wA]['cfg']['url'] = $urlStr;
        return $urlStr;
    }

    function handleLinkButton($list, $buttonName, &$buttonCfg)
    {

        if (isset($buttonCfg['linkingalias'])) {
            $linkingAlias = $buttonCfg['linkingalias'];
        } elseif (isset($_REQUEST['lalias'])) {
            $linkingAlias = $_REQUEST['lalias'];
        } else {
            $linkingAlias = false;
        }
        $linkingCfg = $list->getExtData('linking');
        if (!$linkingAlias || !isset($linkingCfg[$linkingAlias])) {
            return '';
        }

        $linkingCfg = $linkingCfg[$linkingAlias];

        $url = new \Url($buttonCfg['url']);

        $params = $url->getParams();
        $params['lalias'] = $linkingAlias;
        $params['primSlId'] = $linkingCfg['primSlId'];
        $params['primOtId'] = $linkingCfg['primOtId'];
        $params['secSlId'] = $linkingCfg['secSlId'];
        $params['secOtId'] = $linkingCfg['secOtId'];

        $url->setParams($params);
        $urlStr = $url->get();

        if (!isset($buttonCfg['workers'])
            || !is_array($buttonCfg['workers'])
            || !count($buttonCfg['workers'])) {
            $wA = 'btnLinkerLink' . rand(0, 100);
            $buttonCfg['workers'] = array(
                $wA => array(
                    'name' => 'btnLinkerLink',
                )
            );
        }
        if (!$wA) {
            reset($buttonCfg['workers']);
            $wA = key($buttonCfg['workers']);
        };
        if (!isset($buttonCfg['workers'][$wA]['cfg']) || !is_array($buttonCfg['workers'][$wA]['cfg'])) {
            $buttonCfg['workers'][$wA]['cfg'] = array();
        }

        $buttonCfg['workers'][$wA]['cfg']['btnClass'] = $buttonCfg['class'];
        $buttonCfg['workers'][$wA]['cfg']['url'] = $urlStr;
        return $urlStr;
    }

    function handleUnlinkButton($list, $buttonName, &$buttonCfg)
    {

        if (isset($buttonCfg['linkingalias'])) {
            $linkingAlias = $buttonCfg['linkingalias'];
        } elseif (isset($_REQUEST['lalias'])) {
            $linkingAlias = $_REQUEST['lalias'];
        } else {
            $linkingAlias = false;
        }
        $linkingCfg = $list->getExtData('linking');
        if (!$linkingAlias || !isset($linkingCfg[$linkingAlias])) {
            return '';
        }
        $linkingCfg = $linkingCfg[$linkingAlias];

        $url = new \Url($buttonCfg['url']);
        $params = $url->getParams();
        $params['lalias'] = $linkingAlias;
        $params['primSlId'] = $linkingCfg['primSlId'];
        $params['primOtId'] = $linkingCfg['primOtId'];
        $params['secSlId'] = $linkingCfg['secSlId'];
        $params['secOtId'] = $linkingCfg['secOtId'];
        $url->setParams($params);
        $urlStr = $url->get();

        if (!isset($buttonCfg['workers'])
            || !is_array($buttonCfg['workers'])
            || !count($buttonCfg['workers'])) {
            $wA = 'btnLinkerUnlink' . rand(0, 100);
            $buttonCfg['workers'] = array(
                $wA => array(
                    'name' => 'btnLinkerUnlink',
                )
            );
        }
        if (!$wA) {
            reset($buttonCfg['workers']);
            $wA = key($buttonCfg['workers']);
        };
        if (!isset($buttonCfg['workers'][$wA]['cfg']) || !is_array($buttonCfg['workers'][$wA]['cfg'])) {
            $buttonCfg['workers'][$wA]['cfg'] = array();
        }

        $buttonCfg['workers'][$wA]['cfg']['btnClass'] = $buttonCfg['class'];
        $buttonCfg['workers'][$wA]['cfg']['url'] = $urlStr;
        return $urlStr;
    }

    function linkingList($bp = null)
    {
        $primSlId = $_REQUEST['primSlId'];
        $primOtId = $_REQUEST['primOt'];
        $_prim = \Verba\_oh($primOtId);
        $pList = $_prim->initList(array(
            'listId' => $primSlId
        ));
        $pSl = $pList->Selection();
        if (!$pList || !$pSl) {
            throw new \Exception('Invalid primary selectionID');
        }
        if (!$pSl->getSelectedCount($primOtId)) {
            throw new \Exception('No primary items selected');
        }
        if (isset($_REQUEST['lalias'])) {
            $linkingAlias = $_REQUEST['lalias'];
        } else {
            $linkingAlias = false;
        }
        $linkingCfg = $pList->getExtData('linking');
        if (!$linkingAlias || !isset($linkingCfg[$linkingAlias])) {
            throw new \Exception('Linking config not found');
        }

        $linkingCfg = $linkingCfg[$linkingAlias];

        if (!is_array($linkingCfg)
            || !isset($linkingCfg['secondary'])
            || !($_sec = \Verba\_oh($linkingCfg['secondary']))
        ) {
            throw new \Exception('Bad linking params');
        }

        if (isset($linkingCfg['mod-action'])) {
            if (!is_array($linkingCfg['mod-action'])
                || !isset($linkingCfg['mod-action'][0])
                || !($mod = \Verba\_mod($linkingCfg['mod-action'][0]))
            ) {
                throw new \Exception('Bad secondary list generator');
            }
            $sec_bp = isset($linkingCfg['mod-action']['bp']) && is_array($linkingCfg['mod-action']['bp'])
                ? $linkingCfg['mod-action']['bp']
                : array();
            $sec_bp['ot_id'] = $_sec->getID();
            $sList = $mod->dispatcher($sec_bp);
        } else {
            if (isset($linkingCfg['secListCfg'])) {
                $sBp = array('cfg' => $linkingCfg['secListCfg']);
            } else {
                $sBp = null;
            }
            $sList = $_sec->initList($sBp);
        }

        if (!$sList) {
            throw new \Exception('Unable to init secondary list');
        }

        $sList->addExtData('linking', array(
                $linkingAlias => array(
                    'primSlId' => $primSlId,
                    'primOtId' => $primOtId,
                    'secSlId' => $sList->getID(),
                    'secOtId' => $_sec->getID(),
                ))
        );

        if (!$sList->validateAccess()) {
            throw new \Exception('Access denied');
        }
        $sList->setEditIdOver('get');

        $l = $sList->generateList();
        $q = $sList->QM()->getQuery();
        return $l;
    }

    /*  function link($bp = null){
        try{
          $r = $this->handleLinking($_REQUEST['secSlId'], $_REQUEST['primSlId'], 'link');
          return \Verba\Response\Json::wrap(true, $r);
        }catch(Exception $e){
          return \Verba\Response\Json::wrap(false, $e->getMessage());
        }
      }

      function unlink($bp = null){
        try{
          $r = $this->handleLinking($_REQUEST['secSlId'], $_REQUEST['primSlId'], 'unlink');
          return \Verba\Response\Json::wrap(true, $r);
        }catch(Exception $e){
          return \Verba\Response\Json::wrap(false, $e->getMessage());
        }
      }*/

    protected function handleLinking($secSlId, $primSlId, $mth)
    {
        if (($mth != 'link' && $mth != 'unlink')) {
            throw new \Exception('Unknown linking method');
        }

        $primOtId = $_REQUEST['primOtId'];
        $_prim = \Verba\_oh($primOtId);
        $pList = $_prim->initList(array('listId' => $primSlId));

        $pSl = $pList->Selection();

        if (isset($_REQUEST['lalias'])) {
            $linkingAlias = $_REQUEST['lalias'];
        } else {
            $linkingAlias = false;
        }
        $linkingCfg = $pList->getExtData('linking');
        if (!$linkingAlias || !isset($linkingCfg[$linkingAlias])) {
            throw new \Exception('Linking config not found');
        }
        $linkingCfg = $linkingCfg[$linkingAlias];
        if (!isset($linkingCfg['parent'])
            && !($linkingCfg['parent'] == 'primary' || $linkingCfg['parent'] == 'secondary')) {
            $parent = 'secondary';
        } else {
            $parent = $linkingCfg['parent'];
        }

        $secOtId = $_REQUEST['secOtId'];
        $_sec = \Verba\_oh($secOtId);
        $sList = $_sec->initList(array('listId' => $secSlId));

        $sSl = $sList->Selection();

        if (!$pList || !$pSl || !$sList || !$sSl) {
            throw new \Exception('Invalid primary or secondary selection');
        }
        if (!$pSl->getSelectedCount($primOtId) || !$sSl->getSelectedCount($secOtId)) {
            throw new \Exception('No primary or secondary items selected');
        }

        if ($parent == 'secondary') {
            $cItems = array($primOtId => $pSl->getSelected($primOtId));
            $pItems = $sSl->getSelected($secOtId);
            $_parent = $_sec;
        } else {
            $cItems = array($secOtId => $sSl->getSelected($secOtId));
            $pItems = $pSl->getSelected($primOtId);
            $_parent = $_prim;
        }
        $r = 0;
        foreach ($pItems as $pId) {
            $r += $_parent->$mth($pId, $cItems);
        }
        return $r;
    }

    // Link items to parent

    /**
     * @param \Act\MakeList $list
     * @return string
     */
    function makeBrowseLinkingListUrl($list, $buttonName, &$buttonCfg, $cfg)
    {

        if (!isset($cfg['parent']) || !is_string($cfg['parent'])) {
            $cfg['parent'] = 'secondary';
        }
        if (isset($buttonCfg['linkingalias'])) {
            $linkingAlias = $buttonCfg['linkingalias'];
        } else {
            $linkingAlias = $buttonName;
        }
        $linkingCfg = $list->getExtData('linking');
        if (!is_array($linkingCfg)) {
            $linkingCfg = array();
        }
        $cfg['parentOt'] = $list->getFirstParentOt();
        $cfg['parentIid'] = $list->getFirstParentIid();
        $cfg['primOtId'] = $list->getOtId();
        $cfg['secOtId'] = \Verba\_oh($cfg['secondary'])->getId();
        $cfg['primSlId'] = $list->getId();

        $linkingCfg[$linkingAlias] = $cfg;
        $list->addExtData('linking', $linkingCfg);

        $url = new \Url($buttonCfg['url']);
        $params = $url->getParams();
        $params['lalias'] = $linkingAlias;
        $params['primSlId'] = $list->getId();
        $params['primOtId'] = $list->getOtId();
        $url->setParams($params);
        $urlStr = $url->get();

        if (!isset($buttonCfg['workers'])
            || !is_array($buttonCfg['workers'])
            || !count($buttonCfg['workers'])) {
            $wA = 'btnLinkerBrowseListCall' . rand(0, 100);
            $buttonCfg['workers'] = array(
                $wA => array(
                    'name' => 'btnLinkerBrowseListCall',
                )
            );
        }
        if (!$wA) {
            reset($buttonCfg['workers']);
            $wA = key($buttonCfg['workers']);
        };
        if (!isset($buttonCfg['workers'][$wA]['cfg']) || !is_array($buttonCfg['workers'][$wA]['cfg'])) {
            $buttonCfg['workers'][$wA]['cfg'] = array();
        }

        $buttonCfg['workers'][$wA]['cfg']['btnClass'] = $buttonCfg['class'];
        $buttonCfg['workers'][$wA]['cfg']['url'] = $urlStr;
        return $urlStr;
    }

    function browseList($bp = null)
    {
        $primSlId = $_REQUEST['primSlId'];
        $primOtId = $_REQUEST['primOtId'];

        $_prim = \Verba\_oh($primOtId);
        $pList = $_prim->initList(array('listId' => $primSlId));

        $pSl = $pList->Selection();
        if (!$pList || !$pSl) {
            throw new \Exception('Invalid primary selectionID');
        }

        if (isset($_REQUEST['lalias'])) {
            $linkingAlias = $_REQUEST['lalias'];
        } else {
            $linkingAlias = false;
        }
        $linkingCfg = $pList->getExtData('linking');
        if (!$linkingAlias || !isset($linkingCfg[$linkingAlias])) {
            throw new \Exception('Linking config not found');
        }

        $linkingCfg = $linkingCfg[$linkingAlias];

        if (!$linkingCfg['parentOt']
            || !$linkingCfg['parentIid']) {
            throw new \Exception('No primary items detected');
        }

        if (!is_array($linkingCfg)
            || !isset($linkingCfg['secondary'])
            || !($_sec = \Verba\_oh($linkingCfg['secondary']))
        ) {
            throw new \Exception('Bad linking params');
        }

        if (isset($linkingCfg['mod-action'])) {
            if (!is_array($linkingCfg['mod-action'])
                || !isset($linkingCfg['mod-action'][0])
                || !($mod = \Verba\_mod($linkingCfg['mod-action'][0]))
            ) {
                throw new \Exception('Bad secondary list generator');
            }
            $sec_bp = isset($linkingCfg['mod-action']['bp']) && is_array($linkingCfg['mod-action']['bp'])
                ? $linkingCfg['mod-action']['bp']
                : array();
            $sec_bp['ot_id'] = $_sec->getID();
            $sList = $mod->dispatcher($sec_bp);
            $sBp = null;
        } else {
            if (isset($linkingCfg['secListCfg'])) {
                $sBp = array('cfg' => $linkingCfg['secListCfg']);
            } else {
                $sBp = null;
            }
            $sList = $_sec->initList($sBp);
        }

        if (!$sList) {
            throw new \Exception('Unable to init secondary list');
        }

        $sList->addExtData('linking', array(
                $linkingAlias => array(
                    'primSlId' => $primSlId,
                    'primOtId' => $primOtId,
                    'secSlId' => $sList->getID(),
                    'secOtId' => $_sec->getID(),
                    'parentOt' => $linkingCfg['parentOt'],
                    'parentIid' => $linkingCfg['parentIid'],
                ))
        );

        if (!$sList->validateAccess()) {
            throw new \Exception('Access denied');
        }
        $sList->setEditIdOver('get');

        $l = $sList->generateList();
        $q = $sList->QM()->getQuery();
        return $l;
    }

    function handleLinkParentButton($list, $buttonName, &$buttonCfg)
    {

        if (isset($buttonCfg['linkingalias'])) {
            $linkingAlias = $buttonCfg['linkingalias'];
        } elseif (isset($_REQUEST['lalias'])) {
            $linkingAlias = $_REQUEST['lalias'];
        } else {
            $linkingAlias = $buttonName;
        }
        $linkingCfg = $list->getExtData('linking');
        if (!$linkingAlias || !isset($linkingCfg[$linkingAlias])) {
            return '';
        }

        $linkingCfg = $linkingCfg[$linkingAlias];

        $url = new \Url($buttonCfg['url']);

        $params = $url->getParams();
        $params['lalias'] = $linkingAlias;
        $params['primSlId'] = $linkingCfg['primSlId'];
        $params['primOtId'] = $linkingCfg['primOtId'];
        $params['secSlId'] = $linkingCfg['secSlId'];
        $params['secOtId'] = $linkingCfg['secOtId'];

        $url->setParams($params);
        $urlStr = $url->get();

        if (!isset($buttonCfg['workers'])
            || !is_array($buttonCfg['workers'])
            || !count($buttonCfg['workers'])) {
            $wA = 'btnLinkerParentLink' . rand(0, 100);
            $buttonCfg['workers'] = array(
                $wA => array(
                    'name' => 'btnLinkerParentLink',
                )
            );
        }
        if (!$wA) {
            reset($buttonCfg['workers']);
            $wA = key($buttonCfg['workers']);
        };
        if (!isset($buttonCfg['workers'][$wA]['cfg']) || !is_array($buttonCfg['workers'][$wA]['cfg'])) {
            $buttonCfg['workers'][$wA]['cfg'] = array();
        }

        $buttonCfg['workers'][$wA]['cfg']['btnClass'] = $buttonCfg['class'];
        $buttonCfg['workers'][$wA]['cfg']['url'] = $urlStr;
        return $urlStr;
    }

    function handleUnlinkParentButton($list, $buttonName, &$buttonCfg)
    {

        if (isset($buttonCfg['linkingalias'])) {
            $linkingAlias = $buttonCfg['linkingalias'];
        } elseif (isset($_REQUEST['lalias'])) {
            $linkingAlias = $_REQUEST['lalias'];
        } else {
            $linkingAlias = false;
        }
        $linkingCfg = $list->getExtData('linking');
        if (!$linkingAlias || !isset($linkingCfg[$linkingAlias])) {
            return '';
        }
        $linkingCfg = $linkingCfg[$linkingAlias];

        $url = new \Url($buttonCfg['url']);
        $params = $url->getParams();
        $params['lalias'] = $linkingAlias;
        $params['primSlId'] = $linkingCfg['primSlId'];
        $params['primOtId'] = $linkingCfg['primOtId'];
        $params['secSlId'] = $linkingCfg['primSlId'];
        $params['secOtId'] = $linkingCfg['primOtId'];
        $url->setParams($params);
        $urlStr = $url->get();

        if (!isset($buttonCfg['workers'])
            || !is_array($buttonCfg['workers'])
            || !count($buttonCfg['workers'])) {
            $wA = 'btnLinkerUnlink' . rand(0, 100);
            $buttonCfg['workers'] = array(
                $wA => array(
                    'name' => 'btnLinkerUnlink',
                )
            );
        }
        if (!$wA) {
            reset($buttonCfg['workers']);
            $wA = key($buttonCfg['workers']);
        };
        if (!isset($buttonCfg['workers'][$wA]['cfg']) || !is_array($buttonCfg['workers'][$wA]['cfg'])) {
            $buttonCfg['workers'][$wA]['cfg'] = array();
        }

        $buttonCfg['workers'][$wA]['cfg']['btnClass'] = $buttonCfg['class'];
        $buttonCfg['workers'][$wA]['cfg']['url'] = $urlStr;
        return $urlStr;
    }

    function parentLink($bp = null)
    {
        try {
            $r = $this->handleParentLinking($_REQUEST['secSlId'], $_REQUEST['primSlId'], 'link');
            return \Verba\Response\Json::wrap(true, $r);
        } catch (\Exception $e) {
            return \Verba\Response\Json::wrap(false, $e->getMessage());
        }
    }

    function parentUnlink($bp = null)
    {
        try {
            $r = $this->handleParentLinking($_REQUEST['secSlId'], $_REQUEST['primSlId'], 'unlink');
            return \Verba\Response\Json::wrap(true, $r);
        } catch (\Exception $e) {
            return \Verba\Response\Json::wrap(false, $e->getMessage());
        }
    }

    protected function handleParentLinking($secSlId, $primSlId, $mth)
    {
        if (($mth != 'link' && $mth != 'unlink')) {
            throw new \Exception('Unknown linking method');
        }

        $primOtId = $_REQUEST['primOtId'];
        $_prim = \Verba\_oh($primOtId);

        $pList = $_prim->initList(array('listId' => $primSlId));
        $pSl = $pList->Selection();

        if (isset($_REQUEST['lalias'])) {
            $linkingAlias = $_REQUEST['lalias'];
        } else {
            $linkingAlias = false;
        }
        $linkingCfg = $pList->getExtData('linking');
        if (!$linkingAlias || !isset($linkingCfg[$linkingAlias])) {
            throw new \Exception('Linking config not found');
        }
        $linkingCfg = $linkingCfg[$linkingAlias];
        if (!isset($linkingCfg['parent'])
            && !($linkingCfg['parent'] == 'primary' || $linkingCfg['parent'] == 'secondary')) {
            $parent = 'secondary';
        } else {
            $parent = $linkingCfg['parent'];
        }

        $secOtId = $_REQUEST['secOtId'];
        $_sec = \Verba\_oh($secOtId);
        $sList = $_sec->initList(array('listId' => $secSlId));

        $sSl = $sList->Selection();

        if (!$pList || !$pSl || !$sList || !$sSl) {
            throw new \Exception('Invalid primary or secondary selection');
        }
        if (!$sSl->getSelectedCount($secOtId)) {
            throw new \Exception('No primary or secondary items selected');
        }

        if ($linkingCfg['relation']) {
            $relation = $linkingCfg['relation'];
        } else {
            $relation = null;
        }

        $cItems = array($secOtId => $sSl->getSelected($secOtId));
        $pId = $linkingCfg['parentIid'];
        $r = $_prim->$mth($pId, $cItems, $relation);

        return $r;
    }

    // tools
    function relink()
    {
//    $_cat = \Verba\_oh('catalog');
//    $_prod = \Verba\_oh('product');
//    $q = "SELECT ".$_prod->getPAC().", `parentId`, `ot_id`, catalogId FROM ".$_prod->vltURI();
//    $sqlr = $this->DB()->query($q);
//    $lvlt = $_prod->vltURI($_cat);
//    while($row = $sqlr->fetchRow()){
//      if(!$row['catalogId'] || $row['parentId']){
//        continue;
//      }
//      $qi = "INSERT INTO ".$lvlt." (`p_ot_id`, `ch_ot_id`, `p_iid`, `ch_iid`) VALUES ("
//      . "'52', '".$row['ot_id']."', '".$row['catalogId']."', '".$row[$_prod->getPAC()]."')";
//      $this->DB()->query($qi);
//    }
//    return false;
    }

}

Links::$_config_default = array(
    'primary' => array(
        'ot' => false,
        'items' => array(),
    ),
    'secondary' => array(
        'ot' => false,
        'items' => array(),
    ),
    'rule' => false,
    'relation' => false,
);
