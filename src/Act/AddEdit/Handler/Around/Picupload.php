<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class Picupload extends Around
{
    function run()
    {
        $mImage = \Verba\_mod('image');
        $attr_code = $this->A->getCode();
        $configName = $this->oh->p($attr_code . '_config');
        if (!$configName) {
            return null;
        }
        $imgCfg = \Mod\Image::getImageConfig($configName);

        $fsh = new  \Verba\FileSystem\Local();

        $exists_value = $this->action == 'edit'
            ? $this->ah->getExistsValue($attr_code)
            : null;

        $ot_id = $this->oh->getID();
        if ($this->A->isLCD()) {
            $fileName = isset($_FILES['NewObject']['name'][$ot_id][$attr_code][$this->lc]['upl']) && !empty($_FILES['NewObject']['name'][$ot_id][$attr_code][$this->lc]['upl'])
                ? $_FILES['NewObject']['name'][$ot_id][$attr_code][$this->lc]['upl']
                : false;
            $tmp_upl_name = $_FILES['NewObject']['tmp_name'][$ot_id][$attr_code][$this->lc]['upl'];
        } else {
            $fileName = isset($_FILES['NewObject']['name'][$ot_id][$attr_code]['upl']) && !empty($_FILES['NewObject']['name'][$ot_id][$attr_code]['upl'])
                ? $_FILES['NewObject']['name'][$ot_id][$attr_code]['upl']
                : false;
            $tmp_upl_name = $_FILES['NewObject']['tmp_name'][$ot_id][$attr_code]['upl'];
        }

        $gettedValue = $this->ah->getGettedValue($attr_code);
        //local file
        if ($tmp_upl_name) {
            $pinfo = pathinfo($fileName);
            $dir = $imgCfg->getPath();
            // generate file name
            if ($imgCfg->getKeepOriginalName()) {
                if ($fsh->fileExists($dir . '/' . $fileName)) {
                    if (is_string($generatedName = $fsh->genNewFileName($dir . '/' . $fileName, true))) {
                        $pic_FileName = $imgCfg->getPrefix() . $generatedName;
                    }
                } else {
                    $pic_FileName = $imgCfg->getPrefix() . $fileName;
                }
            }

            if (!isset($pic_FileName)) {
                $pic_FileName = $imgCfg->getPrefix() . \Verba\Hive::make_random_string(10, 10) . '_' . time() . '.' . strtolower($pinfo['extension']);
            }
            if (is_array($wm_cfg = $imgCfg->getWatermark())) {
                require_once(SYS_EXTERNALS_DIR . '/watermark/watermark.class.php');
                $watermark = new \watermark($tmp_upl_name, $wm_cfg);
                $watermark->save($tmp_upl_name);
            }
            $targetPath = $imgCfg->getFullPath($pic_FileName);
            if ((is_dir($dir) || $fsh->make_dir($dir))
                && $mImage->repackImage($tmp_upl_name, $targetPath, $imgCfg->getWidth(), $imgCfg->getHeight(), false, false, false, $imgCfg->getResizeBySmallerSide(), $imgCfg->getQuality())) {
                $c_value = $imgCfg->getFullUrl($pic_FileName);
            }
            if (is_array($h = $imgCfg->getHandlers())) {
                foreach ($h as $hFuncName) {
                    $mImage->$hFuncName($imgCfg->getFullPath($pic_FileName));
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
                    $icon = $mImage->repackImage($tmp_upl_name, $imgCfg->getFullPath($pic_FileName, $copyIdx), $imgCfg->getWidth($copyIdx), $imgCfg->getHeight($copyIdx), false, false, false, $imgCfg->getResizeBySmallerSide($copyIdx), $imgCfg->getQuality($copyIdx));
                    if (is_array($h = $imgCfg->getHandlers($copyIdx))) {
                        foreach ($h as $hFuncName) {
                            $mImage->$hFuncName($imgCfg->getFullPath($pic_FileName, $copyIdx));
                        }
                    }
                }
            }

            // Удаление информации о картинке из _FILES
            if ($this->A->isLCD()) {
                unset($_FILES['NewObject']['name'][$ot_id][$attr_code][$this->lc]['upl']);
                unset($_FILES['NewObject']['tmp_name'][$ot_id][$attr_code][$this->lc]['upl']);
            } else {
                unset($_FILES['NewObject']['name'][$ot_id][$attr_code]['upl']);
                unset($_FILES['NewObject']['tmp_name'][$ot_id][$attr_code]['upl']);
            }

            // remote картинка
        } elseif (is_array($gettedValue) && array_key_exists('u', $gettedValue) && is_string($gettedValue['u'])
            && !empty($gettedValue['u'])) {
            $rm_cfg = array(
                'width' => $imgCfg->getWidth(),
                'height' => $imgCfg->getHeight(),
                'proto' => $imgCfg->getFromRaw('remote allowed_proto'),
                'types' => $imgCfg->getExtensions());
            if ($mImage->approve_remote_pic($gettedValue['u'], $rm_cfg)) {
                $c_value = $gettedValue['u'];
            }
        }

        // Удаление предыдущей
        if ($this->action == 'edit'
            && is_string($exists_value) && !empty($exists_value)
            && (isset($this->gettedObjectData[$attr_code]['delete']) //отмечено удаление
                || is_string($c_value) && $exists_value != $c_value // или изменилось значение
            )
        ) {
            if (!\Mod\Image::isRemotePicURL($exists_value)) { // предыдущее значение не url
                $iu = new \Mod\Image\Cleaner($imgCfg, basename($exists_value));
                $removed = $iu->delete();
            } else {
                $c_value = '';
            }

            if (!isset($c_value)) $c_value = '';
        }

        return $c_value;
    }
}
