<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\View\Clubids;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    /**
     * The list of club ID items.
     *
     * @var array
     */
    protected $items;

    /**
     * The pagination object.
     *
     * @var \Joomla\CMS\Pagination\Pagination
     */
    protected $pagination;

    /**
     * The active state filter.
     *
     * @var \Joomla\Registry\Registry
     */
    protected $state;

    /**
     * Display the view.
     *
     * @param string $tpl The name of the template file to parse.
     *
     * @return void
     */
    public function display($tpl = null): void
    {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');

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
        ToolbarHelper::title(Text::_('COM_TTCLUB_CLUBIDS_TITLE'), 'list');
        ToolbarHelper::addNew('clubid.add');
        ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'clubids.delete');
    }
}
