<?php

namespace Verba\Mod\Profile\Block\Profile;

class Update extends \Verba\Block\Html
{

    function route()
    {
        $response = new \Verba\Response\Raw($this->rq);
        $response->addItems($this);

        return $response;
    }

    function build()
    {
        $this->content = false;

        $cfg = $this->request->asArray();

        $oh = \Verba\_oh('user');
        $U = \Verba\User();
        $this->ae = $ae = $oh->initAddEdit(array(
            'action' => 'edit',
            'iid' => $U->getID(),
        ));

        if (!$ae->validateAccess()) {
            throw new \Exception(\Lang::get('access denied'));
        }

        if (isset($cfg['data'])) {
            $ae->setGettedObjectData($cfg['data']);
        } else {
            $ae->setGettedObjectData($_REQUEST['NewObject'][$oh->getID()]);
        }

        $ae->addedit_object();
        $this->content = '';

        $this->addHeader('Location', \Verba\Mod\Profile::getPrivateUrl());

        $U->planeToReload();

        return $this->content;
    }
}
