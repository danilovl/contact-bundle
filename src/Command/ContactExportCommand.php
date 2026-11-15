<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Command;

use Danilovl\ContactBundle\Service\ContactManager;
use Danilovl\ContactBundle\Service\VCard\VCardWriter;
use Symfony\Component\Console\Attribute\{
    Argument,
    AsCommand,
    Option
};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'danilovl:contact:export', description: 'Export contacts to a vCard (.vcf) file.')]
readonly class ContactExportCommand
{
    public function __construct(
        private ContactManager $contactManager,
        private VCardWriter $vCardWriter
    ) {}

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Path to output .vcf file')]
        string $file,
        #[Option(description: 'Owner identifier (username) of exported contacts')]
        string $owner = 'default'
    ): int {
        $contacts = $this->contactManager->listFull($owner);
        $content = $this->vCardWriter->writeAll($contacts);

        $bytes = file_put_contents($file, $content);
        if ($bytes === false) {
            $message = sprintf('File "%s" cannot be written.', $file);
            $io->error($message);

            return Command::FAILURE;
        }

        $count = count($contacts);
        $message = sprintf('Exported %d contacts to "%s".', $count, $file);
        $io->success($message);

        return Command::SUCCESS;
    }
}
