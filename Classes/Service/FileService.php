<?php
namespace Webandco\DevTools\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Log\PsrSystemLoggerInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Utility\Files;

/**
 * @Flow\Scope("singleton")
 */
class FileService {

    /**
     * @Flow\Inject
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @Flow\Inject
     * @var PsrSystemLoggerInterface
     */
    protected $systemLogger;

    public function createFileNodePublished() {
        $settings = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Webandco.DevTools');
        $this->systemLogger->debug(json_encode($settings), LogEnvironment::fromMethodName(__METHOD__));
        if ($settings['nodePublished']['use'] === true) {
            $file = isset($settings['nodePublished']['file']) ? $settings['nodePublished']['file'] : FLOW_PATH_ROOT . '.WebandcoDevelopmentLastPublished';
            $this->systemLogger->debug(basename($file), LogEnvironment::fromMethodName(__METHOD__));
            file_put_contents($file, date('Y.m.d H:i:s') . '.' . gettimeofday()['usec']);
        }
    }
}
