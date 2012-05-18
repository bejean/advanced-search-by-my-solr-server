<?php

Class Mss_Solr {
	
	private $_solr = null;
	private $_solrHost = null;
	private $_solrPort = null;
	private $_solrPath = null;
	private $_lastErrorCode = "";
	private $_lastErrorMessage = "";
	
	public function Mss_Solr () {}
	
	public function getLastErrorCode() {
		return $this->_lastErrorCode;
	}
	
	public function getLastErrorMessage() {
		return $this->_lastErrorMessage;
	}
	
	public function connect($options, $ping = false) {
		// get the connection options
		$this->_solrHost = $options['mss_solr_host'];
		$this->_solrPort = $options['mss_solr_port'];
		$this->_solrPath = $options['mss_solr_path'];
	
		// double check everything has been set
		if ( ! ($this->_solrHost and $this->_solrPort and $this->_solrPath) ) {
			$this->_lastErrorCode = -1;
			$this->_lastErrorMessage = "Invalid options";
			return false;
		}
	
		// create the solr service object
		try {
			require_once("SolrPhpClient/Apache/Solr/HttpTransport/Curl.php");			
			$httpTransport = new Apache_Solr_HttpTransport_Curl();
			$this->_solr = new Apache_Solr_Service($this->_solrHost, $this->_solrPort, $this->_solrPath, $httpTransport);
		} catch ( Exception $e ) {
			$this->_lastErrorCode = $e->getCode();
			$this->_lastErrorMessage = $e->getMessage();
			return false;
		}
	
		// if we want to check if the server is alive, ping it
		if ($ping) { 
			try {
				if (!$this->_solr->ping()) {
					//$this->_solr = null;
					$this->_lastErrorCode = -1;
					$this->_lastErrorMessage = "Ping failed";
					return false;
				}
			} catch ( Exception $e ) {
				$this->_lastErrorCode = $e->getCode();
				$this->_lastErrorMessage = $e->getMessage();
				return false;
			}
		}
		return true;
	}
	
	public function commit() {
		try {
			$this->_solr->commit();
			return true;
		} catch ( Exception $e ) {
			$this->_lastErrorCode = $e->getCode();
			$this->_lastErrorMessage = $e->getMessage();
			return false;
		}
	}
	
	public function optimize() {
		try {
			$this->_solr->optimize();
			return true;
		} catch ( Exception $e ) {
			$this->_lastErrorCode = $e->getCode();
			$this->_lastErrorMessage = $e->getMessage();
			return false;
		}
	}
	
	public function addDocuments($documents) {
		try {
			$this->_solr->addDocuments($documents);
			return true;
		} catch ( Exception $e ) {
			$this->_lastErrorCode = $e->getCode();
			$this->_lastErrorMessage = $e->getMessage();
			return false;
		}
	}
	
	
	public function deleteAll($optimize = false) {
		try {
			$this->_solr->deleteByQuery('*:*');
			if (!$this->commit()) return false;
			if (optimize) return $this->optimize();
			return true;
		} catch ( Exception $e ) {
			$this->_lastErrorCode = $e->getCode();
			$this->_lastErrorMessage = $e->getMessage();
			return false;
		}
	}
	
	public function deleteById( $doc_id ) {
		try {
			$this->_solr->deleteById( $doc_id );
			$this->_solr->commit();
		} catch ( Exception $e ) {
			echo $e->getMessage();
		}
	}
	
	public function search($qry, $offset, $count, $params) {
		return $this->_solr->search($qry, $offset, $count, $params);
	}
}


?>