<?php
require __DIR__ . '/../vendor/autoload.php';
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

enum Direction {
    case N;
    case E;
    case S;
    case W;
}

class Node {
    public int $x;
    public int $y;
    public int $breadcrumbs;
    public bool $visited;

    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
        $this->breadcrumbs = 0;
        $this->visited = false;
    }

    public function dropBreadcrumb()
    {
        $this->breadcrumbs++;
        $this->visited = true;
    }

    public function eatBreadcrumb()
    {
        if ($this->breadcrumbs === 0) {
            throw new Exception('No breadcrumbs to eat on ' . $this->x . ', ' . $this->y);
        }

        $this->breadcrumbs--;
    }
}

class ClumsyCrucibleCli extends CLI
{
    protected function setup(Options $options)
    {
        $options->setHelp('Day 21: Step Counter');
        $options->registerArgument('input-file', 'Path to input', true);
        $options->registerArgument('steps', 'Number of steps allowed', true);
    }

    protected function main(Options $options)
    {
        $inputPath = $options->getArgs()[0];
        $allowedSteps = $options->getArgs()[1];

        if (!is_readable($inputPath)) {
            $this->fatal('File is not readable');
        }

        $inputContents = file_get_contents($inputPath);
        $lines = array_values(array_filter(explode(PHP_EOL, $inputContents)));

        $map = [];
        $nodes = [];
        $queue = [];

        foreach ($lines as $y => $row) {
            $cols = str_split($row);

            foreach ($cols as $x => $type) {
                if ($type !== '#') {
                    $node = new Node($x, $y);

                    if ($type === 'S') {
                        $node->dropBreadcrumb();
                    }

                    if (!isset($map[$y])) {
                        $map[$y] = [];
                    }

                    $map[$y][$x] = $node;
                    $nodes[] = $node;
                } else {
                    $map[$y][$x] = NULL;
                }
            }
        }

        $queue = [];

        while ($allowedSteps-- > 0) {
            //$this->info('Step');

            $points = array_filter($nodes, function($n) {
                return $n->breadcrumbs > 0;
            });

            $roundAdjacents = [];

            foreach ($points as $point) {
                $adjacents = $this->adjacentNodes($map, $point);

                foreach ($adjacents as $adjacent) {
                    $roundAdjacents[] = $adjacent; 
                }

                $point->eatBreadcrumb();
            }

            $roundAdjacents = array_unique($roundAdjacents, SORT_REGULAR);

            foreach ($roundAdjacents as $roundAdjacent) {
                $roundAdjacent->dropBreadcrumb();
            }
        }

        $answer = 0;

        foreach ($map as $y => $row) {
            foreach ($row as $x => $col) {
                if ($col === NULL) {
                    echo '#';
                } else {
                    echo $map[$y][$x]->breadcrumbs > 0 ? 'O' : '.';

                    if ($map[$y][$x]->breadcrumbs > 0) {
                        $answer++;
                    }
                }
            }

            echo PHP_EOL;
        }

        $this->info($answer);
    }

    private function adjacentNodes($map, Node $node) 
    {
        $adjCoords = [];
        $adjNodes = [];

        if ($node->x > 0) {
            $adjCoords[] = [$node->x - 1, $node->y];
        }

        $adjCoords[] = [$node->x + 1, $node->y];

        if ($node->y > 0) {
            $adjCoords[] = [$node->x, $node->y - 1];
        }

        $adjCoords[] = [$node->x, $node->y + 1];

        foreach ($adjCoords as $adjCoord) {
            list($adjX, $adjY) = $adjCoord;

            if (isset($map[$adjY]) && isset($map[$adjY][$adjX]) && $map[$adjY][$adjX] !== NULL) {
                $adjNodes[] = $map[$adjY][$adjX];
            }
        }

        return $adjNodes;
    }
}

(new ClumsyCrucibleCli())->run();