// ==========================================
// 1. タブ切り替え機能
// ==========================================
function openTab(evt, tabName) {
    // 全てのタブコンテンツを非表示にする
    const tabcontent = document.getElementsByClassName("tab-content");
    for (let i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    // 全てのタブボタンから active クラスを削除
    const tablinks = document.getElementsByClassName("tablinks");
    for (let i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }

    // 指定されたタブを表示
    const target = document.getElementById(tabName);
    if (target) {
        target.style.display = "block";
    } else {
        console.warn("Tab content not found:", tabName);
        return;
    }

    // evtがある場合（クリック時）は、そのボタンをactiveにする
    if (evt && evt.currentTarget) {
        evt.currentTarget.classList.add("active");
    } else {
        // evtがない場合（初期ロード時）、該当するボタンを探してactiveにする
        for (let i = 0; i < tablinks.length; i++) {
            if (tablinks[i].getAttribute('onclick') && tablinks[i].getAttribute('onclick').includes(tabName)) {
                tablinks[i].classList.add("active");
            }
        }
    }
}

// ==========================================
// 2. 編集フォームへの値セット機能
// ==========================================

// 環境変数の編集ボタンクリック時
function editEnvVar(button) {
    const name = button.getAttribute('data-name');
    const value = button.getAttribute('data-value');
    const desc = button.getAttribute('data-desc');

    document.getElementById('env_name_input').value = name;
    document.getElementById('env_value_input').value = value;
    document.getElementById('env_desc_input').value = desc;

    const btn = document.getElementById('env_submit_btn');
    btn.value = "更新";

    document.getElementById('env_settings').scrollIntoView({ behavior: 'smooth' });
}

// ラッパースクリプトの編集ボタンクリック時
function editWrapper(button) {
    const name = button.getAttribute('data-name');
    const value = button.getAttribute('data-value');
    const desc = button.getAttribute('data-desc');

    document.getElementById('wrapper_name_input').value = name;
    document.getElementById('wrapper_value_input').value = value;
    document.getElementById('wrapper_desc_input').value = desc;

    const btn = document.getElementById('wrapper_submit_btn');
    btn.value = "更新";

    // 入力フォーム付近へスクロール
    document.getElementById('wrapper_name_input').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// ==========================================
// 3. マニュアル表示 (モーダル)
// ==========================================
var modal = document.getElementById("manualModal");
var btn = document.getElementById("openManual");
var span = document.getElementsByClassName("close-button")[0];
var contentDiv = document.getElementById("manualContent");
var manualFilePath = 'manual.md';
var manualLoadedSuccessfully = false;

async function loadAndRenderManual() {
    try {
        const response = await fetch(manualFilePath);
        if (!response.ok) {
            throw new Error(`Failed to load ${manualFilePath}: ${response.statusText}`);
        }
        const markdownText = await response.text();

        // marked.js が読み込まれている前提
        if (typeof marked !== 'undefined') {
            contentDiv.innerHTML = marked.parse(markdownText);
        } else {
            contentDiv.innerHTML = "<pre>" + markdownText + "</pre>";
        }
        manualLoadedSuccessfully = true;
    } catch (error) {
        console.error('Error loading or rendering manual:', error);
        contentDiv.innerHTML = '<p style="color: red;">マニュアルのロードに失敗しました。<br>' + error.message + '</p>';
    }
}

if (btn) {
    btn.onclick = function () {
        modal.style.display = "block";
        if (!manualLoadedSuccessfully) {
            loadAndRenderManual();
        }
    }
}

if (span) {
    span.onclick = function () {
        modal.style.display = "none";
    }
}

window.onclick = function (event) {
    if (event.target === modal) {
        modal.style.display = "none";
    }
}

// ==========================================
// 4. 実行状況の自動更新 (ポーリング)
// ==========================================
let pollingIntervalId = null;

// APIを叩いて画面を更新する関数
async function updateJobStatuses() {
    try {
        // index.php のAPIモードへアクセス
        const response = await fetch('index.php?ajax_status=1');
        if (!response.ok) return;

        const data = await response.json();

        // data = { "job_id": "HTML content", ... }
        for (const [jobId, htmlContent] of Object.entries(data)) {
            // data-job-id 属性を持つ行を探す
            const row = document.querySelector(`tr[data-job-id="${jobId}"]`);
            if (row) {
                const cell = row.querySelector('.status-cell');
                // 内容が変化している場合のみ書き換え
                if (cell && cell.innerHTML !== htmlContent) {
                    cell.innerHTML = htmlContent;
                }
            }
        }
    } catch (e) {
        console.error('Status polling error:', e);
    }
}

// チェックボックスの状態に応じてタイマーを制御
function togglePolling() {
    const toggle = document.getElementById('statusPollingToggle');

    if (toggle && toggle.checked) {
        // ONの場合
        if (!pollingIntervalId) {
            updateJobStatuses(); // 初回即時実行
            pollingIntervalId = setInterval(updateJobStatuses, 60000); // 1分間隔
        }
    } else {
        // OFFの場合
        if (pollingIntervalId) {
            clearInterval(pollingIntervalId);
            pollingIntervalId = null;
        }
    }
}

// ==========================================
// 5. 初期化 & 絞り込み検索
// ==========================================

window.addEventListener('load', function () {
    // --- ポーリング初期化 ---
    const toggle = document.getElementById('statusPollingToggle');
    if (toggle) {
        toggle.checked = false; // デフォルトOFF
        toggle.addEventListener('change', togglePolling);
    }

    // --- 絞り込み検索機能 ---
    const searchInput = document.getElementById("searchKeyword");
    const jobTable = document.getElementById("jobTable");

    if (searchInput && jobTable) {
        searchInput.addEventListener('keyup', function () {
            const rawInput = searchInput.value; // 大文字変換は後で行う

            // 正規表現でトークンに分割
            // 1. [!！]?  -> 先頭に ! か ！ があってもよい
            // 2. "[^"]+" -> ダブルクォートで囲まれた文字列 (スペース許容)
            // 3. |       -> または
            // 4. [^\s]+  -> 空白以外の文字列 (通常の単語)
            const matches = rawInput.match(/([!！]?"[^"]+"|[^\s]+)/g) || [];

            const rows = jobTable.getElementsByTagName("tbody")[0].getElementsByTagName("tr");

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                // 行全体のテキストを取得し大文字化
                const rowText = (row.textContent || row.innerText).toUpperCase();

                let isMatch = true;

                for (let term of matches) {
                    let isNegative = false;
                    let keyword = term;

                    // 1. 除外判定 (! で始まるか)
                    if (keyword.startsWith('!') || keyword.startsWith('！')) {
                        isNegative = true;
                        keyword = keyword.slice(1); // 先頭の ! を削除
                    }

                    // 2. クォート除去 (" で囲まれているか)
                    if (keyword.startsWith('"') && keyword.endsWith('"') && keyword.length >= 2) {
                        keyword = keyword.slice(1, -1); // 前後の " を削除
                    }

                    // 3. 大文字化して比較
                    keyword = keyword.toUpperCase();

                    // キーワードが空ならスキップ
                    if (keyword === "") continue;

                    if (isNegative) {
                        // 除外検索: 含んでいたらNG
                        if (rowText.indexOf(keyword) > -1) {
                            isMatch = false;
                            break;
                        }
                    } else {
                        // 通常検索: 含んでいなければNG
                        if (rowText.indexOf(keyword) === -1) {
                            isMatch = false;
                            break;
                        }
                    }
                }

                row.style.display = isMatch ? "" : "none";
            }
        });
    }
    // ==========================================
    // 6. User Dropdown Logic
    // ==========================================
    // ==========================================
    // 6. User Dropdown Logic
    // ==========================================
    const userBtn = document.querySelector('.user-btn');
    const dropdownContent = document.querySelector('.dropdown-content');

    if (userBtn && dropdownContent) {
        userBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdownContent.classList.toggle('show');
        });

        document.addEventListener('click', (e) => {
            if (!dropdownContent.contains(e.target) && !userBtn.contains(e.target)) {
                dropdownContent.classList.remove('show');
            }
        });

        dropdownContent.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                dropdownContent.classList.remove('show');
            });
        });
    }
});