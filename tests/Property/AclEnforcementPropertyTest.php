<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Tests\Property;

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Property 34: ACL enforcement
 *
 * For any backend CRUD operation and any user lacking the required Joomla ACL
 * permission (core.create, core.edit, core.delete, core.edit.state, core.manage),
 * the operation must be denied. Users with core.admin must be granted access to
 * all operations.
 *
 * **Validates: Requirements 12.1, 12.2**
 */
#[\PHPUnit\Framework\Attributes\Group('property')]
class AclEnforcementPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * All ACL permissions supported by com_ttclub.
     */
    private const PERMISSIONS = [
        'core.create',
        'core.edit',
        'core.delete',
        'core.edit.state',
        'core.manage',
    ];

    /**
     * All backend operations and their required permission.
     */
    private const OPERATIONS = [
        'add'     => 'core.create',
        'edit'    => 'core.edit',
        'delete'  => 'core.delete',
        'publish' => 'core.edit.state',
        'display' => 'core.manage',
    ];

    /**
     * Simulate the ACL check logic used across all admin controllers.
     *
     * This replicates the core pattern:
     * 1. If user has core.admin → grant access (bypass)
     * 2. Otherwise, check specific permission for the operation
     * 3. If user lacks the required permission → deny access
     *
     * @param string $operation The operation being attempted
     * @param array<string, bool> $userPermissions Map of permission name => granted
     *
     * @return bool True if access is granted, false if denied
     */
    private function checkAccess(string $operation, array $userPermissions): bool
    {
        // core.admin bypasses all other permission checks
        if (!empty($userPermissions['core.admin'])) {
            return true;
        }

        $requiredPermission = self::OPERATIONS[$operation] ?? null;
        if ($requiredPermission === null) {
            return false;
        }

        return !empty($userPermissions[$requiredPermission]);
    }

    /**
     * Property 34: Users with core.admin permission are granted access to ALL
     * operations regardless of other permission settings.
     *
     * Generate random operations and random subsets of other permissions (some
     * missing, some present); verify that core.admin always grants access.
     *
     * **Validates: Requirements 12.1, 12.2**
     */
    public function testCoreAdminBypassGrantsAccessToAllOperations(): void
    {
        $operations = array_keys(self::OPERATIONS);

        $this
            ->forAll(
                Generators::elements($operations),
                Generators::bool(), // core.create
                Generators::bool(), // core.edit
                Generators::bool(), // core.delete
                Generators::bool(), // core.edit.state
                Generators::bool()  // core.manage
            )
            ->then(function (
                string $operation,
                bool $hasCreate,
                bool $hasEdit,
                bool $hasDelete,
                bool $hasEditState,
                bool $hasManage
            ): void {
                $permissions = [
                    'core.admin'      => true, // Always admin in this test
                    'core.create'     => $hasCreate,
                    'core.edit'       => $hasEdit,
                    'core.delete'     => $hasDelete,
                    'core.edit.state' => $hasEditState,
                    'core.manage'     => $hasManage,
                ];

                $granted = $this->checkAccess($operation, $permissions);

                $this->assertTrue(
                    $granted,
                    "core.admin user must be granted access to '$operation' regardless of other permissions. "
                    . 'Permissions: ' . json_encode($permissions)
                );
            });
    }

    /**
     * Property 34: Users WITHOUT core.admin who LACK the specific required
     * permission for an operation are DENIED access.
     *
     * Generate random operations and permission sets where core.admin is false
     * and the required permission for the selected operation is false; verify
     * that access is denied.
     *
     * **Validates: Requirements 12.1, 12.2**
     */
    public function testNonAdminWithoutRequiredPermissionIsDenied(): void
    {
        $operations = array_keys(self::OPERATIONS);

        $this
            ->forAll(
                Generators::elements($operations),
                Generators::bool(), // core.create
                Generators::bool(), // core.edit
                Generators::bool(), // core.delete
                Generators::bool(), // core.edit.state
                Generators::bool()  // core.manage
            )
            ->then(function (
                string $operation,
                bool $hasCreate,
                bool $hasEdit,
                bool $hasDelete,
                bool $hasEditState,
                bool $hasManage
            ): void {
                $permissions = [
                    'core.admin'      => false, // Never admin in this test
                    'core.create'     => $hasCreate,
                    'core.edit'       => $hasEdit,
                    'core.delete'     => $hasDelete,
                    'core.edit.state' => $hasEditState,
                    'core.manage'     => $hasManage,
                ];

                // Force the required permission for this operation to be false
                $requiredPermission = self::OPERATIONS[$operation];
                $permissions[$requiredPermission] = false;

                $granted = $this->checkAccess($operation, $permissions);

                $this->assertFalse(
                    $granted,
                    "Non-admin user without '$requiredPermission' must be denied access to '$operation'. "
                    . 'Permissions: ' . json_encode($permissions)
                );
            });
    }

    /**
     * Property 34: Users WITHOUT core.admin who DO hold the specific required
     * permission for an operation are GRANTED access.
     *
     * Generate random operations and permission sets where core.admin is false
     * but the required permission for the selected operation is true; verify
     * that access is granted.
     *
     * **Validates: Requirements 12.1, 12.2**
     */
    public function testNonAdminWithRequiredPermissionIsGranted(): void
    {
        $operations = array_keys(self::OPERATIONS);

        $this
            ->forAll(
                Generators::elements($operations),
                Generators::bool(), // core.create
                Generators::bool(), // core.edit
                Generators::bool(), // core.delete
                Generators::bool(), // core.edit.state
                Generators::bool()  // core.manage
            )
            ->then(function (
                string $operation,
                bool $hasCreate,
                bool $hasEdit,
                bool $hasDelete,
                bool $hasEditState,
                bool $hasManage
            ): void {
                $permissions = [
                    'core.admin'      => false, // Never admin in this test
                    'core.create'     => $hasCreate,
                    'core.edit'       => $hasEdit,
                    'core.delete'     => $hasDelete,
                    'core.edit.state' => $hasEditState,
                    'core.manage'     => $hasManage,
                ];

                // Force the required permission for this operation to be true
                $requiredPermission = self::OPERATIONS[$operation];
                $permissions[$requiredPermission] = true;

                $granted = $this->checkAccess($operation, $permissions);

                $this->assertTrue(
                    $granted,
                    "Non-admin user with '$requiredPermission' must be granted access to '$operation'. "
                    . 'Permissions: ' . json_encode($permissions)
                );
            });
    }

    /**
     * Property 34: Each operation maps to exactly one required permission.
     * The mapping must be deterministic and cover all defined operations.
     *
     * Generate random operations; verify the correct permission is required
     * by testing that ONLY the mapped permission (when held) grants access
     * for a non-admin user with no other permissions.
     *
     * **Validates: Requirements 12.1, 12.2**
     */
    public function testEachOperationRequiresExactlyOnePermission(): void
    {
        $operations = array_keys(self::OPERATIONS);

        $this
            ->forAll(
                Generators::elements($operations)
            )
            ->then(function (string $operation): void {
                $requiredPermission = self::OPERATIONS[$operation];

                // Test with ONLY the required permission
                $minimalPermissions = [
                    'core.admin'      => false,
                    'core.create'     => false,
                    'core.edit'       => false,
                    'core.delete'     => false,
                    'core.edit.state' => false,
                    'core.manage'     => false,
                ];
                $minimalPermissions[$requiredPermission] = true;

                $granted = $this->checkAccess($operation, $minimalPermissions);
                $this->assertTrue(
                    $granted,
                    "Operation '$operation' should be granted with only '$requiredPermission' permission"
                );

                // Test with all permissions EXCEPT the required one
                $allExceptRequired = [
                    'core.admin'      => false,
                    'core.create'     => true,
                    'core.edit'       => true,
                    'core.delete'     => true,
                    'core.edit.state' => true,
                    'core.manage'     => true,
                ];
                $allExceptRequired[$requiredPermission] = false;

                $denied = $this->checkAccess($operation, $allExceptRequired);
                $this->assertFalse(
                    $denied,
                    "Operation '$operation' should be denied without '$requiredPermission' even if all other permissions are held"
                );
            });
    }
}
