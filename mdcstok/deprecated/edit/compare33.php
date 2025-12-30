<!DOCTYPE html>
<html>
<head>
    <style>
        /* Your CSS styling here */
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1px solid black;
            padding: 8px;
        }

        th {
            text-align: left;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            margin: 5px;
        }

        .btn:hover {
            background-color: #45a049;
        }

        .btn-sync {
            background-color: #008CBA;
        }
    </style>
</head>
<body>
    <?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    function getDBConnection($serverName, $dbName, $username, $password) {
        $connectionOptions = array(
            "Database" => $dbName,
            "Uid" => $username,
            "PWD" => $password
        );
        $conn = sqlsrv_connect($serverName, $connectionOptions);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        return $conn;
    }

    function compareData() {
        // Add your MODE_A and MODE_B connection details here
        $serverNameA = "192.168.4.99";
        $dbNameA = "MODECENTRE";
        $usernameA = "sa";
        $passwordA = "mode1234ABC";
        $connA = getDBConnection($serverNameA, $dbNameA, $usernameA, $passwordA);

        $serverNameB = "192.168.4.8";
        $dbNameB = "MODECENTRE";
        $usernameB = "sa";
        $passwordB = "mode1234ABC";
        $connB = getDBConnection($serverNameB, $dbNameB, $usernameB, $passwordB);

        // Compare T_STOK with T_BARANG
        $tsqlStokA = "SELECT T_BARANG.KODEBRG, T_STOK.ST01 AS DIPAYUDA_A FROM dbo.T_STOK JOIN dbo.T_BARANG ON T_STOK.ID = T_BARANG.ID";
        $tsqlStokB = "SELECT T_BARANG.KODEBRG, T_STOK.ST01 AS DIPAYUDA_B FROM dbo.T_STOK JOIN dbo.T_BARANG ON T_STOK.ID = T_BARANG.ID";

        $getResultsStokA = sqlsrv_query($connA, $tsqlStokA);
        $getResultsStokB = sqlsrv_query($connB, $tsqlStokB);

        if ($getResultsStokA === false || $getResultsStokB === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        $data = array();
        while ($rowStokA = sqlsrv_fetch_array($getResultsStokA, SQLSRV_FETCH_ASSOC)) {
            $kodebrg = $rowStokA['KODEBRG'];
            $dipayudaA = $rowStokA['DIPAYUDA_A'];

            $rowStokB = sqlsrv_fetch_array($getResultsStokB, SQLSRV_FETCH_ASSOC);
            if ($rowStokB === false) {
                break; // No more data in MODE B, exit the loop
            }
            $dipayudaB = $rowStokB['DIPAYUDA_B'];

            // Show only data where MODE B value is less than MODE A
            if ($dipayudaB < $dipayudaA) {
                $data[] = array('KODEBRG' => $kodebrg, 'DIPAYUDA_A' => $dipayudaA, 'DIPAYUDA_B' => $dipayudaB);
            }
        }

        sqlsrv_close($connA);
        sqlsrv_close($connB);

        return $data;
    }

    function updateData($serverName, $dbName, $kodebrg, $st01, $username, $password) {
        $conn = getDBConnection($serverName, $dbName, $username, $password);

        $tsqlUpdate = "UPDATE dbo.T_STOK SET ST01 = ? WHERE ID IN (SELECT ID FROM dbo.T_BARANG WHERE KODEBRG = ?)";
        $params = array($st01, $kodebrg);
        $getResultsUpdate = sqlsrv_query($conn, $tsqlUpdate, $params);
        if ($getResultsUpdate === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        sqlsrv_close($conn);
    }

    if (isset($_POST['compare'])) {
        $data = compareData();
    }

    if (isset($_POST['update'])) {
        $kodebrg = $_POST['kodebrg'];
        $st01 = $_POST['st01'];

        // Update data in MODE A
        updateData("192.168.4.99", "MODECENTRE", $kodebrg, $st01, "sa", "mode1234ABC");

        // If data does not exist in MODE B, insert data from MODE A into MODE B
        updateData("192.168.4.8", "MODECENTRE", $kodebrg, $st01, "sa", "mode1234ABC");

        echo '<script>alert("Data updated successfully in MODE A and MODE B!");</script>';
    }
    ?>

    <h2>Data Comparison Between MODE A and MODE B (DIPAYUDA)</h2>

    <form method="post" action="compare.php">
        <input type="hidden" name="compare" value="1">
        <input class="btn" type="submit" value="Compare and Show DIPAYUDA Differences">
    </form>

    <?php if (!empty($data)) { ?>
        <table>
            <tr>
                <th>KODE BARANG</th>
                <th>DIPAYUDA A</th>
                <th>DIPAYUDA B</th>
                <th>Update DIPAYUDA A</th>
            </tr>
            <?php foreach ($data as $row) { ?>
                <tr>
                    <td><?php echo $row['KODEBRG']; ?></td>
                    <td><?php echo $row['DIPAYUDA_A']; ?></td>
                    <td><?php echo $row['DIPAYUDA_B']; ?></td>
                    <td>
                        <form method="post" action="compare.php">
                            <input type="hidden" name="update" value="1">
                            <input type="hidden" name="kodebrg" value="<?php echo $row['KODEBRG']; ?>">
                            <input type="text" name="st01" value="<?php echo $row['DIPAYUDA_A']; ?>">
                            <input class="btn" type="submit" value="Update">
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </table>
    <?php } else if (isset($_POST['compare'])) {
        echo "<p>No differences found between MODE A and MODE B (DIPAYUDA) where MODE B value is less than MODE A.</p>";
    } ?>

    <!-- Rest of the HTML and CSS styling -->
</body>
</html>
