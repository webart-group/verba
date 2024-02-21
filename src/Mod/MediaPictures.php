<?php

namespace Verba\Mod;

use Verba\Request;
use Verba\Mod\User\Model\User;

class MediaPictures extends \Verba\Mod
{
    use \Verba\ModInstance;

    public $media_data = [];

    public function getMediaPictureStorePath(User $user, string $filename = null): string
    {
        $path = $user->getFileStorePath() . '/media_pictures';

        if ($filename) {
            $path .= '/' . $filename;
        }

        return $path;
    }

    public function getMediaPictureDownloadUrl(User $user, string $filename = null): string
    {
        $url = $user->getFileStoreUrl() . '/media_pictures';
        if ($filename) {
            $url .= '/' . $filename;
        }

        return $url;
    }

    public function clearMediaPicturesFolder(User $user)
    {
        $path = $user->getFileStorePath() . '/media_pictures';

        $files = glob($path . '/*');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
