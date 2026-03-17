<div id="job_manager_inner" class="tab-content" style="display: <?= ($current_tab === 'job_manager' ? 'block' : 'none') ?>;">
    <?php if ($job_to_edit): ?>
    <h2>ジョブID: <?= $job_to_edit['id'] ?> の編集</h2>
    <form method="POST" id="editJobForm" style="border-color: #007bff;">
        <input type="hidden" name="update" value="1">
        <input type="hidden" name="id" value="<?= $job_to_edit['id'] ?>">
        <div class="form-group"><label>スケジュール</label><input type="text" name="schedule" value="<?= htmlspecialchars($job_to_edit['schedule'], ENT_QUOTES) ?>" required></div>
        <div class="form-group"><label>実行コマンド</label><input type="text" name="command" value="<?= htmlspecialchars($job_to_edit['command'], ENT_QUOTES) ?>" required></div>
        <div class="form-group"><label>説明</label><textarea name="description" rows="2"><?= htmlspecialchars($job_to_edit['description']) ?></textarea></div>
    </form>
    
    <div class="form-actions">
        <div class="actions-left">
            <input type="submit" value="ジョブを更新" form="editJobForm">
            <a href="<?= $_SERVER['PHP_SELF'] ?>" class="cancel-button">キャンセル</a>
            <input type="submit" name="delete" value="削除" onclick="return confirm('削除しますか？');" class="delete-submit-button" form="editJobForm">
        </div>
        <div class="actions-right">
            <form method="POST" style="margin: 0;">
                <input type="hidden" name="apply_crontab" value="1">
                <button type="submit" class="apply-button" onclick="return confirm('現在の設定でサーバーのCrontabを上書き更新します。\nよろしいですか？');">
                    🔄 Crontabに反映
                </button>
            </form>
        </div>
    </div>
    <hr>
    <?php else: ?>
    <h2>新規ジョブ追加</h2>
    <form method="POST" id="addJobForm">
        <input type="hidden" name="add" value="1">
        <div class="form-group"><label>スケジュール (例: * * * * *)</label><input type="text" name="schedule" placeholder="* * * * *" required></div>
        <div class="form-group"><label>実行コマンド</label><input type="text" name="command" placeholder="php /var/www/html/script.php" required></div>
        <div class="form-group"><label>説明 (任意)</label><textarea name="description" rows="2" placeholder="夜間バッチ処理"></textarea></div>
    </form>
    
    <div class="form-actions">
        <div class="actions-left">
            <input type="submit" value="ジョブを追加" form="addJobForm">
        </div>
        <div class="actions-right">
            <form method="POST" style="margin: 0;">
                <input type="hidden" name="apply_crontab" value="1">
                <button type="submit" class="apply-button" onclick="return confirm('現在の設定でサーバーのCrontabを上書き更新します。\nよろしいですか？');">
                    🔄 Crontabに反映
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <h2>登録済みジョブ一覧 (<?= count($jobs) ?>件)</h2>
    <input type="text" id="searchKeyword" placeholder='検索... (例: php !"Err: 0")'>
    <div class="job-table-wrapper">
        <table id="jobTable">
            <thead>
                <tr>
                    <th>ID</th>
                    
                    <th>スケジュール</th>
                    <th>コマンド</th>
                    <th style="vertical-align: middle;">
                        実行状況確認
                        <div style="font-size: 10px; font-weight: normal; margin-top: 2px; display: inline;">
                            <label style="cursor: pointer;">
                                <input type="checkbox" id="statusPollingToggle" style="vertical-align: middle;">自動更新(1分)
                            </label>
                        </div>
                    </th>

                    <th>説明</th>

                </tr>
            </thead>
            <tbody>
                <?php if (empty($jobs)): ?><tr><td colspan="5" style="text-align:center">登録なし</td></tr><?php else: 
                $cur = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
                foreach ($jobs as $job): 
                    $cls = ($job['id'] === $cur) ? 'selected-row' : '';
                    
                    // ★ 追加: コメントアウト判定 ★
                    if (strpos(trim($job['command']), '#') === 0) {
                        $cls .= ' disabled-row';
                    }
                ?>
                <tr class="<?= $cls ?>" data-job-id="<?= $job['id'] ?>">
                    
                    <td><a href="?edit_id=<?= $job['id'] ?>" class="id-link">✏️ <?= $job['id'] ?></a></td>
                    <td><code title="<?= htmlspecialchars($job['schedule'], ENT_QUOTES) ?>"><?= htmlspecialchars($job['schedule']) ?></code></td>
                    <td><code title="<?= htmlspecialchars($job['command'], ENT_QUOTES) ?>"><?= highlightCommand($job['command'], $env_names, $wrapper_names) ?></code></td>
                
                    <td class="status-cell" style="font-size: 14px; line-height: 1.2; vertical-align: middle;">
                        <?php if (empty($job['start_time'])): ?>
                            <span style="color: #999;">未実行</span>
                        <?php else: ?>
                            <div style="display: inline;"><span style="color: #0d6efd; font-weight: bold;">開始：</span><?= date('m/d H:i:s', strtotime($job['start_time'])) ?></div>
                            <?php if ($job['end_time']): ?>
                                <div style="display: inline;"><span style="color:rgb(106, 25, 135); font-weight: bold;">終了：</span><?= date('m/d H:i:s', strtotime($job['end_time'])) ?></div>
                                <?php if ($job['exit_code'] !== 0 && $job['exit_code'] !== null): ?>
                                    <?php if ($job['exit_code'] == 0): ?>
                                        <div style="color:rgb(32, 185, 85); font-weight: bold; display: inline;">Err: <?= $job['exit_code'] ?></div>
                                    <?php else: ?>
                                        <div style="color: #dc3545; font-weight: bold; display: inline;">Err: <?= $job['exit_code'] ?></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <div style="display: inline; color: #fd7e14; font-weight: bold;">実行中...</div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>

                    <td><?= htmlspecialchars($job['description']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>