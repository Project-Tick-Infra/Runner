FROM python:3.11-slim-bookworm AS base

RUN apt-get update &&  \
    apt-get install --no-install-recommends -y \
    # Install CairoSVG dependencies.
    libcairo2 && \
    # Cleanup APT.
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    # Create a non-root user.
    useradd --shell /usr/sbin/nologin --create-home -d /opt/modmail modmail

USER modmail:modmail
WORKDIR /opt/modmail

FROM base AS builder

USER root
RUN pip install poetry==1.8.3
USER modmail:modmail

ENV POETRY_NO_INTERACTION=1 \
    POETRY_VIRTUALENVS_IN_PROJECT=1 \
    POETRY_VIRTUALENVS_CREATE=1 \
    POETRY_CACHE_DIR=/tmp/poetry_cache

RUN touch README.md
COPY pyproject.toml poetry.lock ./

RUN poetry install --no-root && rm -rf $POETRY_CACHE_DIR

FROM base as runtime

ENV VIRTUAL_ENV=/opt/modmail/.venv \
    PATH="/opt/modmail/.venv/bin:$PATH"

COPY --from=builder /opt/modmail/ /opt/modmail/

COPY ./ /opt/modmail/

ENV PYTHONUNBUFFERED=1 \
    PYTHONDONTWRITEBYTECODE=1 \
    USING_DOCKER=yes

CMD ["python", "bot.py"]
