<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KLTN — API Tester</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0f1117;
            --bg-secondary: #161b27;
            --bg-card: #1e2536;
            --bg-input: #252d40;
            --bg-hover: #2a3347;
            --border: #2e3a52;
            --border-focus: #4f6ef7;
            --text-primary: #e8edf5;
            --text-secondary: #8b9ab8;
            --text-muted: #5a6882;
            --accent: #4f6ef7;
            --accent-hover: #6b84f8;
            --accent-glow: rgba(79, 110, 247, 0.2);
            --success: #10d98a;
            --success-bg: rgba(16, 217, 138, 0.1);
            --error: #f75d5d;
            --error-bg: rgba(247, 93, 93, 0.1);
            --warning: #ffc14d;
            --warning-bg: rgba(255, 193, 77, 0.1);
            --method-get: #10d98a;
            --method-post: #4f6ef7;
            --method-put: #ffc14d;
            --method-patch: #f7a44f;
            --method-delete: #f75d5d;
            --radius: 10px;
            --radius-sm: 6px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* ===== HEADER ===== */
        header {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            padding: 0 24px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-logo {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--accent), #8b5cf6);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .header-title {
            font-size: 15px;
            font-weight: 600;
            letter-spacing: -0.2px;
        }

        .header-subtitle {
            font-size: 12px;
            color: var(--text-muted);
        }

        .header-token {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .token-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 6px 14px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .token-badge:hover {
            border-color: var(--accent);
        }

        .token-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--text-muted);
            transition: background 0.2s;
        }

        .token-dot.active {
            background: var(--success);
            box-shadow: 0 0 6px var(--success);
        }

        /* ===== LAYOUT ===== */
        .layout {
            display: flex;
            flex: 1;
            height: calc(100vh - 60px);
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 300px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border);
            overflow-y: auto;
            overflow-x: hidden;
            flex-shrink: 0;
            height: 100%;
        }

        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 2px;
        }

        .sidebar-section {
            padding: 12px 0;
        }

        .sidebar-section-title {
            padding: 8px 16px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
        }

        .endpoint-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 16px;
            cursor: pointer;
            transition: all 0.15s;
            border-left: 3px solid transparent;
        }

        .endpoint-item:hover {
            background: var(--bg-hover);
        }

        .endpoint-item.active {
            background: var(--accent-glow);
            border-left-color: var(--accent);
        }

        .method-tag {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            font-weight: 500;
            padding: 2px 7px;
            border-radius: 4px;
            min-width: 50px;
            text-align: center;
            flex-shrink: 0;
        }

        .method-get {
            background: rgba(16, 217, 138, 0.12);
            color: var(--method-get);
        }

        .method-post {
            background: rgba(79, 110, 247, 0.12);
            color: var(--method-post);
        }

        .method-put {
            background: rgba(255, 193, 77, 0.12);
            color: var(--method-put);
        }

        .method-patch {
            background: rgba(247, 164, 79, 0.12);
            color: var(--method-patch);
        }

        .method-delete {
            background: rgba(247, 93, 93, 0.12);
            color: var(--method-delete);
        }

        .endpoint-name {
            font-size: 12.5px;
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .endpoint-item.active .endpoint-name {
            color: var(--text-primary);
        }

        /* ===== MAIN PANEL ===== */
        .main-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            height: 100%;
        }

        /* ===== REQUEST PANEL ===== */
        .request-panel {
            padding: 24px;
            border-bottom: 1px solid var(--border);
            overflow-y: auto;
            max-height: 60%;
        }

        .request-panel::-webkit-scrollbar {
            width: 4px;
        }

        .request-panel::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 2px;
        }

        .url-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .method-select {
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: var(--text-primary);
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            font-weight: 500;
            width: 110px;
            cursor: pointer;
            outline: none;
        }

        .url-input {
            flex: 1;
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: var(--text-primary);
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            outline: none;
            transition: border-color 0.2s;
        }

        .url-input:focus {
            border-color: var(--border-focus);
        }

        .send-btn {
            background: linear-gradient(135deg, var(--accent), #6b5ef7);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            padding: 10px 24px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .send-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px var(--accent-glow);
        }

        .send-btn:active {
            transform: translateY(0);
        }

        .send-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* ===== TABS ===== */
        .tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 14px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0;
        }

        .tab-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 500;
            padding: 8px 14px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.15s;
            margin-bottom: -1px;
        }

        .tab-btn:hover {
            color: var(--text-secondary);
        }

        .tab-btn.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* ===== FORM FIELDS ===== */
        .field-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .field-label {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .field-required {
            color: var(--error);
            font-size: 10px;
        }

        .field-input {
            background: var(--bg-input);
            border: 1px solid var(--border);
            color: var(--text-primary);
            border-radius: var(--radius-sm);
            padding: 9px 12px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            outline: none;
            transition: border-color 0.2s;
            width: 100%;
        }

        .field-input:focus {
            border-color: var(--border-focus);
        }

        .field-select {
            background: var(--bg-input);
            border: 1px solid var(--border);
            color: var(--text-primary);
            border-radius: var(--radius-sm);
            padding: 9px 12px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            outline: none;
            cursor: pointer;
            width: 100%;
        }

        .body-textarea {
            background: var(--bg-input);
            border: 1px solid var(--border);
            color: var(--text-primary);
            border-radius: var(--radius-sm);
            padding: 12px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12.5px;
            line-height: 1.6;
            outline: none;
            resize: vertical;
            width: 100%;
            min-height: 160px;
            transition: border-color 0.2s;
        }

        .body-textarea:focus {
            border-color: var(--border-focus);
        }

        /* ===== TOKEN SECTION ===== */
        .token-section {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .token-label {
            font-size: 12px;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .token-value {
            flex: 1;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11.5px;
            color: var(--success);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .token-empty {
            color: var(--text-muted);
            font-style: italic;
        }

        .clear-token-btn {
            background: none;
            border: 1px solid var(--border);
            color: var(--text-muted);
            border-radius: 4px;
            padding: 3px 8px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .clear-token-btn:hover {
            border-color: var(--error);
            color: var(--error);
        }

        /* ===== AUTH TOGGLE ===== */
        .auth-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .toggle-switch {
            width: 36px;
            height: 20px;
            background: var(--bg-input);
            border-radius: 10px;
            border: 1px solid var(--border);
            cursor: pointer;
            position: relative;
            transition: background 0.2s;
        }

        .toggle-switch.on {
            background: var(--accent);
            border-color: var(--accent);
        }

        .toggle-thumb {
            width: 14px;
            height: 14px;
            background: white;
            border-radius: 7px;
            position: absolute;
            top: 2px;
            left: 3px;
            transition: left 0.2s;
        }

        .toggle-switch.on .toggle-thumb {
            left: 19px;
        }

        .toggle-label {
            font-size: 12.5px;
            color: var(--text-secondary);
        }

        /* ===== RESPONSE PANEL ===== */
        .response-panel {
            flex: 1;
            padding: 20px 24px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .response-panel::-webkit-scrollbar {
            width: 4px;
        }

        .response-panel::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 2px;
        }

        .response-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .response-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .status-badge {
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            font-weight: 500;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .status-2xx {
            background: var(--success-bg);
            color: var(--success);
        }

        .status-4xx {
            background: var(--error-bg);
            color: var(--error);
        }

        .status-5xx {
            background: var(--error-bg);
            color: var(--error);
        }

        .response-time {
            font-size: 11px;
            color: var(--text-muted);
            margin-left: auto;
        }

        .response-body {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 14px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12.5px;
            line-height: 1.7;
            overflow-x: auto;
            white-space: pre;
            color: var(--text-primary);
            max-height: 400px;
            overflow-y: auto;
        }

        /* JSON Syntax highlight */
        .json-key {
            color: #8bb4ff;
        }

        .json-string {
            color: #a8e6a3;
        }

        .json-number {
            color: #ffd580;
        }

        .json-bool {
            color: #ff79c6;
        }

        .json-null {
            color: var(--text-muted);
        }

        .response-empty {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            color: var(--text-muted);
            text-align: center;
        }

        .response-empty-icon {
            font-size: 40px;
            opacity: 0.4;
        }

        .response-empty-text {
            font-size: 14px;
        }

        /* ===== QUICK FILL BUTTONS ===== */
        .quick-fill {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .quick-btn {
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            border-radius: 4px;
            padding: 4px 10px;
            font-size: 11.5px;
            cursor: pointer;
            transition: all 0.15s;
            white-space: nowrap;
        }

        .quick-btn:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        /* ===== INFO BOX ===== */
        .info-box {
            background: var(--accent-glow);
            border: 1px solid rgba(79, 110, 247, 0.3);
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            font-size: 12px;
            color: #a0b4f9;
            margin-bottom: 16px;
        }

        /* ===== DIVIDER ===== */
        .sidebar-divider {
            height: 1px;
            background: var(--border);
            margin: 4px 16px;
        }

        /* ===== SCROLLBAR ===== */
        .response-body::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        .response-body::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 2px;
        }

        .response-panel::-webkit-scrollbar-track {
            background: transparent;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <header>
        <div class="header-brand">
            <div class="header-logo">⚡</div>
            <div>
                <div class="header-title">KLTN API Tester</div>
                <div class="header-subtitle">Base URL: http://localhost:8000/api/v1</div>
            </div>
        </div>
        <div class="header-token">
            <div class="token-badge" onclick="toggleTokenPanel()">
                <div class="token-dot" id="tokenDot"></div>
                <span id="tokenStatus">Chưa đăng nhập</span>
            </div>
        </div>
    </header>

    <!-- LAYOUT -->
    <div class="layout">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <!-- Public -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">🔓 Public</div>
                <div class="endpoint-item" onclick="selectEndpoint('dang-ky-uv')">
                    <span class="method-tag method-post">POST</span>
                    <span class="endpoint-name">Đăng ký - Ứng viên</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('dang-ky-ntd')">
                    <span class="method-tag method-post">POST</span>
                    <span class="endpoint-name">Đăng ký - Nhà tuyển dụng</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('dang-nhap-admin')">
                    <span class="method-tag method-post">POST</span>
                    <span class="endpoint-name">Đăng nhập - Admin</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('dang-nhap-ntd')">
                    <span class="method-tag method-post">POST</span>
                    <span class="endpoint-name">Đăng nhập - NTD</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('dang-nhap-uv')">
                    <span class="method-tag method-post">POST</span>
                    <span class="endpoint-name">Đăng nhập - Ứng viên</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- Protected -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">🔐 Cần Token</div>
                <div class="endpoint-item" onclick="selectEndpoint('ho-so')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Xem hồ sơ</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('cap-nhat-ho-so')">
                    <span class="method-tag method-put">PUT</span>
                    <span class="endpoint-name">Cập nhật hồ sơ</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('doi-mat-khau')">
                    <span class="method-tag method-post">POST</span>
                    <span class="endpoint-name">Đổi mật khẩu</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('dang-xuat')">
                    <span class="method-tag method-post">POST</span>
                    <span class="endpoint-name">Đăng xuất</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- Admin -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">👑 Admin Only</div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Danh sách người dùng</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-thong-ke')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Thống kê</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-create')">
                    <span class="method-tag method-post">POST</span>
                    <span class="endpoint-name">Tạo tài khoản</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-show')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Chi tiết người dùng</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-update')">
                    <span class="method-tag method-put">PUT</span>
                    <span class="endpoint-name">Cập nhật người dùng</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-khoa')">
                    <span class="method-tag method-patch">PATCH</span>
                    <span class="endpoint-name">Khoá/Mở khoá</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-delete')">
                    <span class="method-tag method-delete">DELETE</span>
                    <span class="endpoint-name">Xoá tài khoản</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- Ứng viên - Hồ sơ -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">👤 Ứng viên — Hồ sơ</div>
                <div class="endpoint-item" onclick="selectEndpoint('uv-ho-so-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Danh sách hồ sơ</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('uv-ho-so-create')">
                    <span class="method-tag method-post">POST</span>
                    <span class="endpoint-name">Tạo hồ sơ</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('uv-ho-so-show')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Chi tiết hồ sơ</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('uv-ho-so-update')">
                    <span class="method-tag method-put">PUT</span>
                    <span class="endpoint-name">Cập nhật hồ sơ</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('uv-ho-so-trang-thai')">
                    <span class="method-tag method-patch">PATCH</span>
                    <span class="endpoint-name">Đổi trạng thái</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('uv-ho-so-delete')">
                    <span class="method-tag method-delete">DELETE</span>
                    <span class="endpoint-name">Xoá hồ sơ</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- NTD - Hồ sơ ứng viên -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">🏢 NTD — Hồ sơ ứng viên</div>
                <div class="endpoint-item" onclick="selectEndpoint('ntd-ho-so-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Duyệt hồ sơ công khai</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('ntd-ho-so-show')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Chi tiết hồ sơ</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- Admin - Hồ sơ -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">👑 Admin — Hồ sơ</div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-ho-so-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Danh sách hồ sơ</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-ho-so-thong-ke')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Thống kê hồ sơ</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-ho-so-show')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Chi tiết hồ sơ</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-ho-so-trang-thai')">
                    <span class="method-tag method-patch">PATCH</span>
                    <span class="endpoint-name">Đổi trạng thái hồ sơ</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-ho-so-delete')">
                    <span class="method-tag method-delete">DELETE</span>
                    <span class="endpoint-name">Xoá mềm hồ sơ</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-ho-so-da-xoa')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Thùng rác (đã xoá)</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-ho-so-khoi-phuc')">
                    <span class="method-tag method-patch">PATCH</span>
                    <span class="endpoint-name">Khôi phục hồ sơ</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- Public - Ngành nghề -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">💼 Ngành nghề (Public)</div>
                <div class="endpoint-item" onclick="selectEndpoint('nn-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Danh sách ngành nghề</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('nn-cay')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Dạng cây (cha-con)</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('nn-show')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Chi tiết ngành nghề</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- Admin - Ngành nghề -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">👑 Admin — Ngành nghề</div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-nn-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Danh sách ngành nghề</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-nn-thong-ke')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Thống kê</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-nn-store')">
                    <span class="method-tag method-post">POST</span>
                    <span class="endpoint-name">Tạo ngành nghề</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-nn-show')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Chi tiết ngành nghề</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-nn-update')">
                    <span class="method-tag method-put">PUT</span>
                    <span class="endpoint-name">Cập nhật ngành nghề</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-nn-trang-thai')">
                    <span class="method-tag method-patch">PATCH</span>
                    <span class="endpoint-name">Đổi trạng thái</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-nn-delete')">
                    <span class="method-tag method-delete">DELETE</span>
                    <span class="endpoint-name">Xoá ngành nghề</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- Public - Kỹ năng -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">⭐ Kỹ năng (Public)</div>
                <div class="endpoint-item" onclick="selectEndpoint('kn-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Danh sách kỹ năng</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('kn-show')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Chi tiết kỹ năng</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- Admin - Kỹ năng -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">👑 Admin — Kỹ năng</div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-kn-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Danh sách kỹ năng</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-kn-thong-ke')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Thống kê</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-kn-store')">
                    <span class="method-tag method-post">POST</span>
                    <span class="endpoint-name">Tạo kỹ năng</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-kn-show')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Chi tiết kỹ năng</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-kn-update')">
                    <span class="method-tag method-put">PUT</span>
                    <span class="endpoint-name">Cập nhật kỹ năng</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-kn-delete')">
                    <span class="method-tag method-delete">DELETE</span>
                    <span class="endpoint-name">Xoá kỹ năng</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- UV - Kỹ năng cá nhân -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">👤 Ứng viên — Kỹ năng cá nhân</div>
                <div class="endpoint-item" onclick="selectEndpoint('uv-kn-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Kỹ năng của tôi</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('uv-kn-store')">
                    <span class="method-tag method-post">POST</span>
                    <span class="endpoint-name">Thêm kỹ năng</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('uv-kn-update')">
                    <span class="method-tag method-put">PUT</span>
                    <span class="endpoint-name">Cập nhật kỹ năng</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('uv-kn-delete')">
                    <span class="method-tag method-delete">DELETE</span>
                    <span class="endpoint-name">Xoá kỹ năng</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- UV - Lưu tin -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">📌 Ứng viên — Lưu Tin</div>
                <div class="endpoint-item" onclick="selectEndpoint('uv-luu-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Danh sách tin đã lưu</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('uv-luu-toggle')">
                    <span class="method-tag method-post">POST</span>
                    <span class="endpoint-name">Lưu / Bỏ Lưu 1 tin</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- UV - Ứng tuyển -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">🚀 Ứng viên — Ứng Tuyển</div>
                <div class="endpoint-item" onclick="selectEndpoint('uv-ungtuyen-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Lịch sử nộp CV</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('uv-ungtuyen-store')">
                    <span class="method-tag method-post">POST</span>
                    <span class="endpoint-name">Nộp Hồ Sơ</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- UV - AI Gợi ý -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">🤖 Ứng viên — Việc Làm (AI)</div>
                <div class="endpoint-item" onclick="selectEndpoint('uv-matching-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">AI Gợi ý theo CV</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('uv-tuvan-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">AI Tư vấn Nghề nghiệp</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- Admin - Kỹ năng người dùng -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">👑 Admin — Kỹ năng người dùng</div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-ndkn-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Tất cả bản ghi</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-ndkn-thong-ke')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Thống kê</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-ndkn-nguoi-dung')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Kỹ năng của 1 user</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- Public - Công ty -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">🏢 Công ty (Public)</div>
                <div class="endpoint-item" onclick="selectEndpoint('ct-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Danh sách công ty</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('ct-show')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Chi tiết công ty</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- Public - Tin Tuyển Dụng -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">📄 Tin Tuyển Dụng (Public)</div>
                <div class="endpoint-item" onclick="selectEndpoint('tin-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Danh sách tin</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('tin-show')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Chi tiết tin</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- NTD - Công ty -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">💼 NTD — Công ty của tôi</div>
                <div class="endpoint-item" onclick="selectEndpoint('ntd-ct-show')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Xem công ty</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('ntd-ct-store')">
                    <span class="method-tag method-post">POST</span>
                    <span class="endpoint-name">Tạo công ty</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('ntd-ct-update')">
                    <span class="method-tag method-put">PUT</span>
                    <span class="endpoint-name">Cập nhật công ty</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- NTD - Tin Tuyển Dụng -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">💼 NTD — Quản lý tin</div>
                <div class="endpoint-item" onclick="selectEndpoint('ntd-tin-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Danh sách tin của tôi</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('ntd-tin-store')">
                    <span class="method-tag method-post">POST</span>
                    <span class="endpoint-name">Đăng tin tuyển dụng</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('ntd-tin-show')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Chi tiết 1 tin</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('ntd-tin-update')">
                    <span class="method-tag method-put">PUT</span>
                    <span class="endpoint-name">Cập nhật tin</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('ntd-tin-trang-thai')">
                    <span class="method-tag method-put">PATCH</span>
                    <span class="endpoint-name">Đổi trạng thái tin</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('ntd-tin-delete')">
                    <span class="method-tag method-delete">DELETE</span>
                    <span class="endpoint-name">Xoá tin</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- NTD - Ứng Tuyển -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">👥 NTD — Phê Duyệt CV</div>
                <div class="endpoint-item" onclick="selectEndpoint('ntd-ungtuyen-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Danh sách ứng viên nộp</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('ntd-ungtuyen-trangthai')">
                    <span class="method-tag method-put">PATCH</span>
                    <span class="endpoint-name">Duyệt Hồ Sơ (Đậu/Rớt)</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- Admin - Công ty -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">👑 Admin — Công ty</div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-ct-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Danh sách công ty</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-ct-thong-ke')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Thống kê</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-ct-show')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Chi tiết công ty</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-ct-update')">
                    <span class="method-tag method-put">PUT</span>
                    <span class="endpoint-name">Cập nhật công ty</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-ct-trang-thai')">
                    <span class="method-tag method-put">PATCH</span>
                    <span class="endpoint-name">Đổi trạng thái</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-ct-delete')">
                    <span class="method-tag method-delete">DELETE</span>
                    <span class="endpoint-name">Xoá công ty</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- Admin - Tin Tuyển Dụng -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">👑 Admin — Tin Tuyển Dụng</div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-tin-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Danh sách (All)</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-tin-thong-ke')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Thống kê tin</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-luu-thong-ke')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Thống kê TOP lưu tin</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-tin-store')">
                    <span class="method-tag method-post">POST</span>
                    <span class="endpoint-name">Tạo tin thay NTD</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-tin-show')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Chi tiết 1 tin</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-tin-update')">
                    <span class="method-tag method-put">PUT</span>
                    <span class="endpoint-name">Cập nhật tin</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-tin-trang-thai')">
                    <span class="method-tag method-put">PATCH</span>
                    <span class="endpoint-name">Bật/Tắt trạng thái</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-tin-delete')">
                    <span class="method-tag method-delete">DELETE</span>
                    <span class="endpoint-name">Xoá tin</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- Admin - Ứng Tuyển -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">👑 Admin — Ứng Tuyển</div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-ungtuyen-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Tất cả đơn ứng tuyển</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-ungtuyen-thongke')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Thống kê Phễu Apply</span>
                </div>
            </div>

            <div class="sidebar-divider"></div>

            <!-- Admin - AI Matching Tracking -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">👑 Admin — Giám sát AI</div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-matching-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Danh sách Matching</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-matching-thongke')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Thống kê Hiệu suất AI</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-tuvan-list')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Lịch sử Tư vấn CV</span>
                </div>
                <div class="endpoint-item" onclick="selectEndpoint('admin-tuvan-thongke')">
                    <span class="method-tag method-get">GET</span>
                    <span class="endpoint-name">Thống kê Định Hướng</span>
                </div>
            </div>
        </aside>

        <!-- MAIN PANEL -->
        <div class="main-panel">
            <!-- REQUEST -->
            <div class="request-panel">
                <!-- Token Status -->
                <div class="token-section" id="tokenSection">
                    <span class="token-label">🔑 Bearer Token:</span>
                    <span class="token-value token-empty" id="tokenDisplay">Chưa có token — hãy đăng nhập trước</span>
                    <button class="clear-token-btn" onclick="clearToken()" id="clearTokenBtn"
                        style="display:none">Xoá</button>
                </div>

                <!-- URL Bar -->
                <div class="url-bar">
                    <select class="method-select" id="methodSelect">
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                        <option value="PUT">PUT</option>
                        <option value="PATCH">PATCH</option>
                        <option value="DELETE">DELETE</option>
                    </select>
                    <input type="text" class="url-input" id="urlInput" value="http://localhost:8000/api/v1"
                        placeholder="URL endpoint...">
                    <button class="send-btn" id="sendBtn" onclick="sendRequest()">
                        <span id="sendIcon">▶</span>
                        <span id="sendText">Gửi</span>
                    </button>
                </div>

                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab-btn active" onclick="switchTab('params')">Params</button>
                    <button class="tab-btn" onclick="switchTab('body')">Body</button>
                    <button class="tab-btn" onclick="switchTab('auth')">Auth</button>
                    <button class="tab-btn" onclick="switchTab('info')">Thông tin</button>
                </div>

                <!-- Tab: Params -->
                <div class="tab-content active" id="tab-params">
                    <div class="field-group" id="paramsFields">
                        <p style="font-size:13px;color:var(--text-muted)">Chọn một endpoint từ sidebar để bắt đầu.</p>
                    </div>
                </div>

                <!-- Tab: Body -->
                <div class="tab-content" id="tab-body">
                    <div class="quick-fill" id="quickFillBtns"></div>
                    <textarea class="body-textarea" id="bodyTextarea" placeholder='{\n    "key": "value"\n}'></textarea>
                </div>

                <!-- Tab: Auth -->
                <div class="tab-content" id="tab-auth">
                    <div class="auth-toggle" id="authToggle">
                        <div class="toggle-switch on" id="authSwitch" onclick="toggleAuth()">
                            <div class="toggle-thumb"></div>
                        </div>
                        <span class="toggle-label">Tự động gửi Bearer Token</span>
                    </div>
                    <div class="field">
                        <label class="field-label">Token thủ công (override)</label>
                        <input type="text" class="field-input" id="manualToken"
                            placeholder="Nhập token nếu muốn override...">
                    </div>
                </div>

                <!-- Tab: Info -->
                <div class="tab-content" id="tab-info">
                    <div class="info-box" id="endpointInfo" style="font-size:13px;line-height:1.7">
                        Chọn endpoint từ sidebar để xem thông tin chi tiết.
                    </div>
                </div>
            </div>

            <!-- RESPONSE -->
            <div class="response-panel" id="responsePanel">
                <div class="response-empty">
                    <div class="response-empty-icon">🚀</div>
                    <div class="response-empty-text">Gửi một request để xem kết quả ở đây</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = 'http://localhost:8000/api/v1';
        let accessToken = localStorage.getItem('kltn_access_token') || '';
        let useAuth = true;
        let currentEndpointId = null;

        // Update token display on load
        updateTokenDisplay();

        const ENDPOINTS = {
            'dang-ky-uv': {
                name: 'Đăng ký - Ứng viên',
                method: 'POST',
                url: `${BASE_URL}/dang-ky`,
                auth: false,
                info: `<strong>POST /api/v1/dang-ky</strong><br>Đăng ký tài khoản ứng viên mới.<br><br><strong>Lưu ý:</strong> vai_tro: 0 = Ứng viên, 1 = Nhà tuyển dụng. Không thể tự đăng ký Admin.`,
                body: {
                    ho_ten: 'Nguyễn Văn Test',
                    email: 'test.uv@kltn.com',
                    mat_khau: 'password123',
                    mat_khau_confirmation: 'password123',
                    so_dien_thoai: '0987654321',
                    ngay_sinh: '2000-06-15',
                    gioi_tinh: 'nam',
                    dia_chi: '123 Đường ABC, Hà Nội',
                    vai_tro: 0
                },
                quickFill: []
            },
            'dang-ky-ntd': {
                name: 'Đăng ký - Nhà tuyển dụng',
                method: 'POST',
                url: `${BASE_URL}/dang-ky`,
                auth: false,
                info: `<strong>POST /api/v1/dang-ky</strong><br>Đăng ký tài khoản nhà tuyển dụng.`,
                body: {
                    ho_ten: 'Trần Thị NTD',
                    email: 'test.ntd@kltn.com',
                    mat_khau: 'password123',
                    mat_khau_confirmation: 'password123',
                    so_dien_thoai: '0911222333',
                    ngay_sinh: '1990-03-20',
                    gioi_tinh: 'nu',
                    dia_chi: '45 Lê Lợi, TP. HCM',
                    vai_tro: 1
                }
            },
            'dang-nhap-admin': {
                name: 'Đăng nhập - Admin',
                method: 'POST',
                url: `${BASE_URL}/dang-nhap`,
                auth: false,
                info: `<strong>POST /api/v1/dang-nhap</strong><br>Đăng nhập với tài khoản Admin.<br><br>⚡ Token sẽ được <strong>tự động lưu</strong> sau khi đăng nhập thành công.`,
                body: { email: 'admin@kltn.com', mat_khau: 'Admin@123' },
                quickFill: [
                    { label: 'Admin', body: { email: 'admin@kltn.com', mat_khau: 'Admin@123' } },
                    { label: 'NTD 1', body: { email: 'tuyen.dung1@kltn.com', mat_khau: 'NTD@123456' } },
                    { label: 'UV 1', body: { email: 'ung.vien1@kltn.com', mat_khau: 'UV@123456' } },
                    { label: 'Bị khoá ❌', body: { email: 'ung.vien.khoa@kltn.com', mat_khau: 'UV@123456' } },
                    { label: 'Sai MK ❌', body: { email: 'admin@kltn.com', mat_khau: 'SaiMatKhau999' } },
                ]
            },
            'dang-nhap-ntd': {
                name: 'Đăng nhập - NTD',
                method: 'POST',
                url: `${BASE_URL}/dang-nhap`,
                auth: false,
                info: `<strong>POST /api/v1/dang-nhap</strong><br>Đăng nhập với tài khoản Nhà tuyển dụng.⚡ Token sẽ tự động lưu.`,
                body: { email: 'tuyen.dung1@kltn.com', mat_khau: 'NTD@123456' }
            },
            'dang-nhap-uv': {
                name: 'Đăng nhập - UV',
                method: 'POST',
                url: `${BASE_URL}/dang-nhap`,
                auth: false,
                info: `<strong>POST /api/v1/dang-nhap</strong><br>Đăng nhập với tài khoản Ứng viên. ⚡ Token sẽ tự động lưu.`,
                body: { email: 'ung.vien1@kltn.com', mat_khau: 'UV@123456' }
            },
            'ho-so': {
                name: 'Xem hồ sơ',
                method: 'GET',
                url: `${BASE_URL}/ho-so`,
                auth: true,
                info: `<strong>GET /api/v1/ho-so</strong><br>Xem thông tin hồ sơ của người dùng đang đăng nhập.<br><br>🔐 Cần Bearer Token.`
            },
            'cap-nhat-ho-so': {
                name: 'Cập nhật hồ sơ',
                method: 'PUT',
                url: `${BASE_URL}/ho-so`,
                auth: true,
                info: `<strong>PUT /api/v1/ho-so</strong><br>Cập nhật thông tin hồ sơ cá nhân. Dùng "sometimes" rule — chỉ gửi trường muốn cập nhật.<br><br>🔐 Cần Bearer Token.`,
                body: { ho_ten: 'Tên Mới', so_dien_thoai: '0909090909', dia_chi: 'TP. HCM mới' }
            },
            'doi-mat-khau': {
                name: 'Đổi mật khẩu',
                method: 'POST',
                url: `${BASE_URL}/doi-mat-khau`,
                auth: true,
                info: `<strong>POST /api/v1/doi-mat-khau</strong><br>Đổi mật khẩu người dùng.<br><br>⚠️ Sau khi đổi thành công, <strong>tất cả token bị thu hồi</strong>, cần đăng nhập lại.<br><br>🔐 Cần Bearer Token.`,
                body: { mat_khau_cu: 'UV@123456', mat_khau_moi: 'NewPass@789', mat_khau_moi_confirmation: 'NewPass@789' }
            },
            'dang-xuat': {
                name: 'Đăng xuất',
                method: 'POST',
                url: `${BASE_URL}/dang-xuat`,
                auth: true,
                info: `<strong>POST /api/v1/dang-xuat</strong><br>Thu hồi token hiện tại và đăng xuất.<br><br>⚡ Token sẽ bị xoá khỏi ứng dụng sau khi đăng xuất thành công.<br><br>🔐 Cần Bearer Token.`
            },
            'admin-list': {
                name: 'Danh sách người dùng',
                method: 'GET',
                url: `${BASE_URL}/admin/nguoi-dungs`,
                auth: true,
                params: [
                    { key: 'per_page', value: '10', label: 'Số bản ghi/trang' },
                    { key: 'vai_tro', value: '', label: 'Vai trò (0/1/2)' },
                    { key: 'trang_thai', value: '', label: 'Trạng thái (0/1)' },
                    { key: 'search', value: '', label: 'Tìm kiếm' },
                    { key: 'sort_by', value: 'id', label: 'Sắp xếp theo' },
                    { key: 'sort_dir', value: 'desc', label: 'Chiều sắp xếp' },
                ],
                info: `<strong>GET /api/v1/admin/nguoi-dungs</strong><br>Danh sách người dùng với lọc, tìm kiếm, phân trang.<br><br>👑 Chỉ Admin.`
            },
            'admin-thong-ke': {
                name: 'Thống kê',
                method: 'GET',
                url: `${BASE_URL}/admin/nguoi-dungs/thong-ke`,
                auth: true,
                info: `<strong>GET /api/v1/admin/nguoi-dungs/thong-ke</strong><br>Thống kê số lượng người dùng theo vai trò và trạng thái.<br><br>👑 Chỉ Admin.`
            },
            'admin-create': {
                name: 'Tạo tài khoản',
                method: 'POST',
                url: `${BASE_URL}/admin/nguoi-dungs`,
                auth: true,
                info: `<strong>POST /api/v1/admin/nguoi-dungs</strong><br>Admin tạo tài khoản mới với bất kỳ vai trò nào.<br><br>👑 Chỉ Admin.`,
                body: { ho_ten: 'Tên Mới', email: 'moi@kltn.com', mat_khau: 'Admin@999', vai_tro: 2, trang_thai: 1 },
                quickFill: [
                    { label: 'Admin', body: { ho_ten: 'Admin Mới', email: 'admin.moi@kltn.com', mat_khau: 'Admin@999', vai_tro: 2, trang_thai: 1 } },
                    { label: 'NTD', body: { ho_ten: 'Công Ty ABC', email: 'congty@kltn.com', mat_khau: 'NTD@999', vai_tro: 1, trang_thai: 1 } },
                    { label: 'UV', body: { ho_ten: 'Ứng Viên Mới', email: 'uv.moi@kltn.com', mat_khau: 'UV@999', vai_tro: 0, trang_thai: 1 } },
                ]
            },
            'admin-show': {
                name: 'Chi tiết người dùng',
                method: 'GET',
                url: `${BASE_URL}/admin/nguoi-dungs/1`,
                auth: true,
                params: [
                    { key: '__id__', value: '1', label: 'ID người dùng' },
                ],
                info: `<strong>GET /api/v1/admin/nguoi-dungs/{id}</strong><br>Xem chi tiết người dùng theo ID.<br><br>👑 Chỉ Admin.`
            },
            'admin-update': {
                name: 'Cập nhật người dùng',
                method: 'PUT',
                url: `${BASE_URL}/admin/nguoi-dungs/4`,
                auth: true,
                params: [
                    { key: '__id__', value: '4', label: 'ID người dùng' },
                ],
                info: `<strong>PUT /api/v1/admin/nguoi-dungs/{id}</strong><br>Admin cập nhật thông tin người dùng.<br><br>👑 Chỉ Admin.`,
                body: { ho_ten: 'Tên Đã Sửa', dia_chi: 'Địa chỉ mới', trang_thai: 1 },
                quickFill: [
                    { label: 'Đổi vai trò → NTD', body: { vai_tro: 1 } },
                    { label: 'Kích hoạt', body: { trang_thai: 1 } },
                    { label: 'Reset mật khẩu', body: { mat_khau: 'NewPass@123' } },
                ]
            },
            'admin-khoa': {
                name: 'Khoá/Mở khoá',
                method: 'PATCH',
                url: `${BASE_URL}/admin/nguoi-dungs/4/khoa`,
                auth: true,
                params: [
                    { key: '__id__', value: '4', label: 'ID người dùng' },
                ],
                info: `<strong>PATCH /api/v1/admin/nguoi-dungs/{id}/khoa</strong><br>Toggle khoá/mở khoá tài khoản.<br>Gọi lần 2 để đảo trạng thái.<br><br>👑 Chỉ Admin.`
            },
            'admin-delete': {
                name: 'Xoá tài khoản',
                method: 'DELETE',
                url: `${BASE_URL}/admin/nguoi-dungs/10`,
                auth: true,
                params: [
                    { key: '__id__', value: '10', label: 'ID người dùng' },
                ],
                info: `<strong>DELETE /api/v1/admin/nguoi-dungs/{id}</strong><br>Xoá tài khoản người dùng.<br><br>⚠️ Không thể xoá chính mình.<br><br>👑 Chỉ Admin.`
            },

            // =============================================
            // ỨNG VIÊN — HỒ SƠ
            // =============================================
            'uv-ho-so-list': {
                name: 'Danh sách hồ sơ (UV)',
                method: 'GET',
                url: `${BASE_URL}/ung-vien/ho-sos`,
                auth: true,
                params: [
                    { key: 'trang_thai', value: '', label: 'Trạng thái (0: ẩn / 1: công khai)' },
                    { key: 'sort_by', value: 'created_at', label: 'Sắp xếp theo' },
                    { key: 'sort_dir', value: 'desc', label: 'Chiều sắp xếp' },
                    { key: 'per_page', value: '10', label: 'Số bản ghi/trang' },
                ],
                info: `<strong>GET /api/v1/ung-vien/ho-sos</strong><br>Danh sách hồ sơ của ứng viên đang đăng nhập.<br><br>🔐 Cần Bearer Token (Ứng viên).`
            },
            'uv-ho-so-create': {
                name: 'Tạo hồ sơ (UV)',
                method: 'POST',
                url: `${BASE_URL}/ung-vien/ho-sos`,
                auth: true,
                info: `<strong>POST /api/v1/ung-vien/ho-sos</strong><br>Tạo hồ sơ mới cho ứng viên.<br><br><strong>Trình độ hợp lệ:</strong> trung_hoc, trung_cap, cao_dang, dai_hoc, thac_si, tien_si, khac<br><br>🔐 Cần Bearer Token (Ứng viên).`,
                body: {
                    tieu_de_ho_so: 'Hồ sơ DevOps Engineer',
                    muc_tieu_nghe_nghiep: 'Mong muốn ứng tuyển vị trí DevOps tại công ty công nghệ.',
                    trinh_do: 'dai_hoc',
                    kinh_nghiem_nam: 3,
                    mo_ta_ban_than: 'Thành thạo Docker, Kubernetes, CI/CD.',
                    trang_thai: 1
                },
                quickFill: [
                    { label: 'Đầy đủ', body: { tieu_de_ho_so: 'Hồ sơ DevOps Engineer', muc_tieu_nghe_nghiep: 'Tìm việc DevOps', trinh_do: 'dai_hoc', kinh_nghiem_nam: 3, mo_ta_ban_than: 'Thành thạo Docker, K8s', trang_thai: 1 } },
                    { label: 'Tối thiểu', body: { tieu_de_ho_so: 'Hồ sơ nhanh' } },
                    { label: 'Thiếu tiêu đề ❌', body: { muc_tieu_nghe_nghiep: 'Tìm việc', trinh_do: 'dai_hoc' } },
                    { label: 'Trình độ sai ❌', body: { tieu_de_ho_so: 'Test', trinh_do: 'tien_sy_sai' } },
                ]
            },
            'uv-ho-so-show': {
                name: 'Chi tiết hồ sơ (UV)',
                method: 'GET',
                url: `${BASE_URL}/ung-vien/ho-sos/1`,
                auth: true,
                params: [
                    { key: '__id__', value: '1', label: 'ID hồ sơ' },
                ],
                info: `<strong>GET /api/v1/ung-vien/ho-sos/{id}</strong><br>Xem chi tiết hồ sơ. Chỉ xem được hồ sơ của mình.<br><br>🔐 Cần Bearer Token (Ứng viên).`
            },
            'uv-ho-so-update': {
                name: 'Cập nhật hồ sơ (UV)',
                method: 'PUT',
                url: `${BASE_URL}/ung-vien/ho-sos/1`,
                auth: true,
                params: [
                    { key: '__id__', value: '1', label: 'ID hồ sơ' },
                ],
                info: `<strong>PUT /api/v1/ung-vien/ho-sos/{id}</strong><br>Cập nhật hồ sơ. Chỉ sửa được hồ sơ của mình.<br><br>🔐 Cần Bearer Token (Ứng viên).`,
                body: { tieu_de_ho_so: 'Hồ sơ Đã Cập Nhật', kinh_nghiem_nam: 4, trinh_do: 'thac_si' },
                quickFill: [
                    { label: 'Đổi tiêu đề', body: { tieu_de_ho_so: 'Hồ sơ Backend (Mới)' } },
                    { label: 'Nâng trình độ', body: { trinh_do: 'thac_si', kinh_nghiem_nam: 5 } },
                    { label: 'Ẩn hồ sơ', body: { trang_thai: 0 } },
                ]
            },
            'uv-ho-so-trang-thai': {
                name: 'Đổi trạng thái (UV)',
                method: 'PATCH',
                url: `${BASE_URL}/ung-vien/ho-sos/1/trang-thai`,
                auth: true,
                params: [
                    { key: '__id__', value: '1', label: 'ID hồ sơ' },
                ],
                info: `<strong>PATCH /api/v1/ung-vien/ho-sos/{id}/trang-thai</strong><br>Toggle trạng thái hồ sơ (công khai ↔ ẩn).<br>Gọi lần 2 để đảo ngược.<br><br>🔐 Cần Bearer Token (Ứng viên).`
            },
            'uv-ho-so-delete': {
                name: 'Xoá hồ sơ (UV)',
                method: 'DELETE',
                url: `${BASE_URL}/ung-vien/ho-sos/3`,
                auth: true,
                params: [
                    { key: '__id__', value: '3', label: 'ID hồ sơ' },
                ],
                info: `<strong>DELETE /api/v1/ung-vien/ho-sos/{id}</strong><br>Xoá hồ sơ. Chỉ xoá được hồ sơ của mình.<br><br>⚠️ Hành động không thể hoàn tác.<br><br>🔐 Cần Bearer Token (Ứng viên).`
            },

            // =============================================
            // NHÀ TUYỂN DỤNG — HỒ SƠ ỨNG VIÊN
            // =============================================
            'ntd-ho-so-list': {
                name: 'Duyệt hồ sơ công khai (NTD)',
                method: 'GET',
                url: `${BASE_URL}/nha-tuyen-dung/ho-sos`,
                auth: true,
                params: [
                    { key: 'trinh_do', value: '', label: 'Trình độ' },
                    { key: 'kinh_nghiem_tu', value: '', label: 'KN từ (năm)' },
                    { key: 'kinh_nghiem_den', value: '', label: 'KN đến (năm)' },
                    { key: 'search', value: '', label: 'Tìm kiếm' },
                    { key: 'sort_by', value: 'created_at', label: 'Sắp xếp theo' },
                    { key: 'sort_dir', value: 'desc', label: 'Chiều sắp xếp' },
                    { key: 'per_page', value: '10', label: 'Số bản ghi/trang' },
                ],
                info: `<strong>GET /api/v1/nha-tuyen-dung/ho-sos</strong><br>Duyệt danh sách hồ sơ <strong>công khai</strong> của ứng viên.<br><br>Có thể lọc theo:<br>• Trình độ: trung_hoc, trung_cap, cao_dang, dai_hoc, thac_si, tien_si, khac<br>• Khoảng kinh nghiệm (năm)<br>• Tìm kiếm theo tiêu đề, mục tiêu, mô tả<br><br>🏢 Chỉ Nhà tuyển dụng.`
            },
            'ntd-ho-so-show': {
                name: 'Chi tiết hồ sơ (NTD)',
                method: 'GET',
                url: `${BASE_URL}/nha-tuyen-dung/ho-sos/1`,
                auth: true,
                params: [
                    { key: '__id__', value: '1', label: 'ID hồ sơ' },
                ],
                info: `<strong>GET /api/v1/nha-tuyen-dung/ho-sos/{id}</strong><br>Xem chi tiết hồ sơ công khai. Bao gồm thông tin ứng viên (tên, email, SĐT, ảnh).<br><br>⚠️ Chỉ xem được hồ sơ <strong>công khai</strong> (trang_thai = 1). Hồ sơ ẩn sẽ trả 404.<br><br>🏢 Chỉ Nhà tuyển dụng.`
            },

            // =============================================
            // ADMIN — HỒ SƠ
            // =============================================
            'admin-ho-so-list': {
                name: 'Danh sách hồ sơ (Admin)',
                method: 'GET',
                url: `${BASE_URL}/admin/ho-sos`,
                auth: true,
                params: [
                    { key: 'per_page', value: '10', label: 'Số bản ghi/trang' },
                    { key: 'nguoi_dung_id', value: '', label: 'Lọc theo ID người dùng' },
                    { key: 'trang_thai', value: '', label: 'Trạng thái (0/1)' },
                    { key: 'trinh_do', value: '', label: 'Trình độ' },
                    { key: 'search', value: '', label: 'Tìm kiếm' },
                    { key: 'sort_by', value: 'created_at', label: 'Sắp xếp theo' },
                    { key: 'sort_dir', value: 'desc', label: 'Chiều sắp xếp' },
                ],
                info: `<strong>GET /api/v1/admin/ho-sos</strong><br>Danh sách tất cả hồ sơ với bộ lọc, tìm kiếm, phân trang.<br><br>👑 Chỉ Admin.`
            },
            'admin-ho-so-thong-ke': {
                name: 'Thống kê hồ sơ',
                method: 'GET',
                url: `${BASE_URL}/admin/ho-sos/thong-ke`,
                auth: true,
                info: `<strong>GET /api/v1/admin/ho-sos/thong-ke</strong><br>Thống kê hồ sơ theo trạng thái, trình độ và số lượng đã xoá mềm.<br><br>👑 Chỉ Admin.`
            },
            'admin-ho-so-show': {
                name: 'Chi tiết hồ sơ (Admin)',
                method: 'GET',
                url: `${BASE_URL}/admin/ho-sos/1`,
                auth: true,
                params: [
                    { key: '__id__', value: '1', label: 'ID hồ sơ' },
                ],
                info: `<strong>GET /api/v1/admin/ho-sos/{id}</strong><br>Xem chi tiết hồ sơ theo ID. Bao gồm thông tin người dùng.<br><br>👑 Chỉ Admin.`
            },
            'admin-ho-so-trang-thai': {
                name: 'Đổi trạng thái hồ sơ',
                method: 'PATCH',
                url: `${BASE_URL}/admin/ho-sos/2/trang-thai`,
                auth: true,
                params: [
                    { key: '__id__', value: '2', label: 'ID hồ sơ' },
                ],
                info: `<strong>PATCH /api/v1/admin/ho-sos/{id}/trang-thai</strong><br>Toggle trạng thái hồ sơ (công khai ↔ ẩn).<br><br>👑 Chỉ Admin.`
            },
            'admin-ho-so-delete': {
                name: 'Xoá mềm hồ sơ',
                method: 'DELETE',
                url: `${BASE_URL}/admin/ho-sos/10`,
                auth: true,
                params: [
                    { key: '__id__', value: '10', label: 'ID hồ sơ' },
                ],
                info: `<strong>DELETE /api/v1/admin/ho-sos/{id}</strong><br>Xoá mềm hồ sơ (soft delete).<br><br>✅ Hồ sơ <strong>không bị xoá khỏi database</strong>, chỉ đánh dấu deleted_at.<br>Có thể khôi phục bằng endpoint <strong>Khôi phục hồ sơ</strong>.<br><br>👑 Chỉ Admin.`
            },
            'admin-ho-so-da-xoa': {
                name: 'Thùng rác (đã xoá)',
                method: 'GET',
                url: `${BASE_URL}/admin/ho-sos/da-xoa`,
                auth: true,
                params: [
                    { key: 'search', value: '', label: 'Tìm kiếm' },
                    { key: 'per_page', value: '10', label: 'Số bản ghi/trang' },
                ],
                info: `<strong>GET /api/v1/admin/ho-sos/da-xoa</strong><br>Danh sách hồ sơ đã bị xoá mềm (thùng rác).<br><br>🗑️ Hiển thị các hồ sơ có deleted_at != null.<br>Có thể khôi phục bằng endpoint <strong>Khôi phục hồ sơ</strong>.<br><br>👑 Chỉ Admin.`
            },
            'admin-ho-so-khoi-phuc': {
                name: 'Khôi phục hồ sơ',
                method: 'PATCH',
                url: `${BASE_URL}/admin/ho-sos/10/khoi-phuc`,
                auth: true,
                params: [
                    { key: '__id__', value: '10', label: 'ID hồ sơ đã xoá' },
                ],
                info: `<strong>PATCH /api/v1/admin/ho-sos/{id}/khoi-phuc</strong><br>Khôi phục hồ sơ đã bị xoá mềm.<br><br>♻️ Hồ sơ sẽ được đưa lại vào danh sách bình thường (deleted_at = null).<br><br>👑 Chỉ Admin.`
            },

            // =============================================
            // NGÀNH NGHỀ — PUBLIC
            // =============================================
            'nn-list': {
                name: 'Danh sách ngành nghề',
                method: 'GET',
                url: `${BASE_URL}/nganh-nghes`,
                auth: false,
                params: [
                    { key: 'search', value: '', label: 'Tìm kiếm' },
                    { key: 'danh_muc_cha_id', value: '', label: 'Lọc theo ID cha' },
                    { key: 'goc', value: '', label: 'Chỉ ngành gốc (1)' },
                    { key: 'per_page', value: '0', label: 'Số bản ghi/trang (0=tất cả)' },
                ],
                info: `<strong>GET /api/v1/nganh-nghes</strong><br>Danh sách ngành nghề <strong>hiển thị</strong> (dạng phẳng).<br><br>Lọc:<br>• <code>goc=1</code> — chỉ ngành gốc<br>• <code>danh_muc_cha_id=1</code> — ngành con của cha<br>• <code>per_page=0</code> — lấy tất cả<br><br>🔓 Không cần đăng nhập.`
            },
            'nn-cay': {
                name: 'Dạng cây (cha-con)',
                method: 'GET',
                url: `${BASE_URL}/nganh-nghes/cay`,
                auth: false,
                info: `<strong>GET /api/v1/nganh-nghes/cay</strong><br>Danh sách ngành nghề dạng cây phân cấp.<br>Trả về ngành gốc kèm danh mục con.<br><br>🔓 Không cần đăng nhập.`
            },
            'nn-show': {
                name: 'Chi tiết ngành nghề',
                method: 'GET',
                url: `${BASE_URL}/nganh-nghes/1`,
                auth: false,
                params: [
                    { key: '__id__', value: '1', label: 'ID ngành nghề' },
                ],
                info: `<strong>GET /api/v1/nganh-nghes/{id}</strong><br>Chi tiết ngành nghề. Bao gồm danh mục cha và danh mục con.<br><br>🔓 Không cần đăng nhập.`
            },

            // =============================================
            // ADMIN — NGÀNH NGHỀ
            // =============================================
            'admin-nn-list': {
                name: 'Danh sách ngành nghề (Admin)',
                method: 'GET',
                url: `${BASE_URL}/admin/nganh-nghes`,
                auth: true,
                params: [
                    { key: 'trang_thai', value: '', label: 'Trạng thái (0/1)' },
                    { key: 'danh_muc_cha_id', value: '', label: 'ID cha (null=gốc)' },
                    { key: 'search', value: '', label: 'Tìm kiếm' },
                    { key: 'sort_by', value: 'ten_nganh', label: 'Sắp xếp theo' },
                    { key: 'sort_dir', value: 'asc', label: 'Chiều sắp xếp' },
                    { key: 'per_page', value: '0', label: 'Số bản ghi/trang' },
                ],
                info: `<strong>GET /api/v1/admin/nganh-nghes</strong><br>Danh sách tất cả ngành nghề (kể cả ẩn).<br><br><code>danh_muc_cha_id=null</code> hoặc <code>=0</code> để lọc ngành gốc.<br><br>👑 Chỉ Admin.`
            },
            'admin-nn-thong-ke': {
                name: 'Thống kê ngành nghề',
                method: 'GET',
                url: `${BASE_URL}/admin/nganh-nghes/thong-ke`,
                auth: true,
                info: `<strong>GET /api/v1/admin/nganh-nghes/thong-ke</strong><br>Thống kê ngành nghề theo trạng thái, gốc/con.<br><br>👑 Chỉ Admin.`
            },
            'admin-nn-store': {
                name: 'Tạo ngành nghề',
                method: 'POST',
                url: `${BASE_URL}/admin/nganh-nghes`,
                auth: true,
                info: `<strong>POST /api/v1/admin/nganh-nghes</strong><br>Tạo ngành nghề mới. Slug tự động tạo từ tên.<br><br><code>danh_muc_cha_id</code> = null → ngành gốc.<br><code>danh_muc_cha_id</code> = ID → ngành con.<br><br>👑 Chỉ Admin.`,
                body: { ten_nganh: 'Logistics / Kho vận', mo_ta: 'Lĩnh vực logistics, vận tải, kho bãi.', icon: '🚚', danh_muc_cha_id: null, trang_thai: 1 },
                quickFill: [
                    { label: 'Ngành gốc', body: { ten_nganh: 'Logistics / Kho vận', mo_ta: 'Lĩnh vực logistics, vận tải, kho bãi.', icon: '🚚', trang_thai: 1 } },
                    { label: 'Ngành con (IT)', body: { ten_nganh: 'An ninh mạng', mo_ta: 'Cybersecurity, SIEM, SOC...', icon: '🔒', danh_muc_cha_id: 1, trang_thai: 1 } },
                    { label: 'Thiếu tên ❌', body: { mo_ta: 'Test thiếu tên' } },
                ]
            },
            'admin-nn-show': {
                name: 'Chi tiết ngành nghề (Admin)',
                method: 'GET',
                url: `${BASE_URL}/admin/nganh-nghes/1`,
                auth: true,
                params: [
                    { key: '__id__', value: '1', label: 'ID ngành nghề' },
                ],
                info: `<strong>GET /api/v1/admin/nganh-nghes/{id}</strong><br>Chi tiết ngành nghề (kể cả ẩn). Kèm danh mục cha và con.<br><br>👑 Chỉ Admin.`
            },
            'admin-nn-update': {
                name: 'Cập nhật ngành nghề',
                method: 'PUT',
                url: `${BASE_URL}/admin/nganh-nghes/1`,
                auth: true,
                params: [
                    { key: '__id__', value: '1', label: 'ID ngành nghề' },
                ],
                info: `<strong>PUT /api/v1/admin/nganh-nghes/{id}</strong><br>Cập nhật ngành nghề. Slug tự động cập nhật nếu đổi tên.<br><br>👑 Chỉ Admin.`,
                body: { ten_nganh: 'CNTT (Đã sửa)', mo_ta: 'Cập nhật mô tả', icon: '💻' },
                quickFill: [
                    { label: 'Đổi tên', body: { ten_nganh: 'Công nghệ thông tin (CNTT)' } },
                    { label: 'Đổi icon', body: { icon: '🖥️' } },
                    { label: 'Ẩn', body: { trang_thai: 0 } },
                    { label: 'Cha = chính nó ❌', body: { danh_muc_cha_id: 1 } },
                ]
            },
            'admin-nn-trang-thai': {
                name: 'Đổi trạng thái ngành nghề',
                method: 'PATCH',
                url: `${BASE_URL}/admin/nganh-nghes/1/trang-thai`,
                auth: true,
                params: [
                    { key: '__id__', value: '1', label: 'ID ngành nghề' },
                ],
                info: `<strong>PATCH /api/v1/admin/nganh-nghes/{id}/trang-thai</strong><br>Toggle trạng thái (hiển thị ↔ ẩn).<br><br>👑 Chỉ Admin.`
            },
            'admin-nn-delete': {
                name: 'Xoá ngành nghề',
                method: 'DELETE',
                url: `${BASE_URL}/admin/nganh-nghes/29`,
                auth: true,
                params: [
                    { key: '__id__', value: '29', label: 'ID ngành nghề' },
                ],
                info: `<strong>DELETE /api/v1/admin/nganh-nghes/{id}</strong><br>Xoá ngành nghề.<br><br>⚠️ Không thể xoá nếu có danh mục con. Hãy xoá/chuyển con trước.<br><br>👑 Chỉ Admin.`
            },

            // =============================================
            // KỸ NĂNG — PUBLIC
            // =============================================
            'kn-list': {
                name: 'Danh sách kỹ năng',
                method: 'GET',
                url: `${BASE_URL}/ky-nangs`,
                auth: false,
                params: [
                    { key: 'search', value: '', label: 'Tìm kiếm' },
                    { key: 'per_page', value: '0', label: 'Số bản ghi/trang (0=tất cả)' },
                ],
                info: `<strong>GET /api/v1/ky-nangs</strong><br>Danh sách kỹ năng (dạng phẳng).<br><br>Lọc:<br>• <code>search=PHP</code> — tìm theo tên<br>• <code>per_page=0</code> — lấy tất cả<br><br>🔓 Không cần đăng nhập.`
            },
            'kn-show': {
                name: 'Chi tiết kỹ năng',
                method: 'GET',
                url: `${BASE_URL}/ky-nangs/1`,
                auth: false,
                params: [
                    { key: '__id__', value: '1', label: 'ID kỹ năng' },
                ],
                info: `<strong>GET /api/v1/ky-nangs/{id}</strong><br>Chi tiết kỹ năng theo ID.<br><br>🔓 Không cần đăng nhập.`
            },

            // =============================================
            // ADMIN — KỸ NĂNG
            // =============================================
            'admin-kn-list': {
                name: 'Danh sách kỹ năng (Admin)',
                method: 'GET',
                url: `${BASE_URL}/admin/ky-nangs`,
                auth: true,
                params: [
                    { key: 'search', value: '', label: 'Tìm kiếm' },
                    { key: 'sort_by', value: 'ten_ky_nang', label: 'Sắp xếp theo' },
                    { key: 'sort_dir', value: 'asc', label: 'Chiều sắp xếp' },
                    { key: 'per_page', value: '0', label: 'Số bản ghi/trang' },
                ],
                info: `<strong>GET /api/v1/admin/ky-nangs</strong><br>Danh sách tất cả kỹ năng.<br><br>👑 Chỉ Admin.`
            },
            'admin-kn-thong-ke': {
                name: 'Thống kê kỹ năng',
                method: 'GET',
                url: `${BASE_URL}/admin/ky-nangs/thong-ke`,
                auth: true,
                info: `<strong>GET /api/v1/admin/ky-nangs/thong-ke</strong><br>Thống kê kỹ năng: tổng, có mô tả, có icon.<br><br>👑 Chỉ Admin.`
            },
            'admin-kn-store': {
                name: 'Tạo kỹ năng',
                method: 'POST',
                url: `${BASE_URL}/admin/ky-nangs`,
                auth: true,
                info: `<strong>POST /api/v1/admin/ky-nangs</strong><br>Tạo kỹ năng mới (danh mục).<br><br>Tên kỹ năng phải duy nhất.<br>Admin chỉ quản lý tên + mô tả + icon.<br><br>👑 Chỉ Admin.`,
                body: { ten_ky_nang: 'GraphQL', mo_ta: 'Ngôn ngữ truy vấn API', icon: '🔗' },
                quickFill: [
                    { label: 'Đầy đủ', body: { ten_ky_nang: 'GraphQL', mo_ta: 'Ngôn ngữ truy vấn API hiện đại', icon: '🔗' } },
                    { label: 'Tối thiểu', body: { ten_ky_nang: 'Rust' } },
                    { label: 'Thiếu tên ❌', body: { mo_ta: 'Test' } },
                    { label: 'Trùng tên ❌', body: { ten_ky_nang: 'PHP' } },
                ]
            },
            'admin-kn-show': {
                name: 'Chi tiết kỹ năng (Admin)',
                method: 'GET',
                url: `${BASE_URL}/admin/ky-nangs/1`,
                auth: true,
                params: [
                    { key: '__id__', value: '1', label: 'ID kỹ năng' },
                ],
                info: `<strong>GET /api/v1/admin/ky-nangs/{id}</strong><br>Chi tiết kỹ năng theo ID.<br><br>👑 Chỉ Admin.`
            },
            'admin-kn-update': {
                name: 'Cập nhật kỹ năng',
                method: 'PUT',
                url: `${BASE_URL}/admin/ky-nangs/1`,
                auth: true,
                params: [
                    { key: '__id__', value: '1', label: 'ID kỹ năng' },
                ],
                info: `<strong>PUT /api/v1/admin/ky-nangs/{id}</strong><br>Cập nhật kỹ năng (tên, mô tả, icon).<br><br>👑 Chỉ Admin.`,
                body: { ten_ky_nang: 'PHP (Đã sửa)', mo_ta: 'Cập nhật mô tả', icon: '🐘' },
                quickFill: [
                    { label: 'Đổi tên', body: { ten_ky_nang: 'PHP 8.x' } },
                    { label: 'Đổi icon', body: { icon: '💻' } },
                    { label: 'Thêm mô tả', body: { mo_ta: 'Ngôn ngữ lập trình web phổ biến nhất' } },
                    { label: 'Trùng tên ❌', body: { ten_ky_nang: 'JavaScript' } },
                ]
            },
            'admin-kn-delete': {
                name: 'Xoá kỹ năng',
                method: 'DELETE',
                url: `${BASE_URL}/admin/ky-nangs/40`,
                auth: true,
                params: [
                    { key: '__id__', value: '40', label: 'ID kỹ năng' },
                ],
                info: `<strong>DELETE /api/v1/admin/ky-nangs/{id}</strong><br>Xoá kỹ năng.<br><br>⚠️ Hành động không thể hoàn tác.<br><br>👑 Chỉ Admin.`
            },

            // =============================================
            // ỨNG VIÊN — KỸ NĂNG CÁ NHÂN
            // =============================================
            'uv-kn-list': {
                name: 'Kỹ năng của tôi',
                method: 'GET',
                url: `${BASE_URL}/ung-vien/ky-nangs`,
                auth: true,
                info: `<strong>GET /api/v1/ung-vien/ky-nangs</strong><br>Danh sách kỹ năng của ứng viên đang đăng nhập.<br><br>Bao gồm: mức độ, năm KN, <strong>số chứng chỉ, hình ảnh chứng chỉ</strong>.<br><br>👤 Chỉ Ứng viên.`
            },
            'uv-kn-store': {
                name: 'Thêm kỹ năng',
                method: 'POST',
                url: `${BASE_URL}/ung-vien/ky-nangs`,
                auth: true,
                info: `<strong>POST /api/v1/ung-vien/ky-nangs</strong><br>Thêm kỹ năng vào hồ sơ.<br><br>Mức độ: 1=Cơ bản, 2=Trung bình, 3=Khá, 4=Giỏi, 5=Chuyên gia.<br>Có thể kèm <strong>số chứng chỉ</strong> và <strong>hình ảnh chứng chỉ</strong>.<br><br>👤 Chỉ Ứng viên.`,
                body: { ky_nang_id: 10, muc_do: 3, nam_kinh_nghiem: 2, so_chung_chi: 1, hinh_anh: 'cert.jpg' },
                quickFill: [
                    { label: 'Laravel + cert', body: { ky_nang_id: 10, muc_do: 4, nam_kinh_nghiem: 3, so_chung_chi: 1, hinh_anh: 'laravel-cert.jpg' } },
                    { label: 'React (không cert)', body: { ky_nang_id: 11, muc_do: 3, nam_kinh_nghiem: 1, so_chung_chi: 0 } },
                    { label: 'AWS - Chuyên gia + 3 cert', body: { ky_nang_id: 26, muc_do: 5, nam_kinh_nghiem: 5, so_chung_chi: 3, hinh_anh: 'aws-certs.jpg' } },
                    { label: 'Thiếu ky_nang_id ❌', body: { muc_do: 3 } },
                    { label: 'Mức độ sai ❌', body: { ky_nang_id: 10, muc_do: 9 } },
                ]
            },
            'uv-kn-update': {
                name: 'Cập nhật kỹ năng',
                method: 'PUT',
                url: `${BASE_URL}/ung-vien/ky-nangs/1`,
                auth: true,
                params: [
                    { key: '__id__', value: '1', label: 'ID bản ghi' },
                ],
                info: `<strong>PUT /api/v1/ung-vien/ky-nangs/{id}</strong><br>Cập nhật mức độ, năm KN, <strong>số chứng chỉ, hình ảnh</strong>.<br><br>Chỉ sửa được bản ghi của chính mình.<br><br>👤 Chỉ Ứng viên.`,
                body: { muc_do: 5, nam_kinh_nghiem: 4, so_chung_chi: 2, hinh_anh: 'updated-cert.jpg' },
                quickFill: [
                    { label: 'Lên chuyên gia + cert', body: { muc_do: 5, nam_kinh_nghiem: 5, so_chung_chi: 3, hinh_anh: 'expert-cert.jpg' } },
                    { label: 'Chỉ đổi mức độ', body: { muc_do: 4 } },
                    { label: 'Upload ảnh chứng chỉ', body: { hinh_anh: 'new-cert.jpg' } },
                    { label: 'Xóa ảnh chứng chỉ', body: { hinh_anh: null } },
                ]
            },
            'uv-kn-delete': {
                name: 'Xoá kỹ năng',
                method: 'DELETE',
                url: `${BASE_URL}/ung-vien/ky-nangs/1`,
                auth: true,
                params: [
                    { key: '__id__', value: '1', label: 'ID bản ghi' },
                ],
                info: `<strong>DELETE /api/v1/ung-vien/ky-nangs/{id}</strong><br>Xoá kỹ năng khỏi hồ sơ cá nhân.<br><br>Chỉ xoá được bản ghi của chính mình.<br><br>👤 Chỉ Ứng viên.`
            },

            // ================== LƯU TIN (ỨNG VIÊN) ==================
            'uv-luu-list': {
                name: 'Danh sách tin đã lưu',
                method: 'GET',
                url: `${BASE_URL}/ung-vien/tin-da-luu?per_page=15`,
                auth: true,
                info: `<strong>GET /api/v1/ung-vien/tin-da-luu</strong><br>Trả về danh sách các tin tuyển dụng mà ứng viên ĐÃ bấm lưu.<br>Có kèm thông tin Công Ty & Ngành Nghề cơ bản.`
            },
            'uv-luu-toggle': {
                name: 'Lưu / Bỏ Lưu 1 tin',
                method: 'POST',
                url: `${BASE_URL}/ung-vien/tin-da-luu/1/toggle`,
                auth: true,
                info: `<strong>POST /api/v1/ung-vien/tin-da-luu/{tin_id}/toggle</strong><br>Bật / Tắt lưu tin (Toggle Action).<br><br>👉 Nếu chưa lưu -> Sẽ lưu.<br>👉 Nếu đã lưu rồi -> Sẽ huỷ lưu.<br><br><i>Rất tiện lợi, chỉ cần gọi 1 API cho nút Trái tim / Bookmark.</i>`
            },

            // ================== ỨNG TUYỂN (ỨNG VIÊN) ==================
            'uv-ungtuyen-list': {
                name: 'Lịch sử nộp CV',
                method: 'GET',
                url: `${BASE_URL}/ung-vien/ung-tuyens?per_page=15`,
                auth: true,
                info: `<strong>GET /api/v1/ung-vien/ung-tuyens</strong><br>Danh sách các công việc mà ứng viên đã từng ứng tuyển.<br>Có thể lọc bằng <code>?trang_thai=0</code> (0: Chờ duyệt, 1: Đã xem, 2: Chấp nhận, 3: Từ chối).`
            },
            'uv-ungtuyen-store': {
                name: 'Nộp Hồ Sơ',
                method: 'POST',
                url: `${BASE_URL}/ung-vien/ung-tuyens`,
                auth: true,
                info: `<strong>POST /api/v1/ung-vien/ung-tuyens</strong><br>Nộp hồ sơ xin việc vào 1 tin tuyển dụng.<br><br>💡 Tự động chặn spam: 1 User chỉ được nộp vào 1 Tin duy nhất 1 lần (dù dùng CV khác cũng không được).`,
                body: { tin_tuyen_dung_id: 1, ho_so_id: 1 }
            },

            // ================== AI GỢI Ý (ỨNG VIÊN) ==================
            'uv-matching-list': {
                name: 'AI Gợi ý theo CV',
                method: 'GET',
                url: `${BASE_URL}/ung-vien/ket-qua-matchings?per_page=15`,
                auth: true,
                info: `<strong>GET /api/v1/ung-vien/ket-qua-matchings</strong><br>Lấy danh sách các công việc được Hệ thống AI chấm điểm cao nhất gửi riêng tới ứng viên đang đăng nhập.<br>Có thể truyền bộ lọc <code>?ho_so_id=X</code> để xem các công việc gợi ý riêng cho 1 CV cụ thể.`
            },
            'uv-tuvan-list': {
                name: 'AI Tư vấn Nghề nghiệp',
                method: 'GET',
                url: `${BASE_URL}/ung-vien/tu-van-nghe-nghieps?per_page=15`,
                auth: true,
                info: `<strong>GET /api/v1/ung-vien/tu-van-nghe-nghieps</strong><br>Xem tất cả các Bản báo cáo Đánh giá Lộ trình và Gợi ý Kỹ Năng do AI tự động tính toán gửi vào tài khoản ứng viên.`
            },

            // =============================================
            // ADMIN — KỸ NĂNG NGƯỜI DÙNG
            // =============================================
            'admin-ndkn-list': {
                name: 'Tất cả bản ghi',
                method: 'GET',
                url: `${BASE_URL}/admin/nguoi-dung-ky-nangs`,
                auth: true,
                params: [
                    { key: 'nguoi_dung_id', value: '', label: 'Lọc theo user ID' },
                    { key: 'ky_nang_id', value: '', label: 'Lọc theo kỹ năng ID' },
                    { key: 'muc_do', value: '', label: 'Lọc theo mức độ (1-5)' },
                    { key: 'per_page', value: '0', label: 'Số bản ghi/trang' },
                ],
                info: `<strong>GET /api/v1/admin/nguoi-dung-ky-nangs</strong><br>Danh sách tất cả bản ghi người dùng — kỹ năng.<br><br>Lọc theo: user_id, ky_nang_id, muc_do.<br><br>👑 Chỉ Admin.`
            },
            'admin-ndkn-thong-ke': {
                name: 'Thống kê kỹ năng người dùng',
                method: 'GET',
                url: `${BASE_URL}/admin/nguoi-dung-ky-nangs/thong-ke`,
                auth: true,
                info: `<strong>GET /api/v1/admin/nguoi-dung-ky-nangs/thong-ke</strong><br>Thống kê: tổng bản ghi, số người có kỹ năng, theo mức độ, top 10 kỹ năng phổ biến nhất.<br><br>👑 Chỉ Admin.`
            },
            'admin-ndkn-nguoi-dung': {
                name: 'Kỹ năng của 1 user',
                method: 'GET',
                url: `${BASE_URL}/admin/nguoi-dung-ky-nangs/nguoi-dung/2`,
                auth: true,
                params: [
                    { key: '__nguoiDungId__', value: '2', label: 'ID người dùng' },
                ],
                info: `<strong>GET /api/v1/admin/nguoi-dung-ky-nangs/nguoi-dung/{nguoiDungId}</strong><br>Xem danh sách kỹ năng của 1 người dùng cụ thể.<br>Sắp xếp theo mức độ giảm dần.<br><br>👑 Chỉ Admin.`
            },

            // =============================================
            // CÔNG TY — PUBLIC
            // =============================================
            'ct-list': {
                name: 'Danh sách công ty',
                method: 'GET',
                url: `${BASE_URL}/cong-tys`,
                auth: false,
                params: [
                    { key: 'search', value: '', label: 'Tìm kiếm' },
                    { key: 'nganh_nghe_id', value: '', label: 'Lọc ngành nghề' },
                    { key: 'quy_mo', value: '', label: 'Lọc quy mô' },
                    { key: 'per_page', value: '0', label: 'Số bản ghi/trang' },
                ],
                info: `<strong>GET /api/v1/cong-tys</strong><br>Danh sách công ty đang hoạt động.<br><br>🔓 Không cần đăng nhập.`
            },
            'ct-show': {
                name: 'Chi tiết công ty',
                method: 'GET',
                url: `${BASE_URL}/cong-tys/1`,
                auth: false,
                params: [
                    { key: '__id__', value: '1', label: 'ID công ty' },
                ],
                info: `<strong>GET /api/v1/cong-tys/{id}</strong><br>Chi tiết công ty.<br><br>🔓 Không cần đăng nhập.`
            },
            'tin-list': {
                name: 'Job Toàn Quốc',
                method: 'GET',
                url: `${BASE_URL}/tin-tuyen-dungs`,
                auth: false,
                info: `<strong>GET /api/v1/tin-tuyen-dungs</strong><br>Danh sách tin tuyển dụng Public.<br><br>🔓 Không cần đăng nhập.`
            },
            'tin-show': {
                name: 'Chi tiết Job',
                method: 'GET',
                url: `${BASE_URL}/tin-tuyen-dungs/1`,
                auth: false,
                info: `<strong>GET /api/v1/tin-tuyen-dungs/{id}</strong><br>Chi tiết 1 job.<br><br>🔓 Không cần đăng nhập.`
            },

            // =============================================
            // NTD — TIN TUYỂN DỤNG
            // =============================================
            'ntd-tin-list': {
                name: 'Danh sách Tin của tôi',
                method: 'GET',
                url: `${BASE_URL}/nha-tuyen-dung/tin-tuyen-dungs`,
                auth: true,
                info: `<strong>GET /api/v1/nha-tuyen-dung/tin-tuyen-dungs</strong><br>Lấy các job do chính công ty của NTD đăng.<br><br>💼 Chỉ NTD.`
            },
            'ntd-tin-store': {
                name: 'Đăng Tin Tuyển Dụng mới',
                method: 'POST',
                url: `${BASE_URL}/nha-tuyen-dung/tin-tuyen-dungs`,
                auth: true,
                info: `<strong>POST /api/v1/nha-tuyen-dung/tin-tuyen-dungs</strong><br>Tạo phiên bản Việc làm.`,
                body: { tieu_de: 'Lập trình NodeJs', mo_ta_cong_viec: 'Làm việc HCM', dia_diem_lam_viec: 'Q1 HCM', so_luong_tuyen: 5, nganh_nghes: [1, 5] },
            },
            'ntd-tin-show': {
                name: 'Chi Tiết 1 Tin của mình',
                method: 'GET',
                url: `${BASE_URL}/nha-tuyen-dung/tin-tuyen-dungs/1`,
                auth: true,
                info: `<strong>GET /api/v1/nha-tuyen-dung/tin-tuyen-dungs/{id}</strong><br>Xem 1 Việc làm chi tiết.`
            },
            'ntd-tin-update': {
                name: 'Sửa Tin',
                method: 'PUT',
                url: `${BASE_URL}/nha-tuyen-dung/tin-tuyen-dungs/1`,
                auth: true,
                info: `<strong>PUT /api/v1/nha-tuyen-dung/tin-tuyen-dungs/{id}</strong><br>Sửa tiêu đề, mức lương, kinh nghiệm... của việc làm.`
            },
            'ntd-tin-trang-thai': {
                name: 'Đóng/Mở Tin',
                method: 'PATCH',
                url: `${BASE_URL}/nha-tuyen-dung/tin-tuyen-dungs/1/trang-thai`,
                auth: true,
                info: `<strong>PATCH /api/v1/nha-tuyen-dung/tin-tuyen-dungs/1/trang-thai</strong><br>Tạm ngưng/Mở lại bài đăng.`
            },
            'ntd-tin-delete': {
                name: 'Xóa Job',
                method: 'DELETE',
                url: `${BASE_URL}/nha-tuyen-dung/tin-tuyen-dungs/1`,
                auth: true,
                info: `<strong>DELETE /api/v1/nha-tuyen-dung/tin-tuyen-dungs/{id}</strong><br>Xóa hẵn tin tuyển dụng nếu ko ai ứng tuyển.`
            },

            // =============================================
            // NTD — QUẢN LÝ ỨNG TUYỂN (Duyệt Đơn)
            // =============================================
            'ntd-ungtuyen-list': {
                name: 'Duyệt danh sách CV nộp vào',
                method: 'GET',
                url: `${BASE_URL}/nha-tuyen-dung/ung-tuyens`,
                auth: true,
                info: `<strong>GET /api/v1/nha-tuyen-dung/ung-tuyens</strong><br>Danh sách ứng viên đâm đơn. Có thể filter bằng ?tin_tuyen_dung_id=1.`
            },
            'ntd-ungtuyen-trangthai': {
                name: 'Chấm Pass/Fail CV',
                method: 'PATCH',
                url: `${BASE_URL}/nha-tuyen-dung/ung-tuyens/1/trang-thai`,
                auth: true,
                info: `<strong>PATCH /api/v1/nha-tuyen-dung/ung-tuyens/{id}/trang-thai</strong><br>Đổi trạng thái hồ sơ (0: Chờ duyệt, 1: Đã xem, 2: Tiếp nhận, 3: Phỏng vấn, 4: Từ chối). Lập tức hệ thống Email/Noti sẽ gửi cho Ứng viên.`,
                body: { trang_thai: 3, ghi_chu: 'Phỏng vấn lúc 10h sáng ở Q1' }
            },

            // =============================================
            // NTD — CÔNG TY CỦA TÔI
            // =============================================
            'ntd-ct-show': {
                name: 'Xem công ty của tôi',
                method: 'GET',
                url: `${BASE_URL}/nha-tuyen-dung/cong-ty`,
                auth: true,
                info: `<strong>GET /api/v1/nha-tuyen-dung/cong-ty</strong><br>Xem thông tin công ty của NTD đang đăng nhập.<br><br>💼 Chỉ NTD.`
            },
            'ntd-ct-store': {
                name: 'Tạo công ty',
                method: 'POST',
                url: `${BASE_URL}/nha-tuyen-dung/cong-ty`,
                auth: true,
                info: `<strong>POST /api/v1/nha-tuyen-dung/cong-ty</strong><br>Tạo thông tin công ty.<br><br>Mỗi NTD chỉ được tạo <strong>1 công ty</strong>.<br>Quy mô: 1-10, 11-50, 51-200, 201-500, 500+<br><br>💼 Chỉ NTD.`,
                body: { ten_cong_ty: 'Công ty Test', ma_so_thue: '0312678129', mo_ta: 'Mô tả công ty', dia_chi: '123 ABC, TP.HCM', dien_thoai: '028-1111-2222', email: 'hr@test.vn', website: 'https://test.vn', nganh_nghe_id: 1, quy_mo: '11-50' },
                quickFill: [
                    { label: 'Đầy đủ', body: { ten_cong_ty: 'Công ty Test', ma_so_thue: '0312678129', mo_ta: 'Công ty công nghệ', dia_chi: '123 ABC', dien_thoai: '028-1111-2222', email: 'hr@test.vn', website: 'https://test.vn', nganh_nghe_id: 1, quy_mo: '51-200' } },
                    { label: 'Tối thiểu', body: { ten_cong_ty: 'Startup X', ma_so_thue: '0123456789' } },
                    { label: 'Thiếu MST ❌', body: { ten_cong_ty: 'Test' } },
                ]
            },
            'ntd-ct-update': {
                name: 'Cập nhật công ty',
                method: 'PUT',
                url: `${BASE_URL}/nha-tuyen-dung/cong-ty`,
                auth: true,
                info: `<strong>PUT /api/v1/nha-tuyen-dung/cong-ty</strong><br>Cập nhật thông tin công ty của mình.<br><br>💼 Chỉ NTD.`,
                body: { ten_cong_ty: 'Tên mới', mo_ta: 'Mô tả mới' },
                quickFill: [
                    { label: 'Đổi tên', body: { ten_cong_ty: 'TechViet Pro' } },
                    { label: 'Đổi địa chỉ', body: { dia_chi: '789 XYZ, Hà Nội' } },
                    { label: 'Đổi quy mô', body: { quy_mo: '201-500' } },
                ]
            },

            // =============================================
            // ADMIN — CÔNG TY
            // =============================================
            'admin-ct-list': {
                name: 'Danh sách công ty (Admin)',
                method: 'GET',
                url: `${BASE_URL}/admin/cong-tys`,
                auth: true,
                params: [
                    { key: 'search', value: '', label: 'Tìm kiếm' },
                    { key: 'trang_thai', value: '', label: 'Trạng thái (0/1)' },
                    { key: 'nganh_nghe_id', value: '', label: 'Ngành nghề ID' },
                    { key: 'quy_mo', value: '', label: 'Quy mô' },
                    { key: 'sort_by', value: 'created_at', label: 'Sắp xếp theo' },
                    { key: 'sort_dir', value: 'desc', label: 'Chiều sắp xếp' },
                    { key: 'per_page', value: '0', label: 'Số bản ghi/trang' },
                ],
                info: `<strong>GET /api/v1/admin/cong-tys</strong><br>Danh sách tất cả công ty (kể cả tạm ngưng).<br><br>👑 Chỉ Admin.`
            },
            'admin-ct-thong-ke': {
                name: 'Thống kê công ty',
                method: 'GET',
                url: `${BASE_URL}/admin/cong-tys/thong-ke`,
                auth: true,
                info: `<strong>GET /api/v1/admin/cong-tys/thong-ke</strong><br>Thống kê: tổng, hoạt động, tạm ngưng, theo quy mô.<br><br>👑 Chỉ Admin.`
            },
            'admin-ct-show': {
                name: 'Chi tiết công ty (Admin)',
                method: 'GET',
                url: `${BASE_URL}/admin/cong-tys/1`,
                auth: true,
                params: [
                    { key: '__id__', value: '1', label: 'ID công ty' },
                ],
                info: `<strong>GET /api/v1/admin/cong-tys/{id}</strong><br>Chi tiết công ty.<br><br>👑 Chỉ Admin.`
            },
            'admin-ct-update': {
                name: 'Cập nhật công ty (Admin)',
                method: 'PUT',
                url: `${BASE_URL}/admin/cong-tys/1`,
                auth: true,
                params: [
                    { key: '__id__', value: '1', label: 'ID công ty' },
                ],
                info: `<strong>PUT /api/v1/admin/cong-tys/{id}</strong><br>Cập nhật bất kỳ công ty nào.<br><br>👑 Chỉ Admin.`,
                body: { ten_cong_ty: 'Tên đã sửa', dia_chi: 'Địa chỉ mới' },
                quickFill: [
                    { label: 'Đổi tên', body: { ten_cong_ty: 'Admin Edit Corp' } },
                    { label: 'Đổi mô tả', body: { mo_ta: 'Mô tả do Admin sửa' } },
                ]
            },
            'admin-ct-trang-thai': {
                name: 'Đổi trạng thái',
                method: 'PATCH',
                url: `${BASE_URL}/admin/cong-tys/1/trang-thai`,
                auth: true,
                params: [
                    { key: '__id__', value: '1', label: 'ID công ty' },
                ],
                info: `<strong>PATCH /api/v1/admin/cong-tys/{id}/trang-thai</strong><br>Toggle trạng thái: hoạt động ↔ tạm ngưng.<br><br>👑 Chỉ Admin.`
            },
            'admin-ct-delete': {
                name: 'Xoá công ty',
                method: 'DELETE',
                url: `${BASE_URL}/admin/cong-tys/2`,
                auth: true,
                params: [
                    { key: '__id__', value: '2', label: 'ID công ty' },
                ],
                info: `<strong>DELETE /api/v1/admin/cong-tys/{id}</strong><br>Xoá công ty.<br><br>⚠️ Không thể hoàn tác.<br><br>👑 Chỉ Admin.`
            },

            // ================== TIN TUYỂN DỤNG (ADMIN) ==================
            'admin-tin-thong-ke': {
                name: 'Thống kê Tin Tuyển Dụng',
                method: 'GET',
                url: `${BASE_URL}/admin/tin-tuyen-dungs/thong-ke`,
                auth: true,
                info: `<strong>GET /api/v1/admin/tin-tuyen-dungs/thong-ke</strong><br>Thống kê số lượng tin hoạt động/tạm ngưng.`
            },
            'admin-tin-list': {
                method: 'GET',
                url: `${BASE_URL}/admin/tin-tuyen-dungs?per_page=10`,
                auth: true,
                info: `<strong>GET /api/v1/admin/tin-tuyen-dungs</strong><br>Danh sách toàn bộ tin tuyển dụng trong hệ thống.<br>Có thể lọc <code>?cong_ty_id=1</code> hoặc <code>?trang_thai=0</code>`
            },
            'admin-tin-store': {
                method: 'POST',
                url: `${BASE_URL}/admin/tin-tuyen-dungs`,
                auth: true,
                info: `<strong>POST /api/v1/admin/tin-tuyen-dungs</strong><br>Admin đăng tin thay NTD. Bắt buộc truyền theo <code>cong_ty_id</code>.`,
                body: { cong_ty_id: 1, tieu_de: 'Admin Seeded Job', mo_ta_cong_viec: 'Admin post job', dia_diem_lam_viec: 'Everywhere', so_luong_tuyen: 1, nganh_nghes: [1, 5] },
            },
            'admin-tin-show': {
                method: 'GET',
                url: `${BASE_URL}/admin/tin-tuyen-dungs/1`,
                auth: true,
                info: `<strong>GET /api/v1/admin/tin-tuyen-dungs/{id}</strong><br>Chi tiết tin qua góc nhìn admin.`
            },
            'admin-tin-update': {
                method: 'PUT',
                url: `${BASE_URL}/admin/tin-tuyen-dungs/1`,
                auth: true,
                info: `<strong>PUT /api/v1/admin/tin-tuyen-dungs/{id}</strong><br>Admin chỉnh sửa nội dung tin (kiểm duyệt/sửa lỗi cho NTD).`,
                body: { tieu_de: 'Tiêu đề đã được Admin kiểm duyệt' }
            },
            'admin-tin-trang-thai': {
                name: 'Đổi trạng thái Tin',
                method: 'PATCH',
                url: `${BASE_URL}/admin/tin-tuyen-dungs/1/trang-thai`,
                auth: true,
                info: `<strong>PATCH /api/v1/admin/tin-tuyen-dungs/{id}/trang-thai</strong><br>Admin khoá / mở khoá tin vi phạm.`
            },
            'admin-tin-delete': {
                method: 'DELETE',
                url: `${BASE_URL}/admin/tin-tuyen-dungs/1`,
                auth: true,
                info: `<strong>DELETE /api/v1/admin/tin-tuyen-dungs/{id}</strong><br>Admin xoá vĩnh viễn tin vi phạm.`
            },

            // ================== THỐNG KÊ LƯU TIN (ADMIN) ==================
            'admin-luu-thong-ke': {
                method: 'GET',
                url: `${BASE_URL}/admin/luu-tins/thong-ke`,
                auth: true,
                info: `<strong>GET /api/v1/admin/luu-tins/thong-ke</strong><br>Trả về Top 10 Tin Tuyển Dụng được nhiều ứng viên bookmark (lưu) nhất hệ thống.`
            },

            // ================== QUẢN LÝ ỨNG TUYỂN (ADMIN) ==================
            'admin-ungtuyen-list': {
                method: 'GET',
                url: `${BASE_URL}/admin/ung-tuyens?per_page=15`,
                auth: true,
                info: `<strong>GET /api/v1/admin/ung-tuyens</strong><br>Xem danh sách nộp CV toàn hệ thống.<br>Có thể lọc bằng ID công ty: <code>?cong_ty_id=1</code>`
            },
            'admin-ungtuyen-thongke': {
                method: 'GET',
                url: `${BASE_URL}/admin/ung-tuyens/thong-ke`,
                auth: true,
                info: `<strong>GET /api/v1/admin/ung-tuyens/thong-ke</strong><br>Thống kê phễu tuyển dụng trên toàn hệ thống (Bao nhiêu đơn mới nộp chờ duyệt, bao nhiêu đơn đã pass/fail).`
            },

            // ================== GIÁM SÁT AI MATCHING (ADMIN) ==================
            'admin-matching-list': {
                name: 'Danh sách Matching',
                method: 'GET',
                url: `${BASE_URL}/admin/ket-qua-matchings?per_page=15`,
                auth: true,
                info: `<strong>GET /api/v1/admin/ket-qua-matchings</strong><br>Bảng điều khiển cho Kỹ sư / Admin theo dõi dữ liệu của thuật toán đang chạy.<br>Các params test hệ thống:<br><code>?model_version=v1.0-tfidf</code><br><code>?min_score=80</code><br><code>?max_score=40</code>`
            },
            'admin-matching-thongke': {
                name: 'Thống kê Hiệu suất AI',
                method: 'GET',
                url: `${BASE_URL}/admin/ket-qua-matchings/thong-ke`,
                auth: true,
                info: `<strong>GET /api/v1/admin/ket-qua-matchings/thong-ke</strong><br>Thống kê hiệu quả hoạt động theo Version thuật toán: Lượng dữ liệu tính toán, điểm cao nhất, thấp nhất, trung bình...`
            },
            'admin-tuvan-list': {
                name: 'Lịch sử Tư vấn CV',
                method: 'GET',
                url: `${BASE_URL}/admin/tu-van-nghe-nghieps?per_page=15`,
                auth: true,
                info: `<strong>GET /api/v1/admin/tu-van-nghe-nghieps</strong><br>Quản lý toàn bộ lịch sử các lời khuyên mà AI đã cấp cho ứng viên.<br>Params: <code>?min_score=90</code> | <code>?nghe_de_xuat=Backend</code>`
            },
            'admin-tuvan-thongke': {
                name: 'Thống kê Định Hướng',
                method: 'GET',
                url: `${BASE_URL}/admin/tu-van-nghe-nghieps/thong-ke`,
                auth: true,
                info: `<strong>GET /api/v1/admin/tu-van-nghe-nghieps/thong-ke</strong><br>Dashboard Phân tích Tổng hợp: Biết được AI đang điều tiết dòng ứng viên đổ về mảng nghề nghiệp nào nhiều nhất.`
            }
        };

        function selectEndpoint(id) {
            currentEndpointId = id;
            const ep = ENDPOINTS[id];
            if (!ep) return;

            // Update active state
            document.querySelectorAll('.endpoint-item').forEach(el => el.classList.remove('active'));
            event.currentTarget.classList.add('active');

            // Set method & URL
            document.getElementById('methodSelect').value = ep.method;
            document.getElementById('urlInput').value = ep.url;

            // Render params
            renderParams(ep);

            // Render body
            if (ep.body) {
                document.getElementById('bodyTextarea').value = JSON.stringify(ep.body, null, 4);
            } else {
                document.getElementById('bodyTextarea').value = '';
            }

            // Quick fill buttons
            renderQuickFill(ep.quickFill || []);

            // Info
            document.getElementById('endpointInfo').innerHTML = ep.info || 'Không có thông tin.';

            // Auth toggle
            useAuth = ep.auth !== false;
            updateAuthSwitch();

            // Switch to params tab
            switchTab('params');
        }

        function renderParams(ep) {
            const container = document.getElementById('paramsFields');

            if (ep.params && ep.params.length) {
                let html = '<div class="field-group">';
                ep.params.forEach(p => {
                    if (p.key === '__id__') {
                        html += `<div class="field">
                    <label class="field-label">${p.label} <span class="field-required">*</span></label>
                    <input type="text" class="field-input" id="param-__id__" value="${p.value}" placeholder="ID...">
                </div>`;
                    } else {
                        html += `<div class="field">
                    <label class="field-label">${p.label}</label>
                    <input type="text" class="field-input" id="param-${p.key}" value="${p.value}" placeholder="${p.key}...">
                </div>`;
                    }
                });
                html += '</div>';
                container.innerHTML = html;

                // Listen for ID change
                const idInput = document.getElementById('param-__id__');
                if (idInput) {
                    idInput.addEventListener('input', () => {
                        const ep = ENDPOINTS[currentEndpointId];
                        if (!ep) return;
                        const newId = idInput.value.trim() || idInput.getAttribute('placeholder');
                        // Reconstruct URL based on endpoint pattern
                        let base = ep.url;
                        const urlParts = ep.url.split('/');
                        const lastPart = urlParts[urlParts.length - 1];
                        // Check if URL ends with a suffix like /khoa or /trang-thai
                        if (['khoa', 'trang-thai'].includes(lastPart)) {
                            // Replace the second-to-last part (the ID)
                            urlParts[urlParts.length - 2] = newId;
                            base = urlParts.join('/');
                        } else {
                            // Replace the last part (the ID)
                            urlParts[urlParts.length - 1] = newId;
                            base = urlParts.join('/');
                        }
                        document.getElementById('urlInput').value = base;
                    });
                }
                return;
            }

            container.innerHTML = `<p style="font-size:13px;color:var(--text-muted)">Không có query params. Xem tab <strong>Body</strong> để nhập dữ liệu.</p>`;
        }

        function renderQuickFill(fills) {
            const container = document.getElementById('quickFillBtns');
            if (!fills || fills.length === 0) {
                container.innerHTML = '';
                return;
            }
            container.innerHTML = fills.map(f =>
              `<button class="quick-btn" onclick='fillBody(${JSON.stringify(f.body)})'>${f.label}</button>`
            ).join('');
        }

        function fillBody(body) {
            document.getElementById('bodyTextarea').value = JSON.stringify(body, null, 4);
        }

        function switchTab(name) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            event?.currentTarget?.classList?.add('active');
            document.getElementById(`tab-${name}`).classList.add('active');

            // Highlight correct tab button
            document.querySelectorAll('.tab-btn').forEach(btn => {
                if (btn.getAttribute('onclick')?.includes(name)) btn.classList.add('active');
            });
        }

        function toggleAuth() {
            useAuth = !useAuth;
            updateAuthSwitch();
        }

        function updateAuthSwitch() {
            const sw = document.getElementById('authSwitch');
            if (useAuth) { sw.classList.add('on'); } else { sw.classList.remove('on'); }
        }

        function updateTokenDisplay() {
            const dot = document.getElementById('tokenDot');
            const display = document.getElementById('tokenDisplay');
            const clearBtn = document.getElementById('clearTokenBtn');
            const status = document.getElementById('tokenStatus');

            if (accessToken) {
                dot.classList.add('active');
                status.textContent = 'Đã đăng nhập';
                display.textContent = accessToken.substring(0, 40) + '...';
                display.classList.remove('token-empty');
                clearBtn.style.display = 'block';
            } else {
                dot.classList.remove('active');
                status.textContent = 'Chưa đăng nhập';
                display.textContent = 'Chưa có token — hãy đăng nhập trước';
                display.classList.add('token-empty');
                clearBtn.style.display = 'none';
            }
        }

        function clearToken() {
            accessToken = '';
            localStorage.removeItem('kltn_access_token');
            updateTokenDisplay();
        }

        async function sendRequest() {
            const method = document.getElementById('methodSelect').value;
            let url = document.getElementById('urlInput').value.trim();
            const manualToken = document.getElementById('manualToken').value.trim();
            const token = manualToken || accessToken;

            // Build query params for GET
            if (method === 'GET' && currentEndpointId) {
                const ep = ENDPOINTS[currentEndpointId];
                if (ep && ep.params) {
                    const params = new URLSearchParams();
                    ep.params.forEach(p => {
                        if (p.key === '__id__') return;
                        const val = document.getElementById(`param-${p.key}`)?.value?.trim();
                        if (val) params.append(p.key, val);
                    });
                    const qs = params.toString();
                    if (qs) url += '?' + qs;
                }
            }

            // Build body
            let body = null;
            const bodyText = document.getElementById('bodyTextarea').value.trim();
            if (['POST', 'PUT', 'PATCH'].includes(method) && bodyText) {
                try { body = bodyText; JSON.parse(bodyText); }
                catch { showError('JSON body không hợp lệ.'); return; }
            }

            // Build headers
            const headers = {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            };
            if (useAuth && token) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            // Loading state
            const sendBtn = document.getElementById('sendBtn');
            const sendIcon = document.getElementById('sendIcon');
            const sendText = document.getElementById('sendText');
            sendBtn.disabled = true;
            sendIcon.textContent = '⟳';
            sendIcon.classList.add('spin');
            sendText.textContent = 'Đang gửi...';

            const startTime = Date.now();

            try {
                const res = await fetch(url, {
                    method,
                    headers,
                    body: body || null,
                });

                const elapsed = Date.now() - startTime;
                let json = null;
                let rawText = '';

                try {
                    json = await res.json();
                    rawText = JSON.stringify(json, null, 2);
                } catch {
                    rawText = await res.text();
                }

                // Auto-save token on login
                if (res.ok && json?.data?.access_token) {
                    accessToken = json.data.access_token;
                    localStorage.setItem('kltn_access_token', accessToken);
                    updateTokenDisplay();
                }

                // Auto-clear token on logout
                if (res.ok && currentEndpointId === 'dang-xuat') {
                    clearToken();
                }

                renderResponse(res.status, rawText, elapsed);

            } catch (err) {
                renderError(`Không thể kết nối đến server.\n\nLỗi: ${err.message}\n\nHãy đảm bảo server đang chạy tại http://localhost:8000`);
            } finally {
                sendBtn.disabled = false;
                sendIcon.classList.remove('spin');
                sendIcon.textContent = '▶';
                sendText.textContent = 'Gửi';
            }
        }

        function renderResponse(status, body, elapsed) {
            const panel = document.getElementById('responsePanel');

            const statusClass = status >= 500 ? 'status-5xx' : status >= 400 ? 'status-4xx' : 'status-2xx';
            const highlighted = syntaxHighlight(body);

            panel.innerHTML = `
        <div class="response-header">
            <span class="response-title">Response</span>
            <span class="status-badge ${statusClass}">HTTP ${status}</span>
            <span class="response-time">⏱ ${elapsed}ms</span>
        </div>
        <div class="response-body">${highlighted}</div>
    `;
        }

        function renderError(msg) {
            const panel = document.getElementById('responsePanel');
            panel.innerHTML = `
        <div class="response-header">
            <span class="response-title">Response</span>
            <span class="status-badge status-5xx">Lỗi kết nối</span>
        </div>
        <div class="response-body" style="color:var(--error)">${msg}</div>
    `;
        }

        function showError(msg) {
            alert(msg);
        }

        function syntaxHighlight(json) {
            if (!json) return '';
            json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, match => {
                let cls = 'json-number';
                if (/^"/.test(match)) {
                    cls = /:$/.test(match) ? 'json-key' : 'json-string';
                } else if (/true|false/.test(match)) {
                    cls = 'json-bool';
                } else if (/null/.test(match)) {
                    cls = 'json-null';
                }
                return `<span class="${cls}">${match}</span>`;
            });
        }

        // Allow Ctrl+Enter to send
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') sendRequest();
        });
    </script>
</body>

</html>