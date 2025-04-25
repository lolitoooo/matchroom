<?php

namespace App\Command;

use App\Repository\NegotiationRepository;
use App\Service\NegotiationService;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'negotiation:process-timeouts',
    description: 'Traite les négociations en attente depuis plus de 3 heures.',
)]
class NegotiationProcessTimeoutsCommand extends Command
{
    public function __construct(
        private NegotiationRepository $negotiationRepository,
        private NegotiationService $negotiationService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $threeHoursAgo = (new DateTimeImmutable())->modify('-3 hours');

        $pendingNegotiations = $this->negotiationRepository->findPendingOlderThan($threeHoursAgo);

        foreach ($pendingNegotiations as $negotiation) {
            $this->negotiationService->processNegotiation($negotiation);
            $output->writeln("Négociation #{$negotiation->getId()} traitée automatiquement.");
        }

        return Command::SUCCESS;
    }
}
