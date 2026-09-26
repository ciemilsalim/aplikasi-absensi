import subprocess
import json

def run_cmd(cmd):
    return subprocess.check_output(cmd, text=True, encoding='utf-8')

# Generate git-history-full.txt
history = run_cmd(['git', 'log', '--all', '--format=%H|%h|%ad|%an|%s', '--date=short'])
with open('docs/LPJ/git-history-full.txt', 'w', encoding='utf-8') as f:
    f.write(history)

lines = [l.strip() for l in history.strip().split('\n') if l.strip()]

app_commits = []
lpj_commits = []
other_commits = []
hybrid_commits = []

for line in lines:
    parts = line.split('|', 4)
    h_full, h_short, date, author, msg = parts[0], parts[1], parts[2], parts[3], parts[4]
    
    files_str = run_cmd(['git', 'show', '--name-only', '--format=', h_full]).strip()
    files = [f.strip() for f in files_str.split('\n') if f.strip()]
    
    is_app = False
    is_lpj = False
    
    for f in files:
        if f.startswith('docs/') or f.startswith('.agents/'):
            is_lpj = True
        else:
            is_app = True
            
    if is_app and not is_lpj:
        app_commits.append({'hash': h_full, 'short': h_short, 'date': date, 'author': author, 'msg': msg, 'files': files})
    elif is_lpj and not is_app:
        lpj_commits.append({'hash': h_full, 'short': h_short, 'date': date, 'author': author, 'msg': msg, 'files': files})
    elif is_app and is_lpj:
        hybrid_commits.append({'hash': h_full, 'short': h_short, 'date': date, 'author': author, 'msg': msg, 'files': files})
        # If hybrid, count as app or lpj depending on primary focus
        app_commits.append({'hash': h_full, 'short': h_short, 'date': date, 'author': author, 'msg': msg, 'files': files})
    else:
        other_commits.append({'hash': h_full, 'short': h_short, 'date': date, 'author': author, 'msg': msg, 'files': files})

print("==================================================")
print("HASIL KLASIFIKASI COMMIT HISTORI REPOSITORI")
print("==================================================")
print(f"Total Histori Lokal:                 {len(lines)}")
origin_count = run_cmd(['git', 'rev-list', '--count', 'origin/main']).strip()
print(f"Total origin/main Remote:           {origin_count}")
left_right = run_cmd(['git', 'rev-list', '--left-right', '--count', 'HEAD...origin/main']).strip()
print(f"Commit Lokal Unik (HEAD vs Remote): {left_right.split()[0]} lokal, {left_right.split()[1]} remote")
print(f"Commit Pengembangan Aplikasi:       {len(app_commits)}")
print(f"Commit Dokumentasi/Tooling LPJ:    {len(lpj_commits)}")
print(f"Commit Hybrid (App + Docs):         {len(hybrid_commits)}")
print(f"Commit Lainnya:                     {len(other_commits)}")

print("\n--------------------------------------------------")
print("COMMIT TERAKHIR PENGEMBANGAN APLIKASI:")
latest_app = app_commits[0]
print(f"  HASH:    {latest_app['hash']} ({latest_app['short']})")
print(f"  DATE:    {latest_app['date']}")
print(f"  MESSAGE: {latest_app['msg']}")
print(f"  FILES (sample): {latest_app['files'][:3]}")

print("\n--------------------------------------------------")
print("COMMIT PERTAMA DOKUMENTASI/TOOLING LPJ:")
first_lpj = lpj_commits[-1]
print(f"  HASH:    {first_lpj['hash']} ({first_lpj['short']})")
print(f"  DATE:    {first_lpj['date']}")
print(f"  MESSAGE: {first_lpj['msg']}")
print(f"  FILES (sample): {first_lpj['files'][:3]}")

print("\n--------------------------------------------------")
print("COMMIT TERAKHIR DOKUMENTASI/TOOLING LPJ:")
latest_lpj = lpj_commits[0]
print(f"  HASH:    {latest_lpj['hash']} ({latest_lpj['short']})")
print(f"  DATE:    {latest_lpj['date']}")
print(f"  MESSAGE: {latest_lpj['msg']}")

# Check if LPJ commits exist on origin/main
print("\n--------------------------------------------------")
print("EVALUASI REMOT GITHUB:")
# Check if b131b87 is in origin/main
remote_revs = run_cmd(['git', 'rev-list', 'origin/main']).strip().split('\n')
is_b131_on_remote = latest_lpj['hash'] in remote_revs
print(f"  Apakah commit LPJ terbaru ({latest_lpj['short']}) sudah ada di GitHub origin/main? {'YA' if is_b131_on_remote else 'BELUM (Masih di HEAD Lokal)'}")
is_latest_app_on_remote = latest_app['hash'] in remote_revs
print(f"  Apakah commit aplikasi terakhir ({latest_app['short']}) sudah ada di GitHub origin/main? {'YA' if is_latest_app_on_remote else 'TIDAK'}")

print("\n--------------------------------------------------")
print("RENTANG HISTORI:")
app_dates = [c['date'] for c in app_commits]
lpj_dates = [c['date'] for c in lpj_commits]
print(f"  Rentang Histori Aplikasi:         {min(app_dates)} s.d. {max(app_dates)}")
print(f"  Rentang Histori Dokumentasi LPJ:  {min(lpj_dates)} s.d. {max(lpj_dates)}")
