<?php
/**
 * Router and Tab for profile public pages
 */
namespace Verba\Mod\Profile\Block;


class Profile extends \profile_contentCommon
{
    public $bodyClass = 'profile-settings';
    public $coloredPanelCfg = false;

    function route()
    {
        switch ($this->rq->node) {
            case 'update':
                $b = new Profile\Update($this);
                break;
            case 's':
                $b = new Profile\Security($this);
                break;
            case '':
                $routed = $this;
                break;
        }

        if (!isset($routed) && isset($b)) {
            $routed = $b->route();
        }

        if (!$routed) {
            throw new \Verba\Exception\Routing();
        }

        return $routed;
    }

    function build()
    {
        if (is_string($this->content) || !is_integer($this->userId)) {
            return $this->content;
        }

        $_user = \Verba\_oh('user');

        $aef = $_user->initForm(array(
            'cfg' => 'public /public/profile/profile',
            'block' => $this,
            'action' => 'edit',
            'iid' => $this->userId,
        ));
        $this->addCss('form-settings', 'profile');

        $aef->tpl()->assign(['SECURITY_URL' => \Verba\Mod\Profile::getPrivateUrl().'s']);

        $profileProperties = new \page_coloredPanel($this, array(
            'title' => \Verba\Lang::get('profile props panelTitle'),
            'content' => $aef->makeForm(),
            'scheme' => 'grey',
            'extra_css_class' => 'half-size',
        ));

        $profileProperties->prepare();

        $this->content = $profileProperties->build();
        \Verba\Hive::setBackURL();
        return $this->content;
    }

}
