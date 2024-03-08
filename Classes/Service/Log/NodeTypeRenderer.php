<?php
namespace Webandco\DevTools\Service\Log;

/**
 * Log renderer Interface
 *
 */
class NodeTypeRenderer implements LogRenderInterface
{
    /**
     * @param LogService $logService
     * @param Neos\ContentRepository\Domain\Model\NodeType $what
     * @return array
     */
    public function render(LogService $logService, $what){
        /** @var \Neos\ContentRepository\Domain\Model\NodeType $what */
        $line = '[ {NodeType} ' . $what->getName(). ' '.$what->getLabel().' '.($what->isAbstract() ? 'abstract' : 'concrete').' '.\json_encode($what->getProperties()).' ]';

        return [
            $line
        ];
    }
}
