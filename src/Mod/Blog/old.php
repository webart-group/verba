<?php
namespace Mod;

class BlogPublic extends \Verba\Mod{

    function makeAction($bp = false){
        $this->extractBParams($bp);
        if($bp['iid'] || $bp['action'] == 'item'){
            $handler = 'show';
        }

        if(isset($handler)){
            return $handler;
        }

        switch($bp['action']){
            default:
            case 'list':
                $handler = 'blogCat';
                break;
        }
        return $handler;
    }

    function blogCat($bp){
        $bp = $this->extractBParams($bp);
        $_catalog = \Verba\_oh('catalog');
        $tpl = $this->tpl();
        $mCat = \Verba\_mod('catalogpublic');
        $catsData = $mCat->getCatsChain($bp['iid'], 0);
        if(!$catsData){
            $tpl->define(array('error' => 'common/error.tpl'));
            $tpl->assign(array(
                'ERROR_TEXT' => \Verba\Lang::get('common not_found'),
            ));
            return $tpl->parse(false, 'error');
        }
        $mCat->addCatsToBreadcrumbs($catsData, '');
        $currentCat = end($catsData);
        $r = $this->publicList(array(
            'pot' => $_catalog->getID(),
            'piid' => $currentCat[$_catalog->getPAC()]
        ));
        return $r;
    }

    function publicList($bp = null){
        $catData = \Verba\_mod('catalog')->getItem($bp['piid']);
        if(!$catData){
            return '';
        }
        $catData = $catData->toArray();
        $pot = $bp['pot'];
        $piid = $bp['piid'];
        unset($bp['pot'], $bp['piid']);
        $cacheKey ='bl'.$pot.'dw'.$piid;
        $_blog = \Verba\_oh('blog');

        $_catalog = \Verba\_oh('catalog');
        $_image = \Verba\_oh('image');

        $this->tpl()->define(array(
            'subst_image' => 'blog/list/subst.image.tpl'
        ));

        $branch = \Verba\Branch::get_branch(array($_catalog->getID() => array('aot' => array($_catalog->getID()), 'iids'=> array($piid))), 'down', 3, true, false);
        $catIids = array($piid);
        $bp['ot_id'] = $_blog->getID();
        $list = init_list_free($_blog->getID(), $cacheKey, $bp);
        $list->applyConfig('public public-blog');
        $qm = $list->QM();
        list($palias, $ptable, $db) = $qm->createAlias($_blog->vltT());
        $qm->addGroupBy(array('id'));
        $qm->addWhere($palias.'.`active` > 0');
        $qm->addWhere($palias.'.`catalogId` IN ('.implode(', ', $catIids).')');
        $qm->addWhere('(unix_timestamp(`'.$a.'`.`date`) - unix_timestamp(now())) <= 0');

        \Verba\Hive::setBackURL();

        $html = $list->generateList();
        $q = $qm->getQuery();
        return $html;
    }

    function show($bp = null, $item = array()){
        $bp = $this->extractBParams($bp);
        if(!$bp['iid']){
            return '';
        }
        $tpl = $this->tpl();
        $oh = \Verba\_oh('blog');
        $listId = $_REQUEST['slID'];
        $tpl->define(array(
            'item' => '/blog/show/item.tpl',
        ));
        if(empty($item)){
            $qm = new \Verba\QueryMaker($oh, false, true);
            list($a) = $qm->createAlias();
            $qm->addWhere(1, 'active');
            $qm->addWhere($bp['iid'], $oh->getPAC());
            $qm->addWhere('(unix_timestamp(`'.$a.'`.`date`) - unix_timestamp(now())) <= 0');
            $q = $qm->getQuery();
            $sqlr = $this->DB()->query($q);
            if(!$sqlr || !$sqlr->getNumRows()){
                $item = false;
            }else{
                $item = $sqlr->fetchRow();
            }
        }

        if(!$item || !$item['active']){
            return '';
        }

        $txt = isset($item['text']) && !empty($item['text']) ? $item['text'] : '';
        $socImage = \Mod\Seo::extractImageUrlFromText($txt);
        \Mod\Seo::addOtags($item['title'], $socImage);

        $url = new \Url(\Mod\Seo::idToSeoStr($item));
        $tpl->assign(array(
            'ITEM_TITLE' => htmlspecialchars($item['title']),
            'ITEM_DATE' =>  strftime('%d.%m.%Y', strtotime($item['date'])),
            'ITEM_TEXT' => $txt,
            'ITEM_ID' => $bp['iid'],
            'ITEM_CLEAR_URL' => $url->get(true),
        ));
        $mCat = \Verba\_mod('catalogpublic');
        $catsData = $mCat->getCatsChain($item['catalogId'], 0);
        //$mCat->addCatsToBreadcrumbs($catsData, '');

        $mMenu = \Verba\_mod('menupublic');
        $mMenu->addMenuChain(array(
            'ot_id' => $oh->getID(),
            $oh->getPAC() => $item[$oh->getPAC()],
            'title' => $item['title'],
        ));

        if($listId){
            $navCfg = array(
                'listId' => $listId,
                'buttons' => array(
                    'prev' => \Verba\Lang::get('blog prev-next prev'),
                    'next' => \Verba\Lang::get('blog prev-next next'),
                ),
                'tpls' => array(
                    'navigation' => 'blog/show/prev-next.tpl',
                    'link' => 'blog/show/prev-next-link.tpl',
                ),
                'attrs' => array(
                    'title', 'url_code','text', 'picture', '_picture_config'
                ),
                'hrefHandler' => array(
                    0 => array($this, 'navPrevNextItemParse'),
                    1 => array('slID' => $listId)
                )
            );

            $tpl->assign(array(
                'NAVIGATION_OVER_LIST' => \Verba\_mod('local')->makeItemNavigation($oh->getID(), $navCfg)
            ));
        }else{
            $tpl->assign(array(
                'NAVIGATION_OVER_LIST' => ''
            ));
        }

        return $tpl->parse(false, 'item');
    }

