gource  -s .1  -1200x900 --background-colour 000000  --auto-skip-seconds .1  --multi-sampling  --stop-at-end    --highlight-users  --date-format "%d/%m/%y"  --hide mouse,filenames  --file-idle-time 0  --max-files 0       --logo './logo-small.png'  --font-size 14  --output-ppm-stream -  --output-framerate 30  |  ffmpeg -y -r 30 -f image2pipe -vcodec ppm -i - -b 65536K './movie.mp4'