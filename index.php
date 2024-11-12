<?php
session_start();

function createSession($sessionName, $password) {
    $filename = "log.$sessionName.txt";
    if (!file_exists($filename)) {
        file_put_contents($filename, "PASSWORD:$password\n");
        return true;
    }
    return false;
}

function joinSession($sessionName, $password) {
    $filename = "log.$sessionName.txt";
    if (file_exists($filename)) {
        $content = file_get_contents($filename);
        $lines = explode("\n", $content);
        if (trim($lines[0]) === "MOT DE PASSE:$password") {
            return true;
        }
    }
    return false;
}

function addMessage($sessionName, $user, $message) {
    $filename = "log.$sessionName.txt";
    $timestamp = date('D-m-y H:i:s');
    $messageLog = "$timestamp - $user: $message\n";
    file_put_contents($filename, $messageLog, FILE_APPEND);
}

function getMessages($sessionName) {
    $filename = "log.$sessionName.txt";
    if (file_exists($filename)) {
        $content = file_get_contents($filename);
        $lines = explode("\n", $content);
        array_shift($lines); // reset mdp
        return array_filter($lines); // reset ligne
    }
    return [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_session':
            $sessionName = $_POST['session_name'] ?? '';
            $password = $_POST['password'] ?? '';
            $result = createSession($sessionName, $password);
            echo json_encode(['success' => $result]);
            exit;

        case 'join_session':
            $sessionName = $_POST['session_name'] ?? '';
            $password = $_POST['password'] ?? '';
            $result = joinSession($sessionName, $password);
            echo json_encode(['success' => $result]);
            exit;

        case 'send_message':
            $sessionName = $_POST['session_name'] ?? '';
            $user = $_POST['user'] ?? '';
            $message = $_POST['message'] ?? '';
            addMessage($sessionName, $user, $message);
            echo json_encode(['success' => true]);
            exit;

        case 'get_messages':
            $sessionName = $_POST['session_name'] ?? '';
            $messages = getMessages($sessionName);
            echo json_encode(['messages' => $messages]);
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OS-web & chat | Bureau</title>
    <link rel="icon" type="image/png" href="./assets/img/icon.png">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
            overflow: hidden;
        }
        .window {
            position: absolute;
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            min-width: 200px;
            min-height: 100px;
            display: flex;
            flex-direction: column;
        }
        .window-header {
            background-color: #e0e0e0;
            padding: 5px;
            cursor: move;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .window-title {
            margin: 0;
            font-size: 14px;
        }
        .window-controls {
            display: flex;
        }
        .window-control {
            margin-left: 5px;
            cursor: pointer;
            background: none;
            border: none;
            font-size: 16px;
        }
        .window-content {
            padding: 10px;
            flex-grow: 1;
            overflow: auto;
        }
        .resize-handle {
            position: absolute;
            background-color: transparent;
        }
        .resize-handle.top {
            top: -3px;
            left: 0;
            right: 0;
            height: 6px;
            cursor: n-resize;
        }
        .resize-handle.right {
            top: 0;
            right: -3px;
            bottom: 0;
            width: 6px;
            cursor: e-resize;
        }
        .resize-handle.bottom {
            bottom: -3px;
            left: 0;
            right: 0;
            height: 6px;
            cursor: s-resize;
        }
        .resize-handle.left {
            top: 0;
            left: -3px;
            bottom: 0;
            width: 6px;
            cursor: w-resize;
        }
        .resize-handle.top-left {
            top: -3px;
            left: -3px;
            width: 6px;
            height: 6px;
            cursor: nw-resize;
        }
        .resize-handle.top-right {
            top: -3px;
            right: -3px;
            width: 6px;
            height: 6px;
            cursor: ne-resize;
        }
        .resize-handle.bottom-left {
            bottom: -3px;
            left: -3px;
            width: 6px;
            height: 6px;
            cursor: sw-resize;
        }
        .resize-handle.bottom-right {
            bottom: -3px;
            right: -3px;
            width: 6px;
            height: 6px;
            cursor: se-resize;
        }
        #control-panel {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 9999;
            background-color: #fff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .taskbar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 10px;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
            z-index: 10000;
        }
        .taskbar-left {
            display: flex;
            align-items: center;
        }
        .taskbar-right {
            display: flex;
            align-items: center;
        }
        .taskbar-icons {
            display: flex;
            align-items: center;
            margin-left: 10px;
        }
        .taskbar-icon {
            width: 30px;
            height: 30px;
            margin-right: 5px;
            background-color: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
            border-radius: 3px;
        }
        .taskbar-icon:hover {
            background-color: #ccc;
        }
        .fullscreen {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: calc(100% - 40px) !important;
            z-index: 9999 !important;
        }
        body.dark-mode {
            background-color: #333;
            color: #fff;
        }
        body.dark-mode .window {
            background-color: #444;
            border-color: #555;
        }
        body.dark-mode .window-header {
            background-color: #555;
        }
        body.dark-mode .taskbar {
            background-color: #222;
        }
        body.dark-mode .taskbar-icon {
            background-color: #333;
            color: #fff;
        }
        body.dark-mode .taskbar-icon:hover {
            background-color: #444;
        }
        .switch {
          position: relative;
          display: inline-block;
          width: 30px;
          height: 17px;
        }
        .slider {
          position: absolute;
          cursor: pointer;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background-color: #ccc;
          transition: .4s;
        }
        .slider:before {
          position: absolute;
          content: "";
          height: 13px;
          width: 13px;
          left: 2px;
          bottom: 2px;
          background-color: white;
          transition: .4s;
        }
        input:checked + .slider {
          background-color: #2196F3;
        }
        input:checked + .slider:before {
          transform: translateX(13px);
        }
        .slider.round {
          border-radius: 34px;
        }
        .slider.round:before {
          border-radius: 50%;
        }
        body.dark-mode .switch .slider {
            background-color: #666;
        }
        body.dark-mode .switch input:checked + .slider {
            background-color: #0056b3;
        }
        #datetime-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            line-height: 1.2;
        }
        #date, #time {
            font-size: 12px;
        }
        .chat-dropdown {
            position: relative;
            display: inline-block;
        }
        .chat-button {
            background-color: #f0f0f0;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            margin-right: 10px;
        }
        body.dark-mode .chat-button {
            background-color: #444;
            color: #fff;
        }
        .chat-dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 300px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            padding: 12px;
            z-index: 1;
            bottom: 100%;
            right: 0;
        }
        .chat-dropdown:hover .chat-dropdown-content {
            display: block;
        }
        #chat-messages {
            height: 200px;
            overflow-y: auto;
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
        }
        #chat-input {
            width: 100%;
            padding: 5px;
            margin-bottom: 10px;
        }
        #chat-send, #create-private-session, #join-private-session {
            width: 100%;
            margin-bottom: 5px;
        }
        body.dark-mode .chat-dropdown-content {
            background-color: #333;
            color: #fff;
        }
        body.dark-mode #chat-messages,
        body.dark-mode #chat-input {
            background-color: #444;
            color: #fff;
            border-color: #555;
        }
        body.dark-mode button,
        body.dark-mode .chat-button,
        body.dark-mode .window-control {
            background-color: #444;
            color: #fff;
            border: 1px solid #555;
        }
        body.dark-mode button:hover,
        body.dark-mode .chat-button:hover,
        body.dark-mode .window-control:hover {
            background-color: #555;
        }
        .desktop-icons {
            position: fixed;
            top: 20px;
            left: 20px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 20px;
        }
        .desktop-icon {
            width: 64px;
            height: 64px;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
            text-align: center;
            padding: 5px;
        }
        .desktop-icon:hover {
            background-color: rgba(255, 255, 255, 0.9);
        }
        body.dark-mode .desktop-icon {
            background-color: rgba(50, 50, 50, 0.8);
            color: #fff;
        }
        body.dark-mode .desktop-icon:hover {
            background-color: rgba(70, 70, 70, 0.9);
        }
        .tic-tac-toe {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 5px;
            width: 300px;
            margin: 0 auto;
        }
        .tic-tac-toe button {
            width: 100%;
            height: 100px;
            font-size: 2em;
            background-color: #fff;
            border: 1px solid #ccc;
            cursor: pointer;
        }
        .tic-tac-toe button:hover {
            background-color: #f0f0f0;
        }
        body.dark-mode .tic-tac-toe button {
            background-color: #444;
            color: #fff;
            border-color: #555;
        }
        body.dark-mode .tic-tac-toe button:hover {
            background-color: #555;
        }

        .nav-logo {
            width: 30px;
            height: 30px;
            margin-right: 10px;
        }
        .neon-path {
            fill: none;
            stroke: #333;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        body.dark-mode .neon-path {
            stroke: #fff;
        }
        .dot {
            fill: #333;
        }
        body.dark-mode .dot {
            fill: #fff;
        }

    </style>
