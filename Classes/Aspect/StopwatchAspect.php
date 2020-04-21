<?php
declare(strict_types=1);

namespace Webandco\DevTools\Aspect;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;

/**
 * Fix issues with custom provider authentication not fixed in NEOS 4.3
 * https://github.com/neos/neos-development-collection/pull/2577
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class StopwatchAspect
{

    /**
     * @Flow\Around("method(Webandco\DevTools\Domain\Model\Dto\StopwatchA->start())")
     * @param JoinPointInterface $joinPoint
     * @return Stopwatch $this
     */
    public function start(JoinPointInterface $joinPoint)
    {
        return $joinPoint->getAdviceChain()->proceed($joinPoint);
    }

    /**
     * @Flow\Around("method(Neos\Neos\Service\NodeTypeSchemaBuilderA->generateNodeTypeSchema())")
     * @param JoinPointInterface $joinPoint
     */
    public function generateNodeTypeSchema(JoinPointInterface $joinPoint)
    {
        return $joinPoint->getAdviceChain()->proceed($joinPoint);
    }
}

