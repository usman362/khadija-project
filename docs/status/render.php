<?php
// Renders docs/status/*.html to docs/*.pdf. Sources live in the repo so a
// cleared scratchpad cannot lose them.
require __DIR__ . '/../../vendor/autoload.php';
$dir = __DIR__;
$out = dirname(__DIR__);
$map = [
    'pro-vs-client.html' => 'GigResource_Professional_Side_Status.pdf',
];
foreach ($map as $src => $pdf) {
    if (! is_file("$dir/$src")) { echo "skip $src\n"; continue; }
    $html = str_replace('<!--STYLE-->', file_get_contents("$dir/_style.html"), file_get_contents("$dir/$src"));
    $d = new Dompdf\Dompdf(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans']);
    $d->loadHtml($html, 'UTF-8');
    $d->setPaper('A4', 'portrait');
    $d->render();
    file_put_contents("$out/$pdf", $d->output());
    echo "rendered $pdf\n";
}
