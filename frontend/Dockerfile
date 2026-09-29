# syntax=docker/dockerfile:1

# Nuxt development image.
# Versions are pinned on purpose; bump them deliberately.
FROM node:24.21.0-bookworm-slim

ARG UID=1000
ARG GID=1000

# Reuse the image's "node" user, re-numbered to match the host user,
# so files created in the bind-mounted source (node_modules/, .nuxt/) stay editable.
RUN groupmod --non-unique --gid "${GID}" node \
    && usermod --non-unique --uid "${UID}" --gid "${GID}" node

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

WORKDIR /app
USER node

ENV NUXT_TELEMETRY_DISABLED=1

EXPOSE 3000

ENTRYPOINT ["entrypoint"]
CMD ["npm", "run", "dev"]
