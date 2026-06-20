<?php
/* ============================================================
   VIBE SQUAD — Course HQ
   Kaggle 5-Day AI Agents (Vibe Coding) study group
   One file. Flat-JSON store. No database. Shared by all bros.
   ============================================================ */

// ---- CONFIG (Ace can change these) ----
$CFG        = file_exists(__DIR__.'/config.php') ? (require __DIR__.'/config.php') : [];
$PASSCODE   = $CFG['passcode'] ?? 'vibesquad';   // one shared code the bros type to get in
$DATA       = __DIR__ . '/board.json';
$UPLOADS    = __DIR__ . '/uploads';
$MAXBYTES   = 15 * 1024 * 1024;       // 15 MB per file
$OK_EXT     = ['pdf','doc','docx','ppt','pptx','xls','xlsx','csv','txt','md',
               'png','jpg','jpeg','gif','webp','zip','ipynb'];   // NO php/html/js — public host

$COURSE_URL = 'https://www.kaggle.com/competitions/5-day-ai-agents-intensive-vibecoding-course-with-google/overview';
$KAGGLE_YT  = 'https://www.youtube.com/@kaggle';

// Live meeting (tonight)
$MEETING = [
  'title' => 'Kaggle Study Group — 5-Day AI Training',
  'when'  => 'Mon, Jun 15 · 8:00 – 10:00 PM',
  'tz'    => 'America/New_York (ET)',
  'link'  => 'https://meet.google.com/hpx-hhau-mvv',
  'dial'  => '(US) +1 208-820-4762',
  'pin'   => '158 191 601#',
  'more'  => 'https://tel.meet/hpx-hhau-mvv?pin=6907640137511',
];

