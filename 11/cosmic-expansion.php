<?php
require __DIR__ . '/../vendor/autoload.php';
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;
use JMGQ\AStar\Node\NodeIdentifierInterface;
use JMGQ\AStar\DomainLogicInterface;
use JMGQ\AStar\AStar;
use JMGQ\AStar\SequencePrinter;

class Point implements NodeIdentifierInterface 
{
    public $x;
    public $y;
    public $isGalaxy;
    public $galaxyId = null;

    public function __construct($x, $y, $isGalaxy, $galaxyId = null)
    {
        $this->x = $x;
        $this->y = $y;
        $this->isGalaxy = $isGalaxy;
        $this->galaxyId = $galaxyId;
    }

    public function getUniqueNodeId(): string
    {
        return $this->x . 'x' . $this->y;
    }
}

class Pair
{
    public $a;
    public $b;
    public $distance;

    public function __construct(Point $a, Point $b, $distance) 
    {
        $this->a = $a;
        $this->b = $b;
        $this->distance = $distance;
    }
}

class CosmicExpansionCli extends CLI
{
    private $gGalaxyId = 0;

    protected function setup(Options $options)
    {
        $options->setHelp('Day 11: Cosmic Expansion');
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

        $sourceMap = [];

        foreach ($lines as $y => $line) {
            $sourceMap[$y] = str_split($line);
        }

        for ($y = 0; $y < count($sourceMap); $y++) {
            echo $y . ': ';
            for ($x = 0; $x < count($sourceMap[$y]); $x++) {
                echo $sourceMap[$y][$x];
            }

            echo PHP_EOL;
        }

        echo PHP_EOL;

        $expandedMap = $this->expandMap($sourceMap);

        for ($y = 0; $y < count($expandedMap); $y++) {
            echo $y . ': ';

            for ($x = 0; $x < count($expandedMap[$y]); $x++) {
                echo $expandedMap[$y][$x];
            }

            echo PHP_EOL;
        }

        $map = [];
        $galaxies = [];

        foreach ($expandedMap as $y => $rows) {
            foreach ($rows as $x => $char) {
                $galaxyId = $char === '#' ? $this->gGalaxyId++ : null;
                $point = new Point($x, $y, $galaxyId !== null, $galaxyId);

                $map[$y][$x] = $point;

                if ($galaxyId !== NULL) {
                    $galaxies []= $point;
                }
            }
        }

        $domainLogic = new DomainLogic($map);
        $aStar = new AStar($domainLogic);

        $this->info('Found ' . count($galaxies) . ' galaxies');

        $pairs = [];

        for ($i = 0; $i < count($galaxies); $i++) {
            for ($j = $i + 1; $j < count($galaxies); $j++) {
                $a = $galaxies[$i];
                $b = $galaxies[$j];

                $distance = count($aStar->run($a, $b)) - 1;

                $pair = new Pair($a, $b, $distance);
                $pairs []= $pair;
            }
        }

        $this->info('Found ' . count($pairs) . ' pairs of galaxies');

        $answer = 0;
        
        foreach ($pairs as $pair) {
            $answer += $pair->distance;
        }

        $this->info($answer);
    }

    private function expandMap($sourceMap)
    {
        $expandedMap = [];
        $expandedY = 0;

        foreach ($sourceMap as $y => $row) {
            $expandedMap[$expandedY++] = $row;

            // Does row contain any galaxies?
            $galaxies = array_filter($row, function($col) {
                return $col === '#';
            });

            if (count($galaxies) === 0) {
                // Push down a new row
                $this->info('Expanding row ' . $y);

                $expandedMap[$expandedY++] = array_values($row);
            }
        }
        
        for ($x = 0; $x < count($expandedMap[0]); $x++) {
            $hasGalaxies = false;

            for ($y = 0; $y < count($expandedMap); $y++) {
                if ($expandedMap[$y][$x] === '#') {
                    $hasGalaxies = true;
                }
            }

            if (!$hasGalaxies) {
                // Shift all cols right

                for ($sx = count($expandedMap[0]) ; $sx > $x; $sx--) {
                    for ($y = 0; $y < count($expandedMap); $y++) {

                        $expandedMap[$y][$sx] = $expandedMap[$y][$sx - 1];
                    }
                }

                $x++;
            }
        }

        return $expandedMap;
    }
}

class DomainLogic implements DomainLogicInterface
{
    private $map;

    public function __construct(array $map)
    {
        $this->map = $map;
    }

    public function getAdjacentNodes(mixed $node): iterable
    {
        $x = $node->x;
        $y = $node->y;
        $nodes = [];

        if ($x > 0) {
            $nodes []= $this->map[$y][$x - 1];
        }

        if ($x < count($this->map[0]) - 1) {
            $nodes []= $this->map[$y][$x + 1];
        }

        if ($y > 0) {
            $nodes []= $this->map[$y - 1][$x];
        }

        if ($y < (count($this->map) - 1)) {
            $nodes []= $this->map[$y + 1][$x];
        }

        return $nodes;
    }

    public function calculateRealCost(mixed $node, mixed $adjacent): float | int
    {
        return 1;
    }

    public function calculateEstimatedCost(mixed $fromNode, mixed $toNode): float | int
    {
        //return 1;

        $rowFactor = ($fromNode->y - $toNode->y) ** 2;
        $columnFactor = ($fromNode->x - $toNode->x) ** 2;

        return sqrt($rowFactor + $columnFactor);
    }
}

(new CosmicExpansionCli())->run();