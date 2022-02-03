<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 16.09.19
 * Time: 12:39
 */

namespace Mod\Otype\Block\ACP\API\Attribute\Handler;


class Unassign extends \Verba\Block\Html{

    function build(){

        $set_id = (int)$_REQUEST['set_id'];
        $mOtype = \Mod\Otype::getInstance();
        $result = $mOtype->unassignAhFromAttr($set_id);
        if(!$result){
            $this->getBlockByRole('response')->setOperationStatus(false);
            $err_msg = $mOtype->log()->getMessagesAsString('error', "\n");
            $this->content = 'Unable to unassign Ah'.(!empty($err_msg) ? '. '.$err_msg : '' );
        }else{
            $this->content = '';
        }

        \Verba\_mod('system')->planeClearCache();

        return $this->content;
    }

}
