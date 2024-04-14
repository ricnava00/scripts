find -iname "*.jpg" | parallel -X -j200% "jpeginfo -c" | grep -E "WARNING|ERROR"
