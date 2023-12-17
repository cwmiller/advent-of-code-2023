<?php
require __DIR__ . '/../vendor/autoload.php';
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

class Tile {
    private string $type;
    private bool $isEnergized = false;
    private $energizedSourceDirections = [];

    public static $types = [
        '.',
        '/',
        '\\',
        '|',
        '-'
    ];

    public function __construct($type)
    {
        if (!in_array($type, self::$types)) {
            throw new Exception('Unrecognized tile type: ' . $type);
        }

        $this->type = $type;
        $this->isEnergized = false;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function energize(Direction $sourceDirection)
    {
        $this->isEnergized = true;

        $this->energizedSourceDirections[] = $sourceDirection;
    }

    public function isEnergized(): bool
    {
        return $this->isEnergized;
    }

    public function isEnergizedFromDirection(Direction $direction): bool
    {
        return $this->isEnergized && in_array($direction, $this->energizedSourceDirections);
    }

    public function clear()
    {
        $this->isEnergized = false;
        $this->energizedSourceDirections = [];
    }
}

enum Direction 
{
    case N;
    case S;
    case E;
    case W;
}

class Beam {
    private int $startX;
    private int $startY;
    private int $curX;
    private int $curY;
    private Direction $curDirection;
    private bool $canMove;
    private ?Beam $parent = null;

    public function __construct(int $x, int $y, Direction $direction, ?Beam $parent = null)
    {
        $this->startX = $x;
        $this->startY = $y;
        $this->curX = $x;
        $this->curY = $y;
        $this->curDirection = $direction;
        $this->canMove = true;
        $this->parent = $parent;
    }

    public function getStartX(): int {
        return $this->startX;
    }

    public function getStartY(): int {
        return $this->startY;
    }

    public function getCurX(): int {
        return $this->curX;
    }

    public function getCurY(): int {
        return $this->curY;
    }

    public function getCurDirection(): Direction {
        return $this->curDirection;
    }

    public function canMove(): bool {
        return $this->canMove;
    }

    public function move(Direction $direction) 
    {
        switch ($direction) {
            case Direction::N:
                $this->curY--;
                break;
            case Direction::E:
                $this->curX++;
                break;
            case Direction::S:
                $this->curY++;
                break;
            case Direction::W:
                $this->curX--;
                break;
        }

        $this->curDirection = $direction;
    }

    public function stop()
    {
        $this->canMove = false;
    }
}

class Map {
    private array $data = [];
    private int $width;
    private int $height;

    public function __construct(int $width, int $height)
    {
        $this->data = [];
        $this->width = $width;
        $this->height = $height;

        for ($y = 0; $y < $height; $y++) {
            $this->data[$y] = [];

            for ($x = 0; $x < $width; $x++) {
                $this->data[$y][$x] = '.';
            }
        }
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getWidth(): int
    {
        return $this->width;;
    }

    public function setTile($x, $y, Tile $tile)
    {
        if (!isset($this->data[$y][$x])) {
            throw new Exception('Coordinates ' . $x . ', ' . $y . ' are outside map dimensions');
        }

        $this->data[$y][$x] = $tile;
    }

    public function getTile($x, $y): Tile
    {
        if (!isset($this->data[$y])) {
            throw new Exception('No tile at ' . $x . ', ' . $y);
        }

        if (!isset($this->data[$y][$x])) {
            throw new Exception('No tile at ' . $x . ', ' . $y);
        }

        return $this->data[$y][$x];
    }

    public function validCoord($x, $y): bool
    {
        if (isset($this->data[$y])) {
            return isset($this->data[$y][$x]);
        }

        return false;
    }

    public function clear()
    {
        foreach ($this->data as $rows) {
            foreach ($rows as $tile) {
                $tile->clear();
            }
        }
    }

    public function draw($showEnergized = false)
    {
        foreach ($this->data as $rows) {
            foreach ($rows as $tile) {
                $char = $tile->getType();

                if ($showEnergized && $tile->isEnergized()) {
                    $char = '#';
                }

                echo $char;
            }

            echo PHP_EOL;
        }
    }
}

class FloorWillBeLavaCli extends CLI
{
    protected function setup(Options $options)
    {
        $options->setHelp('Day 16: The Floor Will Be Lava');
        $options->registerArgument('input-file', 'Path to input', true);
        $options->registerOption('best-configuration', '[Part 2] Find best configuration from beam coming from any edge');
    }

