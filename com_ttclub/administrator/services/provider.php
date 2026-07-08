<?php

declare(strict_types=1);

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Fatherjoe\Component\Ttclub\Administrator\Extension\TtclubComponent;
use Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtParser;
use Fatherjoe\Component\Ttclub\Administrator\Service\ClickTtUrlBuilder;
use Fatherjoe\Component\Ttclub\Administrator\Service\ScheduleService;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('\\Fatherjoe\\Component\\Ttclub'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Fatherjoe\\Component\\Ttclub'));

        $container->set(
            ComponentInterface::class,
            function (Container $container): TtclubComponent {
                $component = new TtclubComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                return $component;
            }
        );

        $container->set(
            ScheduleService::class,
            function (Container $container): ScheduleService {
                $db = $container->get(DatabaseInterface::class);
                $parser = new ClickTtParser();
                $urlBuilder = new ClickTtUrlBuilder();

                $params = ComponentHelper::getParams('com_ttclub');
                $cacheDuration = (int) $params->get('schedule_cache_duration', 259200);

                return new ScheduleService($db, $parser, $urlBuilder, $cacheDuration);
            }
        );
    }
};