    function navPrevNextItemParse($item, $urlVars){
        $imageSrc = false;
        if(isset($item['_picture_config']) && !empty($item['_picture_config'])
            && isset($item['picture']) && !empty($item['picture'])){
            $imgCfg = \Verba\_mod('image')->getImageConfig($item['_picture_config']);
            $imageSrc = $imgCfg->getFullUrl(basename($item['picture']));
        }else{
            $imageSrc = \Mod\Seo::extractImageUrlFromText($item['text']);
        }

        $imageClassSign = '';
        if(!$imageSrc){
            $imageSrc = SYS_IMAGES_URL.'/1px.gif';
            $imageClassSign = ' no-image';
        }

        $this->tpl->assign(array(
            'PN_ITEM_TITLE' => $item['title'],
            'PN_ITEM_PICTURE' => $imageSrc,
            'PN_ITEM_PICTURE_CLASS_SIGN' => $imageClassSign,
        ));
        return \Mod\Seo::idToSeoStr($item, $urlVars);
    }


    /**
     * put your comment there...
     *
     * @param MakeList $list
     * @param mixed $row
     */
    function handleListRow($list, $row){
        global $S;
        $tpl = $this->tpl();
        $date = strtotime($row['date']);
        $url = \Mod\Seo::idToSeoStr($row,array('seq'=>$list->getCurrentPos(), 'slID'=>$list->getID()));
        $tpl->assign(array(
            'ITEM_PAGE_URL' => $url,
            'ITEM_TITLE' => $row['title_'.SYS_LOCALE],
            'ITEM_PICTURE_E' => '',
            '_ITEM_DATE' => date("j.m.Y", $date),
            'ITEM_PREVIEW' => isset($row['text_preview']) && !empty($row['text_preview']) ? $row['text_preview'] : '',
        ));
        if(isset($row['_picture_config']) && !empty($row['_picture_config'])
            && isset($row['picture']) && !empty($row['picture'])){
            $imgCfg = \Verba\_mod('image')->getImageConfig($row['_picture_config']);
            $imageSrc = $imgCfg->getFullUrl(basename($row['picture']));
        }else{
            $imageSrc = \Mod\Seo::extractImageUrlFromText($row['text']);
        }
        if($imageSrc){
            $tpl->assign(array(
                'ITEM_PICTURE' => $imageSrc,
            ));
            $tpl->parse('ITEM_PICTURE_E', 'subst_image');
        }

        return;
    }
}

class BlogAdmin extends \Verba\Mod{

    function makeAction($bp){
        switch($bp['action']){
            case 'list':
                $handler = 'manageList';
                break;
            case 'catalog':
                $handler = 'catalogRedirector';
                break;
            default :
                $handler = null;
        }
        //default actions
        if(!$handler){
            $handler = parent::makeAction($bp);
        }
        return $handler;
    }

    function manageList($bp = null){
        $bp = array(
            'cfg' => 'acp-blog',
        );
        return \Verba\Response\Json::wrap(true, $this->baseList($bp));
    }

    function formJson($bp = null){
        $bp = $this->extractBParams($bp);
        $bp['cfg'] = 'acp-blog acp-blog-'.$bp['action'];

        $r = $this->parseForm($bp);
        return \Verba\Response\Json::wrap(true, $r);
    }

    function catalogRedirector($bp = null){
        global $S;
        $bp['ot_code'] = 'catalog';
        switch($S->url_fragments[2]){
            case 'cuform':
                if(isset($bp['iid']) && !empty($bp['iid'])){
                    $bp['action'] = 'updateform';
                }else{
                    $bp['action'] = 'createform';
                }
                $bp['cfg'] = 'acp-blog-catalog acp-blog-catalog-'.$bp['action'];
                break;
            case 'create':
            case 'update':
                $bp['action'] = $S->url_fragments[2];
                break;
            case 'remove':
                $bp['action'] = 'remove';
                break;
        }
        $mCatAdmin = \Verba\_mod('catalogAdmin');
        $r = $mCatAdmin->dispatcher($bp);
        return $r;
    }
}
