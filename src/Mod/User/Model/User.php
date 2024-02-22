<?php
namespace Verba\Mod\User\Model;

class User extends \Verba\Model\Item
{

    protected $_requiredData = false;
    protected $otype = 'user';
    protected $id;

    protected $fileStoreKeyHash;
    protected $groups;

    protected $authorized = false;
    protected $rights = array();
    protected $all_rights_allowed = array('s' => 1, 'c' => 1, 'u' => 1, 'd' => 1);
    protected $all_rights_denied = array('s' => 0, 'c' => 0, 'u' => 0, 'd' => 0);
    protected $rights_codes = array('s', 'c', 'u', 'd');

    protected $refreshedAt = 0;

    protected $_controllers = array();
    /**
     * @var $Accounts \Verba\Mod\User\Model\User\Controller\Accounts
     */
    public $Accounts;
    /**
     * @var $Stores \Verba\Mod\User\Model\User\Controller\Stores
     */
    public $Stores;

    use \Verba\Model\LastActivityStatus;

    function __construct($data = null)
    {

        parent::__construct($data);

        $this->removeGroup();
        $this->resetRights();
        $this->setAuthorized();
        $this->setFileStoreKeyHash($this->makeFileStoreKeyHash());
        $this->refreshedAt = time();

        $this->addControllers();
    }

    function __wakeup()
    {
        parent::__wakeup();
        $this->addControllers();
    }

    function addControllers()
    {
        $this->addController(new User\Controller\Accounts($this->getId()), 'Accounts');
        $this->addController(new User\Controller\Stores($this), 'Stores');
    }

    /**
     * @return User\Controller\Stores
     */
    function Stores()
    {
        return $this->_controllers['Stores'];
    }

    /**
     * @return User\Controller\Accounts
     */
    function Accounts()
    {
        return $this->_controllers['Accounts'];
    }

    function setUser_id($iid)
    {
        $this->data[$this->oh->getPAC()] = (int)$iid;
        $this->id = $this->data[$this->oh->getPAC()];
    }

    /*
      function getValue($propName){
        return $this->__get($propName);
      }
    */
    function getFileStoreKeyHash()
    {
        return $this->fileStoreKeyHash;
    }

    function setFileStoreKeyHash($val)
    {
        if (is_string($val) && !empty($val)) {
            $this->fileStoreKeyHash = $val;
        } else {
            $this->fileStoreKeyHash = false;
        }
        return;
    }

    function makeFileStoreKeyHash()
    {
        return is_string($this->hash) && $this->getAuthorized()
            ? $this->hash
            : false;
    }

    function getFileStorePath()
    {
        return SYS_UPLOAD_DIR . '/' . $this->getFileStoreKeyHash();
    }

    function getFileStoreUrl()
    {
        return SYS_UPLOAD_URL . '/' . $this->getFileStoreKeyHash();
    }

    function setGroups($val)
    {
        if (!is_array($val) || empty($val)) {
            return false;
        }
        foreach ($val as $gid => $gcode) {
            $this->addGroup($gid, $gcode);
        }
    }

    function getGroups()
    {
        if ($this->groups === null) {
            $this->loadUserGroups();
        }
        return $this->groups;
    }

    function addGroup($val, $code)
    {
        $this->groups[(int)$val] = (string)$code;
    }

    function removeGroup($val = null)
    {

        if ($val === null) {
            $this->groups = null;
            return true;
        }

        $this->getGroups();
        if (is_numeric($val) && isset($this->groups[$val])
            || (is_string($val) && false !== ($val = array_search($val, $this->groups)))
        ) {
            unset($this->groups[$val]);
            return true;
        }

        return false;
    }

    function loadUserGroups()
    {
        $this->groups = array();
        if (!is_numeric($this->id)) {
            $this->addGroup(USR_GUEST_GROUP_ID, USR_GUEST_GROUP_CODE);
            $this->removeGroup(USR_AUTH_GROUP_ID);
            return;
        }

        $this->addGroup(USR_AUTH_GROUP_ID, USR_AUTH_GROUP_CODE);
        $this->removeGroup(USR_GUEST_GROUP_ID);

        $u_ot = \Verba\_oh('user')->getId();
        $_group = \Verba\_oh('group');
        $g_ot = $_group->getId();

        $branch = \Verba\Branch::get_branch(array($u_ot => array('iids' => array($this->id), 'aot' => array($g_ot))), 'up', 100);

        if (is_array($branch['handled'][$g_ot])) {
            $data = $_group->getData($branch['handled'][$g_ot], true, array('code'));
            foreach ($data as $c_grp_id => $c_grp) {
                $this->addGroup($c_grp_id, $c_grp['code']);
            }
        }
    }

