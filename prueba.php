<?php
    require_once("upload.php");
    $subida = new Upload("prueba");
    if ($subida->upload()){
        echo "ok";
    }
    else{
        echo "error al subir";
    }
?>
