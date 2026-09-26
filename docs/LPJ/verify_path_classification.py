import subprocess
import json

def run_cmd(cmd):
    return subprocess.check_output(cmd, text=True, encoding='utf-8')

# App component paths/files criteria
APP_PREFIXES = (
    'app/', 'bootstrap/', 'config/', 'database/', 'public/', 
    'resources/', 'routes/', 'storage/', 'tests/'
)
APP_EXACT_FILES = {
    'composer.json', 'composer.lock', 'package.json', 'package-lock.json',
    'vite.config.js', 'vite.config.ts', 'tailwind.config.js', 'tailwind.config.ts',
    'postcss.config.js', 'postcss.config.cjs', 'phpunit.xml', 'artisan'
}

def is_app_file(filepath):
    f = filepath.strip()
    if any(f.startswith(p) for p in APP_PREFIXES):
        return True
    if f in APP_EXACT_FILES:
        return True
    return False

def is_doc_or_tool_file(filepath):
    f = filepath.strip()
    if f.startswith('docs/') or f.startswith('.agents/'):
        return True
    if f.lower() in ('readme.md', 'readme', 'license', 'changelog.md'):
        return True
    return False

history = run_cmd(['git', 'log', '--all', '--format=%H|%h|%ad|%an|%s', '--date=short'])
lines = [l.strip() for l in history.strip().split('\n') if l.strip()]

app_only_commits = []
doc_only_commits = []
hybrid_commits = []
other_commits = []

for line in lines:
    parts = line.split('|', 4)
    h_full, h_short, date, author, msg = parts[0], parts[1], parts[2], parts[3], parts[4]
    
    files_str = run_cmd(['git', 'show', '--name-only', '--format=', h_full]).strip()
    files = [f.strip() for f in files_str.split('\n') if f.strip()]
    
    has_app = any(is_app_file(f) for f in files)
    has_doc = any(is_doc_or_tool_file(f) for f in files)
    has_other = any(not is_app_file(f) and not is_doc_or_tool_file(f) for f in files)
    
    commit_data = {'hash': h_full, 'short': h_short, 'date': date, 'author': author, 'msg': msg, 'files': files}
    
    if has_app and not has_doc:
        app_only_commits.append(commit_data)
    elif has_doc and not has_app:
        doc_only_commits.append(commit_data)
    elif has_app and has_doc:
        hybrid_commits.append(commit_data)
    else:
        other_commits.append(commit_data)

origin_count = run_cmd(['git', 'rev-list', '--count', 'origin/main']).strip()
head_count = str(len(lines))
total_app_related = len(app_only_commits) + len(hybrid_commits)

print("==================================================")
print("HASIL VERIFIKASI KLASIFIKASI PATH (TAHAP 5A-4C)")
print("==================================================")
print(f"Total commit HEAD lokal:               {head_count}")
print(f"Total commit origin/main:             {origin_count}")
print(f"Pure App-Development Commits:         {len(app_only_commits)}")
print(f"Hybrid Commits (App + Docs/Tooling):   {len(hybrid_commits)}")
print(f"Total Application-Development Commits: {total_app_related}")
print(f"Pure Documentation/Tooling Commits:   {len(doc_only_commits)}")
print(f"Other Commits (e.g. .gitignore only): {len(other_commits)}")

if other_commits:
    print("\n--------------------------------------------------")
    print("OTHER COMMITS DETAILED:")
    for c in other_commits:
        print(f"  {c['short']} | {c['date']} | {c['msg']} | Files: {c['files']}")

if hybrid_commits:
    print("\n--------------------------------------------------")
    print("HYBRID COMMITS DETAILED:")
    for c in hybrid_commits:
        print(f"  {c['short']} | {c['date']} | {c['msg']} | Files: {c['files']}")

# Identify latest app-related commit
all_app_commits = sorted(app_only_commits + hybrid_commits, key=lambda x: lines.index(f"{x['hash']}|{x['short']}|{x['date']}|{x['author']}|{x['msg']}"))

latest_app = all_app_commits[0]
print("\n--------------------------------------------------")
print("LAST APPLICATION DEVELOPMENT COMMIT:")
print(f"  HASH:    {latest_app['hash']} ({latest_app['short']})")
print(f"  DATE:    {latest_app['date']}")
print(f"  MESSAGE: {latest_app['msg']}")
print(f"  FILES:   {latest_app['files']}")

# Identify first LPJ tooling commit
first_lpj = doc_only_commits[-1]
print("\n--------------------------------------------------")
print("FIRST LPJ TOOLING COMMIT:")
print(f"  HASH:    {first_lpj['hash']} ({first_lpj['short']})")
print(f"  DATE:    {first_lpj['date']}")
print(f"  MESSAGE: {first_lpj['msg']}")
print(f"  FILES:   {first_lpj['files']}")

app_dates = [c['date'] for c in all_app_commits]
doc_dates = [c['date'] for c in doc_only_commits]

print("\n--------------------------------------------------")
print("RENTANG PENGEMBANGAN:")
print(f"  Rentang Pengembangan Aplikasi: {min(app_dates)} s.d. {max(app_dates)}")
print(f"  Rentang Dokumentasi LPJ:        {min(doc_dates)} s.d. {max(doc_dates)}")
