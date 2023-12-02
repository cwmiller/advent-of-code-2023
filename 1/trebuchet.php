<?php
require __DIR__ . '/../vendor/autoload.php';
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

class TrebuchetCli extends CLI
{
    protected $conversions = [
        'one'   => '1',
        'two'   => '2',
        'three' => '3',
        'four'  => '4',
        'five'  => '5',
        'six'   => '6',
        'seven' => '7',
        'eight' => '8',
        'nine'  => '9'
    ];

    protected function setup(Options $options)
    {
        $options->setHelp('Day 1: Trebuchet?!');
        $options->registerArgument('input-file', 'Path to input', true);
        $options->registerOption('convert-text', '[Part 2] Convert text to numbers.');
    }

    protected function main(Options $options)
    {
        $inputPath = $options->getArgs()[0];
        $convertText = $options->getOpt('convert-text');

        if (!is_readable($inputPath)) {
            $this->fatal('File is not readable');
        }

        $inputContents = file_get_contents($inputPath);
        $entries = array_filter(explode(PHP_EOL, $inputContents));

        $answer = 0;

        foreach ($entries as $entry) {
            $origEntry = $entry;

            if ($convertText) {
                for ($i = 0; $i < strlen($entry); $i++) {
                    foreach ($this->conversions as $find => $replace) {
                        if (substr($entry, $i, strlen($find)) == $find) {
                            $entry = 
                                substr($entry, 0, $i) 
                                . $replace
                                // The last letter of the word can be used as the first letter of the next
                                . substr($entry, $i + strlen($find) - 1);

                            // Onto next character
                            break;
                        }


                        //$entry = str_replace($find, $replace, $entry);
                    }
                }

                $this->debug('Text Convert: ' . $origEntry . ' = ' . $entry);
            }

            $entry = preg_replace('/[^0-9]+/', '', $entry);
            $firstNumber = substr($entry, 0, 1);
            $lastNumber = substr($entry, -1, 1);

            $val = (int)($firstNumber . $lastNumber);

            $this->debug($origEntry . ' = ' . $val);

            $answer += $val;
        }

        $this->info($answer);
    }
}

(new TrebuchetCli())->run();