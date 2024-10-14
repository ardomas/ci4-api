<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;
use App\Config\CURLRequest;

use App\Libraries\TableInfo;

class DBReadModel extends Model {

    // default
    protected $db;
    protected $DBGroup          = 'default';
    protected $table;
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [];
    protected $filters          = [];

    // timestamps
    protected $useTimestamps    = false;
    protected $useSoftDeletes   = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = 'deleted_at';

    // callbacks
    protected $allowCallbacks   = true;
    protected $beforeInsert     = [];
    protected $afterInsert      = [];
    protected $beforeUpdate     = [];
    protected $afterUpdate      = [];
    protected $beforeFind       = [];
    protected $afterFind        = [];
    protected $beforeDelete     = [];
    protected $afterDelete      = [];

    protected $table_info;
    protected $sql_object;
    protected $fields;
    protected $selected_fields;
    protected $grouped_fields;
    protected $group_by;
    protected $order_by;
    protected $expired_cache_time=300;
    protected $forceLoad = true;

    protected $_result;
    protected $_data_raw;
    protected $_data_idx;
    protected $_last_update;
    protected $_status;
    protected $_is_update;

    public function __construct($params=null){
        //
        parent::__construct();
        $this->_init_db_model($params);
        //
    }

    protected function _init_db_model($params=null){
        //
        if( is_array($params) ){
            //
            foreach( $params as $key=>$val ){
                $this->$key = $val;
            }
            //
            if( !isset( $this->DBGroup ) ){
                // $this->DBGroup = 'default';
                die( 'DBGroup harus ada' );
            }
            if( !isset( $this->table ) ){
                die( 'table harus ada' );
            }
            if( !isset( $params['primaryKey'] ) ){
                $this->primaryKey = 'id';
            }
        }
        if( is_null($this->primaryKey) || trim($this->primaryKey)=='' ){
            $this->primaryKey='id';
        }
        //
        $this->db = \Config\Database::connect($this->DBGroup);
        //
        $this->table_info = new TableInfo($this->DBGroup);
        //
        $this->useTimestamps = !isset($this->useTimestamps) ? false : $this->useTimestamps;
        //
        $this->dateFormat    = !isset($this->dateFormat   ) ? 'datetime'   : ( is_null($this->dateFormat  ) ? 'datetime'   : $this->dateFormat   );
        $this->createdField  = !isset($this->createdField ) ? 'created_at' : ( is_null($this->createdField) ? 'created_at' : $this->createdField );
        $this->updatedField  = !isset($this->updatedField ) ? 'updated_at' : ( is_null($this->updatedField) ? 'updated_at' : $this->updatedField );
        $this->deletedField  = !isset($this->createdField ) ? 'deleted_at' : ( is_null($this->deletedField) ? 'deleted_at' : $this->deletedField );
        //
        $this->generate_allowedFields();
        $this->selected_fields = $this->generate_selected_fields();
        //
        $this->setForceLoad(true);
        //
    }

    public function get($key=null){
        if( $key!=null ){
            $this->filters = Array( $this->primaryKey => $key );
        }
        $this->getwhere();
        return $this->_get_from_list( $key );
    }

    public function getDataTree(){
        return 'not a tree table';
    }

    public function getraw($objcond=null){
        if(!is_null($objcond)){
            $this->where = $objcond;
        }
        $this->_getFromTableOrCache();
        return $this->_data_raw;
    }
    public function getidx($objcond=null){
        $this->getwhere();
        // if(!is_null($objcond)){
        //     $this->where = $objcond;
        // }
        // $this->_getFromTableOrCache();
        // $this->_data_indexed();
        return $this->_data_idx;
    }

    public function getlist($params){
        $temp = $this->getlistraw($params);
        $rows = Array();
        if( isset( $this->primaryKey ) ){
            $rows = array_column( $temp, null, $this->primaryKey );
        }
        // foreach( $temp as $row ){
        //     $key = $row[ $this->primaryKey ];
        //     $rows[$key] = $row;
        // }
        return $rows;
    }

