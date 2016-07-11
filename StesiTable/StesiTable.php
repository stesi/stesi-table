<?php

namespace Stesi\StesiTable;

use PFBC\Form;
use PFBC;
use PFBC\Element\Button;

/**
 * Class that manage Datatable
 *
 * @package Stesi.StesiTable
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
	 * 
	 * @var array
	 */
	private $columns = array ();
	
	/**
	 * name of the Callback that table have to call after reorder column
	 * 
	 * @var string
	 */
	private $columnReorderCallBack;
	/**
	 * attribute to determine if this object has reorder property
	 * 
	 * @var array
	 */
	private $isColReorderable;
	
	
	/**
	 * array of table columns id separated by comma
	 * 
	 * @var integer
	 */
	private $columnOrder;
	private $form;
	private $datatableButtons;
	private $stesiTableButtons;
	private $filterFormName;
	private $globalFilter="";
	/**
	 *
	 * @param int $id        	
	 * @param boolean $useForm
	 *        	if true, use PFBC Form
	 */
	function __construct($id, $useForm = false) {
		$this->id = $id;
		$this->isColReorderable = false;
		$this->customButtons = array ();
		$this->datatableButtons = array ();
		if ($useForm) {
			$this->form = new Form ( $this->id . "_form" );
			$this->filterFormName="filter";
		}		
	}
	
	/**
	 * 
	 * @param string $filterFormName
	 * return @StesiTable
	 */
	public function setFilterFormName($filterFormName){
		$this->filterFormName=$filterFormName;
	}
	public function getFilterFormName(){
		return $this->filterFormName;
	}
	
	/**
	 * Returns id of table.
	 * 
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}
	
	/**
	 * Add a new column to the table.
	 * 
	 * @param string $columnName
	 *        	name of the column
	 * @param string $columnDescription
	 *        	description of column: if it is null, assume the same value of column name
	 * @param boolean $globalSearchable
	 *        	if true, use the column into global search
	 * @return StesiColumn $column
	 */
	public function addColumn(StesiColumn $stesiColumn) {
		$this->columns [$stesiColumn->getColumnName ()] = $stesiColumn;
		if ($this->form) {
			if ($stesiColumn->getColumnType () != StesiColumnType::Button) {
				// Se è settato a true, creo un elemento della form instanziando dinamicamente un elemento PFBC e aggiungendolo alla form
				$class = new \ReflectionClass ( "PFBC\Element\\" . (array_flip ( (new \ReflectionClass ( "Stesi\StesiTable\StesiColumnType" ))->getConstants () ) [$stesiColumn->getColumnType ()]) );
				if (! $class)
					throw new \Exception ( "PFBC class " . $class . " not found" );
				$instance = $class->newInstanceArgs ( array (
						$stesiColumn->getColumnDescription (),
						$stesiColumn->getColumnData(true),
						$stesiColumn->getOptions (),
						$stesiColumn->getProperties () 
				) );
				$this->form->addElement ( $instance );
			}
		}
		return $this->columns [$stesiColumn->getColumnName ()];
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
	 * @param boolean $isColReorderable
	 *        	the value of the property.
	 */
	public function setIsColumnReorderable($isColReorderable) {
		$this->isColReorderable = $isColReorderable;
		return $this;
	}
	
	/**
	 * Setter for the columnReorderableCallback attribute.
	 *
	 * @param string $columnReorderCallBack
	 *        	the value of the property.
	 */
	public function setColumnReorderCallback($columnReorderCallBack) {
		$this->columnReorderCallBack = $columnReorderCallBack;
	}
	
	/**
	 * Setter for the columnOrder array.
	 *
	 * @param array $columnOrder
	 *        	the value of the property.
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
	 * 
	 * @param
	 *        	$draw
	 * @param
	 *        	$recordsTotal
	 * @param
	 *        	$recordsFiltered
	 * @param
	 *        	$data
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
			if ($column->getColumnType () != StesiColumnType::Button) {
				if (! $onlyGlobalSerchable){
					$tableColumnsNames [] = array("name"=>$column->getColumnName (),"alias"=>$column->getAlias());
				}
				else {
					if ($column->isGlobalSerchable ())
						$tableColumnsNames [] = array("name"=>$column->getColumnName (),"alias"=>$column->getAlias());
				}
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
	 * @param string $ajaxCallBack
	 *        	name of function to call to render Datatable
	 * @return string html of DataTable with the javascript that manage the table
	 */
	public function getTable($ajaxCallBack) {
		if ($this->form) {
			$this->form->setAttribute ( "action", null );
			$this->form->configure ( array (
					"prevent" => array (
							"bootstrap",
							"jQuery",
							"jqueryui" 
					) 
			) );
			$this->form->addElement ( new Button ( "Filtra", "button", array (
					"id" => "filter_button",
					"class" => "btn-primary" 
			) ) );
		}
		$table = "
				<table id=\"" . $this->id . "\"  class='table-striped table-bordered table-hover  dataTable nowrap' cellspacing='0'
                   width='100%'>";
		
		$tableColums = $this->getColumns ();
		$th = "<tr>";
		foreach ( $tableColums as $column ) {
			$th .= "<th>" . $column->getColumnDescription () . "</th>";
		}
		$th .= "</tr>";
		
		$table .= "<thead>";
		$table .= $th . "</thead></table>";
		$table .= $this->createJsScript ( $ajaxCallBack );
		return $table;
	}
	/**
	 * Add datatable button as 'print','copy','colvis'....
	 * 
	 * @param string $buttonName        	
	 * @return StesiTable
	 */
	public function addDatatableButton($buttonName,$htmlText=null,$tooltip=null) {
		$this->datatableButtons [] = array("name"=>$buttonName,"text"=>$htmlText,
				"titleAttr"=>$tooltip);
		return $this;
	}
	public function getDatatableButtons() {
		return $this->datatableButtons;
	}
	/**
	 * Add a custom Button initialized by StesiTableButton Class
	 * 
	 * @param StesiTableButton $stesiButton        	
	 * @return StesiTable
	 */
	public function addStesiButton(StesiTableButton $stesiButton) {
		$this->stesiTableButtons [] = $stesiButton;
		return $this;
	}
	
	public function setStesiButtons(array $stesiButtons) {
		$this->stesiTableButtons= $stesiButtons;
		return $this;
	}
	
	public function getStesiButtons() {
		return $this->stesiTableButtons;
	}
	private function createJsScript($ajaxCallBack) {
		$tableColums = $this->getColumns ();
		
		$table = "<script>";
		$table .= '
				$.fn.serializeForm = function() {
    if ( this.length < 1) { 
      return false; 
    }

    var data = {};
    var lookup = data; 
    var selector = ":input[type!=\"checkbox\"][type!=\"radio\"][type!=\"button\"], input:checked";
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
            ( (named[ i + 1 ] === "" || named[ i + 1 ] === "0") ? ($el.val()==null?[]:$el.val()) : {} );
        }

        // at the end, push or assign the value
        if ( lookup.length !==  undefined ) {
          //lookup.push( $el.val() );
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
			serverSide : true,';
		
		$dom='ftl<\"pull-left\"i>p';
		$buttons= $this->inizializeButtons ();
		if(!empty($buttons)){
			
			$dom="<\"pull-left\"B>".$dom;
			$table.=$buttons;
		}
		$table.="dom:'".$dom."',";
		$table .= '
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
      	d.'.$this->filterFormName.'=$("#' . $this->id . '_form").serializeForm();
    		
	}
  }';
		// $("#'.$this->id.'_form").serialize();
		$table .= ',columns:[';
		$buttonsFunction = array ();
		/*
		 * Create column dinamically with custom class that has the same name of column, data with columnName without point (ex ArticoliNatura), name with columnName with point (ex Articoli.Natura)
		 */
		foreach ( $tableColums as $column ) {
			if ($column->getColumnType () == StesiColumnType::Button) {
				$table .= '{ 						
								"data": "null",
								"defaultContent":"<button class=\"' . $column->getColumnName ( false ) . '\">' . $column->getColumnDescription () . '</button>" },';
				array_push ( $buttonsFunction, array (
						"class" => $column->getColumnName ( false ),
						"function" => $column->getJsButtonCallback () 
				) );
			} else {
				$table .= '{ "class": "' . $column->getColumnData() . '","data": "' . $column->getColumnData() . '","name":"' . $column->getColumnData() . '" },';
			}
		}
		
		/*
		 * At InitComplete:
		 * bind enter call to global search and column search on footer columns
		 */
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
			       ';
					if(!empty($this->globalFilter)){
			      		$table.='
			      				
			      				$("#' . $this->id . '_filter input").val("'.$this->globalFilter.'");';
					}
			        $table.='
			},			        		
			createdRow: function(row,data,index){
				applyStyles(row,data);
			}
	});
				';
		if (! empty ( $buttonsFunction )) {
			foreach ( $buttonsFunction as $external ) {
				$table .= "
							$('#" . $this->id . " tbody').on('click','button." . $external ['class'] . "',function(){
									" . $external ['function'] . "();
											});";
			}
		}
		
		/*
		 * Column Reorderable function:
		 * Reorder columns if specified and create an ajax call to the columnReorderCallBack if is specified
		 */
		if ($this->isColReorderable) {
			$table .= "
			new $.fn.dataTable.ColReorder(datatable,{
				realtime: true";
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
	             		datatable.rows().every( function () {
	             				applyStyles(this.node(),this.data());		
	             		} );
	             	}";
			}
			$table .= "
			});
			
			";
		}
		$table.=$this->createFunctionColumnStyles();
		$table .= "
		 $('#".$this->id." tbody')
        .on( 'mouseenter', 'td', function () {
            var colIdx = datatable.cell(this).index().column;
 
            $( datatable.cells().nodes() ).removeClass( 'highlight' );
            $( datatable.column( colIdx ).nodes() ).addClass( 'highlight' );
        } );
				
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

			       $('#" . $this->id . "_form input').unbind();
			        $('#" . $this->id . "_form input').bind('keyup', function(e) {
			          if(e.keyCode == 13 || (e.keyCode==8 && this.value=='')) {
			        	  datatable.draw();
			            }
			          
			        });
			        
			        $('#filter_button').bind('click', function(e) {
			          	e.preventDefault();
			        	datatable.draw();
			        });";
		
		$table .= "</script>";
		return $table;
	}
	
	private function createFunctionColumnStyles(){
	
		$script="function applyStyles(row,data)
		{
				";
		$tableColums = $this->getColumns ();
		foreach ( $tableColums as $column ) {
		/*
		* Apply column styles dinamically to each column
		*/
		$columnStyles = $column->getColumnStyles ();
		foreach ( $columnStyles as $columnStyle ) {
		/*
		* Using column Operator and Value to determine if the style is applicable
		* Ex data['natura']='MDR'
		* EX data['id_articolo']>'10000'
		*/
		$script .= "
				if(data['" . $column->getColumnData() . "']" . $columnStyle->getConditionOperator () . "'" . $columnStyle->getValue () . "'){";
		$script .= '
		$("td.' . $column->getColumnData() . '", row)';
		if (count ( $columnStyle->getClasses () ) > 0) {
		$script .= '.addClass("' . implode ( " ", $columnStyle->getClasses () ) . '")';
		}
		if (count ( $columnStyle->getCss () ) > 0) {
		foreach ( $columnStyle->getCss () as $css ) {
			
		$script .= '.css("' . $css ["propertyName"] . '","' . $css ['value'] . '")';
		}
		}
		if (count ( $columnStyle->getHtml () ) > 0) {
		foreach ( $columnStyle->getHtml () as $html) {
			
		$script .= '.html("' . $html.'")';
		}
		}
		if(!empty($columnStyle->getPClass())){
		$script .= '.html("<p class=\''.$columnStyle->getPClass().'\' style=\'width:100%\'>"+ data[\'' . $column->getColumnData() . '\']+"</p>")';
		}
		$script .= ";}
				";	
		}
		}
		$script.="}";
		return $script;
	}
	
	private function inizializeButtons() {
		if (! empty ( $this->getDatatableButtons () ) || ! empty ( $this->getStesiButtons () )) {
			$buttons="";
			foreach ( $this->getDatatableButtons () as $datatableButton ) {
				if(empty($datatableButton['text']) && empty($datatableButton['titleAttr'])){
					$buttons .= ",'" . $datatableButton['name'] . "'";
				}else{
					$buttons.=",{
							extend: '".$datatableButton['name']."',
							text: '".$datatableButton['text']."',
							titleAttr:'".$datatableButton['titleAttr']."'
							}";
				}
				
			}
			foreach ( $this->getStesiButtons() as $stesiButton ) {
				$text = ! empty ( $stesiButton->getText () ) ? $stesiButton->getText () : $stesiButton->getId ();
				$buttons .= ",{
					text:'" . $text . "',					
					init :function (e,dt){
						dt.context.id='" . $stesiButton->getId () . "'		
					}
					";
				$class = $stesiButton->getClass ();
				if (! empty ( $class )) {
					$buttons .= ",className:'" . $class . "'";
				}
				
				$tooltip=$stesiButton->getTooltip();
				if(!empty($tooltip)){
					$buttons.=",titleAttr:'".$tooltip."'";
				}
				$action = $stesiButton->getAction ();
				if (! empty ( $action )) {
					$buttons .= ",action:'" . $action . "'";
				}
				$buttons .= "}";
			}
			$buttons = 'buttons: [' . substr($buttons,1)."],";
			return $buttons;
		} else
			return "";
	}
	public function renderForm() {
		if ($this->form) {
			return $this->form->render ( true );
		}
		return "";
	}
	
	/**
	 * globalFilter
	 * @return string
	 */
	public function getGlobalFilter(){
		return $this->globalFilter;
	}
	
	/**
	 * globalFilter
	 * @param string $globalFilter
	 * @return StesiTable
	 */
	public function setGlobalFilter($globalFilter){
		$this->globalFilter = $globalFilter;
		return $this;
	}
	
}

