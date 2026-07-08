<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Model;

use Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtImportService;
use Fatherjoe\Component\Ttclub\Administrator\Service\ImportResult;
use Fatherjoe\Component\Ttclub\Administrator\Service\ImportService;
use Fatherjoe\Component\Ttclub\Administrator\Table\ImportLogTable;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Import Model for orchestrating data imports from mytischtennis.de.
 *
 * Supports selective import of players, rosters, and schedules individually
 * or in combination. Handles conflict detection and administrator confirmation
 * for updates.
 */
class ImportModel extends BaseDatabaseModel
{
    /**
     * The import service instance.
     */
    private ?ImportService $importService = null;

    /**
     * Run the import for the specified data types.
     *
     * Requires all selected data types to import successfully for the operation
     * to be considered complete (Requirement 7.10).
     *
     * @param array<string> $types        Data types to import: 'players', 'rosters', 'schedules'.
     * @param int           $seasonId     The season ID for the import context.
     * @param int           $halfSeasonId The half-season ID (required for roster imports).
     * @param bool          $confirmed    Whether the administrator has confirmed conflict updates.
     *
     * @return array<string, ImportResult> Keyed by import type.
     */
    public function runImport(array $types, int $seasonId, int $halfSeasonId, bool $confirmed = false): array
    {
        $params = $this->getComponentParams();

        // If no season selected, run the full discovery import from clubPools overview
        if ($seasonId === 0) {
            $allInstances = ClickTtImportService::allFromDatabase($this->getDatabase());

            // Fallback to params-based config if no DB entries
            if (empty($allInstances)) {
                $allInstances = ClickTtImportService::allFromParams($params, $this->getDatabase());
            }

            if (empty($allInstances)) {
                return ['all' => new ImportResult(success: false, errorMessage: 'No club IDs configured. Go to Components → TT Club → Options and add entries in the "Club IDs" field (format: federation|club_id|label per line).')];
            }

            $totalCreated = 0;
            $totalUnchanged = 0;
            $messages = [];

            foreach ($allInstances as $instance) {
                $result = $instance['service']->discoverAndImportAll();
                $totalCreated += $result->created;
                $totalUnchanged += $result->unchanged;
                $label = $instance['label'];
                $clubId = $instance['club_id'];
                $messages[] = sprintf('%s (ID %d): %d created, %d unchanged', $label, $clubId, $result->created, $result->unchanged);

                if (!$result->success && $result->errorMessage) {
                    $messages[] = '  Error: ' . $result->errorMessage;
                }
            }

            $combined = new ImportResult(created: $totalCreated, updated: 0, unchanged: $totalUnchanged, errorMessage: implode(' | ', $messages));
            $this->logImportResult('full_import', $combined);
            return ['all' => $combined];
        }

        // Season-specific import: needs a configured service
        $service = ClickTtImportService::fromParams($params, $this->getDatabase());

        // Also try to get service from DB entries if params-based fails
        if ($service === null) {
            $allInstances = ClickTtImportService::allFromDatabase($this->getDatabase());
            if (!empty($allInstances)) {
                $service = $allInstances[0]['service'];
            }
        }

        if ($service === null) {
            $errorResult = new ImportResult(
                success: false,
                errorMessage: 'No club IDs configured. Go to Components → TT Club → Options and add entries in the "Club IDs" field (format: federation|club_id|label per line).',
            );
            $results = [];
            foreach ($types as $type) {
                $results[$type] = $errorResult;
            }
            return $results;
        }

        // Get the season's start_year
        $db = $this->getDatabase();
        $query = $db->getQuery(true)->select('start_year')->from($db->quoteName('#__ttclub_seasons'))->where('id = ' . $seasonId);
        $db->setQuery($query);
        $startYear = (int) $db->loadResult();

        if ($startYear === 0) {
            $errorResult = new ImportResult(success: false, errorMessage: 'Season not found.');
            $results = [];
            foreach ($types as $type) { $results[$type] = $errorResult; }
            return $results;
        }

        $results = [];

        foreach ($types as $type) {
            $result = match ($type) {
                'players' => $service->importTeams($startYear, $seasonId),
                'rosters' => $service->importRosters($startYear, $seasonId, $halfSeasonId),
                'schedules' => $service->importSchedule($startYear, $seasonId),
                default => new ImportResult(success: false, errorMessage: 'Unknown import type: ' . $type),
            };
            $results[$type] = $result;
            $this->logImportResult($type, $result);
        }

        return $results;
    }

