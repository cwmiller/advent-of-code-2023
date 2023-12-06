<?php
require __DIR__ . '/../vendor/autoload.php';
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

class SeedPair {
    public $start;
    public $length;
}

class Mapping {
    public $sourceStart;
    public $destStart;
    public $length;
}

class MapGroup {
    public $name;
    public $from;
    public $to;
    public $mappings = [];
}

class SeedCli extends CLI
{
    protected function setup(Options $options)
    {
        $options->setHelp('Day 5: If You Give a Seed a Fertilizer');
        $options->registerArgument('input-file', 'Path to input', true);
        $options->registerOption('seed-pairs', '[Part 2] Seed input are in pairs', false);
    }

    protected function main(Options $options)
    {
        $inputPath = $options->getArgs()[0];
        $useSeedPairs = $options->getOpt('seed-pairs');

        if (!is_readable($inputPath)) {
            $this->fatal('File is not readable');
        }

        $inputContents = file_get_contents($inputPath);
        $lines = array_filter(explode(PHP_EOL, $inputContents));

        $seeds = [];
        $seedPairs = [];

        $mapGroups = [];

        /** @var $currentMapGroup MapGroup|null */
        $currentMapGroup = null;

        $lowestLocationId = NULL;

        foreach ($lines as $line) {
            // Does line define a new map?
            if (strpos($line, ':')) {
                list($ident, $data) = array_map('trim', explode(':', $line));

                if ($ident === 'seeds') {
                    // Seed list and not a mapping
                    $seeds = array_map('trim', explode(' ', $data));

                    $this->debug('Seeds: ' . implode(', ' , $seeds));

                    if ($useSeedPairs) {
                        $seedsCopy = $seeds;
                        $seedsCopy = array_reverse($seedsCopy);

                        while (count($seedsCopy) > 0) {
                            $seedPair = new SeedPair();
                            $seedPair->start = array_pop($seedsCopy);
                            $seedPair->length = array_pop($seedsCopy);

                            $seedPairs []= $seedPair;
                        }
                    }

                } else if (preg_match('/(.*) map/', $ident, $matches)) {
                    $mapName = $matches[1];

                    list($from, $to) = explode('-to-', $mapName);

                    $mapGroup = new MapGroup();
                    $mapGroup->name = $mapName;
                    $mapGroup->from = $from;
                    $mapGroup->to = $to;
                    $mapGroup->mappings = [];

                    $mapGroups[$mapName] = $mapGroup;
                    $currentMapGroup = $mapGroup;

                    $this->debug('Start new map: ' . $mapName);
                }
            } else {
                if (preg_match('/(\d+) (\d+) (\d+)/', $line, $matches)) {
                    $destStart = (int)$matches[1];
                    $srcStart = (int)$matches[2];
                    $length = (int)$matches[3];

                    $mapping = new Mapping();
                    $mapping->sourceStart = $srcStart;
                    $mapping->destStart = $destStart;
                    $mapping->length = $length;

                    if (!$currentMapGroup !== null) {
                        $currentMapGroup->mappings []= $mapping;

                        $this->debug('Add ' . $srcStart . ' / ' . $destStart . ' +' . $length . ' to ' . $currentMapGroup->name);
                    }
                }
            }
        }

        if ($useSeedPairs) {
            foreach ($seedPairs as $seedPair) {
                for ($i = 0; $i < $seedPair->length; $i++) {
                    $seed = $seedPair->start + $i;

                    $locationId = $this->walk($mapGroups, 'seed', $seed);

                    if ($lowestLocationId === NULL || $locationId < $lowestLocationId) {
                        $lowestLocationId = $locationId;
                    }
                }
            }
        } else {
            foreach ($seeds as $seed) {
                $locationId = $this->walk($mapGroups, 'seed', $seed);

                if ($lowestLocationId === NULL || $locationId < $lowestLocationId) {
                    $lowestLocationId = $locationId;
                }
            }
        }

        $this->info('Lowest location: ' . $lowestLocationId);
    }

    // Walk through the mappings until reaching "location" as the final value
    protected function walk(array $mapGroups, $sourceName, $sourceId) {
        $destId = $sourceId;
        $destName = $sourceName;

        $fromGroup = NULL;

        foreach ($mapGroups as $group) {
            if ($group->from === $sourceName) {
                $fromGroup = $group;
            }
        }

        if ($fromGroup !== null) {
            $destName = $fromGroup->to;

            // Find a mapping that includes the source ID
            foreach ($fromGroup->mappings as $mapping) {
                $sourceEnd = $mapping->sourceStart + $mapping->length - 1;

                if ($sourceId >= $mapping->sourceStart && $sourceId <= $sourceEnd) {
                    $delta = $sourceId - $mapping->sourceStart;

                    $destId = $mapping->destStart + $delta;
                }

                /*
                for ($i = 0; $i < $mapping->length; $i++) {
                    if (($mapping->sourceStart + $i) == $sourceId) {
                        $destId = $mapping->destStart + $i;
                        break 2;
                    }
                }
                */
            }

            $this->debug('Map ' . $sourceName . '/' . $sourceId . ' to ' . $destName . '/' . $destId);

        } else {
            $this->fatal('Couldn\'t find group to walk');
        }

        if ($destName === 'location') {
            return $destId;
        } else {
            return $this->walk($mapGroups, $destName, $destId);
        }
    }
}

(new SeedCli())->run();