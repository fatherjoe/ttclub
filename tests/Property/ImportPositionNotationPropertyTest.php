<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtParser;
use PHPUnit\Framework\TestCase;

/**
 * Property 28: Import position notation parsing
 *
 * For any valid position notation string "X.Y" where X and Y are positive integers,
 * parsing must extract X as the team number and Y as the player position within that team.
 *
 * **Validates: Requirements 7.11**
 */
class ImportPositionNotationPropertyTest extends TestCase
{
    use TestTrait;

    private ClickTtParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new ClickTtParser();
    }

    /**
     * Property 28: Valid "X.Y" notation extracts correct team number and position.
     *
     * Generate random positive integers X and Y, form "X.Y" strings,
     * and verify that parsePositionNotation returns teamNumber=X and position=Y.
     *
     * **Validates: Requirements 7.11**
     */
    public function testValidPositionNotationExtractsTeamAndPosition(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 999),
                Generators::choose(1, 999)
            )
            ->then(function (int $teamNumber, int $position): void {
                $notation = sprintf('%d.%d', $teamNumber, $position);

                $result = $this->parser->parsePositionNotation($notation);

                $this->assertSame(
                    $teamNumber,
                    $result['teamNumber'],
                    sprintf(
                        'For notation "%s", expected teamNumber=%d but got %d',
                        $notation,
                        $teamNumber,
                        $result['teamNumber']
                    )
                );

                $this->assertSame(
                    $position,
                    $result['position'],
                    sprintf(
                        'For notation "%s", expected position=%d but got %d',
                        $notation,
                        $position,
                        $result['position']
                    )
                );
            });
    }

    /**
     * Property 28: Round-trip consistency - team number and position are always recoverable.
     *
     * For any valid X.Y notation, the extracted values can reconstruct the original notation.
     *
     * **Validates: Requirements 7.11**
     */
    public function testPositionNotationRoundTrip(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 9999),
                Generators::choose(1, 9999)
            )
            ->then(function (int $teamNumber, int $position): void {
                $notation = sprintf('%d.%d', $teamNumber, $position);

                $result = $this->parser->parsePositionNotation($notation);

                $reconstructed = sprintf('%d.%d', $result['teamNumber'], $result['position']);

                $this->assertSame(
                    $notation,
                    $reconstructed,
                    sprintf(
                        'Round-trip failed for notation "%s": reconstructed as "%s"',
                        $notation,
                        $reconstructed
                    )
                );
            });
    }
}
