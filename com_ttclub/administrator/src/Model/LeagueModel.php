<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

class LeagueModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var string
     */
    public $typeAlias = 'com_ttclub.league';

    /**
     * Get the form for this model.
     *
     * @param array $data     Data for the form.
     * @param bool  $loadData True if the form is to load its own data.
     *
     * @return Form|false A Form object on success, false on failure.
     */
    public function getForm($data = [], $loadData = true): Form|false
    {
        $form = $this->loadForm(
            'com_ttclub.league',
            'league',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Load the data for the form.
     *
     * @return array|object The data for the form.
     */
    protected function loadFormData(): array|object
    {
        $data = Factory::getApplication()->getUserState('com_ttclub.edit.league.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Get the table for this model.
     *
     * @param string $name    The table name. Optional.
     * @param string $prefix  The class prefix. Optional.
     * @param array  $options Configuration array for model. Optional.
     *
     * @return Table A Table object.
     */
    public function getTable($name = 'League', $prefix = 'Administrator', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }
}
