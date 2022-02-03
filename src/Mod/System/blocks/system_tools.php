<?php

class system_tools extends \Verba\Block\Html
{

    function route()
    {

        switch ($this->request->node) {

            case 'recimages':
                $h = new system_toolRecImages($this);
                break;

            case 'fix-images-names':
                $h = new system_toolFixImagesNames($this);
                break;

            case 'uniq-brands':
                $h = new system_toolUniqBrands($this);
                break;

            case 'brands-fix-title':
                $h = new system_toolTrimBrandsTitles($this);
                break;

            case 'users-create-accounts':
                $h = new system_toolMassCreateUsersAccounts($this);
                break;
            case 'account-recalc-balance':
                $h = new system_toolAccountRecalcBalance($this);
                break;
            case 'chatik':

                $rqch = $this->rq->shift();

                switch ($rqch->node) {
                    case 'api':
                        $h = new system_toolChatikApi($rqch);
                        break;
                    default:
                        $h = new system_toolChatikInstance($rqch);
                        break;
                }
                break;
            case 'recalc-store-cppr':
                $h = new system_toolStoreRecalcCppr($this);
                break;
            case 'refres-all-stores-pc':
                $h = new system_toolRefreshStoresPc($this);
                break;

            case 'bids-refresh':
                $h = new system_toolBidsRefresh($this);
                break;
            case 'store-rating-recalc-all':
                $h = new \Mod\Review\Block\Tool\RecalcAllStoresRatings($this);
                break;
            case 'm1':
                $h = new system_toolM1($this->rq->shift());
                break;
        }

        if (!isset($h)) {
            throw new \Exception\Routing();
        }

        return $h->route();
    }

}

class system_toolRecImages extends \Verba\Block\Html
{

    function build()
    {
        exit;

        $_product = \Verba\_oh('product');
        set_time_limit(3600 * 24);
        $l = 100;
        $n = 0;
        $haveMore = true;

        $mImg = \Verba\_mod('image');
        $_img = \Verba\_oh('image');

        while ($haveMore) {

            $q = "SELECT * FROM `" . SYS_DATABASE . "`.`" . $_product->vltT() . "` ORDER BY `id` DESC LIMIT " . $n . ", " . $l;
            $sqlr = $this->DB()->query($q);
            if (!$sqlr || ($numRows = $sqlr->getNumRows()) < 1) {
                break;
            }
            if ($numRows < $l) {
                $haveMore = false;
            }
            $n = $n + $l - 1;

            $images = array();
            while ($row = $sqlr->fetchRow()) {
                $_coh = $row['ot_id'];
                if (!empty($row['picture'])) {
                    if (!isset($images[$_coh->p('picture_config')])) {
                        $images[$_coh->p('picture_config')] = array();
                    }
                    $images[$_coh->p('picture_config')][] = basename($row['picture']);
                }
                $qi = "SELECT * FROM " . $_img->vltURI() . " WHERE " . $_img->getPAC() . " IN (SELECT ch_iid FROM " . $_img->vltURI($_product) . " WHERE `p_ot_id` ='" . $row['ot_id'] . "' && `p_iid` ='" . $row['id'] . "' && ch_ot_id = '" . $_img->getID() . "')";
                $sqlri = $this->DB()->query($qi);
                if (!$sqlri->getNumRows()) {
                    continue;
                }

                while ($ir = $sqlri->fetchRow()) {
                    if (!isset($images[$ir['_storage_file_name_config']])) {
                        $images[$ir['_storage_file_name_config']] = array();
                    }
                    $images[$ir['_storage_file_name_config']][] = $ir['storage_file_name'];
                }
            }

            foreach ($images as $cfgName => $imagesByCfg) {
                $iCfg = $mImg->getImageConfig($cfgName);
                $pCopiesIndexes = $iCfg->getCopiesIndexes();
                $origKeyIdx = array_keys($pCopiesIndexes, 'orig');
                if ($origKeyIdx && isset($origKeyIdx[0])) {
                    $origKeyName = 'orig';
                } else {
                    $origKeyName = 'primary';
                }

                $origKey = current(array_keys($pCopiesIndexes, $origKeyName));
                unset($pCopiesIndexes[$origKey]);

                $i = 0;

                foreach ($imagesByCfg as $cimageName) {
                    $srcImg = $iCfg->getFullPath($cimageName, $origKeyName);
                    if (!\Verba\FileSystem\Local::isFile($srcImg)) {
                        continue;
                    }
                    $imageInfo = array('width' => false, 'height' => false, 'type' => false);
                    list($imageInfo['width'], $imageInfo['height'], $imageInfo['type']) = getimagesize($srcImg);

                    foreach ($pCopiesIndexes as $copyIdx) {
                        $ipath = $iCfg->getFullPath($cimageName, $copyIdx);

                        if (!\Verba\FileSystem\Local::isFile($ipath) || !\Verba\FileSystem\Local::del_file($ipath)) {
                            $this->log()->warning('image was not deleted: filename[' . var_export($ipath, true) . ']', false);
                        } else {
                            $i++;
                        }
                        $destinationImg = $iCfg->getFullPath($cimageName, $copyIdx);
                        $destDir = dirname($destinationImg);
                        if (!\Verba\FileSystem\Local::needDir($destDir)) {
                            $this->log()->error('Unable to create dest directory to image repack. Dir:' . var_export($destDir, true));
                            continue;
                        }

                        $mImg->repackImage($srcImg, $destinationImg, $iCfg->getWidth($copyIdx), $iCfg->getHeight($copyIdx), $imageInfo['width'], $imageInfo['height'], $imageInfo['type'], $iCfg->getResizeBySmallerSide($copyIdx), $iCfg->getQuality($copyIdx));
                    }

                }
            }
        }
        return 1;
    }
}

