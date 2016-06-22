<?php

namespace Stesi\StesiTable;

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
	
	
	function __construct($id) {
		$this->id = $id;
		$this->isColReorderable = false;
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
	 * @return int
	 */
	public function addColumn($columnName, $columnDescription = null, $globalSerchable = 0) {
		$this->columns [$columnName] = new StesiColumn ( $columnName, $columnDescription, $globalSerchable );
		return $this->columns [$columnName];
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
	 * @param string $ajaxCallBack name of function to call to render Datatable
	 * @return string html of DataTable with the javascript that manage the table 
	 */
	public function getTable($ajaxCallBack) {
		$table = "<table id=\"" . $this->id . "\" cellspacing=\"0\" width=\"100%\">";
		$table .= "<thead><tr>";
		$tableColums = $this->getColumns ();
		
		foreach ( $tableColums as $column ) {
			$table .= "<th>" . $column->getColumnDescription () . "</th>";
		}
		$table .= "</thead>";
		$table .= "<tfoot><tr>";
		$tableColums = $this->getColumns ();
		/*
		 * Foreach column create header and footer cell using columnDescription
		 * */
		foreach ( $tableColums as $column ) {
			$table .= "<th>" . $column->getColumnDescription () . "</th>";
		}
		$table .= "</tfoot></table>";
		$table .= "<script>
				 // Setup - add a text input to each footer cell
    $('#" . $this->id . " tfoot th').each( function () {
        var title = $(this).text();
        $(this).html( '<input type=\"text\" placeholder=\"Search '+title+'\" />' );
    } );";
		$table .= 'var datatable=$("#' . $this->id . '").DataTable({
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
        "ajax": "' . $ajaxCallBack . '"';
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
			          if(e.keyCode == 13) {
			        	  $("#collapsed_div2").css("display", "none");
						  $("#collapse_filtri2").children("i").removeClass("fa-minus");
						  $("#collapse_filtri2").children("i").removeClass("fa-plus");
						  $("#collapse_filtri2").children("i").addClass("fa-plus");
			        	  api.search( this.value ).draw();
			            }
			          
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
	             	  // Apply the search
	    			datatable.columns().every( function () {
		        		var that = this;
		 				$( 'input', this.footer() ).unbind();
		        		$( 'input', this.footer() ).on( 'keyup change', function (e) {

		              		if(e.keyCode == 13 || (e.keyCode==8 && this.value=='')) {
		                		that.search( this.value )
		                    	.draw();
		            			}
		             	});
						});
	             	}";
			}
			$table .= "
			});";
		}
		$table .= "		
				$( document ).ready(function() {
  
});
				 
				
				</script>";
		return $table;
	}
}
/*
 * Stesi Column used to define a column of the DataTable.
 * */
class StesiColumn {
	private $columnName;
	private $columnDescription;
	private $globalSearcheable;
	private $stesiColumnStyles;
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
	public function getColumnDescription() {
		return $this->columnDescription;
	}
	public function setColumnDescription($columnDescription) {
		$this->columnDescription = $columnDescription;
		return $this;
	}
	public function isGlobalSerchable() {
		return $this->globalSearcheable;
	}
	public function getColumnStyles() {
		return $this->stesiColumnStyles;
	}
	public function addColumnStyle($conditionOperator, $value) {
		$stesiColumnStyle = new StesiColumnStyle ( $conditionOperator, $value );
		$this->stesiColumnStyles [] = $stesiColumnStyle;
		return $stesiColumnStyle;
	}
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

