<?php

namespace Verba\Mod;

/**
 * Класс отправки письма
 * protocol - SMTP
 * connection - socket
 * extends class \Mod
 */
class SnailMail extends \Verba\Mod
{
    use \Verba\ModInstance;
    /**
     * Текущая кодировка при отправке письма
     * @var string
     * @see SnailMail::setCharset()
     */
    protected $charset;

    /**
     * Параметр для метода set_time_limit()
     * @var integer
     * @see SnailMail::setTimeLimit()
     */
    protected $timelimit;

    /**
     * Cимвол переноса строки (в зависимости от платформы win/unix)
     * @var string
     * @see SnailMail::setCRLF()
     */
    protected $crlf;

    /**
     * Строка, используемая для основы разделителя частей письма
     * @var string
     * @see SnailMail::setBoundary()
     */
    protected $boundary;

    /**
     * Логин для SMTP аутентификации почтового ящика
     * @var string
     * @see SnailMail::setLogin()
     */
    protected $login;

    /**
     * Пароль для SMTP аутентификации почтового ящика
     * @see SnailMail::setLogin()
     */
    protected $password;

    /**
     * SMTP сервер (hostname)
     * @var string
     * @see SnailMail::setSMTPServer()
     */
    protected $host;

    /**
     * SMTP порт
     * @var integer
     * @see SnailMail::setSMTPPort()
     */
    protected $port;

    /**
     * Команда-запрос приветствия почтового сервера (HELO или EHLO)
     * @var string
     * @see SnailMail::setHelloString()
     */
    protected $hellostring;

    /**
     * Текущий домен (hostname) при приветствии почтового сервера
     * @var string
     * @see SnailMail::setHelloHost()
     */
    protected $hellohost;

    /**
     * Ссылка на сокетное соединение с smtp сервером
     * @var resource
     * @see SnailMail::socketCreate()
     */
    protected $socket;

    /**
     * Определяет наличие мультипартной части письма
     * @var boolean.
     * @see SnailMail::send()
     */
    protected $isMultipart;

    protected $smtprequest = array();

    protected $smtpAnswer = array();

    protected $sendID = 0;

    protected $error = false;

    protected $currentAnswer = '';

    /**
     * Конструктор класса. Устанавливает дефолтные параметры класса из конфига
     */
    function __construct($cfg)
    {
        parent::__construct($cfg);
        //Установка CRLF
        $this->setCRLF();
        //Установка границы
        $this->setBoundary();
        //Установка кодировки
        $this->setCharset($this->gC('default', 'charset'));
        //Получение Логина
        $this->setLogin($this->gC('default', 'login'));
        //Получение Пароля
        $this->setPassword($this->gC('default', 'password'));
        //Установка SMTP сервера
        $this->setSMTPServer($this->gC('default', 'host'));
        //Установка SMTP порта
        $this->setSMTPport($this->gC('default', 'port'));
        //установка TimeLimit
        $this->setTimeLimit($this->gC('default', 'time_limit'));
        //Установка строки приветствия
        $this->setHelloHost($this->gC('default', 'hellohost'));
        //Установка домена для строки приветствия
        $this->setHelloString($this->gC('default', 'hellostring'));
    }

    /**
     * Устанавливает значение переноса строки(CRLF) в зависимости от платформы win|unix.
     * @see \Verba\Hive::getCRLF()
     * @see SnailMail::getCRLF()
     * @return null
     */
    function setCRLF()
    {
        $this->crlf = \Verba\Hive::getCRLF();
        return $this->crlf;
    }

    /**
     * Возвращает значение переноса строки(CRLF)
     * @param integer $number Количество последовательных CRLF в строке
     * @see SnailMail::buildFirstPartHeaders()
     * @see SnailMail::getBlockHeaders()
     * @see SnailMail::socketPutsAndLog()
     * @return string - символ переноса строки
     */
    function getCRLF($number = false)
    {
        return is_int($number)
            ? str_repeat($this->crlf, $number)
            : $this->crlf;
    }

