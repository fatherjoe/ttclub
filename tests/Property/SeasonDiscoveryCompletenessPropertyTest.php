<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use Fatherjoe\Component\Ttclub\Administrator\Service\MyTischtennisParser;
use Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtParser;
use PHPUnit\Framework\TestCase;

/**
 * Property 35: Season discovery completeness
 *
 * For any HTML page representing a season archive (with any number of season links
 * embedded), the season parser must discover and return exactly the set of season URLs
 * present in the archive page, with no omissions and no fabricated entries.
 *
 * **Validates: Requirements 13.1**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class SeasonDiscoveryCompletenessPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 35: MyTischtennisParser discovers all season links from select elements
     * with no omissions or fabrications.
     *
     * Generate random season link counts via select/option elements; verify the parser
     * extracts every embedded season entry with correct name and URL.
     *
     * **Validates: Requirements 13.1**
     */
    public function testMyTischtennisParserDiscoversAllSeasonsFromSelect(): void
    {
        $this
            ->forAll(
                Generators::choose(0, 20) // number of season links to embed
            )
            ->then(function (int $seasonCount): void {
                $parser = new MyTischtennisParser();

                // Generate season entries with distinct names and URLs
                $expectedSeasons = [];
                for ($i = 0; $i < $seasonCount; $i++) {
                    $startYear = 2000 + $i;
                    $endYearShort = str_pad((string) (($startYear + 1) % 100), 2, '0', STR_PAD_LEFT);
                    $expectedSeasons[] = [
                        'name' => $startYear . '/' . $endYearShort,
                        'url' => '/saison/' . $startYear . '/' . $endYearShort,
                    ];
                }

                // Build HTML with season entries in a select element with class "saison"
                $html = $this->buildSelectHtml($expectedSeasons);

                // Parse the HTML
                $discovered = $parser->parseSeasonArchive($html);

                // Verify count matches: no omissions, no fabrications
                $this->assertCount(
                    $seasonCount,
                    $discovered,
                    sprintf(
                        'Expected %d seasons discovered, got %d. No omissions or fabrications allowed.',
                        $seasonCount,
                        count($discovered)
                    )
                );

                // Verify each expected season is present in the results
                foreach ($expectedSeasons as $idx => $expected) {
                    $this->assertSame(
                        $expected['name'],
                        $discovered[$idx]['name'],
                        sprintf('Season name mismatch at index %d', $idx)
                    );
                    // URL should be normalized (absolute)
                    $this->assertStringContainsString(
                        $expected['url'],
                        $discovered[$idx]['url'],
                        sprintf('Season URL at index %d should contain the original path', $idx)
                    );
                }

                // Verify no fabricated entries: every discovered season maps back
                // to one of the expected ones
                $expectedNames = array_column($expectedSeasons, 'name');
                foreach ($discovered as $season) {
                    $this->assertContains(
                        $season['name'],
                        $expectedNames,
                        sprintf('Fabricated season "%s" found that was not in the source HTML', $season['name'])
                    );
                }
            });
    }

    /**
     * Property 35: MyTischtennisParser discovers all season links from list-based
     * navigation with no omissions or fabrications.
     *
     * Generate random season link counts via ul/li/a elements; verify complete extraction.
     *
     * **Validates: Requirements 13.1**
     */
    public function testMyTischtennisParserDiscoversAllSeasonsFromList(): void
    {
        $this
            ->forAll(
                Generators::choose(0, 15) // number of season links
            )
            ->then(function (int $seasonCount): void {
                $parser = new MyTischtennisParser();

                $expectedSeasons = [];
                for ($i = 0; $i < $seasonCount; $i++) {
                    $startYear = 2000 + $i;
                    $endYearShort = str_pad((string) (($startYear + 1) % 100), 2, '0', STR_PAD_LEFT);
                    $expectedSeasons[] = [
                        'name' => $startYear . '/' . $endYearShort,
                        'url' => '/saison/' . $startYear . '/' . $endYearShort,
                    ];
                }

                // Build HTML with season entries in a ul element with class "saison"
                $html = $this->buildListHtml($expectedSeasons);

                $discovered = $parser->parseSeasonArchive($html);

                // Completeness: count must match
                $this->assertCount(
                    $seasonCount,
                    $discovered,
                    sprintf(
                        'Expected %d seasons from list HTML, got %d.',
                        $seasonCount,
                        count($discovered)
                    )
                );

                // No fabrications: every result must be in the expected set
                $expectedNames = array_column($expectedSeasons, 'name');
                foreach ($discovered as $season) {
                    $this->assertContains(
                        $season['name'],
                        $expectedNames,
                        sprintf('Fabricated season "%s" found that was not in the source HTML', $season['name'])
                    );
                }
            });
    }

    /**
     * Property 35: ClickTtParser discovers all season links from select elements
     * with no omissions or fabrications.
     *
     * Generate random season link counts; verify complete extraction by ClickTtParser.
     *
     * **Validates: Requirements 13.1**
     */
    public function testClickTtParserDiscoversAllSeasonsFromSelect(): void
    {
        $this
            ->forAll(
                Generators::choose(0, 20) // number of season links
            )
            ->then(function (int $seasonCount): void {
                $parser = new ClickTtParser();

                $expectedSeasons = [];
                for ($i = 0; $i < $seasonCount; $i++) {
                    $startYear = 2000 + $i;
                    $endYearShort = str_pad((string) (($startYear + 1) % 100), 2, '0', STR_PAD_LEFT);
                    $expectedSeasons[] = [
                        'name' => $startYear . '/' . $endYearShort,
                        'url' => '/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/saison?id=' . $startYear,
                    ];
                }

                // Build HTML with a select element using click-tt's naming convention
                $html = $this->buildClickTtSelectHtml($expectedSeasons);

                $discovered = $parser->parseSeasonArchive($html);

                // Completeness
                $this->assertCount(
                    $seasonCount,
                    $discovered,
                    sprintf(
                        'ClickTtParser: Expected %d seasons, got %d.',
                        $seasonCount,
                        count($discovered)
                    )
                );

                // No fabrications
                $expectedNames = array_column($expectedSeasons, 'name');
                foreach ($discovered as $season) {
                    $this->assertContains(
                        $season['name'],
                        $expectedNames,
                        sprintf('ClickTtParser: Fabricated season "%s" not in source HTML', $season['name'])
                    );
                }
            });
    }

    /**
     * Property 35: ClickTtParser discovers all season links from link-based navigation
     * with no omissions or fabrications.
     *
     * Generate random season link counts using anchor tags with "saison" in href;
     * verify complete extraction.
     *
     * **Validates: Requirements 13.1**
     */
    public function testClickTtParserDiscoversAllSeasonsFromLinks(): void
    {
        $this
            ->forAll(
                Generators::choose(0, 15) // number of season links
            )
            ->then(function (int $seasonCount): void {
                $parser = new ClickTtParser();

                $expectedSeasons = [];
                for ($i = 0; $i < $seasonCount; $i++) {
                    $startYear = 2000 + $i;
                    $endYearShort = str_pad((string) (($startYear + 1) % 100), 2, '0', STR_PAD_LEFT);
                    $expectedSeasons[] = [
                        'name' => $startYear . '/' . $endYearShort,
                        'url' => '/cgi-bin/WebObjects/nuLigaTTDE.woa/wa/saison?id=' . $startYear,
                    ];
                }

                // Build HTML with links in a content div
                $html = $this->buildClickTtLinkHtml($expectedSeasons);

                $discovered = $parser->parseSeasonArchive($html);

                // Completeness
                $this->assertCount(
                    $seasonCount,
                    $discovered,
                    sprintf(
                        'ClickTtParser links: Expected %d seasons, got %d.',
                        $seasonCount,
                        count($discovered)
                    )
                );

                // No fabrications
                $expectedNames = array_column($expectedSeasons, 'name');
                foreach ($discovered as $season) {
                    $this->assertContains(
                        $season['name'],
                        $expectedNames,
                        sprintf('ClickTtParser: Fabricated season "%s" not in source HTML', $season['name'])
                    );
                }
            });
    }

    // ---------------------------------------------------------------
    // HTML generators
    // ---------------------------------------------------------------

    /**
     * Build HTML with season entries in a select element (mytischtennis.de style).
     *
     * @param array<array{name: string, url: string}> $seasons
     */
    private function buildSelectHtml(array $seasons): string
    {
        $options = '';
        foreach ($seasons as $season) {
            $options .= sprintf(
                '<option value="%s">%s</option>' . "\n",
                htmlspecialchars($season['url'], ENT_QUOTES),
                htmlspecialchars($season['name'], ENT_QUOTES)
            );
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head><title>Season Archive</title></head>
<body>
<div class="content">
    <h1>Saisonarchiv</h1>
    <select class="saison" name="saison_select">
        {$options}
    </select>
</div>
</body>
</html>
HTML;
    }

    /**
     * Build HTML with season entries in a ul/li/a list (mytischtennis.de style).
     *
     * @param array<array{name: string, url: string}> $seasons
     */
    private function buildListHtml(array $seasons): string
    {
        $items = '';
        foreach ($seasons as $season) {
            $items .= sprintf(
                '<li><a href="%s">%s</a></li>' . "\n",
                htmlspecialchars($season['url'], ENT_QUOTES),
                htmlspecialchars($season['name'], ENT_QUOTES)
            );
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head><title>Season Archive</title></head>
<body>
<div class="content">
    <h1>Saisonarchiv</h1>
    <ul class="saison">
        {$items}
    </ul>
</div>
</body>
</html>
HTML;
    }

    /**
     * Build HTML with season entries in a select element (click-tt.de style).
     *
     * @param array<array{name: string, url: string}> $seasons
     */
    private function buildClickTtSelectHtml(array $seasons): string
    {
        $options = '';
        foreach ($seasons as $season) {
            $options .= sprintf(
                '<option value="%s">%s</option>' . "\n",
                htmlspecialchars($season['url'], ENT_QUOTES),
                htmlspecialchars($season['name'], ENT_QUOTES)
            );
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head><title>click-tt Season Archive</title></head>
<body>
<div id="content">
    <h1>Saison Archiv</h1>
    <select name="saison" id="saison">
        {$options}
    </select>
</div>
</body>
</html>
HTML;
    }

    /**
     * Build HTML with season links in a content div (click-tt.de style).
     *
     * @param array<array{name: string, url: string}> $seasons
     */
    private function buildClickTtLinkHtml(array $seasons): string
    {
        $links = '';
        foreach ($seasons as $season) {
            $links .= sprintf(
                '<a href="%s">%s</a><br>' . "\n",
                htmlspecialchars($season['url'], ENT_QUOTES),
                htmlspecialchars($season['name'], ENT_QUOTES)
            );
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head><title>click-tt Season Archive</title></head>
<body>
<div id="content">
    <h1>Saison Archiv</h1>
    <div class="navigation">
        {$links}
    </div>
</div>
</body>
</html>
HTML;
    }
}
