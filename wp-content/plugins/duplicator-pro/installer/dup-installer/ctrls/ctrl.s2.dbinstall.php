<?php
defined("DUPXABSPATH") or die("");

class DUPX_DBInstall
{
    private $dbh;
    private $post;
    public $sql_result_data;
    public $sql_result_data_length;
    public $dbvar_maxtime;
    public $dbvar_maxpacks;
    public $dbvar_sqlmode;
    public $dbvar_version;
    public $pos_in_sql;
    public $sql_file_path;
    public $sql_result_file_path;
    public $php_mem;
    public $php_mem_range;
    public $table_count;
    public $table_rows;
    public $query_errs;
    public $root_path;
    public $drop_tbl_log;
    public $rename_tbl_log;
    public $dbquery_errs;
    public $dbquery_rows;
    public $dbtable_count;
    public $dbtable_rows;
    public $dbdelete_count;
    public $profile_start;
    public $profile_end;
    public $start_microtime;
    public $dbcollatefb;
    public $dbobj_views;
    public $dbobj_procs;
	public $dbchunk;
    public $dbFileSize = 0;
    private $threadTimeOut = 10;

    public function __construct($post, $start_microtime)
    {
        $this->post                 = $post;
        $this->php_mem              = $GLOBALS['PHP_MEMORY_LIMIT'];
        $this->root_path            = $GLOBALS['DUPX_ROOT'];
        $this->sql_file_path        = "{$GLOBALS['DUPX_INIT']}/dup-database__{$GLOBALS['DUPX_AC']->package_hash}.sql";
        $this->sql_result_file_path = "{$GLOBALS['DUPX_INIT']}/{$GLOBALS['SQL_FILE_NAME']}";
        $this->sql_chunk_seek_tell_log = "{$GLOBALS['DUPX_INIT']}/dup-database-seek-tell-log__{$GLOBALS['DUPX_AC']->package_hash}.txt";
		$this->dbFileSize			= @filesize($this->sql_file_path);
		$this->dbFileSize			= ($this->dbFileSize === false) ? 0 : $this->dbFileSize;

        //ESTABLISH CONNECTION
        $this->dbh = DUPX_DB::connect($post['dbhost'], $post['dbuser'], $post['dbpass']);
        ($this->dbh) or DUPX_Log::error(ERR_DBCONNECT.mysqli_connect_error());
        if ($_POST['dbaction'] == 'empty' || $post['dbaction'] == 'rename') {
            $post_db_name = DUPX_U::sanitize_text_field($post['dbname']);
            mysqli_select_db($this->dbh, mysqli_real_escape_string($this->dbh, $post_db_name))
                or DUPX_Log::error(sprintf(ERR_DBCREATE, $post_db_name));
        }

        @mysqli_query($this->dbh, "SET wait_timeout = ".mysqli_real_escape_string($this->dbh, $GLOBALS['DB_MAX_TIME']));
        @mysqli_query($this->dbh, "SET GLOBAL max_allowed_packet = ".mysqli_real_escape_string($this->dbh, $GLOBALS['DB_MAX_PACKETS']));
        @mysqli_query($this->dbh, "SET max_allowed_packet = ".mysqli_real_escape_string($this->dbh, $GLOBALS['DB_MAX_PACKETS']));

        $this->profile_start   = isset($post['profile_start']) ? DUPX_U::sanitize_text_field($post['profile_start']) : DUPX_U::getMicrotime();
        $this->start_microtime = isset($post['start_microtime']) ? DUPX_U::sanitize_text_field($post['start_microtime']) : $start_microtime;
        $this->thread_start_time = microtime(true);
        $this->dbvar_maxtime   = DUPX_DB::getVariable($this->dbh, 'wait_timeout');
        $this->dbvar_maxpacks  = DUPX_DB::getVariable($this->dbh, 'max_allowed_packet');
        $this->dbvar_sqlmode   = DUPX_DB::getVariable($this->dbh, 'sql_mode');
        $this->dbvar_version   = DUPX_DB::getVersion($this->dbh);
        $this->dbvar_maxtime   = is_null($this->dbvar_maxtime) ? 300 : $this->dbvar_maxtime;
        $this->dbvar_maxpacks  = is_null($this->dbvar_maxpacks) ? 1048576 : $this->dbvar_maxpacks;
        $this->dbvar_sqlmode   = empty($this->dbvar_sqlmode) ? 'NOT_SET' : $this->dbvar_sqlmode;
        $this->dbquery_errs    = isset($post['dbquery_errs']) ? DUPX_U::sanitize_text_field($post['dbquery_errs']) : 0;
        $this->drop_tbl_log    = isset($post['drop_tbl_log']) ? DUPX_U::sanitize_text_field($post['drop_tbl_log']) : 0;
        $this->rename_tbl_log  = isset($post['rename_tbl_log']) ? DUPX_U::sanitize_text_field($post['rename_tbl_log']) : 0;
        $this->dbquery_rows    = isset($post['dbquery_rows']) ? DUPX_U::sanitize_text_field($post['dbquery_rows']) : 0;
        $this->dbdelete_count  = isset($post['dbdelete_count']) ? DUPX_U::sanitize_text_field($post['dbdelete_count']) : 0;
        $this->dbcollatefb     = isset($post['dbcollatefb']) ? DUPX_U::sanitize_text_field($post['dbcollatefb']) : 0;
        $this->dbobj_views     = isset($post['dbobj_views']) ? DUPX_U::sanitize_text_field($post['dbobj_views']) : 0;
        $this->dbobj_procs     = isset($post['dbobj_procs']) ? DUPX_U::sanitize_text_field($post['dbobj_procs']) : 0;
		$this->dbchunk		   = isset($post['dbchunk'])     ? DUPX_U::sanitize_text_field($post['dbchunk']) : 0;
    }

