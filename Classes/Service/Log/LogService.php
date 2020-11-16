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
    const FORMAT_ON  = 'on';
    const FORMAT_OFF = 'off';

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
    protected $invert = false;
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
     *
     * @Flow\InjectConfiguration(package="Webandco.DevTools", path="log.colorCallOrder")
     * @var array
     */
    protected $callColorOrder = [ 'green', 'cyan', 'blue', 'magenta', 'yellow', 'red' ];

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
        'reset' => "\033[0m",
        'bold' =>      ['on' => "\033[1m", 'off' => "\033[22m"],
        'dark' =>      ['on' => "\033[2m", 'off' => "\033[22m"],
        'italic' =>    ['on' => "\033[3m", 'off' => "\033[23m"],
        'underline' => ['on' => "\033[4m", 'off' => "\033[24m"],
        'blink' =>     ['on' => "\033[5m", 'off' => "\033[25m"],
        // invert fore- and background color
        'invert' =>    ['on' => "\033[7m", 'off' => "\033[27m"],
        // hide output - e.g. for passwords
        'concealed' => ['on' => "\033[8m", 'off' => "\033[28m"],
        // foreground colors
        'black' =>   ['on' => "\033[30m", 'off' => "\033[39m"],
        'red' =>     ['on' => "\033[31m", 'off' => "\033[39m"],
        'green' =>   ['on' => "\033[32m", 'off' => "\033[39m"],
        'yellow' =>  ['on' => "\033[33m", 'off' => "\033[39m"],
        'blue' =>    ['on' => "\033[34m", 'off' => "\033[39m"],
        'magenta' => ['on' => "\033[35m", 'off' => "\033[39m"],
        'cyan' =>    ['on' => "\033[36m", 'off' => "\033[39m"],
        'white' =>   ['on' => "\033[37m", 'off' => "\033[39m"],
        // background colors
        'bg_black' =>   ['begin' => "\033[40m", 'off' => "\033[49m"],
        'bg_red' =>     ['begin' => "\033[41m", 'off' => "\033[49m"],
        'bg_green' =>   ['begin' => "\033[42m", 'off' => "\033[49m"],
        'bg_yellow' =>  ['begin' => "\033[43m", 'off' => "\033[49m"],
        'bg_blue' =>    ['begin' => "\033[44m", 'off' => "\033[49m"],
        'bg_magenta' => ['begin' => "\033[45m", 'off' => "\033[49m"],
        'bg_cyan' =>    ['begin' => "\033[46m", 'off' => "\033[49m"],
        'bg_white' =>   ['begin' => "\033[47m", 'off' => "\033[49m"],
    ];

    protected $resetOnEOL = false;

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
        $this->setBoolProperty('bold', $enabled);
        return $this;
    }
    /**
     * Print message bold
     * @param bool $enabled
     * @return $this
     */
    public function italic($enabled=true){
        $this->setBoolProperty('italic', $enabled);
        return $this;
    }
    /**
     * Print message underlined
     * @param bool $enabled
     * @return $this
     */
    public function underline($enabled=true){
        $this->setBoolProperty('underline', $enabled);
        return $this;
    }
    /**
     * Print message blinking
     * @param bool $enabled
     * @return $this
     */
    public function blink($enabled=true){
        $this->setBoolProperty('blink', $enabled);
        return $this;
    }
    /**
     * Print message blinking
     * @param bool $enabled
     * @return $this
     */
    public function invert($enabled=true){
        $this->setBoolProperty('invert', $enabled);
        return $this;
    }

    protected function setBoolProperty($propName, $enabled){
        switch($enabled){
            case self::FORMAT_ON:
                $this->resetOnEOL = true;
                $this->wLog(self::$colorFormats[$propName]['on']);
                break;
            case self::FORMAT_OFF:
                $this->resetOnEOL = true;
                $this->wLog(self::$colorFormats[$propName]['off']);
                break;
            default:
                $this->$propName = $enabled;
                break;
        }
    }

    /**
     * Print message underlined
     * @param string $color
     * @return $this
     */
    public function background(string $color, $wholeMessage = true){
        $colorName = $color;
        if(isset(self::$colorFormats['bg_'.$colorName])){
            $colorName = 'bg_'.$colorName;
        }
        if(!isset(self::$colorFormats[$colorName])){
            // unkonwn color name
            return;
        }

        switch($wholeMessage){
            case self::FORMAT_ON:
                $this->resetOnEOL = true;
                $this->wLog(self::$colorFormats[$colorName]['on']);
                break;
            case self::FORMAT_OFF:
                $this->resetOnEOL = true;
                $this->wLog(self::$colorFormats[$colorName]['off']);
                break;
            default:
                $this->background = $colorName;
                break;
        }

        return $this;
    }

    /**
     * Enable or disable colored output
     *
     * @param bool|string $color If set to false, colors are disabled, if set to true, rainbow colors are used, a color name from $colorFormats can also be used
     * @return $this
     */
    public function color($color=true, $wholeMessage = true){
        $colorName = $color;
        if(!isset(self::$colorFormats[$colorName])){
            // unkonwn color name
            return;
        }

        switch($wholeMessage){
            case self::FORMAT_ON:
                $this->resetOnEOL = true;
                $this->wLog(self::$colorFormats[$colorName]['on']);
                break;
            case self::FORMAT_OFF:
                $this->resetOnEOL = true;
                $this->wLog(self::$colorFormats[$colorName]['off']);
                break;
            default:
                $this->color = $colorName;
                break;
        }

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

    public function eol(){
        $this->writeLog();
    }

    /**
     * Finally write the message to the systemlogger
     */
    protected function writeLog(){
        if (!$this->enabled || 0 == count($this->logs)){
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

        $logFormat = self::$colorFormats['none'];

        $color = $this->color;
        if(true === $this->color){
            if(!empty($this->callColorOrder)) {
                $color = $this->callColorOrder[self::$logCounter % count($this->callColorOrder)];
            }
            else{
                $color = null;
            }
        }
        if(isset(self::$colorFormats[$color])) {
            $this->resetOnEOL = true;

            $logFormat = self::$colorFormats[$color]['on'].$logFormat.''.self::$colorFormats[$color]['off'];
        }

        if($this->bold){
            $this->resetOnEOL = true;

            $logFormat = $logFormat = self::$colorFormats['bold']['on'].$logFormat.''.self::$colorFormats['bold']['off'];
        }
        if($this->italic){
            $this->resetOnEOL = true;

            $logFormat = $logFormat = self::$colorFormats['italic']['on'].$logFormat.''.self::$colorFormats['italic']['off'];
        }
        if($this->underline){
            $this->resetOnEOL = true;

            $logFormat = $logFormat = self::$colorFormats['underline']['on'].$logFormat.''.self::$colorFormats['underline']['off'];
        }
        if($this->blink){
            $this->resetOnEOL = true;

            $logFormat = $logFormat = self::$colorFormats['blink']['on'].$logFormat.''.self::$colorFormats['blink']['off'];
        }

        if($this->background && isset(self::$colorFormats[$this->background])){
            $this->resetOnEOL = true;

            $logFormat = self::$colorFormats[$this->background]['on'].$logFormat.''.self::$colorFormats[$this->background]['off'];
        }

        if($this->resetOnEOL){
            $logFormat .= self::$colorFormats['reset'];
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
        $this->systemLogger->$level(sprintf($logFormat, implode(' ', $this->logs)));

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
        $this->eol();
    }
}
