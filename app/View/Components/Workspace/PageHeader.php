<?php

namespace App\View\Components\Workspace;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PageHeader extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $title,
        public string $eyebrow = '',
        public string $description = '',
        public string $actionLabel = '',
        public string $actionHref = '',
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.workspace.page-header');
    }
}
