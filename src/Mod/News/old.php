<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 17:03
 */
namespace Mod;

class News extends \Verba\Mod{

    protected $valid_objects = array('news');

    function makeAction(&$BParams){
        switch($BParams['action']){
            case 'new'  :
            case 'edit'  :
                $handler = 'addEditForm';  break;

            case 'newnow' :
            case 'editnow':
                $handler = 'addEditNow'; break;

            case 'show':
                $handler = 'Show';break;

            case 'delete':
                $handler = 'deleteNow';  break;

            default:
                $handler = false;
                break;
        }
        return $handler;
    }

    function getLatestNews($num = 10){
        global $S;
        $_news = \Verba\_oh('news');
        $num = isset($num) && is_numeric($num) && $num > 0 ? $num : 10;
        $QM_news = new \Verba\QueryMaker($_news, false, true);
        $QM_news->addOrder(array($_news->getOT()->code2id("creation_date")=>"d"));
        $QM_news->addLimit($num);
        $QM_news->setQuery();
        return $this->DB()->query($QM_news->query());
    }

    function addEditForm($BParams = null, $cfg = '', $url = array()){
        $oh = \Verba\_oh('news');
        $url = (array)$url;
        list($action, $Faction) = AddEditHandlers::extractAEFActionsFromURL($BParams['action']);
        $cfg = empty($cfg) ? 'acp-news acp-news' : (string)$cfg;
        $cfg = $cfg.'-'.$action;
        $iid = $this->extractID($BParams);

        $form = $oh->initForm($action, $iid, $cfg, $BParams['pot'], $BParams['piid'], $oh->getBaseKey());

        $cfg = $this->gC('aeform');
        if($cfg['tags']){
            $form->setVirtualComponent('Tags', 'UniAddEditForm', false, $BParams, $action, $oh->getID(), $iid);
        }
        if($url['fbUrl']){
            $form->setBackUrl($url['fbUrl']);
        }
        return $form->makeForm();


        if($this->gC('captcha', $form->getAction()) && !$S->U()->in_group(USR_ADMIN_GROUP_ID)){
            $form->setVirtualComponent('Captcha', 'captchaFormElement', false, array('ot_id' => $oh->getID()));
        }
    }

    function addEditNow($BParams = false, $data = false){
        $oh = \Verba\_oh('news');
        list($action, $faction) = AddEditHandlers::extractAEFActionsFromURL($BParams['action']);
        $iid  = $this->extractID($BParams);
        $data = is_array($data)
            ? $data
            : $_REQUEST['NewObject'][$oh->getID()];

        $ae = $oh->initAddEdit(array(
            'action' => $action,
            'key_id' => $oh->getBaseKey(),
            'iid' => $iid,
        ));

        $cfg = $this->gC();

        if(isset($BParams['pot'])){
            $ae->addMultipleParents($BParams['pot']);
        }
        $ae->setGettedObjectData($data);
        $r = $ae->addedit_object();
        return $ae;
    }

    function deleteNow($BParams = null){
        global $S;
        $_oh = \Verba\_oh('news');
        if(!$S->U()->chr($_oh->getBaseKey(), 'd')){
            throw  new \Verba\Exception\Building('Dont have permission');
        }
        $dh = $_oh->initDelete();
        $result = $dh->delete_objects();
        if($result['obj'] > 0 || $result['lnk'] > 0){
            throw  new \Verba\Exception\Building('Empty result');
        }
        $this->log()->event(lang::get('delete fault'));

        return resultReport(false, 30);
    }

