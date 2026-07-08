<?php

declare(strict_types=1);

namespace Tests\Unit\Administrator\Service;

use Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtImportService;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\DatabaseQuery;
use PHPUnit\Framework\TestCase;

/**
 * Tests for cup competition (Pokal) detection and parallel season creation during import.
 *
 * @covers \Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtImportService
 */
class ClickTtImportServiceCupTest extends TestCase
{
    /**
     * Test that importTeams creates a parallel "Pokal" season when the clubTeams page
     * contains a championship header indicating a cup competition.
     */
    public function testImportTeamsCreatesPokalSeasonForCupChampionship(): void
    {
        $html = <<<'HTML'
        <html><body>
        <table class="result-set">
            <tr><th colspan="5">Bezirkspokal Karlsruhe 2025/26</th></tr>
            <tr><th>Mannschaft</th><th>Liga</th><th>Kontakt</th><th>Rang</th><th>Punkte</th></tr>
            <tr><td>TTV Wöschbach</td><td>Bezirkspokal Gr. 1</td><td>Max Mustermann</td><td>1</td><td>10</td></tr>
        </table>
        </body></html>
        HTML;

        $insertedSeasons = [];
        $db = $this->buildTrackingDb($insertedSeasons, $insertedTeams);
        $service = new TestableClickTtImportService($db, 'BaTTV', 12345, 1, [$html]);

        $result = $service->importTeams(2025, 1);

        self::assertTrue($result->success);
        self::assertSame(1, $result->created);

        // Verify that a season with label 'Pokal' was inserted
        $pokalSeasons = array_filter($insertedSeasons, fn(object $s) => ($s->label ?? '') === 'Pokal');
        self::assertNotEmpty($pokalSeasons, 'Expected a season with label "Pokal" to be created');
    }

    /**
     * Test that importTeams does NOT create a Pokal season for normal league teams.
     */
    public function testImportTeamsDoesNotCreatePokalSeasonForLeagueTeams(): void
    {
        $html = <<<'HTML'
        <html><body>
        <table class="result-set">
            <tr><th colspan="5">BaTTV 25/26</th></tr>
            <tr><th>Mannschaft</th><th>Liga</th><th>Kontakt</th><th>Rang</th><th>Punkte</th></tr>
            <tr><td>TTV Wöschbach</td><td>Kreisliga Staffel 1</td><td>Max Mustermann</td><td>1</td><td>10</td></tr>
            <tr><td>TTV Wöschbach II</td><td>Kreisliga Staffel 2</td><td>Hans Meier</td><td>3</td><td>6</td></tr>
        </table>
        </body></html>
        HTML;

        $insertedSeasons = [];
        $db = $this->buildTrackingDb($insertedSeasons, $insertedTeams);
        $service = new TestableClickTtImportService($db, 'BaTTV', 12345, 1, [$html]);

        $result = $service->importTeams(2025, 1);

        self::assertTrue($result->success);
        self::assertSame(2, $result->created);

        // Verify that no season with label 'Pokal' was inserted
        $pokalSeasons = array_filter($insertedSeasons, fn(object $s) => ($s->label ?? '') === 'Pokal');
        self::assertEmpty($pokalSeasons, 'Expected no season with label "Pokal" for league teams');
    }

    /**
     * Test that both main and cup teams can coexist — cup teams assigned to Pokal season,
     * league teams assigned to the provided season ID.
     */
    public function testMainAndCupTeamsGoToDifferentSeasons(): void
    {
        $html = <<<'HTML'
        <html><body>
        <table class="result-set">
            <tr><th colspan="5">BaTTV 25/26</th></tr>
            <tr><th>Mannschaft</th><th>Liga</th></tr>
            <tr><td>TTV Wöschbach</td><td>Kreisliga Staffel 1</td></tr>
            <tr><th colspan="5">Bezirkspokal Karlsruhe 2025/26</th></tr>
            <tr><th>Mannschaft</th><th>Liga</th></tr>
            <tr><td>TTV Wöschbach</td><td>Bezirkspokal Gr. 1</td></tr>
        </table>
        </body></html>
        HTML;

        $insertedSeasons = [];
        $insertedTeams = [];
        $db = $this->buildTrackingDb($insertedSeasons, $insertedTeams);
        $service = new TestableClickTtImportService($db, 'BaTTV', 12345, 1, [$html]);

        $result = $service->importTeams(2025, 42);

        self::assertTrue($result->success);
        self::assertSame(2, $result->created);

        // The league team should be assigned to main season ID (42)
        $mainTeams = array_filter($insertedTeams, fn(object $t) => ($t->season_id ?? 0) === 42);
        self::assertCount(1, $mainTeams, 'Expected 1 team in the main season');

        // The cup team should be assigned to the Pokal season (different from 42)
        $pokalTeams = array_filter($insertedTeams, fn(object $t) => ($t->season_id ?? 0) !== 42);
        self::assertCount(1, $pokalTeams, 'Expected 1 team in the Pokal season');
    }

