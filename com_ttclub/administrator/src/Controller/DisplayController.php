<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

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
        $user = $this->app->getIdentity();

        // core.admin bypasses all checks
        if (!$user->authorise('core.admin', 'com_ttclub')) {
            // core.manage is required to access the backend component
            if (!$user->authorise('core.manage', 'com_ttclub')) {
                $this->app->enqueueMessage(
                    Text::_('COM_TTCLUB_ERROR_NO_MANAGE_PERMISSION'),
                    'error'
                );
                $this->setRedirect(Route::_('index.php', false));

                return $this;
            }
        }

        return parent::display($cachable, $urlparams);
    }
}
