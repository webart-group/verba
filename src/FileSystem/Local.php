<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 21.08.19
 * Time: 17:55
 */

namespace Verba\FileSystem;


class Local extends  \Verba\FileSystem{

    static protected $_finfo = array();

    static function is_dir($dirpath){
        return is_dir($dirpath) ? true : false;
    }

    static function dirExists($dirpath){
        return self::is_dir($dirpath);
    }

    static function make_dir($path){
        $root = SYS_ROOT;
        $reqPath = substr($path, strlen($root) + 1);

        $dir_list = explode('/', $reqPath);
        $dbg = array(
            'path' => &$path,
            'reqPath' => &$reqPath,
            'dir_list' => &$dir_list
        );
        foreach($dir_list as $c_dir){
            if (!is_dir($root."/".$c_dir) && !@mkdir($root.'/'.$c_dir) ){
                \Verba\Loger::create(__CLASS__)->error('Unable to create ['.$root.'/'.$c_dir.'] dbg['.var_export($dbg, true).']');
                return false;
            }
            $root .= "/$c_dir";
        }

        return true;
    }

    static function needDir($dir, $chmod = 0775){
        if(!settype($dir, 'string') || empty($dir))
            return false;

        if(self::is_dir($dir))
            return true;

        if(!($r = self::make_dir($dir))){
            \Verba\Loger::create(__CLASS__)->error(__METHOD__." dir not exists: ".var_export($dir, true)." Attemp to create it... " . var_export($r, true));
            return false;
        }

        if($chmod && $r && \Verba\Hive::getPlatform() == 'unix'){
            chmod($dir, $chmod);
        }

        return $r;
    }

    static function isFile($target){
        return is_file($target);
    }

    static function fileExists($target){
        return file_exists($target);
    }

    static function fileStat($target){
        return stat($target);
    }

    static function genNewFileName($target, $fileNameOnly = false){
        $i = 0;
        $file_parts = pathinfo($target);

        do{
            $filename = $file_parts['filename']."($i).".$file_parts['extension'];
            $candidate = $file_parts['dirname'].'/'.$filename;
            if (!self::fileExists($candidate)){
                return $fileNameOnly ? $filename : $candidate;
            }
            $i++;
        }while ($i < 15000);

        return false;
    }

    function move($temp_file_name, $targetTo){
        return rename($temp_file_name, $targetTo) ? true : false;
    }

    static function del_dir($target){
        return @rmdir($target) ? true : false;
    }
    /**
     * Recursively deletes dir and files by the specified path.
     *
     * @param string $dir_name Directory to delete all it content
     * @param array $ext_incl Include files extensions. If set, only this filetypes will be deleted
     * @param array $ext_excl Exclude files extensions. If set, this filetypes will not be deleted
     * @param bool $delItself Delete or not $dir_name
     */
    static function dirDeleteRecursive($dir_name, $ext_incl = false, $ext_excl = false, $delItself = true){
        $delItself = $delItself === null ? true : (bool)$delItself;
        if(!self::is_dir($dir_name) || !is_resource($dir_handle = opendir($dir_name))){
            return false;
        }

        while (false !== ($filename = readdir($dir_handle))) {
            if($filename == '.' || $filename == '..'){
                continue;
            }
            $path = $dir_name.'/'.$filename;
            if(self::is_dir($path)){
                self::dirDeleteRecursive($path, $ext_incl, $ext_excl, null);
            }else{
                $fileInfo = pathinfo($path);
                if(is_array($ext_incl) && !in_array($fileInfo['extension'], $ext_incl) || is_array($ext_excl) && in_array($fileInfo['extension'], $ext_excl)){
                    continue;
                }
                self::del_file($path);
            }
        }
        closedir($dir_handle);
        if(self::is_empty_dir($dir_name) && $delItself){
            self::del_dir($dir_name);
        }
        return true;
    }

