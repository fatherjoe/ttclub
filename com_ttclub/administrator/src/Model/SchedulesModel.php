<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

class SchedulesModel extends ListModel
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
                'team_id', 'a.team_id',
                'season_id', 'a.season_id',
                'match_date', 'a.match_date',
                'opponent', 'a.opponent',
                'venue', 'a.venue',
                'home_away', 'a.home_away',
                'published', 'a.published',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Build the query for the list of schedules.
     *
     * Joins team and season tables to include their display names.
     * Ordered by match_date ascending by default.
     * Filterable by team_id, season_id, and published state.
     *
     * @return QueryInterface The query object.
     */
    protected function getListQuery(): QueryInterface
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.team_id'),
            $db->quoteName('a.season_id'),
            $db->quoteName('a.match_date'),
            $db->quoteName('a.match_time'),
            $db->quoteName('a.opponent'),
            $db->quoteName('a.venue'),
            $db->quoteName('a.home_away'),
            $db->quoteName('a.result'),
            $db->quoteName('a.published'),
            $db->quoteName('a.created'),
            $db->quoteName('a.modified'),
            $db->quoteName('t.team_number', 'team_number'),
            $db->quoteName('s.start_year', 'season_start_year'),
        ])
            ->from($db->quoteName('#__ttclub_schedules', 'a'))
            ->join('LEFT', $db->quoteName('#__ttclub_teams', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('a.team_id'))
            ->join('LEFT', $db->quoteName('#__ttclub_seasons', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('a.season_id'));

        // Filter by team
        $teamId = $this->getState('filter.team_id');

        if (is_numeric($teamId)) {
            $teamId = (int) $teamId;
            $query->where($db->quoteName('a.team_id') . ' = :team_id')
                ->bind(':team_id', $teamId, \Joomla\Database\ParameterType::INTEGER);
        }

        // Filter by season
        $seasonId = $this->getState('filter.season_id');

        if (is_numeric($seasonId)) {
            $seasonId = (int) $seasonId;
            $query->where($db->quoteName('a.season_id') . ' = :season_id')
                ->bind(':season_id', $seasonId, \Joomla\Database\ParameterType::INTEGER);
        }

        // Filter by published state
        $published = $this->getState('filter.published');

        if (is_numeric($published)) {
            $published = (int) $published;
            $query->where($db->quoteName('a.published') . ' = :published')
                ->bind(':published', $published, \Joomla\Database\ParameterType::INTEGER);
        }

        // Filter by search term (opponent name)
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $search = '%' . trim($search) . '%';
            $query->where($db->quoteName('a.opponent') . ' LIKE :search')
                ->bind(':search', $search);
        }

        // Add the list ordering (default: match_date ASC)
        $orderCol = $this->getState('list.ordering', 'a.match_date');
        $orderDirn = $this->getState('list.direction', 'ASC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }

    /**
     * Set the default ordering.
     *
     * @param string $ordering  An optional ordering field.
     * @param string $direction An optional direction (asc|desc).
     *
     * @return void
     */
    protected function populateState($ordering = 'a.match_date', $direction = 'ASC'): void
    {
        parent::populateState($ordering, $direction);
    }
}
