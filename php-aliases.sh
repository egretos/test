alias php='docker run --rm -i --user "$(id -u):$(id -g)" -v "$PWD":/app local-php php'
alias composer='docker run --rm -i --user "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer -v "$PWD":/app local-php composer'
