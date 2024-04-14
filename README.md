Scripts that I made at a certain time for a certain task. They may be generally useful, useful for a very specific task, or absolutely useless. Have fun
Some of them may have a description in the first lines
.sh and .php scripts are ran on WSL (they may or may not run on native Linux, PHP scripts may or may not run on Windows), chromeCookies.py is for Windows
The required tools vary for each script and won't be documented. If a command is called with the same name as one of these scripts without an extension, I probably have it installed in /bin, so handle it as necessary

### convertboth.php
Probably the most useful script
Uses ffmpeg to convert possibly diverse video files to standard h264/hevc/av1 with NVENC
Requires Windows ffmpeg to be callable as ffmpeg.exe from WSL (adding it to the windows path should be sufficient), and parallelrunner.php from this repo
findConverted.sh from this repo finds the videos converted with this tool
Run the script for usage information

### parallelrunner.php
Calls commands concurrently, supports progress callbacks. Should support both Windows and Linux, but the testing done on Linux was minimal
Check convertboth.php for usage examples

### newbitrates.sh
Parallel-ly scans video files and returns a JSON-encoded mediainfo report

### findInterlaced.php
As the title suggests, tries to determine if a video is interlaced or not, with a window increasing from 60, 240, 480, to all frames if the decision can't be made with a smaller one

### TGDecrypt.sh / TGDecryptLocal.sh
Decrypts Telegram for Android .enc files, given the corresponding .key file
.enc files are from Android/media, .key files are from /data/data
If not found, the key file is read directly with adb (only TGDecrypt.sh)

### gtacrewimage.php
Probably useless to most, and maybe it doesn't even work anymore, but a nice script so I will put it here
Converts a png to an svg, each pixel becomes a square, and the resulting svg is (or was) compliant with the Rockstar image editor. Works well with pixel art, probably not as well with more defined images since there might be a limit on the shapes of the svg.

### mergeTorrents.php
Again, very specific but might be useful
If two torrents have the same exact file, and the file fails to download completely from both of them, this script allows to merge the two files thus possibly complementing the missing chunks
If this works, be sure to seed the complete files to both torrents!

### Other scripts
Either even more useless, even more specific, or self-explanatory
