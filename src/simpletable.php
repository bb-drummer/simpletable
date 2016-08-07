<?php
/**
 * simple table generator class
 *
 * @package        SimpleTable
 * @author         Björn Bartels <coding@bjoernbartels.earth>
 * @link           https://gitlab.bjoernbartels.earth/groups/php
 * @license        http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @copyright      copyright (c) 2007 Björn Bartels <coding@bjoernbartels.earth>
 */
namespace SimpleTable;

class SimpleTable {
	
	public $_tpl = NULL;
	
	public $data = array();
	
	public $options = array();
	
	public $tags = array(
		"container" =>	"table",
		"row"		=>	"tr",
		"cell"		=>	"td",
		"headcell"	=>	"th"
	);
	
	/**
	 * class constructor
	 * @param OBJECT|ARRAY $options
	 * @return ProductTable
	 */
	public function __construct ( $options ) {
		$this->tags = (object)$this->tags;
		$this->setOptions($options);
		return $this;
	}
	
	/**
	 * generate table header HTML
	 * @param OBJECT $params
	 */
	public function buildHeaderCells ($aHTMLTableColumns) {
		$sHTMLTableHeader = "";
		$sHTMLTableColumnGroup = "";
		$aColumns = array();
		foreach ((array)$aHTMLTableColumns as $iColumn => $aColumn) {
			if (!empty($aColumn["field"])) {
				$aColumns[] = $aColumn["field"];
				$sHTMLTableHeader .= "<".$this->tags->headcell." class=\"".$aColumn["field"]."\">".$aColumn["title"]."</".$this->tags->headcell.">";
				$sHTMLTableColumnGroup .= "<column class=\"".$aColumn["field"]."\" />";
			} else {
				$sHTMLTableHeader .= "<".$this->tags->headcell." class=\"col_".$iColumn."\">".$aColumn["title"]."</".$this->tags->headcell.">";
				$sHTMLTableColumnGroup .= "<column class=\"col_".$iColumn."\" />";
			}
		}
		$this->_sHeader = $sHTMLTableHeader;
		$this->getTpl()->set('s', 'HEADERCELLS', $sHTMLTableHeader);
		$this->getTpl()->set('s', 'COLUMNGROUP', "<columns>".$sHTMLTableColumnGroup."</columns>");
	}
		
	/**
	 * generate table footer HTML
	 * @param OBJECT $params
	 */
	public function buildFooterCells ($aHTMLTableColumns) {
		$sHTMLTableFooter = "";
		$aColumns = array();
		foreach ((array)$aHTMLTableColumns as $iColumn => $aColumn) {
			if ($aColumn["field"]) {
				$aColumns[] = $aColumn["field"];
				$sHTMLTableFooter .= "<".$this->tags->cell." class=\"".$aColumn["field"]."\">".$aColumn["title"]."</".$this->tags->cell.">";
			} else {
				$sHTMLTableFooter .= "<".$this->tags->cell." class=\"col_".$iColumn."\">".$aColumn["title"]."</".$this->tags->cell.">";
			}
		}
		$this->_sFooter = $sHTMLTableFooter;
		$this->getTpl()->set('s', 'FOOTERCELLS', $sHTMLTableFooter);
	}
	
	/**
	 * generate table body HTML
	 * @param array $aRowData
	 * @param array $aHTMLTableColumns
	 * @return string
	 */
	public function buildBodyCells ($aRowData, $aHTMLTableColumns) {
		$aRows = array();
		$this->_aRows = array();
		$aHTML = array();
		foreach ( (array)$aRowData as $iRow => $oRowData ) {
			$sCells = "";
			foreach ((array)$aHTMLTableColumns as $iColumn => $aColumn) {
				$mCellValue = $oRowData[$aColumn["field"]];
				if (!empty($aColumn["callback"]) && function_exists($aColumn["callback"])) {
					$mCellValue = call_user_func($aColumn["callback"], $oRowData, $aColumn, $iColumn, $iRow);
				}
				if ( isset($aColumn["field"]) && isset($oRowData[$aColumn["field"]]) ) {
					$sClassname = $aColumn["field"];
				} else {
					$sClassname = "col_".$iColumn;
				}
				$sCells .= "<".$this->tags->cell." class=\"".$sClassname."\">".
					$mCellValue.
				"</".$this->tags->cell.">";
			}
			
			$aRows[] = $sCells;
		}
		$this->_aRows = $aRows;
		
		foreach ($aRows as $iRow => $sRow) {
			$this->getTpl()->set('d', 'ROWID', "row_".$aRowData[$iRow]["productID"]);
			$this->getTpl()->set('d', 'BODYCELLS', $sRow);
			if (($iRow % 2) == 0) {
				$this->getTpl()->set('d', 'CSS_CLASS', 'even');
			} else {
				$this->getTpl()->set('d', 'CSS_CLASS', 'odd');
			}
			$this->getTpl()->next();
		
		}
		return $aHTML;
	}
	
