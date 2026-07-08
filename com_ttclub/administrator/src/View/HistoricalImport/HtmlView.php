<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\View\HistoricalImport;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Registry\Registry;

/**
 * HTML View class for the Historical Import admin page.
 *
 * Provides the UI for the one-time bulk historical data import
 * from mytischtennis.de or click-tt.de.
 */
class HtmlView extends BaseHtmlView
{
    /**
     * Component parameters.
     *
     * @var Registry
     */
    public Registry $params;

    /**
     * Whether existing data was detected in the database.
     *
     * @var bool
     */
    public bool $hasExistingData = false;

    /**
     * Previously selected data source (stored in session during confirmation flow).
     *
     * @var string
     */
    public string $selectedDataSource = '';

    /**
     * Whether the confirmation warning should be shown.
     *
     * @var bool
     */
    public bool $showConfirmation = false;

    /**
     * Display the view.
     *
     * @param string|null $tpl The name of the template file to parse.
     *
     * @return void
     */
    public function display($tpl = null): void
    {
        $this->params = ComponentHelper::getParams('com_ttclub');

        $app = Factory::getApplication();

        // Check if we're in the confirmation flow (data source stored in session)
        $storedDataSource = $app->getUserState('com_ttclub.historical_import.data_source', '');
        if ($storedDataSource !== '') {
            $this->selectedDataSource = $storedDataSource;
            $this->showConfirmation = true;
            $this->hasExistingData = true;
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return void
     */
    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_TTCLUB_HISTORICAL_IMPORT_TITLE'), 'download');
    }
}
