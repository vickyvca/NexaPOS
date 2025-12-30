function syncDatabase() {
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

    $tsqlTables = "SELECT name FROM MODECENTRE.sys.tables";
    $getResultsTables = sqlsrv_query($connA, $tsqlTables);
    if ($getResultsTables === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    while ($rowTables = sqlsrv_fetch_array($getResultsTables, SQLSRV_FETCH_ASSOC)) {
        $tableName = $rowTables['name'];

        $tsqlSync = "INSERT INTO MODECENTRE.dbo.$tableName SELECT * FROM [192.168.4.99].MODECENTRE.dbo.$tableName";
        $getResultsSync = sqlsrv_query($connB, $tsqlSync);
        if ($getResultsSync === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    }

    sqlsrv_close($connA);
    sqlsrv_close($connB);

    echo "Data synchronization of the entire MODECENTRE database between MODE A and MODE B completed successfully.";
}
