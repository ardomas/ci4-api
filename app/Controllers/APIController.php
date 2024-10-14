<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\IncomingRequest;

use App\Config\CURLRequest;
use App\Libraries\TableInfo;

class APIController extends ResourceController {

    protected $table_info;
    protected $model;

    public function __construct(){
        // $this->table_info  = new TableInfo($this->model->DBGroup);
    }
    public function init(){
        // do something that will be called from child
    }

    public function index(){
        return view('forbidden-page');
    }

    public function get($key=null){
        return $this->respond( $this->model->get($key) );
    }

    public function getraw(){
        return $this->respond( $this->model->getraw() );
    }

    public function getbyfilter($fld,$idx){
        return $this->respond( $this->model->getbyfilter($fld,$idx), 200 );
    }

    public function revive($key=null){
        return $this->respond( $this->model->revive($key), 200 );
    }

    public function del($key=null){
        return $this->respond( $this->model->del($key), 200 );
    }

    public function kill($key=null){
        return $this->respond($this->model->kill($key),200);
    }

    public function set($data=null){
        $result = false;
        $newData = Array();
        if(is_null($data)){
            if( !is_null($this->model) ){
                $data = $this->_getFromXHR();
            }
        }
        $result = $this->model->save_data($data);
        return $this->respond($result,200);
    }
    public function setFilter( $array ){
        $this->model->setFilter( $array );
    }

    public function getDataTree(){
        return $this->respond( $this->model->getDataTree(), 200 );
    }

    public function getTableInfo(){
        return $this->respond( $this->table_info->index(), 200 );
    }
    public function getCache(){
        return $this->respond( $this->model->_getCache(), 200 );
    }

    public function getFieldNames($table=null){
        if( is_null($table) ){
            $table = $this->model->table;
        }
        return $this->respond( $this->model->getFieldNames($table), 200 );
    }
    public function getFieldData($table=null){
        if( is_null($table) ){
            $table = $this->model->table;
        }
        return $this->respond( $this->model->getFieldData($table), 200 );
    }

    public function getlist(){
        $params = $this->request->getVar();
        $retval = array();
        if( !is_null($params) ){
            $retval = $this->respond( $this->model->getlist($params), 200 );
        }
        return $retval;
    }

    public function getlistraw(){
        $params = $this->request->getVar();
        $retval = array();
        if( !is_null($params) ){
            $retval = $this->respond( $this->model->getlistraw($params), 200 );
        }
        return $retval;
    }

    public function _getFromXHR(){
        $result = array();
        if( !is_null($this->model) ){
            $allowedFields = $this->model->allowedFields;
            if( !is_null( $this->model->primaryKey ) && (trim($this->model->primaryKey)!='') ){
                $allowedFields[] = $this->model->primaryKey;
            }
            $retVar = $this->request->getVar();
            foreach( $retVar as $key=>$val ){
                if( in_array( $key, $allowedFields ) ){
                    $result[$key] = $val;
                }
            }
        }
        return $result;
    }

    public function chk_fields(){
        return $this->respond($this->model->getFieldNames($this->model->table), 200);
    }

    public function chk_fields_metadata(){
        $tmpdata  = $this->_chk_fields_metadata();
        $metadata = Array();
        foreach($tmpdata as $fldnum=>$itemdata){
            // $itemdata = $tmpdata[$fldnum];
            $fieldkey = $itemdata['name'];
            $metdata[$fieldkey] = $itemdata;
        }
        return $this->respond($metadata,200);
    }

    public function _chk_fields_metadata(){
        return $this->respond($this->model->getFieldData($this->model->table),200);
    }

}

?>