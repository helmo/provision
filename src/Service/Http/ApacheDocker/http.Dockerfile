FROM ubuntu:14.04

RUN apt-get update -qq && apt-get install -y -qq\
  apache2 \
  php5 \
  php5-cli \
  php5-gd \
  php5-mysql \
  php-pear \
  php5-curl \
  postfix \
  sudo \
  rsync \
  git-core \
  unzip \
  wget \
  mysql-client

# Use --build-arg option when running docker build to set these variables.
# If wish to "mount" a volume to your host, set AEGIR_UID and AEGIR_GID to your local user's UID.
# There are both ARG and ENV lines to make sure the value persists.
# See https://docs.docker.com/engine/reference/builder/#/arg

# The UID and GID for the aegir user.
ARG AEGIR_UID=1000
ENV AEGIR_UID ${AEGIR_UID:-1000}

# The home directory for the aegir user.
ARG AEGIR_ROOT=/var/aegir
ENV AEGIR_ROOT ${AEGIR_ROOT:-/var/aegir}

# The string used to create /var/aegir/config/SERVER_NAME folders
ARG AEGIR_SERVER_NAME=server_master
ENV AEGIR_SERVER_NAME ${AEGIR_SERVER_NAME:-server_master}

RUN echo "Creating user aegir with UID $AEGIR_UID and GID $AEGIR_GID and HOME $AEGIR_ROOT ..."

RUN addgroup --gid $AEGIR_UID aegir
RUN adduser --uid $AEGIR_UID --gid $AEGIR_UID --system --home $AEGIR_ROOT aegir
RUN adduser aegir www-data
RUN a2enmod rewrite

# Save a symlink to the /var/aegir/config/docker.conf file.
RUN mkdir -p /var/aegir/config
RUN chown aegir:aegir /var/aegir/config -R
RUN ln -sf /var/aegir/config/provision.conf /etc/apache2/conf-available/provision.conf
RUN ln -sf /etc/apache2/conf-available/provision.conf /etc/apache2/conf-enabled/provision.conf

COPY sudoers-aegir /etc/sudoers.d/aegir
RUN chmod 0440 /etc/sudoers.d/aegir

# Prepare Aegir Logs folder.
RUN mkdir /var/log/aegir
RUN chown aegir:aegir /var/log/aegir
RUN echo 'Hello, Aegir.' > /var/log/aegir/system.log

# Prepare apache foreground script.
COPY httpd-foreground.sh /usr/local/bin/httpd-foreground
RUN chmod +x /usr/local/bin/httpd-foreground

USER aegir
WORKDIR /var/aegir

VOLUME /var/aegir/config/$AEGIR_SERVER_NAME/apache
VOLUME /var/aegir/platforms

EXPOSE 80
CMD ["httpd-foreground"]