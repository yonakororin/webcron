<?php
session_start();
require_once __DIR__ . '/common.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>WebCron ジョブ管理</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

  <style>
    body { overflow: hidden; }
    .sidebar {
      width: 240px; min-width: 240px; min-height: 100vh;
      background: #0f172a; transition: width 0.3s, min-width 0.3s;
      overflow-x: hidden; overflow-y: auto;
    }
    .sidebar.closed { width: 60px; min-width: 60px; }
    .sidebar.closed .sidebar-content,
    .sidebar.closed .sidebar-title { display: none; }
    .main-content { flex: 1; overflow-y: auto; height: 100vh; background: #0f172a; color: #f8fafc; }
    .content-pane { display: none; }
    .content-pane.active { display: block; animation: fadeIn 0.3s ease; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    .tab-canvas {
      position: relative; width: 100%; min-height: 400px;
    }
    .component { position: absolute; border: 1px solid #dee2e6; background: #fff; padding: 4px; box-sizing: border-box; border-radius: 6px; max-width: calc(100% - 48px); }
    .c-html { display: block; overflow: auto; background: transparent; border: none; }
    .c-input { display: flex; flex-direction: column; gap: 2px; }
    .c-radio-check { display: flex; flex-direction: column; gap: 0; }
    .c-table { display: flex; flex-direction: column; padding: 0; overflow: hidden; }
    .c-button { display: flex; justify-content: center; align-items: center; background: transparent; border: none; padding: 4px; }
    .c-button button { width: 100%; height: 100%; }
    
    .glb-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 999999 !important; opacity: 0; pointer-events: none; transition: opacity 0.2s; backdrop-filter: blur(2px); }
    .glb-overlay.show { opacity: 1; pointer-events: auto; }
    .glb-modal { background: #fff; border-radius: 12px; width: 400px; max-width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); transform: translateY(20px); transition: transform 0.2s; display: flex; flex-direction: column; overflow: hidden; }
    .glb-overlay.show .glb-modal { transform: translateY(0); }
    .glb-modal-header { padding: 16px 20px; font-weight: 600; font-size: 1.1rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .glb-modal-body { padding: 20px; font-size: 0.95rem; line-height: 1.5; color: #334155; overflow-y: auto; max-height: 60vh; }
    .glb-modal-footer { padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px; }
    .glb-btn { padding: 8px 16px; border-radius: 6px; border: none; font-size: 0.9rem; font-weight: 500; cursor: pointer; transition: all 0.2s; }
    .glb-btn-cancel { background: #e2e8f0; color: #475569; }
    .glb-btn-cancel:hover { background: #cbd5e1; }
    .glb-btn-primary { background: #4f46e5; color: #fff; }
    .glb-btn-primary:hover { filter: brightness(1.1); }
    .glb-btn-info { background: #3b82f6; color: #fff; }
    .glb-btn-info:hover { background: #2563eb; }
    .glb-btn-warning { background: #f59e0b; color: #fff; }
    .glb-btn-warning:hover { background: #d97706; }
    .glb-btn-danger { background: #ef4444; color: #fff; }
    .glb-btn-danger:hover { background: #dc2626; }
    .glb-btn-none { background: transparent; color: #64748b; border: 1px solid transparent; }
    .glb-btn-none:hover { background: #f1f5f9; color: #334155; }
    .glb-input { width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; margin-top: 12px; box-sizing: border-box; font-family: inherit; }
    .glb-input:focus { outline: none; border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15); }
    .glb-loader { display: flex; flex-direction: column; align-items: center; gap: 12px; color: #fff; font-weight: 500; }
    .glb-spinner { width: 40px; height: 40px; border: 4px solid rgba(255,255,255,0.2); border-top-color: #fff; border-radius: 50%; animation: glb-spin 1s linear infinite; }
    @keyframes glb-spin { to { transform: rotate(360deg); } }
    .glb-progress-box { background: #fff; padding: 20px; border-radius: 12px; width: 300px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); text-align: center; }
    .glb-progress-label { font-size: 0.9rem; font-weight: 600; color: #334155; margin-bottom: 12px; }
    .glb-progress-bar-wrap { width: 100%; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
    .glb-progress-bar-fill { height: 100%; background: #4f46e5; width: 0%; transition: width 0.3s ease; }
    .sidebar-nav-link.active { background: rgba(255,255,255,0.15); border-radius: 6px; }
    #manual-body { color: #e2e8f0; line-height: 1.75; }
    #manual-body h1, #manual-body h2 { color: #f8fafc; border-bottom: 1px solid rgba(148,163,184,0.2); padding-bottom: 0.3em; margin: 1.5em 0 0.75em; }
    #manual-body h3, #manual-body h4 { color: #cbd5e1; margin: 1.2em 0 0.5em; }
    #manual-body code { background: rgba(0,0,0,0.35); color: #a5b4fc; padding: 2px 6px; border-radius: 4px; font-size: 0.9em; }
    #manual-body pre { background: rgba(0,0,0,0.35); border: 1px solid rgba(148,163,184,0.2); border-radius: 6px; padding: 14px; overflow-x: auto; }
    #manual-body pre code { background: none; padding: 0; color: #e2e8f0; }
    #manual-body table { border-collapse: collapse; width: 100%; margin: 1em 0; }
    #manual-body th { background: #1e293b; color: #94a3b8; padding: 10px 12px; border: 1px solid rgba(148,163,184,0.2); text-align: left; }
    #manual-body td { padding: 9px 12px; border: 1px solid rgba(148,163,184,0.2); vertical-align: top; }
    #manual-body blockquote { border-left: 3px solid #a855f7; margin: 0; padding: 8px 16px; background: rgba(168,85,247,0.08); color: #94a3b8; }
    #manual-body hr { border: none; border-top: 1px solid rgba(148,163,184,0.2); margin: 2em 0; }

  </style>
</head>
<body>
  <div class="d-flex">
    <!-- Sidebar -->
    <nav class="sidebar d-flex flex-column p-3">
      <div class="d-flex align-items-center gap-2 mb-3 pb-3 border-bottom border-secondary">
        <button class="btn btn-outline-light btn-sm" onclick="document.querySelector('.sidebar').classList.toggle('closed')" title="メニューの開閉">
          <span class="material-icons">menu</span>
        </button>
        <h5 class="sidebar-title text-white fw-bold mb-0 text-truncate">WebCron ジョブ管理</h5>
      </div>
      <ul class="sidebar-content nav flex-column gap-1">
        <li class="nav-item">
          <a class="nav-link text-white sidebar-nav-link" href="#" onclick="if(typeof event !== 'undefined') event.preventDefault(); showContentDirect('content-jobs')" data-target="content-jobs">ジョブ管理</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white sidebar-nav-link" href="#" onclick="if(typeof event !== 'undefined') event.preventDefault(); showContentDirect('content-env')" data-target="content-env">環境設定</a>
        </li>
        <li class="nav-item mt-auto pt-3 border-top border-secondary">
          <a class="nav-link text-white sidebar-nav-link" href="#" onclick="if(typeof event !== 'undefined') event.preventDefault(); showContentDirect('content-manual')" data-target="content-manual">使い方</a>
        </li>
      </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content p-4 position-relative">

    <div class="dropdown position-absolute" style="top: 16px; right: 24px; z-index: 100;">
      <button id="user-menu-btn" class="btn btn-outline-light rounded-pill d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="color:#94a3b8; border-color:rgba(148,163,184,0.3);">
        <span class="material-icons" style="font-size:20px;">account_circle</span>
        <span id="user-name-display">ユーザー名</span>
        <span class="material-icons" style="font-size:16px;">arrow_drop_down</span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item d-flex align-items-center gap-2" href="#" id="user-action-password" onclick="if(typeof event !== 'undefined') event.preventDefault(); console.log('Click: Password Change');">
          <span class="material-icons" style="font-size:18px;">vpn_key</span> パスワード変更
        </a></li>
        <li><a class="dropdown-item d-flex align-items-center gap-2" href="#" id="user-action-logout" onclick="if(typeof event !== 'undefined') event.preventDefault(); console.log('Click: Logout');">
          <span class="material-icons" style="font-size:18px;">logout</span> ログアウト
        </a></li>
      </ul>
    </div>

    <div id="content-jobs" class="content-pane">
      <h2 class="fw-bold mb-3" style="color:#f8fafc;">ジョブ管理</h2>
      <link rel="stylesheet" href="assets/style.css">
      <?php if ($message): ?><div class="message success"><?= $message ?></div><?php endif; ?>
      <?php if ($error): ?><div class="message error">🚨 エラー: <?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php require __DIR__ . '/views/tab_jobs.php'; ?>
      <script src="https://cdn.jsdelivr.net/npm/marked@12.0.2/marked.min.js"></script>
      <script src="assets/script.js"></script>
    </div>

    <div id="content-env" class="content-pane">
      <h2 class="fw-bold mb-3" style="color:#f8fafc;">環境設定</h2>
      <link rel="stylesheet" href="assets/style.css">
      <?php if ($message): ?><div class="message success"><?= $message ?></div><?php endif; ?>
      <?php if ($error): ?><div class="message error">🚨 エラー: <?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php require __DIR__ . '/views/tab_env.php'; ?>
      <script src="assets/script.js"></script>
    </div>

    <div id="content-manual" class="content-pane">
      <h2 class="fw-bold mb-3" style="color:#f8fafc;">使い方</h2>
      <link rel="stylesheet" href="assets/style.css">
      <div id="manual-body" style="max-width: 860px;"></div>
    </div>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/marked@12.0.2/marked.min.js"></script>
  <script>
    
    window.UI = {
      createOverlay(id, innerHtml) {
        let el = document.getElementById(id);
        if (!el) { el = document.createElement('div'); el.id = id; el.className = 'glb-overlay'; el.innerHTML = innerHtml; document.body.appendChild(el); }
        return el;
      },
      showLoading(text = "Now Loading...") {
        const el = this.createOverlay('glb-loading-overlay', '<div class="glb-loader"><div class="glb-spinner"></div><div id="glb-loading-text"></div></div>');
        document.getElementById('glb-loading-text').innerText = text;
        void el.offsetWidth; el.classList.add('show');
      },
      hideLoading() { const el = document.getElementById('glb-loading-overlay'); if (el) el.classList.remove('show'); },
      showProgress(percent, labelText = "Processing...") {
        const el = this.createOverlay('glb-progress-overlay', '<div class="glb-progress-box"><div class="glb-progress-label" id="glb-progress-label"></div><div class="glb-progress-bar-wrap"><div class="glb-progress-bar-fill" id="glb-progress-fill"></div></div></div>');
        document.getElementById('glb-progress-label').innerText = labelText;
        document.getElementById('glb-progress-fill').style.width = Math.min(100, Math.max(0, percent)) + '%';
        void el.offsetWidth; el.classList.add('show');
      },
      hideProgress() { const el = document.getElementById('glb-progress-overlay'); if (el) el.classList.remove('show'); },
      showModal(optOrTitle, contHtml, onConfirm, onCancel, btnConfirm = "OK", btnCancel = "キャンセル") {
        let options = optOrTitle;
        if (typeof optOrTitle === 'string') {
          options = { title: optOrTitle, contentHtml: contHtml || '', buttons: [
            { label: btnCancel, style: 'None', onClick: onCancel },
            { label: btnConfirm, style: 'Primary', onClick: onConfirm }
          ]};
        }
        const { title = '', contentHtml = '', buttons = [] } = options || {};
        const el = this.createOverlay('glb-modal-overlay', '<div class="glb-modal"><div class="glb-modal-header" id="glb-modal-header"></div><div class="glb-modal-body" id="glb-modal-body"></div><div class="glb-modal-footer" id="glb-modal-footer"></div></div>');
        document.getElementById('glb-modal-header').innerText = title;
        document.getElementById('glb-modal-body').innerHTML = contentHtml;
        const footer = document.getElementById('glb-modal-footer');
        footer.innerHTML = '';
        buttons.forEach((btnInfo) => {
          const btn = document.createElement('button');
          const style = (btnInfo.style || 'Primary').toLowerCase();
          btn.className = 'glb-btn glb-btn-' + (style === 'none' ? 'none' : (['primary', 'info', 'warning', 'danger'].includes(style) ? style : 'cancel'));
          btn.innerText = btnInfo.label || 'Button';
          btn.onclick = () => { if (btnInfo.onClick) btnInfo.onClick(); this.hideModal(); };
          footer.appendChild(btn);
        });
        void el.offsetWidth; el.classList.add('show');
      },
      hideModal() { const el = document.getElementById('glb-modal-overlay'); if (el) el.classList.remove('show'); },
      showPrompt(optOrTitle, descHtml, defValue, onConfirm, onCancel, btnConfirm = "決定", btnCancel = "キャンセル") {
        let options = optOrTitle;
        if (typeof optOrTitle === 'string') {
          options = { title: optOrTitle, descriptionHtml: descHtml || '', defaultValue: defValue || '', buttons: [
            { label: btnCancel, style: 'None', onClick: onCancel },
            { label: btnConfirm, style: 'Info', onClick: (val) => { if(onConfirm) onConfirm(val); } }
          ]};
        }
        const { title = '値の入力', descriptionHtml = '', defaultValue = '', buttons = [] } = options || {};
        const bodyHtml = (descriptionHtml || '') + '<input type="text" id="glb-prompt-input" class="glb-input" value="' + (defaultValue || '').replace(/"/g, '&quot;') + '" />';
        const mappedButtons = buttons.map(btnInfo => ({
          label: btnInfo.label, style: btnInfo.style,
          onClick: () => { const val = document.getElementById('glb-prompt-input').value; if (btnInfo.onClick) btnInfo.onClick(val); }
        }));
        this.showModal({ title, contentHtml: bodyHtml, buttons: mappedButtons });
        setTimeout(() => { const input = document.getElementById('glb-prompt-input'); if (input) input.focus(); }, 100);
      }
    };


    function showContentDirect(id) {
      // Close any open collapse submenus safely
      if (typeof bootstrap !== 'undefined') {
        document.querySelectorAll('.sidebar .collapse.show').forEach(el => {
          bootstrap.Collapse.getOrCreateInstance(el).hide();
        });
      }
      showContent(id);
    }
    function showContent(id) {
      document.querySelectorAll('.content-pane').forEach(el => el.classList.remove('active'));
      document.querySelectorAll('.sidebar-nav-link').forEach(el => el.classList.remove('active'));
      const target = document.getElementById(id);
      if (target) {
        target.classList.add('active');
        window.dispatchEvent(new CustomEvent('glbContentShown', { detail: { id: id } }));
      }
      const navLink = document.querySelector('.sidebar-nav-link[data-target="' + id + '"]');
      if (navLink) navLink.classList.add('active');
    }
      function showTab(paneId, tabId) {
      const pane = document.getElementById(paneId);
      if (!pane) return;
      // Use Bootstrap Tab API
      const tabEl = pane.querySelector('[data-bs-target="#tab-' + paneId + '-' + tabId + '"]');
      if (tabEl) {
        bootstrap.Tab.getOrCreateInstance(tabEl).show();
      }
      window.dispatchEvent(new CustomEvent('glbTabShown', { detail: { paneId: paneId, tabId: tabId } }));
    }

    // マニュアルの遅延読み込み
    let manualLoaded = false;
    window.addEventListener('glbContentShown', (e) => {
      if (e.detail.id === 'content-manual' && !manualLoaded) {
        manualLoaded = true;
        fetch('manual.md').then(r => r.text()).then(md => {
          document.getElementById('manual-body').innerHTML = marked.parse(md);
        }).catch(() => {
          document.getElementById('manual-body').innerHTML = '<p style="color:#f87171;">マニュアルの読み込みに失敗しました。</p>';
        });
      }
    });

    // Automatically open the first menu item on load
    document.addEventListener('DOMContentLoaded', () => {
      showContentDirect('content-jobs');
    });
  </script>
  <script src="js/main.js"></script>
</body>
</html>