<?php

namespace App\Command;

use App\Soundboard;
use Discord\Builders\MessageBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Discord\Discord;
use Discord\Voice\VoiceClient;
use Discord\Parts\Interactions\Interaction;

#[AsCommand(
    name: 'app:bot',
    description: 'Start the bot',
)]
class BotCommand extends Command
{
    private Discord $discord;

    private Soundboard $soundboard;

    public function __construct(Discord $discord, Soundboard $soundboard)
    {
        parent::__construct();

        $this->discord = $discord;
        $this->soundboard = $soundboard;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->discord->on('ready', function (Discord $discord) use ($io) {
            $io->success('Bot is ready!');

            $command = new \Discord\Parts\Interactions\Command\Command($discord, [
                'name' => 'kaamelott',
                'description' => 'Lire un son kaamelott',
                'options' => [
                    [
                        'name' => 'query',
                        'description' => 'recherche',
                        'type' => 3,
                    ],
                ],
            ]);
            $discord->application->commands->save($command);

            $discord->listenCommand('kaamelott', function (Interaction $interaction) use ($discord) {
                $queryOption = $interaction->data->options->get('name', 'query');
                $sound = $this->soundboard->random();

                if ($queryOption) {
                    $sound = $this->soundboard->search($queryOption['value']);
                }

                $channel = $this->getVoiceChannelForInteraction($interaction);

                if ($channel === null) {
                    return;
                }

                if ($sound) {
                    $discord->joinVoiceChannel($channel)->done(function (VoiceClient $voice) use ($sound) {
                        $voice->playFile($sound)->then(
                            function () use ($voice) {
                                $voice->close();
                            }
                        );
                    });
                }


                $interaction->acknowledgeWithResponse(true);
                $interaction->updateOriginalResponse(MessageBuilder::new()
                    ->setContent($sound ?? 'Pas de son'));
            });
        });

        $this->discord->run();

        return Command::SUCCESS;
    }

    private function getVoiceChannelForInteraction(Interaction $interaction)
    {
        $voiceStates = $interaction->channel->guild->voice_states;
        $userId = $interaction->user->id;

        foreach ($voiceStates as $voiceState) {
            if ($voiceState->user_id === $userId) {
                return $this->discord->getChannel($voiceState->channel_id);
            }
        }

        return null;
    }
}
