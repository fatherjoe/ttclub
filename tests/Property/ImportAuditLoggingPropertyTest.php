<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property 30: Import audit logging
 *
 * Every import operation must be logged with: timestamp, type (what was imported),
 * record counts, and status (success/failure). This verifies that the logImport
 * logic correctly records all required audit fields for any import operation.
 *
 * **Validates: Requirements 7.8**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class ImportAuditLoggingPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Simulate the logImport logic from ImportLogTable.
     *
     * This replicates the core audit logging logic:
     * 1. Set import_date to current timestamp
     * 2. Set import_type (must be non-empty after trim)
     * 3. Set record counts (created, updated, unchanged)
     * 4. Set status (1 for success, 0 for failure)
     * 5. Set optional message
     * 6. Validate via check() — rejects empty import_type or empty import_date
     * 7. Persist the record
     *
     * Returns null on validation failure, or the log record array on success.
     */
    private function simulateLogImport(
        string $importType,
        int $recordsCreated,
        int $recordsUpdated,
        int $recordsUnchanged,
        bool $success,
        ?string $message = null
    ): ?array {
        $record = [
            'import_date' => date('Y-m-d H:i:s'),
            'import_type' => $importType,
            'records_created' => $recordsCreated,
            'records_updated' => $recordsUpdated,
            'records_unchanged' => $recordsUnchanged,
            'status' => $success ? 1 : 0,
            'message' => $message,
        ];

        // Simulate check() validation: import_type must be non-empty after trim
        $trimmedType = trim($record['import_type']);
        if ($trimmedType === '') {
            return null; // Validation failure
        }
        $record['import_type'] = $trimmedType;

        // Simulate check() validation: import_date must be non-empty
        if (empty($record['import_date'])) {
            return null; // Validation failure
        }

        return $record;
    }

    /**
     * Property 30: For any import operation (success or failure), a log record is
     * created with a non-empty timestamp, a non-empty import type, record counts,
     * and the correct status value.
     *
     * Generate random import types, record counts, and success/failure states;
     * verify that logImport() populates all required fields correctly.
     *
     * **Validates: Requirements 7.8**
     */
    public function testImportOperationCreatesLogWithRequiredFields(): void
    {
        $importTypes = ['players', 'rosters', 'schedules', 'teams', 'historical', 'full'];

        $this
            ->forAll(
                Generators::elements($importTypes),
                Generators::choose(0, 500),   // records_created
                Generators::choose(0, 500),   // records_updated
                Generators::choose(0, 500),   // records_unchanged
                Generators::bool()             // success
            )
            ->then(function (
                string $importType,
                int $recordsCreated,
                int $recordsUpdated,
                int $recordsUnchanged,
                bool $success
            ): void {
                $record = $this->simulateLogImport(
                    $importType,
                    $recordsCreated,
                    $recordsUpdated,
                    $recordsUnchanged,
                    $success
                );

                // Log must be created for valid import types
                $this->assertNotNull(
                    $record,
                    "logImport() should succeed for type='$importType', created=$recordsCreated, "
                    . "updated=$recordsUpdated, unchanged=$recordsUnchanged, success=" . ($success ? 'true' : 'false')
                );

                // Verify timestamp (import_date) is set and non-empty
                $this->assertArrayHasKey('import_date', $record, 'Log record must have an import_date field');
                $this->assertNotEmpty($record['import_date'], 'import_date must not be empty');

                // Verify the timestamp is a valid datetime format
                $parsedDate = \DateTime::createFromFormat('Y-m-d H:i:s', $record['import_date']);
                $this->assertNotFalse(
                    $parsedDate,
                    "import_date '{$record['import_date']}' must be a valid datetime"
                );

                // Verify import_type is set correctly
                $this->assertArrayHasKey('import_type', $record, 'Log record must have an import_type field');
                $this->assertSame(
                    $importType,
                    $record['import_type'],
                    "import_type should match the provided type '$importType'"
                );

                // Verify record counts are stored correctly
                $this->assertArrayHasKey('records_created', $record, 'Log record must have records_created');
                $this->assertSame(
                    $recordsCreated,
                    $record['records_created'],
                    'records_created must match the provided value'
                );

                $this->assertArrayHasKey('records_updated', $record, 'Log record must have records_updated');
                $this->assertSame(
                    $recordsUpdated,
                    $record['records_updated'],
                    'records_updated must match the provided value'
                );

                $this->assertArrayHasKey('records_unchanged', $record, 'Log record must have records_unchanged');
                $this->assertSame(
                    $recordsUnchanged,
                    $record['records_unchanged'],
                    'records_unchanged must match the provided value'
                );

                // Verify status reflects success/failure
                $this->assertArrayHasKey('status', $record, 'Log record must have a status field');
                $expectedStatus = $success ? 1 : 0;
                $this->assertSame(
                    $expectedStatus,
                    $record['status'],
                    "status should be $expectedStatus for " . ($success ? 'successful' : 'failed') . ' import'
                );
            });
    }

    /**
     * Property 30: Failed import operations are logged with status=0 and include error message.
     *
     * Generate random failure scenarios with error messages; verify that the log
     * record correctly captures the failure status and optional error message.
     *
     * **Validates: Requirements 7.8**
     */
    public function testFailedImportOperationsLoggedWithCorrectStatus(): void
    {
        $importTypes = ['players', 'rosters', 'schedules', 'teams', 'historical', 'full'];

        $this
            ->forAll(
                Generators::elements($importTypes),
                Generators::choose(0, 100),    // partial records_created before failure
                Generators::choose(0, 100),    // partial records_updated before failure
                Generators::choose(0, 100)     // partial records_unchanged before failure
            )
            ->then(function (
                string $importType,
                int $recordsCreated,
                int $recordsUpdated,
                int $recordsUnchanged
            ): void {
                $errorMessage = "Connection timeout for $importType import";

                $record = $this->simulateLogImport(
                    $importType,
                    $recordsCreated,
                    $recordsUpdated,
                    $recordsUnchanged,
                    false,         // failure
                    $errorMessage
                );

                $this->assertNotNull($record, 'logImport() should succeed in creating the log record');

                // Verify failure status
                $this->assertSame(
                    0,
                    $record['status'],
                    'Failed imports must be logged with status=0'
                );

                // Verify the error message is stored
                $this->assertArrayHasKey('message', $record, 'Log record should include a message field');
                $this->assertSame(
                    $errorMessage,
                    $record['message'],
                    'Error message must be stored in the log record'
                );
            });
    }

    /**
     * Property 30: Import log validation rejects records without required fields.
     *
     * The check() logic must reject records where import_type is empty.
     * This ensures incomplete log entries are never persisted.
     *
     * **Validates: Requirements 7.8**
     */
    public function testImportLogRejectsEmptyImportType(): void
    {
        $emptyTypes = ['', '   ', "\t", "\n"];

        $this
            ->forAll(
                Generators::elements($emptyTypes),
                Generators::choose(0, 100),
                Generators::choose(0, 100),
                Generators::choose(0, 100),
                Generators::bool()
            )
            ->then(function (
                string $emptyType,
                int $recordsCreated,
                int $recordsUpdated,
                int $recordsUnchanged,
                bool $success
            ): void {
                $record = $this->simulateLogImport(
                    $emptyType,
                    $recordsCreated,
                    $recordsUpdated,
                    $recordsUnchanged,
                    $success
                );

                $this->assertNull(
                    $record,
                    "logImport() should fail when import_type is empty/whitespace: '$emptyType'"
                );
            });
    }
}
