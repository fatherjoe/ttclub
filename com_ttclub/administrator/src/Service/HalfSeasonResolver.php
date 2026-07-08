<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Service;

/**
 * Resolves the current half-season based on calendar month.
 *
 * Logic:
 * - Month 8–12 (August–December): first half (half=1), start_year = that year
 * - Month 1–7 (January–July): second half (half=2), start_year = previous year
 * - Fallback: if no matching season exists, use most recent season's latest half-season
 */
class HalfSeasonResolver
{
    /**
     * Determine which half (1 or 2) a date falls into.
     * Month 8-12 → half 1, Month 1-7 → half 2
     */
    public function getHalfForDate(\DateTimeInterface $date): int
    {
        $month = (int) $date->format('n');

        return ($month >= 8) ? 1 : 2;
    }

    /**
     * Determine which season start_year a date corresponds to.
     * Month 8-12 → that year, Month 1-7 → previous year
     */
    public function getStartYearForDate(\DateTimeInterface $date): int
    {
        $month = (int) $date->format('n');
        $year = (int) $date->format('Y');

        return ($month >= 8) ? $year : $year - 1;
    }
}
