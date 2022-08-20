#!/bin/bash
read -n1 -p "Do you want to also push to your repo? [Y/n]: " push_repo
commit_message=""

echo "" 
case $push_repo in
  n|N) echo "Ignoring git..." ;;

  *) read -p "Type commit message: " commit_message ;;
esac

echo "================= Production Bundling Begins"

echo "== Lay JS FILES"
terser 'omj$/index.js' -c -m -o 'omj$/index.min.js'
terser 'static/js/constants.js' -c -m -o 'static/js/constants.min.js'

echo "== RES FOLDER [JS]"
php compress ../res/client/dev -o ../res/client/prod -e js

echo "== RES FOLDER [CSS]"
php compress ../res/client/dev -o ../res/client/prod -e css

echo "================= Production Bundling Ends"

if [ -n "${commit_message}" ]; then
  echo "================= Deploying to github"
  git add .
  git commit -m "$commit_message"
  git push
else
  echo "Did not deploy to github :("
fi

echo "Process ended (-_-)"