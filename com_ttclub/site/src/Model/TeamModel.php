<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Site\Model;

use Fatherjoe\Component\Ttclub\Administrator\Helper\TtclubHelper;
use Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtParser;
use Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtUrlBuilder;
use Fatherjoe\Component\Ttclub\Administrator\Service\ScheduleService;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

/**
 * Site Team detail model.
 *
 * Loads a single team with its roster for the selected half-season,
 * match schedule for the team's season, and league ranking table from click-tt.de (cached).
 */
class TeamModel extends BaseDatabaseModel
{
    /**
     * Default cache duration for ranking data in seconds (1 hour).
     */
    private const DEFAULT_RANKING_CACHE_DURATION = 3600;

    /**
     * Get the team item.
     *
     * @param int|null $id The team ID. If null, taken from input.
     *
     * @return object|null The team record with league and age class info.
     */
    public function getItem(?int $id = null): ?object
    {
        if ($id === null) {
            $id = $this->getApplication()->input->getInt('id', 0);
        }

        if ($id <= 0) {
            return null;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('a.id'),
                $db->quoteName('a.season_id'),
                $db->quoteName('a.league_id'),
                $db->quoteName('a.age_class_id'),
                $db->quoteName('a.team_number'),
                $db->quoteName('l.name', 'league_name'),
                $db->quoteName('ac.name', 'age_class_name'),
                $db->quoteName('s.start_year', 'season_start_year'),
                $db->quoteName('s.label', 'season_label'),
            ])
            ->from($db->quoteName('#__ttclub_teams', 'a'))
            ->join('LEFT', $db->quoteName('#__ttclub_leagues', 'l') . ' ON ' . $db->quoteName('l.id') . ' = ' . $db->quoteName('a.league_id'))
            ->join('LEFT', $db->quoteName('#__ttclub_age_classes', 'ac') . ' ON ' . $db->quoteName('ac.id') . ' = ' . $db->quoteName('a.age_class_id'))
            ->join('LEFT', $db->quoteName('#__ttclub_seasons', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('a.season_id'))
            ->where($db->quoteName('a.id') . ' = :teamId')
            ->where($db->quoteName('a.published') . ' = 1')
            ->bind(':teamId', $id, ParameterType::INTEGER);

        $db->setQuery($query);

        return $db->loadObject();
    }

    /**
     * Get the current or selected half-season.
     *
     * @return object|null The half-season record.
     */
    public function getHalfSeason(): ?object
    {
        $halfSeasonId = $this->getApplication()->input->getInt('half_season_id', 0);

        if ($halfSeasonId > 0) {
            $db = $this->getDatabase();
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__ttclub_half_seasons'))
                ->where($db->quoteName('id') . ' = :halfSeasonId')
                ->bind(':halfSeasonId', $halfSeasonId, ParameterType::INTEGER);

            $db->setQuery($query);

            return $db->loadObject();
        }

        return TtclubHelper::getCurrentHalfSeason($this->getDatabase());
    }

