<div style="max-width: 1100px;">
  <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
    <h2 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #f8fafc;">Crontabファイル</h2>
    <span id="crontab-mtime" style="font-size: 0.8rem; color: #64748b;"></span>
    <button onclick="loadCrontabFile()" class="btn btn-sm" style="margin-left: auto; background: rgba(168,85,247,0.18); color: #d8b4fe; border: 1px solid rgba(168,85,247,0.3);">
      <span class="material-icons" style="font-size: 15px; vertical-align: middle;">refresh</span>
      更新
    </button>
  </div>
  <div id="crontab-path" style="font-size: 0.8rem; color: #475569; font-family: monospace; margin-bottom: 10px;"></div>
  <pre id="crontab-content" style="
    background: #0d1117;
    border: 1px solid rgba(148,163,184,0.15);
    border-radius: 8px;
    padding: 20px;
    color: #e2e8f0;
    font-size: 0.875rem;
    line-height: 1.7;
    overflow-x: auto;
    white-space: pre;
    min-height: 200px;
  ">読み込み中...</pre>
</div>
