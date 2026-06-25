<?php

namespace Modules\LogManager;

use APP;
use CMenuItem;
use Core\CModule;

class Module extends CModule {

    /**
     * Initialize the module. Registers menu item.
     */
    public function init(): void {
        // Add Log Manager to the Monitoring menu
        APP::Component()->get('menu.main')
            ->findOrAdd(_('Monitoring'))
            ->getSubmenu()
            ->add((new CMenuItem(_('Log Manager')))
                ->setAction('logmanager.overview')
                ->setTitle(_('Log Manager'))
            );
    }
}
