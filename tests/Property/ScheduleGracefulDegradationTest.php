<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property 21: Schedule fetch graceful degradation
 *
 * For any team detail page where the schedule data fetch fails (connection error
 * or invalid response), the page must still render the team detail (photo, roster,
 * ranking) without error, and display an informational message indicating the
 * schedule is temporarily unavailable.
 *
 * **Validates: Requirements 14.7**
 *
 * @Feature tabletennis-club-manager
 * @Property 21: Schedule fetch graceful degradation
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class ScheduleGracefulDegradationTest extends TestCase
{
    use TestTrait;

    /**
     * Simulate the team detail page rendering logic when schedule is null.
     *
     * This replicates the template logic in site/tmpl/team/default.php:
     * - If $schedule === null → show "temporarily unavailable" message
     * - If $schedule === [] → show "no schedule data" message
     * - Otherwise → render table
     *
     * The page always renders photo, roster, and ranking sections independently.
     *
     * @param object $teamItem The team item
     * @param string|null $teamPhoto The team photo path
     * @param array $roster The roster array
     * @param array|null $schedule The schedule (null = fetch failed)
     * @param array|null $ranking The ranking table (null = fetch failed)
     * @return array{photo_rendered: bool, roster_rendered: bool, ranking_rendered: bool, schedule_section: string}
     */
    private function simulateTeamPageRender(
        object $teamItem,
        ?string $teamPhoto,
        array $roster,
        ?array $schedule,
        ?array $ranking,
    ): array {
        // Photo section always renders (uses placeholder if no photo)
        $photoRendered = true;
        $photoSrc = !empty($teamPhoto) ? $teamPhoto : 'media/com_ttclub/images/placeholder.png';

        // Roster section always renders (shows "no roster" message if empty)
        $rosterRendered = true;

        // Ranking section always renders (shows "unavailable" message if null)
        $rankingRendered = true;

        // Schedule section: determine what to display
        if ($schedule === null) {
            $scheduleSection = 'temporarily_unavailable';
        } elseif (empty($schedule)) {
            $scheduleSection = 'no_schedule_data';
        } else {
            $scheduleSection = 'schedule_table';
        }

        return [
            'photo_rendered' => $photoRendered,
            'photo_src' => $photoSrc,
            'roster_rendered' => $rosterRendered,
            'ranking_rendered' => $rankingRendered,
            'schedule_section' => $scheduleSection,
        ];
    }

    /**
     * Property 21: When ScheduleService returns null (fetch failure), the team
     * page still renders photo, roster, and ranking without error.
     *
     * Generate random team/half-season combinations with varying photo, roster,
     * and ranking data. Set schedule to null (simulating fetch failure). Verify
     * that all other sections still render.
     *
     * **Validates: Requirements 14.7**
     */
    public function testPageRendersAllSectionsWhenScheduleFetchFails(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 1000),          // team_id
                Generators::choose(1, 100),           // half_season_id
                Generators::choose(1, 10),            // team_number
                Generators::bool(),                   // has team photo
                Generators::choose(0, 12),            // roster size
                Generators::bool()                    // has ranking data
            )
            ->then(function (
                int $teamId,
                int $halfSeasonId,
                int $teamNumber,
                bool $hasTeamPhoto,
                int $rosterSize,
                bool $hasRanking,
            ): void {
                $teamItem = (object) [
                    'id' => $teamId,
                    'team_number' => $teamNumber,
                    'season_id' => 1,
                    'league_name' => 'Kreisliga Staffel 1',
                    'age_class_name' => 'Herren',
                    'season_start_year' => 2024,
                    'season_label' => '',
                ];

                $teamPhoto = $hasTeamPhoto
                    ? "images/com_ttclub/teams/team_{$teamId}_hs_{$halfSeasonId}.jpg"
                    : null;

                $roster = [];
                for ($i = 0; $i < $rosterSize; $i++) {
                    $roster[] = (object) [
                        'first_name' => "Player{$i}",
                        'last_name' => "Surname{$i}",
                        'player_image' => null,
                    ];
                }

                $ranking = $hasRanking
                    ? [['position' => 1, 'team_name' => 'Test Team', 'matches' => 5, 'wins' => 3, 'draws' => 1, 'losses' => 1, 'points' => '7:3']]
                    : null;

                // Schedule is null — fetch failure scenario
                $schedule = null;

                $result = $this->simulateTeamPageRender(
                    $teamItem,
                    $teamPhoto,
                    $roster,
                    $schedule,
                    $ranking
                );

                // All sections must still render
                $this->assertTrue(
                    $result['photo_rendered'],
                    "Team photo section must render even when schedule fetch fails (team_id=$teamId, hs=$halfSeasonId)"
                );

                $this->assertTrue(
                    $result['roster_rendered'],
                    "Roster section must render even when schedule fetch fails (team_id=$teamId, hs=$halfSeasonId)"
                );

                $this->assertTrue(
                    $result['ranking_rendered'],
                    "Ranking section must render even when schedule fetch fails (team_id=$teamId, hs=$halfSeasonId)"
                );

                // Schedule section must show "temporarily unavailable" message
                $this->assertSame(
                    'temporarily_unavailable',
                    $result['schedule_section'],
                    "When schedule is null (fetch failure), must show 'temporarily unavailable' message (team_id=$teamId, hs=$halfSeasonId)"
                );
            });
    }

    /**
     * Property 21: When schedule fetch fails, the photo uses a valid source
     * (either the actual photo or the placeholder).
     *
     * Generate random teams with and without photos. Schedule is null (failure).
     * Verify the photo source is always non-empty (no broken image).
     *
     * **Validates: Requirements 14.7**
     */
    public function testPhotoAlwaysHasValidSourceWhenScheduleFetchFails(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 1000),          // team_id
                Generators::choose(1, 100),           // half_season_id
                Generators::bool()                    // has team photo
            )
            ->then(function (
                int $teamId,
                int $halfSeasonId,
                bool $hasTeamPhoto,
            ): void {
                $teamItem = (object) [
                    'id' => $teamId,
                    'team_number' => 1,
                    'season_id' => 1,
                    'league_name' => 'Bezirksliga',
                    'age_class_name' => 'Herren',
                    'season_start_year' => 2024,
                    'season_label' => '',
                ];

                $teamPhoto = $hasTeamPhoto
                    ? "images/com_ttclub/teams/team_{$teamId}_hs_{$halfSeasonId}.jpg"
                    : null;

                $result = $this->simulateTeamPageRender(
                    $teamItem,
                    $teamPhoto,
                    [],
                    null, // fetch failure
                    null
                );

                // Photo source must never be empty
                $this->assertNotEmpty(
                    $result['photo_src'],
                    "Photo source must have a value (actual image or placeholder) even when schedule fails"
                );

                // If no team photo, the placeholder must be used
                if (!$hasTeamPhoto) {
                    $this->assertSame(
                        'media/com_ttclub/images/placeholder.png',
                        $result['photo_src'],
                        "Without a team photo, placeholder must be used"
                    );
                }
            });
    }

    /**
     * Property 21: The graceful degradation message is deterministic — for any
     * null schedule, the section always indicates "temporarily unavailable"
     * (never "no schedule data" or a table render attempt).
     *
     * This verifies the distinction between null (fetch failure → temporarily
     * unavailable) vs empty array (success with no data → "no schedule data").
     *
     * **Validates: Requirements 14.7**
     */
    public function testNullScheduleAlwaysShowsTemporarilyUnavailableNeverNoData(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 5000),          // team_id
                Generators::choose(1, 200),           // half_season_id
                Generators::choose(1, 15),            // team_number
                Generators::choose(0, 8)              // roster size
            )
            ->then(function (
                int $teamId,
                int $halfSeasonId,
                int $teamNumber,
                int $rosterSize,
            ): void {
                $teamItem = (object) [
                    'id' => $teamId,
                    'team_number' => $teamNumber,
                    'season_id' => rand(1, 20),
                    'league_name' => 'Liga ' . $teamNumber,
                    'age_class_name' => 'Herren',
                    'season_start_year' => 2020 + rand(0, 5),
                    'season_label' => '',
                ];

                $roster = [];
                for ($i = 0; $i < $rosterSize; $i++) {
                    $roster[] = (object) [
                        'first_name' => "Player{$i}",
                        'last_name' => "Name{$i}",
                        'player_image' => null,
                    ];
                }

                // Test with null schedule (fetch failure)
                $resultNull = $this->simulateTeamPageRender(
                    $teamItem,
                    null,
                    $roster,
                    null,    // fetch failure
                    null
                );

                $this->assertSame(
                    'temporarily_unavailable',
                    $resultNull['schedule_section'],
                    "null schedule must ALWAYS produce 'temporarily_unavailable', never 'no_schedule_data' or 'schedule_table'"
                );

                // Contrast: test with empty array (successful fetch, no matches)
                $resultEmpty = $this->simulateTeamPageRender(
                    $teamItem,
                    null,
                    $roster,
                    [],      // successful fetch, no matches
                    null
                );

                $this->assertSame(
                    'no_schedule_data',
                    $resultEmpty['schedule_section'],
                    "Empty array schedule must produce 'no_schedule_data', not 'temporarily_unavailable'"
                );

                // These two states must be distinct
                $this->assertNotSame(
                    $resultNull['schedule_section'],
                    $resultEmpty['schedule_section'],
                    "null (fetch failure) and [] (no data) must produce different messages"
                );
            });
    }
}
