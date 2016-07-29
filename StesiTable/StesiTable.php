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
	private $defaultIndexOrder;
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
	private $fixedColumnLeft;
	private $fixedColumnRight;
	private $stateSaving = true;
	private $columnOrder;
	private $form;
	private $datatableButtons;
	private $stesiTableButtons;
	private $toolsButtons;
	private $filterFormName;
	private $globalFilter = "";
	private $rowDataAttributes = array ();
	private $rowClasses = array ();
	private $tableClasses = "";
	
	function addRowClass($className) {
		$this->rowClasses [] = $className;
		return $this;
	}
	function getRowClasses() {
		return $this->rowClasses;
	}
	public function getRowDataAttributes() {
		return $this->rowDataAttributes;
	}
	public function stateSaving($stateSaving) {
		$this->stateSaving = $stateSaving;
	}
	public function getStateSaving() {
		return $this->stateSaving;
	}
	public function addRowDataAttributes($attributeKey, $attributeValue) {
		$this->rowDataAttributes [$attributeKey] = str_replace ( ".", "", $attributeValue );
	}
	
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
		$this->toolsButtons = array ();
		$this->fixedColumnLeft = $this->fixedColumnRight = 0;
		if ($useForm) {
			$this->form = new Form ( $this->id . "_form" );
			$this->filterFormName = "filter";
			$this->addFilterButton ( "filter_button_top" );
		}
	}
	
	/**
	 *
	 * @param string $filterFormName
	 *        	return @StesiTable
	 */
	public function setFilterFormName($filterFormName) {
		$this->filterFormName = $filterFormName;
	}
	public function getFilterFormName() {
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
			if (! $stesiColumn->isHidden () && $stesiColumn->getColumnType () != StesiColumnType::Button) {
				// Se è settato a true, creo un elemento della form instanziando dinamicamente un elemento PFBC e aggiungendolo alla form
				$class = new \ReflectionClass ( "PFBC\Element\\" . (array_flip ( (new \ReflectionClass ( "Stesi\StesiTable\StesiColumnType" ))->getConstants () ) [$stesiColumn->getColumnType ()]) );
				if (! $class)
					throw new \Exception ( "PFBC class " . $class . " not found" );
				
				$this->setDefaultColumnAttribute ( $stesiColumn );
				
				$instance = $class->newInstanceArgs ( array (
						($stesiColumn->getColumnType()==StesiColumnType::Select?$stesiColumn->getColumnDescription():null),
						$stesiColumn->getColumnFilterName (),
						$stesiColumn->getOptions (),
						$stesiColumn->getProperties () 
				) );
				$this->form->addElement ( $instance );
			}
		}
		return $this->columns [$stesiColumn->getColumnName ()];
	}
	private function setDefaultColumnAttribute(StesiColumn $stesiColumn) {
		if ($stesiColumn->getColumnType () != StesiColumnType::Select) {
			if (! array_key_exists ( "placeholder", $stesiColumn->getOptions () )) {
				$stesiColumn->setOptions ( array_merge ( $stesiColumn->getOptions (), array (
						"placeholder" => $stesiColumn->getColumnDescription () 
				) ) );
			}
			if (! array_key_exists ( "title", $stesiColumn->getOptions () )) {
				$stesiColumn->setOptions ( array_merge ( $stesiColumn->getOptions (), array (
						"title" => $stesiColumn->getColumnDescription () 
				) ) );
			}
			if (! array_key_exists ( "style", $stesiColumn->getOptions () )) {
				$stesiColumn->setOptions ( array_merge ( $stesiColumn->getOptions (), array (
						"style" => "margin-bottom:5px;" 
				) ) );
			}
			
			if($stesiColumn->getColumnType()==StesiColumnType::Date){
				if (! array_key_exists ( "multiple", $stesiColumn->getOptions () )){
					$stesiColumn->getOptions ( array_merge ( $stesiColumn->getOptions (), array (
							"multiple" => true
					) ) );
				}
			}
			
			if (! array_key_exists ( "class", $stesiColumn->getOptions () )){
				$stesiColumn->setOptions ( array_merge ( $stesiColumn->getOptions (), array (
						"class" => "form-control stesi_".(array_flip ( (new \ReflectionClass ( "Stesi\StesiTable\StesiColumnType" ))->getConstants ())[$stesiColumn->getColumnType()] ).((array_key_exists ( "multiple", $stesiColumn->getOptions () ) && $stesiColumn->getOptions()['multiple']=="false")?"single":"") 
				) ) );
			}
			
			if (! array_key_exists ( "id", $stesiColumn->getOptions () )) {
				$stesiColumn->setOptions ( array_merge ( $stesiColumn->getOptions (), array (
						"id" => $stesiColumn->getColumnData () 
				) ) );
			}
			if (! array_key_exists ( "value", $stesiColumn->getOptions () )) {
				$stesiColumn->setOptions ( array_merge ( $stesiColumn->getOptions (), array (
						"value" => $this->getValueRequestSession ( $this->filterFormName, $stesiColumn->getColumnFilterName (), $stesiColumn->getDefaultFilterValue () ) 
				) ) );
			}
		} else {
			if (! array_key_exists ( "id", $stesiColumn->getProperties () )) {
				$stesiColumn->setProperties ( array_merge ( $stesiColumn->getProperties (), array (
						"id" => $stesiColumn->getColumnData () 
				) ) );
			}
			if (! array_key_exists ( "multiple", $stesiColumn->getProperties () )){
				$stesiColumn->setProperties ( array_merge ( $stesiColumn->getProperties (), array (
						"multiple" => true 
				) ) );
			}
			if (! array_key_exists ( "class", $stesiColumn->getProperties () )){
				$stesiColumn->setProperties ( array_merge ( $stesiColumn->getProperties (), array (
				"class" => "form-control stesi_".(array_flip ( (new \ReflectionClass ( "Stesi\StesiTable\StesiColumnType" ))->getConstants ())[$stesiColumn->getColumnType()] ).((!$stesiColumn->getProperties("multiple"))?"single":"")
				) ) );
			}
			if (! array_key_exists ( "value", $stesiColumn->getProperties () )) {
				$stesiColumn->setProperties ( array_merge ( $stesiColumn->getProperties (), array (
						"value" => $this->getValueRequestSession ( $this->filterFormName, $stesiColumn->getColumnFilterName (), $stesiColumn->getDefaultFilterValue () ) 
				) ) );
			}
		}
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
	public function setNumberFixedLeftColumns($fixedLeftColumns) {
		$this->fixedColumnLeft = $fixedLeftColumns;
	}
	public function setNumberFixedRightColumns($fixedRightColumns) {
		$this->fixedColumnRight = $fixedRightColumns;
	}
	public function getNumberFixedLeftColumns() {
		return $this->fixedLeftColumns;
	}
	public function getNumberFixedRightColumns() {
		return $this->fixedRightColumns;
	}
	
	/**
	 *
	 * @param boolean $onlyGlobalSerchable        	
	 */
	public function getTableColumnsNames($onlyGlobalSerchable = 0) {
		$tableColumnsNames = array ();
		
		foreach ( $this->columns as $column ) {
			if ($column->getColumnType () != StesiColumnType::Button) {
				if (! $onlyGlobalSerchable) {
					$tableColumnsNames [] = array (
							"name" => $column->getColumnName (),
							"alias" => $column->getAlias () 
					);
				} else {
					if ($column->isGlobalSerchable ())
						$tableColumnsNames [] = array (
								"name" => $column->getColumnName (),
								"alias" => $column->getAlias () 
						);
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
	private function addFilterButton($id) {
		$this->form->addElement ( new Button ( "Filtra", "button", array (
				"id" => $id,
				"class" => "btn btn-primary btn-block",
				"style" => "margin-top:10px;margin-bottom:10px;" 
		) ) );
	}
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
			
			$this->addFilterButton ( "filter_button_bottom" );
		}
		$classi_da_aggiungere = $this->tableClasses;
		$table = "
				<table id=\"" . $this->id . "\"  class='table table-striped table-bordered table-hover  dataTable nowrap ".$classi_da_aggiungere."' cellspacing='0'
                   width='100%'>";
		
		$tableColums = $this->getColumns ();
		$th = "<tr>";
		foreach ( $tableColums as $column ) {
			if (! $column->isHidden ()) {
					$th .= "<th>" . $column->getColumnDescription () . "</th>";
			}
		}
		$th .= "</tr>";
		
		$table .= "<thead>";
		$table .= $th . "</thead>";
		$table .= "<tfoot>";
		
		$th = "<tr>";
		foreach ( $tableColums as $column ) {
			if (! $column->isHidden ()) {
					$th .= "<th data-filter_id='".$column->getColumnName(false)."'>" . $column->getColumnDescription () . "</th>";
				
			}
		}
		$th .= "</tr>";
		
		$table .= $th . "</tfoot></table>";
		
		$table .= $this->createJsScript ( $ajaxCallBack );
		return $table;
	}
	/**
	 * Add datatable button as 'print','copy','colvis'....
	 *
	 * @param string $buttonName        	
	 * @return StesiTable
	 */
	public function addDatatableButton($buttonName, $htmlText = null, $tooltip = null) {
		$this->datatableButtons [] = array (
				"name" => $buttonName,
				"text" => $htmlText,
				"titleAttr" => $tooltip 
		);
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
		$this->stesiTableButtons = $stesiButtons;
		return $this;
	}
	public function getStesiButtons() {
		return $this->stesiTableButtons;
	}
	
	public function setToolsButtons(array $toolsButtons) {
		$this->toolsButtons = $toolsButtons;
		return $this;
	}
	public function getToolsButtons() {
		return $this->toolsButtons;
	}
	
	
	public function addDatatableClasses($string)
	{
		$this->tableClasses = $string;
		return $this;
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
			ordering:true,
			stateSave: ' . ($this->stateSaving == true ? "true" : "false") . ',
			select: true,
			stateDuration: 24*60*60,
			scrollCollapse: true,
			scrollY: 800,
	        scrollX:        true,
			serverSide : true,';
		
		$dom = "<'row'<'col-sm-6'B><'col-sm-6'f>>\" +
\"<'row'<'col-sm-12'tr>>\" +
\"<'row'<'col-sm-8'li><'col-sm-4'p>>";
		
		// $dom = 'ftl<\"pull-left\"i>p';
		$buttons = $this->initializeButtons ();
		if (! empty ( $buttons )) {
			
			// $dom = "<\"pull-left\"B>" . $dom;
			$table .= $buttons;
		}
		$table .= "dom:\"" . $dom . "\",";
		$table .= '
				 "lengthMenu": [ 10, 25, 50,75,100,500,1000],' . (! empty ( $this->defaultIndexOrder ) ? 'order: [[' . $this->defaultIndexOrder . ' , "desc" ]],' : 'order:[[0,"desc"]],') . 
				 '"language": {
        	    
				  search: "<span class=\"input-icon\">_INPUT_<i class=\"glyphicon glyphicon-search primary\"></i></span>",
        searchPlaceholder: "Ricerca Globale",
        	    "lengthMenu": "_MENU_",
        	    "info": "_PAGES_ Pagine / Totale elementi: _TOTAL_",
        	    "processing": "Caricamento dati in corso...",
				"paginate":{
				 	"previous":"<<",
				 	"next":">>",
				 }
        	  },
        "ajax": {
    "url": "' . $ajaxCallBack . '",
    type:"POST",
    data: function ( d ) {
      	d.' . $this->filterFormName . '=$("#' . $this->id . '_form").serializeForm();
	}
  }';
		// $("#'.$this->id.'_form").serialize();
		$table .= ',columns:[';
		$buttonsFunction = array ();
		/*
		 * Create column dinamically with custom class that has the same name of column, data with columnName without point (ex ArticoliNatura), name with columnName with point (ex Articoli.Natura)
		 */
		foreach ( $tableColums as $column ) {
			// Se è nascosta, la colonna non deve essere visualizzata
			if (! $column->isHidden ()) {
				if ($column->getColumnType () == StesiColumnType::Button) {
					
					$table .= '{ 	
						"class":"' . $column->getColumnData () . '",
						"data": "null",
						"orderable": false,';
					if(!empty($column->getColumnDescription())){
						$table.='"title":"' . $column->getColumnDescription () . '",';
					}
						$table.='
						"defaultContent":
						"<button class=\"' . $column->getColumnData () . '\"></button>" },';
					if (! empty ( $column->getJsButtonCallback () )) {
						array_push ( $buttonsFunction, array (
								"class" => $column->getColumnName ( false ),
								"function" => $column->getJsButtonCallback () 
						) );
					}
				} else {
					$table .= '{ 
							"class": "' . $column->getColumnData () . '","data": "' . $column->getColumnData () . '","name":"' . $column->getColumnData () . '",
							"orderable":' . var_export ( $column->isOrderable (), true );
					if (! empty ( $column->getHyperlink () )) {
						$table .= ',"render": function ( data, type, full, meta ) {
      return \'<a href="' + $column->getHyperlink () + '">Download</a>\';
    		},';
					}
					
					$table .= '},';
				}
			}
		}
		
		/*
		 * At InitComplete:
		 * bind enter call to global search and column search on footer columns
		 */
		$table .= '
        ],
				createdRow:function(row,data){
				row = applyStyles(row,data);
				//console.log(row);
				},
				initComplete: function() {
				
						datatable.draw();
						var api = this.api();
				  $("#' . $this->id . '_filter input").unbind();
			        $("#' . $this->id . '_filter input").bind("keyup", function(e) {
			          if(e.keyCode == 13 || (e.keyCode==8 && this.value=="")) {
			        	  api.search( this.value ).draw();
			            }
			        });
			       
			   ';
		if (! empty ( $this->globalFilter )) {
			$table .= '
					$("#' . $this->id . '_filter input").val("' . $this->globalFilter . '");';
		}
		$table .= '
			}
	});
				';
		
		
		if (! empty ( $buttonsFunction )) {
			foreach ( $buttonsFunction as $external ) {
				$table .= "
							$('#" . $this->id . " tbody').on('click','button." . $external ['class'] . "',function(){
									" . $external ['function'] . "(this);
											});";
			}
		}
		
		/*
		 * Column Reorderable function:
		 * Reorder columns if specified and create an ajax call to the columnReorderCallBack if is specified
		 */
		if ($this->isColReorderable) {
			
			if ($this->fixedColumnLeft > 0 || $this->fixedColumnRight > 0) {
				$table .= "
									 new $.fn.dataTable.FixedColumns( datatable, {
									'leftColumns':" . $this->fixedColumnLeft . ",
									'rightColumns':" . $this->fixedColumnRight . "
			           } );
									";
			}
			$table .= " new $.fn.dataTable.ColReorder(datatable,{
				realtime: 'false'
				";
			
			if (! empty ( $this->columnOrder )) {
				$table .= ",
									order: [" . implode ( ",", $this->columnOrder ) . "]";
			}
			
			if ($this->fixedColumnLeft > 0 || $this->fixedColumnRight > 0) {
				$table .= "
						,fixedColumnsLeft:" . ($this->fixedColumnLeft > 0 ? ($this->fixedColumnLeft) : "0") . ",
						fixedColumnsRight:" . ($this->fixedColumnRight > 0 ? ($this->fixedColumnRight) : "0");
			}
			if ($this->stateSaving && ! empty ( $this->columnReorderCallBack )) {
				$table .= ",reorderCallback:function(){
										datatable.draw();
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
		$table .= $this->createFunctionColumnStyles ();
		$table .= "
					
			       $('#" . $this->id . "_form input').unbind();
			        $('#" . $this->id . "_form input').bind('keyup', function(e) {
			          if(e.keyCode == 13 || (e.keyCode==8 && this.value=='')) {
			        	  datatable.draw();
			            }
			          
			        });
			         $('#filter_button_top').bind('click', function(e) {
			          	e.preventDefault();
			        	datatable.draw();
			        });
			        $('#filter_button_bottom').bind('click', function(e) {
			          	e.preventDefault();
			        	datatable.draw();
			        });";
		
		$table .= "</script>";
		return $table;
	}
	private function createFunctionColumnStyles() {
		$script = "function applyStyles(row,data)
		{
				var elemento = $(row);
				
				";
		foreach ( $this->rowClasses as $class ) {
			$script .= "elemento.addClass('" . $class . "');";
		}
		foreach ( $this->rowDataAttributes as $key => $value ) {
			$script .= "
					elemento.data('" . $key . "',data['" . $value . "']);";
		}
		
		$tableColums = $this->getColumns ();
		foreach ( $tableColums as $column ) {
			
			/*
			 * Apply column data-attributes
			 */
			
			$scriptStyle = "";
			$selector = '$("td.' . $column->getColumnData () . ' ", row)';
			if ($column->getColumnType () == StesiColumnType::Button) {
				$selector = '$("td.' . $column->getColumnData () . ' button", elemento)';
			}
			
			foreach ( $column->getDataAttributes () as $key => $value ) {
				
				$scriptStyle .= $selector . '.data("' . $key . '",data["' . $value . '"]);';
			}
			
			foreach ( $column->getCustomAttributes () as $key => $value ) {
				
				$scriptStyle .= $selector . '.data("' . $key . '","' . $value . '");';
			}
			
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
				if (! empty ( $columnStyle->getConditionOperator () )) {
					$scriptStyle .= "if(data['" . (! empty ( $columnStyle->getOtherColumnId () ) ? $columnStyle->getOtherColumnId () : $column->getColumnData ()) . "']" . $columnStyle->getConditionOperator () . "'" . $columnStyle->getValue () . "'){";
				} else {
					$scriptStyle .= " if(true){";
				}
				
				$scriptStyle .= $selector;
				if (count ( $columnStyle->getClasses () ) > 0) {
					$scriptStyle .= '.addClass("' . implode ( " ", $columnStyle->getClasses () ) . '")';
				}
				if (count ( $columnStyle->getCss () ) > 0) {
					foreach ( $columnStyle->getCss () as $css ) {
						
						$scriptStyle .= '.css("' . $css ["propertyName"] . '","' . $css ['value'] . '")';
					}
				}
				if (! empty ( $columnStyle->getPClass () )) {
					$scriptStyle .= '.html("<p class=\'' . $columnStyle->getPClass () . '\' style=\'width:100%\;margin:1px;\'>"+ data[\'' . $column->getColumnData () . '\']+"</p>")';
				}
				$scriptStyle .= ";";
				
				if (! $columnStyle->getVisibility ()) {
					$scriptStyle .= '$("td.' . $column->getColumnData () . '", row).html("")';
				}
				
				$scriptStyle .= ";";
				if (count ( $columnStyle->getHtml () ) > 0) {
					foreach ( $columnStyle->getHtml () as $html ) {
						$scriptStyle .= '$("' . $html . '").prependTo($("td.' . $column->getColumnData () . '", row));';
					}
				}
				if ($columnStyle->getIcon ()) {
					$scriptStyle .= $selector . '.append("' . $columnStyle->getIcon () . '");';
				}
				$scriptStyle .= "} else ";
			}
			if (! empty ( $scriptStyle ))
				$scriptStyle = substr ( $scriptStyle, 0, strlen ( $scriptStyle ) - 5 );
			$script .= $scriptStyle . ";";
		}
		
		$script .= "return elemento;}";
		return $script;
	}
	private function initializeButtons() {
		$buttons = "";
		
		if (! empty ( $this->getDatatableButtons () ) || !empty($this->getToolsButtons()) ) {
			$buttons="{
					extend:'collection',
					init :function (e,dt){
						dt.context.id='tools_button';
					},
					text:'<i class=\"fa fa-gears\"></i>',
					fade:false,
					buttons:[";
			$datatableButtons="";
			foreach ( $this->getDatatableButtons () as $datatableButton ) {
				if (empty ( $datatableButton ['text'] ) && empty ( $datatableButton ['titleAttr'] )) {
					$datatableButtons .= "{'" . $datatableButton ['name'] . "'},";
				} else {
					$datatableButtons .= "{
							extend: '" . $datatableButton ['name'] . "',
							text: '" . $datatableButton ['text'] . "',
							titleAttr:'" . $datatableButton ['titleAttr'] . "'
							},";
				}
			}
			if(! empty ( $this->getToolsButtons () )){
				foreach ( $this->getToolsButtons () as $toolsButton ) {
					$text = ! empty ( $toolsButton->getText () ) ? $toolsButton->getText () : $toolsButton->getId ();
					$datatableButtons .= ",{
					text:'" . $text . "',
					init :function (e,dt){
						dt.context.id='" . $toolsButton->getId () . "'";
					foreach ( $toolsButton->getCustomAttributes () as $key => $value ) {
						$datatableButtons .= ",dt.data('" . $key . "','" . $value . "')";
					}
					$datatableButtons .= "
					}
					";
					$class = $toolsButton->getClass ();
					if (! empty ( $class )) {
						$datatableButtons .= ",className:'" . $class . "'";
					}
					
					$tooltip = $toolsButton->getTooltip ();
					if (! empty ( $tooltip )) {
						$datatableButtons .= ",titleAttr:'" . $tooltip . "'";
					}
					$action = $toolsButton->getAction ();
					if (! empty ( $action )) {
						$datatableButtons .= ",action:function(){" . $action . "();}";
					}
					$datatableButtons .= "},";
				}
			}
			$buttons.=substr($datatableButtons,0,strlen($datatableButtons)-1)."]}";
		}
		if(! empty ( $this->getStesiButtons () )){
			foreach ( $this->getStesiButtons () as $stesiButton ) {
				$text = ! empty ( $stesiButton->getText () ) ? $stesiButton->getText () : $stesiButton->getId ();
				$buttons .= ",{
					text:'" . $text . "',
					init :function (e,dt){
						dt.context.id='" . $stesiButton->getId () . "'";
				foreach ( $stesiButton->getCustomAttributes () as $key => $value ) {
					$buttons .= ",dt.data('" . $key . "','" . $value . "')";
				}
				$buttons .= "
					}
					";
				$class = $stesiButton->getClass ();
				if (! empty ( $class )) {
					$buttons .= ",className:'" . $class . "'";
				}
				
				$tooltip = $stesiButton->getTooltip ();
				if (! empty ( $tooltip )) {
					$buttons .= ",titleAttr:'" . $tooltip . "'";
				}
				$action = $stesiButton->getAction ();
				if (! empty ( $action )) {
					$buttons .= ",action:function(){" . $action . "();}";
				}
				$buttons .= "}";
			}
		}
		if(!empty($buttons)){
			$buttons = 'buttons: [' . $buttons . "],";
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
	 *
	 * @return string
	 */
	public function getGlobalFilter() {
		return $this->globalFilter;
	}
	
	/**
	 * globalFilter
	 *
	 * @param string $globalFilter        	
	 * @return StesiTable
	 */
	public function setGlobalFilter($globalFilter) {
		$this->globalFilter = $globalFilter;
		return $this;
	}
	
	/**
	 * defaultIndexOrder
	 *
	 * @return int
	 */
	public function getDefaultIndexOrder() {
		return $this->defaultIndexOrder;
	}
	
	/**
	 * defaultIndexOrder
	 *
	 * @param int $defaultIndexOrder        	
	 * @return StesiTable
	 */
	public function setDefaultIndexOrder($defaultIndexOrder) {
		$this->defaultIndexOrder = $defaultIndexOrder;
		return $this;
	}
	

	private function getValueRequestSession($key,$value=null,$default = "",$pageId=null) {
		$hash = $_REQUEST ['hash'];
		$val="";
		if(!empty($value)){
				
			$val = isset ( $_REQUEST [$key][$value] ) ? $_REQUEST [$key][$value]
			:
			(!empty($pageId)?
					(isset ( $_SESSION ["$hash"][$pageId] [$key][$value] ) ? $_SESSION ["$hash"][$pageId] [$key][$value] : $default)
					:(isset ( $_SESSION ["$hash"] [$key][$value] ) ? $_SESSION ["$hash"] [$key][$value] : $default)
					);
			if(!empty($pageId)){
				if(!isset($_SESSION["$hash"][$pageId]) || !is_array($_SESSION["$hash"][$pageId])){
					$_SESSION["$hash"][$pageId]=array();
				}
				if(!isset($_SESSION["$hash"][$pageId][$key]) || !is_array($_SESSION["$hash"][$pageId][$key])){
					$_SESSION["$hash"][$pageId][$key]=array();
				}
	
				$_SESSION ["$hash"][$pageId] [$key][$value]=$val;
			}else{
				if(!isset($_SESSION["$hash"][$key]) || !is_array($_SESSION["$hash"][$key])){
					$_SESSION["$hash"][$key]=array();
				}
				$_SESSION ["$hash"][$key][$value]=$val;
	
			}
				
		}else{
			$val = isset ( $_REQUEST [$key] ) ? $_REQUEST [$key]
			:
			(!empty($pageId)?
					(isset ( $_SESSION ["$hash"][$pageId] [$key] ) ? $_SESSION ["$hash"][$pageId] [$key] : $default)
					:(isset ( $_SESSION ["$hash"] [$key] ) ? $_SESSION ["$hash"] [$key] : $default)
					);
			if(!empty($pageId)){
				if(!isset($_SESSION["$hash"][$pageId]) || !is_array($_SESSION["$hash"][$pageId])){
					$_SESSION["$hash"][$pageId]=array();
				}
				$_SESSION ["$hash"][$pageId] [$key]=$val;
			}else{
				$_SESSION ["$hash"][$key]=$val;
			}
				
				
		}
	
		return $val;
	}
}
class StesiTableButton {
	private $text;
	private $action;
	private $class;
	private $id;
	private $tooltip;
	private $customAttributes;
	function __construct($id) {
		$this->id = $id;
		$this->customAttributes = array ();
	}

	public function getId() {
		return $this->id;
	}
	public function getCustomAttributes() {
		return $this->customAttributes;
	}
	public function addCustomAttribute($key, $attr) {
		$this->customAttributes [$key] = $attr;
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
	 *
	 * @return string
	 */
	public function getTooltip() {
		return $this->tooltip;
	}
	
	/**
	 * tooltip
	 *
	 * @param string $tooltip        	
	 * @return StesiTable
	 */
	public function setTooltip($tooltip) {
		$this->tooltip = $tooltip;
		return $this;
	}
	
}
