# Instagram RSS cache

Set `INSTAGRAM_RSS_URL` in a private `0600` env file outside web root. Point CLI cron at it through `INSTAGRAM_ENV_FILE`; use `17 * * * *`. The refresher accepts only HTTPS `rss.app` feeds, keeps logs private in `logs/instagram-refresh.log`, and retains existing cache if refresh produces no safe image. Homepage renders local cached WebP files; module 483 remains fallback when cache is absent or invalid.
