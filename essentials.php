<?php
/***********************essentials.php******************************
     The opensource php library
	 Contribute to this project here :https://github.com/jinujd/essentials.php
	 
*/
    class sql_interface {//acts as an interface to the sql server
	    private 
		    $db_uname ,/*database username*/
		    $db_pwd,/*database password*/
			$db_host,/*database host*/
			$table,/*selected table*/
			$db,/*selected database*/
			$col,/*selected column*/
			$resut,/*stores result of a query*/
			$handle;/*connection handle*/
			
	    function __construct($dname='admin',$dpwd='admin',$host = 'localhost') {//constructor
		    $this->db_uname = $dname;
			$this->db_pwd = $dpwd;
			$this->db_host = $host;
		}
		private function ensureConnection() {//ensures that connection to the sql server is established
		    if(!isset($this->handle)) {
			    $this->connect();
			}
		}
		private function doQuery($query,$errMsg = '--Error occured--  <br />') {//executes a mysql query $query,if it fails ,shows the error message $errMsg  along with my_sql_error
         //echo $query;		 
		 if( !( $this->result =  mysql_query($query,$this->handle) )) {
		       die($errMsg.mysql_error());
			}
	  }
		public function connect() {//connecting  the sql server
		    $con = mysql_connect($this->db_host,$this->db_uname,$this->db_pwd);
            if (!$con) {
			    return false;
                //die('Could not connect: ' . mysql_error());
		     }
		     else {
			     $this->handle = $con;
		         return $con;
			 }
		}
		public function disconnect() {
		    if($this->handle) {
			    mysql_close($this->handle);
			}
		}
		public function reflect($arg) {//test whether essentials is loaded,prints the supplied argument
		    echo($arg);
		}
		public function add_db($dbName) {//creates a database if it does not exist and selects it
		    $this->ensureConnection(); 
			$this->doQuery("CREATE DATABASE IF NOT EXISTS ".$dbName,'Could not create database: ' );
			$this->db($dbName);
			return $this;
		 }
		 public function drop_db($dbName) {
		    $this->ensureConnection(); 
			$this->doQuery("DROP DATABASE ".$dbName,'Could not drop database: ' );
			return true;
		 }
		public function db($dbName) {//selects database,
		    $this->ensureConnection(); 
		    if(mysql_select_db($dbName)) {
			    $this->db = $dbName;
			   $ret = $this;
			}else {
			    $ret =  false;
			}
			return $ret;
		}
		public function get_selected_table() {
		    return $this->table;
		}
		public function get_selected_db() {
		    return $this->db;
		}
		public function get_db_handle() {
		    return $this->handle;
		}
		public function table($tableName) {//selects a table
		    $this -> table = $tableName;
			return $this;
		}
		public function add_table($tableName,$details) {/*create a table if it does not exist with table name = $tableName and details as $details ,details are passed 
           ex:obj.add_table("mytable","id INT ,name TEXT,PRIMARY KEY(id)")
		   
	   */		
	       $this->doQuery("CREATE TABLE IF NOT EXISTS ".$tableName."(".$details.")");
			$this->table($tableName);
			return $this;
	    }
		public function add_tables($tables){/*Create the tables given inside the associative array(<tableName>=><details>,...)*/
		    foreach($tables as $table => $details) {
		        $this->add_table($table,$details);
		    }
		}
		public function create_view($viewName,$result){//creates a view with name  = $viewName  and ,columns in the result $result
		    $this->doQuery("CREATE VIEW ".$viewName." AS ".$result);
			$this->table($viewName);
			return $this;
		}
		public function select($columns,$conditions = "0") {
		    /*
			    selects record from table
				$columns = [] ,stroes the column names toselect
				$conditions,stores the conditions to select ,ex:id > 10 ORDER BY name
			*/
			$query = "SELECT ".implode(' , ',$columns)." FROM ".$this->table." ";
			if($conditions != "0") {
			   $query = $query.$conditions;
			}
			$this->doQuery($query);
			return $this;
		}
		public function fetch() {//returns the first row from the result set as an array if there is more than one fields ,otherwise value of  the selected field,and moves the pointer to the next row,if it fails it returns false
		 if($this->result) {
		     $ret =  mysql_fetch_row($this->result);
		}
		else {
		    $ret  = false;
		}
		   if($ret) {
			    $ret = sizeof($ret) > 1 ?$ret:$ret[0];
			    return $ret;
			}
			else {
			    return false;
			}
		}
		public function add_record($values) {
		    /*adds a row to the selected table
			   $values => []
			   it can be of two types 
			   1) if it is a normal array ,each element are considered as values of the column names in the order they appear in the database
			   2) it can be specified as an associative array as ["<column name>" => value,"<column name>" => value]
			*/
			if(isset($this->table)) {
			    $cols =" ";
			    if(is_associative($values)) {
				       $cols = array_keys($values);
					   $cols = "(".implode(' , ',$cols).")";
					   $values = array_values($values);
			   }
			   $values = "(".implode(' , ',$values).")";
			   $this->doQuery("INSERT INTO ".$this->table.$cols." VALUES".$values);
			}
			else {
			    die('<br />Table not selected');
			}
			return $this;
		}
		public function no_of_records() {//returns  number of records matched
		    return mysql_num_rows($this->result);
		}
		public function drop_record($condition = "0") {
		/*
		   deletes a record satisfying the condition $condition which is specified as a string
		*/
		     if($condition != "0") {
			       $this->doQuery("DELETE FROM ".$this->table." WHERE ".$condition);
		    }
	        return $this;
		}
		public function incr_col($col,$incr=1,$condition="0") {//increments a column  increment incr
		    $this->update_record(array($col=>$col."+".$incr),$condition);
		     
		}
	 public function update_record($values,$condition = "0") {
	     /*updates record in a table,
		    $values = [],associative array in the format [<column name>,<value>,<column name>,<value>,...]
		 */
		 $query = "UPDATE ".$this->table." SET ";
		 $vals = array();
		 $i = 0;
		 foreach($values as $key => $value) {
		     $vals[$i++] = $key." = ".$value; 
		 }
		 $values = implode(' , ',$vals);
		 $query = $query.$values;
		 if( $condition != "0" ) {
		    $query = $query." WHERE ".$condition;
		 }
		 $this->doQuery($query);
		 return $this;
	 }
     }
	function is_associative($arr) {//checks whether an array is associative or not
	    $arr = array_keys($arr);
		return $arr !== array_keys($arr);
	}
    function drop_cookie($cookie) {
	    if(!is_array($cookie)) {
	       if($_COOKIE[$cookie]) {
		        setcookie($cookie, "", time() - 3600);
			    $ret = true;
		    } else {
		        $ret = false;
		    }
		} else {
		    $i = 0;
		    $len = sizeof($cookie);
			$ret = true;
			while($i < $len) {
			    $ret = $ret&&drop_cookie($cookie[$i++]);
			}
		 }
	    return $ret;
	}
	function isset_cookie($cookie) {/*
	      format 1:returns true if the COOKIE variable $cookie is set.Otherwise returns false.
	      format 2:returns true if all COOKIE variables whose name is stored in the array() $cookie are set , otherwise false 
	*/
	    if(!is_array($cookie)) {
		   $ret = isset($_COOKIE[$cookie]);
		} else {
	       $ret = true;
	       $i = 0;
		   $len = sizeof($cookie);
		   while($i<$len) {
		       $ky = $cookie[$i++];
		       $ret = $ret&&isset($_COOKIE[$ky]);
			   if(!$ret) {
			       break;
			   }
		   }
		}
	    return  $ret;
	}
	function isset_post($var) {/*
	      format 1:returns true if the POST variable $var is set.Otherwise returns false.
	      format 2:returns true if all POST variables whose name is stored in the array() $var are set , otherwise false 
	*/
	    if(!is_array($var)) {
		   $ret = isset($_POST[$var]);
		} else {
	       $ret = true;
	       $i = 0;
		   $len = sizeof($var);
		   while($i<$len) {
		       $ky = $var[$i++];
		       $ret = $ret&&isset($_POST[$ky]);
			   if(!$ret) {
			       break;
			   }
		   }
		}
	    return  $ret;
	}
	function isset_get($var) {/*
	      format 1:returns true if the GET variable $var is true.Otherwise returns false.
	      format 2:returns true if all GET variables whose name is stored in the array() $var is true , otherwise false 
	*/
	    if(!is_array($var)) {
		   $ret = isset($_GET[$var]);
		} else {
	       $ret = true;
	       $i = 0;
		   $len = sizeof($var);
		   while($i<$len) {
		       $ky = $var[$i++];
		       $ret = $ret&&isset($_GET[$ky]);
			   if(!$ret) {
			       break;
			   }
		   }
		}
	    return  $ret;
	}
	function php_code($str) {//returns php code from a string str,by adding <?php and ?/> at ends of string
	    $ret = "<?php 
		".$str."
		?>";
		return $ret;
	}
	function db_str($str){//adds quotes at ends  of a string.
	    $str = '"'.str_replace('"','\"',$str).'"';
		return $str;
	}
	function line_input($attributes = array()) {
	 //returns html for text box.attributes is array(attribute1 => value,attribute2 => value....)
	 $attributes['type'] = 'text';
	 $ret = html(array('input'=>array('attributes' => $attributes)));
	 return $ret;
	}
	function text_input($attributes = array('value'=>'')) {//returns html for a textarea with attributes array(attr1=>val,...)...attribute named value is innerHTML of textarea
	    $content = $attributes['value'];
	    unset($attributes['value']);
	    return html(array('textarea'=>array('content'=>$content,'attributes' => $attributes)));
	}
	function button($attributes = array()) {//returns html of a button with attributes in array(attr1=>val1,...)
	    $attributes['type'] = 'button';
        return html(array('input'=>array('attributes'=>$attributes)));	
	}
	function submit_button($attributes = array()) {//returns html of a submit  button with attributes in array(attr1=>val1,...)
	    $attributes['type'] = 'submit';
        return html(array('input'=>array('attributes'=>$attributes)));	
	}
	function reset_button($attributes = array()) {//returns html of a reset button with attributes in array(attr1=>val1,...)
	    $attributes['type'] = 'reset';
        return html(array('input'=>array('attributes'=>$attributes)));	
	}
	function password($attributes = array()) {//returns html of a password field with attributes in array(attr1=>val1,...)
	    $attributes['type'] = 'password';
        return html(array('input'=>array('attributes'=>$attributes)));	
	}
	function file_input($attributes = array()) {//returns html of a file upload field with attributes in array(attr1=>val1,...)
	    $attributes['type'] = 'file';
        return html(array('input'=>array('attributes'=>$attributes)));	
	}
	function checkbox($attributes = array()) {//returns html for checkbox with attributes as array(attr1=>val1,...),checked attribute set to 1 => checkbox is checked
	    if(isset($attributes['checked'])) {
		    $checked = $attributes['checked'];
		    if($checked  == 1) {
			    $attributes['checked'] = 'checked';
		    } else {
			    unset($attributes['checked']);
			}
		}
	    $attributes['type'] = 'checkbox';
	    return html(array('input'=>array('attributes'=>$attributes)));
	}
	function radiobutton($attributes = array()) {//returns html for radio button with attributes as array(attr1=>val1,...),checked attribute set to 1 => radio button is checked
	    if(isset($attributes['checked'])) {
		    $checked = $attributes['checked'];
		    if($checked  == 1) {
			    $attributes['checked'] = 'checked';
		    } else {
			    unset($attributes['checked']);
			}
		}
	    $attributes['type'] = 'radio';
	    return html(array('input'=>array('attributes'=>$attributes)));
	}
	function select($options = array(),$attributes = false) {//returns html for select element with options as array options,attributes as attributes(attr1=>val1,...),checked attribute set to 1 => radio button is checked
	  //options can be either normal array or associative array.If it is normal array innerHTML and value of option elements will be same,otherwise it will be as array(value1=>innerHTML1,...) 
	   $optionsList = array();
		$i = 0;
		$len = sizeof($options);
		if(!is_associative($options)) {
		    while($i < $len) {
		        $value = $options[$i++];
			    $optionsList['option_'.$i] = array('content'=>$value,'attributes'=>array('value'=>$value));
		    }
		} else {
		    foreach($options as $actualValue => $displayedValue) {
			    $optionsList['option_'.$i++] = array('content'=>$displayedValue,'attributes'=>array('value'=>$actualValue));
			    
			}
		}
		$options = html($optionsList);
		$select = html(array(
		              'select' => array(
					                  'content'=>$options,
									  'attributes'=>$attributes
									  )
						));
	    return $select;
	}
	function form() {
	/*
	    returns html for a form,all arguments except last arguments are innerHtml of that inside form(possibly result of any input functions like line_input,text_input etc.)
		last argument is array(
		    'url' => the url to which form should be submitted..not necessary,
			'method' =>GET/POST,default is  GET..not necessary,
			'attributes' => array(attr1=>val1,attr2=>val2,...)//attributes for the form tag,..not necessary,
			'submitButton' =>true/false,true=>submit button is shown..,..not necessary.,submit button is included if this key is not given,
			'resetButton' =>true/false,true=>reset button is shown.,..not necessary,reset button is not  included if this key is not given,
		)
	*/
	    $args = func_get_args();
		$i = 0;
		$len = sizeof($args);
		$inputs ='';
		while($i < $len-1) {
		    $inputs = $inputs.$args[$i++];
			
		}
		if(isset($args[$len-1])) {
		    $attributes = $args[$len-1];
		    $url = isset($attributes['url'])?$attributes['url']:'';
			unset($attributes['url']);
		    $submitEnabled = isset($attributes['submitButton'])&&$attributes['submitButton']==false?false:true;
			unset($attributes['submitButton']);
		    $resetEnabled = isset($attributes['resetButton'])&&$attributes['resetButton']==true?true:false;
			unset($attributes['resetButton']);
		} else {
			$url = './';
			$submitEnabled = true;
			$resetEnabled = false;
		}
		$attributes['action'] = $url;
		if($submitEnabled) { 
		    $inputs = $inputs.submit_button(array());
		}
		if($resetEnabled) { 
		    $inputs = $inputs.reset_button(array());
		}
		$ret = html(array('form'=>array('content'=>$inputs,'attributes'=>$attributes)));
		return $ret;
	}
	
    function html($tags) {//generate html tags from array named tags 
	/*tags[tagName] = array [
	                content => innerHtml,
	                attributes => array [
					    attribute1=>value1,attr2=>value ...
					]
				]
				tags with same name should be indicated as $tagNAme_1,$tagName_2 etc
	*/
	    $autoCloseTags = array( 
		    'area'=>1,
            'base'=>1,
            'br'=>1,
            'col'=>1,
            'command'=>1,
            'embed'=>1,
            'hr'=>1,
            'img'=>1,
            'input'=>1,
            'keygen'=>1,
            'link'=>1,
            'meta'=>1,
            'param'=>1,
            'source'=>1,
            'track'=>1,
            'wbr'=>1
        );
		$html = array();
		$len = sizeof($tags);
		foreach($tags as $tagName => $details) {
		    $tagName = strtolower($tagName);
			$tagName = explode('_',$tagName);
			$tagName = $tagName[0];
			$attrList = '';
			if(isset($details['attributes'])) {
			    foreach($details['attributes'] as $attr => $value) {
			        $attrList = $attrList.' '.$attr.'='.db_str($value);
			    }
			}
			$tag = '<'.$tagName.$attrList;
			if(!isset($autoCloseTags[$tagName])) {
			    $content = isset($details['content'])?$details['content']:'';
			    $tag = $tag.'>'.$content.'</'.$tagName.'>';
			} else {
			      $tag = $tag.' />';
			}
			array_push($html,$tag);
		}
		$ret = implode('',$html);
		return $ret;
	}
?>
