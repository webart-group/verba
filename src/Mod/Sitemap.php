<?php

namespace Mod;

class Sitemap extends \Verba\Mod
{

    function generate()
    {

        set_time_limit(3600 * 24 * 2);

        $_catalog = \Verba\_oh('catalog');
        $_product = \Verba\_oh('product');

        $cat_ot_id = $_catalog->getID();

        $handler = new Sitemap\Block\GenerateFile(array(), array(
            'root_catalog_id' => 1,
        ));
        $handler->prepare();
        $handler->build();
        return $handler->getTempFilepath();
    }

    function generateAndReplace()
    {

        $tempFile = $this->generate();
        if (!$tempFile || !file_exists($tempFile) || !is_readable($tempFile)) {

            throw  new \Verba\Exception\Building('Unable to get access to new generated sitemap file.'
                . "\n" . '$tempFile: ' . var_export($tempFile, true));
        }
        $backupFilepath = false;
        $destPath = $this->getFilepath();
        $pinfo = pathinfo($destPath);
        if (!\Verba\FileSystem\Local::is_dir($pinfo['dirname']) && !\Verba\FileSystem\Local::needDir($pinfo['dirname'])) {
            throw  new \Verba\Exception\Building('Destination location is unavaible: ' . var_export($pinfo['dirname'], true));
        }

        if (file_exists($destPath)) {
            $this->log()->event('Old sitemap.xml deleted due generateAndReplace call');
            // copy to backup
            $backupFilepath = SYS_VAR_DIR . '/' . ($pinfo['basename']) . '-' . date('Y-m-d-H-i-s') . '.backup';
            if (!@copy($destPath, $backupFilepath)) {
                throw  new \Verba\Exception\Building('Unable to backup exists sitemap file.');
            }
        }

        if (@copy($tempFile, $destPath)) {
            $this->log()->event('New sitemap.xml successfully copied to work place');
            if ($backupFilepath) {
                 \Verba\FileSystem\Local::del_file($backupFilepath);
            }
        } else {
            if ($backupFilepath && @copy($backupFilepath, $destPath)) {
                $this->log()->event('Previous sitemap successfully restored');
                throw  new \Verba\Exception\Building('Sitemap.xml file copy error. Previous file restored.');
            }
            throw  new \Verba\Exception\Building('Sitemap.xml file copy error.');
        }

        return array(0);
    }

    function getFilepath()
    {
        return $this->gC('path') . '/' . $this->gC('filename');
    }

    function getFileUrl()
    {
        return $this->gC('url') . '/' . $this->gC('filename');
    }

}
