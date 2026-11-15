<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Command;

use Danilovl\ContactBundle\Service\ContactOwnerManager;
use Symfony\Component\Console\Attribute\{
    Argument,
    AsCommand
};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'danilovl:contact:change-owner', description: 'Move all contacts and tags from one owner to another, e.g. after renaming a user.')]
readonly class ContactChangeOwnerCommand
{
    public function __construct(private ContactOwnerManager $ownerManager) {}

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Current owner identifier')]
        string $from,
        #[Argument(description: 'New owner identifier')]
        string $to
    ): int {
        if ($from === $to) {
            $io->error('Owners must be different.');

            return Command::FAILURE;
        }

        $count = $this->ownerManager->reassign($from, $to);
        $message = sprintf('Contacts moved: %d.', $count);
        $io->success($message);

        return Command::SUCCESS;
    }
}
