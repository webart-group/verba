<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 14.09.19
 * Time: 19:48
 */

namespace Verba\Mod\Acp\Block\Product;

use Verba\Block\Json;
use Verba\Branch;
use Verba\Mod\Acp\Router\Contracts\CatalogActionConfigInterface;
use Verba\Mod\Acp\Router\Contracts\CatalogActionConfigTrait;
use function Verba\_oh;

class ProductAcpForm extends Json implements CatalogActionConfigInterface
{

    use CatalogActionConfigTrait;
    function isCatalogActionConfigApplicable()
    {
        return true;
    }

    function get()
    {
        // TODO: Implement get() method.
    }

    function build()
    {
        $_catalog = _oh('catalog');
        $cat_ot_id = $_catalog->getID();
        $cfg = $this->request->asArray();
        list($pot, $piid) = $this->request->getFirstParent();

        // try to include subcatalogs if ony one catalog is as a parent
        if (count($cfg['pot']) == 1
            && count($cfg['pot'][$cat_ot_id]) == 1) {
            $br = Branch::get_branch(array($cat_ot_id => array('aot' => $cat_ot_id, 'iids' => array($piid))), 'down', 100, false);
            $cfg['pot'][$cat_ot_id] = $br['handled'][$cat_ot_id];
        }

        $oh = _oh($cfg['ot_id']);

        $form = $oh->initForm($cfg);
        $this->content = $form->makeForm();
        return $this->content;
    }
}