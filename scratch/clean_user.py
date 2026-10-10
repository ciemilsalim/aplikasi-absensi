import pymysql

try:
    conn = pymysql.connect(host='127.0.0.1', user='root', password='', database='db_absen')
    with conn.cursor() as cur:
        cur.execute("SELECT id, name, email FROM users WHERE email='ahmad.subarjo@example.com'")
        row = cur.fetchone()
        print('Found in DB:', row)
        if row:
            uid = row[0]
            try:
                cur.execute("DELETE FROM parent_models WHERE user_id=%s", (uid,))
            except Exception:
                pass
            try:
                cur.execute("DELETE FROM parents WHERE user_id=%s", (uid,))
            except Exception:
                pass
            cur.execute("DELETE FROM users WHERE id=%s", (uid,))
            conn.commit()
            print('Successfully deleted ahmad.subarjo@example.com')
        else:
            print('Clean: user does not exist in db_absen.')
    conn.close()
except Exception as e:
    print('DB err:', e)