// Per-day course map. Day 1 = real materials. Days 2-5 = topic + date now,
// specific links go live as each day drops (no fabricated URLs).
$COURSE = [
 1 => ['date'=>'Mon · Jun 15','title'=>'Agents & Vibe Coding','live'=>true,
   'blurb'=>'From simple chatbots to autonomous thinkers. What an agent actually is (Model + Harness), and how vibe coding becomes agentic engineering. The differentiator: verification — tests (deterministic) + evals (LM-judged).',
   'links'=>[
     ['📄 Whitepaper — The New SDLC with Vibe Coding','uploads/The_New_SDLC_With_Vibe_Coding_Day_1.pdf'],
     ['🎧 Day 1 podcast','https://youtu.be/cbzmr7vt4XA'],
     ['👀 Sneak-peek video','https://www.youtube.com/watch?v=eG5RpppF-Xo'],
     ['💻 Codelabs — ADK single-agent + multi-agent (Kaggle)','https://www.kaggle.com/competitions/5-day-ai-agents-intensive-vibecoding-course-with-google/overview'],
   ]],
 2 => ['date'=>'Tue · Jun 16','title'=>'Tools & Interoperability — "10x Agents"','live'=>true,
   'blurb'=>'Standardize the plug-and-play AI ecosystem with open protocols — kill the technical debt of custom tool integrations. Covers MCP (Model Context Protocol, model→data/tools), A2A (Agent2Agent collaboration), A2UI (generative UI), and AP2/UCP (machine-to-machine commerce). Today\'s codelabs: add MCP servers to Antigravity + drive it from the terminal (CLI).',
   'links'=>[
     ['🎧 Unit 2 podcast','https://www.youtube.com/watch?v=GjjKXqxFTOY'],
     ['📄 Whitepaper — Agent Tools & Interoperability','https://www.kaggle.com/whitepaper-agent-tools-and-interoperability'],
     ['💻 Codelab — Get started with Antigravity CLI','https://codelabs.developers.google.com/antigravity-cli-hands-on'],
     ['💻 Codelab — Developer Knowledge MCP in Antigravity','https://codelabs.developers.google.com/developer-knowledge-mcp-antigravity'],
     ['📺 Day-1 livestream recording','https://www.youtube.com/live/7iic3Zj427M'],
     ['💬 Kaggle discussion forum (resource hub)','https://www.kaggle.com/competitions/5-day-ai-agents-intensive-vibecoding-course-with-google/discussion'],
   ]],
 3 => ['date'=>'Wed · Jun 17','title'=>'Agent Skills — Memory & Context','live'=>false,
   'blurb'=>'Short-term recall, long-term knowledge retention, and skill-building so agents don\'t lose information. Context engineering + token discipline.',
   'links'=>[]],
 4 => ['date'=>'Thu · Jun 18','title'=>'Security & Evaluation','live'=>false,
   'blurb'=>'Quality assurance for autonomous systems: evaluations, guardrails, and threat mitigation. The differentiator day — you build the harness.',
   'links'=>[]],
 5 => ['date'=>'Fri · Jun 19','title'=>'Production & Capstone','live'=>false,
   'blurb'=>'Bridge prototype to production: cloud deploy, debugging, observability. Capstones go live EOD Fri. Submission due Mon Jul 6, 11:59 PM PT.',
   'links'=>[]],
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
function is_ext($u){ return preg_match('#^https?://#i',$u); }
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Vibe Squad — Course HQ</title>
<style>
  :root{
    --bg:#070a12; --panel:rgba(255,255,255,.035); --panel2:rgba(255,255,255,.06);
    --line:rgba(255,255,255,.09); --ink:#e9edf6; --mut:#8a93a8; --dim:#5c6478;
    --a1:#7c5cff; --a2:#22d3ee; --ok:#34d399; --warn:#fbbf24;
    --grad:linear-gradient(120deg,var(--a1),var(--a2));
  }
  *{box-sizing:border-box} html,body{margin:0}
  body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,Inter,sans-serif;
    color:var(--ink);background:var(--bg);min-height:100vh;line-height:1.5;
    background-image:
      radial-gradient(60vw 50vh at 12% -8%, rgba(124,92,255,.18), transparent 60%),
      radial-gradient(50vw 50vh at 100% 0%, rgba(34,211,238,.13), transparent 55%),
      radial-gradient(40vw 40vh at 50% 120%, rgba(124,92,255,.10), transparent 60%);
    background-attachment:fixed;padding:22px}
  .wrap{max-width:1080px;margin:0 auto}
  a{color:#9fd8ff;text-decoration:none} a:hover{text-decoration:underline}
  .mono{font-family:ui-monospace,"SF Mono",Menlo,monospace;letter-spacing:.06em;
    text-transform:uppercase;font-size:11px;color:var(--mut)}
  .grad-txt{background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
  .pill{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:600;
    padding:4px 10px;border-radius:999px;border:1px solid var(--line);background:var(--panel2);color:var(--mut)}
  .pill .dot{width:7px;height:7px;border-radius:50%;background:var(--ok);box-shadow:0 0 8px var(--ok)}
  .card{background:var(--panel);border:1px solid var(--line);border-radius:18px;padding:20px;
    backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);box-shadow:0 10px 40px rgba(0,0,0,.35)}
  /* header */
  header.top{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;margin:6px 4px 20px}
  h1{font-size:30px;font-weight:800;margin:0;letter-spacing:-.02em}
  h1 .sub{display:block;font-size:13px;font-weight:500;color:var(--mut);letter-spacing:0;margin-top:4px}
  .logout{font-size:12px;color:var(--dim);border:1px solid var(--line);padding:6px 12px;border-radius:10px;background:var(--panel)}
  /* meeting banner */
  .meet{display:flex;flex-wrap:wrap;gap:18px;align-items:center;justify-content:space-between;
    border:1px solid rgba(124,92,255,.4);background:
      linear-gradient(120deg,rgba(124,92,255,.16),rgba(34,211,238,.10));margin-bottom:20px}
  .meet .info b{font-size:17px} .meet .info .mono{margin-bottom:4px;display:block}
  .meet .dial{color:var(--mut);font-size:13px;margin-top:6px}
  .btn{display:inline-flex;align-items:center;gap:8px;background:var(--grad);color:#0a0a12;
    font-weight:700;font-size:14px;padding:12px 20px;border:none;border-radius:12px;cursor:pointer;
    box-shadow:0 6px 24px rgba(124,92,255,.35);transition:transform .12s,box-shadow .12s}
  .btn:hover{transform:translateY(-2px);box-shadow:0 10px 30px rgba(124,92,255,.5);text-decoration:none}
  /* section heads */
  .shead{display:flex;align-items:center;gap:10px;margin:26px 4px 12px}
  .shead h2{font-size:15px;font-weight:700;margin:0;letter-spacing:.01em}
  .shead .ln{flex:1;height:1px;background:var(--line)}
  /* progress */
  .prog{display:flex;align-items:center;gap:12px;margin:4px 0 6px}
  .bar{flex:1;height:8px;background:rgba(255,255,255,.06);border-radius:99px;overflow:hidden}
  .bar i{display:block;height:100%;background:var(--grad);box-shadow:0 0 12px rgba(124,92,255,.5)}
  /* course map */
  .day{display:grid;grid-template-columns:auto 1fr;gap:16px;margin-bottom:12px}
  .daytog{width:42px;height:42px;border-radius:12px;border:1px solid var(--line);background:var(--panel2);
    color:var(--mut);font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center}
  .daytog.on{background:var(--grad);color:#0a0a12;border-color:transparent}
  .daybody .mono{color:var(--a2)}
  .daybody h3{margin:2px 0 6px;font-size:16px;font-weight:700}
  .daybody p{margin:0 0 10px;color:#c3cad9;font-size:13.5px}
  .tag{font-size:10px;font-weight:700;padding:2px 8px;border-radius:6px;margin-left:8px;vertical-align:middle}
  .tag.live{background:rgba(52,211,153,.16);color:var(--ok);border:1px solid rgba(52,211,153,.4)}
  .tag.soon{background:rgba(251,191,36,.14);color:var(--warn);border:1px solid rgba(251,191,36,.35)}
  .mats a{display:inline-block;font-size:12.5px;background:var(--panel2);border:1px solid var(--line);
    padding:6px 11px;border-radius:9px;margin:0 6px 6px 0;color:#dbe3f2}
  .mats a:hover{border-color:rgba(124,92,255,.6);text-decoration:none}
  .soonline{font-size:12px;color:var(--dim)}
  /* grid */
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  @media(max-width:780px){.grid{grid-template-columns:1fr}.day{grid-template-columns:1fr}}
  input,textarea{width:100%;margin:5px 0;font:inherit;font-size:14px;color:var(--ink);
    background:rgba(0,0,0,.25);border:1px solid var(--line);border-radius:10px;padding:10px}
  input:focus,textarea:focus{outline:none;border-color:rgba(124,92,255,.6)}
  .mini{display:inline-flex;align-items:center;gap:6px;background:var(--panel2);border:1px solid var(--line);
    color:var(--ink);font:inherit;font-size:13px;font-weight:600;padding:8px 14px;border-radius:10px;cursor:pointer}
  .mini:hover{border-color:rgba(124,92,255,.6)}
  /* notes */
  .note{background:rgba(255,255,255,.05);border:1px solid var(--line);border-left:3px solid var(--a1);
    border-radius:10px;padding:11px 12px;margin:9px 0;position:relative;font-size:13.5px;
    word-break:break-word;overflow-wrap:break-word;max-width:100%}
  .note b{display:block;font-size:11px;color:var(--mut);margin-bottom:4px}
  .note .x,.note .ed{position:absolute;top:8px;background:none;border:none;cursor:pointer;font-size:12px;color:var(--dim)}
  .note .x{right:10px} .note .ed{right:30px;text-decoration:none}
  .lnk,.file{padding:7px 0;border-bottom:1px solid var(--line);font-size:13.5px;word-break:break-all}
  .lnk:last-child,.file:last-child{border-bottom:none}
  .err{color:#ff9b9b;font-size:13px}
  .empty{color:var(--dim);font-size:13px}
  .ctr{text-align:right;font-size:11px;color:var(--dim)}
  /* login */
  .login{max-width:400px;margin:14vh auto;text-align:center}
  .foot{text-align:center;color:var(--dim);font-size:12px;margin-top:26px}
</style></head><body><div class="wrap">

<?php if (!$AUTHED): ?>
  <div class="card login">
    <div class="mono" style="margin-bottom:6px">Vibe Squad · Course HQ</div>
    <h1 class="grad-txt">Build agents. Ship the capstone.</h1>
    <p style="color:var(--mut);font-size:13px;margin-top:6px">Kaggle 5-Day AI Agents · Vibe Coding w/ Google</p>
    <?php if(!empty($login_err)) echo '<p class="err">'.h($login_err).'</p>'; ?>
    <form method="post" style="margin-top:14px">
      <input name="passcode" type="password" placeholder="squad passcode" autofocus>
      <button class="btn" style="width:100%;justify-content:center;margin-top:6px">Enter HQ →</button>
    </form>
  </div>
<?php else: ?>

  <header class="top">
    <div>
      <div class="mono">Vibe Squad · Course HQ</div>
      <h1 class="grad-txt">Build agents. Ship the capstone.<span class="sub">Kaggle 5-Day AI Agents · Vibe Coding w/ Google · capstone due Mon Jul 6, 11:59 PM PT</span></h1>
    </div>
    <a class="logout" href="?logout=1">log out</a>
  </header>

  <!-- TONIGHT'S MEETING -->
  <div class="card meet">
    <div class="info">
      <span class="mono"><span class="pill"><span class="dot"></span>Live tonight</span></span>
      <b><?=h($MEETING['title'])?></b><br>
      <span style="color:var(--mut);font-size:13.5px"><?=h($MEETING['when'])?> · <?=h($MEETING['tz'])?></span>
      <div class="dial">📞 <?=h($MEETING['dial'])?> · PIN <?=h($MEETING['pin'])?> · <a href="<?=h($MEETING['more'])?>" target="_blank" rel="noopener">more numbers</a></div>
    </div>
    <a class="btn" href="<?=h($MEETING['link'])?>" target="_blank" rel="noopener">▶ Join Google Meet</a>
  </div>

  <!-- COURSE NOTES (study guide) -->
  <div class="card meet" style="border-color:rgba(34,211,238,.4);background:linear-gradient(120deg,rgba(34,211,238,.12),rgba(124,92,255,.10))">
    <div class="info">
      <span class="mono">Study guide</span>
      <b>📖 Course Notes — everything they taught</b><br>
      <span style="color:var(--mut);font-size:13.5px">All 5 units + MCP + capstone rubric. Phone-readable. Save it for offline.</span>
    </div>
    <a class="btn" href="notes.html">Open notes →</a>
  </div>

  <!-- COURSE MAP -->
  <div class="shead"><h2>🗺️ Course Map</h2><div class="ln"></div>
    <span class="mono"><?=$done?>/5 done</span></div>
  <div class="prog"><div class="bar"><i style="width:<?=($done/5*100)?>%"></i></div></div>

  <div class="card">
    <?php foreach($COURSE as $n=>$c): $d=!empty($board['days'][$n]); ?>
      <div class="day">
        <form method="post" style="margin:0">
          <input type="hidden" name="action" value="day"><input type="hidden" name="d" value="<?=$n?>">
          <button class="daytog <?=$d?'on':''?>" title="mark done"><?=$d?'✓':$n?></button>
        </form>
        <div class="daybody">
          <span class="mono"><?=h($c['date'])?></span>
          <h3>Day <?=$n?> — <?=h($c['title'])?>
            <?php if(!empty($c['live'])): ?><span class="tag live">MATERIALS LIVE</span>
            <?php else: ?><span class="tag soon">DROPS <?=strtoupper(explode(' · ',$c['date'])[1])?></span><?php endif; ?>
          </h3>
          <p><?=h($c['blurb'])?></p>
          <div class="mats">
            <?php foreach($c['links'] as $L): $ext=is_ext($L[1]); ?>
              <a href="<?=h($L[1])?>" <?=$ext?'target="_blank" rel="noopener"':'target="_blank"'?>><?=h($L[0])?></a>
            <?php endforeach; ?>
            <?php if(empty($c['links'])): ?>
              <span class="soonline">Codelab + whitepaper links post when the day goes live →
                <a href="<?=h($COURSE_URL)?>" target="_blank" rel="noopener">course page</a> ·
                <a href="<?=h($KAGGLE_YT)?>" target="_blank" rel="noopener">livestream</a></span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- OFFICIAL LINKS QUICK ROW -->
  <div class="shead"><h2>🔗 Official</h2><div class="ln"></div></div>
  <div class="card mats">
    <a href="<?=h($COURSE_URL)?>" target="_blank" rel="noopener">🏠 Kaggle course page</a>
    <a href="<?=h($KAGGLE_YT)?>" target="_blank" rel="noopener">📺 Kaggle livestreams (YouTube)</a>
    <a href="https://discord.gg/kaggle" target="_blank" rel="noopener">💬 Kaggle Discord</a>
    <a href="https://aistudio.google.com" target="_blank" rel="noopener">🧠 Google AI Studio</a>
    <a href="https://github.com/israelburns/vibe-squad-board" target="_blank" rel="noopener">⚙️ This board's repo (fork + PR)</a>
  </div>

  <!-- SQUAD WORKSPACE -->
  <div class="shead"><h2>🚀 Squad Workspace</h2><div class="ln"></div></div>
  <div class="grid">
    <!-- NOTES -->
    <div class="card">
      <div class="mono" style="margin-bottom:8px">Sticky wall</div>
      <form method="post">
        <input type="hidden" name="action" value="note">
        <input name="name" placeholder="your name" maxlength="30">
        <textarea name="text" rows="3" placeholder="a thought, a blocker, a win..." maxlength="600"
          oninput="document.getElementById('nc').textContent=this.value.length"></textarea>
        <div class="ctr"><span id="nc">0</span>/600</div>
        <button class="mini">📌 Pin it</button>
      </form>
      <div style="margin-top:10px">
      <?php foreach(array_reverse($board['notes']??[],true) as $i=>$nt):
        $editing = isset($_GET['edit']) && (int)$_GET['edit'] === $i; ?>
        <div class="note">
          <?php if($editing): ?>
            <form method="post">
              <input type="hidden" name="action" value="editnote"><input type="hidden" name="i" value="<?=$i?>">
              <b><?=h($nt['name'])?> · <?=h($nt['ts'])?></b>
              <textarea name="text" rows="4" maxlength="600" oninput="this.nextElementSibling.firstElementChild.textContent=this.value.length"><?=h($nt['text'])?></textarea>
              <div class="ctr"><span><?=mb_strlen($nt['text'])?></span>/600</div>
              <button class="mini">💾 Save</button>
              <a href="./" class="empty" style="margin-left:8px">cancel</a>
            </form>
          <?php else: ?>
            <a href="?edit=<?=$i?>" class="ed" title="edit">✏️</a>
            <form method="post" style="display:inline">
              <input type="hidden" name="action" value="delnote"><input type="hidden" name="i" value="<?=$i?>">
              <button class="x" title="remove">✕</button>
            </form>
            <b><?=h($nt['name'])?> · <?=h($nt['ts'])?></b><?=nl2br(h($nt['text']))?>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <?php if(empty($board['notes'])): ?><div class="empty">No notes yet — pin the first one.</div><?php endif; ?>
      </div>
    </div>

    <!-- LINKS + FILES -->
    <div class="card">
      <div class="mono" style="margin-bottom:8px">Shared resources</div>
      <form method="post">
        <input type="hidden" name="action" value="link">
        <input name="title" placeholder="title (e.g. Day 1 codelab)" maxlength="120">
        <input name="url" placeholder="https://...">
        <button class="mini">➕ Add link</button>
      </form>
      <div style="margin:10px 0">
      <?php foreach(array_reverse($board['links']??[]) as $lk): ?>
        <div class="lnk">🔗 <a href="<?=h($lk['url'])?>" target="_blank" rel="noopener"><?=h($lk['title'])?></a></div>
      <?php endforeach; ?>
      <?php if(empty($board['links'])): ?><div class="empty">No links yet.</div><?php endif; ?>
      </div>

      <div class="mono" style="margin:16px 0 8px">File drop <span style="text-transform:none;color:var(--dim)">· 15MB max, docs only</span></div>
      <?php if(isset($_GET['e'])) echo '<p class="err">File rejected (bad type or &gt;15MB).</p>'; ?>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload">
        <input type="file" name="file">
        <button class="mini">⬆ Upload</button>
      </form>
      <div style="margin-top:10px">
      <?php foreach($files as $fn): ?>
        <div class="file">📄 <a href="uploads/<?=rawurlencode($fn)?>" target="_blank"><?=h($fn)?></a></div>
      <?php endforeach; ?>
      <?php if(!$files): ?><div class="empty">No files yet.</div><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="foot">Vibe Squad · built for the bros · generation is solved — verification, judgment & direction are the craft 🚀</div>
<?php endif; ?>

</div></body></html>
