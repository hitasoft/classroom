<?php

class dbc {
	
	var $connection;
	var $num_queries = 0;
	
	function dbc($username, $password, $database, $hostname = "localhost") {
		$this->connection = mysql_connect ( $hostname, $username, $password );
		mysql_select_db ( $database, $this->connection );
	}
	function query($sql,$die=true) {
		$start_time = array_sum ( explode ( ' ', microtime () ) );
		if (!$die)
		{
		$query = mysql_query ( $sql, $this->connection );
		}
		else
		{
		$query = mysql_query ( $sql, $this->connection ) or die ( "<span class='error'>Invalid MySQL query: </span>".mysql_error());
		}
		$this->num_queries += 1;
		return $query;
	}
	
	function fetch($query) {
		return mysql_fetch_array ( $query );
	}

	function affected_rows() {
		return mysql_affected_rows( $this->connection );
	}
	
	function close() {
		mysql_close ( $this->connection );
	}
	
	function fetch_row($query) {
		return mysql_fetch_row ( $query );
	}
	
	function num_rows($query) {
		return mysql_num_rows ( $query );
	}
	
	function exists($sql) {
		$query = $this->query ( $sql );
		if ($this->num_rows ( $query ) == 0) {
			return false;
		} else {
			return true;
		}
	}
	
	function insert($table, $array) {
		$columns = "";
		$data = "";
		foreach ( $array as $key => $value ) {
			if (! empty ( $columns )) {
				$columns .= ",";
			}
			$columns .= "`" . $key . "`";
			if (! empty ( $data )) {
				$data .= ",";
			}
			$data .= "'" . mysql_escape_string($value) . "'";
		}
		$this->query ( "INSERT INTO `" . $table . "` (" . $columns . ") VALUES (" . $data . ")",false );
		$id = mysql_insert_id ( $this->connection );
		if ($id){
			return $id;
		}else{
			return mysql_affected_rows($this->connection);
		}
	}
	
	function update($table, $array, $where) {
		$data = "";
		foreach ( $array as $key => $value ) {
			if (! empty ( $data )) {
				$data .= ",";
			}
			$data .= "`" . $key . "` = '" . mysql_escape_string($value) . "'";
		}
		$this->query ( "UPDATE `" . $table . "` SET " . $data . " WHERE " . $where );
	   return mysql_affected_rows($this->connection);
	}
	
	function arrays($query) {
		$result=$this->query($query);
		for($i = 1; $i <= $this->num_rows ( $result ); $i ++) {
			$arrays [] = mysql_fetch_assoc ( $result );
		}
		return $arrays;
	
	}
	
	function json_list($query,$use=true,$id="",$r=false) {
		if ($use) { echo "["; }
		
		for($i = 1; $i <= $this->num_rows ( $query ); $i ++) {
			if ($i != 1) {
				echo ",";
			}
			
			/*
			if (!empty($id))
			{
				echo '"'.$ar[$id].'":';
			}
*/
			$ar = mysql_fetch_assoc ( $query );
			if (!empty($id))
			{
				
				echo '{"'.$ar[$id].'":[';
				echo str_replace("{","[",str_replace("}","]",json_encode ( $ar )));
				echo ']}';
			}
			else
			{
				echo json_encode ( $ar );
			}
			
		
		}
		if ($use)  { echo "]"; }
	}

}
$config ['username'] = "educato8_admin";
$config ['database'] = "educato8_classroom";
$config ['password'] = "password";
$dbc = new dbc ( $config ['username'], $config ['password'], $config ['database'],"127.0.0.1" );
?>
