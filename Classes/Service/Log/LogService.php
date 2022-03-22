<?php
namespace Webandco\DevTools\Service\Log;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Error\Debugger;
use Neos\Flow\Log\Psr\Logger;
use Neos\Flow\Log\PsrLoggerFactoryInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Webandco\DevTools\Service\BacktraceService;

class LogService
{
    const FORMAT_ON = 'on';
    const FORMAT_OFF = 'off';

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

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
     * @Flow\InjectConfiguration(package="Webandco.DevTools", path="log.numberFormat")
     * @var array
     */
    protected $numberFormat = [];

    /**
     * @Flow\InjectConfiguration(package="Webandco.DevTools", path="log.sapiLogger")
     * @var array<string>
     */
    protected $sapiLoggers = [];

    /**
     * @Flow\InjectConfiguration(package="Webandco.DevTools", path="log.caller")
     * @var array
     */
    protected $caller = [];

    /**
     * Debug level to use for logger
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
     * Indent the log messages by the depth given by debug_backtrace
     * @Flow\InjectConfiguration(package="Webandco.DevTools", path="log.hexDump")
     * @var array
     */
    protected $hexdumpConfig = [];

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
    protected $callColorOrder = ['green', 'cyan', 'blue', 'magenta', 'yellow', 'red'];

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
        'bold' =>       ['on' => "\033[1m", 'off' => "\033[22m"],
        'dark' =>       ['on' => "\033[2m", 'off' => "\033[22m"],
        'italic' =>     ['on' => "\033[3m", 'off' => "\033[23m"],
        'underline' =>  ['on' => "\033[4m", 'off' => "\033[24m"],
        'blink' =>      ['on' => "\033[5m", 'off' => "\033[25m"],
        // invert fore- and background color
        'invert' =>     ['on' => "\033[7m", 'off' => "\033[27m"],
        // hide output - e.g. for passwords
        'concealed' =>  ['on' => "\033[8m", 'off' => "\033[28m"],
        // foreground colors
        'black' =>      ['on' => "\033[30m", 'off' => "\033[39m"],
        'red' =>        ['on' => "\033[31m", 'off' => "\033[39m"],
        'green' =>      ['on' => "\033[32m", 'off' => "\033[39m"],
        'yellow' =>     ['on' => "\033[33m", 'off' => "\033[39m"],
        'blue' =>       ['on' => "\033[34m", 'off' => "\033[39m"],
        'magenta' =>    ['on' => "\033[35m", 'off' => "\033[39m"],
        'cyan' =>       ['on' => "\033[36m", 'off' => "\033[39m"],
        'white' =>      ['on' => "\033[37m", 'off' => "\033[39m"],
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
     * Time in seconds when the object was created
     * @var int
     */
    protected $start = 0;
    /**
     * Determines if timing is enabled
     * @var bool
     */
    protected $timing = false;
    /**
     * Identifier for the current timing call
     * @var string
     */
    protected $timingIdentifier = null;
    /**
     * Per timingIdentifier logs of durations
     * @var array
     */
    protected static $timingLogs = [];

    public function initializeObject() {
        $this->start = microtime(true);
    }

    /**
     * Print message bold
     * @param bool $enabled
     * @return $this
     */
    public function bold($enabled = true)
    {
        $this->setBoolProperty('bold', $enabled);
        return $this;
    }

    /**
     * Print message bold
     * @param bool $enabled
     * @return $this
     */
    public function italic($enabled = true)
    {
        $this->setBoolProperty('italic', $enabled);
        return $this;
    }

    /**
     * Print message underlined
     * @param bool $enabled
     * @return $this
     */
    public function underline($enabled = true)
    {
        $this->setBoolProperty('underline', $enabled);
        return $this;
    }

    /**
     * Print message blinking
     * @param bool $enabled
     * @return $this
     */
    public function blink($enabled = true)
    {
        $this->setBoolProperty('blink', $enabled);
        return $this;
    }

    /**
     * Print message blinking
     * @param bool $enabled
     * @return $this
     */
    public function invert($enabled = true)
    {
        $this->setBoolProperty('invert', $enabled);
        return $this;
    }