    public function prepareSQL()
    {
        $faq_url      = $GLOBALS['FAQ_URL'];
        @chmod($this->sql_file_path , 0777);
        $sql_file = file_get_contents($this->sql_file_path, true);

        //ERROR: Reading database.sql file
        if ($sql_file === false || strlen($sql_file) < 10) {
            $spacer = str_repeat("&nbsp;", 5);
            $sql_file_rel_path = "dup-installer/dup-database__{$GLOBALS['DUPX_AC']->package_hash}.sql";
            $msg    = "
<b>Unable to read/find the ".DUPX_U::esc_html($sql_file_rel_path)." file from the archive.</b><br/>
Please check these items: <br/><br/>
1. Validate permissions and/or group-owner rights on these items: <br/>
{$spacer}- File: dup-database__".DUPX_U::esc_html($GLOBALS['DUPX_AC']->package_hash).".sql file in dup-installer folder<br/>
{$spacer}- Directory: [".DUPX_U::esc_html($this->root_path)."] <br/>
{$spacer}<small>See: <a href='".DUPX_U::esc_url($faq_url.'#faq-trouble-055-q')."' target='_blank'>".DUPX_U::esc_url($faq_url.'#faq-trouble-055-q')."</a></small><br/><br/>
2. Validate the dup-database__".DUPX_U::esc_html($GLOBALS['DUPX_AC']->package_hash).".sql file exists and is in the dup-installer folder of the archive.zip file <br/>
{$spacer}<small>See: <a href='".DUPX_U::esc_url($faq_url.'#faq-installer-020-q')."' target='_blank'>".DUPX_U::esc_url($faq_url.'#faq-installer-020-q')."</a></small><br/><br/> ";
            DUPX_Log::error($msg);
        }

        //Removes invalid space characters
        //Complex Subject See: http://webcollab.sourceforge.net/unicode.html
        $sql_file = $this->nbspFix($sql_file);

        //Write new contents to install-data.sql
        @chmod($this->sql_result_file_path, 0777);
        $sql_file_copy_status         = file_put_contents($this->sql_result_file_path, $sql_file);
        $this->sql_result_data        = explode(";\n", $sql_file);
        $this->sql_result_data_length = count($this->sql_result_data);
        $sql_file                     = null;

        //WARNING: Create installer-data.sql failed
        if ($sql_file_copy_status === false || filesize($this->sql_result_file_path) == 0 || !is_readable($this->sql_result_file_path)) {
            $sql_file_path = "{$GLOBALS['DUPX_INIT']}/dup-database__{$GLOBALS['DUPX_AC']->package_hash}.sql";
            $sql_file_size = DUPX_U::readableByteSize(filesize($sql_file_path));
            $msg           = "\nWARNING: Unable to properly copy dup-installer/dup-database__{$GLOBALS['DUPX_AC']->package_hash}.sql ({$sql_file_size}) to {$GLOBALS['SQL_FILE_NAME']}.  Please check these items:\n";
            $msg           .= "- Validate permissions and/or group-owner rights on dup-database__{$GLOBALS['DUPX_AC']->package_hash}.sql and directory [{$GLOBALS['DUPX_INIT']}/] \n";
            $msg           .= "- see: {$faq_url}#faq-trouble-055-q \n";
            DUPX_Log::info($msg);
        }
    }

