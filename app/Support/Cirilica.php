<?php

namespace App\Support;

/**
 * Preslovljavanje srpske latinice u ćirilicu (obrasci su na ćirilici).
 */
class Cirilica
{
    /** Dvoslovi idu pre pojedinačnih slova; strtr uvek bira najduže poklapanje. */
    private const SLOVA = [
        'DŽ' => 'Џ', 'Dž' => 'Џ', 'dž' => 'џ',
        'LJ' => 'Љ', 'Lj' => 'Љ', 'lj' => 'љ',
        'NJ' => 'Њ', 'Nj' => 'Њ', 'nj' => 'њ',
        'A' => 'А', 'B' => 'Б', 'V' => 'В', 'G' => 'Г', 'D' => 'Д', 'Đ' => 'Ђ', 'E' => 'Е', 'Ž' => 'Ж',
        'Z' => 'З', 'I' => 'И', 'J' => 'Ј', 'K' => 'К', 'L' => 'Л', 'M' => 'М', 'N' => 'Н', 'O' => 'О',
        'P' => 'П', 'R' => 'Р', 'S' => 'С', 'T' => 'Т', 'Ć' => 'Ћ', 'U' => 'У', 'F' => 'Ф', 'H' => 'Х',
        'C' => 'Ц', 'Č' => 'Ч', 'Š' => 'Ш',
        'a' => 'а', 'b' => 'б', 'v' => 'в', 'g' => 'г', 'd' => 'д', 'đ' => 'ђ', 'e' => 'е', 'ž' => 'ж',
        'z' => 'з', 'i' => 'и', 'j' => 'ј', 'k' => 'к', 'l' => 'л', 'm' => 'м', 'n' => 'н', 'o' => 'о',
        'p' => 'п', 'r' => 'р', 's' => 'с', 't' => 'т', 'ć' => 'ћ', 'u' => 'у', 'f' => 'ф', 'h' => 'х',
        'c' => 'ц', 'č' => 'ч', 'š' => 'ш',
    ];

    public static function izLatinice(string $tekst): string
    {
        return strtr($tekst, self::SLOVA);
    }
}
