<?php
use GDO\NeinGrep\Server;
use GDO\User\GDO_User;
use GDO\Core\Application;
use GDO\DB\Database;
use GDO\Language\Trans;
use GDO\Core\ModuleLoader;
use GDO\Net\HTTP;

chdir(__DIR__);
chdir('../../../');

# Load config
include 'protected/config.php';
include 'GDO6.php';

final class NeinGrepServelet extends Application
{
	public function isCLI() { return true; }
}

$app = new NeinGrepServelet();
Database::init();
ModuleLoader::instance()->loadModules();
GDO_User::$CURRENT = GDO_User::system();
// HTTP::$DEBUG = true;

Server::make($argc, $argv)->init()->run();
