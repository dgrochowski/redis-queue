<?php declare(strict_types=1);

namespace App\Command;

use Predis\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MailSendCommand extends Command
{
    const QUEUED_LIST = 'queued';
    const SENT_LIST = 'sent';

    const SUBJECT = 'Notification e-mail!';

    private $client;
    private $mailer;

    public function __construct($name = null)
    {
        $this->client = new Client([
            'scheme' => 'tcp',
            'host'   => getenv('REDIS_HOST'),
            'port'   => getenv('REDIS_PORT'),
        ]);

        $transport = (new \Swift_SmtpTransport(getenv('SMTP_HOST'), getenv('SMTP_PORT')))
            ->setUsername(getenv('SMTP_USER'))
            ->setPassword(getenv('SMTP_PASS'))
        ;
        $this->mailer = new \Swift_Mailer($transport);

        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('app:mail-send')
            ->setDescription('Send messages from queue.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $messages = [];
        while ($data = $this->client->rpoplpush(self::QUEUED_LIST, self::SENT_LIST)) {
            $data = json_decode($data, true);
            $messages[$data['email']][] = $data['content'];
        }

        foreach ($messages as $email => $messages) {
            $message = (new \Swift_Message(self::SUBJECT))
                ->setCharset('UTF-8')
                ->setFrom(getenv('EMAIL_FROM'))
                ->setTo($email)
                ->setBody(implode("\n---\n", $messages))
            ;

            if ($this->mailer->send($message)) {
                $output->writeln("\e[32m" . 'E-mail have been successfully sent to: ' . $email . "\e[39m");
            } else {
                $output->writeln("\e[31m" . 'It was not possible to sent e-mail to: ' . $email . "\e[39m");
            }
        }
    }
}