    /**
     * Выполняет проверку участия пользователя в группах
     * @param string|array $groups id или массив id групп
     * @param int $cmp [0|1] флаг сравнения
     *   0 - необходимо участие хотя бы в одной группе;
     *   1 - необходимо участие во всех входящих группах;
     * @return boolean
     */
    function in_group($groups, $cmp = false)
    {
        $this->getGroups();
        if (array_key_exists(USR_ADMIN_GROUP_ID, $this->groups)) {
            return true;
        }

        if (!\Verba\reductionToArray($groups) || !count($groups)) {
            return false;
        }
        $intersect = 0;
        foreach ($groups as $c_grp) {
            if (is_numeric($c_grp) && isset($this->groups[$c_grp])
                || (array_search($c_grp, $this->groups))) {
                $intersect++;
            }
        }
        $cmp = (bool)$cmp;

        return ($cmp && $intersect === count($groups)) || (!$cmp && $intersect)
            ? true
            : false;
    }

    function isKeyRightsLoaded($key)
    {
        return is_array($this->rights[$key]) && count($this->rights[$key]) > 0 ? true : false;
    }

    function buildKeysRights($keys)
    {
        global $S;

        if (!\Verba\reductionToArray($keys)) {
            $this->log()->warning(__METHOD__ . ' line ' . __LINE__ . ', $keys[' . var_export($keys, true) . ']');
            return false;
        }

        // поиск ключей отсутствующих в КК или у которых есть родитель с незагруженными правами
        array_unshift($keys, 0);
        reset($keys);
        $keys2load = array(); //ключи, данных по которым нет в КК
        for ($i = 0; $i < count($keys); $i++) {
            if (false !== ($c_key = next($keys))) {
                if (!array_key_exists($c_key, $S->KK()->keys)) {
                    $keys2load[] = $c_key;
                } elseif ($S->KK()->keys[$c_key]->inherit_id > 0 && !array_key_exists($S->KK()->keys[$c_key]->inherit_id, $this->rights)) {
                    array_push($keys, $S->KK()->keys[$c_key]->inherit_id);
                }
            }
        }
        array_shift($keys); // удаление нулевого элемента
        if (is_array($keys2load) && count($keys2load) > 0) {
            $needAsParents = $S->KK()->buildKeys($keys2load);
            if (is_array($needAsParents)) {
                $keys = array_merge($keys, $needAsParents);
            }
        }

        $need2load = array_diff($keys, is_array($this->rights) ? array_keys($this->rights) : array());

        if (!is_array($need2load) || !count($need2load)) {
            return null;
        }

        $oRes = $this->loadRights($need2load);
        $formated_rights = array();
        if (is_object($oRes) && $oRes->getNumRows() > 0) {
            // форматирование массива прав
            while ($row = $oRes->fetchRow()) {
                $formated_rights[$row['key_id']][$row['ot_id']][$row['group_id']] = array();
                $c_node = &$formated_rights[$row['key_id']][$row['ot_id']][$row['group_id']];
                foreach ($this->rights_codes as $right_code) {
                    $c_node[$right_code] = array_key_exists($right_code, $row) ? $row[$right_code] : (int)0;
                }
            }
        }

        // заливка нулями неполученных (неизвестных) ключей
        $unhandled_keys = array_diff($need2load, array_keys($formated_rights));
        if (is_array($unhandled_keys)) {
            foreach ($unhandled_keys as $uh_key) {
                $formated_rights[$uh_key] = array(0 => array(0 => $this->all_rights_denied));
            }
        }

        if (!is_array($formated_rights)) {
            $this->log()->warning(__METHOD__ . ' line ' . __LINE__ . '!is_array($formated_rights)');
            return false;
        }

        foreach ($formated_rights as $c_key => $key_rights) {
            // если права для этого ключа уже были сформированы вне очереди
            // как права парента другого ключа
            if (array_key_exists($c_key, $this->rights))
                continue;

            $inherit_id = $S->KK()->keys[$c_key]->inherit_id;
            if ($inherit_id == 0
                || (
                    (
                        $inherit_id > 0 && is_array($this->rights[$inherit_id])
                    )
                    ||
                    (
                        !is_array($this->rights[$inherit_id]) && $this->setKeyRights($inherit_id, $formated_rights[$inherit_id])
                    )
                )
            ) {
                $this->setKeyRights($c_key, $key_rights);
            }
        }

        return true;
    }

