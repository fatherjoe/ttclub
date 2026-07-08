<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtParser;
use PHPUnit\Framework\TestCase;

/**
 * Property 37: Team data extraction completeness
 *
 * For any HTML page representing a season's team listing, the parser must extract
 * all team entries with their team number, league name, and age class. The number
 * of extracted teams must equal the number of team entries in the source HTML.
 *
 * **Validates: Requirements 13.3**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class TeamDataExtractionPropertyTest extends TestCase
{
    use TestTrait;

    private ClickTtParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new ClickTtParser();
    }

    /**
     * Property 37: Team count matches number of entries in source HTML.
     *
     * Generate random team data, build a well-formed HTML table, parse it,
     * and verify the number of extracted teams equals the number of team rows.
     *
     * **Validates: Requirements 13.3**
     */
    public function testTeamCountMatchesSourceEntries(): void
    {
        $ageClasses = ['Herren', 'Damen', 'Jungen U18', 'Mädchen U15', 'Schüler U11'];
        $leagues = ['Kreisliga Staffel 1', 'Bezirksliga Gr. 2', 'Verbandsliga', 'Oberliga', 'Landesliga Gr. 3'];

        $this
            ->forAll(
                Generators::choose(1, 10), // number of teams
                Generators::seq(Generators::choose(0, count($ageClasses) - 1)), // age class indices
                Generators::seq(Generators::choose(0, count($leagues) - 1)) // league indices
            )
            ->then(function (int $teamCount, array $ageClassIdxs, array $leagueIdxs) use ($ageClasses, $leagues): void {
                // Build team data
                $teamData = [];
                for ($i = 0; $i < $teamCount; $i++) {
                    $ageClassIdx = $ageClassIdxs[$i] ?? 0;
                    $leagueIdx = $leagueIdxs[$i] ?? 0;
                    $teamNumber = $i + 1;
                    $teamData[] = [
                        'team_number' => $teamNumber,
                        'league' => $leagues[$leagueIdx],
                        'age_class' => $ageClasses[$ageClassIdx],
                    ];
                }

                // Build HTML from team data
                $html = $this->buildTeamListingHtml($teamData);

                // Parse the HTML
                $result = $this->parser->parseTeams($html);

                // Verify team count
                $this->assertCount(
                    $teamCount,
                    $result,
                    sprintf(
                        'Expected %d teams extracted from HTML, got %d. HTML contained teams: %s',
                        $teamCount,
                        count($result),
                        implode(', ', array_map(fn($t) => $t['team_number'] . ' (' . $t['age_class'] . ')', $teamData))
                    )
                );
            });
    }

    /**
     * Property 37: Extracted team numbers match source data.
     *
     * Generate random team data, build HTML, parse it, and verify extracted
     * team_numbers (as a multiset) match the source data exactly.
     *
     * **Validates: Requirements 13.3**
     */
    public function testExtractedTeamNumbersMatchSource(): void
    {
        $ageClasses = ['Herren', 'Damen', 'Jungen U18', 'Mädchen U15'];
        $leagues = ['Kreisliga Staffel 1', 'Bezirksliga Gr. 2', 'Verbandsliga', 'Oberliga'];

        $this
            ->forAll(
                Generators::choose(1, 8), // number of teams
                Generators::seq(Generators::choose(0, count($ageClasses) - 1)),
                Generators::seq(Generators::choose(0, count($leagues) - 1))
            )
            ->then(function (int $teamCount, array $ageClassIdxs, array $leagueIdxs) use ($ageClasses, $leagues): void {
                $teamData = [];
                for ($i = 0; $i < $teamCount; $i++) {
                    $teamData[] = [
                        'team_number' => $i + 1,
                        'league' => $leagues[$leagueIdxs[$i] ?? 0],
                        'age_class' => $ageClasses[$ageClassIdxs[$i] ?? 0],
                    ];
                }

                $html = $this->buildTeamListingHtml($teamData);
                $result = $this->parser->parseTeams($html);

                if (count($result) !== $teamCount) {
                    $this->markTestIncomplete('Count mismatch handled by other test');
                    return;
                }

                // Compare as sorted arrays (order may differ due to age-class grouping in HTML)
                $extractedNumbers = array_map(fn($t) => $t['team_number'], $result);
                $expectedNumbers = array_map(fn($t) => $t['team_number'], $teamData);
                sort($extractedNumbers);
                sort($expectedNumbers);

                $this->assertEquals(
                    $expectedNumbers,
                    $extractedNumbers,
                    'Extracted team numbers (as a set) must match the source data'
                );
            });
    }

    /**
     * Property 37: Extracted league names match source data.
     *
     * Generate random team data, build HTML, parse it, and verify every
     * (team_number, league) pair in the source is present in the extracted results.
     *
     * **Validates: Requirements 13.3**
     */
    public function testExtractedLeagueNamesMatchSource(): void
    {
        $ageClasses = ['Herren', 'Damen', 'Jugend U19'];
        $leagues = ['Kreisliga Staffel 1', 'Bezirksliga Gr. 2', 'Verbandsliga', 'Landesliga Gr. 1'];

        $this
            ->forAll(
                Generators::choose(1, 6), // number of teams
                Generators::seq(Generators::choose(0, count($ageClasses) - 1)),
                Generators::seq(Generators::choose(0, count($leagues) - 1))
            )
            ->then(function (int $teamCount, array $ageClassIdxs, array $leagueIdxs) use ($ageClasses, $leagues): void {
                $teamData = [];
                for ($i = 0; $i < $teamCount; $i++) {
                    $teamData[] = [
                        'team_number' => $i + 1,
                        'league' => $leagues[$leagueIdxs[$i] ?? 0],
                        'age_class' => $ageClasses[$ageClassIdxs[$i] ?? 0],
                    ];
                }

                $html = $this->buildTeamListingHtml($teamData);
                $result = $this->parser->parseTeams($html);

                if (count($result) !== $teamCount) {
                    $this->markTestIncomplete('Count mismatch handled by other test');
                    return;
                }

                // Build lookup by team_number to match results regardless of order
                $resultByNumber = [];
                foreach ($result as $team) {
                    $resultByNumber[$team['team_number']] = $team;
                }

                foreach ($teamData as $expected) {
                    $this->assertArrayHasKey(
                        $expected['team_number'],
                        $resultByNumber,
                        sprintf('Team number %d not found in extracted results', $expected['team_number'])
                    );
                    $this->assertSame(
                        $expected['league'],
                        $resultByNumber[$expected['team_number']]['league'],
                        sprintf(
                            'Team %d: expected league "%s", got "%s"',
                            $expected['team_number'],
                            $expected['league'],
                            $resultByNumber[$expected['team_number']]['league']
                        )
                    );
                }
            });
    }

    /**
     * Property 37: Extracted age classes match source data.
     *
     * Generate random team data, build HTML, parse it, and verify every
     * (team_number, age_class) pair in the source is present in the extracted results.
     *
     * **Validates: Requirements 13.3**
     */
    public function testExtractedAgeClassesMatchSource(): void
    {
        $ageClasses = ['Herren', 'Damen', 'Jungen U18', 'Schüler U11', 'Senioren'];
        $leagues = ['Kreisliga Staffel 1', 'Bezirksliga Gr. 2', 'Oberliga'];

        $this
            ->forAll(
                Generators::choose(1, 6),
                Generators::seq(Generators::choose(0, count($ageClasses) - 1)),
                Generators::seq(Generators::choose(0, count($leagues) - 1))
            )
            ->then(function (int $teamCount, array $ageClassIdxs, array $leagueIdxs) use ($ageClasses, $leagues): void {
                $teamData = [];
                for ($i = 0; $i < $teamCount; $i++) {
                    $teamData[] = [
                        'team_number' => $i + 1,
                        'league' => $leagues[$leagueIdxs[$i] ?? 0],
                        'age_class' => $ageClasses[$ageClassIdxs[$i] ?? 0],
                    ];
                }

                $html = $this->buildTeamListingHtml($teamData);
                $result = $this->parser->parseTeams($html);

                if (count($result) !== $teamCount) {
                    $this->markTestIncomplete('Count mismatch handled by other test');
                    return;
                }

                // Build lookup by team_number to match results regardless of order
                $resultByNumber = [];
                foreach ($result as $team) {
                    $resultByNumber[$team['team_number']] = $team;
                }

                foreach ($teamData as $expected) {
                    $this->assertArrayHasKey(
                        $expected['team_number'],
                        $resultByNumber,
                        sprintf('Team number %d not found in extracted results', $expected['team_number'])
                    );
                    $this->assertSame(
                        $expected['age_class'],
                        $resultByNumber[$expected['team_number']]['age_class'],
                        sprintf(
                            'Team %d: expected age_class "%s", got "%s"',
                            $expected['team_number'],
                            $expected['age_class'],
                            $resultByNumber[$expected['team_number']]['age_class']
                        )
                    );
                }
            });
    }

    /**
     * Build an HTML page containing a team listing table in click-tt.de format.
     *
     * The parser's Strategy 1 expects a table with class "result-set" or "table",
     * with tbody rows containing: team name in first td, league in second td,
     * and optional age class in third td. Age class headers are detected as rows
     * with th or td.header/td.group containing age class keywords.
     *
     * @param array<int, array{team_number: int, league: string, age_class: string}> $teams
     * @return string
     */
    private function buildTeamListingHtml(array $teams): string
    {
        // Group teams by age class to generate proper age class header rows
        $grouped = [];
        foreach ($teams as $team) {
            $grouped[$team['age_class']][] = $team;
        }

        $rows = '';
        foreach ($grouped as $ageClass => $groupTeams) {
            // Add age class header row
            $rows .= sprintf(
                '<tr><td class="group" colspan="3">%s</td></tr>' . "\n",
                htmlspecialchars($ageClass, \ENT_QUOTES, 'UTF-8')
            );

            // Add team rows
            foreach ($groupTeams as $team) {
                $teamName = sprintf('TTC Musterstadt %d', $team['team_number']);
                $rows .= sprintf(
                    '<tr><td>%s</td><td>%s</td><td>%s</td></tr>' . "\n",
                    htmlspecialchars($teamName, \ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($team['league'], \ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($team['age_class'], \ENT_QUOTES, 'UTF-8')
                );
            }
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Teams</title></head>
<body>
<div id="content">
<table class="result-set">
<thead><tr><th>Mannschaft</th><th>Staffel</th><th>Altersklasse</th></tr></thead>
<tbody>
{$rows}
</tbody>
</table>
</div>
</body>
</html>
HTML;
    }
}
