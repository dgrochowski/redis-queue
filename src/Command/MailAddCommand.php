<?php declare(strict_types=1);

namespace App\Command;

use Predis\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MailAddCommand extends Command
{
    const QUEUED_LIST = 'queued';
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
            ->setName('app:mail-add')
            ->setDescription('Add message to queue.')
            ->addArgument('email', InputArgument::REQUIRED, 'Recipient e-mail address')
            ->addArgument('content', InputArgument::REQUIRED, 'E-mail content')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data = json_encode([
            'email' => $input->getArgument('email'),
            'content' => $input->getArgument('content'),
        ]);

        $this->client->lpush(self::QUEUED_LIST, $data);
        $output->writeln('Added: ' . "\e[32m" . $data . "\e[39m");
    }
}
