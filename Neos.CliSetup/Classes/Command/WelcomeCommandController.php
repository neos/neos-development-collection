<?php

declare(strict_types=1);

namespace Neos\CliSetup\Command;

/*
 * This file is part of the Neos.CliSetup package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

#[Flow\Scope('singleton')]
class WelcomeCommandController extends CommandController
{

    public function indexCommand(): void
    {
        $this->output(
            <<<EOT
            <info>
                ....######          .######
                .....#######      ...######
                .......#######   ....######
                .........####### ....######
                ....#......#######...######
                ....##.......#######.######
                ....#####......############
                ....#####  ......##########
                ....#####    ......########
                ....#####      ......######
                .#######         ........

            Welcome to Neos.
            </info>

            The following steps will help you to configure Neos:

            1. Configure the database connection:
               <info>./flow setup:database</info>
            2. Create the required database tables:
               <info>./flow doctrine:migrate</info>
            3. Set up and check the required dependencies for a Content Repository instance:
                <info>./flow cr:setup</info>
            4. Migrate the existing data from the Legacy Content Repository:
                <info>./flow cr:migrateLegacyData --config '{"dbal": {"dbname": "YOUR-DATABASE-NAME"}, "resourcesPath": "/path/to/neos-8.0/Data/Persistent/Resources"}'</info>
            5. Configure the image handler:
               <info>./flow setup:imagehandler</info>
            6. Create an admin user:
               <info>./flow user:create --roles Administrator username password firstname lastname </info>
            7. Create your own site package or require an existing one (choose one option):
               - <info>./flow kickstart:site Vendor.Site</info>
               - <info>composer require neos/demo && ./flow flow:package:rescan</info>
            8. Import a site or create an empty one (choose one option):
               - <info>./flow site:import Neos.Demo</info>
               - <info>./flow site:import Vendor.Site</info>
               - <info>./flow site:create sitename Vendor.Site Vendor.Site:Document.HomePage</info>

            EOT
        );
    }

}
