<?php
namespace Webandco\DevTools\Service\Log;

use Symfony\Component\Stopwatch\Section;

/**
 * Log renderer Interface
 *
 */
class ThrowableLogRenderer implements LogRenderInterface
{
    /**
     * @param LogService $logService
     * @param mixed $what
     * @return array
     */
    public function render(LogService $logService, $what){
        $logs = [];

        /** @var Throwable $what */
        $line  = $what->getMessage().":".$what->getCode().PHP_EOL;
        $line .= $what->getFile().":".$what->getLine().PHP_EOL;
        $line .= $what->getTraceAsString();

        $logs[] = $line;

        return $logs;
    }
}
