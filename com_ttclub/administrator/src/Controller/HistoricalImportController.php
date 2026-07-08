<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Controller;

use Fatherjoe\Component\Ttclub\Administrator\Service\HistoricalImportService;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/**
 * Historical Import Controller for orchestrating the one-time bulk import
 * of all past seasons from mytischtennis.de or click-tt.de.
 *
 * Handles existing-data warning gate, data source selection, confirmation flow,
 * and triggers HistoricalImportService::executeFullImport().
 */
class HistoricalImportController extends BaseController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var string
     */
    protected $text_prefix = 'COM_TTCLUB_HISTORICAL_IMPORT';

    /**
     * Check if the current user has permission to perform historical import.
     *
     * Historical import creates records, so requires core.create permission.
     *
     * @return bool
     */
    private function hasImportPermission(): bool
    {
        $user = $this->app->getIdentity();

        if ($user->authorise('core.admin', 'com_ttclub')) {
            return true;
        }

        return $user->authorise('core.create', 'com_ttclub');
    }

    /**
     * Display the historical import view.
     *
     * @param bool   $cachable  If true, the view output will be cached.
     * @param array  $urlparams An array of safe URL parameters.
     *
     * @return static
     */
    public function display($cachable = false, $urlparams = []): static
    {
        $this->input->set('view', 'historicalimport');

        return parent::display($cachable, $urlparams);
    }

    /**
     * Trigger the historical import.
     *
     * Validates CSRF token, checks for existing data (warning gate),
     * reads data source selection and confirmation state, then delegates
     * to HistoricalImportService for execution.
     *
     * @return void
     */
    public function import(): void
    {
        // ACL check
        if (!$this->hasImportPermission()) {
            $this->app->enqueueMessage(
                Text::_('COM_TTCLUB_ERROR_NO_CREATE_PERMISSION'),
                'error'
            );
            $this->setRedirect(Route::_('index.php', false));
            $this->redirect();

            return;
        }

        // CSRF token validation
        if (!Session::checkToken()) {
            $this->setRedirect(
                Route::_('index.php?option=com_ttclub&view=historicalimport', false),
                'Invalid security token. Please try again.',
                'error'
            );
            $this->redirect();

            return;
        }

        $app = $this->app;
        $input = $app->getInput();

        // Read form inputs
        $dataSource = $input->getString('data_source', '');
        $confirmed = $input->getBool('confirmed', false);

        // Validate data source selection
        $validSources = ['mytischtennis', 'clicktt'];
        if (!in_array($dataSource, $validSources, true)) {
            $this->setRedirect(
                Route::_('index.php?option=com_ttclub&view=historicalimport', false),
                'Please select a valid data source (mytischtennis.de or click-tt.de).',
                'warning'
            );
            $this->redirect();

            return;
        }

        // Get configured club URL from component parameters
        $params = ComponentHelper::getParams('com_ttclub');
        $clubUrl = $params->get('mytischtennis_club_url', '');

        if ($clubUrl === '') {
            $this->setRedirect(
                Route::_('index.php?option=com_ttclub&view=historicalimport', false),
                'No club URL configured. Please configure the club connection in the Import settings first.',
                'error'
            );
            $this->redirect();

            return;
        }

        /** @var HistoricalImportService $service */
        $service = new HistoricalImportService(
            $this->app->bootComponent('com_ttclub')->getMVCFactory()
        );

        // Check for existing data (warning gate)
        if (!$confirmed && $service->hasExistingData()) {
            // Store selection in session for the confirmation step
            $app->setUserState('com_ttclub.historical_import.data_source', $dataSource);

            $this->setRedirect(
                Route::_('index.php?option=com_ttclub&view=historicalimport&layout=default', false),
                'Existing season or team data detected. This operation is intended for initial setup. Please confirm to proceed.',
                'warning'
            );
            $this->redirect();

            return;
        }

        // Execute the full historical import
        try {
            $result = $service->executeFullImport($clubUrl, $dataSource, true);

            // Build summary message
            $summary = sprintf(
                'Historical import completed: %d seasons, %d teams, %d players, %d roster entries, %d schedule entries created.',
                $result->seasonsCreated,
                $result->teamsCreated,
                $result->playersCreated,
                $result->rosterEntriesCreated,
                $result->scheduleEntriesCreated
            );

            $messageType = 'success';
        } catch (\Exception $e) {
            $summary = sprintf(
                'Historical import failed: %s',
                $e->getMessage()
            );
            $messageType = 'error';
        }

        // Clear stored import state
        $app->setUserState('com_ttclub.historical_import.data_source', null);

        $this->setRedirect(
            Route::_('index.php?option=com_ttclub&view=historicalimport', false),
            $summary,
            $messageType
        );
        $this->redirect();
    }

    /**
     * Get the model for this controller.
     *
     * @param string $name   The model name. Optional.
     * @param string $prefix The class prefix. Optional.
     * @param array  $config Configuration array for model. Optional.
     *
     * @return \Joomla\CMS\MVC\Model\BaseDatabaseModel The model.
     */
    public function getModel($name = 'Import', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
