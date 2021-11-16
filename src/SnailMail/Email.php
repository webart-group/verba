<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 14:04
 */

namespace Mod\SnailMail;


/**
 * Класс письма. Содержит свойства письма. Предназначен для формирования объекта письма, который будет обрабатываться
 * классом отсылки письма SnailMail
 * @see class SnailMail
 * @see SnailMail::send()
 */
class Email{

    /**
     * @var array массив, ключ которого представлен в виде email-отправителя, а значение в виде Имя-отправителя
     * @see Email::setReplyTo()
     */
    protected $replyTo = array();

    /**
     * @var array массив, ключ которого представлен в виде email-отправителя, а значение в виде Имя-отправителя
     * @see Email::addBCopyTo()
     */
    protected $bCopy = array();
    /**
     * @var array массив, ключ которого представлен в виде email-отправителя, а значение в виде Имя-отправителя
     * @see Email::setFrom()
     */
    protected $from = array();

    /**
     * @var array массив, ключ которого представлен в виде email-получателя, а значение в виде Имя-получателя
     * @see Email::addTo()
     */
    protected $to = array();

    /**
     * @var string $subject строка - тема сообщения письма (если есть)
     * @see Email::setSubject()
     */
    protected $subject = false;

    /**
     * @var string $text текстовая часть сообщения письма (если есть)
     * @see Email::setText()
     */
    protected $text = false;

    /**
     * @var string $textHTML HTML-часть сообщения письма (если есть)
     * @see Email::setHTML()
     */
    protected $textHTML = false;

    /**
     * @var array $attachment Свойства attachment (если есть), представленные в виде массива
     * @see Email::addAtachment()
     */
    protected $attachment = array();

    function __construct(){

    }

