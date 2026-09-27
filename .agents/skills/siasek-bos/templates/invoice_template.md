# INVOICE / TAGIHAN LAYANAN SIASEK

**Nomor Invoice**: {{INVOICE_NUMBER}}  
**Status Invoice**: **[{{INVOICE_STATUS}}]**  
**Tanggal**: {{INVOICE_DATE}}  
**Periode Layanan**: {{PERIOD_NAME}} ({{PERIOD_START}} s.d. {{PERIOD_END}})  

---

### ITEM LAYANAN

| No | Deskripsi Layanan | Jumlah Siswa (User) | Tarif / Siswa / Bulan | Total Tagihan |
| :---: | :--- | :---: | :---: | :---: |
| 1 | **{{SERVICE_NAME}}**<br>Layanan SaaS Presensi Digital, Portal Orang Tua, Executive Analytics & Supervisi Jurnal Guru | {{STUDENT_COUNT}} Siswa | Rp{{RATE_FORMATTED}} | **Rp{{TOTAL_FORMATTED}}** |

**Terbilang**: *{{TERBILANG}}*  

---

### PIHAK PENYEDIA & PELANGGAN

**Penyedia Layanan (Provider)**:  
**{{PROVIDER_NAME}}**  
Pengembang: {{PROVIDER_DEV}}  
Kontak: {{PROVIDER_EMAIL}}  

**Pelanggan (Customer)**:  
**{{CUSTOMER_NAME}}**  
Alamat: {{CUSTOMER_ADDRESS}}  

---

### INSTRUKSI PEMBAYARAN

- **Metode Pembayaran**: {{PAYMENT_METHOD}}  
- **Bank**: {{PAYMENT_BANK}}  
- **Atas Nama**: {{PAYMENT_ACCOUNT_NAME}}  
- **Nomor Rekening**: {{PAYMENT_ACCOUNT_NUMBER}}  

> [!NOTE]  
> *Dokumen ini merupakan invoice resmi penagihan jasa layanan penggunaan aplikasi dari pihak penyedia.*  
> ***Bukti pembayaran dilampirkan oleh pihak sekolah.***