    function getData($iids){
        if(!\Verba\reductionToArray($iids)){
            return false;
        }
        $iids_str = "'".implode(",'", $iids)."'";
        $_image = \Verba\_oh('image');
        $_news = \Verba\_oh('news');
        $_tags = \Verba\_oh('tags');
        $qm = new \Verba\QueryMaker($_news, false, true);

        list($alias, $table) = $qm->createAlias($_news->vltT());
        list($ialias, $itable) = $qm->createAlias($_image->vltT());
        list($ilalias, $iltable) = $qm->createAlias($_news->vltT($_image->getID()));

        $qm->addSelect('GROUP_CONCAT(DISTINCT CONCAT_WS(\':\',
    CAST(`'.$ialias.'`.`id` AS CHAR),
    CAST(`'.$ialias.'`.`priority` AS CHAR),
    CAST(`'.$ialias.'`.`_storage_file_name_config` AS CHAR),
    `'.$ialias.'`.`storage_file_name`))', false, 'image_data', true);
        $qm->addCJoin(array(array('a' => $ilalias)),
            array(
                array('p' => array('a'=> $ilalias, 'f' => 'p_iid'),
                    's' => array('a'=> $alias, 'f' => $_news->getPAC())),
                array('p' => array('a'=> $ilalias, 'f' => 'p_ot_id'),
                    's' => $_news->getID()),
                array('p' => array('a'=> $ilalias, 'f' => 'ch_ot_id'),
                    's' => $_image->getID())));
        $qm->addCJoin(array(array('a' => $ialias)),
            array(
                array('p' => array('a'=> $ialias, 'f' => $_image->getPAC()),
                    's' => array('t'=> $iltable, 'f' => 'ch_iid'))));

        list($tlalias, $tltable) = $qm->createAlias($_news->vltT($_tags->getID()));
        list($talias, $ttable) = $qm->createAlias($_tags->vltT());
        $qm->addSelect('GROUP_CONCAT(DISTINCT CONCAT_WS(\':\',
      CAST(`'.$talias.'`.`'.$_tags->getPAC().'` AS CHAR),
      `'.$talias.'`.`tag`))', false, 'tags_data', true);
        $qm->addCJoin(array(array('a' => $tlalias)),
            array(
                array('p' => array('a'=> $tlalias, 'f' => 'p_iid'),
                    's' => array('a'=> $alias, 'f' => $_news->getPAC())),
                array('p' => array('a'=> $tlalias, 'f' => 'p_ot_id'),
                    's' => $_news->getID()),
                array('p' => array('a'=> $tlalias, 'f' => 'ch_ot_id'),
                    's' => $_tags->getID())));

        $qm->addCJoin(array(array('a' => $talias)),
            array(
                array('p' => array('a'=> $talias, 'f' => $_tags->getPAC()),
                    's' => array('t'=> $tltable, 'f' => 'ch_iid'))));
        $qm->addOrder(array('priority' => 'a'), false, array($table, null, $alias));
        $qm->addGroupBy($_news->getPAC());
        $qm->addWhere("`$alias`.`".$_news->getPAC()."` IN ($iids_str)");

        $r = array();
        $q = $qm->getQuery();

        if(false == ($sqlr = $this->DB()->query($q)) || !$sqlr->getNumRows()){
            return $r;
        }
        $mImage = \Verba\_mod('image');
        while($row = $sqlr->fetchRow()){
            $r[$_news->getPAC()] = $row;
            $r[$_news->getPAC()]['image_data'] = $mImage->getImgDataFromString($row['image_data']);
        }

        return count($iids) < 2 ? $r[$_news->getPAC()] : $r;
    }
}

class NewsPublic extends \Verba\Mod{

    function makeAction(&$bp){
        $iid = $this->extractID($bp);
        if($iid){
            $handler = 'show';
            $bp['iid'] = $iid;
        }
        if(isset($handler)){
            return $handler;
        }
        switch($bp['action']){
            default:
                $handler = 'publicList';
                break;
        }
        return $handler;
    }

//  function publicList($bp = null){
//    $bp = $this->extractBParams($bp);
//    $_news = \Verba\_oh('news');
//    $listId = isset($bp['listId']) ? $bp['listId'] : 'n'.$_news->getID();
//    $list = init_list_free($_news->getID(), $listId);
//    $cfgStr = isset($bp['cfg']) ? $bp['cfg'] : 'public news';
//    $list->applyConfig($cfgStr);

//    $list->QM()->addWhere(date('Y-m-d H:m:s'), 'date', 'date', $table, '<=');
//    $list->QM()->addWhere(1, 'active');

//    if(isset($bp['limit'])){
//      $list->QM()->addLimit($bp['limit']);
//    }

//    $html = $list->generateList();
//    $q = $list->QM()->getQuery();
//    return $html;
//  }

    function parseLastNews($bp = null){

        $html = $this->publicList(array(
            'cfg' => 'public news news-index',
            'listId' => 'lastNews',
            'limit' => 5
        ));

        return $html;
    }

