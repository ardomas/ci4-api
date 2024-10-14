<?php

namespace App\Libraries;

// use App\Config\Database;

class TableInfo {

    protected $db;
    protected $table_schema;
    // protected $table_info;

    public function __construct($dbconf=null){
        if(is_null($dbconf)){ 
            $dbtest = new \App\Config\Database;
            $dbconf = $dbtest->defaultGroup; 
        }
        $this->db = \Config\Database::connect($dbconf);
        //
        $this->table_schema = $this->db->database;
    }

    public function set_table_schema($ts){
        $this->table_schema = $ts;
    }

    public function get_table_schema(){
        return $this->table_schema;
    }

    public function getLastUpdate($tbl_name,$tbl_schema=null){
        $result = 0;
        if(is_null($tbl_schema)){ $tbl_schema = $this->table_schema; }
        $tbl_info = $this->getTableInfoAll($tbl_name,$tbl_schema);
        if( (strtoupper($tbl_info['TABLE_TYPE'])=='VIEW') || (is_null($tbl_info['UPDATE_TIME'])) ){
            $tmp = $this->db->table( $tbl_name )->select('MAX(updated_at) AS `UPDATE_TIME`');
            $res = $tmp->get()->getResultArray();
            $result = $res[0]['UPDATE_TIME'];
        } else {
            $result = $tbl_info['UPDATE_TIME'];
        }
        return $result;
    }

    public function getTableType($tbl_name,$tbl_schema=null){
        return $this->getTableInfoFieldValue($tbl_name,'TABLE_TYPE',$tbl_schema);
    }

    public function getTableInfoAll($tbl_name,$tbl_schema=null){
        return $this->getTableInfoFieldValue($tbl_name,'*',$tbl_schema);
    }

    protected function getTableInfoFieldValue($tbl_name,$fld_name,$tbl_schema=null){
        if( is_null($tbl_schema) ){
            $tbl_schema=$this->table_schema;
        }
        $tmp = $this->db->table ('information_schema.tables')
                        ->select( $fld_name )
                        ->where ("TABLE_SCHEMA='$tbl_schema' AND TABLE_NAME='$tbl_name'");
        $res = $tmp->get()->getResultArray();
        if( count($res)>0 ){
            return $res[0];
        } else {
            return null;
        }
    }
    //
}