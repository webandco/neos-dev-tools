<?php
namespace Webandco\DevTools\Domain\Model\Dto;

use Symfony\Component\Stopwatch\Section;
use Symfony\Component\Stopwatch\Stopwatch as SymfonyStopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Neos\Flow\Annotations as Flow;
use Webandco\DevTools\Service\BacktraceService;

/**
 * A DTO for storing information related to a stopwatch
 */
class Stopwatch extends SymfonyStopwatch
{
    const FORMAT_AS_STRING = 1;
    const FORMAT_AS_ARRAY = 2;

    protected $metaData = [];

    protected $startedEvents = [];

    /**
     * @Flow\InjectConfiguration(package="Webandco.DevTools", path="stopwatch.emitSignals")
     * @var boolean
     */
    protected $emitSignals = false;

    public function enableSignals(){
        $this->emitSignals = true;
        return $this;
    }
    public function disableSignals(){
        $this->emitSignals = false;
        return $this;
    }

    /**
     * See Symfony Stopwatch docs https://symfony.com/doc/current/components/stopwatch.html
     * This method also emits a signal StopwatchOpenSection
     * @param string|null $id
     * @return $this
     */
    public function openSection(string $id = null) {
        parent::openSection($id);
        if($this->emitSignals) {
            $this->emitStopwatchOpenSection($this, $id);
        }
        return $this;
    }

    /**
     * Calls stop() if needed using the previously given event name and starts the new event with the given name
     * The signals StopwatchStart and StopwatchStop are emitted
     * @param string|null $name Name of the event
     * @param string|null $category Category of the event
     * @return StopwatchEvent
     */
    public function restart(string $name, string $category = null) {
        if(0 < count($this->startedEvents)) {
            $this->stop($this->startedEvents[count($this->startedEvents)-1]);
        }

        return $this->start($name, $category);
    }

    /**
     * @param Stopwatch $stopwatch
     * @param string $id
     * @return void
     * @Flow\Signal
     */
    protected function emitStopwatchOpenSection(Stopwatch $stopwatch, $id) {}

    /**
     * See Symfony Stopwatch docs https://symfony.com/doc/current/components/stopwatch.html
     * Emits the signal StopwatchStart
     * @param string|null $name Name of the event
     * @return StopwatchEvent
     */
    public function start(string $name, string $category = null) {
        $this->startedEvents[] = $name;

        $res = parent::start($name, $category);
        if($this->emitSignals) {
            $this->emitStopwatchStart($this, $name, $category);
        }
        return $res;
    }

    /**
     * @param Stopwatch $stopwatch
     * @param string $name
     * @param string $category
     * @return void
     * @Flow\Signal
     */
    protected function emitStopwatchStart(Stopwatch $stopwatch, $name, $category) {}

    /**
     * See Symfony Stopwatch docs https://symfony.com/doc/current/components/stopwatch.html
     * Emits the signal StopwatchLap
     * @param string|null $name Name of the event
     * @return StopwatchEvent
     */
    public function lap(string $name){
        $res = parent::lap($name);
        if($this->emitSignals) {
            $this->emitStopwatchLap($this, $name);
        }
        return $res;
    }

    /**
     * @param Stopwatch $stopwatch
     * @param string $name
     * @return void
     * @Flow\Signal
     */
    protected function emitStopwatchLap(Stopwatch $stopwatch, $name) {}

    /**
     * See Symfony Stopwatch docs https://symfony.com/doc/current/components/stopwatch.html
     * Emits the signal StopwatchStop
     * @param string|null $name Name of the event
     * @return StopwatchEvent
     */
    public function stop(string $name=null){
        if (!is_null($name)) {
            $idx = array_search($name, $this->startedEvents);
            if (false !== $idx) {
                unset($this->startedEvents[$idx]);
                $this->startedEvents = array_values($this->startedEvents);
            }
        }
        else if (is_null($name) && 0 < count($this->startedEvents)) {
            $idx = count($this->startedEvents)-1;
            $name = $this->startedEvents[$idx];
            unset($this->startedEvents[$idx]);
        }

        $res = parent::stop($name);

        if($this->emitSignals) {
            $this->emitStopwatchStop($this, $name);
        }

        return $res;
    }

