<?php

declare(strict_types=1);

namespace App\Neuron\Nodes;

use App\Neuron\Events\SecondNodeEvent;
use NeuronAI\Workflow\Events\StartEvent;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class FirstNode extends Node
{
    /**
     * Implement the Node's logic
     */
    public function __invoke(StartEvent $event, WorkflowState $state): SecondNodeEvent
    {
        logger('FirstNode', ['event' => $event]);
        logger('State', ['state' => $state]);

        return new SecondNodeEvent(18);
    }
}
