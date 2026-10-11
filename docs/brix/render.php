<?php
// Renders docs/brix/*.html to docs/*.pdf. Sources live in the repo so a
// cleared scratchpad cannot lose them again.
require '/Users/muhammadusman/Sites/khadija-project/vendor/autoload.php';
$dir = '/Users/muhammadusman/Sites/khadija-project/docs/brix';
$out = '/Users/muhammadusman/Sites/khadija-project/docs';
$map = [
    'brief.html'     => 'GigResource_Brix_Artwork_Brief.pdf',
    'coverage.html'  => 'GigResource_Brix_Event_Coverage.pdf',
    'placement.html' => 'GigResource_Brix_Placement_On_Site.pdf',
];
$only = $argv[1] ?? null;
foreach ($map as $src => $pdf) {
    if ($only && $src !== $only) continue;
    if (! is_file("$dir/$src")) { echo "skip $src\n"; continue; }
    $html = str_replace('<!--STYLE-->', file_get_contents("$dir/_style.html"), file_get_contents("$dir/$src"));
    $d = new Dompdf\Dompdf(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans']);
    $d->loadHtml($html, 'UTF-8');
    $d->setPaper('A4', 'portrait');
    $d->render();
    file_put_contents("$out/$pdf", $d->output());
    echo "rendered $pdf\n";
}