</head>
<body>
    <div id="control-panel">
        <input type="text" id="window-name" placeholder="Nom de la fenêtre" aria-label="Nom de la fenêtre">
        <button id="create-window">Créer une fenêtre</button>
        <button id="create-terminal">Créer un terminal JavaScript</button>
    </div>
    <div class="desktop-icons" id="desktop-icons"></div>

    <div class="taskbar" id="taskbar">
        <div class="taskbar-left">
            <svg class="nav-logo" viewBox="0 0 100 100">
                <path class="neon-path monitor-outline" d="M20,20 h60 a5,5 0 0 1 5,5 v35 a5,5 0 0 1 -5,5 h-60 a5,5 0 0 1 -5,-5 v-35 a5,5 0 0 1 5,-5 z"/>
                <path class="neon-path code-symbol" d="M35,40 l-8,8 l8,8 M65,40 l8,8 l-8,8"/>
                <path class="neon-path stand" d="M40,65 h20 l5,10 h-30 z"/>
                <path class="neon-path mouse" d="M70,75 q5,0 5,5 q0,5 -5,5 q-5,0 -5,-5 q0,-5 5,-5"/>
                <circle class="neon-path dot" cx="70" cy="25" r="1.5"/>
                <circle class="neon-path dot" cx="75" cy="25" r="1.5"/>
                <circle class="neon-path dot" cx="80" cy="25" r="1.5"/>
            </svg>
            <label class="switch">
              <input type="checkbox" id="dark-mode-toggle">
              <span class="slider round"></span>
            </label>
            <div class="taskbar-icons" id="taskbar-icons"></div>
        </div>
        <div class="taskbar-right">
            <div id="datetime-container">
                <div id="time"></div>
                <div id="date"></div>
            </div>
	    <div class="chat-dropdown">
                <button class="chat-button">Chat</button>
                <div class="chat-dropdown-content">
                    <div id="chat-messages"></div>
                    <input type="text" id="chat-input" placeholder="Entrez votre message...">
                    <button id="chat-send">Envoyer</button>
                    <button id="create-private-session">Créer une session privée</button>
                    <button id="join-private-session">Rejoindre une session privée</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="desktop-icons">
        <div class="desktop-icon" id="terminal-icon">
            <span style="font-size: 24px;">&#x1F4BB;</span>
            Terminal JS
        </div>
        <div class="desktop-icon" id="new-window-icon">
            <span style="font-size: 24px;">&#x1F5A5;</span>
            Nouvelle Fenêtre
        </div>
        <div class="desktop-icon" id="paint-icon">
            <span style="font-size: 24px;">&#x1F3A8;</span>
            Paint
        </div>
        <div class="desktop-icon" id="game-icon">
            <span style="font-size: 24px;">&#x1F3AE;</span>
            Morpion
        </div>
    </div>

    <script>
        let windowCount = 0;
        let windows = {};
        let currentUser = null;
        let currentSession = 'public';
        let chatMessages = {};

        function createWindow(title, content, id = null, x = null, y = null, width = '300px', height = '200px', isMinimized = false) {
            if (!id) {
                windowCount++;
                id = `window-${windowCount}`;
            }
            const window = document.createElement('div');
            window.className = 'window';
            window.id = id;
            window.style.left = x !== null ? `${x}px` : `${50 * windowCount}px`;
            window.style.top = y !== null ? `${y}px` : `${50 * windowCount}px`;
            window.style.width = width;
            window.style.height = isMinimized ? '30px' : height;
            if (isMinimized) {
                window.style.height = '30px';
            }

            window.innerHTML = `
                <div class="window-header">
                    <h2 class="window-title">${title || `Fenêtre ${windowCount}`}</h2>
                    <div class="window-controls">
                        <button class="window-control minimize" aria-label="Réduire">-</button>
                        <button class="window-control maximize" aria-label="Plein écran">□</button>
                        <button class="window-control close" aria-label="Fermer">×</button>
                    </div>
                </div>
                <div class="window-content">
                    ${content || `<p>Contenu de la fenêtre ${windowCount}</p>`}
                </div>
                <div class="resize-handle top"></div>
                <div class="resize-handle right"></div>
                <div class="resize-handle bottom"></div>
                <div class="resize-handle left"></div>
                <div class="resize-handle top-left"></div>
                <div class="resize-handle top-right"></div>
                <div class="resize-handle bottom-left"></div>
                <div class="resize-handle bottom-right"></div>
            `;

            document.body.appendChild(window);

            const header = window.querySelector('.window-header');
            const minimizeBtn = window.querySelector('.minimize');
            const closeBtn = window.querySelector('.close');
            const resizeHandles = window.querySelectorAll('.resize-handle');
            const maximizeBtn = window.querySelector('.maximize');

            let isDragging = false;
            let isResizing = false;
            let startX, startY, startWidth, startHeight, startLeft, startTop;
            let resizeDirection = '';

            header.addEventListener('mousedown', startDragging);
            minimizeBtn.addEventListener('click', () => minimizeWindow(id));
            closeBtn.addEventListener('click', () => closeWindow(id));
            resizeHandles.forEach(handle => {
                handle.addEventListener('mousedown', (e) => startResizing(e, handle.className.split(' ')[1]));
            });
            maximizeBtn.addEventListener('click', () => toggleFullscreen(window));

            function startDragging(e) {
                isDragging = true;
                startX = e.clientX - window.offsetLeft;
                startY = e.clientY - window.offsetTop;
                window.style.zIndex = getTopZIndex() + 1;
            }

            function startResizing(e, direction) {
                e.preventDefault();
                isResizing = true;
                resizeDirection = direction;
                startX = e.clientX;
                startY = e.clientY;
                startWidth = parseInt(window.getBoundingClientRect().width);
                startHeight = parseInt(window.getBoundingClientRect().height);
                startLeft = window.offsetLeft;
                startTop = window.offsetTop;
                window.style.zIndex = getTopZIndex() + 1;
            }

            document.addEventListener('mousemove', (e) => {
                if (isDragging) {
                    const newX = e.clientX - startX;
                    const newY = e.clientY - startY;
                    window.style.left = `${newX}px`;
                    window.style.top = `${newY}px`;
                    saveWindowState(id);
                } else if (isResizing) {
                    const dx = e.clientX - startX;
                    const dy = e.clientY - startY;
                    let newWidth = startWidth;
                    let newHeight = startHeight;
                    let newLeft = startLeft;
                    let newTop = startTop;

                    if (resizeDirection.includes('right')) {
                        newWidth = Math.max(200, startWidth + dx);
                    }
                    if (resizeDirection.includes('bottom')) {
                        newHeight = Math.max(100, startHeight + dy);
                    }
                    if (resizeDirection.includes('left')) {
                        newWidth = Math.max(200, startWidth - dx);
                        newLeft = startLeft + startWidth - newWidth;
                    }
                    if (resizeDirection.includes('top')) {
                        newHeight = Math.max(100, startHeight - dy);
                        newTop = startTop + startHeight - newHeight;
                    }

                    window.style.width = `${newWidth}px`;
                    window.style.height = `${newHeight}px`;
                    window.style.left = `${newLeft}px`;
                    window.style.top = `${newTop}px`;
                    saveWindowState(id);
                }
            });

            document.addEventListener('mouseup', () => {
                isDragging = false;
                isResizing = false;
            });

            addTaskbarIcon(window);

            windows[id] = {
                title: title || `Fenêtre ${windowCount}`,
                content: content || `<p>Contenu de la fenêtre ${windowCount}</p>`,
                x: parseInt(window.style.left),
                y: parseInt(window.style.top),
                width: window.style.width,
                height: window.style.height,
                isMinimized: isMinimized
            };
            saveWindows();
        }

        function minimizeWindow(id) {
            const window = document.getElementById(id);
            if (window.style.height === '30px') {
                window.style.height = windows[id].height;
                windows[id].isMinimized = false;
            } else {
                windows[id].height = window.style.height;
                window.style.height = '30px';
                windows[id].isMinimized = true;
            }
            saveWindowState(id);
        }

        function saveWindowState(id) {
            const window = document.getElementById(id);
            windows[id] = {
                ...windows[id],
                x: parseInt(window.style.left),
                y: parseInt(window.style.top),
                width: window.style.width,
                height: window.style.height,
                isMinimized: window.style.height === '30px'
            };
            saveWindows();
        }

        function getTopZIndex() {
            return Math.max(
                ...Array.from(document.querySelectorAll('.window'))
                    .map(el => parseInt(getComputedStyle(el).zIndex))
                    .filter(zIndex => !isNaN(zIndex))
            );
        }

        function toggleFullscreen(window) {
            window.classList.toggle('fullscreen');
        }

        function addTaskbarIcon(window) {
            const taskbarIcons = document.getElementById('taskbar-icons');
            const icon = document.createElement('div');
            icon.className = 'taskbar-icon';
            icon.textContent = window.querySelector('.window-title').textContent.charAt(0);
            icon.addEventListener('click', () => {
                window.style.display = window.style.display === 'none' ? 'block' : 'none';
            });
            taskbarIcons.appendChild(icon);
        }

        function changeWindowContent(window) {
            const content = window.querySelector('.window-content');
            const newContent = prompt('Entrez le nouveau contenu de la fenêtre:');
            if (newContent) {
                content.innerHTML = `
                    <p>${newContent}</p>
                    <button class="change-content">Changer le contenu</button>
                `;
                content.querySelector('.change-content').addEventListener('click', () => changeWindowContent(window));
                windows[window.id].content = newContent;
                saveWindows();
            }
        }

        function createJavaScriptTerminal() {
            const terminalContent = `
                <div class="terminal">
                    <div id="output"></div>
                    <input type="text" class="terminal-input" placeholder="Entrez du code JavaScript...">
                </div>
            `;
            createWindow('Terminal JavaScript', terminalContent);

            const terminal = document.querySelector('.terminal');
            const output = terminal.querySelector('#output');
            const input = terminal.querySelector('.terminal-input');

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    const code = input.value;
                    output.innerHTML += `<div>> ${code}</div>`;
                    
                    try {
                        const result = eval(code);
                        output.innerHTML += `<div>${result !== undefined ? result : 'undefined'}</div>`;
                    } catch (error) {
                        output.innerHTML += `<div style="color: red;">${error}</div>`;
                    }

                    input.value = '';
                    terminal.scrollTop = terminal.scrollHeight;
                }
            });
        }

        function createPaintWindow() {
            const paintContent = `
                <canvas id="paint-canvas" width="500" height="300" style="border: 1px solid #000;"></canvas>
                <div>
                    <input type="color" id="color-picker" value="#000000">
                    <input type="range" id="brush-size" min="1" max="50" value="5">
                </div>
            `;
            createWindow('Paint', paintContent);

            const canvas = document.getElementById('paint-canvas');
            const ctx = canvas.getContext('2d');
            const colorPicker = document.getElementById('color-picker');
            const brushSize = document.getElementById('brush-size');

            let painting = false;

            function startPosition(e) {
                painting = true;
                draw(e);
            }

            function endPosition() {
                painting = false;
                ctx.beginPath();
            }

            function draw(e) {
                if (!painting) return;
                ctx.lineWidth = brushSize.value;
                ctx.lineCap = 'round';
                ctx.strokeStyle = colorPicker.value;

                ctx.lineTo(e.clientX - canvas.offsetLeft, e.clientY - canvas.offsetTop);
                ctx.stroke();
                ctx.beginPath();
                ctx.moveTo(e.clientX - canvas.offsetLeft, e.clientY - canvas.offsetTop);
            }

            canvas.addEventListener('mousedown', startPosition);
            canvas.addEventListener('mouseup', endPosition);
            canvas.addEventListener('mousemove', draw);
        }

        function createGameWindow() {
            const gameContent = `
                <div class="tic-tac-toe">
                    <button></button>
                    <button></button>
                    <button></button>
                    <button></button>
                    <button></button>
                    <button></button>
                    <button></button>
                    <button></button>
                    <button></button>
                </div>
                <div id="game-status"></div>
            `;
            createWindow('Morpion', gameContent);

            const buttons = document.querySelectorAll('.tic-tac-toe button');
            const statusElement = document.getElementById('game-status');
            let currentPlayer = 'X';
            let gameBoard = ['', '', '', '', '', '', '', '', ''];

            buttons.forEach((button, index) => {
                button.addEventListener('click', () => {
                    if (button.textContent === '' && !checkWinner()) {
                        button.textContent = currentPlayer;
                        gameBoard[index] = currentPlayer;
                        if (checkWinner()) {
                            statusElement.textContent = `Le joueur ${currentPlayer} a gagné !`;
                        } else if (gameBoard.every(cell => cell !== '')) {
                            statusElement.textContent = 'Match nul !';
                        } else {
                            currentPlayer = currentPlayer === 'X' ? 'O' : 'X';
                            statusElement.textContent = `Au tour du joueur ${currentPlayer}`;
                        }
                    }
                });
            });

            function checkWinner() {
                const winPatterns = [
                    [0, 1, 2], [3, 4, 5], [6, 7, 8], 
                    [0, 3, 6], [1, 4, 7], [2, 5, 8], 
                    [0, 4, 8], [2, 4, 6] 
                ];

                return winPatterns.some(pattern => {
                    const [a, b, c] = pattern;
                    return gameBoard[a] && gameBoard[a] === gameBoard[b] && gameBoard[a] === gameBoard[c];
                });
            }

            statusElement.textContent = `Au tour du joueur ${currentPlayer}`;
        }

        function closeWindow(id) {
            const window = document.getElementById(id);
            const index = Array.from(document.querySelectorAll('.window')).indexOf(window);
            document.body.removeChild(window);
            document.getElementById('taskbar-icons').removeChild(document.getElementById('taskbar-icons').children[index]);
            delete windows[id];
            saveWindows();
        }

        function saveWindows() {
            localStorage.setItem('windows', JSON.stringify(windows));
        }

        function loadWindows() {
            const savedWindows = JSON.parse(localStorage.getItem('windows'));
            if (savedWindows) {
                for (const [id, window] of Object.entries(savedWindows)) {
                    createWindow(window.title, window.content, id, window.x, window.y, window.width, window.height, window.isMinimized);
                }
            }
        }

        document.getElementById('create-window').addEventListener('click', () => {
            const windowName = document.getElementById('window-name').value;
            createWindow(windowName);
        });

        document.getElementById('create-terminal').addEventListener('click', createJavaScriptTerminal);

        const darkModeToggle = document.getElementById('dark-mode-toggle');
        darkModeToggle.addEventListener('change', () => {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', darkModeToggle.checked);
        });

        function updateDateTime() {
            const now = new Date();
            const timeElement = document.getElementById('time');
            const dateElement = document.getElementById('date');
            
            timeElement.textContent = now.toLocaleTimeString();
            dateElement.textContent = now.toLocaleDateString();
        }

        setInterval(updateDateTime, 1000);
        updateDateTime();

        function initChat() {
            if (!currentUser) {
                currentUser = prompt("Choisissez votre pseudo :");
                if (!currentUser) {
                    alert("Vous devez choisir un pseudo pour utiliser le chat.");
                    return;
                }
            }
            loadChatMessages();
        }

        function loadChatMessages() {
            fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_messages&session_name=${currentSession}`
            })
            .then(response => response.json())
            .then(data => {
                const chatMessagesElement = document.getElementById('chat-messages');
                chatMessagesElement.innerHTML = '';
                data.messages.forEach(message => {
                    const messageElement = document.createElement('div');
                    messageElement.textContent = message;
                    chatMessagesElement.appendChild(messageElement);
                });
                chatMessagesElement.scrollTop = chatMessagesElement.scrollHeight;
            });
        }

        function sendMessage() {
            const input = document.getElementById('chat-input');
            const message = input.value.trim();
            if (message) {
                fetch('index.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=send_message&session_name=${currentSession}&user=${currentUser}&message=${encodeURIComponent(message)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        input.value = '';
                        loadChatMessages();
                    }
                });
            }
        }

        function createPrivateSession() {
            const sessionName = prompt("Entrez le nom de la session privée :");
            const password = prompt("Entrez le mot de passe pour la session privée :");
            if (sessionName && password) {
                fetch('index.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=create_session&session_name=${sessionName}&password=${password}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentSession = sessionName;
                        loadChatMessages();
                    } else {
                        alert("La session existe déjà ou n'a pas pu être créée.");
                    }
                });
            }
        }

        function joinPrivateSession() {
            const sessionName = prompt("Entrez le nom de la session privée :");
            const password = prompt("Entrez le mot de passe de la session privée :");
            if (sessionName && password) {
                fetch('index.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=join_session&session_name=${sessionName}&password=${password}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentSession = sessionName;
                        loadChatMessages();
                    } else {
                        alert("Nom de session ou mot de passe incorrect.");
                    }
                });
            }
        }

        document.getElementById('chat-send').addEventListener('click', sendMessage);
        document.getElementById('create-private-session').addEventListener('click', createPrivateSession);
        document.getElementById('join-private-session').addEventListener('click', joinPrivateSession);

        document.getElementById('terminal-icon').addEventListener('click', createJavaScriptTerminal);
        document.getElementById('new-window-icon').addEventListener('click', () => createWindow('Nouvelle Fenêtre'));
        document.getElementById('paint-icon').addEventListener('click', createPaintWindow);
        document.getElementById('game-icon').addEventListener('click', createGameWindow);

        window.addEventListener('load', () => {
            loadWindows();
            const savedDarkMode = localStorage.getItem('darkMode');
            if (savedDarkMode === 'true') {
                darkModeToggle.checked = true;
                document.body.classList.add('dark-mode');
            }
            initChat();
        });
    </script>
</body>
</html>