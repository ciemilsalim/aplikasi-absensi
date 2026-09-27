# PEMBARUAN FITUR SIASEK — {{PERIOD_NAME}}

**Periode Repository Audit**: {{PERIOD_START}} s.d. {{PERIOD_END}}  
**Git Commit Cutoff**: `{{GIT_HEAD}}`  

---

## 1. Klasifikasi Perubahan Aplikasi vs. Infrastruktur Evidence

> [!IMPORTANT]  
> Sesuai ketentuan audit, perubahan repositori dipisahkan secara tegas antara **Fitur Aplikasi SIASEK (Bisnis)** dengan **Infrastruktur Audit / Tooling Evidence**.

---

## 2. Tabel Pembaruan Fitur Aplikasi (Business Features)

| FEATURE | ROLE | CHANGE TYPE | GIT EVIDENCE (COMMIT/FILES) | LIVE STATUS | SCREENSHOT | NOTES |
| :--- | :--- | :---: | :--- | :--- | :--- | :--- |
{{BUSINESS_FEATURE_ROWS}}

---

## 3. Tabel Pembaruan Infrastruktur Evidence & Tooling (Non-Business)

| ITEM / TOOLING | KATEGORI | GIT COMMIT / FILES | ALASAN & RISIKO |
| :--- | :--- | :--- | :--- |
{{INFRASTRUCTURE_ROWS}}