    protected function main(Options $options)
    {
        $inputPath = $options->getArgs()[0];

        if (!is_readable($inputPath)) {
            $this->fatal('File is not readable');
        }

        $inputContents = file_get_contents($inputPath);
        $lines = array_values(array_filter(explode(PHP_EOL, $inputContents)));

        $map = new Map(strlen($lines[0]), count($lines));

        foreach ($lines as $y => $row) {
            $cols = str_split($row);

            foreach ($cols as $x => $col) {
                $tile = new Tile($col);
                $map->setTile($x, $y, $tile);
            }
        }

        $startBeams = [];

        if ($options->getOpt('best-configuration')) {
            for ($x = 0; $x < $map->getWidth(); $x++) {
                $startBeams []= new Beam($x, 0, Direction::S);
                $startBeams []= new Beam($x, $map->getHeight() - 1, Direction::N);
            }

            for ($y = 0; $y < $map->getHeight(); $y++) {
                $startBeams []= new Beam(0, $y, Direction::E);
                $startBeams []= new Beam($map->getWidth() - 1, $y, Direction::W);
            }
        } else {
            $startBeams []= new Beam(0, 0, Direction::E);
        }

        $best = 0;

        foreach ($startBeams as $startBeam) {
            $map->clear();
            $beams = [];

            // Create starting beam
            $beams []= $startBeam;

            while (true) {
                $activeBeams = array_filter($beams, function($beam) { return $beam->canMove(); });

                // We're done once all beams have hit an edge
                if (count($activeBeams) === 0) {
                    break;
                }

                foreach ($activeBeams as $beam) {
                    // Is the beam on a legit tile?
                    // The beam stops if it goes outside bounds
                    if (!$map->validCoord($beam->getCurX(), $beam->getCurY())) {
                        $beam->stop();
                        continue;
                    }

                    $tile = $map->getTile($beam->getCurX(), $beam->getCurY());

                    // Stop the beam if the current tile is already energized from the beams direction
                    if ($tile->isEnergizedFromDirection($beam->getCurDirection())) {
                        $beam->stop();
                        continue;
                    }

                    // Energive current position
                    $tile->energize($beam->getCurDirection());

                    $nextDirection = null;

                    //$this->info($beam->getCurX() . ', ' . $beam->getCurY() . ' = ' . $tile->getType());

                    // Attempt to move beam
                    switch ($tile->getType()) {
                        case '.':
                            // Keep moving
                            $nextDirection = $beam->getCurDirection();
                            break;
                        case '/':
                            // Move 90 degrees 
                            switch ($beam->getCurDirection()) {
                                case Direction::E:
                                    $nextDirection = Direction::N;
                                    break;
                                case Direction::W:
                                    $nextDirection = Direction::S;
                                    break;
                                case Direction::N:
                                    $nextDirection = Direction::E;
                                    break;
                                case Direction::S:
                                    $nextDirection = Direction::W;
                                    break;
                            }
                            break;
                        case '\\':
                            // Move 90 degrees
                            switch ($beam->getCurDirection()) {
                                case Direction::E:
                                    $nextDirection = Direction::S;
                                    break;
                                case Direction::W:
                                    $nextDirection = Direction::N;
                                    break;
                                case Direction::N:
                                    $nextDirection = Direction::W;
                                    break;
                                case Direction::S:
                                    $nextDirection = Direction::E;
                                    break;
                            }
                            break;
                        case '|':
                            switch ($beam->getCurDirection()) {
                                case Direction::E:
                                case Direction::W:
                                    // Split beam
                                    // Direct current beam north, new beam goes south
                                    $nextDirection = Direction::N;

                                    $beams []= new Beam($beam->getCurX(), $beam->getCurY() + 1, Direction::S, $beam);
                                    break;
                                case Direction::N:
                                case Direction::S:
                                    // Pass through
                                    $nextDirection = $beam->getCurDirection();
                                    break;

                            }
                            break;
                        case '-':
                                switch ($beam->getCurDirection()) {
                                    case Direction::E:
                                    case Direction::W:
                                        // Pass through
                                        $nextDirection = $beam->getCurDirection();
                                        break;
                                    case Direction::N:
                                    case Direction::S:
                                        // Split beam
                                        // Direct current beam west, new beam goes east
                                        $nextDirection = Direction::W;

                                        $beams []= new Beam($beam->getCurX() + 1, $beam->getCurY(), Direction::E, $beam);
                                        break;
        
                                }
                                break;
                    }

                    $beam->move($nextDirection);

                }
            }

            echo $map->draw(true);

            $numEnergized = 0;
            for ($x = 0; $x < $map->getWidth(); $x++) {
                for ($y = 0; $y < $map->getHeight(); $y++) {
                    $tile = $map->getTile($x, $y);

                    if ($tile->isEnergized()) {
                        $numEnergized++;
                    }
                }
            }

            $this->info('Tiles energized: ' . $numEnergized);

            if ($numEnergized > $best) {
                $best = $numEnergized;
            }
        }

        $this->info('Best: ' . $best);
    }
}

(new FloorWillBeLavaCli())->run();