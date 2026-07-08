<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

/**
 * Site Schedule model.
 *
 * Provides schedule data for a team, grouped into upcoming and past matches.
 * Upcoming matches (date >= today) are sorted ascending.
 * Past matches (date < today) are sorted descending (most recent first).
 */
class ScheduleModel extends BaseDatabaseModel
{
    /**
     * Get upcoming matches for the given team and season.
     *
     * Upcoming matches have match_date >= today, sorted ascending by date.
     *
     * @param int $teamId   The team ID.
     * @param int $seasonId The season ID.
     *
     * @return array List of upcoming schedule entries.
     */
    public function getUpcomingMatches(int $teamId, int $seasonId): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $today = Factory::getDate()->format('Y-m-d');

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.match_date'),
            $db->quoteName('a.match_time'),
            $db->quoteName('a.opponent'),
            $db->quoteName('a.venue'),
            $db->quoteName('a.home_away'),
            $db->quoteName('a.result'),
        ])
            ->from($db->quoteName('#__ttclub_schedules', 'a'))
            ->where($db->quoteName('a.team_id') . ' = :team_id')
            ->where($db->quoteName('a.season_id') . ' = :season_id')
            ->where($db->quoteName('a.published') . ' = 1')
            ->where($db->quoteName('a.match_date') . ' >= :today')
            ->bind(':team_id', $teamId, ParameterType::INTEGER)
            ->bind(':season_id', $seasonId, ParameterType::INTEGER)
            ->bind(':today', $today)
            ->order($db->quoteName('a.match_date') . ' ASC, ' . $db->quoteName('a.match_time') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Get past matches for the given team and season.
     *
     * Past matches have match_date < today, sorted descending (most recent first).
     *
     * @param int $teamId   The team ID.
     * @param int $seasonId The season ID.
     *
     * @return array List of past schedule entries.
     */
    public function getPastMatches(int $teamId, int $seasonId): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $today = Factory::getDate()->format('Y-m-d');

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.match_date'),
            $db->quoteName('a.match_time'),
            $db->quoteName('a.opponent'),
            $db->quoteName('a.venue'),
            $db->quoteName('a.home_away'),
            $db->quoteName('a.result'),
        ])
            ->from($db->quoteName('#__ttclub_schedules', 'a'))
            ->where($db->quoteName('a.team_id') . ' = :team_id')
            ->where($db->quoteName('a.season_id') . ' = :season_id')
            ->where($db->quoteName('a.published') . ' = 1')
            ->where($db->quoteName('a.match_date') . ' < :today')
            ->bind(':team_id', $teamId, ParameterType::INTEGER)
            ->bind(':season_id', $seasonId, ParameterType::INTEGER)
            ->bind(':today', $today)
            ->order($db->quoteName('a.match_date') . ' DESC, ' . $db->quoteName('a.match_time') . ' DESC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Get all available seasons for the season selector.
     *
     * Includes parallel seasons (e.g., cup/Pokal) alongside main seasons.
     * Ordered by start_year DESC, then label ASC (empty label sorts first).
     *
     * @return array List of season objects with id, start_year, and label.
     */
    public function getSeasons(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            $db->quoteName('id'),
            $db->quoteName('start_year'),
            $db->quoteName('label'),
        ])
            ->from($db->quoteName('#__ttclub_seasons'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('start_year') . ' DESC, ' . $db->quoteName('label') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Get the current season (most recent by name).
     *
     * @return int|null The current season ID, or null if none found.
     */
    public function getCurrentSeasonId(): ?int
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select($db->quoteName('id'))
            ->from($db->quoteName('#__ttclub_seasons'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('start_year') . ' DESC');

        $db->setQuery($query, 0, 1);
        $result = $db->loadResult();

        return $result !== null ? (int) $result : null;
    }

    /**
     * Get team information by ID.
     *
     * @param int $teamId The team ID.
     *
     * @return object|null The team record or null if not found.
     */
    public function getTeam(int $teamId): ?object
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            $db->quoteName('t.id'),
            $db->quoteName('t.team_number'),
            $db->quoteName('t.season_id'),
            $db->quoteName('l.name', 'league_name'),
        ])
            ->from($db->quoteName('#__ttclub_teams', 't'))
            ->join('LEFT', $db->quoteName('#__ttclub_leagues', 'l') . ' ON ' . $db->quoteName('l.id') . ' = ' . $db->quoteName('t.league_id'))
            ->where($db->quoteName('t.id') . ' = :team_id')
            ->where($db->quoteName('t.published') . ' = 1')
            ->bind(':team_id', $teamId, ParameterType::INTEGER);

        $db->setQuery($query);

        return $db->loadObject() ?: null;
    }
}
