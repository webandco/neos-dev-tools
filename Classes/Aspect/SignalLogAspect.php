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
class SignalLogAspect
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @Flow\Inject
     * @var LogService
     */
    protected $logService;

    /**
     *
     * @Flow\InjectConfiguration(package="Webandco.DevTools", path="log.signal.enabled")
     * @var bool
     */
    protected $enabled = false;

    /**
     *
     * @Flow\InjectConfiguration(package="Webandco.DevTools", path="log.signal.regex")
     * @var string
     */
    protected $regex = '/.*/';

    /**
     * @Flow\Inject(lazy=false)
     * @var Bootstrap
     */
    //protected $bootstrap;

    /**
     * Passes the signal over to the Dispatcher
     *
     * @Flow\Before("setting(Webandco.DevTools.log.signal.enabled) && methodAnnotatedWith(Neos\Flow\Annotations\Signal)")
     * @param JoinPointInterface $joinPoint The current join point
     * @return void
     */
    public function logSignal(JoinPointInterface $joinPoint)
    {
        $signalClassName = $joinPoint->getClassName();
        $signalArguments = $joinPoint->getMethodArguments();
        $signalName = lcfirst(str_replace('emit', '', $joinPoint->getMethodName()));

        if(1 !== preg_match($this->regex, $signalClassName.'::'.$signalName)){
            return;
        }

        $slots = $this->dispatcher->getSlots($signalClassName, $signalName);

        $loggedArguments = [];
        foreach($signalArguments as $argument){
            if(count($loggedArguments)){
                $loggedArguments[] = ',';
            }
            switch(\gettype($argument)){
                case 'resource': $loggedArguments[] = 'resource'; break;
                case 'resource (closed)': $loggedArguments[] = 'resource (closed)'; break;
                case 'object':
                    if($argument instanceof \stdClass){
                        $loggedArguments[] = $argument;
                    }
                    else{
                        $loggedArguments[] = get_class($argument);
                    }
                    break;
                default:
                    $loggedArguments[] = $argument;
            }
        }

        $loggedSlots = [];
        foreach($slots as $slotInformation){
            if (isset($slotInformation['object'])) {
                $object = $slotInformation['object'];
                $loggedSlots[] = (is_object($object) ? get_class($object) : $object).'::'.$slotInformation['method'];
            } elseif (substr($slotInformation['method'], 0, 2) === '::') {
                if (!$this->objectManager) {
                    if (is_callable($slotInformation['class'] . $slotInformation['method'])) {
                        $object = $slotInformation['class'];
                    } else {
                        continue;
                    }
                } else {
                    $object = $this->objectManager->getClassNameByObjectName($slotInformation['class']);
                }
                $loggedSlots[] = (is_object($object) ? get_class($object) : $object).$slotInformation['method'];
            } else {
                if (!$this->objectManager->isRegistered($slotInformation['class'])) {
                    continue;
                }

                $loggedSlots[] = $slotInformation['class'].'::'.$slotInformation['method'];
            }
        }

        $this->logService->pretty(false)
                         ->wLog("signal emitted: ")
                         ->wLog($signalClassName.'::'.$signalName.'(')
                         ->wLog(...$loggedArguments)
                         ->wLog(')->')->wLog(count($loggedSlots) ? $loggedSlots : 'no slots defined')
                         ->eol();
    }

    /**
     * Around advice, logs time a signal took to process
     *
     * @param JoinPointInterface $joinPoint
     * @return mixed Result of the target method
     * @Flow\Around("setting(Webandco.DevTools.log.signal.enabled) && method(Neos\Flow\SignalSlot\Dispatcher->dispatch())")
     */
    public function logSignalDispatchTime(JoinPointInterface $joinPoint) {
        $start = microtime(true);
        $joinPoint->getClassName();
        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);
        $end = microtime(true);
        $fromScriptStart = $end-$_SERVER['REQUEST_TIME_FLOAT'];
        $time = microtime(true)-$start;
        $percent = $time*100/$fromScriptStart;

        $signalDestination = $joinPoint->getClassName().'::'.$joinPoint->getMethodName();

        if(1 !== preg_match($this->regex, $signalDestination)){
            return;
        }

        $this->logService->pretty(false)
            ->wLog('signal ', $signalDestination, ' took ' . $time.' s ( ' . $percent . '%)')
            ->eol();

        return $result;
    }
}
