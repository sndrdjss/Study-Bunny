<?php
/* =========================================================
   StudyBuddy — egg & pet SVG art
   render_egg($type, $cracks, $extraClass) and render_pet($pet, $extraClass)
   return inline SVG strings (chunky outlines, blush, shiny eyes).
   Every SVG gets a unique id prefix so many can share one page.
   Animation hooks (styled in css/pet.css):
     .egg-crack (+ data-stage)   .pet-eyes (blink)
     .pet-ear / .pet-ear--floppy (twitch)   .pet-tail (wag)
   Requires pets.php (EGGS, PET_NAMES).
   ========================================================= */

const ART_INK   = '#3B2F45'; // outline colour, reads on light + dark
const ART_BLUSH = '#FF9EB5';

function art_uid(string $p): string
{
    static $n = 0;
    $n++;
    return $p . $n . '_' . bin2hex(random_bytes(2));
}

function art_attr(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/* ---------------- small decorations ---------------- */

function art_paw(float $x, float $y, string $fill): string
{
    return sprintf(
        '<g fill="%s"><ellipse cx="%.1f" cy="%.1f" rx="6" ry="5"/><circle cx="%.1f" cy="%.1f" r="2.4"/><circle cx="%.1f" cy="%.1f" r="2.4"/><circle cx="%.1f" cy="%.1f" r="2.4"/><circle cx="%.1f" cy="%.1f" r="2.4"/></g>',
        $fill, $x, $y, $x - 6.5, $y - 6, $x - 2.3, $y - 9.5, $x + 2.3, $y - 9.5, $x + 6.5, $y - 6
    );
}

function art_snowflake(float $x, float $y, float $s): string
{
    $out = '';
    foreach ([0, 60, 120] as $deg) {
        $r  = deg2rad($deg);
        $dx = cos($r) * $s;
        $dy = sin($r) * $s;
        $out .= sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f"/>', $x - $dx, $y - $dy, $x + $dx, $y + $dy);
    }
    return '<g stroke="#5FB2E6" stroke-width="2.2" stroke-linecap="round">' . $out . '</g>';
}

function art_leaf(float $x, float $y, float $rot): string
{
    return sprintf(
        '<g transform="translate(%.1f %.1f) rotate(%.1f)"><path d="M0 0 C 6 -8, 18 -8, 22 0 C 18 8, 6 8, 0 0 Z" fill="#3F9E52"/><path d="M2 0 L19 0" stroke="#2E7D3F" stroke-width="1.6" stroke-linecap="round"/></g>',
        $x, $y, $rot
    );
}

/* ---------------- eggs ---------------- */

const EGG_PATH = 'M60 8 C 92 8, 108 58, 108 86 C 108 114, 87 132, 60 132 C 33 132, 12 114, 12 86 C 12 58, 28 8, 60 8 Z';

/** Crack lines, one added per perfect score (stage 1..5). */
const EGG_CRACKS = [
    1 => 'M80 30 l-7 9 l6 6 l-6 9',
    2 => 'M20 74 l10 -3 l3 9 l10 -4',
    3 => 'M50 20 l6 8 l-5 7 l7 6',
    4 => 'M101 92 l-9 2 l-2 9 l-9 1',
    5 => 'M14 66 l12 -6 l10 8 l12 -8 l10 8 l12 -8 l12 8 l12 -6 l12 5',
];

function egg_pattern(string $type): string
{
    switch ($type) {
        case 'cozy':
            return art_paw(34, 98, '#F2A07B') . art_paw(84, 70, '#F2A07B') . art_paw(48, 62, '#F7BC9C')
                . art_paw(78, 112, '#F7BC9C')
                . '<path d="M60 33 C 54 27, 46 32, 52 38 L60 45 L68 38 C 74 32, 66 27, 60 33 Z" fill="#FF7A9C"/>';
        case 'forest':
            return '<g fill="#57A862" opacity=".55"><rect x="26" y="0" width="9" height="140"/><rect x="84" y="0" width="9" height="140"/></g>'
                . '<g stroke="#3F8A4C" stroke-width="2.4" stroke-linecap="round"><path d="M26 48 h9 M26 92 h9 M84 40 h9 M84 84 h9"/></g>'
                . art_leaf(38, 44, -30) . art_leaf(70, 66, 20) . art_leaf(42, 86, 10) . art_leaf(64, 30, -60)
                . '<rect x="55" y="111" width="10" height="13" rx="4" fill="#FFF8EC"/>'
                . '<path d="M45 114 C 45 99, 75 99, 75 114 Z" fill="#F05A5A"/>'
                . '<g fill="#FFFFFF"><circle cx="54" cy="107" r="2.4"/><circle cx="64" cy="105" r="2"/><circle cx="69" cy="110" r="1.6"/></g>';
        case 'frost':
        default:
            return '<polygon points="10,86 22,79 34,88 46,79 58,88 70,79 82,88 94,79 110,86 110,101 98,94 86,103 74,94 62,103 50,94 38,103 26,94 10,101" fill="#FFFFFF" opacity=".6" stroke="#7FC3EA" stroke-width="2"/>'
                . art_snowflake(40, 42, 6) . art_snowflake(78, 56, 4.5) . art_snowflake(58, 116, 5) . art_snowflake(30, 66, 3.5)
                . art_snowflake(88, 116, 3.5);
    }
}

const EGG_FILLS = [
    'cozy'   => ['#FFF6E8', '#FFD2B0'],
    'forest' => ['#C6EDAE', '#72C277'],
    'frost'  => ['#F2FAFF', '#A6D6F4'],
];

/**
 * @param string $type   cozy|forest|frost
 * @param int    $cracks 0..5 visible crack lines
 */
function render_egg(string $type, int $cracks = 0, string $extraClass = ''): string
{
    if (!isset(EGGS[$type])) {
        $type = 'cozy';
    }
    $cracks = max(0, min(PERFECTS_TO_HATCH, $cracks));
    $uid    = art_uid('egg');
    [$c1, $c2] = EGG_FILLS[$type];

    $label = EGGS[$type]['name'] . ($cracks > 0 ? ", $cracks of " . PERFECTS_TO_HATCH . ' cracks' : '');

    $crackSvg = '';
    for ($i = 1; $i <= $cracks; $i++) {
        $crackSvg .= sprintf(
            '<path class="egg-crack" data-stage="%d" d="%s" fill="none" stroke="%s" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>',
            $i, EGG_CRACKS[$i], ART_INK
        );
    }

    return sprintf(
        '<svg class="egg-art egg-art--%1$s %2$s" viewBox="0 0 120 140" role="img" aria-label="%3$s" data-cracks="%4$d" xmlns="http://www.w3.org/2000/svg">'
        . '<defs>'
        . '<linearGradient id="%5$s_g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="%6$s"/><stop offset="1" stop-color="%7$s"/></linearGradient>'
        . '<clipPath id="%5$s_c"><path d="%8$s"/></clipPath>'
        . '</defs>'
        . '<ellipse cx="60" cy="134" rx="34" ry="4.5" fill="#000" opacity=".16"/>'
        . '<g class="egg-body">'
        . '<path d="%8$s" fill="url(#%5$s_g)"/>'
        . '<g clip-path="url(#%5$s_c)">%9$s'
        . '<ellipse cx="60" cy="126" rx="46" ry="14" fill="#000" opacity=".07"/>'
        . '%10$s</g>'
        . '<ellipse cx="40" cy="42" rx="8" ry="15" transform="rotate(-22 40 42)" fill="#FFFFFF" opacity=".6"/>'
        . '<path d="%8$s" fill="none" stroke="%11$s" stroke-width="4" stroke-linejoin="round"/>'
        . '</g></svg>',
        $type,
        art_attr($extraClass),
        art_attr($label),
        $cracks,
        $uid,
        $c1,
        $c2,
        EGG_PATH,
        egg_pattern($type),
        $crackSvg,
        ART_INK
    );
}

/* ---------------- pets ---------------- */

/** Big shiny eyes in a blink group. */
function art_eyes(float $lx, float $rx, float $y): string
{
    $eye = static fn ($x) => sprintf(
        '<ellipse cx="%.1f" cy="%.1f" rx="5.6" ry="7" fill="#2B2233"/><circle cx="%.1f" cy="%.1f" r="2.2" fill="#FFFFFF"/><circle cx="%.1f" cy="%.1f" r="1" fill="#FFFFFF"/>',
        $x, $y, $x - 1.8, $y - 2.8, $x + 2, $y + 2.4
    );
    return '<g class="pet-eyes">' . $eye($lx) . $eye($rx) . '</g>';
}

function art_blush(float $lx, float $rx, float $y): string
{
    return sprintf(
        '<g fill="%s" opacity=".75"><ellipse cx="%.1f" cy="%.1f" rx="6.5" ry="3.6"/><ellipse cx="%.1f" cy="%.1f" rx="6.5" ry="3.6"/></g>',
        ART_BLUSH, $lx, $y, $rx, $y
    );
}

function art_mouth(float $y): string
{
    return sprintf('<path d="M54 %.1f q3 4 6 0 q3 4 6 0" fill="none" stroke-width="2.6"/>', $y);
}

/** Thick outlined stroke (for tails): dark under-stroke, colour on top. */
function art_tube(string $d, string $fill, float $w, string $class = ''): string
{
    return sprintf(
        '<g class="%s"><path d="%s" fill="none" stroke="%s" stroke-width="%.1f" stroke-linecap="round"/><path d="%s" fill="none" stroke="%s" stroke-width="%.1f" stroke-linecap="round"/></g>',
        $class, $d, ART_INK, $w + 7, $d, $fill, $w
    );
}

function pet_body(string $pet): string
{
    switch ($pet) {
        case 'cat':
            $c = '#F7B267';
            return art_tube('M84 104 C 104 106, 110 86, 100 74', $c, 9, 'pet-tail')
                . '<ellipse cx="60" cy="102" rx="26" ry="16" fill="' . $c . '"/>'
                . '<g class="pet-ear"><polygon points="24,46 30,12 54,32" fill="' . $c . '"/><polygon points="31,38 33,22 45,32" fill="#FFB3C6" stroke="none"/></g>'
                . '<g class="pet-ear pet-ear--r"><polygon points="96,46 90,12 66,32" fill="' . $c . '"/><polygon points="89,38 87,22 75,32" fill="#FFB3C6" stroke="none"/></g>'
                . '<ellipse cx="60" cy="60" rx="38" ry="32" fill="' . $c . '"/>'
                . '<g stroke="#D9822B" stroke-width="3"><path d="M60 30 v8 M50 32 l2 6 M70 32 l-2 6"/></g>'
                . '<ellipse cx="60" cy="75" rx="13" ry="8.5" fill="#FFE8CF" stroke="none"/>'
                . art_eyes(45, 75, 60)
                . '<path d="M56 70 L64 70 L60 74 Z" fill="#FF7A9C" stroke-width="2"/>'
                . art_mouth(75)
                . '<g stroke-width="2"><path d="M26 70 L40 72 M26 78 L40 76 M94 70 L80 72 M94 78 L80 76"/></g>'
                . art_blush(35, 85, 72)
                . '<ellipse cx="47" cy="114" rx="8" ry="5" fill="' . $c . '"/><ellipse cx="73" cy="114" rx="8" ry="5" fill="' . $c . '"/>';

        case 'dog':
            $c = '#E3B27A';
            $e = '#9C6438';
            return art_tube('M84 100 q16 -2 18 -18', $c, 8, 'pet-tail')
                . '<ellipse cx="60" cy="102" rx="26" ry="16" fill="' . $c . '"/>'
                . '<ellipse cx="60" cy="60" rx="36" ry="32" fill="' . $c . '"/>'
                . '<ellipse cx="77" cy="57" rx="10" ry="11" fill="#C98C55" stroke="none"/>'
                . '<g class="pet-ear pet-ear--floppy"><ellipse cx="25" cy="54" rx="11" ry="21" transform="rotate(22 25 54)" fill="' . $e . '"/></g>'
                . '<g class="pet-ear pet-ear--floppy pet-ear--r"><ellipse cx="95" cy="54" rx="11" ry="21" transform="rotate(-22 95 54)" fill="' . $e . '"/></g>'
                . '<ellipse cx="60" cy="77" rx="16" ry="11" fill="#FFF1DE"/>'
                . art_eyes(45, 75, 58)
                . '<ellipse cx="60" cy="71" rx="6" ry="4.5" fill="#2B2233"/>'
                . '<path d="M57 81 q3 9 6 0 Z" fill="#FF7A9C" stroke-width="2"/>'
                . art_mouth(77)
                . art_blush(34, 86, 72)
                . '<ellipse cx="47" cy="114" rx="8" ry="5" fill="' . $c . '"/><ellipse cx="73" cy="114" rx="8" ry="5" fill="' . $c . '"/>';

        case 'bunny':
            $c = '#FFFFFF';
            return '<g class="pet-ear"><ellipse cx="44" cy="27" rx="9.5" ry="23" fill="' . $c . '"/><ellipse cx="44" cy="29" rx="4.2" ry="16" fill="#FFB3C6" stroke="none"/></g>'
                . '<g class="pet-ear pet-ear--r"><ellipse cx="76" cy="27" rx="9.5" ry="23" fill="' . $c . '"/><ellipse cx="76" cy="29" rx="4.2" ry="16" fill="#FFB3C6" stroke="none"/></g>'
                . '<ellipse cx="60" cy="102" rx="26" ry="16" fill="#F3EDFA"/>'
                . '<ellipse cx="60" cy="63" rx="36" ry="31" fill="' . $c . '"/>'
                . art_eyes(46, 74, 62)
                . '<ellipse cx="60" cy="71" rx="3.8" ry="2.8" fill="#FF7A9C" stroke="none"/>'
                . '<path d="M56 75 q4 4 4 0 q0 4 4 0" fill="none" stroke-width="2.4"/>'
                . '<rect x="57.5" y="77" width="5" height="5" rx="1.2" fill="#FFFFFF" stroke-width="1.8"/>'
                . art_blush(36, 84, 73)
                . '<ellipse cx="47" cy="114" rx="8" ry="5" fill="' . $c . '"/><ellipse cx="73" cy="114" rx="8" ry="5" fill="' . $c . '"/>';

        case 'panda':
            $k = '#2B2233';
            $eye = static fn ($x) => sprintf(
                '<circle cx="%.1f" cy="62" r="4.6" fill="#FFFFFF" stroke="none"/><circle cx="%.1f" cy="62.5" r="2.9" fill="#2B2233" stroke="none"/><circle cx="%.1f" cy="61.2" r="1.1" fill="#FFFFFF" stroke="none"/>',
                $x, $x, $x - 1
            );
            return '<g class="pet-ear"><circle cx="30" cy="33" r="11" fill="' . $k . '"/></g>'
                . '<g class="pet-ear pet-ear--r"><circle cx="90" cy="33" r="11" fill="' . $k . '"/></g>'
                . '<ellipse cx="60" cy="102" rx="26" ry="16" fill="' . $k . '"/>'
                . '<ellipse cx="60" cy="104" rx="14" ry="9" fill="#FFFFFF" stroke="none"/>'
                . '<ellipse cx="60" cy="60" rx="37" ry="32" fill="#FFFFFF"/>'
                . '<ellipse cx="45" cy="62" rx="9" ry="11.5" transform="rotate(-28 45 62)" fill="' . $k . '" stroke="none"/>'
                . '<ellipse cx="75" cy="62" rx="9" ry="11.5" transform="rotate(28 75 62)" fill="' . $k . '" stroke="none"/>'
                . '<g class="pet-eyes">' . $eye(46) . $eye(74) . '</g>'
                . '<ellipse cx="60" cy="72" rx="4.6" ry="3.2" fill="' . $k . '"/>'
                . art_mouth(76)
                . art_blush(33, 87, 75)
                . '<ellipse cx="47" cy="114" rx="8" ry="5" fill="' . $k . '"/><ellipse cx="73" cy="114" rx="8" ry="5" fill="' . $k . '"/>';

        case 'penguin':
            $n = '#34405F';
            return '<ellipse cx="48" cy="116" rx="9" ry="4.2" fill="#FFA94D"/><ellipse cx="72" cy="116" rx="9" ry="4.2" fill="#FFA94D"/>'
                . '<g class="pet-ear pet-ear--floppy"><ellipse cx="22" cy="84" rx="8" ry="18" transform="rotate(28 22 84)" fill="' . $n . '"/></g>'
                . '<g class="pet-ear pet-ear--floppy pet-ear--r"><ellipse cx="98" cy="84" rx="8" ry="18" transform="rotate(-28 98 84)" fill="' . $n . '"/></g>'
                . '<ellipse cx="60" cy="68" rx="40" ry="46" fill="' . $n . '"/>'
                . '<path d="M56 23 q4 -10 8 0" fill="none" stroke-width="3"/>'
                . '<path d="M60 44 C 40 30, 22 50, 30 70 C 34 82, 46 88, 60 88 C 74 88, 86 82, 90 70 C 98 50, 80 30, 60 44 Z" fill="#FFFFFF" stroke="none"/>'
                . '<ellipse cx="60" cy="97" rx="22" ry="15" fill="#FFFFFF" stroke="none"/>'
                . art_eyes(47, 73, 62)
                . '<path d="M53 72 L67 72 L60 80 Z" fill="#FFA94D" stroke-width="2.4"/>'
                . art_blush(37, 83, 76);

        case 'ice_bear':
        default:
            $f = '#F7FAFC';
            return '<g class="pet-ear"><circle cx="31" cy="34" r="10.5" fill="' . $f . '"/><circle cx="31" cy="34" r="5" fill="#C9DCEB" stroke="none"/></g>'
                . '<g class="pet-ear pet-ear--r"><circle cx="89" cy="34" r="10.5" fill="' . $f . '"/><circle cx="89" cy="34" r="5" fill="#C9DCEB" stroke="none"/></g>'
                . '<ellipse cx="60" cy="102" rx="27" ry="16" fill="' . $f . '"/>'
                . '<ellipse cx="60" cy="58" rx="36" ry="31" fill="' . $f . '"/>'
                . '<path d="M30 86 Q60 100 90 86 L90 95 Q60 109 30 95 Z" fill="#4FA3E0"/>'
                . '<rect x="72" y="94" width="10" height="15" rx="3" transform="rotate(-12 77 101)" fill="#4FA3E0"/>'
                . '<ellipse cx="60" cy="72" rx="13" ry="9" fill="#FFFFFF"/>'
                . art_eyes(46, 74, 57)
                . '<ellipse cx="60" cy="68" rx="5.6" ry="4" fill="#2B2233"/>'
                . art_mouth(73)
                . art_blush(35, 85, 70)
                . '<ellipse cx="47" cy="114" rx="8" ry="5" fill="' . $f . '"/><ellipse cx="73" cy="114" rx="8" ry="5" fill="' . $f . '"/>';
    }
}

function render_pet(string $pet, string $extraClass = ''): string
{
    if (!isset(PET_NAMES[$pet])) {
        $pet = 'bunny';
    }
    return sprintf(
        '<svg class="pet-art pet-art--%1$s %2$s" viewBox="0 0 120 124" role="img" aria-label="%3$s" xmlns="http://www.w3.org/2000/svg">'
        . '<ellipse cx="60" cy="119" rx="32" ry="4.5" fill="#000" opacity=".16"/>'
        . '<g class="pet-body" stroke="%4$s" stroke-width="3.5" stroke-linejoin="round" stroke-linecap="round">%5$s</g>'
        . '</svg>',
        art_attr($pet),
        art_attr($extraClass),
        art_attr(PET_NAMES[$pet]),
        ART_INK,
        pet_body($pet)
    );
}
