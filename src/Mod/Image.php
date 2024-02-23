<?php
namespace Verba\Mod;

class Image extends \Verba\Mod
{
    use \Verba\ModInstance;
    protected $valid_objects = array('image');
    static public $media_types = array('jpg' => 2, 'jpeg' => 2, 'png' => 3, 'gif' => 1);
    protected $tmp_zip_dir = false;
    static protected $imageConfigs = array();

    function addEditNow($bp = null)
    {
        try {

            $result = array();
            $tmpDirs = array();
            set_time_limit(14400);
            $bp = $this->extractBParams($bp);

            $oh = \Verba\_oh('image');

            if (isset($bp['data']) && is_array($bp['data'])) {
                $directData = true;
                $data = $bp['data'];
            } else {
                $data = $_REQUEST['NewObject'][$oh->getID()];
                $directData = false;
            }
            $muob = isset($data['multi_mode']) ? (bool)$data['multi_mode'] : false;

            if (!$muob
                && ((!isset($data[$oh->getPAID()]) && !isset($data[$oh->getPAC()]))
                    && ($iid = $bp['iid']))) {
                $data[$oh->getPAC()] = $iid;
            }
            $formKey = isset($data['ok']) ? $data['ok'] : false;
            unset($data['multi_mode'], $data['ok']);

            //обертка даты если в формат одного объекта
            $data = $muob
                ? $data
                : array(0 => $data);
            $fnId = 'filename';
            $sizeId = 'size';
            if (!$directData) {
                $temp = $data;
                $data = array();
                $mFile = \Verba\_mod('file');
                foreach ($temp as $idx => $c_data) {
                    list($tmpPath, $type, $name, $size) = $mFile->extractFromFiles($muob, $oh, $oh->A('storage_file_name'), $idx);
                    if (!$tmpPath) {
                        $data[] = $c_data;
                        continue;
                    }
                    $c_data[$fnId] = $name;
                    $c_data['_tmp_name'] = $tmpPath;
                    $c_data[$sizeId] = $size;
                    $data[] = $c_data;
                }
            }

            if (!count($data)) {
                throw new \Exception('incoming data error');
            }
            $result['images_error'] =
            $result['images_total'] = count($data);
            foreach ($data as $idx => $imageData) {
                if (!is_array($imageData)) {
                    continue;
                }
                $ae = $oh->initAddEdit(array('action' => $bp['action']));
                $ae->setIndex($idx);
                if ($imageData[$oh->getPAC()]) {
                    $ae->setIID($imageData[$oh->getPAC()]);
                }

                if (isset($bp['pot'])) {
                    $ae->addMultipleParents($bp['pot']);
                }
                if (isset($bp['cfg']) && !empty($bp['cfg'])) {
                    $ae->applyConfig($bp['cfg']);
                }
                if (isset($bp['dcfg']) && !empty($bp['dcfg'])) {
                    $ae->applyConfigDirect($bp['dcfg']);
                }
                $ae->setGettedObjectData($imageData);

                if ($ae->addedit_object()) {
                    $result['images_error']--;
                }
            }
            if (!$ae) {
                throw new \Exception('Operation canceled. Data not found.');
            }
            if ($result['images_error'] > 0) {
                $this->log()->error('Error while image "' . $ae->getAction() . '" totalCount:' . $result['images_total'] . ', error:' . $result['images_error']);
            }
            foreach (($tmpDirs = array_unique($tmpDirs)) as $c_tmpDir) {
                if (!\Verba\FileSystem\Local::dirDeleteRecursive($c_tmpDir)) {
                    $this->log()->warning('Unable to delete tempdir', __METHOD__ . '(' . __LINE__ . ') tmpDir:[' . var_export($c_tmpDir, true) . ']');
                }
            }
            return $ae;
        } catch (\Exception $e) {
            $this->log()->error($e->getMessage());
            return $ae;
        }
    }