    /**
     * @param Stopwatch $stopwatch
     * @param string $name
     * @return void
     * @Flow\Signal
     */
    protected function emitStopwatchStop(Stopwatch $stopwatch, $name) {}

    /**
     * Get the number of laps already occured for a given event
     * @param string|null $name Name of the event
     * @return int
     */
    public function countLaps($name){
        $event = $this->getEvent($name);
        return count($event->getPeriods());
    }

    /**
     * See Symfony Stopwatch docs https://symfony.com/doc/current/components/stopwatch.html
     * This method also emits a signal StopwatchStopSection
     * @param string|null $id
     * @return $this
     */
    public function stopSection(string $id = null){
        parent::stopSection($id);
        if($this->emitSignals) {
            $this->emitStopwatchStopSection($this, $id);
        }
        return $this;
    }

    /**
     * @param Stopwatch $stopwatch
     * @param string $id
     * @return void
     * @Flow\Signal
     */
    protected function emitStopwatchStopSection(Stopwatch $stopwatch, $id) {}

    /**
     * Return the metadata object for a given value or all metadata if $name is null
     *
     * @param string|null $name Metadata name
     * @return mixed|null
     */
    public function getEventMetadata(string $name=null){
        if(is_null($name)){
            return $this->metaData;
        }

        if (!isset($this->metaData[$name])) {
            return null;
        }

        return $this->metaData[$name];
    }
    /**
     * Set the metadata object for a given value
     *
     * @param string|null $name Metadata name
     * @param mixed $data The corresponding metadata
     * @return mixed|null
     */
    public function setEventMetadata(string $name, $data){
        $this->metaData[$name] = $data;
        return $this;
    }

    /**
     * Return the duration in milliseconds for the whole stopwatch over all events
     * @return int
     */
    public function getDuration(){
        $duration = 0;

        /** @var Section $section */
        foreach($this->getSections() as $section){
            /** @var StopwatchEvent $event */
            foreach($section->getEvents() as $event){
                $duration += $event->getDuration();
            }
        }

        return $duration;
    }

    /**
     * Compute the estimated time in milliseconds for a given event
     * @param string $name Event name
     * @param int $all The number of all elements that need to be processed
     * @param int $finished (optional) The number of already processed elements, if none given the lap count for this event is used
     * @return int
     */
    public function eta(string $name, int $all, int $finished = -1){
        $event = $this->getEvent($name);

        if (-1 == $finished) {
            $finished = $this->countLaps($name);
        }

        if (0 == $finished) {
            return INF;
        }

        $pending = $all - $finished;

        $dur = $event->getDuration();

        $eta = $pending*$dur/$finished;
        return floor($eta);
    }

    /**
     * Format a given duration in milliseconds to human readable format HH:MM:SS.ms
     * @param int $durationInMs Duration in milliseconds
     * @param int $returnFormat Return as string in human readable format or array
     * @return array|string
     */
    public static function format($durationInMs, $returnFormat=self::FORMAT_AS_STRING){
        $durSeconds = floor($durationInMs/1000);

        $hours = floor($durSeconds/60/60);
        $min = floor(($durSeconds-$hours*60*60)/60);
        $sec = ($durSeconds-$min*60);

        $ms = floor($durationInMs-$durSeconds*1000);

        switch ($returnFormat) {
            case self::FORMAT_AS_ARRAY:
                return [$ms, $sec, $min, $hours];
            default:
                return sprintf("%02d:%02d:%02d.%03d", $hours,$min,$sec,$ms);;
        }
    }

}
