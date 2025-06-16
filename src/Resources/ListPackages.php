<?php

declare(strict_types=1);

namespace Croft\Resources;

use Croft\Feature\Resource\AbstractResource;

class ListPackages extends AbstractResource
{
    public function getUri(): string
    {
        return '/packages';
    }

    public function getDescription(): ?string
    {
        return 'List all Composer & NPM/Yarn/PNPM packages and their versions (composer.json and package.json)';
    }

    public function getContent(): string
    {
        $packages = [
            'composer' => [],
            'npm' => [],
        ];

        $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);
        $packages['composer']['main'] = $composerJson['require'] ?? [];
        $packages['composer']['dev'] = $composerJson['require-dev'] ?? [];

        $packageJson = json_decode(file_get_contents(base_path('package.json')), true);
        $packages['npm']['main'] = $packageJson['dependencies'] ?? [];
        $packages['npm']['dev'] = $packageJson['devDependencies'] ?? [];

        return json_encode($packages);
    }
}
