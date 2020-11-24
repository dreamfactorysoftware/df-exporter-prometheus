FROM ubuntu:xenial

RUN apt-get update -y && apt-get install -y --no-install-recommends software-properties-common

RUN LANG=C.UTF-8 add-apt-repository ppa:ondrej/php -y && \
    apt-get update && apt-get install -y --no-install-recommends --allow-unauthenticated \
    bash git-core curl mcrypt nginx openssl python nodejs zip unzip ssmtp wget gcc make autoconf pkg-config libc-dev libmcrypt-dev \
    php-pear php7.2-dev php7.2-fpm php7.2-common php7.2-cli php7.2-curl php7.2-json php7.2-mysqlnd php7.2-pgsql \
    php7.2-ldap php7.2-interbase php7.2-mbstring php7.2-bcmath php7.2-zip php7.2-soap php7.2-sybase php7.2-xml php7.2-sqlite && \
    pecl channel-update pecl.php.net

RUN apt-get install -y --allow-unauthenticated python-pip python3-pip

RUN pecl install mongodb && \
    echo "extension=mongodb.so" > /etc/php/7.2/mods-available/mongodb.ini && \
    phpenmod mongodb

# install composer
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer && \
    chmod +x /usr/local/bin/composer
