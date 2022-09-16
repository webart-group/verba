<?php

namespace Verba\Mod;

class Acp extends \Verba\Mod
{
    use \Verba\ModInstance;

    function init(){
        require_once('Acp/Node.php');
        require_once('Acp/Tab.php');
        require_once('Acp/Tabset.php');
    }

    function checkAccess($BParams = false, $rightsData = false, $rUrl = null)
    {

        if (!\Verba\reductionToArray($rightsData)) {
            $rightsData = $this->gC('access_rights');
        }
        if (!is_array($rightsData)) {
            return true;
        }
        $U = \Verba\User();
        foreach ($rightsData as $key => $rights) {
            if (!$U->chr($key, $rights)) {
                return false;
            }
        }
        return true;
    }

     static function loadACPUIClass($class)
    {
        if (($pos = strpos($class, '_')) === false) {
            return false;
        }

        $pref = substr($class, 0, $pos);
        $suff = substr($class, $pos + 1);
        $pathBase = \Verba\_mod('acp')->getPath();
        $filename = $suff . '.php';
        if ($pref == 'ACPNode') {
            $path = $pathBase . '/nodes';

        } elseif ($pref == 'ACPTabset') {
            $path = $pathBase . '/tabsets';
        } elseif ($pref == 'ACPTab') {
            $path = array(
                $pathBase . '/tabs',
                $pathBase . '/tabs/list',
                $pathBase . '/tabs/form',
            );
        } else {
            return false;
        }

        if (!$path) {
            return false;
        }
        if (is_string($path)) {
            $path = array($path);
        } elseif (!is_array($path) || !count($path)) {
            return false;
        }
        foreach ($path as $cpath) {
            if (file_exists($cpath . '/' . $filename)) {
                require_once($cpath . '/' . $filename);
                return true;
            }
        }
        return false;
    }
}
