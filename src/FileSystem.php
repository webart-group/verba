<?php
namespace Verba;

class FileSystem
{
    static function formateFileSize($val)
    {
        if (!is_numeric($val) || !is_int($val = intval($val))) {
            return $val;
        }

        switch (true) {
            case $val >= 1024 && $val < 890000 :
                return (round($val / 1000, 1) . ' KB');
            case $val >= 890000 && $val < 1024000000 :
                return (round($val / 1000000, 1) . ' MB');
            case $val >= 1024000000 :
                return (round($val / 1000000000, 1) . ' GB');
        }
        return $val . " bytes";
    }
}
