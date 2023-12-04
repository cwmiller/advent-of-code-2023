<?php
require __DIR__ . '/../vendor/autoload.php';
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

class PartNumber {
    public $number;
    public $row;
    public $col;
    public $len;
}

class Gear {
    public $row;
    public $col;
    public $partNumbers = [];
}

class GearRatiosCli extends CLI
{
    protected function setup(Options $options)
    {
        $options->setHelp('Day 3: Gear Ratios');
        $options->registerArgument('input-file', 'Path to input', true);
    }

    protected function main(Options $options)
    {
        $inputPath = $options->getArgs()[0];

        if (!is_readable($inputPath)) {
            $this->fatal('File is not readable');
        }

        $partNumAnswer = 0;
        $gearRatioAnswer = 0;

        $inputContents = file_get_contents($inputPath);
        $lines = array_filter(explode(PHP_EOL, $inputContents));

        // Convert input into 2-dimensions array of Rows & Columns
        $rows = [];

        foreach ($lines as $line) {
            $row = [];

            for($i = 0; $i < strlen($line); $i++) {
                $char = $line[$i];

                if ($char === '.') {
                    $char = NULL;
                }

                $row[$i] = $char;
            }

            $rows []= $row;
        }

        $partNumbers = [];
        $gears = [];

        foreach ($rows as $rowIdx => $cols) {
            $activePartNumber = null;

            foreach ($cols as $colIdx => $colVal) {
                if ($this->isDigit($colVal)) {
                    if ($activePartNumber === NULL) {
                        $activePartNumber = new PartNumber();
                        $activePartNumber->number = $colVal;
                        $activePartNumber->row = $rowIdx;
                        $activePartNumber->col = $colIdx;
                        $activePartNumber->len = 1;

                        $partNumbers []= $activePartNumber;
                    } else {
                        $activePartNumber->number .= $colVal;
                        $activePartNumber->len++;
                    }
                } else {
                    $activePartNumber = NULL;
                }
            }
        }

        foreach ($partNumbers as $partNumber) {
            $qualifies = false;

            for ($rowIdx = $partNumber->row - 1; ($rowIdx <= $partNumber->row + 1); $rowIdx++) {
                if ($rowIdx >= 0 && $rowIdx < count($rows)) {
                    for ($colIdx = ($partNumber->col - 1); $colIdx <= ($partNumber->col + $partNumber->len); $colIdx++) {
                        if ($colIdx >= 0 && $colIdx < count($rows[$rowIdx])) {
                            if ($this->isSymbol($rows[$rowIdx][$colIdx])) {
                                if ($this->isGear($rows[$rowIdx][$colIdx])) {
                                    $this->trackGear($gears, $rowIdx, $colIdx, $partNumber);
                                }

                                $qualifies = true;
                            }
                        }
                    }
                }
            }

            if ($qualifies) {
                $partNumAnswer += (int)$partNumber->number;
            }
        }

        $this->info('Part Number SUM: ' . $partNumAnswer);

        foreach ($gears as $gear) {
            if (count($gear->partNumbers) === 2) {
                $gearRatioAnswer += ($gear->partNumbers[0]->number * $gear->partNumbers[1]->number);
            }
        }

        $this->info('Gear Ratio SUM: ' . $gearRatioAnswer);
    }

    private function trackGear(&$gears, $row, $col, PartNumber $partNumber) {
        $key = $row . '-' . $col;

        if (!array_key_exists($key, $gears)) {
            $gear = new Gear();
            $gear->col = $col;
            $gear->row = $row;
            $gear->partNumbers = [];
            $gears[$key] = $gear;
        }

        $gear = $gears[$key];

        $parNumberExistWithGear = false;
        foreach ($gear->partNumbers as $gearPartNumber) {
            if ($partNumber == $gearPartNumber) {
                $parNumberExistWithGear = true;
            }
        }

        if (!$parNumberExistWithGear) {
            $gear->partNumbers []= $partNumber;
        }
    }

    private static function isDigit($val) {
        return preg_match('/\d/', $val);
    }

    private static function isSymbol($val) {
        return $val !== NULL && preg_match('/\D/', $val);
    }

    private static function isGear($val) {
        return $val === '*';
    }
}

(new GearRatiosCli())->run();