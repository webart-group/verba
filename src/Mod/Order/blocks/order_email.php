<?php

class order_email extends \Verba\Block\Html
{

    public $role = 'order-email';

    public $templatesBase;
    public $tplvars = array(
        'ORDER_ITEMS' => '',
        'ORDER_SUMMARY' => '',
        'ORDER_INFO_FIELDS' => '',
    );
    /**
     * @var \Verba\Mod\CoMail\PHPMailer
     */
    protected $Mail;
    /**
     * @var \Verba\Mod\Order\Model\Order
     */
    protected $Order;

    protected $tpl_base;
    protected $tpl_custom;
    public $parseItems = false;
    public $parseSummary = false;
    public $subjLangKey;
    public $bodyLangKey;

    public $mailSubject;
    public $mailBody;

    public $Url;

    public $separateSend = false;

    // массив кодов атрибутов заказа, из которых будет сгенерирован блок {INFO_FIELDS}
    // должны быть доступны как Order->$code
    // Так же можно указать спец заголовок в книге локали под кодом order fields $code
    // в этом случае будет взято из словаря, иначе - как Титл Атрибута, иначе - сам код
    public $infoFields = [];

    /**
     * @var \Act\AddEdit
     */
    protected $ae;

    function init()
    {

        if (!is_object($this->Order) || !$this->Order instanceof \Verba\Mod\Order\Model\Order) {
            throw  new \Verba\Exception\Building('Bad Order Object');
        }

        $this->Url = $this->Order->getStatusUrl();

        $this->curr = \Verba\_mod('currency')->getCurrency($this->Order->currencyId);
        $this->paysys = \Verba\_mod('payment')->getPaysys($this->Order->paysysId);


        // Шаблоны
        $path = rtrim( $this->tpl->getCurrentPath(), '/');
        if (is_object($this->_invoker) && $this->_invoker instanceof order_email) {
            $this->templatesBase = $this->_invoker->templatesBase;
            $this->tplvars = $this->_invoker->tplvars;
        } else {
            $this->templatesBase =  \Verba\FileSystem\Local::scandir($path . $this->tpl_base, 1, true, array($this, 'walkArrayTplPathes'), array($path));
        }

        $this->templates = $this->templatesBase;

        if (is_string($this->tpl_custom) && !empty($this->tpl_custom)) {
            $customTemplates =  \Verba\FileSystem\Local::scandir($path . $this->tpl_base . $this->tpl_custom, 1, true, array($this, 'walkArrayTplPathes'), array($path));
            if (is_array($customTemplates)) {
                $this->templates = array_replace_recursive($this->templatesBase, $customTemplates);
            }
        }

        $this->tpl->clear_tpl(array_keys($this->templates));
        $this->tpl->define($this->templates);

        // Окружение для Заказа по умолчанию
        if (!is_array($this->tplvars) || !array_key_exists('ORDER_ID', $this->tplvars)) {
            $this->assignDefaultOrderEnvirement();
        }

        $this->tpl->assign($this->tplvars);

        // items part
        if ($this->parseItems) {
            $itemsTemplates = array_intersect_key($this->tpl()->getFilelist(), array_flip(array('items', 'item', 'extra', 'extraItem')));
            $itemsTemplates['content'] = $itemsTemplates['items'];

            $this->addItems(array(
                'ORDER_ITEMS' => new order_statusItems($this, array(
                    'Order' => $this->Order,
                    'parsePromotions' => true,
                    'templates' => $itemsTemplates
                ))
            ));
        }

        // summary part
        if ($this->parseSummary) {
            $summTemplates = array_intersect_key($this->tpl()->getFilelist(), array_flip(array('summary', 'summary-total')));
            $summTemplates['content'] = $summTemplates['summary'];

            $this->addItems(array(
                'ORDER_SUMMARY' => new order_statusSummary($this, array(
                    'Order' => $this->Order,
                    'templates' => $summTemplates
                ))
            ));
        }
    }

