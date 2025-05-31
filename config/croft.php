<?php

return [
    'resources' => [
    ],
    'tools' => [
        \Croft\Tools\CreateCroftTool::class,
        \Croft\Tools\CreateCroftResource::class,
        \Croft\Tools\InertiaJsDocs::class,
        \Croft\Tools\ListArtisanCommands::class,
        \Croft\Tools\ListAvailableConfigKeys::class,
        // \Croft\Tools\ListAvailableConfig::class, // Config keys _and_ values, if you're happy to pass your config values to LLMs
        \Croft\Tools\ListAvailableEnvVars::class,
        \Croft\Tools\ListRoutes::class,
        \Croft\Tools\ReadLogEntries::class,
        \Croft\Tools\ScreenshotUrl::class,
        \Croft\Tools\DatabaseListTables::class,
        \Croft\Tools\DatabaseQueryReadOnly::class,
        // \Croft\Tools\DatabaseQueryReadWrite::class, // Enable if you're happy for AI to update your database
        \Croft\Tools\GetCurrentDateAndTime::class,
        \Croft\Tools\GetAbsoluteUrl::class,
    ],
];
