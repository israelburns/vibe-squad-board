<?php
/* ============================================================
   VIBE SQUAD — Course Blackboard
   Kaggle 5-Day AI Agents (Vibe Coding) study group
   One file. Flat-JSON store. No database. Shared by all bros.
   ============================================================ */

// ---- CONFIG (Ace can change these) ----
// Live passcode lives in config.php (gitignored, never on GitHub). Falls back if absent.
$CFG        = file_exists(__DIR__.'/config.php') ? (require __DIR__.'/config.php') : [];
$PASSCODE   = $CFG['passcode'] ?? 'vibesquad';   // one shared code the bros type to get in
$DATA       = __DIR__ . '/board.json';
$UPLOADS    = __DIR__ . '/uploads';
$MAXBYTES   = 15 * 1024 * 1024;       // 15 MB per file
$OK_EXT     = ['pdf','doc','docx','ppt','pptx','xls','xlsx','csv','txt','md',
               'png','jpg','jpeg','gif','webp','zip','ipynb'];   // NO php/html/js — public host
$DAYS = [
  1 => 'Day 1 — Agents & Vibe Coding',
  2 => 'Day 2 — Tools & Interoperability',
  3 => 'Day 3 — Agent Skills (memory/state)',
  4 => 'Day 4 — Security & Evaluation',
  5 => 'Day 5 — Spec-Driven Production',
];

// ---- tiny helpers ----
function load($f){ if(!file_exists($f)) return ['days'=>[],'notes'=>[],'links'=>[]];
  $d=json_decode(file_get_contents($f),true); return is_array($d)?$d:['days'=>[],'notes'=>[],'links'=>[]]; }
function save($f,$d){ file_put_contents($f,json_encode($d,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),LOCK_EX); }
function h($s){ return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); }
function clean_name($n){ return preg_replace('/[^A-Za-z0-9 _.-]/','', trim($n)); }

session_start();

