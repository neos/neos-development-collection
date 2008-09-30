<?php
declare(ENCODING = 'utf-8');
namespace F3::TYPO3::Service::View::Tree;

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
 * @subpackage Service
 * @version $Id:F3::TYPO3::View::Page.php 262 2007-07-13 10:51:44Z robert $
 */

/**
 * JSON view for the Tree Show action
 *
 * @package TYPO3
 * @subpackage Service
 * @version $Id:F3::TYPO3::View::Page.php 262 2007-07-13 10:51:44Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ShowJSON extends F3::FLOW3::MVC::View::AbstractView {

	/**
	 * Renders this show view
	 *
	 * @return string The rendered JSON output
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function render() {
		$data = array(
			 "id" => "c7f7a3a1-d056-431a-bca8-d5ef7987e6a2",
			 "label" => "Home",
			 "childNodes" =>
					array(
						 "id" => "a85bdd39-d745-45b3-8efa-11ef612a8df0",
						 "label" => "About",
						 "childNodes" =>
						 array(),
						 "content" =>
								array(
									 "id" => "04ea77e4-900c-4d38-9450-96c31c9200c7",
									 "class" => "F3_TYPO3_Domain_Model_Page",
									 "language" => "en",
									 "country" => "GB"
								),
						 array(),
						 "leaf" => true
					),
					array(
						 "id" => "6fac11f5-bd2d-43c9-8f1d-0da715aca1cc",
						 "label" => "Downloads",
						 "childNodes" =>
								array(
									 "id" => "27af9b7a-043e-41ac-91dc-12aa0b0573f9",
									 "label" => "TYPO3",
									 "childNodes" =>
									 array(),
								 "content" =>

									 array(),
									 "leaf" => true
								),
								array(
									 "id" => "f6737389-940e-456f-9a4f-5e00be0ee761",
									 "childNodes" =>
											array(
												 "id" => "70501950-b04a-4c4d-92c3-49021d359ac8",
												 "label" => "Full Package",
												 "childNodes" =>
												 array(),
												 "content" =>

												 array(),
												 "leaf" => false
											),
											array(
												 "id" => "774ce834-9243-4cd4-8180-48f85592a3bb",
												 "label" => "Light Package",
												 "childNodes" =>
												 array(),
												 "content" =>

												 array(),
												 "leaf" => true
											),
									 array(),
									 "content" =>

									 array(),
									 "leaf" => false
								),
						 array(),
						 "leaf" => false,
						 "content" => array(),
					),
					array(
						 "id" => "979e90b8-a18a-41ea-95b0-1a3de37feaf8",
						 "label" => "Support",
						 "childNodes" =>
						 array(),
						 "leaf" => true,
						 "content" =>

						 array(),
					),
			 "content" => array(),
		);
		return json_encode($data);
	}
}
?>