<?php

/**
 *
 *  DbInterface | simple interface for MySql database for PHP
 *	Created by Christian Spinelli, october 2015
 *  Version: 0.1.2 2015-02-08
 *
 */

class DbInterface{

	public $error;
	public $lastId;

	private $DB;
	private $dbConfig;

	function __construct($config){
		$this->dbConfig = $config;
		$this->DB = new mysqli(
			$this->dbConfig['mySQLserverName'],
			$this->dbConfig['dbUserName'],
			$this->dbConfig['dbPassword'],
			$this->dbConfig['dbName'],
			$this->dbConfig['mySQLserverPort']
		);
	}

	/* Crea una array contenente tutte le righe del risultato della query
			Se nella query è presente la direttiva 'LIMIT 1' (limita il risultato alla prima riga) crea una variabile semplice
			Se nella query inizia con la direttiva 'INSERT TO'
			Se nella query inizia con la direttiva 'UPDATE'
	 		Se nella query inizia con la direttiva 'COUNT(*)'
	 		Se nella query inizia con la direttiva 'DELETE'
	 */
	function queryToRows($query){
		$query = trim($query);
		$rows = Array();
		
		if ($this->DB->connect_errno){
			//Connessione non attiva
			$this->error = array('number' => $this->DB->connect_errno, 'description' => $this->DB->connect_error);
			return false;
		}else{

			if(!$this->DB->set_charset("utf8")){
				$this->error = array('number' => $this->DB->errno, 'description' => $this->DB->error);
				return false;
			}

			//$query = $this->secureSql($query);

			if($result = $this->DB->query($query)){
				if(stripos($query, 'LIMIT 1')==(strlen($query)-7)){
					$rows = $result->fetch_assoc();
					$result->close();
				}elseif(stripos($query, 'INSERT INTO')===0){
					$rows = $result;
					$this->lastId = $this->DB->insert_id;
				}elseif(stripos($query, 'UPDATE')===0){
					$rows = $result;
					$this->lastId = $this->DB->insert_id;
				}elseif(stripos($query, 'SELECT COUNT(*)')===0){
					$rows = $result->fetch_assoc();
					$rows = $rows['COUNT(*)'];
				}elseif(stripos($query, 'DELETE')===0){
					$rows = $result;
					$this->lastId = 0;
 				}else{
 					while($row = $result->fetch_assoc()){
						$rows[] = $row;
					}
					$result->close();
				}
			}else{
				//Query fallita
				$this->error = array('number' => $this->DB->errno, 'description' => $this->DB->error, 'query' => $query  );
				return false;
			}

		}
		return $rows;
		$this->close();
	}

	function secureSql($value){
		if(!empty($this->DB)){
			$value = strip_tags($value);
			return $this->DB->real_escape_string($value);
		}else{
			return '';
		}
	}

	function close(){
		$this->DB->close();
	}

}


function queryClean($query){
	$query = str_replace(Array("\n\r","\n\r","\n","\r")," ", $query);
	$query = str_replace(Array("\t"),"", $query);
	return $query;
}

?>