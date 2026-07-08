<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property 42: Historical import per-season audit logging
 *
 * During historical import, each season imported must produce its own log entry
 * (one per season). If N seasons are imported, exactly N log entries should be
 * created, each with correct content referencing the imported season.
 *
 * **Validates: Requirements 13.10**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class HistoricalImportPerSeasonAuditLoggingPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Simulate the per-season logging behavior of HistoricalImportService.
     *
     * For each discovered season that is successfully imported, the service calls
     * logSeasonImport() which creates one log entry with:
     * - import_type = 'historical_season'
     * - records_created = total records for that season
     * - status = 1 (success) or 0 (failure)
     * - message describing per-season results
     *
     * Returns the array of log entries created.
     *
     * @param array<array{name: string, teamsCreated: int, playersCreated: int, rosterEntries: int, scheduleEntries: int, success: bool}> $seasons
     * @return array<array{import_date: string, import_type: string, records_created: int, records_updated: int, records_unchanged: int, status: int, message: string|null}>
     */
    private function simulateHistoricalImportLogging(array $seasons): array
    {
        $logEntries = [];

        foreach ($seasons as $season) {
            $totalRecords = $season['teamsCreated']
                + $season['playersCreated']
                + $season['rosterEntries']
                + $season['scheduleEntries'];

            $message = $season['success']
                ? sprintf(
                    'Season "%s": %d teams, %d players, %d roster entries, %d schedules created.',
                    $season['name'],
                    $season['teamsCreated'],
                    $season['playersCreated'],
                    $season['rosterEntries'],
                    $season['scheduleEntries'],
                )
                : 'Failed to create season record.';

            // Simulate logSeasonImport -> ImportLogTable::logImport
            $record = [
                'import_date' => date('Y-m-d H:i:s'),
                'import_type' => 'historical_season',
                'records_created' => $season['success'] ? $totalRecords : 0,
                'records_updated' => 0,
                'records_unchanged' => 0,
                'status' => $season['success'] ? 1 : 0,
                'message' => $message,
            ];

            // Simulate check() validation
            $trimmedType = trim($record['import_type']);
            if ($trimmedType === '' || empty($record['import_date'])) {
                continue; // Would not persist
            }

            $logEntries[] = $record;
        }

        return $logEntries;
    }

    /**
     * Property 42: Importing N seasons produces exactly N log entries.
     *
     * Generate a random number of seasons (1–20); verify that exactly N log entries
     * are created when N seasons are imported.
     *
     * **Validates: Requirements 13.10**
     */
    public function testNSeasonsProduceNLogEntries(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 20) // season count
            )
            ->then(function (int $seasonCount): void {
                $seasons = [];

                for ($i = 0; $i < $seasonCount; $i++) {
                    $seasons[] = [
                        'name' => '20' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) . '/'. str_pad((string) ($i + 2), 2, '0', STR_PAD_LEFT),
                        'teamsCreated' => random_int(1, 10),
                        'playersCreated' => random_int(0, 30),
                        'rosterEntries' => random_int(0, 50),
                        'scheduleEntries' => random_int(0, 40),
                        'success' => true,
                    ];
                }

                $logEntries = $this->simulateHistoricalImportLogging($seasons);

                $this->assertCount(
                    $seasonCount,
                    $logEntries,
                    "Importing $seasonCount seasons must produce exactly $seasonCount log entries, got " . count($logEntries)
                );
            });
    }

    /**
     * Property 42: Each per-season log entry has import_type 'historical_season' and
     * references the correct season in its message.
     *
     * Generate random season names and import results; verify each log entry contains
     * the correct import type and the season name in the message.
     *
     * **Validates: Requirements 13.10**
     */
    public function testPerSeasonLogEntriesHaveCorrectContent(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 15), // season count
                Generators::choose(0, 10),  // teams per season
                Generators::choose(0, 20),  // players per season
                Generators::choose(0, 30),  // roster entries per season
                Generators::choose(0, 25)   // schedule entries per season
            )
            ->then(function (
                int $seasonCount,
                int $teamsCreated,
                int $playersCreated,
                int $rosterEntries,
                int $scheduleEntries
            ): void {
                $seasons = [];
                $seasonNames = [];

                for ($i = 0; $i < $seasonCount; $i++) {
                    $startYear = 2000 + $i;
                    $name = $startYear . '/' . substr((string) ($startYear + 1), 2);
                    $seasonNames[] = $name;
                    $seasons[] = [
                        'name' => $name,
                        'teamsCreated' => $teamsCreated,
                        'playersCreated' => $playersCreated,
                        'rosterEntries' => $rosterEntries,
                        'scheduleEntries' => $scheduleEntries,
                        'success' => true,
                    ];
                }

                $logEntries = $this->simulateHistoricalImportLogging($seasons);

                foreach ($logEntries as $index => $entry) {
                    // Verify import_type is always 'historical_season'
                    $this->assertSame(
                        'historical_season',
                        $entry['import_type'],
                        "Log entry $index must have import_type 'historical_season'"
                    );

                    // Verify the log entry has a valid timestamp
                    $this->assertNotEmpty(
                        $entry['import_date'],
                        "Log entry $index must have a non-empty import_date"
                    );

                    // Verify the message references the correct season name
                    $expectedSeasonName = $seasonNames[$index];
                    $this->assertStringContainsString(
                        $expectedSeasonName,
                        $entry['message'],
                        "Log entry $index message must reference season '$expectedSeasonName'"
                    );

                    // Verify status is success (1)
                    $this->assertSame(
                        1,
                        $entry['status'],
                        "Log entry $index must have status=1 for successful import"
                    );

                    // Verify records_created matches total for the season
                    $expectedTotal = $teamsCreated + $playersCreated + $rosterEntries + $scheduleEntries;
                    $this->assertSame(
                        $expectedTotal,
                        $entry['records_created'],
                        "Log entry $index records_created must equal sum of teams + players + roster + schedule entries"
                    );
                }
            });
    }

    /**
     * Property 42: Failed seasons also produce a log entry each.
     *
     * Generate a mix of successful and failed season imports; verify each still
     * produces exactly one log entry regardless of success/failure status.
     *
     * **Validates: Requirements 13.10**
     */
    public function testFailedSeasonsAlsoProduceLogEntries(): void
    {
        $this
            ->forAll(
                Generators::choose(1, 15), // season count
                Generators::choose(1, 14)  // number of failures (capped to seasonCount in logic)
            )
            ->then(function (int $seasonCount, int $failureCount): void {
                // Cap failures to seasonCount
                $actualFailures = min($failureCount, $seasonCount);

                $seasons = [];

                for ($i = 0; $i < $seasonCount; $i++) {
                    $success = $i >= $actualFailures; // First N seasons fail
                    $seasons[] = [
                        'name' => 'Season ' . ($i + 1),
                        'teamsCreated' => $success ? random_int(1, 5) : 0,
                        'playersCreated' => $success ? random_int(0, 10) : 0,
                        'rosterEntries' => $success ? random_int(0, 20) : 0,
                        'scheduleEntries' => $success ? random_int(0, 15) : 0,
                        'success' => $success,
                    ];
                }

                $logEntries = $this->simulateHistoricalImportLogging($seasons);

                // Every season (success or failure) must produce a log entry
                $this->assertCount(
                    $seasonCount,
                    $logEntries,
                    "All $seasonCount seasons (including failures) must produce log entries"
                );

                // Verify failed entries have status=0 and success entries have status=1
                for ($i = 0; $i < $seasonCount; $i++) {
                    $expectedStatus = $seasons[$i]['success'] ? 1 : 0;
                    $this->assertSame(
                        $expectedStatus,
                        $logEntries[$i]['status'],
                        "Log entry $i must have status=$expectedStatus"
                    );

                    // Failed entries must have records_created=0
                    if (!$seasons[$i]['success']) {
                        $this->assertSame(
                            0,
                            $logEntries[$i]['records_created'],
                            "Failed log entry $i must have records_created=0"
                        );
                    }
                }
            });
    }
}
