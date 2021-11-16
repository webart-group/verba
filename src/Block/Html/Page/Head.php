<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 15.01.2020
 * Time: 23:13
 */

namespace Verba\Block\Html\Page;


class Head extends \Verba\Block\Html
{
    public $templates = [
        'content' => 'page/head.tpl'
    ];

    public $tplvars = array(
        'TITLE' => '',
        'META_PROPERTIES' => '',
        'NAMED_META' => '',
        'HEAD_TAGS' => '',
        'CSS_INCLUDES' => '',
        'JS_INCLUDES' => '',
        'ITEMS' => '',
    );

    public $role = 'HtmlHead';

    public function build()
    {
        $layout = $this->getBlockByRole('layout');

        $title = $layout->getNamedMeta('title');
        if($title){ //remove title from NamedMeta to avoid while NamedMeta handling;
            $layout->clearNamedMeta('title');
        }

        $this->tpl()->assign([
            'TITLE' => $title ? htmlspecialchars($title) : '',
            'META_PROPERTIES' => static::parseMetaProperties($layout->getMetaProperty()),
            'NAMED_META' => static::parseNamedMeta($layout->getNamedMeta()),
            'HEAD_TAGS' => static::parseHeadTags($layout->getHeadTags()),
            'CSS_INCLUDES' => $this->parseInc('css', $layout->getCss()),
            'JS_INCLUDES' => $this->parseInc('js', $layout->getScripts()),
        ]);

        if($this->items){
            foreach ($this->items as $Item){
                $this->tpl->assign(
                    'ITEMS',
                    $Item instanceof \Verba\Block ? $Item->getContent() : (string)$Item,
                    true);
            }
        }

        $this->content = $this->tpl()->parse(false, 'content');
        return $this->content;
    }

    static function parseMetaProperties(array $metaProperties){

        if(!count($metaProperties)) {
            return '';
        }
        $r = '';
        foreach($metaProperties as $name => $content){
            $r .= "\n".'<meta property="'.$name.'" content="'.htmlspecialchars((string)$content).'"/>';
        }

        return $r;
    }

    static function parseNamedMeta(array $namedMeta){
        if(!count($namedMeta)) {
            return '';
        }
        $r = '';

        foreach($namedMeta as $tag => $content){
            if(empty($content)){
                continue;
            }
            $r .= "\n<meta name=\"".$tag.'" content="'.htmlspecialchars($content).'" />';
        }

        return true;
    }

    static function parseHeadTags( array  $headTags){

        if(!count($headTags)){
            return '';
        }
        $r = '';
        foreach($headTags as $ctag){
            $attrs_str = '';
            $tagName = $ctag['tag'];
            if(is_array($ctag['attrs']) && !empty($ctag['attrs'])){
                foreach($ctag['attrs'] as $aname => $avalue){
                    $attrs_str .= ' '.$aname.'="'.htmlspecialchars($avalue).'"';
                }
            }

            if(isset($ctag['content'])){
                $r .= '<' . $tagName . $attrs_str . '>'.((string)$ctag['content']).'</'.$tagName.'>';
            }else{
                $r .= '<' . $tagName . $attrs_str . '/>';
            }
        }

        return $r;
    }

    /**
     * @param string $type
     * @param null|array $arrayIncs
     * @return bool|string
     */
    function parseInc($type, $arrayIncs = null){
        $type = (string)strtolower($type);
        if(!$type){
            return false;
        }
        $complete_inc_str = '';

        if(!is_array($arrayIncs)){
            switch($type){
                case 'js':
                    $propName = 'scripts';break;
                case 'css':
                default:
                    $propName = $type;break;
            }

            if(!property_exists($this, $propName)){
                return false;
            }

            $arrayIncs = &$this->{$propName};
        }
        if(!is_array($arrayIncs) || !count($arrayIncs)){
            return $complete_inc_str;
        }

        $all_files_urls = array();
        $all_inc = array();

        uasort ($arrayIncs, '\Verba\sortByPriorityAsArrayDesc');
        foreach($arrayIncs as $c_inc) {
            list($c_url, $query_str, $attrs_str) = $this->makeIncVars($c_inc);
            $all_files_urls[] = $c_url;
            $all_inc[] = array($c_url, $attrs_str);
        }

        if(count($all_inc)){
            $complete_inc_str = $this->assembleIncludes($type, $all_inc, $all_files_urls);
        }

        return $complete_inc_str;
    }

    function makeIncVars($c_inc){
        $attrs_str = $query_str = '';

        if(!is_array($c_inc['attrs'])){
            $c_inc['attrs'] = array();
        }
        $c_inc['attrs']['data-order'] = $c_inc['priority'];

        if(is_array($c_inc['attrs']) && count($c_inc['attrs'])){
            foreach($c_inc['attrs'] as $c_attr_name => $c_attr_value){
                if(is_numeric($c_attr_name)){
                    $attrs_str .= ' '.$c_attr_value;
                }else{
                    $attrs_str .= ' '.$c_attr_name.'="'.$c_attr_value.'"';
                }
            }
        }

        $addVers = is_array($c_inc['params']) && array_key_exists('addversion', $c_inc['params'])
            ? (bool)$c_inc['params']['addversion']
            : true;

        $encode_query = is_array($c_inc['params']) && array_key_exists('encode_query', $c_inc['params'])
            ? (bool)$c_inc['params']['encode_query']
            : true;


        if(!is_array($c_inc['query'])){
            $c_inc['query'] = array();
        }

        if($addVers && !isset($c_inc['v'])){
            $c_inc['query']['v'] = SYS_VERSION;
        }

        foreach($c_inc['query'] as $c_var_name => $c_var_value){
            if($encode_query){
                $c_var_name = urlencode($c_var_name);
                $c_var_value = urlencode($c_var_value);
            }
            if(is_numeric($c_var_name)){
                $query_str .= '&'.urlencode($c_var_value);
            }else{
                $query_str .= '&'.urlencode($c_var_name).'='.urlencode($c_var_value);
            }
        }

        $c_url = $c_inc['url'];

        if(!empty($query_str)){
            $query_str{0} = '?';
            $c_url .= $query_str;
        }

        return array($c_url, $query_str, $attrs_str);
    }

