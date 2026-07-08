<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Table;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class SeasonTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__ttclub_seasons', 'id', $db);
    }

    public function check(): bool
    {
        if (!parent::check()) {
            return false;
        }

        // Set timestamps
        $now = Factory::getDate()->toSql();

        if (empty($this->created)) {
            $this->created = $now;
        }

        $this->modified = $now;

        // Validate start_year
        $year = (int) $this->start_year;

        if ($year < 1900 || $year > 2100) {
            $this->setError('Please enter a valid start year (1900–2100).');
            return false;
        }

        // Normalise label
        $this->label = trim((string) ($this->label ?? ''));

        // Enforce unique (start_year, label) combination
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__ttclub_seasons'))
            ->where($db->quoteName('start_year') . ' = ' . (int) $this->start_year)
            ->where($db->quoteName('label') . ' = ' . $db->quote($this->label));

        // Exclude the current record when updating
        if ($this->id) {
            $query->where($db->quoteName('id') . ' != ' . (int) $this->id);
        }

        $db->setQuery($query);
        $duplicate = $db->loadResult();

        if ($duplicate) {
            $this->setError('A season with this start year and label already exists. The combination of start year and label must be unique.');
            return false;
        }

        return true;
    }

    /**
     * Get the display name for this season (e.g., "2025/26" or "Pokal 2025/26").
     *
     * Formula: label ? label + ' ' + YYYY/YY : YYYY/YY
     * where YY = (start_year + 1) % 100, zero-padded.
     */
    public function getDisplayName(): string
    {
        $startYear = (int) $this->start_year;
        $endYearShort = ($startYear + 1) % 100;
        $label = trim((string) ($this->label ?? ''));

        return sprintf('%s%d/%02d', $label !== '' ? $label . ' ' : '', $startYear, $endYearShort);
    }

    public function delete($pk = null): bool
    {
        $pk = $pk ?? $this->id;
        $db = $this->getDbo();

        // Check rosters via half_seasons
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__ttclub_rosters', 'r'))
            ->innerJoin(
                $db->quoteName('#__ttclub_half_seasons', 'hs')
                . ' ON ' . $db->quoteName('r.half_season_id') . ' = ' . $db->quoteName('hs.id')
            )
            ->where($db->quoteName('hs.season_id') . ' = ' . (int) $pk);

        $db->setQuery($query);

        if ((int) $db->loadResult() > 0) {
            $this->setError('Cannot delete this season because it has roster assignments.');
            return false;
        }

        // Check schedules
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__ttclub_schedules'))
            ->where($db->quoteName('season_id') . ' = ' . (int) $pk);

        $db->setQuery($query);

        if ((int) $db->loadResult() > 0) {
            $this->setError('Cannot delete this season because it has schedule entries.');
            return false;
        }

        return parent::delete($pk);
    }
}
