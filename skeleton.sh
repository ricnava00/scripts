find . -type d -print0 >dirs.txt
mkdir converted 2>/dev/null
cd converted
xargs -0 mkdir -p <../dirs.txt
rm -rf converted
rm ../dirs.txt