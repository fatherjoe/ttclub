<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\View\Ageclasses;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;

class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;

    public function display($tpl = null): void
    {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');

        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('Age Classes'), 'list');
        ToolbarHelper::addNew('ageclass.add');
        ToolbarHelper::publish('ageclasses.publish', 'JTOOLBAR_PUBLISH', true);
        ToolbarHelper::unpublish('ageclasses.unpublish', 'JTOOLBAR_UNPUBLISH', true);
        ToolbarHelper::deleteList('', 'ageclasses.delete', 'JTOOLBAR_DELETE');
    }
}
