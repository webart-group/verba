<?php
namespace Verba\Block;

class Html extends \Verba\Block
{
    public $contentType = 'html';

    use \Verba\Html\Includes;
    use \Verba\Block\Template;

    function build()
    {
        if ($this->tpl->isDefined('content')) {
            $this->content = $this->tpl->parse(false, 'content');
        } elseif (!$this->content) {
            $this->content = '';
            if (count($this->items)) {
                foreach ($this->items as $itm) {
                    $this->content .= $itm->content;
                }
            }
        }
        return $this->content;
    }

    function buildItems()
    {

        if (!count($this->items)) {
            return;
        }

        foreach ($this->items as $key => $h) {

            if (!$h) {
                if (is_string($key) && !empty($key)) {
                    $this->tpl->assign($key, '');
                }
                continue;
            }

            $r = null;

            // old-definition as  array(mod, method)
            if (is_array($h)) {
                $module = \Verba\_mod($h[0]);
                if (!$module || !is_callable(array($module, $h[1]))) {
                    $this->log()->error('Module \'' . var_export($h[0], true) . '\' or method \'' . var_export($h[1], true) . '\'not avaible. $key:' . var_export($key, true) . ', params:' . var_export($h, true));
                    continue;
                }
                $args = count($h) > 4 ? array_slice($h, 4) : array();
                array_unshift($args, $this);
                $r = call_user_func_array(array($module, $h[1]), $args);

                // blocks
            } elseif ($h instanceof \Verba\BlockInterface) {
                if ($h->isMuted()) {
                    $r = '';
                } else {
                    $h->buildItems();
                    $h->build();
                    $r = $h->content;

                    $this->mergeHtmlIncludes($h);
                }
            }

            if (is_string($key) && !empty($key)) {
                $this->tpl->assign($key, (string)$r);
            }
        }
    }
}