class system_toolFixImagesNames extends \Verba\Block\Html
{

    function build()
    {
        $oh = \Verba\_oh('plant');
        $_image = \Verba\_oh('image');
        $q = "SELECT * FROM `" . SYS_DATABASE . "`.`" . $oh->vltT() . "`";
        $sqlr = $this->DB()->query($q);
        $mImg = \Verba\_mod('image');
        $images = array();
        while ($row = $sqlr->fetchRow()) {

            $_coh = \Verba\_oh($row['ot_id']);

            if (!empty($row['picture'])) {
                if (!isset($images[$_coh->p('picture_config')])) {
                    $images[$_coh->p('picture_config')] = array();
                }
                $images[$_coh->p('picture_config')][] = array(
                    'filename' => basename($row['picture']),
                    'cfg' => $_coh->p('picture_config'),
                    'id' => $row[$oh->getPAC()],
                );
            }
        }
        set_time_limit(0);

        foreach ($images as $cfgName => $imagesByCfg) {
            $iCfg = $mImg->getImageConfig($cfgName);
            $pCopiesIndexes = $iCfg->getCopiesIndexes();
            $origKey = current(array_keys($pCopiesIndexes, 'orig'));
            unset($pCopiesIndexes[$origKey]);
            $i = 0;

            foreach ($imagesByCfg as $cimage) {
                $cimageName = $cimage['filename'];
                if ($cimageName[(mb_strlen($cimageName) - 1)] == '.') {
                    $fixedName = $cimageName . 'jpg';
                } else {
                    continue;
                }

                foreach ($pCopiesIndexes as $copyIdx) {
                    $imgName = $iCfg->getFullPath($cimageName, $copyIdx);
                    if (!\Verba\FileSystem\Local::isFile($imgName)) {
                        continue;
                    }
                    $imgNameFixed = $iCfg->getFullPath($fixedName, $copyIdx);
                    rename($imgName, $imgNameFixed);
                }
                $q = "UPDATE `" . SYS_DATABASE . "`.`" . $oh->vltT() . "`
         SET `picture` = '" . $this->DB()->escape($iCfg->getFullUrl($fixedName)) . "'
         WHERE `" . $oh->getPAC() . "` = '" . $cimage['id'] . "' LIMIT 1";
                $this->DB()->query($q);
            }
        }
    }
}

