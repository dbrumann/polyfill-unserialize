FROM php:5.6-cli

RUN apt-get update -yq; \
    apt-get install -yq wget

RUN wget -O phpunit https://phar.phpunit.de/phpunit-5.7.27.phar; \
    chmod a+x phpunit; \
    mv phpunit /usr/local/bin/phpunit

RUN wget https://raw.githubusercontent.com/composer/getcomposer.org/76a7060ccb93902cd7576b67264ad91c8a2700e2/web/installer -O - -q | php -- --quiet --filename=composer --install-dir=/usr/bin

WORKDIR /opt/app

CMD ["bash", "-c", "composer install; phpunit -c phpunit.xml.dist"]
