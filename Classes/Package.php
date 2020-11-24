<?php
namespace Webandco\DevTools {

    use Neos\ContentRepository\Domain\Service\PublishingService;
    use Neos\Flow\Configuration\ConfigurationManager;
    use Neos\Flow\Core\Bootstrap;
    use Neos\Flow\Log\Psr\Logger;
    use Neos\Flow\Log\PsrLoggerFactoryInterface;
    use Neos\Flow\ObjectManagement\ObjectManagerInterface;
    use Neos\Flow\Package\Package as BasePackage;
    use Webandco\DevTools\Aspect\SignalLogAspect;
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
                function (ConfigurationManager $configurationManager) use ($packageKey, $dispatcher, $bootstrap) {
                    $signalWiring = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $packageKey . '.stopwatch.tree.wireSignals');
                    if ($signalWiring === true) {
                        $dispatcher->connect(Stopwatch::class, 'stopwatchStart', StopwatchTree::class, 'startSlot');
                        $dispatcher->connect(Stopwatch::class, 'stopwatchStop', StopwatchTree::class, 'stopSlot');
                    }

                    $signalLogEnabled = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $packageKey . '.log.signal.enabled');
                    if($signalLogEnabled){
                        $explicitSignals = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $packageKey . '.log.signal.explicitSignals');
                        foreach ($explicitSignals as $explicitSignal){
                            //$signalLogAspect = $bootstrap->getObjectManager()->getObjectNameByClassName(SignalLogAspect::class);

                            $signalClass = $explicitSignal['signalClass'];
                            $signalName = $explicitSignal['signalName'];
/*
                            $dispatcher->connect($signalClass, $signalName, function() use($bootstrap){
                                $args = \func_get_args();

                                \error_log("signal: ".$args[count($args)-1]);

                                $logger = $this->getSystemLoggerIfAvailable($bootstrap);
                                if($logger){
                                    $logger->debug('SIGNAL: '.$args[count($args)-1]);
                                }

                                if($bootstrap->getObjectManager()->isRegistered(SignalLogAspect::class)){
                                    $signalLogAspect = $bootstrap->getObjectManager()->get(SignalLogAspect::class);
                                    \call_user_func_array([$signalLogAspect, 'signalSlot'], $args);
                                }
                                else{
                                    \error_log("Signal emmited early stage : ". $args[count($args)-1]);
                                }
                            });
*/
                        };
                    }
                }
            );
        }

        protected function getSystemLoggerIfAvailable(Bootstrap $bootstrap){
            $loggerFactory = null;

            $instances = $bootstrap->getEarlyInstances();
            if(isset($instances[ObjectManagerInterface::class])){
                $objectManager = $bootstrap->getObjectManager();
                $loggerFactory = $objectManager->get(PsrLoggerFactoryInterface::class);
            }
            else if(isset($instances[PsrLoggerFactoryInterface::class])){
                $loggerFactory = $bootstrap->getEarlyInstance(PsrLoggerFactoryInterface::class);
            }

            if($loggerFactory){
                return $loggerFactory->get('systemLogger');
            }

            return null;
        }
    }
}

namespace {

    use Webandco\DevTools\Service\Log\LogService;

    /**
     * @return LogService
     */
    function wLog(){
        $args = func_get_args();

        $logService = new LogService();

        return call_user_func_array([$logService, 'wLog'], $args);
    }
}
