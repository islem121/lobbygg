<?php

namespace App\Command;

use App\Service\GeminiService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-gemini',
    description: 'Teste la connexion à l\'API Gemini',
)]
class TestGeminiCommand extends Command
{
    private GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        parent::__construct();
        $this->geminiService = $geminiService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Diagnostic de l\'API Gemini');

        $io->info('Tentative d\'analyse d\'un dossier test...');
        
        $result = $this->geminiService->analyzeDossier(
            'Société Test',
            'Je suis très motivé par ce projet.',
            'Bonjour, je souhaite postuler.'
        );

        if ($result['score'] > 0) {
            $io->success('Succès ! L\'IA a répondu.');
            $io->writeln('Score : ' . $result['score'] . '%');
            $io->writeln('Avis : ' . $result['avis']);
            return Command::SUCCESS;
        } else {
            $io->error('Échec de l\'analyse.');
            $io->writeln('Message : ' . $result['avis']);
            return Command::FAILURE;
        }
    }
}
