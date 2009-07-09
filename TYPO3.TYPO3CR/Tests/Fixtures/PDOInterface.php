<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 */

/**
 * PDOInterface so we can mock PDO using PHPUnit 3.4 - without the interface a
 * mock cannot be created because "You cannot serialize or unserialize PDO
 * instances"...
 *
 * @package TYPO3CR
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface PDOInterface {
	public function __construct($dsn, $username = NULL, $password = NULL, $driver_options = NULL);
	public function beginTransaction();
	public function commit();
	public function errorCode();
	public function errorInfo();
	public function exec($statement);
	public function getAttribute($attribute);
	public function getAvailableDrivers();
	public function lastInsertId($name = NULL);
	public function prepare($statement, $driver_options = array());
	public function query($statement);
	public function quote($string, $parameter_type = PDO::PARAM_STR);
	public function rollBack();
	public function setAttribute($attribute, $value);
}
?>