<?php
namespace Webandco\DevTools\Aop\Pointcut;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\Builder\ClassNameIndex;
use Neos\Flow\Aop\Exception;
use Neos\Flow\Aop\Pointcut\PointcutExpressionParser;
use Neos\Flow\Aop\Pointcut\PointcutFilterInterface;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\CompileTimeObjectManager;
use Neos\Flow\Reflection\ReflectionService;
use Webandco\DevTools\Aspect\RuntimeStatsAspect;

/**
 * A simple class filter which fires on class names defined by a regular expression
 *
 * @Flow\Proxy(false)
 * @Flow\Scope("singleton")
 */
class RuntimeStatsBeforeFilter extends AbstractRuntimeStatsFilter
{
    protected function getSourceHint(){
        return RuntimeStatsAspect::class.'::'.'beforeLogRuntimeTime';
    }
}