    public function prepareDB()
    {
        @mysqli_query($this->dbh, "SET wait_timeout = ".mysqli_real_escape_string($this->dbh, $GLOBALS['DB_MAX_TIME']));
        @mysqli_query($this->dbh, "SET max_allowed_packet = ".mysqli_real_escape_string($this->dbh, $GLOBALS['DB_MAX_PACKETS']));
        DUPX_DB::setCharset($this->dbh, $this->post['dbcharset'], $this->post['dbcollate']);
		$this->setSQLSessionMode();

        //Set defaults incase the variable could not be read
        $this->drop_tbl_log   = 0;
        $this->rename_tbl_log = 0;
        $sql_file_size1       = DUPX_U::readableByteSize(@filesize("{$GLOBALS['DUPX_INIT']}/dup-database__{$GLOBALS['DUPX_AC']->package_hash}.sql"));
        $sql_file_size2       = DUPX_U::readableByteSize(@filesize("{$this->root_path}/{$GLOBALS['SQL_FILE_NAME']}"));
        $collate_fb           = $this->dbcollatefb ? 'On' : 'Off';

        DUPX_Log::info("--------------------------------------");
        DUPX_Log::info('DATABASE-ENVIRONMENT');
        DUPX_Log::info("--------------------------------------");
        DUPX_Log::info("MYSQL VERSION:\tThis Server: {$this->dbvar_version} -- Build Server: {$GLOBALS['DUPX_AC']->version_db}");
        DUPX_Log::info("FILE SIZE:\tdup-database__{$GLOBALS['DUPX_AC']->package_hash}.sql ({$sql_file_size1}) - installer-data.sql ({$sql_file_size2})");
        DUPX_Log::info("TIMEOUT:\t{$this->dbvar_maxtime}");
        DUPX_Log::info("MAXPACK:\t{$this->dbvar_maxpacks}");
        DUPX_Log::info("SQLMODE-GLOBAL:\t{$this->dbvar_sqlmode}");
		DUPX_Log::info("SQLMODE-SESSION:" . ($this->getSQLSessionMode()));
        DUPX_Log::info("NEW SQL FILE:\t[{$this->sql_result_file_path}]");
        DUPX_Log::info("COLLATE FB:\t{$collate_fb}");
		DUPX_Log::info("DB CHUNKING:\t"	  . ($this->dbchunk	    ? 'enabled' : 'disabled'));
		DUPX_Log::info("DB VIEWS:\t"	  . ($this->dbobj_views ? 'enabled' : 'disabled'));
        DUPX_Log::info("DB PROCEDURES:\t" . ($this->dbobj_procs ? 'enabled' : 'disabled'));

        if (version_compare($this->dbvar_version, $GLOBALS['DUPX_AC']->version_db) < 0) {
            DUPX_Log::info("\nNOTICE: This servers version [{$this->dbvar_version}] is less than the build version [{$GLOBALS['DUPX_AC']->version_db}].  \n"
                ."If you find issues after testing your site please referr to this FAQ item.\n"
                ."https://snapcreek.com/duplicator/docs/faqs-tech/#faq-installer-260-q");
        }

        //CREATE DB
        switch ($this->post['dbaction']) {
            case "create":
                if ($this->post['view_mode'] == 'basic') {
                    mysqli_query($this->dbh, "CREATE DATABASE IF NOT EXISTS `".mysqli_real_escape_string($$this->dbh, $this->post['dbname'])."`");
                }
                mysqli_select_db($this->dbh, $this->post['dbname'])
                    or DUPX_Log::error(sprintf(ERR_DBCONNECT_CREATE, $this->post['dbname']));
                break;

            //DROP DB TABLES:  DROP TABLE statement does not support views
            case "empty":
                //Drop all tables, views and procs
                $this->dropTables();
                $this->dropViews();
                $this->dropProcs();
                break;

            //RENAME DB TABLES
            case "rename" :
                $sql          = "SHOW TABLES FROM `{$this->post['dbname']}` WHERE  `Tables_in_{$this->post['dbname']}` NOT LIKE '{$GLOBALS['DB_RENAME_PREFIX']}%'";
                $found_tables = null;
                if ($result       = mysqli_query($this->dbh, $sql)) {
                    while ($row = mysqli_fetch_row($result)) {
                        $found_tables[] = $row[0];
                    }
                    if (count($found_tables) > 0) {
                        foreach ($found_tables as $table_name) {
                            $sql    = "RENAME TABLE `".mysqli_real_escape_string($this->dbh, $this->post['dbname'])."`.`".mysqli_real_escape_string($this->dbh, $table_name)."` TO  `".mysqli_real_escape_string($this->dbh, $this->post['dbname'])."`.`".mysqli_real_escape_string($this->dbh, $GLOBALS['DB_RENAME_PREFIX']).mysqli_real_escape_string($this->dbh, $table_name)."`";
                            if (!$result = mysqli_query($this->dbh, $sql)) {
                                DUPX_Log::error(sprintf(ERR_DBTRYRENAME, "{$this->post['dbname']}.{$table_name}"));
                            }
                        }
                        $this->rename_tbl_log = count($found_tables);
                    }
                }
                break;
        }
    }

