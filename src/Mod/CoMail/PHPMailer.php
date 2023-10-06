<?php
/**
 * @author webart.group
 * Date: 26.08.19
 * Time: 12:53
 */

namespace Verba\Mod\CoMail;

use PHPMailer\PHPMailer\PHPMailer as OriginPHPMailer;


class PHPMailer extends OriginPHPMailer{

    public $subjectPrefix;

    function __construct(){
        parent::__construct();
        $this->getSubjectPrefix();
    }

    function setSubject($val){
        if(is_string($this->subjectPrefix)){
            $val = $this->subjectPrefix.$val;
        }
        $this->Subject = $val;
    }

    function getSubjectPrefix(){
        if($this->subjectPrefix === null){
            global $S;

            $mp = $S->gC('debug mailSubjectPrefix');

            if(isset($mp) && is_string($mp) && !empty($mp)){
                $this->subjectPrefix = $mp;
            }else{
                $this->subjectPrefix = false;
            }
        }
        return $this->subjectPrefix;
    }
}
