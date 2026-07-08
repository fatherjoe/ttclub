<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Site\View\Teams;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Site Teams overview view.
 *
 * Displays all teams for the current or selected half-season with
 * team photos, names, age classes, and league assignments.
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The list of team items.
     *
     * @var array
     */
    protected array $items = [];

    /**
     * The current half-season.
     *
     * @var object|null
     */
    protected ?object $halfSeason = null;

    /**
     * The current season.
     *
     * @var object|null
     */
    protected ?object $season = null;

    /**
     * All available seasons for navigation.
     *
     * @var array
     */
    protected array $seasons = [];

    /**
     * Half-seasons for the current season (for switching).
     *
     * @var array
     */
    protected array $halfSeasons = [];

    /**
     * Display the view.
     *
     * @param string|null $tpl The name of the template file to parse.
     *
     * @return void
     */
    public function display($tpl = null): void
    {
        /** @var \Fatherjoe\Component\Ttclub\Site\Model\TeamsModel $model */
        $model = $this->getModel();

        $this->items = $model->getItems() ?: [];
        $this->halfSeason = $model->getHalfSeason();
        $this->season = $model->getSeason();
        $this->seasons = $model->getSeasons();

        if ($this->season !== null) {
            $this->halfSeasons = $model->getHalfSeasons((int) $this->season->id);
        }

        parent::display($tpl);
    }
}
