<?php

namespace Mod\Image;


class Config extends \Verba\Base
{

    protected $name;
    protected $rawCfg;
    protected $extensions = array();
    protected $maxUploadSize = 0;
    protected $maxFilesToUpload = false;
    protected $copies = array();
    protected static $_copy = array(
        'path' => '',
        'path_compiled' => null,
        'url' => '',
        'url_compiled' => null,
        'prefix' => null,
        'width' => -1,
        'height' => -1,
        'resizeBySmallerSide' => false,
        'quality' => array(
            'jpg' => 90,
            'png' => 7
        )
    );

    protected $exifSave;
    protected $keepOriginalName;
    protected $watermark;

    function __call($mth, $args)
    {
        $action = substr($mth, 0, 2);
        $propertie = strtolower(substr($mth, 3));
        if (!is_string($propertie) || !isset($this->copies['primary'][$propertie])
            || ($action != 'set' && $action != 'get')) {
            throw new \Exception('Call undefined method - ' . __CLASS__ . '::' . $mth . '()');
        }
        $realMthd = $action . 'Propertie';
        array_unshift($args, $propertie);
        return call_user_func_array(array($this, $realMthd), $args);
    }

    function __construct($cfg)
    {
        if (!is_array($cfg)) return false;

        if (isset($cfg['copyDefault']) && is_array($cfg['copyDefault'])) {
            self::$_copy = array_replace_recursive(self::$_copy, $cfg['copyDefault']);
        }
        $this->copies['primary'] = self::$_copy;
        $this->copies['primary']['path'] = SYS_UPLOAD_DIR;
        $this->copies['primary']['url'] = SYS_UPLOAD_URL;
        $this->rawCfg = $cfg;
        $this->parseRawCfg($this->rawCfg);
    }

    public static function getDefaultCopyPropValue($prop)
    {
        return is_string($prop) && isset(self::$_copy[$prop]) ? self::$_copy[$prop] : null;
    }

    function setName($val)
    {
        $this->name = (string)$val;
    }

    function getName()
    {
        return $this->name;
    }

    public function parseRawCfg($cfg)
    {
        //Получение значений для примари объекта из ствола конфига
        $this->setCopyPropsFromArray($cfg);

        //формирование primary из конфигов экземпляров
        if (isset($cfg['copies']) && is_array($cfg['copies']) && count($cfg['copies'])) {
            if (isset($cfg['copies']['primary']) && is_array($cfg['copies']['primary'])) {
                $this->setCopyPropsFromArray($cfg['copies']['primary']);
                unset($cfg['copies']['primary']);
                // если до сих пор не сформирован конфиг примари - попытка взять
                // первый конфиг из экземпляров
            } elseif (!$this->isPrimaryExtracted()) {
                reset($cfg['copies']);
                list($_key, $copy) = each($cfg['copies']);
                $this->setCopyPropsFromArray($copy);
                unset($cfg['copies'][$_key]);
            }
            if (count($cfg['copies'])) {
                foreach ($cfg['copies'] as $_key => $copy) {
                    $this->setCopyPropsFromArray($copy, $_key);
                }
            }
        }

        if (!$this->isPrimaryExtracted()) {
            throw new \Exception('Cant get valid primary copy parameters');
        }

        // allowed Types
        if (isset($cfg['types']) && is_array($cfg['types'])) {
            $this->extensions = $cfg['types'];
        }
        //maxUploadSize
        if (isset($cfg['maxUploadSize'])) {
            $this->setMaxUploadSize($cfg['maxUploadSize']);
        }

        // Exif
        if (isset($cfg['save_exif'])) {
            $this->setExifSave($cfg['save_exif']);
        }

        // keep_original_name
        if (isset($cfg['keepOriginalName'])) {
            $this->setKeepOriginalName($cfg['keepOriginalName']);
        }
        // watermark
        if (isset($cfg['watermark']) && is_array($cfg['watermark'])) {
            $this->setWatermark($cfg['watermark']);
        }
    }

