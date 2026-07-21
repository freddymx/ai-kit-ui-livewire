<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use SolutionForest\AiKit\Ui\Http\StreamController;

Route::get('ai-kit/stream', StreamController::class)
    ->middleware(config('ai-kit.ui.stream_middleware', ['web']))
    ->name('ai-kit.stream');
