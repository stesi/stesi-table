<?php

namespace Stesi\StesiTable;


use PFBC\Form;
use PFBC;


/**
 * Class that manage Datatable
 *
 * @package    Stesi.StesiTable
 */

class StesiTable {
	/**
     * The value for the id table field.
     * 
     * @var int
     */
	private $id;
	/**
	 * Columns of datatable.
	 * @var array
	 */
	private $columns = array ();
	
	/**
	 * name of the Callback that table have to call after reorder column
	 * @var string
	 */
	private $columnReorderCallBack;
	/**
	 * attribute to determine if this object has reorder property
	 * @var array
	 */
	private $isColReorderable;
	
	/**
	 * array of table columns id separated by comma
	 * @var integer
	 */	
	private $columnOrder;
	
	private $form;
	/**
	 * 
	 * @param int $id
	 * @param boolean $useForm if true, use PFBC Form
	 */
	function __construct($id,$useForm=false) {
		$this->id = $id;
		$this->isColReorderable = false;
		if($useForm){
			$this->form=new Form($this->id."_form");
		}
	}
	
	/**
	 * Returns id of table.
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}
	
	/**
	 * Add a new column to the table. 
	 * @param  string $columnName name of the column
	 * @param  string $columnDescription description of column: if it is null, assume the same value of column name
	 * @param  boolean $globalSearchable if true, use the column into global search
	 * @return StesiColumn $column
	 */
	public function addColumn(StesiColumn $stesiColumn){
		$this->columns [$stesiColumn->getColumnName()]=$stesiColumn;
		if($this->form){
			//Se è settato a true, creo un elemento della form instanziando dinamicamente un elemento PFBC e aggiungendolo alla form
			$class=new \ReflectionClass(
					"PFBC\Element\\".(array_flip((new \ReflectionClass("Stesi\StesiTable\StesiColumnType"))->getConstants())[$stesiColumn->getColumnType()]));
			if(!$class)
				throw new \Exception("PFBC class ".$class." not found");
			$instance = $class->newInstanceArgs(array($stesiColumn->getColumnDescription(), $stesiColumn->getColumnName(), $stesiColumn->getOptions(),$stesiColumn->getProperties()));
			$this->form->addElement($instance);			
		}
		return $this->columns [$stesiColumn->getColumnName()];
	}
	
	/**
	 * Returns whether the object is Reaorderable
	 *
	 * @return boolean True if the object is reorderable.
	 */
	public function isColumnReorderable() {
		return $this->isColReorderable;
	}
	    
	 /**
     * Setter for the columnReorderable attribute. 
     *
     * @param boolean $isColReorderable the value of the property.
     */
	
	public function setIsColumnReorderable($isColReorderable) {
		$this->isColReorderable = $isColReorderable;
		return $this;
	}
	
	/**
	 * Setter for the columnReorderableCallback attribute.
	 *
	 * @param string $columnReorderCallBack the value of the property.
	 */
	
	public function setColumnReorderCallback($columnReorderCallBack) {
		$this->columnReorderCallBack = $columnReorderCallBack;
	}
	
	/**
	 * Setter for the columnOrder array.
	 *
	 * @param array $columnOrder the value of the property.
	 */
	
	public function setColumnOrder($columnOrder) {
		$this->columnOrder = $columnOrder;
	}
	/**
	 * Getter for the columnOrder array.
	 * 
	 * @return array $columnOrder
	 */
	public function getColumnOrder() {
		return $this->columnOrder;
	}
	
	/**
	 * Getter to obtain data of table encoded in json
	 * @param  $draw
	 * @param  $recordsTotal
	 * @param  $recordsFiltered
	 * @param  $data
	 */
	public function getTableData($draw, $recordsTotal, $recordsFiltered, $data) {
		return json_encode ( [ 
				"draw" => $draw,
				"recordsTotal" => $recordsTotal,
				"recordsFiltered" => $recordsFiltered,
				"data" => $data 
		] );
	}
	public function getColumns() {
		return $this->columns;
	}
	/**
	 * 
	 * @param boolean $onlyGlobalSerchable
	 */
	public function getTableColumnsNames($onlyGlobalSerchable = 0) {
		$tableColumnsNames = array ();
		
		foreach ( $this->columns as $column ) {
			if (! $onlyGlobalSerchable)
				$tableColumnsNames [] = $column->getColumnName ();
			else {
				if ($column->isGlobalSerchable ())
					$tableColumnsNames [] = $column->getColumnName ();
			}
		}
		return $tableColumnsNames;
	}
	
