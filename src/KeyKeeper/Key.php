<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 21.08.19
 * Time: 18:21
 */

namespace KeyKeeper;

class Key {

    public $key_id;
    public $key_id_code;
    public $inherit_id   = 0;
    public $description = '';
    public $display = '';

    function __construct(&$key_data){

        foreach($key_data as $prop_name => $prop_value){
            $prop_name = 'set_'.$prop_name;
            if(method_exists($this,$prop_name)){
                $this->$prop_name($prop_value);
            }
        }
    }

    function set_key_id($id = false){
        $this->key_id = is_numeric($id) && $id > 0 ? $id : false;
    }

    function set_key_id_code($var = false){
        $this->key_id_code = is_string($var) && !empty($var) ? $var : '';
    }

    function set_inherit_id($inherit_id = false){
        $this->inherit_id =  is_numeric($inherit_id) && $inherit_id > 0 ? $inherit_id : 0;
    }

    function set_description($var = false){
        $this->description = is_string($var) && !empty($var) ? $var : '';
    }

    function set_display($var = false){
        $this->display = is_string($var) && !empty($var) ? $var : '';
    }
}
