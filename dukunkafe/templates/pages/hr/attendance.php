
<?php

require_auth(['admin', 'hr']);

$title = 'HR - Attendance Report';
$pdo = get_pdo($config);

// Filters
$employee_id = $_GET['employee_id'] ?? 'all';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Fetch employees for dropdown
$employees = $pdo->query('SELECT id, name FROM employees WHERE active = 1 ORDER BY name')->fetchAll();

// Base query
$sql = "
    SELECT 
        e.name AS employee_name,
        a.ts AS timestamp,
        a.type AS event_type
    FROM attendances a
    JOIN employees e ON a.employee_id = e.id
    WHERE DATE(a.ts) BETWEEN ? AND ?
";

$params = [$start_date, $end_date];

if ($employee_id !== 'all') {
    $sql .= " AND a.employee_id = ?";
    $params[] = $employee_id;
}

$sql .= " ORDER BY e.name, a.ts ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$raw_logs = $stmt->fetchAll();

// Process logs to pair IN and OUT
$report_data = [];
$temp_log = [];

foreach ($raw_logs as $log) {
    $name = $log['employee_name'];
    $date = date('Y-m-d', strtotime($log['timestamp']));

    if ($log['event_type'] === 'IN') {
        // If there was a dangling IN, save it before starting a new one
        if (isset($temp_log[$name])) {
            $report_data[] = $temp_log[$name];
        }
        $temp_log[$name] = [
            'name' => $name,
            'date' => $date,
            'clock_in' => $log['timestamp'],
            'clock_out' => null,
            'duration' => null
        ];
    } elseif ($log['event_type'] === 'OUT' && isset($temp_log[$name])) {
        $temp_log[$name]['clock_out'] = $log['timestamp'];
        $in_time = new DateTime($temp_log[$name]['clock_in']);
        $out_time = new DateTime($log['timestamp']);
        $interval = $in_time->diff($out_time);
        $temp_log[$name]['duration'] = $interval->format('%H:%I:%S');
        $report_data[] = $temp_log[$name];
        unset($temp_log[$name]); // Clear after pairing
    }
}

// Add any remaining dangling IN logs
foreach ($temp_log as $log) {
    $report_data[] = $log;
}

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_report_' . date('Ymd') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Employee Name', 'Clock In', 'Clock Out', 'Duration (H:M:S)']);

    foreach ($report_data as $row) {
        fputcsv($output, [
            $row['date'],
            $row['name'],
            $row['clock_in'] ? date('Y-m-d H:i:s', strtotime($row['clock_in'])) : '',
            $row['clock_out'] ? date('Y-m-d H:i:s', strtotime($row['clock_out'])) : '',
            $row['duration']
        ]);
    }
    fclose($output);
    exit();
}

view('hr/attendance', [
    'title' => $title,
    'employees' => $employees,
    'report_data' => $report_data,
    'filters' => [
        'employee_id' => $employee_id,
        'start_date' => $start_date,
        'end_date' => $end_date
    ]
]);
