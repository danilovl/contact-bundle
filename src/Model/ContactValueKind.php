<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Model;

enum ContactValueKind: string
{
    case EMAIL = 'email';
    case PHONE = 'phone';
    case URL = 'url';
    case MESSENGER = 'messenger';
    case RELATION = 'relation';

    public function collectionKey(): string
    {
        return match ($this) {
            self::EMAIL => 'emails',
            self::PHONE => 'phones',
            self::URL => 'urls',
            self::MESSENGER => 'messengers',
            self::RELATION => 'relations'
        };
    }
}
