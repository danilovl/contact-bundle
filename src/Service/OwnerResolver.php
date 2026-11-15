<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Service;

use Danilovl\ContactBundle\DependencyInjection\ContactConfig;
use LogicException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class OwnerResolver
{
    public function __construct(
        private ContactConfig $config,
        private ?Security $security = null
    ) {}

    public function resolve(): string
    {
        if ($this->security === null) {
            return $this->config->defaultOwner;
        }

        $user = $this->security->getUser();
        if ($user === null) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        return $this->resolveUserOwner($user);
    }

    public function isAdmin(): bool
    {
        if (!$this->config->adminEnabled || $this->security === null) {
            return false;
        }

        $role = $this->config->adminRole;

        return $this->security->isGranted($role);
    }

    public function resolveScope(): ?string
    {
        $owner = $this->resolve();

        if ($this->config->accessMode === ContactConfig::ACCESS_SHARED) {
            return null;
        }

        if ($this->isAdmin()) {
            return null;
        }

        return $owner;
    }

    private function resolveUserOwner(UserInterface $user): string
    {
        $property = $this->config->ownerProperty;
        if ($property === null) {
            return $user->getUserIdentifier();
        }

        $method = 'get' . ucfirst($property);
        $callable = [$user, $method];

        if (!is_callable($callable)) {
            $message = sprintf('Owner property "%s": method %s::%s() does not exist.', $property, $user::class, $method);

            throw new LogicException($message);
        }

        $value = $callable();
        $isScalar = is_string($value) || is_int($value);
        $owner = $isScalar ? (string) $value : '';

        if ($owner === '') {
            $message = sprintf('Owner property "%s" must return a non-empty string or integer.', $property);

            throw new LogicException($message);
        }

        return $owner;
    }
}
