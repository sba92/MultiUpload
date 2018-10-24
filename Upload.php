<?php

/*
Resumen de errores:
1 - no existe el archivo que se va a subir o el archivo no se ha subido
2 - el tamaño del archivo excede el máximo, o el tipo no es válido
3 - ya existe un archivo con ese nombre
4 - ha fallado move_uploaded_file()
*/

class Upload {

    const   POLICY_RENAME= 1,
            POLICY_OVERWRITE = 2,
            POLICY_KEEP   = 3,
            MIN_OWN_ERROR = 1000;

    private $error = 0,
            $file,
            $input,
            $maxSize = 0,
            $name,
            $policy = self::POLICY_OVERWRITE,
            $savedName = [] ,
            $target = './',
            $type = '';

    function __construct($input) {
        $this->input = $input;
        if(!isset($_FILES[$input])){
            $this->error = 1;
        }
        else{
            $e=false;
            foreach($_FILES[$input]['error'] as $err){
                if ($err !== 0){
                    $e=true;
                    break;
                }
            }
            if ($e){
                $this->error = 1;
            }
            else{
                $this->file = $_FILES[$input];
                $this->name = $this->file['name'];
            }
        }
    }
    
    private function __doUpload() {
        $result = false;
        switch($this->policy) {
            case self::POLICY_KEEP:
                $result = $this->__doUploadKeep();
                break;
            case self::POLICY_OVERWRITE:
                $result = $this->__doUploadOverwrite();
                break;
            case self::POLICY_RENAME:
                $result = $this->__doUploadRename();
                break;
        }
        if(!$result && $this->error === 0){
            $this->error = 4;
        }
        return $result;
    }
    
    private function __doUploadKeep() {
        $result = false;
        foreach($this->name as $clave=>$valor){
            if(file_exists($this->target . $valor) === true) {
                $this->error = 3;
                return $result;
            }
        }
        foreach($this->file["tmp_name"] as $clave => $valor){
            $result = move_uploaded_file($valor, $this->target . $this->name[$clave]);
            if (!$result){
                $this->error = 3;
                break;
            }
        }
        return $result;
    }
    
    private function __doUploadOverwrite() {
        $result=false;
        foreach($this->file["tmp_name"] as $clave => $valor){
            $result = move_uploaded_file($valor, $this->target . $this->name[$clave]);
            if (!$result){
                $this->error = 3;
                break;
            }
        }
        return $result;
    }
    
    private function __doUploadRename() {
        foreach($this->file["tmp_name"] as $clave=>$valor){
            $newName=$this->target . $this->name[$clave];
            if(file_exists($newName)) {
                $newName = self::__getValidName($newName);
            }
            $result = move_uploaded_file($valor, $newName);
            if (!$result){
                $this->error = 3;
                break;
            }
            else{
                $nombre = pathinfo($newName);
                $nombre = $nombre['basename'];
                array_push($this->savedName, $nombre);
            }
        }
        return $result;
    }
    
    private static function __getValidName($file) {
        $parts = pathinfo($file);
        $extension = '';
        if(isset($parts['extension'])) {
            $extension = '.' . $parts['extension'];
        }
        $cont = 0;
        while(file_exists($parts['dirname'] . '/' . $parts['filename'] . $cont . $extension)) {
            $cont++;
        }
        return $parts['dirname'] . '/' . $parts['filename'] . $cont . $extension;
    }

    function getError() {
        $error = $this->error + self::MIN_OWN_ERROR;
        if($error === self::MIN_OWN_ERROR) {
            $error = $this->file['error'];
        }
        return $error;
    }

    function getMaxSize() {
        return $this->maxSize;
    }
    
    function getNames() {
        return $this->savedName;
    }

    function isValidSize() {
        $pesoTotal=0;
        foreach($this->file["size"] as $peso){
            $pesoTotal=$pesoTotal+$peso;
        }
        return ($this->maxSize === 0 || $this->maxSize >= $pesoTotal);
    }

    function isValidType() {
        $valid = true;
        if($this->type !== '') {
            $tipo = shell_exec('file --mime ' . $this->file['tmp_name']);
            $posicion = strpos($tipo, $this->type);
            if($posicion === false) {
                $valid = false;
            }
        }
        return $valid;
    }

    function setMaxSize($size) {
        if(is_int($size) && $size > 0) {
            $this->maxSize = $size;
        }
        return $this;
    }

    function setName($clave, $name) {
        if(is_string($name) && trim($name) !== '') {
            $this->name[$clave] = trim($name);
        }
        return $this;
    }

    function setPolicy($policy) {
        if(is_int($policy) && $policy >= self::POLICY_KEEP && $policy <= self::POLICY_RENAME) {
            $this->policy = $policy;
        }
        return $this;
    }

    function setTarget($target) {
        if(is_string($target) && trim($target) !== '') {
            $this->target = trim($target);
        }
        return $this;
    }

    function setType($type) {
        if(is_string($type) && trim($type) !== '') {
            $this->type = trim($type);
        }
        return $this;
    }

    function upload() {
        $result = false;
        if($this->error !== 1) {
            if($this->isValidSize() && $this->isValidType()) {
                $this->error = 0;
                $result = $this->__doUpload();
            } else {
                $this->error = 2;
            }
        }
        return $result;
    }

}