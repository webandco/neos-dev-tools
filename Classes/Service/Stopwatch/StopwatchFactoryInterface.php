<?php
namespace Webandco\DevTools\Service\Stopwatch;

use Webandco\DevTools\Domain\Model\Dto\Stopwatch;

/**
 * StopwatchFactory Interface
 *
 */
interface StopwatchFactoryInterface
{
    /**
     * @param bool $morePrecision
     * @return Stopwatch
     */
    public function create(bool $morePrecision = false);

    /**
     * Remove all currently known stopwatches from the factory
     */
    public function reset();

    /**
     * Returns all known instances of Context.
     *
     * @return array<Stopwatch>
     */
    public function getInstances();
}
