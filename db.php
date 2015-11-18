<?php

class db {

	private $query = "";
	private $select = "";
	private $from = "";
	private $where = "";
	private $limit = "";
	private $order_by = "";
	private $join = "";
	private $group_by = "";
	private $bind = array();

	private $values = array();
	private $types = "";
	private $data = array();

	private function reset_data() {
		$this->query = "";
		$this->select = "";
		$this->from = "";
		$this->where = "";
		$this->limit = "";
		$this->join = "";
		$this->order_by = "";
		$this->group_by = "";
		$this->bind = array();

		$this->values = array();
		$this->types = "";
		$this->data = array();
	}

	private function connect_to_db($host="localhost", $user="root", $pass="live4the1", $db="my_collections") {
		try
		{
			$con = new PDO("mysql:host=" . $host . ";dbname=" . $db, $user, $pass);
		}
		catch (PDOException $e)
		{
			echo "Error: " . $e->getMessage() . "<br />";
		}

		return $con;

	}

	public function select($selects) {
		$this->select = "SELECT " . implode(", ", $selects);
	}

	public function from($from) {
		$this->from = " FROM " . $from;
	}

	private function bind_params($key, $val) {
		if (is_numeric($val)) {
			$out = floatval(preg_replace("[^0-9.]", "", $val));
			$this->bind[":$key"] = $out;
		} else {
			$this->bind[":$key"] = $val;
		}
	}

	public function where($where_arr, $relation = "=", $and_or = "AND") {
		$bind = "";
		$run_num = 0;
		foreach ($where_arr as $col => $val) {
			if ($run_num > 0) {
				$bind .= " " . $and_or;
			}
			$run_num++;
			// can't have a dot in bind, so replacing any with underscore.
			$key = str_replace(".", "_", $col);
			$this->bind_params($key, $val);
			$bind .= " " . $col . " " . $relation . " :" . $key;
		}

		if (empty($this->where)) {
			$this->where = " WHERE " . $bind;
		} else {
			$this->where .= " " . $and_or . " " . $bind;
		}
	}

	public function join($tbl, $condition, $join_type = "LEFT") {
		$this->join .= " " . $join_type . " JOIN " . $tbl . " ON " . $condition;
	}

	public function limit($limit, $ofset=0) {
		$this->limit = " LIMIT " . $ofset . " " . $limit;
	}

	public function order_by($col, $order="ASC") {
		$this->order_by = " ORDER BY " . $col . " " . $order;
	}

	public function order_random() {
		$this->order_by = " ORDER BY RAND() ";
	}

	public function group_by($column) {
		$this->group_by = " GROUP BY " . $column;
	}

	public function query($debug=0) {

		$con = $this->connect_to_db();
		$this->query = $this->select . $this->from . $this->join . $this->where . $this->group_by . $this->order_by . $this->limit;

		if ($debug) {
			echo $this->query;
		}

		$stmt = $con->prepare($this->query);

		if ($stmt->execute($this->bind))
		{
			$this->reset_data();
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		$this->reset_data();
		return "Error";
	}

	public function update_data($tbl, $data, $where) {
		$fields_and_placeholders = array();
		foreach ($data as $field => $info) {
			// putting $field$field to avoid problem if the same column
			// will be for the update and for the where clause.
			$bind_field = $field . $field;
			array_push($fields_and_placeholders, "$field = :$bind_field");
			$this->bind_params($bind_field, $info);
		}

		$this->where($where);
		$query = "UPDATE " . $tbl . " SET " . implode(", ", $fields_and_placeholders) . $this->where;
		$con = $this->connect_to_db();

		$stmt = $con->prepare($query);
		if ($stmt->execute($this->bind)) {
			$this->reset_data();
			return "the update was successfull.";
		}
		$this->reset_data();
		return "there was an error while updating the data.";
	}

	public function insert_data($tbl, $data) {
		$fields = array();
		$values = array();
		$placeholders = array();

		foreach ($data as $field => $val) {
			array_push($fields, "$field");
			array_push($values, "$val");
			array_push($placeholders, "?");
		}

		$query = "INSERT INTO " . $tbl . " (" . implode(", ", $fields) . ") VALUES (" . implode( ", ", $placeholders) . ")";
		$con = $this->connect_to_db();

		$stmt = $con->prepare($query);

		if ($stmt->execute($values)) {
			return "the data saved successfully.";
		}
		return "there was an error while saving the data.";
	}

	public function delete_data($tbl, $where) {
		$this->where($where);

		$query = "DELETE FROM " . $tbl . $this->where;

		$con = $this->connect_to_db();

		$stmt = $con->prepare($query);

		if ($stmt->execute($this->bind))
		{
			$this->reset_data();
			return "the data was deleted";
		}
		$this->reset_data();
		return "there was an error while deleting the data.";

	}
}
