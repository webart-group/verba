<?php

namespace Verba\Mod\Image;

class Cleaner extends \Verba\Base
{
    protected $cfgBox;
    protected $filename;
    /**
     * @var object \Model
     */
    protected $oh;

    function __construct($cfg, $filename)
    {

        if ($filename) {
            $this->setFilename($filename);
        }

        if (is_string($cfg) && !empty($cfg)) {
            $this->cfgBox = \Verba\_mod('image')->getImageConfig($cfg);
        } elseif ($cfg instanceof \Verba\Mod\Image\Config) {
            $this->cfgBox = $cfg;
        }
    }

    function setFilename($val)
    {
        $this->filename = (string)$val;
    }

    function delete()
    {
        $i = 0;
        if (!$this->cfgBox instanceof \Verba\Mod\Image\Config
            || !$this->filename) {
            return $i;
        }

        if (!$this->cfgBox->isPrimaryExtracted() || empty($this->filename)) {
            $this->log()->error('Bad image config');
            return false;
        }

        $fsh = new  \Verba\FileSystem\Local();
        //delete copies
        foreach ($this->cfgBox->getCopiesIndexes() as $copyIdx) {
            $c_path = $this->cfgBox->getFullPath($this->filename, $copyIdx);
            if (!$fsh->isFile($c_path) || !$fsh->del_file($c_path)) {
                $this->log()->warning('image was not deleted: filename[' . var_export($c_path, true) . ']', false);
            } else {
                $i++;
            }
        }
        return $i;
    }
}

?>