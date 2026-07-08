<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

class ClubidsModel extends ListModel
{
    /**
     * Constructor.
     *
     * @param array $config An optional associative array of configuration settings.
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'label', 'a.label',
                'click_tt_club_id', 'a.click_tt_club_id',
                'federation', 'a.federation',
                'ordering', 'a.ordering',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param string $ordering  An optional ordering field.
     * @param string $direction An optional direction (asc/desc).
     *
     * @return void
     */
    protected function populateState($ordering = 'a.ordering', $direction = 'ASC'): void
    {
        parent::populateState($ordering, $direction);
    }

    /**
     * Build the query for the list of club IDs.
     *
     * @return QueryInterface The query object.
     */
    protected function getListQuery(): QueryInterface
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.click_tt_club_id'),
            $db->quoteName('a.legacy_club_id'),
            $db->quoteName('a.club_name'),
            $db->quoteName('a.federation'),
            $db->quoteName('a.label'),
            $db->quoteName('a.ordering'),
        ])
            ->from($db->quoteName('#__ttclub_club_ids', 'a'));

        // Filter by search
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $search = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where(
                '(' . $db->quoteName('a.label') . ' LIKE ' . $search
                . ' OR ' . $db->quoteName('a.club_name') . ' LIKE ' . $search
                . ' OR ' . $db->quoteName('a.federation') . ' LIKE ' . $search . ')'
            );
        }

        // Add the list ordering
        $orderCol = $this->getState('list.ordering', 'a.ordering');
        $orderDirn = $this->getState('list.direction', 'ASC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}
