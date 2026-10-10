import imageio_ffmpeg
import subprocess

ffmpeg = imageio_ffmpeg.get_ffmpeg_exe()
cmd = [
    ffmpeg, '-y',
    '-ss', '35.0',
    '-i', '.tutorial-video/final/tutorial-monitoring-log-scan-absensi-v2.3.mp4',
    '-frames:v', '1',
    '-q:v', '2',
    '.tutorial-video/thumbnail/tutorial-monitoring-log-scan-absensi-v2.3.png'
]
subprocess.run(cmd, check=True)
print('Thumbnail created: .tutorial-video/thumbnail/tutorial-monitoring-log-scan-absensi-v2.3.png')
