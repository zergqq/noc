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
    
    /**
     * Pobiera wszystkie kategorie
     * @return array
     */
    
    public function getCategories(){
        $query = 'SELECT category.*, (COUNT(parent.' . $this->idColName . ') - 1) AS depth
		FROM '.$this->tableName.' AS category, '.$this->tableName.' AS parent
		WHERE (category.' . $this->lftColName . ' BETWEEN parent.' . $this->lftColName . ' AND parent.' . $this->rgtColName . ')
		GROUP BY category.' . $this->idColName;
        $query .= ' ORDER BY category.' . $this->lftColName;
        $sql = $this->db->prepare($query);
        if ( !$sql->execute() ){
            return false;
        }
        return $sql->fetchAll($this->defaultFetchMode);
    }

    /**
     * Dodaje kategorie
     * @return int
     * @param int $parentId,
     * @param array $data
     */

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
        $LastIdArr=$this->db->exec('INSERT INTO ' . $this->tableName . ' (' . join(',', $queryCols) . ') VALUES(' . $queryValues . ') RETURNING  '.$this->$idColName.'');
        $lastId=$LastIdArr[0];
        if (false == $this->addTrans($lastId, $data)) {
            return false;
        }
        return $lastId;
    }

    /**
     * Dodaje tlumaczenie do wybranego categoryu
     * @return boolean
     * @param int $categoryId
     * @param array $data
     */

    public function addTrans($categoryId,$data){

        $queryCols = array($this->idColName);
        $queryCols = array_merge($queryCols, array_keys($data));
        $queryValues = $categoryId;
        if (!empty($data)) {
            $queryValues .= ','.join(',', array_map(array($this->db, 'quote'), $data));
        }

        if (false === $this->db->exec('INSERT INTO '. $this->tableNameTrans .' ('.join(',', $queryCols).') VALUES('.$queryValues.')')) {
            return false;
        }
        return true;
    }

    /**
     * Usuwa kategorie
     * @return boolean
     * @param int $categoryId
     */

    public function removeCategory($categoryId){
        if (false === $this->db->exec('DELETE FROM '. $this->tableName .' WHERE '.$this->idColName.'='.$categoryId)) {
            return false;
        }
        return true;
    }

    /**
     * Pobiera kategorie
     * @return array
     * @param int $categoryId
     */

    public function getCategoryById($categoryId){
        $sql = $this->db->prepare('SELECT * FROM '.$this->tableName.' WHERE ' . $this->idColName . ' = ?');
        if (!$sql->execute(array($categoryId))) {
            return array();
        }
        if (!$sql->rowCount()) {
            return array();
        }
        return $sql->fetch($this->defaultFetchMode);
    }

    /**
     * Pobiera id dzieci kategorii
     * @return array
     * @param int $lft
     * @param int $rgt
     */

    public function getCategoryWithChildrenIds($lft, $rgt){
        $sql = $this->db->prepare('SELECT '.$this->$idColName.' FROM '.$this->tableName.' AS category WHERE category.'.$this->lftColName.' >= ? AND category.'.$this->rgtColName.' <= ? ORDER BY category.'.$this->lftColName);
        if (!$sql->execute(array($lft, $rgt))) {
            return array();
        }
        return $sql->fetchAll(PDO::FETCH_COLUMN, 0);
    }


    /**
     * Przenosi kategorie na sam koniec do wezla docelowego
     * @return bool
     * @param int categoryId
     * @param int $parentcategoryId
     */


    public function moveCategory($categoryId, $parentcategoryId){
        $categoryToMove = $this->getCategoryById($categoryId);
        $parent = $this->getCategoryById($parentcategoryId);
        if ($parent[$this->idColName] == $categoryToMove[$this->parentIdColName]) {
            return false;
        }
        $categoryWithChildrenIds = $this->getCategoryWithChildrenIds($categoryToMove[$this->lftColName], $categoryToMove[$this->rgtColName]);
        if (in_array($parentcategoryId, $categoryWithChildrenIds)) {
            return false;
        }
        $categoryToMoveWidth = $categoryToMove[$this->rgtColName] - $categoryToMove[$this->lftColName] + 1;
        $sql = 'UPDATE '.$this->tableName.' SET '.
            $this->lftColName.' = '.$this->lftColName.' - '.$categoryToMoveWidth.' '.
            'WHERE '.$this->lftColName.' > '.$categoryToMove[$this->rgtColName].'
		AND '.$this->idColName.' NOT IN ('.implode(',', $categoryWithChildrenIds).')';
        if (false === $this->db->exec($sql)) {
            return false;
        }
        $sql = 'UPDATE '.$this->tableName.' SET '.
            $this->rgtColName.' = '.$this->rgtColName.' - '.$categoryToMoveWidth.' '.
            'WHERE '.$this->rgtColName.' > '.$categoryToMove[$this->rgtColName].'
		AND '.$this->idColName.' NOT IN ('.implode(',', $categoryWithChildrenIds).')';
        if (false === $this->db->exec($sql)) {
            return false;
        }
        if ($parent[$this->lftColName] > $categoryToMove[$this->lftColName]) {
            $parent[$this->lftColName] -= $categoryToMoveWidth;
        }
        if($parent[$this->rgtColName] > $categoryToMove[$this->rgtColName]){
            $parent[$this->rgtColName] -= $categoryToMoveWidth;
        }
        $sql = 'UPDATE '.$this->tableName.' SET '.
            $this->lftColName.' = '.$this->lftColName.' + '.$categoryToMoveWidth.' '.
            'WHERE '.$this->lftColName.' > '.$parent[$this->rgtColName].'
		AND '.$this->idColName.' NOT IN ('.implode(',', $categoryWithChildrenIds).')';
        if (false === $this->db->exec($sql)) {
            return false;
        }
        $sql = 'UPDATE '.$this->tableName.' SET '.
            $this->rgtColName.' = '.$this->rgtColName.' + '.$categoryToMoveWidth.' '.
            'WHERE '.$this->rgtColName.' >= '.$parent[$this->rgtColName].'
		AND '.$this->idColName.' NOT IN ('.implode(',', $categoryWithChildrenIds).')';
        if (false === $this->db->exec($sql)) {
            return false;
        }
        $parent[$this->rgtColName] += $categoryToMoveWidth;
        $offset = $parent[$this->rgtColName] - ($categoryToMove[$this->lftColName] + $categoryToMoveWidth);
        $sql = 'UPDATE '.$this->tableName.' SET '.
            $this->lftColName.' = '.$this->lftColName.' + '.$offset.', '.
            $this->rgtColName.' = '.$this->rgtColName.' + '.$offset.' '.
            'WHERE '.$this->idColName.' IN ('.implode(',', $categoryWithChildrenIds).')';
        if (false === $this->db->exec($sql)) {
            return false;
        }
        $sql = 'UPDATE '.$this->tableName.' SET '.$this->parentIdColName.' = '.$parentcategoryId.'
		WHERE '.$this->idColName.' = '.$categoryId;
        if (false === $this->db->exec($sql)) {
            return false;
        }
        return true;
    }






}