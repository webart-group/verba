<?php
namespace Verba\ObjectType;


class Attribute  extends \Verba\Base
{

    public $attr_code = false;
    public $attr_id = false;
    public $data_type;
    public $form_element;
    public $handlers = array();
    public $predefined = false;

    public $protected_predefined = false;
    public $predefined_default_value = false;
    public $predefined_root = false;
    public $predefined_object = false;
    public $default_value;
    public $pd_set = false;

    public $isForeignId = false;
    public $lcd;
    protected $annotation;
    protected $roles = array();

    private $foreignOtId;
    private $foreignAttrId;
    /**
     * @var \Verba\Model
     */
    protected $oh;
    /**
     * @var \ObjectType
     */
    protected $OT;
    /**
     * @var \ObjectType\Attribute\Predefined\Collection
     */
    protected $PdCollection = null;
    /**
     * @var \Verba\Model
     */
    protected $call_context;

    function __construct($OT, &$data)
    {
        if (!is_array($data)) return false;

        $this->OT = $OT;
        $this->oh = $OT->getOh();

        $this->set_attr_id($data);
        $this->set_parent_attr_id($data);
        $this->set_attr_code($data);
        $this->set_data_type($data);
        $this->set_form_element($data);
        $this->set_min_val($data);
        $this->set_max_val($data);
        $this->set_default_value($data);

        $this->set_predefined($data);

        $this->set_priority($data);
        $this->set_restrict_key($data);
        $this->set_display($data);
        $this->setIsForeignId($data);
        $this->set_lcd($data);
        $this->setRoles($data['roles']);
        \Verba\Lang::substPlaneLcdAttrByLcArray($data, 'annotation');
        $this->setAnnotation($data['annotation']);

        return true;
    }

    /**
     * @param $OT
     * @param $data
     * @return \ObjectType\Attribute
     */
    static function create($OT, $data){

        $className = null;

        if ($data['predefined']) {
            $className = \Verba\ObjectType\Attribute\Predefined::class;
        } else {
            $type = ucfirst($data['data_type']);
            $className = '\Verba\ObjectType\Attribute\\'
                .($type == 'String' || $type == 'Float' ? '_' : '')
                .$type;
        }

        if(!class_exists($className)){
            $className = '\Verba\ObjectType\Attribute';
        }

        return new $className($OT, $data);
    }

    function setCallContext($oh)
    {
        if (!is_object($oh)) {
            return false;
        }
        $this->call_context = $oh;
        return $this->call_context;
    }

    function oh()
    {
        return $this->oh;
    }

    function getID()
    {
        return $this->attr_id;
    }

    function getCode()
    {
        return $this->attr_code;
    }

    function getFieldCode($val = false)
    {
        if ($this->isLcd()) {
            $suffix = is_string($val) && !empty($val)
                ? $val
                : SYS_LOCALE;
            return $this->attr_code . '_' . $suffix;
        } else {
            return $this->attr_code;
        }
    }

    function set_attr_id($data)
    {
        if (isset($data["attr_id"])) {
            $this->attr_id = $data["attr_id"];
        }
    }

    function set_parent_attr_id($data)
    {
        if (isset($data["parent_attr_id"])) {
            $this->parent_attr_id = $data["parent_attr_id"];
        }
    }

    function set_attr_code($data)
    {
        if (isset($data["attr_code"])) {
            $this->attr_code = $data["attr_code"];
        }
    }

    function set_data_type($data)
    {
        if (isset($data["data_type"])) {
            $this->data_type = $data["data_type"];
        }
    }

    function getType()
    {
        return $this->getDataType();
    }

    function getDataType()
    {
        return $this->data_type;
    }

    function set_form_element($data)
    {
        if (isset($data['form_element'])) {
            $this->form_element = $data['form_element'];
        }
    }

    function set_min_val($data)
    {
        if (isset($data["min_val"])) {
            $this->min_val = $data["min_val"];
        }
    }

    function getHandlerByType()
    {
        return in_array(strtolower($this->data_type), \Verba\Hive::$reservedNames)
            ? ucfirst($this->data_type).'Data'
            : ucfirst($this->data_type);
    }

    function set_max_val($data)
    {
        if (isset($data["max_val"])) {
            $this->max_val = $data["max_val"];
        }
    }

    function set_default_value($data)
    {
        $this->default_value = isset($data['default_value'])
            ? $data['default_value']
            : null;
    }

