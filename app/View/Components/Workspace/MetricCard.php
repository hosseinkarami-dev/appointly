<?php

namespace App\View\Components\Workspace;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class MetricCard extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $label,
        public string|int $value,
        public string $detail = '',
        public string $tone = 'violet',
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.workspace.metric-card');
    }
}
