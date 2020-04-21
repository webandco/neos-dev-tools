<?php
namespace Webandco\DevTools\Service\Stopwatch;

use Webandco\DevTools\Domain\Model\Dto\Stopwatch;

/**
 * StopwatchFactory Interface
 *
 */
interface StopwatchTreeInterface
{    /**
     * Reset the stopwatch tree
     */
    public function reset();

    public function enable();

    public function disable();

    public function startSlot(Stopwatch $instance, $name, $category);
    public function stopSlot(Stopwatch $instance, $name, $category);
}
