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
 */
abstract class AbstractRuntimeStatsFilter implements PointcutFilterInterface
{
    abstract protected function getSourceHint();

    /**
     * Checks if the specified class matches with the class filter pattern
     *
     * @param string $className Name of the class to check against
     * @param string $methodName Name of the method - not used here
     * @param string $methodDeclaringClassName Name of the class the method was originally declared in - not used here
     * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
     * @return boolean true if the class matches, otherwise false
     * @throws Exception
     */
    public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier): bool
    {
        $configurationManager = Bootstrap::$staticObjectManager->get(ConfigurationManager::class);
        $runtimeStats = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Webandco.DevTools.runtimeStats');

        if(!isset($runtimeStats['expression']) || empty($runtimeStats['expression'])){
            return false;
        }

        $expression = $runtimeStats['expression'];

        $expressionParser = Bootstrap::$staticObjectManager->get(PointcutExpressionParser::class);
        $pointcutFilterComposite = $expressionParser->parse($expression, $this->getSourceHint());
        $pointcut = new \Neos\Flow\Aop\Pointcut\Pointcut($expression, $pointcutFilterComposite, RuntimeStatsAspect::class);
        return $pointcut->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier);
    }

    /**
     * Returns true if this filter holds runtime evaluations for a previously matched pointcut
     *
     * @return boolean true if this filter has runtime evaluations
     */
    public function hasRuntimeEvaluationsDefinition(): bool
    {
        return false;
    }

    /**
     * Returns runtime evaluations for the pointcut.
     *
     * @return array Runtime evaluations
     */
    public function getRuntimeEvaluationsDefinition(): array
    {
        return [];
    }

    /**
     * This method is used to optimize the matching process.
     *
     * @param ClassNameIndex $classNameIndex
     * @return ClassNameIndex
     */
    public function reduceTargetClassNames(ClassNameIndex $classNameIndex): ClassNameIndex
    {
        $configurationManager = Bootstrap::$staticObjectManager->get(ConfigurationManager::class);
        $runtimeStats = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Webandco.DevTools.runtimeStats');

        $configuredExcludedClassNames = $runtimeStats['excludeClasses'];

        $classNames = $classNameIndex->getClassNames();
        $allowedClassNames = \array_filter($classNames, function($className) use($configuredExcludedClassNames){
            if(\in_array($className, $configuredExcludedClassNames)){
                return false;
            }
            foreach($configuredExcludedClassNames as $regex){
                try {
                    if (\preg_match($regex, $className)) {
                        return false;

                    }
                }catch(\Exception $e){}
            }

            return true;
        });

        $filteredIndex = new ClassNameIndex();
        $filteredIndex->setClassNames($allowedClassNames);

        return $filteredIndex;
    }
}
