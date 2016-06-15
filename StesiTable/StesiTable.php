<?php
namespace Stesi\StesiTable;

class StesiTable {
	private $id;
	private $columns = array ();
	function __construct($id) {
		$this->id = $id;
	}
	public function addColumn($columnName, $columnDescription = null, $globalSerchable = 0) {
		$this->columns [$columnName] = new StesiColumn ( $columnName, $columnDescription ,$globalSerchable);
		return $this->columns [$columnName];
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
			if(!$onlyGlobalSerchable) $tableColumnsNames [] = $column->getColumnName ();
			else
			{
				if ($column->isGlobalSerchable()) $tableColumnsNames [] = $column->getColumnName ();
			}
		}
		return $tableColumnsNames;
	}
	public function getTable() {
		$table = "<table id=\"" . $this->id . "\" class=\"display\" cellspacing=\"0\" width=\"100%\">";
		$table .= "<thead><tr>";
		$tableColums = $this->getColumns ();

		foreach ( $tableColums as $column ) {
			$table .= "<th>" . $column->getColumnDescription () . "</th>";
		}
		$table .= "</thead></table>";
		$table .= "<script>";
		$table .= '$("#' . $this->id . '").DataTable({
	       processing: true,
			keys: true,
			ordering:true,
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
        "ajax": "mdr_test",
				
		"columns": [';
		foreach ( $tableColums as $column ) {
			$table .= '{ "data": "' . $column->getColumnName ( false ) . '" },';
		}
		$table .= '
        ],
				initComplete: function() {
				   var api = this.api();
			       $("#prova_filter input").unbind();
			        $("#prova_filter input").bind("keyup", function(e) {
			          if(e.keyCode == 13) {
			        	  $("#collapsed_div2").css("display", "none");
						  $("#collapse_filtri2").children("i").removeClass("fa-minus");
						  $("#collapse_filtri2").children("i").removeClass("fa-plus");
						  $("#collapse_filtri2").children("i").addClass("fa-plus");
			        	  api.search( this.value ).draw();
			            }
			          
			        });
			}
	});';
		$table .= "</script>";
		return $table;
	}
}
class StesiColumn {
	private $columnName;
	private $columnDescription;
	private $globalSearcheable;

	function __construct($columnName, $columnDescription = null, $globalSearcheable = 0) {
		$this->columnName = $columnName;
		$this ->globalSearcheable = $globalSearcheable;
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

	public function isGlobalSerchable()
	{
		return $this ->globalSearcheable;
	}
}

