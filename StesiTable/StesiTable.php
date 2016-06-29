<?php

namespace Stesi\StesiTable;


use PFBC\Form;
use PFBC;
use StesiColumn;

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
			//Se � settato a true, creo un elemento della form instanziando dinamicamente un elemento PFBC e aggiungendolo alla form
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
			$th .= "<th>" . $column->getColumnDescription () . "</th>";
		}
		$th.="</tr>";
		
		$table .= "<thead>";
		$table .= $th."</thead></table>";
		$table .= $this->createJsScript($ajaxCallBack);
		return $table;
	}
	
	private function createJsScript($ajaxCallBack){
		$tableColums = $this->getColumns ();
		
		$table="<script>";
		$table .= '
				$.fn.serializeForm = function() {
    if ( this.length < 1) { 
      return false; 
    }

    var data = {};
    var lookup = data; 
    var selector = ":input[type!=\"checkbox\"][type!=\"radio\"], input:checked";
    var parse = function() {

      // Ignore disabled elements
      if (this.disabled) {
        return;
      }

      // data[a][b] becomes [ data, a, b ]
      var named = this.name.replace(/\[([^\]]+)?\]/g, ",$1").split(",");
      var cap = named.length - 1;
      var $el = $( this );

      // Ensure that only elements with valid `name` properties will be serialized
      if ( named[ 0 ] ) {
        for ( var i = 0; i < cap; i++ ) {
          // move down the tree - create objects or array if necessary
          lookup = lookup[ named[i] ] = lookup[ named[i] ] ||
            ( (named[ i + 1 ] === "" || named[ i + 1 ] === "0") ? [] : {} );
        }

        // at the end, push or assign the value
        if ( lookup.length !==  undefined ) {
          lookup.push( $el.val() );
        }else {
          lookup[ named[ cap ] ]  = $el.val();
        }

        // assign the reference back to root
        lookup = data;
      }
    };

    // first, check for elements passed into this function
    this.filter( selector ).each( parse );

    // then parse possible child elements
    this.find( selector ).each( parse );

    // return data
    return data;
  };
			
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
      	d.filter=$("#'.$this->id.'_form").serializeForm();
    		
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
	             	}";
			}
			$table .= "
			});";
		}
		$table .= "
					 // Restore state
        var state = datatable.state.loaded();
        if (state) {
            datatable.columns().eq(0).each(function (colIdx) {
                var colSearch = state.columns[colIdx].search;
 
                if (colSearch.search) {
                    $('input', datatable.column(colIdx).footer()).val(colSearch.search);
                }
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