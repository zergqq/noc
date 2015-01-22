<?php
/**
 * Created by PhpStorm.
 * User: Nfq
 * Date: 0021 2015-01-21
 * Time: 08:57
 */

class Categories {

	protected $tableName = null;
    protected $tableNameTrans = null;
	protected $idColName;
	protected $parentIdColName;
	protected $lftColName;
	protected $rgtColName;
	protected $db = null;
	protected $defaultFetchMode = PDO::FETCH_ASSOC;


    public function __construct($db, $tableName,$tableNameTrans, $idColName, $parentIdCol, $lftColName, $rgtColName){
        $this->db = $db;
        $this->tableName = $tableName;
        $this->tableNameTrans = $tableNameTrans;
        $this->idColName = $idColName;
        $this->lftColName = $lftColName;
        $this->rgtColName = $rgtColName;
        $this->parentIdColName = $parentIdCol;
    }



    public function setDefaultFetchMode($mode){
        $this->defaultFetchMode = $mode;
    }

    public function getCategories(){
        $query = 'SELECT element.*, (COUNT(parent.' . $this->idColName . ') - 1) AS depth
		FROM '.$this->tableName.' AS element, '.$this->tableName.' AS parent
		WHERE (element.' . $this->lftColName . ' BETWEEN parent.' . $this->lftColName . ' AND parent.' . $this->rgtColName . ')
		GROUP BY element.' . $this->idColName;
        $query .= ' ORDER BY element.' . $this->lftColName;
        $sql = $this->db->prepare($query);
        if ( !$sql->execute() ){
            return false;
        }
        return $sql->fetchAll($this->defaultFetchMode);
    }



    public function addCategory($parentId,$data)
    {
        $sql = $this->db->prepare('SELECT ' . $this->rgtColName . ' FROM ' . $this->tableName . ' WHERE ' . $this->idColName . ' = ?');
        if (!$sql->execute(array($parentId))) {
            return false;
        }
        $row = $sql->fetch($this->defaultFetchMode);
        $rgt = $row[$this->rgtColName];
        if (false === $this->db->exec('UPDATE ' . $this->tableName . ' SET ' . $this->rgtColName . ' = ' . $this->rgtColName . ' + 2 WHERE ' . $this->rgtColName . ' >= ' . $rgt)) {
            return false;
        }
        if (false === $this->db->exec('UPDATE ' . $this->tableName . ' SET ' . $this->lftColName . ' = ' . $this->lftColName . ' + 2 WHERE ' . $this->lftColName . ' >= ' . $rgt)) {
            return false;
        }
        $queryCols = array($this->lftColName, $this->rgtColName, $this->parentIdColName);
        $queryValues = $rgt . ',' . $rgt . ' + 1,' . $parentId;
        if (false === $this->db->exec('INSERT INTO ' . $this->tableName . ' (' . join(',', $queryCols) . ') VALUES(' . $queryValues . ')')) {
            return false;
        }
        $lastId=$this->db->lastInsertId('category');
        if (false == $this->addTrans($lastId, $data)) {
            return false;
        }
        return $lastId;
    }

    public function addTrans($elementId,$data){

        $queryCols = array($this->idColName);
        $queryCols = array_merge($queryCols, array_keys($data));
        $queryValues = $elementId;
        if (!empty($data)) {
            $queryValues .= ','.join(',', array_map(array($this->db, 'quote'), $data));
        }

        if (false === $this->db->exec('INSERT INTO '. $this->tableNameTrans .' ('.join(',', $queryCols).') VALUES('.$queryValues.')')) {
            return false;
        }
        return true;
    }

    public function removeCategory($elementId){
        if (false === $this->db->exec('DELETE FROM '. $this->tableName .' WHERE '.$this->idColName.'='.$elementId)) {
            return false;
        }
        return true;
    }


    public function getelementById($elementId){
        $sql = $this->db->prepare('SELECT * FROM '.$this->tableName.' WHERE ' . $this->idColName . ' = ?');
        if (!$sql->execute(array($elementId))) {
            return array();
        }
        if (!$sql->rowCount()) {
            return array();
        }
        return $sql->fetch($this->defaultFetchMode);
    }

