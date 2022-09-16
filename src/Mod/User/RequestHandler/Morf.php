<?php
namespace Verba\Mod\User\RequestHandler;

class Morf extends \Verba\Block\Raw
{

    function build()
    {
        /**
         * @var $mAcp ACP
         * @var $mUser User
         */

        $mAcp = \Verba\_mod('acp');
        if (!$mAcp->checkAccess()) {
            throw new \Verba\Exception\Routing();
        }

        $_user = \Verba\_oh('user');
        $udata = $_user->getData($_REQUEST['uid']);
        if (!is_array($udata) || empty($udata)) {
            throw  new \Verba\Exception\Building('Unknown user');
        }
        global $S;

        $S->setUser($udata);
        $mUser = \Verba\_mod('user');
        $mUser->updateSessionId(null);

        $this->addHeader('Location', '/');

        $this->content = 'morfed';

        return $this->content;

    }

}
