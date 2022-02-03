<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 16.09.19
 * Time: 12:38
 */

namespace Mod\Otype\Block\ACP\API\Attribute\Handler;


class Assign extends \Verba\Block\Json {

    function build(){

        $attr_id = (int)$_REQUEST['attr_id'];
        $ah_id = (int)$_REQUEST['ah_id'];
        $logic = (int)$_REQUEST['logic'];
        $priority = (int)$_REQUEST['priority'];

        list($linkInfo, $ahData) = \Mod\Otype::getInstance()->assignAhToAttr($attr_id, $ah_id, $logic, $priority);
        if(!$linkInfo){
            $this->getBlockByRole('response')->setOperationStatus(false);
            $this->content = 'Error assign ah';
        }else{

            $this->content = array($linkInfo['set_id'] => array(
                'ah_name' => (string)$ahData['ah_name'],
                'ah_id' => (int)$ahData['ah_id'],
                'check_params' => (int)$ahData['check_params'],
                'ah_type' => (int)$ahData['ah_type'],

                'set_id' => (int)$linkInfo['set_id'],
                'logic' => (int)$linkInfo['logic'],
                'priority' => (int)$linkInfo['priority'],

            ));
        }

        \Verba\_mod('system')->planeClearCache();

        return $this->content;
    }

}