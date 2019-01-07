<?php
defined("DUPXABSPATH") or die("");

/**
 * Walks every table in db that then walks every row and column replacing searches with replaces
 * large tables are split into 50k row blocks to save on memory.
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2 Full Documentation
 *
 * @package SC\DUPX\UpdateEngine
 *
 */
class DUPX_UpdateEngine
{

    /**
     *  Used to report on all log errors into the installer-txt.log
     *
     * @param string $report The report error array of all error types
     *
     * @return string Writes the results of the update engine tables to the log
     */
    public static function logErrors($report)
    {
        if (!empty($report['errsql'])) {
            DUPX_Log::info("--------------------------------------");
            DUPX_Log::info("DATA-REPLACE ERRORS (MySQL)");
            foreach ($report['errsql'] as $error) {
                DUPX_Log::info($error);
            }
            DUPX_Log::info("");
        }
        if (!empty($report['errser'])) {
            DUPX_Log::info("--------------------------------------");
            DUPX_Log::info("DATA-REPLACE ERRORS (Serialization):");
            foreach ($report['errser'] as $error) {
                DUPX_Log::info($error);
            }
            DUPX_Log::info("");
        }
        if (!empty($report['errkey'])) {
            DUPX_Log::info("--------------------------------------");
            DUPX_Log::info("DATA-REPLACE ERRORS (Key):");
            DUPX_Log::info('Use SQL: SELECT @row := @row + 1 as row, t.* FROM some_table t, (SELECT @row := 0) r');
            foreach ($report['errkey'] as $error) {
                DUPX_Log::info($error);
            }
        }
    }

    /**
     *  Used to report on all log stats into the installer-txt.log
     *
     * @param string $report The report stats array of all error types
     *
     * @return string Writes the results of the update engine tables to the log
     */
    public static function logStats($report)
    {
        if (!empty($report) && is_array($report)) {
            $stats = "--------------------------------------\n";
            $srchnum = 0;
            foreach ($GLOBALS['REPLACE_LIST'] as $item) {
                $srchnum++;
                $stats .= sprintf("Search{$srchnum}:\t'%s' \nChange{$srchnum}:\t'%s' \n", $item['search'], $item['replace']);
            }
            $stats .= sprintf("SCANNED:\tTables:%d \t|\t Rows:%d \t|\t Cells:%d \n", $report['scan_tables'], $report['scan_rows'], $report['scan_cells']);
            $stats .= sprintf("UPDATED:\tTables:%d \t|\t Rows:%d \t|\t Cells:%d \n", $report['updt_tables'], $report['updt_rows'], $report['updt_cells']);
            $stats .= sprintf("ERRORS:\t\t%d \nRUNTIME:\t%f sec", $report['err_all'], $report['time']);
            DUPX_Log::info($stats);
        }
    }

