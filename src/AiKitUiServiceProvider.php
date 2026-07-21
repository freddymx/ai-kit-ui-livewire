<?php

declare(strict_types=1);

namespace SolutionForest\AiKit\Ui;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use SolutionForest\AiKit\Ui\Livewire\Chat;

final class AiKitUiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'ai-kit-ui');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        Livewire::component('ai-kit.chat', Chat::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/ai-kit-ui'),
            ], 'ai-kit-ui-views');
        }
    }
}
