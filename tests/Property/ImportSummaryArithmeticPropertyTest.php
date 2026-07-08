<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use Fatherjoe\Component\Ttclub\Administrator\Service\ImportResult;
use PHPUnit\Framework\TestCase;

/**
 * Property 31: Import summary arithmetic
 *
 * The import summary must satisfy: created + updated + unchanged = total_processed
 * for every import operation. This verifies that ImportResult::getTotal() always
 * returns the exact sum of its three component counts.
 *
 * **Validates: Requirements 7.4**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class ImportSummaryArithmeticPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Property 31: For any non-negative created, updated, and unchanged counts,
     * getTotal() must equal created + updated + unchanged.
     *
     * Generate random import counts; verify created + updated + unchanged = total processed.
     *
     * **Validates: Requirements 7.4**
     */
    public function testImportSummaryTotalEqualsCreatedPlusUpdatedPlusUnchanged(): void
    {
        $this
            ->forAll(
                Generators::choose(0, 10000),  // created
                Generators::choose(0, 10000),  // updated
                Generators::choose(0, 10000)   // unchanged
            )
            ->then(function (int $created, int $updated, int $unchanged): void {
                $result = new ImportResult(
                    created: $created,
                    updated: $updated,
                    unchanged: $unchanged,
                );

                $expectedTotal = $created + $updated + $unchanged;

                $this->assertSame(
                    $expectedTotal,
                    $result->getTotal(),
                    sprintf(
                        'getTotal() must equal created(%d) + updated(%d) + unchanged(%d) = %d, got %d',
                        $created,
                        $updated,
                        $unchanged,
                        $expectedTotal,
                        $result->getTotal()
                    )
                );
            });
    }

    /**
     * Property 31: The individual components of an ImportResult are preserved exactly
     * as provided, ensuring the summary display shows accurate per-category counts.
     *
     * **Validates: Requirements 7.4**
     */
    public function testImportSummaryComponentsPreservedExactly(): void
    {
        $this
            ->forAll(
                Generators::choose(0, 10000),  // created
                Generators::choose(0, 10000),  // updated
                Generators::choose(0, 10000)   // unchanged
            )
            ->then(function (int $created, int $updated, int $unchanged): void {
                $result = new ImportResult(
                    created: $created,
                    updated: $updated,
                    unchanged: $unchanged,
                );

                $this->assertSame($created, $result->created, 'created count must be preserved');
                $this->assertSame($updated, $result->updated, 'updated count must be preserved');
                $this->assertSame($unchanged, $result->unchanged, 'unchanged count must be preserved');
            });
    }

    /**
     * Property 31: When all counts are zero, total must be zero.
     * When any single count is positive, total must be positive.
     *
     * **Validates: Requirements 7.4**
     */
    public function testImportSummaryTotalZeroIffAllComponentsZero(): void
    {
        $this
            ->forAll(
                Generators::choose(0, 10000),
                Generators::choose(0, 10000),
                Generators::choose(0, 10000)
            )
            ->then(function (int $created, int $updated, int $unchanged): void {
                $result = new ImportResult(
                    created: $created,
                    updated: $updated,
                    unchanged: $unchanged,
                );

                $allZero = ($created === 0 && $updated === 0 && $unchanged === 0);

                if ($allZero) {
                    $this->assertSame(
                        0,
                        $result->getTotal(),
                        'Total must be zero when all components are zero'
                    );
                } else {
                    $this->assertGreaterThan(
                        0,
                        $result->getTotal(),
                        'Total must be positive when at least one component is positive'
                    );
                }
            });
    }
}
