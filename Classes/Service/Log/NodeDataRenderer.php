<?php
namespace Webandco\DevTools\Service\Log;

/**
 * Log renderer Interface
 *
 */
class NodeDataRenderer implements LogRenderInterface
{
    /**
     * @param LogService $logService
     * @param Neos\ContentRepository\Domain\Model\NodeData $what
     * @return array
     */
    public function render(LogService $logService, $what){
        /** @var \Neos\ContentRepository\Domain\Model\NodeData $what */
        $line = '[ {NodeData} ' . $what->getIdentifier(). ' '.$what->getWorkspace()->getName().' '.$what->getPath().' '.$what->getNodeType()->getName().' '.\json_encode($what->getDimensionValues()).' ]';
        $line .= ' {'. \json_encode($what->getProperties()).'}';

        return [
            $line
        ];
    }
}
