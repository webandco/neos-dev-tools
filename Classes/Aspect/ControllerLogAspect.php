<?php

namespace Webandco\DevTools\Aspect;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\SignalSlot\Dispatcher;
use Webandco\DevTools\Service\Log\LogService;

/**
 * Aspect which logs signals
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class ControllerLogAspect
{
    /**
     * @Flow\Inject
     * @var LogService
     */
    protected $logService;

    /**
     *
     * @Flow\InjectConfiguration(package="Webandco.DevTools", path="log.controller.regex")
     * @var string
     */
    protected $regex = '/.*/';

    /**
     * Around advice, logs action duration
     *
     * @param JoinPointInterface $joinPoint
     * @return mixed Result of the target method
     * @Flow\Around("setting(Webandco.DevTools.log.controller.enabled) && within(Neos\Flow\Mvc\Controller\ControllerInterface) && method(.*->.*Action())")
     */
    public function logControllerActionTime(JoinPointInterface $joinPoint) {
        if(empty($this->regex)){
            $this->regex = '/.*/';
        }

        $start = microtime(true);
        $joinPoint->getClassName();
        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);
        $now = microtime(true);
        $fromScriptStart = $now-$_SERVER['REQUEST_TIME_FLOAT'];
        $time = microtime(true)-$start;
        $percent = $time*100/$fromScriptStart;

        $controllerMethod = $joinPoint->getClassName().'::'.$joinPoint->getMethodName();

        if(1 === preg_match($this->regex, $controllerMethod)){
            // excludes view rendering
            $this->logService->pretty(false)
                ->wLog('action method', $controllerMethod, ' took ' . $time.' s (' . \number_format($percent, 2) . '%)')
                ->eol();
        }

        return $result;
    }

    /**
     * Around advice, logs action duration
     *
     * @param JoinPointInterface $joinPoint
     * @return mixed Result of the target method
     * @Flow\Around("setting(Webandco.DevTools.log.controller.enabled) && within(Neos\Flow\Mvc\Controller\ControllerInterface) && method(.*->processRequest())")
     */
    public function logControllerProcessRequestTime(JoinPointInterface $joinPoint) {
        if(empty($this->regex)){
            $this->regex = '/.*/';
        }

        $start = microtime(true);
        $joinPoint->getClassName();
        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);
        $now = microtime(true);
        $fromScriptStart = $now-$_SERVER['REQUEST_TIME_FLOAT'];
        $time = microtime(true)-$start;
        $percent = $time*100/$fromScriptStart;

        $controllerMethod = $joinPoint->getClassName().'::'.$joinPoint->getMethodName();

        if(1 === preg_match($this->regex, $controllerMethod)){
            // includes view rendering
            $this->logService->pretty(false)
                ->wLog('processRequest with view rendering', $controllerMethod, ' took ' . $time.' s (' . \number_format($percent, 2) . '%)')
                ->eol();
        }

        return $result;
    }

}
