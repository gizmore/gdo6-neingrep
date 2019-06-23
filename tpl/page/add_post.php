<?php
use GDO\UI\GDT_Panel;

$html = <<<EOT
Add a post and reveal the OP.
If not revealed yet, rank this post up in queue.
EOT;

echo GDT_Panel::withHTML($html)->render();
?>
