<?php
use GDO\UI\GDT_Panel;
use GDO\NeinGrep\Module_NeinGrep;
use GDO\NeinGrep\Method\Stats;

echo Module_NeinGrep::instance()->templatePHP('page/admin_menu.php');

$html = <<<EOT
<h1>Hello Dear Internet Client</h1>
<h2>This is NeinGrep, The 9gag scraper and search engine</h2>
<h3>Features</h3>
<ul>
 <li>Scraping of post metadata</li>
 <li>Scraping of comments</li>
 <li>Scraping of username<=>uid relations</li>
 <li>Revealing hidden OP</li>
 <li>Ranking of users and posts</li>
</ul>
<h3>Planned</h3>
<ul>
 <li>Friendmatching based on where they posted or commented</li>
 <li>Revealing of gender based on comment texts</li>
</ul>
EOT;

$box = GDT_Panel::withHTML($html);
echo $box->render();


echo Stats::make()->execute()->render();

