<?php

declare(strict_types=1);

namespace Monosize\DynamicDnsIpUpdater\Command;

use Monosize\DynamicDnsIpUpdater\Service\DnsUpdaterService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to update .htaccess file with current IP addresses from dynamic DNS domains.
 */
#[AsCommand(
    name: 'dns:update-dynamic-ip',
    description: 'Updates .htaccess with current IP addresses from dynamic DNS domains',
    hidden: false  // Stellen Sie sicher, dass der Command nicht versteckt ist
)]
class UpdateDynamicIpCommand extends Command
{
    /**
     * @param DnsUpdaterService $dnsUpdater Service to handle DNS updates
     * @param string            $name       Optional command name override
     */
    public function __construct(
        private readonly DnsUpdaterService $dnsUpdater,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * Configures the command options.
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force update without checking cache'
            )
            ->setHelp('This command updates your .htaccess file with the current IP addresses from your configured dynamic DNS domains.');
    }

    /**
     * Executes the command logic.
     *
     * @param InputInterface  $input  Command input
     * @param OutputInterface $output Command output
     *
     * @return int Command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');

        try {
            // Update IP addresses and get the changes
            $updatedIps = $this->dnsUpdater->updateIpAddresses($force);

            // If no IPs were updated, inform and exit successfully
            if (empty($updatedIps)) {
                $io->info('No IP changes detected. .htaccess remains unchanged.');

                return Command::SUCCESS;
            }

            // Display success message for each updated domain
            foreach ($updatedIps as $domain => $ips) {
                $io->success(\sprintf(
                    'Updated IPs for %s: %s%s',
                    $domain,
                    implode(', ', $ips),
                    $force ? ' (forced)' : ''
                ));
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            // Display error message if something goes wrong
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
