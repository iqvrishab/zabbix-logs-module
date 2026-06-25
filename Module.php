<?php
declare(strict_types = 1);

namespace Modules\LogManager;

use Zabbix\Core\CModule;
use APP;
use CMenuItem;

class Module extends CModule {

	public function init(): void {
		APP::Component()->get('menu.main')
			->findOrAdd(_('Monitoring'))
				->getSubmenu()
				->add((new CMenuItem(_('Log Manager')))
					->setAction('logmanager.view')
				);
	}
}
