lwm_http_caching-locale.mo: lwm_http_caching-locale.po
	msgfmt -o $@ $^

lwm_http_caching-locale.po: wp-http-cache.php
	xgettext -d lwm_http_caching-locale --from-code utf-8 --keyword=__ --keyword=_e wp-http-cache.php