    public function getFromRaw()
    {
        $args = func_get_args();

        if (count($args) > 0 && is_array($this->rawCfg)) {
            if (count($args) == 1 && strpos($args[0], ' ') !== false) {
                $args = preg_split("/\s+/", $args[0]);
            }
            $v = &$this->rawCfg;
            foreach ($args as $c_node) {
                if (!is_array($v) || !array_key_exists($c_node, $v)) return null;
                $v = &$v[$c_node];
            }
        }
        return $v;
    }

    private function setCopyPropsFromArray($arr, $idx = 'primary')
    {
        $idx = !$idx || !is_string($idx) ? 'primary' : $idx;
        if (!isset($this->copies[$idx])) {
            $this->copies[$idx] = self::$_copy;
        }
        $this->setPath($arr, $idx);
        $this->setUrl($arr, $idx);
        if (isset($arr['width'])) $this->setWidth($arr['width'], $idx);
        if (isset($arr['height'])) $this->setHeight($arr['height'], $idx);
        if (isset($arr['prefix'])) $this->setPrefix($arr['prefix'], $idx);
        if (isset($arr['resizeBySmallerSide'])) $this->setResizeBySmallerSide($arr['resizeBySmallerSide'], $idx);
        if (isset($arr['handlers'])) $this->setHandlers($arr['handlers'], $idx);
        if (isset($arr['watermark'])) $this->setCopyWatermark($arr['watermark'], $idx);
        if (isset($arr['quality'])) $this->setQuality($arr['quality'], $idx);
        $this->setPathCompiled($this->compilePath($idx), $idx);
        $this->setUrlCompiled($this->compileUrl($idx), $idx);
    }

    public function getCopy($idx = 'primary')
    {
        $idx = !$idx || !is_string($idx) ? 'primary' : $idx;
        return isset($this->copies[$idx]) ? $this->copies[$idx] : null;
    }

    /**
     * @param $iids array | Image ID
     */
    public function getCopies($iids = null)
    {
        if ($iids === null) {
            return $this->copies;
        }
        $r = null;
        if (is_string($iids)) {
            if ($without = ($iids{0} == '!')) {
                $iids = substr($iids, 1);
            }
            $iids = preg_split('/[,;\s]/', $iids);
            if (!is_array($iids)) {
                return null;
            }
            $withoutKeys = array('');
            if ($without && is_array($iids)) {
                $withoutKeys = array_merge($withoutKeys, $iids);
            }
        } elseif (is_numeric($iids)) {
            $iids = array($iids);
        } elseif (!is_array($iids)) {
            return null;
        }

        $r = isset($without) && $without
            ? array_diff_key($this->copies, array_flip($withoutKeys))
            : array_intersect_key($this->copies, array_flip($iids));

        return $r;
    }

    public function countCopies()
    {
        return count($this->copies);
    }

    public function getCopiesIndexes()
    {
        return array_keys($this->copies);
    }

    function isPrimaryExtracted()
    {
        if (empty($this->copies['primary']['path']) || !$this->copies['primary']['width'] || !$this->copies['primary']['height']) {
            return false;
        }
        return true;
    }

    private function setPath($cfg, $idx = 'primary')
    {
        $idx = !$idx || !is_string($idx) ? 'primary' : $idx;
        $dir = (string)$this->getPath();
        if (is_string($cfg)) {
            $cfg = array('path' => $cfg);
        }
        if (is_array($cfg['path'])) {
            if (!isset($cfg['path'][0]) || !is_array($cfg['path'][0]) || !isset($cfg['path'][0][0])) {
                throw new \Exception('Bad path generator format');
            }
            $call = count($cfg['path'][0]) > 1 && isset($cfg['path'][0][1])
                ? array(\Verba\_mod($cfg['path'][0][0]), $cfg['path'][0][1])
                : $cfg['path'][0][0];
            if (count($cfg['path']) > 1) {
                $call_args = array_merge(array($cfg), array_slice($cfg['path'], 1));
            } else {
                $call_args = array($cfg);
            }
            $dir = call_user_func_array($call, $call_args);
        } elseif (is_string($cfg['path'])) {
            $dir = '/' == $cfg['path']{0}
                ? SYS_ROOT . $cfg['path']
                : $dir . '/' . $cfg['path'];
        }

        $this->copies[$idx]['path'] = rtrim($dir, '/');
    }

