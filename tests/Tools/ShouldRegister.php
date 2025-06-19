<?php

use Croft\Feature\Tool\AbstractTool;
use Croft\Feature\Tool\ToolResponse;
use Croft\Server;
use Mockery;

class TestTool extends AbstractTool
{
    public function getName(): string
    {
        return 'test-tool';
    }

    public function getInputSchema(): array
    {
        return [];
    }

    public function handle(array $arguments): ToolResponse
    {
        return ToolResponse::text('test');
    }
}

test('tool isn\'t included if shouldRegister is false', function () {
    $tool = Mockery::mock(TestTool::class)->makePartial();
    $tool->shouldReceive('shouldRegister')->once()->andReturn(false);

    $server = new Server;
    $server->tool($tool);

    $this->assertEquals(0, $server->getToolRegistry()->count());
});

test('tool is included if shouldRegister is true', function () {
    $tool = Mockery::mock(TestTool::class)->makePartial();
    $tool->shouldReceive('shouldRegister')->once()->andReturn(true);

    $server = new Server;
    $server->tool($tool);

    $this->assertEquals(1, $server->getToolRegistry()->count());
});
