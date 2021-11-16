<?php
 namespace Verba\Response;
/**
 * Create a wrapper for response data with next fields:
 *  result: boolean, requested operation status
 *  data: mixed response data. Html, json or what you want to return to client
 */
class Json extends \Verba\Response {

    public $contentType = 'json';

    protected $operationStatus; // true | false (result field into xhr response wrap )


    function run(){
        try{

            parent::run();

        }catch(\Exception $e) {

            $this->handleException($e);
            $this->setOperationStatus(false);

        }

        $result = $this->getOperationStatus();
        if(!is_bool($result)){
            $result = true;
        }
        $this->content = $this->wrap($result, $this->content, JSON_FORCE_OBJECT);
    }

    function build(){
        if(count($this->items) > 0 ){
            $r = [];
            foreach($this->items as $i){
                if($i instanceof \Block\Json){
                    $opstatus = $i->getOperationStatus();
                    if($opstatus !== null){
                        $this->setOperationStatus($opstatus);
                    }
                }
                if($i->content === null
                    && !($i instanceof \Block\Json)){
                    continue;
                }
                $r[] = $i->content;
            }
            if(count($r) == 1){
                $r = $r[0];
            }
        }else{
            $r = null;
        }
        $this->content = $r;
    }

    function setOperationStatus($val){
        $this->operationStatus = (bool)$val;
    }

    function getOperationStatus(){
        return $this->operationStatus;
    }

    function output(){
        if($this->contentType == 'json'){
            $this->addHeader('Content-Type','application/json');
        }
        parent::output();
    }

    static function wrap($result, $data = null, $opts = null){
//        $ar = array('result' => (int)((bool)$result));
//        if($data !== null){
//            $ar['data'] = $data;
//        }

        return \json_encode($data, $opts);
    }

    static function addWhenDocumentReady($code){
        if(is_array($code)){
            $code = implode("\n//--\n", $code);
        }
        if(!is_string($code) || empty($code)){
            return '';
        }
        return "<script type=\"text/javascript\">\n$(document).ready(function(){".$code."\n});</script>";
    }
}
