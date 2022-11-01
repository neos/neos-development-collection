<?php
namespace Neos\Flow\Core\Migrations;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Replace legacy content cache tag definitions in Fusion
 *
 * - Replace legacy NodeType tags with the corresponding eel helper call and the current node as context
 * - Replace legacy DescendantOf tags with the corresponding eel helper call
 * - Replace legacy Node tags with the corresponding eel helper call
 */
class Version20220318111600 extends AbstractMigration
{

    public function getIdentifier(): string
    {
        return 'Neos.Neos-20220318111600';
    }

    public function up(): void
    {
        $this->searchAndReplaceRegex('/(.*) = \$?\{?\'NodeType_(.*)\'\}?/', "$1 = \${Neos.Caching.nodeTypeTag('$2', node)}", ['fusion']);
        $this->searchAndReplaceRegex('/(.*) = \$\{\'Node_\' \+ (.*)\.identifier\}/', "$1 = \${Neos.Caching.nodeTag($2)}", ['fusion']);
        $this->searchAndReplaceRegex('/(.*) = \$\{\'DescendantOf_\' \+ (.*)\.identifier\}/', "$1 = \${Neos.Caching.descendantOfTag($2)}", ['fusion']);
    }
}
