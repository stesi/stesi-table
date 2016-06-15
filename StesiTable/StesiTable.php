<?php
namespace Stesi\StesiTable

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
	        "processing": true,
        "serverSide": true,
        "ajax": "mdr_test",
		"columns": [';
		foreach ( $tableColums as $column ) {
			$table .= '{ "data": "' . $column->getColumnName ( false ) . '" },';
		}
		$table .= '
        ]
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

