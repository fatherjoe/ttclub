<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Model;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

class TeamsModel extends ListModel
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
                'team_number', 'a.team_number',
                'league_id', 'a.league_id',
                'league_name',
                'age_class_id', 'a.age_class_id',
                'age_class_name',
                'season_id', 'a.season_id',
                'season_start_year',
                'published', 'a.published',
                'club_id_source_label',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Build the query for the list of teams.
     *
     * Joins league, season, and age class tables to include their names.
     *
     * @return QueryInterface The query object.
     */
    protected function getListQuery(): QueryInterface
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.team_number'),
            $db->quoteName('a.season_id'),
            $db->quoteName('a.league_id'),
            $db->quoteName('a.age_class_id'),
            $db->quoteName('a.club_id_source'),
            $db->quoteName('a.championship_id'),
            $db->quoteName('a.group_id'),
            $db->quoteName('a.published'),
            $db->quoteName('a.created'),
            $db->quoteName('a.modified'),
            $db->quoteName('l.name', 'league_name'),
            $db->quoteName('s.start_year', 'season_start_year'),
            $db->quoteName('ac.name', 'age_class_name'),
            $db->quoteName('ci.label', 'club_id_source_label'),
        ])
            ->from($db->quoteName('#__ttclub_teams', 'a'))
            ->join('LEFT', $db->quoteName('#__ttclub_leagues', 'l') . ' ON ' . $db->quoteName('l.id') . ' = ' . $db->quoteName('a.league_id'))
            ->join('LEFT', $db->quoteName('#__ttclub_seasons', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('a.season_id'))
            ->join('LEFT', $db->quoteName('#__ttclub_age_classes', 'ac') . ' ON ' . $db->quoteName('ac.id') . ' = ' . $db->quoteName('a.age_class_id'))
            ->join('LEFT', $db->quoteName('#__ttclub_club_ids', 'ci') . ' ON ' . $db->quoteName('ci.id') . ' = ' . $db->quoteName('a.club_id_source'));

        // Filter by season
        $seasonId = $this->getState('filter.season_id');

        if (is_numeric($seasonId)) {
            $seasonId = (int) $seasonId;
            $query->where($db->quoteName('a.season_id') . ' = :season_id')
                ->bind(':season_id', $seasonId, \Joomla\Database\ParameterType::INTEGER);
        }

        // Filter by league
        $leagueId = $this->getState('filter.league_id');

        if (is_numeric($leagueId)) {
            $leagueId = (int) $leagueId;
            $query->where($db->quoteName('a.league_id') . ' = :league_id')
                ->bind(':league_id', $leagueId, \Joomla\Database\ParameterType::INTEGER);
        }

        // Filter by published state
        $published = $this->getState('filter.published');

        if (is_numeric($published)) {
            $published = (int) $published;
            $query->where($db->quoteName('a.published') . ' = :published')
                ->bind(':published', $published, \Joomla\Database\ParameterType::INTEGER);
        }

        // Filter by search term (team number)
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $search = '%' . trim($search) . '%';
            $query->where($db->quoteName('a.team_number') . ' LIKE :search')
                ->bind(':search', $search);
        }

        // Add the list ordering
        $orderCol = $this->getState('list.ordering', 'season_start_year');
        $orderDirn = $this->getState('list.direction', 'DESC');

        if ($orderCol === 'season_start_year') {
            // Default: sort by season descending, then team number ascending
            $query->order($db->escape('season_start_year') . ' ' . $db->escape($orderDirn));
            $query->order($db->quoteName('a.team_number') . ' ASC');
        } else {
            $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));
        }

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
    protected function populateState($ordering = 'season_start_year', $direction = 'DESC'): void
    {
        parent::populateState($ordering, $direction);
    }
}
