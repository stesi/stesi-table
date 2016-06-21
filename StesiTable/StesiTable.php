<?php

namespace Stesi\StesiTable;

class StesiTable {
	private $id;
	private $columns = array ();
	private $columnReorderCallBack;
	private $isColReorderable;
	private $columnOrder;
	function __construct($id) {
		$this->id = $id;
		$this->isColReorderable = false;
	}
	public function getId() {
		return $this->id;
	}
	public function addColumn($columnName, $columnDescription = null, $globalSerchable = 0) {
		$this->columns [$columnName] = new StesiColumn ( $columnName, $columnDescription, $globalSerchable );
		return $this->columns [$columnName];
	}
	public function isColumnReorderable() {
		return $this->isColReorderable;
	}
	public function setIsColumnReorderable($isColReorderable) {
		$this->isColReorderable = $isColReorderable;
		return $this;
	}
	public function setColumnReorderCallback($columnReorderCallBack) {
		$this->columnReorderCallBack = $columnReorderCallBack;
	}
	public function setColumnOrder($columnOrder) {
		$this->columnOrder = $columnOrder;
	}
	public function getColumnOrder() {
		return $this->columnOrder;
	}
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
		foreach ( $tableColums as $column ) {
			$table .= '{ "class": "' . $column->getColumnName ( false ) . '","data": "' . $column->getColumnName ( false ) . '","name":"'.$column->getColumnName(true).'" },';
		}
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
			    // Apply the search
    			this.api().columns().every( function () {
        		var that = this;
 				$( "input", this.footer() ).unbind();
        		$( "input", this.footer() ).on( "keyup change", function (e) {
              		if(e.keyCode == 13) {
                		that.search( this.value )
                    	.draw();
            	}
        } );
    } );
			},			        		
			createdRow: function(row,data,index){';
		foreach ( $tableColums as $column ) {
			$columnStyles = $column->getColumnStyles ();
			foreach ( $columnStyles as $columnStyle ) {
				
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
	});';
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
class StesiColumn {
	private $columnName;
	private $columnDescription;
	private $globalSearcheable;
	private $stesiColumnStyles;
	function __construct($columnName, $columnDescription = null, $globalSearcheable = 0) {
		$this->columnName = $columnName;
		$this->globalSearcheable = $globalSearcheable;
		$this->stesiColumnStyles = array ();
		if ($columnDescription)
			$this->columnDescription = $columnDescription;
		else
			$this->columnDescription = $columnName;
	}
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
class StesiColumnStyle {
	private $operator;
	private $value;
	private $classes;
	private $css;
	function __construct($conditionOperator, $value) {
		$this->operator = ($conditionOperator == "=" ? "==" : $conditionOperator);
		$this->value = $value;
		$this->css = array ();
		$this->classes = array ();
	}
	function addCss($propertyName, $value) {
		$this->css [] = array (
				"propertyName" => $propertyName,
				"value" => $value 
		);
		return $this;
	}
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

