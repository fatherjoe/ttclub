<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\View\Players;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;

class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;
    protected array $clubIdOptions = [];

    public function display($tpl = null): void
    {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');

        /** @var \Fatherjoe\Component\Ttclub\Administrator\Model\PlayersModel $model */
        $model = $this->getModel();
        $this->clubIdOptions = $model->getClubIdOptions();

        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('Players'), 'users');
        ToolbarHelper::addNew('player.add');
        ToolbarHelper::publish('players.publish', 'JTOOLBAR_PUBLISH', true);
        ToolbarHelper::unpublish('players.unpublish', 'JTOOLBAR_UNPUBLISH', true);
        ToolbarHelper::deleteList('', 'players.delete', 'JTOOLBAR_DELETE');
    }
}