    function set_predefined($data)
    {
        $this->predefined = (int)(!!$data['predefined']);
    }

    function PdCollection()
    {
        if (!is_object($this->PdCollection)) {
            $this->PdCollection = new Attribute\Predefined\Collection($this);
        }
        return $this->PdCollection;
    }

    /**
     * @return \ObjectType\Attribute\Predefined\Set|false
     */
    function PdSet($ot_ctx = false)
    {
        if (!$ot_ctx) {
            if (is_object($this->call_context)) {
                $ot_id = $this->call_context->getID();
            } else {
                $ot_id = $this->oh->getID();
            }
        } else {
            $ot_id = \Verba\_oh($ot_ctx)->getID();
        }
        return $this->PdCollection()->get($ot_id);
    }

    function isPredefined()
    {
        return !!$this->predefined;
    }

    function getDefaultValue()
    {
        if ($this->isPredefined()) {
            return $this->PdSet()->getDefaultValue();
        }
        return $this->default_value;
    }

    function set_restrict_key($data)
    {
        if (array_key_exists('restrict_key', $data))
            $this->restrict_key = (int)$data['restrict_key'];
    }

    function set_priority($data)
    {
        $this->priority = isset($data["priority"]) ? $data["priority"] : 0;
    }

    function set_display($data)
    {
        $this->display = isset($data['display']) ? $data['display'] : '';
    }

    function set_lcd($data)
    {
        $this->lcd = isset($data['lcd']) && $data['lcd'] == 1 ? (bool)$data['lcd'] : false;
    }

    function isLcd()
    {
        return $this->lcd;
    }

    function get_lcd()
    {
        return $this->isLcd();
    }

    function display()
    {
        return $this->display;
    }

    function getTitle()
    {
        return $this->display();
    }

    function getHandlers($action = null)
    {

        if ($action === null) {
            return $this->handlers;
        }

        return isset($this->handlers[$action]) && is_array($this->handlers[$action])
            ? $this->handlers[$action]
            : array();
    }

    function getOnPageValue(&$row)
    {

        $aths = $this->getHandlers('present');

        if (!is_array($aths) || empty($aths)) {
            return $row[$this->attr_code];
        }
        $result = '';
        foreach ($aths as $set_id => $set_data) {
            $result = $this->{"ph_{$set_data['ah_name']}_handler"}($this->attr_code, $row, $set_id, $set_data, $result);
        }

        return $result;
    }

    function setIsForeignId($data)
    {
        $this->isForeignId = (bool)$data['foreign_id'];
    }

    function isForeignId()
    {
        return $this->isForeignId;
    }

    function extractForeignDataFromHandlers()
    {
        $this->foreignOtId = false;
        $this->foreignAttrId = false;
        if (!isset($this->handlers['present']) || !count($this->handlers['present'])) {
            return;
        }
        foreach ($this->handlers['present'] as $h) {
            if ($h['ah_name'] == 'ForeignId'
                && isset($h['params']['ot_id'])
            ) {
                $this->foreignOtId = $h['params']['ot_id'];
                $this->foreignAttrId = $h['params']['field2display'];
                return;
            }
        }
    }

    function getForeignOtId()
    {
        if ($this->foreignOtId === null) {
            $this->extractForeignDataFromHandlers();
        }
        return $this->foreignOtId;
    }

    function getForeignAttrId()
    {
        if ($this->foreignAttrId === null) {
            $this->extractForeignDataFromHandlers();
        }
        return $this->foreignAttrId;
    }

    function setRoles($val)
    {
        $this->roles = array();
        if (!is_string($val) || !strlen($val)) {
            return $this->roles;
        }
        $values = explode(';', $val);
        foreach ($values as $cv) {
            $this->roles[$cv] = '';
        }
        return $this->roles;
    }

    function inRole($role)
    {
        return array_key_exists($role, $this->roles);
    }

    function setAnnotation($val)
    {
        $this->annotation = array();
        if (!is_array($val) || !count($val)) {
            return $this->annotation;
        }
        $this->annotation = $val;
        return $this->annotation;
    }

    function getAnnotation()
    {
        return $this->getAnnotationForLang(SYS_LOCALE);
    }

    function getAnnotationForLang($lc)
    {
        if (!is_string($lc) || !array_key_exists($lc, $this->annotation)) {
            return null;
        }
        return $this->annotation[$lc];
    }
}
