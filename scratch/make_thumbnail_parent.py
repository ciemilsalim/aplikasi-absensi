from PIL import Image, ImageDraw, ImageFont
import os

out_dir = 'd:/laragon/www/siasek/aplikasi-absensi/.tutorial-video/thumbnail'
os.makedirs(out_dir, exist_ok=True)
out_path = os.path.join(out_dir, 'tutorial-pengajuan-izin-orang-tua-v2.png')

w, h = 1920, 1080
img = Image.new('RGB', (w, h), color=(15, 23, 42))
draw = ImageDraw.Draw(img)

# Accent bar & overlay
draw.rectangle([100, 120, 120, 960], fill=(59, 130, 246))
draw.rectangle([160, 450, 1760, 630], fill=(30, 41, 59))

try:
    font_title = ImageFont.truetype("arial.ttf", 64)
    font_sub = ImageFont.truetype("arial.ttf", 36)
    font_badge = ImageFont.truetype("arial.ttf", 28)
except Exception:
    font_title = ImageFont.load_default()
    font_sub = ImageFont.load_default()
    font_badge = ImageFont.load_default()

# Title & Subtitle
draw.text((180, 200), "SIASEK TUTORIAL ORANG TUA", fill=(59, 130, 246), font=font_badge)
draw.text((180, 280), "Panduan Pengajuan Izin / Sakit Anak Online", fill=(255, 255, 255), font=font_title)
draw.text((180, 480), "Modul Layanan Mandiri Orang Tua & Wali Murid", fill=(226, 232, 240), font=font_sub)
draw.text((180, 540), "Sistem Informasi & Absensi Sekolah SMP Negeri 1 Biau", fill=(148, 163, 184), font=font_sub)

img.save(out_path)
print(f"Thumbnail created: {out_path}")