    /**
     * Run a URL-based import for a parallel season.
     *
     * Delegates to ImportService::importFromUrl() which handles URL parsing,
     * season derivation, and roster import.
     *
     * @param string $clickTtUrl The click-tt.de URL to import from
     * @return ImportResult
     */
    public function runUrlImport(string $clickTtUrl): ImportResult
    {
        $service = $this->getImportService();

        $result = $service->importFromUrl($clickTtUrl);
        $this->logImportResult('url_import', $result);

        return $result;
    }

    /**
     * Validate the club connection by verifying the configured identifier.
     *
     * @param string $clubIdentifier The club identifier or URL to validate.
     *
     * @return bool True if the connection is valid.
     */
    public function validateConnection(string $clubIdentifier): bool
    {
        $service = $this->getImportService();

        return $service->validateClubConnection($clubIdentifier);
    }

    /**
     * Check whether imported data conflicts with existing records.
     *
     * @param array<string> $types    Data types to check.
     * @param int           $seasonId The season ID.
     *
     * @return bool True if potential conflicts exist.
     */
    public function hasConflicts(array $types, int $seasonId): bool
    {
        $db = $this->getDatabase();

        foreach ($types as $type) {
            $tableName = match ($type) {
                'players' => '#__ttclub_players',
                'rosters' => '#__ttclub_rosters',
                'schedules' => '#__ttclub_schedules',
                default => null,
            };

            if ($tableName === null) {
                continue;
            }

            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName($tableName));

            // For rosters and schedules, filter by season context
            if ($type === 'schedules') {
                $query->where($db->quoteName('season_id') . ' = ' . $seasonId);
            }

            $db->setQuery($query);
            $count = (int) $db->loadResult();

            if ($count > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the configured club URL from component parameters.
     */
    protected function getClubUrl(): string
    {
        $params = $this->getComponentParams();
        $builder = \Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtUrlBuilder::fromParams($params);

        if ($builder === null) {
            return '';
        }

        // Return the base mytischtennis.de URL (the ImportService will append specific paths)
        return $params->get('mytischtennis_club_url', '') ?: 'configured';
    }

    /**
     * Get the URL builder from component params.
     */
    public function getUrlBuilder(): ?\Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtUrlBuilder
    {
        return \Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtUrlBuilder::fromParams($this->getComponentParams());
    }

    /**
     * Get component parameters.
     */
    protected function getComponentParams(): \Joomla\Registry\Registry
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('params'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_ttclub'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'));

        $db->setQuery($query);
        $paramsJson = $db->loadResult();

        return new \Joomla\Registry\Registry($paramsJson ?: '{}');
    }

    /**
     * Get the ImportService instance.
     */
    protected function getImportService(): ImportService
    {
        if ($this->importService === null) {
            $this->importService = new ImportService($this->getDatabase());
        }

        return $this->importService;
    }

    /**
     * Log the result of an import operation.
     */
    protected function logImportResult(string $importType, ImportResult $result): void
    {
        /** @var ImportLogTable $logTable */
        $logTable = $this->getTable('ImportLog', 'Administrator');

        $logTable->logImport(
            importType: $importType,
            recordsCreated: $result->created,
            recordsUpdated: $result->updated,
            recordsUnchanged: $result->unchanged,
            success: $result->success,
            message: $result->errorMessage,
        );
    }

    /**
     * Get a table instance.
     *
     * @param string $name    The table name. Optional.
     * @param string $prefix  The class prefix. Optional.
     * @param array  $options Configuration array for table. Optional.
     *
     * @return \Joomla\CMS\Table\Table
     */
    public function getTable($name = 'ImportLog', $prefix = 'Administrator', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }
}
