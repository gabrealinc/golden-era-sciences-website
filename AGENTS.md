# Golden Era Sciences website operating rules

## GitHub and WordPress alignment

- GitHub repository `gabrealinc/golden-era-sciences-website`, branch `main`, is the canonical source for the production WordPress theme and website code.
- Do not make production-only theme or code edits in WordPress. Make every website code change here, commit it, push it to `main`, allow WordPress.com GitHub Deployments to publish it, and verify the result on `goldenerasciences.com`.
- For every production-affecting release, increment `GE_VERSION` and confirm the live `golden-era-theme-version` meta tag matches the committed version on both the age gate and the verified full site.
- Before closing website work, confirm the local checkout is clean, local `main` equals `origin/main`, the remote branch contains the intended commit, and the live site serves the intended change.
- If an emergency WordPress edit is unavoidable, immediately reconcile that exact change back into GitHub and re-verify alignment before completing the task. Do not allow GitHub and production theme code to drift.

WordPress database content and settings, including products, orders, and plugin configuration, are not stored in this theme repository and must be verified separately when relevant.
