<?php

declare(strict_types=1);

namespace App\Neuron\Nodes;

use App\Neuron\Events\SecondNodeEvent;
use NeuronAI\Workflow\Events\StopEvent;
use NeuronAI\Workflow\Interrupt\Action;
use NeuronAI\Workflow\Interrupt\ApprovalRequest;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class SecondNode extends Node
{
    /**
     * Implement the Node's logic
     */
    public function __invoke(SecondNodeEvent $event, WorkflowState $state): StopEvent
    {
        logger('SecondNode', ['event' => $event]);
        logger('State', ['state' => $state]);
        logger('Age', ['age' => $state->get('age')]);

        // Need to interrupt the workflow if the user is below 18
        $humanResponse = $this->interruptIf(
            (int) $state->get('age', 0) < 18,
            new ApprovalRequest(
                message: 'User is below 18. Manual approval is required to continue.',
                actions: [
                    new Action(
                        id: 'allow',
                        name: 'Allow user',
                        description: 'Allow the user to continue'
                    ),
                    new Action(
                        id: 'deny',
                        name: 'Deny user',
                        description: 'Deny the user to continue'
                    ),
                ]
            )
        );

        if ($humanResponse instanceof ApprovalRequest) {
            $allowAction = $humanResponse->getAction('allow');
            $denyAction = $humanResponse->getAction('deny');

            if ($allowAction?->isApproved()) {
                $state->set('underage_decision', 'allow');
            } elseif ($denyAction?->isApproved()) {
                $state->set('underage_decision', 'deny');
            } else {
                $state->set('underage_decision', 'pending');
            }
        }

        $state->get('underage_decision') == 'allow' && logger('Vote added');
        $state->get('underage_decision') == 'deny' && logger('Vote denied');

        return new StopEvent;
    }
}
