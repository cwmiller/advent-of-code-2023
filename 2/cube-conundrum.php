<?php
require __DIR__ . '/../vendor/autoload.php';
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

class CubeConundrumCli extends CLI
{
    protected function setup(Options $options)
    {
        $options->setHelp('Day 2: Cube Conundrum');
        $options->registerArgument('input-file', 'Path to input', true);
        $options->registerArgument('red', true);
        $options->registerArgument('green', true);
        $options->registerArgument('blue', true);
    }

    protected function main(Options $options)
    {
        list($inputPath, $maxRed, $maxGreen, $maxBlue) = $options->getArgs();

        $colorLimits = [
            'red' => $maxRed,
            'green' => $maxGreen,
            'blue' => $maxBlue
        ];

        if (!is_readable($inputPath)) {
            $this->fatal('File is not readable');
        }

        $inputContents = file_get_contents($inputPath);
        $inputLines = array_filter(explode(PHP_EOL, $inputContents));

        $idSum = 0;
        $powerSum = 0;

        foreach ($inputLines as $line) {
            // Split game ID from reveals
            list($gameIdStr, $revealsStr) = explode(':', $line);

            // Parse game ID
            preg_match('/Game (\d+)/', $gameIdStr, $gameIdMatches);
            $gameId = (int)$gameIdMatches[1];

            $maxColors = [
                'red' => 0,
                'green' => 0,
                'blue' => 0
            ];

            preg_match_all('/ (\d+) (red|green|blue)/', $revealsStr, $matches);

            foreach ($matches[1] as $i => $cnt) {
                $color = $matches[2][$i];

                if ($cnt > $maxColors[$color]) {
                    $maxColors[$color] = $cnt;
                }
            }

            $qualify = true;

            foreach ($maxColors as $color => $cnt) {
                if ($maxColors[$color] > $colorLimits[$color]) {
                    $qualify = false;
                }
            }

            $this->debug($line . ' = ' . ($qualify ? 'PASS' : 'FAIL'));

            if ($qualify) {
                $idSum += $gameId;
            }

            $powerSum += ($maxColors['red'] * $maxColors['green'] * $maxColors['blue']);
        }

        $this->info('Part 1 ID SUM: ' . $idSum);
        $this->info('Part 2 Powers SUM: ' . $powerSum);

    }
}

(new CubeConundrumCli())->run();