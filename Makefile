lwm_http_caching.mo: lwm_http_caching.po
	msgfmt -o $@ $^

lwm_http_caching.po: wp-http-cache.php
	xgettext -d lwm_http_caching --from-code utf-8 --keyword=__ --keyword=_e wp-http-cache.php