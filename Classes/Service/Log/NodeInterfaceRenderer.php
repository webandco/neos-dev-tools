<?php
namespace Webandco\DevTools\Service\Log;

/**
 * Log renderer Interface
 *
 */
class NodeInterfaceRenderer implements LogRenderInterface
{
    /**
     * @param LogService $logService
     * @param Neos\ContentRepository\Domain\Model\NodeInterface $what
     * @return array
     */
    public function render(LogService $logService, $what){
        /** @var \Neos\ContentRepository\Domain\Model\NodeInterface $what */
        $line = '[ {NodeInterface} ' . $what->getIdentifier(). ' '.$what->getWorkspace()->getName().' '.$what->getPath().' '.$what->getNodeType()->getName().' '.\json_encode($what->getDimensions()).' ]';
        $line .= ' {'. \json_encode($what->getProperties()).'}';

        return [
            $line
        ];
    }
}
