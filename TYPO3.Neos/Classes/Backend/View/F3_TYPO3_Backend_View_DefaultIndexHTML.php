<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3::Backend::View;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package TYPO3
 * @subpackage Backend
 * @version $Id$
 */

/**
 * The TYPO3 Backend View
 *
 * @package TYPO3
 * @subpackage Backend
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class DefaultIndexHTML extends F3::FLOW3::MVC::View::AbstractView {

	/**
	 * Renders the view
	 *
	 * @return string The rendered view
	 */
	public function render() {
		return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1 Transitional//EN">
			<html>
				<head>
					<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
					<title>TYPO3</title>
					<base href="' . (string)$this->request->getBaseURI() . '" />
					<link rel="stylesheet" href="Resources/Web/ExtJS/Public/CSS/ext-all.css" />
					<link rel="stylesheet" href="Resources/Web/ExtJS/Public/CSS/xtheme-gray.css" />
					<link rel="stylesheet" href="Resources/Web/TYPO3/Public/Backend/Media/Stylesheets/Backend.css" />
					<script type="text/javascript" src="Resources/Web/ExtJS/Public/JavaScript/adapter/ext/ext-base.js"></script>
					<script type="text/javascript" src="Resources/Web/ExtJS/Public/JavaScript/ext-all-debug.js"></script>
					<script type="text/javascript" src="Resources/Web/TYPO3/Public/Backend/JavaScript/ProcessingTreeLoader.js"></script>
					<script type="text/javascript" src="Resources/Web/TYPO3/Public/Backend/JavaScript/StructureTreeLoader.js"></script>
					<script type="text/javascript" src="Resources/Web/TYPO3/Public/Backend/JavaScript/SitesTreeLoader.js"></script>
					<script type="text/javascript" src="Resources/Web/TYPO3/Public/Backend/JavaScript/base.js"></script>
				</head>

				<body>
				</body>
			</html>
		';
	}

}


?>