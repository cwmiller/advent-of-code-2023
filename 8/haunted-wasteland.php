<?php
require __DIR__ . '/../vendor/autoload.php';
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

class HauntedWastelandCli extends CLI
{
    protected function setup(Options $options)
    {
        $options->setHelp('Day 7: Haunted Wasteland');
        $options->registerArgument('input-file', 'Path to input', true);
    }

    protected function main(Options $options)
    {
        $inputPath = $options->getArgs()[0];

        if (!is_readable($inputPath)) {
            $this->fatal('File is not readable');
        }

        $answer = 0;

        $inputContents = file_get_contents($inputPath);
        $lines = array_filter(explode(PHP_EOL, $inputContents));

        // Read directions from first line
        $directions = str_split($lines[0]);

        // Read all nodes
        $nodes = [];

        for ($i = 2; $i < count($lines); $i++) {
            if (preg_match('/([A-Z]{3}) = \(([A-Z]{3}), ([A-Z]{3})\)/', $lines[$i], $matches)) {
                $node = $matches[1];
                $l = $matches[2];
                $r = $matches[3];

                $nodes[$node] = [$l, $r];
            } else {
                $this->fatal('Cannot parse nodes');
            }
        }

        print_r($nodes);

        $curNodeName = 'AAA';

        while (true) {
            foreach ($directions as $direction) {
                $this->debug($direction);

                $answer++;

                $curNodeName = $nodes[$curNodeName][$direction === 'L' ? 0 : 1];

                $this->debug($curNodeName);

                if ($curNodeName === 'ZZZ') {
                    break 2;
                }
            }
        }


        $this->info($answer);
    }
}

(new HauntedWastelandCli())->run();