<?php

declare(strict_types=1);

namespace Joomla\CMS;

/**
 * Minimal stub of Joomla\CMS\Factory for testing purposes.
 */
class Factory
{
    public static function getDate(?string $date = null, $tz = null): Date\Date
    {
        return new Date\Date($date);
    }
}