    public function writeInChunks() {
        DUPX_Log::info("--------------------------------------");
        DUPX_Log::info("** DB install chunk start");
        DUPX_Log::info("--------------------------------------");

        if (isset($this->post['dbchunk_retry']) && $this->post['dbchunk_retry'] > 0) {
            DUPX_Log::info("Retrying count: ".$this->post['dbchunk_retry']);
        }


        $handle = fopen($this->sql_file_path, 'rb');
       	if ($handle === false) {
            return false;
        }

        DUPX_Log::info("Seeking DB file position: ".$this->post['pos']);

        if (-1 !== fseek($handle, $this->post['pos'])) {
            DUPX_DB::setCharset($this->dbh, $this->post['dbcharset'], $this->post['dbcollate']);
			$this->setSQLSessionMode();

            $elapsed_time = (microtime(true) - $this->thread_start_time);
            if ($elapsed_time < $this->threadTimeOut) {

                DUPX_Log::info("Iterating query loop");
                $query = null;
                while (($line = fgets($handle)) !== false) {
                    $query .= $line;
                    if (preg_match('/;\s*$/S', $query)) {
                        // Temp: Uncomment this to randomly kill the php db process to simulate real world hosts and verify system recovers properly
                        /*
                        $rand_no = rand(0, 500);
                        if (0 == $this->post['dbchunk_retry'] && 1 == $rand_no) {
                            DUPX_Log::info("#### intentionally killing db chunk installation process");
                            error_log('#### intentionally killing db chunk installation process');
                            exit(1);
                        }
                        */

                        $query = trim($query);
                        if (0 === strpos($query, "DELIMITER")) {
                            DUPX_Log::info("Skipping delimiter query");
                            $query = null;
                            continue;
                        }

                        // If any query have double query
                        $query_explode = explode(";\n", $query);
                        $this->writeInDB($query_explode);
                        
                        $elapsed_time = (microtime(true) - $this->thread_start_time);
                        DUPX_Log::info("Elapsed time: ".$elapsed_time);
                        if ($elapsed_time > $this->threadTimeOut) {
                            DUPX_Log::info("Breaking query loop. Elapsed time: ".$elapsed_time);
                            break;
                        } else {
                            DUPX_Log::info("Not Breaking query loop");
                        }                        

                        $query = null;
                    }
                }
            } else {
                DUPX_Log::info("Skipping query loop because already out of time. Elapsed time: ".$elapsed_time);
                $query_offset = ftell($handle);
            }
            
            $query_offset = ftell($handle);
			$progress = ceil($query_offset / $this->dbFileSize * 100);

            $json['profile_start']   = $this->profile_start;
            $json['start_microtime'] = $this->start_microtime;
            $json['dbquery_errs']    = $this->dbquery_errs;
            $json['drop_tbl_log']    = $this->drop_tbl_log;
            $json['dbquery_rows']    = $this->dbquery_rows;
            $json['rename_tbl_log']  = $this->rename_tbl_log;
            $json['dbdelete_count']  = $this->dbdelete_count;
            $json['progress']		 = $progress;
            $json['pos']             = $query_offset;

            $seek_tell_log_line = filesize($this->sql_chunk_seek_tell_log) > 0 ? ',' : '';
            $seek_tell_log_line .= $this->post['pos'].'-'.$query_offset;
            file_put_contents($this->sql_chunk_seek_tell_log, $seek_tell_log_line, FILE_APPEND);

            if (feof($handle)) {
                // ensure integrity
                $is_okay = true;
                $seek_tell_log = file_get_contents($this->sql_chunk_seek_tell_log);
                $seek_tell_log_explodes = explode(',', $seek_tell_log);
                $is_okay = true;
                $last_start = 0;
                $last_end = 0;
                foreach ($seek_tell_log_explodes as $seek_tell_log_explode) {
                    $temp_arr = explode('-', $seek_tell_log_explode);
                    if (is_array($temp_arr) && 2 == count($temp_arr)) {
                        $start = $temp_arr[0];
                        $end = $temp_arr[1];
                        if ($start != $last_end) {
                            $is_okay = false;
                            break;    
                        }
                        if ($last_start > $end) {
                            $is_okay = false;
                            break;
                        }

                        $last_start = $start;
                        $last_end = $end;
                    } else {
                        $is_okay = false;
                        break;
                    }
                }
                if ($is_okay) {
                    $expected_file_size = $last_end;
                    $actual_file_size = filesize($this->sql_file_path);
                    if ($expected_file_size != $actual_file_size) {
                        $is_okay = false;
                    }
                }

                if ($is_okay) {
                    DUPX_Log::info('DB install chunk process integrity check has been just passed successfully.');
                    $json['pass']              = 1;
                    $json['continue_chunking'] = false;
                } else {
                    DUPX_Log::info('DB install chunk process integrity check has been just failed.');
                    $json['is_error'] = 1;
                    $json['error_msg'] = 'DB install chunk process integrity check has been just failed.';
                }
            } else {
                $json['pass']              = 0;
                $json['continue_chunking'] = true;
            }
        }

        DUPX_Log::info("End Query offset: $query_offset");

        if ($json['pass']) {
            DUPX_Log::info("This is last chunk");
        }

        fclose($handle);

        DUPX_Log::info("--------------------------------------");
        DUPX_Log::info("** DB install chunk end");
        DUPX_Log::info("--------------------------------------");

        ob_flush();
        flush();

        return $json;
    }
    