    public function getlistraw($params){
        if( !is_null($params) ){
            $this->where = $params;
        }
        $this-_get_data_from_db();
        return $this->_data_raw;
    }
    public function getwhere($obj=null){
        //
        $result = false;
        // if( !is_null( $this->DBGroup ) ){
        if( !is_null($obj) ){
            $this->where = $obj;
            $this->forceLoad = true;
        }
        $this->_getFromTableOrCache();
    }

    public function clear_filter(){ $this->filters = []; }

    public function set_filter($fld,$val){ 
        $filters = $this->fiters;
        if( is_string( $val ) || is_array( $val ) ){
            $filters[$fld] = $val;
        }
        $this->filters = $filters;
    }

    public function getDataFromModel($srcdata){
        $data = array();
        foreach( $this->allowedFields as $fld ){
            $data[$fld] = $this->getVar( $fld );
        }
        return $data;
    }

    public function prepareData($oldData){
        $newData = array();
        if( !is_null($this->allowedFields) ){
            $newData = $oldData;
        }
        foreach( $this->allowedFields as $fld ){
            $newData[$fld] = $oldData[$fld];
        }
        if( !is_null($oldData[ $this->primaryKey ]) && ($oldData[$this->primaryKey]!=0) ){
            $newData[$this->primaryKey] = $oldData[$this->primaryKey];
        }
        return $newData;
    }

    public function findAllAsObject(){
        $arr  = $this->findAll();
        $obj = array();
        if( isset( $this->primaryKey ) ){
            $obj = array_column( $arr, null, $this->primaryKey );
        }
        // foreach( $arr as $row ){
        //     $fk = $this->primaryKey;
        //     $pk = $row[$fk];
        //     $obj[$pk] = $row;
        // }
        return $obj;
    }

    public function getForceLoad(){
        return $this->forceLoad;
    }

    public function setForceLoad($bool=false){
        $this->forceLoad=$bool;
    }
    public function setFilter( $array ){
        $this->filters = $array;
    }

    public function generate_allowedFields(){
        if( !isset( $this->allowedFields ) ){ $this->allowedFields = []; }
        if( is_null($this->allowedFields) || $this->allowedFields==[] ){
            $list_fields = $this->getFieldNames( $this->table );
            $excl_fields = [ $this->primaryKey, $this->createdField, $this->updatedField, $this->deletedField ];
            $this->allowedFields = [];
            foreach( $list_fields as $field ){
                if( !in_array($field,$excl_fields) ){
                    $this->allowedFields[] = $field;
                }
            }  
        }
    }

    public function get_sql_command(){
        if( is_null($this->sql_object) ){
            $this->sql_object = $this->_generate_sql_string();
        }
        return $this->sql_object;
    }

    protected function _get_from_list($key=null){
        //
        $data = Array();
        if( isset( $key ) ){
            $list = $this->_data_idx;
            if( isset( $list[$key] ) ){
                $data[] = $list[$key];
            }
        } else {
            $data = $this->_data_raw;
        }
        $this->_result['data'] = $data;
        return $this->_result;
    }

    protected function _getFromTableOrCache(){
        $this->_result = Array();
        $this->_data_raw = Array();
        $this->_data_idx = Array();
        //
        $update_time = $this->table_info->getLastUpdate( $this->table );
        $cache_time  = cache()->get( $this->table . '_time' );
        if( $cache_time!=$update_time || $this->forceLoad ){
            //
            $this->_get_data_from_db();
            cache()->save( $this->table . '_data', $this->_data_raw, $this->expired_cache_time );
            cache()->save( $this->table . '_time', $update_time    , $this->expired_cache_time );
            //
            $this->forceLoad    = false;
            $this->_is_update   = true;
            $this->_status      = true;
            //
        } else {
            //
            $this->_data_raw = cache()->get( $this->table . '_data' );
            $this->_is_update   = false;
            $this->_status      = true;
            //
        }
        $this->_last_update = $update_time;
        $this->_generate_result();
        //
    }

