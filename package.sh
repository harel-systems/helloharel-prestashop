#!/bin/bash

VERSION=$(grep '$this->version = ' helloharel.php | sed -E "s/[^0-9\.]+//g")

mkdir -p build

tar --exclude=".git" \
    --exclude=".gitignore" \
    --exclude="composer.*" \
    --exclude="package.sh" \
    --exclude="Jenkinsfile" \
    --exclude="README.md" \
    --exclude="build" \
    --transform 's,^,helloharel/,' \
    -czf "build/helloharel-prestashop-$VERSION.tar.gz" * || \
    exit 1
