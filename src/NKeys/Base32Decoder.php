<?php

declare(strict_types=1);

namespace Basis\Nats\NKeys;

use InvalidArgumentException;

/**
 * @see https://github.com/selective-php/base32
 */
class Base32Decoder
{
    /**
     * @var array<string>
     */
    private const MAP = [
        'A',
        'B',
        'C',
        'D',
        'E',
        'F',
        'G',
        'H', //  7
        'I',
        'J',
        'K',
        'L',
        'M',
        'N',
        'O',
        'P', // 15
        'Q',
        'R',
        'S',
        'T',
        'U',
        'V',
        'W',
        'X', // 23
        'Y',
        'Z',
        '2',
        '3',
        '4',
        '5',
        '6',
        '7', // 31
        '=',  // padding char
    ];

    /**
     * @var array<string>
     */
    private const FLIPPED_MAP = [
        'A' => '0',
        'B' => '1',
        'C' => '2',
        'D' => '3',
        'E' => '4',
        'F' => '5',
        'G' => '6',
        'H' => '7',
        'I' => '8',
        'J' => '9',
        'K' => '10',
        'L' => '11',
        'M' => '12',
        'N' => '13',
        'O' => '14',
        'P' => '15',
        'Q' => '16',
        'R' => '17',
        'S' => '18',
        'T' => '19',
        'U' => '20',
        'V' => '21',
        'W' => '22',
        'X' => '23',
        'Y' => '24',
        'Z' => '25',
        '2' => '26',
        '3' => '27',
        '4' => '28',
        '5' => '29',
        '6' => '30',
        '7' => '31',
    ];

    /**
     * Decodes data encoded with base32.
     * @throws InvalidArgumentException
     */
    public function decode(string $input): string
    {
        if ($input === '') {
            return '';
        }

        $input = strtoupper($input);
        $paddingCharCount = substr_count($input, self::MAP[32]);
        $allowedValues = [6, 4, 3, 1, 0];

        if (!in_array($paddingCharCount, $allowedValues)) {
            throw new InvalidArgumentException('Invalid base32 data');
        }

        for ($i = 0; $i < 4; $i++) {
            if ($paddingCharCount === $allowedValues[$i] &&
                substr($input, -($allowedValues[$i])) !== str_repeat(self::MAP[32], $allowedValues[$i])
            ) {
                throw new InvalidArgumentException('Invalid base32 data');
            }
        }

        $input = str_replace('=', '', $input);
        $input = str_split($input);
        $binaryString = '';
        $count = count($input);

        for ($i = 0; $i < $count; $i += 8) {
            $x = '';

            if (!in_array($input[$i], self::MAP)) {
                throw new InvalidArgumentException('Invalid base32 data');
            }

            $x .= $this->decodeFlippedMap($i, $input);

            $eightBits = str_split($x, 8);
            $bitCount = count($eightBits);

            $binaryString .= $this->decodeEightBits($bitCount, $eightBits);
        }

        // Converting a binary (\0 terminated) string to a PHP string
        return rtrim($binaryString, "\0");
    }

    /**
     * Decode data with flipped map
     *
     * @param int $i The encoded data index
     * @param array<string> $input The encoded data array
     *
     * @return string parted decoded string
     */
    private function decodeFlippedMap(int $i, array $input): string
    {
        $x = '';

        for ($j = 0; $j < 8; $j++) {
            if (!isset($input[$i + $j])) {
                continue;
            }
            $x .= str_pad(base_convert(self::FLIPPED_MAP[$input[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
        }

        return $x;
    }

    /**
     * Decode data with eight bits
     *
     * @param int $bitCount The eight bits count
     * @param array<string> $eightBits The eight bits
     *
     * @return string
     */
    private function decodeEightBits(int $bitCount, array $eightBits): string
    {
        $binaryString = '';

        for ($z = 0; $z < $bitCount; $z++) {
            $binaryString .= (($y = chr((int)base_convert($eightBits[$z], 2, 10))) || ord($y) === 48) ? $y : '';
        }

        return $binaryString;
    }
}
