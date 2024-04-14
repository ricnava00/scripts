find . -type f -iname '*.png' -print0 | parallel -j8 -0 "echo {} && convert {} -resize '2160x2160^>' -background white -flatten -quality 95 {.}.jpg && rm {}"