    /**
     * Get the half-seasons for the team's season (for half-season switching).
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
            ->bind(':seasonId', $seasonId, ParameterType::INTEGER)
            ->order($db->quoteName('half') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Get the team photo for the selected half-season.
     *
     * @param int $teamId       The team ID.
     * @param int $halfSeasonId The half-season ID.
     *
     * @return string|null The image path, or null if no photo exists.
     */
    public function getTeamPhoto(int $teamId, int $halfSeasonId): ?string
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('image_path'))
            ->from($db->quoteName('#__ttclub_team_photos'))
            ->where($db->quoteName('team_id') . ' = :teamId')
            ->where($db->quoteName('half_season_id') . ' = :halfSeasonId')
            ->bind(':teamId', $teamId, ParameterType::INTEGER)
            ->bind(':halfSeasonId', $halfSeasonId, ParameterType::INTEGER);

        $db->setQuery($query);
        $result = $db->loadResult();

        return $result !== null ? (string) $result : null;
    }

    /**
     * Get the roster for the team in the selected half-season.
     *
     * Returns player records with their images for the half-season.
     *
     * @param int $teamId       The team ID.
     * @param int $halfSeasonId The half-season ID.
     *
     * @return array List of player objects with name and image.
     */
    public function getRoster(int $teamId, int $halfSeasonId): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('p.id'),
                $db->quoteName('p.first_name'),
                $db->quoteName('p.last_name'),
                $db->quoteName('pi.image_path', 'player_image'),
            ])
            ->from($db->quoteName('#__ttclub_rosters', 'r'))
            ->join('INNER', $db->quoteName('#__ttclub_players', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('r.player_id'))
            ->join('LEFT', $db->quoteName('#__ttclub_player_images', 'pi') . ' ON ' . $db->quoteName('pi.player_id') . ' = ' . $db->quoteName('p.id') . ' AND ' . $db->quoteName('pi.half_season_id') . ' = :halfSeasonId2')
            ->where($db->quoteName('r.team_id') . ' = :teamId')
            ->where($db->quoteName('r.half_season_id') . ' = :halfSeasonId')
            ->where($db->quoteName('p.published') . ' = 1')
            ->bind(':teamId', $teamId, ParameterType::INTEGER)
            ->bind(':halfSeasonId', $halfSeasonId, ParameterType::INTEGER)
            ->bind(':halfSeasonId2', $halfSeasonId, ParameterType::INTEGER)
            ->order($db->quoteName('p.last_name') . ' ASC, ' . $db->quoteName('p.first_name') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Get the match schedule for the team's season.
     *
     * Ordered by match_date ascending (Requirement 15.5).
     *
     * @param int $teamId   The team ID.
     * @param int $seasonId The season ID.
     *
     * @return array List of schedule entry objects.
     */
    public function getSchedule(int $teamId, int $seasonId): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('s.id'),
                $db->quoteName('s.match_date'),
                $db->quoteName('s.match_time'),
                $db->quoteName('s.opponent'),
                $db->quoteName('s.venue'),
                $db->quoteName('s.home_away'),
                $db->quoteName('s.result'),
            ])
            ->from($db->quoteName('#__ttclub_schedules', 's'))
            ->where($db->quoteName('s.team_id') . ' = :teamId')
            ->where($db->quoteName('s.season_id') . ' = :seasonId')
            ->where($db->quoteName('s.published') . ' = 1')
            ->bind(':teamId', $teamId, ParameterType::INTEGER)
            ->bind(':seasonId', $seasonId, ParameterType::INTEGER)
            ->order($db->quoteName('s.match_date') . ' ASC, ' . $db->quoteName('s.match_time') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Get the league ranking table via the RankingService.
     *
     * Delegates to RankingService which handles caching and fetching from click-tt.de.
     * Returns null on fetch failure so the template can show a "temporarily unavailable" message.
     *
     * @param int $teamId       The team ID.
     * @param int $halfSeasonId The half-season ID.
     *
     * @return array|null Array of ranking rows, or null if unavailable.
     *                    Each row: ['position' => int, 'team_name' => string, 'matches' => int,
     *                               'wins' => int, 'draws' => int, 'losses' => int,
     *                               'points' => string, 'is_own_team' => bool]
     */
    public function getRankingTable(int $teamId, int $halfSeasonId): ?array
    {
        try {
            $rankingService = $this->getRankingService();

            return $rankingService->getRanking($teamId, $halfSeasonId);
        } catch (\Exception $e) {
            // If the service cannot be instantiated or fails, return null (Requirement 14.4)
            return null;
        }
    }

    /**
     * Default cache duration for schedule data in seconds (3 days).
     */
    private const DEFAULT_SCHEDULE_CACHE_DURATION = 259200;

    /**
     * Get the match schedule via the ScheduleService (live from click-tt.de, cached).
     *
     * Delegates to ScheduleService which handles caching and fetching from click-tt.de.
     * Returns null on fetch failure so the template can show a "temporarily unavailable" message.
     * Returns an empty array if no schedule entries exist.
     *
     * @param int $teamId       The team ID.
     * @param int $halfSeasonId The half-season ID.
     *
     * @return array|null Array of schedule entries, or null if unavailable.
     *                    Each entry: ['match_date' => string, 'match_time' => ?string,
     *                                 'home_team' => string, 'guest_team' => string, 'result' => ?string]
     */
    public function getScheduleFromService(int $teamId, int $halfSeasonId): ?array
    {
        try {
            $scheduleService = $this->getScheduleService();

            return $scheduleService->getSchedule($teamId, $halfSeasonId);
        } catch (\Exception $e) {
            // If the service cannot be instantiated or fails, return null (Requirement 14.7)
            return null;
        }
    }

    /**
     * Create and return the ScheduleService instance.
     *
     * Uses the component's configured schedule cache duration parameter.
     *
     * @return ScheduleService
     */
    protected function getScheduleService(): ScheduleService
    {
        $db = $this->getDatabase();

        $app = Factory::getApplication();
        $params = $app->getParams('com_ttclub');
        $cacheDuration = (int) $params->get('schedule_cache_duration', self::DEFAULT_SCHEDULE_CACHE_DURATION);

        $parser = new ClickTtParser();

        $federation = (string) $params->get('clicktt_federation', '');
        $clubNumber = (string) $params->get('clicktt_club_number', '');
        $clubName = (string) $params->get('clicktt_club_name', '');

        $urlBuilder = new ClickTtUrlBuilder($federation, $clubNumber, $clubName);

        return new ScheduleService($db, $parser, $urlBuilder, $cacheDuration);
    }

    /**
     * Create and return the RankingService instance.
     *
     * Uses the component's configured cache duration parameter.
     *
     * @return \Fatherjoe\Component\Ttclub\Administrator\Service\RankingService
     */
    protected function getRankingService(): \Fatherjoe\Component\Ttclub\Administrator\Service\RankingService
    {
        $db = $this->getDatabase();

        $app = Factory::getApplication();
        $params = $app->getParams('com_ttclub');
        $cacheDuration = (int) $params->get('ranking_cache_duration', self::DEFAULT_RANKING_CACHE_DURATION);

        $parser = new \Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtParser();

        return new \Fatherjoe\Component\Ttclub\Administrator\Service\RankingService($db, $parser, $cacheDuration);
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
     * Get the Application instance.
     *
     * @return \Joomla\CMS\Application\CMSApplicationInterface
     */
    private function getApplication(): \Joomla\CMS\Application\CMSApplicationInterface
    {
        return Factory::getApplication();
    }
}
