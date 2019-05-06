#!/bin/bash

HD="/var/www/html/webit-installer8.7"


drush cex #esportazione configurazioni in config/sync
rm -Rf $HD/web/profiles/custom/webit_installer/config/install/* #pulizia configurazioni in profilo
cp -a $HD/config/sync/* $HD/web/profiles/custom/webit_installer/config/install #copia file di configurazione in profilo
rm -f $HD/web/profiles/custom/webit_installer/config/install/core.extension.yml #eliminazione core.extension da profilo

IFSOLD=$IFS
IFS=$'\n'

#pulizia di uuid e _core da file di configurazione
for i in $(find $HD/web/profiles/custom/webit_installer/config/install/ -type f); do
   mv "$i" "$i.swap"
   sed -e '/^uuid: /d' -e '/_core/,+1d' "$i.swap" > "$i"
   rm -f "$i.swap"
done

PROFILE_NAME=$(sed -ne "s/^ *profile: *\(.*\)$/\1/p" $HD/config/sync/core.extension.yml)

HEADER=$(sed -ne '1,/^dependencies:$/p' $HD/web/profiles/custom/webit_installer/webit_installer.info.yml)

echo "$HEADER" > $HD/web/profiles/custom/webit_installer/webit_installer.info.yml

#aggiornamento dipendenze da moduli e temi in info.yml del profilo
for i in $(sed -ne '/^module:$/,/^theme:$/s/ \+\(.*\): *[0-9]*$/\1/p' $HD/config/sync/core.extension.yml); do
   if [ ! "$i" = "$PROFILE_NAME" ]; then
      echo "  - $i" >> $HD/web/profiles/custom/webit_installer/webit_installer.info.yml
   fi
done

echo "themes:" >> $HD/web/profiles/custom/webit_installer/webit_installer.info.yml

for i in $(sed -ne '/^theme:$/,/^$/s/ \+\(.*\): *[0-9]*$/\1/p' $HD/config/sync/core.extension.yml); do
   echo "  - $i" >> $HD/web/profiles/custom/webit_installer/webit_installer.info.yml
done

IFS=$IFSOLD

rm -f $HD/config/sync/*

exit 0