class system_toolUniqBrands extends \Verba\Block\Html
{

    function build()
    {
        set_time_limit(82000);
        $_brand = \Verba\_oh('brand');
        $_prod = \Verba\_oh('product');
        $q = "SELECT id, title_ru, url_code, COUNT(id) as cid
    FROM `" . SYS_DATABASE . "`.`" . $_brand->vltT() . "` as `b`
    GROUP BY b.url_code";
        $sqlr = $this->DB()->query($q);

        while ($row = $sqlr->fetchRow()) {
            if ($row['cid'] < 2) {
                continue;
            }
            $q = "SELECT * FROM " . $_brand->vltURI() . "
      WHERE `url_code` = '" . $this->DB()->escape($row['url_code']) . "'
      ORDER BY id ASC";
            $sqlrb = $this->DB()->query($q);
            $first = false;
            $copies = array();
            while ($brow = $sqlrb->fetchRow()) {
                if (!$first) {
                    $first = $brow['id'];
                } else {
                    $copies[] = $brow['id'];
                }
            }
            $in_statement = "'" . implode($copies, "', '") . "'";
            $qu = "UPDATE " . $_prod->vltURI() . " SET brandId = '" . $first . "'
      WHERE brandId IN(" . $in_statement . ")";
            $this->DB()->query($qu);

            $qd = "DELETE FROM " . $_brand->vltURI() . " WHERE id IN(" . $in_statement . ")";
            $this->DB()->query($qd);

        }
    }
}

class system_toolTrimBrandsTitles extends \Verba\Block\Html
{

    function build()
    {
        set_time_limit(82000);
        $_brand = \Verba\_oh('brand');

        $q = "SELECT id, title_ru
    FROM " . $_brand->vltURI() . " as `b`";
        $sqlr = $this->DB()->query($q);

        while ($row = $sqlr->fetchRow()) {
            $qu = "UPDATE " . $_brand->vltURI() . " SET title_ru = '" . $this->DB()->escape(trim($row['title_ru'])) . "'
      WHERE id='" . $row['id'] . "'";
            $this->DB()->query($qu);
        }
    }
}

class system_toolMassCreateUsersAccounts extends \Verba\Block\Html
{

    function build()
    {
        exit;
        set_time_limit(82000);

        $_account = \Verba\_oh('account');
        $_user = \Verba\_oh('user');

        $this->DB()->query("TRUNCATE TABLE " . $_account->vltURI());

        $q = "SELECT * FROM " . $_user->vltURI();
        $sqlr = $this->DB()->query($q);

        while ($row = $sqlr->fetchRow()) {

            $qi = "INSERT IGNORE INTO " . $_account->vltURI() . " (
      `owner`
      ,`currencyId`
      ,`created`
      ,`active`
      ,`mode`
      ) VALUES
      ('" . $row['user_id'] . "','45','" . date("Y-m-d H:i:s") . "','1', 1158)
      ,('" . $row['user_id'] . "','40','" . date("Y-m-d H:i:s") . "','1', 1158)
      ,('" . $row['user_id'] . "','37','" . date("Y-m-d H:i:s") . "','1', 1158)
      ,('" . $row['user_id'] . "','54','" . date("Y-m-d H:i:s") . "','1', 1158)
      ,('" . $row['user_id'] . "','55','" . date("Y-m-d H:i:s") . "','1', 1158)
      ,('" . $row['user_id'] . "','56','" . date("Y-m-d H:i:s") . "','1', 1158)
      ";

            $this->DB()->query($qi);
        }

    }

}

class system_toolAccountRecalcBalance extends \Verba\Block\Html
{

    function build()
    {
        //exit;
        set_time_limit(82000);
        return \Mod\Account::getInstance()->recalcAndSaveAccountBalances($this->rq->iid);
    }

}

class system_toolAnyCode extends \Verba\Block\Html
{

    function build()
    {
        //exit;
        set_time_limit(82000);
        return;
    }
}

