<?php
namespace Webandco\DevTools\Service\Log;

use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeSubject;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeInterface;
use Neos\Utility\ObjectAccess;

/**
 * Log renderer Interface
 *
 */
class PrivilegeInterfaceRenderer implements LogRenderInterface
{
    /**
     * @param LogService $logService
     * @param PrivilegeInterface $what
     * @return array
     */
    public function render(LogService $logService, $what) {
        $result = [];

        /** @var PrivilegeInterface $what */
        $result[] = '[ {PrivilegeInterface: '.\get_class($what).'} evaluate=' . $what->getMatcher() .
            ' configured permission=' .$what->getPermission() .
            ' privilegeTargetIdentifier='. $what->getPrivilegeTargetIdentifier() .
            ']'
        ;

        return $result;
    }
}
