<?php
namespace Mod\Profile\Block\Toolbar;

class Tool  extends \Verba\Block\Html{

    public $templates = array(
        'content' => 'profile/toolbar/tool.tpl',
    );

    public $onlyForStores = false;

    public $badge = array(
        'value' => '',
        'color' => '',
    );
    public $icon = array(
        'w' => 0,
        'h' => 0,
        'src' => '',
    );

    public $url = '#';
    public $code;
    public $cssClass = '';

    public $notifierAgent = null;

    public $notifyCount;

    public $tplvars = array(
        'TOOL_CLASS' => '',
        'TOOL_NTF_ID' => '',
        'TOOL_TITLE' => '',
    );

    protected $userId;
    protected $seed;

    function init(){

        $this->seed = rand(99999,999999);

        if(!isset($this->userId)){
            $this->userId =\Verba\User()->getId();
        }

        if(is_array($this->notifierAgent))
        {
            $this->notifierAgent = array_replace_recursive(
                self::getDefaultNotifierCfg(),
                $this->notifierAgent
            );
        }elseif($this->notifierAgent !== null){
            $this->notifierAgent = null;
        }
    }

    static function getDefaultNotifierCfg(){
        return [
            'pipe' => null,
            'className' => 'NotifyAgentUserTool',
            'channel' => null,
            'priority' => 0,
            'eid' => null,
        ];
    }

    function prepare(){

        $this->notifyCount = $this->loadNotifyCount();
        if($this->notifyCount){
            $this->cssClass = empty($this->cssClass) ? 'have-notify' : $this->cssClass.' '.'have-notify';
        }

        if($this->notifierAgent){
            $this->prepareNotifierAgent();
            $this->parent('user-toolbar')->orderNotifyAgent($this);
            $this->tpl->assign(array(
                'NTF_AGENT_DATA' => ' '.$this->parseNotifyAgentDATA(),
            ));
        }else{
            $this->tpl->assign(array(
                'NTF_AGENT_DATA' => '',
            ));
        }

        $buttonTitle = $this->code ? \Verba\Lang::get('user page_tools '.$this->code) : false;

        $this->tpl->assign(array(
            'TOOL_URL' => $this->url,
            'TOOL_BADGE_COLOR' => $this->badge['color'],
            'TOOL_NOTIFY_COUNT' => (string)$this->notifyCount,
            'TOOL_ICON' => '',
            'TOOL_CLASS' => $this->cssClass,
            'TOOL_TITLE' => $buttonTitle ? $buttonTitle : '',
            'TOOL_NTF_ID' => isset($this->notifierAgent['eid']) && $this->notifierAgent['eid'] ? $this->notifierAgent['eid'] : '',
        ));

        if(is_array($this->icon)){
            $this->tpl->assign(array(
                'TOOL_ICON' => '<div></div>'
                //'TOOL_ICON' => '<object type="image/svg+xml" width="'.($this->icon['w']).'" height="'.($this->icon['h']).'" data="/images/svg/sprite.svg#'.$this->icon['src'].'"></object>'
            ));
        }
    }

    function prepareNotifierAgent(){
        if(!is_array($this->notifierAgent))
        {
            $this->notifierAgent = self::getDefaultNotifierCfg();
        }

        if(!$this->notifierAgent['channel']){
            $this->notifierAgent['channel'] = \Verba\_mod('user')->getChannelName($this->userId);
        }

        if(!$this->notifierAgent['eid']){
            $this->notifierAgent['eid'] = $this->notifierAgent['pipe'].'-'.$this->seed;
        }
        return $this->notifierAgent;
    }

    function parseNotifyAgentDATA(){

        if(!is_array($this->notifierAgent) || !$this->notifierAgent['eid']){
            return '';
        }

        return ' data-ntf="'.$this->notifierAgent['eid'].'"';
    }

    function loadNotifyCount(){
        return null;
    }

}