    public function getPath($idx = 'primary')
    {
        return isset($this->copies[$idx]) ? $this->copies[$idx]['path'] : null;
    }

    private function setPathCompiled($str, $idx = 'primary')
    {
        $this->copies[$idx]['path_compiled'] = $str;
    }

    public function getPathCompiled($idx = 'primary')
    {
        return isset($this->copies[$idx]) ? $this->copies[$idx]['path_compiled'] : null;
    }

    private function compilePath($idx = 'primary')
    {
        $pfx = (string)$this->getPrefix($idx);
        $str = $this->getPath($idx) . '/' . $pfx;
        return $str;
    }

    public function getFullPath($fileName, $idx = 'primary')
    {
        if (!isset($this->copies[$idx])) return false;
        return $this->getPathCompiled($idx) . $fileName;
    }

    public function getFilepath($fileName, $idx = 'primary')
    {
        return $this->getFullPath($fileName, $idx);
    }

    private function setUrl($cfg, $idx = 'primary')
    {
        $url = (string)$this->getUrl();
        if (is_string($cfg)) {
            $cfg = array('url' => $cfg);
        }
        if (is_array($cfg['url'])) {
            if (!isset($cfg['url'][0]) || !is_array($cfg['url'][0]) || !isset($cfg['url'][0][0])) {
                throw new \Exception('Bad url generator format');
            }
            $call = count($cfg['url'][0]) > 1 && isset($cfg['url'][0][1])
                ? array(\Verba\_mod($cfg['url'][0][0]), $cfg['url'][0][1])
                : $cfg['url'][0][0];
            if (count($cfg['url']) > 1) {
                $call_args = array_merge(array($cfg), array_slice($cfg['url'], 1));
            } else {
                $call_args = array($cfg);
            }
            $url = call_user_func_array($call, $call_args);
        } elseif (is_string($cfg['url']) && strlen($cfg['url']) != 0) {
            $url = ('/' == $cfg['url'][0]
                ? $cfg['url']
                : $url . '/' . $cfg['url']);
        }

        $this->copies[$idx]['url'] = rtrim($url, '/');
    }

    public function getUrl($idx = 'primary')
    {
        $idx = !$idx || !is_string($idx) ? 'primary' : $idx;
        return isset($this->copies[$idx]) ? $this->copies[$idx]['url'] : '';
    }

    private function setUrlCompiled($str, $idx = 'primary')
    {
        $this->copies[$idx]['url_compiled'] = $str;
    }

    public function getUrlCompiled($idx = 'primary')
    {
        return isset($this->copies[$idx]) ? $this->copies[$idx]['url_compiled'] : null;
    }

    private function compileUrl($idx = 'primary')
    {
        $pfx = (string)$this->getPrefix($idx);
        return $this->getUrl($idx) . '/' . $pfx;
    }

    public function getFullUrl($fileName, $idx = 'primary')
    {
        $idx = !$idx || !is_string($idx) ? 'primary' : $idx;
        if (!isset($this->copies[$idx])) return false;
        return $this->getUrlCompiled($idx) . $fileName;
    }

    public function getFileUrl($fileName, $idx = 'primary')
    {
        return $this->getFullUrl($fileName, $idx);
    }

    private function setPrefix($val, $idx = 'primary')
    {
        $this->copies[$idx]['prefix'] = (string)$val;
    }

    public function getPrefix($idx = 'primary')
    {
        return isset($this->copies[$idx]['prefix']) ? $this->copies[$idx]['prefix'] : '';
    }

    private function setWidth($val, $idx = 'primary')
    {
        $this->copies[$idx]['width'] = $val;
    }

    public function getWidth($idx = 'primary')
    {
        return isset($this->copies[$idx]['width']) ? $this->copies[$idx]['width'] : null;
    }

