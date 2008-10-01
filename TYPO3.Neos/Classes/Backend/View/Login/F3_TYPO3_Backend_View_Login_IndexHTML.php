<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3::Backend::View::Login;

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
 * The TYPO3 Backend Login View
 *
 * @package TYPO3
 * @subpackage Backend
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class IndexHTML extends F3::FLOW3::MVC::View::AbstractView {

	/**
	 * Renders the view
	 *
	 * @return string The rendered view
	 */
	public function render() {
		return '<!DOCTYPE html
					 PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
					 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
			<?xml version="1.0" encoding="utf-8"?>

			<html>
				<head>
					<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
					<meta name="generator" content="TYPO3, http://typo3.org, &#169; The TYPO3 Development Team" />
					<title>TYPO3 Login</title>
					<base href="http://typo3.org/typo3/" />
					<link rel="stylesheet" type="text/css" href="stylesheet.css" />
					<style type="text/css" id="internalStyle">
						/*<![CDATA[*/

							/*###POSTCSSMARKER###*/
						/*]]>*/
					</style>
					<link rel="stylesheet" type="text/css" href="sysext/t3skin/stylesheets/stylesheet_post.css" />
				</head>
				<body id="typo3-index-php">
					<form action="" method="post" name="loginform">
					<table cellspacing="0" cellpadding="0" border="0" id="wrapper">
						<tr>
							<td class="c-wrappercell" align="center">
								<div id="loginimage">
									<img src="sysext/t3skin/icons/gfx/typo3logo.gif" width="128" height="59" alt="" />
								</div>
								<table cellspacing="0" cellpadding="0" border="0" id="loginwrapper">
									<tr>
										<td><img src="sysext/t3skin/images/login/loginimage_4_2.jpg" width="500" height="100" id="loginbox-image" alt="Photo by Photo by J.C. Franca (www.digitalphoto.com.br)" title="Photo by Photo by J.C. Franca (www.digitalphoto.com.br)" />
											<table cellspacing="0" cellpadding="0" border="0" id="logintable">
												<tr>
													<td colspan="2"><h2>Administration Login</h2></td>
												</tr>
												<tr class="c-username">
													<td><label for="username" class="c-username">Username:</label></td>
													<td><input type="text" id="username" name="F3::FLOW3::Security::Authentication::Token::UsernamePassword::username" value="" class="c-username" /></td>
												</tr>
												<tr class="c-password">
													<td><label for="password" class="c-password">Password:</label></td>
													<td><input type="password" id="password" name="F3::FLOW3::Security::Authentication::Token::UsernamePassword::password" value="" class="c-password" /></td>
												</tr>
												<tr class="c-submit">
													<td></td>
													<td><input type="submit" name="commandLI" value="Log In" class="c-submit" /></td>
												</tr>
												<tr class="c-info">
													<td colspan="2"><p class="c-info">(Note: Cookies and JavaScript must be enabled!)</p></td>
												</tr>
											</table>
										</td>
									</tr>
								</table>
								<div id="copyrightnotice">
									<a href="http://typo3.com/" target="_blank"><img src="gfx/loginlogo_transp.gif" alt="TYPO3 logo" align="left" />TYPO3 CMS</a>. Copyright &copy; The TYPO3 Development Team. Go to <a href="http://typo3.com/" target="_blank">http://typo3.com/</a> for details. TYPO3 comes with ABSOLUTELY NO WARRANTY; <a href="http://typo3.com/1316.0.html" target="_blank">click for details.</a> This is free software, and you are welcome to redistribute it under certain conditions; <a href="http://typo3.com/1316.0.html" target="_blank">click for details</a>. Obstructing the appearance of this notice is prohibited by law.
								</div>
							</td>
						</tr>
					</table>
				</form>
				</body>
			</html>
		';
	}
}

?>