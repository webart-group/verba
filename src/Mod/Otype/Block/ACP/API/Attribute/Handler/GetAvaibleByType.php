<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 16.09.19
 * Time: 12:35
 */
namespace Verba\Mod\Otype\Block\ACP\API\Attribute\Handler;

class GetAvaibleByType extends \Verba\Block\Html{

    function build(){

        $ah_type_id = isset($_REQUEST['ah_type_id'])
            ? (int)$_REQUEST['ah_type_id']
            : false;

        if(!$ah_type_id || $ah_type_id < 1){
            throw new \Exception('Bad incoming parameters');
        }

        $ahs = \Verba\Mod\Otype::getInstance()->getAhsByTypes($ah_type_id);

        $this->content = array(
            $ah_type_id => $ahs,
        );
        return $this->content;
    }
}
