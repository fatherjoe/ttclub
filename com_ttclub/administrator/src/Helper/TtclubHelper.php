<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Helper;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Shared helper class for the Ttclub component.
 */
class TtclubHelper
{
    /**
     * Get the current half-season record.
     *
     * Logic: A season spans two calendar years (e.g., 2025/26).
     * - First half: Aug–Dec of start_year → half=1
     * - Second half: Jan–Jul of start_year+1 → half=2
     *
     * Determines current half by today's month and year, then looks up the matching season.
     * Falls back to the most recent season's second half if no exact match.
     */
    public static function getCurrentHalfSeason(?DatabaseInterface $db = null): ?object
    {
        if ($db === null) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
        }

        $now = new \DateTime('now');
        $currentYear = (int) $now->format('Y');
        $currentMonth = (int) $now->format('n');

        // Determine which season and half we're in
        if ($currentMonth >= 8) {
            // Aug-Dec: first half of season starting this year
            $seasonStartYear = $currentYear;
            $half = 1;
        } else {
            // Jan-Jul: second half of season that started last year
            $seasonStartYear = $currentYear - 1;
            $half = 2;
        }

        // Try to find the exact half-season
        $query = $db->getQuery(true)
            ->select('hs.*')
            ->from($db->quoteName('#__ttclub_half_seasons', 'hs'))
            ->innerJoin(
                $db->quoteName('#__ttclub_seasons', 's')
                . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('hs.season_id')
            )
            ->where($db->quoteName('s.start_year') . ' = :startYear')
            ->where($db->quoteName('hs.half') . ' = :half')
            ->where($db->quoteName('s.published') . ' = 1')
            ->bind(':startYear', $seasonStartYear, \Joomla\Database\ParameterType::INTEGER)
            ->bind(':half', $half, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $result = $db->loadObject();

        if ($result !== null) {
            return $result;
        }

        // Fallback: most recent season's last half
        $query = $db->getQuery(true)
            ->select('hs.*')
            ->from($db->quoteName('#__ttclub_half_seasons', 'hs'))
            ->innerJoin(
                $db->quoteName('#__ttclub_seasons', 's')
                . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('hs.season_id')
            )
            ->where($db->quoteName('s.published') . ' = 1')
            ->order($db->quoteName('s.start_year') . ' DESC, ' . $db->quoteName('hs.half') . ' DESC')
            ->setLimit(1);

        $db->setQuery($query);

        return $db->loadObject();
    }

    /**
     * Get the display name for a season by start_year and optional label.
     */
    public static function getSeasonDisplayName(int $startYear, string $label = ''): string
    {
        $name = sprintf('%d/%02d', $startYear, ($startYear + 1) % 100);

        if ($label !== '') {
            $name = $label . ' ' . $name;
        }

        return $name;
    }
}