    public function writeInDB($sql_data = array())
    {
        //WRITE DATA
        $fcgi_buffer_pool  = 5000;
        $fcgi_buffer_count = 0;
        $counter           = 0;
        if (!empty($sql_data)) {
            $this->sql_result_data = $sql_data;
        }
		
        $this->applyCollationFallback();
        $this->applyProcUserFix();
        $sql_data_length = count($this->sql_result_data);
        @mysqli_autocommit($dbh, false);

        while ($counter < $sql_data_length) {

            $query_strlen = strlen(trim($this->sql_result_data[$counter]));
            if ($this->dbvar_maxpacks < $query_strlen) {
                DUPX_Log::info("**ERROR** Query size limit [length={$this->dbvar_maxpacks}] [sql=".substr($this->sql_result_data[$counter], 0, 75)."...]");
                $this->dbquery_errs++;
            } elseif ($query_strlen > 0) {
                $this->delimiterFix($counter);
                @mysqli_free_result(@mysqli_query($this->dbh, ($this->sql_result_data[$counter])));
                $err = mysqli_error($this->dbh);
                //Check to make sure the connection is alive
                if (!empty($err)) {
                    if (!mysqli_ping($this->dbh)) {
                        mysqli_close($this->dbh);
                        $this->dbh = DUPX_DB::connect($this->post['dbhost'], $this->post['dbuser'], $this->post['dbpass'], $this->post['dbname']);
                        // Reset session setup
                        @mysqli_query($this->dbh, "SET wait_timeout = ".mysqli_real_escape_string($this->dbh, $GLOBALS['DB_MAX_TIME']));
                        DUPX_DB::setCharset($this->dbh, mysqli_real_escape_string($this->dbh, $this->post['dbcharset']), mysqli_real_escape_string($this->dbh, $this->post['dbcollate']));
                    }
                    DUPX_Log::info("**ERROR** database error write '{$err}' - [sql=".substr($this->sql_result_data[$counter], 0, 75)."...]");

                    if (DUPX_U::contains($err, 'Unknown collation')) {
                        DUPX_Log::info('RECOMMENDATION: Try resolutions found at https://snapcreek.com/duplicator/docs/faqs-tech/#faq-installer-110-q');
                    }

                    $this->dbquery_errs++;

                    //Buffer data to browser to keep connection open
                } else {
                    if ($fcgi_buffer_count++ > $fcgi_buffer_pool) {
                        $fcgi_buffer_count = 0;
                    }
                    $this->dbquery_rows++;
                }
            }
            $counter++;
        }
        @mysqli_commit($this->dbh);
        @mysqli_autocommit($this->dbh, true);
    }

