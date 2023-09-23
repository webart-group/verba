<?php
namespace Verba\Mod\Product\Block;

use Exception;
use Verba\Block\Json;
use Verba\Lang;
use Verba\Mod\Image;
use Verba\Mod\Product;
use Verba\Mod\Review\Block\PublicList as ReviewPublicList;
use Verba\Mod\Seo;
use Verba\Model;
use Verba\QueryMaker;
use function Verba\_oh;
use function Verba\_mod;
use function Verba\reductionToCurrency;

class ProductBasicShow extends Json
{
    public $item;
    /**
     * @var \Verba\Model
     */
    public $oh;
    public $iid;
    public $cats;
    public $cdata;
    public $urlClear;

    function route()
    {
        /**
         * @var Product $mProduct
         */
        $this->oh = _oh($this->request->ot_id);
        $this->iid = $this->request->iid;
        $mProduct = _mod('product');
        $this->cats = $mProduct->getCatsByProduct($this->oh, $this->request->iid);

        if(!$this->cats){
            throw new Exception('Product categories not found');
        }

        $this->cdata = current($this->cats);

        $this->addItems(['reviews' => new ReviewPublicList($this->request)]);

        return $this;
    }

    function prepare()
    {
        $this->item = $this->oh->getData($this->iid, 1);

        if (!$this->item) {
            throw new Exception(Lang::get('products not_found'));
        }
    }

    function build()
    {
        $this->urlClear = new \Verba\Url(Seo::idToSeoStr($this->item));

        $cur = _mod('cart')->getCurrency();

        $productData = [
            'id' => $this->item[$this->oh->getPAC()],
            'ot_id' => $this->item['ot_id'],
            'title' => $this->item['title'],
            'articul' => $this->item['articul'],
            'country' => $this->item['country__value'],
            'description' => $this->item['description'],
            'price' => reductionToCurrency($this->item['price']),
            'price_sign' => $cur->short,
            'catalog_id' => $this->cdata['id'],
            'catalog_title' => $this->cdata['title'],
            'catalog_url' => $this->cdata['fullcode'],
            'extended_params' => $this->parseExtraFields(),
        ];

        $catUrl = array();
        foreach ($this->cats as $ccat) {
            $catUrl[] = $ccat['code'];
        }

        // images
        $this->item['_images'] = $this->loadImages();
        $variantsImages = [];

        self::imagesByVariants($variantsImages, $this->iid, $this->item);

        if (is_array($this->item['_variants'])) {
            foreach ($this->item['_variants'] as $cvar) {
                $cvarId = $cvar[$this->oh->getPAC()];
                self::imagesByVariants($variantsImages, $this->iid, $cvar);
            }
        }

        /**
         * @var Image $mImage
         */
        $mImage = _mod('image');
        $imgConfig = $mImage::getImageConfig('product');

        foreach ($variantsImages as $ckey => $images) {
            foreach($images as $i => $image) {
                $variantsImages[$ckey][$i] = [
                    'src' => $imgConfig->getFullUrl($image['image']),
                    'alt' => $image['alt']
                ];
            }
        }

        $productData['images'] = $variantsImages;

        return $this->content = [
            'product' => $productData,
            'reviews' => $this->items['reviews']->getContent(),
        ];
    }

    function parseExtraFields()
    {
        if (!($eparams = $this->oh->getAttrsByBehaviors('custom'))) {
            return [];
        }
        $eparams = \Verba\Configurable::substNumIdxAsStringValues($eparams);
        $r = [];
        foreach ($eparams as $pkey => $pcfg) {
            if (!isset($this->item[$pkey])) {
                continue;
            }
            $A = $this->oh->A($pkey);
            $acode = $A->getCode();

            if (isset($pcfg['title'])) {
                $ptitle = Lang::get($pcfg['title']);
            } else {
                $ptitle = $A->getTitle();
            }

            if ($A->isPredefined()) {
                // is multiple select
                if ($A->data_type == 'multiple' && !empty($this->item[$pkey])) {
                    $pvalue_items = explode('#', $this->item[$pkey . '__value']);
                    $pvalue = [];
                    foreach ($pvalue_items as $cpvalue) {
                        $vd = explode(':', $cpvalue);
                        $pvalue[] = $vd[1];
                    }
                    $pvalue = implode(', ', $pvalue);

                    // siimple select
                } else {
                    $pvalue = $this->item[$pkey . '__value'];
                }
            } else {
                $pvalue = $this->item[$pkey];
            }
            if ($pvalue === false || $pvalue === null || is_string($pvalue) && !is_numeric($pvalue) && !$pvalue) {
                continue;
            }
            $r[$acode] = [$ptitle, $pvalue];
        }
        return $r;
    }

    static function imagesByVariants(&$colorsImages, $key, $item, $addLinkedImages = true)
    {
        $addLinkedImages = (bool)$addLinkedImages;

        if (!isset($colorsImages[$key]) || !is_array($colorsImages[$key])) {
            $colorsImages[$key] = [];
        }

        if ($item['picture']) {
            $colorsImages[$key][] = [
                'image' => basename($item['picture']),
                'cfg' => $item['_picture_config'],
                'alt' => isset($item['color']) && !empty($item['color'])
                    ? $item['title'] . ' ' . $item['color']
                    : $item['title']
            ];
        }

        if (!$addLinkedImages || !is_array($item['_images']) || !count($item['_images'])) {
            return;
        }
        foreach ($item['_images'] as $img) {
            if (!$img['storage_file_name'] || !$img['_storage_file_name_config']) {
                continue;
            }

            $colorsImages[$key][] = [
                'image' => $img['storage_file_name'],
                'cfg' => $img['_storage_file_name_config'],
                'alt' => isset($item['color']) && !empty($item['color'])
                    ? $item['title'] . ' ' . $item['color']
                    : $item['title'],
            ];
        }
    }

    function loadImages()
    {
        /**
         * @var Image $mImage
         * @var Model $_image
         */

        $_image = _oh('image');

        $mImage = _mod('image');

        $qm = new QueryMaker($_image);

        $qm->addWhere(1, 'active');
        $qm->addOrder(['priority' => 'd']);

        $qm->addConditionByLinkedOT($this->oh, $this->iid);

        $q = $qm->getQuery();

        $sqlr = $qm->run();

        if(!$sqlr || !$sqlr->getNumRows()){
            return [];
        }
        $r = [];
        while($row = $sqlr->fetchRow()) {
            $r[] = $row;
        }

        return $r;
    }
}