    function setKeyRights($key_id, $key_rights)
    {
        global $S;

        $inherit_id = $S->KK()->keys[$key_id]->inherit_id > 0 ? $S->KK()->keys[$key_id]->inherit_id : 0;

        if (!is_numeric($key_id) || !is_array($key_rights)) {
            $this->log()->warning(__METHOD__ . ' line ' . __LINE__ . ': !is_numeric($key_id) || !is_array($key_rights)');
            return false;
        }

        foreach ($key_rights as $c_ot_id => $groups_list) {

            if (!array_key_exists((int)0, $groups_list)) {
                $groups_list[0] = $this->all_rights_denied;
            }

            // добавление нулевых прав по общим группам
            if (count($groups_list) == 1 && array_key_exists((int)0, $groups_list)) {
                $groups_list[-1] = $this->all_rights_denied;
            }

            foreach ($groups_list as $c_group_id => $rights_list) {
                $part = $c_group_id != 0 ? 'common' : 'owner';
                // По группе
                foreach ($rights_list as $right => $value) {
                    if (!isset($this->rights[$key_id][$c_ot_id][$part][$right]) || (isset($this->rights[$key_id][$c_ot_id][$part][$right]) && $value > $this->rights[$key_id][$c_ot_id][$part][$right])) {
                        $this->rights[$key_id][$c_ot_id][$part][$right] = (int)$value;
                    }
                    // проверяем родителя, мож он прав даст поболее.
                    if ($inherit_id > 0 && isset($this->rights[$inherit_id][$c_ot_id][$part][$right]) && $this->rights[$inherit_id][$c_ot_id][$part][$right] > $this->rights[$key_id][$c_ot_id][$part][$right]) {
                        $this->rights[$key_id][$c_ot_id][$part][$right] = $this->rights[$inherit_id][$c_ot_id][$part][$right];
                    }
                }
            }
        }
        return true;
    }

    function loadRights($keys, $ot_ids = false)
    {

        if (!\Verba\reductionToArray($keys)) return false;

        //Генерация условия по ключам
        $keys_where = '(' . $this->DB()->makeWhereStatement($keys, 'key_id') . ')';
        $this->getGroups();
        if ($this->in_group(USR_ADMIN_GROUP_ID)) {
            $ot_id_where = $group_where = '';
            $grpFields = '22 as group_id';
            $rightsFields = '1 as s,1 as c,1 as u,1 as d';
        } else {
            // По группам
            $UserGroups = $this->groups;
            // добавление получения прав для владельца объекта
            $UserGroups[0] = 0;
            $group_where = ' && (' . $this->DB()->makeWhereStatement(array_keys($UserGroups), 'group_id') . ')';
            // По типам объектов
            $ot_id_where = \Verba\reductionToArray($ot_ids) ? ' && (' . $this->DB()->makeWhereStatement($ot_ids, 'ot_id') . ')' : '';
            $grpFields = 'group_id';
            $rightsFields = 's, c, u, d';
        }

        $query =
            "SELECT key_id, " . $grpFields . ", ot_id, " . $rightsFields . "
FROM " . SYS_DATABASE . "._keys_rights as r
WHERE $keys_where $group_where $ot_id_where";

        return $this->DB()->query($query);
    }

