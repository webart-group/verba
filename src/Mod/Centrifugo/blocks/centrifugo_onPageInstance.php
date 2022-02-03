<?php

class centrifugo_onPageInstance extends \Verba\Block\Html
{

    static protected $_centrifugo_instance_added;

    function build()
    {

        if (self::$_centrifugo_instance_added) {
            return true;
        }

        self::$_centrifugo_instance_added = true;

        $this->addScripts(array(
            //array('sockjs.min','sockjs'),
            array('centrifuge.min', 'centrifugo'),
            array('centConnection', 'centrifugo'),
        ));

        /**
         * @var $mCent \Mod\Centrifugo
         */
        $mCent = \Verba\_mod('centrifugo');

        $jscfg = $mCent->generateCentConnectCfg();

        $this->addJsBefore("
window.CentInstance = new CentConnection(" . json_encode($jscfg, JSON_FORCE_OBJECT) . ");
");
        return true;
    }

}
