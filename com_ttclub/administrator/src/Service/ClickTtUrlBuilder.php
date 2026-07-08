<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Service;

/**
 * Builds URLs for the mytischtennis.de / click-tt integration.
 *
 * URL pattern (mytischtennis.de): https://www.mytischtennis.de/click-tt/{federation}/{season}/verein/{clubId}/{clubName}/{page}/
 * URL pattern (click-tt.de): https://{federation}.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/{action}?club={clubId}
 *
 * Season format in URL: "YY--YY" (e.g., "25--26" for season 2025/26)
 *
 * The federation can be set at construction time (for backward compatibility) or passed
 * per-call to support multiple club IDs with different federations (Requirement 16.9).
 */
class ClickTtUrlBuilder
{
    private const BASE_URL = 'https://www.mytischtennis.de/click-tt';
    private const CLICKTT_BASE_TEMPLATE = 'https://%s.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/%s?club=%d';

    public function __construct(
        private readonly string $federation,
        private readonly string $clubNumber,
        private readonly string $clubName,
    ) {}

    /**
     * Build a click-tt.de URL with per-call federation support.
     *
     * @param string     $federation  The federation abbreviation (e.g., "BaTTV", "WTTV")
     * @param string     $action      The click-tt.de action endpoint (e.g., "clubPools", "clubTeams")
     * @param int        $clubId      The click-tt.de club ID
     * @param array      $extraParams Additional query parameters
     */
    public function buildClickTtUrl(string $federation, string $action, int $clubId, array $extraParams = []): string
    {
        $base = sprintf(
            self::CLICKTT_BASE_TEMPLATE,
            strtolower($federation),
            $action,
            $clubId
        );
        if ($extraParams) {
            $base .= '&' . http_build_query($extraParams);
        }
        return $base;
    }

    /**
     * Build clubPools URL with per-call federation.
     */
    public function clubPools(string $federation, int $clubId, array $params = []): string
    {
        return $this->buildClickTtUrl($federation, 'clubPools', $clubId, $params);
    }

    /**
     * Build clubPortraitTT URL with per-call federation.
     */
    public function clubPortraitTT(string $federation, int $clubId, array $params = []): string
    {
        return $this->buildClickTtUrl($federation, 'clubPortraitTT', $clubId, $params);
    }

    /**
     * Build clubTeams URL with per-call federation.
     */
    public function clubTeams(string $federation, int $clubId, array $params = []): string
    {
        return $this->buildClickTtUrl($federation, 'clubTeams', $clubId, $params);
    }

    /**
     * Build the teams (Mannschaften) page URL for a season (mytischtennis.de).
     */
    public function getTeamsUrl(int $startYear): string
    {
        return $this->buildClubUrl($startYear, 'mannschaften');
    }

    /**
     * Build the schedule (Spielplan) page URL for a season (mytischtennis.de).
     */
    public function getScheduleUrl(int $startYear): string
    {
        return $this->buildClubUrl($startYear, 'spielplan');
    }

    /**
     * Build the player reports (Bilanzen) page URL for a season (mytischtennis.de).
     */
    public function getBilanzenUrl(int $startYear): string
    {
        return $this->buildClubUrl($startYear, 'bilanzen/gesamt');
    }

    /**
     * Build the roster (Meldungen) page URL for a season (mytischtennis.de).
     */
    public function getMeldungenUrl(int $startYear): string
    {
        return $this->buildClubUrl($startYear, 'meldungen');
    }

    /**
     * Build the club info page URL for a season (mytischtennis.de).
     */
    public function getClubInfoUrl(int $startYear): string
    {
        return $this->buildClubUrl($startYear, 'info');
    }

    /**
     * Build the club meetings endpoint URL (POST target).
     *
     * Pattern: https://{federation}.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubMeetings
     *
     * @param string $federation The federation abbreviation (e.g., "BaTTV", "WTTV")
     * @return string The clubMeetings POST URL
     */
    public function clubMeetings(string $federation): string
    {
        return sprintf(
            'https://%s.click-tt.de/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubMeetings',
            strtolower($federation)
        );
    }

    /**
     * Build the POST body for clubMeetings request.
     *
     * @param int $clickTtClubId The resolved click-tt internal club ID
     * @param int $startYear Season start year (date range: 01.08.{startYear} to 31.07.{startYear+1})
     * @return string URL-encoded POST body
     */
    public function clubMeetingsBody(int $clickTtClubId, int $startYear): string
    {
        return http_build_query([
            'searchTimeRange' => '13-6976',
            'searchType' => '1',
            'searchTimeRangeFrom' => sprintf('01.08.%d', $startYear),
            'searchTimeRangeTo' => sprintf('31.07.%d', $startYear + 1),
            'selectedTeamId' => 'WONoSelectionString',
            'club' => $clickTtClubId,
            'searchMeetings' => 'Suchen',
        ]);
    }

    /**
     * Get the base club URL for a specific season and page (mytischtennis.de).
     */
    private function buildClubUrl(int $startYear, string $page): string
    {
        $seasonSlug = $this->buildSeasonSlug($startYear);

        return sprintf(
            '%s/%s/%s/verein/%s/%s/%s/',
            self::BASE_URL,
            $this->federation,
            $seasonSlug,
            $this->clubNumber,
            $this->clubName,
            $page
        );
    }

    /**
     * Convert a start year to the URL season slug format.
     * e.g., 2025 → "25--26", 2026 → "26--27"
     */
    private function buildSeasonSlug(int $startYear): string
    {
        $startShort = $startYear % 100;
        $endShort = ($startYear + 1) % 100;

        return sprintf('%02d--%02d', $startShort, $endShort);
    }

    /**
     * Create from component params.
     */
    public static function fromParams(\Joomla\Registry\Registry $params): ?self
    {
        $federation = $params->get('clicktt_federation', '');
        $clubNumber = $params->get('clicktt_club_number', '');
        $clubName = $params->get('clicktt_club_name', '');

        if ($federation === '' || $clubNumber === '' || $clubName === '') {
            return null;
        }

        return new self($federation, $clubNumber, $clubName);
    }
}