	public function runCleanupRotines()
    {
        //DATA CLEANUP: Perform Transient Cache Cleanup
        //Remove all duplicator entries and record this one since this is a new install.
        $dbdelete_count1 = 0;
        $dbdelete_count2 = 0;

        @mysqli_query($this->dbh, "DELETE FROM `".mysqli_real_escape_string($this->dbh, $GLOBALS['DUPX_AC']->wp_tableprefix)."duplicator_pro_packages`");
        $dbdelete_count1 = @mysqli_affected_rows($this->dbh);

        @mysqli_query($this->dbh,
                "DELETE FROM `".mysqli_real_escape_string($this->dbh, $GLOBALS['DUPX_AC']->wp_tableprefix)."options` WHERE `option_name` LIKE ('_transient%') OR `option_name` LIKE ('_site_transient%')");
        $dbdelete_count2 = @mysqli_affected_rows($this->dbh);

        $this->dbdelete_count += (abs($dbdelete_count1) + abs($dbdelete_count2));

        //Reset Duplicator Options
        foreach ($GLOBALS['DUPX_AC']->opts_delete as $value) {
            mysqli_query($this->dbh, "DELETE FROM `".mysqli_real_escape_string($this->dbh, $GLOBALS['DUPX_AC']->wp_tableprefix)."options` WHERE `option_name` = '".mysqli_real_escape_string($dbh, $value)."'");
        }

		DUPX_Log::info("Starting Cleanup Routine...");

        //Remove views from DB
        if (!$this->dbobj_views) {
            $this->dropViews();
			DUPX_Log::info("/t - Views Dropped.");
        }

        //Remove procedures from DB
        if (!$this->dbobj_procs) {
            $this->dropProcs();
			DUPX_Log::info("/t - Procs Dropped.");
        }

		DUPX_Log::info("Cleanup Routine Complete");
	}

	private function getSQLSessionMode()
    {
		$result = mysqli_query($this->dbh, "SELECT @@SESSION.sql_mode;");
		$row = mysqli_fetch_row($result);
		$result->close();
		return is_array($row) ? $row[0] : '';
	}

	/*SQL MODE OVERVIEW:
	 * sql_mode can cause db create issues on some systems because the mode affects how data is inserted.
	 * Right now defaulting to	NO_AUTO_VALUE_ON_ZERO (https://dev.mysql.com/doc/refman/5.5/en/sql-mode.html#sqlmode_no_auto_value_on_zero)
	 * has been the saftest option because the act of seting the sql_mode will nullify the MySQL Engine defaults which can be very problematic
	 * if the default is something such as STRICT_TRANS_TABLES,STRICT_ALL_TABLES,NO_ZERO_DATE.  So the default behavior will be to always
	 * use NO_AUTO_VALUE_ON_ZERO.  If the user insits on using the true system defaults they can use the Custom option.  Note these values can
	 * be overriden by values set in the database.sql script such as:
	 * !40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO'
	*/
	private function setSQLSessionMode()
    {
        switch ($this->post['dbmysqlmode']) {
            case 'DEFAULT':
                @mysqli_query($this->dbh, "SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");
                break;
            case 'DISABLE':
                @mysqli_query($this->dbh, "SET SESSION sql_mode = ''");
                break;
            case 'CUSTOM':
                $dbmysqlmode_opts = $this->post['dbmysqlmode_opts'];
                $qry_session_custom = @mysqli_query($this->dbh, "SET SESSION sql_mode = '".mysqli_real_escape_string($dbh, $dbmysqlmode_opts)."'");
                if ($qry_session_custom == false) {
                    $sql_error = mysqli_error($this->dbh);
                    $log       = "WARNING: A custom sql_mode setting issue has been detected:\n{$sql_error}.\n";
                    $log       .= "For more details visit: http://dev.mysql.com/doc/refman/5.7/en/sql-mode.html\n";
					DUPX_Log::info($log);
                }
                break;
        }
	}

    private function dropTables()
    {
        $sql          = "SHOW FULL TABLES WHERE Table_Type != 'VIEW'";
        $found_tables = null;
        if ($result       = mysqli_query($this->dbh, $sql)) {
            while ($row = mysqli_fetch_row($result)) {
                $found_tables[] = $row[0];
            }
            if (count($found_tables) > 0) {
                foreach ($found_tables as $table_name) {
                    $sql    = "DROP TABLE `".mysqli_real_escape_string($this->dbh, $this->post['dbname'])."`.`".mysqli_real_escape_string($this->dbh, $table_name)."`";
                    if (!$result = mysqli_query($this->dbh, $sql)) {
                        DUPX_Log::error(sprintf(ERR_DBTRYCLEAN, "{$this->post['dbname']}.{$table_name}")."<br/>ERROR MESSAGE:{$err}");
                    }
                }
                $this->drop_tbl_log = count($found_tables);
            }
        }
    }

