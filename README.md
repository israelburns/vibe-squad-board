# Vibe Squad — Course Blackboard

A dead-simple shared blackboard for our **Kaggle 5-Day AI Agents (Vibe Coding)** study group.
One PHP file, flat-JSON storage, no database. Live at **llmdreams.com/Study**.

## What it does
- 🗓️ 5-day course tracker (shared)
- 📝 Sticky-note wall
- 🔗 Resource links
- 📎 File drop (PDFs, slides, notebooks — no executable types)

## Run it anywhere with PHP
```
cp config.sample.php config.php   # set your passcode
php -S localhost:8000             # open http://localhost:8000
```

## Want to improve it?
Fork, branch, PR. Keep it ONE file + flat JSON — that's the whole charm.
Never commit `config.php` (your passcode) — it's gitignored on purpose.
