<?php
namespace Verba\Mod;
class Lister extends \Verba\Mod
{
    use \Verba\ModInstance;
    function selectItem()
    {
        $slId = $_REQUEST['slID'];
        $sl = \Verba\init_selection(false, false, $slId);
        if (!$sl) {
            throw new \Exception('Unknown Selection');
        }
        if (!isset($_REQUEST['item'])) {
            throw new \Exception('Item to select not found');
        }
        $r = $sl->add_to_selected($_REQUEST['item']);
        $sl->save2session();
        return $r;
    }

    function unselectItem()
    {
        $slId = $_REQUEST['slID'];
        $sl = \Verba\init_selection(false, false, $slId);
        if (!$sl) {
            throw new \Exception('Unknown Selection');
        }
        if (!isset($_REQUEST['item'])) {
            throw new \Exception('Item to select not found');
        }

        $r = $sl->remove_from_selected($_REQUEST['item']);
        $sl->save2session();
        return $r;
    }

    function optionsState()
    {
        $slId = $_REQUEST['slID'];

        if (!isset($_REQUEST[$slId]['optstate'])) {
            throw new \Exception('Noting to do');
        }
        $nv = array(
            'options' => array('state' => $_REQUEST[$slId]['optstate'])
        );
        $_SESSION['list'][$slId] = $nv;

        return true;
    }

    /**
     * @param $oh \Model
     * @param $dcfg
     * @param $cfgFields
     * @param $cfgFilters
     */
    static function extendCfgByUiConfigurator($oh, &$dcfg, $cfgFields, $cfgFilters)
    {
        if (isset($cfgFields['items'])
            && is_array($cfgFields['items'])
            && count($cfgFields['items'])) {
            if (!isset($dcfg['fields']) || !is_array($dcfg['fields'])) {
                $dcfg['fields'] = array();
            }
            if (!isset($dcfg['order']) || !is_array($dcfg['order'])) {
                $dcfg['order'] = array();
            }

            foreach ($cfgFields['items'] as $fieldData) {
                $A = $oh->A($fieldData['id']);
                if (!$A) {
                    \Verba\Loger::create(__METHOD__ . '::' . __LINE__)->error('Unexists attr in config. ot: ' . $oh->getCode() . ', fieldData: ' . var_export($fieldData, true));
                    continue;
                }
                $acode = $A->getCode();
                $dcfg['fields'][$acode] = [
                    'priority' => isset($fieldData['priority']) && !empty($fieldData['priority'])
                        ? (int)$fieldData['priority']
                        : 0
                ];
                if (isset($fieldData['handler']) && !empty($fieldData['handler'])) {
                    $dcfg['fields'][$acode]['handler'] = $fieldData['handler'];
                }

                if (isset($fieldData['hidden']) && !empty($fieldData['hidden'])
                    && $fieldData['hidden'] == 1) {
                    $dcfg['fields'][$acode]['type'] = 'hidden';
                }

                if (isset($fieldData['headerText']) && !empty($fieldData['headerText'])) {
                    list($thClassName, $thCfg) = \Verba\Hive::stringToHandlerParts($fieldData['headerText']);
                    $dcfg['fields'][$acode]['header']['textHandler'] = array(
                        $thClassName => $thCfg
                    );
                }

            }
        }


        if (is_array($cfgFilters) && isset($cfgFilters['items'])
            && is_array($cfgFilters['items'])
            && count($cfgFilters['items'])) {
            foreach ($cfgFilters['items'] as $fltAlias => $fieldData) {
                if (!isset($dcfg['filters']) || !is_array($dcfg['filters'])) {
                    $dcfg['filters'] = array(
                        'items' => array(),
                    );
                }
                $dcfg['filters']['items'][$fltAlias] = array(
                    'name' => $fieldData['code'],
                    'className' => $fieldData['filtertype']
                );

                if (!empty($fieldData['handler'])) {
                    list($extension, $extensionCfg) = \Verba\Hive::stringToHandlerParts($fieldData['handler']);
                    if (!array_key_exists('ecfg', $dcfg['filters']['items'][$fltAlias])) {
                        $dcfg['filters']['items'][$fltAlias]['ecfg'] = array();
                    }
                    $dcfg['filters']['items'][$fltAlias]['ecfg']['extensions'] = array(
                        $extension => is_array($extensionCfg) ? $extensionCfg : array(),
                    );
                }
            }
        }
    }

}
