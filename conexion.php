<?php
    try {
        $liga = mysqli_connect("localhost","root","211205","pap");
        if (!$liga) {
            throw new Exception("Error ao conectar: " . mysqli_connect_error());
        }
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
        exit;
    }
global $liga;