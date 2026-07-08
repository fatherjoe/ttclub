<?php

declare(strict_types=1);

namespace Joomla\CMS\Date;

/**
 * Minimal stub of Joomla\CMS\Date\Date for testing purposes.
 */
class Date
{
    private string $dateTime;

    public function __construct(?string $date = null)
    {
        $this->dateTime = $date ?? date('Y-m-d H:i:s');
    }

    public function toSql(): string
    {
        return $this->dateTime;
    }
}
