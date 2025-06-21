<?php

declare(strict_types=1);

use Croft\Feature\Tool\ToolResponse;
use Croft\Tools\ArtisanMake;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    $this->tool = new ArtisanMake;
});

it('can execute artisan make command', function () {
    // Arrange
    $mock = \Mockery::mock(Artisan::getFacadeRoot());
    $mock->shouldReceive('all')->andReturn([
        'make:controller' => 'Make a controller',
    ]);
    $mock->shouldReceive('call')
        ->with('make:controller', ['name' => 'TestController'])
        ->andReturn(0);
    $mock->shouldReceive('output')->andReturn('Controller created successfully.');

    Artisan::swap($mock);

    // Act
    $response = $this->tool->handle([
        'type' => 'controller',
        'name' => 'TestController',
    ]);

    // Assert
    expect($response)
        ->toBeInstanceOf(ToolResponse::class)
        ->toEqual(ToolResponse::text('Controller created successfully.'));
});

it('handles invalid class type', function () {
    // Arrange
    $mock = Mockery::mock(Artisan::getFacadeRoot());
    $mock->shouldReceive('all')->andReturn([
        'make:controller' => 'Make a controller',
    ]);

    Artisan::swap($mock);

    // Act
    $response = $this->tool->handle([
        'type' => 'invalid-type',
        'name' => 'TestController',
    ]);

    // Assert
    expect($response)
        ->toBeInstanceOf(ToolResponse::class)
        ->toEqual(
            ToolResponse::error("Invalid class type 'invalid-type'. Available types for the artisan make command are: controller.")
        );
});

it('can handle artisan make command with additional options', function () {
    // Arrange
    $mock = \Mockery::mock(Artisan::getFacadeRoot());
    $mock->shouldReceive('all')->andReturn([
        'make:controller' => 'Make a controller',
    ]);
    $mock->shouldReceive('call')
        ->with('make:controller', ['name' => 'TestController', '--invokable' => true])
        ->andReturn(0);
    $mock->shouldReceive('output')->andReturn('Invokable controller created successfully.');

    Artisan::swap($mock);

    // Act
    $response = $this->tool->handle([
        'type' => 'controller',
        'name' => 'TestController',
        'options' => ['--invokable' => true],
    ]);

    // Assert
    expect($response)
        ->toBeInstanceOf(ToolResponse::class)
        ->toEqual(ToolResponse::text('Invokable controller created successfully.'));
});
