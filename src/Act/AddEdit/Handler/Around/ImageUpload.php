<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class ImageUpload extends Around
{
    function run()
    {
        /**
         * @var $mImage \Verba\Mod\Image
         */

        $mImage = \Verba\_mod('image');
        $attr_code = $this->A->getCode();
        $gettedValue = $this->ah->getGettedValue('filename');
        $gettedValueConfig = $this->ah->getGettedValue('_' . $attr_code . '_config');
        if (!isset($gettedValueConfig)
            || !isset($gettedValue)) {
            return null;
        }
        $imgCfg = \Verba\Mod\Image::getImageConfig($gettedValueConfig);

        if (!$imgCfg->isPrimaryExtracted()) {
            $this->log()->error('Unable to parse Image Conf data');
            return false;
        }

        $fsh = new  \Verba\FileSystem\Local();
        $originalNameId = 'filename';
        $storageId = $this->A->getCode();
        $sizeId = 'size';
        $widthId = 'width';
        $heightId = 'height';
        $typeId = 'type';
        $uploadId = '_tmp_name';

        $imageInfo = pathinfo($this->ah->getGettedValue($originalNameId));

        if (!isset($imageInfo['extension'])) {
            $this->log()->error('Bad file extension');
        }
        $imageInfo['extension'] = strtolower($imageInfo['extension']);

        if ($this->ah->getGettedValue('error') > 0) {
            $this->log()->error('Upload error');
            return false;
        }
        if ($imgCfg->getMaxUploadSize() > 0 && (int)$this->ah->getGettedValue($sizeId) > $imgCfg->getMaxUploadSize()) {
            $this->log()->error('Image size error');
            return false;
        }

        if (!$this->ah->getGettedValue($uploadId)) {
            $this->log()->error('Image file not found');
            return false;
        }

        // получение размеров и типа оригинала
        list($imageInfo[$widthId], $imageInfo[$heightId], $imageInfo[$typeId]) = getimagesize($this->ah->getGettedValue($uploadId));

        // Проверка по типу изображения
        if (!$imgCfg->isExtensionAllowed($imageInfo[$typeId])) {
            $this->log()->error('Image type error');
            return false;
        }

        //создание примари-изображения
        $prmDir = $imgCfg->getPath();
        if (!\Verba\FileSystem\Local::needDir($prmDir)) {
            $this->log()->error('Unable to access to image storage dir [' . var_export($prmDir, true) . ']');
            return false;
        }
        $refreshedData = array();
        // имя хранения изображения
        if ($imgCfg->getKeepOriginalName()) {
            if ($fsh->fileExists($prmDir . '/' . $gettedValue)) {
                if (is_string($generatedName = $fsh->genNewFileName($prmDir . '/' . $gettedValue, true))) {
                    $storagefileName = $imgCfg->getPrefix() . $generatedName;
                }
            } else {
                $storagefileName = $imgCfg->getPrefix() . $gettedValue;
            }
            $refreshedData[$originalNameId] = $storagefileName;
        }

        if (!isset($storagefileName)) {
            $storagefileName = $imgCfg->getPrefix() . \Verba\Hive::make_random_string(10, 10) . '.' . $imageInfo['extension'];
        }

        $prmPath = $prmDir . '/' . $storagefileName;

        if (!is_array($prmResult = $mImage->repackImage($this->ah->getGettedValue($uploadId), $prmPath, $imgCfg->getWidth(), $imgCfg->getHeight(), $imageInfo[$widthId], $imageInfo[$heightId], $imageInfo[$typeId], $imgCfg->getResizeBySmallerSide(), $imgCfg->getQuality()))) {
            $this->log()->error('Unable to create primary image. primaryCopy:[' . var_export($imgCfg->getCopy, true) . '], destination:[' . var_export($prmPath, true) . '] ');
            return false;
        }
        if (is_array($h = $imgCfg->getHandlers())) {
            foreach ($h as $hFuncName) {
                $mImage->$hFuncName($imgCfg->getFullPath(basename($prmPath)));
            }
        }

        //copies
        if ($imgCfg->countCopies() > 1) {
            foreach ($imgCfg->getCopiesIndexes() as $copyIdx) {
                if ($copyIdx == 'primary') continue;
                if (!\Verba\FileSystem\Local::needDir($imgCfg->getPath($copyIdx))) {
                    $this->log()->error('Unable to access to image copy dir. Dir:[' . var_export($imgCfg->getPath($copyIdx), true) . '] Copy params:[' . var_export($imgCfg->getCopy($copyIdx)) . ']');
                    continue;
                }
                $mImage->repackImage($this->ah->getGettedValue($uploadId), $imgCfg->getFullPath($storagefileName, $copyIdx), $imgCfg->getWidth($copyIdx), $imgCfg->getHeight($copyIdx), $imageInfo[$widthId], $imageInfo[$heightId], $imageInfo[$typeId], $imgCfg->getResizeBySmallerSide($copyIdx), $imgCfg->getQuality($copyIdx));
                if (is_array($h = $imgCfg->getHandlers($copyIdx))) {
                    foreach ($h as $hFuncName) {
                        $mImage->$hFuncName($imgCfg->getFullPath($storagefileName, $copyIdx));
                    }
                }
            }
        }
        $refreshedData['url_path'] = $imgCfg->getUrl();
        $refreshedData['width'] = $prmResult['width'];
        $refreshedData['height'] = $prmResult['height'];
        $refreshedData['size'] = filesize($prmPath);
        $refreshedData['type'] = $imageInfo['type'];

        // Генерация exif данных
        if ($imgCfg->getExifSave() && $imageInfo['type'] == 2
            && is_array($exif = $mImage->getExifData($this->ah->getGettedValue($uploadId)))) {
            $refreshedData['exif_data'] = serialize($exif);
        }
        $this->ah->setGettedObjectData($refreshedData);

        // удаление файла предыдущего изображения если произошла замена картинки
        $exists_value = $this->ah->getExistsValue('storage_file_name');
        if ($this->action == 'edit'
            && is_string($exists_value) && !empty($exists_value)
            && $exists_value != $storagefileName // или изменилось значение
        ) {
            $iu = new \Verba\Mod\Image\Cleaner($imgCfg, $exists_value);
            $removed = $iu->delete();
        }
        return $storagefileName;
    }
}
