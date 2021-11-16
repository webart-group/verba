<?php

namespace Verba\DBDriver\mysql;

class Result implements \Verba\DBDriver\Result {
    /**
     * @var \mysqli_result
     */
    public $miResult;
    public $miInsertId;
    public $miAffectedRows;
    private $currPosition = 0;

    public $SQL_CALC_FOUND_ROWS;

    function __construct($miResult){
        $this->miResult=$miResult;
    }

    public function getResult(){
        return $this->miResult;
    }

    public function getNumRows(){
        if($this->miResult instanceof \mysqli_result)
            return $this->miResult->num_rows;
        else
            return 0;
    }

    public function fetchRow($arrayType='ASSOC_ARRAY'){
        if ($arrayType=='ASSOC_ARRAY'){
            $result = $this->miResult->fetch_assoc();
        }elseif ($arrayType=='NUM_ARRAY'){
            $result = $this->miResult->fetch_row();
        }else {
            $result = false;
        }
        if ($result){
            $this->currPosition++;
            return $result;
        }else {
            return false;
        }
    }

    public function getValue($numRow,$numField){
        $cur_p=$this->getCursor();
        if ($this->setCursor($numRow)){
            if($row=$this->miResult->fetch_row()){
                $value = $row[$numField];
            }else {
                $value = false;
            }
        }else {
            $value = false;
        }
        $this->setCursor($cur_p);
        return $value;
    }

    public function getFirstValue(){
        if($row=$this->getFirstRow('NUM_ARRAY')){
            return $row[0];
        }else {
            return false;
        }
    }

    public function getFirstRow($arrayType='ASSOC_ARRAY'){
        $cur_p=$this->getCursor();
        if ($this->miResult->data_seek(0)){
            $row=$this->fetchRow($arrayType);
            $this->setCursor($cur_p);
            return $row;
        }else {
            $this->setCursor($cur_p);
            return false;
        }
    }

    public function getRow($number=0,$arrayType='ASSOC_ARRAY'){
        $cur_p=$this->getCursor();
        $row=false;
        $this->setCursor(0);
        $i=0;
        while ($row=$this->fetchRow($arrayType)){
            if ($number==$i++) return $row;
        }
        $this->setCursor($cur_p);
        return $row;
    }

    public function getMultiArray($arrayType='ASSOC_ARRAY'){
        $cur_p=$this->getCursor();
        $r_arr=array();
        $this->setCursor(0);
        while ($row=$this->fetchRow($arrayType)){
            $r_arr[]=$row;
        }
        $this->setCursor($cur_p);
        return $r_arr;
    }

    public function setCursor($position=0){
        if(method_exists($this->miResult,'data_seek') && $this->miResult->data_seek($position)){
            $this->currPosition=$position;
            return true;
        }else {
            $this->currPosition=0;
            return false;
        }

    }

    public function getCursor(){
        return $this->currPosition;
    }

    public function getAffectedRows(){
        return $this->miAffectedRows;
    }

    public function getInsertId(){
        return $this->miInsertId;
    }

    public function free(){
        if(is_object($this->miResult)){
            $this->miResult->free_result();
        }
    }
}
