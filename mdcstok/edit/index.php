<!DOCTYPE html>
<html>
<head>
    <title>Stok Barang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
        }
        h1 {
            margin-top: 20px;
            color: #007bff;
        }
        table {
            border-collapse: collapse;
            width: 80%;
            margin: 20px auto;
        }
        th, td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .edit-btn {
            background-color: #28a745;
            display: block;
            margin: 20px auto;
            padding: 8px 16px;
            text-decoration: none;
            color: white;
            width: 100px;
            border-radius: 5px;
        }
        .edit-btn:hover {
            background-color: #1b7e31;
        }
        .edit-form {
            display: none;
            width: 80%;
            margin: 20px auto;
        }
        .edit-form input {
            width: 100%;
            margin-bottom: 10px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .edit-form button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .edit-form button:hover {
            background-color: #0056b3;
        }
        .edit-form label {
            display: inline-block;
            text-align: left;
            width: 150px;
            margin-bottom: 5px;
        }
        .result-form {
            margin: 20px auto;
            width: 80%;
        }
        .result-form label {
            display: inline-block;
            text-align: left;
            width: 150px;
            margin-bottom: 5px;
        }
        /* Styles for the third query result table */
        .result-table {
            border-collapse: collapse;
            width: 80%;
            margin: 20px auto;
        }
        .result-table th, .result-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .result-table th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Stok Barang</h1>
    <form method="post">
        <label for="kodebrg">KODE BARANG</label>
        <input type="text" name="kodebrg" id="kodebrg" placeholder="Masukan Kode Barang" style="width: 200px;">
        <button type="submit" name="cari">Cari</button>
    </form>

    <?php
    $serverName = "192.168.4.99";
    $connectionOptions = array(
        "Database" => "MODECENTRE",
        "Uid" => "sa",
        "PWD" => "mode1234ABC"
    );

    // Function to execute the first query and fetch data
function executeFirstQuery($conn, $kodebrg) {
    $sql = "SELECT T_STOK.ID, T_BARANG.KODEBRG, NAMABRG, ARTIKELBRG, ST01, ST03, ST00, ST99
            FROM T_BARANG
            JOIN T_STOK ON T_BARANG.id = T_STOK.id
            WHERE T_BARANG.KODEBRG = ?";
    $params = array($kodebrg);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);

    return $result;
}

    // Function to execute the second query to update data
    function executeSecondQuery($conn, $id, $st01, $st03, $st00, $st99) {
        $sql = "UPDATE T_STOK
                SET ST01 = ?, ST03 = ?, ST00 = ?, ST99 = ?
                WHERE ID = ?";
        $params = array($st01, $st03, $st00, $st99, $id);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        sqlsrv_free_stmt($stmt);
    }

    // Function to execute the third query to fetch updated data
