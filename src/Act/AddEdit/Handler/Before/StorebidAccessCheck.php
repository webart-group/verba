<?php

namespace Verba\Act\AddEdit\Handler\Before;

use \Verba\Act\AddEdit\Handler\Before;

class StorebidAccessCheck extends Before
{

    function run()
    {

        if ($this->ah->getAction() == 'new') {
            $this->handleCreate();
        } else {
            $this->handleUpdate();
        }

        return true;
    }

    function handleCreate()
    {
        return true;
    }

    function handleUpdate()
    {

        if (!$this->ah->validateAccess()) {
            $this->log()->error('Face not recognized');
            return false;
        }

        return true;
    }
}
