<?php
/**
 * ACP Sales Guide — Web Installer (Shared Hosting / cPanel Edition)
 * Works WITHOUT shell_exec / exec — uses Laravel bootstrap directly.
 *
 * 1. Upload this file to: public_html/sales/public/web-installer.php
 * 2. Visit: https://yourdomain.com/web-installer.php
 * 3. DELETE this file after install!
 */

define('GITHUB_REPO',   'mukeshh02/akash-sales-pipeline');
define('MODULE_NAME',   'ACP_Sales_Guide');
define('SECRET_KEY',    'akash2024install');
define('MIN_PHP',       '8.1.0');

function findLaravelRoot(): ?string {
    foreach ([__DIR__, dirname(__DIR__), dirname(__DIR__, 2)] as $path) {
        $r = realpath($path);
        if ($r && file_exists("$r/artisan") && file_exists("$r/vendor/autoload.php")) return $r;
    }
    return null;
}

function copyDirR(string $src, string $dst): void {
    @mkdir($dst, 0755, true);
    foreach (scandir($src) as $f) {
        if ($f === '.' || $f === '..') continue;
        is_dir("$src/$f") ? copyDirR("$src/$f", "$dst/$f") : copy("$src/$f", "$dst/$f");
    }
}

function deleteDirR(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = "$dir/$f";
        is_dir($p) ? deleteDirR($p) : unlink($p);
    }
    rmdir($dir);
}