    function assembleIncludes($part, $all_inc, $all_files_urls){

        $mPage = \Verba\_mod('Page');
        $compileFlagPropName = 'compile_'.$part;
        $compileFlagValue = isset($this->{$compileFlagPropName}) ? (bool)$this->{$compileFlagPropName} : null;

        $tryToCompile = $compileFlagValue !== null && $compileFlagValue
            ? true
            : false;
        $unable_use_cache = false;
        $cache_path = $mPage->gC('cache '.$part.' path').'/'.SYS_VERSION;
        $cache_url_prefix = $mPage->gC('cache '.$part.' url').'/'.SYS_VERSION;
        $ext = $mPage->gC('cache '.$part.' extension');
        $tag = $mPage->gC('cache '.$part.' tag');
        if(!is_string($ext)){
            $ext = $part;
        }
        $complete_inc_str = '';
        if($tryToCompile){

            try{
                $all_files_url_str = implode("\n",$all_files_urls);
                $hash = md5($all_files_url_str);
                $cache_filename = $cache_path.'/'.$hash.'.'.$ext;

                if(!file_exists($cache_filename)){
                    if(!\Verba\FileSystem\Local::needDir(dirname($cache_filename), 0755)){
                        throw new \Exception('Unable to create '.$part.' cache dir');
                    }
                    $fp = fopen($cache_filename, 'w');

//          fwrite($fp, "/* \n".date('Y-m-d H:i:s')." '".$part."' ".$_SERVER['REQUEST_URI']."\nFiles:\n".$all_files_url_str." */\n\n");
                    fwrite($fp, "/* \n".date('Y-m-d H:i:s')." '".$part."' ".$_SERVER['REQUEST_URI']." */\n\n");

                    foreach($all_inc as $c_item){
                        $url = new \Url($c_item[0]);
                        //$fc = "/*** $c_item[0] ***/\n";
                        $fc = "/*** ~~~~~~~ ***/\n";
                        $inc_content = $this->loadIncContent($part, $c_item, $url);
                        $fc .= $inc_content."\n";

                        if(!fwrite($fp, $fc)){
                            throw new \Exception('Unable to write '.$part.' cache file');
                        }
                    }
                    fclose($fp);
                     \Verba\FileSystem\Local::chmod($cache_filename, 0744);
                }

                $cache_url = $cache_url_prefix.'/'.$hash.'.'.$ext;
                $complete_inc_str = "\n".str_replace(array('{url}', '{attrs}'), array($cache_url, ''), $tag);
            }catch(\Exception $e){
                $unable_use_cache = true;
                $this->log()->error($e->getMessage());
            }
        }
        if(empty($complete_inc_str) || $unable_use_cache){
            foreach($all_inc as $c_inc){
                $complete_inc_str .= "\n".str_replace(['{url}','{attrs}'], [$c_inc[0],(isset($c_inc[1]) ?(string)$c_inc[1] : '') ], $tag);
            }
        }
        return $complete_inc_str;
    }

    protected function loadIncContent($part, $c_item, $url){

        $content = file_get_contents($url->get(true));

        if($part == 'css'){
            $content = $this->substRelPathesInCss($c_item, $content);
        }
        return $content;
    }

    protected  function substRelPathesInCss($inc, $content){
        if(!preg_match_all("/url\s*\(\s*['\"]?(.*?)['\"]?\s*\)/ix", $content, $matches)
            && !count($matches[1])){
            return $content;
        }

        $ctx = dirname($inc[0]);
        $ctx_arr = explode('/', $ctx);
        if($ctx_arr[0] == ''){
            $ctx_arr = array_slice($ctx_arr, 1);
        }
        $matches[1] = array_unique($matches[1]);
        $patterns = $replacements = array();
        foreach($matches[1] as $match_idx => $fileurl){

            if(substr($fileurl, 0, 1) == '/'
                || false == ($cpattern = '/\(\s*[\'"]?'.preg_quote($fileurl, '/').'[\'"]?\s*\)/')
                || in_array($cpattern, $patterns)){
                continue;
            }

            $pinfo = pathinfo($fileurl);
            $knw_path_arr = isset($pinfo['dirname'])
            && !empty($pinfo['dirname'])
            && $pinfo['dirname'] != '.'
                ?  explode('/', $pinfo['dirname'])
                : array();

            $shift = 0;
            foreach($knw_path_arr as $c_dir){
                if($c_dir == '..'){
                    $shift++;
                }
            }
            if($shift){
                $knw_path_arr = array_slice($knw_path_arr, $shift);
                $c_ctx = array_slice($ctx_arr, 0, ($shift * -1));
            }else{
                $c_ctx = $ctx_arr;
            }

            $c_ctx = '/'
                . (count($c_ctx) ? implode('/',$c_ctx).'/' : '')
                . (count($knw_path_arr) ? implode('/',$knw_path_arr).'/' : '')
                . $pinfo['basename'];

            $patterns[] = $cpattern;
            $replacements[] = '("'.$c_ctx.'")';
        }

        if(count($patterns)){
            $content = preg_replace($patterns, $replacements, $content);
        }

        return $content;
    }

}
