<?php

namespace Verba\Mod\Profile\Block\Profile;

class Security extends \profile_contentCommon
{

    public $bodyClass = 'profile-s';
    public $coloredPanelCfg = false;

    function build()
    {

        if (is_string($this->content) || !is_integer($this->userId)) {
            return $this->content;
        }

        $_user = \Verba\_oh('user');

        $aef = $_user->initForm(array(
            'cfg' => 'public /public/profile/profile-security',
            'block' => $this,
            'action' => 'edit',
            'iid' => $this->userId,
        ));

        $profileProperties = new \page_coloredPanel($this, array(
            'title' => \Verba\Lang::get('profile props panelPass'),
            'content' => $aef->makeForm(),
            'scheme' => 'grey',
            'extra_css_class' => 'half-size',
        ));

        $profileProperties->prepare();

        $this->content = $profileProperties->build();

        return $this->content;
    }
}
