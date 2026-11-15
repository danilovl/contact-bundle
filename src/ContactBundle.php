<?php declare(strict_types=1);

namespace Danilovl\ContactBundle;

use Danilovl\ContactBundle\DependencyInjection\ContactExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContactBundle extends Bundle
{
    public function getContainerExtension(): ContactExtension
    {
        return new ContactExtension;
    }
}