    private function dropProcs()
    {
        $sql    = "SHOW PROCEDURE STATUS";
        $found  = null;
        if ($result = mysqli_query($this->dbh, $sql)) {
            while ($row = mysqli_fetch_row($result)) {
                $found[] = $row[1];
            }
            if (count($found) > 0) {
                foreach ($found as $proc_name) {
                    $sql    = "DROP PROCEDURE IF EXISTS `".mysqli_real_escape_string($this->dbh, $this->post['dbname'])."`.`".mysqli_real_escape_string($this->dbh, $proc_name)."`";
                    if (!$result = mysqli_query($this->dbh, $sql)) {
                        DUPX_Log::error(sprintf(ERR_DBTRYCLEAN, "{$this->post['dbname']}.{$proc_name}")."<br/>ERROR MESSAGE:{$err}");
                    }
                }
            }
        }
    }

    private function dropViews()
    {
        $sql         = "SHOW FULL TABLES WHERE Table_Type = 'VIEW'";
        $found_views = null;
        if ($result      = mysqli_query($this->dbh, $sql)) {
            while ($row = mysqli_fetch_row($result)) {
                $found_views[] = $row[0];
            }
            if (count($found_views) > 0) {
                foreach ($found_views as $view_name) {
                    $sql    = "DROP VIEW `".mysqli_real_escape_string($this->dbh, $this->post['dbname'])."`.`".mysqli_real_escape_string($this->dbh, $view_name)."`";
                    if (!$result = mysqli_query($this->dbh, $sql)) {
                        DUPX_Log::error(sprintf(ERR_DBTRYCLEAN, "{$this->post['dbname']}.{$view_name}")."<br/>ERROR MESSAGE:{$err}");
                    }
                }
            }
        }
    }

    public function writeLog()
    {

		
        DUPX_Log::info("ERRORS FOUND:\t{$this->dbquery_errs}");
        DUPX_Log::info("DROPPED TABLES:\t{$this->drop_tbl_log}");
        DUPX_Log::info("RENAMED TABLES:\t{$this->rename_tbl_log}");
        DUPX_Log::info("QUERIES RAN:\t{$this->dbquery_rows}\n");

        $this->dbtable_rows  = 1;
        $this->dbtable_count = 0;

        if ($result = mysqli_query($this->dbh, "SHOW TABLES")) {
            while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
                $table_rows         = DUPX_DB::countTableRows($this->dbh, $row[0]);
                $this->dbtable_rows += $table_rows;
                DUPX_Log::info("{$row[0]}: ({$table_rows})");
                $this->dbtable_count++;
            }
            @mysqli_free_result($result);
        }

        DUPX_Log::info("Removed '{$this->dbdelete_count}' cache/transient rows");

        if ($this->dbtable_count == 0) {
            DUPX_Log::info("NOTICE: You may have to manually run the installer-data.sql to validate data input.
             Also check to make sure your installer file is correct and the table prefix
             '{$GLOBALS['DUPX_AC']->wp_tableprefix}' is correct for this particular version of WordPress. \n");
        }
    }

    public function getJSON($json)
    {
        $json['table_count'] = $this->dbtable_count;
        $json['table_rows']  = $this->dbtable_rows;
        $json['query_errs']  = $this->dbquery_errs;

        return $json;
    }

