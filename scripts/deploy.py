#!/usr/bin/env python3
"""Upload the given repo-relative files to leadbird.app and lint any PHP on the server.

Usage: python3 scripts/deploy.py lib/SocialMediaService.php assets/js/app.js
"""

import json
import os
import posixpath
import sys
from pathlib import Path

import paramiko

ROOT = Path(__file__).resolve().parent.parent
CONFIG = json.loads(Path(os.environ.get("DEPLOY_CONFIG", ROOT / "deploy.json")).read_text())


def ensure_dirs(sftp, remote_dir):
    parts = remote_dir.strip("/").split("/")
    current = "/"
    for part in parts:
        current = posixpath.join(current, part)
        try:
            sftp.stat(current)
        except IOError:
            sftp.mkdir(current)


def main(files):
    if not files:
        print("Nothing to deploy.")
        return 1

    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(
        hostname=CONFIG["host"],
        port=CONFIG["port"],
        username=CONFIG["username"],
        password=CONFIG["password"],
        timeout=45,
    )

    sftp = client.open_sftp()
    remote_root = CONFIG["remotePath"].rstrip("/")
    uploaded = []

    for rel in files:
        local = ROOT / rel
        if not local.is_file():
            print(f"SKIP (missing locally): {rel}")
            continue
        remote = f"{remote_root}/{rel}"
        ensure_dirs(sftp, posixpath.dirname(remote))
        sftp.put(str(local), remote)
        uploaded.append(rel)
        print(f"UP  {rel}")

    sftp.close()

    php_files = [f"{remote_root}/{rel}" for rel in uploaded if rel.endswith(".php")]
    if php_files:
        lint_cmd = " && ".join(f"php -l {path}" for path in php_files)
        _, stdout, stderr = client.exec_command(lint_cmd)
        print(stdout.read().decode().strip())
        err = stderr.read().decode().strip()
        if err:
            print("LINT STDERR:", err)

    client.close()
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
