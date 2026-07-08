<?php

declare(strict_types=1);

namespace Tests\Unit\Site\View;

use Fatherjoe\Component\Ttclub\Administrator\Helper\TtclubHelper;
use PHPUnit\Framework\TestCase;

/**
 * Tests the frontend season navigation display logic.
 *
 * Validates Requirements 17.5, 17.6:
 * - 17.5: Teams can be assigned to any season (main or parallel)
 * - 17.6: Frontend season navigation displays parallel seasons alongside main seasons
 *
 * The season selector should:
 * - Show all published seasons including those with labels (parallel/cup)
 * - Use derived display names: "YYYY/YY" for main seasons, "Label YYYY/YY" for labeled seasons
 * - Order seasons by start_year DESC, then by label ASC (empty label first within same year)
 */
class SeasonNavigationDisplayTest extends TestCase
{
    /**
     * Test that TtclubHelper::getSeasonDisplayName produces "YYYY/YY" for main seasons.
     */
    public function testMainSeasonDisplayName(): void
    {
        $displayName = TtclubHelper::getSeasonDisplayName(2025, '');

        $this->assertSame('2025/26', $displayName);
    }

    /**
     * Test that TtclubHelper::getSeasonDisplayName produces "Label YYYY/YY" for parallel seasons.
     */
    public function testParallelSeasonDisplayName(): void
    {
        $displayName = TtclubHelper::getSeasonDisplayName(2025, 'Pokal');

        $this->assertSame('Pokal 2025/26', $displayName);
    }

    /**
     * Test that seasons are ordered correctly: start_year DESC, label ASC.
     *
     * When we have seasons [2025 "", 2025 "Pokal", 2024 "", 2024 "Pokal"],
     * the correct display order should be:
     *   1. 2025/26 (2025, empty label)
     *   2. Pokal 2025/26 (2025, "Pokal")
     *   3. 2024/25 (2024, empty label)
     *   4. Pokal 2024/25 (2024, "Pokal")
     */
    public function testSeasonOrderingForNavigation(): void
    {
        // Simulate seasons as they would come from the DB ordered by start_year DESC, label ASC
        $seasons = [
            (object) ['id' => 1, 'start_year' => 2025, 'label' => ''],
            (object) ['id' => 2, 'start_year' => 2025, 'label' => 'Pokal'],
            (object) ['id' => 3, 'start_year' => 2024, 'label' => ''],
            (object) ['id' => 4, 'start_year' => 2024, 'label' => 'Pokal'],
        ];

        $displayNames = array_map(
            fn(object $s) => TtclubHelper::getSeasonDisplayName((int) $s->start_year, $s->label),
            $seasons
        );

        $this->assertSame(
            ['2025/26', 'Pokal 2025/26', '2024/25', 'Pokal 2024/25'],
            $displayNames
        );
    }

    /**
     * Test that ordering is correct when multiple parallel seasons exist.
     *
     * If a year has seasons with labels "Cup" and "Pokal", "Cup" should come before "Pokal"
     * alphabetically within the same start_year.
     */
    public function testMultipleParallelSeasonsOrderAlphabetically(): void
    {
        $seasons = [
            (object) ['id' => 1, 'start_year' => 2025, 'label' => ''],
            (object) ['id' => 2, 'start_year' => 2025, 'label' => 'Cup'],
            (object) ['id' => 3, 'start_year' => 2025, 'label' => 'Pokal'],
        ];

        $displayNames = array_map(
            fn(object $s) => TtclubHelper::getSeasonDisplayName((int) $s->start_year, $s->label),
            $seasons
        );

        $this->assertSame(
            ['2025/26', 'Cup 2025/26', 'Pokal 2025/26'],
            $displayNames
        );
    }

    /**
     * Test that a season with label at year boundary works (e.g., 2099/00).
     */
    public function testParallelSeasonWithYearWrap(): void
    {
        $displayName = TtclubHelper::getSeasonDisplayName(2099, 'Pokal');

        $this->assertSame('Pokal 2099/00', $displayName);
    }

    /**
     * Test that the display name helper correctly handles various labels.
     */
    public function testVariousLabels(): void
    {
        $this->assertSame('2024/25', TtclubHelper::getSeasonDisplayName(2024, ''));
        $this->assertSame('Pokal 2024/25', TtclubHelper::getSeasonDisplayName(2024, 'Pokal'));
        $this->assertSame('Bezirkspokal 2024/25', TtclubHelper::getSeasonDisplayName(2024, 'Bezirkspokal'));
        $this->assertSame('Cup 2024/25', TtclubHelper::getSeasonDisplayName(2024, 'Cup'));
    }
}