    /**
     * Test that discoverAndImportAll detects cup championships from link URL
     * championship parameter and creates Pokal season.
     */
    public function testDiscoverAndImportAllCreatesPokalSeasonFromChampionshipUrl(): void
    {
        $overviewHtml = <<<'HTML'
        <html><body>
        <a href="/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubPools?displayTyp=vorrunde&club=12345&contestType=Erwachsene&seasonName=2025%2F26&championship=Bezirkspokal+Karlsruhe+25%2F26">Bezirkspokal Vorrunde 2025/26</a>
        <a href="/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubPools?displayTyp=vorrunde&club=12345&contestType=Erwachsene&seasonName=2025%2F26&championship=BaTTV+25%2F26">Vorrunde 2025/26</a>
        </body></html>
        HTML;

        $rosterHtml = <<<'HTML'
        <html><body><table class="result-set">
            <tr><td>1.1</td><td>1500</td><td>Müller, Hans</td></tr>
        </table></body></html>
        HTML;

        $insertedSeasons = [];
        $db = $this->buildTrackingDb($insertedSeasons, $insertedTeams);
        $service = new TestableClickTtImportService($db, 'battv', 12345, 1, [$overviewHtml, $rosterHtml, $rosterHtml]);

        $result = $service->discoverAndImportAll();

        self::assertTrue($result->success);

        // Verify Pokal season was created
        $pokalSeasons = array_filter($insertedSeasons, fn(object $s) => ($s->label ?? '') === 'Pokal');
        self::assertNotEmpty($pokalSeasons, 'Expected a season with label "Pokal" from championship URL');

        // Verify main season was also created
        $mainSeasons = array_filter($insertedSeasons, fn(object $s) => ($s->label ?? '') === '');
        self::assertNotEmpty($mainSeasons, 'Expected a season with empty label for the league');
    }

    /**
     * Test that discoverAndImportAll detects cup championships from link text
     * when no championship URL parameter is present.
     */
    public function testDiscoverAndImportAllDetectsCupFromLinkText(): void
    {
        $overviewHtml = <<<'HTML'
        <html><body>
        <a href="/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/clubPools?displayTyp=vorrunde&club=12345&contestType=Erwachsene&seasonName=2025%2F26">Kreispokal Vorrunde</a>
        </body></html>
        HTML;

        $rosterHtml = <<<'HTML'
        <html><body><table class="result-set">
            <tr><td>1.1</td><td>1500</td><td>Schmidt, Peter</td></tr>
        </table></body></html>
        HTML;

        $insertedSeasons = [];
        $db = $this->buildTrackingDb($insertedSeasons, $insertedTeams);
        $service = new TestableClickTtImportService($db, 'battv', 12345, 1, [$overviewHtml, $rosterHtml]);

        $result = $service->discoverAndImportAll();

        self::assertTrue($result->success);

        // Verify Pokal season was created from link text "Kreispokal"
        $pokalSeasons = array_filter($insertedSeasons, fn(object $s) => ($s->label ?? '') === 'Pokal');
        self::assertNotEmpty($pokalSeasons, 'Expected Pokal season created from link text containing "Kreispokal"');
    }

    // ---------------------------------------------------------------
    // Test helpers
    // ---------------------------------------------------------------

    /**
     * Build a mock DatabaseInterface that tracks inserted seasons and teams.
     */
    private function buildTrackingDb(array &$insertedSeasons, ?array &$insertedTeams = null): DatabaseInterface
    {
        $insertedTeams ??= [];

        $db = $this->createMock(DatabaseInterface::class);
        $query = $this->createMock(DatabaseQuery::class);
        $query->method('select')->willReturnSelf();
        $query->method('from')->willReturnSelf();
        $query->method('where')->willReturnSelf();
        $query->method('order')->willReturnSelf();
        $db->method('getQuery')->willReturn($query);
        $db->method('quoteName')->willReturnCallback(fn(string $n) => '`' . $n . '`');
        $db->method('quote')->willReturnCallback(fn(string $v) => "'" . $v . "'");
        $db->method('loadResult')->willReturn(null);
        $db->method('setQuery')->willReturnSelf();

        $idCounter = 100;
        $db->method('insertObject')->willReturnCallback(
            function (string $table, object $rec, ?string $key = null) use (&$insertedSeasons, &$insertedTeams, &$idCounter): bool {
                if ($key !== null) {
                    $rec->$key = ++$idCounter;
                }
                if ($table === '#__ttclub_seasons') {
                    $insertedSeasons[] = clone $rec;
                }
                if ($table === '#__ttclub_teams') {
                    $insertedTeams[] = clone $rec;
                }
                return true;
            }
        );

        return $db;
    }
}

/**
 * Testable subclass of ClickTtImportService that overrides fetchPage
 * to return predefined HTML responses instead of making HTTP requests.
 */
class TestableClickTtImportService extends ClickTtImportService
{
    /** @var string[] */
    private array $htmlResponses;
    private int $callIndex = 0;

    /**
     * @param string[] $htmlResponses Ordered list of HTML responses to return
     */
    public function __construct(
        DatabaseInterface $db,
        string $federation,
        int $clubId,
        ?int $clubIdConfigId,
        array $htmlResponses,
    ) {
        parent::__construct($db, $federation, $clubId, $clubIdConfigId);
        $this->htmlResponses = $htmlResponses;
    }

    protected function fetchPage(string $url): ?string
    {
        if ($this->callIndex >= count($this->htmlResponses)) {
            return $this->htmlResponses[count($this->htmlResponses) - 1] ?? null;
        }
        return $this->htmlResponses[$this->callIndex++];
    }
}
