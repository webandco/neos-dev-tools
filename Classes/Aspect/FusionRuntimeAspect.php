<?php

namespace Webandco\DevTools\Aspect;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;

/**
 * The aspect uses RuntimeContentCache::enter and RuntimeContentCache::leave to log the
 * time a fusionPath needs to render
 * During shutdown the result is logged to the System log via `wLog()`
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class FusionRuntimeAspect
{
    protected $stats = [];
    protected $renderStack = [];

    public function shutdownObject() {
        usort($this->stats, fn($a, $b) => $a['avgruntime']*$a['cnt'] <=> $b['avgruntime']*$b['cnt']);
        foreach ($this->stats as $stat){
            wLog('    ',$stat['cnt'], $stat['avgruntime'], $stat['fusionPath']);
        }
    }

    public function getStats(){
        return $this->stats;
    }

    /**
     * @Flow\Before("setting(Webandco.DevTools.fusion.enableRenderStats) && method(Neos\Fusion\Core\Cache\RuntimeContentCache->enter())")
     * @param JoinPointInterface $joinPoint The current join point
     * @return void
     */
    public function startRenderStats(JoinPointInterface $joinPoint)
    {
        $fusionPath = $joinPoint->getMethodArgument('fusionPath');

        $s = microtime(true);
        $this->renderStack[$fusionPath][] = $s;
    }

    /**
     * @Flow\After("setting(Webandco.DevTools.fusion.enableRenderStats) && method(Neos\Fusion\Core\Cache\RuntimeContentCache->leave())")
     * @param JoinPointInterface $joinPoint The current join point
     * @return void
     */
    public function endRenderStats(JoinPointInterface $joinPoint)
    {
        $evaluateContext = $joinPoint->getMethodArgument('evaluateContext');
        $fusionPath = $evaluateContext['fusionPath'];
        $s = \array_pop($this->renderStack[$fusionPath]);

        $runtime = microtime(true)-$s;
        if(!isset($this->stats[$fusionPath])){
            $this->stats[$fusionPath] = [
                'cnt' => 0,
                'fusionPath' => $fusionPath,
                'runtimes' => [],
                'avgruntime' => null,
            ];
        }

        $this->stats[$fusionPath]['cnt']++;
        $this->stats[$fusionPath]['runtimes'][] = $runtime;
        if($this->stats[$fusionPath]['avgruntime'] === null){
            $this->stats[$fusionPath]['avgruntime'] = $runtime;
        }
        else{
            $this->stats[$fusionPath]['avgruntime'] += $runtime;
            $this->stats[$fusionPath]['avgruntime'] /= 2.0;
        }
    }
}