class StesiTableButton {
	private $text;
	private $action;
	private $class;
	private $id;
	private $tooltip;
	function __construct($id) {
		$this->id = $id;
	}
	public function getId() {
		return $this->id;
	}
	
	/**
	 * text
	 * 
	 * @return unkown
	 */
	public function getText() {
		return $this->text;
	}
	
	/**
	 * text
	 * 
	 * @param unkown $text        	
	 * @return StesiTableButton
	 */
	public function setText($text) {
		$this->text = $text;
		return $this;
	}
	
	/**
	 * action
	 * 
	 * @return unkown
	 */
	public function getAction() {
		return $this->action;
	}
	
	/**
	 * action
	 * 
	 * @param unkown $action        	
	 * @return StesiTableButton
	 */
	public function setAction($action) {
		$this->action = $action;
		return $this;
	}
	
	/**
	 * class
	 * 
	 * @return unkown
	 */
	public function getClass() {
		return $this->class;
	}
	
	/**
	 * class
	 * 
	 * @param unkown $class        	
	 * @return StesiTableButton
	 */
	public function setClass($class) {
		$this->class = $class;
		return $this;
	}

   


    /**
     * tooltip
     * @return string
     */
    public function getTooltip(){
        return $this->tooltip;
    }

    /**
     * tooltip
     * @param string $tooltip
     * @return StesiTable
     */
    public function setTooltip($tooltip){
        $this->tooltip = $tooltip;
        return $this;
    }

}
