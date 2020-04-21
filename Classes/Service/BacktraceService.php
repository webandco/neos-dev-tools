<?php
namespace Webandco\DevTools\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy;
use Neos\Flow\ObjectManagement\Proxy\ProxyInterface;

/**
 * @Flow\Scope("singleton")
 */
class BacktraceService {
    /**
     * @Flow\InjectConfiguration(package="Webandco.DevTools", path="backtrace.limit")
     * @var int
     */
    protected $limit = 50;

    public function getCaller($method, $class=null, $ignoreAspect=true) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $this->limit);

        $callFound = false;

        $caller = null;

        $endLoop = count($trace)-1;
        if (false == $ignoreAspect) {
            count($trace)-2;
        }

        for ($i=0;$i<$endLoop;$i++) {
            $t = $trace[$i];
            $c = $trace[$i+1];

            if(isset($t['class']) && is_a($t['class'], DependencyProxy::class, true)){
                continue;
            }
            if(isset($t['function']) && in_array($t['function'], ['call_user_func', 'call_user_func_array', 'Flow_Aop_Proxy_invokeJoinPoint'])){
                continue;
            }

            if(!$callFound){
                if (!isset($t['function'])) {
                    continue;
                }

                if ($t['function'] != $method) {
                    continue;
                }

                if (is_null($class)) {
                    $caller = [
                        'file'     => $t['file'],
                        'line'     => $t['line'],
                        'function' => $c['function'],
                    ];
                    if(isset($c['class'])){
                        $caller['class'] = $c['class'];
                    }

                    break;
                }
                else{
                    // flow adds a postfix `_original` to the classname and the "expected" inheritance is turned around
                    // $class refers to proxy class and
                    // the proxy class ($class) is a child of the original ($t['class'])
                    if (is_a($class, $t['class'], true)) {
                        $callFound = true;

                        // either the call is from an aspect or not

                        // check if the call is made by the proxy class
                        if (isset($c['class']) && is_subclass_of($c['class'], ProxyInterface::class, true)) {
                            // proxy call - this means flow interfered the direct call
                            // who called the proxy method

                            $i++;  // ignore proxy call and search deeper in the trace
                            continue;
                        }
                        else {
                            // seems to be a direct call
                            if (!isset($t['file'])) {
                                // seems to be a call via dependency proxy
                                if ($i+3 < $endLoop) {
                                    $t = $trace[$i+2];
                                    $c = $trace[$i+3];
                                }
                                else {
                                    // could not find a valid caller - a use case is missing here
                                    break;
                                }
                            }

                            $caller = [
                                'file' => $t['file'],
                                'line' => $t['line'],
                                'function' => $c['function'],
                            ];

                            if (isset($c['class'])) {
                                $caller['class'] = $c['class'];
                            }

                            break;
                        }
                    }
                }
            }
            else{
                // searching for the call to the proxy class

                if (!isset($t['function'])) {
                    continue;
                }
                if ($ignoreAspect) {
                    if ($t['function'] != $method) {
                        continue;
                    }
                }
                else if (0 !== strpos($t['function'], 'Flow_Aop_Proxy_')) {
                    continue;
                }

                if (!isset($t['class'])) {
                    continue;
                }

                if ($t['class'] == $class) {
                    if ($ignoreAspect) {
                        $caller = [
                            'file'     => $t['file'],
                            'line'     => $t['line'],
                            'function' => $c['function'],
                            'class'    => $c['class'],
                        ];
                    }
                    else {
                        $caller = [
                            'file'     => $c['file'],
                            'line'     => $c['line'],
                            'function' => $trace[$i+2]['function'],
                            'class'    => $trace[$i+2]['class'],
                        ];
                    }
                    break;
                }
            }
        }

        if(isset($caller['class'])) {
            $postfix = "_Original";
            if (substr($caller['class'], -strlen($postfix)) === $postfix) {
                $caller['class'] = substr($caller['class'], 0, -strlen($postfix));
            }
        }

        return $caller;
    }
}
