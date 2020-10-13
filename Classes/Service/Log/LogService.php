<?php
namespace Webandco\DevTools\Service\Log;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Error\Debugger;
use Neos\Flow\Log\Psr\Logger;
use Neos\Flow\Log\PsrSystemLoggerInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Psr\Log\LogLevel;
use Webandco\DevTools\Service\BacktraceService;

class LogService
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var PsrSystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var BacktraceService
     */
    protected $backtraceService;

    /**
     * @Flow\InjectConfiguration(package="Webandco.DevTools", path="log.enabled")
     * @var boolean
     */
    protected $enabled = false;

    /**
     * @Flow\InjectConfiguration(package="Webandco.DevTools", path="log.caller")
     * @var boolean
     */
    protected $caller = false;

    /**
     * Debug level to use for Systemlogger
     * @Flow\InjectConfiguration(package="Webandco.DevTools", path="log.level")
     * @var boolean
     */
    protected $level = 'debug';

    /**
     * Enable or disable color output. Can also be used to determine which color to use for all log output.
     * @Flow\InjectConfiguration(package="Webandco.DevTools", path="log.color")
     * @var boolean
     */
    protected $color = false;

    /**
     * Complex objects are printed using JSON_PRETTY_PRINT.
     * @Flow\InjectConfiguration(package="Webandco.DevTools", path="log.pretty")
     * @var boolean
     */
    protected $pretty;

    /**
     * Indent the log messages by the depth given by debug_backtrace
     * @Flow\InjectConfiguration(package="Webandco.DevTools", path="log.callDepth.enabled")
     * @var boolean
     */
    protected $callDepth;
    /**
     * Indent the log messages by the depth given by debug_backtrace
     * @Flow\InjectConfiguration(package="Webandco.DevTools", path="log.callDepth.separator")
     * @var float
     */
    protected $callDepthSeparator = ' ';
    /**
     * Indent the log messages by the depth given by debug_backtrace
     * @Flow\InjectConfiguration(package="Webandco.DevTools", path="log.callDepth.factor")
     * @var float
     */
    protected $callDepthFactor = 1.0;

    /**
     * @var boolean
     */
    protected $bold = false;
    /**
     * @var boolean
     */
    protected $italic = false;
    /**
     * @var boolean
     */
    protected $underline = false;
    /**
     * @var boolean
     */
    protected $blink = false;
    /**
     * @var boolean
     */
    protected $background = false;

    /**
     *
     * @Flow\InjectConfiguration(package="Webandco.DevTools", path="log.renderer")
     * @var array
     */
    protected $logRenderer;

    /**
     * Used to generate rainbow color output
     * @var int
     */
    protected static $logCounter = -1;
    /**
     * Color codes for console output
     * @var array
     */
    protected static $colorFormats = [
        // styles
        // italic and blink may not work depending of your terminal
        'none' => "%s",
        'bold' => "\033[1m%s\033[0m",
        'dark' => "\033[2m%s\033[0m",
        'italic' => "\033[3m%s\033[0m",
        'underline' => "\033[4m%s\033[0m",
        'blink' => "\033[5m%s\033[0m",
        'concealed' => "\033[8m%s\033[0m",
        // foreground colors
        'black' => "\033[30m%s\033[0m",
        'red' => "\033[31m%s\033[0m",
        'green' => "\033[32m%s\033[0m",
        'yellow' => "\033[33m%s\033[0m",
        'blue' => "\033[34m%s\033[0m",
        'magenta' => "\033[35m%s\033[0m",
        'cyan' => "\033[36m%s\033[0m",
        'white' => "\033[37m%s\033[0m",
        // background colors
        'bg_black' => "\033[40m%s\033[0m",
        'bg_red' => "\033[41m%s\033[0m",
        'bg_green' => "\033[42m%s\033[0m",
        'bg_yellow' => "\033[43m%s\033[0m",
        'bg_blue' => "\033[44m%s\033[0m",
        'bg_magenta' => "\033[45m%s\033[0m",
        'bg_cyan' => "\033[46m%s\033[0m",
        'bg_white' => "\033[47m%s\033[0m",
    ];

    /**
     * Parts of the log message
     * @var array<mixed>
     */
    protected $logs = [];

    /**
     * Print message bold
     * @param bool $enabled
     * @return $this
     */
    public function bold($enabled=true){
        $this->bold = $enabled;
        return $this;
    }
    /**
     * Print message bold
     * @param bool $enabled
     * @return $this
     */
    public function italic($enabled=true){
        $this->italic = $enabled;
        return $this;
    }
    /**
     * Print message underlined
     * @param bool $enabled
     * @return $this
     */
    public function underline($enabled=true){
        $this->underline = $enabled;
        return $this;
    }
    /**
     * Print message blinking
     * @param bool $enabled
     * @return $this
     */
    public function blink($enabled=true){
        $this->blink = $enabled;
        return $this;
    }

    /**
     * Print message underlined
     * @param string $color
     * @return $this
     */
    public function background(string $color){
        switch($color){
            case 'black':
                $this->background = 'bg_black';
                break;
            case 'red':
                $this->background = 'bg_red';
                break;
            case 'green':
                $this->background = 'bg_green';
                break;
            case 'yellow':
                $this->background = 'bg_yellow';
                break;
            case 'blue':
                $this->background = 'bg_blue';
                break;
            case 'magenta':
                $this->background = 'bg_magenta';
                break;
            case 'cyan':
                $this->background = 'bg_cyan';
                break;
            case 'white':
                $this->background = 'bg_white';
                break;
        }

        return $this;
    }

    /**
     * Enable or disable colored output
     *
     * @param bool|string $c If set to false, colors are disabled, if set to true, rainbow colors are used, a color name from $colorFormats can also be used
     * @return $this
     */
    public function color($c=true){
        $this->color = $c;
        return $this;
    }

    /**
     * Enable or disable pretty printing of complex objects
     * @param bool $pretty
     * @return $this
     */
    public function pretty($pretty=true){
        $this->pretty = $pretty;
        return $this;
    }

    /**
     * Set log level
     * @param string $level
     * @return $this
     */
    public function level(string $level){
        $this->level = $level;
        return $this;
    }

    /**
     * @param boolean $cond
     * @return $this
     */
    public function condition(bool $cond){
        $this->enabled = $cond;
        return $this;
    }

    /**
     * @param boolean $enabled
     * @param string $separator
     * @param float $factor
     * @return $this
     */
    public function withCallDepth(bool $enabled=true, string $separator = null, float $factor=null){
        $this->callDepth = $enabled;
        if(!is_null($separator)){
            $this->callDepthSeparator = $separator;
        }
        if(!is_null($factor)){
            $this->callDepthFactor = $factor;
        }

        return $this;
    }

    /**
     * @param boolean $enabled
     * @param string $separator
     * @param float $factor
     * @return $this
     */
    public function withCaller(){
        $caller = $this->backtraceService->getCaller(__FUNCTION__, __CLASS__);

        $this->addCallerLogLine($caller);

        return $this;
    }
    protected function addCallerLogLine($caller){
        if($caller) {
            $logLine = "";
            if (isset($caller['short'])) {
                $logLine = $caller['short'];
                if (0 < $caller['line']) {
                    $logLine .= ':' . $caller['line'];
                }
                if(isset($caller['class'])){
                    $class = $caller['class'];
                    if($this->endsWith($class, '_Original')){
                        $class = substr($class, 0, -9);
                    }
                    $logLine .= ':' . $class;
                }
                if(isset($caller['function'])){
                    $logLine .= ':' . $caller['function'];
                }
            }

            array_unshift($this->logs, $logLine);
        }
    }


    /**
     * Finally write the message to the systemlogger
     */
    protected function writeLog(){
        if (!$this->enabled ){
            return;
        }

        self::$logCounter++;

        if($this->callDepth){
            $depthCount = \count(\debug_backtrace(false));
            \array_unshift($this->logs, \str_repeat($this->callDepthSeparator, \max(0,(int)($depthCount*$this->callDepthFactor))));
        }

        $jsonEncodingOptions = 0;
        if ($this->pretty) {
            $jsonEncodingOptions |= JSON_PRETTY_PRINT;
        }

        foreach($this->logs as $i => $m){
            if(!is_string($m)){
                $this->logs[$i] = json_encode($m, $jsonEncodingOptions);
            }
        }

        $colorFormat = self::$colorFormats['none'];
        if(true === $this->color){
            $callColorOrder = [ 'green', 'cyan', 'blue', 'magenta', 'yellow', 'red' ];
            $colorFormat = self::$colorFormats[$callColorOrder[self::$logCounter % count($callColorOrder)]];
        }
        else if(isset(self::$colorFormats[$this->color])){
            $colorFormat = self::$colorFormats[$this->color];
        }

        if($this->bold){
            $colorFormat = sprintf(self::$colorFormats['bold'], $colorFormat);
        }
        if($this->italic){
            $colorFormat = sprintf(self::$colorFormats['italic'], $colorFormat);
        }
        if($this->underline){
            $colorFormat = sprintf(self::$colorFormats['underline'], $colorFormat);
        }
        if($this->blink){
            $colorFormat = sprintf(self::$colorFormats['blink'], $colorFormat);
        }

        if($this->background){
            $colorFormat = sprintf(self::$colorFormats[$this->background], $colorFormat);
        }

        $level = 'debug';
        if (isset(Logger::LOGLEVEL_MAPPING[$this->level])){
            $level = $this->level;
        }
        else {
            $logMapping = array_flip(Logger::LOGLEVEL_MAPPING);
            if(isset($logMapping[$this->level])){
                $level = $logMapping[$this->level];
            }
        }
        $this->systemLogger->$level(sprintf($colorFormat, implode(' ', $this->logs)));

        $this->logs = [];
    }

    /**
     * Write the given arguments to the systemlogger
     * @return LogService
     */
    public function wLog()
    {
        if (!$this->enabled ){
            return $this;
        }

        $args = func_get_args();
        if (0 < count($args)) {
            $this->log($args);
        }

        return $this;
    }

    protected function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    protected function log($args){
        if(count($this->logs) <= 0 && $this->caller) {
            $caller = $this->backtraceService->getCaller("wLog", false);

            $this->addCallerLogLine($caller);
        }

        foreach ($args as $arg) {
            switch (gettype($arg)) {
                case 'boolean':
                    $this->logs[] = $arg ? 'true' : 'false';
                    break;
                case 'integer':
                case 'double':
                case 'string':
                    $this->logs[] = "".$arg;
                    break;
                case 'NULL':
                    $this->logs[] = 'NULL';
                    break;
                case 'array':
                    $this->logs[] = $arg;
                    break;
                default:
                    if (is_object($arg)) {
                        $rendered = false;
                        foreach($this->logRenderer as $objectName => $how){
                            if($arg instanceof $objectName){
                                $renderer = $this->objectManager->get($how);
                                $this->logs = array_merge($this->logs, $renderer->render($this, $arg));
                                $rendered = true;
                                break;
                            }
                        }
                        if (!$rendered) {
                            $this->logs[] = get_class($arg) . ":";
                            $this->logs[] = $arg;
                        }
                    }
                    // "resource" or "unknown type" is ignored
            }
        }
    }

    public function __destruct()
    {
        $this->writeLog();
    }
}
