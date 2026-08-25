FROM postgres:18.4-bookworm@sha256:882236b897e39051d2368c5ccc6cda944904723506b2dfc97f2a8f5bc9afa382

ARG APP_VERSION=0.9.0-rc.1
ARG VCS_REF=unknown

RUN rm /usr/local/bin/gosu

USER postgres

LABEL org.opencontainers.image.title="Healthcare Node Registry PostgreSQL" \
      org.opencontainers.image.version="${APP_VERSION}" \
      org.opencontainers.image.revision="${VCS_REF}" \
      org.opencontainers.image.source="https://github.com/ley338-gif/Healthcare-Node-Registry"