    /**
     * @desc Генерирует экземпляр изображения. При переданных $width $height и $type для создания экземпляра будут использованы их значения. При отсутствии любого из них, будет выполнен вызов getimagesize().
     * Если ширина/высота изображения не превышают максимально допустимые значения, изображение будет просто скопировано в $destination
     *
     * @param string $src string Путь к исходному файлу изображения
     * @param string $destination Путь куда сохранять
     * @param bool $maxwidth Максимальная ширина иконки. Если установлено в -1 значит будет принята ширина исходника
     * @param bool $maxheight Максимальная высота иконки. Если установлено в -1 значит будет принята высота исходника.
     * @param bool $width Известная ширина
     * @param bool $height Известная высота
     * @param bool $type Известный тип
     * @param bool $resizeBySmallerSide
     * @param bool $types_quality
     * @return array|bool
     */
    function repackImage($src, $destination, $maxwidth = false, $maxheight = false, $width = false, $height = false, $type = false, $resizeBySmallerSide = false, $types_quality = false)
    {
        $resizeBySmallerSide = (bool)$resizeBySmallerSide;
        $types_quality = !is_array($types_quality) ? array() : $types_quality;

        if (!is_string($destination) || file_exists($destination)) {
            $this->log()->warning('Invalid image target location or file already exists [' . var_export($destination, true) . ']' . __METHOD__ . ' (' . __LINE__ . ')');
            return false;
        }

        if (!file_exists($src)) {
            $this->log()->warning('File is not exists: ' . var_export($src, true));
            return false;
        }

        $r = array('width' => null, 'height' => null, 'type' => null);

        if (!$width || !$height || !$type) {
            list($r['width'], $r['height'], $r['type']) = getimagesize($src);
        } else {
            $r['width'] = $width;
            $r['height'] = $height;
            $r['type'] = $type;
        }
        $maxwidth = is_numeric($maxwidth) && $maxwidth > 0
            ? intval($maxwidth)
            : ($maxwidth == -1 ? $r['width'] : 200);
        $maxheight = is_numeric($maxheight) && $maxheight > 0
            ? intval($maxheight)
            : ($maxheight == -1 ? $r['height'] : false);

        $qkey = false;
        switch ($r['type']) {
            case 1:
                $MtSuff = 'gif';
                break;
            case 2:
                $MtSuff = 'jpeg';
                $qkey = 'jpg';
                break;
            case 3:
                $MtSuff = $qkey = 'png';
                break;
            default :
                $this->log()->warning('Unexpected image format [' . $r['type'] . ']' . __METHOD__ . ' (' . __LINE__ . ')] image_src[' . var_export($src, true) . ']');
                return false;
        }
        $defaultQ = Image\Config::getDefaultCopyPropValue('quality');
        $quality = is_string($qkey)
        && isset($types_quality[$qkey])
        && is_numeric($types_quality[$qkey])
            ? $types_quality[$qkey]
            : (isset($defaultQ[$qkey])
                ? $defaultQ[$qkey]
                : null
            );

        if ($r['width'] > $maxwidth || $r['height'] > $maxheight) {
            $calcMthd = $resizeBySmallerSide ? 'calcImageWHBySmallerSide' : 'calcImageWH';
            list($newwidth, $newheight) = self::$calcMthd($r['width'], $r['height'], $maxwidth, $maxheight);
            if (!$newwidth || !$newheight) {
                $this->log()->error('Bad sizes for image resample. From[' . var_export($src, true) . '] To:[' . var_export($destination, true) . '] img params:[' . var_export($r, true) . ']');
                return false;
            }
            if (!is_resource($thumb = imagecreatetruecolor($newwidth, $newheight))) {
                $this->log()->error('Unable to create image resource to resampling. From[' . var_export($src, true) . '] To:[' . var_export($destination, true) . '] img params:[' . var_export($r, true) . '], quality:[' . var_export($quality, true) . ']');
                return false;
            }

            $imgCreateMethod = 'imagecreatefrom' . $MtSuff;
            $imgFinalizeMethod = 'image' . $MtSuff;

            $source = $imgCreateMethod($src);

            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
            imagefilledrectangle($thumb, 0, 0, $newwidth, $newheight, $transparent);

            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $r['width'], $r['height']);
            $r['width'] = $newwidth;
            $r['height'] = $newheight;
            if (!$imgFinalizeMethod($thumb, $destination, $quality)) {
                $this->log()->error('Unable to save image resampled. From[' . var_export($src, true) . '] To:[' . var_export($destination, true) . '] img params:[' . var_export($r, true) . '], quality:[' . var_export($quality, true) . '].' . "\nFinaliseMethod: " . var_export($imgFinalizeMethod, true));
                return false;
            }
            imagedestroy($source);
            imagedestroy($thumb);
        } elseif (!copy($src, $destination)) {
            $this->log()->error('Unable to copy image. From[' . var_export($src, true) . '] To:[' . var_export($destination, true) . '] img params:[' . var_export($r, true) . '], quality:[' . var_export($quality, true) . ']');
            return false;
        }

