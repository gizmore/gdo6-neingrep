<?php
use GDO\UI\GDT_Bar;
use GDO\UI\GDT_Link;

echo GDT_Bar::makeWith(
	GDT_Link::make('link_admin_section')->href(href('Admin', 'Configure', '&module=NeinGrep')),
	GDT_Link::make('link_add_user')->href(href('NeinGrep', 'AddUser')),
	GDT_Link::make('link_add_post')->href(href('NeinGrep', 'AddPost')),
	GDT_Link::make('link_ranking')->href(href('NeinGrep', 'Ranking'))
)->horizontal()->renderCell();