$laravelRoot = findLaravelRoot();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (($_POST['key'] ?? '') !== SECRET_KEY) {
        echo json_encode(['ok' => false, 'msg' => 'Invalid security key']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    // ── CHECK ──────────────────────────────────────────────────────────────
    if ($action === 'check') {
        $issues = [];
        if (version_compare(PHP_VERSION, MIN_PHP, '<'))
            $issues[] = 'PHP ' . MIN_PHP . '+ required (you have ' . PHP_VERSION . ')';
        if (!extension_loaded('zip'))   $issues[] = "PHP extension 'zip' missing";
        if (!extension_loaded('curl'))  $issues[] = "PHP extension 'curl' missing";
        if (!$laravelRoot)              $issues[] = 'Laravel root not found — put this file in public/ folder';

        $release  = null;
        $ch = curl_init("https://api.github.com/repos/" . GITHUB_REPO . "/releases/latest");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT      => 'ACPInstaller/2.0',
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/vnd.github+json'],
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && $resp) {
            $release = json_decode($resp, true);
        } else {
            $issues[] = "Cannot reach GitHub (HTTP $code)";
        }

        echo json_encode([
            'ok'           => empty($issues),
            'issues'       => $issues,
            'php'          => PHP_VERSION,
            'laravel_root' => $laravelRoot,
            'version'      => $release['tag_name']    ?? 'Unknown',
            'changelog'    => $release['body']         ?? '',
            'zip_url'      => $release['zipball_url']  ?? '',
        ]);
        exit;
    }

    // ── INSTALL ────────────────────────────────────────────────────────────
    if ($action === 'install') {
        $steps  = [];
        $zipUrl = $_POST['zip_url'] ?? '';

        try {
            // 1. Download
            $steps[] = '⬇️ Downloading from GitHub...';
            $tmpZip  = sys_get_temp_dir() . '/acp_' . time() . '.zip';
            $ch = curl_init($zipUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT      => 'ACPInstaller/2.0',
                CURLOPT_TIMEOUT        => 120,
            ]);
            $data = curl_exec($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (!$data || $http !== 200) throw new Exception("Download failed HTTP $http");
            file_put_contents($tmpZip, $data);
            $steps[] = '✅ Downloaded (' . round(strlen($data)/1024) . ' KB)';

            // 2. Extract
            $steps[] = '📦 Extracting...';
            $tmpDir  = sys_get_temp_dir() . '/acp_ext_' . time();
            @mkdir($tmpDir, 0755, true);
            $zip = new ZipArchive();
            if ($zip->open($tmpZip) !== true) throw new Exception('Cannot open ZIP');
            $zip->extractTo($tmpDir);
            $zip->close();
            unlink($tmpZip);

            // Find module root inside ZIP
            $moduleSrc = null;
            foreach (scandir($tmpDir) as $e) {
                if ($e[0] === '.') continue;
                $sub = "$tmpDir/$e";
                if (is_dir($sub) && file_exists("$sub/module.json")) {
                    $moduleSrc = $sub; break;
                }
            }
            if (!$moduleSrc) throw new Exception('module.json not found inside ZIP');
            $steps[] = '✅ Extracted';

            // 3. Copy module files
            $steps[]    = '📂 Installing module files...';
            $moduleDest = $laravelRoot . '/modules/' . MODULE_NAME;
            if (is_dir($moduleDest)) {
                rename($moduleDest, $moduleDest . '_bak_' . date('YmdHis'));
            }
            copyDirR($moduleSrc, $moduleDest);
            deleteDirR($tmpDir);
            $steps[] = '✅ Files copied to modules/' . MODULE_NAME;

            // 4. Enable in modules_statuses.json
            $steps[]    = '⚙️ Enabling module...';
            $statusFile = $laravelRoot . '/modules_statuses.json';
            $statuses   = file_exists($statusFile)
                ? (json_decode(file_get_contents($statusFile), true) ?? [])
                : [];
            $statuses[MODULE_NAME] = true;
            file_put_contents($statusFile, json_encode($statuses, JSON_PRETTY_PRINT));
            $steps[] = '✅ Module enabled';

            // 5. Clear bootstrap/cache files
            $steps[]  = '🧹 Clearing cache files...';
            $cacheDir = $laravelRoot . '/bootstrap/cache';
            foreach (['config.php','packages.php','services.php','routes-v7.php','events.php','module_autoload.php','modules.php'] as $cf) {
                $fp = "$cacheDir/$cf";
                if (file_exists($fp)) unlink($fp);
            }
            $steps[] = '✅ Bootstrap cache cleared';

            // 6. Run migrations via Laravel bootstrap (NO shell_exec needed!)
            $steps[] = '🗄️ Running migrations...';
            try {
                require_once $laravelRoot . '/vendor/autoload.php';
                $app    = require_once $laravelRoot . '/bootstrap/app.php';
                $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
                $kernel->bootstrap();

                $exit = \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                $steps[] = $exit === 0 ? '✅ Migrations complete' : '⚠️ Migrations finished (code ' . $exit . ')';

                \Illuminate\Support\Facades\Artisan::call('core:clear-cache');
                \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                $steps[] = '✅ CRM cache cleared';

            } catch (\Throwable $me) {
                $steps[] = '⚠️ Auto-migration failed: ' . $me->getMessage();
                $steps[] = '👉 Run manually via SSH: php artisan migrate --force';
            }

            echo json_encode(['ok' => true, 'steps' => $steps]);

        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage(), 'steps' => $steps]);
        }
        exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'Unknown action']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ACP Sales Guide — Installer</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#1e293b;border:1px solid #334155;border-radius:12px;width:100%;max-width:560px;padding:32px}
