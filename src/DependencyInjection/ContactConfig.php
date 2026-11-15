<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\DependencyInjection;

final readonly class ContactConfig
{
    public const string ACCESS_OWNER = 'owner';

    public const string ACCESS_SHARED = 'shared';

    public function __construct(
        public string $apiPrefix,
        public string $dashboardPath,
        public string $dashboardTitle,
        public int $importMaxFileSize,
        public string $defaultOwner,
        public string $accessMode = self::ACCESS_OWNER,
        public bool $adminEnabled = false,
        public string $adminRole = 'ROLE_ADMIN',
        public ?string $ownerProperty = null,
        public ?string $phoneDefaultCountryCode = null
    ) {}
}
