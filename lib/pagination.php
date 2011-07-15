<?php
/**
 * PHPSense Pagination Class
 *
 * PHP tutorials and scripts
 *
 * @package		PHPSense
 * @author		Jatinder Singh Thind
 * @copyright	Copyright (c) 2006, Jatinder Singh Thind
 * @link		http://www.phpsense.com
 */

// ------------------------------------------------------------------------


class Pagination {
	var $php_self;
	var $rows_per_page = 10; //Number of records to display per page
	var $total_rows = 0; //Total number of rows returned by the query
	var $links_per_page = 5; //Number of links to display per page
	var $append = ""; //Paremeters to append to pagination links
	var $model = false;
    var $filters = array();
    var $sortBy  = array();
	var $debug = false;
	var $page = 1;
	var $max_pages = 0;
	var $offset = 0;
	
	/**
	 * Constructor
	 *
	 * @param resource $connection Mysql connection link
	 * @param string $sql SQL query to paginate. Example : SELECT * FROM users
	 * @param integer $rows_per_page Number of records to display per page. Defaults to 10
	 * @param integer $links_per_page Number of links to display per page. Defaults to 5
	 * @param string $append Parameters to be appended to pagination links 
	 */
	
	public function __construct($model, $filters = array() , $sortby = array() , $rows_per_page = 10, $links_per_page = 5, $append = "") {
		$this->model = $model;
        $this->filters = $filters;
        $this->sortBy = $sortby;
		$this->rows_per_page = (int)$rows_per_page;
		if (intval($links_per_page ) > 0) {
			$this->links_per_page = (int)$links_per_page;
		} else {
			$this->links_per_page = 5;
		}
		$this->append = $append;
		$this->php_self = htmlspecialchars($_SERVER['PHP_SELF'] );
		if (isset($_GET['page'] )) {
			$this->page = intval($_GET['page'] );
		}
	}
	
    function reset(){
       $this->page = 1; 
    }
    
	/**
	 * Executes the SQL query and initializes internal variables
	 *
	 * @access public
	 * @return resource
	 */
	function paginate($custom_query = null) {
		//Check for valid mysql connection
		if (! $this->model) {
		  if ($this->debug) die("Model is missing<br />");
		 return false;
		}		
        global $db;
        $all_rs = 0;
        $rs = false;
        
        if($custom_query != null){
          $result = $db->query($custom_query);
          if(!$result) die($db->error);   
          $this->total_rows = $result->num_rows;
        }
		else{
		 $all_rs = $this->model->findWhere($this->filters, $this->sortBy);
         $this->total_rows = sizeof($all_rs);
        } 
        
		
		
		//Return FALSE if no rows found
		if ($this->total_rows == 0) {
			if ($this->debug) echo "Query returned zero rows.";
		 return FALSE;
		}
		
		//Max number of pages
		$this->max_pages = ceil($this->total_rows / $this->rows_per_page );
		if ($this->links_per_page > $this->max_pages) {
			$this->links_per_page = $this->max_pages;
		}
		
		//Check the page value just in case someone is trying to input an aribitrary value
		if ($this->page > $this->max_pages || $this->page <= 0) {
			$this->page = 1;
		}
		
		//Calculate Offset
		$this->offset = $this->rows_per_page * ($this->page - 1);
		
		if($custom_query != null){
		  $result = $db->query($custom_query." LIMIT {$this->offset},{$this->rows_per_page}");
          if(!$result) die($db->error);
          $rs = $this->model->map_result_set($result); 
		}
        else
		 $rs = $this->model->findWhere($this->filters, $this->sortBy, array("offset" => $this->offset , "count" => $this->rows_per_page));
		
        if (! $rs) {
		  if ($this->debug) echo "Pagination query failed. Check your query.<br /><br />Error Returned: " . mysql_error();
		 return false;
		}
	 return $rs;
	}
	
	/**
	 * Display the link to the first page
	 *
	 * @access public
	 * @param string $tag Text string to be displayed as the link. Defaults to 'First'
	 * @return string
	 */
	function renderFirst($tag = 'First') {
		if ($this->total_rows == 0)
			return FALSE;
		
		if ($this->page == 1) {
			return "$tag ";
		} else {
			return '<a href="' . $this->php_self . '?page=1&' . $this->append . '">' . $tag . '</a> ';
		}
	}
	
	/**
	 * Display the link to the last page
	 *
	 * @access public
	 * @param string $tag Text string to be displayed as the link. Defaults to 'Last'
	 * @return string
	 */
	function renderLast($tag = 'Last') {
		if ($this->total_rows == 0)
			return FALSE;
		
		if ($this->page == $this->max_pages) {
			return $tag;
		} else {
			return ' <a href="' . $this->php_self . '?page=' . $this->max_pages . '&' . $this->append . '">' . $tag . '</a>';
		}
	}
	
	/**
	 * Display the next link
	 *
	 * @access public
	 * @param string $tag Text string to be displayed as the link. Defaults to '>>'
	 * @return string
	 */
	function renderNext($tag = 'Next') {
		if ($this->total_rows == 0)
			return FALSE;
		
		if ($this->page < $this->max_pages) {
			return '<a href="' . $this->php_self . '?page=' . ($this->page + 1) . '&' . $this->append . '">' . $tag . '</a>';
		} else {
			return "&nbsp;";
		}
	}
	
	/**
	 * Display the previous link
	 *
	 * @access public
	 * @param string $tag Text string to be displayed as the link. Defaults to '<<'
	 * @return string
	 */
	function renderPrev($tag = 'Previous') {
		if ($this->total_rows == 0)
			return FALSE;
		
		if ($this->page > 1) {
			return ' <a href="' . $this->php_self . '?page=' . ($this->page - 1) . '&' . $this->append . '">' . $tag . '</a>';
		} else {
			return "&nbsp;";
		}
	}
	
	/**
	 * Display the page links
	 *
	 * @access public
	 * @return string
	 */
	function renderNav($prefix = '<span class="page_link">', $suffix = '</span>') {
		if ($this->total_rows == 0)
			return "";
		
		$batch = ceil($this->page / $this->links_per_page );
		$end = $batch * $this->links_per_page;
		if ($end == $this->page) {
			//$end = $end + $this->links_per_page - 1;
		//$end = $end + ceil($this->links_per_page/2);
		}
		if ($end > $this->max_pages) {
			$end = $this->max_pages;
		}
		$start = $end - $this->links_per_page + 1;
		$links = '';
		
		for($i = $start; $i <= $end; $i ++) {
		    $link = ($i%$this->links_per_page != 1) ? " | $i " : " $i ";
			if ($i == $this->page) {
				$links .= $prefix . $link . $suffix;
			} else {
				$links .= ' ' . $prefix . '<a href="' . $this->php_self . '?page=' . $i . '&' . $this->append . '">' . $link . '</a>' . $suffix . ' ';
			}
		}
		
		return $links;
	}
	
	/**
	 * Display full pagination navigation
	 *
	 * @access public
	 * @return string
	 */
	function renderFullNav() {
		return $this->renderPrev() . '&nbsp;' . $this->renderNav() . '&nbsp;' . $this->renderNext();
	}
	
	/**
	 * Set debug mode
	 *
	 * @access public
	 * @param bool $debug Set to TRUE to enable debug messages
	 * @return void
	 */
	function setDebug($debug) {
		$this->debug = $debug;
	}
}
?>
