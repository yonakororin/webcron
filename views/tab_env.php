<div id="env_settings_inner" class="tab-content" style="display: <?= ($current_tab === 'env_settings' ? 'block' : 'none') ?>;">
    <div class="settings-section">
        <h3>システム設定</h3>
        <form method="POST" class="settings-form">
            <input type="hidden" name="update_settings" value="1">
            <label for="cron_user">Cron実行ユーザー:</label>
            <input type="text" id="cron_user" name="cron_user" value="<?= htmlspecialchars($cron_user_setting) ?>" required>
            <input type="submit" value="設定を保存">
        </form>
        <small style="color: #666;">※ /etc/cron.d/ ファイルに記述される実行ユーザー名です（例: root, www-data）。</small>
    </div>

    <div>
        <h2>環境変数の設定 (crontab冒頭に定義)</h2>
        <form method="POST">
            <input type="hidden" name="env_add" value="1">
            <div class="env-input-group">
                <input type="text" id="env_name_input" name="env_name" placeholder="変数名 (例: PATH)" required>
                <input type="text" id="env_value_input" name="env_value" placeholder="変数値">
                <input type="text" id="env_desc_input" name="env_description" placeholder="説明">
                <input type="submit" id="env_submit_btn" value="登録/更新">
            </div>
        </form>

        <table class="env-table">
            <thead><tr><th>変数名</th><th>値</th><th>説明</th><th>操作</th></tr></thead>
            <tbody>
                <?php if (empty($env_vars)): ?><tr><td colspan="4" style="text-align:center">登録なし</td></tr><?php else: foreach ($env_vars as $env): ?>
                <tr>
                    <td><div class="env-scrollable var-env"><?= htmlspecialchars($env['name']) ?></div></td>
                    <td><div class="env-scrollable"><?= htmlspecialchars($env['value']) ?></div></td>
                    <td><?= htmlspecialchars($env['description']) ?></td>
                    <td>
                        <button type="button" class="edit-env-btn" 
                            onclick="editEnvVar(this)"
                            data-name="<?= htmlspecialchars($env['name']) ?>"
                            data-value="<?= htmlspecialchars($env['value']) ?>"
                            data-desc="<?= htmlspecialchars($env['description']) ?>">
                            編集
                        </button>
                        <form method="POST" style="margin:0;"><input type="hidden" name="env_delete" value="1"><input type="hidden" name="env_id" value="<?= $env['id'] ?>"><input type="submit" value="削除" class="delete-env-btn" onclick="return confirm('削除しますか？');"></form>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">

        <h2>ラッパースクリプト定義 (変数として埋め込み)</h2>
        <p style="font-size: 13px; color: #666; margin-top: -10px; margin-bottom: 15px;">
            ジョブコマンド内で <code>${変数名}</code> と記述すると、生成時に以下の値に置換されます。
        </p>

        <form method="POST">
            <input type="hidden" name="wrapper_add" value="1">
            <div class="env-input-group">
                <input type="text" id="wrapper_name_input" name="wrapper_name" placeholder="変数名 (例: WRAPPER_TEST)" required>
                <input type="text" id="wrapper_value_input" name="wrapper_value" placeholder="値 (例: export A=1; /path/to/wrapper)">
                <input type="text" id="wrapper_desc_input" name="wrapper_description" placeholder="説明">
                <input type="submit" id="wrapper_submit_btn" value="登録/更新">
            </div>
        </form>

        <table class="env-table">
            <thead><tr><th>変数名</th><th>値 (置換内容)</th><th>説明</th><th>操作</th></tr></thead>
            <tbody>
                <?php if (empty($wrappers)): ?><tr><td colspan="4" style="text-align:center">登録なし</td></tr><?php else: foreach ($wrappers as $wrap): ?>
                <tr>
                    <td><div class="env-scrollable var-wrapper"><?= htmlspecialchars($wrap['name']) ?></div></td>
                    
                    <td><div class="env-scrollable"><?= highlightCommand($wrap['value'], $env_names, $wrapper_names) ?></div></td>
                    
                    <td><?= htmlspecialchars($wrap['description']) ?></td>
                    <td>
                        <button type="button" class="edit-env-btn" 
                            onclick="editWrapper(this)"
                            data-name="<?= htmlspecialchars($wrap['name']) ?>"
                            data-value="<?= htmlspecialchars($wrap['value']) ?>"
                            data-desc="<?= htmlspecialchars($wrap['description']) ?>">
                            編集
                        </button>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="wrapper_delete" value="1">
                            <input type="hidden" name="wrapper_id" value="<?= $wrap['id'] ?>">
                            <input type="submit" value="削除" class="delete-env-btn" onclick="return confirm('削除しますか？');">
                        </form>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>