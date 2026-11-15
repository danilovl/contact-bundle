<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Command;

use Danilovl\ContactBundle\Service\ContactUpgrader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'danilovl:contact:upgrade', description: 'Fill UID, normalized values and move photos and tags to the new tables.')]
readonly class ContactUpgradeCommand
{
    public function __construct(private ContactUpgrader $contactUpgrader) {}

    public function __invoke(SymfonyStyle $io): int
    {
        $result = $this->contactUpgrader->upgrade();

        $message = sprintf(
            'Contacts: %d, UID filled: %d, photos moved: %d, tags moved: %d, values normalized: %d.',
            $result['contacts'],
            $result['uids'],
            $result['photos'],
            $result['tags'],
            $result['values']
        );
        $io->success($message);

        return Command::SUCCESS;
    }
}
