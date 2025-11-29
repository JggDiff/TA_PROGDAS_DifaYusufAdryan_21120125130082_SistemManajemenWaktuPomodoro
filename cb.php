<?php
// Menyimpan antrian tugas dalam sesi
session_start();

// Inisialisasi antrian jika belum ada
if (!isset($_SESSION['task_queue'])) {
    $_SESSION['task_queue'] = [];
}

// Tambah tugas ke antrian
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_task') {
    $name = trim($_POST['task_name'] ?? '');
    $sessions = intval($_POST['session_count'] ?? 0);
    if (!empty($name) && $sessions > 0) {
        $_SESSION['task_queue'][] = [
            'name' => htmlspecialchars($name),
            'sessions' => $sessions
        ];
    }
}

// Hapus tugas dari antrian
if (isset($_GET['delete'])) {
    $index = intval($_GET['delete']);
    if (isset($_SESSION['task_queue'][$index])) {
        array_splice($_SESSION['task_queue'], $index, 1);
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Kosongkan antrian
if (isset($_GET['clear'])) {
    $_SESSION['task_queue'] = [];
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Hasilkan jadwal Pomodoro
$schedule = [];
$task_index = 1;
foreach ($_SESSION['task_queue'] as $task) {
    for ($i = 1; $i <= $task['sessions']; $i++) {
        // Sesi kerja
        $schedule[] = [
            'type' => 'work',
            'task' => $task['name'],
            'duration' => 25,
            'label' => "Tugas: {$task['name']} — Sesi $i"
        ];

        // Tentukan istirahat (abaikan setelah sesi terakhir dari seluruh antrian)
        $isLastSession = ($task === end($_SESSION['task_queue']) && $i === $task['sessions']);
        if (!$isLastSession) {
            if ($i % 4 == 0) {
                $schedule[] = ['type' => 'long_break', 'duration' => 15, 'label' => 'Istirahat Panjang'];
            } else {
                $schedule[] = ['type' => 'short_break', 'duration' => 5, 'label' => 'Istirahat Singkat'];
            }
        }
    }
    $task_index++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pomodoro Planner (Pure PHP)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f9f8f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 700px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #e74c3c;
            text-align: center;
        }
        .form-group {
            margin: 15px 0;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }
        button, .btn {
            padding: 10px 16px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 4px 2px;
        }
        .btn-delete {
            background: #e74c3c;
        }
        .btn-clear {
            background: #95a5a6;
        }
        .queue-item {
            background: #e3f2fd;
            padding: 12px;
            margin: 10px 0;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .schedule-item {
            padding: 10px;
            margin: 8px 0;
            border-radius: 6px;
            font-weight: bold;
        }
        .work { background: #e8f5e9; border-left: 4px solid #4caf50; }
        .short_break { background: #e3f2fd; border-left: 4px solid #2196f3; }
        .long_break { background: #fff8e1; border-left: 4px solid #ff9800; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🍅 Pomodoro Planner (Pure PHP)</h1>

        <!-- Form Tambah Tugas -->
        <form method="POST">
            <input type="hidden" name="action" value="add_task">
            <div class="form-group">
                <label for="task_name">Nama Tugas:</label>
                <input type="text" name="task_name" id="task_name" placeholder="Input Tugas" required>
            </div>
            <div class="form-group">
                <label for="session_count">Jumlah Sesi (25 menit per sesi):</label>
                <input type="number" name="session_count" id="session_count" min="1" placeholder="1" required>
            </div>
            <button type="submit">➕ Tambah ke Antrian</button>
        </form>

        <!-- Aksi Antrian -->
        <div style="margin: 20px 0; text-align: center;">
            <?php if (!empty($_SESSION['task_queue'])): ?>
                <a href="?clear" class="btn btn-clear" onclick="return confirm('Kosongkan semua tugas?')">🗑️ Kosongkan Antrian</a>
            <?php endif; ?>
        </div>

        <!-- Daftar Antrian -->
        <h2>Antrian Tugas</h2>
        <?php if (empty($_SESSION['task_queue'])): ?>
            <p style="text-align: center; color: #777;">Belum ada tugas dalam antrian.</p>
        <?php else: ?>
            <?php foreach ($_SESSION['task_queue'] as $index => $task): ?>
                <div class="queue-item">
                    <div>
                        <strong><?= $index + 1 ?>. <?= $task['name'] ?></strong><br>
                        <small><?= $task['sessions'] ?> sesi</small>
                    </div>
                    <a href="?delete=<?= $index ?>" class="btn btn-delete" onclick="return confirm('Hapus tugas ini?')">🗑️ Hapus</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Jadwal Pomodoro -->
        <?php if (!empty($schedule)): ?>
            <h2>Jadwal Pomodoro</h2>
            <?php $totalTime = 0; ?>
            <?php foreach ($schedule as $item): ?>
                <?php $totalTime += $item['duration']; ?>
                <div class="schedule-item <?= $item['type'] ?>">
                    <?= $item['label'] ?> — <strong><?= $item['duration'] ?> menit</strong>
                </div>
            <?php endforeach; ?>
            <div style="margin-top: 15px; padding: 12px; background: #f1f8e9; border-radius: 8px;">
                <strong>Total Perkiraan Waktu:</strong> <?= $totalTime ?> menit (≈ <?= round($totalTime / 60, 1) ?> jam)
            </div>
        <?php endif; ?>
    </div>
</body>
</html>