    function show($bp = null, $item = array()){
        $bp = $this->extractBParams($bp);
        if(!$bp['iid']){
            return '';
        }
        $tpl = $this->tpl();
        $_news = \Verba\_oh('news');
        $listId = 'n'.$_news->getID();
        $tpl->define(array(
            'news_item' => '/news/public/show/item.tpl',
        ));
        if(empty($item)){
            $item = $_news->getData($bp['iid'], 1);
        }
        if(!$item){
            return '';
        }
        $timestamp = strtotime($item['date']);
        if($timestamp){
            $date = ((int)strftime("%d",$timestamp)).strftime(".%m.%Y",$timestamp);
        }else{
            $timestamp = '';
        }

        $tpl->assign(array(
            'ITEM_TITLE' => $item['title_'.SYS_LOCALE],
            'ITEM_DATE' => $date,
            'ITEM_TEXT' => isset($item['text_'.SYS_LOCALE]) && !empty($item['text_'.SYS_LOCALE]) ? $item['text_'.SYS_LOCALE] : '',
        ));

        $navCfg = array(
            'item' => $item,
            'listId' => $listId,
            'attrs' => array(
                'title', 'url_code', 'picture', '_picture_config','date'
            ),
            'hrefHandler' => array(
                0 => '\Mod\Seo::idToSeoStr',
            )
        );

        $tpl->assign(array(
            'NAVIGATION_OVER_LIST' => $this->makeItemNavigation($_news->getID(), $navCfg)
        ));
        $mMenu = \Verba\_mod('menupublic');
        $mMenu->addMenuChain(array(
            'ot_id' => $_news->getID(),
            $_news->getPAC() => $item[$_news->getPAC()],
            'title' => $item['title_'.SYS_LOCALE],
        ));

        return $tpl->parse(false, 'news_item');
    }

    function makeItemNavigation($ot, $cfg = array()){
        $tpl = $this->tpl();
        $cfg = (array)$cfg;

        $slId = isset($cfg['listId']) ? $cfg['listId'] : $_REQUEST['slID'];
        $oh = \Verba\_oh($ot);

        $cp = $_REQUEST['seq'];
        $cp = (int)$cp;
        if(!is_numeric($cp) || $cp <= 0){
            return false;
        }
        $sl = \Verba\init_selection(false, false, $slId);

        if(!is_object($sl)){
            return '';
        }
        if(isset($cfg['attrs']) && is_array($cfg['attrs'])){
            foreach($cfg['attrs'] as $attrCode){
                $sl->QM->addSelect($attrCode);
            }
        }
        $total = $sl->get_count_v();
        $q = 4;


        if($cp == 1){
            $start = 0;
            $n = ($q/2)+1;
        }elseif($cp == $q/2){

            $start = 0;
            $n = $q;

        }elseif($cp < $q){

            $start = 0;
            $n = $cp + ($q/2);

        }elseif($total - $cp >= $q/2){

            $start = ($cp - $q/2) - 1;
            $n = $q+1;

        }elseif($total - $cp < $q/2 && $total != $cp){

            $start = $cp - ($q/2+1);
            $n = $q;

        }elseif($total == $cp){
            $start = $cp - ($q/2) - 1;
            $n = ($q/2)+1;
        }
        $sl->QM->addLimit($n, $start);
        $sl->refreshOrder();
        $sl->QM->makeQuery();
        $q = $sl->QM->getQuery();
        $sqlr = $this->DB()->query($q);
        $found = $sqlr->getNumRows();
        if(!is_object($sqlr) || $found == 1){
            return '';
        }
        $pac = $oh->getPAC();
        $cIid = $cfg['item'][$pac];
        $data = array();
        $np = null;
        $i = 0;
        while($row = $sqlr->fetchRow()){
            if($row[$pac] == $cIid){
                $np = $cp;
                continue;
            }
            if(!$np){
                $unassigned[] = $row[$pac];
                $i++;
            }

            $data[$row[$pac]] = $row;
            $data[$row[$pac]]['__sec'] = $np == null ? $np : ++$np;
        }
        for($np = $cp;$i>0;$i--){
            $ci = array_pop($unassigned);
            $data[$ci]['__sec'] = --$np;
        }
        if(!count($data)){
            return '';
        }

        $tpl->define(array(
            'prev-next-wrap' => 'news/public/show/prev-next/wrap.tpl',
            'prev-next-item' => 'news/public/show/prev-next/item.tpl',
        ));

        foreach($data as $cid => $citem){
            $date = strtotime($citem['date']);
            $url = \Mod\Seo::idToSeoStr($citem,array('seq'=>$citem['__sec']));

            if(!empty($citem['picture']) && !empty($citem['_picture_config'])){
                $imgCfg = \Verba\_mod('image')->getImageConfig($citem['_picture_config']);
                $imgUrl = $imgCfg->getFullUrl(basename($citem['picture']), 'thumb');
            }else{
                $imgUrl = SYS_IMAGES_URL.'/1px.gif';
            }
            $tpl->assign(array(
                'PN_ITEM_PAGE_URL' => $url,
                'PN_ITEM_TITLE' => $citem['title_'.SYS_LOCALE],
                'PN_ITEM_PICTURE_URL' => $imgUrl,
                'PN_ITEM_DATE' => date("j.m.Y", $date),
            ));

            $tpl->parse('PREV_NEXT_ITEMS', 'prev-next-item', true);
        }


        return $tpl->parse(false, 'prev-next-wrap');
    }

