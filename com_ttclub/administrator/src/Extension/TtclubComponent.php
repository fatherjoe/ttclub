<?php

declare(strict_types=1);

namespace Fatherjoe\Component\Ttclub\Administrator\Extension;

use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Psr\Container\ContainerInterface;

class TtclubComponent extends MVCComponent implements
    BootableExtensionInterface
{
    public function boot(ContainerInterface $container): void
    {
        // Component bootstrap logic
    }
}
