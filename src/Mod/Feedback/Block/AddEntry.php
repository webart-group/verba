<?phpnamespace Verba\Mod\FeedBack\Block;use Verba\Mod\FeedBack;use Verba\Mod\Telegram;use function Verba\_mod;class AddEntry extends \Verba\Mod\Routine\Block\CUNow{    public $valid_otype = 'feedback';    public $responseAs = 'json-item-keys';    public $responseAsKeys = array(        'id'    );    public $templates = array(        'admin_notify' => '/telegram/admin_notify.tpl'    );    function init()    {        $this->rq->setOt('feedback');    }    function routedActions()    {        return [            'create' => true,        ];    }    function build()    {        parent::build();        if (!$this->ae->getIID() || $this->ae->haveErrors()) {            throw  new \Verba\Exception\Building($this->ae->log()->getMessagesAsStr('error'));        }        /**         * @var FeedBack $mFeedback         * @var Telegram $mTelegram         */        $mFeedback = _mod('feedback');        $mFeedback->sendCreationNonifyEmail($this->ae->getObjectData());        $tpl = $this->tpl();        $tpl->define($this->templates);        $contactFormData = $this->ae->getObjectData();        $tpl->assign(array(            'NAME' => $contactFormData['name'],            'EMAIL' => $contactFormData['email']        ));        $mTelegram = _mod('telegram');        $mTelegram->notifyAdmins($tpl->parse(false,'admin_notify'));        return $this->content;    }}