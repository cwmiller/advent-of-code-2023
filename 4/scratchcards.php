<?php
require __DIR__ . '/../vendor/autoload.php';
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

class Card {
    public $num;
    public $winners = [];
    public $picks = [];
    public $instances = 1;
}

class ScratchcardsCli extends CLI
{
    protected function setup(Options $options)
    {
        $options->setHelp('Day 4: Scratchcards');
        $options->registerArgument('input-file', 'Path to input', true);
    }

    protected function main(Options $options)
    {
        $inputPath = $options->getArgs()[0];

        if (!is_readable($inputPath)) {
            $this->fatal('File is not readable');
        }

        $inputContents = file_get_contents($inputPath);
        $lines = array_filter(explode(PHP_EOL, $inputContents));
        $cards = [];

        foreach ($lines as $line) {
            if (preg_match('/^Card +(\d+): ([0-9 ]+) \| ([0-9 ]+)$/', $line, $matches)) {
                $card = new Card();
                $card->num = (int)trim($matches[1]);
                $card->winners = array_filter(explode(' ', trim($matches[2])), function($n) {
                    return strlen(trim($n)) > 0;
                });
                $card->picks = array_filter(explode(' ', trim($matches[3])), function($n) {
                    return strlen(trim($n)) > 0;
                });

                $cards [$card->num]= $card;
            }
        }

        $sum = 0;

        foreach ($cards as $cardNum => $card) {
            $score = 0;
            $matches = array_intersect($card->winners, $card->picks);

            if (count($matches) > 0) {
                $score = 1 << (count($matches) - 1);

                // Add instances of next cards
                for ($i = 0; $i < $card->instances; $i++) {
                    for ($j = 1; $j <= count($matches); $j++) {
                        $this->debug('Card ' . $cardNum . ': add to ' . $cardNum + $j);

                        $cards[$cardNum + $j]->instances += 1;
                    }
                }

            }

            $this->debug('Card ' . $card->num . ': ' . $score . ' (' . implode(', ', $matches) . ')');

            $sum += $score;
        }

        // Get total instances
        $totalInstances = 0;
        foreach ($cards as $card) {
            $this->debug('Card ' . $card->num . ': ' . $card->instances . ' instances');

            $totalInstances += $card->instances;
        }

        $this->info('Sum: ' . $sum);
        $this->info('Instances: ' . $totalInstances);
    }
}

(new ScratchcardsCli())->run();