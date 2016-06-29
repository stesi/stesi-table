<?php
namespace Stesi\StesiTable;

class StesiColumn {
	private $columnName;
	private $columnDescription;
	private $globalSearcheable;
	private $stesiColumnStyles;
	private $columnType;
	private $stesiColumnValue;
	private $options;
	private $properties;


	/**
	 *
	 * @param string $columnName name of the column
	 * @param string $columnDescription description of the column to use on the header table
	 * @param boolean $globalSearcheable if true, the column is used in global search
	 */
	function __construct($columnName, $columnDescription = null, $globalSearcheable = 0) {
		$this->columnName = $columnName;
		$this->globalSearcheable = $globalSearcheable;
		$this->stesiColumnStyles = array ();
		if ($columnDescription)
			$this->columnDescription = $columnDescription;
			else
				$this->columnDescription = $columnName;
				$this->columnType=StesiColumnType::TextBox;
				$this->options=array();
				$this->properties=array();
	}
	/**
	 *
	 * @param boolean $dot if true, dot is removed from column name
	 */
	public function getColumnName($dot = true) {
		if ($dot)
			return $this->columnName;
			else
				return str_replace ( ".", "", $this->columnName );
	}
	/**
	 * Define type of stesiColumn
	 * @param $columnType
	 */
	public function setColumnType($columnType){
		$this->columnType=$columnType;
		return $this;
	}
	/**
	 * @return StesiColumnType $columnType
	 */
	public function getColumnType(){
		return $this->columnType;
	}

	public function getColumnDescription() {
		return $this->columnDescription;
	}
	/**
	 *
	 * @param string $columnDescription
	 */
	public function setColumnDescription($columnDescription) {
		$this->columnDescription = $columnDescription;
		return $this;
	}

	/**
	 * Add an array of option used in PFBC column
	 * @param unknown $options
	 */
	public function addOptions($options){
		$this->options=$options;
	}

	public function isGlobalSerchable() {
		return $this->globalSearcheable;
	}
	public function getColumnStyles() {
		return $this->stesiColumnStyles;
	}
	/**
	 *
	 * @param string $conditionOperator
	 * @param string $value
	 * @return StesiColumnStyle $stesiColumnStyle
	 */
	public function addColumnStyle($conditionOperator, $value) {
		$stesiColumnStyle = new StesiColumnStyle ( $conditionOperator, $value );
		$this->stesiColumnStyles [] = $stesiColumnStyle;
		return $stesiColumnStyle;
	}

	/**
	 * stesiColumnValue return array of default value
	 * @return array
	 */
	public function getStesiColumnValue(){
		return $this->stesiColumnValue;
	}

	/**
	 * Take in input a list of options to use in a select\multiselect column type
	 * @param array $options
	 */
	public function setOptions(array $options){
		$this->options=$options;
		return $this;
	}
	/**
	 * @return array $options
	 */
	public function getOptions(){
		return $this->options;
	}

	/**
	 * Take in input a list of properties to configure column
	 * @param array $properties
	 */
	public function setProperties(array $properties){
		$this->properties=$properties;
		return $this;
	}
	/**
	 * @return array $properties
	 */
	public function getProperties(){
		return $this->properties;
	}

}
/**
 *
 * Define the type of column
 * @author Vincenzo
 *
 */
class StesiColumnType
{
	const TextBox  = 0;
	const Select = 1;
	const Number=3;
	const Date=4;
	const DateTime=5;
	const TextArea=6;
}
/**
 * Define style of the column of the DataTable
 *  */
class StesiColumnStyle {
	private $operator;
	private $value;
	private $classes;
	private $css;

	/**
	 *
	 * @param string $conditionOperator operator used to apply the condition Example '=','>','<'
	 * @param string $value value of the expression example 1000
	 */
	function __construct($conditionOperator, $value) {
		$this->operator = ($conditionOperator == "=" ? "==" : $conditionOperator);
		$this->value = $value;
		$this->css = array ();
		$this->classes = array ();
	}

	/**
	 * Add css style to the column
	 * @param string $propertyName
	 * @param string $value
	 * @return \Stesi\StesiTable\StesiColumnStyle
	 */
	function addCss($propertyName, $value) {
		$this->css [] = array (
				"propertyName" => $propertyName,
				"value" => $value
		);
		return $this;
	}
	/**
	 * Add class to the column
	 * @param string $className
	 * @return \Stesi\StesiTable\StesiColumnStyle
	 */
	function addClass($className) {
		$this->classes [] = $className;
		return $this;
	}
	function getClasses() {
		return $this->classes;
	}
	function getCss() {
		return $this->css;
	}
	function getConditionOperator() {
		return $this->operator;
	}
	function getValue() {
		return $this->value;
	}
}