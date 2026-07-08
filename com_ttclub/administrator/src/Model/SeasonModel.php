<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

class SeasonModel extends AdminModel
{
    public $typeAlias = 'com_ttclub.season';

    public function getForm($data = [], $loadData = true): Form|false
    {
        $form = $this->loadForm(
            'com_ttclub.season',
            'season',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    protected function loadFormData(): array|object
    {
        $data = Factory::getApplication()->getUserState('com_ttclub.edit.season.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    public function getTable($name = 'Season', $prefix = 'Administrator', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * Save the season and auto-create both half-season records.
     */
    public function save($data): bool
    {
        // Save the season record
        if (!parent::save($data)) {
            return false;
        }

        $seasonId = (int) $this->getState($this->getName() . '.id');

        // Auto-create half-season records if they don't exist
        $db = $this->getDatabase();

        for ($half = 1; $half <= 2; $half++) {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__ttclub_half_seasons'))
                ->where($db->quoteName('season_id') . ' = ' . $seasonId)
                ->where($db->quoteName('half') . ' = ' . $half);

            $db->setQuery($query);

            if ((int) $db->loadResult() === 0) {
                $record = (object) [
                    'season_id' => $seasonId,
                    'half' => $half,
                ];
                $db->insertObject('#__ttclub_half_seasons', $record);
            }
        }

        return true;
    }
}
