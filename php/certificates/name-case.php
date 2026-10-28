<?php

function certUpper(string $s): string
{
    return function_exists('mb_strtoupper') ? mb_strtoupper($s, 'UTF-8') : strtoupper($s);
}

function certLower(string $s): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
}

/**
 * "HANZ KERBY S. DACUCOS" -> "Hanz Kerby S. Dacucos"
 * Names that already have mixed capitals (McDonald, De la Cruz) are left exactly as they are.
 */
function certProperCase(string $s): string
{
    $s = trim(preg_replace('/\s+/', ' ', $s));

    if ($s === '' || (certUpper($s) !== $s && certLower($s) !== $s)) {
        return $s;
    }

    $s = certLower($s);

    // Capital after a space, hyphen, apostrophe or dot: Dela Cruz, Jean-Luc, O'Brien
    $s = preg_replace_callback('/(^|[\s\-\'’.(])(\p{L})/u', fn($m) => $m[1] . certUpper($m[2]), $s);

    // Roman numerals stay in capitals (III), and Mc gets its second capital (McDonald)
    $s = preg_replace_callback('/\b(Ii|Iii|Iv|Vi|Vii|Viii|Ix)\b/', fn($m) => strtoupper($m[1]), $s);
    $s = preg_replace_callback('/\bMc(\p{L})/u', fn($m) => 'Mc' . certUpper($m[1]), $s);

    return $s;
}