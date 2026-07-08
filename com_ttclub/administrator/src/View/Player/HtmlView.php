<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\View\Player;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\Form;

class HtmlView extends BaseHtmlView
{
    protected Form|false $form;
    protected object $item;

    public function display($tpl = null): void
    {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $isNew = ($this->item->id == 0);

        ToolbarHelper::title(
            Text::_($isNew ? 'New Player' : 'Edit Player'),
            'pencil-alt'
        );

        ToolbarHelper::apply('player.apply');
        ToolbarHelper::save('player.save');
        ToolbarHelper::cancel('player.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }
}
