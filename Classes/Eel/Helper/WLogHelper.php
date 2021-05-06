<?php
declare(strict_types=1);

namespace Webandco\DevTools\Eel\Helper;

use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

/**
 */
class WLogHelper implements ProtectedContextAwareInterface {
    /**
     * Returns the name of the current fusion object
     *
     * @return string
     * @throws \ReflectionException
     */
    public function wLog() :string
    {
        $args = func_get_args();

        \call_user_func_array('wLog', $args);
    }

    /**
     * Returns the name of the current fusion object
     *
     * @return string
     * @throws \ReflectionException
     */
    public function rwLog() :string
    {
        $args = func_get_args();
        $firstArg = \array_shift($args);

        \call_user_func_array('wLog', $args);

        return $firstArg;
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
