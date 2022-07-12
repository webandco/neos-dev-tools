<?php
namespace Webandco\DevTools {

    use Neos\ContentRepository\Domain\Service\PublishingService;
    use Neos\Flow\Configuration\ConfigurationManager;
    use Neos\Flow\Core\Bootstrap;
    use Neos\Flow\Package\Package as BasePackage;
    use Webandco\DevTools\Domain\Model\Dto\Stopwatch;
    use Webandco\DevTools\Service\FileService;
    use Webandco\DevTools\Service\Stopwatch\StopwatchTree;

    class Package extends BasePackage
    {
        /**
         * @param Bootstrap $bootstrap The current bootstrap
         * @return void
         */
        public function boot(Bootstrap $bootstrap)
        {
            $dispatcher = $bootstrap->getSignalSlotDispatcher();
            $dispatcher->connect(PublishingService::class, 'nodePublished', FileService::class, 'createFileNodePublished');

            $packageKey = $this->getPackageKey();
            $dispatcher->connect(
                ConfigurationManager::class,
                'configurationManagerReady',
                function (ConfigurationManager $configurationManager) use ($packageKey, $dispatcher) {
                    $signalWiring = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $packageKey . '.stopwatch.tree.wireSignals');
                    if ($signalWiring === true) {
                        $dispatcher->connect(Stopwatch::class, 'stopwatchStart', StopwatchTree::class, 'startSlot');
                        $dispatcher->connect(Stopwatch::class, 'stopwatchStop', StopwatchTree::class, 'stopSlot');
                    }
                }
            );
        }
    }
}

namespace {

    use Webandco\DevTools\Service\Log\LogService;
    use Webandco\DevTools\Service\Log\LogServiceFluentTerminator;

    /**
     * @return LogService
     */
    function wLog(){
        $args = func_get_args();

        $logService = new LogService();

        //return call_user_func_array([$logService, 'wLog'], $args);
        call_user_func_array([$logService, 'wLog'], $args);

        return new LogServiceFluentTerminator();
    }
}
