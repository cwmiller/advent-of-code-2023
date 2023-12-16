<?php
require __DIR__ . '/../vendor/autoload.php';
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

class Point {
    public $x;
    public $y;
    public $tile;
    public $partOfLoop = false;

    public function __construct($x, $y, $tile)
    {
        $this->x = $x;
        $this->y = $y;
        $this->tile = $tile;
    }
}

class PipeMazeCli extends CLI
{
    protected function setup(Options $options)
    {
        $options->setHelp('Day 10: Pipe Maze');
        $options->registerArgument('input-file', 'Path to input', true);
    }

    protected function main(Options $options)
    {
        $inputPath = $options->getArgs()[0];

        if (!is_readable($inputPath)) {
            $this->fatal('File is not readable');
        }

        $map = [];
        $steps = 0;

        $inputContents = file_get_contents($inputPath);
        $lines = explode(PHP_EOL, $inputContents);

        foreach ($lines as $y => $line) {
            $cols = str_split($line);

            foreach ($cols as $x => $tile) {
                $map[$y][$x] = new Point($x, $y, $tile);
            }
        }

        $startPoint = $this->startingPosition($map);
        $startPoint->partOfLoop = true;

        $this->info('Starting at ' . $startPoint->x . ', ' . $startPoint->y);
        
        $startExits = $this->validPointExits($map, $startPoint);

        if (count($startExits) !== 2) {
            $this->fatal('Expected 2 adjacent valid paths for start');
        }

        foreach ($startExits as $startExit) {
            $this->debug('Valid point from start: ' . $startExit->x . ', ' . $startExit->y);
        }

        $lastPoint = $startPoint;
        $pos = $startExits[0];
        //$steps++;

        while ($pos !== $startPoint) {
            $pos->partOfLoop = true;
            //$steps++;

            $this->debug($pos->x . ', ' . $pos->y . ': ' . $pos->tile);

            $nextSteps = $this->validPointExits($map, $pos);

            // Discard step we just came from
            $nextSteps = array_values(array_filter($nextSteps, function($p) use($lastPoint) {
                return $p !== $lastPoint;
            }));

            $lastPoint = $pos;
            $pos = $nextSteps[0];

            $this->debug('Moving to ' . $pos->x . ', ' . $pos->y);
        }

        $numSteps = 0;
        for ($y = 0; $y < count($map); $y++) {
            for ($x = 0; $x < count($map[$y]); $x++) {
                if ($map[$y][$x]->partOfLoop) {
                    $numSteps++;
                }
            }
        }

        $this->info($numSteps / 2);
    }

    // Get X,Y point of starting position
    protected function startingPosition(array $map): Point {
        for ($y = 0; $y < count($map); $y++) {
            for ($x = 0; $x < count($map[$y]); $x++) {
                if ($map[$y][$x]->tile === 'S') {
                    return $map[$y][$x];
                }
            }
        }

        $this->fatal('No starting point found');
    }

    // Find all X,Y coordinates that the given point can travel to
    protected function pointExitCoords(Point $point) {
        $points = [];
        $x = $point->x;
        $y = $point->y;

        switch ($point->tile) {
            case '|':
                $points []= [$x, $y - 1];
                $points []= [$x, $y + 1];
                break;

            case '-':
                $points []= [$x - 1, $y];
                $points []= [$x + 1, $y];
                break;

            case 'L':
                $points []= [$x, $y - 1];
                $points []= [$x + 1 , $y];
                break;

            case 'J':
                $points []= [$x, $y - 1];
                $points []= [$x - 1 , $y];
                break;

            case '7':
                $points []= [$x - 1, $y];
                $points []= [$x, $y + 1];
                break;

            case 'F':
                $points []= [$x + 1, $y];
                $points []= [$x, $y + 1];
                break;

            case '.':
                break;

            case 'S':
                $points []= [$x + 1, $y];
                $points []= [$x, $y + 1];
                $points []= [$x - 1, $y];
                $points []= [$x, $y - 1];
                break;
        }

        return $points;
    }

    // Find all X,Y points that the given point attaches to
    // Validates the other tile points back to the source
    /** @return Point[] */
    protected function validPointExits($map, Point $point): array
    {
        $exitCoords = $this->pointExitCoords($point);

        $validCoords = array_values(array_filter($exitCoords, function($coord) use ($map, $point) {
            $destX = $coord[0];
            $destY = $coord[1];

            // Verify points exist on map
            if (!isset($map[$destY]) || !isset($map[$destY][$destX])) {
                return false;
            }

            $destPoint = $map[$destY][$destX];

            // Check that this destination reaches back to source
            $destCoords = $this->pointExitCoords($destPoint);

            $found = false;
            foreach ($destCoords as $destCoord) {
                if ($destCoord[0] === $point->x && $destCoord[1] === $point->y) {
                    $found = true;
                }
            }

            return $found;
        }));

        return array_map(function($coord) use($map) {
            return $map[$coord[1]][$coord[0]];
        }, $validCoords);
    }
}

(new PipeMazeCli())->run();