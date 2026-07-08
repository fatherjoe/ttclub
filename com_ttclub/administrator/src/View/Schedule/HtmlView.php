<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\View\Schedule;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    /**
     * The form object.
     *
     * @var \Joomla\CMS\Form\Form
     */
    protected $form;

    /**
     * The schedule item being edited.
     *
     * @var object
     */
    protected $item;

    /**
     * Display the view.
     *
     * @param string $tpl The name of the template file to parse.
     *
     * @return void
     */
    public function display($tpl = null): void
    {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

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
        Factory::getApplication()->input->set('hidemainmenu', true);

        $isNew = ($this->item->id == 0);

        ToolbarHelper::title(
            Text::_($isNew ? 'COM_TTCLUB_SCHEDULE_NEW' : 'COM_TTCLUB_SCHEDULE_EDIT'),
            'calendar'
        );

        ToolbarHelper::apply('schedule.apply');
        ToolbarHelper::save('schedule.save');
        ToolbarHelper::cancel('schedule.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }
}
