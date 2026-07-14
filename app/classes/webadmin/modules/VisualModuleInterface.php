<?php

declare(strict_types=1);

namespace webadmin\modules;

use vakata\http\Request;
use vakata\http\Response;

interface VisualModuleInterface extends ModuleInterface
{
    public function getSlug(): string;
    public function getIcon(): string;
    public function getColor(): string;
    public function getParent(): string;
    public function onDashboard(): bool;
    public function inMenu(): bool;
    public function process(Request $request): Response;
}
