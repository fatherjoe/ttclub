<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Site\View\Player;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Site Player detail view.
 *
 * Displays a single player's publicly visible fields and image.
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The player item.
     *
     * @var object|null
     */
    protected ?object $item = null;

    /**
     * The list of visible fields.
     *
     * @var array
     */
    protected array $visibleFields = [];

    /**
     * Component parameters.
     *
     * @var \Joomla\Registry\Registry|null
     */
    protected $params = null;

    /**
     * Display the view.
     *
     * @param string|null $tpl The name of the template file to parse.
     *
     * @return void
     */
    public function display($tpl = null): void
    {
        /** @var \Fatherjoe\Component\Ttclub\Site\Model\PlayerModel $model */
        $model = $this->getModel();

        $this->item = $model->getItem();
        $this->visibleFields = $model->getVisibleFields();

        $app = \Joomla\CMS\Factory::getApplication();
        $this->params = $app->getParams('com_ttclub');

        parent::display($tpl);
    }
}
