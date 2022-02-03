<?php
namespace Mod\Meta\Block;

class AddEditNow extends \Verba\Block\Json
{
    function build($data = false)
    {
        $oh = \Verba\_oh('meta');
        $pac = $oh->getPAC();

        $data = is_array($data)
            ? $data
            : $_REQUEST['NewObject'][$oh->getID()];

        foreach ($data as $idx => $itemData) {
            $action = $itemData[$pac] > 0 ? 'editnow' : 'newnow';
            $iid = $itemData[$pac] > 0 ? $itemData[$pac] : false;
            $length = mb_strlen(implode('', $itemData['meta']));
            if ($length == 0 && empty($itemData['rules'])) {
                if ($action == 'newnow') continue;
                if ($action == 'editnow') {
                    $dh = $oh->initDelete();
                    $result = $dh->delete_objects($iid);
                    continue;
                }
            }
            $ae = $oh->initAddEdit(array(
                'action' => $action,
                'key_id' => $oh->getBaseKey(),
                'index' => $idx,
                'iid' => $iid,
            ));
            $ae->setGettedObjectData($itemData);
//            if (isset($bp['pot'])) {
//                $ae->addMultipleParents($bp['pot']);
//            }
            $ae->addedit_object();
        }
        $this->content = '';
        return $this->content;
    }
}