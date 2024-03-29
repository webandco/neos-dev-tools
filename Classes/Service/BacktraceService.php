<?php
namespace Webandco\DevTools\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Error\Debugger;

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
        $caller = [];

        $this->iterateTrace(function($k, $step, $trace) use(&$caller, $method, $class, $ignoreAspect){
            $ok = false;

            if(isset($step['function']) && $step['function'] == $method){
                $stepClass = $step['class'] ?? null;
                if(empty($stepClass)){
                    if(empty($class)){
                        $ok = true;
                    }
                } elseif(\is_a($step['class'], $class)) {
                    $ok = true;
                }
            }

            if($ok){
                $caller = $step;
                unset($caller['function'], $caller['class']);

                if(isset($trace[$k+1]) && isset($trace[$k+1]['function'])){
                    $caller['function'] = $trace[$k+1]['function'];
                }
                if(isset($trace[$k+1]) && isset($trace[$k+1]['class'])){
                    $caller['class'] = $trace[$k+1]['class'];
                }

                return false;
            }

            return true;
        }, true);

        if(count($caller)) {
            return $caller;
        }

        return null;
    }

    protected function iterateTrace(\Closure $closure, bool $withProxy = false)
    {
        $start = microtime(true);
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $this->limit);
        foreach ($trace as $index => $step) {
            if($withProxy && isset($step['file']) && file_exists($step['file'])){
                $proxy = Debugger::findProxyAndShortFilePath($step['file']);
                $trace[$index]['short'] = $proxy['short'];
                $trace[$index]['proxy'] = $proxy['proxy'];
            }

            if(!$closure($index, $trace[$index], $trace)){
                break;
            }
        }
    }
}
