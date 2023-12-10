<?php
require __DIR__ . '/../vendor/autoload.php';
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

class MirageMaintenanceCli extends CLI
{
    protected function setup(Options $options)
    {
        $options->setHelp('Day 8: Mirage Maintenance');
        $options->registerArgument('input-file', 'Path to input', true);
        $options->registerOption('backwards', '[Part 2] extrapolate backwards');
    }

    protected function main(Options $options)
    {
        $inputPath = $options->getArgs()[0];
        $backwards = $options->getOpt('backwards');

        if (!is_readable($inputPath)) {
            $this->fatal('File is not readable');
        }

        $answer = 0;

        $inputContents = file_get_contents($inputPath);
        $lines = array_filter(explode(PHP_EOL, $inputContents));

        foreach ($lines as $line) {
            $numbers = array_map('intval', array_map('trim', explode(' ', $line)));
            $sets = [];

            $setIdx = 0;

            $sets[$setIdx] = $numbers;

            while (true) {
                $set = $sets[$setIdx];
                $nextSetIdx = $setIdx + 1;

                $sets[$nextSetIdx] = [];

                for ($i = 0; $i < count($set) - 1; $i++) {
                    $diff = $set[$i + 1] - $set[$i];

                    $sets[$nextSetIdx] []= $diff;
                }

                // All values in new set 0?
                $zeroCount = count(array_filter($sets[$nextSetIdx], function($n) {
                    return $n === 0;
                }));

                if ($zeroCount === count($sets[$nextSetIdx])) {
                    break;
                } else {
                    $setIdx++;
                }
            }

            // If extrapolating backwards, reverse the sets
            if ($backwards) {
                foreach ($sets as $i => $set) {
                    $sets[$i] = array_reverse($set);
                }
            }

            // Calculate next number in each sequence. Starting with 0 for the last set
            $sets[count($sets) - 1] []= 0;

            // Reverse up the sets, finding the next value in the sequence
            for ($i = count($sets) - 2; $i >= 0; $i--) {
                $set = $sets[$i];
                // Get last value in set underneith
                $setUnder = $sets[$i + 1];
                $diff = $setUnder[count($setUnder) - 1];

                // If extrapolating backwards, then we subtract instead of add the difference
                if ($backwards) {
                    $diff = -1 * $diff;
                }

                $val = $set[count($set) - 1] + $diff;

                // Add new calculation to end of current set
                $sets[$i] [] = $val;
            }

            $answer += $sets[0][count($sets[0]) - 1];
        }

        $this->info($answer);
    }
}

(new MirageMaintenanceCli())->run();