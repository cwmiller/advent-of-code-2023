<?php
require __DIR__ . '/../vendor/autoload.php';
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

const FiveOfAKind = 6;
const FourOfAKind = 5;
const FullHouse = 4;
const ThreeOfAKind = 3;
const TwoPair = 2;
const OnePair = 1;
const HighCard = 0;

class Hand {
    public $raw;
    public $hex;
    public $type;
    public $handVal;
    public $bid = 0;
}

class CamelCardsCli extends CLI
{
    protected $cards = [
        '2',
        '3',
        '4',
        '5',
        '6',
        '7',
        '8',
        '9',
        'T',
        'J',
        'Q',
        'K',
        'A'
    ];

    protected $cardHexValues = [
        'T' => 'A',
        'J' => 'B',
        'Q' => 'C',
        'K' => 'D',
        'A' => 'E'
    ];

    protected function setup(Options $options)
    {
        $options->setHelp('Day 7: Camel Cards');
        $options->registerArgument('input-file', 'Path to input', true);
        $options->registerOption('enable-jokers', '[Part 2] Turn Js into Jokers');
    }

    protected function main(Options $options)
    {
        $inputPath = $options->getArgs()[0];
        $enableJokers = $options->getOpt('enable-jokers');

        if ($enableJokers) {
            $this->cardHexValues['J'] = '1';
        }

        if (!is_readable($inputPath)) {
            $this->fatal('File is not readable');
        }

        $inputContents = file_get_contents($inputPath);
        $lines = array_filter(explode(PHP_EOL, $inputContents));

        $hands = [];
        $answer = 0;

        foreach ($lines as $line) {
            if (preg_match('/^([2-9TJQKA]+) *(\d+)/', $line, $matches)) {
                $hexValue = $this->raw2hex($matches[1]);

                $hand = new Hand();
                $hand->raw = $matches[1];
                $hand->hex = $hexValue;
                $hand->handVal = hexdec($hexValue);
                $hand->type = $this->handType($matches[1], $enableJokers);
                $hand->bid = (int)$matches[2];

                $hands []= $hand;
            }
        }

        $this->sortHands($hands);

        foreach ($hands as $i => $hand) {
            $rank = $i + 1;
            $score = $hand->bid * $rank;

            $answer += $score;
        }
       

        $this->info($answer);
    }

    protected function raw2hex($cards) 
    {
        $hex = '';

        for ($i = 0; $i < strlen($cards); $i++) {
            $card = $cards[$i];
            $cardHex = $card;

            if (isset($this->cardHexValues[$card])) {
                $cardHex = $this->cardHexValues[$card];
            }

            $hex .= $cardHex;
        }

        return $hex;
    }

    protected function handType($cards, $enableJokers = false) {
        if ($enableJokers) {
            $variations = $this->allJokerVariations($cards);
        } else {
            $variations = [$cards];
        }

        $highestType = 0;

        foreach ($variations as $variation) {
            $counts = [];

            foreach ($this->cards as $card) {
                $counts[$card] = 0;
            }
    
            for ($i = 0; $i < strlen($variation); $i++) {
                $card = $variation[$i];
                $counts[$card] += 1;
            }
    
            $type = $this->determineType($counts);

            if ($type > $highestType) {
                $highestType = $type;
            }
        }

        return $highestType;
    }

    protected function allJokerVariations($cards)
    {
        $variations = [$cards];

        for ($i = 0; $i < strlen($cards); $i++) {
            $card = $cards[$i];

            if ($card === 'J') {
                $variations = array_merge($variations, $this->jokerVariations($variations, $i));
            }
        }

        return $variations;
    }

    protected function jokerVariations(array $variations, $idx) 
    {
        // Add an additional variation for all existing variations where J at the index $idx can be anything
        $newVariations = [];

        foreach ($variations as $variation) {
            foreach ($this->cards as $card) {
                $variation[$idx] = $card;

                $newVariations []= $variation;
            }
        }

        return $newVariations;
    }

    protected function determineType($cardCounts)
    {
        $cardCounts = array_values(array_filter($cardCounts, function($c) {
            return $c > 0;
        }));

        // Five of a kind?
        if (count($cardCounts) === 1) {
            return FiveOfAKind;
        }

        // Four of a kind ?
        foreach ($cardCounts as $count) {
            if ($count === 4) {
                return FourOfAKind;
            }
        }

        // Full house?
        if (count($cardCounts) === 2) {
            return FullHouse;
        }

        if (count($cardCounts) === 3) {
            foreach ($cardCounts as $count) {
                if ($count === 3) {
                    return ThreeOfAKind;
                }
            }

            return TwoPair;
        }

        if (count($cardCounts) === 4) {
            return OnePair;
        }

        return HighCard;
    }

    protected function sortHands(array &$hands) 
    {
        usort($hands, function($a, $b) {
            if ($a->type != $b->type) {
                return $a->type < $b->type ? -1 : 1;
            } else {
                if ($a->handVal == $b->handVal) {
                    return 0;
                }
                
                return $a->handVal < $b->handVal ? -1 : 1;
            }
        });
    }
}

(new CamelCardsCli())->run();