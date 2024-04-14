echo -n 'General;%CompleteName%\n%BitRate%\n
Video;%Width%x%Height%\n%FrameRate%\n%Format%\n' > /tmp/template;
find -type f \( -iname "*.mp4" -or -iname "*.m4v" -or -iname "*.wmv" -or -iname "*.mov" -or -iname "*.ts" -or -iname "*.mkv" -or -iname "*.webm" -or -iname "*.avi" -or -iname "*.mpg" -or -iname "*.3gp" -or -iname "*.flv" \) -exec mediainfo --Output="file:///tmp/template" {} \+