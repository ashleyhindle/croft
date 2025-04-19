<?php

return [
    'tools' => [
        \Croft\Tools\CreateTool::class,
        \Croft\Tools\ListArtisanCommands::class,
        \Croft\Tools\ListAvailableConfigKeys::class,
        \Croft\Tools\ListAvailableEnvVars::class,
        \Croft\Tools\ListRoutes::class,
        \Croft\Tools\ReadLogEntries::class,
        \Croft\Tools\ScreenshotUrl::class,
        \Croft\Tools\DatabaseListTables::class,
        // \Croft\Tools\Flux\ListComponents::class, // TODO: Only include if fluxui is being used (look at composer.json)
        // \Croft\Tools\Flux\GetComponentDetails::class,
        // \Croft\Tools\Flux\GetComponentExamples::class,
    ],
];
