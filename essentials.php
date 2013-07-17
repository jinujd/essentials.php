<?php
/***********************essentials.php******************************
     The opensource php library
	 Author: Jinu Joseph Daniel
	 jinujosephdaniel@gmail.com
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
			public function doQuery($query,$errMsg = '--Error occured--  <br />') {//executes a mysql query $query,if it fails ,shows the error message $errMsg  along with my_sql_error
			 //echo $query;		 
			 if( !( $this->result =  mysql_query($query,$this->handle) )) {
				   die($errMsg.mysql_error().'<br />Query was : '.$query);
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
	function line_input($attributes = array('class'=>'line_input')) {
	 //returns html for text box.attributes is array(attribute1 => value,attribute2 => value....)
	 $attributes['type'] = 'text';
	 $ret = html(array('input'=>array('attributes' => $attributes)));
	 return $ret;
	}
	function  label($content='',$attributes = array('class'=>'label')) {//returns html for a label tag with content as first argument attributes array(attr1=>val,...)attribute named value is innerHTML of label
		return html(array('label'=>array('content'=>$content,'attributes'=>$attributes)));    
	}
	function text_input($attributes = array('value'=>'','class'=>'text_input')) {//returns html for a textarea with attributes array(attr1=>val,...)...attribute named value is innerHTML of textarea
	    $content = $attributes['value'];
	    unset($attributes['value']);
	    return html(array('textarea'=>array('content'=>$content,'attributes' => $attributes)));
	}
	function button($attributes = array('class'=>'button')) {//returns html of a button with attributes in array(attr1=>val1,...)
	    $attributes['type'] = 'button';
        return html(array('input'=>array('attributes'=>$attributes)));	
	}
	function submit_button($attributes = array('class'=>'submit_button')) {//returns html of a submit  button with attributes in array(attr1=>val1,...)
	    $attributes['type'] = 'submit';
        return html(array('input'=>array('attributes'=>$attributes)));	
	}
	function reset_button($attributes = array('class'=>'reset_button')) {//returns html of a reset button with attributes in array(attr1=>val1,...)
	    $attributes['type'] = 'reset';
        return html(array('input'=>array('attributes'=>$attributes)));	
	}
	function password($attributes = array('class'=>'password')) {//returns html of a password field with attributes in array(attr1=>val1,...)
	    $attributes['type'] = 'password';
        return html(array('input'=>array('attributes'=>$attributes)));	
	}
	function file_input($attributes = array('class'=>'file_input')) {//returns html of a file upload field with attributes in array(attr1=>val1,...)
	    $attributes['type'] = 'file';
        return html(array('input'=>array('attributes'=>$attributes)));	
	}
	function checkbox($attributes = array('class'=>'checkbox')) {//returns html for checkbox with attributes as array(attr1=>val1,...),checked attribute set to 1 => checkbox is checked
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
	function radio_button($attributes = array('class'=>'radio_button')) {//returns html for radio button with attributes as array(attr1=>val1,...),checked attribute set to 1 => radio button is checked
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
	function select($options = array(),$attributes = array('class'=>'select')) {//returns html for select element with options as array options,attributes as attributes(attr1=>val1,...),checked attribute set to 1 => radio button is checked
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
			'submitAttributes' =>attributes for submit button,
			'resetAttributes' =>attributes for reset button,
		)
	*/
	    $args = func_get_args();
		$i = 0;
		$len = sizeof($args);
		$inputs ='';
		while($i < $len-1) {
		    $inputs = $inputs.$args[$i++];
		}
		if($len>0) {
		    $attributes = $args[$len-1];
		    $url = isset($attributes['url'])?$attributes['url']:'';
			unset($attributes['url']);
		    $submitEnabled = isset($attributes['submitButton'])&&$attributes['submitButton']==false?false:true;
			unset($attributes['submitButton']);
		    $resetEnabled = isset($attributes['resetButton'])&&$attributes['resetButton']==true?true:false;
			unset($attributes['resetButton']);
			if(isset($attributes['submitAttributes'])) {
			   $submitAttributes = $attributes['submitAttributes'];
			   if(!isset($submitAttributes['class'])) {
			       $submitAttributes['class'] = 'submit_button';
			   }
			   unset($attributes['submitAttributes']);
			} else {
			    $submitAttributes = array('class'=>'submit_button');
			}
			if(isset($attributes['resetAttributes'])) {
			   $resetAttributes = $attributes['resetAttributes'];
			   if(!isset($resetAttributes['class'])) {
			       $resetAttributes['class'] = 'reset_button';
			   }
			   unset($attributes['resetAttributes']);
			} else {
			    $resetAttributes = array('class'=>'reset_button');
			}
		} else {
			$url = './';
			$submitEnabled = true;
			$resetEnabled = false;
		}
		$attributes['action'] = $url;
		if($submitEnabled) { 
		    $inputs = $inputs.submit_button($submitAttributes);
		}
		if($resetEnabled) { 
		    $inputs = $inputs.reset_button($resetAttributes);
		}
		$ret = html(array('form'=>array('content'=>$inputs,'attributes'=>$attributes)));
		return $ret;
	}
	function div() {//encloses all arguments in  a div tag with class attribute as last argument and returns the html
	    $args = func_get_args();
		$i = 0;
		$len = sizeof($args);
		if($len>0&&is_array($args[$len-1])) {
		    $attributes = $args[$len-1];
			$limit = $len - 1;
		} else {
		    $attributes = array();
			$limit = $len;
		}
		$inputs ='';
		while($i < $limit) {
		    $inputs = $inputs.$args[$i++];
	    }
		return html(array('div'=>array('content'=>$inputs,'attributes'=>$attributes)));
		
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
function send_post( $url, $data ,$cookies='',$extraHeaders = '') //sends data array(param=>val,...) to the page $url in post method and returns the reply string
{
    $post    = http_build_query( $data );
	$header =  "Accept-language: en\r\n".
			    "Content-Type: application/x-www-form-urlencoded\r\n" . 
				"Content-Length: " . strlen( $post ) . 
				"\r\nUser-agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)\r\n";
	
	if($extraHeaders) {
	    foreach($extraHeaders as  $headerN  => $val) {
		    $header = $header.$headerN.': '.$val."\r\n";
		}
	    
	}
	if($cookies) {
	    $cookieArr = array();
	    foreach($cookies as  $cookie  => $value) {
		    array_push($cookieArr,$cookie.'='.$value);
		}
		$cookieStr = "Cookie: ".implode('; ',$cookieArr)."\r\n";
		$header = $header.$cookieStr;
	}
    $context = stream_context_create( array(
         "http" => array(
             "method" => "POST",
            "header" => $header,
            "content" => $post 
        ) 
    ) );
    $page    = file_get_contents( $url, false, $context );
    return $page;
}
function curl_post($url,$params) {
	$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,"; 
	$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5"; 
	$header[] = "Cache-Control: max-age=0"; 
	$header[] = "Connection: keep-alive"; 
	$header[] = "Keep-Alive: 300"; 
	$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7"; 
	$header[] = "Accept-Language: en-us,en;q=0.5"; 
	$header[] = "Pragma: "; //browsers keep this blank. 
	
		$fields_string = "";
		foreach ($params as $key => $value) { $fields_string .= $key . '=' . $value . '&';
		}
		rtrim($fields_string, '&');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3'); 
		//curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_REFERER, 'http://cbseresults.nic.in/class1211/cbse122012.htm'); 
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate'); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
		$data = curl_exec($ch);
		return $data;
}
function time_cmp( $time1, $time2 ) //compares two times in format hour:min:sec(hour => 0=>24) returns 0 if both equal ,1 if time1>time2,-1if time2>time1  
{
    $time1 = explode( ':', $time1 );
    $time2 = explode( ':', $time2 );
    $i     = 0;
    while ( $i++ < 3 ) {
        array_push( $time1, 0 );
        array_push( $time2, 0 );
    } //$i++ < 3
    $ret = 0;
    if ( $time1[ 0 ] > $time2[ 0 ] ) {
        $ret = 1;
    } //$time1[ 0 ] > $time2[ 0 ]
    else if ( $time1[ 0 ] < $time2[ 0 ] ) {
        $ret = -1;
    } //$time1[ 0 ] < $time2[ 0 ]
    else {
        if ( $time1[ 1 ] > $time2[ 1 ] ) {
            $ret = 1;
        } //$time1[ 1 ] > $time2[ 1 ]
        else if ( $time1[ 1 ] < $time2[ 1 ] ) {
            $ret = -1;
        } //$time1[ 1 ] < $time2[ 1 ]
        else {
            if ( $time1[ 2 ] > $time2[ 2 ] ) {
                $ret = 1;
            } //$time1[ 2 ] > $time2[ 2 ]
            else if ( $time1[ 2 ] < $time2[ 2 ] ) {
                $ret = -1;
            } //$time1[ 2 ] < $time2[ 2 ]
        }
    }
    return $ret;
    
}
function get_upload_data($files_arr) { /*
		argument = >$_FILES[<input_name>] ,
		returns array (
					array(
						'name'=>file_1_name,
						'type'=>file_1_type,
						'tmp_name'=>'file_1_tmp_name',
						'size'=>'file_1_size'
					),
					array(
						'name'=>file_2_name,
						'type'=>file_2_type,
						'tmp_name'=>'file_2_tmp_name',
						'size'=>'file_2_size'
					),
					....
					..
					
				)
    
	*/
	$file_arr = array();
	if(is_array($files_arr['name'])) {
	    $no_of_files = sizeof($files_arr['name']);
		$i = 0;
		$attrs = array_keys($files_arr);
		while($i<$no_of_files) {
		    if(trim($files_arr['name'][$i])) {
		        $file = array();
		        foreach($attrs as $attr) {
			       $file[$attr] = $files_arr[$attr][$i];
			    }
			    array_push($file_arr,$file);
			}
		    $i++;
		}
    } else {
	    if(trim($files_arr['name'])) {
	        $file_arr = array($files_arr);
		}
	}
	$files_arr = $file_arr;
    
    return $files_arr;
}
function remove_end_slash($path) {
    $path = str_replace('\\','/',$path);
    $path = rtrim($path,'/');
	return $path;
}
function upload_file($files,$upload_paths,$check_attributes=array('allowed_mime_types'=>'','max_size'=>'','allowed_extentions'=>'')) {
    /*
	    $files => output of get_upload_data() ,
		check_attributes => attributes to be checked passed as an associative array wth the following keys
		key1 => 'allowed_mime_types' = >allowed file extentions.Can be in any of the following formats
		  1. $allowed_mime_types = <ext_name>
		    ex: 'image/png'
		  2.$allowed_mime_types = array(
		                        <mime_type1> =>
								    array('sub_type_1','sub_type_2',....) ,
		                        <mime_type2> =>
								    array('sub_type_1','sub_type_2',....) ,
								
							);
							 ex: array('image'=>array('jpg','pjpeg','x-png','png','gif'))
 
		  3.$allowed_mime_types = array(<mime_type1>,<mime_type2>,....) 
		      mime_type can be 'image',''...
			  ,
	     key2 =>'max_size' => restricts maximum size in kb , for the files to this value ,this is affected by your php.ini settings
		 key3 => 'allowed_extentions'=> array()
		      ex: $check_attributes['allowed_extentions'] = array('jpg','gif');
		-----------------------
	    $upload_paths => paths to which the files are to be uploaded
			can be any of the following formats
			1.$upload_paths = <dir_path> =>all files will be uploaded to the dir_path
			2.$upload_paths = array(<dir_path0>,<dir_path1>,<dir_path2>,...) => $files[0] will be uploaded to dir_path0,$files[1] to dir_path1 and so on
			                 if n files are there and only i paths are given ,path of  remainig n-i file will be same as that of i th file
		
		Returns array($file_1_status,$file_2_status,.....)
		$file_i_status can be
			1 => all files successfully uploaded
			0 => if the file already exists
		   -1 => if the file  has errors
		   -2 => if upload path for the file is not existing
		   -3 => if file mime type is not allowed
		   -4 => if file extention is not allowed
		   -5 => if the file size exeeds maximum size
	*/
	$check_attributes_dummy = array('allowed_mime_types'=>'','max_size'=>'','allowed_extentions'=>array());
	foreach($check_attributes_dummy as $ky => $vl) {
	    if(!isset($check_attributes[$ky])) {
		    $check_attributes[$ky] = $vl;
		}
	}
	$allowed_mime_types=$check_attributes['allowed_mime_types'];
	$max_size = $check_attributes['max_size'];
	$allowed_extentions = $check_attributes['allowed_extentions'];
    
	$mime_types = array(
					'image'=> array('jpg','pjpeg','x-png','png','gif','tiff','jpeg','svg+xml') ,
					'message' => array('http','imdn+xml','partial','rfc822') ,
					'model' => array('example','iges','mesh','vrml','x3d+binary','x3d+vrml','x3d+xml') ,
					'multipart' => array('mixed','alternative','related','form-data','signed','encrypted') ,
					'text' => array('cmd','css','csv','html','javascript','plain','vcard','xml') ,
					'video' => array('mpeg','mp4','ogg','quicktime','webm','x-matroska','x-ms-wmv','x-flv') ,
					'audio' => array('basic','L24','mp4','mpeg','ogg','vorbis','vnd.rn-realaudio','vnd.wave','webm') ,
					'application' => array('atom+xml','ecmascript','EDI-X12','EDIFACT','json','javascript','octet-stream','ogg','pdf','postscript','rdf+xml','rss+xml','soap+xml','font-woff','xhtml+xml','xml','xml-dtd','xop+xml','zip','gzip')
				);
	$allowed_mime_types_dummy = array();
	if(!is_array($allowed_mime_types)){
		if(isset($mime_types[$allowed_mime_types])) {
		    $mime_sub_types = $mime_types[$allowed_mime_types];
		    foreach($mime_sub_types as $type) {
		       array_push($allowed_mime_types_dummy,$allowed_mime_types.'/'.$type);
		    }
		} else {
		    $allowed_mime_types_dummy = array($allowed_mime_types);
			$allowed_mime_types_parts = explode('/',$allowed_mime_types);
			array_push($allowed_mime_types_parts,'');
		}

	} else if(!is_associative($allowed_mime_types)) {
		foreach($allowed_mime_types as $mime_type) {
		    $mime_sub_types = $mime_types[$mime_type];
		    foreach($mime_sub_types as $type) {
		       array_push($allowed_mime_types_dummy,$mime_type.'/'.$type);
		    }
		}
	} else {
	    foreach($allowed_mime_types as $mime_type => $mime_sub_types) {
		    foreach($mime_sub_types as $type) {
		       array_push($allowed_mime_types_dummy,$mime_type.'/'.$type);
		    }
		}
	}
    $allowed_mime_types = $allowed_mime_types_dummy;
	$max_size = $max_size?$max_size*1000:200000;
	
	if(!is_array($upload_paths)) {
	    $upload_paths = array($upload_paths);
	}
	$i = 0;
	foreach($upload_paths as &$path) {
	    $path = remove_end_slash($path);
		if(!file_exists($path)) {
		    $path  = false;
		}
		$i++;
	}
	$no_of_files = sizeof($files);
	while($i < $no_of_files) 
		$upload_paths[$i++] = $path;
	
	$i = 0;
	$ret = array();
	foreach($files as $file) {
	   $upload_path = $upload_paths[$i];
	   if($upload_path !== false) {
	       if(!$file['error']>0) {
		           $extention = explode('.',$file['name']);
				   array_push($extention,'');//to accomodate file without an extention.ie. for files having  no dot in name
				   $extention = strtolower($extention[1]);
				   $allowed_mime_types = array_filter($allowed_mime_types);
			       if(empty($allowed_mime_types) ||in_array($file['type'],$allowed_mime_types)) {
				      $allowed_extentions = array_filter($allowed_extentions);
				      if(empty($allowed_extentions)||in_array($extention,$allowed_extentions)) {
				          if(!($file['size']>$max_size)) {
					           $rt = 1;
					       } else {
					           $rt = -5;
					       }
					   } else {
					       $rt = -4;
					   }
				   } else {
				       $rt = -3;
				   }
		   } else {
		       $rt = -1;
		   }
	   } else {
	       $rt = -2;
	   }
	   if($rt>0) {
	       $file_path = $upload_path.'/'.$file['name'];
		   if(!file_exists($file_path)) {
		       move_uploaded_file($file["tmp_name"],$file_path);
		   } else {
		       $rt = 0;
		   }
	   }
	   $ret[$i] = $rt;
	   $i++;
	}
	return $ret;
}
?>
