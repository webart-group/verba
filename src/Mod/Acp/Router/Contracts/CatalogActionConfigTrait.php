<?php

namespace Verba\Mod\Acp\Router\Contracts;

use Verba\Branch;
use Verba\Request;
use function Verba\_oh;

trait CatalogActionConfigTrait
{
    protected $_catalogActionDefaultConfig = 'acp/products/product';

    function isCatalogActionConfigApplicable()
    {
        return true;
    }

    public function getCatalogActionConfig()
    {
        return $this->_catalogActionDefaultConfig;
    }

    public function applyCatalogActionConfig(){

        if(!$this->isCatalogActionConfigApplicable()) {
            return null;
        }

        // if catalog specified, try to extract all-catalog-chain configs
        $catsChain = self::extractCatsChain($this->request);

        $catsCfgRoot = $cfragment = '';
        if (is_array($catsChain) && count($catsChain)) {
            foreach ($catsChain as $cdata) {
                $cfragment = empty($cfragment)
                    ? 'acp/products/cats/' . $cdata['code']
                    : $cfragment . '-' . $cdata['code'];
                $catsCfgRoot .= ' ' . $cfragment;
            }
        }

        $cfg = $this->getCatalogActionConfig()
            . ' acp/products/' . $this->request->ot_code
            . ' ' . $catsCfgRoot;

        $this->request->addParam(array(
            'cfg' => $cfg
        ));
    }

    public static function extractCatsChain(Request $request)
    {
        $_cat = _oh('catalog');
        $cat_ot_id = $_cat->getID();
        $r = [];
        if (isset($request->pot[$cat_ot_id]) && !empty($request->pot[$cat_ot_id])) {
            $cat_iid = is_array($request->pot[$cat_ot_id])
                ? current($request->pot[$cat_ot_id])
                : $request->pot[$cat_ot_id];
            $br = Branch::get_branch(
                [
                    $_cat->getID() => [
                        'iids' => $cat_iid,
                        'aot' => $_cat->getID()
                    ]
                ],
                'up', 10, true, true, false, false
            );
            $r = Branch::build_tree($br, 2);
            $r = array_reverse($r);
            $r = $_cat->getData($r, true);
            $catData = &$r[$cat_iid];
            if ($catData['config'] && !empty($catData['config'])) {
                $cat_cfg = unserialize($catData['config']);
                if (isset($cat_cfg['ot'])) {
                    $oh = _oh($cat_cfg['ot']);
                    $request->ot_id = $oh->getID();
                    $request->ot_code = $oh->getCode();
                }
            }
        }

        return $r;
    }
}