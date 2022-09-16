<?php

class seo_public extends \Verba\Block\Html
{

    public $isLocal = false;
    public $content = '';
    public $templates = array(
        'content' => '/tracking/seo.tpl'
    );

    function init()
    {

        if (!SYS_IS_PRODUCTION) {
            $this->mute();
            return null;
        }
        $this->addItems(array(

            'YANDEX_METRIKA' => new seo_addYandexMetrika($this),

            //new seo_verboxInit($this),

            'GOOGLE_ATAG' => new seo_googleAtag($this),
            //'SITEHEART' => new seo_siteheart($this),
            'NETROXCHAT' => new seo_netrox($this),
        ));


        return $this;
    }

    function build()
    {

        if (empty($this->items)) {
            return $this->content;
        }
        foreach ($this->items as $icode => $i) {
            $c = '';
            if ($i instanceof \Verba\Block\Html) {
                $c = $i->content;
                $i->content = '';
            } elseif (is_string($i)) {
                $c = $i;
            }
            $this->content .= $c;
        }

        return $this->content;
    }

}

?>