	/**
	 *
	 * @param boolean $onlyGlobalSerchable
	 * @return array $tableColumnsDescription
	 */
	public function getTableColumnsDescription($onlyGlobalSerchable = 0) {
		$tableColumnsDescription = array ();
	
		foreach ( $this->columns as $column ) {
			if (! $onlyGlobalSerchable)
				$tableColumnsDescription [] = $column->getColumnDescription ();
				else {
					if ($column->isGlobalSerchable ())
						$tableColumnsDescription [] = $column->getColumnDescription ();
				}
		}
		return $tableColumnsDescription;
	}
	
	/**
	 * 
	 * @param string $ajaxCallBack name of function to call to render Datatable
	 * @return string html of DataTable with the javascript that manage the table 
	 */
	public function getTable($ajaxCallBack) {
		if($this->form){
			$this->form->setAttribute("action", $ajaxCallBack);
			$this->form->configure(array(
					"prevent" => array("bootstrap", "jQuery","jqueryui")
			));
		}
		$table = "<table id=\"" . $this->id . "\" cellspacing=\"0\" width=\"100%\">";
	
		$tableColums = $this->getColumns ();
		$th="<tr>";
		foreach ( $tableColums as $column ) {
			$th .= "<th  data-type='".(
					array_flip((new \ReflectionClass("Stesi\StesiTable\StesiColumnType"))->getConstants())[$column->getColumnType()]
					)."'>" . $column->getColumnDescription () . "</th>";
		
		}
		$th.="</tr>";
		
		$table .= "<thead>";
		$table .= $th."</thead>";
		$table .= "<tfoot>";
		$table .= $th."</tfoot></table>";
		$table .= "<script>
				";
		$table .= '
				
				var datatable=$("#' . $this->id . '").DataTable({
	       processing: true,
			keys: true,
			ordering:true,
			bSortable:true,
			stateSave: true,
			stateDuration: 24*60*60,
			scrollY:        800,
			scrollCollapse: true,
	        scrollX:        true,
	        fixedHeader:   true,
			serverSide : true,
        	"language": {
        	    "search": "Ricerca Globale",
        	    "lengthMenu": "Elem.per pagina _MENU_ ",
        	    "info": "_PAGES_ Pagine / Totale elementi: _TOTAL_",
        	    "processing": "Caricamento dati in corso..."
        	  },
        	order: [[ 0, "desc" ]],
        "ajax": {
    "url": "' . $ajaxCallBack . '",
    type:"POST",
    data: function ( d ) {
    		var mio_array = new Array();
			mio_array[0] = new Array();
			mio_array[1] = new Array();
    		mio_array[0][0] = "Primo array, prima voce.";
			mio_array[0][1] = "Primo array, seconda voce.";
			mio_array[0][2] = "Primo array, terza voce.";
			mio_array[1][0] = "Secondo array, prima voce.";	
mio_array[1][1] = "Secondo array, seconda voce.";
mio_array[1][2] = "Secondo array, terza voce.";
    		
    		
		d.filter= mio_array;
    		
	}
  }';
		//$("#'.$this->id.'_form").serialize();
		$table .= ',columns:[';
		/*
		 * Create column dinamically with custom class that has the same name of column, data with columnName without point (ex ArticoliNatura), name with columnName with point (ex Articoli.Natura)
		 * */
		foreach ( $tableColums as $column ) {
			$table .= '{ "class": "' . $column->getColumnName ( false ) . '","data": "' . $column->getColumnName ( false ) . '","name":"'.$column->getColumnName(true).'" },';
		}
		
		/*
		 * At InitComplete: 
		 * bind enter call to global search and column search on footer columns
		 * */
		$table .= '
        ],
				initComplete: function() {
						var api = this.api();				  
				  $("#' . $this->id . '_filter input").unbind();
			        $("#' . $this->id . '_filter input").bind("keyup", function(e) {
			          if(e.keyCode == 13 || (e.keyCode==8 && this.value=="")) {
			        	  api.search( this.value ).draw();
			            }
			          
			        });
			        		
				   //initFooterEvent();
				   api.columns().every( function () {
					var column=this;
	               column.data().unique().sort().each( function ( d, j ) {
	             			if(d){
							if(column.search() == d){
						        $( "select", column.footer() ).append( "<option value=\""+d+"\" selected=\"selected\">"+d+"</option>" )
						    }else {
		                        $( "select", column.footer() ).append( "<option value=\""+d+"\">"+d+"</option>" )
						    }
	           				}
		                } );
				   });
			},			        		
			createdRow: function(row,data,index){';
		foreach ( $tableColums as $column ) {
			/*
			 * Apply column styles dinamically to each column
			 * */
			$columnStyles = $column->getColumnStyles ();
			foreach ( $columnStyles as $columnStyle ) {
				/*
				 * Using column Operator and Value to determine if the style is applicable
				 * Ex data['natura']='MDR'
				 * EX data['id_articolo']>'10000'
				 * */
				$table .= "if(data['" . $column->getColumnName ( false ) . "']" . $columnStyle->getConditionOperator () . "'" . $columnStyle->getValue () . "'){";
				$table .= '
							$("td.' . $column->getColumnName ( false ) . '", row)';
				if (count ( $columnStyle->getClasses () ) > 0) {
					$table .= '.addClass("' . implode ( " ", $columnStyle->getClasses () ) . '")';
				}
				if (count ( $columnStyle->getCss () ) > 0) {
					foreach ( $columnStyle->getCss () as $css ) {
						
						$table .= '.css("' . $css ["propertyName"] . '","' . $css ['value'] . '")';
					}
				}
				$table .= ";
					}";
			}
		}
		
		$table .= '}
	});
	
