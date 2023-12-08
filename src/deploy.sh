#!/bin/bash

git_origin=$(git config --get remote.origin.url)
push_repo="n"
commit_message="Default Lay Commit message"
git_proj_dir=""

lay_dir=$( cd "$(dirname "${BASH_SOURCE[0]}")" ; pwd -P )
# lay_dir=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
current_dir=""

IFS='/' read -ra ADDR <<< "$lay_dir"
for i in "${ADDR[@]}"; do
  current_dir=$i
done

lay_dir=$lay_dir"/"

echo "----------  LAY DEPLOY INIT    ----------"

# Detect working directory
if [ $current_dir != "Lay" ]; then
  echo "  Not working on [Lay] directory"
  echo '  Enter relative path to [Lay] directory, e.g `/Lay`'
  read -p "[Lay] directory $PWD/" lay_dir
  lay_dir=$PWD"/${lay_dir}/"
fi

res_dir=$lay_dir"../"
ignore=$1
invalidate=$2

# talk about git on verbose mode
if [[ -n $ignore && $ignore == '-s' ]]; then
  ignore=$2
  invalidate=$3
  commit_message=''
  # Git Push Condition
else

  if [ -n "${git_origin}" ]; then
    echo "You have git on your project with remote origin: $git_origin"
    read -n1 -p "Do you want to also push? [Y/n]: " push_repo
    git_proj_dir=$(git rev-parse --show-toplevel)
    echo ""
  fi

  case $push_repo in
    n|N) echo "Ignoring git..."
    commit_message='' ;;
    *) read -p "Commit message: " commit_message;;
  esac
fi


echo "**************** Production Bundling Begins"

echo "== Lay JS FILES"
terser $lay_dir'omj$/index.js' -c -m -o $lay_dir'omj$/index.min.js'
terser $lay_dir'static/js/constants.js' -c -m -o $lay_dir'static/js/constants.min.js'

echo "== RES FOLDER"
echo "*** Ignoring $ignore ***"

php "${lay_dir}"compress $res_dir'res/client/dev' -o $res_dir'res/client/prod' -i $ignore $invalidate

echo "**************** Production Bundling Ends"

if [ -n "${commit_message}" ]; then
  echo "************** Deploying to github"
  cd $git_proj_dir && git add .
  git commit -m "$commit_message"
  git pull && git push
else
  echo "Did not deploy to github :("
fi

echo "Process ended (-_-)"