    private function setHeight($val, $idx = 'primary')
    {
        $this->copies[$idx]['height'] = $val;
    }

    public function getHeight($idx = 'primary')
    {
        return isset($this->copies[$idx]['height']) ? $this->copies[$idx]['height'] : null;
    }

    private function setExifSave($val)
    {
        $this->exifSave = (bool)$val;
    }

    public function getExifSave()
    {
        return $this->exifSave;
    }

    private function setKeepOriginalName($val)
    {
        $this->keepOriginalName = (bool)$val;
    }

    public function getKeepOriginalName()
    {
        return $this->keepOriginalName;
    }

    private function setMaxUploadSize($val)
    {
        if (is_numeric($val)) $this->maxUploadSize = intval($val);
    }

    public function getMaxUploadSize()
    {
        return $this->maxUploadSize;
    }

    private function setMaxFilesToUpload($val)
    {
        if (is_numeric($val)) $this->maxFilesToUpload = intval($val);
    }

    public function getMaxFilesToUpload()
    {
        return $this->maxFilesToUpload;
    }

    public function getMaxNumberOfFiles()
    {
        return $this->getMaxFilesToUpload();
    }

    public function setMaxNumberOfFiles($val)
    {
        return $this->setMaxFilesToUpload();
    }

    private function setWatermark($val)
    {
        if (is_array($val)) $this->watermark = $val;
    }

    public function getWatermark()
    {
        return $this->watermark;
    }

    private function setCopyWatermark($val, $idx = 'primary')
    {
        if (!$val) {
            $this->copies[$idx]['watermark'] = null;
        }
        $this->copies[$idx]['watermark'] = isset($this->copies[$idx]['watermark']) && is_array($this->copies[$idx]['watermark']) && is_array($val)
            ? array_replace_recursive($this->copies[$idx]['watermark'], $val)
            : $val;
    }

    public function getCopyWatermark($idx = 'primary')
    {
        return $this->copies[$idx]['watermark'];
    }

    public function setExtensions($val = null)
    {
        if ($val === null) {
            $this->extensions = array();
        }

        if (!is_array($val) && is_string($val)) {
            $val = array($val);
        }

        if (!$val || !is_array($val)) {
            return false;
        }

        $this->extensions = $val;
    }

    public function inExtensions($extension)
    {
        if (!count($this->extensions)) return true;
        if (is_numeric($extension)) {
            $t = \Mod\Image::getMIMETypeById($extension);
            $extension = $t;
        }
        return in_array($extension, $this->extensions);
    }

    public function isExtensionAllowed($type)
    {
        return $this->inExtensions($type);
    }

    public function getExtensions()
    {
        return $this->extensions;
    }

    protected function setResizeBySmallerSide($val, $idx = 'primary')
    {
        $this->copies[$idx]['resizeBySmallerSide'] = (bool)$val;
    }

    public function getResizeBySmallerSide($idx = 'primary')
    {
        return isset($this->copies[$idx]['resizeBySmallerSide']) ? $this->copies[$idx]['resizeBySmallerSide'] : null;
    }

    protected function setQuality($val, $idx = 'primary')
    {
        if (!is_array($val)) {
            return false;
        }

        $valid = array_intersect_key($val, self::$_copy['quality']);
        if (!count($valid)) {
            return false;
        }

        $this->copies[$idx]['quality'] = array_replace_recursive($this->copies[$idx]['quality'], $val);
    }

    public function getQuality($idx = 'primary')
    {
        return isset($this->copies[$idx]['quality'])
            ? $this->copies[$idx]['quality'] : null;
    }

    private function setHandlers($val, $idx = 'primary')
    {
        if (!is_array($val) || !count($val)) {
            return false;
        }
        $this->copies[$idx]['handlers'] = $val;
    }

    public function getHandlers($idx = 'primary')
    {
        return isset($this->copies[$idx]['handlers']) && is_array($this->copies[$idx]['handlers'])
            ? $this->copies[$idx]['handlers']
            : false;
    }
}
