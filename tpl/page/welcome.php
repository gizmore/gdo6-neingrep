<?php
use GDO\UI\GDT_Panel;

$html = <<<EOT
<h1>hi</h1>
EOT;

$box = GDT_Panel::withHTML($html);
echo $box->render();