    /**
     * Проверяет наличие мультипартной части письма.
     * Если письмо - мультипарт, то для него формируются заголовки(headers) для соответствующих частей письма, согласно стандартам RFC 822
     * Этот тип используется, если один или более различных наборов данных заключены в одном письме.
     * Каждая часть тела должна иметь правильный синтакс (то есть, иметь заголовок, пустую строку и тело),
     * должна иметь открывающую и закрывающую границы.
     * @return true если письмо мультипартное (письмо содержит либо HTML часть, либо attachment часть, либо обе части одновременно)
     * @return false - письмо не содержит указанных выше частей
     * @see SnailMail::getBlockHeaders()
     * @see SnailMail::send()
     */
    function isMultipart(){
        if((is_string($this->textHTML) && !empty($this->textHTML))
            || (is_array($this->attachment) && !empty($this->attachment))){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Устанавливает отправителz письма (from) и пишет данные в массив $this->from,
     * паралельно проверяя корректность email-адреса
     * @param string $email - Адрес отправителя
     * @param string #name - Имя отправителя
     * return boolean
     */
    function setFrom($email, $name = false){
        $this->from = array();
        if(!is_array($email) && is_string($email) && $this->validateEmail($email) == true){
            $name = is_string($name) ? $name : false;
            $this->from[$email] = $name;
            return true;
        }else{
            return false;
        }
    }

    /**
     * Возвращает массив $this->from - массив, ключ которого представлен в виде email-получателя,
     * а значение в виде Имя-получателя
     * @return array
     * @see SnailMail::send()
     * @see SnailMail::buildFirstPartHeaders()
     */
    function getFrom(){
        return $this->from;
    }

    /**
     * Устанавливает отправителz письма для заголовка Reply-to и пишет данные в массив $this->replyTo,
     * паралельно проверяя корректность email-адреса
     * @param string $email - Адрес отправителя
     * @param string #name - Имя отправителя
     * return boolean
     */
    function setReplyTo($email, $name = false){
        if(is_string($email) && $this->validateEmail($email) == true){
            $name = is_string($name) ? $name : false;
            $this->replyTo[$email] = $name;
            return true;
        }
        if(!is_array($email)){
            return false;
        }
        foreach($email as $emailAdress => $name){
            if(!is_string($emailAdress) || !$this->validateEmail($emailAdress)){
                continue;
            }
            $this->replyTo[$emailAdress] = $name;
        }
        return true;
    }

    /**
     * Возвращает массив $this->replyTo - массив, ключ которого представлен в виде email-получателя,
     * а значение в виде Имя-получателя
     * @return array
     * @see SnailMail::send()
     * @see SnailMail::buildFirstPartHeaders()
     */
    function getReplyTo(){
        return $this->replyTo;
    }

    /**
     * Устанавливает отправителz письма для заголовка BCC (скрытая копия) и пишет данные в массив $this->bCopy,
     * паралельно проверяя корректность email-адреса
     * @param string $email - Адрес отправителя
     * @param string #name - Имя отправителя
     * return boolean
     */
    function addBCopyTo($email, $name=false){
        if(!is_array($email) && is_string($email) && !empty($email)){
            $name = is_string($name) ? $name : false;
            $email = array($email=>$name);
        }
        foreach($email as $mailbox => $rcpntName){
            if(is_string($mailbox) && $this->validateEmail($mailbox) == true){
                $this->bCopy[$mailbox] = is_string($rcpntName) ?  $rcpntName : false;
            }
        }
        return true;
    }

    /**
     * Возвращает массив $this->bCopy - массив, ключ которого представлен в виде email-получателя,
     * а значение в виде Имя-получателя
     * @return array
     * @see SnailMail::send()
     * @see SnailMail::buildFirstPartHeaders()
     */
    function getBCopyTo(){
        return $this->bCopy;
    }

    /**
     * Добавляет данные получателя письма (to) в массив $this->to,
     * паралельно проверяя корректность email-адреса
     * @param string $email - Адрес отправителя
     * @param string $name - Имя отправителя
     * @return boolean
     */
    function addTo($email, $name=false){
        if(!is_array($email) && is_string($email) && !empty($email)){
            $name = is_string($name) ? $name : false;
            $email = array($email=>$name);
        }
        foreach($email as $mailbox => $rcpntName){
            if(is_string($mailbox) && $this->validateEmail($mailbox) == true){
                $this->to[$mailbox] = is_string($rcpntName) ?  $rcpntName : false;
            }
        }
        return true;
    }

    /**
     * Возвращает массив $this->to - массив, ключ которого представлен в виде email-отправителя,
     * а значение в виде Имя-отправителя
     * @return array
     * @see SnailMail::send()
     * @see SnailMail::buildFirstPartHeaders()
     */
    function getTo(){
        return $this->to;
    }

    /**
     * Проверяет корректность email адреса
     * @param string $email
     * @see Email::addTo()
     * @see Email setFrom()
     * @return boolean. Возвращает true если адрес корректный
     */
    static function validateEmail($email){
        return  is_string($email) && preg_match("/(\s{1}(.|\n)[^@]+?\s{1})*<?([A-Z0-9\._%-]+@[A-Z0-9\.-]+\.[A-Z]{2,4})>?/im", $email, $buff) && isset($buff[3]) && is_string($buff[3])
            ? true
            : false;
    }

    /**
     * Устанавливает значение темы письма
     * @param string $subject - строка темы сообщения (если есть)
     * @return boolean
     * @see Email::getSubject()
     */
    function setSubject($subject){
        if(!(is_string($subject) || is_numeric($subject)) || empty($subject)){
            return false;
        }
        $this->subject = (string) $subject;
        return true;
    }

    /**
     * Возвращает строку - тему сообщения письма
     * @return string
     * @see SnailMail::buildFirstPartHeaders()
     * @see SnailMail::send()
     */
    function getSubject(){
        return $this->subject;
    }

    /**
     * Устанавливает значение текстовой части письма
     * @param string $text строка текстовой части письма
     * @param $overwrite = false данные добавляються к уже существующим
     * @param $overwrite = false данные перезаписываются
     * @return boolean
     * @see Email::getText()
     */
    function setText($text = '', $overwrite = false){

        $overwrite = settype($overwrite, 'boolean');
        if(is_string($text) || is_numeric($text) && !empty($text)){
            $this->text = !$overwrite
                ? $this->text.$text
                :  $text;
            return true;
        }else{
            return false;
        }
    }

    /**
     * Возвращает строку - текстовую часть письма
     * @return string
     * @see SnailMail::send()
     */
    function getText(){
        return $this->text;
    }

    /**
     * Устанавливает значение HTML части письма
     * @param string $textHTML строка HTML части письма
     * @param $overwrite = false данные добавляються к уже существующим
     * @param $overwrite = false данные перезаписываются
     * @return boolean
     * @see Email::getHTML()
     */
    function setHTML($textHTML, $overwrite = false){

        $overwrite = (bool) $overwrite;
        if(!settype($textHTML, 'string')){
            return false;
        }else{
            $this->textHTML = !$overwrite
                ? $this->textHTML.$textHTML
                :  $textHTML;
            return true;
        }
    }

    /**
     * Возвращает строку - HTML часть письма
     * @return string
     * @see SnailMail::send()
     */
    function getHTML(){
        return $this->textHTML;
    }

    /**
     * Определяет MIME type файла
     * Работает через файл /userfiles/mime.types
     * @param string $file путь к файлу, у которого нужно определить MIME type
     * @return string MIME type
     * @see Email::addAttachment()
     */
    function getMimeType($file){
        $info = pathinfo($file);
        foreach (file("/userfiles/mime.types") as $line){
            if (preg_match('/^([^#]\S+)\s+.*'.$info['extension'].'.*$/',$line,$m)){
                return $m[1];
            }
        }
        return 'application/octet-stream';
    }

    /**
     * Устанавливает свойства Attachment и добавляет данные в массив
     * @param string $file путь к файлу, если файл находиться на диске, либо файл находящийся в памяти
     * @param string $filename - название файла
     * @param $isFile = true - $file в файле указан path к файлу на сервере. При пересылке файла используется метод SnailMail::readAttachmentFile()
     * @param $isFile = true - $file это файл, который находится в памяти. При пересылке непосредственно считывается в сокет SnailMail::send()
     * @param string $fileType MIME type файла $isFile = true определяется через метод Email::getMimeType() или указывается вручную
     *                                         $isFile = false указывается вручную обязательно, иначе будет ошибка
     * @return boolean
     * @see SnailMail::send()
     * @see SnailMail::readAttachmentFile()
     * @see SnailMail::getAttachmentPartHeaders()
     */
    function addAttachment($file, $filename = '', $isFile = true, $fileType = false){
        $isFile = (bool)$isFile;
        if($isFile && !file_exists($file) || (!$isFile && (!is_string($file) || empty($file)) )){
            return false;
        }

        if(is_string($fileType) && !empty($fileType)){
            $fileType = $fileType;
        }elseif(!$fileType){
            $fileType = $this->getMimeType($file);
        }else{
            $fileType = false;
        }

        if(is_string($filename) && !empty($filename)){
            $filename = $filename;
        }elseif($isFile && is_string($file) && !empty($file)){
            $filename = basename($file);
        }else{
            $filename = false;
        }

        if(!$file ||  !$fileType){
            throw new Exception('bad_attachment_param');
        }

        $this->attachment[] = array('fileName' => $filename,
            'fileType'  => $fileType,
            'isFile'  => $isFile,
            'file'  => $file);
        return true;

    }

    /**
     * Возвращает массив, содержащий свойства attachment
     * @return array
     */
    function getAttachment(){
        return $this->attachment;
    }

    function removeHTMLtags($doc){
        if(is_string($doc) && !empty($doc)){
            $replaceBR = preg_replace('/\<br(\s*)?\/?\>/i', "\r\n", $doc);
            $search = array ("'<script[^>]*?>.*?</script>'si",  // Remove javaScript
                "'<[\/\!]*?[^<>]*?>'si",           // Remove HTML
                "'&(quot|#34);'i",                 // Replace
                "'&(amp|#38);'i",
                "'&(lt|#60);'i",
                "'&(gt|#62);'i",
                "'&(nbsp|#160);'i",
                "'&(iexcl|#161);'i",
                "'&(cent|#162);'i",
                "'&(pound|#163);'i",
                "'&(copy|#169);'i",
                "'&#(\d+);'e");

            $replace = array ("",
                "",
                "\"",
                "&",
                "<",
                ">",
                " ",
                chr(161),
                chr(162),
                chr(163),
                chr(169),
                "chr(\\1)");
            return preg_replace($search, $replace, $replaceBR);
        }else{
            return false;
        }

    }
}