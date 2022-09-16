<?php

    namespace Verba\Act\Form\Element;

    use \Verba\Html\Element;

    class Picupload extends Element
    {
        public $imgCfgName;
        /**
         * @var object ImageConfig
         */
        public $imgCfg;
        public $fileuploadE = true;
        public $remoteE = false;
        public $restrictions = true;
        public $acceptByTypes = true;
        public $prompt_select = false;
        public $rstr_items = array(
            'maxWidth' => 250,
            'maxHeight' => 250,
            'maxFilesize' => 2000000,
            'types' => array('gif', 'jpg', 'png')
        );
        public $preview = array(
            'width' => 86,
            'height' => 86,
            'idxs' => null,// all | array | Image ID '!primary,acp'
        );

        public $_clientCfg = array(
            'className' => 'AEFElement_picupload'
        );

        public $templates = array(
            'body' => 'aef/fe/picupload/wrap.tpl',
            'content' => 'aef/fe/picupload/picupload.tpl',
            'error' => 'aef/fe/picupload/error.tpl',
            'url_block' => 'aef/fe/picupload/url.tpl',
            'file_block' => 'aef/fe/picupload/file.tpl',
            'preview_block' => 'aef/fe/picupload/preview.tpl',
            'restrictions_block' => 'aef/fe/fileupload/restrictions.tpl',
            'restrictions_row' => 'aef/fe/fileupload/restrictions_row.tpl',
            'fileremove_block' => 'aef/fe/picupload/fileremove_block.tpl',
        );

        function _init()
        {
            $this->listen('prepare', 'addImgCfgFromAttr', $this, 'handleImageConfig');
        }

        function addImgCfgFromAttr()
        {
            $cfgName = $this->aef->oh()->p($this->A->getCode() . '_config');
            if (!$cfgName) {
                return false;
            }
            \Verba\_mod('image');
            $this->imgCfg = \Verba\Mod\Image::getImageConfig($cfgName);
            $this->imgCfgName = $cfgName;

            if (!$this->imgCfg->isPrimaryExtracted()) return false;
            $this->rstr_items['maxWidth'] = $this->imgCfg->getWidth();
            $this->rstr_items['maxHeight'] = $this->imgCfg->getHeight();
            $this->rstr_items['maxFilesize'] = $this->imgCfg->getMaxUploadSize();
            $this->rstr_items['types'] = $this->imgCfg->getExtensions();
            $this->rstr_items['maxFilesToUpload'] = $this->imgCfg->getMaxFilesToUpload();
        }

        function setFileupload($cfg)
        {
            if (!is_array($cfg)) {
                $this->fileuploadE = (bool)$cfg;
                $cfg = array();
            }
            $cfg['name'] = $this->getName();
            $cfg['id'] = $this->getId();
            if($this->acceptByTypes && isset($this->rstr_items['types']) && is_array($this->rstr_items['types'])){
                $cfg['acceptTypes'] = $this->rstr_items['types'];
            }
            $this->fileuploadE = new \Verba\Html\File($cfg);
        }

        function makeFileupload()
        {
            if (is_object($this->fileuploadE) || !$this->fileuploadE) {
                return $this->fileuploadE;
            }

            $this->setFileupload($this->fileuploadE);
            return $this->fileuploadE;
        }

        function getFileuploadEId()
        {
            return $this->getName() . '[upl]';
        }

        function parseFileUploadBlock()
        {

            $this->tpl->assign(array(
                'PICUPLOAD_REMOVE_E' => ''
            ));

            if (!($cValue = $this->getValue())) {
                if($this->prompt_select){
                    $this->tpl->assign(array(
                        'PICUPLOAD_REMOVE_E' => \Verba\Lang::get('fe picupload prompt_select'),//$removeE
                    ));
                }
            } else {
                $rmEId = $this->getId() . '_remove';

                $this->tpl->assign(array(
                    'PICUPLOAD_REMOVE_E_ID' => $rmEId,
                    'PICUPLOAD_REMOVE_E_NAME' => $this->getName() . '[delete]',
                    'PICUPLOAD_REMOVE_E_FILENAME' => basename($cValue),
                    'PICUPLOAD_REMOVE_E_LEGEND' => \Verba\Lang::get('aef buttons delete'),
                    'PICUPLOAD_REMOVE_E_STORY' => \Verba\Lang::get('fe picupload remove story'),
                ));

                $this->tpl->parse('PICUPLOAD_REMOVE_E', 'fileremove_block');
            }


            if (!is_object($fupl = $this->makeFileupload())) {
                $uploadE = '';
            } else {
                $fupl->setName($this->getFileuploadEId());
                $fupl->setId($fupl->getId());
                $uploadE = $fupl->build();
            }
            $this->tpl->assign('PICUPLOAD_FILE_FE', $uploadE);

            return $this->tpl->parse(false, 'file_block');
        }

        function setRemote($cfg)
        {
            if (!$cfg) {
                $this->remoteE = (bool)$cfg;
            }
                $this->remoteE = new \Verba\Html\Text($cfg);
        }

        function makeRemote()
        {
            if (is_object($this->remoteE) || !$this->remoteE) {
                return $this->remoteE;
            }

            $this->setRemote($this->remoteE);
            return $this->remoteE;
        }

        function getRemoteEId()
        {
            return $this->getName() . '[u]';
        }

        function parseRemoteUrlBlock()
        {
            if (!is_object($e = $this->makeRemote())) {
                return '';
            }

            $e->setName($this->getRemoteEId());
            $e->setId($e->getName());
            $this->tpl->assign('PICUPLOAD_URL_FE', $e->build());
            $this->tpl->define('url_block', $this->templates['url_block']);
            return $this->tpl->parse(false, 'url_block');
        }

        function setRestrictions($cfg)
        {
            $this->restrictions = $cfg === false ? false : true;
        }

        function parseRestrictionsBlock()
        {
            $rsts = array();
            if ($this->restrictions !== false) {
                //if(is_int($this->rstr_items['maxWidth'])) $rsts[] = \Verba\Lang::get('fe picupload restrictions maxWidth', array('maxWidth' => $this->rstr_items['maxWidth']));
                //if(is_int($this->rstr_items['maxHeight']))   $rsts[] = \Verba\Lang::get('fe picupload restrictions maxHeight', array('maxHeight' => $this->rstr_items['maxHeight']));
                if (is_array($this->rstr_items['types'])) $rsts[] = \Verba\Lang::get('fe picupload restrictions types', array('types' => implode(', ', $this->rstr_items['types'])));
                if (is_int($this->rstr_items['maxFilesize']) && $this->rstr_items['maxFilesize'] > 0) {
                    $rsts[] = \Verba\Lang::get('fe picupload restrictions maxFilesize', array('maxFilesize' =>  \Verba\FileSystem::formateFileSize($this->rstr_items['maxFilesize'])));
                }

            }
            if (!count($rsts)) {
                return '';
            }

            $this->tpl->clear_vars(array('RESTRICTION_ROWS'));
            foreach ($rsts as $v) {
                $this->tpl->assign('RESTRICTION_ITEM', $v);
                $this->tpl->parse('RESTRICTION_ROWS', 'restrictions_row', true);
            }
            $this->tpl->assign('RESTRICTION_BOX_ID', $this->getId() . 'RstBlock');
            return $this->tpl->parse(false, 'restrictions_block');
        }

        function setPreview($cfg)
        {
            if (!$cfg) {
                $this->preview = false;
                return;
            }
            if (!is_array($cfg)) return;
            $this->preview = array_replace_recursive($this->preview, $cfg);
        }

        function parsePreviewBlock()
        {

            if (!is_string($exists_value = $this->getValue())
                || empty($exists_value)
                || !is_array($this->preview)
                || !($copies = $this->imgCfg->getCopies(isset($this->preview['idxs']) ? $this->preview['idxs'] : null))) {

                return '';
            }

            $jsCfg = array(
                'items' => array(),
                'frame' => array(
                    'width' => $this->preview['width'],
                    'height' => $this->preview['height'],
                )
            );
            $filename = basename($exists_value);
            foreach ($copies as $idx => $copy) {
                $jsCfg['items']['c' . $idx] = array('data' => array(
                    'id' => $idx,
                    'url' => $this->imgCfg->getFullUrl($filename, $idx),
                    'width' => $this->imgCfg->getWidth($idx),
                    'height' => $this->imgCfg->getHeight($idx),
                ));
            }

            $this->tpl->assign(array(
                'PREVIEW_JS_CFG' => \json_encode($jsCfg, JSON_FORCE_OBJECT),
                'PREVIEW_E_ID' => $this->getId() . '_preview',
            ));

            return $this->tpl->parse(false, 'preview_block');
        }

        function makeE()
        {
            $this->fire('makeE');

            $this->tpl->clear_tpl(array_keys($this->templates));
            $this->tpl->clear_vars(array(
                'PICUPLOAD_FILE_BLOCK',
                'PICUPLOAD_URL_BLOCK',
                'PICUPLOAD_C_PREVIEW',
                'PICUPLOAD_RESTRICTIONS_BLOCK',
                'PICUPLOAD_GEN_ERROR',
            ));
            $this->tpl->define($this->templates);

            if (!$this->imgCfg) {

                $this->tpl->assign(array(
                    'PICUPLOAD_GEN_ERROR' => \Verba\Lang::get('fe picupload errors bad_config'),
                ));

                $this->tpl->parse('PICUPLOAD_E_CONTENT', 'error');

            } else {
                $this->tpl->assign(array(
                    'FE_ID' => $this->getId(),
                ));

                //FileUpload Element
                $this->tpl->assign('PICUPLOAD_FILE_BLOCK', $this->parseFileUploadBlock());

                //RemoteURL Input
                $this->tpl->assign('PICUPLOAD_URL_BLOCK', $this->parseRemoteUrlBlock());

                // Preview Image Element
                $this->tpl->assign('PICUPLOAD_C_PREVIEW', $this->parsePreviewBlock());

                // restrictions notifications
                $this->tpl->assign('PICUPLOAD_RESTRICTIONS_BLOCK', $this->parseRestrictionsBlock());

                $this->tpl->parse('PICUPLOAD_E_CONTENT', 'content');
            }

            $this->aef()->addScripts(array('picupload', 'form/fe'));
            $this->aef()->addCss(array('picupload', 'form/fe'));

            $this->setE($this->tpl->parse(false, 'body'));
            $this->fire('makeEFinalize');
        }
    }