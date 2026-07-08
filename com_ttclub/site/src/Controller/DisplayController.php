<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Site\Controller;

use Joomla\CMS\MVC\Controller\BaseController;

class DisplayController extends BaseController
{
    /**
     * The default view.
     *
     * @var string
     */
    protected $default_view = 'players';

    /**
     * Display the view.
     *
     * @param bool $cachable  If true, the view output will be cached.
     * @param array $urlparams An array of safe URL parameters.
     *
     * @return static This object to support chaining.
     */
    public function display($cachable = false, $urlparams = []): static
    {
        return parent::display($cachable, $urlparams);
    }
}
