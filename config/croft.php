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
        \Croft\Tools\GetCurrentDateAndTime::class,
    ],
];