    /**
     * put your comment there...
     *
     * @param MakeList $list
     * @param mixed $row
     */
    function handleListRow($list, $row){

        $tpl = $this->tpl();
        $date = strtotime($row['date']);
        $url = \Mod\Seo::idToSeoStr($row,array('seq'=>$list->getCurrentPos()));

        if(!empty($row['picture']) && !empty($row['_picture_config'])){
            $imgCfg = \Verba\_mod('image')->getImageConfig($row['_picture_config']);
            if($list->getCurrentPos() == 1){
                $imgUrl = $imgCfg->getFullUrl(basename($row['picture']), 'thumbBig');
            }else{
                $imgUrl = $imgCfg->getFullUrl(basename($row['picture']), 'thumb');
            }
        }else{
            $imgUrl = SYS_IMAGES_URL.'/1px.gif';
        }
        $tpl->assign(array(
            'ITEM_PAGE_URL' => $url,
            'ITEM_TITLE' => $row['title_'.SYS_LOCALE],
            'ITEM_PICTURE_URL' => $imgUrl,
            '_ITEM_DATE' => date("j.m.Y", $date),
            'ITEM_PREVIEW' => isset($row['text_preview_'.SYS_LOCALE]) && !empty($row['text_preview_'.SYS_LOCALE]) ? $row['text_preview_'.SYS_LOCALE] : '',
        ));
        return;
    }
}


class NewsAdmin extends \Verba\Mod{

    function makeAction($bp = false){
        global $S;
        switch($S->url_fragments[1]){
            case 'list':
                $handler = 'manageList';
                break;
            case 'image':
                $handler = 'imageRedirector';
                break;
            default :
                $handler = null;
        }
        if(!$handler){
            $handler = parent::makeAction($bp);
        }
        return $handler;
    }

    function manageList($bp = null){
        $bp['ot_code'] = 'news';
        if(!isset($bp['cfg']) || !is_string($bp['cfg'])){
            $bp['cfg'] = 'acp-news';
        }
        return $this->listJson($bp);
    }

    function imageRedirector($bp = null){
        global $S;
        $bp['ot_code'] = 'image';
        switch($S->url_fragments[2]){
            case null:
            case '':
            case 'list':
                $bp['action'] = 'list';
                $bp['cfg'] = 'acp-images acp-news-images';
                break;
            case 'cuform':
                if(isset($bp['iid']) && !empty($bp['iid'])){
                    $bp['action'] = 'updateform';
                }else{
                    $bp['action'] = 'createform';
                }
                $bp['cfg'] = 'acp-image-builtin acp-image-builtin-'.$bp['action'].' acp-news-image acp-news-image-'.$bp['action'];
                break;
            case 'create':
            case 'update':
                $bp['action'] = $S->url_fragments[2];
                break;
            case 'remove':
                $bp['action'] = 'remove';
                break;
        }
        $Mod = \Verba\_mod('imageAdmin');
        $r = $Mod->dispatcher($bp);
        return $r;
    }

}