        return $r;
    }

    static function calcImageWH($width, $height, $maxwidth = 500, $maxheight = 500)
    {
        $width = (int)$width;
        $height = (int)$height;
        $maxwidth = (int)$maxwidth;
        $maxheight = (int)$maxheight;
        if ($width < 1 || $height < 1 || $maxwidth < 1 || $maxheight < 1) return false;
        $c_rate = $height / $width;
        $m_rate = $maxheight / $maxwidth;

        if ($width != $height) {
            if ($c_rate < $m_rate) {
                $w = $maxwidth;
                $h = round($c_rate * $w);
            } else {
                $h = $maxheight;
                $w = round($h / $c_rate);
            }
        } elseif ($width == $height) {
            $need = min($maxheight, $maxwidth);
            $h = $w = ($height >= $need ? $need : $height);
        }

        return array(0 => $w, 'width' => $w, 1 => $h, 'height' => $h);
    }

    static function calcImageWHBySmallerSide($width, $height, $maxwidth = 500, $maxheight = 500)
    {
        $width = (int)$width;
        $height = (int)$height;
        $maxwidth = (int)$maxwidth;
        $maxheight = (int)$maxheight;
        if ($width < 1 || $height < 1 || $maxwidth < 1 || $maxheight < 1) return false;
        $cr = $height / $width;
        $fr = $maxheight / $maxwidth;

        $rW = $width / $maxwidth;
        $rH = $height / $maxheight;

        // src equal to copy
        if ($width == $maxwidth && $height == $maxheight) {
            $sp = 0;

            // src smaller
        } elseif ($width < $maxwidth && $height < $maxheight) {
            $sp = 1;

            // src smaller by one side
        } elseif (($width > $maxwidth && $height < $maxheight)
            || ($width < $maxwidth && $height > $maxheight)
        ) {
            $sp = 2;

            // src bigger both sides
        } elseif ($width > $maxwidth && $height > $maxheight) {
            $sp = 3;
        }

        if ($sp < 3) {
            $w = $width;
            $h = $height;
            goto ret;
        }

        if ($fr < 1) { //horizontal frame
            if ($cr > 1) {
                $w = $maxwidth;
                $h = round($cr * $w);
            } else {//horizontal image
                if ($fr < $cr) {
                    $w = $maxwidth;
                    $h = round($cr * $w);
                } else {
                    $h = $maxheight;
                    $w = round($h / $cr);
                }
            }
        } elseif ($fr > 1) { // vertical frame
            if ($cr < 1) { //horizontal image
                $h = $maxheight;
                $w = round($h / $cr);
            } else {
                if ($fr < $cr) {
                    $w = $maxwidth;
                    $h = round($cr * $w);
                } else {
                    $h = $maxheight;
                    $w = round($h / $cr);
                }
            }
        } else { //square frame
            if ($cr < 1) {
                $h = $maxheight;
                $w = round($h / $cr);
            } else {
                $w = $maxwidth;
                $h = round($cr * $w);
            }
        }
        ret:
        return array(0 => $w, 'width' => $w, 1 => $h, 'height' => $h);
    }

    static function getMediaTypes()
    {
        return self::$media_types;
    }

    static function getMIMETypeById($id)
    {
        $r = array_keys(self::$media_types, $id);
        return isset($r[0]) ? $r[0] : false;
    }

    static function approve_pic_params($width, $height, $type, $cfg)
    {
        return $width <= $cfg['width'] && $height <= $cfg['height']
        && (!array_key_exists('types', $cfg) || !is_array($cfg['types']) || in_array($type, $cfg['types']))
            ? true
            : false;
    }

    static function approve_remote_pic($src, $cfg)
    {
        $src_fragments = parse_url($src);
        if (!is_array($src_fragments) || !isset($src_fragments['scheme'])) {
            return false;
        }

        if ((isset($cfg['proto']) && !in_array($src_fragments['scheme'], $cfg['proto']))
            || false === (list($width, $height, $type) = @getimagesize($src))
            || !self::approve_pic_params($width, $height, $type, $cfg)
        ) {
            return false;
        }
        return true;
    }

    static function isRemotePicURL($src)
    {
        $src_fragments = parse_url($src);
        return is_array($src_fragments)
        && isset($src_fragments['scheme'])
        && !empty($src_fragments['scheme'])
            ? true : false;
    }

    static function getExifData($imgPath)
    {
        $exif_data = exif_read_data($imgPath);

        $exif2save = array();
        if (!empty($exif_data['Make'])) {
            $exif2save['make'] = $exif_data['Make'];
        }
        if ($exif_data['Model']) {
            $exif2save['model'] = $exif_data['Model'];
        }
        if ($exif_data['DateTimeOriginal']) {
            $exif2save['date'] = $exif_data['DateTimeOriginal'];
        }
        if ($exif_data['ExposureTime']) {
            $exif2save['expo_time'] = $exif_data['ExposureTime'];
        }
        if ($exif_data['COMPUTED']['ApertureFNumber']) {
            $exif2save['aperture'] = $exif_data['COMPUTED']['ApertureFNumber'];
        }
        if ($exif_data['ISOSpeedRatings']) {
            $exif2save['ISO'] = $exif_data['ISOSpeedRatings'];
        }
        if ($exif_data['Orientation']) {
            $exif2save['orientation'] = $exif_data['Orientation'];
        }
        return !empty($exif2save)
            ? $exif2save
            : false;
    }

    function substImage($list, $row, $customCfg = false)
    {
        if (!is_array($row) || !count($row)) {
            return '';
        }
        $tpl = $this->tpl();
        $cfg = array(
            'tpl' => array(
                'image' => '/image/list-builtin/image.tpl'
            ),
            'thumb' => 'thumb',
            'normal' => null
        );
        if (is_array($customCfg) && count($customCfg)) {
            $cfg = array_replace_recursive($cfg, $customCfg);
        }

        $imgCfg = self::getImageConfig($row['_storage_file_name_config']);

        $tpl->define($cfg['tpl']);
        $tpl->assign(array(
            'TITLE' => $row['title'],
            'DESCRIPTION' => $row['description'],
            'THUMB_SRC' => $imgCfg->getFullUrl($row['storage_file_name'], $cfg['thumb']),
            'PRIMARY_SRC' => $imgCfg->getFullUrl($row['storage_file_name']),
        ));

        return $tpl->parse(false, 'image');
    }

    function substPriority($list, $row, $cfg = false)
    {
        return $row['priority'];
    }

    function getImagePath($cfg, $custom_path = '')
    {
        return SYS_UPLOAD_DIR . '/images' . $custom_path;
    }

    function getImageURL($cfg, $custom_url = '')
    {
        return SYS_UPLOAD_URL . '/images' . $custom_url;
    }

    /**
     * Возвращает объект предоставляющий интерфейс работы с конфигом изображений.
     *
     * @param mixed $modCode код модуля
     * @param mixed $confKey ключ в конфиге модуля в котором хранится конфигурация для изображений
     * @return Image\Config
     */
    static function getImageConfig($name)
    {

        if (isset(self::$imageConfigs[$name])) {
            return self::$imageConfigs[$name];
        }
        $filename = 'img.' . $name . '.php';
        $filepath = SYS_CONFIGS_DIR . '/images/' . $filename;
        if (!file_exists($filepath)) {
            throw new \Exception('Image config not found in [' . $filepath . ']');
        }
        $cfg = require_once($filepath);
        self::$imageConfigs[$name] = new Image\Config($cfg);
        self::$imageConfigs[$name]->setName($name);
        return (self::$imageConfigs[$name]);
    }

    /**
     *
     *
     * @param mixed $string
     * @param int $byPriority 1 - desc, -1 - asc, 0 or false - no sort
     * @param mixed $single only first image in set
     * @return mixed
     */
    static function getImgDataFromString($string, $byPriority = 1, $single = false)
    {
        if (!is_string($string) || empty($string)) {
            return false;
        }
        $data = array();

        if (is_integer(strpos($string, ','))) {
            $tmp_data = explode(',', $string);
        } else {
            $tmp_data = array(0 => $string);
        }
        foreach ($tmp_data as $image) {
            $img_data = explode(':', $image);

            $data[] = array(
                'id' => $img_data[0],
                '_storage_file_name_config' => $img_data[2],
                'storage_file_name' => $img_data[3],
                'priority' => $img_data[1],
            );
        }
        $byPriority = intval($byPriority);
        if ($byPriority !== 0) {
            $sortMtd = $byPriority < 0
                ? 'sortImgDataByPriorityAsc'
                : 'sortImgDataByPriorityDesc';
            usort($data, array('Image', $sortMtd));
        }
        if ($single) {
            return array_shift($data);
        }
        return $data;
    }

    static function sortImgDataByPriorityAsc($a, $b)
    {
        if ($a['priority'] == $b['priority']) {
            return 0;
        }
        return ($a['priority'] < $b['priority']) ? -1 : 1;
    }

    static function sortImgDataByPriorityDesc($a, $b)
    {
        if ($a['priority'] == $b['priority']) {
            return 0;
        }
        return ($a['priority'] < $b['priority']) ? 1 : -1;
    }

    function grayAndShift($filepath)
    {
        $r = array('width' => null, 'height' => null, 'type' => null);
        list($r['width'], $r['height'], $r['type']) = getimagesize($filepath);
        switch ($r['type']) {
            case 1:
                $MtSuff = 'gif';
                break;
            case 2:
                $MtSuff = 'jpeg';
                break;
            case 3:
                $MtSuff = 'png';
                break;
            default :
                $this->log()->warning('Unexpected image format [' . $r['type'] . ']' . __METHOD__ . ' (' . __LINE__ . ')] image_src[' . var_export($filepath, true) . ']');
                return false;
        }

        $imgCreateMethod = 'imagecreatefrom' . $MtSuff;
        $imgFinalizeMethod = 'image' . $MtSuff;

        $src = $imgCreateMethod($filepath);
        $src2 = $imgCreateMethod($filepath);

        $dest = imagecreatetruecolor(64, 32);

        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 255, 255, 255, 127);
        imagefill($dest, 0, 0, $transparent);
        imagefilter($src2, IMG_FILTER_GRAYSCALE);
        imagecopy($dest, $src2, 0, 0, 0, 0, 32, 32);
        // Copy and merge
        imagecopy($dest, $src, 32, 0, 0, 0, 32, 32);
        $imgFinalizeMethod($dest, $filepath);
        imagedestroy($src);
        imagedestroy($src2);
        imagedestroy($dest);
    }

    function grayAndPromo($filepath)
    {
        $r = array('width' => null, 'height' => null, 'type' => null);
        list($r['width'], $r['height'], $r['type']) = getimagesize($filepath);
        switch ($r['type']) {
            case 1:
                $MtSuff = 'gif';
                $qty = 9;
                break;
            case 2:
                $MtSuff = 'jpeg';
                $qty = 100;
                break;
            case 3:
                $MtSuff = 'png';
                $qty = 9;
                break;
            default :
                $this->log()->warning('Unexpected image format [' . $r['type'] . ']' . __METHOD__ . ' (' . __LINE__ . ')] image_src[' . var_export($filepath, true) . ']');
                return false;
        }

        $imgCreateMethod = 'imagecreatefrom' . $MtSuff;
        $imgFinalizeMethod = 'image' . $MtSuff;

        $src = $imgCreateMethod($filepath);
        $src2 = $imgCreateMethod($filepath);
        if ($r['width'] > 150) {
            $srcX = ceil(($r['width'] - 150) / 2);
        } else {
            $srcX = 0;
        }
        if ($r['height'] > 150) {
            $srcY = ceil(($r['height'] - 150) / 2);
        } else {
            $srcY = 0;
        }
        $dest = imagecreatetruecolor(300, 150);

        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 255, 0, 0, 127);
        imagefill($dest, 0, 0, $transparent);
        imagefilter($src2, IMG_FILTER_GRAYSCALE);
        imagecopy($dest, $src2, 0, 0, $srcX, $srcY, 150, 150);
        // Copy and merge
        imagecopy($dest, $src, 150, 0, $srcX, $srcY, 150, 150);
        $imgFinalizeMethod($dest, $filepath, $qty);
        imagedestroy($src);
        imagedestroy($src2);
        imagedestroy($dest);
    }

    /**
     * @param $val string
     * @param $attr string
     * @param $oh \Verba\Model
     * @return string
     * @throws \Exception
     */
    static function pictureToImgTag($val, $attr, $oh, $urlOnly = false)
    {
        //$A = $oh->A($attr);

        if (!($iCfg = Image::getImageConfig($oh->p($attr . '_config')))) {
            return '';
        }

        return $urlOnly
            ? $iCfg->getFullUrl(basename($val))
            : "<img src=\"".$iCfg->getFullUrl(basename($val))."\" />";
    }
}
