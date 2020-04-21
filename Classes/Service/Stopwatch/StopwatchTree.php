<?php
namespace Webandco\DevTools\Service\Stopwatch;

use Neos\Flow\Core\Bootstrap;
use Webandco\DevTools\Domain\Model\Dto\Stopwatch;
use Neos\Flow\Annotations as Flow;
use Webandco\DevTools\Service\BacktraceService;

/**
 * StopwatchTree
 * @Flow\Scope("singleton")
 */
class StopwatchTree implements StopwatchTreeInterface
{
    /**
     * @Flow\Inject
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @Flow\Inject
     * @var BacktraceService
     */
    protected $backtraceService;

    /**
     * @Flow\InjectConfiguration(package="Webandco.DevTools", path="stopwatch.tree.enabled")
     * @var boolean
     */
    protected $enabled = false;

    protected $root = null;

    protected $currentNode = null;

    public function initializeObject() {
        $this->reset();
    }

    public function reset(){
        $this->root = $this->createTreeNode();
        $this->currentNode = $this->root;
    }

    public function enable(){
        $this->enabled = true;
        return $this;
    }

    public function disable(){
        $this->enabled = false;
        return $this;
    }

    /**
     * @param bool $morePrecision
     * @return Stopwatch
     */
    public function startSlot(Stopwatch $instance, $name, $category){
        if(false === $this->enabled){
            return;
        }

        $id = spl_object_id($instance);
        $childs = &$this->currentNode->childs;
        if (!isset($childs["$id::$name::$category"])) {
            $childs["$id::$name::$category"] = $this->createTreeNode($instance, $name, $category);
        }
        $this->currentNode = $childs["$id::$name::$category"];
    }

    /**
     * @param bool $morePrecision
     * @return Stopwatch
     */
    public function stopSlot(Stopwatch $instance, $name, $category){
        if(false === $this->enabled){
            return;
        }

        $this->currentNode = $this->currentNode->parent;
    }

    protected function renderChilds($childs, $depth = 0){
        $result = [];

        foreach($childs as $child){
            $stopwatch = $child->stopwatch;

            $duration = $stopwatch->getEvent($child->name)->getDuration();
            $line = sprintf("%s%s : %s",
                               str_repeat(" ", $depth*2), strlen($child->category) ? "{$child->category}:{$child->name}" : $child->name, Stopwatch::format($duration));
            $lapCount = $stopwatch->countLaps($child->name);
            if(1 < $lapCount){
                $line .= " (laps $lapCount)";
            }

            $result[] = $line;

            if(count($child->childs)){
                $result = array_merge($result, $this->renderChilds($child->childs, $depth+1));
            }
        }

        return $result;
    }

    public function getTreeString(){
        $result = implode("\n", $this->renderChilds($this->root->childs));

        return $result;
    }

    protected function createTreeNode(Stopwatch $watch=null, $name=null, $category=null){
        $treeNode = new \stdClass();
        $treeNode->childs = [];
        $treeNode->name = $name;
        $treeNode->category = $category;
        $treeNode->stopwatch = $watch;
        $treeNode->parent = null;

        if (!is_null($this->currentNode)) {
            $treeNode->parent = $this->currentNode;
        }

        return $treeNode;
    }
}
