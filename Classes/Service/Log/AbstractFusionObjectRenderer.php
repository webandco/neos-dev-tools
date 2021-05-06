<?php
namespace Webandco\DevTools\Service\Log;

use Neos\Fusion\FusionObjects\AbstractFusionObject;

/**
 * Log renderer Interface
 *
 */
class AbstractFusionObjectRenderer implements LogRenderInterface
{
    /**
     * @param LogService $logService
     * @param AbstractFusionObject $what
     * @return array
     */
    public function render(LogService $logService, $what){
        $runtime = $what->getRuntime();

        $fusionPath = $this->getProtectedProperty($what, 'path');
        $fusionObjectName = $this->getProtectedProperty($what, 'fusionObjectName');

        $currentApplyValues = $this->getProtectedProperty($runtime, 'currentApplyValues');
        // Note: contextStack can cause an exception in \json_encode because of a recursion - not sure why this happened
        //$contextStack = $this->getProtectedProperty($runtime, 'contextStack');
        //$defaultContextVariables = $this->getProtectedProperty($runtime, 'defaultContextVariables');
        $context = $runtime->getCurrentContext();

        $runtimeConfiguration = $this->getProtectedProperty($runtime, 'runtimeConfiguration');

        return [
            '[ {AbstractFusionObject ' . $fusionObjectName . ' ' . $fusionPath . ' status=' . $runtime->getLastEvaluationStatus() . ' } ]',
            ' {context = '. \json_encode($context, JSON_PRETTY_PRINT).'}',
            ' {currentApplyValues = '.\json_encode($currentApplyValues, JSON_PRETTY_PRINT) . '}',
            ' {runtimeConfig = ' . \json_encode($runtimeConfiguration->forPath($fusionPath), JSON_PRETTY_PRINT) . ' }',
        ];
    }

    /**
     * return the value of an inaccessible property of an object using reflection
     *
     * @param $object
     * @param $propertyName
     * @return mixed
     * @throws \ReflectionException
     */
    protected function getProtectedProperty($object, $propertyName){
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }
}
