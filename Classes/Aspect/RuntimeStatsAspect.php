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
class RuntimeStatsAspect
{
    /**
     * @Flow\Inject
     * @var LogService
     */
    protected $logService;

    protected $stats = [];
    protected $executionLog = [];

    /**
     * Before advice, logs methods defined via settings
     *
     * @param JoinPointInterface $joinPoint
     * @Flow\Before("setting(Webandco.DevTools.runtimeStats.enabled) && filter(Webandco\DevTools\Aop\Pointcut\RuntimeStatsBeforeFilter)")
     */
    public function beforeLogRuntimeTime(JoinPointInterface $joinPoint) {
        $start = microtime(true);
        /*
        $fromScriptStart = $start-$_SERVER['REQUEST_TIME_FLOAT'];
        $time = microtime(true)-$start;
        $percent = $time*100/$fromScriptStart;
        */
        $method = $joinPoint->getClassName().'::'.$joinPoint->getMethodName();
        $this->executionLog[] = $method;

        if(!isset($this->stats[$method])){
            $this->stats[$method] = [
                'starts' => [],
                'durations' => [],
                'cnt' => 0,
            ];
        }
        $this->stats[$method]['starts'][] = $start;
        $this->stats[$method]['cnt']++;
    }

    /**
     * Before advice, logs methods defined via settings
     *
     * @param JoinPointInterface $joinPoint
     * @Flow\After("setting(Webandco.DevTools.runtimeStats.enabled) && filter(Webandco\DevTools\Aop\Pointcut\RuntimeStatsAfterFilter)")
     */
    public function afterLogRuntimeTime(JoinPointInterface $joinPoint) {
        $end = microtime(true);
        $method = $joinPoint->getClassName().'::'.$joinPoint->getMethodName();

        $start = \array_pop($this->stats[$method]['starts']);
        \array_pop($this->executionLog);

        $duration = $end-$start;
        $this->stats[$method]['avgDuration']['durations'] = $duration;
        $avgDuration = \array_sum($this->stats[$method]['durations'])/\max(1, \count($this->stats[$method]['durations']));

        $fromScriptStart = $start-$_SERVER['REQUEST_TIME_FLOAT'];
        $time = microtime(true)-$start;
        $percent = $time*100/$fromScriptStart;

        $i = count($this->executionLog);
        $seperator = 1 < $i ? \sprintf('%'.$i.'s', ' ') : '';

        // excludes view rendering
        $msg = \sprintf('runtime stats %s%s took %.3f s (%.3f) duration (%.3f s / avg %.3f s) called %d times', $seperator, $method, $time, $percent, $duration, $avgDuration, $this->stats[$method]['cnt']);
        $this->logService->pretty(false)->wLog($msg)->eol();
    }
}
