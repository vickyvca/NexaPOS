<?php
$serverNameA = "192.168.4.99"; 
$connectionOptionsA = array(
    "Database" => "MODECENTRE",
    "Uid" => "sa",
    "PWD" => "mode1234ABC"
);
$connA = sqlsrv_connect($serverNameA, $connectionOptionsA);

$serverNameB = "192.168.4.8"; 
$connectionOptionsB = array(
    "Database" => "MODECENTRE",
    "Uid" => "sa",
    "PWD" => "mode1234ABC"
);
$connB = sqlsrv_connect($serverNameB, $connectionOptionsB);

if(isset($_POST['ST01']) && isset($_POST['ST03']) && isset($_POST['ST00']) && isset($_POST['ST99']))
{
    $id = $_POST['id'];
    $dipayuda = $_POST['ST01'];
    $pemuda = $_POST['ST03'];
    $gudang = $_POST['ST00'];
    $rusak = $_POST['ST99'];

    $tsqlA = "UPDATE dbo.T_STOK SET ST01 = ?, ST03 = ?, ST00 = ?, ST99 = ? WHERE ID = ?";
    $tsqlB = "UPDATE dbo.T_STOK SET ST01 = ?, ST03 = ?, ST00 = ?, ST99 = ? WHERE ID = ?";

    $paramsA = array($dipayuda, $pemuda, $gudang, $rusak, $id);
    $paramsB = array($dipayuda, $pemuda, $gudang, $rusak, $id);

    $getResultsA = sqlsrv_query($connA, $tsqlA, $paramsA);
    $getResultsB = sqlsrv_query($connB, $tsqlB, $paramsB);

    if ($getResultsA == FALSE || $getResultsB == FALSE) {
        die(print_r(sqlsrv_errors(), true));
    }

    // Redirect back to the comparison page after updating
    header("Location: compare.php");
    exit();
}
else {
    // Redirect back to the comparison page if data is not set
    header("Location: compare.php");
    exit();
}