function executeThirdQuery($conn, $kodebrg) {
    $sql = "SELECT T_STOK.ID, T_BARANG.KODEBRG, NAMABRG, ARTIKELBRG, ST01, ST03, ST00, ST99
            FROM T_BARANG
            JOIN T_STOK ON T_BARANG.id = T_STOK.id
            WHERE T_BARANG.KODEBRG = ?";
    $params = array($kodebrg);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);

    return $result;
}

    // Check if the form is submitted and execute the update query
    if (isset($_POST['update'])) {
        $kodebrg = $_POST['kodebrg'];
        $st01 = $_POST['edit_st01'];
        $st03 = $_POST['edit_st03'];
        $st00 = $_POST['edit_st00'];
        $st99 = $_POST['edit_st99'];

        $conn = sqlsrv_connect($serverName, $connectionOptions);
        if ($conn) {
            $result = executeFirstQuery($conn, $kodebrg);
            if ($result) {
                executeSecondQuery($conn, $result["ID"], $st01, $st03, $st00, $st99);
                $updatedResult = executeThirdQuery($conn, $kodebrg);
            }
            sqlsrv_close($conn);
        } else {
            echo "Connection could not be established.";
            die(print_r(sqlsrv_errors(), true));
        }
    }

    // Check if the form is submitted and execute the first query
    if (isset($_POST['cari'])) {
        $kodebrg = $_POST['kodebrg'];
        $conn = sqlsrv_connect($serverName, $connectionOptions);
        if ($conn) {
            $result = executeFirstQuery($conn, $kodebrg);

            if ($result) {
                echo "<table>";
                echo "<tr><th>Kode Barang</th><td>" . $result["KODEBRG"] . "</td></tr>";
                echo "<tr><th>Nama</th><td>" . $result["NAMABRG"] . "</td></tr>";
                echo "<tr><th>Artikel</th><td>" . $result["ARTIKELBRG"] . "</td></tr>";
                echo "<tr><th>Dipayuda</th><td>" . $result["ST01"] . "</td></tr>";
                echo "<tr><th>Pemuda</th><td>" . $result["ST03"] . "</td></tr>";
                echo "<tr><th>Gudang</th><td>" . $result["ST00"] . "</td></tr>";
                echo "<tr><th>Rusak</th><td>" . $result["ST99"] . "</td></tr>";
                echo "</table>";

                echo "<a href='javascript:void(0);' onclick='showEditForm();' class='edit-btn'>Edit</a>";
                echo "<div class='edit-form' id='editForm'>";
                echo "<form method='post'>";
                echo "<input type='hidden' name='kodebrg' value='" . $kodebrg . "'>";
                echo "<label for='edit_st01'>Dipayuda</label>";
                echo "<input type='text' name='edit_st01' id='edit_st01' placeholder='DIPAYUDA' value='" . $result["ST01"] . "'>";
                echo "<label for='edit_st03'>Pemuda</label>";
                echo "<input type='text' name='edit_st03' id='edit_st03' placeholder='PEMUDA' value='" . $result["ST03"] . "'>";
                echo "<label for='edit_st00'>Gudang</label>";
                echo "<input type='text' name='edit_st00' id='edit_st00' placeholder='GUDANG PUSAT' value='" . $result["ST00"] . "'>";
                echo "<label for='edit_st99'>Rusak</label>";
                echo "<input type='text' name='edit_st99' id='edit_st99' placeholder='RUSAK' value='" . $result["ST99"] . "'>";
                echo "<button type='submit' name='update'>Update</button>";
                echo "</form>";
                echo "</div>";
            } else {
                echo "No data found for KODEBRG: $kodebrg";
            }
            sqlsrv_close($conn);
        } else {
            echo "Connection could not be established.";
            die(print_r(sqlsrv_errors(), true));
        }
    }
    ?>

    <!-- Third query result table -->
    <?php
    if (isset($updatedResult) && $updatedResult) {
        echo "<div class='result-form'>";
        echo "<h2>Third Query Result</h2>";
        echo "<table class='result-table'>";
        echo "<tr><th>Kode Barang</th><td>" . $updatedResult["KODEBRG"] . "</td></tr>";
        echo "<tr><th>Nama</th><td>" . $updatedResult["NAMABRG"] . "</td></tr>";
        echo "<tr><th>Artikel</th><td>" . $updatedResult["ARTIKELBRG"] . "</td></tr>";
        echo "<tr><th>Dipayuda</th><td>" . $updatedResult["ST01"] . "</td></tr>";
        echo "<tr><th>Pemuda</th><td>" . $updatedResult["ST03"] . "</td></tr>";
        echo "<tr><th>Gudang</th><td>" . $updatedResult["ST00"] . "</td></tr>";
        echo "<tr><th>Rusak</th><td>" . $updatedResult["ST99"] . "</td></tr>";
        echo "</table>";
        echo "</div>";
    }
    ?>
    
    <script>
        function showEditForm() {
            var editForm = document.getElementById('editForm');
            if (editForm.style.display === 'none') {
                editForm.style.display = 'block';
            } else {
                editForm.style.display = 'none';
            }
        }
    </script>
</body>
</html>
