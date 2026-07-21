<?php

declare(strict_types=1);

use SolutionForest\AiKit\Ingestion\IngestionService;
use SolutionForest\AiKit\Models\Collection;
use SolutionForest\AiKit\Ui\Tests\TestCase;

uses(TestCase::class)->in('Feature');

function seedCollection(string $slug = 'kb'): Collection
{
    $collection = Collection::create(['name' => ucfirst($slug), 'slug' => $slug]);
    $service = app(IngestionService::class);

    $path = sys_get_temp_dir() . '/ui_' . uniqid() . '.md';
    file_put_contents($path, str_repeat('Refunds are issued within fourteen days to the original payment method. ', 12));
    $service->createFromPath($collection, $path, title: 'Refunds', sync: true);

    return $collection;
}
