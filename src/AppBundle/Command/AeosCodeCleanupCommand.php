<?php

namespace AppBundle\Command;

use AppBundle\Entity\Code;
use AppBundle\Service\AeosHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AeosCodeCleanupCommand extends Command
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var AeosHelper */
    private $aeosHelper;

    public function __construct(EntityManagerInterface $entityManager, AeosHelper $aeosHelper)
    {
        $this->entityManager = $entityManager;
        $this->aeosHelper = $aeosHelper;
        parent::__construct();
    }

    public function configure()
    {
        $this
            ->setName('app:aeos:code-cleanup')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Don\'t do anything. Just show what will be done.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $dryRun = $input->getOption('dry-run');
        $expiredCodes = $this->entityManager->getRepository(Code::class)->findExpired();
        if ($output->isVerbose()) {
            $output->writeln('#expired codes: '.count($expiredCodes));
        }
        foreach ($expiredCodes as $code) {
            if ($dryRun) {
                $output->writeln('Delete code: '.$code);
            } else {
                if ($output->isVerbose()) {
                    $output->writeln('Deleting code: '.$code);
                }

                try {
                    $this->aeosHelper->deleteAeosIdentifier($code);
                    $this->entityManager->remove($code);
                    $this->entityManager->flush();
                    if ($output->isVerbose()) {
                        $output->writeln('Done');
                    }
                } catch (\Exception $ex) {
                    $output->writeln('Error deleting code: '.$code);
                    $output->writeln($ex->getMessage());
                    if ($output->isVerbose()) {
                        $output->writeln($ex->getTraceAsString());
                    }
                }
            }
        }
    }
}
