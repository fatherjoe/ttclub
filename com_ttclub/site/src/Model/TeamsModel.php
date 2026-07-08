<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Site\Model;

use Fatherjoe\Component\Ttclub\Administrator\Helper\TtclubHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

/**
 * Site Teams list model.
 *
 * Displays all teams for the current (or selected) half-season,
 * ordered by team_number ascending.
 */
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
                'team_number', 'a.team_number',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param string $ordering  An optional ordering field.
     * @param string $direction An optional direction (asc|desc).
     *
     * @return void
     */
    protected function populateState($ordering = 'a.team_number', $direction = 'ASC'): void
    {
        $app = \Joomla\CMS\Factory::getApplication();

        $halfSeasonId = $app->input->getInt('half_season_id', 0);
        $seasonId = $app->input->getInt('season_id', 0);

        $this->setState('filter.half_season_id', $halfSeasonId);
        $this->setState('filter.season_id', $seasonId);

        parent::populateState($ordering, $direction);
    }

    /**
     * Get the current or selected half-season.
     *
     * @return object|null The half-season record, or null if none exist.
     */
    public function getHalfSeason(): ?object
    {
        $halfSeasonId = (int) $this->getState('filter.half_season_id');

        if ($halfSeasonId > 0) {
            $db = $this->getDatabase();
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__ttclub_half_seasons'))
                ->where($db->quoteName('id') . ' = :halfSeasonId')
                ->bind(':halfSeasonId', $halfSeasonId, \Joomla\Database\ParameterType::INTEGER);

            $db->setQuery($query);

            return $db->loadObject();
        }

        return TtclubHelper::getCurrentHalfSeason($this->getDatabase());
    }

    /**
     * Get the season for the current half-season.
     *
     * @return object|null The season record.
     */
    public function getSeason(): ?object
    {
        $halfSeason = $this->getHalfSeason();

        if ($halfSeason === null) {
            return null;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__ttclub_seasons'))
            ->where($db->quoteName('id') . ' = :seasonId')
            ->bind(':seasonId', $halfSeason->season_id, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);

        return $db->loadObject();
    }

    /**
     * Get all seasons for navigation.
     *
     * Includes parallel seasons (e.g., cup/Pokal) alongside main seasons.
     * Ordered by start_year DESC, then label ASC (empty label sorts first).
     *
     * @return array List of season objects.
     */
    public function getSeasons(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__ttclub_seasons'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('start_year') . ' DESC, ' . $db->quoteName('label') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Get the half-seasons for a given season (for half-season switching).
     *
     * @param int $seasonId The season ID.
     *
     * @return array List of half-season objects.
     */
    public function getHalfSeasons(int $seasonId): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__ttclub_half_seasons'))
            ->where($db->quoteName('season_id') . ' = :seasonId')
            ->bind(':seasonId', $seasonId, \Joomla\Database\ParameterType::INTEGER)
            ->order($db->quoteName('half') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Build the query for the list of teams.
     *
     * Joins league, age class, and team photo for the resolved half-season.
     * Orders by team_number ascending.
     *
     * @return QueryInterface The query object.
     */
    protected function getListQuery(): QueryInterface
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $halfSeason = $this->getHalfSeason();
        $seasonId = $halfSeason !== null ? (int) $halfSeason->season_id : 0;
        $halfSeasonId = $halfSeason !== null ? (int) $halfSeason->id : 0;

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.season_id'),
            $db->quoteName('a.league_id'),
            $db->quoteName('a.age_class_id'),
            $db->quoteName('a.team_number'),
            $db->quoteName('l.name', 'league_name'),
            $db->quoteName('ac.name', 'age_class_name'),
            $db->quoteName('tp.image_path', 'team_photo'),
        ])
            ->from($db->quoteName('#__ttclub_teams', 'a'))
            ->join('LEFT', $db->quoteName('#__ttclub_leagues', 'l') . ' ON ' . $db->quoteName('l.id') . ' = ' . $db->quoteName('a.league_id'))
            ->join('LEFT', $db->quoteName('#__ttclub_age_classes', 'ac') . ' ON ' . $db->quoteName('ac.id') . ' = ' . $db->quoteName('a.age_class_id'))
            ->join('LEFT', $db->quoteName('#__ttclub_team_photos', 'tp') . ' ON ' . $db->quoteName('tp.team_id') . ' = ' . $db->quoteName('a.id') . ' AND ' . $db->quoteName('tp.half_season_id') . ' = ' . (int) $halfSeasonId)
            ->where($db->quoteName('a.published') . ' = 1')
            ->where($db->quoteName('a.season_id') . ' = :seasonId')
            ->bind(':seasonId', $seasonId, \Joomla\Database\ParameterType::INTEGER)
            ->order($db->quoteName('a.team_number') . ' ASC');

        return $query;
    }
}
