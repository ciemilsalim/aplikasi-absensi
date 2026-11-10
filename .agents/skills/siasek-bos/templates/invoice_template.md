# INVOICE / TAGIHAN LAYANAN {{PROJECT_CODE}}

**Nomor Invoice**: {{INVOICE_NUMBER}}  
**Status Invoice**: **[{{INVOICE_STATUS}}]**  
**Tanggal**: {{INVOICE_DATE}}  
**Periode Layanan**: {{PERIOD_NAME}} ({{PERIOD_START}} s.d. {{PERIOD_END}})  

---

### ITEM LAYANAN

| No | Deskripsi Layanan | {{QUANTITY_LABEL}} | {{RATE_LABEL}} | Total Tagihan |
| :---: | :--- | :---: | ---: | ---: |
{{INVOICE_ITEM_ROWS}}

**Terbilang**: *{{TERBILANG}}*  

---

### PIHAK PENYEDIA & PELANGGAN

**Penyedia Layanan (Provider)**:  
**{{PROVIDER_NAME}}**  
Pengembang: {{PROVIDER_DEV}}  
Kontak:  
{{PROVIDER_EMAIL}}  

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
> ***Bukti pembayaran dilampirkan oleh pihak pelanggan/pengelola.***

