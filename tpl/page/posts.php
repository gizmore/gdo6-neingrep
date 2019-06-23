<?php
use GDO\UI\GDT_Panel;

$html = <<<EOT
Here you can sort posts by various fields to mimic a ranking.
EOT;

echo GDT_Panel::withHTML($html)->render();