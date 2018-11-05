<?php declare(strict_types=1);

namespace App\Command;

use Predis\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckHistoryCommand extends Command
{
    const SENT_LIST = 'sent';

    private $client;

    public function __construct($name = null)
    {
        $this->client = new Client([
            'scheme' => 'tcp',
            'host'   => getenv('REDIS_HOST'),
            'port'   => getenv('REDIS_PORT'),
        ]);

        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('app:check-history')
            ->setDescription('Check messages history.')
            ->addArgument('limit', InputArgument::OPTIONAL, 'History limit.', -1)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $result = $this->client->lrange(self::SENT_LIST, 0, $input->getArgument('limit'));
        foreach ($result as $item) {
            $output->writeln($item);
        }
    }
}
