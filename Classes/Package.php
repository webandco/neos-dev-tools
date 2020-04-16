<?php
namespace Webandco\DevTools {

    use Neos\ContentRepository\Domain\Service\PublishingService;
    use Neos\Flow\Core\Bootstrap;
    use Neos\Flow\Package\Package as BasePackage;

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
        }
    }
}

namespace {

    use Webandco\DevTools\Service\Log\LogService;

    function wLog(){
        $args = func_get_args();

        $logService = new LogService();

        return call_user_func_array([$logService, 'wLog'], $args);
    }
}
