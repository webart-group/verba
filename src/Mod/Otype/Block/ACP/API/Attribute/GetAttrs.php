<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 16.09.19
 * Time: 13:02
 */

namespace Verba\Mod\Otype\Block\ACP\API\Attribute;

use \Verba\Mod\Otype;

class GetAttrs extends \Verba\Block\Json {

    /**
     * @var int
     */
    public $ot_id;

    function build(){
        $this->content = array('attrs' => []);

        $this->ot_id = $_REQUEST['ot'];
        if(!$this->ot_id){
            return $this->content;
        }

        $this->content['attrs'] = Otype::getInstance()->getOtAttrsAsArray($this->ot_id);
        return $this->content;
    }

}
