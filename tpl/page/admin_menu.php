<?php
use GDO\UI\GDT_Bar;
use GDO\UI\GDT_Link;

echo GDT_Bar::makeWith(
// 	GDT_Link::make('ng_link_add_user')->href(href('NeinGrep', 'AddUser')),
	GDT_Link::make('ng_link_add_post')->href(href('NeinGrep', 'AddPost')),
	GDT_Link::make('ng_link_ranking')->href(href('NeinGrep', 'Ranking')),
	GDT_Link::make('ng_link_posts')->href(href('NeinGrep', 'Posts'))
)->horizontal()->renderCell();
