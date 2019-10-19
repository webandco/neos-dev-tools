# Neos Development Tools

The package provides tools for Neos development

## Installation

Install the package with composer. It is recommended to use the package only in development environments.

```
composer require webandco/neos-dev-tools --dev
```

## Tools

### nodePublished file tool

This tools creates a file when something is published in Neos. The purpose for this, is to use the file eg in gulp watch for browser-sync and reload the frontend, when the file changes.

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