	/**
	 * generate mini table mark-up template
	 * @return STRING
	 */
	public function buildMarkupTemplate () {
		$aHTML = array(
			"<".$this->tags->container.">",
				"<".$this->tags->row.">",
					"{HEADERCELLS}",
				"</".$this->tags->row.">",
					"<!-- BEGIN:BLOCK -->",
						"<".$this->tags->row.">",
							"{BODYCELLS}",
						"</".$this->tags->row.">",
					"<!-- END:BLOCK -->",
				"<".$this->tags->row.">",
					"{FOOTERCELLS}",
				"</".$this->tags->row.">",
			"</".$this->tags->container.">"
		);
		$sHTML = implode("", $aHTML);
		return $sHTML;
	}
	
	/**
	 * generate table mark-up
	 * @return STRING
	 */
	public function buildMarkup () {
		$sHTML = "";
		
		$sTableID = $this->getOptions("formID");
		if (!$sTableID) {
			$sTableID = "table" . md5(microtime());
			$this->options["formID"] = $sTableID;
		}
		
		$this->getTpl()->reset();
		$this->getTpl()->set('s', 'TABLEID',			$sTableID );
		$this->buildHeaderCells( $this->getOptions("columns") );
		$this->buildFooterCells( $this->getOptions("footer") );
		$this->buildBodyCells( $this->getData(), $this->getOptions("columns") );
		$sTemplate = $this->getOptions("template");
		if ($sTemplate == "") {
			$sTemplate = $this->buildMarkupTemplate();
		}
		$sHTML = $this->getTpl()->generate( $sTemplate, true );
		return $sHTML;
	}
	
	/**
	 * generate table JSON data
	 * @return STRING
	 */
	public function buildData () {
		$sJSON = "[]";
		if ($this->data) {
			$sJSON = json_encode($this->data);
		}
		return $sJSON;
	}
	
	/**
	 * return template object
	 * @return Template
	 */
	public function getTpl() {
		if ($this->_tpl == NULL) {
			$this->setTpl();
		}
		return $this->_tpl;
	}

	/**
	 * generate template object
	 * @param Template $_tpl
	 * @return ProductTable
	 */
	public function setTpl( $_tpl = NULL ) {
		if ($_tpl == NULL) {
			$this->_tpl = new Template;
		} else {
			$this->_tpl = $_tpl;
		}
		return ($this);
	}
	/**
	 * generate database object
	 * @return DB_Contenido
	 */
	public function getDb() {
		if ($this->_db == NULL) {
			$this->setDb();
		}
		return $this->_db;
	}

	/**
	 * generate database object
	 * @param DB_Contenido $_db
	 * @return ProductTable
	 */
	public function setDb( $_db = NULL ) {
		if ($_db == NULL) {
			$this->_db = new DB_Contenido();
		} else {
			$this->_db = $_db;
		}
		return ($this);
	}
	
	/**
	 * return table data
	 * @return MIXED
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * set new table data
	 * @param MIXED $data
	 * @return ProductTable
	 */
	public function setData( $data = NULL) {
		if ( is_array($data) ) {
			$this->data = $data;
		}
		return $this;
	}

	/**
	 * return option by key or complete option set
	 * @param	STRING $key	
	 * @return	MIXED
	 */
	public function getOptions( $key = "" ) {
		if ( !empty($key) ) { 
			if ( isset($this->options[$key]) ) {
				return $this->options[$key];
			} else {
				return false;
			}
		}
		return $this->options;
	}

	/**
	 * @param OBJECT|ARRAY $options
	 * @return ProductTable
	 */
	public function setOptions($options) {
		if ( is_array($options) ) {
			$this->options = $options;
		} else if ( is_object($options) ) {
			$this->options = (array)$options;
		} else {
			throw new Exception("invalid table options");
		}
		if ( isset($this->options["data"]) ) {
			$this->setData($this->getOptions("data"));
			unset( $this->options->data );
		}
		return $this;
	}



}