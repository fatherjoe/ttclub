<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Controller;

use Fatherjoe\Component\Ttclub\Administrator\Model\ImportModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/**
 * Import Controller for orchestrating the import workflow from mytischtennis.de.
 *
 * Handles data type selection, CSRF validation, and redirects with success/error messages.
 */
class ImportController extends BaseController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var string
     */
    protected $text_prefix = 'COM_TTCLUB_IMPORT';

    /**
     * Check if the current user has permission to perform import operations.
     *
     * Import creates records, so requires core.create permission.
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
     * Trigger the import with selected data types.
     *
     * Validates CSRF token, reads selected import types from the request,
     * and delegates to the ImportModel for execution.
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
                Route::_('index.php?option=com_ttclub&view=import', false),
                'Invalid security token. Please try again.',
                'error'
            );
            $this->redirect();

            return;
        }

        $app = $this->app;
        $input = $app->getInput();

        // Get selected import types from the form
        $types = $input->get('import_types', [], 'array');
        $seasonId = $input->getInt('season_id', 0);
        $halfSeasonId = $input->getInt('half_season_id', 0);
        $confirmed = $input->getBool('confirmed', false);

        // Validate that at least one type is selected
        $validTypes = ['players', 'rosters', 'schedules'];
        $types = array_intersect($types, $validTypes);

        if (empty($types)) {
            $this->setRedirect(
                Route::_('index.php?option=com_ttclub&view=import', false),
                'Please select at least one data type to import.',
                'warning'
            );
            $this->redirect();

            return;
        }

        // Validate season selection (skip for full import - season 0 means discover all)
        if ($seasonId === 0 && empty($types)) {
            $this->setRedirect(
                Route::_('index.php?option=com_ttclub&view=import', false),
                'Please select a season or data types to import.',
                'warning'
            );
            $this->redirect();

            return;
        }

        // Validate half-season for roster imports (skip for full import)
        if ($seasonId !== 0 && in_array('rosters', $types, true) && $halfSeasonId === 0) {
            $this->setRedirect(
                Route::_('index.php?option=com_ttclub&view=import', false),
                'Please select a half-season for roster import.',
                'warning'
            );
            $this->redirect();

            return;
        }

        /** @var ImportModel $model */
        $model = $this->getModel('Import', 'Administrator');

        // Run the import
        $results = $model->runImport($types, $seasonId, $halfSeasonId, true);

        // Build summary messages
        $messages = [];
        $hasErrors = false;

        foreach ($results as $type => $result) {
            if (!$result->success) {
                $hasErrors = true;
                $messages[] = sprintf(
                    '%s import failed: %s',
                    ucfirst($type),
                    $result->errorMessage ?? 'Unknown error'
                );
            } else {
                $messages[] = sprintf(
                    '%s: %d created, %d updated, %d unchanged',
                    ucfirst($type),
                    $result->created,
                    $result->updated,
                    $result->unchanged
                );
            }
        }

        // Clear stored import state
        $app->setUserState('com_ttclub.import.types', null);
        $app->setUserState('com_ttclub.import.season_id', null);
        $app->setUserState('com_ttclub.import.half_season_id', null);

        $messageType = $hasErrors ? 'error' : 'success';
        $summary = implode(' | ', $messages);

        $this->setRedirect(
            Route::_('index.php?option=com_ttclub&view=import', false),
            $summary,
            $messageType
        );
        $this->redirect();
    }

    /**
     * Import data from a pasted click-tt.de URL (parallel season import).
     *
     * Validates the URL, parses it, and imports teams/rosters from the linked page.
     * No season or half-season selection is required — these are derived from the URL/page content.
     *
     * @return void
     */
    public function importUrl(): void
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
                Route::_('index.php?option=com_ttclub&view=import', false),
                'Invalid security token. Please try again.',
                'error'
            );
            $this->redirect();

            return;
        }

        $input = $this->app->getInput();
        $clickTtUrl = trim($input->getString('click_tt_url', ''));

        // Validate that a URL was provided
        if ($clickTtUrl === '') {
            $this->setRedirect(
                Route::_('index.php?option=com_ttclub&view=import', false),
                'Please enter a click-tt.de URL.',
                'warning'
            );
            $this->redirect();

            return;
        }

        // Basic URL format validation
        if (!str_contains($clickTtUrl, 'click-tt.de')) {
            $this->setRedirect(
                Route::_('index.php?option=com_ttclub&view=import', false),
                'The URL does not appear to be a valid click-tt.de URL.',
                'error'
            );
            $this->redirect();

            return;
        }

        /** @var ImportModel $model */
        $model = $this->getModel('Import', 'Administrator');

        $result = $model->runUrlImport($clickTtUrl);

        if (!$result->success) {
            $this->setRedirect(
                Route::_('index.php?option=com_ttclub&view=import', false),
                'URL import failed: ' . ($result->errorMessage ?? 'Unknown error'),
                'error'
            );
        } else {
            $summary = sprintf(
                'URL import completed: %d created, %d updated, %d unchanged',
                $result->created,
                $result->updated,
                $result->unchanged
            );

            $this->setRedirect(
                Route::_('index.php?option=com_ttclub&view=import', false),
                $summary,
                'success'
            );
        }

        $this->redirect();
    }

    /**
     * Validate the club connection.
     *
     * Tests that the configured club identifier returns valid data
     * from mytischtennis.de.
     *
     * @return void
     */
    public function validate(): void
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
                Route::_('index.php?option=com_ttclub&view=import', false),
                'Invalid security token. Please try again.',
                'error'
            );
            $this->redirect();

            return;
        }

        $input = $this->app->getInput();
        $clubIdentifier = $input->getString('club_identifier', '');

        if ($clubIdentifier === '') {
            $this->setRedirect(
                Route::_('index.php?option=com_ttclub&view=import', false),
                'Please enter a club identifier or URL.',
                'warning'
            );
            $this->redirect();

            return;
        }

        /** @var ImportModel $model */
        $model = $this->getModel('Import', 'Administrator');

        $isValid = $model->validateConnection($clubIdentifier);

        if ($isValid) {
            $this->setRedirect(
                Route::_('index.php?option=com_ttclub&view=import', false),
                'Club connection validated successfully. The club page is reachable and contains valid data.',
                'success'
            );
        } else {
            $this->setRedirect(
                Route::_('index.php?option=com_ttclub&view=import', false),
                'Club connection validation failed. Please check the club identifier or URL and try again.',
                'error'
            );
        }

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
