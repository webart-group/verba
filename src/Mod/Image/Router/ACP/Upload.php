<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 18.12.2019
 * Time: 1:17
 */

namespace Verba\Mod\Image\Router\ACP;

class Upload extends \Verba\Block\Html
{
    function build()
    {
        /**
         * @var $fCfg \Verba\Mod\Image\Config
         * @var $mImage \Verba\Mod\Image
         */

        $mFile = \Verba\_mod('file');
        $mImage = \Verba\_mod('image');

        $_image = \Verba\_oh('image');

        $oh = \Verba\_oh($_REQUEST['_upload_ot']);
        $A = $oh->A($_REQUEST['_upload_attr']);

        $fCfg = $mImage->getImageConfig($_REQUEST['_upload_cfg']);
        if (!$oh || !$A || !$fCfg) {
            throw new \Exception('Wrong upload Data');
        }

        $_REQUEST['NewObject'][$oh->getID()]['_' . $A->getCode() . '_config'] = $_REQUEST['_upload_cfg'];
        $ae = $mImage->addEditNow(['action' => 'new']);
        $objData = $ae->getObjectData();
        $response = array(
            'files' => array(
                array(
                    'ot_id' => $oh->getID(),
                    'id' => $ae->getIID(),
                    'name' => $objData['filename'],
                    'filepath' => $fCfg->getFilepath($objData['storage_file_name']),
                    'size' => $objData['size'],
                    'url' => $fCfg->getFileUrl($objData['storage_file_name']),
                    'type' => $mImage->getMIMETypeById($objData['type']),
                    'priority' => $objData['priority'],
                ),
            )
        );
        $this->content = $response;
    }
}
