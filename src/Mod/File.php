<?php

namespace Verba\Mod;

class File extends \Verba\Mod
{
    use \Verba\ModInstance;
    static protected $fileConfigs = array();

    function addEditNow($bp = null)
    {
        try {
            $tmpDirs = array();
            set_time_limit(14400);
            $bp = $this->extractBParams($bp);
            list($action) = AddEditHandlers::extractAEFActionsFromURL($bp['action']);
            $oh = \Verba\_oh($bp['ot_id']);

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
            if (!$muob) {
                $data = array(0 => $data);
            }

            $fnId = 'filename';
            $sizeId = 'size';
            if (!$directData) {
                $temp = $data;
                $data = array();
                foreach ($temp as $idx => $c_data) {
                    list($tmpPath, $type, $name, $size, $error) = self::extractFromFiles($muob, $oh, $oh->A($fnId), $idx);
                    if (!$tmpPath) {
                        $data[] = $c_data;
                        continue;
                    }

                    list($ftype, $subtype) = explode('/', $type);
                    if (strpos($subtype, 'zip') !== false
                        || strpos($type, 'application') !== false) {
                        if (!is_array($unzipData = $this->grabFilesFromZip($tmpPath, $c_data, $oh))) {
                            continue;
                        }
                        $tmpImg = current($unzipData);
                        $tmpDirs[] = dirname($tmpImg[$uploadAttrId]);
                        $data = array_merge($data, $unzipData);
                    } else {
                        $c_data[$fnId] = $name;
                        $c_data['_tmp_name'] = $tmpPath;
                        $c_data['_error'] = $error;
                        $c_data[$sizeId] = $size;
                        $data[] = $c_data;
                    }
                }
            }

            if (!count($data)) {
                throw new Exception('incoming data error');
            }
            $aes = array();
            foreach ($data as $idx => $fileData) {
                if (!is_array($fileData)) {
                    continue;
                }
                $aes[] = $ae = $oh->initAddEdit(array(
                    'action' => $action,
                ));
                $ae->setIndex($idx);
                if ($fileData[$oh->getPAC()]) {
                    $ae->setIID($fileData[$oh->getPAC()]);
                }

                if (isset($bp['pot'])) {
                    $ae->addMultipleParents($bp['pot']);
                }
                if (isset($bp['cfg']) && !empty($bp['cfg'])) {
                    $ae->applyConfig($bp['cfg']);
                }
                if (isset($bp['dcfg']) && !empty($bp['dcfg'])) {
                    $ae->applyConfigDirect($bp['cfg']);
                }
                $ae->setGettedObjectData($fileData);

                if (!$ae->addedit_object()) {
                    $this->log()->error('File ' . $ae->getAction() . " error. \$ae dump:\n" . var_export($ae, true));
                }
            }
            if (!count($aes)) {
                throw new Exception('Operation canceled. Data not found.');
            }
            foreach (($tmpDirs = array_unique($tmpDirs)) as $c_tmpDir) {
                if (!\Verba\FileSystem\Local::dirDeleteRecursive($c_tmpDir)) {
                    $this->log()->warning('Unable to delete tempdir', __METHOD__ . '(' . __LINE__ . ') tmpDir:[' . var_export($c_tmpDir, true) . ']');
                }
            }
            return $aes;
        } catch (Exception $e) {
            return $e;
        }
    }

    static function extractFromFiles($muob, $oh, $A, $idx = false)
    {
        $attr_code = $A->getCode();
        if (!isset($_FILES['NewObject']['tmp_name'][$oh->getID()])
            || $muob && !isset($_FILES['NewObject']['tmp_name'][$oh->getID()][$attr_code][$idx])) {
            return array(null, null, null, null);
        }
        if (!$muob) {
            return array(
                $_FILES['NewObject']['tmp_name'][$oh->getID()][$attr_code],
                $_FILES['NewObject']['type'][$oh->getID()][$attr_code],
                $_FILES['NewObject']['name'][$oh->getID()][$attr_code],
                $_FILES['NewObject']['size'][$oh->getID()][$attr_code],
                $_FILES['NewObject']['error'][$oh->getID()][$attr_code]
            );
        } else {
            return array(
                $_FILES['NewObject']['tmp_name'][$oh->getID()][$attr_code][$idx],
                $_FILES['NewObject']['type'][$oh->getID()][$attr_code][$idx],
                $_FILES['NewObject']['name'][$oh->getID()][$attr_code][$idx],
                $_FILES['NewObject']['size'][$oh->getID()][$attr_code][$idx],
                $_FILES['NewObject']['error'][$oh->getID()][$attr_code][$idx]
            );
        }
    }

    /**
     *Обрабатывает архивы при добавлении файлов
     *Распаковывает архив во временную директорию, заполняет список файлов находящимися в архиве изображениями, возвращает имя временной директории
     * @param array $BParams
     * @param $num Порядковый номер файла в массиве $_FILES
     * @return string
     */
    function grabFilesFromZip($zipPath, $objData = false, $oh)
    {
        $tmpZipDir = SYS_VAR_DIR . '/' . $this->getCode() . '/' . \Verba\Hive::make_random_string(10, 10);
        if (!\Verba\FileSystem\Local::needDir($tmpZipDir)) return false;
        $zip = new ZipArchive($zipPath);

        $files = false;
        if ($zip->open($zipPath) === true && $zip->extractTo($tmpZipDir)) {
            $files =  \Verba\FileSystem\Local::scandir($tmpZipDir, 1, true, array(get_class($this), 'unzipedFilesToData'), array($objData, $oh));
        }
        return $files;
    }

    static function unzipedFilesToData($filepath, $isDir, $objData = false, $oh)
    {
        if (!is_string($filepath) || empty($filepath)) return null;
        if (!is_array($objData)) {
            $objData = array();
        }
        $oh = \Verba\_oh($oh);
        $objData[$oh->A('filename')->getID()] = basename($filepath);
        $objData['_tmp_name'] = $filepath;
        $objData[$oh->A('size')->getID()] = filesize($filepath);
        return $objData;
    }

    function genFilePath($fCfg)
    {
        $dir = $fCfg->getPath();
        if (\FileSystem\Local::dirExists($dir)) {
            return $dir;
        }
        if (!\Verba\FileSystem\Local::needDir($dir)) {
            throw new Exception('Unable to create File storage dir. $dir[' . var_export($dir, true) . ']');
        }
        //file_put_contents($dir.'/.htaccess', "deny from all\n");
        return $dir;
    }

    function genFileUrl($fCfg)
    {
        return $fCfg->getPath();
    }

    /**
     * put your comment there...
     *
     * @param mixed $name
     * @return \Verba\Mod\File\Config
     */
    static function getFileConfig($name)
    {
        if (isset(self::$fileConfigs[$name])) {
            return self::$fileConfigs[$name];
        }
        $filename = 'f.' . $name . '.php';
        $filepath = SYS_CONFIGS_DIR . '/files/' . $filename;
        if (!file_exists($filepath)) {
            throw new \Exception('File config not found in [' . $filepath . ']');
        }
        $cfg = require_once($filepath);
        return (self::$fileConfigs[$name] = new File\Config($cfg));
    }
}



