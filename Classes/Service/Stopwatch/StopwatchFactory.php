<?php
namespace Webandco\DevTools\Service\Stopwatch;

use Webandco\DevTools\Domain\Model\Dto\Stopwatch;
use Neos\Flow\Annotations as Flow;

/**
 * StopwatchFactory
 * @Flow\Scope("singleton")
 */
class StopwatchFactory
{
    /**
     * @var array<Stopwatch>
     */
    protected $stopwatchInstances = [];

    /**
     * @param bool $morePrecision
     * @return Stopwatch
     */
    public function create(bool $morePrecision = false){
        $stopwatch = new Stopwatch($morePrecision);
        $this->stopwatchInstances[] = $stopwatch;

        $this->emitStopwatchCreated($stopwatch);

        return $stopwatch;
    }

    /**
     * @param Stopwatch $stopwatch
     * @return void
     * @Flow\Signal
     */
    protected function emitStopwatchCreated(Stopwatch $stopwatch) {}

    /**
     * Remove all currently known stopwatches from the factory
     */
    public function reset(){
        $this->stopwatchInstances = [];
    }

    /**
     * Returns all known instances of Context.
     *
     * @return array<Stopwatch>
     */
    public function getInstances(){
        return $this->stopwatchInstances;
    }
}
