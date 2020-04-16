# Neos Development Tools

The package provides tools for Neos development

## Installation

Install the package with composer. It is recommended to use the package only in development environments.

```
composer require webandco/neos-dev-tools --dev
```

## Tools

### nodePublished file tool

This tools create a file when something is published in Neos. The purpose for this, is to use the file eg in gulp watch for browser-sync and reload the frontend, when the file changes.

#### Configuration

```
Webandco:
  DevTools:
    nodePublished:
      use: true
      file: '%FLOW_PATH_ROOT%.WebandcoNeosDevToolsLastPublished'
```

##### Webandco.DevTools.nodePublished.use

`true` to enable writing nodePublished file, else `false`

##### Webandco.DevTools.nodePublished.file

path of the nodePublished file

### FLOW commandline BASH autocomplete

To get autocomplete for the FLOW commandline you need to load the script

```
source Scripts/flow.complete.sh
```

in BASH.
This might be handy in a server environment like Beach or custom docker containers. 

### Stopwatch

Sometimes it's quite handy to determine how many milliseconds an operation took to determine
performance issues. This package makes use of [Symfony's Stopwatch](https://github.com/symfony/stopwatch)
for this purpose.

The Stopwatch class is extended to provide some more features.

#### Instantiation

There are two ways to instantiate a stopwatch. Either directly using `new Stopwatch()` or
via the provided `StopwatchFactory`.

The factory might be relevant if multiple stopwatches are needed in different code parts.
Using the factory you could create some final log output for all used stopwatches. 

#### Usage

The stopwatch can be used like (Symfony's Stopwatch)(https://symfony.com/doc/current/components/stopwatch.html).

Two additional features are available: Restart the stopwatch and estimations.

##### Restart

Given a stopwatch `$s = new Stopwatch();` you can avoid consecutive calls to
`->start('critical_section')` and `->stop('critical_section')` by using

```
$s->start('init');
....
$s->restart('workspace_live');
if ($workspace->getName() == 'live') {
    $s->restart('critical_section_1');
    ...
    $s->restart('critical_section_2');
    ...
    $s->restart('nodes');
    foreach($nodes as $node){
        ...
        $s->lap('nodes');
    }
}
$s->stop();

```

##### ETA and format duration

Let's assume you process a bunch of nodes in a loop and you know this takes a while.
To get an estimate of how long it takes you can use the following:

```
$s = new Stopwatch();
...
$nodes = ....
$nodeCount = count($nodes);

$s->restart('nodes');
foreach($nodes as $node){
    $c = $stopwatch->countLaps('nodes');
    if($c % 10 == 0){  // print ETA on every 10'th node
        $this->systemLogger->debug("Duration: ".$s->format($s->getEvent('nodes')->getDuration())." ETA: ".$s->format($s->eta('nodes', $nodeCount)));
    }
    ...  // process a node
    $s->lap('nodes');
}
$s->stop('nodes');

```

##### Stopwatch duration

The duration in milliseconds can be retrieved for the whole stopwatch
using `$s->getDuration()` or for a single event `$s->getEvent('someeventname')->getDuration()`.

##### Stopwatch metadata

You can also add metadata to a stopwatch, which can be used later on.

```
public function processNode(Node $node){
    $s = new Stopwatch();
    $s->setMetadata('nodeIdentifier', $node->getIdentifier());
    $s->setMetadata('nodeType', $node->getNodeType()->getName());

    ...
    $s->restart('ciritical_section');
    $s->stop();

    // Log the stopwatch, e.g. using wLog($s); - see below
}
```

#### Stopwatch signals

The [Stopwatch](./Classes/Domain/Model/Dto/Stopwatch.php) emits signals
upon start/stop/openSection/stopSection. These signals can be used
to create a tree of the stopwatch calls.
This calltree could provide better details on which parts take how long.

### Logging

This package also provides a quick and dirty way to log to the SystemLogger.
Keep in mind that this should not be used in production!

The goal is to get a fast way to log to the system log and have some convenience alike
`console.log` known from browsers.

In [Package.php](./Classes/Package.php) a function `wLog()` is declared
in the global namespace.
This function makes use of the [LogService](./Classes/Service/Log/LogService.php).

After the package is loaded you can use the function `wLog()`
without the need to inject the systemLogger by simply writing

```
wLog("Something to log", 4711, null, true);
```
somewhere in your code, which creates an output like
```
20-04-10 13:41:11 2865       DEBUG                          Something to log here 4711 NULL true
```

You can also write multiple lines using the syntax
```
wLog("First line")->wLog("Second line");
```

#### Configuration

An example configuration could be like this:

```
Webandco:
  DevTools:
    log:
      enabled: true
      pretty: true
      color: true
      level: 'debug'
      renderer:
        Webandco\DevTools\Domain\Model\Dto\Stopwatch: Webandco\DevTools\Service\Stopwatch\StopwatchLogRenderer
        Throwable: Webandco\DevTools\Service\Log\ThrowableLogRenderer

```

* enabled: To enable or disable logging to SystemLogger. The global method `wLog()` is created always.
* pretty: In case a complex object is given as an argument, the object is logged using `json_encode`.
If `pretty` is `true` the option `JSON_PRETTY_PRINT` is used for those arguments.
* color: If set to `true` every new log message is printed in a new color. Thus you get a colored system log
and maybe some issues or patterns might be easier to spot.
The color can also be fixed using any of the names
`none, bold, dark, italic, underline, blink, concealed, black, red, green, yellow,
blue, magenta,cyan, white, bg_black, bg_red, bg_green, bg_yellow,
bg_blue, bg_magenta, bg_cyan, bg_white`.
* level: The log level to use for logging. Can be any of
`emergency, alert, critical, error, warning, notice, info, debug`
* renderer: For custom objects you can provide a custom renderer which implements
the [LogRendererInterface.php](./Classes/Service/Log/LogRendererInterface.php) 

##### Overrule config

You can overrule the config using
```
wLog()->pretty(true)->color('red')->level('critical')->wLog("Something gone wrong, see this Exception", $e);
```

#### Custom Log Renderer

Complex objects are rendered using `json_encode`. Often this doesn't expose
the needed information, thus you can create a custom renderer to transform a complex
object to simpler log message parts.
For an example, have a look at [ThrowableLogRenderer.php](./Classes/Service/Log/ThrowableLogRenderer.php)
and the [Settings.yaml](./Configuration/Settings.yaml).
