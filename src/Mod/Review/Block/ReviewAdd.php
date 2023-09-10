<?php

namespace Verba\Mod\Review\Block;

use Verba\Block\Json;
use Verba\Mod\Review;

class ReviewAdd extends Json
{
    function build()
    {
        /**
         * @var Review $mReview
         */
        $mReview = \Verba\_mod('review');

        $_review = \Verba\_oh('review');
        $ae = $_review->initAddEdit('new');
        $ae->setGettedObjectData($this->request->post());
        $ae->addedit_object();

//        if (!$mReview->sendCreationNonifyEmail($ae->getObjectData())) {
//            $this->log()->error('Unable to set notify email after review creation');
//        }

        $this->content = $ae->getResponseByFormat();
        return $this->content;
    }
}