    protected function _get_data_from_db(){
        if( is_null($this->sql_object) ){
            $this->sql_object = $this->_generate_sql_string();
        }
        $this->_last_update = $this->table_info->getLastUpdate( $this->table );
        $this->_data_raw = $this->sql_object->get()->getResultArray();
        $this->_data_indexed();
        $this->_is_update = false;
        $this->_status = true;
    }

    protected function _generate_result(){
        $this->_result['status'] = $this->_status;
        $this->_result['last_update'] = $this->_last_update;
        $this->_result['is_update'] = $this->_is_update;
        $this->_result['data'] = $this->_data_raw;
    }

    protected function _data_indexed(){
        $tmp_data_idx = Array();
        if( isset( $this->primaryKey ) ){
            $tmp_data_idx = array_column( $this->_data_raw, null, $this->primaryKey );
            // foreach( $this->_data_raw as $row ){
            //     if( isset( $row[$this->primaryKey] ) ){
            //         $key = $row[$this->primaryKey];
            //         $tmp_data_idx[$key] = $row;
            //     }
            // }
        }
        $this->_data_idx = $tmp_data_idx;
    }

    protected function _generate_sql_string(){
        $obj_sql = null;
        if( is_null($this->where) ){
            $this->forceLoad = true;
            // $this->where = '1=1';
            if( is_string( $this->filters ) ){
                if( trim($this->filters)!='' ){
                    $this->where = $this->filters;
                }
            } else if(is_array( $this->filters )) {
                if( $this->filters!=[] ){
                    if( isset( $this->filters[0] ) ){
                        $this->where = implode(' AND ', $this->filters);
                    } else {
                        $array_where = [];
                        foreach( $this->filters as $fld=>$val ){
                            if( is_string( $val ) ){
                                $array_where[] = "( `" . trim($fld) . "` = '" . trim( $val . '' ) . "' )";
                            } else if( is_array( $val ) ){
                                $items = [];
                                foreach( $val as $item ){
                                    $items[] = "'" . $item . "'";
                                }
                                $array_where[] = "( `" . trim($fld) . "` IN ( " . implode( ',', $items ) . ") )";
                            }
                        }
                        $this->where = implode( ' AND ', $array_where );
                    }
                }
            } else if( is_object( $this->filters ) ){
                $this->where = $this->filters;
            }
        }
        if( is_null($this->where) ){ $this->where = '1=1'; }
        if( is_null($this->selected_fields) ){ $this->selected_fields = $this->generate_selected_fields(); }
        if( is_null($this->order_by) ){ $this->order_by = $this->table . '.' . $this->primaryKey; }
        if( is_null($this->grouped_fields) ){ 
            $obj_sql = $this->db->table( $this->table )->select( $this->selected_fields )->where($this->where)->orderBy( $this->order_by );
        } else {
            $this->selected_fields .= ', ' . $this->grouped_fields; 
            $obj_sql = $this->db->table( $this->table )->select( $this->selected_fields )->where($this->where)->$this->groupBy($this->group_by)->orderBy( $this->order_by );
        }
        return $obj_sql;
    }

    protected function generate_selected_fields(){
        $selected_fields  = $this->table . '.' . $this->primaryKey;
        foreach( $this->allowedFields as $fld ) { $selected_fields .= ', ' . $this->table . '.' . $fld; }
        $selected_fields .= ', ' . $this->table . '.' . $this->createdField .
                            ', ' . $this->table . '.' . $this->updatedField .
                            ', ' . $this->table . '.' . $this->deletedField;
        return $selected_fields;
    }

    //
    public function set_expired_cache_time($value=3600){ return ( $this->expired_cache_time = $value ); }
    public function get_expired_cache_time(){ return $this->expired_cache_time; }
    //

}

?>