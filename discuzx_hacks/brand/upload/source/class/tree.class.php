<?php

/**
 *      [品牌空間] (C)2001-2010 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: tree.class.php 3775 2010-07-16 07:46:52Z yexinhao $
 */

class Tree {
	var $data = array();
	var $child = array(-1 => array());
	var $layer = array(-1 => -1);
	var $parent = array();
	var $countid = 0;
	function Tree($value) {}

	function setNode($id, $parent, $value) {

		$parent = $parent?$parent:0;

		$this->data[$id] = $value;
		$this->child[$parent][]  = $id;
		$this->parent[$id] = $parent;

		if(!isset($this->layer[$parent])) {
			$this->layer[$id] = 0;
		} else {
			$this->layer[$id] = $this->layer[$parent] + 1;
		}
	}

	function getList(&$tree, $root= 0) {
		foreach($this->child[$root] as $key => $id) {
			$tree[] = $id;
			if(!empty($this->child[$id])) $this->getList($tree, $id);
		}
	}

	function getValue($id) {
		return $this->data[$id];
	}

	function reSetLayer($id) {
		if($this->parent[$id]) {
			$this->layer[$this->countid] = $this->layer[$this->countid] + 1;
			$this->reSetLayer($this->parent[$id]);
		}
	}

	function getLayer($id, $space = false) {
		//重新計算級數
		$this->layer[$id] = 0;
		$this->countid = $id;
		$this->reSetLayer($id);
		return $space?str_repeat($space, $this->layer[$id]):$this->layer[$id];
	}

	function getParent($id) {
		return $this->parent[$id];
	}

	function getParents($id) {
		while($this->parent[$id] != -1) {
			$id = $parent[$this->layer[$id]] = $this->parent[$id];
		}

		ksort($parent);	//按照鍵名排序
		reset($parent); //數組指針移回第一個單元

		return $parent;
	}

	function getChild($id) {
		if(!empty($this->child[$id])) {
			return $this->child[$id];
		} else {
			return 0;
		}
	}

	function getChilds($id = 0) {
		$child = array();
		$this->getList($child, $id);

		return $child;
	}
}

?>