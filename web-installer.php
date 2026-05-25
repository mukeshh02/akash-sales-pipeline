<?php
/**
 * AkashSalesPipeline â€” Web Installer
 *
 * FOR cPanel / Shared Hosting users (no SSH needed)
 *
 * INSTALL STEPS:
 *   1. Download this file
 *   2. Upload to your website's PUBLIC folder (public_html/public/ OR public_html/)
 *   3. Visit: https://yourdomain.com/web-installer.php
 *   4. Click Install
 *   5. DELETE this file after install (security!)
 */

define('GITHUB_REPO',   'mukeshh02/akash-sales-pipeline');
define('MODULE_NAME',   'ACP_Sales_Guide');
define('SECRET_KEY',    'akash2024install');  // Simple protection â€” change this!
define('MIN_PHP',       '8.1.0');

// â”€â”€â”€ Find Laravel root â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Works whether this file is in public/ OR public_html/ (cPanel root)
function findLaravelRoot(): ?string {
    $checks = [
        dirname(__FILE__),           // same folder
        dirname(__FILE__) . '/..',   // one level up (public/ â†’ root)
        dirname(__FILE__) . '/../..', // two levels up
    ];
    foreach ($checks as $path) {
        $real = realpath($path);
        if ($real && file_exists("$real/artisan") && file_exists("$real/composer.json")) {
            return $real;
        }
    }
    return null;
}

$laravelRoot = findLaravelRoot();