    /**
     * Устанавливает границу для мультипартной части письма
     * @param string $boundary - cтрока, используемая для основы разделителя частей письма
     * @see SnailMail::closeBoundary()
     * @see SnailMail::getBoundary()
     * @return true;
     */
    function setBoundary($boundary = false)
    {
        $this->boundary = is_string($boundary) && !empty($boundary)
            ? $boundary
            : "--" . session_id() . '-' . time();
        return true;
    }

    /**
     * Возвращает значение строки, используемой для основы разделителя частей письма
     * @see SnailMail::buildFirstPartHeaders()
     * @see SnailMail::getBlockHeaders()
     * @return string
     */
    function getBoundary()
    {
        return $this->boundary;
    }

    /**
     * Возвращает значение строки, используемой для завершения мультипартной части письма
     * @see SnailMail::send()
     * @return string
     */
    function closeBoundary()
    {
        return is_string($this->getBoundary()) ? $this->getBoundary() . '--' : '';
    }

    /**
     * Устанавливает значение SMTP сервера
     * @param string $smtp значение SMTP сервера (hostname)
     * @return true
     */
    function setSMTPServer($smtp)
    {
        if (is_string($smtp) && !empty($smtp)) {
            $this->host = $smtp;
            return true;
        }
    }

    /**
     * Устанавливает значение SMTP порта
     * @param integer $smtpport значение SMTP порт
     * @return true
     */
    function setSMTPport($smtpport)
    {
        if (is_numeric($smtpport) && settype($timelimit, 'integer') && $smtpport > 0) {
            $this->port = $smtpport;
            return true;
        } else return false;
    }

