<?php

namespace App\Neuron\Events;

use NeuronAI\Workflow\Events\Event;

class FirstNodeEvent implements Event
{
    public function __construct(public readonly int $age) {}
}
