<?php

namespace Verba\Mod;

use Verba\Request;
use Verba\Mod\User\Model\User;

class MediaPictures extends \Verba\Mod
{
    use \Verba\ModInstance;

    public $media_data = [];

    function handleMediaPicture(Request $request)
    {
        $req = $request->post();

        $this->media_data['image_url'] = isset($req['image_url']) ? $req['image_url'] : null;
        $this->media_data['source_url'] = isset($req['source_url']) ? $req['source_url'] : null;
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

        $savePath = $this->getMediaPictureStorePath($U);

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
            download_url,
            source_url,
            image_url,
            high,
            width) 
        VALUES (
            '" . $this->DB()->escape_string($U->getId()) . "',
            '" . $this->DB()->escape_string($imageName) . "',
            '" . $this->DB()->escape_string($savePath) . "',
            '" . $this->DB()->escape_string($this->media_data['source_url']) . "',
            '" . $this->DB()->escape_string($this->media_data['image_url']) . "',
            '" . $this->DB()->escape_string($this->media_data['high']) . "',
            '" . $this->DB()->escape_string($this->media_data['width']) . "'
            )";
        $this->DB()->query($updateQuery);

        return ['imageDownloadUrl' => $this->getMediaPictureDownloadUrl($U, $imageName)];
    }

    public function getMediaPictureStorePath(User $user, string $filename = null): string
    {
        $path = $user->getFileStorePath() . '/media_pictures';

        if($filename) {
            $path .= '/' . $filename;
        }

        return $path;
    }

    public function getMediaPictureDownloadUrl(User $user, string $filename = null): string
    {
        $url = $user->getFileStoreUrl() . '/media_pictures';
        if($filename) {
            $url .= '/' . $filename;
        }

        return $url;
    }
}
