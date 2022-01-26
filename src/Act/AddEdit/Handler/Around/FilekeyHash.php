<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class FilekeyHash extends Around
{
    function run()
    {
        $str = $this->ah->getFirstParentOt()
            . $this->ah->getFirstParentIid()
            . $this->ah->getIID()
            . $this->oh->getID()
            . $this->ah->getObjectValue('filename')
            . $this->ah->getObjectValue('size')
            . $this->ah->getObjectValue('mime');

        return md5($str);
    }
}
