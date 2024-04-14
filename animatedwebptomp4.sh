count=`ls -1 *.png 2>/dev/null | wc -l`
if [ $count == 0 ]
then 
ls *.webp | parallel -j8 -I {} convert {} {.}.png
fi 
for f in *.webp; do ffmpeg.exe -stream_loop 3 -framerate $((1000/$(webpinfo "$f" | grep Duration | head -n 1 | sed 's/.* //'))) -i "${f%.*}-%d.png" -c:v libx265 -crf 20 -preset slow "${f%.*}.mp4" -y; done