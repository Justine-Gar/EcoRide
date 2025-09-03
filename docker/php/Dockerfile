#depart
FROM php:8.2-fpm

#installation des dépendances système necessaire
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev

#installation des extention php necessaire pour symfony
RUN docker-php-ext-install \
    pdo_mysql \
    intl \
    zip
#installetion de mongodb
RUN apt-get update && apt-get install -y \
    libssl-dev \
    libsasl2-dev \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb
    
#installation de composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
#Installation de symfony CLI pour les commande de synfony
RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony
#definission du répertoire (container de travail)
WORKDIR /var/www/symfony