    /**
     * Returns only the text type columns of a table ignoring all numeric types
     *
     * @param obj $conn A valid database link handle
     * @param string $table A valid table name
     *
     * @return array All the column names of a table
     */
    public static function getTextColumns($conn, $table)
    {
        $type_where = "type NOT LIKE 'tinyint%' AND ";
        $type_where .= "type NOT LIKE 'smallint%' AND ";
        $type_where .= "type NOT LIKE 'mediumint%' AND ";
        $type_where .= "type NOT LIKE 'int%' AND ";
        $type_where .= "type NOT LIKE 'bigint%' AND ";
        $type_where .= "type NOT LIKE 'float%' AND ";
        $type_where .= "type NOT LIKE 'double%' AND ";
        $type_where .= "type NOT LIKE 'decimal%' AND ";
        $type_where .= "type NOT LIKE 'numeric%' AND ";
        $type_where .= "type NOT LIKE 'date%' AND ";
        $type_where .= "type NOT LIKE 'time%' AND ";
        $type_where .= "type NOT LIKE 'year%' ";

        $result = mysqli_query($conn, "SHOW COLUMNS FROM `".mysqli_real_escape_string($conn, $table)."` WHERE {$type_where}");
        if (!$result) {
            return null;
        }
        $fields = array();
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $fields[] = $row['Field'];
            }
        }

        //Return Primary which is needed for index lookup.  LIKE '%PRIMARY%' is less accurate with lookup
        //$result = mysqli_query($conn, "SHOW INDEX FROM `{$table}` WHERE KEY_NAME LIKE '%PRIMARY%'");
        $result = mysqli_query($conn, "SHOW INDEX FROM `".mysqli_real_escape_string($conn, $table)."`");
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $fields[] = $row['Column_name'];
            }
        }

        return (count($fields) > 0) ? $fields : null;
    }

    /**
     * Begins the processing for replace logic
     *
     * @param mysql $conn The db connection object
     * @param array $list Key value pair of 'search' and 'replace' arrays
     * @param array $tables The tables we want to look at
     * @param array $fullsearch Search every column regardless of its data type
     *
     * @return array Collection of information gathered during the run.
     */
    public static function load($conn, $list = array(), $tables = array(), $fullsearch = false, $fixpartials = false)
    {

        @mysqli_autocommit($conn, false);

        $report = array(
            'scan_tables' => 0,
            'scan_rows' => 0,
            'scan_cells' => 0,
            'updt_tables' => 0,
            'updt_rows' => 0,
            'updt_cells' => 0,
            'errsql' => array(),
            'errser' => array(),
            'errkey' => array(),
            'errsql_sum' => 0,
            'errser_sum' => 0,
            'errkey_sum' => 0,
            'time' => '',
            'err_all' => 0
        );

		function set_sql_column_safe(&$str) {
			$str = "`$str`";
		}

    	// init search and replace array
    	$searchList = array();
    	$replaceList = array();

    	foreach ($list as $item) {
    	    $searchList[] = $item['search'];
    	    $replaceList[] = $item['replace'];
    	}

        $profile_start = DUPX_U::getMicrotime();
        if (is_array($tables) && !empty($tables)) {

            foreach ($tables as $table) {
                $report['scan_tables']++;
                $columns = array();

                // Get a list of columns in this table
                $sql = 'DESCRIBE ' . mysqli_real_escape_string($conn, $table);
                $fields = mysqli_query($conn, $sql);
                if (!$fields)  continue;
                while ($column = mysqli_fetch_array($fields)) {
                    $columns[$column['Field']] = $column['Key'] == 'PRI' ? true : false;
                }

                // Count the number of rows we have in the table if large we'll split into blocks
                $row_count = mysqli_query($conn, "SELECT COUNT(*) FROM `".mysqli_real_escape_string($conn, $table)."`");
                if (!$row_count)  continue;
                $rows_result = mysqli_fetch_array($row_count);
                @mysqli_free_result($row_count);
                $row_count = $rows_result[0];
                if ($row_count == 0) {
                    DUPX_Log::info("{$table}^ ({$row_count})");
                    continue;
                }

                // $page_size = 25000;
                $page_size = 3500;
                $offset = ($page_size + 1);
                $pages = ceil($row_count / $page_size);

                // Grab the columns of the table.  Only grab text based columns because
                // they are the only data types that should allow any type of search/replace logic
                $colList = '*';
                $colMsg = '*';
                if (!$fullsearch) {
                    $colList = self::getTextColumns($conn, $table);
                    if ($colList != null && is_array($colList)) {
                        array_walk($colList, 'set_sql_column_safe');
                        $colList = implode(',', $colList);
                    }
                    $colMsg = (empty($colList)) ? '*' : '~';
                }

                if (empty($colList)) {
                    DUPX_Log::info("{$table}^ ({$row_count})");
                    continue;
                } else {
                    DUPX_Log::info("{$table}{$colMsg} ({$row_count})");
                }

                //Paged Records
                for ($page = 0; $page < $pages; $page++) {
                    $current_row = 0;
                    $start = $page * $page_size;
                    $end = $start + $page_size;
                    $sql = sprintf("SELECT {$colList} FROM `%s` LIMIT %d, %d", $table, $start, $offset);
                    $data = mysqli_query($conn, $sql);

                    if (!$data) {
                        $report['errsql'][] = mysqli_error($conn);
                    }

                    $scan_count = ($row_count < $end) ? $row_count : $end;
                    DUPX_Log::info("\tScan => {$start} of {$scan_count}", 2);

                    //Loops every row
                    while ($row = mysqli_fetch_array($data)) {
                        $report['scan_rows']++;
                        $current_row++;
                        $upd_col = array();
                        $upd_sql = array();
                        $where_sql = array();
                        $upd = false;
                        $serial_err = 0;
                        $is_unkeyed = !in_array(true, $columns);

                        //Loops every cell
                        foreach ($columns as $column => $primary_key) {
                            $report['scan_cells']++;
                            if (!isset($row[$column]))  continue;
                            $edited_data = $data_to_fix = $row[$column];
                            $base64converted = false;
                            $txt_found = false;

                            //Unkeyed table code
                            //Added this here to add all columns to $where_sql
                            //The if statement with $txt_found would skip additional columns -TG
                            if($is_unkeyed && ! empty($data_to_fix)) {
                                $where_sql[] = mysqli_real_escape_string($conn, $column) . ' = "' . mysqli_real_escape_string($conn, $data_to_fix) . '"';
                            }

                            //Only replacing string values
                            if (!empty($row[$column]) && !is_numeric($row[$column]) && $primary_key != 1) {
                                //Base 64 detection
                                if (base64_decode($row[$column], true)) {
                                    $decoded = base64_decode($row[$column], true);
                                    if (self::isSerialized($decoded)) {
                                        $edited_data = $decoded;
                                        $base64converted = true;
                                    }
                                }

                                //Skip table cell if match not found
                                foreach ($list as $item) {
                                    if (strpos($edited_data, $item['search']) !== false) {
                                        $txt_found = true;
                                        break;
                                    }
                                }

                                if (!$txt_found) {
                                    continue;
                                }

                                //Replace logic - level 1: simple check on any string or serlized strings
/*
                                *** fixpartials options become deprecated **/
                                $edited_data = self::searchAndReplaceItems($searchList , $replaceList , $edited_data);


                                                //Replace logic - level 2: repair serialized strings that have become broken
                                // check value without unserialize it
                                if (self::is_serialized_string( $edited_data )) {
                                    $serial_check = self::fixSerialString($edited_data);
                                    if ($serial_check['fixed']) {
                                        $edited_data = $serial_check['data'];
                                    } elseif ($serial_check['tried'] && !$serial_check['fixed']) {
                                        $serial_err++;
                                    }
                                }

                            }

                            //Change was made
                            if ($edited_data != $data_to_fix || $serial_err > 0) {
                                $report['updt_cells']++;
                                //Base 64 encode
                                if ($base64converted) {
                                    $edited_data = base64_encode($edited_data);
                                }
                                $upd_col[] = $column;
                                $upd_sql[] = $column . ' = "' . mysqli_real_escape_string($conn, $edited_data) . '"';
                                $upd = true;
                            }

                            if ($primary_key) {
                                $where_sql[] = $column . ' = "' . mysqli_real_escape_string($conn, $data_to_fix) . '"';
                            }
                        }

                        //PERFORM ROW UPDATE
                        if ($upd && !empty($where_sql)) {
                            $sql	= "UPDATE `{$table}` SET " . implode(', ', $upd_sql) . ' WHERE ' . implode(' AND ', array_filter($where_sql));
							$result	= mysqli_query($conn, $sql);
                            if ($result) {
                                if ($serial_err > 0) {
                                    $report['errser'][] = "SELECT " . implode(', ',
                                            $upd_col) . " FROM `{$table}`  WHERE " . implode(' AND ',
                                            array_filter($where_sql)) . ';';
                                }
                                $report['updt_rows']++;
                            } else {
								$report['errsql'][]	= ($GLOBALS['LOGGING'] == 1)
									? 'DB ERROR: ' . mysqli_error($conn)
									: 'DB ERROR: ' . mysqli_error($conn) . "\nSQL: [{$sql}]\n";
							}

							//DEBUG ONLY:
                            DUPX_Log::info("\t{$sql}\n", 3);

                        } elseif ($upd) {
                            $report['errkey'][] = sprintf("Row [%s] on Table [%s] requires a manual update.", $current_row, $table);
                        }
                    }
                    //DUPX_U::fcgiFlush();
                    @mysqli_free_result($data);
                }

                if ($upd) {
                    $report['updt_tables']++;
                }
            }
        }

        @mysqli_commit($conn);
        @mysqli_autocommit($conn, true);

        $profile_end = DUPX_U::getMicrotime();
        $report['time'] = DUPX_U::elapsedTime($profile_end, $profile_start);
        $report['errsql_sum'] = empty($report['errsql']) ? 0 : count($report['errsql']);
        $report['errser_sum'] = empty($report['errser']) ? 0 : count($report['errser']);
        $report['errkey_sum'] = empty($report['errkey']) ? 0 : count($report['errkey']);
        $report['err_all'] = $report['errsql_sum'] + $report['errser_sum'] + $report['errkey_sum'];
        return $report;
    }

	/**
	 * searches and replaces strings without deserializing
	 * recursion for arrays
	 *
	 * @param array $search
	 * @param array $replace
	 * @param mixed $data
	 *
	 * @return mixed
	 */
	public static function searchAndReplaceItems($search , $replace , $data) {

	    if (empty( $data ) || is_numeric($data) || is_bool($data) || is_callable($data) ) {

		/* do nothing */

	    } else if (is_string($data)) {

		//  Multiple replace string. If the string is serialized will fixed with fixSerialString
		$data = str_replace($search , $replace , $data);

	    } else if (is_array($data)) {

		$_tmp = array();
		foreach ($data as $key => $value) {

		    // prevent recursion overhead
		    if (empty( $value ) || is_numeric($value) || is_bool($value) || is_callable($value) || is_object($data)) {

		        $_tmp[$key] = $value;

		    } else {

		        $_tmp[$key] = self::searchAndReplaceItems($search, $replace , $value, false);

		    }

		}

		$data = $_tmp;
		unset($_tmp);

	    } elseif (is_object($data)) {
		// it can never be an object type
		DUPX_Log::info("OBJECT DATA IMPOSSIBLE\n" , 1);

	    }

	    return $data;

	}

	/**
	 * FROM WORDPRESS
	 * Check value to find if it was serialized.
	 *
	 * If $data is not an string, then returned value will always be false.
	 * Serialized data is always a string.
	 *
	 * @since 2.0.5
	 *
	 * @param string $data   Value to check to see if was serialized.
	 * @param bool   $strict Optional. Whether to be strict about the end of the string. Default true.
	 * @return bool False if not serialized and true if it was.
	 */
	public static function is_serialized_string( $data, $strict = true ) {
	    // if it isn't a string, it isn't serialized.
	    if ( ! is_string( $data ) ) {
	        return false;
	    }
	    $data = trim( $data );
	    if ( 'N;' == $data ) {
	        return true;
	    }
	    if ( strlen( $data ) < 4 ) {
	        return false;
	    }
	    if ( ':' !== $data[1] ) {
	        return false;
	    }
	    if ( $strict ) {
	        $lastc = substr( $data, -1 );
	        if ( ';' !== $lastc && '}' !== $lastc ) {
	            return false;
	        }
	    } else {
	        $semicolon = strpos( $data, ';' );
	        $brace     = strpos( $data, '}' );
	        // Either ; or } must exist.
	        if ( false === $semicolon && false === $brace )
	            return false;
	            // But neither must be in the first X characters.
	            if ( false !== $semicolon && $semicolon < 3 )
	                return false;
	                if ( false !== $brace && $brace < 4 )
	                    return false;
	    }
	    $token = $data[0];
	    switch ( $token ) {
	        case 's' :
	            if ( $strict ) {
	                if ( '"' !== substr( $data, -2, 1 ) ) {
	                    return false;
	                }
	            } elseif ( false === strpos( $data, '"' ) ) {
	                return false;
	            }
	            // or else fall through
	        case 'a' :
	        case 'O' :
	            return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
	        case 'b' :
	        case 'i' :
	        case 'd' :
	            $end = $strict ? '$' : '';
	            return (bool) preg_match( "/^{$token}:[0-9.E-]+;$end/", $data );
	    }
	    return false;
	}

    /**
     * Take a serialized array and unserialized it replacing elements and
     * serializing any subordinate arrays and performing the replace.
     *
     * @param string $from String we're looking to replace.
     * @param string $to What we want it to be replaced with
     * @param array $data Used to pass any subordinate arrays back to in.
     * @param bool $serialised Does the array passed via $data need serializing.
     *
     * @return array    The original array with all elements replaced as needed.
     */
    public static function recursiveUnserializeReplace($from = '', $to = '', $data = '', $serialised = false, &$objArr = array(), $fixpartials = false)
    {
        // some unseriliased data cannot be re-serialised eg. SimpleXMLElements
        try {
            if (is_string($data) && ($unserialized = @unserialize($data)) !== false) {
                $data = self::recursiveUnserializeReplace($from, $to, $unserialized, true, $objArr, $fixpartials);
            } elseif (is_array($data)) {
                $_tmp = array();
                foreach ($data as $key => $value) {
                    $_tmp[$key] = self::recursiveUnserializeReplace($from, $to, $value, false, $objArr, $fixpartials);
                }
                $data = $_tmp;
                unset($_tmp);
            } elseif (is_object($data)) {
                foreach ($objArr as $obj) {
                    if ($obj === spl_object_hash($data)) {
                        DUPX_Log::info("Recursion detected.");
                        return $data;
                    }
                }

                $objArr[] = spl_object_hash($data);
                // RSR NEW LOGIC
                $_tmp = $data;
                if($fixpartials){
                    if(method_exists($data,"getObjectVars")) {
                        $props = $data->getObjectVars();
                    }else{
                        $props = get_object_vars($data);
                    }
                    foreach ($props as $key => $value) {
                        $obj_replace_result = self::recursiveUnserializeReplace($from, $to, $value, false, $objArr, $fixpartials);
                        if(method_exists($_tmp,"setVar")){
                            $property_name = self::cleanPropertyName($_tmp,$key);
                            $_tmp->setVar($property_name,$obj_replace_result);
                        }else{
                            $_tmp->$key = $obj_replace_result;
                        }
                    }
                }else{
                    $props = get_object_vars($data);
                    foreach ($props as $key => $value) {
                        // if ("__PHP_Incomplete_Class_Name" == $key)  continue;
                        // if (isset($_tmp->$key)) {
                            $_tmp->$key = self::recursiveUnserializeReplace($from, $to, $value, false, $objArr);
                        // } else {
                            // $key is like \0
                            /*
                            $int_key = intval($key);
                            if ($key == $int_key && isset($_tmp->$int_key)) {
                                $_tmp->$int_key = self::recursiveUnserializeReplace($from, $to, $value, false, $objArr);
                            } else {
                                throw new Exception('Object key->'.$key.' is not exist');
                            }
                            */
                        // }
                    }
                }
                $data = $_tmp;
                unset($_tmp);
            } else {
                if (is_string($data)) {
                    $data = str_replace($from, $to, $data);
                }
            }

            if ($serialised) {
                return serialize($data);
            }
        } catch (Exception $error) {
            DUPX_Log::info("\nRECURSIVE UNSERIALIZE ERROR: With string\n" . $error, 2);
        } catch (Error $error) {
            DUPX_Log::info("\nRECURSIVE UNSERIALIZE ERROR: With string\n" . print_r($error, true), 2);
        }

        return $data;
    }

    /**
     * Crates a php file containing a quasi-class with
     * the properties of the partial object
     *
     * @param $partialObject __PHP_Incomplete_Class the source object
     * @return string the path to the file
     */
    private static function createClassDefinition($partialObject)
    {
        $object_properties = get_object_vars($partialObject);
        $class_name = '';
        $public_properties = array();
        $private_properties = array();
        $protected_properties = array();


        foreach ($object_properties as $name => $val){
            if($name == "__PHP_Incomplete_Class_Name"){
                $class_name = $val;
            }elseif(isset($class_name) && strpos($name,$class_name) !== false){
                $private_properties[] = str_replace("\0","",str_replace($class_name,"",$name));
            }elseif(strpos($name,"*") !== false){
                $protected_properties[] = str_replace("\0","",str_replace("*","",$name));
            }else{
                $public_properties[] = $name;
            }
        }

        $class_str = "<?php\nclass $class_name{\n";
        $class_str .= "\t//PUBLIC PROPERTIES\n";
        foreach ($public_properties as $public_property){
            $class_str .= "\tpublic $".$public_property.";\n";
        }
        $class_str .= "\t//PROTECTED PROPERTIES\n";
        foreach ($protected_properties as $protected_property){
            $class_str .= "\tprotected $".$protected_property.";\n";
        }
        $class_str .= "\t//PRIVATE PROPERTIES\n";
        foreach ($private_properties as $private_property){
            $class_str .= "\tprivate $".$private_property.";\n";
        }
        $class_str .= "\t//FUNCTION TO SET ALL PROPERTIES\n";
        $class_str .= "\t".'public function setVar($name,$value)';
        $class_str .= "\t{\n";
        $class_str .= "\t\t".'$this->$name = $value;'."\n";
        $class_str .= "\t}\n\n";

//        $class_str .= "\t//FUNCTION TO Get ALL PROPERTIES\n";
//        $class_str .= "\t".'public function getVar($name)';
//        $class_str .= "\t{\n";
//        $class_str .= "\t\t".'return $this->{$name};'."\n";
//        $class_str .= "\t}\n";

        $class_str .= "\t//GET ALL OBJECT VARS\n";
        $class_str .= "\t".'public function getObjectVars()';
        $class_str .= "\t{\n";
        $class_str .= "\t\t".'return get_object_vars($this);'."\n";
        $class_str .= "\t}\n";

        $class_str .= "}";

        $filename = uniqid().".php";
        $path = str_replace("\\","/",dirname(__FILE__)).'/tmp/';
        mkdir($path);

        if(file_put_contents($path.$filename,$class_str) !== false){
            return $path.$filename;
        }else{
            throw new Exception("Couldn't write to ".$path.$filename);
        }
    }

    private static function recursiveClassInclude($data, &$objArr = array()){
        if(is_object($data) && is_a($data, '__PHP_Incomplete_Class')){
            foreach ($objArr as $obj) {
                if($obj == spl_object_hash($data)){
                    DUPX_Log::info("Recursion");
                    return;
                }
            }
            $objArr[] = spl_object_hash($data);
            include self::createClassDefinition($data);
            $props = get_object_vars($data);
            foreach ($props as $key => $value) {
                self::recursiveClassInclude($value,$objArr);
            }
        }elseif(is_array($data)){
            foreach ($data as $key => $value) {
                self::recursiveClassInclude($value,$objArr);
            }
        }
    }

    private static function cleanPropertyName($obj,$str){
        $class_name = get_class($obj);
        return str_replace("*", "", str_replace("\0","",str_replace($class_name,"",$str)));
    }

    private static function deleteTempClassFiles(){
        $path = str_replace("\\","/",dirname(__FILE__)).'/tmp/*';
        $files = glob($path); // get all file names
        foreach($files as $file){ // iterate files
            if(is_file($file))
                unlink($file); // delete file
        }
    }

    /**
     * Test if a string in properly serialized
     *
     * @param string $data Any string type
     *
     * @return bool Is the string a serialized string
     */
    public static function isSerialized($data)
    {
        $test = @unserialize(($data));
        return ($test !== false || $test === 'b:0;') ? true : false;
    }

    /**
     *  Fixes the string length of a string object that has been serialized but the length is broken
     *
     * @param string $data The string object to recalculate the size on.
     *
     * @return string  A serialized string that fixes and string length types
     */
	public static function fixSerialString($data)
	{
		$result = array('data' => $data, 'fixed' => false, 'tried' => false);

		if (!self::isSerialized($data)) {

		    $serialized_fixed = self::recursiveFixSerialString($data);

			if (self::isSerialized($serialized_fixed)) {

			    $result['data'] = $serialized_fixed;

			    $result['fixed'] = true;
			}

			$result['tried'] = true;
		}

		return $result;
	}

	/**
	 *  Fixes the string length of a string object that has been serialized but the length is broken
	 *  Work on nested serialized string recursively.
	 *
	 *  @param string $data	The string ojbect to recalculate the size on.
	 *
	 *  @return string  A serialized string that fixes and string length types
	 */

	public static function recursiveFixSerialString($data ) {

	    if (!self::is_serialized_string($data)) {
	        return $data;
	    }

	    $result = '';

	    $matches = null;

	    $openLevel = 0;
	    $openContent = '';
	    $openContentL2 = '';

            // parse every char
	    for ($i = 0 ; $i < strlen($data) ; $i++) {

	        $cChar = $data[$i];

	        $addChar = true;

	        if ($cChar  == 's') {

	            // test if is a open string
	            if (preg_match ( '/^(s:\d+:")/', substr($data , $i)  , $matches )) {

	                $addChar = false;

	                $openLevel ++;

	                $i += strlen($matches[0]) - 1;

	            }

	        } else if ($openLevel > 0 && $cChar  == '"') {

                    // test if is a close string
	            if (preg_match ( '/^";(?:}|a:|s:|S:|b:|d:|i:|o:|O:|C:|r:|R:|N;)/', substr($data , $i) ) ) {

	                $addChar = false;

	                switch ($openLevel) {
		            case 1:
		                // level 1
				// flush string content
		                $result .= 's:'.strlen($openContent).':"'.$openContent.'";';

		                $openContent = '';

		                break;
		            case 2;
		               // level 2
			       // fix serial string level2
			       $sublevelstr = self::recursiveFixSerialString($openContentL2);

			       // flush content on level 1
		               $openContent .= 's:'.strlen($sublevelstr).':"'.$sublevelstr.'";';

		               $openContentL2 = '';

		               break;
		            default:
                              // level > 2
			      // keep writing at level 2; it will be corrected with recursion
                              break;

	                }

	                $openLevel --;

	                $closeString = '";';

	                $i += strlen($closeString) -1;

	            }

	        }


	        if ($addChar) {

	            switch ($openLevel) {
		        case 0:
		            // level 0
			    // add char on result
		            $result .= $cChar;

		            break;
		        case 1:
		            // level 1
			    // add char on content level1
		            $openContent .= $cChar;

		            break;
		        default:
		            // level > 1
			    // add char on content level2
		            $openContentL2 .= $cChar;

		            break;
	            }

	        }

	    }

	    return $result;

	}

    /**
     *  The call back method call from via fixSerialString
     */
    private static function fixStringCallback($matches)
    {
        return 's:' . strlen(($matches[2]));
    }
}