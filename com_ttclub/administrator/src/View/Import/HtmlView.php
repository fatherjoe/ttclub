<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\View\Import;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    protected ?Form $form = null;
    protected $params;
    protected ?object $results = null;

    public function display($tpl = null): void
    {
        $this->params = ComponentHelper::getParams('com_ttclub');

        // Load form using the component's forms directory
        Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_ttclub/forms');
        $this->form = Form::getInstance('com_ttclub.import', 'import', ['control' => 'jform']);

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('Import'), 'download');
    }
}
