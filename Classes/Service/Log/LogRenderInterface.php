<?php
namespace Webandco\DevTools\Service\Log;

/**
 * Log renderer Interface
 *
 */
interface LogRenderInterface
{
    /**
     * @param LogService $logService
     * @param mixed $what
     * @return array
     */
    public function render(LogService $logService, $what);
}
