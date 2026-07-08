<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtUrlBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Property 27: Import URL construction
 *
 * Generate random federation strings, club numbers, club names, start years, and page names;
 * verify URL matches pattern:
 * `https://www.mytischtennis.de/click-tt/{federation}/{seasonSlug}/verein/{clubNumber}/{clubName}/{page}/`
 *
 * where seasonSlug = "{startYear%100 zero-padded}--{(startYear+1)%100 zero-padded}"
 *
 * **Validates: Requirements 7.7**
 */
class ImportUrlConstructionPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 27: Teams URL matches expected pattern for any valid inputs.
     *
     * Generate random federation strings, club numbers, club names, and start years;
     * verify the teams URL follows the pattern exactly.
     *
     * **Validates: Requirements 7.7**
     */
    public function testTeamsUrlConstruction(): void
    {
        $this
            ->forAll(
                Generators::elements(['WTTV', 'BaTTV', 'HeTTV', 'NdSVTT', 'TTVN']),
                Generators::suchThat(
                    fn(string $s) => trim($s) !== '' && preg_match('/^[0-9]+$/', $s) === 1,
                    Generators::map(
                        fn(int $n) => (string) $n,
                        Generators::choose(10000, 99999)
                    )
                ),
                Generators::elements(['ttc-musterstadt', 'sv-fortuna', 'tus-example', 'sc-test-club']),
                Generators::choose(1900, 2100)
            )
            ->then(function (string $federation, string $clubNumber, string $clubName, int $startYear): void {
                $builder = new ClickTtUrlBuilder($federation, $clubNumber, $clubName);

                $url = $builder->getTeamsUrl($startYear);

                $startShort = $startYear % 100;
                $endShort = ($startYear + 1) % 100;
                $seasonSlug = sprintf('%02d--%02d', $startShort, $endShort);

                $expected = sprintf(
                    'https://www.mytischtennis.de/click-tt/%s/%s/verein/%s/%s/mannschaften/',
                    $federation,
                    $seasonSlug,
                    $clubNumber,
                    $clubName
                );

                $this->assertSame(
                    $expected,
                    $url,
                    sprintf(
                        'Teams URL for federation=%s, club=%s, name=%s, year=%d should be "%s", got "%s"',
                        $federation,
                        $clubNumber,
                        $clubName,
                        $startYear,
                        $expected,
                        $url
                    )
                );
            });
    }

    /**
     * Property 27: Schedule URL matches expected pattern for any valid inputs.
     *
     * **Validates: Requirements 7.7**
     */
    public function testScheduleUrlConstruction(): void
    {
        $this
            ->forAll(
                Generators::elements(['WTTV', 'BaTTV', 'HeTTV', 'NdSVTT', 'TTVN']),
                Generators::map(
                    fn(int $n) => (string) $n,
                    Generators::choose(10000, 99999)
                ),
                Generators::elements(['ttc-musterstadt', 'sv-fortuna', 'tus-example', 'sc-test-club']),
                Generators::choose(1900, 2100)
            )
            ->then(function (string $federation, string $clubNumber, string $clubName, int $startYear): void {
                $builder = new ClickTtUrlBuilder($federation, $clubNumber, $clubName);

                $url = $builder->getScheduleUrl($startYear);

                $startShort = $startYear % 100;
                $endShort = ($startYear + 1) % 100;
                $seasonSlug = sprintf('%02d--%02d', $startShort, $endShort);

                $expected = sprintf(
                    'https://www.mytischtennis.de/click-tt/%s/%s/verein/%s/%s/spielplan/',
                    $federation,
                    $seasonSlug,
                    $clubNumber,
                    $clubName
                );

                $this->assertSame(
                    $expected,
                    $url,
                    sprintf(
                        'Schedule URL for federation=%s, club=%s, name=%s, year=%d should be "%s", got "%s"',
                        $federation,
                        $clubNumber,
                        $clubName,
                        $startYear,
                        $expected,
                        $url
                    )
                );
            });
    }

    /**
     * Property 27: All URL methods produce URLs with consistent base pattern.
     *
     * Generate random inputs and verify that all URL methods share the same base structure
     * differing only in the trailing page segment.
     *
     * **Validates: Requirements 7.7**
     */
    public function testAllUrlMethodsShareConsistentBasePattern(): void
    {
        $this
            ->forAll(
                Generators::elements(['WTTV', 'BaTTV', 'HeTTV', 'NdSVTT', 'TTVN']),
                Generators::map(
                    fn(int $n) => (string) $n,
                    Generators::choose(10000, 99999)
                ),
                Generators::elements(['ttc-musterstadt', 'sv-fortuna', 'tus-example', 'sc-test-club']),
                Generators::choose(1900, 2100)
            )
            ->then(function (string $federation, string $clubNumber, string $clubName, int $startYear): void {
                $builder = new ClickTtUrlBuilder($federation, $clubNumber, $clubName);

                $startShort = $startYear % 100;
                $endShort = ($startYear + 1) % 100;
                $seasonSlug = sprintf('%02d--%02d', $startShort, $endShort);

                $expectedBase = sprintf(
                    'https://www.mytischtennis.de/click-tt/%s/%s/verein/%s/%s/',
                    $federation,
                    $seasonSlug,
                    $clubNumber,
                    $clubName
                );

                $teamsUrl = $builder->getTeamsUrl($startYear);
                $scheduleUrl = $builder->getScheduleUrl($startYear);
                $bilanzenUrl = $builder->getBilanzenUrl($startYear);
                $meldungenUrl = $builder->getMeldungenUrl($startYear);
                $clubInfoUrl = $builder->getClubInfoUrl($startYear);

                $this->assertStringStartsWith($expectedBase, $teamsUrl);
                $this->assertStringStartsWith($expectedBase, $scheduleUrl);
                $this->assertStringStartsWith($expectedBase, $bilanzenUrl);
                $this->assertStringStartsWith($expectedBase, $meldungenUrl);
                $this->assertStringStartsWith($expectedBase, $clubInfoUrl);

                // Verify the specific page suffixes
                $this->assertStringEndsWith('mannschaften/', $teamsUrl);
                $this->assertStringEndsWith('spielplan/', $scheduleUrl);
                $this->assertStringEndsWith('bilanzen/gesamt/', $bilanzenUrl);
                $this->assertStringEndsWith('meldungen/', $meldungenUrl);
                $this->assertStringEndsWith('info/', $clubInfoUrl);
            });
    }

    /**
     * Property 27: Season slug wraps correctly at century boundaries.
     *
     * Verify that years like 1999 produce "99--00" and 2099 produce "99--00".
     *
     * **Validates: Requirements 7.7**
     */
    public function testSeasonSlugCenturyWraparound(): void
    {
        $this
            ->forAll(
                Generators::elements([1999, 2099, 1900, 2000]),
                Generators::elements(['WTTV', 'BaTTV']),
                Generators::elements(['12345', '99999']),
                Generators::elements(['test-club'])
            )
            ->then(function (int $startYear, string $federation, string $clubNumber, string $clubName): void {
                $builder = new ClickTtUrlBuilder($federation, $clubNumber, $clubName);

                $url = $builder->getTeamsUrl($startYear);

                $startShort = $startYear % 100;
                $endShort = ($startYear + 1) % 100;
                $seasonSlug = sprintf('%02d--%02d', $startShort, $endShort);

                $this->assertStringContainsString(
                    '/' . $seasonSlug . '/',
                    $url,
                    sprintf(
                        'URL for year %d should contain season slug "%s"',
                        $startYear,
                        $seasonSlug
                    )
                );
            });
    }
}
