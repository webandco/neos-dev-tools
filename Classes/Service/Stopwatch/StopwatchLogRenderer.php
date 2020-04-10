<?php
namespace Webandco\DevTools\Service\Stopwatch;

use Symfony\Component\Stopwatch\Section;
use Webandco\DevTools\Service\Log\LogRenderInterface;
use Webandco\DevTools\Service\Log\LogService;

/**
 * Log renderer Interface
 *
 */
class StopwatchLogRenderer implements LogRenderInterface
{
    /**
     * @param LogService $logService
     * @param mixed $what
     * @return array
     */
    public function render(LogService $logService, $what){
        $logs = [];

        /** @var Stopwatch $what */
        $logs[] = get_class($what).' '.$what->format($what->getDuration()).PHP_EOL;
        $metaData = $what->getEventMetadata();
        if(count($metaData)) {
            $logs[] = ' Meta:'.PHP_EOL;
            foreach ($metaData as $name => $data) {
                $logs[] = '  '.$name.'=';
                $logs[] = $data;
                $logs[] = PHP_EOL;
            }
        }
        $logs[] = ' Events:'.PHP_EOL;
        foreach($what->getSections() as $sname => $section){
            /** @var Section $section */
            foreach ($section->getEvents() as $name => $event) {
                $logs[] = '  ';
                if ($sname != '__root__'){
                    $logs[] = $sname.'.'.$name;
                } else {
                    $logs[] = $name;
                }
                $logs[] = $what->format($event->getDuration());
                $logs[] = ':';
                $logs[] = sprintf("%.02F MiB",$event->getMemory()/1024/1024);
                $logs[] = PHP_EOL;
            }
        }

        return $logs;
    }
}