class system_toolChatikInstance extends \Verba\Block\Html
{

    function build()
    {
        //exit;
        //set_time_limit(82000);

        $chatikInst = new chatik_pageInstance($this, array(
            'channel' => 'public:ch1',
            'backendUrl' => '/acp/system/tools/chatik/api/',
        ));

        //$mCent = \Verba\_mod('centrifugo');
        //$mChatik = \Verba\_mod('Chatik');

        $chatikInst->prepare();
        $chatikInst->build();
        $this->mergeHtmlIncludes($chatikInst);
        $this->content = $chatikInst->getContent();

        return $this->content;
    }

}

class system_toolChatikApi extends \Verba\Block\Json
{

    function build()
    {
        //exit;
        set_time_limit(82000);

        /**
         * @var $mCent Centrifugo
         */
        $mCent = \Verba\_mod('centrifugo');
        $mChatik = \Verba\_mod('Chatik');

        $channel = '$pv:usrntf#1';
        $data = array(
            'events' => array(
//        'msgs' => array(
//          array('id' => '2231', 'from' => 'Питонио1', 'text' => 'Hello', 'channel' => '$pd:str2u1'),
//          array('id' => '451', 'from' => 'Магазиноштуки2', 'text' => 'Lol4to', 'channel' => '$pd:str41u1'),
//        ),
//        'sells' => array(
//          array('id' => '231', 'title' => 'Wow', 'url' => '/#'),
//          array('id' => '221', 'title' => 'Aion', 'url' => '/#'),
//        )
            )
        );
        $mCent->Client()->publish($channel, $data);


        $channel = '$pd:store2';
        $data = array(
            'events' => array(
                'smsgs' => array(
                    array('id' => '2231', 'from' => 'Клиент1', 'text' => 'Hello', 'channel' => '$pd:str2u1'),
                    //array('id' => '451', 'from' => 'Клиент2', 'text' => 'Lol4to', 'channel' => '$public:str2u213'),
                ),
//        'sells' => array(
//          array('id' => '231', 'title' => 'Wow', 'url' => '/#'),
//        )
            )
        );
        $mCent->Client()->publish($channel, $data);

    }

}


class system_toolImitateOrderPayment extends \Verba\Block\Html
{

    function build()
    {
        exit;
        set_time_limit(82000);

        $_order = \Verba\_oh('order');
        $_balop = \Verba\_oh('balop');

        $q = "UPDATE " . $_order->vltURI() . " SET 
    `payed` = 0, `payedDate` = NULL, `status` = 20 WHERE " . $_order->getPAC() . "='" . $this->rq->iid . "'";

        $this->DB()->query($q);

        $q = "TRUNCATE TABLE " . $_balop->vltURI();

        $this->DB()->query($q);

        $ae = $_order->initAddEdit('edit');

        $ae->setIid($this->rq->iid);

        $ae->setGettedData(array(
            'status' => 21
        ));

        $ae->addedit_object();

    }

}

class system_toolStoreRecalcCppr extends \Verba\Block\Html
{

    function build()
    {

        $mStore = \Mod\Store::getInstance();

        $mStore->refreshStoreCPK($this->rq->iid);

    }

}

class system_toolRefreshStoresPc extends \Verba\Block\Html
{

    function build()
    {

        $mStore = \Mod\Store::getInstance();

        $mStore->refreshStoresCPK();

    }

}


class system_toolBidsRefresh extends \Verba\Block\Html
{

    function build()
    {
        //return false;
        set_time_limit(3600 * 24);

        $_loo = \Verba\_oh('loo');

        $descendance = $_loo->getDescendants(true);

        foreach ($descendance as $cDescOt) {
            //$cDescOt = 333;
            $_prod = \Verba\_oh($cDescOt);

            $l = 100;
            $n = 0;

            $qm = new \Verba\QueryMaker($_prod);
            //$qm->addWhereIids(25);

            $foundMore = false;
            $i = 0;
            do {
                $qm->addLimit($l, $n);

                $sqlr = $qm->run();

                if ($sqlr->getNumRows() == 100) {
                    $n += $l;
                    $foundMore = true;
                } else {
                    $foundMore = false;
                }

                while ($row = $sqlr->fetchRow()) {
                    $ae = $_prod->initAddEdit('edit');
                    $ae->setExistsValues($row);
                    $ae->setIid($row[$_prod->getPAC()]);
                    $ae->setIgnoreErrors(true);
                    $ae->setGettedData(['created' => 1]);

                    $ae->addedit_object();
                }

            } while (++$i < 100 && $foundMore);
            //break;
        }

        return 1;
    }
}

