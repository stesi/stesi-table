<?php

namespace Stesi\StesiTable;

class StesiColumn {
	private $columnName;
	private $columnDescription;
	private $globalSearcheable;
	private $stesiColumnStyles;
	private $columnType;
	private $options;
	private $properties;
	private $jsFunction;
	private $alias;
	private $text;
	private $dataAttributes=array();
	private $customAttributes=array();
	private $hidden=false;
	private $columnHeader="";
	private $hyperlink;
	/**
	 *
	 * @param string $columnName
	 *        	name of the column
	 * @param string $columnDescription
	 *        	description of the column to use on the header table
	 * @param boolean $globalSearcheable
	 *        	if true, the column is used in global search
	 */
	function __construct($columnName, $columnDescription = null, $globalSearcheable = 1) {
		$this->columnName = $columnName;
		$this->globalSearcheable = $globalSearcheable;
		$this->stesiColumnStyles = array ();
		if ($columnDescription)
			$this->columnDescription = $columnDescription;
		else
			$this->columnDescription = $columnName;
		$this->columnType = StesiColumnType::TextBox;
		$this->alias = null;
		$this->options = array ();
		$this->properties = array ();
		$this->attributes=array();
		$this->columnHeader=$this->columnDescription;
	}
	
	/**
	 * hyperlink
	 * @return unkown
	 */
	public function getHyperlink(){
		return $this->hyperlink;
	}
	
	/**
	 * hyperlink
	 * @param unkown $hyperlink
	 * @return StesiColumn
	 */
	public function setHyperlink($hyperlink){
		$this->hyperlink = $hyperlink;
		return $this;
	}
		
	public function getDataAttributes(){
		return $this->dataAttributes;
	}
	
	public function addDataAttributes($attributeKey,$attributeValue){
		$this->dataAttributes[$attributeKey]=str_replace ( ".", "", $attributeValue);
	}
	
	public function addCustomAttributes($attributeKey,$attributeValue){
		$this->dataAttributes[$attributeKey]=str_replace ( ".", "", $attributeValue);
	}
	
	public function getCustomAttributes(){
		return $this->customAttributes;
	}
	
	/**
	 * columnHeader
	 * @return string
	 */
	public function getColumnHeader(){
		return $this->columnHeader;
	}
	
	/**
	 * columnHeader
	 * @param string $columnHeader
	 * @return StesiColumn
	 */
	public function setColumnHeader($columnHeader){
		$this->columnHeader = $columnHeader;
		return $this;
	}
	
	/**
	 *
	 * @param boolean $dot
	 *        	if true, dot is removed from column name
	 */
// 	public function getColumnName($dot = true) {
// 	if ($dot)
// 	return ! empty ( $this->alias ) ? $this->alias : $this->columnName;
// 	else
// 	return ! empty ( $this->alias ) ? str_replace ( ".", "", $this->alias ) : str_replace ( ".", "", $this->columnName );
// 	}
	public function getColumnName($dot = true) {
		if ($dot)
			return $this->columnName;
		else
			return str_replace ( ".", "", $this->columnName );
	}
	
