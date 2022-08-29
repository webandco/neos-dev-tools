<?php
namespace Webandco\DevTools\Service\Log;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Error\Debugger;
use Neos\Flow\Log\Psr\Logger;
use Neos\Flow\Log\PsrLoggerFactoryInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Webandco\DevTools\Service\BacktraceService;

class LogServiceFluentTerminator
{
    public function __call($name, $arguments)
    {
        $result = \call_user_func_array([LogService::getActiveInstance(), $name], $arguments);

        if($result instanceof LogService){
            return $this;
        }
    }

    public function __destruct()
    {
        $instance = LogService::getActiveInstance();
        if($instance){
            $instance->eol();
        }
    }
}
