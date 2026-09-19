#!/usr/bin/env bash
set -euo pipefail

# Runs as root at image build time. An empty named volume mounted onto an
# existing image directory inherits that directory's ownership, so creating it
# here is what keeps /home/sail/.claude writable by the remote user.
mkdir -p "${_REMOTE_USER_HOME}/.claude"
chown "${_REMOTE_USER}:" "${_REMOTE_USER_HOME}/.claude"
