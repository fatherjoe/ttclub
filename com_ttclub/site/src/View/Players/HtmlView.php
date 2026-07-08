<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Site\View\Players;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Site Players list view.
 *
 * Displays a grid of players with images and names.
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The list of player items.
     *
     * @var array
     */
    protected array $items = [];

    /**
     * The current half-season object.
     *
     * @var object|null
     */
    protected ?object $currentHalfSeason = null;

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
        /** @var \Fatherjoe\Component\Ttclub\Site\Model\PlayersModel $model */
        $model = $this->getModel();

        $this->items = $model->getItems() ?: [];
        $this->currentHalfSeason = $model->getCurrentHalfSeason();
        $this->params = $model->getState('params');

        parent::display($tpl);
    }
}
