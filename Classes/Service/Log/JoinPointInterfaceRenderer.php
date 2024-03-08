<?php
namespace Webandco\DevTools\Service\Log;

use Neos\Flow\Aop\JoinPointInterface;
use Neos\Utility\ObjectAccess;

/**
 * Log renderer Interface
 *
 */
class JoinPointInterfaceRenderer implements LogRenderInterface
{
    /**
     * @param LogService $logService
     * @param JoinPointInterface $what
     * @return array
     */
    public function render(LogService $logService, $what) {
        $result = [];

        /** @var JoinPointInterface $what */
        $result[] = '[ {'.\get_class($what).'} ' . $what->getClassName() . '::' . $what->getMethodName() . '(';
        $result = \array_merge($result, $what->getMethodArguments());
        $result[] = ')]';

        return $result;
    }
}
