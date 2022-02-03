<?php

namespace Mod\Sitemap;

class ContextFile extends \Verba\Base
{
    protected $fp;
    protected $filepath = '';
    protected $pinfo = array();

    public $lastmod;
    public $changefreq;

    function __construct($filepath)
    {
        $this->setFilepath($filepath);
        if (!\Verba\FileSystem\Local::needDir(dirname($this->filepath))) {
            $this->log()->error('Unable to reach location: ' . dirname($this->filepath));
        }
    }

    function __destruct()
    {
        if (is_resource($this->fp)) {
            fclose($this->fp);
        }
    }

    function getFp()
    {
        if ($this->fp === null) {
            $this->fp = false;
            $this->fp = $this->createFp();
        }
        return $this->fp;
    }

    function setFilepath($filepath)
    {
        if (!is_string($filepath) || !$filepath) {
            return false;
        }
        $pinfo = pathinfo($filepath);
        if (!$pinfo || !isset($pinfo['filename']) || !$pinfo['filename']) {
            return false;
        }
        $this->pinfo = $pinfo;
        $this->filepath = $filepath;
    }

    function createFp()
    {
        if (!$this->filepath || !$this->pinfo) {
            return false;
        }

        $fp = @fopen($this->filepath, 'w');

        if (!is_resource($fp)) {
            $this->log()->error('Unable to create SitemapContextFile fopen.'
                . "\n" . 'fopen(filepath): ' . var_export($this->filepath, true));

            return false;
        }

        $this->log()->event(__CLASS__ . ' file opened successfull ' . var_export($this->filepath, true));

        return $fp;
    }

    function write($str)
    {
        $fp = $this->getFp();
        if (!$fp) {
            return false;
        }
        return fwrite($fp, (string)$str);
    }

    function close()
    {
        if (!is_resource($this->fp)) {
            return;
        }
        fclose($this->fp);
    }
}
