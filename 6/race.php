<?php
require __DIR__ . '/../vendor/autoload.php';
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

class Race {
    public $num;
    public $time;
    public $distance;
}

class RaceCli extends CLI
{
    protected function setup(Options $options)
    {
        $options->setHelp('Day 6: Wait For It');
        $options->registerArgument('input-file', 'Path to input', true);
        $options->registerOption('single-race', '[Part 2] Input is a single race');
    }

    protected function main(Options $options)
    {
        $inputPath = $options->getArgs()[0];
        $singleRace = $options->getOpt('single-race');

        if (!is_readable($inputPath)) {
            $this->fatal('File is not readable');
        }

        $inputContents = file_get_contents($inputPath);
        $lines = array_filter(explode(PHP_EOL, $inputContents));

        $races = [];

        foreach ($lines as $line) {
            list($field, $value) = array_map('trim', explode(':', $line));

            $nums = array_values(array_filter(explode(' ', $value)));

            if ($singleRace) {
                $nums = [implode('', $nums)];
            }

            foreach ($nums as $i => $num) {
                if (!array_key_exists($i, $races)) {
                    $races[$i] = new Race();
                    $races[$i]->num = $i;
                }

                if ($field === 'Time') {
                    $races[$i]->time = (int)$num;
                } else if ($field === 'Distance') {
                    $races[$i]->distance = (int)$num;
                }
            }
        }

        $answer = 1;

        foreach ($races as $race) {
            $answer *= count($this->solutions($race));
        }

        $this->info($answer);
    }

    private function solutions(Race $race) {
        $solutions = [];

        for ($heldMs = 1; $heldMs < $race->time; $heldMs++) {
            $remainingMs = $race->time - $heldMs;

            if ($remainingMs > 0) {
                $distanceTravelled = $remainingMs * $heldMs;

                if ($distanceTravelled > $race->distance) {
                    $solutions []= $heldMs;
                }
            }
        }

        $this->debug('Race ' . $race->num . ' solutions: ' . implode(', ', $solutions));

        return $solutions;
    }
}

(new RaceCli())->run();