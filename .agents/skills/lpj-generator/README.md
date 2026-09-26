# LPJ Generator untuk Antigravity

Skill ini ditujukan untuk membuat LPJ/dokumentasi pengembangan aplikasi berdasarkan kondisi nyata project.

## Instalasi workspace

Salin folder `lpj-generator` ke:

`.agents/skills/lpj-generator/`

di root project Antigravity.

Struktur akhir:

```text
project/
└── .agents/
    └── skills/
        └── lpj-generator/
            ├── SKILL.md
            └── resources/
                ├── LPJ_TEMPLATE.md
                └── EVIDENCE_MATRIX.md
```

Antigravity mendeteksi skill dari `.agents/skills` dan skill dapat dipanggil secara otomatis atau manual dengan `/lpj-generator`.

## Penggunaan

Di Agent Antigravity:

`/lpj-generator`

atau:

`Analisis project ini dan buat LPJ pengembangan aplikasi berdasarkan bukti nyata yang tersedia.`

Untuk audit ulang:

`Gunakan lpj-generator. Audit ulang implementasi dan perbarui semua dokumen LPJ sesuai kondisi project terbaru.`

Jangan meminta skill untuk mengisi fakta yang tidak ada di source code atau hasil pengujian.
