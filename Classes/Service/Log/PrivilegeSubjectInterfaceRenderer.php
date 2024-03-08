<?php
namespace Webandco\DevTools\Service\Log;

use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeSubject;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;
use Neos\Utility\ObjectAccess;

/**
 * Log renderer Interface
 *
 */
class PrivilegeSubjectInterfaceRenderer implements LogRenderInterface
{
    /**
     * @param LogService $logService
     * @param PrivilegeSubjectInterface $what
     * @return array
     */
    public function render(LogService $logService, $what) {
        $result = [];

        /** @var PrivilegeSubjectInterface $what */

        $result[] = '[ {PrivilegeSubject: '.\get_class($what).'}';
        switch (true) {
            case \is_a($what, 'Neos\ContentRepository\Security\Authorization\Privilege\Node\NodePrivilegeSubject\NodePrivilegeSubject'):
                $result[] = $what->getNode();
            case \is_a($what, MethodPrivilegeSubject::class):
                $result[] = ' '.$what->getJoinPoint()->getClassName().'::'.$what->getJoinPoint()->getMethodName().'(';
                $result = \array_merge($result, $what->getJoinPoint()->getMethodArguments());
                $result[] = ')';
                break;
            case \is_a($what, 'Neos\Neos\Security\Authorization\Privilege\ModulePrivilegeSubject\ModulePrivilegeSubject'):
                $result[] = 'ModulePath = ' . $what->getModulePath();
                break;
            default:
                $result = \array_merge($result, ObjectAccess::getGettableProperties($what));
                break;
        }

        $result[] = ']';

        return $result;
    }
}