    function prepare()
    {
        $this->tplvars['ORDER_INFO_FIELDS'] = '';

        //Info Fields
        if (is_array($this->infoFields) && !empty($this->infoFields)) {
            $_order = \Verba\_oh('order');

            foreach ($this->infoFields as $orderAttrCode) {
                $A = $_order->A($orderAttrCode);
                if (!$A) {
                    continue;
                }
                $code = $A->getCode();
                $displayName = \Verba\Lang::get('order fields ' . $code);
                if (!is_string($displayName)) {
                    $displayName = $A->getTitle();
                }
                if (!is_string($displayName)) {
                    $displayName = $orderAttrCode;
                }
                $v = htmlspecialchars($this->Order->{$code});
                $this->tpl->assign(array(
                    'ORDER_INFO_FIELD_NAME' => $displayName,
                    'ORDER_INFO_FIELD_VALUE' => $v,
                    'ORDER_INFO_FIELD_' . strtoupper($code) . '_NAME' => $displayName,
                    'ORDER_INFO_FIELD_' . strtoupper($code) . '_VALUE' => $v,
                ));
                $this->tpl->parse('ORDER_INFO_FIELDS', 'infoField', true);
            }
        }
    }

    function build()
    {
        // Если в качестве шаблона задано использовать словарь
        if (is_string($this->subjLangKey)) {
            $this->mailSubject = $this->tpl->parse_template(Lang::getFromLang($this->Order->locale, $this->subjLangKey));
        } else {
            $this->mailSubject = $this->tpl->parse(false, 'subject');
        }

        // Если в качестве шаблона задано использовать словарь
        if (is_string($this->bodyLangKey)) {
            $this->mailBody = $this->tpl->parse_template(Lang::getFromLang($this->Order->locale, $this->bodyLangKey));
        } else {
            $this->mailBody = $this->tpl->parse(false, 'body');
        }
    }

    function setAe($ae)
    {
        if (!$ae instanceof \Act\AddEdit) {
            return false;
        }
        $this->ae = $ae;
    }

    function getAe()
    {
        return $this->ae;
    }

    function setTpl_base($val)
    {
        if (!is_string($val)) {
            return false;
        }
        $val = trim($val, '/ ');
        $this->tpl_base = '/' . $val;
    }

    function setTpl_custom($val)
    {
        if (!is_string($val)) {
            return false;
        }
        $val = trim($val, '/ ');
        $this->tpl_custom = '/' . $val;
    }

    function customizeIt($customizeCode, $dcfg = null)
    {
        $this_class = get_class($this);
        if (!is_array($dcfg)) {
            $dcfg = array();
        }
        $dcfg['tpl_custom'] = $customizeCode;
        $dcfg['Order'] = $this->Order;
        $dcfg['ae'] = $this->ae;

        $b = new $this_class($this, $dcfg);
        return $b;
    }

    function assignDefaultOrderEnvirement()
    {
        $this->tplvars['ORDER_ID'] = $this->Order->code;
        $this->tplvars['ORDER_NUMBER'] = $this->Order->code;
        $this->tplvars['ORDER_SHOPNAME'] = \Verba\Lang::getFromLang($this->Order->locale, 'order shopName');
        $this->tplvars['ORDER_STATUS_URL'] = $this->Url;
        $this->tplvars['ORDER_CURRENCY_UNIT'] = $this->curr->short;
        $this->tplvars['ORDER_PAYSYS_TITLE'] = $this->paysys->title;
        $this->tplvars['ORDER_TOTAL_COST'] = number_format($this->Order->getTotal(), 2, '.', ' ');
        $this->tplvars['ORDER_TOPAY_COST'] = number_format($this->Order->getTopay(), 2, '.', ' ');
        $this->tplvars['ORDER_EMAIL'] = $this->Order->email;
        $this->tplvars['ORDER_NAME'] = htmlspecialchars($this->Order->name);
        $this->tplvars['ORDER_PHONE'] = htmlspecialchars($this->Order->phone);
        $this->tplvars['ORDER_SURNAME'] = htmlspecialchars($this->Order->surname);
        $this->tplvars['ORDER_COUNTRY'] = htmlspecialchars($this->Order->country__value);
        $this->tplvars['ORDER_ADDRESS'] = htmlspecialchars($this->Order->address);
        $this->tplvars['ORDER_COMMENT'] = htmlspecialchars($this->Order->comment);
        $this->tplvars['ORDER_CREATED'] = $this->Order->getFormatedCreationDate();
    }

