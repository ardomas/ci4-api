<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Config\CURLRequest;
use App\Libraries\TableInfo;
use CodeIgniter\I18n\Time;

class DBTreeModel extends DBReadModel {

    protected $parentKey=null;
    protected $treeTable;
    protected $treeAlias;
    protected $treeLevel;

    protected $table_info;
    protected $db;

    public function __construct($params=null){
        // if(is_null($dbconf)){ die('something missing'); }
        // parent::__construct($dbconf);
        parent::__construct($params);
        if( is_null($this->treeAlias) ){ $this->treeAlias = 'tree'; }
        $this->sql_object = $this->get_sql_command();
        $this->treeLevel  = 0;
    }

    public function getDataTree(){
        // return $this->_array2tree( $this->getlist() );
        $this->get();
        $full_data = $this->_result;
        $tree_data = $this->_array2tree( $this->_data_idx );
        $full_data['data'] = $tree_data;
        return $full_data;
    }

    protected function _chktree($data,$key){
        $result = true;
        $tmp = $key;
        while( $result && !is_null($data[$tmp][$this->parentKey]) ){
            if($data[$tmp][$this->parentKey]==$key){
                $result = false;
            } else {
                $tmp = $data[$tmp][$this->parentKey];
            }
        }
        return $result;
    }

    protected function _array2tree( $data, $pid=null ){
        $newData = array();
        $this->treeLevel++;
        foreach( $data as $key=>$row ){
            $continue = false;
            $parentKey = $row[ $this->parentKey ];
            if( is_null($pid) ){
                if( is_null($parentKey) || ( trim( $parentKey )=='' ) ){
                    $continue = true;
                }
            } else {
                $continue = ( $pid == $parentKey );
            }
            if( $continue ){
                $continue = $this->treeLevel < 60;
            }
            if( $continue ){
                $continue = $this->_chktree($data,$key);
            }
            if( $continue ){
                $row['tree_level'] = $this->treeLevel;
                $key = $row[ $this->primaryKey ];
                $subData = $this->_array2tree( $data, $key );
                $row['hasSub'] = false;
                if( $subData != [] ){
                    $row['sub'] = $subData;
                    $row['hasSub'] = true;
                }
                $newData[] = $row;
            }
        }
        $this->treeLevel--;
        return $newData;
    }

}

?>