<?php

namespace Verba\Mod;

use Verba\Request;

class MediaPictures extends \Verba\Mod
{

    use \Verba\ModInstance;

    public $media_data = [];

    function handleMediaPicture(Request $request)
    {
        $req = $request->post();

        $this->media_data['user_id'] =  '547'; // \Verba\getUser()->getID();
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
        $savePath = 'userfiles/media/' . $imageName; // Путь можно изменить по вашему усмотрению

        // Save on server
        if (!file_put_contents($savePath, $imageContent)) {
            throw  new \Verba\Exception\Building('Failed to save image on server');
        }

        $updateQuery = "
        INSERT INTO " . SYS_DATABASE . ".media_pictures (
            url,
            origin_url,
            width,
            high) 
        VALUES (
            '" . $this->DB()->escape_string($imageName) . "',
            '" . $this->DB()->escape_string($media_data['origin_url']) . "',
            '" . $this->DB()->escape_string($media_data['high']) . "',
            '" . $this->DB()->escape_string($media_data['width']) . "'
            )";
        $this->DB()->query($updateQuery);

        $result = json_encode(['imageDownloadUrl' => $savePath]);

        return $result;
    }
}