    /**
     * Проверяет наличие доступа к ключу $key_id для прав $rights
     *
     * @param int $key_id ключ доступа
     * @param string|array $rights перечень правдоступа: s - выборка; c - создание; u - изменение; d - удаление.
     * @param int $checkIn Битовая маска 11. первая 1 - проверять в общих правах по ключу.  вторая 1 - проверять право для owner. По умолчанию - 3 (11)
     * @param int $ot_id OT для которых переданы данные в $row
     *
     *
     * @return true|false
     */
    function chr($key_id, $rights = 's', $checkIn = 3, $ot_id = null)
    {

        if (!is_numeric($key_id) || !\Verba\reductionToArray($rights)) {
            return false;
        }

        $otCase = is_numeric($ot_id) ? intval($ot_id) : 0;

        if (!is_array($chp = $this->getRightsByKey($key_id, $otCase))) {
            return false;
        }
        $all_rights_status = 0;

        foreach ($rights as $c_right) {
            $status = 0;
            // common
            if ($checkIn & 1 && is_array($chp['common']) && array_key_exists($c_right, $chp['common']) && $chp['common'][$c_right] == 1) {
                $status++;
            }

            // есть запрос на валидацию по праву для владельца и текущий аккаунт залогинен.
            // owner
            if ($checkIn & 2 && $this->authorized) {
                if (
                    is_array($chp['owner']) && array_key_exists($c_right, $chp['owner']) && $chp['owner'][$c_right] == 1
                ) {
                    $status++;
                }
            }

            if ($status) {
                $all_rights_status++;
            }
        }

        return $all_rights_status > 0
            && count($rights) > 0
            && $all_rights_status == count($rights);
    }

    function chrItem($key_id, $rights, $item)
    {

        if ($this->chr($key_id, $rights, 1)) {
            return true;
        }

        if ($this->chr($key_id, $rights, 2)) {
            if ($item === '~') {
                return true;
            }

            if (is_array($item) && array_key_exists('ot_id', $item)) {
                $ot_id = $item['ot_id'];
            }

            if (!isset($ot_id)) {
                return false;
            }

            $_oh = \Verba\_oh($ot_id);
            if (!$_oh) {
                return false;
            }
            $ownerFiedlCode = $_oh->getOwnerAttributeCode();
            $itemOwnerId = array_key_exists($ownerFiedlCode, $item) ? $item[$ownerFiedlCode] : false;

            if ($itemOwnerId && $itemOwnerId == $this->getId()) {
                return true;
            }

        }
        return false;
    }

    function getRightsByKey($key, $ot_id = 0)
    {
        $ot_id = (int)$ot_id;
        if (!$this->isKeyRightsLoaded($key) && !$this->buildKeysRights($key, $ot_id)
            || !isset($this->rights[$key][$ot_id])
        ) {
            return false;
        }
        return $this->rights[$key][$ot_id];
    }

    function resetRights($val = false)
    {
        if ($val == false) {
            $this->rights = array();
            return true;
        } elseif (is_numeric($val) && isset($this->rights[$val])) {
            unset($this->rights[$val]);
            return true;
        }
        return false;
    }

    function setAuthorized()
    {
        $this->authorized = is_numeric($this->getId()) && $this->getId() > 0;
    }

    function getAuthorized()
    {
        return $this->authorized;
    }

    function getUserpic()
    {
        if ($this->data['userpic'] !== null) {
            return $this->data['userpic'];
        }
        $this->data['userpic'] = $this->makeUserpic();

        return $this->data['userpic'];
    }

    function makeUserpic($size = 32)
    {

        if (empty($this->data['picture'])) {
            return '';
        }
        $mImage = \Verba\_mod('image');
        $_user = \Verba\_oh('user');
        $iCfg = $mImage->getImageConfig($_user->p('picture_config'));
        if (!$iCfg) {
            return '';
        }
        return $iCfg->getFullUrl(basename($this->data['picture']), 'ico' . $size);
    }

    function addController($C, $ctrlName = false)
    {
        if (!is_object($C)) {
            return false;
        }

        $ctrlName = !is_string($ctrlName) ? get_class($C) : $ctrlName;
        $this->_controllers[$ctrlName] = $C;
        return true;
    }

    function removeController($ctrlName)
    {
        if (!$ctrlName || !is_string($ctrlName)) {
            return false;
        }
        if (!array_key_exists($ctrlName, $this->_controllers)) {
            return true;
        }

        unset($this->_controllers[$ctrlName]);
    }

    function requireRefresh()
    {
        return !$this->refreshedAt || time() - $this->refreshedAt > 84600;
    }

    function getRefreshedAt()
    {
        return $this->refreshedAt;
    }

    function planeToReload()
    {
        $this->refreshedAt = 0;
    }

    function haveStore()
    {
        return (bool)$this->data['storeId'];
    }

    function getStoreId()
    {
        return (int)$this->data['storeId'];
    }

    function updateLastLoginAt()
    {
        $this->DB()->query('UPDATE ' . $this->oh->vltURI() . " SET last_login = '" . date('Y-m-d H:i:s')
            . "' WHERE " . $this->oh->getPAC() . "='" . $this->getId() . "' LIMIT 1");
    }
}
