FROM postgres:18.6-bookworm@sha256:afc7e2d441324c0388fa80c3d24f733b4194a4eb7f47dd8ee2b08eb1a24a647c

ARG APP_VERSION=0.9.0-rc.1
ARG VCS_REF=unknown

RUN rm /usr/local/bin/gosu

USER postgres

LABEL org.opencontainers.image.title="Healthcare Node Registry PostgreSQL" \
      org.opencontainers.image.version="${APP_VERSION}" \
      org.opencontainers.image.revision="${VCS_REF}" \
      org.opencontainers.image.source="https://github.com/ley338-gif/Healthcare-Node-Registry"
