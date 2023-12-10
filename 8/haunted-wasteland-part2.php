<?php
require __DIR__ . '/../vendor/autoload.php';
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

class HauntedWastelandCli extends CLI
{
    protected function setup(Options $options)
    {
        $options->setHelp('Day 7: Haunted Wasteland Part 2');
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
        $lines = array_values(array_filter(explode(PHP_EOL, $inputContents)));

        // Read directions from first line
        $directions = str_split($lines[0]);

        // Read all nodes
        $nodes = [];

        for ($i = 1; $i < count($lines); $i++) {
            if (preg_match('/([A-Z0-9]{3}) = \(([A-Z0-9]{3}), ([A-Z0-9]{3})\)/', $lines[$i], $matches)) {
                $node = $matches[1];
                $l = $matches[2];
                $r = $matches[3];

                $nodes[$node] = [$l, $r];
            } else {
                $this->fatal('Cannot parse nodes');
            }
        }

        $curNodes = [];
        $nodeHops = [];

        // Find starting nodes (end with A)
        foreach ($nodes as $nodeName => $_) {
            if ($nodeName[2] === 'A') {
                $curNodes []= $nodeName;
                $nodeHops []= 0;
            }
        }

        // Track how many hops it takes to find Z for each node
        foreach ($curNodes as $idx => $nodeName) {
            $hops = 0;

            while (true) {
                foreach ($directions as $direction) {
                    $hops++;

                    $nextNodeName = $nodes[$nodeName][$direction === 'L' ? 0 : 1];

                    if (!array_key_exists($nextNodeName, $nodes)) {
                        $this->fatal('Unknown node ' . $nextNodeName);
                    }

                    $nodeName = $nextNodeName;

                    if ($nodeName[2] === 'Z') {
                        $nodeHops[$idx] = $hops;
                        break 2;
                    }
                }
            }
        }

        if (count($nodeHops) === 1) {
            $answer = $nodeHops[0];
        } else {
            $v1 = array_pop($nodeHops);
            $v2 = array_pop($nodeHops);

            $answer = gmp_lcm($v1, $v2);

            $this->debug($answer);

            foreach ($nodeHops as $hops) {
                $answer = gmp_lcm($answer, $hops);
                $this->debug($answer);
            }

        }

        $this->info($answer);
    }
}

(new HauntedWastelandCli())->run();