// ---- auth gate ----
if (isset($_POST['passcode'])) {
  if (hash_equals($PASSCODE, $_POST['passcode'])) { $_SESSION['ok']=true; }
  else { $login_err = 'Wrong passcode.'; }
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: ./'); exit; }
$AUTHED = !empty($_SESSION['ok']);

// ---- actions (only when authed) ----
if ($AUTHED && $_SERVER['REQUEST_METHOD']==='POST') {
  $board = load($DATA);
  $a = $_POST['action'] ?? '';

  if ($a==='note' && trim($_POST['text']??'')!=='') {
    $board['notes'][] = ['name'=>clean_name($_POST['name']??'bro') ?: 'bro',
                         'text'=>mb_substr(trim($_POST['text']),0,600),
                         'ts'=>date('M j, g:ia')];
  }
  if ($a==='link' && trim($_POST['url']??'')!=='') {
    $u = trim($_POST['url']);
    if (preg_match('#^https?://#i',$u)) {
      $board['links'][] = ['title'=>mb_substr(trim($_POST['title']??$u),0,120) ?: $u,
                           'url'=>$u, 'ts'=>date('M j')];
    }
  }
  if ($a==='day' && isset($_POST['d'])) {
    $d=(int)$_POST['d']; $board['days'][$d] = empty($board['days'][$d]);
  }
  if ($a==='delnote' && isset($_POST['i'])) {
    $i=(int)$_POST['i']; if(isset($board['notes'][$i])){ array_splice($board['notes'],$i,1); }
  }
  if ($a==='editnote' && isset($_POST['i']) && trim($_POST['text']??'')!=='') {
    $i=(int)$_POST['i']; if(isset($board['notes'][$i])){
      $board['notes'][$i]['text'] = mb_substr(trim($_POST['text']),0,600);
    }
  }
  if ($a==='upload' && !empty($_FILES['file']['name'])) {
    $f=$_FILES['file'];
    $ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
    if ($f['error']===0 && $f['size']<=$MAXBYTES && in_array($ext,$OK_EXT,true)) {
      if(!is_dir($UPLOADS)) @mkdir($UPLOADS,0775,true);
      $safe = clean_name(pathinfo($f['name'],PATHINFO_FILENAME));
      $safe = ($safe?:'file').'.'.$ext;
      $dest = $UPLOADS.'/'.$safe;
      $n=1; while(file_exists($dest)){ $dest=$UPLOADS.'/'.pathinfo($safe,PATHINFO_FILENAME).'_'.$n.'.'.$ext; $n++; }
      @move_uploaded_file($f['tmp_name'],$dest);
    } else { $up_err='File rejected (type not allowed or over 15MB).'; }
  }
  save($DATA,$board);
  header('Location: ./'.(isset($up_err)?'?e=1':'')); exit;   // PRG: no double-post on refresh
}

$board = load($DATA);
$files = is_dir($UPLOADS) ? array_values(array_filter(scandir($UPLOADS),
          function($x){ return $x[0]!=='.' && strtolower($x)!=='index.php' && strtolower($x)!=='.htaccess'; })) : [];
$done = count(array_filter($board['days']??[]));
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Vibe Squad — Course Blackboard</title>
<style>
  :root{ --chalk:#f4f1e8; --green:#2e4a3b; --board:#27392e; --line:#3d5747; --gold:#e8c87a; }
  *{box-sizing:border-box} html,body{margin:0}
  body{font-family:'Comic Sans MS','Segoe Print',cursive,system-ui;background:#1a241d;color:var(--chalk);
       background-image:radial-gradient(circle at 50% 0,#314a3c,#1a241d 70%);min-height:100vh;padding:18px}
  .wrap{max-width:1000px;margin:0 auto}
  h1{font-size:30px;margin:.2em 0;text-shadow:1px 1px 0 #0006;letter-spacing:1px}
  h1 small{display:block;font-size:13px;color:#b9c9bd;font-weight:normal}
  .board{background:var(--board);border:10px solid #6b4f2e;border-radius:10px;
         box-shadow:inset 0 0 60px #0007, 0 8px 24px #0008;padding:20px;margin-top:14px}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}
  @media(max-width:760px){.grid{grid-template-columns:1fr}}
  .card{background:#22332a;border:1px solid var(--line);border-radius:8px;padding:14px}
  h2{font-size:18px;margin:.1em 0 .6em;color:var(--gold);border-bottom:1px dashed var(--line);padding-bottom:5px}
  input,textarea,button{font-family:inherit;font-size:14px;border-radius:6px;border:1px solid var(--line);
        background:#1b2820;color:var(--chalk);padding:8px}
  input,textarea{width:100%;margin:4px 0}
  button{background:var(--green);cursor:pointer;border-color:#4d7a5f}
  button:hover{background:#3a5e49}
  .day{display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px dotted var(--line)}
  .day.done{opacity:.55;text-decoration:line-through}
  .day button{padding:3px 9px;font-size:13px}
  .note{background:#f7f3c8;color:#222;padding:10px;border-radius:4px;margin:8px 0;
        box-shadow:2px 3px 6px #0005;transform:rotate(-.6deg);font-size:14px;position:relative;
        word-break:break-word;overflow-wrap:break-word;overflow:hidden;max-width:100%}
  .note:nth-child(even){transform:rotate(.7deg);background:#cfeac0}
  .note b{display:block;font-size:12px;color:#444;margin-bottom:3px}
  .note .x{position:absolute;top:4px;right:6px;background:none;border:none;color:#933;font-size:14px;padding:0;cursor:pointer}
  .lnk{padding:5px 0;border-bottom:1px dotted var(--line);font-size:14px;word-break:break-all}
  .lnk a{color:#9fd6ff}
  .file{padding:5px 0;border-bottom:1px dotted var(--line);font-size:14px}
  .file a{color:#9fd6ff}
  .bar{height:10px;background:#1b2820;border-radius:6px;overflow:hidden;margin:6px 0}
  .bar i{display:block;height:100%;background:linear-gradient(90deg,#5fa37a,var(--gold))}
  .err{color:#ffb4b4;font-size:13px}
  .login{max-width:380px;margin:12vh auto;text-align:center}
  .foot{text-align:center;color:#7e8f82;font-size:12px;margin-top:16px}
  a.logout{color:#9fb;float:right;font-size:12px}
</style></head><body><div class="wrap">

<?php if (!$AUTHED): ?>
  <div class="board login">
    <h1>📚 Vibe Squad<small>Course Blackboard — bros only</small></h1>
    <?php if(!empty($login_err)) echo '<p class="err">'.h($login_err).'</p>'; ?>
    <form method="post">
      <input name="passcode" type="password" placeholder="squad passcode" autofocus>
      <button>Enter the board</button>
    </form>
  </div>
<?php else: ?>
  <a class="logout" href="?logout=1">log out</a>
  <h1>📚 Vibe Squad — Course Blackboard
    <small>Kaggle 5-Day AI Agents · Vibe Coding w/ Google · capstone due Mon Jul 6</small></h1>

  <div class="board">
    <!-- TRACKER -->
    <div class="card" style="margin-bottom:18px">
      <h2>🗓️ 5-Day Tracker — <?=$done?>/5 done</h2>
      <div class="bar"><i style="width:<?=($done/5*100)?>%"></i></div>
      <?php foreach($DAYS as $n=>$label): $d=!empty($board['days'][$n]); ?>
        <form method="post" class="day <?=$d?'done':''?>">
          <input type="hidden" name="action" value="day"><input type="hidden" name="d" value="<?=$n?>">
          <button title="toggle"><?=$d?'✅':'⬜'?></button> <span><?=h($label)?></span>
        </form>
      <?php endforeach; ?>
    </div>

    <div class="grid">
      <!-- NOTES -->
      <div class="card">
        <h2>📝 Sticky Wall</h2>
        <form method="post">
          <input type="hidden" name="action" value="note">
          <input name="name" placeholder="your name" maxlength="30">
          <textarea name="text" rows="6" placeholder="a thought, a blocker, a win..." maxlength="600" oninput="document.getElementById('note-count').textContent=this.value.length"></textarea>
          <div style="text-align:right;font-size:12px;color:var(--line);margin:2px 0 6px"><span id="note-count">0</span>/600</div>
          <button>Pin it</button>
        </form>
        <?php foreach(array_reverse($board['notes']??[],true) as $i=>$nt):
          $editing = isset($_GET['edit']) && (int)$_GET['edit'] === $i; ?>
          <div class="note" style="<?=$editing?'transform:none;padding:12px':''?>">
            <?php if($editing): ?>
              <form method="post">
                <input type="hidden" name="action" value="editnote">
                <input type="hidden" name="i" value="<?=$i?>">
                <b><?=h($nt['name'])?> · <?=h($nt['ts'])?></b>
                <textarea name="text" rows="5" maxlength="600" style="margin:6px 0" oninput="this.nextElementSibling.textContent=this.value.length"><?=h($nt['text'])?></textarea>
                <div style="text-align:right;font-size:11px;color:#666;margin-bottom:4px"><span><?=mb_strlen($nt['text'])?></span>/600</div>
                <button style="font-size:13px">💾 Save</button>
                <a href="./" style="font-size:13px;margin-left:6px;color:#666">cancel</a>
              </form>
            <?php else: ?>
              <form method="post" style="display:inline">
                <input type="hidden" name="action" value="delnote"><input type="hidden" name="i" value="<?=$i?>">
                <button class="x" title="remove">✕</button>
              </form>
              <a href="?edit=<?=$i?>" style="position:absolute;top:4px;right:24px;background:none;border:none;color:#666;font-size:13px;text-decoration:none;cursor:pointer" title="edit">✏️</a>
              <b><?=h($nt['name'])?> · <?=h($nt['ts'])?></b><span style="overflow-wrap:break-word"><?=nl2br(h($nt['text']))?></span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- LINKS + FILES -->
      <div class="card">
        <h2>🔗 Resources</h2>
        <form method="post">
          <input type="hidden" name="action" value="link">
          <input name="title" placeholder="title (e.g. Day 1 codelab)" maxlength="120">
          <input name="url" placeholder="https://..." >
          <button>Add link</button>
        </form>
        <?php foreach(array_reverse($board['links']??[]) as $lk): ?>
          <div class="lnk">🔗 <a href="<?=h($lk['url'])?>" target="_blank" rel="noopener"><?=h($lk['title'])?></a></div>
        <?php endforeach; ?>

        <h2 style="margin-top:16px">📎 File Drop</h2>
        <?php if(isset($_GET['e'])) echo '<p class="err">File rejected (bad type or &gt;15MB).</p>'; ?>
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="upload">
          <input type="file" name="file">
          <button>Upload</button>
        </form>
        <?php if(!$files): ?><div class="file" style="opacity:.6">no files yet</div><?php endif; ?>
        <?php foreach($files as $fn): ?>
          <div class="file">📄 <a href="uploads/<?=rawurlencode($fn)?>" target="_blank"><?=h($fn)?></a></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="foot">Vibe Squad blackboard · built for the bros · keep momentum, ship the capstone 🚀</div>
<?php endif; ?>

</div></body></html>
