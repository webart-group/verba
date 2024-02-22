<?php

namespace Verba\Mod\MediaPictures\Block;

use Verba\Block\Json;
use Verba\QueryMaker;
use function Verba\_oh;
use function Verba\_mod;
use Verba\Mod\User\Model\User;

class StoreMediaPicture extends Json
{
    function build()
    {
        $req = $this->rq->post();
        /**
         * @var $mMeta \Verba\Mod\MediaPictures
         */

        $mMediaPictures = \Verba\_mod('mediapictures');

        $this->media_data['image_url'] = isset($req['image_url']) ? $req['image_url'] : null;
        $this->media_data['video_id'] = isset($req['video_id']) ? $req['video_id'] : null;
        $this->media_data['high'] = isset($req['high']) ? $req['high'] : null;
        $this->media_data['width'] = isset($req['width']) ? $req['width'] : null;

        if (!is_array($this->media_data)) {
            throw  new \Verba\Exception\Building('Bad data');
        }

        // Get image from url
        $imageContent = file_get_contents($this->media_data['image_url']);

        if ($imageContent === false) {
            throw  new \Verba\Exception\Building('Image download error');
        }

        $imageName = uniqid() . '.jpg';

        // Path to save
        $U = \Verba\getUser();

        $savePath = $mMediaPictures->getMediaPictureStorePath($U);

        \Verba\FileSystem\Local::needDir($savePath);

        $saveFilename = $savePath . '/' . $imageName;

        // Save on server
        if (!file_put_contents($saveFilename, $imageContent)) {
            throw  new \Verba\Exception\Building('Failed to save image on server');
        }

        $updateQuery = "
        INSERT INTO " . SYS_DATABASE . ".media_pictures (
            user_id,
            image_name,
            video_id,
            high,
            width
        )
        VALUES (
            '" . $this->DB()->escape_string($U->getId()) . "',
            '" . $this->DB()->escape_string($imageName) . "',
            '" . $this->DB()->escape_string($this->media_data['video_id']) . "',
            '" . $this->DB()->escape_string($this->media_data['high']) . "',
            '" . $this->DB()->escape_string($this->media_data['width']) . "'
            )
            ";
        $this->DB()->query($updateQuery);

        $this->content = $mMediaPictures->getMediaPictureDownloadUrl($U, $imageName);

        // clear folder every night

        $desiredHour = 0; // 0 hours
        $desiredMinute = 0; // 0 min
        $desiredSecond = 0; // 0 sec

        $currentTimestamp = time();
        $currentDate = getdate($currentTimestamp);

        if ($currentDate['hours'] === $desiredHour && $currentDate['minutes'] === $desiredMinute && $currentDate['seconds'] === $desiredSecond) {
            $mMediaPictures->clearMediaPicturesFolder($U);
        }

        return ['imageDownloadUrl' => $this->content];
    }
}
