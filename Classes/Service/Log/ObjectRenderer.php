<?php
namespace Webandco\DevTools\Service\Log;

/**
 * Log renderer Interface
 *
 */
class ObjectRenderer implements LogRenderInterface
{
    protected $renderedObjects = [];

    /**
     * @param LogService $logService
     * @param Neos\ContentRepository\Domain\Model\NodeData $what
     * @return array
     */
    public function render(LogService $logService, $what){
        $line = $this->objectToString($what);
        //var_dump(__LINE__." : ".$line);
        return [
            $line
        ];
    }

    protected function objectToString($what, $depth=0){
        $str = '['.\get_class($what).']';

        if($depth < 1) {
            $props = [];
            $r = new \ReflectionClass($what);
            /** @var \ReflectionProperty $property */
            foreach ($r->getProperties() as $property) {
                $name = $property->getName();
                if(strpos($name, 'Flow_Aop_Proxy') === 0){
                    continue;
                }

                if (!$property->isPublic()) {
                    //$name .= ' (p)';
                    $property->setAccessible(true);
                }
                $val = $property->getValue($what);
                if (\is_object($val)) {
                    $objectId = \spl_object_id($val);
                    if (!isset($this->renderedObjects[$objectId])) {
                        $this->renderedObjects[$objectId] = true;
                        $val = $this->objectToString($val, $depth + 1);
                    }
                }
                $props[$name] = $val;
            }

            $str .= ' ' . \json_encode($props);
            //var_dump(__LINE__." : ".$str);
        }

        return $str;
    }
}