    function sendTo($adr)
    {
        if (is_string($adr)) {
            $adr = array($adr => '');
        }
        if (!is_array($adr) || !count($adr)) {
            $this->log()->error('Bad adresses to sending Order email');
            return false;
        }

        /**
         * @var $mMail CoMail
         */
        $mMail = \Verba\_mod('CoMail');

        if (!$this->Mail) {
            $mcfg = \Verba\_mod('order')->gC('mailing');
            $this->Mail = $mMail->PHPMailer($mcfg['mail']);
        }

        $this->Mail->ClearAllRecipients();
        $this->Mail->ClearAttachments();

        $this->Mail->setSubject($this->mailSubject);
        $this->Mail->MsgHTML($this->mailBody);

        $erroremails = array();

        $this->saveToLocalTestFile($adr);

        if ($this->separateSend) {
            foreach ($adr as $tomail => $toname) {
                $this->Mail->ClearAllRecipients();
                $this->Mail->AddAddress($tomail, $toname);
                if (!$mMail->Send($this->Mail)) {
                    $erroremails[] = array($tomail, $this->Mail->ErrorInfo);
                }
            }
        } else {
            foreach ($adr as $tomail => $toname) {
                $this->Mail->AddAddress($tomail, $toname);
            }
            if (!$mMail->Send($this->Mail)) {
                $erroremails[] = array(implode(',', array_keys($adr)), $this->Mail->ErrorInfo);
            }
        }

        if (count($erroremails)) {
            foreach ($erroremails as $err) {
                $this->log()->error('Order #' . $this->Order->code . ' emails sending error. to:[' . var_export($err[0], true) . '] errInfo' . $err[1]);
            }
            return false;
        }

        return true;
    }

    function saveToLocalTestFile($to)
    {

        //Сохранение в локальный файл
        if (!\Verba\_mod('order')->gC('saveEmailToTestFile')) {
            return false;
        }
        $path = SYS_ROOT . '/test/order_emails_tests';
         \Verba\FileSystem\Local::needDir($path);
        $filepath = $path . '/' . get_class($this) . (strlen($this->tpl_custom) ? str_replace('/', '_', $this->tpl_custom) : '') . '.html';

        $toall = array();
        foreach ($to as $toemail => $toname) {
            $toall[] = is_string($toname) && !empty($toname) ? $toemail . ' "' . htmlspecialchars($toname) . '"' : $toemail;
        }

        $this->tpl->assign(array(
            'EMAIL_SUBJ' => $this->mailSubject,
            'EMAIL_BODY' => $this->mailBody,
            'EMAIL_TO' => implode(', ', $toall),
        ));

        $this->tpl->define(array(
            'testLocalFile' => 'shop/order/email/creation/test_local_file.tpl'
        ));
        $testHtml = $this->tpl->parse(false, 'testLocalFile');
        file_put_contents($filepath, $testHtml);
    }

    function walkArrayTplPathes($value, $isDir, $pathToRemove)
    {
        return array(substr($value, strlen($pathToRemove)), pathinfo($value, PATHINFO_FILENAME));
    }

    function setOrder($order)
    {
        $this->Order = $order instanceof \Verba\Mod\Order\Model\Order ? $order : false;
        if (!$this->Order) {
            throw new Exception('Bad order instance');
        }
    }

    function getOrder()
    {
        return $this->Order;
    }
}
