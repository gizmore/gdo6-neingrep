<?php
use GDO\UI\GDT_Panel;

$html = <<<EOT
Here you can sort sections by various fields. It's kinda ranking.
EOT;

echo GDT_Panel::withHTML($html)->render();