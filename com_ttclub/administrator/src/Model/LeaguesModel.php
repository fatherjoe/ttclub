<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

class LeaguesModel extends ListModel
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
                'name', 'a.name',
                'published', 'a.published',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Build the query for the list of leagues.
     *
     * Includes a COUNT of teams per league.
     *
     * @return QueryInterface The query object.
     */
    protected function getListQuery(): QueryInterface
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.name'),
            $db->quoteName('a.published'),
            $db->quoteName('a.created'),
            $db->quoteName('a.modified'),
            'COUNT(' . $db->quoteName('t.id') . ') AS ' . $db->quoteName('team_count'),
        ])
            ->from($db->quoteName('#__ttclub_leagues', 'a'))
            ->join('LEFT', $db->quoteName('#__ttclub_teams', 't') . ' ON ' . $db->quoteName('t.league_id') . ' = ' . $db->quoteName('a.id'))
            ->group($db->quoteName('a.id'));

        // Filter by published state
        $published = $this->getState('filter.published');

        if (is_numeric($published)) {
            $query->where($db->quoteName('a.published') . ' = ' . (int) $published);
        }

        // Filter by search
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $search = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where($db->quoteName('a.name') . ' LIKE ' . $search);
        }

        // Add the list ordering
        $orderCol = $this->getState('list.ordering', 'a.name');
        $orderDirn = $this->getState('list.direction', 'ASC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}
