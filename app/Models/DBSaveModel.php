<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;
use App\Config\CURLRequest;
use App\Libraries\TableInfo;

class DBSaveModel extends DBReadModel {

    public function __construct($params=null){
        parent::__construct($params);
    }

    public function save_data($data=null){
        $result = 0;
        if( !is_null($data) ){
            $newData = Array();
            foreach($data as $key=>$val){
                $newData[$key] = (trim($val)=='') ? NULL : trim($val);
            }
            if( $this->save($newData) ){
                if( !isset($newData[$this->primaryKey]) || (1*$newData[$this->primaryKey]==0) ){
                    $result = $this->insertID;
                } else {
                    $result = $newData[$this->primaryKey];
                }
            }
        }
        if($result){
            $this->forceLoad = true;
            $this->_getFromTableOrCache();
        }
        return $result;
    }

    public function revive($id=null){
        $result = false;
        if( is_null($id) ){
            if( isset( $_REQUEST['id'] ) ){
                $id = $_REQUEST['id'];
            }
        }
        if( !is_null($id) ){
            $ck0 = $this->db->table( $this->table )->select( $this->deletedField )->where("`" . $this->primaryKey . "`='$id'" )->get()->getResultArray();
            if( count( $ck0 )>0 ){
                $result = true;
                return $this->db->table( $this->table )->where( $this->primaryKey, $id )->set( $this->deletedField, 'NULL', false )->update();
            }
        }
        if( !$result ){
            return $result;
        }
    }

    public function del($id=null){
      $result = false;
        if( is_null($id) ){
            if(isset($_REQUEST['id'])){
                $id = $_REQUEST['id'];
            }
        }
        if( !is_null($id) ){
            $ck0 = $this->db->table( $this->table )->select( $this->deletedField )->where("`" . $this->primaryKey . "`='$id'" )->get()->getResultArray();
            if( count( $ck0 )>0 ){
                $ck1 = $ck0[0];
                if( is_null($ck1[ $this->deletedField ]) ){
                    $result = true;
                    return $this->db->table( $this->table )->where( $this->primaryKey, $id )->set( $this->deletedField, 'NOW()', false )->update();
                }
            }
        }
        if( !$result ){
            return $result;
        }
    }

    public function kill($id=null){
        $result = false;
        if( is_null($id) ){
            if(isset( $_REQUEST['id'] )){
                $id = $_REQUEST['id'];
            }
        }
        if( !is_null($id) ){
            $builder = $this->db->table( $this->table );
            $result = true;
            return $builder->delete([$this->primaryKey=>$id]);
        } else {
            return $result;
        }
    }

}