    protected function setBoolProperty($propName, $enabled)
    {
        switch ($enabled) {
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
    public function background(string $color, $wholeMessage = true)
    {
        $colorName = $color;
        if (isset(self::$colorFormats['bg_' . $colorName])) {
            $colorName = 'bg_' . $colorName;
        }
        if (!isset(self::$colorFormats[$colorName])) {
            // unkonwn color name
            return;
        }

        switch ($wholeMessage) {
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
    public function color($color = true, $wholeMessage = true)
    {
        $colorName = $color;
        if (!isset(self::$colorFormats[$colorName])) {
            // unkonwn color name
            return;
        }

        switch ($wholeMessage) {
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
    public function pretty($pretty = true)
    {
        $this->pretty = $pretty;
        return $this;
    }

    /**
     * Set log level
     * @param string $level
     * @return $this
     */
    public function level(string $level)
    {
        $this->level = $level;
        return $this;
    }

    /**
     * @param boolean $cond
     * @return $this
     */
    public function condition(bool $cond)
    {
        $this->enabled = $cond;
        return $this;
    }

    /**
     * @param boolean $enabled
     * @param string $separator
     * @param float $factor
     * @return $this
     */
    public function withCallDepth(bool $enabled = true, string $separator = null, float $factor = null)
    {
        $this->callDepth = $enabled;
        if (!is_null($separator)) {
            $this->callDepthSeparator = $separator;
        }
        if (!is_null($factor)) {
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
    public function withCaller()
    {
        $caller = $this->backtraceService->getCaller(__FUNCTION__, __CLASS__);

        $this->addCallerLogLine($caller);

        return $this;
    }

    protected function addCallerLogLine($caller)
    {
        if ($caller) {
            $logLine = [];
            if ($this->caller['path'] && isset($caller['short'])) {
                $logLine[] = $caller['short'];
            }

            if ($this->caller['class'] && isset($caller['class'])) {
                $class = $caller['class'];
                if ($this->endsWith($class, '_Original')) {
                    $class = substr($class, 0, -9);
                }
                $logLine[] =$class;
            }
            if ($this->caller['method'] && isset($caller['function'])) {
                $logLine[] = $caller['function'];
            }
            if ($this->caller['line'] && 0 < $caller['line']) {
                $logLine[] =  $caller['line'];
            }

            $logLine = \implode(':', $logLine);

            array_unshift($this->logs, $logLine);
        }
    }

    public function withTiming(string $identifier): self
    {
        $args = func_get_args();
        $this->timingIdentifier = \implode(':', $args);
        $this->timing = true;

        if(!isset(self::$timingLogs[$this->timingIdentifier])){
            self::$timingLogs[$this->timingIdentifier]  = [
                'durations' => []
            ];
        }

        return $this;
    }

    /**
     * From https://stackoverflow.com/a/34279537
     *
     * Dumps a string into a traditional hex dump for programmers,
     * in a format similar to the output of the BSD command hexdump -C file.
     * The default result is a string.
     * Supported options:
     * <pre>
     *   line_sep        - line seperator char, default = "\n"
     *   bytes_per_line  - default = 16
     *   pad_char        - character to replace non-readble characters with, default = '.'
     * </pre>
     *
     * @param string $string
     * @param array $options
     * @param string|array
     */
    public function hexDump(string $string, array $options = null) {
        if (!is_array($options)) {
            $options = [];
        }

        $highlightBinaryOption = $this->hexdumpConfig['highlightBinary'];
        $highlightBinary = [ 'on' => '', 'off' => '' ];
        if(isset(self::$colorFormats[$highlightBinaryOption])){
            $highlightBinary = self::$colorFormats[$highlightBinaryOption];
        }

        $line_sep       = $this->hexdumpConfig['lineSeparator'] ?? $options['lineSeparator'] ?? "\n";
        $bytes_per_line = $this->hexdumpConfig['bytesPerLine'] ?? $options['bytesPerLine'] ?? 16;
        $pad_char       = $this->hexdumpConfig['paddingCharacter'] ?? $options['paddingCharacter'] ?? '.';
        $logArray       = $this->hexdumpConfig['logArray'] ?? $options['logArray'] ?? false;

        $text_lines = str_split($string, $bytes_per_line);
        $hex_lines  = str_split(bin2hex($string), $bytes_per_line * 2);

        $offset = 0;
        $output = [];
        $bytes_per_line_div_2 = (int)($bytes_per_line / 2);
        foreach ($hex_lines as $i => $hex_line) {
            $text_line = $text_lines[$i];
            $output []=
                sprintf('%08X',$offset) .
                preg_replace(
                    '/ ([0,1].|7f|[8-f].)/',
                    ' ' . $highlightBinary['on'] . '${1}' . $highlightBinary['off'],
                    '  ' .str_pad(
                        strlen($text_line) > $bytes_per_line_div_2
                            ?
                            implode(' ', str_split(substr($hex_line,0,$bytes_per_line),2)) . '  ' .
                            implode(' ', str_split(substr($hex_line,$bytes_per_line),2))
                            :
                            implode(' ', str_split($hex_line,2))
                        , $bytes_per_line * 3)
                ) .
                '  |' . preg_replace('/[^\x20-\x7E]/', $highlightBinary['on'].$pad_char.$highlightBinary['off'], $text_line) . '|';
            $offset += $bytes_per_line;
        }
        $output []= sprintf('%08X', strlen($string));
        $output []= sprintf('string length in bytes: %d', strlen($string));

        $output = $logArray ? $output : "\n".\implode($line_sep, $output) . $line_sep;

        $this->wLog($output);

        return $this;
    }

    public function eol()
    {
        $this->writeLog();
    }

    /**
     * Finally write the message to the logger
     */
    protected function writeLog()
    {
        if (!$this->enabled || 0 == count($this->logs)) {
            return;
        }

        self::$logCounter++;

        if ($this->callDepth) {
            $depthCount = \count(\debug_backtrace(false));
            \array_unshift($this->logs,
                \str_repeat($this->callDepthSeparator, \max(0, (int)($depthCount * $this->callDepthFactor))));
        }

        $jsonEncodingOptions = 0;
        if ($this->pretty) {
            $jsonEncodingOptions |= JSON_PRETTY_PRINT;
        }

        foreach ($this->logs as $i => $m) {
            if (!is_string($m)) {
                $this->logs[$i] = json_encode($m, $jsonEncodingOptions);
            }
        }

        $logFormat = self::$colorFormats['none'];

        if($this->timing){
            $end = microtime(true);
            $duration = $end-$this->start;

            $scriptTime = $_SERVER['REQUEST_TIME_FLOAT'];
            $durationScriptTime = ($end-$scriptTime);
            $percentScriptTime = $duration*100/$durationScriptTime;

            $cnt = 1;

            if($this->timingIdentifier){
                self::$timingLogs[$this->timingIdentifier]['durations'][] = $duration;
                $cnt = count(self::$timingLogs[$this->timingIdentifier]['durations']);
            }

            $timingFormat = '[';
            $timingFormat .= "\u{23F0} ".$this->numberFormat($duration, 4).'s';

            $timingFormat .= ' = '.$this->numberFormat($percentScriptTime, 3).'%%';
            $timingFormat .= ' of '.$this->numberFormat($durationScriptTime, 3).'s';

            if($this->timingIdentifier) {
                if(1 < $cnt) {
                    $timingFormat .= ', #' . $cnt;
                    $sumDuration = \array_sum(self::$timingLogs[$this->timingIdentifier]['durations']);
                    $timingFormat .= ' sum '."\u{23F0} ".' ' . $this->numberFormat($sumDuration, 4) . 's';

                    $percent = $sumDuration * 100 / $durationScriptTime;
                    $timingFormat .= ' = ' . $this->numberFormat($percent, 3) . '%%';
                }
            }
            $timingFormat .= ']';

            $logFormat =  $timingFormat.' '.$logFormat;
        }

        $color = $this->color;
        if (true === $this->color) {
            if (!empty($this->callColorOrder)) {
                $color = $this->callColorOrder[self::$logCounter % count($this->callColorOrder)];
            } else {
                $color = null;
            }
        }
        if (isset(self::$colorFormats[$color])) {
            $this->resetOnEOL = true;

            $logFormat = self::$colorFormats[$color]['on'] . $logFormat . '' . self::$colorFormats[$color]['off'];
        }

        if ($this->bold) {
            $this->resetOnEOL = true;

            $logFormat = self::$colorFormats['bold']['on'] . $logFormat . '' . self::$colorFormats['bold']['off'];
        }
        if ($this->italic) {
            $this->resetOnEOL = true;

            $logFormat = self::$colorFormats['italic']['on'] . $logFormat . '' . self::$colorFormats['italic']['off'];
        }
        if ($this->underline) {
            $this->resetOnEOL = true;

            $logFormat = self::$colorFormats['underline']['on'] . $logFormat . '' . self::$colorFormats['underline']['off'];
        }
        if ($this->blink) {
            $this->resetOnEOL = true;

            $logFormat = self::$colorFormats['blink']['on'] . $logFormat . '' . self::$colorFormats['blink']['off'];
        }

        if ($this->background && isset(self::$colorFormats[$this->background])) {
            $this->resetOnEOL = true;

            $logFormat = self::$colorFormats[$this->background]['on'] . $logFormat . '' . self::$colorFormats[$this->background]['off'];
        }

        if ($this->resetOnEOL) {
            $logFormat .= self::$colorFormats['reset'];
        }

        $level = 'debug';
        if (isset(Logger::LOGLEVEL_MAPPING[$this->level])) {
            $level = $this->level;
        } else {
            $logMapping = array_flip(Logger::LOGLEVEL_MAPPING);
            if (isset($logMapping[$this->level])) {
                $level = $logMapping[$this->level];
            }
        }

        $loggerName = 'systemLogger';
        $sapiName = php_sapi_name();
        if (isset($this->sapiLoggers[$sapiName])) {
            $loggerName = $this->sapiLoggers[$sapiName];
        } else {
            if (isset($this->sapiLoggers['default'])) {
                $loggerName = $this->sapiLoggers['default'];
            }
        }

        $logger = $this->objectManager->get(PsrLoggerFactoryInterface::class)->get($loggerName);
        $message = sprintf($logFormat, implode(' ', $this->logs));
        $logger->$level($message);

        $this->logs = [];
    }

    /**
     * Write the given arguments to the configured sapiLogger
     * @return LogService
     */
    public function wLog()
    {
        if (!$this->enabled) {
            return $this;
        }

        $args = func_get_args();
        $this->log($args);

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

    protected function numberFormat($number, $decimals=null)
    {
        if (isset($this->numberFormat['decimals']) && is_null($decimals)) {
            $decimals = $this->numberFormat['decimals'];
        }
        return $decimals ? \number_format($number, $decimals) : $number;
    }

    protected function log($args)
    {
        // determine the caller takes around 0.2 ms
        if (isset($this->caller['enable']) && count($this->logs) === 0){
            $caller = $this->backtraceService->getCaller("wLog");
            $this->addCallerLogLine($caller);
        }

        if(count($args) === 0){
            return;
        }

        foreach ($args as $arg) {
            switch (gettype($arg)) {
                case 'boolean':
                    $this->logs[] = $arg ? 'true' : 'false';
                    break;
                case 'double':
                    $this->logs[] = "" . $this->numberFormat($arg);
                    break;
                case 'integer':
                case 'string':
                    $this->logs[] = "" . $arg;
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
                        foreach ($this->logRenderer as $objectName => $how) {
                            if ($arg instanceof $objectName) {
                                $this->appendRenderedObject($how, $arg);
                                $rendered = true;
                                break;
                            }
                        }
                        if (!$rendered) {
                            $this->appendRenderedObject(ObjectRenderer::class, $arg);
                        }
                    }
                // "resource" or "unknown type" is ignored
            }
        }
    }

    protected function appendRenderedObject($how, $arg)
    {
        $renderer = $this->objectManager->get($how);

        $line = $renderer->render($this, $arg);
        if (!is_array($line)) {
            $line = [$line];
        }

        $this->logs = \array_merge($this->logs, $line);
    }

    public function __destruct()
    {
        $this->eol();
    }
}
