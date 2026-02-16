<?php
/**
 * Веб-интерфейс для скачивания бэкапов
 * Доступ: https://ru-pills.com/backup-download.php
 * 
 * ВАЖНО: Защитите этот файл паролем через .htaccess!
 */

// Простая защита паролем (замените на свой пароль)
$BACKUP_PASSWORD = 'your_backup_password_here_change_me';
$BACKUP_DIR = '/var/www/backups';

// Проверка авторизации
session_start();
if (!isset($_SESSION['backup_authenticated'])) {
    if (isset($_POST['password']) && $_POST['password'] === $BACKUP_PASSWORD) {
        $_SESSION['backup_authenticated'] = true;
    } else {
        ?>
        <!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Доступ к бэкапам</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                }
                .login-box {
                    background: white;
                    padding: 40px;
                    border-radius: 10px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                    max-width: 400px;
                    width: 100%;
                }
                h1 {
                    margin: 0 0 30px 0;
                    color: #333;
                    text-align: center;
                }
                input[type="password"] {
                    width: 100%;
                    padding: 12px;
                    border: 2px solid #ddd;
                    border-radius: 5px;
                    font-size: 16px;
                    box-sizing: border-box;
                    margin-bottom: 20px;
                }
                button {
                    width: 100%;
                    padding: 12px;
                    background: #667eea;
                    color: white;
                    border: none;
                    border-radius: 5px;
                    font-size: 16px;
                    cursor: pointer;
                    transition: background 0.3s;
                }
                button:hover {
                    background: #5568d3;
                }
                .error {
                    color: #e74c3c;
                    margin-bottom: 20px;
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class="login-box">
                <h1>🔒 Доступ к бэкапам</h1>
                <?php if (isset($_POST['password'])): ?>
                    <div class="error">Неверный пароль!</div>
                <?php endif; ?>
                <form method="POST">
                    <input type="password" name="password" placeholder="Введите пароль" required autofocus>
                    <button type="submit">Войти</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Получение списка файлов бэкапов
function getBackupFiles($dir) {
    $files = [];
    if (is_dir($dir)) {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === '.gitkeep') continue;
            $path = $dir . '/' . $item;
            if (is_file($path)) {
                $files[] = [
                    'name' => $item,
                    'size' => filesize($path),
                    'date' => filemtime($path),
                    'path' => $path
                ];
            }
        }
    }
    // Сортируем по дате (новые сверху)
    usort($files, function($a, $b) {
        return $b['date'] - $a['date'];
    });
    return $files;
}

// Скачивание файла
if (isset($_GET['download']) && $_SESSION['backup_authenticated']) {
    $file = basename($_GET['download']);
    $filepath = $BACKUP_DIR . '/' . $file;
    
    if (file_exists($filepath) && strpos(realpath($filepath), realpath($BACKUP_DIR)) === 0) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        die('Файл не найден или доступ запрещён!');
    }
}

// Выход
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: backup-download.php');
    exit;
}

$backupFiles = getBackupFiles($BACKUP_DIR);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бэкапы ru-pills.com</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .logout {
            color: #e74c3c;
            text-decoration: none;
            padding: 8px 16px;
            border: 1px solid #e74c3c;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .logout:hover {
            background: #e74c3c;
            color: white;
        }
        .info {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background: #f9f9f9;
        }
        .download-btn {
            background: #27ae60;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            transition: background 0.3s;
        }
        .download-btn:hover {
            background: #229954;
        }
        .file-size {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        .file-date {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        .latest-badge {
            background: #e74c3c;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.8em;
            margin-left: 5px;
        }
        .empty {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>📦 Бэкапы ru-pills.com</h1>
                <p style="color: #7f8c8d; margin-top: 5px;">Скачайте нужные файлы для восстановления</p>
            </div>
            <a href="?logout" class="logout">Выйти</a>
        </div>

        <div class="info">
            <strong>💡 Инструкция:</strong><br>
            • <strong>wp-version-latest.txt</strong> - версия WordPress (используется при восстановлении)<br>
            • <strong>db-latest.sql.gz</strong> - последний бэкап базы данных<br>
            • <strong>plugins-latest.tar.gz</strong> - последний бэкап плагинов<br>
            • <strong>uploads-latest.tar.gz</strong> - последний бэкап медиа файлов<br>
            • <strong>wp-config-latest.enc</strong> - зашифрованный wp-config.php (пароль в логе бэкапа)<br>
            • <strong>flatsome-child-latest.tar.gz</strong> - последний бэкап темы
        </div>

        <?php if (empty($backupFiles)): ?>
            <div class="empty">
                <p>📭 Бэкапы пока не созданы</p>
                <p style="margin-top: 10px; font-size: 0.9em;">Запустите скрипт: <code>/var/www/scripts/backup-all.sh</code></p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Файл</th>
                        <th>Размер</th>
                        <th>Дата создания</th>
                        <th>Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backupFiles as $file): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($file['name']); ?>
                                <?php if (strpos($file['name'], 'latest') !== false): ?>
                                    <span class="latest-badge">LATEST</span>
                                <?php endif; ?>
                            </td>
                            <td class="file-size">
                                <?php
                                $size = $file['size'];
                                $units = ['B', 'KB', 'MB', 'GB'];
                                $unitIndex = 0;
                                while ($size >= 1024 && $unitIndex < count($units) - 1) {
                                    $size /= 1024;
                                    $unitIndex++;
                                }
                                echo round($size, 2) . ' ' . $units[$unitIndex];
                                ?>
                            </td>
                            <td class="file-date">
                                <?php echo date('d.m.Y H:i', $file['date']); ?>
                            </td>
                            <td>
                                <a href="?download=<?php echo urlencode($file['name']); ?>" class="download-btn">
                                    ⬇ Скачать
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
