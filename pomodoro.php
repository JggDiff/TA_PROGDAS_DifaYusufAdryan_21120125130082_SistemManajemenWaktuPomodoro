<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pomodoro: Antrian Otomatis</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f9f8f4;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 700px;
            margin: auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            padding: 25px;
        }
        h1 {
            text-align: center;
            color: #e74c3c;
            margin-top: 0;
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
        button {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            margin: 4px;
        }
        #add-task-btn {
            background: #2ecc71;
            color: white;
            width: 100%;
            font-size: 18px;
            padding: 12px;
        }
        #start-queue-btn {
            background: #e74c3c;
            color: white;
            width: 100%;
            font-size: 18px;
            padding: 12px;
            margin-top: 10px;
        }
        .queue-list {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            max-height: 250px;
            overflow-y: auto;
        }
        .queue-item {
            padding: 10px;
            background: #e3f2fd;
            margin: 8px 0;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .queue-item-info {
            flex: 1;
            text-align: left;
        }
        .queue-item-actions {
            display: flex;
            gap: 6px;
        }
        .delete-btn {
            background: #e74c3c;
            color: white;
            padding: 6px 10px;
            font-size: 14px;
        }
        .timer-section {
            margin-top: 25px;
            padding: 20px;
            border: 2px solid #eee;
            border-radius: 12px;
            background: #fafafa;
        }
        #timer-display {
            font-size: 64px;
            font-weight: bold;
            margin: 15px 0;
            color: #2c3e50;
            font-family: monospace;
        }
        #status {
            font-size: 20px;
            font-weight: bold;
            margin: 10px 0;
            color: #e74c3c;
            min-height: 26px;
        }
        .hidden { display: none; }
        .break-buttons, .controls {
            margin: 15px 0;
        }
        .btn-short { background: #3498db; color: white; }
        .btn-long { background: #e67e22; color: white; }
        .session-info {
            background: #e8f5e9;
            padding: 8px;
            border-radius: 6px;
            margin: 10px 0;
            font-weight: bold;
        }
        #empty-queue {
            text-align: center;
            color: #777;
            font-style: italic;
        }
        .running-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            background: #2ecc71;
            border-radius: 50%;
            margin-right: 6px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🍅 Pomodoro: Antrian Otomatis</h1>

        <!-- Form Input Tugas -->
        <div class="form-group">
            <label for="task-name">Nama Tugas:</label>
            <input type="text" id="task-name" placeholder="Contoh: Belajar PHP" required>
        </div>
        <div class="form-group">
            <label for="session-count">Jumlah Sesi (1 sesi = 25 menit):</label>
            <input type="number" id="session-count" min="1" value="2" required>
        </div>
        <button id="add-task-btn">➕ Tambah ke Antrian</button>
        <button id="start-queue-btn">▶️ Jalankan Antrian</button>

        <!-- Daftar Antrian -->
        <div class="queue-list">
            <h3>Antrian Tugas:</h3>
            <div id="queue-items"></div>
            <p id="empty-queue">Tidak ada tugas dalam antrian.</p>
        </div>

        <!-- Timer Section -->
        <div id="timer-section" class="timer-section hidden">
            <div id="current-task-info">
                <strong>Tugas Saat Ini:</strong> <span id="current-task-name"></span>
            </div>
            <div class="session-info">
                Sesi: <span id="session-current">0</span> / <span id="session-total">0</span>
            </div>
            <div id="status">Fokus: <span id="status-task"></span></div>
            <div id="timer-display">25:00</div>
            <div class="controls">
                <button id="pause-btn">⏸️ Pause</button>
                <button id="reset-btn">⏹️ Reset</button>
                <button id="stop-queue-btn">⏹️ Hentikan Antrian</button>
            </div>
            <div id="break-buttons" class="break-buttons hidden">
                <p><strong>Sesi selesai! Pilih istirahat:</strong></p>
                <button class="btn-short" onclick="startBreak(5)">🕗 Istirahat Singkat (5 menit)</button>
                <button class="btn-long" onclick="startBreak(15)">🕛 Istirahat Panjang (15 menit)</button>
            </div>
        </div>
    </div>

    <script>
        let taskQueue = [];
        let currentTaskIndex = -1;
        let isRunning = false;
        let isPaused = false;

        // Timer state
        let currentSession = 0;
        let totalSessions = 0;
        let currentTaskName = '';
        let currentMode = 'work';
        let timeLeft = 25 * 60;
        let timerInterval = null;

        // DOM
        const taskNameInput = document.getElementById('task-name');
        const sessionCountInput = document.getElementById('session-count');
        const addTaskBtn = document.getElementById('add-task-btn');
        const startQueueBtn = document.getElementById('start-queue-btn');
        const stopQueueBtn = document.getElementById('stop-queue-btn');
        const queueItemsDiv = document.getElementById('queue-items');
        const emptyQueueMsg = document.getElementById('empty-queue');
        const timerSection = document.getElementById('timer-section');
        const timerDisplay = document.getElementById('timer-display');
        const statusDisplay = document.getElementById('status');
        const statusTaskSpan = document.getElementById('status-task');
        const currentTaskNameSpan = document.getElementById('current-task-name');
        const sessionCurrentSpan = document.getElementById('session-current');
        const sessionTotalSpan = document.getElementById('session-total');
        const breakButtons = document.getElementById('break-buttons');

        // Render antrian
        function renderQueue() {
            if (taskQueue.length === 0) {
                queueItemsDiv.innerHTML = '';
                emptyQueueMsg.classList.remove('hidden');
                startQueueBtn.disabled = true;
            } else {
                emptyQueueMsg.classList.add('hidden');
                startQueueBtn.disabled = false;
                queueItemsDiv.innerHTML = taskQueue.map((task, i) => {
                    const isCurrent = isRunning && i === currentTaskIndex;
                    return `
                        <div class="queue-item">
                            <div class="queue-item-info">
                                ${isCurrent ? '<span class="running-indicator"></span>' : ''}
                                <strong>${i + 1}. ${task.name}</strong><br>
                                <small>${task.totalSessions} sesi</small>
                            </div>
                            <div class="queue-item-actions">
                                <button class="delete-btn" onclick="removeTask(${i})">🗑️ Hapus</button>
                            </div>
                        </div>
                    `;
                }).join('');
            }
        }

        // Tambah tugas
        addTaskBtn.addEventListener('click', () => {
            const name = taskNameInput.value.trim();
            const sessions = parseInt(sessionCountInput.value);
            if (!name || sessions < 1) {
                alert('Nama tugas tidak boleh kosong dan sesi minimal 1!');
                return;
            }
            taskQueue.push({ name, totalSessions: sessions });
            taskNameInput.value = '';
            sessionCountInput.value = 2;
            renderQueue();
        });

        // Hapus tugas
        window.removeTask = function(index) {
            if (isRunning && index <= currentTaskIndex) {
                alert('Tidak bisa menghapus tugas yang sedang/sudah dijalankan!');
                return;
            }
            if (confirm(`Hapus tugas "${taskQueue[index].name}" dari antrian?`)) {
                taskQueue.splice(index, 1);
                if (isRunning && index < currentTaskIndex) {
                    currentTaskIndex--;
                }
                renderQueue();
            }
        };

        // Mulai antrian — OTOMATIS JALANKAN SESI PERTAMA
        startQueueBtn.addEventListener('click', () => {
            if (taskQueue.length === 0) return;
            isRunning = true;
            currentTaskIndex = 0;
            loadCurrentTask(true); // true = auto-start
            timerSection.classList.remove('hidden');
            startQueueBtn.disabled = true;
            renderQueue();
        });

        // Hentikan antrian
        stopQueueBtn.addEventListener('click', () => {
            if (confirm('Hentikan seluruh antrian?')) {
                clearInterval(timerInterval);
                timerInterval = null;
                isRunning = false;
                isPaused = false;
                timerSection.classList.add('hidden');
                startQueueBtn.disabled = false;
                renderQueue();
            }
        });

        // Muat tugas & opsional auto-start
        function loadCurrentTask(autoStart = false) {
            if (currentTaskIndex >= taskQueue.length) {
                statusDisplay.textContent = '✅ Semua tugas selesai! Selamat!';
                breakButtons.classList.add('hidden');
                isRunning = false;
                startQueueBtn.disabled = false;
                return;
            }

            const task = taskQueue[currentTaskIndex];
            currentTaskName = task.name;
            totalSessions = task.totalSessions;
            currentSession = 0;

            currentTaskNameSpan.textContent = currentTaskName;
            statusTaskSpan.textContent = currentTaskName;
            sessionTotalSpan.textContent = totalSessions;
            resetTimerToWork();

            if (autoStart) {
                startTimer();
            } else {
                updateDisplay();
            }
        }

        function resetTimerToWork() {
            currentMode = 'work';
            timeLeft = 25 * 60;
            statusDisplay.innerHTML = `Fokus: <strong>${currentTaskName}</strong>`;
            breakButtons.classList.add('hidden');
            sessionCurrentSpan.textContent = currentSession;
        }

        function updateDisplay() {
            const mins = Math.floor(timeLeft / 60).toString().padStart(2, '0');
            const secs = (timeLeft % 60).toString().padStart(2, '0');
            timerDisplay.textContent = `${mins}:${secs}`;
        }

        function countdown() {
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                if (currentMode === 'work') {
                    currentSession++;
                    sessionCurrentSpan.textContent = currentSession;

                    if (currentSession >= totalSessions) {
                        alert(`✅ Tugas "${currentTaskName}" selesai!`);
                        currentTaskIndex++;
                        setTimeout(() => {
                            loadCurrentTask(true); // Lanjut ke tugas berikutnya & auto-start
                        }, 500);
                    } else {
                        statusDisplay.textContent = 'Sesi selesai! Pilih istirahat:';
                        breakButtons.classList.remove('hidden');
                    }
                } else {
                    // Kembali ke mode kerja dan auto-start
                    resetTimerToWork();
                    startTimer();
                }
                return;
            }
            timeLeft--;
            updateDisplay();
        }

        function startTimer() {
            if (timerInterval) clearInterval(timerInterval);
            timerInterval = setInterval(countdown, 1000);
            isPaused = false;
        }

        // Mulai istirahat
        window.startBreak = function(minutes) {
            currentMode = 'break';
            timeLeft = minutes * 60;
            statusDisplay.textContent = minutes === 5 
                ? '⏳ Istirahat Singkat' 
                : '⏳ Istirahat Panjang';
            breakButtons.classList.add('hidden');
            updateDisplay();
            startTimer(); // Istirahat juga langsung jalan
        };

        // Kontrol manual
        pauseBtn.addEventListener('click', () => {
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
                isPaused = true;
            }
        });

        resetBtn.addEventListener('click', () => {
            if (isRunning) {
                if (currentMode === 'work') {
                    timeLeft = 25 * 60;
                } else {
                    // Jika di break, kembali ke sesi kerja
                    resetTimerToWork();
                }
                updateDisplay();
                if (!isPaused) {
                    startTimer();
                }
            }
        });

        // Inisialisasi
        renderQueue();
        updateDisplay();
    </script>
</body>
</html>