class system_toolM1 extends \Verba\Block\Json {

    public function route()
    {
        if(!$this->rq->node){
            $this->addItems([
                new system_toolM1Catalog($this)
            ]);
            return $this;
        }

        $router = get_class($this).'_'.ucfirst($this->rq->node);
        if(!class_exists($router)){
            throw new \Exception\Routing();
        }

        return (new $router($this->rq->shift()))->route();

    }

    function build()
    {

    }
}

class system_toolM1Catalog extends \Verba\Block\Json
{

    function build()
    {
        $this->content = '';
        //echo 1; exit;
        //$q = "SELECT * FROM `".SYS_DATABASE."`.`catalog` WHERE id = '12659'";
        $q = "SELECT * FROM `".SYS_DATABASE."`.`catalog`";
        $sqlr = $this->DB()->query($q);
        $all_subs = [];
        $all_f = [];
        while ( $row = $sqlr->fetchRow() ) {
            if(!$row['config']){
                continue;
            }

            $cfg_orig = $cfg = unserialize($row['config']);
            //list fields

            $updateRequired = false;

            $listsKeys = ['public_fields', 'store_fields', 'offer_fields', 'order_info', 'order_description'];

            // списки
            foreach($listsKeys as $listKey) {
                if (isset($cfg['groups'][$listKey])) {
                    if(isset($cfg['groups'][$listKey]['items']) && is_array($cfg['groups'][$listKey]['items'])){
                        foreach($cfg['groups'][$listKey]['items'] as $i => $fieldCfg){
                            $fieldUpdate = [];
                            if($fieldCfg['headerText']) {

                                $headerText = $fieldCfg['headerText'];

                                if($fieldCfg['headerText'] && preg_match("/Handlers\\\HList\\\Headers/i", $fieldCfg['headerText'],$_buff)) {
                                    $headerText = preg_replace("/Handlers\\\HList\\\Headers/i", 'Act\MakeList\Handler\Header', $fieldCfg['headerText']);
                                }

                                if(preg_match("/\\\Mods\\\(.*)?$/i", $fieldCfg['headerText'],$_buff)) {
                                    $headerText = '\Mod\\'.ucfirst($_buff[1]);
                                }

                                if ($headerText !== $fieldCfg['headerText']) {
                                    $fieldUpdate['headerText'] = $headerText;
                                    $all_subs[] = array(
                                        $listKey,
                                        'headerText',
                                        $fieldCfg['headerText'],
                                        $headerText
                                    );
                                }
                            }


                            if($fieldCfg['handler']) {
                                $handler = $fieldCfg['handler'];

                                if(preg_match("/(.*)_listHandler(.*)/i", $handler,$_buff)) {
                                    $handler = '\Mod\\'.ucfirst($_buff[1]).'\Act\MakeList\Handler\Field\\'.ucfirst($_buff[2]);
                                }

                                if(preg_match("/\\\Mods\\\([a-z]+)\\\Handlers\\\HList\\\([a-z0-9_]+)(\(.*)?$/i", $handler,$_buff)) {
                                    $handler = '\Mod\\'.ucfirst($_buff[1]).'\Act\MakeList\Handler\Field\\'.ucfirst($_buff[2]).$_buff[3];
                                }

                                if(preg_match("/\\\Mods\\\Image\\\Handlers\\\Present\\\([a-z0-9_]+)$/i", $handler,$_buff)) {
                                    $handler = '\Mod\\Image\Act\Look\Handler\\'.ucfirst($_buff[1]);
                                }

                                if(preg_match("/\\\Mods\\\Image\\\Act\\\Look\\\Handler\\\([a-z0-9_]+)$/i", $handler,$_buff)) {
                                    $handler = '\Mod\\Image\Act\Look\Handler\\'.ucfirst($_buff[1]);
                                }

                                if(preg_match("/\\\Mods\\\(.*)?$/i", $handler,$_buff)) {
                                    $handler = '\Mod\\'.ucfirst($_buff[1]);
                                }

                                if($handler != $fieldCfg['handler']){
                                    $fieldUpdate['handler'] = $handler;
                                    $all_subs[] = array(
                                        $listKey,
                                        'handler',
                                        $fieldCfg['handler'],
                                        $fieldUpdate['handler']
                                    );
                                }

                            }


                            if(count($fieldUpdate)) {
                                $updateRequired = true;

                                $cfg['groups'][$listKey]['items'][$i] =
                                    array_replace_recursive(
                                        $cfg['groups'][$listKey]['items'][$i],
                                        $fieldUpdate
                                    );
                            }
                        }
                    }
                }
            }

            //формы
            $formKeys = ['form_fields', 'tform', 'update_form_fields'];
            foreach($formKeys as $formKey) {
                if (isset($cfg['groups'][$formKey])) {
                    if (isset($cfg['groups'][$formKey]['items']) && is_array($cfg['groups'][$formKey]['items'])) {
                        foreach ($cfg['groups'][$formKey]['items'] as $i => $fieldCfg) {
                            $fieldUpdate = [];
                            if($fieldCfg['handler']){
                                $all_f[] = $fieldCfg['handler'];

                                if( preg_match("/(\\\Mods\\\([a-z]+)\\\Handlers)?\\\Form\\\E\\\Ext\\\([a-z0-9_]+)(\(.*)?$/i", $fieldCfg['handler'],$_buff)) {
                                    // Mod
                                    if($_buff[2] ){
                                        $fieldUpdate['handler'] = '\Mod\\'.ucfirst($_buff[2]).'\Act\Form\Element\Extension\\'.ucfirst($_buff[3]).$_buff[4];
                                    } else {
                                        $fieldUpdate['handler'] = '\Act\Form\Element\Extension\\'.ucfirst($_buff[3]).$_buff[4];
                                    }
                                    $all_subs[] = array(
                                        $formKey,
                                        $fieldCfg['handler'],
                                        $fieldUpdate['handler']
                                    );
                                }else if(preg_match("/\\\Mods\\\(.*)?$/i", $fieldCfg['handler'],$_buff)) {
                                    $fieldUpdate['handler'] = '\Mod\\'.ucfirst($_buff[1]);
                                    $all_subs[] = array(
                                        $formKey,
                                        'handler',
                                        $fieldCfg['handler'],
                                        $fieldUpdate['handler']
                                    );
                                }
                            }


                            if(count($fieldUpdate)) {
                                $updateRequired = true;

                                $cfg['groups'][$formKey]['items'][$i] =
                                    array_replace_recursive(
                                        $cfg['groups'][$formKey]['items'][$i],
                                        $fieldUpdate
                                    );
                            }
                        }
                    }
                }
            }

            if ($updateRequired) {
                //$a = 1;
                $this->DB()->query("UPDATE `".SYS_DATABASE."`.`catalog` SET `config` = '".$this->DB()->escape(serialize($cfg))."' WHERE `id` = '".$row['id']."'");
            }
        }

        return $this->content;
    }
}

class system_toolM1_1 extends \Verba\Block\Json
{

    function init(){
        $this->addItems([
            # new system_toolBidsRefresh($this->rq->shift(), ['contentType' => 'json']),
            new \Mod\Review\Block\Tool\RecalcAllStoresRatings($this),
        ]);
    }

}

class system_toolM1_2 extends \Verba\Block\Json
{

    function init(){
        $this->addItems([
            new system_toolRefreshStoresPc($this->rq->shift(), ['contentType' => 'json']),
        ]);
    }

}