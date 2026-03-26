<?php

declare(strict_types=1);

namespace App\Neuron\Workflows;

use App\Neuron\Nodes\FirstNode;
use App\Neuron\Nodes\SecondNode;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\Workflow;

class BlogCreationWorkflow extends Workflow
{
    /**
     * Returns an array of nodes that make up the workflow.
     *
     * @return Node[]
     */
    protected function nodes(): array
    {
        return [
            // new InitialNode(),
            new FirstNode,
            new SecondNode,
        ];
    }
}