    private function applyCollationFallback()
    {
        if (!empty($this->post['dbcolsearchreplace']) && $this->post['dbcollatefb']) {
            $collation_replace_list = json_decode(stripslashes($this->post['dbcolsearchreplace']), true);

            if ($collation_replace_list === null) {
                DUPX_Log::info("WARNING: Cannot decode collation replace list JSON.\n", 1);
                return;
            }

            if (!empty($collation_replace_list)) {

                if ($this->firstOrNotChunking()) {
                    DUPX_Log::info("LEGACY COLLATION FALLBACK:\n\tRunning the following replacements:\n\t".stripslashes($this->post['dbcolsearchreplace']));
                }

                foreach ($collation_replace_list as $val) {
                    $replace_charset = false;
                    if (strpos($val['search'], 'utf8mb4') !== false && strpos($val['replace'], 'utf8mb4') === false) {
                        $replace_charset = true;
                    }
                    foreach ($this->sql_result_data as $key => $query) {
                        if (strpos($query, $val['search'])) {
                            $this->sql_result_data[$key] = str_replace($val['search'], $val['replace'], $query);
                            $sub_query                   = str_replace("\n", '', substr($query, 0, 80));
                            DUPX_Log::info("\tNOTICE: {$val['search']} replaced by {$val['replace']} in query [{$sub_query}...]");
                        }
                        if ($replace_charset && strpos($query, 'utf8mb4')) {
                            $this->sql_result_data[$key] = str_replace('utf8mb4', 'utf8', $this->sql_result_data[$key]);
                            $sub_query                   = str_replace("\n", '', substr($query, 0, 80));
                            DUPX_Log::info("\tNOTICE: utf8mb4 replaced by utf8 in query [{$sub_query}...]");
                        }
                    }
                }
            }
        }
    }

    private function applyProcUserFix()
    {
        foreach ($this->sql_result_data as $key => $query) {
            if (preg_match("/DEFINER.*PROCEDURE/", $query) === 1) {
                $query                       = preg_replace("/DEFINER.*PROCEDURE/", "PROCEDURE", $query);
                $query                       = str_replace("BEGIN", "SQL SECURITY INVOKER\nBEGIN", $query);
                $this->sql_result_data[$key] = $query;
            }
        }
    }

    private function delimiterFix($counter)
    {
        $firstQuery = trim(preg_replace('/\s\s+/', ' ', $this->sql_result_data[$counter]));
        $start      = $counter;
        $end        = 0;
        if (strpos($firstQuery, "DELIMITER") === 0) {
            $this->sql_result_data[$start] = "";
            $continueSearch                = true;
            while ($continueSearch) {
                $counter++;
                if (strpos($this->sql_result_data[$counter], 'DELIMITER') === 0) {
                    $continueSearch        = false;
                    unset($this->sql_result_data[$counter]);
                    $this->sql_result_data = array_values($this->sql_result_data);
                } else {
                    $this->sql_result_data[$start] .= $this->sql_result_data[$counter].";\n";
                    unset($this->sql_result_data[$counter]);
                }
            }
        }
    }

    public function nbspFix($sql)
    {
        if ($this->post['dbnbsp']) {
            if ($this->firstOrNotChunking()) {
                DUPX_Log::info("ran fix non-breaking space characters\n");
            }
            $sql = preg_replace('/\xC2\xA0/', ' ', $sql);
        }
        return $sql;
    }

    public function firstOrNotChunking()
    {
        return (!isset($this->post['continue_chunking']) || $this->post['first_chunk']);
    }

    public function disableRSSSL()
    {
        if(!DUPX_U::is_ssl()) {
            if($this->deactivatePlugin("really-simple-ssl/rlrsssl-really-simple-ssl.php")){
                DUPX_Log::info("Deactivated 'Really Simple SSL' plugin\n");
            }
        }
    }

    public function deactivatePlugin($slug)
    {
        $sql = "SELECT * FROM ".mysqli_real_escape_string($this->dbh, $GLOBALS['DUPX_AC']->wp_tableprefix)."options WHERE option_name = 'active_plugins'";
        $arr = mysqli_fetch_assoc(mysqli_query($this->dbh, $sql));
        $active_plugins_serialized = stripslashes($arr['option_value']);
        $active_plugins = unserialize($active_plugins_serialized);
        foreach ($active_plugins as $key => $active_plugin){
            if($active_plugin == $slug){
                unset($active_plugins[$key]);
                $active_plugins = array_values($active_plugins);
                $active_plugins_serialized = mysqli_real_escape_string($this->dbh,serialize($active_plugins));
                $sql = "UPDATE `".mysqli_real_escape_string($this->dbh, $GLOBALS['DUPX_AC']->wp_tableprefix)."options` SET `option_value`='".mysqli_real_escape_string($this->dbh, $active_plugins_serialized)."' WHERE `option_name` = 'active_plugins'";
                $result = mysqli_query($this->dbh, $sql);
                return $result;
                break;
            }
        }
    }

    public function insertMigrationFlag() {
        $sql = "INSERT into ".mysqli_real_escape_string($this->dbh, $GLOBALS['DUPX_AC']->wp_tableprefix)."options (option_name, option_value) VALUES ('duplicator_pro_migration', '1');";
        return mysqli_query($this->dbh, $sql);
    }

    public function __destruct()
    {
        @mysqli_close($this->dbh);
    }
}