			 // Restore state
        var state = datatable.state.loaded();
        if (state) {
            datatable.columns().eq(0).each(function (colIdx) {
                var colSearch = state.columns[colIdx].search;
 
                if (colSearch.search) {
                    $("input", datatable.column(colIdx).footer()).val(colSearch.search);
                }
            });
 
            datatable.draw();
        }	
				';
		

// 		$("#' . $this->id . '_form input").unbind();
// 		$("#' .$this->id . '_form input").bind("keyup", function(e) {
// 			if(e.keyCode == 13 || (e.keyCode==8 && this.value=="")) {
		
// 				var t=datatable;
// 				$("#' .$this->id . '_form input").each(function(index){
// 					if(this.value!=""){
// 						t=t.column(($(this)).attr("name")+":name");
// 						t=t.search(this.value);
// 					}
// 				});
// 					console.log(t.draw());
// 			}
// 		});

		
		
		/*
		 * Column Reorderable function:
		 * Reorder columns if specified and create an ajax call to the columnReorderCallBack if is specified 
		 * */
		if ($this->isColReorderable) {
			$table .= "
			new $.fn.dataTable.ColReorder(datatable,{
				realtime: false";
			if (! empty ( $this->columnOrder )) {
				$table .= ",order: [" . implode ( ",", $this->columnOrder ) . "]";
			}
			if (! empty ( $this->columnReorderCallBack )) {
				$table .= ",reorderCallback:function(){
	            		$.ajax({
									type : 'POST',
									url : '" . $this->columnReorderCallBack . "',
									dataType : 'JSON',
									data : {
										colReorderOrder : datatable.colReorder.order(),
	             						dataTableId: '" . $this->id . "'
									}
								});
	             				initFooterEvent();
	             	}";
			}
			$table .= "
			});";
		}
		$table .= "		
				var initFooterEvent=function(){
				
				   // Apply the search
	    			datatable.columns().every( function () {
		        		var that = this;
	             		var column=this;
		 				$( 'input', this.footer() ).unbind();
		        		$( 'input', this.footer() ).on( 'keyup change', function (e) {

		              		if(e.keyCode == 13 || (e.keyCode==8 && this.value=='')) {
		                		that.search( this.value )
		                    	.draw();
		            			}
		             	});
	             		$( 'select', this.footer() ).unbind();
	             	   $( 'select', this.footer() ).on( 'change', function () {
                        var val = $.fn.dataTable.util.escapeRegex(
                            $(this).val()
                        );
 
                        column
                            .search( val ? val : '', true, false )
                            .draw();
                    	} );
 
		              
						});
				}
				</script>";
		return $table;
	}
	
	public function renderForm(){
		if($this->form){
			return $this->form->render(true);
		}
		return "";
	}
}
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