// â”€â”€â”€ Handle AJAX actions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // Security check
    if (($_POST['key'] ?? '') !== SECRET_KEY) {
        echo json_encode(['ok' => false, 'msg' => 'Invalid security key']);
        exit;
    }

    $action = $_POST['action'];

    if ($action === 'check') {
        // Check requirements
        $issues = [];
        if (version_compare(PHP_VERSION, MIN_PHP, '<'))
            $issues[] = "PHP " . MIN_PHP . "+ required (you have " . PHP_VERSION . ")";
        if (!extension_loaded('zip'))   $issues[] = "PHP extension 'zip' missing";
        if (!extension_loaded('curl'))  $issues[] = "PHP extension 'curl' missing";
        if (!$laravelRoot)              $issues[] = "Laravel root not found. Is this file in public/ folder?";

        // Fetch latest version from GitHub
        $release = @json_decode(file_get_contents(
            "https://api.github.com/repos/" . GITHUB_REPO . "/releases/latest",
            false,
            stream_context_create(['http' => [
                'method'  => 'GET',
                'header'  => "User-Agent: AkashWebInstaller/1.0\r\n",
                'timeout' => 10,
            ]])
        ), true);

        echo json_encode([
            'ok'          => empty($issues),
            'issues'      => $issues,
            'php'         => PHP_VERSION,
            'laravel_root'=> $laravelRoot,
            'version'     => $release['tag_name'] ?? 'Unknown',
            'changelog'   => $release['body'] ?? '',
            'zip_url'     => $release['assets'][0]['browser_download_url'] ?? $release['zipball_url'] ?? '',
        ]);
        exit;
    }

    if ($action === 'install') {
        $zipUrl = $_POST['zip_url'] ?? '';
        $steps  = [];

        try {
            // Step 1: Download ZIP
            $steps[] = "â¬‡ï¸ Downloading...";
            $tmpZip = sys_get_temp_dir() . '/akash_install.zip';
            $ch = curl_init($zipUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT      => 'AkashWebInstaller/1.0',
                CURLOPT_TIMEOUT        => 120,
            ]);
            $data = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code !== 200 || !$data) throw new Exception("Download failed (HTTP $code)");
            file_put_contents($tmpZip, $data);
            $steps[] = "âœ… Downloaded (" . round(strlen($data)/1024) . " KB)";

            // Step 2: Extract ZIP
            $steps[] = "ðŸ“¦ Extracting...";
            $tmpDir  = sys_get_temp_dir() . '/akash_extract';
            if (is_dir($tmpDir)) array_map('unlink', glob("$tmpDir/*.*"));
            @mkdir($tmpDir, 0755, true);

            $zip = new ZipArchive();
            if ($zip->open($tmpZip) !== true) throw new Exception("Cannot open ZIP");
            $zip->extractTo($tmpDir);
            $zip->close();
            unlink($tmpZip);

            // Find module folder in ZIP
            $moduleSrc = $tmpDir . '/AkashSalesPipeline';
            if (!is_dir($moduleSrc)) {
                // GitHub zipball has a subfolder
                foreach (scandir($tmpDir) as $e) {
                    $sub = "$tmpDir/$e";
                    if ($e[0] !== '.' && is_dir($sub) && is_dir("$sub/AkashSalesPipeline")) {
                        $moduleSrc = "$sub/AkashSalesPipeline";
                        break;
                    }
                    // Direct subfolder that IS the module
                    if ($e[0] !== '.' && is_dir($sub) && file_exists("$sub/module.json")) {
                        $moduleSrc = $sub;
                        break;
                    }
                }
            }
            if (!is_dir($moduleSrc)) throw new Exception("Module folder not found in ZIP");
            $steps[] = "âœ… Extracted";

            // Step 3: Copy module to modules/
            $steps[] = "ðŸ“‚ Copying module files...";
            $moduleDest = $laravelRoot . '/modules/' . MODULE_NAME;

            function copyDirWeb($src, $dst) {
                @mkdir($dst, 0755, true);
                foreach (scandir($src) as $f) {
                    if ($f === '.' || $f === '..') continue;
                    is_dir("$src/$f") ? copyDirWeb("$src/$f", "$dst/$f") : copy("$src/$f", "$dst/$f");
                }
            }

            // Backup old version if exists
            if (is_dir($moduleDest)) {
                rename($moduleDest, $moduleDest . '_backup_' . date('YmdHis'));
            }
            copyDirWeb($moduleSrc, $moduleDest);
            $steps[] = "âœ… Module files copied to modules/" . MODULE_NAME;

            // Step 4: Copy pre-built assets (if packaged in ZIP)
            $assetSrc = $tmpDir . '/public/build';
            // Try in subfolder too
            if (!is_dir($assetSrc)) {
                foreach (scandir($tmpDir) as $e) {
                    $sub = "$tmpDir/$e/public/build";
                    if ($e[0] !== '.' && is_dir($sub)) { $assetSrc = $sub; break; }
                }
            }
            if (is_dir($assetSrc)) {
                $steps[] = "ðŸŽ¨ Copying pre-built assets...";
                copyDirWeb($assetSrc, $laravelRoot . '/public/build');
                $steps[] = "âœ… Assets copied (npm run build NOT needed!)";
            } else {
                $steps[] = "âš ï¸ Pre-built assets not in ZIP. You may need to run: npm run build";
            }

            // Step 5: Run artisan commands via PHP CLI
            $steps[] = "ðŸ—„ï¸ Running migrations...";
            $phpBin  = PHP_BINARY;
            $artisan = $laravelRoot . '/artisan';
            $cmds    = [
                "migrate --force"            => "Migrations",
                "module:enable AkashSalesPipeline" => "Module enabled",
                "core:clear-cache"           => "Cache cleared",
                "optimize:clear"             => "Optimized",
            ];
            foreach ($cmds as $cmd => $label) {
                $out = shell_exec("\"$phpBin\" \"$artisan\" $cmd 2>&1");
                $steps[] = "âœ… $label";
            }

            // Cleanup
            array_map('unlink', glob("$tmpDir/*.*"));

            echo json_encode(['ok' => true, 'steps' => $steps]);

        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage(), 'steps' => $steps]);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AkashSalesPipeline Installer</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
         background: #0f172a; color: #e2e8f0; min-height: 100vh;
         display: flex; align-items: center; justify-content: center; padding: 20px; }
  .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px;
          width: 100%; max-width: 560px; padding: 32px; }
  .logo { text-align: center; margin-bottom: 24px; }
  .logo h1 { font-size: 22px; color: #38bdf8; }
  .logo p  { color: #64748b; font-size: 13px; margin-top: 6px; }
  .badge { display: inline-block; background: #0ea5e9; color: white;
           font-size: 11px; padding: 2px 8px; border-radius: 20px; }
  label { display: block; font-size: 13px; color: #94a3b8; margin-bottom: 6px; }
  input[type=password] { width: 100%; background: #0f172a; border: 1px solid #334155;
      color: #e2e8f0; border-radius: 8px; padding: 10px 14px; font-size: 14px; }
  .btn { width: 100%; padding: 12px; border: none; border-radius: 8px;
         font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 16px;
         background: #0ea5e9; color: white; transition: background .2s; }
  .btn:hover:not(:disabled) { background: #0284c7; }
  .btn:disabled { opacity: .5; cursor: not-allowed; }
  .checks { margin: 20px 0; }
  .check-item { display: flex; align-items: center; gap: 10px; padding: 8px 0;
                border-bottom: 1px solid #1e293b; font-size: 14px; }
  .dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
  .dot.ok  { background: #22c55e; }
  .dot.err { background: #ef4444; }
  .dot.warn { background: #f59e0b; }
  .log { background: #0f172a; border-radius: 8px; padding: 16px; margin-top: 16px;
         font-family: monospace; font-size: 13px; line-height: 1.8; max-height: 280px;
         overflow-y: auto; }
  .success-box { background: #052e16; border: 1px solid #16a34a; border-radius: 8px;
                 padding: 16px; text-align: center; color: #4ade80; margin-top: 20px; }
  .error-box   { background: #450a0a; border: 1px solid #dc2626; border-radius: 8px;
                 padding: 16px; color: #fca5a5; margin-top: 20px; }
  .version-box { background: #0c1a2e; border: 1px solid #1d4ed8; border-radius: 8px;
                 padding: 12px 16px; margin: 16px 0; font-size: 14px; }
  .spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid #334155;
             border-top-color: #38bdf8; border-radius: 50%; animation: spin .8s linear infinite;
             vertical-align: middle; margin-right: 8px; }
  @keyframes spin { to { transform: rotate(360deg); } }
  .warning-box { background: #431407; border: 1px solid #ea580c; border-radius: 8px;
                 padding: 12px 16px; font-size: 13px; color: #fdba74; margin-top: 12px; }
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <h1>ðŸ“¦ AkashSalesPipeline</h1>
    <p>Module Installer for Concord CRM</p>
  </div>

  <!-- Step 1: Security Key -->
  <div id="step-key">
    <label>Security Key</label>
    <input type="password" id="key" placeholder="Enter installer key..." />
    <button class="btn" onclick="checkRequirements()">Check Requirements â†’</button>
  </div>

  <!-- Step 2: Requirements -->
  <div id="step-check" style="display:none">
    <div id="check-list" class="checks"></div>
    <div id="version-info" class="version-box" style="display:none"></div>
    <button id="btn-install" class="btn" onclick="startInstall()" style="display:none">
      Install Now âœ“
    </button>
  </div>

  <!-- Step 3: Install Log -->
  <div id="step-install" style="display:none">
    <div class="log" id="install-log"></div>
    <div id="result-box"></div>
  </div>

  <div class="warning-box" id="security-note" style="display:none">
    âš ï¸ <strong>Security:</strong> Delete this file after install!<br>
    Path: <code><?= htmlspecialchars($_SERVER['PHP_SELF'] ?? 'web-installer.php') ?></code>
  </div>
</div>

<script>
const SECRET = () => document.getElementById('key').value;
let zipUrl = '';

async function post(data) {
  const fd = new FormData();
  fd.append('key', SECRET());
  for (const [k,v] of Object.entries(data)) fd.append(k, v);
  const r = await fetch('', { method: 'POST', body: fd });
  return r.json();
}

async function checkRequirements() {
  const btn = document.querySelector('#step-key .btn');
  btn.innerHTML = '<span class="spinner"></span> Checking...';
  btn.disabled = true;

  const data = await post({ action: 'check' });

  document.getElementById('step-key').style.display   = 'none';
  document.getElementById('step-check').style.display = 'block';

  const list = document.getElementById('check-list');
  const checks = [
    { label: 'PHP Version: ' + data.php,                ok: data.issues?.every(i => !i.includes('PHP 8')) },
    { label: 'PHP Extensions (zip, curl, json)',        ok: !data.issues?.some(i => i.includes('extension')) },
    { label: 'Laravel Root: ' + (data.laravel_root || 'Not found'), ok: !!data.laravel_root },
    { label: 'GitHub Connection',                       ok: !!data.version && data.version !== 'Unknown' },
  ];

  list.innerHTML = checks.map(c => `
    <div class="check-item">
      <div class="dot ${c.ok ? 'ok' : 'err'}"></div>
      <span>${c.label}</span>
    </div>
  `).join('');

  if (data.ok && data.version) {
    zipUrl = data.zip_url;
    document.getElementById('version-info').style.display = 'block';
    document.getElementById('version-info').innerHTML =
      `ðŸš€ Ready to install <strong>${data.version}</strong>` +
      (data.changelog ? `<br><small style="color:#64748b;white-space:pre-wrap">${data.changelog}</small>` : '');
    document.getElementById('btn-install').style.display = 'block';
  } else if (data.issues?.length) {
    list.innerHTML += `<div class="error-box">âŒ Fix issues above before installing.</div>`;
  }
}

async function startInstall() {
  document.getElementById('step-check').style.display   = 'none';
  document.getElementById('step-install').style.display = 'block';

  const log  = document.getElementById('install-log');
  const result = document.getElementById('result-box');
  log.innerHTML = '<span class="spinner"></span> Installing...\n';

  const data = await post({ action: 'install', zip_url: zipUrl });

  if (data.steps) {
    log.innerHTML = data.steps.map(s => s + '\n').join('');
  }

  if (data.ok) {
    result.innerHTML = `
      <div class="success-box">
        âœ… <strong>Installation Complete!</strong><br><br>
        Next steps:<br>
        1. <strong>Delete this file</strong> (security!)<br>
        2. Open your CRM â€” AkashSalesPipeline is ready
      </div>`;
    document.getElementById('security-note').style.display = 'block';
  } else {
    result.innerHTML = `<div class="error-box">âŒ <strong>Error:</strong> ${data.msg || 'Unknown error'}</div>`;
  }
}
</script>
</body>
</html>

