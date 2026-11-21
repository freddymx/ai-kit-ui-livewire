<?php

declare(strict_types=1);

namespace SolutionForest\AiKit\Ui\Tests;

use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use SolutionForest\AiKit\AiKitServiceProvider;
use SolutionForest\AiKit\Contracts\Embedder;
use SolutionForest\AiKit\Ui\AiKitUiServiceProvider;
use SolutionForest\AiKit\Ui\Tests\Support\FakeEmbedder;
use SolutionForest\AiKit\Ui\Tests\Support\User;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            \Laravel\Ai\AiServiceProvider::class,
            LivewireServiceProvider::class,
            AiKitServiceProvider::class,
            AiKitUiServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('ai-kit.retrieval.driver', 'sqlite');
        $app['config']->set('ai-kit.retrieval.min_score', 0.05);
        $app['config']->set('ai-kit.embeddings.dimensions', FakeEmbedder::DIMENSIONS);
        $app['config']->set('ai-kit.embeddings.cache', false);
        $app['config']->set('ai-kit.chat.streaming', false);

        $app['config']->set('auth.providers.users.model', User::class);

        $app->singleton(Embedder::class, fn (): Embedder => new FakeEmbedder());
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/../../ai-kit-core/database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/../vendor/laravel/ai/database/migrations');
    }
}
