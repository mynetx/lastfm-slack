FROM arm32v7/php:7.3
COPY qemu-arm-static /usr/bin/
LABEL maintainer="Josh Ghent <me@joshghent.com>"

RUN apt-get update && apt-get install zip git -y

# Install dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

WORKDIR /app
COPY composer.json composer.lock ./

RUN composer install --ignore-platform-reqs --prefer-dist --no-scripts --no-dev && rm -rf /root/.composer
COPY . ./

CMD [ "php", "statusUpdate.php" ]