h1{font-size:20px;color:#38bdf8;text-align:center;margin-bottom:4px}
.sub{text-align:center;color:#64748b;font-size:13px;margin-bottom:24px}
label{display:block;font-size:13px;color:#94a3b8;margin-bottom:6px}
input[type=password]{width:100%;background:#0f172a;border:1px solid #334155;color:#e2e8f0;border-radius:8px;padding:10px 14px;font-size:14px}
.btn{width:100%;padding:12px;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;margin-top:14px;background:#0ea5e9;color:#fff}
.btn:hover:not(:disabled){background:#0284c7}
.btn:disabled{opacity:.5;cursor:not-allowed}
.ci{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #0f172a;font-size:14px}
.dot{width:8px;height:8px;border-radius:50%}
.ok{background:#22c55e}.err{background:#ef4444}
.log{background:#0f172a;border-radius:8px;padding:16px;margin-top:16px;font-family:monospace;font-size:13px;line-height:1.9;max-height:320px;overflow-y:auto;white-space:pre-wrap}
.sbox{background:#052e16;border:1px solid #16a34a;border-radius:8px;padding:16px;text-align:center;color:#4ade80;margin-top:14px}
.ebox{background:#450a0a;border:1px solid #dc2626;border-radius:8px;padding:16px;color:#fca5a5;margin-top:14px}
.vbox{background:#0c1a2e;border:1px solid #1d4ed8;border-radius:8px;padding:12px 16px;margin:14px 0;font-size:14px}
.wbox{background:#431407;border:1px solid #ea580c;border-radius:8px;padding:10px 14px;font-size:13px;color:#fdba74;margin-top:12px}
.spin{display:inline-block;width:14px;height:14px;border:2px solid #334155;border-top-color:#38bdf8;border-radius:50%;animation:s .8s linear infinite;vertical-align:middle;margin-right:6px}
@keyframes s{to{transform:rotate(360deg)}}
</style>
</head>
<body>
<div class="card">
  <h1>📦 ACP Sales Guide</h1>
  <p class="sub">Module Installer · Concord CRM · v2.0</p>

  <div id="s1">
    <label>Security Key</label>
    <input type="password" id="key" placeholder="Enter installer key…">
    <button class="btn" onclick="doCheck()">Check Requirements →</button>
  </div>

  <div id="s2" style="display:none">
    <div id="checks"></div>
    <div id="vbox" class="vbox" style="display:none"></div>
    <button id="btn-i" class="btn" onclick="doInstall()" style="display:none">⚡ Install Now</button>
  </div>

  <div id="s3" style="display:none">
    <div class="log" id="log">Installing… please wait (30–60s)\n</div>
    <div id="result"></div>
  </div>

  <div class="wbox" id="sec" style="display:none">
    ⚠️ <strong>Delete this file after install!</strong><br>
    <code><?= htmlspecialchars($_SERVER['PHP_SELF'] ?? '/public/web-installer.php') ?></code>
  </div>
</div>
<script>
const key = () => document.getElementById('key').value;
let zipUrl = '';

async function post(data) {
  const fd = new FormData();
  fd.append('key', key());
  for (const [k,v] of Object.entries(data)) fd.append(k, v);
  const r = await fetch(location.href, {method:'POST', body:fd});
  if (!r.ok) throw new Error('Server returned ' + r.status + '. Check cPanel Error Logs.');
  return r.json();
}

async function doCheck() {
  const btn = document.querySelector('#s1 .btn');
  btn.innerHTML = '<span class="spin"></span> Checking…'; btn.disabled = true;
  try {
    const d = await post({action:'check'});
    document.getElementById('s1').style.display = 'none';
    document.getElementById('s2').style.display = 'block';
    const rows = [
      {l:'PHP ' + d.php,                                         ok:!d.issues?.some(i=>i.includes('PHP 8'))},
      {l:'Extensions (zip, curl)',                               ok:!d.issues?.some(i=>i.includes('extension'))},
      {l:'Laravel root: ' + (d.laravel_root||'NOT FOUND'),       ok:!!d.laravel_root},
      {l:'GitHub: ' + (d.version||'Failed'),                    ok:!!d.version && d.version!=='Unknown'},
    ];
    document.getElementById('checks').innerHTML = rows.map(r=>
      `<div class="ci"><div class="dot ${r.ok?'ok':'err'}"></div><span>${r.l}</span></div>`
    ).join('');
    if (d.ok) {
      zipUrl = d.zip_url;
      const v = document.getElementById('vbox');
      v.style.display = 'block';
      v.innerHTML = `🚀 Ready to install <strong>${d.version}</strong>`;
      document.getElementById('btn-i').style.display = 'block';
    }
  } catch(e) {
    document.getElementById('s1').style.display = 'none';
    document.getElementById('s2').style.display = 'block';
    document.getElementById('checks').innerHTML = `<div class="ebox">❌ ${e.message}</div>`;
  }
}

async function doInstall() {
  document.getElementById('s2').style.display = 'none';
  document.getElementById('s3').style.display = 'block';
  try {
    const d = await post({action:'install', zip_url:zipUrl});
    document.getElementById('log').textContent = (d.steps||[]).join('\n');
    document.getElementById('result').innerHTML = d.ok
      ? `<div class="sbox">✅ <strong>Done!</strong><br><br>Delete this file → open CRM 🎉</div>`
      : `<div class="ebox">❌ ${d.msg||'Error'}</div>`;
    if (d.ok) document.getElementById('sec').style.display = 'block';
  } catch(e) {
    document.getElementById('log').textContent += '\n❌ ' + e.message;
  }
}
</script>
</body>
</html>
