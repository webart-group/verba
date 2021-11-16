<?php
namespace Verba;

class Cache
{

    public $default_valid_time = 3600;
    public $filename = false;
    public $fileExtension = false;
    private $cache_dir = false;
    private $filepath = false;
    protected $nameParts = array();
    protected $cacheEnable;
    /**
     * @var  \Verba\FileSystem\Local
     */
    protected $fsh;

    function __construct($path)
    {
        global $S;

        $this->cacheEnable = (bool)$S->gC('cacheEnable');

        $this->fsh = new  \Verba\FileSystem\Local();

        if (($slr = strrpos($path, '/')) === false) {
            $filename = $path;
            $dir = false;
        } else {
            $filename = substr($path, $slr + 1);
            $dir = substr($path, 0, $slr + 1);
        }
        $this->setCacheDir($dir);
        $this->setFilename($filename);

    }

    function dpndLocale()
    {
        $this->nameParts['lang'] = SYS_LOCALE;
    }

    function dpndByCurrentUser()
    {
        $this->nameParts['userid'] = \Verba\User()->getID();
    }

    function dpndCustomKey($key)
    {
        $key = (string)$key;
        $this->nameParts[] = $key;
    }

    function setCacheDir($dir)
    {
        if (is_string($dir) && strlen($dir)
            && (strpos($dir, SYS_CACHE_DIR) === 0
                || ((SYS_PLATFORM == 'nix' && mb_substr($dir, 0, 1) == '/')
                    || (SYS_PLATFORM == 'win' && mb_substr($dir, 1, 1) == ':'))
            )) {
            $this->cache_dir = $dir;
        } else {
            $this->cache_dir = SYS_CACHE_DIR . '/' . $dir;
        }
        $this->cache_dir = rtrim($this->cache_dir, '/\\');
         \Verba\FileSystem\Local::needDir($this->cache_dir);
    }

    function getCacheDir()
    {
        return $this->cache_dir;
    }

    function setFilename($filename)
    {
        if (($pos = strrpos($filename, '.')) === false || $pos === 0) {
            $this->filename = $filename;
        } else {
            $this->filename = substr($filename, 0, $pos + 1);
            $this->fileExtension = substr($path, $pos + 1);
        }
    }

    function getFilename()
    {
        return $this->filename;
    }

    function validateDataCache($valid_timeout, $forced = false)
    {
        if (!$forced) {
            if (!$this->cacheEnable) {
                return false;
            }
        }
        $valid_timeout = !is_numeric($valid_timeout) ? $this->default_valid_time : (int)$valid_timeout;
        $cache_file = $this->getFilePath();
        return $this->fsh->fileExists($cache_file) && ($stat = $this->fsh->fileStat($cache_file))
        && (time() - (int)$stat['mtime']) <= $valid_timeout
            ? true
            : false;
    }

    function fileExists()
    {
        return $this->fsh->fileExists($this->getFilePath());
    }

    function getFilePath()
    {
        return is_string($this->filepath)
            ? $this->filepath
            : $this->makeFilePath();
    }

    function makeFilePath()
    {
        $customParts = count($this->nameParts)
            ? '_' . implode('_', $this->nameParts)
            : '';

        $ext = !empty($this->fileExtension)
            ? '.' . $this->fileExtension
            : $this->fileExtension;

        $this->setFilePath($this->cache_dir . '/' . $this->filename . $customParts . $ext);
        return $this->filepath;
    }

    function setFilePath($path)
    {
        $this->filepath = $path;
    }

    function writeDataToCache($info, $plain = false)
    {
        $file_name = $this->getFilePath();
        if (!$plain) $info = '<?php return ' . var_export($info, true) . ' ?>';
        return file_put_contents($file_name, $info) ? true : false;
    }

    function get()
    {
        $cache_file = $this->getFilePath();
        return $this->fsh->fileExists($cache_file)
            ? file_get_contents($cache_file)
            : null;
    }

    function getAsRequire()
    {
        $cache_file = $this->getFilePath();
        return $this->fsh->fileExists($cache_file)
            ? require($cache_file)
            : null;
    }

    function remove()
    {
        if (!$this->fileExists()) {
            return true;
        }
        $cache_file = $this->getFilePath();
        return $this->fsh->del_file($cache_file);
    }

    function clearCacheDir()
    {
        return $this->fsh->dirDeleteRecursive($this->getCacheDir(), false, false, false);
    }
}
