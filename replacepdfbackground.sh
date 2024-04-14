mkdir converted 2>/dev/null
file="${1%.*}"
pdftk "$1" output "${file}_uncomp.pdf" uncompress
cat "${file}_uncomp.pdf" | perl -p0e 's/1(\.0+)?\n1(\.0+)?\n1(\.0+)?/0.584\n0.639\n0.675/igs' > "${file}_repl.pdf"
rm "${file}_uncomp.pdf"
pdftk "${file}_repl.pdf" output "converted/${file}.pdf" compress
rm "${file}_repl.pdf"