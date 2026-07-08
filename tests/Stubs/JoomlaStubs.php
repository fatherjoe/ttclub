<?php

declare(strict_types=1);

/**
 * Minimal Joomla framework stubs for unit/property testing.
 *
 * These stubs provide just enough of the Joomla framework interface
 * to allow component classes to be loaded and tested in isolation.
 */

namespace Joomla\Database {

    if (!class_exists(DatabaseDriver::class)) {
        abstract class DatabaseDriver
        {
            abstract public function getQuery(bool $new = false): DatabaseQuery;
            abstract public function quoteName(string $name, ?string $as = null): string;
            abstract public function setQuery($query, int $offset = 0, int $limit = 0): static;
            abstract public function loadResult(): ?string;
            abstract public function loadAssoc(): ?array;
            abstract public function loadObject(?string $class = null): ?object;
            abstract public function loadColumn(int $offset = 0): array;
            abstract public function execute(): bool;
            abstract public function insertid(): int;
            abstract public function insertObject(string $table, &$object, ?string $key = null): bool;
            abstract public function updateObject(string $table, &$object, string $key, bool $nulls = false): bool;
            abstract public function quote(string $text, bool $escape = true): string;
        }
    }

    if (!interface_exists(DatabaseInterface::class)) {
        interface DatabaseInterface
        {
            public function getQuery(bool $new = false): DatabaseQuery;
            public function quoteName(string $name, ?string $as = null): string;
            public function setQuery($query, int $offset = 0, int $limit = 0): static;
            public function loadResult(): ?string;
            public function loadObject(?string $class = null): ?object;
            public function loadColumn(int $offset = 0): array;
            public function insertObject(string $table, &$object, ?string $key = null): bool;
            public function updateObject(string $table, &$object, string $key, bool $nulls = false): bool;
            public function execute(): bool;
            public function quote(string $text, bool $escape = true): string;
        }
    }

    if (!class_exists(DatabaseQuery::class)) {
        abstract class DatabaseQuery
        {
            abstract public function select($columns): static;
            abstract public function from(string $table): static;
            abstract public function where($conditions, string $glue = 'AND'): static;
            abstract public function innerJoin(string $table): static;
            abstract public function order($columns): static;
            abstract public function setLimit(int $limit, int $offset = 0): static;
            abstract public function bind($key, &$value = null, $dataType = 'string', int $length = 0, array $driverOptions = []): static;
            abstract public function insert(string $table): static;
            abstract public function columns(array $columns): static;
            abstract public function values(string $values): static;
            abstract public function update(string $table): static;
            abstract public function set($conditions, string $glue = ','): static;
            abstract public function delete(?string $table = null): static;
        }
    }

    if (!class_exists(ParameterType::class)) {
        class ParameterType
        {
            public const INTEGER = 'int';
            public const STRING = 'string';
            public const NULL = 'null';
            public const BOOLEAN = 'bool';
            public const LARGE_OBJECT = 'lob';
        }
    }
}

namespace Joomla\CMS\Table {

    use Joomla\Database\DatabaseDriver;

    if (!class_exists(Table::class)) {
        #[\AllowDynamicProperties]
        abstract class Table
        {
            protected string $_tbl;
            protected string $_tbl_key;
            protected DatabaseDriver $_db;
            protected array $_errors = [];

            public $id = 0;

            // Common column properties (dynamically set in real Joomla Table)
            public ?string $first_name = null;
            public ?string $last_name = null;
            public ?string $name = null;
            public ?string $created = null;
            public ?string $modified = null;
            public ?int $published = null;
            public ?int $created_by = null;
            public ?int $modified_by = null;

            public function __construct(string $table, string $key, DatabaseDriver $db)
            {
                $this->_tbl = $table;
                $this->_tbl_key = $key;
                $this->_db = $db;
            }

            public function getDbo(): DatabaseDriver
            {
                return $this->_db;
            }

            public function check(): bool
            {
                return true;
            }

            public function store(bool $updateNulls = false): bool
            {
                return true;
            }

            public function load($keys = null, bool $reset = true): bool
            {
                return true;
            }

            public function delete($pk = null): bool
            {
                return true;
            }

            public function bind($src, $ignore = ''): bool
            {
                if (is_array($src) || is_object($src)) {
                    $src = (array) $src;
                    foreach ($src as $key => $value) {
                        if (property_exists($this, $key)) {
                            $this->$key = $value;
                        }
                    }
                }
                return true;
            }

            public function reset(): static
            {
                return $this;
            }

            public function setError(string $error): void
            {
                $this->_errors[] = $error;
            }

            public function getError(int $i = 0, bool $toString = true): string
            {
                return $this->_errors[$i] ?? '';
            }

            public function getTableName(): string
            {
                return $this->_tbl;
            }

            public function getKeyName(): string
            {
                return $this->_tbl_key;
            }

            public function getErrors(): array
            {
                return $this->_errors;
            }
        }
    }
}

namespace Joomla\CMS {

    if (!class_exists(Factory::class)) {
        class Factory
        {
            private static ?object $date = null;

            public static function getDate(?string $date = null, ?string $tz = null): object
            {
                return new class($date) {
                    private string $dateStr;

                    public function __construct(?string $date = null)
                    {
                        $this->dateStr = $date ?? date('Y-m-d H:i:s');
                    }

                    public function toSql(): string
                    {
                        return $this->dateStr;
                    }
                };
            }
        }
    }
}

namespace Joomla\CMS\Language {

    if (!class_exists(Text::class)) {
        class Text
        {
            public static function _(string $string): string
            {
                return $string;
            }

            public static function sprintf(string $string, ...$args): string
            {
                return sprintf($string, ...$args);
            }
        }
    }
}

namespace Joomla\CMS\MVC\Model {

    if (!class_exists(BaseDatabaseModel::class)) {
        abstract class BaseDatabaseModel
        {
        }
    }

    if (!class_exists(AdminModel::class)) {
        abstract class AdminModel extends BaseDatabaseModel
        {
        }
    }

    if (!class_exists(ListModel::class)) {
        abstract class ListModel extends BaseDatabaseModel
        {
        }
    }
}

namespace Joomla\CMS\MVC\Controller {

    if (!class_exists(BaseController::class)) {
        abstract class BaseController
        {
        }
    }

    if (!class_exists(FormController::class)) {
        abstract class FormController extends BaseController
        {
        }
    }

    if (!class_exists(AdminController::class)) {
        abstract class AdminController extends BaseController
        {
        }
    }
}
