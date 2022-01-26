<?php

namespace Verba\Act\Delete\Handler;

use \Verba\Act\Delete\Handler;

class MailchimpUnsubscribe extends Handler
{
    function run()
    {
        $mSubs = \Verba\_mod('subscribe');
        if(!$mSubs->MCUnsubscribe($this->row[$this->A->getCode()])){
            $this->log()->error('Unable to unsubscribe email from MailChimp Service');
        }
        return true;
    }
}
