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
     * Logs the method arguments using wLog to System log
     *
     * @return void
     * @throws \ReflectionException
     */
    public function wLog() :void
    {
        $args = func_get_args();

        \call_user_func_array('wLog', $args);
    }

    /**
     * Returns the first method argument and
     * logs other arguments using wLog to System log
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function rwLog(): mixed
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