    /**
     * Устанавливает значение логина при SMTP аутентификации почтового ящика
     * @param string $login
     * @see SnailMail::getLogin()
     * @return boolean
     */
    function setLogin($login)
    {
        if (is_string($login) && !empty($login)) {
            $this->login = $login;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Возвращает значение логина при SMTP аутентификации почтового ящика
     * @see SnailMail::send()
     * @return string
     */
    function getLogin()
    {
        return $this->login;
    }

    /**
     * Устанавливает значение пароля при SMTP аутентификации почтового ящика
     * @see SnailMail::getPassword()
     * @return boolean
     */
    function setPassword($password)
    {
        if ((is_string($password) || is_numeric($password)) && !empty($password)) {
            $this->password = $password;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Возвращает значение пароля при SMTP аутентификации почтового ящика
     * @see SnailMail::send()
     * @return string
     */
    function getPassword()
    {
        return $this->password;
    }

    /**
     * Устанавливает команду-запрос приветствие для SMTP сервера (EHLO или HELO)
     * @param staring $hello значение строки приветствия для SMTP сервера
     * @see SnailMail::getHelloString()
     * @return true;
     */
    function setHelloString($hello)
    {
        if (is_string($hello) && !empty($hello)) {
            $this->hellostring = trim($hello);
        } else {
            $this->hellostring = 'EHLO';
        }
        return true;
    }

    /**
     * Возвращает значение команды-приветствия (EHLO или HELO) при подключении к SMTP-серверу
     * @see SnailMail::send()
     * @return string
     */
    function getHelloString()
    {
        return $this->hellostring;
    }

    /**
     * Устанавливает домен (hostname) для строки приветствия при обращении к SMTP серверу в команде выхода на конкретный сервер
     * @param string $host значение домена (hostname)
     * @see SnailMail::getHelloHost()
     * @return boolean
     */
    function setHelloHost($host)
    {
        if (is_string($host) && !empty($host)) {
            $this->hellohost = $host;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Возвращает значение домена (hostname) в команде выхода на конкретный сервер
     * @see SnailMail::send()
     * @return string (hostname)
     */
    function getHelloHost()
    {
        return $this->hellohost;
    }

    /**
     * Устанавливает текущую кодировку при отправке письма
     * @param string $charset - значение кодировки
     * default = UTF-8
     * @see getCharset()
     * @return boolean
     */
    function setCharset($charset)
    {
        if (is_string($charset) && !empty($charset)) {
            $this->charset = trim($charset);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Возвращает значение текущей кодировки
     * @see SnailMail::combineBase64()
     * @see SnailMail::getBlockHeaders()
     * @return string
     */
    function getCharset()
    {
        return $this->charset;
    }

    /**
     * Устанавливает параметр для функции set_time_limit()
     * set_time_limit() - устанавливает время в секундах, в течение которого скрипт может работать.
     * Если это значение достигнуто, скрипт возвращает фатальную ошибку.
     * По умолчанию лимит - 30 секунд или, если он существует (значение max_execution_time определённое в файле конфигурации)
     * @param integer $timelimit
     * @see SnailMail::getTimeLimit()
     * @return boolean
     */
    function setTimeLimit($timelimit)
    {
        if (is_numeric($timelimit) && settype($timelimit, 'integer') && $timelimit > 0) {
            $this->timelimit = $timelimit;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Возвращает значение параметра для функции set_time_limit()
     * @see SnailMail::send()
     * @return integer $this->timelimit;
     */
    function getTimeLimit()
    {
        return $this->timelimit;
    }

    /**
     * Метод разбивает строку на фрагменты, каждый из которых не превышает 30 символов
     * и затем кодирует данные алгоритмом MIME base64 в соответствие с требованиями RFC 2045
     * @param string $str приходящая строка
     * @see SnailMail::implodeFullAdressString()
     * @see SnailMail::buildFirstPartHeaders()
     * @see SnailMail::getBlockHeaders()
     * @return string строка, кодированная нужным алгоритмом
     */
    function combineBase64($str)
    {

        if (is_string($str) && !empty($str)) {
            //Допустимая длинна строки
            $len = 30;

            $pre = "=?{$this->getCharset()}?B?";
            if (mb_strlen($str) < $len) {
                $enc_str = $pre . base64_encode($str) . '?=';
            } else {
                $enc_str = $pre;
                for ($i = 0, $start = 0; $i < ceil(mb_strlen($str) / $len); $i++) {
                    $enc_str .= base64_encode(mb_substr($str, $start, $len)) . "?=\t";
                    $enc_str .= $pre;
                    $start = $start + $len;
                }
                $enc_str = mb_substr($enc_str, 0, mb_strlen($enc_str) - 10);
            }
            return $enc_str;
        } elseif (empty($str)) {
            return '';
        } else {
            return false;
        }
    }

    /**
     * Формирует строку в виде Имя адресата<email адресата> из массива данных
     * @param array $adresses Массив в котором ключи это email адресата, а значения - имена адресатов
     * @see SnailMail::buildFirstPartHeaders()
     * @return string
     */
    function implodeFullAdressString($adresses)
    {

        if (is_array($adresses) && !empty($adresses)) {
            $full_str = '';
            foreach ($adresses as $key => $value) {
                $full_str .= !empty($value)
                    ? $this->combineBase64($value) . "<$key>"
                    : "<$key>";
                $full_str .= ",\n\t";
            }
            return $full_str = substr($full_str, 0, strlen($full_str) - 3);
        } else {
            return false;
        }
    }

    /**
     * Формирует заголовки(heders) письма
     * @param object $email Объект письма
     * @return string $headers - возвращает сформированные заголовки в виде строки
     */
    function buildFirstPartHeaders($email)
    {
        if (is_object($email) && !empty($email)) {
            $headers = "From:" . $this->implodeFullAdressString($email->getFrom()) . $this->getCRLF();
            $headers .= is_array($email->getReplyTo()) && count($email->getReplyTo())
                ? "Reply-to:" . $this->implodeFullAdressString($email->getReplyTo()) . $this->getCRLF()
                : "Reply-to:" . $this->implodeFullAdressString($email->getFrom()) . $this->getCRLF();
            $headers .= is_array($email->getBCopyTo()) && count($email->getBCopyTo())
                ? "BCC: " . $this->implodeFullAdressString($email->getBCopyTo()) . $this->getCRLF()
                : '';
            $headers .= "To:" . $this->implodeFullAdressString($email->getTo()) . $this->getCRLF()
                . "Subject:" . $this->combineBase64($email->getSubject()) . $this->getCRLF()
                . "User-Agent:SnailMail-kinkybrains.biz" . $this->getCRLF()
                . "MIME-Version:1.0";
            //Если есть мультипартная часть
            if ($this->isMultipart) {
                $headers .= $this->getCRLF() . "Content-Type:multipart/mixed;" . $this->getCRLF()
                    . "\tboundary=\"" . mb_substr($this->getBoundary(), 2, mb_strlen($this->getBoundary())) . "\"" . $this->getCRLF();
            }
            return $headers;

        }
    }

    /**
     * Возвращает описательные заголовки(headers) текстовой части письма
     * @return string - возвращает сформированные заголовки в виде строки
     * @see SnailMail::send()
     */
    function getTextPartHeaders()
    {
        return $this->getBlockHeaders('text');
    }

    /**
     * Возвращает описательные заголовки(headers) HTML части письма
     * @return string - возвращает сформированные заголовки в виде строки
     * @see SnailMail::send()
     */
    function getHTMLPartHeaders()
    {
        return $this->getBlockHeaders('html');
    }

    /**
     * Возвращает описательные заголовки(headers) attachment-части письма
     * @param array $attachdata Массив свойств attachment
     * @return string - возвращает сформированные заголовки в виде строки
     *
     */
    function getAttachmentPartHeaders($attachdata)
    {
        return $this->getBlockHeaders('attachment', $attachdata);
    }

    /**
     * Генерирует заголовки(headers) частей письма в зависимости от ключевого слова (attachment, text, html)
     * @param string $code - ключевое слово
     * @param $data array|false Cвойства attachment (если есть - array, если нет то false)
     * @return string $headers Возвращает сгенерированные заголовки в виде строки
     * @return false при неверноых данных
     * @see SnailMail::getAttachmentPartHeaders()
     * @see SnailMail::getTextPartHeaders()
     * @see SnailMail::getHTMLPartHeaders()
     */
    function getBlockHeaders($code, $data = false)
    {
        //Заголовки(headers) attachment
        if (is_array($data) && !empty($data) && $code === 'attachment') {
            $headers = $this->getBoundary() . $this->getCRLF()
                . "Content-Type:{$data['fileType']};"
                . "\tname=\"" . trim($this->combineBase64($data['fileName'])) . "\"" . $this->getCRLF()
                . "Content-Transfer-Encoding:base64{$this->getCRLF()}"
                . "Content-Disposition:attachment;"
                . "\tfilename=\"" . trim($this->combineBase64($data['fileName'])) . "\"" . $this->getCRLF();
            return $headers;
            //Заголовки(headers) текстовой части
        } elseif (!$data && $code === 'text') {
            $headers = $this->isMultipart
                ? $this->getBoundary() . $this->getCRLF()
                : '';
            $headers .= "Content-Type:text/plain;"
                . "\tcharset=\"" . $this->getCharset() . "\"" . $this->getCRLF()
                . "Content-Transfer-Encoding:base64" . $this->getCRLF();
            return $headers;
            //Заголовки(headers) HTML части
        } elseif (!$data && $code == 'html') {
            $headers = $this->getBoundary() . $this->getCRLF()
                . "Content-Type:text/html;"
                . "\tcharset=\"" . $this->getCharset() . "\"" . $this->getCRLF()
                . "Content-Transfer-Encoding:base64" . $this->getCRLF();
            return $headers;
        } else {
            return false;
        }
    }

    /**
     * Метод устанавливает соединение через сокет,
     * socket_create() - создает ресурс соединения
     * AF_INET (domain)- IPv4 Internet based protocols. TCP and UDP are common protocols of this protocol family.
     * SOCK_STREAM (type)- Provides sequenced, reliable, full-duplex, connection-based byte streams.
     * An out-of-band data transmission mechanism may be supported. The TCP protocol is based on this socket type.
     * SOL_TCP (protocol) - The Transmission Control Protocol is a reliable,
     * connection based, stream oriented, full duplex protocol.
     * socket_connect() — Initiates a connection on a socket
     * @see SnailMail::send()
     */
    function socketCreateAndLog($log = 3)
    {

        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $result = @socket_connect($this->socket, $this->host, $this->port);
        if (!is_resource($this->socket) || !$result) {
            throw new SnailMail\Exception('socket_create_error socket[' . var_export($this->smtp_socket, true) . '] host[' . var_export($this->host, true) . '] port[' . var_export($this->port, true) . ']');
        }
        $this->saveLog2Array('socket[' . var_export($this->smtp_socket, true) . '] host[' . var_export($this->host, true) . '] port[' . var_export($this->port, true) . ']', $log);
        return true;
    }

    function socketReadAnswer($lenght = 512)
    {
        $this->currentAnswer = '';
        $this->currentAnswer = is_numeric($lenght)
            ? socket_read($this->socket, intval($lenght))
            : false;
        if (is_string($this->currentAnswer) || !empty($this->currentAnswer)) {
            return $this->currentAnswer;
        } else {
            return false;
        }
    }

    function analiseAnswer()
    {
        $code = is_string($this->currentAnswer) && !empty($this->currentAnswer)
            ? (int)mb_substr($this->currentAnswer, 0, 1)
            : false;
        if (!$code || $code > 3) {
            $errorcode = socket_last_error($this->socket);
            $errormsg = utf8fix(socket_strerror($errorcode));
            throw new SnailMail\Exception('Bad Server Answer Data. Socket Error: ' . var_export($errormsg, true) . ' This: ' . var_export($this, true));
        } else {
            return true;
        }
    }

    function getLastAnswer()
    {
        return $this->currentAnswer;
    }

    function saveLog2Array($strRequest, $logMode)
    {
        if ($logMode & 1) {
            $strAnswer = $this->socketReadAnswer();
            $this->smtpAnswers[$this->sendID] = is_string($strAnswer) && !empty($strAnswer)
                ? strftime("%H:%M:%S", time()) . ' # ' . $strAnswer
                : false;
        }
        if ($logMode & 2) {
            $this->smtpRequests[$this->sendID] = is_string($strRequest) && !empty($strRequest)
                ? strftime("%H:%M:%S", time()) . ' # ' . $strRequest
                : false;
        }
        $this->sendID++;
        return true;
    }

    function socketPutsAndLog($msg, $log = 0)
    {
        if (is_string($msg) && !empty($msg)) {
            socket_write($this->socket, $msg . $this->getCRLF());
        }
        $this->saveLog2Array($msg, $log);
        $this->analiseAnswer();
    }

    function saveData2File($filename, $saveMode)
    {
        global $S;
        $fsh = $S->getFS();

        if (is_string($filename) && !empty($filename) && (int)$saveMode == 1) {
            $dir = SYS_VAR_DIR . '/mods/' . $this->getCode() . '/';
            if (!$fsh->is_dir($dir)) {
                $fsh->make_dir($dir);
            }
            $path = $fsh->fileExists($dir . $filename)
                ? $fsh->genNewFileName($dir . $filename)
                : $dir . $filename;

            $data = $this->generateLogData();

            if (!$data) {
                throw new SnailMail\Exception('bad data write to file');
            }
            if (!file_put_contents($path, $data)) {
                throw new SnailMail\Exception('cant write data');
            }
            return true;
        }
        return false;
    }

    function generateLogData()
    {
        end($this->smtpAnswers);
        $lastSmtpAnswer = each($this->smtpAnswers);
        end($this->smtpRequests);
        $lastSmtpRequest = each($this->smtpRequests);
        if ((is_array($this->smtpAnswers) && !empty($this->smtpAnswers)) || (is_array($this->smtpRequests) && !empty($this->smtpRequests))) {
            for ($i = 0; $i <= $lastSmtpAnswer['key'] || $i <= $lastRequest['key']; $i++) {
                $str .= !empty($this->smtpRequests[$i])
                    ? $i . '. Request: ' . $this->smtpRequests[$i] . $this->getCRLF()
                    : '';
                $str .= !empty($this->smtpAnswers[$i])
                    ? $i . '. Answer: ' . $this->smtpAnswers[$i] . $this->getCRLF()
                    : '';
            }
        }
        if (is_string($this->error) && !empty($this->error)) {
            $str .= $this->getCRLF() . 'Error: ' . $this->error;
        }
        return is_string($str) && !empty($str)
            ? $str
            : false;
    }

    /**
     * Открывает файл и считывает данные в ресурс сокетного соединения, шифруя данные в Base64
     * @param string $file путь к файлу
     * @see SnailMail::send()
     */
    function readAttachmentFile($file)
    {
        $handle = fopen($file, 'rb');
        $fsize = filesize($file);
        while (!feof($handle)) {
            $this->socketPutsAndLog(imap_binary(fread($handle, $fsize)), 0);
        }
        fclose($handle);
    }

    /**
     * Метод производит отправку объекта письма, который сформирован классом Email
     * @param $email SnailMail\Email Объект письма
     * @see class Email
     */
    function send($email, $fileName = false, $saveMode = 1)
    {
        try {
            if (!is_object($email)) {
                throw new SnailMail\Exception('wrong_object');
            }
            if (!is_array($email->getTo()) || !count($email->getTo())) {
                throw new SnailMail\Exception('no_email_for_send');
            }
            if (!is_array($email->getFrom()) || !count($email->getFrom())) {
                $email->setFrom($this->gC('default', 'email-from'), $this->gC('default', 'name-from'));
            }

            $to = is_array($email->getBCopyTo()) && count($email->getBCopyTo())
                ? array_merge_recursive($email->getTo(), $email->getBCopyTo())
                : $email->getTo();

            //Устанавливаем Таймлимит
            set_time_limit($this->getTimeLimit());
            //Проверяем, является ли письмо мультипартным
            $this->isMultipart = $email->isMultipart();

            //Создаем сокет-ресурс
            $this->socketCreateAndLog();
            //Команда-приветствие сервера
            $this->socketPutsAndLog($this->getHelloString() . ' ' . $this->getHelloHost(), 3);
            //SMTP аутентификация. Данные сначала шифруются base64
            if ($this->host !== 'localhost' && $this->host !== '127.0.0.1') {
                $this->socketPutsAndLog("AUTH LOGIN", 3);
                $this->socketPutsAndLog(base64_encode($this->getLogin()), 3);
                $this->socketPutsAndLog(base64_encode($this->getPassword()), 3);
            }
            //Последовательная отправка письма согласно стандартам
            $this->socketPutsAndLog("MAIL FROM: <" . key($email->getFrom()) . ">", 3);
            foreach ($to as $key => $value) {
                $this->socketPutsAndLog("RCPT TO: <$key>", 3);
            }
            $this->socketPutsAndLog("DATA", 3);
            $this->socketPutsAndLog($this->buildFirstPartHeaders($email));
            if ($email->getText() != '') {
                $this->socketPutsAndLog($this->getTextPartHeaders());
                $this->socketPutsAndLog(trim(imap_binary($email->getText())));
            }
            if ($email->getHTML() != '') {
                $this->socketPutsAndLog($this->getHTMLPartHeaders());
                $this->socketPutsAndLog(trim(imap_binary($email->getHTML())));
            }
            if (count($email->getAttachment())) {
                foreach ($email->getAttachment() as $attach) {
                    $this->socketPutsAndLog($this->getAttachmentPartHeaders($attach));
                    if ($attach['isFile'] == true) {
                        $this->readAttachmentFile($attach['file']);
                    } else {
                        $this->socketPutsAndLog(imap_binary($attach['file']));
                    }
                }
            }
            if ($this->isMultipart) {
                $this->socketPutsAndLog($this->closeBoundary());
            }
            $this->socketPutsAndLog('.');
            $this->socketPutsAndLog("QUIT", 3);
            //Закрытие сокета
            if (is_resource($this->socket)) {
                socket_close($this->socket);
            }
            if (is_string($fileName) && !empty($fileName)) {
                $this->saveData2File($fileName, $saveMode);
            }
            return true;
        } catch (SnailMail\Exception $e) {
            $error = 'Line:' . $e->getLine() . ' Error Message:' . $e->getMessage();
            $this->log()->error($error);
            if (is_string($fileName) && !empty($fileName)) {
                $this->saveData2File($fileName, $saveMode);
            }
            return false;
        }
    }
}


