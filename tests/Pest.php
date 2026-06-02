<?php

declare(strict_types=1);

/*
 | Binds the application TestCase to Feature/Unit suites so that helpers like
 | $this->seed(), $this->getJson(), and RefreshDatabase work in Pest tests.
 | (Tests\TestCase is provided by the Laravel skeleton.)
 */

uses(Tests\TestCase::class)->in('Feature', 'Unit');