    public function getelementWithChildrenIds($lft, $rgt){
        $sql = $this->db->prepare('SELECT '.$this->$idColName.' FROM '.$this->tableName.' AS element WHERE element.'.$this->lftColName.' >= ? AND element.'.$this->rgtColName.' <= ? ORDER BY element.'.$this->lftColName);
        if (!$sql->execute(array($lft, $rgt))) {
            return array();
        }
        return $sql->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function moveCategory($elementId, $parentelementId){
        $elementToMove = $this->getelementById($elementId);
        $parent = $this->getelementById($parentelementId);
        if ($parent[$this->idColName] == $elementToMove[$this->parentIdColName]) {
            return false;
        }
        $elementWithChildrenIds = $this->getelementWithChildrenIds($elementToMove[$this->lftColName], $elementToMove[$this->rgtColName]);
        if (in_array($parentelementId, $elementWithChildrenIds)) {
            return false;
        }
        $elementToMoveWidth = $elementToMove[$this->rgtColName] - $elementToMove[$this->lftColName] + 1;
        $sql = 'UPDATE '.$this->tableName.' SET '.
            $this->lftColName.' = '.$this->lftColName.' - '.$elementToMoveWidth.' '.
            'WHERE '.$this->lftColName.' > '.$elementToMove[$this->rgtColName].'
		AND '.$this->idColName.' NOT IN ('.implode(',', $elementWithChildrenIds).')';
        if (false === $this->db->exec($sql)) {
            return false;
        }
        $sql = 'UPDATE '.$this->tableName.' SET '.
            $this->rgtColName.' = '.$this->rgtColName.' - '.$elementToMoveWidth.' '.
            'WHERE '.$this->rgtColName.' > '.$elementToMove[$this->rgtColName].'
		AND '.$this->idColName.' NOT IN ('.implode(',', $elementWithChildrenIds).')';
        if (false === $this->db->exec($sql)) {
            return false;
        }
        if ($parent[$this->lftColName] > $elementToMove[$this->lftColName]) {
            $parent[$this->lftColName] -= $elementToMoveWidth;
        }
        if($parent[$this->rgtColName] > $elementToMove[$this->rgtColName]){
            $parent[$this->rgtColName] -= $elementToMoveWidth;
        }
        $sql = 'UPDATE '.$this->tableName.' SET '.
            $this->lftColName.' = '.$this->lftColName.' + '.$elementToMoveWidth.' '.
            'WHERE '.$this->lftColName.' > '.$parent[$this->rgtColName].'
		AND '.$this->idColName.' NOT IN ('.implode(',', $elementWithChildrenIds).')';
        if (false === $this->db->exec($sql)) {
            return false;
        }
        $sql = 'UPDATE '.$this->tableName.' SET '.
            $this->rgtColName.' = '.$this->rgtColName.' + '.$elementToMoveWidth.' '.
            'WHERE '.$this->rgtColName.' >= '.$parent[$this->rgtColName].'
		AND '.$this->idColName.' NOT IN ('.implode(',', $elementWithChildrenIds).')';
        if (false === $this->db->exec($sql)) {
            return false;
        }
        $parent[$this->rgtColName] += $elementToMoveWidth;
        $offset = $parent[$this->rgtColName] - ($elementToMove[$this->lftColName] + $elementToMoveWidth);
        $sql = 'UPDATE '.$this->tableName.' SET '.
            $this->lftColName.' = '.$this->lftColName.' + '.$offset.', '.
            $this->rgtColName.' = '.$this->rgtColName.' + '.$offset.' '.
            'WHERE '.$this->idColName.' IN ('.implode(',', $elementWithChildrenIds).')';
        if (false === $this->db->exec($sql)) {
            return false;
        }
        $sql = 'UPDATE '.$this->tableName.' SET '.$this->parentIdColName.' = '.$parentelementId.'
		WHERE '.$this->idColName.' = '.$elementId;
        if (false === $this->db->exec($sql)) {
            return false;
        }
        return true;
    }






}