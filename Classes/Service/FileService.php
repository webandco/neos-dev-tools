<?php
namespace Webandco\DevTools\Service;

use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\ViewHelper\Exception\InvalidVariableException;

/**
 * @Flow\Scope("singleton")
 */
class FileService {

    /**
     * @Flow\InjectConfiguration(package="Webandco.DevTools")
     * @var array
     */
    protected $configuration;

    public function createFileNodePublished() {
        if ($this->configuration['nodePublished']['use'] === true) {
            $file = isset($this->configuration['nodePublished']['file']) ? $this->configuration['nodePublished']['file'] : FLOW_PATH_ROOT . '.WebandcoNeosDevToolsLastPublished';
            if (is_writeable(dirname($file))) {
                file_put_contents($file, date('Y.m.d H:i:s') . '.' . gettimeofday()['usec']);
            } else {
                throw new InvalidVariableException('The configured path is not writable', 1561991295);
            }
        }
    }
}
