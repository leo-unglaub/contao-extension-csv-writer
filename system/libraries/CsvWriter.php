<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  2create.at 2011
 * @author     Leo Unglaub <leo@leo-unglaub.net>
 * @package    CsvWriter
 * @license    LGPL
 * @filesource
 */


/**
 * Class CsvWriter
 * Contain methods with help you to generate valid csv files
 * @see http://tools.ietf.org/html/rfc4180
 */
class CsvWriter
{
	/**
	 * An array with contains the fields
	 * @var array
	 */
	protected $arrContent = array();


	/**
	 * An array with the header fields
	 * @var array
	 */
	protected $arrHeaderFields = array();


	/**
	 * Convert the file to and excel compat file
	 * @var bool
	 */
	protected $blnExcel = false;


	/**
	 * The field delimiter
	 * @var string
	 */
	protected $strDelimiter = '"';


	/**
	 * The field seperator
	 * @var string
	 */
	protected $strSeperator = ',';


	/**
	 * The line end
	 * @var string
	 */
	protected $strLineEnd = "\r\n";


	/**
	 * The name of the file without the file extension
	 * @var string
	 */
	protected $strFileName = 'file';



	/**
	 * Set an object parameter
	 *
	 * @param string $strKey
	 * @param mixed $varValue
	 * @return void
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey)
		{
			case 'content':
				$this->arrContent = $varValue;
				break;

			case 'headerFields':
				$this->arrHeaderFields = $varValue;
				break;

			case 'excel':
				$this->blnExcel = (bool) $varValue;

				// excel always need ; as seperator and " as delimiter. So we set it for the developer
				if ($this->blnExcel)
				{
					$this->strSeperator = ';';
					$this->strDelimiter = '"';
				}
				break;

			case 'delimiter':
				$this->strDelimiter = $varValue;
				break;

			case 'seperator':
				$this->strSeperator = $varValue;
				break;

			case 'lineEnd':
				$this->strLineEnd = $varValue;
				break;

			case 'fileName':
				$this->strFileName = $varValue;
				break;

			default:
				throw new Exception(sprintf('Invalid argument "%s"', $strKey));
				break;
		}
	}


	/**
	 * Return an object property
	 *
	 * @param string $strKey
	 * @return mixed
	 */
	public function __get($strKey)
	{
		switch ($strKey)
		{
			case 'content':
				return $this->arrContent;
				break;

			case 'headerFields':
				return $this->arrHeaderFields;
				break;

			case 'excel':
				return $this->blnExcel;
				break;

			case 'delimiter':
				return $this->strDelimiter;
				break;

			case 'seperator':
				return $this->strSeperator;
				break;

			case 'lineEnd':
				return $this->strLineEnd;
				break;

			case 'fileName':
				return $this->strFileName;
				break;

			default:
				return null;
				break;
		}
	}


	/**
	 * Return true if no lines are in the content
	 *
	 * @param	void
	 * @return	bool	Return true if there are no lines.
	 */
	public function isEmpty()
	{
		return empty($this->arrContent);
	}


	/**
	 * Append an array to the content array
	 *
	 * @param array $arrArray
	 * @return void
	 */
	public function appendContent($arrArray)
	{
		$this->arrContent[] = $arrArray;
	}


	/**
	 * Generate the csv file and send it to the browser
	 *
	 * @param void
	 * @return void
	 */
	public function saveToBrowser()
	{
		$strContent = $this->prepareContent();

		header('Content-Type: text/csv');
		header('Content-Transfer-Encoding: binary');
		header('Content-Disposition: attachment; filename="' . $this->strFileName . '.csv"');
		header('Content-Length: ' . strlen($strContent));
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Expires: 0');

		echo $strContent;
		exit;
	}


	/**
	 * Generate the csv file and save the content in a file
	 *
	 * @param string $strPath
	 * @return void
	 */
	public function saveToFile($strPath)
	{
		$objFile = new File($strPath);
		$objFile->write($this->prepareContent());
		$objFile->close();

		unset($objFile);
	}


	/**
	 * Prepare the given array and build the content stream
	 *
	 * @param void
	 * @return string
	 */
	public function prepareContent()
	{
		$strCsv = '';
		$arrData = array();

		// add the header fields if there are some
		if (count($this->arrHeaderFields)>0)
		{
			$arrData = array($this->arrHeaderFields);
		}

		// add all other elements
		foreach ($this->arrContent as $k=>$v)
		{
			//TODO: maybe find a better solution
			$arrData[] = $v;
		}


		// build the csv string
		foreach((array) $arrData as $arrRow)
		{
			array_walk($arrRow, array($this, 'escapeRow'));
			$strCsv .= $this->strDelimiter . implode($this->strDelimiter . $this->strSeperator . $this->strDelimiter, $arrRow) . $this->strDelimiter . $this->strLineEnd;
		}

		// add the excel support if requested
		if ($this->blnExcel === true)
		{
			$strCsv = chr(255) . chr(254) . mb_convert_encoding($strCsv, 'UTF-16LE', 'UTF-8');
		}

		return $strCsv;
	}


	/**
	 * Escape a row
	 *
	 * @param mixed &$varValue
	 * @return void
	 */
	public function escapeRow(&$varValue)
	{
		$varValue = str_replace('"', '""', $varValue);
	}
}