    static function del_file($target){
        return unlink($target);
    }
    /**
     * Возвращает содержимое директории для пути $target
     *
     * @param string $target путь в файловой системе
     * @param int $mode 1 - только файлы; 2 - только директории; 3 - все в виде массивов; 0 - результат работы родной scandir().
     * @param bool $fullpath - в случае true, сохраняет полные пути найденных объектов
     * @param mixed $callback - калбэк метод применяемый к каждому найденному объекту
     * @param mixed $callback_args - аргументы для $callback
     * @param mixed $r - рекурсионный накопитель результатата
     *
     * @return array $mode = 3 - массив вида array('d' =>array(...), 'f' => array(...)); $mode = 1 массив файлов; $mode = 2 - массив директорий. $mode = 0 возвращает результат нативной scandir()
     */
    static function scandir($target, $mode = 3, $fullpath = false, $callback = false, $callback_args = null, $r = array('f'=>array(), 'd' => array())){
        if(!self::is_dir($target) || !is_resource($dir_handle = @opendir($target))) return false;
        if(!is_int($mode = intval($mode)) || $mode > 3 || $mode < 0 ) $mode = 3;
        $fullpath = (bool) $fullpath;
        if($mode == 0) return @scandir($target);
        $callFirst = true;

        if(is_array($callback) || is_string($callback)){
            if(!is_array($callback_args)) $callback_args = array();
        }else{
            $callback = false;
        }
        $c_args = $callback_args;
        while (false !== ($filename = readdir($dir_handle))){
            if($filename == '.' || $filename == '..') continue;
            $isD = self::is_dir($target.'/'.$filename) ? true : false;
            if($fullpath){
                $filename = $target.'/'.$filename;
            }
            $fname = $filename;

            if($callback !== false){
                if($callFirst){
                    $callFirst = false;
                    array_unshift($c_args, $filename, $isD);
                }else{
                    $c_args[0] = $filename;
                    $c_args[1] = $isD;
                }

                $valueAndKey = call_user_func_array($callback, $c_args);
                if($valueAndKey === null) continue;

                if(is_array($valueAndKey)){
                    $fkey = array_key_exists(1, $valueAndKey) ? $valueAndKey[1] : null;
                    $filename = array_key_exists(0, $valueAndKey) ? $valueAndKey[0] : null;
                }else{
                    $fkey = null;
                    $filename = $valueAndKey;
                }
            }

            if($mode & 2 && $isD){
                if(isset($fkey)){
                    $r['d'][$fkey] = $filename;
                }else{
                    $r['d'][] = $filename;
                }
            }elseif($mode & 1 && !$isD){
                if(isset($fkey)){
                    $r['f'][$fkey] = $filename;
                }else{
                    $r['f'][] = $filename;
                }
            }elseif($mode & 1 && $isD){
                $r['f'] = self::scandir($fname, $mode, $fullpath, $callback, $callback_args, $r);
            }
        }
        closedir($dir_handle);

        return $mode == 3 ? $r : ($mode & 2 ? $r['d'] : $r['f']);
    }

    static function is_empty_dir($target){
        if(!is_resource($dir_handle = @opendir($target))) return null;
        for($i= 0; $i < 3, false != ($filename = readdir($dir_handle)); $i++){}

        closedir($dir_handle);
        return $i == 2 ? true : false;
    }

    static function getFileInfoResource($type){
        if(is_int($type) && (is_object(self::$_finfo[$type]) || false != (self::$_finfo[$type] = new \finfo($type, FILEINFO_MAGIC)))){
            return self::$_finfo[$type];
        }
        if(!ini_get('mime_magic.magicfile')){
            \Verba\Loger::create(__CLASS__)->error('Unable to get mime_magic.magicfile php.ini parameter.');
        }
        \Verba\Loger::create(__CLASS__)->error('Unable to create finfo copy. $type is ['.var_export($type, true).']');
        return false;
    }

    static function getMIME($path){
        if(!$finfo = self::getFileInfoResource(FILEINFO_MIME)){
            return false;
        }
        return $finfo->file($path);
    }

    static function outputFile($filepath, $filesize = null){
        if($filesize == null){
            $filesize = filesize($filepath);
        }
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.basename($filepath));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . $filesize);
        return readfile($filepath);
    }

    static function chmod($path, $chmod = 0775){
        if(\Verba\Hive::getPlatform() == 'unix' && $chmod){
            chmod($path, $chmod);
        }
    }
}