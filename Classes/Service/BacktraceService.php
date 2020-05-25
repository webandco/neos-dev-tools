<?php
namespace Webandco\DevTools\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Error\Debugger;
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
        $caller = [];

        $trace = $this->getTrace();

        foreach($trace as $k => $step){
            $ok = false;

            if(isset($step['function']) && $step['function'] == $method){
                if($class === false && !isset($step['class']) || is_null($class) || (!is_null($class) && isset($step['class']) && $step['class'] == $class)){
                    $ok = true;
                }
            }

            if($ok){
                $caller = $step;
                unset($caller['function']);
                if(isset($caller['class'])){
                    unset($caller['class']);
                }

                if(isset($trace[$k+1]) && isset($trace[$k+1]['function'])){
                    $caller['function'] = $trace[$k+1]['function'];
                }
                if(isset($trace[$k+1]) && isset($trace[$k+1]['class'])){
                    $caller['class'] = $trace[$k+1]['class'];
                }

                break;
            }
        }

        if(count($caller)) {
            return $caller;
        }

        return null;
    }

    protected function getTrace()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $this->limit);
        foreach ($trace as $index => $step) {
            if(isset($step['file']) && file_exists($step['file'])){
                $proxy = Debugger::findProxyAndShortFilePath($step['file']);
                $trace[$index]['short'] = $proxy['short'];
                $trace[$index]['proxy'] = $proxy['proxy'];
            }
        }
        return $trace;
    }
}