	/**
	 * Define type of stesiColumn
	 *
	 * @param
	 *        	$columnType
	 */
	public function setColumnType($columnType) {
		if($columnType==StesiColumnType::Button || $columnType==StesiColumnType::Date)
			$this->globalSearcheable=0;
		$this->columnType = $columnType;
		return $this;
	}
	/**
	 *
	 * @return StesiColumnType $columnType
	 */
	public function getColumnType() {
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
	 * alias
	 * 
	 * @return string
	 */
	public function getAlias() {
		return $this->alias;
	}
	
	/**
	 * alias
	 * 
	 * @param string $alias        	
	 * @return StesiColumn
	 */
	public function setAlias($alias) {
		$this->alias = $alias;
		return $this;
	}
	
	public function getColumnData($dot=false){
		
		if(!empty($this->alias))
			return $this->alias;
		else {
			if(!$dot){
				return str_replace ( ".", "", $this->columnName );
			}else 
				return $this->columnName;
		}
	}
	/**
	 * Add an array of option used in PFBC column
	 *
	 * @param unknown $options        	
	 */
	public function addOptions($options) {
		$this->options = $options;
	}
	public function isGlobalSerchable() {
		return $this->globalSearcheable;
	}
	public function getColumnStyles() {
		return $this->stesiColumnStyles;
	}
	/**
	 *
	 * @param string $conditionOperator if it is set to null, the style is apply on each column
	 * @param string $value        	
	 * @return StesiColumnStyle $stesiColumnStyle
	 */
	public function addColumnStyle($conditionOperator=null, $value=null,$otherColumnId=null) {
		$stesiColumnStyle = new StesiColumnStyle ( $conditionOperator, $value,$otherColumnId );
		$this->stesiColumnStyles [] = $stesiColumnStyle;
		return $stesiColumnStyle;
	}
	
	/**
	 * Take in input a list of options to use in a select\multiselect column type
	 *
	 * @param array $options        	
	 */
	public function setOptions(array $options) {
		$this->options = $options;
		return $this;
	}
	/**
	 *
	 * @return array $options
	 */
	public function getOptions() {
		return $this->options;
	}
	
	/**
	 * Take in input a list of properties to configure column
	 *
	 * @param array $properties        	
	 */
	public function setProperties(array $properties) {
		$this->properties = $properties;
		return $this;
	}
	/**
	 *
	 * @return array $properties
	 */
	public function getProperties() {
		return $this->properties;
	}
	/**
	 * If column is define as a button, you have to define the jsFunction to call after click
	 *
	 * @param string $jsFunction        	
	 *
	 * @return StesiColumn
	 */
	public function setJsButtonCallback($jsFunction) {
		$this->jsFunction = $jsFunction;
	}
	public function getJsButtonCallback() {
		return $this->jsFunction;
	}
	
	/**
	 * columnFilterName
	 * @return string as name of filter
	 */
	public function getColumnFilterName(){
		return array_flip ( (new \ReflectionClass ( "Stesi\StesiTable\StesiColumnType" ))->getConstants () ) [$this->getColumnType ()]."_".(empty($this->alias)?$this->columnName:$this->alias);
	}
	

	/**
	 * hidden
	 * @return boolean
	 */
	public function isHidden(){
		return $this->hidden;
	}
	
	/**
	 * hidden
	 * @param boolean $hidden if true, the column doesn't shown into datatable
	 * @return StesiColumn
	 */
	public function setHidden($hidden){
		$this->hidden = $hidden;
		return $this;
	}
	
}
/**
 *
 * Define the type of column
 *
 * @author Vincenzo
 *        
 */
class StesiColumnType {
	const TextBox = 0;
	const Select = 1;
	const Number = 3;
	const Date = 4;
	const Button = 7;
}
/**
 * Define style of the column of the DataTable
 */
class StesiColumnStyle {
	private $operator;
	private $value;
	private $classes;
	private $css;
	private $html;
	private $pClass;
	private $otherColumnId;
	private $visibility=true;
	private $icon="";
	
	/**
	 *
	 * @param string $conditionOperator
	 *        	operator used to apply the condition Example '=','>','<'
	 * @param string $value
	 *        	value of the expression example 1000
	 * @param string $otherColumnId id of column used to apply condition
	 */
	function __construct($conditionOperator=null, $value=null,$otherColumnId=null) {
		$this->operator = ($conditionOperator == "=" ? "==" : $conditionOperator);
		$this->value = $value;
		$this->css = array ();
		$this->html = array ();
		$this->classes = array ();		
		$this->otherColumnId=!empty($otherColumnId)?str_replace ( ".", "", $otherColumnId ):null;
	}
	
	function getIcon(){
		return $this->icon;
	}
	
	function setIcon($icon){
		$this->icon=$icon;
	}
	
	/**
	 * Add css style to the column
	 *
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
	 * 
	 * @param boolean $visibility if false, set hidden prop to data column where data satisfy the condition
	 */
	function setVisibility($visibility){
		$this->visibility=$visibility;
		return $this;
	}
	function getVisibility(){
		return $this->visibility;
	}
	
	/**
	 * Add html to the column
	 *
	 * @param string $propertyName
	 * @param string $value
	 * @return \Stesi\StesiTable\StesiColumnStyle
	 */
	function addHtml($value) {
		$this->html [] = $value;
		return $this;
	}
	function getHtml() {
		return $this->html;
	}
	
	/**
	 * Add class paragraph to the column
	 * @param $value class into paragraph (ex. label-danger)
	 * @return \Stesi\StesiTable\StesiColumnStyle
	 */
	function setPClass($value) {
		$this->pClass=$value;
		return $this;
	}
	function getPClass() {
		return $this->pClass;
	}
	
	/**
	 * Add class to the column
	 *
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

    /**
     * otherColumnId
     * @return string
     */
    public function getOtherColumnId(){
        return $this->otherColumnId;
    }

    /**
     * otherColumnId
     * @param string $otherColumnId
     * @return StesiColumn
     */
    public function setOtherColumnId($otherColumnId){
        $this->otherColumnId = $otherColumnId;
        return $this;
    }

  

}